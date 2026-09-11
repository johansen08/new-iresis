<div class="row">
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Penugasan Picker Special Hari Ini (<?= date('d M Y') ?>)</strong></h3>
            </div>
            <div class="panel-body">
                <form id="form-special-assignment" class="form-horizontal">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label col-md-4">Picker</label>
                            <div class="col-md-8">
                                <select name="id_pegawaipicker" class="form-control select" data-live-search="true" required>
                                    <option value="">-- Pilih Picker --</option>
                                    <?php foreach ($list_picker as $picker) : ?>
                                        <option value="<?= $picker['id_pegawai'] ?>" <?= (isset($current_assignment->id_pegawaipicker) && $current_assignment->id_pegawaipicker == $picker['id_pegawai']) ? 'selected' : '' ?>>
                                            <?= $picker['nama_pegawai'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label col-md-4">Status</label>
                            <div class="col-md-8">
                                <select name="status_performa_id" class="form-control select" data-live-search="true" required>
                                    <option value="">-- Pilih Status --</option>
                                    <?php foreach ($list_status_performa as $status) : ?>
                                        <option value="<?= $status['id_statusperforma'] ?>" <?= (isset($current_assignment->status_performa_id) && $current_assignment->status_performa_id == $status['id_statusperforma']) ? 'selected' : '' ?>>
                                            <?= $status['status_name'] ?> (<?= $status['kode_status'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-info" id="btn-save-assignment">Simpan Penugasan</button>
                        <?php if (isset($current_assignment->nama_pegawai)): ?>
                            <span class="label label-success" style="margin-left: 10px;">Aktif: <?= $current_assignment->nama_pegawai ?> (<?= $current_assignment->status_name ?>)</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title" style="display:inline-block;"><strong>Master Special SKU</strong></h3>
                <div class="pull-right">
                    <button class="btn btn-info" id="btn-analyze-special">Analisa Otomatis</button>
                    <button class="btn btn-default" id="btn-undo-reset">Undo Reset</button>
                    <button class="btn btn-warning" id="btn-reset-all">Reset Semua ke Non-Special</button>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="panel-body">
                <table class="table table-striped datatable-sku-special">
                    <thead>
                        <tr>
                            <th width="10%">ID SKU</th>
                            <th>Nama SKU</th>
                            <th width="20%">Status Special</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        const table = $('.datatable-sku-special').DataTable({
            'scrollX': true,
            'pageLength': 10,
            'processing': true,
            'serverSide': true,
            'order': [
                [0, 'desc']
            ],
            'ajax': {
                url: 'sku_special/get_skus_data',
                type: 'POST',
            }
        });

        $('#form-special-assignment').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btn-save-assignment');
            $btn.attr('disabled', true);

            $.ajax({
                url: 'sku_special/save_daily_assignment',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    noty({
                        text: response.message,
                        layout: 'topRight',
                        type: 'success',
                        timeout: 2000
                    });
                    setTimeout(() => location.reload(), 1000); // Reload to reflect status
                },
                error: function(xhr) {
                    let errorMessage = 'Gagal menyimpan penugasan';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        errorMessage = res.message || errorMessage;
                    } catch (e) {}
                    
                    noty({
                        text: errorMessage,
                        layout: 'topRight',
                        type: 'error',
                        timeout: 3000
                    });
                },
                complete: function() {
                    $btn.attr('disabled', false);
                }
            });
        });

        $(document).on('change', '.select-is-special', function() {
            const id_sku = $(this).data('id');
            const is_special = $(this).val();
            const $select = $(this);

            $select.attr('disabled', true);

            $.ajax({
                url: 'sku_special/update_status',
                type: 'POST',
                data: {
                    id_sku: id_sku,
                    is_special: is_special
                },
                dataType: 'json',
                success: function(response) {
                    noty({
                        text: response.message,
                        layout: 'topRight',
                        type: 'success',
                        timeout: 2000
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Gagal mengupdate status';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        errorMessage = res.message || errorMessage;
                    } catch (e) {}
                    
                    noty({
                        text: errorMessage,
                        layout: 'topRight',
                        type: 'error',
                        timeout: 3000
                    });
                    
                    // Revert on error? Maybe just reload table
                    table.ajax.reload(null, false);
                },
                complete: function() {
                    $select.attr('disabled', false);
                }
            });
        });

        $('#btn-reset-all').on('click', function() {
            if (confirm('Apakah Anda yakin ingin merubah semua SKU special menjadi non-special?')) {
                const $btn = $(this);
                $btn.attr('disabled', true);
                
                $.ajax({
                    url: 'sku_special/reset_all',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        noty({
                            text: response.message,
                            layout: 'topRight',
                            type: 'success',
                            timeout: 2000
                        });
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Gagal mereset status';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            errorMessage = res.message || errorMessage;
                        } catch (e) {}
                        
                        noty({
                            text: errorMessage,
                            layout: 'topRight',
                            type: 'error',
                            timeout: 3000
                        });
                    },
                    complete: function() {
                        $btn.attr('disabled', false);
                    }
                });
            }
        });

        $('#btn-undo-reset').on('click', function() {
            if (confirm('Apakah Anda yakin ingin mengembalikan SKU status ke sebelum di reset (Undo)?')) {
                const $btn = $(this);
                $btn.attr('disabled', true);
                
                $.ajax({
                    url: 'sku_special/undo_reset_all',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        noty({
                            text: response.message,
                            layout: 'topRight',
                            type: 'success',
                            timeout: 2000
                        });
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Gagal mengembalikan status';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            errorMessage = res.message || errorMessage;
                        } catch (e) {}
                        
                        noty({
                            text: errorMessage,
                            layout: 'topRight',
                            type: 'error',
                            timeout: 3000
                        });
                    },
                    complete: function() {
                        $btn.attr('disabled', false);
                    }
                });
            }
        });

        $('#btn-analyze-special').on('click', function() {
            if (confirm('Apakah Anda yakin ingin menjalankan analisa otomatis? Ini akan mengubah SKU yang memenuhi kriteria (1 Resi 1 Tipe SKU dengan Jumlah 1, minimal 3 resi) menjadi special.')) {
                const $btn = $(this);
                $btn.attr('disabled', true);
                $btn.text('Menganalisa...');
                
                $.ajax({
                    url: 'sku_special/analyze',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        noty({
                            text: response.message,
                            layout: 'topRight',
                            type: 'success',
                            timeout: 3000
                        });
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Gagal menjalankan analisa otomatis';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            errorMessage = res.message || errorMessage;
                        } catch (e) {}
                        
                        noty({
                            text: errorMessage,
                            layout: 'topRight',
                            type: 'error',
                            timeout: 3000
                        });
                    },
                    complete: function() {
                        $btn.attr('disabled', false);
                        $btn.text('Analisa Otomatis');
                    }
                });
            }
        });
    });
</script>

