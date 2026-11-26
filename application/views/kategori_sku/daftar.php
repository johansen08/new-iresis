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
  #kd-root .kd-head { padding:0 0 12px; }

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
  #kd-root .kd-table > thead > tr > th.kd-jual, #kd-root .kd-table > tbody > tr > td.kd-jual { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
  #kd-root .kd-info { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin:0 0 14px; }
  #kd-root .kd-card { display:flex; flex-direction:column; gap:10px; min-width:0; padding:14px; border:1px solid var(--kd-line); border-top:3px solid var(--aksen); border-radius:12px; background:#fff; box-shadow:0 4px 14px rgba(15,23,42,.06); text-align:left; }
  #kd-root .kd-card-jual { --aksen:#4f46e5; --lembut:#eef2ff; }
  #kd-root .kd-card-warna { --aksen:#ea580c; --lembut:#fff7ed; }
  #kd-root .kd-card-data { --aksen:#059669; --lembut:#ecfdf5; }
  #kd-root .kd-card-data[data-keadaan="basi"] { --aksen:#d97706; --lembut:#fffbeb; }
  #kd-root .kd-card-data[data-keadaan="hitung"], #kd-root .kd-card-data[data-keadaan="muat"] { --aksen:#4f46e5; --lembut:#eef2ff; }
  #kd-root .kd-card-data[data-keadaan="gagal"] { --aksen:#dc2626; --lembut:#fef2f2; }
  #kd-root .kd-card-head { display:flex; align-items:center; gap:10px; }
  #kd-root .kd-card-head > div { min-width:0; }
  #kd-root .kd-card-head h4 { margin:0; font-family:inherit; font-size:14px; font-weight:700; line-height:1.3; color:var(--kd-ink); }
  #kd-root .kd-card-head p { margin:2px 0 0; font-size:11.5px; line-height:1.4; color:var(--kd-muted); }
  #kd-root .kd-card-ikon { flex:none; display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:var(--lembut); color:var(--aksen); font-size:15px; }

  #kd-root .kd-aturan { display:flex; flex-direction:column; gap:6px; margin:0; padding:0; list-style:none; flex:1; justify-content:center; }
  #kd-root .kd-aturan li { display:flex; align-items:center; gap:8px; padding:7px 10px; border-radius:8px; font-size:12.5px; line-height:1.4; }
  #kd-root .kd-aturan li.ya { background:#ecfdf5; color:#065f46; }
  #kd-root .kd-aturan li.tidak { background:#f8fafc; color:#475569; }
  #kd-root .kd-aturan li > i { flex:none; width:12px; text-align:center; }
  #kd-root .kd-aturan li.tidak > i { color:#94a3b8; }

  #kd-root .kd-contoh { display:grid; grid-template-columns:1fr 1fr; gap:8px; flex:1; }
  #kd-root .kd-contoh-kotak { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; padding:8px 6px; border:1px solid var(--kd-line); border-radius:10px; background:var(--kd-bg); text-align:center; }
  #kd-root .kd-contoh-kotak.oranye { border-color:#fdba74; background:#fff7ed; }
  #kd-root .kd-contoh-angka { font-size:20px; font-weight:500; color:var(--kd-ink); font-variant-numeric:tabular-nums; }
  #kd-root .kd-contoh-kotak.oranye .kd-contoh-angka { font-weight:700; color:#ea580c; }
  #kd-root .kd-contoh-nama { font-size:11px; font-weight:600; color:var(--kd-muted); }
  #kd-root .kd-contoh-kotak.oranye .kd-contoh-nama { color:#c2410c; }
  #kd-root .kd-card-kaki { margin:0; font-size:11.5px; line-height:1.4; color:#475569; }

  #kd-root .kd-data-waktu { display:flex; align-items:baseline; flex-wrap:wrap; gap:8px; flex:1; align-content:center; }
  #kd-root .kd-data-waktu strong { font-size:26px; font-weight:700; line-height:1.1; color:var(--kd-ink); font-variant-numeric:tabular-nums; }
  #kd-root .kd-data-waktu span { font-size:12px; color:var(--kd-muted); }
  #kd-root .kd-bar { position:relative; height:6px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
  #kd-root .kd-bar span { display:block; height:100%; width:0; border-radius:999px; background:var(--aksen); transition:width .3s ease; }
  #kd-root .kd-card-data[data-keadaan="hitung"] .kd-bar { height:10px; }
  #kd-root .kd-card-data[data-keadaan="hitung"] .kd-bar::after { content:''; position:absolute; top:0; bottom:0; width:40%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.75), transparent); animation:kd-kilau 1.1s linear infinite; }
  #kd-root .kd-data-kaki { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
  #kd-root .kd-data-kaki > span { font-size:11.5px; color:var(--kd-muted); font-variant-numeric:tabular-nums; }
  #kd-root .kd-hitung { display:inline-flex; align-items:center; gap:6px; height:34px; margin-left:auto; padding:0 14px; border:0; border-radius:8px; background:var(--kd-primary); color:#fff; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
  #kd-root .kd-hitung:hover:not(:disabled) { background:#4338ca; }
  #kd-root .kd-hitung:focus-visible { outline:3px solid #a5b4fc; outline-offset:2px; }
  #kd-root .kd-hitung:disabled { background:#a5b4fc; cursor:wait; }

  /* selama hitung: garis berjalan di atas tabel; sel bulan tanpa angka diberi kerangka berdenyut */
  #kd-root .kd-table-wrap { position:relative; }
  #kd-root .kd-table-wrap.menghitung::before { content:''; position:absolute; left:0; top:0; z-index:2; width:35%; height:3px; background:var(--kd-primary); animation:kd-jalan 1.2s ease-in-out infinite; }
  #kd-root .kd-table-wrap.menghitung td.kd-jual > * { opacity:.55; }
  #kd-root .kd-rangka { display:inline-block; width:44px; height:12px; border-radius:6px; background:#e2e8f0; animation:kd-denyut 1s ease-in-out infinite; }
  @keyframes kd-kilau { from { left:-40%; } to { left:100%; } }
  @keyframes kd-jalan { 0% { left:0; } 50% { left:65%; } 100% { left:0; } }
  @keyframes kd-denyut { 50% { opacity:.4; } }
  @media (prefers-reduced-motion: reduce) {
    #kd-root .kd-table-wrap.menghitung::before, #kd-root .kd-rangka, #kd-root .kd-card-data[data-keadaan="hitung"] .kd-bar::after { animation:none; }
  }
  @media (max-width: 1099px) {
    #kd-root .kd-info { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    #kd-root .kd-card-data { grid-column:1 / -1; order:-1; }
  }
  #kd-root .kd-belum { color:#ea580c; font-weight:700; }
  #kd-root .kd-sort { display:inline-flex; align-items:center; gap:6px; padding:0; border:0; background:none; color:inherit; font-size:inherit; font-weight:inherit; letter-spacing:inherit; text-transform:inherit; cursor:pointer; white-space:nowrap; }
  #kd-root .kd-sort:hover, #kd-root .kd-sort.aktif { color:var(--kd-primary); }
  #kd-root .kd-sort .fa-sort { color:#cbd5e1; }
  #kd-root .kd-urut { display:none; }
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
    #kd-root .kd-info { grid-template-columns:1fr; }
    #kd-root .kd-p-kursor { display:none; }
    #kd-root .kd-hitung { width:100%; height:44px; justify-content:center; font-size:15px; }
    #kd-root .kd-table-wrap.menghitung::before { display:none; }
    #kd-root .kd-urut { display:block; width:100%; height:44px; padding:0 10px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:var(--kd-text); font-size:15px; }
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
    <?php $this->load->view('kategori_sku/_upload_terakhir', ['upload_terakhir' => $upload_terakhir]); ?>
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
      <!-- Di HP judul kolom disembunyikan (tampilan kartu), jadi urutan dipilih dari sini. -->
      <select class="kd-urut" id="kd-urut" aria-label="Urutkan">
        <option value="">Urut: SKU</option>
        <?php foreach ($bulan_jual as $j => $b) : ?>
        <option value="<?= (int) $j ?>:desc"><?= htmlspecialchars($b) ?> terbanyak</option>
        <option value="<?= (int) $j ?>:asc"><?= htmlspecialchars($b) ?> tersedikit</option>
        <?php endforeach; ?>
      </select>
      <span class="kd-count" id="kd-count"></span>
      <button type="button" class="kd-xls" id="kd-export" title="Unduh daftar (sesuai pencarian dan filter) sebagai file Excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
    </div>
    <!-- Ringkasan kolom penjualan: arti angka, penanda belum final, dan kesegaran data. -->
    <div class="kd-info">
      <section class="kd-card kd-card-jual" aria-labelledby="kd-j1">
        <header class="kd-card-head"><span class="kd-card-ikon" aria-hidden="true"><i class="fa fa-bar-chart"></i></span>
          <div><h4 id="kd-j1">Pcs terjual per bulan</h4><p>Bulan mengikuti tanggal pesan</p></div></header>
        <ul class="kd-aturan">
          <li class="ya"><i class="fa fa-check" aria-hidden="true"></i><span>Dihitung: <b>COMPLETED</b></span></li>
          <li class="tidak"><i class="fa fa-times" aria-hidden="true"></i><span>Tidak: <b>Paket Hilang</b>, retur, cancel, belum selesai</span></li>
        </ul>
      </section>

      <section class="kd-card kd-card-warna" aria-labelledby="kd-j2">
        <header class="kd-card-head"><span class="kd-card-ikon" aria-hidden="true"><i class="fa fa-hourglass-half"></i></span>
          <div><h4 id="kd-j2">Cara baca warna angka</h4><p class="kd-p-kursor">Arahkan kursor ke angka orange untuk pcs belum final</p></div></header>
        <div class="kd-contoh">
          <div class="kd-contoh-kotak"><span class="kd-contoh-angka">1.250</span><span class="kd-contoh-nama">Final</span></div>
          <div class="kd-contoh-kotak oranye"><span class="kd-contoh-angka">1.250</span><span class="kd-contoh-nama">Masih bisa berubah</span></div>
        </div>
        <p class="kd-card-kaki">Orange = masih ada pesanan <b>SHIPPED</b>, <b>PROCESSING</b>, dll.</p>
      </section>

      <section class="kd-card kd-card-data" id="kd-data" data-keadaan="muat" aria-labelledby="kd-j3">
        <header class="kd-card-head"><span class="kd-card-ikon" aria-hidden="true"><i class="fa fa-clock-o" id="kd-data-ikon"></i></span>
          <div><h4 id="kd-j3">Data penjualan</h4><p id="kd-data-sub" aria-live="polite">Memeriksa data...</p></div></header>
        <div class="kd-data-waktu"><strong id="kd-data-jam">&ndash;</strong><span id="kd-data-tgl"></span></div>
        <div class="kd-bar" id="kd-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progres hitung penjualan"><span id="kd-bar-isi"></span></div>
        <div class="kd-data-kaki">
          <span id="kd-data-ket">Otomatis tiap 30 menit</span>
          <button type="button" class="kd-hitung" id="kd-hitung"><i class="fa fa-refresh" aria-hidden="true"></i> <span>Hitung ulang</span></button>
        </div>
      </section>
    </div>
    <div class="kd-table-wrap" id="kd-wrap">
      <table class="kd-table">
        <thead><tr><th style="width:56px">#</th><th>SKU</th><th>No Rak</th><th>Kategori</th><?php foreach ($bulan_jual as $j => $b) : ?><th class="kd-jual" aria-sort="none"><button type="button" class="kd-sort" data-urut="<?= (int) $j ?>" title="Urutkan penjualan <?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?> <i class="fa fa-sort"></i></button></th><?php endforeach; ?></tr></thead>
        <tbody id="kd-body"><tr><td colspan="<?= 4 + count($bulan_jual) ?>" class="kd-empty"><i class="fa fa-spinner fa-spin"></i>Memuat data...</td></tr></tbody>
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
  var BULAN = <?= json_encode(array_values($bulan_jual), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  var LEBAR = 4 + BULAN.length;
  var urut = '', arah = 'desc', nomorAwal = 0;
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

  /** Sel pcs per bulan; null = data penjualan gagal dihitung (strip, bukan 0). */
  function selJual(jual, belum) {
    return BULAN.map(function (label, j) {
      var v = jual ? jual[j] : null, isi;
      if (v === null || v === undefined) { isi = sedangHitung ? '<span class="kd-rangka" title="Sedang dihitung"></span>' : '<span class="kd-kosong">&ndash;</span>'; }
      else if (belum && belum[j] > 0) { isi = '<span class="kd-belum" title="' + fmt(belum[j]) + ' pcs belum final">' + fmt(v) + '</span>'; }
      else { isi = fmt(v); }
      return '<td class="kd-jual" data-label="' + escAttr(label) + '">' + isi + '</td>';
    }).join('');
  }

  /** Samakan panah judul kolom dan pilihan HP dengan urutan aktif. */
  function tandaiUrut() {
    $('#kd-root .kd-sort').each(function () {
      var aktif = $(this).attr('data-urut') === urut;
      $(this).toggleClass('aktif', aktif).find('i').attr('class', 'fa ' + (aktif ? (arah === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc') : 'fa-sort'));
      $(this).closest('th').attr('aria-sort', aktif ? (arah === 'asc' ? 'ascending' : 'descending') : 'none');
    });
    $('#kd-urut').val(urut === '' ? '' : urut + ':' + arah);
  }

  function gambarBaris() {
    var html = '';
    baris.forEach(function (r, i) {
      r.__i = i;
      html += '<tr><td class="kd-kosong kd-no">' + (nomorAwal + i + 1) + '</td>' +
        '<td class="kd-sku" data-label="SKU"><span class="kd-skucell">' + thumb(r) + '<span>' + esc(r.id_sku) + '</span></span></td>' +
        '<td data-label="No Rak">' + (r.no_rak ? esc(r.no_rak) : '<span class="kd-kosong">&ndash;</span>') + '</td>' +
        '<td data-label="Kategori">' + pil(r.kategori_sku) + '</td>' + selJual(r.jual, r.belum) + '</tr>';
    });
    $('#kd-body').html(html || '<tr><td colspan="' + LEBAR + '" class="kd-empty"><i class="fa fa-search"></i>Tidak ada SKU yang cocok dengan pencarian / filter.</td></tr>');
  }

  function gambar(d, awal) {
    baris = d.baris;
    nomorAwal = awal;
    gambarBaris();
    terimaInfo(d.jual_info);
    $('#kd-count').text(fmt(d.total) + ' SKU');
    $('#kd-hal').text('Halaman ' + d.halaman + ' / ' + d.total_halaman);
    $('#kd-prev').prop('disabled', d.halaman <= 1);
    $('#kd-next').prop('disabled', d.halaman >= d.total_halaman);
  }

  // ---------- data penjualan: kesegaran, hitung ulang, progres ----------
  var AKAR = document.getElementById('kd-root');
  var info = null, dihitungPada = null, sedangHitung = false, cobaLagiPada = 0, mulaiHitung = 0, pemantau = null;
  var progresAkhir = null, jamHitung = null;
  var pesanSelesai = '', pesanSampai = 0;   // "Selesai dihitung dalam X detik" bertahan beberapa detik

  function lama(detik) {
    detik = Math.max(0, Math.floor(detik));
    return detik < 60 ? detik + ' detik' : Math.floor(detik / 60) + ' menit';
  }

  function keadaan(nama, sub, ikon) {
    $('#kd-data').attr('data-keadaan', nama);
    $('#kd-data-sub').text(sub);
    $('#kd-data-ikon').attr('class', 'fa ' + ikon);
  }

  function bar(persen) {
    $('#kd-bar-isi').css('width', persen + '%');
    $('#kd-bar').attr('aria-valuenow', Math.round(persen));
  }

  function tampilkanWaktu() {
    var w = info && /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/.exec(info.dihitung || '');
    $('#kd-data-jam').text(w ? w[4] : '\u2013');
    $('#kd-data-tgl').text(w ? 'tanggal ' + w[3] + '/' + w[2] + '/' + w[1] : 'Belum pernah dihitung');
  }

  /** Dipanggil tiap detik saat tidak menghitung: umur data + hitung mundur pembaruan otomatis. */
  function segarkanKartu() {
    if (sedangHitung || !info) { return; }
    tampilkanWaktu();
    if (Date.now() < cobaLagiPada) {
      keadaan('gagal', 'Hitung ulang gagal. Angka sebelumnya tetap dipakai.', 'fa-exclamation-triangle');
      bar(100);
      $('#kd-data-ket').text('Dicoba lagi dalam ' + lama((cobaLagiPada - Date.now()) / 1000));
      return;
    }
    if (!info.ada) { keadaan('basi', 'Belum ada hasil hitungan', 'fa-clock-o'); bar(0); $('#kd-data-ket').text('Otomatis tiap 30 menit'); return; }
    var umur = (Date.now() - dihitungPada) / 1000, sisa = info.masa - umur;
    keadaan(sisa > 0 ? 'segar' : 'basi',
      sisa > 0 && Date.now() < pesanSampai ? pesanSelesai
        : (sisa > 0 ? 'Terbaru, dihitung ' : 'Sudah lama, dihitung ') + (umur < 60 ? 'baru saja' : lama(umur) + ' lalu'),
      sisa > 0 ? 'fa-check' : 'fa-clock-o');
    bar(Math.max(0, Math.min(100, sisa / info.masa * 100)));   // sisa masa berlaku
    $('#kd-data-ket').text(sisa > 0 ? 'Otomatis lagi dalam ' + lama(Math.ceil(sisa / 60) * 60) : 'Menunggu dihitung ulang');
  }

  function terimaInfo(baru) {
    if (!baru) { return; }
    info = baru;
    dihitungPada = baru.ada ? Date.now() - baru.umur * 1000 : null;
    segarkanKartu();
    if (baru.basi && !sedangHitung && Date.now() >= cobaLagiPada) { hitungUlang(); }
  }

  function modeHitung(aktif) {
    sedangHitung = aktif;
    // Detik berjalan sendiri di browser: tetap bergerak walau jawaban progres dari server terlambat.
    clearInterval(jamHitung);
    progresAkhir = null;
    if (aktif) { jamHitung = setInterval(function () { tulisKetProgres(); }, 200); }
    $('#kd-hitung').prop('disabled', aktif)
      .find('i').toggleClass('fa-spin', aktif).end()
      .find('span').text(aktif ? 'Menghitung...' : 'Hitung ulang');
    $('#kd-wrap').toggleClass('menghitung', aktif).attr('aria-busy', aktif ? 'true' : 'false');
  }

  function tulisKetProgres() {
    var p = progresAkhir, detik = ((Date.now() - mulaiHitung) / 1000).toFixed(1).replace('.', ',');
    $('#kd-data-ket').text((p && p.total ? p.selesai + ' dari ' + p.total + ' bagian' : 'Menyiapkan data') + ' \u00b7 ' + detik + ' detik');
  }

  function tampilProgres(p) {
    var persen = p && p.total ? Math.min(99, Math.round(p.selesai / p.total * 100)) : 0;
    keadaan('hitung', 'Menghitung penjualan ' + persen + '%', 'fa-refresh fa-spin');
    bar(Math.max(4, persen));
    progresAkhir = p;
    tulisKetProgres();
  }

  function hentikanPantau() { clearTimeout(pemantau); pemantau = null; }

  /** Tanya progres berulang. olehOranglain = kita hanya menonton; selesai/gagal ditentukan dari status. */
  function pantau(olehOrangLain) {
    hentikanPantau();
    pemantau = setTimeout(function () {
      if (!sedangHitung || !document.contains(AKAR)) { return; }
      $.ajax({ url: 'purchasing/kategori-sku/status-hitung', dataType: 'json', timeout: 10000 }).done(function (r) {
        if (!sedangHitung || r.code !== 200) { return; }
        if (r.data.status === 'proses') { tampilProgres(r.data); }
        else if (olehOrangLain) { selesaiHitung(r.data.status !== 'gagal'); }
      }).always(function () { if (sedangHitung) { pantau(olehOrangLain); } });
    }, 350);
  }

  function selesaiHitung(berhasil) {
    hentikanPantau();
    var tunggu = Math.max(0, 700 - (Date.now() - mulaiHitung));   // progres jangan hanya berkedip
    if (berhasil) { bar(100); keadaan('hitung', 'Menghitung penjualan 100%', 'fa-refresh fa-spin'); }
    setTimeout(function () {
      var detik = ((Date.now() - mulaiHitung) / 1000).toFixed(1).replace('.', ',');
      modeHitung(false);
      if (!berhasil) { cobaLagiPada = Date.now() + 5 * 60 * 1000; segarkanKartu(); muat(); return; }
      cobaLagiPada = 0;
      pesanSelesai = 'Selesai dihitung dalam ' + detik + ' detik';
      pesanSampai = Date.now() + 8000;
      // Kartu langsung keluar dari mode progres walau pemuatan tabel sesudah ini gagal.
      keadaan('segar', pesanSelesai, 'fa-check');
      $('#kd-data-ket').text('Memuat angka terbaru...');
      info = null;
      muat();   // angka baru diambil dari cache; info terbaru ikut di jawabannya
    }, tunggu);
  }

  function hitungUlang() {
    if (sedangHitung) { return; }
    mulaiHitung = Date.now(); cobaLagiPada = 0;
    modeHitung(true);
    tampilProgres(null);
    if (baris.length) { gambarBaris(); }   // sel tanpa angka langsung berubah jadi kerangka
    $.ajax({ url: 'purchasing/kategori-sku/hitung-ulang', type: 'POST', dataType: 'json', timeout: 200000 }).done(function (r) {
      if (r.code !== 200) { selesaiHitung(false); return; }
      if (r.data.status === 'sibuk') { pantau(true); return; }   // pengguna lain sedang menghitung
      selesaiHitung(true);
    }).fail(function () { selesaiHitung(false); });
    pantau(false);
  }

  $('#kd-hitung').on('click', hitungUlang);
  // detak per detik; berhenti sendiri bila halaman sudah diganti (SPA)
  clearInterval(window.kdDetak);
  window.kdDetak = setInterval(function () {
    if (!document.contains(AKAR)) { clearInterval(window.kdDetak); clearInterval(jamHitung); hentikanPantau(); return; }
    if (sedangHitung || !info) { return; }
    segarkanKartu();
    if (info.ada && !document.hidden && (Date.now() - dihitungPada) / 1000 >= info.masa && Date.now() >= cobaLagiPada) { hitungUlang(); }
    else if (cobaLagiPada && Date.now() >= cobaLagiPada) { hitungUlang(); }
  }, 1000);

  /** Ambil satu halaman dari server; permintaan lama yang terlambat dibuang (reqKe). */
  function muat(sesudah) {
    var ini = ++reqKe, ukuran = parseInt($('#kd-ukuran').val(), 10) || 50;
    $.ajax({ url: 'purchasing/kategori-sku/get-data', type: 'POST', dataType: 'json', timeout: 120000,
      data: { q: $.trim($('#kd-cari').val()), kat: kat, halaman: hal, ukuran: ukuran, urut: urut, arah: arah },
      success: function (r) {
        if (ini !== reqKe) { return; }
        if (r.code !== 200) { $('#kd-body').html('<tr><td colspan="' + LEBAR + '" class="kd-empty"><i class="fa fa-exclamation-triangle"></i>' + esc(r.message) + '</td></tr>'); return; }
        var d = r.data;
        hal = d.halaman; total = d.total; totalHal = d.total_halaman;
        gambar(d, (d.halaman - 1) * d.ukuran);
        if (typeof sesudah === 'function') { sesudah(); }
      },
      error: function (xhr) {
        if (ini !== reqKe) { return; }
        $('#kd-body').html('<tr><td colspan="' + LEBAR + '" class="kd-empty"><i class="fa fa-exclamation-triangle"></i>Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 160)) + '</td></tr>');
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

  // sort per bulan: klik judul berputar terbanyak -> tersedikit -> kembali urut SKU
  $('#kd-root').on('click', '.kd-sort', function () {
    var k = $(this).attr('data-urut');
    if (urut !== k) { urut = k; arah = 'desc'; }
    else if (arah === 'desc') { arah = 'asc'; }
    else { urut = ''; arah = 'desc'; }
    tandaiUrut(); hal = 1; muat();
  });
  $('#kd-urut').on('change', function () {
    var p = this.value.split(':');
    urut = p[0]; arah = p[1] || 'desc';
    tandaiUrut(); hal = 1; muat();
  });

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
    x.open('GET', 'purchasing/kategori-sku/export?' + $.param({ q: $.trim($('#kd-cari').val()), kat: kat, urut: urut, arah: arah }));
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
