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

