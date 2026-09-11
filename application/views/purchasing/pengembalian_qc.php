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
                <form action="<?= base_url('purchasing/pengembalian_qc') ?>" class="form-horizontal" method="post" id="form-pengembalian-qc">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu (Tgl Masuk)</label>
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
                <form action="<?= base_url('purchasing/process_bulk_qc') ?>" method="post" id="form-bulk-action">
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-md-12 text-right">
                            <input type="hidden" name="keterangan" id="bulk_keterangan" value="">
                            <input type="hidden" name="no_penyesuaian_bulk" id="bulk_no_penyesuaian" value="">
                            <button type="button" class="btn btn-warning" onclick="openModalBulk('repair')">
                                <i class="fa fa-wrench"></i> Repair
                            </button>
                            <button type="button" class="btn btn-danger" onclick="openModalBulk('reject')">
                                <i class="fa fa-times-circle"></i> Reject
                            </button>
                            <button type="button" class="btn btn-info" onclick="openModalBulk('giveaway')" style="background-color: #8e44ad; border-color: #8e44ad; color: white;">
                                <i class="fa fa-gift"></i> Giveaway
                            </button>
                            <button type="button" class="btn btn-primary" onclick="openModalBulk('tolak')">
                                <i class="fa fa-ban"></i> Tolak Pengembalian
                            </button>
                            <button type="button" class="btn btn-default" onclick="openModalBulk('tidak_ada')">
                                <i class="fa fa-search-minus"></i> Barang Tidak Ada
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="table-pengembalian-qc">
                            <thead>
                                <tr>
                                    <th width="4%" class="text-center">
                                        <input type="checkbox" id="check-all">
                                    </th>
                                    <th>#</th>
                                    <th>Gambar SKU</th>
                                    <th>Tgl Masuk</th>
                                    <th>SKU</th>
                                    <th>No. Rak</th>
                                    <th>Qty</th>
                                    <th>Kondisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($list_pengembalian_qc)): ?>
                                <?php $i = 1; foreach ($list_pengembalian_qc as $pqc): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="ids[]" class="chk-item"
                                            value="<?= $pqc['id_pengembalian'] ?>"
                                            data-sku="<?= $pqc['sku'] ?>"
                                            data-qty="<?= $pqc['qty'] ?>">
                                    </td>
                                    <td><?= $i++ ?></td>
                                    <td class="text-center" style="width:70px;">
                                        <?php
                                            // Coba ambil gambar dari tblsku berdasarkan SKU
                                            $sku_img = base_url('assets/img/no-image.png');
                                        ?>
                                        <img src="<?= base_url('purchasing/get_sku_img?sku='.urlencode($pqc['sku'])) ?>"
                                             alt="<?= $pqc['sku'] ?>"
                                             class="sku-thumb"
                                             data-sku="<?= $pqc['sku'] ?>"
                                             style="width:50px;height:50px;object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid #ddd;"
                                             onerror="this.src='<?= base_url('assets/img/no-image.png') ?>'"
                                             onclick="openSkuImg(this.src, '<?= $pqc['sku'] ?>')">
                                    </td>
                                    <td><?= $pqc['tanggal'] ?></td>
                                    <td><?= $pqc['sku'] ?></td>
                                    <td><?= $pqc['no_rak'] ?></td>
                                    <td><?= $pqc['qty'] ?></td>
                                    <td><?= $pqc['kondisi'] ?></td>
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

<!-- Modal Gambar SKU (Zoom) -->
<div class="modal fade" id="modal_sku_img" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal_sku_img_title">Gambar SKU</h4>
            </div>
            <div class="modal-body text-center">
                <img id="modal_sku_img_src" src="" style="max-width:100%;border-radius:8px;" alt="SKU Image">
            </div>
        </div>
    </div>
</div>

<!-- Modal Repair (wajib notes) -->
<div class="modal fade" id="modal_repair" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#f39c12;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-wrench"></i> Proses REPAIR</h4>
            </div>
            <div class="modal-body">
                <p><strong><span id="repair_selected_count">0</span> data</strong> akan diproses sebagai REPAIR.</p>
                <div class="form-group">
                    <label>Catatan / Keterangan <span class="text-danger">*Wajib</span></label>
                    <textarea id="repair_keterangan" class="form-control" rows="3" placeholder="Masukkan keterangan kerusakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" onclick="submitModal('repair')"><i class="fa fa-wrench"></i> Proses Repair</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject (wajib notes) -->
