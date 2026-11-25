<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<style>
/* Dashboard Retur New: filter periode -> progres 6 tahap (bar bertumpuk + 6 kartu) -> peringkat Marketplace, Kurir, Toko.
   Gaya mengikuti Daftar Retur Penjualan (putih, aksen teal, kontrol 38 px, teks 13). Semua kelas berawalan drb- supaya tidak bentrok
   dengan Bootstrap/tema. Angka datang dari endpoint retur/get-dashboard-retur-new (sel hasil Daftar_retur_fcd::get_list). */
#drb { --fg:#111827; --muted:#6b7280; --line:#e5e7eb; --line2:#d1d5db; --soft:#f9fafb; --accent:#0f766e; --accent-d:#115e59; --accent-bg:#f0fdfa; --accent-bg2:#ccfbf1;
  --s0:#ef4444; --s1:#f59e0b; --s2:#3b82f6; --s3:#8b5cf6; --s4:#14b8a6; --s5:#15803d;
  font-size:13px; color:var(--fg); line-height:1.45; display:flex; flex-direction:column; gap:14px; padding:4px 0 24px; }
#drb * { box-sizing:border-box; }
#drb .drb-num { font-variant-numeric:tabular-nums; }
#drb .drb-head { display:flex; flex-wrap:wrap; align-items:center; gap:10px 14px; background:#fff; border-radius:8px; padding:14px 20px; }
#drb .drb-head h1 { margin:0; font-size:17px; font-weight:700; }
#drb .drb-sub { color:var(--muted); font-size:12px; margin-top:2px; }
#drb .drb-acts { margin-left:auto; display:flex; flex-wrap:wrap; align-items:center; gap:8px 10px; }
#drb .drb-seg { display:inline-flex; border:1px solid var(--line2); border-radius:6px; overflow:hidden; height:38px; background:#fff; }
#drb .drb-seg button { border:0; background:transparent; color:var(--muted); font-size:13px; font-weight:600; font-family:inherit; padding:0 14px; cursor:pointer; border-right:1px solid var(--line2); }
#drb .drb-seg button:last-child { border-right:0; }
#drb .drb-seg button:hover { color:var(--accent); }
#drb .drb-seg button.on { background:var(--accent); color:#fff; }
#drb button:focus-visible, #drb input:focus-visible { outline:0; box-shadow:0 0 0 3px rgba(15,118,110,.3); }
#drb .drb-btn { height:38px; padding:0 14px; border:1px solid var(--line2); border-radius:6px; background:#fff; color:var(--fg); font-size:13px; font-weight:600; font-family:inherit; cursor:pointer; }
#drb .drb-btn:hover { border-color:var(--accent); color:var(--accent); }
#drb .drb-btn[disabled] { opacity:.45; cursor:not-allowed; }
#drb .drb-info { display:flex; flex-wrap:wrap; gap:8px; align-items:center; background:#fff; border-radius:8px; padding:10px 20px; }
#drb .drb-info[hidden] { display:none; }
#drb .drb-info .drb-lbl { color:var(--muted); font-size:12px; }
#drb .drb-chip { display:inline-flex; align-items:center; gap:6px; height:28px; padding:0 4px 0 10px; border-radius:999px; background:var(--accent-bg2); color:var(--accent-d); font-weight:600; font-size:12px; }
#drb .drb-chip button { width:20px; height:20px; border:0; border-radius:50%; background:transparent; color:inherit; cursor:pointer; font-size:14px; line-height:20px; padding:0; }
#drb .drb-chip button:hover { background:rgba(0,0,0,.12); }
#drb .drb-warn { background:#fef3c7; color:#92400e; border-radius:6px; padding:8px 12px; font-size:12px; }
#drb .drb-warn[hidden] { display:none; }
#drb .drb-err { background:#fee2e2; color:#991b1b; border-radius:6px; padding:10px 14px; font-size:12.5px; white-space:pre-wrap; word-break:break-word; }
#drb .drb-err[hidden] { display:none; }

