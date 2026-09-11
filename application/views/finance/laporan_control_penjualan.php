<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default" style="border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="panel-heading" style="border-radius:8px 8px 0 0; background:linear-gradient(135deg,#6a1fbf,#9c27b0); border:none; padding:14px 20px;">
                <div class="row" style="display:flex; align-items:center;">
                    <div class="col-xs-8">
                        <h3 class="panel-title" style="font-size:16px; font-weight:700; color:#fff;">
                            <i class="fa fa-bar-chart"></i> &nbsp;Laporan Control Penjualan
                        </h3>
                    </div>
                    <div class="col-xs-4 text-right">
                        <a href="<?= base_url('finance/list_hpp') ?>" class="btn btn-sm btn-info" style="font-weight:600; border-radius:5px;">
                            <i class="fa fa-list"></i> List HPP
                        </a>
                        <a href="<?= base_url('finance/upload_hpp') ?>" class="btn btn-sm btn-warning" style="font-weight:600; border-radius:5px; margin-left:5px;">
                            <i class="fa fa-upload"></i> Upload HPP
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <!-- Filter Row -->
                <div class="row" style="margin-bottom:20px;">
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">Tanggal Awal</label>
                        <input type="date" id="filter_tgl_awal" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">Tanggal Akhir</label>
                        <input type="date" id="filter_tgl_akhir" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">Toko</label>
                        <select id="filter_toko" class="form-control select">
                            <option value="">Semua Toko</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">Cari SKU/Resi</label>
                        <input type="text" id="filter_search_sku" class="form-control" placeholder="SKU, Resi, Nama...">
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">Status Analisa</label>
                        <select id="filter_analisa" class="form-control select">
                            <option value="">Semua Data</option>
                            <option value="BERMASALAH">Hanya Bermasalah (Rugi/Tipis)</option>
                            <option value="RUGI">Hanya Rugi</option>
                            <option value="TIPIS">Hanya Tipis (<= 5%)</option>
                            <option value="AMAN">Hanya Aman</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label style="font-weight:600; font-size:12px; color:#555;">&nbsp;</label>
                        <div style="display:flex; gap:5px;">
                            <button id="btn_filter" class="btn btn-primary" style="background:linear-gradient(135deg,#6a1fbf,#9c27b0); border:none; font-weight:600; flex:1;">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button id="btn_analisa" class="btn btn-danger" style="background:#e74c3c; border:none; font-weight:600; flex:1;" title="Cek data bermasalah">
                                <i class="fa fa-search"></i> Analisa
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards (5 Cards) -->
                <div class="row" style="margin-bottom:20px; display: flex; flex-wrap: wrap;">
                    <div class="col-md-2" style="margin-bottom:10px; flex: 1; min-width: 180px;">
                        <div style="background:linear-gradient(135deg,#6a1fbf,#9c27b0); border-radius:10px; padding:16px 18px; color:#fff; height: 100%;">
                            <div style="font-size:11px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-file-text-o"></i> Total Resi</div>
                            <div style="font-size:22px; font-weight:700;" id="s_total_resi">-</div>
                        </div>
                    </div>
                    <div class="col-md-3" style="margin-bottom:10px; flex: 1.2; min-width: 220px;">
                        <div style="background:linear-gradient(135deg,#1a6fbf,#2196F3); border-radius:10px; padding:16px 18px; color:#fff; height: 100%;">
                            <div style="font-size:11px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-shopping-cart"></i> Total Penjualan</div>
                            <div style="font-size:22px; font-weight:700;" id="s_total_harga">-</div>
                        </div>
                    </div>
                    <div class="col-md-2" style="margin-bottom:10px; flex: 1.2; min-width: 220px;">
                        <div style="background:linear-gradient(135deg,#e67e22,#f39c12); border-radius:10px; padding:16px 18px; color:#fff; height: 100%;">
                            <div style="font-size:11px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-money"></i> Total HPP</div>
                            <div style="font-size:22px; font-weight:700;" id="s_total_hpp">-</div>
                        </div>
                    </div>
                    <div class="col-md-2" style="margin-bottom:10px; flex: 1; min-width: 200px;">
                        <div style="background:linear-gradient(135deg,#27ae60,#2ecc71); border-radius:10px; padding:16px 18px; color:#fff; height: 100%;">
                            <div style="font-size:11px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-line-chart"></i> Total Profit</div>
                            <div style="font-size:22px; font-weight:700;" id="s_total_profit">-</div>
                        </div>
                    </div>
                    <div class="col-md-3" style="margin-bottom:10px; flex: 1.5; min-width: 250px;">
                        <div id="card_bermasalah" style="background:linear-gradient(135deg,#e74c3c,#c0392b); border-radius:10px; padding:16px 18px; color:#fff; height: 100%; cursor: pointer; transition: all 0.2s ease; position: relative; overflow: hidden;">
                            <div style="font-size:11px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-exclamation-triangle"></i> Penjualan Bermasalah</div>
                            <div style="font-size:22px; font-weight:700;" id="s_total_bermasalah">-</div>
                            <div style="font-size:10px; margin-top:5px; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                <i class="fa fa-mouse-pointer"></i> Klik untuk detail
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="tbl_laporan_cp" style="font-size:11px;">
                        <thead>
                            <tr style="background:linear-gradient(135deg,#6a1fbf,#9c27b0); color:#fff;">
                                <th width="3%" class="text-center">No</th>
                                <th width="11%">No Resi</th>
                                <th width="8%">Toko</th>
                                <th width="10%">SKU</th>
                                <th>Produk</th>
                                <th width="7%">Variasi</th>
                                <th width="8%" class="text-right">Harga Jual</th>
                                <th width="8%" class="text-right">HPP</th>
                                <th width="8%" class="text-right">Profit</th>
                                <th width="5%" class="text-center">Margin</th>
                                <th width="12%" class="text-center">Status Analisa</th>
                                <th width="10%" class="text-center">Tanggal</th>
                                <th width="5%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var baseUrl = "<?= base_url() ?>";

    // Load Toko List
    $.get(baseUrl + 'finance/get_toko_list', function(res) {
        if (res && res.length > 0) {
            res.forEach(function(item) {
                $('#filter_toko').append('<option value="'+item.toko+'">'+item.toko+'</option>');
            });
        }
    });

    var table = $('#tbl_laporan_cp').DataTable({
        processing:  true,
        serverSide:  true,
        ajax: {
            url:  baseUrl + 'finance/get_laporan_data',
            type: 'POST',
            data: function (d) {
                d.tgl_awal       = $('#filter_tgl_awal').val();
                d.tgl_akhir      = $('#filter_tgl_akhir').val();
                d.search_sku     = $('#filter_search_sku').val();
                d.filter_toko    = $('#filter_toko').val();
                d.filter_analisa = $('#filter_analisa').val();
                return d;
            }
        },
        columns: [
            { data: 0, orderable: false, className: 'text-center' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, className: 'text-right' },
            { data: 7, className: 'text-right' },
            { data: 8, className: 'text-right' },
            { data: 9, className: 'text-center' },
            { data: 10, className: 'text-center' },
            { data: 11, className: 'text-center' },
            { data: 12, className: 'text-center', render: function(d) {
                var cls = 'label-default';
                if (d === 'COMPLETED' || d === 'SHIPPED') cls = 'label-success';
                if (d === 'CANCELED') cls = 'label-danger';
                if (d === 'PROCESSED') cls = 'label-info';
                if (d === 'RETURNED') cls = 'label-warning';
                return '<span class="label '+cls+'" style="font-size:10px;">'+d+'</span>';
            }},
        ],
        pageLength: 25,
        order: [[11, 'desc']],
        language: {
            search:      "Cari:",
            lengthMenu:  "Tampilkan _MENU_ data",
            info:        "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            paginate:    { previous: "Prev", next: "Next" },
            processing:  '<i class="fa fa-spinner fa-spin fa-2x"></i>',
        },
        drawCallback: function (settings) {
            updateTotals();
        }
    });

    function updateTotals() {
        $.ajax({
            url:  baseUrl + 'finance/get_laporan_totals',
            type: 'POST',
            data: {
                tgl_awal:      $('#filter_tgl_awal').val(),
                tgl_akhir:     $('#filter_tgl_akhir').val(),
                filter_toko:   $('#filter_toko').val(),
                filter_status: $('#filter_status').val(),
            },
            dataType: 'json',
            success: function (res) {
                if (res) {
                    $('#s_total_resi').text(res.total_resi);
                    $('#s_total_harga').text(res.total_harga);
                    $('#s_total_hpp').text(res.total_hpp);
                    $('#s_total_profit').text(res.total_profit);
                    $('#s_total_bermasalah').text(res.total_bermasalah + ' Resi');
                }
            }
        });
    }

    $('#btn_filter').on('click', function () {
        table.ajax.reload();
    });

    $('#btn_analisa').on('click', function () {
        $('#filter_analisa').val('BERMASALAH');
        table.ajax.reload();
    });

    // New Clickable Card Logic
    $('#card_bermasalah').on('click', function () {
        $('#filter_analisa').val('BERMASALAH');
        table.ajax.reload();
        $('html, body').animate({
            scrollTop: $("#tbl_laporan_cp").offset().top - 100
        }, 500);
    });

    $('#filter_search_sku').on('keypress', function (e) {
        if (e.which === 13) table.ajax.reload();
    });
});
</script>

<style>
#tbl_laporan_cp thead tr th {
    white-space: nowrap;
    vertical-align: middle;
}
#tbl_laporan_cp tbody tr:hover {
    background: #f5e6ff !important;
}
.label { border-radius: 4px; padding: 3px 8px; font-weight: 600; }
#card_bermasalah:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
}
</style>
