<div class="row">
    <div class="col-md-12">
        <form action="email_blast/save" class="form-horizontal" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= empty($email_blast) ? '0' : $email_blast['id'] ?>" />

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong><?= $action ?></strong></h3>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Email Code</label>
                        <div class="col-md-2 col-xs-12">
                            <input type="text" name="code" class="form-control" value="<?= empty($email_blast) ? '' : $email_blast['code'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Subject</label>
                        <div class="col-md-7 col-xs-12">
                            <input type="text" name="subject" class="form-control" value="<?= empty($email_blast) ? '' : $email_blast['subject'] ?>" require />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Body Email</label>
                        <div class="col-md-10 col-xs-12">
                            <textarea name="body_mail" class="summernote_long" height="500px"><?= empty($email_blast) ? '' : $email_blast['body_mail'] ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <a href="email_blast" type="reset" class="btn btn-info link">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>