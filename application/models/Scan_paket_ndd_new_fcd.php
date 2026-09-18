<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Query baca untuk menu TIM HO -> "Scan Paket NDD New".
 *
 * Sengaja tidak ada query tulis di sini: simpan scan tetap lewat
 * Scan_logistic_fcd::save_scan() dan simpan lost scan lewat
 * Lost_scan_packer_fcd::save(), supaya aturan bisnisnya satu sumber dengan
 * menu lama.
 */
class Scan_paket_ndd_new_fcd extends CI_Model
{
    /** id_hakakses "client packer" di tblhakakses. */
    const ROLE_PACKER = 4;

    /**
     * Daftar packer untuk dropdown lost scan.
     *
     * Menu Lost Scan lama menampilkan seluruh tblpegawai (139 baris, termasuk
     * QC/INB/AFF dan beberapa nama kosong). Di sini hanya akun packer aktif
     * yang sudah terhubung ke tblpegawai lewat tbluser.id_pegawai -- per
     * 18 Sep 2026 seluruh 15 akun packer aktif memenuhi syarat itu. Yang
     * dipakai sebagai nilai tetap nama_pegawai supaya Laporan Lost Scan lama
     * membaca datanya tanpa perubahan.
     */
    public function daftar_packer()
    {
        return $this->db->query(
            "SELECT u.id_user, p.kode_pegawai, p.nama_pegawai
             FROM tbluser u
             JOIN tblpegawai p ON p.kode_pegawai = u.id_pegawai
             WHERE u.hakakses = ? AND u.isactive = 1 AND TRIM(p.nama_pegawai) <> ''
             ORDER BY p.nama_pegawai ASC",
            [self::ROLE_PACKER]
        )->result_array();
    }

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
