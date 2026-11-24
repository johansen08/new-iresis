<?php
// Halaman cetak A4 serah terima retur yang sudah dibuka (Retur -> Restok). Standalone, dibuka di tab baru.
// Tata letak mengikuti paket_cancel/serah_cetak. Isi = barang yang kembali ke display, dijumlah per SKU.
$bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$t = strtotime($tanggal);
$tgl_indo = date('d', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);

// Per lantai (digit pertama No. Rak). Rak kosong / berawalan huruf = LAINNYA. Satu dokumen, tiap lantai mulai halaman baru.
$per_lantai = !empty($per_lantai);
$grup = array();
if ($per_lantai) {
    foreach ($det as $x) {
        $k = (isset($x['rak'][0]) && ctype_digit($x['rak'][0])) ? $x['rak'][0] : 'LAINNYA';
        $grup[$k][] = $x;
    }
    uksort($grup, function ($a, $b) {
        if ($a === 'LAINNYA') return $b === 'LAINNYA' ? 0 : 1;
        if ($b === 'LAINNYA') return -1;
        return (int) $a - (int) $b;
    });
} else {
    $grup[''] = $det;
}
$judul = $per_lantai ? 'PENGEMBALIAN BARANG (RETUR KE RESTOK)' : 'PENGEMBALIAN BARANG (DARI RETUR KE RESTOK)';

// Tinggi baris & huruf menyesuaikan jumlah baris terbanyak per halaman supaya muat satu halaman
$n = 1;
foreach ($grup as $g) $n = max($n, count($g));
$rowH = max(5.5, min(10, (297 - 24 - 14 - 6 - 46) / ($n + 1)));
$fs = max(7.5, min(11.5, $rowH * 1.45));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Pengembalian Barang (Retur Sudah Dibuka ke Restok) <?= html_escape($tgl_indo) ?></title>
<style>
@page{size:A4;margin:12mm}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;font-size:10.5pt;color:#111;margin:0}
.head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2.5px solid #111;padding-bottom:8px}
.head h1{font-size:16pt;margin:0;letter-spacing:.5px}
.head b{font-size:12pt}
.sub{font-size:9.5pt;color:#333;margin-top:6px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th{background:#fff;color:#111;font-size:9pt;font-weight:bold;padding:5px;border:1px solid #888;border-bottom:2px solid #111;text-transform:uppercase;text-align:center}
td{border:1px solid #888;padding:0 6px;font-size:<?= round($fs, 1) ?>pt;height:<?= round($rowH, 2) ?>mm;line-height:1.1}
thead{display:table-header-group}tr{page-break-inside:avoid}
.c{text-align:center}
.fit{width:1%;white-space:nowrap;padding-left:9px;padding-right:9px}
.real{min-width:17mm}
.ttd{display:flex;justify-content:space-around;margin-top:16px;page-break-inside:avoid}
.ttd > div{width:44%;text-align:center;font-size:10pt}
.ttd .ttl{font-weight:bold;letter-spacing:.5px}
.ttd .sp{height:20mm;border-bottom:1px solid #111;margin:4px 0}
.ttd .nm{font-weight:bold;font-size:10pt}
.kosong{text-align:center;padding:30px;color:#555}
</style>
</head>
<body>
<?php if (empty($det)): ?>
<div class="head">
    <h1><?= $judul ?><?php if (!empty($ulang)): ?> <span style="font-size:11pt;border:2px solid #111;padding:1px 6px;margin-left:6px;">CETAK ULANG</span><?php endif; ?></h1>
    <b><?= html_escape($tgl_indo) ?></b>
</div>
<div class="sub"><?= html_escape($sub) ?></div>
<div class="kosong">Tidak ada barang yang perlu diserahterimakan pada filter ini.</div>
<?php else: ?>
<?php $gi = 0; foreach ($grup as $kunci => $baris): $gi++; ?>
<div<?= $gi > 1 ? ' style="page-break-before:always"' : '' ?>>
<div class="head">
    <h1><?= $judul ?><?php if ($per_lantai): ?> <?= $kunci === 'LAINNYA' ? 'LAINNYA' : 'LANTAI ' . html_escape($kunci) ?><?php endif; ?><?php if (!empty($ulang)): ?> <span style="font-size:11pt;border:2px solid #111;padding:1px 6px;margin-left:6px;">CETAK ULANG</span><?php endif; ?></h1>
    <b><?= html_escape($tgl_indo) ?></b>
</div>
<div class="sub"><?= html_escape($sub) ?></div>
<table>
    <thead>
        <tr><th class="fit">No</th><th class="fit">No. Rak</th><th class="fit">SKU</th><th class="fit">Qty</th><th class="fit real">Qty Real</th><th>Ket</th></tr>
    </thead>
    <tbody>
    <?php foreach ($baris as $i => $x): ?>
        <tr>
            <td class="c fit"><?= $i + 1 ?></td>
            <td class="c fit"><?= html_escape($x['rak']) ?></td>
            <td class="fit"><?= html_escape($x['sku']) ?></td>
            <td class="c fit"><?= (int) $x['qty'] ?></td>
            <td class="fit real"></td>
            <td></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endforeach; ?>
<?php endif; ?>
<div class="ttd">
    <div>
        <div class="ttl">RETUR</div>
        <div class="sp"></div>
        <div class="nm"><?= $petugas !== '' ? html_escape($petugas) : '&nbsp;' ?></div>
    </div>
    <div>
        <div class="ttl">RESTOK</div>
        <div class="sp"></div>
        <div class="nm">&nbsp;</div>
    </div>
</div>
<script>window.onload = function () { window.print(); };</script>
</body>
</html>
