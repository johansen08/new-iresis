<script>
  // Anti-duplication: Hilangkan area print lama jika ada sebelum render yang baru
  $('#print-area-cs').remove();
</script>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Daftar Masalah Picker</strong></h3>
      </div>

      <div class="panel-body">
        <form action="cs/masalah-picker" class="form-horizontal" method="post" id="form-masalah-picker">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
            <div class="col-md-3 col-xs-12">
              <input type="text" name="reportrange" id="reportrange" class="form-control"
                value="<?= !empty($reportrange) ? $reportrange : null ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label"></label>
            <div class="col-md-2 col-xs-12">
              <button type="submit" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Cari</button>
            </div>
          </div>
        </form>

        <hr>

        <div class="form-group" id="button-group" style="display: none;">
          <div class="col-md-12">
            <button type="button" class="btn btn-warning" id="btn-proses-kurangan-cs"
              style="font-weight: bold; color: #333;">
              <i class="fa fa-refresh"></i> PROSES KURANGAN
            </button>
            <button type="button" id="btn-select-all" class="btn btn-info">
              <i class="fa fa-check-square"></i> Select All
            </button>
          </div>
        </div>

        <table class="table table-striped table-bordered" id="datatable-masalah-picker">
          <thead>
            <tr>
              <th>#</th>
              <th>No. Resi</th>
              <th>SKU</th>
              <th>SKU Salah</th>
              <th>Nama Barang</th>
              <th>Qty</th>
              <th>Qty Bermasalah</th>
              <th>Tipe Masalah</th>
              <th>Picker</th>
              <th>Packer / Pelapor</th>
              <th>Tanggal</th>
              <th style="width: 50px;">Check</th>
              <th style="width: 100px;">Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Masalah Picker -->
<div id="modalDetailMasalah" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Detail Masalah Picker</h4>
      </div>
      <div class="modal-body" id="modalDetailContent">
        <p>Memuat data...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL PROSES KURANGAN (TIM CS) -->
<div id="modalProsesKuranganCS" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #f5f5f5;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title text-center"><strong>PROSES KURANGAN</strong></h4>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="table-proses-kurangan-data">
            <thead>
              <tr style="background-color: #f9f9f9;">
                <th class="text-center">SKU</th>
                <th class="text-center">QTY</th>
                <th class="text-center">No. Rak</th>
              </tr>
            </thead>
            <tbody>
              <!-- Populated via JS (Max 5 rows) -->
            </tbody>
          </table>
        </div>

        <div class="form-horizontal" style="margin-top: 20px;">
          <div class="form-group">
            <label class="col-md-3 control-label">Penerima (Picker)</label>
            <div class="col-md-7">
              <select id="sel_penerima_cs" class="form-control selectpicker" data-live-search="true">
                <option value="">-- Pilih Picker --</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-3 control-label">Pengambil (Packer)</label>
            <div class="col-md-7">
              <select id="sel_pengambil_cs" class="form-control selectpicker" data-live-search="true">
                <option value="">-- Pilih Packer --</option>
              </select>
            </div>
          </div>
        </div>

        <div class="text-center" style="margin-top: 30px;">
          <button type="button" class="btn btn-primary btn-lg" id="btn-submit-kurangan-cs"
            style="min-width: 150px; font-weight: bold;">
            SUBMIT
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- AREA PRINT (Hidden) -->
<div id="print-area-cs" style="display: none;"></div>

