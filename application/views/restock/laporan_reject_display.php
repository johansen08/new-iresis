<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Laporan Reject Display</strong></h3>
      </div>

      <div class="panel-body">
        <form action="restock/laporan-reject-display" class="form-horizontal" method="post" id="form-laporan-reject-display">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Rentang Waktu</label>
            <div class="col-md-6 col-xs-12">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                <input type="text" name="reportrange" id="reportrange" class="form-control"
                  placeholder="*Filter Range tanggal"
                  value="<?= !empty($reportrange) ? $reportrange : null ?>" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label"></label>
            <div class="col-md-6 col-xs-12">
              <button type="button" class="btn btn-primary" id="btn-cari"><i class="fa fa-search"></i> Cari</button>
              <button type="button" class="btn btn-success" id="btn-export-excel"><i class="fa fa-file-excel-o"></i> Ekspor ke Excel</button>
            </div>
          </div>
        </form>

        <hr>

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="datatable-laporan-reject-display" style="width:100%">
            <thead>
              <tr>
                <th>Picker</th>
                <th>Packer / Pelapor</th>
                <th>No. Resi</th>
                <th>SKU</th>
                <th>SKU Salah</th>
                <th>QTY</th>
                <th>QTY Bermasalah</th>
                <th>Tipe Masalah</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  #datatable-laporan-reject-display {
    font-size: 13px;
  }

  #datatable-laporan-reject-display thead th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
  }

  #datatable-laporan-reject-display tbody td {
    vertical-align: middle;
  }

  #datatable-laporan-reject-display tr:hover {
    background-color: #f9f9f9;
  }
</style>

<script>
  var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;

  var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
  var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment();

  // Initialize daterangepicker
  $('#reportrange').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: start,
    endDate: end,
    ranges: {
      'Hari Ini': [moment().startOf('day'), moment()],
      'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
      '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment()],
      '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment()],
      'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
      'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    },
    locale: {
      format: 'YYYY-MM-DD HH:mm:ss',
      separator: ' - ',
      applyLabel: 'Terapkan',
      cancelLabel: 'Batal',
      fromLabel: 'Dari',
      toLabel: 'Sampai',
      customRangeLabel: 'Custom',
      weekLabel: 'W',
      daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
      monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      firstDay: 1
    },
  });

  // Initialize DataTable
  var table = $('#datatable-laporan-reject-display').DataTable({
    'scrollX': true,
    'pageLength': 25,
    'processing': true,
    'language': {
      'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Memproses data...</span>',
      'lengthMenu': 'Tampilkan _MENU_ data per halaman',
      'zeroRecords': 'Data tidak ditemukan',
      'info': 'Menampilkan halaman _PAGE_ dari _PAGES_',
      'infoEmpty': 'Tidak ada data yang tersedia',
      'infoFiltered': '(difilter dari _MAX_ total data)',
      'search': 'Cari:',
      'paginate': {
        'first': 'Pertama',
        'last': 'Terakhir',
        'next': 'Selanjutnya',
        'previous': 'Sebelumnya'
      }
    },
    'serverSide': true,
    'order': [[8, 'desc']], // Order by date desc
    'lengthMenu': [
      [10, 25, 50, 100, 200],
      [10, 25, 50, 100, 200]
    ],
    'ajax': {
      url: 'restock/get-laporan-reject-display-data',
      type: 'POST',
      data: function (d) {
        d.reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s') ?>';
      },
      error: function (xhr, error, code) {
        console.error('DataTable AJAX error:', error, code);
        showNotification('error', 'Gagal memuat data. Silakan coba lagi.');
      }
    },
    'columnDefs': [
      { className: 'text-center', targets: [5, 6, 8, 9] },
      { orderable: false, targets: [9] }
    ]
  });

  // Reload table when search button clicked
  $('#btn-cari').on('click', function () {
    table.ajax.reload();
  });

  // Auto reload when date range changes
  $('#reportrange').on('apply.daterangepicker', function (ev, picker) {
    table.ajax.reload();
  });


  // Export to Excel
  $('#btn-export-excel').on('click', function () {
    var reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s') ?>';
    var url = 'restock/export-laporan-reject-display?reportrange=' + encodeURIComponent(reportrange);
    window.open(url, '_blank');
  });

  // Helper function to show notifications
  function showNotification(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var notification = $('<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
      '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
      '<span aria-hidden="true">&times;</span></button>' + message + '</div>');

    $('.panel-body').prepend(notification);

    setTimeout(function () {
      notification.fadeOut('slow', function () {
        $(this).remove();
      });
    }, 5000);
  }

  // Handle Action Buttons
  $('#datatable-laporan-reject-display').on('click', '.btn-hapus', function () {
    var id = $(this).data('id');
    if (confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
      $.ajax({
        url: 'restock/hapus-masalah-picker',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
          if (res.status === 'success') {
            showNotification('success', res.message);
            table.ajax.reload(null, false);
          } else {
            showNotification('error', res.message);
          }
        },
        error: function () {
          showNotification('error', 'Terjadi kesalahan sistem.');
        }
      });
    }
  });

  $('#datatable-laporan-reject-display').on('click', '.btn-kembalikan', function () {
    var id = $(this).data('id');
    if (confirm('Apakah Anda yakin ingin mengembalikan laporan ini ke daftar masalah picker?')) {
      $.ajax({
        url: 'restock/kembalikan-masalah-picker',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
          if (res.status === 'success') {
            showNotification('success', res.message);
            table.ajax.reload(null, false);
          } else {
            showNotification('error', res.message);
          }
        },
        error: function () {
          showNotification('error', 'Terjadi kesalahan sistem.');
        }
      });
    }
  });
</script>
