<div class="row">
    <!-- Single Top Stat -->
    <div class="col-md-12">
        <div class="widget widget-primary widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-truck"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="total-resi-active">0</div>
                <div class="widget-title">TOTAL RESI</div>
                <div class="widget-subtitle"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Selisih Paket</strong></h3>
                <ul class="panel-controls">
                    <li><a href="#" class="btn btn-success" id="btn-export" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-file-excel-o"></span> EXPORT EXCEL</small></a></li>
                    <li><a href="#" class="panel-refresh" id="refresh-data"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- Pool 1: Resi to Picker -->
                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">RESI KE PICKER <br><small>(Belum Ambil Barang)</small></h3>
                            </div>
                            <div class="panel-body" style="padding: 0;">
                                <table class="table table-bordered" style="margin-bottom: 0;">
                                    <tbody id="p1-indicators">
                                        <!-- Will be filled by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f1f1f1; font-weight: bold;">
                                            <td>GRAND TOTAL</td>
                                            <td class="text-right" id="p1-grand-total">0</td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pool 2: Picker to Packer -->
                    <div class="col-md-4">
                        <div class="panel panel-warning" style="border-color: #f0ad4e;">
                            <div class="panel-heading" style="background-color: #f0ad4e; color: white;">
                                <h3 class="panel-title">PICKER KE PACKER <br><small>(Belum Packing)</small></h3>
                            </div>
                            <div class="panel-body" style="padding: 0;">
                                <table class="table table-bordered" style="margin-bottom: 0;">
                                    <tbody id="p2-indicators">
                                        <!-- Will be filled by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f1f1f1; font-weight: bold;">
                                            <td>GRAND TOTAL</td>
                                            <td class="text-right" id="p2-grand-total">0</td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pool 3: Jatoh Tempo -->
                    <div class="col-md-4">
                        <div class="panel panel-danger" style="border-color: #d9534f;">
                            <div class="panel-heading" style="background-color: #d9534f; color: white;">
                                <h3 class="panel-title">JATOH TEMPO HARI INI <br><small>(Deadline Hari Ini)</small></h3>
                            </div>
                            <div class="panel-body" style="padding: 0;">
                                <table class="table table-bordered" style="margin-bottom: 0;">
                                    <tbody id="p3-indicators">
                                        <!-- Will be filled by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f1f1f1; font-weight: bold;">
                                            <td>GRAND TOTAL</td>
                                            <td class="text-right" id="p3-grand-total">0</td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="text-muted">Data terupdate pada: <span id="last-update">-</span></span>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        fetchData();

        $('#refresh-data').on('click', function(e) {
            e.preventDefault();
            fetchData();
        });

        $('#btn-export').on('click', function(e) {
            e.preventDefault();
            window.location.href = 'resi_team/export_excel';
        });

        function fetchData() {
            $.ajax({
                url: 'resi_team/get_selisih_data',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.data) {
                        const d = response.data;
                        
                        $('#total-resi-active').text(d.top_stats.total_resi);

                        updatePoolUI(1, d.pool1);
                        updatePoolUI(2, d.pool2);
                        updatePoolUI(3, d.pool3);

                        $('#last-update').text(d.processed_at);
                    }
                },
                error: function() {
                    console.error('Failed to fetch data');
                }
            });
        }

        function updatePoolUI(stage, data) {
            if (!data) return;

            const total = parseInt(data.total_resi) || 0;
            $(`#p${stage}-grand-total`).text(total);

            const indicators = [
                { key: 'sku_special', label: 'SKU SPECIAL' },
                { key: 'resi_qty_banyak', label: 'RESI QTY BANYAK' },
                { key: 'resi_lebih_10_sku', label: 'RESI > 10 SKU' },
                { key: 'resi_satuan', label: 'RESI 1 SKU' },
                { key: 'resi_kurang_10_sku', label: 'RESI 2-10 SKU' }
            ];

            let html = '';
            indicators.forEach(ind => {
                const count = parseInt(data[ind.key]) || 0;
                const pct = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                
                html += `<tr>
                    <td>${ind.label}</td>
                    <td class="text-right"><strong>${count}</strong></td>
                    <td class="text-right"><span class="label ${pct > 0 ? 'label-primary' : 'label-default'}">${pct}%</span></td>
                </tr>`;

                if (ind.key === 'sku_special' && data.special_sku_list && data.special_sku_list.length > 0) {
                    const skuList = data.special_sku_list;
                    const maxItems = 5;
                    const hasMore = skuList.length > maxItems;
                    
                    html += `<tr class="special-sku-row">
                        <td colspan="3" style="padding: 4px 8px 8px 25px; background: #fffcf0;">
                            <ul style="margin: 0; padding-left: 15px; font-size: 11px; list-style-type: none;">
                                ${skuList.map((sku, i) => {
                                    const skuCount = parseInt(sku.resi_count) || 0;
                                    const skuPct = total > 0 ? ((skuCount / total) * 100).toFixed(1) : 0;
                                    const display = i >= maxItems ? 'none' : 'block';
                                    const itemClass = i >= maxItems ? `p${stage}-extra-sku` : '';
                                    return `<li style="display: ${display};" class="${itemClass}">- ${sku.id_sku}: <strong>${skuCount}</strong> <span class="text-muted">(${skuPct}%)</span></li>`;
                                }).join('')}
                            </ul>
                            ${hasMore ? `<div style="margin-top: 5px; padding-left: 15px;">
                                <a href="#" class="toggle-sku" data-stage="${stage}" style="font-size: 10px; text-decoration: none;">Lihat Selengkapnya (${skuList.length - maxItems} lagi)...</a>
                            </div>` : ''}
                        </td>
                    </tr>`;
                }
            });

            $(`#p${stage}-indicators`).html(html);

            // Add toggle event
            $('.toggle-sku').off('click').on('click', function(e) {
                e.preventDefault();
                const stageNum = $(this).data('stage');
                const extras = $(`.p${stageNum}-extra-sku`);
                if (extras.first().is(':visible')) {
                    extras.hide();
                    $(this).text(`Lihat Selengkapnya (${extras.length} lagi)...`);
                } else {
                    extras.show();
                    $(this).text('Sembunyikan');
                }
            });
        }
    });
</script>

<style>
    .panel-heading .panel-title { font-weight: bold; }
    .table > tbody > tr > td { vertical-align: middle; padding: 10px 8px; font-size: 12px; }
    h2 { margin-top: 10px; font-weight: bold; color: #33414E; }
    .label { font-size: 10px; }
    .widget { margin-bottom: 10px; }
    .widget .widget-int { font-size: 36px; line-height: 40px; }
    .special-sku-row td { border-top: none !important; }
</style>
