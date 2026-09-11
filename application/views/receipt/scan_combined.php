<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="receipt/save-combined" method="post" class="form-horizontal" id="form_scan_combined" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>SCAN COMBINED (Picker + Packer)</strong></h3>
        </div>

        <div class="panel-body">
            
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nama Picker</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_picker" id="id_picker" class="form-control select" data-live-search="true">
                <option value="" selected disabled>-- Pilih Picker --</option>
                <?php foreach ($list_picker as $picker) : ?>
                  <option value="<?= $picker['id_pegawai'] ?>"><?= $picker['nama_pegawai'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nama Packer</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_packer" id="id_packer" class="form-control select" data-live-search="true">
                <option value="" selected disabled>-- Pilih Packer --</option>
                <?php foreach ($list_packer as $packer) : ?>
                  <option value="<?= $packer['id_user'] ?>"><?= $packer['name'] ?> (<?= $packer['username'] ?>)</option>
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
            <p><small id="p_latest_receipt_message">Nomor resi/picklist terakhir yang berhasil di-sync</small></p>
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

<!-- Sound elements are preloaded in main.php (audio-alexis, audio-wrong) -->

<script type="text/javascript">
  $("#noresi").focus();

  var requestQueue = [];
  var isProcessing = false;

  function processQueue() {
    if (isProcessing || requestQueue.length === 0) return;

    isProcessing = true;
    var request = requestQueue.shift();
    
    $.ajax({
      url: request.url,
      type: 'post',
      data: request.data,
      dataType: 'json',
      success: function(data) {
        request.success(data);
        isProcessing = false;
        processQueue();
      },
      error: function(xhr, status, error) {
        request.error(xhr, status, error);
        isProcessing = false;
        processQueue();
      }
    });
  }

  $("#form_scan_combined").submit(function(event) {
    event.preventDefault();

    var noresi = $("#noresi").val().trim();
    var id_picker = $("#id_picker").val();
    var id_packer = $("#id_packer").val();

    if (!id_picker) {
        if (typeof noty === 'function') noty({text: 'Pilih Picker terlebih dahulu', type: 'error', layout: 'topRight', timeout: 2000});
        playStandardAudio('audio-wrong');
        return false;
    }
    if (!id_packer) {
        if (typeof noty === 'function') noty({text: 'Pilih Packer terlebih dahulu', type: 'error', layout: 'topRight', timeout: 2000});
        playStandardAudio('audio-wrong');
        return false;
    }
    if (!noresi) return false;

    $("#noresi").val(""); // Clear input immediately for next scan
    $("#noresi").focus(); 

    requestQueue.push({
      url: $(this).attr("action"),
      data: {
        noresi: noresi,
        id_picker: id_picker,
        id_packer: id_packer
      },
      success: function(data) {
        var displayResi = data.noresi ? data.noresi : noresi;
        $("#span_latest_receipt").text(displayResi);
        
        if (data.message === 'Sukses menambahkan data' || data.code === 201) {
            $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-success");
            $("#p_latest_receipt_message").text("Berhasil di-scan: Picker & Packer");
            playStandardAudio('audio-alexis');
        } else {
            $("#div_container_latest_receipt").removeClass("tile-success").addClass("tile-danger");
            $("#p_latest_receipt_message").text(data.message);
            playStandardAudio('audio-wrong');
        }
        $("#noresi").focus();
      },
      error: function(xhr) {
        var msg = "Gagal memproses data";
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        }
        $("#span_latest_receipt").text(noresi);
        $("#div_container_latest_receipt").removeClass("tile-success").addClass("tile-danger");
        $("#p_latest_receipt_message").text(msg);
        playStandardAudio('audio-wrong');
        $("#noresi").focus();
      }
    });

    processQueue();
    return false;
  });

  // suaraScan (main.php) memotong durasi dan mereset posisi, jadi scan
  // berikutnya tidak menunggu suara scan sebelumnya selesai.
  function playStandardAudio(id, opsi) {
    if (typeof suaraScan === 'function') {
      suaraScan(id, opsi);
      return;
    }

    var audio = document.getElementById(id);
    if (audio) {
      audio.currentTime = 0;
      audio.play().catch(function(e) {
          console.error("Audio play failed:", e);
      });
    }
  }
</script>
