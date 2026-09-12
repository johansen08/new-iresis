<?php if (!empty($akses_ditolak)) : ?>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-danger">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Akses Ditolak</strong></h3>
        </div>
        <div class="panel-body">
          <p>Menu <strong>Scan Resi Packer (Webcam)</strong> untuk sementara hanya bisa dibuka oleh webmaster.</p>
          <p>Silakan pakai menu <strong>Scan Resi Packer</strong> yang biasa.</p>
        </div>
      </div>
    </div>
  </div>
<?php return; ?>
<?php endif; ?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <!-- title form -->
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Scan Resi Packer (Webcam)</strong></h3>
        <div class="pull-right" id="packer-session-controls">
            <button class="btn btn-success btn-session <?= $session_status['masuk'] ? 'hidden' : '' ?>" data-action="masuk" id="btn-session-masuk">Check-in</button>
            <button class="btn btn-warning btn-session <?= (!$session_status['masuk'] || $session_status['istirahat'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="istirahat_mulai" id="btn-session-istirahat">Istirahat</button>
            <button class="btn btn-info btn-session <?= !$session_status['istirahat'] ? 'hidden' : '' ?>" data-action="istirahat_selesai" id="btn-session-selesai">Selesai Istirahat</button>
            <button class="btn btn-danger btn-session <?= (!$session_status['masuk'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="pulang" id="btn-session-pulang">Check-out</button>
        </div>
      </div>

      <!-- search input by noresi -->
      <div class="panel-body">
        <form action="packer/scan-packer-webcam" method="post" class="form-horizontal" id="form-scan-webcam" autocomplete="off">
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  name="noresi"
                  id="noresi"
                  placeholder="Nomor resi"
                />
                  <span class="input-group-btn" id="button-group">
                    <button class="btn btn-default" type="submit">
                      <i class="fa fa-search"></i> Cari
                    </button>
                  </span>
              </div>
            </div>
          </div>
        </form>

        <!-- ====== panel kamera ====== -->
        <div class="row" id="webcam-wrap">
          <div class="col-md-6">
            <div class="input-group">
              <select id="webcam-device" class="form-control">
                <option value="">Kamera bawaan</option>
              </select>
              <span class="input-group-btn">
                <button type="button" class="btn btn-primary" id="btn-kamera-mulai">
                  <i class="fa fa-camera"></i> Nyalakan Kamera
                </button>
                <button type="button" class="btn btn-danger hidden" id="btn-kamera-stop">
                  <i class="fa fa-stop"></i> Matikan Kamera
                </button>
              </span>
            </div>
            <p class="help-block" id="webcam-status">
              Kamera mati. Nomor resi tetap bisa diketik atau di-scan manual di kolom atas.
            </p>
          </div>
          <div class="col-md-6">
            <video id="webcam-preview" playsinline muted
                   style="width: 100%; max-height: 240px; background: #000; border-radius: 4px;"></video>
          </div>
        </div>
        <!-- ====== panel kamera ====== -->
      </div>

      <!-- table untuk data resi -->
      <?php if (!empty($noresi)) : ?>
          <div class="col-md-12" id="result-info">
              <div id="button-footer">
                  <div class="text-left" style="margin-top: 10px;">
                      <button id="submit-selected" class="btn btn-success mb-2" style="margin-bottom: 10px;">
                          <strong>Submit</strong>
                      </button>
                      <button id="btn-reset" class="btn btn-warning mb-2" style="margin-bottom: 10px;">
                          <strong>Reset</strong>
                      </button>
                  </div>
              </div>
              <div class="row">
                  <div class="col-md-4 text-center" style="border-right: 1px solid #ccc;">
                      <div><strong>Total Scan: <?= $total_scan ?></strong></div>
                  </div>
                  <div class="col-md-4 text-center" style="border-right: 1px solid #ccc;">
                      <div><strong>Picker: <?= $nama_picker ?></strong></div>
                  </div>
                  <div class="col-md-4 text-center">
                      <div><strong>Komputer: <?= $komputer_packer ?></strong></div>
                  </div>
              </div>
              <div class="row justify-content-center">
                  <div class="col-md-3">
                  </div>
                  <div class="col-md-2">
                      <input type="text"
                             class="form-control text-center"
                             readonly
                             value="No Resi:"
                             style="
                                background-color: transparent;
                                border: none;
                                color: black;
                                font-weight: bold;
                                cursor: text;"
                      />
                  </div>
                  <div class="col-md-4">
                      <input type="text"
                             name="noresi"
                             id="noresi-detail"
                             class="form-control text-center mx-auto"
                             readonly
                             value="<?= isset($noresi) ? htmlspecialchars($noresi, ENT_QUOTES, 'UTF-8') : '' ?>"
                             style="
                                background-color: transparent;
                                color: black;
                                font-weight: bold;
                                cursor: text;"
                      />
                  </div>
                  <div class="col-md-3">
                  </div>
              </div>
          </div>
          <div class="panel-body" id="table-scan-packer">
            <table class="table table-striped datatable-masalah-picker">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Foto</th>
                  <th>Nama Barang</th>
                  <th>Jenis Packing</th>
                  <th>SKU</th>
                  <th>Quantity</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="7" class="text-center">No details</td>
                </tr>
              </tbody>
            </table>
          </div>
      <?php endif; ?>

      <!-- modal pop up untuk masalah picker -->
      <div id="masalahPickerModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box col-md-6 center-block float-none">
          <form action="packer/masalah-picker-save" method="post" class="form-horizontal" id="from_scan_packer" autocomplete="off">
            <div class="panel panel-default">
              <div class="panel-heading">
                <h3 class="panel-title">
                  <strong>Submit Masalah Picker</strong>
                </h3>
              </div>

              <div class="panel-body">

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Nama Picker</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" id="modal_nama_picker" class="form-control" readonly />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">SKU</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" id="modal_sku" class="form-control" readonly />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Quantity</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" id="modal_qty" class="form-control" readonly />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">No Rak</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" id="modal_no_rak" class="form-control" readonly />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Action</label>
                  <div class="col-md-8 col-xs-12">
                    <select name="type_masalah" id="type_masalah" class="form-control selectpicker" data-live-search="true">
                      <?php foreach ((isset($list_type_masalah) && is_array($list_type_masalah) ? $list_type_masalah : []) as $masalah) : ?>
                        <option value="<?= $masalah['id_typemasalah'] ?>"><?= $masalah['type_masalah'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Quantity Bermasalah</label>
                  <div class="col-md-8 col-xs-12">
                    <select name="qty_bermasalah" id="qty_bermasalah" class="form-control selectpicker" data-live-search="true">

                    </select>
                  </div>
                </div>

                <div class="form-group hidden" id="div_sku_salah">
                  <label class="col-md-3 col-xs-12 control-label">SKU Salah</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" name="sku_salah" id="sku_salah" class="form-control" />
                  </div>
                </div>

                <div class="hidden form-group">
                  <label class="col-md-3 col-xs-12 control-label">Noresi</label>
                  <div class="col-md-8 col-xs-12">
                    <input type="text" value="<?= isset($noresi) ? htmlspecialchars($noresi, ENT_QUOTES, 'UTF-8') : '' ?>" name="noresi" id="noresi" class="form-control" />
                  </div>
                </div>

                <div class="tile tile-default" id="div_container_latest_receipt">
                  <span id="span_latest_receipt">-</span>
                  <p><small id="p_latest_receipt_message">Nomor SKU terakhir yang sudah di-scan</small></p>
                </div>

                <button type="submit" class="btn btn-info btn-submit-popup">Submit</button>
                <button type="reset" class="btn btn-primary">Reset</button>
                <button type="button" class="btn btn-default btn-cancel-popup">Cancel</button>

              </div>
            </div>
          </form>
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

      <!-- Success Auto Popup Modal -->
      <div id="successModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box success-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #5cb85c; color: white;">
              <h3 class="panel-title">
                <i class="fa fa-check-circle"></i> <strong>Success!</strong>
              </h3>
            </div>
            <div class="panel-body">
              <p id="successMessage" style="font-size: 16px; margin: 20px 0;">Data berhasil disubmit!</p>
              <div class="progress" style="margin: 10px 0;">
                <div id="progressBar" class="progress-bar progress-bar-success" role="progressbar" style="width: 100%; transition: width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Auto Popup Modal -->
      <div id="errorModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box error-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #d9534f; color: white;">
              <h3 class="panel-title">
                <i class="fa fa-times-circle"></i> <strong>Error!</strong>
              </h3>
            </div>
            <div class="panel-body">
              <p id="errorMessage" style="font-size: 16px; margin: 20px 0;">Terjadi kesalahan!</p>
              <div class="progress" style="margin: 10px 0;">
                <div id="errorProgressBar" class="progress-bar progress-bar-danger" role="progressbar" style="width: 100%; transition: width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript">
  var table;
  var idPrintResi;
  var noresi;
  var sku;
  var qty;
  var scanFeedback = <?= json_encode(isset($scan_feedback) ? $scan_feedback : null) ?>;

  $().ready(function() {

    // agar saat tampilkan halaman, kursor langsung muncul di form input noresi
    $(document).ready(function() {
      $('#noresi').focus();
    });
    
    // Packer Monitoring Session JS
    $(document).on('click', '.btn-session', function() {
      var action = $(this).data('action');
      var $btn = $(this);
      console.log('Session button clicked:', action);
      
      $.ajax({
          url: '<?= base_url("packer_monitoring/update_session") ?>?t=' + new Date().getTime(),
          method: 'POST',
          data: { action: action },
          dataType: 'json',
          success: function(res) {
              console.log('Session update response:', res);
              if (res.code == 200) {
                  // Show success message using Noty if available, else alert
                  if (typeof noty === 'function') {
                      noty({
                          text: 'Berhasil: ' + action.replace('_', ' ').toUpperCase(),
                          layout: 'topRight',
                          type: 'success',
                          timeout: 2000
                      });
                  } else {
                      alert('Berhasil: ' + action.replace('_', ' ').toUpperCase());
                  }
                  
                  if (action == 'istirahat_mulai') {
                      $('#btn-session-istirahat').addClass('hidden');
                      $('#btn-session-selesai').removeClass('hidden');
                  } else if (action == 'istirahat_selesai') {
                      $('#btn-session-selesai').addClass('hidden');
                      $('#btn-session-istirahat').removeClass('hidden');
                  }
              } else {
                  alert('Error: ' + res.message);
              }
          },
          error: function(xhr, status, error) {
              console.error('Session update failed:', error);
              alert('Gagal menghubungi server. Periksa koneksi atau console log.');
          }
      });
    });

    // menampilkan data list sku by noresi yang di input
    $(document).ready(function() {

      let noresiTbl = "<?= isset($noresi) ? htmlspecialchars($noresi, ENT_QUOTES, 'UTF-8') : '' ?>";

      table = $('.datatable-masalah-picker').DataTable({
        'scrollX': true,
        'pageLength': 10,
        'processing': true,
        'serverSide': true,
        'order': [
          [2, 'desc']
        ],
        'lengthMenu': [
          [10, 50, 100, 150, 200],
          [10, 50, 100, 150, 200]
        ],
        'columnDefs': [
          { width: '5%', targets: 0 },
          { width: '15%', targets: 1 },
          { width: '25%', targets: 2 },
          { width: '15%', targets: 3 },
          { width: '15%', targets: 4 },
          { width: '10%', targets: 5 },
          { width: '15%', targets: 6 },
          { className: 'text-center', targets: [0, 1, 2, 3, 4, 5, 6] }
        ],
        'ajax': {
          url: 'packer/get-scan-packer-data/' + noresiTbl,
          type: 'POST',
        },
      });
    });

    // ====================== modal masalah picker ======================

    // tampilkan modal masalah picker
    $(document).on('click', '.saveMasalahPickera', function() {
      alert('Fitur ini belum bisa digunakan');
    });

    $(document).on('click', '.saveMasalahPicker', function() {

      event.preventDefault();

      $('#masalahPickerModal').show();

      hargajualpcs = $(this).data("hargajualpcs");
      idPrintResi = $(this).data("id");
      noresi = $(this).data("noresi");

      // Get values from data attributes
      const namapicker = $(this).data("nama-picker");
      sku = $(this).data("sku");
      qty = $(this).data("qty");
      const noRak = $(this).data("no-rak");

      $('#modal_nama_picker').val(namapicker);
      $('#modal_sku').val(sku);
      $('#modal_qty').val(qty);
      $('#modal_no_rak').val(noRak);

      // Update qty_bermasalah dropdown
      const $qtyDropdown = $('#qty_bermasalah');
      $qtyDropdown.empty();
      for (let i = 1; i <= parseInt(qty); i++) {
          $qtyDropdown.append(`<option value="${i}">${i}</option>`);
      }

      $qtyDropdown.selectpicker('refresh');

      // supaya bisa cari dengan ketik
      $('.selectpicker').selectpicker('render');

    });

    // tutup modal masalah picker
    $(document).on('click', '.btn-cancel-popup', function() {
      $('#masalahPickerModal').hide();
      $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
    });

    // ini kondisi ketika memilih tipe masalah pada modal masalah picker
    $('#type_masalah').on('change', function() {
      // const id_alasan = this.value;
      const id_typemasalah = $(this).val();

      // console.log("typemasalah", id_typemasalah);

      if (id_typemasalah === "4") { // condition for PAKET SALAH KERANGJANG OLEH KARYAWAN
        $('#div_sku_salah').removeClass('hidden');
      } else {
        $('#div_sku_salah').addClass('hidden');
      }
    });

    // aksi button submit dari modal masalah picker, simpan ke tblmasalahpicker per sku
    $(document).on('click', '.btn-submit-popup', function () {

      var jvalidate = $("#from_scan_packer").validate({
        ignore: [],
        rules: {
          type_masalah: {
            required: true,
          }
        },
        submitHandler: function(form) {
          var formData = new FormData(form);

          // formData.append("noresi", noresi);
          formData.append("sku", sku);
          formData.append("qty", qty);
          formData.append("id_printresi", idPrintResi);

          // Keep noresi submittable to allow reporting multiple SKUs for the same receipt

          $.ajax({
            url: form.action,
            type: 'post',
            data: Object.fromEntries(formData),
            success: function(data) {},
            error: function(data) {},
          }).done(function(response) {
            $("#span_latest_receipt").text(sku);

            playAudio('audio-alert');

            $('#masalahPickerModal').hide();
            // $('.custom-popup-overlay').fadeOut();
            if (typeof table !== 'undefined') {
              table.ajax.reload(null, false);
            }
            // row.fadeOut(500, function() { $(this).remove(); });

          }).fail(function(response) {

            $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");

            playAudio('audio-fail');
          });
          return false; // required to block normal submit since you used ajax
        }
      });

    });

    // ====================== modal masalah picker ======================

    // ====================== modal foto ======================

    // event ketika klik gambar foto yang ada di tabel
    $(document).on('click', '.foto-preview', function() {
      var fotoUrl = $(this).data('foto');

        if (fotoUrl && fotoUrl.trim() !== '') {
            $('#previewFoto').attr('src', fotoUrl);   // ✅ Set the image source
            $('#fotoModal').fadeIn();                 // ✅ Show the modal
        } else {
            alert('Foto tidak tersedia!');
        }
    });

    // event ketika klik button lihat foto (for backward compatibility)
    $(document).on('click', '.lihat-foto', function() {
      var fotoUrl = $(this).data('foto');

        if (fotoUrl && fotoUrl.trim() !== '') {
            $('#previewFoto').attr('src', fotoUrl);   // ✅ Set the image source
            $('#fotoModal').fadeIn();                 // ✅ Show the modal
        } else {
            alert('Foto tidak tersedia!');
        }
    });

    // Tombol close
    $(document).on('click', '#closeModal', function() {
      $('#fotoModal').hide();
    });

    // ====================== modal foto ======================

    // Select/Deselect semua checkbox saat klik #select-all
    $(document).on('change', '#select-all', function() {
      var isChecked = $(this).is(':checked');
      $('.row-select').prop('checked', isChecked);
    });

    // Kalau ada 1 checkbox di-uncheck manual, #select-all juga ikut uncheck
    $(document).on('change', '.row-select', function() {
      if (!$(this).is(':checked')) {
        $('#select-all').prop('checked', false);
      } else {
        // Kalau semua checkbox ke-check, otomatis select-all ke-check juga
        if ($('.row-select:checked').length === $('.row-select').length) {
          $('#select-all').prop('checked', true);
        }
      }
    });

    // Handle submit selected, simpan ke tblpacking
    $('#submit-selected').on('click', function() {
      const noresi = $('#noresi-detail').val();

      if (!noresi || noresi.trim() === '') {
          // Show error popup for empty noresi
          $('#errorMessage').text("Nomor resi tidak boleh kosong!");
          $('#errorModal').fadeIn();
          setTimeout(function() {
            $('#errorModal').fadeOut();
          }, 1000);
          return;
      }

      // Kirim pakai Ajax (status_performa otomatis dari session di controller)
      $.ajax({
        url: 'packer/save-packer', // Ganti sesuai route kamu
        method: 'POST',
        data: { 
          noresi: noresi
        },
        success: function(response) {
          // alert("Data berhasil disubmit!");
          $('#successModal').fadeIn();

          setTimeout(function() {
            $('#successModal').fadeOut();
          }, 1000);

            resetScanView();
        },
        error: function(xhr, status, error) {
          // Show error popup instead of alert
          let errorText = 'Terjadi kesalahan saat memproses data!';

          // Try to parse JSON response and extract message
          try {
            let response = JSON.parse(xhr.responseText);
            if (response.message) {
              errorText = response.message;
            }
          } catch (e) {
            // If not JSON, use responseText directly or default message
            errorText = xhr.responseText || errorText;
          }

          $('#errorMessage').text(errorText);
          $('#errorModal').fadeIn();

          setTimeout(function() {
            $('#errorModal').fadeOut();
          }, 1000);
        }
      });
    });

    $('#btn-reset').on('click', function () {
        resetScanView();
    });

    triggerScanFeedback(scanFeedback);

    inisialisasiKamera();
  })

  function triggerScanFeedback(feedback) {
    if (!feedback || !feedback.message) {
      return;
    }

    var notyType = mapFeedbackType(feedback.type);
    noty({
      text: feedback.message,
      layout: 'topRight',
      type: notyType,
      timeout: feedback.status === 'auto_save_success' ? 1500 : 2500
    });

    playFeedbackAudio(feedback.status, feedback.exception_code);

    if (feedback.status === 'auto_save_success') {
      resetScanView();
    }
  }

  function mapFeedbackType(type) {
    var allowed = ['success', 'error', 'warning', 'information'];
    if (allowed.indexOf(type) !== -1) {
      return type;
    }
    return 'information';
  }

  // Semua suara di halaman ini lewat sini, jangan panggil .play() langsung.
  // suaraScan (main.php) memotong durasi dan mereset posisi: error.mp3 saja
  // panjangnya ~3,8 detik, dan tanpa reset currentTime scan kedua yang datang
  // sebelum suara pertama habis tidak berbunyi sama sekali.
  function playAudio(id) {
    if (typeof suaraScan === 'function') {
      suaraScan(id);
      return;
    }

    var el = document.getElementById(id);
    if (el && typeof el.play === 'function') {
      el.currentTime = 0;
      el.play();
    }
  }

  function playFeedbackAudio(status, exceptionCode) {
    var audioId = 'audio-error';
    if (status === 'auto_save_success') {
      audioId = 'audio-alert';
    } else if (status === 'auto_save_failed') {
      // Dulu semua kegagalan auto-save bunyinya audio-fail, padahal dua
      // penyebab paling sering -- resi sudah di-packing dan pesanan sudah
      // dibatalkan -- menuntut tindakan yang berbeda dari operator.
      // exception_code-nya dikirim handle_double_scan_state() di Packer.php.
      if (exceptionCode === 'ALREADY_PACKED') {
        audioId = 'audio-sudah-packing';
      } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
        audioId = 'audio-cancel-order';
      } else {
        audioId = 'audio-fail';
      }
    }

    playAudio(audioId);
  }

  function resetScanView() {
    $('#result-info').hide();
    $('#table-scan-packer').hide();
    $('#button-footer').hide();
    $('#noresi').val('').focus();
    $('#noresi-detail').val('');
  }


  // ====================== scanner kamera ======================
  //
  // Halaman ini SPA: tiap submit nomor resi mengganti isi .page-content-wrap,
  // jadi elemen <video> yang lama ikut dibuang dan stream kamera mati. Supaya
  // operator tidak perlu menyalakan kamera ulang tiap satu resi, status "kamera
  // menyala" dan kamera yang dipilih disimpan di sessionStorage lalu dipulihkan
  // otomatis saat halaman dirender ulang.
  //
  // Dekodernya ZXing UMD yang dilayani dari assets sendiri (tanpa CDN), dimuat
  // baru saat kamera pertama kali dinyalakan supaya halaman tidak ikut berat
  // kalau operator ternyata tetap memakai scanner gun.

  var KUNCI_KAMERA_AKTIF  = 'packer_webcam_aktif';
  var KUNCI_KAMERA_DEVICE = 'packer_webcam_device';
  var URL_ZXING = '<?= base_url("assets/js/plugins/zxing/zxing.min.js") ?>';

  window.packerWebcam = window.packerWebcam || {
    reader: null,
    jaga: null,
    kodeTerakhir: '',
    waktuTerakhir: 0,
    mengirim: false
  };

  function simpanPrefKamera(kunci, nilai) {
    try { window.sessionStorage.setItem(kunci, nilai); } catch (e) {}
  }

  function bacaPrefKamera(kunci) {
    try { return window.sessionStorage.getItem(kunci); } catch (e) { return null; }
  }

  function statusKamera(teks) {
    $('#webcam-status').text(teks);
  }

  function tampilTombolKamera(menyala) {
    $('#btn-kamera-mulai').toggleClass('hidden', menyala);
    $('#btn-kamera-stop').toggleClass('hidden', !menyala);
  }

  function muatZxing() {
    var d = $.Deferred();

    if (typeof ZXing !== 'undefined') {
      d.resolve();
    } else {
      $.ajax({ url: URL_ZXING, dataType: 'script', cache: true })
        .done(function() { d.resolve(); })
        .fail(function() { d.reject(); });
    }

    return d.promise();
  }

  function isiDaftarKamera(reader) {
    if (!reader || typeof reader.listVideoInputDevices !== 'function') {
      return;
    }

    reader.listVideoInputDevices().then(function(daftar) {
      var $sel = $('#webcam-device');
      var dipilih = $sel.val() || '';

      $sel.empty().append($('<option></option>').attr('value', '').text('Kamera bawaan'));
      $.each(daftar, function(i, perangkat) {
        $sel.append($('<option></option>')
          .attr('value', perangkat.deviceId)
          .text(perangkat.label || ('Kamera ' + (i + 1))));
      });

      // Kalau deviceId simpanan sudah tidak ada (kamera dicabut), balik ke bawaan.
      $sel.val(dipilih);
      if ($sel.val() === null) {
        $sel.val('');
      }
    }).catch(function() {});
  }

  function hentikanKamera(matikanPref) {
    var w = window.packerWebcam;

    if (w.reader) {
      try { w.reader.reset(); } catch (e) {}
      w.reader = null;
    }

    if (w.jaga) {
      clearInterval(w.jaga);
      w.jaga = null;
    }

    w.mengirim = false;

    if (matikanPref) {
      simpanPrefKamera(KUNCI_KAMERA_AKTIF, '0');
      tampilTombolKamera(false);
      statusKamera('Kamera mati. Nomor resi tetap bisa diketik atau di-scan manual di kolom atas.');
    }
  }

  function mulaiKamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      statusKamera('Browser tidak mengizinkan akses kamera di halaman ini. Buka lewat http://localhost:8080 atau https://, bukan alamat IP polos.');
      return;
    }

    statusKamera('Menyiapkan kamera...');

    muatZxing().done(function() {
      var w = window.packerWebcam;

      // Bersihkan sisa reader lama sebelum bikin yang baru, supaya tidak ada dua
      // stream jalan bersamaan setelah halaman dirender ulang.
      hentikanKamera(false);

      w.reader = new ZXing.BrowserMultiFormatReader();

      var deviceId = $('#webcam-device').val() || null;

      w.reader.decodeFromVideoDevice(deviceId, 'webcam-preview', function(hasil) {
        // Parameter error-nya sengaja tidak dipakai: ZXing melempar
        // NotFoundException di setiap frame yang tidak berisi barcode, dan itu
        // kondisi normal, bukan kegagalan.
        if (hasil) {
          tanganiHasilScan(hasil.getText ? hasil.getText() : hasil.text);
        }
      }).catch(function(err) {
        hentikanKamera(true);
        statusKamera('Gagal membuka kamera: ' + ((err && err.message) ? err.message : err));
      });

      simpanPrefKamera(KUNCI_KAMERA_AKTIF, '1');
      tampilTombolKamera(true);
      statusKamera('Kamera menyala. Arahkan barcode resi ke kamera.');

      // Label kamera baru terisi setelah izin diberikan, jadi daftarnya diisi
      // ulang sesaat setelah stream jalan.
      setTimeout(function() { isiDaftarKamera(w.reader); }, 1200);

      pasangPenjagaKamera();
    }).fail(function() {
      statusKamera('Gagal memuat pustaka pemindai (zxing.min.js). Periksa berkas di assets/js/plugins/zxing/.');
    });
  }

  // Saat pengguna pindah menu, .page-content-wrap diganti dan <video> hilang
  // tanpa memicu event apa pun. Tanpa penjaga ini lampu kamera tetap menyala
  // dan stream-nya bocor sampai tab ditutup.
  function pasangPenjagaKamera() {
    var w = window.packerWebcam;

    if (w.jaga) {
      clearInterval(w.jaga);
    }

    w.jaga = setInterval(function() {
      var video = document.getElementById('webcam-preview');
      if (!video || !document.body.contains(video)) {
        hentikanKamera(false);
      }
    }, 1000);
  }

  function tanganiHasilScan(kode) {
    var w = window.packerWebcam;

    kode = $.trim(kode || '');
    if (kode.length < 4 || w.mengirim) {
      return;
    }

    // Satu barcode terbaca puluhan kali per detik. Tanpa jeda ini satu resi
    // terkirim berkali-kali dan langsung memicu logika double-scan di server.
    var sekarang = new Date().getTime();
    if (kode === w.kodeTerakhir && (sekarang - w.waktuTerakhir) < 3000) {
      return;
    }

    w.kodeTerakhir = kode;
    w.waktuTerakhir = sekarang;
    w.mengirim = true;

    statusKamera('Terbaca: ' + kode);

    $('#noresi').first().val(kode);
    $('#form-scan-webcam').submit();
  }

  function inisialisasiKamera() {
    // Reader sisa render sebelumnya wajib dimatikan dulu: elemen <video>-nya
    // sudah dibuang, tapi objek ZXing-nya masih memegang stream.
    hentikanKamera(false);

    var deviceTersimpan = bacaPrefKamera(KUNCI_KAMERA_DEVICE);
    if (deviceTersimpan) {
      $('#webcam-device').append($('<option></option>').attr('value', deviceTersimpan).text('Kamera terakhir dipakai'));
      $('#webcam-device').val(deviceTersimpan);
    }

    // Tombol di-bind langsung ke elemennya (bukan lewat $(document)) karena
    // elemen ini selalu baru tiap render; delegasi ke document akan menumpuk
    // handler setiap kali halaman dibuka ulang.
    $('#btn-kamera-mulai').on('click', function() {
      mulaiKamera();
    });

    $('#btn-kamera-stop').on('click', function() {
      hentikanKamera(true);
    });

    $('#webcam-device').on('change', function() {
      simpanPrefKamera(KUNCI_KAMERA_DEVICE, $(this).val() || '');
      if (window.packerWebcam.reader) {
        mulaiKamera();
      }
    });

    if (bacaPrefKamera(KUNCI_KAMERA_AKTIF) === '1') {
      mulaiKamera();
    } else {
      tampilTombolKamera(false);
    }
  }

  // ====================== scanner kamera ======================

