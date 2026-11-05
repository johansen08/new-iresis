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
  <button type="button" class="btn-tanpa" data-act="tanpa">&#128196; Tidak Ada Resi</button>
  <label class="kunci"><input type="checkbox" id="pc_kunci" checked><span>Kunci kursor di kolom scan resi</span></label>
  <div id="pc_msg"></div>
 </div>

 <div id="pc_form" class="pcd hide">
  <div class="fh">
   <div><div class="fk" id="pc_fk">No. Resi</div><h3 id="pc_fres"></h3></div>
   <span class="b gray" id="pc_ftoko"></span>
  </div>

  <div class="sec" id="pc_secisi">
   <div class="sect"><b>Isi resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">Isi <b>Qty ada</b> sesuai barang yang benar-benar ada (tombol &minus; / + atau ketik). Tombol <b>0</b> = barang itu tidak ada di paket. Bila qty ada kurang dari qty resi, pilih <b>Kurang</b> (qty kurang dihitung otomatis) atau <b>Salah</b> (isi SKU yang terambil dan qty-nya; sisa selisih otomatis Kurang). Barang yang tidak tertulis di resi dan bukan salah ambil masuk <b>Barang di luar resi</b>.</div></div>
   <div id="pc_ban"></div>
   <div class="txw"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th></tr></thead><tbody id="pc_isi"></tbody></table></div>
  </div>

  <div class="sec">
   <div class="sect"><b id="pc_xjudul">Barang di luar resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">SKU / qty yang ada di paket tetapi tidak tertulis di resi. Scan SKU: tiap scan menambah 1 (tidak perlu mengetik qty). Salah ambil jangan dimasukkan di sini: pilih <b>Salah</b> di baris SKU resi yang kurang.</div></div>
   <div id="pc_xt" class="txw hide"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty ada</th><th>Status</th><th style="width:50px"></th></tr></thead><tbody id="pc_ex"></tbody></table></div>
   <div class="addx"><div class="ac"><input id="pc_xsku" placeholder="Ketik SKU, rekomendasi muncul..." autocomplete="off"><div id="pc_dd" class="dd hide"></div></div><input id="pc_xq" class="q" type="number" min="1" value="1"><button type="button" class="a" data-act="tambah-x">+ Tambah</button></div>
   <div id="pc_xmsg"></div>
  </div>

  <div class="sec">
   <div class="sect"><b>Catatan</b><small class="note" id="pc_catn">opsional</small></div>
   <textarea id="pc_cat" rows="2" maxlength="255" placeholder="mis. picker baru, belum hafal rak"></textarea>
  </div>

  <div class="fa"><button type="button" class="a x" data-act="batal">Batal</button><button type="button" class="a g" data-act="simpan">&#10003; Simpan sekarang (atau scan resi ini lagi)</button></div>
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
    var cur = null;      // resi yang sedang diisi: {noresi, toko} (atau {tanpa: true})
    var sibuk = false;   // ada request scan yang belum selesai

    // Isi paket (qty ada, pilihan Kurang / Salah, barang di luar resi): modul bersama di _umum.php
    var K = U.periksa({
        root: R, isi: g('isi'), luar: g('ex'), luarWrap: g('xt'), ban: g('ban'),
        onChange: function () {
            if (cur && cur.tanpa) {
                var x = 0;
                K.l.forEach(function (i) { x += i.ada; });
                g('ban').innerHTML = '<div class="al ok"><b>PAKET TANPA RESI:</b> ' + x + ' pcs, ikut serah terima ke restock</div>';
            }
        }
    });

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
        K.muat([], []);
    }
    function modeTanpa(on) {
        K.tanpa = on;
        g('secisi').classList.toggle('hide', on);
        g('fk').textContent = on ? 'Paket' : 'No. Resi';
        g('xjudul').textContent = on ? 'Barang di paket' : 'Barang di luar resi';
        g('catn').textContent = on ? 'wajib: ciri zip / keranjang asal' : 'opsional';
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
        g('xmsg').innerHTML = '';
        g('cat').value = '';
        g('form').className = 'pcd';
        K.muat([], []);
        g('xsku').focus();
    }

    // ───── scan resi ─────
    function scan() {
        var no = g('resi').value.trim();
        if (!no || sibuk) return;
        if (!asalWajib()) return;
        // Scan kedua: resi yang sama dengan yang sedang diperiksa -> simpan langsung
        if (cur && !cur.tanpa) {
            if (no.toUpperCase() === String(cur.noresi).toUpperCase()) { simpanLangsung(); return; }
            U.al('msg', 'warn', 'Resi <b>' + esc(cur.noresi) + '</b> masih diperiksa. Scan resi itu sekali lagi untuk menyimpan, atau tekan Batal dulu.');
            g('resi').value = cur.noresi;
            g('resi').select();
            return;
        }
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
        K.muat((d.isi || []).map(function (x) {
            return { sku: x.sku, no_rak: x.no_rak, qty: parseInt(x.qty, 10), foto: x.foto, foto_lokal: x.foto_lokal };
        }), []);
        g('xmsg').innerHTML = '';
        g('cat').value = '';
        g('form').className = 'pcd';
        // Fokus tetap di kolom resi: periksa isi (ubah qty bila perlu), lalu scan resi yang sama sekali lagi untuk menyimpan.
        U.al('msg', 'ok', 'Periksa isi paket. Bila qty ada kurang dari qty resi, pilih <b>Kurang</b> atau <b>Salah</b>. Setelah semua beres, <b>scan resi ini sekali lagi</b> untuk menyimpan.');
        g('resi').focus();
        g('resi').select();
    }

    // ───── barang di luar resi ─────
    function tambahBaris(sku) {
        var qty = +g('xq').value;
        if (qty < 1) { U.al('xmsg', 'bad', 'Qty minimal 1'); return; }
        var gagal = K.tambahLuar(sku, qty);
        if (gagal) { U.al('xmsg', gagal.tipe, gagal.pesan); return; }
        g('xsku').value = '';
        g('xq').value = 1;
        g('xmsg').innerHTML = '';
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

    // ───── simpan langsung (scan resi kedua / tombol Simpan), tanpa pop up ─────
    function simpanLangsung() {
        if (!cur) return;
        if (!asalWajib()) return;
        var bp = K.belumPutus();
        if (bp.length) {
            K.render();
            U.al('msg', 'bad', 'Pilih <b>Kurang</b> atau <b>Salah</b> untuk ' + bp.map(function (b) { return '<b>' + esc(b.sku) + '</b>'; }).join(', ') + ' (qty ada kurang dari qty resi), lalu scan resi ini lagi.');
            var pr = g('isi').querySelector('tr.perlu');
            if (pr) pr.scrollIntoView({ block: 'center' });
            return;
        }
        var pay = K.payload();
        var dari = asalDipilih(), cat = g('cat').value.trim(), resi = cur;
        if (sibuk) return;
        sibuk = true;
        U.post(URL.simpan, {
            noresi: resi.noresi, dari: dari, catatan: cat,
            items: JSON.stringify(pay.items), salah: JSON.stringify(pay.salah)
        }).then(function (res) {
            var d = (res && res.data) || {};
            if (!d.ok) { U.al('msg', 'bad', esc(res && res.message ? res.message : 'Gagal menyimpan')); g('resi').select(); return; }
            clr();
            asalReset();
            U.al('msg', d.selisih ? 'warn' : 'ok', 'Resi <b>' + esc(resi.noresi) + '</b> tersimpan' + (d.selisih ? ', ADA SELISIH tercatat.' : ', isi sesuai resi.'));
            g('resi').focus();
        }).catch(function (x) {
            U.al('msg', 'bad', esc(U.pesanGagal(x)));
        }).then(function () { sibuk = false; });
    }

    function simpanTanpa() {
        var it = [], tot = 0, cat = g('cat').value.trim(), dari = asalDipilih();
        K.l.forEach(function (x) { if (x.ada > 0) { it.push({ s: x.sku, a: x.ada }); tot += x.ada; } });
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
        else if (a === 'simpan') { if (cur && cur.tanpa) simpanTanpa(); else simpanLangsung(); }
        else if (a === 'tanpa') tanpaResi();
        else if (a === 'batal') { g('msg').innerHTML = ''; clr(); }
    });
    R.addEventListener('change', function (e) {
        if (e.target.name === 'dari') { asalPilih(); g('resi').focus(); }
    });
    g('resi').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); scan(); } });
    // Esc = tutup kotak "Salah" yang terbuka, bila tidak ada = batal (hanya bila halaman ini masih tampil)
    if (window.__pcTerimaEsc) document.removeEventListener('keydown', window.__pcTerimaEsc);
    window.__pcTerimaEsc = function (e) {
        if (e.key !== 'Escape' || !R.isConnected) return;
        if (K.tutupEditor()) return;
        if (!cur) return;
        g('msg').innerHTML = '';
        clr();
        g('resi').focus();
    };
    document.addEventListener('keydown', window.__pcTerimaEsc);
    // ───── kunci kursor di kolom scan resi ─────
    // Aktif (default): setelah klik tombol / area kosong / selesai mengisi kolom lain, kursor kembali ke kolom scan resi
    // supaya scanner tidak mengetik ke tempat lain. Kolom ketik (qty, SKU luar resi, catatan) tetap bisa diklik langsung.
    // Berhenti sementara saat mode Tanpa Resi atau ada popup / pembesar foto terbuka.
    var KUNCI_KEY = 'pc_kunci_scan';
    var cbKunci = g('kunci');
    try { if (localStorage.getItem(KUNCI_KEY) === '0') cbKunci.checked = false; } catch (er) { /* abaikan: tanpa penyimpanan */ }
    cbKunci.addEventListener('change', function () {
        try { localStorage.setItem(KUNCI_KEY, cbKunci.checked ? '1' : '0'); } catch (er) { /* abaikan */ }
        if (cbKunci.checked) g('resi').focus();
    });
    var BOLEH_FOKUS = 'input[type=text],input[type=number],input:not([type]),textarea,select';
    function fokusScan(paksa) {
        if (!R.isConnected || !cbKunci.checked || (cur && cur.tanpa)) return;
        if (R.querySelector('.pclb, .mdo')) return;
        if (K.b.some(function (b) { return b.edit; })) return; // kotak "Salah" terbuka: kursor ada di isiannya
        var a = document.activeElement, r = g('resi');
        if (a === r) return;
        if (!paksa && a && a !== document.body && a.matches && a.matches(BOLEH_FOKUS) && a.offsetParent !== null) return;
        if (a && a.id === 'pc_kunci') return;
        r.focus();
    }
    R.addEventListener('focusin', function (e) { if (e.target !== g('resi')) setTimeout(fokusScan, 0); });
    R.addEventListener('focusout', function () { setTimeout(fokusScan, 0); });
    R.addEventListener('click', function (e) { if (!e.target.closest(BOLEH_FOKUS + ',#pc_kunci,.kunci')) setTimeout(fokusScan, 0); });
    U.autoBersih(g('resi'));
    U.skuPasang({ input: g('xsku'), dd: g('dd'), qty: g('xq'), tambah: tambahX });
    g('resi').focus();
})();
</script>
