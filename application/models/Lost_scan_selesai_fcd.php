<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Jalur baru (menyimpang sengaja dari docs/LOST_SCAN.md §6 poin 4 lama):
 * HO mengisi data packer/picker resi lost scan LANGSUNG lewat no absen,
 * tanpa menunggu paket diantar fisik untuk discan ulang. Dipisah dari
 * Lost_scan_picker_fcd/Packer_fcd supaya jalur lama sama sekali tidak
 * tersentuh. Lihat docs/LOST_SCAN.md §12.
 *
 * "No absen" = angka di ujung nama pegawai menurut konvensi penamaan
 * "NAMA - JABATAN - NOABSEN" (mis. "DEWI - QC - 0288" -> 288). BUKAN
 * kode_pegawai/id_user: DEWI ber-id 57. Kolom absen terpisah tidak ada.
 */
class Lost_scan_selesai_fcd extends CI_Model
{
    const PENANDA_PICKING = 'LOST SCAN HO LANGSUNG';
    /** id_hakakses "client packer" di tblhakakses. */
    const ROLE_PACKER = 4;

    /**
     * Tombol "Simpan Lost Scan". Satu transaksi: pastikan baris picking &
     * packing ada (insert kalau belum, tanggal = saat HO klik Simpan), tutup
     * antrean tim picker kalau resi ini ada di sana, catat tbllostscanpacker
     * per baris yang baru dibuat. Controller lalu memanggil
     * Scan_logistic_fcd::save_scan() supaya resi langsung masuk HO/NDD.
     *
     * Detail resi (Receipt_fcd::get_detail) membaca Nama Packer dari
     * tbluser.name lewat tblpacking.packer_pegawai, Nama Picker dari
     * tblpegawai lewat yangambil_pegawai -- keduanya langsung terisi.
     *
     * Tidak ada pencatatan KPI -- sama seperti precedent tambah_picker() --
     * karena bukan picker/packer yang benar-benar mengerjakan ulang.
     *
     * @param string   $noresi
     * @param int|null $no_absen_picker wajib kalau resi belum di-picker
     * @param int|null $no_absen_packer wajib kalau resi belum di-packing
     * @param array    $user petugas HO yang memproses
     * @return array ['ok'=>TRUE, 'perlu_picker'=>bool, 'perlu_packer'=>bool,
     *                'nama_picker'=>?string, 'nama_packer'=>?string, 'tanggal'=>string]
     *               atau ['error'=>TRUE, 'code'=>int, 'message'=>string, 'kode'=>string]
     */
    public function proses($noresi, $no_absen_picker, $no_absen_packer, $user)
    {
        $no_absen_picker = (int) $no_absen_picker;
        $no_absen_packer = (int) $no_absen_packer;

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $resi = $this->kunci_resi($noresi, $prev_debug, $user, 'Simpan Lost Scan');
        if (is_array($resi)) {
            return $resi;
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
            if ($no_absen_picker <= 0) {
                return $this->batal($prev_debug, 400, 'No absen picker belum diisi', 'PICKER_KOSONG');
            }

            $picker = $this->cari_picker($no_absen_picker);
            if (is_string($picker)) {
                return $this->batal($prev_debug, 400, $picker, 'PICKER_TIDAK_VALID');
            }

            $this->db->insert('tblresiambilbarang', [
                'id_resi'                 => $id_resi,
                'tanggal_resiambilbarang' => $now,
                'admin_pegawai'           => (int) $user['id_user'],
                'yangambil_pegawai'       => (int) $picker->kode_pegawai,
                'nama_komputer'           => self::PENANDA_PICKING,
                'pending'                 => '',
                'is_preorder'             => 0,
                'status_performa_id'      => null,
            ]);
            $id_rab = (int) $this->db->insert_id();
            if ($id_rab <= 0) {
                return $this->batal($prev_debug, 500, 'Gagal membuat baris picking', 'SAVE_FAILED');
            }

            $nama_picker = $picker->nama;
            $id_lost_picker = $this->catat_lost_scan($resi, 'PICKER', $nama_picker, $user, $now);

            // Kalau resi ini sudah di antrean tim picker (PENDING), tutup
            // sekarang supaya tidak menggantung -- lihat
            // Lost_scan_picker_fcd::tambah_picker() cabang SELESAI_LUAR.
            $this->db->query(
                "UPDATE tbllostscanpicker_pending
                 SET status = 'SELESAI_LUAR', kode_picker = ?, diproses_oleh = ?,
                     waktu_proses = ?, id_resiambilbarang = ?, id_lostscanpacker = ?
                 WHERE id_printresi = ? AND status = 'PENDING'",
                [(int) $picker->kode_pegawai, (int) $user['id_user'], $now, $id_rab, $id_lost_picker, $id_resi]
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
            if ($no_absen_packer <= 0) {
                return $this->batal($prev_debug, 400, 'No absen packer belum diisi', 'PACKER_KOSONG');
            }

            $packer = $this->cari_packer($no_absen_packer);
            if (is_string($packer)) {
                return $this->batal($prev_debug, 400, $packer, 'PACKER_TIDAK_VALID');
            }

            if (!$this->tulis_packing($id_resi, $packer, $no_absen_packer, $now)) {
                return $this->batal($prev_debug, 500, 'Gagal membuat baris packing', 'SAVE_FAILED');
            }

            $nama_packer = $packer->nama;
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
            'tanggal'      => $now,
        ];
    }

    /**
     * Kunci baris resi (FOR UPDATE) dan tolak resi selesai/batal. Untuk resi
     * batal, transaksi di-rollback dulu lalu jejak paket cancel dicatat
     * (docs/PAKET_CANCEL.md §7.1).
     *
     * @return object|array baris resi, atau array error (transaksi sudah ditutup)
     */
    private function kunci_resi($noresi, $prev_debug, $user, $asal)
    {
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
            $hasil = $this->batal($prev_debug, 400, 'Pesanan sudah DIBATALKAN', 'ORDER_CANCELED');

            $this->load->model('cancel_paket_fcd');
            $this->cancel_paket_fcd->catat_tolak($resi, 'HO', $user, [
                'keterangan' => $asal . ' ditolak -- pesanan sudah DIBATALKAN',
            ]);

            return $hasil;
        }

        return $resi;
    }

