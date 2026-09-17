<div class="row animated fadeIn">
    <div class="col-md-12">
        <div class="panel panel-default" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="panel-heading" style="background: linear-gradient(135deg, #1d2b64, #f87271); color: white; border-radius: 15px 15px 0 0; padding: 20px;">
                <h3 class="panel-title" style="font-weight: 700; font-size: 20px;"><i class="fa fa-truck"></i> SCAN MASUK PICKER</h3>
                <ul class="panel-controls">
                    <li><a href="#" class="panel-fullscreen"><span class="fa fa-expand"></span></a></li>
                    <li><button class="btn btn-info btn-sync" style="margin-top: -5px; border-radius: 20px;"><i class="fa fa-refresh"></i> Sinkronisasi Resi</button></li>
                </ul>
            </div>
            <div class="panel-body" style="padding: 30px;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #555;">PILIH PICKER</label>
                            <select id="id_picker" class="form-control select" data-live-search="true" style="border-radius: 10px;">
                                <option value="">-- Pilih Nama Picker --</option>
                                <?php foreach ($list_picker as $picker) : ?>
                                    <option value="<?= $picker['kode_pegawai'] ?>"><?= $picker['nama_pegawai'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #555;">PILIH PACKER</label>
                            <select id="id_packer" class="form-control select" data-live-search="true" style="border-radius: 10px;">
                                <option value="">-- Pilih Nama Packer --</option>
                                <?php foreach ($list_packer as $packer) : ?>
                                    <option value="<?= $packer['id_user'] ?>"><?= $packer['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #555;">SCAN NOMOR RESI</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="border-radius: 10px 0 0 10px; background: #eee;"><i class="fa fa-barcode"></i></span>
                                <input type="text" id="noresi" class="form-control input-lg" placeholder="Scan barcode di sini..." style="border-radius: 0 10px 10px 0; font-size: 24px; font-weight: 700; height: 60px; text-transform: uppercase;">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <div style="background: #f8f9fa; border-radius: 15px; padding: 10px; border: 1px solid #ddd;">
                            <h4 style="margin: 0; font-size: 12px; color: #888;">TOTAL SCAN HARI INI</h4>
                            <h2 id="total-scan-count" style="margin: 5px 0; font-weight: 800; color: #1d2b64;"><?= $total_scan ?></h2>
                        </div>
                    </div>
                </div>

                <hr style="border-top: 2px dashed #eee; margin: 30px 0;">

                <div class="row">
                    <div class="col-md-12">
                        <h4 style="font-weight: 700; color: #444; margin-bottom: 20px;"><i class="fa fa-history"></i> Recent Scans</h4>
                        <div class="table-responsive" style="border-radius: 10px; overflow: hidden;">
                            <table class="table table-hover" id="table-recent-scans">
                                <thead style="background: #f4f6f9;">
                                    <tr>
                                        <th width="50">No</th>
                                        <th>No. Resi</th>
                                        <th>Marketplace</th>
                                        <th>Toko</th>
                                        <th>Picker</th>
                                        <th>Packer</th>
                                        <th>Status Pesanan</th>
                                        <th>Waktu Scan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="empty-row">
                                        <td colspan="8" class="text-center" style="padding: 50px; color: #aaa;">Belum ada resi yang discan di sesi ini.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Focus to noresi on load
    $('#noresi').focus();

    // Selectpicker init (Bootstrap Select)
    if ($('.select').length > 0) {
        $('.select').selectpicker();
    }

    var scanCount = 0;

    $('#noresi').on('keypress', function(e) {
        if (e.which == 13) {
            var noresi = $(this).val().trim();
            var id_picker = $('#id_picker').val();
            var id_packer = $('#id_packer').val();

            if (id_picker == '' || id_packer == '') {
                noty({text: 'Harap pilih Picker dan Packer terlebih dahulu!', layout: 'topRight', type: 'warning', timeout: 2000});
                $(this).val('');
                return;
            }

            if (noresi != '') {
                saveScan(noresi, id_picker, id_packer);
            }
            $(this).val('');
        }
    });

    function saveScan(noresi, id_picker, id_packer) {
        $.ajax({
            url: 'inbound_picker/save_scan',
            type: 'POST',
            data: { noresi: noresi, id_picker: id_picker, id_packer: id_packer },
            dataType: 'JSON',
            success: function(resp) {
                if (resp.code == 201) {
                    playSound('success');
                    updateTable(resp.data.resi);
                    $('#total-scan-count').text(resp.data.total_scan);
                    noty({text: 'Resi ' + noresi + ' berhasil discan.', layout: 'topRight', type: 'success', timeout: 1500});
                } else {
                    // save_scan tidak mengirim EXCEPTION_CODE, jadi resi tidak
                    // ditemukan dikenali dari kalimat pesannya.
                    var teks = (resp.message || '').toUpperCase();
                    playSound(teks.indexOf('TIDAK DITEMUKAN') !== -1 ? 'tidak_ditemukan' : 'error');
                    noty({text: resp.message, layout: 'topRight', type: 'error', timeout: 3000});
                }
            },
            error: function() {
                playSound('error');
                noty({text: 'Terjadi kesalahan sistem.', layout: 'topRight', type: 'error', timeout: 3000});
            }
        });
    }

    function updateTable(resi) {
        $('#empty-row').hide();
        scanCount++;
        
        var pickerName = $('#id_picker option:selected').text();
        var packerName = $('#id_packer option:selected').text();
        var time = new Date().toLocaleTimeString();
        
        var row = '<tr class="animated slideInDown" style="background: #e8f5e9;">' +
                  '<td>' + scanCount + '</td>' +
                  '<td><strong>' + resi.noresi + '</strong></td>' +
                  '<td><span class="label label-info">' + (resi.nama_marketplace || '-') + '</span></td>' +
                  '<td>' + (resi.toko || '-') + '</td>' +
                  '<td>' + pickerName + '</td>' +
                  '<td>' + packerName + '</td>' +
                  '<td>' + (resi.status_pesanan || '-') + '</td>' +
                  '<td>' + time + '</td>' +
                  '</tr>';
        
        $('#table-recent-scans tbody').prepend(row);
        
        // Remove background color after 2 seconds
        setTimeout(function() {
            $('#table-recent-scans tbody tr').first().css('background', 'transparent');
        }, 2000);
    }

    $('.btn-sync').on('click', function() {
        var id_picker = $('#id_picker').val();
        var id_packer = $('#id_packer').val();

        if (id_picker == '' || id_packer == '') {
            noty({text: 'Harap pilih Picker dan Packer terlebih dahulu!', layout: 'topRight', type: 'warning', timeout: 2000});
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: 'inbound_picker/sync_resi',
            type: 'GET',
            data: { id_picker: id_picker, id_packer: id_packer },
            dataType: 'JSON',
            // Sinkronisasi massal bisa memakan waktu, jangan diputus lebih cepat dari server.
            timeout: 600000,
            success: function(resp) {
                if (typeof resp === 'string') resp = JSON.parse(resp);
                
                if (resp.code == 200) {
                    noty({text: resp.message, layout: 'topRight', type: 'success', timeout: 5000});
                    playSound('success');
                    // Tidak perlu location.reload(): sinkronisasi hanya mengisi tblpacking
                    // milik packer, sedangkan "TOTAL SCAN HARI INI" menghitung scan
                    // user yang sedang login. Reload penuh justru memuat ulang
                    // seluruh aplikasi dan terasa seperti sinkronisasi yang lambat.
                } else {
                    noty({text: resp.message, layout: 'topRight', type: 'error', timeout: 3000});
                }
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Sinkronisasi Resi');
            },
            error: function() {
                noty({text: 'Gagal melakukan sinkronisasi.', layout: 'topRight', type: 'error', timeout: 3000});
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Sinkronisasi Resi');
            }
        });
    });

    // type: 'success' | 'error' | 'tidak_ditemukan' (ucapan "tidak ditemukan",
    // sama dengan halaman scan lain).
    function playSound(type) {
        var id = 'audio-fail';
        if (type == 'success') id = 'audio-alert';
        else if (type == 'tidak_ditemukan') id = 'audio-tidak-ditemukan';

        // suaraScan memotong durasi dan mereset posisi, jadi scan beruntun
        // tidak menunggu suara scan sebelumnya selesai.
        if (typeof suaraScan === 'function') {
            suaraScan(id);
            return;
        }

        var audio = document.getElementById(id);
        if (audio) {
            audio.currentTime = 0;
            audio.play();
        }
    }
});
</script>

<style>
.input-lg:focus {
    border-color: #1d2b64;
    box-shadow: 0 0 15px rgba(29, 43, 100, 0.2);
}
.select2-container--default .select2-selection--single {
    border-radius: 10px;
    height: 40px;
    padding-top: 5px;
}
.jogging-text {
    font-family: 'Montserrat', sans-serif;
}
@keyframes jogging {
    0% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0); }
}
</style>
