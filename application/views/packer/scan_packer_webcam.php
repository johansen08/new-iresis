<?php
// Halaman ini SENGAJA salinan utuh packer/scan_packer.php, bukan turunannya.
// Scan Resi Packer versi biasa tetap dipakai sebagai cadangan kalau kamera
// bermasalah, jadi kedua halaman harus bisa berubah sendiri-sendiri.
// Duplikasi di sini disengaja -- jangan disatukan jadi satu view.
//
// Bedanya dengan versi biasa: setiap packing direkam videonya. Perekamnya ada
// di assets/js/packer_video.js (dimuat global lewat main.php karena panel
// kameranya harus selamat dari pergantian isi halaman SPA); view ini cuma
// memberi tahu resi mana yang sedang direkam lewat PackerVideo.sinkron().
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
<div class="row" id="scan-packer-webcam-root">
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

      <?php if (!empty($scan_aktif)) : ?>
        <!-- Bilah ini muncul selama ada resi yang menunggu scan kedua, termasuk
             saat halaman dibuka lewat GET, supaya packer selalu tahu resi mana
             yang sedang dipegang dan punya jalan keluar kalau barangnya
             bermasalah. -->
        <div class="panel-body" style="padding-bottom: 0;">
          <div class="alert alert-warning" id="bar-scan-aktif" style="margin-bottom: 0;">
            <button type="button" class="btn btn-danger btn-sm pull-right" id="btn-batal-scan">
              <i class="fa fa-times"></i> <strong>Batal Scan</strong>
            </button>
            <i class="fa fa-clock-o"></i>
            Menunggu scan kedua untuk resi
            <strong id="teks-scan-aktif"><?= htmlspecialchars($scan_aktif, ENT_QUOTES, 'UTF-8') ?></strong>
            <!-- Penanda rekam ikut di bilah ini, bukan cuma di panel kamera:
                 panelnya duduk di pojok kanan bawah, bisa dilipat, dan tertutup
                 setiap popup. Bilah ini selalu terlihat selama resi dipegang. -->
            <span id="tanda-rekam" style="display: none;">
              <span class="titik-rekam"></span>
              <strong>MEREKAM</strong>
              <span id="durasi-rekam">00:00</span>
            </span>
            <!-- Hitung mundur jeda packing tinggal di sini, bukan di dalam popup.
                 Popupnya menutupi tabel SKU, dan justru daftar barang itulah yang
                 perlu dibaca packer selama menunggu. -->
            <span id="tunggu-packing" style="display: none;">
              <i class="fa fa-hand-paper-o"></i>
              Packing dulu &mdash; tunggu <strong id="tunggu-packing-detik">0</strong> detik
            </span>
            <div style="clear: both;"></div>
          </div>
        </div>
      <?php endif; ?>

      <!-- search input by noresi -->
      <div class="panel-body">
        <form action="packer/scan-packer-webcam" method="post" class="form-horizontal" id="form-scan-packer" autocomplete="off">
          <!-- Kesiapan kamera ikut dikirim tiap scan; server yang memutuskan
               boleh tidaknya resi baru dibuka. Nilai awalnya sengaja yang paling
               ketat, jadi kalau JS-nya gagal jalan sekalipun scan tetap ditolak
               alih-alih diam-diam diterima tanpa rekaman. -->
          <input type="hidden" name="kamera_status" id="kamera-status" value="tidak-didukung" />
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
      </div>

      <!-- table untuk data resi -->
      <?php if (!empty($noresi)) : ?>
          <div class="col-md-12" id="result-info">
              <div id="button-footer">
                  <div class="text-left" style="margin-top: 10px;">
                      <!-- Tombol Reset dihapus: satu-satunya jalan keluar dari
                           resi yang sedang dipegang sekarang Batal Scan, yang
                           membuang rekamannya sekalian. Reset dulu menutup
                           rekaman tanpa menyimpan resinya, jadi satu resi bisa
                           berakhir punya lebih dari satu video. -->
                      <button id="submit-selected" class="btn btn-success mb-2" style="margin-bottom: 10px;">
                          <strong>Submit</strong>
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
                             value="<?= $noresi ?>"
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
                      <?php foreach ($list_type_masalah as $masalah) : ?>
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
                    <input type="text" value="<?= $noresi ?>" name="noresi" id="noresi" class="form-control" />
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

      <!-- Kabar dari perekam: rekaman terputus, durasi hampir habis, atau batas
           durasi tercapai. Semuanya ditahan sampai ditutup packer -- ini justru
           kejadian yang selama ini paling tidak terlihat, karena cuma muncul
           sebagai teks kecil di panel kamera yang mungkin sedang dilipat. -->
      <div id="videoModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box">
          <div class="panel panel-default">
            <div class="panel-heading" id="videoModalHeader" style="background-color: #d9534f; color: white;">
              <h3 class="panel-title">
                <i class="fa" id="videoModalIcon"></i> <strong id="videoModalTitle">Rekaman</strong>
              </h3>
            </div>
            <div class="panel-body">
              <p id="videoModalMessage" style="font-size: 17px; margin: 15px 0;"></p>
              <button type="button" class="btn btn-default" id="videoModalTutup">Mengerti</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Konfirmasi Batal Scan. Tombolnya duduk tepat di atas kolom input yang
           dipakai sepanjang hari, dan akibatnya tidak bisa dikembalikan:
           rekamannya dihapus. -->
      <div id="konfirmasiBatalModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #d9534f; color: white;">
              <h3 class="panel-title">
                <i class="fa fa-exclamation-triangle"></i> <strong>Batalkan Scan?</strong>
              </h3>
            </div>
            <div class="panel-body">
              <p style="font-size: 17px; margin: 15px 0;">
                Scan pertama resi <strong id="konfirmasiBatalResi"></strong> akan dibatalkan
                dan <strong>rekaman videonya dibuang</strong>. Tidak bisa dikembalikan.
              </p>
              <p style="font-size: 14px; color: #8a6d3b; margin-bottom: 20px;">
                Pakai ini kalau barangnya kurang atau salah dan penyelesaiannya lama.
                Kalau barangnya sudah beres, mulai lagi dari scan pertama.
              </p>
              <button type="button" class="btn btn-danger" id="konfirmasiBatalYa"><strong>Ya, Batalkan</strong></button>
              <button type="button" class="btn btn-default" id="konfirmasiBatalTidak">Tidak Jadi</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Popup penolakan scan. Dipakai bersama oleh semua scan yang ditolak:
           terlalu cepat, resi berbeda, resi yang sudah selesai, resi yang tidak
           layak dipacking, kamera belum siap, dan status sesi yang tidak aktif. -->
      <div id="tolakScanModal" class="custom-popup-overlay" style="display: none;">
        <div class="custom-popup-box">
          <div class="panel panel-default">
            <div class="panel-heading" id="tolakScanHeader" style="background-color: #f0ad4e; color: white;">
              <h3 class="panel-title">
                <i class="fa" id="tolakScanIcon"></i> <strong id="tolakScanTitle">Perhatian</strong>
              </h3>
            </div>
            <div class="panel-body">
              <p id="tolakScanMessage" style="font-size: 18px; margin: 15px 0;"></p>
              <p id="tolakScanCountdown" style="font-size: 15px; font-weight: bold; color: #8a6d3b;">&nbsp;</p>
              <button type="button" class="btn btn-default" id="tolakScanTutup">Tutup</button>
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

  // Resi yang layak direkam, sudah divalidasi di controller: hanya resi yang
  // detailnya ketemu dan yang memang sedang menunggu scan kedua. Nilainya bisa
  // terisi walau halaman dibuka lewat GET -- itulah yang membuat rekaman lanjut
  // sendiri setelah tab packer sempat mati.
  var videoNoresi = <?= json_encode(isset($video_noresi) ? $video_noresi : '') ?>;
  // Resi yang siklus scan pertamanya baru saja ditutup server karena sudah
  // terlalu lama menggantung. Diberitahukan juga saat halaman dibuka lewat GET,
  // supaya packer tahu resi itu harus dimulai lagi dari scan pertama -- bukan
  // ditutup dengan scan kedua atas rekaman yang sudah lama mati.
  var resiKedaluwarsa = <?= json_encode(isset($resi_kedaluwarsa) ? $resi_kedaluwarsa : '') ?>;

  var videoUploadUrl = <?= json_encode(base_url('packer/upload-video-packing')) ?>;
  var videoBatalUrl  = <?= json_encode(base_url('packer/batalkan-video-packing')) ?>;
  var batalScanUrl   = <?= json_encode(base_url('packer/batal-scan')) ?>;
  var tutupBatasUrl  = <?= json_encode(base_url('packer/tutup-batas-rekam')) ?>;

  $().ready(function() {

    // Timer jeda packing disimpan di window: isi halaman diganti total tiap
    // scan, jadi timer dari render sebelumnya harus dimatikan -- kalau tidak, ia
    // akan mengunci kolom resi milik scan berikutnya.
    hentikanJedaPacking();

    if (window.timerTutupPopupTolak) {
      clearTimeout(window.timerTutupPopupTolak);
      window.timerTutupPopupTolak = null;
    }

    // Penanda MEREKAM di bilah kuning, alasannya sama: dimatikan dulu supaya
    // interval dari render sebelumnya tidak menumpuk.
    if (window.timerTandaRekam) {
      clearInterval(window.timerTandaRekam);
      window.timerTandaRekam = null;
    }
    perbaruiTandaRekam();
    window.timerTandaRekam = setInterval(perbaruiTandaRekam, 1000);

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
        dataType: 'json',
        data: {
          noresi: noresi
        },
        success: function(response) {
          // make_ajax_response SELALU membalas HTTP 200 dan menaruh status
          // sebenarnya di body, supaya jawaban JSON-nya tidak dirusak halaman
          // error bawaan CodeIgniter. Jadi gagal-tidaknya harus dibaca dari
          // response.code; cabang error jQuery di bawah cuma kena kalau
          // jaringannya yang putus.
          var kode = response && response.code ? response.code : 0;
          var data = response && response.data ? response.data : null;
          var ditolakJeda = !!(data && data.status === 'scan_terlalu_cepat');

          // Di luar penolakan jeda, server sudah menutup siklusnya -- berhasil
          // maupun gagal. Bilahnya ikut dilepas supaya packer tidak melihat resi
          // yang sebenarnya sudah tidak ditunggu lagi masih tercatat "menunggu
          // scan kedua".
          if (!ditolakJeda) {
            $('#bar-scan-aktif').slideUp();
          }

          if (kode >= 400) {
            tampilkanGagalSimpan(response);
            return;
          }

          if (window.PackerVideo) window.PackerVideo.selesai();

          $('#successModal').fadeIn();

          setTimeout(function() {
            $('#successModal').fadeOut();
          }, 1000);

            resetScanView();
        },
        error: function(xhr, status, error) {
          tampilkanGagalSimpan(null);
        }
      });
    });

    $('#tolakScanTutup').on('click', tutupPopupTolak);

    // Kesiapan kamera dilaporkan pada saat scan, bukan pada saat halaman dimuat:
    // kamera bisa saja baru selesai dibuka -- atau justru dicabut -- di antara
    // keduanya. Handler ini menempel langsung di form, jadi jalan lebih dulu
    // daripada handler SPA yang menunggu di body.
    $('#form-scan-packer').on('submit', function (e) {
      // Enter di kolom kosong dulu tetap dikirim: halaman dimuat ulang, layar
      // berkedip, dan tidak ada pesan apa pun. Diabaikan saja di sini.
      if ($('#noresi').val().trim() === '') {
        e.preventDefault();
        e.stopPropagation();
        $('#noresi').val('').focus();
        return false;
      }

      // Dicek sebagai fungsi, bukan cuma keberadaan PackerVideo-nya. Kalau
      // browser masih memegang packer_video.js versi lama, memanggil metode yang
      // belum ada akan melempar error di tengah handler ini -- dan scan tetap
      // terkirim dengan nilai bawaan field, tanpa jejak apa pun selain penolakan
      // yang membingungkan.
      var siapKamera = (window.PackerVideo && typeof window.PackerVideo.statusKamera === 'function')
        ? window.PackerVideo.statusKamera()
        : 'tidak-didukung';

      $('#kamera-status').val(siapKamera);
    });

    $('#videoModalTutup').on('click', function () {
      $('#videoModal').fadeOut();
      $('#noresi').focus();
    });

    // Batal Scan: dipakai kalau barang kurang/salah dan penyelesaiannya lama.
    // Karena akibatnya tidak bisa dikembalikan -- rekamannya dihapus -- tombolnya
    // cuma membuka konfirmasi, bukan langsung menjalankan.
    $('#btn-batal-scan').on('click', function () {
      $('#konfirmasiBatalResi').text($('#teks-scan-aktif').text());
      $('#konfirmasiBatalModal').fadeIn();
    });

    $('#konfirmasiBatalTidak').on('click', function () {
      $('#konfirmasiBatalModal').fadeOut();
      $('#noresi').focus();
    });

    // Status scan pertama dikosongkan di server DAN rekamannya dibuang, jadi
    // resi itu benar-benar kembali seperti belum pernah discan.
    $('#konfirmasiBatalYa').on('click', function () {
      var $btn = $('#btn-batal-scan');
      $btn.prop('disabled', true);
      $('#konfirmasiBatalModal').fadeOut();

      if (window.PackerVideo) {
        window.PackerVideo.batalkan(videoBatalUrl);
      }

      $.ajax({
        url: batalScanUrl,
        method: 'POST',
        dataType: 'json',
        success: function (res) {
          noty({
            text: res && res.message ? res.message : 'Scan dibatalkan.',
            layout: 'topRight',
            type: 'information',
            timeout: 2500
          });

          // Pembatalan sebelumnya tidak berbunyi sama sekali, padahal akibatnya
          // paling berat di menu ini: rekamannya dibuang.
          playAudio('audio-cancel');

          // Resinya sudah dilepas, jadi jeda packing-nya ikut batal. Tanpa ini
          // kolom resi tetap terkunci sampai hitungannya habis padahal tidak ada
          // lagi yang ditunggu -- dan bilah kuningnya sendiri sudah hilang.
          hentikanJedaPacking();
          $('#noresi').prop('disabled', false);

          $('#bar-scan-aktif').slideUp();
          resetScanView();
        },
        error: function () {
          $btn.prop('disabled', false);
          noty({
            text: 'Gagal membatalkan scan. Coba lagi.',
            layout: 'topRight',
            type: 'error',
            timeout: 2500
          });
        }
      });
    });

    if (resiKedaluwarsa) {
      noty({
        text: 'Scan pertama resi ' + resiKedaluwarsa + ' sudah kedaluwarsa dan ditutup otomatis. '
            + 'Kalau resi itu masih perlu dipacking, mulai lagi dari scan pertama.',
        layout: 'topRight',
        type: 'warning',
        timeout: 6000
      });

      playAudio('audio-cancel');
    }

    triggerScanFeedback(scanFeedback);

    // Rekaman menumpang aturan double-scan yang sudah ada: scan pertama membuka
    // rekaman, scan kedua (yang sekaligus menyimpan resi) menutup lalu
    // mengunggahnya.
    if (window.PackerVideo) {
      window.PackerVideo.sinkron({
        noresi: videoNoresi,
        status: scanFeedback ? scanFeedback.status : null,
        uploadUrl: videoUploadUrl,
        // Diisi ulang tiap render, jadi selalu menunjuk ke DOM halaman yang
        // sekarang -- isi halaman diganti total setiap scan.
        pemberitahu: tanganiKabarVideo
      });
    }
  })

  function triggerScanFeedback(feedback) {
    if (!feedback || !feedback.message) {
      return;
    }

    // Scan kedua yang terlalu cepat ditampilkan sebagai popup, bukan noty:
    // packer perlu benar-benar berhenti dan packing dulu, bukan sekadar melihat
    // notifikasi kecil yang lewat di pojok.
    if (STATUS_DITOLAK[feedback.status]) {
      tampilkanPopupTolak(feedback);
      playFeedbackAudio(feedback.status, feedback.exception_code || feedback.kode_alasan);
      return;
    }

    var notyType = mapFeedbackType(feedback.type);
    noty({
      text: feedback.message,
      layout: 'topRight',
      type: notyType,
      timeout: feedback.status === 'auto_save_success' ? 1500 : 2500
    });

    playFeedbackAudio(feedback.status, feedback.exception_code || feedback.kode_alasan);

    if (feedback.status === 'auto_save_success') {
      resetScanView();
    }
  }

  // Semua penolakan scan tampil sebagai popup, bukan noty: packer perlu benar-
  // benar berhenti dan membaca, bukan sekadar melihat notifikasi kecil lewat.
  var STATUS_DITOLAK = {
    scan_terlalu_cepat: { judul: 'Packing Dulu!',         ikon: 'fa-hand-paper-o', warna: '#f0ad4e' },
    scan_beda_resi:     { judul: 'Resi Belum Selesai',    ikon: 'fa-exchange',     warna: '#f0ad4e' },
    resi_sudah_selesai: { judul: 'Resi Sudah Di-packing', ikon: 'fa-ban',          warna: '#d9534f' },
    resi_tidak_dikenal: { judul: 'Resi Tidak Ditemukan',  ikon: 'fa-question',     warna: '#d9534f' },
    resi_tidak_layak:   { judul: 'Jangan Dipacking',      ikon: 'fa-ban',          warna: '#d9534f' },
    kamera_belum_siap:  { judul: 'Kamera Belum Siap',     ikon: 'fa-video-camera', warna: '#d9534f' },
    sesi_tidak_aktif:   { judul: 'Status Belum Aktif',    ikon: 'fa-user-times',   warna: '#d9534f' }
  };

  // Berapa lama popup penolakan menahan layar sebelum menyingkir sendiri.
  // Cukup untuk dibaca, tidak sampai menghalangi packing -- sisa waktunya
  // diteruskan bilah kuning yang tidak menutupi apa pun.
  var DURASI_POPUP_TOLAK_MS = 3000;

  function tutupPopupTolak() {
    if (window.timerTutupPopupTolak) {
      clearTimeout(window.timerTutupPopupTolak);
      window.timerTutupPopupTolak = null;
    }

    $('#tolakScanModal').fadeOut();

    // Kolom resi TIDAK dibuka di sini kalau jeda packing-nya masih berjalan:
    // penguncinya milik hitung mundur, bukan milik popup. Menutup popup lebih
    // awal boleh, menyingkat jedanya tidak.
    if (!window.timerScanTerlaluCepat) {
      $('#noresi').prop('disabled', false).val('').focus();
    }
  }

  /**
   * Jeda packing: kolom resi dikunci dan sisa waktunya tampil di bilah kuning.
   * Berjalan terus walau popupnya sudah menyingkir.
   */
  function mulaiJedaPacking(sisa) {
    hentikanJedaPacking();

    $('#noresi').prop('disabled', true).blur();

    var tick = function () {
      // Packer sudah pindah halaman -- tidak ada lagi yang perlu dihitung.
      if (!$('#noresi').length) {
        hentikanJedaPacking();
        return;
      }

      if (sisa <= 0) {
        hentikanJedaPacking();
        $('#noresi').prop('disabled', false).val('').focus();
        return;
      }

      $('#tunggu-packing-detik').text(sisa);
      $('#tunggu-packing').show();
      sisa--;
    };

    tick();
    window.timerScanTerlaluCepat = setInterval(tick, 1000);
  }

  function hentikanJedaPacking() {
    if (window.timerScanTerlaluCepat) {
      clearInterval(window.timerScanTerlaluCepat);
      window.timerScanTerlaluCepat = null;
    }
    $('#tunggu-packing').hide();
  }

  /**
   * Untuk penolakan karena terlalu cepat, popup menahan layar sampai jeda
   * minimum terlewati lalu menutup sendiri. Penolakan lain menunggu ditutup
   * packer, karena tidak ada hitungan waktu yang relevan.
   *
   * Rekaman video sengaja tidak disentuh di sini: kamera terus merekam sesi yang
   * sama, jadi begitu packer selesai packing dan scan resi yang benar, videonya
   * utuh dari scan pertama sampai scan kedua yang sah.
   */
  function tampilkanPopupTolak(feedback) {
    var gaya = STATUS_DITOLAK[feedback.status] || { judul: 'Perhatian', ikon: 'fa-warning', warna: '#f0ad4e' };

    $('#tolakScanTitle').text(gaya.judul);
    $('#tolakScanIcon').attr('class', 'fa ' + gaya.ikon);
    $('#tolakScanHeader').css('background-color', gaya.warna);
    $('#tolakScanMessage').text(feedback.message);
    $('#tolakScanModal').fadeIn();

    hentikanJedaPacking();

    var sisa = parseInt(feedback.sisa_detik, 10);
    if (!(sisa > 0)) {
      $('#tolakScanCountdown').html('&nbsp;');
      return;
    }

    // Overlay tidak mencuri fokus, jadi scan berikutnya tetap masuk ke kolom
    // resi dan mengulang popup yang sama. Kolomnya dikunci selama jeda packing,
    // dan tetap terkunci walau popupnya sudah menyingkir.
    $('#tolakScanCountdown').text('Kolom resi terkunci ' + sisa +
      ' detik. Sisa waktunya lanjut di bilah kuning.');

    mulaiJedaPacking(sisa);

    window.timerTutupPopupTolak = setTimeout(function () {
      $('#tolakScanModal').fadeOut();
      window.timerTutupPopupTolak = null;
    }, DURASI_POPUP_TOLAK_MS);
  }

  /**
   * Kegagalan tombol Submit. Penolakan yang aturannya sama dengan jalur scan
   * kedua -- sejauh ini cuma jeda minimum -- memakai popup yang sama persis,
   * lengkap dengan hitung mundurnya, supaya satu aturan tidak tampil dalam dua
   * rupa yang berbeda. Sisanya jatuh ke modal error biasa.
   */
  function tampilkanGagalSimpan(response) {
    var data = response && response.data ? response.data : null;

    if (data && data.status && STATUS_DITOLAK[data.status]) {
      tampilkanPopupTolak({
        status: data.status,
        message: response.message,
        sisa_detik: data.sisa_detik
      });
      playFeedbackAudio(data.status);
      return;
    }

    $('#errorMessage').text(
      (response && response.message) || 'Terjadi kesalahan saat memproses data!'
    );
    $('#errorModal').fadeIn();

    setTimeout(function() {
      $('#errorModal').fadeOut();
    }, 1500);

    playAudio('audio-fail');
  }

  /**
   * Penanda "MEREKAM 03:12" di bilah kuning, disegarkan tiap detik.
   *
   * Sengaja membaca keadaan perekam apa adanya, bukan menebak dari status scan:
   * kalau kameranya ternyata mati atau rekamannya sudah ditutup batas durasi,
   * penandanya ikut hilang -- persis itu yang perlu diketahui packer.
   */
  function perbaruiTandaRekam() {
    if (!document.getElementById('scan-packer-webcam-root')) {
      clearInterval(window.timerTandaRekam);
      window.timerTandaRekam = null;
      return;
    }

    var $tanda = $('#tanda-rekam');
    if (!$tanda.length) {
      return;
    }

    if (!window.PackerVideo || !window.PackerVideo.aktif()) {
      $tanda.hide();
      return;
    }

    var detik = window.PackerVideo.durasi();
    $('#durasi-rekam').text(formatDurasiDetik(detik === null ? 0 : detik));
    $tanda.show();
  }

  function formatDurasiDetik(detik) {
    var m = Math.floor(detik / 60);
    var s = detik % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
  }

  /**
   * Kabar dari perekam video. Tiga di antaranya menahan layar dengan popup,
   * karena packer harus benar-benar tahu videonya tidak utuh; satu lagi -- video
   * selesai diunggah -- cukup noty, supaya tidak memperlambat lini packing.
   */
  function tanganiKabarVideo(info) {
    if (!info || !info.jenis) {
      return;
    }

    if (info.jenis === 'tersimpan') {
      // Baru di sini videonya benar-benar aman di server. Scan kedua sudah
      // dijawab "tersimpan" beberapa detik sebelumnya, saat potongan penutupnya
      // masih di jalan -- kalau unggahannya gagal, kabarnya datang lewat
      // 'rusak' di bawah, bukan lewat diam.
      noty({
        text: 'Video resi ' + info.noresi + ' tersimpan (' + formatDurasiDetik(info.durasi) + ').',
        layout: 'topRight',
        type: 'success',
        timeout: 2500
      });
      return;
    }

    // Tidak ada lagi peringatan "hampir habis": batas durasinya sekarang jauh di
    // atas packing terpanjang yang wajar, jadi popup itu hanya akan mengganggu.
    if (info.jenis === 'batas-habis') {
      tutupKarenaBatasRekam(info);
      return;
    }

    if (info.jenis === 'rusak') {
      tampilkanPopupVideo({
        judul: 'Video Terpotong',
        ikon:  'fa-chain-broken',
        warna: '#d9534f',
        pesan: 'Rekaman resi ' + info.noresi + ' terputus di potongan ke-' + info.seq +
               ' (' + info.alasan + '). Video tersimpan sebagian saja. ' +
               'Laporkan ke IT: kemungkinan jaringan atau server bermasalah.'
      });
      playAudio('audio-fail');
    }
  }

  /**
   * Rekaman mentok batas durasi: resinya ikut ditutup, tidak dibiarkan
   * menggantung.
   *
   * Kalau siklusnya dibiarkan terbuka, bilah kuning terus menunggu scan kedua
   * padahal kamera sudah mati, dan scan berikutnya justru membuka siklus baru
   * berikut rekaman bagian kedua. Menutupnya di sini membuat resi itu
   * berperilaku seperti resi selesai lainnya: scan sesudahnya ditolak dengan
   * popup "Resi Sudah Di-packing".
   */
  function tutupKarenaBatasRekam(info) {
    playAudio('audio-error');

    var lama = formatDurasiDetik(info.batas);

    var tutupTampilan = function () {
      $('#bar-scan-aktif').slideUp();
      hentikanJedaPacking();
      $('#noresi').prop('disabled', false);
      resetScanView();
    };

    $.ajax({
      url: tutupBatasUrl,
      method: 'POST',
      dataType: 'json',
      data: { noresi: info.noresi },
      success: function (res) {
        var berhasil = res && res.code && res.code < 400;

        tampilkanPopupVideo({
          judul: berhasil ? 'Rekaman Berhenti, Resi Ditutup' : 'Rekaman Berhenti',
          ikon:  'fa-stop-circle',
          warna: '#d9534f',
          // Pesan popup masuk lewat .text(), jadi HARUS teks biasa. Entitas HTML
          // seperti &mdash; akan tampil apa adanya di layar packer.
          pesan: berhasil
            ? 'Rekaman resi ' + info.noresi + ' mencapai batas ' + lama + ' dan dihentikan. ' +
              'Resi ini otomatis ditutup sebagai selesai di-packing, videonya tersimpan. ' +
              'Kalau packing-nya sebenarnya belum selesai, segera laporkan ke atasan.'
            : 'Rekaman resi ' + info.noresi + ' mencapai batas ' + lama + ' dan dihentikan, ' +
              'tapi resinya gagal ditutup: ' + ((res && res.message) || 'sebab tidak diketahui') +
              '. Laporkan ke atasan.'
        });

        tutupTampilan();
      },
      error: function () {
        tampilkanPopupVideo({
          judul: 'Rekaman Berhenti',
          ikon:  'fa-stop-circle',
          warna: '#d9534f',
          pesan: 'Rekaman resi ' + info.noresi + ' mencapai batas ' + lama + ' dan dihentikan, ' +
                 'tapi server tidak bisa dihubungi untuk menutup resinya. Periksa koneksi, ' +
                 'lalu laporkan ke atasan.'
        });
      }
    });
  }

  function tampilkanPopupVideo(gaya) {
    $('#videoModalTitle').text(gaya.judul);
    $('#videoModalIcon').attr('class', 'fa ' + gaya.ikon);
    $('#videoModalHeader').css('background-color', gaya.warna);
    $('#videoModalMessage').text(gaya.pesan);
    $('#videoModal').fadeIn();
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

  // Scan pertama yang berhasil TIDAK boleh berbunyi seperti kesalahan. Kalau
  // suara error terdengar di tiap awal packing, packer berhenti menganggapnya
  // tanda bahaya -- dan penolakan yang sungguhan (resi sudah di-packing, resi
  // tidak dikenal) ikut lewat begitu saja. audio-alexis adalah nada "scan
  // diterima" yang sudah dipakai menu picker/receipt/retur, dan berkasnya beda
  // dari audio-alert yang menandai simpanan akhir di menu ini.
  function playFeedbackAudio(status, kodeAlasan) {
    var audioId = 'audio-error';
    if (status === 'need_second_scan') {
      audioId = 'audio-alexis';
    } else if (status === 'auto_save_success') {
      audioId = 'audio-alert';
    } else if (status === 'auto_save_failed') {
      // Dua penyebab paling sering -- resi sudah di-packing dan pesanan sudah
      // dibatalkan -- punya suara ucapan sendiri, karena tindakan operatornya
      // berbeda. exception_code-nya dikirim handle_double_scan_state_webcam().
      if (kodeAlasan === 'ALREADY_PACKED') {
        audioId = 'audio-sudah-packing';
      } else if (kodeAlasan === 'ORDER_CANCELED' || kodeAlasan === 'ORDER_COMPLETED') {
        audioId = 'audio-cancel-order';
      } else {
        audioId = 'audio-fail';
      }
    } else if (status === 'scan_terlalu_cepat') {
      audioId = 'audio-double';
    } else if (status === 'scan_beda_resi') {
      audioId = 'audio-wrong';
    } else if (status === 'resi_tidak_layak') {
      // Nada "cancel" dipisahkan dari nada error biasa: resi batal, resi sudah
      // selesai, dan resi belum di-picker bukan salah scan -- barangnya harus
      // ditahan dan dilaporkan ke CS, bukan discan ulang. Pesanan batal/selesai
      // memakai suara ucapan yang sama dengan jalur auto-save.
      if (kodeAlasan === 'ORDER_CANCELED' || kodeAlasan === 'ORDER_COMPLETED') {
        audioId = 'audio-cancel-order';
      } else {
        audioId = 'audio-cancelcoi';
      }
    } else if (status === 'resi_sudah_selesai') {
      audioId = 'audio-sudah-packing';
    } else if (status === 'resi_tidak_dikenal'
               || status === 'kamera_belum_siap' || status === 'sesi_tidak_aktif') {
      audioId = 'audio-error';
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

  /* Penanda MEREKAM di bilah kuning. Kedipannya sengaja pelan (1,2 detik) --
     cukup menarik mata tanpa jadi gangguan sepanjang packing. */
  #tanda-rekam {
    margin-left: 14px;
    padding: 2px 10px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #d9534f;
    color: #d9534f;
    font-size: 13px;
    white-space: nowrap;
  }

  #tanda-rekam .titik-rekam {
    display: inline-block;
    width: 9px;
    height: 9px;
    margin-right: 4px;
    border-radius: 50%;
    background: #d9534f;
    vertical-align: middle;
    animation: kedip-rekam 1.2s ease-in-out infinite;
  }

  #durasi-rekam {
    font-variant-numeric: tabular-nums;
  }

  /* Chip padat, bukan garis tipis: ini satu-satunya penanda yang tersisa begitu
     popupnya menyingkir, jadi harus terbaca dari jarak kerja packer. */
  #tunggu-packing {
    margin-left: 14px;
    padding: 3px 12px;
    border-radius: 12px;
    background: #f0ad4e;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
  }

  #tunggu-packing-detik {
    font-variant-numeric: tabular-nums;
    display: inline-block;
    min-width: 1.4em;
    text-align: right;
  }

  /* Kolom resi yang sedang terkunci harus terlihat terkunci, bukan seperti
     halaman yang macet. */
  #noresi:disabled {
    background-color: #f5f5f5;
    color: #999;
    cursor: not-allowed;
  }

  @keyframes kedip-rekam {
    0%, 100% { opacity: 1; }
    50%      { opacity: .15; }
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
