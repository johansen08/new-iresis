<?php
$BASE = rtrim(base_url(), '/') . '/';
?>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Cek Detail Retur by SKU</strong></h3>
      </div>
      <div class="panel-body">
        
        <form action="javascript:void(0);" class="form-horizontal nojs" autocomplete="off" id="form-cek-sku">
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input type="text" class="form-control" name="sku_cari" id="sku_cari" list="sku_list" placeholder="Masukkan SKU untuk cek detail retur..." />
                <datalist id="sku_list"></datalist>
                <span class="input-group-btn">
                  <button class="btn btn-default" type="submit" id="btn-cek-sku">
                    <i class="fa fa-search"></i> Cari
                  </button>
                </span>
              </div>
            </div>
          </div>
        </form>

        <div class="panel-body" id="table-cek-sku-container" style="display: none; padding-left: 0; padding-right: 0;">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover datatable-cek-sku" style="width: 100%;">
              <thead>
                <tr>
                  <th>No. Pesanan</th>
                  <th>No. Resi</th>
                  <th>Marketplace</th>
                  <th>Nama Toko</th>
                  <th>Kurir</th>
                  <th>Status Retur</th>
                  <th>Tgl Terima</th>
                  <th>Tgl Buka</th>
                  <th>Status Dibuka</th>
                  <th>Tgl ACC</th>
                  <th>SKU</th>
                  <th>Qty</th>
                  <th>Harga</th>
                  <th>Total</th>
                  <th>No. Rak</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="15" class="text-center">Silakan cari SKU.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var BASE = '<?= $BASE ?>';

    $('#sku_cari').focus();

    // ==================== AUTOCOMPLETE SUGGESTIONS ====================
    $('#sku_cari').on('input', function() {
        var term = $(this).val().trim();
        if (term.length >= 2) {
            $.ajax({
                url: BASE + 'retur/get-sku-suggestions',
                type: 'GET',
                data: { term: term },
                dataType: 'json',
                success: function(data) {
                    var $list = $('#sku_list');
                    $list.empty();
                    if (data && data.length > 0) {
                        $.each(data, function(i, item) {
                            $list.append('<option value="' + item.id + '">' + item.value + '</option>');
                        });
                    }
                }
            });
        }
    });

    // ==================== CEK DETAIL RETUR BY SKU ====================
    $('#form-cek-sku').on('submit', function(e) {
        e.preventDefault();
        var skuVal = $('#sku_cari').val().trim();
        if (skuVal === '') return;

        $('#table-cek-sku-container').show();
        var $tbody = $('.datatable-cek-sku tbody');
        $tbody.html('<tr><td colspan="15" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');

        $.ajax({
            url: BASE + 'retur/get-retur-details-by-sku-api',
            type: 'POST',
            data: { sku: skuVal },
            dataType: 'json',
            success: function(response) {
                $tbody.empty();
                if (response.success && response.data && response.data.length > 0) {
                    $.each(response.data, function(i, item) {
                        var row = '<tr>' +
                            '<td>' + item.no_pesanan + '</td>' +
                            '<td>' + item.noresi + '</td>' +
                            '<td>' + item.nama_marketplace + '</td>' +
                            '<td>' + item.nama_toko + '</td>' +
                            '<td>' + item.nama_kurir + '</td>' +
                            '<td>' + item.status_retur + '</td>' +
                            '<td>' + item.tanggal_terima + '</td>' +
                            '<td>' + item.tanggal_buka + '</td>' +
                            '<td>' + item.status_detail_buka + '</td>' +
                            '<td>' + item.tanggal_acc + '</td>' +
                            '<td>' + item.sku + '</td>' +
                            '<td class="text-center">' + item.jumlah + '</td>' +
                            '<td>' + item.harga + '</td>' +
                            '<td>' + item.total + '</td>' +
                            '<td>' + item.no_rak + '</td>' +
                            '</tr>';
                        $tbody.append(row);
                    });
                } else {
                    $tbody.html('<tr><td colspan="15" class="text-center">Tidak ada data retur untuk SKU "' + skuVal + '"</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="15" class="text-center text-danger">Gagal memuat data dari server</td></tr>');
            }
        });
    });
});
</script>

<style>
.table-responsive {
    width: 100% !important;
    overflow-x: auto !important;
}
.datatable-cek-sku th, .datatable-cek-sku td {
    white-space: nowrap !important;
}
</style>
