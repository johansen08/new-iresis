<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Jalur baru (menyimpang sengaja dari docs/LOST_SCAN.md §6 poin 4 lama):
 * HO menyelesaikan resi lost scan picker/packer LANGSUNG lewat nomor pegawai
 * ("no absen" = tblpegawai.kode_pegawai), tanpa menunggu paket diantar fisik
 * untuk discan ulang. Dipisah dari Lost_scan_picker_fcd/Packer_fcd supaya
 * jalur lama (yang masih mewajibkan scan ulang fisik) sama sekali tidak
 * tersentuh. Lihat docs/LOST_SCAN.md §12.
 */
class Lost_scan_selesai_fcd extends CI_Model
{
    const PENANDA_PICKING = 'LOST SCAN HO LANGSUNG';
    /** id_hakakses "client packer" di tblhakakses -- sama dengan Scan_paket_ndd_new_fcd::ROLE_PACKER. */
    const ROLE_PACKER = 4;

    /**
     * Satu transaksi: pastikan baris picking & packing ada (insert kalau
     * belum), tutup antrean tim picker kalau resi ini ada di sana, catat
     * tbllostscanpacker per baris yang baru dibuat. Tidak ada pencatatan KPI
     * sama sekali -- sama seperti precedent tambah_picker() -- karena bukan
     * picker/packer yang benar-benar mengerjakan ulang.
     *
     * @param string   $noresi
     * @param int|null $kode_picker_input kode_pegawai picker, wajib kalau resi belum di-picker
     * @param int|null $kode_packer_input kode_pegawai packer, wajib kalau resi belum di-packing
     * @param array    $user petugas HO yang memproses
     * @return array ['ok'=>TRUE, 'perlu_picker'=>bool, 'perlu_packer'=>bool,
     *                'nama_picker'=>?string, 'nama_packer'=>?string]
     *               atau ['error'=>TRUE, 'code'=>int, 'message'=>string, 'kode'=>string]
     */
    public function proses($noresi, $kode_picker_input, $kode_packer_input, $user)
    {
        $kode_picker_input = $kode_picker_input !== null ? (int) $kode_picker_input : null;
        $kode_packer_input = $kode_packer_input !== null ? (int) $kode_packer_input : null;

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $resi = $this->db->query(
            "SELECT id_printresi, noresi, status_pesanan, batal, id_kurir
             FROM tblprintresi WHERE noresi = ? LIMIT 1 FOR UPDATE",
            [$noresi]
        )->row();
        if (!$resi) {
            return $this->batal($prev_debug, 404, 'Nomor resi tidak ditemukan', 'NOT_FOUND');
        }
        if ($resi->status_pesanan === 'COMPLETED') {
            return $this->batal($prev_debug, 400, 'Pesanan sudah SELESAI', 'ORDER_COMPLETED');
        }
        if ($resi->status_pesanan === 'CANCELED' || (string) $resi->batal === '1') {
            $this->db->trans_rollback();
            $this->db->db_debug = $prev_debug;

            // Jejak paket cancel (docs/PAKET_CANCEL.md §7.1) -- titik penolakan baru,
            // di luar transaksi di atas supaya aman ditulis setelah rollback.
            $resi->noresi = $noresi;
            $this->load->model('cancel_paket_fcd');
            $this->cancel_paket_fcd->catat_tolak($resi, 'HO', $user, [
                'keterangan' => 'Selesaikan Lost Scan Langsung ditolak -- pesanan sudah DIBATALKAN',
            ]);

            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN', 'kode' => 'ORDER_CANCELED'];
        }

        $id_resi = (int) $resi->id_printresi;
        $now     = date('Y-m-d H:i:s');

        $nama_picker = null;
        $nama_packer = null;

        // ------------------------------------------------------------------
        // Picking (kalau belum ada)
        // ------------------------------------------------------------------
        $ada_picking = $this->db->query(
            "SELECT id_resiambilbarang FROM tblresiambilbarang WHERE id_resi = ? LIMIT 1",
            [$id_resi]
        )->row();
        $perlu_picker = empty($ada_picking);

        if ($perlu_picker) {
            if (empty($kode_picker_input) || $kode_picker_input <= 0) {
                return $this->batal($prev_debug, 400, 'No absen picker belum diisi', 'PICKER_KOSONG');
            }

            $picker = $this->db->get_where('tblpegawai', [
                'kode_pegawai' => $kode_picker_input,
                'status_aktif' => 'AKTIF',
            ])->row();
            if (!$picker) {
                return $this->batal($prev_debug, 400, 'No absen picker tidak ditemukan atau tidak aktif', 'PICKER_TIDAK_VALID');
            }

            $this->db->insert('tblresiambilbarang', [
                'id_resi'                 => $id_resi,
                'tanggal_resiambilbarang' => $now,
                'admin_pegawai'           => (int) $user['id_user'],
                'yangambil_pegawai'       => $kode_picker_input,
                'nama_komputer'           => self::PENANDA_PICKING,
                'pending'                 => '',
                'is_preorder'             => 0,
                'status_performa_id'      => null,
            ]);
            $id_rab = (int) $this->db->insert_id();
            if ($id_rab <= 0) {
                return $this->batal($prev_debug, 500, 'Gagal membuat baris picking', 'SAVE_FAILED');
            }

            $nama_picker = $picker->nama_pegawai;
            $id_lost_picker = $this->catat_lost_scan($resi, 'PICKER', $nama_picker, $user, $now);

            // Kalau resi ini sudah di antrean tim picker (PENDING), tutup
            // sekarang supaya tidak menggantung -- lihat
            // Lost_scan_picker_fcd::tambah_picker() cabang SELESAI_LUAR.
            $this->db->query(
                "UPDATE tbllostscanpicker_pending
                 SET status = 'SELESAI_LUAR', kode_picker = ?, diproses_oleh = ?,
                     waktu_proses = ?, id_resiambilbarang = ?, id_lostscanpacker = ?
                 WHERE id_printresi = ? AND status = 'PENDING'",
                [$kode_picker_input, (int) $user['id_user'], $now, $id_rab, $id_lost_picker, $id_resi]
            );
        }

        // ------------------------------------------------------------------
        // Packing (kalau belum ada)
        // ------------------------------------------------------------------
        $ada_packing = $this->db->query(
            "SELECT id_packing FROM tblpacking WHERE id_resi = ? LIMIT 1",
            [$id_resi]
        )->row();
        $perlu_packer = empty($ada_packing);

        if ($perlu_packer) {
            if (empty($kode_packer_input) || $kode_packer_input <= 0) {
                return $this->batal($prev_debug, 400, 'No absen packer belum diisi', 'PACKER_KOSONG');
            }

            $packer = $this->resolve_packer($kode_packer_input);
            if (!$packer) {
                return $this->batal($prev_debug, 400, 'No absen packer tidak ditemukan atau tidak ada akun packer aktif', 'PACKER_TIDAK_VALID');
            }

            $this->db->insert('tblpacking', [
                'id_resi'            => $id_resi,
                'tanggal_packing'    => $now,
                'packer_pegawai'     => (int) $packer->id_user,
                'keterangan'         => self::PENANDA_PICKING . ' (no absen ' . $kode_packer_input . ')',
                'status_performa_id' => null,
            ]);
            if ($this->db->affected_rows() <= 0) {
                return $this->batal($prev_debug, 500, 'Gagal membuat baris packing', 'SAVE_FAILED');
            }

            $nama_packer = $packer->nama_pegawai;
            $this->catat_lost_scan($resi, 'PACKER', $nama_packer, $user, $now);
        }

        if ($this->db->trans_status() === FALSE) {
            return $this->batal($prev_debug, 500, 'Gagal menyimpan, silakan ulangi', 'SAVE_FAILED');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $prev_debug;

        return [
            'ok'           => TRUE,
            'perlu_picker' => $perlu_picker,
            'perlu_packer' => $perlu_packer,
            'nama_picker'  => $nama_picker,
            'nama_packer'  => $nama_packer,
        ];
    }

    /**
     * Dipakai tombol "Simpan Lost Scan" (mode utama di panel, dipicu saat
     * resi NOT_PACKED atau NOT_PICKED): langsung catat baris tblpacking NYATA
     * atas nama packer sesuai no absen yang dimasukkan, tanggal_packing = saat
     * HO klik Simpan -- bukan cuma catatan tbllostscanpacker seperti
     * sebelumnya. Efeknya: scan HO berikutnya untuk resi ini lolos penjaga
     * is_packed tanpa packer harus benar-benar packing ulang secara fisik.
     *
     * Picker TIDAK disentuh di sini -- kalau resi juga NOT_PICKED, controller
     * tetap memakai alur lama (lapor ke antrean tim picker) setelah method
     * ini sukses, persis seperti sebelumnya.
     *
     * @param string $noresi
     * @param int    $kode_packer_input kode_pegawai packer (wajib)
     * @param array  $user petugas HO yang klik Simpan
     * @return array ['ok'=>TRUE, 'nama_packer'=>string] atau
     *               ['error'=>TRUE, 'code'=>int, 'message'=>string, 'kode'=>string]
     */
    public function simpan_packer($noresi, $kode_packer_input, $user)
    {
        $kode_packer_input = (int) $kode_packer_input;

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $resi = $this->db->query(
            "SELECT id_printresi, noresi, status_pesanan, batal, id_kurir
             FROM tblprintresi WHERE noresi = ? LIMIT 1 FOR UPDATE",
            [$noresi]
        )->row();
        if (!$resi) {
            return $this->batal($prev_debug, 404, 'Nomor resi tidak ditemukan', 'NOT_FOUND');
        }
        if ($resi->status_pesanan === 'COMPLETED') {
            return $this->batal($prev_debug, 400, 'Pesanan sudah SELESAI', 'ORDER_COMPLETED');
        }
        if ($resi->status_pesanan === 'CANCELED' || (string) $resi->batal === '1') {
            $this->db->trans_rollback();
            $this->db->db_debug = $prev_debug;

            $resi->noresi = $noresi;
            $this->load->model('cancel_paket_fcd');
            $this->cancel_paket_fcd->catat_tolak($resi, 'HO', $user, [
                'keterangan' => 'Simpan Lost Scan Packer ditolak -- pesanan sudah DIBATALKAN',
            ]);

            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN', 'kode' => 'ORDER_CANCELED'];
        }

        $id_resi = (int) $resi->id_printresi;

        $ada_packing = $this->db->query(
            "SELECT id_packing FROM tblpacking WHERE id_resi = ? LIMIT 1",
            [$id_resi]
        )->row();
        if ($ada_packing) {
            return $this->batal($prev_debug, 400, 'Nomor resi sudah di-packing (Double Scan)', 'ALREADY_PACKED');
        }

        if ($kode_packer_input <= 0) {
            return $this->batal($prev_debug, 400, 'No absen packer belum diisi', 'PACKER_KOSONG');
        }

        $packer = $this->resolve_packer($kode_packer_input);
        if (!$packer) {
            return $this->batal($prev_debug, 400, 'No absen packer tidak ditemukan atau tidak ada akun packer aktif', 'PACKER_TIDAK_VALID');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert('tblpacking', [
            'id_resi'            => $id_resi,
            'tanggal_packing'    => $now,
            'packer_pegawai'     => (int) $packer->id_user,
            'keterangan'         => self::PENANDA_PICKING . ' (no absen ' . $kode_packer_input . ')',
            'status_performa_id' => null,
        ]);
        if ($this->db->affected_rows() <= 0) {
            return $this->batal($prev_debug, 500, 'Gagal membuat baris packing', 'SAVE_FAILED');
        }

        $this->catat_lost_scan($resi, 'PACKER', $packer->nama_pegawai, $user, $now);

        if ($this->db->trans_status() === FALSE) {
            return $this->batal($prev_debug, 500, 'Gagal menyimpan, silakan ulangi', 'SAVE_FAILED');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $prev_debug;

        return ['ok' => TRUE, 'nama_packer' => $packer->nama_pegawai];
    }

    /**
     * Resolusi no absen (kode_pegawai) ke akun packer aktif -- query sama
     * dengan Scan_paket_ndd_new_fcd::daftar_packer(), difilter ke satu
     * kode_pegawai. Dipakai proses() dan simpan_packer(). Memastikan hanya
     * akun packer AKTIF yang bisa dipakai, karena tblpacking.packer_pegawai
     * adalah tbluser.id_user, bukan tblpegawai.kode_pegawai.
     */
    private function resolve_packer($kode_packer_input)
    {
        return $this->db->query(
            "SELECT u.id_user, p.kode_pegawai, p.nama_pegawai
             FROM tbluser u
             JOIN tblpegawai p ON p.kode_pegawai = u.id_pegawai
             WHERE u.hakakses = ? AND u.isactive = 1 AND p.kode_pegawai = ?
             LIMIT 1",
            [self::ROLE_PACKER, $kode_packer_input]
        )->row();
    }

    /**
     * Catat tbllostscanpacker, dup-dicek per (noresi, lost_type) -- bukan per
     * noresi saja seperti Lost_scan_packer_fcd::save() lama. Kolom sama
     * dengan Lost_scan_picker_fcd::tambah_picker(). Mengembalikan id baris
     * (baru atau yang sudah ada).
     */
    private function catat_lost_scan($resi, $lost_type, $nama_pegawai, $user, $now)
    {
        $ada = $this->db->select('id_lostscanpacker')
            ->get_where('tbllostscanpacker', ['noresi' => $resi->noresi, 'lost_type' => $lost_type])
            ->row();
        if ($ada) {
            return (int) $ada->id_lostscanpacker;
        }

        $kurir = $this->db->select('nama_kurir')->get_where('tblkurir', ['id_kurir' => $resi->id_kurir])->row();

        $this->db->insert('tbllostscanpacker', [
            'noresi'      => $resi->noresi,
            'lost_type'   => $lost_type,
            'nama_packer' => $nama_pegawai,
            'status_resi' => $resi->status_pesanan,
            'kurir'       => $kurir ? $kurir->nama_kurir : 'NOT FOUND',
            'created_at'  => $now,
            'created_by'  => (int) $user['id_user'],
        ]);

        return (int) $this->db->insert_id();
    }

    /** Rollback + pulihkan db_debug + bentuk error seragam. */
    private function batal($prev_debug, $code, $message, $kode)
    {
        $this->db->trans_rollback();
        $this->db->db_debug = $prev_debug;
        return ['error' => TRUE, 'code' => $code, 'message' => $message, 'kode' => $kode];
    }
}
