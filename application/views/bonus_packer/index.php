<?php
// View menu TIM PACKER -> Bonus Packer. Dimuat lewat AJAX (SPA); semua selector
// diawali #bk-root supaya tidak bocor ke halaman lain. Data dihitung server
// (Bonus_packer_fcd), view hanya menampilkan, mengurutkan, dan mencari.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Bonus Packer</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif;

$fmt = function ($n) { return number_format((int) $n, 0, ',', '.'); };
?>
<style>
  #bk-root { --bk-ink:#1c2733; --bk-muted:#6b7a8c; --bk-line:#e4e9ef; --bk-bg:#f5f7fa; --bk-brand:#2563eb; --bk-brand-soft:#e8effd;
    --bk-ok:#15803d; --bk-ok-soft:#e3f6ea; --bk-gold:#b45309; --bk-gold-soft:#fdf0d8; --bk-none:#6b7a8c; --bk-none-soft:#eef1f5;
    --bk-sp:#6d28d9; --bk-sp-soft:#ede9fe; --bk-warn:#c2410c; --bk-warn-soft:#ffedd5;
    color:var(--bk-ink); padding:4px 0 24px; }
  #bk-root * { box-sizing:border-box; }
  #bk-root .bk-card { background:#fff; border:1px solid var(--bk-line); border-radius:12px; box-shadow:0 1px 2px rgba(16,24,40,.05); }

  /* header */
  #bk-root .bk-head { display:flex; flex-wrap:wrap; align-items:center; gap:8px 14px; margin-bottom:14px; }
  #bk-root .bk-head h2 { margin:0; font-size:22px; font-weight:700; display:flex; align-items:center; gap:8px; }
  #bk-root .bk-rentang { margin-left:auto; font-size:12px; color:var(--bk-muted); background:#fff; border:1px solid var(--bk-line); border-radius:999px; padding:4px 12px; }

  /* filter */
  #bk-root .bk-filter { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; padding:14px 16px; margin-bottom:14px; }
  #bk-root .bk-filter .bk-f { display:flex; flex-direction:column; gap:4px; }
  #bk-root .bk-filter label { font-size:12px; color:var(--bk-muted); font-weight:600; margin:0; }
  #bk-root .bk-filter .form-control { height:38px; border-radius:8px; box-shadow:none; }
  #bk-root .bk-tgl { width:138px; }
  #bk-root .bk-cari { width:220px; }
  #bk-root .bk-seg { display:inline-flex; border:1px solid var(--bk-line); border-radius:8px; overflow:hidden; height:38px; }
  #bk-root .bk-seg button { border:0; background:#fff; padding:0 14px; color:var(--bk-muted); cursor:pointer; border-right:1px solid var(--bk-line); font:inherit; }
  #bk-root .bk-seg button:last-child { border-right:0; }
  #bk-root .bk-seg button:hover { color:var(--bk-brand); background:var(--bk-brand-soft); }
  #bk-root .bk-seg button.on { background:var(--bk-brand); color:#fff; }
  #bk-root .bk-btn { height:38px; border-radius:8px; border:1px solid transparent; padding:0 16px; font:inherit; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
  #bk-root .bk-btn.pri { background:var(--bk-brand); color:#fff; }
  #bk-root .bk-btn.pri:hover { background:#1d4fd8; }
  #bk-root .bk-btn.ghost { background:#fff; color:var(--bk-ok); border-color:#b7e1c5; }
  #bk-root .bk-btn.ghost:hover { background:var(--bk-ok-soft); }
  #bk-root .bk-btn.sm { height:32px; padding:0 12px; font-size:13px; }
  #bk-root .bk-grow { flex:1 1 auto; }

  /* kartu ringkasan */
  #bk-root .bk-kpi { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:14px; }
  #bk-root .bk-k { padding:14px 16px; min-width:0; }
  #bk-root .bk-k .k { font-size:12px; color:var(--bk-muted); font-weight:600; display:flex; align-items:center; gap:6px; }
  #bk-root .bk-k .v { font-size:26px; font-weight:700; line-height:1.25; margin-top:2px; }
  #bk-root .bk-k .v small { font-size:14px; font-weight:600; color:var(--bk-muted); }
  #bk-root .bk-k .s { font-size:12px; color:var(--bk-muted); margin-top:2px; }

  /* progres */
  #bk-root .bk-bar { height:6px; background:var(--bk-none-soft); border-radius:99px; overflow:hidden; margin-top:6px; }
  #bk-root .bk-bar i { display:block; height:100%; border-radius:99px; background:var(--bk-brand); }
  #bk-root .bk-bar i.hi { background:#16a34a; } #bk-root .bk-bar i.mid { background:var(--bk-brand); } #bk-root .bk-bar i.lo { background:#ea580c; }
  #bk-root .bk-pct { font-weight:700; }
  #bk-root .bk-pct.hi { color:#16a34a; } #bk-root .bk-pct.mid { color:var(--bk-brand); } #bk-root .bk-pct.lo { color:#ea580c; }

  /* tabel utama */
  #bk-root .bk-tabelwrap { overflow:hidden; }
  #bk-root table.bk-tbl { width:100%; border-collapse:collapse; margin:0; }
  #bk-root .bk-tbl th { font-size:12px; color:var(--bk-muted); font-weight:600; background:#fafbfd; padding:11px 16px; border-bottom:1px solid var(--bk-line); white-space:nowrap; text-align:left; }
  #bk-root .bk-tbl td { padding:12px 16px; border-bottom:1px solid var(--bk-line); vertical-align:middle; }
  #bk-root .bk-tbl tbody tr:last-child td { border-bottom:0; }
  #bk-root .bk-tbl tbody tr:hover { background:#fafcff; }
  #bk-root .bk-tbl th.r, #bk-root .bk-tbl td.r { text-align:right; }
  #bk-root .bk-tbl th.c, #bk-root .bk-tbl td.c { text-align:center; }
  #bk-root th.bk-sort { cursor:pointer; user-select:none; }
  #bk-root th.bk-sort:hover { color:var(--bk-brand); }
  #bk-root .bk-absen { font-variant-numeric:tabular-nums; color:var(--bk-muted); }
  #bk-root .bk-nama { display:flex; align-items:center; gap:10px; font-weight:600; }
  #bk-root .bk-av { width:32px; height:32px; border-radius:50%; color:#fff; font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
  #bk-root .bk-rank { font-size:11px; font-weight:700; border-radius:99px; padding:1px 8px; }
  #bk-root .bk-rank.r1 { background:#fef3c7; color:#92400e; } #bk-root .bk-rank.r2 { background:#e5e7eb; color:#374151; } #bk-root .bk-rank.r3 { background:#fde4cf; color:#9a3412; }
  #bk-root .bk-bonus { font-weight:700; font-variant-numeric:tabular-nums; }
  #bk-root .bk-bonus.nol { color:#9aa5b1; font-weight:500; }
  #bk-root .bk-cap { min-width:170px; }
  #bk-root .bk-cap .t { display:flex; justify-content:space-between; gap:8px; align-items:baseline; }
  #bk-root .bk-cap .sub { font-size:12px; color:var(--bk-muted); margin-top:3px; }

  /* pill */
  #bk-root .bk-pill { display:inline-block; padding:2px 10px; border-radius:999px; font-weight:600; font-size:12px; white-space:nowrap; }
  #bk-root .bk-pill.ok { background:var(--bk-ok-soft); color:var(--bk-ok); } #bk-root .bk-pill.none { background:var(--bk-none-soft); color:var(--bk-none); }
  #bk-root .bk-pill.gold { background:var(--bk-gold-soft); color:var(--bk-gold); } #bk-root .bk-pill.sp { background:var(--bk-sp-soft); color:var(--bk-sp); }

  /* status kosong / memuat / error */
  #bk-root .bk-state { padding:44px 16px; text-align:center; color:var(--bk-muted); }
  #bk-root .bk-state i.fa { font-size:30px; display:block; margin-bottom:8px; color:#b8c2cd; }
  #bk-root .bk-state.err i.fa { color:#dc2626; }
  #bk-root .bk-state .bk-btn { margin-top:10px; }
  #bk-root .bk-skel td > span { display:block; height:14px; border-radius:6px; background:linear-gradient(90deg,#eef1f5 25%,#f7f9fb 50%,#eef1f5 75%); background-size:200% 100%; animation:bkshim 1.2s infinite; }
  @keyframes bkshim { from { background-position:200% 0; } to { background-position:-200% 0; } }

  /* panel proses: tampil selama data dihitung supaya jelas halaman tidak macet */
  #bk-root .bk-load { display:none; margin-bottom:14px; overflow:hidden; border-color:#bfd2fb; background:#f5f8ff; }
  #bk-root .bk-load.on { display:block; }
  #bk-root .bk-load .bk-indet { height:4px; background:#dbe6fd; position:relative; overflow:hidden; }
  #bk-root .bk-load .bk-indet i { position:absolute; top:0; bottom:0; width:35%; background:var(--bk-brand); border-radius:99px; animation:bkindet 1.3s ease-in-out infinite; }
  @keyframes bkindet { 0% { left:-35%; } 100% { left:100%; } }
  #bk-root .bk-load .bk-loadbody { display:flex; align-items:center; gap:14px; padding:14px 16px; }
  #bk-root .bk-load .bk-spin { font-size:22px; color:var(--bk-brand); }
  #bk-root .bk-load .bk-lt { font-weight:700; display:flex; align-items:center; gap:6px; }
  #bk-root .bk-load .bk-ls { font-size:12px; color:var(--bk-muted); margin-top:2px; font-variant-numeric:tabular-nums; }
  #bk-root .bk-load .bk-lwaktu { margin-left:auto; font-size:20px; font-weight:700; color:var(--bk-brand); font-variant-numeric:tabular-nums; }
  #bk-root .bk-btn.busy { opacity:.75; cursor:progress; }
  @media (max-width: 767px) { #bk-root .bk-load .bk-loadbody { flex-wrap:wrap; } #bk-root .bk-load .bk-lwaktu { margin-left:0; } #bk-root .bk-load .bk-btn { width:100%; justify-content:center; } }

  /* ikon info (tooltip fixed supaya tidak terpotong tabel/jendela) */
  #bk-root .bk-info { display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:50%; background:var(--bk-brand-soft); color:var(--bk-brand);
    font-size:11px; font-weight:700; cursor:help; font-style:normal; outline:none; vertical-align:middle; }
  #bk-root .bk-info:focus { box-shadow:0 0 0 2px var(--bk-brand); }
  #bk-root .bk-tip { display:none; position:fixed; z-index:3000; width:300px; max-width:calc(100vw - 16px); background:#1c2733; color:#fff; padding:10px 12px; border-radius:8px;
    font-size:12px; font-weight:400; line-height:1.5; text-align:left; white-space:normal; box-shadow:0 8px 24px rgba(0,0,0,.28); pointer-events:none; }
  #bk-root .bk-tip.on { display:block; }
  #bk-root .bk-info .bk-tipsrc { display:none; }

  /* jendela detail */
  #bk-root .bk-ov { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(16,24,40,.5); display:none; align-items:center; justify-content:center; padding:16px; z-index:2000; }
  #bk-root .bk-ov.on { display:flex; }
  #bk-root .bk-modal { background:#fff; width:100%; max-width:760px; max-height:90vh; display:flex; flex-direction:column; border-radius:14px; overflow:hidden; outline:none; box-shadow:0 20px 50px rgba(0,0,0,.3); }
  #bk-root .bk-mh { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--bk-line); }
  #bk-root .bk-mh h4 { margin:0; font-size:18px; font-weight:700; }
  #bk-root .bk-mh .s { color:var(--bk-muted); font-size:12px; margin-top:2px; }
  #bk-root .bk-mh .act { display:flex; align-items:center; gap:8px; }
  #bk-root .bk-x { border:0; background:var(--bk-none-soft); width:32px; height:32px; border-radius:50%; font-size:20px; line-height:1; cursor:pointer; color:var(--bk-muted); }
  #bk-root .bk-x:hover { background:#e2e8f0; color:var(--bk-ink); }
  #bk-root .bk-msum { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; padding:14px 20px; border-bottom:1px solid var(--bk-line); background:#fafbfd; }
  #bk-root .bk-msum > div { background:#fff; border:1px solid var(--bk-line); border-radius:10px; padding:8px 12px; font-size:12px; color:var(--bk-muted); }
  #bk-root .bk-msum b { display:block; font-size:16px; color:var(--bk-ink); }
  #bk-root .bk-mb { overflow:auto; -webkit-overflow-scrolling:touch; }
  #bk-root .bk-mb table { width:100%; border-collapse:collapse; margin:0; min-width:520px; }
  #bk-root .bk-mb th { position:sticky; top:0; z-index:1; background:#fff; font-size:12px; color:var(--bk-muted); font-weight:600; padding:10px 14px; border-bottom:1px solid var(--bk-line); text-align:right; white-space:nowrap; }
  #bk-root .bk-mb th:first-child, #bk-root .bk-mb td:first-child { text-align:left; }
  #bk-root .bk-mb td { padding:9px 14px; border-bottom:1px solid #f0f3f6; text-align:right; font-variant-numeric:tabular-nums; }
  #bk-root .bk-mb td:first-child, #bk-root .bk-mb td:last-child { white-space:nowrap; }
  #bk-root .bk-sal { display:inline-block; min-width:34px; padding:2px 0; border:0; border-radius:999px; text-align:center; font:inherit; font-weight:700; line-height:1.4; }
  #bk-root .bk-sal.nol { background:#eef1f5; color:#9aa5b1; font-weight:500; }
  #bk-root button.bk-sal { background:#fee2e2; color:#b91c1c; cursor:pointer; }
  #bk-root button.bk-sal:hover { background:#fecaca; }
  #bk-root button.bk-sal.buka { background:#b91c1c; color:#fff; }
  #bk-root .bk-mb tr.bk-resirow td { background:#fff7f7; text-align:left; white-space:normal; padding:10px 14px 12px; border-bottom:1px solid #fbd5d5; }
  #bk-root .bk-resibox .jd { font-size:12px; font-weight:600; color:#991b1b; margin-bottom:8px; }
  #bk-root .bk-resibox ul { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:6px 8px; }
  #bk-root .bk-resibox li { background:#fff; border:1px solid #fbd5d5; border-radius:6px; padding:3px 10px; font:12px/1.5 Consolas, Menlo, monospace; color:#7f1d1d; user-select:all; white-space:nowrap; }
  #bk-root .bk-mb td .bk-hr { color:var(--bk-muted); font-size:11px; display:block; }

  /* tablet */
  @media (max-width: 991px) { #bk-root .bk-kpi { grid-template-columns:1fr 1fr; } #bk-root .bk-kpi .bk-k:last-child { grid-column:1 / -1; } }

  /* HP: tabel utama jadi kartu, jendela detail jadi lembar bawah */
  @media (max-width: 767px) {
    #bk-root .bk-head h2 { font-size:19px; } #bk-root .bk-rentang { margin-left:0; }
    #bk-root .bk-filter { padding:12px; gap:10px; }
    #bk-root .bk-filter .bk-f, #bk-root .bk-tgl, #bk-root .bk-cari { width:100%; flex:1 1 100%; }
    #bk-root .bk-f.dua { flex:1 1 calc(50% - 5px); width:auto; } #bk-root .bk-f.dua .bk-tgl { width:100%; }
    #bk-root .bk-seg { width:100%; } #bk-root .bk-seg button { flex:1; }
    #bk-root .bk-filter .bk-f > label:empty, #bk-root .bk-filter .bk-f > label.kosong { display:none; }
    #bk-root .bk-filter .bk-f > .bk-btn { width:100%; justify-content:center; }
    #bk-root .bk-f.bk-grow { display:none; }
    #bk-root .bk-btn.sm { width:auto !important; }
    #bk-root .bk-kpi { grid-template-columns:1fr; }
    #bk-root .bk-tabelwrap { border:0; box-shadow:none; background:transparent; }
    #bk-root .bk-tbl, #bk-root .bk-tbl tbody { display:block; }
    #bk-root .bk-tbl thead { display:none; }
    #bk-root .bk-tbl tbody tr { display:grid; grid-template-columns:1fr auto; grid-template-areas:"nama bonus" "cap cap" "absen aksi"; gap:8px 12px;
      background:#fff; border:1px solid var(--bk-line); border-radius:12px; padding:12px; margin-bottom:10px; align-items:center; }
    #bk-root .bk-tbl td { display:block; padding:0; border:0; text-align:left !important; }
    #bk-root .bk-tbl td.c-nama { grid-area:nama; } #bk-root .bk-tbl td.c-bonus { grid-area:bonus; text-align:right !important; }
    #bk-root .bk-tbl td.c-cap { grid-area:cap; } #bk-root .bk-tbl td.c-absen { grid-area:absen; font-size:12px; } #bk-root .bk-tbl td.c-absen::before { content:"No. Absen "; }
    #bk-root .bk-tbl td.c-aksi { grid-area:aksi; text-align:right !important; }
    #bk-root .bk-tbl tr.bk-skel, #bk-root .bk-tbl tr.bk-vacant { display:block; }
    #bk-root .bk-cap { min-width:0; }
    #bk-root .bk-ov { align-items:flex-end; padding:0; }
    #bk-root .bk-modal { max-width:none; max-height:94vh; border-radius:16px 16px 0 0; }
    #bk-root .bk-mh, #bk-root .bk-msum { padding-left:14px; padding-right:14px; }
    #bk-root .bk-msum { grid-template-columns:1fr 1fr; gap:8px; padding-top:10px; padding-bottom:10px; }
    #bk-root .bk-msum > div { padding:6px 10px; } #bk-root .bk-msum b { font-size:14px; }
    #bk-root .bk-mb table { min-width:0; font-size:12px; }
    #bk-root .bk-mb th { white-space:normal; padding:8px 4px; vertical-align:bottom; font-size:11px; }
    #bk-root .bk-mb td { padding:8px 4px; }
    #bk-root .bk-mb .bk-pill { padding:2px 7px; font-size:11px; }
    #bk-root .bk-mb th:first-child, #bk-root .bk-mb td:first-child { padding-left:14px; }
    #bk-root .bk-mb th:last-child, #bk-root .bk-mb td:last-child { padding-right:14px; }
    #bk-root .bk-mh .bk-btn .tx { display:none; }
  }
</style>

<div id="bk-root">
  <div class="bk-head">
    <h2>Bonus Packer
      <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">
        <b>Aturan perhitungan</b><br>
        &bull; Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin. Kesalahan diambil dari komplain CS dengan hasil investigasi QC Salah, dilacak lewat resinya dan dicatat pada tanggal scan packing resi itu (semua status performa dan jam).<br>
        &bull; Net harian = total packing &minus; poin kesalahan.<br>
        &bull; Net <?= $fmt($aturan['target']) ?>&ndash;<?= $fmt($aturan['tier2'] - 1) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus1']) ?>/hari.<br>
        &bull; Net &ge; <?= $fmt($aturan['tier2']) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus2']) ?>/hari.<br>
        &bull; Net &lt; <?= $fmt($aturan['target']) ?> &rarr; tidak ada bonus.<br>
        &bull; Hanya resi yang di-scan packing pukul 05.00&ndash;18.00.<br>
        &bull; Hanya NORMAL_PACKER; 1_SKU_PACKER, QTY_BANYAK, dan PAYUNG tidak dihitung.<br>
        &bull; Capai target = jumlah hari dengan net &ge; <?= $fmt($aturan['target']) ?>.<br>
        &bull; Persentase = hari capai target &divide; hari kerja.
      </span></span>
    </h2>
    <div class="bk-rentang" id="bk-rentang"></div>
    <div id="bk-arsip"></div>
  </div>

  <div class="bk-card bk-filter">
    <div class="bk-f dua"><label for="bk-awal">Dari tanggal</label>
      <input type="text" id="bk-awal" class="form-control bk-tgl" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($awal)) ?>"></div>
    <div class="bk-f dua"><label for="bk-akhir">Sampai tanggal</label>
      <input type="text" id="bk-akhir" class="form-control bk-tgl" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($akhir)) ?>"></div>
    <div class="bk-f"><label class="kosong">&nbsp;</label>
      <div class="bk-seg" id="bk-seg">
        <button type="button" class="bk-cepat" data-r="0">Hari ini</button>
        <button type="button" class="bk-cepat" data-r="6">7 hari</button>
        <button type="button" class="bk-cepat on" data-r="bulan">Bulan ini</button>
      </div></div>
    <div class="bk-f"><label class="kosong">&nbsp;</label>
      <button type="button" class="bk-btn pri" id="bk-tampilkan"><i class="fa fa-search"></i> Tampilkan</button></div>
    <div class="bk-f bk-grow"></div>
    <div class="bk-f"><label for="bk-cari">Cari packer</label>
      <input type="search" id="bk-cari" class="form-control bk-cari" placeholder="Nama atau no. absen"></div>
    <div class="bk-f"><label class="kosong">&nbsp;</label>
      <button type="button" class="bk-btn ghost" id="bk-export"><i class="fa fa-file-excel-o"></i> Export Excel</button></div>
  </div>

  <div class="bk-kpi">
    <div class="bk-card bk-k"><div class="k">Total bonus</div><div class="v" id="bk-s-bonus">-</div><div class="s" id="bk-s-bonus-s">&nbsp;</div></div>
    <div class="bk-card bk-k"><div class="k">Packer berbonus</div><div class="v" id="bk-s-packer">-</div><div class="s" id="bk-s-packer-s">&nbsp;</div></div>
    <div class="bk-card bk-k"><div class="k">Capaian hari kerja
      <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Hari capai target (net &ge; <?= $fmt($aturan['target']) ?>) dibagi seluruh hari kerja semua packer pada rentang tanggal.</span></span></div>
      <div class="v" id="bk-s-hari">-</div>
      <div class="bk-bar" id="bk-s-bar" style="display:none"><i></i></div>
      <div class="s" id="bk-s-hari-s">&nbsp;</div></div>
  </div>

  <div class="bk-card bk-load" id="bk-load" role="status" aria-live="polite">
    <div class="bk-indet"><i></i></div>
    <div class="bk-loadbody">
      <i class="fa fa-circle-o-notch fa-spin bk-spin"></i>
      <div>
        <div class="bk-lt">Sedang menghitung bonus packer
          <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Halaman tidak macet. Rentang panjang butuh waktu lebih lama, terutama kalau data lama dari arsip ikut dibaca. Hasil tampil otomatis begitu selesai, jadi mohon jangan tutup atau muat ulang halaman.</span></span></div>
        <div class="bk-ls" id="bk-load-sub"></div>
      </div>
      <div class="bk-lwaktu" id="bk-load-waktu">00:00</div>
      <button type="button" class="bk-btn ghost sm" id="bk-load-batal" style="color:#b91c1c;border-color:#fecaca"><i class="fa fa-times"></i> Batalkan</button>
    </div>
  </div>

  <div class="bk-card bk-tabelwrap">
    <table class="bk-tbl">
      <thead><tr>
        <th class="bk-sort" data-k="absen" style="width:110px">No. Absen <span class="bk-arr"></span></th>
        <th class="bk-sort" data-k="nama">Nama <span class="bk-arr"></span></th>
        <th class="bk-sort r" data-k="bonus">Total Bonus <span class="bk-arr"></span></th>
        <th class="bk-sort" data-k="pct">Total Capai Target
          <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Hari target tercapai (net &ge; <?= $fmt($aturan['target']) ?>) dari seluruh hari kerja pada rentang tanggal. Urutan mengikuti persentase.</span></span>
          <span class="bk-arr"></span></th>
        <th class="c" style="width:90px">Aksi</th>
      </tr></thead>
      <tbody id="bk-rows"></tbody>
    </table>
  </div>

  <div class="bk-ov" id="bk-ov">
    <div class="bk-modal" id="bk-modal" role="dialog" aria-modal="true" aria-labelledby="bk-m-nama" tabindex="-1">
      <div class="bk-mh">
        <div><h4 id="bk-m-nama"></h4><div class="s" id="bk-m-sub"></div></div>
        <div class="act">
          <button type="button" class="bk-btn ghost sm" id="bk-m-export"><i class="fa fa-file-excel-o"></i> <span class="tx">Export Excel</span></button>
          <button type="button" class="bk-x" id="bk-m-x" aria-label="Tutup">&times;</button>
        </div>
      </div>
      <div class="bk-msum" id="bk-m-sum"></div>
      <div class="bk-mb">
        <table>
          <thead><tr>
            <th>Tanggal</th>
            <th>Total Packing</th>
            <th>Jumlah Kesalahan</th>
            <th>Poin Kesalahan
              <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin. Kesalahan = komplain CS dengan hasil investigasi QC Salah pada resi yang scan packing-nya di tanggal itu (semua status performa dan jam).</span></span></th>
            <th>Net
              <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Net = total packing &minus; poin kesalahan.<br>Emas = net &ge; <?= $fmt($aturan['tier2']) ?> (Rp <?= $fmt($aturan['bonus2']) ?>), hijau = <?= $fmt($aturan['target']) ?>&ndash;<?= $fmt($aturan['tier2'] - 1) ?> (Rp <?= $fmt($aturan['bonus1']) ?>), abu = di bawah target (tanpa bonus).</span></span></th>
            <th>Bonus</th>
          </tr></thead>
          <tbody id="bk-m-rows"></tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="bk-tip" id="bk-tip"></div>
