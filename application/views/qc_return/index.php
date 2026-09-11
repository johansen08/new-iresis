<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Laporan Pengembalian Barang QC</h3>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" id="reportrange" class="form-control" value="<?= date('Y-m-d') ?> - <?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_status" class="form-control select">
                            <option value="">- Semua Status -</option>
                            <option value="PENDING">PENDING</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="REJECTED">REJECTED</option>
                            <option value="GIVEAWAY">GIVEAWAY</option>
                            <option value="BARANG_TIDAK_ADA">BARANG TIDAK ADA</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_kondisi" class="form-control select">
                            <option value="">- Semua Kondisi -</option>
                            <option value="LEBIH AMBIL">LEBIH AMBIL</option>
                            <option value="REJECT">REJECT</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" onclick="refreshTable()"><i class="fa fa-search"></i> Filter</button>
                        <button class="btn btn-info" onclick="exportToExcel()"><i class="fa fa-file-excel-o"></i> Export</button>
                    </div>
                    <div class="col-md-3 text-right">
                        <a href="qc_return/add" class="btn btn-success link"><i class="fa fa-plus"></i> Submit Pengembalian</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="table_qc" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>No Rak</th>
                                <th>Kondisi</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Acc By</th>
                                <th>Aksi</th>
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
        $('#reportrange').daterangepicker({
            timePicker: false,
            autoUpdateInput: true,
            ranges: {
                'Hari Ini': [moment().startOf('day'), moment()],
                'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment()],
                '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment()],
                'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                customRangeLabel: 'Custom',
                weekLabel: 'M',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            startDate: moment(),
            endDate: moment()
        });

        table = $('#table_qc').DataTable({
            "processing": true,
            "ajax": {
                "url": "qc_return/get_data",
                "type": "POST",
                "data": function(d) {
                    var range = $('#reportrange').val().split(' - ');
                    d.start_date = range[0];
                    d.end_date = range[1];
                    d.status = $('#filter_status').val();
                    d.kondisi = $('#filter_kondisi').val();
                }
            },
            "columnDefs": [{
                "targets": [7],
                "render": function(data, type, row) {
                    if (data == 'PENDING') return '<span class="label label-warning">PENDING</span>';
                    if (data == 'APPROVED') return '<span class="label label-success">APPROVED</span>';
                    if (data == 'REJECTED') return '<span class="label label-danger">REJECTED</span>';
                    if (data == 'GIVEAWAY') return '<span class="label label-info">GIVEAWAY</span>';
                    if (data == 'BARANG_TIDAK_ADA') return '<span class="label label-default">BARANG TIDAK ADA</span>';
                    return data;
                }
            }, {
                "targets": [9],
                "orderable": false,
                "searchable": false
            }]
        });
    });

    function refreshTable() {
        table.ajax.reload();
    }

    function exportToExcel() {
        var range = $('#reportrange').val().split(' - ');
        var start_date = range[0];
        var end_date = range[1];
        var status = $('#filter_status').val();
        var kondisi = $('#filter_kondisi').val();

        var url = 'qc_return/export_to_excel?start_date=' + start_date + '&end_date=' + end_date + '&status=' + status + '&kondisi=' + kondisi;
        window.location.href = url;
    }

    function deleteData(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: 'qc_return/delete',
                type: 'POST',
                data: {
                    id: id
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.success) {
                        refreshTable();
                        noty({
                            text: 'Data berhasil dihapus',
                            layout: 'topRight',
                            type: 'success',
                            timeout: 3000
                        });
                    } else {
                        noty({
                            text: 'Gagal menghapus data',
                            layout: 'topRight',
                            type: 'error',
                            timeout: 3000
                        });
                    }
                }
            });
        }
    }
</script>

<style>
    #reportrange {
        background-color: #fff !important;
        cursor: pointer !important;
        color: #555 !important;
        border: 1px solid #ccc !important;
    }

    #reportrange:hover {
        border-color: #66afe9 !important;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6) !important;
    }
</style>
