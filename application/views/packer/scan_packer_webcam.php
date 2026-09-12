<?php
// Halaman ini SENGAJA salinan utuh packer/scan_packer.php, bukan turunannya.
// Scan Resi Packer versi biasa tetap dipakai sebagai cadangan kalau kamera
// bermasalah, jadi kedua halaman harus bisa berubah sendiri-sendiri.
// Duplikasi di sini disengaja -- jangan disatukan jadi satu view.
?>
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
        <form action="packer/scan-packer-webcam" method="post" class="form-horizontal" autocomplete="off">
          <!-- Status kamera ikut terkirim tiap scan. Dipakai server saat setelan
               "wajib kamera" menyala: scan ditolak kalau nilainya bukan "siap",
               supaya tidak ada paket tersimpan tanpa rekaman. -->
          <input type="hidden" name="kamera_status" id="kamera-status" value="belum" />
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

        <!-- ====== panel rekam video packing ====== -->
        <div class="row" id="rekam-wrap">
          <div class="col-md-7">
            <div id="rekam-status" class="alert alert-warning" style="margin-bottom: 10px;">
              Kamera mati. Nomor resi masih bisa di-scan, tapi packing-nya tidak akan terekam.
            </div>

            <div class="form-group hidden" id="pilih-kamera-wrap" style="max-width: 340px;">
              <label class="control-label" for="pilih-kamera">Kamera</label>
              <select id="pilih-kamera" class="form-control"></select>
            </div>

            <button type="button" class="btn btn-primary" id="btn-kamera-mulai">
              <i class="fa fa-video-camera"></i> Nyalakan Kamera
            </button>
            <button type="button" class="btn btn-default hidden" id="btn-kamera-stop">
              <i class="fa fa-power-off"></i> Matikan Kamera
            </button>

            <span id="rekam-indikator" class="hidden" style="margin-left: 12px; font-weight: bold; color: #d9534f;">
              <i class="fa fa-circle"></i> REC <span id="rekam-durasi">00:00</span>
            </span>

            <?php if (!empty($is_webmaster)) : ?>
              <hr style="margin: 12px 0;" />
              <div id="setelan-video">
                <strong>Setelan rekam</strong>
                <small class="text-muted">(hanya webmaster)</small>
                <div class="row" style="margin-top: 8px;">
                  <div class="col-md-4">
                    <label class="control-label" for="setelan-resolusi">Resolusi</label>
                    <select id="setelan-resolusi" class="form-control">
                      <option value="720p" <?= $setelan_video['resolusi'] === '720p' ? 'selected' : '' ?>>720p (hemat)</option>
                      <option value="1080p" <?= $setelan_video['resolusi'] === '1080p' ? 'selected' : '' ?>>1080p</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="control-label" for="setelan-batas">Batas rekam (menit)</label>
                    <input type="number" id="setelan-batas" class="form-control" min="1" max="120"
                           value="<?= (int) $setelan_video['batas_menit'] ?>" />
                  </div>
                  <div class="col-md-4">
                    <label class="control-label">&nbsp;</label>
                    <button type="button" class="btn btn-default btn-block" id="btn-simpan-setelan">
                      Simpan Setelan
                    </button>
                  </div>
                </div>
                <div class="checkbox" style="margin-top: 4px;">
                  <label>
                    <input type="checkbox" id="setelan-wajib" <?= !empty($setelan_video['wajib_kamera']) ? 'checked' : '' ?> />
                    Tolak scan kalau kamera belum siap
                  </label>
                </div>

                <p class="help-block" style="margin-top: 8px;">
                  Rekaman berhenti sendiri di batas ini dan tetap disimpan (terpotong), tidak dibuang.
                  Setelan baru berlaku saat kamera dinyalakan ulang.<br />
                  <strong>Tolak scan</strong> menutup celah packing tanpa rekaman, tapi juga menghentikan
                  lini packing kalau kamera bermasalah — halaman Scan Resi Packer biasa tetap bisa dipakai
                  sebagai cadangan.<br />
                  Folder simpan: <code><?= htmlspecialchars($folder_video, ENT_QUOTES, 'UTF-8') ?></code>
                </p>

                <hr style="margin: 12px 0;" />

                <strong>Cari video packing</strong>
                <div class="row" style="margin-top: 8px;">
                  <div class="col-md-8">
                    <div class="input-group">
                      <input type="text" id="cari-video-resi" class="form-control" placeholder="Nomor resi" autocomplete="off" />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default" id="btn-cari-video">
                          <i class="fa fa-search"></i> Cari
                        </button>
                      </span>
                    </div>
                  </div>
                </div>

                <div id="hasil-cari-video" style="margin-top: 10px;"></div>
              </div>
            <?php endif; ?>
          </div>

          <div class="col-md-5">
            <video id="rekam-preview" playsinline muted
                   style="width: 100%; max-height: 260px; background: #000; border-radius: 4px;"></video>
          </div>
        </div>
        <!-- ====== panel rekam video packing ====== -->
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
        url: 'packer/save-packer-webcam', // endpoint sendiri, terpisah dari Scan Resi Packer biasa
        method: 'POST',
        data: { 
          noresi: noresi
        },
        success: function(response) {
          // make_ajax_response() selalu balas HTTP 200 dan menaruh statusnya
          // di body, jadi gagal-simpan ikut mendarat di handler ini. Tanpa
          // pemeriksaan res.code, video bisa terunggah untuk resi yang
          // sebenarnya GAGAL tersimpan.
          var res = response;
          if (typeof res === 'string') {
            try { res = JSON.parse(res); } catch (e) { res = null; }
          }

          if (!res || res.code !== 201) {
            $('#errorMessage').text((res && res.message) ? res.message : 'Data tidak tersimpan.');
            $('#errorModal').fadeIn();
            setTimeout(function() { $('#errorModal').fadeOut(); }, 1500);
            return; // rekaman sengaja dibiarkan jalan supaya bisa dicoba lagi
          }

          // Video baru diunggah SETELAH simpan terkonfirmasi.
          selesaikanRekam(noresi);

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
        buangRekam('reset oleh packer');
        resetScanView();
    });

    inisialisasiRekam();
    triggerScanFeedback(scanFeedback);
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


  // ====================== rekam video packing ======================
  //
  // Halaman ini SPA: setiap scan resi mengganti isi .page-content-wrap, jadi
  // elemen <video> ikut dibuang. MediaStream dan MediaRecorder karena itu
  // disimpan di window, bukan di dalam closure halaman, lalu dipasang ulang ke
  // elemen <video> yang baru tiap render. Tanpa itu rekaman mati setiap kali
  // packer men-scan resi berikutnya.
  //
  // Videonya diunggah SEKALI setelah simpan terkonfirmasi, bukan potongan
  // berkala. Konsekuensinya sengaja: rekaman yang terputus (pindah menu, tab
  // ditutup, Chrome mati) tidak pernah sampai ke server, jadi "dibuang" terjadi
  // dengan sendirinya -- tidak ada berkas separuh jadi yang perlu disapu.

  var VIDEO_SETELAN   = <?= json_encode(isset($setelan_video) ? $setelan_video : ['resolusi' => '720p', 'batas_menit' => 15]) ?>;
  var NORESI_HALAMAN  = <?= json_encode(isset($noresi) ? $noresi : '') ?>;
  var SUDAH_TERSIMPAN = <?= json_encode(!empty($scan_feedback['auto_saved'])) ?>;

  var KUNCI_KAMERA_AKTIF = 'packer_rekam_aktif';

  // Kamera pilihan diingat per KOMPUTER (localStorage, bukan sessionStorage),
  // karena satu meja packing selalu memakai webcam yang sama.
  var KUNCI_DEVICE = 'packer_video_device_id';

  // Toleransi hilangnya elemen <video> sebelum rekaman dianggap ditinggalkan.
  // Saat berpindah halaman SPA, .page-content-wrap sempat diisi spinner, jadi
  // elemennya memang hilang sebentar -- tanpa toleransi ini rekaman yang masih
  // sah ikut dibuang.
  var BATAS_JAGA_HILANG = 5;

  window.rekamPacking = window.rekamPacking || {
    stream: null,
    recorder: null,
    potongan: [],
    noresi: '',
    statusKamera: 'belum', // belum | membuka | siap | gagal | tidak-didukung
    mulai: 0,
    timerUI: null,
    jaga: null,
    jagaHilang: 0,
    finalisasi: false,
    terpotong: false,
    menunggu: '',
    mengunggah: false
  };

  function simpanPrefKamera(nilai) {
    try { window.sessionStorage.setItem(KUNCI_KAMERA_AKTIF, nilai); } catch (e) {}
  }

  function bacaPrefKamera() {
    try { return window.sessionStorage.getItem(KUNCI_KAMERA_AKTIF); } catch (e) { return null; }
  }

  function bacaDeviceId() {
    try { return window.localStorage.getItem(KUNCI_DEVICE) || ''; } catch (e) { return ''; }
  }

  function simpanDeviceId(nilai) {
    try { window.localStorage.setItem(KUNCI_DEVICE, nilai); } catch (e) {}
  }

  /**
   * Status kamera disimpan di window DAN ditulis ke field tersembunyi form.
   * Field itu ikut terkirim di setiap scan, jadi server bisa menolak scan saat
   * setelan "wajib kamera" menyala.
   */
  function setStatusKamera(nilai) {
    window.rekamPacking.statusKamera = nilai;
    $('#kamera-status').val(nilai);
  }

  function statusRekam(teks, jenis) {
    var $s = $('#rekam-status');
    if (!$s.length) { return; }
    $s.removeClass('alert-warning alert-success alert-danger alert-info')
      .addClass('alert-' + (jenis || 'info'))
      .html(teks);
  }

  function tampilTombolKamera(menyala) {
    $('#btn-kamera-mulai').toggleClass('hidden', menyala);
    $('#btn-kamera-stop').toggleClass('hidden', !menyala);
  }

  function formatDurasi(detik) {
    var m = Math.floor(detik / 60), d = detik % 60;
    return (m < 10 ? '0' : '') + m + ':' + (d < 10 ? '0' : '') + d;
  }

  // ── kamera ───────────────────────────────────────────────────────────

  function kendalaVideo() {
    // Resolusi HARUS diminta eksplisit. Tanpa ini Chrome memberi 640x480
    // walaupun kameranya 1080p -- itu yang bikin percobaan lama tercatat 480p.
    var t = (VIDEO_SETELAN.resolusi === '1080p')
      ? { w: 1920, h: 1080 }
      : { w: 1280, h: 720 };

    var video = {
      width:     { ideal: t.w },
      height:    { ideal: t.h },
      frameRate: { ideal: 15, max: 30 }
    };

    // 'ideal', bukan 'exact': kalau webcam yang diingat sudah dicabut, browser
    // jatuh ke kamera lain daripada gagal total dan membuat packing tak terekam.
    var device = bacaDeviceId();
    if (device) { video.deviceId = { ideal: device }; }

    return {
      audio: false, // audio belum diputuskan; mematikannya juga memangkas ukuran
      video: video
    };
  }

  function opsiRekam() {
    // VP8 DULU, baru VP9. VP9 memang menghasilkan berkas lebih kecil, tapi
    // encoding-nya jauh lebih berat dan ini berjalan di PC packer, bukan di
    // server. Di 1080p beban itu bisa membuat Chrome tersendat saat packing.
    var kandidat = ['video/webm;codecs=vp8', 'video/webm;codecs=vp9', 'video/webm'];
    var mime = '';

    for (var i = 0; i < kandidat.length; i++) {
      if (window.MediaRecorder && MediaRecorder.isTypeSupported(kandidat[i])) {
        mime = kandidat[i];
        break;
      }
    }

    var opsi = { videoBitsPerSecond: (VIDEO_SETELAN.resolusi === '1080p') ? 2500000 : 1500000 };
    if (mime) { opsi.mimeType = mime; }

    return opsi;
  }

  /**
   * Mengisi dropdown kamera. Label perangkat baru terbaca SETELAH izin
   * diberikan, jadi ini dipanggil sesudah stream jalan. Dropdown-nya sendiri
   * hanya ditampilkan kalau memang ada lebih dari satu kamera.
   */
  function isiDaftarKamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return; }

    navigator.mediaDevices.enumerateDevices().then(function (daftar) {
      var $sel = $('#pilih-kamera');
      if (!$sel.length) { return; }

      var kamera = [];
      for (var i = 0; i < daftar.length; i++) {
        if (daftar[i].kind === 'videoinput') { kamera.push(daftar[i]); }
      }

      $sel.empty();
      $.each(kamera, function (i, d) {
        $sel.append($('<option></option>').attr('value', d.deviceId).text(d.label || ('Kamera ' + (i + 1))));
      });

      var tersimpan = bacaDeviceId();
      if (tersimpan) {
        $sel.val(tersimpan);
        if ($sel.val() === null && kamera.length) { $sel.val(kamera[0].deviceId); }
      }

      $('#pilih-kamera-wrap').toggleClass('hidden', kamera.length < 2);
    }).catch(function () {});
  }

  /** Mematikan stream lalu membukanya lagi dengan kamera / setelan terbaru. */
  function gantiKamera() {
    var w = window.rekamPacking;

    buangRekam(null);

    if (w.stream) {
      w.stream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
      w.stream = null;
    }

    setStatusKamera('belum');
    nyalakanKamera();
  }

  function pasangPratinjau() {
    var video = document.getElementById('rekam-preview');
    var w = window.rekamPacking;

    if (video && w.stream && video.srcObject !== w.stream) {
      video.srcObject = w.stream;
      video.play().catch(function () {});
    }
  }

  function nyalakanKamera() {
    var w = window.rekamPacking;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
      setStatusKamera('tidak-didukung');
      statusRekam('Browser tidak mendukung perekaman di halaman ini. Buka lewat <code>http://localhost</code> atau HTTPS, bukan alamat IP polos.', 'danger');
      return;
    }

    if (w.stream) { pasangPratinjau(); return; }

    setStatusKamera('membuka');
    statusRekam('Menyiapkan kamera...', 'info');

    navigator.mediaDevices.getUserMedia(kendalaVideo()).then(function (stream) {
      w.stream = stream;
      setStatusKamera('siap');
      simpanPrefKamera('1');
      tampilTombolKamera(true);
      pasangPratinjau();

      var trek = stream.getVideoTracks()[0];
      var s = trek ? trek.getSettings() : {};
      statusRekam('Kamera siap (' + (s.width || '?') + 'x' + (s.height || '?') + '). Packing akan terekam otomatis saat resi di-scan.', 'success');

      isiDaftarKamera();
      pasangPenjaga();

      // Resi sudah di-scan duluan sebelum kamera siap -- langsung rekam.
      if (w.menunggu) {
        var n = w.menunggu;
        w.menunggu = '';
        mulaiRekam(n);
      }
    }).catch(function (err) {
      setStatusKamera('gagal');
      simpanPrefKamera('0');
      tampilTombolKamera(false);
      statusRekam('Kamera tidak bisa dibuka: ' + ((err && err.message) ? err.message : err) + '. Packing tidak akan terekam.', 'danger');
    });
  }

  function matikanKamera() {
    var w = window.rekamPacking;

    buangRekam('kamera dimatikan');

    if (w.stream) {
      w.stream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
      w.stream = null;
    }

    if (w.jaga) { clearInterval(w.jaga); w.jaga = null; }

    setStatusKamera('belum');
    simpanPrefKamera('0');
    tampilTombolKamera(false);
    statusRekam('Kamera mati. Nomor resi masih bisa di-scan, tapi packing-nya tidak akan terekam.', 'warning');
  }

  // ── siklus rekam ─────────────────────────────────────────────────────

  function mulaiRekam(noresi) {
    var w = window.rekamPacking;

    if (!w.stream) { w.menunggu = noresi; return; }
    if (w.recorder && w.noresi === noresi) { return; }

    buangRekam('ganti resi');

    try {
      w.recorder = new MediaRecorder(w.stream, opsiRekam());
    } catch (e) {
      statusRekam('Perekam tidak bisa dibuat: ' + e.message, 'danger');
      w.recorder = null;
      return;
    }

    w.potongan   = [];
    w.noresi     = noresi;
    w.mulai      = Date.now();
    w.finalisasi = false;
    w.terpotong  = false;

    w.recorder.ondataavailable = function (e) {
      if (e.data && e.data.size > 0) { w.potongan.push(e.data); }
    };

    w.recorder.onstop = function () {
      if (w.finalisasi) {
        w.finalisasi = false;
        kirimVideo();
      }
    };

    // Potongan 5 detik supaya Chrome bisa melimpahkan data ke disk dan tidak
    // menahan seluruh rekaman di memori sampai selesai.
    w.recorder.start(5000);

    $('#rekam-indikator').removeClass('hidden');
    jalankanTimer();
    statusRekam('Merekam packing resi <strong>' + noresi + '</strong>.', 'success');
  }

  function jalankanTimer() {
    var w = window.rekamPacking;

    if (w.timerUI) { clearInterval(w.timerUI); }

    w.timerUI = setInterval(function () {
      if (!w.recorder || !w.mulai) { return; }

      var detik = Math.floor((Date.now() - w.mulai) / 1000);
      $('#rekam-durasi').text(formatDurasi(detik));

      // Batas rekam: rekaman DIHENTIKAN tapi TIDAK dibuang. Paket kuantitas
      // banyak justru yang paling butuh bukti, jadi lebih baik punya rekaman
      // terpotong daripada tidak punya sama sekali.
      if (!w.terpotong && detik >= VIDEO_SETELAN.batas_menit * 60) {
        w.terpotong = true;
        if (w.recorder.state === 'recording') {
          w.finalisasi = false;
          w.recorder.stop();
        }
        statusRekam('Batas ' + VIDEO_SETELAN.batas_menit + ' menit tercapai. Rekaman dihentikan dan akan tetap disimpan saat Submit.', 'warning');
      }
    }, 1000);
  }

  function hentikanTimer() {
    var w = window.rekamPacking;
    if (w.timerUI) { clearInterval(w.timerUI); w.timerUI = null; }
    $('#rekam-indikator').addClass('hidden');
    $('#rekam-durasi').text('00:00');
  }

  /** Menghentikan rekaman TANPA mengunggah -- isinya benar-benar dibuang. */
  function buangRekam(alasan) {
    var w = window.rekamPacking;

    w.menunggu = '';

    if (!w.recorder) { w.potongan = []; w.noresi = ''; return; }

    w.finalisasi = false;

    try {
      if (w.recorder.state !== 'inactive') { w.recorder.stop(); }
    } catch (e) {}

    w.recorder  = null;
    w.potongan  = [];
    w.noresi    = '';
    w.mulai     = 0;
    w.terpotong = false;

    hentikanTimer();

    if (alasan && w.stream) {
      statusRekam('Rekaman dibuang (' + alasan + '). Kamera masih menyala.', 'warning');
    }
  }

  /** Menutup rekaman lalu mengunggahnya. Dipanggil setelah simpan berhasil. */
  function selesaikanRekam(noresi) {
    var w = window.rekamPacking;

    if (!w.recorder || w.noresi !== noresi) { return; }

    hentikanTimer();

    if (w.recorder.state === 'inactive') {
      // Sudah berhenti karena kena batas menit; potongannya masih utuh.
      kirimVideo();
      return;
    }

    w.finalisasi = true;
    w.recorder.stop(); // onstop -> kirimVideo()
  }

  function kirimVideo() {
    var w = window.rekamPacking;
    var noresi = w.noresi;
    var potongan = w.potongan;

    // State dibersihkan lebih dulu supaya resi berikutnya bisa langsung direkam
    // walaupun unggahan ini masih berjalan di latar.
    w.recorder  = null;
    w.potongan  = [];
    w.noresi    = '';
    w.mulai     = 0;
    var terpotong = w.terpotong;
    w.terpotong = false;

    if (!noresi || !potongan.length) { return; }

    var blob = new Blob(potongan, { type: 'video/webm' });
    var mb = (blob.size / 1048576).toFixed(1);

    var fd = new FormData();
    fd.append('noresi', noresi);
    fd.append('video', blob, noresi + '.webm');

    w.mengunggah = true;
    statusRekam('Mengunggah video resi <strong>' + noresi + '</strong> (' + mb + ' MB)...', 'info');

    $.ajax({
      url: 'packer/upload-video-packing',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
      timeout: 600000
    }).done(function (res) {
      w.mengunggah = false;

      // make_ajax_response() selalu balas HTTP 200 dan menaruh statusnya di
      // body, jadi keberhasilan diperiksa dari res.code, bukan status HTTP.
      if (res && res.code === 201) {
        statusRekam('Video resi <strong>' + noresi + '</strong> tersimpan (' + mb + ' MB)'
          + (terpotong ? ' <em>— terpotong di batas waktu</em>' : '') + '.', 'success');
      } else {
        statusRekam('Video resi ' + noresi + ' GAGAL disimpan: ' + ((res && res.message) ? res.message : 'respons tidak dikenal'), 'danger');
      }
    }).fail(function (xhr, textStatus) {
      w.mengunggah = false;
      statusRekam('Video resi ' + noresi + ' GAGAL diunggah (' + textStatus + '). Rekamannya hilang — laporkan ke IT.', 'danger');
    });
  }

  // ── penjaga & pengelola per render ───────────────────────────────────

  function pasangPenjaga() {
    var w = window.rekamPacking;

    if (w.jaga) { clearInterval(w.jaga); }
    w.jagaHilang = 0;

    w.jaga = setInterval(function () {
      var video = document.getElementById('rekam-preview');

      if (video && document.body.contains(video)) {
        w.jagaHilang = 0;
        return;
      }

      w.jagaHilang++;
      if (w.jagaHilang < BATAS_JAGA_HILANG) { return; }

      // Packer sudah pindah menu: rekaman yang belum disimpan dibuang, kamera
      // dilepas supaya lampunya tidak menyala terus.
      buangRekam(null);
      if (w.stream) {
        w.stream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
        w.stream = null;
      }
      clearInterval(w.jaga);
      w.jaga = null;
    }, 1000);
  }

  /**
   * Menentukan apa yang harus dilakukan rekaman pada render halaman ini.
   * Satu-satunya tempat keputusan itu diambil, supaya tidak ada dua jalur yang
   * saling menimpa saat auto-save double-scan terjadi.
   */
  function kelolaRekamHalaman() {
    var w = window.rekamPacking;

    pasangPratinjau();

    if (SUDAH_TERSIMPAN) {
      // Scan kedua sudah menyimpan resi di sisi server pada request ini.
      w.menunggu = '';
      selesaikanRekam(NORESI_HALAMAN);
      return;
    }

    if (NORESI_HALAMAN) {
      if (w.recorder && w.noresi === NORESI_HALAMAN) {
        jalankanTimer();
        $('#rekam-indikator').removeClass('hidden');
        return;
      }
      mulaiRekam(NORESI_HALAMAN);
      return;
    }

    // Halaman dibuka tanpa resi: rekaman yang masih menggantung tidak akan
    // pernah punya pasangan simpanan, jadi dibuang.
    w.menunggu = '';
    if (w.recorder) { buangRekam('resi dibatalkan'); }
  }

  function inisialisasiRekam() {
    var w = window.rekamPacking;

    // Tombol di-bind langsung ke elemennya (bukan lewat $(document)) karena
    // elemen ini selalu baru tiap render; delegasi ke document akan menumpuk.
    $('#btn-kamera-mulai').on('click', nyalakanKamera);
    $('#btn-kamera-stop').on('click', matikanKamera);
    $('#btn-simpan-setelan').on('click', simpanSetelanVideo);
    $('#btn-cari-video').on('click', cariVideoPacking);
    $('#cari-video-resi').on('keydown', function (e) {
      if (e.which === 13) { e.preventDefault(); cariVideoPacking(); }
    });

    $('#pilih-kamera').on('change', function () {
      simpanDeviceId($(this).val() || '');
      gantiKamera();
    });

    // Field tersembunyi ikut dibuat ulang tiap render, jadi nilainya disetel
    // ulang dari state yang bertahan di window.
    setStatusKamera(w.statusKamera);

    if (w.stream) { isiDaftarKamera(); }

    if (w.stream) {
      tampilTombolKamera(true);
      pasangPenjaga();
    } else {
      tampilTombolKamera(false);
    }

    if (!w.stream && bacaPrefKamera() !== '0') {
      nyalakanKamera();
    }

    kelolaRekamHalaman();
  }

  function simpanSetelanVideo() {
    var $btn = $('#btn-simpan-setelan').prop('disabled', true);

    $.ajax({
      url: 'packer/simpan-setelan-video',
      type: 'POST',
      dataType: 'json',
      data: {
        resolusi:     $('#setelan-resolusi').val(),
        batas_menit:  $('#setelan-batas').val(),
        wajib_kamera: $('#setelan-wajib').is(':checked') ? '1' : '0'
      }
    }).done(function (res) {
      $btn.prop('disabled', false);

      if (res && res.code === 200) {
        VIDEO_SETELAN.resolusi     = res.data.resolusi;
        VIDEO_SETELAN.batas_menit  = res.data.batas_menit;
        VIDEO_SETELAN.wajib_kamera = res.data.wajib_kamera;
        noty({ text: 'Setelan video disimpan. Matikan lalu nyalakan kamera agar resolusi baru dipakai.', layout: 'topRight', type: 'success', timeout: 4000 });
      } else {
        noty({ text: 'Gagal menyimpan setelan: ' + ((res && res.message) ? res.message : 'respons tidak dikenal'), layout: 'topRight', type: 'error', timeout: 4000 });
      }
    }).fail(function () {
      $btn.prop('disabled', false);
      noty({ text: 'Gagal menghubungi server saat menyimpan setelan.', layout: 'topRight', type: 'error', timeout: 4000 });
    });
  }

  // ── pemutar video ────────────────────────────────────────────────────
  //
  // Videonya disimpan di luar webroot, jadi <video src> menunjuk ke endpoint
  // PHP (packer/video-packing/<noresi>), bukan ke berkas statis. Endpoint itu
  // mendukung HTTP Range sehingga pemutar tetap bisa digeser ke menit tertentu
  // tanpa mengunduh ulang seluruh berkas.

  function cariVideoPacking() {
    var resi = $.trim($('#cari-video-resi').val() || '');
    var $hasil = $('#hasil-cari-video');

    if (!resi) {
      $hasil.empty();
      return;
    }

    $hasil.html($('<p class="help-block"></p>').text('Mencari video resi ' + resi + '...'));

    $.ajax({
      url: 'packer/cari-video-packing',
      type: 'POST',
      dataType: 'json',
      data: { noresi: resi }
    }).done(function (res) {
      if (!res || res.code !== 200 || !res.data) {
        $hasil.empty().append(
          $('<div class="alert alert-warning" style="margin-bottom:0;"></div>')
            .text((res && res.message) ? res.message : 'Video tidak ditemukan.')
        );
        return;
      }

      var d = res.data;
      var mb = (d.ukuran_byte / 1048576).toFixed(1);

      // Elemen dibangun lewat DOM dan .text(), bukan rangkaian string HTML,
      // supaya nilai dari server tidak pernah tertafsir sebagai markup.
      var $video = $('<video controls preload="metadata"></video>')
        .attr('src', d.url)
        .css({ width: '100%', maxHeight: '320px', background: '#000', borderRadius: '4px' });

      var $info = $('<p class="help-block" style="margin-top:6px;"></p>')
        .text(d.nama_berkas + ' — ' + mb + ' MB — direkam ' + (d.diubah_pada || '-'));

      var $unduh = $('<a class="btn btn-default btn-sm"></a>')
        .attr({ href: d.url, download: d.nama_berkas })
        .html('<i class="fa fa-download"></i> Unduh');

      $hasil.empty().append($video).append($info).append($unduh);

      if (!d.tercatat) {
        $hasil.append(
          $('<div class="alert alert-warning" style="margin-top:8px;margin-bottom:0;"></div>')
            .text('Berkasnya ada, tapi tblpacking.video_path untuk resi ini kosong — pencatatan ke database gagal saat unggah.')
        );
      } else if (d.format_lama) {
        $hasil.append(
          $('<div class="alert alert-info" style="margin-top:8px;margin-bottom:0;"></div>')
            .text('Database masih menyimpan catatan format lama: ' + d.tercatat)
        );
      }
    }).fail(function () {
      $hasil.empty().append(
        $('<div class="alert alert-danger" style="margin-bottom:0;"></div>')
          .text('Gagal menghubungi server saat mencari video.')
      );
    });
  }

  // ====================== rekam video packing ======================

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
