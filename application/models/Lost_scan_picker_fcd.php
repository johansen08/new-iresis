<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Antrean "Lost Scan Picker": resi yang ditolak karena belum di-picker,
 * dilaporkan packer (tahap B) atau HO (tahap C), lalu diselesaikan tim
 * picker lewat tombol Tambahkan Picker (menu TIM PICKER -> Laporan Lost Scan
 * Picker).
 *
 * Keputusan yang dikunci di sini:
 * - Pelapor tidak memilih picker. Nama picker baru ada saat tim picker
 *   memproses, dan baru saat itu pula baris tbllostscanpacker tipe PICKER
 *   dibuat -- tidak pernah ada catatan lost scan tanpa nama.
 * - Baris tblresiambilbarang dibuat demi integritas data supaya packer bisa
 *   scan ulang; KPI picker TIDAK dicatat (hukumannya sudah tercatat sebagai
 *   lost scan). nama_komputer = 'LOST SCAN PICKER' jadi penanda.
 * - Lost_scan_packer_fcd::save() tidak dipakai karena menolak duplikat per
 *   noresi tanpa melihat lost_type; HO mungkin sudah mencatat PACKER untuk
 *   resi yang sama.
 */
class Lost_scan_picker_fcd extends CI_Model
{
    const TABEL = 'tbllostscanpicker_pending';
    const PENANDA_KOMPUTER = 'LOST SCAN PICKER';

    // ------------------------------------------------------------------
    // Dipakai tahap B (packer) dan C (HO)
    // ------------------------------------------------------------------

    /**
     * Buat laporan untuk satu resi. Tidak pernah membuat dua PENDING untuk
     * resi yang sama.
     */
    public function lapor($noresi, $sumber, $user)
    {
        $noresi = trim((string) $noresi);
        $sumber = ($sumber === 'HO') ? 'HO' : 'PACKER';

        if ($noresi === '') {
            return ['status' => 'TIDAK_DITEMUKAN', 'pending' => null, 'message' => 'Nomor resi kosong'];
        }

        $resi = $this->db->query(
            "SELECT p.id_printresi,
                    EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = p.id_printresi) AS is_picked
             FROM tblprintresi p
             WHERE p.noresi = ?
             LIMIT 1",
            [$noresi]
        )->row();

        if (!$resi) {
            return ['status' => 'TIDAK_DITEMUKAN', 'pending' => null, 'message' => 'Nomor resi tidak ditemukan'];
        }
        if ($resi->is_picked) {
            return ['status' => 'SUDAH_PICKED', 'pending' => null, 'message' => 'Resi sudah di-picker, tidak perlu dilaporkan'];
        }

        $pending = $this->cari_pending($noresi);
        if ($pending) {
            return ['status' => 'SUDAH_PENDING', 'pending' => $pending, 'message' => 'Resi sudah dilaporkan, menunggu tim picker'];
        }

        $this->db->insert(self::TABEL, [
            'id_printresi'    => (int) $resi->id_printresi,
            'noresi'          => $noresi,
            'sumber'          => $sumber,
            'dilaporkan_oleh' => (int) $user['id_user'],
            'waktu_lapor'     => date('Y-m-d H:i:s'),
            'status'          => 'PENDING',
        ]);

