<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM MONITORING -> "Cabut SKU": daftar SKU yang layak dicabut dari rak.
 * Syarat tampil: tblsku.kategori_sku di KATEGORI dan stok total Jubelio antara STOK_MIN dan BATAS_STOK, urut stok terbanyak.
 * Stok disalin dari Jubelio ke tblcabut_sku_stok hanya saat tombol Perbarui diklik (Cabut_sku::perbarui).
 */
class Cabut_sku_fcd extends CI_Model
{
    const KATEGORI = ['EOL', 'WP EOL', 'CUT'];
    const STOK_MIN = 1;
    const BATAS_STOK = 10;
    const UKURAN_HALAMAN = 200;

    /** SKU yang memenuhi syarat, stok paling banyak lebih dulu. */
    public function daftar()
    {
        $in = implode(',', array_fill(0, count(self::KATEGORI), '?'));
        // Rak Jubelio kosong tetap tampil (belum dicabut); rak CABUT atau berawalan KR- dianggap sudah dicabut dan disembunyikan.
        $sql = "SELECT s.id_sku, s.kategori_sku, s.link_foto, s.foto_lokal, k.rak_jubelio, k.stok_total, k.stok_display, k.stok_gudang,
                       k.qty_real, k.qty_real_oleh, k.qty_real_at, k.qty_real_foto, k.pindah_ke, k.pindah_ke_oleh, k.pindah_ke_at
                FROM tblsku s
                JOIN tblcabut_sku_stok k ON k.id_sku = s.id_sku
                WHERE s.kategori_sku IN ($in) AND k.stok_total BETWEEN ? AND ?
                  AND (k.rak_jubelio IS NULL OR (UPPER(TRIM(k.rak_jubelio)) <> 'CABUT' AND UPPER(TRIM(k.rak_jubelio)) NOT LIKE 'KR-%'))
                ORDER BY k.stok_total DESC, s.id_sku ASC";
        $bind = array_merge(self::KATEGORI, [self::STOK_MIN, self::BATAS_STOK]);
        $rows = $this->db->query($sql, $bind)->result_array();
        foreach ($rows as &$r) {
            // Foto SKU untuk klien: URL asli + salinan lokal (assets/js/foto_sku.js memilih yang bisa dimuat).
            $r['foto']       = trim((string) $r['link_foto']);
            $r['foto_lokal'] = foto_sku_lokal_url($r['link_foto'], $r['foto_lokal']);
            unset($r['link_foto']);
        }
        unset($r);
        return $rows;
    }

    /** Tab tempat satu baris berada: isi (qty real kosong), adj (beda dgn stok), rak (sama dgn stok -> isi no rak tujuan). */
    public static function tab_baris(array $r)
    {
        if ($r['qty_real'] === null) {
            return 'isi';
        }
        return (int) $r['qty_real'] !== (int) $r['stok_total'] ? 'adj' : 'rak';
    }

    /**
     * Baris satu tab dengan filter yang sama seperti layar: kategori (array, kosong = semua) dan kata kunci
     * (SKU / no rak / rak tujuan). Dipakai ekspor Excel supaya isinya persis yang dilihat user.
     */
    public function daftar_tab($tab, array $kategori = [], $q = '')
    {
        $q = mb_strtolower(trim((string) $q), 'UTF-8');
        $out = [];
        foreach ($this->daftar() as $r) {
            if (self::tab_baris($r) !== $tab) {
                continue;
            }
            if ($kategori && !in_array($r['kategori_sku'], $kategori, TRUE)) {
                continue;
            }
            if ($q !== '' && mb_strpos(mb_strtolower($r['id_sku'] . ' ' . $r['rak_jubelio'] . ' ' . $r['pindah_ke'], 'UTF-8'), $q) === FALSE) {
                continue;
            }
            $out[] = $r;
        }
        if ($tab === 'rak') { // yang belum diisi dulu
            usort($out, function ($a, $b) { return ($a['pindah_ke'] !== null) <=> ($b['pindah_ke'] !== null); });
        }
        return $out;
    }

    /** Waktu perbarui terakhir dan jumlah SKU berkategori yang belum punya data stok. */
    public function ringkasan()
    {
        $in = implode(',', array_fill(0, count(self::KATEGORI), '?'));
        $terakhir = $this->db->query("SELECT MAX(diperbarui) AS t FROM tblcabut_sku_stok")->row();
        $belum = $this->db->query(
            "SELECT COUNT(*) AS n FROM tblsku s LEFT JOIN tblcabut_sku_stok k ON k.id_sku = s.id_sku
             WHERE s.kategori_sku IN ($in) AND k.id_sku IS NULL",
            self::KATEGORI
        )->row();
        return [
            'diperbarui' => $terakhir && $terakhir->t ? $terakhir->t : null,
            'belum_ada'  => (int) $belum->n,
        ];
    }

