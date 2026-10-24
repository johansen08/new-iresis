<?php
// View menu TIM CS -> Request Top Level. Dimuat lewat AJAX (SPA).
// Alur: Perlu dicetak -> (cetak slip 100x150) -> Sedang direstok -> (input qty dari slip) -> Selesai.
// Tampilan "ruang kerja": antrian di kiri, satu request dikerjakan di kanan (cetak dan input qty tanpa popup).
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Request Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-req"></div>
<script>
(function () {
  var AKUN = <?= json_encode((string) (isset($akun) ? $akun : ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var T = window.TL, $r = $('#tl-req'), filter = 'cetak', q = '', rows = [], hitung = {}, timerCari = null;
  var sel = 0, det = null, baru = 0, selesaiId = 0, offset = 0, draf = {};
  var CS = {
    DIAJUKAN: ['Perlu dicetak', 'warn'], DITUGASKAN: ['Sedang direstok', 'info'], SELESAI: ['Selesai', 'ok'], BATAL: ['Dibatalkan', '']
  };
  var TABS = [['cetak', 'Perlu dicetak', 'DIAJUKAN'], ['restok', 'Sedang direstok', 'DITUGASKAN'], ['selesai', 'Selesai', 'SELESAI']];
  var INFO_HALAMAN = 'Request restok display dari picker.\nAlur: cetak slip → slip kembali dari Restok → input qty → selesai.\nSetelah selesai, resi diserahkan fisik ke picker.\nPilih request di kiri, lalu kerjakan di kanan. Tombol ↑ ↓ memindah request.\nLencana merah = menunggu lebih dari 20 menit.';

  $r.html(
    '<div class="tlc-top">' + T.info(INFO_HALAMAN) + '</div>' +
    '<div class="tlr-grid"><section class="tl-panel tlr-left"><div class="tlr-lh">' +
    '<label class="tl-search">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari SKU, resi, atau nomor TTL" autocomplete="off"></label>' +
    '<div class="tlr-tabs" id="tl-tabs"></div></div><div class="tlr-list" id="tl-list"></div></section>' +
    '<section class="tl-panel tlr-ws" id="tl-ws"></section></div>'
  );

  // ---------- waktu ----------
  function ms(s) { var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]).getTime() : 0; }
  function jam(s) { return s ? String(s).substr(11, 8) : '—'; }
  function sekarang() { return Date.now() + offset; }
  function menit(s) { return Math.max(0, Math.round((sekarang() - ms(s)) / 60000)); }
  function umur(s) { var m = menit(s); return m < 1 ? '< 1 mnt' : m < 60 ? m + ' mnt' : Math.floor(m / 60) + ' j ' + (m % 60) + ' mnt'; }
  function kelasUmur(s) { var m = menit(s); return m > 20 ? 'l' : m > 10 ? 'm' : ''; }
  function nomor(x) { return x.nomor || x.kode; }

  // ---------- antrian (kiri) ----------
  function tabs() {
    var h = '';
    $.each(TABS, function (i, t) {
      h += '<button type="button" class="tlr-s-' + t[2] + '" data-filter="' + t[0] + '" aria-pressed="' + (filter === t[0]) + '">' + t[1] + '<span class="c">' + (hitung[t[0]] || 0) + '</span></button>';
    });
    $('#tl-tabs').html(h);
  }

  function daftar() {
    if (!rows.length) { $('#tl-list').html('<div class="tlr-none">' + (q ? 'Tidak ada hasil' : 'Tidak ada request di sini') + '</div>'); return; }
    var h = '';
    $.each(rows, function (i, r) {
      var umr;
      if (r.status === 'SELESAI') umr = '<span class="tlr-age">' + jam(r.selesai_at) + '</span>';
      else if (r.status === 'BATAL') umr = '<span class="tlr-age">Batal</span>';
      else { var t = r.status === 'DIAJUKAN' ? r.request_at : r.cetak_at; umr = '<span class="tlr-age ' + kelasUmur(t) + '">' + umur(t) + '</span>'; }
      h += '<div class="tlr-it tlr-s-' + r.status + (+r.id === sel ? ' on' : '') + (+r.id === baru ? ' baru' : '') + '" data-id="' + r.id + '"><span class="sku mono">' + T.esc(r.sku) + '</span>' + umr +
        '<span class="no mono">' + T.esc(nomor(r)) + '</span><span></span><span class="rs mono">' + (r.resi ? T.esc(r.resi) : '<span class="muted">tanpa resi</span>') + '</span></div>';
    });
    $('#tl-list').html(h);
  }

  function muat(diam) {
    T.post('cs/request-top-level/data', { filter: filter, q: q }, function (d) {
      rows = d.rows || []; hitung = d.hitung || {};
      // Tab kerja: yang paling lama menunggu di atas (id kecil lebih dulu). Tab Selesai tetap yang terbaru di atas (urutan server).
      if (filter !== 'selesai') rows.sort(function (a, b) { return a.id - b.id; });
      if (d.sekarang) offset = ms(d.sekarang) - Date.now();
      tabs(); pastikan();
    }, function (m) { if (!diam) T.galat(m); });
  }

  /** Setelah daftar berubah: pilih request yang valid, dan muat detailnya bila belum atau statusnya sudah berbeda. */
  function pastikan() {
    if (!rows.length && selesaiId) { filter = 'selesai'; sel = selesaiId; selesaiId = 0; det = null; muat(); return; }
    selesaiId = 0;
    var ada = null;
    $.each(rows, function (i, r) { if (+r.id === sel) ada = r; });
    if (!ada) {
      sel = rows.length ? +rows[0].id : 0; det = null;
      daftar();
      if (sel) muatDetail(sel); else ws();
      return;
    }
    daftar();
    if (!det || +det.id !== sel) { muatDetail(sel); return; }
    if (det.status !== ada.status && !$('#tl-fq :focus').length) muatDetail(sel, true);
  }

  function muatDetail(id, senyap) {
    T.post('cs/request-top-level/detail', { id: id }, function (data) {
      if (+id !== sel) return; // pilihan sudah pindah
      det = data.d; daftar(); ws();
    }, function (m) { if (!senyap) T.galat(m); });
  }

  // ---------- ruang kerja (kanan) ----------
  function stepper(d) {
    var n = d.status === 'DIAJUKAN' ? 0 : d.status === 'DITUGASKAN' ? 1 : 2;
    if (d.status === 'BATAL') n = 0;
    var ts = [d.request_at, d.cetak_at, d.selesai_at], nm = ['Diajukan picker', 'Slip dicetak' + (d.cetak_ke > 1 ? ' (' + d.cetak_ke + '×)' : ''), 'Qty diinput'], h = '';
    for (var k = 0; k < 3; k++) {
      h += '<div class="tlr-st k' + (k + 1) + (k <= n ? ' f' : '') + '"><i></i><b>' + nm[k] + '</b><span>' + (k <= n ? jam(ts[k]) : '—') + '</span></div>';
    }
    return '<div class="tlr-stp">' + h + '</div>';
  }

  function resiBox(d) {
    return '<div class="tlr-box"><h3>Resi ' + T.info('Setelah request selesai, serahkan resi ini ke picker secara fisik.') + '</h3>' +
      (d.resi.length ? $.map(d.resi, function (x) { return '<span class="tl-rk">' + T.esc(x) + '</span>'; }).join(' ') : '<span class="muted">Tanpa resi</span>') + '</div>';
  }

  function rincianBox(d) {
    var rk = d.rak.length ? $.map(d.rak, function (x) { return '<span class="tl-rk">' + T.esc(x.rak) + ' · ' + x.stok + '</span>'; }).join(' ') : '<span class="muted">Stok sudah habis</span>';
    var h = '<div class="tlr-box"><h3>Rincian ' + T.info('Rak top level = stok sistem per rak saat slip dicetak.') + '</h3><dl class="tl-kv"><dt>Rak display</dt><dd class="mono">' + T.esc(d.rak_display) + '</dd>' +
      '<dt>Rak top level</dt><dd>' + rk + '</dd>';
    if (d.status === 'SELESAI') {
      h += '<dt>Petugas list</dt><dd>' + (d.petugas_list ? T.esc(d.petugas_list) : '—') + '</dd><dt>Petugas input</dt><dd>' + (d.petugas_input ? T.esc(d.petugas_input) : '—') + '</dd>';
    } else if (d.status === 'DIAJUKAN' || d.status === 'DITUGASKAN') {
      h += '<dt>Menunggu</dt><dd><b>' + umur(d.status === 'DIAJUKAN' ? d.request_at : d.cetak_at) + '</b></dd>';
    }
    return h + '</dl></div>';
  }

  function ws() {
    var d = det;
    if (!d) { $('#tl-ws').html('<div class="tlr-empty"><b>' + (rows.length ? 'Memuat…' : 'Tidak ada request') + '</b>Rincian, slip, dan input qty muncul di sini.</div>'); return; }
    var s = CS[d.status] || ['', ''];
    var h = '<div class="tlr-s-' + d.status + '"><div class="tlr-wh"><div><div class="sku mono">' + T.esc(d.sku) + '</div><div class="no mono">' + T.esc(nomor(d)) + ' · ' + (d.asal === 'PICKER' ? 'Picker' : 'CS') + '</div></div>' +
      '<span class="tlr-chip">' + s[0] + '</span></div>' + stepper(d) + '<div class="tlr-body">';
    if (d.status === 'DIAJUKAN') {
      h += '<div class="tlr-two">' + rincianBox(d) + resiBox(d) + '</div>' +
        '<div class="tlr-box"><h3>Slip 100 × 150 mm ' + T.info('Jam cetak tercatat otomatis sampai detik dan tampil di slip.') + '</h3><div class="tl-paper">' + slipHtml(d, null) + '</div></div>' +
        '<div class="tlr-cta"><button type="button" class="tl-btn ghost dng" data-batal="' + d.id + '">Batalkan request</button>' +
        '<button type="button" class="tl-btn pri lg" data-cetak="' + d.id + '">' + T.ic('print', 15) + 'Cetak slip</button></div>';
    } else if (d.status === 'DITUGASKAN') {
      h += '<div class="tlr-two">' + rincianBox(d) + resiBox(d) + '</div>' + formQty(d) +
        '<div class="tlr-cta"><button type="button" class="tl-btn" data-cetak="' + d.id + '">' + T.ic('print', 15) + 'Cetak ulang slip</button>' +
        '<button type="button" class="tl-btn pri lg" id="tl-simpan">Simpan &amp; selesai</button></div>';
    } else if (d.status === 'SELESAI') {
      var hasil = {}, tot = 0;
      $.each(d.hasil || [], function (i, x) { hasil[x.rak] = x.amb; tot += +x.amb; });
      var rk = '<table class="tl-tbl"><thead><tr><th>No rak</th><th class="n">Stok</th><th class="n">Diambil</th></tr></thead><tbody>' +
        $.map(d.rak, function (x) { return '<tr><td class="mono">' + T.esc(x.rak) + '</td><td class="n">' + x.stok + '</td><td class="n">' + (hasil[x.rak] !== undefined ? hasil[x.rak] : '—') + '</td></tr>'; }).join('') + '</tbody></table>';
      h += '<div class="tlr-hand"><b>Serahkan resi ke picker:</b>' + (d.resi.length ? $.map(d.resi, function (x) { return '<span class="tl-rk">' + T.esc(x) + '</span>'; }).join(' ') : '<span>tanpa resi</span>') + '</div>' +
        '<div class="tlr-two">' + rincianBox(d) + '<div class="tlr-box"><h3>Hasil</h3><div class="tl-note ' + (tot > 0 ? 'ok' : '') + '">' +
        (tot > 0 ? 'Diambil <b>' + tot + '</b> pcs' : 'Qty 0: TL sudah direstok tim Restok sebelum diterbitkan, stok tidak berkurang') + '</div><div class="tl-panel tl-tw" style="margin-top:10px">' + rk + '</div></div></div>';
    } else {
      h += '<div class="tl-note warn">Request ini dibatalkan' + (d.batal_at ? ' pada <b class="mono">' + T.tgl(d.batal_at) + '</b>' : '') + '.</div><div class="tlr-two">' + rincianBox(d) + resiBox(d) + '</div>';
    }
    $('#tl-ws').html(h + '</div></div>');
    if (d.status === 'DITUGASKAN') ikatQty(d);
  }

  // ---------- slip 100 x 150 mm ----------
  function slipHtml(d, tCetak) {
    var h = '<div class="slip"><div class="slip-h"><b>SLIP RESTOK DISPLAY</b><span>' + T.esc(nomor(d)) + '</span></div>' +
      '<div class="slip-sku">' + T.esc(d.sku) + '</div>' +
      '<div class="slip-kv"><span>Ke display</span><b>' + T.esc(d.rak_display) + '</b><span>Request</span><b>' + T.tgl(d.request_at) + '</b><span>Dicetak</span><b>' + (tCetak ? T.tgl(tCetak) : 'otomatis saat cetak') + '</b><span>Resi</span><b>' + (d.resi.length ? T.esc(d.resi.join(', ')) : '—') + '</b></div>' +
      '<table class="slip-t"><thead><tr><th>No rak top level</th><th>Stok TL</th><th>Qty diambil</th></tr></thead><tbody>';
    $.each(d.rak, function (i, x) { h += '<tr><td>' + T.esc(x.rak) + '</td><td>' + x.stok + '</td><td></td></tr>'; });
    return h + '</tbody></table><p class="slip-f">Tulis qty yang diambil per rak.</p><div class="slip-sg"><div>Tanda tangan CS</div><div>Tanda tangan Restok</div></div></div>';
  }
  var CSS_CETAK = '@page{size:100mm 150mm;margin:0}html,body{margin:0;padding:0}' +
    '.slip{font-family:Arial,Helvetica,sans-serif;width:100mm;height:150mm;box-sizing:border-box;padding:4mm;display:flex;flex-direction:column;gap:2mm;font-size:9pt;line-height:1.25;color:#000;overflow:hidden}' +
    '.slip-h{display:flex;justify-content:space-between;border-bottom:.6mm solid #000;padding-bottom:1.2mm;font-size:10pt}' +
    '.slip-sku{font-size:26pt;font-weight:700;line-height:1.1;text-align:center;padding:2.5mm 0;border:.6mm solid #000;word-break:break-all}' +
    '.slip-kv{display:grid;grid-template-columns:auto 1fr;gap:.4mm 3mm}' +
    '.slip-t{width:100%;border-collapse:collapse}.slip-t th,.slip-t td{border:.3mm solid #000;padding:1mm 1.2mm;text-align:left;font-size:8.5pt}.slip-t td{height:7mm;font-size:9.5pt;font-weight:500}.slip-t th{font-weight:700}' +
    '.slip-f{font-size:7.5pt;margin:0}.slip-sg{margin-top:auto;display:grid;grid-template-columns:1fr 1fr;gap:1.5mm}.slip-sg div{border:.3mm solid #000;height:17mm;padding:.8mm 1.2mm;font-size:7.5pt}';
  function cetakKeKertas(d) {
    var f = document.createElement('iframe');
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
    document.body.appendChild(f);
    var doc = f.contentWindow.document;
    doc.open();
    doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Slip ' + T.esc(nomor(d)) + '</title><style>' + CSS_CETAK + '</style></head><body>' + slipHtml(d, d.cetak_at) + '</body></html>');
    doc.close();
    setTimeout(function () {
      try { f.contentWindow.focus(); f.contentWindow.print(); } catch (e) { T.galat('Dialog cetak tidak bisa dibuka.'); }
      setTimeout(function () { if (f.parentNode) f.parentNode.removeChild(f); }, 3000);
    }, 250);
  }

  function cetak(id, tombol) {
    var pertama = det && +det.id === +id && det.status === 'DIAJUKAN';
    $(tombol).prop('disabled', true);
    T.post('cs/request-top-level/cetak', { id: id }, function (r) {
      cetakKeKertas(r.d);
      if (pertama) {
        T.toast('Slip dicetak. Serahkan ke Restok.');
        filter = 'restok'; sel = +id; det = r.d; baru = +id; muat(true);
      } else {
        T.toast('Slip dicetak ulang.');
        if (det && +det.id === +id) det.cetak_ke = r.d.cetak_ke;
        $(tombol).prop('disabled', false);
      }
    }, function (m) { T.galat(m); muat(true); if (det && +det.id === +id) muatDetail(id, true); });
  }

  // ---------- input qty dari slip (di ruang kerja) ----------
  function formQty(d) {
    var b = '<div class="tlr-box" id="tl-fq"><h3>Input qty dari slip ' + T.info('Salin angka yang ditulis Restok di slip. Slip sudah ditandatangani Restok dan CS.\nSisa fisik = hitungan fisik rak setelah barang diambil; kalau berbeda dengan stok sistem dikurangi qty diambil, selisih masuk log dan stok mengikuti fisik.') + '</h3>' +
      '<div class="tl-fld" style="margin-bottom:10px"><label for="tl-plist" class="tl-lbl">Petugas List ' + T.info('Nama yang mendata dan menulis qty di slip (Restok).\nPetugas input dan tanggal list serta input dicatat otomatis oleh sistem.') + '</label>' +
      '<input type="text" id="tl-plist" placeholder="Ketik nama, atau pilih dari saran" autocomplete="off" style="width:100%"></div>' +
      '<div class="tl-note" style="margin-bottom:10px">Petugas input: <b>' + T.esc(AKUN) + '</b> · Tanggal list dan input: <b>hari ini</b>, otomatis.</div>' +
      '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No rak</th><th class="n">Stok sistem</th><th class="n">Qty diambil</th><th class="n">Sisa fisik</th></tr></thead><tbody>';
    $.each(d.rak, function (i, x) {
      b += '<tr data-rak="' + T.esc(x.rak) + '" data-sys="' + x.stok + '"><td class="mono">' + T.esc(x.rak) + '</td><td class="n">' + x.stok + '</td>' +
        '<td class="n"><input type="number" min="0" class="amb" placeholder="0"></td><td class="n"><input type="number" min="0" class="sisa" value="' + x.stok + '"></td></tr>';
    });
    return b + '</tbody></table></div><div id="tl-qty-sum" style="margin-top:10px"></div></div>';
  }

  function ikatQty(d) {
    var $f = $('#tl-fq'), $pl = $('#tl-plist'), dr = draf[d.id] || (draf[d.id] = { pl: window.__tlListReq || '' });
    $pl.val(dr.pl || '');
    $pl.on('input', function () { dr.pl = this.value; });
    T.combo($pl, function (kata, kirim) {
      T.post('cs/request-top-level/tim', { q: kata }, function (r) {
        kirim($.map(r.rows || [], function (x) { return { nilai: x.nama, label: x.nama, grup: x.dari === 'terakhir' ? 'Terakhir dipakai' : 'Pegawai' }; }));
      }, function () { kirim([]); });
    }, function (it) { dr.pl = it.nilai; });
    function hitungan() {
      var tot = 0, sel = 0;
      $f.find('tbody tr').each(function () {
        var $t = $(this), sys = +$t.data('sys'), amb = parseInt($t.find('.amb').val(), 10) || 0, sisa = $t.find('.sisa').val();
        tot += amb;
        if (sisa !== '' && parseInt(sisa, 10) !== sys - amb) sel++;
      });
      var nolDicentang = $('#tl-qnol').prop('checked');
      $('#tl-qty-sum').html('<div class="tl-note ' + (tot > 0 ? 'ok' : '') + '">Total diambil <b>' + tot + '</b> pcs</div>' +
        (tot > 0 ? '' : '<div class="tl-note warn" style="margin-top:6px"><label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer"><input type="checkbox" id="tl-qnol"' + (nolDicentang ? ' checked' : '') + ' style="margin-top:3px"><span>Qty diambil 0: TL ini sudah direstok atas inisiatif tim Restok sebelum TL diterbitkan, jadi tidak ada barang diambil dari top level. Request selesai tanpa mengurangi stok.</span></label></div>') +
        (sel ? '<div class="tl-note warn" style="margin-top:6px">' + sel + ' rak berselisih dengan sistem. Stok akan mengikuti fisik.</div>' : ''));
    }
    $f.on('input', '.amb', function () {
      var $t = $(this).closest('tr'), sys = +$t.data('sys'), amb = parseInt(this.value, 10) || 0;
      if (!$t.data('sentuh')) $t.find('.sisa').val(sys - amb);
      hitungan();
    });
    $f.on('input', '.sisa', function () { $(this).closest('tr').data('sentuh', true); hitungan(); });
    $f.on('change', '#tl-qnol', hitungan);
    $('#tl-simpan').on('click', function () {
      var data = [], tot = 0, ok = true, $b = $(this);
      $f.find('tbody tr').each(function () {
        var $t = $(this), amb = $t.find('.amb').val(), sisa = $t.find('.sisa').val();
        if (amb === '') amb = '0';
        if (sisa === '' || +amb > +$t.data('sys') || +amb < 0 || +sisa < 0) { ok = false; }
        tot += parseInt(amb, 10) || 0;
        data.push({ rak: $t.data('rak'), amb: amb, sisa: sisa });
      });
      var pl = $.trim($pl.val());
      if (!pl) { T.galat('Isi nama petugas list.'); $pl.focus(); return; }
      if (!ok) { T.galat('Qty diambil tidak boleh melebihi stok sistem, dan sisa fisik wajib diisi.'); return; }
      if (tot <= 0 && !$('#tl-qnol').prop('checked')) { T.galat('Isi qty yang diambil, atau centang konfirmasi qty 0.'); return; }
      $b.prop('disabled', true);
      T.post('cs/request-top-level/selesai', { id: d.id, petugas_list: pl, qty_nol: tot <= 0 ? 1 : 0, rows: data }, function (r, j) {
        window.__tlListReq = pl; delete draf[d.id];
        T.toast(j.message + ' Serahkan resi ke picker.');
        // lanjut ke request Sedang direstok berikutnya; kalau tidak ada, tampilkan hasil request ini di tab Selesai
        filter = 'restok'; sel = 0; det = null; baru = +d.id; selesaiId = +d.id; muat(true);
      }, function (m) { T.galat(m); $b.prop('disabled', false); });
    });
    hitungan();
  }

  // ---------- batal (popup konfirmasi) ----------
  function dialogBatal(id) {
    var d = det;
    if (!d || +d.id !== +id) return;
    var $m = T.modal({
      judul: 'Batalkan request?',
      body: '<div class="tl-db"><div class="tlc-kv"><span>Nomor TTL</span><b>' + T.esc(nomor(d)) + '</b><span>SKU</span><b>' + T.esc(d.sku) + '</b><span>Resi</span><b>' + (d.resi.length ? T.esc(d.resi.join(', ')) : '—') + '</b></div>' +
        '<div class="tlc-wr">Request dibatalkan dan nomor TTL-nya ikut dibatalkan. Hanya bisa sebelum slip dicetak.</div></div>' +
        '<div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tidak</button><button type="button" class="tl-btn pri bad" data-yabatal>Ya, Batalkan</button></div>'
    });
    $m.find('[data-tl-tutup]').last().focus();
    $m.on('click', '[data-yabatal]', function () {
      $(this).prop('disabled', true);
      T.post('cs/request-top-level/batal', { id: id }, function (r, j) {
        T.closeModal(); T.toast(j.message); sel = 0; det = null; muat(true);
      }, function (m) { T.closeModal(); T.galat(m); muat(true); });
    });
  }

  // ---------- event ----------
  $r.on('click', '[data-filter]', function () { filter = $(this).data('filter'); sel = 0; det = null; muat(); });
  $r.on('input', '#tl-q', function () { var v = this.value; clearTimeout(timerCari); timerCari = setTimeout(function () { q = $.trim(v); sel = 0; det = null; muat(); }, 300); });
  $r.on('click', '.tlr-it', function () {
    var id = +$(this).data('id');
    if (id === sel) return;
    sel = id; det = null; daftar(); ws(); muatDetail(id);
  });
  $r.on('click', '[data-cetak]', function () { cetak($(this).data('cetak'), this); });
  $r.on('click', '[data-batal]', function () { dialogBatal($(this).data('batal')); });
  $(document).off('keydown.tlreq').on('keydown.tlreq', function (e) {
    if (!$.contains(document, $r[0])) { $(document).off('keydown.tlreq'); return; }
    if (T.adaModal() || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp')) return;
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'select' || tag === 'textarea') return;
    var i = -1;
    $.each(rows, function (k, r) { if (+r.id === sel) i = k; });
    i = Math.max(0, Math.min(rows.length - 1, i + (e.key === 'ArrowDown' ? 1 : -1)));
    if (rows[i] && +rows[i].id !== sel) { e.preventDefault(); sel = +rows[i].id; det = null; daftar(); ws(); muatDetail(sel); }
  });

  clearInterval(window.__tlReqTimer);
  window.__tlReqTimer = setInterval(function () {
    if (!$.contains(document, $r[0])) { clearInterval(window.__tlReqTimer); return; }
    if (!T.adaModal()) muat(true);
  }, 20000);

  tabs();
  ws();
  muat();
})();
</script>
