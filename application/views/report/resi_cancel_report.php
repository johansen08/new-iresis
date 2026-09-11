<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>LAPORAN RESI CANCEL</strong></h3>
            </div>
            <div class="panel-body">
                <form action="<?= base_url('report/export-excel-resi-cancel') ?>" class="form-horizontal nojs" method="post" id="form-report-cancel">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang Waktu</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="reportrange" id="reportrange" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label"></label>
                        <div class="col-md-4 col-xs-12">
                            <button type="button" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Cari</button>
                            <button type="submit" class="btn btn-primary" id="btn-export"><i class="fa fa-file-excel-o"></i> Excel</button>
                        </div>
                    </div>
                </form>
                <hr>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="table-cancel">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Marketplace</th>
                                <th>Tgl Print Resi</th>
                                <th>No Resi</th>
                                <th>Kurir</th>
                                <th>Alasan Batal / Status</th>
                                <th>Picker</th>
                                <th>Packer</th>
                                <th>Scan By HO</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#reportrange').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: moment().startOf('day'),
            endDate: moment(),
            ranges: {
                'Today': [moment().startOf('day'), moment()],
                'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')]
            },
            locale: { format: 'YYYY-MM-DD HH:mm:ss' }
        });

        const table = $('#table-cancel').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url("report/get_resi_cancel_report_data") ?>',
                type: 'POST',
                data: function(d) {
                    const range = $('#reportrange').val().split(' - ');
                    d.start_date = range[0];
                    d.end_date = range[1];
                }
            },
            columns: [
                { data: 0 },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4 },
                { data: 5 },
                { data: 6 },
                { data: 7 },
                { data: 8 }
            ]
        });

        $('#btn-search').click(function() {
            table.draw();
        });
    });
</script>
