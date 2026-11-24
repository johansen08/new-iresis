<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unduh gambar dari URL luar dan simpan di server, supaya foto tetap tampil
 * saat aplikasi berjalan di jaringan lokal tanpa internet.
 *
 * Dipakai oleh form yang menerima "link foto" (User, Purchasing). Internet
 * hanya dibutuhkan SAAT link itu disimpan; setelah itu yang dipakai salinan lokal.
 */

/** Ubah link Google Drive (file/d/ID atau ?id=ID) menjadi URL thumbnail yang bisa diunduh. */
function foto_lokal_normalisasi_url($url)
{
    $url = trim((string) $url);
    if (stripos($url, 'drive.google.com') === FALSE) {
        return $url;
    }
    $id = '';
    if (preg_match('#/file/d/([^/?]+)#', $url, $m) || preg_match('/[?&]id=([^&]+)/', $url, $m)) {
        $id = $m[1];
    }
    if ($id === '') {
        return $url;
    }
    $rk = preg_match('/[?&]resourcekey=([^&]+)/', $url, $m) ? '&resourcekey=' . $m[1] : '';
    return 'https://drive.google.com/thumbnail?id=' . $id . $rk . '&sz=w1000';
}

/** TRUE bila $url adalah http(s) ke host publik (menolak localhost / IP LAN, mencegah SSRF). */
function foto_lokal_url_publik($url)
{
    $p = parse_url($url);
    if (empty($p['scheme']) || !in_array(strtolower($p['scheme']), array('http', 'https'), TRUE) || empty($p['host'])) {
        return FALSE;
    }
    $ip = gethostbyname($p['host']);
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        return FALSE;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE;
}

/** Huruf pertama dari nama depan (fallback username) untuk avatar bila foto tidak tersedia. */
function avatar_inisial($user)
{
    $nama = trim((string) (isset($user['name']) ? $user['name'] : ''));
    if ($nama === '') {
        $nama = trim((string) (isset($user['username']) ? $user['username'] : ''));
    }
    return $nama === '' ? '?' : mb_strtoupper(mb_substr($nama, 0, 1, 'UTF-8'), 'UTF-8');
}

/** src avatar: path lokal -> base_url; kosong -> 'data:,' (sengaja gagal agar onerror menampilkan huruf). */
function avatar_src($foto)
{
    $foto = trim((string) $foto);
    if ($foto === '') {
        return 'data:,';
    }
    return preg_match('#^https?://#i', $foto) ? $foto : base_url($foto);
}

/** TRUE bila $s tampak seperti URL http(s) (bukan path lokal). */
function foto_lokal_adalah_url($s)
{
    return (bool) preg_match('#^https?://#i', trim((string) $s));
}

/**
 * Unduh gambar dan simpan ke FCPATH.$dir_rel (mis. 'assets/img/users/').
 * Kembalian: path relatif ke root aplikasi (mis. 'assets/img/users/user_x.jpg'),
 * atau '' bila gagal (offline, bukan gambar, terlalu besar, host non-publik).
 */
function foto_lokal_unduh($url, $dir_rel, $prefix, $maks_byte = 5242880)
{
    $url = foto_lokal_normalisasi_url($url);
    if ( ! foto_lokal_url_publik($url)) {
        return '';
    }

    $c = curl_init($url);
    curl_setopt_array($c, array(
        CURLOPT_RETURNTRANSFER  => TRUE,
        CURLOPT_FOLLOWLOCATION  => TRUE,
        CURLOPT_MAXREDIRS       => 3,
        CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT  => 5,
        CURLOPT_TIMEOUT         => 15,
        CURLOPT_USERAGENT       => 'IRESIS-foto-lokal/1.0',
    ));
    $isi  = curl_exec($c);
    $kode = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    if ($isi === FALSE || $kode !== 200 || $isi === '' || strlen($isi) > $maks_byte) {
        return '';
    }

    $info = @getimagesizefromstring($isi);
    $ekstensi = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp');
    if ($info === FALSE || ! isset($ekstensi[$info['mime']])) {
        return '';
    }

    $dir_rel = rtrim($dir_rel, '/') . '/';
    if ( ! is_dir(FCPATH . $dir_rel) && ! @mkdir(FCPATH . $dir_rel, 0777, TRUE)) {
        return '';
    }
    $nama = $prefix . '_' . time() . '_' . substr(md5($url), 0, 8) . '.' . $ekstensi[$info['mime']];
    if (@file_put_contents(FCPATH . $dir_rel . $nama, $isi) === FALSE) {
        return '';
    }
    return $dir_rel . $nama;
}
