<div class="retur-dash">

  <!-- Header -->
  <div class="rd-header">
    <div>
      <h2 class="rd-title">Dashboard Tim Retur</h2>
      <p class="rd-sub">Ringkasan aktivitas retur, buka retur, komplain &amp; nilai tagihan</p>
    </div>
    <div class="rd-filter">
      <input type="text" id="rd_range" class="form-control" readonly />
      <button type="button" class="btn btn-primary" id="rd_refresh"><i class="fa fa-refresh"></i> Muat</button>
      <button type="button" class="btn btn-success" id="rd_screenshot"><i class="fa fa-camera"></i> Download Gambar</button>
    </div>
  </div>

  <!-- Action Panel: Yang Perlu Dikerjakan -->
  <div class="rd-actbar">
    <div class="rd-actbar-title"><i class="fa fa-bolt"></i> Yang Perlu Dikerjakan</div>
    <div class="rd-actbar-items">
      <div class="rd-act rd-act-red">
        <div class="rd-act-ico"><i class="fa fa-truck"></i></div>
        <div><div class="rd-act-val" id="act_terima">-</div><div class="rd-act-lbl">Perlu DITERIMA (Retur Tadro)</div></div>
      </div>
      <div class="rd-act rd-act-amber">
        <div class="rd-act-ico"><i class="fa fa-folder-open"></i></div>
        <div><div class="rd-act-val" id="act_buka">-</div><div class="rd-act-lbl">Perlu DIBUKA &amp; beri keterangan SKU</div></div>
      </div>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="rd-kpis">
    <div class="rd-card rd-red">
      <div class="rd-card-ico"><i class="fa fa-hourglass-start"></i></div>
      <div class="rd-card-body"><div class="rd-card-val" id="kpi_tadro">-</div><div class="rd-card-lbl">Retur Tadro</div></div>
    </div>
    <div class="rd-card rd-amber">
      <div class="rd-card-ico"><i class="fa fa-hourglass-half"></i></div>
      <div class="rd-card-body"><div class="rd-card-val" id="kpi_belum">-</div><div class="rd-card-lbl">Belum Dibuka</div></div>
    </div>
    <div class="rd-card rd-green">
      <div class="rd-card-ico"><i class="fa fa-folder-open"></i></div>
      <div class="rd-card-body"><div class="rd-card-val" id="kpi_buka">-</div><div class="rd-card-lbl">Buka Retur</div></div>
    </div>
    <div class="rd-card rd-purple">
      <div class="rd-card-ico"><i class="fa fa-comments"></i></div>
      <div class="rd-card-body"><div class="rd-card-val" id="kpi_complain">-</div><div class="rd-card-lbl">Komplain</div></div>
    </div>
    <div class="rd-card rd-teal">
      <div class="rd-card-ico"><i class="fa fa-money"></i></div>
      <div class="rd-card-body"><div class="rd-card-val rd-rp" id="kpi_tagihan">-</div><div class="rd-card-lbl">Total Tagihan</div></div>
    </div>
  </div>

  <!-- Row: Trend + Status Buka -->
  <div class="rd-row">
    <div class="rd-panel rd-2">
      <div class="rd-panel-head"><i class="fa fa-line-chart"></i> Tren Harian (Terima vs Buka)
        <span class="rd-legend"><span class="rd-dot rd-d-blue"></span>Terima <span class="rd-dot rd-d-green"></span>Buka</span>
      </div>
      <div class="rd-panel-body"><div id="rd_trend" class="rd-trend"></div></div>
    </div>
    <div class="rd-panel rd-1">
      <div class="rd-panel-head"><i class="fa fa-pie-chart"></i> Status Buka Retur</div>
      <div class="rd-panel-body"><div id="rd_status" class="rd-bars"></div></div>
    </div>
  </div>

  <!-- Row: Kurir + Marketplace -->
  <div class="rd-row">
    <div class="rd-panel rd-1">
      <div class="rd-panel-head"><i class="fa fa-truck"></i> Retur per Kurir</div>
      <div class="rd-panel-body"><div id="rd_kurir" class="rd-bars"></div></div>
    </div>
    <div class="rd-panel rd-1">
      <div class="rd-panel-head"><i class="fa fa-shopping-bag"></i> Retur per Marketplace</div>
      <div class="rd-panel-body"><div id="rd_mp" class="rd-bars"></div></div>
    </div>
    <div class="rd-panel rd-1">
      <div class="rd-panel-head"><i class="fa fa-cubes"></i> Top SKU Retur</div>
      <div class="rd-panel-body"><div id="rd_sku" class="rd-bars"></div></div>
    </div>
  </div>

