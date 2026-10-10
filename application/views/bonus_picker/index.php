<?php
// View menu TIM PICKER -> Bonus Picker. Dimuat lewat AJAX (SPA); semua selector
// diawali #bp-root supaya tidak bocor ke halaman lain. Data dihitung server
// (Bonus_picker_fcd), view hanya menampilkan, mengurutkan, dan mencari.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Bonus Picker</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif;

$fmt = function ($n) { return number_format((int) $n, 0, ',', '.'); };
?>
<style>
  #bp-root .bp-toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:15px; }
  #bp-root .bp-toolbar .form-group { margin:0; }
  #bp-root .bp-toolbar label { font-size:12px; color:#777; margin-bottom:3px; display:block; }
  #bp-root .bp-kartu { background:#fff; border:1px solid #e3e8ed; border-left:4px solid #5bc0de; border-radius:4px; padding:10px 14px; margin-bottom:15px; }
  #bp-root .bp-kartu .k { font-size:12px; color:#777; }
  #bp-root .bp-kartu .v { font-size:22px; font-weight:700; line-height:1.3; color:#333; }
  #bp-root table td, #bp-root table th { vertical-align:middle !important; }
  #bp-root .bp-bonus { font-weight:700; }
  #bp-root .bp-bonus.nol { color:#999; font-weight:normal; }
  #bp-root th.bp-sort { cursor:pointer; user-select:none; white-space:nowrap; }
  #bp-root th.bp-sort:hover { color:#337ab7; }
  #bp-root .bp-pill { display:inline-block; padding:2px 10px; border-radius:999px; font-weight:600; font-size:12px; }
  #bp-root .bp-pill.ok   { background:#e3f6ea; color:#15803d; }
  #bp-root .bp-pill.none { background:#eef1f5; color:#6b7a8c; }
  #bp-root .bp-pill.gold { background:#fdf0d8; color:#b45309; }
  #bp-root .bp-pill.sp   { background:#ede9fe; color:#6d28d9; }
  #bp-root tr.bp-spes td { background:#faf8ff; }
  #bp-root .bp-info { position:relative; display:inline-block; width:16px; height:16px; line-height:16px; text-align:center; border-radius:50%;
    background:#e8effd; color:#2563eb; font-size:11px; font-weight:700; cursor:help; font-style:normal; outline:none; }
  #bp-root .bp-info .bp-tip { display:none; position:absolute; z-index:30; top:22px; left:-8px; width:290px; background:#1c2733; color:#fff; padding:10px 12px;
    border-radius:6px; font-size:12px; font-weight:normal; line-height:1.5; text-align:left; white-space:normal; box-shadow:0 8px 24px rgba(0,0,0,.25); }
  #bp-root .bp-info:hover .bp-tip, #bp-root .bp-info:focus .bp-tip { display:block; }
  #bp-root th.text-right .bp-info .bp-tip, #bp-root th.text-center .bp-info .bp-tip { left:auto; right:-8px; }
  #bp-root .bp-ov { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(16,24,40,.45); display:none; align-items:center; justify-content:center; padding:16px; z-index:2000; }
  #bp-root .bp-ov.on { display:flex; }
  #bp-root .bp-modal { background:#fff; width:100%; max-width:680px; max-height:88vh; display:flex; flex-direction:column; border-radius:6px; overflow:hidden; }
  #bp-root .bp-mh { display:flex; justify-content:space-between; align-items:flex-start; padding:14px 18px; border-bottom:1px solid #e3e8ed; }
  #bp-root .bp-mh h4 { margin:0; font-weight:700; }
  #bp-root .bp-mh .s { color:#777; font-size:12px; margin-top:2px; }
  #bp-root .bp-x { border:0; background:none; font-size:24px; line-height:1; cursor:pointer; color:#777; }
  #bp-root .bp-msum { display:flex; gap:8px; flex-wrap:wrap; padding:10px 18px; border-bottom:1px solid #e3e8ed; }
  #bp-root .bp-msum div { background:#f4f6f9; border-radius:4px; padding:5px 12px; font-size:12px; color:#777; }
  #bp-root .bp-msum b { display:block; font-size:15px; color:#333; }
  #bp-root .bp-mb { overflow:auto; }
  #bp-root .bp-mb table { margin:0; }
</style>

