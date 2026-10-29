<link rel="stylesheet" href="assets/css/paket_cancel.css?v=<?= @filemtime(FCPATH . 'assets/css/paket_cancel.css') ?: '1' ?>">
<div class="pc" id="pc_root">
 <div class="pcd tb">
  <div class="tbl">
   <div class="drp"><button type="button" class="drb" data-act="dr-tog"><span>&#128197;</span><b id="pc_drl"></b><span class="car">&#9662;</span></button>
    <div id="pc_drp" class="drpop hide"><div class="drpre" id="pc_drpre"></div><div class="drcal"><div class="drnav"><button type="button" data-act="dr-nav" data-n="-1">&lsaquo;</button><b id="pc_drm"></b><button type="button" data-act="dr-nav" data-n="1">&rsaquo;</button></div><div class="drg" id="pc_drg"></div>
    <div class="drft"><small id="pc_drh"></small><span><button type="button" class="a x s" data-act="dr-tutup">Tutup</button> <button type="button" class="a s" data-act="dr-terapkan">Terapkan</button></span></div></div></div></div>
   <div class="msw" id="pc_ms_fd"></div>
   <input id="pc_qs" class="pcqs" placeholder="Cari resi, SKU, asal, catatan..." autocomplete="off">
  </div>
  <div class="tbr">
   <button type="button" id="pc_cardtg" class="eye ic" data-act="card-tog"></button>
   <span class="inf" title="Info" data-act="info">&#9432;</span>
   <button type="button" class="a w" id="pc_bsr" data-act="serah">&#128424; Serah Terima</button>
  </div>
  <div id="pc_infolap" class="infotxt hide"><b>Serah terima hanya satu per hari</b>: pilih satu tanggal paket diterima, lalu klik Serah Terima &mdash; boleh dicetak di hari berikutnya (mis. paket 28 Okt dicetak 29 Okt). Kertas dibuat langsung dari paket yang diterima pada tanggal itu; hanya barang yang sesuai resi yang dihitung, selisih tidak.</div>
 </div>
 <div id="pc_cards" class="cards"></div>
 <div id="pc_msg2"></div>
 <div id="pc_cetak" class="pcd hide"></div>
 <div class="pcd tc" id="pc_dspw">
  <div class="sect" style="padding:10px 14px 0"><b>Barang kembali ke display</b><small class="note" id="pc_dspn"></small></div>
  <div class="tw"><table><thead><tr><th>Waktu</th><th>No. Resi</th><th>SKU</th><th class="c">Qty</th><th>Butuh dari</th><th>Catatan</th><th>Oleh</th><th class="c" style="width:56px">Aksi</th></tr></thead><tbody id="pc_dsp"></tbody></table></div>
 </div>
 <div class="pcd tc">
  <div class="tw"><table><thead><tr><th style="width:36px"></th><th>No. Resi</th><th>Tanggal</th><th>Asal</th><th>Jam</th><th class="c">Qty Resi</th><th class="c">Qty Ada</th><th>Isi</th><th class="c" style="width:130px">Aksi</th></tr></thead><tbody id="pc_pk"></tbody></table></div>
  <div class="tf">
   <span id="pc_pgi" class="note"></span>
   <div class="pr"><label class="note">Tampilkan <select id="pc_ps"><option>10</option><option>20</option><option>50</option><option>100</option></select> baris</label><span id="pc_pgn" class="pr" style="gap:4px"></span></div>
  </div>
 </div>
</div>

