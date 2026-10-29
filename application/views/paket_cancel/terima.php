<link rel="stylesheet" href="assets/css/paket_cancel.css?v=<?= @filemtime(FCPATH . 'assets/css/paket_cancel.css') ?: '1' ?>">
<div class="pc" id="pc_root">
<div class="pgrid">
 <div class="pcd">
  <div class="stp"><span class="stn">1</span><b>Asal paket</b><span class="req">*</span><small id="pc_asl_e" class="hide">pilih salah satu</small></div>
  <div id="pc_asal" class="asl"><div class="asl-g">
   <label class="asl-o"><input type="radio" name="dari" value="PACKER"><span class="asl-c"><span class="asl-i">&#128230;</span><span><b>Packer</b></span></span></label>
   <label class="asl-o"><input type="radio" name="dari" value="HO"><span class="asl-c"><span class="asl-i">&#127970;</span><span><b>HO</b></span></span></label>
   <label class="asl-o"><input type="radio" name="dari" value="KURIR"><span class="asl-c"><span class="asl-i">&#128666;</span><span><b>Kurir</b></span></span></label></div></div>
  <div class="stp" style="margin-top:16px"><span class="stn">2</span><b>Scan no. resi</b></div>
  <input id="pc_resi" class="scan" placeholder="Scan atau ketik no. resi, lalu Enter" autocomplete="off">
  <div class="tanpa" style="margin-top:10px"><button type="button" class="a x s" data-act="tanpa">Zip / paket tanpa kertas resi</button> <small class="note">barang ada, kertas resi tidak ada</small></div>
  <div id="pc_msg"></div>
 </div>

 <div id="pc_form" class="pcd hide">
  <div class="fh">
   <div><div class="fk" id="pc_fk">No. Resi</div><h3 id="pc_fres"></h3></div>
   <span class="b gray" id="pc_ftoko"></span>
  </div>

  <div class="sec" id="pc_secisi">
   <div class="sect"><b>Isi resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">Isi <b>Qty ada</b> sesuai barang yang benar-benar ada. Salah ambil: nolkan SKU resi yang tidak ada, lalu tambahkan SKU yang ada di bagian bawah.</div></div>
   <div class="txw"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th></tr></thead><tbody id="pc_isi"></tbody></table></div>
  </div>

  <div class="sec">
   <div class="sect"><b id="pc_xjudul">Barang di luar resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">SKU / qty yang ada di paket tetapi tidak tertulis di resi.</div></div>
   <div id="pc_xt" class="txw hide"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty ada</th><th style="width:50px"></th></tr></thead><tbody id="pc_ex"></tbody></table></div>
   <div class="addx"><div class="ac"><input id="pc_xsku" placeholder="Ketik SKU, rekomendasi muncul..." autocomplete="off"><div id="pc_dd" class="dd hide"></div></div><input id="pc_xq" class="q" type="number" min="1" value="1"><button type="button" class="a" data-act="tambah-x">+ Tambah</button></div>
   <div id="pc_xmsg"></div>
  </div>

  <div class="sec">
   <div id="pc_ban"></div>
   <div class="sect"><b>Catatan</b><small class="note" id="pc_catn">wajib bila ada selisih</small></div>
   <textarea id="pc_cat" rows="2" maxlength="255" placeholder="mis. picker salah ambil ZU-2, qty kurang 1"></textarea>
  </div>

  <div class="fa"><button type="button" class="a x" data-act="batal">Batal</button><button type="button" class="a g" data-act="simpan">&#10003; Simpan Penerimaan</button></div>
 </div>
</div>
</div>

