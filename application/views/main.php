
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- META SECTION -->
	<title>BEVERRA - Manajemen Resi</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'): ?>
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
	<?php endif; ?>

	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<!-- END META SECTION -->

	<!-- CSS INCLUDE -->
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/blueimp-gallery/2.41.0/css/blueimp-gallery.min.css" />
	
	<link rel="stylesheet" type="text/css" id="theme" href="assets/css/theme-fresh.css?v=1.0.1" />
	<!-- Panel perekam video packing (Scan Resi Packer Webcam); dibuat oleh assets/js/packer_video.js -->
	<link rel="stylesheet" type="text/css" href="assets/css/packer_video.css?v=<?= @filemtime(FCPATH . 'assets/css/packer_video.css') ?: '1.0.0' ?>" />
	<!-- EOF CSS INCLUDE -->
    <style>
        /* Force clear frames */
        .profile-image, .profile-mini {
            position: relative;
            overflow: hidden !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #eee;
        }
        
        /* Main Sidebar Profile Area */
        .profile-image {
            width: 100px !important;
            height: 100px !important;
            margin: 0 auto !important;
            border: 3px solid #FFF !important;
        }
        
        /* Mini Sidebar Icon */
        .profile-mini {
            width: 32px !important;
            height: 32px !important;
            margin: 10px auto !important;
        }

        /* Image sizing inside frames - Force fit and cover */
        .profile-image img, .profile-mini img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
            display: block !important;
            border-radius: 50% !important;
        }
        /* Ngrok Indicator Styles */
        .ngrok-indicator {
            display: flex;
            align-items: center;
            padding: 0 15px;
            height: 50px;
            border-left: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
        }
        .ngrok-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }
        .ngrok-status-online {
            background-color: #22c55e;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
        }
        .ngrok-status-offline {
            background-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        .ngrok-url {
            margin-left: 5px;
            font-weight: 600;
            color: #60a5fa;
            cursor: pointer;
            text-decoration: none;
        }
        .ngrok-url:hover {
            text-decoration: underline;
        }
        .ngrok-refresh {
            margin-left: 10px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .ngrok-refresh:hover {
            color: #fff;
        }
    </style>
</head>

<body>

    <!-- PREMIUM PRELOADER -->
    <div class="preloader-overlay" id="preloader">
        <div class="jogger-wrap">
            <i class="fa fa-street-view"></i>
        </div>
        <div class="jogging-text">Sabar ya, lagi jogging...</div>
        <div class="loader-progress-container">
            <div class="loader-progress-bar" id="loader-bar"></div>
        </div>
    </div>
    <script>
        (function() {
            var bar = document.getElementById('loader-bar');
            var overlay = document.getElementById('preloader');
            
            function forceHide() {
                if (overlay) overlay.style.display = 'none';
                console.log('Preloader forced hidden');
            }

            // Fallback: Hide after 2.5 seconds regardless
            setTimeout(forceHide, 2500);

            window.addEventListener('load', function() {
                if (bar) bar.style.width = '100%';
                setTimeout(forceHide, 500);
            });
        })();
    </script>
    <!-- END PREMIUM PRELOADER -->
	<!-- START PAGE CONTAINER -->
	<div class="page-container page-navigation-top-fixed">

		<!-- START PAGE SIDEBAR -->
		<div class="page-sidebar page-sidebar-fixed scroll">
			<!-- START X-NAVIGATION -->
			<ul class="x-navigation">
				<li class="xn-logo">
					<a href="">BEVERRA</a>
					<a href="#" class="x-navigation-control"></a>
				</li>

				<li class="xn-profile">
						<a href="#" class="profile-mini">
							<?php 
								$foto_url = base_url('assets/img/no-image.jpg');
								$username = isset($user['username']) ? $user['username'] : 'User';
								$akses = isset($user['akses']) ? $user['akses'] : '-';
								
								if (!empty($user['foto'])) {
									$foto_url = (strpos($user['foto'], 'http') === 0) ? $user['foto'] : base_url($user['foto']);
								}
							?>
							<img src="<?= $foto_url ?>" alt="<?= $username ?>" />
						</a>
						<div class="profile">
							<div class="profile-image">
								<img src="<?= $foto_url ?>" alt="<?= $username ?>" />
							</div>
						<div class="profile-data">
							<div class="profile-data-name"><?= $username ?></div>
							<div class="profile-data-title"><?= $akses . ' - ' . (isset($nama_pk) ? $nama_pk : '') ?></div>
                            <?php if (!empty($status_performa)) : ?>
                            <div class="profile-data-title">Status: <?= $status_performa ?></div>
                            <?php endif; ?>
						</div>					</div>
				</li>

				<li class="xn-title">Navigation</li>

				<li class="active">
					<a href=""><span class="fa fa-desktop"></span> <span class="xn-text">Dashboard</span></a>
				</li>

				<?= $html_menu_tree ?>
			</ul>
			<!-- END X-NAVIGATION -->
		</div>
		<!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div class="page-content">

			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button">
					<a href="#" class="x-navigation-minimize"><span class="fa fa-dedent"></span></a>
				</li>
				<!-- END TOGGLE NAVIGATION -->
				
				<!-- NOTIFICATION -->
				<li class="xn-icon-button pull-right">
					<a href="#"><span class="fa fa-bell"></span></a>
					<div class="informer informer-danger" id="notif-count"><?= isset($notif_count) ? $notif_count : 0 ?></div>
					<div class="panel panel-primary animated zoomIn xn-drop-left xn-panel-dragging">
						<div class="panel-heading">
							<h3 class="panel-title"><span class="fa fa-bell"></span> Notifikasi</h3>
							<div class="pull-right">
								<span class="label label-danger" id="notif-count-label"><?= isset($notif_count) ? $notif_count : 0 ?> Baru</span>
							</div>
						</div>
						<div class="panel-body list-group list-group-contacts scroll" id="notif-list-container" style="height: 250px;">
							<?php if(!empty($notif_list)): ?>
								<?php foreach($notif_list as $notif): ?>
									<a href="javascript:void(0);" class="list-group-item mark-read" data-id="<?= $notif['id'] ?>">
										<div class="list-group-status <?= $notif['is_read'] ? '' : 'status-online' ?>"></div>
										<span class="contacts-title"><?= $notif['category'] ?></span>
										<p><?= $notif['message'] ?></p>
										<small class="text-muted"><?= date('d M, H:i', strtotime($notif['created_at'])) ?></small>
									</a>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="text-center" style="padding: 20px;">Tidak ada notifikasi</div>
							<?php endif; ?>
						</div>
						<div class="panel-footer text-center">
							<a href="javascript:void(0);" id="mark-all-read">Tandai semua telah dibaca</a>
						</div>
					</div>
				</li>
				<!-- END NOTIFICATION -->

				<!-- NGROK STATUS -->
				<li class="pull-right">
					<div class="ngrok-indicator">
						<span id="ngrok-dot" class="ngrok-status-dot ngrok-status-offline"></span>
						<span id="ngrok-text">Ngrok: Offline</span>
						<a id="ngrok-link" href="#" target="_blank" class="ngrok-url" style="display:none;">Copy URL</a>
						<i id="ngrok-refresh-btn" class="fa fa-refresh ngrok-refresh" title="Refresh Ngrok Status"></i>
					</div>
				</li>
				<!-- END NGROK STATUS -->

				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right">
					<a href="#" class="mb-control" data-box="#mb-signout"><span class="fa fa-sign-out"></span></a>
				</li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li>Welcome</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<?= $content ?>
			</div>
			<!-- END PAGE CONTENT WRAPPER -->

            <!-- FOOTER -->
            <div class="row">
                <div class="col-md-12 text-center" style="padding: 20px; color: #888; border-top: 1px solid #eee; margin-top: 20px;">
                    © ashari wibowo - beverra @2026
                </div>
            </div>
            <!-- END FOOTER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->

	<!-- MESSAGE BOX-->
	<div class="message-box animated fadeIn" data-sound="alert" id="mb-signout">
		<div class="mb-container">
			<div class="mb-middle">
				<div class="mb-title"><span class="fa fa-sign-out"></span> Log <strong>Out</strong> ?</div>
				<div class="mb-content">
					<p>Are you sure you want to log out?</p>
					<p>Press No if youwant to continue work. Press Yes to logout current user.</p>
				</div>
				<div class="mb-footer">
					<div class="pull-right">
						<a href="logout" class="btn btn-success btn-lg">Yes</a>
						<button class="btn btn-default btn-lg mb-control-close">No</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- END MESSAGE BOX-->

	<!-- START PRELOADS -->
	<audio id="audio-alert" src="<?= base_url('assets/audio/alert.mp3') ?>" preload="auto"></audio>
	<audio id="audio-fail" src="<?= base_url('assets/audio/fail.mp3') ?>" preload="auto"></audio>
	<audio id="audio-error" src="<?= base_url('assets/audio/error.mp3') ?>" preload="auto"></audio>
	<audio id="audio-jnt" src="<?= base_url('assets/audio/jnt.mp3') ?>" preload="auto"></audio>
	<audio id="audio-shopee" src="<?= base_url('assets/audio/shopee.mp3') ?>" preload="auto"></audio>
	<audio id="audio-lazada" src="<?= base_url('assets/audio/lazada.mp3') ?>" preload="auto"></audio>
	<audio id="audio-jne" src="<?= base_url('assets/audio/jne.mp3') ?>" preload="auto"></audio>
	<audio id="audio-rekomen" src="<?= base_url('assets/audio/rekomen.mp3') ?>" preload="auto"></audio>
	<audio id="audio-sicepat" src="<?= base_url('assets/audio/sicepat.mp3') ?>" preload="auto"></audio>
	<audio id="audio-ninja" src="<?= base_url('assets/audio/ninja.mp3') ?>" preload="auto"></audio>
	<audio id="audio-instant" src="<?= base_url('assets/audio/instant.mp3') ?>" preload="auto"></audio>
	<audio id="audio-alexis" src="<?= base_url('assets/audio/ALEXIS.mp3') ?>" preload="auto"></audio>
	<audio id="audio-wrong" src="<?= base_url('assets/audio/WRONG.mp3') ?>" preload="auto"></audio>
	<audio id="audio-cancelcoi" src="<?= base_url('assets/audio/cancelcoi.mp3') ?>" preload="auto"></audio>
	<audio id="audio-double" src="<?= base_url('assets/audio/suaradouble.mp3') ?>" preload="auto"></audio>
	<audio id="audio-cancel" src="<?= base_url('assets/audio/suaracancel.mp3') ?>" preload="auto"></audio>
	<!--
		Empat berkas di bawah ini suara ORANG, bukan nada. Isinya kalimat pendek
		("sudah packing", "paket double", "sudah scan", "cancel") supaya operator
		tahu penyebab gagalnya tanpa harus membaca layar. Berbeda dari berkas di
		atas, suara ini TIDAK BOLEH dipotong di tengah -- lihat BATAS_SUARA.
		Formatnya m4a (AAC): didukung Chrome, Edge, Firefox, dan Safari.
	-->
	<audio id="audio-sudah-packing" src="<?= base_url('assets/audio/sudah-packing.m4a') ?>" preload="auto"></audio>
	<audio id="audio-paket-double" src="<?= base_url('assets/audio/paket-double.m4a') ?>" preload="auto"></audio>
	<audio id="audio-sudah-scan" src="<?= base_url('assets/audio/sudah-scan.m4a') ?>" preload="auto"></audio>
	<audio id="audio-cancel-order" src="<?= base_url('assets/audio/cancel-order.m4a') ?>" preload="auto"></audio>
	<!-- END PRELOADS -->

	<!-- START PEMUTAR SUARA SCAN -->
	<script type="text/javascript">
	/*
	 * suaraScan(id) -- satu-satunya cara memutar berkas suara di halaman scan.
	 *
	 * Berkas mp3-nya sengaja TIDAK diubah, hanya dihentikan lebih awal. Beberapa
	 * berkas panjangnya jauh melebihi satu siklus scan: error.mp3 ~3,8 detik,
	 * suaracancel ~4,6 detik, cancelcoi ~6,1 detik, suaradouble ~6,4 detik.
	 * Diputar utuh, operator menunggu suara habis sebelum scan berikutnya, dan
	 * scan beruntun jadi terasa berat -- padahal server sudah menjawab.
	 *
	 * Tiga hal yang ditangani di sini, semuanya penyebab delay yang nyata:
	 *   1. Suara dipotong di BATAS_SUARA. Nada pembukanya sudah cukup dikenali.
	 *   2. currentTime di-reset tiap pemanggilan. Tanpa ini, play() pada elemen
	 *      yang masih berbunyi tidak melakukan apa-apa -- scan kedua terdengar
	 *      "tidak bunyi" padahal berhasil.
	 *   3. Elemen <audio> dipakai ulang, tidak pernah `new Audio(url)` per scan.
	 *      Bikin elemen baru tiap scan berarti fetch + decode ulang tiap scan.
	 *
	 * Halaman boleh menimpa angkanya: suaraScan('audio-wrong', { batas: 400 }).
	 */
	(function (global) {
		var BATAS_SUARA = {
			// pendek, biarkan utuh
			'audio-alert':      500,
			'audio-alexis':     500,
			// sedang, sekitar 1 detik
			'audio-wrong':      700,
			'audio-fail':       700,
			'audio-slow-alert': 700,
			// panjang, wajib dipotong. Angka ini dihitung SETELAH lompatan
			// hening di MULAI_SUARA, jadi 800 untuk cancel berarti 800 ms suara
			// yang benar-benar terdengar.
			'audio-error':      800,
			'audio-cancel':     800,
			'audio-double':     900,
			'audio-cancelcoi':  900,
			// nada kurir: cukup pembukanya
			'audio-jnt':        400,
			'audio-jne':        400,
			// Suara ucapan, bukan nada. Dipotong di 700-900 ms kalimatnya tinggal
			// separuh ("sudah pack...", "can...") dan justru bikin operator
			// bertanya-tanya. Angka di bawah = posisi bunyi terakhir + margin,
			// diukur dengan decodeAudioData: sudah-packing bunyi terakhir 1,843 s
			// (berkas 1,856 s), paket-double 1,783 s, sudah-scan 2,049 s,
			// cancel-order 1,982 s. Hening di awal cuma ~0,1 s, tidak perlu
			// dilompati seperti audio-cancel.
			'audio-sudah-packing': 1950,
			'audio-paket-double':  1900,
			'audio-sudah-scan':    2150,
			'audio-cancel-order':  2100
		};
		var BATAS_UMUM = 700;

		// suaracancel.mp3 baru berbunyi di 751 ms (diukur dengan decodeAudioData).
		// Heningnya dilompati, bukan potongannya yang diperpanjang -- kalau
		// diperpanjang, delaynya balik lagi.
		var MULAI_SUARA = {
			'audio-cancel': 0.75
		};

		var pemotong = {};

		function suaraScan(id, opsi) {
			var el = document.getElementById(id);
			if (!el) return;
			opsi = opsi || {};

			// Scan beruntun: batalkan timer potong milik pemutaran sebelumnya
			// supaya tidak ikut menghentikan suara yang baru saja dimulai.
			if (pemotong[id]) {
				clearTimeout(pemotong[id]);
				pemotong[id] = null;
			}

			var mulai = (typeof opsi.mulai === 'number') ? opsi.mulai : (MULAI_SUARA[id] || 0);
			var batas = (typeof opsi.batas === 'number') ? opsi.batas : (BATAS_SUARA[id] || BATAS_UMUM);

			try { el.currentTime = mulai; } catch (err) {}

			var pasangPotong = function () {
				// Kalau berkas belum siap saat currentTime diset di atas, seek-nya
				// bisa diabaikan browser dan suara mulai dari 0 lagi. Pastikan
				// sekali lagi di sini.
				if (mulai > 0 && el.currentTime < mulai) {
					try { el.currentTime = mulai; } catch (err) {}
				}
				pemotong[id] = setTimeout(function () {
					try { el.pause(); el.currentTime = mulai; } catch (err) {}
					pemotong[id] = null;
				}, batas);
			};

			// Timer dihitung sejak suara BENAR-BENAR mulai diputar, bukan sejak
			// play() dipanggil. Kalau berkas belum selesai dimuat, play() baru
			// jalan beberapa ratus milidetik kemudian -- timer yang start duluan
			// akan memotongnya sebelum sempat terdengar.
			var p = el.play();
			if (p && p.then) {
				p.then(pasangPotong).catch(function () { pasangPotong(); });
			} else {
				pasangPotong();
			}
		}

		global.suaraScan = suaraScan;
	})(window);
	</script>

	<script type="text/javascript">
	/*
	 * suaraKurir(noresi) -- nada sukses per kurir untuk halaman scan HO.
	 *
	 * Ada di sini, bukan di masing-masing view, karena scan_logistic/scan_view
	 * dan handover/scan_handover memetakan prefiks resi ke kurir dengan aturan
	 * yang sama persis. Dua salinan berarti satu kurir baru harus didaftarkan
	 * dua kali, dan itu sudah pernah bikin dua halaman berbeda bunyinya.
	 *
	 * Kenapa sebagian nada disintesis, bukan mp3: delapan berkas di
	 * assets/audio isinya kembar (cek md5). jnt = lazada = ninja = sicepat =
	 * instant, dan jne = rekomen = shopee. Jadi lima kurir kedengarannya satu
	 * kurir. Berkas mp3-nya sengaja TIDAK diganti -- J&T dan JNE tetap memakai
	 * berkas aslinya supaya operator tidak kehilangan patokan lama, sisanya
	 * dapat nada sintetis yang dibedakan lewat pola ketukan dan bentuk
	 * gelombang, bukan cuma tinggi nada.
	 */
	(function (global) {
		var audioCtx = null;
		function getAudioCtx() {
			if (!audioCtx) audioCtx = new (global.AudioContext || global.webkitAudioContext)();
			if (audioCtx.state === 'suspended') audioCtx.resume();
			return audioCtx;
		}

		// Nada yang sudah selesai WAJIB dilepas dari graf audio. Tanpa ini setiap
		// scan Shopee meninggalkan gain node yang masih tersambung ke destination;
		// dengan ~7.500 scan Shopee/hari graf-nya menumpuk jadi puluhan ribu node
		// yang tetap diproses audio thread tiap render quantum -- makin siang makin
		// berat, dan paling terasa saat berpindah kurir Shopee <-> J&T.
		function autoLepas(osc, gain) {
			osc.onended = function () {
				try { osc.disconnect(); } catch (e) {}
				try { gain.disconnect(); } catch (e) {}
			};
		}

		// Semua nada lewat satu master + kompresor.
		//
		// Volume nada dinaikkan 2026-08-10 atas laporan operator: lazada, rekomen,
		// sicepat, ninja, instant, dan bel "tak dikenal" terdengar terlalu pelan
		// dibanding Shopee dan J&T. Menaikkan angka volume saja berisiko pecah,
		// karena nada yang bertumpuk (Shopee 3 nada sekaligus) bisa melewati 1.0.
		// Kompresor menahan puncaknya, jadi suara terdengar lebih keras tanpa
		// gemeretak.
		var master = null;
		function getMaster() {
			var ctx = getAudioCtx();
			if (!master) {
				var comp = ctx.createDynamicsCompressor();
				comp.threshold.value = -12;
				comp.ratio.value = 8;
				comp.attack.value = 0.003;
				comp.release.value = 0.15;

				master = ctx.createGain();
				master.gain.value = 1.0;
				master.connect(comp);
				comp.connect(ctx.destination);
			}
			return master;
		}

		// Menguatkan berkas mp3 tanpa mengubah berkasnya. Elemen <audio> volume
		// maksimalnya 1.0, jadi satu-satunya cara menaikkan lagi adalah lewat
		// Web Audio.
		//
		// HATI-HATI: elemen yang sudah disambungkan ke Web Audio TIDAK berbunyi
		// lagi lewat jalur biasa. Karena itu penyambungan hanya dilakukan saat
		// AudioContext benar-benar 'running' -- kalau tidak, elemen dibiarkan apa
		// adanya dan tetap berbunyi seperti biasa, cuma tidak dikuatkan.
		var sudahDikuatkan = {};
		function kuatkanMp3(id, faktor) {
			if (sudahDikuatkan[id]) return;

			var el = document.getElementById(id);
			if (!el) return;

			var ctx = getAudioCtx();
			if (ctx.state !== 'running') return;

			try {
				var src = ctx.createMediaElementSource(el);
				var g = ctx.createGain();
				g.gain.value = faktor;
				src.connect(g);
				g.connect(getMaster());
			} catch (e) {
				// Sudah pernah disambung, atau browser tidak mendukung. Jangan
				// dicoba lagi -- percobaan kedua pasti melempar error yang sama.
			}
			sudahDikuatkan[id] = true;
		}

		// Tiap nada: { f: frekuensi, f2: frekuensi akhir (opsional, jadi glide),
		//              at: mulai detik ke-berapa, dur: panjang detik }
		function playNada(bentuk, nada, volume) {
			var ctx = getAudioCtx();
			var now = ctx.currentTime;
			var vol = volume || 0.3;

			nada.forEach(function (n) {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				var mulai = now + n.at;
				var selesai = mulai + n.dur;

				osc.type = bentuk;
				osc.frequency.setValueAtTime(n.f, mulai);
				if (n.f2) osc.frequency.linearRampToValueAtTime(n.f2, selesai);

				// Naik cepat lalu meluruh: tanpa attack sesaat, tiap nada bunyi
				// "klik" karena gelombang dipotong mendadak di awal.
				gain.gain.setValueAtTime(0.0001, mulai);
				gain.gain.linearRampToValueAtTime(vol, mulai + 0.015);
				gain.gain.exponentialRampToValueAtTime(0.0001, selesai);

				osc.connect(gain);
				gain.connect(getMaster());
				autoLepas(osc, gain);
				osc.start(mulai);
				osc.stop(selesai + 0.02);
			});
		}

		// Shopee: tiga nada naik (A5, C#6, E6) plus shimmer tinggi
		function nadaShopee() {
			var ctx = getAudioCtx();
			playNada('sine', [
				{ f: 880.00,  at: 0,    dur: 0.35 },
				{ f: 1108.73, at: 0.12, dur: 0.35 },
				{ f: 1318.51, at: 0.24, dur: 0.35 }
			], 0.35);

			setTimeout(function () {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				osc.type = 'triangle';
				osc.frequency.value = 2637; // E7
				gain.gain.setValueAtTime(0.15, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
				osc.connect(gain);
				gain.connect(getMaster());
				autoLepas(osc, gain);
				osc.start();
				osc.stop(ctx.currentTime + 0.5);
			}, 300);
		}

		// Lazada: tiga blip pendek nada sama, cepat -- "tit-tit-tit"
		function nadaLazada() {
			playNada('triangle', [
				{ f: 1318.51, at: 0,    dur: 0.07 },
				{ f: 1318.51, at: 0.10, dur: 0.07 },
				{ f: 1318.51, at: 0.20, dur: 0.09 }
			], 0.60);
		}

		// SiCepat: tangga naik cepat, kasar -- kesan "zip"
		function nadaSicepat() {
			playNada('sawtooth', [
				{ f: 523.25,  at: 0,    dur: 0.07 },
				{ f: 659.25,  at: 0.05, dur: 0.07 },
				{ f: 783.99,  at: 0.10, dur: 0.07 },
				{ f: 1046.50, at: 0.15, dur: 0.12 }
			], 0.55);
		}

		// Rekomen: tiga nada turun -- persis kebalikan nada Shopee yang naik
		function nadaRekomen() {
			playNada('sine', [
				{ f: 1318.51, at: 0,    dur: 0.16 },
				{ f: 1046.50, at: 0.12, dur: 0.16 },
				{ f: 783.99,  at: 0.24, dur: 0.22 }
			], 0.60);
		}

		// Ninja: dua ketukan rendah dan tumpul -- "dun-dun"
		function nadaNinja() {
			playNada('square', [
				{ f: 196.00, at: 0,    dur: 0.12 },
				{ f: 196.00, at: 0.18, dur: 0.18 }
			], 0.75);
		}

		// Instant: satu lompatan satu oktaf ke atas, mulus
		function nadaInstant() {
			playNada('triangle', [
				{ f: 783.99, f2: 1567.98, at: 0, dur: 0.28 }
			], 0.60);
		}

		// Kurir tak dikenal: satu bel netral, tidak menyerupai kurir mana pun.
		// Dulu memakai alert.mp3, padahal berkas itu juga dipakai untuk error
		// lain-lain -- sukses dan gagal jadi sebunyi.
		function nadaLain() {
			playNada('sine', [
				{ f: 880.00, at: 0, dur: 0.38 }
			], 0.60);
		}

		// Nada CANCEL/pesanan selesai. Dibuat sintetis 2026-08-10 karena operator
		// melaporkan suaranya tertukar dengan suara Double Scan: suaracancel.mp3
		// dan suaradouble.mp3 sama-sama dipotong di ~0,8-0,9 detik, dan potongan
		// pembukanya terdengar mirip.
		//
		// Sengaja dibuat rendah dan menggerung-turun -- tidak menyerupai satu pun
		// nada kurir maupun berkas double yang nadanya tinggi.
		function nadaCancel() {
			playNada('sawtooth', [
				{ f: 320, f2: 160, at: 0,    dur: 0.30 },
				{ f: 260, f2: 130, at: 0.22, dur: 0.34 }
			], 0.55);
		}

		// Tiap cabang WAJIB bunyi beda dari cabang lain, termasuk beda dari
		// suara error di halaman pemanggil.
		function suaraKurir(noresi) {
			var resi = String(noresi || '').toUpperCase();
			var kode2 = resi.substring(0, 2);
			var kode3 = resi.substring(0, 3);

			if (kode2 === 'SP') {
				nadaShopee();
			} else if (kode2 === 'JP' || kode2 === 'JX' || kode2 === 'JO' || kode2 === 'JZ' || kode2 === 'TJ' || kode2 === '20' || kode2 === 'JY') {
				global.suaraScan('audio-jnt');   // mp3 asli, tetap dipertahankan
			} else if (kode2 === 'JN' || kode2 === 'LX' || kode2 === 'NL') {
				nadaLazada();
			} else if (kode2 === 'JT' || kode2 === 'TL') {
				// mp3 asli tetap dipertahankan, hanya dikuatkan lewat Web Audio.
				// Operator melaporkan JNE terdengar jauh lebih pelan dari J&T.
				kuatkanMp3('audio-jne', 2.5);
				global.suaraScan('audio-jne');
			} else if (kode2 === '00' || kode2 === 'TK') {
				if (kode3 === 'TKP') { nadaRekomen(); } else { nadaSicepat(); }
			} else if (kode2 === 'NJ') {
				nadaNinja();
			} else if (kode2 === 'IN' || kode2 === '24' || kode2 === 'GT') {
				nadaInstant();
			} else {
				nadaLain();
			}
		}

		global.suaraKurir = suaraKurir;
		global.nadaCancel = nadaCancel;
	})(window);
	</script>
	<!-- END PEMUTAR SUARA SCAN -->

	<!-- START PLUGINS -->
	<script type="text/javascript" src="assets/js/plugins/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap/bootstrap.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap/bootstrap-select.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap/bootstrap-datepicker.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap/bootstrap-timepicker.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap/bootstrap-file-input.js"></script>
	<script type="text/javascript" src="assets/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/owl/owl.carousel.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/jstree/jstree.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/summernote/summernote.js"></script>
	<script type="text/javascript" src="assets/js/plugins/icheck/icheck.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/datatables/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/blueimp/jquery.blueimp-gallery.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/nvd3/lib/d3.v3.js"></script>
	<script type="text/javascript" src="assets/js/plugins/nvd3/nv.d3.min.js"></script>
	<script type='text/javascript' src='assets/js/plugins/jquery-validation/jquery.validate.js'></script>

	<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

	<script type='text/javascript' src='assets/js/plugins/noty/jquery.noty.js'></script>
	<script type='text/javascript' src='assets/js/plugins/noty/layouts/center.js'></script>
	<script type='text/javascript' src='assets/js/plugins/noty/layouts/topRight.js'></script>

	<script type='text/javascript' src='assets/js/plugins/noty/themes/default.js'></script>
	<!-- END PLUGINS -->

	<!-- START TEMPLATE -->
	<script type="text/javascript" src="assets/js/plugins.js?v=1.0.1"></script>
	<script type="text/javascript" src="assets/js/actions.js?v=1.0.1"></script>
	<!-- Perekam video packing. Dimuat global (bukan di view) karena panel kameranya
	     harus selamat dari pergantian isi .page-content-wrap tiap kali packer scan.
	     Pasif sampai halaman Scan Resi Packer (Webcam) memanggil PackerVideo.sinkron(). -->
	<script type="text/javascript" src="assets/js/packer_video.js?v=<?= @filemtime(FCPATH . 'assets/js/packer_video.js') ?: '1.0.0' ?>"></script>
	<!-- END TEMPLATE -->

	<!-- NOTIFIKASI -->
    <script src="https://js.pusher.com/8.0/pusher.min.js" async></script>
    <script>
    var userRoleId = "<?= $user['hakakses'] ?>";
    var allowedRoleIds = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

    if (allowedRoleIds.includes(userRoleId)) {
        function initPusher() {
            if (typeof Pusher === "undefined") {
                setTimeout(initPusher, 1000);
                return;
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (Notification.permission !== "denied" && Notification.permission !== "granted") {
                    Notification.requestPermission();
                }
            });

            var pusher = new Pusher('c32db7f53e0dc19fc7b7', {
                cluster: 'ap1'
            });

            var channelName = 'notif-role-' + userRoleId;
            var channel = pusher.subscribe(channelName);

            channel.bind('notif-event', function(data) {
                var audioInstant = document.getElementById("audio-instant");
                if (audioInstant) audioInstant.play();

                refreshNotifications();

                if (Notification.permission === "granted") {
                    var notification = new Notification(data.title, {
                        body: data.message,
                        icon: "<?= !empty($user['foto']) ? $user['foto'] : base_url('assets/img/no-image.jpg') ?>"
                    });
                    notification.onclick = function() { window.focus(); };
                }

                if (typeof noty !== "undefined") {
                    noty({
                        text: '<strong><i class="fa fa-bell"></i> ' + data.title + '</strong><br/>' + data.message,
                        layout: 'topRight',
                        type: 'information',
                        theme: 'relax',
                        timeout: 5000
                    });
                }
            });
        }
        initPusher();
    }

    // Function untuk refresh lonceng
    function refreshNotifications() {
        $.ajax({
            url: "<?= base_url('welcome/get_notif_ajax') ?>",
            type: "GET",
            dataType: "json",
            global: false, // JANGAN pancing kotak merah error
            success: function(resp) {
                if(resp.status === 'success') {
                    $('#notif-count').text(resp.data.count);
                    $('#notif-count-label').text(resp.data.count + ' Baru');
                    
                    var html = '';
                    if(resp.data.list.length > 0) {
                        resp.data.list.forEach(function(n) {
                            html += `<a href="javascript:void(0);" class="list-group-item mark-read" data-id="${n.id}">
                                        <div class="list-group-status ${n.is_read == 0 ? 'status-online' : ''}"></div>
                                        <span class="contacts-title">${n.category}</span>
                                        <p>${n.message}</p>
                                        <small class="text-muted">${n.created_at}</small>
                                    </a>`;
                        });
                    } else {
                        html = '<div class="text-center" style="padding: 20px;">Tidak ada notifikasi</div>';
                    }
                    $('#notif-list-container').html(html);
                }
            }
        });
    }

    // Handle Click Mark as Read
    $(document).on('click', '.mark-read', function() {
        var id = $(this).data('id');
        var $this = $(this);
        $.post("<?= base_url('welcome/mark_notif_read') ?>", {id: id}, function(resp) {
            $this.find('.list-group-status').removeClass('status-online');
            refreshNotifications();
        }, 'json');
    });

    // Handle Mark All as Read
    $(document).on('click', '#mark-all-read', function() {
        $.post("<?= base_url('welcome/mark_all_read') ?>", {}, function(resp) {
            refreshNotifications();
        }, 'json');
    });

    // Panggil initPusher jika diperbolehkan
    if (allowedRoleIds.includes(userRoleId)) {
        initPusher();
    }
    </script>

    <div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
        <div class="slides"></div>
        <h3 class="title"></h3>
        <a class="prev">‹</a>
        <a class="next">›</a>
        <a class="close">×</a>
        <a class="play-pause"></a>
        <ol class="indicator"></ol>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-gallery/2.41.0/js/blueimp-gallery.min.js"></script>
    

    <script>
    $(document).ready(function() {
        // Sidebar Color Coding
        $('.x-navigation > li').each(function() {
            var $link = $(this).find('> a');
            var text = $link.text().toUpperCase();
            var $icon = $link.find('.fa');
            
            if (text.includes('RESI')) {
                $(this).css('border-left', '5px solid #3b82f6');
                $icon.css('color', '#3b82f6');
            } else if (text.includes('PICKER')) {
                $(this).css('border-left', '5px solid #22c55e');
                $icon.css('color', '#22c55e');
            } else if (text.includes('PACKER')) {
                $(this).css('border-left', '5px solid #eab308');
                $icon.css('color', '#eab308');
            } else if (text.includes('HO') || text.includes('LOGISTIC') || text.includes('HANDOVER')) {
                $(this).css('border-left', '5px solid #ef4444');
                $icon.css('color', '#ef4444');
            } else if (text.includes('PURCHASING')) {
                $(this).css('border-left', '5px solid #a855f7');
                $icon.css('color', '#a855f7');
            } else if (text.includes('ACCOUNTING') || text.includes('KEUANGAN') || text.includes('FINANCE')) {
                $(this).css('border-left', '5px solid #ec4899');
                $icon.css('color', '#ec4899');
            } else if (text.includes('INVENTORY') || text.includes('GUDANG') || text.includes('STOK')) {
                $(this).css('border-left', '5px solid #f97316');
                $icon.css('color', '#f97316');
            } else if (text.includes('RETUR') || text.includes('CS')) {
                $(this).css('border-left', '5px solid #6366f1');
                $icon.css('color', '#6366f1');
            } else if (text.includes('INBOUND')) {
                $(this).css('border-left', '5px solid #14b8a6');
                $icon.css('color', '#14b8a6');
            } else if (text.includes('RESTOCK')) {
                $(this).css('border-left', '5px solid #f59e0b');
                $icon.css('color', '#f59e0b');
            } else if (text.includes('USER') || text.includes('ADMIN') || text.includes('SETTING') || text.includes('MASTER')) {
                $(this).css('border-left', '5px solid #64748b');
                $icon.css('color', '#64748b');
            } else if (text.includes('MONITORING') || text.includes('NGROK')) {
                $(this).css('border-left', '5px solid #8b5cf6');
                $icon.css('color', '#8b5cf6');
            }
        });

        // Ngrok Status Polling
        function checkNgrok() {
            var $dot = $('#ngrok-dot');
            var $text = $('#ngrok-text');
            var $link = $('#ngrok-link');
            var $refresh = $('#ngrok-refresh-btn');

            $refresh.addClass('fa-spin');

            $.ajax({
                url: "<?= base_url('welcome/check_ngrok_status') ?>",
                type: "GET",
                dataType: "json",
                global: false, // Prevent global AJAX error handling (red box)
                success: function(resp) {
                    $refresh.removeClass('fa-spin');
                    if (resp.status === 'online') {
                        $dot.removeClass('ngrok-status-offline').addClass('ngrok-status-online');
                        $text.text('Ngrok: Online');
                        $link.attr('href', resp.url).text(resp.url).show();
                    } else {
                        $dot.removeClass('ngrok-status-online').addClass('ngrok-status-offline');
                        $text.text('Ngrok: Offline');
                        $link.hide();
                    }
                },
                error: function() {
                    $refresh.removeClass('fa-spin');
                    $dot.removeClass('ngrok-status-online').addClass('ngrok-status-offline');
                    $text.text('Ngrok: Offline');
                    $link.hide();
                }
            });
        }

        // Initial check
        checkNgrok();

        // Check every 60 seconds
        setInterval(checkNgrok, 60000);

        // Manual refresh
        $('#ngrok-refresh-btn').click(function() {
            checkNgrok();
        });

        // Copy URL on click
        $('#ngrok-link').click(function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            navigator.clipboard.writeText(url).then(function() {
                if (typeof noty !== "undefined") {
                    noty({
                        text: 'URL copied to clipboard!',
                        layout: 'topRight',
                        type: 'success',
                        timeout: 2000
                    });
                }
            });
        });
    });
    </script>
</body>

</html>