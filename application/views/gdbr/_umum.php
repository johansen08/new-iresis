<?php
// Fungsi JS bersama Scan Resi GD-BR & Rekap Resi GD-BR (window.GDB). Di-include oleh kedua view.
?>
<script type="text/javascript">
window.GDB = (function () {
    'use strict';
    var U = {};
    U.URL = {
        cek: '<?= base_url('gdbr/cek') ?>',
        simpan: '<?= base_url('gdbr/simpan') ?>',
        sku_saran: '<?= base_url('gdbr/sku-saran') ?>',
        sku_detail: '<?= base_url('gdbr/sku-detail') ?>',
        rekap_data: '<?= base_url('gdbr/rekap-data') ?>',
        detail: '<?= base_url('gdbr/detail') ?>',
        ubah_cek: '<?= base_url('gdbr/ubah-cek') ?>',
        hapus_resi: '<?= base_url('gdbr/hapus-resi') ?>'
    };

    U.esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    var esc = U.esc;
    U.p2 = function (n) { return (n < 10 ? '0' : '') + n; };
    // 'Y-m-d H:i:s' -> 'dd/mm/yyyy hh:mm:ss'
    U.fmtD = function (s) {
        if (!s) return '';
        var m = /^(\d{4})-(\d\d)-(\d\d)[ T](\d\d):(\d\d):(\d\d)/.exec(s);
        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] + ':' + m[6] : s;
    };
    // Jam klien dalam format server, untuk riwayat scan yang ditolak (tidak ada di database)
    U.sekarang = function () {
        var d = new Date();
        return d.getFullYear() + '-' + U.p2(d.getMonth() + 1) + '-' + U.p2(d.getDate()) + ' ' + U.p2(d.getHours()) + ':' + U.p2(d.getMinutes()) + ':' + U.p2(d.getSeconds());
    };
    U.ini = function (n) {
        return String(n || '').split(' ').filter(Boolean).map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase();
    };

    // ───── ikon ─────
    var IC = {
        copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        eye: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        check: '<path d="M20 6 9 17l-5-5"/>',
        down: '<path d="m6 9 6 6 6-6"/>'
    };
    U.IC = IC;
    U.ic = function (p) { return '<svg class="ic" viewBox="0 0 24 24">' + p + '</svg>'; };

    // ───── salin ─────
    U.cp = function (t) {
        return '<button type="button" class="cp" data-copy="' + esc(t) + '" title="Salin" aria-label="Salin ' + esc(t) + '">' + U.ic(IC.copy) + '</button>';
    };
    function fbSalin(t) {
        var a = document.createElement('textarea');
        a.value = t; a.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(a); a.select();
        var r = false;
        try { r = document.execCommand('copy'); } catch (e) {}
        a.remove();
        return r;
    }
    jQuery(document).off('click.gdbrcp').on('click.gdbrcp', '.gdbr [data-copy]', function (e) {
        e.stopPropagation();
        var b = this, t = b.getAttribute('data-copy');
        var ok = function () {
            b.classList.add('ok'); b.innerHTML = U.ic(IC.check);
            setTimeout(function () { b.classList.remove('ok'); b.innerHTML = U.ic(IC.copy); }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(t).then(ok, function () { if (fbSalin(t)) ok(); });
        else if (fbSalin(t)) ok();
    });

    // ───── lapisan di <body> (drawer, popup, foto besar) supaya position:fixed tidak terjebak container halaman ─────
    U.ovl = function () {
        var o = document.getElementById('gdbr_ovl');
        if (!o) {
            o = document.createElement('div');
            o.id = 'gdbr_ovl';
            o.className = 'gdbr gdbr-ovl';
            document.body.appendChild(o);
        }
        return o;
    };
    U.ovlBersih = function () { var o = document.getElementById('gdbr_ovl'); if (o) o.remove(); };

    U.toast = function (t) {
        var d = document.createElement('div');
        d.className = 'toast'; d.textContent = t;
        U.ovl().appendChild(d);
        setTimeout(function () { d.remove(); }, 2600);
    };

    // ───── badge hasil cek: h = {kurang, lebih, salah, lain, sesuai} ─────
    U.badges = function (h) {
        if (h.sesuai) return '<span class="b ok">SESUAI</span>';
        return [
            h.kurang && '<span class="b bad">KURANG ' + h.kurang + '</span>',
            h.lebih && '<span class="b warn">LEBIH ' + h.lebih + '</span>',
            h.salah && '<span class="b info">SALAH ' + h.salah + '</span>',
            h.lain && '<span class="b ind">LAINNYA</span>'
        ].filter(Boolean).join(' ');
    };

    // ───── foto SKU: URL asli dulu, salinan lokal cadangan (assets/js/foto_sku.js); tanpa foto = inisial ─────
    var WARNA = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#be185d', '#7c3aed', '#475569'];
    U.thumb = function (it, mata) {
        var sku = it.sku || it.id_sku || '', src = it.foto || it.foto_lokal || '';
        var img = src
            ? '<img class="th" src="' + esc(src) + '"' + (it.foto && it.foto_lokal ? ' data-foto-lokal="' + esc(it.foto_lokal) + '"' : '') + ' alt="Foto ' + esc(sku) + '">'
            : '<span class="th" style="background:' + WARNA[(String(sku).charCodeAt(0) + String(sku).length) % WARNA.length] + '">' + esc(String(sku).slice(0, 2)) + '</span>';
        return img + (mata
            ? '<button type="button" class="eye" data-lihat="1" data-sku="' + esc(sku) + '" data-rak="' + esc(it.no_rak || '') + '" data-foto="' + esc(it.foto || '') + '" data-lokal="' + esc(it.foto_lokal || '') +
              '" title="Lihat foto" aria-label="Lihat foto ' + esc(sku) + '">' + U.ic(IC.eye) + '</button>'
            : '');
    };
    jQuery(document).off('click.gdbrlb').on('click.gdbrlb', '.gdbr [data-lihat]', function (e) {
        e.stopPropagation();
        var b = this, foto = b.getAttribute('data-foto'), lokal = b.getAttribute('data-lokal'), src = foto || lokal, sku = b.getAttribute('data-sku'), rak = b.getAttribute('data-rak');
        var d = document.createElement('div');
        d.className = 'lb'; d.id = 'gdbr_lb';
        d.innerHTML = (src
            ? '<img src="' + esc(src) + '"' + (foto && lokal ? ' data-foto-lokal="' + esc(lokal) + '"' : '') + ' alt="Foto ' + esc(sku) + '">'
            : '<span class="th" style="width:min(86vw,70vh,420px);height:min(86vw,70vh,420px);font-size:64px;background:#475569">' + esc(String(sku).slice(0, 2)) + '</span>') +
            '<b>' + esc(sku) + '</b><small>' + (rak ? 'Rak ' + esc(rak) + ' · ' : '') + 'klik di mana saja untuk menutup</small>';
        d.addEventListener('click', function () { d.remove(); });
        U.ovl().appendChild(d);
    });
    if (!window.__gdbrEsc) {
        window.__gdbrEsc = true;
        document.addEventListener('keydown', function (e) {
            var l = document.getElementById('gdbr_lb');
            if (e.key === 'Escape' && l) { l.remove(); e.stopImmediatePropagation(); }
        }, true);
    }

    // ───── popup konfirmasi: onOk() mengembalikan teks error (popup tetap terbuka), kosong (tutup), atau Promise keduanya ─────
    U.modal = function (html, okLabel, onOk) {
        var o = document.createElement('div');
        o.className = 'mdo';
        o.innerHTML = '<div class="mdb" role="dialog" aria-modal="true">' + html + '<div id="gdbr_mderr"></div>' +
            '<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px"><button class="btn sec" type="button" data-m="x">Batal</button><button class="btn" type="button" data-m="ok">' + okLabel + '</button></div></div>';
        U.ovl().appendChild(o);
        var sibuk = false;
        var tutup = function () { if (o.parentNode) o.parentNode.removeChild(o); };
        var err = function (t) { o.querySelector('#gdbr_mderr').innerHTML = t ? '<div class="al bad">' + t + '</div>' : ''; };
        o.addEventListener('click', function (e) {
            var k = e.target.dataset && e.target.dataset.m;
            if (k === 'x' || e.target === o) { if (!sibuk) tutup(); }
            else if (k === 'ok' && !sibuk) {
                var r = onOk();
                if (r && typeof r.then === 'function') {
                    sibuk = true;
                    o.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                    r.then(function (t) {
                        sibuk = false;
                        o.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
                        if (t) err(t); else tutup();
                    });
                } else if (r) err(r); else tutup();
            }
        });
        var ok = o.querySelector('[data-m=ok]'); if (ok) ok.focus();
        return o;
    };

    // ───── POST/GET ke controller: resolve {code, message, data} ─────
    U.post = function (url, data) {
        return new Promise(function (ok, gagal) {
            jQuery.ajax({ url: url, type: 'POST', data: data, dataType: 'json', timeout: 60000 }).done(ok).fail(gagal);
        });
    };
    U.pesanGagal = function (x) {
        return 'Gagal menghubungi server' + (x && x.status ? ' (' + x.status + ')' : '') + '. Coba lagi.';
    };
    return U;
})();
</script>
