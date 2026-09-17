<div class="row">
  <div class="col-md-5">
    <form action="picker/save_scan_picker_summary" class="form-horizontal" id="form_scan_picker" autocomplete="off">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Picker Summary</strong></h3>
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
          <button type="button" id="btn-reset-form" class="btn btn-primary pull-right">Reset Form</button>
        </div>
      </div>
    </form>
  </div>

  <div class="col-md-7">
    <div class="panel panel-info">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Summary Pengambilan Barang</strong></h3>
        <ul class="panel-controls">
           <li><button class="btn btn-warning btn-sm" id="btn-reset-summary" style="margin-right: 10px;">Reset Summary</button></li>
           <li><button class="btn btn-success btn-sm" id="btn-print-summary">Print Thermal</button></li>
        </ul>
      </div>
      <div class="panel-body">
        <div class="alert alert-info" role="alert">
            Total Resi di-scan di sesi ini: <strong id="session-resi-count">0</strong>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
          <table class="table table-bordered table-striped" id="table-summary">
            <thead>
              <tr>
                <th width="20%">No Rak</th>
                <th width="60%">SKU / Nama Barang</th>
                <th width="20%" style="text-align:center;">Total Qty</th>
              </tr>
            </thead>
            <tbody id="summary-tbody">
              <tr><td colspan="3" class="text-center">Belum ada data resi yang di-scan.</td></tr>
            </tbody>
          </table>
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
  var summaryData = {};
  var sessionResiCount = 0;

  // REQUEST QUEUE untuk handle scan cepat
  var requestQueue = [];
  var isProcessing = false;

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

  function updateSummaryTable() {
    var $tbody = $('#summary-tbody');
    $tbody.empty();
    
    var rakKeys = Object.keys(summaryData);
    if (rakKeys.length === 0) {
        $tbody.append('<tr><td colspan="3" class="text-center">Belum ada data resi yang di-scan.</td></tr>');
        return;
    }

    // Sort by No Rak
    rakKeys.sort();

    rakKeys.forEach(function(rak) {
        var skus = summaryData[rak];
        var skuKeys = Object.keys(skus);
        skuKeys.sort();
        
        skuKeys.forEach(function(sku) {
            var item = skus[sku];
            var displayRak = rak === 'NO_RAK' ? '-' : rak;
            
            var tr = '<tr>' +
                '<td>' + displayRak + '</td>' +
                '<td><strong>' + sku + '</strong><br><small>' + (item.nama_sku || '') + '</small></td>' +
                '<td style="text-align:center; font-size: 16px; font-weight: bold;">' + item.qty + '</td>' +
            '</tr>';
            $tbody.append(tr);
        });
    });

    $('#session-resi-count').text(sessionResiCount);
  }

  function resetSummary() {
      summaryData = {};
      sessionResiCount = 0;
      updateSummaryTable();
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
      dataType: 'json',
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
    $("#noresi").focus();
    // Jika ganti picker, tanya apakah mau reset summary
    if (sessionResiCount > 0) {
        if (confirm("Anda mengubah nama Picker. Apakah Anda ingin mereset Summary saat ini?")) {
            resetSummary();
        }
    }
  });

  $('#btn-reset-summary').on('click', function(e) {
      e.preventDefault();
      if (confirm('Apakah Anda yakin ingin mereset/menghapus summary saat ini?')) {
          resetSummary();
      }
  });

  $('#btn-reset-form').on('click', function() {
      $('#form_scan_picker')[0].reset();
      $('#id_pegawaipicker').selectpicker('refresh');
      $('#status_performa').selectpicker('refresh');
      $("#noresi").focus();
  });

  $('#btn-print-summary').on('click', function() {
      if (sessionResiCount === 0) {
          alert('Belum ada resi yang di-scan pada sesi ini.');
          return;
      }

      // Populate print area
      var pickerName = $('#id_pegawaipicker option:selected').text();
      $('#print-picker-name').text(pickerName);
      
      var now = new Date();
      $('#print-time').text(now.toLocaleString());
      $('#print-resi-count').text(sessionResiCount);

      var $printTbody = $('#print-tbody');
      $printTbody.empty();

      var rakKeys = Object.keys(summaryData);
      rakKeys.sort();

      rakKeys.forEach(function(rak) {
          var skus = summaryData[rak];
          var skuKeys = Object.keys(skus);
          skuKeys.sort();
          
          skuKeys.forEach(function(sku) {
              var item = skus[sku];
              var displayRak = rak === 'NO_RAK' ? '-' : rak;
              
              var tr = '<tr style="border-bottom: 1px dashed #ccc;">' +
                  '<td style="padding: 5px 0;">' + displayRak + '</td>' +
                  '<td style="padding: 5px 0;">' + sku + '</td>' +
                  '<td style="text-align: right; padding: 5px 0; font-weight: bold;">' + item.qty + '</td>' +
              '</tr>';
              $printTbody.append(tr);
          });
      });

      // Buka window baru untuk print khusus area thermal
      var printContents = document.getElementById('print-area').innerHTML;
      var originalContents = document.body.innerHTML;

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
          // printWindow.close(); // Optional: close immediately after print dialogue
      };

      // Opsional: Tanya apakah mau reset setelah print
      // if (confirm('Apakah Anda ingin mereset summary setelah mencetak?')) {
      //     resetSummary();
      // }
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
          if (data && (data.code === 200 || data.code === 201)) {
            // Success feedback
            $("#div_container_latest_receipt").removeClass("tile-danger").addClass("tile-default");
            $("#span_latest_receipt").text(noresiValue);
            $("#p_latest_receipt_message").text("Nomor resi terakhir yang sudah di-scan Picker");
  
            // Play success sound (Alexis)
            playAudio('audio-alexis');

            // UPDATE SUMMARY STATE
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
                });
                updateSummaryTable();
            }

          } else {
            // Handle logical error from server
            var msg = data && data.message ? data.message : "Gagal memproses data";
            
            // Rollback counter on error
            total_scan.value = Number(total_scan.value) - 1;

            $("#span_latest_receipt").text(noresiValue);
            $("#div_container_latest_receipt").removeClass("tile-default").addClass("tile-danger");
            $("#p_latest_receipt_message").text(msg);

            playScanErrorAudio(msg, (data && data.data) ? data.data.EXCEPTION_CODE : '');
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

          playScanErrorAudio(response.message, (response && response.data) ? response.data.EXCEPTION_CODE : '');
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
