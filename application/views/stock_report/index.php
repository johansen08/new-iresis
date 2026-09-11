<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Laporan Stok TIM RESTOCK</strong></h3>
                <ul class="panel-controls">
                    <li><a href="#" class="panel-collapse"><span class="fa fa-angle-down"></span></a></li>
                    <li><a href="#" class="panel-refresh" onclick="location.reload();"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="widget widget-danger widget-item-icon" onclick="showStockDetails('empty')" style="cursor: pointer;">
                            <div class="widget-item-left">
                                <span class="fa fa-ban"></span>
                            </div>
                            <div class="widget-data">
                                <div class="widget-int num-count"><?= $empty_stock ?></div>
                                <div class="widget-title">Stok Kosong</div>
                                <div class="widget-subtitle">Barang dengan stok 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-warning widget-item-icon" onclick="showStockDetails('low')" style="cursor: pointer;">
                            <div class="widget-item-left">
                                <span class="fa fa-warning"></span>
                            </div>
                            <div class="widget-data">
                                <div class="widget-int num-count"><?= $low_stock ?></div>
                                <div class="widget-title">Stok Menipis</div>
                                <div class="widget-subtitle">Stok di bawah <?= $threshold ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="widget widget-success widget-item-icon" onclick="showStockDetails('available')" style="cursor: pointer;">
                            <div class="widget-item-left">
                                <span class="fa fa-check"></span>
                            </div>
                            <div class="widget-data">
                                <div class="widget-int num-count"><?= $available_stock ?></div>
                                <div class="widget-title">Stok Aman</div>
                                <div class="widget-subtitle">Stok di atas <?= $threshold ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr/>

                <div class="row">
                    <div class="col-md-6">
                        <h4>Setting Batas Stok Menipis</h4>
                        <form id="form_setting_stock" class="form-inline" action="stock_report/save_setting" method="post">
                            <div class="form-group">
                                <label>Batas Minimun Stok: </label>
                                <input type="number" name="threshold" class="form-control" value="<?= $threshold ?>" min="1" max="1000" />
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modal_stock_detail" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 10001;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="modal_title_detail">Detail Stok SKU</h4>
            </div>
            <div class="modal-body">
                <!-- Fitur Search -->
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-addon"><span class="fa fa-search"></span></span>
                            <input type="text" id="input_search_sku" class="form-control" placeholder="Cari ID SKU atau Nama SKU..." onkeyup="filterStockTable()"/>
                        </div>
                    </div>
                </div>

                <div style="max-height: 450px; overflow-y: auto; overflow-x: hidden; border: 1px solid #eee;">
                    <table class="table table-bordered table-striped table-condensed" id="table_stock_detail" style="margin-bottom: 0;">
                        <thead>
                            <tr style="position: sticky; top: -1px; background: #f9f9f9; z-index: 10;">
                                <th width="150">ID SKU</th>
                                <th>Nama SKU</th>
                                <th width="80">Stok</th>
                                <th width="100">No Rak</th>
                                <th width="150">Lokasi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_stock_detail">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function filterStockTable() {
        var input = document.getElementById("input_search_sku");
        var filter = input.value.toLowerCase();
        var table = document.getElementById("tbody_stock_detail");
        var tr = table.getElementsByTagName("tr");

        for (var i = 0; i < tr.length; i++) {
            var tdId = tr[i].getElementsByTagName("td")[0];
            var tdName = tr[i].getElementsByTagName("td")[1];
            if (tdId || tdName) {
                var txtValueId = tdId.textContent || tdId.innerText;
                var txtValueName = tdName.textContent || tdName.innerText;
                if (txtValueId.toLowerCase().indexOf(filter) > -1 || txtValueName.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    function showStockDetails(type) {
        var titles = {
            'empty': 'Detail Stok Kosong',
            'low': 'Detail Stok Menipis',
            'available': 'Detail Stok Aman'
        };
        $("#modal_title_detail").text(titles[type]);
        $("#tbody_stock_detail").html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
        $("#input_search_sku").val(''); // Reset search
        $("#modal_stock_detail").modal('show');

        $.ajax({
            url: "stock_report/get_details",
            type: "GET",
            data: { type: type },
            dataType: "json",
            success: function(response) {
                var html = '';
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        html += '<tr>' +
                            '<td>' + item.id_sku + '</td>' +
                            '<td>' + item.nama_sku + '</td>' +
                            '<td class="text-bold">' + item.total_stok + '</td>' +
                            '<td>' + (item.no_rak || '-') + '</td>' +
                            '<td>' + (item.lokasi || '-') + '</td>' +
                            '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">Tidak ada data.</td></tr>';
                }
                $("#tbody_stock_detail").html(html);
            },
            error: function() {
                $("#tbody_stock_detail").html('<tr><td colspan="5" class="text-center text-danger">Gagal mengambil data.</td></tr>');
            }
        });
    }

    $("#form_setting_stock").on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                // Refresh content because we are in SPA
                $(".link[href='stock_report']").click(); 
                // Or just reload
                // location.reload();
            }
        });
    });
</script>
