<?php $BASE = rtrim(base_url(), '/') . '/'; ?>
<style>
#lrs_wrap { --lrs-hijau:#0b6b5e; --lrs-teks:#1c2a33; --lrs-redup:#4b5d68; --lrs-garis:#e3e8ec;
            max-width:1100px; margin:0 auto; padding:16px 20px 40px; color:var(--lrs-teks); }
#lrs_wrap * { box-sizing:border-box; }
#lrs_wrap button { font-family:inherit; }
#lrs_wrap svg { flex:none; width:18px; height:18px; max-width:none; }
#lrs_wrap .lrs-tip { position:relative; display:inline-flex; align-self:flex-end; height:44px; align-items:center; margin-left:auto; }
#lrs_wrap .lrs-tip-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; padding:0; border:0; border-radius:16px; background:transparent; color:#6b7c87; cursor:help; }
#lrs_wrap .lrs-tip-btn:hover, #lrs_wrap .lrs-tip-btn:focus { background:#e9eef1; color:var(--lrs-hijau); outline:none; }
#lrs_wrap .lrs-tip-btn svg { width:20px; height:20px; }
#lrs_wrap .lrs-tip-box { display:none; position:absolute; top:46px; right:0; z-index:40; width:300px; padding:12px 14px; border-radius:10px; background:#12212b; color:#fff; font-size:13px; line-height:1.5; box-shadow:0 8px 24px rgba(18,33,43,.18); }
#lrs_wrap .lrs-tip:hover .lrs-tip-box, #lrs_wrap .lrs-tip:focus-within .lrs-tip-box { display:block; }

/* Filter */
#lrs_wrap .lrs-filter { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; margin-bottom:16px; }
#lrs_wrap .lrs-fgrup { position:relative; display:flex; flex-direction:column; gap:6px; }
#lrs_wrap .lrs-flabel { font-size:12px; font-weight:600; color:var(--lrs-redup); }
#lrs_wrap .lrs-input { display:inline-flex; align-items:center; gap:10px; min-height:44px; padding:0 14px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:var(--lrs-teks); font-size:14px; font-weight:500; cursor:pointer; }
#lrs_wrap .lrs-input:hover { border-color:#9fb0ba; }
#lrs_wrap .lrs-input:focus-visible, #lrs_wrap .lrs-input.buka { border-color:var(--lrs-hijau); box-shadow:0 0 0 3px rgba(11,107,94,.15); outline:none; }
#lrs_wrap select.lrs-input { width:96px; height:44px; padding:0 10px; }
#lrs_wrap .lrs-lapis { position:fixed; top:0; left:0; right:0; bottom:0; z-index:29; border:0; background:transparent; cursor:default; padding:0; }
#lrs_wrap .lrs-pop { position:absolute; top:76px; left:0; z-index:30; background:#fff; border:1px solid #dce3e8; border-radius:14px; box-shadow:0 12px 32px rgba(18,33,43,.16); }

