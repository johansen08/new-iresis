<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default shadow-lg" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="panel-heading" id="panel_header" style="background: linear-gradient(135deg, #1a2a6c, #2a4858); color: white; padding: 25px;">
                <h3 class="panel-title" style="font-weight: 700; font-size: 1.6rem; letter-spacing: 1px;">
                    <i class="fa fa-truck"></i> <span id="header_title">SCAN HO + NDD</span>
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
                </form>
            </div>
            <div class="panel-footer" style="background: #2a4858; color: white; border: none; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 500;">
                    <i class="fa fa-shield"></i> SECURE LOGISTIC SCAN v2.0
                </div>
                <div>
                    <a href="scan_logistic/report" class="btn btn-info btn-sm" style="border-radius: 6px; font-weight: 600;">
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
</style>

<script>
$(document).ready(function() {
    $("#noresi").focus();
    $(document).on('click', function(e) { 
        if (!$(e.target).closest('.btn-group, .btn').length) {
            $("#noresi").focus(); 
        }
    });

    // ===== MODE TOGGLE =====
    var currentMode = 'ndd'; // default

    $("#btn_mode_ndd").on('click', function() {
        currentMode = 'ndd';
        $("#is_ndd").val('true');
        
        // UI: Highlight NDD button
        $(this).css({ background: '#1a2a6c', color: '#fff' });
        $("#btn_mode_reguler").css({ background: '#e0e0e0', color: '#666' });
        
        // UI: Update header
        $("#header_title").text("SCAN HO + NDD");
        $("#header_badge").text("HO + NDD").css('background', '#e67e22');
        $(".panel-default").removeClass('mode-reguler');
        
        // UI: Update description
        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> dan <strong>NDD Report</strong>');
        
        $("#noresi").focus();
    });

    $("#btn_mode_reguler").on('click', function() {
        currentMode = 'reguler';
        $("#is_ndd").val('false');
        
        // UI: Highlight Reguler button
        $(this).css({ background: '#27ae60', color: '#fff' });
        $("#btn_mode_ndd").css({ background: '#e0e0e0', color: '#666' });
        
        // UI: Update header
        $("#header_title").text("SCAN HO REGULER");
        $("#header_badge").text("REGULER").css('background', '#27ae60');
        $(".panel-default").addClass('mode-reguler');
        
        // UI: Update description
        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> saja');
        
        $("#noresi").focus();
    });

    // ===== ANTREAN SCAN =====
    // Input TIDAK pernah di-disable. Scanner gun mengetik sangat cepat lalu
    // menekan Enter; kalau input dikunci selama AJAX, karakter resi berikutnya
    // hilang separuh. Di sini nilai langsung diambil + dikosongkan, resi masuk
    // antrean, dan dikirim satu per satu di latar belakang.
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

    // ===== PENGUKUR WAKTU SCAN =====
    // Dipasang 2026-08-04. Sampai sekarang tidak ada satu pun angka waktu nyata
    // dari stasiun: Apache belum mencatat durasi request (%D) dan slow query log
    // masih mati. Ini mengukur bolak-balik AJAX apa adanya, dari sisi yang
    // benar-benar dirasakan operator.
    //
    // 2026-08-10: angka bolak-balik saja tidak bisa menjawab "server atau
    // jaringan?" -- di log stasiun ada scan 133 ms dan 2082 ms di detik yang
    // sama. Sekarang server ikut mengirim rinciannya lewat response.data:
    //   srv  = total waktu PHP di server
    //   boot = bagian srv yang habis di konstruktor MY_Controller, sebelum kode
    //          scan jalan (migrasi DDL + rebuild pohon menu tiap request)
    // Sisa (ms - srv) adalah jaringan + antrean Apache.
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

        // Rata-rata server vs jaringan: kalau selisihnya besar, biang lambatnya
        // ada di luar PHP (wifi/antrean), bukan di query.
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

        // Tolak dobel di sisi klien: double-Enter scanner tidak perlu round-trip.
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
            url: "scan_logistic/save",
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
                $("#noresi").focus();
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

        // Counter dinaikkan sesuai baris yang benar-benar dibuat server. Di mode
        // NDD, resi yang sudah pernah HO tidak menambah angka HO lagi.
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

        // Scan yang lambat ditandai merah supaya kelihatan langsung dari layar
        // stasiun, tidak perlu buka log. Angka server ditempel di bawahnya:
        // kalau baris merah tapi server kecil, tersangkanya jaringan/antrean.
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

    // ===== SUARA =====
    // Berkas mp3-nya TIDAK diubah -- hanya dihentikan lebih awal. Nada khas tiap
    // kurir tetap dikenali telinga di 0,4 detik pertama, tapi operator tidak
    // perlu menunggu suaranya habis sebelum scan berikutnya.
    //
    // Sebelumnya satu keranjang berisi resi campur kurir berarti tiap scan
    // memutar berkas berbeda dengan durasi berbeda: jnt/lazada/ninja/sicepat
    // ~0,5 detik, jne/rekomen ~1,5 detik, dan yang paling menahan adalah suara
    // error -- suaradouble.mp3 ~6 detik, cancel ~4,5 detik.
    var BATAS_SUARA = {
        // Cuma J&T dan JNE yang masih memakai berkas mp3; kurir lain pindah ke
        // nada sintetis di suaraKurir() (main.php) karena berkasnya kembar.
        'audio-jnt':     400,
        'audio-jne':     400,
        // error: sedikit lebih panjang supaya jelas terdengar beda, tapi jauh
        // dari 3-4,5 detik seperti sebelumnya.
        // Catatan: angka ini dihitung SETELAH lompatan hening di MULAI_SUARA,
        // jadi 800 untuk cancel = 800 ms suara yang benar-benar terdengar.
        'audio-double':  900,
        'audio-fail':    900,
        'audio-cancel':  800,
        // alert.mp3 sekarang khusus error lain-lain. Sukses kurir tak dikenal
        // dipindah ke nada sintetis supaya sukses dan gagal tidak sebunyi.
        'audio-alert':   500
    };
    var BATAS_DEFAULT = 500;

    // Mekanik potong/reset/lompat-hening-nya ada di suaraScan (main.php) supaya
    // halaman scan lain memakai perilaku yang sama persis. Di sini tinggal
    // angkanya, karena halaman HO menahan suara error sedikit lebih lama
    // daripada halaman packer.
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

    // Nada per kurir ada di suaraKurir() (main.php). Dulu pemetaan prefiks resi
    // ke kurir disalin persis di sini dan di handover/scan_handover.php -- dua
    // salinan berarti kurir baru harus didaftarkan dua kali.
    function playCourierAudio(noresi) {
        if (typeof suaraKurir === "function") {
            suaraKurir(noresi);
            return;
        }
        playTag("audio-alert");
    }

    function playErrorAudio(exceptionCode) {
        if (exceptionCode === 'ALREADY_HANDOVER' || exceptionCode === 'ALREADY_SCANNED') {
            playTag('audio-double');
        } else if (exceptionCode === 'NOT_PICKED' || exceptionCode === 'NOT_PACKED') {
            playTag('audio-fail');
        } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
            // Nada sintetis, bukan suaracancel.mp3 lagi. Dipotong di ~0,8 detik,
            // pembukaan berkas cancel dan berkas double terdengar mirip sehingga
            // operator tidak bisa membedakan pesanan batal dari scan dobel.
            if (typeof nadaCancel === 'function') {
                nadaCancel();
            } else {
                playTag('audio-cancel');
            }
        } else {
            // alert.mp3 sekarang milik jalur error saja. Dulu suara ini juga
            // dipakai untuk sukses kurir tak dikenal, jadi scan berhasil dan
            // scan gagal bunyinya sama -- operator tidak bisa membedakan.
            playTag('audio-alert');
        }
    }
});
</script>
