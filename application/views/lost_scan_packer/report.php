<!-- DASHBOARD SUMMARY -->
<div class="row">
    <!-- WIDGETS TOTAL PER TIPE -->
    <div class="col-md-3">
        <div class="widget widget-primary widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-shopping-cart"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-packer"><?= $stats['totals']['PACKER'] ?></div>
                <div class="widget-title">Total Type PACKER</div>
                <div class="widget-subtitle">Periode Terpilih</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget widget-warning widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-search"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-picker"><?= $stats['totals']['PICKER'] ?></div>
                <div class="widget-title">Total Type PICKER</div>
                <div class="widget-subtitle">Periode Terpilih</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget widget-info widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-truck"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-ho"><?= $stats['totals']['HO'] ?></div>
                <div class="widget-title">Total Type HO</div>
                <div class="widget-subtitle">Periode Terpilih</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget widget-danger widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-exclamation-circle"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="stat-total-all"><?= $stats['totals']['ALL'] ?></div>
                <div class="widget-title">Grand Total Lost</div>
                <div class="widget-subtitle">Semua Tipe</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- RANKING PER TIPE -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading" style="background: #33414e; color: white;">
                <h3 class="panel-title"><i class="fa fa-trophy"></i> Top 3 - Tipe Lost Scan PACKER</h3>
            </div>
            <div class="panel-body list-group list-group-contacts" id="ranking-packer-list">
                <?php if (!empty($stats['top_packer'])) : ?>
                    <?php foreach ($stats['top_packer'] as $idx => $p) : ?>
                        <a href="#" class="list-group-item">
                            <span class="badge badge-primary"><?= $p['total'] ?></span>
                            <span class="contacts-title"><?= ($idx + 1) . '. ' . $p['nama'] ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="text-center" style="padding: 10px;">Belum ada data</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading" style="background: #f0ad4e; color: white;">
                <h3 class="panel-title"><i class="fa fa-trophy"></i> Top 3 - Tipe Lost Scan PICKER</h3>
            </div>
            <div class="panel-body list-group list-group-contacts" id="ranking-picker-list">
                <?php if (!empty($stats['top_picker'])) : ?>
                    <?php foreach ($stats['top_picker'] as $idx => $p) : ?>
                        <a href="#" class="list-group-item">
                            <span class="badge badge-warning"><?= $p['total'] ?></span>
                            <span class="contacts-title"><?= ($idx + 1) . '. ' . $p['nama'] ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="text-center" style="padding: 10px;">Belum ada data</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading" style="background: #5bc0de; color: white;">
                <h3 class="panel-title"><i class="fa fa-trophy"></i> Top 3 - Tipe Lost Scan HO</h3>
            </div>
            <div class="panel-body list-group list-group-contacts" id="ranking-ho-list">
                <?php if (!empty($stats['top_ho'])) : ?>
                    <?php foreach ($stats['top_ho'] as $idx => $p) : ?>
                        <a href="#" class="list-group-item">
                            <span class="badge badge-info"><?= $p['total'] ?></span>
                            <span class="contacts-title"><?= ($idx + 1) . '. ' . $p['nama'] ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="text-center" style="padding: 10px;">Belum ada data</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default tabs">
            <ul class="nav nav-tabs" role="tablist">
                <li class="active"><a href="#tab-packer" role="tab" data-toggle="tab">Lost Scan Packer</a></li>
                <li><a href="#tab-picker" role="tab" data-toggle="tab">Lost Scan Picker</a></li>
                <li><a href="#tab-ho" role="tab" data-toggle="tab">Lost Scan HO</a></li>
                
                <li class="pull-right">
                    <a href="lost_scan_packer/input" class="btn btn-primary link" style="margin-right: 10px; color: white; padding: 5px 10px;"><i class="fa fa-plus"></i> Input Baru</a>
                </li>
            </ul>
            <div class="panel-body tab-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Filter Tanggal</label>
                            <div class="input-group">
                                <input type="text" id="reportrange" class="form-control daterange-report">
                                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <br>
                        <button type="button" id="btn_export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Download Excel (<span id="active_tab_name">Packer</span>)</button>
                    </div>
                </div>
                <hr>

                <!-- TAB PACKER -->
                <div class="tab-pane active" id="tab-packer">
                    <div class="table-responsive">
                        <table id="table_lost_scan_packer" class="table table-bordered table-striped table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>No Resi</th>
                                    <th>Tipe</th>
                                    <th>Status Resi</th>
                                    <th>Kurir</th>
                                    <th>Karyawan (Pelaku)</th>
                                    <th>Dilaporkan Oleh</th>
                                    <?php if ($is_webmaster) : ?>
                                    <th width="100">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- TAB PICKER -->
                <div class="tab-pane" id="tab-picker">
                    <div class="table-responsive">
                        <table id="table_lost_scan_picker" class="table table-bordered table-striped table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>No Resi</th>
                                    <th>Tipe</th>
                                    <th>Status Resi</th>
                                    <th>Kurir</th>
                                    <th>Karyawan (Pelaku)</th>
                                    <th>Dilaporkan Oleh</th>
                                    <?php if ($is_webmaster) : ?>
                                    <th width="100">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- TAB HO -->
                <div class="tab-pane" id="tab-ho">
                    <div class="table-responsive">
                        <table id="table_lost_scan_ho" class="table table-bordered table-striped table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>No Resi</th>
                                    <th>Tipe</th>
                                    <th>Status Resi</th>
                                    <th>Kurir</th>
                                    <th>Karyawan (Pelaku)</th>
                                    <th>Dilaporkan Oleh</th>
                                    <?php if ($is_webmaster) : ?>
                                    <th width="100">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var start_date = moment().format('YYYY-MM-DD');
        var end_date = moment().format('YYYY-MM-DD');
        var active_tab = 'PACKER';
        var is_webmaster = <?= $is_webmaster ? 'true' : 'false' ?>;

        $('#reportrange').daterangepicker({
            startDate: moment(),
            endDate: moment(),
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end) {
            start_date = start.format('YYYY-MM-DD');
            end_date = end.format('YYYY-MM-DD');
            reload_active_table();
            reload_dashboard();
        });

        function reload_dashboard() {
            $.ajax({
                url: 'lost_scan_packer/get_summary_stats_ajax',
                type: 'POST',
                data: {
                    start_date: start_date,
                    end_date: end_date
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    var stats = result.data;

                    // Update Totals
                    $('#stat-total-packer').text(stats.totals.PACKER);
                    $('#stat-total-picker').text(stats.totals.PICKER);
                    $('#stat-total-ho').text(stats.totals.HO);
                    $('#stat-total-all').text(stats.totals.ALL);

                    // Update Rankings
                    update_ranking_list('ranking-packer-list', stats.top_packer, 'badge-primary');
                    update_ranking_list('ranking-picker-list', stats.top_picker, 'badge-warning');
                    update_ranking_list('ranking-ho-list', stats.top_ho, 'badge-info');
                }
            });
        }

        function update_ranking_list(containerId, data, badgeClass) {
            var html = '';
            if (data.length > 0) {
                $.each(data, function(idx, p) {
                    html += '<a href="#" class="list-group-item">';
                    html += '    <span class="badge ' + badgeClass + '">' + p.total + '</span>';
                    html += '    <span class="contacts-title">' + (idx + 1) + '. ' + p.nama + '</span>';
                    html += '</a>';
                });
            } else {
                html = '<div class="text-center" style="padding: 10px;">Belum ada data</div>';
            }
            $('#' + containerId).html(html);
        }

        function init_table(tableId, type) {
            var columns = [
                { "data": "0" },
                { "data": "1" },
                { "data": "2" },
                { "data": "3" },
                { "data": "4" },
                { "data": "5" },
                { "data": "6" },
                { "data": "7" },
                { "data": "8" }
            ];

            if (is_webmaster) {
                columns.push({ 
                    "data": "9",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return '<button class="btn btn-danger btn-xs" onclick="delete_data(\'' + data + '\')"><i class="fa fa-trash-o"></i> Hapus</button>';
                    }
                });
            }

            return $('#' + tableId).DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "lost_scan_packer/get_lost_scan_data",
                    "type": "POST",
                    "data": function(d) {
                        d.start_date = start_date;
                        d.end_date = end_date;
                        d.lost_type = type;
                    }
                },
                "columns": columns,
                "order": [[1, "desc"]]
            });
        }

        var tablePacker = init_table('table_lost_scan_packer', 'PACKER');
        var tablePicker = null; // Lazy load
        var tableHO = null; // Lazy load

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            if (target === '#tab-packer') {
                active_tab = 'PACKER';
                $('#active_tab_name').text('Packer');
                if (tablePacker) tablePacker.ajax.reload();
            } else if (target === '#tab-picker') {
                active_tab = 'PICKER';
                $('#active_tab_name').text('Picker');
                if (!tablePicker) {
                    tablePicker = init_table('table_lost_scan_picker', 'PICKER');
                } else {
                    tablePicker.ajax.reload();
                }
            } else if (target === '#tab-ho') {
                active_tab = 'HO';
                $('#active_tab_name').text('HO');
                if (!tableHO) {
                    tableHO = init_table('table_lost_scan_ho', 'HO');
                } else {
                    tableHO.ajax.reload();
                }
            }
        });

        function reload_active_table() {
            if (active_tab === 'PACKER' && tablePacker) tablePacker.ajax.reload();
            if (active_tab === 'PICKER' && tablePicker) tablePicker.ajax.reload();
            if (active_tab === 'HO' && tableHO) tableHO.ajax.reload();
        }

        $("#btn_export").click(function() {
            window.location.href = "lost_scan_packer/export_excel?start_date=" + start_date + "&end_date=" + end_date + "&lost_type=" + active_tab;
        });

        // Expose to global for delete_data
        window.reload_dashboard_global = reload_dashboard;

        // Initialize dashboard
        reload_dashboard();
    });

    function delete_data(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: 'lost_scan_packer/delete',
                type: 'POST',
                data: {id: id},
                success: function(response) {
                    if (response.data && response.data.status === 200) {
                        noty({text: response.message, layout: 'topRight', type: 'success', timeout: 3000});
                        // Reload current active table and dashboard
                        $('.tab-pane.active table').DataTable().ajax.reload();
                        window.reload_dashboard_global(); // We need a way to call it
                    } else {
                        var msg = response.message || 'Gagal menghapus data';
                        noty({text: msg, layout: 'topRight', type: 'error', timeout: 3000});
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403) {
                        noty({text: 'Akses Ditolak: Anda tidak memiliki hak untuk menghapus.', layout: 'topRight', type: 'error', timeout: 3000});
                    } else {
                        noty({text: 'Terjadi kesalahan server', layout: 'topRight', type: 'error', timeout: 3000});
                    }
                }
            });
        }
    }