    /** Upsert satu halaman hasil Jubelio_stok::daftar_stok. Mengembalikan jumlah baris yang diproses. */
    public function simpan_halaman(array $items)
    {
        if (!$items) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $ph = [];
        $bind = [];
        foreach ($items as $it) {
            $ph[] = '(?, ?, ?, ?, ?, ?)';
            array_push($bind, $it['sku'], $it['item_id'], $it['stok_total'], $it['stok_display'], $it['stok_gudang'], $now);
        }
        $this->db->query(
            "INSERT INTO tblcabut_sku_stok (id_sku, item_id, stok_total, stok_display, stok_gudang, diperbarui) VALUES " . implode(',', $ph) .
            " ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), stok_total = VALUES(stok_total), stok_display = VALUES(stok_display), stok_gudang = VALUES(stok_gudang), diperbarui = VALUES(diperbarui)",
            $bind
        );
        return count($items);
    }

    /** Upsert satu halaman hasil Jubelio_stok::daftar_rak (hanya kolom rak_jubelio). */
    public function simpan_rak_halaman(array $items)
    {
        if (!$items) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $ph = [];
        $bind = [];
        foreach ($items as $it) {
            $ph[] = '(?, ?, ?, ?)';
            array_push($bind, $it['sku'], $it['item_id'], $it['rak'], $now);
        }
        $this->db->query(
            "INSERT INTO tblcabut_sku_stok (id_sku, item_id, rak_jubelio, diperbarui) VALUES " . implode(',', $ph) .
            " ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), rak_jubelio = VALUES(rak_jubelio), diperbarui = VALUES(diperbarui)",
            $bind
        );
        return count($items);
    }

    /**
     * Simpan qty real satu SKU (UPDATE satu baris, pencocokan persis). $qty sudah divalidasi bilangan bulat >= 0
     * oleh controller. Mengembalikan FALSE bila SKU tidak ada di tblcabut_sku_stok.
     */
    public function simpan_qty_real($sku, $qty, $oleh, $foto)
    {
        if (!$this->ada($sku)) {
            return FALSE;
        }
        $this->db->query(
            "UPDATE tblcabut_sku_stok SET qty_real = ?, qty_real_oleh = ?, qty_real_at = ?, qty_real_foto = ? WHERE id_sku = ? AND BINARY id_sku = BINARY ?",
            [(int) $qty, $oleh, date('Y-m-d H:i:s'), $foto, $sku, $sku]
        );
        return TRUE;
    }

    /**
     * Simpan no rak tujuan (kolom "Pindah ke") satu SKU. Hanya untuk SKU yang berada di tab Perlu Isi No. Rak:
     * qty real sudah diisi dan sama dengan stok total. Mengembalikan '' bila sukses, atau pesan galat.
     * Format ($rak) sudah divalidasi controller: tanpa spasi, hanya huruf, angka, '-' dan '/', 1-50 karakter.
     */
    public function simpan_pindah_ke($sku, $rak, $oleh)
    {
        $baris = $this->db->query("SELECT stok_total, qty_real FROM tblcabut_sku_stok WHERE id_sku = ? AND BINARY id_sku = BINARY ?", [$sku, $sku])->row_array();
        if (!$baris) {
            return 'SKU ' . $sku . ' tidak ditemukan di data Cabut SKU.';
        }
        if ($baris['qty_real'] === null || (int) $baris['qty_real'] !== (int) $baris['stok_total']) {
            return 'SKU ini belum masuk tab Perlu Isi No. Rak (qty real harus sudah diisi dan sama dengan stok total Jubelio).';
        }
        $this->db->query(
            "UPDATE tblcabut_sku_stok SET pindah_ke = ?, pindah_ke_oleh = ?, pindah_ke_at = ? WHERE id_sku = ? AND BINARY id_sku = BINARY ?",
            [$rak, $oleh, date('Y-m-d H:i:s'), $sku, $sku]
        );
        return '';
    }

    /** Apakah SKU (persis) ada di tblcabut_sku_stok. */
    public function ada($sku)
    {
        return (bool) $this->db->query("SELECT 1 FROM tblcabut_sku_stok WHERE id_sku = ? AND BINARY id_sku = BINARY ?", [$sku, $sku])->row();
    }
}
