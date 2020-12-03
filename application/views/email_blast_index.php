<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">List of email blast</h3>
            </div>
            <div class="panel-body">
                <p>
                    <a href="email_blast/edit" class="btn btn-info link"><i class="fa fa-plus"></i> Add new email blast</a>
                </p>
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Email Code</th>
                            <th>Subject</th>
                            <th>Created</th>
                            <th>Created By</th>
                            <th>Published On</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($list_email_blast as $email_blast): ?>
                        <?php $status = array_search($email_blast['status'], EMAIL_BLAST_STATUS); ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= $email_blast['code'] ?></td>
                            <td><?= $email_blast['subject'] ?></td>
                            <td><?= $email_blast['created_date'] ?></td>
                            <td><?= $email_blast['created_by_name'] ?></td>
                            <td><?= $email_blast['publish_date'] ?></td>
                            <td><?= empty($status) ? 'DRAFT' : $status ?></td>
                            <td class="text-center">
                                <a href="email_blast/edit/<?= $email_blast['id'] ?>" class="btn btn-info link" title="Edit"><i class="fa fa-edit"></i> </a>
                                <a href="email_blast/delete/<?= $email_blast['id'] ?>" class="btn btn-info confirm" title="Delete"><i class="fa fa-trash-o"></i> </a>
                                <a href="email_blast/preview/<?= $email_blast['id'] ?>" type="reset" class="btn btn-info link" title="Preview"><i class="fa fa-eye"></i> </a>
                                <a href="email_blast/recipients/<?= $email_blast['id'] ?>" type="reset" class="btn btn-info link" title="List of recipients"><i class="fa fa-list"></i> </a>
                                <a href="email_blast/report/<?= $email_blast['id'] ?>" type="reset" class="btn btn-info link" title="Report"><i class="fa fa-file-text-o"></i> </a>

                                <a href="email_blast/send/<?= $email_blast['id'] ?>" type="reset" class="btn btn-info confirm" data-message="Are you sure want to send the email?" title="Send" <?= empty($email_blast['status']) ? '' : 'disabled' ?>><i class="fa fa-send"></i> </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>