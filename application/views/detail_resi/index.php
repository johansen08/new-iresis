<?php
/**
 * Cari Detail Resi: kolom pencarian + area hasil. Hasil (detail_resi/hasil) dimuat otomatis
 * lewat AJAX begitu isian berhenti diketik/ditempel/discan -- tidak ada tombol Cari.
 * Prototype & dasar data: lihat komentar di application/models/Detail_resi_fcd.php.
 */
?>
<style>
.dr{--bg:#f2f4f8; --surface:#ffffff; --line:#dde3ec; --fg:#1b2433; --muted:#5b6a82; --faint:#8794aa;
  --accent:#1f5fbf; --accent-soft:#e7effb;
  --ok:#17703f; --ok-soft:#e2f4ea; --warn:#9a5700; --warn-soft:#fff0d6; --bad:#b3261e; --bad-soft:#fde9e7;
  --font:"Segoe UI",Arial,sans-serif; --mono:Consolas,"Courier New",monospace;}
.dr *{box-sizing:border-box}
.dr{color:var(--fg);font:14.5px/1.45 var(--font);padding:0}
.dr .wrap{max-width:none;margin:0;display:flex;flex-direction:column;gap:12px}
.dr button,.dr input,.dr select{font:inherit;color:inherit}
.dr button{cursor:pointer}
.dr :focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.dr .mono{font-family:var(--mono)}
.dr .card{background:var(--surface);border:1px solid var(--line);border-radius:6px}
.dr .lbl{font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:var(--muted);font-weight:600}
.dr .top{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.dr .search{flex:1 1 360px;display:flex;min-width:0}
.dr .search{position:relative}
.dr .search input{flex:1;min-width:0;height:40px;padding:0 100px 0 12px;border:1px solid var(--line);border-radius:6px;background:var(--surface)}
.dr .search input:focus{border-color:var(--accent);outline:2px solid var(--accent-soft)}
.dr .hint{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--muted);pointer-events:none}
.dr .notfound{padding:28px;text-align:center;color:var(--muted)}
.dr .proto{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted)}
.dr .proto select{height:30px;border:1px dashed var(--faint);border-radius:6px;background:var(--surface);padding:0 6px}
.dr .ident{display:grid;grid-template-columns:1fr auto;gap:16px;padding:16px 20px;align-items:center}
.dr .ident .row1{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.dr .noresi{font:600 28px/1.1 var(--mono);letter-spacing:.01em}
.dr .iconbtn{border:1px solid var(--line);background:var(--surface);border-radius:5px;height:26px;padding:0 8px;color:var(--muted);font-size:12px}
.dr .cpy{display:inline-grid;place-items:center;width:28px;height:28px;padding:0;border:1px solid var(--line);background:var(--surface);border-radius:5px;color:var(--muted);vertical-align:middle}
.dr .cpy:hover{border-color:var(--accent);color:var(--accent)}
.dr .cpy.ok{border-color:var(--ok);color:var(--ok)}
.dr .cpy svg{width:15px;height:15px}
.dr .iconbtn:hover{border-color:var(--accent);color:var(--accent)}
.dr .meta{display:flex;flex-wrap:wrap;gap:6px 18px;margin-top:10px;color:var(--muted)}
.dr .meta b{color:var(--fg);font-weight:500}
.dr .pill{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 11px;border-radius:13px;font-size:13px;font-weight:600;white-space:nowrap}
.dr .pill::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.dr .p-ok{background:var(--ok-soft);color:var(--ok)}
.dr .p-warn{background:var(--warn-soft);color:var(--warn)}
.dr .p-bad{background:var(--bad-soft);color:var(--bad)}
.dr .p-info{background:var(--accent-soft);color:var(--accent)}
.dr .p-mute{background:var(--bg);color:var(--muted)}
.dr .deadline{text-align:right;border-left:1px solid var(--line);padding-left:18px;min-width:170px}
.dr .deadline .v{font-size:20px;font-weight:600}
.dr .deadline.late .v,.dr .deadline.today .v{color:var(--bad)}
.dr .alerts{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:9px 14px;background:var(--bad-soft);border:1px solid color-mix(in srgb,var(--bad) 35%,transparent);border-radius:6px}
.dr .alerts .lbl{color:var(--bad)}
.dr .chip{border:1px solid color-mix(in srgb,var(--bad) 45%,transparent);background:var(--surface);color:var(--bad);border-radius:14px;height:28px;padding:0 12px;font-weight:600;font-size:13px}
.dr .chip:hover{background:var(--bad);color:var(--surface)}
.dr .alerts.calm{background:var(--ok-soft);border-color:color-mix(in srgb,var(--ok) 35%,transparent);color:var(--ok);font-weight:600}
.dr .flow{display:grid;grid-template-columns:repeat(6,1fr);padding:16px 10px 12px}
.dr .step{position:relative;text-align:center;padding:0 4px;min-width:0}
.dr .step::before{content:"";position:absolute;top:14px;left:-50%;width:100%;height:2px;background:var(--line)}
.dr .step:first-child::before{display:none}
.dr .step.done::before,.dr .step.cur::before{background:var(--ok)}
.dr .dot{position:relative;z-index:1;margin:0 auto 5px;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-size:14px;font-weight:700;background:var(--surface);border:2px solid var(--line);color:var(--faint)}
.dr .step.done .dot{background:var(--ok);border-color:var(--ok);color:var(--surface)}
.dr .step.cur .dot{border-color:var(--accent);color:var(--accent);box-shadow:0 0 0 4px var(--accent-soft)}
.dr .step.stop .dot{background:var(--bad);border-color:var(--bad);color:var(--surface)}
.dr .step .nm{font-weight:600;font-size:13.5px}
.dr .step .tm{font:500 13.5px var(--mono);color:var(--fg)}
.dr .step .pic{font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dr .step.todo .nm,.dr .step.todo .tm{color:var(--faint)}
.dr .main{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
.dr .ch{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--line)}
.dr .ch h2{margin:0;font-size:14.5px;font-weight:600;display:flex;align-items:center;gap:6px}
.dr .scroll{max-height:230px;overflow:auto}
.dr table{width:100%;border-collapse:collapse}
.dr th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);text-align:left;padding:8px 16px;font-weight:600;position:sticky;top:0;background:var(--surface)}
.dr td{padding:10px 16px;border-top:1px solid var(--line);vertical-align:middle}
.dr td.n,.dr th.n{text-align:right}
.dr .sku{font:600 16px var(--mono)}
.dr .skn{color:var(--muted);font-size:12px;max-width:340px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dr .rak{font:500 14px var(--mono);background:var(--bg);border-radius:4px;padding:3px 8px}
.dr .qty{font:600 18px var(--mono)}
.dr tfoot td{background:var(--bg);font-weight:600}
.dr .people{display:flex;flex-direction:column}
.dr .person{display:grid;grid-template-columns:70px 1fr auto;gap:4px 10px;align-items:center;padding:12px 16px;border-top:1px solid var(--line)}
.dr .person:first-of-type{border-top:0}
.dr .person .who{font-weight:600}
.dr .person .sub{grid-column:2/4;color:var(--muted);font-size:13.5px}
.dr .person .sub a{color:var(--accent)}
.dr .i{position:relative;display:inline-grid;place-items:center;width:17px;height:17px;border-radius:50%;border:1px solid var(--faint);color:var(--faint);font:600 11px/1 var(--font);cursor:help;background:none;padding:0;flex:none;text-transform:none;letter-spacing:0}
.dr .i:hover,.dr .i:focus-visible,.dr .i.on{border-color:var(--accent);color:var(--accent)}
.dr .i .tip{position:absolute;z-index:20;left:50%;bottom:calc(100% + 6px);transform:translateX(-50%);width:250px;padding:9px 11px;border-radius:6px;background:var(--fg);color:var(--bg);font:400 13px/1.45 var(--font);text-align:left;display:none;pointer-events:none}
.dr .i:hover .tip,.dr .i:focus-visible .tip,.dr .i.on .tip{display:block}
.dr .deadline .i .tip{left:auto;right:-4px;transform:none}
.dr .more{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
.dr .tg{display:flex;align-items:center;justify-content:space-between;gap:6px;text-align:left;padding:11px 14px;border:1px solid var(--line);background:var(--surface);border-radius:6px;min-width:0}
.dr .tg:hover{border-color:var(--accent)}
.dr .tg .t{font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dr .tg .c{font:600 12.5px var(--mono);min-width:20px;text-align:center;border-radius:10px;padding:1px 6px;background:var(--bg);color:var(--muted)}
.dr .tg.has .c{background:var(--bad-soft);color:var(--bad)}
.dr .tg.has.soft .c{background:var(--accent-soft);color:var(--accent)}
.dr .tg[aria-expanded="true"]{border-color:var(--accent);background:var(--accent-soft)}
.dr .tg::after{content:"▾";color:var(--faint);font-size:11px}
.dr .tg[aria-expanded="true"]::after{content:"▴"}
.dr .panels{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:8px}
.dr .panels:empty{display:none}
.dr .pn .body{padding:12px 16px}
.dr .kv{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px 14px}
.dr .kv div span{display:block;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600}
.dr .kv div b{font-weight:500}
.dr .sub2{color:var(--muted);font-size:13.5px}
.dr .kv .i{margin-left:4px;vertical-align:-3px}
.dr .lst{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:6px}
.dr .lst li{display:flex;gap:8px;align-items:baseline;flex-wrap:wrap;padding-top:6px;border-top:1px solid var(--line)}
.dr .lst li:first-child{border-top:0;padding-top:0}
.dr .lst .when{font:500 13px var(--mono);color:var(--muted)}
.dr .empty{color:var(--faint)}
.dr .foot{font-size:11px;color:var(--faint);text-align:center}
@media (max-width:860px){
.dr .ident{grid-template-columns:1fr}
.dr .deadline{border-left:0;padding-left:0;text-align:left}
.dr .main{grid-template-columns:1fr}
.dr .more{grid-template-columns:repeat(2,1fr)}
.dr .flow{grid-template-columns:repeat(2,1fr);row-gap:10px}
.dr .step::before{display:none}
.dr .skn{max-width:180px}
}

.dr [hidden]{display:none!important}
.dr .deadline .i .tip,.dr .people .i .tip{left:auto;right:0;transform:none}
.dr .flow .step:first-child .i .tip{left:0;transform:none}
.dr .flow .step:last-child .i .tip{left:auto;right:0;transform:none}
.dr{padding:10px 14px}
.dr .search{max-width:640px;width:100%;margin:0 auto}
.dr .top{justify-content:center}
.dr .search input{text-align:center;padding:0 100px}

</style>

<div class="dr" id="dr">
  <div class="wrap">
    <div class="top">
      <form class="search" id="dr-form" autocomplete="off" onsubmit="return false;">
        <input id="dr-q" type="text" aria-label="Nomor resi atau nomor pesanan" placeholder="No. Resi / No. Pesanan" autofocus />
        <span class="hint" id="dr-hint" aria-live="polite"></span>
      </form>
    </div>
    <div id="dr-hasil">
      <div class="card notfound">Scan atau tempel nomor resi / nomor pesanan untuk melihat detailnya.</div>
    </div>
  </div>
</div>

<script type="text/javascript">
(function () {
  var $dr = $('#dr'), q = document.getElementById('dr-q'), hint = document.getElementById('dr-hint'),
      hasil = document.getElementById('dr-hasil'), tmr = null, xhr = null, terakhir = '', sudahCari = false, nilaiLama = '',
      KOSONG = '<div class="card notfound">Scan atau tempel nomor resi / nomor pesanan untuk melihat detailnya.</div>';

  function cari() {
    var v = q.value.trim();
    clearTimeout(tmr);
    if (xhr) { xhr.abort(); xhr = null; }               // permintaan lama dibatalkan: hasil telat tidak menimpa yang baru
    if (v.length < 6) {                                  // nomor resi/pesanan nyata jauh lebih panjang
      hint.textContent = '';
      terakhir = '';
      if (v === '') hasil.innerHTML = KOSONG;
      return;
    }
    if (v === terakhir) return;
    terakhir = v;
    hint.textContent = 'Mencari…';
    xhr = $.ajax({
      url: 'receipt/cari-detail-receipt', type: 'POST', dataType: 'json', data: { q: v }, timeout: 30000,
      success: function (res) {
        xhr = null;
        if (q.value.trim() !== v) return;
        if (res && res.data && typeof res.data.html === 'string') {
          hasil.innerHTML = res.data.html;
          hint.textContent = res.data.ditemukan ? 'Ditemukan' : '';
          if (res.data.ditemukan) { sudahCari = true; nilaiLama = v; }   // hanya hasil nyata yang membuat scan berikutnya mulai dari kosong (ketikan setengah jalan tidak)
          var auto = hasil.querySelector('[data-p="retur"]');
          if (auto && res.data.ditemukan) {
            var tombol = hasil.querySelector('.tg[data-k="retur"].has');
            if (tombol) bukaKartu('retur', true);
          }
        } else {
          hint.textContent = '';
          hasil.innerHTML = '<div class="card notfound">Gagal memuat hasil: ' + $('<div>').text((res && res.message) || 'respons tidak dikenal').html() + '</div>';
        }
      },
      error: function (jq, status) {
        xhr = null;
        if (status === 'abort') return;
        terakhir = '';
        hint.textContent = '';
        hasil.innerHTML = '<div class="card notfound">Gagal menghubungi server (' + status + '). Coba scan lagi.</div>';
      }
    });
  }

  function bukaKartu(k, buka) {
    var p = hasil.querySelector('[data-p="' + k + '"]'), t = hasil.querySelector('.tg[data-k="' + k + '"]');
    if (!p || !t) return;
    p.hidden = !buka;
    t.setAttribute('aria-expanded', buka ? 'true' : 'false');
  }

  function salinTeks(teks, tombol) {
    var selesai = function () {
      tombol.classList.add('ok'); tombol.title = 'Tersalin';
      setTimeout(function () { tombol.classList.remove('ok'); tombol.title = 'Salin'; }, 1200);
    };
    var cadangan = function () {                         // halaman LAN lewat http:// tidak punya navigator.clipboard
      var ta = document.createElement('textarea');
      ta.value = teks; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { if (document.execCommand('copy')) selesai(); } catch (e) {}
      document.body.removeChild(ta);
    };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(teks).then(selesai, cadangan);
    else cadangan();
  }

  // Setiap scan dimulai dari kosong: begitu ada ketikan/scan/tempelan baru sesudah pencarian,
  // isian dan hasil lama dibuang dulu (alat scan mengetik cepat, jadi karakter pertama sudah cukup).
  function bersihkan() {
    clearTimeout(tmr);
    if (xhr) { xhr.abort(); xhr = null; }
    q.value = '';
    terakhir = '';
    sudahCari = false;
    hint.textContent = '';
    hasil.innerHTML = KOSONG;
  }
  q.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); terakhir = ''; sudahCari = true; nilaiLama = q.value; cari(); return; }
    if (sudahCari && !e.ctrlKey && !e.metaKey && !e.altKey && e.key.length === 1) bersihkan();
  });
  q.addEventListener('paste', function () { if (sudahCari) bersihkan(); });
  q.addEventListener('input', function () {
    if (sudahCari) {
      // Teks masuk tanpa keydown (tempel, atau scanner yang mengirim sekaligus): buang isian dan hasil lama,
      // sisakan hanya teks barunya. Menghapus sebagian dari isian lama (koreksi ketikan) dibiarkan.
      var baru = q.value;
      if (nilaiLama.indexOf(baru) !== 0) {
        if (baru.indexOf(nilaiLama) === 0) baru = baru.slice(nilaiLama.length);
        bersihkan();
        q.value = baru;
      }
    }
    clearTimeout(tmr); tmr = setTimeout(cari, 300);
  });
  q.addEventListener('focus', function () { q.select(); });   // scan berikutnya menimpa yang lama

  // Handler dipasang di wadah #dr (ikut hilang saat menu lain dibuka), bukan di document.
  $dr.on('click', function (e) {
    var cb = $(e.target).closest('[data-copy]')[0];
    if (cb) { salinTeks(cb.getAttribute('data-copy'), cb); return; }

    var i = $(e.target).closest('.i')[0];
    $dr.find('.i.on').each(function () { if (this !== i) this.classList.remove('on'); });
    if (i) { i.classList.toggle('on'); return; }

    var t = $(e.target).closest('[data-k],[data-open],[data-close]')[0];
    if (!t) return;
    var k = t.getAttribute('data-k') || t.getAttribute('data-open') || t.getAttribute('data-close');
    var p = hasil.querySelector('[data-p="' + k + '"]');
    if (!p) return;
    if (t.getAttribute('data-close')) bukaKartu(k, false);
    else if (t.getAttribute('data-open')) { bukaKartu(k, true); p.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
    else bukaKartu(k, p.hidden);
  });

  q.focus();
})();
</script>
