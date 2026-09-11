<div class="row" id="today-summary-widgets" style="margin-bottom: 20px;">
    <!-- Filled by JS -->
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Progress Pengerjaan per Periode (Breakdown per Batas Kirim 30 Hari)</strong></h3>
                <ul class="panel-controls" style="width: auto; display: flex; align-items: center;">
                    <li style="width: 350px;">
                        <div class="input-group">
                            <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                            <input type="text" class="form-control datepicker" id="start-date" value="<?= date('Y-m-d') ?>" placeholder="Dari Tanggal">
                            <span class="input-group-addon">-</span>
                            <input type="text" class="form-control datepicker" id="end-date" value="<?= date('Y-m-d') ?>" placeholder="Sampai Tanggal">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" id="btn-filter" type="button">CEK</button>
                            </span>
                        </div>
                    </li>
                    <li><a href="#" class="btn btn-success" id="btn-export" style="color: white; padding: 5px 15px; margin-right: 10px; margin-left: 10px;"><small><span class="fa fa-file-excel-o"></span> EXPORT</small></a></li>
                    <li><a href="#" class="panel-refresh" id="refresh-data"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <blockquote style="margin-bottom: 20px; border-left: 5px solid #0288d1; background: #e1f5fe; padding: 10px 20px;">
                    <p style="font-size: 14px; color: #01579b; margin-bottom: 0;">
                        <strong>Info:</strong> Menampilkan data resi yang <strong>DIKERJAKAN pada periode terpilih</strong>, dikelompokkan berdasarkan <strong>Batas Kirim</strong>. 
                    </p>
                </blockquote>

                <div class="timeline-wrapper">
                    <div id="on-progress-timeline" class="d-flex timeline-container">
                        <!-- Filled by JS -->
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
        const indicators_meta = [
            { key: 'sku_special', label: 'SKU SPECIAL', color: '#d32f2f' },
            { key: 'resi_qty_banyak', label: 'QTY BANYAK', color: '#7b1fa2' },
            { key: 'resi_lebih_10_sku', label: 'SKU > 10', color: '#0288d1' },
            { key: 'resi_kurang_10_sku', label: 'SKU 2-9', color: '#388e3c' },
            { key: 'resi_satuan', label: 'SKU 1', color: '#689f38' }
        ];

        fetchData();

        $('#refresh-data, #btn-filter').on('click', function(e) { 
            e.preventDefault(); 
            fetchData(); 
        });

        $('#btn-export').on('click', function(e) { 
            e.preventDefault(); 
            const startStr = $('#start-date').val();
            const endStr = $('#end-date').val();
            window.location.href = `resi_team/export_on_progress_excel?start_date=${startStr}&end_date=${endStr}`; 
        });

        function fetchData() {
            const startStr = $('#start-date').val();
            const endStr = $('#end-date').val();

            $.ajax({
                url: 'resi_team/get_on_progress_data',
                type: 'POST',
                data: { start_date: startStr, end_date: endStr },
                dataType: 'json',
                success: function(response) {
                    if (response.data) {
                        const d = response.data;
                        renderSummary(d.summary_today);
                        updateTimeline(d.days);
                        $('#last-update').text(d.processed_at);
                    }
                }
            });
        }

        function renderSummary(summary) {
            let html = '';
            const stages = [
                { id: 'p1', label: 'TOTAL PICKER', color: 'info', icon: 'fa-shopping-basket' },
                { id: 'p2', label: 'TOTAL PACKER', color: 'warning', icon: 'fa-cube' },
                { id: 'p3', label: 'TOTAL HO', color: 'danger', icon: 'fa-truck' }
            ];

            stages.forEach(s => {
                const data = summary[s.id];
                html += `
                    <div class="col-md-4">
                        <div class="widget widget-${s.color} widget-item-icon">
                            <div class="widget-item-left"><span class="fa ${s.icon}"></span></div>
                            <div class="widget-data">
                                <div class="widget-int num-count">${data.total_resi}</div>
                                <div class="widget-title">${s.label} HARI INI</div>
                                <div class="widget-subtitle">Semua Batas Kirim</div>
                            </div>
                            <div class="widget-controls">
                                <div style="display: flex; flex-wrap: wrap; gap: 5px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-top: 10px;">
                                    ${indicators_meta.map(ind => `
                                        <div style="flex: 1 1 45%; font-size: 10px; font-weight: bold;">
                                            <span style="color: ${ind.color}">${ind.label}:</span> ${data[ind.key]}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#today-summary-widgets').html(html);
        }

        function updateTimeline(days) {
            let html = '';
            if (!days || days.length === 0) {
                $('#on-progress-timeline').html('<div class="alert alert-info">Belum ada pengerjaan hari ini.</div>');
                return;
            }

            days.forEach((day, index) => {
                const totalWorkToday = (parseInt(day.p1.total_resi) || 0) + (parseInt(day.p2.total_resi) || 0) + (parseInt(day.p3.total_resi) || 0);
                html += `
                    <div class="timeline-day-card">
                        <div class="day-header ${index === 0 ? 'header-today' : ''}">
                            <div class="day-label">${day.date_label}</div>
                            <div class="day-date">${day.date}</div>
                        </div>
                        <div class="day-content">
                            ${renderStageBlock('PICKER', 'blue', day.p1, index, 1)}
                            ${renderStageBlock('PACKER', 'orange', day.p2, index, 2)}
                            ${renderStageBlock('HO', 'red', day.p3, index, 3)}
                        </div>
                        <div class="day-footer">Total: <strong>${totalWorkToday}</strong></div>
                    </div>
                `;
            });
            $('#on-progress-timeline').html(html);

            $('.toggle-sku').off('click').on('click', function(e) {
                e.preventDefault();
                const extras = $(`.day${$(this).data('day')}-p${$(this).data('pool')}-extra-sku`);
                if (extras.first().is(':visible')) { extras.hide(); $(this).text(`+${extras.length} SKU Special...`); }
                else { extras.show(); $(this).text('Sembunyikan'); }
            });
        }

        function renderStageBlock(label, color, data, dayIndex, poolIndex) {
            const count = parseInt(data.total_resi) || 0;
            const hasSpecial = data.special_sku_list && data.special_sku_list.length > 0;

            let indicatorHtml = `
                <div class="indicator-grid">
                    ${indicators_meta.map(ind => `
                        <div class="ind-item">
                            <span class="ind-dot" style="background: ${ind.color}"></span>
                            <span class="ind-label">${ind.label}</span>
                            <span class="ind-val">${data[ind.key] || 0}</span>
                        </div>
                    `).join('')}
                </div>
            `;

            let specialHtml = '';
            if (hasSpecial) {
                specialHtml = `
                    <div class="special-sku-breakdown">
                        <ul class="sku-list">
                            ${data.special_sku_list.map((sku, i) => {
                                const display = i >= 2 ? 'none' : 'block';
                                const itemClass = i >= 2 ? `day${dayIndex}-p${poolIndex}-extra-sku` : '';
                                return `<li style="display: ${display};" class="${itemClass}">${sku.id_sku}: <strong>${sku.resi_count}</strong></li>`;
                            }).join('')}
                        </ul>
                        ${data.special_sku_list.length > 2 ? `<a href="#" class="toggle-sku" data-day="${dayIndex}" data-pool="${poolIndex}">+${data.special_sku_list.length - 2} SKU...</a>` : ''}
                    </div>
                `;
            }

            return `
                <div class="stage-block block-${color}">
                    <div class="stage-top">
                        <span class="stage-label">${label}</span>
                        <span class="stage-count">${count}</span>
                    </div>
                    ${indicatorHtml}
                    ${specialHtml}
                </div>
            `;
        }
    });
