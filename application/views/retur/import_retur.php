<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-upload"></i> Upload / Suntik Data Retur dari Excel</h4>
      </div>
      <div class="panel-body">

        <div class="alert alert-info">
          <i class="fa fa-info-circle"></i>
          Upload file Excel retur (.xlsx / .xls) dengan kolom:
          <strong>NOMOR RESI, SKU, QTY, NO PESANAN, TOKO, KITA (kurir), TGL PESANAN, TAGIHAN, TANGGAL TERIMA, TANGGAL BUKA</strong>.
          <ul style="margin-top:8px;margin-bottom:0;">
            <li>Ada <strong>TANGGAL TERIMA</strong> &rarr; data masuk sebagai <strong>Terima Retur</strong>.</li>
            <li>Ada <strong>TANGGAL BUKA</strong> &rarr; data masuk sebagai <strong>Buka Retur</strong> (status default) + <strong>TAGIHAN</strong> disimpan sebagai harga per SKU.</li>
            <li>Kolom <strong>KITA</strong> = kurir: <code>JNT - &lt;nama&gt;</code> &rarr; JNT, <code>SPX</code> &rarr; SHOPEE.</li>
            <li>Kolom <strong>TOKO</strong> dipecah jadi marketplace + nama toko, mis. <code>Shop | Tokopedia - TT YARRA STORE</code> &rarr; marketplace <strong>Tiktok</strong>, toko <strong>TT YARRA STORE</strong>.</li>
            <li>Data yang tidak lengkap akan dibiarkan kosong / N/A dan bisa disesuaikan lagi nanti.</li>
          </ul>
        </div>

        <div class="form-inline" style="margin-bottom:15px;">
          <input type="file" id="file_retur" accept=".xlsx,.xls" class="form-control" style="display:inline-block;width:auto;" />
          <button type="button" class="btn btn-primary" id="btn_upload_retur">
            <i class="fa fa-upload"></i> Upload &amp; Suntik
          </button>
          <span id="upload_retur_status" style="margin-left:10px;font-weight:bold;"></span>
        </div>

        <div id="hasil_import_retur" style="display:none;">
          <h5 style="font-weight:bold;"><i class="fa fa-check-circle"></i> Ringkasan Import</h5>
          <table class="table table-bordered" style="max-width:520px;">
            <tbody>
              <tr><th style="width:60%;">Total baris diproses</th><td id="r_total">0</td></tr>
              <tr class="active"><th>Retur Tadro (belum terima &amp; belum buka)</th><td id="r_tadro">0</td></tr>
              <tr><th>Terima Retur (belum dibuka)</th><td id="r_terima">0</td></tr>
              <tr class="success"><th>Buka Retur (selesai dibuka)</th><td id="r_buka">0</td></tr>
              <tr><th>Resi baru / diperbarui</th><td><span id="r_resi_baru">0</span> / <span id="r_resi_update">0</span></td></tr>
              <tr class="warning"><th>Resi tidak ditemukan di sistem</th><td id="r_resi_nf">0</td></tr>
              <tr class="warning"><th>Kurir tidak dikenal</th><td id="r_kurir_nf">0</td></tr>
            </tbody>
          </table>
          <p class="text-muted">
            Lihat hasilnya di menu <strong>Laporan Retur</strong> &rarr; tab <strong>Terima Retur</strong> / <strong>Buka Retur</strong>
            (kolom <strong>Harga</strong> &amp; <strong>Total</strong> kini tampil di Buka Retur).
          </p>
        </div>

      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var BASE = '<?= rtrim(base_url(), "/") ?>/';

    $('#btn_upload_retur').on('click', function () {
        var fileInput = $('#file_retur')[0];
        if (!fileInput || !fileInput.files.length) {
            alert('Pilih file Excel retur terlebih dahulu!');
            return;
        }
        var fd = new FormData();
        fd.append('returFile', fileInput.files[0]);

        var $btn = $(this);
        $.ajax({
            url: BASE + 'retur/upload-retur',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
                $('#upload_retur_status').removeClass('text-success text-danger').addClass('text-muted').text('Memproses file...');
            },
            success: function (res) {
                if (res && res.code === 201) {
                    $('#upload_retur_status').removeClass('text-muted text-danger').addClass('text-success').text(res.message);
                    if (res.data) {
                        $('#r_total').text(res.data.total || 0);
                        $('#r_tadro').text(res.data.tadro || 0);
                        $('#r_terima').text(res.data.terima || 0);
                        $('#r_buka').text(res.data.buka || 0);
                        $('#r_resi_baru').text(res.data.resi_baru || 0);
                        $('#r_resi_update').text(res.data.resi_update || 0);
                        $('#r_resi_nf').text(res.data.resi_not_found || 0);
                        $('#r_kurir_nf').text(res.data.kurir_not_found || 0);
                        $('#hasil_import_retur').show();
                    }
                    $('#file_retur').val('');
                } else {
                    $('#upload_retur_status').removeClass('text-muted text-success').addClass('text-danger').text((res && res.message) ? res.message : 'Gagal import');
                }
            },
            error: function (xhr) {
                $('#upload_retur_status').removeClass('text-muted text-success').addClass('text-danger')
                    .text('Gagal upload (HTTP ' + xhr.status + '). ' + (xhr.responseText ? xhr.responseText.substring(0,200) : ''));
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Suntik');
            }
        });
    });
});
</script>
