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
            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuNotif"
                style="width: 380px; padding: 0;">

                <li style="padding: 10px 15px; border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"
                    class="clearfix">
                    <strong class="pull-left" style="margin-top: 3px;">
                        Ada <span id="notif-text-count"><?= isset($notif_count) ? $notif_count : 0 ?></span> notifikasi
                        baru
                    </strong>

                    <?php if(isset($notif_count) && $notif_count > 0): ?>
                    <button type="button" id="btn-mark-all-read" data-category="TIM INBOUND"
                        class="btn btn-primary btn-xs pull-right">
                        <i class="fa fa-check-square-o"></i> Tandai Semua
                    </button>
                    <?php endif; ?>
                </li>

                <li>
                    <div style="max-height: 350px; overflow-y: auto;">
                        <div class="list-group" style="margin-bottom: 0;">
                            <?php if(!empty($notif_list)): ?>
                            <?php foreach($notif_list as $notif): ?>
                            <a href="javascript:void(0)"
                                class="list-group-item notif-item <?= $notif['is_read'] == 0 ? 'list-group-item-info' : '' ?>"
                                data-id="<?= $notif['id'] ?>">

                                <div class="clearfix" style="margin-bottom: 5px;">
                                    <span class="label label-default pull-left">
                                        <i class="fa fa-tag"></i> <?= $notif['category'] ?>
                                    </span>
                                    <small class="text-muted pull-right">
                                        <i class="fa fa-clock-o"></i>
                                        <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                    </small>
                                </div>

                                <p class="list-group-item-text"
                                    style="font-weight: <?= $notif['is_read'] == 0 ? 'bold' : 'normal' ?>; color: #333; margin-top: 8px;">
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
                <form action="<?= base_url('inbound/laporan-surat-jalan-tp') ?>" class="form-horizontal" method="post"
                    id="form-laporan-surat-jalan-tp">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
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

                <form action="<?= base_url('inbound/update_status_surat_jalan_tp') ?>" method="post" id="form-bulk">

                    <input type="hidden" name="action" id="hidden_action" value="">

                    <div style="margin-bottom: 15px;">
                        <button type="button" onclick="submitForm('print')" class="btn btn-warning">
                            <i class="fa fa-print"></i> PRINT
                        </button>

                        <button type="button" onclick="submitForm('proses')" class="btn btn-primary">
                            <i class="fa fa-cogs"></i> PROSES
                        </button>

                        <button type="button" onclick="submitForm('done')" class="btn btn-success">
                            <i class="fa fa-check"></i> DONE
                        </button>
                    </div>

                    <table class="table table-striped datatable">
                        <thead>
                            <tr class="bg-navy">
                                <th width="5%" class="text-center">
                                    <input type="checkbox" id="check-all">
                                </th>
                                <th width="5%">No.</th>
                                <th width="20%">Tanggal Unggah</th>
                                <th width="20%">Nama File</th>
                                <th class="text-center">Link Dokumen</th>
                                <th class="text-center">Jenis</th>
                                <th class="text-center">SKU</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($list_surat_jalan_tp as $surat_jalan_tp): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="ids[]" value="<?= $surat_jalan_tp['id'] ?>"
                                        class="check-item">
                                </td>
                                <td><?= $i++; ?></td>
                                <td><?= $surat_jalan_tp['created_at'] ?></td>
                                <td><?= $surat_jalan_tp['nama_file'] ?></td>
                                <td class="text-center">
                                    <?php if(!empty($surat_jalan_tp['link_dokumen'])): ?>
                                    <a href="<?= $surat_jalan_tp['link_dokumen'] ?>" target="_blank"
                                        class="btn btn-primary btn-xs">
                                        <i class="fa fa-external-link"></i> Buka
                                    </a>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $surat_jalan_tp['jenis'] ?? '-' ?></td>
                                <td class="text-center"><?= $surat_jalan_tp['sku'] ?></td>
                                <td class="text-center">
                                    <?= !empty($surat_jalan_tp['status_inbound']) ? $surat_jalan_tp['status_inbound'] : '-' ?>
                                </td>
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
$(document).ready(function() {
    var table = $('#table-laporan-surat-jalan-tp').DataTable({
        "ordering": false,
        "language": {
            "emptyTable": "Tidak ada data"
        },
        "columnDefs": [{
            "orderable": false,
            "targets": 0
        }]
    });

    var existingVal = $('#reportrange').val();
    var start = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[0]) : moment()
        .startOf('day');
    var end = (existingVal && existingVal.indexOf(' - ') > 0) ? moment(existingVal.split(" - ")[1]) : moment()
        .endOf('day');

    $('#reportrange').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        startDate: start,
        endDate: end,
        opens: 'right',
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf(
                'day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss',
            cancelLabel: 'Clear'
        }
    });

    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format(
            'YYYY-MM-DD HH:mm:ss'));
        $('#form-laporan-surat-jalan-tp').submit();
    });

    // --- KLIK DETAIL NOTIFIKASI ---
    $('.notif-item').click(function(e) {
        e.preventDefault();
        var listItem = $(this);
        var notifId = listItem.data('id');

        // Cek apakah class list-group-item-info (belum dibaca) masih ada
        if (listItem.hasClass('list-group-item-info')) {
            $.ajax({
                url: '<?= base_url("inbound/mark_notif_read") ?>', // Sesuaikan URL controller Anda
                type: 'POST',
                data: {
                    id: notifId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Hapus highlight (warna biru) karena sudah dibaca
                        listItem.removeClass('list-group-item-info');
                        // Ubah font pesan menjadi normal
                        listItem.find('.list-group-item-text').css('font-weight', 'normal');

                        // Update counter badge
                        var currentCount = parseInt($('#notif-badge').text());
                        if (!isNaN(currentCount) && currentCount > 0) {
                            var newCount = currentCount - 1;
                            $('#notif-text-count').text(newCount);
                            if (newCount > 0) {
                                $('#notif-badge').text(newCount);
                            } else {
                                $('#notif-badge').remove();
                                $('#btn-mark-all-read')
                            .fadeOut(); // Hilangkan tombol tandai semua jika 0
                            }
                        }
                    }
                }
            });
        }
    });
});

