<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Daftar SKU Page Styles ── */
.sku-page-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #7c3aed 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(37,99,235,0.25);
    position: relative;
    overflow: hidden;
}
.sku-page-header::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
    pointer-events: none;
}
.sku-page-header .header-title {
    font-size: 22px;
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
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border-left: 4px solid #2563eb;
    transition: transform 0.15s;
}
.sku-stat-card:hover { transform: translateY(-2px); }
.sku-stat-card.green  { border-color: #22c55e; }
.sku-stat-card.orange { border-color: #f97316; }
.sku-stat-card.purple { border-color: #7c3aed; }
.sku-stat-card .sc-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
.sku-stat-card .sc-value { font-size: 26px; font-weight: 800; color: #1e293b; font-family: 'Outfit', sans-serif; }
.filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}
.filter-bar label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; display: block; }
.filter-bar .filter-group { display: flex; flex-direction: column; }
.filter-bar .form-control { height: 36px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; }
.filter-bar .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
.bulk-bar {
    background: linear-gradient(90deg, #fef3c7, #fff7ed);
    border: 1.5px solid #fbbf24;
    border-radius: 10px;
    padding: 10px 16px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.2s ease;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.bulk-bar .bulk-count { font-weight: 700; color: #d97706; font-size: 14px; }
.table-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    overflow: hidden;
}
.table-card .table-card-header {
    padding: 14px 20px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.table-card .table-card-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }

/* Table inner styles */
#tbl-daftar-sku thead th {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff;
    border: none;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
    white-space: nowrap;
}
#tbl-daftar-sku tbody tr { transition: background 0.1s; }
#tbl-daftar-sku tbody tr:hover { background: #f0f7ff !important; }
#tbl-daftar-sku tbody td { vertical-align: middle; padding: 8px 12px; font-size: 13px; }
.select-special, .select-packing {
    min-width: 120px;
    border-radius: 6px !important;
    border: 1.5px solid #e2e8f0 !important;
    font-size: 12px !important;
    padding: 4px 6px;
    transition: border-color 0.15s;
}
.select-special:focus, .select-packing:focus { border-color: #2563eb !important; outline: none; }
.select-saving { opacity: 0.5; }
.sku-check { width: 16px; height: 16px; cursor: pointer; accent-color: #2563eb; }
.text-mono { font-family: 'Courier New', monospace; font-size: 12px; background: #f8fafc; padding: 2px 6px; border-radius: 4px; color: #475569; }

/* Input no rak */
.input-norak {
    min-width: 100px;
    max-width: 130px;
    border-radius: 6px !important;
    border: 1.5px solid #e2e8f0 !important;
    font-size: 12px !important;
    padding: 4px 8px;
    transition: border-color 0.15s, box-shadow 0.15s;
    height: 32px;
}
.input-norak:focus { border-color: #2563eb !important; box-shadow: 0 0 0 3px rgba(37,99,235,0.12) !important; outline: none; }
.input-norak.saving { background: #fef9c3; border-color: #f59e0b !important; }
.input-norak.saved  { background: #f0fdf4; border-color: #22c55e !important; }

/* ─── DataTables Horizontal Scroll Fix ─── */
.dataTables_wrapper {
    width: 100% !important;
    overflow: visible !important;
}
.dataTables_scroll {
    overflow: visible !important;
}
.dataTables_scrollHead {
    overflow: hidden !important;
    width: 100% !important;
    min-width: 0 !important;
}
.dataTables_scrollHeadInner {
    width: 100% !important;
    min-width: 900px;
}
.dataTables_scrollBody {
    overflow-x: auto !important;
    overflow-y: auto !important;
    width: 100% !important;
    -webkit-overflow-scrolling: touch;
}
.dataTables_scrollBody table {
    min-width: 900px;
    width: 100% !important;
}
/* Thin custom scrollbar */
.dataTables_scrollBody::-webkit-scrollbar { height: 7px; width: 7px; }
.dataTables_scrollBody::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.dataTables_scrollBody::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
.dataTables_scrollBody::-webkit-scrollbar-thumb:hover { background: #2563eb; }

/* Bulk Modal */
.modal-daftar-sku .modal-header {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff;
    border-radius: 8px 8px 0 0;
}
.modal-daftar-sku .modal-title { font-weight: 800; font-family: 'Outfit', sans-serif; }
.modal-daftar-sku .form-group label { font-weight: 600; color: #374151; }
.modal-daftar-sku .form-control { border-radius: 8px; }

/* Save indicator */
.save-dot {
    display: inline-block;
    width: 7px; height: 7px;
    border-radius: 50%;
    margin-left: 6px;
    vertical-align: middle;
    background: #22c55e;
    animation: pulse-dot 1.5s infinite;
}
@keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.7)} }
</style>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="sku-page-header">
    <div>
        <div class="header-title"><i class="fa fa-cube"></i>&nbsp; Daftar SKU</div>
        <div class="header-sub">Kelola status Special & Jenis Packing untuk semua SKU &mdash; update otomatis real-time</div>
    </div>
    <div>
        <button class="btn btn-light btn-sm" id="btn-select-all" style="border-radius:8px;font-weight:700;">
            <i class="fa fa-check-square-o"></i> Pilih Semua
        </button>
        &nbsp;
        <button class="btn btn-warning btn-sm" id="btn-bulk-update" style="border-radius:8px;font-weight:700;" disabled>
            <i class="fa fa-bolt"></i> Bulk Update (<span id="selected-count">0</span>)
        </button>
    </div>
</div>

<!-- ═══════════════════ STATS CARDS ═══════════════════ -->
<div class="sku-stats-row" id="sku-stats-row">
    <div class="sku-stat-card">
        <div class="sc-label">Total SKU</div>
        <div class="sc-value" id="stat-total">—</div>
    </div>
    <div class="sku-stat-card green">
        <div class="sc-label">⭐ Special</div>
        <div class="sc-value" id="stat-special">—</div>
    </div>
    <div class="sku-stat-card orange">
        <div class="sc-label">Belum Ada Packing</div>
        <div class="sc-value" id="stat-no-packing">—</div>
    </div>
    <div class="sku-stat-card purple">
        <div class="sc-label">Hasil Filter</div>
        <div class="sc-value" id="stat-filtered">—</div>
    </div>
</div>

<!-- ═══════════════════ FILTER BAR ═══════════════════ -->
<div class="filter-bar">
    <div class="filter-group">
        <label>Status Special</label>
        <select id="filter-special" class="form-control" style="min-width:150px;">
            <option value="">Semua Status</option>
            <option value="1">⭐ Special</option>
            <option value="0">Non Special</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Jenis Packing</label>
        <select id="filter-packing" class="form-control" style="min-width:160px;">
            <option value="">Semua Packing</option>
            <option value="Kardus">Kardus</option>
            <option value="Bubble Wrap">Bubble Wrap</option>
            <option value="Plastik">Plastik</option>
            <option value="Amplop">Amplop</option>
            <option value="Karung">Karung</option>
            <option value="Kayu">Kayu</option>
            <option value="Lainnya">Lainnya</option>
            <option value="kosong">Belum Diset</option>
        </select>
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="btn-apply-filter" class="btn btn-primary btn-sm" style="height:36px;border-radius:8px;font-weight:700;padding:0 18px;">
            <i class="fa fa-filter"></i> Terapkan
        </button>
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="btn-reset-filter" class="btn btn-default btn-sm" style="height:36px;border-radius:8px;padding:0 14px;">
            <i class="fa fa-times"></i> Reset
        </button>
    </div>
    <div class="filter-group" style="margin-left:auto;">
        <label>Tampilkan</label>
        <select id="filter-per-page" class="form-control" style="min-width:110px;">
            <option value="50">50 baris</option>
            <option value="100">100 baris</option>
            <option value="200">200 baris</option>
            <option value="500">500 baris</option>
            <option value="1000">1000 baris</option>
        </select>
    </div>
</div>

<!-- ═══════════════════ BULK BAR (hidden default) ═══════════════════ -->
<div class="bulk-bar" id="bulk-bar" style="display:none;">
    <i class="fa fa-bolt" style="color:#d97706;font-size:16px;"></i>
    <span class="bulk-count"><span id="bulk-count-text">0</span> SKU dipilih</span>
    <button id="btn-do-bulk" class="btn btn-warning btn-sm" style="border-radius:7px;font-weight:700;">
        <i class="fa fa-pencil"></i> Update Massal
    </button>
    <button id="btn-clear-sel" class="btn btn-default btn-sm" style="border-radius:7px;">
        <i class="fa fa-times"></i> Batal Pilih
    </button>
    <span style="margin-left:auto;font-size:12px;color:#92400e;">Klik tombol Update Massal untuk mengubah status / jenis packing sekaligus</span>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h4><i class="fa fa-table" style="color:#2563eb;"></i> Data SKU <span class="save-dot" id="autosave-dot" style="display:none;"></span></h4>
        <small class="text-muted">Perubahan status, packing &amp; no. rak tersimpan otomatis &mdash; scroll kanan/kiri untuk melihat semua kolom</small>
    </div>
    <div style="padding:0 0 4px 0;">
        <table id="tbl-daftar-sku" class="table table-striped table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th width="36px"><input type="checkbox" id="check-all" style="width:16px;height:16px;accent-color:#fff;"></th>
                    <th style="min-width:130px;">ID SKU</th>
                    <th style="min-width:220px;">Nama SKU</th>
                    <th style="min-width:150px;">Status Special</th>
                    <th style="min-width:160px;">Jenis Packing</th>
                    <th style="min-width:120px;">No. Rak</th>
                    <th style="min-width:80px;">Stok</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════ BULK MODAL ═══════════════════ -->
<div class="modal fade modal-daftar-sku" id="modal-bulk" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width:480px;">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:0.8;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-bolt"></i> Update Massal SKU</h4>
            </div>
            <div class="modal-body" style="padding:24px;">
                <div class="alert alert-info" style="border-radius:8px;font-size:13px;">
                    <i class="fa fa-info-circle"></i>
                    Anda akan mengubah <strong><span id="modal-count">0</span> SKU</strong> sekaligus. Pilih apa yang ingin diubah.
                </div>

                <div class="form-group">
                    <label>Status Special</label>
                    <select id="bulk-is-special" class="form-control" style="border-radius:8px;">
                        <option value="">-- Tidak diubah --</option>
                        <option value="1">⭐ Special</option>
                        <option value="0">Non Special</option>
                    </select>
                    <small class="text-muted">Biarkan kosong jika tidak ingin mengubah status</small>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label>Jenis Packing</label>
                    <select id="bulk-packing" class="form-control" style="border-radius:8px;">
                        <option value="__no_change__">-- Tidak diubah --</option>
                        <option value="">Hapus / Kosongkan</option>
                        <option value="Kardus">Kardus</option>
                        <option value="Bubble Wrap">Bubble Wrap</option>
                        <option value="Plastik">Plastik</option>
                        <option value="Amplop">Amplop</option>
                        <option value="Karung">Karung</option>
                        <option value="Kayu">Kayu</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <small class="text-muted">Biarkan "Tidak diubah" jika tidak ingin mengubah packing</small>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 24px;background:#f8fafc;">
                <button id="btn-confirm-bulk" class="btn btn-warning" style="border-radius:8px;font-weight:700;min-width:130px;">
                    <i class="fa fa-check"></i> Simpan Perubahan
                </button>
                <button class="btn btn-default" data-dismiss="modal" style="border-radius:8px;">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    /* ─── DataTable ─── */
    var filterSpecial = '';
    var filterPacking = '';
    var perPage = 50;

    var dt = $('#tbl-daftar-sku').DataTable({
        processing   : true,
        serverSide   : true,
        scrollX      : true,
        scrollY      : '55vh',
        scrollCollapse: true,
        paging       : true,
        pageLength   : perPage,
        lengthChange : false,
        dom           : '<"top"<"dt-search"f>>rt<"bottom"ip>',
        language      : {
            search            : '',
            searchPlaceholder : '🔍 Cari ID SKU / Nama SKU...',
            processing        : '<div style="padding:18px;"><i class="fa fa-spinner fa-spin fa-2x" style="color:#2563eb;"></i><br><small>Memuat data...</small></div>',
            info              : 'Menampilkan _START_ – _END_ dari <strong>_TOTAL_</strong> SKU',
            infoEmpty         : 'Tidak ada data',
            paginate          : { previous: '‹ Prev', next: 'Next ›' },
            emptyTable        : '<div style="padding:30px;color:#94a3b8;"><i class="fa fa-inbox fa-2x"></i><br>Tidak ada SKU ditemukan</div>',
        },
        ajax: {
            url : 'resi_team/get_daftar_sku_dt',
            type: 'POST',
            data: function(d) {
                d.filter_special = filterSpecial;
                d.filter_packing = filterPacking;
                d.length         = perPage;
            }
        },
        order   : [[1, 'asc']],
        columns : [
            { orderable: false, searchable: false, width: '36px' },
            { title: 'ID SKU',          width: '130px' },
            { title: 'Nama SKU',        width: '220px' },
            { title: 'Status Special',  width: '150px', orderable: false },
            { title: 'Jenis Packing',   width: '160px', orderable: false },
            { title: 'No. Rak',         width: '120px', orderable: true },
            { title: 'Stok',            width: '80px',  className: 'text-right' },
        ],
        drawCallback: function(settings) {
            updateStats(settings.json);
            updateBulkBar();
            // Sync header width after draw (fix scrollX head/body alignment)
            dt.columns.adjust();
        }
    });

    /* ─── Stats update ─── */
    function updateStats(json) {
        if (!json) return;
        $('#stat-filtered').text(numberFmt(json.recordsFiltered));
        if (!$('#stat-total').data('loaded')) {
            fetchGlobalStats();
            $('#stat-total').data('loaded', true);
        }
    }

    function fetchGlobalStats() {
        // Quick count calls via AJAX
        // Total
        $.post('resi_team/get_daftar_sku_dt', { draw:1, start:0, length:1, 'search[value]':'', 'order[0][column]':1, 'order[0][dir]':'asc', filter_special:'', filter_packing:'' }, function(d){
            $('#stat-total').text(numberFmt(d.recordsTotal));
        });
        // Special count
        $.post('resi_team/get_daftar_sku_dt', { draw:1, start:0, length:1, 'search[value]':'', 'order[0][column]':1, 'order[0][dir]':'asc', filter_special:'1', filter_packing:'' }, function(d){
            $('#stat-special').text(numberFmt(d.recordsTotal));
        });
        // No packing
        $.post('resi_team/get_daftar_sku_dt', { draw:1, start:0, length:1, 'search[value]':'', 'order[0][column]':1, 'order[0][dir]':'asc', filter_special:'', filter_packing:'kosong' }, function(d){
            $('#stat-no-packing').text(numberFmt(d.recordsTotal));
        });
    }

    function numberFmt(n) { return n !== undefined ? n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '—'; }

    /* ─── Filter buttons ─── */
    $('#btn-apply-filter').on('click', function() {
        filterSpecial = $('#filter-special').val();
        filterPacking = $('#filter-packing').val();
        dt.ajax.reload(null, true);
    });

    $('#btn-reset-filter').on('click', function() {
        filterSpecial = '';
        filterPacking = '';
        $('#filter-special').val('');
        $('#filter-packing').val('');
        dt.ajax.reload(null, true);
    });

    /* ─── Per page selector ─── */
    $('#filter-per-page').on('change', function() {
        perPage = parseInt($(this).val());
        dt.ajax.reload(null, true);
    });

    /* ─── Inline auto-save: Special Status ─── */
    $('#tbl-daftar-sku').on('change', '.select-special', function() {
        var $el   = $(this);
        var idSku = $el.data('id');
        var val   = $el.val();
        doInlineSave(idSku, 'is_special', val, $el);
    });

    /* ─── Inline auto-save: Jenis Packing ─── */
    $('#tbl-daftar-sku').on('change', '.select-packing', function() {
        var $el   = $(this);
        var idSku = $el.data('id');
        var val   = $el.val();
        doInlineSave(idSku, 'jenis_packing', val, $el);
    });

    /* ─── Inline auto-save: No. Rak (debounce 600ms) ─── */
    var noRakTimer = {};
    $('#tbl-daftar-sku').on('input', '.input-norak', function() {
        var $el   = $(this);
        var idSku = $el.data('id');
        var val   = $el.val();
        $el.addClass('saving').removeClass('saved');
        clearTimeout(noRakTimer[idSku]);
        noRakTimer[idSku] = setTimeout(function() {
            doInlineSave(idSku, 'no_rak', val, $el);
        }, 700);
    });

    function doInlineSave(idSku, field, value, $el) {
        var isInput = $el.is('input');
        if (!isInput) $el.addClass('select-saving');
        $('#autosave-dot').show();
        $.ajax({
            url : 'resi_team/update_sku_inline',
            type: 'POST',
            data: { id_sku: idSku, field: field, value: value },
            dataType: 'json',
            success: function(res) {
                if (res.code === 200) {
                    if (isInput) {
                        $el.removeClass('saving').addClass('saved');
                        setTimeout(function(){ $el.removeClass('saved'); }, 2000);
                    }
                    noty({ text: '✓ ' + res.message, layout: 'topRight', type: 'success', timeout: 1500 });
                    fetchGlobalStats();
                } else {
                    noty({ text: '✗ ' + res.message, layout: 'topRight', type: 'error', timeout: 3000 });
                }
            },
            error: function() {
                noty({ text: '✗ Gagal terhubung ke server', layout: 'topRight', type: 'error', timeout: 3000 });
            },
            complete: function() {
                if (!isInput) $el.removeClass('select-saving');
                setTimeout(function(){ $('#autosave-dot').hide(); }, 1200);
            }
        });
    }

    /* ─── Check-all checkbox ─── */
    $('#check-all').on('change', function() {
        var checked = $(this).is(':checked');
        $('#tbl-daftar-sku tbody .sku-check').prop('checked', checked);
        updateBulkBar();
    });

    $('#tbl-daftar-sku').on('change', '.sku-check', function() {
        updateBulkBar();
    });

    /* ─── Select All button (across all pages note) ─── */
    $('#btn-select-all').on('click', function() {
        $('#tbl-daftar-sku tbody .sku-check').prop('checked', true);
        $('#check-all').prop('checked', true);
        updateBulkBar();
    });

    function getSelectedIds() {
        var ids = [];
        $('#tbl-daftar-sku tbody .sku-check:checked').each(function(){ ids.push($(this).val()); });
        return ids;
    }

    function updateBulkBar() {
        var ids = getSelectedIds();
        var count = ids.length;
        if (count > 0) {
            $('#bulk-bar').show();
            $('#bulk-count-text').text(count);
            $('#selected-count').text(count);
            $('#btn-bulk-update').prop('disabled', false);
        } else {
            $('#bulk-bar').hide();
            $('#selected-count').text(0);
            $('#btn-bulk-update').prop('disabled', true);
        }
    }

    $('#btn-clear-sel, #btn-bulk-update[disabled]').on('click', function() {
        if ($(this).attr('id') === 'btn-clear-sel') {
            $('#tbl-daftar-sku tbody .sku-check').prop('checked', false);
            $('#check-all').prop('checked', false);
            updateBulkBar();
        }
    });

    /* ─── Open Bulk Modal ─── */
    function openBulkModal() {
        var ids = getSelectedIds();
        if (ids.length === 0) { noty({ text: 'Pilih minimal satu SKU', layout: 'topRight', type: 'warning', timeout: 2000 }); return; }
        $('#modal-count').text(ids.length);
        $('#bulk-is-special').val('');
        $('#bulk-packing').val('__no_change__');
        $('#modal-bulk').modal('show');
    }

    $('#btn-do-bulk, #btn-bulk-update').on('click', function() { openBulkModal(); });

    /* ─── Confirm Bulk Update ─── */
    $('#btn-confirm-bulk').on('click', function() {
        var ids        = getSelectedIds();
        var isSpecial  = $('#bulk-is-special').val();
        var packing    = $('#bulk-packing').val();

        if (ids.length === 0) {
            noty({ text: 'Tidak ada SKU yang dipilih', layout: 'topRight', type: 'warning', timeout: 2000 });
            return;
        }
        if (isSpecial === '' && packing === '__no_change__') {
            noty({ text: 'Pilih minimal satu field yang ingin diubah', layout: 'topRight', type: 'warning', timeout: 2500 });
            return;
        }

        var $btn = $(this);
        $btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        var postData = { 'id_skus[]': ids };

        // Build the correct format for CodeIgniter POST
        var formData = new FormData();
        ids.forEach(function(id) { formData.append('id_skus[]', id); });
        if (isSpecial !== '') formData.append('is_special', isSpecial);
        if (packing !== '__no_change__') formData.append('jenis_packing', packing);

        $.ajax({
            url        : 'resi_team/bulk_update_skus',
            type       : 'POST',
            data       : formData,
            processData: false,
            contentType: false,
            dataType   : 'json',
            success: function(res) {
                if (res.code === 200) {
                    noty({ text: '✓ ' + res.message, layout: 'topRight', type: 'success', timeout: 3000 });
                    $('#modal-bulk').modal('hide');
                    // Deselect all
                    $('#tbl-daftar-sku tbody .sku-check').prop('checked', false);
                    $('#check-all').prop('checked', false);
                    updateBulkBar();
                    // Reload table
                    dt.ajax.reload(null, false);
                    fetchGlobalStats();
                } else {
                    noty({ text: '✗ ' + res.message, layout: 'topRight', type: 'error', timeout: 3000 });
                }
            },
            error: function() {
                noty({ text: '✗ Gagal terhubung ke server', layout: 'topRight', type: 'error', timeout: 3000 });
            },
            complete: function() {
                $btn.attr('disabled', false).html('<i class="fa fa-check"></i> Simpan Perubahan');
            }
        });
    });

    /* ─── Deselect on redraw ─── */
    dt.on('draw', function() {
        $('#check-all').prop('checked', false);
        updateBulkBar();
    });

    // Custom DT search styling
    setTimeout(function() {
        $('.dt-search input').addClass('form-control').css({ 'border-radius': '8px', 'min-width': '280px' });
    }, 200);
});
</script>
