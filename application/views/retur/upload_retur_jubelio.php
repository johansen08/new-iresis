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
                <th>Keterangan (Status Paket)</th>
                <th>Tanggal</th>
                <th>Cocok di iresis?</th>
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

    // ==================== UPLOAD JUBELIO ====================
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
            { 'data': 8 }, { 'data': 9 }, { 'data': 10 }, { 'data': 11 }, { 'data': 12 }
        ]
    });
});
</script>
