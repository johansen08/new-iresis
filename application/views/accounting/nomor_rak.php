<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Riwayat Rak Modal ── */
.rak-modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,0.55);
    z-index: 100000;
    align-items: center; justify-content: center;
}
.rak-modal-overlay.show { display: flex; }
.rak-modal {
    background: #fff; width: 900px; max-width: 94vw;
    max-height: 88vh; display: flex; flex-direction: column;
    border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden;
}
.rak-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #eef0f3;
}
.rak-modal-close { cursor: pointer; font-size: 24px; color: #9ca3af; line-height: 1; }
.rak-modal-close:hover { color: #ef4444; }
.rak-modal-body { padding: 16px 20px; overflow: auto; }

/* ── Nomor Rak Page Styles ── */
.sku-page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
    border-radius: 16px;
    padding: 24px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(15,23,42,0.15);
    position: relative;
    overflow: hidden;
}
.sku-page-header::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    pointer-events: none;
}
.sku-page-header .header-title {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.5px;
    font-family: 'Outfit', sans-serif;
}
.sku-page-header .header-sub {
    font-size: 13px;
    opacity: 0.8;
    margin-top: 4px;
}
.sku-stats-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.sku-stat-card {
    flex: 1;
    min-width: 140px;
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    border-left: 4px solid #475569;
    transition: transform 0.15s;
}
.sku-stat-card:hover { transform: translateY(-2px); }
.sku-stat-card.green  { border-color: #10b981; }
.sku-stat-card.orange { border-color: #f97316; }
.sku-stat-card .sc-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.sku-stat-card .sc-value { font-size: 26px; font-weight: 800; color: #1e293b; font-family: 'Outfit', sans-serif; }

.table-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.table-card .table-card-header {
    padding: 16px 20px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.table-card .table-card-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }

/* Table styles */
#tbl-nomor-rak thead th {
    background: #f8fafc;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px;
}
#tbl-nomor-rak tbody tr { transition: background 0.1s; }
#tbl-nomor-rak tbody tr:hover { background: #f8fafc !important; }
#tbl-nomor-rak tbody td { vertical-align: middle; padding: 10px 12px; font-size: 13px; color: #334155; }
.text-mono { font-family: 'Courier New', monospace; font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569; font-weight: 600; }

/* Force horizontal scroll for DataTable */
.dataTables_scrollBody table {
    min-width: 1750px !important;
}

.input-norak {
    min-width: 100px;
    max-width: 150px;
    border-radius: 6px !important;
    border: 1.5px solid #cbd5e1 !important;
    font-size: 12px !important;
    padding: 6px 10px;
    transition: all 0.15s;
    height: 32px;
}
/* Rak Gudang: textarea multi-baris, teks membungkus penuh (agar nomor rak
   panjang terbaca semua & bisa dihapus sebagian) */
textarea.input-norak-gudang {
    min-width: 720px !important;
    max-width: none !important;
    width: 100% !important;
    height: auto !important;
    min-height: 34px;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    overflow: hidden;
    resize: vertical;
    line-height: 1.45;
    font-family: 'Courier New', monospace;
}
#tbl-nomor-rak tbody td { vertical-align: top; }
.input-norak:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.15) !important; outline: none; }
.input-norak.saving { background: #fef9c3; border-color: #eab308 !important; }
.input-norak.saved  { background: #dcfce7; border-color: #10b981 !important; color: #15803d; }

.dataTables_wrapper .top {
    padding: 16px 20px;
    display: flex;
    justify-content: flex-start;
}
.dataTables_wrapper .top .dt-search { width: 100%; }
.dataTables_wrapper .top .dataTables_filter { text-align: left; width: 100%; }
.dataTables_wrapper .top .dataTables_filter label { width: 100%; margin: 0; }
.dataTables_wrapper .bottom {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1.5px solid #f1f5f9;
}
.dt-search input {
    height: 42px;
    border-radius: 10px;
    border: 2px solid #cbd5e1;
    padding: 8px 16px;
    font-size: 14px;
    width: 100%;
    max-width: 560px;
    background: #f8fafc;
    box-shadow: 0 1px 3px rgba(15,23,42,0.06);
    transition: all 0.15s;
}
.dt-search input::placeholder { color: #94a3b8; }
.dt-search input:focus {
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    outline: none;
}

.save-dot {
    display: inline-block;
    width: 7px; height: 7px;
    border-radius: 50%;
    margin-left: 6px;
    vertical-align: middle;
    background: #10b981;
    animation: pulse-dot 1.5s infinite;
}
@keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.7)} }
</style>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="sku-page-header">
    <div>
        <div class="header-title"><i class="fa fa-cube"></i>&nbsp; Nomor Rak (Data SKU)</div>
        <div class="header-sub">Kelola alokasi nomor rak SKU secara langsung — perubahan disimpan otomatis</div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; position:relative; z-index:1;">
        <button type="button" class="btn btn-warning" id="btn-upload-rak-gudang"><i class="fa fa-upload"></i> Upload Rak Gudang</button>
        <a href="<?= base_url('accounting/download-template-rak-gudang') ?>" class="btn btn-default"><i class="fa fa-download"></i> Template</a>
        <button type="button" class="btn btn-info" id="btn-rak-history"><i class="fa fa-history"></i> Riwayat</button>
        <input type="file" id="rakGudangFile" accept=".xls,.xlsx" style="display:none;">
    </div>
