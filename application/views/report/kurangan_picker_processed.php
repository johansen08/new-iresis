<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Laporan Kurangan Picker (Processed)</strong></h3>
      </div>

      <div class="panel-body">
        <form action="report/kurangan-picker-processed" class="form-horizontal" method="post" id="form-laporan-kurangan-picker">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
            <div class="col-md-3 col-xs-12">
              <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : null ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label"></label>
            <div class="col-md-2 col-xs-12">
              <button type="submit" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Cari</button>
              <button type="submit" class="btn btn-primary" id="btn-export-excel"><i class="fa fa-download"></i> Ekspor ke Excel</button>
            </div>
          </div>
        </form>

        <hr>

        <table class="table table-striped table-bordered" id="datatable-laporan-kurangan-picker-processed">
          <thead>
            <tr>
              <th>#</th>
              <th>SKU</th>
              <th>No. Resi</th>
              <th>Marketplace</th>
              <th>Tgl Cetak</th>
              <th>B. Akhir Kirim</th>
              <th>QTY Kurang</th>
              <th>Status</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;

  var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
  var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment().endOf('day');

  $('#reportrange').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: start,
    endDate: end,
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

  // Auto refresh when date range changes
  $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
    table.ajax.reload(null, false);
  });

  var table = $('#datatable-laporan-kurangan-picker-processed').DataTable({
    'scrollX': true,
    'pageLength': 10,
    'processing': true,
    'language': {
      'processing': 'Memproses data...'
    },
    'serverSide': true,
    'order': [[4, 'desc']],
    'lengthMenu': [
      [10, 50, 100, 150, 200],
      [10, 50, 100, 150, 200]
    ],
    'ajax': {
      url: 'report/get-kurangan-picker-processed-data',
      type: 'POST',
      data: function(d) {
        d.reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59') ?>';
      }
    },
    'columnDefs': [
      { className: 'text-center', targets: [0, 6, 7] }
    ]
  });

  $('#btn-search').on('click', function() {
    $('#form-laporan-kurangan-picker').removeAttr("target");
    $('#form-laporan-kurangan-picker').removeClass('nojs');
    $('#form-laporan-kurangan-picker').attr('action', 'report/kurangan-picker-processed');
  });
  
  $('#btn-export-excel').on('click', function() {
    $('#form-laporan-kurangan-picker').attr("target", "_blank");
    $('#form-laporan-kurangan-picker').addClass('nojs');
    $('#form-laporan-kurangan-picker').attr('action', 'report/export-excel-kurangan-picker-processed');
  });
</script>