</script>
<style>
  .custom-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
  }

  .custom-popup-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 50%;
    text-align: center;
  }

  .custom-popup-buttons {
    margin-top: 15px;
  }

  .custom-popup-buttons button {
    margin: 0 5px;
  }

  table.dataTable tbody td {
    vertical-align: middle;
    padding-top: 30px;
    padding-bottom: 30px;
  }

  .modal-content-custom {
    position: relative;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
  }

  /* .modal-content-custom {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    } */

    .close-button {
      position: absolute;
      top: -15px;
      right: -2px;
      font-size: 34px;
      background: none;
      border: none;
      color: #333;
      cursor: pointer;
      z-index: 10;
  }

  .close-button:hover {
    color: red;
  }

  #result-info {
      display: <?= empty($noresi) ? 'none' : 'block' ?>;
  }

  /* Style untuk success modal */
  .success-popup {
    text-align: center;
  }

  .success-popup .panel-heading {
    background-color: #5cb85c;
    color: white;
  }

  .success-popup .progress {
    height: 5px;
    background-color: #f5f5f5;
  }

  .success-popup .progress-bar {
    background-color: #5cb85c;
  }

  /* Style untuk error modal */
  .error-popup {
    text-align: center;
  }

  .error-popup .panel-heading {
    background-color: #d9534f;
    color: white;
  }

  .error-popup .progress {
    height: 5px;
    background-color: #f5f5f5;
  }

  .error-popup .progress-bar {
    background-color: #d9534f;
  }
</style>
