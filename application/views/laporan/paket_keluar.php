<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Rekap Paket Keluar Hari Ini</h3>
      </div>
      <div class="panel-body">

        <div class="row margin-bottom-15">
          <div class="col-md-3">
            <div class="alert alert-success text-center" style="margin-bottom:0">
              <h2 style="margin:0"><?= $total ?></h2>
              <small>Total Paket Keluar</small>
            </div>
          </div>
          <div class="col-md-9 text-right" style="padding-top:10px">
            <button type="button" class="btn btn-success btn-sm" id="btn-wa-paket-keluar">
              <i class="fa fa-whatsapp"></i> Kirim ke WhatsApp
            </button>
          </div>
        </div>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Ekspedisi / Kurir</th>
              <th width="120" class="text-center">Jumlah Paket</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= $row->nama_kurir ?: 'Lainnya' ?></td>
              <td class="text-center"><strong><?= $row->jumlah_paket ?></strong></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="3" class="text-center text-muted">Belum ada paket keluar hari ini</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr class="active">
              <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
              <td class="text-center"><strong><?= $total ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(document).off('click', '#btn-wa-paket-keluar').on('click', '#btn-wa-paket-keluar', function() {
  var btn = $(this);
  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

  $.post('laporan/send-wa-paket-keluar', function(res) {
    var data = (typeof res === 'string') ? JSON.parse(res) : res;
    if (data.code === 200) {
      noty({ text: data.message, type: 'success', timeout: 3000 });
    } else {
      noty({ text: data.message, type: 'error', timeout: 5000 });
    }
  }).fail(function() {
    noty({ text: 'Gagal menghubungi server.', type: 'error', timeout: 5000 });
  }).always(function() {
    btn.prop('disabled', false).html('<i class="fa fa-whatsapp"></i> Kirim ke WhatsApp');
  });
});
</script>
