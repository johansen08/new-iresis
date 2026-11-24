/**
 * Foto produk: SALINAN LOKAL DULU, URL asli hanya cadangan -- pasangan helper PHP
 * foto_sku (application/helpers).
 *
 * Aplikasi berjalan penuh di jaringan lokal (tanpa internet), jadi setiap
 * <img src="URL asli" data-foto-lokal="/foto-produk/x.jpg"> langsung dialihkan ke
 * salinan lokal yang dilayani Apache dari C:/foto-produk. URL asli disimpan di
 * atribut data-foto-asli dan baru dipakai bila berkas lokal gagal dimuat.
 * Berlaku juga untuk <img> yang dibuat belakangan oleh AJAX/DataTables dan untuk
 * <img> modal pratinjau yang src-nya diganti-ganti (diamati lewat MutationObserver).
 *
 * Tidak ada yang perlu dipanggil dari view: cukup beri atribut data-foto-lokal.
 */
(function () {
    'use strict';

    // Alihkan ke salinan lokal (URL asli dititipkan di data-foto-asli).
    function jaga(img) {
        var lokal = img.getAttribute('data-foto-lokal');
        var src = img.getAttribute('src') || '';
        if (!lokal || src === lokal) {
            return;
        }
        // src ini baru saja kita kembalikan ke URL asli karena lokal gagal: biarkan.
        if (img.__fotoCadangan && img.__fotoCadangan === src) {
            return;
        }
        img.__fotoCadangan = null;
        img.setAttribute('data-foto-asli', src);
        img.src = lokal;
    }

    // Berkas lokal gagal dimuat -> coba URL asli (sekali saja per src).
    function pakaiAsli(img) {
        var asli = img.getAttribute('data-foto-asli');
        var src = img.getAttribute('src') || '';
        if (!asli || src === asli) {
            return;
        }
        img.__fotoCadangan = asli;
        img.src = asli;
    }

    // error tidak menggelembung, tapi bisa ditangkap di fase capture.
    document.addEventListener('error', function (e) {
        var t = e.target;
        if (t && t.tagName === 'IMG' && t.getAttribute('data-foto-lokal')) {
            pakaiAsli(t);
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
                if (m.target.tagName === 'IMG' && m.target.getAttribute('data-foto-lokal')) {
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
