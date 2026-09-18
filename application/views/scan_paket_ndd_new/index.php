<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default shadow-lg" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="panel-heading" id="panel_header" style="background: linear-gradient(135deg, #1a2a6c, #2a4858); color: white; padding: 25px;">
                <h3 class="panel-title" style="font-weight: 700; font-size: 1.6rem; letter-spacing: 1px;">
                    <i class="fa fa-truck"></i> <span id="header_title">SCAN HO + NDD</span>
                    <span class="label label-warning" style="font-size: 0.7rem; vertical-align: middle; margin-left: 8px;">NEW</span>
                </h3>
                <div class="pull-right">
                    <span class="label" id="header_badge" style="font-size: 0.9rem; padding: 5px 10px; border-radius: 4px; background: #e67e22;">HO + NDD</span>
                </div>
            </div>
            <div class="panel-body" style="padding: 40px; background: #f4f7f6;">
                <form id="form_scan_logistic" autocomplete="off" class="form-horizontal nojs">
                    <input type="hidden" name="is_ndd" id="is_ndd" value="true">

                    <!-- MODE TOGGLE -->
                    <div class="row" style="margin-bottom: 25px;">
                        <div class="col-md-12 text-center">
                            <div class="btn-group" id="mode_toggle" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
                                <button type="button" class="btn btn-lg mode-btn" id="btn_mode_ndd" style="padding: 12px 35px; font-weight: 700; font-size: 1rem; letter-spacing: 1px; background: #1a2a6c; color: #fff; border: none;">
                                    <i class="fa fa-bolt"></i> HO + NDD
                                </button>
                                <button type="button" class="btn btn-lg mode-btn" id="btn_mode_reguler" style="padding: 12px 35px; font-weight: 700; font-size: 1rem; letter-spacing: 1px; background: #e0e0e0; color: #666; border: none;">
                                    <i class="fa fa-cube"></i> REGULER
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #1a2a6c; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Scan NDD</label>
                                <div style="font-size: 2.2rem; font-weight: 800; color: #1a2a6c;" id="total_scan_ndd_display"><?= $total_scan_ndd ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #27ae60; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Scan HO</label>
                                <div style="font-size: 2.2rem; font-weight: 800; color: #27ae60;" id="total_scan_ho_display"><?= $total_scan ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #2a4858; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Host ID</label>
                                <div style="font-size: 1.2rem; font-weight: 700; color: #333; margin-top: 5px;"><i class="fa fa-desktop"></i> <?= $nama_komputer ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <div class="col-md-12">
                            <div class="input-group input-group-lg" id="input_wrapper" style="box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
                                <span class="input-group-addon" id="input_icon" style="background: #fff; border-right: none; border-radius: 12px 0 0 12px; color: #1a2a6c;">
                                    <i class="fa fa-barcode fa-2x"></i>
                                </span>
                                <input type="text" name="noresi" id="noresi" class="form-control"
                                       placeholder="MASUKKAN NOMOR RESI..."
                                       style="height: 75px; font-size: 1.8rem; border-left: none; border-radius: 0 12px 12px 0; border: 2px solid #ddd; font-weight: 600; text-align: center;">
                            </div>
                            <p class="text-center text-muted" style="margin-top: 15px; font-weight: 500;" id="scan_description">
                                <i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> dan <strong>NDD Report</strong>
                            </p>
                        </div>
                    </div>

                    <div id="status_container" class="mt-4 text-center" style="min-height: 140px; margin-top: 40px;">
                        <div id="latest_resi_card" class="well" style="background: #fff; border: 2px solid #eee; border-radius: 15px; transition: all 0.3s ease; padding: 20px;">
                            <div id="resi_status_icon" style="font-size: 3rem; margin-bottom: 10px; color: #bbb;">
                                <i class="fa fa-dot-circle-o"></i>
                            </div>
                            <h3 id="display_noresi" style="font-weight: 800; color: #444; letter-spacing: 2px; margin: 5px 0;">-</h3>
                            <p id="display_message" style="font-size: 1.1rem; font-weight: 600; color: #888;">STANDBY</p>
                            <span id="queue_badge" class="label label-warning" style="display: none; font-size: 0.85rem; padding: 5px 10px;">
                                <i class="fa fa-spinner fa-spin"></i> <span id="queue_count">0</span> resi dalam antrean
                            </span>
                        </div>
                    </div>
                </form>

                <!-- PANEL LOST SCAN PACKER: di luar <form> supaya Enter di dropdown
                     tidak men-submit form scan. Muncul saat NOT_PACKED dan NOT_PICKED
                     (yang kedua sekaligus melaporkan resi ke antrean tim picker). -->
                <div id="panel_lost_scan" style="display: none; margin-top: 20px;">
                    <div class="well" style="background: #fff8e1; border: 2px solid #f39c12; border-radius: 15px; padding: 20px; margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #b9770e; letter-spacing: 1px;">
                                    <i class="fa fa-user-times"></i> Lost Scan Packer
                                </div>
                                <div id="ls_noresi" style="font-size: 1.4rem; font-weight: 800; letter-spacing: 2px; color: #444;">-</div>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" id="ls_tutup"><i class="fa fa-times"></i> Tutup (Esc)</button>
                        </div>

                        <div id="ls_mode_simpan">
                            <p style="color: #666; margin-bottom: 10px;" id="ls_teks_alasan">
                                Resi belum di-packing. Pilih packer yang lupa scan, lalu tekan <b>Enter</b> / klik Simpan.
                                Resi ini <b>tidak</b> masuk HO -- scan ulang setelah packer selesai.
                            </p>
                            <p id="ls_antrean_picker" style="display: none; margin: 0 0 10px; padding: 8px 10px; background: #fdecea; border-left: 4px solid #dc3545; color: #721c24; font-weight: 600;">
                                <i class="fa fa-hourglass-half"></i> <span id="ls_antrean_teks"></span>
                            </p>
                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <select id="ls_packer" class="form-control" data-live-search="true" data-size="8" title="Pilih Packer">
                                        <option value="">Pilih Packer</option>
                                        <?php foreach ($list_packer as $p) : ?>
                                            <option value="<?= htmlspecialchars($p['nama_pegawai']) ?>"><?= htmlspecialchars($p['nama_pegawai'] . ' - ' . $p['kode_pegawai']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-warning" id="ls_simpan" style="font-weight: 700; white-space: nowrap;">
                                    <i class="fa fa-save"></i> Simpan Lost Scan
                                </button>
                            </div>
                            <?php if (empty($list_packer)) : ?>
                                <p class="text-danger" style="margin: 10px 0 0;">
                                    <i class="fa fa-exclamation-triangle"></i> Tidak ada akun packer aktif yang terhubung ke data pegawai.
                                    Catat lewat <a href="lost_scan_packer/input" class="link">menu Lost Scan Packer</a>.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div id="ls_mode_info" style="display: none;">
                            <p style="font-weight: 600; color: #155724; margin: 0; font-size: 1.05rem;">
                                <i class="fa fa-check-circle"></i> <span id="ls_info_teks"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div id="stat_waktu_wrapper" style="margin-top: 20px; text-align: center; font-size: 0.85rem; color: #666;">
                    <i class="fa fa-clock-o"></i> <span id="stat_waktu">belum ada scan</span>
                </div>

                <div id="history_wrapper" style="margin-top: 25px; display: none;">
                    <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
                        <i class="fa fa-history"></i> Riwayat Scan Terakhir
                    </label>
                    <div style="background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;">
                        <table class="table table-condensed" style="margin: 0; font-size: 0.9rem;">
                            <tbody id="scan_history"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="panel-footer" style="background: #2a4858; color: white; border: none; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 500;">
                    <i class="fa fa-shield"></i> SECURE LOGISTIC SCAN v2.0 &middot; NEW
                </div>
                <div>
                    <a href="lost_scan_packer/report" class="btn btn-warning btn-sm link" style="border-radius: 6px; font-weight: 600; margin-right: 6px;">
                        <i class="fa fa-user-times"></i> LAPORAN LOST SCAN
                    </a>
                    <a href="scan_logistic/report" class="btn btn-info btn-sm link" style="border-radius: 6px; font-weight: 600;">
                        <i class="fa fa-list-alt"></i> BUKA LAPORAN NDD
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #noresi:focus { border-color: #1a2a6c; outline: none; box-shadow: 0 0 15px rgba(26, 42, 108, 0.3); }
    .status-success { border: 3px solid #28a745 !important; background-color: #f0fff4 !important; animation: pop-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .status-error { border: 3px solid #dc3545 !important; background-color: #fff5f5 !important; animation: shake-hard 0.4s; }

    @keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    @keyframes shake-hard { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-15px); } 40% { transform: translateX(15px); } 60% { transform: translateX(-15px); } 80% { transform: translateX(15px); } }

    .info-card { transition: transform 0.2s; }
    .info-card:hover { transform: translateY(-5px); }

    .mode-btn { transition: all 0.3s ease; }
    .mode-btn:focus { outline: none; box-shadow: none; }

    /* Reguler mode theme */
    .mode-reguler #panel_header { background: linear-gradient(135deg, #27ae60, #1e8449) !important; }
    .mode-reguler #input_icon { color: #27ae60 !important; }
    .mode-reguler #noresi:focus { border-color: #27ae60 !important; box-shadow: 0 0 15px rgba(39, 174, 96, 0.3) !important; }

    #panel_lost_scan { animation: pop-in 0.3s ease; }
    #panel_lost_scan .bootstrap-select > .btn { height: 42px; font-weight: 600; }
</style>

<script>
$(document).ready(function() {
    $("#noresi").focus();

    // Klik di mana pun mengembalikan fokus ke input resi -- KECUALI di area
    // panel lost scan dan dropdown-nya, supaya petugas bisa memilih packer.
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group, .btn, #panel_lost_scan, .bootstrap-select, .dropdown-menu').length) {
            $("#noresi").focus();
        }
    });

    // ===== MODE TOGGLE =====
    var currentMode = 'ndd'; // default

    $("#btn_mode_ndd").on('click', function() {
        currentMode = 'ndd';
        $("#is_ndd").val('true');

        $(this).css({ background: '#1a2a6c', color: '#fff' });
        $("#btn_mode_reguler").css({ background: '#e0e0e0', color: '#666' });

        $("#header_title").text("SCAN HO + NDD");
        $("#header_badge").text("HO + NDD").css('background', '#e67e22');
        $(".panel-default").removeClass('mode-reguler');

        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> dan <strong>NDD Report</strong>');

        $("#noresi").focus();
    });

    $("#btn_mode_reguler").on('click', function() {
        currentMode = 'reguler';
        $("#is_ndd").val('false');

        $(this).css({ background: '#27ae60', color: '#fff' });
        $("#btn_mode_ndd").css({ background: '#e0e0e0', color: '#666' });

        $("#header_title").text("SCAN HO REGULER");
        $("#header_badge").text("REGULER").css('background', '#27ae60');
        $(".panel-default").addClass('mode-reguler');

        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> saja');

        $("#noresi").focus();
    });

    // ===== ANTREAN SCAN =====
    // Input TIDAK pernah di-disable (scanner gun mengetik cepat lalu Enter).
    // Nilai langsung diambil + dikosongkan, resi masuk antrean, dikirim satu
    // per satu di latar belakang. Sama dengan menu lama.
    var busy = false;
    var queue = [];
    var recent = {};              // noresi -> waktu scan sukses terakhir
    var RECENT_MS = 5 * 60 * 1000; // tolak scan ulang dalam 5 menit tanpa ke server

    $("#form_scan_logistic").on("submit", function(e) {
        e.preventDefault();

        var noresi = $("#noresi").val().trim();
        $("#noresi").val("").focus();
        if (noresi === "") return;

        queue.push({ noresi: noresi, mode: currentMode });
        renderQueue();
        processQueue();
    });

    function renderQueue() {
        var pending = queue.length + (busy ? 1 : 0);
        $("#queue_count").text(pending);
        $("#queue_badge").toggle(pending > 1);
    }

    // ===== PENGUKUR WAKTU SCAN ===== (sama dengan menu lama)
    var statWaktu = { n: 0, total: 0, maks: 0, lambat: 0, nSrv: 0, totalSrv: 0, totalBoot: 0 };
    var AMBANG_LAMBAT = 1000; // ms

    function catatWaktu(t0, data) {
        var t1 = (window.performance && performance.now) ? performance.now() : Date.now();
        var ms = Math.round(t1 - t0);

        var w = { ms: ms, srv: null, boot: null };
        if (data && typeof data.srv_ms === 'number') {
            w.srv = data.srv_ms;
            w.boot = (typeof data.boot_ms === 'number') ? data.boot_ms : null;

            statWaktu.nSrv++;
            statWaktu.totalSrv += w.srv;
            statWaktu.totalBoot += (w.boot || 0);
        }

        statWaktu.n++;
        statWaktu.total += ms;
        if (ms > statWaktu.maks) statWaktu.maks = ms;
        if (ms > AMBANG_LAMBAT) statWaktu.lambat++;

        var rata = Math.round(statWaktu.total / statWaktu.n);
        var teks =
            'terakhir <b>' + ms + ' ms</b> &nbsp;|&nbsp; rata-rata <b>' + rata +
            ' ms</b> &nbsp;|&nbsp; terlama <b>' + statWaktu.maks +
            ' ms</b> &nbsp;|&nbsp; di atas 1 detik: <b>' + statWaktu.lambat + '</b> dari ' + statWaktu.n;

        if (statWaktu.nSrv > 0) {
            var rataSrv = Math.round(statWaktu.totalSrv / statWaktu.nSrv);
            var rataBoot = Math.round(statWaktu.totalBoot / statWaktu.nSrv);
            teks += '<br><span style="font-size: 11px;">server <b>' + rataSrv +
                ' ms</b> (bootstrap <b>' + rataBoot + ' ms</b>) &nbsp;|&nbsp; jaringan+antrean <b>' +
                Math.max(0, rata - rataSrv) + ' ms</b></span>';
        }

        $("#stat_waktu").html(teks).css('color', ms > AMBANG_LAMBAT ? '#dc3545' : '#666');

        return w;
    }

    function processQueue() {
        if (busy || queue.length === 0) return;

        var item = queue.shift();
        var noresi = item.noresi;
        var now = Date.now();

        if (recent[noresi] && (now - recent[noresi]) < RECENT_MS) {
            showError(noresi, "SUDAH DI-SCAN BARUSAN (DOBEL)", "ALREADY_SCANNED");
            renderQueue();
            setTimeout(processQueue, 0);
            return;
        }

        busy = true;
        renderQueue();

        var t0 = (window.performance && performance.now) ? performance.now() : Date.now();

        $.ajax({
            url: "scan-paket-ndd-new/save",
            type: "POST",
            data: { noresi: noresi, is_ndd: (item.mode === 'ndd') ? 'true' : 'false' },
            dataType: "json",
            success: function(response) {
                var data = response.data || {};
                var w = catatWaktu(t0, data);
                if (response.code === 201) {
                    recent[noresi] = Date.now();
                    pruneRecent();
                    showSuccess(noresi, item.mode, data, w);
                } else {
                    showError(noresi, response.message, data.EXCEPTION_CODE || '', w);
                }
            },
            error: function() {
                showError(noresi, "KESALAHAN SISTEM!", '', catatWaktu(t0));
            },
            complete: function() {
                busy = false;
                renderQueue();
                // Kalau panel lost scan sedang menunggu pilihan packer, fokus
                // jangan direbut kembali ke input resi.
                if (!panelLostScanAktif()) $("#noresi").focus();
                processQueue();
            }
        });
    }

    function pruneRecent() {
        var cutoff = Date.now() - RECENT_MS;
        for (var k in recent) {
            if (recent[k] < cutoff) delete recent[k];
        }
    }

    // ===== TAMPILAN HASIL =====
    function showSuccess(noresi, mode, data, w) {
        $("#latest_resi_card").removeClass("status-error").addClass("status-success");
        $("#resi_status_icon").html('<i class="fa fa-check-circle text-success" style="animation: pop-in 0.5s;"></i>');
        $("#display_noresi").text(noresi).css("color", "#155724");

        var pesan = (mode === 'ndd') ? "SCAN HO + NDD BERHASIL!" : "SCAN HO REGULER BERHASIL!";
        $("#display_message").text(pesan).css("color", "#28a745");

        if (data.ndd_inserted) bumpCounter("#total_scan_ndd_display");
        if (data.ho_inserted) bumpCounter("#total_scan_ho_display");

        pushHistory(noresi, pesan, true, w);
        playCourierAudio(noresi);
    }

    function showError(noresi, message, exceptionCode, w) {
        $("#latest_resi_card").removeClass("status-success").addClass("status-error");
        $("#resi_status_icon").html('<i class="fa fa-exclamation-triangle text-danger"></i>');
        $("#display_noresi").text(noresi).css("color", "#721c24");
        $("#display_message").text(String(message).toUpperCase()).css("color", "#dc3545");

        pushHistory(noresi, message, false, w);
        playErrorAudio(exceptionCode);

        // Inti menu New: belum di-packing -> langsung tawarkan catat lost scan.
        // Belum di-picker -> packer tetap wajib diisi (packer ikut lost scan),
        // dan resi otomatis dilaporkan ke antrean tim picker saat disimpan.
        if (exceptionCode === 'NOT_PACKED') {
            tampilkanPanelLostScan(noresi, false);
        } else if (exceptionCode === 'NOT_PICKED') {
            tampilkanPanelLostScan(noresi, true);
        }
    }

    function bumpCounter(selector) {
        var el = $(selector);
        el.text((parseInt(el.text(), 10) || 0) + 1);
    }

    function pushHistory(noresi, message, ok, w) {
        var jam = new Date().toTimeString().substring(0, 8);
        var warna = ok ? '#28a745' : '#dc3545';
        var ikon = ok ? 'fa-check-circle' : 'fa-times-circle';

        var ms = (w && typeof w.ms === 'number') ? w.ms : null;

        var lambat = (ms !== null && ms > AMBANG_LAMBAT);
        var rincian = '';
        if (w && typeof w.srv === 'number') {
            rincian = '<br><span style="font-weight: 400; font-size: 10px; color: #999;">srv ' +
                w.srv + (typeof w.boot === 'number' ? ' · boot ' + w.boot : '') + '</span>';
        }
        var kolomMs = (ms !== null)
            ? '<td style="width: 95px; text-align: right; font-weight: 700; color: ' +
              (lambat ? '#dc3545' : '#999') + ';">' + ms + ' ms' + rincian + '</td>'
            : '<td></td>';

        $("#history_wrapper").show();
        $("#scan_history").prepend(
            '<tr>' +
            '<td style="width: 70px; color: #999;">' + jam + '</td>' +
            '<td style="font-weight: 700; letter-spacing: 1px;">' + $('<div>').text(noresi).html() + '</td>' +
            '<td style="color: ' + warna + '; font-weight: 600;">' +
            '<i class="fa ' + ikon + '"></i> ' + $('<div>').text(message).html() +
            '</td>' +
            kolomMs +
            '</tr>'
        );
        $("#scan_history tr:gt(7)").remove();
    }

    // ===== PANEL LOST SCAN PACKER =====
    // Panel terikat ke satu resi (lsResi). Antrean scan tetap berjalan; scan
    // resi lain hanya mengganti kartu status di atas, panel tetap ada sampai
    // disimpan atau ditutup. NOT_PACKED untuk resi lain mengganti isi panel.
    var lsResi = null;
    var lsBelumPicker = false; // true = ditolak NOT_PICKED, ikut lapor ke tim picker
    var lsAdaSelectpicker = (typeof $.fn.selectpicker === 'function');
    if (lsAdaSelectpicker) {
        $("#ls_packer").selectpicker({ liveSearch: true, size: 8 });
    }

    function panelLostScanAktif() {
        return $("#panel_lost_scan").is(":visible") && $("#ls_mode_simpan").is(":visible");
    }

    var TEKS_BELUM_PACKING =
        'Resi belum di-packing. Pilih packer yang lupa scan, lalu tekan <b>Enter</b> / klik Simpan. ' +
        'Resi ini <b>tidak</b> masuk HO -- scan ulang setelah packer selesai.';
    var TEKS_BELUM_PICKER =
        '<b>Resi belum di-picker</b> -- picker dan packer sama-sama lost scan. Pilih packer yang lupa scan; ' +
        'saat disimpan, resi otomatis dilaporkan ke <b>tim picker</b> untuk ditentukan picker-nya. ' +
        'Baru setelah itu packer bisa scan ulang, lalu HO.';

    function tampilkanPanelLostScan(noresi, belumPicker) {
        if (lsResi === noresi && $("#panel_lost_scan").is(":visible")) return;

        lsResi = noresi;
        lsBelumPicker = !!belumPicker;
        $("#ls_noresi").text(noresi);
        $("#ls_teks_alasan").html(lsBelumPicker ? TEKS_BELUM_PICKER : TEKS_BELUM_PACKING);
        $("#ls_antrean_picker").hide();
        setModeSimpan();
        $("#panel_lost_scan").show();
        fokusPacker();

        // Cek informatif: kalau sudah pernah dicatat, ganti ke mode info.
        // Kalau request-nya gagal, panel simpan tetap tampil.
        $.ajax({
            url: "scan-paket-ndd-new/cek-lost-scan",
            type: "POST",
            data: { noresi: noresi },
            dataType: "json",
            success: function(r) {
                if (lsResi !== noresi) return; // panel sudah pindah ke resi lain
                if (r.code !== 200 || !r.data) return;
                if (r.data.antrean_picker) tampilkanAntreanPicker(r.data.antrean_picker);
                if (r.data.sudah_dicatat) {
                    setModeInfo(r.data.catatan);
                }
            }
        });
    }

    // Resi sudah ada di antrean tim picker: tampilkan siapa yang melapor dan
    // kapan, supaya petugas tahu paket ini sedang menunggu dan tidak melapor
    // ulang (server pun menolak laporan ganda).
    function tampilkanAntreanPicker(a) {
        var waktu = a.waktu_lapor ? String(a.waktu_lapor).substring(0, 16) : '-';
        $("#ls_antrean_teks").text(
            'Sudah dilaporkan ke tim picker oleh ' + (a.nama_pelapor || '-') + ' (' + (a.sumber || '-') +
            ') pada ' + waktu + ' -- menunggu picker ditentukan.'
        );
        $("#ls_antrean_picker").show();
    }

    function setModeSimpan() {
        $("#ls_mode_info").hide();
        $("#ls_mode_simpan").show();
        $("#ls_simpan").prop("disabled", false);
        resetPilihanPacker();
    }

    function setModeInfo(catatan) {
        var c = catatan || {};
        var waktu = c.created_at ? String(c.created_at).substring(0, 16) : '-';
        var teks = 'Sudah dicatat lost scan ' + (c.lost_type || '') + ' → ' + (c.nama_packer || '-') +
            ', oleh ' + (c.nama_pelapor || '-') + ' pada ' + waktu + '. Tidak perlu dicatat lagi.';
        $("#ls_info_teks").text(teks);
        $("#ls_mode_simpan").hide();
        $("#ls_mode_info").show();
        $("#ls_tutup").focus();
    }

    function resetPilihanPacker() {
        $("#ls_packer").val('');
        if (lsAdaSelectpicker) $("#ls_packer").selectpicker('refresh');
    }

    function fokusPacker() {
        if (lsAdaSelectpicker) {
            // Membuka dropdown langsung menaruh kursor di kotak pencarian:
            // petugas tinggal mengetik nama.
            setTimeout(function() { $("#ls_packer").selectpicker('toggle'); }, 50);
        } else {
            $("#ls_packer").focus();
        }
    }

    function tutupPanelLostScan() {
        lsResi = null;
        lsBelumPicker = false;
        $("#panel_lost_scan").hide();
        $("#noresi").focus();
    }

    function simpanLostScan() {
        if (!lsResi) return;

        var packer = $("#ls_packer").val();
        if (!packer) {
            noty({ text: 'Pilih packer dulu', layout: 'topRight', type: 'warning', timeout: 2500 });
            fokusPacker();
            return;
        }

        var resi = lsResi;
        $("#ls_simpan").prop("disabled", true);

        $.ajax({
            url: "scan-paket-ndd-new/simpan-lost-scan",
            type: "POST",
            data: { noresi: resi, nama_petugas: packer, belum_picker: lsBelumPicker ? '1' : '0' },
            dataType: "json",
            success: function(r) {
                if (r.code === 201) {
                    var d = r.data || {};
                    var teksRiwayat = 'LOST SCAN DICATAT → ' + packer;
                    if (d.lapor_picker === 'DIBUAT') {
                        teksRiwayat += ' + DILAPORKAN KE TIM PICKER';
                    } else if (d.lapor_picker === 'SUDAH_PENDING') {
                        teksRiwayat += ' (sudah di antrean tim picker)';
                    }
                    noty({ text: r.message + ': ' + resi + ' → ' + packer, layout: 'topRight', type: 'success', timeout: 4000 });
                    pushHistory(resi, teksRiwayat, true, null);
                    playTag('audio-alert');
                    tutupPanelLostScan();
                } else if (r.data && r.data.sudah_dicatat) {
                    noty({ text: r.message, layout: 'topRight', type: 'warning', timeout: 3000 });
                    setModeInfo(r.data.catatan);
                } else {
                    noty({ text: r.message || 'Gagal menyimpan lost scan', layout: 'topRight', type: 'error', timeout: 3000 });
                    $("#ls_simpan").prop("disabled", false);
                }
            },
            error: function() {
                noty({ text: 'Kesalahan sistem saat menyimpan lost scan', layout: 'topRight', type: 'error', timeout: 3000 });
                $("#ls_simpan").prop("disabled", false);
            }
        });
    }

    $("#ls_simpan").on('click', simpanLostScan);
    $("#ls_tutup").on('click', tutupPanelLostScan);

    // Setelah packer dipilih (Enter di kotak cari / klik), fokus pindah ke
    // tombol Simpan -- Enter berikutnya langsung menyimpan.
    $("#ls_packer").on('changed.bs.select change', function() {
        if ($(this).val()) $("#ls_simpan").focus();
    });

    // Esc = tutup panel (kalau dropdown sedang terbuka, Esc pertama hanya
    // menutup dropdown -- itu perilaku bawaan bootstrap-select).
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape' || !$("#panel_lost_scan").is(":visible")) return;
        if ($("#panel_lost_scan .bootstrap-select").hasClass('open')) return;
        tutupPanelLostScan();
    });

    // ===== SUARA ===== (sama dengan menu lama)
    var BATAS_SUARA = {
        'audio-jnt':     400,
        'audio-jne':     400,
        'audio-double':  900,
        'audio-fail':    900,
        'audio-cancel':  800,
        'audio-paket-double': 1900,
        'audio-cancel-order': 2100,
        'audio-tidak-ditemukan': 1500,
        'audio-alert':   500
    };
    var BATAS_DEFAULT = 500;

    function playTag(id) {
        var batas = BATAS_SUARA[id] || BATAS_DEFAULT;

        if (typeof suaraScan === 'function') {
            suaraScan(id, { batas: batas });
            return;
        }

        var el = document.getElementById(id);
        if (el) {
            try { el.currentTime = 0; } catch (err) {}
            el.play();
        }
    }

    function playCourierAudio(noresi) {
        if (typeof suaraKurir === "function") {
            suaraKurir(noresi);
            return;
        }
        playTag("audio-alert");
    }

    function playErrorAudio(exceptionCode) {
        if (exceptionCode === 'ALREADY_HANDOVER' || exceptionCode === 'ALREADY_SCANNED') {
            playTag('audio-paket-double');
        } else if (exceptionCode === 'NOT_PICKED' || exceptionCode === 'NOT_PACKED') {
            playTag('audio-fail');
        } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
            playTag('audio-cancel-order');
        } else if (exceptionCode === 'NOT_FOUND') {
            playTag('audio-tidak-ditemukan');
        } else {
            playTag('audio-alert');
        }
    }
});
</script>
