<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Laporan Retur Shipped</strong></h3>
      </div>
      <div class="panel-body">
        
        <!-- Filter Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-horizontal">
              
              <div class="form-group">
                <label class="col-md-2 control-label">Rentang Waktu Buka Retur</label>
                <div class="col-md-4">
                  <input type="text" id="rwaktu_laporan" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                </div>
                
                <div class="col-md-6 text-right">
                  <button type="button" class="btn btn-primary" id="btn_tampilkan">
                    <i class="fa fa-search"></i> Tampilkan
                  </button>
                  <button type="button" class="btn btn-success" id="btn_export">
                    <i class="fa fa-file-excel-o"></i> Export ke Excel
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <hr style="margin-top: 10px; margin-bottom: 20px;"/>

        <!-- Table Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover" id="dt_laporan_shipped" style="width: 100%;">
                <thead>
                  <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>No. Resi</th>
                    <th>No. Pesanan</th>
                    <th>Nama Toko</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Status Paket</th>
                    <th>Status Jubelio</th>
                    <th>Status Berubah</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';

    // ==================== DATERANGEPICKER HELPER ====================
    var drpLocale = {
        format: 'YYYY-MM-DD HH:mm',
        separator: ' s/d ',
        applyLabel: 'Terapkan', cancelLabel: 'Batal',
        fromLabel: 'Dari', toLabel: 'Sampai',
        customRangeLabel: 'Custom', weekLabel: 'W',
        daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
        monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
        firstDay: 1
    };
    var drpRanges = {
        'Hari Ini'        : [moment().startOf('day'), moment().endOf('day')],
        'Kemarin'         : [moment().subtract(1,'days').startOf('day'), moment().subtract(1,'days').endOf('day')],
        '7 Hari Terakhir' : [moment().subtract(6,'days').startOf('day'), moment().endOf('day')],
        '30 Hari Terakhir': [moment().subtract(29,'days').startOf('day'), moment().endOf('day')],
        'Bulan Ini'       : [moment().startOf('month'), moment().endOf('month')]
    };

    $('#rwaktu_laporan').daterangepicker({
        timePicker: true, timePicker24Hour: true, timePickerIncrement: 1,
        startDate: moment().startOf('month'), endDate: moment().endOf('day'),
        ranges: drpRanges, locale: drpLocale
    });
    $('#rwaktu_laporan').val(moment().startOf('month').format('YYYY-MM-DD HH:mm') + ' s/d ' + moment().endOf('day').format('YYYY-MM-DD HH:mm'));

    // ==================== DATATABLE INITIALIZATION ====================
    var cols = [
        {data: 0, orderable: false}, // No
        {data: 1}, // Tanggal
        {data: 2}, // No. Resi
        {data: 3}, // No. Pesanan
        {data: 4}, // Nama Toko
        {data: 5}, // SKU
        {data: 6}, // Qty
        {data: 7}, // Status Paket
        {data: 8}, // Status Jubelio
        {data: 9}  // Status Berubah
    ];

    var dtLaporan = $('#dt_laporan_shipped').DataTable({
        scrollX: true, 
        pageLength: 25, 
        processing: true, 
        serverSide: true,
        order: [[1, 'desc']], // Default sort by Tanggal Buka (index 1)
        deferLoading: 0,
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        ajax: { 
            url: BASE + 'retur/get-data-laporan-retur-shipped', 
            type: 'POST', 
            data: function(d) {
                var v = $('#rwaktu_laporan').val().split(' s/d ');
                d.start_date = v[0] || '';
                d.end_date = v[1] || '';
            } 
        },
        columns: cols,
        language: { 
            emptyTable: 'Klik <b>Tampilkan</b> untuk memuat data', 
            zeroRecords: 'Tidak ada data ditemukan' 
        }
    });

    // Auto-trigger loading on startup
    dtLaporan.ajax.reload();

    // ==================== ACTIONS ====================
    $('#btn_tampilkan').on('click', function () { 
        dtLaporan.ajax.reload(); 
    });

    $('#btn_export').on('click', function () {
        var v = $('#rwaktu_laporan').val().split(' s/d ');
        if (!v[0] || !v[1]) { 
            alert('Pilih rentang waktu terlebih dahulu!'); 
            return; 
        }
        var url = BASE + 'retur/export-excel-laporan-retur-shipped?start_date=' + encodeURIComponent(v[0]) 
            + '&end_date=' + encodeURIComponent(v[1]);
        window.open(url, '_blank');
    });
});
</script>
