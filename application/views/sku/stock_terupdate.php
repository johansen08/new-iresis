<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Stok Terupdate (Dari Upload SKU)</h3>
      </div>
      <div class="panel-body">
        <div class="alert alert-info">
            <p><i class="fa fa-info-circle"></i> Data stok di bawah ini bersumber dari hasil **Upload SKU** terakhir.</p>
        </div>
        <table class="table table-hover datatable-stock-terupdate">
          <thead>
            <tr>
              <th>ID SKU</th>
              <th>Nama SKU</th>
              <th>No Rak</th>
              <th>Total Stok</th>
              <th>Terakhir Update</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    var table = $('.datatable-stock-terupdate').DataTable({
      'scrollX': true,
      'pageLength': 25,
      'processing': true,
      'serverSide': true,
      'order': [
        [4, 'desc']
      ],
      'lengthMenu': [
        [25, 50, 100, 200],
        [25, 50, 100, 200]
      ],
      'ajax': {
        url: 'sku/get_stock_data',
        type: 'POST',
      },
      'columns': [
        { "data": 0 },
        { "data": 1 },
        { "data": 2 },
        { "data": 3 },
        { "data": 4 }
      ]
    });
  });
</script>
