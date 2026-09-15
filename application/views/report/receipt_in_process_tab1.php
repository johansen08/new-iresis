<form action="report/export-to-excel-receipt-in-process-tab1" class="form-horizontal nojs" method="post" target="_blank">
  <div class="form-group">
    <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
    <div class="col-md-3 col-xs-12">
      <input type="text" name="reportrange" id="reportrange-receipt-process-tab1" class="form-control" />
    </div>
  </div>

  <div class="form-group">
    <label class="col-md-3 col-xs-12 control-label"></label>
    <div class="col-md-2 col-xs-12">
      <button type="button" class="btn btn-info" id="btn-search-receipt-process-tab1">Tampilkan</button>
      <button type="submit" class="btn btn-primary" id="btn-export-excel-receipt-process-tab1"><i class="fa fa-download"></i> Ekspor ke Excel</button>
    </div>
  </div>
</form>

<hr>

<table class="table table-striped" id="datatable-receipt-process-tab1">
  <thead>
    <tr>
      <th colspan="11" style="text-align:right">Grand Total</th>
      <th id="grand-total-tab1">-</th>
    </tr>
    <tr>
      <th>#</th>
      <th>Market Place</th>
      <th>Tanggal Scan Resi</th>
      <th>Jam Scan Resi</th>
      <th>Nomor Resi</th>
      <th>Status Pesanan</th>
      <th>Kurir</th>
      <th>Nomor Pick List</th>
      <th>Tanggal Pick</th>
      <th>Jam Pick</th>
      <th>Picker</th>
      <th>Batas Kirim</th>
    </tr>
  </thead>
</table>

<script type="text/javascript">
  $('#reportrange-receipt-process-tab1').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: moment().startOf('day'),
    endDate: moment(),
    ranges: {
      'Today': [moment().startOf('day'), moment()],
      'Last 1 Hours': [moment().subtract(1, 'hours'), moment()],
      'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().startOf('day')],
      'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
    },
    locale: {
      format: 'YYYY-MM-DD HH:mm:ss'
    },
  });

  // init for the first time
  var table_receipt_process_tab1 = $('#datatable-receipt-process-tab1').DataTable({
    'dom': '<if<t>lp>',
    'destroy': true,
  });
  $('#btn-search-receipt-process-tab1').on('click', function() {
    table_receipt_process_tab1 = $('#datatable-receipt-process-tab1').DataTable({
      'dom': '<if<t>lp>',
      'destroy': true,
      'pageLength': 10,
      'processing': true,
      'serverSide': true,
      'order': [
        [11, 'asc']
      ],
      'lengthMenu': [
        [10, 50, 100, 150, 200],
        [10, 50, 100, 150, 200]
      ],
      'ajax': {
        url: 'report/get-receipt-in-process-data-tab1',
        type: 'POST',
        data: function(d) {
          d.start_date = $('#reportrange-receipt-process-tab1').val().split(" - ")[0];
          d.end_date = $('#reportrange-receipt-process-tab1').val().split(" - ")[1];
        }
      },
      'initComplete': function() {
        pasangPencarianTertunda(this.api(), '#datatable-receipt-process-tab1');

        // Update grand total
        var json = this.api().ajax.json();
        if (json && json.grandTotal !== undefined) {
          $('#grand-total-tab1').text(json.grandTotal);
        }
      },
      'createdRow': function(row, data, dataIndex) {
        if (isWajibKirimHariIni(data[1], data[2], data[3], data[11])) {
          $(row).css('background-color', '#ffebee').css('color', '#c62828');
          $(row).find('td').css('font-weight', 'bold');
        }
      },
      'drawCallback': function() {
        // Update grand total setiap kali draw
        var json = this.api().ajax.json();
        if (json && json.grandTotal !== undefined) {
          $('#grand-total-tab1').text(json.grandTotal);
        }
      },
    });
  });
</script>