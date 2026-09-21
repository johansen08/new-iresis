<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper arsip — dipanggil di pintu masuk alur yang mungkin menyentuh resi lama
 * (scan retur, buka retur, komplain CS, cek/detail resi, cancel order, video packing).
 * Di-autoload (config/autoload.php). Alur lengkap: docs/ARSIP_DATA.md.
 */

if (!function_exists('pastikan_resi_live')) {
	/**
	 * Pastikan keluarga resi $noresi ada di prod: kalau sudah lewat retensi dan
	 * hanya tersisa di iresis_arsip, tarik kembali ke prod (INSERT IGNORE, satu
	 * transaksi) supaya alur pemanggil berjalan seperti biasa tanpa tahu resi itu
	 * pernah diarsipkan. Selama tahap purna belum aktif semua resi masih di prod,
	 * jadi fungsi ini berhenti di satu SELECT berindeks (noresi UNIQUE).
	 *
	 * Tidak pernah melempar dan tidak mengubah alur pemanggil — hanya
	 * mengembalikan status: 'live' | 'ditarik' | 'tidak_ada' | 'lewati' | 'gagal'.
	 * Rincian kegagalan ada di iresis_arsip._arsip_log (tahap 'tarik_balik').
	 */
	function pastikan_resi_live($noresi)
	{
		$noresi = trim((string) $noresi);
		if ($noresi === '') {
			return 'lewati';
		}

		try {
			$CI =& get_instance();
			if (!isset($CI->Arsip_fcd)) {
				$CI->load->model('Arsip_fcd');
			}
			return $CI->Arsip_fcd->tarik_balik($noresi);
		} catch (Throwable $e) {
			log_message('error', "pastikan_resi_live($noresi): " . $e->getMessage());
			return 'gagal';
		}
	}
}
