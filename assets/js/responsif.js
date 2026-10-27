/*
 * Perilaku laci sidebar untuk layar <= 1024px (tablet & handphone).
 * Gayanya ada di assets/css/responsif.css. Di desktop file ini tidak melakukan apa pun.
 *
 * Tombol hamburger = a.x-navigation-minimize (di desktop fungsinya menciutkan
 * sidebar lewat actions.js). Di layar kecil klik itu dicegat DI FASE CAPTURE,
 * sebelum handler jQuery milik actions.js, lalu dialihkan membuka/menutup laci.
 */
(function () {
	'use strict';

	var BATAS = 1024;
	var body = document.body;

	function layarKecil() {
		return (window.innerWidth || document.documentElement.clientWidth) <= BATAS;
	}

	var backdrop = document.createElement('div');
	backdrop.className = 'sidebar-backdrop';
	body.appendChild(backdrop);

	// Animasi laci baru menyala setelah halaman selesai dimuat
	window.addEventListener('load', function () {
		setTimeout(function () { body.classList.add('sidebar-anim'); }, 300);
	});

	function buka() {
		body.classList.add('sidebar-mobile-open');
	}

	function tutup() {
		body.classList.remove('sidebar-mobile-open');
	}

	function ganti() {
		if (body.classList.contains('sidebar-mobile-open')) {
			tutup();
		} else {
			buka();
		}
	}

	// Hamburger: dicegat sebelum actions.js
	document.addEventListener('click', function (e) {
		if (!layarKecil()) { return; }
		var tombol = e.target.closest ? e.target.closest('.x-navigation-minimize') : null;
		if (!tombol) { return; }
		e.preventDefault();
		e.stopPropagation();
		ganti();
	}, true);

	// Klik latar gelap menutup laci
	backdrop.addEventListener('click', tutup);

	// Memilih halaman (tautan daun) menutup laci; judul grup yang hanya membuka submenu tidak
	var sidebar = document.querySelector('.page-sidebar');
	if (sidebar) {
		sidebar.addEventListener('click', function (e) {
			if (!layarKecil()) { return; }
			var tautan = e.target.closest ? e.target.closest('a.link') : null;
			if (tautan) { setTimeout(tutup, 120); }
		}, true);
	}

	// Esc menutup laci
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { tutup(); }
	});

	// Kembali ke lebar desktop: pastikan laci tidak tersangkut terbuka
	window.addEventListener('resize', function () {
		if (!layarKecil()) { tutup(); }
	});
})();
