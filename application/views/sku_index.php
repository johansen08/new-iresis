<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Daftar SKU</h3>
      </div>
      <div class="panel-body">
        <table class="table table-hover datatable-sku">
          <thead>
            <tr>
              <th>#</th>
              <th>ID SKU</th>
              <th>Nama SKU</th>
              <th>Berat</th>
              <th>Total Stok</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="modal_edit_sku" tabindex="-1" role="dialog" aria-labelledby="largeModalHead" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form action="sku/save_sku_location_stock" class="form-horizontal" id="form-content-edit-sku" method="post" autocomplete="off">
      <input type="hidden" name="id" value="" />
      <input type="hidden" name="item_id" value="" />

      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="largeModalHead">Update stok item</h4>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Kode</label>
            <div class="col-md-3 col-xs-12">
              <input type="text" name="item_code" class="form-control" value="" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Nama</label>
            <div class="col-md-6 col-xs-12">
              <input type="text" name="item_name" class="form-control" value="" disabled />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 col-xs-12 control-label">Gambar</label>
            <div class="col-md-6 col-xs-12">
              <img id="thumbnail" src="" alt="">
            </div>
          </div>

          <hr>

          <div class="row">
            <?php foreach ($list_location as $key => $value) : ?>
              <div class="col-md-4 col-xs-12">
                <p><span class="badge" style="background: <?= $bg_colors[$key] ?>; color: #000"><?= $value['paramvalue1'] ?></span></p>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">On hand</label>
                  <div class="col-md-6 col-xs-12">
                    <input type="number" name="<?= $value['paramvalue2'] ?>_on_hand" class="form-control" value="" />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">On order</label>
                  <div class="col-md-6 col-xs-12">
                    <input type="number" name="<?= $value['paramvalue2'] ?>_on_order" class="form-control" value="" />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Reserved</label>
                  <div class="col-md-6 col-xs-12">
                    <input type="number" name="<?= $value['paramvalue2'] ?>_reserved" class="form-control" value="" />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 col-xs-12 control-label">Available</label>
                  <div class="col-md-6 col-xs-12">
                    <input type="number" name="<?= $value['paramvalue2'] ?>_available" class="form-control" value="" />
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-info">Submit</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    var table = $('.datatable-sku').DataTable({
      'scrollX': true,
      'pageLength': 20,
      'processing': true,
      'serverSide': true,
      'order': [
        [1, 'asc']
      ],
      'lengthMenu': [
        [20, 50, 100, 150, 200],
        [20, 50, 100, 150, 200]
      ],
      'ajax': {
        url: 'sku/get_data',
        type: 'POST',
      }
    });

    table.on('draw', function() {
      $(this).find('.btn-edit-sku').on('click', function() {
        var id = $(this).data('id');

        $.ajax({
          url: 'sku/get_sku_location_stock/' + id,
          type: 'get',
          dataType: 'JSON',
        }).done(function(response) {
          var sku = response.data;

          $('input[name="id"]').val(sku.id);
          $('input[name="id_sku"]').val(sku.id_sku);
          $('input[name="nama_sku"]').val(sku.nama_sku);
          $('input[name="berat"]').val(sku.berat);
          $('input[name="total_stok"]').val(sku.total_stok);

          $('#thumbnail').attr("src", sku.link_foto);

          $('#modal_edit_sku').modal('show');
        });
      });
    });

    $('#form-content-edit-sku').on('submit', function() {
      var form = $(this);

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize(),
        dataType: 'JSON',
      }).done(function(response) {
        noty({text: response.message, timeout: 3000, layout: 'topRight', type: 'information'});
      }).fail(function(response) {
        noty({text: response.message, timeout: 3000, layout: 'topRight', type: 'danger'});
      });

      table.ajax.reload(null, false);

      $('#modal_edit_sku').modal('hide');
      return false;
    });
  });
</script>
