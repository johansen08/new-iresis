<div class="row">
  <div class="col-md-8 center-block float-none">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Cari Detail Resi</strong></h3>
      </div>

      <div class="panel-body">

        <form action="receipt/detail-receipt" method="post" class="form-horizontal" autocomplete="off">
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input type="text" class="form-control" name="noresi" id="noresi" placeholder="Scan / ketik nomor resi atau nomor pesanan" />
                <span class="input-group-btn">
                  <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> Cari</button>
                </span>
              </div>
            </div>
          </div>

          <div class="tile tile-default mb-0" id="div_container_latest_receipt">
            <span id="span_latest_receipt"><?= !empty($receipt) ? $receipt['noresi'] : '-' ?></span>
          </div>
        </form>

      </div>

      <div class="panel-footer">
        <?php if (!empty($receipt)) : ?>
          <div class="row">
            <div class="col-md-6">
              <table class="table table-striped">
                <tbody>
                  <tr>
                      <td align="right">Nomor Pesanan :</td>
                      <td><strong>
                        <?php if (!empty($receipt_items)) : ?>
                          <?php
                          $unique_orders = array_unique(array_column($receipt_items, 'no_pesanan'));
                          echo implode(', ', $unique_orders);
                          ?>
                        <?php else : ?>
                          -
                        <?php endif; ?>
                      </strong></td>
                  </tr>
                  <tr>
                      <td align="right">Status Pesanan :</td>
                      <td><strong><?= $receipt['status_pesanan'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Status WMS :</td>
                      <td><strong><?= !empty($receipt['status_wms']) ? $receipt['status_wms'] : '-' ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">SKU :</td>
                      <td>
                        <?php if (!empty($receipt_items)) : ?>
                          <table class="table table-condensed table-bordered" style="margin: 0;">
                            <thead>
                              <tr>
                                <th>SKU</th>
                                <th>No. Rak</th>
                                <th>Kuantitas</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($receipt_items as $item) : ?>
                              <tr>
                                <td><strong><?= $item['sku'] ?></strong></td>
                                <td><strong><?= !empty($item['no_rak']) ? $item['no_rak'] : '-' ?></strong></td>
                                <td><strong><?= $item['jumlah'] ?></strong></td>
                              </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        <?php else : ?>
                          <strong>-</strong>
                        <?php endif; ?>
                      </td>
                  </tr>
                  <tr>
                      <td align="right">Nama Toko :</td>
                      <td><strong><?= $receipt['toko'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Marketplace :</td>
                      <td><strong><?= $receipt['nama_marketplace'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Kurir / Ekspedisi :</td>
                      <td><strong><?= $receipt['nama_kurir'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Tanggal Proses Resi :</td>
                      <td><strong><?= $receipt['tanggal_printresi'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Tanggal Batas Kirim :</td>
                      <td>
                        <?php 
                          $is_today = (!empty($receipt['tanggal_bataskirim']) && date('Y-m-d', strtotime($receipt['tanggal_bataskirim'])) == date('Y-m-d'));
                          $display = $receipt['tanggal_bataskirim'] ?: '-';
                          echo $is_today ? '<strong style="color: red;">' . $display . '</strong>' : '<strong>' . $display . '</strong>';
                        ?>
                      </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-striped">
                <tbody>
                  <tr>
                      <td align="right">Nama Picker :</td>
                      <td><strong><?= $receipt['picker'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Tanggal Picker :</td>
                      <td><strong><?= $receipt['tanggal_resiambilbarang'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Status Performa Picker :</td>
                      <td><strong><?= !empty($receipt['picker_status']) ? $receipt['picker_status'] : '-' ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Nama Packer :</td>
                      <td><strong><?= $receipt['packer'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Tanggal Packing :</td>
                      <td><strong><?= $receipt['tanggal_packing'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Nomor Komputer Packer :</td>
                      <td><strong><?= $receipt['komputer_packer_no'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Status Performa Packer :</td>
                      <td><strong><?= !empty($receipt['packer_status']) ? $receipt['packer_status'] : '-' ?></strong></td>
                  </tr>
                  <tr>
                    <td align="right">Tanggal Serah :</td>
                    <td><strong><?= $receipt['tanggal_resikeluar'] ?></strong></td>
                  </tr>
                  <tr>
                      <td align="right">Tanggal Scan Jadi :</td>
                      <td><strong><?= $receipt['tanggal_cetak'] ?></strong></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="row" style="margin-top: 15px; border-top: 1px dashed #ddd; padding-top: 15px;">
            <div class="col-md-12">
              <h4 style="margin-top: 0; color: #e04b4a; font-weight: bold; border-left: 3px solid #e04b4a; padding-left: 8px;">
                <i class="fa fa-undo"></i> Detail Retur Barang
              </h4>
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-striped" style="margin-bottom: 0;">
                    <tbody>
                      <tr>
                        <td align="right" style="width: 40%;">Tanggal Retur :</td>
                        <td><strong><?= !empty($receipt['tanggal_retur']) ? $receipt['tanggal_retur'] : '-' ?></strong></td>
                      </tr>
                      <tr>
                        <td align="right">Tanggal Diterima :</td>
                        <td><strong><?= !empty($receipt['tanggal_diterima']) ? $receipt['tanggal_diterima'] : '-' ?></strong></td>
                      </tr>
                      <tr>
                        <td align="right">Tanggal Dibuka :</td>
                        <td><strong><?= !empty($receipt['tanggal_dibuka']) ? $receipt['tanggal_dibuka'] : '-' ?></strong></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-striped" style="margin-bottom: 0;">
                    <tbody>
                      <tr>
                        <td align="right" style="width: 40%;">Status Dibuka :</td>
                        <td><strong><?= !empty($receipt['status_dibuka']) ? $receipt['status_dibuka'] : '-' ?></strong></td>
                      </tr>
                      <tr>
                        <td align="right">Tanggal ACC :</td>
                        <td><strong><?= !empty($receipt['tanggal_acc']) ? $receipt['tanggal_acc'] : '-' ?></strong></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        <?php else : ?>
          <p class="text-center"><small><i>Resi tidak ditemukan</i></small></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $("#noresi").focus();
</script>