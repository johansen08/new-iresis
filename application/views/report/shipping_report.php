<style>
/* ============================================================
   SHIPPING REPORT – Premium UI
   ============================================================ */
:root {
  --sr-blue:   #1a73e8;
  --sr-teal:   #00897b;
  --sr-amber:  #f57c00;
  --sr-purple: #7b1fa2;
  --sr-red:    #c62828;
  --sr-green:  #2e7d32;
  --sr-gray:   #546e7a;
  --sr-bg:     #f4f6fb;
  --sr-card:   #ffffff;
  --sr-radius: 12px;
  --sr-shadow: 0 4px 20px rgba(0,0,0,.08);
}

/* ── Page wrapper ── */
.sr-wrapper {
  background: var(--sr-bg);
  padding: 24px;
  border-radius: var(--sr-radius);
  animation: fadeSlide .4s ease;
}
@keyframes fadeSlide {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ── Header card ── */
.sr-header {
  background: linear-gradient(135deg, #1565c0 0%, #0288d1 100%);
  color: #fff;
  border-radius: var(--sr-radius);
  padding: 22px 28px;
  margin-bottom: 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: var(--sr-shadow);
}
.sr-header-icon {
  font-size: 2.4rem;
  opacity: .9;
}
.sr-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }
.sr-header p  { margin: 4px 0 0; opacity: .8; font-size: .9rem; }

/* ── Filter panel ── */
.sr-filter-panel {
  background: var(--sr-card);
  border-radius: var(--sr-radius);
  padding: 20px 24px;
  margin-bottom: 22px;
  box-shadow: var(--sr-shadow);
}
.sr-filter-panel .form-horizontal { margin: 0; }
.sr-filter-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.sr-filter-label {
  font-weight: 600;
  color: var(--sr-gray);
  white-space: nowrap;
}
.sr-filter-input {
  flex: 1;
  min-width: 260px;
  max-width: 380px;
}
.sr-filter-input .form-control {
  border-radius: 8px;
  border: 1.5px solid #dde3ef;
  padding: 8px 14px;
  font-size: .92rem;
  transition: border-color .2s;
}
.sr-filter-input .form-control:focus { border-color: var(--sr-blue); outline: none; }
.sr-btn-search {
  background: linear-gradient(135deg, #1a73e8, #0288d1);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 8px 22px;
  font-weight: 600;
  transition: opacity .2s, transform .15s;
}
.sr-btn-search:hover { opacity:.88; transform:translateY(-1px); }
.sr-btn-excel {
  background: linear-gradient(135deg, #388e3c, #00897b);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 8px 22px;
  font-weight: 600;
  transition: opacity .2s, transform .15s;
}
.sr-btn-excel:hover { opacity:.88; transform:translateY(-1px); }

/* ── Summary cards (top KPIs) ── */
.sr-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 22px;
}
.sr-card {
  background: var(--sr-card);
  border-radius: var(--sr-radius);
  padding: 18px 20px;
  box-shadow: var(--sr-shadow);
  border-left: 5px solid transparent;
  transition: transform .2s, box-shadow .2s;
  position: relative;
  overflow: hidden;
}
.sr-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.13); }
.sr-card::after {
  content: '';
  position: absolute;
  right: -18px; top: -18px;
  width: 80px; height: 80px;
  border-radius: 50%;
  opacity: .08;
}
.sr-card.total-all   { border-color: var(--sr-blue);   } .sr-card.total-all::after   { background: var(--sr-blue);   }
.sr-card.total-spec  { border-color: var(--sr-purple);  } .sr-card.total-spec::after  { background: var(--sr-purple); }
.sr-card.total-1sku  { border-color: var(--sr-teal);   } .sr-card.total-1sku::after  { background: var(--sr-teal);  }
.sr-card.total-29sku { border-color: var(--sr-amber);  } .sr-card.total-29sku::after { background: var(--sr-amber); }
.sr-card.total-banyak{ border-color: var(--sr-red);    } .sr-card.total-banyak::after{ background: var(--sr-red);   }

.sr-card-label {
  font-size: .78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: #78909c;
  margin-bottom: 8px;
}
.sr-card-val {
  font-size: 2rem;
  font-weight: 800;
  color: #263238;
  line-height: 1;
}
.sr-card-sub {
  font-size: .76rem;
  color: #90a4ae;
  margin-top: 4px;
}
.sr-card-icon {
  font-size: 1.4rem;
  position: absolute;
  right: 20px; bottom: 14px;
  opacity: .18;
}