<div id="bp-root">
<div class="row"><div class="col-md-12">
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><strong>Bonus Picker</strong>
      <span class="bp-info" tabindex="0">i<span class="bp-tip">
        <b>Aturan perhitungan</b><br>
        &bull; Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin.<br>
        &bull; Net harian = total pick &minus; poin kesalahan.<br>
        &bull; Net <?= $fmt($aturan['target']) ?>&ndash;<?= $fmt($aturan['tier2'] - 1) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus1']) ?>/hari.<br>
        &bull; Net &ge; <?= $fmt($aturan['tier2']) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus2']) ?>/hari.<br>
        &bull; Net &lt; <?= $fmt($aturan['target']) ?> &rarr; tidak ada bonus.<br>
        &bull; Hanya resi yang di-assign pukul 05.00&ndash;17.00.<br>
        &bull; Hanya NORMAL_PICKER; 1_SKU_PICKER tidak dihitung.<br>
        &bull; Hari bertugas sebagai petugas resi spesial tidak dihitung sama sekali.<br>
        &bull; Capai target = jumlah hari dengan net &ge; <?= $fmt($aturan['target']) ?>.
      </span></span>
    </h3>
  </div>
  <div class="panel-body">
    <div class="bp-toolbar">
      <div class="form-group"><label for="bp-awal">Dari tanggal</label>
        <input type="text" id="bp-awal" class="form-control" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" style="width:130px" value="<?= date('d/m/Y', strtotime($awal)) ?>"></div>
      <div class="form-group"><label for="bp-akhir">Sampai tanggal</label>
        <input type="text" id="bp-akhir" class="form-control" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" style="width:130px" value="<?= date('d/m/Y', strtotime($akhir)) ?>"></div>
      <div class="btn-group">
        <button type="button" class="btn btn-default bp-cepat" data-r="0">Hari ini</button>
        <button type="button" class="btn btn-default bp-cepat" data-r="6">7 hari</button>
        <button type="button" class="btn btn-default bp-cepat" data-r="bulan">Bulan ini</button>
      </div>
      <button type="button" class="btn btn-primary" id="bp-tampilkan"><i class="fa fa-search"></i> Tampilkan</button>
      <button type="button" class="btn btn-success" id="bp-export"><i class="fa fa-file-excel-o"></i> Export Excel</button>
      <div class="form-group" style="margin-left:auto;"><label for="bp-cari">Cari picker</label>
        <input type="search" id="bp-cari" class="form-control" placeholder="Nama atau no. absen"></div>
    </div>

    <div class="row">
      <div class="col-sm-4"><div class="bp-kartu"><div class="k">Picker berbonus</div><div class="v" id="bp-s-picker">-</div></div></div>
      <div class="col-sm-4"><div class="bp-kartu"><div class="k">Total bonus</div><div class="v" id="bp-s-bonus">-</div></div></div>
      <div class="col-sm-4"><div class="bp-kartu"><div class="k">Hari tercapai (semua picker)
        <span class="bp-info" tabindex="0">i<span class="bp-tip">Jumlah hari-picker dengan net &ge; <?= $fmt($aturan['target']) ?> pada rentang yang dipilih.</span></span></div>
        <div class="v" id="bp-s-hari">-</div></div></div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover table-bordered">
      <thead><tr>
        <th class="bp-sort" data-k="absen">No. Absen <span class="bp-arr"></span></th>
        <th class="bp-sort" data-k="nama">Nama <span class="bp-arr"></span></th>
        <th class="bp-sort text-right" data-k="bonus">Total Bonus <span class="bp-arr"></span></th>
        <th class="bp-sort text-center" data-k="hari">Total Capai Target
          <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tip">Berapa hari target tercapai (net &ge; <?= $fmt($aturan['target']) ?>) pada rentang tanggal.</span></span>
          <span class="bp-arr"></span></th>
        <th class="text-center">Aksi</th>
      </tr></thead>
      <tbody id="bp-rows"><tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr></tbody>
    </table>
    </div>
  </div>
</div>
</div></div>