<script>
  var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;

  var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
  var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment();

  $('#reportrange').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: start,
    endDate: end,
    ranges: {
      'Today': [moment().startOf('day'), moment()],
      'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
      'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
    },
    locale: {
      format: 'YYYY-MM-DD HH:mm:ss'
    },
  });

  // Auto refresh when date range changes
  $('#reportrange').on('apply.daterangepicker', function (ev, picker) {
    table.ajax.reload(null, false);
  });

  // Inisialisasi DataTable - auto load data hari ini
  var table = $('#datatable-masalah-picker').DataTable({
    'scrollX': true,
    'pageLength': 10,
    'processing': true,
    'language': {
      'processing': 'Memproses data...'
    },
    'serverSide': true,
    'order': [[10, 'desc']],
    'drawCallback': function () {
      // Prevent duplicate print areas in DOM
      if ($('#print-area-cs').length > 1) {
        $('#print-area-cs').not(':last').remove();
      }
    },
    'lengthMenu': [
      [10, 50, 100, 150, 200],
      [10, 50, 100, 150, 200]
    ],
    'ajax': {
      url: 'cs/get-masalah-picker-data',
      type: 'POST',
      data: function (d) {
        d.reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s') ?>';
      }
    },
    'columnDefs': [
      { className: 'text-center', targets: [0, 5, 6, 11, 12] },
      { orderable: false, targets: [11, 12] }
    ]
  });

  // Show button group when table is loaded
  $('#button-group').show();

  // Handle checkbox select all
  $('#btn-select-all').on('click', function() {
    var isChecked = $(this).data('checked') || false;
    $('.row-select').prop('checked', !isChecked);
    $(this).data('checked', !isChecked);
    $(this).html(isChecked ? '<i class="fa fa-check-square"></i> Select All' : '<i class="fa fa-square"></i> Deselect All');
  });

  // Handle detail button
  $(document).on('click', '.btn-detail-masalah', function () {
    var id = $(this).data('id');

    $.ajax({
      url: 'cs/get-detail-masalah-picker',
      method: 'POST',
      data: { id: id },
      dataType: 'json',
      success: function (response) {
        if (response && response.success) {
          var html = '<table class="table table-bordered">';
          html += '<tr><th width="30%">No. Resi</th><td>' + (response.data.noresi || '-') + '</td></tr>';
          html += '<tr><th>SKU</th><td>' + (response.data.sku || '-') + '</td></tr>';
          html += '<tr><th>Nama Barang</th><td>' + (response.data.nama_barang || '-') + '</td></tr>';
          html += '<tr><th>Quantity</th><td>' + (response.data.qty || 0) + '</td></tr>';
          html += '<tr><th>Quantity Bermasalah</th><td>' + (response.data.qty_bermasalah || 0) + '</td></tr>';
          html += '<tr><th>Tipe Masalah</th><td>' + (response.data.type_masalah || '-') + '</td></tr>';
          html += '<tr><th>SKU Salah</th><td>' + (response.data.sku_salah || '-') + '</td></tr>';
          html += '<tr><th>Picker</th><td>' + (response.data.nama_picker || '-') + '</td></tr>';
          html += '<tr><th>Packer / Pelapor</th><td>' + (response.data.nama_packer || '-') + '</td></tr>';
          html += '<tr><th>Tanggal Dibuat</th><td>' + (response.data.created || '-') + '</td></tr>';
          if (response.data.updated) {
            html += '<tr><th>Tanggal Diupdate</th><td>' + response.data.updated + '</td></tr>';
          }
          html += '</table>';
          $('#modalDetailContent').html(html);
          $('#modalDetailMasalah').modal('show');
        } else {
          alert('Gagal memuat detail data');
        }
      },
      error: function () {
        alert('Terjadi kesalahan saat memuat detail data');
      }
    });
  });

  $('#btn-search').on('click', function () {
    $('#form-masalah-picker').removeAttr("target");
    $('#form-masalah-picker').removeClass('nojs');
    $('#form-masalah-picker').attr('action', 'cs/masalah-picker');
  });

  // --- LOGIC PROSES KURANGAN CS ---
  var currentKuranganData = [];

  $('#btn-proses-kurangan-cs').on('click', function () {
    // Show loading state and modal immediately
    $('#table-proses-kurangan-data tbody').html('<tr><td colspan="3" class="text-center">Memuat data...</td></tr>');
    $('#modalProsesKuranganCS').modal('show');

    // Check for selected rows
    var selectedIds = [];
    $('.row-select:checked').each(function () {
      var id = $(this).data('id-detail'); // Using data attribute directly from checkbox
      if (id) selectedIds.push(id);
    });

    // Ambil data terbaru berdasarkan filter atau pilihan checkbox
    $.ajax({
      url: 'cs/get-kurangan-preview-data',
      type: 'POST',
      data: {
        reportrange: $('#reportrange').val(),
        selected_ids: selectedIds
      },
      dataType: 'json',
      success: function (res) {
        if (res.data && res.data.length > 0) {
          currentKuranganData = res.data;
          var html = '';
          $.each(res.data, function (i, item) {
            html += '<tr>';
            html += '<td class="text-center">' + item.sku + '</td>';
            html += '<td class="text-center">' + item.qty_bermasalah + '</td>';
            html += '<td class="text-center">' + (item.no_rak || '-') + '</td>';
            html += '</tr>';
          });
          $('#table-proses-kurangan-data tbody').html(html);

          // 2. Load Picker/Packer list if needed
          loadPickerPackerLists();
        } else {
          $('#modalProsesKuranganCS').modal('hide');
          alert('Tidak ada data masalah picker dalam rentang waktu ini untuk diproses.');
        }
      },
      error: function () {
        $('#modalProsesKuranganCS').modal('hide');
        alert('Gagal mengambil data preview kurangan. Silakan coba lagi.');
      }
    });
  });

  function loadPickerPackerLists() {
    if ($('#sel_penerima_cs option').length <= 1) {
      $.ajax({
        url: 'cs/get-user-list-kurangan',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
          var options = '<option value="">-- Pilih User --</option>';
          $.each(data, function (index, item) {
            options += '<option value="' + item.id_user + '">' + item.name + '</option>';
          });
          $('#sel_penerima_cs, #sel_pengambil_cs').html(options);
          $('.selectpicker').selectpicker('refresh');
        }
      });
    }
  }

  $('#btn-submit-kurangan-cs').on('click', function () {
    var penerima = $('#sel_penerima_cs').val();
    var pengambil = $('#sel_pengambil_cs').val();
    var penerima_name = $('#sel_penerima_cs option:selected').text();
    var pengambil_name = $('#sel_pengambil_cs option:selected').text();

    if (!penerima || !pengambil) {
      alert('Silakan pilih Penerima dan Pengambil terlebih dahulu');
      return;
    }

    // Submit ke server
    $.ajax({
      url: 'cs/save-proses-kurangan',
      type: 'POST',
      data: {
        penerima_id: penerima,
        pengambil_id: pengambil,
        items: currentKuranganData
      },
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          // Anti-duplication: Hapus area print lama dari manapun
          $('#print-area-cs').remove();

          // Siapkan Print Container baru
          var htmlPrint = '<div id="print-area-cs">';
          var chunkSize = 5;
          var now = moment().format('DD/MM/YYYY HH:mm:ss');

          for (var i = 0; i < currentKuranganData.length; i += chunkSize) {
            var chunk = currentKuranganData.slice(i, i + chunkSize);
            htmlPrint += '<div class="print-page">';
            htmlPrint += '<h2>LAPORAN PROSES KURANGAN</h2>';
            htmlPrint += '<p><strong>Penerima:</strong> ' + penerima_name + '</p>';
            htmlPrint += '<p><strong>Pengambil:</strong> ' + pengambil_name + '</p>';
            htmlPrint += '<p><strong>Tanggal:</strong> ' + now + '</p>';

            htmlPrint += '<table>';
            htmlPrint += '<thead><tr><th style="width:45%">SKU</th><th style="width:20%">QTY</th><th style="width:35%">No. Rak</th></tr></thead>';
            htmlPrint += '<tbody>';
            $.each(chunk, function (j, item) {
              htmlPrint += '<tr>';
              htmlPrint += '<td>' + item.sku + '</td>';
              htmlPrint += '<td>' + item.qty_bermasalah + '</td>';
              htmlPrint += '<td>' + (item.no_rak || '-') + '</td>';
              htmlPrint += '</tr>';
            });
            htmlPrint += '</tbody></table>';
            htmlPrint += '</div>';
          }
          htmlPrint += '</div>';

          // Masukkan langsung ke body agar tidak terpengaruh CSS container aplikasi
          $('body').append(htmlPrint);

          $('#modalProsesKuranganCS').modal('hide');

          // Jalankan Print
          setTimeout(function () {
            window.print();

            // Reload table only, don't reload page
            if (typeof table !== 'undefined') {
              table.ajax.reload(null, false);
            }

            // Show notification
            if (typeof noty !== 'undefined') {
              noty({
                text: 'Berhasil diproses!',
                layout: 'topRight',
                type: 'success',
                timeout: 3000
              });
            } else {
              alert('Berhasil diproses!');
            }
          }, 500);
        } else {
          alert('Gagal menyimpan data kurangan');
        }
      }
    });
  });
