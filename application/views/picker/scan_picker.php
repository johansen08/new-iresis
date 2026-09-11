<div class="row" id="scan-picker-container">
  <div class="col-md-5" id="panel-kiri">
    <form action="picker/save-scan-picker" class="form-horizontal" id="form_scan_picker" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title" style="display:inline-block;"><strong>Scan Picker</strong></h3>
          <div class="pull-right">
            <button type="button" class="btn btn-warning" id="btn-show-logs"><i class="fa fa-history"></i> Log Summary</button>
            <button type="button" class="btn btn-danger" id="btn-toggle-summary"><i class="fa fa-close"></i> Tutup Summary</button>
          </div>
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
          <button type="reset" class="btn btn-primary pull-right">Reset Form</button>
        </div>
      </div>
    </form>
  </div>

  <div class="col-md-7" id="panel-kanan">
    <div class="panel panel-info">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Summary Pengambilan Barang</strong></h3>
        <ul class="panel-controls">
           <li><button class="btn btn-warning btn-sm" id="btn-save-log-manual" style="margin-right: 5px;" title="Simpan ke Riwayat"><i class="fa fa-save"></i> Simpan Log</button></li>
           <li><button class="btn btn-danger btn-sm" id="btn-reset-summary" style="margin-right: 5px;">Reset Summary</button></li>
           <li><button class="btn btn-success btn-sm" id="btn-print-summary">Print Thermal</button></li>
        </ul>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" role="alert">
            Total Resi di-scan di sesi ini: <strong id="session-resi-count">0</strong>
        </div>
        <div id="summary-content-area" style="margin-top: 15px;">
          <!-- Data Summary akan muncul di sini secara dinamis -->
          <div class="text-center" style="padding: 40px; color: #999; border: 2px dashed #eee; border-radius: 8px;">
            <i class="fa fa-barcode" style="font-size: 40px; margin-bottom: 10px;"></i>
            <p>Belum ada data resi yang di-scan untuk sesi ini.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Print Area (Hidden) -->
<div id="print-area" style="display:none;">
    <div style="width: 100mm; font-family: monospace; font-size: 14px; padding: 5px;">
        <h3 style="text-align: center; margin: 0 0 10px 0;">PICKING SUMMARY</h3>
        <p style="margin: 0;">Picker : <span id="print-picker-name"></span></p>
        <p style="margin: 0;">Waktu  : <span id="print-time"></span></p>
        <p style="margin: 0; margin-bottom: 10px;">Total Resi: <span id="print-resi-count">0</span></p>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 1px solid #000; border-top: 1px solid #000;">
                    <th style="text-align: left; padding: 5px 0;">Rak</th>
                    <th style="text-align: left; padding: 5px 0;">SKU</th>
                    <th style="text-align: right; padding: 5px 0;">Qty</th>
                </tr>
            </thead>
            <tbody id="print-tbody">
            </tbody>
        </table>
        <p style="text-align: center;">--- END OF SUMMARY ---</p>
    </div>
</div>

