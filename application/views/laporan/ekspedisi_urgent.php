<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Laporan Ekspedisi Urgent</h3>
      </div>
      <div class="panel-body">

        <div class="row margin-bottom-15">
          <div class="col-md-6">
            <p class="text-muted" style="margin-bottom:0">Data 2 hari kebelakang &mdash; resi yang belum selesai proses</p>
          </div>
        </div>

        <table class="table table-striped table-bordered" id="tbl-ekspedisi-urgent">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Ekspedisi</th>
              <th width="100" class="text-center">Total Resi</th>
              <th width="100" class="text-center text-danger">Belum Pick</th>
              <th width="100" class="text-center text-warning">Belum Pack</th>
              <th width="100" class="text-center text-info">Belum HO</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $grand_total = 0; $grand_pick = 0; $grand_pack = 0; $grand_ho = 0;
              if (!empty($rows)): $i = 1; foreach ($rows as $row):
                $grand_total += $row->total_resi;
                $grand_pick  += $row->belum_pick;
                $grand_pack  += $row->belum_pack;
                $grand_ho    += $row->belum_ho;
            ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= $row->nama_kurir ?></td>
              <td class="text-center"><strong><?= number_format($row->total_resi) ?></strong></td>
              <td class="text-center">
                <?php if ($row->belum_pick > 0): ?>
                  <a href="#" class="btn-detail-urgent text-danger" data-kurir="<?= $row->id_kurir ?>" data-status="belum_pick" data-nama="<?= $row->nama_kurir ?>">
                    <strong><?= $row->belum_pick ?></strong>
                  </a>
                <?php else: ?>
                  <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($row->belum_pack > 0): ?>
                  <a href="#" class="btn-detail-urgent text-warning" data-kurir="<?= $row->id_kurir ?>" data-status="belum_pack" data-nama="<?= $row->nama_kurir ?>">
                    <strong><?= $row->belum_pack ?></strong>
                  </a>
                <?php else: ?>
                  <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($row->belum_ho > 0): ?>
                  <a href="#" class="btn-detail-urgent text-info" data-kurir="<?= $row->id_kurir ?>" data-status="belum_ho" data-nama="<?= $row->nama_kurir ?>">
                    <strong><?= $row->belum_ho ?></strong>
                  </a>
                <?php else: ?>
                  <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr class="active">
              <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
              <td class="text-center"><strong><?= number_format($grand_total) ?></strong></td>
              <td class="text-center text-danger"><strong><?= $grand_pick ?></strong></td>
              <td class="text-center text-warning"><strong><?= $grand_pack ?></strong></td>
              <td class="text-center text-info"><strong><?= $grand_ho ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-detail-urgent" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="modal-detail-title">Detail Resi</h4>
      </div>
      <div class="modal-body">
        <table class="table table-striped table-bordered" id="tbl-detail-urgent" width="100%">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>No Resi</th>
              <th>Kurir</th>
              <th width="160">Tanggal Print</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(document).off('click', '.btn-detail-urgent').on('click', '.btn-detail-urgent', function(e) {
  e.preventDefault();
  var kurir  = $(this).data('kurir');
  var status = $(this).data('status');
  var nama   = $(this).data('nama');
  var label  = status.replace('_', ' ').toUpperCase();

  $('#modal-detail-title').text(nama + ' - ' + label);

  if ($.fn.DataTable.isDataTable('#tbl-detail-urgent')) {
    $('#tbl-detail-urgent').DataTable().destroy();
  }

  $('#tbl-detail-urgent').DataTable({
    processing: true,
    ajax: {
      url: 'laporan/get-data-ekspedisi-urgent-detail',
      type: 'POST',
      data: { id_kurir: kurir, status: status }
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 }
    ],
    pageLength: 25,
    language: { emptyTable: 'Tidak ada data' }
  });

  $('#modal-detail-urgent').modal('show');
});

</script>
