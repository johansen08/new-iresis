<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="picker/save_scan_picker_preorder" class="form-horizontal" id="form_scan_picker" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Picker PreOrder</strong></h3>
        </div>

        <div class="panel-body">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Total scan resi Anda hari ini</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" id="total_scan" value="<?= $total_scan ?>" class="form-control" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nama Picker</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_pegawaipicker" id="id_pegawaipicker" class="form-control select" data-live-search="true">
                <?php foreach ($list_picker as $picker) : ?>
                  <option value="<?= $picker['id_pegawai'] ?>"><?= $picker['nama_pegawai'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Status Performa</label>
            <div class="col-md-8 col-xs-12">
              <select name="status_performa" id="status_performa" class="form-control select" data-live-search="true" style="font-weight: bold;">
                <option value="" style="font-weight: bold;" selected disabled>-- Select Status Performa --</option>
                <?php foreach ($list_status_performa as $status) : ?>
                  <option value="<?= $status['kode_status'] ?>" style="font-weight: bold;">
                    <?= $status['status_name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Preorder</label>
            <div class="col-md-8 col-xs-12">
              <select name="is_preorder" id="is_preorder" class="form-control select">
                <option value="1">Ya</option>
                <option value="0" selected>Tidak</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Resi</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" name="noresi" id="noresi" class="form-control" />
            </div>
          </div>

          <div class="tile tile-default" id="div_container_latest_receipt">
            <span id="span_latest_receipt">-</span>
            <p><small id="p_latest_receipt_message">Nomor resi terakhir yang sudah di-scan Picker PreOrder</small></p>
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

<script type="text/javascript">
  $("#noresi").focus();
  var total_scan = document.getElementById('total_scan');

  // REQUEST QUEUE untuk handle scan cepat
  var requestQueue = [];
  var isProcessing = false;

  // Function untuk process queue
  // Semua suara di halaman ini lewat sini, jangan panggil .play() langsung.
  // suaraScan (main.php) memotong durasi dan mereset posisi, jadi scan
  // berikutnya tidak menunggu suara scan sebelumnya selesai.
  function playAudio(id, opsi) {
    if (typeof suaraScan === 'function') {
      suaraScan(id, opsi);
      return;
    }
    var el = document.getElementById(id);
    if (el) {
      try { el.currentTime = 0; } catch (err) {}
      el.play();
    }
  }

  // Penyebab gagal dibaca dari EXCEPTION_CODE yang dikirim Picking_fcd::save(),
  // pola sama dengan halaman Scan Picker. Resi tidak ditemukan dapat ucapan
  // sendiri supaya salah scan barcode terbedakan dari penolakan lain.
  function playScanErrorAudio(message, exceptionCode) {
    switch (exceptionCode) {
      case 'ALREADY_PICKED':  playAudio('audio-sudah-scan');       return;
      case 'NOT_FOUND':       playAudio('audio-tidak-ditemukan');  return;
      case 'ORDER_CANCELED':  playAudio('audio-cancel-order');     return;
      case 'ORDER_COMPLETED': playAudio('audio-fail');             return;
    }

    var teks = (message || "").toUpperCase();
    if (teks.includes('TIDAK DITEMUKAN')) {
      playAudio('audio-tidak-ditemukan');
    } else {
      playAudio('audio-wrong');
    }
  }

  function processQueue() {
    if (isProcessing || requestQueue.length === 0) {
      return;
    }

    isProcessing = true;
    var request = requestQueue.shift(); // Ambil request pertama
    
    $.ajax({
      url: request.url,
      type: 'post',
      data: request.data,
      timeout: 10000,
      cache: false,
      success: function(data) {
        request.success(data);
        isProcessing = false;
        processQueue(); // Process next request in queue
      },
      error: function(xhr, status, error) {
        request.error(xhr, status, error);
        isProcessing = false;
        processQueue(); // Process next request in queue
      }
    });
  }

  $('#id_pegawaipicker').on('change', function() {
    $("#noresi").focus();
  });

  var jvalidate = $("#form_scan_picker").validate({
    ignore: [],
    rules: {
      id_pegawaipicker: {
        required: true,
      },
      status_performa: {
        required: true,
      },
      noresi: {
        required: true,
      },
    },
    submitHandler: function(form) {
      var formData = new FormData(form);
      var noresiValue = form.noresi.value;

      // Immediate feedback - update UI
      $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
      $("#span_latest_receipt").text(noresiValue);
      $("#p_latest_receipt_message").text("Dalam antrian...");
      
      // Increment counter immediately
      total_scan.value = Number(total_scan.value) + 1;

      // Penolakan server dan error jaringan sama-sama lewat sini.
      function tampilkanGagal(pesan, exceptionCode) {
        // Rollback counter on error
        total_scan.value = Number(total_scan.value) - 1;

        $("#span_latest_receipt").text(noresiValue);
        $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
        $("#p_latest_receipt_message").text(pesan || "Gagal memproses data");

        playScanErrorAudio(pesan, exceptionCode);
      }

      // Tambahkan ke queue
      requestQueue.push({
        url: form.action,
        data: Object.fromEntries(formData),
        noresiValue: noresiValue,
        success: function(data) {
          // make_ajax_response() SELALU membalas HTTP 200 dan menaruh status di
          // body, jadi penolakan server (resi tidak ditemukan, double, batal)
          // mendarat di sini, bukan di error(). Dulu blok ini langsung memutar
          // suara sukses tanpa memeriksa code -- semua penolakan berbunyi sukses.
          if (!data || (data.code !== 200 && data.code !== 201)) {
            var kode = (data && data.data) ? data.data.EXCEPTION_CODE : '';
            tampilkanGagal(data && data.message, kode);
            return;
          }

          // Success feedback
          $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
          $("#span_latest_receipt").text(noresiValue);
          $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan Picker PreOrder");

          // Play success sound
          playAudio('audio-alexis');
        },
        error: function(xhr, status, error) {
          // Hanya error jaringan / respons yang tidak terbaca yang sampai ke sini.
          var response = {};
          try {
            response = JSON.parse(xhr.responseText);
          } catch (e) {
            response.message = "Terjadi kesalahan pada server";
          }
          var kode = (response && response.data) ? response.data.EXCEPTION_CODE : '';

          tampilkanGagal(response.message, kode);
        }
      });

      // Process queue
      processQueue();

      // Reset form immediately untuk scan berikutnya
      form.noresi.value = "";
      form.noresi.focus();
      
      return false;
    }
  });

  // Process KPI queue every 30 seconds (optimized)
  var kpiQueueInterval = setInterval(function() {
    $.ajax({
      url: 'picker/process-kpi-queue',
      type: 'post',
      timeout: 5000, // 5 second timeout
      cache: false, // Disable cache for real-time data
      success: function(response) {
        // KPI queue processed successfully
      },
      error: function(xhr, status, error) {
        // Silent fail - KPI processing is not critical
        if (status === 'timeout') {
          console.warn('KPI queue processing timeout');
        }
      }
    });
  }, 30000); // 30 seconds
  
  // Clear interval when page unloads
  $(window).on('beforeunload', function() {
    clearInterval(kpiQueueInterval);
  });
</script>
