<style>
/* LUXURY DESIGN SYSTEM - REFINED */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --brand-indigo: #4338ca;
    --brand-rose: #e11d48;
    --brand-emerald: #059669;
    --brand-amber: #d97706;
    --brand-slate: #1e293b;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --bg-premium: #f8fafc;
    --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
}

.panel-body, .summary-wrapper, .stat-card, .marketplace-card {
    font-family: 'Outfit', sans-serif !important;
}

.table-val, .stat-card-value, .pct-badge {
    font-family: 'Inter', sans-serif !important;
}

/* Base styling */
#capture-cards, #capture-summary {
    background: #f1f5f9 !important;
    padding: 30px !important;
    border-radius: 30px !important;
}

/* Marketplace Scroll Container */
.marketplace-container {
    display: flex;
    overflow-x: auto;
    gap: 30px;
    padding: 10px 5px 35px 5px;
    scroll-behavior: smooth;
}

.marketplace-container::-webkit-scrollbar { height: 8px; }
.marketplace-container::-webkit-scrollbar-track { background: transparent; }
.marketplace-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* Executive Summary Cards */
.analysis-premium-card {
    background: #fff;
    border-radius: 32px;
    border: 1px solid #edf2f7;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    padding: 35px;
    margin-bottom: 40px;
    position: relative;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 24px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    transition: all 0.4s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow-hover);
}

.stat-card-title {
    font-size: 11px;
    font-weight: 800;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.stat-card-value {
    font-size: 32px;
    font-weight: 900;
    color: var(--text-main);
    letter-spacing: -1px;
}

/* Marketplace Cards - LUXURY REVISION */
.labels-column {
    flex: 0 0 240px;
    background: rgba(248, 250, 252, 0.9);
    backdrop-filter: blur(10px);
    position: sticky;
    left: 0;
    z-index: 20;
    border-radius: 24px;
    box-shadow: 15px 0 35px -15px rgba(0,0,0,0.08);
}

.labels-column .mp-header {
    background: var(--brand-slate);
    color: #fff;
    border-radius: 24px 0 0 0;
    height: 90px;
    padding: 0 25px;
}

.labels-column .detail-label {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 800;
    letter-spacing: 1.2px;
}

.marketplace-card {
    flex: 0 0 210px;
    background: #ffffff;
    border-radius: 30px;
    border: 1px solid #f1f5f9;
    box-shadow: var(--card-shadow);
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}

.marketplace-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 60px -12px rgba(0,0,0,0.15);
}

.marketplace-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 30px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5);
    pointer-events: none;
}

.mp-header {
    padding: 0 25px;
    height: 90px;
    background: #fff;
    border-bottom: 1px solid #f8fafc;
}

.mp-title {
    font-size: 17px;
    font-weight: 900;
    color: var(--text-main);
    letter-spacing: -0.5px;
}

.mp-subtitle {
    font-size: 11px;
    font-weight: 700;
    color: var(--brand-indigo);
    background: var(--primary-light);
    padding: 2px 10px;
    border-radius: 10px;
    width: fit-content;
    margin-top: 5px;
}

.mp-body { padding: 20px 25px; }

.detail-row {
    height: 48px;
    border-bottom: 1px solid #f8fafc;
}

.detail-value {
    font-size: 17px;
    font-weight: 800;
    color: var(--text-main);
}

.pct-badge {
    font-size: 9px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 12px;
}

/* Premium Colors */
.detail-value.special { color: var(--brand-indigo); }
.detail-value.sku1 { color: var(--brand-emerald); }
.detail-value.sku29 { color: var(--brand-amber); }
.detail-value.qtybanyak { color: var(--brand-rose); }

.detail-row.grand-total {
    border-top: 1px solid var(--slate-200);
    margin-top: 10px;
    padding-top: 10px;
    background: #fafafa;
    border-bottom: none;
}

/* Modern Analytics Table - LUXURY REVISION */
.summary-wrapper {
    background: #fff;
    border-radius: 40px;
    padding: 45px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.03);
}

