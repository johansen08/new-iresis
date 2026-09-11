<style>
  body { font-family: "Calibri", "Arial", sans-serif; font-size: 11px; color: #333; }
  table { border-collapse: collapse; margin-bottom: 15px; }
  th, td { padding: 6px 10px; border: 1px solid #ce93d8; }
  
  /* Header Info Section */
  .title-header { font-size: 16px; font-weight: bold; background-color: #4a148c; color: #ffffff; text-align: left; padding: 12px; }
  .info-lbl { background-color: #fcf3f8; font-weight: bold; width: 140px; }
  .info-val { background-color: #ffffff; font-weight: 500; }
  
  /* Table Headers */
  .h-main { background-color: #4a148c; color: #ffffff; font-weight: bold; text-align: center; font-size: 11px; }
  .h-sub  { background-color: #7b1fa2; color: #ffffff; font-weight: bold; text-align: center; font-size: 11px; }
  
  /* Category headers with specific brand colors */
  .h-spec   { background-color: #8e44ad; color: #ffffff; font-weight: bold; text-align: center; }
  .h-1sku   { background-color: #16a085; color: #ffffff; font-weight: bold; text-align: center; }
  .h-29sk   { background-color: #d35400; color: #ffffff; font-weight: bold; text-align: center; }
  .h-banyak { background-color: #c0392b; color: #ffffff; font-weight: bold; text-align: center; }
  .h-blue   { background-color: #1e3d59; color: #ffffff; font-weight: bold; text-align: center; }

  /* Cell backgrounds for category highlights */
  .cell-spec   { background-color: #f5eef8; text-align: center; }
  .cell-1sku   { background-color: #e8f8f5; text-align: center; }
  .cell-29sku  { background-color: #fdf2e9; text-align: center; }
  .cell-banyak { background-color: #fdedd8; text-align: center; }
  .cell-total  { background-color: #f5f0f6; text-align: center; font-weight: bold; }

  /* Utility classes */
  .tc { text-align: center; }
  .tr { text-align: right; }
  .bold { font-weight: bold; }
  .row-alt { background-color: #faf5fc; }
  .pct { color: #7f8c8d; font-size: 9px; font-style: italic; }
  
  /* Footers */
  .foot-row { background-color: #f5f0f6; font-weight: bold; }
</style>

<?php
  $dates   = explode(' - ', $reportrange);
  $cat     = !empty($cat_totals) ? $cat_totals : [];

  // Tally counts from list_data by kategori_resi (mutually exclusive)
  $cnt_spec = 0; 
  $cnt_1 = 0; 
  $cnt_29 = 0; 
  $cnt_ban = 0; 
  $cnt_total = 0;

  if (!empty($list_data)) {
      foreach ($list_data as $row) {
          $cnt_total++;
          $kat = isset($row->kategori_resi) ? $row->kategori_resi : (isset($row['kategori_resi']) ? $row['kategori_resi'] : '');
          $kat_lower = strtolower($kat);
          
          if (strpos($kat_lower, 'special') !== false) {
              $cnt_spec++;
          } elseif (strpos($kat_lower, '1 sku') !== false) {
              $cnt_1++;
          } elseif (strpos($kat_lower, '2-9') !== false) {
              $cnt_29++;
          } else {
              $cnt_ban++;
          }
      }
  }

  $pct = function($v, $t) { return $t > 0 ? round($v / $t * 100, 1) . '%' : '0%'; };
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
    <td colspan="2" class="title-header">LAPORAN SKU SPECIAL</td>
  </tr>
  <tr>
    <td class="info-lbl">Periode</td>
    <td class="info-val"><?= htmlspecialchars($dates[0]) ?> s/d <?= htmlspecialchars($dates[1]) ?></td>
  </tr>
  <tr>
    <td class="info-lbl">Total Resi Special</td>
    <td class="info-val" style="font-weight: bold; color: #4a148c;"><?= number_format($cnt_spec) ?> resi</td>
  </tr>
  <tr>
    <td class="info-lbl">Total Baris Detail</td>
    <td class="info-val"><?= number_format($cnt_total) ?> baris</td>
  </tr>
</table>

<!-- ===================================================
     SECTION 2: SUMMARY CARDS (KPI)
     =================================================== -->
<table>
  <colgroup>
    <col width="220">
    <col width="120">
    <col width="120">
  </colgroup>
  <thead>
    <tr>
      <th colspan="3" class="h-main">RINGKASAN KATEGORI RESI SPECIAL</th>
    </tr>
    <tr>
      <th style="background-color: #f5f0f6;">Kategori</th>
      <th style="background-color: #f5f0f6;">Jumlah Resi</th>
      <th style="background-color: #f5f0f6;">% dari Total Detail</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="bold" style="color: #8e44ad; background-color: #f5eef8;">★ Resi Special (1 SKU, 1 Qty Special)</td>
      <td class="tc cell-spec bold"><?= number_format($cnt_spec) ?></td>
      <td class="tc cell-spec pct"><?= $pct($cnt_spec, $cnt_total) ?></td>
    </tr>
    <tr>
      <td style="color: #16a085; background-color: #e8f8f5;">1 SKU &amp; Qty &le; 9</td>
      <td class="tc cell-1sku"><?= number_format($cnt_1) ?></td>
      <td class="tc cell-1sku pct"><?= $pct($cnt_1, $cnt_total) ?></td>
    </tr>
    <tr>
      <td style="color: #d35400; background-color: #fdf2e9;">2-9 SKU &amp; Qty &le; 9</td>
      <td class="tc cell-29sku"><?= number_format($cnt_29) ?></td>
      <td class="tc cell-29sku pct"><?= $pct($cnt_29, $cnt_total) ?></td>
    </tr>
    <tr>
      <td style="color: #c0392b; background-color: #fdedd8;">Qty Banyak (&gt; 9)</td>
      <td class="tc cell-banyak"><?= number_format($cnt_ban) ?></td>
      <td class="tc cell-banyak pct"><?= $pct($cnt_ban, $cnt_total) ?></td>
    </tr>
    <tr class="foot-row">
      <td class="bold">Total Baris Transaksi</td>
      <td class="tc bold"><?= number_format($cnt_total) ?></td>
      <td class="tc">100%</td>
    </tr>
  </tbody>
</table>

<!-- ===================================================
     SECTION 3: SUMMARY PER SKU
     =================================================== -->
<table>
  <colgroup>
    <col width="45">
    <col width="180">
    <col width="280">
    <col width="120">
    <col width="120">
  </colgroup>
  <thead>
    <tr>
      <th colspan="5" class="h-main">RINGKASAN PER SKU SPECIAL</th>
    </tr>
    <tr>
      <th class="h-sub">#</th>
      <th class="h-sub">SKU Produk</th>
      <th class="h-sub">Nama SKU</th>
      <th class="h-sub">Total Unit</th>
      <th class="h-sub">Jumlah Resi</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($summary)): $i = 1; foreach($summary as $row):
      $rowAlt = ($i % 2 == 0) ? 'row-alt' : '';
    ?>
    <tr class="<?= $rowAlt ?>">
      <td class="tc pct"><?= $i++ ?></td>
      <td class="bold" style="color: #4a148c;"><?= htmlspecialchars($row->sku) ?></td>
      <td><?= htmlspecialchars($row->nama_sku) ?></td>
      <td class="tc bold"><?= number_format($row->total_qty) ?></td>
      <td class="tc"><?= number_format($row->total_resi) ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr>
      <td colspan="5" class="tc">Tidak ada data.</td>
    </tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- ===================================================
     SECTION 4: DETAILED TRANSACTION ROWS
     =================================================== -->
<table>
  <colgroup>
    <col width="45">
    <col width="150">
    <col width="180">
    <col width="180">
    <col width="70">
    <col width="150">
    <col width="120">
    <col width="150">
    <col width="130">
  </colgroup>
  <thead>
    <tr>
      <th colspan="9" class="h-blue">DETAIL TRANSAKSI RESI (SKU SPECIAL)</th>
    </tr>
    <tr>
      <th class="h-sub">#</th>
      <th class="h-sub">Tanggal / Jam</th>
      <th class="h-sub">No Resi</th>
      <th class="h-sub">SKU</th>
      <th class="h-sub">Qty</th>
      <th class="h-sub">Marketplace</th>
      <th class="h-sub">Kurir</th>
      <th class="h-sub">Kategori Resi</th>
      <th class="h-sub">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if(!empty($list_data)): $i = 1; foreach($list_data as $row):
      $created_at  = is_array($row) ? ($row['created_at']    ?? '') : ($row->created_at    ?? '');
      $noresi      = is_array($row) ? ($row['noresi']        ?? '') : ($row->noresi        ?? '');
      $sku         = is_array($row) ? ($row['sku']           ?? '') : ($row->sku           ?? '');
      $jumlah      = is_array($row) ? ($row['jumlah']        ?? '') : ($row->jumlah        ?? '');
      $marketplace = is_array($row) ? ($row['nama_marketplace'] ?? '') : ($row->nama_marketplace ?? '');
      $kurir       = is_array($row) ? ($row['nama_kurir']    ?? '') : ($row->nama_kurir    ?? '');
      $kategori    = is_array($row) ? ($row['kategori_resi'] ?? '-') : ($row->kategori_resi ?? '-');
      $status      = is_array($row) ? ($row['status_pesanan'] ?? '') : ($row->status_pesanan ?? '');

      // Apply row highlights based on categories
      $cellClass = 'cell-banyak';
      $katLo = strtolower($kategori);
      if (strpos($katLo, 'special') !== false) {
          $cellClass = 'cell-spec';
      } elseif (strpos($katLo, '1 sku') !== false) {
          $cellClass = 'cell-1sku';
      } elseif (strpos($katLo, '2-9') !== false) {
          $cellClass = 'cell-29sku';
      }
    ?>
    <tr>
      <td class="tc pct"><?= $i++ ?></td>
      <td class="tc"><?= !empty($created_at) ? date('d/m/Y H:i:s', strtotime($created_at)) : '-' ?></td>
      <td><?= htmlspecialchars($noresi) ?></td>
      <td class="bold" style="color: #4a148c;"><?= htmlspecialchars($sku) ?></td>
      <td class="tc bold"><?= (int)$jumlah ?></td>
      <td><?= htmlspecialchars($marketplace) ?></td>
      <td><?= htmlspecialchars($kurir) ?></td>
      <td class="<?= $cellClass ?> bold"><?= htmlspecialchars($kategori) ?></td>
      <td class="tc"><?= htmlspecialchars($status) ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr>
      <td colspan="9" class="tc">Tidak ada data.</td>
    </tr>
    <?php endif; ?>
  </tbody>
</table>
