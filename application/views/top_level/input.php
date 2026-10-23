<?php
// View menu TIM CS -> Input Top Level. Dimuat lewat AJAX (SPA).
// CS memasukkan stok top level dari tim Restok: nama tim (saran mengikuti ketikan), lalu baris SKU, no rak, qty.
// Setiap simpan menghasilkan satu nomor transfer TTL-TAHUN-NNNNN (stok masuk).
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Input Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-in"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-in'), baris = [], selesai = null, galat = '', sibuk = false;
  var KOSONG = function () { return { sku: '', rak: '', qty: '' }; };
  baris = [KOSONG(), KOSONG(), KOSONG()];

  function judul() {
    return '<div class="tl-ph"><div class="t"><h1>Input Top Level</h1>' +
      T.info('Masukkan stok top level dari tim Restok.\nPilih nama tim dari saran atau ketik bebas.\nSetiap simpan menghasilkan satu nomor transfer TTL-TAHUN-NNNNN untuk stok masuk.\nQty ditambahkan ke rak; rak baru dibuat otomatis.') + '</div></div>';
  }

  function paint() {
    if (selesai) {
      $r.html(judul() + '<section class="tl-panel"><div class="tl-res" style="padding:36px 16px"><div class="ring ok">' + T.ic('check', 26) + '</div>' +
        '<span class="muted">Nomor transfer</span><b class="mono tl-ttl">' + T.esc(selesai.nomor) + '</b>' +
        '<span class="muted">' + selesai.baris + ' baris · ' + selesai.qty + ' pcs masuk ke stok top level</span>' +
        '<div style="display:flex;gap:8px;margin-top:6px"><button type="button" class="tl-btn" data-salin>Salin nomor</button><button type="button" class="tl-btn" data-lagi>Input lagi</button>' +
        '<button type="button" class="tl-btn pri" data-stok>Lihat Stok Top Level</button></div></div></section>');
      return;
    }
    var h = judul() + '<section class="tl-panel tl-pad"><div class="tl-fld" style="max-width:420px"><label for="tl-tim" class="tl-lbl">Tim Restok</label>' +
      '<div><input type="text" id="tl-tim" placeholder="Ketik nama, atau pilih dari saran" autocomplete="off" style="width:100%"></div></div></section>' +
      '<section class="tl-panel"><div class="tl-line tl-line-h"><span>SKU</span><span>No rak top level</span><span>Qty</span><span></span></div><div id="tl-baris"></div>' +
      '<div class="tl-line"><div><button type="button" class="tl-btn sm ghost" data-tambah>' + T.ic('plus', 14) + 'Tambah baris</button></div></div>' +
      '<div class="tl-sumbar"><span class="muted" id="tl-ring"></span><div style="display:flex;gap:10px;align-items:center"><span class="tl-fmsg" id="tl-gal" style="min-height:0"></span>' +
      '<button type="button" class="tl-btn pri" data-simpan>Simpan &amp; buat nomor transfer</button></div></div></section>';
    $r.html(h);
    $('#tl-tim').val(window.__tlTim || '');
    T.combo($('#tl-tim'), function (q, kirim) {
      T.post('cs/input-top-level/tim', { q: q }, function (d) {
        kirim($.map(d.rows, function (x) { return { nilai: x.nama, label: x.nama, grup: x.dari === 'terakhir' ? 'Terakhir dipakai' : 'Pegawai' }; }));
      }, function () { kirim([]); });
    }, function () { window.__tlTim = $('#tl-tim').val(); });
    $('#tl-tim').on('input', function () { window.__tlTim = this.value; });
    gambarBaris();
    ringkas();
  }

  function gambarBaris() {
    var h = '';
    $.each(baris, function (i, b) {
      h += '<div class="tl-line" data-i="' + i + '"><div><input type="text" class="f-sku" placeholder="Ketik kode SKU" autocomplete="off" value="' + T.esc(b.sku) + '"></div>' +
        '<div><input type="text" class="f-rak" placeholder="mis. TL-A1" autocomplete="off" value="' + T.esc(b.rak) + '"></div>' +
        '<div><input type="number" class="f-qty" min="1" placeholder="0" value="' + T.esc(b.qty) + '" style="text-align:right"></div>' +
        '<div><button type="button" class="tl-btn sm ghost" data-hapus aria-label="Hapus baris"' + (baris.length < 2 ? ' disabled' : '') + '>' + T.ic('x', 14) + '</button></div></div>';
    });
    $('#tl-baris').html(h);
    $('#tl-baris .f-sku').each(function () {
      var $in = $(this), i = +$in.closest('.tl-line').data('i');
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/sku', { q: q }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.sku, label: x.sku, sub: (x.nama ? x.nama + ' · ' : '') + 'rak ' + x.rak }; }));
        }, function () { kirim([]); });
      }, function () { baris[i].sku = $in.val(); ringkas(); $in.closest('.tl-line').find('.f-rak').focus(); });
    });
  }

  function ringkas() {
    var n = 0, q = 0;
    $.each(baris, function (i, b) { if (b.sku && b.rak && parseInt(b.qty, 10) > 0) { n++; q += parseInt(b.qty, 10); } });
    $('#tl-ring').text(n ? n + ' baris · ' + q + ' pcs' : '');
  }

  function simpan() {
    var tim = $.trim($('#tl-tim').val());
    $('#tl-gal').text('');
    if (!tim) { $('#tl-gal').text('Isi nama tim Restok.'); $('#tl-tim').focus(); return; }
    var kirim = $.grep(baris, function (b) { return b.sku || b.rak || b.qty; });
    if (!kirim.length) { $('#tl-gal').text('Isi minimal satu baris.'); return; }
    if (sibuk) return;
    sibuk = true;
    $('[data-simpan]').prop('disabled', true);
    T.post('cs/input-top-level/simpan', { tim: tim, rows: kirim }, function (d) {
      sibuk = false; selesai = d; window.__tlTim = tim; paint();
    }, function (m) { sibuk = false; $('[data-simpan]').prop('disabled', false); $('#tl-gal').text(m); });
  }

  $r.on('input', '.f-sku,.f-rak,.f-qty', function () {
    var $l = $(this).closest('.tl-line'), b = baris[+$l.data('i')];
    b.sku = $.trim($l.find('.f-sku').val());
    b.rak = $.trim($l.find('.f-rak').val());
    b.qty = $l.find('.f-qty').val();
    ringkas();
  });
  $r.on('keydown', '.f-qty', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var i = +$(this).closest('.tl-line').data('i');
    if (i === baris.length - 1) { baris.push(KOSONG()); gambarBaris(); }
    $('#tl-baris .tl-line').eq(i + 1).find('.f-sku').focus();
  });
  $r.on('keydown', '.f-rak', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).closest('.tl-line').find('.f-qty').focus(); }
  });
  $r.on('click', '[data-tambah]', function () { baris.push(KOSONG()); gambarBaris(); $('#tl-baris .tl-line:last .f-sku').focus(); });
  $r.on('click', '[data-hapus]', function () { baris.splice(+$(this).closest('.tl-line').data('i'), 1); if (!baris.length) baris.push(KOSONG()); gambarBaris(); ringkas(); });
  $r.on('click', '[data-simpan]', simpan);
  $r.on('click', '[data-lagi]', function () { selesai = null; baris = [KOSONG(), KOSONG(), KOSONG()]; paint(); });
  $r.on('click', '[data-stok]', function () { $('a[href="cs/stok-top-level"]').first().trigger('click'); });
  $r.on('click', '[data-salin]', function () {
    var n = selesai.nomor;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(n).then(function () { T.toast('Nomor ' + n + ' disalin'); }, function () { T.toast(n); });
    } else T.toast(n);
  });

  paint();
})();
</script>
