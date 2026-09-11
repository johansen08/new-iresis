<form action="report/export-to-excel-production-team-report-tab0" class="form-horizontal nojs" method="post" target="_blank">
    <div class="form-group">
        <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
        <div class="col-md-3 col-xs-12">
            <input type="text" name="reportrange" id="reportrange-production-team-tab0" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 col-xs-12 control-label"></label>
        <div class="col-md-2 col-xs-12">
            <button type="button" class="btn btn-info" id="btn-search-production-team-tab0">Tampilkan</button>
            <button type="submit" class="btn btn-primary" id="btn-export-excel-production-team-tab0"><i class="fa fa-download"></i> Ekspor ke Excel</button>
        </div>
    </div>
</form>

<hr>

<!-- Loading Overlay -->
<div id="loading-overlay-tab0" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem; margin-bottom: 15px;">
            <span class="sr-only">Loading...</span>
        </div>
        <h4 style="margin: 0; color: #333;">Memuat Data Picker...</h4>
        <p style="margin: 10px 0 0 0; color: #666;">Mohon tunggu sebentar</p>
    </div>
</div>

<div style="position: relative;">
    <table class="table table-striped" id="datatable-production-team-tab0">
        <thead>
        <tr>
            <th colspan="4" style="text-align:right">Grand Total</th>
            <th id="grand-total-tab0">-</th>
            <th></th>
        </tr>
        <tr>
            <th>Pegawai</th>
            <th>Tanggal</th>
            <th>Jam Mulai</th>
            <th>Jam Selesai</th>
            <th>Jumlah</th>
            <th>Status</th>
        </tr>
        </thead>
    </table>
</div>

