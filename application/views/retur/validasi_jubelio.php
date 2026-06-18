<div class="row">
  <div class="col-md-12">

    <!-- ============ PANEL 1: UPLOAD + LIST RETUR JUBELIO ============ -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-upload"></i> Upload &amp; List Retur Jubelio</h4>
      </div>
      <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom:15px;">
          <i class="fa fa-info-circle"></i>
          Upload file Excel <strong>"daftar retur penjualan"</strong> dari Jubelio (.xlsx / .xls).
          Data yang berhasil masuk akan tampil di tabel <strong>List Retur Jubelio</strong> di bawah.
        </div>

        <div class="form-inline" style="margin-bottom:20px;">
          <input type="file" id="file_jubelio" accept=".xlsx,.xls" class="form-control" style="display:inline-block;width:auto;" />
          <button type="button" class="btn btn-primary" id="btn_upload_jubelio">
            <i class="fa fa-upload"></i> Upload &amp; Validasi
          </button>
          <span id="upload_jubelio_status" style="margin-left:10px;font-weight:bold;"></span>
        </div>

        <h5 style="font-weight:bold;margin-top:10px;"><i class="fa fa-list"></i> List Retur Jubelio</h5>
        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="datatable_jubelio_list" style="width:100%;">
            <thead>
              <tr>
                <th>No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Status Jubelio</th>
                <th>Tanggal</th>
                <th>Cocok di iresis?</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- ============ PANEL 2: LAPORAN REKONSILIASI ============ -->
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-check-square-o"></i> Laporan Rekonsiliasi (iresis vs Jubelio)</h4>
      </div>
      <div class="panel-body">

        <!-- Filter -->
        <div class="form-horizontal">
          <div class="form-group">
            <label class="col-md-2 control-label">Rentang Waktu</label>
            <div class="col-md-4">
              <input type="text" id="rentang_waktu_jubelio" class="form-control" placeholder="Pilih Rentang Waktu" />
            </div>
            <label class="col-md-1 control-label">Kurir</label>
            <div class="col-md-2">
              <select id="kurir_jubelio" class="form-control">
                <option value="">Semua Kurir</option>
                <?php foreach ($list_kurir as $kurir): ?>
                  <option value="<?= $kurir->id_kurir ?>"><?= $kurir->nama_kurir ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <label class="col-md-1 control-label">Kondisi</label>
            <div class="col-md-2">
              <select id="kondisi_jubelio" class="form-control">
                <option value="">Semua</option>
                <option value="COCOK">Cocok</option>
                <option value="IRESIS">Hanya di iresis</option>
                <option value="JUBELIO">Hanya di Jubelio</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 control-label"></label>
            <div class="col-md-8">
              <button type="button" class="btn btn-primary" id="btn_tampilkan_jubelio">
                <i class="fa fa-search"></i> Tampilkan
              </button>
              <button type="button" class="btn btn-success" id="btn_export_jubelio">
                <i class="fa fa-file-excel-o"></i> Export ke Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-4">
            <div class="panel panel-success"><div class="panel-body text-center">
              <h4 style="margin:0;"><span id="sum_cocok">0</span></h4><small>Cocok (iresis &amp; Jubelio)</small>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="panel panel-warning"><div class="panel-body text-center">
              <h4 style="margin:0;"><span id="sum_iresis">0</span></h4><small>Hanya di iresis</small>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="panel panel-danger"><div class="panel-body text-center">
              <h4 style="margin:0;"><span id="sum_jubelio">0</span></h4><small>Hanya di Jubelio</small>
            </div></div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="datatable_jubelio" style="width:100%;">
            <thead>
              <tr>
                <th>No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Kategori iresis</th>
                <th>Status Jubelio</th>
                <th>Qty iresis</th>
                <th>Qty Jubelio</th>
                <th>Tanggal</th>
                <th>Kondisi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // URL absolut supaya tidak salah resolve saat halaman dimuat lewat SPA
    var BASE = '<?= rtrim(base_url(), "/") ?>/';

    // ==================== UPLOAD JUBELIO (didaftarkan paling awal) ====================
    $('#btn_upload_jubelio').on('click', function () {
        var fileInput = $('#file_jubelio')[0];
        if (!fileInput || !fileInput.files.length) {
            alert('Pilih file Excel Jubelio terlebih dahulu!');
            return;
        }
        var fd = new FormData();
        fd.append('jubelioFile', fileInput.files[0]);

        var $btn = $(this);
        $.ajax({
            url: BASE + 'retur/upload-jubelio',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengupload...');
                $('#upload_jubelio_status').removeClass('text-success text-danger').addClass('text-muted').text('Memproses file...');
            },
            success: function (res) {
                if (res && res.code === 201) {
                    $('#upload_jubelio_status').removeClass('text-muted text-danger').addClass('text-success').text(res.message);
                    $('#file_jubelio').val('');
                    if (typeof jubelioListTable !== 'undefined') jubelioListTable.ajax.reload();
                } else {
                    $('#upload_jubelio_status').removeClass('text-muted text-success').addClass('text-danger').text((res && res.message) ? res.message : 'Gagal upload');
                }
            },
            error: function (xhr) {
                $('#upload_jubelio_status').removeClass('text-muted text-success').addClass('text-danger')
                    .text('Gagal upload (HTTP ' + xhr.status + '). ' + (xhr.responseText ? xhr.responseText.substring(0,200) : ''));
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Validasi');
            }
        });
    });

    // ==================== LIST RETUR JUBELIO (server-side) ====================
    var jubelioListTable = $('#datatable_jubelio_list').DataTable({
        'scrollX': true,
        'pageLength': 25,
        'processing': true,
        'serverSide': true,
        'order': [[0, 'asc']],
        'lengthMenu': [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        'ajax': {
            url: BASE + 'retur/get-jubelio-list-data',
            type: 'POST'
        },
        'columns': [
            { 'data': 0, 'orderable': false }, { 'data': 1 }, { 'data': 2 }, { 'data': 3 },
            { 'data': 4 }, { 'data': 5 }, { 'data': 6 }, { 'data': 7 },
            { 'data': 8 }, { 'data': 9 }, { 'data': 10 }, { 'data': 11 }
        ]
    });

    // ==================== DATERANGEPICKER (rekonsiliasi) ====================
    $('#rentang_waktu_jubelio').daterangepicker({
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 1,
        ranges: {
            'Hari Ini': [moment().startOf('day'), moment().endOf('day')],
            'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            '7 Hari Terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            '30 Hari Terakhir': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD HH:mm',
            separator: ' s/d ',
            applyLabel: 'Terapkan',
            cancelLabel: 'Batal',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        startDate: moment().startOf('day'),
        endDate: moment().endOf('day')
    });

    // ==================== DATATABLE REKONSILIASI (client-side) ====================
    var tableJubelio = $('#datatable_jubelio').DataTable({
        'scrollX': true,
        'pageLength': 25,
        'lengthMenu': [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        'data': [],
        'columns': [
            { 'data': 0 }, { 'data': 1 }, { 'data': 2 }, { 'data': 3 },
            { 'data': 4 }, { 'data': 5 }, { 'data': 6 }, { 'data': 7 },
            { 'data': 8 }, { 'data': 9 }, { 'data': 10 }, { 'data': 11 }
        ]
    });

    function getJubelioFilter() {
        var dates = $('#rentang_waktu_jubelio').val().split(' s/d ');
        return {
            start_date: dates[0] || '',
            end_date: dates[1] || '',
            id_kurir: $('#kurir_jubelio').val(),
            kondisi: $('#kondisi_jubelio').val()
        };
    }

    function loadJubelio() {
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        tableJubelio.clear().draw();
        $.ajax({
            url: BASE + 'retur/get-rekonsiliasi-data',
            type: 'POST',
            dataType: 'json',
            data: f,
            beforeSend: function () {
                $('#btn_tampilkan_jubelio').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memuat...');
            },
            success: function (res) {
                tableJubelio.clear().rows.add(res.data || []).draw();
                $('#sum_cocok').text((res.summary && res.summary.cocok) || 0);
                $('#sum_iresis').text((res.summary && res.summary.iresis) || 0);
                $('#sum_jubelio').text((res.summary && res.summary.jubelio) || 0);
            },
            error: function () { alert('Gagal memuat data rekonsiliasi.'); },
            complete: function () {
                $('#btn_tampilkan_jubelio').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');
            }
        });
    }

    $('#btn_tampilkan_jubelio').on('click', loadJubelio);

    $('#btn_export_jubelio').on('click', function () {
        var f = getJubelioFilter();
        if (!f.start_date || !f.end_date) {
            alert('Silakan pilih rentang waktu terlebih dahulu!');
            return;
        }
        var url = BASE + 'retur/export-rekonsiliasi?start_date=' + encodeURIComponent(f.start_date) +
                  '&end_date=' + encodeURIComponent(f.end_date);
        if (f.id_kurir) url += '&id_kurir=' + encodeURIComponent(f.id_kurir);
        if (f.kondisi) url += '&kondisi=' + encodeURIComponent(f.kondisi);
        window.location.href = url;
    });
});
</script>

<style>
#rentang_waktu_jubelio {
    background-color: #fff !important;
    cursor: pointer !important;
    color: #555 !important;
    border: 1px solid #ccc !important;
}
#rentang_waktu_jubelio:hover {
    border-color: #66afe9 !important;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6) !important;
}
</style>
