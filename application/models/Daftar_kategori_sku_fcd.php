<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Kategori SKU": daftar SKU (gambar, no rak, kategori) baca-saja dengan pencarian dan
 * filter kategori multi-pilih. Paging di sisi server karena tblsku berisi >11 ribu baris.
 */
class Daftar_kategori_sku_fcd extends CI_Model
{
    /** Nilai filter khusus untuk SKU yang kolom kategori_sku-nya NULL / kosong. */
    const BELUM_ADA = '__BELUM__';
    const UKURAN_BOLEH = [25, 50, 100];

    /** Daftar kategori yang ada beserta jumlah SKU, terbanyak dulu. Nilai NULL/kosong digabung jadi BELUM_ADA. */
    public function daftar_kategori()
    {
        $rows = $this->db->query(
            "SELECT COALESCE(NULLIF(TRIM(kategori_sku), ''), ?) AS kategori, COUNT(*) AS n
             FROM tblsku GROUP BY 1 ORDER BY n DESC, kategori ASC",
            [self::BELUM_ADA]
        )->result_array();
        foreach ($rows as &$r) {
            $r['n'] = (int) $r['n'];
        }
        return $rows;
    }

    /** Klausa WHERE + bind untuk kata kunci dan filter kategori (dipakai layar dan ekspor). */
    private function bangun_where($q, array $kategori)
    {
        $where = [];
        $bind  = [];
        $q = trim((string) $q);
        if ($q !== '') {
            $like = '%' . $this->db->escape_like_str($q) . '%';
            $where[] = '(id_sku LIKE ? OR no_rak LIKE ?)';
            array_push($bind, $like, $like);
        }
        if ($kategori) {
            $in = [];
            $dgn_null = FALSE;
            foreach ($kategori as $k) {
                if ($k === self::BELUM_ADA) {
                    $dgn_null = TRUE;
                } else {
                    $in[] = $k;
                }
            }
            $bagian = [];
            if ($in) {
                $bagian[] = 'kategori_sku IN (' . implode(',', array_fill(0, count($in), '?')) . ')';
                $bind = array_merge($bind, $in);
            }
            if ($dgn_null) {
                $bagian[] = "(kategori_sku IS NULL OR TRIM(kategori_sku) = '')";
            }
            $where[] = '(' . implode(' OR ', $bagian) . ')';
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bind];
    }

    /** Semua SKU yang cocok (tanpa paging) untuk ekspor Excel, urut SKU. */
    public function semua($q, array $kategori)
    {
        list($sql_where, $bind) = $this->bangun_where($q, $kategori);
        return $this->db->query(
            "SELECT id_sku, kategori_sku, no_rak FROM tblsku" . $sql_where . " ORDER BY id_sku ASC", $bind
        )->result_array();
    }

    /**
     * Satu halaman SKU. $kategori = array nilai kategori (kosong = semua; BELUM_ADA = tanpa kategori),
     * $q = kata kunci untuk SKU atau no rak (potongan teks, tidak peka huruf besar-kecil).
     *
     * @return array {total, baris: [{id_sku, kategori_sku, no_rak, foto, foto_lokal}]}
     */
    public function halaman($q, array $kategori, $halaman, $ukuran)
    {
        list($sql_where, $bind) = $this->bangun_where($q, $kategori);

        $total = (int) $this->db->query("SELECT COUNT(*) AS n FROM tblsku" . $sql_where, $bind)->row()->n;
        $ukuran  = in_array($ukuran, self::UKURAN_BOLEH, TRUE) ? $ukuran : 50;
        $maks    = max(1, (int) ceil($total / $ukuran));
        $halaman = min(max(1, (int) $halaman), $maks);

        $rows = $this->db->query(
            "SELECT id_sku, kategori_sku, no_rak, link_foto, foto_lokal FROM tblsku" . $sql_where .
            " ORDER BY id_sku ASC LIMIT " . (int) $ukuran . " OFFSET " . (int) (($halaman - 1) * $ukuran),
            $bind
        )->result_array();
        foreach ($rows as &$r) {
            // Foto SKU untuk klien: URL asli + salinan lokal (assets/js/foto_sku.js memilih yang bisa dimuat).
            $r['foto']       = trim((string) $r['link_foto']);
            $r['foto_lokal'] = foto_sku_lokal_url($r['link_foto'], $r['foto_lokal']);
            unset($r['link_foto']);
        }
        unset($r);

        return ['total' => $total, 'halaman' => $halaman, 'total_halaman' => $maks, 'ukuran' => $ukuran, 'baris' => $rows];
    }
}