.summary-title {
    font-size: 26px;
    font-weight: 900;
    color: var(--text-main);
    letter-spacing: -1px;
}

.summary-title i {
    color: #fff;
    background: var(--brand-indigo);
    width: 50px;
    height: 50px;
    border-radius: 18px;
    box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2);
}

.modern-table { border-spacing: 0 15px; }

.modern-table th {
    padding: 10px 30px;
    color: var(--text-muted);
    font-size: 10px;
}

.data-row td {
    padding: 25px 30px;
    background: #fff;
}

.data-row:hover td { background: #fcfcfc; }

.data-row td:first-child {
    font-weight: 900;
    font-size: 15px;
    color: var(--text-main);
}

.table-val { font-size: 18px; font-weight: 900; }

.pct-badge.premium {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.pct-blue { background: #e0e7ff; color: #4338ca; }
.pct-green { background: #d1fae5; color: #059669; }
.pct-amber { background: #fef3c7; color: #d97706; }
.pct-rose { background: #ffe4e6; color: #e11d48; }

.progress-bar-premium {
    background: linear-gradient(to right, var(--brand-indigo), #6366f1);
    box-shadow: 0 2px 4px rgba(67, 56, 202, 0.2);
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Laporan Pesanan Masuk</h3>
                <div class="pull-right">
                    <form method="post" id="form-filter" action="monitoring/laporan_pesanan_masuk" style="display:inline-block; margin-right: 10px;">
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" name="reportrange" id="reportrange" value="<?= $reportrange ?>">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit" name="action" value="filter"><i class="fa fa-search"></i> Filter</button>
                            </span>
                        </div>
                    </form>
                    <button class="btn btn-success" onclick="exportExcel()"><i class="fa fa-file-excel-o"></i> Export</button>
                    <div class="btn-group">
                        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown"><i class="fa fa-camera"></i> Screenshot <span class="caret"></span></button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a href="javascript:void(0)" onclick="takeScreenshot('capture-cards', 'Cards')">SS Kartu Atas</a></li>
                            <li><a href="javascript:void(0)" onclick="takeScreenshot('capture-summary', 'Summary')">SS Ringkasan Bawah</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-primary" onclick="toggleAnalysis()" style="background: var(--primary); border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); font-weight: 700; padding: 6px 15px;">
                        <i class="fa fa-bar-chart"></i> Analisa Visual
                    </button>
                </div>
            </div>
            
            <div class="panel-body" style="background-color: #f5f5f5; padding: 20px;">
                <?php 
                    // Pre-calculate global totals for the whole page
                    $g_total = 0;
                    $g_special = 0;
                    $g_sku1 = 0;
                    $g_sku29 = 0;
                    $g_qtybanyak = 0;
                    $g_cancel = 0;
                    if(!empty($marketplaces)) {
                        foreach($marketplaces as $mp) {
                            $g_total += ($mp['total_pesanan'] ?: 0);
                            $g_special += ($mp['total_special'] ?: 0);
                            $g_sku1 += ($mp['total_1sku_sd9'] ?: 0);
                            $g_sku29 += ($mp['total_2_9sku_sd9'] ?: 0);
                            $g_qtybanyak += ($mp['total_qty_banyak'] ?: 0);
                            $g_cancel += ($mp['total_cancel'] ?: 0);
                        }
                    }
                ?>
                
                <!-- Visual Analysis Section REDESIGNED -->
                <div id="analysis-section" style="display: none; margin-bottom: 40px;">
                    <div class="analysis-premium-card">
                        <div class="chart-title">
                            <i class="fa fa-dashboard"></i>
                            Executive Summary Analysis
                        </div>
                        
                        <div class="stat-summary-row">
                            <div class="stat-card">
                                <span class="stat-card-title">Volume Penjualan</span>
                                <span class="stat-card-value"><?= number_format($total_semua) ?></span>
                                <span class="stat-card-trend trend-up"><i class="fa fa-level-up"></i> Live Tracking</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-card-title">Resi Cancel</span>
                                <span class="stat-card-value"><?= number_format($g_cancel) ?></span>
                                <span class="stat-card-trend trend-down"><i class="fa fa-warning"></i> <?= $g_total > 0 ? round(($g_cancel / $g_total) * 100, 1) : 0 ?>% dari total</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-card-title">Resi Special</span>
                                <span class="stat-card-value"><?= number_format($g_special) ?></span>
                                <span class="stat-card-trend trend-up" style="color: #3b82f6;"><i class="fa fa-star"></i> High Priority</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-card-title">Marketplace Aktif</span>
                                <span class="stat-card-value"><?= count($marketplaces) ?></span>
                                <span class="stat-card-trend" style="color: #64748b;"><i class="fa fa-globe"></i> Channel Terintegrasi</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-title" style="font-size: 13px;">
                                    <i class="fa fa-pie-chart" style="padding: 4px; font-size: 11px;"></i>
                                    Marketplace Share
                                </div>
                                <div style="height: 220px;" id="chart-mp-share"><svg></svg></div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-title" style="font-size: 13px;">
                                    <i class="fa fa-tasks" style="padding: 4px; font-size: 11px;"></i>
                                    Order Complexity Breakdown
                                </div>
                                <div style="height: 220px;" id="chart-cat-dist"><svg></svg></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="capture-cards" style="background-color: #f5f5f5; padding: 10px; border-radius: 10px;">
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin:0; font-weight:bold;">Total Seluruh Pesanan: <span style="color:#33414E; font-size: 24px;"><?= number_format($total_semua) ?></span></h4>
                        <p class="text-muted">Periode: <?= $reportrange ?></p>
                    </div>

                    <div class="marketplace-container">
                        <!-- Labels Column -->
                        <div class="labels-column">
                            <div class="mp-header">
                                <h3 class="mp-title" style="visibility: hidden;">LABEL</h3>
                                <div class="mp-subtitle" style="visibility: hidden;">SUBTITLE</div>
                            </div>
                            <div class="mp-body">
                                <div class="detail-row"><span class="detail-label">RESI SPECIAL</span></div>
                                <div class="detail-row"><span class="detail-label">1 SKU & QTY S/D 9</span></div>
                                <div class="detail-row"><span class="detail-label">2-9 SKU & QTY S/D 9</span></div>
                                <div class="detail-row"><span class="detail-label">QTY BANYAK (>9)</span></div>
                                <div class="detail-row grand-total"><span class="detail-label">GRAND TOTAL</span></div>
                                <div class="detail-row"><span class="detail-label">RESI CANCEL</span></div>
                            </div>
                        </div>

                    <?php if(!empty($marketplaces)): ?>
                        <?php foreach($marketplaces as $mp): ?>
                            <?php 
                                $total = $mp['total_pesanan'] ?: 0;
                                $percentage = ($total_semua > 0) ? round(($total / $total_semua) * 100, 1) : 0;
                                $qty = $mp['total_qty'] ?: 0;
                                $cancel = $mp['total_cancel'] ?: 0;
                                
                                $special = $mp['total_special'] ?: 0;
                                $sku1 = $mp['total_1sku_sd9'] ?: 0;
                                $sku29 = $mp['total_2_9sku_sd9'] ?: 0;
                                $qtybanyak = $mp['total_qty_banyak'] ?: 0;

                                // Deteksi nama marketplace untuk css class
                                $mp_class = 'Default';
                                if(stripos($mp['nama_marketplace'], 'shopee') !== false) $mp_class = 'Shopee';
                                if(stripos($mp['nama_marketplace'], 'tiktok') !== false || stripos($mp['nama_marketplace'], 'tokopedia') !== false) $mp_class = 'Tiktok';
                                if(stripos($mp['nama_marketplace'], 'lazada') !== false) $mp_class = 'Lazada';
                            ?>
                             <div class="marketplace-card <?= $mp_class ?>">
                                 <!-- Baris Pertama: Nama Marketplace -->
                                 <div class="mp-header">
                                     <?php 
                                        $icon = 'fa-shopping-cart';
                                        if($mp_class == 'Shopee') $icon = 'fa-shopping-bag';
                                        if($mp_class == 'Lazada') $icon = 'fa-heart';
                                        if($mp_class == 'Tiktok') $icon = 'fa-music';
                                     ?>
                                     <h3 class="mp-title clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', 'all', 'TOTAL PESANAN')">
                                         <i class="fa <?= $icon ?>" style="margin-right: 8px; color: var(--brand-indigo);"></i>
                                         <?= htmlspecialchars($mp['nama_marketplace']) ?>
                                     </h3>
                                     <!-- Di bawahnya sedikit: Total Resi & Persentase (Font kecil, bold) -->
                                     <div class="mp-subtitle">
                                         <?= number_format($total) ?> RESI <span style="opacity: 0.6; font-size: 9px; margin-left: 4px;"><?= $percentage ?>%</span>
                                     </div>
                                 </div>
                                <div class="mp-body">
                                    <!-- Baris Kedua: Breakdown Detail -->
                                    <?php 
                                        $total = $mp['total_pesanan'] ?: 0;
                                        $cancel = $mp['total_cancel'] ?: 0;
                                        $special = $mp['total_special'] ?: 0;
                                        $sku1 = $mp['total_1sku_sd9'] ?: 0;
                                        $sku29 = $mp['total_2_9sku_sd9'] ?: 0;
                                        $qtybanyak = $mp['total_qty_banyak'] ?: 0;

                                        $p_cancel = $total > 0 ? round(($cancel / $total) * 100, 1) : 0;
                                        $p_special = $total > 0 ? round(($special / $total) * 100, 1) : 0;
                                        $p_sku1 = $total > 0 ? round(($sku1 / $total) * 100, 1) : 0;
                                        $p_sku29 = $total > 0 ? round(($sku29 / $total) * 100, 1) : 0;
                                        $p_qtybanyak = $total > 0 ? round(($qtybanyak / $total) * 100, 1) : 0;
                                    ?>
                                     <div class="detail-row">
                                         <span class="detail-value special clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', 'special', 'RESI SPECIAL')">
                                             <?= number_format($special) ?> 
                                             <span class="pct-badge pct-blue"><?= $p_special ?>%</span>
                                         </span>
                                     </div>
                                     <div class="detail-row">
                                         <span class="detail-value sku1 clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', '1sku', '1 SKU & QTY S/D 9')">
                                             <?= number_format($sku1) ?>
                                             <span class="pct-badge pct-green"><?= $p_sku1 ?>%</span>
                                         </span>
                                     </div>
                                     <div class="detail-row">
                                         <span class="detail-value sku29 clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', '29sku', '2-9 SKU & QTY S/D 9')">
                                             <?= number_format($sku29) ?>
                                             <span class="pct-badge pct-amber"><?= $p_sku29 ?>%</span>
                                         </span>
                                     </div>
                                     <div class="detail-row">
                                         <span class="detail-value qtybanyak clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', 'qty_banyak', 'QTY BANYAK')">
                                             <?= number_format($qtybanyak) ?>
                                             <span class="pct-badge pct-rose"><?= $p_qtybanyak ?>%</span>
                                         </span>
                                     </div>
                                    <div class="detail-row grand-total">
                                        <span class="detail-value clickable" style="color: #0f172a;" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', 'all', 'TOTAL PESANAN')">
                                            <?= number_format($total) ?>
                                            <span class="pct-badge">100%</span>
                                        </span>
                                    </div>
                                     <div class="detail-row">
                                         <span class="detail-value cancel clickable" onclick="showResiDetail('<?= $mp['nama_marketplace'] ?>', 'cancel', 'RESI CANCEL')">
                                             <?= number_format($cancel) ?>
                                             <span class="pct-badge" style="background: #f1f5f9; color: #64748b;"><?= $p_cancel ?>%</span>
                                         </span>
                                     </div>
                                </div>
                                 <div class="progress progress-small">
                                     <div class="progress-bar-premium" style="width: <?= $percentage ?>%;"></div>
                                 </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Grand Total Card -->
                        <?php 
                            $gp_special = $g_total > 0 ? round(($g_special / $g_total) * 100, 1) : 0;
                            $gp_sku1 = $g_total > 0 ? round(($g_sku1 / $g_total) * 100, 1) : 0;
                            $gp_sku29 = $g_total > 0 ? round(($g_sku29 / $g_total) * 100, 1) : 0;
                            $gp_qtybanyak = $g_total > 0 ? round(($g_qtybanyak / $g_total) * 100, 1) : 0;
                            $gp_cancel = $g_total > 0 ? round(($g_cancel / $g_total) * 100, 1) : 0;
                        ?>
                        <div class="marketplace-card" style="border-top-color: #33414E; background: #fcfcfc;">
                            <div class="mp-header" style="background: #33414E; color: #fff;">
                                <h3 class="mp-title" style="color: #fff;">TOTAL SELURUHNYA</h3>
                                <div class="mp-subtitle" style="color: #cbd5e1;">
                                    <?= number_format($g_total) ?> RESI (100%)
                                </div>
                            </div>
                            <div class="mp-body">
                                <div class="detail-row">
                                    <span class="detail-value special">
                                        <?= number_format($g_special) ?> 
                                        <span class="pct-badge"><?= $gp_special ?>%</span>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-value sku1">
                                        <?= number_format($g_sku1) ?>
                                        <span class="pct-badge"><?= $gp_sku1 ?>%</span>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-value sku29">
                                        <?= number_format($g_sku29) ?>
                                        <span class="pct-badge"><?= $gp_sku29 ?>%</span>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-value qtybanyak">
                                        <?= number_format($g_qtybanyak) ?>
                                        <span class="pct-badge"><?= $gp_qtybanyak ?>%</span>
                                    </span>
                                </div>
                                <div class="detail-row grand-total">
                                    <span class="detail-value" style="color: #0f172a;">
                                        <?= number_format($g_total) ?>
                                        <span class="pct-badge">100%</span>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-value cancel">
                                        <?= number_format($g_cancel) ?>
                                        <span class="pct-badge"><?= $gp_cancel ?>%</span>
                                    </span>
                                </div>
                            </div>
                            <div class="progress progress-small">
                                <div class="progress-bar progress-bar-info" role="progressbar" style="width: 100%;"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning" style="width: 100%;">Tidak ada data pesanan pada periode ini.</div>
                    <?php endif; ?>
                </div>

                <!-- Restore Modern Detailed Summary -->
                <div id="capture-summary" style="background-color: #f5f5f5; padding: 10px; border-radius: 10px; margin-top: 20px;">
                    <div class="summary-wrapper">
                        <div class="summary-header">
                            <div class="summary-title">
                                <i class="fa fa-line-chart"></i>
                                Marketplace Performance Analytics
                            </div>
                            <div class="badge badge-info" style="padding: 8px 15px; border-radius: 20px; font-weight: 700; background: #f1f5f9; color: #475569;">
                                <i class="fa fa-calendar"></i> <?= $reportrange ?>
                            </div>
                        </div>

                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="modern-table" style="min-width: 1000px;">
                                <thead>
                                    <tr>
                                        <th>Marketplace</th>
                                        <th class="text-center">Total Pesanan</th>
                                        <th class="text-center">Resi Special</th>
                                        <th class="text-center">1 SKU (Qty ≤9)</th>
                                        <th class="text-center">2-9 SKU (Qty ≤9)</th>
                                        <th class="text-center">Qty Banyak (>9)</th>
                                        <th class="text-center" style="border-radius: 0 10px 10px 0;">Cancel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($marketplaces)): ?>
                                        <?php foreach ($marketplaces as $mp): 
                                            $total = $mp['total_pesanan'] ?: 0;
                                            $p_cancel = $total > 0 ? round(($mp['total_cancel'] / $total) * 100, 1) : 0;
                                            $p_special = $total > 0 ? round(($mp['total_special'] / $total) * 100, 1) : 0;
                                            $p_sku1 = $total > 0 ? round(($mp['total_1sku_sd9'] / $total) * 100, 1) : 0;
                                            $p_sku29 = $total > 0 ? round(($mp['total_2_9sku_sd9'] / $total) * 100, 1) : 0;
                                            $p_qtybanyak = $total > 0 ? round(($mp['total_qty_banyak'] / $total) * 100, 1) : 0;
                                        ?>
                                            <tr class="data-row">
                                                <td style="min-width: 150px;"><?= $mp['nama_marketplace'] ?></td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val"><?= number_format($total) ?></span>
                                                         <span class="pct-badge premium pct-slate">100%</span>
                                                     </div>
                                                 </td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val" style="color: var(--primary);"><?= number_format($mp['total_special']) ?></span>
                                                         <span class="pct-badge premium pct-blue"><?= $p_special ?>%</span>
                                                     </div>
                                                 </td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val" style="color: var(--success);"><?= number_format($mp['total_1sku_sd9']) ?></span>
                                                         <span class="pct-badge premium pct-green"><?= $p_sku1 ?>%</span>
                                                     </div>
                                                 </td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val" style="color: var(--warning);"><?= number_format($mp['total_2_9sku_sd9']) ?></span>
                                                         <span class="pct-badge premium pct-amber"><?= $p_sku29 ?>%</span>
                                                     </div>
                                                 </td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val" style="color: var(--danger);"><?= number_format($mp['total_qty_banyak']) ?></span>
                                                         <span class="pct-badge premium pct-rose"><?= $p_qtybanyak ?>%</span>
                                                     </div>
                                                 </td>
                                                 <td align="center">
                                                     <div class="table-cell-content">
                                                         <span class="table-val" style="color: #64748b;"><?= number_format($mp['total_cancel']) ?></span>
                                                         <span class="pct-badge premium pct-slate"><?= $p_cancel ?>%</span>
                                                     </div>
                                                 </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center" style="padding: 50px; color: #94a3b8;">Tidak ada data operasional</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Resi -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="modal-title-text">Detail Resi</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="table-detail">
                        <thead>
                            <tr>
                                <th>No Resi</th>
                                <th>Tanggal Batas Kirim</th>
                                <th>Status Pesanan</th>
                                <th>SKUs</th>
                                <th>Total Qty</th>
                            </tr>
                        </thead>
                        <tbody id="table-detail-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load html2canvas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
$(document).ready(function() {
    $('#reportrange').daterangepicker({
        locale: { format: 'YYYY-MM-DD HH:mm:ss' },
        timePicker: true,
        timePicker24Hour: true,
        timePickerSeconds: true,
        ranges: {
           'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
           'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
           '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
           'Bulan Ini': [moment().startOf('month'), moment().endOf('month')]
        }
    });
});

function exportExcel() {
    var form = document.getElementById('form-filter');
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    input.value = 'export';
    form.appendChild(input);
    form.submit();
    
    // remove it back so filter works normally next time
    setTimeout(function(){ form.removeChild(input); }, 1000);
}

function takeScreenshot(elementId, suffix) {
    var element = document.getElementById(elementId);
    
    // Disable button and show loading
    var btn = document.querySelector('.btn-group .dropdown-toggle');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    // Scroll to element to ensure it's in view for capture
    element.scrollIntoView();

    html2canvas(element, {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#f5f5f5',
        logging: false,
        onclone: function(clonedDoc) {
            // Optional: modify cloned doc if needed
            clonedDoc.getElementById(elementId).style.overflow = 'visible';
        }
    }).then(function(canvas) {
        btn.innerHTML = originalHtml;
        btn.disabled = false;

        try {
            var link = document.createElement('a');
            link.download = 'Laporan_Pesanan_Masuk_' + suffix + '_' + moment().format('YYYYMMDD_HHmmss') + '.png';
            link.href = canvas.toDataURL('image/png');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (e) {
            console.error("Screenshot download failed", e);
            alert("Gagal mengunduh gambar. Silakan coba klik kanan pada gambar di tab baru.");
            var win = window.open();
            win.document.write('<img src="' + canvas.toDataURL('image/png') + '"/>');
        }
    }).catch(function(err) {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        console.error("html2canvas error", err);
        alert('Gagal mengambil screenshot. Pastikan koneksi internet stabil.');
    });
}
function showResiDetail(marketplace, indicator, label) {
    $('#modal-title-text').text('Detail ' + label + ' - ' + marketplace);
    $('#table-detail-body').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading data...</td></tr>');
    $('#modal-detail').modal('show');

    if ($.fn.DataTable.isDataTable('#table-detail')) {
        $('#table-detail').DataTable().destroy();
    }

    $.ajax({
        url: '<?= base_url("monitoring/get_marketplace_resi_detail") ?>',
        type: 'POST',
        data: {
            marketplace: marketplace,
            indicator: indicator,
            reportrange: $('#reportrange').val()
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                var html = '';
                resp.data.forEach(function(r) {
                    html += `<tr>
                        <td>${r.noresi}</td>
                        <td>${r.tanggal_bataskirim}</td>
                        <td><span class="label label-${r.status_pesanan.includes('CANCEL') ? 'danger' : 'info'}">${r.status_pesanan}</span></td>
                        <td>${r.distinct_skus}</td>
                        <td>${r.total_qty}</td>
                    </tr>`;
                });
                $('#table-detail-body').html(html);
                $('#table-detail').DataTable({
                    "order": [[1, "asc"]],
                    "pageLength": 25
                });
            } else {
                $('#table-detail-body').html('<tr><td colspan="5" class="text-center text-danger">Gagal mengambil data</td></tr>');
            }
        },
        error: function() {
            $('#table-detail-body').html('<tr><td colspan="5" class="text-center text-danger">Error server</td></tr>');
        }
    });
}
function toggleAnalysis() {
    var section = $('#analysis-section');
    if (section.is(':visible')) {
        section.slideUp();
    } else {
        section.slideDown(function() {
            renderCharts();
        });
    }
}

function renderCharts() {
    // 1. MP Share Data
    var mpData = [
        <?php foreach($marketplaces as $mp): ?>
        { label: "<?= $mp['nama_marketplace'] ?>", value: <?= $mp['total_pesanan'] ?: 0 ?> },
        <?php endforeach; ?>
    ];

    nv.addGraph(function() {
        var chart = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true)
            .labelThreshold(.05)
            .labelType("percent")
            .donut(true)
            .donutRatio(0.35);

        d3.select("#chart-mp-share svg")
            .datum(mpData)
            .transition().duration(350)
            .call(chart);

        return chart;
    });

    // 2. Category Distribution Data
    var catData = [
        { label: "SPECIAL", value: <?= $g_special ?> },
        { label: "1 SKU", value: <?= $g_sku1 ?> },
        { label: "2-9 SKU", value: <?= $g_sku29 ?> },
        { label: "QTY BANYAK", value: <?= $g_qtybanyak ?> },
        { label: "CANCEL", value: <?= $g_cancel ?> }
    ];

    nv.addGraph(function() {
        var chart = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true)
            .color(['#2563eb', '#16a34a', '#d97706', '#e11d48', '#64748b'])
            .labelType("value");

        d3.select("#chart-cat-dist svg")
            .datum(catData)
            .transition().duration(350)
            .call(chart);

        return chart;
    });
}
</script>
