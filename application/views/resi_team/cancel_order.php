<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Cancel Order Page Styles ── */
.co-page-header {
    background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 60%, #f97316 100%);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 22px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(220,38,38,0.22);
    position: relative;
    overflow: hidden;
    flex-wrap: wrap;
    gap: 12px;
}
.co-page-header::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
    pointer-events: none;
}
.co-page-header .header-title {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0.5px;
    font-family: 'Outfit', sans-serif;
}
.co-page-header .header-sub { font-size: 13px; opacity: 0.85; margin-top: 4px; max-width: 640px; }
.co-page-header .header-actions { position: relative; z-index: 2; display: flex; gap: 8px; flex-wrap: wrap; }
.co-page-header .btn { border-radius: 9px; font-weight: 700; border: none; }

.co-stats-row { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
.co-stat-card {
    flex: 1;
    min-width: 150px;
    background: #fff;
    border-radius: 12px;
    padding: 15px 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border-left: 4px solid #dc2626;
}
.co-stat-card.orange { border-color: #f97316; }
.co-stat-card.blue   { border-color: #2563eb; }
.co-stat-card.green  { border-color: #22c55e; }
.co-stat-card .sc-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
.co-stat-card .sc-value { font-size: 25px; font-weight: 800; color: #1e293b; font-family: 'Outfit', sans-serif; }

.co-filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 15px 18px;
    margin-bottom: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex;
    align-items: flex-end;
    gap: 11px;
    flex-wrap: wrap;
}
.co-filter-bar label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; display: block; }
.co-filter-bar .filter-group { display: flex; flex-direction: column; }
.co-filter-bar .form-control { height: 36px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; }
.co-filter-bar .form-control:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.12); }
.co-filter-bar .btn { height: 36px; border-radius: 8px; font-weight: 700; }

.co-mp-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.co-mp-chip {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.co-mp-chip b { color: #dc2626; font-size: 13px; }

.co-table-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); overflow: hidden; }
.co-table-card .table-card-header {
    padding: 13px 20px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.co-table-card .table-card-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }

#tbl-cancel-order thead th {
    background: linear-gradient(135deg, #7f1d1d, #dc2626);
    color: #fff;
    border: none;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
    white-space: nowrap;
}
#tbl-cancel-order tbody tr:hover { background: #fff5f5 !important; }
#tbl-cancel-order tbody td { vertical-align: middle; padding: 8px 12px; font-size: 13px; }

.co-resi { font-family: 'Courier New', monospace; font-size: 12.5px; font-weight: 700; background: #f8fafc; padding: 3px 7px; border-radius: 4px; color: #1e293b; }
.co-tgl { font-weight: 600; color: #334155; font-size: 12.5px; }
.co-jam { font-size: 11.5px; color: #64748b; font-family: 'Courier New', monospace; }
.co-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; white-space: nowrap; }
.co-badge-danger  { background: #fee2e2; color: #b91c1c; }
.co-badge-warning { background: #ffedd5; color: #c2410c; }
.co-badge-info    { background: #dbeafe; color: #1d4ed8; }
.co-badge-default { background: #f1f5f9; color: #475569; }
.co-badge-jubelio { background: #ede9fe; color: #6d28d9; }
.co-badge-scan    { background: #dcfce7; color: #15803d; }

/* DataTables horizontal scroll */
.dataTables_wrapper { width: 100% !important; overflow: visible !important; }
.dataTables_scroll { overflow: visible !important; }
.dataTables_scrollHead { overflow: hidden !important; width: 100% !important; }
.dataTables_scrollHeadInner { width: 100% !important; min-width: 1000px; }
.dataTables_scrollBody { overflow-x: auto !important; overflow-y: auto !important; width: 100% !important; -webkit-overflow-scrolling: touch; }
.dataTables_scrollBody table { min-width: 1000px; width: 100% !important; }
.dataTables_scrollBody::-webkit-scrollbar { height: 7px; width: 7px; }
.dataTables_scrollBody::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.dataTables_scrollBody::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
.dataTables_scrollBody::-webkit-scrollbar-thumb:hover { background: #dc2626; }
.dt-search input { border-radius: 8px !important; border: 1.5px solid #e2e8f0 !important; height: 34px; min-width: 240px; }
</style>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="co-page-header">
    <div>
        <div class="header-title"><i class="fa fa-ban"></i> CANCEL ORDER</div>
        <div class="header-sub">
            Daftar resi yang dibatalkan &mdash; gabungan data cancel dari Jubelio dan input manual hasil scan tim resi.
        </div>
    </div>
    <div class="header-actions">
        <button id="btn-sync-jubelio" class="btn btn-light" style="background:#fff;color:#b91c1c;">
            <i class="fa fa-cloud-download"></i> Sinkron Jubelio
        </button>
        <button id="btn-export-cancel" class="btn btn-success">
            <i class="fa fa-file-excel-o"></i> Export Excel
        </button>
    </div>
</div>

<!-- ═══════════════════ STATS ═══════════════════ -->
<div class="co-stats-row">
    <div class="co-stat-card">
        <div class="sc-label">Total Cancel</div>
        <div class="sc-value" id="stat-total">&mdash;</div>
    </div>
    <div class="co-stat-card">
        <div class="sc-label">Canceled</div>
        <div class="sc-value" id="stat-canceled">&mdash;</div>
    </div>
    <div class="co-stat-card orange">
        <div class="sc-label">Request Cancel</div>
        <div class="sc-value" id="stat-request">&mdash;</div>
    </div>
    <div class="co-stat-card blue">
        <div class="sc-label">Dari Jubelio</div>
        <div class="sc-value" id="stat-jubelio">&mdash;</div>
    </div>
    <div class="co-stat-card green">
        <div class="sc-label">Input Manual (Scan)</div>
        <div class="sc-value" id="stat-scan">&mdash;</div>
    </div>
</div>

<!-- ═══════════════════ FILTER ═══════════════════ -->
<div class="co-filter-bar">
    <div class="filter-group">
        <label>Filter Berdasarkan</label>
        <select id="filter-tanggal" class="form-control" style="min-width:160px;">
            <option value="cancel">Tanggal Cancel</option>
            <option value="pesan">Tanggal Pesanan</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Dari Tanggal</label>
        <input type="text" id="co-start-date" class="form-control" style="min-width:140px;"
               value="<?= date('Y-m-d', strtotime('-7 days')) ?>" autocomplete="off">
    </div>
    <div class="filter-group">
        <label>Sampai Tanggal</label>
        <input type="text" id="co-end-date" class="form-control" style="min-width:140px;"
               value="<?= date('Y-m-d') ?>" autocomplete="off">
    </div>
    <div class="filter-group">
        <label>Marketplace</label>
        <select id="filter-marketplace" class="form-control" style="min-width:150px;">
            <option value="">Semua Marketplace</option>
            <?php foreach ($list_marketplace as $mp) : ?>
                <option value="<?= $mp['id_marketplace'] ?>"><?= htmlspecialchars($mp['nama_marketplace']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Status Marketplace</label>
        <select id="filter-status" class="form-control" style="min-width:160px;">
            <option value="">Semua Status</option>
            <option value="CANCELED">CANCELED</option>
            <option value="REQUEST_CANCEL">REQUEST_CANCEL</option>
            <option value="CANCEL MANUAL">CANCEL MANUAL</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Sumber Data</label>
        <select id="filter-sumber" class="form-control" style="min-width:140px;">
            <option value="">Semua Sumber</option>
            <option value="JUBELIO">Jubelio</option>
            <option value="SCAN">Scan Manual</option>
        </select>
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="btn-apply-filter" class="btn btn-danger" style="padding:0 18px;">
            <i class="fa fa-filter"></i> Terapkan
        </button>
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="btn-reset-filter" class="btn btn-default" style="padding:0 14px;">
            <i class="fa fa-times"></i> Reset
        </button>
    </div>
    <div class="filter-group" style="margin-left:auto;">
        <label>Tampilkan</label>
        <select id="filter-per-page" class="form-control" style="min-width:110px;">
            <option value="50">50 baris</option>
            <option value="100">100 baris</option>
            <option value="250">250 baris</option>
            <option value="500">500 baris</option>
        </select>
    </div>
</div>

<div class="co-mp-bar" id="co-mp-bar"></div>

<!-- ═══════════════════ TABEL ═══════════════════ -->
<div class="co-table-card">
    <div class="table-card-header">
        <h4><i class="fa fa-table" style="color:#dc2626;"></i> Daftar Resi Cancel</h4>
        <small class="text-muted">
            Waktu cancel data Jubelio = saat iresis pertama kali membaca status pesanan berubah jadi cancel
        </small>
    </div>
    <div style="padding:0 0 4px 0;">
        <table id="tbl-cancel-order" class="table table-striped table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th style="min-width:150px;">No. Resi</th>
                    <th style="min-width:140px;">No. Pesanan</th>
                    <th style="min-width:150px;">Marketplace / Toko</th>
                    <th style="min-width:140px;">Status Marketplace</th>
                    <th style="min-width:110px;">Tgl &amp; Jam Pesan</th>
                    <th style="min-width:110px;">Tgl &amp; Jam Cancel</th>
                    <th style="min-width:130px;">Sumber</th>
                    <th style="min-width:60px;">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {

    var perPage = 50;

    function pesan(msg, type) {
        if (typeof noty === 'function') {
            noty({ text: msg, layout: 'topRight', type: type || 'information', timeout: 4000 });
        } else {
            alert(msg);
        }
    }

    function filterPayload(d) {
        d.start_date         = $('#co-start-date').val();
        d.end_date           = $('#co-end-date').val();
        d.filter_tanggal     = $('#filter-tanggal').val();
        d.filter_marketplace = $('#filter-marketplace').val();
        d.filter_status      = $('#filter-status').val();
        d.filter_sumber      = $('#filter-sumber').val();
        return d;
    }

    var dt = $('#tbl-cancel-order').DataTable({
        processing    : true,
        serverSide    : true,
        scrollX       : true,
        scrollY       : '52vh',
        scrollCollapse: true,
        paging        : true,
        pageLength    : perPage,
        lengthChange  : false,
        dom           : '<"top"<"dt-search"f>>rt<"bottom"ip>',
        language      : {
            search            : '',
            searchPlaceholder : '🔍 Cari no. resi / no. pesanan / toko...',
            processing        : '<div style="padding:18px;"><i class="fa fa-spinner fa-spin fa-2x" style="color:#dc2626;"></i><br><small>Memuat data...</small></div>',
            info              : 'Menampilkan _START_ – _END_ dari <strong>_TOTAL_</strong> resi cancel',
            infoEmpty         : 'Tidak ada data',
            paginate          : { previous: '‹ Prev', next: 'Next ›' },
            emptyTable        : '<div style="padding:30px;color:#94a3b8;"><i class="fa fa-inbox fa-2x"></i><br>Belum ada resi cancel di rentang ini.<br><small>Klik <b>Sinkron Jubelio</b> untuk menarik data cancel terbaru.</small></div>'
        },
        ajax: {
            url : 'resi_team/get_cancel_order_data',
            type: 'POST',
            data: function (d) {
                filterPayload(d);
                d.length = perPage;
            }
        },
        order  : [[5, 'desc']],
        columns: [
            { title: 'No. Resi' },
            { title: 'No. Pesanan' },
            { title: 'Marketplace / Toko' },
            { title: 'Status Marketplace' },
            { title: 'Tgl & Jam Pesan' },
            { title: 'Tgl & Jam Cancel' },
            { title: 'Sumber', orderable: false },
            { title: 'Aksi', orderable: false, searchable: false, className: 'text-center' }
        ],
        drawCallback: function (settings) {
            updateStats(settings.json);
            dt.columns.adjust();
        }
    });

    function updateStats(json) {
        if (!json || !json.stats) return;
        var s = json.stats;
        $('#stat-total').text(Number(s.total).toLocaleString('id-ID'));
        $('#stat-canceled').text(Number(s.canceled).toLocaleString('id-ID'));
        $('#stat-request').text(Number(s.request_cancel).toLocaleString('id-ID'));
        $('#stat-jubelio').text(Number(s.dari_jubelio).toLocaleString('id-ID'));
        $('#stat-scan').text(Number(s.dari_scan).toLocaleString('id-ID'));

        var html = '';
        (json.per_marketplace || []).forEach(function (mp) {
            html += '<span class="co-mp-chip">' + $('<div>').text(mp.nama_marketplace).html()
                 + ' <b>' + Number(mp.jumlah).toLocaleString('id-ID') + '</b></span>';
        });
        $('#co-mp-bar').html(html);
    }

    /* ── Datepicker ── */
    if ($.fn.datepicker) {
        $('#co-start-date, #co-end-date').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true
        });
    }

    /* ── Filter ── */
    $('#btn-apply-filter').on('click', function () { dt.ajax.reload(); });

    $('#btn-reset-filter').on('click', function () {
        $('#filter-tanggal').val('cancel');
        $('#filter-marketplace').val('');
        $('#filter-status').val('');
        $('#filter-sumber').val('');
        $('#co-start-date').val('<?= date('Y-m-d', strtotime('-7 days')) ?>');
        $('#co-end-date').val('<?= date('Y-m-d') ?>');
        dt.ajax.reload();
    });

    $('#filter-per-page').on('change', function () {
        perPage = parseInt($(this).val(), 10) || 50;
        dt.page.len(perPage).draw();
    });

    /* ── Sinkron data cancel dari Jubelio ── */
    $('#btn-sync-jubelio').on('click', function () {
        var $btn = $(this);
        var html = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyinkron...');

        $.ajax({
            url     : 'resi_team/sync_cancel_order',
            type    : 'post',
            dataType: 'json',
            data    : filterPayload({}),
            success : function (res) {
                pesan(res.message, res.code === 200 ? 'success' : 'error');
                if (res.code === 200) dt.ajax.reload(null, false);
            },
            error   : function () { pesan('Gagal menyinkron data Jubelio', 'error'); },
            complete: function () { $btn.prop('disabled', false).html(html); }
        });
    });

    /* ── Export Excel (submit form supaya browser mengunduh file) ── */
    $('#btn-export-cancel').on('click', function () {
        var payload = filterPayload({});
        payload.search = dt.search();

        var $form = $('<form>', { method: 'POST', action: 'resi_team/export_excel_cancel_order' });
        $.each(payload, function (k, v) {
            $form.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v }));
        });
        $form.appendTo('body').submit().remove();
    });

    /* ── Hapus data cancel manual ── */
    $('#tbl-cancel-order').on('click', '.btn-hapus-cancel', function () {
        var id   = $(this).data('id');
        var resi = $(this).data('resi');
        if (!confirm('Hapus data cancel manual untuk resi ' + resi + '?')) return;

        $.ajax({
            url     : 'resi_team/hapus_cancel_order',
            type    : 'post',
            dataType: 'json',
            data    : { id_cancel: id },
            success : function (res) {
                pesan(res.message, res.code === 200 ? 'success' : 'error');
                if (res.code === 200) dt.ajax.reload(null, false);
            },
            error   : function () { pesan('Gagal menghapus data', 'error'); }
        });
    });
});
</script>
