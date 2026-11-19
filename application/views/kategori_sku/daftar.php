<?php
// View menu TIM PURCHASING -> Kategori SKU (daftar baca-saja). Dimuat lewat AJAX (SPA); selector diawali #kd-root / .kd-mod.
// Gaya mengikuti menu Cabut SKU / CS Manajemen Komplain New.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Kategori SKU</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Menu ini hanya untuk webmaster dan tim purchasing.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<style>
  #kd-root, .kd-mod {
    --kd-primary:#4f46e5; --kd-primary-light:rgba(79,70,229,.1);
    --kd-bg:#f8fafc; --kd-line:#e2e8f0; --kd-text:#334155; --kd-muted:#64748b; --kd-ink:#0f172a;
    font-family:'Inter','Segoe UI',sans-serif; color:#1e293b;
  }
  #kd-root input, #kd-root select, #kd-root button { font-family:inherit; }
  #kd-root .kd-head { padding:4px 0 14px; }
  #kd-root .kd-title { margin:0; font-size:20px; font-weight:700; color:var(--kd-ink); }
  #kd-root .kd-sub { margin:2px 0 0; font-size:13px; color:var(--kd-muted); }

  #kd-root .kd-body { padding:16px 18px 12px; background:#fff; border:1px solid var(--kd-line); border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  #kd-root .kd-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:0 0 14px; }
  #kd-root .kd-search { position:relative; flex:1 1 240px; max-width:340px; }
  #kd-root .kd-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; }
  #kd-root .kd-search input { width:100%; height:38px; padding:0 12px 0 34px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; background:#fff; color:var(--kd-ink); box-sizing:border-box; }
  #kd-root .kd-search input:focus { outline:none; border-color:var(--kd-primary); box-shadow:0 0 0 3px var(--kd-primary-light); }
  #kd-root .kd-count { margin-left:auto; font-size:12.5px; color:var(--kd-muted); }
  #kd-root .kd-xls { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 14px; border:1px solid #86efac; border-radius:8px; background:#f0fdf4; color:#15803d; font-size:13.5px; font-weight:600; cursor:pointer; white-space:nowrap; }
  #kd-root .kd-xls:hover:not(:disabled) { background:#dcfce7; }
  #kd-root .kd-xls:disabled { opacity:.6; cursor:wait; }

  /* filter kategori multi-pilih */
  #kd-root .kd-ms { position:relative; }
  #kd-root .kd-chip { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:var(--kd-text); font-size:13.5px; font-weight:500; cursor:pointer; white-space:nowrap; }
  #kd-root .kd-chip:hover { background:var(--kd-bg); }
  #kd-root .kd-chip .fa-angle-down { color:#94a3b8; }
  #kd-root .kd-chip-count { display:none; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:var(--kd-primary); color:#fff; font-size:11px; font-weight:700; line-height:18px; text-align:center; }
  #kd-root .kd-ms.aktif .kd-chip { border-color:#a5b4fc; background:#eef2ff; color:#3730a3; }
  #kd-root .kd-ms.aktif .kd-chip-count { display:inline-block; }
  #kd-root .kd-ms-panel { display:none; position:absolute; top:calc(100% + 4px); left:0; z-index:50; min-width:240px; max-height:340px; overflow-y:auto; padding:6px; background:#fff; border:1px solid var(--kd-line); border-radius:10px; box-shadow:0 10px 15px -3px rgba(0,0,0,.12); }
  #kd-root .kd-ms.buka .kd-ms-panel { display:block; }
  #kd-root .kd-ms-opt { display:flex; align-items:center; gap:10px; margin:0; padding:8px 10px; border-radius:8px; font-size:13.5px; font-weight:500; color:var(--kd-text); cursor:pointer; }
  #kd-root .kd-ms-opt:hover { background:var(--kd-primary-light); }
  #kd-root .kd-ms-opt input { width:16px; height:16px; margin:0; accent-color:var(--kd-primary); flex:none; }
  #kd-root .kd-ms-opt .kd-n { margin-left:auto; padding-left:12px; font-size:12px; color:var(--kd-muted); font-variant-numeric:tabular-nums; }
  #kd-root .kd-ms-foot { position:sticky; bottom:-6px; margin-top:4px; padding:6px 4px 2px; border-top:1px solid var(--kd-line); text-align:right; background:#fff; }
  #kd-root .kd-ms-foot button { border:0; background:none; color:var(--kd-primary); font-size:12.5px; font-weight:600; cursor:pointer; padding:4px 6px; }

  /* tabel */
  #kd-root .kd-table-wrap { border:1px solid var(--kd-line); border-radius:10px; overflow-x:auto; background:#fff; }
  #kd-root .kd-table { width:100%; margin:0; border-collapse:separate; border-spacing:0; }
  #kd-root .kd-table > thead > tr > th { padding:10px 12px; background:var(--kd-bg); border-bottom:1px solid var(--kd-line); font-size:11.5px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--kd-muted); white-space:nowrap; text-align:left; }
  #kd-root .kd-table > tbody > tr > td { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:13.5px; color:var(--kd-text); vertical-align:middle; }
  #kd-root .kd-table > tbody > tr:nth-child(even) > td { background:#fafbfd; }
  #kd-root .kd-table > tbody > tr:hover > td { background:#f1f5ff; }
  #kd-root .kd-table > tbody > tr:last-child > td { border-bottom:0; }
  #kd-root .kd-sku { font-weight:600; color:var(--kd-ink); }
  #kd-root .kd-kosong { color:#94a3b8; }
  #kd-root .kd-empty { padding:36px 12px !important; text-align:center; color:var(--kd-muted); }
  #kd-root .kd-empty i { display:block; font-size:26px; margin-bottom:8px; color:#cbd5e1; }

  /* gambar + mata */
  #kd-root .kd-skucell { display:flex; align-items:center; gap:10px; }
  #kd-root .kd-thumb { position:relative; flex:none; width:48px; height:48px; border-radius:8px; border:1px solid var(--kd-line); background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:20px; }
  #kd-root .kd-thumb img { width:100%; height:100%; object-fit:cover; border-radius:7px; display:block; }
  #kd-root .kd-eye { position:absolute; right:-6px; bottom:-6px; width:22px; height:22px; padding:0; border:1px solid var(--kd-line); border-radius:50%; background:#fff; color:var(--kd-primary); font-size:11px; line-height:1; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 1px 3px rgba(15,23,42,.18); }
  #kd-root .kd-eye:hover { background:var(--kd-primary); color:#fff; border-color:var(--kd-primary); }

  /* pil kategori */
  #kd-root .kd-pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; background:#f1f5f9; color:#475569; }
  #kd-root .kd-pill-eol { background:#fef2f2; color:#b91c1c; }
  #kd-root .kd-pill-wp { background:#fffbeb; color:#b45309; }
  #kd-root .kd-pill-cut { background:#eef2ff; color:#4338ca; }

  /* pager */
  #kd-root .kd-pager { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:12px 2px 4px; font-size:13px; color:var(--kd-muted); }
  #kd-root .kd-pager-ctl { display:flex; align-items:center; gap:6px; }
  #kd-root .kd-pager select { height:32px; border:1px solid #cbd5e1; border-radius:8px; padding:0 6px; background:#fff; color:var(--kd-text); }
  #kd-root .kd-pg { min-width:34px; height:32px; padding:0 10px; border:1px solid var(--kd-line); border-radius:8px; background:#fff; color:var(--kd-text); font-size:13px; cursor:pointer; }
  #kd-root .kd-pg:hover:not(:disabled) { background:var(--kd-bg); }
  #kd-root .kd-pg:disabled { color:#cbd5e1; cursor:not-allowed; }

  /* gambar penuh */
  .kd-mod .modal-content { border:none; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.25); }
  .kd-mod .modal-header { padding:18px 24px 14px; border-bottom:1px solid var(--kd-line); }
  .kd-mod .modal-title { font-size:17px; font-weight:700; color:var(--kd-ink); font-family:inherit; word-break:break-all; }
  .kd-mod .close { margin-top:2px; font-size:26px; opacity:.45; }
  .kd-mod .modal-body { padding:12px; text-align:center; background:#0f172a; border-radius:0 0 14px 14px; }
  .kd-mod .modal-body img { max-width:100%; max-height:75vh; border-radius:8px; background:#fff; }

  @media (max-width: 767px) {
    #kd-root .kd-toolbar { flex-direction:column; align-items:stretch; }
    #kd-root .kd-search { max-width:none; flex:none; }
    #kd-root .kd-search input { height:44px; font-size:16px; }
    #kd-root .kd-ms { width:100%; }
    #kd-root .kd-chip { width:100%; height:44px; justify-content:space-between; font-size:15px; }
    #kd-root .kd-ms-panel { left:0; right:0; }
    #kd-root .kd-ms-opt { padding:12px 10px; font-size:15px; }
    #kd-root .kd-count { margin-left:0; }
    #kd-root .kd-xls { width:100%; justify-content:center; height:44px; font-size:15px; }
    #kd-root .kd-body { padding:12px 12px 8px; }
    /* tabel -> kartu per SKU */
    #kd-root .kd-table-wrap { border:0; overflow:visible; background:transparent; }
    #kd-root .kd-table, #kd-root .kd-table tbody { display:block; }
    #kd-root .kd-table thead { display:none; }
    #kd-root .kd-table > tbody > tr { display:block; margin-bottom:10px; border:1px solid var(--kd-line); border-radius:12px; background:#fff; padding:6px 12px; }
    #kd-root .kd-table > tbody > tr > td { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid #f1f5f9; background:transparent !important; text-align:right; }
    #kd-root .kd-table > tbody > tr > td:last-child { border-bottom:0; }
    #kd-root .kd-table > tbody > tr > td::before { content:attr(data-label); font-size:11.5px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--kd-muted); text-align:left; flex:none; }
    #kd-root .kd-table > tbody > tr > td.kd-no { display:none; }
    #kd-root .kd-table > tbody > tr > td.kd-sku { font-size:15px; }
    #kd-root .kd-table > tbody > tr > td.kd-empty { display:block; text-align:center; }
    #kd-root .kd-table > tbody > tr > td.kd-empty::before { content:none; }
    #kd-root .kd-thumb { width:56px; height:56px; }
    #kd-root .kd-eye { width:28px; height:28px; font-size:13px; }
    #kd-root .kd-pager { justify-content:center; }
    #kd-root .kd-pg { min-width:44px; height:40px; }
    #kd-root .kd-pager select { height:40px; }
    .kd-mod .modal-dialog { margin:10px; }
  }
</style>

<div id="kd-root">
  <div class="kd-head">
    <h2 class="kd-title">Kategori SKU</h2>
    <p class="kd-sub">Daftar SKU dengan gambar, no. rak, dan kategorinya. Untuk mengubah kategori, gunakan menu Update Kategori SKU.</p>
  </div>

  <div class="kd-body">
    <div class="kd-toolbar">
      <div class="kd-search"><i class="fa fa-search"></i><input type="text" id="kd-cari" placeholder="Cari SKU atau No Rak" autocomplete="off"></div>
      <div class="kd-ms" id="kd-ms">
        <button type="button" class="kd-chip" id="kd-ms-btn" aria-haspopup="true" aria-expanded="false" title="Filter kategori (bisa pilih lebih dari satu)">
          <span>Kategori</span><span class="kd-chip-count" id="kd-ms-n"></span><i class="fa fa-angle-down"></i>
        </button>
        <div class="kd-ms-panel" id="kd-ms-panel" role="group" aria-label="Pilih kategori">
          <?php foreach ($kategori as $k) :
              $nilai = $k['kategori'];
              $nama  = $nilai === $belum_ada ? '(Belum ada kategori)' : $nilai; ?>
          <label class="kd-ms-opt"><input type="checkbox" class="kd-ms-cek" value="<?= htmlspecialchars($nilai) ?>"><span><?= htmlspecialchars($nama) ?></span><span class="kd-n"><?= number_format($k['n'], 0, ',', '.') ?></span></label>
          <?php endforeach; ?>
          <div class="kd-ms-foot"><button type="button" id="kd-ms-reset">Reset</button></div>
        </div>
      </div>
      <span class="kd-count" id="kd-count"></span>
      <button type="button" class="kd-xls" id="kd-export" title="Unduh daftar (sesuai pencarian dan filter) sebagai file Excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
    </div>
    <div class="kd-table-wrap">
      <table class="kd-table">
        <thead><tr><th style="width:56px">#</th><th>SKU</th><th>No Rak</th><th>Kategori</th></tr></thead>
        <tbody id="kd-body"><tr><td colspan="4" class="kd-empty"><i class="fa fa-spinner fa-spin"></i>Memuat data...</td></tr></tbody>
      </table>
    </div>
    <div class="kd-pager">
      <div class="kd-pager-ctl">Tampilkan
        <select id="kd-ukuran"><option>25</option><option selected>50</option><option>100</option></select> baris</div>
      <div class="kd-pager-ctl">
        <button type="button" class="kd-pg" id="kd-prev"><i class="fa fa-angle-left"></i></button>
        <span id="kd-hal"></span>
        <button type="button" class="kd-pg" id="kd-next"><i class="fa fa-angle-right"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Pop-up gambar penuh -->
<div class="modal fade kd-mod" id="kd-lb" role="dialog" aria-labelledby="kd-lb-judul" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="kd-lb-judul">Gambar SKU</h4>
      </div>
      <div class="modal-body"><img id="kd-lb-img" alt="Gambar SKU"></div>
    </div>
  </div>
</div>

<script>
(function () {
  var BELUM = '<?= htmlspecialchars($belum_ada, ENT_QUOTES) ?>';
  var hal = 1, total = 0, totalHal = 1, kat = [], baris = [], reqKe = 0, timer = null;

  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function escAttr(s) { return esc(s).replace(/"/g, '&quot;'); }
  function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
  function pil(k) {
    if (!k || $.trim(k) === '') { return '<span class="kd-kosong">&ndash;</span>'; }
    var c = k === 'EOL' ? ' kd-pill-eol' : (k === 'WP EOL' ? ' kd-pill-wp' : (k === 'CUT' ? ' kd-pill-cut' : ''));
    return '<span class="kd-pill' + c + '">' + esc(k) + '</span>';
  }
  function thumb(r) {
    var src = r.foto || r.foto_lokal;
    if (!src) { return '<span class="kd-thumb" title="Belum ada foto"><i class="fa fa-picture-o"></i></span>'; }
    return '<span class="kd-thumb"><img src="' + escAttr(src) + '"' + (r.foto && r.foto_lokal ? ' data-foto-lokal="' + escAttr(r.foto_lokal) + '"' : '') + ' alt="" loading="lazy">' +
      '<button type="button" class="kd-eye" data-i="' + r.__i + '" title="Lihat gambar penuh" aria-label="Lihat gambar penuh"><i class="fa fa-eye"></i></button></span>';
  }

  function gambar(d, awal) {
    var html = '';
    baris = d.baris;
    baris.forEach(function (r, i) {
      r.__i = i;
      html += '<tr><td class="kd-kosong kd-no">' + (awal + i + 1) + '</td>' +
        '<td class="kd-sku" data-label="SKU"><span class="kd-skucell">' + thumb(r) + '<span>' + esc(r.id_sku) + '</span></span></td>' +
        '<td data-label="No Rak">' + (r.no_rak ? esc(r.no_rak) : '<span class="kd-kosong">&ndash;</span>') + '</td>' +
        '<td data-label="Kategori">' + pil(r.kategori_sku) + '</td></tr>';
    });
    $('#kd-body').html(html || '<tr><td colspan="4" class="kd-empty"><i class="fa fa-search"></i>Tidak ada SKU yang cocok dengan pencarian / filter.</td></tr>');
    $('#kd-count').text(fmt(d.total) + ' SKU');
    $('#kd-hal').text('Halaman ' + d.halaman + ' / ' + d.total_halaman);
    $('#kd-prev').prop('disabled', d.halaman <= 1);
    $('#kd-next').prop('disabled', d.halaman >= d.total_halaman);
  }

  /** Ambil satu halaman dari server; permintaan lama yang terlambat dibuang (reqKe). */
  function muat() {
    var ini = ++reqKe, ukuran = parseInt($('#kd-ukuran').val(), 10) || 50;
    $.ajax({ url: 'purchasing/kategori-sku/get-data', type: 'POST', dataType: 'json', timeout: 60000,
      data: { q: $.trim($('#kd-cari').val()), kat: kat, halaman: hal, ukuran: ukuran },
      success: function (r) {
        if (ini !== reqKe) { return; }
        if (r.code !== 200) { $('#kd-body').html('<tr><td colspan="4" class="kd-empty"><i class="fa fa-exclamation-triangle"></i>' + esc(r.message) + '</td></tr>'); return; }
        var d = r.data;
        hal = d.halaman; total = d.total; totalHal = d.total_halaman;
        gambar(d, (d.halaman - 1) * d.ukuran);
      },
      error: function (xhr) {
        if (ini !== reqKe) { return; }
        $('#kd-body').html('<tr><td colspan="4" class="kd-empty"><i class="fa fa-exclamation-triangle"></i>Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 160)) + '</td></tr>');
      }
    });
  }

  // gambar penuh
  $('#kd-body').on('click', '.kd-eye', function () {
    var r = baris[parseInt($(this).attr('data-i'), 10)];
    if (!r) { return; }
    var img = $('#kd-lb-img');
    img.removeAttr('data-foto-lokal');
    if (r.foto && r.foto_lokal) { img.attr('data-foto-lokal', r.foto_lokal); }
    img.attr('src', r.foto || r.foto_lokal);
    $('#kd-lb-judul').text(r.id_sku);
    $('#kd-lb').modal('show');
  });
  $('#kd-lb').on('hidden.bs.modal', function () { $('#kd-lb-img').attr('src', ''); });

  // filter kategori multi-pilih: tanpa centang = semua kategori
  function segarkanKategori() {
    kat = $('#kd-ms .kd-ms-cek:checked').map(function () { return this.value; }).get();
    $('#kd-ms').toggleClass('aktif', kat.length > 0);
    $('#kd-ms-n').text(kat.length || '');
    hal = 1; muat();
  }
  function tutupKategori() { $('#kd-ms').removeClass('buka'); $('#kd-ms-btn').attr('aria-expanded', 'false'); }
  $('#kd-ms-btn').on('click', function (e) {
    e.stopPropagation();
    var buka = !$('#kd-ms').hasClass('buka');
    $('#kd-ms').toggleClass('buka', buka); $(this).attr('aria-expanded', buka ? 'true' : 'false');
  });
  $('#kd-ms-panel').on('click', function (e) { e.stopPropagation(); });
  $('#kd-ms').on('change', '.kd-ms-cek', segarkanKategori);
  $('#kd-ms-reset').on('click', function () { $('#kd-ms .kd-ms-cek').prop('checked', false); segarkanKategori(); });
  // handler dokumen diberi namespace agar tidak menumpuk saat halaman dimuat ulang (SPA)
  $(document).off('click.kdms').on('click.kdms', tutupKategori);
  $(document).off('keydown.kdms').on('keydown.kdms', function (e) { if (e.key === 'Escape') { tutupKategori(); } });

  $('#kd-cari').on('input', function () { clearTimeout(timer); timer = setTimeout(function () { hal = 1; muat(); }, 300); });
  $('#kd-ukuran').on('change', function () { hal = 1; muat(); });
  $('#kd-prev').on('click', function () { if (hal > 1) { hal--; muat(); } });
  $('#kd-next').on('click', function () { if (hal < totalHal) { hal++; muat(); } });
  // export Excel (pencarian + filter kategori yang sedang aktif, semua halaman)
  $('#kd-export').on('click', function () {
    var tombol = $(this);
    if (!total) { alert('Tidak ada data untuk diekspor.'); return; }
    tombol.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan...');
    var x = new XMLHttpRequest();
    x.open('GET', 'purchasing/kategori-sku/export?' + $.param({ q: $.trim($('#kd-cari').val()), kat: kat }));
    x.responseType = 'blob'; x.timeout = 120000;
    var pulih = function () { tombol.prop('disabled', false).html('<i class="fa fa-file-excel-o"></i> Export Excel'); };
    x.onload = function () {
      pulih();
      if (x.status !== 200 || (x.getResponseHeader('Content-Type') || '').indexOf('spreadsheetml') === -1) { alert('Export gagal. Muat ulang halaman lalu coba lagi.'); return; }
      var m = /filename="([^"]+)"/.exec(x.getResponseHeader('Content-Disposition') || '');
      var a = document.createElement('a');
      a.href = URL.createObjectURL(x.response); a.download = m ? m[1] : 'kategori-sku.xlsx';
      document.body.appendChild(a); a.click();
      setTimeout(function () { URL.revokeObjectURL(a.href); document.body.removeChild(a); }, 1000);
    };
    x.onerror = x.ontimeout = function () { pulih(); alert('Export gagal: koneksi bermasalah atau waktu habis.'); };
    x.send();
  });

  muat();
})();
</script>
