<div class="row">
  <div class="col-md-6 center-block float-none">
    <form action="resi_team/save_scan_preorder" class="form-horizontal" id="form_scan_preorder" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>SCAN RESI PREORDER (TIM RESI)</strong></h3>
        </div>

        <div class="panel-body">

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nama Picker (Opsional)</label>
            <div class="col-md-8 col-xs-12">
              <select name="id_pegawaipicker" id="id_pegawaipicker" class="form-control select" data-live-search="true">
                <option value="" selected>-- Tanpa Picker --</option>
                <?php foreach ($list_picker as $picker) : ?>
                  <option value="<?= $picker['id_pegawai'] ?>"><?= $picker['nama_pegawai'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Mark as Preorder?</label>
            <div class="col-md-8 col-xs-12">
              <select name="is_preorder" id="is_preorder" class="form-control select">
                <option value="1" selected>Ya (Preorder)</option>
                <option value="0">Tidak (Normal)</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nomor Resi</label>
            <div class="col-md-8 col-xs-12">
              <input type="text" name="noresi" id="noresi" class="form-control" />
            </div>
          </div>

          <div class="tile tile-default" id="div_container_latest_receipt">
            <span id="span_latest_receipt">-</span>
            <p><small id="p_latest_receipt_message">Scan resi untuk ditandai sebagai preorder</small></p>
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

  $("#form_scan_preorder").submit(function(event) {
    event.preventDefault();

    var noresi = $("#noresi").val().trim();
    if (!noresi) return false;

    $("#noresi").val("");
    
    requestQueue.push({
      url: $(this).attr("action"),
      data: {
        noresi: noresi,
        id_pegawaipicker: $("#id_pegawaipicker").val(),
        is_preorder: $("#is_preorder").val()
      },
      success: function(data) {
        $("#span_latest_receipt").text(noresi);
        
        if (data.code === 201) {
            $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-success");
            $("#p_latest_receipt_message").text(data.message);
            playAudio('audio-alexis');
        } else {
            $("#div_container_latest_receipt").removeClass("tile-success").addClass("tile-danger");
            $("#p_latest_receipt_message").text(data.message);
            playAudio('audio-wrong');
        }
      },
      error: function(xhr) {
        var msg = "Gagal memproses data";
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        
        $("#span_latest_receipt").text(noresi);
        $("#div_container_latest_receipt").removeClass("tile-success").addClass("tile-danger");
        $("#p_latest_receipt_message").text(msg);
        playAudio('audio-wrong');
      }
    });

    processQueue();
    return false;
  });
</script>