<?php $this->load->view('paket_cancel/_umum'); ?>
<script type="text/javascript">
(function () {
    'use strict';
    var U = window.PCU, R = U.root(), g = U.g, esc = U.esc;
    var URL = {
        cek: '<?= base_url('paket-cancel/cek-resi') ?>',
        simpan: '<?= base_url('paket-cancel/simpan') ?>',
        simpanTanpa: '<?= base_url('paket-cancel/simpan-tanpa-resi') ?>'
    };
    var cur = null;      // resi yang sedang diisi: {noresi, toko}
    var sibuk = false;   // ada request scan yang belum selesai

    function asalDipilih() { var c = R.querySelector('[name=dari]:checked'); return c ? c.value : null; }
    function asalPilih() {
        g('asal').classList.remove('err');
        g('asl_e').classList.add('hide');
        if (g('msg').innerText.indexOf('Pilih asal paket') >= 0) g('msg').innerHTML = '';
    }
    function asalWajib() {
        if (asalDipilih()) return true;
        g('asal').classList.add('err');
        g('asl_e').classList.remove('hide');
        U.al('msg', 'bad', 'Pilih <b>asal paket</b> (Packer, HO, atau Kurir) dulu. Wajib dipilih untuk setiap input.');
        return false;
    }
    function asalReset() { R.querySelectorAll('[name=dari]').forEach(function (r) { r.checked = false; }); }

    function clr() {
        g('resi').value = '';
        g('form').className = 'pcd hide';
        cur = null;
        modeTanpa(false);
    }
    function modeTanpa(on) {
        g('secisi').classList.toggle('hide', on);
        g('fk').textContent = on ? 'Paket' : 'No. Resi';
        g('xjudul').textContent = on ? 'Barang di paket' : 'Barang di luar resi';
        g('catn').textContent = on ? 'wajib: ciri zip / keranjang asal' : 'wajib bila ada selisih';
    }

    // ───── paket tanpa kertas resi ─────
    function tanpaResi() {
        if (!asalWajib()) return;
        g('msg').innerHTML = '';
        clr();
        modeTanpa(true);
        cur = { tanpa: true, noresi: '', toko: '' };
        g('fres').textContent = 'TANPA RESI';
        g('ftoko').textContent = '-';
        g('isi').innerHTML = '';
        g('ex').innerHTML = '';
        g('xt').className = 'txw hide';
        g('xmsg').innerHTML = '';
        g('cat').value = '';
        g('form').className = 'pcd';
        hit();
        g('xsku').focus();
    }

    // ───── scan resi ─────
    function scan() {
        var no = g('resi').value.trim();
        if (!no || sibuk) return;
        if (!asalWajib()) return;
        g('msg').innerHTML = '';
        sibuk = true;
        U.post(URL.cek, { noresi: no }).then(function (res) {
            var d = (res && res.data) || {};
            if (d.status === 'ok') { tampilForm(d); return; }
            if (d.status === 'tidak_ada') U.al('msg', 'bad', 'Resi <b>' + esc(no) + '</b> tidak ditemukan.');
            else if (d.status === 'bukan_cancel') U.al('msg', 'bad', 'Resi <b>' + esc(no) + '</b> bukan pesanan CANCEL (status: ' + esc(d.status_pesanan) + '). Ini barang balikan retur biasa &mdash; proses lewat Scan Retur, jangan dicampur.');
            else if (d.status === 'sudah_diterima') U.al('msg', 'warn', 'Resi <b>' + esc(no) + '</b> sudah diterima tim retur (' + esc(d.sudah.dicek_at) + ', dari ' + esc(U.NM[d.sudah.dari] || d.sudah.dari) + (d.sudah.petugas ? ', oleh ' + esc(d.sudah.petugas) : '') + ').');
            else U.al('msg', 'bad', esc(res && res.message ? res.message : 'Balasan server tidak dikenal.'));
            clr();
        }).catch(function (x) {
            U.al('msg', 'bad', esc(U.pesanGagal(x)));
        }).then(function () { sibuk = false; });
    }

    function tampilForm(d) {
        modeTanpa(false);
        cur = { noresi: d.noresi, toko: d.toko || '' };
        g('fres').textContent = d.noresi;
        g('ftoko').textContent = d.toko || '-';
        g('isi').innerHTML = (d.isi || []).map(function (x) {
            var q = parseInt(x.qty, 10);
            return '<tr data-s="' + esc(x.sku) + '" data-q="' + q + '"><td><b>' + esc(x.sku) + '</b></td><td>' + U.foto(x) + '</td><td class="c">' + esc(x.no_rak) + '</td><td class="c">' + q +
                '</td><td><input class="q" type="number" min="0" value="' + q + '"></td><td class="st"></td></tr>';
        }).join('') || '<tr><td colspan="6" class="c" style="color:var(--mut)">Resi tidak punya detail SKU</td></tr>';
        g('ex').innerHTML = '';
        g('xt').className = 'txw hide';
        g('xmsg').innerHTML = '';
        g('cat').value = '';
        g('form').className = 'pcd';
        hit();
        var p = g('isi').querySelector('input');
        if (p) { p.focus(); p.select(); }
    }

    // Hitung ulang status baris + banner selisih. Mengembalikan true bila ada selisih.
    function hit() {
        var k = 0, l = 0, x = 0;
        g('isi').querySelectorAll('tr[data-s]').forEach(function (tr) {
            var q = +tr.dataset.q, v = +tr.querySelector('input').value || 0, d = v - q;
            tr.querySelector('.st').innerHTML = U.bd(d === 0 ? 'SESUAI' : d < 0 ? 'KURANG' : 'LEBIH', Math.abs(d));
            if (d < 0) k -= d; else l += d;
        });
        g('ex').querySelectorAll('input').forEach(function (i) { x += +i.value || 0; });
        var s = k || l || x;
        if (cur && cur.tanpa) {
            g('ban').innerHTML = '<div class="al ok"><b>PAKET TANPA RESI:</b> ' + x + ' pcs, ikut serah terima ke restock</div>';
            return false;
        }
        g('ban').innerHTML = s
            ? '<div class="al warn"><b>ADA SELISIH:</b> ' + [k && 'kurang ' + k, l && 'lebih ' + l, x && 'di luar resi ' + x].filter(Boolean).join(', ') + ' pcs</div>'
            : '<div class="al ok"><b>ISI SESUAI RESI</b></div>';
        return !!s;
    }

    // ───── barang di luar resi ─────
    function tambahBaris(sku) {
        var qty = +g('xq').value;
        if (qty < 1) { U.al('xmsg', 'bad', 'Qty minimal 1'); return; }
        var kode = sku.id_sku;
        var diResi = false;
        g('isi').querySelectorAll('tr[data-s]').forEach(function (tr) { if (tr.dataset.s.toUpperCase() === kode.toUpperCase()) diResi = true; });
        if (diResi) { U.al('xmsg', 'warn', 'SKU <b>' + esc(kode) + '</b> ada di resi: ubah Qty ada di tabel atas.'); return; }
        var ada = null;
        g('ex').querySelectorAll('tr').forEach(function (tr) { if (tr.dataset.s.toUpperCase() === kode.toUpperCase()) ada = tr; });
        if (ada) {
            var i = ada.querySelector('input');
            i.value = (+i.value || 0) + qty;
        } else {
            g('ex').insertAdjacentHTML('beforeend', '<tr data-s="' + esc(kode) + '"><td><b>' + esc(kode) + '</b></td><td>' + U.foto(sku) + '</td><td class="c">' + esc(sku.no_rak) +
                '</td><td class="c"><input class="q" type="number" min="1" value="' + qty + '"></td><td><button type="button" class="a x s" data-act="hapus-x">&times;</button></td></tr>');
        }
        g('xt').className = 'txw';
        g('xsku').value = '';
        g('xq').value = 1;
        g('xmsg').innerHTML = '';
        hit();
    }
    function tambahX() {
        var teks = g('xsku').value.trim();
        g('xmsg').innerHTML = '';
        if (!teks) { U.al('xmsg', 'bad', 'Isi SKU dulu.'); return; }
        if (!(+g('xq').value >= 1)) { U.al('xmsg', 'bad', 'Qty minimal 1'); return; }
        U.cariSku(teks).then(function (d) {
            if (d.status === 'ok') tambahBaris(d.sku);
            else if (d.status === 'ambigu') U.al('xmsg', 'warn', 'Beberapa SKU cocok: ' + d.kandidat.map(function (k) { return esc(k.id_sku); }).join(', ') + '. Ketik kodenya lebih lengkap atau pilih dari rekomendasi.');
            else U.al('xmsg', 'bad', 'SKU <b>' + esc(teks) + '</b> tidak ditemukan.');
        }).catch(function (x) { U.al('xmsg', 'bad', esc(U.pesanGagal(x))); });
    }

    // ───── simpan: pratinjau + konfirmasi ─────
    function simpan() {
        if (!cur) return;
        if (!asalWajib()) return;
        var s = hit();
        if (cur.tanpa) { simpanTanpa(); return; }
        if (s && !g('cat').value.trim()) {
            U.al('msg', 'bad', 'Ada selisih: isi catatan penyebabnya.');
            g('cat').focus();
            return;
        }
        var it = [];
        g('isi').querySelectorAll('tr[data-s]').forEach(function (tr) { it.push({ s: tr.dataset.s, r: +tr.dataset.q, a: +tr.querySelector('input').value || 0 }); });
        g('ex').querySelectorAll('tr').forEach(function (tr) { it.push({ s: tr.dataset.s, r: 0, a: +tr.querySelector('input').value || 0 }); });
        var dari = asalDipilih(), cat = g('cat').value.trim(), resi = cur, tot = 0;
        var rows = it.map(function (i) {
            tot += i.a;
            return '<tr><td><b>' + esc(i.s) + '</b></td><td class="c">' + i.r + '</td><td class="c">' + i.a + '</td><td>' + U.bd(U.jenis(i.r, i.a), Math.abs(i.a - i.r)) + '</td></tr>';
        }).join('');
        var isi = '<div class="pv"><div><span>No. Resi</span><b>' + esc(resi.noresi) + '</b></div><div><span>Toko</span><b>' + esc(resi.toko) + '</b></div><div><span>Asal</span><b>' + U.NM[dari] + '</b></div><div><span>Total ada</span><b>' + tot + ' pcs</b></div></div>' +
            '<div class="txw"><table class="tx"><thead><tr><th>SKU</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th></tr></thead><tbody>' + rows + '</tbody></table></div>' +
            '<div style="margin-top:10px">' + g('ban').innerHTML + '</div>' + (cat ? '<div class="pvc"><b>Catatan:</b> ' + esc(cat) + '</div>' : '');
        U.konfirm('Simpan penerimaan?', isi, 'Ya, simpan', 'g', function () {
            return U.post(URL.simpan, {
                noresi: resi.noresi, dari: dari, catatan: cat,
                items: JSON.stringify(it.map(function (i) { return { sku: i.s, qty: i.a }; }))
            }).then(function (res) {
                var d = (res && res.data) || {};
                if (!d.ok) return esc(res && res.message ? res.message : 'Gagal menyimpan');
                clr();
                asalReset();
                U.al('msg', d.selisih ? 'warn' : 'ok', 'Resi <b>' + esc(resi.noresi) + '</b> tersimpan' + (d.selisih ? ', ADA SELISIH tercatat.' : ', isi sesuai resi.'));
                g('resi').focus();
                return null;
            }).catch(function (x) { return esc(U.pesanGagal(x)); });
        }, 520);
    }

    function simpanTanpa() {
        var it = [], tot = 0, cat = g('cat').value.trim(), dari = asalDipilih();
        g('ex').querySelectorAll('tr').forEach(function (tr) { var q = +tr.querySelector('input').value || 0; if (q > 0) { it.push({ s: tr.dataset.s, a: q }); tot += q; } });
        if (!it.length) { U.al('msg', 'bad', 'Tambahkan minimal satu SKU dengan qty.'); g('xsku').focus(); return; }
        if (!cat) { U.al('msg', 'bad', 'Isi catatan: ciri zip / keranjang asal paket tanpa resi.'); g('cat').focus(); return; }
        g('msg').innerHTML = '';
        var rows = it.map(function (i) { return '<tr><td><b>' + esc(i.s) + '</b></td><td class="c">' + i.a + '</td></tr>'; }).join('');
        var isi = '<div class="pv"><div><span>No. Resi</span><b>TANPA RESI</b></div><div><span>Asal</span><b>' + U.NM[dari] + '</b></div><div><span>Total ada</span><b>' + tot + ' pcs</b></div></div>' +
            '<div class="txw"><table class="tx"><thead><tr><th>SKU</th><th class="c">Qty ada</th></tr></thead><tbody>' + rows + '</tbody></table></div><div class="pvc"><b>Catatan:</b> ' + esc(cat) + '</div>';
        U.konfirm('Simpan paket tanpa resi?', isi, 'Ya, simpan', 'g', function () {
            return U.post(URL.simpanTanpa, { dari: dari, catatan: cat, items: JSON.stringify(it.map(function (i) { return { sku: i.s, qty: i.a }; })) }).then(function (res) {
                var d = (res && res.data) || {};
                if (!d.ok) return esc(res && res.message ? res.message : 'Gagal menyimpan');
                clr();
                asalReset();
                U.al('msg', 'ok', 'Paket tanpa resi tersimpan (' + tot + ' pcs).');
                g('resi').focus();
                return null;
            }).catch(function (x) { return esc(U.pesanGagal(x)); });
        }, 520);
    }

    // ───── event ─────
    R.addEventListener('click', function (e) {
        var t = e.target.closest('[data-act]');
        if (!t) return;
        var a = t.dataset.act;
        if (a === 'lihat') U.lihat(t);
        else if (a === 'tg') U.tg(t);
        else if (a === 'tambah-x') tambahX();
        else if (a === 'hapus-x') { t.closest('tr').remove(); if (!g('ex').children.length) g('xt').className = 'txw hide'; hit(); }
        else if (a === 'simpan') simpan();
        else if (a === 'tanpa') tanpaResi();
        else if (a === 'batal') { g('msg').innerHTML = ''; clr(); }
    });
    R.addEventListener('input', function (e) {
        if (e.target.matches('#pc_isi input, #pc_ex input')) hit();
    });
    R.addEventListener('change', function (e) {
        if (e.target.name === 'dari') { asalPilih(); g('resi').focus(); }
    });
    g('resi').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); scan(); } });
    U.autoBersih(g('resi'));
    U.skuPasang({ input: g('xsku'), dd: g('dd'), qty: g('xq'), tambah: tambahX });
    g('resi').focus();
})();
</script>
