<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pencairan Dana (TIM FINANCE) — pantau pesanan Completed: dananya sudah cair atau belum.
 *
 * Satu baris = satu pesanan. Di data Iresis satu resi (tblprintresi) hanya memuat satu
 * nomor pesanan, jadi pesanan diambil dari tbldetailprintresi.no_pesanan lewat id_resi.
 *
 * Sumber:
 *   - tblprintresi.status_pesanan = 'COMPLETED' dan tanggal_selesai (completed_date Jubelio).
 *     Completed yang tanggal_selesai-nya kosong tidak bisa difilter rentang tanggal, jadi
 *     tidak ikut daftar ini.
 *   - tblprintresi.tanggal_pencairan: NULL = belum cair. Diisi dari upload laporan keuangan MP.
 *
 * Marketplace dari awalan nomor pesanan (SP Shopee, TT TikTok, TP Tokopedia, LZ Lazada).
 * Awalan lain (mis. SO reseller) tidak ada pencairan marketplace dan diabaikan.
 *
 * Status (patokan sama untuk semua marketplace, dihitung dari tanggal completed):
 *   sudah cair          : tanggal_pencairan terisi (hijau)
 *   belum, <= 7 hari    : kuning
 *   belum, >= 8 hari    : merah
 *
 * Nomor pesanan marketplace = bagian di antara tanda '-' pertama dan kedua
 * (TP-586720295584630101-109465 -> 586720295584630101; SP-586720295584630101 -> sama).
 */
class Pencairan_dana_fcd extends CI_Model
{
    /** Batas hari dari tanggal completed: <= ini kuning, di atasnya merah. */
    const BATAS_KUNING = 7;

    /** Awalan nomor pesanan -> nama marketplace. Urutan = urutan di filter. */
    public static function peta_mp()
    {
        return array('SP' => 'Shopee', 'TT' => 'TikTok', 'TP' => 'Tokopedia', 'LZ' => 'Lazada');
    }

