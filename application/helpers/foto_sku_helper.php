<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Salinan lokal foto produk (tblsku.link_foto -> tblsku.foto_lokal).
 *
 * link_foto menunjuk ke object storage Jubelio (upcloudobjects.com, Azure blob),
 * jadi foto di layar packer/retur/QC hilang begitu internet putus.
 * Cron::sinkron_foto_sku() mengunduh setiap URL ke folder `foto_produk_dir`
 * (secrets.php, bawaan C:/foto-produk/) dengan nama md5(url).ext dan mencatat
 * nama itu di tblsku.foto_lokal. Apache melayani folder itu langsung lewat
 * Alias /foto-produk/ (conf/extra/httpd-iresis-foto-produk.conf) -- tanpa PHP,
 * dengan cache browser 30 hari -- jadi jalur lokal tetap ringan.
 *
 * Urutan di browser: <img src="URL asli" data-foto-lokal="/foto-produk/x.jpg">.
 * assets/js/foto_sku.js mengganti ke salinan lokal hanya kalau URL asli gagal
 * atau tidak selesai dalam beberapa detik. Helper ini hanya menyiapkan atribut
 * itu; foto_lokal dipakai bila cocok dengan link_foto sekarang (md5), supaya
 * SKU yang fotonya baru diganti tidak menampilkan foto lama.
 */

/** Awalan URL Alias Apache untuk folder foto produk. */
define('FOTO_SKU_URL_PREFIX', '/foto-produk/');

/** Folder penyimpanan di disk (kunci `foto_produk_dir`), selalu berakhiran pemisah. */
function foto_sku_dir()
{
    if (!function_exists('iresis_secret')) {
        require_once(APPPATH . 'config/secrets_load.php');
    }
    return rtrim((string) iresis_secret('foto_produk_dir', 'C:/foto-produk/'), '/\\') . DIRECTORY_SEPARATOR;
}

/**
 * Nama berkas lokal untuk sebuah URL foto: md5 URL + ekstensi asli
 * (jpg/jpeg/png/gif/webp; selain itu dipaksa jpg). Kosong bila bukan URL http.
 */
function foto_sku_nama_berkas($url)
{
    $url = trim((string) $url);
    if ($url === '' || stripos($url, 'http') !== 0) {
        return '';
    }
    $path = (string) parse_url($url, PHP_URL_PATH);
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], TRUE)) {
        $ext = 'jpg';
    }
    return md5($url) . '.' . $ext;
}

/**
 * URL salinan lokal (/foto-produk/<nama>) bila foto_lokal terisi DAN masih
 * cocok dengan link_foto sekarang; kalau tidak, string kosong. Tidak menyentuh
 * disk -- cukup dari dua kolom tblsku.
 */
function foto_sku_lokal_url($link_foto, $foto_lokal)
{
    $foto_lokal = trim((string) $foto_lokal);
    if ($foto_lokal === '' || $foto_lokal !== foto_sku_nama_berkas($link_foto)) {
        return '';
    }
    return FOTO_SKU_URL_PREFIX . rawurlencode($foto_lokal);
}

/**
 * Tag <img> lengkap: src = URL asli, data-foto-lokal = cadangan lokal.
 * $attr = atribut tambahan yang sudah aman untuk HTML (class, style, data-*).
 * Kosong bila tidak ada foto sama sekali.
 */
function foto_sku_img($link_foto, $foto_lokal, $attr = '')
{
    $url   = trim((string) $link_foto);
    $lokal = foto_sku_lokal_url($url, $foto_lokal);
    if ($url === '' && $lokal === '') {
        return '';
    }
    $src  = $url !== '' ? $url : $lokal;
    $html = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"';
    if ($lokal !== '' && $url !== '') {
        $html .= ' data-foto-lokal="' . htmlspecialchars($lokal, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($attr !== '') {
        $html .= ' ' . $attr;
    }
    return $html . '>';
}
