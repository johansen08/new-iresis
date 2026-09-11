<div class="row">
    <div class="col-md-12">
        <form action="<?= base_url('accounting/proses_acc_bulk') ?>" method="post" id="form-bulk-acc">
            
            <div class="panel panel-default">
                <div class="panel-body">
                    <p>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-check"></i> ACC Semua
                        </button>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="table-returan">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal Buka Retur</th>
                                    <th>Status Buka</th>
                                    <th>Status Detail Buka</th>
                                    <th>Resi Buka</th>
                                    <th>Hasil Scan Buka</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($list_returan_buka)): ?>
                                    <?php $i = 1; foreach ($list_returan_buka as $returan_buka): ?>
                                    <tr>
                                        <td>
                                            <?= $i++; ?>
                                            <input type="hidden" name="ids[]" value="<?= $returan_buka['id_bukaretur'] // SESUAIKAN DENGAN VARIABEL ID ANDA ?>">
                                        </td>
                                        <td><?= $returan_buka['tanggal_buka_retur'] ?></td>
                                        <td><?= $returan_buka['status_buka'] ?></td>
                                        <td><?= $returan_buka['status_detail_buka'] ?></td>
                                        <td><?= $returan_buka['resi_buka'] ?></td>
                                        <td><?= $returan_buka['hasil_scan_buka'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#table-returan').DataTable({
        "order": [], 
        "language": {
            "emptyTable": "Tidak ada returan buka hari ini",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(disaring dari _MAX_ total data)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            },
        }
    });

    $('#form-bulk-acc').on('submit', function(e) {
        var form = this;
        var rows = table.rows({ 'search': 'applied' }).nodes();
        var count = rows.length;

        if(count === 0) {
            e.preventDefault();
            alert('Tidak ada data yang ditampilkan untuk di-ACC!');
            return false;
        }

        if(!confirm('Apakah Anda yakin ingin meng-ACC semua ' + count + ' data yang ditampilkan ini?')) {
            e.preventDefault();
            return false;
        }

        $(rows).each(function() {
            var inputID = $(this).find('input[name="ids[]"]');
            
            if (!$.contains(document, inputID[0])) {
                $(form).append(
                    $('<input>').attr('type', 'hidden').attr('name', 'ids[]').val(inputID.val())
                );
            }
        });
        
    });
});
</script>
