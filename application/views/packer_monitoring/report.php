<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Laporan Detail Speed Packer</strong></h3>
            </div>
            <div class="panel-body">
                <form id="form-filter" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-md-4 control-label">Mulai</label>
                                <div class="col-md-8">
                                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-md-4 control-label">Selesai</label>
                                <div class="col-md-8">
                                    <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-md-4 control-label">Packer</label>
                                <div class="col-md-8">
                                    <select name="user_id" class="form-control select" data-live-search="true">
                                        <option value="">Semua Packer</option>
                                        <?php foreach ($list_packer as $packer) : ?>
                                            <option value="<?= $packer['id_user'] ?>"><?= $packer['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="col-md-4 control-label">Status</label>
                                <div class="col-md-8">
                                    <select name="is_slow" class="form-control">
                                        <option value="">Semua</option>
                                        <option value="1">Melambat</option>
                                        <option value="0">Normal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" id="btn-filter" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="table-report">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Packer</th>
                                <th>No Resi</th>
                                <th>SKU (Qty)</th>
                                <th>Type</th>
                                <th>Durasi</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Komentar</th>
                                <th>Abaikan</th>
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
    var tableReport = $('#table-report').DataTable({
        "ajax": {
            "url": "<?= base_url('packer_monitoring/get_report_data') ?>",
            "type": "POST",
            "data": function(d) {
                d.start_date = $('input[name="start_date"]').val();
                d.end_date = $('input[name="end_date"]').val();
                d.user_id = $('select[name="user_id"]').val();
                d.is_slow = $('select[name="is_slow"]').val();
            }
        },
        "order": [[0, "desc"]],
        "columns": [
            { "data": "tanggal_packing" },
            { "data": "nama_packer" },
            { "data": "noresi" },
            { "data": "sku_qty", "defaultContent": "-" },
            { "data": "status_performa" },
            { 
                "data": "durasi_aktual",
                "render": function(data) { return data + 's'; }
            },
            { 
                "data": "durasi_target",
                "render": function(data) { return data + 's'; }
            },
            { 
                "data": "is_slow",
                "render": function(data) {
                    return data == 1 ? '<span class="label label-danger">Melambat</span>' : '<span class="label label-success">OK</span>';
                }
            },
            { "data": "komentar", "defaultContent": "-" },
            { 
                "data": "is_deleted",
                "render": function(data) {
                    return data == 1 ? '<span class="label label-info">Ya</span>' : 'Tidak';
                }
            }
        ]
    });

    $('#btn-filter').click(function() {
        tableReport.ajax.reload();
    });
});
</script>
