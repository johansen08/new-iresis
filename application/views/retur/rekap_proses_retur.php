<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="rk-wrap">

  <!-- Header -->
  <div class="rk-header">
    <div>
      <h2 class="rk-title"><i class="fa fa-balance-scale"></i> Rekap Proses Retur</h2>
      <p class="rk-sub">
        Dihitung dari daftar retur Jubelio (yang <em>seharusnya</em> kembali), bukan dari hasil scan &mdash;
        supaya paket yang tidak pernah kembali ikut terlihat.
      </p>
    </div>
    <div class="rk-filter">
      <select id="rk_kurir" class="form-control" style="width:190px;">
        <option value="">Semua Kurir</option>
      </select>
      <input type="text" id="rk_range" class="form-control" readonly style="width:210px;" />
      <button type="button" class="btn btn-primary" id="rk_load"><i class="fa fa-refresh"></i> Muat</button>
      <button type="button" class="btn btn-success" id="rk_export"><i class="fa fa-file-excel-o"></i> Export</button>
    </div>
  </div>

  <div class="rk-fresh" id="rk_fresh"></div>

  <!-- KPI utama -->
  <div class="rk-kpis">
    <div class="rk-card">
      <div class="rk-card-lbl">Diklaim Retur oleh Marketplace</div>
      <div class="rk-card-val" id="kpi_total">-</div>
      <div class="rk-card-sub" id="kpi_total_rp">-</div>
    </div>
    <div class="rk-card rk-card-green">
      <div class="rk-card-lbl">Sudah Kembali &amp; Lengkap</div>
      <div class="rk-card-val" id="kpi_beres">-</div>
      <div class="rk-card-sub" id="kpi_beres_pct">-</div>
    </div>
    <div class="rk-card rk-card-red">
      <div class="rk-card-lbl">Perlu Diklaim ke Kurir / MP</div>
      <div class="rk-card-val" id="kpi_klaim">-</div>
      <div class="rk-card-sub" id="kpi_klaim_rp">-</div>
    </div>
    <div class="rk-card rk-card-dark">
      <div class="rk-card-lbl">Hangus (lewat window klaim)</div>
      <div class="rk-card-val" id="kpi_hangus">-</div>
      <div class="rk-card-sub" id="kpi_hangus_rp">-</div>
    </div>
  </div>

  <!-- Corong -->
  <div class="rk-panel">
    <div class="rk-panel-head">
      <i class="fa fa-filter"></i> Corong Proses Retur
      <span class="rk-hint" id="rk_sla_hint"></span>
    </div>
    <div class="rk-panel-body">
      <div class="rk-funnel" id="rk_funnel">
        <div class="rk-loading"><i class="fa fa-spinner fa-spin"></i> Memuat&hellip;</div>
      </div>
      <div class="rk-note" id="rk_hanya_iresis"></div>
    </div>
  </div>

  <!-- Tindak lanjut klaim (tahap 2) -->
  <div class="rk-panel">
    <div class="rk-panel-head">
      <i class="fa fa-gavel"></i> Tindak Lanjut Klaim
      <span class="rk-hint">seluruh periode, dari menu Pengajuan Klaim Retur</span>
    </div>
    <div class="rk-panel-body">
      <div class="rk-klaim" id="rk_klaim">
        <div class="rk-loading"><i class="fa fa-spinner fa-spin"></i> Memuat&hellip;</div>
      </div>
      <div class="rk-note" id="rk_klaim_alarm" style="display:none;"></div>
    </div>
  </div>

  <!-- Matriks kurir & marketplace -->
  <div class="rk-row">
    <div class="rk-panel">
      <div class="rk-panel-head"><i class="fa fa-truck"></i> Per Kurir <span class="rk-hint">bahan negosiasi klaim</span></div>
      <div class="rk-panel-body rk-scroll">
        <table class="rk-matrix" id="rk_kurir_tbl"><tbody><tr><td class="rk-loading">Memuat&hellip;</td></tr></tbody></table>
      </div>
    </div>
    <div class="rk-panel">
      <div class="rk-panel-head"><i class="fa fa-shopping-cart"></i> Per Marketplace</div>
      <div class="rk-panel-body rk-scroll">
        <table class="rk-matrix" id="rk_mp_tbl"><tbody><tr><td class="rk-loading">Memuat&hellip;</td></tr></tbody></table>
      </div>
    </div>
  </div>

  <!-- Detail -->
  <div class="rk-panel">
    <div class="rk-panel-head">
      <i class="fa fa-list"></i> Daftar Resi
      <span class="rk-hint" id="rk_detail_hint">semua kategori</span>
    </div>
    <div class="rk-panel-body">
      <div class="rk-chips" id="rk_chips"></div>
      <div class="table-responsive" style="margin-top:12px;">
        <table class="table table-striped table-bordered table-hover" id="rk_detail" style="width:100%;">
          <thead>
            <tr>
              <th style="width:44px;">No</th>
              <th>Tgl Retur</th>
              <th>No. Resi</th>
              <th>No. Pesanan</th>
              <th>Toko</th>
              <th>Kurir</th>
              <th>Qty MP</th>
              <th>Qty Diterima</th>
              <th>Nilai</th>
              <th>Umur</th>
              <th>Kategori</th>
              <th>Status Buka</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<style>
