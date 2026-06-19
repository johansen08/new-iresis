<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs nav-justified">
        <li class="<?= (!isset($active_tab) || $active_tab == 'terima-retur') ? 'active' : '' ?>"><a href="#tab-terima-retur" data-toggle="tab">Terima Retur</a></li>
        <li class="<?= (isset($active_tab) && $active_tab == 'buka-retur') ? 'active' : '' ?>"><a href="#tab-buka-retur" data-toggle="tab">Buka Retur</a></li>
      </ul>
      <div class="panel-body tab-content">
          <!-- ==================== TAB 1: TERIMA RETUR ==================== -->
          <div class="tab-pane fade <?= (!isset($active_tab) || $active_tab == 'terima-retur') ? 'in active' : '' ?>" id="tab-terima-retur">
            <div class="row">
              <div class="col-md-6 center-block float-none">
                <form action="retur/save-retur" class="form-horizontal nojs" id="form_scan_terima_retur" autocomplete="off">
                  <div class="panel panel-default">
                    <div class="panel-body">

                      <div class="form-group">
                        <label class="col-md-4 col-xs-12 control-label">Total scan resi Anda hari ini</label>
                        <div class="col-md-8 col-xs-12">
                          <input type="text" id="total_scan_terima" value="<?= isset($total_scan_terima) ? $total_scan_terima : 0 ?>" class="form-control" disabled />
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-md-4 col-xs-12 control-label">Status</label>
                        <div class="col-md-8 col-xs-12">
                          <input type="text" class="form-control" value="Terima Retur" readonly />
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="col-md-4 col-xs-12 control-label">Resi</label>
                        <div class="col-md-8 col-xs-12">
                          <input type="text" name="hasil_scan" id="noresi_terima" class="form-control" />
                        </div>
                      </div>

                      <div class="tile tile-default" id="div_container_latest_receipt_terima">
                        <span id="span_latest_receipt_terima">-</span>
                        <p><small id="p_latest_receipt_message_terima">Nomor resi terakhir yang sudah di-scan</small></p>
                      </div>

                    </div>

                    <div class="panel-footer">
                      <button type="submit" class="btn btn-info">Submit</button>
                      <button type="reset" class="btn btn-primary pull-right">Reset</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- ==================== TAB 2: BUKA RETUR ==================== -->
          <div class="tab-pane fade <?= (isset($active_tab) && $active_tab == 'buka-retur') ? 'in active' : '' ?>" id="tab-buka-retur">
            <div class="row">
              <div class="col-md-12">
                <form action="retur/scan-retur" method="post" class="form-horizontal nojs" autocomplete="off" id="form-scan-buka-retur">
                  <div class="form-group">
                    <div class="col-md-12">
                      <div class="input-group">
                        <input type="text" class="form-control" name="noresi_buka" id="noresi_buka" placeholder="Nomor resi" value="<?= isset($noresi_buka) ? $noresi_buka : '' ?>" />
                        <span class="input-group-btn" id="button-group">
                          <button class="btn btn-default" type="submit">
                            <i class="fa fa-search"></i> Cari
                          </button>
                        </span>
                      </div>
                    </div>
                  </div>
                </form>

                <?php if (isset($error_message_buka)): ?>
                <div class="alert alert-danger" role="alert">
                  <strong>Error!</strong> <?= $error_message_buka ?>
                </div>
                <?php endif; ?>

                <div class="col-md-12" id="result-info-buka" style="display: <?= isset($noresi_buka) ? 'block' : 'none' ?>;">
                  <div class="row">
                    <div class="col-md-12 text-center" style="margin-bottom: 10px;">
                      <div><strong>Total scan resi Anda hari ini: <span id="total_scan_buka"><?= isset($total_scan_buka) ? $total_scan_buka : 0 ?></span></strong></div>
                    </div>
                  </div>
                  <div class="row justify-content-center">
                    <div class="col-md-3">
                    </div>
                    <div class="col-md-6" style="text-align: center;">
                        <span style="color: black; font-weight: bold;">No Resi: </span>
                        <span style="color: black; font-weight: bold;" id="display_noresi_buka"><?= isset($noresi_buka) ? $noresi_buka : '' ?></span>
                    </div>
                    <div class="col-md-3">
                    </div>
                  </div>
                </div>
                <div class="panel-body" id="table-buka-retur-container" style="display: <?= isset($noresi_buka) ? 'block' : 'none' ?>;">
                  <table class="table table-striped datatable-buka-retur" style="width: 100%;">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Foto</th>
                        <th>Nama Barang</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td colspan="6" class="text-center">Loading...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
      </div>
    </div>
    </div>
  </div>
