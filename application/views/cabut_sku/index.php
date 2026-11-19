<?php
// View menu TIM MONITORING -> Cabut SKU. Dimuat lewat AJAX (SPA); selector diawali #cb-root / .cb-mod.
// Gaya mengikuti CS -> Manajemen Komplain (New): kartu ringkasan, tab, toolbar, tabel bersih, pil status, modal.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Cabut SKU</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Menu ini hanya untuk webmaster.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<style>
  #cb-root, .cb-mod {
    --cb-primary:#4f46e5; --cb-primary-hover:#4338ca; --cb-primary-light:rgba(79,70,229,.1);
    --cb-bg:#f8fafc; --cb-line:#e2e8f0; --cb-text:#334155; --cb-muted:#64748b; --cb-ink:#0f172a;
    font-family:'Inter','Segoe UI',sans-serif; color:#1e293b;
  }
  #cb-root input, #cb-root select, #cb-root button, .cb-mod input, .cb-mod button { font-family:inherit; }

  /* tombol */
  .cb-btn { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 18px; border:1px solid transparent; border-radius:8px; background:var(--cb-primary); color:#fff; font-size:14px; font-weight:600; cursor:pointer; transition:all .2s ease; }
  .cb-btn:hover { background:var(--cb-primary-hover); color:#fff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(79,70,229,.2); }
  .cb-btn:disabled { opacity:.55; cursor:not-allowed; transform:none; box-shadow:none; }
  .cb-btn-sec { background:#fff; color:var(--cb-text); border-color:var(--cb-line); }
  .cb-btn-sec:hover { background:var(--cb-bg); color:var(--cb-text); border-color:#cbd5e1; box-shadow:none; }

  /* header */
  #cb-root .cb-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:4px 0 14px; }
  #cb-root .cb-title { margin:0; font-size:20px; font-weight:700; color:var(--cb-ink); }
  #cb-root .cb-sub { margin:2px 0 0; font-size:13px; color:var(--cb-muted); }
  #cb-root .cb-head-act { display:flex; align-items:center; gap:12px; }
  #cb-root .cb-updated { font-size:12.5px; color:var(--cb-muted); text-align:right; line-height:1.3; }

  /* progres perbarui */
  #cb-root .cb-prog { height:6px; background:var(--cb-line); border-radius:999px; overflow:hidden; margin:0 0 14px; display:none; }
  #cb-root .cb-prog > div { height:100%; width:0; background:var(--cb-primary); transition:width .2s; }

  /* kartu ringkasan */
  #cb-root .cb-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
  #cb-root .cb-kpi { border:1px solid var(--cb-line); border-radius:12px; padding:14px 16px 12px; background:#fff; }
  #cb-root .cb-kpi-top { display:flex; align-items:center; gap:8px; }
  #cb-root .cb-kpi-ic { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; font-size:14px; }
  #cb-root .cb-ic-indigo { background:#eef2ff; color:#4338ca; }
  #cb-root .cb-ic-amber { background:#fffbeb; color:#b45309; }
  #cb-root .cb-ic-green { background:#ecfdf5; color:#047857; }
  #cb-root .cb-ic-red { background:#fef2f2; color:#b91c1c; }
  #cb-root .cb-ms { position:relative; }
  #cb-root .cb-chip { display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:var(--cb-text); font-size:13.5px; font-weight:500; cursor:pointer; white-space:nowrap; }
  #cb-root .cb-chip:hover { background:var(--cb-bg); }
  #cb-root .cb-chip .fa-angle-down { color:#94a3b8; }
  #cb-root .cb-chip-count { display:none; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:var(--cb-primary); color:#fff; font-size:11px; font-weight:700; line-height:18px; text-align:center; }
  #cb-root .cb-ms.aktif .cb-chip { border-color:#a5b4fc; background:#eef2ff; color:#3730a3; }
  #cb-root .cb-ms.aktif .cb-chip-count { display:inline-block; }
  #cb-root .cb-ms-panel { display:none; position:absolute; top:calc(100% + 4px); left:0; z-index:50; min-width:200px; padding:6px; background:#fff; border:1px solid var(--cb-line); border-radius:10px; box-shadow:0 10px 15px -3px rgba(0,0,0,.12); }
  #cb-root .cb-ms.buka .cb-ms-panel { display:block; }
  #cb-root .cb-ms-opt { display:flex; align-items:center; gap:10px; margin:0; padding:9px 10px; border-radius:8px; font-size:13.5px; font-weight:500; color:var(--cb-text); cursor:pointer; }
  #cb-root .cb-ms-opt:hover { background:var(--cb-primary-light); }
  #cb-root .cb-ms-opt input { width:16px; height:16px; margin:0; accent-color:var(--cb-primary); }
  #cb-root .cb-ms-foot { margin-top:4px; padding:6px 4px 2px; border-top:1px solid var(--cb-line); text-align:right; }
  #cb-root .cb-ms-foot button { border:0; background:none; color:var(--cb-primary); font-size:12.5px; font-weight:600; cursor:pointer; padding:4px 6px; }
  #cb-root .cb-select { height:38px; border:1px solid #cbd5e1; border-radius:8px; padding:0 10px; background:#fff; color:var(--cb-text); font-size:13.5px; }
  #cb-root .cb-selisih-plus { color:#047857; font-weight:700; }
  #cb-root .cb-selisih-min { color:#b91c1c; font-weight:700; }
  #cb-root .cb-tab .cb-tab-n.ada { background:#fffbeb; color:#b45309; }
  #cb-root .cb-kpi-label { font-size:13px; font-weight:600; color:var(--cb-text); }
  #cb-root .cb-kpi-val { margin-top:10px; font-size:28px; line-height:1.1; font-weight:700; color:var(--cb-ink); font-variant-numeric:tabular-nums; }
  #cb-root .cb-kpi-note { margin-top:4px; font-size:12px; color:var(--cb-muted); }

  /* kartu data */
  #cb-root .cb-body { margin-top:16px; padding:16px 18px 12px; background:#fff; border:1px solid var(--cb-line); border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  #cb-root .cb-tabs { display:flex; flex-wrap:wrap; gap:4px; margin:0 0 12px; border-bottom:1px solid var(--cb-line); }
  #cb-root .cb-tab { appearance:none; background:none; border:0; border-bottom:2px solid transparent; margin-bottom:-1px; padding:8px 14px; font-size:13.5px; font-weight:600; color:var(--cb-muted); cursor:pointer; }
  #cb-root .cb-tab:hover { color:var(--cb-ink); }
  #cb-root .cb-tab.aktif { color:var(--cb-primary); border-bottom-color:var(--cb-primary); }
  #cb-root .cb-tab-n { display:inline-block; min-width:20px; margin-left:6px; padding:1px 7px; border-radius:999px; background:#f1f5f9; color:#475569; font-size:11.5px; font-weight:700; text-align:center; }
  #cb-root .cb-tab.aktif .cb-tab-n { background:var(--cb-primary-light); color:var(--cb-primary); }
  #cb-root .cb-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:4px 0 14px; }
  #cb-root .cb-search { position:relative; flex:1 1 240px; max-width:340px; }
  #cb-root .cb-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; }
  #cb-root .cb-search input { width:100%; height:38px; padding:0 12px 0 34px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; background:#fff; color:var(--cb-ink); box-sizing:border-box; }
  #cb-root .cb-search input:focus { outline:none; border-color:var(--cb-primary); box-shadow:0 0 0 3px var(--cb-primary-light); }
  #cb-root .cb-count { margin-left:auto; font-size:12.5px; color:var(--cb-muted); }

  /* tabel */
  #cb-root .cb-table-wrap { border:1px solid var(--cb-line); border-radius:10px; overflow-x:auto; background:#fff; }
  #cb-root .cb-table { width:100%; margin:0; border-collapse:separate; border-spacing:0; }
  #cb-root .cb-table > thead > tr > th { padding:10px 12px; background:var(--cb-bg); border-bottom:1px solid var(--cb-line); font-size:11.5px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--cb-muted); white-space:nowrap; text-align:left; }
  #cb-root .cb-table > tbody > tr > td { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:13.5px; color:var(--cb-text); vertical-align:middle; }
  #cb-root .cb-table > tbody > tr:nth-child(even) > td { background:#fafbfd; }
  #cb-root .cb-table > tbody > tr:hover > td { background:#f1f5ff; }
  #cb-root .cb-table > tbody > tr:last-child > td { border-bottom:0; }
  #cb-root .cb-num { text-align:right !important; font-variant-numeric:tabular-nums; white-space:nowrap; }
  #cb-root .cb-sku { font-weight:600; color:var(--cb-ink); }
  #cb-root .cb-rak { color:var(--cb-text); }
  #cb-root .cb-kosong { color:#94a3b8; }
  #cb-root .cb-stok { font-weight:700; color:var(--cb-ink); }
  #cb-root .cb-empty { padding:36px 12px !important; text-align:center; color:var(--cb-muted); }
  #cb-root .cb-empty i { display:block; font-size:26px; margin-bottom:8px; color:#cbd5e1; }

  /* pil kategori */
  #cb-root .cb-pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; }
  #cb-root .cb-pill-eol { background:#fef2f2; color:#b91c1c; }
  #cb-root .cb-pill-wp { background:#fffbeb; color:#b45309; }
  #cb-root .cb-pill-cut { background:#eef2ff; color:#4338ca; }
  #cb-root .cb-pill-lain { background:#f1f5f9; color:#475569; }

  /* qty real */
  #cb-root .cb-qty { display:inline-flex; align-items:center; justify-content:flex-end; gap:8px; }
  #cb-root .cb-qty-val { font-weight:700; color:#047857; min-width:18px; text-align:right; }
  #cb-root .cb-iconbtn { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--cb-line); border-radius:6px; background:#fff; color:var(--cb-muted); cursor:pointer; transition:all .15s; padding:0; }
  #cb-root .cb-iconbtn:hover { background:var(--cb-primary-light); border-color:var(--cb-primary); color:var(--cb-primary); }

  /* gambar SKU + ikon mata */
  #cb-root .cb-skucell { display:flex; align-items:center; gap:10px; }
  #cb-root .cb-skucell > span { white-space:nowrap; }
  #cb-root .cb-thumb { position:relative; flex:none; width:48px; height:48px; border-radius:8px; border:1px solid var(--cb-line); background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:20px; }
  #cb-root .cb-thumb img { width:100%; height:100%; object-fit:cover; border-radius:7px; display:block; }
  #cb-root .cb-eye { position:absolute; right:-6px; bottom:-6px; width:22px; height:22px; padding:0; border:1px solid var(--cb-line); border-radius:50%; background:#fff; color:var(--cb-primary); font-size:11px; line-height:1; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 1px 3px rgba(15,23,42,.18); }
  #cb-root .cb-eye:hover { background:var(--cb-primary); color:#fff; border-color:var(--cb-primary); }
  .cb-mod.cb-lb .modal-body { padding:12px; text-align:center; background:#0f172a; border-radius:0 0 14px 14px; }
  .cb-mod.cb-lb .modal-body img { max-width:100%; max-height:75vh; border-radius:8px; background:#fff; }

  /* stok Jubelio: empat angka per gudang, warna per jenis (On Hand hijau, On Order merah, On Reserve ungu, On Available biru) */
  #cb-root .cb-table td.cb-m { width:30px; min-width:30px; text-align:center !important; padding:10px 2px; font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; cursor:help; }
  #cb-root .cb-table td.cb-m:hover { background:#eef2ff !important; }
  #cb-root .cb-table td.cb-mg { border-left:1px solid var(--cb-line); }
  #cb-root .cb-m-oh { color:#15803d; } #cb-root .cb-m-oo { color:#b91c1c; } #cb-root .cb-m-rs { color:#7e22ce; } #cb-root .cb-m-av { color:#1d4ed8; }
    #cb-root .cb-table > tbody > tr > td.cb-m-oh { color:#15803d; } #cb-root .cb-table > tbody > tr > td.cb-m-oo { color:#b91c1c; } #cb-root .cb-table > tbody > tr > td.cb-m-rs { color:#7e22ce; } #cb-root .cb-table > tbody > tr > td.cb-m-av { color:#1d4ed8; }
  #cb-root .cb-table > thead > tr > th.cb-hc { text-align:center !important; border-left:1px solid var(--cb-line); border-right:1px solid var(--cb-line); }
  #cb-root .cb-table > thead > tr > th.cb-hroot { background:#eef2ff; color:#4338ca; }
  #cb-root .cb-table > thead > tr > th.cb-hg { background:#f1f5f9; color:#334155; padding:8px 4px; font-size:10.5px; line-height:1.25; white-space:normal; letter-spacing:.02em; }
  #cb-tip { position:fixed; z-index:2000; display:none; padding:4px 9px; border-radius:6px; background:#0f172a; color:#fff; font-size:12px; font-weight:600; line-height:1.3; pointer-events:none; white-space:nowrap; box-shadow:0 4px 10px rgba(15,23,42,.3); }
  #cb-root .cb-copy { margin-left:2px; padding:2px 5px; border:0; border-radius:6px; background:transparent; color:#94a3b8; font-size:13px; line-height:1; cursor:pointer; }
  #cb-root .cb-copy:hover { background:var(--cb-primary-light, #eef2ff); color:var(--cb-primary); }
  #cb-root .cb-copy.ok { color:#15803d; }
  #cb-root .cb-syarat { display:flex; flex-wrap:wrap; align-items:center; gap:6px 8px; margin:6px 0 0; }
  #cb-root .cb-syarat-judul { font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--cb-muted); margin-right:2px; }
  #cb-root .cb-sy { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border:1px solid var(--cb-line); border-radius:999px; background:#fff; font-size:12.5px; color:var(--cb-text); white-space:nowrap; }
  #cb-root .cb-sy i { color:var(--cb-primary); font-size:12px; }
  #cb-root .cb-sy b { font-weight:700; color:var(--cb-ink); }
  #cb-root .cb-legend { display:flex; flex-wrap:wrap; align-items:center; gap:8px 16px; margin:0 0 12px; padding:9px 14px; border:1px solid var(--cb-line); border-radius:10px; background:var(--cb-bg); font-size:12.5px; color:var(--cb-muted); }
  #cb-root .cb-lg-info { display:inline-flex; align-items:center; gap:7px; color:var(--cb-text); font-weight:500; }
  #cb-root .cb-lg-info i { color:var(--cb-primary); font-size:14px; }
  #cb-root .cb-lg-chips { display:inline-flex; flex-wrap:wrap; gap:6px; }
  #cb-root .cb-lg { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border:1px solid var(--cb-line); border-radius:999px; background:#fff; font-size:12px; font-weight:600; color:var(--cb-text); white-space:nowrap; }
  #cb-root .cb-dot { width:9px; height:9px; border-radius:50%; display:inline-block; }
  #cb-root .cb-dot-oh { background:#15803d; } #cb-root .cb-dot-oo { background:#b91c1c; } #cb-root .cb-dot-rs { background:#7e22ce; } #cb-root .cb-dot-av { background:#1d4ed8; }
  #cb-root .cb-lg-note { margin-left:auto; font-size:12px; }
  @media (max-width: 767px) { #cb-root .cb-lg-note { margin-left:0; flex-basis:100%; } #cb-root .cb-sy { white-space:normal; } }
  #cb-root .cb-pindah { display:inline-flex; align-items:center; gap:8px; }
  #cb-root .cb-pindah-val { font-weight:700; color:#047857; }
  #cb-root .cb-btn-xls { height:38px; padding:0 14px; font-size:13.5px; margin-left:auto; }
  #cb-root .cb-toolbar .cb-count { margin-left:0; }

  /* pager */
  #cb-root .cb-pager { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:12px 2px 4px; font-size:13px; color:var(--cb-muted); }
  #cb-root .cb-pager-ctl { display:flex; align-items:center; gap:6px; }
  #cb-root .cb-pager select { height:32px; border:1px solid #cbd5e1; border-radius:8px; padding:0 6px; background:#fff; color:var(--cb-text); }
  #cb-root .cb-pg { min-width:34px; height:32px; padding:0 10px; border:1px solid var(--cb-line); border-radius:8px; background:#fff; color:var(--cb-text); font-size:13px; cursor:pointer; }
  #cb-root .cb-pg:hover:not(:disabled) { background:var(--cb-bg); }
  #cb-root .cb-pg:disabled { color:#cbd5e1; cursor:not-allowed; }

  /* modal qty real */
  .cb-mod .modal-content { border:none; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.25); }
  .cb-mod .modal-header { padding:18px 24px 14px; border-bottom:1px solid var(--cb-line); }
  .cb-mod .modal-title { font-size:17px; font-weight:700; color:var(--cb-ink); font-family:inherit; }
  .cb-mod .close { margin-top:2px; font-size:26px; opacity:.45; }
  .cb-mod .modal-body { padding:18px 24px 6px; }
  .cb-mod .modal-footer { padding:14px 24px 18px; border-top:0; }
  .cb-mod .cb-info { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
  .cb-mod .cb-info > div { background:var(--cb-bg); border:1px solid var(--cb-line); border-radius:10px; padding:10px 12px; }
  .cb-mod .cb-info dt { font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--cb-muted); }
  .cb-mod .cb-info dd { margin:2px 0 0; font-size:15px; font-weight:700; color:var(--cb-ink); word-break:break-all; }
  .cb-mod .cb-label { display:block; margin:0 0 6px; font-size:12.5px; font-weight:600; color:var(--cb-text); }
  .cb-mod .cb-req { color:#dc2626; }
  .cb-mod .cb-input { display:block; width:100%; height:46px; padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; font-size:18px; font-weight:600; color:var(--cb-ink); box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
  .cb-mod .cb-input:focus { outline:none; border-color:var(--cb-primary); box-shadow:0 0 0 3px var(--cb-primary-light); }
  .cb-mod .cb-input.salah { border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.12); }
  .cb-mod .cb-hint { margin:6px 0 0; font-size:12px; color:var(--cb-muted); }
  .cb-mod .cb-err { margin:6px 0 0; min-height:18px; font-size:12.5px; font-weight:600; color:#dc2626; }

  /* foto bukti di pop-up */
  .cb-mod .cb-fotobox { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:12px; border:2px dashed #cbd5e1; border-radius:10px; background:var(--cb-bg); }
  .cb-mod .cb-fotobox.salah { border-color:#ef4444; }
  .cb-mod .cb-fotobox img { display:none; width:84px; height:84px; object-fit:cover; border-radius:8px; border:1px solid var(--cb-line); background:#fff; }
  .cb-mod .cb-fotonama { font-size:12px; color:var(--cb-muted); word-break:break-all; }
  .cb-mod .cb-srv { margin:8px 0 0; min-height:0; font-size:12.5px; font-weight:600; color:#dc2626; }
  #cb-root .cb-fotolink { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; color:#047857; background:#ecfdf5; text-decoration:none; }
  #cb-root .cb-fotolink:hover { background:#d1fae5; color:#047857; }

  /* ===== responsif: tablet & HP ===== */
  @media (max-width: 991px) {
    #cb-root .cb-kpis { grid-template-columns:repeat(2,1fr); gap:10px; }
    #cb-root .cb-kpi-val { font-size:24px; }
  }
  @media (max-width: 767px) {
    #cb-root .cb-head { flex-direction:column; align-items:stretch; }
    #cb-root .cb-head-act { justify-content:space-between; }
    #cb-root .cb-btn { min-height:44px; }
    #cb-root .cb-kpis { grid-template-columns:1fr; gap:8px; }
    #cb-root .cb-btn-xls { width:100%; justify-content:center; height:44px; margin-left:0; }
    #cb-root .cb-skucell { gap:12px; }
    #cb-root .cb-thumb { width:56px; height:56px; }
    #cb-root .cb-eye { width:28px; height:28px; font-size:13px; }
    #cb-root .cb-kpi { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; }
    #cb-root .cb-kpi-val { margin:0; font-size:22px; }
    #cb-root .cb-kpi-note { display:none; }
    #cb-root .cb-body { padding:12px 12px 8px; }
    #cb-root .cb-tabs { flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch; }
    #cb-root .cb-tab { white-space:nowrap; padding:10px 12px; }
    #cb-root .cb-toolbar { flex-direction:column; align-items:stretch; }
    #cb-root .cb-search { max-width:none; flex:none; }
    #cb-root .cb-search input, #cb-root .cb-select { height:44px; font-size:16px; width:100%; }
    #cb-root .cb-ms { width:100%; }
    #cb-root .cb-chip { width:100%; height:44px; justify-content:space-between; font-size:15px; }
    #cb-root .cb-ms-panel { left:0; right:0; }
    #cb-root .cb-ms-opt { padding:12px 10px; font-size:15px; }
    #cb-root .cb-count { margin-left:0; }
    /* tabel -> kartu per SKU */
    #cb-root .cb-table-wrap { border:0; overflow:visible; background:transparent; }
    #cb-root .cb-table, #cb-root .cb-table tbody { display:block; }
    #cb-root .cb-table thead { display:none; }
    #cb-root .cb-table > tbody > tr { display:block; margin-bottom:10px; border:1px solid var(--cb-line); border-radius:12px; background:#fff; padding:6px 12px; }
    #cb-root .cb-table > tbody > tr > td { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid #f1f5f9; background:transparent !important; text-align:right; }
    #cb-root .cb-table > tbody > tr > td:last-child { border-bottom:0; }
    #cb-root .cb-table > tbody > tr > td::before { content:attr(data-label); font-size:11.5px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--cb-muted); text-align:left; flex:none; }
    #cb-root .cb-table > tbody > tr > td.cb-no { display:none; }
    #cb-root .cb-table > tbody > tr > td.cb-sku { font-size:15px; }
    #cb-root .cb-table > tbody > tr > td.cb-empty { display:block; text-align:center; }
    #cb-root .cb-table > tbody > tr > td.cb-empty::before { content:none; }
    #cb-root .cb-iconbtn, #cb-root .cb-fotolink { width:40px; height:40px; font-size:16px; }
    #cb-root .cb-qty { gap:10px; }
    #cb-root .cb-pager { justify-content:center; }
    #cb-root .cb-pg { min-width:44px; height:40px; }
    #cb-root .cb-pager select { height:40px; }
    .cb-mod .modal-dialog { margin:10px; }
    .cb-mod .modal-body { padding:16px 16px 6px; }
    .cb-mod .modal-footer { padding:12px 16px 16px; display:flex; gap:8px; }
    .cb-mod .modal-footer .cb-btn { flex:1; }
    .cb-mod .cb-input { font-size:18px; }
  }
</style>

<div id="cb-root">
  <div class="cb-head">
    <div>
      <h2 class="cb-title">Cabut SKU</h2>
      <div class="cb-syarat" aria-label="Syarat SKU tampil">
        <span class="cb-syarat-judul">Syarat tampil</span>
        <span class="cb-sy"><i class="fa fa-tags"></i>Kategori <b><?= implode(' &middot; ', array_map('htmlspecialchars', $kategori)) ?></b></span>
        <span class="cb-sy"><i class="fa fa-cubes"></i>Stok total Jubelio <b><?= (int) $stok_min ?>&ndash;<?= (int) $batas ?></b></span>
        <span class="cb-sy"><i class="fa fa-map-marker"></i>Rak Jubelio belum <b>CABUT</b> / <b>KR-</b></span>
      </div>
    </div>
    <div class="cb-head-act">
      <div class="cb-updated" id="cb-updated">&nbsp;</div>
      <button type="button" class="cb-btn" id="cb-perbarui" title="Ambil stok dan rak terbaru dari Jubelio"><i class="fa fa-refresh"></i> Perbarui</button>
    </div>
  </div>

  <div class="cb-prog" id="cb-prog"><div></div></div>

  <div class="cb-kpis">
    <div class="cb-kpi">
      <div class="cb-kpi-top"><span class="cb-kpi-ic cb-ic-indigo"><i class="fa fa-cubes"></i></span><span class="cb-kpi-label">SKU Perlu Dicabut</span></div>
      <div class="cb-kpi-val" id="cb-k-sku">&ndash;</div>
      <div class="cb-kpi-note">memenuhi semua syarat di atas</div>
    </div>
    <div class="cb-kpi">
      <div class="cb-kpi-top"><span class="cb-kpi-ic cb-ic-amber"><i class="fa fa-pencil-square-o"></i></span><span class="cb-kpi-label">Perlu Isi Qty Real</span></div>
      <div class="cb-kpi-val" id="cb-k-isi">&ndash;</div>
      <div class="cb-kpi-note">qty real belum diisi</div>
    </div>
    <div class="cb-kpi">
      <div class="cb-kpi-top"><span class="cb-kpi-ic cb-ic-red"><i class="fa fa-exchange"></i></span><span class="cb-kpi-label">Perlu Penyesuaian</span></div>
      <div class="cb-kpi-val" id="cb-k-adj">&ndash;</div>
      <div class="cb-kpi-note" id="cb-k-adj-note">qty real beda dengan stok Jubelio</div>
    </div>
    <div class="cb-kpi">
      <div class="cb-kpi-top"><span class="cb-kpi-ic cb-ic-green"><i class="fa fa-map-marker"></i></span><span class="cb-kpi-label">Perlu Isi No. Rak</span></div>
      <div class="cb-kpi-val" id="cb-k-rak">&ndash;</div>
      <div class="cb-kpi-note">qty real sama dengan stok Jubelio</div>
    </div>
  </div>

  <div class="cb-body">
    <div class="cb-tabs" id="cb-tabs">
      <button type="button" class="cb-tab aktif" data-tab="isi">Perlu Isi Qty Real<span class="cb-tab-n" id="cb-n-isi">&ndash;</span></button>
      <button type="button" class="cb-tab" data-tab="adj">Perlu Penyesuaian<span class="cb-tab-n" id="cb-n-adj">&ndash;</span></button>
      <button type="button" class="cb-tab" data-tab="rak">Perlu Isi No. Rak<span class="cb-tab-n" id="cb-n-rak">&ndash;</span></button>
    </div>
    <div class="cb-toolbar">
      <div class="cb-search"><i class="fa fa-search"></i><input type="text" id="cb-cari" placeholder="Cari SKU atau No Rak" autocomplete="off"></div>
      <div class="cb-ms" id="cb-ms">
        <button type="button" class="cb-chip" id="cb-ms-btn" aria-haspopup="true" aria-expanded="false" title="Filter kategori SKU (bisa pilih lebih dari satu)">
          <span class="cb-chip-label">Kategori</span><span class="cb-chip-count" id="cb-ms-n"></span><i class="fa fa-angle-down"></i>
        </button>
        <div class="cb-ms-panel" id="cb-ms-panel" role="group" aria-label="Pilih kategori">
          <?php foreach ($kategori as $k) : ?>
          <label class="cb-ms-opt"><input type="checkbox" class="cb-ms-cek" value="<?= htmlspecialchars($k) ?>"><span><?= htmlspecialchars($k) ?></span></label>
          <?php endforeach; ?>
          <div class="cb-ms-foot"><button type="button" id="cb-ms-reset">Reset</button></div>
        </div>
      </div>
      <span class="cb-count" id="cb-count"></span>
      <button type="button" class="cb-btn cb-btn-sec cb-btn-xls" id="cb-export" title="Unduh tab ini (sesuai filter) sebagai file Excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
    </div>
    <div class="cb-legend" aria-label="Keterangan stok Jubelio">
      <span class="cb-lg-info"><i class="fa fa-info-circle"></i>Arahkan kursor atau ketuk angka untuk melihat jenis stok</span>
      <span class="cb-lg-chips">
        <span class="cb-lg"><i class="cb-dot cb-dot-oh"></i>On Hand</span>
        <span class="cb-lg"><i class="cb-dot cb-dot-oo"></i>On Order</span>
        <span class="cb-lg"><i class="cb-dot cb-dot-rs"></i>On Reserve</span>
        <span class="cb-lg"><i class="cb-dot cb-dot-av"></i>On Available</span>
      </span>
      <span class="cb-lg-note">Transit hanya menampilkan On Available, sama seperti Jubelio.</span>
    </div>
    <div class="cb-table-wrap">
      <table class="cb-table">
        <thead id="cb-thead"></thead>
        <tbody id="cb-body"><tr><td colspan="18" class="cb-empty"><i class="fa fa-spinner fa-spin"></i>Memuat data...</td></tr></tbody>
      </table>
    </div>
    <div class="cb-pager">
      <div class="cb-pager-ctl">Tampilkan
        <select id="cb-ukuran"><option>50</option><option selected>100</option><option>250</option></select> baris</div>
      <div class="cb-pager-ctl">
        <button type="button" class="cb-pg" id="cb-prev"><i class="fa fa-angle-left"></i></button>
        <span id="cb-hal"></span>
        <button type="button" class="cb-pg" id="cb-next"><i class="fa fa-angle-right"></i></button>
      </div>
    </div>
  </div>
</div>

<!-- Pop-up qty real -->
<div class="modal fade cb-mod" id="cb-modal" role="dialog" aria-labelledby="cb-modal-judul" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width:460px;">
    <div class="modal-content">
      <form id="cb-form" class="nojs" novalidate autocomplete="off">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="cb-modal-judul">Isi Qty Real</h4>
        </div>
        <div class="modal-body">
          <dl class="cb-info">
            <div><dt>SKU</dt><dd id="cb-m-sku">-</dd></div>
            <div><dt>Stok Jubelio</dt><dd id="cb-m-stok">-</dd></div>
          </dl>
          <label class="cb-label" for="cb-m-qty">Qty real <span class="cb-req">*</span></label>
          <input type="text" class="cb-input" id="cb-m-qty" inputmode="numeric" maxlength="9" placeholder="Contoh: 9">
          <p class="cb-hint">Hasil hitung fisik di rak. Hanya bilangan bulat, tanpa koma atau titik.</p>
          <p class="cb-err" id="cb-m-err"></p>
          <label class="cb-label" style="margin-top:8px">Foto bukti <span class="cb-req">*</span></label>
          <input type="file" id="cb-m-foto" accept="image/jpeg,image/png,image/webp" style="display:none">
          <div class="cb-fotobox" id="cb-m-fotobox">
            <button type="button" class="cb-btn cb-btn-sec" id="cb-m-pilihfoto"><i class="fa fa-camera"></i> Ambil / pilih foto</button>
            <img id="cb-m-preview" alt="Pratinjau foto">
            <span class="cb-fotonama" id="cb-m-fotonama"></span>
          </div>
          <p class="cb-hint" id="cb-m-fotohint">Foto hitungan fisik di rak. JPG, PNG, atau WebP; otomatis dikecilkan sebelum dikirim.</p>
          <p class="cb-err" id="cb-m-foto-err"></p>
          <p class="cb-srv" id="cb-m-srv"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="cb-btn cb-btn-sec" data-dismiss="modal">Batal</button>
          <button type="submit" class="cb-btn" id="cb-m-simpan"><i class="fa fa-save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Pop-up no rak tujuan (Pindah ke) -->
<div class="modal fade cb-mod" id="cb-rakmodal" role="dialog" aria-labelledby="cb-rak-judul" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width:440px;">
    <div class="modal-content">
      <form id="cb-rakform" class="nojs" novalidate autocomplete="off">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="cb-rak-judul">Isi No. Rak Tujuan</h4>
        </div>
        <div class="modal-body">
          <dl class="cb-info">
            <div><dt>SKU</dt><dd id="cb-r-sku">-</dd></div>
            <div><dt>Qty Real</dt><dd id="cb-r-qty">-</dd></div>
          </dl>
          <label class="cb-label" for="cb-r-rak">Pindah ke (no. rak) <span class="cb-req">*</span></label>
          <input type="text" class="cb-input" id="cb-r-rak" maxlength="50" placeholder="Contoh: KR-12/A" autocapitalize="characters" spellcheck="false">
          <p class="cb-hint">Tanpa spasi. Hanya huruf, angka, tanda - dan /.</p>
          <p class="cb-err" id="cb-r-err"></p>
          <p class="cb-srv" id="cb-r-srv"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="cb-btn cb-btn-sec" data-dismiss="modal">Batal</button>
          <button type="submit" class="cb-btn" id="cb-r-simpan"><i class="fa fa-save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Pop-up gambar penuh -->
<div class="modal fade cb-mod cb-lb" id="cb-lb" role="dialog" aria-labelledby="cb-lb-judul" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="cb-lb-judul">Gambar SKU</h4>
      </div>
      <div class="modal-body"><img id="cb-lb-img" alt="Gambar SKU"></div>
    </div>
  </div>
</div>

<script>
(function () {
  var semua = [], tampil = [], hal = 1, sedang = false, kat = [], tab = 'isi', skuAktif = null;
  var RE_BULAT = /^[0-9]+$/;
  var FOTO_URL = '<?= htmlspecialchars($foto_url, ENT_QUOTES) ?>';

  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function escAttr(s) { return esc(s).replace(/"/g, '&quot;'); }
  function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
  function fmtWaktu(t) { return t ? t.replace(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}).*/, '$3/$2/$1 $4') : ''; }
  function pil(k) {
    var c = k === 'EOL' ? 'eol' : (k === 'WP EOL' ? 'wp' : (k === 'CUT' ? 'cut' : 'lain'));
    return '<span class="cb-pill cb-pill-' + c + '">' + esc(k) + '</span>';
  }

  // ---------- ringkasan & tab ----------
  function terisi(r) { return r.qty_real !== null && r.qty_real !== undefined; }
  /** Perlu penyesuaian: qty real sudah diisi dan beda dengan stok total Jubelio. */
  function perluAdj(r) { return terisi(r) && parseInt(r.qty_real, 10) !== parseInt(r.stok_total, 10); }

  /** Tab tempat baris berada (sama dengan Cabut_sku_fcd::tab_baris): isi / adj / rak. */
  function tabBaris(r) { return !terisi(r) ? 'isi' : (perluAdj(r) ? 'adj' : 'rak'); }
  /** Empat sel stok (On Hand, On Order, On Reserve, On Available) satu gudang; kosong = belum pernah diambil dari Jubelio. */
  function sel4(nama, oh, oo, rs, av) {
    var k = [['cb-m-oh', 'On Hand', oh], ['cb-m-oo', 'On Order', oo], ['cb-m-rs', 'On Reserve', rs], ['cb-m-av', 'On Available', av]], h = '';
    k.forEach(function (x, n) {
      h += '<td class="cb-m ' + x[0] + (n === 0 ? ' cb-mg' : '') + '" data-label="' + nama + ' \u00b7 ' + x[1] + '" data-tip="' + nama + ' \u00b7 ' + x[1] + '">' + angka(x[2]) + '</td>';
    });
    return h;
  }
  function angka(v) { return v === null || v === undefined ? '<span class="cb-kosong">&ndash;</span>' : esc(v); }

  function ringkasan() {
    var isi = 0, adj = 0, rak = 0, selisih = 0;
    semua.forEach(function (r) {
      var t = tabBaris(r);
      if (t === 'isi') { isi++; }
      else if (t === 'adj') { adj++; selisih += Math.abs(parseInt(r.qty_real, 10) - parseInt(r.stok_total, 10)); }
      else if (!r.pindah_ke) { rak++; } // tab rak: yang masih perlu diisi
    });
    $('#cb-k-rak').text(fmt(rak));
    $('#cb-n-rak').text(fmt(rak)).toggleClass('ada', rak > 0);
    $('#cb-k-sku').text(fmt(semua.length));
    $('#cb-k-isi').text(fmt(isi));
    $('#cb-k-adj').text(fmt(adj));
    $('#cb-k-adj-note').text(adj ? 'total selisih ' + fmt(selisih) + ' pcs' : 'qty real beda dengan stok Jubelio');
    $('#cb-n-isi').text(fmt(isi)).toggleClass('ada', isi > 0);
    $('#cb-n-adj').text(fmt(adj)).toggleClass('ada', adj > 0);
  }

  // ---------- tabel ----------
  function kepala() {
    var r1 = '<th rowspan="2" style="width:56px">#</th><th rowspan="2">SKU</th><th rowspan="2">Kategori SKU</th><th rowspan="2">No Rak</th>' + (tab === 'rak' ? '<th rowspan="2">Pindah ke</th>' : '') +
      '<th colspan="13" class="cb-hc cb-hroot">Stok Jubelio</th><th rowspan="2" class="cb-num">Qty Real</th>';
    if (tab === 'adj') { r1 += '<th rowspan="2" class="cb-num">Selisih</th>'; }
    var r2 = '<th colspan="4" class="cb-hc cb-hg">Display Barang (F19&amp;F19A)</th><th colspan="4" class="cb-hc cb-hg">Gudang Utama (F16)</th>' +
      '<th class="cb-hc cb-hg">Transit</th><th colspan="4" class="cb-hc cb-hg">Total Stok</th>';
    $('#cb-thead').html('<tr>' + r1 + '</tr><tr>' + r2 + '</tr>');
    return tab === 'isi' ? 18 : 19;
  }

  /** Gambar SKU kecil + ikon mata (buka gambar penuh); tanpa foto = kotak kosong. */
  function thumb(r) {
    var src = r.foto || r.foto_lokal;
    if (!src) { return '<span class="cb-thumb" title="Belum ada foto"><i class="fa fa-picture-o"></i></span>'; }
    return '<span class="cb-thumb"><img src="' + escAttr(src) + '"' + (r.foto && r.foto_lokal ? ' data-foto-lokal="' + escAttr(r.foto_lokal) + '"' : '') + ' alt="" loading="lazy">' +
      '<button type="button" class="cb-eye" data-sku="' + escAttr(r.id_sku) + '" title="Lihat gambar penuh" aria-label="Lihat gambar penuh"><i class="fa fa-eye"></i></button></span>';
  }

  function render() {
    var q = $.trim($('#cb-cari').val()).toLowerCase(), ukuran = parseInt($('#cb-ukuran').val(), 10) || 100, kolom = kepala();
    tampil = semua.filter(function (r) {
      if (tabBaris(r) !== tab) { return false; }
      if (kat.length && kat.indexOf(r.kategori_sku) === -1) { return false; }
      return !q || (r.id_sku + ' ' + (r.rak_jubelio || '') + ' ' + (r.pindah_ke || '')).toLowerCase().indexOf(q) !== -1;
    });
    if (tab === 'rak') { // yang belum diisi dulu (sort stabil: urutan stok tetap)
      tampil = tampil.map(function (r, i) { return { r: r, i: i }; })
        .sort(function (a, b) { return (!!a.r.pindah_ke - !!b.r.pindah_ke) || (a.i - b.i); })
        .map(function (x) { return x.r; });
    }
    var total = Math.max(1, Math.ceil(tampil.length / ukuran));
    if (hal > total) { hal = total; }
    var awal = (hal - 1) * ukuran, html = '';
    tampil.slice(awal, awal + ukuran).forEach(function (r, i) {
      var qty = terisi(r)
        ? '<span class="cb-qty-val" title="' + escAttr('Diisi ' + (r.qty_real_oleh || '-') + ' · ' + fmtWaktu(r.qty_real_at)) + '">' + esc(r.qty_real) + '</span>'
        : '<span class="cb-kosong">&ndash;</span>';
      var sel = '';
      if (tab === 'adj') {
        var d = parseInt(r.qty_real, 10) - parseInt(r.stok_total, 10);
        sel = '<td data-label="Selisih" class="cb-num ' + (d > 0 ? 'cb-selisih-plus' : 'cb-selisih-min') + '">' + (d > 0 ? '+' : '') + d + '</td>';
      }
      var foto = r.qty_real_foto
        ? '<a class="cb-fotolink" href="' + escAttr(FOTO_URL + r.qty_real_foto) + '" target="_blank" rel="noopener" title="Lihat foto bukti"><i class="fa fa-camera"></i></a>' : '';
      var pindah = '';
      if (tab === 'rak') {
        pindah = '<td data-label="Pindah ke"><span class="cb-pindah">' +
          (r.pindah_ke ? '<span class="cb-pindah-val" title="' + escAttr('Diisi ' + (r.pindah_ke_oleh || '-') + ' \u00b7 ' + fmtWaktu(r.pindah_ke_at)) + '">' + esc(r.pindah_ke) + '</span>' : '<span class="cb-kosong">&ndash;</span>') +
          '<button type="button" class="cb-iconbtn cb-rakbtn" data-sku="' + escAttr(r.id_sku) + '" title="' + (r.pindah_ke ? 'Ubah' : 'Isi') + ' no. rak tujuan"><i class="fa fa-pencil"></i></button></span></td>';
      }
      html += '<tr><td class="cb-kosong cb-no">' + (awal + i + 1) + '</td>' +
        '<td class="cb-sku" data-label="SKU"><span class="cb-skucell">' + thumb(r) + '<span>' + esc(r.id_sku) + '<button type="button" class="cb-copy" data-sku="' + escAttr(r.id_sku) + '" title="Salin SKU" aria-label="Salin SKU ' + escAttr(r.id_sku) + '"><i class="fa fa-clipboard"></i></button></span></span></td>' +
        '<td data-label="Kategori SKU">' + pil(r.kategori_sku) + '</td>' +
        '<td class="cb-rak" data-label="No Rak">' + (r.rak_jubelio ? esc(r.rak_jubelio) : '<span class="cb-kosong">&ndash;</span>') + '</td>' + pindah +
        sel4('Display', r.stok_display, r.disp_order, r.disp_reserved, r.disp_available) +
        sel4('Gudang', r.stok_gudang, r.gud_order, r.gud_reserved, r.gud_available) +
        '<td class="cb-m cb-m-av cb-mg" data-label="Transit \u00b7 On Available" data-tip="Transit \u00b7 On Available">' + angka(r.stok_transit) + '</td>' +
        sel4('Total', r.stok_total, r.tot_order, r.tot_reserved, r.tot_available) +
        '<td class="cb-num" data-label="Qty Real"><span class="cb-qty">' + qty + foto +
          '<button type="button" class="cb-iconbtn cb-qtybtn" data-sku="' + escAttr(r.id_sku) + '" title="' + (terisi(r) ? 'Ubah' : 'Isi') + ' qty real"><i class="fa fa-pencil"></i></button></span></td>' + sel + '</tr>';
    });
    var kosong = tab === 'isi'
      ? '<i class="fa fa-check-circle"></i>Semua SKU di daftar sudah terisi qty real. Klik Perbarui bila stok belum pernah diambil.'
      : (tab === 'adj'
        ? '<i class="fa fa-check-circle"></i>Tidak ada SKU yang perlu penyesuaian: qty real yang diisi sama dengan stok Jubelio.'
        : '<i class="fa fa-info-circle"></i>Belum ada SKU yang qty real-nya sudah diisi dan sama dengan stok Jubelio.');
    $('#cb-body').html(html || '<tr><td colspan="' + kolom + '" class="cb-empty">' + kosong + '</td></tr>');
    $('#cb-count').text(fmt(tampil.length) + ' SKU');
    $('#cb-hal').text('Halaman ' + hal + ' / ' + total);
    $('#cb-prev').prop('disabled', hal <= 1);
    $('#cb-next').prop('disabled', hal >= total);
  }

  function muat() {
    $.ajax({ url: 'cabut-sku/get-data', type: 'POST', dataType: 'json', timeout: 60000,
      success: function (r) {
        if (r.code !== 200) { $('#cb-updated').text(r.message); return; }
        var d = r.data || {}, rs = d.ringkasan || {};
        semua = d.baris || [];
        var s = rs.diperbarui ? 'Stok Jubelio diperbarui<br><strong>' + fmtWaktu(rs.diperbarui) + '</strong>' : 'Stok belum pernah diambil.<br>Klik Perbarui.';
        if (rs.belum_ada > 0) { s += '<br>' + fmt(rs.belum_ada) + ' SKU berkategori belum punya data stok'; }
        var kosong = semua.filter(function (r) { return r.stok_display === null || r.stok_gudang === null || r.stok_transit === null || r.disp_available === null || r.tot_available === null; }).length;
        if (kosong > 0) { s += '<br><strong style="color:#c2410c">' + fmt(kosong) + ' SKU belum punya rincian On Hand/Order/Reserve/Available. Klik Perbarui untuk mengisinya.</strong>'; }
        $('#cb-updated').html(s);
        ringkasan();
        render();
      },
      error: function (xhr) {
        $('#cb-updated').text('Gagal memuat data.');
        $('#cb-body').html('<tr><td colspan="18" class="cb-empty"><i class="fa fa-exclamation-triangle"></i>Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 160)) + '</td></tr>');
      }
    });
  }

  // ---------- keterangan angka stok: muncul saat di-hover atau diklik ----------
  var tipWaktu = null;
  function tipTampil(el, lama) {
    var t = $(el).attr('data-tip'); if (!t) { return; }
    var $t = $('#cb-tip'); if (!$t.length) { $t = $('<div id="cb-tip"></div>').appendTo('body'); }
    var r = el.getBoundingClientRect();
    $t.text(t).css({ display: 'block', left: 0, top: 0 });
    var w = $t.outerWidth(), h = $t.outerHeight();
    $t.css({ left: Math.max(6, Math.min(window.innerWidth - w - 6, r.left + r.width / 2 - w / 2)), top: r.top - h - 6 < 6 ? r.bottom + 6 : r.top - h - 6 });
    clearTimeout(tipWaktu);
    if (lama) { tipWaktu = setTimeout(tipTutup, lama); }
  }
  function tipTutup() { clearTimeout(tipWaktu); $('#cb-tip').hide(); }
  $('#cb-body').on('mouseenter', 'td.cb-m', function () { tipTampil(this, 0); })
    .on('mouseleave', 'td.cb-m', tipTutup)
    .on('click', 'td.cb-m', function () { tipTampil(this, 1800); });
  $('#cb-root').closest('.page-content-wrap, body').off('scroll.cbtip').on('scroll.cbtip', tipTutup);

  // ---------- salin SKU ----------
  function salinTeks(t, ok) {
    function cadangan() { // http di LAN bukan konteks aman: navigator.clipboard tidak tersedia, pakai execCommand
      var a = document.createElement('textarea');
      a.value = t; a.setAttribute('readonly', ''); a.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
      document.body.appendChild(a); a.select();
      var b = false; try { b = document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(a); ok(b);
    }
    if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(function () { ok(true); }, cadangan); } else { cadangan(); }
  }
  $('#cb-body').on('click', '.cb-copy', function (e) {
    e.stopPropagation();
    var tb = $(this), ikon = tb.find('i');
    salinTeks(tb.attr('data-sku'), function (berhasil) {
      ikon.attr('class', berhasil ? 'fa fa-check' : 'fa fa-times'); tb.toggleClass('ok', berhasil).attr('title', berhasil ? 'Tersalin' : 'Gagal menyalin');
      setTimeout(function () { ikon.attr('class', 'fa fa-clipboard'); tb.removeClass('ok').attr('title', 'Salin SKU'); }, 1300);
    });
  });

  // ---------- perbarui dari Jubelio ----------
  /** Satu permintaan: server mengambil semua halaman Jubelio secara paralel (page size besar) lalu menyimpannya. */
  function ambilSemua() {
    $('#cb-prog > div').css('width', '100%');
    $.ajax({ url: 'cabut-sku/perbarui', type: 'POST', dataType: 'json', timeout: 200000,
      success: function (r) {
        if (r.code !== 200) { selesai('Gagal memperbarui: ' + r.message); return; }
        selesai(null);
      },
      error: function (xhr, st) { selesai('Gagal memperbarui: ' + (st === 'timeout' ? 'waktu habis' : (xhr.responseText || '').substring(0, 160))); }
    });
  }

  function selesai(galat) {
    sedang = false;
    $('#cb-perbarui').prop('disabled', false);
    $('#cb-prog').hide();
    muat();
    if (galat) { setTimeout(function () { $('#cb-updated').text(galat + ' (data yang sudah terambil tetap tersimpan)'); }, 400); }
  }

  // ---------- pop-up qty real ----------
  function cariRow(sku) { for (var i = 0; i < semua.length; i++) { if (semua[i].id_sku === sku) { return semua[i]; } } return null; }

  /** Pesan galat untuk isian qty real, atau '' bila valid (bilangan bulat >= 0, hanya digit). */
  function cekQty(v) {
    v = $.trim(v);
    if (v === '') { return 'Qty real wajib diisi.'; }
    if (/[.,]/.test(v)) { return 'Tidak boleh desimal. Isi bilangan bulat, contoh: 9.'; }
    if (!RE_BULAT.test(v)) { return 'Hanya boleh angka bulat (0\u20139). Huruf, spasi, atau tanda lain ditolak.'; }
    return '';
  }

  var TIPE_FOTO = ['image/jpeg', 'image/png', 'image/webp'], MAKS_ASLI = 20 * 1024 * 1024, MAKS_KIRIM = 5 * 1024 * 1024, SISI_MAKS = 1600;
  var fotoBlob = null, fotoUrl = null, qtyGalat = '', fotoGalat = '', menyimpan = false;

  /** Tombol Simpan aktif hanya bila qty valid DAN foto valid sudah dipilih. */
  function segarkan() {
    var kosong = $.trim($('#cb-m-qty').val()) === '';
    $('#cb-m-simpan').prop('disabled', menyimpan || qtyGalat !== '' || fotoGalat !== '' || kosong || !fotoBlob);
  }
  function tampilGalat(pesan) {
    qtyGalat = pesan;
    $('#cb-m-err').text(pesan);
    $('#cb-m-qty').toggleClass('salah', pesan !== '');
    segarkan();
  }
  function tampilGalatFoto(pesan) {
    fotoGalat = pesan;
    $('#cb-m-foto-err').text(pesan);
    $('#cb-m-fotobox').toggleClass('salah', pesan !== '');
    segarkan();
  }
  function resetFoto() {
    if (fotoUrl) { URL.revokeObjectURL(fotoUrl); fotoUrl = null; }
    fotoBlob = null;
    $('#cb-m-foto').val('');
    $('#cb-m-preview').hide().attr('src', '');
    $('#cb-m-fotonama').text('');
    tampilGalatFoto('');
  }

  /** Perkecil foto (maks 1600 px, JPEG) supaya ringan dikirim lewat HP. cb(blob, galat, urlPratinjau). */
  function kompres(f, cb) {
    var url = URL.createObjectURL(f), img = new Image();
    img.onload = function () {
      var w = img.naturalWidth, h = img.naturalHeight;
      if (w < 200 || h < 200) { URL.revokeObjectURL(url); cb(null, 'Foto terlalu kecil (minimal 200 x 200 piksel).'); return; }
      var skala = Math.min(1, SISI_MAKS / Math.max(w, h));
      if (skala === 1 && f.size <= MAKS_KIRIM && f.type === 'image/jpeg') { cb(f, null, url); return; }
      var c = document.createElement('canvas'); c.width = Math.round(w * skala); c.height = Math.round(h * skala);
      var ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height); ctx.drawImage(img, 0, 0, c.width, c.height);
      var selesaiBlob = function (b) {
        if (!b) { URL.revokeObjectURL(url); cb(null, 'Foto tidak bisa diproses di browser ini.'); return; }
        if (b.size > MAKS_KIRIM) { URL.revokeObjectURL(url); cb(null, 'Ukuran foto masih terlalu besar setelah dikecilkan (maks 5 MB).'); return; }
        cb(b, null, url);
      };
      if (c.toBlob) { c.toBlob(selesaiBlob, 'image/jpeg', 0.82); } else { selesaiBlob(null); }
    };
    img.onerror = function () { URL.revokeObjectURL(url); cb(null, 'Berkas bukan foto yang valid atau tidak bisa dibuka.'); };
    img.src = url;
  }

  $('#cb-body').on('click', '.cb-qtybtn', function () {
    var sku = $(this).attr('data-sku'), r = cariRow(sku);
    if (!r) { return; }
    skuAktif = sku;
    $('#cb-m-sku').text(r.id_sku);
    $('#cb-m-stok').text(r.stok_total);
    $('#cb-m-qty').val(r.qty_real !== null && r.qty_real !== undefined ? r.qty_real : '');
    $('#cb-m-srv').text('');
    $('#cb-m-fotohint').text(r.qty_real_foto
      ? 'Sudah ada foto sebelumnya. Mengubah qty real tetap wajib mengunggah foto baru.'
      : 'Foto hitungan fisik di rak. JPG, PNG, atau WebP; otomatis dikecilkan sebelum dikirim.');
    resetFoto();
    tampilGalat('');
    $('#cb-modal').modal('show');
  });
  $('#cb-modal').on('shown.bs.modal', function () { $('#cb-m-qty').trigger('focus').select(); });
  $('#cb-modal').on('hidden.bs.modal', function () { resetFoto(); });
  // validasi langsung saat mengetik / menempel; field kosong tidak menampilkan galat sampai disimpan
  $('#cb-m-qty').on('input paste keyup', function () {
    var el = this;
    setTimeout(function () { var v = $.trim(el.value); tampilGalat(v === '' ? '' : cekQty(v)); }, 0);
  });

  $('#cb-m-pilihfoto').on('click', function () { $('#cb-m-foto').trigger('click'); });
  $('#cb-m-foto').on('change', function () {
    var f = this.files[0];
    if (!f) { resetFoto(); return; }
    if (fotoUrl) { URL.revokeObjectURL(fotoUrl); fotoUrl = null; }
    fotoBlob = null; $('#cb-m-preview').hide();
    if (TIPE_FOTO.indexOf(f.type) === -1) { tampilGalatFoto('Format foto harus JPG, PNG, atau WebP.'); return; }
    if (f.size === 0) { tampilGalatFoto('Berkas foto kosong (0 KB).'); return; }
    if (f.size > MAKS_ASLI) { tampilGalatFoto('Ukuran foto ' + (f.size / 1048576).toFixed(1) + ' MB terlalu besar (maks 20 MB).'); return; }
    tampilGalatFoto('');
    $('#cb-m-fotonama').text('Memproses foto...');
    kompres(f, function (blob, galat, url) {
      if (galat) { $('#cb-m-fotonama').text(''); tampilGalatFoto(galat); return; }
      fotoBlob = blob; fotoUrl = url;
      $('#cb-m-preview').attr('src', url).show();
      $('#cb-m-fotonama').text(f.name + ' \u00b7 ' + Math.max(1, Math.round(blob.size / 1024)) + ' KB');
      tampilGalatFoto('');
    });
  });

  $('#cb-form').on('submit', function (e) {
    e.preventDefault();
    if (menyimpan) { return; }
    var v = $.trim($('#cb-m-qty').val()), salah = cekQty(v);
    if (salah) { tampilGalat(salah); return; }
    if (!fotoBlob) { tampilGalatFoto(fotoGalat || 'Foto wajib diunggah sebagai bukti qty real.'); return; }
    menyimpan = true; segarkan();
    $('#cb-m-simpan').html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('#cb-m-srv').text('');
    var fd = new FormData(); fd.append('sku', skuAktif); fd.append('qty_real', v); fd.append('foto', fotoBlob, 'foto.jpg');
    $.ajax({ url: 'cabut-sku/simpan-qty-real', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json', timeout: 60000,
      success: function (r) {
        if (r.code !== 200) { $('#cb-m-srv').text(r.message); return; }
        var row = cariRow(skuAktif);
        if (row) { row.qty_real = r.data.qty_real; row.qty_real_oleh = r.data.oleh; row.qty_real_at = r.data.waktu; row.qty_real_foto = r.data.foto; }
        $('#cb-modal').modal('hide');
        ringkasan();
        render();
      },
      error: function (xhr, st) { $('#cb-m-srv').text(st === 'timeout' ? 'Waktu habis. Periksa sinyal lalu coba lagi.' : 'Gagal menyimpan. ' + (xhr.responseText || '').substring(0, 120)); },
      complete: function () { menyimpan = false; $('#cb-m-simpan').html('<i class="fa fa-save"></i> Simpan'); segarkan(); }
    });
  });

  // ---------- gambar penuh ----------
  $('#cb-body').on('click', '.cb-eye', function () {
    var r = cariRow($(this).attr('data-sku'));
    if (!r) { return; }
    var img = $('#cb-lb-img');
    img.removeAttr('data-foto-lokal');
    if (r.foto && r.foto_lokal) { img.attr('data-foto-lokal', r.foto_lokal); }
    img.attr('src', r.foto || r.foto_lokal);
    $('#cb-lb-judul').text(r.id_sku);
    $('#cb-lb').modal('show');
  });
  $('#cb-lb').on('hidden.bs.modal', function () { $('#cb-lb-img').attr('src', ''); });

  // ---------- pop-up no rak tujuan (Pindah ke) ----------
  var rakAktif = null, rakGalat = '', rakSimpan = false;
  /** Pesan galat untuk no rak, atau '' bila valid: tanpa spasi, hanya huruf/angka/-/ /, maks 50. */
  function cekRak(v) {
    if (v === '') { return 'No rak wajib diisi.'; }
    if (/\s/.test(v)) { return 'No rak tidak boleh mengandung spasi.'; }
    if (!/^[A-Za-z0-9\/-]+$/.test(v)) { return 'Hanya boleh huruf, angka, tanda - dan /.'; }
    if (v.length > 50) { return 'Maksimal 50 karakter.'; }
    return '';
  }
  function segarkanRak() {
    var v = $('#cb-r-rak').val();
    $('#cb-r-simpan').prop('disabled', rakSimpan || v === '' || rakGalat !== '');
  }
  function tampilGalatRak(pesan) {
    rakGalat = pesan;
    $('#cb-r-err').text(pesan);
    $('#cb-r-rak').toggleClass('salah', pesan !== '');
    segarkanRak();
  }
  $('#cb-body').on('click', '.cb-rakbtn', function () {
    var r = cariRow($(this).attr('data-sku'));
    if (!r) { return; }
    rakAktif = r.id_sku;
    $('#cb-r-sku').text(r.id_sku);
    $('#cb-r-qty').text(r.qty_real);
    $('#cb-r-rak').val(r.pindah_ke || '');
    $('#cb-r-srv').text('');
    tampilGalatRak('');
    $('#cb-rakmodal').modal('show');
  });
  $('#cb-rakmodal').on('shown.bs.modal', function () { $('#cb-r-rak').trigger('focus').select(); });
  // validasi langsung saat mengetik / menempel; field kosong tidak menampilkan galat sampai disimpan
  $('#cb-r-rak').on('input paste keyup', function () {
    var el = this;
    setTimeout(function () { tampilGalatRak(el.value === '' ? '' : cekRak(el.value)); }, 0);
  });
  $('#cb-rakform').on('submit', function (e) {
    e.preventDefault();
    if (rakSimpan) { return; }
    var v = $('#cb-r-rak').val(), salah = cekRak(v);
    if (salah) { tampilGalatRak(salah); return; }
    rakSimpan = true; segarkanRak();
    $('#cb-r-simpan').html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('#cb-r-srv').text('');
    $.ajax({ url: 'cabut-sku/simpan-pindah-ke', type: 'POST', dataType: 'json', data: { sku: rakAktif, rak: v }, timeout: 60000,
      success: function (r) {
        if (r.code !== 200) { $('#cb-r-srv').text(r.message); return; }
        var row = cariRow(rakAktif);
        if (row) { row.pindah_ke = r.data.pindah_ke; row.pindah_ke_oleh = r.data.oleh; row.pindah_ke_at = r.data.waktu; }
        if (row && row.rak_jubelio && String(row.rak_jubelio).replace(/^\s+|\s+$/g, '').toUpperCase() === String(row.pindah_ke).toUpperCase()) {
          semua.splice(semua.indexOf(row), 1); // no. rak sudah sama dengan tujuan: selesai pindah, hilang dari daftar
        }
        $('#cb-rakmodal').modal('hide');
        ringkasan();
        render();
      },
      error: function (xhr, st) { $('#cb-r-srv').text(st === 'timeout' ? 'Waktu habis. Periksa sinyal lalu coba lagi.' : 'Gagal menyimpan. ' + (xhr.responseText || '').substring(0, 120)); },
      complete: function () { rakSimpan = false; $('#cb-r-simpan').html('<i class="fa fa-save"></i> Simpan'); segarkanRak(); }
    });
  });

  // ---------- export Excel (tab aktif + filter layar) ----------
  $('#cb-export').on('click', function () {
    var tombol = $(this);
    if (!tampil.length) { alert('Tidak ada data di tab ini untuk diekspor.'); return; }
    var url = 'cabut-sku/export?' + $.param({ tab: tab, q: $.trim($('#cb-cari').val()), kat: kat });
    tombol.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan...');
    var x = new XMLHttpRequest();
    x.open('GET', url); x.responseType = 'blob'; x.timeout = 120000;
    var pulih = function () { tombol.prop('disabled', false).html('<i class="fa fa-file-excel-o"></i> Export Excel'); };
    x.onload = function () {
      pulih();
      var tipe = x.getResponseHeader('Content-Type') || '';
      if (x.status !== 200 || tipe.indexOf('spreadsheetml') === -1) { alert('Export gagal. Muat ulang halaman lalu coba lagi.'); return; }
      var m = /filename="([^"]+)"/.exec(x.getResponseHeader('Content-Disposition') || '');
      var a = document.createElement('a');
      a.href = URL.createObjectURL(x.response); a.download = m ? m[1] : 'cabut-sku.xlsx';
      document.body.appendChild(a); a.click();
      setTimeout(function () { URL.revokeObjectURL(a.href); document.body.removeChild(a); }, 1000);
    };
    x.onerror = x.ontimeout = function () { pulih(); alert('Export gagal: koneksi bermasalah atau waktu habis.'); };
    x.send();
  });

  // ---------- kontrol ----------
  $('#cb-perbarui').on('click', function () {
    if (sedang) { return; }
    sedang = true;
    $(this).prop('disabled', true);
    $('#cb-prog').show().children().css('width', '0');
    $('#cb-updated').text('Mengambil semua stok dari Jubelio (paralel)...');
    ambilSemua();
  });
  // filter kategori multi-pilih: tanpa centang = semua kategori
  function segarkanKategori() {
    kat = $('#cb-ms .cb-ms-cek:checked').map(function () { return this.value; }).get();
    $('#cb-ms').toggleClass('aktif', kat.length > 0);
    $('#cb-ms-n').text(kat.length || '');
    hal = 1; render();
  }
  function tutupKategori() { $('#cb-ms').removeClass('buka'); $('#cb-ms-btn').attr('aria-expanded', 'false'); }
  $('#cb-ms-btn').on('click', function (e) {
    e.stopPropagation();
    var buka = !$('#cb-ms').hasClass('buka');
    $('#cb-ms').toggleClass('buka', buka); $(this).attr('aria-expanded', buka ? 'true' : 'false');
  });
  $('#cb-ms-panel').on('click', function (e) { e.stopPropagation(); });
  $('#cb-ms').on('change', '.cb-ms-cek', segarkanKategori);
  $('#cb-ms-reset').on('click', function () { $('#cb-ms .cb-ms-cek').prop('checked', false); segarkanKategori(); });
  // klik di luar / Esc menutup; handler dokumen diberi namespace agar tidak menumpuk saat halaman dimuat ulang (SPA)
  $(document).off('click.cbms').on('click.cbms', tutupKategori);
  $(document).off('keydown.cbms').on('keydown.cbms', function (e) { if (e.key === 'Escape') { tutupKategori(); } });
  $('#cb-tabs').on('click', '.cb-tab', function () {
    tab = $(this).attr('data-tab');
    $('#cb-tabs .cb-tab').removeClass('aktif');
    $(this).addClass('aktif');
    hal = 1; render();
  });
  $('#cb-cari').on('input', function () { hal = 1; render(); });
  $('#cb-ukuran').on('change', function () { hal = 1; render(); });
  $('#cb-prev').on('click', function () { hal--; render(); });
  $('#cb-next').on('click', function () { hal++; render(); });
  muat();
})();
</script>