<div class="bp-ov" id="bp-ov">
  <div class="bp-modal" role="dialog" aria-modal="true">
    <div class="bp-mh">
      <div><h4 id="bp-m-nama"></h4><div class="s" id="bp-m-sub"></div></div>
      <div style="display:flex; align-items:center; gap:10px;">
        <button type="button" class="btn btn-success btn-sm" id="bp-m-export"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button type="button" class="bp-x" id="bp-m-x" aria-label="Tutup">&times;</button>
      </div>
    </div>
    <div class="bp-msum" id="bp-m-sum"></div>
    <div class="bp-mb">
      <table class="table table-condensed">
        <thead><tr>
          <th>Tanggal</th>
          <th class="text-right">Total Pick</th>
          <th class="text-right">Jumlah Kesalahan</th>
          <th class="text-right">Poin Kesalahan
            <span class="bp-info" tabindex="0">i<span class="bp-tip">Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin.</span></span></th>
          <th class="text-right">Net
            <span class="bp-info" tabindex="0">i<span class="bp-tip">Net = total pick &minus; poin kesalahan. Hijau = capai target, emas = net &ge; <?= $fmt($aturan['tier2']) ?>.</span></span></th>
        </tr></thead>
        <tbody id="bp-m-rows"></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script>
(function () {
  var $root = $('#bp-root');
  var TARGET = <?= (int) $aturan['target'] ?>, TIER2 = <?= (int) $aturan['tier2'] ?>, POIN = <?= (int) $aturan['poin'] ?>;
  var data = [], sortK = 'bonus', sortDir = -1, awalAktif = '', akhirAktif = '', kodeAktif = 0;

  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
  function num(n) { return Number(n).toLocaleString('id-ID'); }
  function absen(a) { return a == null ? '-' : ('0000' + a).slice(-4); }
  function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
  // Tampilan dd/mm/yyyy (Indonesia); ke server selalu yyyy-mm-dd (format database).
  function idTgl(d) { return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear(); }
  function keIso(s) {
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec($.trim(s));
    if (!m) { return ''; }
    var d = new Date(+m[3], +m[2] - 1, +m[1]);
    if (d.getFullYear() !== +m[3] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[1]) { return ''; }
    return iso(d);
  }
  if ($.fn.datepicker) {
    $('#bp-awal, #bp-akhir').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true, weekStart: 1 })
      .on('changeDate', function () { muat(); });
  }
  function tgl(s) {
    var d = new Date(s + 'T00:00:00');
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
  }

  function muat() {
    var a = keIso($('#bp-awal').val()), b = keIso($('#bp-akhir').val());
    if (!a || !b) {
      $('#bp-rows').html('<tr><td colspan="5" class="text-center text-danger">Format tanggal harus dd/mm/yyyy, contoh 01/10/2026.</td></tr>');
      return;
    }
    $('#bp-rows').html('<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');
    $.ajax({
      url: 'bonus-picker/get-data', type: 'POST', dataType: 'json', data: { awal: a, akhir: b },
      success: function (r) {
        if (r.code === 200) { data = r.data.pickers; awalAktif = a; akhirAktif = b; render(); }
        else { $('#bp-rows').html('<tr><td colspan="5" class="text-center text-danger">' + esc(r.message) + '</td></tr>'); }
      },
      error: function (xhr) {
        $('#bp-rows').html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 200)) + '</td></tr>');
      }
    });
  }

  function render() {
    var q = $.trim($('#bp-cari').val()).toLowerCase();
    var rows = data.filter(function (p) {
      return !q || p.nama.toLowerCase().indexOf(q) >= 0 || String(p.absen) === q;
    });
    rows.sort(function (x, y) {
      var A = x[sortK], B = y[sortK];
      if (A == null) { A = -1; }
      if (B == null) { B = -1; }
      return (typeof A === 'string' ? A.localeCompare(B) : A - B) * sortDir;
    });
    var html = rows.map(function (p) {
      return '<tr><td>' + absen(p.absen) + '</td>' +
        '<td><strong>' + esc(p.nama) + '</strong></td>' +
        '<td class="text-right bp-bonus' + (p.bonus ? '' : ' nol') + '">' + rp(p.bonus) + '</td>' +
        '<td class="text-center"><span class="bp-pill ' + (p.hari ? 'ok' : 'none') + '">' + p.hari + ' hari</span></td>' +
        '<td class="text-center"><button type="button" class="btn btn-xs btn-primary bp-detail" data-k="' + p.kode + '">Detail</button></td></tr>';
    }).join('');
    $('#bp-rows').html(html || '<tr><td colspan="5" class="text-center text-muted">Tidak ada data picker pada rentang ini.</td></tr>');
    $('#bp-s-picker').text(rows.filter(function (p) { return p.bonus > 0; }).length + ' / ' + rows.length);
    $('#bp-s-bonus').text(rp(rows.reduce(function (s, p) { return s + p.bonus; }, 0)));
    $('#bp-s-hari').text(rows.reduce(function (s, p) { return s + p.hari; }, 0));
    $root.find('th.bp-sort').each(function () {
      $(this).find('.bp-arr').text($(this).data('k') === sortK ? (sortDir > 0 ? '▲' : '▼') : '');
    });
  }

  function buka(kode) {
    var p = data.filter(function (x) { return x.kode === kode; })[0];
    if (!p) { return; }
    var biasa = p.det.filter(function (x) { return !x.spesial; });
    var tp = 0, ts = 0;
    biasa.forEach(function (x) { tp += x.pick; ts += x.salah; });
    kodeAktif = p.kode;
    $('#bp-m-nama').text(p.nama);
    $('#bp-m-sub').text('No. absen ' + absen(p.absen) + ' · ' + tgl(awalAktif) + ' – ' + tgl(akhirAktif));
    $('#bp-m-sum').html(
      '<div>Total bonus<b>' + rp(p.bonus) + '</b></div><div>Capai target<b>' + p.hari + ' hari</b></div>' +
      '<div>Hari petugas resi spesial<b>' + (p.det.length - biasa.length) + ' hari</b></div>' +
      '<div>Total pick<b>' + num(tp) + '</b></div><div>Total kesalahan<b>' + ts + '</b></div>' +
      '<div>Total poin salah<b>' + (ts * POIN) + '</b></div>');
    $('#bp-m-rows').html(p.det.map(function (x) {
      if (x.spesial) {
        return '<tr class="bp-spes"><td>' + tgl(x.tgl) + '</td><td colspan="4" class="text-center"><span class="bp-pill sp">Petugas Resi Spesial</span> ' +
          '<span class="bp-info" tabindex="0">i<span class="bp-tip">Pada hari bertugas mengambil resi spesial, resi picker tidak dihitung sama sekali: tanpa bonus dan tidak masuk capai target.</span></span></td></tr>';
      }
      var cls = x.net >= TIER2 ? 'gold' : (x.net >= TARGET ? 'ok' : 'none');
      return '<tr><td>' + tgl(x.tgl) + '</td><td class="text-right">' + num(x.pick) + '</td><td class="text-right">' + x.salah +
        '</td><td class="text-right">' + x.poin + '</td><td class="text-right"><span class="bp-pill ' + cls + '">' + num(x.net) + '</span></td></tr>';
    }).join(''));
    $('#bp-ov').addClass('on');
  }

  $root.on('click', '.bp-detail', function () { buka(parseInt($(this).data('k'), 10)); });
  $root.on('click', '#bp-m-export', function () {
    // Pakai rentang yang dimuat di tabel (bukan isi kotak tanggal yang mungkin sudah diubah).
    window.location.href = '<?= base_url('bonus-picker/export-excel-detail') ?>?kode=' + kodeAktif + '&awal=' + awalAktif + '&akhir=' + akhirAktif;
  });
  $root.on('click', '#bp-m-x',function () { $('#bp-ov').removeClass('on'); });
  $root.on('click', '#bp-ov', function (e) { if (e.target === this) { $('#bp-ov').removeClass('on'); } });
  $root.on('click', '#bp-tampilkan', muat);
  $root.on('click', '#bp-export', function () {
    var a = keIso($('#bp-awal').val()), b = keIso($('#bp-akhir').val());
    if (!a || !b) { muat(); return; } // munculkan pesan format tanggal
    window.location.href = '<?= base_url('bonus-picker/export-excel') ?>?awal=' + a + '&akhir=' + b;
  });
  $root.on('change', '#bp-awal, #bp-akhir', muat);
  $root.on('input', '#bp-cari', render);
  $root.on('click', 'th.bp-sort', function () {
    var k = $(this).data('k');
    sortDir = (sortK === k) ? -sortDir : ((k === 'nama' || k === 'absen') ? 1 : -1);
    sortK = k; render();
  });
  $root.on('click', '.bp-cepat', function () {
    var r = $(this).data('r'), n = new Date();
    if (r === 'bulan') { $('#bp-awal').val(idTgl(new Date(n.getFullYear(), n.getMonth(), 1))); }
    else { var s = new Date(n); s.setDate(s.getDate() - Number(r)); $('#bp-awal').val(idTgl(s)); }
    $('#bp-akhir').val(idTgl(n)); muat();
  });

  muat();
})();
</script>
