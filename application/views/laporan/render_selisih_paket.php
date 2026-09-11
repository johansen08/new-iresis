<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; padding: 24px; width: 800px; }
  .header { text-align: center; margin-bottom: 20px; }
  .header h1 { font-size: 20px; color: #1e293b; margin-bottom: 4px; }
  .header .date { font-size: 13px; color: #64748b; }
  .stats-row { display: flex; gap: 16px; margin-bottom: 20px; }
  .stat-box { flex: 1; border-radius: 12px; padding: 16px; text-align: center; }
  .stat-box.blue { background: #eff6ff; border: 2px solid #3b82f6; }
  .stat-box.red { background: #fef2f2; border: 2px solid #ef4444; }
  .stat-box.yellow { background: #fffbeb; border: 2px solid #f59e0b; }
  .stat-box .value { font-size: 28px; font-weight: 800; }
  .stat-box.blue .value { color: #1d4ed8; }
  .stat-box.red .value { color: #dc2626; }
  .stat-box.yellow .value { color: #d97706; }
  .stat-box .label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
  .section { margin-bottom: 16px; }
  .section-title { font-size: 14px; font-weight: 700; padding: 8px 12px; border-radius: 8px 8px 0 0; color: #fff; }
  .section-title.wajib { background: #dc2626; }
  .section-title.sisa { background: #f59e0b; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #f1f5f9; padding: 8px 12px; text-align: left; font-size: 12px; color: #475569; border-bottom: 2px solid #e2e8f0; }
  th.num { text-align: right; }
  td { padding: 7px 12px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
  td.num { text-align: right; font-weight: 600; }
  td.warn { color: #dc2626; }
  td.ok { color: #16a34a; }
  tr.highlight { background: #f0fdf4; }
  tr.highlight td { font-weight: 700; }
  tr.total { background: #f8fafc; }
  tr.total td { font-weight: 700; border-top: 2px solid #cbd5e1; }
  .watermark { text-align: center; color: #94a3b8; font-size: 11px; margin-top: 12px; }
</style>
</head>
<body>
  <div class="header">
    <h1>📋 MONITORING SELISIH HARI INI</h1>
    <div class="date"><?= date('d F Y') ?> — <?= date('H:i') ?> WIB</div>
  </div>

  <?php
    $total_resi = $top_stats['total_resi'] ?? 0;
    $wajib_resi = $top_stats['wajib_resi'] ?? 0;
    $selisih    = $total_resi - $wajib_resi;
  ?>

  <div class="stats-row">
    <div class="stat-box blue">
      <div class="value"><?= number_format($total_resi) ?></div>
      <div class="label">Total Resi</div>
    </div>
    <div class="stat-box red">
      <div class="value"><?= number_format($wajib_resi) ?></div>
      <div class="label">Wajib Hari Ini</div>
    </div>
    <div class="stat-box yellow">
      <div class="value"><?= number_format($selisih) ?></div>
      <div class="label">Selisih</div>
    </div>
  </div>

  <?php
    function render_pool_table($pools) {
      $labels = [
        'pool1' => 'Resi ke Picker',
        'pool2' => 'Picker ke Packer',
        'pool3' => '⭐ Total Pick + Pack',
        'pool4' => 'Packer ke HO',
        'pool5' => '🚨 Total Belum HO',
      ];
      echo '<table>';
      echo '<tr><th>Tahap</th><th class="num">Total</th><th class="num">Special</th><th class="num">1 SKU</th><th class="num">2-9 SKU</th><th class="num">QTY &gt;9</th></tr>';
      foreach ($labels as $key => $label) {
        $p = $pools[$key];
        $cls = ($key === 'pool3' || $key === 'pool5') ? ' highlight' : '';
        $total = (int)($p['total_resi'] ?? 0);
        echo "<tr class=\"{$cls}\">";
        echo "<td>{$label}</td>";
        echo '<td class="num ' . ($total > 0 ? 'warn' : 'ok') . '">' . number_format($total) . '</td>';
        echo '<td class="num">' . (int)($p['sku_special'] ?? 0) . '</td>';
        echo '<td class="num">' . (int)($p['resi_1_sku_sd_9'] ?? 0) . '</td>';
        echo '<td class="num">' . (int)($p['resi_2_9_sku_sd_9'] ?? 0) . '</td>';
        echo '<td class="num">' . (int)($p['resi_qty_banyak'] ?? 0) . '</td>';
        echo '</tr>';
      }
      echo '</table>';
    }
  ?>

  <div class="section">
    <div class="section-title wajib">🔴 WAJIB HARI INI (Deadline hari ini + tunggakan)</div>
    <?php render_pool_table($wajib); ?>
  </div>

  <div class="section">
    <div class="section-title sisa">🟡 SISA / NON-WAJIB (Antrian selain wajib)</div>
    <?php render_pool_table($sisa); ?>
  </div>

  <div class="watermark">Laporan otomatis IRESIS</div>
</body>
</html>