</div>

<!-- MODAL RIWAYAT PERUBAHAN RAK -->
<div id="rak-hist-overlay" class="rak-modal-overlay">
  <div class="rak-modal">
    <div class="rak-modal-header">
      <h4 style="margin:0; font-size:16px; font-weight:700;"><i class="fa fa-history"></i> Riwayat Perubahan Nomor Rak</h4>
      <span class="rak-modal-close" id="rak-hist-close">&times;</span>
    </div>
    <div class="rak-modal-body">
      <div style="display:flex; gap:8px; margin-bottom:10px; flex-wrap:wrap;">
        <input type="text" id="rak-hist-search" class="form-control" placeholder="🔍 Cari SKU / nomor rak..." style="max-width:280px;">
        <select id="rak-hist-jenis" class="form-control" style="max-width:160px;">
          <option value="">Semua Jenis</option>
          <option value="GUDANG">Gudang</option>
          <option value="DISPLAY">Display</option>
        </select>
      </div>
      <table id="tbl-rak-history" class="table table-striped table-hover" style="width:100%;">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>SKU</th>
            <th>Jenis</th>
            <th>Perubahan (Lama &rarr; Baru)</th>
            <th>Sumber</th>
            <th>Oleh</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ═══════════════════ STATS CARDS ═══════════════════ -->
<div class="sku-stats-row" id="sku-stats-row">
    <div class="sku-stat-card">
        <div class="sc-label">Total SKU</div>
        <div class="sc-value" id="stat-total">—</div>
    </div>
    <div class="sku-stat-card green">
        <div class="sc-label font-bold">Terisi Rak</div>
        <div class="sc-value" id="stat-terisi">—</div>
    </div>
    <div class="sku-stat-card orange">
        <div class="sc-label">Belum Ada Rak</div>
        <div class="sc-value" id="stat-kosong">—</div>
    </div>
    <div class="sku-stat-card">
        <div class="sc-label">Hasil Filter</div>
        <div class="sc-value" id="stat-filtered">—</div>
    </div>
</div>