/* ── Tables ── */
.sr-table-card {
  background: var(--sr-card);
  border-radius: var(--sr-radius);
  padding: 20px 22px;
  box-shadow: var(--sr-shadow);
  margin-bottom: 22px;
}
.sr-table-title {
  font-size: 1rem;
  font-weight: 700;
  color: #37474f;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sr-table-title .badge-title {
  background: #e3f2fd;
  color: var(--sr-blue);
  font-size: .72rem;
  border-radius: 20px;
  padding: 2px 10px;
  font-weight: 700;
}

.sr-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: .88rem;
}
.sr-table thead th {
  background: #eceff1;
  color: #455a64;
  font-weight: 700;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  padding: 10px 14px;
  border-bottom: 2px solid #cfd8dc;
}
.sr-table thead th:first-child { border-radius: 8px 0 0 0; }
.sr-table thead th:last-child  { border-radius: 0 8px 0 0; }
.sr-table tbody td {
  padding: 10px 14px;
  border-bottom: 1px solid #f0f4f8;
  vertical-align: middle;
}
.sr-table tbody tr:last-child td { border-bottom: none; }
.sr-table tbody tr:hover td { background: #f7fbff; }
.sr-table tfoot td {
  padding: 10px 14px;
  font-weight: 700;
  background: #e8f5e9;
  border-top: 2px solid #c8e6c9;
}

/* ── Badges ── */
.pill {
  display: inline-block;
  border-radius: 20px;
  padding: 3px 12px;
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: .3px;
}
.pill-blue   { background: #e3f2fd; color: #1565c0; }
.pill-purple { background: #f3e5f5; color: #7b1fa2; }
.pill-teal   { background: #e0f2f1; color: #004d40; }
.pill-amber  { background: #fff8e1; color: #e65100; }
.pill-red    { background: #ffebee; color: #b71c1c; }
.pill-gray   { background: #eceff1; color: #546e7a; }

/* ── Courier icon ── */
.courier-name { font-weight: 700; font-size: .95rem; color: #263238; }

/* ── No data state ── */
.sr-empty {
  text-align: center;
  padding: 60px 20px;
  color: #90a4ae;
}
.sr-empty-icon { font-size: 3rem; margin-bottom: 12px; }
.sr-empty p { margin: 0; font-size: .95rem; }

/* Progress bar in table */
.sr-bar-wrap { background: #eceff1; border-radius: 6px; height: 6px; margin-top: 4px; }
.sr-bar { height: 6px; border-radius: 6px; }
</style>

<div class="sr-wrapper">

  <!-- ── Page Header ── -->
  <div class="sr-header">
    <span class="sr-header-icon"><i class="fa fa-truck"></i></span>
    <div>
      <h2>Laporan Pengiriman Paket</h2>
      <p>Rekap total paket terkirim per kurir beserta detail kategori resi</p>
    </div>
  </div>

  <!-- ── Filter Panel ── -->
  <div class="sr-filter-panel">
    <form action="report/shipping-report" class="form-horizontal" method="post" id="form-report-receipt-shipping">
      <div class="sr-filter-row">
        <span class="sr-filter-label"><i class="fa fa-calendar"></i> Rentang Waktu :</span>
        <div class="sr-filter-input">
          <input type="text" name="reportrange" id="reportrange" class="form-control"
                 value="<?= !empty($reportrange) ? $reportrange : '' ?>" />
        </div>
        <button type="submit" class="btn sr-btn-search" id="btn-search">
          <i class="fa fa-search"></i> Cari
        </button>
        <button type="submit" class="btn sr-btn-excel" id="btn-export-excel">
          <i class="fa fa-file-excel-o"></i> Ekspor Excel
        </button>
      </div>
    </form>
  </div>

  <?php if (!empty($list_data)): ?>

    <!-- ── KPI Summary Cards ── -->
    <?php
      $cat = !empty($cat_totals) ? $cat_totals : [];
      $gt  = !empty($grand_total) ? $grand_total : 0;
      $t_special = isset($cat['total_special'])   ? (int)$cat['total_special']   : 0;
      $t_1sku    = isset($cat['total_1sku'])       ? (int)$cat['total_1sku']       : 0;
      $t_29sku   = isset($cat['total_2_9sku'])     ? (int)$cat['total_2_9sku']     : 0;
      $t_banyak  = isset($cat['total_qty_banyak']) ? (int)$cat['total_qty_banyak'] : 0;
      $pct = function($v,$t){ return $t>0 ? round($v/$t*100,1) : 0; };
    ?>

    <div class="sr-cards">
      <!-- Grand Total -->
      <div class="sr-card total-all">
        <div class="sr-card-label">Grand Total</div>
        <div class="sr-card-val"><?= number_format($gt) ?></div>
        <div class="sr-card-sub">Seluruh paket dikirim</div>
        <span class="sr-card-icon"><i class="fa fa-boxes"></i></span>
      </div>
      <!-- Resi Special -->
      <div class="sr-card total-spec">
        <div class="sr-card-label">Resi Special</div>
        <div class="sr-card-val"><?= number_format($t_special) ?></div>
        <div class="sr-card-sub"><?= $pct($t_special,$gt) ?>% dari total</div>
        <span class="sr-card-icon"><i class="fa fa-star"></i></span>
      </div>
      <!-- 1 SKU -->
      <div class="sr-card total-1sku">
        <div class="sr-card-label">1 SKU &amp; Qty ≤9</div>
        <div class="sr-card-val"><?= number_format($t_1sku) ?></div>
        <div class="sr-card-sub"><?= $pct($t_1sku,$gt) ?>% dari total</div>
        <span class="sr-card-icon"><i class="fa fa-cube"></i></span>
      </div>
      <!-- 2-9 SKU -->
      <div class="sr-card total-29sku">
        <div class="sr-card-label">2-9 SKU &amp; Qty ≤9</div>
        <div class="sr-card-val"><?= number_format($t_29sku) ?></div>
        <div class="sr-card-sub"><?= $pct($t_29sku,$gt) ?>% dari total</div>
        <span class="sr-card-icon"><i class="fa fa-cubes"></i></span>
      </div>
      <!-- Qty Banyak -->
      <div class="sr-card total-banyak">
        <div class="sr-card-label">Qty Banyak (&gt;9)</div>
        <div class="sr-card-val"><?= number_format($t_banyak) ?></div>
        <div class="sr-card-sub"><?= $pct($t_banyak,$gt) ?>% dari total</div>
        <span class="sr-card-icon"><i class="fa fa-archive"></i></span>
      </div>
    </div>

    <!-- ── Detail Table per Kurir ── -->
    <?php if (!empty($detail_data)): ?>
    <div class="sr-table-card">
      <div class="sr-table-title">
        <i class="fa fa-table" style="color:var(--sr-blue)"></i>
        Rincian per Kurir
        <span class="badge-title"><?= count($detail_data) ?> kurir</span>
      </div>
      <div style="overflow-x:auto">
        <table class="sr-table" id="table-shipping-detail">
          <thead>
            <tr>
              <th style="width:40px">#</th>
              <th>Kurir</th>
              <th class="text-center">Total Paket</th>
              <th class="text-center"><span class="pill pill-purple">Resi Special</span></th>
              <th class="text-center"><span class="pill pill-teal">1 SKU &amp; Qty ≤9</span></th>
              <th class="text-center"><span class="pill pill-amber">2-9 SKU &amp; Qty ≤9</span></th>
              <th class="text-center"><span class="pill pill-red">Qty Banyak (&gt;9)</span></th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; foreach ($detail_data as $d):
              $tot = (int)$d['total'];
              $p_spec  = $pct((int)$d['total_special'],   $tot);
              $p_1sku  = $pct((int)$d['total_1sku'],      $tot);
              $p_29sku = $pct((int)$d['total_2_9sku'],    $tot);
              $p_banyak= $pct((int)$d['total_qty_banyak'],$tot);
            ?>
            <tr>
              <td style="color:#90a4ae;font-size:.8rem"><?= $i++ ?></td>
              <td>
                <span class="courier-name">
                  <i class="fa fa-truck" style="color:var(--sr-blue);margin-right:6px"></i>
                  <?= htmlspecialchars($d['nama_kurir']) ?>
                </span>
              </td>
              <td class="text-center">
                <span style="font-size:1.15rem;font-weight:800;color:#263238"><?= number_format($tot) ?></span>
              </td>
              <td class="text-center">
                <div><span class="pill pill-purple"><?= number_format($d['total_special']) ?></span></div>
                <div class="sr-bar-wrap" style="width:70px;margin:4px auto 0">
                  <div class="sr-bar" style="width:<?= $p_spec ?>%;background:#7b1fa2"></div>
                </div>
                <div style="font-size:.7rem;color:#90a4ae;margin-top:2px"><?= $p_spec ?>%</div>
              </td>
              <td class="text-center">
                <div><span class="pill pill-teal"><?= number_format($d['total_1sku']) ?></span></div>
                <div class="sr-bar-wrap" style="width:70px;margin:4px auto 0">
                  <div class="sr-bar" style="width:<?= $p_1sku ?>%;background:#00897b"></div>
                </div>
                <div style="font-size:.7rem;color:#90a4ae;margin-top:2px"><?= $p_1sku ?>%</div>
              </td>
              <td class="text-center">
                <div><span class="pill pill-amber"><?= number_format($d['total_2_9sku']) ?></span></div>
                <div class="sr-bar-wrap" style="width:70px;margin:4px auto 0">
                  <div class="sr-bar" style="width:<?= $p_29sku ?>%;background:#f57c00"></div>
                </div>
                <div style="font-size:.7rem;color:#90a4ae;margin-top:2px"><?= $p_29sku ?>%</div>
              </td>
              <td class="text-center">
                <div><span class="pill pill-red"><?= number_format($d['total_qty_banyak']) ?></span></div>
                <div class="sr-bar-wrap" style="width:70px;margin:4px auto 0">
                  <div class="sr-bar" style="width:<?= $p_banyak ?>%;background:#c62828"></div>
                </div>
                <div style="font-size:.7rem;color:#90a4ae;margin-top:2px"><?= $p_banyak ?>%</div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" style="text-align:right"><i class="fa fa-sigma"></i> TOTAL</td>
              <td class="text-center" style="font-size:1.1rem"><?= number_format($gt) ?></td>
              <td class="text-center"><?= number_format($t_special) ?><br><small style="color:#90a4ae;font-weight:400"><?= $pct($t_special,$gt) ?>%</small></td>
              <td class="text-center"><?= number_format($t_1sku) ?><br><small style="color:#90a4ae;font-weight:400"><?= $pct($t_1sku,$gt) ?>%</small></td>
              <td class="text-center"><?= number_format($t_29sku) ?><br><small style="color:#90a4ae;font-weight:400"><?= $pct($t_29sku,$gt) ?>%</small></td>
              <td class="text-center"><?= number_format($t_banyak) ?><br><small style="color:#90a4ae;font-weight:400"><?= $pct($t_banyak,$gt) ?>%</small></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="sr-table-card">
      <div class="sr-empty">
        <div class="sr-empty-icon"><i class="fa fa-truck" style="color:#cfd8dc"></i></div>
        <p>Pilih rentang waktu dan klik <strong>Cari</strong> untuk melihat laporan</p>
      </div>
    </div>
  <?php endif; ?>

</div><!-- /.sr-wrapper -->

<script>
(function() {
  var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : 'null' ?>;
  var start = report_range ? moment(report_range.split(' - ')[0]) : moment().startOf('day');
  var end   = report_range ? moment(report_range.split(' - ')[1]) : moment();

  $('#reportrange').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: start,
    endDate: end,
    ranges: {
      'Hari Ini'  : [moment().startOf('day'), moment()],
      '1 Jam Lalu': [moment().subtract(1,'hours'), moment()],
      'Kemarin'   : [moment().subtract(1,'days').startOf('day'), moment().startOf('day')],
      '7 Hari'    : [moment().subtract(6,'days').startOf('day'), moment()],
      'Bulan Ini' : [moment().startOf('month'), moment().endOf('month')],
    },
    locale: { format: 'YYYY-MM-DD HH:mm:ss' },
  });

  $('#btn-search').on('click', function() {
    $('#form-report-receipt-shipping').removeAttr('target').removeClass('nojs')
      .attr('action', 'report/shipping-report');
  });

  $('#btn-export-excel').on('click', function() {
    $('#form-report-receipt-shipping').attr('target','_blank').addClass('nojs')
      .attr('action', 'report/export-to-excel-shipping-report');
  });
})();
</script>