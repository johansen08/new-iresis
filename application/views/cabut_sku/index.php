<?php
// View menu TIM MONITORING -> Cabut SKU. Dimuat lewat AJAX (SPA); selector diawali #cs-root.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Cabut SKU</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Menu ini hanya untuk webmaster.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<style>
  #cs-root .cs-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:12px; }
  #cs-root .cs-bar .form-control { width:auto; display:inline-block; }
  #cs-root .cs-info { color:#6b7a8c; font-size:12px; }
  #cs-root .cs-prog { height:8px; background:#e4e9ef; border-radius:4px; overflow:hidden; margin-bottom:10px; display:none; }
  #cs-root .cs-prog > div { height:100%; width:0; background:#2563eb; transition:width .2s; }
  #cs-root .cs-num { text-align:right; font-variant-numeric:tabular-nums; }
</style>
<div id="cs-root">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Cabut SKU</strong>
      <small>&nbsp; kategori <?= htmlspecialchars(implode(', ', $kategori)) ?> dengan stok total Jubelio &le; <?= (int) $batas ?></small></h3></div>
    <div class="panel-body">
      <div class="cs-bar">
        <button type="button" class="btn btn-primary" id="cs-perbarui"><i class="fa fa-refresh"></i> Perbarui</button>
        <select id="cs-kat" class="form-control"><option value="">Semua kategori</option>
          <?php foreach ($kategori as $k) : ?><option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option><?php endforeach; ?>
        </select>
        <input type="text" id="cs-cari" class="form-control" placeholder="Cari SKU / nama / rak" style="width:240px">
        <span class="cs-info" id="cs-status"></span>
      </div>
      <div class="cs-prog" id="cs-prog"><div></div></div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed">
          <thead><tr><th>#</th><th>SKU</th><th>Nama</th><th>Variasi</th><th>No Rak</th><th>Kategori</th><th class="cs-num">Stok Total</th></tr></thead>
          <tbody id="cs-body"><tr><td colspan="7" class="text-center cs-info">Memuat...</td></tr></tbody>
        </table>
      </div>
      <div class="cs-bar">
        <button type="button" class="btn btn-default btn-sm" id="cs-prev">&laquo; Sebelumnya</button>
        <span class="cs-info" id="cs-hal"></span>
        <button type="button" class="btn btn-default btn-sm" id="cs-next">Berikutnya &raquo;</button>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var semua = [], tampil = [], hal = 1, UKURAN = 100, sedang = false;
  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function fmtWaktu(t) { return t ? t.replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}).*/, '$3/$2/$1 $4') : ''; }

  function render() {
    var kat = $('#cs-kat').val(), q = $.trim($('#cs-cari').val()).toLowerCase();
    tampil = semua.filter(function (r) {
      if (kat && r.kategori_sku !== kat) { return false; }
      return !q || (r.id_sku + ' ' + (r.nama_sku || '') + ' ' + (r.no_rak || '')).toLowerCase().indexOf(q) !== -1;
    });
    var total = Math.max(1, Math.ceil(tampil.length / UKURAN));
    if (hal > total) { hal = total; }
    var awal = (hal - 1) * UKURAN, html = '';
    tampil.slice(awal, awal + UKURAN).forEach(function (r, i) {
      html += '<tr><td>' + (awal + i + 1) + '</td><td>' + esc(r.id_sku) + '</td><td>' + esc(r.nama_sku) + '</td><td>' + esc(r.variasi) +
        '</td><td>' + esc(r.no_rak) + '</td><td>' + esc(r.kategori_sku) + '</td><td class="cs-num">' + esc(r.stok_total) + '</td></tr>';
    });
    $('#cs-body').html(html || '<tr><td colspan="7" class="text-center cs-info">Tidak ada SKU yang memenuhi syarat. Klik Perbarui bila stok belum pernah diambil.</td></tr>');
    $('#cs-hal').text('Halaman ' + hal + ' / ' + total + ' · ' + tampil.length + ' SKU');
    $('#cs-prev').prop('disabled', hal <= 1);
    $('#cs-next').prop('disabled', hal >= total);
  }

  function muat() {
    $.ajax({ url: 'cabut-sku/get-data', type: 'POST', dataType: 'json', timeout: 60000,
      success: function (r) {
        if (r.code !== 200) { $('#cs-status').text(r.message); return; }
        var d = r.data || {}, rs = d.ringkasan || {};
        semua = d.baris || [];
        var s = rs.diperbarui ? 'Stok Jubelio diperbarui ' + fmtWaktu(rs.diperbarui) : 'Stok belum pernah diambil dari Jubelio, klik Perbarui.';
        if (rs.belum_ada > 0) { s += ' · ' + rs.belum_ada + ' SKU berkategori belum punya data stok.'; }
        $('#cs-status').text(s);
        render();
      },
      error: function (xhr) { $('#cs-status').text('Gagal memuat data. ' + (xhr.responseText || '').substring(0, 160)); $('#cs-body').empty(); }
    });
  }

  function halaman(n) {
    $.ajax({ url: 'cabut-sku/perbarui', type: 'POST', dataType: 'json', data: { halaman: n }, timeout: 60000,
      success: function (r) {
        if (r.code !== 200) { selesai('Berhenti di halaman ' + n + ': ' + r.message); return; }
        var d = r.data;
        $('#cs-prog > div').css('width', Math.round(d.halaman / d.total_halaman * 100) + '%');
        $('#cs-status').text('Mengambil stok Jubelio: halaman ' + d.halaman + ' / ' + d.total_halaman + '...');
        if (d.halaman < d.total_halaman) { halaman(n + 1); } else { selesai(null); }
      },
      error: function (xhr, st) { selesai('Berhenti di halaman ' + n + ': ' + (st === 'timeout' ? 'waktu habis' : (xhr.responseText || '').substring(0, 160))); }
    });
  }

  function selesai(galat) {
    sedang = false;
    $('#cs-perbarui').prop('disabled', false);
    $('#cs-prog').hide();
    muat();
    if (galat) { setTimeout(function () { $('#cs-status').text(galat + ' (data yang sudah terambil tetap tersimpan)'); }, 400); }
  }

  $('#cs-perbarui').on('click', function () {
    if (sedang) { return; }
    sedang = true;
    $(this).prop('disabled', true);
    $('#cs-prog').show().children().css('width', '0');
    $('#cs-status').text('Menghubungi Jubelio...');
    halaman(1);
  });
  $('#cs-kat').on('change', function () { hal = 1; render(); });
  $('#cs-cari').on('input', function () { hal = 1; render(); });
  $('#cs-prev').on('click', function () { hal--; render(); });
  $('#cs-next').on('click', function () { hal++; render(); });
  muat();
})();
</script>
