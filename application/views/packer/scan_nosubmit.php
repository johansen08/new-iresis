<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <!-- title form -->
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Scan Resi Packer</strong></h3>
      </div>

      <!-- input scan noresi -->
      <div class="panel-body">
        <form class="form-horizontal" autocomplete="off" onsubmit="return false;">
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input type="text" class="form-control" name="noresi" id="noresi" placeholder="Nomor resi" />
                <span class="input-group-btn" id="button-group">
                  <button class="btn btn-default" type="button" disabled>
                    <i class="fa fa-search"></i> Cari
                  </button>
                </span>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- info dan tabel -->
      <div id="result-info" style="display:none;">
        <div id="button-footer" class="text-left" style="margin-top:10px;"></div>

        <div class="row">
          <div class="col-md-4 text-center" style="border-right:1px solid #ccc;"><strong>Total Scan: <span id="total_scan">0</span></strong></div>
          <div class="col-md-4 text-center" style="border-right:1px solid #ccc;"><strong>Picker: <span id="nama_picker">-</span></strong></div>
          <div class="col-md-4 text-center"><strong>Komputer: <span id="komputer_packer">-</span></strong></div>
        </div>

        <div class="panel-body" id="table-scan-packer">
          <table class="table table-striped datatable-masalah-picker">
            <thead>
              <tr>
                <th>#</th>
                <th>Foto</th>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="6" class="text-center">No details</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- success modal -->
      <div id="successModal" class="custom-popup-overlay" style="display:none;">
        <div class="custom-popup-box success-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color:#5cb85c;color:white;">
              <h3 class="panel-title"><i class="fa fa-check-circle"></i> <strong>Success!</strong></h3>
            </div>
            <div class="panel-body">
              <p id="successMessage" style="font-size:16px;margin:20px 0;">Data berhasil disubmit!</p>
              <div class="progress" style="margin:10px 0;">
                <div id="progressBar" class="progress-bar progress-bar-success" style="width:100%;transition:width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- error modal -->
      <div id="errorModal" class="custom-popup-overlay" style="display:none;">
        <div class="custom-popup-box error-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color:#d9534f;color:white;">
              <h3 class="panel-title"><i class="fa fa-times-circle"></i> <strong>Error!</strong></h3>
            </div>
            <div class="panel-body">
              <p id="errorMessage" style="font-size:16px;margin:20px 0;">Terjadi kesalahan!</p>
              <div class="progress" style="margin:10px 0;">
                <div id="errorProgressBar" class="progress-bar progress-bar-danger" style="width:100%;transition:width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- audio -->
      <audio id="audio-alexis" src="<?= base_url('assets/audio/ALEXIS.mp3') ?>" preload="auto"></audio>
      <audio id="audio-wrong" src="<?= base_url('assets/audio/WRONG.mp3') ?>" preload="auto"></audio>
      <audio id="audio-slow-alert" src="<?= base_url('assets/audio/WRONG.mp3') ?>" preload="auto"></audio>
    </div>
  </div>
</div>

<!-- Slow Alert Banner -->
<div id="slowAlertBanner" style="display:none; position:fixed; top:0; left:0; right:0; z-index:9999;
     background:#c0392b; color:#fff; text-align:center; padding:14px 10px; font-size:18px; font-weight:bold;
     box-shadow:0 4px 12px rgba(0,0,0,0.4); border-bottom:4px solid #922b21; letter-spacing:1px;">
  <i class="fa fa-exclamation-triangle" style="margin-right:8px;"></i>
  <span>&#9888; KAMU LAMBAT! Percepat scan kamu!</span>
  <span style="font-size:13px; margin-left:15px; opacity:0.85;">(Sudah <span id="slowCountDisplay">0</span>x lambat hari ini)</span>
</div>

