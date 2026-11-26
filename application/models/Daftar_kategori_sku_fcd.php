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
    /** Umur cache penjualan (detik). Lewat dari ini dianggap basi dan dihitung ulang. */
    const CACHE_PENJUALAN_DETIK = 1800;
    /**
     * Hitungan dipecah menjadi potongan rentang id_printresi yang dikerjakan beberapa koneksi sekaligus
     * (uji 26 Nov 2026: 1 koneksi 11,5 dtk, 6 koneksi 2,3 dtk, hasil identik). 24 potongan > 6 koneksi supaya
     * potongan yang padat tidak membuat koneksi lain menganggur, sekaligus jadi satuan progres.
     */
    const POTONGAN_PER_DATABASE = 24;
    const KONEKSI_PARALEL = 6;
    const BATAS_HITUNG_DETIK = 180;
    /** Status pesanan yang sudah final. Selain ini (SHIPPED, PROCESSING, dst.) angka bulannya masih bisa berubah. */
    const STATUS_FINAL = "'COMPLETED','RETURNED','CANCELED'";
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
     * Hasil hitungan tersimpan (berapa pun umurnya), TANPA menghitung. Layar memakai ini supaya tabel tidak
     * pernah menunggu hitungan; kesegarannya dilihat lewat info_penjualan().
     *
     * Penjualan = pesanan COMPLETED selain "Paket Hilang" (status_label_mp), bulan menurut tanggal_pesan.
     *
     * @return array|null {bulan: [label], dihitung: 'Y-m-d H:i', waktu: epoch, durasi: detik,
     *                    data: {SKU_BESAR: [pcs, pcs, pcs]}, belum: {SKU_BESAR: [pcs belum final, ...]}}
     */
    public function penjualan()
    {
        $berkas = $this->berkas_penjualan();
        if (!is_file($berkas)) return null;
        $isi = json_decode((string) @file_get_contents($berkas), true);
        return isset($isi['bulan'], $isi['dihitung'], $isi['waktu'], $isi['data'], $isi['belum']) ? $isi : null;
    }

    /** Ringkasan kesegaran cache untuk layar. umur = detik sejak dihitung. */
    public function info_penjualan($penjualan)
    {
        $umur = $penjualan === null ? null : max(0, time() - (int) $penjualan['waktu']);
        return [
            'ada'      => $penjualan !== null,
            'dihitung' => $penjualan['dihitung'] ?? null,
            'durasi'   => $penjualan['durasi'] ?? null,
            'umur'     => $umur,
            'masa'     => self::CACHE_PENJUALAN_DETIK,
            'basi'     => $umur === null || $umur >= self::CACHE_PENJUALAN_DETIK,
        ];
    }

    /**
     * Hitung ulang dan simpan ke cache. Hanya satu proses yang boleh menghitung; yang lain langsung mendapat
     * 'sibuk' (layar lalu memantau progres lewat status_hitung()).
     *
     * @return string 'selesai' | 'sibuk' | 'gagal'
     */
    public function hitung_ulang()
    {
        $kunci = @fopen($this->berkas_kunci(), 'c');
        if (!$kunci) {
            log_message('error', 'Penjualan per SKU: berkas kunci tidak bisa dibuka');
            return 'gagal';
        }
        if (!@flock($kunci, LOCK_EX | LOCK_NB)) {
            @fclose($kunci);
            return 'sibuk';
        }

        set_time_limit(0);
        $mulai = microtime(true);
        $this->tulis_progres('proses', 0, 0);
        try {
            $hasil = $this->hitung_penjualan($this->bulan_penjualan(), function ($selesai, $total) {
                $this->tulis_progres('proses', $selesai, $total);
            });
            $hasil['durasi'] = round(microtime(true) - $mulai, 1);
            $this->tulis_json($this->berkas_penjualan(), $hasil);
            $this->tulis_progres('selesai', 1, 1);
            $status = 'selesai';
        } catch (Throwable $e) {
            log_message('error', 'Penjualan per SKU gagal dihitung: ' . $e->getMessage());
            $this->tulis_progres('gagal', 0, 0);
            $status = 'gagal';
        }
        @flock($kunci, LOCK_UN);
        @fclose($kunci);
        return $status;
    }

    /** Progres hitungan yang sedang / terakhir berjalan: {status: diam|proses|selesai|gagal, selesai, total}. */
    public function status_hitung()
    {
        $berkas = $this->berkas_progres();
        $isi = is_file($berkas) ? json_decode((string) @file_get_contents($berkas), true) : null;
        if (!isset($isi['status'])) return ['status' => 'diam', 'selesai' => 0, 'total' => 0];

        // "proses" tapi kuncinya bebas = proses PHP-nya mati di tengah jalan.
        if ($isi['status'] === 'proses' && ($kunci = @fopen($this->berkas_kunci(), 'c'))) {
            if (@flock($kunci, LOCK_EX | LOCK_NB)) {
                $ulang = json_decode((string) @file_get_contents($berkas), true);
                if (($ulang['status'] ?? '') === 'proses') $isi['status'] = 'gagal';
                else $isi = $ulang ?: $isi;
                @flock($kunci, LOCK_UN);
            }
            @fclose($kunci);
        }
        return $isi;
    }

    private function berkas_penjualan()
    {
        return APPPATH . 'cache/penjualan_sku_' . substr($this->bulan_penjualan()[0]['dari'], 0, 7) . '.json';
    }

    private function berkas_progres()
    {
        return APPPATH . 'cache/penjualan_sku_progres.json';
    }

    private function berkas_kunci()
    {
        return APPPATH . 'cache/penjualan_sku.lock';
    }

    private function tulis_progres($status, $selesai, $total)
    {
        $this->tulis_json($this->berkas_progres(), ['status' => $status, 'selesai' => $selesai, 'total' => $total]);
    }

    /** Tulis lewat berkas sementara + rename supaya pembaca tidak pernah mendapat JSON setengah jadi. */
    private function tulis_json($berkas, array $isi)
    {
        $sementara = $berkas . '.tmp';
        if (@file_put_contents($sementara, json_encode($isi)) === false) {
            throw new RuntimeException('Cache tidak bisa ditulis: ' . $berkas);
        }
        // Di Windows rename bisa ditolak sesaat bila berkas tujuan sedang dibaca.
        for ($coba = 0; $coba < 20; $coba++) {
            if (@rename($sementara, $berkas)) return;
            usleep(25000);
        }
        throw new RuntimeException('Cache tidak bisa diganti: ' . $berkas);
    }

    /**
     * Daftar query potongan: [jenis_kolom, sql]. Prod: terjual dan belum final sekali jalan. Arsip: hanya resi
     * terjual yang sudah dipindah (purna, retensi 60 hari) dan tidak ada lagi di prod; pesanan belum final
     * tidak dicari di arsip karena baris yang tinggal di sana tidak pernah diperbarui lagi. Nama database
     * ditulis lengkap supaya hasilnya sama di Mode Arsip.
     */
    private function susun_potongan(array $bulan)
    {
        require_once APPPATH . 'config/secrets_load.php';
        $this->config->load('arsip', TRUE, TRUE);
        $prod  = (string) iresis_secret('db_database', 'iresis_prod');
        $arsip = (string) $this->config->item('db_arsip', 'arsip');

        $final   = self::STATUS_FINAL;
        $terjual = "p.status_pesanan = 'COMPLETED' AND (p.status_label_mp IS NULL OR p.status_label_mp <> 'Paket Hilang')";
        $belum   = "(p.status_pesanan IS NULL OR p.status_pesanan NOT IN ($final))";
        // Resi lama yang diganti (mis. nomor resi Lazada berubah) tertinggal SHIPPED selamanya. Pesanan + SKU
        // yang sudah punya salinan berstatus final tidak dihitung belum final.
        $tanpa_salinan_final = "NOT EXISTS (SELECT 1 FROM `$prod`.tbldetailprintresi d2
            JOIN `$prod`.tblprintresi p2 ON p2.id_printresi = d2.id_resi
            WHERE d2.no_pesanan = d.no_pesanan AND d2.sku = d.sku AND p2.status_pesanan IN ($final))";

        $kolom_t = $kolom_b = [];
        foreach ($bulan as $i => $b) {
            $di_bulan  = "p.tanggal_pesan >= '{$b['dari']}' AND p.tanggal_pesan < '{$b['sampai']}'";
            $kolom_t[] = "SUM(IF($di_bulan AND $terjual, d.jumlah, 0)) AS t$i";
            $kolom_b[] = "SUM(IF($di_bulan AND $belum AND $tanpa_salinan_final, d.jumlah, 0)) AS b$i";
        }
        $rentang = "p.tanggal_pesan >= '{$bulan[count($bulan) - 1]['dari']}' AND p.tanggal_pesan < '{$bulan[0]['sampai']}'";

        $sumber = [[$prod, array_merge($kolom_t, $kolom_b), "$rentang AND (($terjual) OR $belum)"]];
        if ($arsip !== '' && $arsip !== $prod) {
            $sumber[] = [$arsip, $kolom_t, "$rentang AND $terjual
                AND NOT EXISTS (SELECT 1 FROM `$prod`.tblprintresi l WHERE l.noresi = p.noresi)"];
        }

        $potongan = [];
        foreach ($sumber as list($db, $kolom, $syarat)) {
            $q = $this->db->query("SELECT MIN(id_printresi) AS a, MAX(id_printresi) AS z FROM `$db`.tblprintresi");
            if ($q === FALSE) throw new RuntimeException($this->db->error()['message'] ?? "gagal membaca $db");
            $r = $q->row();
            if ($r->a === null) continue;
            $lebar = max(1, (int) ceil(($r->z - $r->a + 1) / self::POTONGAN_PER_DATABASE));
            for ($dari = (int) $r->a; $dari <= (int) $r->z; $dari += $lebar) {
                // FORCE INDEX (PRIMARY) + STRAIGHT_JOIN: tiap potongan hanya menyisir rentang id-nya sendiri.
                $potongan[] = 'SELECT d.sku, ' . implode(', ', $kolom) . "
                    FROM `$db`.tblprintresi p FORCE INDEX (PRIMARY)
                    STRAIGHT_JOIN `$db`.tbldetailprintresi d ON d.id_resi = p.id_printresi
                    WHERE p.id_printresi >= $dari AND p.id_printresi < " . ($dari + $lebar) . " AND $syarat
                    GROUP BY d.sku";
            }
        }
        return $potongan;
    }

    /** @param callable $lapor fn(int $selesai, int $total), dipanggil tiap satu potongan rampung. */
    private function hitung_penjualan(array $bulan, callable $lapor)
    {
        $debug_awal = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $potongan = $this->susun_potongan($bulan);
            $total    = count($potongan);
            $hasil    = ['data' => [], 'belum' => []];
            $selesai  = 0;
            $olah = function (array $rows) use (&$hasil, &$selesai, $total, $bulan, $lapor) {
                $this->gabungkan($hasil, $rows, count($bulan));
                $lapor(++$selesai, $total);
            };
            $lapor(0, $total);

            $koneksi = $this->koneksi_paralel();
            if ($koneksi) {
                $this->jalankan_paralel($koneksi, $potongan, $olah);
            } else {
                // Cadangan bila koneksi tambahan tidak tersedia: satu per satu lewat koneksi aplikasi.
                foreach ($potongan as $sql) {
                    $q = $this->db->query($sql);
                    if ($q === FALSE) throw new RuntimeException($this->db->error()['message'] ?? 'query gagal');
                    $olah($q->result_array());
                    $q->free_result();
                }
            }
        } finally {
            $this->db->db_debug = $debug_awal;
        }

        return ['bulan' => array_column($bulan, 'label'), 'dihitung' => date('Y-m-d H:i'), 'waktu' => time()] + $hasil;
    }

    private function gabungkan(array &$hasil, array $rows, $jumlah_bulan)
    {
        foreach ($rows as $r) {
            $sku = $this->kunci_sku($r['sku']);
            foreach (['data' => 't', 'belum' => 'b'] as $jenis => $awalan) {
                for ($i = 0; $i < $jumlah_bulan; $i++) {
                    $pcs = (int) ($r[$awalan . $i] ?? 0);
                    if ($pcs === 0) continue;
                    if (!isset($hasil[$jenis][$sku])) $hasil[$jenis][$sku] = array_fill(0, $jumlah_bulan, 0);
                    $hasil[$jenis][$sku][$i] += $pcs;
                }
            }
        }
    }

    /** Koneksi baca-saja tambahan untuk query paralel; array kosong bila tidak bisa dibuat. */
    private function koneksi_paralel()
    {
        $koneksi = [];
        if (!function_exists('mysqli_poll') || $this->db->dbdriver !== 'mysqli') return $koneksi;
        for ($i = 0; $i < self::KONEKSI_PARALEL; $i++) {
            try {
                $c = mysqli_init();
                if (!@$c->real_connect($this->db->hostname, $this->db->username, $this->db->password, $this->db->database, !empty($this->db->port) ? (int) $this->db->port : null)) {
                    break;
                }
                $c->set_charset($this->db->char_set);
                $c->query('SET SESSION TRANSACTION READ ONLY');
                $koneksi[] = $c;
            } catch (Throwable $e) {
                log_message('error', 'Penjualan per SKU: koneksi paralel gagal: ' . $e->getMessage());
                break;
            }
        }
        return $koneksi;
    }

    private function jalankan_paralel(array $koneksi, array $antrean, callable $olah)
    {
        $bebas = $koneksi;
        $sibuk = [];
        $batas = microtime(true) + self::BATAS_HITUNG_DETIK;
        try {
            while ($antrean || $sibuk) {
                while ($antrean && $bebas) {
                    $c = array_pop($bebas);
                    if ($c->query(array_shift($antrean), MYSQLI_ASYNC) === FALSE) throw new RuntimeException($c->error);
                    $sibuk[$c->thread_id] = $c;
                }
                $baca = $galat = $tolak = array_values($sibuk);
                $siap = mysqli_poll($baca, $galat, $tolak, 0, 100000);
                if (microtime(true) > $batas) throw new RuntimeException('melewati ' . self::BATAS_HITUNG_DETIK . ' detik');
                if (!$siap) continue;
                if ($galat || $tolak) throw new RuntimeException('koneksi paralel bermasalah');
                foreach ($baca as $c) {
                    $res = $c->reap_async_query();
                    if (!$res) throw new RuntimeException($c->error);
                    $olah($res->fetch_all(MYSQLI_ASSOC));
                    $res->free();
                    unset($sibuk[$c->thread_id]);
                    $bebas[] = $c;
                }
            }
        } finally {
            // Koneksi yang masih menjalankan query dihentikan di server supaya tidak membebani setelah gagal.
            foreach ($sibuk as $c) {
                $this->db->query('KILL QUERY ' . (int) $c->thread_id);
            }
            foreach ($koneksi as $c) {
                @$c->close();
            }
        }
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

    /** Pcs belum final per bulan satu SKU (> 0 berarti angka bulan itu masih bisa berubah). */
    public function belum_final_sku($penjualan, $sku)
    {
        return $penjualan['belum'][$this->kunci_sku($sku)] ?? array_fill(0, self::JUMLAH_BULAN, 0);
    }

    /**
     * Jumlah SKU yang angka penjualannya belum final per bulan, dari sekumpulan SKU (angka dalam kurung di
     * judul kolom; sama dengan banyaknya angka orange di kolom itu). null bila data penjualan tidak tersedia.
     */
    public function jumlah_sku_belum_final($penjualan, array $rows)
    {
        if ($penjualan === null) return null;
        $total = array_fill(0, self::JUMLAH_BULAN, 0);
        foreach ($rows as $r) {
            foreach ($this->belum_final_sku($penjualan, $r['id_sku']) as $i => $pcs) {
                if ($pcs > 0) $total[$i]++;
            }
        }
        return $total;
    }

    /**
     * Urutkan baris SKU menurut pcs satu bulan. $urut = indeks bulan ('0' = bulan berjalan); nilai lain atau
     * data penjualan tidak tersedia = urutan asal (SKU) dipertahankan. Angka sama diurutkan menurut SKU.
     */
    private function urutkan(array $rows, $penjualan, $urut, $arah)
    {
        if ($penjualan === null || !ctype_digit((string) $urut) || (int) $urut >= self::JUMLAH_BULAN) {
            return $rows;
        }
        $i = (int) $urut;
        $nilai = [];
        foreach ($rows as $k => $r) {
            $nilai[$k] = $this->penjualan_sku($penjualan, $r['id_sku'])[$i];
        }
        $tanda = $arah === 'asc' ? 1 : -1;
        $kunci = array_keys($rows);
        usort($kunci, function ($a, $b) use ($nilai, $tanda) {
            return $nilai[$a] === $nilai[$b] ? $a <=> $b : $tanda * ($nilai[$a] <=> $nilai[$b]);
        });
        return array_map(function ($k) use ($rows) {
            return $rows[$k];
        }, $kunci);
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

    /** Semua SKU yang cocok (tanpa paging) untuk ekspor Excel; urut SKU, atau menurut penjualan bila diminta. */
    public function semua($q, array $kategori, $penjualan = null, $urut = '', $arah = 'desc')
    {
        list($sql_where, $bind) = $this->bangun_where($q, $kategori);
        $rows = $this->db->query(
            "SELECT id_sku, kategori_sku, no_rak FROM tblsku" . $sql_where . " ORDER BY id_sku ASC", $bind
        )->result_array();
        return $this->urutkan($rows, $penjualan, $urut, $arah);
    }

    /**
     * Satu halaman SKU. $kategori = array nilai kategori (kosong = semua; BELUM_ADA = tanpa kategori),
     * $q = kata kunci untuk SKU atau no rak (potongan teks, tidak peka huruf besar-kecil).
     *
     * @return array {total, baris: [{id_sku, kategori_sku, no_rak, foto, foto_lokal}]}
     */
    public function halaman($q, array $kategori, $halaman, $ukuran, $penjualan = null, $urut = '', $arah = 'desc')
    {
        list($sql_where, $bind) = $this->bangun_where($q, $kategori);

        $total = (int) $this->db->query("SELECT COUNT(*) AS n FROM tblsku" . $sql_where, $bind)->row()->n;
        $ukuran  = in_array($ukuran, self::UKURAN_BOLEH, TRUE) ? $ukuran : 50;
        $maks    = max(1, (int) ceil($total / $ukuran));
        $halaman = min(max(1, (int) $halaman), $maks);

        $awal   = (int) (($halaman - 1) * $ukuran);
        $dasar  = "SELECT id_sku, kategori_sku, no_rak, link_foto, foto_lokal FROM tblsku" . $sql_where . " ORDER BY id_sku ASC";
        if ($penjualan !== null && ctype_digit((string) $urut)) {
            // Angka penjualan ada di cache, bukan di SQL: urutkan semua SKU yang cocok, baru ambil satu halaman.
            $rows = array_slice($this->urutkan($this->db->query($dasar, $bind)->result_array(), $penjualan, $urut, $arah), $awal, $ukuran);
        } else {
            $rows = $this->db->query($dasar . " LIMIT " . (int) $ukuran . " OFFSET " . $awal, $bind)->result_array();
        }
        foreach ($rows as &$r) {
            // Foto SKU untuk klien: URL asli + salinan lokal (assets/js/foto_sku.js memilih yang bisa dimuat).
            $r['foto']       = trim((string) $r['link_foto']);
            $r['foto_lokal'] = foto_sku_lokal_url($r['link_foto'], $r['foto_lokal']);
            unset($r['link_foto']);
        }
        unset($r);

        // Judul kolom menjumlah SEMUA SKU yang cocok dengan pencarian / filter, bukan hanya halaman tampil.
        $sku_belum = $penjualan === null ? null : $this->jumlah_sku_belum_final(
            $penjualan, $this->db->query("SELECT id_sku FROM tblsku" . $sql_where, $bind)->result_array()
        );

        return ['sku_belum' => $sku_belum, 'total' => $total, 'halaman' => $halaman, 'total_halaman' => $maks, 'ukuran' => $ukuran, 'baris' => $rows];
    }
}
