<link rel="stylesheet" href="assets/css/gdbr.css?v=<?= @filemtime(FCPATH . 'assets/css/gdbr.css') ?: '1' ?>">
<?php
// Scan Resi GD-BR: tahap RESI (tandai), GEDUNG BARU, PACKER. Alur: docs/GDBR.md.
$petugas = $petugas ?? ['label' => '', 'nama' => ''];
?>
<div class="gdbr" id="gdbr_root">
  <div class="scan-grid">
    <div>
      <div class="card">
        <div class="step"><i>1</i> Petugas</div>
        <div class="who" id="who">
          <i class="av big"><?= htmlspecialchars(implode('', array_map(function ($w) { return mb_substr($w, 0, 1); }, array_slice(array_filter(explode(' ', (string) $petugas['nama'])), 0, 2)))) ?></i>
          <div><b><?= htmlspecialchars($petugas['label']) ?></b></div>
        </div>
        <div class="step" style="margin-top:20px"><i>2</i> Tahap pengecekan <small id="e-stage" class="hide">pilih salah satu</small></div>
        <div class="stage-g" id="stage-g">
          <label class="stage"><input type="radio" name="gdbr_stage" value="RESI"><span class="box"><span class="ico"><svg class="ic" viewBox="0 0 24 24"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8zM7 7h.01"/></svg></span><span><b>RESI</b></span></span></label>
          <label class="stage"><input type="radio" name="gdbr_stage" value="GEDUNG_BARU"><span class="box"><span class="ico"><svg class="ic" viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></span><span><b>GEDUNG BARU</b></span></span></label>
          <label class="stage"><input type="radio" name="gdbr_stage" value="PACKER"><span class="box"><span class="ico"><svg class="ic" viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8"/></svg></span><span><b>PACKER</b></span></span></label>
        </div>
        <div class="step" style="margin-top:20px"><i>3</i> Scan no. resi</div>
        <div class="scanrow">
          <input id="resi" class="fld scan" placeholder="No. Resi" autocomplete="off">
        </div>
        <div id="msg"></div>
        <div class="rh">
          <div class="rh-t"><b>10 resi terakhir discan</b><span id="rh-n"></span></div>
          <ul class="hist" id="hist"></ul>
        </div>
      </div>

      <div class="card hide" id="form">
        <div class="fh">
          <div><div class="k" id="f-stage"></div><h3 id="f-resi"></h3></div>
          <span class="b slate" id="f-toko"></span>
        </div>
        <div class="sum" id="f-sum"></div>

        <div class="sect">Isi resi <small>Isi Qty ada sesuai barang yang benar-benar ada di tangan.</small></div>
        <div class="tw"><table class="tx"><thead id="isi-head"></thead><tbody id="isi"></tbody></table></div>

        <div class="sect">Barang di luar resi <small>Salah ambil: nolkan SKU resi yang tidak ada, lalu tambahkan SKU yang ada.</small></div>
        <div class="tw hide" id="xt"><table class="tx"><thead><tr><th>SKU</th><th class="c">Rak</th><th class="c">Qty ada</th><th style="width:50px"></th></tr></thead><tbody id="ex"></tbody></table></div>
        <div class="addx">
          <div class="ac"><input class="fld" id="xsku" placeholder="Ketik SKU..." autocomplete="off"><div class="dd hide" id="dd"></div></div>
          <input class="fld q" id="xq" type="number" min="1" value="1" aria-label="Qty">
          <button class="btn sec" id="btn-xadd" type="button">+ Tambah</button>
        </div>
        <div id="xmsg"></div>

        <label class="opt" style="margin-top:12px;padding-left:0"><input type="checkbox" id="lain"><span>Ada masalah lain (barang rusak, cacat, kemasan, dll.)</span></label>
        <div id="ban"></div>
        <div class="sect">Catatan <small id="catn">wajib bila ada yang tidak sesuai (salah, kurang, lebih, atau lainnya)</small></div>
        <textarea id="cat" class="fld" rows="2" maxlength="255" placeholder="mis. picker salah ambil ZU-2, qty kurang 1"></textarea>

        <div class="fa-row">
          <button class="btn sec" id="btn-batal" type="button">Batal</button>
          <button class="btn" id="btn-simpan" type="button"><svg class="ic" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Simpan Pengecekan</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('gdbr/_umum'); ?>