/* Pemilih tanggal: pilihan cepat | kalender */
#lrs_wrap .lrs-dp { width:580px; max-width:calc(100vw - 32px); display:flex; flex-wrap:wrap; }
#lrs_wrap .lrs-dp-kiri { flex:0 0 176px; display:flex; flex-direction:column; gap:4px; padding:16px 12px; border-right:1px solid var(--lrs-garis); }
#lrs_wrap .lrs-dp-kiri button { min-height:44px; padding:0 12px; border:0; border-radius:8px; background:transparent; color:var(--lrs-teks); font-size:14px; font-weight:500; text-align:left; cursor:pointer; }
#lrs_wrap .lrs-dp-kiri button:hover { background:#f3f6f8; }
#lrs_wrap .lrs-dp-kiri button.on { background:#e3f1ee; color:#0b4f45; font-weight:600; }
#lrs_wrap .lrs-dp-kanan { flex:1 1 340px; min-width:0; display:flex; flex-direction:column; gap:14px; padding:16px; }
#lrs_wrap .lrs-dp-isi { display:flex; align-items:center; gap:8px; }
#lrs_wrap .lrs-dp-isi .k { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; padding:8px 12px; border-radius:10px; border:2px solid #dce3e8; background:#fff; }
#lrs_wrap .lrs-dp-isi .k.aktif { border-color:var(--lrs-hijau); }
#lrs_wrap .lrs-dp-isi .k span:first-child { font-size:11px; font-weight:600; color:#6b7c87; }
#lrs_wrap .lrs-dp-isi .k span:last-child { font-size:14px; font-weight:600; font-variant-numeric:tabular-nums; }
#lrs_wrap .lrs-kal-kepala { display:flex; align-items:center; justify-content:space-between; height:44px; font-size:14px; font-weight:700; }
#lrs_wrap .lrs-ikon-btn { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border:0; border-radius:10px; background:#fff; color:var(--lrs-teks); cursor:pointer; padding:0; }
#lrs_wrap .lrs-ikon-btn:hover:not(:disabled) { background:#e9eef1; }
#lrs_wrap .lrs-ikon-btn:disabled { opacity:.4; cursor:not-allowed; }
#lrs_wrap .lrs-hari7 { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); height:32px; align-items:center; text-align:center; font-size:12px; font-weight:600; color:#6b7c87; }
#lrs_wrap .lrs-minggu { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); }
#lrs_wrap .lrs-tgl { height:40px; width:100%; border:0; border-radius:20px; background:transparent; color:var(--lrs-teks); font-size:13px; cursor:pointer; padding:0; }
#lrs_wrap button.lrs-tgl:hover { background:#e9eef1; }
#lrs_wrap .lrs-tgl.range { background:#e3f1ee; color:#0b4f45; border-radius:0; }
#lrs_wrap .lrs-tgl.ujung, #lrs_wrap button.lrs-tgl.ujung:hover { background:var(--lrs-hijau); color:#fff; font-weight:700; }
#lrs_wrap .lrs-tgl.mati { display:flex; align-items:center; justify-content:center; color:#b4c0c7; cursor:default; }
#lrs_wrap .lrs-petunjuk { font-size:12px; color:#6b7c87; }

