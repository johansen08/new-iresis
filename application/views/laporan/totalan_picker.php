<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">
          Totalan Picker (Realtime)
          <span class="pull-right text-muted" id="last-refresh"></span>
        </h3>
      </div>
      <div class="panel-body">
        <div class="row margin-bottom-15">
          <div class="col-md-4">
            <div class="alert alert-info text-center" style="margin-bottom:0">
              <h2 id="grand-total" style="margin:0">0</h2>
              <small>Total Resi Hari Ini</small>
            </div>
          </div>
          <div class="col-md-8 text-right" style="padding-top:15px">
            <span class="text-muted"><i class="fa fa-refresh fa-spin"></i> Auto-refresh setiap 30 detik</span>
          </div>
        </div>

        <table class="table table-striped table-bordered" id="tbl-picker">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Nama Picker</th>
              <th width="100" class="text-center">Total Resi</th>
              <th width="100" class="text-center">Satuan</th>
              <th width="100" class="text-center">Campuran</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= $row->nama_pegawai ?></td>
              <td class="text-center"><strong><?= $row->total_resi ?></strong></td>
              <td class="text-center"><?= $row->satuan ?></td>
              <td class="text-center"><?= $row->campuran ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  function refreshData() {
    $.post('laporan/get-data-totalan-picker', {}, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      var tbody = $('#tbl-picker tbody');
      tbody.empty();

      if (data.data && data.data.length) {
        data.data.forEach(function(row) {
          tbody.append(
            '<tr>' +
              '<td>' + row[0] + '</td>' +
              '<td>' + row[1] + '</td>' +
              '<td class="text-center"><strong>' + row[2] + '</strong></td>' +
              '<td class="text-center">' + row[3] + '</td>' +
              '<td class="text-center">' + row[4] + '</td>' +
            '</tr>'
          );
        });
      } else {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">Belum ada data hari ini</td></tr>');
      }

      $('#grand-total').text(data.grandTotal || 0);
      $('#last-refresh').text('Update: ' + moment().format('HH:mm:ss'));
    });
  }

  var _pickerInterval = setInterval(refreshData, 30000);
  refreshData();

  $(document).on('pjax:end', function() { clearInterval(_pickerInterval); });
})();
</script>
