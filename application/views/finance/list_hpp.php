<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default" style="border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="panel-heading" style="border-radius:8px 8px 0 0; background:linear-gradient(135deg,#1a6fbf,#2196F3); border:none; padding:14px 20px;">
                <div class="row" style="display:flex; align-items:center;">
                    <div class="col-xs-8">
                        <h3 class="panel-title" style="font-size:16px; font-weight:700; color:#fff;">
                            <i class="fa fa-list"></i> &nbsp;List Data HPP
                        </h3>
                    </div>
                    <div class="col-xs-4 text-right">
                        <a href="<?= base_url('finance/upload_hpp') ?>" class="btn btn-sm btn-warning" style="font-weight:600; border-radius:5px;">
                            <i class="fa fa-upload"></i> Upload HPP
                        </a>
                        <a href="<?= base_url('finance/laporan_control_penjualan') ?>" class="btn btn-sm btn-success" style="font-weight:600; border-radius:5px; margin-left:5px;">
                            <i class="fa fa-bar-chart"></i> Laporan
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <!-- Summary cards -->
                <div class="row" id="hpp_summary_cards" style="margin-bottom:20px;">
                    <div class="col-md-4">
                        <div style="background:linear-gradient(135deg,#1a6fbf,#2196F3); border-radius:10px; padding:18px 20px; color:#fff;">
                            <div style="font-size:12px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-tags"></i> Total SKU dengan HPP</div>
                            <div style="font-size:28px; font-weight:700;" id="stat_total_sku">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="background:linear-gradient(135deg,#27ae60,#2ecc71); border-radius:10px; padding:18px 20px; color:#fff;">
                            <div style="font-size:12px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-money"></i> Rata-Rata HPP</div>
                            <div style="font-size:22px; font-weight:700;" id="stat_avg_hpp">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="background:linear-gradient(135deg,#e74c3c,#e67e22); border-radius:10px; padding:18px 20px; color:#fff;">
                            <div style="font-size:12px; opacity:0.85; margin-bottom:4px;"><i class="fa fa-cube"></i> Total Stok</div>
                            <div style="font-size:28px; font-weight:700;" id="stat_total_stok">-</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="tbl_hpp_data" style="font-size:13px;">
                        <thead>
                            <tr style="background:#1a6fbf; color:#fff;">
                                <th width="4%" class="text-center">No</th>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Bundle</th>
                                <th>Variasi</th>
                                <th class="text-right">HPP</th>
                                <th class="text-right">Total Stok</th>
                                <th>Terakhir Update</th>
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

    var table = $('#tbl_hpp_data').DataTable({
        processing:  true,
        serverSide:  true,
        ajax: {
            url:  baseUrl + 'finance/get_hpp_data',
            type: 'POST',
            data: function (d) { return $.extend({}, d, {}); }
        },
        columns: [
            { data: 0, orderable: false, className: 'text-center' },
            { data: 1 },
            { data: 2 },
            { data: 3, className: 'text-center' },
            { data: 4 },
            { data: 5, className: 'text-right', render: function(d){ return '<strong>' + d + '</strong>'; } },
            { data: 6, className: 'text-right' },
            { data: 7, className: 'text-center' },
        ],
        pageLength: 25,
        language: {
            search:      "Cari:",
            lengthMenu:  "Tampilkan _MENU_ data",
            info:        "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            paginate:    { previous: "Prev", next: "Next" },
            processing:  '<i class="fa fa-spinner fa-spin fa-2x"></i>',
        },
        drawCallback: function (settings) {
            var info = settings.json;
            if (info) {
                $('#stat_total_sku').text(settings.fnRecordsTotal().toLocaleString('id-ID'));
            }
        }
    });

    // Load summary stats
    loadSummaryStats(baseUrl);

    function loadSummaryStats(baseUrl) {
        $.ajax({
            url:  baseUrl + 'finance/get_hpp_stats',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res) {
                    $('#stat_total_sku').text(res.total_sku ? res.total_sku.toLocaleString('id-ID') : '-');
                    $('#stat_avg_hpp').text(res.avg_hpp || '-');
                    $('#stat_total_stok').text(res.total_stok ? res.total_stok.toLocaleString('id-ID') : '-');
                }
            }
        });
    }
});
</script>

<style>
#tbl_hpp_data thead tr:first-child th {
    background: #1a6fbf !important;
    color: #fff !important;
    border-color: #1565a8 !important;
}
#tbl_hpp_data tbody tr:hover {
    background: #e8f4fd !important;
}
</style>
