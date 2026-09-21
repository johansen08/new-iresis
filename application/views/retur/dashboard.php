<div class="retur-dash">

  <!-- Header -->
  <div class="rd-header">
    <div>
      <h2 class="rd-title"><i class="fa fa-bar-chart"></i> Dashboard Manager &ndash; Tim Retur</h2>
      <p class="rd-sub" id="rd_updated_at">Ringkasan eksekutif: status operasional, kinerja, dan risiko retur</p>
    </div>
    <div class="rd-filter">
      <input type="text" id="rd_range" class="form-control" readonly style="width:230px;" />
      <button type="button" class="btn btn-primary" id="rd_refresh"><i class="fa fa-refresh"></i> Muat</button>
      <button type="button" class="btn btn-success" id="rd_screenshot"><i class="fa fa-camera"></i> Simpan</button>
    </div>
  </div>

  <!-- Alert Overdue -->
  <div class="rd-alert-bar" id="rd_alertbar" style="display:none"></div>

  <!-- Action bar: Perlu dikerjakan -->
  <div class="rd-actbar">
    <div class="rd-actbar-title"><i class="fa fa-bolt"></i> Yang Perlu Segera Dikerjakan</div>
    <div class="rd-actbar-items">
      <div class="rd-act rd-act-red">
        <div class="rd-act-ico"><i class="fa fa-truck"></i></div>
        <div>
          <div class="rd-act-val" id="act_terima">-</div>
          <div class="rd-act-lbl">Retur Online &ndash; Perlu DITERIMA (periode ini)</div>
        </div>
      </div>
      <div class="rd-act rd-act-amber">
        <div class="rd-act-ico"><i class="fa fa-folder-open"></i></div>
        <div>
          <div class="rd-act-val" id="act_buka">-</div>
          <div class="rd-act-lbl">Sudah Terima &ndash; Perlu DIBUKA &amp; Dicatat SKU</div>
        </div>
      </div>
      <div class="rd-act rd-act-danger">
        <div class="rd-act-ico"><i class="fa fa-clock-o"></i></div>
        <div>
          <div class="rd-act-val" id="act_overdue">-</div>
          <div class="rd-act-lbl">OVERDUE &gt;48 jam &ndash; Belum Dibuka (all time)</div>
        </div>
      </div>
      <div class="rd-act rd-act-dark">
        <div class="rd-act-ico"><i class="fa fa-archive"></i></div>
        <div>
          <div class="rd-act-val" id="act_tadro_all">-</div>
          <div class="rd-act-lbl">Retur Online Belum Diterima (semua waktu)</div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Row 1: Operasional -->
  <div class="rd-kpis rd-kpis-6">
    <div class="rd-card rd-red">
      <div class="rd-card-ico"><i class="fa fa-inbox"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_total">-</div>
        <div class="rd-card-lbl">Total Retur Masuk</div>
      </div>
    </div>
    <div class="rd-card rd-amber">
      <div class="rd-card-ico"><i class="fa fa-hourglass-half"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_belum">-</div>
        <div class="rd-card-lbl">Belum Dibuka</div>
      </div>
    </div>
    <div class="rd-card rd-green">
      <div class="rd-card-ico"><i class="fa fa-check-circle"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_buka">-</div>
        <div class="rd-card-lbl">Selesai Dibuka</div>
      </div>
    </div>
    <div class="rd-card rd-indigo">
      <div class="rd-card-ico"><i class="fa fa-percent"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_rate">-<small>%</small></div>
        <div class="rd-card-lbl">Tingkat Penyelesaian</div>
        <div class="rd-progress-bar"><div class="rd-progress-fill" id="kpi_rate_bar" style="width:0%"></div></div>
      </div>
    </div>
    <div class="rd-card rd-teal">
      <div class="rd-card-ico"><i class="fa fa-clock-o"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_avg_jam">-<small> jam</small></div>
        <div class="rd-card-lbl">Rata-rata Waktu Proses</div>
      </div>
    </div>
    <div class="rd-card rd-purple">
      <div class="rd-card-ico"><i class="fa fa-comments"></i></div>
      <div class="rd-card-body">
        <div class="rd-card-val" id="kpi_complain">-</div>
        <div class="rd-card-lbl">Komplain</div>
      </div>
    </div>
  </div>

  <!-- KPI Row 2: Keuangan -->
  <div class="rd-kpis rd-kpis-2" style="margin-bottom:14px;">
    <div class="rd-card rd-fin">
      <div class="rd-card-ico rd-fin-ico"><i class="fa fa-money"></i></div>
      <div class="rd-card-body">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;letter-spacing:.4px;margin-bottom:4px;">Total Tagihan Retur (Periode)</div>
        <div class="rd-card-val rd-rp" id="kpi_tagihan" style="font-size:26px;">-</div>
      </div>
    </div>
    <div class="rd-card rd-fin">
      <div class="rd-card-ico rd-fin-ico" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="fa fa-calculator"></i></div>
      <div class="rd-card-body">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;letter-spacing:.4px;margin-bottom:4px;">Rata-rata Nilai per Retur yang Dibuka</div>
        <div class="rd-card-val rd-rp" id="kpi_tagihan_rata" style="font-size:26px;">-</div>
      </div>
    </div>
  </div>

  <!-- Row: Tren + Status Buka -->
  <div class="rd-row rd-row-60-40">
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-line-chart"></i> Tren Harian (Terima vs Buka)
        <span class="rd-legend"><span class="rd-dot rd-d-blue"></span>Terima <span class="rd-dot rd-d-green"></span>Buka</span>
      </div>
      <div class="rd-panel-body"><div id="rd_trend" class="rd-trend"></div></div>
    </div>
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-pie-chart"></i> Status Buka Retur</div>
      <div class="rd-panel-body"><div id="rd_status" class="rd-bars"></div></div>
    </div>
  </div>

  <!-- Row: Jam Puncak + Top SKU -->
  <div class="rd-row rd-row-50-50">
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-bar-chart"></i> Pola Retur Masuk per Jam (Jam Kerja)</div>
      <div class="rd-panel-body"><div id="rd_hourly" class="rd-hourly"></div></div>
    </div>
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-cubes"></i> Top 10 SKU Retur Terbanyak</div>
      <div class="rd-panel-body"><div id="rd_sku" class="rd-bars"></div></div>
    </div>
  </div>

  <!-- Row: Kurir + Marketplace + Komplain -->
  <div class="rd-row rd-row-3">
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-truck"></i> Retur per Kurir</div>
      <div class="rd-panel-body"><div id="rd_kurir" class="rd-bars"></div></div>
    </div>
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-shopping-bag"></i> Retur per Marketplace</div>
      <div class="rd-panel-body"><div id="rd_mp" class="rd-bars"></div></div>
    </div>
    <div class="rd-panel">
      <div class="rd-panel-head"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i> Jenis Komplain</div>
      <div class="rd-panel-body"><div id="rd_complain_type" class="rd-bars"></div></div>
    </div>
  </div>

  <!-- Tabel: Resi Terlama Menunggu -->
  <div class="rd-panel" style="margin-bottom:16px;">
    <div class="rd-panel-head" style="display:flex;justify-content:space-between;align-items:center;">
      <span><i class="fa fa-warning" style="color:#ef4444;"></i> Resi Paling Lama Menunggu Dibuka (Top 10)</span>
      <span style="font-size:11px;color:#94a3b8;font-weight:400;">Diurutkan dari yang terlama</span>
    </div>
    <div class="rd-panel-body" style="padding:0 0 6px;">
      <table class="rd-pending-tbl">
        <thead>
          <tr>
            <th>#</th>
            <th>No. Resi</th>
            <th>Tanggal Diterima</th>
            <th>Kurir</th>
            <th>Lama Menunggu</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="rd_pending_tbody">
          <tr><td colspan="6" class="rd-loading"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- .retur-dash -->