<div class="modal fade" id="modal_reject" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#e74c3c;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-times-circle"></i> Proses REJECT</h4>
            </div>
            <div class="modal-body">
                <p><strong><span id="reject_selected_count">0</span> data</strong> akan diproses sebagai REJECT.</p>
                <div class="form-group">
                    <label>Catatan / Alasan Reject <span class="text-danger">*Wajib</span></label>
                    <textarea id="reject_keterangan" class="form-control" rows="3" placeholder="Masukkan alasan reject..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitModal('reject')"><i class="fa fa-times-circle"></i> Proses Reject</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Giveaway (wajib notes) -->
<div class="modal fade" id="modal_giveaway" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#8e44ad;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-gift"></i> Proses GIVEAWAY</h4>
            </div>
            <div class="modal-body">
                <p><strong><span id="giveaway_selected_count">0</span> data</strong> akan diproses sebagai GIVEAWAY.</p>
                <div class="form-group">
                    <label>Catatan / Keterangan <span class="text-danger">*Wajib</span></label>
                    <textarea id="giveaway_keterangan" class="form-control" rows="3" placeholder="Masukkan keterangan giveaway..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="submitModal('giveaway')"><i class="fa fa-gift"></i> Proses Giveaway</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Pengembalian (Partial Qty) -->
<div class="modal fade" id="modal_tolak_qty" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Tolak Pengembalian (Pilih Qty)</h4>
            </div>
            <div class="modal-body">
                <p>Tentukan jumlah barang yang ingin ditolak. Sisa qty akan tetap ada sebagai data terpisah.</p>
                <table class="table table-bordered" id="table-tolak-items">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Qty Awal</th>
                            <th>Qty Ditolak</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitTolak()">Proses Tolak</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Barang Tidak Ada (Partial Qty per SKU) -->
<div class="modal fade" id="modal_tidak_ada" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-search-minus"></i> Barang Tidak Ada</h4>
            </div>
            <div class="modal-body">
                <p>Tentukan qty yang <strong>Tidak Ada</strong>. Sisa qty (jika ada) akan tetap di list untuk diproses lain.</p>
                <table class="table table-bordered" id="table-tidak-ada-items">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Qty Total</th>
                            <th>Qty Tidak Ada</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-default" onclick="submitTidakAda()"><i class="fa fa-check"></i> Proses</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable
    $('#table-pengembalian-qc').DataTable({
        "ordering": false,
        "language": { "emptyTable": "Tidak ada data Pengembalian QC" },
        "columnDefs": [{ "orderable": false, "targets": 0 }]
    });

    // DateRangePicker
    var existingVal = $('#reportrange').val();
    var start = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[0]) : moment().startOf('day');
    var end   = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[1]) : moment().endOf('day');

    $('#reportrange').daterangepicker({
        timePicker: true, timePicker24Hour: true,
        startDate: start, endDate: end, opens: 'right',
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
        },
        locale: { format: 'YYYY-MM-DD HH:mm:ss', cancelLabel: 'Clear' }
    });
    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
        $('#form-pengembalian-qc').submit();
    });

    // Check All
    $('#check-all').click(function() { $('.chk-item').prop('checked', this.checked); });
    $(document).on('change', '.chk-item', function() {
        if (!$(this).prop("checked")) $("#check-all").prop('checked', false);
        if ($('.chk-item:checked').length == $('.chk-item').length) $("#check-all").prop('checked', true);
    });

    // Notifikasi klik
    $('.notif-item').click(function(e) {
        e.preventDefault();
        var listItem = $(this);
        var notifId = listItem.data('id');
        if (listItem.hasClass('list-group-item-info')) {
            $.ajax({
                url: '<?= base_url("purchasing/mark_notif_read") ?>',
                type: 'POST', data: { id: notifId }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        listItem.removeClass('list-group-item-info');
                        listItem.find('.list-group-item-text').css('font-weight', 'normal');
                        var currentCount = parseInt($('#notif-badge').text());
                        if (!isNaN(currentCount) && currentCount > 0) {
                            var newCount = currentCount - 1;
                            $('#notif-text-count').text(newCount);
                            if (newCount > 0) { $('#notif-badge').text(newCount); }
                            else { $('#notif-badge').remove(); $('#btn-mark-all-read').fadeOut(); }
                        }
                    }
                }
            });
        }
    });

    $('#btn-mark-all-read').click(function(e) {
        e.preventDefault(); e.stopPropagation();
        var btnMarkAll = $(this);
        var category = btnMarkAll.data('category');
        if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
            $.ajax({
                url: '<?= base_url("purchasing/mark_all_notif_read") ?>',
                type: 'POST', data: { category: category }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('.notif-item').removeClass('list-group-item-info');
                        $('.notif-item').find('.list-group-item-text').css('font-weight', 'normal');
                        $('#notif-text-count').text('0');
                        $('#notif-badge').remove();
                        btnMarkAll.fadeOut();
                    } else { alert('Gagal menandai notifikasi.'); }
                },
                error: function() { alert('Terjadi kesalahan pada server.'); }
            });
        }
    });
});

