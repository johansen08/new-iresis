<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="picker/save-update-picker" class="form-horizontal" id="form_scan_picker" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Update Picker</strong></h3>
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

  $('#id_pegawaipicker').on('change', function() {
    $("#noresi").focus();
  });

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
  // bukan dari teks pesannya -- pola sama dengan halaman Scan Picker.
  //   NOT_PICKED      -> nada WRONG, resi belum punya nama picker untuk dikoreksi
  //   NOT_FOUND       -> ucapan "tidak ditemukan"
  //   ORDER_CANCELED  -> ucapan "cancel"
  //   ORDER_COMPLETED -> nada fail
  // Dulu NOT_PICKED dan NOT_FOUND sama-sama nada WRONG. Sekarang dipisah:
  // resi yang belum punya nama picker masih bisa dibereskan lewat menu Scan
  // Picker, sedangkan resi yang tidak ada di sistem berarti salah scan
  // barcode -- tindakannya beda, suaranya harus beda.
  function playScanErrorAudio(message, exceptionCode) {
    switch (exceptionCode) {
      case 'NOT_PICKED':      playAudio('audio-wrong');            return;
      case 'NOT_FOUND':       playAudio('audio-tidak-ditemukan');  return;
      case 'ORDER_CANCELED':  playAudio('audio-cancel-order');     return;
      case 'ORDER_COMPLETED': playAudio('audio-fail');             return;
    }

    // Tanpa kode -- error jaringan, timeout, atau respons yang tidak terbaca.
    var teks = (message || "").toUpperCase();

    if (teks.includes('CANCEL') || teks.includes('BATAL')) {
      playAudio('audio-cancel-order');
    } else if (teks.includes('TIDAK DITEMUKAN')) {
      playAudio('audio-tidak-ditemukan');
    } else if (teks.includes('BELUM MEMILIKI NAMA PICKER')) {
      playAudio('audio-wrong');
    } else {
      playAudio('audio-alert');
    }
  }

  var jvalidate = $("#form_scan_picker").validate({
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
      var noresiValue = form.noresi.value;

      form.noresi.disabled = true;

      function selesai() {
        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      }

      function tampilkanBerhasil() {
        $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
        $("#span_latest_receipt").text(noresiValue);
        $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan Picker");

        playAudio('audio-alexis');
        selesai();
      }

      function tampilkanGagal(pesan, exceptionCode) {
        $("#span_latest_receipt").text(noresiValue);
        $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
        $("#p_latest_receipt_message").text(pesan || "Gagal memproses data");

        playScanErrorAudio(pesan, exceptionCode);
        selesai();
      }

      $.ajax({
        url: form.action,
        type: 'post',
        data: Object.fromEntries(formData),
      }).done(function(response) {
        // make_ajax_response() SELALU membalas HTTP 200 dan menaruh status di body
        // JSON, jadi .done() juga dipanggil untuk respons gagal. Dulu blok ini
        // langsung dianggap berhasil: resi yang belum punya nama picker ditolak
        // server, tapi di layar tetap tampil "sudah di-scan Picker" dan berbunyi
        // ALEXIS -- operator menyangka update-nya masuk. Cek response.code dulu.
        if (typeof response === 'string') {
          try { response = JSON.parse(response); } catch (err) { response = {}; }
        }

        if (response && response.code === 201) {
          tampilkanBerhasil();
          return;
        }

        var kode = (response && response.data) ? response.data.EXCEPTION_CODE : '';
        tampilkanGagal(response ? response.message : '', kode);
      }).fail(function(error) {
        // Sisa jalur: putus jaringan atau error 500 yang bukan JSON.
        var response = {};
        try {
          response = JSON.parse(error.responseText);
        } catch (err) {
          response.message = "Terjadi kesalahan pada server";
        }

        var kode = (response && response.data) ? response.data.EXCEPTION_CODE : '';
        tampilkanGagal(response.message, kode);
      });
      return false; // required to block normal submit since you used ajax
    }
  });
</script>