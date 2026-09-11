<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Daftar Kurangan Picker</strong></h3>
      </div>

      <div class="panel-body">
        <form action="cs/laporan-kurangan-picker" class="form-horizontal" method="post" id="form-laporan-kurangan-picker">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Rentang waktu</label>
            <div class="col-md-3 col-xs-12">
              <input type="text" name="reportrange" id="reportrange" class="form-control" value="<?= !empty($reportrange) ? $reportrange : null ?>" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label"></label>
            <div class="col-md-2 col-xs-12">
              <button type="submit" class="btn btn-info" id="btn-search"><i class="fa fa-search"></i> Cari</button>
              <button type="submit" class="btn btn-primary" id="btn-export-excel"><i class="fa fa-download"></i> Ekspor ke Excel</button>
            </div>
          </div>
        </form>

        <hr>

        <div class="form-group" id="button-group" style="display: none;">
          <div class="col-md-12">
            <button type="button" id="btn-submit-selected" class="btn btn-success">
              <i class="fa fa-check"></i> Submit Selected
            </button>
            <button type="button" id="btn-select-all" class="btn btn-info">
              <i class="fa fa-check-square"></i> Select All
            </button>
          </div>
        </div>

        <table class="table table-striped table-bordered" id="datatable-laporan-kurangan-picker">
          <thead>
            <tr>
              <th>#</th>
              <th>SKU</th>
              <th>No. Resi</th>
              <th>No. Pesanan</th>
              <th>Marketplace</th>
              <th>Tgl Cetak</th>
              <th>B. Akhir Kirim</th>
              <th>QTY Kurang</th>
              <th style="width: 50px;">Check</th>
              <th style="width: 100px;">Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Stock Ready -->
<div class="modal fade" id="modalStockReady" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="form-stock-ready">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          <h4 class="modal-title">Action: Stock Ready</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="sr_id_detail_resi" name="id_detail_resi">
          <input type="hidden" name="action_type" value="Stock Ready">
          <div class="form-group">
            <label>SKU Bermasalah</label>
            <input type="text" class="form-control" id="sr_sku" readonly>
          </div>
          <div class="form-group">
            <label>Notes (Barangnya masih ada dimana?)</label>
            <input type="text" class="form-control" name="notes" placeholder="Contoh: Rak B2" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Minta SJ -->
<div class="modal fade" id="modalMintaSj" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="form-minta-sj">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          <h4 class="modal-title">Action: Minta SJ</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="msj_id_detail_resi" name="id_detail_resi">
          <input type="hidden" id="msj_noresi" name="noresi">
          <input type="hidden" id="msj_sku_hidden" name="sku">
          <input type="hidden" name="action_type" value="Minta SJ">
          <div class="form-group">
            <label>SKU Bermasalah</label>
            <input type="text" class="form-control" id="msj_sku" disabled>
          </div>
          <div class="form-group">
            <label>TP Berapa?</label>
            <input type="text" class="form-control" name="notes" placeholder="Contoh: TP 3" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Submit Request SJ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Pergantian Barang -->
