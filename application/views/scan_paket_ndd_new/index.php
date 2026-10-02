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

                <!-- PANEL LOST SCAN: di luar <form> supaya Enter di input no absen tidak
                     men-submit form scan. Muncul saat NOT_PACKED / NOT_PICKED. Simpan
                     mengisi data picking/packing yang kosong lalu langsung men-scan
                     resi ke HO/NDD -- docs/LOST_SCAN.md §12. -->
                <div id="panel_lost_scan" style="display: none; margin-top: 20px;">
                    <div class="well" style="background: #fff8e1; border: 2px solid #f39c12; border-radius: 15px; padding: 20px; margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #b9770e; letter-spacing: 1px;">
                                    <i class="fa fa-user-times"></i> Lost Scan
                                </div>
                                <div id="ls_noresi" style="font-size: 1.4rem; font-weight: 800; letter-spacing: 2px; color: #444;">-</div>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" id="ls_tutup"><i class="fa fa-times"></i> Tutup (Esc)</button>
                        </div>

                        <p id="ls_antrean_picker" style="display: none; margin: 0 0 10px; padding: 8px 10px; background: #fdecea; border-left: 4px solid #dc3545; color: #721c24; font-weight: 600;">
                            <i class="fa fa-hourglass-half"></i> <span id="ls_antrean_teks"></span>
                        </p>
                        <p id="ls_catatan_lama" style="display: none; margin: 0 0 10px; padding: 8px 10px; background: #e8f4fd; border-left: 4px solid #3498db; color: #1b4f72; font-weight: 600;">
                            <i class="fa fa-info-circle"></i> <span id="ls_catatan_lama_teks"></span>
                        </p>

                        <p style="color: #666; margin-bottom: 10px;" id="ls_teks_alasan"></p>

                        <div style="display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap;">
                            <div id="ls_wrap_picker" class="ls-field" style="display: none;">
                                <label>No Absen Picker</label>
                                <input type="text" id="ls_picker_no_absen" class="form-control" autocomplete="off" inputmode="numeric" placeholder="ketik no absen / nama">
                                <div class="ls-saran" id="ls_saran_picker"></div>
                                <div class="ls-terpilih" id="ls_terpilih_picker"></div>
                            </div>
                            <div id="ls_wrap_packer" class="ls-field">
                                <label>No Absen Packer</label>
                                <input type="text" id="ls_packer_no_absen" class="form-control" autocomplete="off" inputmode="numeric" placeholder="ketik no absen / nama">
                                <div class="ls-saran" id="ls_saran_packer"></div>
                                <div class="ls-terpilih" id="ls_terpilih_packer"></div>
                            </div>
                            <div class="ls-field">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-warning" id="ls_simpan" style="font-weight: 700; white-space: nowrap; height: 34px;">
                                    <i class="fa fa-save"></i> Simpan Lost Scan
                                </button>
                            </div>
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
    #panel_lost_scan .ls-field { position: relative; width: 240px; }
    #panel_lost_scan .ls-field label { display: block; font-size: 0.75rem; font-weight: 700; color: #666; margin-bottom: 3px; }
    #panel_lost_scan .ls-saran {
        display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
        background: #fff; border: 1px solid #ccc; border-radius: 4px; margin-top: 2px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15); max-height: 260px; overflow-y: auto;
    }
    #panel_lost_scan .ls-saran-item { padding: 6px 10px; cursor: pointer; display: flex; gap: 8px; align-items: center; }
    #panel_lost_scan .ls-saran-item.aktif, #panel_lost_scan .ls-saran-item:hover { background: #fff3cd; }
    #panel_lost_scan .ls-saran-absen { font-weight: 800; color: #1a2a6c; min-width: 40px; }
    #panel_lost_scan .ls-saran-nama { font-weight: 600; color: #333; flex: 1; }
    #panel_lost_scan .ls-saran-role { font-size: 0.7rem; font-weight: 700; background: #e0e0e0; color: #555; padding: 2px 6px; border-radius: 3px; }
    #panel_lost_scan .ls-saran-kosong { padding: 6px 10px; color: #999; font-style: italic; }
    #panel_lost_scan .ls-terpilih { font-size: 0.8rem; font-weight: 700; color: #155724; margin-top: 3px; min-height: 1em; }
</style>

