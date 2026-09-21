<script src="assets/js/plugins/html2canvas/html2canvas.min.js"></script>
<div id="capture-area" style="padding: 10px; background: #f5f5f5;">
<div class="row">
    <!-- Total Resi Today -->
    <div class="col-md-4">
        <div class="widget widget-primary widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-truck"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="total-resi-active">0</div>
                <div class="widget-title">TOTAL RESI</div>
                <div class="widget-subtitle">yang sudah diupload belum dikerjakan</div>
            </div>
        </div>
    </div>
    <!-- Required Today -->
    <div class="col-md-4">
        <div class="widget widget-danger widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-exclamation-triangle"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="total-wajib-active">0</div>
                <div class="widget-title">WAJIB HARI INI</div>
                <div class="widget-subtitle">Deadline hari ini + tunggakan blm selesai</div>
            </div>
        </div>
    </div>
    <!-- Difference -->
    <div class="col-md-4">
        <div class="widget widget-warning widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-calculator"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count" id="total-selisih-active">0</div>
                <div class="widget-title">SELISIH PAKET</div>
                <div class="widget-subtitle">Selisih Total Dikurangi Wajib Hari Ini</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">MONITORING SELISIH HARI INI</h3>
                <ul class="panel-controls">
                    <li>
                        <div class="input-group" style="width: 150px; margin-right: 10px;">
                            <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                            <input type="text" class="form-control datepicker" id="filter-date" value="<?= date('Y-m-d') ?>" data-date-format="yyyy-mm-dd">
                        </div>
                    </li>
                    <li><a href="<?= base_url('report/resi-cancel-report') ?>" class="btn btn-danger link" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-ban"></span> RESI CANCEL</small></a></li>
                    <li><a href="#" class="btn btn-info" id="btn-capture" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-camera"></span> CAPTURE PNG</small></a></li>
                    <li><a href="<?= base_url("resi_team/export_excel") ?>" class="btn btn-success" id="btn-export" style="color: white; padding: 5px 15px; margin-right: 10px;"><small><span class="fa fa-file-excel-o"></span> EXPORT EXCEL</small></a></li>
                    <li><a href="#" class="panel-refresh" id="refresh-data"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <style>
                    .pool-scroll-wrapper {
                        overflow-x: auto;
                        padding-bottom: 20px;
                        display: flex;
                        gap: 15px;
                        width: 100%;
                    }
                    .pool-item {
                        min-width: 350px;
                        flex: 1;
                    }
                    .special-sku-row {
                        background-color: #f9f9f9;
                    }
                    .clickable-indicator:hover {
                        background-color: #f0f7ff !important;
                    }
                    .pool-arrow-down {
                        text-align: center;
                        font-size: 80px;
                        font-weight: 900;
                        line-height: 1;
                        margin-bottom: 0px;
                        animation: bounce 1.5s infinite;
                        color: #0d9488;
                        text-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    }
                    .pool-arrow-down.arrow-green {
                        color: #5cb85c;
                    }
                    .pool-arrow-down.arrow-orange {
                        color: #f39c12;
                    }
                    .pool-item-regular {
                        margin-top: 65px;
                    }
                    @keyframes bounce {
                        0%, 100% { transform: translateY(0); }
                        50% { transform: translateY(-15px); }
                    }
                </style>
                <div id="wajib-resi-breakdown">
                    <h4 style="font-weight: bold; color: #c0392b; margin-left:10px; margin-bottom: 15px;"><span class="fa fa-exclamation-triangle"></span> BREAKDOWN WAJIB HARI INI <small>(Deadline hari ini + tunggakan)</small></h4>
                    <div class="pool-scroll-wrapper" id="wajib-pools-area">
                        <!-- Pool 1 Wajib -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h3 class="panel-title">RESI KE PICKER <br><small>(Ready to Pick)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="wajib-p1-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fdeaea; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="wajib-p1-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 2 Wajib -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h3 class="panel-title">PICKER KE PACKER <br><small>(Done Picking / Ready to Pack)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="wajib-p2-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fdeaea; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="wajib-p2-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 3 Wajib (MAIN FOCUS) -->
                        <div class="pool-item">
                            <div class="pool-arrow-down">
                                <i class="fa fa-arrow-down"></i>
                            </div>
                            <div class="panel panel-success" style="border-color: #0d9488; border-width: 3px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                <div class="panel-heading" style="background-color: #0d9488; color: white !important;">
                                    <h3 class="panel-title" style="color: white !important;">RESI KE PICKER + PICKER KE PACKER <br><small style="color: #e0f2f1;">⭐ MAIN FOCUS (Must Go Today)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="wajib-p3-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#e0f2f1; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="wajib-p3-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 4 Wajib -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h3 class="panel-title">PACKER KE HO <br><small>(Ready to HO)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="wajib-p4-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fdeaea; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="wajib-p4-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 5 Wajib (PAKET BELUM HO) -->
                        <div class="pool-item">
                            <div class="pool-arrow-down arrow-green">
                                <i class="fa fa-arrow-down"></i>
                            </div>
                            <div class="panel panel-success" style="border-color: #5cb85c; border-width: 3px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                <div class="panel-heading" style="background-color: #5cb85c; color: white !important;">
                                    <h3 class="panel-title" style="color: white !important;">PAKET BELUM HO <br><small style="color: #e0f2f1;">Semua Wajib Belum Handover</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="wajib-p5-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#e0f2f1; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="wajib-p5-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr style="border-top: 2px dashed #ccc; margin: 30px 10px;">

                <div id="sisa-resi-breakdown">
                    <h4 style="font-weight: bold; color: #f39c12; margin-left:10px; margin-bottom: 15px;"><span class="fa fa-calculator"></span> BREAKDOWN SISA / NON-WAJIB <small>(Antrian pesanan selain yg wajib hari ini)</small></h4>
                    <div class="pool-scroll-wrapper" id="sisa-pools-area">
                        <!-- Pool 1 Sisa -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title">RESI KE PICKER <br><small>(Ready to Pick)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="sisa-p1-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fcf8e3; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="sisa-p1-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 2 Sisa -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title">PICKER KE PACKER <br><small>(Done Picking / Ready to Pack)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="sisa-p2-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fcf8e3; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="sisa-p2-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 3 Sisa (Merged) -->
                        <div class="pool-item">
                            <div class="pool-arrow-down arrow-orange">
                                <i class="fa fa-arrow-down"></i>
                            </div>
                            <div class="panel panel-warning" style="border-color: #f39c12; border-width: 2px;">
                                <div class="panel-heading" style="background-color: #f39c12; color: white !important;">
                                    <h3 class="panel-title" style="color: white !important;">RESI KE PICKER + PICKER KE PACKER <br><small style="color: #fff9c4;">⭐ REMAINDER QUEUE</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="sisa-p3-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fcf8e3; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="sisa-p3-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 4 Sisa -->
                        <div class="pool-item pool-item-regular">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title">PACKER KE HO <br><small>(Ready to HO)</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="sisa-p4-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#fcf8e3; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="sisa-p4-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pool 5 Sisa (PAKET BELUM HO) -->
                        <div class="pool-item">
                            <div class="pool-arrow-down arrow-green">
                                <i class="fa fa-arrow-down"></i>
                            </div>
                            <div class="panel panel-success" style="border-color: #5cb85c; border-width: 2px;">
                                <div class="panel-heading" style="background-color: #5cb85c; color: white !important;">
                                    <h3 class="panel-title" style="color: white !important;">PAKET BELUM HO <br><small style="color: #e0f2f1;">Semua Sisa Belum Handover</small></h3>
                                </div>
                                <div class="panel-body" style="padding:0;">
                                    <table class="table table-bordered">
                                        <tbody id="sisa-p5-indicators"></tbody>
                                        <tfoot>
                                            <tr style="background:#e0f2f1; font-weight:bold;">
                                                <td>GRAND TOTAL</td>
                                                <td class="text-right" id="sisa-p5-grand-total">0</td>
                                                <td class="text-right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-right text-muted" style="font-size: 10px; margin-top: 10px;">
                    Last updated: <span id="last-update">-</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div><!-- end capture-area -->

