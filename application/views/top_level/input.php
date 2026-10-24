<?php
// View menu TIM CS -> Input Top Level. Dimuat lewat AJAX (SPA).
// CS memasukkan stok top level dari tim Restok: Petugas List dan Petugas Input (saran mengikuti ketikan), lalu baris SKU, no rak, qty.
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
  // Arah transfer: tanpa default, penginput wajib memilih. MASUK menambah stok rak, KELUAR mengurangi stok rak (diingat selama menu tidak di-reload).
  var arah = window.__tlArah === 'KELUAR' || window.__tlArah === 'MASUK' ? window.__tlArah : '';
  var keluar = function () { return arah === 'KELUAR'; };
  baris = [KOSONG(), KOSONG(), KOSONG()];

  function judul() {
    return '<div class="tl-ph"><div class="t"><h1>Input Top Level</h1>' +
      T.info('Pilih arah: Masuk menambah stok rak, Keluar mengurangi stok rak.\nArah keluar hanya bisa untuk SKU dan rak yang stoknya cukup.\nArah keluar: SKU yang hanya ada di satu rak mengisi no rak otomatis; kalau ada di beberapa rak, saran rak muncul saat no rak diketik.\nArah masuk: saran no rak muncul saat no rak diketik.\nMasukkan stok top level dari tim Restok.\nPetugas List dan tanggal list: yang mendata stok. Petugas Input dan tanggal input: yang menginput.\nPilih nama dari saran atau ketik bebas.\nSetiap simpan menghasilkan satu nomor transfer TTL-TAHUN-NNNNN untuk stok masuk.\nRak baru dibuat otomatis.\nSKU dan rak yang sudah punya stok ditandai merah sebagai duplikat dan tidak bisa disimpan.\nSKU dan rak yang sama boleh diisi lebih dari satu baris asalkan qty-nya berbeda; qty dijumlahkan. Qty yang sama persis ditandai dobel.') + '</div></div>';
  }

  function paint() {
    if (selesai) {
      $r.html(judul() + '<section class="tl-panel"><div class="tl-res" style="padding:36px 16px"><div class="ring ok">' + T.ic('check', 26) + '</div>' +
        '<span class="muted">Nomor transfer</span><b class="mono tl-ttl">' + T.esc(selesai.nomor) + '</b>' +
        '<span class="muted">' + selesai.baris + ' baris · ' + selesai.qty + ' pcs · ' + tglIndo(selesai.tanggal) + '</span>' +
        '<div style="display:flex;gap:8px;margin-top:6px"><button type="button" class="tl-btn" data-salin>Salin nomor</button><button type="button" class="tl-btn" data-lagi>Input lagi</button>' +
        '<button type="button" class="tl-btn pri" data-stok>Lihat Stok Top Level</button></div></div></section>');
      return;
    }
    // Kartu = nama petugas (dengan saran) + tanggalnya. Petugas List <-> TGL LIST, Petugas Input <-> TGL INPUT di Excel upload.
    var kartu = function (judulKartu, idIn, labelTgl, idTgl) {
      return '<section class="tl-panel tl-pad"><div class="tl-pair">' +
        '<label for="' + idIn + '" class="tl-lbl">' + judulKartu + '</label><label for="' + idTgl + '" class="tl-lbl">' + labelTgl + '</label>' +
        '<div><input type="text" id="' + idIn + '" placeholder="Ketik nama, atau pilih dari saran" autocomplete="off" style="width:100%"></div>' +
        '<div><input type="date" id="' + idTgl + '" max="' + hariIni() + '" style="width:100%"></div></div></section>';
    };
    var h = judul() + '<div class="tl-cards3"><section class="tl-panel tl-pad"><label class="tl-lbl" for="tl-arah">Arah</label>' +
      '<select id="tl-arah" class="tl-arah ' + arah.toLowerCase() + '"><option value=""' + (arah ? '' : ' selected') + '>Pilih arah</option>' +
      '<option value="MASUK"' + (arah === 'MASUK' ? ' selected' : '') + '>Masuk</option>' +
      '<option value="KELUAR"' + (arah === 'KELUAR' ? ' selected' : '') + '>Keluar</option></select></section>' +
      kartu('Petugas List', 'tl-list', 'Tanggal list', 'tl-tgl-list') +
      kartu('Petugas Input', 'tl-pinput', 'Tanggal input', 'tl-tgl') + '</div>' +
      '<section class="tl-panel"><div class="tl-line tl-line-h"><span>SKU</span><span>No rak top level</span><span>Qty</span><span></span></div><div id="tl-baris"></div>' +
      '<div class="tl-line"><div><button type="button" class="tl-btn sm ghost" data-tambah>' + T.ic('plus', 14) + 'Tambah baris</button></div></div>' +
      '<div class="tl-sumbar"><span class="muted" id="tl-ring"></span><div style="display:flex;gap:10px;align-items:center"><span class="tl-fmsg" id="tl-gal" style="min-height:0"></span>' +
      '<button type="button" class="tl-btn pri" data-simpan>Simpan</button></div></div></section>';
    $r.html(h);
    $('#tl-list').val(window.__tlList || '');
    // Petugas Input bawaan = akun yang sedang login; tetap bisa diganti (pilihan terakhir diingat selama menu tidak di-reload).
    if (window.__tlInput === undefined) window.__tlInput = <?= json_encode((string) (isset($akun_login) ? $akun_login : '')) ?>;
    $('#tl-pinput').val(window.__tlInput || '');
    $('#tl-tgl').val(window.__tlTgl || hariIni());
    $('#tl-tgl-list').val(window.__tlTglList || hariIni());
    $.each([['#tl-list', '__tlList'], ['#tl-pinput', '__tlInput']], function (i, p) {
      T.combo($(p[0]), function (q, kirim) {
        T.post('cs/input-top-level/tim', { q: q }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.nama, label: x.nama, grup: x.dari === 'terakhir' ? 'Terakhir dipakai' : 'Pegawai' }; }));
        }, function () { kirim([]); });
      }, function () { window[p[1]] = $(p[0]).val(); });
      $(p[0]).on('input', function () { window[p[1]] = this.value; });
    });
    $('#tl-arah').on('change', function () { arah = window.__tlArah = this.value; $(this).removeClass('masuk keluar').addClass(arah.toLowerCase()); dup = {};
      // Ganti arah: rak isian otomatis dilepas (masuk) atau diisi ulang dari SKU yang sudah ada (keluar).
      $.each(baris, function (i, b) {
        if (!keluar() && b.auto) { b.rak = ''; b.auto = false; $('#tl-baris .tl-line').eq(i).find('.f-rak').val(''); }
        else if (keluar() && b.sku) autoRak(i);
      });
      ringkas(); tandai(); cekDb(); });
    $('#tl-tgl').on('input', function () { window.__tlTgl = this.value; });
    $('#tl-tgl-list').on('input', function () { window.__tlTglList = this.value; });
    gambarBaris();
    ringkas();
  }

  function gambarBaris() {
    var h = '';
    $.each(baris, function (i, b) {
      h += '<div class="tl-line" data-i="' + i + '"><div><input type="text" class="f-sku" placeholder="Ketik kode SKU" autocomplete="off" value="' + T.esc(b.sku) + '"></div>' +
        '<div><input type="text" class="f-rak" placeholder="mis. TL-A1" autocomplete="off" value="' + T.esc(b.rak) + '"></div>' +
        '<div><input type="number" class="f-qty" min="1" placeholder="0" value="' + T.esc(b.qty) + '" style="text-align:right"></div>' +
        '<div><button type="button" class="tl-btn sm ghost" data-hapus aria-label="Hapus baris"' + (baris.length < 2 ? ' disabled' : '') + '>' + T.ic('x', 14) + '</button></div>' +
        '<div class="tl-dupmsg"></div></div>';
    });
    $('#tl-baris').html(h);
    tandai();
    $('#tl-baris .f-sku').each(function () {
      var $in = $(this), i = +$in.closest('.tl-line').data('i');
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/sku', { q: q }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.sku, label: x.sku }; }));
        }, function () { kirim([]); });
      }, function () { baris[i].sku = $in.val(); autoRak(i); ringkas(); tandai(); cekDb(); $in.closest('.tl-line').find('.f-rak').focus(); });
    });
    // Saran no rak muncul hanya saat diketik. Keluar: rak yang menyimpan stok SKU baris itu. Masuk: semua rak sesuai ketikan.
    $('#tl-baris .f-rak').each(function () {
      var $in = $(this), i = +$in.closest('.tl-line').data('i');
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/rak', { sku: baris[i] ? baris[i].sku : '', q: q, arah: arah }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.rak, label: x.rak, sub: x.qty ? x.qty + ' pcs' : '' }; }));
        }, function () { kirim([]); });
      }, function () { baris[i].rak = $in.val(); baris[i].auto = false; ringkas(); tandai(); cekDb(); $in.closest('.tl-line').find('.f-qty').focus(); });
    });
  }

  // Arah keluar: begitu SKU diketik, kalau SKU itu hanya ada di satu rak maka no rak terisi otomatis.
  // Lebih dari satu rak: dibiarkan kosong, saran muncul saat no rak diketik. Isian otomatis ikut dilepas kalau SKU berubah.
  var timerRak = {};
  function autoRak(i) {
    clearTimeout(timerRak[i]);
    if (!keluar()) return;
    timerRak[i] = setTimeout(function () {
      var b = baris[i], sku = b && b.sku;
      if (!b) return;
      var terapkan = function (rak) {
        if (baris[i] !== b) return;
        b.rak = rak; b.auto = !!rak;
        $('#tl-baris .tl-line').eq(i).find('.f-rak').val(rak);
        ringkas(); tandai(); cekDb();
      };
      if (!sku) { if (b.auto) terapkan(''); return; }
      T.post('cs/input-top-level/rak', { sku: sku, q: '', arah: 'KELUAR' }, function (d) {
        if (baris[i] !== b || b.sku !== sku || !keluar()) return;
        if (d.rows.length === 1 && (!b.rak || b.auto)) terapkan(d.rows[0].rak);
        else if (d.rows.length !== 1 && b.auto) terapkan('');
      });
    }, 300);
  }

  // ---------- guard duplikat ----------
  // SKU + no rak yang sudah tersimpan dengan stok bukan nol = duplikat (bukan penambahan): baris langsung merah.
  var dup = {}, timerCek = null;
  var kunci = function (b) { return b.sku + '|' + String(b.rak).toUpperCase(); };
  function lengkap(b) { return b.sku && b.rak; }

  function tandai() {
    var lihat = {}, ada = 0;
    $('#tl-baris .tl-line').each(function () {
      var $l = $(this), b = baris[+$l.data('i')], msg = '';
      if (b && lengkap(b)) {
        var k = kunci(b), q = parseInt(b.qty, 10) > 0 ? '|' + parseInt(b.qty, 10) : '';
        // SKU + rak sama dengan qty berbeda boleh (dijumlahkan); qty sama persis dianggap dobel.
        if (q && lihat[k + q] !== undefined) msg = 'Dobel: SKU, rak, dan qty sama dengan baris ' + (lihat[k + q] + 1) + '.';
        else if (keluar()) {
          if (q) lihat[k + q] = +$l.data('i');
          var stok = dup[k] || 0;
          if (!stok) msg = 'Tidak ada stok di rak ini.';
          else if (parseInt(b.qty, 10) > stok) msg = 'Stok di rak ini hanya ' + stok + ' pcs.';
        } else {
          if (q) lihat[k + q] = +$l.data('i');
          if (dup[k]) msg = 'Duplikat: stok ' + dup[k] + ' sudah tersimpan di rak ini. ' + T.info('SKU dan no rak ini sudah tersimpan dengan stok ' + dup[k] + '.\nInput tidak menambah stok yang sudah ada.\nUntuk mengoreksi, pakai Edit di menu Stok Top Level.');
        }
      }
      $l.toggleClass('bad', !!msg);
      $l.find('.tl-dupmsg').html(msg);
      if (msg) ada++;
    });
    return ada;
  }

  function cekDb() {
    clearTimeout(timerCek);
    timerCek = setTimeout(function () {
      var kirim = $.map($.grep(baris, lengkap), function (b) { return { sku: b.sku, rak: String(b.rak).toUpperCase() }; });
      if (!kirim.length) { dup = {}; tandai(); return; }
      T.post('cs/input-top-level/cek', { rows: kirim }, function (d) {
        dup = {};
        $.each(d.rows, function (i, x) { dup[x.sku + '|' + String(x.rak).toUpperCase()] = x.qty; });
        tandai();
      });
    }, 350);
  }

  function ringkas() {
    var n = 0, q = 0;
    $.each(baris, function (i, b) { if (b.sku && b.rak && parseInt(b.qty, 10) > 0) { n++; q += parseInt(b.qty, 10); } });
    $('#tl-ring').text(n ? n + ' baris · ' + q + ' pcs' : '');
  }

  function hariIni() {
    var d = new Date(), p = function (n) { return ('0' + n).slice(-2); };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
  }
  function tglIndo(s) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : s; }

  // Memeriksa isian lalu menampilkan konfirmasi ringkas sebelum benar-benar menyimpan.
  function simpan() {
    var pl = $.trim($('#tl-list').val()), pi = $.trim($('#tl-pinput').val()), tgl = $('#tl-tgl').val(), tglL = $('#tl-tgl-list').val();
    $('#tl-gal').text('');
    window.__tlList = pl; window.__tlInput = pi; window.__tlTgl = tgl; window.__tlTglList = tglL;
    if (!arah) { $('#tl-gal').text('Pilih arah: Masuk atau Keluar.'); $('#tl-arah').focus(); return; }
    if (!pl) { $('#tl-gal').text('Isi nama petugas list.'); $('#tl-list').focus(); return; }
    if (!pi) { $('#tl-gal').text('Isi nama petugas input.'); $('#tl-pinput').focus(); return; }
    if (!tglL) { $('#tl-gal').text('Isi tanggal list.'); $('#tl-tgl-list').focus(); return; }
    if (!tgl) { $('#tl-gal').text('Isi tanggal input.'); $('#tl-tgl').focus(); return; }
    if (tglL > tgl) { $('#tl-gal').text('Tanggal list tidak boleh setelah tanggal input.'); $('#tl-tgl-list').focus(); return; }
    var kirim = $.grep(baris, function (b) { return b.sku || b.rak || b.qty; });
    if (!kirim.length) { $('#tl-gal').text('Isi minimal satu baris.'); return; }
    var bad = $.grep(kirim, function (b) { return !b.sku || !b.rak || !(parseInt(b.qty, 10) > 0); });
    if (bad.length) { $('#tl-gal').text('Lengkapi SKU, no rak, dan qty (lebih dari 0) di semua baris yang diisi.'); return; }
    if (tandai() > 0) { $('#tl-gal').text(keluar() ? 'Ada baris bermasalah (merah). Hapus atau ubah baris itu.' : 'Ada baris duplikat (merah). Hapus atau ubah baris itu.'); return; }
    var q = 0;
    $.each(kirim, function (i, b) { q += parseInt(b.qty, 10); });
    var b = '<div class="tl-db"><dl class="tl-kv"><dt>Arah</dt><dd><b>' + (keluar() ? 'Keluar' : 'Masuk') + '</b></dd><dt>Petugas List</dt><dd>' + T.esc(pl) + ' · ' + tglIndo(tglL) + '</dd><dt>Petugas Input</dt><dd>' + T.esc(pi) + ' · ' + tglIndo(tgl) + '</dd><dt>Total</dt><dd><b>' + kirim.length + ' baris · ' + q + ' pcs</b></dd></dl>' +
      '<div class="tl-tw tl-panel" style="max-height:240px"><table class="tl-tbl"><thead><tr><th>SKU</th><th>No rak</th><th class="n">Qty</th></tr></thead><tbody>' +
      $.map(kirim, function (x) { return '<tr><td class="mono">' + T.esc(x.sku) + '</td><td class="mono">' + T.esc(String(x.rak).toUpperCase()) + '</td><td class="n">' + T.esc(x.qty) + '</td></tr>'; }).join('') +
      '</tbody></table></div></div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-ya>Simpan</button></div>';
    var $m = T.modal({ judul: keluar() ? 'Simpan keluar top level?' : 'Simpan input top level?', body: b });
    $m.on('click', '[data-ya]', function () {
      if (sibuk) return;
      sibuk = true;
      $(this).prop('disabled', true);
      T.post('cs/input-top-level/simpan', { petugas_list: pl, petugas_input: pi, tanggal: tgl, tanggal_list: tglL, arah: arah, rows: kirim }, function (d) {
        sibuk = false; T.closeModal(); selesai = d; arah = window.__tlArah = ''; paint();
      }, function (m2) { sibuk = false; T.closeModal(); $('#tl-gal').text(m2); cekDb(); });
    });
    $m.find('[data-ya]').trigger('focus');
  }

  $r.on('input', '.f-sku,.f-rak,.f-qty', function () {
    var $l = $(this).closest('.tl-line'), b = baris[+$l.data('i')];
    b.sku = $.trim($l.find('.f-sku').val());
    b.rak = $.trim($l.find('.f-rak').val());
    b.qty = $l.find('.f-qty').val();
    if ($(this).hasClass('f-rak')) b.auto = false;
    if ($(this).hasClass('f-sku')) autoRak(+$l.data('i'));
    ringkas();
    if (!$(this).hasClass('f-qty')) { tandai(); cekDb(); }
    else if (keluar()) tandai();
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
  $r.on('click', '[data-hapus]', function () { baris.splice(+$(this).closest('.tl-line').data('i'), 1); if (!baris.length) baris.push(KOSONG()); gambarBaris(); ringkas(); cekDb(); });
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
