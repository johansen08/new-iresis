<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-12 text-right">
        <div class="dropdown" style="display: inline-block;">
            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuNotif" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="true">
                <i class="fa fa-bell"></i> Notifikasi
                <?php if(isset($notif_count) && $notif_count > 0): ?>
                <span class="label label-danger" id="notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuNotif" style="width: 380px; padding: 0;">
                <li style="padding: 10px 15px; border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;" class="clearfix">
                    <strong class="pull-left" style="margin-top: 3px;">
                        Ada <span id="notif-text-count"><?= isset($notif_count) ? $notif_count : 0 ?></span> notifikasi baru
                    </strong>
                    <?php if(isset($notif_count) && $notif_count > 0): ?>
                        <button type="button" id="btn-mark-all-read" data-category="TIM PURCHASING" class="btn btn-primary btn-xs pull-right">
                            <i class="fa fa-check-square-o"></i> Tandai Semua
                        </button>
                    <?php endif; ?>
                </li>
                <li>
                    <div style="max-height: 350px; overflow-y: auto;">
                        <div class="list-group" style="margin-bottom: 0;">
                            <?php if(!empty($notif_list)): ?>
                                <?php foreach($notif_list as $notif): ?>
                                    <a href="javascript:void(0)" class="list-group-item notif-item <?= $notif['is_read'] == 0 ? 'list-group-item-info' : '' ?>" data-id="<?= $notif['id'] ?>">
                                        <div class="clearfix" style="margin-bottom: 5px;">
                                            <span class="label label-default pull-left">
                                                <i class="fa fa-tag"></i> <?= $notif['category'] ?>
                                            </span>
                                            <small class="text-muted pull-right">
                                                <i class="fa fa-clock-o"></i> <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                            </small>
                                        </div>
                                        <p class="list-group-item-text" style="font-weight: <?= $notif['is_read'] == 0 ? 'bold' : 'normal' ?>; color: #333; margin-top: 8px;">
                                            <?= $notif['message'] ?>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="list-group-item text-center text-muted" style="padding: 20px;">
                                    Tidak ada notifikasi
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('purchasing/giveaway') ?>" class="form-horizontal" method="post" id="form-filter">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu ACC</label>
                        <div class="col-md-3 col-xs-12">
                            <input type="text" name="reportrange" id="reportrange" class="form-control"
                                value="<?= isset($reportrange) ? $reportrange : '' ?>" autocomplete="off" readonly
                                style="background-color: #fff; cursor: pointer;" />
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">
                <?php if($this->session->flashdata('message')) echo $this->session->flashdata('message'); ?>
                
                <form action="<?= base_url('purchasing/kirim_gudang_purchasing') ?>" method="post" id="form-bulk">
                    <input type="hidden" name="no_penyesuaian" id="val_no_penyesuaian">
                    
                    <div style="margin-bottom: 15px;">
                        <button type="button" id="btn-submit" class="btn btn-primary">
                            <i class="fa fa-pencil-square-o"></i> Input No. Penyesuaian
                        </button>
                        <button type="button" class="btn btn-success" onclick="openModalGudang()">
                            <i class="fa fa-truck"></i> KIRIM KE GUDANG PURCHASING
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable" id="table-giveaway">
                            <thead>
                                <tr class="bg-navy">
                                    <th width="5%" class="text-center">
                                        <input type="checkbox" id="check-all">
                                    </th>
                                    <th>No.</th>
                                    <th>Tanggal</th>
                                    <th>SKU</th>
                                    <th>No. Rak</th>
                                    <th>Qty</th>
                                    <th>Keterangan</th>
                                    <th>ACC At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($list_giveaway)): ?>
                                <?php $i = 1; foreach ($list_giveaway as $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="ids[]" class="chk-item" value="<?= $item['id_pengembalian'] ?>">
                                    </td>
                                    <td><?= $i++; ?></td>
                                    <td><?= $item['tanggal'] ?></td>
                                    <td><?= $item['sku'] ?></td>
                                    <td><?= $item['no_rak'] ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td><?= $item['keterangan_reject'] ?></td>
                                    <td><?= $item['acc_at'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kirim Ke Gudang -->
<div class="modal fade" id="modal_kirim_gudang" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#2ecc71; color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-truck"></i> Kirim ke Gudang Purchasing</h4>
            </div>
            <div class="modal-body">
                <p>Anda akan mengirim <strong><span id="selected-count">0</span></strong> data giveaway ke gudang.</p>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="stock_kosong" name="stock_kosong" value="1">
                        Stock Jubelio Kosong (Jika dicentang, No Penyesuaian tidak wajib)
                    </label>
                </div>

                <div class="form-group">
                    <label>No. Penyesuaian <span class="text-danger">*Wajib</span></label>
                    <input type="text" id="no_penyesuaian_modal" class="form-control" placeholder="Masukkan nomor penyesuaian..." required>
                </div>

                <div class="form-group">
                    <label>Upload Foto (Maks 1MB atau gunakan Link)</label>
                    <input type="file" id="foto_upload" class="form-control" accept="image/*">
                    <input type="hidden" name="foto_base64" id="foto_base64">
                    <br>
                    <small class="text-muted">Atau masukkan Link Foto:</small>
                    <input type="text" id="foto_link" name="foto_link" class="form-control" placeholder="https://...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="submitKirimGudang()"><i class="fa fa-check"></i> Simpan & Kirim</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#table-giveaway').DataTable({
        "ordering": false
    });

    var existingVal = $('#reportrange').val();
    var start = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[0]) : moment().startOf('day');
    var end = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[1]) : moment().endOf('day');

    $('#reportrange').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        startDate: start,
        endDate: end,
        opens: 'right',
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

    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
        $('#form-filter').submit();
    });

    $('#check-all').click(function() {
        $('.chk-item').prop('checked', this.checked);
    });

    // Input No Penyesuaian (Prompt Style like Reject)
    $("#btn-submit").click(function(e) {
        e.preventDefault();
        var selected = $(".chk-item:checked").length;
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

    // Modal Gudang Logic
    window.openModalGudang = function() {
        var selected = $('.chk-item:checked').length;
        if(selected === 0) {
            alert('Pilih data terlebih dahulu!');
            return;
        }
        $('#selected-count').text(selected);
        $('#modal_kirim_gudang').modal('show');
    }

    // Photo Upload Compression Logic
    document.getElementById('foto_upload').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if(!file) return;
        
        var reader = new FileReader();
        reader.onload = function(event) {
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                
                var width = img.width;
                var height = img.height;
                var MAX_WIDTH = 1200; 
                var MAX_HEIGHT = 1200;
                
                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);
                var dataurl = canvas.toDataURL('image/jpeg', 0.6); 
                document.getElementById('foto_base64').value = dataurl;
            }
            img.src = event.target.result;
        }
        reader.readAsDataURL(file);
    });

    $('#stock_kosong').change(function() {
        if($(this).is(':checked')) {
            $('#no_penyesuaian_modal').val('Stock Jubelio Kosong').attr('readonly', true);
        } else {
            $('#no_penyesuaian_modal').val('').attr('readonly', false);
        }
    });

    window.submitKirimGudang = function() {
        var is_stock_kosong = $('#stock_kosong').is(':checked');
        var no_penyesuaian = $('#no_penyesuaian_modal').val();
        var foto_base64 = $('#foto_base64').val();
        var foto_link = $('#foto_link').val();

        if(!is_stock_kosong && no_penyesuaian.trim() === '') {
            alert('No Penyesuaian wajib diisi!');
            return;
        }

        $("#val_no_penyesuaian").val(no_penyesuaian);
        
        // Append photo data if any
        if(foto_base64 !== '') {
            $('#form-bulk').append('<input type="hidden" name="foto_base64" value="'+foto_base64+'">');
        }
        if(foto_link !== '') {
            $('#form-bulk').append('<input type="hidden" name="foto_link" value="'+foto_link+'">');
        }

        $('#form-bulk').submit();
    }
});
</script>
