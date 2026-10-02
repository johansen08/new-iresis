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
    /** id_hakakses "client packer" di tblhakakses. */
    const ROLE_PACKER = 4;

    /**
     * Saran no absen packer: akun packer aktif -- himpunan yang sama dengan
     * Lost_scan_selesai_fcd::cari_packer().
     */
    public function roster_packer()
    {
        $rows = $this->db->query(
            "SELECT name AS nama FROM tbluser WHERE hakakses = ? AND isactive = 1",
            [self::ROLE_PACKER]
        )->result_array();

        return $this->urai_roster($rows);
    }

    /**
     * Saran no absen picker: Master Picker aktif (tblnamaambilbarang, sama
     * dengan dropdown Tambahkan Picker tim picker) -- himpunan yang sama
     * dengan Lost_scan_selesai_fcd::cari_picker().
     */
    public function roster_picker()
    {
        $rows = $this->db->query(
            "SELECT p.nama_pegawai AS nama
             FROM tblnamaambilbarang t
             JOIN tblpegawai p ON p.kode_pegawai = t.id_pegawai
             WHERE t.status_aktif = 'AKTIF' AND p.status_aktif = 'AKTIF'"
        )->result_array();

        return $this->urai_roster($rows);
    }

    /**
     * "DEWI - QC - 0288" -> no_absen 0288, nama DEWI, role QC. Nama yang tidak
     * mengikuti konvensi (mis. "0001 - TRAINER") dilewati. Urut no absen.
     */
    private function urai_roster(array $rows)
    {
        $hasil = [];
        foreach ($rows as $r) {
            $bagian = array_map('trim', explode('-', (string) $r['nama']));
            $absen  = array_pop($bagian);
            if (count($bagian) < 1 || !ctype_digit($absen) || (int) $absen <= 0) {
                continue;
            }
            $nama = array_shift($bagian);
            $hasil[] = [
                'no_absen' => sprintf('%04d', (int) $absen),
                'nama'     => $nama,
                'role'     => $bagian ? implode(' - ', $bagian) : '-',
            ];
        }

        usort($hasil, function ($a, $b) {
            return strcmp($a['no_absen'], $b['no_absen']);
        });

        return $hasil;
    }

    /** Resi ini sudah punya baris tblpacking (Nama/Tanggal Packing di detail resi terisi)? */
    public function sudah_packing($noresi)
    {
        return (bool) $this->db->query(
            "SELECT 1
             FROM tblprintresi p
             JOIN tblpacking k ON k.id_resi = p.id_printresi
             WHERE p.noresi = ?
             LIMIT 1",
            [$noresi]
        )->row();
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
