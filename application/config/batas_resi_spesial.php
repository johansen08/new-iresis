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
 *           Angka yang tidak masuk tingkat mana pun memakai default.
 */
$config['batas_resi_spesial'] = array(
    'basis'   => 'resi_spesial',
    'default' => 55,
    'tingkat' => array(
        // Sementara rata 55% untuk semua angka. Batas bertingkat yang pernah dipakai:
        // 5k=52, 6k=55, 7k=58, 8k=60, 9k=62 -- aktifkan lagi dengan menghapus tanda //.
        // array(5000, 5999, 52),
        // array(6000, 6999, 55),
        // array(7000, 7999, 58),
        // array(8000, 8999, 60),
        // array(9000, 9999, 62),
    ),
);
