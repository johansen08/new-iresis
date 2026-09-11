<div class="row">
    <div class="col-md-12">
        <form action="user/save_user" class="form-horizontal" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_user" value="<?= empty($user) ? '0' : $user['id_user'] ?>" />

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong><?= $action ?></strong></h3>
                </div>

                <div class="panel-body">

                    <h4>Informasi Pengguna</h4>

                    <div class="form-group text-center">
                        <div style="display: inline-block;">
                            <?php 
                                $foto_url = base_url('assets/img/no-image.jpg');
                                if (!empty($user['foto'])) {
                                    $foto_url = (strpos($user['foto'], 'http') === 0) ? $user['foto'] : base_url($user['foto']);
                                }
                            ?>
                            <a href="<?= $foto_url ?>" class="gallery-item" title="Foto Profil: <?= $user['username'] ?? 'User' ?>" data-gallery>
                                <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #f5f5f5; cursor: pointer;">
                                    <img id="preview_foto" src="<?= $foto_url ?>" 
                                         style="width: 100%; height: 100%; object-fit: cover;" 
                                         onerror="this.src='<?= base_url('assets/img/no-image.jpg') ?>'"/>
                                </div>
                            </a>
                            <p style="margin-top: 10px;"><small>Klik foto untuk memperbesar</small></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Username</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= empty($user) ? '' : $user['username'] ?>" required />
                        </div>
                    </div>

                    <?php if (empty($user)) : ?>
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

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="col-md-3 col-xs-12 control-label">Name</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="name" class="form-control" value="<?= empty($user) ? '' : $user['name'] ?>" required />
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="col-md-3 col-xs-12 control-label">Email</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="email" class="form-control" value="<?= empty($user) ? '' : $user['email'] ?>" />
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 40px;">
                        <label class="col-md-3 col-xs-12 control-label">Role</label>
                        <div class="col-md-4 col-xs-12">
                            <select name="hakakses" class="form-control select" required>
                                <option></option>
                                <?php foreach ($list_role as $role) : ?>
                                    <option value="<?= $role['id_hakakses'] ?>" <?= !empty($user) && ($user['hakakses'] == $role['id_hakakses']) ? 'selected' : '' ?>><?= $role['akses'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 40px;">
                        <label class="col-md-3 col-xs-12 control-label">User Photo</label>
                        <div class="col-md-6 col-xs-12">
                            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 10px;">
                                <li class="active"><a href="#tab-upload" role="tab" data-toggle="tab">Upload File</a></li>
                                <li><a href="#tab-link" role="tab" data-toggle="tab">Paste Link URL</a></li>
                            </ul>
                            <div class="tab-content" style="padding: 15px 0;">
                                <div class="tab-pane active" id="tab-upload">
                                    <input type="file" name="foto_file" class="fileinput btn-info" id="input_file_foto" title="Pilih File Foto"/>
                                    <span class="help-block">Format: JPG, PNG. Maks: 2MB.</span>
                                </div>
                                <div class="tab-pane" id="tab-link">
                                    <input type="text" id="input_foto" name="foto" class="form-control" value="<?= empty($user) ? '' : $user['foto'] ?>" placeholder="Link Google Drive, Dropbox, atau lainnya"/>
                                    <span class="help-block">Copy-paste link foto di sini. Otomatis support Google Drive.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        // File preview
                        document.getElementById('input_file_foto').addEventListener('change', function(e) {
                            if (e.target.files && e.target.files[0]) {
                                var reader = new FileReader();
                                reader.onload = function(ex) {
                                    document.getElementById('preview_foto').src = ex.target.result;
                                    // Update gallery link as well
                                    var gallLink = document.querySelector('.gallery-item');
                                    if(gallLink) gallLink.href = ex.target.result;
                                }
                                reader.readAsDataURL(e.target.files[0]);
                            }
                        });

                        // URL link preview
                        document.getElementById('input_foto').addEventListener('input', function() {
                            var url = this.value.trim();
                            var preview = document.getElementById('preview_foto');
                            var gallLink = document.querySelector('.gallery-item');
                            
                            if (url === "") {
                                preview.src = '<?= base_url('assets/img/no-image.jpg') ?>';
                                if(gallLink) gallLink.href = preview.src;
                                return;
                            }

                            if (url.includes('drive.google.com')) {
                                var id = '';
                                var rk = '';
                                var idMatch = url.match(/\/file\/d\/([^\/\?]+)/) || url.match(/[?&]id=([^&]+)/);
                                if (idMatch) id = idMatch[1];
                                var rkMatch = url.match(/[?&]resourcekey=([^&]+)/);
                                if (rkMatch) rk = rkMatch[1];
                                if (id) {
                                    url = "https://drive.google.com/thumbnail?id=" + id + (rk ? "&resourcekey=" + rk : "") + "&sz=w1000";
                                }
                            }
                            
                            preview.src = url;
                            if(gallLink) gallLink.href = url;
                        });
                    </script>

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label"></label>
                        <div class="col-md-4 col-xs-12">
                            <label class="check"><input type="checkbox" name="bypass" class="icheckbox" value="1" <?= (!empty($user) && $user['bypass']) ? 'checked' : null ?> /> Bypass password saat login?</label>
                        </div>
                    </div>

                    <hr>

                    <h4>Informasi pegawai</h4>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label class="col-md-3 col-xs-12 control-label">Nama Pegawai</label>
                        <div class="col-md-4 col-xs-12">
                            <input type="text" name="nama_pegawai" class="form-control" value="<?= empty($employee) ? '' : $employee['nama_pegawai'] ?>" require <?= empty($user) ? '' : 'disabled' ?> />
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label class="col-md-3 col-xs-12 control-label">Status</label>
                        <div class="col-md-4 col-xs-12">
                            <select name="status_aktif" class="form-control select" <?= empty($user) ? '' : 'disabled' ?>>
                                <?php foreach ($list_status as $status) : ?>
                                    <option value="<?= $status ?>" <?= !empty($employee) && ($employee['status_aktif'] == $status) ? 'selected' : '' ?>><?= $status ?></option>
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