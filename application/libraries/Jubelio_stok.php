<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Klien baca-saja ke Jubelio core-api untuk menu Cek Top Level: stok live per gudang dan history transfer.
 *
 * Kredensial dari secrets.php: `jubelio_email`, `jubelio_password`; `jubelio_base` opsional (default
 * https://open.jubelio.com/core-api, dipakai juga untuk mengarahkan uji ke server tiruan).
 * Token hasil login disimpan di application/cache/jubelio_token.json dan dipakai ulang sampai hampir
 * kedaluwarsa; kalau Jubelio menjawab 401, login diulang satu kali.
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
    private $token_file;
    private $token = null;

    public function __construct()
    {
        $this->base = rtrim((string) iresis_secret('jubelio_base', self::BASE_DEFAULT), '/');
        $this->token_file = APPPATH . 'cache/jubelio_token.json';
    }

    /**
     * Stok live dan history transfer (2 tanggal terakhir) untuk satu SKU.
     *
     * @return array {sku, lokasi: [{id, nama, on_hand, on_order, reserved, available}], history: [{tanggal, diubah, lokasi, qty, sisa, keterangan}]}
     */
    public function cek($sku)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            throw new RuntimeException('SKU kosong.');
        }
        $stok = $this->ambil_stok($sku);
        return array(
            'sku'     => $stok['sku'],
            'lokasi'  => $stok['lokasi'],
            'history' => $this->ambil_history($stok['item_id']),
        );
    }

    // ------------------------------------------------------------ stok & history

    private function ambil_stok($sku)
    {
        $q = http_build_query(array(
            'page' => 1, 'page_size' => 25, 'sort_direction' => 'NONE', 'q' => $sku,
            'location_ids' => array_keys(self::$LOKASI),
        ));
        $j = $this->get('/inventory/v2/?' . $q);
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
    private function ambil_history($item_id)
    {
        $q = http_build_query(array(
            'page' => 1, 'page_size' => 50, 'sort_by' => 'transaction_date', 'sort_direction' => 'DESC',
            'itemId' => $item_id, 'source' => 'TRANSFER', 'q' => '',
        ));
        $j = $this->get('/inventory/activity/?' . $q);
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

    // ------------------------------------------------------------ HTTP & token

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
        if ($r['kode'] === 401 || $r['kode'] === 400) {
            throw new RuntimeException('Login Jubelio ditolak. Periksa jubelio_email dan jubelio_password di secrets.php.');
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
        if (!is_dir(dirname($this->token_file))) {
            @mkdir(dirname($this->token_file), 0775, true);
        }
        @file_put_contents($this->token_file, json_encode(array('token' => $token, 'exp' => $exp)), LOCK_EX);
    }

    private function hapus_token()
    {
        $this->token = null;
        @unlink($this->token_file);
    }

    private function http($metode, $url, $badan, $token)
    {
        $c = curl_init($url);
        $h = array('Accept: application/json', 'Origin: https://v2.jubelio.com', 'Referer: https://v2.jubelio.com/');
        if ($token) {
            $h[] = 'Authorization: ' . $token;
        }
        if ($badan !== null) {
            $h[] = 'Content-Type: application/json';
            curl_setopt($c, CURLOPT_POSTFIELDS, $badan);
        }
        curl_setopt_array($c, array(
            CURLOPT_CUSTOMREQUEST  => $metode,
            CURLOPT_HTTPHEADER     => $h,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) iresis',
        ));
        $isi = curl_exec($c);
        $kode = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $galat = curl_errno($c);
        curl_close($c);
        if ($isi === false || $galat) {
            throw new RuntimeException('Tidak bisa terhubung ke Jubelio. Periksa koneksi internet.');
        }
        return array('kode' => $kode, 'isi' => (string) $isi);
    }
}
