<?php
/**
 * Tab "Buka Retur Otomatis" (Scan Retur Komplain). Rancangan: docs/RANCANGAN_BUKA_RETUR_OTOMATIS.md,
 * prototipe: docs/prototype_buka_retur_otomatis.html.
 * Dimuat dari retur/scan_retur.php hanya untuk menu komplain (bukan mode update).
 * Penulisan data lewat Retur::save_buka_otomatis (sama dengan Buka Retur manual).
 */
?>
<style>
  #tab-buka-otomatis { --bo-line: #e4e7ee; --bo-text: #111827; --bo-muted: #6b7280; --bo-primary: #4f46e5; --bo-primary-soft: #eef0ff;
    --bo-ok: #059669; --bo-warn: #b45309; --bo-bad: #b91c1c; --bo-soft: #f3f4f8; color: var(--bo-text); font-size: 14px; line-height: 1.45; }
  #tab-buka-otomatis * { box-sizing: border-box; }
  #tab-buka-otomatis .bo-layout { display: grid; gap: 14px; grid-template-columns: minmax(0, 1fr); align-items: start; }
  @media (min-width: 1150px) { #tab-buka-otomatis .bo-layout { grid-template-columns: 330px minmax(0, 1fr); } #tab-buka-otomatis .bo-side { position: sticky; top: 8px; } }
  @media (min-width: 1700px) { #tab-buka-otomatis .bo-layout { grid-template-columns: 400px minmax(0, 1fr); gap: 20px; } #tab-buka-otomatis { font-size: 15px; } }
  #tab-buka-otomatis .bo-side, #tab-buka-otomatis .bo-main { display: grid; gap: 12px; min-width: 0; }
  #tab-buka-otomatis .bo-card { background: #fff; border: 1px solid var(--bo-line); border-radius: 10px; padding: 14px 16px; min-width: 0; }
  #tab-buka-otomatis .bo-lbl { display: block; font-size: 12px; font-weight: 600; color: var(--bo-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
  #tab-buka-otomatis input[type=text], #tab-buka-otomatis input[type=number], #tab-buka-otomatis select { padding: 7px 9px; border: 1px solid var(--bo-line); border-radius: 7px; background: #fff; min-width: 0; font: inherit; color: inherit; height: 36px; }
  #tab-buka-otomatis button { font: inherit; cursor: pointer; border-radius: 7px; border: 1px solid var(--bo-line); background: #fff; padding: 6px 12px; color: inherit; }
  #tab-buka-otomatis button.bo-primary { background: var(--bo-primary); border-color: var(--bo-primary); color: #fff; font-weight: 600; padding: 9px 22px; }
  #tab-buka-otomatis button.bo-primary:disabled { opacity: .4; cursor: not-allowed; }
  #tab-buka-otomatis .bo-link { border: 0; background: none; color: var(--bo-muted); padding: 2px 0; text-decoration: underline; font-size: 13px; }
  #tab-buka-otomatis #bo-scan { width: 100%; font-size: 18px; padding: 12px 14px; border: 2px solid var(--bo-primary); border-radius: 9px; height: auto; }
  #tab-buka-otomatis .bo-hint { color: var(--bo-muted); font-size: 12px; margin-top: 6px; }
  #tab-buka-otomatis .bo-chip { display: inline-block; padding: 1px 9px; border-radius: 999px; font-size: 12px; background: var(--bo-soft); color: var(--bo-muted); white-space: nowrap; }
  #tab-buka-otomatis .bo-chip.warn { color: var(--bo-warn); } #tab-buka-otomatis .bo-chip.ok { color: var(--bo-ok); }
  #tab-buka-otomatis .bo-muted { color: var(--bo-muted); } #tab-buka-otomatis .bo-small { font-size: 12px; }
  #tab-buka-otomatis .bo-l1 { font-size: 17px; font-weight: 650; word-break: break-all; }
  #tab-buka-otomatis .bo-l2 { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; }
  #tab-buka-otomatis .bo-alert { padding: 8px 12px; border-radius: 8px; font-size: 13px; background: var(--bo-soft); border-left: 3px solid var(--bo-warn); margin-bottom: 10px; }
  #tab-buka-otomatis .bo-alert.ok { border-color: var(--bo-ok); } #tab-buka-otomatis .bo-alert.bad { border-color: var(--bo-bad); }
  #tab-buka-otomatis .bo-kosong { text-align: center; color: var(--bo-muted); padding: 48px 16px; }
  #tab-buka-otomatis .bo-kosong b { display: block; color: var(--bo-text); font-size: 16px; margin-bottom: 4px; }
  #tab-buka-otomatis .bo-grp { display: grid; grid-template-columns: 76px minmax(0, 1fr); gap: 14px; padding: 14px 0; border-top: 1px solid var(--bo-line); }
  #tab-buka-otomatis .bo-grp:first-child { border-top: 0; padding-top: 0; }
  #tab-buka-otomatis .bo-foto { position: relative; width: 76px; height: 76px; border-radius: 9px; overflow: hidden; border: 1px solid var(--bo-line); background: var(--bo-soft); }
  #tab-buka-otomatis .bo-foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
  #tab-buka-otomatis .bo-foto.tanpa { display: flex; align-items: center; justify-content: center; text-align: center; font-size: 11px; color: var(--bo-bad); padding: 4px; border: 1px dashed var(--bo-bad); }
  #tab-buka-otomatis .bo-eye { position: absolute; right: 3px; bottom: 3px; width: 26px; height: 26px; padding: 0 !important; display: grid; place-items: center; border-radius: 50% !important; background: rgba(17,24,39,.72) !important; border: 0 !important; color: #fff !important; }
  #tab-buka-otomatis .bo-eye svg { width: 15px; height: 15px; }
  #tab-buka-otomatis .bo-ghead { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
  #tab-buka-otomatis .bo-ghead b { font-size: 15px; word-break: break-all; }
  #tab-buka-otomatis .bo-alok { font-size: 12px; font-weight: 600; white-space: nowrap; padding-top: 2px; }
  #tab-buka-otomatis .bo-lines { display: grid; gap: 8px; }
  #tab-buka-otomatis .bo-line { display: grid; align-items: start; gap: 8px 10px; grid-template-columns: 124px minmax(170px, 1.1fr) minmax(140px, 1fr) 100px; }
  #tab-buka-otomatis .bo-stepper { display: grid; grid-template-columns: 32px 1fr 32px; }
  #tab-buka-otomatis .bo-stepper button { padding: 0; height: 36px; font-size: 18px; line-height: 1; }
  #tab-buka-otomatis .bo-stepper button:first-child { border-radius: 7px 0 0 7px; } #tab-buka-otomatis .bo-stepper button:last-child { border-radius: 0 7px 7px 0; }
  #tab-buka-otomatis .bo-stepper button:disabled { opacity: .35; cursor: not-allowed; }
  #tab-buka-otomatis .bo-stepper input { text-align: center; border-radius: 0; border-left: 0; border-right: 0; width: 100%; padding: 0 2px; -moz-appearance: textfield; }
  #tab-buka-otomatis .bo-stepper input::-webkit-outer-spin-button, #tab-buka-otomatis .bo-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  #tab-buka-otomatis .bo-st { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 6px; }
  #tab-buka-otomatis .bo-st select { flex: 1; min-width: 0; }
  #tab-buka-otomatis .bo-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--bo-ok); flex: none; }
  #tab-buka-otomatis .bo-dot.manual { background: var(--bo-warn); } #tab-buka-otomatis .bo-dot.edit { background: var(--bo-primary); }
  #tab-buka-otomatis .bo-err { color: var(--bo-bad); font-size: 12px; flex-basis: 100%; padding-left: 14px; }
  #tab-buka-otomatis .bo-ket input { width: 100%; }
  #tab-buka-otomatis .bo-ket .bo-cat { font-size: 12px; color: var(--bo-muted); line-height: 36px; }
  #tab-buka-otomatis .bo-act { display: flex; justify-content: flex-end; gap: 2px; }
  #tab-buka-otomatis .bo-icon { border: 0 !important; background: none !important; color: var(--bo-muted); width: 32px; height: 36px; padding: 0 !important; font-size: 16px; }
  #tab-buka-otomatis .bo-icon:hover { color: var(--bo-text); background: var(--bo-soft) !important; }
  #tab-buka-otomatis .bo-icon.ok-i { color: var(--bo-ok); }
  #tab-buka-otomatis .bo-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px 14px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--bo-line); flex-wrap: wrap; }
  #tab-buka-otomatis .bo-foot .bo-kiri { display: flex; flex-direction: column; gap: 2px; }
  #tab-buka-otomatis .bo-msg { color: var(--bo-warn); font-size: 13px; }
  #tab-buka-otomatis .bo-ok-line { color: var(--bo-ok); font-weight: 650; }
  #tab-buka-otomatis .bo-hide { display: none !important; }
  #tab-buka-otomatis details > summary { cursor: pointer; color: var(--bo-muted); font-size: 13px; list-style: none; }
  #tab-buka-otomatis details > summary::-webkit-details-marker { display: none; }
  #tab-buka-otomatis details > summary::before { content: "▸ "; } #tab-buka-otomatis details[open] > summary::before { content: "▾ "; }
  #tab-buka-otomatis dl.bo-kv { display: grid; grid-template-columns: 105px minmax(0, 1fr); gap: 4px 10px; margin: 10px 0 0; font-size: 13px; }
  #tab-buka-otomatis dl.bo-kv dt { color: var(--bo-muted); font-weight: 400; } #tab-buka-otomatis dl.bo-kv dd { margin: 0; word-break: break-word; }
  @media (max-width: 760px) {
    #tab-buka-otomatis .bo-grp { grid-template-columns: 56px minmax(0, 1fr); gap: 10px; }
    #tab-buka-otomatis .bo-foto { width: 56px; height: 56px; }
    #tab-buka-otomatis .bo-line { grid-template-columns: 124px minmax(0, 1fr) auto; }
    #tab-buka-otomatis .bo-line .bo-st { grid-column: 1 / -1; order: 3; } #tab-buka-otomatis .bo-line .bo-ket { grid-column: 1 / -1; order: 4; }
    #tab-buka-otomatis .bo-line .bo-act { order: 2; grid-column: 3; } #tab-buka-otomatis .bo-line .bo-stepper { order: 1; }
  }
  /* popup konfirmasi */
  .bo-modal { position: fixed; inset: 0; z-index: 2000; background: rgba(2,6,23,.55); display: flex; align-items: center; justify-content: center; padding: 16px; }
  .bo-modal.bo-hide { display: none; }
  .bo-modal .bo-box { background: #fff; border: 1px solid #e4e7ee; border-radius: 12px; width: min(560px, 100%); max-height: 90vh; overflow: auto; padding: 18px 20px; box-shadow: 0 20px 50px rgba(0,0,0,.3); font-size: 14px; color: #111827; }
  .bo-modal h2 { margin: 0 0 2px; font-size: 17px; }
  .bo-modal .bo-tot { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 14px 0; }
  .bo-modal .bo-tot div { background: #f3f4f8; border-radius: 8px; padding: 8px 10px; font-size: 12px; color: #6b7280; }
  .bo-modal .bo-tot b { display: block; font-size: 20px; color: #111827; }
  .bo-modal table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .bo-modal th { text-align: left; color: #6b7280; font-weight: 500; padding: 4px 6px; }
  .bo-modal td { padding: 6px; border-top: 1px solid #e4e7ee; }
  .bo-modal .bo-n { text-align: right; width: 56px; }
  .bo-modal .bo-aksi { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
  .bo-modal button { font: inherit; cursor: pointer; border-radius: 7px; border: 1px solid #e4e7ee; background: #fff; padding: 8px 18px; }
  .bo-modal button.bo-primary { background: #4f46e5; border-color: #4f46e5; color: #fff; font-weight: 600; }
  .bo-modal .bo-alert { padding: 8px 12px; border-radius: 8px; font-size: 13px; background: #f3f4f8; border-left: 3px solid #b45309; }
  .bo-modal .bo-alert.ok { border-color: #059669; }
</style>

<div class="tab-pane fade" id="tab-buka-otomatis">
  <div class="bo-layout">
    <aside class="bo-side">
      <section class="bo-card">
        <label class="bo-lbl" for="bo-scan">Scan resi</label>
        <input type="text" id="bo-scan" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Arahkan alat scan ke sini">
        <div class="bo-hint">Resi asli atau resi pengembalian. Enter memproses otomatis dan kolom langsung dikosongkan untuk scan berikutnya.</div>
      </section>
      <section class="bo-card bo-hide" id="bo-ringkas"></section>
    </aside>
    <main class="bo-main">
      <section class="bo-card bo-kosong" id="bo-kosong"><b>Siap scan</b>Scan resi retur komplain untuk melihat rencana Buka Retur.</section>
      <section class="bo-card bo-hide" id="bo-rencana">
        <div id="bo-pesan"></div>
        <div id="bo-isi"></div>
        <div class="bo-foot">
          <div class="bo-kiri"><button class="bo-link" id="bo-ulang" type="button">Isi ulang otomatis</button><span class="bo-msg" id="bo-validasi"></span></div>
          <button class="bo-primary" id="bo-simpan" type="button">Simpan</button>
        </div>
      </section>
      <section class="bo-card bo-hide" id="bo-hasil"></section>
    </main>
  </div>
</div>

<div class="bo-modal bo-hide" id="bo-konfirmasi" role="dialog" aria-modal="true" aria-labelledby="bo-kf-judul">
  <div class="bo-box">
    <h2 id="bo-kf-judul">Konfirmasi simpan</h2>
    <div class="bo-small" style="color:#6b7280" id="bo-kf-resi"></div>
    <div class="bo-tot">
      <div><b id="bo-kf-total">0</b>Total qty resi</div>
      <div><b id="bo-kf-sudah">0</b>Sudah diproses</div>
      <div><b id="bo-kf-now">0</b>Disimpan sekarang</div>
    </div>
    <div id="bo-kf-status"></div>
    <table><thead><tr><th>SKU</th><th>Status</th><th class="bo-n">Qty</th></tr></thead><tbody id="bo-kf-baris"></tbody></table>
    <div class="bo-aksi"><button type="button" id="bo-kf-batal">Batal</button><button type="button" class="bo-primary" id="bo-kf-ok">Simpan</button></div>
  </div>
</div>

<script type="text/javascript">
(function () {
  'use strict';
  var BASE = '<?= base_url() ?>';
  var STATUS = {
    KE_DISPLAY: 'KE DISPLAY', REJECT: 'REJECT', REFUND: 'REFUND', PENUKARAN_BERES: 'PENDINGAN BERES (PB)',
    REQUEST_DARI_PEMBELI: 'REQUEST DARI PEMBELI', KURANG: 'KURANG KIRIM', BUKAN_BARANG_KITA: 'BUKAN BARANG KITA',
    DIPAKAI_ADMIN: 'DIPAKAI ADMIN', BARANG_TIDAK_ADA: 'BARANG TIDAK ADA', BUKAN_RETUR: 'BUKAN RETUR', ALASAN_LAINNYA: 'ALASAN LAINNYA'
  };
  var WAJIB_ALASAN = { REJECT: 'Alasan ditolak', ALASAN_LAINNYA: 'Tulis alasan' };
  var WAJIB_PERGANTIAN = { PENUKARAN_BERES: 1, REQUEST_DARI_PEMBELI: 1 };
  var IKON_MATA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';

  var sesi = null, seq = 0, muatKe = 0, sedangSimpan = false;

  function $q(s) { return $(s); }
  function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function up(t) { return String(t || '').trim().toUpperCase(); }
  function kodeK(id) { return 'K-' + ('00000' + id).slice(-5); }
  function hidup() { return $('#bo-scan').length > 0; }
  function aktif() { return $('#tab-buka-otomatis').hasClass('active'); }
  function bunyi(id) { if (typeof suaraScan === 'function') { suaraScan(id); } }

  function itemDari(sku) { return sesi.data.items.filter(function (i) { return up(i.sku) === up(sku); })[0]; }
  function sisaSku(it) { return Math.max(0, it.sisa); }
  function alokasi() {
    var m = {};
    sesi.baris.forEach(function (b) { m[up(b.sku)] = (m[up(b.sku)] || 0) + (parseInt(b.qty, 10) || 0); });
    return m;
  }
  function batasQty(b) {
    var it = itemDari(b.sku), al = alokasi()[up(b.sku)] || 0;
    return sisaSku(it) - (al - (parseInt(b.qty, 10) || 0));
  }
  function barisBaru(r) { return { id: ++seq, sku: r.sku, qty: r.qty, status: r.status || '', alasan: r.alasan || '', perg: r.perg || '', sumber: r.sumber, catatan: r.catatan || '', diubah: !!r.diubah }; }

  // ---------- validasi ----------
  function validasi() {
    var galat = [], per = {}, al = alokasi();
    sesi.baris.forEach(function (b) {
      var e = [], q = parseInt(b.qty, 10) || 0;
      if (q < 1) e.push('Qty minimal 1');
      if (!b.status) e.push('Pilih status');
      if (WAJIB_ALASAN[b.status] && !String(b.alasan).trim()) e.push(WAJIB_ALASAN[b.status] + ' wajib diisi');
      if (WAJIB_PERGANTIAN[b.status] && !String(b.perg).trim()) e.push('SKU pergantian wajib diisi');
      if (WAJIB_ALASAN[b.status] && String(b.alasan).length > 255) e.push('Alasan maksimal 255 karakter');
      if (WAJIB_PERGANTIAN[b.status] && String(b.perg).length > 255) e.push('SKU pergantian maksimal 255 karakter');
      per[b.id] = e;
      if (e.length) galat.push(e[0]);
    });
    var lebih = [], kurang = [];
    sesi.data.items.forEach(function (it) {
      var a = al[up(it.sku)] || 0;
      if (a > sisaSku(it)) lebih.push(it.sku);
      if (sisaSku(it) > 0 && a < sisaSku(it)) kurang.push(it.sku);
    });
    return { per: per, galat: galat, lebih: lebih, kurang: kurang, al: al };
  }
  function pesanValidasi(v) {
    var m = [];
    if (v.lebih.length) m.push('Qty melebihi sisa: ' + v.lebih.join(', '));
    if (v.galat.length) m.push(v.galat.length + ' baris belum lengkap');
    if (!m.length && v.kurang.length) m.push('Belum semua qty dialokasikan');
    return m.join(' · ');
  }
  function perbaruiFoot() {
    var v = validasi();
    $('#bo-validasi').text(pesanValidasi(v));
    $('#bo-simpan').prop('disabled', sedangSimpan || !sesi.baris.length || v.lebih.length > 0 || v.galat.length > 0);
  }

  // ---------- render ----------
  function render() {
    var s = sesi;
    if (!s) { return; }
    $('#bo-kosong').addClass('bo-hide');
    $('#bo-ringkas').removeClass('bo-hide');
    $('#bo-hasil').addClass('bo-hide');
    if (s.ditolak) {
      $('#bo-ringkas').html('<div class="bo-l1" style="color:#b91c1c">Ditolak</div><div class="bo-muted" style="margin-top:4px">' + esc(s.ditolak) + '</div>');
      $('#bo-rencana').addClass('bo-hide');
      return;
    }
    $('#bo-rencana').removeClass('bo-hide');
    var d = s.data;

    var h = '<div class="bo-l1">' + esc(d.noresi) + '</div><div class="bo-l2">';
    if (d.cara !== 'resi asli') { h += '<span class="bo-chip">discan: ' + esc(d.diinput) + '</span>'; }
    if (!d.komplain.length) { h += '<span class="bo-chip warn">Tanpa komplain</span>'; }
    else if (d.komplain.length > 1) { h += '<span class="bo-chip warn">' + d.komplain.length + ' komplain cocok</span>'; }
    else {
      var c = d.komplain[0];
      h += '<span class="bo-chip ok">' + kodeK(c.id) + '</span><span class="bo-chip">' + esc(c.kategori) + '</span>' + (c.hasil ? '<span class="bo-chip">CCTV ' + esc(c.hasil) + '</span>' : '');
      if (c.via !== 'resi') { h += '<span class="bo-chip">via ' + esc(c.via) + '</span>'; }
    }
    h += '</div><details style="margin-top:10px"><summary>Detail</summary><dl class="bo-kv">' +
      '<dt>No. pesanan</dt><dd>' + esc(d.pesanan) + '</dd>' +
      '<dt>Toko</dt><dd>' + esc(d.toko) + ' · ' + esc(d.marketplace) + '</dd>' +
      '<dt>Terima Retur</dt><dd>' + esc(d.terima || '-') + '</dd>';
    d.komplain.forEach(function (c) {
      h += '<dt>' + kodeK(c.id) + '</dt><dd>SKU komplain: ' + (c.items.length ? c.items.map(function (i) { return esc(i.sku) + ' ×' + i.qty; }).join(', ') : '-') +
        (c.no_resi_retur ? ' · resi pengembalian ' + esc(c.no_resi_retur) : '') + '</dd>';
    });
    h += '</dl></details>';
    $('#bo-ringkas').html(h);

    var p = '';
    if (s.catatanAwal) { p += '<div class="bo-alert">' + esc(s.catatanAwal) + '</div>'; }
    if (d.sisa_total <= 0) { p += '<div class="bo-alert ok">Semua qty resi ini sudah diproses. Untuk mengubah, pakai menu Update Retur Komplain.</div>'; }
    d.peringatan.forEach(function (w) { p += '<div class="bo-alert">' + esc(w) + '</div>'; });
    if (d.otomatis && d.sisa_total > 0) { p += '<div class="bo-small bo-muted" style="margin-bottom:10px">Diisi otomatis dari komplain. Periksa dulu sebelum menyimpan.</div>'; }
    $('#bo-pesan').html(p);

    var v = validasi(), t = '', adaBaris = false, selesai = 0;
    d.items.forEach(function (it) {
      var sisa = sisaSku(it), a = v.al[up(it.sku)] || 0;
      var rows = s.baris.filter(function (b) { return up(b.sku) === up(it.sku); });
      if (sisa <= 0 && !rows.length) { selesai++; return; } // sudah ditetapkan: tidak ditampilkan lagi
      var warna = a > sisa ? '#b91c1c' : (a < sisa ? '#b45309' : '#6b7280');
      var fotoSrc = it.foto || it.foto_lokal;
      var fotoHtml = fotoSrc
        ? '<div class="bo-foto"><img src="' + esc(fotoSrc) + '"' + (it.foto && it.foto_lokal ? ' data-foto-lokal="' + esc(it.foto_lokal) + '"' : '') + ' alt="' + esc(it.nama) + '">' +
          '<button type="button" class="bo-eye foto-preview" data-foto="' + esc(fotoSrc) + '" data-foto-lokal="' + esc(it.foto && it.foto_lokal ? it.foto_lokal : '') + '" title="Lihat foto penuh" aria-label="Lihat foto penuh">' + IKON_MATA + '</button></div>'
        : '<div class="bo-foto tanpa" title="Foto SKU belum ada di data produk">Foto belum ada</div>';
      t += '<div class="bo-grp">' + fotoHtml + '<div class="bo-body"><div class="bo-ghead"><div><b>' + esc(it.sku) + '</b><div class="bo-small bo-muted">' + esc(it.nama) +
        (fotoSrc ? '' : ' · <span style="color:#b91c1c">lengkapi foto di data SKU</span>') + (it.rak ? ' · rak ' + esc(it.rak) : '') + '</div></div>' +
        '<div class="bo-alok" style="color:' + warna + '">' + a + ' / ' + sisa + ' pcs</div></div><div class="bo-lines">';
      rows.forEach(function (b) {
        adaBaris = true;
        var e = v.per[b.id] || [], maks = batasQty(b), qn = parseInt(b.qty, 10) || 0;
        var kls = b.diubah ? 'edit' : (b.sumber === 'manual' ? 'manual' : '');
        var judul = b.diubah ? 'Diedit' : (b.sumber === 'manual' ? 'Manual' : 'Diisi otomatis');
        t += '<div class="bo-line" data-id="' + b.id + '">' +
          '<div class="bo-stepper"><button type="button" data-act="qty-" aria-label="Kurangi qty"' + (qn <= 1 ? ' disabled' : '') + '>−</button>' +
          '<input type="number" inputmode="numeric" min="1" max="' + maks + '" value="' + esc(b.qty) + '" data-f="qty" aria-label="Qty">' +
          '<button type="button" data-act="qty+" aria-label="Tambah qty"' + (qn >= maks ? ' disabled' : '') + '>+</button></div>' +
          '<div class="bo-st"><span class="bo-dot ' + kls + '" title="' + judul + '"></span><select data-f="status" aria-label="Status detail"><option value="">— pilih status —</option>' +
          Object.keys(STATUS).map(function (k) { return '<option value="' + k + '"' + (b.status === k ? ' selected' : '') + '>' + STATUS[k] + '</option>'; }).join('') +
          '</select>' + (e.length ? '<span class="bo-err">' + esc(e[0]) + '</span>' : '') + '</div><div class="bo-ket">';
        if (WAJIB_ALASAN[b.status]) { t += '<input type="text" data-f="alasan" maxlength="255" placeholder="' + WAJIB_ALASAN[b.status] + '" value="' + esc(b.alasan) + '">'; }
        else if (WAJIB_PERGANTIAN[b.status]) { t += '<input type="text" data-f="perg" maxlength="255" placeholder="SKU pergantian" value="' + esc(b.perg) + '">'; }
        else if (b.catatan) { t += '<span class="bo-cat">' + esc(b.catatan) + '</span>'; }
        t += '</div><div class="bo-act"><button type="button" class="bo-icon ok-i" data-act="simpan-baris" title="Simpan baris ini saja" aria-label="Simpan baris ini saja">✓</button>' +
          '<button type="button" class="bo-icon" data-act="pecah" title="Pecah: pindahkan 1 pcs ke baris baru" aria-label="Pecah">⑂</button>' +
          '<button type="button" class="bo-icon" data-act="hapus" title="Hapus baris" aria-label="Hapus baris">✕</button></div></div>';
      });
      if (sisa > 0) { t += '<div><button type="button" class="bo-link" data-act="tambah" data-sku="' + esc(it.sku) + '">+ tambah baris</button></div>'; }
      t += '</div></div></div>';
    });
    if (selesai) { t = '<div class="bo-small bo-muted" style="margin-bottom:10px">' + selesai + ' SKU sudah selesai diproses dan tidak ditampilkan lagi. Hanya yang belum ditetapkan yang perlu diisi.</div>' + t; }
    $('#bo-isi').html(t);
    perbaruiFoot();
    if (!adaBaris) { $('#bo-simpan').prop('disabled', true); }
  }

  // ---------- muat dari server ----------
  // mode: 'baru' (scan baru, rencana dari server) | 'rencana' (ganti semua baris dengan rencana server) | 'item' (perbarui sisa qty saja, baris tetap)
  function muat(input, mode, sesudah) {
    var token = ++muatKe;
    if (mode === 'baru') { // tampilkan keadaan memuat supaya rencana resi sebelumnya tidak terlihat seperti hasil scan ini
      $('#bo-kosong').addClass('bo-hide'); $('#bo-hasil').addClass('bo-hide'); $('#bo-rencana').addClass('bo-hide');
      $('#bo-ringkas').removeClass('bo-hide').html('<div class="bo-muted">Memuat ' + esc(input) + '…</div>');
    }
    $.ajax({
      url: BASE + 'retur/buka-otomatis-rencana/' + encodeURIComponent(input), type: 'GET', dataType: 'json', cache: false, timeout: 30000,
      success: function (res) {
        if (token !== muatKe || !hidup()) { return; }
        if (res.status !== 'ok') {
          sesi = { ditolak: res.message || 'Gagal memuat resi.', baris: [], data: null };
          bunyi((res.message || '').toLowerCase().indexOf('tidak ditemukan') !== -1 ? 'audio-tidak-ditemukan' : 'audio-wrong');
          render();
          return;
        }
        var catatan = (mode === 'baru' && sesi && sesi.data && sesi.baris.some(function (b) { return b.diubah; }))
          ? 'Rencana resi ' + sesi.data.noresi + ' yang belum disimpan telah diganti oleh scan ini.' : '';
        if (mode === 'item' && sesi && sesi.data) {
          sesi.data = res.data;
          sesi.baris = sesi.baris.filter(function (b) { return !!itemDari(b.sku) && sisaSku(itemDari(b.sku)) > 0; });
        } else {
          sesi = { input: input, data: res.data, baris: res.data.baris.map(barisBaru), catatanAwal: catatan };
        }
        render();
        if (sesudah) { sesudah(); }
      },
      error: function () {
        if (token !== muatKe) { return; }
        sesi = { ditolak: 'Gagal memuat data resi. Periksa koneksi lalu scan ulang.', baris: [], data: null };
        bunyi('audio-wrong');
        render();
      }
    });
  }

  // ---------- simpan ----------
  function payloadBaris(rows) {
    return rows.map(function (b) {
      return { sku: b.sku, qty: Math.max(1, parseInt(b.qty, 10) || 1), status: b.status,
        sku_pergantian: WAJIB_PERGANTIAN[b.status] ? String(b.perg).trim() : '', alasan_ditolak: WAJIB_ALASAN[b.status] ? String(b.alasan).trim() : '' };
    });
  }
  function kirimSimpan(rows, sukses) {
    if (sedangSimpan) { return; }
    sedangSimpan = true; perbaruiFoot();
    $.ajax({
      url: BASE + 'retur/save-buka-otomatis', type: 'POST', dataType: 'json', timeout: 30000,
      data: { noresi: sesi.data.noresi, baris: JSON.stringify(payloadBaris(rows)) },
      success: function (res) {
        sedangSimpan = false;
        if (res.code === 201) { bunyi('audio-alexis'); sukses(res.message); }
        else { bunyi('audio-wrong'); perbaruiFoot(); $('#bo-validasi').text(res.message || 'Gagal menyimpan.'); }
      },
      error: function () { sedangSimpan = false; bunyi('audio-wrong'); perbaruiFoot(); $('#bo-validasi').text('Gagal menghubungi server. Tidak ada data yang diubah; coba lagi.'); }
    });
  }
  function tampilHasil(pesan) {
    var sisa = sesi.data.sisa_total;
    $('#bo-hasil').html('<div class="bo-ok-line">Tersimpan · ' + esc(pesan) + '</div><div class="bo-small bo-muted" style="margin-top:2px">' +
      (sisa > 0 ? sisa + ' pcs belum diproses; resi masih bisa dibuka lagi.' : 'Semua qty resi sudah diproses.') + ' Scan resi berikutnya.</div>').removeClass('bo-hide');
  }
  function simpanBaris(b) {
    var v = validasi(), e = v.per[b.id] || [], q = parseInt(b.qty, 10) || 0;
    if (e.length) { $('#bo-validasi').text('Lengkapi baris ini dulu: ' + e[0]); return; }
    if (q > sisaSku(itemDari(b.sku))) { $('#bo-validasi').text('Qty melebihi sisa SKU ' + b.sku); return; }
    kirimSimpan([b], function (pesan) {
      sesi.baris = sesi.baris.filter(function (x) { return x !== b; });
      muat(sesi.data.noresi, 'item', function () { tampilHasil('1 baris (' + b.sku + ' ×' + q + ')'); $('#bo-scan').trigger('focus'); });
    });
  }
  function bukaKonfirmasi() {
    var v = validasi();
    if (v.lebih.length || v.galat.length || !sesi.baris.length) { return; }
    var total = sesi.data.total_qty, sudah = total - sesi.data.sisa_total;
    var now = sesi.baris.reduce(function (a, b) { return a + (parseInt(b.qty, 10) || 0); }, 0);
    var lengkap = sudah + now === total;
    $('#bo-kf-resi').text(sesi.data.noresi);
    $('#bo-kf-total').text(total); $('#bo-kf-sudah').text(sudah); $('#bo-kf-now').text(now);
    $('#bo-kf-status').html(lengkap
      ? '<div class="bo-alert ok">Qty cocok: total resi ' + total + ' = ' + (sudah ? sudah + ' sudah diproses + ' : '') + now + ' disimpan sekarang. Resi ini selesai setelah disimpan.</div>'
      : '<div class="bo-alert">Qty belum lengkap: ' + (total - sudah - now) + ' pcs belum dialokasikan. Resi tetap terbuka untuk Buka Retur berikutnya.</div>');
    $('#bo-kf-baris').html(sesi.baris.map(function (b) {
      return '<tr><td>' + esc(b.sku) + '</td><td>' + esc(STATUS[b.status]) + (WAJIB_PERGANTIAN[b.status] && b.perg ? ' → ' + esc(b.perg) : '') +
        (WAJIB_ALASAN[b.status] && b.alasan ? '<div class="bo-small" style="color:#6b7280">' + esc(b.alasan) + '</div>' : '') + '</td><td class="bo-n">' + esc(b.qty) + '</td></tr>';
    }).join(''));
    $('#bo-kf-judul').text(lengkap ? 'Simpan hasil Buka Retur' : 'Simpan sebagian?');
    $('#bo-konfirmasi').removeClass('bo-hide');
    $('#bo-kf-ok').trigger('focus');
  }
  function tutupKonfirmasi() { $('#bo-konfirmasi').addClass('bo-hide'); $('#bo-scan').trigger('focus'); }

  // ---------- fokus: kursor selalu kembali ke kolom scan ----------
  function modalTerbuka() { return !$('#bo-konfirmasi').hasClass('bo-hide') || $('#fotoModal').is(':visible'); }
  function kunciFokus() {
    if (!hidup() || !aktif() || modalTerbuka()) { return; }
    var a = document.activeElement;
    if (a && a.id !== 'bo-scan' && /^(INPUT|SELECT|TEXTAREA)$/.test(a.tagName) && $(a).closest('#tab-buka-otomatis').length) { return; } // sedang mengedit
    if (!a || a.id !== 'bo-scan') { $('#bo-scan').trigger('focus'); }
  }
  function prosesScan() {
    var nilai = $.trim($('#bo-scan').val());
    $('#bo-scan').val(''); // kosongkan dulu supaya scan berikutnya mulai dari kolom bersih
    if (nilai !== '') { muat(nilai, 'baru'); }
  }

  // ---------- pemasangan event (dilepas otomatis saat pindah menu SPA) ----------
  $(document).off('.boOtomatis');
  $(document).on('keydown.boOtomatis', '#bo-scan', function (e) { if (e.key === 'Enter') { e.preventDefault(); prosesScan(); } });
  $(document).on('keydown.boOtomatis', function (e) {
    if (!hidup()) { $(document).off('.boOtomatis'); return; }
    if (e.key === 'Escape' && !$('#bo-konfirmasi').hasClass('bo-hide')) { tutupKonfirmasi(); return; }
    if (e.key === 'Enter' && e.target.id !== 'bo-scan' && aktif() && $(e.target).closest('#tab-buka-otomatis').length && /^(INPUT|SELECT)$/.test(e.target.tagName)) {
      e.preventDefault(); $('#bo-scan').trigger('focus');
    }
  });
  $(document).on('click.boOtomatis focusout.boOtomatis', function () { if (!hidup()) { $(document).off('.boOtomatis'); return; } setTimeout(kunciFokus, 60); });
  $(document).on('shown.bs.tab.boOtomatis', 'a[data-toggle="tab"]', function (e) { if (hidup() && e.target.hash === '#tab-buka-otomatis') { $('#bo-scan').trigger('focus'); } });

  function temukan(el) {
    var id = parseInt($(el).closest('[data-id]').attr('data-id'), 10);
    return sesi.baris.filter(function (b) { return b.id === id; })[0] || null;
  }
  $(document).on('input.boOtomatis', '#tab-buka-otomatis [data-f]', function () {
    if (!sesi || !sesi.data) { return; }
    var b = temukan(this); if (!b) { return; }
    b[$(this).attr('data-f')] = this.value; b.diubah = true; perbaruiFoot();
  });
  $(document).on('change.boOtomatis', '#tab-buka-otomatis [data-f]', function () {
    if (!sesi || !sesi.data) { return; }
    if ($(this).attr('data-f') === 'qty') { var b = temukan(this); if (b) { b.qty = Math.max(1, Math.min(parseInt(b.qty, 10) || 1, Math.max(1, batasQty(b)))); } }
    render(); setTimeout(kunciFokus, 0);
  });
  $(document).on('click.boOtomatis', '#tab-buka-otomatis [data-act]', function () {
    if (!sesi || !sesi.data) { return; }
    var act = $(this).attr('data-act');
    if (act === 'tambah') {
      var sku = $(this).attr('data-sku'), it = itemDari(sku);
      var nb = barisBaru({ sku: sku, qty: Math.max(1, sisaSku(it) - (alokasi()[up(sku)] || 0)), status: '', sumber: 'manual', diubah: true });
      sesi.baris.push(nb);
    } else {
      var b = temukan(this); if (!b) { return; }
      if (act === 'simpan-baris') { simpanBaris(b); return; }
      if (act === 'hapus') { sesi.baris = sesi.baris.filter(function (x) { return x !== b; }); }
      if (act === 'qty+') { b.qty = Math.min((parseInt(b.qty, 10) || 0) + 1, batasQty(b)); b.diubah = true; }
      if (act === 'qty-') { b.qty = Math.max(1, (parseInt(b.qty, 10) || 1) - 1); b.diubah = true; }
      if (act === 'pecah') {
        if ((parseInt(b.qty, 10) || 0) < 2) { alert('Qty baris ini 1, tidak bisa dipecah.'); return; }
        b.qty = parseInt(b.qty, 10) - 1; b.diubah = true;
        sesi.baris.splice(sesi.baris.indexOf(b) + 1, 0, barisBaru({ sku: b.sku, qty: 1, status: '', sumber: 'manual', diubah: true }));
      }
    }
    render();
  });
  $(document).on('click.boOtomatis', '#bo-ulang', function () {
    if (!sesi || !sesi.data) { return; }
    muat(sesi.data.noresi, 'rencana');
  });
  $(document).on('click.boOtomatis', '#bo-simpan', bukaKonfirmasi);
  $(document).on('click.boOtomatis', '#bo-kf-batal', tutupKonfirmasi);
  $(document).on('click.boOtomatis', '#bo-konfirmasi', function (e) { if (e.target === this) { tutupKonfirmasi(); } });
  $(document).on('click.boOtomatis', '#bo-kf-ok', function () {
    var rows = sesi.baris.slice(), ringkas = rows.length + ' baris';
    $('#bo-konfirmasi').addClass('bo-hide');
    kirimSimpan(rows, function () {
      muat(sesi.data.noresi, 'rencana', function () { tampilHasil(ringkas); $('#bo-scan').trigger('focus'); });
    });
  });

  // fokus awal bila tab ini yang aktif saat halaman dibuka
  setTimeout(function () { if (hidup() && aktif()) { $('#bo-scan').trigger('focus'); } }, 100);
})();
</script>
