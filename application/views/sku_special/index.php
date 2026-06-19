<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Master Special SKU</strong></h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped datatable-sku-special">
                    <thead>
                        <tr>
                            <th width="10%">ID SKU</th>
                            <th>Nama SKU</th>
                            <th width="20%">Status Special</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        const table = $('.datatable-sku-special').DataTable({
            'scrollX': true,
            'pageLength': 10,
            'processing': true,
            'serverSide': true,
            'order': [
                [0, 'desc']
            ],
            'ajax': {
                url: 'sku_special/get_skus_data',
                type: 'POST',
            }
        });

        $(document).on('change', '.select-is-special', function() {
            const id_sku = $(this).data('id');
            const is_special = $(this).val();
            const $select = $(this);

            $select.attr('disabled', true);

            $.ajax({
                url: 'sku_special/update_status',
                type: 'POST',
                data: {
                    id_sku: id_sku,
                    is_special: is_special
                },
                dataType: 'json',
                success: function(response) {
                    noty({
                        text: response.message,
                        layout: 'topRight',
                        type: 'success',
                        timeout: 2000
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Gagal mengupdate status';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        errorMessage = res.message || errorMessage;
                    } catch (e) {}
                    
                    noty({
                        text: errorMessage,
                        layout: 'topRight',
                        type: 'error',
                        timeout: 3000
                    });
                    
                    // Revert on error? Maybe just reload table
                    table.ajax.reload(null, false);
                },
                complete: function() {
                    $select.attr('disabled', false);
                }
            });
        });
    });
</script>
