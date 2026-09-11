<style>
/* ============================================================
   SKU SPECIAL REPORT – Premium UI
   ============================================================ */
:root {
  --sk-blue:   #1a73e8;
  --sk-purple: #7b1fa2;
  --sk-teal:   #00897b;
  --sk-amber:  #f57c00;
  --sk-red:    #c62828;
  --sk-green:  #2e7d32;
  --sk-card:   #ffffff;
  --sk-bg:     #f4f6fb;
  --sk-radius: 12px;
  --sk-shadow: 0 4px 20px rgba(0,0,0,.08);
}

.sk-wrapper {
  background: var(--sk-bg);
  padding: 24px;
  border-radius: var(--sk-radius);
  animation: fadeUp .4s ease;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ── Header ── */
.sk-header {
  background: linear-gradient(135deg, #6a1b9a 0%, #ab47bc 100%);
  color: #fff;
  border-radius: var(--sk-radius);
  padding: 22px 28px;
  margin-bottom: 22px;
  display: flex; align-items: center; gap: 16px;
  box-shadow: var(--sk-shadow);
}
.sk-header-icon { font-size: 2.4rem; opacity:.9; }
.sk-header h2   { margin:0; font-size:1.5rem; font-weight:700; }
.sk-header p    { margin:4px 0 0; opacity:.8; font-size:.9rem; }

/* ── Filter ── */
.sk-filter {
  background: var(--sk-card);
  border-radius: var(--sk-radius);
  padding: 20px 24px;
  margin-bottom: 22px;
  box-shadow: var(--sk-shadow);
}
.sk-filter-row { display:flex; align-items:center; flex-wrap:wrap; gap:12px; }
.sk-filter-label { font-weight:600; color:#546e7a; white-space:nowrap; }
.sk-filter-inp { flex:1; min-width:260px; max-width:380px; }
.sk-filter-inp .form-control {
  border-radius:8px; border:1.5px solid #dde3ef;
  padding:8px 14px; font-size:.92rem;
  transition: border-color .2s;
}
.sk-filter-inp .form-control:focus { border-color:var(--sk-purple); outline:none; }
.sk-btn-cari {
  background: linear-gradient(135deg, #6a1b9a, #ab47bc);
  color:#fff!important; border:none; border-radius:8px;
  padding:8px 22px; font-weight:600;
  transition: opacity .2s, transform .15s;
}
.sk-btn-cari:hover { opacity:.88; transform:translateY(-1px); }
.sk-btn-excel {
  background: linear-gradient(135deg, #388e3c, #00897b);
  color:#fff!important; border:none; border-radius:8px;
  padding:8px 22px; font-weight:600;
  transition: opacity .2s, transform .15s;
}
.sk-btn-excel:hover { opacity:.88; transform:translateY(-1px); }

/* ── Summary cards ── */
.sk-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px,1fr));
  gap: 16px;
  margin-bottom: 22px;
}
.sk-card {
  background: var(--sk-card);
  border-radius: var(--sk-radius);
  padding: 18px 20px;
  box-shadow: var(--sk-shadow);
  border-left: 5px solid transparent;
  position: relative; overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.sk-card:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,.12); }
.sk-card::after {
  content:''; position:absolute;
  right:-18px; top:-18px;
  width:80px; height:80px;
  border-radius:50%; opacity:.07;
}
.sk-card.c-total  { border-color:var(--sk-blue);   } .sk-card.c-total::after  { background:var(--sk-blue);   }
.sk-card.c-spec   { border-color:var(--sk-purple);  } .sk-card.c-spec::after   { background:var(--sk-purple); }
.sk-card.c-1sku   { border-color:var(--sk-teal);   } .sk-card.c-1sku::after   { background:var(--sk-teal);  }
.sk-card.c-29sku  { border-color:var(--sk-amber);  } .sk-card.c-29sku::after  { background:var(--sk-amber); }
.sk-card.c-banyak { border-color:var(--sk-red);    } .sk-card.c-banyak::after { background:var(--sk-red);   }

.sk-card-lbl { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#78909c; margin-bottom:8px; }
.sk-card-val { font-size:2rem; font-weight:800; color:#263238; line-height:1; }
.sk-card-sub { font-size:.75rem; color:#90a4ae; margin-top:4px; }
.sk-card-ico { font-size:1.4rem; position:absolute; right:18px; bottom:14px; opacity:.15; }

/* ── Tabs for SKU summary ── */
.sk-tab-panel {
  background: var(--sk-card);
  border-radius: var(--sk-radius);
  box-shadow: var(--sk-shadow);
  margin-bottom: 22px;
  overflow: hidden;
}
.sk-tab-header {
  display: flex;
  border-bottom: 2px solid #eceff1;
  background: #fafafa;
}
.sk-tab-btn {
  flex: 1;
  padding: 13px 10px;
  text-align: center;
  cursor: pointer;
  font-weight: 600;
  font-size: .82rem;
  color: #78909c;
  border: none;
  background: transparent;
  border-bottom: 3px solid transparent;
  transition: color .2s, border-color .2s;
  outline: none;
}
.sk-tab-btn.active {
  color: var(--sk-purple);
  border-bottom-color: var(--sk-purple);
  background: #fff;
}
.sk-tab-btn:hover:not(.active) { color: #37474f; background: #f3e5f5; }
.sk-tab-pane { display: none; padding: 18px 20px; }
.sk-tab-pane.active { display: block; }

/* ── Tables ── */
.sk-tbl {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: .88rem;
}
.sk-tbl thead th {
  background: #f3e5f5;
  color: #4a148c;
  font-weight: 700;
  font-size: .76rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  padding: 10px 14px;
  border-bottom: 2px solid #ce93d8;
}
.sk-tbl tbody td {
  padding: 10px 14px;
  border-bottom: 1px solid #f3e5f5;
  vertical-align: middle;
}
.sk-tbl tbody tr:last-child td { border-bottom: none; }
.sk-tbl tbody tr:hover td { background: #fce4ec; }

/* ── DataTable section ── */
.sk-dt-card {
  background: var(--sk-card);
  border-radius: var(--sk-radius);
  padding: 20px 22px;
  box-shadow: var(--sk-shadow);
}
.sk-dt-title {
  font-size:1rem; font-weight:700; color:#37474f;
  margin-bottom:14px; display:flex; align-items:center; gap:8px;
}
.sk-dt-title .lbl { background:#f3e5f5; color:var(--sk-purple); font-size:.72rem; border-radius:20px; padding:2px 10px; font-weight:700; }

/* ── Pill badges ── */
.pk { display:inline-block; border-radius:20px; padding:3px 12px; font-size:.74rem; font-weight:700; }
.pk-blue   { background:#e3f2fd; color:#1565c0; }
.pk-purple { background:#f3e5f5; color:#7b1fa2; }
.pk-teal   { background:#e0f2f1; color:#004d40; }
.pk-amber  { background:#fff8e1; color:#e65100; }
.pk-red    { background:#ffebee; color:#b71c1c; }
.pk-green  { background:#e8f5e9; color:#1b5e20; }
.pk-gray   { background:#eceff1; color:#546e7a; }

/* ── Status badges ── */
.status-b { padding:3px 9px; border-radius:6px; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.s-normal  { background:#e8f5e9; color:#2e7d32; }
.s-batal   { background:#ffebee; color:#c62828; }
.s-pending { background:#fff3e0; color:#ef6c00; }

/* ── Category tag in DataTable ── */
.cat-badge { display:inline-block; border-radius:6px; padding:3px 10px; font-size:.72rem; font-weight:700; }
.cat-spec   { background:#f3e5f5; color:#7b1fa2; }
.cat-1sku   { background:#e0f2f1; color:#004d40; }
.cat-29sku  { background:#fff8e1; color:#e65100; }
.cat-banyak { background:#ffebee; color:#b71c1c; }

/* ── Empty state ── */
.sk-empty { text-align:center; padding:60px 20px; color:#90a4ae; }
.sk-empty-icon { font-size:3rem; margin-bottom:12px; }
</style>

<div class="sk-wrapper">

  <!-- Header -->
  <div class="sk-header">
    <span class="sk-header-icon"><i class="fa fa-star"></i></span>
    <div>
      <h2>Laporan SKU Special</h2>
      <p>Detail resi dengan produk SKU special beserta kategorisasi berdasarkan jumlah SKU dan quantity</p>
    </div>
  </div>

  <!-- Filter -->
  <div class="sk-filter">
    <form action="report/sku-special-report" class="form-horizontal" method="post" id="form-report-sku-special">
      <div class="sk-filter-row">
        <span class="sk-filter-label"><i class="fa fa-calendar"></i> Rentang Waktu :</span>
        <div class="sk-filter-inp">
          <input type="text" name="reportrange" id="reportrange" class="form-control"
                 value="<?= !empty($reportrange) ? $reportrange : '' ?>" />
        </div>
        <button type="submit" class="btn sk-btn-cari" id="btn-search">
          <i class="fa fa-search"></i> Cari
        </button>
        <button type="submit" class="btn sk-btn-excel" id="btn-export-excel">
          <i class="fa fa-file-excel-o"></i> Ekspor Excel
        </button>
      </div>
    </form>
  </div>

  <?php if (!empty($summary)): ?>

    <?php
      $t_resi = isset($sku_cat_totals['total_resi']) ? (int)$sku_cat_totals['total_resi'] : 0;
      $t_spec = isset($sku_cat_totals['total_special']) ? (int)$sku_cat_totals['total_special'] : 0;
      $t_1sku = isset($sku_cat_totals['total_1sku']) ? (int)$sku_cat_totals['total_1sku'] : 0;
      $t_29sku = isset($sku_cat_totals['total_2_9sku']) ? (int)$sku_cat_totals['total_2_9sku'] : 0;
      $t_banyak = isset($sku_cat_totals['total_qty_banyak']) ? (int)$sku_cat_totals['total_qty_banyak'] : 0;

      $total_spec_qty  = 0;
      if (!empty($summary)) {
          foreach ($summary as $s) {
              $total_spec_qty += (int)$s->total_qty;
          }
      }
    ?>

    <!-- ── KPI Cards ── -->
    <div class="sk-cards">
      <div class="sk-card c-spec">
        <div class="sk-card-lbl">Resi Special</div>
        <div class="sk-card-val"><?= number_format($t_spec) ?></div>
        <div class="sk-card-sub">resi kategori ini</div>
        <span class="sk-card-ico"><i class="fa fa-star"></i></span>
      </div>
      <div class="sk-card c-total">
        <div class="sk-card-lbl">Total Unit Special</div>
        <div class="sk-card-val"><?= number_format($total_spec_qty) ?></div>
        <div class="sk-card-sub">unit produk SKU special</div>
        <span class="sk-card-ico"><i class="fa fa-cubes"></i></span>
      </div>
      <div class="sk-card c-1sku">
        <div class="sk-card-lbl">1 SKU &amp; Qty ≤9</div>
        <div class="sk-card-val" id="cnt-1sku"><?= number_format($t_1sku) ?></div>
        <div class="sk-card-sub">resi kategori ini</div>
        <span class="sk-card-ico"><i class="fa fa-cube"></i></span>
      </div>
      <div class="sk-card c-29sku">
        <div class="sk-card-lbl">2-9 SKU &amp; Qty ≤9</div>
        <div class="sk-card-val" id="cnt-29sku"><?= number_format($t_29sku) ?></div>
        <div class="sk-card-sub">resi kategori ini</div>
        <span class="sk-card-ico"><i class="fa fa-cubes"></i></span>
      </div>
      <div class="sk-card c-banyak">
        <div class="sk-card-lbl">Qty Banyak (&gt;9)</div>
        <div class="sk-card-val" id="cnt-banyak"><?= number_format($t_banyak) ?></div>
        <div class="sk-card-sub">resi kategori ini</div>
        <span class="sk-card-ico"><i class="fa fa-archive"></i></span>
      </div>
    </div>

    <!-- ── SKU Summary Tabs ── -->
    <div class="sk-tab-panel">
      <div class="sk-tab-header">
        <button class="sk-tab-btn active" onclick="switchTab(this,'tab-sku-summary')">
          <i class="fa fa-list"></i> Ringkasan per SKU
        </button>
        <button class="sk-tab-btn" onclick="switchTab(this,'tab-detail')">
          <i class="fa fa-table"></i> Detail Transaksi Resi
        </button>
      </div>

      <!-- Tab 1 – SKU Summary -->
      <div id="tab-sku-summary" class="sk-tab-pane active">
        <p style="color:#78909c;font-size:.82rem;margin-bottom:14px">
          <i class="fa fa-info-circle"></i>
          Daftar SKU yang berstatus <em>special</em> beserta total unit yang dikirim dalam periode ini.
        </p>
        <div style="overflow-x:auto">
          <table class="sk-tbl">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>SKU Produk</th>
                <th>Nama SKU</th>
                <th class="text-center">Total Unit</th>
                <th class="text-center">Jumlah Resi</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($summary as $row): ?>
              <tr>
                <td style="color:#90a4ae;font-size:.8rem"><?= $i++ ?></td>
                <td><strong style="color:var(--sk-purple)"><?= htmlspecialchars($row->sku) ?></strong></td>
                <td><?= htmlspecialchars($row->nama_sku) ?></td>
                <td class="text-center"><span class="pk pk-purple"><?= number_format($row->total_qty) ?></span></td>
                <td class="text-center"><span class="pk pk-blue"><?= number_format($row->total_resi) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2 – DataTable detail -->
      <div id="tab-detail" class="sk-tab-pane">
        <p style="color:#78909c;font-size:.82rem;margin-bottom:14px">
          <i class="fa fa-info-circle"></i>
          Setiap baris adalah 1 SKU special dalam 1 resi, beserta kategori berdasarkan jumlah SKU &amp; quantity total resi tersebut.
        </p>
        <?php if($this->input->method() == 'post'): ?>
        <div style="overflow-x:auto">
          <table class="sk-tbl" id="datatable-report-sku-special" style="width:100%">
            <thead>
              <tr>
                <th>#</th>
                <th>Tanggal / Jam</th>
                <th>No Resi</th>
                <th>SKU</th>
                <th class="text-center">Qty</th>
                <th>Marketplace</th>
                <th>Kurir</th>
                <th class="text-center">Kategori Resi</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
          </table>
        </div>
        <?php else: ?>
          <div class="sk-empty">
            <div class="sk-empty-icon"><i class="fa fa-table" style="color:#cfd8dc"></i></div>
            <p>Klik tab <strong>Cari</strong> di atas untuk memuat data detail transaksi.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <div style="background:#fff;border-radius:var(--sk-radius);box-shadow:var(--sk-shadow);padding:22px">
      <div class="sk-empty">
        <div class="sk-empty-icon"><i class="fa fa-star" style="color:#cfd8dc"></i></div>
        <p>Pilih rentang waktu dan klik <strong>Cari</strong> untuk melihat laporan SKU Special</p>
      </div>
      <!-- Still render the DataTable container so script works -->
      <div style="display:none">
        <table id="datatable-report-sku-special"></table>
      </div>
    </div>
  <?php endif; ?>

</div><!-- /.sk-wrapper -->

<script>
(function() {
  /* ── Tab switcher ── */
  window.switchTab = function(btn, paneId) {
    document.querySelectorAll('.sk-tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.sk-tab-pane').forEach(function(p){ p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(paneId).classList.add('active');
  };

  /* ── Dependency check ── */
  var itry = 0;
  function checkDeps() {
    if (window.jQuery && window.moment && window.jQuery.fn.DataTable && window.jQuery.fn.daterangepicker) {
      init(window.jQuery);
    } else if (itry++ < 80) {
      setTimeout(checkDeps, 100);
    }
  }

  function init($) {
    if ($('#reportrange').data('daterangepicker')) return;

    var range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : 'null' ?>;
    var start = range ? moment(range.split(' - ')[0]) : moment().startOf('day');
    var end   = range ? moment(range.split(' - ')[1]) : moment();

    $('#reportrange').daterangepicker({
      timePicker: true,
      timePicker24Hour: true,
      startDate: start, endDate: end,
      ranges: {
        'Hari Ini'  : [moment().startOf('day'), moment()],
        'Kemarin'   : [moment().subtract(1,'days').startOf('day'), moment().startOf('day')],
        '7 Hari'    : [moment().subtract(6,'days').startOf('day'), moment()],
        'Bulan Ini' : [moment().startOf('month'), moment().endOf('month')],
      },
      locale: { format: 'YYYY-MM-DD HH:mm:ss' },
    });

    <?php if($this->input->method() == 'post'): ?>
    var catCount = { '1 SKU & Qty ≤9': 0, '2-9 SKU & Qty ≤9': 0, 'Qty Banyak': 0 };

    var dt = $('#datatable-report-sku-special').DataTable({
      scrollX: true,
      processing: true,
      serverSide: true,
      pageLength: 50,
      order: [[1,'desc']],
      ajax: {
        url: '<?= site_url("report/get-sku-special-report-data") ?>',
        type: 'POST',
        data: function(d) {
          var r = $('#reportrange').val();
          d.start_date = r.split(' - ')[0];
          d.end_date   = r.split(' - ')[1];
        }
      },
      columns: [
        { data: 0 }, // #
        { data: 1 }, // tanggal
        { data: 2 }, // noresi
        { data: 3 }, // sku
        { data: 4, className:'text-center' }, // qty
        { data: 5 }, // marketplace
        { data: 6 }, // kurir
        { data: 7, className:'text-center' }, // kategori
        { data: 8, className:'text-center' }, // status (used to be index 7)
      ],
      createdRow: function(row, data) {
        var kat = (data[7] || '').toLowerCase();
        if (kat.includes('special'))  $(row).css('background','#f3e5f5');
        else if (kat.includes('1 sku'))    $(row).css('background','#f1f8f6');
        else if (kat.includes('2-9')) $(row).css('background','#fffde7');
        else if (kat.includes('banyak')) $(row).css('background','#fff3e0');
      },
      columnDefs: [
        {
          targets: 4,
          render: function(d) {
            return '<span class="pk pk-purple">' + d + '</span>';
          }
        },
        {
          targets: 7,
          render: function(d) {
            if (!d) return '-';
            var cls = 'cat-banyak';
            var lo = d.toLowerCase();
            if (lo.includes('special')) cls = 'cat-spec';
            else if (lo.includes('1 sku')) cls = 'cat-1sku';
            else if (lo.includes('2-9')) cls = 'cat-29sku';
            return '<span class="cat-badge ' + cls + '">' + d + '</span>';
          }
        },
        {
          targets: 8,
          render: function(d) {
            if (!d) return '-';
            var cls = 's-normal';
            var lo = (d || '').toLowerCase();
            if (lo.includes('batal') || lo.includes('cancel')) cls = 's-batal';
            else if (lo.includes('pending')) cls = 's-pending';
            return '<span class="status-b ' + cls + '">' + d + '</span>';
          }
        }
      ],
      drawCallback: function() {
        // Category totals are computed server-side for accuracy and displayed directly
      }
    });
    <?php endif; ?>

    $('#btn-search').on('click', function() {
      $('#form-report-sku-special').removeAttr('target').removeClass('nojs')
        .attr('action', '<?= site_url("report/sku-special-report") ?>');
    });

    $('#btn-export-excel').on('click', function() {
      $('#form-report-sku-special').attr('target','_blank').addClass('nojs')
        .attr('action', '<?= site_url("report/export-to-excel-sku-special-report") ?>');
    });
  }

  checkDeps();
})();
</script>
