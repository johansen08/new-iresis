<?php
// Fungsi JS bersama Terima & Laporan Paket Cancel (window.PCU). Di-include oleh kedua view.
?>
<script type="text/javascript">
window.PCU = (function () {
    'use strict';
    var U = {};
    U.URL = {
        sku_saran: '<?= base_url('paket-cancel/sku-saran') ?>',
        sku_detail: '<?= base_url('paket-cancel/sku-detail') ?>'
    };
    U.NM = { PACKER: 'Packer', HO: 'HO', KURIR: 'Kurir' };
    U.IKON = { PACKER: '&#128230;', HO: '&#127970;', KURIR: '&#128666;' };

    U.root = function () { return document.getElementById('pc_root'); };
    U.g = function (id) { return document.getElementById('pc_' + id); };
    U.esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    U.al = function (id, tipe, html) { U.g(id).innerHTML = '<div class="al ' + tipe + '">' + html + '</div>'; };

    // POST JSON ke controller; resolve dengan balasan {code, message, data}
    U.post = function (url, data) {
        return new Promise(function (ok, gagal) {
            jQuery.ajax({ url: url, type: 'POST', data: data, dataType: 'json', timeout: 60000 }).done(ok).fail(gagal);
        });
    };
    U.pesanGagal = function (x) {
        return 'Gagal menghubungi server' + (x && x.status ? ' (' + x.status + ')' : '') + '. Coba lagi.';
    };

    U.jenis = function (qtyResi, qtyAda) {
        qtyResi = +qtyResi; qtyAda = +qtyAda;
        if (qtyResi === 0) return qtyAda > 0 ? 'EXTRA' : 'SESUAI';
        return qtyAda === qtyResi ? 'SESUAI' : (qtyAda < qtyResi ? 'KURANG' : 'LEBIH');
    };
    U.bd = function (j, n) {
        return j === 'SESUAI' ? '<span class="b ok">SESUAI</span>'
            : j === 'KURANG' ? '<span class="b bad">KURANG ' + n + '</span>'
            : j === 'LEBIH' ? '<span class="b warn">LEBIH ' + n + '</span>'
            : '<span class="b info">DI LUAR RESI</span>';
    };

    // ───── tanggal ─────
    U.pad = function (n) { return (n < 10 ? '0' : '') + n; };
    U.ymd = function (d) { return d.getFullYear() + '-' + U.pad(d.getMonth() + 1) + '-' + U.pad(d.getDate()); };
    U.hari = function (n) { var d = new Date(); d.setDate(d.getDate() + n); return U.ymd(d); };
    U.BLN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    U.BLNF = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    U.tglIndo = function (y) { var p = y.split('-'); return +p[2] + ' ' + U.BLNF[+p[1] - 1] + ' ' + p[0]; };
    U.tglPendek = function (y) { var p = y.split('-'); return +p[2] + ' ' + U.BLN[+p[1] - 1] + ' ' + p[0]; };

    // ───── foto SKU ─────
    // it: {sku|id_sku, foto, foto_lokal}. URL asli dulu, salinan lokal sebagai cadangan (assets/js/foto_sku.js).
    U.thumb = function (it, ukuran) {
        var sku = it.sku || it.id_sku || '';
        var src = it.foto || it.foto_lokal || '';
        var gaya = ukuran ? ' style="width:' + ukuran + 'px;height:' + ukuran + 'px"' : '';
        if (!src) {
            return '<span class="th pcfn"' + gaya + '>' + U.esc(String(sku).slice(0, 3)) + '</span>';
        }
        return '<img class="th" src="' + U.esc(src) + '"' + (it.foto && it.foto_lokal ? ' data-foto-lokal="' + U.esc(it.foto_lokal) + '"' : '') + gaya + ' alt="">';
    };
    U.foto = function (it) {
        var sku = it.sku || it.id_sku || '';
        return U.thumb(it) + '<button type="button" class="eye" title="Lihat foto" data-act="lihat" data-sku="' + U.esc(sku) +
            '" data-foto="' + U.esc(it.foto || '') + '" data-lokal="' + U.esc(it.foto_lokal || '') + '">&#128065;</button>';
    };
    U.lihat = function (el) {
        var foto = el.dataset.foto, lokal = el.dataset.lokal, src = foto || lokal;
        var d = document.createElement('div');
        d.className = 'pclb';
        d.onclick = function () { d.remove(); };
        d.innerHTML = (src
            ? '<img src="' + U.esc(src) + '"' + (foto && lokal ? ' data-foto-lokal="' + U.esc(lokal) + '"' : '') + ' alt="">'
            : '<span class="pcfn pcfn-besar">' + U.esc(el.dataset.sku) + '</span>')
            + '<b>' + U.esc(el.dataset.sku) + '</b><small>klik di mana saja untuk menutup</small>';
        U.root().appendChild(d);
    };

    // ───── popup ─────
    U.mdBuka = function (html, z, lebar) {
        var o = document.createElement('div');
        o.className = 'mdo';
        if (z) o.style.zIndex = z;
        o.setAttribute('tabindex', '-1');
        o.innerHTML = '<div class="mdb"' + (lebar ? ' style="max-width:' + lebar + 'px"' : '') + '>' + html + '</div>';
        U.root().appendChild(o);
        o.focus();
        return o;
    };
    U.mdTutup = function (o) { if (o && o.parentNode) o.parentNode.removeChild(o); };

    /**
     * Popup konfirmasi. ok(o) mengembalikan teks error (popup tetap terbuka), kosong (tutup),
     * atau Promise yang resolve dengan teks error / kosong.
     */
    U.konfirm = function (judul, isi, label, kelas, ok, lebar) {
        var o = U.mdBuka('<h3 style="margin:0 0 10px">' + judul + '</h3><div>' + isi + '</div><div id="pc_kferr"></div>' +
            '<div class="pr" style="justify-content:flex-end;margin-top:16px"><button type="button" class="a x" data-k="x">Batal</button>' +
            '<button type="button" class="a ' + kelas + '" data-k="ok">' + label + '</button></div>', 80, lebar || 440);
        var tampilErr = function (er) {
            o.querySelector('#pc_kferr').innerHTML = er ? '<div class="al bad" style="margin-bottom:0">' + er + '</div>' : '';
        };
        var sibuk = false;
        var jalankan = function () {
            if (sibuk) return;
            var r = ok(o);
            if (r && typeof r.then === 'function') {
                sibuk = true;
                o.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                r.then(function (er) {
                    sibuk = false;
                    o.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
                    if (er) tampilErr(er); else U.mdTutup(o);
                });
            } else if (r) {
                tampilErr(r);
            } else {
                U.mdTutup(o);
            }
        };
        o.addEventListener('click', function (e) {
            var k = e.target.dataset && e.target.dataset.k;
            if (k === 'x' || e.target === o) { if (!sibuk) U.mdTutup(o); }
            else if (k === 'ok') jalankan();
        });
        o.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.stopPropagation(); if (!sibuk) U.mdTutup(o); }
            else if (e.key === 'Enter' && kelas === 'g' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
                e.preventDefault(); jalankan();
            }
        });
        return o;
    };

    // ───── input scanner: isi lama dibersihkan sebelum scan berikutnya ─────
    U.autoBersih = function (el) {
        var siap = false;
        el.addEventListener('keydown', function (e) { if (e.key === 'Enter') siap = true; });
        el.addEventListener('beforeinput', function (e) {
            if (siap && e.inputType && e.inputType.indexOf('insert') === 0) { el.value = ''; siap = false; }
        });
        el.addEventListener('focus', function () { if (el.value) el.select(); });
        el.addEventListener('input', function () { if (!el.value) siap = false; });
    };

    // ───── cari SKU di luar resi: rekomendasi saat mengetik, tebak SKU dari teks ─────
    // opsi: {input, dd, qty, tambah} -- tambah() dipanggil saat Enter tanpa memilih saran.
    U.skuPasang = function (opsi) {
        var inp = opsi.input, dd = opsi.dd, qty = opsi.qty, sel = -1, timer = null, seq = 0;
        var tutup = function () { dd.className = 'dd hide'; };
        var pilih = function (sku) { inp.value = sku; tutup(); qty.focus(); qty.select(); };
        inp.addEventListener('input', function () {
            clearTimeout(timer);
            var q = inp.value.trim();
            if (q.length < 2) { tutup(); return; }
            timer = setTimeout(function () {
                var mine = ++seq;
                jQuery.getJSON(U.URL.sku_saran, { q: q }).done(function (res) {
                    if (mine !== seq || inp.value.trim() !== q) return;
                    var r = (res && res.data && res.data.results) || [];
                    sel = -1;
                    dd.innerHTML = r.map(function (k) {
                        return '<div data-s="' + U.esc(k.id_sku) + '">' + U.thumb(k, 28) + '<b>' + U.esc(k.id_sku) + '</b><small>Rak ' + U.esc(k.no_rak || '-') + '</small></div>';
                    }).join('');
                    dd.className = r.length ? 'dd' : 'dd hide';
                });
            }, 250);
        });
        inp.addEventListener('keydown', function (e) {
            var it = dd.children;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!it.length) return;
                sel = (sel + (e.key === 'ArrowDown' ? 1 : -1) + it.length) % it.length;
                for (var i = 0; i < it.length; i++) it[i].className = i === sel ? 'on' : '';
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (sel >= 0 && it[sel]) pilih(it[sel].dataset.s); else { tutup(); opsi.tambah(); }
            } else if (e.key === 'Escape') {
                tutup();
            }
        });
        dd.addEventListener('mousedown', function (e) {
            var d = e.target.closest('[data-s]');
            if (d) { e.preventDefault(); pilih(d.dataset.s); }
        });
        inp.addEventListener('blur', function () { setTimeout(tutup, 120); });
        qty.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); opsi.tambah(); inp.focus(); }
        });
    };

    // Tebak satu SKU dari teks (server: persis -> mengandung -> akhiran). Resolve {status, sku|kandidat}.
    U.cariSku = function (teks) {
        return U.post(U.URL.sku_detail, { sku: teks }).then(function (res) { return (res && res.data) || { status: 'tidak_ada' }; });
    };

    U.tg = function (el) { el.parentNode.querySelector('.infotxt').classList.toggle('hide'); };
    return U;
})();
</script>
