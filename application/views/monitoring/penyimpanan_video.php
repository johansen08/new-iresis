<?php
/*
 * Penyimpanan Video (Tim Monitoring).
 * Data dari Penyimpanan_video_fcd::snapshot(); halaman ini hanya menampilkan.
 * Gaya dikurung di bawah .pvd supaya tidak bocor ke menu lain (halaman dimuat lewat AJAX).
 */
?>
<style>
.pvd{--ink:#0a2345;--text:#1d2939;--muted:#5b6b82;--line:#dfe4ec;--surface:#fff;--track:#e4e9f1;--accent:#1463c9;
  --ok:#227a3a;--ok-soft:#e3f3e7;--warn:#9a5200;--warn-soft:#fdf0db;--bad:#c42b2b;--bad-soft:#fde6e4;
  box-sizing:border-box;max-width:1184px;margin:0 auto;padding:20px 32px 32px;display:grid;gap:16px;
  color:var(--text);font:15px/1.5 Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-variant-numeric:tabular-nums}
.pvd *{box-sizing:border-box}
.pvd h1{font-family:Outfit,Inter,system-ui,sans-serif;color:var(--ink);margin:0;font-size:28px;font-weight:600;line-height:1.15}
.pvd-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:10px 16px}
.pvd-sub{color:var(--muted);margin-top:4px}
.pvd-btn{height:36px;border:1px solid #c4cedb;background:#fff;border-radius:8px;padding:0 12px;cursor:pointer;font-weight:500;display:inline-flex;align-items:center;gap:6px;font:inherit}
.pvd-btn:hover{border-color:var(--ink)}
.pvd-btn[disabled]{opacity:.6;cursor:wait}
.pvd-alert{border-radius:10px;padding:12px 16px;font-weight:500}
.pvd-alert[hidden]{display:none}
.pvd-alert.bahaya{background:var(--bad-soft);color:var(--bad)}
.pvd-alert.waspada{background:var(--warn-soft);color:var(--warn)}
.pvd-alert.aman{background:var(--ok-soft);color:var(--ok)}
.pvd-alert.tidak_tahu{background:#eef1f6;color:var(--muted)}
.pvd-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.pvd-card{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:18px 20px}
.pvd-label{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.pvd-num{font-family:Outfit,Inter,system-ui,sans-serif;font-weight:600;color:var(--ink);font-size:38px;line-height:1;margin-top:8px;letter-spacing:-.01em}
.pvd-num small{font-size:18px;font-weight:600;margin-left:4px}
.pvd-note{color:var(--muted);margin-top:8px;font-size:14px}
.pvd-note b{color:var(--ink)}
.pvd-bar{height:10px;background:var(--track);border-radius:99px;overflow:hidden;margin-top:12px}
.pvd-bar i{display:block;height:100%;background:var(--accent);border-radius:99px}
.pvd-card.bahaya{border:2px solid var(--bad)}.pvd-card.bahaya .pvd-bar i{background:var(--bad)}
.pvd-card.waspada{border:2px solid var(--warn)}.pvd-card.waspada .pvd-bar i{background:var(--warn)}
.pvd-tabel{background:var(--surface);border:1px solid var(--line);border-radius:12px;overflow:hidden}
.pvd-tabel h2{margin:0;padding:14px 20px;font-size:18px;font-family:Outfit,Inter,system-ui,sans-serif;color:var(--ink);border-bottom:1px solid var(--line)}
.pvd-scroll{max-height:560px;overflow:auto}
.pvd table{width:100%;border-collapse:collapse}
.pvd th{position:sticky;top:0;background:#f5f7fa;text-align:right;font-size:12px;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);padding:10px 16px;border-bottom:1px solid var(--line)}
.pvd td{text-align:right;padding:8px 16px;border-bottom:1px solid #edf0f5}
.pvd th:first-child,.pvd td:first-child{text-align:left}
.pvd tbody tr:hover{background:#f8fafc}
.pvd-kosong{padding:24px;text-align:center;color:var(--muted)}
@media (max-width:820px){.pvd{padding:16px}.pvd-cards{grid-template-columns:minmax(0,1fr)}.pvd-num{font-size:30px}}
</style>

<div class="pvd" id="pvd-root">
  <div class="pvd-head">
    <div>
      <h1>Penyimpanan Video</h1>
      <div class="pvd-sub" id="pvd-sub"></div>
    </div>
    <button type="button" class="pvd-btn" id="pvd-muat"><i class="fa fa-refresh"></i> Muat ulang</button>
  </div>

  <div class="pvd-alert" id="pvd-alert" role="alert" hidden></div>

  <section class="pvd-cards">
    <div class="pvd-card" id="pvd-c-total">
      <div class="pvd-label">Total penyimpanan terpakai</div>
      <div class="pvd-num" id="pvd-total">–</div>
      <div class="pvd-note" id="pvd-total-note"></div>
    </div>
    <div class="pvd-card" id="pvd-c-sisa">
      <div class="pvd-label">Space tersisa</div>
      <div class="pvd-num" id="pvd-sisa">–</div>
      <div class="pvd-bar"><i id="pvd-bar" style="width:0"></i></div>
      <div class="pvd-note" id="pvd-sisa-note"></div>
    </div>
    <div class="pvd-card" id="pvd-c-est">
      <div class="pvd-label">Estimasi penuh</div>
      <div class="pvd-num" id="pvd-est">–</div>
      <div class="pvd-note" id="pvd-est-note"></div>
    </div>
  </section>

  <section class="pvd-tabel">
    <h2>Rekap per tanggal</h2>
    <div class="pvd-scroll">
      <table>
        <thead><tr>
          <th>Tanggal</th><th>Total video</th><th>Ukuran video</th><th>Hasil MP4</th><th>Total penyimpanan</th><th>Rata-rata/video</th><th>Kumulatif</th>
        </tr></thead>
        <tbody id="pvd-body"></tbody>
      </table>
    </div>
  </section>
</div>

<script>
(function(){
  var URL_DATA = <?= json_encode(base_url('monitoring/penyimpanan-video-data')) ?>;
  var DATA = <?= json_encode($snapshot) ?>;
  var HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  var BLN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

  function el(id){ return document.getElementById(id); }
  function esc(s){ return String(s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  function nf(n){ return Number(n).toLocaleString('id-ID'); }
  function ukuran(b, tanpaSatuan){
    var u = ['B','KB','MB','GB','TB'], i = 0, v = Number(b);
    while (v >= 1000 && i < u.length - 1) { v /= 1000; i++; }
    var t = v.toLocaleString('id-ID', {maximumFractionDigits: i >= 3 ? 2 : 0});
    return tanpaSatuan ? [t, u[i]] : t + ' ' + u[i];
  }
  function tgl(s, panjang){
    var p = s.split('-'), d = new Date(+p[0], +p[1]-1, +p[2]);
    return (panjang ? HARI[d.getDay()] + ', ' : '') + (+p[2]) + ' ' + BLN[+p[1]-1] + ' ' + p[0];
  }
  function angka(id, b){ var x = ukuran(b, true); el(id).innerHTML = esc(x[0]) + '<small>' + x[1] + '</small>'; }

  function render(d){
    el('pvd-sub').innerHTML = 'Folder <b>' + esc(d.folder) + '</b> · dihitung ' + esc(d.dihitung_pada.substr(11, 5));

    angka('pvd-total', d.terpakai_video);
    el('pvd-total-note').innerHTML = '<b>' + nf(d.total_video) + '</b> video'
      + (d.terpakai_disk !== null ? ' · terpakai di drive (semua data): <b>' + ukuran(d.terpakai_disk) + '</b>' : '');

    var al = el('pvd-alert'), est = d.perkiraan;
    if (!d.drive_ok) {
      el('pvd-sisa').textContent = '–';
      el('pvd-sisa-note').textContent = 'Drive tidak terbaca. Pastikan HDD backup terpasang.';
      el('pvd-bar').style.width = '0';
      al.className = 'pvd-alert bahaya'; al.hidden = false;
      al.textContent = 'Drive penyimpanan video tidak terbaca. Pastikan HDD backup terpasang dan folder ' + d.folder + ' bisa diakses.';
    } else {
      angka('pvd-sisa', d.sisa);
      var pakai = Math.min(100, Math.round(d.terpakai_disk / d.kapasitas * 100));
      el('pvd-bar').style.width = pakai + '%';
      el('pvd-sisa-note').innerHTML = 'dari kapasitas <b>' + ukuran(d.kapasitas) + '</b> · terpakai <b>' + pakai + '%</b>';
    }

    var c = el('pvd-c-est'), c2 = el('pvd-c-sisa');
    c.className = 'pvd-card ' + est.level; c2.className = 'pvd-card ' + est.level;
    if (est.sisa_hari === null) {
      el('pvd-est').innerHTML = '–';
      el('pvd-est-note').textContent = d.drive_ok ? 'Belum ada data pertumbuhan harian untuk dihitung.' : '';
      if (d.drive_ok) { al.className = 'pvd-alert tidak_tahu'; al.hidden = false; al.textContent = 'Estimasi belum bisa dihitung: belum ada data pertumbuhan harian.'; }
    } else {
      el('pvd-est').innerHTML = nf(est.sisa_hari) + '<small>hari lagi</small>';
      el('pvd-est-note').innerHTML = 'sekitar <b>' + tgl(est.tanggal_penuh) + '</b><br>rata-rata tambah <b>' + ukuran(est.rata_harian_byte) + '</b>/hari (' + d.hari_perkiraan + ' hari terakhir)';
      var pesan = {
        bahaya: 'AWAS: HDD backup diperkirakan penuh dalam ' + nf(est.sisa_hari) + ' hari (sekitar ' + tgl(est.tanggal_penuh) + '). Segera siapkan HDD tambahan atau arsipkan video lama.',
        waspada: 'Waspada: HDD backup diperkirakan penuh dalam ' + nf(est.sisa_hari) + ' hari (sekitar ' + tgl(est.tanggal_penuh) + '). Mulai rencanakan penambahan kapasitas.',
        aman: 'Aman: HDD backup diperkirakan baru penuh sekitar ' + tgl(est.tanggal_penuh) + ' (' + nf(est.sisa_hari) + ' hari lagi).'
      };
      al.className = 'pvd-alert ' + est.level; al.hidden = false; al.textContent = pesan[est.level];
    }

    var b = el('pvd-body');
    if (!d.hari.length) { b.innerHTML = '<tr><td colspan="7" class="pvd-kosong">Belum ada rekaman video.</td></tr>'; return; }
    b.innerHTML = d.hari.map(function(h){
      var rata = h.total_video ? ukuran(h.byte_total / h.total_video) : '–';
      return '<tr><td>' + esc(tgl(h.tanggal, true)) + '</td><td>' + nf(h.total_video) + '</td><td>' + ukuran(h.byte_webm)
        + '</td><td>' + (h.byte_mp4 ? ukuran(h.byte_mp4) : '–') + '</td><td><b>' + ukuran(h.byte_total) + '</b></td><td>' + rata
        + '</td><td>' + ukuran(h.kumulatif) + '</td></tr>';
    }).join('');
  }

  var tombol = el('pvd-muat');
  tombol.addEventListener('click', function(){
    tombol.disabled = true;
    $.ajax({url: URL_DATA, dataType: 'json', timeout: 120000}).done(function(r){
      if (r && r.code == 200 && r.data) { render(r.data); }
      else { var a = el('pvd-alert'); a.className = 'pvd-alert bahaya'; a.hidden = false; a.textContent = (r && r.message) || 'Data gagal dimuat.'; }
    }).fail(function(x){
      var a = el('pvd-alert'); a.className = 'pvd-alert bahaya'; a.hidden = false;
      a.textContent = 'Data gagal dimuat (' + (x.status || 'tidak ada respon') + '). Coba lagi.';
    }).always(function(){ tombol.disabled = false; });
  });

  if (DATA) { render(DATA); }
  else { var a = el('pvd-alert'); a.className = 'pvd-alert bahaya'; a.hidden = false; a.textContent = 'Data penyimpanan gagal dihitung. Klik Muat ulang.'; }
})();
</script>
