<div class="row">
    <div class="col-md-12">
        <form action="save-user" class="form-horizontal" method="post">
            <input type="hidden" name="id" value="<?= empty($user) ? '0' : $user['id'] ?>" />

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong><?= $action ?></strong></h3>
                </div>

                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Username</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= empty($user) ? '' : $user['username'] ?>" require />
                        </div>
                    </div>

                    <?php if (empty($user)): ?>
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Password</label>
                        <div class="col-md-4 col-xs-12">
                            <div class="input-group">
                                <input type="text" name="password" class="form-control" />
                                <span class="input-group-btn">
                                    <button id="btn_generate_password" type="button" class="btn btn-default"><i class="fa fa-gears"></i> </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Name</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="name" class="form-control" value="<?= empty($user) ? '' : $user['name'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Email</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="email" class="form-control" value="<?= empty($user) ? '' : $user['email'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Role</label>
                        <div class="col-md-4 col-xs-12">
                            <select name="roleid" class="form-control select">
                                <option></option>
                                <?php foreach ($list_role as $role): ?>
                                <option value="<?= $role['id'] ?>" <?= !empty($user) && ($user['roleid'] == $role['id']) ? 'selected' : '' ?>><?= $role['paramvalue1'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <button type="submit" class="btn btn-info">Submit</button>
                    <button type="reset" class="btn btn-primary">Reset</button>

                    <a href="user" type="reset" class="btn btn-default pull-right link">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
