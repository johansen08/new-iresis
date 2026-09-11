<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Approval Pengembalian Barang (Tim Support)</h3>
                <ul class="panel-controls">
                    <li><button class="btn btn-success" onclick="approveAll()"><i class="fa fa-check-circle"></i> Acc Semua</button></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table id="table_approval" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>No Rak</th>
                                <th>Kondisi</th>
                                <th>Submitted By</th>
                                <th class="text-center">Action</th>
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
    var table_app;
    $(document).ready(function() {
        table_app = $('#table_approval').DataTable({
            "processing": true,
            "ajax": {
                "url": "qc_return/get_data_approval",
                "type": "POST"
            }
        });
    });

    function approve(id) {
        if(confirm('Setujui pengembalian barang ini?')) {
            updateStatus(id, 'APPROVED');
        }
    }

    function approveAll() {
        if(confirm('Setujui SEMUA pengembalian barang yang pending?')) {
            $.ajax({
                url: 'qc_return/do_approve_all',
                type: 'POST',
                dataType: 'JSON',
                success: function(res) {
                    if(res.success) {
                        noty({text: 'All items approved successfully', layout: 'topRight', type: 'success', timeout: 3000});
                        table_app.ajax.reload();
                    } else {
                        noty({text: 'Failed to approve items', layout: 'topRight', type: 'error', timeout: 3000});
                    }
                }
            });
        }
    }

    function reject(id) {
        if(confirm('Tolak pengembalian barang ini?')) {
            updateStatus(id, 'REJECTED');
        }
    }

    function giveaway(id) {
        if(confirm('Proses barang ini sebagai GIVEAWAY?')) {
            updateStatus(id, 'GIVEAWAY');
        }
    }

    function tidakAda(id) {
        if(confirm('Tandai barang ini sebagai TIDAK ADA?')) {
            updateStatus(id, 'BARANG_TIDAK_ADA');
        }
    }

    function updateStatus(id, status) {
        $.ajax({
            url: 'qc_return/do_approve',
            type: 'POST',
            data: {id: id, status: status},
            dataType: 'JSON',
            success: function(res) {
                if(res.success) {
                    noty({text: 'Status updated successfully', layout: 'topRight', type: 'success', timeout: 3000});
                    table_app.ajax.reload();
                } else {
                    noty({text: 'Failed to update status', layout: 'topRight', type: 'error', timeout: 3000});
                }
            }
        });
    }
</script>
