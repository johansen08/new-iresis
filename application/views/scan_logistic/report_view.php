<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong><?= $title ?></strong></h3>
                <ul class="panel-controls">
                    <li><a href="#" class="panel-collapse"><span class="fa fa-angle-down"></span></a></li>
                    <li><a href="#" class="panel-refresh"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <form class="form-horizontal">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-md-4 control-label">Rentang Waktu</label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : null ?>" />
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="button" id="btn_filter" class="btn btn-info">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button type="button" id="btn_export" class="btn btn-success">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr>

                <div class="table-responsive">
                    <table id="table_scan" class="table table-bordered table-striped table-actions" style="width: 100%;">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>No Resi</th>
                                <th>Marketplace</th>
                                <th>Kurir</th>
                                <th>Waktu Scan</th>
                                <th>Admin Scan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Exact same initialization as daily_receipt_report
    var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;
    var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
    var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment();

    if ($.fn.daterangepicker) {
        $('#reportrange').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: start,
            endDate: end,
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
    }

    var table = $('#table_scan').DataTable({
        "processing": true,
        "serverSide": true,
        "order": [[4, "desc"]],
        "ajax": {
            "url": "scan_logistic/get_data",
            "type": "POST",
            "data": function(d) {
                d.reportrange = $('#reportrange').val();
            }
        },
        "columnDefs": [
            { "targets": 0, "orderable": false },
            { "targets": 4, "className": "text-center" },
            { "targets": 5, "className": "text-center" }
        ]
    });

    $('#btn_filter').click(function() {
        table.ajax.reload();
    });

    $('#btn_export').click(function() {
        var reportrange = $('#reportrange').val();
        window.location.href = "scan_logistic/export_excel?reportrange=" + encodeURIComponent(reportrange);
    });
});
</script>
