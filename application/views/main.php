
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- META SECTION -->
	<title><?= !empty($mode_arsip) ? "[ARSIP] " : "" ?>BEVERRA - Manajemen Resi</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'): ?>
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
	<?php endif; ?>

	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<!-- END META SECTION -->

	<!-- CSS INCLUDE -->
	<!-- Semua aset dimuat lokal (tanpa CDN) agar aplikasi tetap jalan saat internet mati; Font Awesome 4.7 ikut lewat theme-*.css -->
	<link rel="stylesheet" type="text/css" href="assets/css/daterangepicker/daterangepicker.css" />
	<link rel="stylesheet" type="text/css" href="assets/css/fonts-lokal.css" />
	<link rel="stylesheet" href="assets/css/blueimp/blueimp-gallery.min.css" />
	
	<link rel="stylesheet" type="text/css" id="theme" href="assets/css/theme-fresh.css?v=1.0.2" />
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

				<li class="xn-menu-cari">
					<input type="search" id="cari-menu" placeholder="Cari menu..." autocomplete="off" aria-label="Cari menu">
				</li>

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

			<?php if (!empty($mode_arsip)) : // Banner Mode Arsip -- lihat MY_Controller::terapkan_mode_arsip() ?>
			<div style="background:#c9302c;color:#fff;padding:8px 20px;font-size:15px;font-weight:bold;letter-spacing:.3px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
				<span><span class="fa fa-archive"></span> MODE ARSIP &mdash; Anda melihat <code style="color:#fff;background:rgba(0,0,0,.25)"><?= $mode_arsip_db ?></code> (<?= !empty($mode_arsip_tulis) ? "TULISAN DIIZINKAN" : "hanya baca" ?>). Ini BUKAN data live.</span>
				<a href="<?= base_url("arsip/keluar") ?>" class="btn btn-default btn-sm" style="font-weight:bold"><span class="fa fa-sign-out"></span> Kembali ke LIVE</a>
			</div>
			<?php endif; ?>

			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button">
					<a href="#" class="x-navigation-minimize" title="Sembunyikan / tampilkan menu"><span class="fa fa-dedent"></span></a>
				</li>
				<!-- END TOGGLE NAVIGATION -->
				
				<!-- NOTIFICATION -->
				<li class="xn-icon-button pull-right">
					<a href="#" title="Notifikasi"><span class="fa fa-bell"></span></a>
					<div class="informer informer-danger" id="notif-count"<?= empty($notif_count) ? ' style="display:none"' : '' ?>><?= !empty($notif_count) && $notif_count > 99 ? '99+' : (isset($notif_count) ? $notif_count : 0) ?></div>
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
									<a href="javascript:void(0);" class="list-group-item mark-read<?= $notif['is_read'] ? ' notif-dibaca' : '' ?>" data-id="<?= $notif['id'] ?>">
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
							<small class="text-muted pull-left" id="notif-ringkas"><?= (!empty($notif_list) && !empty($notif_count) && $notif_count > count($notif_list)) ? count($notif_list) . ' terbaru dari ' . $notif_count . ' belum dibaca' : '' ?></small>
							<a href="javascript:void(0);" id="mark-all-read">Tandai semua dibaca</a>
						</div>
					</div>
				</li>
				<!-- END NOTIFICATION -->

				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right">
					<a href="#" class="mb-control" data-box="#mb-signout" title="Keluar"><span class="fa fa-sign-out"></span></a>
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
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->

	<!-- MESSAGE BOX-->
	<div class="message-box animated fadeIn" data-sound="alert" id="mb-signout">
		<div class="mb-container">
			<div class="mb-middle">
				<div class="mb-title"><span class="fa fa-sign-out"></span> <strong>Keluar</strong> dari aplikasi?</div>
				<div class="mb-content">
					<p>Sesi Anda akan diakhiri. Pilih Batal untuk tetap bekerja.</p>
				</div>
				<div class="mb-footer">
					<div class="pull-right">
						<a href="logout" class="btn btn-success btn-lg">Ya, keluar</a>
						<button class="btn btn-default btn-lg mb-control-close">Batal</button>
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
		Lima berkas di bawah ini suara ORANG, bukan nada. Isinya kalimat pendek
		("sudah packing", "paket double", "sudah scan", "cancel", "tidak
		ditemukan") supaya operator tahu penyebab gagalnya tanpa harus membaca
		layar. Berbeda dari berkas di atas, suara ini TIDAK BOLEH dipotong di
		tengah -- lihat BATAS_SUARA.
		Formatnya m4a (AAC): didukung Chrome, Edge, Firefox, dan Safari.

		audio-tidak-ditemukan dipakai SEMUA halaman scan resi (picker, packer,
		HO, receipt, retur, resi team, inbound) untuk satu penyebab: nomor resi
		tidak ada di tblprintresi. Dulu kasus ini ikut nada salah umum
		(WRONG/alert/fail, beda-beda tiap halaman), jadi operator tidak bisa
		membedakan "salah scan barcode" dari penolakan lain.
	-->
	<audio id="audio-sudah-packing" src="<?= base_url('assets/audio/sudah-packing.m4a') ?>" preload="auto"></audio>
	<audio id="audio-paket-double" src="<?= base_url('assets/audio/paket-double.m4a') ?>" preload="auto"></audio>
	<audio id="audio-sudah-scan" src="<?= base_url('assets/audio/sudah-scan.m4a') ?>" preload="auto"></audio>
	<audio id="audio-cancel-order" src="<?= base_url('assets/audio/cancel-order.m4a') ?>" preload="auto"></audio>
	<audio id="audio-tidak-ditemukan" src="<?= base_url('assets/audio/tidak-ditemukan.m4a') ?>" preload="auto"></audio>
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
			// tidak-ditemukan: berkas 1,408 s dan bunyi sampai akhir (ffmpeg
			// silencedetect -40 dB hanya menemukan hening 0,096 s di awal).
			'audio-sudah-packing': 1950,
			'audio-paket-double':  1900,
			'audio-sudah-scan':    2150,
			'audio-cancel-order':  2100,
			'audio-tidak-ditemukan': 1500
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

	<script type="text/javascript" src="assets/js/plugins/moment/moment.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/daterangepicker/daterangepicker.min.js"></script>

	<script type='text/javascript' src='assets/js/plugins/noty/jquery.noty.js'></script>
	<script type='text/javascript' src='assets/js/plugins/noty/layouts/center.js'></script>
	<script type='text/javascript' src='assets/js/plugins/noty/layouts/topRight.js'></script>

	<script type='text/javascript' src='assets/js/plugins/noty/themes/default.js'></script>
	<!-- END PLUGINS -->

	<!-- START TEMPLATE -->
	<script type="text/javascript" src="assets/js/plugins.js?v=<?= @filemtime(FCPATH . 'assets/js/plugins.js') ?: '1.0.1' ?>"></script>
	<script type="text/javascript" src="assets/js/actions.js?v=1.0.1"></script>
	<!-- Perekam video packing. Dimuat global (bukan di view) karena panel kameranya
	     harus selamat dari pergantian isi .page-content-wrap tiap kali packer scan.
	     Pasif sampai halaman Scan Resi Packer (Webcam) memanggil PackerVideo.sinkron(). -->
	<script type="text/javascript">window.PACKER_VIDEO_KOMPUTER = <?= json_encode((string) (isset($nama_pk) ? $nama_pk : '')) ?>;</script>
	<script type="text/javascript" src="assets/js/packer_video.js?v=<?= @filemtime(FCPATH . 'assets/js/packer_video.js') ?: '1.0.0' ?>"></script>
	<!-- Foto produk: coba URL asli dulu, ganti ke salinan lokal (/foto-produk/) bila gagal -- lihat helper foto_sku -->
	<script type="text/javascript" src="assets/js/foto_sku.js?v=<?= @filemtime(FCPATH . 'assets/js/foto_sku.js') ?: '1.0.0' ?>"></script>
	<!-- END TEMPLATE -->

	<!-- NOTIFIKASI -->
    <script src="assets/js/plugins/pusher/pusher.min.js" async></script>
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
                    var jml = parseInt(resp.data.count, 10) || 0;
                    $('#notif-count').text(jml > 99 ? '99+' : jml).toggle(jml > 0);
                    $('#notif-count-label').text(jml + ' Baru');
                    $('#notif-ringkas').text(jml > resp.data.list.length ? resp.data.list.length + ' terbaru dari ' + jml + ' belum dibaca' : '');
                    
                    var html = '';
                    if(resp.data.list.length > 0) {
                        resp.data.list.forEach(function(n) {
                            html += `<a href="javascript:void(0);" class="list-group-item mark-read${n.is_read == 0 ? '' : ' notif-dibaca'}" data-id="${n.id}">
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

    <script src="assets/js/plugins/blueimp/blueimp-gallery.min.js"></script>
    

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
            } else if (text.includes('MONITORING')) {
                $(this).css('border-left', '5px solid #8b5cf6');
                $icon.css('color', '#8b5cf6');
            }
        });

        // Standarisasi teks menu: Title Case; singkatan di daftar ini tetap kapital
        var singkatan = ['HO', 'CS', 'QC', 'KPI', 'SKU', 'PO', 'SJ', 'SO', 'MP', 'OT', 'COD', 'WMS', 'ID', 'PDF', 'TV', 'RTS', 'NDD', 'HPP'];
        function titleCase(s) {
            return s.replace(/[A-Za-z]+/g, function(k) {
                var u = k.toUpperCase();
                return singkatan.indexOf(u) !== -1 ? u : u.charAt(0) + k.slice(1).toLowerCase();
            });
        }
        $('.page-sidebar li > a').each(function() {
            $(this).contents().each(function() {
                if (this.nodeType === 3) {
                    this.nodeValue = titleCase(this.nodeValue);
                } else if ($(this).hasClass('xn-text')) {
                    // Awalan "Tim " dibuang dari tampilan; nama di database tidak berubah
                    $(this).text(titleCase($(this).text()).replace(/^\s*Tim\s+/, ''));
                }
            });
        });

        // Aksesibilitas sidebar: landmark navigasi, aria-expanded pada grup, aria-current pada halaman aktif
        var $sidebar = $('.page-sidebar');
        $sidebar.attr({ role: 'navigation', 'aria-label': 'Menu utama' });
        function sinkronAria() {
            $sidebar.find('li.xn-openable').each(function() {
                $(this).children('a').attr('aria-expanded', $(this).hasClass('active') ? 'true' : 'false');
            });
            $sidebar.find('a.link').removeAttr('aria-current');
            $sidebar.find('li.halaman-aktif > a.link').attr('aria-current', 'page');
        }

        // Tandai halaman yang sedang dibuka (navigasi SPA tidak mengubah URL, jadi tidak ada penanda lain)
        // Fase capture: handler menu lain menghentikan propagasi klik, jadi delegasi jQuery tidak sampai
        $sidebar[0].addEventListener('click', function(e) {
            var tautan = $(e.target).closest('a.link');
            if (!tautan.length) { return; }
            $sidebar.find('li.halaman-aktif').removeClass('halaman-aktif');
            tautan.parent().addClass('halaman-aktif');
            sinkronAria();
        }, true);
        sinkronAria();
        if (window.MutationObserver) {
            new MutationObserver(sinkronAria).observe($sidebar[0], { subtree: true, attributes: true, attributeFilter: ['class'] });
        }

        // Kotak cari menu: saring submenu, buka grup yang berisi hasil, kembalikan keadaan semula saat dikosongkan
        var grupTerbukaSebelumCari = null;
        $('#cari-menu').on('input', function() {
            var q = $.trim($(this).val()).toLowerCase();
            var $grup = $sidebar.find('li.xn-openable');
            var $daun = $sidebar.find('a.link').parent();
            if (q === '') {
                $sidebar.find('li').not('.xn-menu-cari').css('display', '');
                $grup.children('ul').css('display', '');
                $grup.removeClass('active');
                if (grupTerbukaSebelumCari) { grupTerbukaSebelumCari.addClass('active'); }
                grupTerbukaSebelumCari = null;
                return;
            }
            if (grupTerbukaSebelumCari === null) { grupTerbukaSebelumCari = $grup.filter('.active'); }
            $daun.each(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
            });
            $grup.each(function() {
                var cocokNama = $(this).children('a').text().toLowerCase().indexOf(q) !== -1;
                var adaHasil = $(this).find('a.link').parent(':visible').length > 0;
                if (cocokNama && !adaHasil) { $(this).find('a.link').parent().show(); adaHasil = true; }
                $(this).toggle(adaHasil).toggleClass('active', adaHasil);
                $(this).children('ul').toggle(adaHasil);
            });
        });
    });
    </script>
    <style>
    .page-sidebar .xn-menu-cari { padding: 6px 12px; }
    .page-sidebar .xn-menu-cari input { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; }
    .x-navigation-minimized .xn-menu-cari { display: none; }
    /* Header sidebar ringkas: menu diutamakan, logo/profil/status tidak boleh memakan ruang (hanya mode normal) */
    /* Card logo BEVERRA dibuang dari sidebar (nama aplikasi sudah ada di judul tab); tombol kontrol tetap ada di DOM */
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-logo { height: 0; min-height: 0; overflow: hidden; margin: 0; padding: 0; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile { display: flex; align-items: center; float: none; padding: 8px 12px; margin-bottom: 4px; border-bottom-right-radius: 12px; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-image { float: none; width: 40px !important; height: 40px !important; margin: 0 10px 0 0 !important; border-width: 2px !important; flex: 0 0 auto; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-image img { border-width: 0; box-shadow: none; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile > a.profile-mini { display: none !important; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data { float: none; width: auto; min-width: 0; text-align: left; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-name { float: none; font-size: 13px; letter-spacing: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-title { float: none; font-size: 10px; letter-spacing: 0; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .page-sidebar .x-navigation > li.xn-title { padding-top: 4px; padding-bottom: 2px; height: auto; line-height: 18px; }
    /* Hierarki: judul grup lebih tebal dan besar dari submenu */
    .page-sidebar .x-navigation > li > a { font-size: 14px; font-weight: 700; padding: 9px 10px; margin: 2px 8px; width: calc(100% - 16px); border-radius: 8px; }
    .page-sidebar .x-navigation li li > a { font-size: 13px; font-weight: 500; padding: 7px 6px 7px 8px; margin: 1px 6px; width: calc(100% - 12px); border-radius: 6px; }
    /* Ikon dan jarak antar menu dikecilkan agar scroll sidebar minimal */
    .page-sidebar .x-navigation > li > a .fa, .page-sidebar .x-navigation > li > a .glyphicon { font-size: 15px; width: 22px; margin-right: 8px; }
    .page-sidebar .x-navigation li li > a .fa, .page-sidebar .x-navigation li li > a .glyphicon { font-size: 12px; width: 18px; margin-right: 6px; }
    .page-sidebar .x-navigation li { line-height: 1.3; }
    /* Indentasi submenu bawaan tema (margin 25 + padding 15) membuat ul meluber keluar sidebar 220px */
    .page-sidebar .x-navigation li > ul { margin: 2px 6px 2px 8px !important; padding: 2px 0 2px 8px !important; width: auto !important; }
    /* Sejajarkan awal ikon dan teks: semua menu utama punya garis kiri 5px (transparan bila tak berwarna), kotak ikon lebar tetap */
    .page-sidebar .x-navigation > li:not(.xn-logo):not(.xn-profile) { border-left: 5px solid transparent; }
    .page-sidebar .x-navigation > li > a .fa, .page-sidebar .x-navigation > li > a .glyphicon { display: inline-block; text-align: center; flex: 0 0 22px; }
    .page-sidebar .x-navigation li li > a .fa, .page-sidebar .x-navigation li li > a .glyphicon { display: inline-block; text-align: center; flex: 0 0 18px; }
    .page-sidebar .x-navigation li li > a { white-space: normal; overflow: visible; text-overflow: clip; }
    /* Panah expand: sejajar tengah baris (top lama 17px untuk baris ~48px) */
    .page-sidebar .x-navigation > li.xn-openable::before { top: 2px; height: 36px; line-height: 36px; font-size: 13px; left: auto; right: 14px; }
    .page-sidebar .x-navigation li li.xn-openable::before { top: 1px; height: 31px; line-height: 31px; font-size: 12px; left: auto; right: 12px; }
    .page-sidebar .xn-menu-cari { padding: 4px 10px; }
    /* Monitor besar: sidebar dilebarkan bersama font, ikon, dan jarak (mode minimize/wide tidak ikut berubah) */
    @media (min-width: 1900px) {
        .page-container:not(.page-container-wide) .page-sidebar { width: 280px; }
        .page-container:not(.page-container-wide) .page-content { margin-left: 280px; }
        .page-sidebar .x-navigation > li > a { font-size: 16px; padding: 0 12px; min-height: 43px; box-sizing: border-box; margin: 2px 10px; width: calc(100% - 20px); }
        .page-sidebar .x-navigation li li > a { font-size: 15px; padding: 0 8px 0 10px; min-height: 35px; box-sizing: border-box; margin: 1px 8px; width: calc(100% - 16px); }
        .page-sidebar .x-navigation > li > a .fa, .page-sidebar .x-navigation > li > a .glyphicon { font-size: 18px; width: 26px; flex-basis: 26px; margin-right: 10px; }
        .page-sidebar .x-navigation li li > a .fa, .page-sidebar .x-navigation li li > a .glyphicon { font-size: 14px; width: 20px; flex-basis: 20px; margin-right: 8px; }
        .page-sidebar .x-navigation > li.xn-openable::before { top: 2px; height: 43px; line-height: 43px; font-size: 15px; right: 16px; }
        .page-sidebar .x-navigation li li.xn-openable::before { top: 1px; height: 35px; line-height: 35px; font-size: 14px; right: 14px; }
        .page-sidebar .xn-menu-cari input { font-size: 15px; padding: 7px 10px; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-image { width: 48px !important; height: 48px !important; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-name { font-size: 16px; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-title { font-size: 12px; }
    }
    @media (min-width: 2400px) {
        .page-container:not(.page-container-wide) .page-sidebar { width: 340px; }
        .page-container:not(.page-container-wide) .page-content { margin-left: 340px; }
        .page-sidebar .x-navigation > li > a { font-size: 19px; padding: 0 14px; min-height: 52px; margin: 3px 12px; width: calc(100% - 24px); }
        .page-sidebar .x-navigation li li > a { font-size: 17px; padding: 0 10px 0 12px; min-height: 42px; margin: 1px 10px; width: calc(100% - 20px); }
        .page-sidebar .x-navigation > li > a .fa, .page-sidebar .x-navigation > li > a .glyphicon { font-size: 21px; width: 30px; flex-basis: 30px; margin-right: 12px; }
        .page-sidebar .x-navigation li li > a .fa, .page-sidebar .x-navigation li li > a .glyphicon { font-size: 16px; width: 24px; flex-basis: 24px; margin-right: 10px; }
        .page-sidebar .x-navigation > li.xn-openable::before { top: 3px; height: 52px; line-height: 52px; font-size: 18px; right: 20px; }
        .page-sidebar .x-navigation li li.xn-openable::before { top: 1px; height: 42px; line-height: 42px; font-size: 16px; right: 18px; }
        .page-sidebar .xn-menu-cari input { font-size: 17px; padding: 9px 12px; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-image { width: 56px !important; height: 56px !important; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-name { font-size: 19px; }
        .page-sidebar .x-navigation:not(.x-navigation-minimized) > li.xn-profile .profile-data-title { font-size: 14px; }
    }
    .page-sidebar .xn-menu-cari input { padding: 4px 8px; font-size: 12px; }
    .page-sidebar .x-navigation li.halaman-aktif > a { background: rgba(37, 99, 235, .12); color: #1d4ed8; box-shadow: inset 3px 0 0 #2563eb; }
    /* Header atas: semua tombol ikon satu ukuran (38px), satu radius (8px), jarak antar tombol 8px, sejajar tengah bar 50px */
    /* Bar fixed selebar viewport: padding kanan menggeser ikon kanan ke tepi konten (220px sidebar, 50px saat diciutkan) + 8px */
    .page-container.page-navigation-top-fixed .page-content > .x-navigation-panel { padding: 0 228px 0 8px; box-sizing: border-box; }
    .page-container.page-navigation-top-fixed.page-navigation-toggled .page-content > .x-navigation-panel { padding-right: 58px; }
    /* Sidebar diciutkan: konten ikut melebar (tema bawaan membiarkan margin kiri 220px sehingga ada celah kosong) */
    .page-container.page-navigation-top-fixed.page-container-wide .page-content { margin-left: 50px; }
    .page-content > .x-navigation-panel > li.xn-icon-button { height: 50px; width: auto; margin: 0 0 0 8px; padding: 0; display: flex; align-items: center; }
    .page-content > .x-navigation-panel > li.xn-icon-button:first-child { margin-left: 0; }
    .page-content > .x-navigation-panel > li.xn-icon-button > a { display: flex; align-items: center; justify-content: center; width: 38px !important; height: 38px !important; margin: 0 !important; padding: 0 !important; border-radius: 8px !important; color: #fff !important; font-size: 17px; line-height: 1; transition: background .15s; }
    .page-content > .x-navigation-panel > li.xn-icon-button > a:hover, .page-content > .x-navigation-panel > li.xn-icon-button.active > a { background: rgba(255, 255, 255, .2) !important; }
    .page-content > .x-navigation-panel > li.xn-icon-button > a .fa { width: 18px; margin: 0; color: #fff !important; font-size: 17px; line-height: 1; text-align: center; }
    #notif-count { left: auto; right: -2px; top: 3px; min-width: 18px; height: 18px; padding: 0 4px; line-height: 14px; font-size: 10px; font-weight: 700; text-align: center; border: 2px solid #007bff; border-radius: 9px; box-sizing: border-box; pointer-events: none; }
    .page-content > ul.breadcrumb { position: absolute; top: 0; left: 62px; right: 110px; height: 50px; margin: 0; padding: 0; display: flex; align-items: center; background: transparent; z-index: 4; pointer-events: none; overflow: hidden; white-space: nowrap; }
    .page-content > ul.breadcrumb li, .page-content > ul.breadcrumb li a { color: #fff; font-size: 15px; font-weight: 600; line-height: 1; }
    .page-content-wrap { padding-top: 10px; }
    /* Panel notifikasi */
    .x-navigation-panel .xn-drop-left { width: 380px; }
    #notif-list-container { height: auto !important; max-height: 320px; overflow-y: auto; }
    #notif-list-container .list-group-item { white-space: normal; }
    #notif-list-container .notif-dibaca { opacity: .6; }
    #notif-ringkas { line-height: 20px; }
    /* Sidebar diciutkan: hanya ikon, rata tengah, ukuran seragam (teks menu atas dibungkus xn-text dan disembunyikan tema) */
    .page-sidebar .x-navigation.x-navigation-minimized > li:not(.xn-logo):not(.xn-profile) { border-left-width: 3px; }
    .page-sidebar .x-navigation.x-navigation-minimized > li > a { display: flex; align-items: center; justify-content: center; width: calc(100% - 8px); margin: 1px 4px; padding: 7px 0; }
    .page-sidebar .x-navigation.x-navigation-minimized > li > a .fa, .page-sidebar .x-navigation.x-navigation-minimized > li > a .glyphicon { flex: 0 0 auto; width: auto; margin: 0; font-size: 15px; }
    /* Laporan (Tim Produksi, Resi Dalam Proses) di monitor besar: skala font dan kontrol naik bertahap, tinggi tabel menyesuaikan */
    :root { --ptr-fs: 12px; --ptr-offset: 270px; }
    @media (min-width: 1600px) { :root { --ptr-fs: 15px; --ptr-offset: 318px; } }
    @media (min-width: 2200px) { :root { --ptr-fs: 19px; --ptr-offset: 372px; } }
    .tabs > .nav-tabs > li > a { font-size: calc(var(--ptr-fs) + 1px); }
    .ptr-toolbar label, .ptr-toolbar .form-control, .ptr-toolbar .btn { font-size: var(--ptr-fs); }
    .ptr-toolbar .form-control, .ptr-toolbar .btn, .ptr-total { height: calc(var(--ptr-fs) * 2.5); }
    .ptr-toolbar .btn { line-height: calc(var(--ptr-fs) * 1.5); padding-top: 0; padding-bottom: 0; }
    .ptr-toolbar .ptr-range { height: calc(var(--ptr-fs) * 2.5); flex-basis: calc(var(--ptr-fs) * 28); width: calc(var(--ptr-fs) * 28); }
    .ptr-total-label { font-size: calc(var(--ptr-fs) - 1px); }
    .ptr-total-angka { font-size: calc(var(--ptr-fs) * 1.75); }
    .ptr-tabel table.dataTable, .ptr-tabel .dataTables_wrapper { font-size: var(--ptr-fs); }
    .ptr-tabel table.dataTable tbody td, .ptr-tabel .dataTables_scrollHead th { font-size: var(--ptr-fs); padding: calc(var(--ptr-fs) * .3) calc(var(--ptr-fs) * .7); }
    .ptr-tabel .dataTables_info, .ptr-tabel .dataTables_filter, .ptr-tabel .dataTables_length { font-size: var(--ptr-fs); }
    .ptr-tabel .dataTables_filter input, .ptr-tabel .dataTables_length select { height: calc(var(--ptr-fs) * 2.3); font-size: var(--ptr-fs); }
    .ptr-tabel .dataTables_info { line-height: calc(var(--ptr-fs) * 2.3); }
    .ptr-tabel .dataTables_paginate .paginate_button { font-size: var(--ptr-fs); }
    .ptr-pegawai .label { font-size: calc(var(--ptr-fs) - 2px); }
    .ptr-tabel .badge { font-size: var(--ptr-fs) !important; }
    .ptr-tabel td .label { font-size: calc(var(--ptr-fs) - 1px) !important; }
    .btn-salin-resi { font-size: var(--ptr-fs); }
    .ptr-tabel .dataTables_scrollBody { max-height: calc(100vh - var(--ptr-offset)) !important; }
    /* Tab Picker / Packer / HO di laporan: hijau, kuning, pink. Tab aktif terisi penuh, tab lain redup agar jelas posisi user */
    .nav-tabs-ptr > li > a { border: 0 !important; border-top: 4px solid transparent !important; border-radius: 0; color: #666 !important; background: #f2f2f2 !important; font-weight: 400; transition: background .15s, color .15s; }
    .nav-tabs-ptr > li:nth-child(1) > a { border-top-color: #4caf50 !important; }
    .nav-tabs-ptr > li:nth-child(2) > a { border-top-color: #fbc02d !important; }
    .nav-tabs-ptr > li:nth-child(3) > a { border-top-color: #e91e63 !important; }
    .nav-tabs-ptr > li:nth-child(1) > a:hover { background: #e3f1e4 !important; }
    .nav-tabs-ptr > li:nth-child(2) > a:hover { background: #fdf3d2 !important; }
    .nav-tabs-ptr > li:nth-child(3) > a:hover { background: #fadbe7 !important; }
    .nav-tabs-ptr > li.active > a, .nav-tabs-ptr > li.active > a:hover { font-weight: 700; color: #fff !important; box-shadow: 0 2px 6px rgba(0, 0, 0, .18); }
    .nav-tabs-ptr > li.active:nth-child(1) > a, .nav-tabs-ptr > li.active:nth-child(1) > a:hover { background: #4caf50 !important; }
    .nav-tabs-ptr > li.active:nth-child(2) > a, .nav-tabs-ptr > li.active:nth-child(2) > a:hover { background: #fbc02d !important; color: #3a2b00 !important; }
    .nav-tabs-ptr > li.active:nth-child(3) > a, .nav-tabs-ptr > li.active:nth-child(3) > a:hover { background: #e91e63 !important; }
    </style>
</body>

</html>