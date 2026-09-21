/**
 * Cadangan lokal foto produk -- pasangan helper PHP foto_sku (application/helpers).
 *
 * Setiap <img src="URL asli" data-foto-lokal="/foto-produk/x.jpg"> dicoba dari
 * URL aslinya dulu. Kalau gagal (internet putus, DNS tidak menjawab) ATAU belum
 * selesai dalam BATAS_MS (internet "setengah mati": DNS jalan tapi paket tidak
 * sampai, browser bisa menunggu 20-30 detik sebelum menyerah), src diganti ke
 * salinan lokal yang dilayani Apache dari C:/foto-produk. Berlaku juga untuk
 * <img> yang dibuat belakangan oleh AJAX/DataTables dan untuk <img> modal
 * pratinjau yang src-nya diganti-ganti (diamati lewat MutationObserver).
 *
 * Tidak ada yang perlu dipanggil dari view: cukup beri atribut data-foto-lokal.
 */
(function () {
    'use strict';

    var BATAS_MS = 4000;

    function pakaiLokal(img) {
        var lokal = img.getAttribute('data-foto-lokal');
        if (!lokal || img.getAttribute('src') === lokal) {
            return;
        }
        img.setAttribute('data-foto-asli', img.getAttribute('src') || '');
        img.src = lokal;
    }

    function jaga(img) {
        var lokal = img.getAttribute('data-foto-lokal');
        if (!lokal || img.getAttribute('src') === lokal) {
            return;
        }
        if (img.__fotoSkuTimer) {
            clearTimeout(img.__fotoSkuTimer);
        }
        // Sudah gagal sebelum kita sempat memasang listener (mis. HTML dari
        // DataTables yang langsung dirender saat offline).
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) {
            pakaiLokal(img);
            return;
        }
        img.__fotoSkuTimer = setTimeout(function () {
            if (!img.complete || img.naturalWidth === 0) {
                pakaiLokal(img);
            }
        }, BATAS_MS);
    }

    // error/load tidak menggelembung, tapi bisa ditangkap di fase capture.
    document.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.getAttribute('data-foto-lokal')) {
            if (t.__fotoSkuTimer) {
                clearTimeout(t.__fotoSkuTimer);
            }
            pakaiLokal(t);
        }
    }, true);

    document.addEventListener('load', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.__fotoSkuTimer) {
            clearTimeout(t.__fotoSkuTimer);
            t.__fotoSkuTimer = null;
        }
    }, true);

    function pindai(akar) {
        if (!akar || akar.nodeType !== 1) {
            return;
        }
        if (akar.tagName === 'IMG') {
            if (akar.getAttribute('data-foto-lokal')) {
                jaga(akar);
            }
            return;
        }
        var daftar = akar.querySelectorAll ? akar.querySelectorAll('img[data-foto-lokal]') : [];
        for (var i = 0; i < daftar.length; i++) {
            jaga(daftar[i]);
        }
    }

    var pengamat = new MutationObserver(function (mutasi) {
        for (var i = 0; i < mutasi.length; i++) {
            var m = mutasi[i];
            if (m.type === 'attributes') {
                // src atau data-foto-lokal diganti (modal pratinjau dipakai ulang)
                if (m.target.tagName === 'IMG') {
                    jaga(m.target);
                }
                continue;
            }
            for (var j = 0; j < m.addedNodes.length; j++) {
                pindai(m.addedNodes[j]);
            }
        }
    });

    function mulai() {
        pengamat.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['src', 'data-foto-lokal']
        });
        pindai(document.body);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai);
    } else {
        mulai();
    }
})();
