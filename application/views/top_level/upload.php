<?php
// View menu TIM CS -> Upload Top Level. Dimuat lewat AJAX (SPA).
// Tiga langkah: pilih file -> periksa data (pratinjau, dry=1) -> selesai (impor, dry=0). Header di baris ke-3.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Upload Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-up"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-up');
  var HEAD = ['SKU', 'NO RAK', 'QTY', 'AC NO LIST', 'NAMA LIST', 'TGL LIST', 'AC NO INPUT', 'NAMA INPUT', 'TGL INPUT'];
  var FORMAT = 'Header di baris 3, data mulai baris 4.\n' + $.map(HEAD, function (h, i) { return String.fromCharCode(65 + i) + '  ' + h; }).join('\n') +
    '\nMaksimal <?= (int) $maks_baris ?> baris per file.';
  var file = null, prev = null, filter = 'semua', riwayat = [], selesai = null, galat = null, sibuk = false;

  function stepper(n) {
    var h = '<div class="tl-stp">', nama = ['Pilih file', 'Periksa data', 'Selesai'];
    $.each(nama, function (i, t) {
      h += '<span class="s' + (i + 1 < n ? ' done' : i + 1 === n ? ' on' : '') + '"><span class="d">' + (i + 1 < n ? T.ic('check', 13) : i + 1) + '</span>' + t + '</span>' + (i < 2 ? '<span class="ln"></span>' : '');
    });
    return h + '</div>';
  }

  function riwayatHtml() {
    var h = '<section class="tl-panel"><div class="tl-shead"><div class="l"><h2>Riwayat upload</h2></div></div>';
    if (!riwayat.length) return h + T.empty('file', 'Belum ada upload') + '</section>';
    h += '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>ID</th><th>Waktu</th><th>File</th><th class="n">Baris</th><th class="n">Qty</th><th>Dilist oleh</th></tr></thead><tbody>';
    $.each(riwayat, function (i, b) {
      h += '<tr><td class="mono">' + T.esc(b.kode) + '</td><td class="mono nowrap">' + T.tgl(b.import_at) + '</td><td>' + T.esc(b.nama_file) + '</td><td class="n">' + b.jumlah_baris + '</td><td class="n">' + b.jumlah_qty + '</td><td>' + T.esc(b.dilist_oleh || '-') + '</td></tr>';
    });
    return h + '</tbody></table></div></section>';
  }

  function judul() {
    return '<div class="tl-ph"><div class="t"><h1>Upload Top Level</h1>' +
      T.info('Unggah file stok top level dari tim Restok.\nSetiap baris menambah qty pada SKU dan rak tersebut.\nBaris yang sama persis dengan yang pernah diimpor otomatis dilewati.') + '</div></div>';
  }

  function paint() {
    var h = judul();
    if (selesai) {
      h += stepper(3) + '<section class="tl-panel"><div class="tl-res" style="padding:36px 16px"><div class="ring ok">' + T.ic('check', 26) + '</div><b>' + selesai.baris + ' baris diimpor</b>' +
        '<span class="muted">Top level bertambah ' + selesai.qty + ' pcs' + (selesai.lewat ? ' · ' + selesai.lewat + ' baris dilewati' : '') + '</span>' +
        '<div style="display:flex;gap:8px;margin-top:6px"><button type="button" class="tl-btn" data-ulang>Unggah file lain</button></div></div></section>' + riwayatHtml();
    } else if (galat) {
      h += stepper(1) + '<section class="tl-panel tl-pad" style="display:flex;flex-direction:column;gap:12px"><div style="display:flex;gap:8px;align-items:center">' + T.ic('file') + '<b>' + T.esc(galat.file) + '</b></div>' +
        '<div class="tl-note bad"><b>File ditolak</b><ul>' + $.map(galat.pesan, function (e) { return '<li>' + T.esc(e) + '</li>'; }).join('') + '</ul></div><div><button type="button" class="tl-btn" data-ulang>Pilih file lain</button></div></section>';
    } else if (prev) {
      var c = prev.hitung, rows = $.grep(prev.rows, function (r) { return filter === 'semua' || r.st === filter; });
      h += stepper(2) + '<section class="tl-panel"><div class="tl-shead"><div class="l">' + T.ic('file') + '<b>' + T.esc(prev.file) + '</b>' +
        T.info('Baris error tidak ikut diimpor. Perbaiki di Excel lalu unggah ulang; baris yang sudah berhasil akan dilewati.') + '</div><div class="tl-seg" role="group">' +
        $.map([['semua', 'Semua', prev.rows.length], ['ok', 'Siap', c.ok], ['err', 'Error', c.err], ['dup', 'Dilewati', c.dup]], function (t) {
          return '<button type="button" data-filter="' + t[0] + '" aria-pressed="' + (filter === t[0]) + '">' + t[1] + '<span class="c">' + t[2] + '</span></button>';
        }).join('') + '</div></div>';
      if (!rows.length) h += T.empty('check', 'Tidak ada baris');
      else {
        h += '<div class="tl-tw cap"><table class="tl-tbl"><thead><tr><th class="n">Baris</th><th>SKU</th><th>No rak</th><th class="n">Qty</th><th>Dilist</th><th>Diinput</th><th>Status</th></tr></thead><tbody>';
        $.each(rows, function (i, r) {
          h += '<tr><td class="n mono">' + r.baris + '</td><td class="mono">' + T.esc(r.sku) + '</td><td class="mono">' + T.esc(r.rak) + '</td><td class="n">' + T.esc(r.qty) + '</td>' +
            '<td><div class="tl-cell2"><span>' + T.esc(r.nama_list) + '</span><small>' + T.tgl(r.tgl_list) + '</small></div></td>' +
            '<td><div class="tl-cell2"><span>' + T.esc(r.nama_input) + '</span><small>' + T.tgl(r.tgl_input) + '</small></div></td><td>' +
            (r.st === 'ok' ? T.pill('Siap' + (r.baru ? ' · rak baru' : ''), 'ok') + (r.warn ? ' ' + T.info(r.warn) : '') : T.pill(r.err, r.st === 'err' ? 'bad' : 'warn')) + '</td></tr>';
        });
        h += '</tbody></table></div>';
      }
      h += '<div class="tl-sumbar"><span class="muted">' + c.ok + ' baris siap · ' + c.qty + ' pcs</span><div style="display:flex;gap:8px"><button type="button" class="tl-btn" data-ulang>Ganti file</button>' +
        '<button type="button" class="tl-btn pri" data-impor' + (c.ok && !sibuk ? '' : ' disabled') + '>Impor ' + c.ok + ' baris</button></div></div></section>';
    } else {
      h += stepper(1) + '<div class="tl-drop" id="tl-dz">' + T.ic('upload', 30) + '<b>Tarik file Excel ke sini</b><span class="muted">.xlsx, .xls, atau .csv</span>' +
        '<div style="display:flex;gap:8px;align-items:center"><label class="tl-btn pri" for="tl-berkas" style="margin:0">Pilih file</label><input type="file" id="tl-berkas" accept=".xlsx,.xls,.csv" hidden>' +
        '<span class="muted" style="display:inline-flex;align-items:center;gap:4px;font-size:12.5px">Format kolom ' + T.info(FORMAT) + '</span></div></div>' + riwayatHtml();
    }
    $r.html(h);
  }

  function baca(f) {
    if (!f || sibuk) return;
    sibuk = true; file = f; galat = null; selesai = null;
    T.toast('Memeriksa ' + f.name + '…');
    var fd = new FormData();
    fd.append('berkas', f);
    fd.append('dry', '1');
    T.postForm('cs/upload-top-level/proses', fd, function (d) {
      sibuk = false; prev = d; riwayat = d.riwayat || riwayat; filter = 'semua'; paint();
    }, function (m, data) {
      sibuk = false; prev = null;
      galat = { file: f.name, pesan: data && data.header_salah ? data.header_salah : [m] };
      paint();
    });
  }

  function impor() {
    if (!file || !prev || sibuk) return;
    sibuk = true; paint();
    var fd = new FormData();
    fd.append('berkas', file);
    fd.append('dry', '0');
    T.postForm('cs/upload-top-level/proses', fd, function (d) {
      sibuk = false; selesai = d.hasil; riwayat = d.riwayat || riwayat; prev = null; file = null; paint();
    }, function (m) { sibuk = false; T.galat(m); paint(); });
  }

  $r.on('change', '#tl-berkas', function () { baca(this.files && this.files[0]); });
  $r.on('click', '[data-filter]', function () { filter = $(this).data('filter'); paint(); });
  $r.on('click', '[data-ulang]', function () { prev = null; file = null; galat = null; selesai = null; paint(); });
  $r.on('click', '[data-impor]', impor);
  $r.on('dragover', '#tl-dz', function (e) { e.preventDefault(); $(this).addClass('over'); });
  $r.on('dragleave', '#tl-dz', function () { $(this).removeClass('over'); });
  $r.on('drop', '#tl-dz', function (e) {
    e.preventDefault(); $(this).removeClass('over');
    var dt = e.originalEvent.dataTransfer;
    if (dt && dt.files && dt.files[0]) baca(dt.files[0]);
  });

  paint();
  T.post('cs/upload-top-level/riwayat', {}, function (d) { riwayat = d.riwayat; if (!prev && !galat && !selesai) paint(); });
})();
</script>
