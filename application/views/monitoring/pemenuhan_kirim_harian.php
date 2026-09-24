<?php
/*
 * Pemenuhan Kirim Harian (Tim Monitoring).
 * Data dihitung Pemenuhan_kirim_fcd::snapshot(); halaman ini hanya menampilkan
 * dan menghitung beban per packer di browser. Aturan: docs/PEMENUHAN_KIRIM_HARIAN.md.
 * Semua gaya dikurung di bawah .pkh supaya tidak bocor ke menu lain (halaman
 * dimuat lewat AJAX ke .page-content-wrap, CSS-nya tetap tinggal di dokumen).
 */
?>
<style>
.pkh{--pkh-ground:#eef1f6;--pkh-surface:#fff;--pkh-surface-2:#f5f7fa;--pkh-track:#e4e9f1;
  --pkh-ink:#0a2345;--pkh-text:#1d2939;--pkh-muted:#5b6b82;--pkh-line:#dfe4ec;--pkh-line-strong:#c4cedb;
  --pkh-accent:#1463c9;--pkh-accent-soft:#e4eefc;
  --pkh-ok:#227a3a;--pkh-ok-soft:#e3f3e7;--pkh-warn:#9a5200;--pkh-warn-soft:#fdf0db;--pkh-bad:#c42b2b;--pkh-bad-soft:#fde6e4;
  max-width:1120px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1fr);gap:16px;
  color:var(--pkh-text);font:15px/1.5 Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-variant-numeric:tabular-nums}
.pkh *{box-sizing:border-box}
.pkh > *{min-width:0}
.pkh h1,.pkh h2{font-family:Outfit,Inter,system-ui,sans-serif;color:var(--pkh-ink);margin:0}
.pkh button,.pkh input{font:inherit;color:inherit}
.pkh :focus-visible{outline:2px solid var(--pkh-accent);outline-offset:2px;border-radius:4px}
.pkh .num{font-family:Outfit,Inter,system-ui,sans-serif;font-weight:600;color:var(--pkh-ink);letter-spacing:-.01em;line-height:1}

/* kepala */
.pkh-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:10px 16px}
.pkh-head h1{font-size:28px;font-weight:600;line-height:1.15}
.pkh-sub{color:var(--pkh-muted);margin-top:4px}
.pkh-sub b{color:var(--pkh-ink)}
.pkh-ctrl{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pkh-ctrl input[type=date]{height:36px;border:1px solid var(--pkh-line-strong);border-radius:8px;padding:0 10px;background:var(--pkh-surface)}
.pkh-btn{height:36px;border:1px solid var(--pkh-line-strong);background:var(--pkh-surface);border-radius:8px;padding:0 12px;cursor:pointer;font-weight:500;display:inline-flex;align-items:center;gap:6px}
.pkh-btn:hover{border-color:var(--pkh-ink)}
.pkh-btn[disabled]{opacity:.6;cursor:wait}
.pkh-alert{border-radius:10px;padding:12px 16px;background:var(--pkh-bad-soft);color:var(--pkh-bad);font-weight:500}
.pkh-alert[hidden]{display:none}

/* kartu */
.pkh-card{background:var(--pkh-surface);border:1px solid var(--pkh-line);border-radius:12px;padding:18px 20px}
.pkh-label{display:flex;align-items:center;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--pkh-muted)}
.pkh-top{display:grid;grid-template-columns:1fr 1.25fr;gap:14px}
.pkh-top .num{font-size:52px;margin-top:6px}
.pkh-note{color:var(--pkh-muted);margin-top:8px;font-size:14px}
.pkh-note b{color:var(--pkh-ink)}
.pkh-parts{list-style:none;margin:12px 0 0;padding:0;display:grid;gap:4px;font-size:14px}
.pkh-parts li{display:flex;justify-content:space-between;gap:12px;border-bottom:1px dashed var(--pkh-line);padding:3px 0}
.pkh-parts li:last-child{border-bottom:0}
.pkh-parts b{color:var(--pkh-ink)}
.pkh-stages{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.pkh-stage.key{border:2px solid var(--pkh-accent);padding:17px 19px}
.pkh-stage .row-n{display:flex;align-items:baseline;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:8px}
.pkh-stage .num{font-size:38px}
.pkh-stage .pct{font-weight:700;color:var(--pkh-ink);font-size:18px}
.pkh-bar{height:10px;background:var(--pkh-track);border-radius:99px;overflow:hidden;margin-top:10px}
.pkh-bar i{display:block;height:100%;background:var(--pkh-accent);border-radius:99px;transition:width .35s ease}
.pkh-stage .left{margin-top:10px;font-size:14px;color:var(--pkh-muted)}
.pkh-stage .left b{color:var(--pkh-ink);font-size:16px}
.pkh-stage .left-row{display:flex;align-items:center;justify-content:space-between;gap:6px 10px;flex-wrap:wrap;min-height:30px}
.pkh-lihat{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 11px;border-radius:8px;border:1px solid var(--pkh-line-strong);background:var(--pkh-surface);color:var(--pkh-accent);font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.pkh-lihat:hover{border-color:var(--pkh-accent);background:var(--pkh-accent-soft)}
.pkh-lihat svg{width:14px;height:14px;flex:none}

/* ikon info */
.pkh-info{position:relative;display:inline-grid;place-items:center;width:20px;height:20px;margin-left:6px;border-radius:50%;color:var(--pkh-muted);cursor:help;vertical-align:-4px;flex:none}
.pkh-info:hover,.pkh-info:focus-visible,.pkh-info.open{color:var(--pkh-accent)}
.pkh-info svg{width:16px;height:16px}
.pkh-tip{position:absolute;z-index:1030;top:calc(100% + 8px);left:-12px;width:max-content;max-width:min(320px,calc(100vw - 32px));
  background:var(--pkh-ink);color:#f3f6fb;font:400 13px/1.5 Inter,system-ui,sans-serif;letter-spacing:0;text-transform:none;text-align:left;white-space:normal;
  padding:10px 12px;border-radius:8px;box-shadow:0 8px 24px rgba(10,35,69,.22);display:none;pointer-events:none}
.pkh-tip b{font-weight:700;color:inherit}
.pkh-tip p{margin:0}
.pkh-tip p + p{margin-top:6px}
.pkh-info.show .pkh-tip,.pkh-info.open .pkh-tip{display:block}

/* bagian lipat */
.pkh details.pkh-sec{background:var(--pkh-surface);border:1px solid var(--pkh-line);border-radius:12px}
.pkh details.pkh-sec.ok{border:2px solid var(--pkh-ok)}
.pkh details.pkh-sec.warn{border:2px solid var(--pkh-warn)}
.pkh details.pkh-sec.bad{border:2px solid var(--pkh-bad)}
.pkh details.pkh-sec > summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:10px 16px;flex-wrap:wrap;padding:16px 20px;outline:none}
.pkh details.pkh-sec > summary::-webkit-details-marker{display:none}
.pkh-sum-l{display:flex;align-items:center;gap:12px;min-width:0}
.pkh-chev{flex:none;width:26px;height:26px;border-radius:7px;border:1px solid var(--pkh-line-strong);display:grid;place-items:center;color:var(--pkh-ink);transition:transform .2s ease}
.pkh-chev svg{width:12px;height:12px}
.pkh details[open] > summary .pkh-chev{transform:rotate(90deg)}
.pkh-sum-title{display:flex;align-items:center;font-family:Outfit,Inter,sans-serif;font-size:19px;font-weight:600;color:var(--pkh-ink)}
.pkh-sum-meta{color:var(--pkh-muted);font-size:14px;margin-top:1px}
.pkh-sum-meta b{color:var(--pkh-ink)}
.pkh-sum-r{display:flex;align-items:center;gap:12px;margin-left:auto}
.pkh-sum-act{font-size:13px;color:var(--pkh-accent);font-weight:600}
.pkh-sum-act::after{content:"Tampilkan"}
.pkh details[open] > summary .pkh-sum-act::after{content:"Sembunyikan"}
.pkh-sec-body{padding:0 20px 20px}

/* tabel 1 Qty / >1 Qty */
.pkh-tbl{overflow-x:auto}
.pkh table.pkh-kat{width:100%;border-collapse:collapse;font-size:16px;min-width:440px;margin:0}
.pkh-kat th{font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--pkh-muted);font-weight:700;text-align:right;padding:8px 14px;border-bottom:1px solid var(--pkh-line-strong);white-space:nowrap}
.pkh-kat th:first-child,.pkh-kat td:first-child{text-align:left}
.pkh-kat td{padding:12px 14px;border-bottom:1px solid var(--pkh-line);text-align:right;white-space:nowrap}
.pkh-kat td:first-child{font-weight:600;color:var(--pkh-ink)}
.pkh-kat .col-pack{background:var(--pkh-accent-soft)}
.pkh-kat td.col-pack{font-weight:700;color:var(--pkh-ink)}
.pkh-kat .tot td{font-weight:700;color:var(--pkh-ink);border-top:2px solid var(--pkh-line-strong);border-bottom:0}
.pkh-kat .zero{color:var(--pkh-muted);font-weight:400}