        return ['status' => 'DIBUAT', 'pending' => $this->cari_pending($noresi), 'message' => 'Laporan lost scan picker dibuat'];
    }

    /** Baris PENDING untuk resi itu beserta nama pelapor, atau null. */
    public function cari_pending($noresi)
    {
        $row = $this->db->query(
            "SELECT t.*, u.name AS nama_pelapor
             FROM " . self::TABEL . " t
             LEFT JOIN tbluser u ON u.id_user = t.dilaporkan_oleh
             WHERE t.noresi = ? AND t.status = 'PENDING'
             ORDER BY t.id_pending DESC
             LIMIT 1",
            [$noresi]
        )->row_array();

        return $row ? $row : null;
    }

    // ------------------------------------------------------------------
    // Halaman laporan
    // ------------------------------------------------------------------

    public function jumlah_pending()
    {
        return (int) $this->db->where('status', 'PENDING')->count_all_results(self::TABEL);
    }

    /**
     * Data DataTables. Tab pending: SEMUA baris PENDING (tanpa filter
     * tanggal -- antrean tidak boleh tersembunyi). Tab selesai: SELESAI dan
     * SELESAI_LUAR yang waktu_proses-nya di rentang.
     */
    public function daftar($params)
    {
        $tab = ($params['tab'] === 'selesai') ? 'selesai' : 'pending';

        $this->terapkan_filter($params, $tab);
        $total = $this->db->count_all_results(self::TABEL . ' t');

        $this->terapkan_filter($params, $tab);
        $this->db->select("t.*, u.name AS nama_pelapor, up.name AS nama_pemroses, pg.nama_pegawai AS nama_picker,
            EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = t.id_printresi) AS sudah_picked", FALSE);

        $urut = !empty($params['order']) ? $params['order'] : ($tab === 'pending' ? 't.waktu_lapor' : 't.waktu_proses');
        $arah = (isset($params['dir']) && strtolower($params['dir']) === 'asc') ? 'ASC' : 'DESC';
        $this->db->order_by($urut, $arah, FALSE);

        if (isset($params['length']) && (int) $params['length'] > 0) {
            $this->db->limit((int) $params['length'], (int) $params['start']);
        }

        $rows = $this->db->get(self::TABEL . ' t')->result_array();

        return ['rows' => $rows, 'total' => $total];
    }

    private function terapkan_filter($params, $tab)
    {
        $this->db->join('tbluser u', 'u.id_user = t.dilaporkan_oleh', 'left');
        $this->db->join('tbluser up', 'up.id_user = t.diproses_oleh', 'left');
        $this->db->join('tblpegawai pg', 'pg.kode_pegawai = t.kode_picker', 'left');

        if ($tab === 'pending') {
            $this->db->where('t.status', 'PENDING');
        } else {
            $this->db->where_in('t.status', ['SELESAI', 'SELESAI_LUAR']);
            $this->db->where('t.waktu_proses >=', $params['start_date']);
            $this->db->where('t.waktu_proses <=', $params['end_date']);
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('t.noresi', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->or_like('pg.nama_pegawai', $params['search']);
            $this->db->group_end();
        }
    }

    /**
     * Item (SKU x qty @ rak) untuk sekumpulan resi -- satu query untuk satu
     * halaman tabel, bukan satu query per baris.
     */
    public function item_resi(array $id_list)
    {
        $hasil = [];
        $id_list = array_values(array_unique(array_map('intval', $id_list)));
        if (empty($id_list)) {
            return $hasil;
        }

        $rows = $this->db->query(
            "SELECT dr.id_resi, dr.sku, dr.jumlah, dr.no_rak,
                    COALESCE(NULLIF(NULLIF(TRIM(s.nama_sku), ''), 'False'), '') AS nama_sku
             FROM tbldetailprintresi dr
             LEFT JOIN tblsku s ON s.id_sku = dr.sku
             WHERE dr.id_resi IN (" . implode(',', $id_list) . ")
             ORDER BY dr.id_resi, dr.no_rak, dr.sku"
        )->result_array();

        foreach ($rows as $r) {
            $hasil[(int) $r['id_resi']][] = $r;
        }
        return $hasil;
    }

    // ------------------------------------------------------------------
    // Proses "Tambahkan Picker"
    // ------------------------------------------------------------------

    /**
     * Satu transaksi:
     *  1. kunci baris pending (FOR UPDATE), harus PENDING
     *  2. resi ada & tidak batal
     *  3. kalau picking sudah ada (dibuat di luar alur, mis. SCAN COMBINED)
     *     -> tutup sebagai SELESAI_LUAR, tidak menulis picking/lost scan
     *  4. picker valid & aktif
     *  5. insert tblresiambilbarang atas nama picker (tanpa KPI)
     *  6. insert tbllostscanpacker tipe PICKER (unik per noresi+PICKER)
     *  7. tutup pending -> SELESAI
     */
    public function tambah_picker($id_pending, $kode_picker, $user)
    {
        $id_pending  = (int) $id_pending;
        $kode_picker = (int) $kode_picker;

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $pending = $this->db->query("SELECT * FROM " . self::TABEL . " WHERE id_pending = ? FOR UPDATE", [$id_pending])->row();
        if (!$pending) {
            return $this->batal($prev_debug, 404, 'Laporan tidak ditemukan', 'TIDAK_DITEMUKAN');
        }
        if ($pending->status !== 'PENDING') {
            $pemroses = $this->db->select('name')->get_where('tbluser', ['id_user' => (int) $pending->diproses_oleh])->row();
            $oleh = $pemroses ? $pemroses->name : '-';
            return $this->batal($prev_debug, 409, 'Laporan sudah diproses oleh ' . $oleh, 'SUDAH_DIPROSES');
        }

        $resi = $this->db->query(
            "SELECT p.id_printresi, p.noresi, p.status_pesanan, p.batal, k.nama_kurir
             FROM tblprintresi p
             LEFT JOIN tblkurir k ON k.id_kurir = p.id_kurir
             WHERE p.id_printresi = ?
             LIMIT 1",
            [(int) $pending->id_printresi]
        )->row();
        if (!$resi) {
            return $this->batal($prev_debug, 404, 'Resi tidak ditemukan di tblprintresi', 'TIDAK_DITEMUKAN');
        }
        if (strtoupper((string) $resi->status_pesanan) === 'CANCELED' || (string) $resi->batal === '1') {
            return $this->batal($prev_debug, 400, 'Pesanan sudah DIBATALKAN -- tidak dibuatkan picking', 'ORDER_CANCELED');
        }

        $now = date('Y-m-d H:i:s');

        // 3. Sudah di-picker di luar alur -> tutup saja.
        $picking_ada = $this->db->query(
            "SELECT a.id_resiambilbarang, a.yangambil_pegawai, pg.nama_pegawai
             FROM tblresiambilbarang a
             LEFT JOIN tblpegawai pg ON pg.kode_pegawai = a.yangambil_pegawai
             WHERE a.id_resi = ?
             LIMIT 1",
            [(int) $resi->id_printresi]
        )->row();
        if ($picking_ada) {
            $this->db->where('id_pending', $id_pending)->update(self::TABEL, [
                'status'             => 'SELESAI_LUAR',
                'kode_picker'        => (int) $picking_ada->yangambil_pegawai,
                'diproses_oleh'      => (int) $user['id_user'],
                'waktu_proses'       => $now,
                'id_resiambilbarang' => (int) $picking_ada->id_resiambilbarang,
            ]);
            if ($this->db->trans_status() === FALSE) {
                return $this->batal($prev_debug, 500, 'Gagal menutup laporan', 'SAVE_FAILED');
            }
            $this->db->trans_commit();
            $this->db->db_debug = $prev_debug;
            return [
                'status'      => 'SELESAI_LUAR',
                'noresi'      => $resi->noresi,
                'nama_picker' => $picking_ada->nama_pegawai ?: ('PEGAWAI #' . (int) $picking_ada->yangambil_pegawai),
            ];
        }

        // 4. Picker
        if ($kode_picker <= 0) {
            return $this->batal($prev_debug, 400, 'Picker belum dipilih', 'PICKER_KOSONG');
        }
        $picker = $this->db->get_where('tblpegawai', ['kode_pegawai' => $kode_picker, 'status_aktif' => 'AKTIF'])->row();
        if (!$picker) {
            return $this->batal($prev_debug, 400, 'Picker tidak ditemukan atau tidak aktif', 'PICKER_TIDAK_VALID');
        }

        // 5. Picking atas nama picker -- pola kolom sama dengan
        //    Resi_team_fcd::save_combined_scan(), minus KPI.
        $this->db->insert('tblresiambilbarang', [
            'id_resi'                 => (int) $resi->id_printresi,
            'tanggal_resiambilbarang' => $now,
            'admin_pegawai'           => (int) $user['id_user'],
            'yangambil_pegawai'       => $kode_picker,
            'nama_komputer'           => self::PENANDA_KOMPUTER,
            'pending'                 => '',
            'is_preorder'             => 0,
            'status_performa_id'      => $this->status_performa_picker($kode_picker),
        ]);
        $id_rab = (int) $this->db->insert_id();
        if ($id_rab <= 0) {
            return $this->batal($prev_debug, 500, 'Gagal membuat baris picking', 'SAVE_FAILED');
        }

        // 6. Catatan lost scan PICKER (kolom sama dengan Lost_scan_packer_fcd::save()).
        $lost = $this->db->select('id_lostscanpacker')
            ->get_where('tbllostscanpacker', ['noresi' => $resi->noresi, 'lost_type' => 'PICKER'])
            ->row();
        if ($lost) {
            $id_lost = (int) $lost->id_lostscanpacker;
        } else {
            $this->db->insert('tbllostscanpacker', [
                'noresi'      => $resi->noresi,
                'lost_type'   => 'PICKER',
                'nama_packer' => $picker->nama_pegawai,
                'status_resi' => $resi->status_pesanan,
                'kurir'       => $resi->nama_kurir ?: 'NOT FOUND',
                'created_at'  => $now,
                'created_by'  => (int) $user['id_user'],
            ]);
            $id_lost = (int) $this->db->insert_id();
        }

        // 7. Tutup pending
        $this->db->where('id_pending', $id_pending)->update(self::TABEL, [
            'status'             => 'SELESAI',
            'kode_picker'        => $kode_picker,
            'diproses_oleh'      => (int) $user['id_user'],
            'waktu_proses'       => $now,
            'id_resiambilbarang' => $id_rab,
            'id_lostscanpacker'  => $id_lost,
        ]);

        if ($this->db->trans_status() === FALSE) {
            return $this->batal($prev_debug, 500, 'Gagal menyimpan, silakan ulangi', 'SAVE_FAILED');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $prev_debug;

        return [
            'status'      => 'SELESAI',
            'noresi'      => $resi->noresi,
            'nama_picker' => $picker->nama_pegawai,
        ];
    }

    /** Rollback + pulihkan db_debug + bentuk error seragam. */
    private function batal($prev_debug, $code, $message, $kode)
    {
        $this->db->trans_rollback();
        $this->db->db_debug = $prev_debug;
        return ['error' => TRUE, 'code' => $code, 'message' => $message, 'kode' => $kode];
    }

    /**
     * Status performa picker hari ini (lewat akun tbluser yang terhubung ke
     * pegawai), fallback NORMAL_PICKER, fallback NULL. Sama dengan urutan di
     * save_combined_scan().
     */
    private function status_performa_picker($kode_picker)
    {
        $akun = $this->db->select('id_user')->get_where('tbluser', ['id_pegawai' => $kode_picker, 'isactive' => 1])->row();
        if ($akun) {
            $st = $this->db->select('id_statusperforma')
                ->get_where('tblstatusperforma', ['id_user' => $akun->id_user, 'tanggal' => date('Y-m-d'), 'isactive' => 1])
                ->row();
            if ($st) {
                return (int) $st->id_statusperforma;
            }
        }

        $normal = $this->db->select('id_statusperforma')
            ->get_where('tblmasterstatusperforma', ['kode_status' => 'NORMAL_PICKER', 'isactive' => 1])
            ->row();

        return $normal ? (int) $normal->id_statusperforma : null;
    }
}
