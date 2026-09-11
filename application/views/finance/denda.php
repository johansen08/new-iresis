<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('finance/bulk_status') ?>" method="post" id="form-bulk">

                    <div style="margin-bottom: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-square-o"></i> DONE
                        </button>
                    </div>

                    <table class="table table-striped datatable">
                        <thead>
                            <tr class="bg-navy">
                                <th width="5%" class="text-center">
                                    <input type="checkbox" id="check-all">
                                </th>
                                <th width="5%">No.</th>
                                <th>Nama File</th>
                                <th class="text-center">Link</th>
                                <th class="text-center"> Status</th>
                                <th width="20%" class="text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($data as $datum): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="ids[]" value="<?= $datum['id'] ?>" class="check-item">
                                </td>
                                <td><?= $i++; ?></td>
                                <td><?= $datum['nama_file'] ?></td>
                                <td class="text-center">
                                    <?php if (!empty($datum['link_dokumen'])): ?>
                                    <a href="<?= $datum['link_dokumen'] ?>" target="_blank" class="btn btn-info btn-xs"
                                        title="Buka Dokumen">
                                        <i class="fa fa-external-link"></i> Klik
                                    </a>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($datum['status'])): ?>
                                    DONE
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $datum['created_at'] ?></td>
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
</script>