</div>

<style>
.retur-dash { font-family:'Inter',sans-serif; color:#1e293b; padding:4px 2px 24px; }
.retur-dash * { box-sizing:border-box; }
.rd-header { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
.rd-title { margin:0; font-weight:800; font-size:22px; color:#0f172a; letter-spacing:-.5px; }
.rd-sub { margin:2px 0 0; color:#64748b; font-size:13px; }
.rd-filter { display:flex; gap:8px; align-items:center; }
.rd-filter #rd_range { width:240px; background:#fff; cursor:pointer; border:1px solid #e2e8f0; border-radius:8px; }

/* Action bar */
.rd-actbar { background:#fff; border:1px solid #eef2f7; border-left:5px solid #6366f1; border-radius:14px; padding:14px 18px; margin-bottom:16px; box-shadow:0 1px 3px rgba(15,23,42,.04); }
.rd-actbar-title { font-weight:800; font-size:15px; color:#0f172a; margin-bottom:12px; }
.rd-actbar-title .fa { color:#6366f1; margin-right:6px; }
.rd-actbar-items { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
@media(max-width:800px){ .rd-actbar-items{ grid-template-columns:1fr;} }
.rd-act { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:12px; color:#fff; }
.rd-act-red { background:linear-gradient(135deg,#f43f5e,#e11d48); }
.rd-act-amber { background:linear-gradient(135deg,#f59e0b,#d97706); }
.rd-act-ico { width:46px; height:46px; border-radius:11px; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.rd-act-val { font-size:28px; font-weight:900; line-height:1; }
.rd-act-lbl { font-size:12px; font-weight:600; opacity:.95; margin-top:4px; text-transform:uppercase; letter-spacing:.4px; }

.rd-kpis { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:16px; }
@media(max-width:1100px){ .rd-kpis{ grid-template-columns:repeat(2,1fr);} }
.rd-card { display:flex; align-items:center; gap:14px; background:#fff; border:1px solid #eef2f7; border-radius:14px; padding:16px 18px; box-shadow:0 1px 3px rgba(15,23,42,.04); transition:transform .15s, box-shadow .15s; }
.rd-card:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(15,23,42,.10); }
.rd-card-ico { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff; flex-shrink:0; }
.rd-card-val { font-size:24px; font-weight:800; line-height:1.1; color:#0f172a; }
.rd-card-lbl { font-size:12px; color:#64748b; margin-top:3px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
.rd-red   .rd-card-ico{ background:linear-gradient(135deg,#f43f5e,#e11d48);}
.rd-blue  .rd-card-ico{ background:linear-gradient(135deg,#3b82f6,#2563eb);}
.rd-amber .rd-card-ico{ background:linear-gradient(135deg,#f59e0b,#d97706);}
.rd-green .rd-card-ico{ background:linear-gradient(135deg,#22c55e,#16a34a);}
.rd-purple .rd-card-ico{ background:linear-gradient(135deg,#a855f7,#7c3aed);}
.rd-teal  .rd-card-ico{ background:linear-gradient(135deg,#14b8a6,#0d9488);}

.rd-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px; }
.rd-row .rd-2 { grid-column:span 2; }
@media(max-width:1100px){ .rd-row{ grid-template-columns:1fr;} .rd-row .rd-2{ grid-column:span 1;} }
.rd-panel { background:#fff; border:1px solid #eef2f7; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); display:flex; flex-direction:column; }
.rd-panel-head { padding:14px 18px; font-weight:700; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; }
.rd-panel-head .fa { color:#3b82f6; margin-right:6px; }
.rd-panel-body { padding:16px 18px; flex:1; }
.rd-legend { float:right; font-size:11px; font-weight:600; color:#64748b; }
.rd-dot { display:inline-block; width:10px; height:10px; border-radius:3px; margin:0 4px 0 10px; vertical-align:middle; }
.rd-d-blue{ background:#3b82f6;} .rd-d-green{ background:#22c55e;}

/* Trend bars */
.rd-trend { display:flex; align-items:flex-end; gap:10px; height:230px; overflow-x:auto; padding-top:10px; }
.rd-tcol { display:flex; flex-direction:column; align-items:center; gap:6px; min-width:34px; }
.rd-tbars { display:flex; align-items:flex-end; gap:3px; height:190px; }
.rd-tbar { width:11px; border-radius:4px 4px 0 0; transition:height .4s; }
.rd-tbar.t{ background:linear-gradient(180deg,#60a5fa,#3b82f6);}
.rd-tbar.b{ background:linear-gradient(180deg,#4ade80,#22c55e);}
.rd-tlbl { font-size:10px; color:#94a3b8; white-space:nowrap; }

/* Horizontal bars */
.rd-bars { display:flex; flex-direction:column; gap:11px; }
.rd-bar-row .rd-bar-top { display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px; }
.rd-bar-top .l { color:#334155; font-weight:600; max-width:72%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rd-bar-top .v { color:#0f172a; font-weight:700; }
.rd-bar-track { height:9px; background:#f1f5f9; border-radius:6px; overflow:hidden; }
.rd-bar-fill { height:100%; border-radius:6px; background:linear-gradient(90deg,#818cf8,#6366f1); transition:width .5s; }
.rd-empty { color:#94a3b8; font-size:13px; text-align:center; padding:30px 0; }
.rd-loading { color:#94a3b8; font-size:13px; text-align:center; padding:24px 0; }
</style>

<script type="text/javascript">
$(document).ready(function() {
  var BASE = '<?= rtrim(base_url(), "/") ?>/';

  $('#rd_range').daterangepicker({
    ranges: {
      'Hari Ini':[moment().startOf('day'),moment().endOf('day')],
      '7 Hari':[moment().subtract(6,'days').startOf('day'),moment().endOf('day')],
      '30 Hari':[moment().subtract(29,'days').startOf('day'),moment().endOf('day')],
      'Bulan Ini':[moment().startOf('month'),moment().endOf('month')],
      'Bulan Lalu':[moment().subtract(1,'month').startOf('month'),moment().subtract(1,'month').endOf('month')]
    },
    locale:{ format:'YYYY-MM-DD', separator:' s/d ', applyLabel:'Terapkan', cancelLabel:'Batal', customRangeLabel:'Custom',
      daysOfWeek:['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
      monthNames:['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'], firstDay:1 },
    startDate: moment().startOf('month'), endDate: moment().endOf('month')
  });

  function rp(n){ return 'Rp ' + (n||0).toLocaleString('id-ID'); }
  function num(n){ return (n||0).toLocaleString('id-ID'); }

  function renderHBars(el, data, opt){
    opt = opt || {};
    var $c = $(el).empty();
    if(!data || !data.length){ $c.html('<div class="rd-empty">Tidak ada data</div>'); return; }
    var max = Math.max.apply(null, data.map(function(d){return d.n;})) || 1;
    data.forEach(function(d){
      var pct = Math.round(d.n/max*100);
      var fill = opt.color ? 'background:'+opt.color : '';
      $c.append(
        '<div class="rd-bar-row"><div class="rd-bar-top"><span class="l">'+ $('<i>').text(d.label).html() +'</span><span class="v">'+ num(d.n) +'</span></div>'+
        '<div class="rd-bar-track"><div class="rd-bar-fill" style="width:'+pct+'%;'+fill+'"></div></div></div>'
      );
    });
  }

  function renderTrend(el, data){
    var $c = $(el).empty();
    if(!data || !data.length){ $c.html('<div class="rd-empty">Tidak ada data</div>'); return; }
    var max = 1;
    data.forEach(function(d){ max = Math.max(max, d.terima, d.buka); });
    data.forEach(function(d){
      var ht = Math.round(d.terima/max*180), hb = Math.round(d.buka/max*180);
      $c.append(
        '<div class="rd-tcol"><div class="rd-tbars">'+
        '<div class="rd-tbar t" style="height:'+ht+'px" title="Terima: '+d.terima+'"></div>'+
        '<div class="rd-tbar b" style="height:'+hb+'px" title="Buka: '+d.buka+'"></div>'+
        '</div><div class="rd-tlbl">'+d.tgl+'</div></div>'
      );
    });
  }

  function loadDash(){
    var dates = $('#rd_range').val().split(' s/d ');
    $('#rd_trend,#rd_status,#rd_kurir,#rd_mp,#rd_sku').html('<div class="rd-loading"><i class="fa fa-spinner fa-spin"></i> Memuat...</div>');
    $.ajax({
      url: BASE + 'retur/get-dashboard-data', type:'POST', dataType:'json',
      data:{ start_date:(dates[0]||'')+' 00:00:00', end_date:(dates[1]||'')+' 23:59:59' },
      success:function(res){
        var s = res.summary || {};
        $('#kpi_tadro').text(num(s.tadro));
        $('#kpi_belum').text(num(s.belum_dibuka));
        $('#kpi_buka').text(num(s.buka));
        $('#kpi_complain').text(num(s.complain));
        $('#kpi_tagihan').text(rp(s.tagihan));
        $('#act_terima').text(num(s.tadro));
        $('#act_buka').text(num(s.belum_dibuka));
        renderTrend('#rd_trend', res.trend);
        renderHBars('#rd_status', res.status_buka, {color:'linear-gradient(90deg,#fbbf24,#f59e0b)'});
        renderHBars('#rd_kurir', res.by_kurir, {color:'linear-gradient(90deg,#60a5fa,#3b82f6)'});
        renderHBars('#rd_mp', res.by_mp, {color:'linear-gradient(90deg,#34d399,#10b981)'});
        renderHBars('#rd_sku', res.top_sku, {color:'linear-gradient(90deg,#c084fc,#a855f7)'});
      },
      error:function(){
        $('#rd_trend,#rd_status,#rd_kurir,#rd_mp,#rd_sku').html('<div class="rd-empty">Gagal memuat data</div>');
      }
    });
  }

  $('#rd_refresh').on('click', loadDash);

  // ==================== SCREENSHOT -> DOWNLOAD PNG ====================
  function ensureHtml2Canvas(cb){
    if (window.html2canvas) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
    s.onload = function(){ cb(); };
    s.onerror = function(){ alert('Gagal memuat modul screenshot (perlu internet).'); };
    document.head.appendChild(s);
  }

  $('#rd_screenshot').on('click', function(){
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan...');
    ensureHtml2Canvas(function(){
      var node = document.querySelector('.retur-dash');
      // Sembunyikan tombol filter saat capture biar bersih
      $('.rd-filter').css('visibility','hidden');
      html2canvas(node, { scale: 2, backgroundColor: '#f8fafc', useCORS: true, logging: false })
        .then(function(canvas){
          $('.rd-filter').css('visibility','visible');
          var link = document.createElement('a');
          link.download = 'Dashboard_Retur_' + moment().format('YYYYMMDD_HHmmss') + '.png';
          link.href = canvas.toDataURL('image/png');
          document.body.appendChild(link); link.click(); document.body.removeChild(link);
          $btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Download Gambar');
        })
        .catch(function(){
          $('.rd-filter').css('visibility','visible');
          alert('Gagal membuat gambar dashboard.');
          $btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Download Gambar');
        });
    });
  });

  loadDash();
});
</script>
