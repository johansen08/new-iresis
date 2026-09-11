<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Laporan Resi Preorder</strong></h3>
            </div>
            <div class="panel-body">
                <form id="form_report_preorder" action="<?= site_url('report/preorder_receipt_report') ?>" method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Filter Periode Batas Kirim</label>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59') ?>" />
                                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-info" id="btn-search"><span class="fa fa-search"></span> Cari</button>
                            <button type="submit" class="btn btn-success" id="btn-export-excel"><span class="fa fa-file-excel-o"></span> Export to Excel</button>
                        </div>
                    </div>
                </form>

                <hr>

                <style>
                    .qty-badge {
                        background: #e1f5fe;
                        color: #0288d1;
                        padding: 4px 10px;
                        border-radius: 12px;
                        font-weight: 600;
                    }
                    .resi-text {
                        font-family: 'Courier New', Courier, monospace;
                        font-weight: bold;
                        color: #333;
                    }
                </style>

                <div class="table-responsive">
                    <table id="datatable_report_preorder" class="table table-bordered table-striped" style="width: 100%;">
                        <thead>
                            <tr class="active">
                                <th colspan="3" class="text-right" style="vertical-align: middle;">GRAND TOTAL</th>
                                <th class="text-center" style="vertical-align: middle; background: #e8f5e9; color: #2e7d32; font-weight: bold; font-size: 14px;">RESI: <span id="total_resi_display">-</span></th>
                                <th class="text-center" style="vertical-align: middle; background: #e1f5fe; color: #0288d1; font-weight: bold; font-size: 14px;">QTY: <span id="grand_total_display">-</span></th>
                                <th></th>
                            </tr>
                            <tr class="active">
                                <th colspan="6">Daftar Resi Preorder</th>
                            </tr>
                            <tr>
                                <th width="50">No.</th>
                                <th>Nomor Resi</th>
                                <th>SKU</th>
                                <th>Nama Barang</th>
                                <th width="80" class="text-center">Qty</th>
                                <th>Batas Kirim</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function() {
    var itry = 0;
    var checkDeps = function() {
        if (window.jQuery && window.moment && window.jQuery.fn.DataTable && window.jQuery.fn.daterangepicker) {
            runSetup(window.jQuery);
        } else {
            if (itry < 60) {
                itry++;
                setTimeout(checkDeps, 100);
            }
        }
    };

    var runSetup = function(jq) {
        if (jq('#reportrange').data('daterangepicker')) return;

        var range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;
        var start = range ? moment(range.split(" - ")[0]) : moment().startOf('day');
        var end = range ? moment(range.split(" - ")[1]) : moment();

        jq('#reportrange').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment().startOf('day'), moment().endOf('day')],
                'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
            },
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        });

        <?php if($this->input->method() == 'post'): ?>
        jq('#datatable_report_preorder').DataTable({
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "order": [[1, 'asc']],
            "ajax": {
                "url": "<?= site_url('report/get_preorder_receipt_report_data') ?>",
                "type": "POST",
                "data": function(d) {
                    var r = jq('#reportrange').val();
                    d.reportrange = r;
                }
            },
            "drawCallback": function(settings) {
                var json = settings.json;
                var total = (json && typeof json.grandTotal !== 'undefined') ? json.grandTotal : '-';
                var resi = (json && typeof json.totalResi !== 'undefined') ? json.totalResi : '-';
                jq('#grand_total_display').text(total);
                jq('#total_resi_display').text(resi);
            },
            "columnDefs": [
                {
                    "targets": [0],
                    "orderable": false,
                },
                {
                    "targets": [1],
                    "render": function(data) {
                        return '<span class="resi-text">' + data + '</span>';
                    }
                },
                {
                    "targets": [4],
                    "className": "text-center",
                    "render": function(data) {
                        return '<span class="qty-badge">' + data + '</span>';
                    }
                }
            ],
        });
        <?php endif; ?>

        jq('#btn-search').on('click', function() {
            jq('#form_report_preorder').removeAttr("target");
            jq('#form_report_preorder').removeClass('nojs');
            jq('#form_report_preorder').attr('action', '<?= site_url('report/preorder_receipt_report') ?>');
        });

        jq('#btn-export-excel').on('click', function() {
            jq('#form_report_preorder').attr("target", "_blank");
            jq('#form_report_preorder').addClass('nojs');
            jq('#form_report_preorder').attr('action', '<?= site_url('report/export_to_excel_preorder_receipt_report') ?>');
        });
    };

    checkDeps();
})();
</script>
