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
    /** Jumlah kolom bulan penjualan (bulan berjalan + 2 bulan sebelumnya). */
    const JUMLAH_BULAN = 3;
    /** Umur cache penjualan (detik). Menghitung ulang semua SKU makan ±11 detik, jadi tidak tiap halaman. */
    const CACHE_PENJUALAN_DETIK = 1800;
    const NAMA_BULAN = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEPT', 'OKT', 'NOV', 'DES'];

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

    /** Kolom bulan penjualan, terbaru dulu: [{dari, sampai (eksklusif), label: 'NOV/26'}]. */
    public function bulan_penjualan()
    {
        $awal = new DateTime(date('Y-m-01'));
        $out  = [];
        for ($i = 0; $i < self::JUMLAH_BULAN; $i++) {
            $dari   = (clone $awal)->modify("-$i month");
            $sampai = (clone $dari)->modify('+1 month');
            $out[]  = [
                'dari'   => $dari->format('Y-m-d'),
                'sampai' => $sampai->format('Y-m-d'),
                'label'  => self::NAMA_BULAN[(int) $dari->format('n')] . '/' . $dari->format('y'),
            ];
        }
        return $out;
    }

    /**
     * Pcs terjual per SKU per bulan (urutan = bulan_penjualan()), dari cache bila masih segar.
     * Penjualan = pesanan COMPLETED selain "Paket Hilang" (status_label_mp), bulan menurut tanggal_pesan.
     *
     * @return array|null {bulan: [label], dihitung: 'Y-m-d H:i', data: {SKU_BESAR: [pcs, pcs, pcs]}};
     *                    null bila gagal dihitung (layar menampilkan strip, bukan angka 0).
     */
    public function penjualan()
    {
        $bulan  = $this->bulan_penjualan();
        $berkas = APPPATH . 'cache/penjualan_sku_' . substr($bulan[0]['dari'], 0, 7) . '.json';

        $cache = $this->baca_cache_penjualan($berkas);
        if ($cache && time() - filemtime($berkas) < self::CACHE_PENJUALAN_DETIK) {
            return $cache;
        }

        // Hanya satu request yang menghitung; yang lain menunggu lalu memakai hasilnya.
        $kunci = @fopen(APPPATH . 'cache/penjualan_sku.lock', 'c');
        if ($kunci) {
            @flock($kunci, LOCK_EX);
            clearstatcache();
            $baru = $this->baca_cache_penjualan($berkas);
            if ($baru && time() - filemtime($berkas) < self::CACHE_PENJUALAN_DETIK) {
                $cache = $baru;
                $hasil = $baru;
            }
        }

        if (!isset($hasil)) {
            $hasil = $this->hitung_penjualan($bulan);
            if ($hasil !== null) {
                $sementara = $berkas . '.tmp';
                if (@file_put_contents($sementara, json_encode($hasil)) !== false) {
                    @rename($sementara, $berkas);
                }
            }
        }

        if ($kunci) {
            @flock($kunci, LOCK_UN);
            @fclose($kunci);
        }

        // Gagal menghitung: pakai cache lama bila ada (jam hitungnya tetap tampil di layar).
        return $hasil !== null ? $hasil : $cache;
    }

    private function baca_cache_penjualan($berkas)
    {
        if (!is_file($berkas)) return null;
        $isi = json_decode((string) @file_get_contents($berkas), true);
        return isset($isi['bulan'], $isi['dihitung'], $isi['data']) ? $isi : null;
    }

    /**
     * Hitung dari prod, ditambah resi yang sudah dipindah ke arsip (purna, retensi 60 hari) dan tidak ada
     * lagi di prod. Nama database ditulis lengkap supaya hasilnya sama di Mode Arsip.
     */
    private function hitung_penjualan(array $bulan)
    {
        require_once APPPATH . 'config/secrets_load.php';
        $this->config->load('arsip', TRUE, TRUE);
        $prod  = (string) iresis_secret('db_database', 'iresis_prod');
        $arsip = (string) $this->config->item('db_arsip', 'arsip');

        $kolom = [];
        foreach ($bulan as $i => $b) {
            $kolom[] = "SUM(IF(p.tanggal_pesan >= '{$b['dari']}' AND p.tanggal_pesan < '{$b['sampai']}', d.jumlah, 0)) AS b$i";
        }
        $dari   = $bulan[count($bulan) - 1]['dari'];
        $sampai = $bulan[0]['sampai'];
        $pilih  = function ($db, $tambahan = '') use ($kolom, $dari, $sampai) {
            return 'SELECT d.sku, ' . implode(', ', $kolom) . "
                FROM `$db`.tblprintresi p JOIN `$db`.tbldetailprintresi d ON d.id_resi = p.id_printresi
                WHERE p.status_pesanan = 'COMPLETED'
                  AND (p.status_label_mp IS NULL OR p.status_label_mp <> 'Paket Hilang')
                  AND p.tanggal_pesan >= '$dari' AND p.tanggal_pesan < '$sampai' $tambahan
                GROUP BY d.sku";
        };

        $query = [$pilih($prod)];
        if ($arsip !== '' && $arsip !== $prod) {
            $query[] = $pilih($arsip, "AND NOT EXISTS (SELECT 1 FROM `$prod`.tblprintresi l WHERE l.noresi = p.noresi)");
        }

        set_time_limit(0);
        $debug_awal = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $data = [];
        foreach ($query as $sql) {
            $q = $this->db->query($sql);
            if ($q === FALSE) {
                $err = $this->db->error();
                log_message('error', 'Penjualan per SKU gagal dihitung: ' . ($err['message'] ?? '-'));
                $this->db->db_debug = $debug_awal;
                return null;
            }
            foreach ($q->result_array() as $r) {
                $sku = $this->kunci_sku($r['sku']);
                if (!isset($data[$sku])) $data[$sku] = array_fill(0, count($bulan), 0);
                foreach (array_keys($bulan) as $i) $data[$sku][$i] += (int) $r["b$i"];
            }
            $q->free_result();
        }
        $this->db->db_debug = $debug_awal;

        return ['bulan' => array_column($bulan, 'label'), 'dihitung' => date('Y-m-d H:i'), 'data' => $data];
    }

    /** Kunci pencocokan SKU antara tblsku dan detail resi (tanpa beda huruf besar-kecil / spasi tepi). */
    public function kunci_sku($sku)
    {
        return strtoupper(trim((string) $sku));
    }

    /** Pcs per bulan satu SKU dari hasil penjualan(); null per bulan bila data penjualan tidak tersedia. */
    public function penjualan_sku($penjualan, $sku)
    {
        if ($penjualan === null) return array_fill(0, self::JUMLAH_BULAN, null);
        return $penjualan['data'][$this->kunci_sku($sku)] ?? array_fill(0, self::JUMLAH_BULAN, 0);
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