#drb .drb-card { background:#fff; border-radius:8px; padding:16px 20px 18px; min-width:0; position:relative; }
#drb .drb-card h2 { margin:0; font-size:14px; font-weight:700; }
#drb .drb-cap { color:var(--muted); font-size:12px; margin-top:2px; }
#drb .drb-cardh { display:flex; flex-wrap:wrap; align-items:baseline; justify-content:space-between; gap:4px 12px; margin-bottom:14px; }
#drb .drb-big { font-size:22px; font-weight:700; }
#drb .drb-big small { font-size:12px; font-weight:500; color:var(--muted); margin-left:4px; }
#drb.drb-muat .drb-card, #drb.drb-muat .drb-head ~ .drb-ranks { opacity:.45; pointer-events:none; transition:opacity .15s; }

#drb .drb-stack { display:flex; height:30px; border-radius:6px; overflow:hidden; background:var(--soft); gap:2px; }
#drb .drb-seg1 { position:relative; min-width:0; transition:flex-grow .5s ease; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11.5px; font-weight:700; overflow:hidden; white-space:nowrap; }
#drb .drb-seg1:hover { filter:brightness(1.1); }
#drb .drb-seg1.dim { opacity:.3; }
#drb .drb-seg1:focus-visible { outline:2px solid var(--fg); outline-offset:-2px; }
#drb .drb-stages { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:10px; margin-top:14px; }
#drb .drb-st { text-align:left; border:1px solid var(--line); border-radius:8px; background:#fff; padding:10px 12px 11px; cursor:pointer; font:inherit; color:inherit; transition:border-color .15s, box-shadow .15s, background .15s; }
#drb .drb-st:hover { border-color:var(--c); }
#drb .drb-st.on { border-color:var(--c); box-shadow:0 0 0 3px rgba(15,118,110,.15); background:var(--soft); }
#drb .drb-st .nm { display:flex; align-items:center; gap:7px; font-size:12px; color:var(--muted); font-weight:600; }
#drb .drb-st .dot { width:9px; height:9px; border-radius:50%; background:var(--c); flex:none; }
#drb .drb-st .v { font-size:20px; font-weight:700; margin-top:6px; line-height:1.1; }
#drb .drb-st .p { font-size:12px; color:var(--muted); margin-top:2px; }
#drb .drb-st .p b { color:var(--fg); }
#drb .drb-st .mini { height:4px; border-radius:2px; background:var(--line); margin-top:9px; overflow:hidden; }
#drb .drb-st .mini i { display:block; height:100%; background:var(--c); border-radius:2px; transition:width .5s ease; }

