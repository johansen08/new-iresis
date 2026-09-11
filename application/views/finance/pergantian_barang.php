<div class="row">
    <div class="col-md-12">

        <?php if(!empty($data)): ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('finance/bulk_acc') ?>" method="post" id="form-bulk-acc"
                    onsubmit="return validateForm()">

                    <div style="margin-bottom: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check"></i> ACC Data Terpilih
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="table-returan">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">
                                        <input type="checkbox" id="check-all">
                                    </th>
                                    <th>No. Resi</th>
                                    <th>Total Harga Barang Ganti</th>
                                    <th>Total Harga Barang Pesanan</th>
                                    <th>Kerugian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $datum): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="no_resi[]" class="check-item"
                                            value="<?= $datum['no_resi'] ?>">
                                    </td>
                                    <td><?= $datum['no_resi'] ?></td>
                                    <td>Rp <?= number_format($datum['total_ganti'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($datum['total_pesanan'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($datum['selisih'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-success text-center" role="alert">
                <h4><i class="fa fa-check-circle"></i> Selesai!</h4>
                <p>Tidak ada data pergantian barang yang perlu di-ACC saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$("#check-all").click(function() {
    var isChecked = $(this).prop('checked');
    $(".check-item").prop('checked', isChecked);
});

$(".check-item").click(function() {
    if (!$(this).prop("checked")) {
        $("#check-all").prop("checked", false);
    }
});
</script>
