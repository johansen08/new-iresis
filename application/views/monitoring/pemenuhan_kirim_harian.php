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
.pkh-stage .telat{display:block;margin-top:2px;color:var(--pkh-warn)}
.pkh-stage .telat b{color:var(--pkh-warn)}
.pkh-kat .telat td{color:var(--pkh-warn)}
.pkh-kat .telat td:first-child{color:var(--pkh-warn)}

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

@media (max-width:760px){.pkh-calc{grid-template-columns:1fr}}
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
          <div class="pkh-sum-title">Detail OT/Perbantuan<span class="pkh-info" data-tip="<p><b>Beban per packer</b> = paket &gt;1 Qty yang belum dipacking ÷ jumlah packer.</p><p><b>Cukup</b> bila beban paling banyak 85% dari batas; <b>Mepet</b> bila sampai batas; <b>Perlu OT / Perbantuan</b> bila lewat batas.</p><p>Resi <b>upload telat</b> tidak ikut dihitung: resi itu masuk sesudah picking tutup dan baru dikerjakan besok.</p>"></span></div>
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
</div>

<script>
(function () {
  // Kategori: 0 Spesial, 1 Reguler (keduanya 1 Qty), 2 1 SKU 2–9, 3 2–9 SKU, 4 Qty >9 (ketiganya >1 Qty), 5 tanpa rincian SKU.
  // Grup: wajib keluar = KA (sisa kemarin) + TA (s/d 12.00) + TB (TikTok s/d 15.00, batas ≤ besok) + TM (batas MP hari ini);
  //       TX (TikTok s/d 15.00 batas lusa+) dan TC boleh besok.
  var URL_DATA = <?= json_encode(base_url('monitoring/pemenuhan-kirim-harian-data')) ?>;
  var AWAL = <?= json_encode($snapshot) ?>;
  var WAJIB = ['KA', 'TA', 'TB', 'TM'];
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
  // A − B per sel: dipakai untuk memisahkan resi upload telat dari sisanya
  function kurang(A, B) { return A.map(function (r, k) { return r.map(function (v, j) { return v - B[k][j]; }); }); }
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
    // T = bagian W yang di-upload sesudah jam batas upload; N = sisanya (tanggungan gudang)
    var T = d.telat ? gabung(d.telat, WAJIB) : SEMUA.map(function () { return [0, 0, 0, 0, 0]; });
    var N = kurang(W, T);
    var jamTelat = d.setelan.jam_upload_terlambat.replace(':', '.');
    var n = function (nama) { return total(g[nama], 0); };
    var dit = total(W, 0), tanpaHO = total(W, 4), besok = n('TX') + n('TC');
    var wajibHariIni = n('TA') + n('TB') + n('TM');
    $id('pkh-sub').innerHTML = d.label_tanggal + ' · ' + (d.hari_ini ? 'data jam <b>' + d.jam_data.replace(':', '.') + '</b>' : '<b>rekap akhir hari</b>');
    $id('pkh-masuk').textContent = fmt(wajibHariIni + besok);
    $id('pkh-masuk-note').innerHTML = '<b>' + fmt(wajibHariIni) + '</b> wajib keluar hari ini · <b>' + fmt(besok) + '</b> boleh keluar besok';
    $id('pkh-wajib').textContent = fmt(dit);
    $id('pkh-parts').innerHTML =
      '<li><span>Sisa kemarin</span><b>' + fmt(n('KA')) + '</b></li>' +
      '<li><span>Pesanan s/d 12.00 · semua MP</span><b>' + fmt(n('TA')) + '</b></li>' +
      '<li><span>TikTok 12.00–15.00 · batas kirim s/d besok</span><b>' + fmt(n('TB')) + '</b></li>' +
      (n('TM') ? '<li><span>Batas kirim MP hari ini · masuk sesudah jam</span><b>' + fmt(n('TM')) + '</b></li>' : '');
    pasangInfo($id('pkh-info-wajib'),
      '<p>Wajib keluar = pesanan masuk s/d 12.00 (semua MP) + pesanan TikTok masuk s/d 15.00 yang batas kirim MP-nya hari ini atau besok + sisa kemarin.</p>' +
      '<p><b>Cek silang batas kirim MP:</b> ' + (n('TM') ? fmt(n('TM')) + ' resi berbatas kirim hari ini masuk sesudah jam, sudah ikut dihitung.' : 'tidak ada resi berbatas kirim hari ini yang terlewat aturan jam.') +
      (n('TX') ? ' ' + fmt(n('TX')) + ' pesanan TikTok 12.00–15.00 berbatas kirim lusa atau lebih, dihitung boleh besok.' : '') + '</p>' +
      '<p><b>Tidak dihitung:</b> ' + fmt(d.cancel_hari_ini) + ' resi cancel hari ini (CANCELED / REQUEST_CANCEL) dan ' + fmt(d.tertunggak_lama) +
      ' resi lama (&gt; 7 hari) yang belum keluar, perlu dicek terpisah.</p>' +
      (total(T, 0) ? '<p><b>Upload telat:</b> ' + fmt(total(T, 0)) + ' resi wajib baru di-upload ke IRESIS sesudah ' + jamTelat +
        '. Tetap dihitung, tetapi di kotak Picker/Packer/HO dipisah dari Belum.</p>' : ''));
    var TIP_TELAT = '<p><b>Upload telat</b> = resi wajib yang baru di-upload ke IRESIS sesudah ' + jamTelat +
      ', saat picking sudah tutup. Tetap dihitung di angka dan persen, tetapi dipisah dari Belum karena gudang tidak sempat mengerjakannya hari itu.</p>';
    var tahap = [   // [judul, indeks kolom, kelas, teks ⓘ]
      ['Picker', 1, '', 'Resi wajib yang sudah discan picker. Resi yang sudah dipacking atau sudah keluar ikut terhitung walau scan picker-nya terlewat.'],
      ['Packer', 2, 'key', 'Resi wajib yang sudah discan packer, termasuk Spesial yang otomatis ter-pack saat dipick. Resi yang sudah keluar ikut terhitung walau scan packer-nya terlewat.'],
      ['HO (keluar)', 3, '', 'Keluar = sudah discan HO, atau status marketplace sudah SHIPPED / COMPLETED / RETURNED.' + (tanpaHO ? ' Termasuk <b>' + fmt(tanpaHO) + '</b> resi yang keluar tanpa scan HO.' : '')]
    ];
    $id('pkh-stages').innerHTML = tahap.map(function (t, i) {
      var sudah = total(W, t[1]), telat = belum(T, t[1]);
      return '<div class="pkh-card pkh-stage ' + t[2] + '">' +
        '<div class="pkh-label">' + t[0] + '<span class="pkh-info" id="pkh-info-tahap-' + i + '"></span></div>' +
        '<div class="row-n"><span class="num">' + fmt(sudah) + '</span><span class="pct">' + pct(sudah, dit) + '</span></div>' +
        '<div class="pkh-bar"><i style="width:' + (dit ? Math.min(100, sudah / dit * 100) : 0) + '%"></i></div>' +
        '<div class="left">Belum: <b>' + fmt(belum(N, t[1])) + '</b>' +
        (telat ? '<span class="telat">Upload telat: <b>' + fmt(telat) + '</b></span>' : '') + '</div></div>';
    }).join('');
    tahap.forEach(function (t, i) { pasangInfo($id('pkh-info-tahap-' + i), '<p>' + t[3] + '</p>' + (belum(T, t[1]) ? TIP_TELAT : '')); });
    return { W: W, N: N, T: T, jamTelat: jamTelat };
  }

  function renderJenis(M) {
    var v = function (x) { return x ? fmt(x) : '<span class="zero">–</span>'; };
    var baris = function (label, X, kat, cls) {
      return '<tr class="' + (cls || '') + '"><td>' + label + '</td><td>' + v(belum(X, 1, kat)) + '</td><td class="col-pack">' + v(belum(X, 2, kat)) + '</td><td>' + v(belum(X, 3, kat)) + '</td></tr>';
    };
    // baris jenis hanya tanggungan gudang; upload telat punya baris sendiri; Total = semuanya
    $id('pkh-t-kat').innerHTML =
      '<thead><tr><th>Jenis resi</th><th>Belum picker</th><th class="col-pack">Belum packer</th><th>Belum HO</th></tr></thead><tbody>' +
      baris('1 Qty', M.N, SATU_QTY) + baris('&gt;1 Qty', M.N, LEBIH_QTY) +
      (total(M.N, 0, [5]) > 0 ? baris('Tanpa rincian SKU', M.N, [5]) : '') +
      (belum(M.T, 3) > 0 ? baris('Upload telat (sesudah ' + M.jamTelat + ')', M.T, SEMUA, 'telat') : '') +
      baris('Total', M.W, SEMUA, 'tot') + '</tbody>';
    $id('pkh-kat-meta').innerHTML = 'Belum dipacking <b>' + fmt(belum(M.W, 2)) + '</b>: 1 Qty <b>' + fmt(belum(M.N, 2, SATU_QTY)) + '</b> · &gt;1 Qty <b>' + fmt(belum(M.N, 2, LEBIH_QTY)) + '</b>' +
      (belum(M.T, 2) ? ' · upload telat <b>' + fmt(belum(M.T, 2)) + '</b>' : '');
  }

  // W di sini = resi tanggungan gudang saja (tanpa upload telat)
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
    var M = renderAtas(state.data);
    renderJenis(M);
    renderPacking(M.N);
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