/* Tabel */
#lrs_wrap table { width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--lrs-garis); }
#lrs_wrap th, #lrs_wrap td { padding:10px 14px; border-bottom:1px solid var(--lrs-garis); font-size:14px; text-align:right; font-variant-numeric:tabular-nums; }
#lrs_wrap th:first-child, #lrs_wrap td:first-child { text-align:left; }
#lrs_wrap th { background:#f3f6f8; font-weight:700; }
#lrs_wrap .lrs-ok { color:var(--lrs-hijau); font-weight:700; }
#lrs_wrap .lrs-kurang { color:#b3261e; font-weight:700; }
#lrs_wrap .lrs-kosong { text-align:center !important; color:var(--lrs-redup); padding:24px; }
#lrs_wrap .lrs-bawah { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-top:12px; font-size:14px; color:var(--lrs-redup); }
#lrs_wrap .lrs-nav { display:flex; gap:8px; align-items:center; }
#lrs_wrap .lrs-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:0 18px; border:1px solid #c9d3d9; border-radius:10px; background:#fff; color:var(--lrs-teks); font-size:14px; font-weight:600; cursor:pointer; }
#lrs_wrap .lrs-btn:hover:not(:disabled) { background:#f3f6f8; }
#lrs_wrap .lrs-btn.utama { border:0; background:var(--lrs-hijau); color:#fff; }
#lrs_wrap .lrs-btn.utama:hover:not(:disabled) { background:#095a4f; }
#lrs_wrap .lrs-btn:disabled { opacity:.45; cursor:not-allowed; }
#lrs_wrap .lrs-err { color:#b3261e; margin-bottom:12px; }
</style>
<div id="lrs_wrap">
    <div class="lrs-filter">
        <div class="lrs-fgrup">
            <span class="lrs-flabel">Tanggal Scan Picker</span>
            <button type="button" class="lrs-input" id="lrs_tgl_btn" aria-label="Pilih rentang tanggal">
                <svg viewBox="0 0 24 24" fill="none" stroke="#6b7c87" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line><line x1="8" y1="3" x2="8" y2="7"></line><line x1="16" y1="3" x2="16" y2="7"></line></svg>
                <span id="lrs_tgl_label" style="font-variant-numeric:tabular-nums"></span>
            </button>
            <div id="lrs_tgl_pop"></div>
        </div>
        <div class="lrs-fgrup">
            <label class="lrs-flabel" for="lrs_size">Baris per halaman</label>
            <select id="lrs_size" class="lrs-input lrs-size"><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option></select>
        </div>
        <span class="lrs-tip">
            <button type="button" class="lrs-tip-btn" aria-label="Penjelasan laporan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="11" x2="12" y2="16"></line><circle cx="12" cy="8" r="0.6"></circle></svg>
            </button>
            <span class="lrs-tip-box">Per tanggal scan picker. 1 SKU 1 QTY = resi satuan; 1 SKU PICKER = yang discan sebagai resi spesial. Target minimal 50%.</span>
        </span>
        <button type="button" class="lrs-btn utama lrs-export" id="lrs_export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export Excel
        </button>
    </div>
    <div class="lrs-err" id="lrs_err" style="display:none"></div>
    <table>
        <thead><tr><th>Tanggal Scan Picker</th><th>1 SKU 1 QTY</th><th>1 SKU PICKER</th><th>Persen</th></tr></thead>
        <tbody id="lrs_body"><tr><td colspan="4" class="lrs-kosong">Memuat...</td></tr></tbody>
    </table>
    <div class="lrs-bawah">
        <span id="lrs_info"></span>
        <span class="lrs-nav">
            <button type="button" class="lrs-btn" id="lrs_prev">Sebelumnya</button>
            <span id="lrs_hal"></span>
            <button type="button" class="lrs-btn" id="lrs_next">Berikutnya</button>
        </span>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var HARI_INI = '<?= htmlspecialchars($end) ?>';
    var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_S = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var DAY = 86400000;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function utc(s) { var p = s.split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
    function iso(ms) { var d = new Date(ms); return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); }
    function fmt(s) { var p = s.split('-'); return p[2] + ' ' + BULAN_S[+p[1] - 1] + ' ' + p[0]; }
    function esc(t) { return String(t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function angka(n) { return Number(n).toLocaleString('id-ID'); }

    var T = utc(HARI_INI);
    var S = {
        from: '<?= htmlspecialchars($start) ?>', to: HARI_INI, page: 1, req: 0,
        dp: { open: false, dFrom: '', dTo: '', hover: '', vy: 0, vm: 0 }
    };

    var ICON_PREV = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 5 8 12 15 19"></polyline></svg>';
    var ICON_NEXT = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 5 16 12 9 19"></polyline></svg>';

    // ------------------------------------------------------------ muat data
    function galat(m) { $('#lrs_err').text(m).show(); $('#lrs_body').html('<tr><td colspan="4" class="lrs-kosong">Gagal memuat data.</td></tr>'); }

    function muat(page) {
        var id = ++S.req;
        $('#lrs_err').hide();
        $('#lrs_prev, #lrs_next').prop('disabled', true);
        $.ajax({
            url: BASE + 'laporan-resi-spesial/get-data', type: 'POST', dataType: 'text', timeout: 120000,
            data: { start_date: S.from, end_date: S.to, size: $('#lrs_size').val(), page: page }
        }).done(function (teks) {
            if (id !== S.req) return;
            var r;
            try { r = JSON.parse(teks); } catch (e) { return galat('Respons server bukan JSON: ' + teks.substring(0, 300)); }
            if (r.code !== 200) return galat(r.message || 'Gagal memuat data.');
            var d = r.data, h = '';
            S.page = d.page;
            var maks = Math.max(1, Math.ceil(d.total / d.size));
            if (!d.rows.length) h = '<tr><td colspan="4" class="lrs-kosong">Tidak ada scan picker 1 SKU 1 QTY pada rentang ini.</td></tr>';
            $.each(d.rows, function (i, x) {
                var kls = x.persen >= 50 ? 'lrs-ok' : 'lrs-kurang';
                h += '<tr><td>' + esc(fmt(x.tanggal)) + '</td><td>' + angka(x.satu_sku_satu_qty) + '</td><td>' + angka(x.satu_sku_picker)
                   + '</td><td class="' + kls + '">' + Number(x.persen).toFixed(1).replace('.', ',') + '%</td></tr>';
            });
            $('#lrs_body').html(h);
            var dari = d.total ? (d.page - 1) * d.size + 1 : 0, sampai = Math.min(d.total, d.page * d.size);
            $('#lrs_info').text('Menampilkan ' + dari + '-' + sampai + ' dari ' + d.total + ' tanggal');
            $('#lrs_hal').text('Halaman ' + S.page + ' / ' + maks);
            $('#lrs_prev').prop('disabled', S.page <= 1);
            $('#lrs_next').prop('disabled', S.page >= maks);
        }).fail(function (xhr) { if (id === S.req) galat('Gagal memuat (' + (xhr.status || 'tanpa respons') + ').'); });
    }

    // ------------------------------------------------------------ pemilih tanggal
    function rentangTeks(a, b) { return !a ? 'Pilih tanggal' : (!b || a === b ? fmt(a) : fmt(a) + ' – ' + fmt(b)); }
    function renderTglLabel() { $('#lrs_tgl_label').text(rentangTeks(S.from, S.to)); }

    function presetList() {
        var ty = new Date(T).getUTCFullYear(), tm = new Date(T).getUTCMonth();
        return [
            ['Hari ini', iso(T), iso(T)],
            ['Kemarin', iso(T - DAY), iso(T - DAY)],
            ['7 hari terakhir', iso(T - 6 * DAY), iso(T)],
            ['30 hari terakhir', iso(T - 29 * DAY), iso(T)],
            ['Bulan ini', iso(Date.UTC(ty, tm, 1)), iso(T)],
            ['Bulan lalu', iso(Date.UTC(ty, tm - 1, 1)), iso(Date.UTC(ty, tm, 0))]
        ];
    }

    function renderTglPop() {
        var dp = S.dp;
        $('#lrs_tgl_btn').toggleClass('buka', dp.open);
        if (!dp.open) { $('#lrs_tgl_pop').empty(); return; }
        var ty = new Date(T).getUTCFullYear(), tm = new Date(T).getUTCMonth();
        var memilihAkhir = !!dp.dFrom && !dp.dTo;
        var a = dp.dFrom || '', b = dp.dTo || (dp.dFrom && dp.hover ? dp.hover : '');
        if (b && b < a) { var t = a; a = b; b = t; }

        var h = '<button type="button" class="lrs-lapis" data-act="tgl-tutup" aria-label="Tutup pemilih tanggal"></button>'
            + '<div class="lrs-pop lrs-dp"><div class="lrs-dp-kiri">';
        presetList().forEach(function (p, i) {
            h += '<button type="button" data-preset="' + i + '"' + (dp.dFrom === p[1] && dp.dTo === p[2] ? ' class="on"' : '') + '>' + p[0] + '</button>';
        });
        h += '</div><div class="lrs-dp-kanan"><div class="lrs-dp-isi">'
            + '<div class="k' + (memilihAkhir ? '' : ' aktif') + '"><span>Tanggal awal</span><span>' + (dp.dFrom ? fmt(dp.dFrom) : '—') + '</span></div><span style="color:#6b7c87">–</span>'
            + '<div class="k' + (memilihAkhir ? ' aktif' : '') + '"><span>Tanggal akhir</span><span>' + (dp.dTo ? fmt(dp.dTo) : (memilihAkhir && dp.hover ? fmt(b) : '—')) + '</span></div></div>';

        // satu bulan kalender, minggu mulai Senin
        var y = dp.vy, m = dp.vm;
        var lead = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7;
        var jumlah = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
        var nextOff = (y * 12 + m) >= (ty * 12 + tm);
        h += '<div data-act="tgl-keluar"><div class="lrs-kal-kepala">'
            + '<button type="button" class="lrs-ikon-btn" data-act="bln-prev" aria-label="Bulan sebelumnya">' + ICON_PREV + '</button>'
            + '<span>' + BULAN[m] + ' ' + y + '</span>'
            + '<button type="button" class="lrs-ikon-btn" data-act="bln-next" aria-label="Bulan berikutnya"' + (nextOff ? ' disabled' : '') + '>' + ICON_NEXT + '</button></div>'
            + '<div class="lrs-hari7"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>';
        var sel = [], i;
        for (i = 0; i < lead; i++) sel.push('<span style="height:40px"></span>');
        for (var d = 1; d <= jumlah; d++) {
            var s = y + '-' + pad(m + 1) + '-' + pad(d);
            if (s > HARI_INI) { sel.push('<span class="lrs-tgl mati">' + d + '</span>'); continue; }
            var kelas = 'lrs-tgl';
            if (s === a || s === b) kelas += ' ujung';
            else if (a && b && s > a && s < b) kelas += ' range';
            sel.push('<button type="button" class="' + kelas + '" data-tgl="' + s + '">' + d + '</button>');
        }
        while (sel.length % 7 !== 0) sel.push('<span style="height:40px"></span>');
        for (i = 0; i < sel.length; i += 7) h += '<div class="lrs-minggu">' + sel.slice(i, i + 7).join('') + '</div>';
        h += '</div><span class="lrs-petunjuk">' + (memilihAkhir ? 'Pilih tanggal akhir. Rentang langsung berlaku.' : 'Pilih tanggal awal, lalu tanggal akhir.') + '</span></div></div>';
        $('#lrs_tgl_pop').html(h);
    }

    function bukaTgl() {
        var td = new Date(utc(S.to));
        S.dp = { open: true, dFrom: S.from, dTo: S.to, hover: '', vy: td.getUTCFullYear(), vm: td.getUTCMonth() };
        renderTglPop();
    }
    function tutupTgl() { S.dp.open = false; S.dp.hover = ''; renderTglPop(); }
    function terapkanRentang(a, b) {
        S.from = a; S.to = b;
        S.dp.open = false; S.dp.hover = '';
        renderTglPop(); renderTglLabel(); muat(1);
    }

    $('#lrs_tgl_btn').on('click', function () { S.dp.open ? tutupTgl() : bukaTgl(); });
    $('#lrs_tgl_pop').on('click', '[data-act="tgl-tutup"]', tutupTgl);
    $('#lrs_tgl_pop').on('click', '[data-preset]', function () {
        var p = presetList()[+$(this).attr('data-preset')];
        terapkanRentang(p[1], p[2]);
    });
    $('#lrs_tgl_pop').on('click', '[data-act="bln-prev"]', function () {
        var dp = S.dp; if (dp.vm === 0) { dp.vy--; dp.vm = 11; } else dp.vm--; renderTglPop();
    });
    $('#lrs_tgl_pop').on('click', '[data-act="bln-next"]', function () {
        var dp = S.dp; if (dp.vm === 11) { dp.vy++; dp.vm = 0; } else dp.vm++; renderTglPop();
    });
    $('#lrs_tgl_pop').on('click', '[data-tgl]', function () {
        var s = $(this).attr('data-tgl'), dp = S.dp;
        if (!dp.dFrom || dp.dTo) { dp.dFrom = s; dp.dTo = ''; dp.hover = ''; renderTglPop(); return; }
        // tanggal akhir dipilih: rentang langsung berlaku dan popup menutup
        terapkanRentang(s < dp.dFrom ? s : dp.dFrom, s < dp.dFrom ? dp.dFrom : s);
    });
    // pratinjau rentang mengikuti kursor setelah tanggal awal dipilih
    $('#lrs_tgl_pop').on('mouseover', '[data-tgl]', function () {
        var dp = S.dp, s = $(this).attr('data-tgl');
        if (dp.dFrom && !dp.dTo && dp.hover !== s) { dp.hover = s; renderTglPop(); }
    });
    $('#lrs_tgl_pop').on('mouseleave', '[data-act="tgl-keluar"]', function () {
        if (S.dp.hover) { S.dp.hover = ''; renderTglPop(); }
    });
    $(document).off('keydown.lrs').on('keydown.lrs', function (e) { if (e.key === 'Escape' && S.dp.open) tutupTgl(); });

    $('#lrs_export').on('click', function () {
        window.location.href = BASE + 'laporan-resi-spesial/export?start_date=' + S.from + '&end_date=' + S.to;
    });
    $('#lrs_size').on('change', function () { muat(1); });
    $('#lrs_prev').on('click', function () { muat(S.page - 1); });
    $('#lrs_next').on('click', function () { muat(S.page + 1); });

    renderTglLabel();
    muat(1);
});
</script>
