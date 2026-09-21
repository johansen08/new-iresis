<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><span class="fa fa-archive"></span> Mode Arsip</h3>
      </div>
      <div class="panel-body">

        <?php if ($aktif): ?>
          <div class="alert alert-danger">
            <strong>Anda sedang di MODE ARSIP</strong> &mdash; database <code><?= $db_arsip ?></code>,
            <?= $tulis ? '<strong>tulisan diizinkan</strong> (lihat catatan di bawah)' : 'hanya baca' ?>.
            Semua menu yang Anda buka sekarang menampilkan data arsip, bukan data live.
          </div>
        <?php else: ?>
          <div class="alert alert-info">
            Anda sedang di <strong>data LIVE</strong> &mdash; database <code><?= $db_live ?></code>.
          </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-6">
            <h4>Apa bedanya?</h4>
            <table class="table table-bordered table-condensed">
              <tr>
                <th width="30%">Live (<code><?= $db_live ?></code>)</th>
                <td>Data <?= $retensi_hari ?> hari terakhir. Dipakai semua proses harian: scan, upload, picking, packing, HO. Cepat.</td>
              </tr>
              <tr>
                <th>Arsip (<code><?= $db_arsip ?></code>)</th>
                <td>
                  <strong>Semua data sejak awal</strong> sampai sinkron terakhir (tiap malam 01.00).
                  Untuk laporan, KPI, dan pencarian resi lama. Hanya baca &mdash; tidak ada scan/upload di sini.
                </td>
              </tr>
            </table>
            <p class="text-muted">
              Retur/komplain untuk resi lama tidak perlu Mode Arsip: alur retur menarik resi itu
              kembali ke live secara otomatis.
            </p>
          </div>

          <div class="col-md-6">
            <h4>Kesegaran arsip</h4>
            <?php if (empty($status) || empty($status->terakhir)): ?>
              <div class="alert alert-warning">Arsip belum pernah disinkron (tabel <code>_arsip_status</code> kosong / belum ada).</div>
            <?php else: ?>
              <table class="table table-bordered table-condensed">
                <tr><th width="45%">Sinkron terakhir</th><td><?= date('d M Y H:i', strtotime($status->terakhir)) ?></td></tr>
                <tr><th>Tabel disalin</th><td><?= (int) $status->jml_tabel - (int) $status->dilewati ?> (dilewati: <?= (int) $status->dilewati ?>)</td></tr>
                <?php if ((int) $status->berjalan > 0): ?>
                  <tr><th>Status</th><td class="text-warning"><span class="fa fa-refresh fa-spin"></span> sinkron sedang berjalan (<?= (int) $status->berjalan ?> tabel)</td></tr>
                <?php endif; ?>
              </table>
            <?php endif; ?>

            <?php if (!empty($gagal)): ?>
              <div class="alert alert-danger">
                <strong>Ada kegagalan sinkron 3 hari terakhir:</strong>
                <ul style="margin-bottom:0">
                  <?php foreach ($gagal as $g): ?>
                    <li><?= date('d/m H:i', strtotime($g->waktu)) ?> [<?= $g->tahap ?><?= $g->tabel ? ' ' . $g->tabel : '' ?>] <?= htmlspecialchars($g->pesan) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <hr>

        <?php if ($aktif): ?>
          <a href="<?= base_url('arsip/keluar') ?>" class="btn btn-success btn-lg">
            <span class="fa fa-sign-out"></span> Kembali ke data LIVE
          </a>
        <?php elseif ($boleh): ?>
          <a href="<?= base_url('arsip/masuk') ?>" class="btn btn-danger btn-lg">
            <span class="fa fa-archive"></span> Masuk Mode Arsip
          </a>
          <span class="text-muted" style="margin-left:10px">Aplikasi akan dimuat ulang; banner merah muncul selama mode aktif.</span>
        <?php else: ?>
          <button class="btn btn-default btn-lg" disabled><span class="fa fa-lock"></span> Masuk Mode Arsip</button>
          <span class="text-muted" style="margin-left:10px">Role Anda belum diberi akses menu ini. Minta admin mencentangnya di halaman Access.</span>
        <?php endif; ?>

        <?php if ($tulis): ?>
          <div class="alert alert-warning" style="margin-top:15px">
            <strong>Catatan:</strong> <code>mode_arsip_tulis</code> aktif. Data baru yang dibuat di Mode Arsip memakai id yang
            juga akan dipakai live, dan malam berikutnya sinkron dari live menimpanya. Jangan melakukan transaksi di sini.
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
