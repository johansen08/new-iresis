<?php
// View menu TIM PICKER -> Bonus Picker. Dimuat lewat AJAX (SPA); semua selector
// diawali #bp-root supaya tidak bocor ke halaman lain. Data dihitung server
// (Bonus_picker_fcd), view hanya menampilkan, mengurutkan, dan mencari.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Bonus Picker</strong></h3></div>
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
  #bp-root { --bp-ink:#1c2733; --bp-muted:#6b7a8c; --bp-line:#e4e9ef; --bp-bg:#f5f7fa; --bp-brand:#2563eb; --bp-brand-soft:#e8effd;
    --bp-ok:#15803d; --bp-ok-soft:#e3f6ea; --bp-gold:#b45309; --bp-gold-soft:#fdf0d8; --bp-none:#6b7a8c; --bp-none-soft:#eef1f5;
    --bp-sp:#6d28d9; --bp-sp-soft:#ede9fe; --bp-warn:#c2410c; --bp-warn-soft:#ffedd5;
    color:var(--bp-ink); padding:4px 0 24px; }
  #bp-root * { box-sizing:border-box; }
  #bp-root .bp-card { background:#fff; border:1px solid var(--bp-line); border-radius:12px; box-shadow:0 1px 2px rgba(16,24,40,.05); }

  /* header */
  #bp-root .bp-head { display:flex; flex-wrap:wrap; align-items:center; gap:8px 14px; margin-bottom:14px; }
  #bp-root .bp-head h2 { margin:0; font-size:22px; font-weight:700; display:flex; align-items:center; gap:8px; }
  #bp-root .bp-rentang { margin-left:auto; font-size:12px; color:var(--bp-muted); background:#fff; border:1px solid var(--bp-line); border-radius:999px; padding:4px 12px; }

  /* filter */
  #bp-root .bp-filter { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; padding:14px 16px; margin-bottom:14px; }
  #bp-root .bp-filter .bp-f { display:flex; flex-direction:column; gap:4px; }
  #bp-root .bp-filter label { font-size:12px; color:var(--bp-muted); font-weight:600; margin:0; }
  #bp-root .bp-filter .form-control { height:38px; border-radius:8px; box-shadow:none; }
  #bp-root .bp-tgl { width:138px; }
  #bp-root .bp-cari { width:220px; }
  #bp-root .bp-seg { display:inline-flex; border:1px solid var(--bp-line); border-radius:8px; overflow:hidden; height:38px; }
  #bp-root .bp-seg button { border:0; background:#fff; padding:0 14px; color:var(--bp-muted); cursor:pointer; border-right:1px solid var(--bp-line); font:inherit; }
  #bp-root .bp-seg button:last-child { border-right:0; }
  #bp-root .bp-seg button:hover { color:var(--bp-brand); background:var(--bp-brand-soft); }
  #bp-root .bp-seg button.on { background:var(--bp-brand); color:#fff; }
  #bp-root .bp-btn { height:38px; border-radius:8px; border:1px solid transparent; padding:0 16px; font:inherit; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
  #bp-root .bp-btn.pri { background:var(--bp-brand); color:#fff; }
  #bp-root .bp-btn.pri:hover { background:#1d4fd8; }
  #bp-root .bp-btn.ghost { background:#fff; color:var(--bp-ok); border-color:#b7e1c5; }
  #bp-root .bp-btn.ghost:hover { background:var(--bp-ok-soft); }
  #bp-root .bp-btn.sm { height:32px; padding:0 12px; font-size:13px; }
  #bp-root .bp-grow { flex:1 1 auto; }

  /* kartu ringkasan */
  #bp-root .bp-kpi { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:14px; }
  #bp-root .bp-k { padding:14px 16px; min-width:0; }
  #bp-root .bp-k .k { font-size:12px; color:var(--bp-muted); font-weight:600; display:flex; align-items:center; gap:6px; }
  #bp-root .bp-k .v { font-size:26px; font-weight:700; line-height:1.25; margin-top:2px; }
  #bp-root .bp-k .v small { font-size:14px; font-weight:600; color:var(--bp-muted); }
  #bp-root .bp-k .s { font-size:12px; color:var(--bp-muted); margin-top:2px; }

  /* progres */
  #bp-root .bp-bar { height:6px; background:var(--bp-none-soft); border-radius:99px; overflow:hidden; margin-top:6px; }
  #bp-root .bp-bar i { display:block; height:100%; border-radius:99px; background:var(--bp-brand); }
  #bp-root .bp-bar i.hi { background:#16a34a; } #bp-root .bp-bar i.mid { background:var(--bp-brand); } #bp-root .bp-bar i.lo { background:#ea580c; }
  #bp-root .bp-pct { font-weight:700; }
  #bp-root .bp-pct.hi { color:#16a34a; } #bp-root .bp-pct.mid { color:var(--bp-brand); } #bp-root .bp-pct.lo { color:#ea580c; }

  /* tabel utama */
  #bp-root .bp-tabelwrap { overflow:hidden; }
  #bp-root table.bp-tbl { width:100%; border-collapse:collapse; margin:0; }
  #bp-root .bp-tbl th { font-size:12px; color:var(--bp-muted); font-weight:600; background:#fafbfd; padding:11px 16px; border-bottom:1px solid var(--bp-line); white-space:nowrap; text-align:left; }
  #bp-root .bp-tbl td { padding:12px 16px; border-bottom:1px solid var(--bp-line); vertical-align:middle; }
  #bp-root .bp-tbl tbody tr:last-child td { border-bottom:0; }
  #bp-root .bp-tbl tbody tr:hover { background:#fafcff; }
  #bp-root .bp-tbl th.r, #bp-root .bp-tbl td.r { text-align:right; }
  #bp-root .bp-tbl th.c, #bp-root .bp-tbl td.c { text-align:center; }
  #bp-root th.bp-sort { cursor:pointer; user-select:none; }
  #bp-root th.bp-sort:hover { color:var(--bp-brand); }
  #bp-root .bp-absen { font-variant-numeric:tabular-nums; color:var(--bp-muted); }
  #bp-root .bp-nama { display:flex; align-items:center; gap:10px; font-weight:600; }
  #bp-root .bp-av { width:32px; height:32px; border-radius:50%; color:#fff; font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
  #bp-root .bp-rank { font-size:11px; font-weight:700; border-radius:99px; padding:1px 8px; }
  #bp-root .bp-rank.r1 { background:#fef3c7; color:#92400e; } #bp-root .bp-rank.r2 { background:#e5e7eb; color:#374151; } #bp-root .bp-rank.r3 { background:#fde4cf; color:#9a3412; }
  #bp-root .bp-bonus { font-weight:700; font-variant-numeric:tabular-nums; }
  #bp-root .bp-bonus.nol { color:#9aa5b1; font-weight:500; }
  #bp-root .bp-cap { min-width:170px; }
  #bp-root .bp-cap .t { display:flex; justify-content:space-between; gap:8px; align-items:baseline; }
  #bp-root .bp-cap .sub { font-size:12px; color:var(--bp-muted); margin-top:3px; }

  /* pill */
  #bp-root .bp-pill { display:inline-block; padding:2px 10px; border-radius:999px; font-weight:600; font-size:12px; white-space:nowrap; }
  #bp-root .bp-pill.ok { background:var(--bp-ok-soft); color:var(--bp-ok); } #bp-root .bp-pill.none { background:var(--bp-none-soft); color:var(--bp-none); }
  #bp-root .bp-pill.gold { background:var(--bp-gold-soft); color:var(--bp-gold); } #bp-root .bp-pill.sp { background:var(--bp-sp-soft); color:var(--bp-sp); }

  /* status kosong / memuat / error */
  #bp-root .bp-state { padding:44px 16px; text-align:center; color:var(--bp-muted); }
  #bp-root .bp-state i.fa { font-size:30px; display:block; margin-bottom:8px; color:#b8c2cd; }
  #bp-root .bp-state.err i.fa { color:#dc2626; }
  #bp-root .bp-state .bp-btn { margin-top:10px; }
  #bp-root .bp-skel td > span { display:block; height:14px; border-radius:6px; background:linear-gradient(90deg,#eef1f5 25%,#f7f9fb 50%,#eef1f5 75%); background-size:200% 100%; animation:bpshim 1.2s infinite; }
  @keyframes bpshim { from { background-position:200% 0; } to { background-position:-200% 0; } }

  /* ikon info (tooltip fixed supaya tidak terpotong tabel/jendela) */
  #bp-root .bp-info { display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:50%; background:var(--bp-brand-soft); color:var(--bp-brand);
    font-size:11px; font-weight:700; cursor:help; font-style:normal; outline:none; vertical-align:middle; }
  #bp-root .bp-info:focus { box-shadow:0 0 0 2px var(--bp-brand); }
  #bp-root .bp-tip { display:none; position:fixed; z-index:3000; width:300px; max-width:calc(100vw - 16px); background:#1c2733; color:#fff; padding:10px 12px; border-radius:8px;
    font-size:12px; font-weight:400; line-height:1.5; text-align:left; white-space:normal; box-shadow:0 8px 24px rgba(0,0,0,.28); pointer-events:none; }
  #bp-root .bp-tip.on { display:block; }
  #bp-root .bp-info .bp-tipsrc { display:none; }

  /* jendela detail */
  #bp-root .bp-ov { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(16,24,40,.5); display:none; align-items:center; justify-content:center; padding:16px; z-index:2000; }
  #bp-root .bp-ov.on { display:flex; }
  #bp-root .bp-modal { background:#fff; width:100%; max-width:760px; max-height:90vh; display:flex; flex-direction:column; border-radius:14px; overflow:hidden; outline:none; box-shadow:0 20px 50px rgba(0,0,0,.3); }
  #bp-root .bp-mh { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--bp-line); }
  #bp-root .bp-mh h4 { margin:0; font-size:18px; font-weight:700; }
  #bp-root .bp-mh .s { color:var(--bp-muted); font-size:12px; margin-top:2px; }
  #bp-root .bp-mh .act { display:flex; align-items:center; gap:8px; }
  #bp-root .bp-x { border:0; background:var(--bp-none-soft); width:32px; height:32px; border-radius:50%; font-size:20px; line-height:1; cursor:pointer; color:var(--bp-muted); }
  #bp-root .bp-x:hover { background:#e2e8f0; color:var(--bp-ink); }
  #bp-root .bp-msum { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; padding:14px 20px; border-bottom:1px solid var(--bp-line); background:#fafbfd; }
  #bp-root .bp-msum > div { background:#fff; border:1px solid var(--bp-line); border-radius:10px; padding:8px 12px; font-size:12px; color:var(--bp-muted); }
  #bp-root .bp-msum b { display:block; font-size:16px; color:var(--bp-ink); }
  #bp-root .bp-mb { overflow:auto; -webkit-overflow-scrolling:touch; }
  #bp-root .bp-mb table { width:100%; border-collapse:collapse; margin:0; min-width:520px; }
  #bp-root .bp-mb th { position:sticky; top:0; z-index:1; background:#fff; font-size:12px; color:var(--bp-muted); font-weight:600; padding:10px 14px; border-bottom:1px solid var(--bp-line); text-align:right; white-space:nowrap; }
  #bp-root .bp-mb th:first-child, #bp-root .bp-mb td:first-child { text-align:left; }
  #bp-root .bp-mb td { padding:9px 14px; border-bottom:1px solid #f0f3f6; text-align:right; font-variant-numeric:tabular-nums; }
  #bp-root .bp-mb tr.bp-spes td { background:#faf8ff; text-align:left; }
  #bp-root .bp-mb td:first-child, #bp-root .bp-mb td:last-child { white-space:nowrap; }
  #bp-root .bp-mb td .bp-hr { color:var(--bp-muted); font-size:11px; display:block; }

  /* tablet */
  @media (max-width: 991px) { #bp-root .bp-kpi { grid-template-columns:1fr 1fr; } #bp-root .bp-kpi .bp-k:last-child { grid-column:1 / -1; } }

  /* HP: tabel utama jadi kartu, jendela detail jadi lembar bawah */
  @media (max-width: 767px) {
    #bp-root .bp-head h2 { font-size:19px; } #bp-root .bp-rentang { margin-left:0; }
    #bp-root .bp-filter { padding:12px; gap:10px; }
    #bp-root .bp-filter .bp-f, #bp-root .bp-tgl, #bp-root .bp-cari { width:100%; flex:1 1 100%; }
    #bp-root .bp-f.dua { flex:1 1 calc(50% - 5px); width:auto; } #bp-root .bp-f.dua .bp-tgl { width:100%; }
    #bp-root .bp-seg { width:100%; } #bp-root .bp-seg button { flex:1; }
    #bp-root .bp-filter .bp-f > label:empty, #bp-root .bp-filter .bp-f > label.kosong { display:none; }
    #bp-root .bp-filter .bp-f > .bp-btn { width:100%; justify-content:center; }
    #bp-root .bp-f.bp-grow { display:none; }
    #bp-root .bp-btn.sm { width:auto !important; }
    #bp-root .bp-kpi { grid-template-columns:1fr; }
    #bp-root .bp-tabelwrap { border:0; box-shadow:none; background:transparent; }
    #bp-root .bp-tbl, #bp-root .bp-tbl tbody { display:block; }
    #bp-root .bp-tbl thead { display:none; }
    #bp-root .bp-tbl tbody tr { display:grid; grid-template-columns:1fr auto; grid-template-areas:"nama bonus" "cap cap" "absen aksi"; gap:8px 12px;
      background:#fff; border:1px solid var(--bp-line); border-radius:12px; padding:12px; margin-bottom:10px; align-items:center; }
    #bp-root .bp-tbl td { display:block; padding:0; border:0; text-align:left !important; }
    #bp-root .bp-tbl td.c-nama { grid-area:nama; } #bp-root .bp-tbl td.c-bonus { grid-area:bonus; text-align:right !important; }
    #bp-root .bp-tbl td.c-cap { grid-area:cap; } #bp-root .bp-tbl td.c-absen { grid-area:absen; font-size:12px; } #bp-root .bp-tbl td.c-absen::before { content:"No. Absen "; }
    #bp-root .bp-tbl td.c-aksi { grid-area:aksi; text-align:right !important; }
    #bp-root .bp-tbl tr.bp-skel, #bp-root .bp-tbl tr.bp-vacant { display:block; }
    #bp-root .bp-cap { min-width:0; }
    #bp-root .bp-ov { align-items:flex-end; padding:0; }
    #bp-root .bp-modal { max-width:none; max-height:94vh; border-radius:16px 16px 0 0; }
    #bp-root .bp-mh, #bp-root .bp-msum { padding-left:14px; padding-right:14px; }
    #bp-root .bp-msum { grid-template-columns:1fr 1fr; gap:8px; padding-top:10px; padding-bottom:10px; }
    #bp-root .bp-msum > div { padding:6px 10px; } #bp-root .bp-msum b { font-size:14px; }
    #bp-root .bp-mb table { min-width:0; font-size:12px; }
    #bp-root .bp-mb th { white-space:normal; padding:8px 4px; vertical-align:bottom; font-size:11px; }
    #bp-root .bp-mb td { padding:8px 4px; }
    #bp-root .bp-mb .bp-pill { padding:2px 7px; font-size:11px; }
    #bp-root .bp-mb th:first-child, #bp-root .bp-mb td:first-child { padding-left:14px; }
    #bp-root .bp-mb th:last-child, #bp-root .bp-mb td:last-child { padding-right:14px; }
    #bp-root .bp-mh .bp-btn .tx { display:none; }
  }
</style>

<div id="bp-root">
  <div class="bp-head">
    <h2>Bonus Picker
      <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">
        <b>Aturan perhitungan</b><br>
        &bull; Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin.<br>
        &bull; Net harian = total pick &minus; poin kesalahan.<br>
        &bull; Net <?= $fmt($aturan['target']) ?>&ndash;<?= $fmt($aturan['tier2'] - 1) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus1']) ?>/hari.<br>
        &bull; Net &ge; <?= $fmt($aturan['tier2']) ?> &rarr; bonus Rp <?= $fmt($aturan['bonus2']) ?>/hari.<br>
        &bull; Net &lt; <?= $fmt($aturan['target']) ?> &rarr; tidak ada bonus.<br>
        &bull; Hanya resi yang di-assign pukul 05.00&ndash;17.00.<br>
        &bull; Hanya NORMAL_PICKER; 1_SKU_PICKER tidak dihitung.<br>
        &bull; Hari bertugas sebagai petugas resi spesial tidak dihitung sama sekali.<br>
        &bull; Capai target = jumlah hari dengan net &ge; <?= $fmt($aturan['target']) ?>.<br>
        &bull; Persentase = hari capai target &divide; hari normal (tanpa hari spesial).
      </span></span>
    </h2>
    <div class="bp-rentang" id="bp-rentang"></div>
  </div>

  <div class="bp-card bp-filter">
    <div class="bp-f dua"><label for="bp-awal">Dari tanggal</label>
      <input type="text" id="bp-awal" class="form-control bp-tgl" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($awal)) ?>"></div>
    <div class="bp-f dua"><label for="bp-akhir">Sampai tanggal</label>
      <input type="text" id="bp-akhir" class="form-control bp-tgl" maxlength="10" autocomplete="off" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($akhir)) ?>"></div>
    <div class="bp-f"><label class="kosong">&nbsp;</label>
      <div class="bp-seg" id="bp-seg">
        <button type="button" class="bp-cepat" data-r="0">Hari ini</button>
        <button type="button" class="bp-cepat" data-r="6">7 hari</button>
        <button type="button" class="bp-cepat on" data-r="bulan">Bulan ini</button>
      </div></div>
    <div class="bp-f"><label class="kosong">&nbsp;</label>
      <button type="button" class="bp-btn pri" id="bp-tampilkan"><i class="fa fa-search"></i> Tampilkan</button></div>
    <div class="bp-f bp-grow"></div>
    <div class="bp-f"><label for="bp-cari">Cari picker</label>
      <input type="search" id="bp-cari" class="form-control bp-cari" placeholder="Nama atau no. absen"></div>
    <div class="bp-f"><label class="kosong">&nbsp;</label>
      <button type="button" class="bp-btn ghost" id="bp-export"><i class="fa fa-file-excel-o"></i> Export Excel</button></div>
  </div>

  <div class="bp-kpi">
    <div class="bp-card bp-k"><div class="k">Total bonus</div><div class="v" id="bp-s-bonus">-</div><div class="s" id="bp-s-bonus-s">&nbsp;</div></div>
    <div class="bp-card bp-k"><div class="k">Picker berbonus</div><div class="v" id="bp-s-picker">-</div><div class="s" id="bp-s-picker-s">&nbsp;</div></div>
    <div class="bp-card bp-k"><div class="k">Capaian hari normal
      <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">Hari capai target (net &ge; <?= $fmt($aturan['target']) ?>) dibagi seluruh hari normal semua picker. Hari petugas resi spesial dikeluarkan dari hitungan.</span></span></div>
      <div class="v" id="bp-s-hari">-</div>
      <div class="bp-bar" id="bp-s-bar" style="display:none"><i></i></div>
      <div class="s" id="bp-s-hari-s">&nbsp;</div></div>
  </div>

  <div class="bp-card bp-tabelwrap">
    <table class="bp-tbl">
      <thead><tr>
        <th class="bp-sort" data-k="absen" style="width:110px">No. Absen <span class="bp-arr"></span></th>
        <th class="bp-sort" data-k="nama">Nama <span class="bp-arr"></span></th>
        <th class="bp-sort r" data-k="bonus">Total Bonus <span class="bp-arr"></span></th>
        <th class="bp-sort" data-k="pct">Total Capai Target
          <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">Hari target tercapai (net &ge; <?= $fmt($aturan['target']) ?>) dari seluruh hari normal pada rentang tanggal. Hari petugas resi spesial tidak dihitung. Urutan mengikuti persentase.</span></span>
          <span class="bp-arr"></span></th>
        <th class="c" style="width:90px">Aksi</th>
      </tr></thead>
      <tbody id="bp-rows"></tbody>
    </table>
  </div>

  <div class="bp-ov" id="bp-ov">
    <div class="bp-modal" id="bp-modal" role="dialog" aria-modal="true" aria-labelledby="bp-m-nama" tabindex="-1">
      <div class="bp-mh">
        <div><h4 id="bp-m-nama"></h4><div class="s" id="bp-m-sub"></div></div>
        <div class="act">
          <button type="button" class="bp-btn ghost sm" id="bp-m-export"><i class="fa fa-file-excel-o"></i> <span class="tx">Export Excel</span></button>
          <button type="button" class="bp-x" id="bp-m-x" aria-label="Tutup">&times;</button>
        </div>
      </div>
      <div class="bp-msum" id="bp-m-sum"></div>
      <div class="bp-mb">
        <table>
          <thead><tr>
            <th>Tanggal</th>
            <th>Total Pick</th>
            <th>Jumlah Kesalahan</th>
            <th>Poin Kesalahan
              <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">Setiap 1 kesalahan = <?= (int) $aturan['poin'] ?> poin.</span></span></th>
            <th>Net
              <span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">Net = total pick &minus; poin kesalahan.<br>Emas = net &ge; <?= $fmt($aturan['tier2']) ?> (Rp <?= $fmt($aturan['bonus2']) ?>), hijau = <?= $fmt($aturan['target']) ?>&ndash;<?= $fmt($aturan['tier2'] - 1) ?> (Rp <?= $fmt($aturan['bonus1']) ?>), abu = di bawah target (tanpa bonus).</span></span></th>
            <th>Bonus</th>
          </tr></thead>
          <tbody id="bp-m-rows"></tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="bp-tip" id="bp-tip"></div>
