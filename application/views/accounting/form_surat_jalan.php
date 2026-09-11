<style>
  /* Premium Spreadsheet and Dashboard Styles */
  .accounting-dashboard {
    font-family: 'Inter', 'Outfit', 'Open Sans', sans-serif;
    padding: 15px;
  }
  
  /* KPI Summaries */
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
  }
  .kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
  }
  .kpi-card .card-info h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .kpi-card .card-info .card-value {
    font-size: 28px;
    font-weight: 700;
    margin-top: 5px;
    color: #111827;
  }
  .kpi-card .card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  
  /* HSL Tailored KPI Color Themes */
  .kpi-yellow {
    border-left: 5px solid #eab308;
  }
  .kpi-yellow .card-icon {
    background: #fef9c3;
    color: #ca8a04;
  }
  .kpi-green {
    border-left: 5px solid #22c55e;
  }
  .kpi-green .card-icon {
    background: #dcfce7;
    color: #16a34a;
  }
  .kpi-orange {
    border-left: 5px solid #f97316;
  }
  .kpi-orange .card-icon {
    background: #ffedd5;
    color: #ea580c;
  }
  .kpi-blue {
    border-left: 5px solid #3b82f6;
  }
  .kpi-blue .card-icon {
    background: #dbeafe;
    color: #2563eb;
  }

  /* Control Panel */
  .control-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 15px 20px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
  }

  /* Excel Spreadsheet Table Styles */
  .spreadsheet-container {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }
  .spreadsheet-wrapper {
    overflow-x: auto;
    max-height: 500px;
  }
  .excel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    table-layout: auto;
  }
  .excel-table th {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #d1d5db;
    padding: 8px 6px;
    white-space: nowrap;
    text-transform: uppercase;
    font-size: 10px;
  }
  /* Warna header per kelompok kolom */
  .excel-table th.g-info    { background:#e0f2fe; }
  .excel-table th.g-restock { background:#dcfce7; }
  .excel-table th.g-stok    { background:#fef3c7; }
  .excel-table th.g-sj      { background:#ede9fe; }
  .excel-table th.g-rvjb    { background:#cffafe; }
  .excel-table th.g-hasil   { background:#fee2e2; }
  /* Kolom Real: input utama saat closing -> lebih besar & tebal */
  .excel-input.input-real { font-size:14px; font-weight:800; color:#0f172a; background:#fffbeb; }
  .excel-input.input-real:focus { background:#fff; }
  .excel-table td {
    border: 1px solid #e5e7eb;
    padding: 2px;
    vertical-align: middle;
    background: #fff;
    transition: background-color 0.15s;
  }
  .excel-table tr:hover td {
    background-color: #f3f4f6;
  }
  
  /* Input Fields inside Spreadsheet */
  .excel-input {
    width: 100%;
    border: 1px solid transparent;
    background: transparent;
    padding: 5px 6px;
    font-size: 11px;
    color: #1f2937;
    border-radius: 4px;
    transition: all 0.15s;
    font-family: inherit;
  }
  .excel-input:focus {
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    outline: none;
  }
  .excel-input[readonly] {
    background-color: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
  }
  
  /* Specific Input Types */
  select.excel-input {
    padding: 4px;
    cursor: pointer;
  }
  input[type="date"].excel-input {
    padding: 3px 4px;
  }
  
  /* Highlighting Columns matching image color patterns */
  .col-highlight-green {
    background-color: #ecfdf5 !important;
  }
  .col-highlight-green:focus {
    background-color: #ffffff !important;
  }
  
  /* Action button styling */
  .btn-table-action {
    padding: 4px 6px;
    font-size: 11px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s;
  }
  .btn-save-row {
    background: #2563eb;
    color: #ffffff;
  }
  .btn-save-row:hover {
    background: #1d4ed8;
  }
  .btn-delete-row {
    background: #ef4444;
    color: #ffffff;
  }
  .btn-delete-row:hover {
    background: #dc2626;
  }
  
  /* Autocomplete Styling Fixes */
  .ui-autocomplete {
    z-index: 99999 !important;
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
    font-size: 12px;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    border: 1px solid #d1d5db;
    background: #fff;
  }
  .ui-menu-item-wrapper {
    padding: 6px 10px !important;
  }
  .ui-state-active, .ui-widget-content .ui-state-active {
    background: #2563eb !important;
    color: #fff !important;
    border: 1px solid #2563eb !important;
  }

  /* Excel-like cell selection (copy / select down / paste) */
  .excel-input.cell-selected {
    background-color: #dbeafe !important;
    box-shadow: inset 0 0 0 1px #3b82f6;
  }
  .excel-input.cell-active {
    box-shadow: inset 0 0 0 2px #2563eb;
  }
  .excel-table td.no-select {
    -webkit-user-select: none;
    user-select: none;
  }

  /* Document status badge */
  .doc-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    background: #f3f4f6;
    color: #6b7280;
  }
  .doc-status-badge.status-DRAFT   { background:#fef9c3; color:#ca8a04; }
  .doc-status-badge.status-TERKIRIM{ background:#dbeafe; color:#2563eb; }
  .doc-status-badge.status-SELESAI { background:#dcfce7; color:#16a34a; }

  /* Simple modal */
  .sj-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(17,24,39,0.55);
    z-index: 100000;
    align-items: center;
    justify-content: center;
  }
  .sj-modal-overlay.show { display: flex; }
  .sj-modal {
    background: #fff;
    width: 420px;
    max-width: 92vw;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
  }
  .sj-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
  }
  .sj-modal-close { cursor: pointer; font-size: 22px; color: #9ca3af; line-height: 1; }
  .sj-modal-close:hover { color: #ef4444; }
  .sj-modal-body { padding: 18px 20px; }
  .sj-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px;
    border-top: 1px solid #f3f4f6;
    background: #f9fafb;
  }
  .sj-field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 5px;
  }
</style>

<div class="accounting-dashboard">
  
  <!-- SUMMARY CARDS (KPI TOP PANEL) -->
  <div class="kpi-row">
    <div class="kpi-card kpi-yellow">
      <div class="card-info">
        <h3>DATABASE ROWS</h3>
        <div class="card-value" id="kpi-total-rows">0</div>
      </div>
      <div class="card-icon">
        <i class="fa fa-database"></i>
      </div>
    </div>
    
    <div class="kpi-card kpi-green">
      <div class="card-info">
        <h3>TTL SKU RS</h3>
        <div class="card-value" id="kpi-total-skus">0</div>
      </div>
      <div class="card-icon">
        <i class="fa fa-tags"></i>
      </div>
    </div>
    
    <div class="kpi-card kpi-orange">
      <div class="card-info">
        <h3>TTL QTY RS</h3>
        <div class="card-value" id="kpi-total-qty">0</div>
      </div>
      <div class="card-icon">
        <i class="fa fa-cubes"></i>
      </div>
    </div>

    <div class="kpi-card kpi-blue">
      <div class="card-info">
        <h3>SELISIH JUBELIO</h3>
        <div class="card-value" id="kpi-total-selisih">0</div>
      </div>
      <div class="card-icon">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
    </div>
  </div>

  <!-- CONTROL PANEL - DOKUMEN -->
  <div class="control-card">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
      <label style="font-weight: 600; font-size: 12px; color: #4b5563; margin: 0;">Periode:</label>
      <input type="text" id="filter-daterange" class="form-control" style="width: 240px; display: inline-block; height: 34px;" autocomplete="off" />
      <label style="font-weight: 600; font-size: 12px; color: #4b5563; margin: 0 0 0 8px;">Buka Dokumen:</label>
      <select id="doc-select" class="form-control" style="min-width: 240px; display: inline-block; height: 34px;">
        <option value="">-- Pilih Surat Jalan --</option>
      </select>
    </div>

    <div style="display: flex; gap: 8px;">
      <button type="button" class="btn btn-success" id="btn-new-doc"><i class="fa fa-plus-circle"></i> Buat Surat Jalan Baru</button>
    </div>
  </div>

  <!-- ACTIVE DOC BAR (muncul saat ada dokumen aktif) -->
  <div class="control-card" id="active-doc-bar" style="display:none;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <span style="font-weight:700; font-size:14px; color:#111827;" id="active-doc-nosj">-</span>
      <span class="doc-status-badge" id="active-doc-status">DRAFT</span>
      <span style="font-size:12px; color:#6b7280;" id="active-doc-meta"></span>
      <span style="display:inline-flex; align-items:center; gap:6px;">
        <label style="font-size:12px; font-weight:600; color:#4b5563; margin:0;">No. Trf Jubelio:</label>
        <input type="text" id="active-doc-notrf" class="form-control" placeholder="isi di akhir..." style="width:160px; height:30px; display:inline-block; font-size:12px;">
        <button type="button" class="btn btn-xs btn-primary" id="btn-save-notrf" title="Simpan No. Trf"><i class="fa fa-check"></i></button>
      </span>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <button type="button" class="btn btn-warning" id="btn-upload-excel"><i class="fa fa-upload"></i> Upload Excel Persediaan</button>
      <button type="button" class="btn btn-primary" id="btn-upload-trf"><i class="fa fa-download"></i> Upload Transfer Jubelio</button>
      <button type="button" class="btn btn-default" id="btn-reload"><i class="fa fa-refresh"></i> Muat Ulang</button>
      <button type="button" class="btn btn-success" id="btn-add-row"><i class="fa fa-plus"></i> Tambah Baris</button>
      <button type="button" class="btn btn-primary" id="btn-save-all"><i class="fa fa-save"></i> Simpan Semua</button>
      <button type="button" class="btn btn-info" id="btn-print-sj"><i class="fa fa-print"></i> Cetak Surat Jalan</button>
      <button type="button" class="btn btn-danger" id="btn-kirim-inbound"><i class="fa fa-paper-plane"></i> Kirim ke Inbound</button>
      <input type="file" id="bundleFileInput" accept=".xls,.xlsx" style="display:none;">
      <input type="file" id="trfFileInput" accept=".xls,.xlsx" multiple style="display:none;">
    </div>
  </div>

  <!-- SPREADSHEET CONTAINER -->
  <div class="spreadsheet-container">
    <div class="spreadsheet-wrapper">
      <table class="excel-table" id="spreadsheet-table">
        <thead>
          <!-- Header Level 1 -->
          <tr>
            <th rowspan="2" style="width: 40px;">No</th>
            <th rowspan="2" class="g-info" style="min-width: 110px;">Tgl</th>
            <th rowspan="2" class="g-info" style="min-width: 120px;">Jenis SJ</th>
            <th rowspan="2" class="g-info" style="min-width: 140px;">No. Trf Jubelio</th>
            <th rowspan="2" style="min-width: 120px;">SKU</th>
            <th colspan="2" class="g-restock">Qty Re Stock</th>
            <th colspan="2" class="g-stok">Qty Jubelio</th>
            <th colspan="2" class="g-sj">SJ - Jubelio</th>
            <th colspan="2" class="g-rvjb">Real vs JB</th>
            <th rowspan="2" class="g-hasil" style="min-width: 70px;">Selisih</th>
            <th rowspan="2" class="g-hasil" style="min-width: 160px;">Action In Jubelio</th>
            <th rowspan="2" style="width: 100px;">Aksi</th>
          </tr>
          <!-- Header Level 2 -->
          <tr>
            <th class="g-restock" style="min-width: 60px;">Rqst</th>
            <th class="g-restock" style="min-width: 60px;">Real</th>
            <th class="g-stok" style="min-width: 60px;">Disp</th>
            <th class="g-stok" style="min-width: 60px;">Gd</th>
            <th class="g-sj" style="min-width: 110px;">SKU</th>
            <th class="g-sj" style="min-width: 60px;">Qty</th>
            <th class="g-rvjb" style="width: 46px;">SKU</th>
            <th class="g-rvjb" style="width: 46px;">Qty</th>
          </tr>
        </thead>
        <tbody id="spreadsheet-body">
          <!-- Rows will be populated via jQuery AJAX -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- MODAL BUAT SURAT JALAN BARU -->
  <div id="sj-modal-overlay" class="sj-modal-overlay">
    <div class="sj-modal">
      <div class="sj-modal-header">
        <h4 style="margin:0; font-size:16px; font-weight:700;">Buat Surat Jalan Baru</h4>
        <span class="sj-modal-close" id="sj-modal-close">&times;</span>
      </div>
      <div class="sj-modal-body">
        <label class="sj-field-label">Tanggal</label>
        <input type="date" id="new-doc-tgl" class="form-control" style="height:36px;">
        <label class="sj-field-label" style="margin-top:12px;">Jenis SJ <span style="color:#9ca3af;">(ketik manual)</span></label>
        <input type="text" id="new-doc-jenis" class="form-control" placeholder="Contoh: RESTOCK DISPLAY" style="height:36px;">
        <p style="margin-top:12px; font-size:11px; color:#9ca3af;"><i class="fa fa-info-circle"></i> No. Trf Jubelio diisi nanti di akhir (di bar dokumen).</p>
      </div>
      <div class="sj-modal-footer">
        <button type="button" class="btn btn-default" id="sj-modal-cancel">Batal</button>
        <button type="button" class="btn btn-success" id="sj-modal-save"><i class="fa fa-check"></i> Buat & Lanjut</button>
      </div>
    </div>
  </div>

</div>

<!-- JAVASCRIPT FOR DYNAMIC SPREADSHEET CRUD -->
<script type="text/javascript">
  $(document).ready(function() {
    
    // Dokumen Surat Jalan yang sedang aktif
    var currentDocId = null;

    // Rentang tanggal untuk memfilter daftar dokumen (default: bulan ini)
    var start = moment().startOf('month');
    var end = moment().endOf('month');

    $('#filter-daterange').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        startDate: start,
        endDate: end,
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss',
            cancelLabel: 'Clear'
        }
    });

    $('#filter-daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
        loadDocList();
    });

    // Initial: muat daftar dokumen (tabel item kosong sampai dokumen dipilih)
    $('#filter-daterange').val(start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss'));

    // Dokumen yang diminta dibuka dari halaman Daftar Surat Jalan (?doc=ID)
    var openDocId = <?= isset($open_doc) && $open_doc ? json_encode((string)$open_doc) : 'null' ?>;
    if (openDocId) {
      loadDocList(openDocId);
      openDoc(openDocId);
    } else {
      loadDocList();
      renderEmptyState();
    }

    // Reload item dokumen aktif
    $('#btn-reload').click(function() {
      if (currentDocId) loadDocItems(currentDocId);
    });

    // Ganti dokumen dari dropdown
    $('#doc-select').on('change', function() {
      var id = $(this).val();
      if (id) {
        openDoc(id);
      } else {
        currentDocId = null;
        $('#active-doc-bar').hide();
        renderEmptyState();
      }
    });

    // Auto-calculate logic on input change
    $('#spreadsheet-table').on('input', '.input-rqst, .input-real, .input-sj-qty, .input-sj-sku, .input-sku', function() {
      var row = $(this).closest('tr');
      calculateRow(row);
      row.addClass('row-dirty'); // mark row as changed
    });

    $('#spreadsheet-table').on('change', 'input, select', function() {
      $(this).closest('tr').addClass('row-dirty');
    });

    // ---- Daftar dokumen (dropdown "Buka Dokumen") ----
    function loadDocList(selectId) {
      $.ajax({
        url: '<?= base_url("accounting/list-surat-jalan-docs") ?>',
        method: 'POST',
        data: { reportrange: $('#filter-daterange').val() },
        dataType: 'json',
        success: function(res) {
          if (res.status !== 'success') return;
          var $sel = $('#doc-select');
          $sel.empty().append('<option value="">-- Pilih Surat Jalan --</option>');
          $.each(res.data, function(i, d) {
            var label = d.no_sj + '  |  ' + (d.tgl || '') + '  |  ' + (d.jenis_sj || '-') +
                        '  (' + d.total_item + ' item, ' + d.status + ')';
            $sel.append('<option value="' + d.id + '">' + label + '</option>');
          });
          if (selectId) $sel.val(selectId);
        }
      });
    }

    // ---- Buka satu dokumen ----
    function openDoc(id) {
      currentDocId = id;
      loadDocItems(id);
    }

    function loadDocItems(id) {
      $.ajax({
        url: '<?= base_url("accounting/get-surat-jalan-doc-items") ?>/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            updateActiveDocBar(res.doc);
            renderRows(res.data);
            updateSummary(res.summary);
            $('#doc-select').val(id);
          } else {
            showNoty('Gagal memuat dokumen: ' + (res.message || 'Error'), 'error');
          }
        },
        error: function() {
          showNoty('Terjadi kesalahan koneksi server', 'error');
        }
      });
    }

    function updateActiveDocBar(doc) {
      if (!doc) { $('#active-doc-bar').hide(); return; }
      $('#active-doc-nosj').text(doc.no_sj || '-');
      $('#active-doc-status')
        .text(doc.status || 'DRAFT')
        .attr('class', 'doc-status-badge status-' + (doc.status || 'DRAFT'));
      var meta = 'Tgl: ' + (doc.tgl || '-') + ' | Jenis: ' + (doc.jenis_sj || '-');
      $('#active-doc-meta').text(meta);
      $('#active-doc-notrf').val(doc.no_trf_jubelio || '');
      $('#active-doc-bar').css('display', 'flex');
    }

    function renderEmptyState() {
      $('#spreadsheet-body').empty().append(
        '<tr class="no-data-row"><td colspan="16" class="text-center" style="padding:24px; color:#9ca3af; font-size:12px;">' +
        'Pilih dokumen pada "Buka Dokumen" atau klik "Buat Surat Jalan Baru" untuk memulai.</td></tr>'
      );
      $('#kpi-total-rows').text(0);
      $('#kpi-total-skus').text(0);
      $('#kpi-total-qty').text(0);
      $('#kpi-total-selisih').text(0);
    }

    // ================= MODAL BUAT DOKUMEN =================
    $('#btn-new-doc').click(function() {
      $('#new-doc-tgl').val(moment().format('YYYY-MM-DD'));
      $('#new-doc-jenis').val('');
      $('#sj-modal-overlay').addClass('show');
    });
    function closeModal() { $('#sj-modal-overlay').removeClass('show'); }
    $('#sj-modal-close, #sj-modal-cancel').click(closeModal);
    $('#sj-modal-overlay').click(function(e){ if (e.target === this) closeModal(); });

    $('#sj-modal-save').click(function() {
      var tgl = $('#new-doc-tgl').val();
      if (!tgl) { showNoty('Tanggal wajib diisi', 'warning'); return; }
      var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Membuat...');
      $.ajax({
        url: '<?= base_url("accounting/create-surat-jalan-doc") ?>',
        method: 'POST',
        data: {
          tgl: tgl,
          jenis_sj: $('#new-doc-jenis').val()
        },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            closeModal();
            showNoty('Surat Jalan ' + res.data.no_sj + ' dibuat. Silakan upload excel persediaan.', 'success');
            loadDocList(res.data.id);
            openDoc(res.data.id);
          } else {
            showNoty('Gagal membuat dokumen: ' + (res.message || 'Error'), 'error');
          }
        },
        error: function() { showNoty('Terjadi kesalahan koneksi server', 'error'); },
        complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-check"></i> Buat & Lanjut'); }
      });
    });

    // ================= SIMPAN NO. TRF JUBELIO (diisi di akhir) =================
    function saveNoTrf() {
      if (!currentDocId) { showNoty('Pilih/buat dokumen dahulu', 'warning'); return; }
      var val = $('#active-doc-notrf').val();
      $.ajax({
        url: '<?= base_url("accounting/update-surat-jalan-notrf") ?>',
        method: 'POST',
        data: { id_doc: currentDocId, no_trf_jubelio: val },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            showNoty('No. Trf Jubelio disimpan', 'success');
            // perbarui kolom No. Trf tiap baris tanpa reload penuh
            $('#spreadsheet-body tr').not('.no-data-row').find('.input-no-trf').val(val);
          } else {
            showNoty(res.message || 'Gagal menyimpan No. Trf', 'error');
          }
        },
        error: function() { showNoty('Terjadi kesalahan koneksi server', 'error'); }
      });
    }
    $('#btn-save-notrf').click(saveNoTrf);
    $('#active-doc-notrf').on('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); saveNoTrf(); } });

    // ================= UPLOAD EXCEL PERSEDIAAN =================
    $('#btn-upload-excel').click(function() {
      if (!currentDocId) { showNoty('Pilih/buat dokumen terlebih dahulu', 'warning'); return; }
      $('#bundleFileInput').val('').click();
    });

    $('#bundleFileInput').on('change', function() {
      if (!this.files || !this.files.length) return;
      if (!currentDocId) { showNoty('Dokumen belum aktif', 'warning'); return; }

      var fd = new FormData();
      fd.append('id_doc', currentDocId);
      fd.append('bundleFile', this.files[0]);

      var $btn = $('#btn-upload-excel').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
      $.ajax({
        url: '<?= base_url("accounting/upload-bundle-persediaan") ?>',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            showNoty(res.message, 'success');
            loadDocItems(currentDocId);
            loadDocList(currentDocId);
          } else {
            showNoty('Gagal upload: ' + (res.message || 'Error'), 'error');
          }
        },
        error: function() { showNoty('Terjadi kesalahan saat upload', 'error'); },
        complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Excel Persediaan'); }
      });
    });

    // ================= UPLOAD TRANSFER JUBELIO (auto-match) =================
    $('#btn-upload-trf').click(function() { $('#trfFileInput').val('').click(); });
    $('#trfFileInput').on('change', function() {
      if (!this.files || !this.files.length) return;
      var fd = new FormData();
      for (var i = 0; i < this.files.length; i++) fd.append('trfFiles[]', this.files[i]);
      var $btn = $('#btn-upload-trf').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mencocokkan...');
      $.ajax({
        url: '<?= base_url("accounting/upload-transfer-jubelio") ?>',
        method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            var t = res.total || {};
            var lines = ['Cocok: ' + (t.matched||0) + ' baris dari ' + (t.files||0) + ' file.'];
            if (t.unmatched_sj) lines.push(t.unmatched_sj + ' SKU di SJ tak ada di transfer.');
            if (t.extra_jb) lines.push(t.extra_jb + ' SKU transfer tak ada di SJ.');
            showNoty(lines.join(' '), (t.unmatched_sj||t.extra_jb) ? 'warning' : 'success');
            if (currentDocId) loadDocItems(currentDocId);
          } else { showNoty('Gagal: ' + (res.message || 'Error'), 'error'); }
        },
        error: function() { showNoty('Terjadi kesalahan saat upload', 'error'); },
        complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-download"></i> Upload Transfer Jubelio'); }
      });
    });

    // ================= CETAK SURAT JALAN (format PDF) =================
    // Simpan dulu perubahan yang belum tersimpan agar hasil cetak = isi tabel,
    // baru buka halaman cetak.
    $('#btn-print-sj').click(function() {
      if (!currentDocId) { showNoty('Pilih/buat dokumen dahulu', 'warning'); return; }

      var printUrl = '<?= base_url("accounting/surat-jalan-print") ?>/' + currentDocId;
      var dirtyRows = $('#spreadsheet-body tr.row-dirty').not('.no-data-row');

      if (dirtyRows.length === 0) {
        window.open(printUrl, '_blank');
        return;
      }

      // Buka tab sekarang (dalam gesture klik) agar tidak diblok popup blocker,
      // lalu arahkan ke halaman cetak setelah semua tersimpan.
      var win = window.open('', '_blank');
      if (win) { win.document.write('<p style="font-family:sans-serif;padding:24px;">Menyimpan & menyiapkan cetakan…</p>'); }

      var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
      var promises = [];
      dirtyRows.each(function() { promises.push(saveRow($(this), false)); });

      $.when.apply($, promises).always(function() {
        $btn.prop('disabled', false).html('<i class="fa fa-print"></i> Cetak Surat Jalan');
        recalculateTopSummary();
        if (win) { win.location.href = printUrl; } else { window.open(printUrl, '_blank'); }
      });
    });

    // ================= KIRIM KE INBOUND =================
    $('#btn-kirim-inbound').click(function() {
      if (!currentDocId) { showNoty('Pilih/buat dokumen dahulu', 'warning'); return; }
      if (!confirm('Kirim Surat Jalan ini ke Tim Inbound? Status akan menjadi TERKIRIM.')) return;

      var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');
      $.ajax({
        url: '<?= base_url("accounting/kirim-surat-jalan-inbound") ?>',
        method: 'POST',
        data: { id_doc: currentDocId },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            showNoty(res.message, 'success');
            loadDocItems(currentDocId);
            loadDocList(currentDocId);
          } else {
            showNoty(res.message || 'Gagal mengirim', 'error');
          }
        },
        error: function() { showNoty('Terjadi kesalahan koneksi server', 'error'); },
        complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Kirim ke Inbound'); }
      });
    });

    // Render database rows to spreadsheet.
    // Jumlah baris = jumlah SKU (item). Tidak ada baris kosong otomatis;
    // gunakan tombol "Tambah Baris" untuk menambah manual.
    function renderRows(items) {
      var body = $('#spreadsheet-body');
      body.empty();

      if (!items || items.length === 0) {
        body.append(
          '<tr class="no-data-row"><td colspan="16" class="text-center" style="padding:24px; color:#9ca3af; font-size:12px;">' +
          'Belum ada item. Klik "Upload Excel Persediaan" untuk menarik SKU display minus, atau "Tambah Baris" untuk input manual.</td></tr>'
        );
        return;
      }

      $.each(items, function(idx, item) {
        body.append(createRowHtml(item, idx + 1));
      });

      // Initialize autocomplete on all rows
      initRowAutocomplete();

      // Hitung ulang tampilan selisih/flag/action tiap baris (REAL BLM / NBP / dst)
      $('#spreadsheet-body tr').not('.no-data-row').each(function(){ calculateRow($(this)); });
    }

    // Update KPI panels on top
    function updateSummary(summary) {
      $('#kpi-total-rows').text(summary.total_rows);
      $('#kpi-total-skus').text(summary.total_skus);
      $('#kpi-total-qty').text(summary.total_qty_restock.toLocaleString('id-ID'));
      
      // Calculate total selisih from the currently rendered elements
      var totalSelisih = 0;
      $('.input-selisih').each(function() {
        totalSelisih += parseInt($(this).val()) || 0;
      });
      $('#kpi-total-selisih').text(totalSelisih.toLocaleString('id-ID'));
    }

    // Create HTML template for a row
    function createRowHtml(item, rowNum) {
      item = item || {};
      var id = item.id || '';
      var tgl = item.tgl || moment().format('YYYY-MM-DD');
      var jenis_sj = item.jenis_sj || '';
      var no_trf = item.no_trf_jubelio || '';
      var sku = item.sku || '';
      var rqst = item.qty_restock_rqst || 0;
      // Real boleh kosong (NULL) => "REAL BLM"; jangan paksa jadi 0.
      var real = (item.qty_restock_real === null || typeof item.qty_restock_real === 'undefined') ? '' : item.qty_restock_real;
      var disp = item.qty_jubelio_disp || 0;
      var gd = item.qty_jubelio_gd || 0;
      var sj_sku = item.sj_jubelio_sku || '';
      var sj_qty = item.sj_jubelio_qty || 0;
      var rv_sku = item.real_vs_jb_sku || '';
      var rv_qty = item.real_vs_jb_qty || 0;
      var selisih = item.selisih || 0;
      var action = item.action_in_jubelio || '';

      var isNewClass = id === '' ? 'row-new' : '';

      return '<tr data-id="' + id + '" class="' + isNewClass + '">' +
        '<td class="text-center row-number" style="font-weight:600; color:#6b7280;">' + rowNum + '</td>' +
        '<td><input type="date" class="excel-input input-tgl" value="' + tgl + '"></td>' +
        '<td><input type="text" class="excel-input input-jenis-sj" value="' + jenis_sj + '" placeholder="Jenis SJ..."></td>' +
        '<td><input type="text" class="excel-input input-no-trf" value="' + no_trf + '" placeholder="No. Transfer"></td>' +
        '<td><input type="text" class="excel-input input-sku sku-autocomplete" value="' + sku + '" placeholder="Ketik SKU..."></td>' +
        '<td><input type="number" class="excel-input input-rqst text-right" value="' + rqst + '" min="0"></td>' +
        '<td><input type="number" class="excel-input input-real text-right col-highlight-green" value="' + real + '" min="0"></td>' +
        '<td><input type="number" class="excel-input input-disp text-right" value="' + disp + '" min="0"></td>' +
        '<td><input type="number" class="excel-input input-gd text-right" value="' + gd + '" min="0"></td>' +
        '<td><input type="text" class="excel-input input-sj-sku sku-autocomplete" value="' + sj_sku + '" placeholder="SKU Jubelio"></td>' +
        '<td><input type="number" class="excel-input input-sj-qty text-right" value="' + sj_qty + '" min="0"></td>' +
        '<td><input type="text" class="excel-input input-real-vs-jb-sku text-center" value="' + rv_sku + '" readonly title="1 = SKU transfer beda" style="width:42px; font-weight:600;"></td>' +
        '<td><input type="number" class="excel-input input-real-vs-jb-qty text-center" value="' + rv_qty + '" readonly title="1 = Real vs Qty Jubelio beda" style="width:42px; font-weight:600;"></td>' +
        '<td><input type="text" class="excel-input input-selisih text-right" value="' + selisih + '" readonly style="font-weight:600; color:' + (selisih !== 0 ? '#ef4444' : '#10b981') + ';"></td>' +
        '<td><input type="text" class="excel-input input-action" value="' + action + '" placeholder="Action note..."></td>' +
        '<td class="text-center" style="white-space:nowrap;">' +
          '<button type="button" class="btn-table-action btn-save-row" title="Simpan Baris Ini"><i class="fa fa-save"></i></button>&nbsp;' +
          '<button type="button" class="btn-table-action btn-delete-row" title="Hapus Baris Ini"><i class="fa fa-trash-o"></i></button>' +
        '</td>' +
      '</tr>';
    }

    // Initialize Autocomplete on SKU inputs
    function initRowAutocomplete(context) {
      var selector = context ? context.find('.sku-autocomplete') : $('.sku-autocomplete');
      selector.autocomplete({
        source: '<?= base_url("accounting/get-sku-autocomplete") ?>',
        minLength: 1,
        select: function(event, ui) {
          $(this).val(ui.item.value);
          var $tr = $(this).closest('tr');
          $tr.addClass('row-dirty');
          calculateRow($tr);
          return false;
        }
      });
    }

    // Teks Action baku (harus sama dgn backend _recon_values).
    var SJ_ACT_LEBIH  = 'LEBIH ISSUE - TRF DARI DISP KE GUDANG';
    var SJ_ACT_KURANG = 'KURANG ISSUE - TRF DARI GUDANG KE DISP';
    var SJ_ACT_NBP    = 'NOL BISA PESAN => PENYESUAIAN STOCK';

    // Live Math Calculation for Row (ikut Excel HARI INI: NBP / REAL BLM / LEBIH / KURANG / klop)
    function calculateRow(row) {
      var gd      = parseInt(row.find('.input-gd').val()) || 0;
      var realStr = (row.find('.input-real').val() || '').trim();
      var realBlank = (realStr === '');
      var sj_qty  = parseInt(row.find('.input-sj-qty').val()) || 0;

      var $sel = row.find('.input-selisih');
      var $fq  = row.find('.input-real-vs-jb-qty');
      var $fs  = row.find('.input-real-vs-jb-sku');
      var $act = row.find('.input-action');

      var selDisp, qFlag, sFlag, autoAct, selColor;
      if (gd === 0) {                     // NBP
        selDisp = '0'; qFlag = 0; sFlag = 0; autoAct = SJ_ACT_NBP; selColor = '#10b981';
      } else if (realBlank) {             // Real belum diisi
        selDisp = 'REAL BLM'; qFlag = 0; sFlag = 0; autoAct = ''; selColor = '#6b7280';
      } else {
        var sel = sj_qty - (parseInt(realStr) || 0);
        var sku = (row.find('.input-sku').val() || '').trim();
        var sjSku = (row.find('.input-sj-sku').val() || '').trim();
        selDisp = String(sel);
        qFlag = (sel !== 0) ? 1 : 0;
        sFlag = (sjSku !== '' && sjSku !== sku) ? 1 : 0;
        autoAct = sel > 0 ? SJ_ACT_LEBIH : (sel < 0 ? SJ_ACT_KURANG : '');
        selColor = (sel !== 0) ? '#ef4444' : '#10b981';
      }

      $sel.val(selDisp).css('color', selColor);
      $fq.val(qFlag).css('color', qFlag ? '#ef4444' : '#10b981');
      $fs.val(sFlag).css('color', sFlag ? '#ef4444' : '#10b981');

      // Action: timpa hanya bila kosong / masih teks auto (bukan catatan manual).
      var cur = ($act.val() || '').trim();
      if (cur === '' || cur === SJ_ACT_LEBIH || cur === SJ_ACT_KURANG || cur === SJ_ACT_NBP || cur === 'KLOP') {
        $act.val(autoAct);
      }

      recalculateTopSummary();
    }

    // Recalculate summary boxes dynamically on input
    function recalculateTopSummary() {
      var totalRows = 0;
      var uniqueSkus = {};
      var totalQtyRestock = 0;
      var totalSelisih = 0;

      $('#spreadsheet-body tr').not('.no-data-row').each(function() {
        var $row = $(this);
        // Hanya hitung baris yang berisi data (abaikan baris kosong template)
        if (isRowEmpty($row)) return;
        totalRows++;
        var skuVal = $row.find('.input-sku').val();
        if (skuVal && skuVal.trim() !== '') {
          uniqueSkus[skuVal.trim()] = true;
        }
        totalQtyRestock += parseInt($row.find('.input-real').val()) || 0;
        totalSelisih += parseInt($row.find('.input-selisih').val()) || 0;
      });

      $('#kpi-total-rows').text(totalRows);
      $('#kpi-total-skus').text(Object.keys(uniqueSkus).length);
      $('#kpi-total-qty').text(totalQtyRestock.toLocaleString('id-ID'));
      $('#kpi-total-selisih').text(totalSelisih.toLocaleString('id-ID'));
    }

    // Add Row button action
    $('#btn-add-row').click(function() {
      if (!currentDocId) { showNoty('Buat/pilih dokumen dahulu', 'warning'); return; }
      // Remove "no data" row if it exists
      $('.no-data-row').remove();

      var body = $('#spreadsheet-body');
      var rowNum = body.find('tr').length + 1;
      
      var newRowHtml = createRowHtml(null, rowNum);
      var newRow = $(newRowHtml);
      
      body.append(newRow);
      
      // Init autocomplete on the new inputs
      initRowAutocomplete(newRow);
      
      // Recalculate totals
      recalculateTopSummary();

      // Scroll spreadsheet container to bottom to show the new row
      var wrapper = $('.spreadsheet-wrapper');
      wrapper.animate({ scrollTop: wrapper[0].scrollHeight }, 300);
    });

    // Save single row button click
    $('#spreadsheet-table').on('click', '.btn-save-row', function() {
      var row = $(this).closest('tr');
      saveRow(row, true);
    });

    // Delete single row button click
    $('#spreadsheet-table').on('click', '.btn-delete-row', function() {
      var row = $(this).closest('tr');
      var id = row.data('id');
      
      if (!id) {
        // Row is new and not saved to DB yet, just remove from UI
        row.remove();
        renumberRows();
        recalculateTopSummary();
        showNoty('Baris kosong berhasil dihapus', 'success');
        return;
      }

      if (confirm('Apakah Anda yakin ingin menghapus data ini dari database?')) {
        $.ajax({
          url: '<?= base_url("accounting/delete-surat-jalan-item") ?>',
          method: 'POST',
          data: { id: id },
          dataType: 'json',
          success: function(res) {
            if (res.status === 'success') {
              row.remove();
              renumberRows();
              recalculateTopSummary();
              showNoty('Data berhasil dihapus dari database', 'success');
            } else {
              showNoty('Gagal menghapus data: ' + (res.message || 'Error'), 'error');
            }
          },
          error: function() {
            showNoty('Terjadi kesalahan koneksi server', 'error');
          }
        });
      }
    });

    // Renumber rows order numbers
    function renumberRows() {
      var body = $('#spreadsheet-body');
      body.find('tr').not('.no-data-row').each(function(idx) {
        $(this).find('.row-number').text(idx + 1);
      });
      // Kalau tidak ada baris tersisa, tampilkan pesan kosong
      if (body.find('tr').not('.no-data-row').length === 0) {
        renderRows([]);
      }
    }

    // Cek apakah sebuah baris benar-benar kosong (tidak ada input teks/angka yang berarti)
    function isRowEmpty(row) {
      var filled = false;
      row.find('input.excel-input').each(function() {
        var $inp = $(this);
        // Abaikan kolom kalkulasi otomatis (readonly: selisih) dan tanggal (default hari ini)
        if ($inp.prop('readonly') || $inp.attr('type') === 'date') return;
        var val = ($inp.val() || '').toString().trim();
        if ($inp.attr('type') === 'number') {
          if (val !== '' && parseInt(val) !== 0) filled = true;
        } else {
          if (val !== '') filled = true;
        }
      });
      return !filled;
    }

    // Save row via AJAX
    function saveRow(row, showSuccessNoty) {
      // Lewati baris kosong yang belum tersimpan (baris template otomatis)
      if (!row.data('id') && isRowEmpty(row)) {
        if (showSuccessNoty) {
          showNoty('Baris masih kosong, tidak ada yang disimpan', 'information');
        }
        return $.Deferred().resolve().promise();
      }

      var id = row.data('id');
      var tgl = row.find('.input-tgl').val();
      var jenis_sj = row.find('.input-jenis-sj').val();
      var no_trf = row.find('.input-no-trf').val();
      var sku = row.find('.input-sku').val();
      var rqst = row.find('.input-rqst').val();
      var real = row.find('.input-real').val();
      var disp = row.find('.input-disp').val();
      var gd = row.find('.input-gd').val();
      var sj_sku = row.find('.input-sj-sku').val();
      var sj_qty = row.find('.input-sj-qty').val();
      var rv_sku = row.find('.input-real-vs-jb-sku').val();
      var rv_qty = row.find('.input-real-vs-jb-qty').val();
      var selisih = row.find('.input-selisih').val();
      var action = row.find('.input-action').val();

      return $.ajax({
        url: '<?= base_url("accounting/save-surat-jalan-item") ?>',
        method: 'POST',
        data: {
          id: id,
          id_doc: currentDocId,
          tgl: tgl,
          jenis_sj: jenis_sj,
          no_trf_jubelio: no_trf,
          sku: sku,
          qty_restock_rqst: rqst,
          qty_restock_real: real,
          qty_jubelio_disp: disp,
          qty_jubelio_gd: gd,
          sj_jubelio_sku: sj_sku,
          sj_jubelio_qty: sj_qty,
          real_vs_jb_sku: rv_sku,
          real_vs_jb_qty: rv_qty,
          selisih: selisih,
          action_in_jubelio: action
        },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            row.data('id', res.data.id);
            row.removeClass('row-new row-dirty');
            if (showSuccessNoty) {
              showNoty('Baris berhasil disimpan', 'success');
            }
          } else {
            showNoty('Gagal menyimpan baris: ' + (res.message || 'Error'), 'error');
          }
        },
        error: function() {
          showNoty('Terjadi kesalahan koneksi server saat menyimpan', 'error');
        }
      });
    }

    // Save All modified rows
    $('#btn-save-all').click(function() {
      var dirtyRows = $('#spreadsheet-body tr.row-dirty').not('.no-data-row');
      
      if (dirtyRows.length === 0) {
        showNoty('Tidak ada perubahan baris data untuk disimpan', 'information');
        return;
      }

      var promises = [];
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

      dirtyRows.each(function() {
        var row = $(this);
        promises.push(saveRow(row, false));
      });

      $.when.apply($, promises).always(function() {
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Semua');
        recalculateTopSummary();
        showNoty('Semua perubahan data baris berhasil disimpan', 'success');
      });
    });

    // Noty utility helper
    function showNoty(message, type) {
      noty({
        text: message,
        layout: 'topRight',
        type: type || 'information',
        timeout: 3000
      });
    }

    /* =====================================================================
     *  EXCEL-LIKE SELECT & COPY / PASTE (FILL DOWN)
     *  - Klik sel lalu Ctrl+C untuk copy
     *  - Shift+Klik atau Shift+Panah Atas/Bawah untuk pilih ke bawah
     *  - Ctrl+V untuk paste (mengisi seluruh sel terpilih)
     *  - Ctrl+D untuk fill-down dari sel paling atas
     *  - Bisa juga paste langsung dari Excel (satu kolom / banyak kolom)
     * ===================================================================== */
    var $body = $('#spreadsheet-body');
    var anchorInput = null;       // sel awal (jangkar) saat memilih range
    var activeInput = null;       // ujung range yang sedang aktif
    var internalClipboard = [];   // nilai hasil copy internal (vektor kolom)

    // Ambil index kolom (posisi td) & index baris (posisi tr) dari sebuah input
    function cellCol($inp) { return $inp.closest('td').index(); }
    function cellRow($inp) { return $inp.closest('tr').index(); }

    // Dapatkan input pada koordinat baris/kolom tertentu (atau null)
    function getCell(rowIdx, colIdx) {
      var $tr = $body.find('tr').eq(rowIdx);
      if (!$tr.length) return null;
      var $inp = $tr.children('td').eq(colIdx).find('input.excel-input').first();
      return $inp.length ? $inp : null;
    }

    // Pastikan tersedia minimal (rowIdx+1) baris; tambah baris kosong bila kurang
    function ensureRows(rowIdx) {
      var num = $body.find('tr').length;
      while ($body.find('tr').length <= rowIdx) {
        num++;
        var $r = $(createRowHtml(null, num));
        $body.append($r);
        initRowAutocomplete($r);
      }
    }

    function clearSelection() {
      $body.find('.cell-selected').removeClass('cell-selected');
      $body.find('.cell-active').removeClass('cell-active');
    }

    // Tandai range terpilih pada kolom yang sama antara jangkar & sel target
    function selectRange($from, $to) {
      clearSelection();
      var col = cellCol($from);
      var r1 = cellRow($from), r2 = cellRow($to);
      if (r1 > r2) { var t = r1; r1 = r2; r2 = t; }
      for (var r = r1; r <= r2; r++) {
        var $c = getCell(r, col);
        if ($c) $c.addClass('cell-selected');
      }
      $to.addClass('cell-active');
    }

    // Kumpulkan input yang saat ini terpilih (urut dari atas ke bawah)
    function selectedInputs() {
      var arr = [];
      $body.find('input.cell-selected').each(function() { arr.push($(this)); });
      return arr;
    }

    // Isi sebuah input + picu event agar kalkulasi & tanda "dirty" berjalan
    function setCellValue($inp, val) {
      if (!$inp || $inp.prop('readonly')) return;
      $inp.val(val).trigger('input').trigger('change');
    }

    // --- Fokus / jangkar saat klik biasa ---
    $body.on('mousedown', 'input.excel-input', function(e) {
      if (e.shiftKey) {
        // Shift+Klik: perluas range dari jangkar ke sel ini (kolom sama)
        if (anchorInput && cellCol(anchorInput) === cellCol($(this))) {
          e.preventDefault();
          activeInput = $(this);
          this.focus();
          selectRange(anchorInput, activeInput);
          return;
        }
      }
      // Klik biasa: jadikan sel ini jangkar baru
      anchorInput = $(this);
      activeInput = $(this);
      clearSelection();
    });

    // --- Keyboard: Enter (turun 1 baris di kolom sama), Shift+Panah, Ctrl+C, Ctrl+D, Delete ---
    $body.on('keydown', 'input.excel-input', function(e) {
      var $inp = $(this);
      var ctrl = e.ctrlKey || e.metaKey;

      // Enter pada kolom angka (mis. Qty/Rqst) -> langsung ke baris berikutnya,
      // kolom yang sama, tanpa perlu klik. Untuk input teks/SKU dibiarkan default.
      if (e.key === 'Enter' && !e.shiftKey && !ctrl && $inp.attr('type') === 'number') {
        e.preventDefault();
        var $down = getCell(cellRow($inp) + 1, cellCol($inp));
        if ($down) {
          $down[0].focus();
          if ($down[0].select) $down[0].select();
        } else {
          $inp.blur(); // baris terakhir -> simpan
        }
        return;
      }

      // Shift + Panah Atas/Bawah -> perluas seleksi vertikal
      if (e.shiftKey && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        if (!anchorInput) anchorInput = $inp;
        var base = activeInput || $inp;
        var nextRow = cellRow(base) + (e.key === 'ArrowDown' ? 1 : -1);
        var $next = getCell(nextRow, cellCol(base));
        if ($next) {
          e.preventDefault();
          activeInput = $next;
          $next[0].focus();
          selectRange(anchorInput, activeInput);
        }
        return;
      }

      // Ctrl+C -> salin nilai sel terpilih (atau sel fokus) ke clipboard internal
      if (ctrl && (e.key === 'c' || e.key === 'C')) {
        var sel = selectedInputs();
        if (sel.length > 1) {
          internalClipboard = sel.map(function($c) { return $c.val(); });
          try { navigator.clipboard && navigator.clipboard.writeText(internalClipboard.join('\n')); } catch (err) {}
          e.preventDefault();
        } else {
          internalClipboard = [$inp.val()];
          // biarkan browser menyalin teks sel bila ada yang di-blok;
          // jika tidak ada blok, tetap simpan nilai ke clipboard sistem
          if (this.selectionStart === this.selectionEnd) {
            try { navigator.clipboard && navigator.clipboard.writeText($inp.val()); } catch (err) {}
          }
        }
        return;
      }

      // Ctrl+D -> fill-down: isi seluruh sel terpilih dengan sel teratas
      if (ctrl && (e.key === 'd' || e.key === 'D')) {
        e.preventDefault();
        var selD = selectedInputs();
        if (selD.length > 1) {
          var top = selD[0].val();
          for (var i = 1; i < selD.length; i++) setCellValue(selD[i], top);
        } else {
          // ambil dari sel tepat di atasnya
          var $above = getCell(cellRow($inp) - 1, cellCol($inp));
          if ($above) setCellValue($inp, $above.val());
        }
        recalculateTopSummary();
        return;
      }

      // Delete / Backspace saat range terpilih -> kosongkan sel terpilih
      if ((e.key === 'Delete') && selectedInputs().length > 1) {
        e.preventDefault();
        selectedInputs().forEach(function($c) { setCellValue($c, $c.attr('type') === 'number' ? 0 : ''); });
        recalculateTopSummary();
        return;
      }
    });

    // --- Paste: dari clipboard internal maupun langsung dari Excel ---
    $body.on('paste', 'input.excel-input', function(e) {
      var $inp = $(this);
      var cd = (e.originalEvent || e).clipboardData || window.clipboardData;
      var text = cd ? cd.getData('text') : '';

      var multiline = text && /[\t\n\r]/.test(text);
      var sel = selectedInputs();

      // Kasus 1: paste grid dari Excel (ada tab/baris baru)
      if (multiline) {
        e.preventDefault();
        var startRow = cellRow($inp), startCol = cellCol($inp);
        var lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
        // buang baris kosong terakhir bila ada
        while (lines.length && lines[lines.length - 1] === '') lines.pop();
        ensureRows(startRow + lines.length - 1);
        for (var i = 0; i < lines.length; i++) {
          var cols = lines[i].split('\t');
          for (var j = 0; j < cols.length; j++) {
            var $target = getCell(startRow + i, startCol + j);
            if ($target) setCellValue($target, cols[j].trim());
          }
        }
        recalculateTopSummary();
        return;
      }

      // Kasus 2: clipboard internal berisi banyak nilai -> fill-down mulai sel ini
      if (internalClipboard.length > 1) {
        e.preventDefault();
        var sRow = cellRow($inp), sCol = cellCol($inp);
        ensureRows(sRow + internalClipboard.length - 1);
        for (var k = 0; k < internalClipboard.length; k++) {
          var $t = getCell(sRow + k, sCol);
          if ($t) setCellValue($t, internalClipboard[k]);
        }
        recalculateTopSummary();
        return;
      }

      // Kasus 3: ada range terpilih -> isi semua sel dengan satu nilai.
      // Utamakan clipboard internal (andal di http); jika kosong pakai clipboard sistem.
      var fillVal = internalClipboard.length === 1 ? internalClipboard[0] : (text !== '' ? text : null);
      if (sel.length > 1 && fillVal !== null) {
        e.preventDefault();
        sel.forEach(function($c) { setCellValue($c, fillVal); });
        recalculateTopSummary();
        return;
      }
      // selain itu: biarkan paste normal ke dalam sel
    });

    // Esc -> batalkan seleksi
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape') clearSelection();
    });

  });
</script>
