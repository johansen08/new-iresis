<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Monitoring penyimpanan video packing di HDD backup (menu Tim Monitoring).
 *
 * Rekap per tanggal dihitung dari tblvideopacking (ukuran_byte + mp4_ukuran_byte),
 * jadi tidak perlu memindai ratusan ribu berkas di disk. Kapasitas dan sisa
 * ruang dibaca dari drive tempat folder video berada (Video_packing_fcd::root_upload).
 * Model ini hanya membaca; tidak mengubah data apa pun.
 */
class Penyimpanan_video_fcd extends CI_Model
{
    /** Rata-rata pertumbuhan dihitung dari sekian hari penuh terakhir (hari ini tidak ikut: belum selesai). */
    const HARI_PERKIRAAN = 14;

    /** Ambang alert (hari tersisa sebelum penuh). */
    const HARI_BAHAYA = 30;
    const HARI_WASPADA = 90;

    /**
     * @return array|NULL NULL kalau query gagal
     */
    public function snapshot()
    {
        $this->load->model('video_packing_fcd');
        $root = $this->video_packing_fcd->root_upload();

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $q = $this->db->query(
            "SELECT DATE(mulai_at) AS tgl, COUNT(*) AS total_video,
                    SUM(ukuran_byte) AS byte_webm, SUM(mp4_ukuran_byte) AS byte_mp4,
                    SUM(durasi_detik) AS durasi
               FROM tblvideopacking
              WHERE status <> 'DIBATALKAN'
              GROUP BY DATE(mulai_at)
              ORDER BY tgl ASC"
        );
        $this->db->db_debug = $db_debug;

        if ($q === FALSE) {
            log_message('error', 'Penyimpanan_video_fcd::snapshot gagal: ' . json_encode($this->db->error()));
            return NULL;
        }

        $hari = [];
        $kumulatif = 0;
        $total_video = 0;
        foreach ($q->result_array() as $r) {
            $webm = (int) $r['byte_webm'];
            $mp4  = (int) $r['byte_mp4'];
            $kumulatif += $webm + $mp4;
            $total_video += (int) $r['total_video'];
            $hari[] = [
                'tanggal'     => $r['tgl'],
                'total_video' => (int) $r['total_video'],
                'byte_webm'   => $webm,
                'byte_mp4'    => $mp4,
                'byte_total'  => $webm + $mp4,
                'durasi'      => (int) $r['durasi'],
                'kumulatif'   => $kumulatif,
            ];
        }

        // Kapasitas drive (null kalau drive tidak terbaca, misal HDD belum terpasang).
        $kapasitas = @disk_total_space($root);
        $sisa      = @disk_free_space($root);
        $drive_ok  = ($kapasitas !== FALSE && $sisa !== FALSE && $kapasitas > 0);

        $terpakai_video = $kumulatif;
        $terpakai_disk  = $drive_ok ? ($kapasitas - $sisa) : NULL;

        // Perkiraan penuh: rata-rata byte/hari dari HARI_PERKIRAAN hari penuh terakhir.
        $per_hari = $this->rata_harian($hari);
        $perkiraan = ['rata_harian_byte' => $per_hari, 'sisa_hari' => NULL, 'tanggal_penuh' => NULL, 'level' => 'tidak_tahu'];
        if ($drive_ok && $per_hari > 0) {
            $sisa_hari = (int) floor($sisa / $per_hari);
            $perkiraan['sisa_hari'] = $sisa_hari;
            $perkiraan['tanggal_penuh'] = date('Y-m-d', strtotime('+' . $sisa_hari . ' days'));
            $perkiraan['level'] = $sisa_hari <= self::HARI_BAHAYA ? 'bahaya'
                : ($sisa_hari <= self::HARI_WASPADA ? 'waspada' : 'aman');
        }

        return [
            'folder'         => $root,
            'drive_ok'       => $drive_ok,
            'kapasitas'      => $drive_ok ? (int) $kapasitas : NULL,
            'sisa'           => $drive_ok ? (int) $sisa : NULL,
            'terpakai_disk'  => $terpakai_disk !== NULL ? (int) $terpakai_disk : NULL,
            'terpakai_video' => (int) $terpakai_video,
            'total_video'    => $total_video,
            'perkiraan'      => $perkiraan,
            'hari_perkiraan' => self::HARI_PERKIRAAN,
            'hari'           => array_reverse($hari), // terbaru di atas
            'dihitung_pada'  => date('Y-m-d H:i:s'),
        ];
    }

    /** Rata-rata byte per hari dari hari-hari penuh terakhir (hari ini dikecualikan). */
    private function rata_harian(array $hari)
    {
        $hari_ini = date('Y-m-d');
        $awal = date('Y-m-d', strtotime('-' . self::HARI_PERKIRAAN . ' days'));

        $jumlah = 0;
        $pertama = NULL;
        foreach ($hari as $h) {
            if ($h['tanggal'] >= $hari_ini || $h['tanggal'] < $awal) {
                continue;
            }
            $jumlah += $h['byte_total'];
            if ($pertama === NULL) {
                $pertama = $h['tanggal'];
            }
        }
        if ($pertama === NULL) {
            return 0;
        }

        // Pembagi = hari kalender dari data pertama di jendela sampai kemarin (hari tanpa rekaman ikut dihitung).
        $pembagi = (int) round((strtotime(date('Y-m-d', strtotime('-1 day'))) - strtotime($pertama)) / 86400) + 1;
        return (int) floor($jumlah / max(1, $pembagi));
    }
}
