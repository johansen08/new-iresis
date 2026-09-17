<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="receipt/delete-receipt-action" class="form-horizontal" id="form_delete_resi" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Hapus Resi</strong></h3>
        </div>

        <div class="panel-body">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Resi</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" name="noresi" id="noresi" class="form-control" />
            </div>
          </div>

          <div class="tile tile-default" id="div_container_deleted_receipt">
            <span id="span_delete_receipt">-</span>
            <p><small id="p_latest_receipt_message">Nomor resi yang sudah dihapus</small></p>
          </div>

        </div>

        <div class="panel-footer">
          <button type="submit" class="btn btn-info"><i class="fa fa-trash-o"></i> Hapus</button>
          <button type="reset" class="btn btn-primary pull-right">Reset</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
  // Semua suara di halaman ini lewat sini, jangan panggil .play() langsung.
  // suaraScan (main.php) memotong durasi dan mereset posisi, jadi aksi
  // berikutnya tidak menunggu suara sebelumnya selesai.
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

  // delete-receipt-action tidak mengirim EXCEPTION_CODE, jadi penyebab gagal
  // dibaca dari kalimat pesannya. Resi tidak ditemukan dapat ucapan sendiri
  // supaya salah scan barcode terbedakan dari penolakan lain.
  function playScanErrorAudio(message) {
    var teks = (message || "").toUpperCase();
    if (teks.includes('TIDAK DITEMUKAN')) {
      playAudio('audio-tidak-ditemukan');
    } else {
      playAudio('audio-wrong');
    }
  }

  function tampilkanGagal(form, pesan) {
    $("#span_delete_receipt").text(form.noresi.value);
    $("#div_container_deleted_receipt").removeClass("tile-default").addClass("tile-danger");
    $("#p_latest_receipt_message").text(pesan || "Gagal memproses data");

    playScanErrorAudio(pesan);

    form.noresi.value = "";
    form.noresi.disabled = false;
    form.noresi.focus();
  }

  $("#noresi").focus();
  var jvalidate = $("#form_delete_resi").validate({
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
        // make_ajax_response() SELALU membalas HTTP 200 dan menaruh status di
        // body, jadi penolakan server (resi tidak ditemukan) mendarat di sini,
        // bukan di .fail(). Dulu blok ini langsung memutar suara sukses tanpa
        // memeriksa code -- resi yang tidak ada pun berbunyi "sudah dihapus".
        if (!response || (response.code !== 200 && response.code !== 201)) {
          tampilkanGagal(form, response && response.message);
          return;
        }

        $("#span_delete_receipt").text(form.noresi.value);
        $("#div_container_deleted_receipt").removeClass("tile-danger").addClass("tile-default");
        $("#p_latest_receipt_message").text("Nomor resi yang sudah dihapus");

        playAudio('audio-alexis');

        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      }).fail(function(error) {
        // Hanya error jaringan / respons yang tidak terbaca yang sampai ke sini.
        var response = {};
        try { response = JSON.parse(error.responseText); } catch (e) {}

        tampilkanGagal(form, response.message || "Gagal menghubungi server");
      });
      return false; // required to block normal submit since you used ajax
    }
  });
</script>