<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Kirim Barang ke Display</strong></h3>
      </div>
      <div class="panel-body">
        
        <!-- Filter Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-horizontal">
              <div class="form-group" style="margin-bottom: 10px;">
                <label class="col-md-2 control-label">Rentang Tanggal</label>
                <div class="col-md-3">
                  <input type="text" id="rwaktu_laporan" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                </div>
                
                <label class="col-md-1 control-label">Status Buka</label>
                <div class="col-md-2">
                  <select id="filter_status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="KE_DISPLAY" selected>KE DISPLAY</option>
                    <option value="REJECT">REJECT</option>
                    <option value="REFUND">REFUND</option>
                    <option value="PENUKARAN_BERES">PENDINGAN BERES (PB)</option>
                    <option value="REQUEST_DARI_PEMBELI">REQUEST DARI PEMBELI</option>
                    <option value="KURANG">kurang dari penjual</option>
                    <option value="KURANG_DARI_PEMBELI">KURANG DARI PEMBELI</option>
                    <option value="BUKAN_BARANG_KITA">BUKAN BARANG KITA</option>
                    <option value="PAKET_HILANG">PAKET HILANG</option>
                    <option value="DIPAKAI_ADMIN">DIPAKAI ADMIN</option>
                    <option value="BARANG_TIDAK_ADA">BARANG TIDAK ADA</option>
                    <option value="BUKAN_RETUR">BUKAN RETUR</option>
                    <option value="ALASAN_LAINNYA">ALASAN LAINNYA</option>
                  </select>
                </div>
                
                <label class="col-md-1 control-label">Jenis Retur</label>
                <div class="col-md-2">
                  <select id="filter_jenis" class="form-control">
                    <option value="">Semua Jenis</option>
                    <option value="0">REGULER</option>
                    <option value="1">KOMPLAIN</option>
                  </select>
                </div>
              </div>
              
              <div class="form-group" style="margin-bottom: 0;">
                <div class="col-md-12 text-right">
                  <button type="button" class="btn btn-primary" id="btn_tampilkan">
                    <i class="fa fa-search"></i> Tampilkan
                  </button>
                  <button type="button" class="btn btn-danger" id="btn_buat_batch" disabled>
                    <i class="fa fa-cube"></i> Buat Batch Display (<span id="selected_count">0</span>)
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr style="margin-top: 15px; margin-bottom: 15px;"/>

        <!-- Table Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover" id="dt_unbatched_returns" style="width: 100%;">
                <thead>
                  <tr>
                    <th style="width: 30px; text-align: center;"><input type="checkbox" id="chk_all" /></th>
                    <th>No. Resi</th>
                    <th>Status Buka</th>
                    <th>Jenis Retur</th>
                    <th>SKU</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>No. Rak</th>
                    <th>Marketplace</th>
                    <th>Nama Toko</th>
                    <th>Tanggal Buka</th>
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

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var selectedIds = [];

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

    // ==================== DATATABLE INITIALIZATION ====================
    var cols = [
        {data: 0, orderable: false, className: 'text-center'}, // Checkbox
        {data: 1}, // No. Resi
        {
            data: 2, // Status Buka
            render: function(data, type, row) {
                if(!data) return '-';
                var color = 'label-default';
                var dUpper = data.toUpperCase();
                if(dUpper === 'KE_DISPLAY') color = 'label-success';
                else if(dUpper === 'REJECT' || dUpper === 'ALASAN_LAINNYA' || dUpper === 'BUKAN_RETUR' || dUpper === 'BARANG_TIDAK_ADA') color = 'label-danger';
                return '<span class="label ' + color + '">' + data + '</span>';
            }
        },
        {
            data: 10, // Jenis Retur
            render: function(data, type, row) {
                if (data === '1' || data === 1) {
                    return '<span class="label label-danger">KOMPLAIN</span>';
                } else {
                    return '<span class="label label-info">REGULER</span>';
                }
            }
        },
        {data: 3}, // SKU
        {data: 4}, // Nama Barang
        {data: 5}, // Qty
        {data: 6}, // No. Rak
        {data: 7}, // Marketplace
        {data: 8}, // Nama Toko
        {data: 9}  // Tanggal Buka
    ];

    var dtUnbatched = $('#dt_unbatched_returns').DataTable({
        scrollX: true, 
        pageLength: 50, 
        processing: true, 
        serverSide: true,
        order: [[9, 'desc']], // Default sort by Tanggal Buka
        lengthMenu: [[10,25,50,100,200,500],[10,25,50,100,200,500]],
        ajax: { 
            url: BASE + 'retur/get-unbatched-returns', 
            type: 'POST', 
            data: function(d) {
                var v = $('#rwaktu_laporan').val().split(' s/d ');
                d.reportrange = $('#rwaktu_laporan').val();
                d.status_filter = $('#filter_status').val();
                d.jenis_filter = $('#filter_jenis').val();
            } 
        },
        columns: cols,
        drawCallback: function() {
            // Uncheck header checkbox on redraw
            $('#chk_all').prop('checked', false);
            updateSelectedState();
        }
    });

    // ==================== CHECKBOX LOGIC ====================
    // Header checkbox toggle
    $('#chk_all').on('change', function() {
        var checked = $(this).is(':checked');
        $('.chk-item').each(function() {
            $(this).prop('checked', checked);
            var val = parseInt($(this).val());
            if (checked) {
                if (selectedIds.indexOf(val) === -1) selectedIds.push(val);
            } else {
                var idx = selectedIds.indexOf(val);
                if (idx !== -1) selectedIds.splice(idx, 1);
            }
        });
        updateSelectedState();
    });

    // Individual checkbox toggle
    $(document).on('change', '.chk-item', function() {
        var val = parseInt($(this).val());
        var checked = $(this).is(':checked');
        if (checked) {
            if (selectedIds.indexOf(val) === -1) selectedIds.push(val);
        } else {
            var idx = selectedIds.indexOf(val);
            if (idx !== -1) selectedIds.splice(idx, 1);
        }
        updateSelectedState();
    });

    function updateSelectedState() {
        // Sync visual check state for items currently on the page
        $('.chk-item').each(function() {
            var val = parseInt($(this).val());
            $(this).prop('checked', selectedIds.indexOf(val) !== -1);
        });

        var count = selectedIds.length;
        $('#selected_count').text(count);
        if (count > 0) {
            $('#btn_buat_batch').prop('disabled', false);
        } else {
            $('#btn_buat_batch').prop('disabled', true);
        }
    }

    // ==================== ACTIONS ====================
    $('#btn_tampilkan').on('click', function () { 
        selectedIds = [];
        dtUnbatched.ajax.reload(); 
    });

    $('#btn_buat_batch').on('click', function() {
        if (selectedIds.length === 0) return;

        if (confirm('Apakah Anda yakin ingin membuat batch display dari ' + selectedIds.length + ' item terpilih?')) {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: BASE + 'retur/create-display-batch',
                type: 'POST',
                data: { ids: selectedIds },
                dataType: 'json',
                success: function(res) {
                    if (res && res.code === 201) {
                        alert(res.message);
                        selectedIds = [];
                        dtUnbatched.ajax.reload();
                        
                        // Automatically open print view in popup
                        var printUrl = BASE + 'restock/print-retur-display-batch?batch_id=' + res.data.id_batch;
                        window.open(printUrl, '_blank', 'width=800,height=600');
                    } else {
                        alert(res.message || 'Gagal membuat batch display');
                        $btn.prop('disabled', false);
                        updateSelectedState();
                    }
                },
                error: function(xhr) {
                    alert('Gagal memproses (HTTP ' + xhr.status + ')');
                    $btn.prop('disabled', false);
                    updateSelectedState();
                }
            });
        }
    });
});
</script>

<style>
#dt_unbatched_returns th, #dt_unbatched_returns td {
    white-space: nowrap !important;
}
.table-responsive {
    width: 100% !important;
    overflow-x: auto !important;
}
</style>
