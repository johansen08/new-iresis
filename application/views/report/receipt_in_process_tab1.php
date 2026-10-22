<style>
    .ptr-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 6px; }
    .ptr-toolbar label { margin: 0; font-weight: 600; }
    .ptr-toolbar .ptr-range { width: 340px; max-width: 100%; height: 30px; }
    .ptr-toolbar .btn { padding: 4px 12px; }
    .ptr-total { margin-left: auto; display: flex; align-items: baseline; gap: 8px; padding: 2px 14px; background: #f4f8fc; border-left: 4px solid #337ab7; border-radius: 3px; }
    .ptr-total-label { color: #666; text-transform: uppercase; font-size: 11px; letter-spacing: .5px; }
    .ptr-total-angka { font-size: 22px; font-weight: 700; color: #1f5f99; line-height: 1.3; }
    .ptr-tabel table.dataTable tbody td, .ptr-tabel .dataTables_scrollHead th { padding: 3px 8px; vertical-align: middle; } 
    .ptr-tabel table.dataTable tbody td { white-space: nowrap; }
    .ptr-tabel .dataTables_scrollBody thead tr { height: 0; }
    .ptr-tabel .dataTables_scrollBody thead th { padding: 0 8px !important; border: 0; line-height: 0; font-size: 0; height: 0 !important; border-width: 0 !important; }
    .ptr-tabel .dataTables_scrollBody thead th::before, .ptr-tabel .dataTables_scrollBody thead th::after { display: none !important; }
    .ptr-tabel .dataTables_scrollBody thead th .dataTables_sizing { height: 0; overflow: hidden; }
    .ptr-tabel .dataTables_wrapper .dataTables_info { padding-top: 0; }
    .ptr-tabel .dataTables_scrollBody { border-bottom: 1px solid #ddd; overflow-x: hidden !important; }
    .ptr-tabel .dataTables_scrollBody table { margin-bottom: 0 !important; margin-top: -1px !important; }
    /* Perataan tepi: toolbar, kartu total, dan tabel berbagi tepi kiri/kanan yang sama */
    .ptr-toolbar { margin-bottom: 8px; }
    .ptr-total { height: 30px; padding: 0 12px; align-items: center; }
    .ptr-total-angka { font-size: 20px; line-height: 1; }
    .ptr-tabel .dataTables_info { float: left; padding: 0; line-height: 28px; }
    .ptr-tabel .dataTables_filter { float: right; margin: 0 0 6px; }
    .ptr-tabel .dataTables_filter label { margin: 0; display: flex; align-items: center; gap: 6px; }
    .ptr-tabel .dataTables_filter input { height: 28px; margin: 0; }
    .ptr-tabel .dataTables_scroll { clear: both; border: 1px solid #ddd; border-radius: 3px; overflow: hidden; }
    .ptr-tabel .dataTables_scrollBody { border-bottom: 0; }
    .ptr-tabel .dataTables_scrollHead th { white-space: nowrap; }
    .ptr-tabel .dataTables_scrollHead { border-bottom: 1px solid #ddd; }
    .ptr-tabel .dataTables_length { float: left; margin-top: 6px; }
    .ptr-tabel .dataTables_length label { margin: 0; display: flex; align-items: center; gap: 6px; }
    .ptr-tabel .dataTables_length select { height: 28px; margin: 0; width: 64px; }
    .ptr-tabel .dataTables_paginate { float: right; margin-top: 6px; }
    .ptr-tabel .dataTables_paginate .pagination { margin: 0; }
</style>
<form action="report/export-to-excel-receipt-in-process-tab1" class="form-horizontal nojs" method="post" target="_blank">
  <div class="ptr-toolbar">
    <label for="reportrange-receipt-process-tab1">Rentang waktu</label>
    <input type="text" name="reportrange" id="reportrange-receipt-process-tab1" class="form-control ptr-range" />
    <button type="button" class="btn btn-info" id="btn-search-receipt-process-tab1"><i class="fa fa-refresh"></i> Tampilkan</button>
    <button type="submit" class="btn btn-primary" id="btn-export-excel-receipt-process-tab1"><i class="fa fa-download"></i> Ekspor ke Excel</button>
    <div class="ptr-total"><span class="ptr-total-label">Grand Total</span><span class="ptr-total-angka" id="grand-total-tab1">-</span></div>
  </div>
</form>

<div class="ptr-tabel">
<table class="table table-striped nowrap" id="datatable-receipt-process-tab1">
  <thead>
    <tr>
      <th>#</th>
      <th>No. Resi</th>
      <th>Marketplace</th>
      <th>Kurir</th>
      <th>Status</th>
      <th>Batas Kirim</th>
      <th>No. Picklist</th>
      <th>Tanggal Scan</th>
      <th>Jam Scan</th>
      <th>Tanggal Pick</th>
      <th>Jam Pick</th>
      <th>Picker</th>
    </tr>
  </thead>
</table>
</div>

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
      'pageLength': 50,
      'scrollY': 'calc(100vh - 270px)',
      'scrollCollapse': true,
      'scrollX': true,
      'processing': true,
      'serverSide': true,
      'order': [
        [5, 'asc']
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
      'columnDefs': [
        { targets: 1, render: renderNoResi }
      ],
      'initComplete': function() {
        pasangPencarianTertunda(this.api(), '#datatable-receipt-process-tab1');

        // Update grand total
        var json = this.api().ajax.json();
        if (json && json.grandTotal !== undefined) {
          $('#grand-total-tab1').text(json.grandTotal);
        }
      },
      'createdRow': function(row, data, dataIndex) {
        if (isWajibKirimHariIni(data[2], data[7], data[8], data[5])) {
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

  // Langsung tampilkan data hari ini tanpa perlu klik Tampilkan
  $('#btn-search-receipt-process-tab1').trigger('click');
</script>