<?php $BASE = rtrim(base_url(), '/') . '/'; ?>
<style>
#lrs_wrap { max-width:960px; margin:0 auto; padding:16px 20px 40px; color:#1c2a33; }
#lrs_wrap h1 { margin:0 0 4px; font-size:24px; font-weight:700; }
#lrs_wrap .lrs-sub { margin:0 0 16px; color:#4b5d68; font-size:14px; }
#lrs_wrap .lrs-filter { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:16px; }
#lrs_wrap .lrs-filter label { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:#4b5d68; }
#lrs_wrap input[type=date], #lrs_wrap select { height:40px; padding:0 10px; border:1px solid #c9d3d9; border-radius:8px; font-size:14px; background:#fff; }
#lrs_wrap button { height:40px; padding:0 18px; border:0; border-radius:8px; background:#0b6b5e; color:#fff; font-size:14px; font-weight:600; cursor:pointer; }
#lrs_wrap button:disabled { opacity:.45; cursor:not-allowed; }
#lrs_wrap table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e3e8ec; }
#lrs_wrap th, #lrs_wrap td { padding:10px 14px; border-bottom:1px solid #e3e8ec; font-size:14px; text-align:right; font-variant-numeric:tabular-nums; }
#lrs_wrap th:first-child, #lrs_wrap td:first-child { text-align:left; }
#lrs_wrap th { background:#f3f6f8; font-weight:700; }
#lrs_wrap .lrs-ok { color:#0b6b5e; font-weight:700; }
#lrs_wrap .lrs-kurang { color:#b3261e; font-weight:700; }
#lrs_wrap .lrs-kosong { text-align:center !important; color:#4b5d68; padding:24px; }
#lrs_wrap .lrs-bawah { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-top:12px; font-size:14px; color:#4b5d68; }
#lrs_wrap .lrs-nav { display:flex; gap:8px; align-items:center; }
#lrs_wrap .lrs-err { color:#b3261e; margin-bottom:12px; }
</style>
<div id="lrs_wrap">
    <h1>Laporan Resi Spesial</h1>
    <p class="lrs-sub">Per tanggal scan picker. 1 SKU 1 QTY = resi satuan; 1 SKU PICKER = yang discan sebagai resi spesial. Target minimal 50%.</p>
    <div class="lrs-filter">
        <label>Dari tanggal <input type="date" id="lrs_start" value="<?= htmlspecialchars($start) ?>"></label>
        <label>Sampai tanggal <input type="date" id="lrs_end" value="<?= htmlspecialchars($end) ?>"></label>
        <label>Baris per halaman
            <select id="lrs_size"><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option></select>
        </label>
        <button type="button" id="lrs_cari">Tampilkan</button>
    </div>
    <div class="lrs-err" id="lrs_err" style="display:none"></div>
    <table>
        <thead><tr><th>Tanggal Scan Picker</th><th>1 SKU 1 QTY</th><th>1 SKU PICKER</th><th>Persen</th></tr></thead>
        <tbody id="lrs_body"><tr><td colspan="4" class="lrs-kosong">Memuat...</td></tr></tbody>
    </table>
    <div class="lrs-bawah">
        <span id="lrs_info"></span>
        <span class="lrs-nav">
            <button type="button" id="lrs_prev">Sebelumnya</button>
            <span id="lrs_hal"></span>
            <button type="button" id="lrs_next">Berikutnya</button>
        </span>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var S = { page: 1, maks: 1, req: 0 };

    function esc(t) { return String(t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function angka(n) { return Number(n).toLocaleString('id-ID'); }
    function tgl(s) { var p = s.split('-'); return p[2] + ' ' + BULAN[+p[1] - 1] + ' ' + p[0]; }
    function galat(m) { $('#lrs_err').text(m).show(); $('#lrs_body').html('<tr><td colspan="4" class="lrs-kosong">Gagal memuat data.</td></tr>'); }

    function muat(page) {
        var id = ++S.req;
        $('#lrs_err').hide();
        $('#lrs_prev, #lrs_next').prop('disabled', true);
        $.ajax({
            url: BASE + 'laporan-resi-spesial/get-data', type: 'POST', dataType: 'text', timeout: 120000,
            data: { start_date: $('#lrs_start').val(), end_date: $('#lrs_end').val(), size: $('#lrs_size').val(), page: page }
        }).done(function (teks) {
            if (id !== S.req) return;
            var r;
            try { r = JSON.parse(teks); } catch (e) { return galat('Respons server bukan JSON: ' + teks.substring(0, 300)); }
            if (r.code !== 200) return galat(r.message || 'Gagal memuat data.');
            var d = r.data, h = '';
            S.page = d.page; S.maks = Math.max(1, Math.ceil(d.total / d.size));
            if (!d.rows.length) h = '<tr><td colspan="4" class="lrs-kosong">Tidak ada scan picker 1 SKU 1 QTY pada rentang ini.</td></tr>';
            $.each(d.rows, function (i, x) {
                var kls = x.persen >= 50 ? 'lrs-ok' : 'lrs-kurang';
                h += '<tr><td>' + esc(tgl(x.tanggal)) + '</td><td>' + angka(x.satu_sku_satu_qty) + '</td><td>' + angka(x.satu_sku_picker)
                   + '</td><td class="' + kls + '">' + String(x.persen).replace('.', ',') + '%</td></tr>';
            });
            $('#lrs_body').html(h);
            var dari = d.total ? (d.page - 1) * d.size + 1 : 0, sampai = Math.min(d.total, d.page * d.size);
            $('#lrs_info').text('Menampilkan ' + dari + '-' + sampai + ' dari ' + d.total + ' tanggal');
            $('#lrs_hal').text('Halaman ' + S.page + ' / ' + S.maks);
            $('#lrs_prev').prop('disabled', S.page <= 1);
            $('#lrs_next').prop('disabled', S.page >= S.maks);
        }).fail(function (xhr) { if (id === S.req) galat('Gagal memuat (' + (xhr.status || 'tanpa respons') + ').'); });
    }

    $('#lrs_cari').on('click', function () { muat(1); });
    $('#lrs_size').on('change', function () { muat(1); });
    $('#lrs_prev').on('click', function () { muat(S.page - 1); });
    $('#lrs_next').on('click', function () { muat(S.page + 1); });
    muat(1);
});
</script>
