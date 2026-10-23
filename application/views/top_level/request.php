<?php
// View menu TIM CS -> Request Top Level. Dimuat lewat AJAX (SPA).
// Alur: Perlu dicetak -> (cetak slip 100x150) -> Di Restok -> (input qty dari slip) -> Selesai.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Request Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-req"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-req'), filter = 'aktif', q = '', rows = [], hitung = {}, timerCari = null, drawerId = 0;
  var CS = {
    DIAJUKAN: ['Perlu dicetak', 'warn'], DITUGASKAN: ['Di Restok', 'info'], SELESAI: ['Selesai', 'ok'], BATAL: ['Dibatalkan', '']
  };
  var TABS = [['aktif', 'Aktif'], ['cetak', 'Perlu dicetak'], ['restok', 'Di Restok'], ['selesai', 'Selesai']];

  $r.html(
    '<div class="tl-ph"><div class="t"><h1>Request Top Level</h1>' +
    T.info('Request restok display dari picker.\nKlik baris untuk melihat rak top level, resi, dan waktu.\nAlur: cetak slip → slip kembali dari Restok → input qty → selesai.\nSetelah itu resi diserahkan fisik ke picker.') +
    '</div></div><div class="tl-tb"><div id="tl-tabs"></div>' +
    '<label class="tl-search">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari SKU, resi, atau ID" autocomplete="off"></label></div>' +
    '<section class="tl-panel" id="tl-tabel"></section><div id="tl-drawer"></div>'
  );

  function tabs() {
    var h = '<div class="tl-seg" role="group">';
    $.each(TABS, function (i, t) {
      h += '<button type="button" data-filter="' + t[0] + '" aria-pressed="' + (filter === t[0]) + '">' + t[1] + '<span class="c">' + (hitung[t[0]] || 0) + '</span></button>';
    });
    $('#tl-tabs').html(h + '</div>');
  }

  function aksi(r) {
    if (r.status === 'DIAJUKAN') return '<button type="button" class="tl-btn sm pri" data-cetak="' + r.id + '">' + T.ic('print', 14) + 'Cetak slip</button>';
    if (r.status === 'DITUGASKAN') return '<button type="button" class="tl-btn sm pri" data-qty="' + r.id + '">Input qty</button>';
    return '';
  }

  function tabel() {
    if (!rows.length) { $('#tl-tabel').html(T.empty('inbox', q ? 'Tidak ada hasil' : 'Belum ada request')); return; }
    var h = '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>ID</th><th>SKU</th><th>Resi</th><th>Request</th><th>Dicetak</th><th>Status</th><th></th></tr></thead><tbody>';
    $.each(rows, function (i, r) {
      var s = CS[r.status] || ['', ''];
      h += '<tr class="go" data-detail="' + r.id + '"><td class="mono">' + T.esc(r.kode) + '</td><td><b class="mono">' + T.esc(r.sku) + '</b></td><td class="mono">' +
        (r.resi ? T.esc(r.resi) : '<span class="muted">tanpa resi</span>') + '</td><td class="mono nowrap">' + T.tgl(r.request_at) + '</td><td class="mono nowrap">' +
        (r.cetak_at ? T.tgl(r.cetak_at) : '<span class="muted">—</span>') + '</td><td>' + T.pill(s[0], s[1]) + '</td><td class="act">' + aksi(r) + '</td></tr>';
    });
    $('#tl-tabel').html(h + '</tbody></table></div>');
  }

  function muat(diam) {
    T.post('cs/request-top-level/data', { filter: filter, q: q }, function (d) {
      rows = d.rows; hitung = d.hitung; tabs(); tabel();
      if (drawerId) drawer(drawerId, true);
    }, function (m) { if (!diam) T.galat(m); });
  }

  // ---------- panel detail ----------
  function drawer(id, senyap) {
    T.post('cs/request-top-level/detail', { id: id }, function (data) {
      var d = data.d, s = CS[d.status] || ['', ''];
      drawerId = id;
      var tl = '<li><span>Request</span><span class="t">' + T.tgl(d.request_at) + '</span></li>' +
        (d.cetak_at ? '<li><span>Slip dicetak' + (d.cetak_ke > 1 ? ' (' + d.cetak_ke + '×)' : '') + '</span><span class="t">' + T.tgl(d.cetak_at) + '</span></li>' : '') +
        (d.selesai_at ? '<li><span>Selesai (qty diinput)</span><span class="t">' + T.tgl(d.selesai_at) + '</span></li>' : '') +
        (d.batal_at ? '<li><span>Dibatalkan</span><span class="t">' + T.tgl(d.batal_at) + '</span></li>' : '');
      var hasil = {};
      $.each(d.hasil || [], function (i, h) { hasil[h.rak] = h.amb; });
      var rak = '<div class="tl-panel tl-tw"><table class="tl-tbl"><thead><tr><th>No rak</th><th class="n">Stok</th>' + (d.hasil ? '<th class="n">Diambil</th>' : '') + '</tr></thead><tbody>';
      $.each(d.rak, function (i, x) { rak += '<tr><td class="mono">' + T.esc(x.rak) + '</td><td class="n">' + x.stok + '</td>' + (d.hasil ? '<td class="n">' + (hasil[x.rak] !== undefined ? hasil[x.rak] : '—') + '</td>' : '') + '</tr>'; });
      rak += '</tbody></table></div>';
      var foot = '';
      if (d.status === 'DIAJUKAN') foot += '<button type="button" class="tl-btn dng" data-batal="' + d.id + '">Batalkan</button>';
      if (d.status === 'DITUGASKAN') foot += '<button type="button" class="tl-btn" data-cetak="' + d.id + '">Cetak ulang</button>';
      foot += aksi({ id: d.id, status: d.status });
      $('#tl-drawer').html(
        '<div class="tl tl-drawer"><div class="tl-dh"><div style="display:flex;gap:10px;align-items:center"><h2 class="mono">' + T.esc(d.kode) + '</h2>' + T.pill(s[0], s[1]) +
        '</div><button type="button" class="tl-btn ghost sm" data-tutup-drawer aria-label="Tutup">' + T.ic('x') + '</button></div><div class="tl-dr-b">' +
        '<dl class="tl-kv"><dt>SKU</dt><dd class="mono"><b>' + T.esc(d.sku) + '</b></dd><dt>Rak display</dt><dd class="mono">' + T.esc(d.rak_display) + '</dd><dt>Asal</dt><dd>' + (d.asal === 'PICKER' ? 'Picker' : 'CS') + '</dd></dl>' +
        '<div class="tl-sec"><h3>Rak top level ' + T.info('Stok sistem per rak saat slip dicetak.') + '</h3>' + (d.rak.length ? rak : '<span class="muted">Stok sudah habis</span>') + '</div>' +
        '<div class="tl-sec"><h3>Resi ' + T.info('Setelah request selesai, serahkan resi ini ke picker secara fisik.') + '</h3>' +
        (d.resi.length ? '<div class="tl-rks">' + $.map(d.resi, function (x) { return '<span class="tl-rk">' + T.esc(x) + '</span>'; }).join('') + '</div>' : '<span class="muted">Tanpa resi</span>') + '</div>' +
        '<div class="tl-sec"><h3>Waktu</h3><ul class="tl-tl">' + tl + '</ul></div></div>' +
        (foot ? '<div class="tl-df">' + foot + '</div>' : '') + '</div>'
      );
    }, function (m) { if (!senyap) T.galat(m); drawerId = 0; $('#tl-drawer').empty(); });
  }
  function tutupDrawer() { drawerId = 0; $('#tl-drawer').empty(); }

  // ---------- slip 100 x 150 mm ----------
  function slipHtml(d, tCetak) {
    var h = '<div class="slip"><div class="slip-h"><b>SLIP RESTOK DISPLAY</b><span>' + T.esc(d.kode) + '</span></div>' +
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
    doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Slip ' + T.esc(d.kode) + '</title><style>' + CSS_CETAK + '</style></head><body>' + slipHtml(d, d.cetak_at) + '</body></html>');
    doc.close();
    setTimeout(function () {
      try { f.contentWindow.focus(); f.contentWindow.print(); } catch (e) { T.galat('Dialog cetak tidak bisa dibuka.'); }
      setTimeout(function () { if (f.parentNode) f.parentNode.removeChild(f); }, 3000);
    }, 250);
  }

  function dialogCetak(id) {
    T.post('cs/request-top-level/detail', { id: id }, function (data) {
      var d = data.d;
      if (d.status !== 'DIAJUKAN' && d.status !== 'DITUGASKAN') { T.galat('Request sudah tidak aktif.'); muat(true); return; }
      var tampil = d.cetak_at;
      var $m = T.modal({
        judul: 'Cetak slip · ' + d.kode, wide: true,
        body: '<div class="tl-cols"><div class="tl-paper">' + slipHtml(d, tampil) + '</div><div class="tl-setp">' +
          '<div class="tl-fld">Printer<select disabled><option>Printer termal</option></select></div>' +
          '<div class="tl-fld">Ukuran kertas<input type="text" value="100 × 150 mm" disabled></div>' +
          '<div class="muted" style="display:flex;gap:6px;align-items:center;font-size:12.5px">Jam cetak tercatat otomatis ' +
          T.info('Tanggal dan jam request serta cetak dicatat sampai detik dan tampil di slip.') + '</div></div></div>' +
          '<div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-cetak-ok>' + T.ic('print', 15) + (d.cetak_at ? 'Cetak ulang' : 'Cetak') + '</button></div>'
      });
      $m.on('click', '[data-cetak-ok]', function () {
        $(this).prop('disabled', true);
        T.post('cs/request-top-level/cetak', { id: id }, function (r) {
          T.closeModal();
          cetakKeKertas(r.d);
          T.toast('Slip dicetak. Serahkan ke Restok.');
          muat(true);
        }, function (m) { T.closeModal(); T.galat(m); muat(true); });
      });
    }, T.galat);
  }

  // ---------- input qty dari slip ----------
  function dialogQty(id) {
    T.post('cs/request-top-level/detail', { id: id }, function (data) {
      var d = data.d;
      if (d.status !== 'DITUGASKAN') { T.galat('Request belum dicetak atau sudah selesai.'); muat(true); return; }
      var b = '<div class="tl-db"><div class="mono" style="font-size:18px;font-weight:600">' + T.esc(d.sku) + '</div><div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No rak</th><th class="n">Stok sistem</th><th class="n">Qty diambil ' +
        T.info('Salin angka yang ditulis Restok di slip. Slip sudah ditandatangani Restok dan CS.') + '</th><th class="n">Sisa fisik ' +
        T.info('Hitungan fisik rak setelah barang diambil. Kalau berbeda dengan stok sistem dikurangi qty diambil, selisih masuk log dan stok mengikuti fisik.') + '</th></tr></thead><tbody>';
      $.each(d.rak, function (i, x) {
        b += '<tr data-rak="' + T.esc(x.rak) + '" data-sys="' + x.stok + '"><td class="mono">' + T.esc(x.rak) + '</td><td class="n">' + x.stok + '</td>' +
          '<td class="n"><input type="number" min="0" class="amb" placeholder="0"></td><td class="n"><input type="number" min="0" class="sisa" value="' + x.stok + '"></td></tr>';
      });
      b += '</tbody></table></div><div id="tl-qty-sum"></div></div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-simpan>Simpan &amp; selesai</button></div>';
      var $m = T.modal({ judul: 'Input qty dari slip · ' + d.kode, wide: true, body: b });
      function hitungan() {
        var tot = 0, sel = 0;
        $m.find('tbody tr').each(function () {
          var $t = $(this), sys = +$t.data('sys'), amb = parseInt($t.find('.amb').val(), 10) || 0, sisa = $t.find('.sisa').val();
          tot += amb;
          if (sisa !== '' && parseInt(sisa, 10) !== sys - amb) sel++;
        });
        $('#tl-qty-sum').html('<div class="tl-note ' + (tot > 0 ? 'ok' : '') + '">Total diambil <b>' + tot + '</b> pcs</div>' + (sel ? '<div class="tl-note warn" style="margin-top:6px">' + sel + ' rak berselisih dengan sistem. Stok akan mengikuti fisik.</div>' : ''));
      }
      $m.on('input', '.amb', function () {
        var $t = $(this).closest('tr'), sys = +$t.data('sys'), amb = parseInt(this.value, 10) || 0;
        if (!$t.data('sentuh')) $t.find('.sisa').val(sys - amb);
        hitungan();
      });
      $m.on('input', '.sisa', function () { $(this).closest('tr').data('sentuh', true); hitungan(); });
      $m.on('click', '[data-simpan]', function () {
        var data = [], tot = 0, ok = true;
        $m.find('tbody tr').each(function () {
          var $t = $(this), amb = $t.find('.amb').val(), sisa = $t.find('.sisa').val();
          if (amb === '') amb = '0';
          if (sisa === '' || +amb > +$t.data('sys') || +amb < 0 || +sisa < 0) { ok = false; }
          tot += parseInt(amb, 10) || 0;
          data.push({ rak: $t.data('rak'), amb: amb, sisa: sisa });
        });
        if (!ok) { T.galat('Qty diambil tidak boleh melebihi stok sistem, dan sisa fisik wajib diisi.'); return; }
        if (tot <= 0) { T.galat('Isi qty yang diambil.'); return; }
        $(this).prop('disabled', true);
        T.post('cs/request-top-level/selesai', { id: id, rows: data }, function (r, j) {
          T.closeModal(); T.toast('Selesai. Stok diperbarui; serahkan resi ke picker.'); muat(true);
        }, function (m) { T.galat(m); $m.find('[data-simpan]').prop('disabled', false); });
      });
      hitungan();
    }, T.galat);
  }

  // ---------- event ----------
  $r.on('click', '[data-filter]', function () { filter = $(this).data('filter'); muat(); });
  $r.on('input', '#tl-q', function () { var v = this.value; clearTimeout(timerCari); timerCari = setTimeout(function () { q = $.trim(v); muat(); }, 300); });
  $r.on('click', '[data-detail]', function (e) {
    if ($(e.target).closest('button').length) return;
    drawer($(this).data('detail'));
  });
  $r.on('click', '[data-tutup-drawer]', tutupDrawer);
  $r.on('click', '[data-cetak]', function (e) { e.stopPropagation(); dialogCetak($(this).data('cetak')); });
  $r.on('click', '[data-qty]', function (e) { e.stopPropagation(); dialogQty($(this).data('qty')); });
  $r.on('click', '[data-batal]', function () {
    var id = $(this).data('batal');
    T.post('cs/request-top-level/batal', { id: id }, function (d, j) { T.toast(j.message); muat(true); }, function (m) { T.galat(m); muat(true); });
  });
  $(document).off('keydown.tlreq').on('keydown.tlreq', function (e) {
    if (!$.contains(document, $r[0])) { $(document).off('keydown.tlreq'); return; }
    if (e.key === 'Escape' && !T.adaModal() && drawerId) tutupDrawer();
  });

  clearInterval(window.__tlReqTimer);
  window.__tlReqTimer = setInterval(function () {
    if (!$.contains(document, $r[0])) { clearInterval(window.__tlReqTimer); return; }
    if (!T.adaModal()) muat(true);
  }, 20000);

  tabs();
  muat();
})();
</script>