/* OT / perbantuan */
.pkh-calc{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.5fr);gap:20px 32px;align-items:start}
.pkh-inputs{display:grid;gap:18px}
.pkh-inputs .pkh-label{margin-bottom:8px}
.pkh-inputs label{margin:0;font-weight:700}
.pkh-stepper{display:inline-flex;align-items:stretch;border:1.5px solid var(--pkh-line-strong);border-radius:10px;overflow:hidden;background:var(--pkh-surface)}
.pkh-stepper button{width:48px;border:0;background:var(--pkh-surface-2);font-size:24px;line-height:1;cursor:pointer;color:var(--pkh-ink)}
.pkh-stepper button:hover{background:var(--pkh-track)}
.pkh-stepper input{width:80px;border:0;border-left:1px solid var(--pkh-line);border-right:1px solid var(--pkh-line);text-align:center;font-family:Outfit,Inter,sans-serif;font-weight:600;font-size:32px;color:var(--pkh-ink);background:transparent;padding:6px 0;-moz-appearance:textfield;appearance:textfield}
.pkh-stepper input::-webkit-outer-spin-button,.pkh-stepper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.pkh-per .num{font-size:60px}
.pkh-per-row{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-top:6px}
.pkh-per .of{font-size:18px;font-weight:500;color:var(--pkh-muted)}
.pkh-load{position:relative;height:12px;background:var(--pkh-track);border-radius:99px;margin-top:14px;overflow:hidden}
.pkh-load i{display:block;height:100%;border-radius:99px;background:var(--pkh-ok);transition:width .25s ease}
.pkh-load i.warn{background:var(--pkh-warn)}
.pkh-load i.bad{background:var(--pkh-bad)}
.pkh-load .cap-mark{position:absolute;left:85%;top:0;bottom:0;border-left:2px solid var(--pkh-surface)}
.pkh-per .cap{margin-top:8px}
.pkh-per .cap b{color:var(--pkh-ink)}
.pkh-incl-row{display:flex;align-items:center;gap:4px;margin-top:18px}
.pkh-incl{display:inline-flex;gap:8px;align-items:center;font-size:14px;cursor:pointer;margin:0;font-weight:400}
.pkh-incl input{width:16px;height:16px;margin:0;accent-color:var(--pkh-accent)}
.pkh-action{margin-top:14px;border-radius:10px;padding:14px 16px;font-size:17px;line-height:1.45}
.pkh-action b{font-weight:700}
.pkh-action.ok{background:var(--pkh-ok-soft);color:var(--pkh-ok)}
.pkh-action.warn{background:var(--pkh-warn-soft);color:var(--pkh-warn)}
.pkh-action.bad{background:var(--pkh-bad-soft);color:var(--pkh-bad)}
.pkh-action span{display:block;font-size:14px;color:var(--pkh-text);margin-top:2px}
.pkh-pill{display:inline-flex;align-items:center;gap:7px;font-weight:700;font-size:14px;padding:4px 12px;border-radius:999px;white-space:nowrap}
.pkh-pill::before{content:"";width:8px;height:8px;border-radius:50%;background:currentColor}
.pkh-pill.ok{background:var(--pkh-ok-soft);color:var(--pkh-ok)}
.pkh-pill.warn{background:var(--pkh-warn-soft);color:var(--pkh-warn)}
.pkh-pill.bad{background:var(--pkh-bad-soft);color:var(--pkh-bad)}