</div>

<!-- Modal buat preview foto -->
<div id="fotoModal" class="custom-popup-overlay" style="display: none;">
  <div class="modal-content-custom">
    <button type="button" id="closeModal" class="close-button">&times;</button>
    <div class="modal-header">
      <h5 class="modal-title">Preview Foto</h5>
    </div>
    <div class="modal-body" style="text-align: center;">
      <img id="previewFoto" src="" style="max-width: 100%; max-height: 70vh;">
    </div>
  </div>
</div>

<!-- modal pop up untuk proses Buka Retur per SKU -->
<div id="bukaReturModal" class="custom-popup-overlay" style="display: none; z-index: 1050;">
  <div class="custom-popup-box col-md-6 center-block float-none" style="background: white; padding: 20px; border-radius: 5px; margin-top: 100px;">
    <form action="retur/save-buka-retur-sku" method="post" class="form-horizontal nojs" id="form_proses_buka_retur" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">
            <strong>Proses Buka Retur SKU</strong>
          </h3>
        </div>

        <div class="panel-body">
          <div class="row" style="margin-bottom: 20px; padding: 0 15px;">
            <div class="col-md-3 text-center">
              <img id="modal_foto" src="" style="max-width: 100%; max-height: 100px; display: none; border-radius: 4px; border: 1px solid #ddd; padding: 4px;" />
              <div id="modal_no_foto" style="display:none; height:100px; line-height:100px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; color:#aaa;">No Image</div>
            </div>
            <div class="col-md-9">
              <h4 id="modal_nama_barang" style="font-weight: bold; margin-top: 0; line-height: 1.4; font-size: 16px;"></h4>
              <p style="margin-bottom: 5px; font-size: 14px;">SKU: <span id="modal_sku_text" style="font-weight:bold;color:#333;"></span></p>
              <input type="hidden" id="modal_sku" name="sku" />
            </div>
          </div>
          <hr style="margin-top:0; margin-bottom: 20px;">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Quantity</label>
            <div class="col-md-8 col-xs-12">
              <div class="input-group">
                <span class="input-group-btn">
                  <button type="button" class="btn btn-default btn-qty-minus"><i class="fa fa-minus"></i></button>
                </span>
                <input type="number" id="modal_qty" name="qty" class="form-control text-center" min="1" required style="font-size: 16px; font-weight: bold;"/>
                <span class="input-group-btn">
                  <button type="button" class="btn btn-default btn-qty-plus"><i class="fa fa-plus"></i></button>
                </span>
              </div>
              <small class="text-muted">Maksimal bisa diproses: <strong id="modal_max_qty"></strong></small>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Status Detail</label>
            <div class="col-md-8 col-xs-12">
              <select name="status_detail" id="modal_status_detail" class="form-control" required>
                <option value="" selected disabled>Pilihan Status</option>
                <option value="REJECT">REJECT</option>
                <option value="REFUND">REFUND</option>
                <option value="PENUKARAN_BERES">Pendingan Beres</option>
                <option value="KURANG">kurang dari penjual</option>
                <option value="KURANG_DARI_PEMBELI">KURANG DARI PEMBELI</option>
                <option value="KE_DISPLAY">KE DISPLAY</option>
                <option value="BUKAN_BARANG_KITA">BUKAN BARANG KITA</option>
              </select>
            </div>
          </div>

          <div class="hidden form-group">
            <label class="col-md-3 col-xs-12 control-label">Noresi</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" id="modal_noresi" name="noresi" class="form-control" />
              <input type="text" id="modal_id_printresi" name="id_printresi" class="form-control" />
            </div>
          </div>

          <div class="tile tile-default" id="div_container_latest_receipt_modal">
            <span id="span_latest_receipt_modal">-</span>
            <p><small id="p_latest_receipt_message_modal">Pesan respon akan muncul di sini</small></p>
          </div>

          <button type="submit" class="btn btn-info btn-submit-popup">Submit</button>
          <button type="button" class="btn btn-default btn-cancel-popup">Cancel</button>

        </div>
      </div>
    </form>
  </div>
