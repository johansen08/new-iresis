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
    </style>

    <!-- Header Menu -->
    <div class="modern-panel">
      <div class="modern-panel-header">
        <h3 class="modern-panel-title"><i class="fa fa-exclamation-triangle" style="color: var(--primary); margin-right: 8px;"></i> Manajemen Komplain CS</h3>
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
  <div class="modal-dialog modal-lg" role="document" style="border-radius: 12px; overflow: hidden;">
    <div class="modal-content" style="border: none; border-radius: 12px;">
      <!-- class "nojs" wajib: tanpa itu handler global di plugins.js membajak submit,
           menimpa isi halaman dengan spinner, dan data tidak pernah terkirim. -->
      <form id="form-save-complain" class="nojs" action="javascript:void(0);" enctype="multipart/form-data">
        <input type="hidden" name="is_edit" id="is_edit" value="0">
        <input type="hidden" name="id_complain" id="id_complain" value="">
        <input type="hidden" name="force_duplikat" id="force_duplikat" value="0">

        <div class="modal-header" style="border-bottom: 1px solid var(--border-slate); padding: 20px 24px;">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 28px; opacity: 0.5;"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modalFormKomplainLabel" style="font-weight: 700; color: #0f172a; font-family: 'Outfit', sans-serif;">Form Komplain</h4>
        </div>

        <div class="modal-body" style="padding: 24px;">
          <div id="alert-komplain-lain" class="alert alert-warning" style="display: none; border-radius: 8px; font-size: 13px;"></div>

          <div class="row">
            <!-- Sisi Kiri: Informasi Order & Komplain -->
            <div class="col-md-6">
              <div class="modern-form-group">
                <label class="modern-label">No. Resi / Pesanan <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="text" name="no_resi" id="no_resi" class="modern-input" style="border-top-right-radius:0; border-bottom-right-radius:0;" placeholder="Masukkan Resi atau Nomor Picklist" required />
                  <span class="input-group-btn">
                    <button type="button" class="btn btn-primary" id="btn-search-resi" style="height: 38px; border-top-left-radius:0; border-bottom-left-radius:0;" title="Auto-fill data dari resi"><i class="fa fa-magic"></i> Auto-fill</button>
                  </span>
                </div>
                <small class="text-muted" id="resi-search-hint">Scan atau ketik resi lalu Enter &mdash; nomor pesanan, tanggal pesanan, SKU, qty, nominal, marketplace, toko, picker, dan packer terisi otomatis.</small>
              </div>

              <!-- Hasil auto-fill: hanya dibaca, tidak diketik manual -->
              <div class="row">
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">No. Pesanan</label>
                    <input type="text" name="no_pesanan" id="no_pesanan" class="modern-input" readonly placeholder="terisi dari resi" />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Tanggal Pesanan</label>
                    <input type="text" name="tgl_pesanan" id="tgl_pesanan" class="modern-input" readonly placeholder="terisi dari resi" />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Nilai Pesanan</label>
                    <input type="text" id="nilai_pesanan_tampil" class="modern-input" readonly placeholder="terisi dari resi" />
                    <input type="hidden" name="nilai_pesanan" id="nilai_pesanan" />
                    <small class="text-muted" id="hint-nilai-perkiraan" style="display:none;">Harga pesanan kosong di data resi &mdash; dipakai total HPP barang.</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Diinput</label>
                    <input type="text" id="info_input" class="modern-input" readonly />
                    <small class="text-muted">Terisi otomatis dari waktu input dan akun yang login.</small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Tanggal Komplain</label>
                    <input type="date" name="tgl_komplain" id="tgl_komplain" class="modern-input" />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Sumber Komplain</label>
                    <select name="sumber_komplain" id="sumber_komplain" class="modern-select">
                      <option value="">- Belum ditentukan -</option>
                      <?php foreach ($sumber_list as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Marketplace <span class="text-danger">*</span></label>
                    <select name="id_marketplace" id="id_marketplace" class="modern-select" required>
                      <option value="" selected disabled>Pilih Marketplace</option>
                      <?php foreach ($marketplaces as $m): ?>
                        <option value="<?= $m['id_marketplace'] ?>"><?= htmlspecialchars($m['nama_marketplace']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="modern-form-group">
                    <label class="modern-label">Toko <span class="text-danger">*</span></label>
                    <select name="toko" id="toko" class="modern-select" required>
                      <option value="" selected disabled>Pilih Toko</option>
                      <?php foreach ($stores as $s): ?>
                        <option value="<?= htmlspecialchars($s['nama_toko']) ?>"><?= htmlspecialchars($s['nama_toko']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-7">
                  <div class="modern-form-group">
                    <label class="modern-label">Kategori Komplain <span class="text-danger">*</span></label>
                    <select name="kategori_komplain" id="kategori_komplain" class="modern-select" required>
                      <option value="" selected disabled>Pilih Kategori</option>
                      <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="modern-form-group">
                    <label class="modern-label">Status Penanganan</label>
                    <select name="status_penanganan" id="status_penanganan" class="modern-select">
                      <option value="">- Belum diisi -</option>
                      <?php foreach ($status_list as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="modern-form-group" id="group-detail-lainnya" style="display: none;">
                <label class="modern-label">Detail Tambahan (Lainnya) <span class="text-danger">*</span></label>
                <textarea name="detail_lainnya" id="detail_lainnya" class="modern-input" rows="3" placeholder="Tulis rincian masalah komplain..."></textarea>
              </div>

              <div class="modern-form-group">
                <label class="modern-label">Hasil Investigasi (Cek CCTV)</label>
                <select name="hasil_investigasi" id="hasil_investigasi" class="modern-select">
                  <option value="">- Belum dicek -</option>
                  <?php foreach ($hasil_list as $h): ?>
                    <option value="<?= htmlspecialchars($h) ?>"><?= htmlspecialchars($h) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="text-muted" id="hint-qc-salah" style="display: none; color: #b91c1c;">
                  <i class="fa fa-exclamation-triangle"></i>
                  <strong>QC Salah</strong> akan menambahkan 1 poin kesalahan packer ke rekapan KPI
                  (resi harus sudah pernah dipacking). Mengubahnya lagi akan mencabut poin itu.
                </small>
              </div>

              <div class="modern-form-group">
                <label class="modern-label">Catatan Penanganan</label>
                <textarea name="catatan_penanganan" id="catatan_penanganan" class="modern-input" rows="2" placeholder="Tindakan yang sudah diambil CS..."></textarea>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="modern-form-group">
                    <label class="modern-label">Nama Picker</label>
                    <select name="nama_picker" id="nama_picker" class="modern-select">
                      <option value="">Pilih Picker</option>
                      <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['nama_pegawai']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="modern-form-group">
                    <label class="modern-label">Nama Packer</label>
                    <select name="nama_packer" id="nama_packer" class="modern-select">
                      <option value="">Pilih Packer</option>
                      <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['nama_pegawai']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="modern-form-group">
                    <label class="modern-label">Nama QC</label>
                    <select name="nama_qc" id="nama_qc" class="modern-select">
                      <option value="">Pilih QC</option>
                      <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['nama_pegawai']) ?>"><?= htmlspecialchars($emp['nama_pegawai']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sisi Kanan: Input SKU & Qty Dinamis + Lampiran + Proses Banding -->
            <div class="col-md-6" style="border-left: 1px solid var(--border-slate); padding-left: 24px;">

              <!-- SKU Dynamic Rows -->
              <div class="modern-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                  <label class="modern-label" style="margin: 0;">Barang Komplain (SKU & Qty) <span class="text-danger">*</span></label>
                  <button type="button" class="btn btn-default btn-xs" id="btn-add-sku-row" style="color: var(--primary); font-weight: 600;"><i class="fa fa-plus"></i> Tambah Baris</button>
                </div>

                <div class="sku-row-container" id="sku-rows-list">
                  <!-- Baris SKU dinamis dimasukkan di sini oleh JS -->
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 8px;">
                  <span class="grand-total-label">Total Nominal Barang:</span>
                  <span class="grand-total-val" id="text-nominal-total">Rp 0</span>
                </div>
              </div>

              <!-- Lampiran Bukti -->
              <div style="border-top: 1px solid var(--border-slate); padding-top: 15px; margin-top: 15px;">
                <h5 style="font-weight: 700; color: #334155; margin-bottom: 12px;"><i class="fa fa-paperclip"></i> Lampiran Bukti</h5>
                <input type="file" name="lampiran[]" id="lampiran" class="modern-input" multiple accept=".jpg,.jpeg,.png,.gif,.pdf" style="padding: 6px;" />
                <small class="text-muted">Screenshot chat / foto barang. JPG, PNG, GIF, atau PDF. Maks 5 MB per berkas.</small>
                <div class="lampiran-list" id="lampiran-list"></div>
              </div>

              <!-- Proses Banding Section -->
              <div style="border-top: 1px solid var(--border-slate); padding-top: 15px; margin-top: 15px;">
                <h5 style="font-weight: 700; color: #334155; margin-bottom: 15px;"><i class="fa fa-balance-scale"></i> Proses Banding Logistik</h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="modern-form-group">
                      <label class="modern-label">Tgl & Jam Pengajuan</label>
                      <input type="datetime-local" name="tgl_banding_pengajuan" id="tgl_banding_pengajuan" class="modern-input" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="modern-form-group">
                      <label class="modern-label">Tgl & Jam Tinjauan</label>
                      <input type="datetime-local" name="tgl_banding_tinjauan" id="tgl_banding_tinjauan" class="modern-input" />
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="modern-form-group">
                      <label class="modern-label">Tanggal Claim Dana</label>
                      <input type="date" name="tgl_claim_dana" id="tgl_claim_dana" class="modern-input" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="modern-form-group">
                      <label class="modern-label">Nominal Claim Dana</label>
                      <input type="number" step="0.01" name="nominal_claim_dana" id="nominal_claim_dana" class="modern-input" placeholder="Rp 0" />
                    </div>
                  </div>
                </div>

                <div class="modern-form-group">
                  <label class="modern-label">Keterangan Hasil Banding</label>
                  <textarea name="keterangan_banding" id="keterangan_banding" class="modern-input" rows="2" placeholder="Tulis hasil banding logistik..."></textarea>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="modal-footer" style="border-top: 1px solid var(--border-slate); padding: 20px 24px; background-color: var(--bg-slate); border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
          <span id="role-warning-edit" class="text-warning pull-left" style="display: none; padding-top: 8px; font-size:12px; font-weight:600;"><i class="fa fa-lock"></i> Mode Read-only (Edit hanya diizinkan bagi Admin/Supervisor)</span>
          <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn-modern" id="btn-save-submit"><i class="fa fa-save"></i> Simpan Data</button>
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
            "url": "cs/complain-management/get-data",
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
                "url": "cs/complain-management/kandidat-retur",
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
            url: 'cs/complain-management/tarik-kandidat',
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
        $('#form-save-complain')[0].reset();
        $('#force_duplikat').val('0');
        $('#sku-rows-list').empty();
        $('#lampiran-list').empty();
        $('#group-detail-lainnya').hide();
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
        $('#status_penanganan').val('Baru');
        // Tanggal input & penginput terisi sendiri, tidak bisa diubah manual
        $('#info_input').val(moment().format('DD/MM/YYYY HH:mm') + ' oleh ' + namaPenggunaLogin);
        $('#nilai_pesanan_tampil').val('');
        $('#hint-nilai-perkiraan').hide();

        // Add 1 default empty row
        addSkuRow('', 1, 0, '');
        calculateNominalTotal();

        $('#modalFormKomplainLabel').text('Tambah Komplain Baru');
        $('#modalFormKomplain').modal('show');
    });

    // Peringatan dampak KPI saat hasil investigasi = QC Salah
    $('#hasil_investigasi').on('change', function() {
        if ($(this).val() === 'QC Salah') {
            $('#hint-qc-salah').slideDown();
        } else {
            $('#hint-qc-salah').slideUp();
        }
    });

    // Toggle detail lainnya textarea
    $('#kategori_komplain').on('change', function() {
        if ($(this).val() === 'Lainnya') {
            $('#group-detail-lainnya').slideDown();
            $('#detail_lainnya').prop('required', true);
        } else {
            $('#group-detail-lainnya').slideUp();
            $('#detail_lainnya').prop('required', false).val('');
        }
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

    function searchResiAutoFill() {
        var noresi = $('#no_resi').val().trim();
        if (noresi === '') {
            showNoty('Silakan masukkan nomor resi terlebih dahulu!', 'error');
            return;
        }

        var $btn = $('#btn-search-resi');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mencari...');

        $.ajax({
            url: 'cs/complain-management/search-resi',
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

                    if (resp.data.nama_packer) {
                        ensureOption('#nama_packer', resp.data.nama_packer);
                        $('#nama_packer').val(resp.data.nama_packer);
                    }

                    if (resp.data.nama_picker) {
                        ensureOption('#nama_picker', resp.data.nama_picker);
                        $('#nama_picker').val(resp.data.nama_picker);
                    }

                    // Nomor & tanggal pesanan
                    $('#no_pesanan').val(resp.data.no_pesanan || '');
                    $('#tgl_pesanan').val(resp.data.tanggal_pesan || '');

                    // Nilai pesanan
                    var nilai = parseFloat(resp.data.nilai_pesanan || 0);
                    $('#nilai_pesanan').val(nilai);
                    $('#nilai_pesanan_tampil').val('Rp ' + nilai.toLocaleString('id-ID'));
                    if (resp.data.nilai_pesanan_perkiraan) {
                        $('#hint-nilai-perkiraan').show();
                    } else {
                        $('#hint-nilai-perkiraan').hide();
                    }

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
    function addSkuRow(sku, qty, hpp, name) {
        var rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        var formattedHpp = parseFloat(hpp || 0).toFixed(2);

        var html = '<div class="sku-item-row" id="' + rowId + '">' +
                   '  <div style="flex: 3; position: relative;">' +
                   '    <input type="text" name="sku[]" class="modern-input sku-autocomplete" placeholder="Cari SKU..." value="' + sku + '" required />' +
                   '  </div>' +
                   '  <div style="flex: 1;">' +
                   '    <input type="number" name="qty[]" class="modern-input item-qty" placeholder="Qty" min="1" value="' + qty + '" required />' +
                   '  </div>' +
                   '  <div style="flex: 2;">' +
                   '    <div class="input-group">' +
                   '      <span class="input-group-addon" style="padding:6px 8px; font-size:12px;">Rp</span>' +
                   '      <input type="text" name="price[]" class="modern-input item-price" value="' + formattedHpp + '" readonly />' +
                   '    </div>' +
                   '  </div>' +
                   '  <div>' +
                   '    <button type="button" class="btn-icon btn-icon-danger btn-remove-sku-row" title="Hapus Baris"><i class="fa fa-times"></i></button>' +
                   '  </div>' +
                   '</div>';

        $('#sku-rows-list').append(html);

        // Listener qty; saran SKU ditangani lewat event delegation di bawah
        $('#' + rowId).find('.item-qty').on('input change', function() {
            calculateNominalTotal();
        });
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
                url: 'cs/complain-management/get-sku-suggestions',
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
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            total += (qty * price);
        });

        // Format Currency
        var formatted = 'Rp ' + total.toLocaleString('id-ID');
        $('#text-nominal-total').text(formatted);
    }

    // Submit form action (Save / Edit)
    $('#form-save-complain').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        kirimFormKomplain();
    });

    function kirimFormKomplain() {
        var $btn = $('#btn-save-submit');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        var formData = new FormData($('#form-save-complain')[0]);

        $.ajax({
            url: 'cs/complain-management/save',
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
            url: 'cs/complain-management/detail/' + encodeURIComponent(id),
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var comp = resp.complain;

                    $('#no_resi').val(comp.no_resi);
                    $('#tgl_komplain').val(comp.tgl_komplain || '');
                    $('#sumber_komplain').val(comp.sumber_komplain || '');
                    $('#status_penanganan').val(comp.status_penanganan || '');
                    $('#catatan_penanganan').val(comp.catatan_penanganan || '');
                    $('#hasil_investigasi').val(comp.hasil_investigasi || '');
                    if (comp.hasil_investigasi === 'QC Salah') {
                        $('#hint-qc-salah').show();
                    }
                    $('#id_marketplace').val(comp.id_marketplace);

                    ensureOption('#toko', comp.toko);
                    $('#toko').val(comp.toko);

                    $('#kategori_komplain').val(comp.kategori_komplain);
                    if (comp.kategori_komplain === 'Lainnya') {
                        $('#group-detail-lainnya').show();
                        $('#detail_lainnya').val(comp.detail_lainnya).prop('required', true);
                    }

                    if (comp.nama_qc) {
                        ensureOption('#nama_qc', comp.nama_qc);
                        $('#nama_qc').val(comp.nama_qc);
                    }

                    if (comp.nama_packer) {
                        ensureOption('#nama_packer', comp.nama_packer);
                        $('#nama_packer').val(comp.nama_packer);
                    }

                    if (comp.nama_picker) {
                        ensureOption('#nama_picker', comp.nama_picker);
                        $('#nama_picker').val(comp.nama_picker);
                    }

                    $('#no_pesanan').val(comp.no_pesanan || '');
                    $('#tgl_pesanan').val(comp.tgl_pesanan || '');

                    var nilaiEdit = parseFloat(comp.nilai_pesanan || 0);
                    $('#nilai_pesanan').val(comp.nilai_pesanan || '');
                    $('#nilai_pesanan_tampil').val(nilaiEdit > 0 ? 'Rp ' + nilaiEdit.toLocaleString('id-ID') : '');

                    // Tanggal input & penginput asli, bukan waktu sekarang
                    var tglInput = comp.created_at ? moment(comp.created_at).format('DD/MM/YYYY HH:mm') : '-';
                    $('#info_input').val(tglInput + ' oleh ' + (comp.nama_penginput || '-'));

                    // Banding inputs
                    if (comp.tgl_banding_pengajuan) {
                        $('#tgl_banding_pengajuan').val(comp.tgl_banding_pengajuan.replace(' ', 'T').substring(0, 16));
                    }
                    if (comp.tgl_banding_tinjauan) {
                        $('#tgl_banding_tinjauan').val(comp.tgl_banding_tinjauan.replace(' ', 'T').substring(0, 16));
                    }
                    if (comp.tgl_claim_dana) {
                        $('#tgl_claim_dana').val(comp.tgl_claim_dana);
                    }
                    if (comp.nominal_claim_dana) {
                        $('#nominal_claim_dana').val(comp.nominal_claim_dana);
                    }
                    if (comp.keterangan_banding) {
                        $('#keterangan_banding').val(comp.keterangan_banding);
                    }

                    // SKU rows
                    if (resp.items && resp.items.length > 0) {
                        $.each(resp.items, function(idx, item) {
                            addSkuRow(item.sku, item.qty, item.price, item.nama_sku);
                        });
                    } else {
                        addSkuRow('', 1, 0, '');
                    }

                    calculateNominalTotal();
                    renderLampiran(resp.lampiran);
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
            url: 'cs/complain-management/delete-lampiran/' + encodeURIComponent(id),
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
                url: 'cs/complain-management/delete/' + encodeURIComponent(id),
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
