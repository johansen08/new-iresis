<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scan_logistic_fcd extends CI_Model
{
    /**
     * Simpan satu hasil scan.
     *
     * Alur validasi (5-6 query terpisah) digabung jadi SATU query: kolom resi
     * plus 4 flag EXISTS. Semua sub-query memakai index id_resi tabel masing-
     * masing, jadi biayanya tetap murah tapi round-trip ke DB turun drastis --
     * ini yang paling terasa saat scanner gun menembak beruntun.
     *
     * Insert dibungkus transaksi + INSERT ... WHERE NOT EXISTS supaya double
     * Enter dari scanner tidak pernah menghasilkan baris ganda.
     *
     * Status index per 2026-09-12:
     *   tblscan_ndd   -- uq_scan_ndd_resi (UNIQUE) SUDAH terpasang, jadi
     *                    jaring pengaman DB-nya nyata. Catatan lama di sini
     *                    yang bilang "belum dipasang" sudah tidak berlaku.
     *   tblresikeluar -- id_resi BELUM unik, jadi pola WHERE NOT EXISTS di
     *                    bawah masih bisa kebobolan kalau dua stasiun men-scan
     *                    resi yang sama pada saat bersamaan. Skrip pemasangan
     *                    UNIQUE-nya ada di dev_tools/optimasi_scan_ho.sql
     *                    (sudah diverifikasi 0 duplikat dari 2.525.459 baris).
     */
    public function save_scan($noresi, $user, $type)
    {
        $noresi = trim((string) $noresi);
        if ($noresi === '') {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi kosong', 'data' => ['EXCEPTION_CODE' => 'EMPTY_RESI']];
        }

        $is_ndd_mode = ($type == 'NDD');

        $receipt = $this->db->query(
            "SELECT p.id_printresi, p.status_pesanan, p.batal, p.id_kurir,
                    EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = p.id_printresi) AS is_picked,
                    EXISTS(SELECT 1 FROM tblpacking k        WHERE k.id_resi = p.id_printresi) AS is_packed,
                    EXISTS(SELECT 1 FROM tblresikeluar h     WHERE h.id_resi = p.id_printresi) AS is_handover,
                    EXISTS(SELECT 1 FROM tblscan_ndd n       WHERE n.id_resi = p.id_printresi) AS is_ndd
             FROM tblprintresi p
             WHERE p.noresi = ?
             LIMIT 1",
            [$noresi]
        )->row();

        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 404, 'message' => 'Nomor resi tidak ditemukan', 'data' => ['EXCEPTION_CODE' => 'NOT_FOUND']];
        }

        // Kurir Shopee hanya REGULER, tidak pernah masuk NDD.
        if ($is_ndd_mode && $receipt->id_kurir == 7) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Kurir Shopee adalah REGULER, tidak masuk NDD. Silakan gunakan mode REGULER.', 'data' => ['EXCEPTION_CODE' => 'NOT_NDD']];
        }

        // Cek tahapan (aturan sama dengan menu Handover)
        if ($receipt->status_pesanan == 'COMPLETED') {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah SELESAI', 'data' => ['EXCEPTION_CODE' => 'ORDER_COMPLETED']];
        }
        if ($receipt->status_pesanan == 'CANCELED' || $receipt->batal == '1' || $receipt->batal == 1) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN', 'data' => ['EXCEPTION_CODE' => 'ORDER_CANCELED']];
        }
        if (!$receipt->is_picked) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor Resi belum di-picker.', 'data' => ['EXCEPTION_CODE' => 'NOT_PICKED']];
        }
        if (!$receipt->is_packed) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi belum di-packing.', 'data' => ['EXCEPTION_CODE' => 'NOT_PACKED']];
        }

        // Mode REGULER: sudah pernah scan keluar = dobel, tolak.
        // Mode NDD    : sudah ada di HO tidak masalah, yang dicek adalah baris NDD.
        if ($receipt->is_handover && !$is_ndd_mode) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi sudah di-scan keluar (Double Scan).', 'data' => ['EXCEPTION_CODE' => 'ALREADY_HANDOVER']];
        }
        if ($is_ndd_mode && $receipt->is_ndd) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi sudah di-scan NDD (Double Scan).', 'data' => ['EXCEPTION_CODE' => 'ALREADY_SCANNED']];
        }

        $id_resi = (int) $receipt->id_printresi;
        $now     = date('Y-m-d H:i:s');

        // db_debug dimatikan sesaat: bila UNIQUE index sudah terpasang, bentrok
        // 1062 dari dua request di detik yang sama tidak boleh memunculkan
        // halaman error HTML (itu merusak parsing JSON di sisi scanner).
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $ho_inserted = FALSE;
        if (!$receipt->is_handover) {
            $this->db->query(
                "INSERT INTO tblresikeluar (id_resi, tanggal_resikeluar, sudah_cetak, tanggal_cetak, id_pegawai)
                 SELECT ?, ?, '-', '', ?
                 WHERE NOT EXISTS (SELECT 1 FROM tblresikeluar WHERE id_resi = ?)",
                [$id_resi, $now, $user['id_user'], $id_resi]
            );
            $ho_inserted = ($this->db->affected_rows() > 0);

            // CATATAN: menu Handover ikut mengisi tblprintresi.trip di sini, dan
            // Cron RTS memfilter pr.trip -- artinya paket yang keluar lewat menu
            // ini tidak pernah terjaring cek RTS. Sengaja TIDAK diisi dari sini
            // supaya menu ini tidak mengubah isi daftar RTS / notifikasi WA.
        }

        $ndd_inserted = FALSE;
        if ($is_ndd_mode) {
            $this->db->query(
                "INSERT INTO tblscan_ndd (id_resi, tanggal_scan, id_pegawai)
                 SELECT ?, ?, ?
                 WHERE NOT EXISTS (SELECT 1 FROM tblscan_ndd WHERE id_resi = ?)",
                [$id_resi, $now, $user['id_user'], $id_resi]
            );
            $ndd_inserted = ($this->db->affected_rows() > 0);

            if (!$ndd_inserted) {
                // Kalah balapan dengan request kembar -> batalkan semuanya,
                // termasuk baris HO yang baru saja dibuat.
                $this->db->trans_rollback();
                $this->db->db_debug = $prev_debug;
                return ['error' => TRUE, 'code' => 409, 'message' => 'Nomor resi sudah di-scan NDD (Double Scan).', 'data' => ['EXCEPTION_CODE' => 'ALREADY_SCANNED']];
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->db->db_debug = $prev_debug;
            return ['error' => TRUE, 'code' => 500, 'message' => 'Gagal menyimpan scan, silakan ulangi.', 'data' => ['EXCEPTION_CODE' => 'SAVE_FAILED']];
        }

        $this->db->trans_commit();
        $this->db->db_debug = $prev_debug;

        // Flag dikirim balik supaya counter di layar naik sesuai kenyataan:
        // di mode NDD, resi yang sudah pernah HO tidak menambah counter HO.
        return [
            'affected_rows' => 1,
            'type'          => $type,
            'ho_inserted'   => $ho_inserted,
            'ndd_inserted'  => $ndd_inserted,
        ];
    }

    /**
     * Total scan hari ini untuk satu petugas.
     *
     * Memakai rentang tanggal, bukan DATE(kolom) = CURDATE(). Bentuk lama
     * membungkus kolom dalam fungsi sehingga index tidak terpakai (tblscan_ndd
     * full-scan 73k baris tiap halaman dibuka).
     */
    public function get_total_scan_today($id_user, $type)
    {
        $start = date('Y-m-d 00:00:00');
        $end   = date('Y-m-d 00:00:00', strtotime('+1 day'));

        if ($type == 'HO') {
            $row = $this->db->query(
                "SELECT COUNT(1) AS total FROM tblresikeluar
                 WHERE id_pegawai = ? AND tanggal_resikeluar >= ? AND tanggal_resikeluar < ?",
                [$id_user, $start, $end]
            )->row();
        } else {
            $row = $this->db->query(
                "SELECT COUNT(1) AS total FROM tblscan_ndd
                 WHERE id_pegawai = ? AND tanggal_scan >= ? AND tanggal_scan < ?",
                [$id_user, $start, $end]
            )->row();
        }

        return $row ? (int) $row->total : 0;
    }

    public function get_report_data($data, $type)
    {
        $table = ($type == 'HO') ? 'tblscan_ho' : 'tblscan_ndd';

        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('t.tanggal_scan', 'DESC');
        }

        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('t2.noresi', $data['search']);
            $this->db->or_like('t3.nama_pegawai', $data['search']);
            $this->db->group_end();
        }

        $this->db->select('
            t.id_scan_' . strtolower($type) . ' as id,
            t2.noresi,
            t3.nama_pegawai,
            t.tanggal_scan,
            t4.nama_marketplace,
            t5.nama_kurir
        ');

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
        $this->db->join('tblpegawai t3', 't3.kode_pegawai = t.id_pegawai', 'left');
        $this->db->join('tblmarketplace t4', 't4.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t5', 't5.id_kurir = t2.id_kurir', 'left');

        if (isset($data['start_date']) && isset($data['end_date'])) {
            $this->db->where('t.tanggal_scan >=', $data['start_date']);
            $this->db->where('t.tanggal_scan <=', $data['end_date']);
        }

        if (isset($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get($table . ' t');
    }

    public function get_total_report_data($data, $type)
    {
        $table = ($type == 'HO') ? 'tblscan_ho' : 'tblscan_ndd';

        // Join hanya dipasang kalau memang dipakai untuk filter pencarian.
        // Tanpa kata kunci, count cukup baca index tanggal_scan saja -- tidak
        // perlu menyentuh tblprintresi (2,1 juta baris) sama sekali.
        if (!empty($data['search'])) {
            $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
            $this->db->join('tblpegawai t3', 't3.kode_pegawai = t.id_pegawai', 'left');
            $this->db->group_start();
            $this->db->like('t2.noresi', $data['search']);
            $this->db->or_like('t3.nama_pegawai', $data['search']);
            $this->db->group_end();
        }

        if (isset($data['start_date']) && isset($data['end_date'])) {
            $this->db->where('t.tanggal_scan >=', $data['start_date']);
            $this->db->where('t.tanggal_scan <=', $data['end_date']);
        }

        return $this->db->count_all_results($table . ' t');
    }
}