<!-- Modal Detail -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="modal-title">Detail Resi</h4>
            </div>
            <div class="modal-body" style="padding: 0; max-height: 500px; overflow-y: auto;">
                <table class="table table-bordered table-striped" style="margin-bottom:0;">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr>
                            <th>No. Resi</th>
                            <th>Batas Kirim</th>
                            <th>Status Pesanan</th>
                            <th>Total SKU</th>
                            <th>Total QTY</th>
                        </tr>
                    </thead>
                    <tbody id="detail-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            if ($(".datepicker").length > 0) {
                $(".datepicker").datepicker({ format: 'yyyy-mm-dd', autoclose: true });
            }

            fetchData();

            $('#filter-date').on('change', function() { fetchData(); });
            $('#refresh-data').on('click', function(e) { e.preventDefault(); fetchData(); });

            $(document).on('click', '.clickable-indicator', function() {
                const pool = $(this).data('pool-type');
                const key = $(this).data('indicator-key');
                const label = $(this).data('label');
                showDetail(pool, key, label);
            });

            $(document).on('click', '.toggle-sku', function(e) {
                e.preventDefault();
                const stage = $(this).data('stage');
                const prefix = $(this).data('prefix');
                $('.' + prefix + '-p' + stage + '-extra-sku').toggle();
                const visible = $('.' + prefix + '-p' + stage + '-extra-sku').is(':visible');
                $(this).text(visible ? 'Lihat Lebih Sedikit' : 'Lihat Selengkapnya...');
            });

            $('#btn-capture').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                btn.html('<i class="fa fa-spinner fa-spin"></i> processing...').addClass('disabled');
                
                // Use a wider width to ensure pools are not cut off
                html2canvas(document.querySelector("#capture-area"), {
                    scale: 2, // Higher quality
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: -window.scrollY,
                    windowWidth: 2200 // Ensure wide content is captured including the 5th pool
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'Selisih-Paket-' + $('#filter-date').val() + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    btn.html('<small><span class="fa fa-camera"></span> CAPTURE PNG</small>').removeClass('disabled');
                });
            });
        });

        function fetchData() {
            const date = $('#filter-date').val();
            const panel = $('.panel-default');
            panel.addClass('panel-refreshing');
            
            $.ajax({
                url: '<?= base_url("resi_team/get_selisih_data") ?>',
                type: 'POST',
                data: { start_date: date + ' 00:00:00', end_date: date + ' 23:59:59' },
                dataType: 'json',
                success: function(response) {
                    panel.removeClass('panel-refreshing');
                    if (response.data) {
                        const d = response.data;
                        $('#total-resi-active').text(d.top_stats.total_resi || 0);
                        $('#total-wajib-active').text(d.top_stats.wajib_resi || 0);
                        const selisih = (parseInt(d.top_stats.total_resi) || 0) - (parseInt(d.top_stats.wajib_resi) || 0);
                        $('#total-selisih-active').text(selisih);
                        
                        // Update Sisa Sections
                        updatePoolUI('sisa', 1, d.sisa_data.pool1);
                        updatePoolUI('sisa', 2, d.sisa_data.pool2);
                        updatePoolUI('sisa', 3, d.sisa_data.pool3);
                        updatePoolUI('sisa', 4, d.sisa_data.pool4);
                        updatePoolUI('sisa', 5, d.sisa_data.pool5);
                        
                        // Update Wajib Sections
                        updatePoolUI('wajib', 1, d.wajib_data.pool1);
                        updatePoolUI('wajib', 2, d.wajib_data.pool2);
                        updatePoolUI('wajib', 3, d.wajib_data.pool3);
                        updatePoolUI('wajib', 4, d.wajib_data.pool4);
                        updatePoolUI('wajib', 5, d.wajib_data.pool5);
                        
                        $('#last-update').text(d.processed_at);
                    }
                },
                error: function() {
                    panel.removeClass('panel-refreshing');
                    alert('Gagal memuat data selisih.');
                }
            });
        }

        function updatePoolUI(prefix, stage, data) {
            if (!data) return;
            const indicators = [
                { key: 'sku_special', label: 'RESI SPECIAL' },
                { key: 'resi_1_sku_sd_9', label: '1 SKU & QTY S/D 9' },
                { key: 'resi_2_9_sku_sd_9', label: '2-9 SKU & QTY S/D 9' },
                { key: 'resi_qty_banyak', label: 'QTY BANYAK (>9)' }
            ];
            
            const total = parseInt(data.total_resi) || 0;
            $('#' + prefix + '-p' + stage + '-grand-total').text(total);
            
            let html = '';
            let poolType = '';
            if(stage == 1) poolType = 'resi_to_picker';
            else if(stage == 2) poolType = 'picker_to_packer';
            else if(stage == 3) poolType = 'picker_total_combined';
            else if(stage == 4) poolType = 'packer_to_ho';
            else if(stage == 5) poolType = 'paket_belum_ho';

            indicators.forEach(function(ind) {
                const count = parseInt(data[ind.key]) || 0;
                const percent = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';
                
                let label = ind.label;
                const totalSpecial = parseInt(data.total_special_skus) || 0;
                if (ind.key === 'sku_special' && totalSpecial > 0 && stage != 3) {
                    label += ' (' + totalSpecial + ' SKU)';
                }

                html += '<tr class="clickable-indicator" data-pool-type="' + poolType + '" data-indicator-key="' + ind.key + '" data-label="' + ind.label + '" style="cursor:pointer;">' +
                        '<td style="padding-left:15px;">' + label + '</td>' +
                        '<td class="text-right"><strong>' + count + '</strong></td>' +
                        '<td class="text-right"><span class="label ' + (count > 0 ? 'label-info' : 'label-default') + '">' + percent + '%</span></td>' +
                        '</tr>';
                
                if (ind.key === 'sku_special' && data.special_sku_list && data.special_sku_list.length > 0) {
                    html += '<tr class="special-sku-row"><td colspan="3" style="padding:4px 8px 8px 25px; background:#fdfdfd;">' +
                            '<ul style="margin:0; padding-left:15px; font-size:11px; list-style:none; color:#555;">';
                    data.special_sku_list.forEach((sku, i) => {
                        const disp = i >= 5 ? 'none' : 'block';
                        const cls = i >= 5 ? prefix + '-p' + stage + '-extra-sku' : '';
                        html += '<li style="display:' + disp + ';" class="' + cls + '">- ' + sku.id_sku + ': <strong>' + sku.resi_count + '</strong></li>';
                    });
                    html += '</ul>';
                    if (data.special_sku_list.length > 5) {
                        html += '<div style="margin-top:5px; padding-left:15px;"><a href="#" class="toggle-sku" data-prefix="' + prefix + '" data-stage="' + stage + '" style="font-size:10px;">Lihat Selengkapnya...</a></div>';
                    }
                    html += '</td></tr>';
                }
            });
            $('#' + prefix + '-p' + stage + '-indicators').html(html);
        }

        function showDetail(pool, key, label) {
            $('#modal-title').text('Detail: ' + label);
            $('#detail-body').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
            $('#modal-detail').modal('show');
            const date = $('#filter-date').val();

            $.ajax({
                url: '<?= base_url("resi_team/get_indicator_details") ?>',
                type: 'POST',
                data: { pool_type: pool, indicator_key: key, start_date: date + ' 00:00:00', end_date: date + ' 23:59:59' },
                dataType: 'json',
                success: function(response) {
                    if (response.data) {
                        let html = '';
                        response.data.forEach(item => {
                            html += '<tr>' +
                                '<td>' + item.noresi + '</td>' +
                                '<td>' + item.tanggal_bataskirim + '</td>' +
                                '<td>' + (item.status_pesanan || '-') + '</td>' +
                                '<td class="text-center">' + item.distinct_skus + '</td>' +
                                '<td class="text-center">' + item.total_qty + '</td>' +
                                '</tr>';
                        });
                        $('#detail-body').html(html || '<tr><td colspan="5" class="text-center">No data</td></tr>');
                    }
                }
            });
        }
    })(jQuery);
</script>
