<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data satu resi untuk menu Cari Detail Resi (receipt/detail-receipt).
 *
 * Mengumpulkan semua jejak satu resi lintas meja: alur scan (picker, packer, HO),
 * barang, masalah picker/packer, lost scan, paket cancel, komplain CS, retur, video
 * dan log scan. Hanya kolom yang benar-benar terisi di database yang dipakai --
 * kolom yang selalu kosong atau konstan sengaja tidak diambil (tblresikeluar.
 * tanggal_cetak selalu '0000-00-00', status_kurangan selalu 'Tidak', status_rts selalu 0,
 * tblprintresi.status_wms berisi nama produk).
 *
 * Satu resi (tblprintresi) hanya memuat SATU nomor pesanan (tbldetailprintresi.no_pesanan).
 *
 * Model ini berdiri sendiri dan tidak mengubah Receipt_fcd::get_detail() yang masih dipakai
 * Inbound_picker. Semua query berindeks (noresi / id_resi) kecuali tabel masalah picker/packer
 * dan lost scan yang kecil (< 2.000 baris).
 */
class Detail_resi_fcd extends CI_Model
{
    /**
     * Cari berdasarkan nomor resi atau nomor pesanan. NULL bila tidak ditemukan.
     */
    public function cari($keyword)
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return null;
        }

        pastikan_resi_live($keyword); // resi lama yang sudah diarsipkan ditarik dulu ke prod (docs/ARSIP_DATA.md)

        $noresi = $this->resolve_noresi($keyword);
        if ($noresi === null) {
            return null;
        }

        $resi = $this->db->query(
            'SELECT t.id_printresi, t.noresi, t.status_pesanan, t.tanggal_printresi, t.tanggal_bataskirim,
                    t.tanggal_pesan, t.tanggal_pengiriman, t.tanggal_selesai, t.tanggal_pencairan, t.tanggal_retur,
                    t.nomorpicklist, t.tipe_resi, m.nama_marketplace, k.nama_kurir, u.name AS admin
             FROM tblprintresi t
             LEFT JOIN tblmarketplace m ON m.id_marketplace = t.id_marketplace
             LEFT JOIN tblkurir k ON k.id_kurir = t.id_kurir
             LEFT JOIN tbluser u ON u.id_user = t.admin_pegawai
             WHERE t.noresi = ?
             ORDER BY t.created_at DESC
             LIMIT 1',
            [$noresi]
        )->row_array();

        if (empty($resi)) {
            return null;
        }

        $id = (int) $resi['id_printresi'];

        $d = ['resi' => $resi];
        $d['pesanan'] = $this->nilai(
            'SELECT no_pesanan AS v FROM tbldetailprintresi WHERE id_resi = ? ORDER BY id_detail_resi LIMIT 1', [$id]
        );
        $d['items'] = $this->db->query(
            'SELECT d.sku, d.jumlah, COALESCE(NULLIF(TRIM(s.no_rak), ""), "BELUM DITENTUKAN") AS no_rak
             FROM tbldetailprintresi d
             LEFT JOIN tblsku s ON s.id_sku = d.sku
             WHERE d.id_resi = ?
             ORDER BY d.id_detail_resi',
            [$id]
        )->result_array();

        // Alur scan: picker -> packer -> HO.
        $d['ambil'] = $this->db->query(
            'SELECT r.tanggal_resiambilbarang AS tgl, r.nama_komputer, p.nama_pegawai AS nama, sp.status_name AS kategori
             FROM tblresiambilbarang r
             LEFT JOIN tblpegawai p ON p.kode_pegawai = r.yangambil_pegawai
             LEFT JOIN tblmasterstatusperforma sp ON sp.id_statusperforma = r.status_performa_id
             WHERE r.id_resi = ?
             ORDER BY r.id_resiambilbarang DESC
             LIMIT 1',
            [$id]
        )->row_array();

        $pack = $this->db->query(
            'SELECT k.tanggal_packing AS tgl, k.keterangan, u.name AS nama, u.nama_komputer, sp.status_name AS kategori
             FROM tblpacking k
             LEFT JOIN tbluser u ON u.id_user = k.packer_pegawai
             LEFT JOIN tblmasterstatusperforma sp ON sp.id_statusperforma = k.status_performa_id
             WHERE k.id_resi = ?
             ORDER BY k.id_packing DESC
             LIMIT 1',
            [$id]
        )->row_array();
        if (!empty($pack)) {
            // keterangan = nomor komputer saat scan packer. Dua nilai lain bukan komputer dan
            // menandai packing yang tidak lewat scan packer (tanpa komputer, durasi, video):
            // 'SYNC_FROM_PICKER' (massal dari Inbound Picker) dan 'LOST SCAN HO LANGSUNG ...'.
            $ket = (string) $pack['keterangan'];
            $pack['sync']     = ($ket === 'SYNC_FROM_PICKER');
            $pack['lost']     = (strpos($ket, 'LOST SCAN') === 0);
            $pack['komputer'] = ($pack['sync'] || $pack['lost']) ? '' : ($ket !== '' ? $ket : (string) $pack['nama_komputer']);
        }
        $d['pack'] = $pack;

        $d['ho'] = $this->db->query(
            'SELECT k.tanggal_resikeluar AS tgl, u.name AS nama
             FROM tblresikeluar k
             LEFT JOIN tbluser u ON u.id_user = k.id_pegawai
             WHERE k.id_resi = ?
             ORDER BY k.id_resikeluar DESC
             LIMIT 1',
            [$id]
        )->row_array();

        // Durasi scan packer dibanding target kategorinya (hanya ada bila packing lewat scan).
        $d['kinerja'] = $this->db->query(
            'SELECT durasi_aktual, durasi_target, is_slow
             FROM tblpacker_performance_logs
             WHERE id_resi = ? AND is_deleted = 0
             ORDER BY id DESC
             LIMIT 1',
            [$id]
        )->row_array();

        // Video dan log scan.
        $d['video'] = $this->db->query(
            "SELECT id_videopacking, bagian, durasi_detik, mulai_at
             FROM tblvideopacking
             WHERE noresi = ? AND status <> 'DIBATALKAN'
             ORDER BY bagian, id_videopacking",
            [$noresi]
        )->result_array();
        $d['batalscan'] = $this->db->query(
            'SELECT b.tanggal_batal AS tgl, b.durasi_detik, b.nama_komputer, u.name AS nama
             FROM tblbatalscanpacker b
             LEFT JOIN tbluser u ON u.id_user = b.id_user
             WHERE b.noresi = ?
             ORDER BY b.tanggal_batal',
            [$noresi]
        )->result_array();
        $d['ndd'] = $this->nilai('SELECT tanggal_scan AS v FROM tblscan_ndd WHERE id_resi = ?', [$id]);

        // Masalah picker / packer dan lost scan.
        $d['masalah'] = array_merge(
            $this->db->query(
                "SELECT m.created AS tgl, 'Picker' AS jenis, tm.type_masalah AS tipe, m.sku, m.sku_salah,
                        m.qty_bermasalah AS qty, NULL AS ket, u.name AS oleh
                 FROM tblmasalahpicker m
                 LEFT JOIN tbltypemasalah tm ON tm.id_typemasalah = m.id_typemasalah
                 LEFT JOIN tbluser u ON u.id_user = m.created_by
                 WHERE m.id_printresi = ?",
                [$id]
            )->result_array(),
            $this->db->query(
                "SELECT m.created AS tgl, 'Packer' AS jenis, tm.type_masalah AS tipe, m.sku, m.sku_salah,
                        m.qty_bermasalah AS qty, m.keterangan AS ket, u.name AS oleh
                 FROM tblmasalahpacker m
                 LEFT JOIN tbltypemasalahpacker tm ON tm.id_typemasalahpacker = m.id_typemasalahpacker
                 LEFT JOIN tbluser u ON u.id_user = m.created_by
                 WHERE m.id_printresi = ?",
                [$id]
            )->result_array()
        );
        usort($d['masalah'], function ($a, $b) {
            return strcmp((string) $a['tgl'], (string) $b['tgl']);
        });
        $d['lost'] = $this->db->query(
            'SELECT created_at AS tgl, lost_type AS jenis, nama_packer AS oleh, status_resi
             FROM tbllostscanpacker
             WHERE noresi = ?
             ORDER BY created_at',
            [$noresi]
        )->result_array();

        // Paket cancel: temuan + riwayat scan yang ditolak karena CANCELED.
        $d['cancel'] = $this->db->query(
            'SELECT c.ditemukan_di, c.ditemukan_komputer, c.ditemukan_at, u.name AS oleh
             FROM tblcancel_paket c
             LEFT JOIN tbluser u ON u.id_user = c.ditemukan_oleh
             WHERE c.id_resi = ?
             LIMIT 1',
            [$id]
        )->row_array();
        $d['tolak'] = empty($d['cancel']) ? [] : $this->db->query(
            'SELECT tahap, waktu, nama_komputer FROM tblcancel_paket_tolak WHERE id_resi = ? ORDER BY waktu',
            [$id]
        )->result_array();

        // Komplain CS.
        $d['komplain'] = $this->db->query(
            'SELECT tgl_komplain AS tgl, kategori_komplain AS kategori, status_penanganan AS status,
                    nominal_total AS nominal, nama_packer, nama_picker, hasil_investigasi
             FROM tblcs_complain
             WHERE no_resi = ? AND is_deleted = 0
             ORDER BY tgl_komplain',
            [$noresi]
        )->result_array();

        // Retur.
        $d['retur_diterima'] = $this->nilai(
            'SELECT tanggal_resiretur AS v FROM tblresiretur WHERE noresi = ? ORDER BY id_resiretur DESC LIMIT 1', [$noresi]
        );
        $d['retur_buka'] = $this->db->query(
            'SELECT sku, qty, status_detail_buka AS status, tanggal_buka_retur AS tgl
             FROM tblbukaretur
             WHERE resi_buka = ?
             ORDER BY tanggal_buka_retur, id_bukaretur',
            [$noresi]
        )->result_array();
        $d['retur_acc'] = $this->nilai(
            'SELECT verified_at AS v FROM tblreturverifikasi WHERE no_resi = ? AND verified = 1 LIMIT 1', [$noresi]
        );

        return $d;
    }

    /** Nomor resi dari input: cocokkan sebagai nomor resi dulu, lalu sebagai nomor pesanan. */
    private function resolve_noresi($keyword)
    {
        $v = $this->nilai('SELECT noresi AS v FROM tblprintresi WHERE noresi = ? ORDER BY created_at DESC LIMIT 1', [$keyword]);
        if ($v !== null) {
            return $v;
        }

        return $this->nilai(
            'SELECT t.noresi AS v
             FROM tbldetailprintresi d
             INNER JOIN tblprintresi t ON t.id_printresi = d.id_resi
             WHERE d.no_pesanan = ?
             ORDER BY t.created_at DESC
             LIMIT 1',
            [$keyword]
        );
    }

    /** Satu nilai (kolom alias "v") dari baris pertama, NULL bila tidak ada. */
    private function nilai($sql, $binds)
    {
        $row = $this->db->query($sql, $binds)->row_array();

        return isset($row['v']) ? $row['v'] : null;
    }
}
