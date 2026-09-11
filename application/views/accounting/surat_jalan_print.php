<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Helper format angka ala Excel (ribuan pakai koma seperti PDF)
if (!function_exists('sj_num')) {
    function sj_num($v) {
        $v = intval($v);
        return number_format($v, 0, '.', ',');
    }
}

$tgl_cetak = date('n/j/Y ~ g:i A');
$tgl_doc   = !empty($doc['tgl']) ? date('d/M/Y', strtotime($doc['tgl'])) : '';
// "Surat Jalan Ke" = Jenis SJ (mis. TR-7). Fallback ke No. Trf Jubelio bila kosong.
$sj_ke     = !empty($doc['jenis_sj']) ? $doc['jenis_sj'] : ($doc['no_trf_jubelio'] ?? '');

$tot_disp = 0; $tot_gd = 0; $tot_rqs = 0;
foreach ($items as $it) {
    $tot_disp += intval($it['qty_jubelio_disp']);
    $tot_gd   += intval($it['qty_jubelio_gd']);
    $tot_rqs  += intval($it['qty_restock_rqst']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Surat Jalan <?= htmlspecialchars($doc['no_sj']) ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: "Times New Roman", Times, serif;
    color: #000;
    margin: 0;
    padding: 0.1in;
    background: #fff;
    font-size: 11px;
  }
  .sj-print-btn {
    position: fixed; top: 12px; right: 16px;
    background: #2563eb; color: #fff; border: none;
    padding: 8px 16px; border-radius: 6px; cursor: pointer;
    font-family: Arial, sans-serif; font-size: 13px; font-weight: 600;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
  }
  .page-num { text-align: right; font-style: italic; font-weight: bold; font-size: 11px; margin-bottom: 4px; }

  /* Title bar */
  table.head-title { width: 100%; border-collapse: collapse; }
  table.head-title td { border: 1px solid #000; padding: 5px 8px; vertical-align: middle; }
  .title-main { text-align: center; font-size: 18px; font-weight: bold; letter-spacing: 0.3px; white-space: nowrap; }
  .title-side-l { width: 105px; text-align: left; font-size: 10px; font-weight: bold; white-space: nowrap; }
  .title-side-v { width: 128px; text-align: center; font-size: 17px; font-weight: bold; white-space: nowrap; }

  /* Info block */
  table.head-info { width: 100%; border-collapse: collapse; margin-top: 2px; }
  table.head-info td { padding: 3px 8px; font-size: 11px; }
  table.head-info .lbl { font-weight: bold; }

  /* Main table */
  table.sj { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
  table.sj th, table.sj td { border: 1px solid #000; padding: 3px 3px; font-size: 8.5px; word-break: break-word; overflow-wrap: anywhere; }
  /* Header selalu satu baris (huruf tidak turun ke bawah) */
  table.sj thead th { text-align: center; font-weight: bold; background: #fff; white-space: nowrap; font-size: 8px; line-height: 1.15; padding: 3px 2px; }
  table.sj td { vertical-align: top; }
  .c { text-align: center; }
  .r { text-align: right; }
  /* Kolom No Rak Gudang: bungkus penuh, semua teks terbaca (tidak terpotong) */
  .rak { font-size: 8px; line-height: 1.2; white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; text-align: left; }
  .rqs { font-weight: bold; text-align: center; font-size: 13px; }
  .tot-lbl { text-align: center; font-weight: bold; }
  .fill { min-width: 26px; }
  /* Tinggi baris item minimal ~2.5cm walau isi rak gudang sedikit (ruang tulis tangan) */
  table.sj tbody tr.item-row td { height: 2.5cm; }

  .legend { font-size: 10px; font-weight: bold; font-style: italic; margin: 8px 0 14px; }

  /* Signature */
  table.sign { width: 100%; border-collapse: collapse; margin-top: 6px; }
  table.sign > tbody > tr > td { vertical-align: top; padding: 4px 10px; font-size: 11px; }
  .sign-col { width: 30%; }
  .sign-mid { width: 40%; }
  .sign-line { border-top: 1px solid #000; margin-top: 48px; }
  .sign-role { font-weight: bold; }
  table.wtable { border-collapse: collapse; width: 100%; }
  table.wtable td, table.wtable th { border: 1px solid #000; padding: 3px 6px; font-size: 10.5px; height: 20px; }
  table.wtable th { text-align: center; font-weight: bold; }
  table.wtable .wlbl { text-align: right; font-weight: bold; width: 45%; }

  .printed { margin-top: 22px; font-size: 10px; font-style: italic; }

  @media print {
    .sj-print-btn { display: none; }
    body { padding: 0; }
    @page { size: A4 portrait; margin: 0.1in; }
  }
</style>
</head>
<body>

<button class="sj-print-btn" onclick="window.print()">&#128424; Cetak</button>

<div class="page-num">Page 1 of 1</div>

<!-- TITLE BAR -->
<table class="head-title">
  <tr>
    <td class="title-main">SURAT JALAN - TIM PRINT RESI</td>
    <td class="title-side-l">Surat Jalan Ke :</td>
    <td class="title-side-v"><?= htmlspecialchars($sj_ke) ?></td>
  </tr>
</table>

<!-- INFO BLOCK -->
<table class="head-info">
  <tr>
    <td class="lbl" style="width:26%;">DARI&nbsp;&nbsp;&nbsp;:&nbsp; Gudang Utama ( F16 )</td>
    <td class="lbl" style="width:18%;">RF TR No.&nbsp; :</td>
    <td style="width:14%;">0</td>
    <td class="lbl" style="width:16%;">Nomor Transfer :</td>
    <td style="width:26%;"><?= htmlspecialchars($doc['no_trf_jubelio'] ?? '') ?></td>
  </tr>
  <tr>
    <td class="lbl">TUJUAN&nbsp; :&nbsp; Display Barang ( F19A &amp; F19B )</td>
    <td class="lbl">Market Place&nbsp; :</td>
    <td>0</td>
    <td class="lbl">Tanggal&nbsp; :</td>
    <td style="font-weight:bold;"><?= htmlspecialchars($tgl_doc) ?></td>
  </tr>
</table>

<!-- MAIN TABLE -->
<table class="sj">
  <colgroup>
    <col style="width:2.5%;"> <!-- NO -->
    <col style="width:10%;">  <!-- SKU -->
    <col style="width:30%;">  <!-- NO. RAK GUDANG -->
    <col style="width:2.5%;"> <!-- H -->
    <col style="width:3.5%;"> <!-- INS -->
    <col style="width:4.5%;"> <!-- INS-H -->
    <col style="width:4.5%;"> <!-- DISP -->
    <col style="width:4%;">   <!-- GD -->
    <col style="width:4%;">   <!-- RQS -->
    <col style="width:4%;">   <!-- REAL -->
    <col style="width:9%;">   <!-- NO. RAK DISP -->
    <col style="width:3.5%;"> <!-- INS -->
    <col style="width:13.5%;"><!-- KET -->
  </colgroup>
  <thead>
    <tr>
      <th rowspan="2" style="width:26px;">NO</th>
      <th rowspan="2" style="width:110px;">SKU</th>
      <th colspan="4">TIM INBOUND</th>
      <th colspan="2">QTY JUBELIO</th>
      <th colspan="2">QTY TRANSF</th>
      <th colspan="2">TIM DISPLAY</th>
      <th rowspan="2" style="width:120px;">KET</th>
    </tr>
    <tr>
      <th>NO. RAK GUDANG</th>
      <th style="width:22px;">H</th>
      <th style="width:26px;">INS</th>
      <th style="width:30px;">INS-H</th>
      <th style="width:40px;">DISP</th>
      <th style="width:48px;">GD</th>
      <th style="width:42px;">RQS</th>
      <th style="width:42px;">REAL</th>
      <th style="width:60px;">NO. RAK DISP</th>
      <th style="width:26px;">INS</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="13" class="c" style="padding:16px;">Belum ada item. Upload excel persediaan terlebih dahulu.</td></tr>
    <?php else: $no = 0; foreach ($items as $it): $no++; ?>
      <tr class="item-row">
        <td class="c"><?= $no ?></td>
        <td><?= htmlspecialchars($it['sku']) ?></td>
        <td class="rak"><?= htmlspecialchars($it['no_rak_gudang'] ?? '') ?></td>
        <td class="fill">&nbsp;</td>
        <td class="fill">&nbsp;</td>
        <td class="fill">&nbsp;</td>
        <td class="c"><?= sj_num($it['qty_jubelio_disp']) ?></td>
        <td class="r"><?= sj_num($it['qty_jubelio_gd']) ?></td>
        <td class="rqs"><?= sj_num($it['qty_restock_rqst']) ?></td>
        <td class="fill">&nbsp;</td>
        <td class="c"><?= htmlspecialchars($it['no_rak'] ?? '') ?></td>
        <td class="fill">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    <?php endforeach; endif; ?>
    <!-- TOTAL -->
    <tr>
      <td colspan="6" class="tot-lbl">TOTAL QUANTITY</td>
      <td class="c" style="font-weight:bold;"><?= sj_num($tot_disp) ?></td>
      <td class="r" style="font-weight:bold;"><?= sj_num($tot_gd) ?></td>
      <td class="c" style="font-weight:bold;"><?= sj_num($tot_rqs) ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  </tbody>
</table>

<div class="legend">H = HABIS, INS = INISIAL PELAKSANA, INS-H = INISIAL HITUNG, RQS = REQUEST</div>

<!-- SIGNATURE -->
<table class="sign">
  <tr>
    <td class="sign-col">
      <div class="sign-role">Dibuat Oleh</div>
      <div style="margin-top:2px;">Tim Accounting</div>
      <div class="sign-line"></div>
      <div class="c" style="margin-top:2px;"><?= htmlspecialchars($pembuat) ?></div>
    </td>
    <td class="sign-mid">
      <table class="wtable">
        <tr>
          <th class="wlbl" style="border:none;">&nbsp;</th>
          <th>GUDANG</th>
          <th>DISPLAY</th>
        </tr>
        <tr><td class="wlbl">Waktu Terima :</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td class="wlbl">Waktu Selesai :</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td class="wlbl">Jumlah Orang :</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td class="wlbl">Inisial Orang :</td><td>&nbsp;</td><td>&nbsp;</td></tr>
      </table>
    </td>
    <td class="sign-col">
      <table style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="width:50%; vertical-align:top;">
            <div class="sign-role">Disiapkan Oleh</div>
            <div style="margin-top:2px;">Tim Inbound</div>
            <div class="sign-line"></div>
          </td>
          <td style="width:50%; vertical-align:top;">
            <div class="sign-role">Penerima</div>
            <div style="margin-top:2px;">Tim Re Stock</div>
            <div class="sign-line"></div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<div class="printed">Dicetak Tanggal : <?= htmlspecialchars($tgl_cetak) ?></div>

</body>
</html>
