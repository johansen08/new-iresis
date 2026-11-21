<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Daftar Retur Penjualan (TIM RETUR) — retur EKSPEDISI (kurir) yang masih ditunggu.
 *
 * Retur ekspedisi = paket sudah diambil kurir dari toko, tapi gudang kurir
 * kewalahan lalu paket dikembalikan ke toko. Di Excel "daftar retur penjualan"
 * Jubelio ditandai status_retur = BARANG RETUR. Status SELESAI / DITOLAK adalah
 * pengembalian complain dari customer dan SENGAJA tidak masuk daftar ini.
 *
 * Satu baris = satu resi (yang semua barisnya di tblreturjubelio BARANG RETUR;
 * resi yang di sebagian barisnya sudah SELESAI/DITOLAK dianggap sudah diproses).
 *
 * TANGGAL RETUR dipilih berlapis (yang pertama tersedia):
 *   1. tblprintresi.tanggal_retur — dari Excel pesanan (TGL_RETUR + WAKTU_RETUR),
 *      dicari di prod lalu arsip. Ini tanggal yang akurat.
 *   2. tblprintresi.tanggal_pengiriman   — perkiraan (resi Shopee lama, Jubelio
 *                                           hanya menyimpan 3 bulan terakhir).
 *   3. tblreturjubelio.tanggal_retur      — sebenarnya tanggal ORDER; perkiraan kasar.
 * Kolom `sumber_tanggal` memberi tahu lapisan mana yang dipakai.
 *
 * Umur = selisih hari dari tanggal retur sampai hari ini.
 * >= 25 hari oranye (peringatan), >= 30 hari merah (wajib klaim ke ekspedisi).
 *
 * PENTING — charset: tblreturjubelio.no_resi utf8 sedangkan tblprintresi.noresi,
 * tblresiretur.noresi, tblbukaretur.resi_buka latin1. no_resi di-CONVERT ke latin1
 * lebih dulu supaya index sisi latin1 tetap dipakai (lihat Retur_fcd::_rekap_base).
 */
class Daftar_retur_fcd extends CI_Model
{
    const UMUR_ORANYE = 25;
    const UMUR_MERAH  = 30;

    /** Lama cache daftar (detik). Perubahan scan retur terlihat paling lambat segini. */
    const CACHE_DETIK = 120;

    /** Lama cache baris tambahan tab Semua (resi SELESAI/DITOLAK, ~72 ribu baris; queri ~9 detik). */
    const CACHE_SEMUA_DETIK = 600;

    /** Nilai tblreturjubelio.status_retur yang bisa dipilih di filter tab Semua. */
    const STATUS_RETUR = array('SELESAI', 'DITOLAK', 'BARANG RETUR');

    /** Bit status_retur (kolom stm) supaya penyaringan puluhan ribu baris tanpa explode/array_intersect per baris. */
    const BIT_STATUS = array('SELESAI' => 1, 'DITOLAK' => 2, 'BARANG RETUR' => 4);

    /**
     * Cache basi boleh disajikan (user tidak menunggu hitung ulang) selama tugas terjadwal "segarkan_daftar_retur" hidup:
     * denyutnya (berkas daftar_retur_warm.txt) lebih baru dari DENYUT_DETIK. Tanpa denyut perilaku lama berlaku: cache
     * lewat umur = hitung ulang saat itu juga. Cache basi paling tua yang masih disajikan = STALE_MAKS_DETIK.
     */
    const DENYUT_DETIK    = 600;
    const STALE_MAKS_DETIK = 1800;

    const POSISI_BELUM_DITERIMA = 'BELUM_DITERIMA';
    const POSISI_BELUM_DIBUKA   = 'BELUM_DIBUKA';
    const POSISI_SUDAH_DIBUKA   = 'SUDAH_DIBUKA';   // sudah scan buka, BELUM diverifikasi accounting
    const POSISI_SUDAH_SERAH    = 'SUDAH_SERAH';    // sudah dibuka DAN sudah dicetak serah terimanya, BELUM diverifikasi
    const POSISI_SUDAH_VERIF    = 'SUDAH_VERIF';    // sudah scan buka DAN sudah diverifikasi accounting

    /**
     * Tahap progres retur (kolom Progress): 0 belum terima -> 1 sudah terima (scan Terima Retur) -> 2 sudah buka
     * (scan Buka Retur) -> 3 sudah serah terima (kertas serah terima dicetak) -> 4 sudah verifikasi (accounting).
     * Tiap tahap >= 1 tersimpan lengkap: petugas (id_user, ditampilkan NO ABSEN - NAMA - ROLE) + tanggal & jam.
     */
    const TAHAP_NAMA = array(
        0 => 'Belum Terima', 1 => 'Sudah Terima', 2 => 'Sudah Buka', 3 => 'Sudah Serah Terima', 4 => 'Sudah Verifikasi',
    );

    /**
     * Alur Serah Terima (cetak tercatat) baru ada sejak tanggal ini. Resi yang dibuka SEBELUM tanggal ini tidak punya
     * jejak serah terima dan TIDAK dibuatkan data palsu (tanpa backfill): tahap dikosongkan dan diberi keterangan "data lama".
     */
    const SERAH_MULAI = '2026-11-20 00:00:00';

    /** Jenis retur fisik di tblresiretur / tblbukaretur: B = retur ekspedisi / gagal kirim (is_komplain = 0), K = retur komplain / pengembalian dari customer (is_komplain = 1). */
    const JENIS_BIASA    = 'B';
    const JENIS_KOMPLAIN = 'K';

    public function __construct()
    {
        parent::__construct();
    }

    /** Nama DB arsip dari config (satu server dengan prod). */
    private function _db_arsip()
    {
        $this->config->load('arsip', TRUE, TRUE);
        $nama = $this->config->item('db_arsip', 'arsip');
        return preg_match('/^[A-Za-z0-9_]+$/', (string) $nama) ? $nama : 'iresis_arsip';
    }