<script>
$(document).ready(function() {
    $("#noresi").focus();

    // Klik di mana pun mengembalikan fokus ke input resi -- KECUALI di area
    // panel lost scan, supaya petugas bisa mengisi no absen.
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group, .btn, #panel_lost_scan').length) {
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
    function showSuccess(noresi, mode, data, w, pesanKhusus, riwayatKhusus) {
        $("#latest_resi_card").removeClass("status-error").addClass("status-success");
        $("#resi_status_icon").html('<i class="fa fa-check-circle text-success" style="animation: pop-in 0.5s;"></i>');
        $("#display_noresi").text(noresi).css("color", "#155724");

        var pesan = pesanKhusus || ((mode === 'ndd') ? "SCAN HO + NDD BERHASIL!" : "SCAN HO REGULER BERHASIL!");
        $("#display_message").text(pesan).css("color", "#28a745");

        if (data.ndd_inserted) bumpCounter("#total_scan_ndd_display");
        if (data.ho_inserted) bumpCounter("#total_scan_ho_display");

        pushHistory(noresi, riwayatKhusus || pesan, true, w);
        playCourierAudio(noresi);
    }

    function showError(noresi, message, exceptionCode, w) {
        $("#latest_resi_card").removeClass("status-success").addClass("status-error");
        $("#resi_status_icon").html('<i class="fa fa-exclamation-triangle text-danger"></i>');
        $("#display_noresi").text(noresi).css("color", "#721c24");
        $("#display_message").text(String(message).toUpperCase()).css("color", "#dc3545");

        pushHistory(noresi, message, false, w);
        playErrorAudio(exceptionCode);

        // Inti menu New: belum di-packing -> langsung tawarkan isi packer.
        // Belum di-picker -> picker DAN packer diisi (packer ikut lost scan).
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

    // ===== PANEL LOST SCAN =====
    // Panel terikat ke satu resi (lsResi). Antrean scan tetap berjalan; scan
    // resi lain hanya mengganti kartu status di atas, panel tetap ada sampai
    // disimpan atau ditutup. NOT_PACKED/NOT_PICKED resi lain mengganti isinya.
    // Satu klik Simpan mengisi picking/packing yang kosong lalu men-scan resi
    // ke HO/NDD (docs/LOST_SCAN.md §12).
    var lsResi = null;
    var lsPerluPicker = false;
    var lsPerluPacker = true;

    // Bahan saran no absen, diurai di server dari nama "NAMA - JABATAN - NOABSEN":
    // akun packer aktif dan Master Picker aktif. Daftarnya sama dengan yang
    // divalidasi Lost_scan_selesai_fcd saat Simpan.
    var ROSTER_PACKER = <?= json_encode($roster_packer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var ROSTER_PICKER = <?= json_encode($roster_picker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var MAKS_SARAN = 8;

    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function panelLostScanAktif() {
        return $("#panel_lost_scan").is(":visible");
    }

    /**
     * Input no absen dengan dropdown saran (no absen, nama, jabatan). Ketik
     * angka -> cocok ke no absen; ketik huruf -> cocok ke nama/jabatan.
     * Panah atas/bawah memilih, Enter mengambil saran yang disorot lalu
     * pindah ke kolom berikutnya; Enter sekali lagi di kolom terakhir = Simpan.
     */
    function pasangSaran(kunci, roster, berikutnya) {
        var $input = $("#ls_" + kunci + "_no_absen");
        var $saran = $("#ls_saran_" + kunci);
        var $terpilih = $("#ls_terpilih_" + kunci);
        var hasil = [];
        var aktif = -1;
        var terpilih = null;

        function cocokkan(q) {
            q = $.trim(q).toLowerCase();
            if (q === '') return roster.slice(0, MAKS_SARAN);

            if (/^\d+$/.test(q)) {
                var angka = parseInt(q, 10);
                var persis = [], lain = [];
                $.each(roster, function(_, p) {
                    if (parseInt(p.no_absen, 10) === angka) persis.push(p);
                    else if (p.no_absen.indexOf(q) !== -1) lain.push(p);
                });
                return persis.concat(lain).slice(0, MAKS_SARAN);
            }

            return $.grep(roster, function(p) {
                return p.nama.toLowerCase().indexOf(q) !== -1 || p.role.toLowerCase().indexOf(q) !== -1;
            }).slice(0, MAKS_SARAN);
        }

        function sorot() {
            $saran.children('.ls-saran-item').each(function(i) {
                $(this).toggleClass('aktif', i === aktif);
                if (i === aktif) this.scrollIntoView({ block: 'nearest' });
            });
        }

        function tampil() {
            hasil = cocokkan($input.val());
            aktif = hasil.length ? 0 : -1;
            if (!hasil.length) {
                $saran.html('<div class="ls-saran-kosong">Tidak ada yang cocok</div>').show();
                return;
            }
            $saran.html($.map(hasil, function(p, i) {
                return '<div class="ls-saran-item" data-i="' + i + '">' +
                    '<span class="ls-saran-absen">' + esc(p.no_absen) + '</span>' +
                    '<span class="ls-saran-nama">' + esc(p.nama) + '</span>' +
                    '<span class="ls-saran-role">' + esc(p.role) + '</span>' +
                    '</div>';
            }).join('')).show();
            sorot();
        }

        function pilih(p) {
            terpilih = p;
            $input.val(p.no_absen);
            $terpilih.text('✓ ' + p.nama + ' — ' + p.role);
            $saran.hide();
            berikutnya().focus();
        }

        $input.on('input', function() {
            terpilih = null;
            $terpilih.text('');
            tampil();
        });
        $input.on('focus', tampil);
        $input.on('blur', function() { $saran.hide(); });

        // mousedown (bukan click) + preventDefault: blur input tidak sempat
        // menyembunyikan dropdown sebelum pilihan diambil.
        $saran.on('mousedown', '.ls-saran-item', function(e) {
            e.preventDefault();
            pilih(hasil[$(this).data('i')]);
        });

        $input.on('keydown', function(e) {
            var terbuka = $saran.is(':visible');
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!terbuka) { tampil(); return; }
                if (!hasil.length) return;
                aktif = (aktif + (e.key === 'ArrowDown' ? 1 : -1) + hasil.length) % hasil.length;
                sorot();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (terbuka && aktif >= 0) {
                    pilih(hasil[aktif]);
                } else if (terpilih) {
                    var $b = berikutnya();
                    if ($b.is('button')) $b.click(); else $b.focus();
                }
            } else if (e.key === 'Escape' && terbuka) {
                // Esc pertama hanya menutup saran, bukan panelnya.
                e.stopPropagation();
                $saran.hide();
            }
        });

        return {
            // No absen yang akan dikirim: pilihan dari saran, angka yang
            // diketik langsung (divalidasi server), atau satu-satunya saran
            // yang cocok dengan nama yang diketik.
            nilai: function() {
                if (terpilih) return terpilih.no_absen;
                var v = $.trim($input.val());
                if (/^\d+$/.test(v)) return v;
                var c = cocokkan(v);
                return (v !== '' && c.length === 1) ? c[0].no_absen : '';
            },
            reset: function() {
                terpilih = null;
                $input.val('');
                $terpilih.text('');
                $saran.hide();
            },
            fokus: function() { $input.focus(); }
        };
    }

    var saranPicker = pasangSaran('picker', ROSTER_PICKER, function() {
        return lsPerluPacker ? $("#ls_packer_no_absen") : $("#ls_simpan");
    });
    var saranPacker = pasangSaran('packer', ROSTER_PACKER, function() {
        return $("#ls_simpan");
    });

    function aturTampilanPanel() {
        $("#ls_wrap_picker").toggle(lsPerluPicker);
        $("#ls_wrap_packer").toggle(lsPerluPacker);

        var akhir = ', pilih dari saran, lalu <b>Enter</b> / klik Simpan -- data diisi atas nama mereka ' +
            '(tanggal &amp; jam saat ini) dan resi <b>langsung masuk HO/NDD</b>.';
        var teks;
        if (lsPerluPicker && lsPerluPacker) {
            teks = '<b>Resi belum di-picker dan belum di-packing.</b> Ketik no absen / nama <b>picker</b> dan <b>packer</b>' + akhir;
        } else if (lsPerluPicker) {
            teks = '<b>Resi belum di-picker</b> (packing sudah ada). Ketik no absen / nama <b>picker</b>' + akhir;
        } else {
            teks = '<b>Resi belum di-packing.</b> Ketik no absen / nama <b>packer</b>' + akhir;
        }
        $("#ls_teks_alasan").html(teks);
    }

    function fokusPertama() {
        if (lsPerluPicker) saranPicker.fokus(); else saranPacker.fokus();
    }

    function tampilkanPanelLostScan(noresi, belumPicker) {
        if (lsResi === noresi && $("#panel_lost_scan").is(":visible")) return;

        lsResi = noresi;
        lsPerluPicker = !!belumPicker;
        lsPerluPacker = true;
        $("#ls_noresi").text(noresi);
        $("#ls_antrean_picker, #ls_catatan_lama").hide();
        saranPicker.reset();
        saranPacker.reset();
        aturTampilanPanel();
        $("#ls_simpan").prop("disabled", false);
        $("#panel_lost_scan").show();
        fokusPertama();

        // Info tambahan: catatan lost scan lama, antrean tim picker, dan
        // apakah packing ternyata sudah ada (kolom packer disembunyikan).
        // Kalau request-nya gagal, panel tetap bisa dipakai.
        $.ajax({
            url: "scan-paket-ndd-new/cek-lost-scan",
            type: "POST",
            data: { noresi: noresi },
            dataType: "json",
            success: function(r) {
                if (lsResi !== noresi) return; // panel sudah pindah ke resi lain
                if (r.code !== 200 || !r.data) return;
                if (r.data.antrean_picker) tampilkanAntreanPicker(r.data.antrean_picker);
                if (r.data.sudah_dicatat) tampilkanCatatanLama(r.data.catatan);
                if (r.data.sudah_packing && lsPerluPacker) {
                    lsPerluPacker = false;
                    aturTampilanPanel();
                }
            }
        });
    }

    function tampilkanCatatanLama(catatan) {
        var c = catatan || {};
        var waktu = c.created_at ? String(c.created_at).substring(0, 16) : '-';
        $("#ls_catatan_lama_teks").text(
            'Sudah pernah dicatat lost scan ' + (c.lost_type || '') + ' → ' + (c.nama_packer || '-') +
            ' oleh ' + (c.nama_pelapor || '-') + ' pada ' + waktu +
            '. Catatan itu dipakai ulang; Simpan tetap mengisi data yang masih kosong.'
        );
        $("#ls_catatan_lama").show();
    }

    function tampilkanAntreanPicker(a) {
        var waktu = a.waktu_lapor ? String(a.waktu_lapor).substring(0, 16) : '-';
        $("#ls_antrean_teks").text(
            'Sudah dilaporkan ke tim picker oleh ' + (a.nama_pelapor || '-') + ' (' + (a.sumber || '-') +
            ') pada ' + waktu + '. Kalau disimpan dari sini, laporan itu ditutup otomatis.'
        );
        $("#ls_antrean_picker").show();
    }

    function tutupPanelLostScan() {
        lsResi = null;
        saranPicker.reset();
        saranPacker.reset();
        $("#panel_lost_scan").hide();
        $("#noresi").focus();
    }

    function simpanLostScan() {
        if (!lsResi) return;

        var absenPicker = lsPerluPicker ? saranPicker.nilai() : '';
        var absenPacker = lsPerluPacker ? saranPacker.nilai() : '';

        if (lsPerluPicker && absenPicker === '') {
            noty({ text: 'Pilih picker dari saran (ketik no absen / nama)', layout: 'topRight', type: 'warning', timeout: 2500 });
            saranPicker.fokus();
            return;
        }
        if (lsPerluPacker && absenPacker === '') {
            noty({ text: 'Pilih packer dari saran (ketik no absen / nama)', layout: 'topRight', type: 'warning', timeout: 2500 });
            saranPacker.fokus();
            return;
        }

        var resi = lsResi;
        var mode = currentMode;
        $("#ls_simpan").prop("disabled", true);

        $.ajax({
            url: "scan-paket-ndd-new/simpan-lost-scan",
            type: "POST",
            data: {
                noresi: resi,
                no_absen_picker: absenPicker,
                no_absen_packer: absenPacker,
                is_ndd: (mode === 'ndd') ? 'true' : 'false'
            },
            dataType: "json",
            success: function(r) {
                var d = r.data || {};
                if (r.code === 201) {
                    recent[resi] = Date.now();
                    pruneRecent();

                    var rincian = [];
                    if (d.nama_picker) rincian.push('picker ' + d.nama_picker);
                    if (d.nama_packer) rincian.push('packer ' + d.nama_packer);
                    var tujuan = (mode === 'ndd') ? 'HO + NDD' : 'HO REGULER';
                    var riwayat = 'LOST SCAN → ' + tujuan + (rincian.length ? ' (' + rincian.join(', ') + ', ' + d.tanggal + ')' : '');

                    showSuccess(resi, mode, d, null, 'LOST SCAN DISIMPAN — MASUK ' + tujuan, riwayat);
                    noty({ text: resi + ' — ' + r.message, layout: 'topRight', type: 'success', timeout: 5000 });
                    tutupPanelLostScan();
                } else if (d.data_terisi) {
                    // Picking/packing sudah tersimpan, hanya scan HO-nya yang gagal.
                    noty({ text: r.message, layout: 'topRight', type: 'warning', timeout: 8000 });
                    pushHistory(resi, r.message, false, null);
                    tutupPanelLostScan();
                } else {
                    noty({ text: r.message || 'Gagal menyimpan lost scan', layout: 'topRight', type: 'error', timeout: 4000 });
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

    // Esc = tutup panel.
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape' || !$("#panel_lost_scan").is(":visible")) return;
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