</div>

<script>
(function () {
  var $root = $('#bk-root');
  var TARGET = <?= (int) $aturan['target'] ?>, TIER2 = <?= (int) $aturan['tier2'] ?>, POIN = <?= (int) $aturan['poin'] ?>;
  var data = [], sortK = 'bonus', sortDir = -1, awalAktif = '', akhirAktif = '', kodeAktif = 0;

  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
  function num(n) { return Number(n).toLocaleString('id-ID'); }
  function absen(a) { return a == null ? '-' : ('0000' + a).slice(-4); }
  function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
  // Tampilan dd/mm/yyyy (Indonesia); ke server selalu yyyy-mm-dd (format database).
  function idTgl(d) { return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear(); }
  function keIso(s) {
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec($.trim(s));
    if (!m) { return ''; }
    var d = new Date(+m[3], +m[2] - 1, +m[1]);
    if (d.getFullYear() !== +m[3] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[1]) { return ''; }
    return iso(d);
  }
  function tgl(s) {
    var d = new Date(s + 'T00:00:00');
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
  }
  function tglBaris(s) { return new Date(s + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short' }); }
  function tglPendek(s) { return new Date(s + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }
  function kelas(p) { return p >= 80 ? 'hi' : (p >= 50 ? 'mid' : 'lo'); }
  function warnaAvatar(nama) {
    var h = 0; for (var i = 0; i < nama.length; i++) { h = (h * 31 + nama.charCodeAt(i)) % 360; }
    return 'hsl(' + h + ',48%,46%)';
  }

  // ---- tooltip info: fixed + dijepit ke layar, jalan untuk hover maupun sentuh/fokus ----
  var $tip = $('#bk-tip');
  function tampilTip(el) {
    var src = $(el).find('.bk-tipsrc').html();
    if (!src) { return; }
    // Dari pojok kiri-atas dulu supaya lebar/tinggi terukur penuh sebelum dijepit ke layar.
    $tip.css({ left: 0, top: 0 }).html(src).addClass('on');
    var r = el.getBoundingClientRect(), w = $tip.outerWidth(), h = $tip.outerHeight();
    var left = Math.min(Math.max(8, r.left + r.width / 2 - w / 2), window.innerWidth - w - 8);
    var top = r.bottom + 8;
    if (top + h > window.innerHeight - 8) { top = Math.max(8, r.top - h - 8); }
    $tip.css({ left: left + 'px', top: top + 'px' });
  }
  function sembunyiTip() { $tip.removeClass('on'); }
  $root.on('mouseenter focusin', '.bk-info', function () { tampilTip(this); });
  $root.on('mouseleave focusout', '.bk-info', sembunyiTip);

  function skeleton() {
    var baris = '';
    for (var i = 0; i < 5; i++) {
      baris += '<tr class="bk-skel"><td class="c-absen"><span style="width:50px"></span></td><td class="c-nama"><span style="width:140px"></span></td>' +
        '<td class="c-bonus r"><span style="width:90px;margin-left:auto"></span></td><td class="c-cap"><span style="width:100%"></span></td><td class="c-aksi"><span style="width:50px;margin:auto"></span></td></tr>';
    }
    $('#bk-rows').html(baris);
  }
  function keadaan(ikon, teks, tombol) {
    $('#bk-rows').html('<tr class="bk-vacant"><td colspan="5" class="bk-state' + (ikon === 'fa-exclamation-triangle' ? ' err' : '') + '" style="display:table-cell"><i class="fa ' + ikon + '"></i>' + teks +
      (tombol ? '<br><button type="button" class="bk-btn pri sm" id="bk-ulang"><i class="fa fa-refresh"></i> Coba lagi</button>' : '') + '</td></tr>');
  }

  function siapkan(list) {
    return list.map(function (p) {
      var normal = p.det.length;
      p.hari_normal = normal;
      p.pct = normal > 0 ? Math.round(p.hari / normal * 100) : -1;
      return p;
    });
  }

  // Pemilih tanggal memicu beberapa event sekaligus (change + changeDate): digabung
  // lewat debounce, dan permintaan identik yang masih berjalan tidak dikirim ulang.
  var tundaMuat = null, xhrAktif = null, kunciAktif = '', timerProses = null;

  // Panel proses: spinner + stopwatch selama data dihitung, supaya jelas halaman tidak macet.
  function mmss(detik) { return ('0' + Math.floor(detik / 60)).slice(-2) + ':' + ('0' + (detik % 60)).slice(-2); }
  function mulaiProses(a, b) {
    var hari = Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000) + 1;
    var mulai = Date.now();
    clearInterval(timerProses);
    $('#bk-load').addClass('on');
    $('#bk-tampilkan').addClass('busy').html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
    function tik() {
      if (!$.contains(document.documentElement, $root[0])) { clearInterval(timerProses); return; } // pindah menu
      var dt = Math.floor((Date.now() - mulai) / 1000);
      $('#bk-load-waktu').text(mmss(dt));
      $('#bk-load-sub').text(hari + ' hari · ' + tglPendek(a) + ' – ' + tglPendek(b) + (dt >= 15 ? ' · masih berjalan, mohon tunggu' : ''));
    }
    tik();
    timerProses = setInterval(tik, 1000);
  }
  function akhiriProses() {
    clearInterval(timerProses);
    $('#bk-load').removeClass('on');
    $('#bk-tampilkan').removeClass('busy').html('<i class="fa fa-search"></i> Tampilkan');
  }
  function muat() {
    clearTimeout(tundaMuat);
    tundaMuat = setTimeout(muatLangsung, 150);
  }

  function muatLangsung() {
    var a = keIso($('#bk-awal').val()), b = keIso($('#bk-akhir').val());
    if (!a || !b) {
      keadaan('fa-calendar-times-o', 'Format tanggal harus dd/mm/yyyy, contoh 01/10/2026.', false);
      return;
    }
    var kunci = a + '|' + b;
    if (xhrAktif && kunciAktif === kunci) { return; }
    if (xhrAktif) { xhrAktif.abort(); }
    kunciAktif = kunci;
    skeleton();
    mulaiProses(a, b);
    var req = $.ajax({
      url: 'bonus-packer/get-data', type: 'POST', dataType: 'json', data: { awal: a, akhir: b },
      complete: function () { if (xhrAktif === req) { xhrAktif = null; kunciAktif = ''; akhiriProses(); } },
      success: function (r) {
        if (r.code === 200) {
          data = siapkan(r.data.packers); awalAktif = a; akhirAktif = b;
          $('#bk-rentang').text(tglPendek(a) + ' – ' + tglPendek(b));
          var ars = r.data.arsip;
          $('#bk-arsip').html(ars === 'ya'
            ? '<span class="bk-pill sp">Termasuk data arsip <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Rentang ini menyentuh data lama yang sudah dipindah dari iresis_prod. Hasil dilengkapi otomatis dari iresis_arsip.</span></span></span>'
            : (ars === 'gagal'
              ? '<span class="bk-pill" style="background:#fee2e2;color:#b91c1c">Data arsip gagal dibaca <span class="bk-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bk-tipsrc">Rentang ini butuh data dari iresis_arsip tetapi tidak terbaca. Angka di layar hanya dari iresis_prod dan bisa kurang. Coba muat ulang, atau hubungi admin kalau berulang.</span></span></span>'
              : ''));
          render();
        } else { keadaan('fa-exclamation-triangle', esc(r.message), false); }
      },
      error: function (xhr, status) {
        if (status === 'abort') { return; } // digantikan permintaan yang lebih baru
        keadaan('fa-exclamation-triangle', 'Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 160)), true);
      }
    });
    xhrAktif = req;
  }

  function render() {
    var q = $.trim($('#bk-cari').val()).toLowerCase();
    // Peringkat tetap berdasar bonus (bukan urutan tabel yang sedang dipilih).
    var peringkat = data.slice().sort(function (x, y) { return y.bonus - x.bonus || y.hari - x.hari; });
    var rank = {};
    peringkat.forEach(function (p, i) { if (p.bonus > 0 && i < 3) { rank[p.kode] = i + 1; } });

    var rows = data.filter(function (p) {
      return !q || p.nama.toLowerCase().indexOf(q) >= 0 || String(p.absen) === q || absen(p.absen) === q;
    });
    rows.sort(function (x, y) {
      var A = x[sortK], B = y[sortK];
      if (A == null) { A = -1; }
      if (B == null) { B = -1; }
      return (typeof A === 'string' ? A.localeCompare(B) : A - B) * sortDir;
    });

    if (!rows.length) {
      keadaan('fa-user-o', q ? 'Tidak ada packer yang cocok dengan pencarian.' : 'Tidak ada data packer pada rentang ini.', false);
    } else {
      $('#bk-rows').html(rows.map(function (p) {
        var cap;
        if (p.hari_normal > 0) {
          var k = kelas(p.pct);
          cap = '<div class="bk-cap"><div class="t"><b>' + p.hari + ' hari</b><span class="bk-pct ' + k + '">' + p.pct + '%</span></div>' +
            '<div class="bk-bar"><i class="' + k + '" style="width:' + p.pct + '%"></i></div>' +
            '<div class="sub">dari ' + p.hari_normal + ' hari kerja</div></div>';
        } else {
          cap = '<div class="bk-cap"></div>';
        }
        var badge = rank[p.kode] ? ' <span class="bk-rank r' + rank[p.kode] + '">#' + rank[p.kode] + '</span>' : '';
        return '<tr><td class="c-absen"><span class="bk-absen">' + absen(p.absen) + '</span></td>' +
          '<td class="c-nama"><div class="bk-nama"><span class="bk-av" style="background:' + warnaAvatar(p.nama) + '">' + esc(p.nama.charAt(0)) + '</span><span>' + esc(p.nama) + badge + '</span></div></td>' +
          '<td class="c-bonus r bk-bonus' + (p.bonus ? '' : ' nol') + '">' + rp(p.bonus) + '</td>' +
          '<td class="c-cap">' + cap + '</td>' +
          '<td class="c-aksi c"><button type="button" class="bk-btn pri sm bk-detail" data-k="' + p.kode + '">Detail</button></td></tr>';
      }).join(''));
    }

    // Kartu ringkasan dihitung dari baris yang tampil (ikut pencarian).
    var bonus = 0, berbonus = 0, hari = 0, normal = 0;
    rows.forEach(function (p) { bonus += p.bonus; hari += p.hari; normal += p.hari_normal; if (p.bonus > 0) { berbonus++; } });
    var pct = normal > 0 ? Math.round(hari / normal * 100) : null;
    $('#bk-s-bonus').text(rp(bonus));
    $('#bk-s-bonus-s').text(rows.length ? 'rata-rata ' + rp(rows.length ? Math.round(bonus / rows.length) : 0) + ' per packer' : ' ');
    $('#bk-s-packer').html(berbonus + ' <small>/ ' + rows.length + ' packer</small>');
    $('#bk-s-packer-s').text(rows.length ? (rows.length - berbonus) + ' packer belum capai bonus' : ' ');
    $('#bk-s-hari').html(pct === null ? '-' : pct + '% <small>(' + hari + ' / ' + normal + ' hari)</small>');
    if (pct === null) { $('#bk-s-bar').hide(); }
    else { $('#bk-s-bar').show().find('i').attr('class', kelas(pct)).css('width', pct + '%'); }
    $('#bk-s-hari-s').text(' ');

    $root.find('th.bk-sort').each(function () {
      $(this).find('.bk-arr').text($(this).data('k') === sortK ? (sortDir > 0 ? '▲' : '▼') : '');
    });
  }

  function buka(kode) {
    var p = data.filter(function (x) { return x.kode === kode; })[0];
    if (!p) { return; }
    kodeAktif = p.kode;
    var tp = 0, ts = 0;
    p.det.forEach(function (x) { tp += x.pick; ts += x.salah; });
    $('#bk-m-nama').text(p.nama);
    $('#bk-m-sub').text('No. absen ' + absen(p.absen) + ' · ' + tglPendek(awalAktif) + ' – ' + tglPendek(akhirAktif));
    var capaian = p.hari_normal > 0
      ? '<b>' + p.hari + ' / ' + p.hari_normal + ' hari <span class="bk-pct ' + kelas(p.pct) + '">' + p.pct + '%</span></b><div class="bk-bar"><i class="' + kelas(p.pct) + '" style="width:' + p.pct + '%"></i></div>'
      : '<b>-</b>';
    $('#bk-m-sum').html(
      '<div>Total bonus<b>' + rp(p.bonus) + '</b></div>' +
      '<div>Capai target (hari kerja)' + capaian + '</div>' +
      '<div>Total packing<b>' + num(tp) + '</b></div>' +
      '<div>Kesalahan<b>' + ts + ' <span style="font-weight:500;font-size:12px;color:#6b7a8c">(' + (ts * POIN) + ' poin)</span></b></div>');
    $('#bk-m-rows').html(p.det.map(function (x) {
      var cls = x.net >= TIER2 ? 'gold' : (x.net >= TARGET ? 'ok' : 'none');
      var salah = x.salah > 0
        ? '<button type="button" class="bk-sal" data-t="' + esc(x.tgl) + '" title="Lihat daftar resi" aria-expanded="false">' + x.salah + '</button>'
        : '<span class="bk-sal nol">0</span>';
      var baris = '<tr><td>' + tglBaris(x.tgl) + '</td><td>' + num(x.pick) + '</td><td>' + salah + '</td><td>' + x.poin +
        '</td><td><span class="bk-pill ' + cls + '">' + num(x.net) + '</span></td>' +
        '<td>' + (x.bonus ? '<b>' + rp(x.bonus) + '</b>' : '<span style="color:#9aa5b1">-</span>') + '</td></tr>';
      if (x.salah > 0) {
        baris += '<tr class="bk-resirow" data-t="' + esc(x.tgl) + '" style="display:none"><td colspan="6"><div class="bk-resibox">' +
          '<div class="jd">Resi kesalahan QC (komplain QC Salah) &middot; ' + x.salah + ' resi</div><ul>' +
          x.resi.map(function (r) { return '<li>' + esc(r) + '</li>'; }).join('') + '</ul></div></td></tr>';
      }
      return baris;
    }).join(''));
    $('#bk-ov').addClass('on');
    $('#bk-modal').trigger('focus');
  }
  function tutup() { sembunyiTip(); $('#bk-ov').removeClass('on'); }

  $root.on('click', '.bk-detail', function () { buka(parseInt($(this).data('k'), 10)); });
  $root.on('click', '.bk-sal', function () {
    var $b = $(this), $r = $('#bk-m-rows tr.bk-resirow[data-t="' + $b.data('t') + '"]');
    $r.toggle();
    $b.toggleClass('buka', $r.is(':visible')).attr('aria-expanded', $r.is(':visible'));
  });
  $root.on('click', '#bk-m-x', tutup);
  $root.on('click', '#bk-ov', function (e) { if (e.target === this) { tutup(); } });
  $root.on('keydown', '#bk-ov', function (e) { if (e.key === 'Escape') { tutup(); } });
  $root.on('click', '#bk-m-export', function () {
    // Pakai rentang yang dimuat di tabel (bukan isi kotak tanggal yang mungkin sudah diubah).
    window.location.href = '<?= base_url('bonus-packer/export-excel-detail') ?>?kode=' + kodeAktif + '&awal=' + awalAktif + '&akhir=' + akhirAktif;
  });
  $root.on('click', '#bk-tampilkan, #bk-ulang', muat);
  $root.on('click', '#bk-load-batal', function () {
    if (xhrAktif) { xhrAktif.abort(); }
    akhiriProses();
    keadaan('fa-ban', 'Pemuatan dibatalkan.', true);
  });
  $root.on('click', '#bk-export', function () {
    var a = keIso($('#bk-awal').val()), b = keIso($('#bk-akhir').val());
    if (!a || !b) { muat(); return; } // munculkan pesan format tanggal
    window.location.href = '<?= base_url('bonus-packer/export-excel') ?>?awal=' + a + '&akhir=' + b;
  });
  $root.on('change', '#bk-awal, #bk-akhir', function () { $('#bk-seg button').removeClass('on'); muat(); });
  $root.on('input', '#bk-cari', render);
  $root.on('click', 'th.bk-sort', function () {
    var k = $(this).data('k');
    sortDir = (sortK === k) ? -sortDir : ((k === 'nama' || k === 'absen') ? 1 : -1);
    sortK = k; render();
  });
  $root.on('click', '.bk-cepat', function () {
    var r = $(this).data('r'), n = new Date();
    $('#bk-seg button').removeClass('on'); $(this).addClass('on');
    if (r === 'bulan') { $('#bk-awal').val(idTgl(new Date(n.getFullYear(), n.getMonth(), 1))); }
    else { var s = new Date(n); s.setDate(s.getDate() - Number(r)); $('#bk-awal').val(idTgl(s)); }
    $('#bk-akhir').val(idTgl(n)); muat();
  });

  if ($.fn.datepicker) {
    $('#bk-awal, #bk-akhir').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true, weekStart: 1 })
      .on('changeDate', function () { $('#bk-seg button').removeClass('on'); muat(); });
  }

  muat();
})();
</script>
