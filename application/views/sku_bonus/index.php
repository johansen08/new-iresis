<?php
// View menu TIM PURCHASING -> Daftar SKU Bonus. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #sb-root supaya ikut hilang saat user
// pindah menu. Tanpa <form> sengaja -- submit form dicegat handler global.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Daftar SKU Bonus</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="sb-root">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Daftar SKU Bonus</strong>
            <small class="text-muted" style="margin-left:8px;">resi yang berisi salah satu SKU aktif di bawah mendapat 1 AKS27-RDM (PINSET)</small>
          </h3>
        </div>
        <div class="panel-body">
          <div class="row" style="margin-bottom:12px;">
            <div class="col-md-4 col-sm-6 col-xs-12" style="margin-bottom:8px;">
              <input type="text" id="sb-cari" class="form-control" placeholder="Cari SKU" autocomplete="off" />
            </div>
            <div class="col-md-5 col-md-offset-3 col-sm-6 col-xs-12 sb-tambah">
              <div class="input-group">
                <input type="text" id="sb-tambah" class="form-control" placeholder="Tambah SKU dari master" autocomplete="off" />
                <span class="input-group-btn">
                  <button type="button" id="sb-btn-tambah" class="btn btn-primary">Tambah</button>
                </span>
              </div>
            </div>
          </div>

          <div id="sb-pesan" class="alert hidden" role="status" style="margin-bottom:12px;"></div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="sb-tabel" style="margin-bottom:10px;">
              <thead>
                <tr><th>SKU</th><th style="width:140px;">Status</th><th style="width:150px;"></th></tr>
              </thead>
              <tbody><tr><td colspan="3" class="text-muted text-center">Memuat…</td></tr></tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-sm-5 col-xs-12 text-muted" id="sb-info" style="padding-top:7px;"></div>
            <div class="col-sm-7 col-xs-12 text-right"><ul class="pagination" id="sb-halaman" style="margin:0;"></ul></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#sb-root');
  var URL = {
    data:  'purchasing/sku-bonus/data',
    saran: 'purchasing/sku-bonus/saran',
    tambah: 'purchasing/sku-bonus/tambah',
    ubah:  'purchasing/sku-bonus/ubah-status'
  };
  var q = '', halaman = 1, reqData = 0, timerCari = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function pesan(tipe, teks) {
    $('#sb-pesan').removeClass('hidden alert-success alert-danger alert-warning').addClass('alert-' + tipe).text(teks);
  }
  function jsonGagal(xhr) {
    // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
    pesan('danger', 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
  }

  // ---------- tabel + halaman ----------
  function muat() {
    var no = ++reqData;
    $.getJSON(URL.data, { q: q, halaman: halaman }).done(function (res) {
      if (no !== reqData) { return; } // balasan lama, abaikan
      if (Number(res.code) !== 200 || !res.data) { pesan('danger', res.message || 'Gagal memuat daftar.'); return; }
      var d = res.data;
      halaman = d.halaman;
      var $tb = $('#sb-tabel tbody').empty();
      if (!d.rows.length) {
        $tb.append('<tr><td colspan="3" class="text-muted text-center">SKU tidak ditemukan.</td></tr>');
      }
      $.each(d.rows, function (i, r) {
        var aktif = Number(r.aktif) === 1;
        $tb.append('<tr><td><code>' + esc(r.sku) + '</code></td>' +
          '<td><span class="label label-' + (aktif ? 'success' : 'default') + '">' + (aktif ? 'aktif' : 'nonaktif') + '</span></td>' +
          '<td><button type="button" class="btn btn-default btn-sm sb-ubah" data-id="' + esc(r.id) + '">' +
          (aktif ? 'Nonaktifkan' : 'Aktifkan') + '</button></td></tr>');
      });
      $('#sb-info').text(d.total ? 'Menampilkan ' + d.dari + '-' + d.sampai + ' dari ' + d.total : '0 SKU');
      gambarHalaman(d.halaman, d.jml_hal);
    }).fail(function (xhr) { jsonGagal(xhr); });
  }
  function gambarHalaman(sekarang, jumlah) {
    var $p = $('#sb-halaman').empty();
    function item(label, hal, nonaktif, aktif, aria) {
      $p.append('<li class="' + (nonaktif ? 'disabled ' : '') + (aktif ? 'active' : '') + '"><a href="javascript:void(0)" class="nojs sb-hal" data-hal="' + hal + '"' +
        (aria ? ' aria-label="' + aria + '"' : '') + '>' + label + '</a></li>');
    }
    item('Sebelumnya', sekarang - 1, sekarang <= 1, false);
    for (var i = 1; i <= jumlah; i++) { item(i, i, false, i === sekarang, 'Halaman ' + i); }
    item('Berikutnya', sekarang + 1, sekarang >= jumlah, false);
  }
  $root.on('click', '.sb-hal', function (e) {
    e.preventDefault(); e.stopPropagation();
    if ($(this).parent().hasClass('disabled')) { return; }
    halaman = Number($(this).data('hal'));
    muat();
  });

  // ---------- cari: tabel mengikuti ketikan ----------
  $root.on('input', '#sb-cari', function () {
    q = $.trim($(this).val());
    halaman = 1;
    clearTimeout(timerCari);
    timerCari = setTimeout(muat, 200);
  });

  // ---------- ubah status ----------
  $root.on('click', '.sb-ubah', function () {
    var $b = $(this).prop('disabled', true);
    $.post(URL.ubah, { id: $b.data('id') }, null, 'json').done(function (res) {
      pesan(Number(res.code) === 200 ? 'warning' : 'danger', res.message);
      muat();
    }).fail(jsonGagal);
  });

  // ---------- tambah SKU + rekomendasi SKU standar ----------
  // Dropdown dibuat sendiri: jquery-ui.min.js di proyek ini tanpa widget autocomplete.
  var $in = $('#sb-tambah');
  var $saran = $('<ul class="sb-saran hidden"></ul>').insertAfter($in.closest('.input-group'));
  var item = [], aktifIdx = -1, timerSaran = null, reqSaran = 0;

  function tutup() { $saran.addClass('hidden').empty(); item = []; aktifIdx = -1; }
  function sorot(i) { aktifIdx = i; $saran.children('li').removeClass('aktif').eq(i).addClass('aktif'); }
  function pilih(i) { if (item[i]) { $in.val(item[i].sku); tutup(); $in.focus(); } }
  function cariSaran() {
    var no = ++reqSaran;
    $.getJSON(URL.saran, { q: $.trim($in.val()) }).done(function (data) {
      if (no !== reqSaran) { return; }
      item = $.isArray(data) ? data : [];
      $saran.empty();
      if (!item.length) {
        $saran.append('<li class="sb-kosong text-muted">Tidak ada di master SKU atau sudah ada di daftar.</li>').removeClass('hidden');
        return;
      }
      $saran.append('<li class="sb-judul text-muted">SKU standar dari master</li>');
      $.each(item, function (i, it) {
        $saran.append('<li class="sb-opsi" data-i="' + i + '"><strong>' + esc(it.sku) + '</strong></li>');
      });
      $saran.removeClass('hidden');
      sorot(-1);
    }).fail(function () { tutup(); });
  }
  $in.on('input focus', function () { clearTimeout(timerSaran); timerSaran = setTimeout(cariSaran, 150); });
  $in.on('blur', function () { setTimeout(tutup, 150); });
  $in.on('keydown', function (e) {
    var terbuka = !$saran.hasClass('hidden');
    if (e.key === 'ArrowDown' && terbuka && item.length) { e.preventDefault(); sorot(Math.min(aktifIdx + 1, item.length - 1)); }
    else if (e.key === 'ArrowUp' && terbuka && item.length) { e.preventDefault(); sorot(Math.max(aktifIdx - 1, 0)); }
    else if (e.key === 'Escape' && terbuka) { e.preventDefault(); tutup(); }
    else if (e.key === 'Enter') {
      e.preventDefault();
      if (terbuka && aktifIdx >= 0) { pilih(aktifIdx); } else { tambah(); }
    }
  });
  $saran.on('mousedown', '.sb-opsi', function (e) { e.preventDefault(); pilih(Number($(this).data('i'))); });

  function tambah() {
    var sku = $.trim($in.val());
    if (sku === '') { pesan('danger', 'Isi kode SKU dulu.'); return; }
    $('#sb-btn-tambah').prop('disabled', true);
    $.post(URL.tambah, { sku: sku }, null, 'json').done(function (res) {
      if (Number(res.code) === 200) {
        pesan('success', res.message);
        $in.val(''); $('#sb-cari').val(''); q = ''; halaman = 1;
      } else {
        pesan('danger', res.message);
        // Duplikat: arahkan tabel ke SKU yang sudah ada supaya barisnya terlihat.
        if (res.data && res.data.sku) { $('#sb-cari').val(res.data.sku); q = res.data.sku; halaman = 1; }
      }
      tutup();
      muat();
    }).fail(jsonGagal).always(function () { $('#sb-btn-tambah').prop('disabled', false); });
  }
  $root.on('click', '#sb-btn-tambah', tambah);

  muat();
})();
</script>
<style>
  #sb-root .sb-tambah { position: relative; }
  /* Font template iresis kecil (12-13px); menu ini dibaca dari jauh, jadi dibesarkan. */
  #sb-root .panel-title { font-size: 18px; }
  #sb-root .panel-title small { font-size: 14px; }
  #sb-root .form-control, #sb-root .input-group .btn { height: 44px; font-size: 18px; }
  #sb-root .btn-primary { padding: 0 22px; }
  #sb-root #sb-pesan { font-size: 17px; }
  #sb-root .table > thead > tr > th { font-size: 16px; padding: 12px 14px; }
  #sb-root .table > tbody > tr > td { font-size: 18px; padding: 10px 14px; vertical-align: middle; }
  #sb-root .table code { font-size: 18px; font-weight: 600; }
  #sb-root .table .label { font-size: 14px; padding: 5px 10px; }
  #sb-root .table .btn { font-size: 15px; padding: 7px 14px; }
  #sb-root #sb-info { font-size: 16px; }
  #sb-root .pagination > li > a { font-size: 16px; padding: 8px 14px; }
  .sb-saran {
    position: absolute; left: 15px; right: 15px; top: 46px; z-index: 99999;
    margin: 0; padding: 0; list-style: none; max-height: 340px; overflow-y: auto; font-size: 18px;
    background: #fff; border: 1px solid #d1d5db; box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  }
  .sb-saran li { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; }
  .sb-saran li.sb-opsi { cursor: pointer; }
  .sb-saran li.sb-opsi:hover, .sb-saran li.sb-opsi.aktif { background: #337ab7; color: #fff; }
  .sb-saran li.sb-opsi:hover .text-muted, .sb-saran li.sb-opsi.aktif .text-muted { color: #dbe7f3; }
  .sb-saran li.sb-judul { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
</style>
