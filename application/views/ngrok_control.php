<div class="row">
    <div class="col-md-12">
        <!-- LOGOUT NOTICE -->
        <div class="alert alert-info" id="logout-notice">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span></button>
            <strong><i class="fa fa-info-circle"></i> Info Menu:</strong> Jika menu "Control Ngrok" belum muncul di sidebar kiri (Master), silakan <strong>Logout</strong> dan <strong>Login</strong> kembali untuk menyegarkan daftar menu.
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-plug"></i> Ngrok Control Center</h3>
                <ul class="panel-controls">
                    <li><a href="#" class="panel-refresh" id="refresh-control"><span class="fa fa-refresh"></span></a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- STATUS CARD -->
                    <div class="col-md-4">
                        <div class="widget widget-default widget-item-icon">
                            <div class="widget-item-left">
                                <span id="ngrok-icon" class="fa fa-circle text-danger"></span>
                            </div>                             
                            <div class="widget-data">
                                <div class="widget-int num-count" id="ngrok-status-text">OFFLINE</div>
                                <div class="widget-title">Connection Status</div>
                                <div class="widget-subtitle">Local API 127.0.0.1:4040</div>
                            </div>      
                        </div>

                        <!-- TOKEN INPUT FORM -->
                        <div class="panel panel-primary" style="margin-top: 20px;">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-key"></i> Setup Auth Token</h3>
                            </div>
                            <div class="panel-body">
                                <p class="small text-muted">Dapatkan token di <a href="https://dashboard.ngrok.com/get-started/your-authtoken" target="_blank">dashboard.ngrok.com</a></p>
                                <div class="form-group">
                                    <input type="password" id="token-input" class="form-control" placeholder="Paste token di sini...">
                                </div>
                                <button class="btn btn-primary btn-block" id="save-token-btn">Simpan Token</button>
                            </div>
                        </div>
                    </div>

                    <!-- DETAILS CARD -->
                    <div class="col-md-8">
                        <div class="panel panel-info">
                            <div class="panel-body">
                                <h4>Public URL Information</h4>
                                <hr>
                                <div id="ngrok-details">
                                    <p class="text-muted">Ngrok is currently not running. Please start the tunnel using the <code>beverra-ngrok.bat</code> script.</p>
                                </div>
                                <div id="ngrok-active-details" style="display:none;">
                                    <div class="form-group">
                                        <label>Secure URL (HTTPS)</label>
                                        <div class="input-group">
                                            <input type="text" id="https-url" class="form-control" readonly>
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btn-copy" data-target="#https-url">Copy</button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Standard URL (HTTP)</label>
                                        <div class="input-group">
                                            <input type="text" id="http-url" class="form-control" readonly>
                                            <span class="input-group-btn">
                                                <button class="btn btn-default btn-copy" data-target="#http-url">Copy</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DETAILED GUIDE -->
                        <div class="panel panel-default" style="margin-top: 20px;">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-book"></i> Panduan Lengkap Running</h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="list-group border-bottom">
                                            <div class="list-group-item">
                                                <span class="fa fa-1 fa-lg text-primary"></span> &nbsp; <strong>Dapatkan Token:</strong> Buka <a href="https://dashboard.ngrok.com/" target="_blank">Ngrok Dashboard</a>, copy token Anda.
                                            </div>
                                            <div class="list-group-item">
                                                <span class="fa fa-2 fa-lg text-primary"></span> &nbsp; <strong>Simpan Token:</strong> Masukkan ke kotak "Setup Auth Token" di samping kiri dan klik Simpan.
                                            </div>
                                            <div class="list-group-item">
                                                <span class="fa fa-3 fa-lg text-primary"></span> &nbsp; <strong>Jalankan Script:</strong> Buka folder <code>C:\xampp\htdocs\new-iresis</code> dan double click file <code>beverra-ngrok.bat</code>.
                                            </div>
                                            <div class="list-group-item">
                                                <span class="fa fa-4 fa-lg text-primary"></span> &nbsp; <strong>Cek Status:</strong> Pastikan jendela hitam (CMD) tetap terbuka. Status di halaman ini akan otomatis berubah jadi "ONLINE".
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateNgrokControl() {
        $.ajax({
            url: "<?= base_url('ngrok_control/get_status') ?>",
            type: "GET",
            dataType: "json",
            success: function(resp) {
                if (resp.tunnels && resp.tunnels.length > 0) {
                    $('#ngrok-icon').removeClass('text-danger').addClass('text-success');
                    $('#ngrok-status-text').text('ONLINE').addClass('text-success');
                    $('#ngrok-details').hide();
                    $('#ngrok-active-details').show();

                    resp.tunnels.forEach(function(t) {
                        if (t.proto === 'https') $('#https-url').val(t.public_url);
                        if (t.proto === 'http') $('#http-url').val(t.public_url);
                    });
                } else {
                    setOffline();
                }
            },
            error: function() {
                setOffline();
            }
        });
    }

    function setOffline() {
        $('#ngrok-icon').removeClass('text-success').addClass('text-danger');
        $('#ngrok-status-text').text('OFFLINE').removeClass('text-success');
        $('#ngrok-details').show();
        $('#ngrok-active-details').hide();
        $('#https-url').val('');
        $('#http-url').val('');
    }

    // Save Token Logic
    $('#save-token-btn').click(function() {
        var token = $('#token-input').val();
        if(!token) {
            noty({text: 'Token tidak boleh kosong!', layout: 'topRight', type: 'error', timeout: 2000});
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Menyimpan...');

        $.post("<?= base_url('ngrok_control/save_token') ?>", {token: token}, function(resp) {
            $btn.prop('disabled', false).text('Simpan Token');
            if(resp.status === 'success') {
                noty({text: resp.message, layout: 'topRight', type: 'success', timeout: 3000});
                $('#token-input').val('');
            } else {
                noty({text: resp.message, layout: 'topRight', type: 'error', timeout: 3000});
            }
        }, 'json');
    });

    $('.btn-copy').click(function() {
        var target = $(this).data('target');
        var val = $(target).val();
        if (val) {
            navigator.clipboard.writeText(val);
            noty({text: 'URL copied!', layout: 'topRight', type: 'success', timeout: 2000});
        }
    });

    $('#refresh-control').click(function(e) {
        e.preventDefault();
        updateNgrokControl();
    });

    updateNgrokControl();
    setInterval(updateNgrokControl, 10000); // Update every 10s
});
</script>
