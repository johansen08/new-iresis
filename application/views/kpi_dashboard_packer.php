<?php
// Cek akses - hanya user yang memiliki akses ke menu Dashboard KPI
$user = $this->session->userdata('user');
if (!$user || !isset($user['id_user'])) {
    redirect('welcome/restricted');
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Dashboard KPI Packer</strong></h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal nojs" method="post" action="<?= base_url('kpi_reports/dashboard_packer') ?>"
                    id="form-filter-kpi-packer">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
                        <div class="col-md-3 col-xs-12">
                            <input type="text" name="reportrange" id="reportrange" class="form-control"
                                value="<?= !empty($reportrange) ? $reportrange : null ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label"></label>
                        <div class="col-md-6 col-xs-12">
                            <button type="submit" class="btn btn-info" id="btn-search">
                                <i class="fa fa-search"></i> Filter
                            </button>
                            <button type="button" class="btn btn-default" id="btn-refresh-kpi-packer">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-success" id="btn-export-excel">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-success">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-cube fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($dashboard_stats['total_packing'] ?? 0) ?></div>
                        <div>Total Packing</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Total Packed Receipts</span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-cubes fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($dashboard_stats['total_qty'] ?? 0) ?></div>
                        <div>Total SKU</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Total SKU Packed</span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-success">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-line-chart fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($dashboard_stats['avg_sku_per_packer'] ?? 0, 2) ?></div>
                        <div>Avg SKU/Resi</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Average SKU per Receipt</span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-users fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($dashboard_stats['total_active_packers'] ?? 0) ?></div>
                        <div>Active Packers</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Total Active Packers</span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Chart -->
<div class="row">
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bar-chart"></i> Performa SKU Packer Per Hari <small>(Tim Inti)</small>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="packerSkuChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-trophy"></i> Top 5 Performance Ranking
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="rankingPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers - TIM INTI -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-success">
            <div class="panel-heading" style="background-color: #5cb85c; color: white;">
                <i class="fa fa-trophy"></i> <strong>ROLE PACKER (TIM INTI) - Packer Performance</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #fcf8e3;">
                                <th>#</th>
                                <th>Username</th>
                                <th>Nama Pegawai</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Total Resi</th>
                                <th class="text-right">Selisih</th>
                                <th class="text-right">% Capaian</th>
                                <th class="text-right">Hari Kerja</th>
                                <th class="text-right">Total Jam Kerja</th>
                                <th class="text-right">% Jam Kerja</th>
                                <th class="text-right">Total Qty</th>
                                <th class="text-right">Total Eror</th>
                                <th class="text-center">Performance</th>
                                <th class="text-center">Ranking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rows_inti = $dashboard_stats['top_packers_inti'] ?? [];
                            $expected_hari = $dashboard_stats['expected_hari_kerja'] ?? 1;
                            // Hitung skor performa akhir tiap packer, lalu tentukan ranking (skor tertinggi = 1)
                            // Bobot: % Capaian 40%, % Jam Kerja 25%, Hari Kerja 15%, Akurasi (eror) 20%.
                            $skor_inti = [];
                            foreach ($rows_inti as $i => $p) {
                                $pc = (float)($p['pct_capai'] ?? 0);       // capaian tidak dibatasi (boleh >100)
                                $pj = (float)($p['pct_jam_kerja'] ?? 0);   // jam kerja tidak dibatasi (boleh >100)
                                $te = (float)($p['total_eror'] ?? 0);
                                $tq = (float)($p['total_qty'] ?? 0);
                                $hk = (float)($p['total_hari_masuk'] ?? 0);
                                $ak = $tq > 0 ? max(0, 100 - ($te / $tq) * 100) : 100;
                                $hks = $expected_hari > 0 ? ($hk / $expected_hari) * 100 : 0; // skor hari kerja (kehadiran)
                                $rows_inti[$i]['performance_score'] = round(($pc * 0.40) + ($pj * 0.25) + ($hks * 0.15) + ($ak * 0.20));
                                $skor_inti[$i] = $rows_inti[$i]['performance_score'];
                            }
                            arsort($skor_inti);
                            $rk = 1;
                            foreach ($skor_inti as $i => $s) { $rows_inti[$i]['rank'] = $rk++; }
                            // Urutkan baris mengikuti ranking (skor tertinggi di atas)
                            usort($rows_inti, function ($a, $b) {
                                return ($b['performance_score'] ?? 0) <=> ($a['performance_score'] ?? 0);
                            });
                            ?>
                            <?php if (count($rows_inti) > 0): ?>
                                <?php foreach ($rows_inti as $index => $packer): ?>
                                    <?php
                                    // Nilai dasar untuk baris ini
                                    $target_resi = $packer['target_resi'] ?? 0;
                                    $selisih     = $packer['selisih'] ?? 0;
                                    $pct_capai   = $packer['pct_capai'] ?? 0;
                                    $pct_jam     = $packer['pct_jam_kerja'] ?? 0;

                                    // Styling kolom Target & Jam Kerja
                                    $selisih_badge = $selisih >= 0 ? 'success' : 'danger';
                                    $pct_badge = $pct_capai >= 100 ? 'success' : ($pct_capai >= 80 ? 'warning' : 'danger');
                                    if ($pct_jam > 80) {
                                        $jam_pct_badge = 'success'; // Hijau  (>80%)
                                    } elseif ($pct_jam >= 50) {
                                        $jam_pct_badge = 'warning'; // Oranye (50-79%)
                                    } else {
                                        $jam_pct_badge = 'danger';  // Merah  (<50%)
                                    }

                                    // Skor performa akhir (dihitung sekali di atas) + kelompok 3 bagian
                                    $performance_score = $packer['performance_score'] ?? 0;
                                    if ($performance_score >= 75) {
                                        $status = 'BAIK';
                                        $status_class = 'success';
                                    } elseif ($performance_score >= 50) {
                                        $status = 'CUKUP';
                                        $status_class = 'warning';
                                    } else {
                                        $status = 'KURANG';
                                        $status_class = 'danger';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($packer['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($packer['nama_pegawai'] ?? $packer['user_name'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($target_resi) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_resi']) ?></td>
                                        <td class="text-right text-<?= $selisih_badge ?>"><strong><?= ($selisih > 0 ? '+' : '') . number_format($selisih) ?></strong></td>
                                        <td class="text-right"><span class="label label-<?= $pct_badge ?>"><?= number_format($pct_capai, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($packer['total_hari_masuk'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_jam_kerja'], 1) ?> Jam</td>
                                        <td class="text-right"><span class="label label-<?= $jam_pct_badge ?>"><?= number_format($pct_jam, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($packer['total_qty']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($packer['total_eror'] ?? 0) ?></strong></td>
                                        <td class="text-center">
                                            <span class="label label-<?= $status_class ?>"><?= $status ?></span>
                                            <div style="font-size: 11px; color: #888; margin-top: 3px;">Skor: <?= $performance_score ?></div>
                                        </td>
                                        <td class="text-center"><span style="font-size: 18px; font-weight: bold; color: #337ab7;">#<?= $packer['rank'] ?? '-' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="14" class="text-center text-muted">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers - TIM OTHERS -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #f0ad4e; color: white;">
                <i class="fa fa-users"></i> <strong>PERBANTUAN / NON INTI - User Lain yang Bantu Packing</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th>#</th>
                                <th>Username</th>
                                <th>Nama User</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Total Resi</th>
                                <th class="text-right">Selisih</th>
                                <th class="text-right">% Capaian</th>
                                <th class="text-right">Hari Kerja</th>
                                <th class="text-right">Total Jam Kerja</th>
                                <th class="text-right">% Jam Kerja</th>
                                <th class="text-right">Total Qty</th>
                                <th class="text-right">Total Eror</th>
                                <th class="text-center">Performance</th>
                                <th class="text-center">Ranking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rows_others = $dashboard_stats['top_packers_others'] ?? [];
                            $expected_hari = $dashboard_stats['expected_hari_kerja'] ?? 1;
                            // Hitung skor performa akhir tiap packer, lalu tentukan ranking (skor tertinggi = 1)
                            // Bobot: % Capaian 40%, % Jam Kerja 25%, Hari Kerja 15%, Akurasi (eror) 20%.
                            $skor_others = [];
                            foreach ($rows_others as $i => $p) {
                                $pc = (float)($p['pct_capai'] ?? 0);       // capaian tidak dibatasi (boleh >100)
                                $pj = (float)($p['pct_jam_kerja'] ?? 0);   // jam kerja tidak dibatasi (boleh >100)
                                $te = (float)($p['total_eror'] ?? 0);
                                $tq = (float)($p['total_qty'] ?? 0);
                                $hk = (float)($p['total_hari_masuk'] ?? 0);
                                $ak = $tq > 0 ? max(0, 100 - ($te / $tq) * 100) : 100;
                                $hks = $expected_hari > 0 ? ($hk / $expected_hari) * 100 : 0; // skor hari kerja (kehadiran)
                                $rows_others[$i]['performance_score'] = round(($pc * 0.40) + ($pj * 0.25) + ($hks * 0.15) + ($ak * 0.20));
                                $skor_others[$i] = $rows_others[$i]['performance_score'];
                            }
                            arsort($skor_others);
                            $rk = 1;
                            foreach ($skor_others as $i => $s) { $rows_others[$i]['rank'] = $rk++; }
                            // Urutkan baris mengikuti ranking (skor tertinggi di atas)
                            usort($rows_others, function ($a, $b) {
                                return ($b['performance_score'] ?? 0) <=> ($a['performance_score'] ?? 0);
                            });
                            ?>
                            <?php if (count($rows_others) > 0): ?>
                                <?php foreach ($rows_others as $index => $packer): ?>
                                    <?php
                                    // Nilai dasar untuk baris ini
                                    $target_resi = $packer['target_resi'] ?? 0;
                                    $selisih     = $packer['selisih'] ?? 0;
                                    $pct_capai   = $packer['pct_capai'] ?? 0;
                                    $pct_jam     = $packer['pct_jam_kerja'] ?? 0;

                                    // Styling kolom Target & Jam Kerja
                                    $selisih_badge = $selisih >= 0 ? 'success' : 'danger';
                                    $pct_badge = $pct_capai >= 100 ? 'success' : ($pct_capai >= 80 ? 'warning' : 'danger');
                                    if ($pct_jam > 80) {
                                        $jam_pct_badge = 'success'; // Hijau  (>80%)
                                    } elseif ($pct_jam >= 50) {
                                        $jam_pct_badge = 'warning'; // Oranye (50-79%)
                                    } else {
                                        $jam_pct_badge = 'danger';  // Merah  (<50%)
                                    }

                                    // Skor performa akhir (dihitung sekali di atas) + kelompok 3 bagian
                                    $performance_score = $packer['performance_score'] ?? 0;
                                    if ($performance_score >= 75) {
                                        $status = 'BAIK';
                                        $status_class = 'success';
                                    } elseif ($performance_score >= 50) {
                                        $status = 'CUKUP';
                                        $status_class = 'warning';
                                    } else {
                                        $status = 'KURANG';
                                        $status_class = 'danger';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($packer['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($packer['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($target_resi) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_resi']) ?></td>
                                        <td class="text-right text-<?= $selisih_badge ?>"><strong><?= ($selisih > 0 ? '+' : '') . number_format($selisih) ?></strong></td>
                                        <td class="text-right"><span class="label label-<?= $pct_badge ?>"><?= number_format($pct_capai, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($packer['total_hari_masuk'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_jam_kerja'], 1) ?> Jam</td>
                                        <td class="text-right"><span class="label label-<?= $jam_pct_badge ?>"><?= number_format($pct_jam, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($packer['total_qty']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($packer['total_eror'] ?? 0) ?></strong></td>
                                        <td class="text-center">
                                            <span class="label label-<?= $status_class ?>"><?= $status ?></span>
                                            <div style="font-size: 11px; color: #888; margin-top: 3px;">Skor: <?= $performance_score ?></div>
                                        </td>
                                        <td class="text-center"><span style="font-size: 18px; font-weight: bold; color: #337ab7;">#<?= $packer['rank'] ?? '-' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="14" class="text-center text-muted">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Laporan Performa Packer (Tim Inti) -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-success">
            <div class="panel-heading" style="background-color: #5cb85c; color: white;">
                <i class="fa fa-file-text-o"></i> <strong>Laporan Performa Packer - TIM INTI</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #d9edf7;">
                                <th style="width: 50px;">#</th>
                                <th>Username</th>
                                <th>Nama Packer</th>
                                <th class="text-right">Total Resi</th>
                                <th class="text-right">Total SKU</th>
                                <th class="text-right">Total Kesalahan</th>
                                <th class="text-right">Total Eror</th>
                                <th class="text-right" style="font-weight: bold; background-color: #dff0d8; color: #3c763d;">Total Final SKU</th>
                                <th class="text-right">Persentase Eror</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $packers_inti = $dashboard_stats['top_packers_inti'] ?? [];
                            // Sort by total_final_sku DESC
                            usort($packers_inti, function ($a, $b) {
                                return ($b['total_final_sku'] ?? 0) <=> ($a['total_final_sku'] ?? 0);
                            });
                            ?>
                            <?php if (count($packers_inti) > 0): ?>
                                <?php foreach ($packers_inti as $index => $packer): ?>
                                    <?php
                                    // Persentase eror = (total eror / 50) / total resi, ditampilkan dalam persen
                                    $total_resi_row = (float)($packer['total_resi'] ?? 0);
                                    $pct_eror = $total_resi_row > 0
                                        ? (((float)($packer['total_eror'] ?? 0) / 50) / $total_resi_row) * 100
                                        : 0;
                                    $pct_eror_badge = $pct_eror <= 1 ? 'success' : ($pct_eror <= 3 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($packer['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($packer['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($packer['total_resi'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_qty'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($packer['total_kesalahan'] ?? 0) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($packer['total_eror'] ?? 0) ?></strong></td>
                                        <td class="text-right" style="font-weight: bold; background-color: #dff0d8; color: #3c763d;">
                                            <?= number_format($packer['total_final_sku'] ?? 0) ?>
                                        </td>
                                        <td class="text-right"><span class="label label-<?= $pct_eror_badge ?>"><?= number_format($pct_eror, 2) ?>%</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Global state to store current reportrange
    window.kpiPackerReportRange = <?= !empty($reportrange) ? '"' . $reportrange . '"' : '"' . date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s') . '"' ?>;
    window.kpiPackerRetryCount = 0;
    window.kpiPackerIsLoading = false;

    // Function to initialize daterangepicker - SAMA SEPERTI LAPORAN RESI HARIAN
    function initKpiPackerDaterangepicker() {
        var report_range = window.kpiPackerReportRange;

        var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
        var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment();

        var $reportrange = $('#reportrange');

        if ($reportrange.length === 0) {
            console.warn('Reportrange element not found, skipping initialization');
            return;
        }

        // Destroy existing datepicker if exists
        if ($reportrange.data('daterangepicker')) {
            try {
                $reportrange.data('daterangepicker').remove();
            } catch (e) {
                console.warn('Error removing old daterangepicker:', e);
            }
        }

        $reportrange.daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment().startOf('day'), moment()],
                'Last 1 Hours': [moment().subtract(1, 'hours'), moment()],
                'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment()],
                'This Month': [moment().startOf('month'), moment()],
            },
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        }, function (start, end, label) {
            // Callback when range is applied - UPDATE value
            var newRange = start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss');
            $reportrange.val(newRange);

            // FIX: Update global variable to keep in sync
            window.kpiPackerReportRange = newRange;

            console.log('✅ Date range selected:', label);
            console.log('✅ New range:', newRange);
            console.log('✅ Input value updated:', $reportrange.val());
        });

        // Set initial value in input field immediately after initialization
        $reportrange.val(report_range);

        console.log('KPI Packer daterangepicker initialized with:', report_range);
        console.log('Available ranges:', {
            'Today': [moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'), moment().format('YYYY-MM-DD HH:mm:ss')],
            'Yesterday': [moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'), moment().subtract(1, 'days').endOf('day').format('YYYY-MM-DD HH:mm:ss')],
            'Last 7 Days': [moment().subtract(6, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss'), moment().format('YYYY-MM-DD HH:mm:ss')],
            'This Month': [moment().startOf('month').format('YYYY-MM-DD HH:mm:ss'), moment().format('YYYY-MM-DD HH:mm:ss')]
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        initKpiPackerDaterangepicker();
    });

    // Handle form submission via AJAX to prevent URL change
    // Use delegated event to ensure handler persists after content replacement
    $(document).off('submit', '#form-filter-kpi-packer').on('submit', '#form-filter-kpi-packer', function (e) {
        e.preventDefault();

        // Function to check element and proceed with submission
        function proceedWithSubmission() {
            var $reportrange = $('#reportrange');

            // Check if element exists
            if ($reportrange.length === 0) {
                console.error('Reportrange element not found');
                window.kpiPackerIsLoading = false; // Reset flag
                return false;
            }

            var reportrange = null;

            // Try to get the latest range from the daterangepicker object if it exists (PRIORITY 1)
            try {
                var picker = $reportrange.data('daterangepicker');
                if (picker && picker.startDate && picker.endDate) {
                    reportrange = picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                    console.log('Retrieved range from picker object:', reportrange);
                }
            } catch (e) {
                console.warn('Error getting range from picker:', e);
            }

            // If no range from picker, get from input field (PRIORITY 2)
            if (!reportrange) {
                reportrange = $reportrange.val();
                console.log('Retrieved range from input field:', reportrange);
            }

            // Fallback to global variable (PRIORITY 3)
            if (!reportrange && window.kpiPackerReportRange) {
                reportrange = window.kpiPackerReportRange;
                console.log('Retrieved range from global variable:', reportrange);
            }

            // Update input field if we have a range
            if (reportrange && reportrange.trim() !== '') {
                $reportrange.val(reportrange);
            } else {
                // Final check for validity
                noty({ text: 'Rentang waktu tidak boleh kosong', timeout: 3000, layout: 'topRight', type: 'error' });
                return false;
            }

            console.log('=== KPI PACKER SUBMIT ===');
            console.log('Submitting reportrange:', reportrange);
            console.log('Parsed dates:', {
                start: reportrange.split(' - ')[0],
                end: reportrange.split(' - ')[1]
            });

            // Store in global state
            window.kpiPackerReportRange = reportrange;
            window.kpiPackerIsLoading = true;

            var btn = $('#btn-search');
            if (btn.length > 0) {
                var originalBtnHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fa fa-street-view fa-jogging"></i> Jogging...');
            }

            // Show loading
            var loading = "<div style='max-width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 50px;'><i class='fa fa-street-view fa-jogging' style='font-size: 50px;'></i><div style='margin-top: 15px; font-weight: 700; color: #444; letter-spacing: 2px; font-size: 14px;'>SABAR, LAGI JOGGING...</div></div>";
            $('.page-content-wrap').html(loading);

            $.ajax({
                url: '<?= base_url('kpi_reports/dashboard_packer') ?>',
                type: 'POST',
                data: { reportrange: reportrange },
                dataType: 'json',
                success: function (response) {
                    console.log('Response received:', response);
                    if (response.view) {
                        // Replace content with new view
                        $('.page-content-wrap').html(response.view);

                        // Re-initialize plugins (like in plugins.js devScript.formSubmit)
                        if (typeof formElements !== 'undefined' && formElements.init) formElements.init();
                        if (typeof uiElements !== 'undefined' && uiElements.init) uiElements.init();
                        if (typeof templatePlugins !== 'undefined' && templatePlugins.init) templatePlugins.init();

                        // Re-initialize daterangepicker
                        setTimeout(function () {
                            if (typeof initKpiPackerDaterangepicker !== 'undefined') {
                                initKpiPackerDaterangepicker();
                            }
                        }, 200);

                        noty({ text: 'Dashboard berhasil di-update', timeout: 2000, layout: 'topRight', type: 'success' });
                    }
                    window.kpiPackerIsLoading = false;
                    if (response.message) {
                        noty({ text: response.message, timeout: 3000, layout: 'topRight', type: 'success' });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    console.error('Response Text:', xhr.responseText);

                    noty({ text: 'Error loading data. Check console for details.', timeout: 3000, layout: 'topRight', type: 'error' });

                    // Restore original content or show error
                    if (xhr.responseText) {
                        $('.page-content-wrap').html(xhr.responseText);
                    }
                    window.kpiPackerIsLoading = false;
                }
            });

            return false;
        }

        // Pre-check: if already loading, ignore
        if (window.kpiPackerIsLoading) {
            console.log('Request already in progress, ignoring...');
            return false;
        }

        // Check if element exists, if not wait a bit and retry
        if ($('#reportrange').length === 0) {
            // Check if we are currently showing a loader - if so, just exit quietly
            if ($('.page-content-wrap img[src*="LoaderIcon"]').length > 0) {
                console.log('Element missing because loader is active, exit.');
                return false;
            }

            console.log('Element not found, waiting...');
            var retryCount = 0;
            var maxRetries = 10; // 10 retries = 2 seconds total

            function waitForElement() {
                if ($('#reportrange').length > 0) {
                    console.log('Element found, proceeding...');
                    proceedWithSubmission();
                } else if (retryCount < maxRetries) {
                    retryCount++;
                    console.log('Element not found, retrying... (' + retryCount + '/' + maxRetries + ')');
                    setTimeout(waitForElement, 200);
                } else {
                    console.error('Element not found after ' + maxRetries + ' attempts');
                    // Only show notification if not already loading via other means
                    if (!window.kpiPackerIsLoading) {
                        noty({ text: 'Form error: Date range element not found after multiple attempts', timeout: 3000, layout: 'topRight', type: 'error' });
                    }
                }
            }

            waitForElement();
        } else {
            proceedWithSubmission();
        }
    });

    // Refresh button should reload the dashboard section only (no full page refresh)
    $(document).off('click', '#btn-refresh-kpi-packer').on('click', '#btn-refresh-kpi-packer', function (e) {
        e.preventDefault();
        $('#form-filter-kpi-packer').trigger('submit');
    });

    // Export Excel button
    $('#btn-export-excel').click(function () {
        var $reportrange = $('#reportrange');
        var reportrange = $reportrange.val();

        // If empty, try to get from daterangepicker object
        if (!reportrange || reportrange.trim() === '') {
            try {
                var picker = $reportrange.data('daterangepicker');
                if (picker && picker.startDate && picker.endDate) {
                    reportrange = picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                    $reportrange.val(reportrange);
                    console.log('Retrieved range from picker for export:', reportrange);
                }
            } catch (e) {
                console.warn('Error getting range from picker for export:', e);
            }
        }

        // Validate before export
        if (!reportrange || (typeof reportrange === 'string' && reportrange.trim() === '')) {
            noty({ text: 'Rentang waktu tidak boleh kosong', timeout: 3000, layout: 'topRight', type: 'error' });
            return false;
        }

        var url = '<?= base_url('kpi_reports/export_excel_packer') ?>?reportrange=' + encodeURIComponent(reportrange);
        window.open(url, '_blank');
    });

    // Initialize charts — tunggu sampai Chart.js selesai dimuat (view di-load via AJAX)
    var safeInitRetryCount = 0;
    function safeInitializeCharts() {
        if (typeof Chart === 'undefined') {
            safeInitRetryCount++;
            if (safeInitRetryCount < 50) {
                console.warn('Chart.js belum siap, coba lagi 100ms...');
                setTimeout(safeInitializeCharts, 100);
            } else {
                console.error('Chart.js gagal dimuat setelah 5 detik. Chart tidak diinisialisasi.');
            }
        } else {
            initializeCharts();
        }
    }

    $(document).ready(function () {
        safeInitializeCharts();
    });

    function initializeCharts() {
        try {
            createRankingChart();
        } catch (e) {
            console.error('Error creating Ranking chart:', e);
        }
        try {
            createPackerSkuChart();
        } catch (e) {
            console.error('Error creating Packer SKU chart:', e);
        }
    }

    // Performa SKU Packer Per Hari — khusus TIM INTI, sumber data sama dengan
    // tabel "Laporan Performa Packer - TIM INTI".
    function createPackerSkuChart() {
        var packersInti = <?= json_encode($dashboard_stats['top_packers_inti'] ?? []) ?>;

        var allPackers = packersInti.map(function (p) {
            return {
                name: p.nama_pegawai || p.username,
                total_resi: parseInt(p.total_resi) || 0,
                total_eror: parseInt(p.total_eror) || 0,   // total_kesalahan sudah dikali 50
                total_qty: parseInt(p.total_qty) || 0
            };
        });

        // Urutkan sama seperti tabel (Total SKU terbesar di kiri)
        allPackers.sort(function (a, b) {
            return b.total_qty - a.total_qty;
        });

        var canvas = document.getElementById('packerSkuChart');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        if (window.packerSkuChartInstance) {
            window.packerSkuChartInstance.destroy();
        }

        window.packerSkuChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: allPackers.map(function (p) { return p.name; }),
                datasets: [
                    {
                        label: 'Total Resi',
                        data: allPackers.map(function (p) { return p.total_resi; }),
                        backgroundColor: 'rgba(91, 192, 222, 0.7)',
                        borderColor: 'rgba(91, 192, 222, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Total Eror (x50)',
                        data: allPackers.map(function (p) { return p.total_eror; }),
                        backgroundColor: 'rgba(217, 83, 79, 0.7)',
                        borderColor: 'rgba(217, 83, 79, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Total SKU',
                        data: allPackers.map(function (p) { return p.total_qty; }),
                        backgroundColor: 'rgba(92, 184, 92, 0.7)',
                        borderColor: 'rgba(92, 184, 92, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah',
                            font: { weight: 'bold' }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Nama Packer',
                            font: { weight: 'bold' }
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Ranking Performance Chart
    function createRankingChart() {
        // Combine all packers and calculate performance scores
        var allPackers = [];

        // Add TIM INTI packers
        <?php if (isset($dashboard_stats['top_packers_inti']) && count($dashboard_stats['top_packers_inti']) > 0): ?>
            <?php foreach ($dashboard_stats['top_packers_inti'] as $packer): ?>
                allPackers.push({
                    name: '<?= htmlspecialchars($packer['nama_pegawai'] ?? $packer['username']) ?>',
                    total_resi: <?= (int)($packer['total_resi'] ?? 0) ?>,
                    total_qty: <?= (int)($packer['total_qty'] ?? 0) ?>,
                    total_eror: <?= (int)($packer['total_eror'] ?? 0) ?>,
                    total_hari_masuk: <?= (int)($packer['total_hari_masuk'] ?? 0) ?>,
                    pct_capai: <?= (float)($packer['pct_capai'] ?? 0) ?>,
                    pct_jam_kerja: <?= (float)($packer['pct_jam_kerja'] ?? 0) ?>,
                    tim: 'INTI'
                });
            <?php endforeach; ?>
        <?php endif; ?>

        // Add TIM OTHERS packers
        <?php if (isset($dashboard_stats['top_packers_others']) && count($dashboard_stats['top_packers_others']) > 0): ?>
            <?php foreach ($dashboard_stats['top_packers_others'] as $packer): ?>
                allPackers.push({
                    name: '<?= htmlspecialchars($packer['nama_pegawai'] ?? $packer['username']) ?>',
                    total_resi: <?= (int)($packer['total_resi'] ?? 0) ?>,
                    total_qty: <?= (int)($packer['total_qty'] ?? 0) ?>,
                    total_eror: <?= (int)($packer['total_eror'] ?? 0) ?>,
                    total_hari_masuk: <?= (int)($packer['total_hari_masuk'] ?? 0) ?>,
                    pct_capai: <?= (float)($packer['pct_capai'] ?? 0) ?>,
                    pct_jam_kerja: <?= (float)($packer['pct_jam_kerja'] ?? 0) ?>,
                    tim: 'OTHERS'
                });
            <?php endforeach; ?>
        <?php endif; ?>

        // Calculate performance score for each packer (bobot sama seperti tabel)
        // % Capaian 40%, % Jam Kerja 25%, Hari Kerja 15%, Akurasi (kebalikan eror) 20%. Tidak dibatasi.
        var expectedHari = <?= (int)($dashboard_stats['expected_hari_kerja'] ?? 1) ?>;
        allPackers.forEach(function (packer) {
            var skor_capai = packer.pct_capai;       // capaian tidak dibatasi (boleh >100)
            var skor_jam   = packer.pct_jam_kerja;   // jam kerja tidak dibatasi (boleh >100)
            var akurasi    = packer.total_qty > 0
                ? Math.max(0, 100 - (packer.total_eror / packer.total_qty) * 100)
                : 100;
            var skor_hari  = expectedHari > 0 ? (packer.total_hari_masuk / expectedHari) * 100 : 0;
            packer.performance_score = Math.round((skor_capai * 0.40) + (skor_jam * 0.25) + (skor_hari * 0.15) + (akurasi * 0.20));
        });

        // Sort by performance score and get top 5
        allPackers.sort(function (a, b) {
            return b.performance_score - a.performance_score;
        });
        var top5 = allPackers.slice(0, 5);

        if (top5.length > 0) {
            var ctx2 = document.getElementById('rankingPerformanceChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: top5.map(function (p, i) { return '#' + (i + 1) + ' ' + p.name; }),
                    datasets: [{
                        label: 'Performance Score',
                        data: top5.map(function (p) { return p.performance_score; }),
                        backgroundColor: [
                            'rgba(255, 215, 0, 0.8)',  // Gold
                            'rgba(192, 192, 192, 0.8)', // Silver
                            'rgba(205, 127, 50, 0.8)',  // Bronze
                            'rgba(240, 173, 78, 0.6)',  // Orange
                            'rgba(91, 192, 222, 0.6)'   // Blue
                        ],
                        borderColor: [
                            'rgba(255, 215, 0, 1)',
                            'rgba(192, 192, 192, 1)',
                            'rgba(205, 127, 50, 1)',
                            'rgba(240, 173, 78, 1)',
                            'rgba(91, 192, 222, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Performance Score'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: function (context) {
                                    var packer = top5[context.dataIndex];
                                    return [
                                        'Skor: ' + packer.performance_score,
                                        'Total Resi: ' + packer.total_resi,
                                        '% Capaian: ' + packer.pct_capai.toFixed(1) + '%',
                                        '% Jam Kerja: ' + packer.pct_jam_kerja.toFixed(1) + '%',
                                        'Hari Kerja: ' + packer.total_hari_masuk + ' / ' + expectedHari,
                                        'Total Eror: ' + packer.total_eror,
                                        'Tim: ' + packer.tim
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }
    }
</script>

<style>
    /* Tema membuat .label-warning berwarna biru; kembalikan ke oranye */
    .label-warning {
        background-color: #f0ad4e !important;
    }

    /* Form Control */
    .form-control {
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .form-control:focus {
        border-color: #66afe9;
        outline: 0;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
    }

    .huge {
        font-size: 32px;
        font-weight: bold;
    }

    .panel-heading {
        padding: 15px;
    }

    .panel-heading .row {
        display: flex;
        align-items: center;
    }

    .panel-footer {
        padding: 10px 15px;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 20px;
    }

    .table>thead>tr>th {
        border-bottom: 2px solid #ddd;
    }

    .panel-success .panel-heading {
        background-color: #5cb85c;
        color: white;
    }

    .panel-warning .panel-heading {
        background-color: #f0ad4e;
        color: white;
    }

    .panel-primary .panel-heading,
    .panel-info .panel-heading {
        color: white;
    }

    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
    }
</style>