</script>

<style>
.widget {
    background: #fff;
    padding: 15px;
    height: 110px;
    margin-bottom: 20px;
    border-radius: 5px;
    position: relative;
    border: 1px solid #E5E5E5;
}
.widget.widget-primary { background: #33414e; color: #fff; border: 0px; }
.widget.widget-warning { background: #f0ad4e; color: #fff; border: 0px; }
.widget.widget-info { background: #5bc0de; color: #fff; border: 0px; }
.widget.widget-danger { background: #d9534f; color: #fff; border: 0px; }

.widget .widget-item-left {
    width: 60px;
    height: 100%;
    float: left;
    text-align: center;
    font-size: 35px;
    line-height: 80px;
}
.widget .widget-data {
    padding-left: 70px;
    padding-top: 10px;
}
.widget .widget-int {
    font-size: 30px;
    font-weight: 600;
    line-height: 30px;
}
.widget .widget-title {
    font-size: 14px;
    font-weight: 400;
}
.widget .widget-subtitle {
    font-size: 11px;
    opacity: 0.7;
}

.list-group-contacts .list-group-item {
    padding: 8px 15px;
    border-left: 0px;
    border-right: 0px;
}
.badge { font-size: 12px; }
.panel-title { font-weight: 600; font-size: 13px !important; }
</style>