// --- KLIK TANDAI SEMUA DIBACA ---
$('#btn-mark-all-read').click(function(e) {
    e.preventDefault();
    e.stopPropagation(); // Mencegah dropdown tertutup otomatis

    var btnMarkAll = $(this);
    var category = btnMarkAll.data('category');

    if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
        $.ajax({
            url: '<?= base_url("inbound/mark_all_notif_read") ?>', // Sesuaikan dengan controller Anda
            type: 'POST',
            data: {
                category: category
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // 1. Hilangkan class 'list-group-item-info' dari semua list (mengembalikan warna jadi putih)
                    $('.notif-item').removeClass('list-group-item-info');

                    // 2. Ubah semua font menjadi normal
                    $('.notif-item').find('.list-group-item-text').css('font-weight', 'normal');

                    // 3. Reset angka hitungan menjadi 0
                    $('#notif-text-count').text('0');
                    $('#notif-badge').remove();

                    // 4. Sembunyikan tombol "Tandai Semua"
                    btnMarkAll.fadeOut();
                } else {
                    alert('Gagal menandai notifikasi.');
                }
            },
            error: function() {
                alert('Terjadi kesalahan pada server.');
            }
        });
    }
});

$("#check-all").click(function() {
    var isChecked = $(this).prop('checked');
    $(".check-item").prop('checked', isChecked);
});

$(".check-item").click(function() {
    if (!$(this).prop("checked")) {
        $("#check-all").prop("checked", false);
    }
});

function submitForm(actionType) {
    if ($('.check-item:checked').length == 0) {
        alert("Silahkan pilih data terlebih dahulu!");
        return false;
    }

    $('#hidden_action').val(actionType);

    $('#form-bulk').submit();
}
</script>
