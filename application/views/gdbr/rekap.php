<link rel="stylesheet" href="assets/css/gdbr.css?v=<?= @filemtime(FCPATH . 'assets/css/gdbr.css') ?: '1' ?>">
<?php // Rekap Resi GD-BR: ringkasan, filter, tabel, dan drawer detail. Alur: docs/GDBR.md. ?>
<div class="gdbr" id="gdbr_root">
  <div class="kp-head">
    <div class="period"><span>Periode</span>
      <label class="chip" style="text-transform:none;letter-spacing:0;font-size:14px;color:var(--text);font-weight:500;margin:0">
        <svg class="ic" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        <input type="text" id="reportrange" readonly style="border:0;background:transparent;width:210px;font:inherit;color:inherit;cursor:pointer;padding:0">
      </label>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn sec" id="btn-export" type="button"><svg class="ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Export</button>
      <a class="btn link" href="gdbr/scan" style="text-decoration:none"><svg class="ic" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Scan Resi</a>
    </div>
  </div>
  <div class="kpis" id="kpis"></div>
  <div class="body">
    <div class="toolbar">
      <div class="search"><svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg><input id="q" placeholder="Cari resi, pesanan, toko, picker, petugas..." autocomplete="off"></div>
      <div id="filters" style="display:contents"></div>
      <button class="reset hide" id="f-reset" type="button">× Reset filter</button>
    </div>
    <div class="tw2"><table class="kt">
      <thead><tr><th style="width:36px"><input type="checkbox" id="cb-all" aria-label="Pilih semua di halaman ini"></th><th>Resi / Pesanan</th><th>Diambil picker</th><th>Ditandai tim resi</th><th>Cek Gedung Baru</th><th>Cek Packer</th><th>Progres</th><th></th></tr></thead>
      <tbody id="rows"><tr><td colspan="8"><div class="empty">Memuat...</div></td></tr></tbody>
    </table></div>
    <div class="foot"><div id="info"></div><div class="pager"><label>Baris per halaman <select id="per"><option>10</option><option>25</option><option>50</option></select></label><div class="pg" id="pg"></div></div></div>
  </div>
</div>