</script>

<style>
    .timeline-wrapper { width: 100%; overflow-x: auto; padding-bottom: 15px; -webkit-overflow-scrolling: touch; }
    .timeline-container { display: inline-flex; gap: 15px; padding: 5px; }
    .timeline-day-card { width: 300px; background: #fff; border: 1px solid #ddd; border-radius: 8px; display: flex; flex-direction: column; }
    .day-header { background: #f8f9fa; padding: 8px; text-align: center; border-bottom: 2px solid #eee; border-radius: 8px 8px 0 0; }
    .header-today { background: #0288d1; color: white; }
    .day-label { font-weight: bold; font-size: 13px; }
    .day-date { font-size: 10px; opacity: 0.8; }
    .day-content { padding: 8px; flex: 1; }
    .stage-block { border-radius: 6px; padding: 8px; margin-bottom: 8px; }
    .block-blue { background: #e3f2fd; border-left: 4px solid #2196f3; }
    .block-orange { background: #fff3e0; border-left: 4px solid #ff9800; }
    .block-red { background: #ffebee; border-left: 4px solid #f44336; }
    .stage-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .stage-label { font-size: 10px; font-weight: bold; color: #555; }
    .stage-count { font-size: 14px; font-weight: 900; }
    
    .indicator-grid { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 5px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 5px; }
    .ind-item { flex: 1 1 45%; display: flex; align-items: center; font-size: 9px; }
    .ind-dot { width: 6px; height: 6px; border-radius: 50%; margin-right: 4px; flex-shrink: 0; }
    .ind-label { color: #666; margin-right: 3px; }
    .ind-val { font-weight: bold; color: #33414E; }

    .special-sku-breakdown { background: rgba(255,255,255,0.6); padding: 4px; border-radius: 4px; font-size: 9px; margin-top: 4px;}
    .sku-list { margin: 0; padding-left: 12px; }
    .toggle-sku { display: block; margin-top: 2px; color: #0288d1; text-decoration: none; font-weight: bold; }
    .day-footer { padding: 6px; text-align: center; background: #f8f9fa; font-size: 10px; border-radius: 0 0 8px 8px; }
    
    .widget-controls { margin-top: 5px; border-top: 1px solid rgba(255,255,255,0.2); }
</style>
