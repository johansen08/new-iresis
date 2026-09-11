<div class="row">
    <!-- TOP SECTION: UPLOAD DATA SALES (RESI + SKU + HARGA) -->
    <div class="col-md-8 center-block float-none">
        <div class="panel panel-default" style="border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin-bottom:30px;">
            <div class="panel-heading" style="border-radius:8px 8px 0 0; background:linear-gradient(135deg,#6a1fbf,#9c27b0); border:none; padding:14px 20px;">
                <h3 class="panel-title" style="font-size:16px; font-weight:700; color:#fff;">
                    <i class="fa fa-shopping-cart"></i> &nbsp;1. Upload Data Penjualan (Harga per Resi)
                </h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-info" style="border-radius:6px; font-size:12px; background:#f0f4ff; border:1px solid #d0daff; color:#334d99;">
                    <strong>Format Excel:</strong> Kolom A: No Resi, Kolom B: ID SKU, Kolom C: Harga Jual (Angka).
                </div>
                
                <form id="form_upload_sales" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-md-3 control-label" style="font-weight:600;">File Sales</label>
                        <div class="col-md-7">
                            <input type="file" name="salesFile" id="sales_file" class="form-control" accept=".xls,.xlsx">
                            <input type="hidden" name="upload_id" id="sales_upload_id">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#6a1fbf,#9c27b0); border:none; font-weight:600; width:100%;">
                                Upload
                            </button>
                        </div>
                    </div>
                </form>

                <div class="progress" style="display:none; height:20px; margin-top:15px;" id="sales_progress_container">
                    <div id="sales_progress_bar" class="progress-bar progress-bar-striped active" role="progressbar" style="width:0%;">0%</div>
                </div>
                <div id="sales_status" style="margin-top:10px; font-weight:600; font-size:13px; display:none;"></div>
            </div>
        </div>

        <!-- BOTTOM SECTION: UPLOAD DATA HPP (SKU + HPP) -->
        <div class="panel panel-default" style="border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <div class="panel-heading" style="border-radius:8px 8px 0 0; background:linear-gradient(135deg,#1a6fbf,#2196F3); border:none; padding:14px 20px;">
                <h3 class="panel-title" style="font-size:16px; font-weight:700; color:#fff;">
                    <i class="fa fa-money"></i> &nbsp;2. Upload Data HPP (Harga Pokok)
                </h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-warning" style="border-radius:6px; font-size:12px; background:#fff9e6; border:1px solid #ffeeba; color:#856404;">
                    <strong>Format Excel:</strong> Kolom A: ID SKU, Kolom D: HPP (Angka). (Sesuai template SKU standard)
                </div>

                <form id="form_upload_hpp" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-md-3 control-label" style="font-weight:600;">File HPP</label>
                        <div class="col-md-7">
                            <input type="file" name="hppFile" id="hpp_file" class="form-control" accept=".xls,.xlsx">
                            <input type="hidden" name="upload_id" id="hpp_upload_id">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info" style="background:linear-gradient(135deg,#1a6fbf,#2196F3); border:none; font-weight:600; width:100%;">
                                Upload
                            </button>
                        </div>
                    </div>
                </form>

                <div class="progress" style="display:none; height:20px; margin-top:15px;" id="hpp_progress_container">
                    <div id="hpp_progress_bar" class="progress-bar progress-bar-striped active" role="progressbar" style="width:0%;">0%</div>
                </div>
                <div id="hpp_status" style="margin-top:10px; font-weight:600; font-size:13px; display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="financeLoadingOverlay" class="custom-popup-overlay" style="display:none;">
    <div style="text-align:center; color:#fff;">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p style="margin-top:10px; font-size:16px;">Memproses data... Mohon tunggu.</p>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var baseUrl = "<?= base_url() ?>";

    // --- UPLOAD SALES ---
    $('#form_upload_sales').submit(function(e) {
        e.preventDefault();
        var file = $('#sales_file').val();
        if(!file) { alert('Pilih file sales!'); return; }

        var uploadId = 'sales_' + Math.random().toString(36).substr(2, 9);
        $('#sales_upload_id').val(uploadId);
        var formData = new FormData(this);

        $.ajax({
            url: baseUrl + 'finance/upload_sales_action',
            type: 'POST',
            data: formData,
            contentType: false, processData: false,
            beforeSend: function() {
                $('#financeLoadingOverlay').show();
                $('#sales_progress_container').show();
                $('#sales_status').show().text('Memulai upload...');
            },
            success: function(res) {
                $('#financeLoadingOverlay').hide();
                $('#sales_status').css('color', 'green').text(res.message || res);
                if(typeof noty !== 'undefined') noty({text: 'Upload Sales Berhasil', type: 'success', timeout: 3000});
            },
            error: function(err) {
                $('#financeLoadingOverlay').hide();
                $('#sales_status').css('color', 'red').text('Upload gagal: ' + (err.responseJSON ? err.responseJSON.message : 'Unknown error'));
            }
        });
    });

    // --- UPLOAD HPP ---
    $('#form_upload_hpp').submit(function(e) {
        e.preventDefault();
        var file = $('#hpp_file').val();
        if(!file) { alert('Pilih file HPP!'); return; }

        var uploadId = 'hpp_' + Math.random().toString(36).substr(2, 9);
        $('#hpp_upload_id').val(uploadId);
        var formData = new FormData(this);

        $.ajax({
            url: baseUrl + 'finance/upload_hpp_action',
            type: 'POST',
            data: formData,
            contentType: false, processData: false,
            beforeSend: function() {
                $('#financeLoadingOverlay').show();
                $('#hpp_progress_container').show();
                $('#hpp_status').show().text('Memulai upload...');
            },
            success: function(res) {
                $('#financeLoadingOverlay').hide();
                $('#hpp_status').css('color', 'green').text(res.message || res);
                if(typeof noty !== 'undefined') noty({text: 'Upload HPP Berhasil', type: 'success', timeout: 3000});
            },
            error: function(err) {
                $('#financeLoadingOverlay').hide();
                $('#hpp_status').css('color', 'red').text('Upload gagal');
            }
        });
    });
});
</script>

<style>
.custom-popup-overlay {
    position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
}
.panel { transition: all 0.3s ease; }
.panel:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; }
</style>
