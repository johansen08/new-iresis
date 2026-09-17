<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="picker/save-pending-picker" class="form-horizontal" id="form_scan_pending_picker" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Pending Picker</strong></h3>
        </div>

        <div class="panel-body">

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
            <label class="col-md-3 col-xs-12 control-label">Resi</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" name="noresi" id="noresi" class="form-control" />
            </div>
          </div>

          <div class="tile tile-default" id="div_container_latest_receipt">
            <span id="span_latest_receipt">-</span>
            <p><small id="p_latest_receipt_message">Nomor resi terakhir yang sudah di-scan Picker</small></p>
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

  function tampilkanGagal(form, pesan, exceptionCode) {
    $("#span_latest_receipt").text(form.noresi.value);
    $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
    $("#p_latest_receipt_message").text(pesan || "Gagal memproses data");

    playScanErrorAudio(pesan, exceptionCode);

    form.noresi.value = "";
    form.noresi.disabled = false;
    form.noresi.focus();
  }

  var jvalidate = $("#form_scan_pending_picker").validate({
    ignore: [],
    rules: {
      id_pegawaipicker: {
        required: true,
      },
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
        // make_ajax_response() SELALU membalas HTTP 200 dan menaruh status di
        // body, jadi penolakan server (resi tidak ditemukan, double, batal)
        // mendarat di sini, bukan di .fail(). Dulu blok ini langsung memutar
        // suara sukses tanpa memeriksa code -- semua penolakan berbunyi sukses.
        if (!response || (response.code !== 200 && response.code !== 201)) {
          var kode = (response && response.data) ? response.data.EXCEPTION_CODE : '';
          tampilkanGagal(form, response && response.message, kode);
          return;
        }

        $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
        $("#span_latest_receipt").text(form.noresi.value);
        $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan Picker");

        playAudio('audio-alexis');


        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      }).fail(function(error) {
        // Hanya error jaringan / respons yang tidak terbaca yang sampai ke sini.
        var response = {};
        try { response = JSON.parse(error.responseText); } catch (e) {}
        var kode = (response && response.data) ? response.data.EXCEPTION_CODE : '';

        tampilkanGagal(form, response.message || "Gagal menghubungi server", kode);
      });
      return false; // required to block normal submit since you used ajax
    }
  });
</script>