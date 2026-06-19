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
                <h3 class="panel-title"><strong>Batas Kirim Paket (5 Hari Kedepan)</strong></h3>
                <ul class="panel-controls">
                    <li><a href="#" class="btn btn-success" id="btn-export" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-file-excel-o"></span> EXPORT EXCEL</small></a></li>
                    <li><a href="#" class="panel-refresh" id="refresh-data"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="row" id="days-container">
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
            window.location.href = 'resi_team/export_batas_kirim_excel';
        });

        function fetchData() {
            $.ajax({
                url: 'resi_team/get_batas_kirim_data',
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
                const total = parseInt(day.total_resi) || 0;
                
                // Color progression: Today (Red), Tomorrow (Orange), others (Blue)
                let panelClass = 'panel-info';
                let panelStyle = '';
                let headStyle = '';
                
                if (index === 0) {
                    panelClass = 'panel-danger';
                    panelStyle = 'border-color: #d9534f;';
                    headStyle = 'background-color: #d9534f; color: white;';
                } else if (index === 1) {
                    panelClass = 'panel-warning';
                    panelStyle = 'border-color: #f0ad4e;';
                    headStyle = 'background-color: #f0ad4e; color: white;';
                }

                html += `
                    <div class="col-md-5th col-sm-6">
                        <div class="panel ${panelClass}" style="${panelStyle} margin-bottom: 20px;">
                            <div class="panel-heading" style="${headStyle}">
                                <h3 class="panel-title" style="font-size: 13px;">${day.date_label} <br><small style="color: inherit; opacity: 0.9;">${day.date}</small></h3>
                            </div>
                            <div class="panel-body" style="padding: 0;">
                                <table class="table table-bordered" style="margin-bottom: 0;">
                                    <tbody>
                                        ${renderIndicators(day, index)}
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f1f1f1; font-weight: bold;">
                                            <td style="font-size: 10px;">GRAND TOTAL</td>
                                            <td class="text-right" style="font-size: 11px;">${total}</td>
                                            <td class="text-right" style="font-size: 10px;">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#days-container').html(html);

            // Add toggle event
            $('.toggle-sku').off('click').on('click', function(e) {
                e.preventDefault();
                const dayIndex = $(this).data('day');
                const extras = $(`.day${dayIndex}-extra-sku`);
                if (extras.first().is(':visible')) {
                    extras.hide();
                    $(this).text(`Lihat Selengkapnya (${extras.length} lagi)...`);
                } else {
                    extras.show();
                    $(this).text('Sembunyikan');
                }
            });
        }

        function renderIndicators(data, dayIndex) {
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
                    <td style="font-size: 10px;">${ind.label}</td>
                    <td class="text-right" style="width: 50px;"><strong>${count}</strong></td>
                    <td class="text-right" style="width: 45px;"><span class="label ${pct > 0 ? (pct > 20 ? 'label-danger' : 'label-primary') : 'label-default'}">${pct}%</span></td>
                </tr>`;

                if (ind.key === 'sku_special' && data.special_sku_list && data.special_sku_list.length > 0) {
                    const skuList = data.special_sku_list;
                    const maxItems = 5;
                    const hasMore = skuList.length > maxItems;

                    html += `<tr class="special-sku-row">
                        <td colspan="3" style="padding: 4px 8px 8px 15px; background: #fffcf0; border-top: none;">
                            <ul style="margin: 0; padding-left: 10px; font-size: 9px; color: #856404; list-style-type: none;">
                                ${skuList.map((sku, i) => {
                                    const skuCount = parseInt(sku.resi_count) || 0;
                                    const skuPct = total > 0 ? ((skuCount / total) * 100).toFixed(1) : 0;
                                    const display = i >= maxItems ? 'none' : 'block';
                                    const itemClass = i >= maxItems ? `day${dayIndex}-extra-sku` : '';
                                    return `<li style="display: ${display};" class="${itemClass}">- ${sku.id_sku}: <strong>${skuCount}</strong> (${skuPct}%)</li>`;
                                }).join('')}
                            </ul>
                            ${hasMore ? `<div style="margin-top: 3px; padding-left: 10px;">
                                <a href="#" class="toggle-sku" data-day="${dayIndex}" style="font-size: 9px; color: #007bff; text-decoration: none;">Lihat Selengkapnya (${skuList.length - maxItems} lagi)...</a>
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
    .col-md-5th { width: 20%; float: left; position: relative; min-height: 1px; padding-right: 5px; padding-left: 5px; }
    @media (max-width: 1200px) { .col-md-5th { width: 33.33%; } }
    @media (max-width: 992px) { .col-md-5th { width: 50%; } }
    @media (max-width: 600px) { .col-md-5th { width: 100%; } }
    
    .panel-heading .panel-title { font-weight: bold; line-height: 1.2; margin: 0; }
    .table > tbody > tr > td { vertical-align: middle; padding: 6px 4px; border-color: #eee; }
    .label { font-size: 8px; padding: 2px 4px; }
    .special-sku-row td { border-top: none !important; }
</style>
