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

    // ───── pemeriksa isi paket (dipakai Terima Paket Cancel & popup Edit di Laporan) ─────
    // Qty ada per SKU resi; bila kurang dari qty resi petugas WAJIB memilih Kurang atau Salah:
    //   Kurang = qty kurang langsung dihitung (qty resi - qty ada).
    //   Salah  = isi SKU yang terambil dan qty-nya (boleh beberapa SKU); sisa selisih otomatis Kurang.
    // Label tampil hanya KURANG / SALAH / LEBIH (SKU di luar resi tampil LEBIH; di data tetap DI_LUAR_RESI).
    U.NMJ = { SESUAI: 'SESUAI', KURANG: 'KURANG', LEBIH: 'LEBIH', SALAH_AMBIL: 'SALAH', DI_LUAR_RESI: 'LEBIH' };
    U.CLSJ = { SESUAI: 'ok', KURANG: 'bad', LEBIH: 'info', SALAH_AMBIL: 'warn', DI_LUAR_RESI: 'info' };
    U.tag = function (j, n) { return '<span class="b ' + U.CLSJ[j] + '">' + U.NMJ[j] + (n ? ' ' + n : '') + '</span>'; };

    /**
     * cfg: {root, isi (tbody SKU resi), luar (tbody SKU di luar resi), luarWrap (pembungkus tabel luar), ban (kartu ringkasan), onChange()}
     * Mengembalikan pengendali dengan muat(), render(), tambahLuar(), payload(), belumPutus(), tutupEditor(), grup().
     */
    U.periksa = function (cfg) {
        var K = { b: [], l: [], tanpa: false }, esc = U.esc;
        var selisih = function (b) { return Math.max(0, b.qr - b.ada); };
        var sumSalah = function (b) { return b.salah.reduce(function (n, x) { return n + x.qty; }, 0); };
        var sisa = function (b) { return selisih(b) - sumSalah(b); };
        var putus = function (b) { return selisih(b) === 0 || b.pilih === 'KURANG' || b.salah.length > 0; };
        var up = function (s) { return String(s).toUpperCase(); };

        // Qty ada berubah: alokasi salah ambil tidak boleh melebihi selisih; selisih 0 = pilihan dibuang
        function rapikan(b) {
            var S = selisih(b);
            if (S === 0) { b.pilih = null; b.salah = []; b.edit = false; return; }
            while (sumSalah(b) > S && b.salah.length) {
                var l = b.salah[b.salah.length - 1], lebih = sumSalah(b) - S;
                if (l.qty > lebih) l.qty -= lebih; else b.salah.pop();
            }
        }

        /**
         * isi: [{sku, no_rak, qty, ada?, foto, foto_lokal}] (SKU resi); luar: [{sku, no_rak, ada, foto, foto_lokal}];
         * salah: [{sku (SKU resi), pasangan: {sku, no_rak, foto, foto_lokal}, qty}]; kurang: [SKU yang dipilih Kurang]
         */
        K.muat = function (isi, luar, salah, kurang) {
            K.b = (isi || []).map(function (i) {
                return { sku: i.sku, it: i, qr: +i.qty, ada: i.ada === undefined ? +i.qty : +i.ada, pilih: null, salah: [], edit: false };
            });
            K.l = (luar || []).map(function (x) { return { sku: x.sku, it: x, ada: +x.ada }; });
            (salah || []).forEach(function (s) {
                var b = K.b.filter(function (x) { return up(x.sku) === up(s.sku); })[0];
                if (!b || selisih(b) < 1) return;
                b.salah.push({ sku: s.pasangan.sku, it: s.pasangan, qty: +s.qty });
                b.pilih = 'SALAH';
            });
            (kurang || []).forEach(function (sku) {
                K.b.forEach(function (b) { if (up(b.sku) === up(sku) && selisih(b) > 0 && !b.salah.length) b.pilih = 'KURANG'; });
            });
            K.b.forEach(rapikan);
            K.render();
        };

        // Kejadian selisih saat ini (sama dengan Cancel_terima_fcd::klasifikasi_eksplisit())
        K.grup = function () {
            var g = [];
            K.b.forEach(function (b) {
                var S = selisih(b);
                b.salah.forEach(function (x) { g.push({ j: 'SALAH_AMBIL', s: b.sku, p: x.sku, q: x.qty }); });
                var sq = S - sumSalah(b);
                if (S > 0 && sq > 0 && (b.pilih === 'KURANG' || b.salah.length)) g.push({ j: 'KURANG', s: b.sku, p: '', q: sq });
                if (b.ada > b.qr) g.push({ j: 'LEBIH', s: b.sku, p: '', q: b.ada - b.qr });
            });
            K.l.forEach(function (x) { if (x.ada > 0) g.push({ j: 'DI_LUAR_RESI', s: x.sku, p: '', q: x.ada }); });
            return g;
        };
        K.belumPutus = function () { return K.b.filter(function (b) { return !putus(b); }); };
        K.payload = function () {
            var items = [], salah = [];
            K.b.forEach(function (b) {
                items.push({ sku: b.sku, qty: b.ada });
                b.salah.forEach(function (s) { items.push({ sku: s.sku, qty: s.qty }); salah.push({ sku: b.sku, pasangan: s.sku, qty: s.qty }); });
            });
            K.l.forEach(function (x) { if (x.ada > 0) items.push({ sku: x.sku, qty: x.ada }); });
            return { items: items, salah: salah };
        };
        K.adaSelisih = function () { return K.grup().length > 0 || K.belumPutus().length > 0; };
        K.tutupEditor = function () {
            var ada = false;
            K.b.forEach(function (b) { if (b.edit) { ada = true; b.edit = false; if (!b.salah.length) b.pilih = null; } });
            if (ada) K.render();
            return ada;
        };

        function statusHtml(b, i) {
            var S = selisih(b), h = [];
            if (b.ada > b.qr) return U.tag('LEBIH', b.ada - b.qr);
            if (S === 0) return U.tag('SESUAI');
            if (!putus(b)) {
                return '<div class="pilih"><div class="pilih-t">Selisih <b>' + S + '</b></div><div class="pilih-b">' +
                    '<button type="button" class="a x s" data-k="pilihkurang" data-i="' + i + '">Kurang ' + S + '</button>' +
                    '<button type="button" class="a x s" data-k="pilihsalah" data-i="' + i + '">Salah</button></div></div>';
            }
            b.salah.forEach(function (s, j) {
                h.push('<div class="sa">' + U.tag('SALAH_AMBIL') + '<div class="sa-r">' + U.foto(Object.assign({}, s.it, { sku: s.sku })) + '<b>' + esc(s.sku) + '</b>' + (s.it.no_rak ? '<small>rak ' + esc(s.it.no_rak) + '</small>' : '') + '</div>' +
                    '<div class="stp3"><button type="button" data-k="smin" data-i="' + i + '" data-j="' + j + '" aria-label="Kurangi qty ' + esc(s.sku) + '">&minus;</button><span class="n">' + s.qty + '</span>' +
                    '<button type="button" data-k="splus" data-i="' + i + '" data-j="' + j + '" aria-label="Tambah qty ' + esc(s.sku) + '">+</button><small>pcs</small>' +
                    '<button type="button" class="a x s" data-k="shapus" data-i="' + i + '" data-j="' + j + '">Hapus</button></div></div>');
            });
            var sq = S - sumSalah(b);
            if (sq > 0) h.push('<div class="sa">' + U.tag('KURANG', sq) + '</div>');
            var aksi = [];
            if (sq > 0 && b.salah.length) aksi.push('<button type="button" class="a x s" data-k="pilihsalah" data-i="' + i + '">+ Salah</button>');
            aksi.push('<button type="button" class="a x s" data-k="ubahpilih" data-i="' + i + '">Ubah</button>');
            h.push('<div class="sa-btn">' + aksi.join(' ') + '</div>');
            return h.join('');
        }
        function editorHtml(b, i) {
            var q = Math.max(1, sisa(b));
            return '<tr class="edrow"><td colspan="6"><div class="ed">' +
                '<div><b>Salah: SKU apa yang terambil menggantikan ' + esc(b.sku) + '?</b> <small class="note">maks ' + sisa(b) + ' pcs</small></div>' +
                '<div class="ed-f"><div class="ac"><input type="text" class="esku" placeholder="Scan / ketik SKU yang terambil" autocomplete="off" spellcheck="false"><div class="dd hide"></div></div>' +
                '<span class="stp3"><button type="button" data-k="eqmin" aria-label="Kurangi">&minus;</button><input class="q eq" type="number" min="1" value="' + q + '" aria-label="Qty terambil"><button type="button" data-k="eqplus" aria-label="Tambah">+</button></span>' +
                '<button type="button" class="a s" data-k="etambah" data-i="' + i + '">Simpan</button><button type="button" class="a x s" data-k="etutup">Tutup</button></div>' +
                '<div class="emsg"></div></div></td></tr>';
        }
        function stepper(k, i, v, nol) {
            return '<span class="stp3"><button type="button" data-k="' + k + 'min" data-i="' + i + '" aria-label="Kurangi">&minus;</button><input class="q" type="number" min="0" data-k="' + k + 'val" data-i="' + i + '" value="' + v + '"><button type="button" data-k="' + k + 'plus" data-i="' + i + '" aria-label="Tambah">+</button>' +
                (nol ? '<button type="button" class="nol" data-k="nol" data-i="' + i + '" title="Barang ini tidak ada di paket">0</button>' : '') + '</span>';
        }

        K.render = function () {
            cfg.isi.innerHTML = K.b.map(function (b, i) {
                return '<tr data-s="' + esc(b.sku) + '"' + (putus(b) ? '' : ' class="perlu"') + '><td><b>' + esc(b.sku) + '</b></td><td>' + U.foto(Object.assign({}, b.it, { sku: b.sku })) + '</td><td class="c">' + esc(b.it.no_rak || '') +
                    '</td><td class="c">' + b.qr + '</td><td class="c">' + stepper('', i, b.ada, true) + '</td><td class="st">' + statusHtml(b, i) + '</td></tr>' + (b.edit ? editorHtml(b, i) : '');
            }).join('') || '<tr><td colspan="6" class="c" style="color:var(--mut)">Resi tidak punya detail SKU</td></tr>';
            cfg.luar.innerHTML = K.l.map(function (x, i) {
                return '<tr data-s="' + esc(x.sku) + '"><td><b>' + esc(x.sku) + '</b></td><td>' + U.foto(Object.assign({}, x.it, { sku: x.sku })) + '</td><td class="c">' + esc(x.it.no_rak || '') +
                    '</td><td class="c">' + stepper('l', i, x.ada, false) + '</td><td class="st">' + (K.tanpa ? '' : U.tag('DI_LUAR_RESI', x.ada)) + '</td><td><button type="button" class="a x s" data-k="lhapus" data-i="' + i + '">&times;</button></td></tr>';
            }).join('');
            cfg.luarWrap.className = K.l.length ? 'txw' : 'txw hide';
            var ed = K.b.filter(function (b) { return b.edit; })[0];
            if (ed) {
                var inp = cfg.isi.querySelector('.esku'), dd = cfg.isi.querySelector('.dd'), qv = cfg.isi.querySelector('.eq');
                U.skuPasang({ input: inp, dd: dd, qty: qv, tambah: function () { simpanEditor(K.b.indexOf(ed)); } });
                inp.focus();
            }
            ringkasan();
            if (cfg.onChange) cfg.onChange(K);
        };
        function ringkasan() {
            var tot = { KURANG: 0, LEBIH: 0, SALAH_AMBIL: 0, DI_LUAR_RESI: 0 }, sesuai = 0;
            K.grup().forEach(function (g) { tot[g.j] += g.q; });
            K.b.forEach(function (b) { if (b.ada === b.qr) sesuai++; });
            if (K.tanpa) { cfg.ban.innerHTML = ''; return; }
            var kartu = [['o', 'Sesuai', sesuai], ['k', 'Kurang', tot.KURANG], ['l', 'Lebih', tot.LEBIH + tot.DI_LUAR_RESI], ['s', 'Salah', tot.SALAH_AMBIL], ['w', 'Belum dipilih', K.belumPutus().length]];
            cfg.ban.innerHTML = '<div class="sum">' + kartu.map(function (k) { return '<div class="' + k[0] + (k[2] > 0 ? ' on' : '') + '"><b>' + k[2] + '</b><small>' + k[1] + '</small></div>'; }).join('') + '</div>';
        }
        function pesanEd(tipe, html) { var e = cfg.isi.querySelector('.emsg'); if (e) e.innerHTML = '<div class="al ' + tipe + '" style="margin:6px 0 0">' + html + '</div>'; }

        function simpanEditor(i) {
            var b = K.b[i];
            if (!b) return;
            var inp = cfg.isi.querySelector('.esku'), qv = cfg.isi.querySelector('.eq');
            if (!inp) return;
            var teks = inp.value.trim(), q = Math.max(0, parseInt(qv.value, 10) || 0), s = sisa(b);
            if (!teks) { pesanEd('bad', 'Isi SKU yang terambil.'); inp.focus(); return; }
            if (q < 1) { pesanEd('bad', 'Qty minimal 1.'); return; }
            if (q > s) { pesanEd('bad', 'Qty melebihi sisa selisih (' + s + ' pcs). Turunkan Qty ada dulu bila memang lebih banyak.'); return; }
            U.cariSku(teks).then(function (d) {
                if (!b.edit) return;
                if (d.status === 'ambigu') { pesanEd('warn', 'Beberapa SKU cocok: ' + d.kandidat.map(function (k) { return esc(k.id_sku); }).join(', ') + '. Ketik lebih lengkap.'); return; }
                if (d.status !== 'ok') { pesanEd('bad', 'SKU <b>' + esc(teks) + '</b> tidak ditemukan.'); return; }
                var kode = d.sku.id_sku;
                if (K.b.some(function (x) { return up(x.sku) === up(kode); })) { pesanEd('warn', 'SKU <b>' + esc(kode) + '</b> ada di resi ini. Naikkan Qty ada-nya (terbaca LEBIH), lalu pilih Kurang di sini.'); return; }
                if (q > sisa(b)) { pesanEd('bad', 'Qty melebihi sisa selisih.'); return; }
                var x = b.salah.filter(function (y) { return up(y.sku) === up(kode); })[0];
                if (x) x.qty += q; else b.salah.push({ sku: kode, it: d.sku, qty: q });
                b.pilih = 'SALAH'; b.edit = false;
                K.render();
            }).catch(function (x) { pesanEd('bad', esc(U.pesanGagal(x))); });
        }

        // Tambah SKU di luar resi. Mengembalikan null bila berhasil, atau {tipe, pesan}.
        K.tambahLuar = function (sku, qty) {
            if (K.b.some(function (x) { return up(x.sku) === up(sku.id_sku); })) return { tipe: 'warn', pesan: 'SKU <b>' + esc(sku.id_sku) + '</b> ada di resi: ubah Qty ada di tabel atas.' };
            var x = K.l.filter(function (y) { return up(y.sku) === up(sku.id_sku); })[0];
            if (x) x.ada += qty; else K.l.push({ sku: sku.id_sku, it: { no_rak: sku.no_rak, foto: sku.foto, foto_lokal: sku.foto_lokal }, ada: qty });
            K.render();
            return null;
        };

        function setAda(b, v) { b.ada = Math.max(0, parseInt(v, 10) || 0); rapikan(b); K.render(); }
        cfg.root.addEventListener('click', function (e) {
            var t = e.target.closest('[data-k]');
            if (!t || !(cfg.isi.contains(t) || cfg.luar.contains(t))) return;
            var a = t.dataset.k, i = +t.dataset.i, j = +t.dataset.j, b = K.b[i];
            if (a === 'min' || a === 'plus') setAda(b, b.ada + (a === 'plus' ? 1 : -1));
            else if (a === 'nol') setAda(b, 0);
            else if (a === 'lmin' || a === 'lplus') { K.l[i].ada = Math.max(0, K.l[i].ada + (a === 'lplus' ? 1 : -1)); K.render(); }
            else if (a === 'lhapus') { K.l.splice(i, 1); K.render(); }
            else if (a === 'pilihkurang') { b.pilih = 'KURANG'; b.salah = []; b.edit = false; K.render(); }
            else if (a === 'pilihsalah') { K.b.forEach(function (x) { x.edit = false; }); b.pilih = 'SALAH'; b.edit = true; K.render(); }
            else if (a === 'ubahpilih') { b.pilih = null; b.salah = []; b.edit = false; K.render(); }
            else if (a === 'etutup') { K.tutupEditor(); }
            else if (a === 'etambah') simpanEditor(i);
            else if (a === 'eqmin' || a === 'eqplus') { var q = cfg.isi.querySelector('.eq'); q.value = Math.max(1, (parseInt(q.value, 10) || 1) + (a === 'eqplus' ? 1 : -1)); }
            else if (a === 'smin') { b.salah[j].qty -= 1; if (b.salah[j].qty < 1) b.salah.splice(j, 1); K.render(); }
            else if (a === 'splus') { if (sisa(b) > 0) { b.salah[j].qty += 1; K.render(); } else pesanEd('warn', 'Sudah sebesar selisih.'); }
            else if (a === 'shapus') { b.salah.splice(j, 1); K.render(); }
        });
        cfg.root.addEventListener('change', function (e) {
            var t = e.target, a = t.dataset && t.dataset.k;
            if (!a || !(cfg.isi.contains(t) || cfg.luar.contains(t))) return;
            if (a === 'val') setAda(K.b[+t.dataset.i], t.value);
            else if (a === 'lval') { K.l[+t.dataset.i].ada = Math.max(0, parseInt(t.value, 10) || 0); K.render(); }
        });
        cfg.root.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var t = e.target;
            if (t.dataset && (t.dataset.k === 'val' || t.dataset.k === 'lval') && (cfg.isi.contains(t) || cfg.luar.contains(t))) { e.preventDefault(); t.blur(); }
        });
        return K;
    };

    U.tg = function (el) { el.parentNode.querySelector('.infotxt').classList.toggle('hide'); };
    return U;
})();
</script>
