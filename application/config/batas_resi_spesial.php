<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Batas persen Laporan Resi Spesial menurut besar angka hari itu.
 * Makin banyak resi, makin tinggi batas yang dituntut.
 *
 * basis   : angka yang dipakai mencocokkan tingkat --
 *           'resi_spesial' (kolom Maks. Resi Spesial) atau 'satu_sku_satu_qty' (kolom 1 SKU 1 QTY).
 * default : batas (%) untuk angka yang tidak masuk tingkat mana pun.
 * tingkat : [dari, sampai, batas%], batas bawah dan atas ikut (inklusif).
 *           Tambah baris baru di sini begitu batas 5k, 7k, 8k, dst sudah ditentukan.
 */
$config['batas_resi_spesial'] = array(
    'basis'   => 'resi_spesial',
    'default' => 50,
    'tingkat' => array(
        array(6000, 6999, 55),
        // array(5000, 5999, ??),
        // array(7000, 7999, ??),
        // array(8000, 8999, ??),
        // array(9000, 9999, ??),
    ),
);