// Zoom gambar SKU
function openSkuImg(src, sku) {
    $('#modal_sku_img_title').text('SKU: ' + sku);
    $('#modal_sku_img_src').attr('src', src);
    $('#modal_sku_img').modal('show');
}

// Buka modal sesuai action
function openModalBulk(action) {
    var selected = $('.chk-item:checked').length;
    if (selected === 0) {
        alert('Silakan pilih data terlebih dahulu!');
        return;
    }

    if (action === 'repair') {
        $('#repair_selected_count').text(selected);
        $('#repair_keterangan').val('');
        $('#modal_repair').modal('show');

    } else if (action === 'reject') {
        $('#reject_selected_count').text(selected);
        $('#reject_keterangan').val('');
        $('#modal_reject').modal('show');

    } else if (action === 'giveaway') {
        $('#giveaway_selected_count').text(selected);
        $('#giveaway_keterangan').val('');
        $('#modal_giveaway').modal('show');

    } else if (action === 'tolak') {
        var html = '';
        $('.chk-item:checked').each(function() {
            var id = $(this).val();
            var sku = $(this).data('sku');
            var maxQty = $(this).data('qty');
            html += '<tr>';
            html += '<td>' + sku + '</td>';
            html += '<td>' + maxQty + '</td>';
            html += '<td><input type="number" name="qty_proses[' + id + ']" class="form-control" max="' + maxQty + '" min="1" value="' + maxQty + '"></td>';
            html += '</tr>';
        });
        $('#table-tolak-items tbody').html(html);
        $('#modal_tolak_qty').modal('show');

    } else if (action === 'tidak_ada') {
        // Issue 5 fix: tampilkan per-item qty input
        var html = '';
        $('.chk-item:checked').each(function() {
            var id = $(this).val();
            var sku = $(this).data('sku');
            var maxQty = $(this).data('qty');
            html += '<tr>';
            html += '<td>' + sku + '</td>';
            html += '<td>' + maxQty + '</td>';
            html += '<td><input type="number" name="qty_tidak_ada[' + id + ']" class="form-control" max="' + maxQty + '" min="1" value="' + maxQty + '"></td>';
            html += '</tr>';
        });
        $('#table-tidak-ada-items tbody').html(html);
        $('#modal_tidak_ada').modal('show');
    }
}

// Submit dari modal repair/reject/giveaway
function submitModal(action) {
    var keterangan = '';
    var no_penyesuaian = '';

    if (action === 'repair') {
        keterangan = $('#repair_keterangan').val().trim();
        if (keterangan === '') {
            alert('Catatan/Keterangan wajib diisi untuk REPAIR!');
            return;
        }
        $('#modal_repair').modal('hide');

    } else if (action === 'reject') {
        keterangan = $('#reject_keterangan').val().trim();
        if (keterangan === '') {
            alert('Alasan Reject wajib diisi!');
            return;
        }
        $('#bulk_no_penyesuaian').val('');
        $('#modal_reject').modal('hide');

    } else if (action === 'giveaway') {
        keterangan = $('#giveaway_keterangan').val().trim();
        if (keterangan === '') {
            alert('Keterangan wajib diisi untuk GIVEAWAY!');
            return;
        }
        $('#bulk_no_penyesuaian').val('');
        $('#modal_giveaway').modal('hide');
    }

    $('#bulk_keterangan').val(keterangan);
    $('#form-bulk-action input[name="action_type"]').remove();
    $('#form-bulk-action').append('<input type="hidden" name="action_type" value="' + action + '">');
    $('#form-bulk-action').submit();
}

function submitTolak() {
    if (confirm('Anda yakin ingin MENOLAK PENGEMBALIAN barang terpilih dengan QTY yang ditentukan?')) {
        $('#bulk_keterangan').val('');
        $('#form-bulk-action input[name="action_type"]').remove();
        $('#form-bulk-action').append('<input type="hidden" name="action_type" value="tolak">');
        $('#form-bulk-action').submit();
    }
}

function submitTidakAda() {
    if (confirm('Proses barang yang dipilih sebagai BARANG TIDAK ADA?')) {
        $('#bulk_keterangan').val('');
        $('#form-bulk-action input[name="action_type"]').remove();
        $('#form-bulk-action').append('<input type="hidden" name="action_type" value="tidak_ada">');
        $('#form-bulk-action').submit();
    }
}
</script>
