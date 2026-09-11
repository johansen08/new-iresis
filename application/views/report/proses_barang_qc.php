<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Laporan Proses Barang QC</h3>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <label>Rentang Tanggal</label>
                        <input type="text" id="reportrange" class="form-control" readonly style="background: #fff; cursor: pointer;">
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select id="filter_status" class="form-control select">
                            <option value="">- Semua Status -</option>
                            <option value="PENDING">PENDING</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="REJECTED">REJECTED / TOLAK</option>
                            <option value="REPAIR">REPAIR / PERBAIKAN</option>
                            <option value="GIVEAWAY">GIVEAWAY</option>
                            <option value="BARANG_TIDAK_ADA">BARANG TIDAK ADA</option>
                        </select>
                    </div>
                    <div class="col-md-2" style="margin-top: 25px;">
                        <button class="btn btn-primary" onclick="reloadData()"><i class="fa fa-refresh"></i> Filter</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="table_proses_qc" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Tgl Masuk</th>
                                <th>Tgl Proses</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>No Rak</th>
                                <th>Kondisi Awal</th>
                                <th>Status Support</th>
                                <th>Tracking / Posisi Barang</th>
                                <th>Keterangan</th>
                                <th>No. Penyesuaian</th>
                                <th>Foto Bukti</th>
                                <th>Di-acc Oleh</th>
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
    var table;
    $(document).ready(function() {
        var start = moment().subtract(29, 'days');
        var end = moment();

        function cb(start, end) {
            $('#reportrange').val(start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss'));
        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
               'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
               'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
               '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
               '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        }, cb);

        cb(start, end);

        table = $('#table_proses_qc').DataTable({
            "processing": true,
            "ajax": {
                "url": "report/get_proses_barang_qc_data",
                "type": "POST",
                "data": function(d) {
                    d.reportrange = $('#reportrange').val();
                    d.status = $('#filter_status').val();
                }
            },
            "order": [[1, "desc"]],
            "columnDefs": [
                {
                    "targets": 8,
                    "render": function(data, type, row) {
                        if (data == 'PENDING') return '<span class="label label-warning">PENDING</span>';
                        if (data == 'APPROVED') return '<span class="label label-success">APPROVED</span>';
                        if (data == 'REJECTED') return '<span class="label label-danger">REJECTED / TOLAK</span>';
                        if (data == 'REPAIR') return '<span class="label label-primary">REPAIR</span>';
                        if (data == 'GIVEAWAY') return '<span class="label label-info">GIVEAWAY</span>';
                        if (data == 'BARANG_TIDAK_ADA') return '<span class="label label-default">TIDAK ADA</span>';
                        return data;
                    }
                }
            ]
        });
    });

    function reloadData() {
        table.ajax.reload();
    }
</script>
