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
     *  'ya'    = tanggal lama diambil dari iresis_arsip, sisanya dari prod,
     *  'gagal' = arsip dibutuhkan tapi tidak terbaca (hasil hanya dari prod,
     *            bisa kurang dari yang sebenarnya).
     */
    public $sumber_arsip = 'tidak';

    /**
     * Tanggal pemisah: tanggal SEBELUM ini dibaca dari iresis_arsip, mulai
     * tanggal ini dari iresis_prod. Purna memindahkan resi lama dari prod ke
     * arsip (config/arsip.php, retensi_hari); selisih 5 hari menutup resi yang
     * aktivitas terakhirnya mepet cutoff. Arsip adalah superset yang disinkron
     * tiap malam, jadi untuk tanggal lama arsip lengkap; tanggal baru selalu dari
     * prod (arsip bisa tertinggal sampai 1 hari).
     *
     * Dipecah per tanggal, bukan digabung UNION per baris: tiap baris pick
     * jatuh di tepat satu sumber sehingga tidak perlu dedup (NOT EXISTS) dan
     * query jauh lebih cepat (uji 29 Jun-25 Sep: 18,6 dtk -> 2,7 dtk).
     *
     * @return string|null Y-m-d, NULL kalau arsip tidak dipakai
     */
    private function tanggal_pemisah()
    {
        $this->config->load('arsip', TRUE);
        $nama_arsip = (string) $this->config->item('db_arsip', 'arsip');
        $retensi    = (int) $this->config->item('retensi_hari', 'arsip');
        // Mode Arsip: koneksi sudah menunjuk arsip itu sendiri.
        if ($nama_arsip === '' || $nama_arsip === $this->db->database || $retensi <= 0) {
            return NULL;
        }
        return date('Y-m-d', strtotime('-' . max($retensi - 5, 0) . ' days'));
    }

    /**
     * Pick + kesalahan pada satu database untuk rentang tanggal tertentu.
     *
     * Hanya baris berstatus NORMAL_PICKER / 1_SKU_PICKER yang relevan (status
     * lain tidak memengaruhi pick maupun penanda hari spesial). id_resi UNIQUE
     * di tblresiambilbarang, jadi COUNT(*) sama dengan hitung resi distinct.
     * Kesalahan dihitung dari tblmasalahpicker (kecil, tanpa indeks id_printresi)
     * lalu dicari baris pick-nya lewat indeks id_resi.
     *
     * Debug DB dimatikan sementara agar kegagalan (DB/tabel/hak akses) tidak
     * mencetak halaman error CI yang merusak JSON; yang memanggil menangani FALSE.
     *
     * @return array|FALSE [pick[], salah[]]
     */
    private function ambil_segmen($nama_db, $dari, $sampai, $id_normal, $id_1sku)
    {
        $db = $this->db->escape_identifiers($nama_db);
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $q_pick = $this->db->query(
            "SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(CASE WHEN rab.status_performa_id = ?
                                AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
                                AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
                               THEN 1 END) AS total_pick,
                    MAX(rab.status_performa_id = ?) AS spesial
             FROM $db.tblresiambilbarang rab
             WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
               AND rab.status_performa_id IN (?, ?)
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            [$id_normal, self::JAM_AWAL, self::JAM_AKHIR, $id_1sku, $dari, $sampai, $id_normal, $id_1sku]
        );

        $q_salah = $q_pick ? $this->db->query(
            "SELECT rab.yangambil_pegawai AS peg,
                    DATE(rab.tanggal_resiambilbarang) AS tgl,
                    COUNT(DISTINCT mp.id_masalahpicker) AS jml
             FROM $db.tblmasalahpicker mp
             JOIN $db.tblresiambilbarang rab ON rab.id_resi = mp.id_printresi
             WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
               AND rab.status_performa_id = ?
               AND TIME(rab.tanggal_resiambilbarang) BETWEEN ? AND ?
               AND COALESCE(rab.nama_komputer, '') <> 'LOST SCAN PICKER'
             GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)",
            [$dari, $sampai, $id_normal, self::JAM_AWAL, self::JAM_AKHIR]
        ) : FALSE;

        $this->db->db_debug = $debug;

        if ($q_pick === FALSE || $q_salah === FALSE) {
            log_message('error', 'Bonus Picker: query segmen ' . $nama_db . ' gagal. ' . json_encode($this->db->error()));
            return FALSE;
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

        // Segmen [db, dari, sampai]: tanggal lama dari arsip, tanggal baru dari prod.
        $pemisah = $this->tanggal_pemisah();
        $segmen  = [];
        if ($pemisah === NULL || $awal >= $pemisah) {
            $segmen[] = [$this->db->database, $awal, $akhir];
        } else {
            $this->sumber_arsip = 'ya';
            $akhir_arsip = min($akhir, date('Y-m-d', strtotime($pemisah . ' -1 day')));
            $segmen[] = [$this->config->item('db_arsip', 'arsip'), $awal, $akhir_arsip];
            if ($akhir >= $pemisah) {
                $segmen[] = [$this->db->database, $pemisah, $akhir];
            }
        }

        $pick = [];
        $salah = [];
        foreach ($segmen as $sg) {
            $hasil = $this->ambil_segmen($sg[0], $sg[1] . ' 00:00:00', $sg[2] . ' 23:59:59', $id_normal, $id_1sku);
            if ($hasil === FALSE) {
                if ($sg[0] === $this->db->database) {
                    show_error('Bonus Picker: query gagal dijalankan.', 500);
                }
                $this->sumber_arsip = 'gagal'; // arsip tak terbaca: lanjut dengan prod saja
                continue;
            }
            $pick  = array_merge($pick, $hasil[0]);
            $salah = array_merge($salah, $hasil[1]);
        }
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
