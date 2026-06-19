<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-tadro" data-toggle="tab">Retur Tadro</a></li>
        <li><a href="#tab-terima" data-toggle="tab">Terima Retur</a></li>
        <li><a href="#tab-buka" data-toggle="tab">Buka Retur</a></li>
      </ul>

      <div class="panel-body tab-content">

        <!-- ==================== TAB 1: RETUR TADRO ==================== -->
        <div class="tab-pane active" id="tab-tadro">
          <div class="row">
            <div class="col-md-12">
              <div class="form-horizontal">
                <div class="form-group">
                  <label class="col-md-2 control-label">Rentang Waktu</label>
                  <div class="col-md-4">
                    <input type="text" id="rwaktu_tadro" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                  </div>
                  <label class="col-md-1 control-label">Kurir</label>
                  <div class="col-md-3">
                    <select id="kurir_tadro" class="form-control">
                      <option value="">Semua Kurir</option>
                      <?php foreach ($list_kurir as $k): ?>
                        <option value="<?= $k->id_kurir ?>"><?= $k->nama_kurir ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 control-label"></label>
                  <div class="col-md-8">
                    <button type="button" class="btn btn-primary" id="btn_tampilkan_tadro">
                      <i class="fa fa-search"></i> Tampilkan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table table-striped table-bordered" id="dt_tadro">
                  <thead>
                    <tr>
                      <th>No. Pesanan</th>
                      <th>No. Resi</th>
                      <th>Marketplace</th>
                      <th>Nama Toko</th>
                      <th>Kurir</th>
                      <th>Tanggal</th>
                      <th>Jam</th>
                      <th>SKU</th>
                      <th>No. Rak</th>
                      <th>Qty</th>
                      <th>Status Detail</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== TAB 2: TERIMA RETUR ==================== -->
        <div class="tab-pane" id="tab-terima">
          <div class="row">
            <div class="col-md-12">
              <div class="form-horizontal">
                <div class="form-group">
                  <label class="col-md-2 control-label">Rentang Waktu</label>
                  <div class="col-md-4">
                    <input type="text" id="rwaktu_terima" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                  </div>
                  <label class="col-md-1 control-label">Kurir</label>
                  <div class="col-md-3">
                    <select id="kurir_terima" class="form-control">
                      <option value="">Semua Kurir</option>
                      <?php foreach ($list_kurir as $k): ?>
                        <option value="<?= $k->id_kurir ?>"><?= $k->nama_kurir ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 control-label"></label>
                  <div class="col-md-8">
                    <button type="button" class="btn btn-primary" id="btn_tampilkan_terima">
                      <i class="fa fa-search"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-success" id="btn_export_terima">
                      <i class="fa fa-file-excel-o"></i> Export ke Excel
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table table-striped table-bordered" id="dt_terima">
                  <thead>
                    <tr>
                      <th>No. Pesanan</th>
                      <th>No. Resi</th>
                      <th>Marketplace</th>
                      <th>Nama Toko</th>
                      <th>Kurir</th>
                      <th>Tanggal Terima</th>
                      <th>Jam Terima</th>
                      <th>SKU</th>
                      <th>No. Rak</th>
                      <th>Qty</th>
                      <th>Status Detail</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ==================== TAB 3: BUKA RETUR ==================== -->
        <div class="tab-pane" id="tab-buka">
          <div class="row">
            <div class="col-md-12">
              <div class="form-horizontal">
                <div class="form-group">
                  <label class="col-md-2 control-label">Rentang Waktu</label>
                  <div class="col-md-4">
                    <input type="text" id="rwaktu_buka" class="form-control" placeholder="Pilih Rentang Waktu" readonly />
                  </div>
                  <label class="col-md-1 control-label">Kurir</label>
                  <div class="col-md-3">
                    <select id="kurir_buka" class="form-control">
                      <option value="">Semua Kurir</option>
                      <?php foreach ($list_kurir as $k): ?>
                        <option value="<?= $k->id_kurir ?>"><?= $k->nama_kurir ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 control-label"></label>
                  <div class="col-md-8">
                    <button type="button" class="btn btn-primary" id="btn_tampilkan_buka">
                      <i class="fa fa-search"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-success" id="btn_export_buka">
                      <i class="fa fa-file-excel-o"></i> Export ke Excel
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table table-striped table-bordered" id="dt_buka">
                  <thead>
                    <tr>
                      <th>No. Pesanan</th>
                      <th>No. Resi</th>
                      <th>Marketplace</th>
                      <th>Nama Toko</th>
                      <th>Kurir</th>
                      <th>Tanggal Buka</th>
                      <th>Jam Buka</th>
                      <th>SKU</th>
                      <th>Qty</th>
                      <th>Harga</th>
                      <th>Total</th>
                      <th>Status Detail</th>
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
    function initDRP(id) {
        $('#' + id).daterangepicker({
            timePicker: true, timePicker24Hour: true, timePickerIncrement: 1,
            startDate: moment().startOf('month'), endDate: moment().endOf('day'),
            ranges: drpRanges, locale: drpLocale
        });
        $('#' + id).val(moment().startOf('month').format('YYYY-MM-DD HH:mm') + ' s/d ' + moment().endOf('day').format('YYYY-MM-DD HH:mm'));
    }
    initDRP('rwaktu_tadro');
    initDRP('rwaktu_terima');
    initDRP('rwaktu_buka');

    // ==================== DATATABLE HELPER (lazy: tidak load sampai klik Tampilkan) ====================
    var dtTadro = null, dtTerima = null, dtBuka = null;

    function createDT(tableId, ajaxUrl, extraData, cols) {
        return $('#' + tableId).DataTable({
            scrollX: true, pageLength: 25, processing: true, serverSide: true,
            order: [[5, 'desc']], deferLoading: 0,
            lengthMenu: [[10,25,50,100],[10,25,50,100]],
            ajax: { url: BASE + ajaxUrl, type: 'POST', data: extraData },
            columns: cols,
            language: { emptyTable: 'Klik <b>Tampilkan</b> untuk memuat data', zeroRecords: 'Tidak ada data ditemukan' }
        });
    }

    var colsTadroTerima = [
        {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},
        {data:7},{data:8},{data:9},{data:10},{data:11, orderable:false}
    ];
    var colsBuka = [
        {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},
        {data:7},{data:8},{data:9},{data:10},{data:11}
    ];

    dtTadro = createDT('dt_tadro', 'retur/get-data-terima-retur-laporan', function(d) {
        var v = $('#rwaktu_tadro').val().split(' s/d ');
        d.start_date = v[0]||''; d.end_date = v[1]||'';
        d.id_kurir = $('#kurir_tadro').val(); d.status = 'Retur Tadro';
    }, colsTadroTerima);

    dtTerima = createDT('dt_terima', 'retur/get-data-terima-retur-laporan', function(d) {
        var v = $('#rwaktu_terima').val().split(' s/d ');
        d.start_date = v[0]||''; d.end_date = v[1]||'';
        d.id_kurir = $('#kurir_terima').val(); d.status = 'Terima Retur';
    }, colsTadroTerima);

    dtBuka = createDT('dt_buka', 'retur/get-data-buka-retur-laporan', function(d) {
        var v = $('#rwaktu_buka').val().split(' s/d ');
        d.start_date = v[0]||''; d.end_date = v[1]||'';
        d.id_kurir = $('#kurir_buka').val();
    }, colsBuka);

    // ==================== TOMBOL TAMPILKAN ====================
    $('#btn_tampilkan_tadro').on('click', function () { dtTadro.ajax.reload(); });
    $('#btn_tampilkan_terima').on('click', function () { dtTerima.ajax.reload(); });
    $('#btn_tampilkan_buka').on('click', function () { dtBuka.ajax.reload(); });

    // ==================== TOMBOL EXPORT ====================
    $('#btn_export_terima').on('click', function () {
        var v = $('#rwaktu_terima').val().split(' s/d ');
        if (!v[0] || !v[1]) { alert('Pilih rentang waktu terlebih dahulu!'); return; }
        var url = BASE + 'retur/export-excel-terima-retur?start_date=' + encodeURIComponent(v[0]) + '&end_date=' + encodeURIComponent(v[1]);
        var k = $('#kurir_terima').val(); if (k) url += '&id_kurir=' + encodeURIComponent(k);
        window.location.href = url;
    });
    $('#btn_export_buka').on('click', function () {
        var v = $('#rwaktu_buka').val().split(' s/d ');
        if (!v[0] || !v[1]) { alert('Pilih rentang waktu terlebih dahulu!'); return; }
        var url = BASE + 'retur/export-excel-buka-retur?start_date=' + encodeURIComponent(v[0]) + '&end_date=' + encodeURIComponent(v[1]);
        var k = $('#kurir_buka').val(); if (k) url += '&id_kurir=' + encodeURIComponent(k);
        window.location.href = url;
    });

    // ==================== TAB SWITCH ====================
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var hash = e.target.hash;
        if (hash === '#tab-tadro')   dtTadro.columns.adjust();
        if (hash === '#tab-terima')  dtTerima.columns.adjust();
        if (hash === '#tab-buka')    dtBuka.columns.adjust();
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
                    if (_pendingAction === 'terima') { dtTadro.ajax.reload(null,false); dtTerima.ajax.reload(null,false); }
                    else { dtTerima.ajax.reload(null,false); dtBuka.ajax.reload(null,false); }
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
</style>