/* popup Lihat detail */
.pkh-dlg{border:0;padding:0;border-radius:14px;width:min(1120px,calc(100vw - 32px));max-width:none;max-height:calc(100vh - 32px);background:var(--pkh-surface);color:var(--pkh-text);box-shadow:0 18px 50px rgba(10,35,69,.25);overflow:hidden}
.pkh-dlg[open]{display:flex;flex-direction:column}
.pkh-dlg::backdrop{background:rgba(10,35,69,.45)}
.pkh-m-head{padding:18px 20px 12px;border-bottom:1px solid var(--pkh-line);display:grid;gap:12px}
.pkh-m-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.pkh-m-top h2{font-size:22px;font-weight:600}
.pkh-m-sub{color:var(--pkh-muted);font-size:13.5px;margin-top:2px}
.pkh-x{width:36px;height:36px;border-radius:9px;border:1px solid var(--pkh-line-strong);background:var(--pkh-surface);cursor:pointer;display:grid;place-items:center;flex:none;padding:0}
.pkh-x:hover{border-color:var(--pkh-ink)}
.pkh-x svg{width:14px;height:14px}
.pkh-tabs{display:flex;gap:6px;flex-wrap:wrap}
.pkh-tab{border:1px solid var(--pkh-line-strong);background:var(--pkh-surface);border-radius:99px;padding:5px 12px;font-size:13.5px;font-weight:600;cursor:pointer;color:var(--pkh-muted)}
.pkh-tab b{color:var(--pkh-ink);margin-left:6px}
.pkh-tab[aria-selected="true"]{background:var(--pkh-ink);border-color:var(--pkh-ink);color:#fff}
.pkh-tab[aria-selected="true"] b{color:#fff}
.pkh-cari{position:relative}
.pkh-cari svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--pkh-muted);pointer-events:none}
.pkh-cari input{width:100%;height:42px;border:1.5px solid var(--pkh-line-strong);border-radius:10px;padding:0 40px 0 38px;background:var(--pkh-surface);font-size:15px}
.pkh-cari input:focus{border-color:var(--pkh-accent);outline:none;box-shadow:0 0 0 3px var(--pkh-accent-soft)}
.pkh-cari input::-webkit-search-cancel-button{display:none}
.pkh-cari .pkh-bersih{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:none;cursor:pointer;color:var(--pkh-muted);width:30px;height:30px;border-radius:6px}
.pkh-cari .pkh-bersih:hover{background:var(--pkh-surface-2);color:var(--pkh-ink)}
.pkh-filter{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pkh-filter select{height:34px;border:1px solid var(--pkh-line-strong);border-radius:8px;padding:0 8px;background:var(--pkh-surface);font-size:13.5px;max-width:100%}
.pkh-filter select.aktif{border-color:var(--pkh-accent);background:var(--pkh-accent-soft);color:var(--pkh-ink);font-weight:600}
.pkh-hapus{border:0;background:none;color:var(--pkh-accent);font-weight:600;font-size:13px;cursor:pointer;padding:4px}
.pkh-m-info{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px 12px;padding:10px 20px;background:var(--pkh-surface-2);border-bottom:1px solid var(--pkh-line);font-size:13.5px;color:var(--pkh-muted)}
.pkh-m-info b{color:var(--pkh-ink)}
.pkh-m-aksi{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pkh-m-aksi select{height:32px;border:1px solid var(--pkh-line-strong);border-radius:8px;padding:0 8px;background:var(--pkh-surface);font-size:13px}
.pkh-tbl-btn{height:32px;border:1px solid var(--pkh-line-strong);background:var(--pkh-surface);border-radius:8px;padding:0 10px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.pkh-tbl-btn:hover{border-color:var(--pkh-ink)}
.pkh-tbl-btn[disabled]{opacity:.45;cursor:default}
.pkh-tbl-btn svg{width:14px;height:14px}
.pkh-tbl-btn.utama{background:var(--pkh-ok);border-color:var(--pkh-ok);color:#fff}
.pkh-tbl-btn.utama:hover{filter:brightness(1.08)}
.pkh-m-body{overflow:auto;flex:1;min-height:180px}
.pkh table.pkh-rs{width:100%;border-collapse:collapse;font-size:14px;margin:0}
.pkh-rs th{position:sticky;top:0;background:var(--pkh-surface);z-index:1;text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--pkh-muted);font-weight:700;padding:10px 12px;border-bottom:1px solid var(--pkh-line-strong);white-space:nowrap}
.pkh-rs td{padding:10px 12px;border-bottom:1px solid var(--pkh-line);vertical-align:top}
.pkh-rs tbody tr:hover td{background:var(--pkh-surface-2)}
.pkh-rs th:first-child,.pkh-rs td:first-child{padding-left:20px}
.pkh-mono{font-family:ui-monospace,"Cascadia Mono",Consolas,monospace;font-size:13px;color:var(--pkh-ink)}
.pkh-resi{font-weight:600;white-space:nowrap}
.pkh-pes{font-size:12.5px;color:var(--pkh-muted);word-break:break-all;min-width:12ch}
.pkh-chips{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
.pkh-chip{display:inline-block;font-size:11.5px;font-weight:700;padding:1px 8px;border-radius:99px;white-space:nowrap;background:var(--pkh-track);color:var(--pkh-muted)}
.pkh-chip.w{background:var(--pkh-warn-soft);color:var(--pkh-warn)}
.pkh-chip.b{background:var(--pkh-bad-soft);color:var(--pkh-bad)}
.pkh-chip.a{background:var(--pkh-accent-soft);color:var(--pkh-accent)}
.pkh-sku{display:grid;gap:2px}
.pkh-sku div{white-space:nowrap}
.pkh-sku .q{font-weight:700;color:var(--pkh-ink)}
.pkh-kecil{font-size:12.5px;color:var(--pkh-muted)}
.pkh-nowrap{white-space:nowrap}
.pkh-rs mark{background:#ffe58a;color:var(--pkh-text);border-radius:2px;padding:0 1px}
.pkh-kosong{padding:40px 20px;text-align:center;color:var(--pkh-muted)}
.pkh-kosong b{display:block;color:var(--pkh-ink);font-size:16px;margin-bottom:4px}
.pkh-m-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 20px;border-top:1px solid var(--pkh-line);font-size:13.5px;color:var(--pkh-muted);flex-wrap:wrap}
.pkh-pager{display:flex;gap:6px;align-items:center}
.pkh-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:var(--pkh-ink);color:#fff;padding:8px 14px;border-radius:8px;font-size:13.5px;font-weight:600;z-index:10;max-width:calc(100vw - 32px)}
.pkh-salin-area{display:block;width:calc(100% - 40px);margin:12px 20px 0;height:90px;font-family:ui-monospace,Consolas,monospace;font-size:12px;border:1px solid var(--pkh-line-strong);border-radius:8px;background:var(--pkh-surface-2);padding:8px}

@media (max-width:760px){.pkh-calc{grid-template-columns:1fr}}
@media (max-width:760px){
  .pkh-lihat{height:28px;padding:0 8px;font-size:12px}
  .pkh-dlg{width:100vw;max-height:100%;height:100%;border-radius:0}
  .pkh-m-head{padding:14px 16px 10px}
  .pkh-m-info,.pkh-m-foot{padding-left:16px;padding-right:16px}
  .pkh-filter select{flex:1 1 140px}
  .pkh-rs thead{display:none}
  .pkh-rs,.pkh-rs tbody,.pkh-rs tr,.pkh-rs td{display:block;width:100%}
  .pkh-rs tr{padding:10px 16px;border-bottom:1px solid var(--pkh-line)}
  .pkh-rs td{border:0;padding:3px 0;display:grid;grid-template-columns:92px minmax(0,1fr);gap:8px}
  .pkh-rs th:first-child,.pkh-rs td:first-child{padding-left:0}
  .pkh-rs td::before{content:attr(data-l);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--pkh-muted);padding-top:2px}
  .pkh-rs tbody tr:hover td{background:none}
  .pkh-sku div{white-space:normal}
  .pkh-salin-area{width:calc(100% - 32px);margin-left:16px;margin-right:16px}
}
@media (max-width:720px){.pkh-top{grid-template-columns:1fr}}
@media (max-width:640px){
  .pkh-stages{gap:8px}
  .pkh-stage,.pkh-stage.key{padding:12px}
  .pkh-stage .num{font-size:24px}
  .pkh-stage .pct{font-size:14px}
  .pkh-stage .left{font-size:12.5px}
  .pkh-stage .left b{font-size:14px}
}
@media (prefers-reduced-motion: reduce){.pkh-bar i,.pkh-chev,.pkh-load i{transition:none}}
</style>

<div class="pkh" id="pkh-root">
  <div class="pkh-head">
    <div>
      <h1>Pemenuhan Kirim Harian</h1>
      <div class="pkh-sub"><span id="pkh-sub"></span><span class="pkh-info" id="pkh-info-sub"></span></div>
    </div>
    <div class="pkh-ctrl">
      <input type="date" id="pkh-tanggal" value="<?= html_escape($tanggal) ?>" max="<?= date('Y-m-d') ?>" aria-label="Tanggal">
      <button type="button" class="pkh-btn" id="pkh-muat"><i class="fa fa-refresh"></i> Muat ulang</button>
    </div>
  </div>

  <div class="pkh-alert" id="pkh-alert" role="alert" hidden></div>

  <section class="pkh-top">
    <div class="pkh-card">
      <div class="pkh-label">Resi masuk hari ini<span class="pkh-info" data-tip="Resi yang di-upload ke IRESIS pada tanggal ini. Resi cancel tidak dihitung."></span></div>
      <div class="num" id="pkh-masuk">–</div>
      <div class="pkh-note" id="pkh-masuk-note"></div>
    </div>
    <div class="pkh-card">
      <div class="pkh-label">Wajib keluar hari ini<span class="pkh-info" id="pkh-info-wajib"></span></div>
      <div class="num" id="pkh-wajib">–</div>
      <ul class="pkh-parts" id="pkh-parts"></ul>
    </div>
  </section>

  <section class="pkh-stages" id="pkh-stages" aria-label="Progres resi wajib keluar per tahap"></section>

  <details class="pkh-sec" id="pkh-sec-kat" open>
    <summary>
      <div class="pkh-sum-l">
        <span class="pkh-chev" aria-hidden="true"><svg viewBox="0 0 12 12"><path d="M4 2l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <div>
          <div class="pkh-sum-title">Detail Belum Selesai<span class="pkh-info" data-tip="<p><b>1 Qty</b> = resi berisi 1 barang, termasuk Spesial yang otomatis ter-pack saat dipick. <b>&gt;1 Qty</b> = semua resi lainnya.</p><p>Rata-rata waktu packing per paket (scan packer 16–23 Sep 2026): 1 Qty ±38 detik, &gt;1 Qty ±53 detik.</p>"></span></div>
          <div class="pkh-sum-meta" id="pkh-kat-meta"></div>
        </div>
      </div>
      <div class="pkh-sum-r"><span class="pkh-sum-act"></span></div>
    </summary>
    <div class="pkh-sec-body">
      <div class="pkh-tbl"><table class="pkh-kat" id="pkh-t-kat"></table></div>
    </div>
  </details>

  <details class="pkh-sec" id="pkh-sec-pack" open>
    <summary>
      <div class="pkh-sum-l">
        <span class="pkh-chev" aria-hidden="true"><svg viewBox="0 0 12 12"><path d="M4 2l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <div>
          <div class="pkh-sum-title">Detail OT/Perbantuan<span class="pkh-info" data-tip="<p><b>Beban per packer</b> = paket &gt;1 Qty yang belum dipacking ÷ jumlah packer.</p><p><b>Cukup</b> bila beban paling banyak 85% dari batas; <b>Mepet</b> bila sampai batas; <b>Perlu OT / Perbantuan</b> bila lewat batas.</p>"></span></div>
          <div class="pkh-sum-meta" id="pkh-pack-meta"></div>
        </div>
      </div>
      <div class="pkh-sum-r"><span id="pkh-pack-pill"></span><span class="pkh-sum-act"></span></div>
    </summary>
    <div class="pkh-sec-body">
      <div class="pkh-calc">
        <div class="pkh-inputs">
          <div>
            <div class="pkh-label"><label for="pkh-packer">Jumlah packer</label><span class="pkh-info" id="pkh-info-packer"></span></div>
            <div class="pkh-stepper">
              <button type="button" data-langkah="packer" data-arah="-1" aria-label="Kurangi jumlah packer">&minus;</button>
              <input type="number" id="pkh-packer" min="1" max="60" inputmode="numeric">
              <button type="button" data-langkah="packer" data-arah="1" aria-label="Tambah jumlah packer">+</button>
            </div>
          </div>
          <div>
            <div class="pkh-label"><label for="pkh-batas">Batas per packer</label><span class="pkh-info" id="pkh-info-batas"></span></div>
            <div class="pkh-stepper">
              <button type="button" data-langkah="batas" data-arah="-1" aria-label="Kurangi batas per packer">&minus;</button>
              <input type="number" id="pkh-batas" min="1" max="999" inputmode="numeric">
              <button type="button" data-langkah="batas" data-arah="1" aria-label="Tambah batas per packer">+</button>
            </div>
          </div>
        </div>
        <div class="pkh-per">
          <div class="pkh-label">Beban per packer</div>
          <div class="pkh-per-row"><span class="num" id="pkh-per">–</span><span class="of" id="pkh-per-of"></span></div>
          <div class="pkh-load" aria-hidden="true"><i id="pkh-load-bar"></i><span class="cap-mark"></span></div>
          <div class="cap" id="pkh-per-cap"></div>
        </div>
      </div>
      <div class="pkh-incl-row">
        <label class="pkh-incl" for="pkh-with-1qty"><input type="checkbox" id="pkh-with-1qty"><span id="pkh-1qty-text"></span></label>
        <span class="pkh-info" data-tip="Biasanya tidak perlu dihitung karena 1 Qty cepat dipacking. Centang bila sisa 1 Qty sangat banyak. Spesial tidak ikut karena otomatis ter-pack saat dipick."></span>
      </div>
      <div class="pkh-action" id="pkh-action"></div>
    </div>
  </details>

  <dialog class="pkh-dlg" id="pkh-dlg" aria-labelledby="pkh-m-judul">
    <div class="pkh-m-head">
      <div class="pkh-m-top">
        <div><h2 id="pkh-m-judul"></h2><div class="pkh-m-sub" id="pkh-m-sub"></div></div>
        <button type="button" class="pkh-x" id="pkh-m-tutup" aria-label="Tutup"><svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
      </div>
      <div class="pkh-tabs" id="pkh-m-tabs" role="tablist" aria-label="Tahap"></div>
      <div class="pkh-cari">
        <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <input type="search" id="pkh-m-cari" placeholder="Cari no resi, no pesanan, SKU, atau nama picker" autocomplete="off" aria-label="Cari">
        <button type="button" class="pkh-bersih" id="pkh-m-cari-x" aria-label="Kosongkan pencarian" hidden>&#x2715;</button>
      </div>
      <div class="pkh-filter">
        <select id="pkh-f-mp" aria-label="Marketplace"></select>
        <select id="pkh-f-kurir" aria-label="Kurir"></select>
        <select id="pkh-f-jenis" aria-label="Jenis resi"></select>
        <select id="pkh-f-grup" aria-label="Kelompok wajib"></select>
        <select id="pkh-f-posisi" aria-label="Posisi terakhir"></select>
        <button type="button" class="pkh-hapus" id="pkh-m-hapus" hidden>Hapus filter</button>
      </div>
    </div>
    <div class="pkh-m-info">
      <span id="pkh-m-jumlah"></span>
      <span class="pkh-m-aksi">
        <select id="pkh-m-urut" aria-label="Urutkan">
          <option value="masuk">Masuk IRESIS terlama</option>
          <option value="masuk-d">Masuk IRESIS terbaru</option>
          <option value="batas">Batas kirim terdekat</option>
          <option value="resi">No resi A–Z</option>
        </select>
        <button type="button" class="pkh-tbl-btn" id="pkh-m-salin"><svg viewBox="0 0 16 16" aria-hidden="true"><rect x="5" y="5" width="9" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3 11V3.5A1.5 1.5 0 014.5 2H11" fill="none" stroke="currentColor" stroke-width="1.5"/></svg><span id="pkh-m-salin-t">Salin no resi</span></button>
        <button type="button" class="pkh-tbl-btn utama" id="pkh-m-excel"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2v8M4.5 6.5L8 10l3.5-3.5M3 13h10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span id="pkh-m-excel-t">Unduh Excel</span></button>
      </span>
    </div>
    <div class="pkh-m-body" id="pkh-m-body"></div>
    <div class="pkh-m-foot">
      <span id="pkh-m-hal"></span>
      <span class="pkh-pager"><button type="button" class="pkh-tbl-btn" id="pkh-m-prev">&lsaquo; Sebelumnya</button><button type="button" class="pkh-tbl-btn" id="pkh-m-next">Berikutnya &rsaquo;</button></span>
    </div>
  </dialog>
</div>

<script>
(function () {
  // Kategori: 0 Spesial, 1 Reguler (keduanya 1 Qty), 2 1 SKU 2–9, 3 2–9 SKU, 4 Qty >9 (ketiganya >1 Qty), 5 tanpa rincian SKU.
  // Grup (standar operasional): wajib keluar = KA (sisa kemarin) + TA (batas kirim MP hari ini; tanpa batas kirim: pesanan s/d 12.00)
  //       + TB (TikTok s/d 15.00, batas ≤ besok); TX (TikTok s/d 15.00 batas lusa+) dan TC boleh besok.
  var URL_DATA = <?= json_encode(base_url('monitoring/pemenuhan-kirim-harian-data')) ?>;
  var URL_DETAIL = <?= json_encode(base_url('monitoring/pemenuhan-kirim-harian-detail')) ?>;
  var URL_EXCEL = <?= json_encode(base_url('monitoring/pemenuhan-kirim-harian-excel')) ?>;
  var IKON_DAFTAR = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5 4h9M5 8h9M5 12h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="2" cy="4" r="1" fill="currentColor"/><circle cx="2" cy="8" r="1" fill="currentColor"/><circle cx="2" cy="12" r="1" fill="currentColor"/></svg>';
  var AWAL = <?= json_encode($snapshot) ?>;
  var WAJIB = ['KA', 'TA', 'TB'];
  var SATU_QTY = [0, 1], LEBIH_QTY = [2, 3, 4], SEMUA = [0, 1, 2, 3, 4, 5];
  var MEPET = 0.85;
  var BATAS_INPUT = { packer: { min: 1, max: 60, langkah: 1 }, batas: { min: 1, max: 999, langkah: 10 } };
  var TIP_SUB = 'Hari ini diperbarui otomatis tiap 1 menit. Pilih tanggal lain untuk melihat rekap akhir hari tanggal itu.';
  var state = { data: null, packer: null, batas: null, satuQty: false, memuat: false };
  var root = document.getElementById('pkh-root');
  var $id = function (id) { return document.getElementById(id); };

  var nf = new Intl.NumberFormat('id-ID');
  var fmt = function (n) { return nf.format(Math.round(n)); };
  var keMenit = function (hhmm) { return (+hhmm.slice(0, 2)) * 60 + (+hhmm.slice(3, 5)); };
  var dur = function (m, naik) {   // naik = dibulatkan ke atas per 5 menit (OT); selain itu ke bawah (sisa waktu)
    m = naik ? Math.max(5, Math.ceil(m / 5) * 5) : Math.max(0, Math.floor(m));
    var h = Math.floor(m / 60), r = m % 60;
    return h ? h + ' jam' + (r ? ' ' + r + ' menit' : '') : r + ' menit';
  };
  var pct = function (a, b) {
    if (!(b > 0)) return '–';
    if (a >= b) return '100%';
    return (Math.floor(a / b * 1000) / 10).toLocaleString('id-ID', { minimumFractionDigits: 1 }) + '%';
  };

  /* ---------- ikon info: muncul saat hover / fokus / ketuk ---------- */
  var IKON = '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M8 7.2v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="4.8" r=".95" fill="currentColor"/></svg>';
  var tipSeq = 0;
  function pasangInfo(el, html) {
    if (!el.getAttribute('data-siap')) {
      var id = 'pkh-tip-' + (++tipSeq);
      el.innerHTML = IKON + '<span class="pkh-tip" role="tooltip" id="' + id + '"></span>';
      el.tabIndex = 0;
      el.setAttribute('role', 'button');
      el.setAttribute('aria-label', 'Info');
      el.setAttribute('aria-describedby', id);
      el.setAttribute('data-siap', '1');
      var tampil = function () {   // tampilkan lalu jaga agar teks tidak keluar layar
        el.classList.add('show');
        var t = el.querySelector('.pkh-tip');
        t.style.left = '';
        var r = t.getBoundingClientRect(), kiri = parseFloat(getComputedStyle(t).left);
        if (r.right > window.innerWidth - 12) t.style.left = (kiri - (r.right - window.innerWidth + 12)) + 'px';
        else if (r.left < 12) t.style.left = (kiri + (12 - r.left)) + 'px';
      };
      el.addEventListener('mouseenter', tampil);
      el.addEventListener('focus', tampil);
      el.addEventListener('mouseleave', function () { el.classList.remove('show'); });
      el.addEventListener('blur', function () { el.classList.remove('show', 'open'); });
      // ketuk (layar sentuh) membuka/menutup; di dalam <summary> jangan ikut melipat bagian
      el.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        if (el.classList.contains('open')) { el.classList.remove('open', 'show'); } else { tampil(); el.classList.add('open'); }
      });
      el.addEventListener('keydown', function (e) { if (e.key === 'Escape') { el.classList.remove('show', 'open'); el.blur(); } });
    }
    el.querySelector('.pkh-tip').innerHTML = html;
  }
  root.addEventListener('click', function () {
    Array.prototype.forEach.call(root.querySelectorAll('.pkh-info.open, .pkh-info.show'), function (x) { x.classList.remove('open', 'show'); });
  });
  Array.prototype.forEach.call(root.querySelectorAll('.pkh-info[data-tip]'), function (el) { pasangInfo(el, el.getAttribute('data-tip')); });

  /* ---------- hitung ---------- */
  // jumlahkan grup per kategori → 6 × [diterima, picker, packer, keluar, keluar tanpa HO]
  function gabung(grup, nama) {
    return SEMUA.map(function (k) {
      return [0, 1, 2, 3, 4].map(function (j) { return nama.reduce(function (s, g) { return s + grup[g][k][j]; }, 0); });
    });
  }
  function total(perKat, j, kat) { return (kat || SEMUA).reduce(function (s, k) { return s + perKat[k][j]; }, 0); }
  function belum(W, j, kat) { return Math.max(0, total(W, 0, kat) - total(W, j, kat)); }
  function sisaMenitKerja(d) {   // sampai jam selesai packer, istirahat tidak dihitung
    if (!d.hari_ini) return 0;
    var t0 = keMenit(d.jam_data), t1 = keMenit(d.setelan.jam_selesai);
    var b0 = keMenit(d.setelan.istirahat_mulai), b1 = keMenit(d.setelan.istirahat_selesai);
    return Math.max(0, (t1 - t0) - Math.max(0, Math.min(t1, b1) - Math.max(t0, b0)));
  }

  function renderAtas(d) {
    var g = d.grup, W = gabung(g, WAJIB);
    var n = function (nama) { return total(g[nama], 0); };
    var dit = total(W, 0), tanpaHO = total(W, 4), besok = n('TX') + n('TC');
    var wajibHariIni = n('TA') + n('TB');
    $id('pkh-sub').innerHTML = d.label_tanggal + ' · ' + (d.hari_ini ? 'data jam <b>' + d.jam_data.replace(':', '.') + '</b>' : '<b>rekap akhir hari</b>');
    $id('pkh-masuk').textContent = fmt(wajibHariIni + besok);
    $id('pkh-masuk-note').innerHTML = '<b>' + fmt(wajibHariIni) + '</b> wajib keluar hari ini · <b>' + fmt(besok) + '</b> boleh keluar besok';
    $id('pkh-wajib').textContent = fmt(dit);
    $id('pkh-parts').innerHTML =
      '<li><span>Sisa kemarin</span><b>' + fmt(n('KA')) + '</b></li>' +
      '<li><span>Batas kirim MP hari ini · standar MP</span><b>' + fmt(n('TA')) + '</b></li>' +
      '<li><span>TikTok s/d 15.00 · tambahan operasional</span><b>' + fmt(n('TB')) + '</b></li>';
    pasangInfo($id('pkh-info-wajib'),
      '<p>Wajib keluar (standar operasional) = sisa kemarin + resi yang <b>batas kirim MP</b>-nya hari ini + pesanan <b>TikTok</b> masuk s/d 15.00 yang batas kirim MP-nya hari ini atau besok.</p>' +
      '<p>Resi tanpa batas kirim dari upload (Lazada, reseller) ikut pesanan masuk s/d 12.00.' +
      (n('TX') ? ' ' + fmt(n('TX')) + ' pesanan TikTok s/d 15.00 berbatas kirim lusa atau lebih dihitung boleh besok.' : '') + '</p>' +
      '<p><b>Tidak dihitung:</b> ' + fmt(d.cancel_hari_ini) + ' resi cancel hari ini (CANCELED / REQUEST_CANCEL, atau dicatat di Daftar Cancel Order) dan ' + fmt(d.tertunggak_lama) +
      ' resi lama (&gt; 7 hari) yang belum keluar, perlu dicek terpisah.</p>');
    var tahap = [   // [judul, indeks kolom, kelas, teks ⓘ]
      ['Picker', 1, '', 'Resi wajib yang sudah discan picker. Resi yang sudah dipacking atau sudah keluar ikut terhitung walau scan picker-nya terlewat.'],
      ['Packer', 2, 'key', 'Resi wajib yang sudah discan packer, termasuk Spesial yang otomatis ter-pack saat dipick. Resi yang sudah keluar ikut terhitung walau scan packer-nya terlewat.'],
      ['HO (keluar)', 3, '', 'Keluar = sudah discan HO, atau status marketplace sudah SHIPPED / COMPLETED / RETURNED.' + (tanpaHO ? ' Termasuk <b>' + fmt(tanpaHO) + '</b> resi yang keluar tanpa scan HO.' : '')]
    ];
    $id('pkh-stages').innerHTML = tahap.map(function (t, i) {
      var sudah = total(W, t[1]), sisa = belum(W, t[1]);
      return '<div class="pkh-card pkh-stage ' + t[2] + '">' +
        '<div class="pkh-label">' + t[0] + '<span class="pkh-info" id="pkh-info-tahap-' + i + '"></span></div>' +
        '<div class="row-n"><span class="num">' + fmt(sudah) + '</span><span class="pct">' + pct(sudah, dit) + '</span></div>' +
        '<div class="pkh-bar"><i style="width:' + (dit ? Math.min(100, sudah / dit * 100) : 0) + '%"></i></div>' +
        '<div class="left"><div class="left-row"><span>Belum: <b>' + fmt(sisa) + '</b></span>' +
        (sisa ? '<button type="button" class="pkh-lihat" data-tahap="' + i + '">' + IKON_DAFTAR + 'Lihat detail</button>' : '') + '</div></div></div>';
    }).join('');
    tahap.forEach(function (t, i) { pasangInfo($id('pkh-info-tahap-' + i), t[3]); });
    return W;
  }

  function renderJenis(W) {
    var v = function (x) { return x ? fmt(x) : '<span class="zero">–</span>'; };
    var baris = function (label, kat, cls) {
      return '<tr class="' + (cls || '') + '"><td>' + label + '</td><td>' + v(belum(W, 1, kat)) + '</td><td class="col-pack">' + v(belum(W, 2, kat)) + '</td><td>' + v(belum(W, 3, kat)) + '</td></tr>';
    };
    $id('pkh-t-kat').innerHTML =
      '<thead><tr><th>Jenis resi</th><th>Belum picker</th><th class="col-pack">Belum packer</th><th>Belum HO</th></tr></thead><tbody>' +
      baris('1 Qty', SATU_QTY) + baris('&gt;1 Qty', LEBIH_QTY) +
      (total(W, 0, [5]) > 0 ? baris('Tanpa rincian SKU', [5]) : '') +
      baris('Total', SEMUA, 'tot') + '</tbody>';
    $id('pkh-kat-meta').innerHTML = 'Belum dipacking <b>' + fmt(belum(W, 2)) + '</b>: 1 Qty <b>' + fmt(belum(W, 2, SATU_QTY)) + '</b> · &gt;1 Qty <b>' + fmt(belum(W, 2, LEBIH_QTY)) + '</b>';
  }

  function renderPacking(W) {
    var d = state.data, P = state.packer, B = state.batas;
    // 1 Qty yang lewat meja packer hanya Reguler; Spesial otomatis ter-pack saat dipick
    var kat = state.satuQty ? [1].concat(LEBIH_QTY) : LEBIH_QTY;
    var paket = kat.reduce(function (s, k) { return s + belum(W, 2, [k]); }, 0);
    var detikRata = paket ? kat.reduce(function (s, k) { return s + belum(W, 2, [k]) * d.detik_kategori[k]; }, 0) / paket : 0;
    var beban = Math.ceil(paket / P);   // paket tidak bisa dibagi pecahan
    var sisa = sisaMenitKerja(d);

    pasangInfo($id('pkh-info-packer'), 'Default ' + d.setelan.default_packer + ', bisa diubah. Terdeteksi scan packing dalam 1 jam terakhir: <b>' + fmt(d.packer_aktif) + ' orang</b>.');
    pasangInfo($id('pkh-info-batas'), 'Default ' + d.setelan.default_batas + ': paket &gt;1 Qty paling banyak yang masih sanggup dikerjakan 1 packer sampai jam ' +
      d.setelan.jam_selesai.replace(':', '.') + '. ' + (d.hari_ini ? 'Sisa waktu kerja sekarang: <b>' + (sisa > 0 ? dur(sisa) : 'sudah lewat') + '</b>.' : ''));
    $id('pkh-per').textContent = fmt(beban);
    $id('pkh-per-of').textContent = 'paket / batas ' + fmt(B);
    $id('pkh-per-cap').innerHTML = '<b>' + fmt(paket) + '</b> paket ' + (state.satuQty ? '' : '&gt;1 Qty ') + 'belum dipacking ÷ <b>' + P + '</b> packer';
    $id('pkh-1qty-text').textContent = 'Ikut hitung 1 Qty (' + fmt(belum(W, 2, [1])) + ' paket)';

    var st, aksi;
    if (!paket) { st = 'ok'; aksi = '<b>Tidak ada sisa paket yang dihitung.</b>'; }
    else if (beban <= B * MEPET) { st = 'ok'; aksi = '<b>Tenaga cukup.</b><span>Beban ' + fmt(beban) + ' paket per packer, masih di bawah batas ' + fmt(B) + '.</span>'; }
    else if (beban <= B) { st = 'warn'; aksi = '<b>Mepet.</b><span>Beban ' + fmt(beban) + ' paket per packer, mendekati batas ' + fmt(B) + '. Siapkan cadangan orang.</span>'; }
    else {
      st = 'bad';
      var tambah = Math.ceil(paket / B) - P, lebih = paket - P * B;
      var ot = dur(lebih * detikRata / 60 / P, true);
      aksi = '<b>Perlu OT / Perbantuan: tambah ' + fmt(tambah) + ' packer, atau OT ±' + ot + ' untuk ' + P + ' packer (±' + fmt(lebih) + ' paket).</b>' +
        '<span>Beban ' + fmt(beban) + ' paket per packer, lewat batas ' + fmt(B) + '.</span>';
    }
    var LBL = { ok: 'Cukup', warn: 'Mepet', bad: 'Perlu OT / Perbantuan' };
    $id('pkh-sec-pack').className = 'pkh-sec ' + st;
    $id('pkh-action').className = 'pkh-action ' + st;
    $id('pkh-action').innerHTML = aksi;
    var bar = $id('pkh-load-bar');
    bar.style.width = Math.min(100, B ? beban / B * 100 : 0) + '%';
    bar.className = st;
    $id('pkh-pack-pill').innerHTML = '<span class="pkh-pill ' + st + '">' + LBL[st] + '</span>';
    $id('pkh-pack-meta').innerHTML = paket ? '<b>' + fmt(beban) + '</b> paket per packer · batas ' + fmt(B) + ' · ' + P + ' packer' : 'Tidak ada sisa';
  }

  function render() {
    if (!state.data) return;
    var W = renderAtas(state.data);
    renderJenis(W);
    renderPacking(W);
  }

  function pakaiData(d) {
    state.data = d;
    // bawaan dari setelan hanya dipasang sekali; angka yang sudah diubah user tetap dipakai
    if (state.packer === null) { state.packer = d.setelan.default_packer; $id('pkh-packer').value = state.packer; }
    if (state.batas === null) { state.batas = d.setelan.default_batas; $id('pkh-batas').value = state.batas; }
    tampilGalat('');
    render();
  }

  function tampilGalat(pesan) {
    var el = $id('pkh-alert');
    el.textContent = pesan;
    el.hidden = !pesan;
  }

  function muat(tanggal, diam) {
    if (state.memuat) return;
    state.memuat = true;
    var tombol = $id('pkh-muat');
    if (!diam) tombol.disabled = true;
    $.ajax({ url: URL_DATA, data: { tanggal: tanggal }, dataType: 'json', cache: false })
      .done(function (resp) {
        if (resp && resp.code === 200 && resp.data) { pakaiData(resp.data); }
        else if (resp && resp.status === 401) { tampilGalat('Sesi login habis. Silakan login ulang.'); }
        else { tampilGalat((resp && resp.message) || 'Data gagal dimuat.'); }
      })
      .fail(function () { tampilGalat('Data gagal dimuat dari server. Angka di layar mungkin sudah lama.'); })
      .always(function () { state.memuat = false; tombol.disabled = false; });
  }

  /* ---------- kontrol: setiap perubahan langsung dihitung ulang ---------- */
  var rapikan = function (nama, v) { var c = BATAS_INPUT[nama]; return Math.max(c.min, Math.min(c.max, Math.round(+v) || c.min)); };
  ['packer', 'batas'].forEach(function (nama) {
    var inp = $id('pkh-' + nama);
    // saat mengetik: hitung ulang begitu angkanya sah; saat keluar dari kotak: rapikan ke rentang yang sah
    inp.addEventListener('input', function () {
      var v = parseInt(inp.value, 10), c = BATAS_INPUT[nama];
      if (v >= c.min && v <= c.max) { state[nama] = v; render(); }
    });
    inp.addEventListener('change', function () { state[nama] = rapikan(nama, inp.value); inp.value = state[nama]; render(); });
  });
  Array.prototype.forEach.call(root.querySelectorAll('[data-langkah]'), function (b) {
    b.addEventListener('click', function () {
      var nama = b.getAttribute('data-langkah');
      state[nama] = rapikan(nama, state[nama] + (+b.getAttribute('data-arah')) * BATAS_INPUT[nama].langkah);
      $id('pkh-' + nama).value = state[nama];
      render();
    });
  });
  $id('pkh-with-1qty').addEventListener('change', function (e) { state.satuQty = e.target.checked; render(); });
  $id('pkh-tanggal').addEventListener('change', function (e) { if (e.target.value) muat(e.target.value); });
  $id('pkh-muat').addEventListener('click', function () { muat($id('pkh-tanggal').value); });

  // ingat bagian yang dibuka/ditutup per browser
  ['pkh-sec-kat', 'pkh-sec-pack'].forEach(function (id) {
    var el = $id(id), simpan = null;
    try { simpan = localStorage.getItem('pkh-' + id); } catch (e) {}
    if (simpan !== null) el.open = simpan === '1';
    el.addEventListener('toggle', function () { try { localStorage.setItem('pkh-' + id, el.open ? '1' : '0'); } catch (e) {} });
  });

  /* ---------- popup Lihat detail: daftar resi belum per tahap ---------- */
  // Data dimuat dari server sekali per buka (disimpan ±55 dtk); cari, filter,
  // urut, dan halaman dikerjakan di browser. Unduh Excel mengirim id resi yang
  // sedang tersaring supaya isi file sama persis dengan yang terlihat.
  var TAHAP_M = [
    { nama: 'Belum picker', ket: 'resi wajib keluar yang belum discan picker' },
    { nama: 'Belum packer', ket: 'resi wajib keluar yang belum discan packer' },
    { nama: 'Belum HO', ket: 'resi wajib keluar yang belum keluar (scan HO)' }
  ];
  var GRUP_M = { KA: 'Sisa kemarin', TA: 'Batas kirim MP hari ini', TB: 'TikTok s/d 15.00 (operasional)' };
  var JENIS_M = { 1: '1 Qty', 2: '>1 Qty', 0: 'Tanpa rincian SKU' };
  var POSISI_M = { belum: 'Belum dipick', pick: 'Sudah dipick, belum packing', pack: 'Sudah packing, belum HO' };
  var URUT_M = { 'masuk': 'Masuk IRESIS terlama', 'masuk-d': 'Masuk IRESIS terbaru', 'batas': 'Batas kirim terdekat', 'resi': 'No resi A–Z' };
  var BLN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var PER_HAL = 50;
  var dlg = $id('pkh-dlg');
  var pop = { tahap: 1, cari: '', mp: '', kurir: '', jenis: '', grup: '', posisi: '', urut: 'masuk', hal: 0, data: null, dimuat: 0, memuat: false, hasil: [] };
  var FILTER_M = [['pkh-f-mp', 'mp'], ['pkh-f-kurir', 'kurir'], ['pkh-f-jenis', 'jenis'], ['pkh-f-grup', 'grup'], ['pkh-f-posisi', 'posisi']];

  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  function waktuM(s, tgl) {   // "2026-09-24 09:26" → "09.26" bila hari yang sama, selain itu "23 Sep 16.34"
    if (!s || s === '-') return s || '';
    var jam = s.slice(11, 16).replace(':', '.');
    return s.slice(0, 10) === tgl ? jam : (+s.slice(8, 10)) + ' ' + BLN[+s.slice(5, 7)] + ' ' + jam;
  }
  function tglPendek(s) { return s ? (+s.slice(8, 10)) + ' ' + BLN[+s.slice(5, 7)] : '–'; }
  function diTahap(r, i) { return i === 0 ? r.pk === '' : i === 1 ? r.pc === '' : true; }
  function posisiM(r) { return r.pc ? 'pack' : (r.pk ? 'pick' : 'belum'); }
  function barisTahap() { var i = pop.tahap; return pop.data ? pop.data.rows.filter(function (r) { return diTahap(r, i); }) : []; }
  function toastM(t) {
    var el = document.createElement('div'); el.className = 'pkh-toast'; el.setAttribute('role', 'status'); el.textContent = t;
    dlg.appendChild(el); setTimeout(function () { el.remove(); }, 2600);
  }

  function bukaDetail(tahap) {
    pop.tahap = tahap; pop.cari = ''; pop.hal = 0;
    FILTER_M.forEach(function (f) { pop[f[1]] = ''; });
    $id('pkh-m-cari').value = ''; $id('pkh-m-cari-x').hidden = true;
    if (!dlg.open) { if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', ''); }
    var tgl = state.data.tanggal;
    if (pop.data && pop.data.tanggal === tgl && Date.now() - pop.dimuat < 55000) {
      isiPilihan(); renderDetail();
    } else {
      muatDetail(tgl);
    }
    setTimeout(function () { $id('pkh-m-cari').focus(); }, 30);
  }
  function tutupDetail() { if (dlg.close) dlg.close(); else dlg.removeAttribute('open'); }

  function muatDetail(tgl) {
    if (pop.memuat) return;
    pop.memuat = true;
    $id('pkh-m-judul').textContent = TAHAP_M[pop.tahap].nama;
    $id('pkh-m-sub').textContent = 'Memuat daftar resi…';
    $id('pkh-m-body').innerHTML = '<div class="pkh-kosong"><b>Memuat…</b>Mengambil daftar resi yang belum selesai.</div>';
    $.ajax({ url: URL_DETAIL, data: { tanggal: tgl }, dataType: 'json', cache: false, timeout: 60000 })
      .done(function (resp) {
        if (resp && resp.code === 200 && resp.data) {
          pop.data = resp.data; pop.dimuat = Date.now();
          isiPilihan(); renderDetail();
        } else {
          var pesan = resp && resp.status === 401 ? 'Sesi login habis. Silakan login ulang.' : ((resp && resp.message) || 'Daftar resi gagal dimuat.');
          $id('pkh-m-body').innerHTML = '<div class="pkh-kosong"><b>Daftar belum bisa ditampilkan</b>' + esc(pesan) + '</div>';
        }
      })
      .fail(function () { $id('pkh-m-body').innerHTML = '<div class="pkh-kosong"><b>Daftar belum bisa ditampilkan</b>Server tidak menjawab. Tutup lalu buka lagi.</div>'; })
      .always(function () { pop.memuat = false; });
  }

  function opsiM(el, semua, pasangan, nilai) {
    el.innerHTML = '<option value="">' + semua + '</option>' + pasangan.map(function (p) { return '<option value="' + esc(p[0]) + '">' + esc(p[1]) + ' (' + fmt(p[2]) + ')</option>'; }).join('');
    el.value = nilai;
  }
  function hitungM(rows, fn) {
    var m = {}; rows.forEach(function (r) { var k = fn(r); m[k] = (m[k] || 0) + 1; });
    return Object.keys(m).map(function (k) { return [k, m[k]]; }).sort(function (a, b) { return b[1] - a[1]; });
  }
  // pilihan filter dihitung dari resi tahap ini, lengkap dengan jumlahnya
  function isiPilihan() {
    var rows = barisTahap(), sama = function (p) { return [p[0], p[0], p[1]]; };
    opsiM($id('pkh-f-mp'), 'Semua marketplace', hitungM(rows, function (r) { return r.mp; }).map(sama), pop.mp);
    opsiM($id('pkh-f-kurir'), 'Semua kurir', hitungM(rows, function (r) { return r.k; }).map(sama), pop.kurir);
    opsiM($id('pkh-f-jenis'), 'Semua jenis', hitungM(rows, function (r) { return String(r.j); }).map(function (p) { return [p[0], JENIS_M[p[0]], p[1]]; }), pop.jenis);
    opsiM($id('pkh-f-grup'), 'Semua kelompok', hitungM(rows, function (r) { return r.g; }).map(function (p) { return [p[0], GRUP_M[p[0]] || p[0], p[1]]; }), pop.grup);
    opsiM($id('pkh-f-posisi'), 'Semua posisi', hitungM(rows, posisiM).map(function (p) { return [p[0], POSISI_M[p[0]], p[1]]; }), pop.posisi);
    $id('pkh-f-posisi').hidden = pop.tahap === 0;   // belum picker pasti belum dipick
    $id('pkh-m-tabs').innerHTML = TAHAP_M.map(function (t, i) {
      var n = pop.data ? pop.data.rows.filter(function (r) { return diTahap(r, i); }).length : 0;
      return '<button type="button" class="pkh-tab" role="tab" data-tahap="' + i + '" aria-selected="' + (pop.tahap === i) + '">' + t.nama + '<b>' + fmt(n) + '</b></button>';
    }).join('');
  }

  function saringM() {
    var q = pop.cari.trim().toUpperCase();
    var rows = barisTahap().filter(function (r) {
      if (pop.mp && r.mp !== pop.mp) return false;
      if (pop.kurir && r.k !== pop.kurir) return false;
      if (pop.jenis && String(r.j) !== pop.jenis) return false;
      if (pop.grup && r.g !== pop.grup) return false;
      if (pop.posisi && posisiM(r) !== pop.posisi) return false;
      if (q) {
        var teks = (r.r + ' ' + r.p + ' ' + r.pn + ' ' + r.sku.map(function (s) { return s[0]; }).join(' ')).toUpperCase();
        if (teks.indexOf(q) < 0) return false;
      }
      return true;
    });
    var bd = function (x, y) { return x < y ? -1 : x > y ? 1 : 0; };
    var U = {
      'masuk': function (a, b) { return bd(a.up, b.up) || a.id - b.id; },
      'masuk-d': function (a, b) { return bd(b.up, a.up) || b.id - a.id; },
      'batas': function (a, b) { return bd(a.bk || '9', b.bk || '9') || bd(a.up, b.up); },
      'resi': function (a, b) { return bd(a.r, b.r); }
    };
    return rows.sort(U[pop.urut]);
  }
  function tandai(s) {   // sorot kata yang dicari
    var q = pop.cari.trim(); s = String(s);
    var i = q ? s.toUpperCase().indexOf(q.toUpperCase()) : -1;
    return i < 0 ? esc(s) : esc(s.slice(0, i)) + '<mark>' + esc(s.slice(i, i + q.length)) + '</mark>' + esc(s.slice(i + q.length));
  }
  function keteranganFilter() {   // ditulis di baris 2 file Excel
    var k = [];
    if (pop.mp) k.push(pop.mp);
    if (pop.kurir) k.push('kurir ' + pop.kurir);
    if (pop.jenis) k.push(JENIS_M[pop.jenis]);
    if (pop.grup) k.push(GRUP_M[pop.grup]);
    if (pop.posisi) k.push(POSISI_M[pop.posisi]);
    if (pop.cari.trim()) k.push('cari "' + pop.cari.trim() + '"');
    k.push('urut ' + URUT_M[pop.urut].toLowerCase());
    return k.join(' · ');
  }

  function renderDetail() {
    var t = TAHAP_M[pop.tahap], d = pop.data;
    $id('pkh-m-judul').textContent = t.nama;
    $id('pkh-m-sub').textContent = d.label_tanggal + ' · ' + (d.hari_ini ? 'data jam ' + d.jam_data.replace(':', '.') : 'rekap akhir hari') + ' · ' + t.ket;
    var hasil = pop.hasil = saringM();
    var total = barisTahap().length, n = hasil.length;
    var halMax = Math.max(0, Math.ceil(n / PER_HAL) - 1);
    if (pop.hal > halMax) pop.hal = halMax;
    var a = pop.hal * PER_HAL, b = Math.min(n, a + PER_HAL);
    $id('pkh-m-hapus').hidden = !(pop.cari || pop.mp || pop.kurir || pop.jenis || pop.grup || pop.posisi);
    $id('pkh-m-jumlah').innerHTML = n ? 'Menampilkan <b>' + fmt(a + 1) + '–' + fmt(b) + '</b> dari <b>' + fmt(n) + '</b> resi' : 'Tidak ada resi yang cocok';
    $id('pkh-m-salin-t').textContent = 'Salin ' + fmt(n) + ' no resi';
    $id('pkh-m-salin').disabled = !n;
    $id('pkh-m-excel').disabled = !n;
    $id('pkh-m-hal').textContent = n ? 'Halaman ' + (pop.hal + 1) + ' dari ' + (halMax + 1) : '';
    $id('pkh-m-prev').disabled = pop.hal === 0;
    $id('pkh-m-next').disabled = pop.hal >= halMax;
    FILTER_M.forEach(function (f) { $id(f[0]).classList.toggle('aktif', !!$id(f[0]).value); });

    if (!n) {
      $id('pkh-m-body').innerHTML = '<div class="pkh-kosong"><b>' + (total ? 'Tidak ada resi yang cocok' : 'Tidak ada resi di tahap ini') + '</b>' +
        (total ? 'Ubah kata pencarian atau filter.' : 'Semua resi wajib sudah lewat tahap ini.') + '</div>';
      return;
    }
    $id('pkh-m-body').innerHTML = '<table class="pkh-rs"><thead><tr><th>No resi</th><th>No pesanan</th><th>MP</th><th>Kurir</th><th>SKU × Qty</th><th>Masuk IRESIS</th><th>Batas kirim</th><th>Posisi</th></tr></thead><tbody>' +
      hasil.slice(a, b).map(function (r) {
        var chips = r.g === 'KA' ? '<span class="pkh-chip">Sisa kemarin</span>' : (r.g === 'TB' ? '<span class="pkh-chip a">TikTok s/d 15.00</span>' : '');
        var pos = r.pc ? '<span class="pkh-chip a">Packing ' + waktuM(r.pc, d.tanggal) + '</span>'
          : r.pk ? '<span class="pkh-chip w">' + (r.pk === '-' ? 'Sudah dipick' : 'Dipick ' + waktuM(r.pk, d.tanggal)) + '</span>' + (r.pn ? '<div class="pkh-kecil">' + tandai(r.pn) + '</div>' : '')
          : '<span class="pkh-chip b">Belum dipick</span>';
        var sku = r.sku.length ? r.sku.map(function (s) {
          return '<div><span class="pkh-mono">' + tandai(s[0]) + '</span> <span class="q">×' + s[1] + '</span></div>';
        }).join('') : '<span class="pkh-kecil">tanpa rincian</span>';
        return '<tr>' +
          '<td data-l="No resi"><div><div class="pkh-mono pkh-resi">' + tandai(r.r) + '</div>' + (chips ? '<div class="pkh-chips">' + chips + '</div>' : '') + '</div></td>' +
          '<td data-l="No pesanan"><div class="pkh-mono pkh-pes">' + (r.p ? tandai(r.p) : '–') + '</div></td>' +
          '<td data-l="MP" class="pkh-nowrap">' + esc(r.mp) + '</td>' +
          '<td data-l="Kurir" class="pkh-nowrap">' + esc(r.k) + '</td>' +
          '<td data-l="SKU × Qty"><div class="pkh-sku">' + sku + '</div></td>' +
          '<td data-l="Masuk IRESIS" class="pkh-nowrap"><div>' + waktuM(r.up, d.tanggal) + '<div class="pkh-kecil">pesan ' + waktuM(r.ps, d.tanggal) + '</div></div></td>' +
          '<td data-l="Batas kirim" class="pkh-nowrap"><div>' + (r.bk && r.bk < d.tanggal ? '<span class="pkh-chip b">' + tglPendek(r.bk) + ' · lewat</span>' : tglPendek(r.bk)) + '</div></td>' +
          '<td data-l="Posisi"><div>' + pos + '</div></td></tr>';
      }).join('') + '</tbody></table>';
  }

  $id('pkh-stages').addEventListener('click', function (e) {
    var b = e.target.closest('.pkh-lihat'); if (!b || !state.data) return;
    bukaDetail(+b.getAttribute('data-tahap'));
  });
  $id('pkh-m-tutup').addEventListener('click', tutupDetail);
  dlg.addEventListener('click', function (e) { if (e.target === dlg) tutupDetail(); });   // klik di luar kotak menutup
  $id('pkh-m-tabs').addEventListener('click', function (e) {
    var b = e.target.closest('.pkh-tab'); if (!b || !pop.data) return;
    pop.tahap = +b.getAttribute('data-tahap'); pop.posisi = ''; pop.hal = 0; isiPilihan(); renderDetail();
  });
  var tundaCari;
  $id('pkh-m-cari').addEventListener('input', function (e) {
    clearTimeout(tundaCari); $id('pkh-m-cari-x').hidden = !e.target.value;
    tundaCari = setTimeout(function () { pop.cari = e.target.value; pop.hal = 0; if (pop.data) renderDetail(); }, 150);
  });
  $id('pkh-m-cari-x').addEventListener('click', function () {
    $id('pkh-m-cari').value = ''; $id('pkh-m-cari-x').hidden = true; pop.cari = ''; pop.hal = 0; if (pop.data) renderDetail(); $id('pkh-m-cari').focus();
  });
  FILTER_M.forEach(function (f) {
    $id(f[0]).addEventListener('change', function (e) { pop[f[1]] = e.target.value; pop.hal = 0; if (pop.data) renderDetail(); });
  });
  $id('pkh-m-urut').addEventListener('change', function (e) { pop.urut = e.target.value; pop.hal = 0; if (pop.data) renderDetail(); });
  $id('pkh-m-hapus').addEventListener('click', function () {
    pop.cari = ''; FILTER_M.forEach(function (f) { pop[f[1]] = ''; });
    $id('pkh-m-cari').value = ''; $id('pkh-m-cari-x').hidden = true; pop.hal = 0; isiPilihan(); renderDetail();
  });
  $id('pkh-m-prev').addEventListener('click', function () { pop.hal--; renderDetail(); $id('pkh-m-body').scrollTop = 0; });
  $id('pkh-m-next').addEventListener('click', function () { pop.hal++; renderDetail(); $id('pkh-m-body').scrollTop = 0; });

  $id('pkh-m-salin').addEventListener('click', function () {
    var teks = pop.hasil.map(function (r) { return r.r; }).join('\n');
    var cadangan = function () {   // clipboard ditolak (mis. lewat http di IP LAN): tampilkan teks terpilih untuk Ctrl+C
      var lama = $id('pkh-m-body').querySelector('.pkh-salin-area'); if (lama) lama.remove();
      var ta = document.createElement('textarea'); ta.className = 'pkh-salin-area'; ta.value = teks; ta.readOnly = true; ta.setAttribute('aria-label', 'Daftar no resi');
      $id('pkh-m-body').insertBefore(ta, $id('pkh-m-body').firstChild); ta.focus(); ta.select(); toastM('Tekan Ctrl+C untuk menyalin');
    };
    try {
      if (!navigator.clipboard) { cadangan(); return; }
      navigator.clipboard.writeText(teks).then(function () { toastM(fmt(pop.hasil.length) + ' no resi disalin'); }, cadangan);
    } catch (e) { cadangan(); }
  });

  $id('pkh-m-excel').addEventListener('click', function () {
    var tombol = $id('pkh-m-excel'), label = $id('pkh-m-excel-t');
    if (!pop.hasil.length || tombol.disabled) return;
    tombol.disabled = true; label.textContent = 'Menyiapkan…';
    var badan = 'tanggal=' + encodeURIComponent(pop.data.tanggal) +
      '&judul=' + encodeURIComponent(TAHAP_M[pop.tahap].nama) +
      '&filter=' + encodeURIComponent(keteranganFilter()) +
      '&ids=' + pop.hasil.map(function (r) { return r.id; }).join(',');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', URL_EXCEL);
    xhr.responseType = 'blob';
    xhr.timeout = 300000;
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    var selesai = function () { tombol.disabled = !pop.hasil.length; label.textContent = 'Unduh Excel'; };
    var gagal = function (pesan) { selesai(); toastM(pesan || 'Excel gagal dibuat. Coba lagi.'); };
    xhr.onload = function () {
      var jenis = xhr.getResponseHeader('Content-Type') || '';
      if (xhr.status === 200 && jenis.indexOf('spreadsheetml') >= 0) {
        var m = /filename="([^"]+)"/.exec(xhr.getResponseHeader('Content-Disposition') || '');
        var url = URL.createObjectURL(xhr.response), a = document.createElement('a');
        a.href = url; a.download = m ? m[1] : 'resi-belum.xlsx';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
        selesai(); toastM('Excel ' + fmt(pop.hasil.length) + ' resi diunduh');
        return;
      }
      // server membalas JSON (sesi habis, daftar kosong, dsb.)
      var baca = new FileReader();
      baca.onload = function () {
        try { var j = JSON.parse(baca.result); gagal(j.status === 401 ? 'Sesi login habis. Silakan login ulang.' : j.message); } catch (e) { gagal(); }
      };
      baca.onerror = function () { gagal(); };
      baca.readAsText(xhr.response);
    };
    xhr.onerror = function () { gagal('Server tidak menjawab. Coba lagi.'); };
    xhr.ontimeout = function () { gagal('Waktu habis saat membuat Excel. Coba saring lebih sedikit resi.'); };
    xhr.send(badan);
  });

  pasangInfo($id('pkh-info-sub'), TIP_SUB);
  if (AWAL) { pakaiData(AWAL); } else { tampilGalat('Data gagal dihitung. Coba tekan Muat ulang sebentar lagi.'); }

  // Pembaruan otomatis tiap menit untuk hari ini. Halaman dimuat lewat AJAX, jadi
  // timer lama dihentikan dulu dan timer berhenti sendiri begitu menu lain dibuka.
  if (window.__pkhTimer) clearInterval(window.__pkhTimer);
  window.__pkhTimer = setInterval(function () {
    if (!document.body.contains(root)) { clearInterval(window.__pkhTimer); window.__pkhTimer = null; return; }
    if (state.data && state.data.hari_ini && !document.hidden) muat(state.data.tanggal, true);
  }, 60000);
})();
</script>
