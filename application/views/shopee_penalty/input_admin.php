<div class="row">
    <div class="col-md-6 center-block" style="float: none;">
        <form action="shopee_penalty/save-penalty" method="post" enctype="multipart/form-data" class="form-horizontal">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Input Pembatalan Marketplace (Tim CS)</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Nama CS</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" value="<?= $this->session->userdata('user')['name'] ?>" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Scan Resi</label>
                        <div class="col-md-9">
                            <input type="text" name="noresi" id="noresi" class="form-control" required autofocus />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Toko Shopee</label>
                        <div class="col-md-9">
                            <select name="id_shopee_shop" class="form-control select" required>
                                <option value="" selected disabled>Pilih Toko Shopee</option>
                                <?php foreach ($shops as $s): ?>
                                <option value="<?= $s['id_shopee_shop'] ?>"><?= $s['nama_toko'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Platform</label>
                        <div class="col-md-9">
                            <select name="id_marketplace" class="form-control select">
                                <?php foreach ($marketplaces as $m): ?>
                                <option value="<?= $m['id_marketplace'] ?>" <?= stripos($m['nama_marketplace'], 'shopee') !== false ? 'selected' : '' ?>><?= $m['nama_marketplace'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Nilai Pesanan</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-addon">Rp</span>
                                <input type="number" name="nilai_pesanan" class="form-control" required />
                            </div>
                            <span class="help-block text-danger">*Poin akan dihitung 10% otomatis oleh sistem</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Alasan Batal</label>
                        <div class="col-md-9">
                            <textarea name="alasan_batal" class="form-control" rows="3" placeholder="Masukkan alasan pembatalan..." required></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Bukti Resi</label>
                        <div class="col-md-9">
                            <input type="file" name="file_bukti" class="form-control" required />
                            <span class="help-block">Format: JPG, PNG, PDF (Scan/Foto)</span>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <button type="submit" class="btn btn-primary">Simpan Data Pinalti</button>
                    <button type="reset" class="btn btn-default pull-right">Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>
