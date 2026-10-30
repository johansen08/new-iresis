<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Laporan Pengembalian Barang QC</h3>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" id="reportrange" class="form-control" value="<?= date('Y-m-d') ?> - <?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_status" class="form-control select">
                            <option value="">- Semua Status -</option>
                            <option value="PENDING">PENDING</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="REJECTED">REJECTED</option>
                            <option value="GIVEAWAY">GIVEAWAY</option>
                            <option value="BARANG_TIDAK_ADA">BARANG TIDAK ADA</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filter_kondisi" class="form-control select">
                            <option value="">- Semua Kondisi -</option>
                            <option value="LEBIH AMBIL">LEBIH AMBIL</option>
                            <option value="SALAH AMBIL">SALAH AMBIL</option>
                            <option value="REJECT">REJECT</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="refreshTable()"><i class="fa fa-search"></i> Filter</button>
                        <button class="btn btn-info" onclick="exportToExcel()"><i class="fa fa-file-excel-o"></i> Export</button>
                        <?php if (!empty($bisa_restok)): ?>
                        <button class="btn btn-warning" onclick="serahTerima()"><i class="fa fa-print"></i> Serah Terima</button>
                        <?php endif; ?>
                        <button class="btn btn-default" onclick="riwayatCetak()"><i class="fa fa-history"></i> Riwayat Cetak</button>
                    </div>
                    <div class="col-md-2 text-right">
                        <a href="qc_return/add" class="btn btn-success link"><i class="fa fa-plus"></i> Submit Pengembalian</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="table_qc" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>No Rak</th>
                                <th>Kondisi</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Acc By</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Kesalahan Picker</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted" style="margin-bottom:10px">Masalah picker yang dilaporkan Packer saat scan, sesuai rentang tanggal di atas.</p>
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

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Pengerjaan Serah Terima Harian (30 hari terakhir)</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted" style="margin-bottom:10px">Satu tanggal = satu pengerjaan. Klik <b>Barang Diambil</b> saat keranjang tanggal tsb diambil tim Restok, lalu <b>Selesai</b> setelah Restok selesai mengerjakannya. Semua waktu tercatat untuk investigasi.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr><th>Tanggal Data</th><th>SKU</th><th>Qty</th><th>Dicetak</th><th>Barang Diambil</th><th>Selesai</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="tbody_pengerjaan"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal_riwayat_cetak" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Riwayat Cetak Serah Terima (100 terakhir)</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr><th>#</th><th>Waktu Cetak</th><th>Tanggal Data</th><th>SKU</th><th>Qty</th><th>Penyerah QC</th><th>Dicetak Oleh</th></tr>
                        </thead>
                        <tbody id="tbody_riwayat_cetak"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var table;
    var BISA_DIAMBIL = <?= !empty($bisa_diambil) ? 'true' : 'false' ?>;
    var BISA_RESTOK = <?= !empty($bisa_restok) ? 'true' : 'false' ?>;
    $(document).ready(function() {
        muatPengerjaan();
        muatKesalahanPicker();
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

        table = $('#table_qc').DataTable({
            "processing": true,
            "ajax": {
                "url": "qc_return/get_data",
                "type": "POST",
                "data": function(d) {
                    var range = $('#reportrange').val().split(' - ');
                    d.start_date = range[0];
                    d.end_date = range[1];
                    d.status = $('#filter_status').val();
                    d.kondisi = $('#filter_kondisi').val();
                }
            },
            "columnDefs": [{
                "targets": [7],
                "render": function(data, type, row) {
                    if (data == 'PENDING') return '<span class="label label-warning">PENDING</span>';
                    if (data == 'APPROVED') return '<span class="label label-success">APPROVED</span>';
                    if (data == 'REJECTED') return '<span class="label label-danger">REJECTED</span>';
                    if (data == 'GIVEAWAY') return '<span class="label label-info">GIVEAWAY</span>';
                    if (data == 'BARANG_TIDAK_ADA') return '<span class="label label-default">BARANG TIDAK ADA</span>';
                    return data;
                }
            }, {
                "targets": [9],
                "orderable": false,
                "searchable": false
            }]
        });
    });

    function refreshTable() {
        table.ajax.reload();
        muatKesalahanPicker();
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

    function exportToExcel() {
        var range = $('#reportrange').val().split(' - ');
        var start_date = range[0];
        var end_date = range[1];
        var status = $('#filter_status').val();
        var kondisi = $('#filter_kondisi').val();

        var url = 'qc_return/export_to_excel?start_date=' + start_date + '&end_date=' + end_date + '&status=' + status + '&kondisi=' + kondisi;
        window.location.href = url;
    }

    // Buka halaman cetak A4 serah terima (tanpa REJECT) untuk rentang tanggal yang difilter
    function serahTerima() {
        var range = $('#reportrange').val().split(' - ');
        window.open('qc_return/serah_terima?start_date=' + range[0] + '&end_date=' + range[1], '_blank');
    }

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function ringkasWaktu(waktu, nama) {
        return waktu ? esc(waktu) + '<br><small class="text-muted">' + esc(nama || '-') + '</small>' : '<span class="text-muted">-</span>';
    }

    function muatPengerjaan() {
        var $tb = $('#tbody_pengerjaan');
        $.ajax({
            url: 'qc_return/pengerjaan_harian',
            type: 'POST',
            dataType: 'JSON',
            success: function(res) {
                var list = (res && res.data) ? res.data : [];
                if (!list.length) {
                    $tb.html('<tr><td colspan="7" class="text-center">Belum ada data</td></tr>');
                    return;
                }
                var html = '';
                $.each(list, function(i, r) {
                    var cetak = r.jumlah_cetak > 0 ?
                        r.jumlah_cetak + 'x<br><small class="text-muted">pertama ' + esc(r.cetak_pertama) + '<br>terakhir ' + esc(r.cetak_terakhir) + '</small>' :
                        '<span class="text-muted">Belum dicetak</span>';
                    var aksi = BISA_RESTOK ? '<button class="btn btn-warning btn-xs" onclick="cetakTanggal(\'' + r.tanggal + '\')"><i class="fa fa-print"></i> Cetak</button> ' : '';
                    if (!r.diambil_at) {
                        if (BISA_DIAMBIL) aksi += '<button class="btn btn-primary btn-xs" onclick="aksiPengerjaan(\'barang_diambil\',\'' + r.tanggal + '\')"><i class="fa fa-shopping-basket"></i> Barang Diambil</button>';
                    } else if (!r.selesai_at) {
                        if (BISA_RESTOK) aksi += '<button class="btn btn-success btn-xs" onclick="aksiPengerjaan(\'pengerjaan_selesai\',\'' + r.tanggal + '\')"><i class="fa fa-check"></i> Selesai</button>';
                    } else {
                        aksi += '<span class="label label-success">SELESAI</span>';
                    }
                    html += '<tr><td><b>' + esc(r.tanggal) + '</b></td><td>' + r.jumlah_sku + '</td><td>' + r.total_qty + '</td><td>' + cetak +
                        '</td><td>' + ringkasWaktu(r.diambil_at, r.diambil_nama) + '</td><td>' + ringkasWaktu(r.selesai_at, r.selesai_nama) + '</td><td>' + aksi + '</td></tr>';
                });
                $tb.html(html);
            },
            error: function() {
                $tb.html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat pengerjaan harian</td></tr>');
            }
        });
    }

    function cetakTanggal(tgl) {
        window.open('qc_return/serah_terima?start_date=' + tgl + '&end_date=' + tgl, '_blank');
        setTimeout(muatPengerjaan, 1500);
    }

    function aksiPengerjaan(aksi, tgl) {
        var pesan = aksi == 'barang_diambil' ?
            'Tandai barang tanggal ' + tgl + ' SUDAH DIAMBIL tim Restok sekarang? Waktu akan tercatat dan tidak bisa diubah.' :
            'Tandai pengerjaan tanggal ' + tgl + ' SELESAI sekarang? Waktu akan tercatat dan tidak bisa diubah.';
        if (!confirm(pesan)) return;
        $.ajax({
            url: 'qc_return/' + aksi,
            type: 'POST',
            data: {
                tanggal: tgl
            },
            dataType: 'JSON',
            success: function(res) {
                noty({
                    text: res.message,
                    layout: 'topRight',
                    type: res.code == 200 ? 'success' : 'error',
                    timeout: 3000
                });
                muatPengerjaan();
            },
            error: function() {
                noty({
                    text: 'Gagal menyimpan',
                    layout: 'topRight',
                    type: 'error',
                    timeout: 3000
                });
            }
        });
    }

    // Riwayat setiap kali halaman cetak serah terima dibuka
    function riwayatCetak() {
        $('#modal_riwayat_cetak').modal('show');
        var $tb = $('#tbody_riwayat_cetak').html('<tr><td colspan="7" class="text-center">Memuat...</td></tr>');
        $.ajax({
            url: 'qc_return/riwayat_cetak',
            type: 'POST',
            dataType: 'JSON',
            success: function(res) {
                var list = (res && res.data) ? res.data : [];
                if (!list.length) {
                    $tb.html('<tr><td colspan="7" class="text-center">Belum ada riwayat cetak</td></tr>');
                    return;
                }
                var html = '';
                $.each(list, function(i, r) {
                    var tgl = r.tanggal_awal == r.tanggal_akhir ? r.tanggal_awal : r.tanggal_awal + ' s/d ' + r.tanggal_akhir;
                    html += '<tr><td>' + (i + 1) + '</td><td>' + $('<div>').text(r.dicetak_at).html() + '</td><td>' + tgl +
                        '</td><td>' + r.jumlah_sku + '</td><td>' + r.total_qty + '</td><td>' +
                        $('<div>').text(r.penyerah || '-').html() + '</td><td>' + $('<div>').text(r.pencetak || '-').html() + '</td></tr>';
                });
                $tb.html(html);
            },
            error: function() {
                $tb.html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat riwayat</td></tr>');
            }
        });
    }

    function deleteData(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: 'qc_return/delete',
                type: 'POST',
                data: {
                    id: id
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.success) {
                        refreshTable();
                        noty({
                            text: 'Data berhasil dihapus',
                            layout: 'topRight',
                            type: 'success',
                            timeout: 3000
                        });
                    } else {
                        noty({
                            text: 'Gagal menghapus data',
                            layout: 'topRight',
                            type: 'error',
                            timeout: 3000
                        });
                    }
                }
            });
        }
    }
</script>

<style>
    #reportrange {
        background-color: #fff !important;
        cursor: pointer !important;
        color: #555 !important;
        border: 1px solid #ccc !important;
    }

    #reportrange:hover {
        border-color: #66afe9 !important;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6) !important;
    }
</style>
