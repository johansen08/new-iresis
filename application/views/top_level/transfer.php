<?php
// View menu TIM CS -> Daftar Transfer Top Level. Dimuat lewat AJAX (SPA).
// Tabel semua nomor transfer TTL-TAHUN-NNNNN (masuk dan keluar), terbaru di atas, dengan filter dan detail per baris.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Daftar Transfer Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl tl-lebar" id="tl-trf"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-trf'), arah = '', q = '', dari = '', sampai = '', halaman = 1, ukuran = 50, timer = null, data = null;
  var SUMBER = { INPUT: 'Input', UPLOAD: 'Upload Excel', REQUEST: 'Request', KOREKSI: 'Koreksi' };

  $r.html(
    '<div class="tl-ph"><div class="t"><h1>Daftar Transfer Top Level</h1>' +
    T.info('Semua perpindahan stok top level, masing-masing dengan nomor TTL-TAHUN-NNNNN.\nMasuk: Input, Upload Excel, dan koreksi naik.\nKeluar: request yang selesai dan koreksi turun.\nKlik Detail untuk rincian per SKU dan rak.') +
    '</div><div class="muted" id="tl-ring"></div></div>' +
    '<div class="tl-tb"><div class="tl-seg" role="group" id="tl-arah"></div><div class="tl-tools">' +
    '<label class="tl-tgl"><span>Dari</span><input type="date" id="tl-dari"></label><label class="tl-tgl"><span>Sampai</span><input type="date" id="tl-sampai"></label>' +
    '<label class="tl-search">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari nomor, petugas, atau SKU" autocomplete="off"></label></div></div>' +
    '<section class="tl-panel" id="tl-tabel"></section><div class="tl-pager" id="tl-pager"></div>'
  );

  function segmen() {
    var h = '';
    $.each([['', 'Semua'], ['MASUK', 'Masuk'], ['KELUAR', 'Keluar']], function (i, t) {
      h += '<button type="button" data-arah="' + t[0] + '" aria-pressed="' + (arah === t[0]) + '">' + t[1] + '</button>';
    });
    $('#tl-arah').html(h);
  }

  function dua(nama, tgl) {
    if (!nama && !tgl) return '<span class="muted">—</span>';
    return '<div class="tl-cell2"><span>' + T.esc(nama || '—') + '</span><small>' + (tgl ? T.tgl(tgl) : '') + '</small></div>';
  }

  function tabel() {
    var d = data;
    $('#tl-ring').html(d ? 'Masuk <b>' + d.ringkas.masuk + '</b> pcs · Keluar <b>' + d.ringkas.keluar + '</b> pcs' : '');
    if (!d || !d.rows.length) { $('#tl-tabel').html(T.empty('list', (q || arah || dari || sampai) ? 'Tidak ada transfer yang cocok' : 'Belum ada transfer')); $('#tl-pager').empty(); return; }
    var h = '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No. transfer</th><th>Arah</th><th>Sumber</th><th>Petugas list</th><th>Petugas input</th><th class="n">Baris</th><th class="n">Qty</th><th>Dibuat</th><th></th></tr></thead><tbody>';
    $.each(d.rows, function (i, t) {
      h += '<tr class="go" data-id="' + t.id + '"><td class="nowrap"><b class="mono">' + T.esc(t.nomor) + '</b></td><td>' + T.pill(t.arah === 'MASUK' ? 'Masuk' : 'Keluar', t.arah === 'MASUK' ? 'ok' : 'warn') + '</td>' +
        '<td><div class="tl-cell2"><span>' + T.esc(SUMBER[t.sumber] || t.sumber) + '</span><small class="mono">' + T.esc(t.ref || '') + '</small></div></td>' +
        '<td>' + dua(t.nama_tim, t.tgl_list) + '</td><td>' + dua(t.nama_input, t.tgl_input) + '</td>' +
        '<td class="n">' + t.jumlah_baris + '</td><td class="n">' + (+t.jumlah_baris === 0 && !t.nama_input ? '<span class="muted" data-tip="Nomor sudah dibuat saat pengajuan; qty diisi saat CS input qty dari slip.">menunggu qty</span>' : (+t.jumlah_qty === 0 ? '0' : (t.arah === 'MASUK' ? '+' : '−') + Math.abs(t.jumlah_qty))) + '</td><td class="mono nowrap">' + T.tgl(t.dibuat_at) + '</td>' +
        '<td class="act"><button type="button" class="tl-btn sm" data-detail="' + t.id + '">Detail</button></td></tr>';
    });
    $('#tl-tabel').html(h + '</tbody></table></div>');
    var dari1 = (d.halaman - 1) * d.ukuran + 1, sampai1 = dari1 + d.rows.length - 1, maks = Math.ceil(d.total / d.ukuran);
    $('#tl-pager').html('<span class="muted">' + dari1 + '–' + sampai1 + ' dari ' + d.total + ' transfer</span><div class="tl-pg">' +
      '<select id="tl-ukuran" aria-label="Baris per halaman">' + $.map([25, 50, 100], function (n) { return '<option' + (n === d.ukuran ? ' selected' : '') + '>' + n + '</option>'; }).join('') + '</select>' +
      '<button type="button" class="tl-btn sm" data-hal="' + (d.halaman - 1) + '"' + (d.halaman <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
      '<span class="muted">' + d.halaman + ' / ' + maks + '</span>' +
      '<button type="button" class="tl-btn sm" data-hal="' + (d.halaman + 1) + '"' + (d.halaman >= maks ? ' disabled' : '') + '>Berikutnya</button></div>');
  }

  function muat() {
    T.post('cs/daftar-transfer-top-level/data', { arah: arah, q: q, dari: dari, sampai: sampai, halaman: halaman, ukuran: ukuran }, function (d) {
      data = d; halaman = d.halaman; ukuran = d.ukuran; tabel();
    }, T.galat);
  }

  function detail(id) {
    T.post('cs/daftar-transfer-top-level/detail', { id: id }, function (r) {
      var t = r.d, masuk = t.arah === 'MASUK';
      var b = '<div class="tl-db"><div style="display:flex;gap:10px;align-items:center"><b class="mono" style="font-size:18px">' + T.esc(t.nomor) + '</b>' + T.pill(masuk ? 'Masuk' : 'Keluar', masuk ? 'ok' : 'warn') + '</div>' +
        '<dl class="tl-kv"><dt>Sumber</dt><dd>' + T.esc(SUMBER[t.sumber] || t.sumber) + (t.ref ? ' · <span class="mono">' + T.esc(t.ref) + '</span>' : '') + '</dd>' +
        (t.nama_tim ? '<dt>Petugas list</dt><dd>' + T.esc(t.nama_tim) + (t.tgl_list ? ' · ' + T.tgl(t.tgl_list) : '') + '</dd>' : '') +
        (t.nama_input ? '<dt>Petugas input</dt><dd>' + T.esc(t.nama_input) + (t.tgl_input ? ' · ' + T.tgl(t.tgl_input) : '') + '</dd>' : '') +
        '<dt>Dibuat</dt><dd class="mono">' + T.tgl(t.dibuat_at) + (t.dibuat_by ? ' · ' + T.esc(t.dibuat_by) : '') + '</dd></dl>' +
        '<div class="tl-tw tl-panel" style="max-height:300px"><table class="tl-tbl"><thead><tr><th>SKU</th><th>No rak</th><th class="n">Qty</th><th class="n">Sebelum</th><th class="n">Sesudah</th></tr></thead><tbody>' +
        (t.baris.length ? '' : '<tr><td colspan="5" class="muted">' + (t.nama_input ? 'Qty 0: tidak ada barang diambil dari top level.' : 'Menunggu CS input qty dari slip.') + '</td></tr>') +
        $.map(t.baris, function (x) { return '<tr><td class="mono">' + T.esc(x.sku) + '</td><td class="mono">' + T.esc(x.rak) + '</td><td class="n"><b>' + (masuk ? '+' : '−') + Math.abs(x.qty) + '</b></td><td class="n">' + x.sebelum + '</td><td class="n">' + x.sesudah + '</td></tr>'; }).join('') +
        '</tbody></table></div></div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tutup</button></div>';
      T.modal({ judul: 'Detail transfer', wide: true, body: b });
    }, T.galat);
  }

  $r.on('click', '[data-arah]', function () { arah = $(this).data('arah'); halaman = 1; segmen(); muat(); });
  $r.on('change', '#tl-dari', function () { dari = this.value; halaman = 1; muat(); });
  $r.on('change', '#tl-sampai', function () { sampai = this.value; halaman = 1; muat(); });
  $r.on('input', '#tl-q', function () { var v = this.value; clearTimeout(timer); timer = setTimeout(function () { q = $.trim(v); halaman = 1; muat(); }, 300); });
  $r.on('click', '[data-hal]', function () { halaman = +$(this).data('hal'); muat(); });
  $r.on('change', '#tl-ukuran', function () { ukuran = +this.value; halaman = 1; muat(); });
  $r.on('click', 'tr.go', function (e) { if ($(e.target).closest('button').length) return; detail($(this).data('id')); });
  $r.on('click', '[data-detail]', function () { detail($(this).data('detail')); });

  segmen();
  muat();
})();
</script>
