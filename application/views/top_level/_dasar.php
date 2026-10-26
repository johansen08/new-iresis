<?php
// Bagian bersama menu Top Level (Cek, Request, Upload, Stok): CSS dan helper JS (window.TL).
// Dimuat di awal setiap view; JS-nya hanya didefinisikan sekali per halaman, jadi aman
// walau SPA memuat beberapa menu Top Level berurutan tanpa reload.
?>
<link rel="stylesheet" type="text/css" href="assets/css/top_level.css?v=<?= @filemtime(FCPATH . 'assets/css/top_level.css') ?: '1' ?>" />
<script>
(function () {
  if (window.TL && window.TL.v >= 2) return; // naikkan v bila helper berubah, supaya sesi yang sudah terbuka memuat ulang helper
  var T = window.TL = { v: 2 };

  var IC = {
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    download: '<path d="M12 4v11"/><path d="m7 11 5 5 5-5"/><path d="M5 20h14"/>',
    scan: '<path d="M4 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M20 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2"/><path d="M8 8v8M12 8v8M16 8v8"/>',
    print: '<path d="M7 9V4h10v5"/><rect x="4" y="9" width="16" height="8" rx="1"/><path d="M7 14h10v6H7z"/>',
    upload: '<path d="M12 16V5m0 0-4 4m4-4 4 4"/><path d="M5 19h14"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    check: '<path d="m5 12 4.5 4.5L19 7"/>',
    x: '<path d="M6 6l12 12M18 6 6 18"/>',
    file: '<path d="M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7z"/><path d="M14 3v4h4"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
    alert: '<path d="M12 4 3 20h18z"/><path d="M12 10v4M12 17h.01"/>',
    inbox: '<path d="M4 13l2-8h12l2 8v6H4z"/><path d="M4 13h5l1 2h4l1-2h5"/>',
    db: '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>'
  };

  T.esc = function (s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  };
  T.ic = function (n, s) {
    s = s || 16;
    return '<svg class="tl-ic" style="width:' + s + 'px;height:' + s + 'px" width="' + s + '" height="' + s + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + IC[n] + '</svg>';
  };
  T.info = function (teks) {
    return '<button type="button" class="tl-ib" data-tip="' + T.esc(teks) + '" aria-label="Informasi">' + T.ic('info', 15) + '</button>';
  };
  T.pill = function (teks, kelas) {
    return '<span class="tl-pill ' + (kelas || '') + '">' + T.esc(teks) + '</span>';
  };
  T.empty = function (ikon, teks) {
    return '<div class="tl-empty">' + T.ic(ikon, 28) + '<span>' + T.esc(teks) + '</span></div>';
  };
  /** 'Y-m-d H:i:s' -> 'd/m/Y H:i:s' (tanggal dan jam sampai detik). */
  T.tgl = function (s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}:\d{2}:\d{2}))?/.exec(s || '');
    return m ? m[3] + '/' + m[2] + '/' + m[1] + (m[4] ? ' ' + m[4] : '') : '';
  };
  T.now = function () {
    var d = new Date(), p = function (n) { return ('0' + n).slice(-2); };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
  };

  T.toast = function (pesan, buruk) {
    var $w = $('#tl-toasts');
    if (!$w.length) $w = $('<div id="tl-toasts"></div>').appendTo('body');
    var $t = $('<div class="tl-toast"></div>').toggleClass('bad', !!buruk).text(pesan).appendTo($w);
    setTimeout(function () { $t.remove(); }, 4000);
  };

  // Semua panggilan server memakai POST dan membalas {code, message, data}. Kalau balasan bukan JSON
  // (mis. halaman error PHP), cuplikannya ikut ditampilkan supaya mudah didiagnosa.
  function kirim(url, data, ok, gagal, form) {
    var opt = { url: url, type: 'POST', data: data, dataType: 'text', timeout: 600000 };
    if (form) { opt.processData = false; opt.contentType = false; }
    $.ajax(opt).done(function (txt) {
      var j;
      try { j = JSON.parse(txt); } catch (e) {
        if (window.console) console.error('Respons mentah:', txt.substr(0, 600));
        if (gagal) gagal('Respons server bukan JSON: ' + txt.replace(/<[^>]*>/g, ' ').substr(0, 140), null);
        return;
      }
      if (j.code === 200) { if (ok) ok(j.data || {}, j); }
      else if (gagal) gagal(j.message || 'Gagal', j.data || null);
    }).fail(function () {
      if (gagal) gagal('Koneksi ke server gagal.', null);
    });
  }
  T.post = function (url, data, ok, gagal) { kirim(url, data, ok, gagal, false); };
  T.postForm = function (url, fd, ok, gagal) { kirim(url, fd, ok, gagal, true); };
  T.galat = function (pesan) { T.toast(pesan, true); };

  // ---------- modal ----------
  var modalAktif = null;
  T.closeModal = function () {
    if (modalAktif) { modalAktif.remove(); modalAktif = null; }
  };
  /** o: {judul, wide, body, foot}. Mengembalikan elemen dialog (jQuery). */
  T.modal = function (o) {
    T.closeModal();
    var $ov = $('<div class="tl-ov"></div>');
    var $d = $('<div class="tl tl-dlg" role="dialog" aria-modal="true"></div>').toggleClass('wide', !!o.wide).appendTo($ov);
    $d.html('<div class="tl-dh"><h2></h2><button type="button" class="tl-btn ghost sm" data-tl-tutup aria-label="Tutup">' + T.ic('x') + '</button></div><div class="tl-body"></div>');
    $d.find('h2').text(o.judul || '');
    $d.find('.tl-body').html(o.body || '');
    $ov.on('mousedown', function (e) { if (e.target === $ov[0]) T.closeModal(); });
    $d.on('click', '[data-tl-tutup]', function () { T.closeModal(); });
    $ov.appendTo('body');
    modalAktif = $ov;
    return $d;
  };
  T.adaModal = function () { return !!modalAktif; };

  // ---------- isian dengan saran yang mengikuti ketikan ----------
  /**
   * cari(q, kirim): ambil saran untuk ketikan q, lalu panggil kirim([{nilai, label, sub, grup}]).
   * pilih(item): dipanggil saat saran dipilih (klik, atau Enter pada saran yang disorot).
   * Panah atas/bawah menyorot, Enter memilih, Esc menutup. Isian tetap boleh berisi teks bebas.
   */
  T.combo = function ($in, cari, pilih) {
    var $wrap = $in.parent().css('position', 'relative'), $list = $('<div class="tl-combo-list"></div>').hide().appendTo($wrap);
    var items = [], aktif = -1, timer = null, seq = 0;
    function tutup() { $list.hide(); aktif = -1; }
    function gambar() {
      if (!items.length) { tutup(); return; }
      var h = '', grup = null;
      $.each(items, function (i, it) {
        if (it.grup && it.grup !== grup) { grup = it.grup; h += '<div class="tl-opt-h">' + T.esc(grup) + '</div>'; }
        h += '<div class="tl-opt' + (i === aktif ? ' on' : '') + '" data-i="' + i + '"><span>' + T.esc(it.label) + '</span>' + (it.sub ? '<small>' + T.esc(it.sub) + '</small>' : '') + '</div>';
      });
      $list.html(h).show();
    }
    function ambil() {
      var q = $.trim($in.val()), n = ++seq;
      cari(q, function (daftar) { if (n !== seq) return; items = daftar || []; aktif = -1; gambar(); });
    }
    function ambilItem(i) {
      var it = items[i];
      if (!it) return;
      $in.val(it.nilai);
      tutup();
      if (pilih) pilih(it);
    }
    $in.on('focus', ambil);
    $in.on('input', function () { clearTimeout(timer); timer = setTimeout(ambil, 150); });
    $in.on('keydown', function (e) {
      if (!$list.is(':visible')) { if (e.key === 'ArrowDown') ambil(); return; }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        aktif = (aktif + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        gambar();
        var el = $list.find('.tl-opt.on')[0];
        if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' && aktif >= 0) { e.preventDefault(); e.stopPropagation(); ambilItem(aktif); }
      else if (e.key === 'Escape') { e.stopPropagation(); tutup(); }
    });
    $in.on('blur', function () { setTimeout(tutup, 150); });
    $list.on('mousedown', '.tl-opt', function (e) { e.preventDefault(); ambilItem(+$(this).data('i')); });
  };

  // ---------- ikon info: hover/fokus menampilkan, klik menyematkan, Esc menutup ----------
  var tip = document.getElementById('tl-tip') || document.createElement('div');
  tip.id = 'tl-tip';
  tip.setAttribute('role', 'tooltip');
  document.body.appendChild(tip);
  var tipEl = null, sematkan = false;
  function tampil(el) {
    tipEl = el;
    tip.textContent = el.getAttribute('data-tip');
    tip.className = 'on';
    var r = el.getBoundingClientRect(), w = tip.offsetWidth, h = tip.offsetHeight;
    var x = Math.max(8, Math.min(r.left + r.width / 2 - w / 2, window.innerWidth - w - 8));
    var y = r.bottom + 8;
    if (y + h > window.innerHeight - 8) y = r.top - h - 8;
    tip.style.left = x + 'px';
    tip.style.top = y + 'px';
  }
  function sembunyi() { tip.className = ''; tipEl = null; sematkan = false; }
  T.sembunyiTip = sembunyi;
  $(document).off('.tltip');
  $(document).on('mouseover.tltip focusin.tltip', '[data-tip]', function () { if (!sematkan) tampil(this); });
  $(document).on('mouseout.tltip focusout.tltip', '[data-tip]', function () { if (!sematkan) sembunyi(); });
  $(document).on('click.tltip', function (e) {
    var t = $(e.target).closest('[data-tip]')[0];
    if (t) {
      if (sematkan && tipEl === t) sembunyi(); else { tampil(t); sematkan = true; }
    } else if (sematkan) sembunyi();
  });
  $(document).on('keydown.tltip', function (e) {
    if (e.key !== 'Escape') return;
    if (tipEl) sembunyi(); else T.closeModal();
  });
})();
</script>