<script type="text/javascript">
  $("#noresi").focus();
  var total_scan = document.getElementById('total_scan');
  
  // State Summary
  var isSummaryMode = true; // Default ON
  var summaryData = {};
  var sessionResiCount = 0;

  // Toggle Summary Mode
  $('#btn-toggle-summary').on('click', function() {
      isSummaryMode = !isSummaryMode;
      if (isSummaryMode) {
          $('#panel-kiri').removeClass('col-md-6 center-block float-none').addClass('col-md-5');
          $('#panel-kanan').show();
          $(this).html('<i class="fa fa-close"></i> Tutup Summary');
          $(this).removeClass('btn-info').addClass('btn-danger');
      } else {
          $('#panel-kanan').hide();
          $('#panel-kiri').removeClass('col-md-5').addClass('col-md-6 center-block float-none');
          $(this).html('<i class="fa fa-list"></i> Tampilkan Summary');
          $(this).removeClass('btn-danger').addClass('btn-info');
      }
  });

  // REQUEST QUEUE untuk handle scan cepat
  var requestQueue = [];
  var isProcessing = false;

  // Semua suara di halaman ini lewat sini, jangan panggil .play() langsung.
  // Dua alasan, keduanya bikin scan terasa lambat:
  //   - suaracancel.mp3 ~4,6 detik dan suaradouble.mp3 ~6,4 detik. Diputar
  //     utuh, satu resi bermasalah menahan antrean scan berdetik-detik.
  //   - tanpa reset currentTime, play() pada elemen yang masih berbunyi tidak
  //     melakukan apa-apa, jadi scan berikutnya terdengar senyap.
  // suaraScan (main.php) menangani dua-duanya.
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

  function updateSummaryTable() {
    var $container = $('#summary-content-area');
    $container.empty();
    
    var items = [];
    Object.keys(summaryData).forEach(function(rak) {
        Object.keys(summaryData[rak]).forEach(function(sku) {
            var item = summaryData[rak][sku];
            items.push({
                rak: rak,
                sku: sku,
                nama_sku: item.nama_sku,
                qty: item.qty
            });
        });
    });

    if (items.length === 0) {
        $container.append('<div class="text-center" style="padding: 40px; color: #999; border: 2px dashed #eee; border-radius: 8px;"><i class="fa fa-barcode" style="font-size: 40px; margin-bottom: 10px;"></i><p>Belum ada data resi yang di-scan untuk sesi ini.</p></div>');
        $('.alert-info[role="alert"]').html('Total Resi di-scan di sesi ini: <strong>0</strong>');
        return;
    }

    // Sort by No Rak (asc) and Qty (desc)
    items.sort(function(a, b) {
        if (a.rak < b.rak) return -1;
        if (a.rak > b.rak) return 1;
        return b.qty - a.qty;
    });

    var html = '<div style="max-height: 500px; overflow-y: auto;"><table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">' +
               '<thead><tr style="background: #f8f9fa; color: #333;">' +
               '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; width: 20%;">No Rak</th>' +
               '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; width: 60%;">SKU / Nama Barang</th>' +
               '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; width: 20%; text-align: center;">Qty</th>' +
               '</tr></thead><tbody>';

    items.forEach(function(item) {
        var displayRak = item.rak === 'NO_RAK' ? '-' : item.rak;
        html += '<tr style="background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: transform 0.2s;">' +
                '<td style="padding: 15px 12px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; border-left: 1px solid #eee; border-top-left-radius: 8px; border-bottom-left-radius: 8px; font-weight: bold; color: #2c3e50;">' + displayRak + '</td>' +
                '<td style="padding: 15px 12px; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">' +
                    '<span style="font-weight: 700; color: #1a1a1a; font-size: 14px;">' + item.sku + '</span><br>' +
                    '<span style="color: #666; font-size: 12px;">' + (item.nama_sku || '-') + '</span>' +
                '</td>' +
                '<td style="padding: 15px 12px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; border-right: 1px solid #eee; border-top-right-radius: 8px; border-bottom-right-radius: 8px; text-align: center;">' +
                    '<span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 16px;">' + item.qty + '</span>' +
                '</td>' +
                '</tr>';
    });
    html += '</tbody></table></div>';
    $container.append(html);

    // Update Blue Alert with details
    var totalQty = items.reduce(function(sum, item) { return sum + item.qty; }, 0);
    var skuCount = items.length;
    var alertText = 'Total Resi di-scan di sesi ini: <strong>' + sessionResiCount + '</strong> | Items: <strong>' + totalQty + '</strong> (' + skuCount + ' SKU)';
    $('.alert-info[role="alert"]').html(alertText);
  }

  function resetSummary() {
      summaryData = {};
      sessionResiCount = 0;
      updateSummaryTable();
  }

  $('#btn-reset-summary').on('click', function(e) {
      e.preventDefault();
      if (confirm('Apakah Anda yakin ingin mereset/menghapus summary saat ini?')) {
          resetSummary();
      }
  });

  $('#btn-print-summary').on('click', function() {
      if (sessionResiCount === 0) {
          alert('Belum ada resi yang di-scan pada sesi ini.');
          return;
      }
      
      // Save to Log automatically before print
      saveToHistoryLog();

      // Populate print area
      var pickerName = $('#id_pegawaipicker option:selected').text();
      $('#print-picker-name').text(pickerName);
      
      var now = new Date();
      $('#print-time').text(now.toLocaleString());
      $('#print-resi-count').text(sessionResiCount);

      var $printTbody = $('#print-tbody');
      $printTbody.empty();

      // Convert summaryData to array for sorting
      var items = [];
      Object.keys(summaryData).forEach(function(rak) {
          Object.keys(summaryData[rak]).forEach(function(sku) {
              var item = summaryData[rak][sku];
              items.push({
                  rak: rak,
                  sku: sku,
                  qty: item.qty
              });
          });
      });

      // Sort same as table
      items.sort(function(a, b) {
          if (a.rak < b.rak) return -1;
          if (a.rak > b.rak) return 1;
          return b.qty - a.qty;
      });

      items.forEach(function(item) {
          var displayRak = item.rak === 'NO_RAK' ? '-' : item.rak;
          var tr = '<tr style="border-bottom: 1px dashed #ccc;">' +
              '<td style="padding: 5px 0;">' + displayRak + '</td>' +
              '<td style="padding: 5px 0;">' + item.sku + '</td>' +
              '<td style="text-align: right; padding: 5px 0; font-weight: bold;">' + item.qty + '</td>' +
          '</tr>';
          $printTbody.append(tr);
      });

      var printContents = document.getElementById('print-area').innerHTML;
      
      var printWindow = window.open('', '_blank', 'width=400,height=600');
      printWindow.document.write('<html><head><title>Print Summary</title>');
      printWindow.document.write('<style>@page { margin: 0; size: 100mm 150mm; } body { margin: 0; padding: 10px; background-color: #fff; }</style>');
      printWindow.document.write('</head><body>');
      printWindow.document.write(printContents);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      
      printWindow.onload = function() {
          printWindow.focus();
          printWindow.print();
      };
  });

  // History Log Functions
  var lastSavedSummaryStr = "";

  function saveToHistoryLog() {
      if (sessionResiCount === 0) return;
      
      var currentDataStr = JSON.stringify(summaryData);
      
      // Cegah double save jika data sama dengan yang terakhir
      if (lastSavedSummaryStr === currentDataStr) {
          console.log('Data summary sama, skip simpan ke database.');
          return;
      }
      
      var pickerId = $('#id_pegawaipicker').val();
      
      // Hitung total Qty & SKU
      var totalQty = 0;
      var skuCount = 0;
      Object.keys(summaryData).forEach(function(rak) {
          Object.keys(summaryData[rak]).forEach(function(sku) {
              totalQty += summaryData[rak][sku].qty;
              skuCount++;
          });
      });
      
      $.ajax({
          url: 'picker/save_summary_log',
          type: 'post',
          data: {
              picker_id: pickerId,
              total_resi: sessionResiCount,
              total_qty: totalQty,
              sku_count: skuCount,
              summary_data: currentDataStr
          },
          success: function(response) {
              if (response.code === 201) {
                  lastSavedSummaryStr = currentDataStr;
                  console.log('Log berhasil disimpan ke database (Shared)');
                  // Tampilkan notifikasi kecil
                  if (typeof noty !== 'undefined') {
                      noty({text: 'Berhasil menyimpan log ke database terpusat', layout: 'topRight', type: 'success', timeout: 2000});
                  }
              }
          }
      });
  }

  // Timer Inactivity & Auto Save
  var inactivityTimer;
  var autoSaveTimer;
  
  function resetInactivityTimer() {
      clearTimeout(inactivityTimer);
      inactivityTimer = setTimeout(function() {
          if (sessionResiCount > 0) {
              saveToHistoryLog();
              resetSummary();
              alert('Summary otomatis di-reset karena tidak ada aktivitas selama 5 menit. Data sudah disimpan ke Log Summary.');
          }
      }, 5 * 60 * 1000); // 5 Menit
      
      // Auto save log setiap 1 menit inaktivitas (tanpa reset summary)
      clearTimeout(autoSaveTimer);
      autoSaveTimer = setTimeout(function() {
          if (sessionResiCount > 0) {
              saveToHistoryLog();
              console.log('Auto-saved current session to log');
          }
      }, 60 * 1000); // 1 Menit
  }

  function fetchSummaryFromServer() {
      // Fungsi ini dihapus agar tidak menarik data "yang sebelumnya"
      // Summary murni per sesi scan sekarang
      return;
  }

  // Function untuk process queue
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
    var pickerId = $(this).val();
    $("#noresi").focus();
    
    // Save current session before switching if needed
    if (sessionResiCount > 0) {
        saveToHistoryLog();
    }
    
    // Fetch summary for the new picker for today
    if (pickerId) {
        $.ajax({
            url: 'picker/get_summary_by_picker',
            type: 'post',
            data: { id_picker: pickerId },
            success: function(response) {
                if (response.code === 200) {
                    summaryData = {};
                    sessionResiCount = response.total_resi || 0;
                    
                    if (response.items && response.items.length > 0) {
                        response.items.forEach(function(item) {
                            var rak = item.no_rak || 'NO_RAK';
                            var sku = item.sku;
                            if (!summaryData[rak]) summaryData[rak] = {};
                            summaryData[rak][sku] = {
                                qty: parseInt(item.total_qty),
                                nama_sku: item.nama_sku
                            };
                        });
                    }
                    updateSummaryTable();
                }
            }
        });
    } else {
        resetSummary();
    }
  });

  // Initial setup
  $(document).ready(function() {
      resetInactivityTimer(); // Start timer
  });

  $('#btn-show-logs').on('click', function() {
      $.ajax({
          url: 'picker/get_summary_logs',
          type: 'get',
          success: function(response) {
              if (response.code === 200) {
                  var logs = response.logs;
                  if (logs.length === 0) {
                      alert('Belum ada riwayat summary terpusat dalam 24 jam terakhir.');
                      return;
                  }
                  
                  var html = '<div style="max-height: 400px; overflow-y: auto;"><table class="table table-condensed table-bordered">';
                  html += '<thead><tr><th>Waktu</th><th>Admin</th><th>Picker</th><th>Summary</th><th>Aksi</th></tr></thead><tbody>';
                  
                  logs.forEach(function(log, index) {
                      var date = new Date(log.timestamp).toLocaleString();
                      var summaryTxt = '<strong>' + log.total_resi + '</strong> Resi<br><small>' + (log.total_qty || 0) + ' Item (' + (log.sku_count || 0) + ' SKU)</small>';
                      html += '<tr>' +
                              '<td>' + date + '</td>' +
                              '<td><small>' + log.admin_name + '</small></td>' +
                              '<td>' + log.picker_name + '</td>' +
                              '<td>' + summaryTxt + '</td>' +
                              '<td><button class="btn btn-xs btn-info btn-view-log-db" data-index="' + index + '">Lihat</button></td>' +
                              '</tr>';
                  });
                  html += '</tbody></table></div>';
                  
                  var $modal = $('<div class="modal fade" tabindex="-1" role="dialog">' +
                      '<div class="modal-dialog" role="document">' +
                        '<div class="modal-content">' +
                          '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Riwayat Summary Terpusat (24 Jam)</h4></div>' +
                          '<div class="modal-body">' + html + '</div>' +
                          '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>' +
                        '</div>' +
                      '</div>' +
                    '</div>');
                  
                  $modal.modal('show');
                  
                  $modal.on('click', '.btn-view-log-db', function() {
                      var idx = $(this).data('index');
                      var log = logs[idx];
                      try {
                          summaryData = JSON.parse(log.summary_data);
                          sessionResiCount = log.total_resi;
                          updateSummaryTable();
                          $modal.modal('hide');
                          alert('Berhasil memuat data summary dari: ' + log.picker_name + ' (oleh ' + log.admin_name + ')');
                      } catch(e) {
                          alert('Gagal memuat data detail log.');
                      }
                  });
              }
          }
      });
  });

  $('#btn-save-log-manual').on('click', function() {
      if (sessionResiCount === 0) {
          alert('Belum ada data untuk disimpan.');
          return;
      }
      saveToHistoryLog();
      alert('Summary berhasil disimpan ke Riwayat (Log).');
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

      // Tambahkan ke queue
      requestQueue.push({
        url: form.action,
        data: Object.fromEntries(formData),
        noresiValue: noresiValue,
        success: function(data) {
          // Parse JSON if needed
          if (typeof data === 'string') {
             try { data = JSON.parse(data); } catch(e) {}
          }
          
          if (data && (data.code === 200 || data.code === 201)) {
            // Success feedback
            $("#div_container_latest_receipt").removeClass("tile-danger tile-default").addClass("tile-success");
            $("#span_latest_receipt").text(noresiValue);
            $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan Picker");
  
            // Play success sound (Alexis)
            playAudio('audio-alexis');

            // UPDATE SUMMARY STATE if available
            console.log('Scan Response Data:', data);
            
            if (data.items && data.items.length > 0) {
                sessionResiCount++;
                data.items.forEach(function(item) {
                    var rak = item.no_rak || 'NO_RAK';
                    var sku = item.sku;
                    var qty = parseInt(item.jumlah) || 1;

                    if (!summaryData[rak]) summaryData[rak] = {};
                    if (!summaryData[rak][sku]) {
                        summaryData[rak][sku] = {
                            qty: 0,
                            nama_sku: item.nama_sku
                        };
                    }
                    summaryData[rak][sku].qty += qty;
                    summaryData[rak][sku].last_updated = Date.now();
                });
                
                // Reset timer activity setiap ada scan sukses
                resetInactivityTimer();

                updateSummaryTable();
            } else {
                console.warn('No items returned for resi:', noresiValue);
            }
          } else {
            // Handle logical error from server
            var msg = data && data.message ? data.message : "Gagal memproses data";
            
            // Rollback counter on error
            total_scan.value = Number(total_scan.value) - 1;

            $("#span_latest_receipt").text(noresiValue);
            $("#div_container_latest_receipt").removeClass("tile-default tile-success").addClass("tile-danger");
            $("#p_latest_receipt_message").text(msg);

            // Play error sound (Wrong / Cancel / Double)
            var upperMsg = msg.toUpperCase();
            if (upperMsg.includes('CANCEL') || upperMsg.includes('BATAL')) {
              playAudio('audio-cancel');
            } else if (upperMsg.includes('SUDAH DI SCAN') || upperMsg.includes('SUDAH DI PICK')) {
              playAudio('audio-double');
            } else {
              playAudio('audio-wrong');
            }
          }
        },
        error: function(xhr, status, error) {
          // Error feedback (network or internal server error)
          var response = {};
          try {
            response = JSON.parse(xhr.responseText);
          } catch (e) {
            response.message = "Terjadi kesalahan pada server";
          }

          // Rollback counter on error
          total_scan.value = Number(total_scan.value) - 1;

          $("#span_latest_receipt").text(noresiValue);
          $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
          $("#p_latest_receipt_message").text(response.message);

          // Play error sound (Wrong / Cancel / Double)
          var upperErr = response.message ? response.message.toUpperCase() : "";
          if (upperErr.includes('CANCEL') || upperErr.includes('BATAL')) {
            playAudio('audio-cancel');
          } else if (upperErr.includes('SUDAH DI SCAN') || upperErr.includes('SUDAH DI PICK')) {
            playAudio('audio-double');
          } else {
            playAudio('audio-wrong');
          }
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