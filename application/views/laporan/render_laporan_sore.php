<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; width: 900px; }

  .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid #1e3a5f; }
  .header-left h1 { font-size: 20px; font-weight: 700; color: #f8fafc; letter-spacing: 0.5px; }
  .header-left h1 span { color: #a78bfa; }
  .header-left .subtitle { font-size: 13px; color: #94a3b8; margin-top: 3px; }
  .header-right { text-align: right; }
  .header-right .time { font-size: 26px; font-weight: 700; color: #a78bfa; }
  .header-right .date { font-size: 13px; color: #94a3b8; margin-top: 2px; }

  .summary-row { display: flex; gap: 10px; margin-bottom: 18px; }
  .summary-card { flex: 1; background: #1e293b; border-radius: 10px; padding: 12px 16px; }
  .summary-card .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
  .summary-card .value { font-size: 24px; font-weight: 700; }
  .summary-card .sub { font-size: 11px; color: #64748b; margin-top: 3px; }
  .v-purple { color: #c4b5fd; }
  .v-green  { color: #4ade80; }
  .v-amber  { color: #fbbf24; }
  .v-red    { color: #f87171; }

  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
  .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; padding: 5px 10px; border-radius: 5px; }
  .t-picker  { background: rgba(167,139,250,.15); color: #c4b5fd; border-left: 3px solid #a78bfa; }
  .t-packer  { background: rgba(56,189,248,.15);  color: #7dd3fc; border-left: 3px solid #38bdf8; }
  .t-ho      { background: rgba(34,197,94,.15);   color: #86efac; border-left: 3px solid #22c55e; }
  .t-mp      { background: rgba(245,158,11,.15);  color: #fde68a; border-left: 3px solid #f59e0b; }
  .t-sisa    { background: rgba(239,68,68,.15);   color: #fca5a5; border-left: 3px solid #ef4444; }

  table { width: 100%; border-collapse: collapse; }
  th { background: #0f172a; color: #64748b; padding: 6px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #334155; }
  th.r { text-align: right; }
  td { padding: 7px 10px; font-size: 12px; border-bottom: 1px solid #1e293b; }
  td.r { text-align: right; font-weight: 700; }
  tr:last-child td { border-bottom: none; }

  .rank { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; font-size: 10px; font-weight: 700; margin-right: 6px; }
  .r1 { background: rgba(251,191,36,.2); color: #fbbf24; }
  .r2 { background: rgba(148,163,184,.15); color: #94a3b8; }
  .r3 { background: rgba(180,83,9,.15); color: #c2855a; }
  .rn { background: rgba(51,65,85,.5); color: #64748b; }

  .bar-wrap { display: flex; align-items: center; gap: 6px; }
  .bar-bg { flex: 1; background: #0f172a; border-radius: 3px; height: 4px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 3px; }
  .bar-pct { font-size: 10px; color: #64748b; width: 26px; text-align: right; flex-shrink: 0; }

  .mp-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
  .dot-shopee    { background: #ff5722; }
  .dot-tokopedia { background: #00aa5b; }
  .dot-lazada    { background: #4f8cf7; }
  .dot-tiktok    { background: #aaa; }
  .dot-default   { background: #64748b; }

  .total-row { background: #1e293b; }
  .total-row td { font-weight: 700; color: #4ade80; border-bottom: none; }

  .footer { text-align: center; color: #334155; font-size: 10px; margin-top: 16px; }
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <h1>🌆 LAPORAN PRODUKSI <span>SORE</span></h1>
    <div class="subtitle">Rekap akhir hari — picker, packer, HO & sisa wajib</div>
  </div>
  <div class="header-right">
    <div class="time"><?= date('H:i') ?> WIB</div>
    <div class="date"><?= date('l, d F Y') ?></div>
  </div>
</div>

<?php
  function mp_dot_sore($n) {
    $n = strtolower($n ?? '');
    if (strpos($n,'shopee')    !== false) return 'dot-shopee';
    if (strpos($n,'tokopedia') !== false) return 'dot-tokopedia';
    if (strpos($n,'lazada')    !== false) return 'dot-lazada';
    if (strpos($n,'tiktok')    !== false) return 'dot-tiktok';
    return 'dot-default';
  }

  $total_picker = array_sum(array_column((array)$pickers, 'total_resi'));
  $total_packer = array_sum(array_column((array)$packers, 'total_resi'));
  $total_ho     = (int)($total_ho ?? 0);
  $total_mp     = array_sum(array_column((array)$per_mp, 'total'));
  $total_sisa   = array_sum(array_column((array)$sisa_wajib, 'total'));

  $max_picker   = !empty($pickers)    ? max(array_column((array)$pickers,   'total_resi')) : 1;
  $max_packer   = !empty($packers)    ? max(array_column((array)$packers,   'total_resi')) : 1;
  $max_mp       = !empty($per_mp)     ? max(array_column((array)$per_mp,    'total'))      : 1;
  $max_sisa     = !empty($sisa_wajib) ? max(array_column((array)$sisa_wajib,'total'))      : 1;
?>

<!-- SUMMARY -->
<div class="summary-row">
  <div class="summary-card">
    <div class="label">🧺 Total Pick</div>
    <div class="value v-purple"><?= number_format($total_picker) ?></div>
    <div class="sub"><?= count((array)$pickers) ?> picker aktif</div>
  </div>
  <div class="summary-card">
    <div class="label">📦 Total Pack</div>
    <div class="value v-amber"><?= number_format($total_packer) ?></div>
    <div class="sub"><?= count((array)$packers) ?> packer aktif</div>
  </div>
  <div class="summary-card">
    <div class="label">🚚 Total HO</div>
    <div class="value v-green"><?= number_format($total_ho) ?></div>
    <div class="sub">resi handover</div>
  </div>
  <div class="summary-card">
    <div class="label">⚠️ Sisa Wajib</div>
    <div class="value v-red"><?= number_format($total_sisa) ?></div>
    <div class="sub">belum pack</div>
  </div>
</div>

<!-- PICKER + PACKER -->
<div class="two-col">
  <div>
    <div class="section-title t-picker">🧺 Picker — <?= number_format($total_picker) ?> resi</div>
    <table>
      <thead><tr><th>#</th><th>Nama</th><th class="r">Resi</th><th style="width:80px">Bar</th></tr></thead>
      <tbody>
        <?php foreach ($pickers as $i => $p): ?>
        <?php $pct = $max_picker > 0 ? round(($p->total_resi / $max_picker) * 100) : 0; ?>
        <tr>
          <td><span class="rank <?= $i==0?'r1':($i==1?'r2':($i==2?'r3':'rn')) ?>"><?= $i+1 ?></span></td>
          <td style="font-size:11px"><?= $p->nama_pegawai ?></td>
          <td class="r" style="color:#c4b5fd"><?= number_format($p->total_resi) ?></td>
          <td>
            <div class="bar-wrap">
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#a78bfa"></div></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pickers)): ?>
        <tr><td colspan="4" style="text-align:center;color:#64748b;padding:12px">Belum ada data</td></tr>
        <?php endif; ?>
        <tr class="total-row"><td colspan="2" style="color:#c4b5fd;font-size:11px">Total</td><td class="r" style="color:#c4b5fd"><?= number_format($total_picker) ?></td><td></td></tr>
      </tbody>
    </table>
  </div>

  <div>
    <div class="section-title t-packer">📦 Packer — <?= number_format($total_packer) ?> resi</div>
    <table>
      <thead><tr><th>#</th><th>Nama</th><th class="r">Resi</th><th style="width:80px">Bar</th></tr></thead>
      <tbody>
        <?php foreach ($packers as $i => $p): ?>
        <?php $pct = $max_packer > 0 ? round(($p->total_resi / $max_packer) * 100) : 0; ?>
        <tr>
          <td><span class="rank <?= $i==0?'r1':($i==1?'r2':($i==2?'r3':'rn')) ?>"><?= $i+1 ?></span></td>
          <td style="font-size:11px"><?= $p->nama_packer ?></td>
          <td class="r" style="color:#7dd3fc"><?= number_format($p->total_resi) ?></td>
          <td>
            <div class="bar-wrap">
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#38bdf8"></div></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($packers)): ?>
        <tr><td colspan="4" style="text-align:center;color:#64748b;padding:12px">Belum ada data</td></tr>
        <?php endif; ?>
        <tr class="total-row"><td colspan="2" style="color:#7dd3fc;font-size:11px">Total</td><td class="r" style="color:#7dd3fc"><?= number_format($total_packer) ?></td><td></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- HO per MP + SISA -->
<div class="two-col">
  <div>
    <div class="section-title t-mp">✅ HO per Marketplace — <?= number_format($total_mp) ?> resi</div>
    <table>
      <thead><tr><th>Marketplace</th><th class="r">HO</th><th style="width:90px">Proporsi</th></tr></thead>
      <tbody>
        <?php foreach ($per_mp as $row): ?>
        <?php $pct = $max_mp > 0 ? round(($row->total / $max_mp) * 100) : 0; ?>
        <tr>
          <td><span class="mp-dot <?= mp_dot_sore($row->nama_marketplace) ?>"></span><?= $row->nama_marketplace ?: 'Lainnya' ?></td>
          <td class="r" style="color:#fde68a"><?= number_format($row->total) ?></td>
          <td>
            <div class="bar-wrap">
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#f59e0b"></div></div>
              <span class="bar-pct"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row"><td>Total</td><td class="r"><?= number_format($total_mp) ?></td><td></td></tr>
      </tbody>
    </table>
  </div>

  <div>
    <div class="section-title t-sisa">⚠️ Sisa Wajib Belum Pack — <?= number_format($total_sisa) ?> resi</div>
    <table>
      <thead><tr><th>Marketplace</th><th class="r">Sisa</th><th style="width:90px">Proporsi</th></tr></thead>
      <tbody>
        <?php foreach ($sisa_wajib as $row): ?>
        <?php $pct = $max_sisa > 0 ? round(($row->total / $max_sisa) * 100) : 0; ?>
        <tr>
          <td><span class="mp-dot <?= mp_dot_sore($row->nama_marketplace) ?>"></span><?= $row->nama_marketplace ?: 'Lainnya' ?></td>
          <td class="r" style="color:#fca5a5"><?= number_format($row->total) ?></td>
          <td>
            <div class="bar-wrap">
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#ef4444"></div></div>
              <span class="bar-pct"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($sisa_wajib)): ?>
        <tr><td colspan="3" style="text-align:center;color:#4ade80;padding:12px">✅ Semua wajib sudah pack!</td></tr>
        <?php endif; ?>
        <?php if (!empty($sisa_wajib)): ?>
        <tr class="total-row"><td>Total sisa</td><td class="r" style="color:#f87171"><?= number_format($total_sisa) ?></td><td></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="footer">Laporan otomatis IRESIS — <?= date('d/m/Y H:i') ?> WIB</div>

</body>
</html>
