<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">Upload SKU (Excel)</h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <strong>Format Excel (Jubelio):</strong><br>
                    A: ID SKU, B: Nama SKU, C: Bundle, D: Variasi, E: Display, F: Gudang, G: Transit, H: Total Stok, I: Link Foto, J: No Rak, K: Berat
                </div>

                <form id="form_upload_sku" class="form-horizontal">
                    <input type="hidden" name="upload_id" id="upload_id" value="<?= uniqid() ?>">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Pilih File Excel (.xls, .xlsx)</label>
                        <div class="col-sm-6">
                            <input type="file" name="file_sku" id="file_sku" class="form-control" accept=".xls, .xlsx" required>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary" id="btn_upload">
                                <i class="fa fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>
                </form>

                <div id="progress_container" style="display:none; margin-top: 20px;">
                    <div class="progress progress-striped active">
                        <div id="progress_bar" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                            <span id="progress_text">0% Terproses</span>
                        </div>
                    </div>
                    <div id="status_text" class="text-center"></div>
                </div>

                <div id="result_container" style="display:none; margin-top: 20px;">
                    <div class="alert alert-success" id="result_message"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var uploadId = $('#upload_id').val();
        var progressInterval;

        $('#form_upload_sku').on('submit', function(e) {
            e.preventDefault();

            if (!$('#file_sku').val()) {
                alert('Pilih file terlebih dahulu');
                return;
            }

            $('#btn_upload').attr('disabled', true);
            $('#progress_container').show();
            $('#result_container').hide();
            $('#progress_bar').css('width', '0%');
            $('#progress_text').text('0% Terproses');
            $('#status_text').text('Memulai upload...');

            var formData = new FormData(this);

            // Start progress tracking
            progressInterval = setInterval(updateProgress, 1000);

            $.ajax({
                url: '<?= base_url("receipt/upload-sku-action") ?>',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json',
                timeout: 600000, // 10 minutes timeout for large files
                success: function(response) {
                    clearInterval(progressInterval);
                    $('#btn_upload').prop('disabled', false);
                    $('#progress_bar').css('width', '100%');
                    $('#progress_text').text('100% Selesai');
                    
                    $('#result_message').html(response.message);
                    $('#result_container').show();
                    $('#status_text').text('Berhasil!');
                },
                error: function(xhr, status, error) {
                    clearInterval(progressInterval);
                    $('#btn_upload').prop('disabled', false);
                    var errMsg = 'Terjadi kesalahan saat upload';
                    
                    if (status === 'timeout') {
                        errMsg = 'Request Timeout (Upload memakan waktu terlalu lama).';
                    } else if (status === 'parsererror') {
                        errMsg = 'Server Error (Format respon tidak valid). Hubungi admin.';
                        console.error('Raw Response:', xhr.responseText);
                        // Show first 200 chars of raw response for debugging
                        if (xhr.responseText) {
                            errMsg += '<br><small>Respon: ' + xhr.responseText.substring(0, 200) + '...</small>';
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else {
                        errMsg += ': ' + error;
                    }
                    
                    $('#result_message').html('<div class="alert alert-danger">' + errMsg + '</div>');
                    $('#result_container').show();
                    $('#status_text').text('Gagal!');
                }
            });
        });

        function updateProgress() {
            $.getJSON('<?= base_url("receipt/get-progress-file/") ?>' + uploadId, function(data) {
                if (data.total > 0) {
                    var percentage = data.percentage;
                    $('#progress_bar').css('width', percentage + '%');
                    $('#progress_text').text(percentage + '% Terproses');
                    $('#status_text').text('Memproses: ' + data.processed + ' dari ' + data.total + ' baris');
                }
            });
        }
    });
</script>
