<form action="report/export-to-excel-production-team-report-tab2" class="form-horizontal nojs" method="post" target="_blank">
    <div class="form-group">
        <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
        <div class="col-md-3 col-xs-12">
            <input type="text" name="reportrange" id="reportrange-production-team-tab2" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 col-xs-12 control-label"></label>
        <div class="col-md-2 col-xs-12">
            <button type="button" class="btn btn-info" id="btn-search-production-team-tab2">Tampilkan</button>
            <button type="submit" class="btn btn-primary" id="btn-export-excel-production-team-tab2"><i class="fa fa-download"></i> Ekspor ke Excel</button>
        </div>
    </div>
</form>

<hr>

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

<div style="position: relative;">
    <table class="table table-striped" id="datatable-production-team-tab2">
        <thead>
        <tr>
            <th colspan="2" style="text-align:right">Grand Total</th>
            <th id="grand-total-tab2">-</th>
        </tr>
        <tr>
            <th>Pegawai</th>
            <th>Tanggal</th>
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
                        var badgeClass = isInti ? 'label-info' : 'label-default';
                        var badgeText  = isInti ? 'HO Inti' : 'Perbantuan';
                        
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
                    targets: 1,
                    className: 'text-center'
                },
                {
                    targets: 2,
                    className: 'text-center',
                    render: function (data) {
                        return '<span class="badge" style="font-size:14px; background-color:#337ab7;">' + data + '</span>';
                    }
                },
                {
                    targets: 3,
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
</script>
