<?php
// View menu TIM PURCHASING -> Update Kategori SKU. Dimuat lewat AJAX (SPA); selector diawali #ks-root / #ks-modal.
// Gaya mengikuti CS -> Manajemen Komplain (New). Responsif: nyaman di HP.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Update Kategori SKU</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<style>
  #ks-root, #ks-modal {
    --ks-primary:#4f46e5; --ks-primary-hover:#4338ca; --ks-primary-light:rgba(79,70,229,.1);
    --ks-bg:#f8fafc; --ks-line:#e2e8f0; --ks-text:#334155; --ks-muted:#64748b; --ks-ink:#0f172a;
    font-family:'Inter','Segoe UI',sans-serif; color:#1e293b;
  }
  #ks-root button, #ks-root input, #ks-modal button { font-family:inherit; }
  .ks-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:0 18px; border:1px solid transparent; border-radius:8px; background:var(--ks-primary); color:#fff; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s ease; }
  .ks-btn:hover, .ks-btn:focus { background:var(--ks-primary-hover); color:#fff; text-decoration:none; }
  .ks-btn:disabled { opacity:.55; cursor:not-allowed; }
  .ks-btn-sec { background:#fff; color:var(--ks-text); border-color:var(--ks-line); }
  .ks-btn-sec:hover, .ks-btn-sec:focus { background:var(--ks-bg); color:var(--ks-text); border-color:#cbd5e1; }

  #ks-root .ks-title { margin:0; font-size:20px; font-weight:700; color:var(--ks-ink); }
  #ks-root .ks-sub { margin:2px 0 14px; font-size:13px; color:var(--ks-muted); }
  #ks-root .ks-grid { display:grid; grid-template-columns:minmax(0,3fr) minmax(0,2fr); gap:16px; align-items:start; }
  #ks-root .ks-card { background:#fff; border:1px solid var(--ks-line); border-radius:14px; padding:18px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  #ks-root .ks-card h3 { margin:0 0 12px; font-size:15px; font-weight:700; color:var(--ks-ink); display:flex; align-items:center; gap:8px; }
  #ks-root .ks-step { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:var(--ks-primary); color:#fff; font-size:12px; font-weight:700; flex:none; }

  #ks-root .ks-drop { display:block; border:2px dashed #cbd5e1; border-radius:12px; padding:26px 14px; text-align:center; cursor:pointer; background:var(--ks-bg); transition:all .15s; }
  #ks-root .ks-drop:hover, #ks-root .ks-drop.over { border-color:var(--ks-primary); background:var(--ks-primary-light); }
  #ks-root .ks-drop i { font-size:30px; color:#94a3b8; display:block; margin-bottom:6px; }
  #ks-root .ks-drop b { color:var(--ks-ink); }
  #ks-root .ks-drop small { display:block; margin-top:4px; color:var(--ks-muted); }
  #ks-root .ks-file { display:none; align-items:center; gap:10px; margin-top:12px; padding:10px 12px; border:1px solid var(--ks-line); border-radius:10px; background:#fff; }
  #ks-root .ks-file.ada { display:flex; }
  #ks-root .ks-file .nama { flex:1; min-width:0; font-weight:600; color:var(--ks-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  #ks-root .ks-file .ukuran { color:var(--ks-muted); font-size:12px; white-space:nowrap; }
  #ks-root .ks-file button { border:0; background:none; color:var(--ks-muted); font-size:18px; padding:4px 8px; cursor:pointer; }
  #ks-root .ks-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
  #ks-root .ks-err { margin:10px 0 0; min-height:18px; font-size:12.5px; font-weight:600; color:#dc2626; }

  #ks-root .ks-aturan { margin:0; padding-left:18px; color:var(--ks-text); font-size:13.5px; line-height:1.6; }
  #ks-root .ks-aturan li { margin-bottom:4px; }
  #ks-root .ks-kode { display:inline-block; padding:1px 7px; border-radius:6px; background:#eef2ff; color:#4338ca; font-weight:600; font-size:12.5px; }
  #ks-root .ks-tabel { width:100%; margin:8px 0 0; border-collapse:separate; border-spacing:0; font-size:13px; border:1px solid var(--ks-line); border-radius:8px; overflow:hidden; }
  #ks-root .ks-tabel th { background:var(--ks-bg); padding:7px 10px; text-align:left; color:var(--ks-muted); font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
  #ks-root .ks-tabel td { padding:7px 10px; border-top:1px solid var(--ks-line); }

  /* pop-up hasil */
  #ks-modal .modal-content { border:none; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.25); }
  #ks-modal .modal-header { padding:18px 22px 14px; border-bottom:1px solid var(--ks-line); display:flex; align-items:center; gap:12px; }
  #ks-modal .modal-header .close { margin:0 0 0 auto; font-size:26px; opacity:.45; }
  #ks-modal .ks-ic { width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:20px; flex:none; }
  #ks-modal .ks-ic.ok { background:#ecfdf5; color:#047857; }
  #ks-modal .ks-ic.gagal { background:#fef2f2; color:#b91c1c; }
  #ks-modal .modal-title { font-size:17px; font-weight:700; color:var(--ks-ink); font-family:inherit; }
  #ks-modal .modal-body { padding:16px 22px 6px; max-height:60vh; overflow-y:auto; }
  #ks-modal .modal-footer { padding:12px 22px 18px; border-top:0; }
  #ks-modal .ks-stat { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:10px; }
  #ks-modal .ks-stat > div { background:var(--ks-bg); border:1px solid var(--ks-line); border-radius:10px; padding:10px 12px; text-align:center; }
  #ks-modal .ks-stat b { display:block; font-size:22px; color:var(--ks-ink); font-variant-numeric:tabular-nums; }
  #ks-modal .ks-stat span { font-size:11.5px; color:var(--ks-muted); text-transform:uppercase; letter-spacing:.04em; }
  #ks-modal .ks-alasan { margin:8px 0 0; padding:0; list-style:none; }
  #ks-modal .ks-alasan li { padding:8px 10px; border:1px solid #fecaca; background:#fef2f2; color:#7f1d1d; border-radius:8px; margin-bottom:6px; font-size:13px; line-height:1.45; word-break:break-word; }
  #ks-modal .ks-catatan { font-size:12.5px; color:var(--ks-muted); margin:4px 0 8px; }

  @media (max-width: 767px) {
    #ks-root .ks-grid { grid-template-columns:1fr; }
    #ks-root .ks-actions .ks-btn { flex:1 1 100%; }
    #ks-root .ks-card { padding:14px; }
    #ks-modal .modal-dialog { margin:10px; }
    #ks-modal .ks-stat b { font-size:18px; }
    #ks-modal .modal-footer .ks-btn { width:100%; }
  }
</style>

<div id="ks-root">
  <h2 class="ks-title">Update Kategori SKU</h2>
  <p class="ks-sub">Upload Excel berisi SKU dan kategori untuk mengisi kolom kategori di data SKU.</p>

  <div class="ks-grid">
    <div class="ks-card">
      <h3><span class="ks-step">1</span> Upload berkas Excel</h3>
      <input type="file" id="ks-berkas" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" style="display:none">
      <label class="ks-drop" id="ks-drop" for="ks-berkas">
        <i class="fa fa-file-excel-o"></i>
        <b>Pilih berkas Excel</b> atau seret ke sini
        <small>.xlsx / .xls, maksimal 10 MB, maksimal 20.000 baris</small>
      </label>
      <div class="ks-file" id="ks-file">
        <i class="fa fa-file-excel-o" style="color:#047857;font-size:20px"></i>
        <span class="nama" id="ks-nama"></span><span class="ukuran" id="ks-ukuran"></span>
        <button type="button" id="ks-hapus" title="Ganti berkas" aria-label="Hapus berkas"><i class="fa fa-times"></i></button>
      </div>
      <p class="ks-err" id="ks-err"></p>
      <div class="ks-actions">
        <button type="button" class="ks-btn" id="ks-kirim" disabled><i class="fa fa-upload"></i> Upload &amp; Proses</button>
        <a class="ks-btn ks-btn-sec" href="purchasing/update-kategori-sku/template"><i class="fa fa-download"></i> Unduh Template</a>
      </div>
    </div>

    <div class="ks-card">
      <h3><span class="ks-step">2</span> Aturan pengisian</h3>
      <ul class="ks-aturan">
        <li>Kolom <b>A = SKU</b>, kolom <b>B = Kategori</b>; data mulai baris 2.</li>
        <li>SKU harus <b>sama persis</b> dengan SKU di sistem (huruf besar/kecil dan spasi dihitung).</li>
        <li>Kategori kosong otomatis menjadi <span class="ks-kode"><?= htmlspecialchars($default_kategori) ?></span>.</li>
        <li>Satu SKU hanya boleh sekali; kategori maksimal 50 karakter.</li>
        <li>Bila ada satu baris yang salah, <b>seluruh upload dibatalkan</b> dan alasannya ditampilkan agar bisa diperbaiki.</li>
      </ul>
      <table class="ks-tabel"><thead><tr><th>SKU</th><th>Kategori</th></tr></thead>
        <tbody><tr><td>ABC-01</td><td>EOL</td></tr><tr><td>ABC-02</td><td><i style="color:#94a3b8">(kosong)</i> &rarr; <?= htmlspecialchars($default_kategori) ?></td></tr></tbody></table>
    </div>
  </div>
</div>

<!-- Pop-up hasil upload -->
<div class="modal fade" id="ks-modal" role="dialog" aria-labelledby="ks-m-judul" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width:560px;">
    <div class="modal-content">
      <div class="modal-header">
        <span class="ks-ic" id="ks-m-ic"></span>
        <h4 class="modal-title" id="ks-m-judul"></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body" id="ks-m-isi"></div>
      <div class="modal-footer"><button type="button" class="ks-btn" data-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script>
(function () {
  var MAKS = 10 * 1024 * 1024, berkas = null, mengirim = false;
  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
  function ukuran(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }

  /** Pesan galat untuk berkas terpilih, '' bila lolos pemeriksaan di browser (server memeriksa ulang). */
  function cekBerkas(f) {
    if (!f) { return 'Pilih berkas Excel terlebih dahulu.'; }
    if (!/\.(xlsx|xls)$/i.test(f.name)) { return 'Format berkas harus .xlsx atau .xls.'; }
    if (f.size === 0) { return 'Berkas kosong (0 KB).'; }
    if (f.size > MAKS) { return 'Ukuran berkas ' + ukuran(f.size) + ' melebihi batas 10 MB.'; }
    return '';
  }

  function pilih(f) {
    var salah = cekBerkas(f);
    $('#ks-err').text(f ? salah : '');
    if (!f || salah) { berkas = null; $('#ks-file').removeClass('ada'); $('#ks-kirim').prop('disabled', true); if (!f) { $('#ks-berkas').val(''); } return; }
    berkas = f;
    $('#ks-nama').text(f.name); $('#ks-ukuran').text(ukuran(f.size));
    $('#ks-file').addClass('ada');
    $('#ks-kirim').prop('disabled', false);
  }

  function popup(ok, judul, html) {
    $('#ks-m-ic').attr('class', 'ks-ic ' + (ok ? 'ok' : 'gagal')).html('<i class="fa ' + (ok ? 'fa-check' : 'fa-times') + '"></i>');
    $('#ks-m-judul').text(judul);
    $('#ks-m-isi').html(html);
    $('#ks-modal').modal('show');
  }

  $('#ks-berkas').on('change', function () { pilih(this.files[0] || null); });
  $('#ks-hapus').on('click', function () { pilih(null); });
  $('#ks-drop').on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('over'); })
    .on('dragleave drop', function () { $(this).removeClass('over'); })
    .on('drop', function (e) {
      e.preventDefault();
      var f = e.originalEvent.dataTransfer.files[0];
      if (f) { pilih(f); }
    });

  $('#ks-kirim').on('click', function () {
    if (mengirim) { return; }
    var salah = cekBerkas(berkas);
    if (salah) { $('#ks-err').text(salah); return; }
    mengirim = true;
    var tombol = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
    var fd = new FormData(); fd.append('berkas', berkas);
    $.ajax({ url: 'purchasing/update-kategori-sku/upload', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json', timeout: 600000,
      success: function (r) {
        var d = r.data || {};
        if (r.code === 200) {
          popup(true, 'Upload berhasil',
            '<div class="ks-stat"><div><b>' + fmt(d.total) + '</b><span>SKU berhasil</span></div><div><b>' + fmt(d.berubah) + '</b><span>Berubah</span></div><div><b>' + fmt(d.sama) + '</b><span>Sudah sama</span></div></div>' +
            '<p class="ks-catatan">Kategori ' + fmt(d.total) + ' SKU sudah diperbarui. Kode batch: <b>' + esc(d.batch) + '</b></p>');
          pilih(null);
        } else {
          var li = '', g = d.galat || [r.message];
          g.forEach(function (x) { li += '<li>' + esc(x) + '</li>'; });
          var lebih = (d.total_galat || g.length) - g.length;
          popup(false, 'Gagal upload',
            '<p class="ks-catatan"><b>' + esc(r.message) + '</b><br>Perbaiki berkas sesuai alasan di bawah, lalu upload ulang.</p><ul class="ks-alasan">' + li + '</ul>' +
            (lebih > 0 ? '<p class="ks-catatan">&hellip; dan ' + fmt(lebih) + ' masalah lain. Perbaiki yang tampil dulu, lalu upload ulang untuk melihat sisanya.</p>' : ''));
        }
      },
      error: function (xhr, st) {
        var alasan = st === 'timeout' ? 'Waktu habis. Berkas terlalu besar atau server sibuk, coba lagi.' : 'Server tidak merespons dengan benar. ' + (xhr.responseText || '').substring(0, 160);
        popup(false, 'Gagal upload', '<ul class="ks-alasan"><li>' + esc(alasan) + '</li></ul><p class="ks-catatan">Tidak ada data yang diubah.</p>');
      },
      complete: function () { mengirim = false; tombol.html('<i class="fa fa-upload"></i> Upload &amp; Proses').prop('disabled', !berkas); }
    });
  });
})();
</script>
