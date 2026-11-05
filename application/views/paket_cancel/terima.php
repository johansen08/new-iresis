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
   <div class="sect"><b>Isi resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">Isi <b>Qty ada</b> sesuai barang yang benar-benar ada (tombol &minus; / + atau ketik). Tombol <b>0</b> = barang itu tidak ada di paket. Salah ambil: nolkan SKU resi yang tidak ada, lalu scan SKU yang ada di bagian bawah &mdash; otomatis terbaca sebagai salah ambil.</div></div>
   <div class="txw"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th></tr></thead><tbody id="pc_isi"></tbody></table></div>
  </div>

  <div class="sec">
   <div class="sect"><b id="pc_xjudul">Barang di luar resi</b><span class="inf" title="Petunjuk" data-act="tg">&#9432;</span><div class="infotxt hide">SKU / qty yang ada di paket tetapi tidak tertulis di resi. Scan SKU: tiap scan menambah 1 (tidak perlu mengetik qty).</div></div>
   <div id="pc_xt" class="txw hide"><table class="tx"><thead><tr><th>SKU</th><th>Foto</th><th class="c">Rak</th><th class="c">Qty ada</th><th>Status</th><th style="width:50px"></th></tr></thead><tbody id="pc_ex"></tbody></table></div>
   <div class="addx"><div class="ac"><input id="pc_xsku" placeholder="Ketik SKU, rekomendasi muncul..." autocomplete="off"><div id="pc_dd" class="dd hide"></div></div><input id="pc_xq" class="q" type="number" min="1" value="1"><button type="button" class="a" data-act="tambah-x">+ Tambah</button></div>
   <div id="pc_xmsg"></div>
  </div>

  <div class="sec">
   <div id="pc_ban"></div>
   <div class="sect"><b>Kejadian selisih &amp; alasan</b><small class="note">pilih alasan untuk tiap kejadian</small></div>
   <div id="pc_grups"></div>
   <div class="sect" style="margin-top:10px"><b>Catatan</b><small class="note" id="pc_catn">opsional</small></div>
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
    var ALASAN = <?= json_encode(Cancel_terima_fcd::ALASAN, JSON_UNESCAPED_UNICODE) ?>;
    var NMJ = { KURANG: 'KURANG', LEBIH: 'LEBIH', SALAH_AMBIL: 'SALAH AMBIL', DI_LUAR_RESI: 'DI LUAR RESI' };
    var CLSJ = { SESUAI: 'ok', KURANG: 'bad', LEBIH: 'info', SALAH_AMBIL: 'warn', DI_LUAR_RESI: 'lur' };
    var alasan = {};     // key kejadian (jenis|SKU|PASANGAN) -> kode alasan
    var grupCur = [];    // kejadian selisih saat ini
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
        alasan = {}; grupCur = [];
        g('grups').innerHTML = '';
        modeTanpa(false);
    }
    function modeTanpa(on) {
        g('secisi').classList.toggle('hide', on);
        g('fk').textContent = on ? 'Paket' : 'No. Resi';
        g('xjudul').textContent = on ? 'Barang di paket' : 'Barang di luar resi';
        g('catn').textContent = on ? 'wajib: ciri zip / keranjang asal' : 'opsional';
        g('grups').previousElementSibling.hidden = false;
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
        g('isi').innerHTML = (d.isi || []).map(function (x) {
            var q = parseInt(x.qty, 10);
            return '<tr data-s="' + esc(x.sku) + '" data-q="' + q + '"><td><b>' + esc(x.sku) + '</b></td><td>' + U.foto(x) + '</td><td class="c">' + esc(x.no_rak) + '</td><td class="c">' + q +
                '</td><td>' + stepper(q, 'isi') + '</td><td class="st"></td></tr>';
        }).join('') || '<tr><td colspan="6" class="c" style="color:var(--mut)">Resi tidak punya detail SKU</td></tr>';
        g('ex').innerHTML = '';
        g('xt').className = 'txw hide';
        g('xmsg').innerHTML = '';
        g('cat').value = '';
        alasan = {};
        g('form').className = 'pcd';
        hit();
        // Fokus tetap di kolom resi: periksa isi (ubah qty bila perlu), lalu scan resi yang sama sekali lagi untuk menyimpan.
        U.al('msg', 'ok', 'Periksa isi paket. Setelah semua beres (kurang / salah / lebih), <b>scan resi ini sekali lagi</b> untuk menyimpan.');
        g('resi').focus();
        g('resi').select();
    }

    // Blok "salah ambil" di kolom Status: SKU yang ada, rak-nya, dan qty dengan tombol - / +.
    function blokSalah(k, awalan, br) {
        var sku = esc(k.p);
        return '<div class="sa"><span class="b warn">SALAH AMBIL</span>' +
            '<div class="sa-r"><small>yang ada</small><b>' + sku + '</b>' + (br && br.rak ? '<small>rak ' + esc(br.rak) + '</small>' : '') + '</div>' +
            (br ? '<div class="stp3"><button type="button" data-act="' + awalan + 'min" data-sku="' + sku + '" aria-label="Kurangi qty ' + sku + '">&minus;</button><span class="n">' + br.qty + '</span>' +
                '<button type="button" data-act="' + awalan + 'plus" data-sku="' + sku + '" aria-label="Tambah qty ' + sku + '">+</button><small>pcs</small></div>'
                : '<div class="sa-r"><small>' + k.q + ' pcs</small></div>') + '</div>';
    }
    function stepper(v, tipe) {
        return '<span class="stp3"><button type="button" data-act="qmin" aria-label="Kurangi">&minus;</button><input class="q" type="number" min="0" value="' + v + '"><button type="button" data-act="qplus" aria-label="Tambah">+</button>' +
            (tipe === 'isi' ? '<button type="button" class="nol" data-act="qnol" title="Barang ini tidak ada di paket">0</button>' : '') + '</span>';
    }

    // Kelompokkan selisih jadi kejadian: kurang, lebih, salah ambil (kurang dipasangkan dengan SKU yang
    // muncul di luar resi / lebih di resi yang sama), di luar resi. Aturan sama dengan Cancel_terima_fcd::klasifikasi().
    function klas(b) {
        var kur = [], luar = [], leb = [], grup = [];
        b.forEach(function (x) {
            if (x.r > 0) {
                if (x.a < x.r) kur.push({ s: x.s, q: x.r - x.a });
                else if (x.a > x.r) leb.push({ s: x.s, q: x.a - x.r, j: 'LEBIH' });
            } else if (x.a > 0) luar.push({ s: x.s, q: x.a, j: 'DI_LUAR_RESI' });
        });
        var pool = luar.concat(leb);
        kur.forEach(function (k) {
            pool.forEach(function (o) {
                if (k.q <= 0 || o.q <= 0) return;
                var t = Math.min(k.q, o.q);
                grup.push({ j: 'SALAH_AMBIL', s: k.s, p: o.s, q: t });
                k.q -= t; o.q -= t;
            });
            if (k.q > 0) grup.push({ j: 'KURANG', s: k.s, p: '', q: k.q });
        });
        pool.forEach(function (o) { if (o.q > 0) grup.push({ j: o.j, s: o.s, p: '', q: o.q }); });
        grup.forEach(function (g) { g.key = g.j + '|' + g.s.toUpperCase() + '|' + g.p.toUpperCase(); });
        return grup;
    }
    function ambilBaris() {
        var b = [];
        g('isi').querySelectorAll('tr[data-s]').forEach(function (tr) { b.push({ s: tr.dataset.s, r: +tr.dataset.q, a: +tr.querySelector('input').value || 0, tr: tr }); });
        g('ex').querySelectorAll('tr').forEach(function (tr) { b.push({ s: tr.dataset.s, r: 0, a: +tr.querySelector('input').value || 0, tr: tr }); });
        return b;
    }
    function tagJenis(j, n, p) {
        return '<span class="b ' + CLSJ[j] + '">' + (NMJ[j] || j) + (n ? ' ' + n : '') + '</span>' + (p ? ' <small class="note">&rarr; ' + esc(p) + '</small>' : '');
    }

    // Hitung ulang status baris, kartu ringkasan, dan daftar kejadian selisih. Mengembalikan true bila ada selisih.
    function hit() {
        var b = ambilBaris(), x = 0;
        if (cur && cur.tanpa) {
            b.forEach(function (i) { x += i.a; });
            g('ban').innerHTML = '<div class="al ok"><b>PAKET TANPA RESI:</b> ' + x + ' pcs, ikut serah terima ke restock</div>';
            g('grups').innerHTML = ''; g('grups').previousElementSibling.hidden = true;
            return false;
        }
        g('grups').previousElementSibling.hidden = false;
        var grup = klas(b), tot = { KURANG: 0, LEBIH: 0, SALAH_AMBIL: 0, DI_LUAR_RESI: 0 }, sesuai = 0;
        grup.forEach(function (k) { tot[k.j] += k.q; });
        var adaLuar = function (sku) {
            var x = b.filter(function (y) { return y.r === 0 && y.s.toUpperCase() === String(sku).toUpperCase(); })[0];
            return x ? { qty: x.a, rak: x.tr.cells[2].textContent.trim() } : null;
        };
        b.forEach(function (i) {
            if (i.r > 0 && i.a === i.r) sesuai++;
            var st = i.tr.querySelector('.st');
            var sku = i.s.toUpperCase();
            var mine = grup.filter(function (k) { return k.s.toUpperCase() === sku; });
            var asPair = grup.filter(function (k) { return k.p && k.p.toUpperCase() === sku; });
            var paired = asPair.reduce(function (n, k) { return n + k.q; }, 0);
            // SKU salah yang seluruh qty-nya terpakai di salah ambil disembunyikan: tampil di baris SKU resi
            var sembunyi = i.r === 0 && paired > 0 && paired === i.a;
            i.tr.hidden = sembunyi;
            var h = [];
            mine.forEach(function (k) {
                if (k.j === 'SALAH_AMBIL') {
                    h.push(blokSalah(k, 'p', adaLuar(k.p)));
                } else h.push(tagJenis(k.j, k.q));
            });
            if (!mine.length && !sembunyi) asPair.forEach(function (k) { h.push(tagJenis('LEBIH', k.q) + ' <small class="note">dipakai ganti ' + esc(k.s) + '</small>'); });
            if (!h.length && i.r > 0) h.push(tagJenis('SESUAI', 0));
            if (st) st.innerHTML = h.join('<br>');
        });
        var kartu = [['o', 'Baris sesuai', sesuai], ['k', 'Kurang', tot.KURANG], ['l', 'Lebih', tot.LEBIH], ['s', 'Salah ambil', tot.SALAH_AMBIL], ['x', 'Di luar resi', tot.DI_LUAR_RESI]];
        g('ban').innerHTML = '<div class="sum">' + kartu.map(function (k) { return '<div class="' + k[0] + (k[2] > 0 ? ' on' : '') + '"><b>' + k[2] + '</b><small>' + k[1] + '</small></div>'; }).join('') + '</div>';
        grupCur = grup;
        grup.forEach(function (k) { if (k.j === 'SALAH_AMBIL' && !alasan[k.key]) alasan[k.key] = 'PICKER_SALAH'; });
        g('grups').innerHTML = grup.length ? grup.map(function (k) {
            var judul = k.j === 'SALAH_AMBIL' ? 'tertulis <b>' + esc(k.s) + '</b> &rarr; yang ada <b>' + esc(k.p) + '</b> &times; ' + k.q
                : k.j === 'KURANG' ? '<b>' + esc(k.s) + '</b> kurang ' + k.q
                : k.j === 'LEBIH' ? '<b>' + esc(k.s) + '</b> lebih ' + k.q
                : '<b>' + esc(k.s) + '</b> di luar resi &times; ' + k.q;
            var opt = '<option value="">Pilih alasan...</option>' + Object.keys(ALASAN[k.j]).map(function (kd) { return '<option value="' + kd + '"' + (alasan[k.key] === kd ? ' selected' : '') + '>' + esc(ALASAN[k.j][kd]) + '</option>'; }).join('');
            return '<div class="grp' + (alasan[k.key] ? '' : ' need') + '"><div>' + tagJenis(k.j, 0) + ' ' + judul + '</div><select data-key="' + esc(k.key) + '" aria-label="Alasan">' + opt + '</select></div>';
        }).join('') : '<div class="al ok" style="margin:0"><b>ISI SESUAI RESI</b> &mdash; tidak ada alasan yang perlu diisi.</div>';
        return grup.length > 0;
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
                '</td><td class="c">' + stepper(qty, 'ex') + '</td><td class="st"></td><td><button type="button" class="a x s" data-act="hapus-x">&times;</button></td></tr>');
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

    // ───── simpan langsung (scan resi kedua / tombol Simpan), tanpa pop up ─────
    function simpanLangsung() {
        if (!cur) return;
        if (!asalWajib()) return;
        hit();
        var kosong = grupCur.filter(function (k) { return !alasan[k.key]; });
        if (kosong.length) {
            U.al('msg', 'bad', 'Pilih alasan untuk <b>' + kosong.length + '</b> kejadian selisih dulu, lalu scan resi ini lagi.');
            var sl = g('grups').querySelector('.grp.need select');
            if (sl) sl.focus();
            return;
        }
        var am = {};
        grupCur.forEach(function (k) { am[k.key] = alasan[k.key]; });
        var it = [];
        g('isi').querySelectorAll('tr[data-s]').forEach(function (tr) { it.push({ s: tr.dataset.s, r: +tr.dataset.q, a: +tr.querySelector('input').value || 0 }); });
        g('ex').querySelectorAll('tr').forEach(function (tr) { it.push({ s: tr.dataset.s, r: 0, a: +tr.querySelector('input').value || 0 }); });
        var dari = asalDipilih(), cat = g('cat').value.trim(), resi = cur;
        if (sibuk) return;
        sibuk = true;
        U.post(URL.simpan, {
            noresi: resi.noresi, dari: dari, catatan: cat, alasan: JSON.stringify(am),
            items: JSON.stringify(it.map(function (i) { return { sku: i.s, qty: i.a }; }))
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
        else if (a === 'qmin' || a === 'qplus' || a === 'qnol') {
            var inp = t.closest('td').querySelector('input.q');
            inp.value = a === 'qnol' ? 0 : Math.max(0, (+inp.value || 0) + (a === 'qplus' ? 1 : -1));
            hit();
        }
        else if (a === 'pmin' || a === 'pplus') {
            var br = [].filter.call(g('ex').querySelectorAll('tr'), function (tr) { return tr.dataset.s.toUpperCase() === t.dataset.sku.toUpperCase(); })[0];
            if (br) { var pi = br.querySelector('input.q'); pi.value = Math.max(0, (+pi.value || 0) + (a === 'pplus' ? 1 : -1)); hit(); }
        }
        else if (a === 'hapus-x') { t.closest('tr').remove(); if (!g('ex').children.length) g('xt').className = 'txw hide'; hit(); }
        else if (a === 'simpan') { if (cur && cur.tanpa) simpanTanpa(); else simpanLangsung(); }
        else if (a === 'tanpa') tanpaResi();
        else if (a === 'batal') { g('msg').innerHTML = ''; clr(); }
    });
    R.addEventListener('input', function (e) {
        if (e.target.matches('#pc_isi input, #pc_ex input')) hit();
    });
    R.addEventListener('change', function (e) {
        if (e.target.matches('#pc_grups select')) {
            alasan[e.target.dataset.key] = e.target.value;
            e.target.closest('.grp').classList.toggle('need', !e.target.value);
            return;
        }
        if (e.target.name === 'dari') { asalPilih(); g('resi').focus(); }
    });
    g('resi').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); scan(); } });
    // Esc = batal (hanya bila halaman ini masih tampil)
    if (window.__pcTerimaEsc) document.removeEventListener('keydown', window.__pcTerimaEsc);
    window.__pcTerimaEsc = function (e) {
        if (e.key !== 'Escape' || !R.isConnected || !cur) return;
        g('msg').innerHTML = '';
        clr();
        g('resi').focus();
    };
    document.addEventListener('keydown', window.__pcTerimaEsc);
    // ───── kunci kursor di kolom scan resi ─────
    // Aktif (default): setelah klik tombol / area kosong / selesai mengisi kolom lain, kursor kembali ke kolom scan resi
    // supaya scanner tidak mengetik ke tempat lain. Kolom ketik (qty, SKU luar resi, catatan) dan pilihan alasan tetap bisa
    // diklik langsung. Berhenti sementara saat mode Tanpa Resi atau ada popup / pembesar foto terbuka.
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
        var a = document.activeElement, r = g('resi');
        if (a === r) return;
        if (!paksa && a && a !== document.body && a.matches && a.matches(BOLEH_FOKUS) && a.offsetParent !== null) return;
        if (a && a.id === 'pc_kunci') return;
        r.focus();
    }
    R.addEventListener('focusin', function (e) { if (e.target !== g('resi')) setTimeout(fokusScan, 0); });
    R.addEventListener('focusout', function () { setTimeout(fokusScan, 0); });
    R.addEventListener('click', function (e) { if (!e.target.closest(BOLEH_FOKUS + ',#pc_kunci,.kunci')) setTimeout(fokusScan, 0); });
    // Selesai memilih alasan: kursor kembali ke scan walau kotak pilihan masih terfokus
    R.addEventListener('change', function (e) { if (e.target.matches('select')) setTimeout(function () { fokusScan(true); }, 0); });
    U.autoBersih(g('resi'));
    U.skuPasang({ input: g('xsku'), dd: g('dd'), qty: g('xq'), tambah: tambahX });
    g('resi').focus();
})();
</script>
