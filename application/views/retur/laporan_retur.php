<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong><?= isset($is_komplain) && $is_komplain ? 'Laporan Retur Komplain' : 'Laporan Retur Lengkap' ?></strong></h3>
      </div>
      <div class="panel-body">
        
        <!-- Filter Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-horizontal">
              
              <div class="form-group">
                <label class="col-md-2 control-label">Rentang Waktu</label>
                <div class="col-md-3">
                  <input type="text" id="rwaktu_laporan" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                </div>
                
                <label class="col-md-1 control-label">Tipe Tanggal</label>
                <div class="col-md-2">
                  <select id="date_type" class="form-control">
                    <option value="terima">Tanggal Terima</option>
                    <option value="buka">Tanggal Buka</option>
                    <option value="acc">Tanggal ACC</option>
                  </select>
                </div>

                <label class="col-md-1 control-label">Status</label>
                <div class="col-md-3">
                  <select id="status_retur" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="Retur Online">Retur Online</option>
                    <option value="Terima Retur">Terima Retur</option>
                    <option value="Buka Retur">Buka Retur</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-2 control-label">Kurir</label>
                <div class="col-md-3">
                  <select id="kurir_laporan" class="form-control">
                    <option value="">Semua Kurir</option>
                    <?php foreach ($list_kurir as $k): ?>
                      <option value="<?= $k->id_kurir ?>"><?= $k->nama_kurir ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-7 text-right">
                  <button type="button" class="btn btn-primary" id="btn_tampilkan">
                    <i class="fa fa-search"></i> Tampilkan
                  </button>
                  <button type="button" class="btn btn-success" id="btn_export">
                    <i class="fa fa-file-excel-o"></i> Export ke Excel
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <hr style="margin-top: 10px; margin-bottom: 20px;"/>

        <!-- Table Section -->
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover" id="dt_laporan_lengkap" style="width: 100%;">
                <thead>
                  <tr>
                    <th>No. Pesanan</th>
                    <th>No. Resi</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>No. Rak</th>
                    <th>Marketplace</th>
                    <th>Nama Toko</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Status Dibuka</th>
                    <th>Status Retur</th>
                    <th>Tgl Terima</th>
                    <th>Jam Terima</th>
                    <th>Tgl Buka</th>
                    <th>Jam Buka</th>
                    <th>Tgl ACC</th>
                    <th>Jam ACC</th>
                    <th>Aksi</th>
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

<!-- Modal konfirmasi aksi -->
<div class="modal fade" id="modalProgress" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="modalProgressTitle">Konfirmasi</h4>
      </div>
      <div class="modal-body" id="modalProgressBody">Apakah Anda yakin?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnProgressConfirm">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';
    var IS_KOMPLAIN = <?= isset($is_komplain) && $is_komplain ? 'true' : 'false' ?>;

    // ==================== DATERANGEPICKER HELPER ====================
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
        startDate: moment().startOf('month'), endDate: moment().endOf('day'),
        ranges: drpRanges, locale: drpLocale
    });
    $('#rwaktu_laporan').val(moment().startOf('month').format('YYYY-MM-DD HH:mm') + ' s/d ' + moment().endOf('day').format('YYYY-MM-DD HH:mm'));

    // ==================== DATATABLE INITIALIZATION ====================
    var cols = [
        {data: 0}, // No. Pesanan
        {data: 1}, // No. Resi
        {data: 2}, // SKU
        {data: 3}, // Qty
        {data: 4}, // No. Rak
        {data: 5}, // Marketplace
        {data: 6}, // Nama Toko
        {data: 7}, // Harga
        {data: 8}, // Total
        {data: 9}, // Status Dibuka
        {data: 10}, // Status Retur
        {data: 11}, // Tgl Terima
        {data: 12}, // Jam Terima
        {data: 13}, // Tgl Buka
        {data: 14}, // Jam Buka
        {data: 15}, // Tgl ACC
        {data: 16}, // Jam ACC
        {data: 17, orderable: false} // Aksi
    ];

    var dtLaporan = $('#dt_laporan_lengkap').DataTable({
        scrollX: true, 
        pageLength: 25, 
        processing: true, 
        serverSide: true,
        order: [[11, 'desc']], // Default sort by Tanggal Terima
        deferLoading: 0,
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        ajax: { 
            url: BASE + 'retur/get-data-laporan-retur-lengkap', 
            type: 'POST', 
            data: function(d) {
                var v = $('#rwaktu_laporan').val().split(' s/d ');
                d.start_date = v[0] || '';
                d.end_date = v[1] || '';
                d.date_type = $('#date_type').val();
                d.status_retur = $('#status_retur').val();
                d.id_kurir = $('#kurir_laporan').val();
                d.is_komplain = IS_KOMPLAIN ? 1 : 0;
            } 
        },
        columns: cols,
        language: { 
            emptyTable: 'Klik <b>Tampilkan</b> untuk memuat data', 
            zeroRecords: 'Tidak ada data ditemukan' 
        }
    });

    // Auto-trigger loading on startup
    dtLaporan.ajax.reload();

    // ==================== ACTIONS ====================
    $('#btn_tampilkan').on('click', function () { 
        dtLaporan.ajax.reload(); 
    });

    $('#btn_export').on('click', function () {
        var v = $('#rwaktu_laporan').val().split(' s/d ');
        if (!v[0] || !v[1]) { 
            alert('Pilih rentang waktu terlebih dahulu!'); 
            return; 
        }
        var url = BASE + 'retur/export-excel-laporan-retur-lengkap?start_date=' + encodeURIComponent(v[0]) 
            + '&end_date=' + encodeURIComponent(v[1])
            + '&date_type=' + encodeURIComponent($('#date_type').val())
            + '&status_retur=' + encodeURIComponent($('#status_retur').val())
            + '&is_komplain=' + (IS_KOMPLAIN ? 1 : 0);
            
        var k = $('#kurir_laporan').val(); 
        if (k) url += '&id_kurir=' + encodeURIComponent(k);
        
        window.location.href = url;
    });

    // ==================== INLINE AKSI: TANDAI TERIMA / BUKA ====================
    var _pendingNoresi = '', _pendingAction = '';

    $(document).on('click', '.btn-progress', function () {
        _pendingNoresi = $(this).data('noresi');
        _pendingAction = $(this).data('action');
        var label = _pendingAction === 'terima' ? 'Terima Retur' : 'Buka Retur';
        $('#modalProgressTitle').text('Konfirmasi ' + label);
        $('#modalProgressBody').html('Tandai resi <strong>' + _pendingNoresi + '</strong> sebagai <strong>' + label + '</strong>?');
        $('#modalProgress').modal('show');
    });

    $('#btnProgressConfirm').on('click', function () {
        $('#modalProgress').modal('hide');
        $.ajax({
            url: BASE + 'retur/progress-status-retur',
            type: 'POST',
            data: { noresi: _pendingNoresi, action: _pendingAction },
            dataType: 'json',
            success: function (res) {
                if (res && res.code === 201) {
                    dtLaporan.ajax.reload(null, false);
                    alert(res.message);
                } else {
                    alert((res && res.message) ? res.message : 'Gagal memproses');
                }
            },
            error: function (xhr) { alert('Gagal (HTTP ' + xhr.status + ')'); }
        });
    });
});
</script>

<style>
.btn-progress { white-space: nowrap; }
.form-group { margin-bottom: 12px; }
.table-responsive {
    width: 100% !important;
    overflow-x: auto !important;
}
#dt_laporan_lengkap th, #dt_laporan_lengkap td {
    white-space: nowrap !important;
}
</style>
