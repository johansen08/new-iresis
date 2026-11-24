<?php
/*
 * Penyimpanan Video (Tim Monitoring, hanya webmaster). Dokumen: docs/PENYIMPANAN_VIDEO.md.
 * Data dari Penyimpanan_video_fcd::snapshot(). Gaya mengikuti Retur -> Daftar Retur Penjualan
 * (kontrol 38 px, teal #0f766e, tabel bersih 37 px/baris, paging DataTables). Selector diawali #pv_wrap.
 */
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Penyimpanan Video</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;"><i class="fa fa-lock"></i> Menu ini hanya untuk webmaster.</div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<style>
#pv_wrap { background:#fff; border-radius:8px; padding:14px 20px 10px; font-size:13px; color:#111827; }
.pv-judul { display:flex; align-items:baseline; justify-content:space-between; flex-wrap:wrap; gap:4px 16px; margin-bottom:12px; }
.pv-judul h4 { margin:0; font-size:18px; font-weight:700; color:#111827; }
.pv-judul .sub { color:#6b7280; font-size:12.5px; }
.pv-judul .sub b { color:#111827; }

/* Banner alert estimasi */
.pv-alert { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600; line-height:1.45; border:1px solid; }
.pv-alert[hidden] { display:none; }
.pv-alert i { flex:none; margin-top:2px; }
.pv-alert.bahaya { background:#fee2e2; border-color:#fecaca; color:#b91c1c; }
.pv-alert.waspada { background:#fef3c7; border-color:#fde68a; color:#92400e; }
.pv-alert.aman { background:#f0fdfa; border-color:#99f6e4; color:#115e59; }
.pv-alert.tidak_tahu { background:#f3f4f6; border-color:#e5e7eb; color:#4b5563; }

/* Kartu ringkasan */
.pv-cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
.pv-card { border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; background:#fff; }
.pv-card .lbl { font-size:12px; font-weight:600; color:#6b7280; }
.pv-card .angka { margin-top:6px; font-size:28px; font-weight:700; line-height:1.1; color:#111827; font-variant-numeric:tabular-nums; }
.pv-card .angka small { margin-left:4px; font-size:14px; font-weight:600; color:#6b7280; }
.pv-card .cat { margin-top:6px; color:#6b7280; font-size:12.5px; line-height:1.5; }
.pv-card .cat b { color:#111827; }
.pv-bar { height:8px; margin-top:10px; background:#f1f5f9; border-radius:999px; overflow:hidden; }
.pv-bar i { display:block; height:100%; background:#0f766e; border-radius:999px; }
.pv-card.bahaya { border-color:#fca5a5; } .pv-card.bahaya .angka { color:#b91c1c; } .pv-card.bahaya .pv-bar i { background:#dc2626; }
.pv-card.waspada { border-color:#fcd34d; } .pv-card.waspada .angka { color:#b45309; } .pv-card.waspada .pv-bar i { background:#d97706; }

/* Bar filter (kontrol sama dengan Daftar Retur: tinggi 38, sudut 6, garis #d1d5db) */
.pv-filter { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; padding:10px 0; border-top:1px solid #e5e7eb; }
.pv-filter .dataTables_filter { position:relative; height:38px; margin:0; padding:0; float:none; width:auto; text-align:left; flex:1 1 300px; min-width:220px; max-width:440px; }
.pv-filter .dataTables_filter label { margin:0; padding:0; display:block; width:100%; height:38px; line-height:0; overflow:visible; }
.pv-filter .dataTables_filter input { box-sizing:border-box; display:block; width:100%; height:38px; margin:0; padding:0 40px 0 34px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; line-height:36px; color:#111827;
  background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5' stroke-linecap='round'><circle cx='11' cy='11' r='7'/><path d='M20 20l-3.5-3.5'/></svg>") no-repeat 12px center; }
.pv-filter .dataTables_filter input:hover { border-color:#0f766e; }
.pv-filter .dataTables_filter input:focus { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
.pv-filter .dataTables_filter.terisi input { border-color:#0f766e; background-color:#f0fdfa; }
.pv-cari-x { display:none; position:absolute; right:8px; top:50%; transform:translateY(-50%); width:22px; height:22px; padding:0; border:0; border-radius:50%; background:#e5e7eb; color:#4b5563; font-size:12px; line-height:22px; text-align:center; cursor:pointer; }
.pv-cari-x:hover { background:#d1d5db; color:#111827; }
.pv-filter .dataTables_filter.terisi .pv-cari-x { display:block; }
.pv-reset { box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; padding:0; margin:0; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer; color:#6b7280; }
.pv-reset:hover { border-color:#0f766e; color:#0f766e; }
.pv-acts { display:flex; align-items:center; gap:10px; margin-left:auto; }
.pv-btn { box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:8px; height:38px; margin:0; padding:0 14px; border:1px solid #0f766e; border-radius:6px; background:#fff; color:#0f766e; font-size:13px; font-weight:600; line-height:1; cursor:pointer; white-space:nowrap; }
.pv-btn:hover { background:#f0fdfa; }
.pv-btn[disabled] { background:#f3f4f6; border-color:#d1d5db; color:#9ca3af; cursor:progress; }
.pv-reset:focus-visible, .pv-btn:focus-visible { outline:0; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.25); }

/* Tabel */
#pv_wrap table.dataTable { float:none; clear:both; border:0; table-layout:fixed; width:100% !important; font-size:13px; }
#pv_wrap table.dataTable thead th { background:#f9fafb; color:#6b7280; font-weight:600; border:0; padding:9px 22px 9px 8px; font-size:12px; white-space:nowrap; box-shadow:0 1px 0 #e5e7eb; }
#pv_wrap table.dataTable thead th:first-child { border-radius:6px 0 0 6px; }
#pv_wrap table.dataTable thead th:last-child { border-radius:0 6px 6px 0; }
#pv_wrap table.dataTable tbody td { height:37px; border:0; border-bottom:1px solid #f1f5f9; padding:0 8px; vertical-align:middle; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-variant-numeric:tabular-nums; }
#pv_wrap table.dataTable tbody tr:hover td { background:#f8fafc; }
#pv_wrap table.dataTable td:nth-child(n+2), #pv_wrap table.dataTable th:nth-child(n+2) { text-align:right; }
#pv_wrap table.dataTable thead th:nth-child(n+2) { padding-right:22px; }
#pv_wrap table.dataTable tbody td:nth-child(n+2) { padding-right:22px; }
#pv_wrap table.dataTable tbody td.hari { font-weight:600; color:#111827; }
#pv_wrap table.dataTable tbody td.hari small { margin-left:6px; font-weight:400; color:#9ca3af; font-size:12px; }
#pv_wrap table.dataTable tbody td.tebal { font-weight:700; color:#111827; }
.pv-macet { display:inline-block; padding:0 8px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:11.5px; font-weight:700; line-height:20px; }
.pv-tag { display:inline-block; margin-left:6px; padding:0 8px; border-radius:999px; background:#ccfbf1; color:#0f766e; font-size:11px; font-weight:700; line-height:20px; }

/* Bawah: info + baris per halaman + paging */
#pv_wrap .pv-bawah { clear:both; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px 18px; margin-top:8px; padding-top:8px; border-top:1px solid #f1f5f9; }
#pv_wrap .pv-bawah .dataTables_length, #pv_wrap .pv-bawah .dataTables_info, #pv_wrap .pv-bawah .dataTables_paginate { float:none; margin:0; padding:0; width:auto; min-width:0; }
#pv_wrap .pv-bawah .dataTables_info { color:#6b7280; white-space:nowrap; font-size:12.5px; text-align:left; }
#pv_wrap .pv-kanan { display:flex; align-items:center; gap:8px 18px; margin-left:auto; }
#pv_wrap .pv-bawah .dataTables_length label { margin:0; font-weight:400; color:#6b7280; white-space:nowrap; font-size:12.5px; display:inline-flex; align-items:center; gap:6px; }
#pv_wrap .pv-bawah .dataTables_length select { width:auto; display:inline-block; margin:0; height:32px; padding:0 8px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; font-weight:600; font-size:13px; }
#pv_wrap .dataTables_paginate { display:flex; align-items:center; gap:4px; }
#pv_wrap .dataTables_paginate > span { display:flex; align-items:center; gap:4px; }
#pv_wrap .dataTables_paginate .paginate_button { box-sizing:border-box; min-width:32px; height:32px; margin:0; padding:0 9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:6px; background:#fff; color:#374151; font-size:13px; font-weight:600; line-height:1; cursor:pointer; text-decoration:none; }
#pv_wrap .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) { border-color:#0f766e; background:#f0fdfa; color:#0f766e; }
#pv_wrap .dataTables_paginate .paginate_button.current, #pv_wrap .dataTables_paginate .paginate_button.current:hover { background:#0f766e; border-color:#0f766e; color:#fff; cursor:default; }
#pv_wrap .dataTables_paginate .paginate_button.disabled, #pv_wrap .dataTables_paginate .paginate_button.disabled:hover { background:#f9fafb; border-color:#f1f5f9; color:#cbd5e1; cursor:not-allowed; }
@media (max-width:820px) { .pv-cards { grid-template-columns:minmax(0,1fr); } .pv-acts { margin-left:0; } }
</style>

<div id="pv_wrap">
  <div class="pv-judul">
    <h4>Penyimpanan Video</h4>
    <div class="sub" id="pv_sub"></div>
  </div>

  <div class="pv-alert" id="pv_alert" role="alert" hidden></div>
  <div class="pv-alert waspada" id="pv_macet" role="alert" hidden></div>

  <div class="pv-cards">
    <div class="pv-card" id="pv_c_total">
      <div class="lbl">Total penyimpanan terpakai</div>
      <div class="angka" id="pv_total">-</div>
      <div class="cat" id="pv_total_cat"></div>
    </div>
    <div class="pv-card" id="pv_c_sisa">
      <div class="lbl">Space tersisa</div>
      <div class="angka" id="pv_sisa">-</div>
      <div class="pv-bar"><i id="pv_bar" style="width:0"></i></div>
      <div class="cat" id="pv_sisa_cat"></div>
    </div>
    <div class="pv-card" id="pv_c_est">
      <div class="lbl">Estimasi penuh</div>
      <div class="angka" id="pv_est">-</div>
      <div class="cat" id="pv_est_cat"></div>
    </div>
  </div>

  <div class="pv-filter">
    <!-- kotak cari DataTables disisipkan JS di sini -->
    <button type="button" class="pv-reset" id="pv_reset" title="Atur ulang pencarian dan urutan"><i class="fa fa-undo"></i></button>
    <div class="pv-acts">
      <button type="button" class="pv-btn" id="pv_muat" title="Hitung ulang dari data terbaru"><i class="fa fa-refresh"></i> Muat ulang</button>
    </div>
  </div>

  <table class="table table-hover" id="dt_penyimpanan_video" style="width:100%;">
    <thead>
      <tr>
        <th style="width:250px;">Tanggal</th>
        <th>Total video</th>
        <th>Ukuran video</th>
        <th>Rata-rata</th>
        <th title="Rekaman berstatus MEREKAM lebih dari 2 jam (sesi tidak pernah ditutup)">Macet</th>
        <th>Kumulatif</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<script>
(function () {
  var URL_DATA = <?= json_encode(base_url('monitoring/penyimpanan-video-data')) ?>;
  var DATA = <?= json_encode($snapshot) ?>;
  var HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  var BLN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  var tabel = null;

  function $e(id) { return document.getElementById(id); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  function nf(n) { return Number(n).toLocaleString('id-ID'); }
  // Satuan sama dengan Windows Explorer: 1 KB = 1024 B (labelnya tetap KB/MB/GB/TB), tiga angka bermakna,
  // dipotong (bukan dibulatkan). Contoh: 4.000.768.323.584 B = "3,63 TB", sama seperti properti drive D.
  function ukuranParts(b) {
    var u = ['B','KB','MB','GB','TB'], i = 0, v = Number(b);
    while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
    var d = i === 0 ? 0 : (v < 10 ? 2 : (v < 100 ? 1 : 0));
    var f = Math.pow(10, d);
    var t = (Math.floor(v * f) / f).toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
    return [t, u[i]];
  }
  function ukuran(b) { var x = ukuranParts(b); return x[0] + ' ' + x[1]; }
  function tgl(s) { var p = s.split('-'); return (+p[2]) + ' ' + BLN[+p[1] - 1] + ' ' + p[0]; }
  function hariNama(s) { var p = s.split('-'); return HARI[new Date(+p[0], +p[1] - 1, +p[2]).getDay()]; }
  function angka(id, b) { var x = ukuranParts(b); $e(id).innerHTML = esc(x[0]) + '<small>' + x[1] + '</small>'; }
  function banner(level, ikon, teks) {
    var a = $e('pv_alert'); a.className = 'pv-alert ' + level; a.hidden = false;
    a.innerHTML = '<i class="fa ' + ikon + '"></i><span>' + esc(teks) + '</span>';
  }

  function isiKartu(d) {
    $e('pv_sub').innerHTML = 'Folder <b>' + esc(d.folder) + '</b> · satuan seperti Windows (1 TB = 1024 GB) · dihitung ' + esc(d.dihitung_pada.substr(11, 5));
    angka('pv_total', d.terpakai_video);
    $e('pv_total_cat').innerHTML = '<b>' + nf(d.total_video) + '</b> video'
      + (d.terpakai_disk !== null ? '<br>Terpakai di drive (semua data): <b>' + ukuran(d.terpakai_disk) + '</b>' : '');

    var est = d.perkiraan;
    $e('pv_c_est').className = 'pv-card ' + est.level;
    $e('pv_c_sisa').className = 'pv-card ' + est.level;

    if (!d.drive_ok) {
      $e('pv_sisa').textContent = '-';
      $e('pv_bar').style.width = '0';
      $e('pv_sisa_cat').textContent = 'Drive tidak terbaca.';
      banner('bahaya', 'fa-exclamation-triangle', 'Drive penyimpanan video tidak terbaca. Pastikan HDD backup terpasang dan folder ' + d.folder + ' bisa diakses.');
    } else {
      angka('pv_sisa', d.sisa);
      var pakai = Math.min(100, Math.round(d.terpakai_disk / d.kapasitas * 100));
      $e('pv_bar').style.width = pakai + '%';
      $e('pv_sisa_cat').innerHTML = 'dari kapasitas <b>' + ukuran(d.kapasitas) + '</b> · terpakai <b>' + pakai + '%</b>';
    }

    if (est.sisa_hari === null) {
      $e('pv_est').textContent = '-';
      $e('pv_est_cat').textContent = d.drive_ok ? 'Belum ada data pertumbuhan harian untuk dihitung.' : '';
      if (d.drive_ok) { banner('tidak_tahu', 'fa-info-circle', 'Estimasi belum bisa dihitung: belum ada data pertumbuhan harian.'); }
    } else {
      $e('pv_est').innerHTML = nf(est.sisa_hari) + '<small>hari lagi</small>';
      $e('pv_est_cat').innerHTML = 'sekitar <b>' + tgl(est.tanggal_penuh) + '</b><br>rata-rata tambah <b>' + ukuran(est.rata_harian_byte) + '</b>/hari (' + d.hari_perkiraan + ' hari terakhir)';
      var t = tgl(est.tanggal_penuh), n = nf(est.sisa_hari);
      if (est.level === 'bahaya') { banner('bahaya', 'fa-exclamation-triangle', 'AWAS: HDD backup diperkirakan penuh dalam ' + n + ' hari (sekitar ' + t + '). Segera siapkan HDD tambahan atau arsipkan video lama.'); }
      else if (est.level === 'waspada') { banner('waspada', 'fa-exclamation-circle', 'Waspada: HDD backup diperkirakan penuh dalam ' + n + ' hari (sekitar ' + t + '). Mulai rencanakan penambahan kapasitas.'); }
      else { banner('aman', 'fa-check-circle', 'Aman: HDD backup diperkirakan baru penuh sekitar ' + t + ' (' + n + ' hari lagi).'); }
    }
  }

  function isiMacet(d) {
    var m = d.macet, a = $e('pv_macet');
    if (!m || !m.jumlah) { a.hidden = true; return; }
    a.hidden = false;
    a.innerHTML = '<i class="fa fa-exclamation-circle"></i><span><b>' + nf(m.jumlah) + ' rekaman macet</b> (status Merekam lebih dari ' + (m.menit / 60) + ' jam, belum pernah ditutup) memakai <b>' + ukuran(m.byte) + '</b>. Rekaman macet ikut menaikkan rata-rata harian dan mempercepat estimasi penuh; lihat kolom Macet di tabel.</span>';
  }

  function isiTabel(d) {
    var baris = d.hari.map(function (h) {
      return [h.tanggal, h.total_video, h.byte_total, h.total_video ? h.byte_total / h.total_video : 0, [h.n_macet, h.byte_macet], h.kumulatif];
    });
    tabel.clear().rows.add(baris).draw(false);
  }

  function mulai() {
    tabel = $('#dt_penyimpanan_video').DataTable({
      autoWidth: false,
      data: [],
      dom: "<'pv-atas'f>rt<'pv-bawah'<'pv-kiri'i><'pv-kanan'lp>>",
      pageLength: 15,
      pagingType: 'full_numbers',
      lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
      order: [[0, 'desc']],
      columns: [
        { className: 'hari', render: function (v, t) {
            if (t !== 'display') { return v + ' ' + hariNama(v) + ' ' + tgl(v); }
            var hi = new Date(), p = function (x) { return (x < 10 ? '0' : '') + x; };
            var ini = v === hi.getFullYear() + '-' + p(hi.getMonth() + 1) + '-' + p(hi.getDate());
            return esc(tgl(v)) + '<small>' + hariNama(v) + '</small>' + (ini ? '<span class="pv-tag">Hari ini</span>' : '');
          } },
        { render: function (v, t) { return t === 'display' ? nf(v) : v; } },
        { className: 'tebal', render: function (v, t) { return t === 'display' ? ukuran(v) : v; } },
        { render: function (v, t) { return t === 'display' ? (v ? ukuran(v) : '-') : v; } },
        { className: 'macet', render: function (v, t) {
            if (t !== 'display') { return v[1]; }
            return v[0] ? '<span class="pv-macet" title="' + nf(v[0]) + ' rekaman macet, total ' + ukuran(v[1]) + '">' + nf(v[0]) + ' · ' + ukuran(v[1]) + '</span>' : '-';
          } },
        { render: function (v, t) { return t === 'display' ? ukuran(v) : v; } }
      ],
      language: {
        emptyTable: 'Belum ada rekaman video',
        zeroRecords: 'Tidak ada data yang cocok',
        search: '',
        searchPlaceholder: 'Cari tanggal, mis. 2026-11 atau Senin...',
        lengthMenu: 'Baris per halaman _MENU_',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ tanggal',
        infoEmpty: 'Tidak ada data',
        infoFiltered: '',
        thousands: '.',
        paginate: { first: '«', previous: '‹', next: '›', last: '»' }
      }
    });

    var $cari = $('#dt_penyimpanan_video_filter').prependTo('.pv-filter');
    var $in = $cari.find('input').attr({ autocomplete: 'off', 'aria-label': 'Cari tanggal' });
    $cari.find('label').append('<button type="button" class="pv-cari-x" title="Hapus pencarian" aria-label="Hapus pencarian"><i class="fa fa-times"></i></button>');
    $in.on('input', function () { $cari.toggleClass('terisi', $.trim($in.val()) !== ''); });
    $cari.on('click', '.pv-cari-x', function () { $in.val('').trigger('input').trigger('keyup').focus(); });

    $('#pv_reset').on('click', function () {
      $in.val('').trigger('input');
      tabel.search('').order([[0, 'desc']]).page('first').draw();
    });

    $('#pv_muat').on('click', function () {
      var $b = $(this).prop('disabled', true);
      $.ajax({ url: URL_DATA, dataType: 'json', timeout: 120000 }).done(function (r) {
        if (r && r.code == 200 && r.data) { isiKartu(r.data); isiMacet(r.data); isiTabel(r.data); }
        else { banner('bahaya', 'fa-exclamation-triangle', (r && r.message) || 'Data gagal dimuat.'); }
      }).fail(function (x) {
        banner('bahaya', 'fa-exclamation-triangle', 'Data gagal dimuat (' + (x.status || 'tidak ada respon') + '). Coba lagi.');
      }).always(function () { $b.prop('disabled', false); });
    });

    if (DATA) { isiKartu(DATA); isiMacet(DATA); isiTabel(DATA); }
    else { banner('bahaya', 'fa-exclamation-triangle', 'Data penyimpanan gagal dihitung. Klik Muat ulang.'); }
  }

  mulai();
})();
</script>