#drb .drb-ranks { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; align-items:start; }
#drb .drb-rk { display:flex; flex-direction:column; gap:2px; }
#drb .drb-rrow { display:grid; grid-template-columns:40px minmax(0,1fr) auto; gap:2px 10px; align-items:center; padding:8px 8px; margin:0 -8px; border-radius:6px; cursor:pointer; border:0; background:transparent; text-align:left; font:inherit; color:inherit; width:calc(100% + 16px); }
#drb .drb-rrow:hover { background:var(--soft); }
#drb .drb-rrow.on { background:var(--accent-bg); box-shadow:inset 0 0 0 1.5px var(--accent); }
#drb .drb-rrow.dim { opacity:.45; }
#drb .drb-badge { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:22px; padding:0 7px; border-radius:999px; font-size:11px; font-weight:700; background:var(--soft); color:var(--muted); border:1px solid var(--line); }
#drb .drb-badge.r1 { background:#fef3c7; color:#92400e; border-color:#f59e0b; }
#drb .drb-badge.r2 { background:#e5e7eb; color:#374151; border-color:var(--line2); }
#drb .drb-badge.r3 { background:#fde4d0; color:#9a3412; border-color:#ea8b4f; }
#drb .drb-rname { font-weight:600; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
#drb .drb-rname small { color:var(--muted); font-weight:500; margin-left:6px; font-size:11.5px; }
#drb .drb-rnum { text-align:right; white-space:nowrap; }
#drb .drb-rnum b { font-size:14px; }
#drb .drb-rnum span { display:inline-block; min-width:48px; color:var(--muted); font-size:12px; }
#drb .drb-rbar { grid-column:2 / 4; height:8px; border-radius:4px; background:var(--soft); overflow:hidden; display:flex; }
#drb .drb-rbar-w { transition:width .5s ease; display:flex; height:100%; }
#drb .drb-rbar-w i { display:block; height:100%; }
#drb .drb-kosong { padding:22px 8px; text-align:center; color:var(--muted); }
#drb .drb-tools { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
#drb .drb-tools input { height:38px; width:240px; max-width:100%; padding:0 12px 0 34px; border:1px solid var(--line2); border-radius:6px; background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5' stroke-linecap='round'><circle cx='11' cy='11' r='7'/><path d='M20 20l-3.5-3.5'/></svg>") no-repeat 12px center; color:var(--fg); font-size:13px; font-family:inherit; }
#drb .drb-tools input:hover { border-color:var(--accent); }
#drb .drb-tools input:focus { outline:0; border-color:var(--accent); box-shadow:0 0 0 3px rgba(15,118,110,.2); }
#drb .drb-rk.toko { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); column-gap:36px; }
#drb .drb-rk.toko.scroll { max-height:520px; overflow-y:auto; padding-right:6px; }
#drb .drb-rk.toko .drb-rrow.lain { grid-column:1 / -1; margin-top:6px; background:var(--soft); border:1px dashed var(--line2); padding:10px 12px; width:100%; margin-left:0; margin-right:0; }
#drb .drb-rk.toko .drb-rrow.lain:hover, #drb .drb-rk.toko .drb-rrow.lain.open { border-color:var(--accent); }
#drb .drb-rrow.lain .chev { font-style:normal; margin-left:12px; color:var(--accent); font-size:12px; font-weight:600; }
#drb .drb-rrow.lain.open .chev { color:var(--muted); }
#drb .drb-lain-detail { grid-column:1 / -1; display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); column-gap:36px; max-height:440px; overflow-y:auto; padding:6px 14px 6px 12px; margin-top:6px; border:1px solid var(--line); border-radius:8px; }
#drb .drb-rk.toko .drb-kosong { grid-column:1 / -1; }
#drb .drb-legend { display:flex; flex-wrap:wrap; gap:6px 14px; font-size:12px; color:var(--muted); }
#drb .drb-legend span { display:inline-flex; align-items:center; gap:6px; }
#drb .drb-legend i { width:9px; height:9px; border-radius:2px; display:inline-block; }
#drb .drb-legend strong { color:var(--fg); }
.drb-tip { position:fixed; z-index:3000; pointer-events:none; background:#111827; color:#fff; font-size:12px; padding:7px 10px; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,.25); opacity:0; transition:opacity .1s; max-width:260px; }
.drb-tip.on { opacity:1; }
.drb-tip b { display:block; margin-bottom:3px; }
.drb-tip div { display:flex; justify-content:space-between; gap:14px; }
@media (max-width:1100px) {
  #drb .drb-ranks { grid-template-columns:1fr; } #drb .drb-rk.toko, #drb .drb-lain-detail { grid-template-columns:1fr; }
  #drb .drb-stages { grid-template-columns:repeat(3, minmax(0,1fr)); }
}
@media (max-width:560px) { #drb .drb-stages { grid-template-columns:repeat(2, minmax(0,1fr)); } #drb .drb-seg1 span { display:none; } }
@media (prefers-reduced-motion: reduce) { #drb * { transition:none !important; } }
</style>

<div id="drb" class="drb-muat">
  <div class="drb-head">
    <div>
      <h1>Dashboard Retur New</h1>
      <div class="drb-sub" id="drb_sub">Retur · tab Semua pada Daftar Retur Penjualan, disaring menurut tanggal tahap</div>
    </div>
    <div class="drb-acts">
      <div class="drb-seg" id="drb_periode" role="group" aria-label="Periode">
        <button type="button" data-v="7">7 hari</button>
        <button type="button" data-v="30">30 hari</button>
        <button type="button" data-v="90">90 hari</button>
        <button type="button" data-v="tahun" class="on">Tahun ini</button>
        <button type="button" data-v="semua">Semua</button>
      </div>
      <button type="button" class="drb-btn" id="drb_reset" disabled>Atur ulang filter</button>
      <button type="button" class="drb-btn" id="drb_segar" title="Hitung ulang dari data terbaru (lewati cache)">Segarkan</button>
    </div>
  </div>

  <div class="drb-err" id="drb_err" hidden></div>
  <div class="drb-info" id="drb_chips" hidden></div>

  <section class="drb-card">
    <div class="drb-cardh">
      <div>
        <h2>Progres Retur</h2>
        <div class="drb-cap">Posisi terakhir tiap retur. Klik tahap untuk menyaring tiga peringkat di bawah. <span id="drb_waktu"></span></div>
      </div>
      <div class="drb-big drb-num"><span id="drb_tot">0</span><small>retur</small></div>
    </div>
    <div class="drb-stack" id="drb_stack" role="list"></div>
    <div class="drb-stages" id="drb_stages"></div>
  </section>

  <div class="drb-ranks">
    <section class="drb-card"><div class="drb-cardh"><div><h2>Retur per Marketplace</h2><div class="drb-cap" id="drb_cap_mp"></div></div></div><div class="drb-rk" id="drb_rk_mp"></div></section>
    <section class="drb-card"><div class="drb-cardh"><div><h2>Retur per Kurir</h2><div class="drb-cap" id="drb_cap_kurir"></div><div class="drb-cap">Nama kurir digabung bila mengandung SPX, J&amp;T, GoTo, JNE, Ninja, atau LEX ID di awal, tengah, atau akhir nama.</div></div></div><div class="drb-rk" id="drb_rk_kurir"></div></section>
  </div>

  <section class="drb-card">
    <div class="drb-cardh">
      <div><h2>Retur per Toko</h2><div class="drb-cap" id="drb_cap_toko"></div></div>
      <div class="drb-tools"><input type="search" id="drb_toko_q" placeholder="Cari nama toko…" aria-label="Cari toko" autocomplete="off"></div>
    </div>
    <div class="drb-rk toko" id="drb_rk_toko"></div>
  </section>

  <div class="drb-card" style="padding-top:12px;padding-bottom:12px"><div class="drb-legend" id="drb_legend"></div></div>
</div>
<div class="drb-tip" id="drb_tip"></div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var TAHAP = [
        { n: 'Belum Terima',       c: 'var(--s0)' },
        { n: 'Sudah Terima',       c: 'var(--s1)' },
        { n: 'Sudah Buka',         c: 'var(--s2)' },
        { n: 'Sudah Serah Terima', c: 'var(--s3)' },
        { n: 'Sudah Verifikasi',   c: 'var(--s4)' },
        { n: 'Selesai',            c: 'var(--s5)' }
    ];
    var DIMS = [
        { k: 'mp',    el: 'drb_rk_mp',    cap: 'drb_cap_mp',    lbl: 'Marketplace' },
        { k: 'kurir', el: 'drb_rk_kurir', cap: 'drb_cap_kurir', lbl: 'Kurir' },
        { k: 'toko',  el: 'drb_rk_toko',  cap: 'drb_cap_toko',  lbl: 'Toko' }
    ];
    var TOKO_LIMIT = 10, tokoQ = '', tokoAll = false;
    var cells = [], tokoMp = {};
    var F = { mp: null, kurir: null, toko: null, stage: null };
    var periode = 'tahun', reqAktif = null;

    var $d = function (id) { return document.getElementById(id); };
    var fmt = function (n) { return n.toLocaleString('id-ID'); };
    var pct = function (n, t) { return t ? (n / t * 100) : 0; };
    var fp = function (p) { return p.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%'; };
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function tgl(d) { var m = d.getMonth() + 1, h = d.getDate(); return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (h < 10 ? '0' : '') + h; }

    function rentang() {
        var now = new Date(), a = new Date();
        if (periode === 'tahun') a = new Date(now.getFullYear(), 0, 1);
        else if (periode === 'semua') a = new Date(2000, 0, 1);
        else a.setDate(a.getDate() - (parseInt(periode, 10) - 1));
        return { start: tgl(a), end: tgl(now) };
    }

    // ---------------------------------------------------------------- data
    function muat(segarkan) {
        var r = rentang();
        if (reqAktif) reqAktif.abort();
        $('#drb').addClass('drb-muat');
        $('#drb_err').prop('hidden', true);
        $('#drb_segar').prop('disabled', true).text('Menghitung…');
        reqAktif = $.ajax({
            url: BASE + 'retur/get-dashboard-retur-new', type: 'POST', dataType: 'text', timeout: 600000,
            data: { start_date: r.start, end_date: r.end, segarkan: segarkan ? '1' : '0' }
        }).done(function (txt) {
            var j;
            try { j = JSON.parse(txt); } catch (e) { return galat('Balasan server bukan JSON. Balasan mentah:\n' + String(txt).substring(0, 1500)); }
            if (!j.ok) return galat(j.error || 'Gagal memuat dashboard.');
            var K = j.kamus;
            cells = j.sel.map(function (c) { return { mp: K.mp[c[0]], kurir: K.kurir[c[1]], raw: K.raw[c[2]], toko: K.toko[c[3]], st: c[4], n: c[5] }; });
            tokoMp = {};
            cells.forEach(function (c) { if (!(c.toko in tokoMp)) tokoMp[c.toko] = c.mp; });
            $d('drb_waktu').textContent = 'Dihitung pukul ' + j.waktu + '.';
            $d('drb_sub').textContent = 'Retur · tab Semua pada Daftar Retur Penjualan · tanggal tahap ' + j.start.split('-').reverse().join('/') + ' s/d ' + j.end.split('-').reverse().join('/');
            // buang filter yang tidak ada lagi di data periode ini
            DIMS.forEach(function (d) { if (F[d.k] && !cells.some(function (c) { return c[d.k] === F[d.k]; })) F[d.k] = null; });
            render();
        }).fail(function (xhr, st) {
            if (st === 'abort') return;
            galat('Gagal menghubungi server (' + st + '). ' + (xhr.responseText ? String(xhr.responseText).substring(0, 800) : ''));
        }).always(function () {
            $('#drb').removeClass('drb-muat');
            $('#drb_segar').prop('disabled', false).text('Segarkan');
        });
    }
    function galat(pesan) { $('#drb_err').text(pesan).prop('hidden', false); }

    function rows(ignore) {
        return cells.filter(function (c) {
            if (ignore !== 'mp' && F.mp && c.mp !== F.mp) return false;
            if (ignore !== 'kurir' && F.kurir && c.kurir !== F.kurir) return false;
            if (ignore !== 'toko' && F.toko && c.toko !== F.toko) return false;
            if (ignore !== 'stage' && F.stage !== null && c.st !== F.stage) return false;
            return true;
        });
    }
    function jumlah(list) { return list.reduce(function (a, c) { return a + c.n; }, 0); }

    // ---------------------------------------------------------------- tooltip
    var tip = $d('drb_tip');
    function tipOn(el, html) {
        el.addEventListener('mouseenter', function () { tip.innerHTML = html; tip.classList.add('on'); });
        el.addEventListener('mousemove', function (e) {
            var x = e.clientX + 14, y = e.clientY + 14, w = tip.offsetWidth, h = tip.offsetHeight;
            if (x + w > window.innerWidth - 8) x = e.clientX - w - 14;
            if (y + h > window.innerHeight - 8) y = e.clientY - h - 14;
            tip.style.left = x + 'px'; tip.style.top = y + 'px';
        });
        el.addEventListener('mouseleave', function () { tip.classList.remove('on'); });
    }

    // ---------------------------------------------------------------- progres
    function toggleStage(i) { F.stage = F.stage === i ? null : i; render(); }

    function renderProgress() {
        var list = rows('stage'), c = [0, 0, 0, 0, 0, 0];
        list.forEach(function (x) { c[x.st] += x.n; });
        var total = c.reduce(function (a, b) { return a + b; }, 0);
        $d('drb_tot').textContent = fmt(total);

        var stack = $d('drb_stack'); stack.innerHTML = '';
        TAHAP.forEach(function (s, i) {
            var p = pct(c[i], total), el = document.createElement('div');
            el.className = 'drb-seg1' + (F.stage !== null && F.stage !== i ? ' dim' : '');
            el.style.background = s.c; el.style.flexGrow = c[i]; el.style.flexBasis = '0';
            el.setAttribute('role', 'listitem'); el.tabIndex = 0;
            el.innerHTML = p >= 7 ? '<span class="drb-num">' + fp(p) + '</span>' : '';
            el.onclick = function () { toggleStage(i); };
            el.onkeydown = function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleStage(i); } };
            tipOn(el, '<b>' + s.n + '</b><div><span>Total</span><span class="drb-num">' + fmt(c[i]) + '</span></div><div><span>Persentase</span><span class="drb-num">' + fp(p) + '</span></div>');
            stack.appendChild(el);
        });

        var box = $d('drb_stages'); box.innerHTML = '';
        TAHAP.forEach(function (s, i) {
            var p = pct(c[i], total), b = document.createElement('button');
            b.type = 'button';
            b.className = 'drb-st' + (F.stage === i ? ' on' : ''); b.style.setProperty('--c', s.c);
            b.setAttribute('aria-pressed', F.stage === i);
            b.innerHTML = '<div class="nm"><span class="dot"></span>' + s.n + '</div><div class="v drb-num">' + fmt(c[i]) +
                '</div><div class="p drb-num"><b>' + fp(p) + '</b> dari total</div><div class="mini"><i style="width:' + p + '%"></i></div>';
            b.onclick = function () { toggleStage(i); };
            box.appendChild(b);
        });
    }

    // ---------------------------------------------------------------- peringkat
    function renderRank(d) {
        var list = rows(d.k), total = jumlah(list), cnt = {}, brk = {}, vr = {};
        list.forEach(function (r) {
            var key = r[d.k]; cnt[key] = (cnt[key] || 0) + r.n;
            var b = (brk[key] = brk[key] || [0, 0, 0, 0, 0, 0]); b[r.st] += r.n;
            if (d.k === 'kurir') { var v = (vr[key] = vr[key] || {}); v[r.raw] = (v[r.raw] || 0) + r.n; }
        });
        var names = Object.keys(cnt).sort(function (a, b) { return cnt[b] - cnt[a] || a.localeCompare(b); });
        var max = names.length ? cnt[names[0]] : 1;
        var cap = fmt(total) + ' retur · ' + names.length + ' ' + d.lbl.toLowerCase() + ' · diurut total terbanyak';
        var box = $d(d.el); box.innerHTML = '';
        if (!names.length) { $d(d.cap).textContent = cap; box.classList.remove('scroll'); box.innerHTML = '<div class="drb-kosong">Tidak ada retur untuk filter ini.</div>'; return; }

        var show = names.map(function (nm, i) { return [nm, i + 1]; }), rest = [];
        if (d.k === 'toko') {
            var q = tokoQ.trim().toLowerCase();
            if (q) show = show.filter(function (x) { return x[0].toLowerCase().indexOf(q) > -1; });
            var tot = show.length;
            if (!q) { rest = show.slice(TOKO_LIMIT); show = show.slice(0, TOKO_LIMIT); }
            cap += q ? ' · ' + tot + ' cocok' : ' · 10 teratas' + (rest.length ? ' + Lainnya (' + rest.length + ' toko)' : '');
            box.classList.toggle('scroll', !!q);
            if (!show.length) { $d(d.cap).textContent = cap; box.innerHTML = '<div class="drb-kosong">Tidak ada toko bernama “' + esc(tokoQ) + '”.</div>'; return; }
        }
        $d(d.cap).textContent = cap;

        function rincian(arr, n) {
            return TAHAP.map(function (s, i) {
                return '<div><span>' + s.n + '</span><span class="drb-num">' + fmt(arr[i]) + ' (' + fp(pct(arr[i], n)) + ')</span></div>';
            }).join('');
        }
        function bar(arr, n, lebar) {
            return '<span class="drb-rbar"><span class="drb-rbar-w" style="width:' + lebar + '%">' + TAHAP.map(function (s, i) {
                return arr[i] ? '<i style="background:' + s.c + ';width:' + pct(arr[i], n) + '%"></i>' : '';
            }).join('') + '</span></span>';
        }
        function drawRow(x, host) {
            var nm = x[0], rank = x[1], p = pct(cnt[nm], total), sel = F[d.k] === nm;
            var b = document.createElement('button'); b.type = 'button';
            b.className = 'drb-rrow' + (sel ? ' on' : '') + (F[d.k] && !sel ? ' dim' : '');
            var sub = '', vn = [];
            if (d.k === 'toko' && tokoMp[nm]) sub = '<small>' + esc(tokoMp[nm]) + '</small>';
            if (d.k === 'kurir') { vn = Object.keys(vr[nm] || {}); if (vn.length > 1) sub = '<small>' + vn.length + ' varian nama</small>'; }
            b.innerHTML = '<span class="drb-badge r' + (rank <= 3 ? rank : 0) + '">#' + rank + '</span>' +
                '<span class="drb-rname" title="' + esc(nm) + '">' + esc(nm) + sub + '</span>' +
                '<span class="drb-rnum drb-num"><b>' + fmt(cnt[nm]) + '</b> <span>' + fp(p) + '</span></span>' +
                bar(brk[nm], cnt[nm], cnt[nm] / max * 100);
            b.onclick = function () { F[d.k] = sel ? null : nm; render(); };
            var tt = '<b>' + esc(nm) + '</b>' + rincian(brk[nm], cnt[nm]);
            if (vn.length > 1) tt += '<b style="margin-top:6px">Nama kurir yang digabung</b>' + vn.sort(function (a, c) { return vr[nm][c] - vr[nm][a]; }).map(function (v) {
                return '<div><span>' + esc(v) + '</span><span class="drb-num">' + fmt(vr[nm][v]) + '</span></div>';
            }).join('');
            tipOn(b, tt);
            host.appendChild(b);
        }
        show.forEach(function (x) { drawRow(x, box); });

        if (d.k === 'toko' && rest.length) {
            var sum = 0, lb = [0, 0, 0, 0, 0, 0];
            rest.forEach(function (x) { sum += cnt[x[0]]; for (var i = 0; i < 6; i++) lb[i] += brk[x[0]][i]; });
            var open = tokoAll, l = document.createElement('button'); l.type = 'button';
            l.className = 'drb-rrow lain' + (open ? ' open' : ''); l.setAttribute('aria-expanded', open);
            l.innerHTML = '<span class="drb-badge">#' + (TOKO_LIMIT + 1) + '+</span>' +
                '<span class="drb-rname">Lainnya<small>' + rest.length + ' toko · rank ' + (TOKO_LIMIT + 1) + '–' + (TOKO_LIMIT + rest.length) + '</small></span>' +
                '<span class="drb-rnum drb-num"><b>' + fmt(sum) + '</b> <span>' + fp(pct(sum, total)) + '</span><em class="chev">' + (open ? 'Sembunyikan' : 'Lihat detail') + ' ▾</em></span>' +
                bar(lb, sum, Math.min(100, sum / max * 100));
            l.onclick = function () { tokoAll = !tokoAll; renderRank(d); };
            tipOn(l, '<b>Lainnya (' + rest.length + ' toko)</b>' + rincian(lb, sum));
            box.appendChild(l);
            if (open) {
                var dt = document.createElement('div'); dt.className = 'drb-lain-detail';
                rest.forEach(function (x) { drawRow(x, dt); });
                box.appendChild(dt);
            }
        }
    }

    function renderChips() {
        var box = $d('drb_chips'), items = [];
        if (F.stage !== null) items.push(['stage', 'Tahap: ' + TAHAP[F.stage].n]);
        DIMS.forEach(function (d) { if (F[d.k]) items.push([d.k, d.lbl + ': ' + F[d.k]]); });
        box.hidden = !items.length;
        box.innerHTML = '<span class="drb-lbl">Filter aktif</span>';
        items.forEach(function (it) {
            var c = document.createElement('span'); c.className = 'drb-chip';
            c.innerHTML = esc(it[1]) + '<button type="button" aria-label="Hapus filter" title="Hapus filter">×</button>';
            c.querySelector('button').onclick = function () { F[it[0]] = null; render(); };
            box.appendChild(c);
        });
        $d('drb_reset').disabled = !items.length;
    }

    function render() {
        tip.classList.remove('on');
        renderChips(); renderProgress(); DIMS.forEach(renderRank);
    }

    // ---------------------------------------------------------------- kendali
    $d('drb_legend').innerHTML = '<strong>Warna bar peringkat = komposisi progres:</strong>' +
        TAHAP.map(function (s) { return '<span><i style="background:' + s.c + '"></i>' + s.n + '</span>'; }).join('');
    $('#drb_periode button').on('click', function () {
        periode = $(this).data('v').toString();
        $('#drb_periode button').removeClass('on'); $(this).addClass('on');
        muat(false);
    });
    $d('drb_toko_q').oninput = function () { tokoQ = this.value; renderRank(DIMS[2]); };
    $d('drb_reset').onclick = function () { F.mp = F.kurir = F.toko = null; F.stage = null; render(); };
    $d('drb_segar').onclick = function () { muat(true); };

    muat(false);
});
</script>
