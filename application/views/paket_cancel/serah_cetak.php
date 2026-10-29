<?php
// Halaman cetak A4 serah terima paket cancel (Retur -> Restok) untuk satu tanggal terima.
// Standalone (bukan JSON/SPA), dibuka di tab baru. Hanya barang yang sesuai resi yang dihitung.
$bulan = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$t = strtotime($tanggal);
$tgl_indo = date('d', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);

// Tinggi baris & huruf menyesuaikan jumlah baris supaya muat satu halaman
$n = max(count($det), 1);
$rowH = max(5.5, min(10, (297 - 24 - 14 - 6 - 46) / ($n + 1)));
$fs = max(7.5, min(11.5, $rowH * 1.45));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Serah Terima Paket Cancel <?= html_escape($tgl_indo) ?></title>
<style>
@page{size:A4;margin:12mm}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;font-size:10.5pt;color:#111;margin:0}
.head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2.5px solid #111;padding-bottom:8px}
.head h1{font-size:16pt;margin:0;letter-spacing:.5px}
.head b{font-size:12pt}
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
<div class="head">
    <h1>SERAH TERIMA PAKET CANCEL</h1>
    <b><?= html_escape($tgl_indo) ?></b>
</div>
<?php if (empty($det)): ?>
    <div class="kosong">Tidak ada barang sesuai resi pada tanggal ini.</div>
<?php else: ?>
<table>
    <thead>
        <tr><th class="fit">No</th><th class="fit">No. Rak</th><th class="fit">SKU</th><th class="fit">Qty</th><th class="fit real">Qty Real</th><th>Ket</th></tr>
    </thead>
    <tbody>
    <?php foreach ($det as $i => $x): ?>
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
<?php endif; ?>
<div class="ttd">
    <div>
        <div class="ttl">RETUR</div>
        <div class="sp"></div>
        <?php foreach ($petugas as $nm): ?><div class="nm"><?= html_escape($nm) ?></div><?php endforeach; ?>
        <?php if (empty($petugas)): ?><div class="nm">&nbsp;</div><?php endif; ?>
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