.rk-wrap { font-family:'Inter','Segoe UI',sans-serif; color:#0f172a; }
.rk-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.rk-title { font-size:21px; font-weight:800; margin:0 0 4px; }
.rk-title .fa { color:#3b82f6; }
.rk-sub { font-size:12px; color:#64748b; margin:0; max-width:640px; }
.rk-filter { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.rk-fresh { font-size:11px; color:#92400e; background:#fffbeb; border:1px solid #fcd34d; border-radius:6px;
            padding:6px 12px; margin-bottom:14px; display:none; }

.rk-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:14px; }
.rk-card { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #3b82f6; border-radius:10px; padding:14px 16px; }
.rk-card-green { border-left-color:#22c55e; }
.rk-card-red   { border-left-color:#dc2626; }
.rk-card-dark  { border-left-color:#7f1d1d; }
.rk-card-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:6px; }
.rk-card-val { font-size:26px; font-weight:800; line-height:1.1; }
.rk-card-sub { font-size:12px; color:#475569; margin-top:3px; }

.rk-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; overflow:hidden; }
.rk-panel-head { padding:11px 16px; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; background:#f8fafc; }
.rk-panel-head .fa { color:#3b82f6; margin-right:5px; }
.rk-panel-body { padding:14px 16px; }
.rk-hint { float:right; font-size:11px; font-weight:600; color:#94a3b8; }
.rk-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width:1100px) { .rk-row { grid-template-columns:1fr; } }
.rk-scroll { max-height:340px; overflow:auto; }
.rk-loading, .rk-empty { color:#94a3b8; font-size:12px; text-align:center; padding:20px 0; }
.rk-note { font-size:12px; color:#64748b; margin-top:12px; padding-top:10px; border-top:1px dashed #e2e8f0; }

/* Corong */
.rk-funnel { display:flex; flex-direction:column; gap:8px; }
.rk-fbar { display:flex; align-items:center; gap:12px; cursor:pointer; }
.rk-fbar:hover .rk-flabel { color:#0f172a; }
.rk-flabel { width:210px; flex-shrink:0; font-size:12px; font-weight:600; color:#475569; }
.rk-ftrack { flex:1; height:26px; background:#f1f5f9; border-radius:5px; overflow:hidden; }
.rk-ffill { height:100%; border-radius:5px; transition:width .5s; min-width:2px; }
.rk-fval { width:190px; flex-shrink:0; text-align:right; font-size:12px; }
.rk-fval b { font-size:14px; }
.rk-fval small { color:#64748b; display:block; }

/* Tindak lanjut klaim */
.rk-klaim { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
.rk-kbox { border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; }
.rk-kbox-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; }
.rk-kbox-val { font-size:19px; font-weight:800; margin-top:3px; }
.rk-kbox-sub { font-size:11px; color:#64748b; }

/* Matriks */
.rk-matrix { width:100%; border-collapse:collapse; font-size:12px; }
.rk-matrix th { position:sticky; top:0; background:#f8fafc; padding:7px 8px; font-size:10px; font-weight:700;
                text-transform:uppercase; color:#475569; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.rk-matrix td { padding:6px 8px; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
.rk-matrix td.rk-l { font-weight:600; color:#334155; max-width:170px; overflow:hidden; text-overflow:ellipsis; }
.rk-matrix td.rk-n { text-align:right; font-variant-numeric:tabular-nums; }
.rk-matrix td.rk-danger { color:#dc2626; font-weight:700; }
.rk-matrix tr:hover td { background:#f8fafc; }

/* Chips filter */
.rk-chips { display:flex; gap:7px; flex-wrap:wrap; }
.rk-chip { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; cursor:pointer;
           border:1px solid #e2e8f0; background:#fff; color:#475569; }
.rk-chip.on { color:#fff; border-color:transparent; }
.rk-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10px; font-weight:700;
            color:#fff; white-space:nowrap; }
</style>

<script type="text/javascript">
$(document).ready(function () {
  var BASE = '<?= $BASE ?>';
  var BUCKET_META = {};   // diisi dari respons server
  var CUR_BUCKET  = '';

  // ---------- Filter tanggal ----------
  $('#rk_range').daterangepicker({
    ranges: {
      '30 Hari':     [moment().subtract(29,'days').startOf('day'), moment().endOf('day')],
      'Bulan Ini':   [moment().startOf('month'), moment().endOf('month')],
      'Bulan Lalu':  [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
      '3 Bulan':     [moment().subtract(2,'month').startOf('month'), moment().endOf('month')]
    },
    locale: {
      format:'YYYY-MM-DD', separator:' s/d ', applyLabel:'Terapkan', cancelLabel:'Batal',
      customRangeLabel:'Custom', firstDay:1,
      daysOfWeek:['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
      monthNames:['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
    },
    startDate: moment().subtract(1,'month').startOf('month'),
    endDate:   moment().subtract(1,'month').endOf('month')
  });

  function rp(n)  { return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
  function num(n) { return Number(n||0).toLocaleString('id-ID'); }
  function esc(s) { return $('<span>').text(s == null ? '' : s).html(); }
  function range() {
    var v = ($('#rk_range').val() || '').split(' s/d ');
    return { sd: (v[0] || '') + ' 00:00:00', ed: (v[1] || '') + ' 23:59:59' };
  }

  // ---------- Ringkasan + matriks ----------
  function loadRekap() {
    var r = range();
    $('#rk_funnel').html('<div class="rk-loading"><i class="fa fa-spinner fa-spin"></i> Memuat&hellip;</div>');

    $.post(BASE + 'retur/get-rekap-proses-retur', {
      start_date: r.sd, end_date: r.ed, kurir: $('#rk_kurir').val() || ''
    }, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) { return; } }

      BUCKET_META = {};
      $.each(res.summary, function (i, s) { BUCKET_META[s.key] = s; });

      var beres  = (BUCKET_META.SELESAI || {}).n || 0;
      var klaim  = res.resi_klaim || 0;
      var hangus = (BUCKET_META.HANGUS || {}).n || 0;

      $('#kpi_total').text(num(res.total_resi) + ' resi');
      $('#kpi_total_rp').text(rp(res.total_nilai));
      $('#kpi_beres').text(num(beres) + ' resi');
      $('#kpi_beres_pct').text(res.total_resi ? (beres / res.total_resi * 100).toFixed(1) + '% dari total' : '-');
      $('#kpi_klaim').text(num(klaim) + ' resi');
      $('#kpi_klaim_rp').text(rp(res.nilai_klaim));
      $('#kpi_hangus').text(num(hangus) + ' resi');
      $('#kpi_hangus_rp').text(rp((BUCKET_META.HANGUS || {}).nilai || 0));

      $('#rk_sla_hint').text('wajar ≤ ' + res.sla.wajar + ' hari · terlambat ≤ ' + res.sla.terlambat
        + ' hari · window klaim ' + res.sla.window + ' hari');

      // Corong
      var $f = $('#rk_funnel').empty();
      var max = 1;
      $.each(res.summary, function (i, s) { max = Math.max(max, s.n); });
      $.each(res.summary, function (i, s) {
        var pct = Math.round(s.n / max * 100);
        $f.append(
          '<div class="rk-fbar" data-bucket="' + s.key + '" title="' + esc(s.ket) + '">' +
            '<div class="rk-flabel">' + esc(s.label) + '</div>' +
            '<div class="rk-ftrack"><div class="rk-ffill" style="width:' + pct + '%;background:' + s.warna + '"></div></div>' +
            '<div class="rk-fval"><b>' + num(s.n) + '</b> resi <small>' + rp(s.nilai) + '</small></div>' +
          '</div>'
        );
      });

      $('#rk_hanya_iresis').html(
        '<i class="fa fa-info-circle"></i> <b>' + num(res.hanya_iresis) + ' resi</b> discan di iresis tapi tidak ada di ' +
        'daftar retur Jubelio pada rentang ini (bukan kandidat klaim &mdash; indikasi selisih data atau retur di luar sistem).'
      );

      // Kesegaran data
      if (res.last_upload) {
        $('#rk_fresh').show().html(
          '<i class="fa fa-clock-o"></i> Data Jubelio terakhir di-upload <b>' +
          moment(res.last_upload).format('DD/MM/YYYY HH:mm') + '</b>, tanggal retur terakhir <b>' +
          moment(res.last_retur).format('DD/MM/YYYY') + '</b>. Angka di bawah hanya sevalid upload terakhir.'
        );
      }

      // Dropdown kurir (pertahankan pilihan)
      var sel = $('#rk_kurir').val();
      var $k = $('#rk_kurir').empty().append('<option value="">Semua Kurir</option>');
      $.each(res.kurir_list, function (i, k) {
        $k.append('<option value="' + esc(k) + '"' + (k === sel ? ' selected' : '') + '>' + esc(k) + '</option>');
      });

      renderMatrix('#rk_kurir_tbl', res.by_kurir, res.summary);
      renderMatrix('#rk_mp_tbl',    res.by_mp,    res.summary);
      renderChips(res.summary);
    }, 'json');
  }

  // Matriks baris x bucket
  function renderMatrix(sel, rows, summary) {
    var $t = $(sel).empty();
    if (!rows || !rows.length) { $t.html('<tbody><tr><td class="rk-empty">Tidak ada data</td></tr></tbody>'); return; }

    var head = '<thead><tr><th>Nama</th>';
    $.each(summary, function (i, s) { head += '<th style="text-align:right">' + esc(s.label) + '</th>'; });
    head += '<th style="text-align:right">Total</th><th style="text-align:right">Nilai Klaim</th></tr></thead>';

    var body = '<tbody>';
    $.each(rows, function (i, r) {
      body += '<tr><td class="rk-l" title="' + esc(r.label) + '">' + esc(r.label) + '</td>';
      $.each(summary, function (j, s) {
        var v = r.bucket[s.key] || 0;
        var cls = (s.key === 'LAYAK_KLAIM' || s.key === 'HANGUS') && v > 0 ? 'rk-n rk-danger' : 'rk-n';
        body += '<td class="' + cls + '">' + (v ? num(v) : '<span style="color:#cbd5e1">-</span>') + '</td>';
      });
      body += '<td class="rk-n"><b>' + num(r.total) + '</b></td>';
      body += '<td class="rk-n">' + rp(r.nilai_klaim) + '</td></tr>';
    });
    body += '</tbody>';

    $t.html(head + body);
  }

  // Chip filter kategori
  function renderChips(summary) {
    var $c = $('#rk_chips').empty();
    $c.append('<span class="rk-chip' + (CUR_BUCKET === '' ? ' on' : '') + '" data-bucket="" ' +
      'style="' + (CUR_BUCKET === '' ? 'background:#334155' : '') + '">Semua</span>');
    $.each(summary, function (i, s) {
      var on = CUR_BUCKET === s.key;
      $c.append('<span class="rk-chip' + (on ? ' on' : '') + '" data-bucket="' + s.key + '" ' +
        'style="' + (on ? 'background:' + s.warna : '') + '">' + esc(s.label) + ' (' + num(s.n) + ')</span>');
    });
  }

  // ---------- Detail ----------
  var dt = $('#rk_detail').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    order: [[9, 'desc']], deferLoading: 0,
    lengthMenu: [[10,25,50,100],[10,25,50,100]],
    ajax: {
      url: BASE + 'retur/get-rekap-detail',
      type: 'POST',
      data: function (d) {
        var r = range();
        d.start_date = r.sd;
        d.end_date   = r.ed;
        d.bucket     = CUR_BUCKET;
        d.kurir      = $('#rk_kurir').val() || '';
      }
    },
    columns: [
      {data: 0, orderable: false}, {data: 1}, {data: 2}, {data: 3}, {data: 4}, {data: 5},
      {data: 6}, {data: 7}, {data: 8}, {data: 9}, {data: 10}, {data: 11, orderable: false}
    ],
    language: { emptyTable: 'Tidak ada data', zeroRecords: 'Tidak ada data ditemukan' }
  });

  // ---------- Tindak lanjut klaim (tabel tblreturklaim, tahap 2) ----------
  function loadKlaim() {
    $.post(BASE + 'retur-klaim/get-stats', {}, function (res) {
      if (typeof res === 'string') { try { res = JSON.parse(res); } catch (e) { return; } }

      var s = res.per_status || {};
      var g = function (k) { return s[k] || {n: 0, nilai: 0, diterima: 0}; };

      $('#rk_klaim').html(
        box('Diajukan, Menunggu Putusan', num(g('DIAJUKAN').n) + ' klaim', rp(g('DIAJUKAN').nilai), '#3b82f6') +
        box('Disetujui, Belum Diganti',   num(g('DISETUJUI').n + g('SEBAGIAN').n) + ' klaim',
            rp(g('DISETUJUI').nilai + g('SEBAGIAN').nilai), '#0ea5e9') +
        box('Menunggu Verifikasi Finance', num(g('DIGANTI').n) + ' klaim', rp(g('DIGANTI').diterima), '#8b5cf6') +
        box('Selesai (Terverifikasi)',    num(g('SELESAI').n) + ' klaim', rp(g('SELESAI').diterima), '#16a34a') +
        box('Ditolak Kurir',              num(g('DITOLAK').n) + ' klaim', rp(g('DITOLAK').nilai), '#dc2626') +
        box('Recovery Rate',              res.recovery_rate + '%', 'dari ' + rp(res.nilai_klaim) + ' diklaim', '#7f1d1d')
      );

      if (!res.total_klaim) {
        $('#rk_klaim').append(
          '<div class="rk-kbox" style="grid-column:1/-1;border-style:dashed;">' +
          '<div class="rk-kbox-sub">Belum ada klaim yang diajukan. Buka menu <b>Pengajuan Klaim Retur</b> ' +
          '(TIM ACCOUNTING) untuk mengajukan dari kategori Layak Klaim / Isi Bermasalah.</div></div>'
        );
      }

      if (res.alarm > 0) {
        $('#rk_klaim_alarm').show().html(
          '<i class="fa fa-exclamation-triangle text-danger"></i> <b>' + num(res.alarm) + ' klaim</b> ternyata ' +
          'barangnya muncul di scan buka retur setelah diklaim — perlu dicek supaya tidak dapat ganti rugi sekaligus barangnya.'
        );
      } else {
        $('#rk_klaim_alarm').hide();
      }
    }, 'json').fail(function () {
      $('#rk_klaim').html('<div class="rk-empty">Data klaim belum tersedia.</div>');
    });
  }

  function box(lbl, val, sub, warna) {
    return '<div class="rk-kbox" style="border-left:3px solid ' + warna + '">' +
             '<div class="rk-kbox-lbl">' + lbl + '</div>' +
             '<div class="rk-kbox-val">' + val + '</div>' +
             '<div class="rk-kbox-sub">' + sub + '</div>' +
           '</div>';
  }

  function reloadAll() { loadRekap(); loadKlaim(); dt.ajax.reload(); }

  // ---------- Interaksi ----------
  $('#rk_load').on('click', reloadAll);
  $('#rk_kurir').on('change', reloadAll);

  $(document).on('click', '#rk_chips .rk-chip', function () {
    CUR_BUCKET = $(this).data('bucket') || '';
    var lbl = CUR_BUCKET ? (BUCKET_META[CUR_BUCKET] || {}).label : 'semua kategori';
    $('#rk_detail_hint').text(lbl);
    $('#rk_chips .rk-chip').removeClass('on').css('background', '');
    $(this).addClass('on').css('background', CUR_BUCKET ? (BUCKET_META[CUR_BUCKET] || {}).warna : '#334155');
    dt.ajax.reload();
  });

  // Klik batang corong = filter detail kategori tsb
  $(document).on('click', '#rk_funnel .rk-fbar', function () {
    var b = $(this).data('bucket');
    $('#rk_chips .rk-chip[data-bucket="' + b + '"]').trigger('click');
    $('html,body').animate({ scrollTop: $('#rk_detail').offset().top - 80 }, 300);
  });

  $('#rk_export').on('click', function () {
    var r = range();
    window.open(BASE + 'retur/export-rekap-proses-retur'
      + '?start_date=' + encodeURIComponent(r.sd)
      + '&end_date='   + encodeURIComponent(r.ed)
      + '&bucket='     + encodeURIComponent(CUR_BUCKET)
      + '&kurir='      + encodeURIComponent($('#rk_kurir').val() || ''), '_blank');
  });

  reloadAll();
});
</script>
