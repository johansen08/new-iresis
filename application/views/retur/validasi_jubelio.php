<style>
  /* Gaya mengikuti menu CS > Manajemen Komplain CS New (kartu ringkasan, satu kartu data, tabel bersih).
     Semua kelas berawalan vj- supaya tidak bentrok dengan halaman lain. Hanya tampilan:
     id elemen, kelas JS (rekon-chk, verif-chk, summary-clickable, ...) dan indeks data tidak berubah. */
  .vj-page { --vj-primary: #4f46e5; --vj-primary-light: rgba(79,70,229,.1); --vj-bg: #f8fafc; --vj-border: #e2e8f0; --vj-text: #334155; --vj-muted: #64748b;
             font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; }
  .vj-page input, .vj-page select, .vj-page button { font-family: inherit; }

  .vj-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 4px 0 14px; }
  .vj-filters { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
  .vj-period-label { font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vj-muted); margin-right: 2px; }
  .vj-chip { display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: var(--vj-text); font-size: 13.5px; font-weight: 500; margin: 0; white-space: nowrap; }
  .vj-chip > i.fa { color: #94a3b8; }
  .vj-chip-lbl { color: var(--vj-muted); font-weight: 600; font-size: 12px; }
  .vj-chip input, .vj-chip select { border: 0 !important; background: transparent !important; font-size: 13.5px; color: var(--vj-text) !important; padding: 0; height: 100%; box-shadow: none !important; outline: none; }
  .vj-chip input { width: 262px; cursor: pointer; }
  .vj-chip select { cursor: pointer; max-width: 160px; }
  .vj-chip:hover, .vj-chip:focus-within { border-color: #a5b4fc; }
  .vj-chip:focus-within { box-shadow: 0 0 0 3px var(--vj-primary-light); }

  .vj-btn { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; border-radius: 8px; border: 1px solid var(--vj-border); background: #fff; color: var(--vj-text); font-size: 14px; font-weight: 600; cursor: pointer; transition: all .2s ease; white-space: nowrap; }
  .vj-btn:hover:not(:disabled) { background: var(--vj-bg); border-color: #cbd5e1; }
  .vj-btn:disabled { opacity: .5; cursor: not-allowed; }
  .vj-btn-primary { background: var(--vj-primary); border-color: var(--vj-primary); color: #fff; }
  .vj-btn-primary:hover:not(:disabled) { background: #4338ca; border-color: #4338ca; box-shadow: 0 4px 12px rgba(79,70,229,.2); }
  .vj-btn-sm { height: 36px; padding: 0 12px; font-size: 13px; }
  .vj-dd { position: relative; display: inline-block; }
  .vj-dd .dropdown-menu { margin-top: 6px; border: 1px solid var(--vj-border); border-radius: 10px; box-shadow: 0 12px 28px rgba(15,23,42,.14); padding: 6px; min-width: 220px; right: 0; left: auto; }
  .vj-dd .dropdown-menu > li > a { padding: 8px 10px; border-radius: 6px; font-size: 13.5px; color: var(--vj-text); }
  .vj-dd .dropdown-menu > li > a:hover { background: var(--vj-bg); color: var(--vj-primary); }
  .vj-dd .dropdown-menu > li > a i { width: 18px; color: #94a3b8; }

  /* Kartu ringkasan (bisa diklik = filter) */
  .vj-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
  .vj-kpi { border: 1px solid var(--vj-border); border-radius: 12px; padding: 12px 16px 10px; background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s, transform .15s; margin: 0; }
  .vj-kpi:hover { border-color: #a5b4fc; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,23,42,.08); }
  .vj-kpi.active-filter { border-color: var(--vj-primary); box-shadow: 0 0 0 3px var(--vj-primary-light); background: #fbfbff; }
  .vj-kpi-top { display: flex; align-items: center; gap: 8px; }
  .vj-kpi-ic { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; font-size: 14px; }
  .vj-ic-indigo { background: #eef2ff; color: #4338ca; } .vj-ic-red { background: #fef2f2; color: #b91c1c; }
  .vj-ic-amber { background: #fffbeb; color: #b45309; } .vj-ic-green { background: #ecfdf5; color: #047857; }
  .vj-ic-sky { background: #f0f9ff; color: #0369a1; } .vj-ic-slate { background: #f1f5f9; color: #475569; }
  .vj-kpi-label { font-size: 13px; font-weight: 600; color: var(--vj-text); }
  .vj-kpi-val { margin-top: 8px; font-size: 26px; line-height: 1.1; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
  .vj-kpi-note { margin-top: 3px; font-size: 12px; color: var(--vj-muted); }

  /* Satu kartu data */
  .vj-body { margin-top: 16px; padding: 16px 18px 12px; background: #fff; border: 1px solid var(--vj-border); border-radius: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
  .vj-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 4px 0 14px; }
  .vj-search { position: relative; flex: 1 1 240px; max-width: 340px; }
  .vj-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
  .vj-search input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #fff; color: #0f172a; box-sizing: border-box; }
  .vj-search input:focus { outline: none; border-color: var(--vj-primary); box-shadow: 0 0 0 3px var(--vj-primary-light); }
  .vj-sel { display: flex; align-items: center; gap: 8px; margin-left: auto; padding: 4px 4px 4px 12px; border: 1px solid var(--vj-border); border-radius: 10px; background: var(--vj-bg); }
  .vj-sel-info { font-size: 13px; color: var(--vj-muted); }
  .vj-sel-info b { color: var(--vj-primary); font-variant-numeric: tabular-nums; }

  .vj-sec-title { display: flex; align-items: center; gap: 8px; margin: 26px 0 10px; font-size: 15px; font-weight: 700; color: #0f172a; }
  .vj-sec-title small { font-size: 12px; font-weight: 500; color: var(--vj-muted); }
  .vj-table-wrap { border: 1px solid var(--vj-border); border-radius: 10px; overflow-x: auto; background: #fff; padding-bottom: 2px; }
  .vj-table { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
  .vj-table > thead > tr > th { padding: 10px 9px; background: var(--vj-bg); border-bottom: 1px solid var(--vj-border) !important; border-top: 0; font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--vj-muted); white-space: nowrap; }
  .vj-table > thead > tr > th[class*="sorting"] { padding-right: 26px; }
  .vj-table > tbody > tr > td { padding: 10px 9px; border-top: 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; color: var(--vj-text); vertical-align: middle; }
  .vj-table > tbody > tr:nth-child(even) > td { background: #fafbfd; }
  .vj-table > tbody > tr:hover > td { background: #f1f5ff; }
  .vj-table > tbody > tr:last-child > td { border-bottom: 0; }
  /* Baris header "pengukur" milik DataTables (scrollX) harus tetap tak terlihat */
  .vj-table-wrap .dataTables_scrollBody > .vj-table > thead > tr > th { padding-top: 0 !important; padding-bottom: 0 !important; height: 0 !important; border: 0 !important; line-height: 0; font-size: 0; }
  .vj-table-wrap .dataTables_scrollBody > .vj-table > thead > tr > th::before, .vj-table-wrap .dataTables_scrollBody > .vj-table > thead > tr > th::after { display: none !important; }
  .vj-table > tbody > tr > td:nth-child(2), .vj-table > tbody > tr > td:nth-child(5) { white-space: nowrap; }
  .vj-table input[type=checkbox] { accent-color: var(--vj-primary); width: 15px; height: 15px; margin: 0; cursor: pointer; }
  .vj-table td small.text-success { display: block; margin-top: 2px; font-size: 11.5px; color: #047857 !important; white-space: nowrap; }
  .vj-table label { font-weight: 500; }
  .vj-table .label { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
  .vj-table .label-success { background: #ecfdf5; color: #047857; }
  .vj-table .label-warning { background: #fffbeb; color: #b45309; }
  .vj-table .label-danger { background: #fef2f2; color: #b91c1c; }
  .vj-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
  .vj-pill-green { background: #ecfdf5; color: #047857; } .vj-pill-red { background: #fef2f2; color: #b91c1c; }
  .vj-pill-amber { background: #fffbeb; color: #b45309; } .vj-pill-slate { background: #f1f5f9; color: #475569; }
  .vj-muted { color: #94a3b8; }

  .vj-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 4px 0; font-size: 13px; color: var(--vj-muted); }
  .vj-foot .dataTables_info { padding: 0; }
  .vj-pager { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
  .vj-pager .dataTables_length label { margin: 0; font-weight: 400; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
  .vj-pager .dataTables_length select { height: 32px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 6px; background: #fff; color: var(--vj-text); }
  .vj-pager .dataTables_paginate { margin: 0; }
  .vj-pager .pagination { margin: 0; }
  .vj-pager .pagination > li > a { margin-left: 4px; border: 1px solid var(--vj-border); border-radius: 8px !important; color: var(--vj-text); padding: 5px 11px; font-size: 13px; }
  .vj-pager .pagination > li > a:hover { background: var(--vj-bg); }
  .vj-pager .pagination > .active > a, .vj-pager .pagination > .active > a:hover { background: var(--vj-primary) !important; border-color: var(--vj-primary) !important; color: #fff !important; }
  .vj-pager .pagination > .disabled > a { color: #cbd5e1; background: #fff; }

  @media (max-width: 1199px) { .vj-kpis { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 767px) {
    .vj-kpis { gap: 10px; }
    .vj-body { padding: 12px 12px 8px; }
    .vj-search { max-width: none; flex-basis: 100%; }
    .vj-chip input { width: 190px; }
    .vj-sel { margin-left: 0; width: 100%; }
  }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="vj-page">

      <!-- Filter periode (data dimuat saat tombol Tampilkan ditekan) -->
      <div class="vj-head">
        <div class="vj-filters">
          <span class="vj-period-label">Periode</span>
          <label class="vj-chip" title="Rentang waktu retur">
            <i class="fa fa-calendar"></i>
            <input type="text" id="rentang_waktu_jubelio" readonly placeholder="Pilih Rentang Waktu" />
          </label>
          <label class="vj-chip">
            <span class="vj-chip-lbl">Kurir</span>
            <select id="kurir_jubelio">
              <option value="">Semua</option>
              <?php foreach ($list_kurir as $kurir): ?>
                <option value="<?= $kurir->id_kurir ?>"><?= $kurir->nama_kurir ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="vj-chip">
            <span class="vj-chip-lbl">Kondisi</span>
            <select id="kondisi_jubelio">
              <option value="">Semua</option>
              <option value="COCOK">Cocok</option>
              <option value="IRESIS">Hanya di iresis</option>
              <option value="JUBELIO">Hanya di Jubelio</option>
              <option value="VERIFIED">Sudah Diverifikasi</option>
              <option value="BELUM">Belum Diverifikasi</option>
            </select>
          </label>
          <button type="button" class="vj-btn vj-btn-primary" id="btn_tampilkan_jubelio">
            <i class="fa fa-search"></i> Tampilkan
          </button>
        </div>
        <div class="vj-dd dropdown">
          <button type="button" class="vj-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Unduh sesuai rentang &amp; filter">
            <i class="fa fa-download"></i> Export <i class="fa fa-angle-down"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="javascript:void(0)" id="btn_export_jubelio"><i class="fa fa-file-excel-o"></i> Export Semua</a></li>
            <li><a href="javascript:void(0)" id="btn_export_verified"><i class="fa fa-check"></i> Export Terverifikasi</a></li>
            <li><a href="javascript:void(0)" id="btn_export_update"><i class="fa fa-refresh"></i> Export Update</a></li>
          </ul>
        </div>
      </div>

      <!-- Kartu ringkasan: klik untuk memfilter tabel -->
      <div class="vj-kpis">
        <div class="vj-kpi summary-clickable" data-filter="TOTAL">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-indigo"><i class="fa fa-list-alt"></i></span><span class="vj-kpi-label">Total</span></div>
          <div class="vj-kpi-val" id="sum_total">0</div>
          <div class="vj-kpi-note">semua baris (utama + update)</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="VERIFIED">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-sky"><i class="fa fa-check-square-o"></i></span><span class="vj-kpi-label">Sudah Diverifikasi</span></div>
          <div class="vj-kpi-val" id="sum_verified">0</div>
          <div class="vj-kpi-note">sudah dicentang verifikasi</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="COCOK">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-green"><i class="fa fa-check-circle"></i></span><span class="vj-kpi-label">Cocok</span></div>
          <div class="vj-kpi-val" id="sum_cocok">0</div>
          <div class="vj-kpi-note">ada di iresis &amp; Jubelio</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="IRESIS">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-amber"><i class="fa fa-exclamation-circle"></i></span><span class="vj-kpi-label">Hanya di iresis</span></div>
          <div class="vj-kpi-val" id="sum_iresis">0</div>
          <div class="vj-kpi-note">belum ada di Jubelio</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="JUBELIO">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-red"><i class="fa fa-times-circle"></i></span><span class="vj-kpi-label">Hanya di Jubelio</span></div>
          <div class="vj-kpi-val" id="sum_jubelio">0</div>
          <div class="vj-kpi-note">belum diterima Retur</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="UPDATE">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-indigo"><i class="fa fa-refresh"></i></span><span class="vj-kpi-label">Update Retur</span></div>
          <div class="vj-kpi-val" id="sum_update_retur">0</div>
          <div class="vj-kpi-note">discan ulang</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="DITOLAK">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-red"><i class="fa fa-ban"></i></span><span class="vj-kpi-label">Ditolak</span></div>
          <div class="vj-kpi-val" id="sum_ditolak">0</div>
          <div class="vj-kpi-note">status buka selain KE_DISPLAY</div>
        </div>
        <div class="vj-kpi summary-clickable" data-filter="SELISIH">
          <div class="vj-kpi-top"><span class="vj-kpi-ic vj-ic-slate"><i class="fa fa-thumbs-o-up"></i></span><span class="vj-kpi-label">Setujui Jubelio</span></div>
          <div class="vj-kpi-val" id="sum_selisih">0</div>
          <div class="vj-kpi-note">KE_DISPLAY, perlu disetujui</div>
        </div>
      </div>

      <div class="vj-body">
        <div class="vj-toolbar">
          <div class="vj-search">
            <i class="fa fa-search"></i>
            <input type="text" id="vj-search" placeholder="Cari no. pesanan, resi, SKU, toko..." autocomplete="off" />
          </div>
          <button type="button" class="vj-btn vj-btn-sm" id="btn_kolom_lain" title="Tampilkan/sembunyikan kolom tambahan">
            <i class="fa fa-columns"></i> <span>Tampilkan Kolom Lain</span>
          </button>
          <div class="vj-sel">
            <span class="vj-sel-info"><b id="verif-selected-count">0</b> dipilih</span>
            <button type="button" class="vj-btn vj-btn-sm" id="btn_pilih_semua" disabled>
              <i class="fa fa-check-square-o"></i> Pilih Semua
            </button>
            <button type="button" class="vj-btn vj-btn-sm vj-btn-primary" id="btn_verifikasi_terpilih" disabled>
              <i class="fa fa-bolt"></i> Verifikasi Terpilih
            </button>
          </div>
        </div>

        <div class="vj-table-wrap">
          <table class="vj-table table" id="datatable_jubelio" style="width:100%;">
            <thead>
              <tr>
                <th class="text-center" style="width:30px;"><input type="checkbox" id="check-all-rekon" title="Pilih/Batal Semua (semua halaman)" /></th>
                <th>No. Pesanan</th>
                <th>Status Buka</th>
                <th>Ditolak Karena Apa</th>
                <th>Detail SKU</th>
                <th>SKU Pergantian</th>
                <th>Verifikasi</th>
                <th>No</th>
                <th>No. Resi</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Kategori iresis</th>
                <th>Status Jubelio</th>
                <th>Keterangan (Status Paket)</th>
                <th>Qty iresis</th>
                <th>Qty Jubelio</th>
                <th>Tanggal</th>
                <th>Kondisi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="vj-sec-title"><i class="fa fa-refresh" style="color:var(--vj-primary);"></i> Laporan Update Retur <small>retur yang discan ulang</small></div>
        <div class="vj-table-wrap">
          <table class="vj-table table" id="datatable_jubelio_update" style="width:100%;">
            <thead>
              <tr>
                <th class="text-center" style="width:30px;"><input type="checkbox" id="check-all-rekon-update" title="Pilih/Batal Semua (semua halaman)" /></th>
                <th>No. Pesanan</th>
                <th>Status Buka</th>
                <th>Ditolak Karena Apa</th>
                <th>Detail SKU</th>
                <th>SKU Pergantian</th>
                <th>Verifikasi</th>
                <th>No</th>
                <th>No. Resi</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Kategori iresis</th>
                <th>Status Jubelio</th>
                <th>Keterangan (Status Paket)</th>
                <th>Qty iresis</th>
                <th>Qty Jubelio</th>
                <th>Tanggal</th>
                <th>Kondisi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // URL absolut supaya tidak salah resolve saat halaman dimuat lewat SPA
    var BASE = '<?= rtrim(base_url(), "/") ?>/';
    var IS_KOMPLAIN = <?= isset($is_komplain) && $is_komplain ? 'true' : 'false' ?>;



    // ==================== DATERANGEPICKER (rekonsiliasi) ====================
    $('#rentang_waktu_jubelio').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 1,
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm',
            separator: ' s/d ',
            applyLabel: 'Terapkan',
            cancelLabel: 'Batal',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        startDate: moment().startOf('day'),
        endDate: moment().endOf('day')
    });

    // Global row variables and active filter state
    var ALL_ROWS = [];
    var ALL_ROWS_UPDATE = [];
    var activeFilter = 'TOTAL';

    function filterRow(row, filterType) {
        if (filterType === 'TOTAL') return true;

        // Sudah Diverifikasi
        if (filterType === 'VERIFIED') {
            return row[17] && row[17].indexOf('checked') !== -1;
        }

        // Cocok
        if (filterType === 'COCOK') {
            return row[12] && row[12].indexOf('label-success') !== -1;
        }

        // Hanya di iresis
        if (filterType === 'IRESIS') {
            return row[12] && row[12].indexOf('label-warning') !== -1;
        }

        // Hanya di Jubelio
        if (filterType === 'JUBELIO') {
            return row[12] && row[12].indexOf('label-danger') !== -1;
        }

        // Ditolak: status detail not empty, not "-", not "KE_DISPLAY"
        if (filterType === 'DITOLAK') {
            var statusDetail = (row[13] || '').trim().toUpperCase();
            return statusDetail !== '' && statusDetail !== '-' && statusDetail !== 'KE_DISPLAY';
        }

        // Setujui Jubelio: status KE_DISPLAY yang BUKAN "Hanya di iresis"
        if (filterType === 'SELISIH') {
            var statusDetail = (row[13] || '').trim().toUpperCase();
            var isIresis = row[12] && row[12].indexOf('label-warning') !== -1;
            return statusDetail === 'KE_DISPLAY' && !isIresis;
        }

        return true;
    }

    function applyFilter() {
        var filteredNormal = [];
        var filteredUpdate = [];

        if (activeFilter === 'UPDATE') {
            filteredNormal = [];
            filteredUpdate = ALL_ROWS_UPDATE;
        } else {
            filteredNormal = ALL_ROWS.filter(function(row) {
                return filterRow(row, activeFilter);
            });
            filteredUpdate = ALL_ROWS_UPDATE.filter(function(row) {
                // Setujui Jubelio: baris Update Retur yang SUDAH diverifikasi tidak dihitung.
                if (activeFilter === 'SELISIH' && (row[17] || '').indexOf('checked') !== -1) {
                    return false;
                }
                return filterRow(row, activeFilter);
            });
        }

        tableJubelio.clear().rows.add(filteredNormal).draw();
        tableJubelioUpdate.clear().rows.add(filteredUpdate).draw();

        // Update Pilih Semua button state based on visible checkboxes
        var visibleCheckboxes = allChk(false).length;
        $('#btn_pilih_semua').prop('disabled', visibleCheckboxes === 0);
        updateVerifSelectedCount();
    }

    // ==================== DATATABLE REKONSILIASI (client-side) ====================
    // Urutan kolom tampilan: 5 kolom wajib dulu (checkbox, No. Pesanan, Status Buka,
    // Detail SKU, SKU Pergantian) + Verifikasi; sisanya disembunyikan (tombol "Kolom Lain").
    // Angka 'data' = indeks array dari get_rekonsiliasi_data().
    var KOLOM_LAIN = [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
    function dtConfig() {
        return {
            'scrollX': true,
            'pageLength': 25,
            'lengthMenu': [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            'data': [],
            'order': [],
            'dom': 't<"vj-foot"<"vj-info"i><"vj-pager"lp>>',
            'language': { 'emptyTable': 'Pilih rentang waktu lalu klik "Tampilkan" untuk memuat data' },
            'columns': [
                { 'data': 'cb', 'orderable': false },
                { 'data': 2 },
                { 'data': 13, 'render': function (d, type) {
                    if (type !== 'display' || typeof d !== 'string') return d;
                    var raw = d.trim().toUpperCase();
                    if (raw === '' || raw === '-') return '<span class="vj-muted">-</span>';
                    var warna = (raw === 'KE_DISPLAY') ? 'green'
                              : (['REJECT', 'BUKAN_BARANG_KITA', 'BARANG_TIDAK_ADA', 'PAKET_HILANG', 'DIPAKAI_ADMIN'].indexOf(raw) !== -1 ? 'red' : 'amber');
                    return '<span class="vj-pill vj-pill-' + warna + '">' + d.replace(/_/g, ' ') + '</span>';
                } },
                { 'data': 14 },
                { 'data': 15 },
                { 'data': 16 },
                { 'data': 17, 'orderable': false },
                { 'data': 0 }, { 'data': 1 }, { 'data': 3 }, { 'data': 4 },
                { 'data': 5 }, { 'data': 6 }, { 'data': 7 }, { 'data': 8 },
                { 'data': 9 }, { 'data': 10 }, { 'data': 11 }, { 'data': 12 }
            ],
            'columnDefs': [{ 'targets': KOLOM_LAIN, 'visible': false }]
        };
    }

    var tableJubelio = $('#datatable_jubelio').DataTable(dtConfig());
    var tableJubelioUpdate = $('#datatable_jubelio_update').DataTable(dtConfig());

    // Semua checkbox pilih (SEMUA halaman, bukan hanya halaman tampil).
    function allChk(onlyChecked, searchApplied) {
        var mod = { page: 'all' };
        if (searchApplied) mod.search = 'applied';
        var sel = onlyChecked ? 'input.rekon-chk:checked' : 'input.rekon-chk:not(:disabled)';
        var selU = onlyChecked ? 'input.rekon-chk-update:checked' : 'input.rekon-chk-update:not(:disabled)';
        return tableJubelio.$(sel, mod).add(tableJubelioUpdate.$(selU, mod));
    }

    // Pencarian client-side untuk kedua tabel (juga mencari di kolom yang disembunyikan)
    var timerCari = null;
    $('#vj-search').on('input', function () {
        var v = this.value;
        clearTimeout(timerCari);
        timerCari = setTimeout(function () {
            tableJubelio.search(v).draw();
            tableJubelioUpdate.search(v).draw();
            updateVerifSelectedCount();
        }, 250);
    });

    var kolomLainTampil = false;
    $('#btn_kolom_lain').on('click', function () {
        kolomLainTampil = !kolomLainTampil;
        tableJubelio.columns(KOLOM_LAIN).visible(kolomLainTampil, false);
        tableJubelioUpdate.columns(KOLOM_LAIN).visible(kolomLainTampil, false);
        tableJubelio.columns.adjust().draw(false);
        tableJubelioUpdate.columns.adjust().draw(false);
        $(this).find('span').text(kolomLainTampil ? 'Sembunyikan Kolom Lain' : 'Tampilkan Kolom Lain');
    });

    function updateVerifSelectedCount() {
        var count = allChk(true).length;
        $('#verif-selected-count').text(count);
        $('#btn_verifikasi_terpilih').prop('disabled', count === 0);

        var all = tableJubelio.$('input.rekon-chk:not(:disabled)', { page: 'all' });
        $('#check-all-rekon').prop('checked', all.length > 0 && all.length === tableJubelio.$('input.rekon-chk:checked', { page: 'all' }).length);

        var allUpdate = tableJubelioUpdate.$('input.rekon-chk-update:not(:disabled)', { page: 'all' });
        $('#check-all-rekon-update').prop('checked', allUpdate.length > 0 && allUpdate.length === tableJubelioUpdate.$('input.rekon-chk-update:checked', { page: 'all' }).length);
    }

    function getJubelioFilter() {
        var dates = $('#rentang_waktu_jubelio').val().split(' s/d ');
        return {
            start_date: dates[0] || '',
            end_date: dates[1] || '',
            id_kurir: $('#kurir_jubelio').val(),
            kondisi: $('#kondisi_jubelio').val()
        };
    }

    // keepState=true: pertahankan kartu filter & halaman aktif (dipakai setelah verifikasi).
    function loadJubelio(keepState) {
        keepState = (keepState === true);
        var prevFilter = activeFilter, prevPage = tableJubelio.page(), prevPageU = tableJubelioUpdate.page();
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        tableJubelio.clear().draw();
        $('#check-all-rekon').prop('checked', false);
        $('#verif-selected-count').text(0);
        $('#btn_verifikasi_terpilih').prop('disabled', true);
        $('#btn_pilih_semua').prop('disabled', true);
        $.ajax({
            url: BASE + 'retur/get-rekonsiliasi-data',
            type: 'POST',
            dataType: 'json',
            data: $.extend(f, { is_komplain: IS_KOMPLAIN ? 1 : 0 }),
            beforeSend: function () {
                $('#btn_tampilkan_jubelio').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memuat...');
            },
            success: function (res) {
                // Inject per-row checkbox column
                var rows = res.data || [];
                rows.forEach(function(row) {
                    var resi = row[1] || '';
                    var kondisiBadge = row[12] || '';
                    var verifCell = row[17] || '';
                    var isJubelioOnly = kondisiBadge.indexOf('label-danger') !== -1;
                    var isVerified = verifCell.indexOf('checked') !== -1;
                    if (!isJubelioOnly && !isVerified) {
                        row['cb'] = '<input type="checkbox" class="rekon-chk" data-resi="' + $('<div>').text(resi).html() + '" />';
                    } else {
                        row['cb'] = '<span class="text-muted">-</span>';
                    }
                });
                ALL_ROWS = rows;

                // Inject per-row checkbox column for Update Table
                var rowsUpdate = res.data_update || [];
                rowsUpdate.forEach(function(row) {
                    var resi = row[1] || '';
                    var kondisiBadge = row[12] || '';
                    var verifCell = row[17] || '';
                    var isJubelioOnly = kondisiBadge.indexOf('label-danger') !== -1;
                    var isVerified = verifCell.indexOf('checked') !== -1;
                    if (!isJubelioOnly && !isVerified) {
                        row['cb'] = '<input type="checkbox" class="rekon-chk-update" data-resi="' + $('<div>').text(resi).html() + '" />';
                    } else {
                        row['cb'] = '<span class="text-muted">-</span>';
                    }
                });
                ALL_ROWS_UPDATE = rowsUpdate;

                // Reset active filter to TOTAL on fresh load
                activeFilter = keepState ? prevFilter : 'TOTAL';
                $('.summary-clickable').removeClass('active-filter');
                $('[data-filter="' + activeFilter + '"]').addClass('active-filter');

                // Render tables (applyFilter sudah menggambar ulang kedua tabel)
                applyFilter();
                if (keepState) {
                    tableJubelio.page(prevPage).draw(false);
                    tableJubelioUpdate.page(prevPageU).draw(false);
                }

                var cocok = (res.summary && res.summary.cocok) || 0;
                var iresis = (res.summary && res.summary.iresis) || 0;
                var jubelio = (res.summary && res.summary.jubelio) || 0;
                var update = (res.summary && res.summary.update_retur) || 0;
                // Total = jumlah semua baris (dihitung langsung di PHP: baris tab utama + tab update).
                var total = (res.summary && res.summary.total) || 0;
                var ditolak = (res.summary && res.summary.ditolak) || 0;
                var selisih = (res.summary && res.summary.selisih) || 0;

                $('#sum_total').text(total);
                $('#sum_cocok').text(cocok);
                $('#sum_iresis').text(iresis);
                $('#sum_jubelio').text(jubelio);
                $('#sum_update_retur').text(update);
                $('#sum_verified').text((res.summary && res.summary.verified) || 0);
                $('#sum_ditolak').text(ditolak);
                $('#sum_selisih').text(selisih);

                // Enable Pilih Semua if there are selectable rows
                var selectableCount = ALL_ROWS.filter(function(r){ return (r['cb'] || '').indexOf('rekon-chk') !== -1; }).length +
                                       ALL_ROWS_UPDATE.filter(function(r){ return (r['cb'] || '').indexOf('rekon-chk-update') !== -1; }).length;
                $('#btn_pilih_semua').prop('disabled', selectableCount === 0);
                updateVerifSelectedCount();

                // Recalculate column widths after drawing to ensure 100% width alignment
                setTimeout(function() {
                    tableJubelio.columns.adjust();
                    tableJubelioUpdate.columns.adjust();
                }, 100);
            },
            error: function () { alert('Gagal memuat data rekonsiliasi.'); },
            complete: function () {
                $('#btn_tampilkan_jubelio').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');
            }
        });
    }

    $('#btn_tampilkan_jubelio').on('click', function () { loadJubelio(false); });

    // ==================== SUMMARY CARD CLICK FILTERS ====================
    $(document).on('click', '.summary-clickable', function() {
        var filter = $(this).data('filter');
        $('.summary-clickable').removeClass('active-filter');
        $(this).addClass('active-filter');
        activeFilter = filter;
        applyFilter();
    });

    $('#btn_export_jubelio').on('click', function () {
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        var url = BASE + 'retur/export-rekonsiliasi?start_date=' + encodeURIComponent(f.start_date) +
                  '&end_date=' + encodeURIComponent(f.end_date) + '&is_komplain=' + (IS_KOMPLAIN ? 1 : 0);
        if (f.id_kurir) url += '&id_kurir=' + encodeURIComponent(f.id_kurir);
        if (f.kondisi) url += '&kondisi=' + encodeURIComponent(f.kondisi);
        window.location.href = url;
    });

    // ==================== EXPORT TERVERIFIKASI (Step 4) ====================
    $('#btn_export_verified').on('click', function () {
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        var url = BASE + 'retur/export-rekonsiliasi?verified=1&start_date=' + encodeURIComponent(f.start_date) +
                  '&end_date=' + encodeURIComponent(f.end_date) + '&is_komplain=' + (IS_KOMPLAIN ? 1 : 0);
        if (f.id_kurir) url += '&id_kurir=' + encodeURIComponent(f.id_kurir);
        window.location.href = url;
    });

    // ==================== EXPORT UPDATE ====================
    $('#btn_export_update').on('click', function () {
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        var url = BASE + 'retur/export-rekonsiliasi?is_update=1&start_date=' + encodeURIComponent(f.start_date) +
                  '&end_date=' + encodeURIComponent(f.end_date) + '&is_komplain=' + (IS_KOMPLAIN ? 1 : 0);
        if (f.id_kurir) url += '&id_kurir=' + encodeURIComponent(f.id_kurir);
        if (f.kondisi) url += '&kondisi=' + encodeURIComponent(f.kondisi);
        window.location.href = url;
    });

    // ==================== SELECT ALL CHECKBOX ====================
    $('#check-all-rekon').on('change', function() {
        var isChecked = $(this).prop('checked');
        tableJubelio.$('input.rekon-chk:not(:disabled)', { page: 'all', search: 'applied' }).prop('checked', isChecked);
        updateVerifSelectedCount();
    });

    $(document).on('change', '.rekon-chk', function() {
        updateVerifSelectedCount();
    });

    $('#check-all-rekon-update').on('change', function() {
        var isChecked = $(this).prop('checked');
        tableJubelioUpdate.$('input.rekon-chk-update:not(:disabled)', { page: 'all', search: 'applied' }).prop('checked', isChecked);
        updateVerifSelectedCount();
    });

    $(document).on('change', '.rekon-chk-update', function() {
        updateVerifSelectedCount();
    });

    // ==================== PILIH SEMUA BUTTON ====================
    $('#btn_pilih_semua').on('click', function() {
        var semua = allChk(false, true);
        var allChecked = semua.length > 0 && semua.filter(':checked').length === semua.length;
        semua.prop('checked', !allChecked);
        updateVerifSelectedCount();
    });

    // ==================== VERIFIKASI TERPILIH ====================
    $('#btn_verifikasi_terpilih').on('click', function () {
        var resiList = [];
        allChk(true).each(function () {
            var resi = $(this).data('resi');
            if (resi) resiList.push(resi);
        });

        if (resiList.length === 0) {
            alert('Belum ada resi yang dipilih. Centang baris yang ingin diverifikasi terlebih dahulu.');
            return;
        }

        if (confirm('Verifikasi ' + resiList.length + ' resi yang dipilih?')) {
            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

            $.ajax({
                url: BASE + 'retur/bulk-verifikasi-jubelio',
                type: 'POST',
                dataType: 'json',
                data: { resi_list: resiList },
                success: function (res) {
                    if (res && res.code === 200) {
                        if (typeof noty === 'function') {
                            noty({ text: res.message, timeout: 2000, layout: 'topRight', type: 'success' });
                        } else {
                            alert(res.message);
                        }
                        loadJubelio(true);
                    } else {
                        alert((res && res.message) ? res.message : 'Gagal melakukan verifikasi masal');
                    }
                },
                error: function (xhr) {
                    alert('Gagal terhubung ke server (HTTP ' + xhr.status + ').');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });

    // ==================== VERIFIKASI PER RESI (Step 3) ====================
    $(document).off('change.verif').on('change.verif', '.verif-chk', function () {
        var $chk = $(this);
        var resi = $chk.data('resi');
        var verified = $chk.is(':checked') ? 1 : 0;
        $chk.prop('disabled', true);
        $.ajax({
            url: BASE + 'retur/verifikasi-jubelio',
            type: 'POST',
            dataType: 'json',
            data: { no_resi: resi, verified: verified },
            success: function (res) {
                if (res && res.code === 200) {
                    if (typeof noty === 'function') {
                        noty({ text: res.message, timeout: 1500, layout: 'topRight', type: 'success' });
                    }
                    loadJubelio(true);
                } else {
                    alert((res && res.message) ? res.message : 'Gagal memperbarui verifikasi');
                    $chk.prop('checked', !verified).prop('disabled', false);
                }
            },
            error: function () {
                alert('Gagal terhubung ke server.');
                $chk.prop('checked', !verified).prop('disabled', false);
            }
        });
    });
});
</script>
