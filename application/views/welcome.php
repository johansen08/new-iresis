<style>
    .widget[data-type] { cursor: pointer !important; transition: all 0.2s ease; }
    .widget[data-type]:hover { opacity: 0.8; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="widget widget-info widget-padding-sm">
            <div class="widget-big-int plugin-clock">00:00</div>
            <div class="widget-subtitle plugin-date">Loading...</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-refresh"></i> MONITORING ON PROGRESS (HARI INI)</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="widget widget-info widget-item-icon" data-type="pending_picker" data-title="ON PROGRESS: PICKER">
                            <div class="widget-item-left"><span class="fa fa-shopping-cart"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_pending_picker">...</div>
                                <div class="widget-title">PICKER</div>
                                <div class="widget-subtitle">Sisa paket scan hari ini</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-warning widget-item-icon" data-type="pending_packer" data-title="ON PROGRESS: PACKER">
                            <div class="widget-item-left"><span class="fa fa-archive"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_pending_packer">...</div>
                                <div class="widget-title">PACKER</div>
                                <div class="widget-subtitle">Sisa paket dipacking hari ini</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-success widget-item-icon" data-type="pending_ho" data-title="ON PROGRESS: HO (KELUAR)">
                            <div class="widget-item-left"><span class="fa fa-truck"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_pending_ho">...</div>
                                <div class="widget-title">HO (KELUAR)</div>
                                <div class="widget-subtitle">Sisa paket handover hari ini</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-calendar"></i> MONITORING BATAS KIRIM HARI INI</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="widget widget-danger widget-item-icon" data-type="belum_selesai" data-title="WAJIB HARI INI">
                            <div class="widget-item-left"><span class="fa fa-exclamation-circle"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_belum">...</div>
                                <div class="widget-title">WAJIB HARI INI</div>
                                <div class="widget-subtitle">Belum Handover</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="widget widget-danger widget-item-icon" style="background: #33414e !important;" data-type="total_overdue" data-title="OVERDUE">
                            <div class="widget-item-left"><span class="fa fa-history"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_overdue" style="color: #ff4d4d;">...</div>
                                <div class="widget-title" style="color: #fff;">OVERDUE</div>
                                <div class="widget-subtitle" style="color: #ccc;">Lewat Batas Kirim</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="widget widget-primary widget-item-icon" data-type="total_resi" data-title="TOTAL RESI">
                            <div class="widget-item-left"><span class="fa fa-print"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_resi">...</div>
                                <div class="widget-title"> TOTAL RESI</div>
                                <div class="widget-subtitle">Deadline</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="widget widget-info widget-item-icon" data-type="sudah_picker" data-title="PICKER">
                            <div class="widget-item-left"><span class="fa fa-shopping-cart"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_picker">...</div>
                                <div class="widget-title">PICKER</div>
                                <div class="widget-subtitle">Telah di-scan</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="widget widget-warning widget-item-icon" data-type="sudah_packer" data-title="PACKER">
                            <div class="widget-item-left"><span class="fa fa-archive"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_packer">...</div>
                                <div class="widget-title">PACKER</div>
                                <div class="widget-subtitle">Telah di-scan</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="widget widget-success widget-item-icon" data-type="sudah_ho" data-title="HO (KELUAR)">
                            <div class="widget-item-left"><span class="fa fa-truck"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count" id="count_ho">...</div>
                                <div class="widget-title">HO (KELUAR)</div>
                                <div class="widget-subtitle">Telah di-scan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-center" style="margin-bottom: 20px;">
        <button class="btn btn-default btn-sm" onclick="loadDashboardData()"><i class="fa fa-refresh"></i> Refresh Dashboard</button>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="modal-detail-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modal-detail-title">Detail Resi</h4>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto; padding: 0;">
                <table class="table table-bordered table-striped" id="table-detail" style="margin-bottom: 0;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th>No. Resi</th>
                            <th>Kurir</th>
                            <th>Status Pesanan</th>
                            <th>Batas Kirim</th>
                            <th>Status Saat Ini</th>
                            <th>Update Terakhir</th>
                        </tr>
                    </thead>
                    <tbody id="body-detail">
                        <!-- Data will be appended here -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Robust jQuery check
function startDashboard() {
    if (window.jQuery) {
        $(document).ready(function() {
            loadDashboardData();
            
            // Event delegation to handle click even if element is re-rendered
            $(document).off('click', '.widget[data-type]').on('click', '.widget[data-type]', function(e) {
                var type = $(this).data('type');
                var title = $(this).data('title');
                showDetails(type, title);
            });
        });
    } else {
        setTimeout(startDashboard, 50);
    }
}
startDashboard();

function loadDashboardData() {
    $.ajax({
        url: 'welcome/get_dashboard_data',
        type: 'GET',
        dataType: 'JSON',
        success: function(res) {
            $('#count_belum').text(formatNumber(res.simple.belum_selesai));
            $('#count_overdue').text(formatNumber(res.simple.total_overdue));
            $('#count_resi').text(formatNumber(res.simple.total_resi));
            $('#count_picker').text(formatNumber(res.simple.sudah_picker));
            $('#count_packer').text(formatNumber(res.simple.sudah_packer));
            $('#count_ho').text(formatNumber(res.simple.sudah_ho));

            // New ON PROGRESS counts
            $('#count_pending_picker').text(formatNumber(res.simple.pending_picker_today));
            $('#count_pending_packer').text(formatNumber(res.simple.pending_packer_today));
            $('#count_pending_ho').text(formatNumber(res.simple.pending_ho_today));
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard data:', error);
        }
    });
}

function showDetails(type, title) {
    $('#modal-detail-title').text('Detail: ' + title);
    $('#body-detail').html('<tr><td colspan="6" class="text-center"><div style="padding: 20px;"><i class="fa fa-street-view fa-jogging" style="font-size: 30px;"></i><br><small>Jogging ambil data...</small></div></td></tr>');
    $('#modal-detail').modal('show');
    
    $.ajax({
        url: 'welcome/get_dashboard_details',
        type: 'GET',
        data: { type: type },
        dataType: 'JSON',
        success: function(res) {
            var html = '';
            if (res && res.length > 0) {
                res.forEach(function(item) {
                    var statusLabel = '<span class="label label-default">' + (item.current_status || 'STAGING') + '</span>';
                    if (item.current_status == 'HO') statusLabel = '<span class="label label-success">HO</span>';
                    else if (item.current_status == 'PACKED') statusLabel = '<span class="label label-warning">PACKED</span>';
                    else if (item.current_status == 'PICKED') statusLabel = '<span class="label label-info">PICKED</span>';
                    
                    // Red Alert Logic for Batas Kirim (Today)
                    var deadlineDate = item.tanggal_bataskirim ? item.tanggal_bataskirim.split(' ')[0] : '';
                    var todayDate = new Date().toISOString().split('T')[0];
                    var isDeadlineToday = (deadlineDate === todayDate);
                    var deadlineStyle = isDeadlineToday ? 'style="color: #e74c3c; font-weight: bold;"' : '';

                    html += '<tr>' +
                        '<td><strong style="color: #2980b9;">' + item.noresi + '</strong></td>' +
                        '<td>' + (item.nama_kurir || '-') + '</td>' +
                        '<td>' + (item.status_pesanan || '-') + '</td>' +
                        '<td ' + deadlineStyle + '>' + (item.tanggal_bataskirim || '-') + '</td>' +
                        '<td>' + statusLabel + '</td>' +
                        '<td>' + (item.last_action_date || '-') + '</td>' +
                    '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-center">Tidak ada data ditemukan</td></tr>';
            }
            $('#body-detail').html(html);
        },
        error: function() {
            $('#body-detail').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
        }
    });
}

function formatNumber(num) {
    if (!num) return 0;
    return new Intl.NumberFormat('id-ID').format(num);
}
</script>