    private function tulis_packing($id_resi, $packer, $no_absen_packer, $now)
    {
        $this->db->insert('tblpacking', [
            'id_resi'            => $id_resi,
            'tanggal_packing'    => $now,
            'packer_pegawai'     => (int) $packer->id_user,
            'keterangan'         => self::PENANDA_PICKING . ' (no absen ' . sprintf('%04d', $no_absen_packer) . ')',
            'status_performa_id' => null,
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Akun packer aktif pemilik no absen. tblpacking.packer_pegawai adalah
     * tbluser.id_user, dan detail resi menampilkan tbluser.name -- jadi yang
     * dicocokkan langsung nama akunnya.
     *
     * @return object|string baris (id_user, nama) atau pesan error
     */
    private function cari_packer($no_absen)
    {
        $rows = $this->db->query(
            "SELECT u.id_user, u.name AS nama
             FROM tbluser u
             WHERE u.hakakses = ? AND u.isactive = 1
               AND CAST(TRIM(SUBSTRING_INDEX(u.name, '-', -1)) AS UNSIGNED) = ?
             LIMIT 2",
            [self::ROLE_PACKER, $no_absen]
        )->result();

        return $this->satu_hasil($rows, 'packer', $no_absen, 'akun packer aktif');
    }

    /**
     * Picker pemilik no absen di Master Picker aktif (tblnamaambilbarang --
     * daftar yang sama dengan dropdown Tambahkan Picker tim picker). Picker
     * dicatat per kode_pegawai di tblresiambilbarang.yangambil_pegawai,
     * tidak butuh akun login.
     *
     * @return object|string baris (kode_pegawai, nama) atau pesan error
     */
    private function cari_picker($no_absen)
    {
        $rows = $this->db->query(
            "SELECT p.kode_pegawai, p.nama_pegawai AS nama
             FROM tblnamaambilbarang t
             JOIN tblpegawai p ON p.kode_pegawai = t.id_pegawai
             WHERE t.status_aktif = 'AKTIF' AND p.status_aktif = 'AKTIF'
               AND CAST(TRIM(SUBSTRING_INDEX(p.nama_pegawai, '-', -1)) AS UNSIGNED) = ?
             LIMIT 2",
            [$no_absen]
        )->result();

        return $this->satu_hasil($rows, 'picker', $no_absen, 'Master Picker aktif');
    }

    /** No absen harus menunjuk tepat satu orang -- beberapa no absen dipakai ganda di tblpegawai. */
    private function satu_hasil(array $rows, $peran, $no_absen, $sumber)
    {
        $absen = sprintf('%04d', $no_absen);

        if (count($rows) === 0) {
            return 'No absen ' . $peran . ' ' . $absen . ' tidak ditemukan di ' . $sumber;
        }
        if (count($rows) > 1) {
            return 'No absen ' . $peran . ' ' . $absen . ' dipakai lebih dari satu orang (' .
                $rows[0]->nama . ', ' . $rows[1]->nama . ') -- hubungi admin';
        }

        return $rows[0];
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
