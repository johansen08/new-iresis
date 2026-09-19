<div class="row">
    <div class="col-md-7 center-block float-none">
        <form action="receipt/upload-receipt-action" method="post" enctype="multipart/form-data" class="form-horizontal" id="form_upload_resi_jubelio" autocomplete="off">
            <input type="hidden" name="token" id="upload_token" value="">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong>Upload File Dari Jubelio</strong></h3>
                </div>

                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">Upload File</label>
                        <div class="col-md-9 col-xs-12">
                            <input id="receipt_file" class="form-control" type="file" name="receiptFile" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                            <span class="help-block" id="info_file">Pilih file laporan penjualan Jubelio (.xlsx / .xls).</span>
                        </div>
                    </div>

                    <!-- Panel status: sembunyi sampai upload dimulai -->
                    <div id="panel_status" style="display:none;">
                        <div class="progress" style="margin-bottom:8px;">
                            <div id="bar_progres" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" style="width:0%; min-width:3em;">0%</div>
                        </div>
                        <div class="tile tile-default" id="div_container_latest_receipt" style="margin-bottom:8px;">
                            <span id="ikon_status" class="fa fa-spinner fa-spin"></span>
                            <span id="span_latest_receipt">-</span>
                            <p style="margin:6px 0 0;"><small id="p_latest_receipt_message"></small></p>
                        </div>
                        <ul id="log_tahap" class="list-unstyled text-muted" style="font-size:12px; max-height:140px; overflow-y:auto; margin:0;"></ul>
                    </div>

                </div>

                <div class="panel-footer">
                    <button type="submit" class="btn btn-info" id="btn_submit"><span class="fa fa-upload"></span> Upload</button>
                    <button type="reset" class="btn btn-primary pull-right" id="btn_reset">Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
