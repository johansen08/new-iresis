<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default tabs">
            <ul class="nav nav-tabs nav-justified">
                <li class="<?= $active_tab == 'packer' ? 'active' : '' ?>"><a href="#tab-packer" data-toggle="tab">Lost Scan Packer</a></li>
                <li class="<?= $active_tab == 'picker' ? 'active' : '' ?>"><a href="#tab-picker" data-toggle="tab">Lost Scan Picker</a></li>
                <li class="<?= $active_tab == 'ho' ? 'active' : '' ?>"><a href="#tab-ho" data-toggle="tab">Lost Scan HO</a></li>
            </ul>
            <div class="panel-body tab-content">
                <!-- ==================== TAB 1: PACKER ==================== -->
                <div class="tab-pane fade <?= $active_tab == 'packer' ? 'in active' : '' ?>" id="tab-packer">
                    <div class="row">
                        <div class="col-md-6 center-block float-none">
                            <form action="lost_scan_packer/save" class="form-horizontal nojs" id="form_lost_scan_packer" autocomplete="off">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <input type="hidden" name="lost_type" value="PACKER" />
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nama Karyawan</label>
                                            <div class="col-md-8">
                                                <select name="nama_petugas" class="form-control select" data-live-search="true" required>
                                                    <option value="">Pilih Petugas</option>
                                                    <?php foreach ($list_packer as $packer) : ?>
                                                        <?php $display_name = $packer['nama_pegawai'] . ' - ' . $packer['kode_pegawai']; ?>
                                                        <option value="<?= $packer['nama_pegawai'] ?>" <?= $packer['nama_pegawai'] == $user_fullname ? 'selected' : '' ?>>
                                                            <?= $display_name ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nomor Resi</label>
                                            <div class="col-md-8">
                                                <input type="text" name="noresi" id="noresi_packer" class="form-control" placeholder="Scan/Input Resi" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <button type="submit" class="btn btn-primary pull-right">Simpan</button>
                                        <a href="lost_scan_packer/report" class="btn btn-info link"><i class="fa fa-list"></i> Lihat Laporan</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB 2: PICKER ==================== -->
                <div class="tab-pane fade <?= $active_tab == 'picker' ? 'in active' : '' ?>" id="tab-picker">
                    <div class="row">
                        <div class="col-md-6 center-block float-none">
                            <form action="lost_scan_packer/save" class="form-horizontal nojs" id="form_lost_scan_picker" autocomplete="off">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <input type="hidden" name="lost_type" value="PICKER" />
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nama Karyawan</label>
                                            <div class="col-md-8">
                                                <select name="nama_petugas" class="form-control select" data-live-search="true" required>
                                                    <option value="">Pilih Petugas</option>
                                                    <?php foreach ($list_picker as $picker) : ?>
                                                        <?php $display_name = $picker['nama_pegawai'] . ' - ' . $picker['kode_pegawai']; ?>
                                                        <option value="<?= $picker['nama_pegawai'] ?>" <?= $picker['nama_pegawai'] == $user_fullname ? 'selected' : '' ?>>
                                                            <?= $display_name ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nomor Resi</label>
                                            <div class="col-md-8">
                                                <input type="text" name="noresi" id="noresi_picker" class="form-control" placeholder="Scan/Input Resi" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <button type="submit" class="btn btn-primary pull-right">Simpan</button>
                                        <a href="lost_scan_packer/report" class="btn btn-info link"><i class="fa fa-list"></i> Lihat Laporan</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB 3: HO ==================== -->
                <div class="tab-pane fade <?= $active_tab == 'ho' ? 'in active' : '' ?>" id="tab-ho">
                    <div class="row">
                        <div class="col-md-6 center-block float-none">
                            <form action="lost_scan_packer/save" class="form-horizontal nojs" id="form_lost_scan_ho" autocomplete="off">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <input type="hidden" name="lost_type" value="HO" />
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nama Karyawan</label>
                                            <div class="col-md-8">
                                                <select name="nama_petugas" class="form-control select" data-live-search="true" required>
                                                    <option value="">Pilih Petugas</option>
                                                    <?php foreach ($list_ho as $ho) : ?>
                                                        <?php $display_name = $ho['nama_pegawai'] . ' - ' . $ho['kode_pegawai']; ?>
                                                        <option value="<?= $ho['nama_pegawai'] ?>" <?= $ho['nama_pegawai'] == $user_fullname ? 'selected' : '' ?>>
                                                            <?= $display_name ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Nomor Resi</label>
                                            <div class="col-md-8">
                                                <input type="text" name="noresi" id="noresi_ho" class="form-control" placeholder="Scan/Input Resi" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <button type="submit" class="btn btn-primary pull-right">Simpan</button>
                                        <a href="lost_scan_packer/report" class="btn btn-info link"><i class="fa fa-list"></i> Lihat Laporan</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        if ($(".select").length > 0) {
            $(".select").selectpicker();
        }

        // Dulu tiap simpan bikin `new Audio(url)` baru. Elemen baru berarti
        // browser mengambil dan men-decode berkasnya lagi tiap scan -- itu
        // delay yang paling terasa di sini, apalagi error.mp3 yang ~3,8 detik
        // dan diputar sampai habis. Sekarang pakai elemen preload di main.php
        // lewat suaraScan, yang juga memotong durasinya.
        function playAudio(id) {
            if (typeof suaraScan === 'function') {
                suaraScan(id);
                return;
            }
            var el = document.getElementById(id);
            if (el) {
                el.currentTime = 0;
                el.play();
            }
        }

        function handleFormSubmit(formId, resiId) {
            $("#" + formId).validate({
                submitHandler: function(form) {
                    var formData = $(form).serialize();
                    $.ajax({
                        url: form.action,
                        type: 'POST',
                        data: formData,
                        success: function(data) {
                            if (data.data && data.data.status === 201) {
                                playAudio('audio-alert');
                                noty({text: 'Data berhasil disimpan', layout: 'topRight', type: 'success', timeout: 3000});
                                $("#" + resiId).val('').focus();
                            } else {
                                playAudio('audio-error');
                                noty({text: data.message, layout: 'topRight', type: 'error', timeout: 3000});
                                $("#" + resiId).select().focus();
                            }
                        },
                        error: function() {
                            playAudio('audio-error');
                            noty({text: 'Internal Server Error', layout: 'topRight', type: 'error', timeout: 3000});
                            $("#" + resiId).select().focus();
                        }
                    });
                    return false;
                }
            });
        }

        handleFormSubmit("form_lost_scan_packer", "noresi_packer");
        handleFormSubmit("form_lost_scan_picker", "noresi_picker");
        handleFormSubmit("form_lost_scan_ho", "noresi_ho");

        $("#noresi_packer, #noresi_picker, #noresi_ho").on('keypress', function(e) {
            if (e.which == 13) {
                if ($(this).val().trim() !== "") {
                    $(this).closest('form').submit();
                }
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (e.target.hash === '#tab-packer') {
                $('#noresi_packer').focus();
            } else if (e.target.hash === '#tab-picker') {
                $('#noresi_picker').focus();
            } else if (e.target.hash === '#tab-ho') {
                $('#noresi_ho').focus();
            }
        });

        $("#noresi_<?= $active_tab ?>").focus();
    });
</script>

<style>
.center-block {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.float-none {
    float: none !important;
}
</style>
