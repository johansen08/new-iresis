<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Laporan Retur ke Display</strong></h3>
      </div>
      <div class="panel-body">
        
        <!-- Filter Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-horizontal">
              <div class="form-group" style="margin-bottom: 0;">
                <label class="col-md-2 control-label">Rentang Tanggal Kirim</label>
                <div class="col-md-3">
                  <input type="text" id="rwaktu_laporan" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                </div>
                <div class="col-md-7 text-right">
                  <button type="button" class="btn btn-primary" id="btn_tampilkan">
                    <i class="fa fa-search"></i> Tampilkan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr style="margin-top: 15px; margin-bottom: 15px;"/>

        <!-- Main Datatable -->
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover" id="dt_display_batches" style="width: 100%;">
                <thead>
                  <tr>
                    <th>Kode Batch</th>
                    <th>Tanggal Kirim</th>
                    <th>Dikirim Oleh</th>
                    <th>Total Qty</th>
                    <th>Status</th>
                    <th>Diterima Oleh</th>
                    <th>Tanggal Diterima</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Batch -->
<div class="modal fade" id="modal_detail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="modalDetailLabel">Detail Batch Display: <strong id="lbl_kode_batch"></strong></h4>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover" id="dt_batch_items" style="width: 100%;">
            <thead>
              <tr>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>No. Rak</th>
              </tr>
            </thead>
            <tbody id="body_batch_items">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';

    // ==================== DATERANGEPICKER INITIALIZATION ====================
    var drpLocale = {
        format: 'YYYY-MM-DD HH:mm',
        separator: ' s/d ',
        applyLabel: 'Terapkan', cancelLabel: 'Batal',
        fromLabel: 'Dari', toLabel: 'Sampai',
        customRangeLabel: 'Custom', weekLabel: 'W',
        daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
        monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
        firstDay: 1
    };
    var drpRanges = {
        'Hari Ini'        : [moment().startOf('day'), moment().endOf('day')],
        'Kemarin'         : [moment().subtract(1,'days').startOf('day'), moment().subtract(1,'days').endOf('day')],
        '7 Hari Terakhir' : [moment().subtract(6,'days').startOf('day'), moment().endOf('day')],
        '30 Hari Terakhir': [moment().subtract(29,'days').startOf('day'), moment().endOf('day')],
        'Bulan Ini'       : [moment().startOf('month'), moment().endOf('month')]
    };

    $('#rwaktu_laporan').daterangepicker({
        timePicker: true, timePicker24Hour: true, timePickerIncrement: 1,
        startDate: moment().subtract(30, 'days').startOf('day'), endDate: moment().endOf('day'),
        ranges: drpRanges, locale: drpLocale
    });
    $('#rwaktu_laporan').val(moment().subtract(30, 'days').startOf('day').format('YYYY-MM-DD HH:mm') + ' s/d ' + moment().endOf('day').format('YYYY-MM-DD HH:mm'));

    // ==================== MAIN DATATABLE ====================
    var cols = [
        {data: 0}, // Kode Batch
        {data: 1}, // Tanggal Kirim
        {data: 2}, // Dikirim Oleh
        {data: 3, className: 'text-center'}, // Total Qty
        {data: 4, className: 'text-center'}, // Status
        {data: 5}, // Diterima Oleh
        {data: 6}, // Tanggal Diterima
        {data: 7, orderable: false, className: 'text-center'} // Aksi
    ];

    var dtBatches = $('#dt_display_batches').DataTable({
        scrollX: true, 
        pageLength: 25, 
        processing: true, 
        serverSide: true,
        order: [[1, 'desc']], // Default sort by Tanggal Kirim desc
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        ajax: { 
            url: BASE + 'restock/get-display-batches', 
            type: 'POST', 
            data: function(d) {
                d.reportrange = $('#rwaktu_laporan').val();
            } 
        },
        columns: cols,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i> <span>Loading...</span>'
        }
    });

    $('#btn_tampilkan').on('click', function () { 
        dtBatches.ajax.reload(); 
    });

    // ==================== ACTIONS ====================

    // Detail Button Clicked
    $('#dt_display_batches').on('click', '.btn-detail', function() {
        var batchId = $(this).data('id');
        var batchCode = $(this).data('code');

        $('#lbl_kode_batch').text(batchCode);
        $('#body_batch_items').html('<tr><td colspan="4" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
        $('#modal_detail').modal('show');

        $.ajax({
            url: BASE + 'restock/get-display-batch-details',
            type: 'POST',
            data: { batch_id: batchId },
            dataType: 'json',
            success: function(res) {
                var html = '';
                if (res && res.length > 0) {
                    $.each(res, function(i, item) {
                        html += '<tr>' +
                            '<td>' + (item.sku || '-') + '</td>' +
                            '<td>' + (item.nama_barang || '-') + '</td>' +
                            '<td class="text-center">' + (item.qty || 1) + '</td>' +
                            '<td class="text-center">' + (item.no_rak || '-') + '</td>' +
                            '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="4" class="text-center text-muted">Tidak ada item dalam batch ini.</td></tr>';
                }
                $('#body_batch_items').html(html);
            },
            error: function() {
                $('#body_batch_items').html('<tr><td colspan="4" class="text-center text-danger">Gagal mengambil detail item.</td></tr>');
            }
        });
    });

    // Print Button Clicked
    $('#dt_display_batches').on('click', '.btn-print', function() {
        var batchId = $(this).data('id');
        var printUrl = BASE + 'restock/print-retur-display-batch?batch_id=' + batchId;
        window.open(printUrl, '_blank', 'width=800,height=600');
    });

    // Confirm Terima Button Clicked
    $('#dt_display_batches').on('click', '.btn-terima', function() {
        var batchId = $(this).data('id');
        var batchCode = $(this).data('code');

        if (confirm('Apakah Anda yakin telah menerima fisik barang untuk batch ' + batchCode + '?\nStatus akan berubah menjadi DITERIMA dan item akan otomatis bertambah ke stok display.')) {
            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                url: BASE + 'restock/confirm-display-batch',
                type: 'POST',
                data: { batch_id: batchId },
                dataType: 'json',
                success: function(res) {
                    if (res && res.code === 200) {
                        showNotification('success', res.message);
                        dtBatches.ajax.reload(null, false);
                    } else {
                        showNotification('danger', res.message || 'Gagal mengkonfirmasi penerimaan batch.');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    showNotification('danger', 'Gagal memproses request (HTTP ' + xhr.status + ').');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });

    // Helper notification function
    function showNotification(type, message) {
        var alertClass = 'alert-' + type;
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        var notification = $('<div class="alert ' + alertClass + ' alert-dismissible" role="alert" style="margin-top: 10px;">' +
          '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
          '<span aria-hidden="true">&times;</span></button>' +
          '<i class="fa ' + icon + '"></i> ' + message + '</div>');

        $('.panel-body').prepend(notification);

        setTimeout(function () {
          notification.fadeOut('slow', function () {
            $(this).remove();
          });
        }, 6000);
    }
});
</script>

<style>
#dt_display_batches th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}
#dt_display_batches td {
    vertical-align: middle;
    white-space: nowrap;
}
#dt_batch_items th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: center;
}
.table-responsive {
    width: 100% !important;
    overflow-x: auto !important;
}
</style>
