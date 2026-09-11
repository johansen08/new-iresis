<?php
$user = $this->session->userdata('user');
if (!$user || !isset($user['id_user'])) {
    redirect('welcome/restricted');
}
?>

<style>
    .nav-tabs-custom {
        margin-bottom: 20px;
        background: #fff;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border-radius: 3px;
    }
    .nav-tabs-custom > .nav-tabs {
        margin: 0;
        border-bottom-color: #f4f4f4;
        border-top-right-radius: 3px;
        border-top-left-radius: 3px;
    }
    .nav-tabs-custom > .nav-tabs > li {
        border-top: 3px solid transparent;
        margin-bottom: -2px;
        margin-right: 5px;
    }
    .nav-tabs-custom > .nav-tabs > li.active {
        border-top-color: #3c8dbc;
    }
    .nav-tabs-custom > .nav-tabs > li.active > a {
        border-top-color: transparent;
        border-left-color: #f4f4f4;
        border-right-color: #f4f4f4;
        background-color: #fff;
        color: #444;
        font-weight: 600;
    }
    .nav-tabs-custom > .nav-tabs > li > a {
        color: #444;
        border-radius: 0;
    }
    .nav-tabs-custom > .nav-tabs > li > a:hover {
        background: #fdfdfd;
        color: #333;
    }
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        border-radius: 2px;
        margin-bottom: 15px;
        border-left: 5px solid #ddd;
    }
    .info-box-icon {
        border-top-left-radius: 2px;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        font-size: 45px;
        line-height: 90px;
        background: rgba(0,0,0,0.05);
    }
    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }
    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 24px;
        font-family: 'Outfit', sans-serif;
    }
    .info-box-text {
        display: block;
        font-size: 13px;
        text-transform: uppercase;
        color: #777;
    }
    .bg-red-custom { border-left-color: #dd4b39; }
    .bg-green-custom { border-left-color: #00a65a; }
    .bg-yellow-custom { border-left-color: #f39c12; }
    .bg-blue-custom { border-left-color: #00c0ef; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong><i class="fa fa-exclamation-triangle"></i> Rekap Kesalahan Picker & Packer</strong></h3>
            </div>
            <div class="panel-body">
                <!-- Global Filters -->
                <form class="form-horizontal" id="filterForm">
                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Rentang Waktu</label>
                        <div class="col-md-3 col-xs-12">
                            <input type="text" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : null ?>" />
                        </div>
                        
                        <label class="col-md-1 col-xs-12 control-label">Periode</label>
                        <div class="col-md-2 col-xs-12">
                            <select id="filter-period" class="form-control">
                                <option value="daily">Harian</option>
                                <option value="weekly">Mingguan</option>
                                <option value="monthly">Bulanan</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 col-xs-12">
                            <button type="button" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Filter</button>
                            <button type="button" class="btn btn-primary" id="btn-export-excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
                        </div>
                    </div>
                </form>

                <hr>

                <!-- Tab System -->
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" id="recap-tabs">
                        <li class="active"><a href="#tab-picker" data-toggle="tab" data-type="picker"><i class="fa fa-shopping-basket"></i> Rekap Picker</a></li>
                        <li><a href="#tab-packer" data-toggle="tab" data-type="packer"><i class="fa fa-archive"></i> Rekap Packer</a></li>
                    </ul>
                    <div class="tab-content" style="padding: 15px 0;">
                        <!-- PICKER TAB -->
                        <div class="tab-pane active" id="tab-picker">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="datatable-picker" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th width="30">#</th>
                                            <th>Nama Picker</th>
                                            <th>Periode</th>
                                            <th>Total Resi</th>
                                            <th>Total SKU</th>
                                            <th>Total Qty</th>
                                            <th>Jumlah Salah</th>
                                            <th>Qty Salah</th>
                                            <th>Error Rate</th>
                                            <th>Rincian Kesalahan</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- PACKER TAB -->
                        <div class="tab-pane" id="tab-packer">
                            <div class="row" style="margin-bottom: 15px;">
                                <div class="col-md-12 text-right">
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modal-tambah-kesalahan"><i class="fa fa-plus"></i> Input Kesalahan Packer</button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="datatable-packer" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th width="30">#</th>
                                            <th>Nama Packer</th>
                                            <th>Periode</th>
                                            <th>Total Resi</th>
                                            <th>Total SKU</th>
                                            <th>Total Qty</th>
                                            <th>Jumlah Salah</th>
                                            <th>Qty Salah</th>
                                            <th>Error Rate</th>
                                            <th>Rincian Kesalahan</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <hr>
                            <h4><strong>10 Kesalahan Packer Terakhir Terinput</strong></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No. Resi</th>
                                            <th>Packer</th>
                                            <th>Tipe Kesalahan</th>
                                            <th>SKU Terkait</th>
                                            <th>Qty Bermasalah</th>
                                            <th>Keterangan</th>
                                            <th>Input Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_errors)): ?>
                                            <?php foreach ($recent_errors as $err): ?>
                                                <tr>
                                                    <td><?= date('d-m-Y H:i', strtotime($err['created'])) ?></td>
                                                    <td><?= $err['noresi'] ?></td>
                                                    <td><strong><?= $err['nama_packer'] ? $err['nama_packer'] : 'Tidak Terdata' ?></strong></td>
                                                    <td><span class="label label-danger"><?= $err['type_masalah'] ?></span></td>
                                                    <td><?= $err['sku'] ?></td>
                                                    <td><?= $err['qty_bermasalah'] ?></td>
                                                    <td><?= $err['keterangan'] ?></td>
                                                    <td><small><?= $err['nama_pelapor'] ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Belum ada data kesalahan packer terinput hari ini.</td>
                                            </tr>
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
</div>

<!-- Modal Input Kesalahan Packer -->
<div class="modal fade" id="modal-tambah-kesalahan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><strong>Input Kesalahan Kerja Packer</strong></h4>
            </div>
            <form id="form-masalah-packer" class="form-horizontal">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">No. Resi</label>
                        <div class="col-md-9">
                            <input type="text" name="noresi" id="input-noresi" class="form-control" required placeholder="Scan atau input nomor resi...">
                            <span class="help-block text-info" id="resi-lookup-msg">Masukkan resi untuk mencari data packer secara otomatis.</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label">Nama Packer</label>
                        <div class="col-md-9">
                            <input type="text" id="display-packer" class="form-control" readonly placeholder="Nama packer terdeteksi otomatis...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Pilih SKU</label>
                        <div class="col-md-9">
                            <select name="sku" id="select-sku" class="form-control" required>
                                <option value="">-- Masukkan nomor resi dahulu --</option>
                            </select>
                            <input type="hidden" name="qty" id="input-sku-qty" value="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Tipe Kesalahan</label>
                        <div class="col-md-9">
                            <select name="id_typemasalahpacker" id="select-tipe" class="form-control" required>
                                <option value="">-- Pilih Tipe Kesalahan --</option>
                                <?php foreach ($types_packer as $t): ?>
                                    <option value="<?= $t['id_typemasalahpacker'] ?>"><?= $t['type_masalah'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Qty Bermasalah</label>
                        <div class="col-md-9">
                            <input type="number" name="qty_bermasalah" class="form-control" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="form-group" id="group-sku-salah" style="display:none;">
                        <label class="col-md-3 control-label">SKU Salah Kirim</label>
                        <div class="col-md-9">
                            <input type="text" name="sku_salah" class="form-control" placeholder="SKU barang yang salah kirim...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Keterangan / Notes</label>
                        <div class="col-md-9">
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Tuliskan keterangan detail kesalahan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-error"><i class="fa fa-save"></i> Simpan Kesalahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;
        var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
        var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment().endOf('day');

        $('#reportrange').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            startDate: start,
            endDate: end,
            ranges: {
                'Hari Ini': [moment().startOf('day'), moment()],
                'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment()],
                'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        });

        // Initialize Picker Datatable
        var tablePicker = $('#datatable-picker').DataTable({
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': 'error_recap/get_data_picker',
                'type': 'POST',
                'data': function(d) {
                    d.reportrange = $('#reportrange').val();
                    d.period = $('#filter-period').val();
                }
            },
            'columns': [
                { 'data': 'no', 'className': 'text-center' },
                { 'data': 'nama', 'className': 'text-left' },
                { 'data': 'periode', 'className': 'text-center' },
                { 'data': 'total_resi', 'className': 'text-center' },
                { 'data': 'total_sku', 'className': 'text-center' },
                { 'data': 'total_qty', 'className': 'text-center' },
                { 'data': 'total_kesalahan', 'className': 'text-center' },
                { 'data': 'total_qty_salah', 'className': 'text-center' },
                { 
                    'data': 'error_rate', 
                    'className': 'text-center',
                    'render': function(data, type, row) {
                        var val = parseFloat(data);
                        var badgeClass = 'label-success';
                        if (val > 5) {
                            badgeClass = 'label-danger';
                        } else if (val > 2) {
                            badgeClass = 'label-warning';
                        }
                        return '<span class="label ' + badgeClass + '" style="font-size:12px;">' + data + '</span>';
                    }
                },
                { 'data': 'breakdown', 'className': 'text-left' }
            ],
            'pageLength': 25
        });

        // Initialize Packer Datatable
        var tablePacker = $('#datatable-packer').DataTable({
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': 'error_recap/get_data_packer',
                'type': 'POST',
                'data': function(d) {
                    d.reportrange = $('#reportrange').val();
                    d.period = $('#filter-period').val();
                }
            },
            'columns': [
                { 'data': 'no', 'className': 'text-center' },
                { 'data': 'nama', 'className': 'text-left' },
                { 'data': 'periode', 'className': 'text-center' },
                { 'data': 'total_resi', 'className': 'text-center' },
                { 'data': 'total_sku', 'className': 'text-center' },
                { 'data': 'total_qty', 'className': 'text-center' },
                { 'data': 'total_kesalahan', 'className': 'text-center' },
                { 'data': 'total_qty_salah', 'className': 'text-center' },
                { 
                    'data': 'error_rate', 
                    'className': 'text-center',
                    'render': function(data, type, row) {
                        var val = parseFloat(data);
                        var badgeClass = 'label-success';
                        if (val > 5) {
                            badgeClass = 'label-danger';
                        } else if (val > 2) {
                            badgeClass = 'label-warning';
                        }
                        return '<span class="label ' + badgeClass + '" style="font-size:12px;">' + data + '</span>';
                    }
                },
                { 'data': 'breakdown', 'className': 'text-left' }
            ],
            'pageLength': 25
        });

        // Search action
        $('#btn-search').click(function() {
            tablePicker.ajax.reload();
            tablePacker.ajax.reload();
        });

        // Excel Export action
        $('#btn-export-excel').click(function() {
            var activeTab = $('#recap-tabs li.active a').data('type');
            var reportrange = $('#reportrange').val();
            var period = $('#filter-period').val();
            window.open('error_recap/export_excel?type=' + activeTab + '&reportrange=' + encodeURIComponent(reportrange) + '&period=' + period, '_blank');
        });

        // Auto lookup Packer and SKU when Resi is filled
        var timer;
        $('#input-noresi').on('input', function() {
            var noresi = $(this).val().trim();
            clearTimeout(timer);
            if (noresi.length < 5) return;

            timer = setTimeout(function() {
                $('#resi-lookup-msg').html('<i class="fa fa-spinner fa-spin"></i> Mencari resi...');
                $.ajax({
                    url: 'error_recap/get_receipt_details',
                    type: 'POST',
                    dataType: 'json',
                    data: { noresi: noresi },
                    success: function(resp) {
                        if (resp.status === 'success' && resp.data.length > 0) {
                            var packerName = resp.data[0].nama_packer || 'Belum di-packing / Tidak Teridentifikasi';
                            $('#display-packer').val(packerName);
                            $('#resi-lookup-msg').html('<i class="fa fa-check text-success"></i> Packer terdeteksi: <strong>' + packerName + '</strong>');
                            
                            // Load SKUs
                            var $selectSku = $('#select-sku');
                            $selectSku.empty().append('<option value="">-- Pilih SKU --</option>');
                            resp.data.forEach(function(row) {
                                if (row.sku) {
                                    $selectSku.append('<option value="' + row.sku + '" data-qty="' + row.jumlah + '">' + row.sku + ' (Qty: ' + row.jumlah + ')</option>');
                                }
                            });
                        } else {
                            $('#display-packer').val('');
                            $('#select-sku').empty().append('<option value="">-- Masukkan nomor resi dahulu --</option>');
                            $('#resi-lookup-msg').html('<i class="fa fa-times text-danger"></i> ' + (resp.message || 'Resi tidak ditemukan'));
                        }
                    },
                    error: function() {
                        $('#resi-lookup-msg').html('<i class="fa fa-times text-danger"></i> Gagal koneksi ke server.');
                    }
                });
            }, 500);
        });

        // Track SKU qty
        $('#select-sku').change(function() {
            var selectedOpt = $(this).find('option:selected');
            var qty = selectedOpt.data('qty') || 1;
            $('#input-sku-qty').val(qty);
        });

        // Toggle SKU Salah field based on problem type
        $('#select-tipe').change(function() {
            var val = $(this).val();
            // Tipe Salah Barang (2) atau Salah Resi (3)
            if (val === '2' || val === '3') {
                $('#group-sku-salah').slideDown();
            } else {
                $('#group-sku-salah').slideUp();
                $('#group-sku-salah input').val('');
            }
        });

        // Save error form submission
        $('#form-masalah-packer').submit(function(e) {
            e.preventDefault();
            
            var packerVal = $('#display-packer').val();
            if (!packerVal || packerVal.includes('Belum di-packing')) {
                alert('Tolong cari resi yang valid dan telah di-packing terlebih dahulu.');
                return;
            }

            var formData = $(this).serialize();
            $('#btn-save-error').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: 'error_recap/save_packer_error',
                type: 'POST',
                dataType: 'json',
                data: formData,
                success: function(resp) {
                    $('#btn-save-error').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Kesalahan');
                    if (resp.error === false) {
                        alert('Berhasil menyimpan kesalahan packer!');
                        $('#modal-tambah-kesalahan').modal('hide');
                        $('#form-masalah-packer')[0].reset();
                        $('#select-sku').empty().append('<option value="">-- Masukkan nomor resi dahulu --</option>');
                        $('#display-packer').val('');
                        $('#resi-lookup-msg').text('Masukkan resi untuk mencari data packer secara otomatis.');
                        tablePacker.ajax.reload();
                        
                        // Dynamic page reload simulation to refresh recent items if needed, or simply reload page section
                        if (typeof load_page === 'function') {
                            load_page('error_recap');
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(resp.message || 'Gagal menyimpan kesalahan.');
                    }
                },
                error: function() {
                    $('#btn-save-error').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Kesalahan');
                    alert('Terjadi kesalahan koneksi server.');
                }
            });
        });
    });
</script>
