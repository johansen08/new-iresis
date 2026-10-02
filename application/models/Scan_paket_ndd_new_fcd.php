<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Query baca untuk menu TIM HO -> "Scan Paket NDD New".
 *
 * Sengaja tidak ada query tulis di sini: simpan scan tetap lewat
 * Scan_logistic_fcd::save_scan() dan simpan lost scan lewat
 * Lost_scan_selesai_fcd, supaya aturan bisnisnya satu sumber dengan menu
 * lama/fitur lain.
 */
class Scan_paket_ndd_new_fcd extends CI_Model
{
    /**
     * Catatan lost scan terakhir untuk satu resi (tipe apa pun), beserta nama
     * pelapornya. Dipakai untuk menampilkan "sudah dicatat oleh X" tanpa
     * perlu mencoba simpan dulu.
     */
    public function cari_lost_scan($noresi)
    {
        $row = $this->db->query(
            "SELECT t.id_lostscanpacker, t.noresi, t.lost_type, t.nama_packer, t.created_at,
                    u.name AS nama_pelapor
             FROM tbllostscanpacker t
             LEFT JOIN tbluser u ON u.id_user = t.created_by
             WHERE t.noresi = ?
             ORDER BY t.created_at DESC, t.id_lostscanpacker DESC
             LIMIT 1",
            [$noresi]
        )->row_array();

        return $row ? $row : null;
    }
}
