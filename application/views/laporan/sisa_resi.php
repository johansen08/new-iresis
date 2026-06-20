<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Sisa Resi Belum Kirim</h3>
      </div>
      <div class="panel-body">

        <div class="row margin-bottom-15">
          <div class="col-md-3">
            <div class="alert alert-warning text-center" style="margin-bottom:0">
              <h2 style="margin:0"><?= $total ?></h2>
              <small>Total Sisa Resi (cutoff <?= $cutoff ?>)</small>
            </div>
          </div>
          <div class="col-md-9 text-right" style="padding-top:10px">
            <button type="button" class="btn btn-success btn-sm" id="btn-wa-sisa-resi">
              <i class="fa fa-whatsapp"></i> Kirim ke WhatsApp
            </button>
          </div>
        </div>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Ekspedisi / Kurir</th>
              <th width="120" class="text-center">Jumlah Sisa</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= $row->nama_kurir ?: 'Lainnya' ?></td>
              <td class="text-center"><strong><?= $row->jumlah_sisa ?></strong></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="3" class="text-center text-muted">Semua resi sudah dikirim ✓</td></tr>
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
$(document).off('click', '#btn-wa-sisa-resi').on('click', '#btn-wa-sisa-resi', function() {
  var btn = $(this);
  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

  $.post('laporan/send-wa-sisa-resi', function(res) {
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
