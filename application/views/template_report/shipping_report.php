<style>
  body { font-family: "Calibri", "Arial", sans-serif; font-size: 11px; color: #333; }
  table { border-collapse: collapse; margin-bottom: 15px; }
  th, td { padding: 6px 10px; border: 1px solid #bdc3c7; }
  
  /* Header Info Section */
  .title-header { font-size: 16px; font-weight: bold; background-color: #1e3d59; color: #ffffff; text-align: left; padding: 12px; }
  .info-lbl { background-color: #f5f7fa; font-weight: bold; width: 140px; }
  .info-val { background-color: #ffffff; font-weight: 500; }
  
  /* Table Headers */
  .h-main { background-color: #1e3d59; color: #ffffff; font-weight: bold; text-align: center; font-size: 11px; }
  .h-sub  { background-color: #17b978; color: #ffffff; font-weight: bold; text-align: center; font-size: 11px; }
  
  /* Column headers with specific brand colors */
  .h-spec  { background-color: #8e44ad; color: #ffffff; font-weight: bold; text-align: center; }
  .h-1sku  { background-color: #16a085; color: #ffffff; font-weight: bold; text-align: center; }
  .h-29sk  { background-color: #d35400; color: #ffffff; font-weight: bold; text-align: center; }
  .h-banyak { background-color: #c0392b; color: #ffffff; font-weight: bold; text-align: center; }
  .h-total { background-color: #2c3e50; color: #ffffff; font-weight: bold; text-align: center; }

  /* Cells with soft color backgrounds matching headers */
  .cell-spec   { background-color: #f5eef8; text-align: center; }
  .cell-1sku   { background-color: #e8f8f5; text-align: center; }
  .cell-29sku  { background-color: #fdf2e9; text-align: center; }
  .cell-banyak { background-color: #fdedd8; text-align: center; }
  .cell-total  { background-color: #eaeded; text-align: center; font-weight: bold; }
  .cell-kurir  { background-color: #ffffff; font-weight: bold; }

  /* Utility classes */
  .tc { text-align: center; }
  .tr { text-align: right; }
  .bold { font-weight: bold; }
  .row-alt { background-color: #f9fbfd; }
  .pct { color: #7f8c8d; font-size: 9px; font-style: italic; }
  
  /* Footers */
  .foot-row { background-color: #eaeded; font-weight: bold; }
  .foot-spec { background-color: #ebdef0; font-weight: bold; text-align: center; }
  .foot-1sku { background-color: #d1f2eb; font-weight: bold; text-align: center; }
  .foot-29sk { background-color: #fadbd8; font-weight: bold; text-align: center; }
  .foot-ban  { background-color: #fcdcd3; font-weight: bold; text-align: center; }
</style>

<?php
  $cat    = !empty($cat_totals) ? $cat_totals : [];
  $gt     = !empty($grand_total) ? (int)$grand_total : 0;
  $t_spec = isset($cat['total_special'])   ? (int)$cat['total_special']   : 0;
  $t_1sku = isset($cat['total_1sku'])      ? (int)$cat['total_1sku']      : 0;
  $t_29   = isset($cat['total_2_9sku'])    ? (int)$cat['total_2_9sku']    : 0;
  $t_ban  = isset($cat['total_qty_banyak'])? (int)$cat['total_qty_banyak']: 0;
  $pct    = function($v, $t) { return $t > 0 ? round($v / $t * 100, 1) . '%' : '0%'; };
  $dates  = explode(' - ', $reportrange);
?>

<!-- ===================================================
     SECTION 1: HEADER & PERIOD INFORMATION
     =================================================== -->
<table>
  <colgroup>
    <col width="160">
    <col width="300">
  </colgroup>
  <tr>
    <td colspan="2" class="title-header">LAPORAN TOTAL PENGIRIMAN PAKET</td>
  </tr>
  <tr>
    <td class="info-lbl">Periode</td>
    <td class="info-val"><?= htmlspecialchars($dates[0]) ?> s/d <?= htmlspecialchars($dates[1]) ?></td>
  </tr>
  <tr>
    <td class="info-lbl">Total Pengiriman</td>
    <td class="info-val" style="font-weight: bold; color: #1e3d59;"><?= number_format($gt) ?> paket</td>
  </tr>
</table>

<!-- ===================================================
     SECTION 2: SUMMARY CARDS (KPI KPI)
     =================================================== -->
<table>
  <colgroup>
    <col width="220">
    <col width="120">
    <col width="120">
  </colgroup>
  <thead>
    <tr>
      <th colspan="3" class="h-main">RINGKASAN KATEGORI PENGIRIMAN</th>
    </tr>
    <tr>
      <th style="background-color: #f2f4f4;">Kategori</th>
      <th style="background-color: #f2f4f4;">Jumlah Paket</th>
      <th style="background-color: #f2f4f4;">% dari Total</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="bold" style="background-color: #fcfcfc;">Grand Total Dikirim</td>
      <td class="tc bold" style="background-color: #fcfcfc;"><?= number_format($gt) ?></td>
      <td class="tc bold" style="background-color: #fcfcfc;">100%</td>
    </tr>
    <tr>
      <td class="bold" style="color: #8e44ad; background-color: #f5eef8;">â˜… Resi Special (1 SKU, 1 Qty Special)</td>
      <td class="tc cell-spec bold"><?= number_format($t_spec) ?></td>
      <td class="tc cell-spec pct"><?= $pct($t_spec, $gt) ?></td>
    </tr>
    <tr>
      <td style="color: #16a085; background-color: #e8f8f5;">1 SKU &amp; Qty &le; 9</td>
      <td class="tc cell-1sku"><?= number_format($t_1sku) ?></td>
      <td class="tc cell-1sku pct"><?= $pct($t_1sku, $gt) ?></td>
    </tr>
    <tr>
      <td style="color: #d35400; background-color: #fdf2e9;">2-9 SKU &amp; Qty &le; 9</td>
      <td class="tc cell-29sku"><?= number_format($t_29) ?></td>
      <td class="tc cell-29sku pct"><?= $pct($t_29, $gt) ?></td>
    </tr>
    <tr>
      <td style="color: #c0392b; background-color: #fdedd8;">Qty Banyak (&gt; 9)</td>
      <td class="tc cell-banyak"><?= number_format($t_ban) ?></td>
      <td class="tc cell-banyak pct"><?= $pct($t_ban, $gt) ?></td>
    </tr>
  </tbody>
</table>

<!-- ===================================================
     SECTION 3: DETAILED TABLE PER COURIER
     =================================================== -->
<?php if (!empty($detail_data)): ?>
<table>
  <colgroup>
    <col width="45">
    <col width="180">
    <col width="110">
    <col width="130">
    <col width="130">
    <col width="130">
    <col width="130">
  </colgroup>
  <thead>
    <tr>
      <th colspan="7" class="h-main">RINCIAN KINERJA PENGIRIMAN PER KURIR</th>
    </tr>
    <tr>
      <th class="h-sub">#</th>
      <th class="h-sub">Kurir</th>
      <th class="h-total">Total Paket</th>
      <th class="h-spec">Resi Special</th>
      <th class="h-1sku">1 SKU &amp; Qty &le; 9</th>
      <th class="h-29sk">2-9 SKU &amp; Qty &le; 9</th>
      <th class="h-banyak">Qty Banyak (&gt; 9)</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1; foreach($detail_data as $d):
      $tot   = (int)$d['total'];
      $spec  = (int)$d['total_special'];
      $s1sku = (int)$d['total_1sku'];
      $s29   = (int)$d['total_2_9sku'];
      $sban  = (int)$d['total_qty_banyak'];
      $rowAlt = ($i % 2 == 0) ? 'row-alt' : '';
    ?>
    <tr class="<?= $rowAlt ?>">
      <td class="tc pct"><?= $i++ ?></td>
      <td class="cell-kurir"><?= htmlspecialchars($d['nama_kurir']) ?></td>
      <td class="cell-total"><?= number_format($tot) ?></td>
      <td class="cell-spec"><?= number_format($spec) ?> <span class="pct">(<?= $pct($spec, $tot) ?>)</span></td>
      <td class="cell-1sku"><?= number_format($s1sku) ?> <span class="pct">(<?= $pct($s1sku, $tot) ?>)</span></td>
      <td class="cell-29sku"><?= number_format($s29) ?> <span class="pct">(<?= $pct($s29, $tot) ?>)</span></td>
      <td class="cell-banyak"><?= number_format($sban) ?> <span class="pct">(<?= $pct($sban, $tot) ?>)</span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="foot-row">
      <td colspan="2" class="tr bold">TOTAL HARI INI</td>
      <td class="tc bold"><?= number_format($gt) ?></td>
      <td class="foot-spec"><?= number_format($t_spec) ?> <span class="pct">(<?= $pct($t_spec, $gt) ?>)</span></td>
      <td class="foot-1sku"><?= number_format($t_1sku) ?> <span class="pct">(<?= $pct($t_1sku, $gt) ?>)</span></td>
      <td class="foot-29sk"><?= number_format($t_29) ?> <span class="pct">(<?= $pct($t_29, $gt) ?>)</span></td>
      <td class="foot-ban"><?= number_format($t_ban) ?> <span class="pct">(<?= $pct($t_ban, $gt) ?>)</span></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>