    /**
     * SQL dasar: satu baris per resi, lengkap dengan tanggal retur terpilih,
     * sumber tanggal, umur, dan posisi paket. Sudah tanpa filter tanggal/cari.
     */
    private function _sql_dasar()
    {
        $arsip = '`' . $this->_db_arsip() . '`';
        $sah   = "'2000-01-01'";
        // is_komplain NULL diperlakukan sebagai retur biasa (sama seperti Retur::_cari_baris_retur).
        $biasa_r = '(r.is_komplain IS NULL OR r.is_komplain = 0)';
        $biasa_b = '(b.is_komplain IS NULL OR b.is_komplain = 0)';

        return "
            SELECT f.*,
                   DATEDIFF(NOW(), f.tanggal_retur) AS umur_hari
            FROM (
                SELECT g.no_resi, g.no_pesanan, g.kurir, g.marketplace, g.nama_toko, g.qty, g.nilai,
                       COALESCE(g.d_pesan_p, g.d_pesan_a, g.tgl_order) AS tanggal_pesan,
                       COALESCE(g.d_retur_p, g.d_retur_a, g.d_kirim_p, g.d_kirim_a, g.tgl_order) AS tanggal_retur,
                       CASE
                         WHEN g.d_retur_p IS NOT NULL OR g.d_retur_a IS NOT NULL THEN 'RETUR'
                         WHEN g.d_kirim_p IS NOT NULL OR g.d_kirim_a IS NOT NULL THEN 'KIRIM'
                         ELSE 'ORDER'
                       END AS sumber_tanggal,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblresiretur r WHERE r.noresi = g.resi_l AND {$biasa_r}),
                         (SELECT COUNT(*) FROM {$arsip}.tblresiretur r WHERE r.noresi = g.resi_l AND {$biasa_r})
                       ) AS ir_b,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblresiretur r WHERE r.noresi = g.resi_l AND r.is_komplain = 1),
                         (SELECT COUNT(*) FROM {$arsip}.tblresiretur r WHERE r.noresi = g.resi_l AND r.is_komplain = 1)
                       ) AS ir_k,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b}),
                         (SELECT COUNT(*) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b})
                       ) AS bk_b,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1),
                         (SELECT COUNT(*) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1)
                       ) AS bk_k,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b} AND b.status_acc = 1),
                         (SELECT COUNT(*) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b} AND b.status_acc = 1)
                       ) AS av_b,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1 AND b.status_acc = 1),
                         (SELECT COUNT(*) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1 AND b.status_acc = 1)
                       ) AS av_k,
                       (EXISTS (SELECT 1 FROM tblreturverifikasi v WHERE v.no_resi = g.resi_l AND v.verified = 1)
                        OR EXISTS (SELECT 1 FROM {$arsip}.tblreturverifikasi v WHERE v.no_resi = g.resi_l AND v.verified = 1)) AS vf,
                       GREATEST(
                         COALESCE((SELECT MAX(COALESCE(b.tanggal_buka_retur, b.created_at)) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b}), '1000-01-01'),
                         COALESCE((SELECT MAX(COALESCE(b.tanggal_buka_retur, b.created_at)) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND {$biasa_b}), '1000-01-01')
                       ) AS tb_b,
                       GREATEST(
                         COALESCE((SELECT MAX(COALESCE(b.tanggal_buka_retur, b.created_at)) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1), '1000-01-01'),
                         COALESCE((SELECT MAX(COALESCE(b.tanggal_buka_retur, b.created_at)) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l AND b.is_komplain = 1), '1000-01-01')
                       ) AS tb_k
                FROM (
                    SELECT j.no_resi, j.resi_l, j.no_pesanan, j.kurir, j.marketplace, j.nama_toko,
                           j.qty, j.nilai, j.tgl_order,
                           CASE WHEN p.tanggal_pesan >= {$sah} THEN p.tanggal_pesan END           AS d_pesan_p,
                           CASE WHEN a.tanggal_pesan >= {$sah} THEN a.tanggal_pesan END           AS d_pesan_a,
                           CASE WHEN p.tanggal_retur >= {$sah} THEN p.tanggal_retur END           AS d_retur_p,
                           CASE WHEN a.tanggal_retur >= {$sah} THEN a.tanggal_retur END           AS d_retur_a,
                           CASE WHEN p.tanggal_pengiriman >= {$sah} THEN p.tanggal_pengiriman END AS d_kirim_p,
                           CASE WHEN a.tanggal_pengiriman >= {$sah} THEN a.tanggal_pengiriman END AS d_kirim_a
                    FROM (
                        SELECT no_resi,
                               CONVERT(no_resi USING latin1) AS resi_l,
                               MAX(no_pesanan)  AS no_pesanan,
                               MAX(kurir)       AS kurir,
                               MAX(marketplace) AS marketplace,
                               MAX(nama_toko)   AS nama_toko,
                               SUM(qty)         AS qty,
                               SUM(amount)      AS nilai,
                               MIN(tanggal_retur) AS tgl_order
                        FROM tblreturjubelio
                        WHERE no_resi IS NOT NULL AND no_resi <> ''
                        GROUP BY no_resi
                        HAVING SUM(status_retur IS NULL OR status_retur <> 'BARANG RETUR') = 0
                    ) j
                    LEFT JOIN tblprintresi p ON p.noresi = j.resi_l
                    LEFT JOIN {$arsip}.tblprintresi a ON a.noresi = j.resi_l
                ) g
            ) f
        ";
    }

    /**
     * Seluruh baris daftar (sudah lengkap tanggal/umur/posisi). Query dasarnya berat
     * (~2 detik: grup tblreturjubelio + join prod/arsip + cek scan), padahal hasilnya
     * cuma ~8 ribu baris. Karena itu hasilnya disimpan di file cache singkat dan semua
     * penyaringan / pengurutan / paging dikerjakan di PHP -- klik tab, sort, atau ganti
     * halaman jadi hampir seketika. Cache dibuang otomatis setelah CACHE_DETIK; parameter
     * $segarkan memaksa hitung ulang (tombol atur ulang di halaman).
     *
     * @return array [baris(array of object), waktu_hitung(timestamp)]
     */
    public function semua_baris($segarkan = false)
    {
        $file = $this->_berkas_cache('daftar_retur5');

        if (!$segarkan && $this->_cache_layak($file, self::CACHE_DETIK)) {
            $isi = json_decode((string) @file_get_contents($file), true);
            if (is_array($isi) && isset($isi['baris'])) {
                return array(array_map(function ($r) { return (object) $r; }, $isi['baris']), (int) $isi['waktu']);
            }
        }

        $rows  = $this->db->query($this->_sql_dasar())->result();
        $waktu = time();
        $this->_tulis_cache($file, json_encode(array('waktu' => $waktu, 'baris' => $rows)));

        return array($rows, $waktu);
    }

    /**
     * SQL baris tambahan untuk tab Semua: resi yang punya minimal satu baris SELESAI / DITOLAK (atau status kosong)
     * di tblreturjubelio -- kebalikan dari _sql_dasar() yang hanya mengambil resi BARANG RETUR murni. Kolomnya sama
     * dengan _sql_dasar() ditambah `stat` (daftar status_retur resi itu, dipisah koma).
     *
     * Beda cara hitung: jumlah scan terima/buka/verifikasi diambil dari tabel agregat (GROUP BY satu kali) lalu di-JOIN,
     * bukan subquery per resi. Untuk ~72 ribu resi cara per resi memakan ~17 detik, cara ini ~9 detik. Arti kolomnya
     * identik (prod dan arsip dibandingkan dengan GREATEST, bukan dijumlah, karena arsip adalah superset).
     */
    private function _sql_tambahan()
    {
        $arsip = '`' . $this->_db_arsip() . '`';
        $prod  = '`' . $this->db->database . '`';
        $sah   = "'2000-01-01'";
        $tb    = 'COALESCE(tanggal_buka_retur, created_at)';
        $agr_resi = function ($sk) {
            return "SELECT noresi, SUM(is_komplain IS NULL OR is_komplain = 0) ir_b, SUM(is_komplain = 1) ir_k
                    FROM {$sk}.tblresiretur GROUP BY noresi";
        };
        $agr_buka = function ($sk) use ($tb) {
            return "SELECT resi_buka, SUM(is_komplain IS NULL OR is_komplain = 0) bk_b, SUM(is_komplain = 1) bk_k,
                           SUM((is_komplain IS NULL OR is_komplain = 0) AND status_acc = 1) av_b,
                           SUM(is_komplain = 1 AND status_acc = 1) av_k,
                           MAX(IF(is_komplain IS NULL OR is_komplain = 0, {$tb}, NULL)) tb_b,
                           MAX(IF(is_komplain = 1, {$tb}, NULL)) tb_k,
                           MAX(IF((is_komplain IS NULL OR is_komplain = 0) AND status_acc = 1, updated_at, NULL)) ac_b,
                           MAX(IF(is_komplain = 1 AND status_acc = 1, updated_at, NULL)) ac_k
                    FROM {$sk}.tblbukaretur GROUP BY resi_buka";
        };
        $r_prod = $agr_resi($prod);   $r_arsip = $agr_resi($arsip);
        $b_prod = $agr_buka($prod);   $b_arsip = $agr_buka($arsip);

        return "
            SELECT f.*, DATEDIFF(NOW(), f.tanggal_retur) AS umur_hari
            FROM (
                SELECT g.no_resi, g.no_pesanan, g.kurir, g.marketplace, g.nama_toko, g.qty, g.nilai, g.stat,
                       COALESCE(g.d_pesan_p, g.d_pesan_a, g.tgl_order) AS tanggal_pesan,
                       COALESCE(g.d_retur_p, g.d_retur_a, g.d_kirim_p, g.d_kirim_a, g.tgl_order) AS tanggal_retur,
                       CASE
                         WHEN g.d_retur_p IS NOT NULL OR g.d_retur_a IS NOT NULL THEN 'RETUR'
                         WHEN g.d_kirim_p IS NOT NULL OR g.d_kirim_a IS NOT NULL THEN 'KIRIM'
                         ELSE 'ORDER'
                       END AS sumber_tanggal,
                       GREATEST(COALESCE(rp.ir_b, 0), COALESCE(ra.ir_b, 0)) AS ir_b,
                       GREATEST(COALESCE(rp.ir_k, 0), COALESCE(ra.ir_k, 0)) AS ir_k,
                       GREATEST(COALESCE(bp.bk_b, 0), COALESCE(ba.bk_b, 0)) AS bk_b,
                       GREATEST(COALESCE(bp.bk_k, 0), COALESCE(ba.bk_k, 0)) AS bk_k,
                       GREATEST(COALESCE(bp.av_b, 0), COALESCE(ba.av_b, 0)) AS av_b,
                       GREATEST(COALESCE(bp.av_k, 0), COALESCE(ba.av_k, 0)) AS av_k,
                       (vp.r IS NOT NULL OR va.r IS NOT NULL) AS vf,
                       NULLIF(GREATEST(COALESCE(vp.t, '1000-01-01'), COALESCE(va.t, '1000-01-01')), '1000-01-01') AS vf_at,
                       NULLIF(GREATEST(COALESCE(bp.ac_b, '1000-01-01'), COALESCE(ba.ac_b, '1000-01-01')), '1000-01-01') AS ac_b,
                       NULLIF(GREATEST(COALESCE(bp.ac_k, '1000-01-01'), COALESCE(ba.ac_k, '1000-01-01')), '1000-01-01') AS ac_k,
                       NULLIF(GREATEST(COALESCE(bp.tb_b, '1000-01-01'), COALESCE(ba.tb_b, '1000-01-01')), '1000-01-01') AS tb_b,
                       NULLIF(GREATEST(COALESCE(bp.tb_k, '1000-01-01'), COALESCE(ba.tb_k, '1000-01-01')), '1000-01-01') AS tb_k
                FROM (
                    SELECT j.no_resi, j.resi_l, j.no_pesanan, j.kurir, j.marketplace, j.nama_toko,
                           j.qty, j.nilai, j.tgl_order, j.stat,
                           CASE WHEN p.tanggal_pesan >= {$sah} THEN p.tanggal_pesan END           AS d_pesan_p,
                           CASE WHEN a.tanggal_pesan >= {$sah} THEN a.tanggal_pesan END           AS d_pesan_a,
                           CASE WHEN p.tanggal_retur >= {$sah} THEN p.tanggal_retur END           AS d_retur_p,
                           CASE WHEN a.tanggal_retur >= {$sah} THEN a.tanggal_retur END           AS d_retur_a,
                           CASE WHEN p.tanggal_pengiriman >= {$sah} THEN p.tanggal_pengiriman END AS d_kirim_p,
                           CASE WHEN a.tanggal_pengiriman >= {$sah} THEN a.tanggal_pengiriman END AS d_kirim_a
                    FROM (
                        SELECT no_resi,
                               CONVERT(no_resi USING latin1) AS resi_l,
                               MAX(no_pesanan)  AS no_pesanan,
                               MAX(kurir)       AS kurir,
                               MAX(marketplace) AS marketplace,
                               MAX(nama_toko)   AS nama_toko,
                               SUM(qty)         AS qty,
                               SUM(amount)      AS nilai,
                               MIN(tanggal_retur) AS tgl_order,
                               CONCAT_WS(',',
                                   IF(SUM(status_retur = 'SELESAI') > 0, 'SELESAI', NULL),
                                   IF(SUM(status_retur = 'DITOLAK') > 0, 'DITOLAK', NULL),
                                   IF(SUM(status_retur = 'BARANG RETUR') > 0, 'BARANG RETUR', NULL)) AS stat
                        FROM tblreturjubelio
                        WHERE no_resi IS NOT NULL AND no_resi <> ''
                        GROUP BY no_resi
                        HAVING SUM(status_retur IS NULL OR status_retur <> 'BARANG RETUR') > 0
                    ) j
                    LEFT JOIN tblprintresi p ON p.noresi = j.resi_l
                    LEFT JOIN {$arsip}.tblprintresi a ON a.noresi = j.resi_l
                ) g
                LEFT JOIN ({$r_prod}) rp ON rp.noresi = g.resi_l
                LEFT JOIN ({$r_arsip}) ra ON ra.noresi = g.resi_l
                LEFT JOIN ({$b_prod}) bp ON bp.resi_buka = g.resi_l
                LEFT JOIN ({$b_arsip}) ba ON ba.resi_buka = g.resi_l
                LEFT JOIN (SELECT no_resi AS r, MAX(verified_at) AS t FROM {$prod}.tblreturverifikasi WHERE verified = 1 GROUP BY no_resi) vp ON vp.r = g.resi_l
                LEFT JOIN (SELECT no_resi AS r, MAX(verified_at) AS t FROM {$arsip}.tblreturverifikasi WHERE verified = 1 GROUP BY no_resi) va ON va.r = g.resi_l
            ) f
        ";
    }

    /** Berkas cache di application/cache; nama memuat nama DB supaya dev / uji / prod tidak saling timpa. */
    private function _berkas_cache($nama, $ekstensi = 'json')
    {
        return APPPATH . 'cache/' . $nama . '_' . preg_replace('/[^A-Za-z0-9_]/', '', $this->db->database) . '.' . $ekstensi;
    }

    /** Berkas denyut yang disentuh tugas terjadwal segarkan_daftar_retur (lihat DENYUT_DETIK). */
    private function _berkas_denyut()
    {
        return APPPATH . 'cache/daftar_retur_warm.txt';
    }

    /** Cache $berkas masih layak disajikan? Segar (umur < $ttl), atau basi tapi tugas terjadwal hidup (user tidak perlu menunggu). */
    private function _cache_layak($berkas, $ttl)
    {
        if (!is_file($berkas)) return false;
        $umur = time() - filemtime($berkas);
        if ($umur < $ttl) return true;
        $denyut = $this->_berkas_denyut();
        return $umur < self::STALE_MAKS_DETIK && is_file($denyut) && (time() - filemtime($denyut)) < self::DENYUT_DETIK;
    }

    /** Tulis cache lewat berkas sementara lalu rename, supaya pembaca tidak pernah menemukan berkas separuh jadi. */
    private function _tulis_cache($berkas, $isi)
    {
        $tmp = $berkas . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, $isi) === false) {
            @unlink($tmp);
            @file_put_contents($berkas, $isi, LOCK_EX);
            return;
        }
        if (!@rename($tmp, $berkas)) {   // Windows menolak bila berkas sedang dibaca proses lain
            @file_put_contents($berkas, $isi, LOCK_EX);
            @unlink($tmp);
        }
    }

    /**
     * Baris tambahan tab Semua (resi dengan status SELESAI / DITOLAK). Cache terpisah dari semua_baris() dengan umur
     * lebih panjang karena queri-nya berat dan hanya dibaca saat tab Semua dibuka dengan SELESAI/DITOLAK terpilih.
     * Posisi paket tetap dihitung di get_list() dari kolom ir_* / bk_* yang ikut tersimpan di cache ini.
     * Format serialize() (dibaca ~2x lebih cepat dari JSON untuk ~72 ribu baris) dan nilai bawaan NULL supaya berkas ramping.
     *
     * @return array baris (array of object)
     */
    public function baris_tambahan($segarkan = false)
    {
        $file = $this->_berkas_cache('daftar_retur_semua2', 'dat');

        if (!$segarkan && $this->_cache_layak($file, self::CACHE_SEMUA_DETIK)) {
            $isi = @unserialize((string) @file_get_contents($file), array('allowed_classes' => false));
            if (is_array($isi)) {
                foreach ($isi as $i => $r) $isi[$i] = (object) $r;
                return $isi;
            }
        }

        $rows = array();
        foreach ($this->db->query($this->_sql_tambahan())->result_array() as $r) {
            $stm = 0;
            foreach (explode(',', (string) $r['stat']) as $st) {
                if (isset(self::BIT_STATUS[$st])) $stm |= self::BIT_STATUS[$st];
            }
            $r['stm'] = $stm;
            $rows[] = $r;
        }
        $this->_tulis_cache($file, serialize($rows));
        foreach ($rows as $i => $r) $rows[$i] = (object) $r;
        return $rows;
    }

    /**
     * Dipanggil tugas terjadwal (Cron::segarkan_daftar_retur): bangun ulang cache supaya user hampir tidak pernah
     * menunggu hitung ulang. Daftar dasar selalu dibangun; baris tambahan hanya bila sudah lebih tua dari $umur_tambahan
     * detik (queri-nya ~3 detik + tulis ~30 MB). Menyentuh denyut sehingga cache basi boleh disajikan (lihat _cache_layak).
     * @return array ringkasan untuk log
     */
    public function segarkan_cache($umur_tambahan = 300)
    {
        $t = microtime(true);
        list($dasar) = $this->semua_baris(true);
        $info = array('dasar' => count($dasar));
        $file = $this->_berkas_cache('daftar_retur_semua2', 'dat');
        if (!is_file($file) || (time() - filemtime($file)) >= $umur_tambahan) {
            $info['tambahan'] = count($this->baris_tambahan(true));
        } else {
            $info['tambahan'] = 'dilewati (masih baru)';
        }
        @file_put_contents($this->_berkas_denyut(), date('Y-m-d H:i:s'));
        $info['detik'] = round(microtime(true) - $t, 1);
        return $info;
    }

    /**
     * Posisi paket menurut jenis retur yang dipilih ($jenis = daftar 'B' / 'K').
     * Tiap jenis: ada scan buka -> sudah dibuka; ada scan terima saja -> diterima belum dibuka;
     * selain itu -> belum diterima. Bila dua jenis dipilih, yang masih menunggu dibuka menang
     * (paling perlu ditindak), lalu sudah dibuka, lalu belum diterima.
     * Return NULL = baris tidak termasuk (pilih Komplain saja tapi resi tak pernah komplain).
     */
    public function hitung_posisi($r, array $jenis)
    {
        $pakai_b = in_array(self::JENIS_BIASA, $jenis, true);
        $pakai_k = in_array(self::JENIS_KOMPLAIN, $jenis, true);
        if (!$pakai_b && !$pakai_k) return null;

        // Komplain saja: hanya resi yang memang punya scan komplain.
        if (!$pakai_b && ((int) $r->ir_k + (int) $r->bk_k) === 0) return null;

        $n_buka = ($pakai_b ? (int) $r->bk_b : 0) + ($pakai_k ? (int) $r->bk_k : 0);
        $n_terima = ($pakai_b ? (int) $r->ir_b : 0) + ($pakai_k ? (int) $r->ir_k : 0);

        // Dua jenis: satu jenis menunggu dibuka sudah cukup untuk masuk tab "Terima".
        if ($pakai_b && $pakai_k) {
            $tunggu_b = ((int) $r->ir_b > 0 && (int) $r->bk_b === 0);
            $tunggu_k = ((int) $r->ir_k > 0 && (int) $r->bk_k === 0);
            if ($tunggu_b || $tunggu_k) return self::POSISI_BELUM_DIBUKA;
        }
        if ($n_buka > 0) {
            // Sudah diverifikasi accounting -> tab Sudah verif. Dua penanda yang dipakai:
            //  1. menu Verifikasi Retur (tblreturverifikasi.verified = 1, per resi) -- penanda utama accounting;
            //  2. Accounting > Returan Buka (tblbukaretur.status_acc = 1) bila SEMUA baris buka sudah di-ACC.
            // Keduanya hanya bisa naik 0 -> 1, jadi aman terhadap salinan arsip yang basi.
            if ((int) $r->vf === 1) return self::POSISI_SUDAH_VERIF;
            $n_acc = ($pakai_b ? (int) $r->av_b : 0) + ($pakai_k ? (int) $r->av_k : 0);
            return ($n_acc >= $n_buka) ? self::POSISI_SUDAH_VERIF : self::POSISI_SUDAH_DIBUKA;
        }
        if ($n_terima > 0) return self::POSISI_BELUM_DIBUKA;
        return self::POSISI_BELUM_DITERIMA;
    }

    /**
     * Tanggal verifikasi baris tambahan tab Semua (sama dengan _peta_tanggal 'verif' untuk baris ekspedisi):
     * verifikasi accounting (tblreturverifikasi.verified_at); bila tidak ada, ACC Returan Buka menurut jenis yang dipilih.
     */
    private function _tanggal_verif_baris($r, array $jenis)
    {
        $v = (string) $r->vf_at;
        if ($v >= '2000-01-01') return $v;
        $t = '';
        if (in_array(self::JENIS_BIASA, $jenis, true)) $t = max($t, (string) $r->ac_b);
        if (in_array(self::JENIS_KOMPLAIN, $jenis, true)) $t = max($t, (string) $r->ac_k);
        return ($t >= '2000-01-01') ? $t : null;
    }

    /** Tanggal buka retur terakhir menurut jenis yang dipilih; NULL bila belum pernah dibuka. */
    private function _tanggal_buka($r, array $jenis)
    {
        $t = '';
        if (in_array(self::JENIS_BIASA, $jenis, true) && (int) $r->bk_b > 0) $t = max($t, (string) $r->tb_b);
        if (in_array(self::JENIS_KOMPLAIN, $jenis, true) && (int) $r->bk_k > 0) $t = max($t, (string) $r->tb_k);
        return ($t >= '2000-01-01') ? $t : null;
    }

    /**
     * @param array $p start, end (Y-m-d H:i:s), status (posisi/kosong), search, order (kolom),
     *                 dir, offset, limit, segarkan, jenis (array 'B'/'K'; kosong/tak diisi = keduanya)
     *                 status_retur (array 'SELESAI'/'DITOLAK'/'BARANG RETUR'; HANYA berlaku di tab Semua, status kosong):
     *                 tampilkan semua retur Jubelio dengan status itu. Tidak diisi = perilaku lama (hanya BARANG RETUR murni).
     * @return array [rows, total_difilter, total_semua_pada_rentang, jumlah_per_status, waktu_hitung, jumlah_tab_semua (NULL bila bukan tab Semua)]
     */
    public function get_list(array $p)
    {
        list($semua, $waktu) = $this->semua_baris(!empty($p['segarkan']));

        // Tab Semua: selain retur ekspedisi (BARANG RETUR murni) ikut resi yang berstatus SELESAI / DITOLAK.
        $status_retur = (isset($p['status_retur']) && is_array($p['status_retur']))
            ? array_values(array_intersect($p['status_retur'], self::STATUS_RETUR)) : null;
        $mode_semua = ($status_retur !== null && empty($p['status']));
        $mask = 0;
        foreach ((array) $status_retur as $st) $mask |= self::BIT_STATUS[$st];
        foreach ($semua as $r) {
            $r->stat = 'BARANG RETUR';
            $r->stm = self::BIT_STATUS['BARANG RETUR'];
            $r->ekstra = 0;
        }
        if ($mode_semua && ($mask & (self::BIT_STATUS['SELESAI'] | self::BIT_STATUS['DITOLAK']))) {
            foreach ($this->baris_tambahan(!empty($p['segarkan'])) as $r) {
                if (!($r->stm & $mask)) continue;
                $r->ekstra = 1;
                $semua[] = $r;
            }
        }

        $jenis = isset($p['jenis']) && is_array($p['jenis'])
            ? array_values(array_intersect($p['jenis'], array(self::JENIS_BIASA, self::JENIS_KOMPLAIN)))
            : array(self::JENIS_BIASA, self::JENIS_KOMPLAIN);

        // Rentang: tiap baris disaring dengan TANGGAL TAHAP-nya sendiri (tanggal_tahap): Belum diterima = tanggal pesan,
        // Sudah Terima = tanggal terima, Sudah Buka = tanggal buka, Sudah Serah Terima = tanggal serah terima,
        // Sudah Verifikasi = tanggal verifikasi (cadangan tanggal pesan bila tanggal tahap tidak tercatat).
        $serah = $this->peta_serah();
        $rentang = array();
        $tab = array(self::POSISI_BELUM_DITERIMA => 0, self::POSISI_BELUM_DIBUKA => 0, self::POSISI_SUDAH_DIBUKA => 0,
            self::POSISI_SUDAH_SERAH => 0, self::POSISI_SUDAH_VERIF => 0);
        $kandidat = array();
        $perlu_terima = array();
        $perlu_verif = array();
        foreach ($semua as $r) {
            $posisi = $this->hitung_posisi($r, $jenis);
            if ($posisi === null) continue;
            // Final di Jubelio: SEMUA barisnya SELESAI / DITOLAK (tanpa BARANG RETUR) = accounting sudah menyelesaikan / menolak
            // retur itu di Jubelio dan tidak bisa diubah lagi, jadi setara Sudah Verifikasi walau iresis tidak punya scan atau
            // catatan verifikasi (data lama). Jejak scan yang tidak ada TIDAK dibuatkan data palsu; dari_jubelio = 1 menandai
            // verifikasi yang hanya disimpulkan dari status Jubelio (kartu Progress & Excel menjelaskannya).
            $r->final_jubelio = 0;
            $r->dari_jubelio = 0;
            if ($r->ekstra && (int) $r->stm !== 0 && !((int) $r->stm & self::BIT_STATUS['BARANG RETUR'])) {
                $r->final_jubelio = 1;
                if ($posisi !== self::POSISI_SUDAH_VERIF) {
                    $r->dari_jubelio = 1;
                    $posisi = self::POSISI_SUDAH_VERIF;
                }
            }
            $r->tanggal_buka = $this->_tanggal_buka($r, $jenis);
            $kunci_serah = strtoupper((string) $r->no_resi);
            // Serah terima = cetak PERTAMA; cetak ulang tercatat terpisah (jumlah cetak + waktu cetak terakhir).
            $r->tgl_serah = isset($serah[$kunci_serah]) ? $serah[$kunci_serah]['awal'] : null;
            $r->serah_akhir = isset($serah[$kunci_serah]) ? $serah[$kunci_serah]['akhir'] : null;
            $r->serah_n = isset($serah[$kunci_serah]) ? $serah[$kunci_serah]['n'] : 0;
            // Sudah dibuka + pernah dicetak serah terima -> pindah ke tab Sudah Serah Terima.
            if ($posisi === self::POSISI_SUDAH_DIBUKA && $r->tgl_serah !== null) $posisi = self::POSISI_SUDAH_SERAH;
            $r->posisi = $posisi;
            $r->tahap = $this->hitung_tahap($posisi, $r->tgl_serah !== null);
            // Data lama: sudah dibuka sebelum alur serah terima ada dan tidak punya catatan serah terima.
            $r->data_lama = ($r->tgl_serah === null && $r->tanggal_buka !== null && $r->tanggal_buka < self::SERAH_MULAI
                && ($posisi === self::POSISI_SUDAH_DIBUKA || $posisi === self::POSISI_SUDAH_VERIF));
            if ($posisi === self::POSISI_BELUM_DIBUKA) $perlu_terima[] = (string) $r->no_resi;
            // Baris tambahan tab Semua membawa tanggal verifikasi dari queri-nya sendiri (vf_at / ac_*): tidak perlu dibaca lagi.
            if ($posisi === self::POSISI_SUDAH_VERIF && !$r->ekstra) $perlu_verif[] = (string) $r->no_resi;
            $kandidat[] = $r;
        }
        // Tanggal terima & verifikasi tidak ada di query dasar (berat, di-cache): dibaca hanya untuk resi yang butuh.
        $tgl_terima = $this->_peta_tanggal($perlu_terima, $jenis, 'terima');
        $tgl_verif = $this->_peta_tanggal($perlu_verif, $jenis, 'verif');
        foreach ($kandidat as $r) {
            $k = strtoupper((string) $r->no_resi);
            switch ($r->posisi) {
                case self::POSISI_BELUM_DIBUKA: $t = isset($tgl_terima[$k]) ? $tgl_terima[$k] : null; break;
                case self::POSISI_SUDAH_DIBUKA: $t = $r->tanggal_buka; break;
                case self::POSISI_SUDAH_SERAH:  $t = $r->tgl_serah; break;
                case self::POSISI_SUDAH_VERIF:
                    $t = $r->ekstra ? $this->_tanggal_verif_baris($r, $jenis) : (isset($tgl_verif[$k]) ? $tgl_verif[$k] : null);
                    if (!$t) $t = $r->tanggal_buka;
                    break;
                default: $t = null;
            }
            $r->tanggal_tahap = $t ?: null;
            $tgl_filter = $t ?: $r->tanggal_pesan;
            if ($tgl_filter >= $p['start'] && $tgl_filter <= $p['end']) {
                $rentang[] = $r;
                // Hitungan tab hanya dari retur ekspedisi, supaya angka tab lain tidak berubah oleh baris tambahan tab Semua.
                if (!$r->ekstra) $tab[$r->posisi]++;
            }
        }
        $total_rentang = count($rentang);
        $jumlah_semua = null;
        if ($mode_semua) {
            $jumlah_semua = 0;
            foreach ($rentang as $r) {
                if ($r->stm & $mask) $jumlah_semua++;
            }
        }

        $cari = mb_strtolower((string) $p['search']);
        $status = (!empty($p['status']) && isset($tab[$p['status']])) ? $p['status'] : '';
        $hasil = array();
        foreach ($rentang as $r) {
            if ($status !== '' && $r->posisi !== $status) continue;
            if ($mode_semua && !($r->stm & $mask)) continue;
            if ($cari !== '') {
                $gabung = mb_strtolower($r->no_resi . ' ' . $r->no_pesanan . ' ' . $r->kurir . ' ' . $r->marketplace . ' ' . $r->nama_toko);
                if (mb_strpos($gabung, $cari) === false) continue;
            }
            $hasil[] = $r;
        }
        $total_filter = count($hasil);

        // Kolom pengurutan. 'umur' dibalik: umur terbesar = tanggal retur terkecil.
        $kol = $p['order'];
        $asc = ($p['dir'] === 'asc');
        if ($kol === 'umur') { $kol = 'tanggal_retur'; $asc = !$asc; }
        $angka = in_array($kol, array('nilai', 'umur_hari', 'tahap'), true);

        // Urut lewat array_multisort (kunci dihitung sekali): usort dengan closure untuk puluhan ribu baris memakan ~1 detik.
        // Kunci sama -> urut no_resi, arah mengikuti kolom (sama dengan perilaku usort sebelumnya).
        $kunci = array();
        $tie = array();
        foreach ($hasil as $r) {
            $kunci[] = $angka ? (float) $r->$kol : strtolower((string) $r->$kol);
            $tie[] = (string) $r->no_resi;
        }
        $arah = $asc ? SORT_ASC : SORT_DESC;
        array_multisort($kunci, $arah, $angka ? SORT_NUMERIC : SORT_STRING, $tie, $arah, SORT_STRING, $hasil);

        $rows = array_slice($hasil, (int) $p['offset'], (int) $p['limit']);

        return array($rows, $total_filter, $total_rentang, $tab, $waktu, $jumlah_semua);
    }

    /** Tahap progres 0..4 dari posisi paket; serah terima hanya relevan untuk paket yang sudah dibuka dan belum diverifikasi. */
    public function hitung_tahap($posisi, $ada_serah)
    {
        switch ($posisi) {
            case self::POSISI_SUDAH_VERIF: return 4;
            case self::POSISI_SUDAH_SERAH: return 3;
            case self::POSISI_SUDAH_DIBUKA: return $ada_serah ? 3 : 2;
            case self::POSISI_BELUM_DIBUKA: return 1;
            default: return 0;
        }
    }

    /** "NAMA - ROLE - NOABSEN" (tbluser.name) -> "NOABSEN - NAMA - ROLE" (sama dengan serah terima paket cancel). */
    public function format_petugas($nama)
    {
        $nama = trim((string) $nama);
        $p = array_map('trim', explode(' - ', $nama));
        return count($p) >= 3 ? end($p) . ' - ' . $p[0] . ' - ' . $p[1] : $nama;
    }

    /** Tabel ada di DB arsip? (tabel baru baru ikut ke arsip setelah cek skema malam berikutnya.) */
    private function _tabel_arsip_ada($tabel)
    {
        static $cache = array();
        if (!isset($cache[$tabel])) {
            $n = $this->db->query(
                'SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                array($this->_db_arsip(), $tabel)
            )->row();
            $cache[$tabel] = ((int) $n->n > 0);
        }
        return $cache[$tabel];
    }

    /** Skema yang perlu dibaca untuk $tabel: prod, plus arsip bila tabelnya sudah ada di sana. */
    private function _skema($tabel)
    {
        $skema = array('`' . $this->db->database . '`');
        if ($this->_tabel_arsip_ada($tabel)) $skema[] = '`' . $this->_db_arsip() . '`';
        return $skema;
    }

    /**
     * Peta resi (huruf besar) -> array('awal' => cetak pertama, 'akhir' => cetak terakhir, 'n' => jumlah cetak).
     * Tabel kecil, dibaca segar tiap permintaan (tidak ikut cache daftar). Arsip adalah salinan superset, jadi
     * jumlah cetak diambil yang terbesar (bukan dijumlah) supaya baris yang ada di prod dan arsip tidak dihitung dua kali.
     */
    public function peta_serah()
    {
        $peta = array();
        if (!$this->db->table_exists('tblretur_serah_terima')) return $peta;
        foreach ($this->_skema('tblretur_serah_terima') as $sk) {
            foreach ($this->db->query("SELECT UPPER(no_resi) r, MIN(tanggal_serah) a, MAX(tanggal_serah) z, COUNT(*) n FROM {$sk}.tblretur_serah_terima GROUP BY UPPER(no_resi)")->result() as $x) {
                if (!isset($peta[$x->r])) {
                    $peta[$x->r] = array('awal' => $x->a, 'akhir' => $x->z, 'n' => (int) $x->n);
                } else {
                    $peta[$x->r] = array(
                        'awal' => min($peta[$x->r]['awal'], $x->a), 'akhir' => max($peta[$x->r]['akhir'], $x->z),
                        'n' => max($peta[$x->r]['n'], (int) $x->n),
                    );
                }
            }
        }
        return $peta;
    }

    /**
     * Catat cetak serah terima: satu baris per resi per kali cetak (riwayat tidak ditimpa). cetak_ke = jumlah cetak
     * sebelumnya + 1; cetak ke-2 dan seterusnya bertanda is_ulang = 1 (cetak ulang).
     * @return int jumlah baris tercatat
     */
    public function catat_serah(array $resi, $id_user, $nama_user)
    {
        $resi = array_values(array_unique(array_filter(array_map('strval', $resi))));
        if (!$resi || !$this->db->table_exists('tblretur_serah_terima')) return 0;
        $now = date('Y-m-d H:i:s');
        $batch = 'SRH-' . date('ymd-His') . '-' . (int) $id_user;
        $petugas = $this->format_petugas($nama_user);
        $sudah = $this->peta_serah();
        $baris = array();
        foreach ($resi as $r) {
            $n = isset($sudah[strtoupper($r)]) ? (int) $sudah[strtoupper($r)]['n'] : 0;
            $baris[] = array('no_resi' => $r, 'tanggal_serah' => $now, 'id_pegawai' => (int) $id_user,
                'petugas' => $petugas, 'batch' => $batch, 'cetak_ke' => $n + 1, 'is_ulang' => $n > 0 ? 1 : 0);
        }
        $this->db->trans_start();
        $this->db->insert_batch('tblretur_serah_terima', $baris);
        $this->db->trans_complete();
        return $this->db->trans_status() ? count($baris) : 0;
    }

    /**
     * Rincian tiap tahap untuk sekumpulan resi (kartu progres & Excel): tanggal-jam + petugas.
     * Dibaca langsung dari tabel sumber (prod + arsip), hanya untuk resi yang diminta.
     *   terima = scan Terima Retur PERTAMA (tblresiretur), buka = scan Buka Retur TERAKHIR (tblbukaretur),
     *   serah = cetak serah terima TERAKHIR (tblretur_serah_terima),
     *   verif = Verifikasi Retur (tblreturverifikasi); bila tidak ada, ACC Accounting > Returan Buka (tblbukaretur.status_acc).
     *
     * @return array resi(huruf besar) => array(terima|buka|serah|verif => array('t' => 'Y-m-d H:i:s', 'p' => 'NOABSEN - NAMA - ROLE') atau null)
     */
    public function detail_progress(array $resi, array $jenis)
    {
        $out = array();
        $resi = array_values(array_unique(array_map('strval', $resi)));
        if (!$resi) return $out;

        $kond = array();
        if (in_array(self::JENIS_BIASA, $jenis, true)) $kond[] = '(is_komplain IS NULL OR is_komplain = 0)';
        if (in_array(self::JENIS_KOMPLAIN, $jenis, true)) $kond[] = 'is_komplain = 1';
        $kond = $kond ? '(' . implode(' OR ', $kond) . ')' : '1=0';

        $mentah = array('terima' => array(), 'buka' => array(), 'acc' => array(), 'serah' => array(), 'serah_akhir' => array(), 'verif' => array());
        $serah_id = array();   // id_serah yang sudah dihitung (baris prod & arsip yang sama tidak dihitung dua kali)
        $simpan = function ($jenis_data, $resi_x, $t, $id, $teks = null, $pilih_awal = false) use (&$mentah) {
            $k = strtoupper((string) $resi_x);
            if ($t === null || $t === '') return;
            $lama = isset($mentah[$jenis_data][$k]) ? $mentah[$jenis_data][$k] : null;
            if ($lama === null || ($pilih_awal ? $t < $lama['t'] : $t > $lama['t'])) {
                $mentah[$jenis_data][$k] = array('t' => $t, 'id' => $id, 'teks' => $teks);
            }
        };
        $ada_serah = $this->db->table_exists('tblretur_serah_terima');

        foreach (array_chunk($resi, 500) as $grup) {
            $in = implode(',', array_map(array($this->db, 'escape'), $grup));
            foreach ($this->_skema('tblresiretur') as $sk) {
                foreach ($this->db->query("SELECT noresi, tanggal_resiretur t, id_pegawai p FROM {$sk}.tblresiretur WHERE noresi IN ({$in}) AND {$kond}")->result() as $x) {
                    $simpan('terima', $x->noresi, $x->t, $x->p, null, true);
                }
            }
            foreach ($this->_skema('tblbukaretur') as $sk) {
                foreach ($this->db->query("SELECT resi_buka, COALESCE(tanggal_buka_retur, created_at) t, id_pegawai p, status_acc, acc_by, updated_at FROM {$sk}.tblbukaretur WHERE resi_buka IN ({$in}) AND {$kond}")->result() as $x) {
                    $simpan('buka', $x->resi_buka, $x->t, $x->p);
                    if ((int) $x->status_acc === 1) $simpan('acc', $x->resi_buka, $x->updated_at, $x->acc_by);
                }
            }
            if ($ada_serah) {
                foreach ($this->_skema('tblretur_serah_terima') as $sk) {
                    foreach ($this->db->query("SELECT id_serah, no_resi, tanggal_serah t, id_pegawai p, petugas FROM {$sk}.tblretur_serah_terima WHERE no_resi IN ({$in})")->result() as $x) {
                        $simpan('serah', $x->no_resi, $x->t, $x->p, $x->petugas, true);         // cetak pertama = serah terima
                        $simpan('serah_akhir', $x->no_resi, $x->t, $x->p, $x->petugas);          // cetak terakhir
                        $serah_id[strtoupper((string) $x->no_resi)][(int) $x->id_serah] = true;
                    }
                }
            }
            foreach ($this->_skema('tblreturverifikasi') as $sk) {
                foreach ($this->db->query("SELECT no_resi, verified_at t, verified_by p FROM {$sk}.tblreturverifikasi WHERE no_resi IN ({$in}) AND verified = 1")->result() as $x) {
                    $simpan('verif', $x->no_resi, $x->t, $x->p);
                }
            }
        }

        // Nama petugas (tbluser.id_user; kolom id_pegawai di tabel retur menyimpan id_user).
        $ids = array();
        foreach ($mentah as $per) foreach ($per as $d) if (!empty($d['id'])) $ids[(int) $d['id']] = true;
        $nama = array();
        if ($ids) {
            foreach ($this->db->query('SELECT id_user, name FROM tbluser WHERE id_user IN (' . implode(',', array_keys($ids)) . ')')->result() as $u) {
                $nama[(int) $u->id_user] = $this->format_petugas($u->name);
            }
        }
        $isi = function ($d) use ($nama) {
            if (!$d) return null;
            $p = ($d['teks'] !== null && $d['teks'] !== '') ? $d['teks'] : (isset($nama[(int) $d['id']]) ? $nama[(int) $d['id']] : null);
            return array('t' => $d['t'], 'p' => $p);
        };
        foreach ($resi as $r) {
            $k = strtoupper($r);
            $verif = isset($mentah['verif'][$k]) ? $mentah['verif'][$k] : (isset($mentah['acc'][$k]) ? $mentah['acc'][$k] : null);
            $serah = $isi(isset($mentah['serah'][$k]) ? $mentah['serah'][$k] : null);
            if ($serah) {
                $serah['n'] = isset($serah_id[$k]) ? count($serah_id[$k]) : 1;
                $serah['akhir'] = $isi(isset($mentah['serah_akhir'][$k]) ? $mentah['serah_akhir'][$k] : null);
            }
            $out[$k] = array(
                'terima' => $isi(isset($mentah['terima'][$k]) ? $mentah['terima'][$k] : null),
                'buka'   => $isi(isset($mentah['buka'][$k]) ? $mentah['buka'][$k] : null),
                'serah'  => $serah,
                'verif'  => $isi($verif),
            );
        }
        return $out;
    }

    /**
     * Tanggal tahap untuk sekumpulan resi: 'terima' = scan Terima Retur PERTAMA, 'verif' = verifikasi accounting
     * (tblreturverifikasi.verified_at; bila tidak ada, ACC Returan Buka = tblbukaretur.updated_at saat status_acc = 1).
     * @return array resi(huruf besar) => 'Y-m-d H:i:s'
     */
    private function _peta_tanggal(array $resi, array $jenis, $untuk)
    {
        $peta = array();
        $resi = array_values(array_unique($resi));
        if (!$resi) return $peta;

        $kond = array();
        if (in_array(self::JENIS_BIASA, $jenis, true)) $kond[] = '(is_komplain IS NULL OR is_komplain = 0)';
        if (in_array(self::JENIS_KOMPLAIN, $jenis, true)) $kond[] = 'is_komplain = 1';
        $kond = $kond ? '(' . implode(' OR ', $kond) . ')' : '1=0';

        $isi = function (&$tujuan, $resi_x, $t, $awal) {
            $k = strtoupper((string) $resi_x);
            if ($t === null || $t === '') return;
            if (!isset($tujuan[$k]) || ($awal ? $t < $tujuan[$k] : $t > $tujuan[$k])) $tujuan[$k] = $t;
        };
        $acc = array();
        foreach (array_chunk($resi, 500) as $grup) {
            $in = implode(',', array_map(array($this->db, 'escape'), $grup));
            if ($untuk === 'terima') {
                foreach ($this->_skema('tblresiretur') as $sk) {
                    foreach ($this->db->query("SELECT noresi, MIN(tanggal_resiretur) t FROM {$sk}.tblresiretur WHERE noresi IN ({$in}) AND {$kond} GROUP BY noresi")->result() as $x) {
                        $isi($peta, $x->noresi, $x->t, true);
                    }
                }
            } else {
                foreach ($this->_skema('tblreturverifikasi') as $sk) {
                    foreach ($this->db->query("SELECT no_resi, MAX(verified_at) t FROM {$sk}.tblreturverifikasi WHERE no_resi IN ({$in}) AND verified = 1 GROUP BY no_resi")->result() as $x) {
                        $isi($peta, $x->no_resi, $x->t, false);
                    }
                }
                foreach ($this->_skema('tblbukaretur') as $sk) {
                    foreach ($this->db->query("SELECT resi_buka, MAX(updated_at) t FROM {$sk}.tblbukaretur WHERE resi_buka IN ({$in}) AND status_acc = 1 AND {$kond} GROUP BY resi_buka")->result() as $x) {
                        $isi($acc, $x->resi_buka, $x->t, false);
                    }
                }
            }
        }
        foreach ($acc as $k => $t) if (!isset($peta[$k])) $peta[$k] = $t;
        return $peta;
    }
}
