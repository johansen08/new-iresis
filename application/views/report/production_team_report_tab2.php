<style>
    .ptr-toolbar { display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; margin-bottom: 6px; }
    .ptr-toolbar label { margin: 0; font-weight: 600; }
    .ptr-toolbar .ptr-range { flex: 0 1 340px; width: 340px; min-width: 190px; height: 30px; }
    .ptr-toolbar label, .ptr-toolbar .btn, .ptr-total { white-space: nowrap; flex: 0 0 auto; }
    .ptr-toolbar .btn { padding: 4px 12px; }
    .ptr-total { margin-left: auto; display: flex; align-items: baseline; gap: 8px; padding: 2px 14px; background: #f4f8fc; border-left: 4px solid #337ab7; border-radius: 3px; }
    .ptr-total-label { color: #666; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; }
    .ptr-total-angka { font-size: 22px; font-weight: 700; color: #1f5f99; line-height: 1.3; }
    .ptr-tabel table.dataTable tbody td, .ptr-tabel .dataTables_scrollHead th { padding: 3px 8px; vertical-align: middle; } 
    .ptr-tabel table.dataTable tbody td { white-space: nowrap; }
    .ptr-tabel .dataTables_scrollBody thead tr { height: 0; }
    .ptr-tabel .dataTables_scrollBody thead th { padding: 0 8px !important; border: 0; line-height: 0; font-size: 0; height: 0 !important; border-width: 0 !important; }
    .ptr-tabel .dataTables_scrollBody thead th::before, .ptr-tabel .dataTables_scrollBody thead th::after { display: none !important; }
    .ptr-tabel .dataTables_scrollBody thead th .dataTables_sizing { height: 0; overflow: hidden; }
    .ptr-tabel .dataTables_wrapper .dataTables_info { padding-top: 0; }
    .ptr-tabel .dataTables_scrollBody { border-bottom: 1px solid #ddd; overflow-x: hidden !important; }
    .ptr-tabel .dataTables_scrollBody table { margin-bottom: 0 !important; margin-top: -1px !important; }
    .ptr-pegawai { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .ptr-pegawai .label { font-size: 10px; }
    .ptr-pegawai .label-success { background-color: #4caf50; }
    .ptr-pegawai .label-warning { background-color: #fbc02d; color: #4a3500; }
    .ptr-pegawai .label-pink { background-color: #e91e63; color: #fff; }
    /* Perataan tepi: toolbar, kartu total, dan tabel berbagi tepi kiri/kanan yang sama */
    .ptr-toolbar { margin-bottom: 8px; }
    .ptr-total { height: 30px; padding: 0 12px; align-items: center; }
    .ptr-total-angka { font-size: 20px; line-height: 1; }
    .ptr-tabel .dataTables_info { float: left; padding: 0; line-height: 28px; }
    .ptr-tabel .dataTables_filter { float: right; margin: 0 0 6px; }
    .ptr-tabel .dataTables_filter label { margin: 0; display: flex; align-items: center; gap: 6px; }
    .ptr-tabel .dataTables_filter input { height: 28px; margin: 0; }
    .ptr-tabel .dataTables_scroll { clear: both; border: 1px solid #ddd; border-radius: 3px; overflow: hidden; }
    .ptr-tabel .dataTables_scrollBody { border-bottom: 0; }
    .ptr-tabel .dataTables_scrollHead { border-bottom: 1px solid #ddd; }
    .ptr-tabel .dataTables_length { float: left; margin-top: 6px; }
    .ptr-tabel .dataTables_length label { margin: 0; display: flex; align-items: center; gap: 6px; }
    .ptr-tabel .dataTables_length select { height: 28px; margin: 0; width: 64px; }
    .ptr-tabel .dataTables_paginate { float: right; margin-top: 6px; }
    .ptr-tabel .dataTables_paginate .pagination { margin: 0; }
</style>

<form action="report/export-to-excel-production-team-report-tab2" class="form-horizontal nojs" method="post" target="_blank">
    <div class="ptr-toolbar">
        <label for="reportrange-production-team-tab2">Rentang waktu</label>
        <input type="text" name="reportrange" id="reportrange-production-team-tab2" class="form-control ptr-range" />
        <button type="button" class="btn btn-info" id="btn-search-production-team-tab2"><i class="fa fa-refresh"></i> Tampilkan</button>
        <button type="submit" class="btn btn-primary" id="btn-export-excel-production-team-tab2"><i class="fa fa-download"></i> Ekspor ke Excel</button>
        <div class="ptr-total" id="ptr-total-tab2"><span class="ptr-total-label">Total</span><span class="ptr-total-angka" id="grand-total-tab2">-</span></div>
    </div>
</form>

<!-- Loading Overlay -->
<div id="loading-overlay-tab2" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem; margin-bottom: 15px;">
            <span class="sr-only">Loading...</span>
        </div>
        <h4 style="margin: 0; color: #333;">Memuat Data Handover (HO)...</h4>
        <p style="margin: 10px 0 0 0; color: #666;">Mohon tunggu sebentar</p>
    </div>
</div>

<div class="ptr-tabel" style="position: relative;">
    <table class="table table-striped" id="datatable-production-team-tab2">
        <thead>
        <tr>
            <th>Pegawai</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Jumlah</th>
            <th>Status</th>
        </tr>
        </thead>
    </table>
</div>

<script type="text/javascript">
    $('#reportrange-production-team-tab2').daterangepicker({
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
    var totalColumn = 2;
    var table_production_team_tab2 = $('#datatable-production-team-tab2').DataTable({
        dom: '<if<t>lp>',
        'destroy': true,
    });
    $('#btn-search-production-team-tab2').on('click', function() {
        // Show loading overlay
        $('#loading-overlay-tab2').css('display', 'flex');
        $('#btn-search-production-team-tab2').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        table_production_team_tab2 = $('#datatable-production-team-tab2').DataTable({
            dom: '<if<t>lp>',
            'destroy': true,
            'pageLength': 50,
            'scrollY': 'calc(100vh - 270px)',
            'scrollCollapse': true,
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
                url: 'report/get-production-team-report-data-tab2',
                type: 'POST',
                data: function(d) {
                    d.start_date = $('#reportrange-production-team-tab2').val().split(" - ")[0];
                    d.end_date = $('#reportrange-production-team-tab2').val().split(" - ")[1];
                }
            },
            'columns': [
                { data: 'pegawai' },
                { data: 'tanggal' },
                { data: 'tanggal' },
                { data: 'total' },
                { data: 'status_performa' }
            ],
            'columnDefs': [
                {
                    targets: 0,
                    render: function (data, type, row) {
                        // Assuming role for HO is stored as "ho", "admin", "gudang", etc.
                        var roleName = row.role ? row.role.toLowerCase() : '';
                        var isInti = (roleName.indexOf('ho') !== -1 || roleName.indexOf('admin') !== -1);
                        var badgeClass = isInti ? 'label-pink' : 'label-default';
                        var badgeText  = isInti ? 'HO Inti' : 'Perbantuan';
                        
                        return '<div class="ptr-pegawai"><i class="fa fa-user-circle text-muted"></i><strong>' + data + '</strong><span class="label ' + badgeClass + '">' + badgeText + '</span></div>';
                    }
                },
                {
                    targets: 1,
                    className: 'text-center',
                    render: function (data) { return String(data).split(' ')[0]; }
                },
                {
                    targets: 2,
                    className: 'text-center',
                    render: function (data) { return String(data).split(' ')[1] || '-'; }
                },
                {
                    targets: 3,
                    className: 'text-center',
                    render: function (data) {
                        return '<span class="badge" style="font-size:14px; background-color:#337ab7;">' + data + '</span>';
                    }
                },
                {
                    targets: 4,
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
                $('#loading-overlay-tab2').hide();
                $('#btn-search-production-team-tab2').prop('disabled', false).html('Tampilkan');
                
                // Update grand total
                var json = this.api().ajax.json();
                if (json && json.grandTotal !== undefined) {
                    $('#grand-total-tab2').text(json.grandTotal);
                }
            },
            'preDrawCallback': function() {
                // Show loading on pagination/sorting
                $('#loading-overlay-tab2').css('display', 'flex');
            },
            'drawCallback': function() {
                $('#datatable-production-team-tab2').DataTable().columns.adjust();

                // Kolom Status disembunyikan bila semua baris "Tanpa Status"
                var api = this.api();
                var adaStatus = api.column(4).data().toArray().some(function(v) {
                    return String(v).toUpperCase() !== 'TANPA STATUS';
                });
                api.column(4).visible(adaStatus, false);
                // Hide loading after draw
                $('#loading-overlay-tab2').hide();
                
                // Update grand total setiap kali draw
                var json = this.api().ajax.json();
                if (json && json.grandTotal !== undefined) {
                    $('#grand-total-tab2').text(json.grandTotal);
                }
            }
        });
    });

    // Langsung tampilkan data hari ini tanpa perlu klik Tampilkan
    $('#btn-search-production-team-tab2').trigger('click');
</script>
