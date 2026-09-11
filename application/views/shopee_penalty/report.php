<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Filter Laporan Shopee Penalty</h3>
            </div>
            <div class="panel-body">
                <form method="get" class="form-horizontal">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Mulai</label>
                            <div class="col-md-8">
                                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Selesai</label>
                            <div class="col-md-8">
                                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Toko Shopee</label>
                            <div class="col-md-8">
                                <select name="id_shopee_shop" class="form-control select">
                                    <option value="">Semua Toko</option>
                                    <?php foreach ($shops as $s): ?>
                                    <option value="<?= $s['id_shopee_shop'] ?>" <?= $id_shopee_shop == $s['id_shopee_shop'] ? 'selected' : '' ?>><?= $s['nama_toko'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-block">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_2" data-toggle="tab">Resi Batal Batas Kirim (Auto Cancel)</a></li>
                <li><a href="#tab_3" data-toggle="tab">Laporan Pembatalan Admin</a></li>
                <li><a href="#tab_4" data-toggle="tab">Laporan Retur (Salah/Kurang)</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tab_2">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Resi</th>
                                <th>Toko</th>
                                <th>Batas Kirim</th>
                                <th>Status Pinalti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($auto_cancellations as $r): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $r['noresi'] ?></td>
                                <td><?= $r['nama_toko'] ?></td>
                                <td>
                                    <?php 
                                        $is_today = (!empty($r['tanggal_bataskirim']) && date('Y-m-d', strtotime($r['tanggal_bataskirim'])) == date('Y-m-d'));
                                        $display = $r['tanggal_bataskirim'] ?: '-';
                                        echo $is_today ? '<span style="color: red; font-weight: bold;">' . $display . '</span>' : $display;
                                    ?>
                                </td>
                                <td><span class="label label-danger">Auto Cancel</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane" id="tab_3">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Resi</th>
                                <th>No Pesanan</th>
                                <th>Toko</th>
                                <th>Nilai Pesanan</th>
                                <th>Poin Penalty (10%)</th>
                                <th>CS</th>
                                <th>Alasan</th>
                                <th>Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $total_nilai = 0;
                            $total_poin = 0;
                            foreach ($admin_cancellations as $r): 
                                $total_nilai += $r['nilai_pesanan'];
                                $total_poin += $r['poin_penalty'];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $r['noresi'] ?></td>
                                <td><?= $r['no_pesanan'] ?></td>
                                <td><?= $r['nama_toko'] ?></td>
                                <td>Rp <?= number_format($r['nilai_pesanan'], 0, ',', '.') ?></td>
                                <td><?= number_format($r['poin_penalty'], 1) ?></td>
                                <td><?= $r['cs_name'] ?></td>
                                <td><?= $r['alasan_batal'] ?></td>
                                <td><a href="assets/uploads/shopee_penalty/<?= $r['file_bukti'] ?>" target="_blank" class="btn btn-xs btn-default">Lihat</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f9f9f9;">
                                <td colspan="4" class="text-right">GRAND TOTAL:</td>
                                <td>Rp <?= number_format($total_nilai, 0, ',', '.') ?></td>
                                <td><?= number_format($total_poin, 1) ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="tab-pane" id="tab_4">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Resi</th>
                                <th>Toko</th>
                                <th>Status Retur</th>
                                <th>Detail Masalah</th>
                                <th>Tanggal Retur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($return_penalties as $r): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $r['noresi'] ?></td>
                                <td><?= $r['nama_toko'] ?></td>
                                <td><?= $r['status_retur'] ?></td>
                                <td><?= $r['status_detail'] ?></td>
                                <td><?= $r['tanggal_resiretur'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
