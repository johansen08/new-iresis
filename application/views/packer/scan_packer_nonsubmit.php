<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <!-- title form -->
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Scan Packer (Direct Submit)</strong></h3>
        <div class="pull-right" id="packer-session-controls">
            <button class="btn btn-success btn-session <?= $session_status['masuk'] ? 'hidden' : '' ?>" data-action="masuk" id="btn-session-masuk">Check-in</button>
            <button class="btn btn-warning btn-session <?= (!$session_status['masuk'] || $session_status['istirahat'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="istirahat_mulai" id="btn-session-istirahat">Istirahat</button>
            <button class="btn btn-info btn-session <?= !$session_status['istirahat'] ? 'hidden' : '' ?>" data-action="istirahat_selesai" id="btn-session-selesai">Selesai Istirahat</button>
            <button class="btn btn-danger btn-session <?= (!$session_status['masuk'] || $session_status['pulang']) ? 'hidden' : '' ?>" data-action="pulang" id="btn-session-pulang">Check-out</button>
        </div>
      </div>

      <!-- search input by noresi -->
      <div class="panel-body">
        <form action="packer/scan-packer-nosubmit" method="post" class="form-horizontal nojs" autocomplete="off" id="form-scan-packer">
          <div class="form-group">
            <div class="col-md-12">
              <div class="alert alert-info" role="alert">
                <strong>Mode Cepat:</strong> Scan barcode resi. Sistem akan otomatis memproses (Submit) tanpa konfirmasi.
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  name="noresi"
                  id="noresi"
                  placeholder="Scan Nomor Resi disini..."
                  style="font-size: 20px; height: 50px;"
                />
                  <span class="input-group-btn" id="button-group">
                    <button class="btn btn-success" type="submit" style="height: 50px;">
                      <i class="fa fa-arrow-right"></i> Process
                    </button>
                  </span>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="panel-body">
          <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Total Scan Hari Ini: <span id="total_scan_display"><?= $total_scan ?></span></h3>
                </div>
          </div>
          
          <div class="row" id="last-scan-container" style="display:none; margin-top: 20px;">
                <div class="col-md-12">
                    <div class="alert alert-success" id="last-scan-alert">
                        <strong>Berhasil!</strong> Resi <span id="last_resi"></span> telah diproses.
                    </div>
                </div>
          </div>
      </div>

      <div class="panel-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div><strong>Picker: <?= $nama_picker ?></strong></div>
                </div>
                <div class="col-md-4 text-center">
                    <div><strong>Komputer: <?= $komputer_packer ?></strong></div>
                </div>
                <div class="col-md-4 text-center">
                     <!-- Info lain -->
                </div>
            </div>
      </div>

      <!-- Slow Alert Banner -->
      <div id="slowAlertBanner" style="display:none; position:fixed; top:0; left:0; right:0; z-index:9999;
           background:#c0392b; color:#fff; text-align:center; padding:14px 10px; font-size:18px; font-weight:bold;
           box-shadow:0 4px 12px rgba(0,0,0,0.4); border-bottom:4px solid #922b21; letter-spacing:1px;">
        <i class="fa fa-exclamation-triangle" style="margin-right:8px;"></i>
        <span id="slowAlertText">⚠ KAMU LAMBAT! Percepat scan kamu!</span>
        <span style="font-size:13px; margin-left:15px; opacity:0.85;">(Sudah <span id="slowCountDisplay">0</span>x lambat hari ini)</span>
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

      <!-- Audio Elements -->
      <audio id="audio-slow-alert" src="<?= base_url('assets/audio/WRONG.mp3') ?>" preload="auto"></audio>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
      // Focus input on load
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
      
      // Packer Monitoring Session JS
      $(document).on('click', '.btn-session', function() {
        var action = $(this).data('action');
        console.log('Session button clicked:', action);
        
        $.ajax({
            url: '<?= base_url("packer_monitoring/update_session") ?>?t=' + new Date().getTime(),
            method: 'POST',
            data: { action: action },
            dataType: 'json',
            success: function(res) {
                console.log('Session update response:', res);
                if (res.code == 200) {
                    alert('Berhasil: ' + action.replace('_', ' ').toUpperCase());
                    
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


      
      // Keep focus on input if user clicks away (unless they select text)
      // Optional: might be annoying if they try to click other things
      /*
      $(document).on('click', function(e) {
          if(!$(e.target).is('input, a, button')) {
             $('#noresi').focus();
          }
      });
      */

      $('#form-scan-packer').on('submit', function(e) {
          e.preventDefault();
          
          const noresi = $('#noresi').val().trim();
          
          if (!noresi) {
              return;
          }

          const form = this;
          const formData = new FormData(form);

          $.ajax({
              url: 'packer/save-packer-nonsubmit?t=' + new Date().getTime(), // Call the new controller method
              type: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              dataType: 'json',
              success: function(response) {
                  // Handle Success
                  if (response && (response.message === '<?= SUCCESS_SAVE_DATA ?>' || response.code === 201)) { 
                       playAudio('audio-alexis');
                       
                       // Show Success Modal
                       $('#successMessage').text("Resi " + noresi + " berhasil diproses.");
                       $('#successModal').fadeIn();
                       setTimeout(function() {
                         $('#successModal').fadeOut();
                       }, 1000); // 1 second for speed

                       // Update Last Scanned info
                       $('#last_resi').text(noresi);
                       $('#last-scan-container').show();

                       // Update total scan count
                       let currentTotal = parseInt($('#total_scan_display').text()) || 0;
                       $('#total_scan_display').text(currentTotal + 1);

                       // === SLOW ALERT CHECK ===
                       if (response.data && response.data.is_slow == 1) {
                           showSlowAlert(response.data.slow_count);
                       } else {
                           hideSlowAlert();
                       }

                  } else if (response && response.code === 200) {
                       // Nothing to save (already scanned?) - Use RED and ALARM
                       playAudio('audio-wrong');
                       showError(response.message || "Sudah di-scan sebelumnya.");
                  } else {
                       // Penolakan server (resi tidak ditemukan, sudah packing,
                       // batal) mendarat di sini: make_ajax_response() SELALU
                       // membalas HTTP 200 dan menaruh status di body, jadi
                       // callback error di bawah tidak pernah kebagian. Dulu
                       // logika pemilihan suaranya hanya ada di sana, sehingga
                       // semua penolakan di sini berbunyi audio-wrong.
                       var kodeGagal = (response && response.data) ? response.data.EXCEPTION_CODE : '';
                       playScanErrorAudio(kodeGagal);
                       showError((response && response.message) || "Gagal memproses data!");
                  }

                  // Clear input and focus
                  $('#noresi').val('').focus();
              },
              error: function(xhr, status, error) {
                  // Hanya error jaringan / respons yang tidak terbaca yang sampai ke sini.
                  let errorMsg = "Terjadi kesalahan koneksi.";
                  let exceptionCode = '';

                  try {
                      const resp = JSON.parse(xhr.responseText);
                      if(resp.message) errorMsg = resp.message;
                      if(resp.data && resp.data.EXCEPTION_CODE) exceptionCode = resp.data.EXCEPTION_CODE;
                  } catch(e) {
                      console.log("Error parsing error response", e);
                  }

                  playScanErrorAudio(exceptionCode);

                  showError(errorMsg);
                  
                  // Sort of clear input to allow retry, or keep it to let user correct it? 
                  // Usually better to select it so typing replaces it.
                  $('#noresi').select().focus();
              }
          });
      });

      function showError(msg) {
          $('#errorMessage').text(msg);
          $('#errorModal').fadeIn();
          setTimeout(function() {
            $('#errorModal').fadeOut();
          }, 3000);
      }

      function playAudio(id) {
          // Dipotong lewat suaraScan supaya WRONG.mp3 (~1 detik) tidak
          // menggantung sampai scan berikutnya.
          if (typeof suaraScan === 'function') {
              suaraScan(id);
              return;
          }

          const audio = document.getElementById(id);
          if (audio) {
              audio.currentTime = 0;
              audio.play().catch(e => console.log("Audio play failed", e));
          }
      }

      // Suara gagal dipilih dari EXCEPTION_CODE yang dikirim
      // Packer_fcd::save_packer_nonsubmit(). Dulu semua cabang memutar
      // audio-wrong, jadi operator QC tahu scan-nya gagal tapi tidak tahu
      // kenapa -- harus baca modal error dulu. Sekarang penyebabnya diucapkan.
      function playScanErrorAudio(exceptionCode) {
          if (exceptionCode === 'ALREADY_PACKED') {
              playAudio('audio-sudah-packing');
          } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
              playAudio('audio-cancel-order');
          } else if (exceptionCode === 'NOT_FOUND') {
              playAudio('audio-tidak-ditemukan');
          } else {
              // NOT_PICKED (lost scan) dan penyebab lain: nada salah umum.
              playAudio('audio-wrong');
          }
      }

      // === SLOW ALERT FUNCTIONS (tidak mengganggu kode di atas) ===
      var slowAlertInterval = null;

      function showSlowAlert(count) {
          $('#slowCountDisplay').text(count);
          $('#slowAlertBanner').slideDown(300);
          // Loop audio beep setiap 3 detik selama lambat
          stopSlowAudio(); // clear existing
          playSlowBeep();
          slowAlertInterval = setInterval(function() {
              playSlowBeep();
          }, 3000);
      }

      function hideSlowAlert() {
          $('#slowAlertBanner').slideUp(300);
          stopSlowAudio();
      }

      function playSlowBeep() {
          // Beep ini berulang tiap 3 detik selama status lambat. Dipendekkan
          // supaya tidak menumpuk dengan suara hasil scan.
          if (typeof suaraScan === 'function') {
              suaraScan('audio-slow-alert', { batas: 500 });
              return;
          }

          var audio = document.getElementById('audio-slow-alert');
          if (audio) {
              audio.currentTime = 0;
              audio.play().catch(function(e) { console.log('Slow audio failed', e); });
          }
      }

      function stopSlowAudio() {
          if (slowAlertInterval) {
              clearInterval(slowAlertInterval);
              slowAlertInterval = null;
          }
          var audio = document.getElementById('audio-slow-alert');
          if (audio) { audio.pause(); audio.currentTime = 0; }
      }


  });
</script>
