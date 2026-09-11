<div class="row">
    <div class="col-md-12">
        
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('accounting/riwayat_surat_jalan_tp') ?>" class="form-horizontal" method="post" id="form-riwayat-surat-jalan-tp">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
                        <div class="col-md-3 col-xs-12">
                            <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= $reportrange ?>" autocomplete="off" />
                        </div>
                        <!-- <div class="col-md-2 col-xs-12">
                            <button type="submit" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Cari</button>
                        </div> -->
                    </div>
                </form>
            </div>
        </div> 
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="table-returan">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama File</th>
                                    <th class="text-center">Link</th>
                                    <th class="text-center">Jenis</th>
                                    <th>SKU</th>
                                    <th>Nama Pegawai</th>
                                    <th>Created At</th>
                                    <th width="100" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($list_riwayat_surat_jalan_tp)): ?>
                                    <?php $i = 1; foreach ($list_riwayat_surat_jalan_tp as $riwayat_surat_jalan_tp): ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= $riwayat_surat_jalan_tp['nama_file'] ?></td>
                                        <td class="text-center">
                                            <?php if(!empty($riwayat_surat_jalan_tp['link_dokumen'])): ?>
                                                <a href="<?= $riwayat_surat_jalan_tp['link_dokumen'] ?>" target="_blank" class="btn btn-primary btn-xs">
                                                    <i class="fa fa-external-link"></i> Buka
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $riwayat_surat_jalan_tp['jenis'] ?? '-' ?></td>
                                        <td><?= $riwayat_surat_jalan_tp['sku'] ?></td>
                                        <td><?= $riwayat_surat_jalan_tp['nama_pegawai'] ?></td>
                                        <td><?= $riwayat_surat_jalan_tp['created_at'] ?></td>
                                        <td class="text-center" style="white-space: nowrap;">
                                            <a href="javascript:void(0);" 
                                                class="btn btn-warning btn-xs btn-edit-surat-jalan-tp"
                                                data-id="<?= $riwayat_surat_jalan_tp['id'] ?>"
                                                data-namafile="<?= $riwayat_surat_jalan_tp['nama_file'] ?>"
                                                data-linkdokumen="<?= $riwayat_surat_jalan_tp['link_dokumen'] ?>"
                                                data-jenis="<?= $riwayat_surat_jalan_tp['jenis'] ?>"
                                                data-sku="<?= $riwayat_surat_jalan_tp['sku'] ?>"
                                                title="Edit Surat Jalan">
                                                <i class="fa fa-pencil"></i>
                                            </a>

                                            <button type="button" 
                                                    class="btn btn-danger btn-xs btn-delete" 
                                                    data-url="<?= base_url('accounting/delete-surat-jalan-tp/'.$riwayat_surat_jalan_tp['id']) ?>" 
                                                    title="Delete">
                                                <i class="fa fa-trash-o"></i>
                                            </button>
                                        </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>

<div class="modal fade" id="modalEditSuratJalan" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalLabel">Edit Surat Jalan (TP)</h4>
            </div>
            
            <form action="<?= base_url('accounting/update-surat-jalan-tp') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="form-group">
                        <label>Nama File</label>
                        <input type="text" class="form-control" name="nama_file" id="edit_nama_file" required>
                        <small class="text-muted">Silahkan ubah nama file di atas.</small>
                    </div>

                    <div class="form-group">
                        <label>Link Dokumen</label>
                        <input type="text" class="form-control" name="link_dokumen" id="edit_link_dokumen" required>
                        <small class="text-muted">Pastikan link dapat diakses publik!</small>
                    </div>

                    <div class="form-group">
                        <label>Jenis</label>
                        <select class="form-control" name="jenis" id="edit_jenis" required>
                            <option value="PRIORITAS">PRIORITAS</option>
                            <option value="TIM PICKER">TIM PICKER</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#table-returan').DataTable({
        "ordering": true,
        "order": [[ 5, "desc" ]],
        "language": {
            "emptyTable": "Tidak ada data surat jalan"
        },
        "columnDefs": [
            { "orderable": false, "targets": 5 }
        ]
    });

    var existingVal = $('#reportrange').val();

    var start = existingVal ? moment(existingVal.split(" - ")[0]) : moment().startOf('day');
    var end   = existingVal ? moment(existingVal.split(" - ")[1]) : moment().endOf('day');

    $('#reportrange').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        startDate: start,
        endDate: end,
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss',
            cancelLabel: 'Clear'
        }
    });

    $('#reportrange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
        $('#form-riwayat-surat-jalan-tp').submit(); 
    });

    $('#table-returan').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var table = $('#table-returan').DataTable();
        var url = $(this).data('url');
        var confirmDelete = confirm("Apakah Anda yakin ingin menghapus data ini?");
        
        if (confirmDelete) {
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    alert(response.message);
                    table.row(row).remove().draw(false);
                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem';
                    alert(errorMessage);
                }
            });
        }
    });

    $(document).on('click', '.btn-edit-surat-jalan-tp', function() {
        var id = $(this).data('id');
        var namaFile = $(this).data('namafile');
        var linkDokumen = $(this).data('linkdokumen');
        var jenis = $(this).data('jenis');

        $('#edit_id').val(id);
        $('#edit_nama_file').val(namaFile);
        $('#edit_link_dokumen').val(linkDokumen);
        $('#edit_jenis').val(jenis);

        $('#modalEditSuratJalan').modal('show');
    });
});
</script>
