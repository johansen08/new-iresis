<?php
// View menu TIM CS -> Daftar Transfer Top Level. Dimuat lewat AJAX (SPA).
// Semua nomor transfer TTL-TAHUN-NNNNN (masuk dan keluar), terbaru di atas. Tab arah dengan jumlah, filter sumber (banyak pilihan),
// rentang tanggal input (bawaan hari ini), pencarian; baris diwarnai menurut arah; Detail membuka popup, dan dari popup transfer bisa diedit.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Daftar Transfer Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl tl-lebar tlt" id="tl-trf"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-trf'), HARI_INI = <?= json_encode($hari_ini) ?>;
  var URL = 'cs/daftar-transfer-top-level';
  var SUMBER = [['INPUT', 'Input'], ['UPLOAD', 'Upload Excel'], ['REQUEST', 'Request'], ['KOREKSI', 'Koreksi']];
  var NAMA_SUMBER = {}; $.each(SUMBER, function (i, s) { NAMA_SUMBER[s[0]] = s[1]; });
  var BLN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  var ABR = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var PRESET = [['hari', 'Hari ini'], ['kemarin', 'Kemarin'], ['7', '7 hari terakhir'], ['30', '30 hari terakhir'], ['bulan', 'Bulan ini'], ['lalu', 'Bulan lalu']];
  var IC_SALIN = '<svg class="tl-ic" style="width:15px;height:15px" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>';
  var IC_CEK = T.ic('check', 15);
  var IC_KAL = '<svg class="tl-ic" style="width:18px;height:18px" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/></svg>';
  var IC_PANAH = '<svg class="tl-ic" style="width:16px;height:16px" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

  var arah = '', sumber = [], q = '', dari = HARI_INI, sampai = HARI_INI, halaman = 1, ukuran = 50, timer = null, data = null, seq = 0;
  var pre = 'hari', picStep = 0, picBulan = tglObj(HARI_INI), picPre = 'hari';

  // ---------- tanggal ----------
  function tglObj(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
  function lbl(s) { var d = tglObj(s); return d.getDate() + ' ' + ABR[d.getMonth()] + ' ' + d.getFullYear(); }
  function rentang(k) {
    var t = tglObj(HARI_INI), y = t.getFullYear(), m = t.getMonth(), d = t.getDate(), a, b;
    if (k === 'hari') { a = b = t; } else if (k === 'kemarin') { a = b = new Date(y, m, d - 1); }
    else if (k === '7') { a = new Date(y, m, d - 6); b = t; } else if (k === '30') { a = new Date(y, m, d - 29); b = t; }
    else if (k === 'bulan') { a = new Date(y, m, 1); b = t; } else { a = new Date(y, m - 1, 1); b = new Date(y, m, 0); }
    return [iso(a), iso(b)];
  }

  $r.html(
    '<div class="tlt-tabs" id="tl-arah" role="tablist"></div>' +
    '<div class="tlt-fl"><div class="tlt-fld"><small>Sumber</small><div class="tlt-ms" id="tl-ms"></div></div>' +
    '<div class="tlt-fld"><small>Tanggal input</small><div class="tlt-dr" id="tl-dr"></div></div>' +
    '<div class="tlt-fld grow"><small>Pencarian</small><label class="tl-search">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari nomor, petugas, atau SKU" autocomplete="off"></label></div></div>' +
    '<section class="tl-panel" id="tl-tabel"></section><div class="tl-pager" id="tl-pager"></div>'
  );

  // ---------- tab arah ----------
  function tab() {
    var h = (data ? data.hitung.arah : { SEMUA: 0, MASUK: 0, KELUAR: 0 });
    var s = '';
    $.each([['', 'Semua', 'SEMUA'], ['MASUK', 'Masuk', 'MASUK'], ['KELUAR', 'Keluar', 'KELUAR']], function (i, t) {
      s += '<button type="button" role="tab" data-arah="' + t[0] + '" aria-pressed="' + (arah === t[0]) + '">' + t[1] + ' <b>' + (+h[t[2]]).toLocaleString('id-ID') + '</b></button>';
    });
    $('#tl-arah').html(s);
  }

  // ---------- filter sumber (banyak pilihan) ----------
  function msBuka() { return $('#tl-ms .tlt-pop').hasClass('on'); }
  function ms(buka) {
    var c = data ? data.hitung.sumber : {};
    var teks = !sumber.length ? 'Semua sumber' : (sumber.length === 1 ? NAMA_SUMBER[sumber[0]] : sumber.length + ' sumber dipilih');
    var h = '<button type="button" class="tlt-btn' + (sumber.length ? ' has' : '') + (buka ? ' on' : '') + '" data-ms-t aria-haspopup="listbox" aria-expanded="' + !!buka + '"><span>' + teks + '</span>' + IC_PANAH + '</button><div class="tlt-pop msp' + (buka ? ' on' : '') + '">';
    $.each(SUMBER, function (i, s) {
      h += '<label class="tlt-o"><input type="checkbox" data-ms-o="' + s[0] + '"' + ($.inArray(s[0], sumber) > -1 ? ' checked' : '') + '><span>' + s[1] + '</span><em>' + (+(c[s[0]] || 0)).toLocaleString('id-ID') + '</em></label>';
    });
    $('#tl-ms').html(h + '<div class="tlt-pf"><button type="button" class="r" data-ms-r>Reset</button><button type="button" class="d" data-ms-s>Selesai</button></div></div>');
  }

  // ---------- filter tanggal (rentang) ----------
  function drBuka() { return $('#tl-dr .tlt-pop').hasClass('on'); }
  function dr(buka) {
    var h = '<button type="button" class="tlt-btn tlt-dbtn' + (buka ? ' on' : '') + '" data-dr-t aria-haspopup="dialog" aria-expanded="' + !!buka + '">' + IC_KAL + '<span>' + (dari === sampai ? lbl(dari) : lbl(dari) + ' – ' + lbl(sampai)) + '</span></button>' +
      '<div class="tlt-pop drp' + (buka ? ' on' : '') + '"><div class="tlt-pre">';
    $.each(PRESET, function (i, p) { h += '<button type="button" data-dr-p="' + p[0] + '"' + (pre === p[0] ? ' class="on"' : '') + '>' + p[1] + '</button>'; });
    h += '</div><div class="tlt-cal"><div class="tlt-in"><button type="button" class="tlt-f' + (picStep === 0 ? ' on' : '') + '" data-dr-step="0"><small>Tanggal awal</small><b>' + lbl(dari) + '</b></button><span>–</span>' +
      '<button type="button" class="tlt-f' + (picStep === 1 ? ' on' : '') + '" data-dr-step="1"><small>Tanggal akhir</small><b>' + lbl(sampai) + '</b></button></div>' +
      '<div class="tlt-nav"><button type="button" data-dr-m="-1" aria-label="Bulan sebelumnya">‹</button><b>' + BLN[picBulan.getMonth()] + ' ' + picBulan.getFullYear() + '</b><button type="button" data-dr-m="1" aria-label="Bulan berikutnya">›</button></div><div class="tlt-g">';
    $.each(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], function (i, x) { h += '<div class="h">' + x + '</div>'; });
    var y = picBulan.getFullYear(), m = picBulan.getMonth(), off = (new Date(y, m, 1).getDay() + 6) % 7, n = new Date(y, m + 1, 0).getDate();
    for (var i = 0; i < off; i++) h += '<div class="k"></div>';
    for (var d = 1; d <= n; d++) {
      var k = iso(new Date(y, m, d)), c = [];
      if (k > HARI_INI) c.push('dis'); else { if (k === dari) c.push('s'); if (k === sampai) c.push('e'); if (k > dari && k < sampai) c.push('mid'); }
      h += '<button type="button" data-dr-d="' + k + '" class="' + c.join(' ') + '"' + (k > HARI_INI ? ' disabled' : '') + '>' + d + '</button>';
    }
    $('#tl-dr').html(h + '</div><div class="tlt-hint">Pilih tanggal awal, lalu tanggal akhir.</div></div></div>');
    if (buka) { // panel terlalu lebar untuk sisa layar di kanan tombol: tempel ke tepi kanan tombol
      var pop = $('#tl-dr .tlt-pop')[0], bt = $('#tl-dr .tlt-btn')[0].getBoundingClientRect();
      if (bt.left + pop.offsetWidth > window.innerWidth - 8 && bt.right - pop.offsetWidth > 8) $(pop).addClass('kanan');
      else if (bt.left + pop.offsetWidth > window.innerWidth - 8) $(pop).css({ left: Math.max(8 - bt.left, -bt.left + 8) + 'px' });
    }
  }

  // ---------- tabel ----------
  function dua(nama, tgl) {
    if (!nama && !tgl) return '<span class="muted">—</span>';
    return '<div class="tl-cell2"><span>' + T.esc(nama || '—') + '</span><small>' + (tgl ? T.tgl(tgl) : '') + '</small></div>';
  }
  function qtyTeks(t) {
    if (+t.jumlah_baris === 0) return '<span class="muted" data-tip="Nomor sudah dibuat saat pengajuan; qty diisi saat CS input qty dari slip.">menunggu qty</span>';
    return '<b>' + (t.arah === 'MASUK' ? '+' : '−') + Math.abs(+t.jumlah_qty).toLocaleString('id-ID') + '</b>';
  }
  function tabel() {
    var d = data;
    tab(); ms(msBuka());
    if (!d || !d.rows.length) {
      $('#tl-tabel').html(T.empty('list', (q || arah || sumber.length) ? 'Tidak ada transfer yang cocok' : 'Belum ada transfer pada rentang tanggal ini')); $('#tl-pager').empty(); return;
    }
    var h = '<div class="tl-tw"><table class="tl-tbl tlt-tbl"><thead><tr><th>No. transfer</th><th>Petugas list</th><th>Petugas input</th><th class="n">Qty</th><th class="act">Aksi</th></tr></thead><tbody>';
    $.each(d.rows, function (i, t) {
      h += '<tr class="go ' + (t.arah === 'MASUK' ? 'in' : 'out') + '" data-id="' + t.id + '"><td class="nowrap"><b class="mono">' + T.esc(t.nomor) + '</b>' +
        '<button type="button" class="tlt-cp" data-salin="' + T.esc(t.nomor) + '" data-tip="Salin nomor transfer" aria-label="Salin nomor ' + T.esc(t.nomor) + '">' + IC_SALIN + '</button>' +
        (+t.jumlah_edit > 0 ? '<span class="tlt-ed" data-tip="Transfer ini pernah diedit. Riwayat ada di Detail.">diedit</span>' : '') + '</td>' +
        '<td>' + dua(t.nama_tim, t.tgl_list) + '</td><td>' + dua(t.nama_input, t.waktu_input) + '</td><td class="n">' + qtyTeks(t) + '</td>' +
        '<td class="act"><button type="button" class="tl-btn sm" data-detail="' + t.id + '">Detail</button> <button type="button" class="tl-btn sm" data-edit="' + t.id + '"' + (+t.jumlah_baris === 0 ? ' disabled data-tip="Belum bisa diedit: qty belum diisi dari slip."' : '') + '>' + T.ic('edit', 14) + 'Edit</button></td></tr>';
    });
    $('#tl-tabel').html(h + '</tbody></table></div>');
    var dari1 = (d.halaman - 1) * d.ukuran + 1, sampai1 = dari1 + d.rows.length - 1, maks = Math.max(1, Math.ceil(d.total / d.ukuran));
    $('#tl-pager').html('<span class="muted">' + dari1 + '–' + sampai1 + ' dari ' + d.total + ' transfer</span><div class="tl-pg">' +
      '<select id="tl-ukuran" aria-label="Baris per halaman">' + $.map([25, 50, 100], function (n) { return '<option' + (n === d.ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') + '</select>' +
      '<button type="button" class="tl-btn sm" data-hal="' + (d.halaman - 1) + '"' + (d.halaman <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
      '<span class="muted">' + d.halaman + ' / ' + maks + '</span>' +
      '<button type="button" class="tl-btn sm" data-hal="' + (d.halaman + 1) + '"' + (d.halaman >= maks ? ' disabled' : '') + '>Berikutnya</button></div>');
  }

  function muat() {
    var n = ++seq;
    T.post(URL + '/data', { arah: arah, sumber: sumber, q: q, dari: dari, sampai: sampai, halaman: halaman, ukuran: ukuran }, function (d) {
      if (n !== seq) return;
      data = d; halaman = d.halaman; ukuran = d.ukuran; tabel();
    }, T.galat);
  }

  // ---------- salin nomor ----------
  function salin(teks, $b) {
    function ok() { $b.html(IC_CEK).addClass('ok'); T.toast('Nomor ' + teks + ' disalin'); setTimeout(function () { $b.html(IC_SALIN).removeClass('ok'); }, 1400); }
    function cadangan() {
      var a = document.createElement('textarea'); a.value = teks; a.style.position = 'fixed'; a.style.opacity = 0; document.body.appendChild(a); a.select();
      var berhasil = false; try { berhasil = document.execCommand('copy'); } catch (e) { berhasil = false; }
      document.body.removeChild(a);
      if (berhasil) ok(); else T.toast('Tidak bisa menyalin otomatis. Salin manual: ' + teks, true);
    }
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(teks).then(ok, cadangan); else cadangan();
  }

  // ---------- popup detail dan edit ----------
  var cur = null, ed = null;
  function bukaTransfer(id, modeEdit) {
    T.post(URL + '/detail', { id: id }, function (r) {
      cur = r.d;
      if (modeEdit && !cur.bisa_edit) { T.toast('Transfer ini belum bisa diedit: qty belum diisi dari slip.', true); return; }
      var $m = T.modal({ judul: modeEdit ? 'Edit transfer' : 'Detail transfer', wide: true, body: '' });
      $m.addClass('tlt-dlg');
      modeEdit ? tampilEdit($m) : tampilDetail($m);
    }, T.galat);
  }

  function kepala(t) {
    var masuk = t.arah === 'MASUK';
    return '<div class="tlt-dk"><b class="mono tlt-no">' + T.esc(t.nomor) + '</b>' + T.pill(masuk ? 'Masuk' : 'Keluar', masuk ? 'ok' : 'bad') +
      '<span class="muted">' + T.esc(NAMA_SUMBER[t.sumber] || t.sumber) + (t.ref ? ' · ' + T.esc(t.ref) : '') + '</span></div>';
  }

  function tampilDetail($m) {
    var t = cur, masuk = t.arah === 'MASUK', total = 0;
    $.each(t.baris, function (i, x) { total += +x.qty; });
    var b = '<div class="tl-db">' + kepala(t) +
      '<dl class="tl-kv"><dt>Petugas list</dt><dd>' + T.esc(t.nama_tim || '—') + (t.tgl_list ? ' · ' + T.tgl(t.tgl_list) : '') + '</dd>' +
      '<dt>Petugas input</dt><dd>' + T.esc(t.nama_input || '—') + (t.waktu_input ? ' · ' + T.tgl(t.waktu_input) : '') + '</dd>' +
      '<dt>Dibuat</dt><dd class="mono">' + T.tgl(t.dibuat_at) + (t.dibuat_by ? ' · ' + T.esc(t.dibuat_by) : '') + '</dd>' +
      '<dt>Total</dt><dd><b>' + (masuk ? '+' : '−') + total.toLocaleString('id-ID') + ' pcs</b> · ' + t.baris.length + ' baris</dd></dl>' +
      '<div class="tl-tw tl-panel" style="max-height:320px"><table class="tl-tbl"><thead><tr><th>SKU</th><th>No rak</th><th class="n">Qty</th><th class="n">Stok sebelum</th><th class="n">Stok sesudah</th></tr></thead><tbody>' +
      (t.baris.length ? '' : '<tr><td colspan="5" class="muted">' + (t.nama_input ? 'Qty 0: TL ini sudah direstok atas inisiatif tim Restok sebelum TL diterbitkan, jadi tidak ada barang diambil dari top level.' : 'Menunggu CS input qty dari slip.') + '</td></tr>') +
      $.map(t.baris, function (x) { return '<tr><td class="mono">' + T.esc(x.sku) + '</td><td class="mono">' + T.esc(x.rak) + '</td><td class="n"><b>' + (masuk ? '+' : '−') + x.qty + '</b></td><td class="n">' + x.sebelum + '</td><td class="n">' + x.sesudah + '</td></tr>'; }).join('') +
      '</tbody></table></div>' + riwayat(t) + '</div>' +
      '<div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tutup</button>' +
      (t.bisa_edit ? '<button type="button" class="tl-btn pri" data-ke-edit>' + T.ic('edit', 14) + 'Edit transfer</button>' : '') + '</div>';
    $m.off('.tltedit');
    $m.find('h2').text('Detail transfer');
    $m.find('.tl-body').html(b);
    $m.off('click.tltdet').on('click.tltdet', '[data-ke-edit]', function () { tampilEdit($m); });
  }

  function riwayat(t) {
    var h = '<div class="tlt-rw"><b>Riwayat perubahan</b>';
    if (!t.riwayat.length) return h + '<div class="muted">Belum pernah diedit.</div></div>';
    h += '<ul>';
    $.each(t.riwayat, function (i, e) {
      h += '<li><div><b>' + T.tgl(e.waktu) + '</b> · ' + T.esc(e.oleh || '—') + ' · “' + T.esc(e.alasan) + '”</div><ul>' + $.map(e.perubahan, function (p) { return '<li>' + T.esc(p) + '</li>'; }).join('') + '</ul></li>';
    });
    return h + '</ul></div>';
  }

  function tampilEdit($m) {
    var t = cur;
    ed = { tim: t.nama_tim || '', input: t.nama_input || '', alasan: '', baris: $.map(t.baris, function (x) { return { id: x.id, sku: x.sku, rak: x.rak, qty: x.qty, hapus: false }; }), baru: 0 };
    var b = '<div class="tl-db">' + kepala(t) +
      '<div class="tlt-2"><label class="tl-fld">Petugas list<input type="text" id="tle-tim" value="' + T.esc(ed.tim) + '" maxlength="150" autocomplete="off"></label>' +
      '<label class="tl-fld">Petugas input<input type="text" id="tle-input" value="' + T.esc(ed.input) + '" maxlength="150" autocomplete="off"></label></div>' +
      (t.edit_baris ? '' : '<div class="tlt-note">' + T.ic('info', 15) + '<span>Baris transfer Request tidak diedit di sini, karena hasil request ikut berubah. Petugas tetap bisa diedit. Untuk mengoreksi qty, pakai menu Stok Top Level.</span></div>') +
      '<div class="tl-tw tl-panel tlt-ov"><table class="tl-tbl tlt-etbl"><thead><tr><th>SKU</th><th>No rak</th><th class="n">Qty</th><th class="n">Stok sebelum → sesudah</th><th></th></tr></thead><tbody id="tle-baris"></tbody></table></div>' +
      (t.edit_baris ? '<div><button type="button" class="tl-btn sm ghost" data-tle-tambah>' + T.ic('plus', 14) + 'Tambah baris</button></div>' : '') +
      '<div class="tlt-dampak" id="tle-dampak"></div>' +
      '<label class="tl-fld"><span>Alasan perubahan <span class="tlt-wajib">wajib diisi</span></span><textarea id="tle-alasan" rows="2" maxlength="255" placeholder="Contoh: salah ketik qty dari slip"></textarea></label></div>' +
      '<div class="tl-df"><span class="muted" id="tle-sum"></span><button type="button" class="tl-btn" data-tle-batal>Batal</button><button type="button" class="tl-btn pri" data-tle-simpan disabled>Simpan perubahan</button></div>';
    $m.find('h2').text('Edit transfer');
    $m.find('.tl-body').html(b);
    gambarBaris($m);
    cek($m);
    T.combo($m.find('#tle-tim'), cariTim, function (it) { ed.tim = it.nilai; cek($m); });
    T.combo($m.find('#tle-input'), cariTim, function (it) { ed.input = it.nilai; cek($m); });
    $m.off('.tltedit');
    $m.on('input.tltedit', '#tle-tim', function () { ed.tim = this.value; cek($m); });
    $m.on('input.tltedit', '#tle-input', function () { ed.input = this.value; cek($m); });
    $m.on('input.tltedit', '#tle-alasan', function () { ed.alasan = this.value; cek($m); });
    $m.on('input.tltedit', '.tle-sku', function () { ed.baris[idx(this)].sku = this.value; cek($m); });
    $m.on('input.tltedit', '.tle-rak', function () { ed.baris[idx(this)].rak = this.value; cek($m); });
    $m.on('input.tltedit', '.tle-qty', function () { ed.baris[idx(this)].qty = this.value; cek($m); });
    $m.on('click.tltedit', '[data-tle-hapus]', function () {
      var i = idx(this);
      if (aktif().length <= 1) { T.toast('Transfer minimal punya satu baris.', true); return; }
      if (ed.baris[i].id) { ed.baris[i].hapus = true; } else { ed.baris.splice(i, 1); }
      gambarBaris($m); cek($m);
    });
    $m.on('click.tltedit', '[data-tle-tambah]', function () { ed.baris.push({ id: '', sku: '', rak: '', qty: 1, hapus: false }); gambarBaris($m); cek($m); $m.find('#tle-baris .tle-sku').last().focus(); });
    $m.on('click.tltedit', '[data-tle-batal]', function () { tampilDetail($m); });
    $m.on('click.tltedit', '[data-tle-simpan]', function () { simpan($m, $(this)); });
  }
  function idx(el) { return +$(el).closest('tr').data('i'); }
  function aktif() { return $.grep(ed.baris, function (b) { return !b.hapus; }); }
  function cariTim(teks, kirim) {
    T.post(URL + '/tim', { q: teks }, function (res) {
      kirim($.map(res.rows, function (r) { return { nilai: r.nama, label: r.nama, sub: r.dari === 'terakhir' ? 'terakhir dipakai' : 'pegawai' }; }));
    }, function () { kirim([]); });
  }
  function gambarBaris($m) {
    var t = cur, h = '', bisa = t.edit_baris, tanda = t.arah === 'MASUK' ? '+' : '−';
    $.each(ed.baris, function (i, b) {
      if (b.hapus) return;
      var lama = null; $.each(t.baris, function (j, x) { if (x.id === b.id) lama = x; });
      h += '<tr data-i="' + i + '"><td>' + (bisa ? '<input type="text" class="tle-sku mono" value="' + T.esc(b.sku) + '" placeholder="SKU" autocomplete="off">' : '<span class="mono">' + T.esc(b.sku) + '</span>') + '</td>' +
        '<td>' + (bisa ? '<input type="text" class="tle-rak mono" value="' + T.esc(b.rak) + '" placeholder="No rak" autocomplete="off">' : '<span class="mono">' + T.esc(b.rak) + '</span>') + '</td>' +
        '<td class="n">' + (bisa ? '<input type="number" min="1" step="1" class="tle-qty" value="' + T.esc(b.qty) + '">' : '<b>' + tanda + T.esc(b.qty) + '</b>') + '</td>' +
        '<td class="n muted tle-ak">' + (lama ? lama.sebelum + ' → <b>' + lama.sesudah + '</b>' : 'baris baru') + '</td>' +
        '<td class="act">' + (bisa ? '<button type="button" class="tl-btn sm ghost" data-tle-hapus aria-label="Hapus baris" data-tip="Hapus baris dari transfer ini">' + T.ic('x', 14) + '</button>' : '') + '</td></tr>';
    });
    $m.find('#tle-baris').html(h);
    $m.find('.tle-sku').each(function () {
      var $in = $(this);
      T.combo($in, function (teks, kirim) {
        T.post(URL + '/sku', { q: teks }, function (res) { kirim($.map(res.rows, function (r) { return { nilai: r.sku, label: r.sku }; })); }, function () { kirim([]); });
      }, function (it) { ed.baris[idx($in)].sku = it.nilai; cek($m); });
    });
  }

  /** Perubahan dibandingkan dengan data asli: daftar uraian. Dipakai untuk ringkasan dan untuk mengaktifkan tombol Simpan. */
  function selisih() {
    var t = cur, d = [], tanda = t.arah === 'MASUK' ? '+' : '−';
    if ($.trim(ed.tim) !== (t.nama_tim || '')) d.push('Petugas list: ' + (t.nama_tim || '—') + ' → ' + $.trim(ed.tim));
    if ($.trim(ed.input) !== (t.nama_input || '')) d.push('Petugas input: ' + (t.nama_input || '—') + ' → ' + $.trim(ed.input));
    if (!t.edit_baris) return d;
    $.each(ed.baris, function (i, b) {
      var lama = null; $.each(t.baris, function (j, x) { if (x.id === b.id) lama = x; });
      if (!lama) { if (!b.hapus) d.push('Baris baru: ' + ($.trim(b.sku) || '?') + ' @ ' + ($.trim(b.rak) || '?') + ' (' + tanda + (b.qty || 0) + ')'); return; }
      if (b.hapus) { d.push('Hapus ' + lama.sku + ' @ ' + lama.rak + ' (' + tanda + lama.qty + ')'); return; }
      var u = [];
      if ($.trim(b.sku).toUpperCase() !== lama.sku.toUpperCase()) u.push('SKU ' + lama.sku + ' → ' + $.trim(b.sku));
      if ($.trim(b.rak).toUpperCase() !== lama.rak.toUpperCase()) u.push('rak ' + lama.rak + ' → ' + $.trim(b.rak).toUpperCase());
      if (+b.qty !== +lama.qty) u.push('qty ' + lama.qty + ' → ' + b.qty);
      if (u.length) d.push(lama.sku + ' @ ' + lama.rak + ': ' + u.join(', '));
    });
    return d;
  }
  function barisValid() {
    var ok = true, seen = {};
    $.each(aktif(), function (i, b) {
      var qty = +b.qty, k = $.trim(b.sku).toUpperCase() + '|' + $.trim(b.rak).toUpperCase();
      if (!cur.edit_baris) return;
      if (!$.trim(b.sku) || !$.trim(b.rak) || !(qty >= 1) || qty !== Math.floor(qty) || seen[k]) ok = false;
      seen[k] = true;
    });
    return ok;
  }
  function cek($m) {
    var d = selisih(), valid = barisValid(), alasanOk = $.trim(ed.alasan).length >= 5;
    $m.find('#tle-dampak').html(d.length ? '<b>Dampak perubahan</b><ul>' + $.map(d, function (x) { return '<li>' + T.esc(x) + '</li>'; }).join('') + '</ul>' + (cur.edit_baris ? '<small>Stok top level digeser sesuai selisih. Ditolak bila stok sebuah rak jadi minus.</small>' : '') : '<span class="muted">Belum ada perubahan.</span>');
    $m.find('#tle-sum').text(d.length + ' perubahan' + (valid ? '' : ' · ada baris yang belum valid'));
    $m.find('[data-tle-simpan]').prop('disabled', !(d.length && valid && alasanOk));
  }
  function simpan($m, $b) {
    $b.prop('disabled', true);
    var rows = $.map(aktif(), function (b) { return { id: b.id, sku: $.trim(b.sku), rak: $.trim(b.rak), qty: b.qty }; });
    T.post(URL + '/edit', { id: cur.id, nama_tim: $.trim(ed.tim), nama_input: $.trim(ed.input), baris: rows, alasan: $.trim(ed.alasan) }, function (res, j) {
      T.toast(j.message || 'Transfer diperbarui.'); T.closeModal(); muat();
    }, function (pesan) { T.galat(pesan); cek($m); });
  }

  // ---------- event ----------
  function tutupPop() { if (msBuka()) ms(false); if (drBuka()) dr(false); }
  $(document).off('.tltrf');
  $(document).on('click.tltrf', function (e) {
    if (!$('#tl-trf').length) { $(document).off('.tltrf'); return; }
    if (!$(e.target).closest('#tl-ms, #tl-dr').length) tutupPop();
  });
  $(document).on('keydown.tltrf', function (e) { if (e.key === 'Escape' && !T.adaModal()) tutupPop(); });

  $r.on('click', '[data-arah]', function () { arah = String($(this).data('arah')); halaman = 1; muat(); });
  $r.on('click', '[data-salin]', function (e) { e.stopPropagation(); salin($(this).attr('data-salin'), $(this)); });
  $r.on('click', '[data-detail]', function (e) { e.stopPropagation(); bukaTransfer($(this).data('detail'), false); });
  $r.on('click', '[data-edit]', function (e) { e.stopPropagation(); bukaTransfer($(this).data('edit'), true); });
  $r.on('click', 'tr.go', function (e) { if ($(e.target).closest('button, a, input').length) return; bukaTransfer($(this).data('id'), false); });
  $r.on('input', '#tl-q', function () { var v = this.value; clearTimeout(timer); timer = setTimeout(function () { q = $.trim(v); halaman = 1; muat(); }, 300); });
  $r.on('click', '[data-hal]', function () { halaman = +$(this).data('hal'); muat(); });
  $r.on('change', '#tl-ukuran', function () { ukuran = +this.value; halaman = 1; muat(); });

  // sumber
  $r.on('click', '[data-ms-t]', function (e) { e.stopPropagation(); var b = !msBuka(); tutupPop(); ms(b); });
  $r.on('change', '[data-ms-o]', function () {
    var k = $(this).data('ms-o'), i = $.inArray(k, sumber);
    if (i > -1) sumber.splice(i, 1); else sumber.push(k);
    halaman = 1; ms(true); muat();
  });
  $r.on('click', '[data-ms-r]', function () { sumber = []; halaman = 1; ms(true); muat(); });
  $r.on('click', '[data-ms-s]', function () { ms(false); });

  // tanggal
  $r.on('click', '[data-dr-t]', function (e) { e.stopPropagation(); var b = !drBuka(); tutupPop(); if (b) picBulan = tglObj(sampai); dr(b); });
  $r.on('click', '[data-dr-p]', function () { pre = String($(this).data('dr-p')); var r = rentang(pre); dari = r[0]; sampai = r[1]; picBulan = tglObj(sampai); picStep = 0; halaman = 1; dr(false); muat(); });
  $r.on('click', '[data-dr-m]', function () { picBulan = new Date(picBulan.getFullYear(), picBulan.getMonth() + (+$(this).data('dr-m')), 1); dr(true); });
  $r.on('click', '[data-dr-step]', function () { picStep = +$(this).data('dr-step'); dr(true); });
  $r.on('click', '[data-dr-d]', function () {
    var k = String($(this).data('dr-d')); pre = '';
    if (picStep === 0) { dari = k; sampai = k; picStep = 1; dr(true); return; }
    if (k < dari) { sampai = dari; dari = k; } else { sampai = k; }
    picStep = 0; halaman = 1; dr(false); muat();
  });

  tab(); ms(false); dr(false);
  muat();
})();
</script>