<?php $this->load->view('paket_cancel/_umum'); ?>
<script type="text/javascript">
(function () {
    'use strict';
    var U = window.PCU, R = U.root(), g = U.g, esc = U.esc, NM = U.NM;
    var URL = {
        data: '<?= base_url('paket-cancel/laporan-data') ?>',
        edit: '<?= base_url('paket-cancel/edit-simpan') ?>',
        hapus: '<?= base_url('paket-cancel/hapus') ?>',
        tandai: '<?= base_url('paket-cancel/tandai-display') ?>',
        batalTandai: '<?= base_url('paket-cancel/batal-display') ?>',
        catat: '<?= base_url('paket-cancel/catat-display') ?>',
        serah: '<?= base_url('paket-cancel/serah-data') ?>',
        cetak: '<?= base_url('paket-cancel/serah-cetak') ?>'
    };
    var rows = [];          // semua paket diterima pada rentang tanggal terpilih
    var disp = [];          // catatan barang kembali ke display pada rentang tanggal
    var pg = 1, muat = 0;   // halaman aktif; nomor permintaan terakhir (abaikan balasan basi)
    var MS = { fd: [] };    // filter asal (kosong = semua)
    var edO = null;         // popup edit yang terbuka

    function pesan(tipe, html) { U.al('msg2', tipe, html); }
    var rg = { a: '', b: '' };
    function satuHariNilai() { return rg.a && rg.a === rg.b; }

    // ───── data ─────
    function setRange(a, b) {
        rg.a = a; rg.b = b;
        g('drl').textContent = labelRange(a, b);
        load();
    }
    function labelRange(a, b) {
        if (a === b) return a === U.hari(0) ? 'Hari ini (' + U.tglPendek(a) + ')' : a === U.hari(-1) ? 'Kemarin (' + U.tglPendek(a) + ')' : U.tglPendek(a);
        return U.tglPendek(a) + ' – ' + U.tglPendek(b);
    }
    function load() {
        var mine = ++muat;
        g('pk').innerHTML = '<tr><td colspan="9" class="c" style="color:var(--mut)">Memuat...</td></tr>';
        return U.post(URL.data, { start: rg.a, end: rg.b }).then(function (res) {
            if (mine !== muat) return;
            if (!res || !res.data || !res.data.ok) {
                rows = []; disp = [];
                pesan('bad', esc(res && res.message ? res.message : 'Gagal memuat data.'));
            } else {
                rows = res.data.rows || [];
                disp = res.data.display || [];
            }
            r2(1);
        }).catch(function (x) {
            if (mine !== muat) return;
            rows = []; disp = [];
            pesan('bad', esc(U.pesanGagal(x)));
            r2(1);
        });
    }

    // ───── filter, kartu, tabel, pagination ─────
    function filt() {
        var q = g('qs').value.trim().toLowerCase();
        return rows.filter(function (p) {
            if (MS.fd.length && MS.fd.indexOf(p.dari) < 0) return false;
            if (!q) return true;
            var h = (nr(p) + ' ' + p.dari + ' ' + (NM[p.dari] || '') + ' ' + (p.catatan || '') + ' ' + (p.toko || '') + ' ' + p.items.map(function (i) { return i.sku; }).join(' ')).toLowerCase();
            return h.indexOf(q) >= 0;
        });
    }
    function jml(p) { return p.items.reduce(function (a, i) { return a + i.qty_ditemukan; }, 0); }
    function nr(p) { return p.tanpa_resi ? 'TANPA RESI #' + p.id : p.noresi; }
    function jmlDisp(p) { return p.items.reduce(function (a, i) { return a + (i.qty_display || 0); }, 0); }
    function jmlResi(p) { return p.items.reduce(function (a, i) { return a + i.qty_resi; }, 0); }

    function r2(reset) {
        if (reset) pg = 1;
        var all = rows, tampil = filt(), per = +g('ps').value, tot = tampil.length, pages = Math.max(1, Math.ceil(tot / per));
        if (pg > pages) pg = pages;
        var c = function (f) { return all.filter(f).length; };
        g('cards').innerHTML = [['Diterima', all.length], ['Ada selisih', c(function (p) { return p.selisih; })], ['Di display', c(function (p) { return jmlDisp(p) > 0; })], ['Dari Packer', c(function (p) { return p.dari === 'PACKER'; })],
            ['Dari HO', c(function (p) { return p.dari === 'HO'; })], ['Dari Kurir', c(function (p) { return p.dari === 'KURIR'; })]]
            .map(function (x) { return '<div><b>' + x[1] + '</b><small>' + x[0] + '</small></div>'; }).join('');
        var bs = g('bsr');
        bs.disabled = !satuHariNilai();
        bs.title = satuHariNilai() ? 'Cetak serah terima ' + U.tglIndo(rg.a) : 'Pilih satu tanggal untuk mencetak serah terima (satu hari satu serah terima)';

        var st = (pg - 1) * per, ps = tampil.slice(st, st + per);
        g('pk').innerHTML = ps.map(function (p, i) {
            var det = p.items.map(function (it) {
                var j = U.jenis(it.qty_resi, it.qty_ditemukan);
                return '<div><b>' + esc(it.sku) + '</b> ' + it.qty_ditemukan + '/' + it.qty_resi + ' ' + U.bd(j, Math.abs(it.qty_ditemukan - it.qty_resi)) +
                    (it.qty_display ? ' <span class="b info">' + it.qty_display + ' di display</span>' : '') + '</div>';
            }).join('');
            return '<tr' + (p.selisih ? ' style="background:#d98e0418"' : '') + '><td><a href="#" data-act="buka" data-i="' + i + '">&#65291;</a></td><td><b>' + esc(nr(p)) + '</b>' +
                (p.diedit ? '<br><small class="note">diedit ' + esc(p.diedit) + '</small>' : '') + '</td><td>' + esc(p.tgl) + '</td><td>' + esc(NM[p.dari] || p.dari) + '</td><td>' + esc(p.jam) +
                '</td><td class="c">' + (p.tanpa_resi ? '-' : jmlResi(p)) + '</td><td class="c">' + jml(p) + '</td><td>' + (p.tanpa_resi ? '<span class="b gray">TANPA RESI</span>' : p.selisih ? '<span class="b bad">SELISIH</span>' : '<span class="b ok">SESUAI</span>') +
                (jmlDisp(p) ? ' <span class="b info" title="Barang sudah dikembalikan ke display">DISPLAY ' + jmlDisp(p) + ' pcs</span>' : '') +
                '</td><td class="c aksi"><button type="button" class="eye" title="Catat barang kembali ke display" aria-label="Catat barang kembali ke display" data-act="display" data-id="' + p.id + '">&#127978;</button>' +
                '<button type="button" class="eye" title="Edit" aria-label="Edit" data-act="edit" data-id="' + p.id + '">&#9998;</button>' +
                '<button type="button" class="eye del" title="Hapus" aria-label="Hapus" data-act="hapus" data-id="' + p.id + '">&#128465;</button></td></tr>' +
                '<tr id="pc_d' + i + '" class="hide"><td></td><td colspan="8">' + det + (p.catatan ? '<div style="color:var(--bad);margin-top:4px"><b>Catatan:</b> ' + esc(p.catatan) + '</div>' : '') +
                (p.petugas ? '<div class="note" style="margin:4px 0 0">Diterima oleh ' + esc(p.petugas) + (p.toko ? ' &middot; ' + esc(p.toko) : '') + '</div>' : '') + '</td></tr>';
        }).join('') || '<tr><td colspan="9" class="c" style="color:var(--mut)">Tidak ada data.</td></tr>';
        g('pgi').textContent = tot ? 'Menampilkan ' + (st + 1) + '-' + Math.min(st + per, tot) + ' dari ' + tot : 'Menampilkan 0 dari 0';
        var b = [], a = Math.max(1, pg - 2), z = Math.min(pages, pg + 2), k;
        b.push('<button type="button" class="a x s"' + (pg <= 1 ? ' disabled' : '') + ' data-act="hal" data-p="' + (pg - 1) + '">&laquo;</button>');
        for (k = a; k <= z; k++) b.push('<button type="button" class="a' + (k === pg ? '' : ' x') + ' s" data-act="hal" data-p="' + k + '">' + k + '</button>');
        b.push('<button type="button" class="a x s"' + (pg >= pages ? ' disabled' : '') + ' data-act="hal" data-p="' + (pg + 1) + '">&raquo;</button>');
        g('pgn').innerHTML = b.join('');
        rDisp();
    }

    function rDisp() {
        var q = g('qs').value.trim().toLowerCase();
        var l = disp.filter(function (d) { return !q || (d.noresi + ' ' + d.sku + ' ' + (d.sumber || '') + ' ' + (d.catatan || '') + ' ' + (d.petugas || '')).toLowerCase().indexOf(q) >= 0; });
        g('dspn').textContent = l.length ? l.length + ' catatan, ' + l.reduce(function (a, d) { return a + d.qty; }, 0) + ' pcs' : '';
        g('dsp').innerHTML = l.map(function (d) {
            return '<tr><td>' + esc(d.tgl) + ' ' + esc(d.jam) + '</td><td>' + (d.noresi ? '<b>' + esc(d.noresi) + '</b>' : '<span class="note">tanpa resi</span>') + (d.id_paket ? '' : ' <span class="b gray">DI LUAR PAKET</span>') +
                '</td><td><b>' + esc(d.sku) + '</b></td><td class="c">' + d.qty + '</td><td>' + (d.sumber ? '<span class="b warn">' + (d.sumber === 'QC' ? 'QC' : 'Restok') + '</span>' : '') +
                '</td><td>' + esc(d.catatan || '') + '</td><td>' + esc(d.petugas || '') + '</td><td class="c aksi"><button type="button" class="eye del" title="Batalkan catatan" aria-label="Batalkan catatan" data-act="display-batal" data-id="' + d.id + '">&#8617;</button></td></tr>';
        }).join('') || '<tr><td colspan="8" class="c" style="color:var(--mut)">Belum ada barang kembali ke display pada rentang ini.</td></tr>';
    }

    function cardSet(sembunyi) {
        g('cards').classList.toggle('hide', sembunyi);
        var b = g('cardtg');
        b.innerHTML = '&#128202;';
        b.title = sembunyi ? 'Tampilkan ringkasan' : 'Sembunyikan ringkasan';
        b.setAttribute('aria-label', b.title);
        b.style.background = sembunyi ? '' : 'var(--pri)';
        b.style.color = sembunyi ? '' : '#fff';
    }

    // ───── filter tanggal ─────
    var drA = null, drB = null, drM = null, drPhase = 2;
    function drPresets() {
        var t = new Date(), y = t.getFullYear(), m = t.getMonth();
        return [['Hari ini', U.hari(0), U.hari(0)], ['Kemarin', U.hari(-1), U.hari(-1)], ['7 hari terakhir', U.hari(-6), U.hari(0)], ['30 hari terakhir', U.hari(-29), U.hari(0)],
            ['Bulan ini', U.ymd(new Date(y, m, 1)), U.hari(0)], ['Bulan lalu', U.ymd(new Date(y, m - 1, 1)), U.ymd(new Date(y, m, 0))]];
    }
    function drPd(y) { var p = y.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function drClose() { g('drp').classList.add('hide'); }
    function msClose() { R.querySelectorAll('.mspop').forEach(function (p) { p.classList.add('hide'); }); }
    function drTog() {
        msClose();
        var o = g('drp');
        if (!o.classList.contains('hide')) { drClose(); return; }
        drA = rg.a || null; drB = rg.b || drA;
        var b = drA ? drPd(drA) : new Date();
        drM = new Date(b.getFullYear(), b.getMonth(), 1);
        drPhase = 2;
        o.classList.remove('hide');
        drRender();
    }
    function drRender() {
        var y = drM.getFullYear(), m = drM.getMonth(), h = U.hari(0), off = (new Date(y, m, 1).getDay() + 6) % 7, n = new Date(y, m + 1, 0).getDate(), gr = '', i;
        g('drm').textContent = U.BLNF[m] + ' ' + y;
        ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].forEach(function (x) { gr += '<span class="dw">' + x + '</span>'; });
        for (i = 0; i < off; i++) gr += '<span></span>';
        for (i = 1; i <= n; i++) {
            var d = U.ymd(new Date(y, m, i)), c = 'dd1', b = drB || drA;
            if (drA && d === drA) c += ' st';
            if (b && d === b) c += ' en';
            if (drA && b && d > drA && d < b) c += ' in';
            if (d === h) c += ' td';
            gr += '<button type="button" class="' + c + '" data-act="dr-hari" data-d="' + d + '">' + i + '</button>';
        }
        g('drg').innerHTML = gr;
        g('drh').textContent = drA ? (drB && drB !== drA ? U.tglPendek(drA) + ' – ' + U.tglPendek(drB) : U.tglPendek(drA) + (drPhase === 1 ? ' — pilih tanggal akhir' : '')) : 'Pilih tanggal';
    }

    // ───── filter asal (multi centang) ─────
    var OPSI = [['PACKER', 'Packer'], ['HO', 'HO'], ['KURIR', 'Kurir']];
    function msInit() {
        var h = '<button type="button" class="drb msb" data-act="ms-tog"><span class="msi">&#9776;</span><b></b><span class="car">&#9662;</span></button><div class="drpop mspop hide"><div class="msl">';
        OPSI.forEach(function (o) {
            h += '<label class="mso"><input type="checkbox" value="' + o[0] + '" data-act="ms-chg"><span class="msck"></span><span class="mst">' + o[1] + '</span></label>';
        });
        h += '</div><div class="msf"><button type="button" class="a x s" data-act="ms-reset">Reset</button><button type="button" class="a s" data-act="ms-tutup">Selesai</button></div></div>';
        g('ms_fd').innerHTML = h;
        msLabel();
    }
    function msLabel() {
        var v = MS.fd, t = v.length === 0 ? 'Semua asal' : v.length === 1 ? OPSI.filter(function (x) { return x[0] === v[0]; })[0][1] : 'Asal: ' + v.length + ' dipilih';
        var b = g('ms_fd').querySelector('.msb');
        b.querySelector('b').textContent = t;
        b.classList.toggle('msact', v.length > 0);
    }

    // ───── serah terima (satu per hari) ─────
    function serah() {
        if (!satuHariNilai()) {
            pesan('warn', 'Serah terima dibuat <b>satu per hari</b>. Pilih satu tanggal di filter tanggal, mis. tanggal paket diterima.');
            return;
        }
        var t = rg.a;
        g('msg2').innerHTML = '';
        U.post(URL.serah, { tanggal: t }).then(function (res) {
            var d = (res && res.data) || {};
            if (!d.ok) { pesan('bad', esc(res && res.message ? res.message : 'Gagal memuat serah terima.')); return; }
            if (!d.jumlah_paket) { pesan('warn', 'Tidak ada paket cancel diterima pada ' + U.tglIndo(t) + '.'); return; }
            pratinjau(t, d);
        }).catch(function (x) { pesan('bad', esc(U.pesanGagal(x))); });
    }
    function pratinjau(t, d) {
        var det = d.det || [];
        var baris = det.length ? det.map(function (x, k) {
            return '<tr><td class="f c">' + (k + 1) + '</td><td class="f c">' + esc(x.rak) + '</td><td class="f">' + esc(x.sku) + '</td><td class="f c">' + x.qty + '</td><td class="f"></td><td></td></tr>';
        }).join('') : '<tr><td colspan="6" style="text-align:center;color:#555">Tidak ada barang sesuai resi pada tanggal ini.</td></tr>';
        var nama = (d.petugas || []).map(function (n) { return '<b>' + esc(n) + '</b>'; }).join('<br>') || '&nbsp;';
        var c = g('cetak');
        c.className = 'pcd';
        c.innerHTML = '<div class="pr"><h2 class="grow">Pratinjau kertas serah terima</h2><button type="button" class="a w s" data-act="cetak" data-t="' + esc(t) + '">&#128424; Cetak</button><button type="button" class="a x s" data-act="cetak-tutup">Tutup</button></div>' +
            '<div class="paper"><div style="display:flex;justify-content:space-between;border-bottom:2px solid #111;padding-bottom:6px"><b style="font-size:16px">SERAH TERIMA PAKET CANCEL</b><b>' + U.tglIndo(t) + '</b></div>' +
            '<table><thead><tr><th class="f">No</th><th class="f">No. Rak</th><th class="f">SKU</th><th class="f">Qty</th><th class="f">Qty Real</th><th>Ket</th></tr></thead><tbody>' + baris + '</tbody></table>' +
            '<div class="ttd"><div>RETUR<div class="sp"></div>' + nama + '</div><div>RESTOK<div class="sp"></div>&nbsp;</div></div></div>';
        c.scrollIntoView();
    }

    // ───── edit ─────
    function cariPaket(id) { return rows.filter(function (p) { return p.id === +id; })[0]; }
    function edRow(i) {
        var ex = i.qty_resi === 0;
        return '<tr data-s="' + esc(i.sku) + '" data-r="' + i.qty_resi + '"><td><b>' + esc(i.sku) + '</b></td><td>' + U.foto(i) + '</td><td class="c">' + esc(i.no_rak) + '</td><td class="c">' + i.qty_resi +
            '</td><td><input class="q" type="number" min="0" value="' + i.qty_ditemukan + '"></td><td class="st"></td><td>' + (ex ? '<button type="button" class="a x s" data-act="ed-hapus-x">&times;</button>' : '') + '</td></tr>';
    }
    function edBuka(id) {
        var p = cariPaket(id);
        if (!p) return;
        var as = ['PACKER', 'HO', 'KURIR'].map(function (a) {
            return '<label class="asl-o"><input type="radio" name="edari" value="' + a + '"' + (p.dari === a ? ' checked' : '') + '><span class="asl-c"><span class="asl-i">' + U.IKON[a] + '</span><span><b>' + NM[a] + '</b></span></span></label>';
        }).join('');
        edO = U.mdBuka('<h3 style="margin:0 0 2px">Edit paket cancel</h3><div class="note" style="margin-bottom:12px">' + esc(nr(p)) + ' &middot; diterima ' + esc(p.tgl) + ' ' + esc(p.jam) + '</div>' +
            '<div class="stp"><b>Asal paket</b></div><div class="asl"><div class="asl-g">' + as + '</div></div>' +
            '<div class="stp" style="margin-top:16px"><b>Isi paket</b></div><div class="txw"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th><th style="width:44px"></th></tr></thead><tbody id="pc_edb">' + p.items.map(edRow).join('') + '</tbody></table></div>' +
            '<div class="addx"><div class="ac"><input id="pc_edsku" placeholder="Tambah SKU di luar resi" autocomplete="off"><div id="pc_eddd" class="dd hide"></div></div><input id="pc_edq" class="q" type="number" min="1" value="1"><button type="button" class="a" data-act="ed-tambah">+ Tambah</button></div><div id="pc_edmsg"></div>' +
            '<div id="pc_edban" style="margin-top:12px"></div><div class="sect" style="margin-top:8px"><b>Catatan</b><small class="note">wajib bila ada selisih</small></div><textarea id="pc_edcat" rows="2" maxlength="255">' + esc(p.catatan || '') + '</textarea>' +
            '<div class="pr" style="justify-content:flex-end;margin-top:16px"><button type="button" class="a x" data-act="ed-batal">Batal</button><button type="button" class="a g" data-act="ed-simpan" data-id="' + p.id + '">&#10003; Simpan perubahan</button></div>', 60, 640);
        U.skuPasang({ input: g('edsku'), dd: g('eddd'), qty: g('edq'), tambah: edTambah });
        edHit();
    }
    function edHit() {
        var k = 0, l = 0, x = 0;
        g('edb').querySelectorAll('tr').forEach(function (tr) {
            var r = +tr.dataset.r, v = +tr.querySelector('input').value || 0, d = v - r;
            tr.querySelector('.st').innerHTML = U.bd(U.jenis(r, v), Math.abs(d));
            if (r === 0) x += v; else if (d < 0) k -= d; else l += d;
        });
        var s = k || l || x;
        g('edban').innerHTML = s ? '<div class="al warn" style="margin:0"><b>ADA SELISIH:</b> ' + [k && 'kurang ' + k, l && 'lebih ' + l, x && 'di luar resi ' + x].filter(Boolean).join(', ') + ' pcs</div>' : '<div class="al ok" style="margin:0"><b>ISI SESUAI RESI</b></div>';
        return !!s;
    }
    function edTambah() {
        var teks = g('edsku').value.trim(), q = +g('edq').value;
        g('edmsg').innerHTML = '';
        if (!teks) { U.al('edmsg', 'bad', 'Isi SKU dulu.'); return; }
        if (!(q >= 1)) { U.al('edmsg', 'bad', 'Qty minimal 1.'); return; }
        U.cariSku(teks).then(function (d) {
            if (d.status === 'ambigu') { U.al('edmsg', 'warn', 'Beberapa SKU cocok: ' + d.kandidat.map(function (k) { return esc(k.id_sku); }).join(', ') + '. Ketik lebih lengkap.'); return; }
            if (d.status !== 'ok') { U.al('edmsg', 'bad', 'SKU <b>' + esc(teks) + '</b> tidak ditemukan.'); return; }
            var kode = d.sku.id_sku, ada = null;
            g('edb').querySelectorAll('tr').forEach(function (tr) { if (tr.dataset.s.toUpperCase() === kode.toUpperCase()) ada = tr; });
            if (ada) {
                if (+ada.dataset.r > 0) { U.al('edmsg', 'warn', 'SKU <b>' + esc(kode) + '</b> ada di resi: ubah Qty ada di tabel.'); return; }
                var i = ada.querySelector('input');
                i.value = (+i.value || 0) + q;
            } else {
                g('edb').insertAdjacentHTML('beforeend', edRow({ sku: kode, no_rak: d.sku.no_rak, qty_resi: 0, qty_ditemukan: q, foto: d.sku.foto, foto_lokal: d.sku.foto_lokal }));
            }
            g('edsku').value = ''; g('edq').value = 1;
            edHit();
        }).catch(function (x) { U.al('edmsg', 'bad', esc(U.pesanGagal(x))); });
    }
    function edSimpan(id) {
        var p = cariPaket(id), m = edO;
        if (!p || !m) return;
        var rd = m.querySelector('[name=edari]:checked'), dari = rd ? rd.value : null;
        g('edmsg').innerHTML = '';
        if (!dari) { U.al('edmsg', 'bad', 'Pilih asal paket.'); return; }
        var it = [];
        m.querySelectorAll('#pc_edb tr').forEach(function (tr) { it.push({ s: tr.dataset.s, r: +tr.dataset.r, a: +tr.querySelector('input').value || 0 }); });
        var sel = edHit(), cat = g('edcat').value.trim();
        if (sel && !cat) { U.al('edmsg', 'bad', 'Ada selisih: isi catatan penyebabnya.'); g('edcat').focus(); return; }
        var ch = [];
        if (dari !== p.dari) ch.push('Asal: <b>' + NM[p.dari] + '</b> &rarr; <b>' + NM[dari] + '</b>');
        var lama = {}, baru = {};
        p.items.forEach(function (i) { lama[i.sku.toUpperCase()] = i; });
        it.forEach(function (i) {
            baru[i.s.toUpperCase()] = i;
            var o = lama[i.s.toUpperCase()];
            if (!o) ch.push('+ <b>' + esc(i.s) + '</b>: ' + i.a + ' pcs (di luar resi)');
            else if (o.qty_ditemukan !== i.a) ch.push('<b>' + esc(i.s) + '</b>: ' + o.qty_ditemukan + ' &rarr; ' + i.a + ' pcs');
        });
        p.items.forEach(function (o) { if (!baru[o.sku.toUpperCase()]) ch.push('- <b>' + esc(o.sku) + '</b> dihapus dari paket'); });
        if (cat !== (p.catatan || '')) ch.push('Catatan diubah');
        if (!ch.length) { U.al('edmsg', 'warn', 'Tidak ada perubahan untuk disimpan.'); return; }
        U.konfirm('Simpan perubahan?', 'Perubahan pada paket <b>' + esc(nr(p)) + '</b>:<ul style="margin:8px 0 0;padding-left:20px">' + ch.map(function (c) { return '<li>' + c + '</li>'; }).join('') + '</ul>', 'Ya, simpan', 'g', function () {
            return U.post(URL.edit, { id: p.id, dari: dari, catatan: cat, items: JSON.stringify(it.map(function (i) { return { sku: i.s, qty: i.a }; })) }).then(function (res) {
                if (!(res && res.data && res.data.ok)) return esc(res && res.message ? res.message : 'Gagal menyimpan perubahan');
                U.mdTutup(edO);
                pesan('ok', 'Paket <b>' + esc(nr(p)) + '</b> berhasil diperbarui.');
                load();
                return null;
            }).catch(function (x) { return esc(U.pesanGagal(x)); });
        });
    }

    // ───── hapus ─────
    function hapusBuka(id) {
        var p = cariPaket(id);
        if (!p) return;
        U.konfirm('Hapus paket cancel?', 'Paket <b>' + esc(nr(p)) + '</b> (' + esc(NM[p.dari]) + ', ' + esc(p.tgl) + ' ' + esc(p.jam) + ', ' + jml(p) + ' pcs) akan dihapus dari laporan dan tidak ikut kertas serah terima. Resi ini bisa diterima ulang.' +
            '<textarea id="pc_hpalasan" rows="2" maxlength="255" placeholder="Alasan penghapusan (wajib)" style="margin-top:10px"></textarea>', 'Ya, hapus', 'r', function (o) {
            var a = o.querySelector('#pc_hpalasan').value.trim();
            if (!a) return 'Isi alasan penghapusan.';
            return U.post(URL.hapus, { id: p.id, alasan: a }).then(function (res) {
                if (!(res && res.data && res.data.ok)) return esc(res && res.message ? res.message : 'Gagal menghapus');
                pesan('ok', 'Paket <b>' + esc(nr(p)) + '</b> dihapus.');
                load();
                return null;
            }).catch(function (x) { return esc(U.pesanGagal(x)); });
        });
    }

    // ───── barang kembali ke display ─────
    function sumberRadio() {
        return [['', 'Tidak ada'], ['QC', 'Dari QC'], ['RESTOK', 'Dari Restok']].map(function (o, k) {
            return '<label style="display:inline-flex;align-items:center;gap:5px;margin-right:14px;cursor:pointer"><input type="radio" name="pc_dsumber" value="' + o[0] + '"' + (k === 0 ? ' checked' : '') + '>' + o[1] + '</label>';
        }).join('');
    }
    function sumberCat() {
        return '<div style="margin-top:12px"><b>Barang pengganti dibutuhkan dari</b><div style="margin-top:6px">' + sumberRadio() + '</div></div>' +
            '<textarea id="pc_dcat" rows="2" maxlength="255" placeholder="Catatan (opsional), mis. dipakai packing, tunggu barang dari QC" style="margin-top:10px"></textarea>';
    }
    function sumberNilai(o) { var rd = o.querySelector('[name=pc_dsumber]:checked'); return rd ? rd.value : ''; }

    function displayBuka(id) {
        var p = cariPaket(id);
        if (!p) return;
        var baris = p.items.filter(function (i) { return i.qty_ditemukan > 0; }).map(function (i) {
            var sisa = i.qty_ditemukan - (i.qty_display || 0);
            return '<tr data-s="' + esc(i.sku) + '" data-m="' + sisa + '"><td><b>' + esc(i.sku) + '</b></td><td>' + U.foto(i) + '</td><td class="c">' + i.qty_ditemukan + '</td><td class="c">' + (i.qty_display || 0) + '</td><td>' +
                (sisa > 0 ? '<input class="q" type="number" min="0" max="' + sisa + '" value="0"> <small class="note">maks ' + sisa + '</small>' : '<small class="note">sudah semua</small>') + '</td></tr>';
        }).join('');
        if (!baris) { pesan('warn', 'Paket ini tidak punya barang untuk dikembalikan ke display.'); return; }
        U.konfirm('Barang kembali ke display',
            'Paket <b>' + esc(nr(p)) + '</b>: isi qty barang yang sudah dikembalikan ke display (0 untuk yang tidak). Qty ini tidak ikut kertas serah terima ke restock.' +
            '<div class="txw" style="margin-top:10px"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Qty ada</th><th class="c">Di display</th><th>Kembali</th></tr></thead><tbody>' + baris + '</tbody></table></div>' + sumberCat(),
            'Simpan', 'g', function (o) {
                var it = [], lebih = null;
                o.querySelectorAll('tbody tr').forEach(function (tr) {
                    var inp = tr.querySelector('input'), v = inp ? +inp.value || 0 : 0;
                    if (v > +tr.dataset.m && !lebih) lebih = tr;
                    if (v > 0) it.push({ sku: tr.dataset.s, qty: v });
                });
                if (!it.length) return 'Isi qty minimal satu SKU.';
                if (lebih) return 'Qty ' + esc(lebih.dataset.s) + ' melebihi sisa (' + lebih.dataset.m + ').';
                return U.post(URL.tandai, { id: p.id, items: JSON.stringify(it), sumber: sumberNilai(o), catatan: o.querySelector('#pc_dcat').value.trim() }).then(function (res) {
                    if (!(res && res.data && res.data.ok)) return esc(res && res.message ? res.message : 'Gagal menyimpan');
                    pesan('ok', esc(res.message));
                    load();
                    return null;
                }).catch(function (x) { return esc(U.pesanGagal(x)); });
            });
    }

    function displayBatal(id) {
        var d = disp.filter(function (x) { return x.id === +id; })[0];
        if (!d) return;
        U.konfirm('Batalkan catatan display?', 'Catatan <b>' + esc(d.sku) + '</b> x' + d.qty + (d.noresi ? ' (resi ' + esc(d.noresi) + ')' : '') + ' dibatalkan.' + (d.id_paket ? ' Qty-nya kembali dihitung di serah terima ke restock.' : ''), 'Ya, batalkan', 'r', function () {
            return U.post(URL.batalTandai, { id: d.id }).then(function (res) {
                if (!(res && res.data && res.data.ok)) return esc(res && res.message ? res.message : 'Gagal membatalkan');
                pesan('ok', 'Catatan display dibatalkan.');
                load();
                return null;
            }).catch(function (x) { return esc(U.pesanGagal(x)); });
        });
    }

    // ───── event ─────
    R.addEventListener('click', function (e) {
        var t = e.target.closest('[data-act]');
        if (!t) return;
        var a = t.dataset.act;
        if (t.tagName === 'A') e.preventDefault();
        switch (a) {
            case 'lihat': U.lihat(t); break;
            case 'buka': var d = g('d' + t.dataset.i); d.classList.toggle('hide'); t.textContent = d.classList.contains('hide') ? '＋' : '−'; break;
            case 'hal': pg = +t.dataset.p; r2(0); break;
            case 'edit': edBuka(t.dataset.id); break;
            case 'hapus': hapusBuka(t.dataset.id); break;
            case 'display': displayBuka(t.dataset.id); break;
            case 'display-batal': displayBatal(t.dataset.id); break;
            case 'serah': serah(); break;
            case 'cetak': window.open(URL.cetak + '?tanggal=' + encodeURIComponent(t.dataset.t), '_blank'); break;
            case 'cetak-tutup': g('cetak').className = 'pcd hide'; break;
            case 'info': g('infolap').classList.toggle('hide'); break;
            case 'card-tog': cardSet(!g('cards').classList.contains('hide')); break;
            case 'dr-tog': drTog(); break;
            case 'dr-tutup': drClose(); break;
            case 'dr-nav': drM = new Date(drM.getFullYear(), drM.getMonth() + (+t.dataset.n), 1); drRender(); break;
            case 'dr-hari':
                var dd = t.dataset.d;
                if (!drA || drPhase === 2) { drA = dd; drB = null; drPhase = 1; }
                else { if (dd < drA) { drB = drA; drA = dd; } else drB = dd; drPhase = 2; }
                drRender();
                break;
            case 'dr-terapkan': if (drA) { setRange(drA, drB || drA); drClose(); } break;
            case 'dr-pre': var pr = drPresets()[+t.dataset.i]; setRange(pr[1], pr[2]); drClose(); break;
            case 'ms-tog':
                var pop = t.parentNode.querySelector('.mspop'), was = pop.classList.contains('hide');
                msClose(); drClose();
                if (was) pop.classList.remove('hide');
                break;
            case 'ms-reset': MS.fd = []; t.closest('.mspop').querySelectorAll('input').forEach(function (i) { i.checked = false; }); msLabel(); r2(1); break;
            case 'ms-tutup': msClose(); break;
            case 'tg': U.tg(t); break;
            case 'ed-tambah': edTambah(); break;
            case 'ed-hapus-x': t.closest('tr').remove(); edHit(); break;
            case 'ed-batal': U.mdTutup(edO); break;
            case 'ed-simpan': edSimpan(t.dataset.id); break;
        }
    });
    R.addEventListener('change', function (e) {
        if (e.target.dataset && e.target.dataset.act === 'ms-chg') {
            var v = [];
            e.target.closest('.msl').querySelectorAll('input:checked').forEach(function (i) { v.push(i.value); });
            MS.fd = v; msLabel(); r2(1);
        } else if (e.target.id === 'pc_ps') {
            r2(1);
        }
    });
    R.addEventListener('input', function (e) {
        if (e.target.id === 'pc_qs') r2(1);
        else if (e.target.matches('#pc_edb input')) edHit();
    });
    // klik di luar popover filter -> tutup (satu listener, dilepas bila halaman sudah diganti)
    if (window.__pcLapDoc) document.removeEventListener('click', window.__pcLapDoc);
    window.__pcLapDoc = function (e) {
        if (!R.isConnected || !e.target.isConnected) return;
        if (!e.target.closest('.drp')) drClose();
        if (!e.target.closest('.msw')) msClose();
    };
    document.addEventListener('click', window.__pcLapDoc);

    U.autoBersih(g('qs'));
    g('drpre').innerHTML = drPresets().map(function (p, i) { return '<button type="button" data-act="dr-pre" data-i="' + i + '">' + p[0] + '</button>'; }).join('');
    msInit();
    cardSet(true);
    setRange(U.hari(0), U.hari(0));
    g('qs').focus();
})();
</script>
