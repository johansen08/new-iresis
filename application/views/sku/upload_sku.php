<div class="row">
    <div class="col-md-6 center-block float-none">
        <form action="sku/upload_sku_action" method="post" enctype="multipart/form-data" class="form-horizontal nojs" id="form_upload_sku" autocomplete="off">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong>Upload Data SKU</strong></h3>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Upload File</label>
                        <div class="col-md-8 col-xs-12">
                            <input id="sku_file" class="form-control" type="file" name="skuFile" accept=".xls, .xlsx">
                            <input type="hidden" name="upload_id" id="upload_id">
                        </div>
                    </div>

                    <div class="progress" style="display: none; height: 25px; margin-bottom: 20px;" id="progress_container">
                        <div id="upload_progress_bar" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; line-height: 25px;">
                            0%
                        </div>
                    </div>

                    <div class="tile tile-default bg-success" id="div_container_latest_sku">
                        <span id="span_latest_sku">-</span>
                        <p><small id="p_latest_sku_message"></small></p>
                    </div>

                </div>

                <div class="panel-footer">
                    <button type="submit" class="btn btn-info">Submit</button>
                    <button type="reset" class="btn btn-primary pull-right">Reset</button>
                </div>
            </div>
        </form>

        <div id="response_message">-</div>

        <!-- Loading Pop UP -->
        <div id="loadingPopUp" class="custom-popup-overlay" style="display: none;">
            <div style="max-width: 100%; display: -webkit-box; display: -ms-flexbox; display: -webkit-flex; display: flex; justify-content: center; align-items: center;">
                <img src="assets/img/LoaderIcon.gif" />
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        let jvalidate = $("#form_upload_sku").validate({
            ignore: [],
            rules: {
                skuFile: {
                    required: true
                }
            },
            messages: {
                skuFile: {
                    required: "Silahkan Input File Terlebih Dahulu!"
                }
            },
            submitHandler: function(form) {
                var uploadId = 'up_' + Math.random().toString(36).substr(2, 9);
                $('#upload_id').val(uploadId);

                var formData = new FormData(form);
                var baseUrl = "<?= base_url() ?>";

                $.ajax({
                    url: baseUrl + 'sku/upload_sku_action',
                    type: 'post',
                    data: formData,
                    contentType: false, 
                    processData: false, 
                    timeout: 600000, 
                    beforeSend: function() {
                        $('#loadingPopUp').show(); 
                        $('#progress_container').show();
                        $('#upload_progress_bar').css('width', '0%').text('0%');

                        $("#span_latest_sku").text("Processing file...");
                        $("#div_container_latest_sku").removeClass("tile-danger tile-success").addClass("tile-default");

                        window.progressInterval = setInterval(function() {
                            checkUploadProgress(uploadId, baseUrl);
                        }, 1000);
                    }
                })
                .done(function(res) {
                    $('#loadingPopUp').hide();
                    $('#progress_container').hide();
                    clearInterval(window.progressInterval);

                    $("#span_latest_sku").text(res.message || res);
                    $("#div_container_latest_sku").removeClass("tile-danger tile-default").addClass("tile-success");
                    
                    if (typeof noty !== 'undefined') {
                        noty({text: res.message || res, timeout: 3000, layout: 'topRight', type: 'success'});
                    }

                    $('#sku_file').val('');
                })
                .fail(function(response) {
                    $('#loadingPopUp').hide();
                    $('#progress_container').hide();
                    clearInterval(window.progressInterval);

                    let errorMessage = "Error processing file";
                    try {
                        let res = typeof response.responseText === 'string' ? JSON.parse(response.responseText) : response.responseJSON;
                        errorMessage = res.message || errorMessage;
                    } catch (e) {
                         if (response.status === 0 || response.statusText === 'timeout') {
                            errorMessage = "File processing timed out.";
                        }
                    }

                    $("#span_latest_sku").text(errorMessage);
                    $("#div_container_latest_sku").removeClass("tile-default tile-success").addClass("tile-danger");
                    
                    if (typeof noty !== 'undefined') {
                        noty({text: errorMessage, timeout: 3000, layout: 'topRight', type: 'error'});
                    }

                    $('#sku_file').val('');
                });

                return false;
            }
        });

        function checkUploadProgress(uploadId, baseUrl) {
            $.ajax({
                url: baseUrl + 'sku/get_progress_file/' + uploadId,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res && res.total > 0) {
                        $('#upload_progress_bar').css('width', res.percentage + '%').attr('aria-valuenow', res.percentage).text(res.percentage + '%');
                        
                        let statusText = res.status || "Processing";
                        if (statusText === 'Finalizing') {
                            $("#span_latest_sku").text("Saving to database... Please wait.");
                        } else {
                            $("#span_latest_sku").text(statusText + ": " + res.processed + " / " + res.total);
                        }
                    }
                }
            });
        }
    });
</script>

<style>
    .custom-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        display: -webkit-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9998;
    }
</style>
