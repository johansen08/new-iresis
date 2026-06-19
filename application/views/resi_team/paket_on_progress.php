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
                <div class="widget-subtitle">Hari Ini + Pending 7 Hari</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Paket On Progress (5 Hari Kedepan)</strong></h3>
                <ul class="panel-controls">
                    <li><a href="#" class="btn btn-success" id="btn-export" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-file-excel-o"></span> EXPORT EXCEL</small></a></li>
                    <li><a href="#" class="panel-refresh" id="refresh-data"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="row" id="on-progress-container">
                    <!-- Will be filled by JS for 5 days -->
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
            window.location.href = 'resi_team/export_on_progress_excel';
        });

        function fetchData() {
            $.ajax({
                url: 'resi_team/get_on_progress_data',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.data) {
                        const d = response.data;
                        
                        if (d.top_stats) {
                            $('#total-resi-active').text(d.top_stats.total_resi);
                        }

                        updateUI(d.days);
                        $('#last-update').text(d.processed_at);
                    }
                },
                error: function() {
                    console.error('Failed to fetch data');
                }
            });
        }

        function updateUI(days) {
            let html = '';
            days.forEach((day, index) => {
                html += `
                    <div class="row" style="margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px;">
                        <div class="col-md-12">
                            <h4 style="margin-bottom: 15px; font-weight: bold; color: #33414E;">
                                <span class="fa fa-calendar"></span> ${day.date_label} (${day.date})
                            </h4>
                        </div>
                        
                        <!-- P1: RESI KE PICKER -->
                        <div class="col-md-4">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h3 class="panel-title">RESI KE PICKER <br><small>(Sudah Ambil Barang)</small></h3>
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <table class="table table-bordered" style="margin-bottom: 0;">
                                        <tbody>
                                            ${renderIndicators(day.p1, index, 1)}
                                        </tbody>
                                        <tfoot>
                                            <tr style="background: #f1f1f1; font-weight: bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right">${day.p1.total_resi || 0}</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- P2: PICKER KE PACKER -->
                        <div class="col-md-4">
                            <div class="panel panel-warning" style="border-color: #f0ad4e;">
                                <div class="panel-heading" style="background-color: #f0ad4e; color: white;">
                                    <h3 class="panel-title">PICKER KE PACKER <br><small>(Sudah Packing)</small></h3>
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <table class="table table-bordered" style="margin-bottom: 0;">
                                        <tbody>
                                            ${renderIndicators(day.p2, index, 2)}
                                        </tbody>
                                        <tfoot>
                                            <tr style="background: #f1f1f1; font-weight: bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right">${day.p2.total_resi || 0}</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- P3: PACKER KE HO -->
                        <div class="col-md-4">
                            <div class="panel panel-danger" style="border-color: #d9534f;">
                                <div class="panel-heading" style="background-color: #d9534f; color: white;">
                                    <h3 class="panel-title">PACKER KE HO <br><small>(Sudah Scan HO)</small></h3>
                                </div>
                                <div class="panel-body" style="padding: 0;">
                                    <table class="table table-bordered" style="margin-bottom: 0;">
                                        <tbody>
                                            ${renderIndicators(day.p3, index, 3)}
                                        </tbody>
                                        <tfoot>
                                            <tr style="background: #f1f1f1; font-weight: bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right">${day.p3.total_resi || 0}</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#on-progress-container').html(html);

            // Add toggle event
            $('.toggle-sku').off('click').on('click', function(e) {
                e.preventDefault();
                const dayIndex = $(this).data('day');
                const poolIndex = $(this).data('pool');
                const extras = $(`.day${dayIndex}-p${poolIndex}-extra-sku`);
                if (extras.first().is(':visible')) {
                    extras.hide();
                    $(this).text(`Lihat Selengkapnya (${extras.length} lagi)...`);
                } else {
                    extras.show();
                    $(this).text('Sembunyikan');
                }
            });
        }

        function renderIndicators(data, dayIndex, poolIndex) {
            const total = parseInt(data.total_resi) || 0;
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
                        <td colspan="3" style="padding: 4px 8px 8px 15px; background: #fffcf0; border-top: none;">
                            <ul style="margin: 0; padding-left: 10px; font-size: 10px; color: #856404; list-style-type: none;">
                                ${skuList.map((sku, i) => {
                                    const skuCount = parseInt(sku.resi_count) || 0;
                                    const skuPct = total > 0 ? ((skuCount / total) * 100).toFixed(1) : 0;
                                    const display = i >= maxItems ? 'none' : 'block';
                                    const itemClass = i >= maxItems ? `day${dayIndex}-p${poolIndex}-extra-sku` : '';
                                    return `<li style="display: ${display};" class="${itemClass}">- ${sku.id_sku}: <strong>${skuCount}</strong> (${skuPct}%)</li>`;
                                }).join('')}
                            </ul>
                            ${hasMore ? `<div style="margin-top: 3px; padding-left: 10px;">
                                <a href="#" class="toggle-sku" data-day="${dayIndex}" data-pool="${poolIndex}" style="font-size: 10px; color: #007bff; text-decoration: none;">Lihat Selengkapnya (${skuList.length - maxItems} lagi)...</a>
                            </div>` : ''}
                        </td>
                    </tr>`;
                }
            });
            return html;
        }
    });
</script>

<style>
    .panel-heading .panel-title { font-weight: bold; line-height: 1.2; margin: 0; font-size: 13px; }
    .table > tbody > tr > td { vertical-align: middle; padding: 10px 8px; font-size: 12px; }
    .label { font-size: 10px; }
    .special-sku-row td { border-top: none !important; }
</style>
