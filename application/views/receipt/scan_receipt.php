<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="receipt/save-receipt" class="form-horizontal" id="form_scan_resi" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Pertama Resi</strong></h3>
        </div>

        <div class="panel-body">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Total scan resi Anda hari ini</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" id="total_scan" value="<?= $total_scan ?>" class="form-control" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Marketplace</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_marketplace" id="id_marketplace" class="form-control select" data-live-search="true">
                <?php foreach ($list_marketplace as $marketplace) : ?>
                  <option value="<?= $marketplace['id_marketplace'] ?>"><?= $marketplace['nama_marketplace'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Kurir</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_kurir" id="id_kurir" class="form-control select" data-live-search="true">
                <?php foreach ($list_courrier as $courrier) : ?>
                  <option value="<?= $courrier['id_kurir'] ?>"><?= $courrier['nama_kurir'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Pick List</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" name="nomorpicklist" id="nomorpicklist" class="form-control" />
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
            <p><small id="p_latest_receipt_message">Nomor resi terakhir yang sudah di-scan</small></p>
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

  // Penyebab gagal dibaca dari kalimat pesan server: endpoint save-receipt
  // tidak mengirim EXCEPTION_CODE seperti jalur packer/HO.
  function playScanErrorAudio(message) {
    var teks = (message || "").toUpperCase();

    if (teks.includes('CANCEL') || teks.includes('BATAL')) {
      playAudio('audio-cancel-order');
    } else if (teks.includes('SUDAH') || teks.includes('COMPLETED')) {
      playAudio('audio-sudah-scan');
    } else {
      playAudio('audio-wrong');
    }
  }

  $("#noresi").focus();
  var total_scan = document.getElementById('total_scan');

  var jvalidate = $("#form_scan_resi").validate({
    ignore: [],
    rules: {
      id_marketplace: {
        required: true,
      },
      id_kurir: {
        required: true,
      },
      nomorpicklist: {
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
        var noresi = form.noresi.value;
        $("#span_latest_receipt").text(noresi);

        // make_ajax_response() SELALU mengirim HTTP 200 dan menaruh status di
        // body (lihat MY_Controller). Jadi .fail() di bawah cuma kena error
        // jaringan -- resi gagal tetap masuk ke sini. Sebelum ini cabangnya
        // tidak diperiksa sama sekali: resi yang ditolak server tetap berbunyi
        // ALEXIS dan tetap menambah counter, seolah-olah berhasil.
        var berhasil = response && (response.code === 201 || response.code === 200);

        if (!berhasil) {
          $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
          $("#p_latest_receipt_message").text(response && response.message ? response.message : "Gagal memproses data");

          playScanErrorAudio(response && response.message);

          form.noresi.value = "";
          form.noresi.disabled = false;
          form.noresi.focus();
          return;
        }

        $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
        $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan");

        playAudio('audio-alexis');

        total_scan.value = Number(total_scan.value) + 1;

        // Auto print label
        if (response.data && response.data.id_printresi) {
          window.open('receipt/print-label/' + response.data.id_printresi, '_blank', 'width=450,height=650');
        }

        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      }).fail(function(response) {
        $("#span_latest_receipt").text(form.noresi.value);
        $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");

        playAudio('audio-wrong');

        form.noresi.value = "";
        form.noresi.disabled = false;
        form.noresi.focus();
      });
      return false; // required to block normal submit since you used ajax
    }
  });
</script>