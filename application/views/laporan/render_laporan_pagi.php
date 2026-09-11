<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; width: 860px; }

  .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 2px solid #1e3a5f; }
  .header-left h1 { font-size: 20px; font-weight: 700; color: #f8fafc; letter-spacing: 0.5px; }
  .header-left h1 span { color: #f59e0b; }
  .header-left .subtitle { font-size: 13px; color: #94a3b8; margin-top: 3px; }
  .header-right { text-align: right; }
  .header-right .time { font-size: 26px; font-weight: 700; color: #f59e0b; }
  .header-right .date { font-size: 13px; color: #94a3b8; margin-top: 2px; }

  .section { margin-bottom: 20px; }
  .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 8px; }
  .section-title.backlog { background: rgba(239,68,68,0.15); color: #fca5a5; border-left: 3px solid #ef4444; }
  .section-title.wajib   { background: rgba(245,158,11,0.15); color: #fde68a; border-left: 3px solid #f59e0b; }

  table { width: 100%; border-collapse: collapse; }
  th { background: #1e293b; color: #94a3b8; padding: 9px 12px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #334155; }
  th.num { text-align: center; }
  td { padding: 9px 12px; font-size: 13px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
  td.num { text-align: center; font-weight: 700; }
  tr:hover { background: rgba(255,255,255,0.02); }

  .mp-name { display: flex; align-items: center; gap: 8px; }
  .mp-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .dot-shopee   { background: #ff5722; }
  .dot-tokopedia { background: #00aa5b; }
  .dot-lazada   { background: #0f146d; box-shadow: 0 0 0 1px #4f8cf7; }
  .dot-tiktok   { background: #010101; box-shadow: 0 0 0 1px #aaa; }
  .dot-default  { background: #64748b; }

  .bar-wrap { display: flex; align-items: center; gap: 8px; }
  .bar-bg { flex: 1; background: #1e293b; border-radius: 4px; height: 6px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 4px; }
  .bar-pct { font-size: 11px; color: #64748b; width: 32px; text-align: right; flex-shrink: 0; }

  .chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
  .chip-red  { background: rgba(239,68,68,0.15); color: #fca5a5; }
  .chip-amber { background: rgba(245,158,11,0.15); color: #fde68a; }
  .chip-green { background: rgba(34,197,94,0.15); color: #86efac; }

  .summary-row { display: flex; gap: 12px; margin-top: 20px; }
  .summary-card { flex: 1; background: #1e293b; border-radius: 10px; padding: 14px 18px; }
  .summary-card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
  .summary-card .value { font-size: 26px; font-weight: 700; }
  .summary-card .value.red   { color: #f87171; }
  .summary-card .value.amber { color: #fbbf24; }
  .summary-card .value.green { color: #4ade80; }
  .summary-card .sub { font-size: 12px; color: #64748b; margin-top: 4px; }

  .footer { text-align: center; color: #334155; font-size: 11px; margin-top: 18px; }
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <h1>🌅 LAPORAN PRODUKSI <span>PAGI</span></h1>
    <div class="subtitle">Rekapitulasi backlog & resi wajib hari ini</div>
  </div>
  <div class="header-right">
    <div class="time"><?= date('H:i') ?> WIB</div>
    <div class="date"><?= date('l, d F Y') ?></div>
  </div>
</div>

<?php
  // Helper: warna dot marketplace
  function mp_dot($nama) {
    $n = strtolower($nama ?? '');
    if (strpos($n,'shopee')    !== false) return 'dot-shopee';
    if (strpos($n,'tokopedia') !== false) return 'dot-tokopedia';
    if (strpos($n,'lazada')    !== false) return 'dot-lazada';
    if (strpos($n,'tiktok')    !== false) return 'dot-tiktok';
    return 'dot-default';
  }

  $total_backlog = array_sum(array_column($backlog, 'total'));
  $max_backlog   = $total_backlog > 0 ? max(array_column($backlog, 'total')) : 1;

  $total_wajib       = array_sum(array_column($wajib, 'total'));
  $total_belum_pick  = array_sum(array_column($wajib, 'belum_pick'));
  $total_belum_pack  = array_sum(array_column($wajib, 'belum_pack'));
  $total_sudah_pack  = array_sum(array_column($wajib, 'sudah_pack'));
  $max_wajib = $total_wajib > 0 ? max(array_column($wajib, 'total')) : 1;
?>

<!-- SUMMARY CARDS -->
<div class="summary-row">
  <div class="summary-card">
    <div class="label">📋 Backlog (5 hari)</div>
    <div class="value red"><?= number_format($total_backlog) ?></div>
    <div class="sub">resi belum selesai</div>
  </div>
  <div class="summary-card">
    <div class="label">🔴 Wajib Hari Ini</div>
    <div class="value amber"><?= number_format($total_wajib) ?></div>
    <div class="sub">per batas kirim</div>
  </div>
  <div class="summary-card">
    <div class="label">❌ Belum Pick</div>
    <div class="value red"><?= number_format($total_belum_pick) ?></div>
    <div class="sub">dari <?= number_format($total_wajib) ?> wajib</div>
  </div>
  <div class="summary-card">
    <div class="label">❌ Belum Pack</div>
    <div class="value amber"><?= number_format($total_belum_pack) ?></div>
    <div class="sub">dari <?= number_format($total_wajib) ?> wajib</div>
  </div>
</div>

<br>

<!-- BACKLOG TABLE -->
<div class="section">
  <div class="section-title backlog">📋 Resi Belum Selesai — 5 Hari ke Belakang</div>
  <table>
    <thead>
      <tr>
        <th>Marketplace</th>
        <th class="num">Jumlah Resi</th>
        <th style="width:260px">Proporsi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($backlog as $row): ?>
      <?php
        $pct = $max_backlog > 0 ? round(($row->total / $max_backlog) * 100) : 0;
        $color = $pct >= 80 ? '#ef4444' : ($pct >= 40 ? '#f59e0b' : '#3b82f6');
      ?>
      <tr>
        <td>
          <div class="mp-name">
            <div class="mp-dot <?= mp_dot($row->nama_marketplace) ?>"></div>
            <?= $row->nama_marketplace ?: 'Lainnya' ?>
          </div>
        </td>
        <td class="num"><?= number_format($row->total) ?></td>
        <td>
          <div class="bar-wrap">
            <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
            <div class="bar-pct"><?= $pct ?>%</div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($backlog)): ?>
      <tr><td colspan="3" style="text-align:center;color:#4ade80;padding:16px">✅ Tidak ada backlog</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- WAJIB TABLE -->
<div class="section">
  <div class="section-title wajib">🔴 Resi Wajib Dikerjakan Hari Ini (per Batas Kirim)</div>
  <table>
    <thead>
      <tr>
        <th>Marketplace</th>
        <th class="num">Total</th>
        <th class="num">Belum Pick</th>
        <th class="num">Belum Pack</th>
        <th class="num">Sudah Pack</th>
        <th style="width:160px">Progress</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($wajib as $row): ?>
      <?php
        $selesai = $row->sudah_pack;
        $pct_done = $row->total > 0 ? round(($selesai / $row->total) * 100) : 0;
        $bar_color = $pct_done >= 80 ? '#4ade80' : ($pct_done >= 40 ? '#fbbf24' : '#f87171');
      ?>
      <tr>
        <td>
          <div class="mp-name">
            <div class="mp-dot <?= mp_dot($row->nama_marketplace) ?>"></div>
            <?= $row->nama_marketplace ?: 'Lainnya' ?>
          </div>
        </td>
        <td class="num"><?= number_format($row->total) ?></td>
        <td class="num">
          <?php if ($row->belum_pick > 0): ?>
            <span class="chip chip-red">❌ <?= $row->belum_pick ?></span>
          <?php else: ?>
            <span class="chip chip-green">✅ 0</span>
          <?php endif; ?>
        </td>
        <td class="num">
          <?php if ($row->belum_pack > 0): ?>
            <span class="chip chip-amber">⏳ <?= $row->belum_pack ?></span>
          <?php else: ?>
            <span class="chip chip-green">✅ 0</span>
          <?php endif; ?>
        </td>
        <td class="num"><span class="chip chip-green">✅ <?= $row->sudah_pack ?></span></td>
        <td>
          <div class="bar-wrap">
            <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct_done ?>%;background:<?= $bar_color ?>"></div></div>
            <div class="bar-pct"><?= $pct_done ?>%</div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($wajib)): ?>
      <tr><td colspan="6" style="text-align:center;color:#4ade80;padding:16px">✅ Semua resi wajib sudah beres!</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="footer">Laporan otomatis IRESIS — <?= date('d/m/Y H:i') ?> WIB</div>

</body>
</html>
