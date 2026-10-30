<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Kesalahan Picker</h3>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" id="reportrange" class="form-control" value="<?= date('Y-m-d') ?> - <?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="muatKesalahanPicker()"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </div>
                <p class="text-muted" style="margin-bottom:10px">Masalah picker jenis LEBIH AMBIL dan SALAH AMBIL yang dilaporkan Packer saat scan, sesuai rentang tanggal di atas.</p>
                <div class="table-responsive">
                    <table id="table_kesalahan_picker" class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr><th>#</th><th>Tanggal</th><th>No Resi</th><th>SKU</th><th>Nama Barang</th><th>Jenis Masalah</th><th>Qty</th><th>Picker</th><th>Packer</th><th>Status</th></tr>
                        </thead>
                        <tbody id="tbody_kesalahan_picker"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#reportrange').daterangepicker({
            timePicker: false,
            autoUpdateInput: true,
            ranges: {
                'Hari Ini': [moment().startOf('day'), moment()],
                'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment()],
                '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment()],
                'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                customRangeLabel: 'Custom',
                weekLabel: 'M',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            startDate: moment(),
            endDate: moment()
        });
        muatKesalahanPicker();
    });

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function muatKesalahanPicker() {
        var $tb = $('#tbody_kesalahan_picker');
        var range = $('#reportrange').val().split(' - ');
        $tb.html('<tr><td colspan="10" class="text-center">Memuat...</td></tr>');
        $.ajax({
            url: 'qc_return/kesalahan_picker',
            type: 'POST',
            data: {
                start_date: range[0],
                end_date: range[1]
            },
            dataType: 'JSON',
            success: function(res) {
                var list = (res && res.data) ? res.data : [];
                if (!list.length) {
                    $tb.html('<tr><td colspan="10" class="text-center">Tidak ada kesalahan picker pada rentang ini</td></tr>');
                    return;
                }
                var html = '';
                $.each(list, function(i, r) {
                    var jenis = esc(r.type_masalah || '-') + (r.sku_salah ? '<br><small class="text-muted">Terambil: ' + esc(r.sku_salah) + '</small>' : '');
                    var qty = r.qty_bermasalah > 0 ? r.qty_bermasalah + ' / ' + r.qty : r.qty;
                    var status = r.status == 0 ? '<span class="label label-warning">BELUM DIPROSES</span>' : '<span class="label label-success">SUDAH DIPROSES</span>';
                    html += '<tr><td>' + (i + 1) + '</td><td>' + esc(r.created) + '</td><td>' + esc(r.noresi) + '</td><td>' + esc(r.sku) +
                        '</td><td>' + esc(r.nama_barang) + '</td><td>' + jenis + '</td><td>' + esc(qty) + '</td><td>' + esc(r.nama_picker || '-') +
                        '</td><td>' + esc(r.nama_packer || '-') + '</td><td>' + status + '</td></tr>';
                });
                $tb.html(html);
            },
            error: function() {
                $tb.html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat kesalahan picker</td></tr>');
            }
        });
    }
</script>

<style>
    #reportrange {
        background-color: #fff !important;
        cursor: pointer !important;
        color: #555 !important;
        border: 1px solid #ccc !important;
    }
</style>
