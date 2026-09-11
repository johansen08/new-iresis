<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?= base_url('purchasing/laporan_penolakan') ?>" class="form-horizontal" method="post" id="form-filter">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Rentang waktu Penolakan</label>
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
            <div class="panel-heading">
                <h3 class="panel-title">Laporan Penolakan Pengembalian QC</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover datatable" id="table-penolakan">
                        <thead>
                            <tr class="bg-navy">
                                <th>No.</th>
                                <th>Tanggal Masuk</th>
                                <th>SKU</th>
                                <th>No. Rak</th>
                                <th>Qty</th>
                                <th>Alasan Penolakan</th>
                                <th>Ditolak Oleh</th>
                                <th>Waktu Penolakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($list_penolakan)): ?>
                            <?php $i = 1; foreach ($list_penolakan as $item): ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= $item['tanggal'] ?></td>
                                <td><?= $item['sku'] ?></td>
                                <td><?= $item['no_rak'] ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td><?= $item['keterangan_reject'] ?></td>
                                <td><?= $item['nama_acc'] ?></td>
                                <td><?= $item['acc_at'] ?></td>
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

<script>
$(document).ready(function() {
    $('#table-penolakan').DataTable({
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
});
</script>
