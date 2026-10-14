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
     * Status sumber data pada panggilan hitung() terakhir:
     *  'tidak' = hanya iresis_prod (rentang masih di jendela prod),
     *  'ya'    = prod dilengkapi iresis_arsip,
     *  'gagal' = arsip dibutuhkan tapi tidak terbaca (hasil hanya dari prod,
     *            bisa kurang dari yang sebenarnya).
     */
    public $sumber_arsip = 'tidak';

    /**
     * Rentang yang dimulai sebelum (hari ini - (retensi_hari - 5)) butuh arsip:
     * purna memindahkan resi lama dari prod ke iresis_arsip (config/arsip.php).
     * Selisih 5 hari menutup resi yang aktivitas terakhirnya mepet cutoff.
     */
    private function perlu_arsip($awal)
    {
        $this->config->load('arsip', TRUE);
        $retensi = (int) $this->config->item('retensi_hari', 'arsip');
        if ($retensi <= 0) {
            return FALSE;
        }
        $batas = date('Y-m-d', strtotime('-' . max($retensi - 5, 0) . ' days'));
        return $awal <= $batas;
    }

    /**
     * Resi pick pada rentang, gabungan prod + arsip. Baris yang ada di prod
     * selalu menang (prod bisa lebih baru dari arsip, mis. Update Picker);
     * baris arsip hanya dipakai kalau PK-nya tidak ada di prod.
     *
     * @return array [potongan SQL "SELECT ..." , binding]
     */
    private function sumber_rab($dari, $sampai, $pakai_arsip)
    {
        $kolom = 'id_resiambilbarang, id_resi, yangambil_pegawai, tanggal_resiambilbarang, status_performa_id, nama_komputer';
        $prod  = $this->db->escape_identifiers($this->db->database);
        $sql   = "SELECT $kolom FROM $prod.tblresiambilbarang WHERE tanggal_resiambilbarang BETWEEN ? AND ?";
        $bind  = [$dari, $sampai];

        if ($pakai_arsip) {
            $arsip = $this->db->escape_identifiers($this->config->item('db_arsip', 'arsip'));
            $sql  .= " UNION ALL SELECT a.id_resiambilbarang, a.id_resi, a.yangambil_pegawai, a.tanggal_resiambilbarang, a.status_performa_id, a.nama_komputer
                       FROM $arsip.tblresiambilbarang a
                       WHERE a.tanggal_resiambilbarang BETWEEN ? AND ?
                         AND NOT EXISTS (SELECT 1 FROM $prod.tblresiambilbarang p WHERE p.id_resiambilbarang = a.id_resiambilbarang)";
            $bind[] = $dari;
            $bind[] = $sampai;
        }
        return [$sql, $bind];
    }

    /** Laporan masalah picker untuk resi di CTE rab, gabungan prod + arsip (PK prod menang). */
    private function sumber_masalah($pakai_arsip)
    {
        $prod = $this->db->escape_identifiers($this->db->database);
        $sql  = "SELECT mp.id_masalahpicker, mp.id_printresi FROM $prod.tblmasalahpicker mp
                 WHERE mp.id_printresi IN (SELECT id_resi FROM rab)";
        if ($pakai_arsip) {
            $arsip = $this->db->escape_identifiers($this->config->item('db_arsip', 'arsip'));
            $sql  .= " UNION ALL SELECT a.id_masalahpicker, a.id_printresi FROM $arsip.tblmasalahpicker a
                       WHERE a.id_printresi IN (SELECT id_resi FROM rab)
                         AND NOT EXISTS (SELECT 1 FROM $prod.tblmasalahpicker p WHERE p.id_masalahpicker = a.id_masalahpicker)";
        }
        return $sql;
    }

    /**
     * Jalankan query pick + kesalahan. Debug DB dimatikan sementara supaya
     * kegagalan membaca arsip (DB/tabel tak ada, hak akses) tidak mencetak
     * halaman error CI yang merusak JSON; hasilnya diulang hanya dengan prod.
     *
     * @return array [pick[], salah[]]
     */
    private function ambil_data($dari, $sampai, $id_normal, $id_1sku, $pakai_arsip)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        list($sql_rab, $bind_rab) = $this->sumber_rab($dari, $sampai, $pakai_arsip);
        $mp = $this->sumber_masalah($pakai_arsip);

        $q_pick = $this->db->query(
            "WITH rab AS ($sql_rab)
             SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(DISTINCT CASE WHEN rab.status_performa_id = ?
                                         AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
                                         AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
                                        THEN rab.id_resi END) AS total_pick,
                    MAX(rab.status_performa_id = ?) AS spesial
             FROM rab
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            array_merge($bind_rab, [$id_normal, self::JAM_AWAL, self::JAM_AKHIR, $id_1sku])
        );

        $q_salah = $q_pick ? $this->db->query(
            "WITH rab AS ($sql_rab)
             SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(DISTINCT mp.id_masalahpicker) AS jml
             FROM rab
             JOIN ($mp) mp ON mp.id_printresi = rab.id_resi
             WHERE rab.status_performa_id = ?
               AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
               AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            array_merge($bind_rab, [$id_normal, self::JAM_AWAL, self::JAM_AKHIR])
        ) : FALSE;

        $this->db->db_debug = $debug;

        if ($q_pick === FALSE || $q_salah === FALSE) {
            if ($pakai_arsip) {
                log_message('error', 'Bonus Picker: gagal membaca arsip, memakai data prod saja. ' . json_encode($this->db->error()));
                $this->sumber_arsip = 'gagal';
                return $this->ambil_data($dari, $sampai, $id_normal, $id_1sku, FALSE);
            }
            show_error('Bonus Picker: query gagal dijalankan.', 500);
        }

        return [$q_pick->result_array(), $q_salah->result_array()];
    }

    /**
     * @param string $awal  Y-m-d
     * @param string $akhir Y-m-d
     * @return array daftar picker: absen, nama, bonus, hari (capai target), det[]
     */
    public function hitung($awal, $akhir)
    {
        $this->sumber_arsip = 'tidak';
        $id_normal = $this->id_status('NORMAL_PICKER');
        $id_1sku   = $this->id_status('1_SKU_PICKER');
        if (!$id_normal) {
            return [];
        }

        $dari   = $awal . ' 00:00:00';
        $sampai = $akhir . ' 23:59:59';

        // Arsip hanya dipakai bila rentang menyentuh data yang sudah dipurna
        // dari prod; kalau koneksi sedang di Mode Arsip, "prod" sudah arsip itu sendiri.
        $this->config->load('arsip', TRUE);
        $nama_arsip  = (string) $this->config->item('db_arsip', 'arsip');
        $pakai_arsip = $nama_arsip !== '' && $nama_arsip !== $this->db->database && $this->perlu_arsip($awal);
        if ($pakai_arsip) {
            $this->sumber_arsip = 'ya';
        }

        list($pick, $salah) = $this->ambil_data($dari, $sampai, $id_normal, $id_1sku, $pakai_arsip);
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