<style>
/* Font Inter sudah dimuat lokal oleh main.php (assets/css/fonts-lokal.css) */
.retur-dash { font-family:'Inter',sans-serif; color:#1e293b; padding:4px 2px 30px; }
.retur-dash * { box-sizing:border-box; }
.rd-header { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px; margin-bottom:14px; }
.rd-title { margin:0; font-weight:800; font-size:22px; color:#0f172a; letter-spacing:-.5px; }
.rd-title .fa { color:#6366f1; margin-right:8px; }
.rd-sub { margin:2px 0 0; color:#64748b; font-size:12px; }
.rd-filter { display:flex; gap:8px; align-items:center; }
.rd-filter #rd_range { background:#fff; cursor:pointer; border:1px solid #e2e8f0; border-radius:8px; }

/* Alert */
.rd-alert-bar { background:linear-gradient(90deg,#fef2f2,#fff8f8); border:1px solid #fca5a5; border-left:5px solid #ef4444; border-radius:12px; padding:11px 18px; margin-bottom:14px; font-size:13px; font-weight:600; color:#7f1d1d; display:flex; align-items:center; gap:10px; animation:rdPulse 2s infinite; }
@keyframes rdPulse { 0%,100%{border-left-color:#ef4444;} 50%{border-left-color:#dc2626;} }

/* Action Bar */
.rd-actbar { background:#fff; border:1px solid #e8edf5; border-left:5px solid #6366f1; border-radius:14px; padding:14px 18px; margin-bottom:14px; box-shadow:0 2px 6px rgba(15,23,42,.05); }
.rd-actbar-title { font-weight:800; font-size:14px; color:#0f172a; margin-bottom:12px; }
.rd-actbar-title .fa { color:#6366f1; margin-right:6px; }
.rd-actbar-items { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
@media(max-width:1000px){ .rd-actbar-items{ grid-template-columns:repeat(2,1fr);} }
@media(max-width:600px){ .rd-actbar-items{ grid-template-columns:1fr;} }
.rd-act { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:12px; color:#fff; transition:transform .2s; }
.rd-act:hover { transform:scale(1.02); }
.rd-act-red    { background:linear-gradient(135deg,#f43f5e,#e11d48); }
.rd-act-amber  { background:linear-gradient(135deg,#f59e0b,#d97706); }
.rd-act-danger { background:linear-gradient(135deg,#dc2626,#991b1b); }
.rd-act-dark   { background:linear-gradient(135deg,#475569,#334155); }
.rd-act-ico { width:46px; height:46px; border-radius:11px; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.rd-act-val { font-size:30px; font-weight:900; line-height:1; }
.rd-act-lbl { font-size:11px; font-weight:600; opacity:.9; margin-top:4px; text-transform:uppercase; letter-spacing:.3px; }

/* KPI Cards */
.rd-kpis { display:grid; gap:12px; margin-bottom:12px; }
.rd-kpis-6 { grid-template-columns:repeat(6,1fr); }
.rd-kpis-2 { grid-template-columns:repeat(2,1fr); }
@media(max-width:1200px){ .rd-kpis-6{ grid-template-columns:repeat(3,1fr);} }
@media(max-width:700px){ .rd-kpis-6{ grid-template-columns:repeat(2,1fr);} .rd-kpis-2{ grid-template-columns:1fr;} }
.rd-card { display:flex; align-items:center; gap:14px; background:#fff; border:1px solid #eef2f7; border-radius:14px; padding:14px 16px; box-shadow:0 1px 5px rgba(15,23,42,.05); transition:transform .15s,box-shadow .15s; }
.rd-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(15,23,42,.09); }
.rd-card-ico { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; flex-shrink:0; }
.rd-card-val { font-size:22px; font-weight:800; line-height:1.1; color:#0f172a; }
.rd-card-val small { font-size:13px; font-weight:600; color:#64748b; }
.rd-card-lbl { font-size:11px; color:#64748b; margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:.35px; }
.rd-red    .rd-card-ico { background:linear-gradient(135deg,#f43f5e,#e11d48); }
.rd-amber  .rd-card-ico { background:linear-gradient(135deg,#f59e0b,#d97706); }
.rd-green  .rd-card-ico { background:linear-gradient(135deg,#22c55e,#16a34a); }
.rd-indigo .rd-card-ico { background:linear-gradient(135deg,#6366f1,#4f46e5); }
.rd-teal   .rd-card-ico { background:linear-gradient(135deg,#14b8a6,#0d9488); }
.rd-purple .rd-card-ico { background:linear-gradient(135deg,#a855f7,#7c3aed); }
.rd-fin    .rd-card-ico, .rd-fin-ico { background:linear-gradient(135deg,#14b8a6,#0d9488); font-size:26px; }
.rd-progress-bar { height:5px; background:#e2e8f0; border-radius:4px; margin-top:6px; overflow:hidden; }
.rd-progress-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#818cf8,#6366f1); transition:width .7s; }

/* Panels */
.rd-row { display:grid; gap:12px; margin-bottom:12px; }
.rd-row-60-40 { grid-template-columns:60fr 40fr; }
.rd-row-50-50 { grid-template-columns:1fr 1fr; }
.rd-row-3     { grid-template-columns:1fr 1fr 1fr; }
@media(max-width:1100px){ .rd-row-60-40,.rd-row-50-50,.rd-row-3{ grid-template-columns:1fr;} }
.rd-panel { background:#fff; border:1px solid #eef2f7; border-radius:14px; box-shadow:0 1px 5px rgba(15,23,42,.05); display:flex; flex-direction:column; }
.rd-panel-head { padding:12px 18px; font-weight:700; font-size:13px; color:#0f172a; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:6px; }
.rd-panel-head .fa { color:#3b82f6; }
.rd-panel-body { padding:14px 18px; flex:1; }
.rd-legend { margin-left:auto; font-size:11px; font-weight:600; color:#64748b; }
.rd-dot { display:inline-block; width:9px; height:9px; border-radius:2px; margin:0 3px 0 8px; vertical-align:middle; }
.rd-d-blue{ background:#3b82f6;} .rd-d-green{ background:#22c55e;}

/* Trend bars */
.rd-trend { display:flex; align-items:flex-end; gap:7px; height:200px; overflow-x:auto; padding-top:10px; }
.rd-tcol { display:flex; flex-direction:column; align-items:center; gap:5px; min-width:28px; }
.rd-tbars { display:flex; align-items:flex-end; gap:2px; height:170px; }
.rd-tbar { width:10px; border-radius:4px 4px 0 0; transition:height .4s; min-height:2px; }
.rd-tbar.t{ background:linear-gradient(180deg,#93c5fd,#3b82f6);}
.rd-tbar.b{ background:linear-gradient(180deg,#86efac,#22c55e);}
.rd-tlbl { font-size:9px; color:#94a3b8; white-space:nowrap; }

/* Horizontal bars */
.rd-bars { display:flex; flex-direction:column; gap:9px; }
.rd-bar-row .rd-bar-top { display:flex; justify-content:space-between; font-size:11px; margin-bottom:3px; }
.rd-bar-top .l { color:#334155; font-weight:600; max-width:72%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rd-bar-top .v { color:#0f172a; font-weight:700; }
.rd-bar-track { height:8px; background:#f1f5f9; border-radius:6px; overflow:hidden; }
.rd-bar-fill { height:100%; border-radius:6px; transition:width .5s; }
.rd-empty { color:#94a3b8; font-size:12px; text-align:center; padding:24px 0; }
.rd-loading { color:#94a3b8; font-size:12px; text-align:center; padding:20px 0; }

/* Hourly bar chart */
.rd-hourly { display:flex; align-items:flex-end; gap:4px; height:120px; padding-top:8px; }
.rd-hcol { display:flex; flex-direction:column; align-items:center; gap:3px; flex:1; }
.rd-hbar { width:100%; border-radius:4px 4px 0 0; transition:height .4s; min-height:2px; }
.rd-hlbl { font-size:8px; color:#94a3b8; white-space:nowrap; }

/* Pending table */
.rd-pending-tbl { width:100%; border-collapse:collapse; font-size:12px; }
.rd-pending-tbl thead tr { background:#f8fafc; }
.rd-pending-tbl th { padding:9px 14px; text-align:left; font-weight:700; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:.35px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.rd-pending-tbl td { padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
.rd-pending-tbl tr:last-child td { border-bottom:none; }
.rd-pending-tbl tr:hover td { background:#f8fafc; }
.rd-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.rd-badge-red   { background:#fef2f2; color:#dc2626; border:1px solid #fca5a5; }
.rd-badge-amber { background:#fffbeb; color:#b45309; border:1px solid #fcd34d; }
.rd-badge-ok    { background:#f0fdf4; color:#16a34a; border:1px solid #86efac; }
.rd-rp { font-family:'Inter',sans-serif; }
</style>

<script type="text/javascript">
$(document).ready(function() {
  var BASE = '<?= rtrim(base_url(), "/") ?>/';

  // Date range picker
  $('#rd_range').daterangepicker({
    ranges: {
      'Hari Ini':   [moment().startOf('day'), moment().endOf('day')],
      '7 Hari':     [moment().subtract(6,'days').startOf('day'), moment().endOf('day')],
      '30 Hari':    [moment().subtract(29,'days').startOf('day'), moment().endOf('day')],
      'Bulan Ini':  [moment().startOf('month'), moment().endOf('month')],
      'Bulan Lalu': [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
    },
    locale: {
      format:'YYYY-MM-DD', separator:' s/d ', applyLabel:'Terapkan', cancelLabel:'Batal',
      customRangeLabel:'Custom', firstDay:1,
      daysOfWeek:['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
      monthNames:['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
    },
    startDate: moment().startOf('month'),
    endDate:   moment().endOf('month')
  });

  // Helpers
  function rp(n)  { return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
  function num(n) { return Number(n||0).toLocaleString('id-ID'); }
  function esc(s) { return $('<span>').text(s).html(); }

  // Horizontal bar chart
  function renderHBars(el, data, color) {
    var $c = $(el).empty();
    if (!data || !data.length) { $c.html('<div class="rd-empty">Tidak ada data</div>'); return; }
    var max = Math.max.apply(null, data.map(function(d){ return d.n; })) || 1;
    data.forEach(function(d) {
      var pct = Math.round(d.n / max * 100);
      $c.append(
        '<div class="rd-bar-row">' +
          '<div class="rd-bar-top"><span class="l">' + esc(d.label) + '</span><span class="v">' + num(d.n) + '</span></div>' +
          '<div class="rd-bar-track"><div class="rd-bar-fill" style="width:' + pct + '%;background:' + color + '"></div></div>' +
        '</div>'
      );
    });
  }

  // Trend chart
  function renderTrend(el, data) {
    var $c = $(el).empty();
    if (!data || !data.length) { $c.html('<div class="rd-empty">Tidak ada data</div>'); return; }
    var max = 1;
    data.forEach(function(d) { max = Math.max(max, d.terima, d.buka); });
    data.forEach(function(d) {
      var ht = Math.max(2, Math.round(d.terima / max * 160));
      var hb = Math.max(2, Math.round(d.buka   / max * 160));
      $c.append(
        '<div class="rd-tcol">' +
          '<div class="rd-tbars">' +
            '<div class="rd-tbar t" style="height:' + ht + 'px" title="Terima: ' + d.terima + '"></div>' +
            '<div class="rd-tbar b" style="height:' + hb + 'px" title="Buka: '   + d.buka   + '"></div>' +
          '</div>' +
          '<div class="rd-tlbl">' + d.tgl + '</div>' +
        '</div>'
      );
    });
  }

  // Hourly heatmap bars
  function renderHourly(el, data) {
    var $c = $(el).empty();
    var max = Math.max.apply(null, data) || 1;
    var colors = ['#dbeafe','#93c5fd','#60a5fa','#3b82f6','#2563eb','#1d4ed8'];
    for (var h = 0; h < 24; h++) {
      var n  = data[h] || 0;
      var ht = Math.max(3, Math.round(n / max * 95));
      var ci = Math.min(5, Math.floor(n / max * 5.9));
      $c.append(
        '<div class="rd-hcol">' +
          '<div class="rd-hbar" style="height:' + ht + 'px;background:' + colors[ci] + '" title="Jam ' + h + ':00 — ' + n + ' retur"></div>' +
          '<div class="rd-hlbl">' + h + '</div>' +
        '</div>'
      );
    }
  }

  // Format jam tunggu ke teks
  function formatJam(j) {
    j = parseInt(j) || 0;
    if (j >= 24) { var d = Math.floor(j / 24); return d + ' hari ' + (j % 24) + ' jam'; }
    return j + ' jam';
  }

  // Badge status overdue
  function jamBadge(j) {
    j = parseInt(j) || 0;
    if (j >= 72) return '<span class="rd-badge rd-badge-red">KRITIS</span>';
    if (j >= 48) return '<span class="rd-badge rd-badge-amber">OVERDUE</span>';
    return '<span class="rd-badge rd-badge-ok">Normal</span>';
  }

  // Load dashboard data
  function loadDash() {
    var dates = $('#rd_range').val().split(' s/d ');
    var sd = (dates[0] || '') + ' 00:00:00';
    var ed = (dates[1] || '') + ' 23:59:59';

    $('#rd_trend,#rd_status,#rd_kurir,#rd_mp,#rd_sku,#rd_hourly,#rd_complain_type')
      .html('<div class="rd-loading"><i class="fa fa-spinner fa-spin"></i> Memuat...</div>');
    $('#rd_pending_tbody').html('<tr><td colspan="6" class="rd-loading"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>');
    $('#rd_alertbar').hide();

    $.ajax({
      url: BASE + 'retur/get-dashboard-data',
      type: 'POST',
      dataType: 'json',
      data: { start_date: sd, end_date: ed },
      success: function(res) {
        var s = res.summary || {};

        // Timestamp
        $('#rd_updated_at').text('Diperbarui: ' + moment().format('DD MMM YYYY HH:mm:ss'));

        // Action bar
        $('#act_terima').text(num(s.tadro));
        $('#act_buka').text(num(s.belum_dibuka));
        $('#act_overdue').text(num(s.overdue));
        $('#act_tadro_all').text(num(s.tadro_all));

        // KPI operasional
        $('#kpi_total').text(num(s.total_retur));
        $('#kpi_belum').text(num(s.belum_dibuka));
        $('#kpi_buka').text(num(s.buka));
        $('#kpi_avg_jam').html((s.avg_proses_jam || 0) + '<small> jam</small>');
        $('#kpi_complain').text(num(s.complain));

        // KPI keuangan
        $('#kpi_tagihan').text(rp(s.tagihan));
        $('#kpi_tagihan_rata').text(rp(s.tagihan_rata));

        // Completion rate
        var rate = parseFloat(s.completion_rate) || 0;
        $('#kpi_rate').html(rate + '<small>%</small>');
        $('#kpi_rate_bar').css('width', Math.min(100, rate) + '%');

        // Alert jika ada overdue
        if ((parseInt(s.overdue) || 0) > 0) {
          $('#rd_alertbar').html(
            '<i class="fa fa-exclamation-triangle"></i> PERHATIAN: Ada <strong>' +
            num(s.overdue) + '</strong> retur yang sudah diterima namun belum dibuka lebih dari 48 jam. Segera tindak lanjuti!'
          ).show();
        }

        // Charts
        renderTrend('#rd_trend', res.trend);
        renderHBars('#rd_status',        res.status_buka,   'linear-gradient(90deg,#fbbf24,#f59e0b)');
        renderHBars('#rd_kurir',         res.by_kurir,      'linear-gradient(90deg,#60a5fa,#3b82f6)');
        renderHBars('#rd_mp',            res.by_mp,         'linear-gradient(90deg,#34d399,#10b981)');
        renderHBars('#rd_sku',           res.top_sku,       'linear-gradient(90deg,#c084fc,#a855f7)');
        renderHBars('#rd_complain_type', res.complain_type, 'linear-gradient(90deg,#f87171,#ef4444)');
        renderHourly('#rd_hourly', res.hourly || []);

        // Tabel pending terlama
        var $tb = $('#rd_pending_tbody').empty();
        if (!res.oldest_pending || !res.oldest_pending.length) {
          $tb.html('<tr><td colspan="6" class="rd-empty"><i class="fa fa-check-circle" style="color:#22c55e;"></i> Tidak ada retur pending — semua sudah dibuka!</td></tr>');
        } else {
          res.oldest_pending.forEach(function(p, i) {
            $tb.append(
              '<tr>' +
                '<td>' + (i+1) + '</td>' +
                '<td><strong>' + esc(p.noresi) + '</strong></td>' +
                '<td>' + moment(p.tgl).format('DD MMM YYYY HH:mm') + '</td>' +
                '<td>' + esc(p.kurir) + '</td>' +
                '<td><strong>' + formatJam(p.jam_tunggu) + '</strong></td>' +
                '<td>' + jamBadge(p.jam_tunggu) + '</td>' +
              '</tr>'
            );
          });
        }
      },
      error: function() {
        $('#rd_trend,#rd_status,#rd_kurir,#rd_mp,#rd_sku,#rd_hourly,#rd_complain_type')
          .html('<div class="rd-empty">Gagal memuat data</div>');
        $('#rd_pending_tbody').html('<tr><td colspan="6" class="rd-empty">Gagal memuat</td></tr>');
      }
    });
  }

  $('#rd_refresh').on('click', loadDash);

  // Screenshot
  function ensureHtml2Canvas(cb) {
    if (window.html2canvas) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'assets/js/plugins/html2canvas/html2canvas.min.js';
    s.onload = function() { cb(); };
    s.onerror = function() { alert('Gagal memuat modul screenshot (perlu internet).'); };
    document.head.appendChild(s);
  }

  $('#rd_screenshot').on('click', function() {
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan...');
    ensureHtml2Canvas(function() {
      var node = document.querySelector('.retur-dash');
      $('.rd-filter').css('visibility', 'hidden');
      html2canvas(node, { scale: 2, backgroundColor: '#f8fafc', useCORS: true, logging: false })
        .then(function(canvas) {
          $('.rd-filter').css('visibility', 'visible');
          var link = document.createElement('a');
          link.download = 'Dashboard_Manager_Retur_' + moment().format('YYYYMMDD_HHmmss') + '.png';
          link.href = canvas.toDataURL('image/png');
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          $btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Simpan');
        })
        .catch(function() {
          $('.rd-filter').css('visibility', 'visible');
          $btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Simpan');
        });
    });
  });

  loadDash();
});
</script>