    /** Ambil nomor pesanan marketplace dari ID Iresis. */
    public static function nomor_pesanan($id)
    {
        $bagian = explode('-', (string) $id);
        return isset($bagian[1]) ? $bagian[1] : (string) $id;
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Nomor pesanan (tbldetailprintresi.no_pesanan) dari resi p. Satu resi hanya memuat satu nomor
     * pesanan (semua baris SKU-nya sama), jadi satu baris detail cukup; subquery ini memakai indeks
     * idx_detail_resi_id dan menghindari GROUP BY + tabel sementara.
     */
    const SQL_NP = "(SELECT d.no_pesanan FROM tbldetailprintresi d WHERE d.id_resi = p.id_printresi LIMIT 1)";

    /** Daftar awalan marketplace yang valid, dari pilihan user ([] = semua). */
    private function _awalan($mps)
    {
        $semua = array_keys(self::peta_mp());
        $mps = is_array($mps) ? array_values(array_intersect($semua, $mps)) : array();
        return empty($mps) ? $semua : $mps;
    }

    /** "'SP','TT'" dari daftar awalan yang sudah divalidasi (hanya huruf kapital). */
    private function _in($awalan)
    {
        return "'" . implode("','", $awalan) . "'";
    }

    /**
     * Syarat pencarian nomor pesanan (cocok sebagian, di mana saja) atas ekspresi $kolom yang
     * berisi ID Iresis (TT-nomor-sufiks). Hanya huruf/angka yang dipakai, jadi tidak ada karakter
     * LIKE, dan awalan/sufiks tidak ikut dicocokkan (yang dicari hanya bagian nomor).
     * Hasil: array(sql_tambahan, bind_tambahan).
     */
    public static function syarat_cari($q, $kolom)
    {
        $q = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $q), 0, 40);
        if ($q === '') return array('', array());
        return array(" AND SUBSTRING_INDEX(SUBSTRING_INDEX($kolom, '-', 2), '-', -1) LIKE ?", array('%' . $q . '%'));
    }

    /**
     * Ringkasan per marketplace pada rentang tanggal (tanpa filter marketplace),
     * supaya jumlah di daftar pilihan marketplace dan semua angka tab bisa dihitung sekali.
     * Hasil: array awalan => array(n, sudah, kuning, merah).
     */
    public function ringkasan($start, $end, $q = '')
    {
        list($sql_cari, $bind_cari) = self::syarat_cari($q, 'y.np');
        $in = $this->_in(array_keys(self::peta_mp()));
        $sql = "SELECT LEFT(y.np, 2) AS mp,
                       COUNT(*) AS n,
                       SUM(y.sudah) AS sudah,
                       SUM(y.sudah = 0 AND y.umur <= " . self::BATAS_KUNING . ") AS kuning,
                       SUM(y.sudah = 0 AND y.umur > " . self::BATAS_KUNING . ") AS merah
                FROM (SELECT " . self::SQL_NP . " AS np,
                             (p.tanggal_pencairan IS NOT NULL) AS sudah,
                             DATEDIFF(CURDATE(), DATE(p.tanggal_selesai)) AS umur
                      FROM tblprintresi p
                      WHERE p.status_pesanan = 'COMPLETED'
                        AND p.tanggal_selesai >= ? AND p.tanggal_selesai <= ?) y
                WHERE LEFT(y.np, 2) IN ($in)" . $sql_cari . "
                GROUP BY LEFT(y.np, 2)";
        $hasil = array();
        foreach (array_keys(self::peta_mp()) as $k) {
            $hasil[$k] = array('n' => 0, 'sudah' => 0, 'kuning' => 0, 'merah' => 0);
        }
        foreach ($this->db->query($sql, array_merge(array($start, $end), $bind_cari))->result() as $r) {
            $hasil[$r->mp] = array(
                'n' => (int) $r->n, 'sudah' => (int) $r->sudah,
                'kuning' => (int) $r->kuning, 'merah' => (int) $r->merah,
            );
        }
        return $hasil;
    }

    /**
     * Daftar baris untuk satu halaman (limit > 0) atau seluruhnya (limit = 0, untuk ekspor).
     * Urutan: belum cair dulu, yang paling lama di atas; sudah cair menyusul, terbaru dulu.
     * Tab Belum/Sudah diurutkan murni menurut tanggal_selesai sehingga MariaDB bisa membaca lewat
     * indeks (status_pesanan, tanggal_selesai) dan berhenti begitu halaman terisi.
     * Kolom hasil: id_printresi, no_pesanan, mp (awalan), tgl_selesai, tgl_cair, sudah, umur.
     */
    public function daftar($start, $end, $mps, $tab, $hanya_merah, $offset, $limit, $q = '')
    {
        list($sql_cari, $bind_cari) = self::syarat_cari($q, 'd.no_pesanan');
        $in = $this->_in($this->_awalan($mps));

        if ($tab === 'sudah') {
            $syarat_tab = ' AND p.tanggal_pencairan IS NOT NULL';
            $urut = 'p.tanggal_selesai DESC, p.id_printresi DESC';
        } elseif ($tab === 'belum') {
            $syarat_tab = ' AND p.tanggal_pencairan IS NULL';
            // umur > 7 hari  <=>  tanggal_selesai sebelum (hari ini - 7 hari) pukul 00:00
            if ($hanya_merah) {
                $syarat_tab .= ' AND p.tanggal_selesai < DATE_SUB(CURDATE(), INTERVAL ' . (int) self::BATAS_KUNING . ' DAY)';
            }
            $urut = 'p.tanggal_selesai ASC, p.id_printresi DESC';
        } else {
            $syarat_tab = '';
            $urut = '(p.tanggal_pencairan IS NOT NULL) ASC,
                     (CASE WHEN p.tanggal_pencairan IS NULL THEN p.tanggal_selesai END) ASC,
                     p.tanggal_selesai DESC, p.id_printresi DESC';
        }

        $sql = "SELECT p.id_printresi,
                       " . self::SQL_NP . " AS no_pesanan,
                       p.tanggal_selesai AS tgl_selesai,
                       p.tanggal_pencairan AS tgl_cair,
                       (p.tanggal_pencairan IS NOT NULL) AS sudah,
                       DATEDIFF(CURDATE(), DATE(p.tanggal_selesai)) AS umur
                FROM tblprintresi p
                WHERE p.status_pesanan = 'COMPLETED'
                  AND p.tanggal_selesai >= ? AND p.tanggal_selesai <= ?" . $syarat_tab . "
                  AND EXISTS (SELECT 1 FROM tbldetailprintresi d
                              WHERE d.id_resi = p.id_printresi AND LEFT(d.no_pesanan, 2) IN ($in)" . $sql_cari . ")
                ORDER BY " . $urut;
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $rows = $this->db->query($sql, array_merge(array($start, $end), $bind_cari))->result();
        foreach ($rows as $r) {
            $r->mp = substr((string) $r->no_pesanan, 0, 2);
        }
        return $rows;
    }

    // ------------------------------------------------------------ upload tanggal pencairan

    /**
     * Cocokkan nomor pesanan marketplace ke satu resi. Shopee tanpa sufiks (SP-nomor),
     * lainnya ber-sufiks (TT-nomor-123). Hasil: id_printresi, tanggal_pencairan saat ini,
     * dan status_pesanan; NULL bila tidak ditemukan.
     */
    public function cari_resi($nomor)
    {
        $nomor = (string) $nomor;
        $like = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $nomor);
        $sql = "SELECT p.id_printresi, p.tanggal_pencairan, p.status_pesanan
                FROM tbldetailprintresi d
                JOIN tblprintresi p ON p.id_printresi = d.id_resi
                WHERE d.no_pesanan = ?
                   OR d.no_pesanan = ?
                   OR d.no_pesanan LIKE ?
                   OR d.no_pesanan LIKE ?
                   OR d.no_pesanan LIKE ?
                LIMIT 1";
        $row = $this->db->query($sql, array(
            'SP-' . $nomor, 'LZ-' . $nomor,
            'TT-' . $like . '-%', 'TP-' . $like . '-%', 'LZ-' . $like . '-%',
        ))->row();
        return $row ? $row : null;
    }

    /** Isi tanggal pencairan hanya bila masih kosong. Mengembalikan true bila baris berubah. */
    public function isi_pencairan($id_printresi, $tanggal)
    {
        $this->db->query(
            'UPDATE tblprintresi SET tanggal_pencairan = ? WHERE id_printresi = ? AND tanggal_pencairan IS NULL',
            array($tanggal, (int) $id_printresi)
        );
        return $this->db->affected_rows() > 0;
    }
}