<script>
$(document).ready(function(){
  $('#noresi').focus();

  // === CEK STATUS SLOW LANGSUNG SAAT HALAMAN DIBUKA ===
  function checkSlowStatus() {
      $.getJSON('<?= base_url("packer_monitoring/get_slow_status") ?>?t=' + new Date().getTime(), function(res) {
          if (res.is_slow == 1) {
              showSlowAlert(res.slow_count);
          } else {
              hideSlowAlert();
          }
      });
  }
  checkSlowStatus(); // jalankan saat load
  setInterval(checkSlowStatus, 30000); // polling setiap 30 detik

  $('#noresi').on('change', function(){
    const noresi = $(this).val().trim();
    if(!noresi) return;

    $.ajax({
      url: "<?= site_url('packer/save_packer') ?>?t=" + new Date().getTime(),
      type: "POST",
      dataType: "json",
      data: { noresi: noresi },
      success: function(response){
        if (response && (response.code === 200 || response.code === 201)) { 
          if (response.code === 201) {
            playAudio('audio-alexis');
            $('#successMessage').text(noresi + " berhasil diproses.");
            $('#successModal').fadeIn();
            setTimeout(()=>$('#successModal').fadeOut(),1000);
            
            // Update stats if available in response
            if (response.data && response.data.total_scan) $('#total_scan').text(response.data.total_scan);
            if (response.data && response.data.nama_picker) $('#nama_picker').text(response.data.nama_picker);

            // === SLOW ALERT CHECK ===
            if (response.data && response.data.is_slow == 1) {
                showSlowAlert(response.data.slow_count);
            } else {
                hideSlowAlert();
            }
          } else {
            // Already scanned (code 200) - Show as Error/Red
            playAudio('audio-wrong');
            $('#errorMessage').text(response.message || noresi + " sudah di-scan sebelumnya.");
            $('#errorModal').fadeIn();
            setTimeout(()=>$('#errorModal').fadeOut(),2000);
          }
        } else {
          playAudio('audio-wrong');
          $('#errorMessage').text(response.message || "Gagal memproses data!");
          $('#errorModal').fadeIn();
          setTimeout(()=>$('#errorModal').fadeOut(),2000);
        }

        $('#noresi').val('').focus();
        $('#result-info').show();
        if(typeof table !== 'undefined') table.ajax.reload(null,false);
      },
      error: function(){
        $('#errorMessage').text("Gagal mengirim ke server!");
        $('#errorModal').fadeIn();
        playAudio('audio-wrong');
        setTimeout(()=>$('#errorModal').fadeOut(),1000);
        $('#noresi').val('').focus();
      }
    });
  });

  // DataTable server side
  let noresiTbl = '';
  table = $('.datatable-masalah-picker').DataTable({
    scrollX:true,
    pageLength:10,
    processing:true,
    serverSide:true,
    ajax:{
      url:'<?= site_url("packer/get_scan_packer_data/") ?>'+noresiTbl,
      type:'POST'
    },
    columnDefs:[
      {width:'5%',targets:0},{width:'20%',targets:1},{width:'35%',targets:2},{width:'10%',targets:3},{width:'10%',targets:4},{width:'20%',targets:5},
      {className:'text-center',targets:[0,1,2,3,4,5]}
    ]
  });

  // === SLOW ALERT FUNCTIONS ===
  var slowAlertInterval = null;

  function showSlowAlert(count) {
      $('#slowCountDisplay').text(count);
      $('#slowAlertBanner').slideDown(300);
      stopSlowAudio();
      playSlowBeep();
      slowAlertInterval = setInterval(function() {
          playSlowBeep();
      }, 3000);
  }

  function hideSlowAlert() {
      $('#slowAlertBanner').slideUp(300);
      stopSlowAudio();
  }

  // Semua suara di halaman ini lewat sini. Sebelumnya .play() dipanggil
  // langsung tanpa reset currentTime -- scan kedua yang datang sebelum suara
  // pertama habis jadi tidak berbunyi sama sekali, dan WRONG.mp3 (~1 detik)
  // menggantung sampai scan berikutnya.
  function playAudio(id, opsi) {
      if (typeof suaraScan === 'function') {
          suaraScan(id, opsi);
          return;
      }
      var audio = document.getElementById(id);
      if (audio) { audio.currentTime = 0; audio.play().catch(function(e){}); }
  }

  function playSlowBeep() {
      // Beep berulang tiap 3 detik selama status lambat, jadi dipendekkan
      // supaya tidak menumpuk dengan suara hasil scan.
      playAudio('audio-slow-alert', { batas: 500 });
  }

  function stopSlowAudio() {
      if (slowAlertInterval) { clearInterval(slowAlertInterval); slowAlertInterval = null; }
      var audio = document.getElementById('audio-slow-alert');
      if (audio) { audio.pause(); audio.currentTime = 0; }
  }
});
</script>
