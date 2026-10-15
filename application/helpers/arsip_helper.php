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

if (!function_exists('resolusi_noresi_retur')) {
	/**
	 * Resi yang discan tim retur kadang BUKAN resi pengiriman di Iresis, melainkan
	 * resi balik dari kurir (mis. JZ… pada Lazada/LEX) yang hanya tercatat di
	 * tblreturjubelio. Kalau $noresi tidak ada di tblprintresi, cari lewat
	 * tblreturjubelio.no_resi -> no_pesanan -> tbldetailprintresi.no_pesanan dan
	 * kembalikan resi pengiriman aslinya. Hanya bila pesanannya memetakan ke
	 * tepat SATU resi; selain itu $noresi dikembalikan apa adanya.
	 * Sekaligus memanggil pastikan_resi_live() untuk resi yang dikembalikan.
	 */
	function resolusi_noresi_retur($noresi)
	{
		$noresi = trim((string) $noresi);
		if ($noresi === '') {
			return $noresi;
		}

		try {
			$CI =& get_instance();
			pastikan_resi_live($noresi);
			if ($CI->db->where('noresi', $noresi)->count_all_results('tblprintresi') > 0) {
				return $noresi;
			}

			$jub = $CI->db->select('no_pesanan')
				->where('no_resi', $noresi)
				->where("no_pesanan <> ''", null, false)
				->limit(1)->get('tblreturjubelio')->row();
			if (empty($jub)) {
				return $noresi;
			}

			$cari = $CI->db->distinct()->select('p.noresi')
				->from('tbldetailprintresi d')
				->join('tblprintresi p', 'p.id_printresi = d.id_resi')
				->where('d.no_pesanan', $jub->no_pesanan)
				->limit(2)->get()->result();
			if (count($cari) === 1) {
				pastikan_resi_live($cari[0]->noresi);
				return $cari[0]->noresi;
			}
		} catch (Throwable $e) {
			log_message('error', "resolusi_noresi_retur($noresi): " . $e->getMessage());
		}

		return $noresi;
	}
}