</script>

<style>
  /* PRINT STYLES */
  @media print {
    @page {
      size: 100mm 150mm;
      margin: 0;
    }

    html,
    body {
      height: auto !important;
      margin: 0 !important;
      padding: 0 !important;
      background: #fff !important;
      overflow: visible !important;
    }

    /* Hide UI */
    body>*:not(#print-area-cs) {
      display: none !important;
    }

    #print-area-cs {
      display: block !important;
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      width: 100mm !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .print-page {
      width: 100mm !important;
      height: 148mm !important;
      /* Slightly shorter to avoid overflow pages */
      padding: 10mm !important;
      margin: 0 !important;
      page-break-after: always !important;
      background-color: #fff !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
    }

    .print-page:last-child {
      page-break-after: avoid !important;
    }

    .print-page h2 {
      font-size: 16pt !important;
      margin: 0 0 15px 0 !important;
      text-align: center !important;
      font-weight: bold !important;
      text-transform: uppercase !important;
      border-bottom: 3px double #000 !important;
      padding-bottom: 5px !important;
    }

    .print-header-info {
      margin-bottom: 12px !important;
      font-size: 10pt !important;
      line-height: 1.4 !important;
    }

    .print-page p {
      margin: 2px 0 !important;
    }

    .print-page table {
      width: 100% !important;
      border-collapse: collapse !important;
      table-layout: fixed !important;
      margin-top: 5px !important;
    }

    .print-page table th,
    .print-page table td {
      border: 1px solid #000 !important;
      padding: 6px 4px !important;
      text-align: center !important;
      word-wrap: break-word !important;
      font-size: 9pt !important;
    }

    .print-page table th {
      background-color: #eee !important;
      -webkit-print-color-adjust: exact !important;
      font-weight: bold !important;
    }

    /* Column widths */
    .print-page table th:nth-child(1),
    .print-page table td:nth-child(1) {
      width: 50% !important;
      text-align: left !important;
    }

    .print-page table th:nth-child(2),
    .print-page table td:nth-child(2) {
      width: 15% !important;
    }

    .print-page table th:nth-child(3),
    .print-page table td:nth-child(3) {
      width: 35% !important;
    }
  }

  /* Browser spacings and UI fixes */
  #reportrange {
    background-color: #fff !important;
    cursor: pointer !important;
    color: #555 !important;
    border: 1px solid #ccc !important;
  }

  #reportrange:hover {
    border-color: #66afe9 !important;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6) !important;
  }
</style>