<script type="text/javascript">
(function () {
    'use strict';
    var U = window.GDB, esc = U.esc, ic = U.ic, fmtD = U.fmtD;
    var R = document.getElementById('gdbr_root');
    var $ = function (s) { return R.querySelector(s); };
    var $$ = function (s) { return Array.prototype.slice.call(R.querySelectorAll(s)); };
    var STAGE_NM = { RESI: 'RESI', GEDUNG_BARU: 'GEDUNG BARU', PACKER: 'PACKER' };
    var PETUGAS = <?= json_encode($petugas) ?>;

    U.ovlBersih();                                   // sisa lapisan dari kunjungan sebelumnya

    var cur = null, extra = [], sibuk = false, antre = [];
    var KUNCI = 'gdbr_riwayat';
    var RIWAYAT = [];
    try { RIWAYAT = JSON.parse(sessionStorage.getItem(KUNCI) || '[]') || []; } catch (e) { RIWAYAT = []; }

    function stage() { var c = R.querySelector('[name=gdbr_stage]:checked'); return c ? c.value : null; }
    function setMsg(tipe, html) {
        $('#msg').innerHTML = html ? '<div class="al ' + tipe + '">' + html + '</div>' : '';
        var el = $('#resi'); el.classList.remove('fl-ok', 'fl-bad', 'fl-warn');
        if (html && (tipe === 'ok' || tipe === 'bad' || tipe === 'warn')) {
            void el.offsetWidth; el.classList.add('fl-' + tipe);
            setTimeout(function () { el.classList.remove('fl-' + tipe); }, 700);
        }
    }

    // ───── riwayat 10 scan terakhir (tersimpan di sessionStorage supaya bertahan antar halaman) ─────
    function catat(no, s, st, h, w) {
        RIWAYAT.unshift({ noresi: no, s: s, p: PETUGAS.label, w: w || U.sekarang(), h: h || null, st: st });
        if (RIWAYAT.length > 50) RIWAYAT.length = 50;
        try { sessionStorage.setItem(KUNCI, JSON.stringify(RIWAYAT)); } catch (e) {}
        renderRiwayat();
    }
    function renderRiwayat() {
        var ST = { dup: '<span class="b warn">SUDAH ADA</span>', nf: '<span class="b bad">TIDAK ADA</span>', tolak: '<span class="b bad">DITOLAK</span>' };
        $('#hist').innerHTML = RIWAYAT.length ? RIWAYAT.slice(0, 10).map(function (x) {
            var b = x.st === 'ok' ? (x.h ? U.badges(x.h) : '<span class="b ok">MASUK GD-BR</span>') : ST[x.st];
            return '<li><div class="t"><b class="num">' + esc(x.noresi) + '</b>' + b + '</div><small>' + STAGE_NM[x.s] + ' · ' + esc(x.p) + ' · ' + fmtD(x.w) + '</small></li>';
        }).join('') : '<li><small>Belum ada scan. Hasil scan tampil di sini.</small></li>';
        $('#rh-n').textContent = RIWAYAT.length ? 'total sesi ini: ' + RIWAYAT.length : '';
    }

    // ───── pilih tahap ─────
    R.querySelector('#stage-g').addEventListener('change', function () {
        $('#stage-g').classList.remove('err'); $('#e-stage').classList.add('hide');
        $$('.stage').forEach(function (l) { l.classList.remove('err'); });
        clr(); setMsg();
    });
    function wajibStage() {
        if (!stage()) {
            $$('.stage').forEach(function (l) { l.classList.add('err'); });
            $('#e-stage').classList.remove('hide');
            setMsg('bad', 'Pilih <b>tahap pengecekan</b> (RESI, GEDUNG BARU, atau PACKER) dulu.');
            return false;
        }
        return true;
    }

    /* Kolom scan selalu dibersihkan dan siap menerima resi berikutnya */
    function clr() {
        $('#resi').value = ''; $('#form').classList.add('hide'); cur = null; extra = [];
        $('#resi').focus({ preventScroll: true });
    }

    // ───── scan: antre supaya scan beruntun tidak ada yang hilang ─────
    function scan() {
        var el = $('#resi'), no = el.value.trim();
        if (!no) return;
        if (!wajibStage()) return;
        el.value = '';
        antre.push({ no: no, s: stage() });
        proses();
    }
    function proses() {
        if (sibuk || !antre.length) return;
        var j = antre.shift();
        sibuk = true;
        U.post(U.URL.cek, { tahap: j.s, resi: j.no }).then(function (res) {
            hasil(j.s, j.no, (res && res.data) || {});
        }).catch(function (x) {
            setMsg('bad', esc(U.pesanGagal(x)));
        }).then(function () { sibuk = false; proses(); });
    }
    function hasil(s, no, d) {
        var st = d.status, rs = esc(d.noresi || no);
        if (st === 'ok') { setMsg(); tampil(d, s); return; }
        if (st === 'ok_tandai') {
            catat(d.noresi, 'RESI', 'ok', null, d.waktu);
            clr();
            setMsg('ok', 'Resi <b>' + rs + '</b> masuk data GD-BR dengan status <b>Belum dicek GD-BR</b>.');
            return;
        }
        clr();
        if (st === 'tidak_ada') { catat(no, s, 'nf'); setMsg('bad', 'Resi <b>' + esc(no) + '</b> tidak ditemukan.'); }
        else if (st === 'belum_picker') { catat(no, s, 'nf'); setMsg('bad', 'Resi <b>' + rs + '</b> tidak ditemukan di data scan picker.'); }
        else if (st === 'sudah') {
            catat(d.noresi, s, 'dup');
            if (s === 'RESI') setMsg('bad', 'Resi <b>' + rs + '</b> sudah masuk data GD-BR (ditandai ' + esc(d.oleh) + ' pada ' + fmtD(d.waktu) + '). Tidak bisa discan lagi.');
            else setMsg('warn', 'Resi <b>' + rs + '</b> sudah dicek ' + STAGE_NM[s] + ' oleh ' + esc(d.oleh) + ' pada ' + fmtD(d.waktu) + '. Lihat detailnya di Rekap.');
        }
        else if (st === 'belum_tandai') { catat(d.noresi, s, 'tolak'); setMsg('bad', 'Resi <b>' + rs + '</b> belum ditandai tim resi sebagai resi gedung baru. Scan di tahap RESI dulu.'); }
        else if (st === 'belum_gb') { catat(d.noresi, s, 'tolak'); setMsg('bad', 'Resi <b>' + rs + '</b> belum dicek di gedung baru. Cek di tahap GEDUNG BARU dulu.'); }
        else if (st === 'batal') { catat(d.noresi, s, 'tolak'); setMsg('bad', 'Resi <b>' + rs + '</b> berstatus CANCEL, tidak bisa masuk data GD-BR. Tangani lewat Paket Cancel.'); }
        else if (st === 'arsip') { setMsg('bad', 'Mode Arsip aktif: scan tidak bisa disimpan. Keluar dari Mode Arsip dulu.'); }
        else { setMsg('bad', 'Balasan server tidak dikenal. Coba scan ulang.'); }
    }

    // ───── form cek (GEDUNG BARU / PACKER) ─────
    function tampil(d, s) {
        cur = { noresi: d.resi.noresi, isi: d.isi, gb: d.gb }; extra = [];
        $('#f-stage').textContent = 'Cek ' + STAGE_NM[s];
        $('#f-resi').innerHTML = esc(d.resi.noresi) + U.cp(d.resi.noresi);
        $('#f-toko').textContent = [d.resi.toko, d.resi.mp].filter(Boolean).join(' · ');
        var gb = d.gb;
        $('#f-sum').innerHTML =
            '<div><span>No. Pesanan</span><b class="num">' + esc(d.resi.pesanan) + U.cp(d.resi.pesanan) + '</b></div>' +
            '<div class="hl"><span>PICKER</span><b>' + esc(d.picker.label) + '</b><small>' + fmtD(d.picker.waktu) + '</small></div>' +
            (s === 'PACKER' ? '<div class="hl"><span>GD-BR</span><b>' + (gb ? esc(gb.label) : 'Belum dicek') + '</b><small>' + (gb ? fmtD(gb.waktu) : '–') + '</small></div>' : '') +
            '<div class="hl"><span>' + (s === 'GEDUNG_BARU' ? 'GD-BR' : 'PACKER') + '</span><b>' + esc(PETUGAS.label) + '</b><small>Waktu dicatat saat Simpan</small></div>';
        $('#isi-head').innerHTML = '<tr><th>SKU</th><th class="c">Rak</th><th class="c">Qty resi</th>' + (s === 'PACKER' ? '<th class="c">Hasil GD-BR</th>' : '') + '<th class="c">Qty ada</th><th>Status</th></tr>';
        $('#isi').innerHTML = d.isi.map(function (i) {
            var hq = '';
            if (s === 'PACKER') {
                var v = gb ? (gb.items[i.sku] != null ? gb.items[i.sku] : 0) : null;
                hq = '<td class="c">' + (v == null ? '<span class="muted">–</span>' : '<b' + (v !== i.qty ? ' style="color:var(--bad-fg)"' : '') + '>' + v + '</b>') + '</td>';
            }
            return '<tr data-s="' + esc(i.sku) + '" data-q="' + i.qty + '"><td><div class="skucell">' + U.thumb(i, true) + '<span><b>' + esc(i.sku) + '</b></span></div></td><td class="c">' + esc(i.no_rak) + '</td><td class="c"><b>' + i.qty + '</b></td>' + hq +
                '<td class="c"><input class="q" type="number" min="0" value="' + i.qty + '" aria-label="Qty ada ' + esc(i.sku) + '"></td><td class="st"></td></tr>';
        }).join('');
        if (s === 'PACKER' && gb && gb.luar.length) {
            setMsg('info', 'Cek gedung baru mencatat barang di luar resi: ' + gb.luar.map(function (e) { return '<b>' + esc(e.sku) + ' × ' + e.qty + '</b>'; }).join(', ') + '. Cek ulang sebelum dipacking.');
        }
        renderExtra();
        $('#cat').value = ''; $('#lain').checked = false; $('#xmsg').innerHTML = '';
        $('#form').classList.remove('hide');
        hit();
        var f = $('#isi input'); if (f) { f.focus(); f.select(); }
        $('#form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderExtra() {
        $('#ex').innerHTML = extra.map(function (e, i) {
            return '<tr><td><div class="skucell">' + U.thumb(e, true) + '<span><b>' + esc(e.sku) + '</b></span></div></td><td class="c">' + esc(e.no_rak) + '</td><td class="c"><input class="q" type="number" min="1" value="' + e.qty + '" data-i="' + i + '" aria-label="Qty ' + esc(e.sku) + '"></td><td><button type="button" class="btn sec sm" data-del="' + i + '" aria-label="Hapus">&times;</button></td></tr>';
        }).join('');
        $('#xt').classList.toggle('hide', !extra.length);
    }

    function cekSekarang() {
        var items = $$('#isi tr[data-s]').map(function (tr) { return { sku: tr.dataset.s, qty_resi: +tr.dataset.q, qty_ada: Math.max(0, +tr.querySelector('input').value || 0) }; });
        return { items: items, extra: extra.map(function (e) { return { sku: e.sku, qty: e.qty }; }), lain: $('#lain').checked };
    }
    function hitung(c) {
        var k = 0, l = 0, x = 0;
        c.items.forEach(function (i) { var d = i.qty_ada - i.qty_resi; if (d < 0) k -= d; else l += d; });
        c.extra.forEach(function (e) { x += e.qty; });
        var o = c.lain ? 1 : 0;
        return { kurang: k, lebih: l, salah: x, lain: o, sesuai: (!k && !l && !x && !o) ? 1 : 0 };
    }
    function hit() {
        $$('#isi tr[data-s]').forEach(function (tr) {
            var q = +tr.dataset.q, v = +tr.querySelector('input').value || 0, d = v - q;
            tr.querySelector('.st').innerHTML = d === 0 ? '<span class="b ok">SESUAI</span>' : d < 0 ? '<span class="b bad">KURANG ' + (-d) + '</span>' : '<span class="b warn">LEBIH ' + d + '</span>';
        });
        var h = hitung(cekSekarang());
        var parts = [h.kurang && 'kurang ' + h.kurang + ' pcs', h.lebih && 'lebih ' + h.lebih + ' pcs', h.salah && 'salah / di luar resi ' + h.salah + ' pcs', h.lain && 'masalah lain'].filter(Boolean);
        $('#ban').innerHTML = h.sesuai ? '<div class="al ok"><b>ISI SESUAI RESI</b></div>' : '<div class="al warn"><b>ADA YANG TIDAK SESUAI:</b> ' + parts.join(', ') + '</div>';
        return !h.sesuai;
    }

    // ───── barang di luar resi ─────
    function tambahBaris(sku, qty) {
        var xm = $('#xmsg'), kode = sku.id_sku;
        var al = function (t, h) { xm.innerHTML = '<div class="al ' + t + '">' + h + '</div>'; };
        if (cur.isi.some(function (i) { return i.sku.toUpperCase() === kode.toUpperCase(); })) return al('warn', 'SKU <b>' + esc(kode) + '</b> ada di resi: ubah Qty ada di tabel atas.');
        var ada = extra.filter(function (e) { return e.sku.toUpperCase() === kode.toUpperCase(); })[0];
        if (ada) ada.qty += qty; else extra.push({ sku: kode, qty: qty, no_rak: sku.no_rak, foto: sku.foto, foto_lokal: sku.foto_lokal });
        $('#xsku').value = ''; $('#xq').value = 1; $('#dd').classList.add('hide'); xm.innerHTML = '';
        renderExtra(); hit();
    }
    function tambahX() {
        var teks = $('#xsku').value.trim(), qty = +$('#xq').value, xm = $('#xmsg');
        xm.innerHTML = '';
        var al = function (t, h) { xm.innerHTML = '<div class="al ' + t + '">' + h + '</div>'; };
        if (!cur) return;
        if (!teks) return al('bad', 'Isi SKU dulu.');
        if (!(qty >= 1)) return al('bad', 'Qty minimal 1.');
        U.post(U.URL.sku_detail, { sku: teks }).then(function (res) {
            var d = (res && res.data) || { status: 'tidak_ada' };
            if (d.status === 'ok') tambahBaris(d.sku, qty);
            else if (d.status === 'ambigu') al('warn', 'Beberapa SKU cocok: ' + d.kandidat.map(function (k) { return esc(k.id_sku); }).join(', ') + '. Pilih dari rekomendasi.');
            else al('bad', 'SKU <b>' + esc(teks) + '</b> tidak ditemukan.');
        }).catch(function (x) { al('bad', esc(U.pesanGagal(x))); });
    }

    var ddSel = -1, ddSeq = 0, ddTimer = null;
    $('#xsku').addEventListener('input', function () {
        var q = this.value.trim(), dd = $('#dd'); ddSel = -1;
        clearTimeout(ddTimer);
        if (q.length < 2) { dd.classList.add('hide'); return; }
        ddTimer = setTimeout(function () {
            var mine = ++ddSeq;
            jQuery.getJSON(U.URL.sku_saran, { q: q }).done(function (res) {
                if (mine !== ddSeq || $('#xsku').value.trim() !== q) return;
                var r = (res && res.data && res.data.results) || [];
                dd.innerHTML = r.map(function (k) { return '<div data-s="' + esc(k.id_sku) + '">' + U.thumb(k) + '<span><b>' + esc(k.id_sku) + '</b></span><small>Rak ' + esc(k.no_rak || '-') + '</small></div>'; }).join('');
                dd.classList.toggle('hide', !r.length);
            });
        }, 250);
    });
    $('#xsku').addEventListener('keydown', function (e) {
        var it = $$('#dd div');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); if (!it.length) return; ddSel = (ddSel + (e.key === 'ArrowDown' ? 1 : -1) + it.length) % it.length; it.forEach(function (d, i) { d.classList.toggle('on', i === ddSel); }); }
        else if (e.key === 'Enter') { e.preventDefault(); if (ddSel >= 0 && it[ddSel]) { this.value = it[ddSel].dataset.s; $('#dd').classList.add('hide'); $('#xq').focus(); $('#xq').select(); } else tambahX(); }
        else if (e.key === 'Escape') $('#dd').classList.add('hide');
    });
    $('#dd').addEventListener('mousedown', function (e) { var d = e.target.closest('[data-s]'); if (d) { e.preventDefault(); $('#xsku').value = d.dataset.s; this.classList.add('hide'); $('#xq').focus(); $('#xq').select(); } });
    $('#xsku').addEventListener('blur', function () { setTimeout(function () { $('#dd').classList.add('hide'); }, 120); });
    $('#xq').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); tambahX(); $('#xsku').focus(); } });
    $('#btn-xadd').addEventListener('click', tambahX);
    $('#lain').addEventListener('change', hit);

    $('#form').addEventListener('input', function (e) {
        if (e.target.matches('#isi input')) hit();
        else if (e.target.matches('#ex input')) { extra[+e.target.dataset.i].qty = Math.max(1, +e.target.value || 1); hit(); }
    });
    $('#form').addEventListener('click', function (e) { var d = e.target.closest('[data-del]'); if (d) { extra.splice(+d.dataset.del, 1); renderExtra(); hit(); } });
    $('#btn-batal').addEventListener('click', function () { setMsg(); clr(); });

    // ───── simpan: pratinjau + konfirmasi ─────
    $('#btn-simpan').addEventListener('click', function () {
        if (!cur) return;
        if (!wajibStage()) return;
        var s = stage(), c = cekSekarang(), selisih = hit(), cat = $('#cat').value.trim();
        if (selisih && !cat) { setMsg('bad', 'Ada yang tidak sesuai: isi catatan penyebabnya.'); $('#cat').focus(); return; }
        setMsg();
        var h = hitung(c), tot = 0;
        var rows = c.items.map(function (i) {
            tot += i.qty_ada;
            return '<tr><td><b>' + esc(i.sku) + '</b></td><td class="c">' + i.qty_resi + '</td><td class="c">' + i.qty_ada + '</td><td>' + (i.qty_ada === i.qty_resi ? '<span class="b ok">SESUAI</span>' : i.qty_ada < i.qty_resi ? '<span class="b bad">KURANG ' + (i.qty_resi - i.qty_ada) + '</span>' : '<span class="b warn">LEBIH ' + (i.qty_ada - i.qty_resi) + '</span>') + '</td></tr>';
        }).concat(c.extra.map(function (e) {
            tot += e.qty;
            return '<tr><td><b>' + esc(e.sku) + '</b></td><td class="c">0</td><td class="c">' + e.qty + '</td><td><span class="b info">SALAH ' + e.qty + '</span></td></tr>';
        })).join('');
        var resi = cur.noresi;
        U.modal('<h3>Simpan pengecekan?</h3><div class="pv"><div><span>No. Resi</span><b>' + esc(resi) + '</b></div><div><span>Tahap</span><b>' + STAGE_NM[s] + '</b></div><div><span>Petugas</span><b>' + esc(PETUGAS.label) + '</b></div><div><span>Waktu cek</span><b>Saat Simpan diklik</b></div><div><span>Total ada</span><b>' + tot + ' pcs</b></div></div>' +
            '<div class="tw"><table class="tx"><thead><tr><th>SKU</th><th class="c">Qty resi</th><th class="c">Qty ada</th><th>Status</th></tr></thead><tbody>' + rows + '</tbody></table></div><div style="margin-top:10px">' +
            (h.sesuai ? '<div class="al ok" style="margin-top:0"><b>ISI SESUAI RESI</b></div>' : '<div class="al warn" style="margin-top:0"><b>ADA YANG TIDAK SESUAI</b> ' + U.badges(h) + '</div>') + '</div>' + (cat ? '<div class="pvc"><b>Catatan:</b> ' + esc(cat) + '</div>' : ''),
            'Ya, simpan', function () {
                return U.post(U.URL.simpan, {
                    tahap: s, resi: resi, catatan: cat, lain: c.lain ? 1 : 0,
                    items: JSON.stringify(c.items.map(function (i) { return { sku: i.sku, qty_ada: i.qty_ada }; })),
                    extra: JSON.stringify(c.extra)
                }).then(function (res) {
                    var d = (res && res.data) || {};
                    if (!d.ok) return esc(res && res.message ? res.message : 'Gagal menyimpan.');
                    catat(resi, s, 'ok', d.hasil, d.waktu);
                    clr();
                    setMsg(d.hasil.sesuai ? 'ok' : 'warn', 'Resi <b>' + esc(resi) + '</b> tersimpan' + (d.hasil.sesuai ? ', isi sesuai resi.' : ', selisih tercatat.'));
                    return null;
                }).catch(function (x) { return esc(U.pesanGagal(x)); });
            });
    });

    /* Kolom scanner: terbaca otomatis tanpa Enter (rentetan ketikan cepat atau tempel), isi lama dibersihkan sebelum scan berikutnya */
    (function () {
        var el = $('#resi'), tmr = null, last = 0, n = 0, cepat = 0;
        function jalan() {
            clearTimeout(tmr);
            if (!el.value.trim()) return;
            el.dataset.siap = '1'; n = 0; cepat = 0;
            scan();
        }
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); jalan(); return; }
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && el.dataset.siap === '1') { el.value = ''; el.dataset.siap = ''; n = 0; cepat = 0; }
        });
        el.addEventListener('input', function (e) {
            var now = Date.now(), gap = now - last; last = now;
            if (!el.value) { el.dataset.siap = ''; n = 0; cepat = 0; return; }
            if (el.value.length <= 1) { n = 0; cepat = 0; }
            n++; if (gap < 50) cepat++;
            clearTimeout(tmr);
            if (e.inputType === 'insertFromPaste') tmr = setTimeout(jalan, 60);
            else if (el.value.length >= 8 && n >= 7 && cepat >= n * 0.8) tmr = setTimeout(jalan, 120);
        });
        el.addEventListener('focus', function () { this.select(); });
    })();

    renderRiwayat();
    $('#resi').focus();

    // Datang dari Rekap lewat "Cek resi ini": pilih tahap dan muat resinya
    try {
        var isi = JSON.parse(sessionStorage.getItem('gdbr_isi') || 'null');
        sessionStorage.removeItem('gdbr_isi');
        if (isi && isi.tahap && isi.resi) {
            var rd = R.querySelector('[name=gdbr_stage][value=' + isi.tahap + ']');
            if (rd) { rd.checked = true; $('#resi').value = isi.resi; scan(); }
        }
    } catch (e) {}
})();
</script>
