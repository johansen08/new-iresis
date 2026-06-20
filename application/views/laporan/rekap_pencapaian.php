<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs nav-justified">
        <li class="active"><a href="#tab-picker" data-toggle="tab">Rekap Picker</a></li>
        <li><a href="#tab-packer" data-toggle="tab">Rekap Packer</a></li>
        <li><a href="#tab-paket" data-toggle="tab">Rekap Paket Keluar</a></li>
      </ul>
      <div class="panel-body tab-content">

        <!-- TAB PICKER -->
        <div class="tab-pane active" id="tab-picker">
          <div class="row margin-bottom-15">
            <div class="col-md-4">
              <div class="input-group">
                <input type="text" class="form-control daterange-picker" id="range-picker" value="">
                <span class="input-group-btn">
                  <button class="btn btn-primary" id="btn-cari-picker"><i class="fa fa-search"></i> Cari</button>
                </span>
              </div>
            </div>
            <div class="col-md-8 text-right">
              <a href="#" class="btn btn-success btn-sm" id="btn-export-picker">
                <i class="fa fa-file-excel-o"></i> Export Excel
              </a>
            </div>
          </div>
          <table class="table table-striped table-bordered" id="tbl-rekap-picker">
            <thead>
              <tr>
                <th width="40">No</th>
                <th>Nama Picker</th>
                <th>Tanggal</th>
                <th class="text-center">Total Resi</th>
                <th class="text-center">Satuan</th>
                <th class="text-center">Campuran</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- TAB PACKER -->
        <div class="tab-pane" id="tab-packer">
          <div class="row margin-bottom-15">
            <div class="col-md-4">
              <div class="input-group">
                <input type="text" class="form-control daterange-picker" id="range-packer" value="">
                <span class="input-group-btn">
                  <button class="btn btn-primary" id="btn-cari-packer"><i class="fa fa-search"></i> Cari</button>
                </span>
              </div>
            </div>
            <div class="col-md-8 text-right">
              <a href="#" class="btn btn-success btn-sm" id="btn-export-packer">
                <i class="fa fa-file-excel-o"></i> Export Excel
              </a>
            </div>
          </div>
          <table class="table table-striped table-bordered" id="tbl-rekap-packer">
            <thead>
              <tr>
                <th width="40">No</th>
                <th>Nama Packer</th>
                <th>Tanggal</th>
                <th class="text-center">Total Resi</th>
                <th class="text-center">Satuan</th>
                <th class="text-center">Campuran</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- TAB PAKET KELUAR -->
        <div class="tab-pane" id="tab-paket">
          <div class="row margin-bottom-15">
            <div class="col-md-4">
              <div class="input-group">
                <input type="text" class="form-control daterange-picker" id="range-paket" value="">
                <span class="input-group-btn">
                  <button class="btn btn-primary" id="btn-cari-paket"><i class="fa fa-search"></i> Cari</button>
                </span>
              </div>
            </div>
            <div class="col-md-8 text-right">
              <a href="#" class="btn btn-success btn-sm" id="btn-export-paket">
                <i class="fa fa-file-excel-o"></i> Export Excel
              </a>
            </div>
          </div>
          <table class="table table-striped table-bordered" id="tbl-rekap-paket">
            <thead>
              <tr>
                <th width="40">No</th>
                <th>Ekspedisi</th>
                <th>Tanggal</th>
                <th class="text-center">Jumlah Paket</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var defaultStart = moment().subtract(7, 'days').format('YYYY-MM-DD');
  var defaultEnd = moment().format('YYYY-MM-DD');

  // Init daterangepickers
  $('.daterange-picker').daterangepicker({
    locale: { format: 'YYYY-MM-DD' },
    startDate: defaultStart,
    endDate: defaultEnd
  });

  function getDates(inputId) {
    var picker = $('#' + inputId).data('daterangepicker');
    return {
      start_date: picker.startDate.format('YYYY-MM-DD'),
      end_date: picker.endDate.format('YYYY-MM-DD')
    };
  }

  function loadPicker() {
    var d = getDates('range-picker');
    $.post('laporan/get-data-rekap-picker', d, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      var tbody = $('#tbl-rekap-picker tbody').empty();
      (data.data || []).forEach(function(r) {
        tbody.append('<tr><td>' + r[0] + '</td><td>' + r[1] + '</td><td>' + r[2] +
          '</td><td class="text-center">' + r[3] + '</td><td class="text-center">' + r[4] +
          '</td><td class="text-center">' + r[5] + '</td></tr>');
      });
      if (!data.data || !data.data.length)
        tbody.append('<tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>');
    });
    var d2 = getDates('range-picker');
    $('#btn-export-picker').attr('href', 'laporan/export-rekap-picker?start_date=' + d2.start_date + '&end_date=' + d2.end_date);
  }

  function loadPacker() {
    var d = getDates('range-packer');
    $.post('laporan/get-data-rekap-packer', d, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      var tbody = $('#tbl-rekap-packer tbody').empty();
      (data.data || []).forEach(function(r) {
        tbody.append('<tr><td>' + r[0] + '</td><td>' + r[1] + '</td><td>' + r[2] +
          '</td><td class="text-center">' + r[3] + '</td><td class="text-center">' + r[4] +
          '</td><td class="text-center">' + r[5] + '</td></tr>');
      });
      if (!data.data || !data.data.length)
        tbody.append('<tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>');
    });
    var d2 = getDates('range-packer');
    $('#btn-export-packer').attr('href', 'laporan/export-rekap-packer?start_date=' + d2.start_date + '&end_date=' + d2.end_date);
  }

  function loadPaket() {
    var d = getDates('range-paket');
    $.post('laporan/get-data-rekap-paket-keluar', d, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      var tbody = $('#tbl-rekap-paket tbody').empty();
      (data.data || []).forEach(function(r) {
        tbody.append('<tr><td>' + r[0] + '</td><td>' + r[1] + '</td><td>' + r[2] +
          '</td><td class="text-center">' + r[3] + '</td></tr>');
      });
      if (!data.data || !data.data.length)
        tbody.append('<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>');
    });
    var d2 = getDates('range-paket');
    $('#btn-export-paket').attr('href', 'laporan/export-rekap-paket-keluar?start_date=' + d2.start_date + '&end_date=' + d2.end_date);
  }

  $(document).off('click', '#btn-cari-picker').on('click', '#btn-cari-picker', loadPicker);
  $(document).off('click', '#btn-cari-packer').on('click', '#btn-cari-packer', loadPacker);
  $(document).off('click', '#btn-cari-paket').on('click', '#btn-cari-paket', loadPaket);

  // Load data on tab switch
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    var target = $(e.target).attr('href');
    if (target === '#tab-picker') loadPicker();
    else if (target === '#tab-packer') loadPacker();
    else if (target === '#tab-paket') loadPaket();
  });

  loadPicker();
})();
</script>
