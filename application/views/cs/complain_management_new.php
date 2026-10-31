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
      .kf-body { padding: 16px 28px 4px; max-height: calc(100vh - 210px); overflow-y: auto; background: #fff; }
      .kf-col { padding-left: 14px; padding-right: 14px; }
      .kf-col-side { border-left: 1px solid var(--border-slate); }
      .kf-sec { margin-bottom: 16px; }
      .kf-sec-head { display: flex; justify-content: space-between; align-items: center; }
      .kf-sec-title { display: flex; align-items: center; gap: 8px; margin: 0 0 12px; font-size: 14px; font-weight: 700; color: #0f172a; letter-spacing: .01em; }
      .kf-step { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 12px; font-weight: 700; }
      .kf-field { margin-bottom: 12px; }
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

      /* Kolom sama tinggi supaya garis pemisah penuh dari atas ke bawah */
      @media (min-width: 992px) {
        .kf-body > .row { display: flex; }
        .kf-body > .row > .kf-col { float: none; }
      }

      @media (max-width: 991px) {
        .kf-preview .kf-pv-grid { grid-template-columns: 1fr; }
        .kf-col-side { border-left: 0; border-top: 1px solid var(--border-slate); padding-top: 18px; }
        .kf-body { padding: 16px 14px 4px; }
        .kf-header, .kf-footer { padding-left: 16px; padding-right: 16px; }
      }
      @media (max-width: 575px) {
        .kf-sku-head { display: none; }
        /* Baris SKU di HP: SKU | Qty | hapus, lalu harga | subtotal di baris kedua */
        .sku-item-row { grid-template-columns: 1fr 70px 38px; }
        .sku-item-row > :nth-child(1) { grid-column: 1; grid-row: 1; }
        .sku-item-row > :nth-child(2) { grid-column: 2; grid-row: 1; }
        .sku-item-row > :nth-child(5) { grid-column: 3; grid-row: 1; }
        .sku-item-row > :nth-child(3) { grid-column: 1; grid-row: 2; }
        .sku-item-row > :nth-child(4) { grid-column: 2 / 4; grid-row: 2; }
        .kf-summary { grid-template-columns: 1fr; }
      }

      /* ===== Daftar komplain (kp-*) — referensi: tabel dashboard dengan kartu ringkasan,
         chip filter multi-pilih, dan drawer detail di sisi kanan ===== */
      .kp-page, .kp-page.modern-panel { font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; background: transparent; border: 0; box-shadow: none; margin-bottom: 8px; }
      .kp-page input, .kp-page select, .kp-page button { font-family: inherit; }
      .kp-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 4px 0 14px; }
      .kp-period { display: flex; align-items: center; gap: 10px; }
      .kp-period-label { font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted); }
      .kp-period .kp-chip { height: 40px; background: #fff; }
      .kp-head-actions .btn-modern { height: 40px; }
      .kp-head-actions { display: flex; gap: 8px; flex: none; }

      .kp-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; padding: 0; }
      .kp-kpi { border: 1px solid var(--border-slate); border-radius: 12px; padding: 14px 16px 12px; background: #fff; }
      .kp-kpi-top { display: flex; align-items: center; gap: 8px; }
      .kp-kpi-ic { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; font-size: 14px; }
      .kp-ic-indigo { background: #eef2ff; color: #4338ca; }
      .kp-ic-red { background: #fef2f2; color: #b91c1c; }
      .kp-ic-amber { background: #fffbeb; color: #b45309; }
      .kp-ic-green { background: #ecfdf5; color: #047857; }
      .kp-kpi-label { font-size: 13px; font-weight: 600; color: var(--text-slate); }
      .kp-kpi-val { margin-top: 10px; font-size: 28px; line-height: 1.1; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
      .kp-kpi-note { margin-top: 4px; font-size: 12px; color: var(--text-muted); }

      /* Satu kartu untuk pencarian, filter, dan tabel: bagian "data" terpisah jelas dari ringkasan di atasnya */
      .kp-body, .modern-panel-body.kp-body { margin-top: 16px; padding: 16px 18px 12px; background: #fff; border: 1px solid var(--border-slate); border-radius: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
      .kp-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 4px 0 14px; }
      .kp-search { position: relative; flex: 1 1 240px; max-width: 340px; }
      .kp-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
      .kp-search input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: #fff; color: #0f172a; box-sizing: border-box; }
      .kp-search input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
      .kp-filter { position: relative; }
      .kp-chip { display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: var(--text-slate); font-size: 13.5px; font-weight: 500; cursor: pointer; margin: 0; white-space: nowrap; }
      .kp-chip:hover { background: var(--bg-slate); }
      .kp-chip .fa-angle-down { color: #94a3b8; }
      .kp-chip-count { display: none; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; }
      .kp-filter.aktif .kp-chip { border-color: #a5b4fc; background: #eef2ff; color: #3730a3; }
      .kp-filter.aktif .kp-chip-count { display: inline-block; }
      .kp-chip-date input { width: 262px; border: 0; background: transparent; font-size: 13.5px; color: var(--text-slate); cursor: pointer; padding: 0; }
      .kp-chip-date input:focus { outline: none; }
      .kp-chip-date .fa { color: #94a3b8; }
      .kp-pop { display: none; position: absolute; z-index: 1050; top: calc(100% + 6px); left: 0; min-width: 210px; padding: 6px; background: #fff; border: 1px solid var(--border-slate); border-radius: 10px; box-shadow: 0 12px 28px rgba(15,23,42,.14); }
      .kp-filter.open .kp-pop { display: block; }
      .kp-opt { display: flex; align-items: center; gap: 9px; margin: 0; padding: 8px 10px; border-radius: 6px; font-size: 13.5px; font-weight: 400; color: var(--text-slate); cursor: pointer; }
      .kp-opt:hover { background: var(--bg-slate); }
      .kp-opt input { margin: 0; accent-color: var(--primary); width: 15px; height: 15px; }
      .kp-opt span { flex: 1; }
      .kp-opt-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; }
      .kp-bg-red { background: #ef4444; } .kp-bg-amber { background: #f59e0b; } .kp-bg-green { background: #10b981; } .kp-bg-slate { background: #94a3b8; }
      .kp-pop-foot { margin-top: 4px; padding: 6px 10px 2px; border-top: 1px solid var(--border-slate); }
      .kp-pop-clear { border: 0; background: transparent; padding: 0; font-size: 12.5px; color: var(--text-muted); cursor: pointer; }
      .kp-pop-clear:hover { color: var(--primary); }
      .kp-reset { height: 38px; padding: 0 10px; border: 0; background: transparent; color: var(--primary); font-size: 13.5px; font-weight: 600; cursor: pointer; border-radius: 8px; }
      .kp-reset:hover { background: var(--primary-light); }

      .kp-table-wrap { border: 1px solid var(--border-slate); border-radius: 10px; overflow-x: auto; background: #fff; }
      .kp-copyrow { display: flex; align-items: center; gap: 4px; }
      .kp-copy { flex: none; width: 20px; height: 20px; padding: 0; border: 0; border-radius: 5px; background: transparent; color: #cbd5e1; font-size: 12px; cursor: pointer; line-height: 1; }
      .kp-table tbody tr:hover .kp-copy { color: #94a3b8; }
      .kp-copy:hover, .kp-table tbody tr:hover .kp-copy:hover { background: var(--primary-light); color: var(--primary); }
      .kp-copy.ok, .kp-copy.ok:hover { color: #059669; background: #ecfdf5; }
      .kp-dr-row dd .kp-copy { margin-left: 6px; vertical-align: middle; color: #94a3b8; }
      .kp-duo { display: flex; flex-direction: column; gap: 4px; }
      .kp-duo .kp-person:nth-child(n) { font-size: 13px; }
      .kp-duo > span + span .kp-avatar { background: #f1f5f9; color: #475569; }
      .kp-table { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
      .kp-table > thead > tr > th { padding: 10px 9px; background: var(--bg-slate); border-bottom: 1px solid var(--border-slate); border-top: 0; font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--text-muted); white-space: nowrap; }
      .kp-table > thead > tr > th.kp-th-num { text-align: right; }
      .kp-table > thead > tr > th[class*="sorting"] { padding-right: 20px; }
      .kp-table > tbody > tr > td { padding: 10px 9px; border-top: 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; color: var(--text-slate); vertical-align: middle; }
      .kp-table > tbody > tr:nth-child(even) > td { background: #fafbfd; }
      .kp-table > tbody > tr:hover > td { background: #f1f5ff; cursor: pointer; }
      .kp-table > tbody > tr:last-child > td { border-bottom: 0; }
      .kp-table td:nth-child(5) { min-width: 120px; max-width: 150px; }
      .kp-table td:nth-child(6) { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; color: #0f172a; white-space: nowrap; }
      .kp-id { font-weight: 600; color: var(--primary); font-variant-numeric: tabular-nums; white-space: nowrap; }
      .kp-resi { display: block; font-weight: 600; color: #0f172a; white-space: nowrap; }
      .kp-sub { display: block; margin-top: 1px; font-size: 12px; font-weight: 400; color: var(--text-muted); white-space: nowrap; }
      .kp-muted { color: #94a3b8; }
      .kp-star { color: #ef4444; }
      .kp-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
      .kp-pill-red { background: #fef2f2; color: #b91c1c; } .kp-pill-amber { background: #fffbeb; color: #b45309; }
      .kp-pill-green { background: #ecfdf5; color: #047857; } .kp-pill-indigo { background: #eef2ff; color: #4338ca; }
      .kp-pill-sky { background: #f0f9ff; color: #0369a1; } .kp-pill-slate { background: #f1f5f9; color: #475569; }
      .kp-dot-text { display: inline-flex; align-items: center; gap: 7px; font-weight: 500; line-height: 1.3; }
      .kp-dot-text i { flex: none; width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
      .kp-dot-red i { background: #ef4444; } .kp-dot-amber i { background: #f59e0b; } .kp-dot-green i { background: #10b981; }
      .kp-person { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; font-size: 12.5px; }
      .kp-avatar { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 20px; height: 20px; border-radius: 50%; background: #e0e7ff; color: #3730a3; font-size: 10px; font-weight: 700; }
      .kp-actions { display: flex; gap: 2px; justify-content: flex-end; }
      .kp-icon-btn { width: 28px; height: 28px; border: 1px solid transparent; border-radius: 8px; background: transparent; color: #64748b; cursor: pointer; }
      .kp-icon-btn:hover { background: #fff; border-color: var(--border-slate); color: var(--primary); }
      .kp-icon-danger:hover { color: #dc2626; background: #fef2f2; border-color: #fecaca; }

      .kp-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 4px 0; font-size: 13px; color: var(--text-muted); }
      .kp-foot .dataTables_info { padding: 0; }
      .kp-pager { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
      .kp-pager .dataTables_length label { margin: 0; font-weight: 400; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
      .kp-pager .dataTables_length select { height: 32px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 6px; background: #fff; color: var(--text-slate); }
      .kp-pager .dataTables_paginate { margin: 0; }
      .kp-pager .pagination { margin: 0; }
      .kp-pager .pagination > li > a { margin-left: 4px; border: 1px solid var(--border-slate); border-radius: 8px !important; color: var(--text-slate); padding: 5px 11px; font-size: 13px; }
      .kp-pager .pagination > li > a:hover { background: var(--bg-slate); }
      .kp-pager .pagination > .active > a, .kp-pager .pagination > .active > a:hover, .kp-pager .pagination > .active > a:focus { background: var(--primary) !important; border-color: var(--primary) !important; color: #fff !important; }
      .kp-pager .pagination > .disabled > a { color: #cbd5e1; background: #fff; }
      #datatable-complain-management_processing { background: rgba(255,255,255,.9); border: 1px solid var(--border-slate); border-radius: 8px; padding: 8px 14px; font-size: 13px; color: var(--text-muted); box-shadow: 0 6px 16px rgba(15,23,42,.1); }

      /* ===== Drawer sisi kanan (form tambah/edit & detail) ===== */
      #modalFormKomplain, #drawerKomplain { overflow: hidden !important; }
      .modal-dialog.kf-dialog, .modal-dialog.kp-dr-dialog { position: fixed; top: 0; right: 0; bottom: 0; margin: 0 !important; height: 100vh; max-width: none; }
      .modal-dialog.kf-dialog { width: min(1180px, 96vw); }
      /* Sidebar aplikasi (280px, z-index 1060) berada di atas modal: sisakan ruang untuknya */
      @media (min-width: 992px) { .modal-dialog.kf-dialog { width: min(1180px, calc(100vw - 280px)); } }
      /* Drawer lebih sempit dari 1000px: susun dua kolom ke bawah (breakpoint bootstrap berbasis layar, bukan drawer) */
      @media (min-width: 992px) and (max-width: 1249px) {
        .kf-dialog .kf-body > .row { display: block; }
        .kf-dialog .kf-body > .row > .kf-col { float: none; width: 100%; }
        .kf-dialog .kf-col-side { border-left: 0; border-top: 1px solid var(--border-slate); padding-top: 18px; }
      }
      .modal-dialog.kp-dr-dialog { width: min(600px, 96vw); }
      .modal.fade .kf-dialog, .modal.fade .kp-dr-dialog { transform: translateX(100%); transition: transform .28s ease-out; }
      .modal.in .kf-dialog, .modal.in .kp-dr-dialog { transform: none; }
      .kf-content, .kp-dr-content { height: 100%; display: flex; flex-direction: column; border-radius: 16px 0 0 16px; overflow: hidden; }
      .kf-content > form { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
      .kf-body { flex: 1 1 auto; min-height: 0; max-height: none; }
      .kf-footer { flex: none; }

      .kp-dr-content { font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; box-shadow: -16px 0 48px rgba(15,23,42,.22); }
      .kp-dr-header { flex: none; }
      .kp-dr-titlebox { display: flex; align-items: center; gap: 12px; padding-right: 30px; }
      .kp-dr-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: var(--primary-light); color: var(--primary); font-size: 17px; flex: none; }
      .kp-dr-meta { margin-top: 3px; font-size: 12.5px; color: var(--text-muted); }
      .kp-dr-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 18px 28px 8px; }
      .kp-dr-sec { margin-bottom: 20px; }
      .kp-dr-sec h5 { margin: 0 0 6px; font-size: 14px; font-weight: 700; color: #0f172a; }
      .kp-dr-row { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
      .kp-dr-row i.fa { flex: none; width: 18px; margin-top: 2px; text-align: center; color: #94a3b8; }
      .kp-dr-row dt { flex: 0 0 120px; margin: 0; font-weight: 400; color: var(--text-muted); }
      .kp-dr-row dd { flex: 1; margin: 0; font-weight: 600; color: #0f172a; word-break: break-word; }
      .kp-dr-table { width: 100%; margin-top: 6px; border: 1px solid var(--border-slate); border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden; font-size: 13px; }
      .kp-dr-table th { padding: 8px 12px; background: var(--bg-slate); font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--text-muted); }
      .kp-dr-table td { padding: 9px 12px; border-top: 1px solid #f1f5f9; }
      .kp-dr-table .num { text-align: right; font-variant-numeric: tabular-nums; }
      .kp-dr-total td { font-weight: 700; background: var(--bg-slate); }

      @media (max-width: 1199px) { .kp-kpis { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 767px) {
        .kp-head { padding: 0 0 12px; }
        .kp-kpis { gap: 10px; }
        .kp-body, .modern-panel-body.kp-body { padding: 12px 12px 8px; }
        .kp-search { max-width: none; flex-basis: 100%; }
        .kp-chip-date input { width: 190px; }
        .modal-dialog.kf-dialog, .modal-dialog.kp-dr-dialog { width: 100vw; }
        .kf-content, .kp-dr-content { border-radius: 0; }
      }
    </style>

    <!-- Header Menu -->
    <?php
      // Nada titik warna untuk opsi filter Hasil CCTV (sama dengan pil di tabel)
      $kp_nada_hasil = ['QC Salah' => 'red', 'QC Benar' => 'green', 'Tidak Terlihat CCTV' => 'amber', 'CCTV Tidak Bisa Diakses' => 'slate'];
    ?>
    <div class="modern-panel kp-page">
      <div class="kp-head">
        <div class="kp-period">
          <span class="kp-period-label">Periode</span>
          <label class="kp-chip kp-chip-date" title="Rentang tanggal komplain">
            <i class="fa fa-calendar"></i>
            <input type="text" id="reportrange" readonly value="<?= date('Y-m-01 00:00:00') . ' - ' . date('Y-m-t 23:59:59') ?>" />
          </label>
        </div>
        <div class="kp-head-actions">
          <button type="button" class="btn-modern btn-modern-secondary" id="btn-export-komplain" title="Unduh daftar sesuai filter yang aktif"><i class="fa fa-download"></i> Export</button>
          <button type="button" class="btn-modern" id="btn-tambah-komplain"><i class="fa fa-plus"></i> Tambah Komplain</button>
        </div>
      </div>

      <!-- Kartu ringkasan: mengikuti rentang tanggal & filter yang aktif -->
      <div class="kp-kpis">
        <div class="kp-kpi">
          <div class="kp-kpi-top"><span class="kp-kpi-ic kp-ic-indigo"><i class="fa fa-list-alt"></i></span><span class="kp-kpi-label">Total Komplain</span></div>
          <div class="kp-kpi-val" id="kpi-total">&ndash;</div>
          <div class="kp-kpi-note">pada periode &amp; filter terpilih</div>
        </div>
        <div class="kp-kpi">
          <div class="kp-kpi-top"><span class="kp-kpi-ic kp-ic-red"><i class="fa fa-exclamation-triangle"></i></span><span class="kp-kpi-label">QC Salah</span></div>
          <div class="kp-kpi-val" id="kpi-qcsalah">&ndash;</div>
          <div class="kp-kpi-note" id="kpi-qcsalah-note">&nbsp;</div>
        </div>
        <div class="kp-kpi">
          <div class="kp-kpi-top"><span class="kp-kpi-ic kp-ic-amber"><i class="fa fa-video-camera"></i></span><span class="kp-kpi-label">Belum Dicek CCTV</span></div>
          <div class="kp-kpi-val" id="kpi-belum">&ndash;</div>
          <div class="kp-kpi-note">perlu investigasi</div>
        </div>
        <div class="kp-kpi">
          <div class="kp-kpi-top"><span class="kp-kpi-ic kp-ic-green"><i class="fa fa-money"></i></span><span class="kp-kpi-label">Total Nominal Barang</span></div>
          <div class="kp-kpi-val" id="kpi-nominal">&ndash;</div>
          <div class="kp-kpi-note">nilai barang yang dikomplain</div>
        </div>
      </div>

      <div class="modern-panel-body kp-body">
        <div id="tab-pane-daftar">
          <div class="kp-toolbar">
            <div class="kp-search">
              <i class="fa fa-search"></i>
              <input type="text" id="kp-search" placeholder="Cari resi, no. pesanan, toko, nama..." autocomplete="off" />
            </div>

            <div class="kp-filter" data-key="kategori">
              <button type="button" class="kp-chip"><span class="kp-chip-label">Kategori</span><span class="kp-chip-count"></span><i class="fa fa-angle-down"></i></button>
              <div class="kp-pop">
                <?php foreach ($kategori_list as $k): ?>
                  <label class="kp-opt"><input type="checkbox" value="<?= htmlspecialchars($k) ?>"><span><?= htmlspecialchars($k) ?></span></label>
                <?php endforeach; ?>
                <div class="kp-pop-foot"><button type="button" class="kp-pop-clear">Hapus pilihan</button></div>
              </div>
            </div>

            <div class="kp-filter" data-key="hasil">
              <button type="button" class="kp-chip"><span class="kp-chip-label">Hasil CCTV</span><span class="kp-chip-count"></span><i class="fa fa-angle-down"></i></button>
              <div class="kp-pop">
                <?php foreach ($hasil_list as $h): ?>
                  <label class="kp-opt"><input type="checkbox" value="<?= htmlspecialchars($h) ?>"><i class="kp-opt-dot kp-bg-<?= $kp_nada_hasil[$h] ?? 'slate' ?>"></i><span><?= htmlspecialchars($h) ?></span></label>
                <?php endforeach; ?>
                <label class="kp-opt"><input type="checkbox" value="__KOSONG__"><i class="kp-opt-dot kp-bg-slate"></i><span>Belum dicek</span></label>
                <div class="kp-pop-foot"><button type="button" class="kp-pop-clear">Hapus pilihan</button></div>
              </div>
            </div>

            <div class="kp-filter" data-key="jenis">
              <button type="button" class="kp-chip"><span class="kp-chip-label">Jenis</span><span class="kp-chip-count"></span><i class="fa fa-angle-down"></i></button>
              <div class="kp-pop">
                <?php foreach ($jenis_list as $j): ?>
                  <label class="kp-opt"><input type="checkbox" value="<?= htmlspecialchars($j) ?>"><span><?= htmlspecialchars($j) ?></span></label>
                <?php endforeach; ?>
                <div class="kp-pop-foot"><button type="button" class="kp-pop-clear">Hapus pilihan</button></div>
              </div>
            </div>

            <div class="kp-filter" data-key="marketplace">
              <button type="button" class="kp-chip"><span class="kp-chip-label">Marketplace</span><span class="kp-chip-count"></span><i class="fa fa-angle-down"></i></button>
              <div class="kp-pop">
                <?php foreach ($marketplaces as $m): ?>
                  <label class="kp-opt"><input type="checkbox" value="<?= (int) $m['id_marketplace'] ?>"><span><?= htmlspecialchars(trim($m['nama_marketplace'])) ?></span></label>
                <?php endforeach; ?>
                <div class="kp-pop-foot"><button type="button" class="kp-pop-clear">Hapus pilihan</button></div>
              </div>
            </div>

            <div class="kp-filter" data-key="sumber">
              <button type="button" class="kp-chip"><span class="kp-chip-label">Sumber</span><span class="kp-chip-count"></span><i class="fa fa-angle-down"></i></button>
              <div class="kp-pop">
                <?php foreach ($sumber_list as $s): ?>
                  <label class="kp-opt"><input type="checkbox" value="<?= htmlspecialchars($s) ?>"><span><?= htmlspecialchars($s) ?></span></label>
                <?php endforeach; ?>
                <div class="kp-pop-foot"><button type="button" class="kp-pop-clear">Hapus pilihan</button></div>
              </div>
            </div>

            <button type="button" class="kp-reset" id="kp-reset" style="display:none;"><i class="fa fa-times"></i> Reset filter</button>
          </div>

          <!-- DataTable -->
          <div class="kp-table-wrap">
            <table class="kp-table table" id="datatable-complain-management">
              <thead>
                <tr>
                  <th>ID / Tanggal</th>
                  <th>Resi / Pesanan</th>
                  <th>Toko</th>
                  <th>Kategori</th>
                  <th>Hasil CCTV</th>
                  <th class="kp-th-num">Nominal</th>
                  <th>Packer / Picker</th>
                  <th></th>
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
                  <label class="kf-label" for="no_resi">No. Resi / No. Pesanan <span class="kf-req">*</span></label>
                  <div class="kf-resi-group">
                    <input type="text" name="no_resi" id="no_resi" class="kf-input kf-input-lg" placeholder="Scan atau ketik, lalu Enter" autocomplete="off" required />
                    <button type="button" class="btn-modern" id="btn-search-resi" title="Isi data otomatis dari nomor ini"><i class="fa fa-magic"></i> Isi Otomatis</button>
                  </div>
                  <small class="kf-hint" id="resi-search-hint">Terisi otomatis: pesanan, tanggal, marketplace, toko, barang, picker, dan QC.</small>
                </div>

                <div class="kf-summary">
                  <div class="kf-summary-item">
                    <span class="kf-summary-label">No. Pesanan</span>
                    <input type="text" id="no_pesanan_tampil" class="kf-ro" readonly placeholder="&ndash;" />
                    <input type="hidden" name="no_pesanan" id="no_pesanan" />
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
                <input type="hidden" name="nama_packer" id="nama_packer" value="" />
                <div class="row kf-row-tight">
                  <div class="col-sm-6">
                    <div class="kf-field">
                      <label class="kf-label" for="nama_picker">Picker</label>
                      <select name="nama_picker" id="nama_picker" class="kf-input">
                        <option value="">Pilih</option>
                        <?php foreach ($employees as $emp): ?>
                          <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['label']) ?></option>
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
                          <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['label']) ?></option>
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

                <div class="kf-field">
                  <label class="kf-label" for="sumber_komplain">Sumber</label>
                  <select name="sumber_komplain" id="sumber_komplain" class="kf-input">
                    <option value="">Belum ditentukan</option>
                    <?php foreach ($sumber_list as $s): ?>
                      <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                  </select>
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

<!-- Drawer detail komplain (hanya lihat). Edit/Hapus membuka alur yang sudah ada. -->
<div class="modal fade" id="drawerKomplain" role="dialog" aria-labelledby="drawerKomplainLabel" aria-hidden="true">
  <div class="modal-dialog kp-dr-dialog" role="document">
    <div class="modal-content kp-dr-content">
      <div class="kf-header kp-dr-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <div class="kp-dr-titlebox">
          <span class="kp-dr-icon"><i class="fa fa-file-text-o"></i></span>
          <div>
            <h4 class="modal-title" id="drawerKomplainLabel">Komplain</h4>
            <div class="kp-dr-meta" id="kp-dr-meta"></div>
          </div>
        </div>
      </div>
      <div class="kp-dr-body" id="kp-dr-body"></div>
      <div class="kf-footer">
        <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn-modern btn-modern-secondary kp-dr-admin" id="kp-dr-hapus" style="display:none;"><i class="fa fa-trash"></i> Hapus</button>
        <button type="button" class="btn-modern kp-dr-admin" id="kp-dr-edit" style="display:none;"><i class="fa fa-pencil"></i> Edit</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts Section -->
<script>
  $(document).ready(function() {
    var userRole = parseInt("<?= $this->session->userdata('user')['hakakses'] ?? 0 ?>");
    var isAdminOrSupervisor = [1, 2].includes(userRole);
    var namaPenggunaLogin = "<?= htmlspecialchars($this->session->userdata('user')['name'] ?? '-', ENT_QUOTES) ?>";

    // Halaman ini dimuat ulang lewat AJAX (SPA): lepas handler lama di document supaya tidak menumpuk
    $(document).off('.kpnew');

    // ---------------- Daftar komplain: filter, tabel, kartu ringkasan ----------------
    var filterAktif = { kategori: [], hasil: [], jenis: [], marketplace: [], sumber: [] };

    function sertakanFilter(d) {
        d.reportrange = $('#reportrange').val();
        $.each(filterAktif, function(k, v) { d['f_' + k] = v; });
        return d;
    }

    var table = $('#datatable-complain-management').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "cs-new/complain-management/get-data",
            "type": "POST",
            "data": function(d) { sertakanFilter(d); }
        },
        "columns": [
            { "data": 0 },  // id + tanggal
            { "data": 1 },  // resi / pesanan
            { "data": 2 },  // toko + marketplace
            { "data": 3 },  // kategori
            { "data": 4 },  // hasil cctv
            { "data": 5 },  // nominal
            { "data": 6 },  // packer / picker
            { "data": 7, "orderable": false } // aksi
        ],
        "order": [[0, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "dom": 't<"kp-foot"<"kp-info"i><"kp-pager"lp>>',
        "language": {
            "emptyTable": "Tidak ada data komplain pada periode & filter ini",
            "zeroRecords": "Tidak ada data komplain pada periode & filter ini",
            "info": "Menampilkan _START_&ndash;_END_ dari _TOTAL_ komplain",
            "infoEmpty": "Menampilkan 0 komplain",
            "infoFiltered": "",
            "lengthMenu": "Baris per halaman _MENU_",
            "processing": "Memuat...",
            "paginate": { "previous": "&lsaquo;", "next": "&rsaquo;" }
        }
    });

    // Kartu ringkasan: dimuat ulang setiap tabel digambar ulang, dengan filter yang sama
    function muatRingkasan() {
        var d = sertakanFilter({});
        d.search_text = $('#kp-search').val();
        $.post('cs-new/complain-management/ringkasan', d, function(r) {
            if (!r || typeof r.total === 'undefined') return;
            $('#kpi-total').text(r.total.toLocaleString('id-ID'));
            $('#kpi-qcsalah').text(r.qc_salah.toLocaleString('id-ID'));
            var pct = r.total > 0 ? Math.round(r.qc_salah / r.total * 100) : 0;
            $('#kpi-qcsalah-note').text(pct + '% dari komplain, ' + r.qc_benar.toLocaleString('id-ID') + ' QC benar');
            $('#kpi-belum').text(r.belum_dicek.toLocaleString('id-ID'));
            $('#kpi-nominal').text('Rp ' + Math.round(r.nominal).toLocaleString('id-ID'));
        }, 'json');
    }
    table.on('draw.dt', muatRingkasan);

    // Pencarian (server-side), ditunda sebentar supaya tidak menembak tiap ketikan
    var timerCari = null;
    $('#kp-search').on('input', function() {
        var v = this.value;
        clearTimeout(timerCari);
        timerCari = setTimeout(function() { table.search(v).draw(); }, 300);
    });

    // Chip filter multi-pilih
    function perbaruiChip($f) {
        var n = filterAktif[$f.data('key')].length;
        $f.toggleClass('aktif', n > 0);
        $f.find('.kp-chip-count').text(n);
        var adaFilter = false;
        $.each(filterAktif, function(k, v) { if (v.length) adaFilter = true; });
        $('#kp-reset').toggle(adaFilter);
    }

    $(document).on('click.kpnew', '.kp-filter > .kp-chip', function(e) {
        e.stopPropagation();
        var $f = $(this).closest('.kp-filter');
        $('.kp-filter').not($f).removeClass('open');
        $f.toggleClass('open');
    });
    $(document).on('click.kpnew', function(e) {
        if (!$(e.target).closest('.kp-filter').length) $('.kp-filter').removeClass('open');
    });
    $(document).on('change.kpnew', '.kp-opt input', function() {
        var $f = $(this).closest('.kp-filter');
        filterAktif[$f.data('key')] = $f.find('.kp-opt input:checked').map(function() { return this.value; }).get();
        perbaruiChip($f);
        table.ajax.reload();
    });
    $(document).on('click.kpnew', '.kp-pop-clear', function() {
        var $f = $(this).closest('.kp-filter');
        $f.find('.kp-opt input').prop('checked', false);
        filterAktif[$f.data('key')] = [];
        perbaruiChip($f);
        table.ajax.reload();
    });
    $('#kp-reset').on('click', function() {
        $('.kp-opt input').prop('checked', false);
        $.each(filterAktif, function(k) { filterAktif[k] = []; });
        $('.kp-filter').each(function() { perbaruiChip($(this)); });
        table.ajax.reload();
    });

    // DateRangePicker
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
    $('#reportrange').on('apply.daterangepicker', function() { table.ajax.reload(); });

    // Export Excel: kirim filter yang sedang aktif lewat form POST ke iframe tersembunyi
    $('#btn-export-komplain').on('click', function() {
        var d = sertakanFilter({});
        d.search_text = $('#kp-search').val();
        var $f = $('<form method="post" class="nojs" action="cs-new/complain-management/export" target="kp-unduh" style="display:none"></form>');
        $.each(d, function(k, v) {
            if ($.isArray(v)) {
                $.each(v, function(i, x) { $f.append($('<input type="hidden">').attr('name', k + '[]').val(x)); });
            } else {
                $f.append($('<input type="hidden">').attr('name', k).val(v));
            }
        });
        if (!$('#kp-unduh').length) $('body').append('<iframe name="kp-unduh" id="kp-unduh" style="display:none"></iframe>');
        $f.appendTo('body').submit();
        setTimeout(function() { $f.remove(); }, 2000);
        showNoty('Menyiapkan file Excel sesuai filter yang aktif...', 'success');
    });

    // ---------------- Drawer detail ----------------
    function pvRow(ikon, label, nilai, salin) {
        var tombol = (salin && nilai) ? '<button type="button" class="kp-copy" data-copy="' + esc(nilai) + '" title="Salin"><i class="fa fa-files-o"></i></button>' : '';
        return '<div class="kp-dr-row"><i class="fa ' + ikon + '"></i><dt>' + label + '</dt><dd>' + esc(nilai) + tombol + '</dd></div>';
    }

    // Salin ke clipboard. Aplikasi dibuka lewat http di LAN (bukan konteks aman), jadi
    // navigator.clipboard sering tidak ada: cadangannya textarea + execCommand('copy').
    function salinTeks(teks) {
        var cadangan = function() {
            return new Promise(function(resolve, reject) {
                var ta = document.createElement('textarea');
                ta.value = teks;
                ta.setAttribute('readonly', '');
                ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(ta);
                ok ? resolve() : reject();
            });
        };
        if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
            // izin/fokus ditolak -> pakai cara cadangan
            return navigator.clipboard.writeText(teks).catch(cadangan);
        }
        return cadangan();
    }

    $(document).on('click.kpnew', '.kp-copy', function(e) {
        e.stopPropagation();
        e.preventDefault();
        var $b = $(this), teks = String($b.attr('data-copy') || '');
        if (!teks) return;
        salinTeks(teks).then(function() {
            $b.addClass('ok').attr('title', 'Tersalin').find('i').removeClass('fa-files-o').addClass('fa-check');
            setTimeout(function() { $b.removeClass('ok').attr('title', 'Salin').find('i').removeClass('fa-check').addClass('fa-files-o'); }, 1300);
        }, function() {
            showNoty('Gagal menyalin. Blok teks lalu salin manual.', 'error');
        });
    });
    var nadaHasil = { 'QC Salah': 'red', 'QC Benar': 'green', 'Tidak Terlihat CCTV': 'amber', 'CCTV Tidak Bisa Diakses': 'slate' };

    function bukaDrawer(id) {
        $.getJSON('cs-new/complain-management/detail/' + encodeURIComponent(id), function(resp) {
            if (!resp || !resp.success) {
                showNoty((resp && resp.message) || 'Gagal mengambil detail komplain.', 'error');
                return;
            }
            var c = resp.complain;
            var kode = 'K-' + ('00000' + c.id_complain).slice(-5);
            var mp = $('#id_marketplace option[value="' + c.id_marketplace + '"]').text().trim();
            var nada = nadaHasil[c.hasil_investigasi] || 'slate';
            var hasilPil = '<span class="kp-pill kp-pill-' + nada + '">' + esc(c.hasil_investigasi || 'Belum dicek') + '</span>';

            $('#drawerKomplainLabel').html('Komplain ' + kode + ' ' + hasilPil);
            $('#kp-dr-meta').text('Dibuat ' + (c.created_at ? moment(c.created_at).format('DD MMM YYYY HH:mm:ss') : '-') +
                ' oleh ' + (c.nama_penginput || '-') + (c.no_absen_input && c.no_absen_input !== '-' ? ' (' + c.no_absen_input + ')' : ''));

            var total = 0, barang = '';
            $.each(resp.items || [], function(i, it) {
                var sub = (parseInt(it.qty) || 0) * (parseFloat(it.price) || 0);
                total += sub;
                barang += '<tr><td>' + esc(it.sku) + '</td><td class="num">' + esc(it.qty) + '</td><td class="num">Rp ' +
                          (parseFloat(it.price) || 0).toLocaleString('id-ID') + '</td><td class="num">Rp ' + sub.toLocaleString('id-ID') + '</td></tr>';
            });

            var html =
                '<div class="kp-dr-sec"><h5>Pesanan</h5>' +
                    pvRow('fa-barcode', 'No. resi', c.no_resi, true) +
                    pvRow('fa-hashtag', 'No. pesanan', (c.no_pesanan || '').replace(/^SP-/i, ''), true) +
                    pvRow('fa-calendar', 'Tanggal pesanan', c.tgl_pesanan) +
                    pvRow('fa-shopping-bag', 'Marketplace', mp) +
                    pvRow('fa-building-o', 'Toko', c.toko) + '</div>' +
                '<div class="kp-dr-sec"><h5>Komplain</h5>' +
                    pvRow('fa-tag', 'Jenis', c.jenis_komplain || 'CCTV') +
                    pvRow('fa-exclamation-circle', 'Kategori', c.kategori_komplain) +
                    pvRow('fa-align-left', 'Keterangan', c.detail_lainnya) +
                    pvRow('fa-comments-o', 'Sumber', c.sumber_komplain) +
                    pvRow('fa-calendar-check-o', 'Tanggal komplain', c.tgl_komplain) + '</div>' +
                '<div class="kp-dr-sec"><h5>Barang komplain</h5><table class="kp-dr-table"><thead><tr><th>SKU</th><th class="num">Qty</th><th class="num">Harga</th><th class="num">Subtotal</th></tr></thead><tbody>' +
                    barang + '<tr class="kp-dr-total"><td colspan="3" class="num">Total</td><td class="num">Rp ' + total.toLocaleString('id-ID') + '</td></tr></tbody></table></div>' +
                '<div class="kp-dr-sec"><h5>Investigasi</h5>' +
                    pvRow('fa-video-camera', 'Hasil cek CCTV', c.hasil_investigasi || 'Belum dicek') +
                    pvRow('fa-sticky-note-o', 'Catatan', c.catatan_penanganan) +
                    pvRow('fa-user', 'Picker', labelOrang(c.nama_picker)) +
                    pvRow('fa-user', 'Packer', labelOrang(c.nama_qc || c.nama_packer)) + '</div>';

            $('#kp-dr-body').html(html).scrollTop(0);
            $('#kp-dr-edit, #kp-dr-hapus').data('id', c.id_complain).data('noresi', c.no_resi).toggle(isAdminOrSupervisor);
            $('#drawerKomplain').modal('show');
        });
    }

    $(document).on('click.kpnew', '.btn-detail-complain', function(e) { e.stopPropagation(); bukaDrawer($(this).data('id')); });
    $('#datatable-complain-management tbody').on('click', 'tr', function(e) {
        if ($(e.target).closest('button, a').length) return;
        var id = $(this).find('.btn-detail-complain').data('id');
        if (id) bukaDrawer(id);
    });
    // Edit & Hapus memakai alur yang sudah ada lewat tombol sementara di body
    function picuTombol(kelas, id, noresi) {
        $('#drawerKomplain').one('hidden.bs.modal', function() {
            var $t = $('<button type="button" style="display:none"></button>').addClass(kelas).attr('data-id', id).attr('data-noresi', noresi || '').appendTo('body');
            $t.trigger('click');
            setTimeout(function() { $t.remove(); }, 1500);
        }).modal('hide');
    }
    $('#kp-dr-edit').on('click', function() { picuTombol('btn-edit-complain', $(this).data('id')); });
    $('#kp-dr-hapus').on('click', function() { picuTombol('btn-delete-complain', $(this).data('id'), $(this).data('noresi')); });

    // ---------------- Form komplain ----------------

    // Tampilan nomor pesanan Shopee tanpa awalan "SP-"; nilai asli tetap disimpan utuh
    // supaya tetap cocok dengan data resi/Jubelio yang memakai awalan itu.
    function setNoPesanan(nilai) {
        nilai = nilai || '';
        $('#no_pesanan').val(nilai);
        $('#no_pesanan_tampil').val(nilai.replace(/^SP-/i, ''));
    }

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

    // ---------------- Isian No. Resi / No. Pesanan: scan & ketik otomatis ----------------
    // Scanner barcode mengetik sangat cepat (jeda antar karakter < 70 ms), manusia jauh lebih lambat.
    // - Scan : isi lama dibuang dulu (tidak menempel), lalu dicari segera setelah karakter terakhir.
    // - Ketik: dicari otomatis setelah berhenti mengetik sebentar, tanpa perlu Enter.
    var terakhirDicari = '';
    var sedangMencari = false;
    var timerAuto = null;
    var tsKeyTerakhir = 0, burstChars = '', burstScan = false, perluBersih = false;
    var JEDA_SCAN_MS = 70, JEDA_BURST_BARU_MS = 300, TUNGGU_SCAN_MS = 120, TUNGGU_KETIK_MS = 650;
    var teksHintAwal = $('#resi-search-hint').text();

    function hintResi(teks, galat) {
        $('#resi-search-hint').text(teks || teksHintAwal).css('color', galat ? '#b91c1c' : '');
    }

    function jalankanAuto(senyap) {
        if ($('#is_edit').val() === '1') return;
        var v = $('#no_resi').val().trim();
        if (v.length < 5 || v === terakhirDicari) return;
        if (sedangMencari) return;       // selesai nanti dicek ulang di complete
        terakhirDicari = v;
        searchResiAutoFill(senyap);
    }

    $('#no_resi').on('keydown', function(e) {
        if ($('#is_edit').val() === '1') return;
        if (e.ctrlKey || e.metaKey || e.altKey || !e.key || e.key.length !== 1) return;
        var now = Date.now(), delta = now - tsKeyTerakhir;
        tsKeyTerakhir = now;
        if (delta > JEDA_BURST_BARU_MS) {          // rangkaian ketikan baru
            burstChars = '';
            burstScan = false;
        } else if (delta < JEDA_SCAN_MS && !burstScan) {   // dua karakter beruntun secepat scanner
            burstScan = true;
            perluBersih = true;
        }
        burstChars += e.key;
    });

    $('#no_resi').on('input', function() {
        if ($('#is_edit').val() === '1') return;
        if (perluBersih) {                          // buang isi lama, sisakan hanya hasil scan
            this.value = burstChars;
            perluBersih = false;
        }
        clearTimeout(timerAuto);
        hintResi();
        timerAuto = setTimeout(function() { jalankanAuto(true); }, burstScan ? TUNGGU_SCAN_MS : TUNGGU_KETIK_MS);
    });

    // Tempel / scanner yang mengirim lewat paste: ganti seluruh isi lalu cari segera
    $('#no_resi').on('paste', function(e) {
        if ($('#is_edit').val() === '1') return;
        var cd = (e.originalEvent || e).clipboardData;
        if (!cd) return;
        e.preventDefault();
        this.value = String(cd.getData('text') || '').trim();
        clearTimeout(timerAuto);
        hintResi();
        jalankanAuto(true);
    });

    // Enter (akhiran scanner atau ketikan manual): cari segera
    $('#no_resi').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            clearTimeout(timerAuto);
            var v = $(this).val().trim();
            if (v !== '' && v === terakhirDicari) return;   // sudah dicari barusan
            terakhirDicari = v;
            searchResiAutoFill(false);
        }
    });

    $('#no_resi').on('change', function() { clearTimeout(timerAuto); jalankanAuto(false); });
    $('#modalFormKomplain').on('show.bs.modal', function() {
        terakhirDicari = '';
        burstChars = '';
        burstScan = false;
        perluBersih = false;
        clearTimeout(timerAuto);
        hintResi();
    });

    // Bersihkan hasil auto-isi sebelumnya (bukan isian CS) sebelum mengisi dari resi baru
    function bersihkanDataResi() {
        $('#no_pesanan, #nilai_pesanan, #nama_packer').val('');
        $('#no_pesanan_tampil, #tgl_pesanan').val('');
        $('#id_marketplace, #toko').prop('selectedIndex', 0);
        $('#nama_picker, #nama_qc').val('');
        $('#sku-rows-list').empty();
        $('#alert-komplain-lain').hide().html('');
    }

    function searchResiAutoFill(senyap) {
        var noresi = $('#no_resi').val().trim();
        if (noresi === '') {
            if (!senyap) showNoty('Silakan masukkan nomor resi / nomor pesanan terlebih dahulu!', 'error');
            return;
        }
        sedangMencari = true;

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
                    bersihkanDataResi();
                    hintResi();

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
                    setNoPesanan(resp.data.no_pesanan);
                    $('#tgl_pesanan').val(resp.data.tanggal_pesan || '');

                    // Nilai pesanan disimpan diam-diam (tidak ditampilkan)
                    var nilai = parseFloat(resp.data.nilai_pesanan || 0);
                    $('#nilai_pesanan').val(nilai);

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
                } else if (senyap) {
                    hintResi(resp.message, true);   // pencarian otomatis: pesan di bawah kolom, bukan notifikasi
                } else {
                    showNoty(resp.message, 'warning');
                }
            },
            error: function() {
                showNoty('Terjadi kesalahan koneksi server.', 'error');
            },
            complete: function() {
                sedangMencari = false;
                $btn.prop('disabled', false).html(originalHtml);
                // nilai di kolom berubah saat mencari (scan berikutnya): cari lagi
                if ($('#no_resi').val().trim() !== terakhirDicari) jalankanAuto(true);
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

    // "NADYA - QC - 0107" -> "0107 - NADYA" (kapital); nama tanpa no. absen tetap tampil nama saja
    function labelOrang(teks) {
        teks = $.trim(teks || '');
        if (!teks) return '';
        var p = pecahNamaUser(teks);
        var nama = p.nama.toUpperCase();
        return p.absen !== '-' ? p.absen + ' - ' + nama : nama;
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
            var tampil = (selector === '#nama_picker' || selector === '#nama_qc') ? labelOrang(value) : value;
            $(selector).append(new Option(tampil, value));
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
                    .append($('<span class="sku-suggest-kode"></span>').text(it.label))
                    .append($('<span class="sku-suggest-nama"></span>').text(it.value || ''));
                $ul.append($li);
            });
        }
        $input.closest('div').append($ul);
    }

    $(document).on('input.kpnew', '.sku-autocomplete', function() {
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

    $(document).on('click.kpnew', '.sku-suggest li', function() {
        if ($(this).hasClass('sku-suggest-kosong')) return;
        pilihSaran($(this));
    });

    $(document).on('keydown.kpnew', '.sku-autocomplete', function(e) {
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
    $(document).on('click.kpnew', function(e) {
        if (!$(e.target).closest('.sku-item-row').length) {
            $('.sku-suggest').remove();
        }
    });

    // Add row button
    $('#btn-add-sku-row').on('click', function() {
        addSkuRow('', 1, 0, '');
    });

    // Remove row button
    $(document).on('click.kpnew', '.btn-remove-sku-row', function() {
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
            barang += '<tr><td>' + esc(sku) + '</td>' +
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
                barisPv('No. resi', $('#no_resi').val()) + barisPv('No. pesanan', $('#no_pesanan_tampil').val()) +
                barisPv('Tanggal pesanan', $('#tgl_pesanan').val()) + barisPv('Marketplace', $('#id_marketplace').val() ? mp : '') +
                barisPv('Toko', $('#toko').val()) +
                barisPv('Picker', labelOrang($('#nama_picker').val())) + barisPv('Packer', labelOrang($('#nama_qc').val())) + '</div>' +
            '<div class="kf-pv-sec"><h5>Barang komplain</h5><table class="kf-pv-table"><thead><tr><th>SKU</th><th class="num">Qty</th><th class="num">Harga</th><th class="num">Subtotal</th></tr></thead><tbody>' +
                barang + '<tr><td colspan="3" class="num"><strong>Total</strong></td><td class="num"><strong>Rp ' + total.toLocaleString('id-ID') + '</strong></td></tr></tbody></table></div>' +
            '</div><div>' +
            '<div class="kf-pv-sec"><h5>Komplain</h5>' +
                barisPv('Jenis', $('input[name="jenis_komplain"]:checked').val()) + barisPv('Kategori', $('#kategori_komplain').val()) +
                barisPv('Keterangan', $('#detail_lainnya').val()) +
                barisPv('Sumber', $('#sumber_komplain').val()) + '</div>' +
            '<div class="kf-pv-sec"><h5>Investigasi</h5>' +
                barisPv('Hasil cek CCTV', $('#hasil_investigasi').val()) + barisPv('Catatan', $('#catatan_penanganan').val()) + '</div>' +
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
        $('.kf-subtitle').text(form
            ? 'Cukup isi nomor resi atau pesanan — data lain terisi otomatis dari iresis.'
            : 'Periksa ringkasan berikut, lalu simpan. Pilih Ubah untuk kembali ke form.');
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
    $(document).on('click.kpnew', '.btn-edit-complain', function() {
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

                    var packerEdit = comp.nama_qc || comp.nama_packer;
                    if (packerEdit) {
                        ensureOption('#nama_qc', packerEdit);
                        $('#nama_qc').val(packerEdit);
                    }

                    $('#nama_packer').val(comp.nama_packer || '');

                    if (comp.nama_picker) {
                        ensureOption('#nama_picker', comp.nama_picker);
                        $('#nama_picker').val(comp.nama_picker);
                    }

                    setNoPesanan(comp.no_pesanan);
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

    $(document).on('click.kpnew', '.btn-hapus-lampiran', function() {
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
    $(document).on('click.kpnew', '.btn-delete-complain', function() {
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
