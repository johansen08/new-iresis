<style>
  .panel-total-card {
    background: linear-gradient(to bottom, #0088ff 0%, #ff9800 100%) !important;
    color: #fff !important;
    border: none !important;
    border-radius: 6px !important;
    box-shadow: 0 4px 15px rgba(0, 136, 255, 0.25) !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  }
  .panel-total-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(0, 136, 255, 0.45) !important;
  }
  .panel-total-card .panel-body {
    padding: 15px !important;
  }
  .panel-total-value {
    margin: 0 !important;
    font-weight: 800 !important;
    font-size: 26px !important;
    color: #fff !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
  }
  .panel-total-label {
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 1.5px !important;
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 11px !important;
    margin-top: 5px !important;
    display: inline-block !important;
  }
  .summary-clickable {
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
  }
  .summary-clickable:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15) !important;
  }
  .summary-clickable.active-filter {
    border: 3px solid #337ab7 !important;
    box-shadow: 0 0 15px rgba(51, 122, 183, 0.6) !important;
  }
  .panel-total-card.active-filter {
    border: 3px solid #fff !important;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.9) !important;
  }
</style>

<div class="row">
  <div class="col-md-12">



    <!-- ============ PANEL 2: LAPORAN REKONSILIASI ============ -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-check-square-o"></i> <?= isset($is_komplain) && $is_komplain ? 'Laporan Verifikasi Retur Komplain' : 'Laporan Verifikasi Retur' ?></h4>
      </div>
      <div class="panel-body">

        <!-- Filter -->
        <div class="form-horizontal">
          <div class="form-group">
            <label class="col-md-2 control-label">Rentang Waktu</label>
            <div class="col-md-4">
              <input type="text" id="rentang_waktu_jubelio" class="form-control" placeholder="Pilih Rentang Waktu" />
            </div>
            <label class="col-md-1 control-label">Kurir</label>
            <div class="col-md-2">
              <select id="kurir_jubelio" class="form-control">
                <option value="">Semua Kurir</option>
                <?php foreach ($list_kurir as $kurir): ?>
                  <option value="<?= $kurir->id_kurir ?>"><?= $kurir->nama_kurir ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <label class="col-md-1 control-label">Kondisi</label>
            <div class="col-md-2">
              <select id="kondisi_jubelio" class="form-control">
                <option value="">Semua</option>
                <option value="COCOK">Cocok</option>
                <option value="IRESIS">Hanya di iresis</option>
                <option value="JUBELIO">Hanya di Jubelio</option>
                <option value="VERIFIED">Sudah Diverifikasi</option>
                <option value="BELUM">Belum Diverifikasi</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label"></label>
            <div class="col-md-10">
              <button type="button" class="btn btn-primary" id="btn_tampilkan_jubelio">
                <i class="fa fa-search"></i> Tampilkan
              </button>
              <button type="button" class="btn btn-success" id="btn_export_jubelio">
                <i class="fa fa-file-excel-o"></i> Export Semua
              </button>
              <button type="button" class="btn btn-info" id="btn_export_verified">
                <i class="fa fa-check"></i> Export Terverifikasi
              </button>
              <button type="button" class="btn btn-warning" id="btn_export_update" style="margin-left:5px;">
                <i class="fa fa-file-excel-o"></i> Export Update
              </button>
              <button type="button" class="btn btn-default" id="btn_pilih_semua" style="margin-left:10px;" disabled>
                <i class="fa fa-check-square-o"></i> Pilih Semua
              </button>
              <button type="button" class="btn btn-warning" id="btn_verifikasi_terpilih" style="margin-left:5px;" disabled>
                <i class="fa fa-bolt"></i> Verifikasi Terpilih (<span id="verif-selected-count">0</span>)
              </button>
              <label style="font-weight:normal;margin-left:15px;cursor:pointer;" title="Baca snapshot iresis sebelum restore 2026-07-07 (tblresiretur/tblbukaretur_corrupt_20260707)">
                <input type="checkbox" id="use_old_jubelio"> Data Lama (07-07)
              </label>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-3">
            <div class="panel panel-total-card summary-clickable" data-filter="TOTAL"><div class="panel-body text-center">
              <h4 class="panel-total-value"><span id="sum_total">0</span></h4>
              <small class="panel-total-label">TOTAL</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-info summary-clickable" data-filter="VERIFIED"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_verified">0</span></h4><small style="font-weight:600;">Sudah Diverifikasi</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-success summary-clickable" data-filter="COCOK"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_cocok">0</span></h4><small style="font-weight:600;">Cocok (iresis &amp; Jubelio)</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-warning summary-clickable" data-filter="IRESIS"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_iresis">0</span></h4><small style="font-weight:600;">Hanya di iresis</small>
            </div></div>
          </div>
        </div>

        <div class="row" style="margin-bottom:15px;">
          <div class="col-md-3">
            <div class="panel panel-danger summary-clickable" data-filter="JUBELIO"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_jubelio">0</span></h4><small style="font-weight:600;">Hanya di Jubelio</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-primary summary-clickable" data-filter="UPDATE"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_update_retur">0</span></h4><small style="font-weight:600;">Update Retur</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-default summary-clickable" data-filter="DITOLAK"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_ditolak">0</span></h4><small style="font-weight:600;">Ditolak</small>
            </div></div>
          </div>
          <div class="col-md-3">
            <div class="panel panel-default summary-clickable" data-filter="SELISIH"><div class="panel-body text-center">
              <h4 style="margin:0;font-weight:bold;font-size:22px;"><span id="sum_selisih">0</span></h4><small style="font-weight:600;">Setujui Jubelio</small>
            </div></div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="datatable_jubelio" style="width:100%;">
            <thead>
              <tr>
                <th class="text-center" style="width:30px;"><input type="checkbox" id="check-all-rekon" title="Pilih/Batal Semua" /></th>
                <th>No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
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
                <th>Status Buka</th>
                <th>Ditolak Karena Apa</th>
                <th>Detail SKU</th>
                <th>SKU Pergantian</th>
                <th>Verifikasi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="clearfix" style="clear: both;"></div>
        <hr style="margin-top: 40px; margin-bottom: 20px; border-top: 2px solid #eee; clear: both;">
        <h4 class="panel-title" style="margin-bottom: 15px; font-weight: bold; clear: both;"><i class="fa fa-refresh"></i> Laporan Update Retur</h4>
        <div class="clearfix" style="clear: both;"></div>
        <div class="table-responsive" style="clear: both; width: 100%;">
          <table class="table table-striped table-bordered" id="datatable_jubelio_update" style="width:100%;">
            <thead>
              <tr>
                <th class="text-center" style="width:30px;"><input type="checkbox" id="check-all-rekon-update" title="Pilih/Batal Semua" /></th>
                <th>No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
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
                <th>Status Buka</th>
                <th>Ditolak Karena Apa</th>
                <th>Detail SKU</th>
                <th>SKU Pergantian</th>
                <th>Verifikasi</th>
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
        var visibleCheckboxes = $('.rekon-chk, .rekon-chk-update').length;
        $('#btn_pilih_semua').prop('disabled', visibleCheckboxes === 0);
        updateVerifSelectedCount();
    }

    // ==================== DATATABLE REKONSILIASI (client-side) ====================
    var tableJubelio = $('#datatable_jubelio').DataTable({
        'scrollX': true,
        'pageLength': 25,
        'lengthMenu': [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        'data': [],
        'columns': [
            { 'data': 'cb',  'orderable': false },
            { 'data': 0 }, { 'data': 1 }, { 'data': 2 }, { 'data': 3 },
            { 'data': 4 }, { 'data': 5 }, { 'data': 6 }, { 'data': 7 },
            { 'data': 8 }, { 'data': 9 }, { 'data': 10 }, { 'data': 11 },
            { 'data': 12 }, { 'data': 13 }, { 'data': 14 }, { 'data': 15 },
            { 'data': 16 }, { 'data': 17, 'orderable': false }
        ]
    });

    var tableJubelioUpdate = $('#datatable_jubelio_update').DataTable({
        'scrollX': true,
        'pageLength': 25,
        'lengthMenu': [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        'data': [],
        'columns': [
            { 'data': 'cb',  'orderable': false },
            { 'data': 0 }, { 'data': 1 }, { 'data': 2 }, { 'data': 3 },
            { 'data': 4 }, { 'data': 5 }, { 'data': 6 }, { 'data': 7 },
            { 'data': 8 }, { 'data': 9 }, { 'data': 10 }, { 'data': 11 },
            { 'data': 12 }, { 'data': 13 }, { 'data': 14 }, { 'data': 15 },
            { 'data': 16 }, { 'data': 17, 'orderable': false }
        ]
    });

    function updateVerifSelectedCount() {
        var count = $('.rekon-chk:checked').length + $('.rekon-chk-update:checked').length;
        $('#verif-selected-count').text(count);
        $('#btn_verifikasi_terpilih').prop('disabled', count === 0);
        // Update check-all state
        var all = $('.rekon-chk').not(':disabled');
        $('#check-all-rekon').prop('checked', all.length > 0 && all.length === $('.rekon-chk:checked').length);

        var allUpdate = $('.rekon-chk-update').not(':disabled');
        $('#check-all-rekon-update').prop('checked', allUpdate.length > 0 && allUpdate.length === $('.rekon-chk-update:checked').length);
    }

    function getJubelioFilter() {
        var dates = $('#rentang_waktu_jubelio').val().split(' s/d ');
        return {
            start_date: dates[0] || '',
            end_date: dates[1] || '',
            id_kurir: $('#kurir_jubelio').val(),
            kondisi: $('#kondisi_jubelio').val(),
            use_old: $('#use_old_jubelio').is(':checked') ? 1 : 0
        };
    }

    function loadJubelio() {
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
                activeFilter = 'TOTAL';
                $('.summary-clickable').removeClass('active-filter');
                $('[data-filter="TOTAL"]').addClass('active-filter');

                // Render tables
                tableJubelio.clear().rows.add(ALL_ROWS).draw();
                tableJubelioUpdate.clear().rows.add(ALL_ROWS_UPDATE).draw();

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

    $('#btn_tampilkan_jubelio').on('click', loadJubelio);

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
        $('.rekon-chk').prop('checked', isChecked);
        updateVerifSelectedCount();
    });

    $(document).on('change', '.rekon-chk', function() {
        updateVerifSelectedCount();
    });

    $('#check-all-rekon-update').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.rekon-chk-update').prop('checked', isChecked);
        updateVerifSelectedCount();
    });

    $(document).on('change', '.rekon-chk-update', function() {
        updateVerifSelectedCount();
    });

    // ==================== PILIH SEMUA BUTTON ====================
    $('#btn_pilih_semua').on('click', function() {
        var allChecked = $('.rekon-chk').not(':disabled').length === $('.rekon-chk:checked').length &&
                         $('.rekon-chk-update').not(':disabled').length === $('.rekon-chk-update:checked').length;
        $('.rekon-chk, .rekon-chk-update').prop('checked', !allChecked);
        updateVerifSelectedCount();
    });

    // ==================== VERIFIKASI TERPILIH ====================
    $('#btn_verifikasi_terpilih').on('click', function () {
        var resiList = [];
        $('.rekon-chk:checked, .rekon-chk-update:checked').each(function () {
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
                        loadJubelio();
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
                    loadJubelio();
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

<style>
#rentang_waktu_jubelio {
    background-color: #fff !important;
    cursor: pointer !important;
    color: #555 !important;
    border: 1px solid #ccc !important;
}
#rentang_waktu_jubelio:hover {
    border-color: #66afe9 !important;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6) !important;
}
</style>