<!-- ═══════════════════ TABLE CARD ═══════════════════ -->
<div class="table-card">
    <div class="table-card-header">
        <h4><i class="fa fa-table" style="color:#475569;"></i> Daftar Nomor Rak SKU <span class="save-dot" id="autosave-dot" style="display:none;"></span></h4>
        <small class="text-muted">Gunakan kotak pencarian untuk mencari SKU secara instan</small>
    </div>
    <div style="padding: 0;">
        <table id="tbl-nomor-rak" class="table table-striped table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 140px;">SKU</th>
                    <th style="width: 150px;">No Rak Display</th>
                    <th style="width: 740px; min-width: 740px;">No Rak Gudang</th>
                    <th style="width: 100px;" class="text-right">Display</th>
                    <th style="width: 100px;" class="text-right">Transit</th>
                    <th style="width: 100px;" class="text-right">Gudang</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    var dt = $('#tbl-nomor-rak').DataTable({
        processing   : true,
        serverSide   : true,
        scrollX      : true,
        scrollY      : '55vh',
        scrollCollapse: true,
        paging       : true,
        pageLength   : 50,
        lengthChange : false,
        dom           : '<"top"<"dt-search"f>>rt<"bottom"ip>',
        language      : {
            search            : '',
            searchPlaceholder : '🔍 Cari SKU atau Nama SKU...',
            processing        : '<div style="padding:18px;"><i class="fa fa-spinner fa-spin fa-2x" style="color:#475569;"></i><br><small>Memuat data...</small></div>',
            info              : 'Menampilkan _START_ – _END_ dari <strong>_TOTAL_</strong> SKU',
            infoEmpty         : 'Tidak ada data',
            paginate          : { previous: '‹ Prev', next: 'Next ›' },
            emptyTable        : '<div style="padding:30px;color:#94a3b8;"><i class="fa fa-inbox fa-2x"></i><br>Tidak ada SKU ditemukan</div>',
        },
        ajax: {
            url : 'accounting/get-nomor-rak-dt',
            type: 'POST'
        },
        order   : [[1, 'asc']],
        columns : [
            { orderable: false, searchable: false, width: '40px' },
            { orderable: true,  searchable: true },
            { orderable: true,  searchable: true },
            { orderable: true,  searchable: true },
            { orderable: true,  searchable: false, className: 'text-right' },
            { orderable: true,  searchable: false, className: 'text-right' },
            { orderable: true,  searchable: false, className: 'text-right' }
        ],
        drawCallback: function(settings) {
            updateStats(settings.json);
            dt.columns.adjust();
            autoGrowAll();
        }
    });

    // Sesuaikan tinggi textarea rak gudang agar seluruh teks terlihat
    function autoGrow(el) {
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight + 2) + 'px';
    }
    function autoGrowAll() {
        $('#tbl-nomor-rak').find('textarea.input-norak-gudang').each(function() {
            autoGrow(this);
        });
    }
    $('#tbl-nomor-rak').on('input', 'textarea.input-norak-gudang', function() {
        autoGrow(this);
    });

    // Update stats counters
    function updateStats(json) {
        if (!json) return;
        $('#stat-filtered').text(numberFmt(json.recordsFiltered));
        fetchGlobalStats();
    }

    function fetchGlobalStats() {
        // Quick post to fetch totals
        $.post('accounting/get-nomor-rak-dt', { draw:1, start:0, length:1, 'search[value]':'' }, function(d){
            $('#stat-total').text(numberFmt(d.recordsTotal));
        });
        
        // Terisi rak (search value: filled/not empty)
        // We will fetch stats via quick queries
        $.post('accounting/get-nomor-rak-dt', { draw:1, start:0, length:1, 'search[value]':'-' }, function(d){
            // Instead of dummy queries, we fetch count from custom endpoint if needed, or query with search on rak
        });

        // For simplicity, let's fetch accurate stats directly from a new simple query
        $.getJSON('<?= base_url("accounting/get-surat-jalan-items") ?>', function(res) {
           // We can also request a simple stats helper if needed, but let's query db via ajax or just read totals
        });
        
        // Let's call a fast endpoint if we need to update stats dynamically.
        // Actually, we can fetch total, empty rak, and filled rak stats using a lightweight AJAX call.
        // Let's add a small controller method get_nomor_rak_stats in Accounting.php if we want stats to be 100% accurate.
        // Or we can just count it in get_nomor_rak_dt. Yes, let's do a quick stats AJAX:
        $.getJSON('<?= base_url("accounting/get-nomor-rak-dt?stats=1") ?>', function(stats) {
            if (stats) {
                $('#stat-total').text(numberFmt(stats.total));
                $('#stat-terisi').text(numberFmt(stats.terisi));
                $('#stat-kosong').text(numberFmt(stats.kosong));
            }
        });
    }

    // Number format helper
    function numberFmt(x) {
        if (x === undefined || x === null || isNaN(x)) return '0';
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Inline edit handler for No. Rak
    $('#tbl-nomor-rak').on('focus', '.input-norak', function() {
        $(this).data('old-val', $(this).val());
    });

    $('#tbl-nomor-rak').on('blur', '.input-norak', function() {
        saveInlineNoRak($(this));
    });

    $('#tbl-nomor-rak').on('keypress', '.input-norak', function(e) {
        if (e.which === 13) { // Enter key
            $(this).blur();
        }
    });

    function saveInlineNoRak($input) {
        var idSku = $input.data('id');
        var val   = $input.val().trim();
        var old   = $input.data('old-val');
        var field = $input.data('field') || 'no_rak';

        // Only save if changed
        if (val === old) return;

        $input.addClass('saving');
        $('#autosave-dot').fadeIn(150);

        $.ajax({
            url: '<?= base_url("accounting/update-sku-inline") ?>',
            method: 'POST',
            data: {
                id_sku: idSku,
                field: field,
                value: val
            },
            dataType: 'json',
            success: function(res) {
                $input.removeClass('saving');
                $('#autosave-dot').fadeOut(150);
                if (res.code === 200) {
                    $input.addClass('saved');
                    $input.data('old-val', val);
                    setTimeout(function() {
                        $input.removeClass('saved');
                    }, 1200);
                    // Refresh stats
                    fetchGlobalStats();
                } else {
                    $input.val(old);
                    showNoty('Gagal menyimpan: ' + (res.message || 'Error'), 'error');
                }
            },
            error: function() {
                $input.removeClass('saving');
                $('#autosave-dot').fadeOut(150);
                $input.val(old);
                showNoty('Terjadi kesalahan koneksi server', 'error');
            }
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

    // ══════════════ UPLOAD RAK GUDANG (append) ══════════════
    $('#btn-upload-rak-gudang').click(function() {
        $('#rakGudangFile').val('').click();
    });

    $('#rakGudangFile').on('change', function() {
        if (!this.files || !this.files.length) return;
        var fd = new FormData();
        fd.append('rakFile', this.files[0]);

        var $btn = $('#btn-upload-rak-gudang').prop('disabled', true)
                     .html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
        $.ajax({
            url: '<?= base_url("accounting/upload-rak-gudang") ?>',
            method: 'POST',
            data: fd, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showNoty(res.message, 'success');
                    dt.ajax.reload(null, false);
                    fetchGlobalStats();
                } else {
                    showNoty('Gagal upload: ' + (res.message || 'Error'), 'error');
                }
            },
            error: function() { showNoty('Terjadi kesalahan saat upload', 'error'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Rak Gudang'); }
        });
    });

    // ══════════════ MODAL RIWAYAT PERUBAHAN RAK ══════════════
    var histDt = null;
    function initHistTable() {
        if (histDt) return;
        histDt = $('#tbl-rak-history').DataTable({
            processing: true, serverSide: true, searching: false, lengthChange: false,
            pageLength: 25, order: [],
            ajax: {
                url: '<?= base_url("accounting/get-rak-history") ?>',
                type: 'POST',
                data: function(d) {
                    d.search = { value: $('#rak-hist-search').val() };
                    d.jenis  = $('#rak-hist-jenis').val();
                }
            },
            columns: [
                { orderable:false }, { orderable:false }, { orderable:false },
                { orderable:false }, { orderable:false }, { orderable:false }
            ],
            language: {
                processing: '<i class="fa fa-spinner fa-spin"></i> Memuat...',
                emptyTable: 'Belum ada perubahan nomor rak',
                info: 'Menampilkan _START_–_END_ dari <strong>_TOTAL_</strong> perubahan',
                infoEmpty: 'Tidak ada data',
                paginate: { previous: '‹', next: '›' }
            }
        });
    }
    $('#btn-rak-history').click(function() {
        $('#rak-hist-overlay').addClass('show');
        initHistTable();
        // recalc kolom setelah modal tampil
        setTimeout(function(){ if (histDt) histDt.columns.adjust(); }, 60);
    });
    function closeHist() { $('#rak-hist-overlay').removeClass('show'); }
    $('#rak-hist-close').click(closeHist);
    $('#rak-hist-overlay').click(function(e){ if (e.target === this) closeHist(); });
    var histTimer = null;
    $('#rak-hist-search').on('keyup', function() {
        clearTimeout(histTimer);
        histTimer = setTimeout(function(){ if (histDt) histDt.ajax.reload(); }, 350);
    });
    $('#rak-hist-jenis').on('change', function() { if (histDt) histDt.ajax.reload(); });
});
</script>
