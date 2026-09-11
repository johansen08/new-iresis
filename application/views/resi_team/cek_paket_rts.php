<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<style>
/* ===== LAYOUT ===== */
.rts-page { font-family: 'Segoe UI', sans-serif; }

/* ===== STEP CARDS ===== */
.rts-step-wrap {
    display: flex;
    gap: 18px;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.rts-step-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid #e2e8f0;
    padding: 20px 22px;
    flex: 1;
    min-width: 260px;
    position: relative;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.rts-step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 700;
    font-size: 13px;
    margin-right: 8px;
}
.rts-step-title {
    font-weight: 700;
    color: #1e293b;
    font-size: 14px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
}

/* ===== UPLOAD DROP ZONE ===== */
.rts-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8fafc;
}
.rts-dropzone:hover, .rts-dropzone.dragover {
    border-color: #f59e0b;
    background: #fffbeb;
}
.rts-dropzone .dz-icon { font-size: 2.2em; color: #94a3b8; margin-bottom: 8px; display: block; }
.rts-dropzone.has-file { border-color: #10b981; background: #f0fdf4; }
.rts-dropzone.has-file .dz-icon { color: #10b981; }

/* ===== DATE RANGE ===== */
.date-range-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.date-range-group .form-control { border-radius: 8px; }

/* ===== ANALISIS BUTTON ===== */
.btn-analisis {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 32px;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 14px rgba(245,158,11,0.35);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 9px;
    cursor: pointer;
}
.btn-analisis:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(245,158,11,0.45);
    color: #fff;
}
.btn-analisis:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* ===== PROGRESS BAR ===== */
#analisis-progress {
    display: none;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 18px 22px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
}
.prog-step { display: flex; align-items: center; gap: 10px; padding: 5px 0; color: #64748b; font-size: 13px; }
.prog-step .ps-icon { width: 22px; text-align: center; }
.prog-step.done { color: #10b981; }
.prog-step.active { color: #f59e0b; font-weight: 600; }
.prog-step.error { color: #ef4444; }

/* ===== COURIER SUMMARY ===== */
.rts-courier-wrap {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.rts-courier-card {
    background: #fff;
    border-radius: 12px;
    padding: 12px 16px;
    min-width: 180px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.rts-courier-name { font-weight: 700; color: #1e293b; font-size: 13px; margin-bottom: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.rts-courier-stats { display: flex; flex-direction: column; gap: 4px; }
.rts-courier-stat { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; }
.rts-courier-stat b { color: #1e293b; }
.rts-count-pill { padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px; }

/* ===== BADGES ===== */
.rts-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78em;
    font-weight: 600;
}
.badge-match     { background:#dcfce7; color:#166534; }
.badge-rts-only  { background:#fef9c3; color:#854d0e; }
.badge-db-only   { background:#dbeafe; color:#1e40af; }
.badge-cancelled { background:#fee2e2; color:#991b1b; }
.badge-handover  { background:#d1fae5; color:#065f46; }
.badge-progress  { background:#e0f2fe; color:#075985; }
.badge-dikemas   { background:#e9d5ff; color:#6b21a8; }

/* ===== TABLE ===== */
.rts-table-wrap {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.rts-table-wrap table { margin-bottom: 0; font-size: 0.89em; }
.rts-table-wrap thead th { background: #f1f5f9; border-bottom: 2px solid #e2e8f0; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
.rts-toolbar {
    background: #f8fafc;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    border: 1px solid #e2e8f0;
}
.rts-toolbar select.form-control { height: 32px; padding: 4px 8px; border-radius: 7px; }
</style>

<div class="rts-page">
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default" style="border-radius:16px; overflow:hidden; box-shadow:0 3px 12px rgba(0,0,0,0.07);">
            <div class="panel-heading" style="background:linear-gradient(135deg,#f59e0b,#d97706); border:none; padding:16px 22px;">
                <h3 class="panel-title" style="color:#fff; font-weight:700; font-size:16px;">
                    <i class="fa fa-search"></i>&nbsp; Cek Paket RTS
                    <small style="color:rgba(255,255,255,0.8); font-weight:400; margin-left:10px;">
                        Cocokkan data RTS Jubelio dengan laporan resi terkirim Iresis
                    </small>
                </h3>
            </div>

            <div class="panel-body" style="padding:24px;">

                <!-- ===== STEP CARDS ===== -->
                <div class="rts-step-wrap">

                    <!-- Step 1: Upload File Jubelio -->
                    <div class="rts-step-card">
                        <div class="rts-step-title">
                            <span class="rts-step-num" style="background:#fef9c3;color:#92400e;">1</span>
                            Upload File RTS dari Jubelio
                        </div>

                        <div class="rts-dropzone" id="dropzone" onclick="document.getElementById('input-rts').click()">
                            <span class="dz-icon"><i class="fa fa-cloud-upload"></i></span>
                            <div id="dz-text" style="font-weight:600; color:#64748b; font-size:13px;">
                                Klik atau seret file ke sini
                            </div>
                            <div style="color:#94a3b8; font-size:11px; margin-top:4px;">Mendukung .XLS, .XLSX, .CSV dari ekspor Jubelio</div>
                        </div>
                        <input type="file" id="input-rts" accept=".xls,.xlsx,.csv" style="display:none">

                        <div style="margin-top:15px; text-align:center;">
                            <span style="font-size:11px; color:#94a3b8;">Atau gunakan data dari database:</span><br>
                            <button id="btn-fetch-db-rts" class="btn btn-info btn-xs" style="margin-top:5px; border-radius:15px; padding:4px 12px;">
                                <i class="fa fa-database"></i> Ambil Data Ready to Ship
                            </button>
                        </div>

                        <div id="file-info" style="margin-top:12px; display:none; padding:10px 14px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span>
                                    <i class="fa fa-file-excel-o" style="color:#10b981;"></i>
                                    <strong id="file-name" style="color:#15803d;"></strong>
                                </span>
                                <span id="file-rows" class="label" style="background:#10b981; color:#fff; border-radius:20px; font-size:11px;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Filter Tanggal -->
                    <div class="rts-step-card">
                        <div class="rts-step-title">
                            <span class="rts-step-num" style="background:#dbeafe;color:#1d4ed8;">2</span>
                            Pilih Rentang Tanggal Data Iresis
                        </div>
                        <div style="margin-bottom:12px; color:#64748b; font-size:12px;">
                            <i class="fa fa-info-circle"></i>
                            Pilih periode tanggal. Sistem akan mencari resi yang di-<strong>print</strong> atau di-<strong>scan HO</strong> dalam periode tersebut.
                        </div>
                        <div class="date-range-group">
                            <div>
                                <label style="font-size:11px; color:#94a3b8; margin-bottom:3px; display:block;">DARI</label>
                                <input type="text" id="db-start-date" class="form-control" style="width:135px;" value="<?= date('Y-m-d') ?>" readonly>
                            </div>
                            <div style="padding-top:18px; color:#94a3b8;">—</div>
                            <div>
                                <label style="font-size:11px; color:#94a3b8; margin-bottom:3px; display:block;">SAMPAI</label>
                                <input type="text" id="db-end-date" class="form-control" style="width:135px;" value="<?= date('Y-m-d') ?>" readonly>
                            </div>
                        </div>
                        <div style="margin-top:12px; color:#94a3b8; font-size:11px;">
                            <i class="fa fa-lightbulb-o"></i>
                            Tips: Sesuaikan tanggal dengan periode ekspor laporan Jubelio Anda
                        </div>
                    </div>

                    <!-- Step 3: Tombol Analisis -->
                    <div class="rts-step-card" style="flex:0 0 auto; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-width:200px;">
                        <div class="rts-step-title" style="justify-content:center;">
                            <span class="rts-step-num" style="background:#fce7f3;color:#9d174d;">3</span>
                            Mulai Analisis
                        </div>
                        <button id="btn-analisis" class="btn-analisis" disabled>
                            <i class="fa fa-bolt" id="analisis-icon"></i>
                            <span id="analisis-label">Analisis</span>
                        </button>
                        <div style="margin-top:12px; color:#94a3b8; font-size:11px;" id="analisis-hint">
                            Upload file Jubelio terlebih dahulu
                        </div>
                    </div>

                </div>

                <!-- ===== PROGRESS ===== -->
                <div id="analisis-progress">
                    <div style="font-weight:700; color:#1e293b; margin-bottom:10px; font-size:13px;">
                        <i class="fa fa-refresh fa-spin" style="color:#f59e0b;"></i>&nbsp; Sedang menganalisis...
                    </div>
                    <div class="prog-step" id="ps-1">
                        <span class="ps-icon"><i class="fa fa-circle-o"></i></span>
                        <span>Membaca file Jubelio</span>
                    </div>
                    <div class="prog-step" id="ps-2">
                        <span class="ps-icon"><i class="fa fa-circle-o"></i></span>
                        <span>Memuat data resi terkirim dari database</span>
                    </div>
                    <div class="prog-step" id="ps-3">
                        <span class="ps-icon"><i class="fa fa-circle-o"></i></span>
                        <span>Mencocokkan data RTS dengan laporan iresis</span>
                    </div>
                    <div class="prog-step" id="ps-4">
                        <span class="ps-icon"><i class="fa fa-circle-o"></i></span>
                        <span>Menyiapkan hasil analisis</span>
                    </div>
                </div>

                <!-- ===== HASIL ===== -->
                <div id="hasil-section" style="display:none;">

                    <!-- Courier Summary -->
                    <div id="courier-summary" class="rts-courier-wrap"></div>

                    <!-- Toolbar -->
                    <div class="rts-toolbar">
                        <strong id="viewer-count" style="white-space:nowrap;">0 Data</strong>
                        <div style="flex:1;"></div>

                        <label style="margin:0; font-size:12px; font-weight:600; color:#64748b;">Filter:</label>
                        <select id="filter-view" class="form-control">
                            <option value="all">Semua</option>
                            <option value="match">Match Saja</option>
                            <option value="rts_only">RTS Belum di DB</option>
                            <option value="db_only">DB Tidak di RTS</option>
                        </select>

                        <select id="filter-kurir" class="form-control">
                            <option value="all">Semua Kurir</option>
                        </select>

                        <select id="filter-pagesize" class="form-control">
                            <option value="50">50/hal</option>
                            <option value="100">100/hal</option>
                            <option value="200">200/hal</option>
                            <option value="500">500/hal</option>
                        </select>

                        <button id="btn-export" class="btn btn-default btn-sm" style="white-space:nowrap;">
                            <i class="fa fa-download"></i> Ekspor CSV
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="rts-table-wrap">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center" width="4%">#</th>
                                    <th>No. Resi</th>
                                    <th>Kurir</th>
                                    <th>Tgl Cetak Resi</th>
                                    <th>Tgl Scan HO</th>
                                    <th>Status RTS (Jubelio)</th>
                                    <th class="text-center">Hasil Cocok</th>
                                </tr>
                            </thead>
                            <tbody id="result-body"></tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div style="display:flex; justify-content:center; gap:15px; align-items:center; margin-top:18px;">
                        <button id="btn-prev" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i> Prev</button>
                        <span id="page-info" class="text-muted" style="font-weight:600; font-size:13px;">Page 1 of 1</span>
                        <button id="btn-next" class="btn btn-default btn-sm">Next <i class="fa fa-chevron-right"></i></button>
                    </div>

                </div><!-- /hasil-section -->

            </div><!-- /panel-body -->
        </div><!-- /panel -->
    </div>
</div>
</div>

<script>
(function($) {

    /* ========== State ========== */
    let rtsRows  = [];   // baris dari file Jubelio
    let dbMap    = {};   // key: uppercase noresi => row dari DB
    let merged   = [];   // hasil pencocokan
    let currentPage = 1;
    const baseUrl = '<?= base_url() ?>';

    /* ========== Helper: Normalisasi Kurir ========== */
    function normalizeKurir(k, dateStr) {
        if (!k || k === '-') return '-';
        let name = k.toString().toUpperCase().trim();
        
        // Map common IDs or fragments to group name
        // 1 & 9 -> JNE, 3 & 6 -> JNT based on requested normalization
        if (name === '1' || name === '9' || name.includes('JNE')) return 'JNE';
        if (name === '3' || name === '6' || name.includes('J&T') || name.includes('JNT')) {
            if (name.includes('FIERRA')) return 'JNT-FIERRA';
            if (name.includes('KAV-DPR') || name.includes('KAV')) return 'JNT-KAV-DPR';
            if (dateStr) {
                let d = new Date(dateStr);
                if (!isNaN(d.getTime())) {
                    let formatted = d.toISOString().slice(0, 10);
                    if (formatted < '2024-05-27') return 'JNT';
                    if (formatted >= '2024-05-27' && formatted < '2025-10-13') return 'JNT-FIERRA';
                    return 'JNT-KAV-DPR';
                }
            }
            return 'JNT-KAV-DPR'; // Default
        }
        if (name === '2' || name === '7' || name.includes('SHOPEE') || name.includes('SPX')) return 'SHOPEE';
        if (name === '8' || name.includes('LAZADA') || name.includes('LEX')) return 'LAZADA';
        if (name === '10' || name === '11' || name.includes('SICEPAT')) return 'SICEPAT';
        if (name === '12' || name.includes('NINJA'))       return 'NINJA';
        if (name === '21' || name.includes('GOTO'))        return 'GOTO';
        if (name.includes('ANTERAJA'))                     return 'ANTERAJA';
        if (name.includes('ID EXPRESS'))                  return 'ID EXPRESS';
        if (name.includes('WAHANA'))                       return 'WAHANA';
        
        // Handle common garbage strings or tracking numbers (length > 13 or contains many digits)
        if (name.length > 13 || name.includes('TRUEJX') || /[A-Z]*[0-9]{8,}/.test(name)) return 'LAINNYA';
        
        return name;
    }

    /* ========== Datepicker ========== */
    if ($.fn.datepicker) {
        $('#db-start-date, #db-end-date').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true
        });
    }

    /* ========== Toast ========== */
    function toast(msg, type) {
        if (typeof noty === 'function') {
            noty({ text: msg, layout: 'topRight', type: type || 'information', timeout: 3500 });
        }
    }

    /* ========== Progress Steps ========== */
    function setStep(id, state) {
        // state: 'idle' | 'active' | 'done' | 'error'
        const $el = $('#' + id);
        $el.removeClass('done active error');
        const icons = { idle: 'fa-circle-o', active: 'fa-spinner fa-spin', done: 'fa-check-circle', error: 'fa-times-circle' };
        $el.find('.ps-icon').html('<i class="fa ' + (icons[state] || 'fa-circle-o') + '"></i>');
        if (state !== 'idle') $el.addClass(state);
    }

    /* ========== Parse File Jubelio ========== */
    // Mendukung berbagai format ekspor Jubelio:
    // "Nomor Resi" / "No Resi" / "No. Resi" / "AWB" / "Tracking"
    // "Ekspedisi" / "Kurir" / "Logistik" / "Courier"
    // "Tanggal Pesan" / "Tanggal" / "Transaction Date"
    // "Channel Status" / "Status Pengiriman" / "Status" / "RTS"
    function parseJubelioFile(file) {
        return new Promise(function(resolve) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const wb    = XLSX.read(e.target.result, { type: 'binary', raw: false });
                    const sheet = wb.Sheets[wb.SheetNames[0]];
                    const csv   = XLSX.utils.sheet_to_csv(sheet, { blankrows: false });
                    const lines = csv.split('\n');

                    // Cari baris header (25 baris pertama)
                    let hIdx = -1;
                    for (let i = 0; i < Math.min(lines.length, 25); i++) {
                        const l = lines[i].toLowerCase();
                        if (l.includes('nomor resi') || l.includes('no resi') || l.includes('no. resi') ||
                            l.includes('tracking')   || l.includes('awb')     ||
                            (l.includes('resi') && !l.includes('tanggal'))) {
                            hIdx = i; break;
                        }
                    }
                    if (hIdx === -1) { resolve([]); return; }

                    const headers = lines[hIdx].split(',').map(h => h.trim().replace(/^"|"$/g,'').toLowerCase());

                    const iResi   = headers.findIndex(h =>
                        h.includes('nomor resi') || h.includes('no resi') || h.includes('no. resi') ||
                        h.includes('tracking')   || h === 'awb' ||
                        (h.includes('resi') && !h.includes('tanggal')));
                    const iKurir  = headers.findIndex(h =>
                        (h.includes('ekspedisi') || h.includes('kurir')  ||
                         h.includes('logistik')  || h.includes('courier') || h.includes('shipping partner'))
                        && !h.includes('no') && !h.includes('resi') && !h.includes('tracking') && !h.includes('awb'));
                    const iTgl    = headers.findIndex(h =>
                        h.includes('tanggal pesan')|| h.includes('tgl pesan') ||
                        h.includes('transaction')  || h.includes('tanggal')   || h.includes('date'));
                    const iStatus = headers.findIndex(h =>
                        h.includes('channel_status') || h.includes('status pengiriman') ||
                        h.includes('shipping status')|| h === 'status'    || h === 'rts');

                    if (iResi === -1) { resolve([]); return; }

                    const rows = [];
                    for (let i = hIdx + 1; i < lines.length; i++) {
                        if (!lines[i].trim()) continue;
                        const cols = lines[i].split(',').map(c => c.trim().replace(/^"|"$/g,''));
                        const resi = (cols[iResi] || '').trim();
                        if (!resi) continue;
                        rows.push({
                            noresi:  resi,
                            kurir:   iKurir  >= 0 ? cols[iKurir]  : '-',
                            tanggal: iTgl    >= 0 ? cols[iTgl]    : '-',
                            status:  iStatus >= 0 ? cols[iStatus] : 'RTS',
                        });
                    }
                    resolve(rows);
                } catch(err) { resolve([]); }
            };
            reader.readAsBinaryString(file);
        });
    }

    /* ========== Fetch RTS from DB 직접 ========== */
    $('#btn-fetch-db-rts').on('click', async function() {
        const startDate = $('#db-start-date').val();
        const endDate   = $('#db-end-date').val();
        
        toast('Sedang mengambil data Ready to Ship dari database...', 'info');
        
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        const dbResult = await loadDbData(startDate, endDate);
        
        if (!dbResult.ok) {
            toast('Gagal memuat data: ' + dbResult.error, 'error');
            $(this).prop('disabled', false).html('<i class="fa fa-database"></i> Ambil Data Ready to Ship');
            return;
        }

        dbMap = dbResult.map;
        const list = Object.values(dbMap);
        
        // Filter for "Ready to ship" status locally
        // WP Status (status_wms) usually contains "Ready to Ship"
        rtsRows = list.filter(row => {
            const status = (row.status_wms || '').toUpperCase();
            return status.includes('READY TO SHIP') || status.includes('RTS');
        }).map(row => {
            return {
                noresi: row.noresi,
                kurir: row.kurir,
                status: row.status_wms || row.status_pesanan || 'Ready to Ship',
                tanggal: row.tanggal
            };
        });

        if (rtsRows.length === 0) {
            toast('Tidak ditemukan paket dengan status "Ready to Ship" pada rentang tanggal tersebut di database.', 'warning');
            $(this).prop('disabled', false).html('<i class="fa fa-database"></i> Ambil Data Ready to Ship');
            return;
        }

        $dropzone.addClass('has-file');
        $('#dz-text').html('<i class="fa fa-check-circle" style="color:#10b981;"></i>&nbsp; Data DB Siap (Ready to Ship)');
        $('#file-name').text('Database RTS (' + startDate + ' sd ' + endDate + ')');
        $('#file-rows').text(rtsRows.length.toLocaleString() + ' resi');
        $('#file-info').show();
        $('#btn-analisis').prop('disabled', false);
        $('#analisis-hint').text('Klik Analisis untuk memproses data RTS dari database');

        $(this).prop('disabled', false).html('<i class="fa fa-database"></i> Ambil Data Ready to Ship');
        toast(rtsRows.length.toLocaleString() + ' paket Ready to Ship berhasil ditarik dari database', 'success');
    });

    /* ========== File Upload Handling ========== */
    const $dropzone = $('#dropzone');

    $dropzone.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $dropzone.addClass('dragover');
    }).on('dragleave drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $dropzone.removeClass('dragover');
        if (e.type === 'drop') {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) handleFile(files[0]);
        }
    });

    $('#input-rts').on('change', function() {
        if (this.files.length) handleFile(this.files[0]);
    });

    async function handleFile(file) {
        // Validasi ekstensi
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xls','xlsx','csv'].includes(ext)) {
            toast('Format file tidak didukung. Gunakan XLS, XLSX, atau CSV.', 'error');
            return;
        }

        // Update UI dropzone
        $('#dz-text').html('<i class="fa fa-spinner fa-spin" style="color:#f59e0b;"></i> Membaca file...');

        rtsRows = await parseJubelioFile(file);

        if (rtsRows.length === 0) {
            $dropzone.removeClass('has-file');
            $('#dz-text').html('<span style="color:#ef4444;"><i class="fa fa-times-circle"></i> Kolom "Nomor Resi" tidak ditemukan</span>');
            $('#file-info').hide();
            $('#btn-analisis').prop('disabled', true);
            $('#analisis-hint').text('Format file tidak sesuai. Coba ekspor ulang dari Jubelio.');
            return;
        }

        $dropzone.addClass('has-file');
        $('#dz-text').html('<i class="fa fa-check-circle" style="color:#10b981;"></i>&nbsp; File siap dianalisis');
        $('#file-name').text(file.name);
        $('#file-rows').text(rtsRows.length.toLocaleString() + ' baris');
        $('#file-info').show();
        $('#btn-analisis').prop('disabled', false);
        $('#analisis-hint').text('Klik Analisis untuk memulai pencocokan data');

        toast(rtsRows.length.toLocaleString() + ' baris berhasil dibaca dari file Jubelio', 'success');
    }

    /* ========== TOMBOL ANALISIS ========== */
    $('#btn-analisis').on('click', async function() {
        if (rtsRows.length === 0) { toast('Upload file Jubelio terlebih dahulu!', 'warning'); return; }

        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#analisis-label').text('Menganalisis...');
        $('#analisis-icon').removeClass('fa-bolt').addClass('fa-spinner fa-spin');

        // Reset progress
        $('#analisis-progress').show();
        $('#hasil-section').hide();
        ['ps-1','ps-2','ps-3','ps-4'].forEach(id => setStep(id, 'idle'));

        try {
            // === Step 1: File sudah diparse ===
            setStep('ps-1', 'active');
            await delay(300);
            setStep('ps-1', 'done');

            // === Step 2: Load DB ===
            setStep('ps-2', 'active');
            const startDate = $('#db-start-date').val();
            const endDate   = $('#db-end-date').val();

            const dbResult = await loadDbData(startDate, endDate);
            if (!dbResult.ok) {
                setStep('ps-2', 'error');
                toast('Gagal memuat data dari database: ' + dbResult.error, 'error');
                resetBtn();
                return;
            }
            dbMap = dbResult.map;
            $('#stat-db').text((dbResult.total || Object.keys(dbMap).length).toLocaleString());
            setStep('ps-2', 'done');

            // === Step 3: Cocokkan ===
            setStep('ps-3', 'active');
            await delay(300);
            doMerge();
            setStep('ps-3', 'done');

            // === Step 4: Render ===
            setStep('ps-4', 'active');
            await delay(200);
            renderTable();
            setStep('ps-4', 'done');

            // Tampilkan hasil
            await delay(300);
            $('#analisis-progress').fadeOut(300);
            $('#hasil-section').fadeIn(300);

            toast('Analisis selesai! ' + merged.length.toLocaleString() + ' data diproses.', 'success');

        } catch(err) {
            toast('Terjadi kesalahan: ' + err.message, 'error');
        }

        resetBtn();
    });

    function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

    function resetBtn() {
        $('#btn-analisis').prop('disabled', rtsRows.length === 0);
        $('#analisis-label').text('Analisis');
        $('#analisis-icon').removeClass('fa-spinner fa-spin').addClass('fa-bolt');
    }

    /* ========== Load DB Data ========== */
    // make_ajax_response mengembalikan: { message, data } — tidak ada field 'status' di body JSON
    // Keberhasilan dilihat dari HTTP status code (200), bukan field JSON
    function loadDbData(startDate, endDate) {
        return new Promise(function(resolve) {
            $.ajax({
                url:      baseUrl + 'resi_team/get_resi_db_data',
                type:     'POST',
                data:     { start_date: startDate, end_date: endDate },
                dataType: 'json',
                success: function(resp) {
                    // resp = { message: '...', data: { total: N, data: [...] } }
                    const map  = {};
                    const list = (resp.data && resp.data.data) ? resp.data.data : [];
                    list.forEach(function(row) {
                        if (row.noresi) {
                            map[row.noresi.toUpperCase().trim()] = row;
                        }
                    });
                    resolve({ ok: true, map: map, total: list.length });
                },
                error: function(xhr) {
                    let errMsg = 'HTTP ' + (xhr.status || '?');
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.message) errMsg = r.message;
                    } catch(e) {}
                    resolve({ ok: false, error: errMsg, map: {} });
                }
            });
        });
    }

    /* ========== Do Merge / Pencocokan ========== */
    function doMerge() {
        merged = [];
        const rtsMap = {};
        rtsRows.forEach(r => { rtsMap[r.noresi.toUpperCase().trim()] = r; });

        let cMatch = 0, cMatchBlmHO = 0, cRtsOnly = 0, cDbOnly = 0, cBatal = 0;

        // Iterasi data dari file Jubelio
        rtsRows.forEach(function(rts) {
            const key   = rts.noresi.toUpperCase().trim();
            const dbRow = dbMap[key];
            const courierName = normalizeKurir(dbRow ? dbRow.kurir : rts.kurir, dbRow ? dbRow.tanggal : rts.tanggal);
            
            if (dbRow) {
                if (dbRow.tanggal_ho && dbRow.tanggal_ho !== '-') {
                    cMatch++; // Sdh HO
                    merged.push({
                        noresi:         rts.noresi,
                        kurir:          courierName,
                        tanggal_iresis: dbRow.tanggal    || '-',
                        tanggal_ho:     dbRow.tanggal_ho || '-',
                        status_rts:     rts.status,
                        ket:            'match',
                    });
                } else {
                    cMatchBlmHO++; 
                    merged.push({
                        noresi:         rts.noresi,
                        kurir:          courierName,
                        tanggal_iresis: dbRow.tanggal    || '-',
                        tanggal_ho:     '-',
                        status_rts:     rts.status,
                        ket:            'match_blm_ho',
                    });
                }
            } else {
                cRtsOnly++;
                merged.push({
                    noresi:         rts.noresi,
                    kurir:          normalizeKurir(rts.kurir, rts.tanggal),
                    tanggal_iresis: '-',
                    tanggal_ho:     '-',
                    status_rts:     rts.status,
                    ket:            'rts_only',
                });
            }
        });

        // Data di DB yang tidak ada di file RTS
        Object.keys(dbMap).forEach(function(key) {
            if (!rtsMap[key]) {
                cDbOnly++;
                const dbRow = dbMap[key];
                merged.push({
                    noresi:         dbRow.noresi,
                    kurir:          normalizeKurir(dbRow.kurir, dbRow.tanggal),
                    tanggal_iresis: dbRow.tanggal    || '-',
                    tanggal_ho:     dbRow.tanggal_ho || '-',
                    status_rts:     '-',
                    ket:            'db_only',
                });
            }
        });

        // Update Stats by Courier calculation
        const courierData = {};
        merged.forEach(r => {
            const c = r.kurir || 'LAINNYA';
            if (!courierData[c]) courierData[c] = { rts: 0, match: 0, rts_only: 0, db_only: 0 };
            
            if (r.ket !== 'db_only') courierData[c].rts++;
            if (r.ket === 'match')    courierData[c].match++;
            if (r.ket === 'match_blm_ho') courierData[c].match++; // count as found in DB but maybe not HO yet, or as requested
            if (r.ket === 'rts_only') courierData[c].rts_only++;
            if (r.ket === 'db_only')  courierData[c].db_only++;
        });

        // Render Courier Summary
        let summaryHtml = '';
        Object.keys(courierData).sort().forEach(c => {
            const d = courierData[c];
            if (d.rts === 0 && d.db_only === 0) return;

            summaryHtml += `
                <div class="rts-courier-card">
                    <div class="rts-courier-name">
                        <span>${c}</span>
                        <span class="label label-primary" style="font-size:10px;">${d.rts.toLocaleString()} RTS</span>
                    </div>
                    <div class="rts-courier-stats">
                        <div class="rts-courier-stat">Match <b class="text-success">${d.match.toLocaleString()}</b></div>
                        <div class="rts-courier-stat">Blm di DB <b class="text-danger">${d.rts_only.toLocaleString()}</b></div>
                    </div>
                </div>`;
        });
        $('#courier-summary').html(summaryHtml || '<div class="text-muted">Tidak ada data kurir</div>');

        // Rebuild filter kurir
        const kurirs = [...new Set(merged.map(r => r.kurir).filter(k => k && k !== '-'))].sort();
        const $sel   = $('#filter-kurir');
        $sel.html('<option value="all">Semua Kurir</option>');
        kurirs.forEach(k => $('<option>').val(k).text(k).appendTo($sel));

        currentPage = 1;
    }

    /* ========== Render Table ========== */
    function getFiltered() {
        const view  = $('#filter-view').val();
        const kurir = $('#filter-kurir').val();
        return merged.filter(r => {
            if (view  !== 'all' && r.ket  !== view)  return false;
            if (kurir !== 'all' && (r.kurir||'').toUpperCase() !== kurir.toUpperCase()) return false;
            return true;
        });
    }

    function renderTable() {
        const filtered   = getFiltered();
        const pageSize   = parseInt($('#filter-pagesize').val());
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const slice = filtered.slice(start, start + pageSize);

        $('#viewer-count').html('<b>' + filtered.length.toLocaleString() + '</b> dari ' + merged.length.toLocaleString() + ' Data');
        $('#page-info').text('Page ' + currentPage + ' of ' + totalPages);
        $('#btn-prev').prop('disabled', currentPage <= 1);
        $('#btn-next').prop('disabled', currentPage >= totalPages);

        const ketBadge = {
            match:        '<span class="label label-success" style="padding:4px 8px;"><i class="fa fa-check-circle"></i> Cocok (Sdh HO)</span>',
            match_blm_ho: '<span class="label label-warning" style="padding:4px 8px;"><i class="fa fa-print"></i> Ada (Blm HO)</span>',
            rts_only:     '<span class="label label-danger"  style="padding:4px 8px;"><i class="fa fa-times-circle"></i> Blm Ada di DB</span>',
            db_only:      '<span class="label label-info"    style="padding:4px 8px;"><i class="fa fa-database"></i> Hanya di DB</span>'
        };

        function statusBadge(s) {
            if (!s || s === '-') return '<span class="text-muted">-</span>';
            const m = {
                'BATAL':       'badge-cancelled',
                'HANDOVER':    'badge-handover',
                'DIKEMAS':     'badge-dikemas',
                'DIAMBIL':     'badge-match',
                'ON PROGRESS': 'badge-progress',
                'CANCELED':    'badge-cancelled',
            };
            return '<span class="rts-badge ' + (m[s.toUpperCase()] || 'badge-progress') + '">' + s + '</span>';
        }

        let html = '';
        slice.forEach(function(r, i) {
            const rowClass = r.ket === 'rts_only' ? 'warning' : (r.ket === 'db_only' ? '' : '');
            html += '<tr class="' + rowClass + '">'
                + '<td class="text-center text-muted" style="width:45px">' + (start + i + 1) + '</td>'
                + '<td><strong style="font-size:13px;">' + r.noresi + '</strong></td>'
                + '<td><span class="label label-default">' + (r.kurir || '-') + '</span></td>'
                + '<td class="text-muted" style="font-size:12px;">' + (r.tanggal_iresis || '-') + '</td>'
                + '<td class="text-muted" style="font-size:12px;">' + (r.tanggal_ho || '-') + '</td>'
                + '<td>' + (r.status_rts && r.status_rts !== '-' ? '<span class="label label-info">' + r.status_rts + '</span>' : '<span class="text-muted">-</span>') + '</td>'
                + '<td class="text-center">' + (ketBadge[r.ket] || '-') + '</td>'
                + '</tr>';
        });

        $('#result-body').html(html || '<tr><td colspan="7" class="text-center text-muted" style="padding:35px;"><i class="fa fa-inbox" style="font-size:2em; display:block; margin-bottom:8px;"></i>Tidak ada data</td></tr>');
    }

    /* ========== Controls ========== */
    $('#filter-view, #filter-kurir, #filter-pagesize').on('change', function() {
        currentPage = 1; renderTable();
    });
    $('#btn-prev').on('click', function() {
        if (currentPage > 1) { currentPage--; renderTable(); }
    });
    $('#btn-next').on('click', function() {
        const total = Math.ceil(getFiltered().length / parseInt($('#filter-pagesize').val()));
        if (currentPage < total) { currentPage++; renderTable(); }
    });

    /* ========== Export CSV ========== */
    $('#btn-export').on('click', function() {
        const filtered = getFiltered();
        if (!filtered.length) { toast('Tidak ada data untuk diekspor', 'warning'); return; }
        let csv = 'No,No Resi,Kurir,Tgl Cetak Resi,Tgl Scan HO,Status RTS (Jubelio),Hasil Cocok\n';
        filtered.forEach(function(r, i) {
            const ket = { match: 'Cocok (Ada di HO)', rts_only: 'Belum di HO', db_only: 'Ada di HO tapi Tidak di RTS' };
            csv += (i+1) + ',"' + r.noresi + '","' + (r.kurir||'-') + '","'
                + (r.tanggal_iresis||'-') + '","' + (r.tanggal_ho||'-') + '","'
                + (r.status_rts||'-') + '","' + (ket[r.ket]||r.ket) + '"\n';
        });
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'CekPaketRTS_' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

})(jQuery);
</script>
