<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Klien baca-saja ke Jubelio core-api untuk menu Cek Top Level: stok live per gudang dan history transfer.
 *
 * Kredensial dari secrets.php: `jubelio_email`, `jubelio_password`; `jubelio_base` opsional (default
 * https://open.jubelio.com/core-api, dipakai juga untuk mengarahkan uji ke server tiruan).
 * Token hasil login disimpan di application/cache/jubelio/token.json dan dipakai ulang sampai hampir
 * kedaluwarsa; kalau Jubelio menjawab 401, login diulang satu kali.
 *
 * Kecepatan (hasil ukur 4 Nov 2026: jaringan <50 ms; server Jubelio ~0,9 dtk untuk stok, ~1 dtk untuk history, tapi
 * history SKU berstok besar seperti AKS27-RDM sampai ~5 dtk dan tidak bisa dipercepat lewat parameter):
 *  - stok() dan history() dipisah. Browser memanggil keduanya bersamaan; kartu stok tampil ~1 dtk tanpa menunggu history;
 *  - item_id SKU (stabil) disimpan di application/cache/jubelio/item_id.json, jadi history() tidak perlu mencari ulang;
 *  - hasil per SKU dan per bagian di-cache singkat (`jubelio_cache_detik` di secrets.php, default 20 dtk, 0 = mati);
 *    tombol Muat ulang memakai $segar = true dan melewati cache;
 *  - handshake TLS: dengan bundel CA bawaan XAMPP (135 sertifikat) OpenSSL 3 memakan ~450 ms di SETIAP koneksi baru
 *    (PHP tidak bisa memakai ulang koneksi antar request); dengan jubelio_ca.pem berisi hanya ISRG Root X1/X2 (akar
 *    Let's Encrypt, penerbit sertifikat *.jubelio.com) turun jadi ~50 ms. Verifikasi sertifikat tetap penuh. Bila Jubelio
 *    suatu hari pindah penerbit sertifikat dan verifikasi gagal, otomatis dicoba ulang dengan bundel bawaan (dan dicatat
 *    di log), jadi fitur tidak mati; perbarui jubelio_ca.pem setelah itu.
 *  - timeout 4 dtk sambung / 15 dtk total supaya Jubelio yang macet tidak menahan proses PHP lama.
 *
 * Semua kegagalan dilempar sebagai RuntimeException berpesan Indonesia yang aman ditampilkan ke user
 * (tidak memuat kredensial maupun token).
 */
class Jubelio_stok
{
    const BASE_DEFAULT = 'https://open.jubelio.com/core-api';
    const WIB = 25200; // UTC+7 dalam detik

    /** Urutan gudang yang ditampilkan: id lokasi Jubelio => nama. */
    private static $LOKASI = array(
        -1 => 'Display Barang (F19&F19A)',
        1  => 'Gudang Utama (F16)',
        0  => 'Transit',
    );

    private $base;
    private $dir;
    private $token_file;
    private $ttl;
    private $token = null;
    private $ca_mini = true; // false setelah verifikasi dengan bundel mini gagal sekali

    public function __construct()
    {
        $this->base = rtrim((string) iresis_secret('jubelio_base', self::BASE_DEFAULT), '/');
        $this->dir = APPPATH . 'cache/jubelio/';
        $this->token_file = $this->dir . 'token.json';
        $this->ttl = max(0, (int) iresis_secret('jubelio_cache_detik', 20));
    }

    /**
     * Stok live per gudang untuk satu SKU.
     *
     * @param bool $segar true = lewati cache hasil (tombol Muat ulang)
     * @return array {sku, item_id, lokasi: [{id, nama, on_hand, on_order, reserved, available}], waktu}
     */
    public function stok($sku, $segar = false)
    {
        $sku = $this->bersih($sku);
        if (!$segar && ($hit = $this->cache_baca('stok', $sku)) !== null) {
            return $hit;
        }
        $s = $this->baca_stok($this->get($this->path_stok($sku)), $sku);
        $this->item_id_simpan($sku, $s['item_id']);
        $hasil = array('sku' => $s['sku'], 'item_id' => $s['item_id'], 'lokasi' => $s['lokasi'], 'waktu' => date('H.i.s'));
        $this->cache_tulis('stok', $sku, $hasil);
        return $hasil;
    }

    /**
     * History transfer (2 tanggal terakhir) untuk satu SKU. item_id diambil dari cache; bila belum ada, dicari lewat stok().
     *
     * @return array {item_id, history: [{tanggal, diubah, lokasi, qty, sisa, keterangan}]}
     */
    public function history($sku, $segar = false)
    {
        $sku = $this->bersih($sku);
        if (!$segar && ($hit = $this->cache_baca('hist', $sku)) !== null) {
            return $hit;
        }
        $item_id = $this->item_id_cache($sku);
        if (!$item_id) {
            $item_id = $this->stok($sku, true)['item_id'];
        }
        $hasil = array('item_id' => $item_id, 'history' => $this->baca_history($this->get($this->path_history($item_id))));
        $this->cache_tulis('hist', $sku, $hasil);
        return $hasil;
    }

    private function bersih($sku)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            throw new RuntimeException('SKU kosong.');
        }
        return $sku;
    }

    // ------------------------------------------------------------ stok & history

    private function path_stok($sku)
    {
        return '/inventory/v2/?' . http_build_query(array(
            'page' => 1, 'page_size' => 25, 'sort_direction' => 'NONE', 'q' => $sku,
            'location_ids' => array_keys(self::$LOKASI),
        ));
    }

    private function path_history($item_id)
    {
        return '/inventory/activity/?' . http_build_query(array(
            'page' => 1, 'page_size' => 50, 'sort_by' => 'transaction_date', 'sort_direction' => 'DESC',
            'itemId' => $item_id, 'source' => 'TRANSFER', 'q' => '',
        ));
    }

    private function baca_stok($j, $sku)
    {
        $item = null;
        foreach ((isset($j['data']) && is_array($j['data'])) ? $j['data'] : array() as $d) {
            if (isset($d['item_code']) && strcasecmp(trim($d['item_code']), $sku) === 0) {
                $item = $d;
                break;
            }
        }
        if (!$item) {
            throw new RuntimeException('SKU ' . $sku . ' tidak ditemukan di Jubelio.');
        }

        $per_lokasi = array();
        foreach ((isset($item['location_stocks']) && is_array($item['location_stocks'])) ? $item['location_stocks'] : array() as $ls) {
            $per_lokasi[(int) $ls['location_id']] = $ls;
        }
        $lokasi = array();
        foreach (self::$LOKASI as $id => $nama) {
            $ls = isset($per_lokasi[$id]) ? $per_lokasi[$id] : array();
            $lokasi[] = array(
                'id'        => $id,
                'nama'      => $nama,
                'on_hand'   => (int) (isset($ls['on_hand']) ? $ls['on_hand'] : 0),
                'on_order'  => (int) (isset($ls['on_order']) ? $ls['on_order'] : 0),
                'reserved'  => (int) (isset($ls['reserved']) ? $ls['reserved'] : 0),
                'available' => (int) (isset($ls['available']) ? $ls['available'] : 0),
            );
        }
        return array('item_id' => (int) $item['item_id'], 'sku' => $item['item_code'], 'lokasi' => $lokasi);
    }

    /** Transaksi sumber TRANSFER halaman 1, dipotong ke 2 tanggal (WIB) terbaru. */
    private function baca_history($j)
    {
        $out = array();
        $tanggal = array();
        foreach ((isset($j['activities']) && is_array($j['activities'])) ? $j['activities'] : array() as $a) {
            if (isset($a['source']) && strtoupper($a['source']) !== 'TRANSFER') {
                continue; // jaga-jaga bila filter sumber diabaikan Jubelio
            }
            $ts = strtotime(isset($a['transaction_date']) ? $a['transaction_date'] : '');
            if (!$ts) {
                continue;
            }
            $hari = gmdate('Y-m-d', $ts + self::WIB);
            if (!in_array($hari, $tanggal, true)) {
                if (count($tanggal) >= 2) {
                    break; // urutan DESC: sisanya lebih lama
                }
                $tanggal[] = $hari;
            }
            $dibuat = !empty($a['created_date']) ? strtotime($a['created_date']) : 0;
            $out[] = array(
                'tanggal'    => $this->tgl($ts, false),
                'diubah'     => $dibuat ? $this->tgl($dibuat, true) : '',
                'lokasi'     => isset($a['location_name']) ? (string) $a['location_name'] : '',
                'qty'        => (int) round((float) (isset($a['qty']) ? $a['qty'] : 0)),
                'sisa'       => (int) round((float) (isset($a['balance']) ? $a['balance'] : 0)),
                'keterangan' => isset($a['description']) ? trim((string) $a['description']) : '',
            );
        }
        return $out;
    }

    private function tgl($ts, $dengan_jam)
    {
        $bln = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
        $w = $ts + self::WIB;
        $s = gmdate('d', $w) . ' ' . $bln[(int) gmdate('n', $w)];
        return $dengan_jam ? $s . ' ' . gmdate('H.i', $w) : $s . ' ' . gmdate('Y', $w);
    }

    // ------------------------------------------------------------ cache (berkas kecil di application/cache/jubelio/)

    private function siapkan_dir()
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    private function berkas_hasil($bagian, $sku)
    {
        return $this->dir . 'hasil_' . $bagian . '_' . md5(strtoupper($sku)) . '.json';
    }

    private function cache_baca($bagian, $sku)
    {
        if ($this->ttl <= 0) {
            return null;
        }
        $f = @file_get_contents($this->berkas_hasil($bagian, $sku));
        $c = $f ? json_decode($f, true) : null;
        return (is_array($c) && isset($c['exp'], $c['data']) && $c['exp'] > time()) ? $c['data'] : null;
    }

    private function cache_tulis($bagian, $sku, $hasil)
    {
        if ($this->ttl <= 0) {
            return;
        }
        $this->siapkan_dir();
        @file_put_contents($this->berkas_hasil($bagian, $sku), json_encode(array('exp' => time() + $this->ttl, 'data' => $hasil)), LOCK_EX);
        if (mt_rand(1, 40) === 1) { // sesekali buang berkas hasil yang sudah basi supaya folder tidak menumpuk
            foreach ((array) glob($this->dir . 'hasil_*.json') as $b) {
                if (@filemtime($b) < time() - 600) {
                    @unlink($b);
                }
            }
        }
    }

    private function item_id_cache($sku)
    {
        $f = @file_get_contents($this->dir . 'item_id.json');
        $m = $f ? json_decode($f, true) : null;
        $k = strtoupper($sku);
        return (is_array($m) && !empty($m[$k])) ? (int) $m[$k] : 0;
    }

    private function item_id_simpan($sku, $item_id)
    {
        $this->siapkan_dir();
        $h = @fopen($this->dir . 'item_id.json', 'c+');
        if (!$h) {
            return;
        }
        if (flock($h, LOCK_EX)) {
            $m = json_decode((string) stream_get_contents($h), true);
            $m = is_array($m) ? $m : array();
            $m[strtoupper($sku)] = (int) $item_id;
            ftruncate($h, 0);
            rewind($h);
            fwrite($h, json_encode($m));
            fflush($h);
            flock($h, LOCK_UN);
        }
        fclose($h);
    }

    // ------------------------------------------------------------ HTTP & token

    /** Satu GET ber-token. Bila dijawab 401, token dibuang dan permintaan diulang satu kali dengan login baru. */
    private function get($path, $ulang = true)
    {
        $r = $this->http('GET', $this->base . $path, null, $this->token());
        if ($r['kode'] === 401 && $ulang) {
            $this->hapus_token();
            return $this->get($path, false);
        }
        return $this->hasil($r);
    }

    private function hasil($r)
    {
        if ($r['kode'] === 429) {
            throw new RuntimeException('Jubelio sedang membatasi permintaan. Tunggu beberapa detik, lalu coba lagi.');
        }
        if ($r['kode'] < 200 || $r['kode'] >= 300) {
            throw new RuntimeException('Jubelio menjawab galat ' . $r['kode'] . '.');
        }
        $j = json_decode($r['isi'], true);
        if (!is_array($j)) {
            throw new RuntimeException('Jawaban Jubelio tidak bisa dibaca.');
        }
        return $j;
    }

    private function token()
    {
        if ($this->token !== null) {
            return $this->token;
        }
        $f = @file_get_contents($this->token_file);
        $t = $f ? json_decode($f, true) : null;
        if (is_array($t) && !empty($t['token']) && !empty($t['exp']) && $t['exp'] > time() + 300) {
            return $this->token = $t['token'];
        }
        return $this->login();
    }

    private function login()
    {
        $email = (string) iresis_secret('jubelio_email', '');
        $sandi = (string) iresis_secret('jubelio_password', '');
        if ($email === '' || $sandi === '') {
            throw new RuntimeException('Akun Jubelio belum diisi di server (jubelio_email dan jubelio_password di secrets.php).');
        }
        $r = $this->http('POST', $this->base . '/login', json_encode(array('email' => $email, 'password' => $sandi)), null);
        if ($r['kode'] >= 400 && $r['kode'] !== 429) {
            // Jubelio menjawab 500 (bukan 401) untuk password salah, jadi semua galat selain 429 dianggap login ditolak.
            throw new RuntimeException('Login Jubelio ditolak (kode ' . $r['kode'] . '). Periksa jubelio_email dan jubelio_password di secrets.php.');
        }
        $j = $this->hasil($r);
        if (empty($j['token'])) {
            throw new RuntimeException('Login Jubelio tidak mengembalikan token.');
        }
        $this->token = (string) $j['token'];
        $this->simpan_token($this->token);
        return $this->token;
    }

    /** Waktu kedaluwarsa dari klaim exp pada JWT; bila tidak terbaca, anggap 1 jam. */
    private function simpan_token($token)
    {
        $exp = time() + 3600;
        $bagian = explode('.', $token);
        if (count($bagian) === 3) {
            $p = json_decode(base64_decode(strtr($bagian[1], '-_', '+/')), true);
            if (is_array($p) && !empty($p['exp'])) {
                $exp = (int) $p['exp'];
            }
        }
        $this->siapkan_dir();
        @file_put_contents($this->token_file, json_encode(array('token' => $token, 'exp' => $exp)), LOCK_EX);
    }

    private function hapus_token()
    {
        $this->token = null;
        @unlink($this->token_file);
    }

    private function opsi($metode, $badan, $token)
    {
        $h = array('Accept: application/json', 'Origin: https://v2.jubelio.com', 'Referer: https://v2.jubelio.com/');
        if ($token) {
            $h[] = 'Authorization: ' . $token;
        }
        $o = array(
            CURLOPT_CUSTOMREQUEST  => $metode,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) iresis',
        );
        if ($badan !== null) {
            $h[] = 'Content-Type: application/json';
            $o[CURLOPT_POSTFIELDS] = $badan;
        }
        $o[CURLOPT_HTTPHEADER] = $h;
        $ca = __DIR__ . '/jubelio_ca.pem';
        if ($this->ca_mini && is_file($ca)) {
            $o[CURLOPT_CAINFO] = $ca;
        }
        return $o;
    }

    private function http($metode, $url, $badan, $token)
    {
        $c = curl_init($url);
        curl_setopt_array($c, $this->opsi($metode, $badan, $token));
        $isi = curl_exec($c);
        $kode = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $galat = curl_errno($c);
        curl_close($c);
        // 60/77 = sertifikat tidak dikenali oleh bundel CA yang dipakai: coba sekali lagi dengan bundel bawaan.
        if (($galat === 60 || $galat === 77) && $this->ca_mini) {
            $this->ca_mini = false;
            function_exists('log_message') && log_message('error', 'Jubelio_stok: verifikasi TLS gagal dengan jubelio_ca.pem (errno ' . $galat . '); pakai bundel bawaan. Perbarui jubelio_ca.pem.');
            return $this->http($metode, $url, $badan, $token);
        }
        if ($isi === false || $galat) {
            throw new RuntimeException('Tidak bisa terhubung ke Jubelio. Periksa koneksi internet.');
        }
        return array('kode' => $kode, 'isi' => (string) $isi);
    }
}
