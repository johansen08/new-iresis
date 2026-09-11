<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Monitoring Shopee Penalty - Hari Ini (<?= date('d M Y') ?>)</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <?php foreach ($stats as $id => $s): ?>
                    <div class="col-md-3">
                        <div class="widget widget-default widget-item-icon">
                            <div class="widget-item-left">
                                <span class="fa fa-shopping-cart"></span>
                            </div>
                            <div class="widget-data">
                                <div class="widget-int num-count"><?= number_format($s['total_orders']) ?></div>
                                <div class="widget-title"><?= $s['nama_toko'] ?></div>
                                <div class="widget-subtitle">Pesanan Hari Ini</div>
                            </div>
                            <div class="widget-controls">
                                <span class="label label-warning" title="Akumulasi Poin Pinalti">
                                    <i class="fa fa-warning"></i> <?= number_format($s['total_points'], 1) ?> Poin
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Ringkasan Pelanggaran Terbanyak</h3>
            </div>
            <div class="panel-body">
                <p>Fitur ini akan segera hadir dengan grafik chart.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Informasi Pinalti Shopee</h3>
            </div>
            <div class="panel-body">
                <ul>
                    <li><strong>Pembatalan Admin:</strong> Diinput manual oleh CS, poin dihitung 10% dari nilai pesanan.</li>
                    <li><strong>Batas Kirim (Auto Cancel):</strong> Terdeteksi otomatis jika resi belum dipack melewati deadline.</li>
                    <li><strong>Retur Masalah:</strong> Terdeteksi dari database retur dengan status KURANG atau REJECT.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
