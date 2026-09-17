<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="handover/save-handover" class="form-horizontal" id="form_scan_handover" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Resi Keluar</strong></h3>
        </div>

        <div class="panel-body">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Total scan resi Anda hari ini</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" id="total_scan" value="<?= $total_scan ?>" class="form-control" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Komputer</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" class="form-control" value="<?= $nama_komputer ?>" readonly />
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
            <p><small id="p_latest_receipt_message">Nomor resi terakhir yang sudah di-scan keluar</small></p>
          </div>

        </div>

        <div class="panel-footer">
          <button type="submit" class="btn btn-info">Submit</button>
          <a href="handover_scan/print" class="btn btn-default link pull-right"><i class="fa fa-print"></i> Cetak Tanda Terima</a>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
  $("#noresi").focus();
  var total_scan = document.getElementById('total_scan');

  // Semua suara di halaman ini lewat dua fungsi ini, jangan panggil .play()
  // langsung. suaraScan (main.php) memotong durasi dan mereset posisi: error.mp3
  // saja ~3,8 detik, dan tanpa reset currentTime scan kedua yang datang sebelum
  // suara pertama habis tidak berbunyi sama sekali.
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

  function playSuaraKurir(noresi) {
    if (typeof suaraKurir === 'function') {
      suaraKurir(noresi);
      return;
    }
    playAudio('audio-alert');
  }

  var jvalidate = $("#form_scan_handover").validate({
    ignore: [],
    rules: {
      noresi: {
        required: true,
      },
    },
    submitHandler: function(form) {
      var formData = new FormData(form);

      form.noresi.disabled = true;

      $.ajax({
        url: form.action,
        type: 'post',
        data: Object.fromEntries(formData),
        success: function(data) {},
        error: function(data) {},
      }).done(function(response) {
        // Parse response if it's a string
        var data = response;
        if (typeof response === 'string') {
          try { data = JSON.parse(response); } catch(e) {}
        }

        if (data && (data.code === 200 || data.code === 201)) {
          // SUCCESS LOGIC
          $("#div_container_latest_receipt").removeClass("tile-danger tile-default").addClass("tile-success");
          $("#span_latest_receipt").text(form.noresi.value);
          $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan");

          // Nada per kurir ada di suaraKurir() (main.php), sama persis dengan
          // yang dipakai Scan HO+NDD. Sebelumnya pemetaan prefiks resi disalin
          // di sini dan delapan cabangnya cuma menghasilkan dua bunyi:
          // shopee/jne/rekomen satu berkas, jnt/lazada/sicepat/ninja/instant
          // satu berkas lagi.
          playSuaraKurir(form.noresi.value);

          total_scan.value = Number(total_scan.value) + 1;

          form.noresi.value = "";
          form.noresi.disabled = false;
          form.noresi.focus();
        } else {
          // LOGICAL ERROR (e.g. 400 Already Handover / Canceled)
          var msg = data && data.message ? data.message : "Gagal memproses data";
          
          $("#span_latest_receipt").text(form.noresi.value);
          $("#div_container_latest_receipt").removeClass("tile-default tile-success").addClass("tile-danger");
          $("#p_latest_receipt_message").text(msg);

          const exceptionCode = (data.data && data.data.EXCEPTION_CODE) ? data.data.EXCEPTION_CODE : '';

          // Distinct Audio based on Exception Code
          if (exceptionCode === 'ALREADY_HANDOVER' || exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
              playAudio('audio-error');
          } else if (exceptionCode === 'NOT_PICKED' || exceptionCode === 'NOT_PACKED') {
              playAudio('audio-fail');
          } else if (exceptionCode === 'NOT_FOUND') {
              // Resi tidak ada di sistem: ucapan "tidak ditemukan", sama dengan
              // halaman scan lain. Dulu ikut audio-alert bersama error lain-lain.
              playAudio('audio-tidak-ditemukan');
          } else {
              playAudio('audio-alert');
          }

          form.noresi.value = "";
          form.noresi.disabled = false;
          form.noresi.focus();
        }
      }).fail(function(error) {
        var response = {};
        try {
            response = JSON.parse(error.responseText);
        } catch(e) {
            response = { message: "Unknown error" };
        }

        $("#span_latest_receipt").text(form.noresi.value);
        $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
        $("#p_latest_receipt_message").text(response.message);

        const exceptionCode = (response.data && response.data.EXCEPTION_CODE) ? response.data.EXCEPTION_CODE : '';

        // Distinct Audio based on Exception Code
        if (exceptionCode === 'ALREADY_HANDOVER') {
            // Double Scan - User says ERROR
            playAudio('audio-error');
        } else if (exceptionCode === 'NOT_PICKED' || exceptionCode === 'NOT_PACKED') {
            // Lost Scan / Skip Step - User says FAIL
            playAudio('audio-fail');
        } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
            // Problematic order status - User says ERROR
            playAudio('audio-error');
        } else if (exceptionCode === 'NOT_FOUND') {
            // Resi tidak ada di sistem: ucapan "tidak ditemukan"
            playAudio('audio-tidak-ditemukan');
        } else {
            // Other errors
            playAudio('audio-alert');
        }

        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      });
      return false; // required to block normal submit since you used ajax
    }
  });
</script>