<script type="text/javascript">
    $('#reportrange-production-team-tab0').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        startDate: moment().startOf('day'),
        endDate: moment(),
        ranges: {
            'Today': [moment().startOf('day'), moment()],
            'Last 1 Hours': [moment().subtract(1, 'hours'), moment()],
            'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().startOf('day')],
            'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        },
    });

    // init for the first time
    var groupColumn = 0;
    var totalColumn = 4;
    var table_production_team_tab0 = $('#datatable-production-team-tab0').DataTable({
        dom: '<if<t>lp>',
        'destroy': true,
    });
    $('#btn-search-production-team-tab0').on('click', function() {
        // Show loading overlay
        $('#loading-overlay-tab0').css('display', 'flex');
        $('#btn-search-production-team-tab0').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        table_production_team_tab0 = $('#datatable-production-team-tab0').DataTable({
            dom: '<if<t>lp>',
            'destroy': true,
            'pageLength': 50,
            'processing': true,
            'serverSide': true,
            'order': [
                [0, 'asc']
            ],
            'lengthMenu': [
                [10, 50, 100, 150, 200],
                [10, 50, 100, 150, 200]
            ],
            'ajax': {
                url: 'report/get-production-team-report-data-tab0',
                type: 'POST',
                data: function(d) {
                    d.start_date = $('#reportrange-production-team-tab0').val().split(" - ")[0];
                    d.end_date = $('#reportrange-production-team-tab0').val().split(" - ")[1];
                }
            },
            'columns': [
                { data: 'pegawai' },
                { data: 'tanggal' },
                { data: 'jam_mulai' },
                { data: 'jam_selesai' },
                { data: 'total' },
                { data: 'status_performa' }
            ],
            'columnDefs': [
                {
                    targets: 0,
                    render: function (data, type, row) {
                        var roleName = row.role ? row.role.toLowerCase() : '';
                        var isInti = (roleName.indexOf('picker') !== -1);
                        var badgeClass = isInti ? 'label-primary' : 'label-default';
                        var badgeText  = isInti ? 'Picker Inti' : 'Perbantuan';
                        
                        return '<div style="display:flex; align-items:center; gap:8px;">' +
                               '<i class="fa fa-user-circle fa-2x text-muted" style="font-size:1.8em;"></i>' +
                               '<div>' +
                                  '<div style="font-weight:bold; font-size:14px;">' + data + '</div>' +
                                  '<span class="label ' + badgeClass + '" style="font-size:11px; margin-top:2px; display:inline-block;">' + badgeText + '</span>' +
                               '</div>' +
                               '</div>';
                    }
                },
                {
                    targets: [1, 2, 3],
                    className: 'text-center'
                },
                {
                    targets: 4,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return '<span class="badge btn-detail-picker" data-id-picker="' + row.id_picker + '" data-tanggal="' + row.tanggal_raw + '" data-nama-picker="' + row.pegawai + '" style="font-size:14px; background-color:#337ab7; cursor:pointer;" title="Klik untuk melihat detail">' + data + '</span>';
                    }
                },
                {
                    targets: 5,
                    className: 'text-center',
                    render: function (data) {
                        var color = '#777';
                        var text = data.toUpperCase();
                        if (text.includes('BAGUS') || text.includes('EXCELLENT')) color = '#5cb85c';
                        else if (text.includes('BURUK') || text.includes('LAMBAT')) color = '#d9534f';
                        else if (text !== 'TANPA STATUS') color = '#f0ad4e';
                        
                        return '<span class="label" style="background-color:' + color + '; font-size:12px;">' + data + '</span>';
                    }
                }
            ],
            'initComplete': function() {
                // Hide loading overlay when data loaded
                $('#loading-overlay-tab0').hide();
                $('#btn-search-production-team-tab0').prop('disabled', false).html('Tampilkan');
                
                // Update grand total
                var json = this.api().ajax.json();
                if (json && json.grandTotal !== undefined) {
                    $('#grand-total-tab0').text(json.grandTotal);
                }
            },
            'preDrawCallback': function() {
                // Show loading on pagination/sorting
                $('#loading-overlay-tab0').css('display', 'flex');
            },
            'drawCallback': function() {
                // Hide loading after draw
                $('#loading-overlay-tab0').hide();
                
                // Update grand total setiap kali draw
                var json = this.api().ajax.json();
                if (json && json.grandTotal !== undefined) {
                    $('#grand-total-tab0').text(json.grandTotal);
                }
            }
        });
    });

    // Global variables to store active picker info
    var activePickerId = null;
    var activeTanggal = null;
    var activePickerName = '';
    var activeCategory = '';
    var activeCategoryLabel = '';

    // Click on "Jumlah" Badge
    $(document).on('click', '.btn-detail-picker', function() {
        activePickerId = $(this).data('id-picker');
        activeTanggal = $(this).data('tanggal');
        activePickerName = $(this).data('nama-picker');

        $('#summary-picker-name').text(activePickerName);
        $('#summary-date').text(moment(activeTanggal).format('DD-MM-YYYY'));

        // Reset badges
        $('#badge-sku_special').text('...');
        $('#badge-resi_1_sku_sd_9').text('...');
        $('#badge-resi_2_9_sku_sd_9').text('...');
        $('#badge-resi_qty_banyak').text('...');

        $('#modal-picker-summary').modal('show');

        // Fetch summary via AJAX
        $.ajax({
            url: 'report/get-picker-performance-detail-summary',
            type: 'POST',
            data: {
                id_picker: activePickerId,
                tanggal: activeTanggal
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    var d = response.data;
                    $('#badge-sku_special').text(d.sku_special || 0);
                    $('#badge-resi_1_sku_sd_9').text(d.resi_1_sku_sd_9 || 0);
                    $('#badge-resi_2_9_sku_sd_9').text(d.resi_2_9_sku_sd_9 || 0);
                    $('#badge-resi_qty_banyak').text(d.resi_qty_banyak || 0);
                } else {
                    alert('Gagal mengambil data ringkasan picker.');
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menghubungi server.');
            }
        });
    });

    // Click on category item in Summary Modal (Opens SKU Summary Modal)
    $(document).on('click', '.btn-show-category-list', function(e) {
        e.preventDefault();
        activeCategory = $(this).data('category');
        activeCategoryLabel = $(this).find('strong').length ? $(this).find('strong').text() : $(this).contents().filter(function() {
            return this.nodeType === 3;
        }).text().trim();

        $('#sku-summary-category-title').text(activeCategoryLabel);
        $('#sku-summary-picker-name').text(activePickerName);
        $('#sku-summary-date').text(moment(activeTanggal).format('DD-MM-YYYY'));

        // Set href for SKU summary export
        $('#btn-export-picker-sku-summary').attr('href', 'report/export-picker-sku-summary?id_picker=' + activePickerId + '&tanggal=' + activeTanggal + '&category=' + activeCategory);

        $('#picker-sku-summary-body').html('<tr><td colspan="4" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat rangkuman SKU...</td></tr>');

        // Hide category summary modal and show SKU summary modal
        $('#modal-picker-summary').modal('hide');
        $('#modal-picker-sku-summary').modal('show');

        // Fetch SKU summary via AJAX
        $.ajax({
            url: 'report/get-picker-sku-summary',
            type: 'POST',
            data: {
                id_picker: activePickerId,
                tanggal: activeTanggal,
                category: activeCategory
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    var html = '';
                    if (response.data.length > 0) {
                        response.data.forEach(function(item, index) {
                            html += '<tr>' +
                                '<td class="text-center">' + (index + 1) + '</td>' +
                                '<td><a href="#" class="btn-show-resi-by-sku" data-id-sku="' + item.id_sku + '" data-nama-sku="' + item.id_sku + '" style="font-weight:bold; color:#337ab7; text-decoration:underline;">' + item.id_sku + '</a></td>' +
                                '<td class="text-center"><span class="badge" style="background-color:#5cb85c; font-size:12px;">' + item.total_qty + '</span></td>' +
                                '<td class="text-center"><span class="badge" style="background-color:#5bc0de; font-size:12px;">' + item.resi_count + '</span></td>' +
                                '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="4" class="text-center">Tidak ada data SKU dalam kategori ini.</td></tr>';
                    }
                    $('#picker-sku-summary-body').html(html);
                } else {
                    $('#picker-sku-summary-body').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat data rangkuman SKU.</td></tr>');
                }
            },
            error: function() {
                $('#picker-sku-summary-body').html('<tr><td colspan="4" class="text-center text-danger">Terjadi kesalahan saat menghubungi server.</td></tr>');
            }
        });
    });

    // Click on SKU link in SKU Summary Modal (Opens Receipt List Modal)
    $(document).on('click', '.btn-show-resi-by-sku', function(e) {
        e.preventDefault();
        var idSku = $(this).data('id-sku');
        var namaSku = $(this).data('nama-sku');

        $('#list-sku-name').text(namaSku);
        $('#list-picker-name').text(activePickerName);
        $('#list-date').text(moment(activeTanggal).format('DD-MM-YYYY'));
        $('#list-category-title').text(activeCategoryLabel);

        // Set href for receipt list export
        $('#btn-export-picker-resi-list').attr('href', 'report/export-picker-resi-list-by-sku?id_picker=' + activePickerId + '&tanggal=' + activeTanggal + '&category=' + activeCategory + '&id_sku=' + idSku);

        $('#picker-resi-list-body').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat daftar resi...</td></tr>');

        // Hide SKU summary modal and show receipt list modal
        $('#modal-picker-sku-summary').modal('hide');
        $('#modal-picker-resi-list').modal('show');

        // Fetch receipt list via AJAX
        $.ajax({
            url: 'report/get-picker-resi-list-by-sku',
            type: 'POST',
            data: {
                id_picker: activePickerId,
                tanggal: activeTanggal,
                category: activeCategory,
                id_sku: idSku
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    var html = '';
                    if (response.data.length > 0) {
                        response.data.forEach(function(item, index) {
                            html += '<tr>' +
                                '<td class="text-center">' + (index + 1) + '</td>' +
                                '<td>' + item.noresi + '</td>' +
                                '<td>' + item.tanggal_bataskirim + '</td>' +
                                '<td class="text-center"><span class="label label-info">' + item.status_pesanan + '</span></td>' +
                                '<td>' + item.detail_barang + '</td>' +
                                '<td class="text-center"><span class="badge" style="background-color:#5bc0de;">' + item.total_qty + '</span></td>' +
                                '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="6" class="text-center">Tidak ada resi untuk SKU ini.</td></tr>';
                    }
                    $('#picker-resi-list-body').html(html);
                } else {
                    $('#picker-resi-list-body').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data resi.</td></tr>');
                }
            },
            error: function() {
                $('#picker-resi-list-body').html('<tr><td colspan="6" class="text-center text-danger">Terjadi kesalahan saat menghubungi server.</td></tr>');
            }
        });
    });

    // Click "Kembali" on SKU Summary Modal (Goes back to Categories Modal)
    $('#btn-back-to-categories').on('click', function() {
        $('#modal-picker-sku-summary').modal('hide');
        $('#modal-picker-summary').modal('show');
    });

    // Click "Kembali" on Receipt List Modal (Goes back to SKU Summary Modal)
    $('#btn-back-to-sku-summary').on('click', function() {
        $('#modal-picker-resi-list').modal('hide');
        $('#modal-picker-sku-summary').modal('show');
    });
</script>

<!-- Modal 1: Picker Categories Summary -->
<div class="modal fade" id="modal-picker-summary" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Detail Kinerja Picker: <span id="summary-picker-name" style="font-weight:bold;"></span></h4>
            </div>
            <div class="modal-body">
                <p>Tanggal: <strong id="summary-date"></strong></p>
                <p class="text-muted">Pilih kategori di bawah untuk melihat rangkuman SKU:</p>
                <div class="list-group">
                    <a href="#" class="list-group-item btn-show-category-list" data-category="sku_special">
                        <span class="badge" id="badge-sku_special" style="font-size: 14px; background-color: #d9534f;">0</span>
                        <strong>RESI SPECIAL</strong>
                    </a>
                    <a href="#" class="list-group-item btn-show-category-list" data-category="resi_1_sku_sd_9">
                        <span class="badge" id="badge-resi_1_sku_sd_9" style="font-size: 14px; background-color: #f0ad4e;">0</span>
                        1 SKU & QTY S/D 9
                    </a>
                    <a href="#" class="list-group-item btn-show-category-list" data-category="resi_2_9_sku_sd_9">
                        <span class="badge" id="badge-resi_2_9_sku_sd_9" style="font-size: 14px; background-color: #5bc0de;">0</span>
                        2-9 SKU & QTY S/D 9
                    </a>
                    <a href="#" class="list-group-item btn-show-category-list" data-category="resi_qty_banyak">
                        <span class="badge" id="badge-resi_qty_banyak" style="font-size: 14px; background-color: #337ab7;">0</span>
                        QTY BANYAK (>9)
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Picker SKU Summary -->
<div class="modal fade" id="modal-picker-sku-summary" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Rangkuman SKU <span id="sku-summary-category-title" style="font-weight:bold;"></span></h4>
                <h5 class="text-muted">Picker: <span id="sku-summary-picker-name"></span> | Tanggal: <span id="sku-summary-date"></span></h5>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto; padding: 0;">
                <table class="table table-bordered table-striped" style="margin-bottom:0;">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr>
                            <th style="width: 5%;" class="text-center">No</th>
                            <th style="width: 65%;">SKU (Klik untuk detail resi)</th>
                            <th style="width: 15%;" class="text-center">Total Qty</th>
                            <th style="width: 15%;" class="text-center">Total Resi</th>
                        </tr>
                    </thead>
                    <tbody id="picker-sku-summary-body">
                        <tr>
                            <td colspan="4" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-success" id="btn-export-picker-sku-summary"><i class="fa fa-download"></i> Ekspor ke Excel</a>
                <button type="button" class="btn btn-info" id="btn-back-to-categories"><i class="fa fa-arrow-left"></i> Kembali</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Picker Receipt List by SKU -->
<div class="modal fade" id="modal-picker-resi-list" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Daftar Resi SKU: <span id="list-sku-name" style="font-weight:bold;"></span></h4>
                <h5 class="text-muted">Picker: <span id="list-picker-name"></span> | Tanggal: <span id="list-date"></span> | Kategori: <span id="list-category-title"></span></h5>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto; padding: 0;">
                <table class="table table-bordered table-striped" style="margin-bottom:0;">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr>
                            <th style="width: 5%;" class="text-center">No</th>
                            <th style="width: 20%;">No. Resi</th>
                            <th style="width: 20%;">Batas Kirim</th>
                            <th style="width: 15%;" class="text-center">Status Pesanan</th>
                            <th style="width: 30%;">Detail Barang (SKU & Qty)</th>
                            <th style="width: 10%;" class="text-center">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody id="picker-resi-list-body">
                        <tr>
                            <td colspan="6" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-success" id="btn-export-picker-resi-list"><i class="fa fa-download"></i> Ekspor ke Excel</a>
                <button type="button" class="btn btn-info" id="btn-back-to-sku-summary"><i class="fa fa-arrow-left"></i> Kembali</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>