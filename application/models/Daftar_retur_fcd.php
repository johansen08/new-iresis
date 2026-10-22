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

    const POSISI_BELUM_DITERIMA = 'BELUM_DITERIMA';
    const POSISI_BELUM_DIBUKA   = 'BELUM_DIBUKA';
    const POSISI_SUDAH_DIBUKA   = 'SUDAH_DIBUKA';

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

        return "
            SELECT f.*,
                   DATEDIFF(NOW(), f.tanggal_retur) AS umur_hari,
                   CASE
                     WHEN f.n_buka > 0   THEN '" . self::POSISI_SUDAH_DIBUKA . "'
                     WHEN f.n_iresis > 0 THEN '" . self::POSISI_BELUM_DIBUKA . "'
                     ELSE '" . self::POSISI_BELUM_DITERIMA . "'
                   END AS posisi
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
                         (SELECT COUNT(*) FROM tblresiretur r WHERE r.noresi = g.resi_l),
                         (SELECT COUNT(*) FROM {$arsip}.tblresiretur r WHERE r.noresi = g.resi_l)
                       ) AS n_iresis,
                       GREATEST(
                         (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = g.resi_l),
                         (SELECT COUNT(*) FROM {$arsip}.tblbukaretur b WHERE b.resi_buka = g.resi_l)
                       ) AS n_buka
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
        $db   = $this->db->database;
        $file = APPPATH . 'cache/daftar_retur_' . preg_replace('/[^A-Za-z0-9_]/', '', $db) . '.json';

        if (!$segarkan && is_file($file) && (time() - filemtime($file)) < self::CACHE_DETIK) {
            $isi = json_decode((string) @file_get_contents($file), true);
            if (is_array($isi) && isset($isi['baris'])) {
                return array(array_map(function ($r) { return (object) $r; }, $isi['baris']), (int) $isi['waktu']);
            }
        }

        $rows  = $this->db->query($this->_sql_dasar())->result();
        $waktu = time();
        @file_put_contents($file, json_encode(array('waktu' => $waktu, 'baris' => $rows)), LOCK_EX);

        return array($rows, $waktu);
    }

    /**
     * @param array $p start, end (Y-m-d H:i:s), status (posisi/kosong), search, order (kolom),
     *                 dir, offset, limit, segarkan
     * @return array [rows, total_difilter, total_semua_pada_rentang, jumlah_per_status, waktu_hitung]
     */
    public function get_list(array $p)
    {
        list($semua, $waktu) = $this->semua_baris(!empty($p['segarkan']));

        // Rentang memakai TANGGAL PESAN (tanggal order), bukan tanggal retur.
        $rentang = array();
        $tab = array(self::POSISI_BELUM_DITERIMA => 0, self::POSISI_BELUM_DIBUKA => 0, self::POSISI_SUDAH_DIBUKA => 0);
        foreach ($semua as $r) {
            if ($r->tanggal_pesan >= $p['start'] && $r->tanggal_pesan <= $p['end']) {
                $rentang[] = $r;
                if (isset($tab[$r->posisi])) $tab[$r->posisi]++;
            }
        }
        $total_rentang = count($rentang);

        $cari = mb_strtolower((string) $p['search']);
        $status = (!empty($p['status']) && isset($tab[$p['status']])) ? $p['status'] : '';
        $hasil = array();
        foreach ($rentang as $r) {
            if ($status !== '' && $r->posisi !== $status) continue;
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
        $angka = in_array($kol, array('nilai', 'umur_hari'), true);

        usort($hasil, function ($a, $b) use ($kol, $asc, $angka) {
            $x = $a->$kol; $y = $b->$kol;
            $c = $angka ? ((float) $x <=> (float) $y) : strcasecmp((string) $x, (string) $y);
            if ($c === 0) $c = strcmp($a->no_resi, $b->no_resi);
            return $asc ? $c : -$c;
        });

        $rows = array_slice($hasil, (int) $p['offset'], (int) $p['limit']);

        return array($rows, $total_filter, $total_rentang, $tab, $waktu);
    }
}