(function () {
    // Semua suara di halaman ini lewat sini, jangan panggil .play() langsung.
    // suaraScan (main.php) memotong durasi dan mereset posisi, jadi aksi
    // berikutnya tidak menunggu suara sebelumnya selesai.
    function playAudio(id, opsi) {
        if (typeof suaraScan === 'function') {
            suaraScan(id, opsi);
            return;
        }
        var el = document.getElementById(id);
        if (el) {
            try { el.currentTime = 0; } catch (err) {}
            el.play();
        }
    }

    function notif(teks, tipe, timeout) {
        if (typeof noty !== 'function') return;
        noty({ text: teks, type: tipe, layout: 'topRight', theme: 'relax', timeout: timeout === undefined ? 6000 : timeout });
    }

    function formatUkuran(byte) {
        if (byte >= 1048576) return (byte / 1048576).toFixed(1).replace('.', ',') + ' MB';
        if (byte >= 1024) return Math.round(byte / 1024) + ' KB';
        return byte + ' B';
    }

    function buatToken() {
        var s = '';
        var abc = 'abcdefghijklmnopqrstuvwxyz0123456789';
        for (var i = 0; i < 24; i++) s += abc.charAt(Math.floor(Math.random() * abc.length));
        return s;
    }

    var $form   = $('#form_upload_resi_jubelio');
    var $file   = $('#receipt_file');
    var $submit = $('#btn_submit');
    var $reset  = $('#btn_reset');
    var $bar    = $('#bar_progres');
    var $tile   = $('#div_container_latest_receipt');
    var $ikon   = $('#ikon_status');
    var $teks   = $('#span_latest_receipt');
    var $sub    = $('#p_latest_receipt_message');
    var $log    = $('#log_tahap');

    var sedangProses = false;
    var pollTimer = null;
    var pesanTahapTerakhir = '';
    var tokenAktif = '';

    // ---- Tampilan ---------------------------------------------------------

    function setBar(persen, warna) {
        persen = Math.max(0, Math.min(100, Math.round(persen || 0)));
        $bar.css('width', persen + '%').text(persen + '%')
            .removeClass('progress-bar-info progress-bar-success progress-bar-danger progress-bar-warning')
            .addClass('progress-bar-' + (warna || 'info'));
    }

    function setStatus(mode, judul, sub) {
        $('#panel_status').show();
        $tile.removeClass('tile-default tile-success tile-danger bg-success bg-danger');
        $ikon.attr('class', '');
        if (mode === 'proses') {
            $tile.addClass('tile-default');
            $ikon.addClass('fa fa-spinner fa-spin');
            $bar.addClass('progress-bar-striped active');
        } else if (mode === 'sukses') {
            $tile.addClass('tile-success bg-success');
            $ikon.addClass('fa fa-check-circle');
            $bar.removeClass('progress-bar-striped active');
        } else {
            $tile.addClass('tile-danger bg-danger');
            $ikon.addClass('fa fa-times-circle');
            $bar.removeClass('progress-bar-striped active');
        }
        $teks.text(' ' + judul);
        $sub.text(sub || '');
    }

    function catatTahap(pesan) {
        if (!pesan || pesan === pesanTahapTerakhir) return;
        pesanTahapTerakhir = pesan;
        var jam = new Date().toTimeString().substr(0, 8);
        $log.append($('<li>').text('[' + jam + '] ' + pesan));
        $log.scrollTop($log[0].scrollHeight);
    }

    function kunciForm(kunci) {
        sedangProses = kunci;
        $submit.prop('disabled', kunci);
        $reset.prop('disabled', kunci);
        $file.prop('disabled', kunci);
        if (kunci) {
            $submit.html('<span class="fa fa-spinner fa-spin"></span> Sedang diproses...');
        } else {
            $submit.html('<span class="fa fa-upload"></span> Upload');
        }
    }

    function selesaiSukses(pesan) {
        hentikanPolling();
        setBar(100, 'success');
        setStatus('sukses', 'Upload berhasil', pesan);
        catatTahap('Selesai: ' + pesan);
        notif('<strong><i class="fa fa-check"></i> Upload resi berhasil</strong><br/>' + $('<div>').text(pesan).html(), 'success', 10000);
        playAudio('audio-alert');
        kunciForm(false);
        $form[0].reset();
        $('#info_file').text('Pilih file laporan penjualan Jubelio (.xlsx / .xls).');
    }

    function selesaiGagal(pesan) {
        hentikanPolling();
        setBar(100, 'danger');
        setStatus('gagal', 'Upload gagal', pesan);
        catatTahap('Gagal: ' + pesan);
        notif('<strong><i class="fa fa-exclamation-triangle"></i> Upload resi gagal</strong><br/>' + $('<div>').text(pesan).html(), 'error', 15000);
        playAudio('audio-fail');
        kunciForm(false);
    }

    // ---- Polling progres server -----------------------------------------

    function mulaiPolling(token) {
        hentikanPolling();
        pollTimer = setInterval(function () { cekProgres(token, false); }, 1500);
    }

    function hentikanPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // finalisasi=true: koneksi upload sudah putus, progres server jadi satu-
    // satunya sumber kebenaran; tunggu sampai status selesai/gagal.
    function cekProgres(token, finalisasi) {
        $.ajax({
            url: 'receipt/upload-receipt-progress',
            type: 'GET',
            data: { token: token, _: Date.now() },
            dataType: 'json',
            global: false,
            timeout: 10000
        }).done(function (res) {
            // Abaikan jawaban polling yang datang setelah upload dinyatakan selesai
            if (!sedangProses || !res || !res.data || token !== tokenAktif) return;
            var p = res.data;
            if (p.status === 'selesai') {
                selesaiSukses(p.hasil || p.pesan);
            } else if (p.status === 'gagal') {
                selesaiGagal(p.pesan);
            } else if (p.status === 'proses') {
                if (typeof p.persen === 'number') setBar(p.persen, 'info');
                setStatus('proses', 'Upload sedang diproses', p.pesan);
                catatTahap(p.pesan);
            } else if (finalisasi) {
                selesaiGagal('Tidak ada catatan proses di server untuk upload ini.');
            }
        });
    }

    // ---- Submit ------------------------------------------------------------

    $file.on('change', function () {
        var f = this.files && this.files[0];
        if (!f) { $('#info_file').text('Pilih file laporan penjualan Jubelio (.xlsx / .xls).'); return; }
        var eks = (f.name.split('.').pop() || '').toLowerCase();
        if (eks !== 'xlsx' && eks !== 'xls') {
            $('#info_file').html('<span class="text-danger">File .' + $('<div>').text(eks).html() + ' tidak didukung, pilih .xlsx atau .xls.</span>');
            notif('Format file tidak didukung, pilih file .xlsx atau .xls dari Jubelio.', 'warning');
            playAudio('audio-fail');
            this.value = '';
            return;
        }
        $('#info_file').text(f.name + ' (' + formatUkuran(f.size) + ')');
    });

    $form.validate({
        ignore: [],
        rules: { receiptFile: { required: true } },
        messages: { receiptFile: { required: 'Silahkan Input File Terlebih Dahulu!' } },
        invalidHandler: function () {
            playAudio('audio-fail');
            notif('Pilih file Excel terlebih dahulu.', 'warning', 4000);
        },
        submitHandler: function (form) {
            if (sedangProses) return false;

            var f = $file[0].files && $file[0].files[0];
            var token = buatToken();
            tokenAktif = token;
            $('#upload_token').val(token);
            pesanTahapTerakhir = '';
            $log.empty();

            // FormData WAJIB dibuat sebelum kunciForm(): kontrol yang disabled
            // tidak ikut diserialisasi, jadi kalau dibalik $_FILES di server kosong
            // dan muncul "Tidak ada file yang dipilih".
            var formData = new FormData(form);
            var sudahPolling = false;

            kunciForm(true);
            setBar(0, 'info');
            setStatus('proses', 'Upload sedang diproses', 'Mengunggah ' + (f ? f.name + ' (' + formatUkuran(f.size) + ')' : 'file') + ' ke server...');
            catatTahap('Mengunggah file ke server...');
            notif('<strong><i class="fa fa-spinner fa-spin"></i> Upload sedang diproses</strong><br/>Jangan tutup halaman ini sampai selesai.', 'information', 5000);

            // Jaga-jaga kalau browser tidak memicu event progress unggah sama
            // sekali (file kecil): mulai polling 3 detik setelah submit.
            setTimeout(function () {
                if (!sudahPolling && sedangProses && tokenAktif === token) {
                    sudahPolling = true;
                    mulaiPolling(token);
                }
            }, 3000);

            $.ajax({
                url: form.action,
                type: 'post',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                global: false,   // jangan pancing handler error global plugins.js
                timeout: 1800000, // 30 menit untuk file sangat besar
                xhr: function () {
                    var xhr = $.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function (e) {
                            if (!e.lengthComputable) return;
                            var persenKirim = e.loaded / e.total * 100;
                            // Tahap unggah = 0..10% dari bar; sisanya progres server
                            setBar(persenKirim / 10, 'info');
                            if (persenKirim < 100) {
                                $sub.text('Mengunggah file ke server... ' + Math.round(persenKirim) + '%');
                            } else if (!sudahPolling) {
                                sudahPolling = true;
                                setBar(10, 'info');
                                setStatus('proses', 'Upload sedang diproses', 'File diterima server, menunggu proses impor dimulai...');
                                catatTahap('File diterima server, mulai diproses.');
                                mulaiPolling(token);
                            }
                        });
                    }
                    return xhr;
                }
            })
            .done(function (res) {
                if (res && res.status === 401) {
                    selesaiGagal('Session habis, silakan login ulang.');
                    return;
                }
                if (res && (res.code === 201 || res.code === 200)) {
                    selesaiSukses(res.message);
                } else {
                    selesaiGagal((res && res.message) ? res.message : 'Respons server tidak dikenali.');
                }
            })
            .fail(function (xhr, statusTeks) {
                // Server mungkin masih memproses (koneksi putus / proxy timeout).
                // Jangan langsung bilang gagal: cek berkas progres dulu.
                var pesan = 'Koneksi ke server terputus';
                if (statusTeks === 'timeout') pesan = 'Menunggu respons server terlalu lama';
                else if (xhr.status && xhr.status !== 200) pesan = 'Server menjawab HTTP ' + xhr.status;
                else if (statusTeks === 'parsererror') pesan = 'Respons server bukan JSON (ada error PHP?)';

                if (statusTeks === 'parsererror') {
                    console.error('Respons mentah upload resi:', xhr.responseText);
                    selesaiGagal(pesan + '. Detail ada di console browser.');
                    return;
                }

                catatTahap(pesan + ', memeriksa status proses di server...');
                setStatus('proses', 'Upload sedang diproses', pesan + ', memeriksa status proses di server...');
                hentikanPolling();
                var percobaan = 0;
                pollTimer = setInterval(function () {
                    percobaan++;
                    cekProgres(token, true);
                    if (percobaan >= 400) { // ~10 menit
                        selesaiGagal(pesan + ' dan status proses tidak kunjung selesai. Cek Daftar Resi sebelum mengunggah ulang.');
                    }
                }, 1500);
            });

            return false;
        }
    });

    $reset.on('click', function () {
        if (sedangProses) return false;
        $('#panel_status').hide();
        $('#info_file').text('Pilih file laporan penjualan Jubelio (.xlsx / .xls).');
        $log.empty();
    });

    // Peringatkan kalau user mau meninggalkan halaman saat impor masih jalan
    $(window).off('beforeunload.uploadresi').on('beforeunload.uploadresi', function () {
        if (sedangProses) return 'Upload resi masih diproses. Yakin mau meninggalkan halaman?';
    });
})();
</script>
