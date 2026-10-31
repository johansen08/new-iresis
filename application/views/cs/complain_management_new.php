<div class="row">
  <div class="col-md-12">
    <!-- CSS Custom untuk Gaya Modern Clean (Startup Aesthetic) -->
    <style>
      :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --primary-light: rgba(79, 70, 229, 0.1);
        --bg-slate: #f8fafc;
        --border-slate: #e2e8f0;
        --text-slate: #334155;
        --text-muted: #64748b;
      }

      .modern-panel {
        background: #ffffff;
        border: 1px solid var(--border-slate);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        transition: all 0.3s ease;
        font-family: 'Inter', 'Segoe UI', sans-serif;
      }

      .modern-panel-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-slate);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .modern-panel-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        font-family: 'Outfit', sans-serif;
      }

      .modern-panel-body {
        padding: 24px;
      }

      /* Form styling */
      .modern-form-group {
        margin-bottom: 20px;
      }

      .modern-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-slate);
        margin-bottom: 8px;
        display: block;
      }

      .modern-input, .modern-select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-slate);
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-slate);
        background-color: #ffffff;
        transition: all 0.2s ease;
        box-sizing: border-box;
      }

      .modern-input:focus, .modern-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
      }

      .modern-input[readonly] {
        background-color: var(--bg-slate);
        color: var(--text-muted);
        cursor: not-allowed;
      }

      .btn-modern {
        background-color: var(--primary);
        color: #ffffff;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
      }

      .btn-modern:hover {
        background-color: var(--primary-hover);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
      }

      .btn-modern-secondary {
        background-color: #ffffff;
        color: var(--text-slate);
        border: 1px solid var(--border-slate);
      }

      .btn-modern-secondary:hover {
        background-color: var(--bg-slate);
        color: var(--text-slate);
        border-color: #cbd5e1;
      }

      .btn-modern-danger {
        background-color: #ef4444;
        color: #ffffff;
      }
      .btn-modern-danger:hover {
        background-color: #dc2626;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
      }

      /* Table styling */
      .modern-table {
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
      }

      .modern-table th {
        background-color: var(--bg-slate);
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        padding: 14px 16px;
        border-bottom: 2px solid var(--border-slate);
      }

      .modern-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-slate);
        font-size: 14px;
        color: var(--text-slate);
        vertical-align: middle;
      }

      .modern-table tr:hover td {
        background-color: rgba(248, 250, 252, 0.7);
      }

      /* Dropdown saran SKU.
         Sengaja tidak memakai jQuery UI autocomplete: build jQuery UI di proyek
         ini hanya berisi draggable/droppable/mouse/resizable/selectable/sortable,
         tidak ada widget autocomplete, sehingga pemanggilannya melempar
         TypeError dan menggagalkan seluruh handler. */
      .sku-suggest {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin: 2px 0 0;
        padding: 0;
        list-style: none;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-slate);
        background: #fff;
        max-height: 250px;
        overflow-y: auto;
        z-index: 9999;
      }

      .sku-suggest li {
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.15s;
        border-bottom: 1px solid #f1f5f9;
      }

      .sku-suggest li:last-child {
        border-bottom: none;
      }

      .sku-suggest li:hover,
      .sku-suggest li.aktif {
        background-color: var(--primary-light);
        color: var(--primary);
      }

      .sku-suggest .sku-suggest-kode {
        font-weight: 700;
      }

      .sku-suggest .sku-suggest-nama {
        display: block;
        color: var(--text-muted);
        font-size: 12px;
      }

      .sku-suggest .sku-suggest-kosong {
        color: var(--text-muted);
        cursor: default;
      }

      /* Dynamic row item list */
      .sku-row-container {
        border: 1px solid var(--border-slate);
        border-radius: 8px;
        padding: 16px;
        background-color: var(--bg-slate);
        margin-bottom: 20px;
      }

      .sku-item-row {
        background: #fff;
        border: 1px solid var(--border-slate);
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .btn-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid var(--border-slate);
        background: #fff;
        cursor: pointer;
        transition: all 0.2s;
      }

      .btn-icon:hover {
        background-color: var(--bg-slate);
        color: #000;
      }

      .btn-icon-danger:hover {
        background-color: #fee2e2;
        color: #ef4444;
        border-color: #fca5a5;
      }

      .grand-total-label {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-slate);
      }

      .grand-total-val {
        font-size: 18px;
        font-weight: 800;
        color: var(--primary);
        font-family: 'Outfit', sans-serif;
      }

      .label {
        border-radius: 4px;
        padding: 4px 8px;
        font-weight: 600;
        font-size: 11px;
      }

      /* Tab navigasi */
      .modern-tabs {
        display: flex;
        gap: 4px;
        border-bottom: 1px solid var(--border-slate);
        margin-bottom: 20px;
      }

      .modern-tab {
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
      }

      .modern-tab:hover {
        color: var(--text-slate);
      }

      .modern-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
      }

      .tab-badge {
        display: inline-block;
        background: #ef4444;
        color: #fff;
        border-radius: 10px;
        padding: 1px 8px;
        font-size: 11px;
        margin-left: 6px;
      }

      /* Lampiran */
      .lampiran-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
      }

      .lampiran-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--border-slate);
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 12px;
        background: #fff;
      }

      .lampiran-chip a {
        color: var(--primary);
        text-decoration: none;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      /* ===== Form Tambah/Edit Komplain (kf-*) =====
         Satu keluarga font (Inter, sudah dimuat lokal) untuk semua teks form; kontrol
         form diwarisi eksplisit karena bootstrap memaksa Open Sans di sana. */
      .kf-dialog { width: 96%; max-width: 1180px; margin: 24px auto; }
      .kf-content { border: none; border-radius: 14px; font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; box-shadow: 0 24px 60px rgba(15,23,42,.25); }
      .kf-content input, .kf-content select, .kf-content textarea, .kf-content button { font-family: inherit; }
      .kf-header { padding: 18px 28px 14px; border-bottom: 1px solid var(--border-slate); }
      .kf-header .close { margin-top: 2px; font-size: 26px; opacity: .45; }
      .kf-header .modal-title { font-size: 18px; font-weight: 700; color: #0f172a; font-family: inherit; line-height: 1.3; }
      .kf-subtitle { margin: 2px 0 0; font-size: 13px; color: var(--text-muted); }
      .kf-body { padding: 20px 28px 8px; max-height: calc(100vh - 210px); overflow-y: auto; background: #fff; }
      .kf-col { padding-left: 14px; padding-right: 14px; }
      .kf-col-side { border-left: 1px solid var(--border-slate); }
      .kf-sec { margin-bottom: 22px; }
      .kf-sec-head { display: flex; justify-content: space-between; align-items: center; }
      .kf-sec-title { display: flex; align-items: center; gap: 8px; margin: 0 0 12px; font-size: 14px; font-weight: 700; color: #0f172a; letter-spacing: .01em; }
      .kf-step { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 12px; font-weight: 700; }
      .kf-field { margin-bottom: 14px; }
      .kf-label { display: block; margin: 0 0 5px; font-size: 12.5px; font-weight: 600; color: var(--text-slate); }
      .kf-req { color: #dc2626; font-weight: 700; }
      .kf-input { display: block; width: 100%; height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; font-size: 14px; line-height: 1.4; color: #0f172a; box-sizing: border-box; transition: border-color .15s, box-shadow .15s; }
      textarea.kf-input { height: auto; min-height: 64px; resize: vertical; }
      .kf-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
      .kf-input::placeholder { color: #94a3b8; }
      .kf-input-lg { height: 46px; font-size: 16px; font-weight: 600; letter-spacing: .01em; }
      .kf-resi-group { display: flex; gap: 8px; }
      .kf-resi-group .kf-input { flex: 1; }
      .kf-resi-group .btn-modern { height: 46px; white-space: nowrap; padding: 0 16px; }
      .kf-hint { display: block; margin-top: 5px; font-size: 12px; line-height: 1.45; color: var(--text-muted); }
      .kf-hint-warn { color: #b91c1c; background: #fef2f2; border-radius: 6px; padding: 6px 8px; }
      .kf-row-tight { margin-left: -6px; margin-right: -6px; }
      .kf-row-tight > [class*="col-"] { padding-left: 6px; padding-right: 6px; }

      .kf-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; margin: 4px 0 6px; background: var(--border-slate); border: 1px solid var(--border-slate); border-radius: 10px; overflow: hidden; }
      .kf-summary-wide { grid-column: 1 / -1; }
      .kf-summary-item { background: var(--bg-slate); padding: 8px 12px; min-width: 0; }
      .kf-summary-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
      .kf-ro { display: block; width: 100%; padding: 0; margin-top: 2px; border: 0; background: transparent; font-size: 13.5px; font-weight: 600; color: #0f172a; text-overflow: ellipsis; box-shadow: none; cursor: default; }
      .kf-ro:focus { outline: none; }
      .kf-ro::placeholder { color: #cbd5e1; font-weight: 400; }

      .kf-alert { margin-bottom: 16px; padding: 10px 14px; border: 1px solid #fcd34d; border-left-width: 4px; border-radius: 8px; background: #fffbeb; color: #92400e; font-size: 13px; line-height: 1.5; }

      .kf-link-btn { margin-bottom: 12px; padding: 4px 8px; border: 0; background: transparent; color: var(--primary); font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; }
      .kf-link-btn:hover { background: var(--primary-light); }
      .kf-sku-head, .sku-item-row { display: grid; grid-template-columns: minmax(150px, 1fr) 72px 128px 112px 34px; gap: 8px; align-items: start; }
      .kf-sku-head { padding: 0 10px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
      .kf-sku-head .kf-sku-c-sub { text-align: right; }
      .sku-row-container { border: 0; padding: 0; background: transparent; margin-bottom: 8px; }
      .sku-item-row { padding: 8px 10px; margin-bottom: 8px; background: #fff; border: 1px solid var(--border-slate); border-radius: 10px; }
      .sku-item-row:focus-within { border-color: #a5b4fc; box-shadow: 0 0 0 3px var(--primary-light); }
      .sku-item-row .sku-cell { position: relative; min-width: 0; }
      .sku-item-row .kf-input { height: 38px; }
      .sku-nama { display: block; margin-top: 3px; font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .sku-sub { display: flex; align-items: center; justify-content: flex-end; height: 38px; text-align: right; font-size: 13.5px; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums; }
      .sku-item-row .btn-icon { width: 34px; height: 38px; }
      .kf-rp { position: relative; }
      .kf-rp > span { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; font-weight: 600; color: var(--text-muted); pointer-events: none; }
      .kf-rp .kf-input { padding-left: 32px; text-align: right; font-variant-numeric: tabular-nums; }
      .kf-total { display: flex; justify-content: space-between; align-items: baseline; padding: 10px 12px; border-top: 2px solid var(--border-slate); }
      .kf-total-label { font-size: 13px; font-weight: 600; color: var(--text-slate); }
      .grand-total-val { font-family: inherit; font-size: 20px; font-weight: 700; color: var(--primary); font-variant-numeric: tabular-nums; }

      .kf-seg { display: flex; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; }
      .kf-seg-opt { flex: 1; margin: 0; cursor: pointer; }
      .kf-seg-opt input { position: absolute; opacity: 0; pointer-events: none; }
      .kf-seg-opt span { display: block; padding: 9px 6px; text-align: center; font-size: 12.5px; font-weight: 600; color: var(--text-slate); background: #fff; border-right: 1px solid #cbd5e1; transition: background .15s, color .15s; }
      .kf-seg-opt:last-child span { border-right: 0; }
      .kf-seg-opt:hover span { background: var(--bg-slate); }
      .kf-seg-opt input:checked + span { background: var(--primary); color: #fff; }
      .kf-seg-opt input:focus-visible + span { box-shadow: inset 0 0 0 2px #fff, inset 0 0 0 4px var(--primary); }

      .kf-footer { display: flex; justify-content: flex-end; align-items: center; gap: 10px; padding: 14px 28px; border-top: 1px solid var(--border-slate); background: var(--bg-slate); border-radius: 0 0 14px 14px; }
      .kf-footer .btn-modern { height: 40px; }
      .kf-footer #role-warning-edit { margin-right: auto; }

      .kf-preview .kf-pv-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0 28px; }
      .kf-pv-sec { margin-bottom: 18px; }
      .kf-pv-sec h5 { margin: 0 0 8px; font-size: 13px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: .04em; }
      .kf-pv-row { display: flex; gap: 12px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
      .kf-pv-row dt { flex: 0 0 130px; margin: 0; font-weight: 500; color: var(--text-muted); }
      .kf-pv-row dd { flex: 1; margin: 0; font-weight: 600; color: #0f172a; word-break: break-word; }
      .kf-pv-auto { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; }
      .kf-pv-auto h5 { color: #3730a3; }
      .kf-pv-auto .kf-pv-row { border-bottom-color: #e0e7ff; }
      .kf-pv-auto .kf-pv-note { margin-top: 6px; font-size: 12px; color: #4f46e5; }
      .kf-pv-table { width: 100%; font-size: 13px; border-collapse: collapse; }
      .kf-pv-table th { text-align: left; padding: 4px 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); border-bottom: 1px solid var(--border-slate); }
      .kf-pv-table td { padding: 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
      .kf-pv-table .num { text-align: right; font-variant-numeric: tabular-nums; }
      .kf-pv-table .nama { display: block; font-size: 11.5px; color: var(--text-muted); }

      @media (max-width: 991px) {
        .kf-preview .kf-pv-grid { grid-template-columns: 1fr; }
        .kf-col-side { border-left: 0; border-top: 1px solid var(--border-slate); padding-top: 18px; }
        .kf-body { padding: 16px 14px 4px; }
        .kf-header, .kf-footer { padding-left: 16px; padding-right: 16px; }
      }
      @media (max-width: 575px) {
        .kf-sku-head { display: none; }
        .sku-item-row { grid-template-columns: 1fr 70px 34px; }
        .sku-item-row .sku-cell-price { grid-column: 1 / 3; }
        .sku-item-row .sku-sub { grid-column: 3 / 4; }
        .kf-summary { grid-template-columns: 1fr; }
      }
    </style>

    <!-- Header Menu -->
    <div class="modern-panel">
      <div class="modern-panel-header">
        <h3 class="modern-panel-title"><i class="fa fa-exclamation-triangle" style="color: var(--primary); margin-right: 8px;"></i> Manajemen Komplain CS New</h3>
        <div>
          <button type="button" class="btn-modern" id="btn-tambah-komplain"><i class="fa fa-plus"></i> Tambah Komplain</button>
        </div>
      </div>

      <div class="modern-panel-body">
        <!-- Tab -->
        <div class="modern-tabs">
          <button type="button" class="modern-tab active" data-tab="daftar"><i class="fa fa-list"></i> Daftar Komplain</button>
          <button type="button" class="modern-tab" data-tab="kandidat">
            <i class="fa fa-truck"></i> Kandidat dari Retur Fisik
            <?php if (!empty($jumlah_kandidat)): ?>
              <span class="tab-badge" id="badge-kandidat"><?= (int) $jumlah_kandidat ?></span>
            <?php endif; ?>
          </button>
        </div>

        <!-- ================= TAB: DAFTAR KOMPLAIN ================= -->
        <div id="tab-pane-daftar">
          <!-- Filter -->
          <div class="row" style="margin-bottom: 10px;">
            <div class="col-md-4">
              <div class="modern-form-group">
                <label class="modern-label">Rentang Tanggal Komplain</label>
                <div class="input-group">
                  <input type="text" id="reportrange" class="modern-input" style="border-top-right-radius:0; border-bottom-right-radius:0;" value="<?= date('Y-m-01 00:00:00') . ' - ' . date('Y-m-t 23:59:59') ?>" />
                  <span class="input-group-btn">
                    <button type="button" class="btn btn-default" id="btn-filter-date" style="height: 38px; border-color: var(--border-slate); border-top-left-radius:0; border-bottom-left-radius:0;"><i class="fa fa-refresh"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="modern-form-group">
                <label class="modern-label">Kategori</label>
                <select id="f_kategori" class="modern-select">
                  <option value="">Semua Kategori</option>
                  <?php foreach ($kategori_list as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="modern-form-group">
                <label class="modern-label">Sumber Komplain</label>
                <select id="f_sumber" class="modern-select">
                  <option value="">Semua Sumber</option>
                  <?php foreach ($sumber_list as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="modern-form-group">
                <label class="modern-label">Status</label>
                <select id="f_status" class="modern-select">
                  <option value="">Semua Status</option>
                  <?php foreach ($status_list as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
                  <?php endforeach; ?>
                  <option value="__KOSONG__">(Belum diisi)</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row" style="margin-bottom: 10px;">
            <div class="col-md-3">
              <div class="modern-form-group">
                <label class="modern-label">Hasil Investigasi (CCTV)</label>
                <select id="f_hasil" class="modern-select">
                  <option value="">Semua Hasil</option>
                  <?php foreach ($hasil_list as $h): ?>
                    <option value="<?= htmlspecialchars($h) ?>"><?= htmlspecialchars($h) ?></option>
                  <?php endforeach; ?>
                  <option value="__KOSONG__">(Belum dicek)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- DataTable -->
          <div class="table-responsive">
            <table class="modern-table table" id="datatable-complain-management">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tgl Komplain</th>
                  <th>No. Resi / Pesanan</th>
                  <th>Marketplace</th>
                  <th>Toko</th>
                  <th>Kategori</th>
                  <th>Sumber</th>
                  <th>Status</th>
                  <th>Hasil Investigasi</th>
                  <th>Nominal Total</th>
                  <th>QC</th>
                  <th>Packer</th>
                  <th>Proses Banding</th>
                  <th style="width: 100px;">Aksi</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

        <!-- ================= TAB: KANDIDAT RETUR FISIK ================= -->
        <div id="tab-pane-kandidat" style="display: none;">
          <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">
            <i class="fa fa-info-circle"></i>
            Resi yang ditandai komplain saat Scan Retur tapi belum tercatat di modul ini.
            Tekan <strong>Tarik</strong> untuk membuatkan draft komplain (kategori &amp; nominal tetap harus dilengkapi CS).
          </p>

          <div style="margin-bottom: 12px;">
            <button type="button" class="btn-modern btn-modern-secondary" id="btn-tarik-terpilih"><i class="fa fa-download"></i> Tarik Semua yang Tampil</button>
          </div>

          <div class="table-responsive">
            <table class="modern-table table" id="datatable-kandidat-retur">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tgl Scan Retur</th>
                  <th>No. Resi</th>
                  <th>Marketplace</th>
                  <th>Toko</th>
                  <th>Status Retur</th>
                  <th style="width: 100px;">Aksi</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Form Input/Edit Komplain (Modern Layout) -->
<div class="modal fade" id="modalFormKomplain" role="dialog" aria-labelledby="modalFormKomplainLabel" aria-hidden="true" style="overflow-y: auto;">
  <div class="modal-dialog modal-lg kf-dialog" role="document">
    <div class="modal-content kf-content">
      <!-- class "nojs" wajib: tanpa itu handler global di plugins.js membajak submit,
           menimpa isi halaman dengan spinner, dan data tidak pernah terkirim. -->
      <form id="form-save-complain" class="nojs" action="javascript:void(0);" enctype="multipart/form-data">
        <input type="hidden" name="is_edit" id="is_edit" value="0">
        <input type="hidden" name="id_complain" id="id_complain" value="">
        <input type="hidden" name="force_duplikat" id="force_duplikat" value="0">

        <div class="modal-header kf-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modalFormKomplainLabel">Form Komplain</h4>
          <p class="kf-subtitle">Cukup isi nomor resi atau pesanan &mdash; data lain terisi otomatis dari iresis.</p>
        </div>

        <div class="modal-body kf-body">
          <div id="alert-komplain-lain" class="kf-alert" style="display: none;"></div>

          <div class="row">
            <!-- Kolom kiri: pesanan + barang -->
            <div class="col-md-7 kf-col">
              <section class="kf-sec">
                <h5 class="kf-sec-title"><span class="kf-step">1</span> Pesanan</h5>

                <div class="kf-field">
                  <label class="kf-label" for="no_resi">No. Resi / Pesanan / Picklist <span class="kf-req">*</span></label>
                  <div class="kf-resi-group">
                    <input type="text" name="no_resi" id="no_resi" class="kf-input kf-input-lg" placeholder="Scan atau ketik, lalu Enter" autocomplete="off" required />
                    <button type="button" class="btn-modern" id="btn-search-resi" title="Isi data otomatis dari nomor ini"><i class="fa fa-magic"></i> Isi Otomatis</button>
                  </div>
                  <small class="kf-hint" id="resi-search-hint">Terisi otomatis: pesanan, tanggal, marketplace, toko, barang, picker, dan QC.</small>
                </div>

                <div class="kf-summary">
                  <div class="kf-summary-item">
                    <span class="kf-summary-label">No. Pesanan</span>
                    <input type="text" name="no_pesanan" id="no_pesanan" class="kf-ro" readonly placeholder="&ndash;" />
                  </div>
                  <div class="kf-summary-item">
                    <span class="kf-summary-label">Tanggal Pesanan</span>
                    <input type="text" name="tgl_pesanan" id="tgl_pesanan" class="kf-ro" readonly placeholder="&ndash;" />
                  </div>
                </div>
                <input type="hidden" name="nilai_pesanan" id="nilai_pesanan" />

                <div class="row kf-row-tight" style="margin-top: 12px;">
                  <div class="col-sm-5">
                    <div class="kf-field">
                      <label class="kf-label" for="id_marketplace">Marketplace <span class="kf-req">*</span></label>
                      <select name="id_marketplace" id="id_marketplace" class="kf-input" required>
                        <option value="" selected disabled>Pilih</option>
                        <?php foreach ($marketplaces as $m): ?>
                          <option value="<?= $m['id_marketplace'] ?>"><?= htmlspecialchars($m['nama_marketplace']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-7">
                    <div class="kf-field">
                      <label class="kf-label" for="toko">Toko <span class="kf-req">*</span></label>
                      <select name="toko" id="toko" class="kf-input" required>
                        <option value="" selected disabled>Pilih</option>
                        <?php foreach ($stores as $s): ?>
                          <option value="<?= htmlspecialchars($s['nama_toko']) ?>"><?= htmlspecialchars($s['nama_toko']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </section>

              <section class="kf-sec">
                <div class="kf-sec-head">
                  <h5 class="kf-sec-title"><span class="kf-step">2</span> Barang Komplain <span class="kf-req">*</span></h5>
                  <button type="button" class="kf-link-btn" id="btn-add-sku-row"><i class="fa fa-plus"></i> Tambah baris</button>
                </div>
                <div class="kf-sku-head">
                  <span>SKU</span><span>Qty</span><span>Harga satuan</span><span class="kf-sku-c-sub">Subtotal</span><span></span>
                </div>
                <div class="sku-row-container" id="sku-rows-list">
                  <!-- Baris SKU dinamis dimasukkan di sini oleh JS -->
                </div>
                <div class="kf-total">
                  <span class="kf-total-label">Total nominal barang</span>
                  <span class="grand-total-val" id="text-nominal-total">Rp 0</span>
                </div>
              </section>
            </div>

            <!-- Kolom kanan: komplain + investigasi -->
            <div class="col-md-5 kf-col kf-col-side">
              <section class="kf-sec">
                <h5 class="kf-sec-title"><span class="kf-step">3</span> Komplain</h5>

                <div class="kf-field">
                  <span class="kf-label">Jenis Komplain</span>
                  <div class="kf-seg" role="radiogroup" aria-label="Jenis komplain">
                    <?php foreach ($jenis_list as $i => $j): ?>
                      <label class="kf-seg-opt"><input type="radio" name="jenis_komplain" value="<?= htmlspecialchars($j) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= htmlspecialchars($j) ?></span></label>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="kf-field">
                  <label class="kf-label" for="kategori_komplain">Kategori Komplain <span class="kf-req">*</span></label>
                  <select name="kategori_komplain" id="kategori_komplain" class="kf-input" required>
                    <option value="" selected disabled>Pilih kategori</option>
                    <?php foreach ($kategori_list as $k): ?>
                      <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="kf-field">
                  <label class="kf-label" for="detail_lainnya">Keterangan Komplain <span class="kf-req" id="tanda-wajib-keterangan" style="display:none;">*</span></label>
                  <textarea name="detail_lainnya" id="detail_lainnya" class="kf-input" rows="2" maxlength="500" placeholder="Rincian keluhan (wajib bila kategori Lainnya)"></textarea>
                </div>

                <div class="row kf-row-tight">
                  <div class="col-sm-6">
                    <div class="kf-field">
                      <label class="kf-label" for="tgl_komplain">Tanggal Komplain</label>
                      <input type="date" name="tgl_komplain" id="tgl_komplain" class="kf-input" />
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="kf-field">
                      <label class="kf-label" for="sumber_komplain">Sumber</label>
                      <select name="sumber_komplain" id="sumber_komplain" class="kf-input">
                        <option value="">Belum ditentukan</option>
                        <?php foreach ($sumber_list as $s): ?>
                          <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </section>

              <section class="kf-sec">
                <h5 class="kf-sec-title"><span class="kf-step">4</span> Investigasi</h5>

                <div class="kf-field">
                  <label class="kf-label" for="hasil_investigasi">Hasil Cek CCTV</label>
                  <select name="hasil_investigasi" id="hasil_investigasi" class="kf-input">
                    <option value="">Belum dicek</option>
                    <?php foreach ($hasil_list as $h): ?>
                      <option value="<?= htmlspecialchars($h) ?>"><?= htmlspecialchars($h) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="kf-hint kf-hint-warn" id="hint-qc-salah" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>QC Salah</strong> menambah 1 poin kesalahan packer di KPI (resi harus pernah dipacking). Mengubahnya lagi mencabut poin itu.
                  </small>
                </div>

                <div class="kf-field">
                  <label class="kf-label" for="catatan_penanganan">Catatan Hasil Investigasi</label>
                  <textarea name="catatan_penanganan" id="catatan_penanganan" class="kf-input" rows="2" placeholder="Temuan dari pengecekan CCTV"></textarea>
                </div>

                <input type="hidden" name="nama_packer" id="nama_packer" value="" />
                <div class="row kf-row-tight">
                  <div class="col-sm-6">
                    <div class="kf-field">
                      <label class="kf-label" for="nama_picker">Picker</label>
                      <select name="nama_picker" id="nama_picker" class="kf-input">
                        <option value="">Pilih</option>
                        <?php foreach ($employees as $emp): ?>
                          <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['nama_pegawai']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="kf-field">
                      <label class="kf-label" for="nama_qc">Packer</label>
                      <select name="nama_qc" id="nama_qc" class="kf-input">
                        <option value="">Pilih</option>
                        <?php foreach ($employees as $emp): ?>
                          <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['nama_pegawai']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>

        <!-- Pratinjau sebelum simpan: ringkasan isi form + data input otomatis -->
        <div class="modal-body kf-body kf-preview" id="kf-preview" style="display: none;"></div>

        <div class="modal-footer kf-footer">
          <span id="role-warning-edit" class="text-warning pull-left" style="display: none; padding-top: 8px; font-size:12px; font-weight:600;"><i class="fa fa-lock"></i> Mode Read-only (Edit hanya diizinkan bagi Admin/Supervisor)</span>
          <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal" id="btn-batal-form">Batal</button>
          <button type="submit" class="btn-modern" id="btn-save-submit"><i class="fa fa-eye"></i> Pratinjau &amp; Simpan</button>
          <button type="button" class="btn-modern btn-modern-secondary" id="btn-preview-back" style="display:none;"><i class="fa fa-arrow-left"></i> Ubah</button>
          <button type="button" class="btn-modern" id="btn-preview-save" style="display:none;"><i class="fa fa-save"></i> Simpan Komplain</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts Section -->
<script>
  $(document).ready(function() {
    var userRole = parseInt("<?= $this->session->userdata('user')['hakakses'] ?? 0 ?>");
    var isAdminOrSupervisor = [1, 2].includes(userRole);
    var namaPenggunaLogin = "<?= htmlspecialchars($this->session->userdata('user')['name'] ?? '-', ENT_QUOTES) ?>";
    var kandidatTable = null;

    // Initialisasi DataTables
    var table = $('#datatable-complain-management').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "cs-new/complain-management/get-data",
            "type": "POST",
            "data": function(d) {
                d.reportrange = $('#reportrange').val();
                d.f_kategori = $('#f_kategori').val();
                d.f_sumber = $('#f_sumber').val();
                d.f_status = $('#f_status').val();
                d.f_hasil = $('#f_hasil').val();
            }
        },
        "columns": [
            { "data": 0, "orderable": false }, // index
            { "data": 1 },  // tgl komplain
            { "data": 2 },  // no resi
            { "data": 3 },  // marketplace
            { "data": 4 },  // toko
            { "data": 5 },  // kategori
            { "data": 6 },  // sumber
            { "data": 7 },  // status
            { "data": 8 },  // hasil investigasi
            { "data": 9 },  // nominal
            { "data": 10 }, // qc
            { "data": 11 }, // packer
            { "data": 12 }, // banding
            { "data": 13, "orderable": false } // action
        ],
        "order": [[1, "desc"]],
        "language": {
            "emptyTable": "Tidak ada data komplain yang terdaftar"
        }
    });

    // DateRangePicker Initialization
    $('#reportrange').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        },
        timePicker: true,
        timePicker24Hour: true,
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        ranges: {
           'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
           'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
           '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
           '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
           'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
           'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#btn-filter-date').on('click', function() {
        table.ajax.reload();
    });

    $('#f_kategori, #f_sumber, #f_status, #f_hasil').on('change', function() {
        table.ajax.reload();
    });

    // ---------------- Tab switching ----------------
    $('.modern-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.modern-tab').removeClass('active');
        $(this).addClass('active');

        if (tab === 'daftar') {
            $('#tab-pane-daftar').show();
            $('#tab-pane-kandidat').hide();
        } else {
            $('#tab-pane-daftar').hide();
            $('#tab-pane-kandidat').show();
            initKandidatTable();
        }
    });

    function initKandidatTable() {
        if (kandidatTable !== null) {
            kandidatTable.columns.adjust().draw(false);
            return;
        }

        kandidatTable = $('#datatable-kandidat-retur').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "cs-new/complain-management/kandidat-retur",
                "type": "POST"
            },
            "columns": [
                { "data": 0, "orderable": false },
                { "data": 1, "orderable": false },
                { "data": 2, "orderable": false },
                { "data": 3, "orderable": false },
                { "data": 4, "orderable": false },
                { "data": 5, "orderable": false },
                { "data": 6, "orderable": false }
            ],
            "language": {
                "emptyTable": "Tidak ada komplain retur fisik yang belum ditarik"
            }
        });
    }

    // Tarik satu kandidat
    $(document).on('click', '.btn-tarik-kandidat', function() {
        var $btn = $(this);
        tarikKandidat([$btn.data('id')], $btn);
    });

    // Tarik semua yang tampil di halaman aktif
    $('#btn-tarik-terpilih').on('click', function() {
        var ids = [];
        $('#datatable-kandidat-retur .btn-tarik-kandidat').each(function() {
            ids.push($(this).data('id'));
        });

        if (ids.length === 0) {
            showNoty('Tidak ada kandidat di halaman ini.', 'warning');
            return;
        }

        showConfirmation('Tarik <strong>' + ids.length + '</strong> komplain retur fisik jadi draft komplain?', function() {
            tarikKandidat(ids, $('#btn-tarik-terpilih'));
        }, 'Ya, Tarik');
    });

    function tarikKandidat(ids, $btn) {
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menarik...');

        $.ajax({
            url: 'cs-new/complain-management/tarik-kandidat',
            type: 'POST',
            data: { id_resiretur: ids },
            dataType: 'json',
            success: function(resp) {
                showNoty(resp.message, resp.code === 201 ? 'success' : 'warning');
                if (kandidatTable) kandidatTable.ajax.reload(null, false);
                table.ajax.reload(null, false);
                refreshBadgeKandidat(-(resp.ditarik || 0));
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function refreshBadgeKandidat(delta) {
        var $badge = $('#badge-kandidat');
        if ($badge.length === 0) return;
        var val = parseInt($badge.text()) + delta;
        if (val > 0) {
            $badge.text(val);
        } else {
            $badge.remove();
        }
    }

    // ---------------- Form komplain ----------------

    function resetFormKomplain() {
        dataInputAsli = null;
        $('#form-save-complain')[0].reset();
        $('#force_duplikat').val('0');
        $('#sku-rows-list').empty();
        $('#tanda-wajib-keterangan').hide();
        // required harus ikut dilepas: field wajib yang tersembunyi bikin submit
        // diblokir browser tanpa pesan apa pun
        $('#detail_lainnya').prop('required', false).val('');
        $('#hint-qc-salah').hide();
        $('#alert-komplain-lain').hide().html('');
    }

    // Show form modal to add new complain
    $('#btn-tambah-komplain').on('click', function() {
        resetFormKomplain();
        $('#is_edit').val('0');
        $('#id_complain').val('');
        $('#no_resi').prop('readonly', false);
        $('#btn-search-resi').show();
        $('#resi-search-hint').show();
        $('#role-warning-edit').hide();
        $('#btn-save-submit').show();
        $('#tgl_komplain').val(moment().format('YYYY-MM-DD'));
        // Hampir semua komplain masuk lewat chat marketplace; ganti hanya bila berbeda
        $('#sumber_komplain').val('Chat Marketplace');
        // Tanggal input & penginput terisi sendiri, tidak bisa diubah manual

        // Add 1 default empty row
        addSkuRow('', 1, 0, '');
        calculateNominalTotal();

        $('#modalFormKomplainLabel').text('Tambah Komplain Baru');
        $('#modalFormKomplain').modal('show');
    });

    // Kursor langsung di kolom resi saat form baru dibuka; setelah edit tidak perlu
    $('#modalFormKomplain').on('shown.bs.modal', function() {
        if ($('#is_edit').val() !== '1') {
            $('#no_resi').trigger('focus');
        }
    });

    // Peringatan dampak KPI saat hasil investigasi = QC Salah
    $('#hasil_investigasi').on('change', function() {
        if ($(this).val() === 'QC Salah') {
            $('#hint-qc-salah').slideDown();
        } else {
            $('#hint-qc-salah').slideUp();
        }
    });

    // Keterangan selalu tampil; wajib hanya untuk kategori Lainnya
    $('#kategori_komplain').on('change', function() {
        var lainnya = $(this).val() === 'Lainnya';
        $('#detail_lainnya').prop('required', lainnya);
        $('#tanda-wajib-keterangan').toggle(lainnya);
    });

    // Auto-fill trigger by clicking button
    $('#btn-search-resi').on('click', function() {
        searchResiAutoFill();
    });

    // Handle pressing Enter inside no_resi input
    $('#no_resi').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchResiAutoFill();
        }
    });

    // Otomatis mencari begitu nomor selesai diisi (pindah kolom / tempel), tanpa harus Enter
    var terakhirDicari = '';
    function cariOtomatis() {
        if ($('#is_edit').val() === '1') return;
        var v = $('#no_resi').val().trim();
        if (v.length < 5 || v === terakhirDicari) return;
        terakhirDicari = v;
        searchResiAutoFill();
    }
    $('#no_resi').on('blur change paste', function() { setTimeout(cariOtomatis, 50); });
    $('#modalFormKomplain').on('show.bs.modal', function() { terakhirDicari = ''; });

    function searchResiAutoFill() {
        var noresi = $('#no_resi').val().trim();
        if (noresi === '') {
            showNoty('Silakan masukkan nomor resi / nomor pesanan terlebih dahulu!', 'error');
            return;
        }

        var $btn = $('#btn-search-resi');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mencari...');

        $.ajax({
            url: 'cs-new/complain-management/search-resi',
            type: 'POST',
            data: { noresi: noresi },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    showNoty('Data resi ditemukan. Form diisi otomatis!', 'success');

                    // Fill simple text inputs
                    if (resp.data.id_marketplace) {
                        $('#id_marketplace').val(resp.data.id_marketplace);
                    }

                    // Fill toko (select2 / normal select)
                    if (resp.data.toko) {
                        ensureOption('#toko', resp.data.toko);
                        $('#toko').val(resp.data.toko);
                    }
                    if (!resp.data.toko) {
                        showNoty('Nama toko resi ini tidak tercatat di iresis (resi lama). Pilih toko secara manual.', 'warning');
                    }

                    // Packer disimpan tersembunyi; di meja ini packer = QC
                    if (resp.data.nama_packer) {
                        $('#nama_packer').val(resp.data.nama_packer);
                        ensureOption('#nama_qc', resp.data.nama_packer);
                        $('#nama_qc').val(resp.data.nama_packer);
                    }

                    if (resp.data.nama_picker) {
                        ensureOption('#nama_picker', resp.data.nama_picker);
                        $('#nama_picker').val(resp.data.nama_picker);
                    }

                    // Nomor resi bisa terisi dari nomor pesanan / picklist yang diketik
                    if (resp.data.noresi) {
                        $('#no_resi').val(resp.data.noresi);
                        terakhirDicari = resp.data.noresi;
                    }

                    // Nomor & tanggal pesanan
                    $('#no_pesanan').val(resp.data.no_pesanan || '');
                    $('#tgl_pesanan').val(resp.data.tanggal_pesan || '');

                    // Nilai pesanan disimpan diam-diam (tidak ditampilkan)
                    var nilai = parseFloat(resp.data.nilai_pesanan || 0);
                    $('#nilai_pesanan').val(nilai);

                    // Tanggal komplain mengikuti saat resi discan, kalau belum diisi
                    if (!$('#tgl_komplain').val()) {
                        $('#tgl_komplain').val(moment().format('YYYY-MM-DD'));
                    }

                    // Fill dynamic items
                    $('#sku-rows-list').empty();
                    if (resp.items && resp.items.length > 0) {
                        $.each(resp.items, function(idx, item) {
                            addSkuRow(item.sku, item.qty, item.hpp, item.nama_sku);
                        });
                    } else {
                        addSkuRow('', 1, 0, '');
                    }

                    calculateNominalTotal();
                    tampilkanKomplainLain(resp.existing);

                    // Pindahkan kursor ke isian pertama yang masih kosong
                    if (!$('#toko').val()) {
                        $('#toko').trigger('focus');
                    } else if (!$('#kategori_komplain').val()) {
                        $('#kategori_komplain').trigger('focus');
                    }

                    if (resp.catatan) {
                        showNoty(resp.catatan, 'warning');
                    }
                } else {
                    showNoty(resp.message, 'warning');
                }
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function tampilkanKomplainLain(list) {
        var $alert = $('#alert-komplain-lain');
        if (!list || list.length === 0) {
            $alert.hide().html('');
            return;
        }

        var html = '<i class="fa fa-exclamation-circle"></i> Resi ini sudah punya <strong>' + list.length + '</strong> komplain tercatat: ';
        var parts = [];
        $.each(list, function(i, c) {
            var tgl = c.tgl_komplain || (c.created_at ? c.created_at.substring(0, 10) : '-');
            parts.push(c.kategori_komplain + ' (' + tgl + ')');
        });
        html += parts.join(', ') + '. Lanjutkan hanya kalau ini memang komplain yang berbeda.';
        $alert.html(html).show();
    }

    function ensureOption(selector, value) {
        var exists = false;
        $(selector + ' option').each(function() {
            if ($(this).val() === value) {
                exists = true;
                return false;
            }
        });
        if (!exists) {
            $(selector).append(new Option(value, value));
        }
    }

    // Function to add a dynamic SKU row
    function escAttr(v) {
        return String(v === undefined || v === null ? '' : v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function addSkuRow(sku, qty, hpp, name) {
        var rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        var formattedHpp = parseFloat(hpp || 0).toFixed(2);

        var html = '<div class="sku-item-row" id="' + rowId + '">' +
                   '  <div class="sku-cell">' +
                   '    <input type="text" name="sku[]" class="kf-input sku-autocomplete" placeholder="Cari / ketik SKU" autocomplete="off" value="' + escAttr(sku) + '" required />' +
                   '    <span class="sku-nama">' + escAttr(name) + '</span>' +
                   '  </div>' +
                   '  <div class="sku-cell">' +
                   '    <input type="number" name="qty[]" class="kf-input item-qty" placeholder="Qty" min="1" value="' + escAttr(qty) + '" required />' +
                   '  </div>' +
                   '  <div class="sku-cell sku-cell-price">' +
                   '    <div class="kf-rp"><span>Rp</span>' +
                   '      <input type="text" name="price[]" class="kf-input item-price" value="' + formattedHpp + '" inputmode="decimal" />' +
                   '    </div>' +
                   '  </div>' +
                   '  <div class="sku-sub">Rp 0</div>' +
                   '  <div>' +
                   '    <button type="button" class="btn-icon btn-icon-danger btn-remove-sku-row" title="Hapus baris"><i class="fa fa-times"></i></button>' +
                   '  </div>' +
                   '</div>';

        $('#sku-rows-list').append(html);

        // Listener qty/harga; saran SKU ditangani lewat event delegation di bawah
        $('#' + rowId).find('.item-qty, .item-price').on('input change', function() {
            calculateNominalTotal();
        });
        calculateNominalTotal();
    }

    // ---------------- Dropdown saran SKU (tanpa jQuery UI) ----------------

    var timerSaran = null;

    function tutupSaran($input) {
        $input.closest('div').find('.sku-suggest').remove();
    }

    function pilihSaran($li) {
        var $input = $li.closest('div').find('.sku-autocomplete');
        var $row = $input.closest('.sku-item-row');
        $row.find('.sku-autocomplete').val($li.data('kode'));
        $row.find('.item-price').val(parseFloat($li.data('hpp') || 0).toFixed(2));
        $row.find('.sku-nama').text($li.data('nama') || '');
        tutupSaran($input);
        calculateNominalTotal();
    }

    function tampilkanSaran($input, items) {
        tutupSaran($input);

        var $ul = $('<ul class="sku-suggest"></ul>');
        // Sesi habis membuat endpoint mengembalikan objek, bukan array
        if (!$.isArray(items) || items.length === 0) {
            $ul.append('<li class="sku-suggest-kosong">SKU tidak ditemukan</li>');
        } else {
            $.each(items, function(i, it) {
                var $li = $('<li></li>')
                    .attr('data-kode', it.label)
                    .attr('data-hpp', it.hpp)
                    .attr('data-nama', it.value || '')
                    .append($('<span class="sku-suggest-kode"></span>').text(it.label))
                    .append($('<span class="sku-suggest-nama"></span>').text(it.value || ''));
                $ul.append($li);
            });
        }
        $input.closest('div').append($ul);
    }

    $(document).on('input', '.sku-autocomplete', function() {
        var $input = $(this);
        var term = $input.val().trim();

        clearTimeout(timerSaran);
        if (term.length < 1) {
            tutupSaran($input);
            return;
        }

        timerSaran = setTimeout(function() {
            $.ajax({
                url: 'cs-new/complain-management/get-sku-suggestions',
                type: 'GET',
                data: { term: term },
                dataType: 'json',
                success: function(items) {
                    // hanya tampilkan kalau isian belum berubah lagi
                    if ($input.val().trim() === term) {
                        tampilkanSaran($input, items);
                    }
                }
            });
        }, 250);
    });

    $(document).on('click', '.sku-suggest li', function() {
        if ($(this).hasClass('sku-suggest-kosong')) return;
        pilihSaran($(this));
    });

    $(document).on('keydown', '.sku-autocomplete', function(e) {
        var $ul = $(this).closest('div').find('.sku-suggest');
        if ($ul.length === 0) return;

        var $aktif = $ul.find('li.aktif');

        if (e.which === 40) { // panah bawah
            e.preventDefault();
            var $next = $aktif.length ? $aktif.next('li') : $ul.find('li').first();
            if ($next.length) { $ul.find('li').removeClass('aktif'); $next.addClass('aktif'); }
        } else if (e.which === 38) { // panah atas
            e.preventDefault();
            var $prev = $aktif.prev('li');
            if ($prev.length) { $ul.find('li').removeClass('aktif'); $prev.addClass('aktif'); }
        } else if (e.which === 13) { // enter
            e.preventDefault();
            if ($aktif.length && !$aktif.hasClass('sku-suggest-kosong')) pilihSaran($aktif);
        } else if (e.which === 27) { // escape
            tutupSaran($(this));
        }
    });

    // Klik di luar menutup dropdown
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.sku-item-row').length) {
            $('.sku-suggest').remove();
        }
    });

    // Add row button
    $('#btn-add-sku-row').on('click', function() {
        addSkuRow('', 1, 0, '');
    });

    // Remove row button
    $(document).on('click', '.btn-remove-sku-row', function() {
        if ($('.sku-item-row').length > 1) {
            $(this).closest('.sku-item-row').remove();
            calculateNominalTotal();
        } else {
            showNoty('Minimal harus ada 1 item komplain!', 'warning');
        }
    });

    // Function to calculate nominal total
    function calculateNominalTotal() {
        var total = 0;
        $('.sku-item-row').each(function() {
            var qty = parseInt($(this).find('.item-qty').val()) || 0;
            var price = parseFloat(String($(this).find('.item-price').val()).replace(',', '.')) || 0;
            var sub = qty * price;
            total += sub;
            $(this).find('.sku-sub').text('Rp ' + sub.toLocaleString('id-ID'));
        });

        $('#text-nominal-total').text('Rp ' + total.toLocaleString('id-ID'));
    }

    // Submit form action (Save / Edit)
    $('#form-save-complain').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        tampilkanPratinjau();
    });

    // ---------------- Pratinjau sebelum simpan ----------------

    var dataInputAsli = null;   // terisi saat membuka mode edit
    var timerJam = null;

    // Format nama user tidak seragam ("IKA - LEADER - 0001", "0353 - JOHANSEN - IT"):
    // no absen = potongan yang semuanya angka, nama = potongan pertama yang bukan angka
    function pecahNamaUser(teks) {
        var hasil = { nama: '-', absen: '-' };
        $.each(String(teks || '').split('-'), function(i, potong) {
            potong = $.trim(potong);
            if (potong === '') return;
            if (/^\d+$/.test(potong)) {
                if (hasil.absen === '-') hasil.absen = potong;
            } else if (hasil.nama === '-') {
                hasil.nama = potong;
            }
        });
        return hasil;
    }

    function esc(v) { return $('<div>').text(v === undefined || v === null || v === '' ? '-' : v).html(); }

    function barisPv(label, nilai) {
        return '<div class="kf-pv-row"><dt>' + label + '</dt><dd>' + esc(nilai) + '</dd></div>';
    }

    function tampilkanPratinjau() {
        var editMode = $('#is_edit').val() === '1';
        var mp = $('#id_marketplace option:selected').text();
        var total = 0, barang = '';
        $('.sku-item-row').each(function() {
            var sku = $(this).find('.sku-autocomplete').val().trim();
            if (!sku) return;
            var qty = parseInt($(this).find('.item-qty').val()) || 0;
            var harga = parseFloat(String($(this).find('.item-price').val()).replace(',', '.')) || 0;
            total += qty * harga;
            barang += '<tr><td>' + esc(sku) + '<span class="nama">' + esc($(this).find('.sku-nama').text()) + '</span></td>' +
                      '<td class="num">' + qty + '</td><td class="num">Rp ' + harga.toLocaleString('id-ID') + '</td>' +
                      '<td class="num">Rp ' + (qty * harga).toLocaleString('id-ID') + '</td></tr>';
        });

        var penginput = pecahNamaUser(namaPenggunaLogin);
        var autoHtml = '<div class="kf-pv-auto"><h5><i class="fa fa-magic"></i> Data input (terisi otomatis saat disimpan)</h5>';
        if (editMode && dataInputAsli) {
            autoHtml += barisPv('Tanggal input', dataInputAsli.waktu) + barisPv('No. absen input', dataInputAsli.absen) + barisPv('Nama penginput', dataInputAsli.nama) +
                        '<div class="kf-pv-note">Data input asli, tidak berubah saat diedit.</div>';
        } else {
            autoHtml += '<div class="kf-pv-row"><dt>Tanggal input</dt><dd id="pv-jam"></dd></div>' +
                        barisPv('No. absen input', penginput.absen) + barisPv('Nama penginput', penginput.nama) +
                        '<div class="kf-pv-note">Jam mengikuti waktu server saat tombol Simpan ditekan.</div>';
        }
        autoHtml += '</div>';

        var html = autoHtml + '<div class="kf-pv-grid"><div>' +
            '<div class="kf-pv-sec"><h5>Pesanan</h5>' +
                barisPv('No. resi', $('#no_resi').val()) + barisPv('No. pesanan', $('#no_pesanan').val()) +
                barisPv('Tanggal pesanan', $('#tgl_pesanan').val()) + barisPv('Marketplace', $('#id_marketplace').val() ? mp : '') +
                barisPv('Toko', $('#toko').val()) + '</div>' +
            '<div class="kf-pv-sec"><h5>Barang komplain</h5><table class="kf-pv-table"><thead><tr><th>SKU</th><th class="num">Qty</th><th class="num">Harga</th><th class="num">Subtotal</th></tr></thead><tbody>' +
                barang + '<tr><td colspan="3" class="num"><strong>Total</strong></td><td class="num"><strong>Rp ' + total.toLocaleString('id-ID') + '</strong></td></tr></tbody></table></div>' +
            '</div><div>' +
            '<div class="kf-pv-sec"><h5>Komplain</h5>' +
                barisPv('Jenis', $('input[name="jenis_komplain"]:checked').val()) + barisPv('Kategori', $('#kategori_komplain').val()) +
                barisPv('Keterangan', $('#detail_lainnya').val()) + barisPv('Tanggal komplain', $('#tgl_komplain').val()) +
                barisPv('Sumber', $('#sumber_komplain').val()) + '</div>' +
            '<div class="kf-pv-sec"><h5>Investigasi</h5>' +
                barisPv('Hasil cek CCTV', $('#hasil_investigasi').val()) + barisPv('Catatan', $('#catatan_penanganan').val()) +
                barisPv('Picker', $('#nama_picker').val()) + barisPv('Packer', $('#nama_qc').val()) + '</div>' +
            '</div></div>';

        $('#kf-preview').html(html);
        modeFormKomplain(false);

        if (!(editMode && dataInputAsli)) {
            var tickJam = function() { $('#pv-jam').text(moment().format('DD/MM/YYYY HH:mm:ss')); };
            tickJam();
            clearInterval(timerJam);
            timerJam = setInterval(tickJam, 1000);
        }
    }

    // true = form isian, false = pratinjau
    function modeFormKomplain(form) {
        clearInterval(form ? timerJam : null);
        $('.kf-body').not('#kf-preview').toggle(form);
        $('#kf-preview').toggle(!form);
        $('#btn-batal-form, #btn-save-submit').toggle(form && !($('#is_edit').val() === '1' && !isAdminOrSupervisor));
        if (form && $('#is_edit').val() === '1' && !isAdminOrSupervisor) { $('#btn-batal-form').show(); }
        $('#btn-preview-back, #btn-preview-save').toggle(!form);
        $('#modalFormKomplainLabel').toggleClass('kf-pv-mode', !form);
        $('.kf-subtitle').toggle(form);
    }

    $('#btn-preview-back').on('click', function() { modeFormKomplain(true); });
    $('#btn-preview-save').on('click', function() { kirimFormKomplain(); });
    $('#modalFormKomplain').on('hidden.bs.modal', function() { clearInterval(timerJam); modeFormKomplain(true); });

    function kirimFormKomplain() {
        var $btn = $('#btn-preview-save');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        var formData = new FormData($('#form-save-complain')[0]);

        $.ajax({
            url: 'cs-new/complain-management/save',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.code === 201) {
                    showNoty(resp.message, 'success');
                    $('#modalFormKomplain').modal('hide');
                    table.ajax.reload(null, false);
                } else if (resp.code === 409) {
                    // Kategori sama pada resi yang sama -- minta konfirmasi
                    showConfirmation(resp.message, function() {
                        $('#force_duplikat').val('1');
                        kirimFormKomplain();
                    }, 'Ya, Simpan Terpisah');
                } else {
                    showNoty(resp.message || 'Gagal menyimpan data.', 'error');
                }
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // Edit action trigger
    $(document).on('click', '.btn-edit-complain', function() {
        var id = $(this).data('id');

        resetFormKomplain();
        $('#is_edit').val('1');
        $('#id_complain').val(id);
        $('#no_resi').prop('readonly', true);
        $('#btn-search-resi').hide();
        $('#resi-search-hint').hide();

        if (isAdminOrSupervisor) {
            $('#role-warning-edit').hide();
            $('#btn-save-submit').show();
        } else {
            $('#role-warning-edit').show();
            $('#btn-save-submit').hide();
        }

        // Fetch detail via AJAX
        $.ajax({
            url: 'cs-new/complain-management/detail/' + encodeURIComponent(id),
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var comp = resp.complain;

                    $('#no_resi').val(comp.no_resi);
                    $('#tgl_komplain').val(comp.tgl_komplain || '');
                    $('#sumber_komplain').val(comp.sumber_komplain || '');
                    $('input[name="jenis_komplain"][value="' + (comp.jenis_komplain || 'CCTV') + '"]').prop('checked', true);
                    $('#catatan_penanganan').val(comp.catatan_penanganan || '');
                    $('#hasil_investigasi').val(comp.hasil_investigasi || '');
                    if (comp.hasil_investigasi === 'QC Salah') {
                        $('#hint-qc-salah').show();
                    }
                    $('#id_marketplace').val(comp.id_marketplace);

                    ensureOption('#toko', comp.toko);
                    $('#toko').val(comp.toko);

                    $('#kategori_komplain').val(comp.kategori_komplain);
                    $('#detail_lainnya').val(comp.detail_lainnya || '').prop('required', comp.kategori_komplain === 'Lainnya');
                    $('#tanda-wajib-keterangan').toggle(comp.kategori_komplain === 'Lainnya');

                    if (comp.nama_qc) {
                        ensureOption('#nama_qc', comp.nama_qc);
                        $('#nama_qc').val(comp.nama_qc);
                    }

                    $('#nama_packer').val(comp.nama_packer || '');

                    if (comp.nama_picker) {
                        ensureOption('#nama_picker', comp.nama_picker);
                        $('#nama_picker').val(comp.nama_picker);
                    }

                    $('#no_pesanan').val(comp.no_pesanan || '');
                    $('#tgl_pesanan').val(comp.tgl_pesanan || '');

                    $('#nilai_pesanan').val(comp.nilai_pesanan || '');

                    // Data input asli (untuk pratinjau mode edit), bukan waktu sekarang
                    dataInputAsli = {
                        waktu: comp.created_at ? moment(comp.created_at).format('DD/MM/YYYY HH:mm:ss') : '-',
                        absen: comp.no_absen_input || '-',
                        nama: comp.nama_penginput || '-'
                    };

                    // SKU rows
                    if (resp.items && resp.items.length > 0) {
                        $.each(resp.items, function(idx, item) {
                            addSkuRow(item.sku, item.qty, item.price, item.nama_sku);
                        });
                    } else {
                        addSkuRow('', 1, 0, '');
                    }

                    calculateNominalTotal();
                    tampilkanKomplainLain(resp.komplain_lain);

                    $('#modalFormKomplainLabel').text(isAdminOrSupervisor ? 'Edit Data Komplain' : 'Detail Data Komplain (Read-Only)');
                    $('#modalFormKomplain').modal('show');
                } else {
                    showNoty(resp.message || 'Gagal mengambil detail komplain.', 'error');
                }
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            }
        });
    });

    function renderLampiran(list) {
        var $wrap = $('#lampiran-list');
        $wrap.empty();
        if (!list || list.length === 0) return;

        $.each(list, function(i, l) {
            var nama = l.nama_asli || l.nama_file;
            var chip = '<span class="lampiran-chip">' +
                       '<i class="fa fa-file-o"></i>' +
                       '<a href="' + l.url + '" target="_blank" title="' + nama + '">' + nama + '</a>';
            if (isAdminOrSupervisor) {
                chip += ' <a href="javascript:void(0)" class="text-danger btn-hapus-lampiran" data-id="' + l.id_lampiran + '" title="Hapus lampiran"><i class="fa fa-times"></i></a>';
            }
            chip += '</span>';
            $wrap.append(chip);
        });
    }

    $(document).on('click', '.btn-hapus-lampiran', function() {
        var $chip = $(this).closest('.lampiran-chip');
        var id = $(this).data('id');

        $.ajax({
            url: 'cs-new/complain-management/delete-lampiran/' + encodeURIComponent(id),
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                if (resp.code === 200) {
                    $chip.remove();
                    showNoty(resp.message, 'success');
                    table.ajax.reload(null, false);
                } else {
                    showNoty(resp.message || 'Gagal menghapus lampiran.', 'error');
                }
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            }
        });
    });

    // Delete/Soft-delete action trigger
    $(document).on('click', '.btn-delete-complain', function() {
        var id = $(this).data('id');
        var noresi = $(this).data('noresi');

        showConfirmation('Apakah Anda yakin ingin menghapus data komplain untuk resi <strong>' + noresi + '</strong>?<br/><small class="text-muted">Data akan di-softdelete (tidak dihapus fisik dari database).</small>', function() {
            $.ajax({
                url: 'cs-new/complain-management/delete/' + encodeURIComponent(id),
                type: 'POST',
                dataType: 'json',
                success: function(resp) {
                    if (resp.code === 200) {
                        showNoty(resp.message, 'success');
                        table.ajax.reload(null, false);
                    } else {
                        showNoty(resp.message || 'Gagal menghapus data.', 'error');
                    }
                },
                error: function() {
                    showNoty('Terjadi kesalahan koneksi server.', 'error');
                }
            });
        }, 'Ya, Hapus');
    });

    function showConfirmation(message, onConfirm, labelYa) {
        noty({
            text: message,
            type: 'warning',
            layout: 'center',
            modal: true,
            buttons: [
                {
                    addClass: 'btn btn-success btn-clean',
                    text: labelYa || 'Ya',
                    onClick: function($noty) {
                        $noty.close();
                        if (typeof onConfirm === 'function') {
                            onConfirm();
                        }
                    }
                },
                {
                    addClass: 'btn btn-danger btn-clean',
                    text: 'Batal',
                    onClick: function($noty) {
                        $noty.close();
                    }
                }
            ]
        });
    }

    function showNoty(message, type) {
        noty({
            text: message,
            layout: 'topRight',
            type: type || 'information',
            timeout: 3000
        });
    }
  });
</script>
