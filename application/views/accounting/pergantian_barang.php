<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('accounting/bulk_no_penyesuaian') ?>" method="post" id="form-bulk">
                    <input type="hidden" name="no_penyesuaian" id="val_no_penyesuaian">
                    <div style="margin-bottom: 15px;">
                        <button type="button" id="btn-submit" class="btn btn-primary">
                            <i class="fa fa-pencil-square-o"></i> Input No. Penyesuaian
                        </button>
                    </div>
                    <table class="table table-striped datatable" id="table-pergantian-barang">
                        <thead>
                            <tr class="bg-navy">
                                <th width="5%" class="text-center">
                                    <input type="checkbox" id="check-all">
                                </th>
                                <th>No. Resi</th>
                                <th>SKU</th>
                                <th class="text-center">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($data as $datum): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="ids[]" value="<?= $datum['id'] ?>" class="check-item">
                                </td>
                                <td><?= $datum['no_resi'] ?></td>
                                <td><?= $datum['sku'] ?></td>
                                <td class="text-center"><?= $datum['jumlah'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
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

    $("#btn-submit").click(function(e) {
        e.preventDefault();

        var selected = $(".check-item:checked").length;
        if (selected === 0) {
            alert("Harap pilih data terlebih dahulu!");
            return;
        }

        var noPenyesuaian = prompt("Masukkan No. Penyesuaian:");
        if (noPenyesuaian !== null) {
            if (noPenyesuaian.trim() === "") {
                alert("No. Penyesuaian tidak boleh kosong!");
            } else {
                $("#val_no_penyesuaian").val(noPenyesuaian);
                $("#form-bulk").submit();
            }
        }
    });
</script>
