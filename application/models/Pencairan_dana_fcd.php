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
     * Subquery dasar: satu baris per resi (= per pesanan) pada rentang tanggal completed.
     * Kolom: id_printresi, no_pesanan, mp (awalan), tgl_selesai, tgl_cair, sudah, umur.
     */
    private function _dasar()
    {
        return "SELECT p.id_printresi,
                       MIN(d.no_pesanan) AS no_pesanan,
                       LEFT(MIN(d.no_pesanan), 2) AS mp,
                       p.tanggal_selesai AS tgl_selesai,
                       p.tanggal_pencairan AS tgl_cair,
                       (p.tanggal_pencairan IS NOT NULL) AS sudah,
                       DATEDIFF(CURDATE(), DATE(p.tanggal_selesai)) AS umur
                FROM tblprintresi p
                JOIN tbldetailprintresi d ON d.id_resi = p.id_printresi
                WHERE p.status_pesanan = 'COMPLETED'
                  AND p.tanggal_selesai >= ? AND p.tanggal_selesai <= ?
                GROUP BY p.id_printresi";
    }

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
     * Ringkasan per marketplace pada rentang tanggal (tanpa filter marketplace),
     * supaya jumlah di daftar pilihan marketplace dan semua angka tab bisa dihitung sekali.
     * Hasil: array awalan => array(n, sudah, kuning, merah).
     */
    public function ringkasan($start, $end)
    {
        $in = $this->_in(array_keys(self::peta_mp()));
        $sql = "SELECT x.mp,
                       COUNT(*) AS n,
                       SUM(x.sudah) AS sudah,
                       SUM(x.sudah = 0 AND x.umur <= " . self::BATAS_KUNING . ") AS kuning,
                       SUM(x.sudah = 0 AND x.umur > " . self::BATAS_KUNING . ") AS merah
                FROM (" . $this->_dasar() . ") x
                WHERE x.mp IN ($in)
                GROUP BY x.mp";
        $hasil = array();
        foreach (array_keys(self::peta_mp()) as $k) {
            $hasil[$k] = array('n' => 0, 'sudah' => 0, 'kuning' => 0, 'merah' => 0);
        }
        foreach ($this->db->query($sql, array($start, $end))->result() as $r) {
            $hasil[$r->mp] = array(
                'n' => (int) $r->n, 'sudah' => (int) $r->sudah,
                'kuning' => (int) $r->kuning, 'merah' => (int) $r->merah,
            );
        }
        return $hasil;
    }

    /** Syarat tambahan dari tab ('semua'|'belum'|'sudah') dan centang "hanya lewat 7 hari". */
    private function _syarat_tab($tab, $hanya_merah)
    {
        if ($tab === 'sudah') return ' AND x.sudah = 1';
        if ($tab === 'belum') {
            return ' AND x.sudah = 0' . ($hanya_merah ? ' AND x.umur > ' . self::BATAS_KUNING : '');
        }
        return '';
    }

    /**
     * Daftar baris untuk satu halaman (limit > 0) atau seluruhnya (limit = 0, untuk ekspor).
     * Urutan: belum cair dulu, yang paling lama di atas; sudah cair menyusul, terbaru dulu.
     */
    public function daftar($start, $end, $mps, $tab, $hanya_merah, $offset, $limit)
    {
        $in = $this->_in($this->_awalan($mps));
        $sql = "SELECT x.* FROM (" . $this->_dasar() . ") x
                WHERE x.mp IN ($in)" . $this->_syarat_tab($tab, $hanya_merah) . "
                ORDER BY x.sudah ASC,
                         (CASE WHEN x.sudah = 0 THEN UNIX_TIMESTAMP(x.tgl_selesai) END) ASC,
                         x.tgl_selesai DESC, x.id_printresi DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        return $this->db->query($sql, array($start, $end))->result();
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
