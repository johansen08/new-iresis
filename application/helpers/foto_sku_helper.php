<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Salinan lokal foto produk (tblsku.link_foto).
 *
 * link_foto menunjuk ke object storage Jubelio (upcloudobjects.com, Azure blob),
 * jadi foto di layar packer/retur/QC hilang begitu internet putus.
 * Cron::sinkron_foto_sku() mengunduh semuanya ke assets/foto_sku/<md5(url)>.<ext>;
 * helper ini memilih salinan lokal itu kalau ada, dan jatuh kembali ke URL asli
 * kalau belum terunduh (SKU baru yang belum disinkron).
 *
 * Dipanggil di controller tepat sebelum link_foto dikirim ke JSON/HTML, supaya
 * JS di view tidak perlu tahu-menahu soal cache ini.
 */

define('FOTO_SKU_DIR', 'assets/foto_sku/');

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
 * URL yang dipakai <img>: salinan lokal bila sudah ada, kalau tidak URL asli.
 */
function foto_sku_url($url)
{
    $nama = foto_sku_nama_berkas($url);
    if ($nama === '') {
        return (string) $url;
    }
    if (is_file(FCPATH . FOTO_SKU_DIR . $nama)) {
        return base_url(FOTO_SKU_DIR . $nama);
    }
    return (string) $url;
}