<?php $this->load->view('gdbr/_umum'); ?>
<script type="text/javascript">
(function () {
    'use strict';
    var U = window.GDB, esc = U.esc, ic = U.ic, IC = U.IC, fmtD = U.fmtD;
    var WEBMASTER = <?= !empty($webmaster) ? 'true' : 'false' ?>;   // tombol ubah/hapus; server tetap memeriksa role
    var R = document.getElementById('gdbr_root');
    var $ = function (s, r) { return (r || R).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || R).querySelectorAll(s)); };

    U.ovlBersih();
    var OV = U.ovl();
    OV.innerHTML = '<div class="scrim" id="scrim"></div>' +
        '<aside class="drawer" id="drawer" aria-hidden="true" aria-labelledby="dr-title">' +
        '<div class="dr-h"><span class="ico">' + ic('<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8M8 17h5"/>') + '</span><div><h4 id="dr-title"></h4><div class="meta" id="dr-meta"></div></div><button class="x" id="dr-x" type="button" aria-label="Tutup">&times;</button></div>' +
        '<div class="dr-b" id="dr-b"></div>' +
        '<div class="dr-f">' + (WEBMASTER ? '<button class="btn sec" id="dr-hapus" type="button" style="margin-right:auto;color:var(--bad-fg,#b91c1c)">Hapus resi</button>' : '') + '<button class="btn sec" id="dr-tutup" type="button">Tutup</button><button class="btn" id="dr-cek" type="button">Cek resi ini</button></div></aside>';
    var $o = function (s) { return OV.querySelector(s); };

    var STATE = { q: '', f: { progres: [], hasil: [], picker: [], marketplace: [], toko: [] }, page: 1, per: 10, start: '', end: '' };
    var seq = 0, opsiTerakhir = '';
    var PCL = { 'Selesai dicek': 'green', 'Menunggu packer': 'ind', 'Belum dicek GD-BR': 'amber' };
    var OPSI = {
        progres: { label: 'Progres', list: ['Belum dicek GD-BR', 'Menunggu packer', 'Selesai dicek'], dot: { 'Belum dicek GD-BR': 'var(--warn-dot)', 'Menunggu packer': 'var(--primary)', 'Selesai dicek': 'var(--ok-dot)' } },
        hasil: { label: 'Hasil cek', list: ['Sesuai', 'Kurang', 'Lebih', 'Salah', 'Lainnya', 'Belum dicek'], dot: { 'Sesuai': 'var(--ok-dot)', 'Kurang': 'var(--bad-dot)', 'Lebih': 'var(--warn-dot)', 'Salah': 'var(--info-fg)', 'Lainnya': 'var(--primary)', 'Belum dicek': 'var(--faint)' } },
        picker: { label: 'Picker', list: [] },
        marketplace: { label: 'Marketplace', list: [] },
        toko: { label: 'Toko', list: [] }
    };

    // ───── filter ─────
    function bangunFilter() {
        $('#filters').innerHTML = Object.keys(OPSI).map(function (k) {
            var o = OPSI[k], aktif = STATE.f[k];
            return '<div class="filter' + (aktif.length ? ' aktif' : '') + '" data-key="' + k + '"><button type="button" class="chip" data-ft="1"><span>' + o.label + '</span><span class="cnt">' + aktif.length + '</span>' + ic(IC.down) + '</button><div class="pop">' +
                o.list.map(function (v) { return '<label class="opt"><input type="checkbox" value="' + esc(v) + '"' + (aktif.indexOf(v) >= 0 ? ' checked' : '') + '>' + (o.dot ? '<i class="dot" style="background:' + o.dot[v] + '"></i>' : '') + '<span>' + esc(v) + '</span></label>'; }).join('') +
                '<div class="pop-foot"><button type="button" data-clr="1">Hapus pilihan</button></div></div></div>';
        }).join('');
    }
    $('#filters').addEventListener('click', function (e) {
        var f = e.target.closest('.filter'); if (!f) return;
        if (e.target.closest('[data-ft]')) { e.stopPropagation(); $$('.filter').forEach(function (x) { if (x !== f) x.classList.remove('open'); }); f.classList.toggle('open'); }
        else if (e.target.closest('[data-clr]')) { $$('input', f).forEach(function (i) { i.checked = false; }); syncFilter(f); }
    });
    $('#filters').addEventListener('change', function (e) { var f = e.target.closest('.filter'); if (f) syncFilter(f); });
    jQuery(document).off('click.gdbrfil').on('click.gdbrfil', function (e) { if (!jQuery(e.target).closest('.filter').length) $$('.filter').forEach(function (x) { x.classList.remove('open'); }); });
    function syncFilter(f) {
        var v = $$('input:checked', f).map(function (i) { return i.value; });
        STATE.f[f.dataset.key] = v; f.classList.toggle('aktif', v.length > 0); $('.cnt', f).textContent = v.length;
        STATE.page = 1; muat();
    }
    $('#f-reset').addEventListener('click', function () {
        STATE.f = { progres: [], hasil: [], picker: [], marketplace: [], toko: [] }; STATE.page = 1;
        bangunFilter(); muat();
    });
    var tm; $('#q').addEventListener('input', function () { var v = this.value; clearTimeout(tm); tm = setTimeout(function () { STATE.q = v.trim(); STATE.page = 1; muat(); }, 300); });
    $('#per').addEventListener('change', function () { STATE.per = +this.value; STATE.page = 1; muat(); });

    // ───── periode (daterangepicker, bawaan Hari Ini) ─────
    STATE.start = moment().format('YYYY-MM-DD'); STATE.end = moment().format('YYYY-MM-DD');
    function labelPeriode() { return moment(STATE.start).format('DD MMM YYYY') + ' – ' + moment(STATE.end).format('DD MMM YYYY'); }
    $('#reportrange').value = labelPeriode();
    jQuery('#reportrange').daterangepicker({
        locale: { format: 'DD MMM YYYY' },
        startDate: moment(STATE.start), endDate: moment(STATE.end),
        ranges: {
            'Hari Ini': [moment(), moment()],
            'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
            'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function (s, e) { STATE.start = s.format('YYYY-MM-DD'); STATE.end = e.format('YYYY-MM-DD'); STATE.page = 1; $('#reportrange').value = labelPeriode(); muat(); });

    // ───── export: kirim periode & filter aktif lewat form POST ke iframe tersembunyi ─────
    $('#btn-export').addEventListener('click', function () {
        var d = { start: STATE.start, end: STATE.end, q: STATE.q };
        var $f = jQuery('<form method="post" class="nojs" action="gdbr/rekap-export" target="gdbr-unduh" style="display:none"></form>');
        jQuery.each(d, function (k, v) { $f.append(jQuery('<input type="hidden">').attr('name', k).val(v)); });
        jQuery.each(STATE.f, function (k, arr) { jQuery.each(arr, function (i, x) { $f.append(jQuery('<input type="hidden">').attr('name', 'f_' + k + '[]').val(x)); }); });
        if (!document.getElementById('gdbr-unduh')) jQuery('body').append('<iframe name="gdbr-unduh" id="gdbr-unduh" style="display:none"></iframe>');
        $f.appendTo('body').submit();
        setTimeout(function () { $f.remove(); }, 2000);
        U.toast('Menyiapkan file sesuai periode dan filter yang aktif...');
    });

    // ───── muat data ─────
    function muat() {
        var mine = ++seq;
        var d = { start: STATE.start, end: STATE.end, q: STATE.q, halaman: STATE.page, per: STATE.per };
        jQuery.each(STATE.f, function (k, v) { d['f_' + k] = v; });
        U.post(U.URL.rekap_data, d).then(function (res) {
            if (mine !== seq) return;
            if (!res || res.code !== 200 || !res.data) { $('#rows').innerHTML = '<tr><td colspan="8"><div class="empty">' + esc(res && res.message ? res.message : 'Gagal memuat data.') + '</div></td></tr>'; return; }
            tampilData(res.data);
        }).catch(function (x) {
            if (mine === seq) $('#rows').innerHTML = '<tr><td colspan="8"><div class="empty">' + esc(U.pesanGagal(x)) + '</div></td></tr>';
        });
    }

    function cekCell(c) {
        if (!c) return '<span class="muted">Belum dicek</span>';
        return '<div class="duo">' + U.badges(c) + '<span class="person"><i class="av">' + esc(U.ini(c.nama)) + '</i>' + esc(c.label) + '</span><span class="sub">' + fmtD(c.waktu) + '</span></div>';
    }
    function tampilData(D) {
        // pilihan filter dinamis (picker, marketplace, toko) mengikuti data periode
        var baru = JSON.stringify(D.opsi);
        if (baru !== opsiTerakhir) {
            opsiTerakhir = baru;
            OPSI.picker.list = D.opsi.picker; OPSI.marketplace.list = D.opsi.marketplace; OPSI.toko.list = D.opsi.toko;
            bangunFilter();
        }
        var K = D.kpi, pct = K.dicek ? Math.round(K.selisih / K.dicek * 100) : 0;
        var kartu = [
            ['ind', '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>', 'Total Resi GD-BR', K.total, 'ditandai tim resi pada periode ini'],
            ['amber', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', 'Belum Dicek GD-BR', K.belum, 'sudah ditandai tim resi, belum dicek'],
            ['ind', '<path d="M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8"/>', 'Menunggu Packer', K.tunggu, 'sudah dicek gedung baru, belum dicek packer'],
            ['red', '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/>', 'Ada Selisih', K.selisih, pct + '% dari ' + K.dicek + ' resi yang sudah dicek']
        ];
        $('#kpis').innerHTML = kartu.map(function (k) { return '<div class="kpi"><div class="top2"><span class="kic ' + k[0] + '">' + ic(k[1]) + '</span><span class="kl">' + k[2] + '</span></div><div class="kv">' + k[3] + '</div><div class="kn">' + k[4] + '</div></div>'; }).join('');

        var adaFilter = Object.keys(STATE.f).some(function (k) { return STATE.f[k].length; });
        $('#f-reset').classList.toggle('hide', !adaFilter);

        $('#rows').innerHTML = D.rows.length ? D.rows.map(function (r) {
            return '<tr data-resi="' + esc(r.noresi) + '"><td class="cb-cell"><input type="checkbox" class="cb-row" value="' + esc(r.noresi) + '"' + (PILIH[r.noresi] ? ' checked' : '') + ' aria-label="Pilih resi"></td><td><span class="resi">' + esc(r.noresi) + U.cp(r.noresi) + '</span><span class="sub">Pesanan ' + esc(r.pesanan) + U.cp(r.pesanan) + '</span><span class="sub">' + [r.toko, r.mp].filter(Boolean).map(esc).join(' · ') + '</span><span class="sub">' + r.n_sku + ' SKU · ' + r.qty + ' pcs</span></td>' +
                '<td>' + (r.picker ? '<span class="person"><i class="av">' + esc(U.ini(r.picker.nama)) + '</i>' + esc(r.picker.label) + '</span><span class="sub">' + fmtD(r.picker.waktu) + '</span>' : '<span class="muted">–</span>') + '</td>' +
                '<td><span class="person"><i class="av">' + esc(U.ini(r.tandai.nama)) + '</i>' + esc(r.tandai.label) + '</span><span class="sub">' + fmtD(r.tandai.waktu) + '</span></td>' +
                '<td>' + cekCell(r.gb) + '</td><td>' + cekCell(r.pk) + '</td><td><span class="pill ' + PCL[r.progres] + '">' + esc(r.progres) + '</span></td>' +
                '<td><button type="button" class="icon-btn" aria-label="Lihat detail">' + ic(IC.eye) + '</button></td></tr>';
        }).join('') : '<tr><td colspan="8"><div class="empty">Tidak ada resi pada periode &amp; filter ini</div></td></tr>';

        syncCbAll();
        var st = (D.halaman - 1) * D.per, pages = Math.max(1, Math.ceil(D.total / D.per));
        $('#info').textContent = D.total ? 'Menampilkan ' + (st + 1) + '–' + Math.min(st + D.per, D.total) + ' dari ' + D.total + ' resi' : 'Menampilkan 0 resi';
        var b = '<button type="button" data-p="' + (D.halaman - 1) + '"' + (D.halaman === 1 ? ' disabled' : '') + ' aria-label="Sebelumnya">&lsaquo;</button>';
        var dari = Math.max(1, D.halaman - 3), sampai = Math.min(pages, dari + 6);
        for (var i = dari; i <= sampai; i++) b += '<button type="button" data-p="' + i + '"' + (i === D.halaman ? ' class="on"' : '') + '>' + i + '</button>';
        b += '<button type="button" data-p="' + (D.halaman + 1) + '"' + (D.halaman === pages ? ' disabled' : '') + ' aria-label="Berikutnya">&rsaquo;</button>';
        $('#pg').innerHTML = b;
    }
    $('#pg').addEventListener('click', function (e) { var b = e.target.closest('[data-p]'); if (b && !b.disabled) { STATE.page = +b.dataset.p; muat(); } });
    // ───── checkbox pilih baris (pilihan bertahan antar halaman) ─────
    var PILIH = {};
    function syncCbAll() {
        var cbs = $$('.cb-row'), all = $('#cb-all'), n = cbs.filter(function (c) { return c.checked; }).length;
        all.checked = cbs.length > 0 && n === cbs.length;
        all.indeterminate = n > 0 && n < cbs.length;
    }
    $('#cb-all').addEventListener('change', function () {
        var on = this.checked;
        $$('.cb-row').forEach(function (c) { c.checked = on; if (on) PILIH[c.value] = 1; else delete PILIH[c.value]; });
        syncCbAll();
    });
    $('#rows').addEventListener('change', function (e) {
        var c = e.target.closest('.cb-row'); if (!c) return;
        if (c.checked) PILIH[c.value] = 1; else delete PILIH[c.value];
        syncCbAll();
    });
    $('#rows').addEventListener('click', function (e) {
        if (e.target.closest('[data-copy]') || e.target.closest('.cb-cell')) return;
        var tr = e.target.closest('tr[data-resi]'); if (tr) bukaDrawer(tr.dataset.resi);
    });

    // ───── drawer detail ─────
    var drResi = null, drProgres = null;
    function langkah(judul, c, tunggu, tahap) {
        if (!c) return '<li><span class="nd"></span><div class="tt" style="color:var(--muted)">' + judul + ' <span class="b slate">BELUM</span></div><div class="tp muted">' + tunggu + '</div></li>';
        return '<li class="' + (c.sesuai ? 'done' : 'warn') + '"><span class="nd">' + (c.sesuai ? '✓' : '!') + '</span><div class="tt">' + judul + ' ' + U.badges(c) +
            (WEBMASTER ? ' <button type="button" class="btn sec" data-ubah="' + tahap + '" style="padding:2px 10px;font-size:12px">Ubah</button>' : '') + '</div><div class="tp">' + esc(c.label) +
            ' <span style="color:var(--muted)"><br>Waktu cek: ' + fmtD(c.waktu) + '</span></div>' + (c.catatan ? '<div class="tn">' + esc(c.catatan) + '</div>' : '') + '</li>';
    }
    var drDetail = null;
    // Form ubah hasil cek (webmaster): qty ada per SKU, kotak "masalah lain", catatan
    function bukaUbah(tahap) {
        var r = drDetail, k = tahap === 'GEDUNG_BARU' ? 'gb' : 'pk', items = r['edit_' + k], c = r[k];
        if (!items) return;
        var baris = items.map(function (it) {
            return '<tr><td><b>' + esc(it.sku) + '</b>' + (it.luar ? ' <span class="b info">DI LUAR RESI</span>' : '') + '</td><td>' + it.qty_resi + '</td>' +
                '<td><input type="number" min="' + (it.luar ? 1 : 0) + '" max="99999" value="' + it.qty_ada + '" data-item="' + it.id_item + '" style="width:80px"></td></tr>';
        }).join('');
        var no = r.noresi;
        U.modal('<h4 style="margin:0 0 10px">Ubah cek ' + (k === 'gb' ? 'Gedung Baru' : 'Packer') + ' — ' + esc(no) + '</h4>' +
            '<div class="tw2"><table class="cmp"><thead><tr><th>SKU</th><th>Resi</th><th>Qty ada</th></tr></thead><tbody>' + baris + '</tbody></table></div>' +
            '<label style="display:block;margin:10px 0"><input type="checkbox" id="ub-lain"' + (c.lain ? ' checked' : '') + '> Ada masalah lain (rusak, cacat, kemasan)</label>' +
            '<textarea id="ub-cat" maxlength="255" rows="2" placeholder="Catatan (wajib bila ada yang tidak sesuai)" style="width:100%">' + esc(c.catatan || '') + '</textarea>', 'Simpan perubahan', function () {
            var o = U.ovl(), data = [];
            o.querySelectorAll('input[data-item]').forEach(function (i) { data.push({ id_item: +i.dataset.item, qty_ada: i.value }); });
            return U.post(U.URL.ubah_cek, { noresi: no, tahap: tahap, items: JSON.stringify(data), lain: o.querySelector('#ub-lain').checked ? 1 : 0, catatan: o.querySelector('#ub-cat').value })
                .then(function (res) {
                    if (res && res.code === 200) { U.toast('Hasil cek diperbarui.'); bukaDrawer(no); muat(); return ''; }
                    return esc(res && res.message ? res.message : 'Gagal menyimpan.');
                }, function (x) { return esc(U.pesanGagal(x)); });
        });
    }
    function bukaDrawer(no) {
        U.post(U.URL.detail, { noresi: no }).then(function (res) {
            var r = res && res.data && res.data.detail;
            if (!r) { U.toast(res && res.message ? res.message : 'Detail tidak ditemukan.'); return; }
            drResi = r.noresi; drProgres = r.progres; drDetail = r;
            $o('#dr-title').innerHTML = esc(r.noresi) + U.cp(r.noresi);
            $o('#dr-meta').textContent = [r.toko, r.mp, r.progres].filter(Boolean).join(' · ');
            var cell = function (v, q) { if (v == null) return '<td class="muted">–</td>'; return '<td class="' + (v === q ? 'same' : 'diff') + '">' + v + '</td>'; };
            var rows = r.baris.map(function (b) {
                return '<tr><td><div class="skucell">' + U.thumb(b, true) + '<span><b>' + esc(b.sku) + '</b>' + (b.luar ? ' <span class="b info">DI LUAR RESI</span>' : '') + '<div class="sub">rak ' + esc(b.no_rak) + '</div></span></div></td><td>' + b.resi + '</td>' + cell(b.gb, b.resi) + cell(b.pk, b.resi) + '</tr>';
            }).join('');
            $o('#dr-b').innerHTML =
                '<div class="dr-sec"><h5>Pesanan</h5><dl style="margin:0"><div class="dr-row"><dt>No. Pesanan</dt><dd class="num">' + esc(r.pesanan) + U.cp(r.pesanan) + '</dd></div><div class="dr-row"><dt>Toko</dt><dd>' + (esc(r.toko) || '–') + '</dd></div><div class="dr-row"><dt>Marketplace</dt><dd>' + (esc(r.mp) || '–') + '</dd></div></dl></div>' +
                '<div class="dr-sec"><h5>Alur pengecekan</h5><ol class="tl">' +
                '<li class="done"><span class="nd">✓</span><div class="tt">Diambil picker</div><div class="tp">' + (r.picker ? esc(r.picker.label) + ' <span style="color:var(--muted)">· ' + fmtD(r.picker.waktu) + '</span>' : '–') + '</div></li>' +
                '<li class="done"><span class="nd">✓</span><div class="tt">Ditandai tim resi</div><div class="tp">' + esc(r.tandai.label) + ' <span style="color:var(--muted)">· ' + fmtD(r.tandai.waktu) + '</span></div></li>' +
                langkah('Cek Gedung Baru', r.gb, 'Menunggu pengecekan di gedung baru', 'GEDUNG_BARU') + langkah('Cek Packer', r.pk, 'Menunggu packer sebelum dipacking', 'PACKER') + '</ol></div>' +
                '<div class="dr-sec"><h5>Perbandingan qty</h5><div class="tw2"><table class="cmp"><thead><tr><th>SKU</th><th>Resi</th><th>Gedung Baru</th><th>Cek Packer</th></tr></thead><tbody>' + rows + '</tbody></table></div></div>';
            var next = !r.gb ? 'GEDUNG_BARU' : !r.pk ? 'PACKER' : null;
            var nm = { GEDUNG_BARU: 'GEDUNG BARU', PACKER: 'PACKER' };
            var b = $o('#dr-cek'); b.classList.toggle('hide', !next); b.dataset.s = next || '';
            b.textContent = next ? 'Cek resi ini (' + nm[next] + ')' : '';
            $o('#drawer').classList.add('on'); $o('#scrim').classList.add('on'); $o('#drawer').setAttribute('aria-hidden', 'false');
        }).catch(function (x) { U.toast(U.pesanGagal(x)); });
    }
    function tutupDrawer() { $o('#drawer').classList.remove('on'); $o('#scrim').classList.remove('on'); $o('#drawer').setAttribute('aria-hidden', 'true'); }
    $o('#scrim').addEventListener('click', tutupDrawer); $o('#dr-x').addEventListener('click', tutupDrawer); $o('#dr-tutup').addEventListener('click', tutupDrawer);
    jQuery(document).off('keydown.gdbrrk').on('keydown.gdbrrk', function (e) { if (e.key === 'Escape' && document.getElementById('drawer')) tutupDrawer(); });
    $o('#dr-b').addEventListener('click', function (e) {
        var b = e.target.closest('[data-ubah]'); if (b) bukaUbah(b.dataset.ubah);
    });
    if (WEBMASTER) $o('#dr-hapus').addEventListener('click', function () {
        var no = drResi;
        U.modal('<h4 style="margin:0 0 10px">Hapus resi ' + esc(no) + '?</h4><p>Resi dikeluarkan dari data GD-BR (Rekap dan scan). Data tidak dihapus permanen, tetapi resi ini tidak bisa discan ulang.</p>', 'Hapus resi', function () {
            return U.post(U.URL.hapus_resi, { noresi: no }).then(function (res) {
                if (res && res.code === 200) { U.toast('Resi dihapus dari data GD-BR.'); tutupDrawer(); muat(); return ''; }
                return esc(res && res.message ? res.message : 'Gagal menghapus.');
            }, function (x) { return esc(U.pesanGagal(x)); });
        });
    });
    $o('#dr-cek').addEventListener('click', function () {
        var s = this.dataset.s, no = drResi;
        try { sessionStorage.setItem('gdbr_isi', JSON.stringify({ tahap: s, resi: no })); } catch (e) {}
        tutupDrawer();
        // Navigasi SPA lewat penangan a.link di body (devScript tidak global)
        var tautan = jQuery('.x-navigation a.link[href="gdbr/scan"]').first();
        if (tautan.length) {
            tautan.trigger('click');
        } else {
            var a = jQuery('<a class="link" href="gdbr/scan" style="display:none">Scan Resi GD-BR</a>').appendTo('body');
            a.trigger('click'); a.remove();
        }
    });

    bangunFilter();
    muat();
})();
</script>
