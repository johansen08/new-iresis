<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporan hasil hitung Bonus Picker (menu TIM PICKER -> Bonus Picker).
 *
 * Murni baca-saja: tidak ada tabel baru dan tidak ada yang ditulis. Aturan:
 *  - Resi yang dihitung: baris tblresiambilbarang yang di-assign pukul
 *    05.00-17.00 dengan status performa NORMAL_PICKER. 1_SKU_PICKER tidak
 *    dihitung. Baris LOST SCAN PICKER dibuang (dibuat tim picker belakangan,
 *    bukan assign harian, dan tanpa KPI).
 *  - Kesalahan: jumlah baris tblmasalahpicker pada resi yang dihitung
 *    (definisi yang sama dengan Error_recap_fcd::get_picker_recap). 1 kesalahan
 *    = 20 poin. Net = total pick - poin kesalahan.
 *  - Hari petugas resi spesial = hari picker itu punya baris 1_SKU_PICKER
 *    (hasil Upload Resi Spesial). Resi hari itu tidak dihitung sama sekali:
 *    tanpa pick, tanpa kesalahan, tanpa bonus, tidak masuk capai target.
 *  - Bonus per hari: net >= 1.300 -> Rp 50.000, net 1.150-1.299 -> Rp 30.000.
 */
class Bonus_picker_fcd extends CI_Model
{
    const POIN_PER_KESALAHAN = 20;
    const TARGET_NET         = 1150;
    const TIER2_NET          = 1300;
    const BONUS_TIER1        = 30000;
    const BONUS_TIER2        = 50000;
    const JAM_AWAL           = '05:00:00';
    const JAM_AKHIR          = '17:00:00';
    const MAKS_HARI          = 62;

    public static function bonus_dari_net($net)
    {
        if ($net >= self::TIER2_NET) {
            return self::BONUS_TIER2;
        }
        return ($net >= self::TARGET_NET) ? self::BONUS_TIER1 : 0;
    }

    private function id_status($kode)
    {
        $row = $this->db->select('id_statusperforma')
            ->get_where('tblmasterstatusperforma', ['kode_status' => $kode, 'isactive' => 1])
            ->row();
        return $row ? (int) $row->id_statusperforma : 0;
    }

    /** "NAMA - JABATAN - 0288" -> 288; null kalau tidak ada angka di ujung. */
    public static function no_absen($nama)
    {
        return preg_match('/(\d+)\s*$/', (string) $nama, $m) ? (int) $m[1] : null;
    }

    /** "Davis - PICKER - 0393" -> "DAVIS" (hanya nama, huruf kapital). */
    public static function nama_saja($nama)
    {
        $bagian = explode('-', (string) $nama, 2);
        return mb_strtoupper(trim($bagian[0]), 'UTF-8');
    }

    /**
     * @param string $awal  Y-m-d
     * @param string $akhir Y-m-d
     * @return array daftar picker: absen, nama, bonus, hari (capai target), det[]
     */
    public function hitung($awal, $akhir)
    {
        $id_normal = $this->id_status('NORMAL_PICKER');
        $id_1sku   = $this->id_status('1_SKU_PICKER');
        if (!$id_normal) {
            return [];
        }

        $dari   = $awal . ' 00:00:00';
        $sampai = $akhir . ' 23:59:59';

        // Pick + penanda hari spesial per picker per tanggal.
        $pick = $this->db->query(
            "SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(DISTINCT CASE WHEN rab.status_performa_id = ?
                                         AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
                                         AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
                                        THEN rab.id_resi END) AS total_pick,
                    MAX(rab.status_performa_id = ?) AS spesial
             FROM tblresiambilbarang rab
             WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            [$id_normal, self::JAM_AWAL, self::JAM_AKHIR, $id_1sku, $dari, $sampai]
        )->result_array();