</div>


<script type="text/javascript">
$(document).ready(function() {
    // ==================== AUTO FOCUS ON LOAD ====================
    var activeTab = "<?= isset($active_tab) ? $active_tab : 'terima-retur' ?>";
    if(activeTab === 'terima-retur') {
        $("#noresi_terima").focus();
    } else {
        $("#noresi_buka").focus();
    }

    // ==================== REQUEST QUEUE - TERIMA RETUR ====================
    var total_scan_terima = document.getElementById('total_scan_terima');
    var requestQueueTerima = [];
    var isProcessingTerima = false;

    function processQueueTerima() {
        if (isProcessingTerima || requestQueueTerima.length === 0) {
            return;
        }

        isProcessingTerima = true;
        var request = requestQueueTerima.shift();
        
        $.ajax({
            url: request.url,
            type: 'post',
            data: request.data,
            timeout: 10000,
            cache: false,
            success: function(data) {
                request.success(data);
                isProcessingTerima = false;
                processQueueTerima();
            },
            error: function(xhr, status, error) {
                request.error(xhr, status, error);
                isProcessingTerima = false;
                processQueueTerima();
            }
        });
    }

    // ==================== FORM VALIDATION & SUBMIT: TERIMA RETUR ====================
    var jvalidateTerima = $("#form_scan_terima_retur").validate({
        ignore: [],
        rules: {
            hasil_scan: {
                required: true,
            },
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            var noresiValue = $("#noresi_terima").val();

            // Immediate feedback - update UI
            $("#div_container_latest_receipt_terima").removeClass("tile-danger").addClass("tile-default");
            $("#span_latest_receipt_terima").text(noresiValue);
            $("#p_latest_receipt_message_terima").text("Dalam antrian...");
            
            // Increment counter immediately
            total_scan_terima.value = Number(total_scan_terima.value) + 1;

            // Add to queue
            requestQueueTerima.push({
                url: form.action,
                data: Object.fromEntries(formData),
                noresiValue: noresiValue,
                success: function(data) {
                    // Success feedback
                    $("#div_container_latest_receipt_terima").removeClass("tile-danger").addClass("tile-default");
                    $("#span_latest_receipt_terima").text(noresiValue);
                    $("#p_latest_receipt_message_terima").text("Nomor resi terakhir yang sudah di-scan");

                    var audio = document.getElementById('audio-alexis');
                    if (audio) { audio.currentTime=0; audio.play(); }
                },
                error: function(xhr, status, error) {
                    // Error feedback
                    var response = {};
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response.message = "Terjadi kesalahan pada server";
                    }

                    // Rollback counter on error
                    total_scan_terima.value = Number(total_scan_terima.value) - 1;

                    $("#span_latest_receipt_terima").text(noresiValue);
                    $("#div_container_latest_receipt_terima").removeClass("tile-default").addClass("tile-danger");
                    $("#p_latest_receipt_message_terima").text(response.message);

                    var audio = document.getElementById('audio-wrong');
                    if (audio) { audio.currentTime=0; audio.play(); }
                }
            });

            // Process queue
            processQueueTerima();

            // Reset form immediately for next scan
            $("#noresi_terima").val("");
            $("#noresi_terima").focus();
            
            return false;
        }
    });

    // ==================== BUKA RETUR AJAX MECHANISM ====================
    $("#form-scan-buka-retur").on('submit', function(e) {
        e.preventDefault();
        var noresiInput = $("#noresi_buka").val().trim();
        if (noresiInput === '') return;

        // Show UI elements
        $('#result-info-buka').show();
        $('#table-buka-retur-container').show();
        $("#display_noresi_buka").text(noresiInput);
        
        // Show loading in table
        var $tbody = $(".datatable-buka-retur tbody");
        $tbody.html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading data...</td></tr>');

        // Fetch data via simple AJAX (GET, session cookie auto-sent by browser)
        $.ajax({
            url: '<?= base_url("retur/get-buka-retur-sku-data") ?>/' + encodeURIComponent(noresiInput),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $tbody.empty();
                if (response.status === 'ok' && response.items && response.items.length > 0) {
                    $.each(response.items, function(i, item) {
                        var btnAction = '<button class="btn btn-warning prosesBukaRetur"' +
                            ' data-id="' + (item.id_printresi || '') + '"' +
                            ' data-noresi="' + (item.noresi || noresiInput) + '"' +
                            ' data-sku="' + (item.sku || '') + '"' +
                            ' data-qty="' + (item.jumlah || 0) + '"' +
                            ' data-nama="' + (item.nama_barang || '') + '"' +
                            ' data-foto="' + (item.link_foto || '') + '"' +
                            '>Proses Buka Retur</button>';

                        var row = '<tr>' +
                            '<td class="text-center">' + (i + 1) + '.</td>' +
                            '<td class="text-center">' + item.html_foto + '</td>' +
                            '<td class="text-center">' + item.nama_barang + '</td>' +
                            '<td class="text-center">' + (item.sku || '-') + '</td>' +
                            '<td class="text-center">' + (item.jumlah || 0) + '</td>' +
                            '<td class="text-center">' + btnAction + '</td>' +
                            '</tr>';
                        $tbody.append(row);
                    });
                } else if (response.status === 'error') {
                    $tbody.html('<tr><td colspan="6" class="text-center text-danger">Error: ' + (response.message || 'Unknown error') + '</td></tr>');
                } else {
                    $tbody.html('<tr><td colspan="6" class="text-center">Data tidak ditemukan untuk resi ini.</td></tr>');
                    var audio = document.getElementById('audio-wrong');
                    if (audio) { audio.currentTime=0; audio.play(); }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                $tbody.html('<tr><td colspan="6" class="text-center text-danger">Terjadi kesalahan saat mengambil data. Status: ' + xhr.status + '</td></tr>');
            }
        });
        
        // Refocus after short delay for next scan readiness
        setTimeout(function() {
            $("#noresi_buka").focus().select();
        }, 500);
    });

    var noresiBukaTbl = "<?= isset($noresi_buka) ? htmlspecialchars($noresi_buka, ENT_QUOTES, 'UTF-8') : '' ?>";
    if (noresiBukaTbl !== '') {
        $("#form-scan-buka-retur").submit();
    }

    // ==================== MODAL BUKA RETUR (PER SKU) ====================
    var currentBukaReturBtn = null;

    $(document).on('click', '.prosesBukaRetur', function(event) {
        event.preventDefault();
        currentBukaReturBtn = $(this);

        // Reset
        $("#form_proses_buka_retur")[0].reset();
        $("#div_container_latest_receipt_modal").removeClass("tile-danger tile-success").addClass("tile-default");
        $("#span_latest_receipt_modal").text("-");
        $("#p_latest_receipt_message_modal").text("Pilih status detail dan klik Submit");

        // Set data
        var idPrintResi = currentBukaReturBtn.data("id");
        var noresi = currentBukaReturBtn.data("noresi");
        var sku = currentBukaReturBtn.data("sku");
        var qty = currentBukaReturBtn.data("qty");
        var nama = currentBukaReturBtn.data("nama");
        var foto = currentBukaReturBtn.data("foto");

        $('#modal_id_printresi').val(idPrintResi);
        $('#modal_noresi').val(noresi);
        $('#modal_sku').val(sku);
        $('#modal_sku_text').text(sku || '-');
        $('#modal_nama_barang').text(nama || '-');
        
        if (foto && foto !== '') {
            $('#modal_foto').attr('src', foto).show();
            $('#modal_no_foto').hide();
        } else {
            $('#modal_foto').hide();
            $('#modal_no_foto').show();
        }

        $('#modal_qty').val(qty).attr('max', qty);
        $('#modal_max_qty').text(qty);

        // Show
        $('#bukaReturModal').fadeIn();
    });

    $(document).on('click', '.btn-qty-minus', function() {
        var input = $('#modal_qty');
        var val = parseInt(input.val()) || 0;
        if(val > 1) {
            input.val(val - 1);
        }
    });

    $(document).on('click', '.btn-qty-plus', function() {
        var input = $('#modal_qty');
        var val = parseInt(input.val()) || 0;
        var max = parseInt(input.attr('max')) || 0;
        if(val < max) {
            input.val(val + 1);
        }
    });
    
    $(document).on('input', '#modal_qty', function() {
        var val = parseInt($(this).val()) || 0;
        var max = parseInt($(this).attr('max')) || 0;
        if (val > max) {
            $(this).val(max);
        }
    });

    $(document).on('click', '.btn-cancel-popup', function() {
        $('#bukaReturModal').fadeOut();
    });

    $("#form_proses_buka_retur").validate({
        rules: {
            status_detail: { required: true },
            qty: { required: true, min: 1 }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            var sku = $('#modal_sku').val();
            var submittedQty = parseInt($('#modal_qty').val());

            $.ajax({
                url: form.action,
                type: 'post',
                data: Object.fromEntries(formData),
                dataType: 'json',
                success: function(response) {
                    if (response && response.message) {
                        $("#span_latest_receipt_modal").text("SUCCESS: " + sku);
                        $("#div_container_latest_receipt_modal").removeClass("tile-danger tile-default").addClass("tile-success");
                        $("#p_latest_receipt_message_modal").text(response.message);

                        var total_scan = document.getElementById('total_scan_buka');
                        if(total_scan) total_scan.innerText = parseInt(total_scan.innerText) + 1;

                        var audio = document.getElementById('audio-alexis');
                        if (audio) { audio.currentTime=0; audio.play(); }

                        // Update table row remaining quantity
                        if (currentBukaReturBtn) {
                            var currentQty = parseInt(currentBukaReturBtn.data("qty"));
                            var newQty = currentQty - submittedQty;
                            if (newQty > 0) {
                                currentBukaReturBtn.data("qty", newQty);
                                currentBukaReturBtn.closest('tr').find('td:eq(4)').text(newQty);
                            } else {
                                currentBukaReturBtn.data("qty", 0);
                                currentBukaReturBtn.closest('tr').find('td:eq(4)').text("0");
                                currentBukaReturBtn.removeClass('btn-warning prosesBukaRetur').addClass('btn-success').text("Selesai").prop('disabled', true);
                            }
                        }

                        setTimeout(function() {
                            $('#bukaReturModal').fadeOut();
                        }, 800);
                    }
                },
                error: function(xhr, status, error) {
                    var response = {};
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response.message = "Terjadi kesalahan";
                    }

                    $("#span_latest_receipt_modal").text("ERROR: " + sku);
                    $("#div_container_latest_receipt_modal").removeClass("tile-default tile-success").addClass("tile-danger");
                    $("#p_latest_receipt_message_modal").text(response.message);

                    var audio = document.getElementById('audio-wrong');
                    if (audio) { audio.currentTime=0; audio.play(); }
                }
            });
            return false;
        }
    });

    // ==================== IMAGE PREVIEW ====================
    $(document).on('click', '.foto-preview', function () {
      var fotoUrl = $(this).data('foto');

      if (fotoUrl && fotoUrl.trim() !== '') {
        $('#previewFoto').attr('src', fotoUrl);
        $('#fotoModal').fadeIn();
      } else {
        alert('Foto tidak tersedia!');
      }
    });

    $(document).on('click', '#closeModal', function () {
      $('#fotoModal').hide();
    });


    // ==================== TAB CHANGE HANDLER ====================
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (e.target.hash === '#tab-terima-retur') {
            $('#noresi_terima').focus();
        } else if (e.target.hash === '#tab-buka-retur') {
            $('#noresi_buka').focus();
        }
    });
});
</script>

<style>
.center-block {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.float-none {
    float: none !important;
}
.modal-content-custom {
    position: relative;
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
}
.close-button {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 24px;
    border: none;
    background: none;
    cursor: pointer;
}
</style>