<div class="modal fade" id="modalPergantianBarang" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="form-pergantian-barang">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          <h4 class="modal-title">Action: Pergantian Barang</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="pb_id_detail_resi" name="id_detail_resi">
          <input type="hidden" id="pb_noresi" name="noresi">
          <input type="hidden" id="pb_qty" name="qty">
          <input type="hidden" name="action_type" value="Pergantian Barang">
          <div class="form-group">
            <label>SKU Lama (Bermasalah)</label>
            <input type="text" class="form-control" id="pb_sku" readonly>
          </div>
          <div class="form-group">
            <label>Ganti ke SKU (Auto Suggestion)</label>
            <!-- Menggunakan basic list (Datalist/Autocomplete API placeholder) -->
            <input type="text" class="form-control" name="new_sku" id="pb_new_sku" placeholder="Ketik Kode SKU Pengganti" autocomplete="on" required>
            <small class="text-muted">Ketik SKU spesifik sebagai pengganti barang ini.</small>
          </div>
          <div class="form-group">
            <label>Notes Opsional</label>
            <input type="text" class="form-control" name="notes" placeholder="Catatan CS...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-info">Submit & Print Resi Baru</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  var report_range = <?= !empty($reportrange) ? '"' . $reportrange . '"' : "null" ?>;

  var start = report_range !== null ? moment(report_range.split(" - ")[0]) : moment().startOf('day');
  var end = report_range !== null ? moment(report_range.split(" - ")[1]) : moment().endOf('day');

  $('#reportrange').daterangepicker({
    timePicker: true,
    timePicker24Hour: true,
    startDate: start,
    endDate: end,
    ranges: {
      'Today': [moment().startOf('day'), moment()],
      'Last 1 Hours': [moment().subtract(1, 'hours'), moment()],
      'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().startOf('day')],
      'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
    },
    locale: {
      format: 'YYYY-MM-DD HH:mm:ss'
    },
  });

  // Auto refresh when date range changes
  $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
    table.ajax.reload(null, false);
  });

  // Inisialisasi DataTable - auto load data hari ini
  var table = $('#datatable-laporan-kurangan-picker').DataTable({
    'scrollX': true,
    'pageLength': 10,
    'processing': true,
    'language': {
      'processing': 'Memproses data...'
    },
    'serverSide': true,
    'order': [[4, 'desc']],
    'lengthMenu': [
      [10, 50, 100, 150, 200],
      [10, 50, 100, 150, 200]
    ],
    'ajax': {
      url: 'cs/get-laporan-kurangan-picker-data',
      type: 'POST',
      data: function(d) {
        d.reportrange = $('#reportrange').val() || '<?= date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59') ?>';
      }
    },
    'rowCallback': function(row, data, index) {
      var noPesanan = data[3];
      if (noPesanan && noPesanan !== '-') {
          if (!window.colorMapKuranganPicker) {
              window.colorMapKuranganPicker = {};
              window.colorIndexKuranganPicker = 0;
              window.orderColorsKuranganPicker = [
                  '#ffcfcc', '#ccffcc', '#ffffcc', '#ccffff', 
                  '#ffccff', '#e6e6fa', '#ffe4b5', '#e0ffff', 
                  '#f5fffa', '#fff0f5', '#f0ffff', '#f5f5dc'
              ];
          }
          if (!window.colorMapKuranganPicker[noPesanan]) {
              window.colorMapKuranganPicker[noPesanan] = window.orderColorsKuranganPicker[window.colorIndexKuranganPicker % window.orderColorsKuranganPicker.length];
              window.colorIndexKuranganPicker++;
          }
          $(row).css('background-color', window.colorMapKuranganPicker[noPesanan]);
      }
    },
    'columnDefs': [
      { className: 'text-center', targets: [0, 7, 8, 9] },
      { orderable: false, targets: [8, 9] }
    ]
  });

  // Show button group when table is loaded
  $('#button-group').show();

  // Handle Action Dropdown (Stock Ready / Minta SJ / Pergantian Barang)
  $(document).on('click', '.btn-action-kurangan', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var action = $(this).data('action');
      var noresi = $(this).data('noresi');
      var sku = $(this).data('sku');
      var qty = $(this).data('qty');

      if (action === 'Stock Ready') {
          $('#sr_id_detail_resi').val(id);
          $('#sr_sku').val(sku);
          $('#modalStockReady').modal('show');
      } else if (action === 'Minta SJ') {
          $('#msj_id_detail_resi').val(id);
          $('#msj_noresi').val(noresi);
          $('#msj_sku').val(sku);
          $('#msj_sku_hidden').val(sku);
          $('#modalMintaSj').modal('show');
      } else if (action === 'Pergantian Barang') {
          $('#pb_id_detail_resi').val(id);
          $('#pb_noresi').val(noresi);
          $('#pb_sku').val(sku);
          $('#pb_qty').val(qty);
          $('#pb_new_sku').val('');
          $('#modalPergantianBarang').modal('show');
      }
  });

  function processActionKurangan(formId, modalId) {
      var $form = $('#' + formId);
      $form.on('submit', function(e) {
          e.preventDefault();
          var $btn = $(this).find('[type="submit"]');
          var originalText = $btn.text();
          $btn.prop('disabled', true).text('Processing...');

          var actionType = $form.find('input[name="action_type"]').val();
          var pbNoresi = $('#pb_noresi').val();
          var pbNewSku = $('#pb_new_sku').val();
          var pbQty = $('#pb_qty').val();

          $.ajax({
              url: 'cs/action-kurangan-picker',
              method: 'POST',
              data: $form.serialize(),
              dataType: 'json',
              success: function(response) {
                  if (response && response.code === 201) {
                      showNoty(response.message, 'success');
                      table.ajax.reload(null, false);
                      $('#' + modalId).modal('hide');
                      $form[0].reset();
                      
                      // Jika proses adalah Pergantian Barang, otomatis print
                      if (actionType === 'Pergantian Barang') {
                          window.open('<?= base_url() ?>receipt/print_pergantian_barang?noresi='+pbNoresi+'&sku='+encodeURIComponent(pbNewSku)+'&qty='+pbQty, '_blank', 'width=800,height=600');
                      }
                  } else {
                      showNoty(response.message || 'Gagal menyimpan.', 'error');
                  }
              },
              error: function() {
                  showNoty('Terjadi kesalahan koneksi server.', 'error');
              },
              complete: function() {
                  $btn.prop('disabled', false).text(originalText);
              }
          });
      });
  }

  processActionKurangan('form-stock-ready', 'modalStockReady');
  processActionKurangan('form-minta-sj', 'modalMintaSj');
  processActionKurangan('form-pergantian-barang', 'modalPergantianBarang');

  $('#btn-search').on('click', function() {
    $('#form-laporan-kurangan-picker').removeAttr("target");
    $('#form-laporan-kurangan-picker').removeClass('nojs');
    $('#form-laporan-kurangan-picker').attr('action', 'cs/laporan-kurangan-picker');
  });
  
  $('#btn-export-excel').on('click', function() {
    $('#form-laporan-kurangan-picker').attr("target", "_blank");
    $('#form-laporan-kurangan-picker').addClass('nojs');
    $('#form-laporan-kurangan-picker').attr('action', 'cs/export-excel-laporan-kurangan-picker');
  });

</script>

<script>
  var currentConfirmationNoty = null;

  function showConfirmation(message, onConfirm) {
    if (currentConfirmationNoty) {
      currentConfirmationNoty.close();
    }

    currentConfirmationNoty = noty({
      text: message,
      layout: 'center',
      modal: true,
      buttons: [
        {
          addClass: 'btn btn-success btn-clean',
          text: 'Ya',
          onClick: function($noty) {
            $noty.close();
            if (typeof onConfirm === 'function') {
              onConfirm();
            }
            currentConfirmationNoty = null;
          }
        },
        {
          addClass: 'btn btn-danger btn-clean',
          text: 'Batal',
          onClick: function($noty) {
            $noty.close();
            currentConfirmationNoty = null;
          }
        }
      ]
    });
  }

  function showNoty(message, type) {
    noty({
      text: message,
      layout: 'topRight',
      type: type || 'information',
      timeout: 3000
    });
  }
</script>

