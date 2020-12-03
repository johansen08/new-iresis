<div class="row">
    <div class="col-md-12">
        <form action="email_blast/recipients_init" class="form-horizontal" method="post">
            <input type="hidden" name="email_blast_id" value="<?= $email_blast['id'] ?>">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Upload data recipients</h3>
                </div>

                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Upload type</label>
                        <div class="col-md-2 col-xs-12">
                            <label class="check"><input type="radio" class="iradio" name="upload_type" value="<?= SEND_EMAIL_ALL ?>" /> All Muniyo panelists</label>
                        </div>

                        <div class="col-md-2 col-xs-12">
                            <label class="check"><input type="radio" class="iradio" name="upload_type" value="<?= SEND_EMAIL_PARTIAL ?>" /> Partial recipients</label>
                        </div>
                    </div>

                    <div class="form-group" id="upload-partial" style="display: none;">
                        <label class="col-md-2 col-xs-12 control-label">Upload file</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="file" class="fileinput btn-info" name="file_partial" />
                            <span class="help-block">File type .csv</span>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Submit</button>
                        <a href="email_blast/recipients_reset/<?= $email_blast['id'] ?>" type="reset" class="btn btn-info link">Reset</a>
                        <a href="email_blast" type="reset" class="btn btn-info link">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">List of recipients : <strong><?= $email_blast['subject'] ?></strong></h3>
            </div>

            <div class="panel-body">
                <p>
                    
                </p>
                <table class="table table-striped datatable-recipients">
                    <thead>
                        <tr>
                            <th>UID</th>
                            <th>Username</th>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Sent Date</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.datatable-recipients').DataTable({
        'scrollX': true,
        'pageLength' : 10,
        'processing' : true,
        'serverSide' : true,
        'order' : [[0, 'asc' ]],
        'ajax' :{
            url : 'email_blast/get_data_recipients',
            type : 'POST',
            data : function (d) {
                d.email_blast_id = $('input[name=email_blast_id]').val();
            }
        },
        'language': {
            'searchPlaceholder' : 'UID / Username / Firstname / Lastname / Email / Status',
        },
    });
});

$('input[type=radio][name=upload_type]').on('ifChanged', function() {
    switch ($(this).val()) {
        case '<?= SEND_EMAIL_ALL ?>':
            $('#upload-partial').hide();
            break;
        case '<?= SEND_EMAIL_PARTIAL ?>':
            $('input[name=file_partial]').val(null);
            $('#upload-partial').show();
            break;
    }
});
</script>