</div>

<script>
(function () {
  var $root = $('#bp-root');
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
  var $tip = $('#bp-tip');
  function tampilTip(el) {
    var src = $(el).find('.bp-tipsrc').html();
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
  $root.on('mouseenter focusin', '.bp-info', function () { tampilTip(this); });
  $root.on('mouseleave focusout', '.bp-info', sembunyiTip);

  function skeleton() {
    var baris = '';
    for (var i = 0; i < 5; i++) {
      baris += '<tr class="bp-skel"><td class="c-absen"><span style="width:50px"></span></td><td class="c-nama"><span style="width:140px"></span></td>' +
        '<td class="c-bonus r"><span style="width:90px;margin-left:auto"></span></td><td class="c-cap"><span style="width:100%"></span></td><td class="c-aksi"><span style="width:50px;margin:auto"></span></td></tr>';
    }
    $('#bp-rows').html(baris);
  }
  function keadaan(ikon, teks, tombol) {
    $('#bp-rows').html('<tr class="bp-vacant"><td colspan="5" class="bp-state' + (ikon === 'fa-exclamation-triangle' ? ' err' : '') + '" style="display:table-cell"><i class="fa ' + ikon + '"></i>' + teks +
      (tombol ? '<br><button type="button" class="bp-btn pri sm" id="bp-ulang"><i class="fa fa-refresh"></i> Coba lagi</button>' : '') + '</td></tr>');
  }

  function siapkan(list) {
    return list.map(function (p) {
      var normal = 0, spesial = 0;
      p.det.forEach(function (x) { if (x.spesial) { spesial++; } else { normal++; } });
      p.hari_normal = normal; p.hari_spesial = spesial;
      p.pct = normal > 0 ? Math.round(p.hari / normal * 100) : -1;
      return p;
    });
  }

  function muat() {
    var a = keIso($('#bp-awal').val()), b = keIso($('#bp-akhir').val());
    if (!a || !b) {
      keadaan('fa-calendar-times-o', 'Format tanggal harus dd/mm/yyyy, contoh 01/10/2026.', false);
      return;
    }
    skeleton();
    $.ajax({
      url: 'bonus-picker/get-data', type: 'POST', dataType: 'json', data: { awal: a, akhir: b },
      success: function (r) {
        if (r.code === 200) {
          data = siapkan(r.data.pickers); awalAktif = a; akhirAktif = b;
          $('#bp-rentang').text(tglPendek(a) + ' – ' + tglPendek(b));
          render();
        } else { keadaan('fa-exclamation-triangle', esc(r.message), false); }
      },
      error: function (xhr) {
        keadaan('fa-exclamation-triangle', 'Gagal memuat data. ' + esc((xhr.responseText || '').substring(0, 160)), true);
      }
    });
  }

  function render() {
    var q = $.trim($('#bp-cari').val()).toLowerCase();
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
      keadaan('fa-user-o', q ? 'Tidak ada picker yang cocok dengan pencarian.' : 'Tidak ada data picker pada rentang ini.', false);
    } else {
      $('#bp-rows').html(rows.map(function (p) {
        var cap;
        if (p.hari_normal > 0) {
          var k = kelas(p.pct);
          cap = '<div class="bp-cap"><div class="t"><b>' + p.hari + ' hari</b><span class="bp-pct ' + k + '">' + p.pct + '%</span></div>' +
            '<div class="bp-bar"><i class="' + k + '" style="width:' + p.pct + '%"></i></div>' +
            '<div class="sub">dari ' + p.hari_normal + ' hari normal' + (p.hari_spesial ? ' · ' + p.hari_spesial + ' hari spesial' : '') + '</div></div>';
        } else {
          cap = '<div class="bp-cap"><span class="bp-pill sp">Hanya hari spesial</span></div>';
        }
        var badge = rank[p.kode] ? ' <span class="bp-rank r' + rank[p.kode] + '">#' + rank[p.kode] + '</span>' : '';
        return '<tr><td class="c-absen"><span class="bp-absen">' + absen(p.absen) + '</span></td>' +
          '<td class="c-nama"><div class="bp-nama"><span class="bp-av" style="background:' + warnaAvatar(p.nama) + '">' + esc(p.nama.charAt(0)) + '</span><span>' + esc(p.nama) + badge + '</span></div></td>' +
          '<td class="c-bonus r bp-bonus' + (p.bonus ? '' : ' nol') + '">' + rp(p.bonus) + '</td>' +
          '<td class="c-cap">' + cap + '</td>' +
          '<td class="c-aksi c"><button type="button" class="bp-btn pri sm bp-detail" data-k="' + p.kode + '">Detail</button></td></tr>';
      }).join(''));
    }

    // Kartu ringkasan dihitung dari baris yang tampil (ikut pencarian).
    var bonus = 0, berbonus = 0, hari = 0, normal = 0, spesial = 0;
    rows.forEach(function (p) { bonus += p.bonus; hari += p.hari; normal += p.hari_normal; spesial += p.hari_spesial; if (p.bonus > 0) { berbonus++; } });
    var pct = normal > 0 ? Math.round(hari / normal * 100) : null;
    $('#bp-s-bonus').text(rp(bonus));
    $('#bp-s-bonus-s').text(rows.length ? 'rata-rata ' + rp(rows.length ? Math.round(bonus / rows.length) : 0) + ' per picker' : ' ');
    $('#bp-s-picker').html(berbonus + ' <small>/ ' + rows.length + ' picker</small>');
    $('#bp-s-picker-s').text(rows.length ? (rows.length - berbonus) + ' picker belum capai bonus' : ' ');
    $('#bp-s-hari').html(pct === null ? '-' : pct + '% <small>(' + hari + ' / ' + normal + ' hari)</small>');
    if (pct === null) { $('#bp-s-bar').hide(); }
    else { $('#bp-s-bar').show().find('i').attr('class', kelas(pct)).css('width', pct + '%'); }
    $('#bp-s-hari-s').text(spesial ? spesial + ' hari petugas spesial tidak dihitung' : ' ');

    $root.find('th.bp-sort').each(function () {
      $(this).find('.bp-arr').text($(this).data('k') === sortK ? (sortDir > 0 ? '▲' : '▼') : '');
    });
  }

  function buka(kode) {
    var p = data.filter(function (x) { return x.kode === kode; })[0];
    if (!p) { return; }
    kodeAktif = p.kode;
    var tp = 0, ts = 0;
    p.det.forEach(function (x) { if (!x.spesial) { tp += x.pick; ts += x.salah; } });
    $('#bp-m-nama').text(p.nama);
    $('#bp-m-sub').text('No. absen ' + absen(p.absen) + ' · ' + tglPendek(awalAktif) + ' – ' + tglPendek(akhirAktif));
    var capaian = p.hari_normal > 0
      ? '<b>' + p.hari + ' / ' + p.hari_normal + ' hari <span class="bp-pct ' + kelas(p.pct) + '">' + p.pct + '%</span></b><div class="bp-bar"><i class="' + kelas(p.pct) + '" style="width:' + p.pct + '%"></i></div>'
      : '<b>-</b>';
    $('#bp-m-sum').html(
      '<div>Total bonus<b>' + rp(p.bonus) + '</b></div>' +
      '<div>Capai target (hari normal)' + capaian + '</div>' +
      '<div>Total pick<b>' + num(tp) + '</b></div>' +
      '<div>Kesalahan<b>' + ts + ' <span style="font-weight:500;font-size:12px;color:#6b7a8c">(' + (ts * POIN) + ' poin)</span></b></div>' +
      (p.hari_spesial ? '<div>Hari petugas spesial<b>' + p.hari_spesial + ' hari</b></div>' : ''));
    $('#bp-m-rows').html(p.det.map(function (x) {
      if (x.spesial) {
        return '<tr class="bp-spes"><td>' + tglBaris(x.tgl) + '</td><td colspan="5"><span class="bp-pill sp">Petugas Resi Spesial</span> ' +
          '<span class="bp-info" tabindex="0" onclick="event.stopPropagation()">i<span class="bp-tipsrc">Pada hari bertugas mengambil resi spesial, resi picker tidak dihitung sama sekali: tanpa bonus dan tidak masuk persentase capaian.</span></span></td></tr>';
      }
      var cls = x.net >= TIER2 ? 'gold' : (x.net >= TARGET ? 'ok' : 'none');
      return '<tr><td>' + tglBaris(x.tgl) + '</td><td>' + num(x.pick) + '</td><td>' + x.salah + '</td><td>' + x.poin +
        '</td><td><span class="bp-pill ' + cls + '">' + num(x.net) + '</span></td>' +
        '<td>' + (x.bonus ? '<b>' + rp(x.bonus) + '</b>' : '<span style="color:#9aa5b1">-</span>') + '</td></tr>';
    }).join(''));
    $('#bp-ov').addClass('on');
    $('#bp-modal').trigger('focus');
  }
  function tutup() { sembunyiTip(); $('#bp-ov').removeClass('on'); }

  $root.on('click', '.bp-detail', function () { buka(parseInt($(this).data('k'), 10)); });
  $root.on('click', '#bp-m-x', tutup);
  $root.on('click', '#bp-ov', function (e) { if (e.target === this) { tutup(); } });
  $root.on('keydown', '#bp-ov', function (e) { if (e.key === 'Escape') { tutup(); } });
  $root.on('click', '#bp-m-export', function () {
    // Pakai rentang yang dimuat di tabel (bukan isi kotak tanggal yang mungkin sudah diubah).
    window.location.href = '<?= base_url('bonus-picker/export-excel-detail') ?>?kode=' + kodeAktif + '&awal=' + awalAktif + '&akhir=' + akhirAktif;
  });
  $root.on('click', '#bp-tampilkan, #bp-ulang', muat);
  $root.on('click', '#bp-export', function () {
    var a = keIso($('#bp-awal').val()), b = keIso($('#bp-akhir').val());
    if (!a || !b) { muat(); return; } // munculkan pesan format tanggal
    window.location.href = '<?= base_url('bonus-picker/export-excel') ?>?awal=' + a + '&akhir=' + b;
  });
  $root.on('change', '#bp-awal, #bp-akhir', function () { $('#bp-seg button').removeClass('on'); muat(); });
  $root.on('input', '#bp-cari', render);
  $root.on('click', 'th.bp-sort', function () {
    var k = $(this).data('k');
    sortDir = (sortK === k) ? -sortDir : ((k === 'nama' || k === 'absen') ? 1 : -1);
    sortK = k; render();
  });
  $root.on('click', '.bp-cepat', function () {
    var r = $(this).data('r'), n = new Date();
    $('#bp-seg button').removeClass('on'); $(this).addClass('on');
    if (r === 'bulan') { $('#bp-awal').val(idTgl(new Date(n.getFullYear(), n.getMonth(), 1))); }
    else { var s = new Date(n); s.setDate(s.getDate() - Number(r)); $('#bp-awal').val(idTgl(s)); }
    $('#bp-akhir').val(idTgl(n)); muat();
  });

  if ($.fn.datepicker) {
    $('#bp-awal, #bp-akhir').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true, weekStart: 1 })
      .on('changeDate', function () { $('#bp-seg button').removeClass('on'); muat(); });
  }

  muat();
})();
</script>