        // Kesalahan pada resi yang sama-sama dihitung sebagai pick.
        $salah = $this->db->query(
            "SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(DISTINCT mp.id_masalahpicker) AS jml
             FROM tblresiambilbarang rab
             JOIN tblmasalahpicker mp ON mp.id_printresi = rab.id_resi
             WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
               AND rab.status_performa_id = ?
               AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
               AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            [$dari, $sampai, $id_normal, self::JAM_AWAL, self::JAM_AKHIR]
        )->result_array();

        $map_salah = [];
        foreach ($salah as $r) {
            $map_salah[$r['peg'] . '|' . $r['tgl']] = (int) $r['jml'];
        }

        $nama = [];
        foreach ($this->db->select('kode_pegawai, nama_pegawai')->get('tblpegawai')->result_array() as $p) {
            $nama[$p['kode_pegawai']] = $p['nama_pegawai'];
        }

        // Hanya picker aktif di Master Picker (pegawai-nya juga aktif, sama
        // dengan Lost_scan_selesai_fcd::cari_picker). Pegawai lain yang punya
        // baris NORMAL_PICKER (mis. tim resi/HVN) tidak ikut dilaporkan.
        $aktif = [];
        foreach ($this->db->query(
            "SELECT t.id_pegawai FROM tblnamaambilbarang t
             JOIN tblpegawai p ON p.kode_pegawai = t.id_pegawai
             WHERE t.status_aktif = 'AKTIF' AND p.status_aktif = 'AKTIF'"
        )->result_array() as $a) {
            $aktif[(int) $a['id_pegawai']] = true;
        }

        $hasil = [];
        foreach ($pick as $r) {
            $peg = $r['peg'];
            if (!isset($aktif[(int) $peg])) {
                continue;
            }
            // Master Picker aktif juga memuat jabatan lain (HVN, RS, RESI) yang
            // bukan picker bonus; hanya "NAMA - PICKER - NOABSEN" yang dilaporkan.
            if (isset($nama[$peg]) && !preg_match('/-\s*PICKER\s*-/i', $nama[$peg])) {
                continue;
            }
            if (!isset($hasil[$peg])) {
                $nm = isset($nama[$peg]) ? $nama[$peg] : ('Pegawai #' . $peg);
                $hasil[$peg] = [
                    'kode'  => (int) $peg,
                    'absen' => self::no_absen($nm),
                    'nama'  => self::nama_saja($nm),
                    'bonus' => 0,
                    'hari'  => 0,
                    'det'   => [],
                ];
            }

            if ((int) $r['spesial'] === 1) {
                $hasil[$peg]['det'][] = ['tgl' => $r['tgl'], 'spesial' => true];
                continue;
            }

            $jml_salah = isset($map_salah[$peg . '|' . $r['tgl']]) ? $map_salah[$peg . '|' . $r['tgl']] : 0;
            $poin  = $jml_salah * self::POIN_PER_KESALAHAN;
            $total = (int) $r['total_pick'];
            if ($total === 0 && $jml_salah === 0) {
                continue; // hari tanpa pick yang dihitung (mis. semua di luar 05.00-17.00)
            }
            $net   = $total - $poin;
            $bonus = self::bonus_dari_net($net);

            $hasil[$peg]['bonus'] += $bonus;
            if ($bonus > 0) {
                $hasil[$peg]['hari']++;
            }
            $hasil[$peg]['det'][] = [
                'tgl'     => $r['tgl'],
                'spesial' => false,
                'pick'    => $total,
                'salah'   => $jml_salah,
                'poin'    => $poin,
                'net'     => $net,
                'bonus'   => $bonus,
            ];
        }

        foreach ($hasil as &$h) {
            usort($h['det'], function ($a, $b) {
                return strcmp($a['tgl'], $b['tgl']);
            });
        }
        unset($h);

        $hasil = array_values(array_filter($hasil, function ($h) {
            return !empty($h['det']);
        }));
        usort($hasil, function ($a, $b) {
            return $b['bonus'] <=> $a['bonus'] ?: strcmp($a['nama'], $b['nama']);
        });

        return $hasil;
    }
}
