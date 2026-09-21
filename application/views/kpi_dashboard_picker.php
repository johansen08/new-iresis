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
                <h3 class="panel-title"><strong>Dashboard KPI Picker</strong></h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal nojs" method="post" action="<?= base_url('kpi_reports/dashboard_picker') ?>"
                    id="form-filter-kpi-picker">
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
                            <button type="button" class="btn btn-default" id="btn-refresh-kpi-picker">
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
                        <i class="fa fa-shopping-basket fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($dashboard_stats['total_picking'] ?? 0) ?></div>
                        <div>Total Picking</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Total Picked Receipts</span>
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
                <span class="pull-left">Total SKU Picked</span>
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
                        <div class="huge"><?= number_format(($dashboard_stats['avg_sku_per_picker'] ?? 0) * 100, 2) ?>%
                        </div>
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
                        <div class="huge"><?= number_format($dashboard_stats['total_active_pickers'] ?? 0) ?></div>
                        <div>Active Pickers</div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Total Active Pickers</span>
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
                <i class="fa fa-bar-chart"></i> Performa SKU Picker Per Hari <small>(Tim Inti)</small>
            </div>
            <div class="panel-body">
                <div class="chart-container">
                    <canvas id="hourlyPerformanceChart"></canvas>
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
                <i class="fa fa-trophy"></i> <strong>ROLE PICKER (TIM INTI) - Picker Performance</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #d9edf7;">
                                <th style="width: 50px;">#</th>
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
                            $rows_inti = $dashboard_stats['top_pickers_inti'] ?? [];
                            $expected_hari = $dashboard_stats['expected_hari_kerja'] ?? 1;
                            // Hitung skor performa akhir tiap picker, lalu tentukan ranking (skor tertinggi = 1)
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
                                <?php foreach ($rows_inti as $index => $picker): ?>
                                    <?php
                                    // Nilai dasar untuk baris ini
                                    $target_resi = $picker['target_resi'] ?? 0;
                                    $selisih     = $picker['selisih'] ?? 0;
                                    $pct_capai   = $picker['pct_capai'] ?? 0;
                                    $pct_jam     = $picker['pct_jam_kerja'] ?? 0;

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
                                    $performance_score = $picker['performance_score'] ?? 0;
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
                                    <tr class="clickable" data-toggle="collapse" data-target="#detail-inti-<?= $index ?>" style="cursor: pointer;">
                                        <td><i class="fa fa-plus-circle text-info"></i> <?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($picker['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($picker['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($target_resi) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_resi']) ?></td>
                                        <td class="text-right text-<?= $selisih_badge ?>"><strong><?= ($selisih > 0 ? '+' : '') . number_format($selisih) ?></strong></td>
                                        <td class="text-right"><span class="label label-<?= $pct_badge ?>"><?= number_format($pct_capai, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($picker['total_hari_masuk'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_jam_kerja'], 1) ?> Jam</td>
                                        <td class="text-right"><span class="label label-<?= $jam_pct_badge ?>"><?= number_format($pct_jam, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($picker['total_qty']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($picker['total_eror'] ?? 0) ?></strong></td>
                                        <td class="text-center">
                                            <span class="label label-<?= $status_class ?>"><?= $status ?></span>
                                            <div style="font-size: 11px; color: #888; margin-top: 3px;">Skor: <?= $performance_score ?></div>
                                        </td>
                                        <td class="text-center"><span style="font-size: 18px; font-weight: bold; color: #337ab7;">#<?= $picker['rank'] ?? '-' ?></span></td>
                                    </tr>
                                    <tr class="collapse-row">
                                        <td colspan="14" style="padding: 0; border-top: none;">
                                            <div id="detail-inti-<?= $index ?>" class="collapse" style="padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; margin: 5px 10px; border-radius: 4px;">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <strong class="text-info"><i class="fa fa-info-circle"></i> Status Resi:</strong>
                                                        <div style="margin-top: 5px;">
                                                            <strong>Resi Normal:</strong> <span class="label label-default"><?= number_format($picker['resi_normal'] ?? 0) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <strong class="text-info"><i class="fa fa-cubes"></i> Rincian Paket berdasarkan Jumlah SKU:</strong>
                                                        <div style="margin-top: 5px; display: flex; flex-wrap: wrap; gap: 8px;">
                                                            <span class="label label-primary">1 SKU: <?= number_format($picker['paket_1_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">5 SKU: <?= number_format($picker['paket_5_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">10 SKU: <?= number_format($picker['paket_10_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">20 SKU: <?= number_format($picker['paket_20_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">30 SKU: <?= number_format($picker['paket_30_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">40 SKU: <?= number_format($picker['paket_40_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">50 SKU: <?= number_format($picker['paket_50_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">&gt; 50 SKU: <?= number_format($picker['paket_50plus_sku'] ?? 0) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
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
        <div class="panel panel-warning">
            <div class="panel-heading" style="background-color: #f0ad4e; color: white;">
                <i class="fa fa-users"></i> <strong>PERBANTUAN / NON INTI - User Lain yang Bantu Picking</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #fcf8e3;">
                                <th style="width: 50px;">#</th>
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
                            $rows_others = $dashboard_stats['top_pickers_others'] ?? [];
                            $expected_hari = $dashboard_stats['expected_hari_kerja'] ?? 1;
                            // Hitung skor performa akhir tiap picker, lalu tentukan ranking (skor tertinggi = 1)
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
                                <?php foreach ($rows_others as $index => $picker): ?>
                                    <?php
                                    // Nilai dasar untuk baris ini
                                    $target_resi = $picker['target_resi'] ?? 0;
                                    $selisih     = $picker['selisih'] ?? 0;
                                    $pct_capai   = $picker['pct_capai'] ?? 0;
                                    $pct_jam     = $picker['pct_jam_kerja'] ?? 0;

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
                                    $performance_score = $picker['performance_score'] ?? 0;
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
                                    <tr class="clickable" data-toggle="collapse" data-target="#detail-others-<?= $index ?>" style="cursor: pointer;">
                                        <td><i class="fa fa-plus-circle text-info"></i> <?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($picker['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($picker['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($target_resi) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_resi']) ?></td>
                                        <td class="text-right text-<?= $selisih_badge ?>"><strong><?= ($selisih > 0 ? '+' : '') . number_format($selisih) ?></strong></td>
                                        <td class="text-right"><span class="label label-<?= $pct_badge ?>"><?= number_format($pct_capai, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($picker['total_hari_masuk'] ?? 0) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_jam_kerja'], 1) ?> Jam</td>
                                        <td class="text-right"><span class="label label-<?= $jam_pct_badge ?>"><?= number_format($pct_jam, 1) ?>%</span></td>
                                        <td class="text-right"><?= number_format($picker['total_qty']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($picker['total_eror'] ?? 0) ?></strong></td>
                                        <td class="text-center">
                                            <span class="label label-<?= $status_class ?>"><?= $status ?></span>
                                            <div style="font-size: 11px; color: #888; margin-top: 3px;">Skor: <?= $performance_score ?></div>
                                        </td>
                                        <td class="text-center"><span style="font-size: 18px; font-weight: bold; color: #337ab7;">#<?= $picker['rank'] ?? '-' ?></span></td>
                                    </tr>
                                    <tr class="collapse-row">
                                        <td colspan="14" style="padding: 0; border-top: none;">
                                            <div id="detail-others-<?= $index ?>" class="collapse" style="padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; margin: 5px 10px; border-radius: 4px;">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <strong class="text-info"><i class="fa fa-info-circle"></i> Status Resi:</strong>
                                                        <div style="margin-top: 5px;">
                                                            <strong>Resi Normal:</strong> <span class="label label-default"><?= number_format($picker['resi_normal'] ?? 0) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <strong class="text-info"><i class="fa fa-cubes"></i> Rincian Paket berdasarkan Jumlah SKU:</strong>
                                                        <div style="margin-top: 5px; display: flex; flex-wrap: wrap; gap: 8px;">
                                                            <span class="label label-primary">1 SKU: <?= number_format($picker['paket_1_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">5 SKU: <?= number_format($picker['paket_5_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">10 SKU: <?= number_format($picker['paket_10_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">20 SKU: <?= number_format($picker['paket_20_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">30 SKU: <?= number_format($picker['paket_30_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">40 SKU: <?= number_format($picker['paket_40_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">50 SKU: <?= number_format($picker['paket_50_sku'] ?? 0) ?></span>
                                                            <span class="label label-primary">&gt; 50 SKU: <?= number_format($picker['paket_50plus_sku'] ?? 0) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
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

<!-- Laporan Performa Picker (Tim Inti) -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-success">
            <div class="panel-heading" style="background-color: #5cb85c; color: white;">
                <i class="fa fa-file-text-o"></i> <strong>Laporan Performa Picker - TIM INTI</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #d9edf7;">
                                <th style="width: 50px;">#</th>
                                <th>Username</th>
                                <th>Nama Picker</th>
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
                            $pickers_inti = $dashboard_stats['top_pickers_inti'] ?? [];
                            // Sort by total_final_sku DESC
                            usort($pickers_inti, function($a, $b) {
                                return $b['total_final_sku'] <=> $a['total_final_sku'];
                            });
                            ?>
                            <?php if (count($pickers_inti) > 0): ?>
                                <?php foreach ($pickers_inti as $index => $picker): ?>
                                    <?php
                                    // Persentase eror = (total eror / 50) / total resi, ditampilkan dalam persen
                                    $total_resi_row = (float)($picker['total_resi'] ?? 0);
                                    $pct_eror = $total_resi_row > 0
                                        ? (((float)($picker['total_eror'] ?? 0) / 50) / $total_resi_row) * 100
                                        : 0;
                                    $pct_eror_badge = $pct_eror <= 1 ? 'success' : ($pct_eror <= 3 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($picker['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($picker['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($picker['total_resi']) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_qty']) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_kesalahan']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($picker['total_eror']) ?></strong></td>
                                        <td class="text-right" style="font-weight: bold; background-color: #dff0d8; color: #3c763d;">
                                            <?= number_format($picker['total_final_sku']) ?>
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

<!-- Laporan Performa Picker (Tim Perbantuan) -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-warning">
            <div class="panel-heading" style="background-color: #f0ad4e; color: white;">
                <i class="fa fa-file-text-o"></i> <strong>Laporan Performa Picker - PERBANTUAN / NON INTI</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr style="background-color: #fcf8e3;">
                                <th style="width: 50px;">#</th>
                                <th>Username</th>
                                <th>Nama Picker</th>
                                <th class="text-right">Total Resi</th>
                                <th class="text-right">Total SKU</th>
                                <th class="text-right">Total Kesalahan</th>
                                <th class="text-right">Total Eror</th>
                                <th class="text-right" style="font-weight: bold; background-color: #dff0d8; color: #3c763d;">Total Final SKU</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pickers_others = $dashboard_stats['top_pickers_others'] ?? [];
                            // Sort by total_final_sku DESC
                            usort($pickers_others, function($a, $b) {
                                return $b['total_final_sku'] <=> $a['total_final_sku'];
                            });
                            ?>
                            <?php if (count($pickers_others) > 0): ?>
                                <?php foreach ($pickers_others as $index => $picker): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($picker['username'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($picker['nama_pegawai'] ?? 'N/A') ?></td>
                                        <td class="text-right"><?= number_format($picker['total_resi']) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_qty']) ?></td>
                                        <td class="text-right"><?= number_format($picker['total_kesalahan']) ?></td>
                                        <td class="text-right text-danger"><strong><?= number_format($picker['total_eror']) ?></strong></td>
                                        <td class="text-right" style="font-weight: bold; background-color: #dff0d8; color: #3c763d;">
                                            <?= number_format($picker['total_final_sku']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No data available</td>
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
<script src="assets/js/plugins/chartjs/chart.umd.min.js"></script>

<script>
    // ===== Date range + AJAX filter (samakan dengan Dashboard KPI Packer) =====
    window.kpiPickerReportRange = <?= !empty($reportrange) ? '"' . $reportrange . '"' : '"' . date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s') . '"' ?>;

    function initKpiPickerDaterangepicker() {
        var report_range = window.kpiPickerReportRange;
        var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
        var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment();

        var $reportrange = $('#reportrange');
        if ($reportrange.length === 0) return;

        // Destroy existing instance to allow clean re-init after content replacement
        if ($reportrange.data('daterangepicker')) {
            try { $reportrange.data('daterangepicker').remove(); } catch (e) {}
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
                'This Month': [moment().startOf('month'), moment().endOf('month')],
            },
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        }, function (start, end) {
            // Callback saat rentang dipilih -> update value lalu refresh via AJAX
            var newRange = start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss');
            $reportrange.val(newRange);
            window.kpiPickerReportRange = newRange;
            $('#form-filter-kpi-picker').submit();
        });

        $reportrange.val(report_range);
    }

    $(document).ready(function () {
        initKpiPickerDaterangepicker();
    });

    // Submit filter via AJAX supaya URL tetap di root (CSS/JS tidak putus).
    // Delegated + off/on agar handler tidak dobel setelah konten diganti.
    $(document).off('submit', '#form-filter-kpi-picker').on('submit', '#form-filter-kpi-picker', function (e) {
        e.preventDefault();

        var $reportrange = $('#reportrange');
        var reportrange = null;

        try {
            var picker = $reportrange.data('daterangepicker');
            if (picker && picker.startDate && picker.endDate) {
                reportrange = picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss');
            }
        } catch (err) { /* abaikan */ }

        if (!reportrange) reportrange = $reportrange.val();
        if (!reportrange && window.kpiPickerReportRange) reportrange = window.kpiPickerReportRange;

        if (!reportrange || reportrange.trim() === '') {
            noty({ text: 'Rentang waktu tidak boleh kosong', timeout: 3000, layout: 'topRight', type: 'error' });
            return false;
        }

        $reportrange.val(reportrange);
        window.kpiPickerReportRange = reportrange;

        var loading = "<div style='max-width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 50px;'><i class='fa fa-spinner fa-spin' style='font-size: 50px;'></i><div style='margin-top: 15px; font-weight: 700; color: #444; letter-spacing: 2px; font-size: 14px;'>MEMUAT DATA...</div></div>";
        $('.page-content-wrap').html(loading);

        $.ajax({
            url: '<?= base_url('kpi_reports/dashboard_picker') ?>',
            type: 'POST',
            data: { reportrange: reportrange },
            dataType: 'json',
            success: function (response) {
                if (response && response.view) {
                    // Konten baru berisi ulang script ini; $(document).ready akan
                    // meng-init ulang daterangepicker & chart secara otomatis.
                    $('.page-content-wrap').html(response.view);

                    if (typeof formElements !== 'undefined' && formElements.init) formElements.init();
                    if (typeof uiElements !== 'undefined' && uiElements.init) uiElements.init();
                    if (typeof templatePlugins !== 'undefined' && templatePlugins.init) templatePlugins.init();
                }
                if (response && response.message) {
                    noty({ text: response.message, timeout: 3000, layout: 'topRight', type: 'success' });
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.responseText);
                noty({ text: 'Gagal memuat data. Cek console untuk detail.', timeout: 3000, layout: 'topRight', type: 'error' });
                if (xhr.responseText) $('.page-content-wrap').html(xhr.responseText);
            }
        });

        return false;
    });

    // Refresh button
    $('#btn-refresh-kpi-picker').click(function () {
        $('#form-filter-kpi-picker').submit();
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

        var url = '<?= base_url('kpi_reports/export_excel_picker') ?>?reportrange=' + encodeURIComponent(reportrange);
        window.open(url, '_blank');
    });

    // Initialize charts safely (checking if Chart.js is loaded)
    var safeInitRetryCount = 0;
    function safeInitializeCharts() {
        if (typeof Chart === 'undefined') {
            safeInitRetryCount++;
            if (safeInitRetryCount < 50) {
                console.warn('Chart.js is not defined yet, retrying in 100ms...');
                setTimeout(safeInitializeCharts, 100);
            } else {
                console.error('Failed to load Chart.js after 5 seconds. Charts will not be initialized.');
            }
        } else {
            console.log('Chart.js is loaded, initializing charts...');
            initializeCharts();
        }
    }

    $(document).ready(function () {
        safeInitializeCharts();
    });

    function initializeCharts() {
        try {
            createPickerSkuChart();
        } catch (e) {
            console.error('Error creating Picker SKU chart:', e);
        }
        try {
            createRankingChart();
        } catch (e) {
            console.error('Error creating Ranking chart:', e);
        }
    }

    function createPickerSkuChart() {
        // Khusus TIM INTI, sumber data sama dengan tabel "Laporan Performa Picker - TIM INTI"
        var pickersInti = <?= json_encode($dashboard_stats['top_pickers_inti'] ?? []) ?>;

        var allPickers = pickersInti.map(function (p) {
            return {
                name: p.nama_pegawai || p.username,
                total_resi: parseInt(p.total_resi) || 0,
                total_eror: parseInt(p.total_eror) || 0,   // total_kesalahan sudah dikali 50
                total_qty: parseInt(p.total_qty) || 0
            };
        });

        // Urutkan sama seperti tabel (Total SKU terbesar di kiri)
        allPickers.sort(function (a, b) {
            return b.total_qty - a.total_qty;
        });

        var labels = allPickers.map(function (p) { return p.name; });

        var ctx = document.getElementById('hourlyPerformanceChart').getContext('2d');

        if (window.pickerSkuChartInstance) {
            window.pickerSkuChartInstance.destroy();
        }

        window.pickerSkuChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Resi',
                        data: allPickers.map(function (p) { return p.total_resi; }),
                        backgroundColor: 'rgba(91, 192, 222, 0.7)',
                        borderColor: 'rgba(91, 192, 222, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Total Eror (x50)',
                        data: allPickers.map(function (p) { return p.total_eror; }),
                        backgroundColor: 'rgba(217, 83, 79, 0.7)',
                        borderColor: 'rgba(217, 83, 79, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Total SKU',
                        data: allPickers.map(function (p) { return p.total_qty; }),
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
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Nama Picker',
                            font: {
                                weight: 'bold'
                            }
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
        // Combine all pickers and calculate performance scores
        var allPickers = [];

        // Add TIM INTI pickers
        <?php if (isset($dashboard_stats['top_pickers_inti']) && count($dashboard_stats['top_pickers_inti']) > 0): ?>
            <?php foreach ($dashboard_stats['top_pickers_inti'] as $picker): ?>
                allPickers.push({
                    name: '<?= htmlspecialchars($picker['nama_pegawai'] ?? $picker['username']) ?>',
                    total_resi: <?= (int)($picker['total_resi'] ?? 0) ?>,
                    total_qty: <?= (int)($picker['total_qty'] ?? 0) ?>,
                    total_eror: <?= (int)($picker['total_eror'] ?? 0) ?>,
                    total_hari_masuk: <?= (int)($picker['total_hari_masuk'] ?? 0) ?>,
                    pct_capai: <?= (float)($picker['pct_capai'] ?? 0) ?>,
                    pct_jam_kerja: <?= (float)($picker['pct_jam_kerja'] ?? 0) ?>,
                    tim: 'INTI'
                });
            <?php endforeach; ?>
        <?php endif; ?>

        // Add TIM OTHERS pickers
        <?php if (isset($dashboard_stats['top_pickers_others']) && count($dashboard_stats['top_pickers_others']) > 0): ?>
            <?php foreach ($dashboard_stats['top_pickers_others'] as $picker): ?>
                allPickers.push({
                    name: '<?= htmlspecialchars($picker['nama_pegawai'] ?? $picker['username']) ?>',
                    total_resi: <?= (int)($picker['total_resi'] ?? 0) ?>,
                    total_qty: <?= (int)($picker['total_qty'] ?? 0) ?>,
                    total_eror: <?= (int)($picker['total_eror'] ?? 0) ?>,
                    total_hari_masuk: <?= (int)($picker['total_hari_masuk'] ?? 0) ?>,
                    pct_capai: <?= (float)($picker['pct_capai'] ?? 0) ?>,
                    pct_jam_kerja: <?= (float)($picker['pct_jam_kerja'] ?? 0) ?>,
                    tim: 'OTHERS'
                });
            <?php endforeach; ?>
        <?php endif; ?>

        // Calculate performance score for each picker (bobot sama seperti tabel)
        // % Capaian 40%, % Jam Kerja 25%, Hari Kerja 15%, Akurasi (kebalikan eror) 20%.
        var expectedHari = <?= (int)($dashboard_stats['expected_hari_kerja'] ?? 1) ?>;
        allPickers.forEach(function (picker) {
            var skor_capai = picker.pct_capai;       // capaian tidak dibatasi (boleh >100)
            var skor_jam   = picker.pct_jam_kerja;   // jam kerja tidak dibatasi (boleh >100)
            var akurasi    = picker.total_qty > 0
                ? Math.max(0, 100 - (picker.total_eror / picker.total_qty) * 100)
                : 100;
            var skor_hari  = expectedHari > 0 ? (picker.total_hari_masuk / expectedHari) * 100 : 0;
            picker.performance_score = Math.round((skor_capai * 0.40) + (skor_jam * 0.25) + (skor_hari * 0.15) + (akurasi * 0.20));
        });

        // Sort by performance score and get top 5
        allPickers.sort(function (a, b) {
            return b.performance_score - a.performance_score;
        });
        var top5 = allPickers.slice(0, 5);

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
                            'rgba(92, 184, 92, 0.6)',   // Green
                            'rgba(91, 192, 222, 0.6)'   // Blue
                        ],
                        borderColor: [
                            'rgba(255, 215, 0, 1)',
                            'rgba(192, 192, 192, 1)',
                            'rgba(205, 127, 50, 1)',
                            'rgba(92, 184, 92, 1)',
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
                                    var picker = top5[context.dataIndex];
                                    return [
                                        'Skor: ' + picker.performance_score,
                                        'Total Resi: ' + picker.total_resi,
                                        '% Capaian: ' + picker.pct_capai.toFixed(1) + '%',
                                        '% Jam Kerja: ' + picker.pct_jam_kerja.toFixed(1) + '%',
                                        'Hari Kerja: ' + picker.total_hari_masuk + ' / ' + expectedHari,
                                        'Total Eror: ' + picker.total_eror,
                                        'Tim: ' + picker.tim
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