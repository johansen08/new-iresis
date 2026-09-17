<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <!-- title form -->
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Scan Resi Packer</strong></h3>
        <div class="pull-right" id="packer-session-controls">
            <button class="btn btn-success btn-session <?= $session_status['masuk'] ? 'hidden' : '' ?>" data-action="masuk" id="btn-session-masuk">Check-in</button>
            <button class="btn btn-warning btn-session <?= (!$session_status['masuk'] || $session_status['istirahat'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="istirahat_mulai" id="btn-session-istirahat">Istirahat</button>
            <button class="btn btn-info btn-session <?= !$session_status['istirahat'] ? 'hidden' : '' ?>" data-action="istirahat_selesai" id="btn-session-selesai">Selesai Istirahat</button>
            <button class="btn btn-danger btn-session <?= (!$session_status['masuk'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="pulang" id="btn-session-pulang">Check-out</button>
        </div>
      </div>

      <!-- search input by noresi -->
      <div class="panel-body">
        <!--
          class="nojs" WAJIB ada. Tanpa itu, handler global di plugins.js
          membajak submit form dan mengganti seluruh .page-content-wrap -- input
          nomor resi ikut terhapus dari DOM selagi request berjalan, dan scan
          yang diketik scanner saat itu hilang tanpa jejak. Scan di halaman ini
          ditangani sendiri oleh kirimScan() di bawah.
        -->
        <form action="packer/scan-packer" method="post" class="form-horizontal nojs" id="form-scan-packer" autocomplete="off">
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

      <!--
        Blok hasil selalu ikut dirender, cukup disembunyikan lewat CSS, lalu
        diisi JS dari respons detail_resi(). Dulu blok ini dibungkus
        "if (!empty($noresi))" karena halaman memang dirender ulang tiap scan;
        sekarang halaman hanya dirender sekali, jadi elemennya harus sudah ada.
      -->
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
                      <div><strong>Total Scan: <span id="info-total-scan"><?= $total_scan ?></span></strong></div>
                  </div>
                  <div class="col-md-4 text-center" style="border-right: 1px solid #ccc;">
                      <div><strong>Picker: <span id="info-nama-picker"><?= $nama_picker ?></span></strong></div>
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
                             value=""
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
          <!--
            Tabel biasa, bukan DataTables server-side lagi. Satu resi cuma
            berisi beberapa SKU, sementara DataTables menuntut satu request HTTP
            tersendiri (get_scan_packer_data) tiap kali tabel dibangun ulang.
            Barisnya sekarang dirender renderDetailSku() dari data yang sudah
            ikut di respons scan.
          -->
          <div class="panel-body" id="table-scan-packer">
            <table class="table table-striped" id="tabel-detail-sku">
              <thead>
                <tr>
                  <th class="text-center">#</th>
                  <th class="text-center">Foto</th>
                  <th class="text-center">Nama Barang</th>
                  <th class="text-center">Jenis Packing</th>
                  <th class="text-center">SKU</th>
                  <th class="text-center">Quantity</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="body-detail-sku">
                <tr>
                  <td colspan="7" class="text-center">No details</td>
                </tr>
              </tbody>
            </table>
          </div>

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
                    <!--
                      id-nya dulu "noresi", bentrok dengan input scan di atas.
                      Selama halaman masih dirender ulang tiap scan nilainya
                      diisi PHP sehingga bentrokan itu tidak kelihatan; sekarang
                      diisi JS saat modal dibuka, jadi id-nya harus unik.
                    -->
                    <input type="text" value="" name="noresi" id="modal_noresi" class="form-control" />
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
  var idPrintResi;
  var noresi;
  var sku;
  var qty;

  // Resi yang sudah discan sekali dan menunggu scan kedua untuk disimpan.
  // Dulu state ini disimpan di session server (packer_double_scan), sehingga
  // scan pertama pun harus menunggu satu perjalanan bolak-balik ke server --
  // dan dua tab browser milik packer yang sama saling merusak hitungan.
  var resiTertunda = null;

  // Antrian scan. Scanner barcode bisa mengirim resi berikutnya sebelum request
  // sebelumnya selesai; tanpa antrian, scan itu hilang diam-diam. Ini inti
  // keluhan "delay antrian" di menu ini -- sekarang scan yang datang saat
  // request berjalan ditampung, bukan dibuang.
  var antrianScan = [];
  var sedangProses = false;

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

    // ====================== alur scan ======================

    // Satu-satunya pintu masuk scan. Nilai input langsung diambil lalu
    // dikosongkan, jadi scanner boleh mengetik resi berikutnya kapan saja.
    $('#form-scan-packer').on('submit', function(e) {
      e.preventDefault();

      var nilai = $('#noresi').val().trim();
      $('#noresi').val('').focus();

      if (nilai === '') {
        return;
      }

      antrianScan.push({ noresi: nilai, paksaSimpan: false });
      prosesAntrian();
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

      // Dulu diisi PHP karena halaman dirender ulang tiap scan. Halaman sekarang
      // dirender sekali, jadi noresi yang ikut terkirim ke masalah-picker-save
      // harus diisi dari tombol yang barusan diklik.
      $('#modal_noresi').val(noresi);

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
            // Tidak ada lagi muat-ulang tabel di sini: isi baris SKU tidak
            // berubah setelah masalah picker dilaporkan, dan tabelnya bukan
            // DataTables server-side lagi.

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

    // Tombol Submit manual: jalurnya sama persis dengan scan kedua, supaya
    // hanya ada satu tempat yang menyimpan ke tblpacking.
    $('#submit-selected').on('click', function() {
      var noresiDetail = $('#noresi-detail').val();

      if (!noresiDetail || noresiDetail.trim() === '') {
          $('#errorMessage').text("Nomor resi tidak boleh kosong!");
          $('#errorModal').fadeIn();
          setTimeout(function() {
            $('#errorModal').fadeOut();
          }, 1000);
          return;
      }

      // paksaSimpan supaya tombol ini tidak bergantung pada nilai resiTertunda
      // saat antrian diproses -- scan lain bisa saja berjalan di sela-selanya.
      antrianScan.push({ noresi: noresiDetail.trim(), paksaSimpan: true });
      prosesAntrian();
    });

    $('#btn-reset').on('click', function () {
        resetScanView();
    });
  })

  // ====================== inti alur scan ======================

  // Menjalankan antrian satu per satu. Selama satu request berjalan, scan yang
  // masuk hanya ditampung -- tidak dibuang, dan tidak dikirim berbarengan
  // (kiriman berbarengan bisa menyimpan resi yang sama dua kali).
  function prosesAntrian() {
    if (sedangProses || antrianScan.length === 0) {
      return;
    }

    sedangProses = true;
    var tugas = antrianScan.shift();

    tanganiScan(tugas).always(function() {
      sedangProses = false;
      prosesAntrian();
    });
  }

  // Aturan double scan, sekarang dijaga di sini (dulu di session server).
  // Scan pertama: ambil detail paket supaya packer bisa mencocokkan isinya.
  // Scan kedua atas resi yang sama: langsung simpan.
  function tanganiScan(tugas) {
    if (tugas.paksaSimpan || (resiTertunda !== null && resiTertunda === tugas.noresi)) {
      return simpanResi(tugas.noresi);
    }

    return ambilDetailResi(tugas.noresi, resiTertunda);
  }

  function ambilDetailResi(noresiScan, resiSebelumnya) {
    return $.ajax({
      url: 'packer/detail-resi',
      method: 'POST',
      dataType: 'json',
      data: { noresi: noresiScan }
    }).done(function(res) {
      if (!res || res.code !== 200) {
        resiTertunda = null;
        triggerScanFeedback({
          status: 'auto_save_failed',
          type: 'error',
          message: (res && res.message) ? res.message : 'Nomor resi tidak ditemukan',
          exception_code: (res && res.data) ? res.data.EXCEPTION_CODE : null
        });
        return;
      }

      tampilkanDetailResi(res.data);
      resiTertunda = noresiScan;

      // Pesan dan suaranya sengaja dibuat sama persis dengan versi lama supaya
      // packer tidak perlu menyesuaikan kebiasaan.
      if (resiSebelumnya && resiSebelumnya !== noresiScan) {
        triggerScanFeedback({
          status: 'scan_restarted',
          type: 'information',
          message: 'Nomor resi berubah dari ' + resiSebelumnya + ' ke ' + noresiScan + '. Scan resi baru ini sekali lagi untuk menyimpan.'
        });
      } else {
        triggerScanFeedback({
          status: 'need_second_scan',
          type: 'warning',
          message: 'Scan ulang nomor resi ' + noresiScan + ' satu kali lagi untuk menyimpan.'
        });
      }
    }).fail(function() {
      resiTertunda = null;
      triggerScanFeedback({
        status: 'auto_save_failed',
        type: 'error',
        message: 'Gagal menghubungi server. Periksa koneksi lalu scan ulang.'
      });
    });
  }

  function simpanResi(noresiScan) {
    return $.ajax({
      url: 'packer/save-packer',
      method: 'POST',
      dataType: 'json',
      data: { noresi: noresiScan }
    }).done(function(res) {
      // make_ajax_response() SELALU membalas HTTP 200 dan menaruh status di body
      // (set_status_header(4xx) di CI mengirim halaman HTML yang merusak parsing
      // JSON). Jadi kegagalan wajib diperiksa lewat res.code, bukan lewat
      // callback error -- versi lama memeriksanya di callback error, sehingga
      // resi yang gagal simpan tetap memunculkan popup "Success".
      resiTertunda = null;

      if (res && res.code === 201) {
        if (res.data && typeof res.data.total_scan !== 'undefined') {
          $('#info-total-scan').text(res.data.total_scan);
        }

        $('#successMessage').text('Resi ' + noresiScan + ' berhasil disimpan.');
        $('#successModal').fadeIn();
        setTimeout(function() { $('#successModal').fadeOut(); }, 1000);

        triggerScanFeedback({
          status: 'auto_save_success',
          type: 'success',
          message: 'Nomor resi ' + noresiScan + ' berhasil otomatis disimpan.'
        });
        return;
      }

      var pesan = (res && res.message) ? res.message : 'Gagal menyimpan data!';

      $('#errorMessage').text(pesan);
      $('#errorModal').fadeIn();
      setTimeout(function() { $('#errorModal').fadeOut(); }, 1000);

      triggerScanFeedback({
        status: 'auto_save_failed',
        type: 'error',
        message: pesan,
        exception_code: (res && res.data) ? res.data.EXCEPTION_CODE : null
      });
    }).fail(function() {
      resiTertunda = null;
      triggerScanFeedback({
        status: 'auto_save_failed',
        type: 'error',
        message: 'Gagal menghubungi server. Periksa koneksi lalu scan ulang.'
      });
    });
  }

  function tampilkanDetailResi(data) {
    $('#info-total-scan').text(data.total_scan);
    $('#info-nama-picker').text(data.nama_picker);
    $('#noresi-detail').val(data.noresi);

    renderDetailSku(data.items, data.noresi, data.id_printresi);

    $('#result-info').show();
    $('#button-footer').show();
    $('#table-scan-packer').show();
  }

  // Pengganti kolom-kolom yang dulu dirakit di get_scan_packer_data(). Kelas
  // .foto-preview dan .saveMasalahPicker beserta data-attribute-nya dijaga sama
  // supaya handler yang sudah ada tetap bekerja tanpa diubah.
  function renderDetailSku(items, noresiResi, idPrintResiResi) {
    var $body = $('#body-detail-sku').empty();

    if (!items || items.length === 0) {
      $body.append('<tr><td colspan="7" class="text-center">No details</td></tr>');
      return;
    }

    items.forEach(function(item, i) {
      var foto = item.link_foto
        ? '<img src="' + escapeHtml(item.link_foto) + '" style="max-width: 100px; max-height: 100px; cursor: pointer;" class="img-thumbnail foto-preview" data-foto="' + escapeHtml(item.link_foto) + '">'
        : '<span class="text-muted">No Photo</span>';

      var packing = item.jenis_packing
        ? '<button class="btn btn-xs btn-info" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #00c0ef !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-cube"></i> ' + escapeHtml(item.jenis_packing) + '</button>'
        : '<button class="btn btn-xs btn-warning" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #f39c12 !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-warning"></i> Belum diset</button>';

      var aksi = '<div class="text-center"><button class="btn btn-info saveMasalahPicker"'
        + ' data-id="' + escapeHtml(idPrintResiResi) + '"'
        + ' data-noresi="' + escapeHtml(noresiResi) + '"'
        + ' data-sku="' + escapeHtml(item.sku) + '"'
        + ' data-qty="' + escapeHtml(item.jumlah) + '"'
        + ' data-nama-picker="' + escapeHtml(item.nama_picker) + '"'
        + ' data-no-rak="' + escapeHtml(item.no_rak) + '"'
        + '>Masalah Picker</button></div>';

      $body.append(
        '<tr>'
        + '<td class="text-center">' + (i + 1) + '.</td>'
        + '<td class="text-center">' + foto + '</td>'
        + '<td class="text-center">' + escapeHtml(item.nama_sku) + '</td>'
        + '<td class="text-center">' + packing + '</td>'
        + '<td class="text-center">' + escapeHtml(item.sku) + '</td>'
        + '<td class="text-center">' + escapeHtml(item.jumlah) + '</td>'
        + '<td class="text-center">' + aksi + '</td>'
        + '</tr>'
      );
    });
  }

  // Nama SKU dan link foto berasal dari data master yang diisi manusia, jadi
  // tetap harus di-escape walau sudah lewat JSON.
  function escapeHtml(nilai) {
    if (nilai === null || typeof nilai === 'undefined') {
      return '';
    }

    return String(nilai)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

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
      // NOT_FOUND datang dari packer/detail-resi (scan pertama) maupun
      // packer/save-packer (scan kedua) -- dua-duanya ucapan "tidak ditemukan"
      // supaya salah scan barcode terbedakan dari kegagalan lain.
      if (exceptionCode === 'ALREADY_PACKED') {
        audioId = 'audio-sudah-packing';
      } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
        audioId = 'audio-cancel-order';
      } else if (exceptionCode === 'NOT_FOUND') {
        audioId = 'audio-tidak-ditemukan';
      } else {
        audioId = 'audio-fail';
      }
    }

    playAudio(audioId);
  }

  function resetScanView() {
    resiTertunda = null;
    $('#result-info').hide();
    $('#table-scan-packer').hide();
    $('#button-footer').hide();
    $('#noresi').val('').focus();
    $('#noresi-detail').val('');
    $('#info-nama-picker').text('-');
    $('#body-detail-sku').html('<tr><td colspan="7" class="text-center">No details</td></tr>');
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

  /* Blok hasil selalu ada di DOM, disembunyikan sampai ada resi yang discan. */
  #result-info,
  #table-scan-packer {
      display: none;
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
