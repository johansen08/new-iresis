<?php /* Stok Paket Cancel — prototipe read-only. Sumber: Cancel_terima_fcd::stok_paket_cancel(). Gaya mengikuti Manajemen Komplain CS New (kartu ringkasan, satu kartu data, drawer detail). */ ?>
<style>
.sk{--pri:#4f46e5;--pri-l:rgba(79,70,229,.1);--bd:#e2e8f0;--tx:#334155;--mut:#64748b;--bg:#f8fafc;font-family:'Inter','Segoe UI',system-ui,sans-serif;color:#1e293b;padding:4px 2px}
.sk,.sk *{box-sizing:border-box}
.sk button,.sk input,.sk select{font-family:inherit}
.sk-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;padding:4px 0 14px}
.sk-head h3{margin:0;font-size:18px;font-weight:700;color:#0f172a}
.sk-head small{display:block;color:var(--mut);font-size:12.5px;margin-top:2px}
.sk-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:var(--tx);font-size:13.5px;font-weight:600;cursor:pointer}
.sk-btn:hover{background:var(--bg)}
.sk-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.sk-kpi{border:1px solid var(--bd);border-radius:12px;padding:14px 16px 12px;background:#fff}
.sk-kpi-top{display:flex;align-items:center;gap:8px}
.sk-ic{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;font-size:14px}
.sk-ic.i{background:#eef2ff;color:#4338ca}.sk-ic.g{background:#ecfdf5;color:#047857}.sk-ic.a{background:#fffbeb;color:#b45309}.sk-ic.s{background:#f1f5f9;color:#475569}
.sk-kpi-l{font-size:13px;font-weight:600;color:var(--tx)}
.sk-kpi-v{margin-top:10px;font-size:28px;line-height:1.1;font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}
.sk-kpi-n{margin-top:4px;font-size:12px;color:var(--mut)}
.sk-body{margin-top:16px;padding:16px 18px 12px;background:#fff;border:1px solid var(--bd);border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.sk-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:4px 0 14px}
.sk-search{position:relative;flex:1 1 240px;max-width:340px}
.sk-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px}
.sk-search input{width:100%;height:38px;padding:0 12px 0 34px;border:1px solid #cbd5e1;border-radius:8px;font-size:13.5px;background:#fff;color:#0f172a}
.sk-search input:focus,.sk-sel:focus{outline:none;border-color:var(--pri);box-shadow:0 0 0 3px var(--pri-l)}
.sk-fil{position:relative}
.sk-chip{display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:var(--tx);font-size:13.5px;font-weight:500;cursor:pointer;white-space:nowrap}
.sk-chip:hover{background:var(--bg)}
.sk-chip .n{display:none;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:var(--pri);color:#fff;font-size:11px;font-weight:700;line-height:18px;text-align:center}
.sk-fil.on .sk-chip{border-color:#a5b4fc;background:#eef2ff;color:#3730a3}
.sk-fil.on .sk-chip .n{display:inline-block}
.sk-pop{display:none;position:absolute;z-index:50;top:calc(100% + 6px);left:0;min-width:200px;padding:6px;background:#fff;border:1px solid var(--bd);border-radius:10px;box-shadow:0 12px 28px rgba(15,23,42,.14)}
.sk-fil.open .sk-pop{display:block}
.sk-opt{display:flex;align-items:center;gap:9px;margin:0;padding:8px 10px;border-radius:6px;font-size:13.5px;font-weight:400;color:var(--tx);cursor:pointer}
.sk-opt:hover{background:var(--bg)}
.sk-opt input{margin:0;accent-color:var(--pri);width:15px;height:15px}
.sk-opt span{flex:1}
.sk-opt em{font-style:normal;color:var(--mut);font-size:12px}
.sk-sel{height:38px;padding:0 10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:13.5px;color:var(--tx)}
.sk-clear{border:0;background:transparent;color:var(--mut);font-size:13px;cursor:pointer;padding:0 6px}
.sk-clear:hover{color:var(--pri)}
.sk-tw{overflow-x:auto}
.sk-table{width:100%;border-collapse:separate;border-spacing:0}
.sk-table th{background:var(--bg);color:var(--mut);font-weight:600;text-transform:uppercase;font-size:11px;letter-spacing:.05em;padding:12px 14px;border-bottom:1px solid var(--bd);text-align:left;white-space:nowrap;user-select:none}
.sk-table th.s{cursor:pointer}.sk-table th.s:hover{color:var(--pri)}
.sk-table th .ar{opacity:.35;margin-left:4px}.sk-table th.cur .ar{opacity:1;color:var(--pri)}
.sk-table td{padding:11px 14px;border-bottom:1px solid #f1f5f9;font-size:13.5px;vertical-align:middle}
.sk-table tbody tr{cursor:pointer}.sk-table tbody tr:hover td{background:#f8fafc}
.sk-table .c{text-align:center}.sk-table .r{text-align:right}
.sk-th{width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--bd);background:#f1f5f9;display:block}
.sk-ph{width:40px;height:40px;border-radius:8px;border:1px dashed #cbd5e1;background:#f8fafc;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:15px}
.sk-sku{font-weight:700;color:#0f172a}
.sk-nm{color:var(--mut);font-size:12.5px;max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sk-qty{display:inline-block;min-width:44px;padding:4px 10px;border-radius:8px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:15px;font-variant-numeric:tabular-nums;text-align:center}
.sk-b{display:inline-block;padding:2px 9px;border-radius:999px;font-size:11.5px;font-weight:600}
.sk-b.a{background:#fffbeb;color:#b45309}.sk-b.g{background:#ecfdf5;color:#047857}.sk-b.s{background:#f1f5f9;color:#475569}.sk-b.r{background:#fef2f2;color:#b91c1c}.sk-b.i{background:#eef2ff;color:#3730a3}
.sk-foot{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;padding:12px 2px 4px;color:var(--mut);font-size:13px}
.sk-pg{display:flex;gap:4px;align-items:center}
.sk-pg button{min-width:34px;height:32px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:var(--tx);cursor:pointer;font-size:13px}
.sk-pg button.cur{background:var(--pri);border-color:var(--pri);color:#fff}
.sk-pg button:disabled{opacity:.4;cursor:not-allowed}
.sk-empty{padding:36px 10px;text-align:center;color:var(--mut)}
.sk-err{margin:10px 0;padding:10px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
/* drawer detail */
.sk-ov{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:1040;display:none}
.sk-dr{position:fixed;top:0;right:0;bottom:0;width:min(560px,100%);background:#fff;z-index:1041;box-shadow:-12px 0 32px rgba(15,23,42,.18);transform:translateX(100%);transition:transform .22s ease;display:flex;flex-direction:column}
.sk-dr.open{transform:none}
.sk-dr-h{display:flex;gap:12px;align-items:flex-start;padding:18px 20px;border-bottom:1px solid var(--bd)}
.sk-dr-h .t{flex:1;min-width:0}.sk-dr-h h4{margin:0;font-size:17px;font-weight:700;color:#0f172a;word-break:break-all}
.sk-dr-h .nm{color:var(--mut);font-size:13px;margin-top:2px}
.sk-x{border:0;background:transparent;font-size:22px;line-height:1;color:#64748b;cursor:pointer}
.sk-dr-b{padding:16px 20px;overflow-y:auto;flex:1}
.sk-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.sk-sum div{border:1px solid var(--bd);border-radius:10px;padding:10px 12px}
.sk-sum b{display:block;font-size:22px;color:#0f172a}.sk-sum span{font-size:12px;color:var(--mut)}
.sk-pk{border:1px solid var(--bd);border-radius:10px;padding:11px 13px;margin-bottom:10px}
.sk-pk-t{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}
.sk-pk-t b{font-size:14px;color:#0f172a;word-break:break-all}
.sk-pk-m{margin-top:6px;font-size:12.5px;color:var(--mut);display:flex;flex-wrap:wrap;gap:6px 14px}
.sk-pk-q{margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px}
.sk-note{margin:0 0 14px;padding:9px 12px;border-radius:8px;background:#eef2ff;color:#3730a3;font-size:12.5px}
@media(max-width:1199px){.sk-kpis{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.sk-kpis{gap:10px}.sk-kpi-v{font-size:22px}.sk-search{max-width:none}}
</style>

<div class="sk" id="sk_root">
  <div class="sk-head">
    <div>
      <h3>Stok Paket Cancel</h3>
      <small>Paket cancel yang sudah diterima tim retur dan belum selesai. Qty adalah barang <b>real hasil pengecekan fisik</b>, bukan SKU/qty yang tertulis di resi.</small>
    </div>
    <button type="button" class="sk-btn" id="sk_ref"><i class="fa fa-refresh"></i> Muat ulang</button>
  </div>

  <div class="sk-kpis">
    <div class="sk-kpi"><div class="sk-kpi-top"><span class="sk-ic i"><i class="fa fa-cubes"></i></span><span class="sk-kpi-l">Total Stok (Qty)</span></div><div class="sk-kpi-v" id="k_qty">&ndash;</div><div class="sk-kpi-n">barang real di tim retur</div></div>
    <div class="sk-kpi"><div class="sk-kpi-top"><span class="sk-ic g"><i class="fa fa-barcode"></i></span><span class="sk-kpi-l">Jumlah SKU</span></div><div class="sk-kpi-v" id="k_sku">&ndash;</div><div class="sk-kpi-n">SKU berbeda yang ada stoknya</div></div>
    <div class="sk-kpi"><div class="sk-kpi-top"><span class="sk-ic s"><i class="fa fa-inbox"></i></span><span class="sk-kpi-l">Paket Belum Selesai</span></div><div class="sk-kpi-v" id="k_pkt">&ndash;</div><div class="sk-kpi-n" id="k_pkt_n">&nbsp;</div></div>
    <div class="sk-kpi"><div class="sk-kpi-top"><span class="sk-ic a"><i class="fa fa-exchange"></i></span><span class="sk-kpi-l">SKU Beda dari Resi</span></div><div class="sk-kpi-v" id="k_beda">&ndash;</div><div class="sk-kpi-n">salah ambil / lebih / kurang ambil</div></div>
  </div>

  <div class="sk-body">
    <div class="sk-bar">
      <div class="sk-search"><i class="fa fa-search"></i><input id="sk_q" placeholder="Cari SKU, nama, rak, atau no. resi..." autocomplete="off"></div>
      <div class="sk-fil" id="sk_fa"><button type="button" class="sk-chip" data-tog><i class="fa fa-inbox"></i> Asal <span class="n" id="sk_fa_n"></span> <i class="fa fa-angle-down"></i></button><div class="sk-pop" id="sk_fa_p"></div></div>
      <select class="sk-sel" id="sk_fb" title="Bandingkan dengan isi resi">
        <option value="">Semua SKU</option>
        <option value="beda">Beda dari resi saja</option>
        <option value="sama">Sesuai resi saja</option>
      </select>
      <button type="button" class="sk-clear" id="sk_rs" style="display:none"><i class="fa fa-times"></i> Reset filter</button>
    </div>
    <div id="sk_msg"></div>
    <div class="sk-tw">
      <table class="sk-table">
        <thead><tr>
          <th style="width:64px"></th>
          <th class="s" data-s="sku">SKU<span class="ar">&#8645;</span></th>
          <th class="s" data-s="rak">Rak<span class="ar">&#8645;</span></th>
          <th class="s c" data-s="stok">Stok Real<span class="ar">&#8645;</span></th>
          <th class="s c" data-s="jumlah_paket">Paket<span class="ar">&#8645;</span></th>
          <th>Keterangan</th>
        </tr></thead>
        <tbody id="sk_tb"><tr><td colspan="6" class="sk-empty">Memuat data...</td></tr></tbody>
      </table>
    </div>
    <div class="sk-foot">
      <span id="sk_info"></span>
      <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <label style="margin:0;font-weight:400">Tampilkan <select class="sk-sel" style="height:32px" id="sk_ps"><option>10</option><option selected>20</option><option>50</option><option>100</option></select> baris</label>
        <span class="sk-pg" id="sk_pg"></span>
      </div>
    </div>
  </div>
</div>

<div class="sk-ov" id="sk_ov"></div>
<aside class="sk-dr" id="sk_dr" aria-hidden="true">
  <div class="sk-dr-h"><div id="sk_dh_img"></div><div class="t"><h4 id="sk_dh_sku"></h4><div class="nm" id="sk_dh_nm"></div></div><button type="button" class="sk-x" id="sk_dx" aria-label="Tutup">&times;</button></div>
  <div class="sk-dr-b" id="sk_db"></div>
</aside>

<script type="text/javascript">
(function () {
    'use strict';
    var URL_DATA = '<?= base_url('paket-cancel/stok-data') ?>';
    var ASAL = { PACKER: 'Packer', HO: 'HO', KURIR: 'Kurir', '': 'Tanpa asal' };
    var S = { rows: [], asal: {}, beda: '', q: '', sort: 'stok', dir: -1, page: 1, size: 20 };
    var $ = window.jQuery;

    function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function n(x) { return Number(x || 0).toLocaleString('id-ID'); }
    function tgl(s) { var p = String(s).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : s; }
    function g(id) { return document.getElementById(id); }

    function img(r, ukuran) {
        var src = r.foto || r.foto_lokal || '';
        if (!src) return '<div class="sk-ph"><i class="fa fa-image"></i></div>';
        return '<img class="sk-th" src="' + esc(src) + '"' + (r.foto && r.foto_lokal ? ' data-foto-lokal="' + esc(r.foto_lokal) + '"' : '') + ' alt="" loading="lazy">';
    }

    // Ringkasan perbedaan satu paket untuk SKU ini (resi vs real)
    function ket(p) {
        var out = [];
        if (p.qty_resi === 0) {
            out.push('<span class="sk-b r">Di luar resi</span>' + (p.pasangan ? ' <span class="sk-b s">pengganti ' + esc(p.pasangan) + '</span>' : ''));
        } else if (p.qty_ada > p.qty_resi) {
            out.push('<span class="sk-b a">Lebih ' + (p.qty_ada - p.qty_resi) + '</span>');
        } else if (p.qty_ada < p.qty_resi) {
            out.push('<span class="sk-b a">Kurang ' + (p.qty_resi - p.qty_ada) + '</span>');
        } else {
            out.push('<span class="sk-b g">Sesuai resi</span>');
        }
        return out.join(' ');
    }

    function cocok(r) {
        if (S.beda === 'beda' && !r.beda) return false;
        if (S.beda === 'sama' && r.beda) return false;
        var pakai = Object.keys(S.asal).filter(function (k) { return S.asal[k]; });
        if (pakai.length && !r.paket.some(function (p) { return S.asal[p.dari]; })) return false;
        if (S.q) {
            var q = S.q.toLowerCase();
            var gudang = (r.sku + ' ' + r.nama + ' ' + r.rak + ' ' + r.paket.map(function (p) { return p.noresi; }).join(' ')).toLowerCase();
            if (gudang.indexOf(q) < 0) return false;
        }
        return true;
    }

    function tersaring() {
        var d = S.dir, k = S.sort;
        return S.rows.filter(cocok).sort(function (a, b) {
            var x = a[k], y = b[k];
            if (typeof x === 'number' && typeof y === 'number') return (x - y) * d || a.sku.localeCompare(b.sku);
            return String(x).localeCompare(String(y), 'id', { numeric: true }) * d || a.sku.localeCompare(b.sku);
        });
    }

    function gambar() {
        var list = tersaring(), total = list.length;
        var hal = Math.max(1, Math.ceil(total / S.size));
        if (S.page > hal) S.page = hal;
        var mulai = (S.page - 1) * S.size;
        var potong = list.slice(mulai, mulai + S.size);
        var h = '';
        potong.forEach(function (r) {
            var asal = {};
            r.paket.forEach(function (p) { asal[p.dari || ''] = 1; });
            h += '<tr data-sku="' + esc(r.sku) + '">' +
                '<td>' + img(r) + '</td>' +
                '<td><div class="sk-sku">' + esc(r.sku) + '</div><div class="sk-nm" title="' + esc(r.nama) + '">' + esc(r.nama || '-') + '</div></td>' +
                '<td>' + esc(r.rak || '-') + '</td>' +
                '<td class="c"><span class="sk-qty">' + n(r.stok) + '</span></td>' +
                '<td class="c">' + n(r.jumlah_paket) + '</td>' +
                '<td>' + (r.beda ? '<span class="sk-b a">Beda dari resi</span> ' : '<span class="sk-b g">Sesuai resi</span> ') +
                Object.keys(asal).map(function (a) { return '<span class="sk-b s">' + esc(ASAL[a] || a) + '</span>'; }).join(' ') + '</td></tr>';
        });
        g('sk_tb').innerHTML = h || '<tr><td colspan="6" class="sk-empty">Tidak ada SKU yang cocok dengan filter.</td></tr>';
        var tot = list.reduce(function (a, r) { return a + r.stok; }, 0);
        g('sk_info').textContent = total ? ('Menampilkan ' + (mulai + 1) + '–' + (mulai + potong.length) + ' dari ' + n(total) + ' SKU · total ' + n(tot) + ' qty') : '0 SKU';
        // pagination ringkas
        var pg = '', btn = function (l, p, o) { return '<button type="button" data-p="' + p + '"' + (o && o.cur ? ' class="cur"' : '') + (o && o.off ? ' disabled' : '') + '>' + l + '</button>'; };
        if (hal > 1) {
            pg += btn('&lsaquo;', S.page - 1, { off: S.page <= 1 });
            var a = Math.max(1, S.page - 2), b = Math.min(hal, a + 4);
            a = Math.max(1, b - 4);
            for (var i = a; i <= b; i++) pg += btn(i, i, { cur: i === S.page });
            pg += btn('&rsaquo;', S.page + 1, { off: S.page >= hal });
        }
        g('sk_pg').innerHTML = pg;
        document.querySelectorAll('.sk-table th.s').forEach(function (th) {
            var on = th.dataset.s === S.sort;
            th.classList.toggle('cur', on);
            th.querySelector('.ar').innerHTML = on ? (S.dir > 0 ? '&uarr;' : '&darr;') : '&#8645;';
        });
        var adaFilter = S.q || S.beda || Object.keys(S.asal).some(function (k) { return S.asal[k]; });
        g('sk_rs').style.display = adaFilter ? '' : 'none';
    }

    function popAsal() {
        var cnt = {};
        S.rows.forEach(function (r) { var s = {}; r.paket.forEach(function (p) { s[p.dari || ''] = 1; }); Object.keys(s).forEach(function (k) { cnt[k] = (cnt[k] || 0) + 1; }); });
        var h = '';
        ['PACKER', 'HO', 'KURIR', ''].forEach(function (k) {
            if (!cnt[k]) return;
            h += '<label class="sk-opt"><input type="checkbox" data-asal="' + k + '"' + (S.asal[k] ? ' checked' : '') + '><span>' + ASAL[k] + '</span><em>' + cnt[k] + ' SKU</em></label>';
        });
        g('sk_fa_p').innerHTML = h || '<div class="sk-empty" style="padding:10px">Tidak ada data</div>';
        var on = Object.keys(S.asal).filter(function (k) { return S.asal[k]; }).length;
        g('sk_fa_n').textContent = on;
        g('sk_fa').classList.toggle('on', on > 0);
    }

    function ringkas(r) {
        g('k_qty').textContent = n(r.qty);
        g('k_sku').textContent = n(r.sku);
        g('k_pkt').textContent = n(r.paket_semua);
        g('k_pkt_n').textContent = r.paket_semua === r.paket ? 'semua masih punya barang' : n(r.paket) + ' paket masih punya barang fisik';
        g('k_beda').textContent = n(r.sku_beda);
    }

    function muat() {
        g('sk_msg').innerHTML = '';
        g('sk_tb').innerHTML = '<tr><td colspan="6" class="sk-empty">Memuat data...</td></tr>';
        $.ajax({ url: URL_DATA, type: 'POST', dataType: 'text', timeout: 120000 }).done(function (raw) {
            var res;
            try { res = JSON.parse(raw); } catch (e) {
                g('sk_msg').innerHTML = '<div class="sk-err">Balasan server bukan JSON: ' + esc(raw.substr(0, 300)) + '</div>';
                g('sk_tb').innerHTML = ''; return;
            }
            var d = res.data || {};
            if (!d.ok) { g('sk_msg').innerHTML = '<div class="sk-err">' + esc(res.message || 'Gagal memuat') + '</div>'; g('sk_tb').innerHTML = ''; return; }
            S.rows = d.rows || [];
            ringkas(d.ringkas);
            popAsal();
            gambar();
        }).fail(function (x) {
            g('sk_msg').innerHTML = '<div class="sk-err">Gagal memuat data (' + esc(x.status) + ')</div>';
            g('sk_tb').innerHTML = '';
        });
    }

    function buka(sku) {
        var r = S.rows.filter(function (x) { return x.sku === sku; })[0];
        if (!r) return;
        g('sk_dh_img').innerHTML = img(r);
        g('sk_dh_sku').textContent = r.sku;
        g('sk_dh_nm').textContent = r.nama || '';
        var resi = r.paket.reduce(function (a, p) { return a + p.qty_resi; }, 0);
        var h = '<div class="sk-sum"><div><b>' + n(r.stok) + '</b><span>Stok real</span></div><div><b>' + n(r.jumlah_paket) + '</b><span>Paket</span></div><div><b>' + esc(r.rak || '-') + '</b><span>Rak</span></div></div>';
        h += '<p class="sk-note">Stok dihitung dari barang yang benar-benar ditemukan saat pengecekan, dikurangi yang sudah dicatat kembali ke display. Angka "tertulis di resi" hanya pembanding.</p>';
        r.paket.forEach(function (p) {
            h += '<div class="sk-pk"><div class="sk-pk-t"><b>' + (p.noresi ? esc(p.noresi) : '(paket tanpa resi)') + '</b><span>' + ket(p) + '</span></div>' +
                '<div class="sk-pk-m"><span>Asal: ' + esc(ASAL[p.dari] || p.dari || '-') + '</span><span>Diterima: ' + esc(tgl(p.tgl)) + ' ' + esc(p.jam) + '</span>' +
                (p.dicetak ? '<span class="sk-b i">Serah terima dicetak</span>' : '') + '</div>' +
                '<div class="sk-pk-q"><span>Tertulis di resi: <b>' + p.qty_resi + '</b></span><span>&rarr;</span><span>Real ada: <b>' + p.qty_ada + '</b></span>' +
                (p.kembali ? '<span>− kembali ke display <b>' + p.kembali + '</b></span>' : '') +
                '<span class="sk-qty" style="min-width:0;padding:2px 9px;font-size:13px">stok ' + p.stok + '</span></div></div>';
        });
        g('sk_db').innerHTML = h;
        g('sk_ov').style.display = 'block';
        g('sk_dr').classList.add('open');
        g('sk_dr').setAttribute('aria-hidden', 'false');
    }
    function tutup() {
        if (!g('sk_ov')) return; // halaman sudah diganti (SPA)
        g('sk_ov').style.display = 'none';
        g('sk_dr').classList.remove('open');
        g('sk_dr').setAttribute('aria-hidden', 'true');
    }

    // ---- event ----
    var tmr;
    g('sk_q').addEventListener('input', function () { clearTimeout(tmr); var v = this.value.trim(); tmr = setTimeout(function () { S.q = v; S.page = 1; gambar(); }, 180); });
    g('sk_fb').addEventListener('change', function () { S.beda = this.value; S.page = 1; gambar(); });
    g('sk_ps').addEventListener('change', function () { S.size = parseInt(this.value, 10) || 20; S.page = 1; gambar(); });
    g('sk_ref').addEventListener('click', muat);
    g('sk_rs').addEventListener('click', function () { S.q = ''; S.beda = ''; S.asal = {}; g('sk_q').value = ''; g('sk_fb').value = ''; S.page = 1; popAsal(); gambar(); });
    g('sk_fa').addEventListener('click', function (e) {
        if (e.target.closest('[data-tog]')) { this.classList.toggle('open'); return; }
        var cb = e.target.closest('input[data-asal]');
        if (cb) { S.asal[cb.dataset.asal] = cb.checked; S.page = 1; popAsal(); gambar(); }
    });
    document.addEventListener('click', function (e) { var f = g('sk_fa'); if (f && !f.contains(e.target)) f.classList.remove('open'); });
    g('sk_pg').addEventListener('click', function (e) { var b = e.target.closest('button[data-p]'); if (b && !b.disabled) { S.page = parseInt(b.dataset.p, 10); gambar(); } });
    document.querySelector('.sk-table thead').addEventListener('click', function (e) {
        var th = e.target.closest('th.s'); if (!th) return;
        if (S.sort === th.dataset.s) S.dir = -S.dir; else { S.sort = th.dataset.s; S.dir = (S.sort === 'sku' || S.sort === 'rak') ? 1 : -1; }
        gambar();
    });
    g('sk_tb').addEventListener('click', function (e) { var tr = e.target.closest('tr[data-sku]'); if (tr) buka(tr.dataset.sku); });
    g('sk_ov').addEventListener('click', tutup);
    g('sk_dx').addEventListener('click', tutup);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });

    muat();
})();
</script>
