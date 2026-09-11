<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Status Kurangan Picker</strong></h3>
      </div>

      <div class="panel-body">
        <form action="monitoring/kurangan-picker" class="form-horizontal" method="post" id="form-monitoring-kurangan-picker">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Rentang Waktu Penyelesaian</label>
            <div class="col-md-4 col-xs-12">
              <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : null ?>" />
            </div>
            <div class="col-md-2 col-xs-12">
              <button type="submit" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Filter</button>
            </div>
          </div>
        </form>

        <hr>

        <table class="table table-striped table-bordered" id="datatable-monitoring-kurangan">
          <thead>
            <tr>
              <th>#</th>
              <th>SKU Bermasalah</th>
              <th>No. Resi</th>
              <th>Marketplace</th>
              <th>QTY Kurang</th>
              <th>Waktu Diselesaikan</th>
              <th>Status</th>
              <th>Notes</th>
              <th>SKU Pengganti</th>
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

  $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
    table.ajax.reload(null, false);
  });

  var table = $('#datatable-monitoring-kurangan').DataTable({
    'scrollX': true,
    'pageLength': 10,
    'processing': true,
    'serverSide': true,
    'order': [[5, 'desc']],
    'lengthMenu': [
      [10, 50, 100, 150, 200],
      [10, 50, 100, 150, 200]
    ],
    'ajax': {
      url: 'monitoring/get-kurangan-picker-data',
      type: 'POST',
      data: function(d) {
        d.reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59') ?>';
      }
    },
    'columnDefs': [
      { className: 'text-center', targets: [0, 4, 6] }
    ]
  });

  $('#btn-search').on('click', function(e) {
      e.preventDefault();
      table.ajax.reload(null, false);
  });
</script>
