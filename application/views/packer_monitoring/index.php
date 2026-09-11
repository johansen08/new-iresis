<div class="row">
    <!-- Summary Widgets -->
    <div class="col-md-4">
        <div class="widget widget-danger widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-exclamation-triangle"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-slow">0</div>
                <div class="widget-title">Total Scan Melambat</div>
                <div class="widget-subtitle">Hari ini (Belum diabaikan)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget widget-warning widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-users"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-slow-persons">0</div>
                <div class="widget-title">Packer Melambat</div>
                <div class="widget-subtitle">Orang yang butuh perhatian</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget widget-success widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-check-circle"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-active">0</div>
                <div class="widget-title">Total Packer Aktif</div>
                <div class="widget-subtitle">Sudah Check-in hari ini</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Monitoring Speed Packer (Real-time)</strong></h3>
                <div class="pull-right">
                    <button class="btn btn-info" id="btn-refresh"><i class="fa fa-refresh"></i> Refresh</button>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="table-monitoring">
                        <thead>
                            <tr>
                                <th>Packer</th>
                                <th>Masuk</th>
                                <th>Status</th>
                                <th>Total Scan</th>
                                <th>Melambat (Slow)</th>
                                <th>Istirahat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Logs -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detail Log Performance: <span id="detail-packer-name"></span></h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-condensed table-bordered" id="table-detail-logs">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>No Resi</th>
                                <th>SKU (Qty)</th>
                                <th>Type</th>
                                <th>Durasi</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Komentar</th>
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

<!-- Modal Comment -->
<div class="modal fade" id="modal-comment" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Beri Komentar</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="comment-log-id">
                <textarea id="comment-text" class="form-control" rows="3" placeholder="Kenapa melambat?"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-comment">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var tableMonitoring = $('#table-monitoring').DataTable({
        "ajax": "<?= base_url('packer_monitoring/get_monitoring_data') ?>",
        "fnInitComplete": function(oSettings, json) {
            updateStats(json.stats);
        },
        "drawCallback": function(settings) {
            var json = settings.json;
            if (json && json.stats) {
                updateStats(json.stats);
            }
        },
        "columns": [
            { "data": "nama_packer" },
            { "data": "waktu_masuk" },
            { 
                "data": null,
                "render": function(data) {
                    if (data.waktu_pulang) return '<span class="label label-default">Pulang</span>';
                    if (data.waktu_istirahat_mulai && !data.waktu_istirahat_selesai) return '<span class="label label-warning">Istirahat</span>';
                    return '<span class="label label-success">Aktif</span>';
                }
            },
            { "data": "total_scan" },
            { 
                "data": "total_slow",
                "render": function(data) {
                    return data > 0 ? '<span class="badge badge-danger" style="background-color: #d9534f;">' + data + '</span>' : '0';
                }
            },
            { 
                "data": "total_istirahat",
                "render": function(data) {
                    var minutes = Math.floor(data / 60);
                    return minutes + ' menit';
                }
            },
            {
                "data": "id_user",
                "render": function(data, type, row) {
                    return '<button class="btn btn-xs btn-primary btn-view-detail" data-id="'+data+'" data-name="'+row.nama_packer+'">Detail</button>';
                }
            }
        ]
    });

    $('#btn-refresh').click(function() {
        tableMonitoring.ajax.reload();
    });

    // View Detail
    $(document).on('click', '.btn-view-detail', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#detail-packer-name').text(name);
        
        $.get("<?= base_url('packer_monitoring/get_detailed_logs/') ?>" + id, function(response) {
            var resData = (typeof response === 'string') ? JSON.parse(response) : response;
            var data = resData.data;
            var html = '';
            data.forEach(function(log) {
                var rowClass = log.is_slow == 1 ? 'danger' : '';
                if (log.is_deleted == 1) rowClass = 'warning'; // Hidden from main but visible here

                html += '<tr class="'+rowClass+'">';
                html += '<td>'+log.tanggal_packing.split(' ')[1]+'</td>';
                html += '<td>'+log.noresi+'</td>';
                html += '<td>'+(log.sku_qty ? log.sku_qty : '-')+'</td>';
                html += '<td>'+log.status_performa+'</td>';
                html += '<td>'+log.durasi_aktual+'s</td>';
                html += '<td>'+log.durasi_target+'s</td>';
                
                var statusText = log.is_slow == 1 ? '<span class="label label-danger">Melambat</span>' : '<span class="label label-success">OK</span>';
                if (log.is_deleted == 1) statusText = '<span class="label label-info">Aman (Diabaikan)</span>';
                
                html += '<td>'+statusText+'</td>';
                html += '<td>'+(log.komentar || '-')+'</td>';
                html += '<td>';
                if (log.is_slow == 1 && log.is_deleted == 0) {
                    html += '<button class="btn btn-xs btn-info btn-comment" data-id="'+log.id+'">Comment</button> ';
                    html += '<button class="btn btn-xs btn-danger btn-delete" data-id="'+log.id+'">Hapus/Abaikan</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            $('#table-detail-logs tbody').html(html);
            $('#modal-detail').modal('show');
        });
    });

    // Comment
    $(document).on('click', '.btn-comment', function() {
        var id = $(this).data('id');
        $('#comment-log-id').val(id);
        $('#comment-text').val('');
        $('#modal-comment').modal('show');
    });

    $('#btn-save-comment').click(function() {
        var id = $('#comment-log-id').val();
        var comment = $('#comment-text').val();
        $.post("<?= base_url('packer_monitoring/add_comment') ?>", { log_id: id, comment: comment }, function() {
            $('#modal-comment').modal('hide');
            // Refresh detail table
            $('.btn-view-detail[data-id="' + $('#modal-detail .btn-view-detail').data('id') + '"]').click();
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        if (confirm('Apakah Anda yakin ingin menghapus log ini dari monitoring utama?')) {
            var id = $(this).data('id');
            $.post("<?= base_url('packer_monitoring/delete_log') ?>", { log_id: id }, function() {
                // Refresh detail table
                $('.btn-view-detail[data-id="' + $('#modal-detail .btn-view-detail').data('id') + '"]').click();
                tableMonitoring.ajax.reload();
            });
        }
    });

    // Auto refresh every 30 seconds
    setInterval(function() {
        tableMonitoring.ajax.reload(null, false);
    }, 30000);

    function updateStats(stats) {
        if (!stats) return;
        $('#stat-total-slow').text(stats.total_slow_scans);
        $('#stat-slow-persons').text(stats.slow_persons);
        $('#stat-total-active').text(stats.total_active);
    }
});
</script>
