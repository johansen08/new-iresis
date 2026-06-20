<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">
          Pencapaian Target Harian
          <span class="pull-right text-muted"><?= date('d M Y', strtotime($tanggal)) ?></span>
        </h3>
      </div>
      <div class="panel-body">

        <!-- PICKER -->
        <h4><i class="fa fa-user"></i> Picker</h4>
        <?php if (!empty($pickers)): foreach ($pickers as $row):
          $pct = $row->target > 0 ? min(round(($row->actual / $row->target) * 100), 100) : 0;
          $bar_class = $pct >= 100 ? 'progress-bar-success' : ($pct >= 50 ? 'progress-bar-warning' : 'progress-bar-danger');
        ?>
        <div class="row" style="margin-bottom:8px">
          <div class="col-md-2 text-right">
            <strong><?= $row->nama ?></strong>
          </div>
          <div class="col-md-8">
            <div class="progress" style="margin-bottom:0; height:24px;">
              <div class="progress-bar <?= $bar_class ?>" role="progressbar"
                   style="width:<?= $pct ?>%; line-height:24px; min-width:40px">
                <?= $row->actual ?><?php if ($row->target > 0): ?>/<?= $row->target ?><?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <?php if ($row->target > 0): ?>
              <span class="label label-<?= $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                <?= $pct ?>%
              </span>
            <?php else: ?>
              <span class="label label-default">No target</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <p class="text-muted">Belum ada data picker hari ini.</p>
        <?php endif; ?>

        <hr>

        <!-- PACKER -->
        <h4><i class="fa fa-cube"></i> Packer</h4>
        <?php if (!empty($packers)): foreach ($packers as $row):
          $pct = $row->target > 0 ? min(round(($row->actual / $row->target) * 100), 100) : 0;
          $bar_class = $pct >= 100 ? 'progress-bar-success' : ($pct >= 50 ? 'progress-bar-warning' : 'progress-bar-danger');
        ?>
        <div class="row" style="margin-bottom:8px">
          <div class="col-md-2 text-right">
            <strong><?= $row->nama ?></strong>
          </div>
          <div class="col-md-8">
            <div class="progress" style="margin-bottom:0; height:24px;">
              <div class="progress-bar <?= $bar_class ?>" role="progressbar"
                   style="width:<?= $pct ?>%; line-height:24px; min-width:40px">
                <?= $row->actual ?><?php if ($row->target > 0): ?>/<?= $row->target ?><?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <?php if ($row->target > 0): ?>
              <span class="label label-<?= $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                <?= $pct ?>%
              </span>
            <?php else: ?>
              <span class="label label-default">No target</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <p class="text-muted">Belum ada data packer hari ini.</p>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
