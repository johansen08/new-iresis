<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporan hasil hitung Bonus Packer (menu TIM PACKER -> Bonus Packer).
 *
 * Murni baca-saja: tidak ada tabel baru dan tidak ada yang ditulis. Kembaran
 * Bonus_picker_fcd dengan sumber data packer. Aturan:
 *  - Resi yang dihitung: baris tblpacking yang di-scan pukul 05.00-18.00 dengan
 *    status performa NORMAL_PACKER, QTY_BANYAK, atau PAYUNG (STATUS_DIHITUNG).
 *    Status lain tidak dihitung.
 *  - Hari petugas spesial = hari packer itu mengerjakan resi 1_SKU_PACKER
 *    (satu resi pun, jam berapa pun; keputusan 3 Nov 2026). Hari itu tidak
 *    dihitung sama sekali: tanpa packing, tanpa kesalahan, tanpa bonus, tidak
 *    masuk capai target.
 *  - Kesalahan: jumlah komplain CS (tblcs_complain, belum dihapus) berhasil
 *    investigasi 'QC Salah'. Satuannya per komplain. Dilacak lewat resinya ke
 *    scan packing TERAKHIR resi itu, lalu dicatat pada packer dan TANGGAL scan
 *    tersebut -- apa pun status performa dan jamnya (komplain datang berhari-hari
 *    kemudian dan packer tetap bertanggung jawab atas resi 1 SKU/payung/dll).
 *    1 kesalahan = 50 poin. Net = total packing - poin kesalahan.
 *  - Bonus per hari: net >= 1.300 -> Rp 50.000, net 1.150-1.299 -> Rp 30.000.
 *  - Packer = pegawai bernama "NAMA - QC - NOABSEN" yang aktif; akun login
 *    (tbluser) dipetakan ke pegawai lewat tbluser.id_pegawai.
 */
class Bonus_packer_fcd extends CI_Model
{
    const POIN_PER_KESALAHAN = 50;
    const TARGET_NET         = 1150;
    const TIER2_NET          = 1300;
    const BONUS_TIER1        = 30000;
    const BONUS_TIER2        = 50000;
    const JAM_AWAL           = '05:00:00';
    const JAM_AKHIR          = '18:00:00';
    const HASIL_QC_SALAH     = 'QC Salah';
    const STATUS_DIHITUNG    = ['NORMAL_PACKER', 'QTY_BANYAK', 'PAYUNG'];
    const STATUS_SPESIAL     = '1_SKU_PACKER';

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

    /** "NAMA - QC - 0288" -> 288; null kalau tidak ada angka di ujung. */
    public static function no_absen($nama)
    {
        return preg_match('/(\d+)\s*$/', (string) $nama, $m) ? (int) $m[1] : null;
    }

    /** "Angel - QC - 0391" -> "ANGEL" (hanya nama, huruf kapital). */
    public static function nama_saja($nama)
    {
        $bagian = explode('-', (string) $nama, 2);
        return mb_strtoupper(trim($bagian[0]), 'UTF-8');
    }

    /** Sama dengan Bonus_picker_fcd::$sumber_arsip ('tidak' | 'ya' | 'gagal'). */
    public $sumber_arsip = 'tidak';

    /** Tanggal pemisah prod/arsip -- penjelasan lengkap di Bonus_picker_fcd::tanggal_pemisah(). */
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
     * Total packing + kesalahan pada satu database untuk rentang tanggal.
     * id_resi bisa muncul lebih dari sekali di tblpacking (tanpa UNIQUE), jadi
     * dihitung DISTINCT id_resi. Debug DB dimatikan sementara supaya kegagalan
     * tidak mencetak halaman error CI yang merusak JSON; yang memanggil
     * menangani FALSE.
     *
     * @return array|FALSE [pack[], salah[]]
     */
    private function ambil_segmen($nama_db, $dari, $sampai, array $ids_status, $id_1sku)
    {
        $db = $this->db->escape_identifiers($nama_db);
        $ids_sql = implode(',', array_map('intval', $ids_status));
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $q_pack = $this->db->query(
            "SELECT pk.packer_pegawai AS akun,
                    DATE(pk.tanggal_packing) AS tgl,
                    COUNT(DISTINCT CASE WHEN pk.status_performa_id IN ($ids_sql)
                                         AND TIME(pk.tanggal_packing) BETWEEN ? AND ?
                                        THEN pk.id_resi END) AS total_pack,
                    COUNT(DISTINCT CASE WHEN pk.status_performa_id = ? THEN pk.id_resi END) AS resi_1sku
             FROM $db.tblpacking pk
             WHERE pk.tanggal_packing BETWEEN ? AND ?
               AND pk.status_performa_id IN ($ids_sql, ?)
             GROUP BY pk.packer_pegawai, DATE(pk.tanggal_packing)",
            [self::JAM_AWAL, self::JAM_AKHIR, $id_1sku, $dari, $sampai, $id_1sku]
        );

        $q_salah = $q_pack ? $this->db->query(
            "SELECT pk.packer_pegawai AS akun,
                    DATE(pk.tanggal_packing) AS tgl,
                    c.id_complain,
                    c.no_resi
             FROM $db.tblcs_complain c
             JOIN $db.tblprintresi pr ON pr.noresi = c.no_resi
             JOIN $db.tblpacking pk ON pk.id_resi = pr.id_printresi
                  AND pk.id_packing = (SELECT MAX(pk2.id_packing) FROM $db.tblpacking pk2 WHERE pk2.id_resi = pr.id_printresi)
             WHERE pk.tanggal_packing BETWEEN ? AND ?
               AND c.hasil_investigasi = ?
               AND c.is_deleted = 0
             GROUP BY c.id_complain, pk.packer_pegawai, DATE(pk.tanggal_packing), c.no_resi",
            [$dari, $sampai, self::HASIL_QC_SALAH]
        ) : FALSE;

        $this->db->db_debug = $debug;

        if ($q_pack === FALSE || $q_salah === FALSE) {
            log_message('error', 'Bonus Packer: query segmen ' . $nama_db . ' gagal. ' . json_encode($this->db->error()));
            return FALSE;
        }
        return [$q_pack->result_array(), $q_salah->result_array()];
    }

    /**
     * @param string $awal  Y-m-d
     * @param string $akhir Y-m-d
     * @return array daftar packer: absen, nama, bonus, hari (capai target), det[]
     */
    public function hitung($awal, $akhir)
    {
        $this->sumber_arsip = 'tidak';
        $ids_status = [];
        foreach (self::STATUS_DIHITUNG as $kode) {
            $id = $this->id_status($kode);
            if ($id) {
                $ids_status[] = $id;
            }
        }
        if (!$ids_status) {
            return [];
        }
        $id_1sku = $this->id_status(self::STATUS_SPESIAL);

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

        $pack  = [];
        $salah = [];
        foreach ($segmen as $sg) {
            $hasil = $this->ambil_segmen($sg[0], $sg[1] . ' 00:00:00', $sg[2] . ' 23:59:59', $ids_status, $id_1sku);
            if ($hasil === FALSE) {
                if ($sg[0] === $this->db->database) {
                    show_error('Bonus Packer: query gagal dijalankan.', 500);
                }
                $this->sumber_arsip = 'gagal'; // arsip tak terbaca: lanjut dengan prod saja
                continue;
            }
            $pack  = array_merge($pack, $hasil[0]);
            $salah = array_merge($salah, $hasil[1]);
        }

        // Akun login -> pegawai. tblpacking.packer_pegawai adalah id_user, bukan kode_pegawai.
        $pegawai_akun = [];
        foreach ($this->db->select('id_user, id_pegawai')->get('tbluser')->result_array() as $u) {
            $pegawai_akun[(int) $u['id_user']] = (int) $u['id_pegawai'];
        }
        $nama = [];
        foreach ($this->db->select('kode_pegawai, nama_pegawai')
                     ->get_where('tblpegawai', ['status_aktif' => 'AKTIF'])->result_array() as $p) {
            $nama[(int) $p['kode_pegawai']] = $p['nama_pegawai'];
        }

        // Hanya "NAMA - QC - NOABSEN" yang aktif; pegawai lain (resi, CS, HO, picker)
        // yang kebetulan punya baris packing tidak ikut dilaporkan.
        $peg_dari_akun = function ($akun) use ($pegawai_akun, $nama) {
            $peg = isset($pegawai_akun[(int) $akun]) ? $pegawai_akun[(int) $akun] : 0;
            if (!$peg || !isset($nama[$peg]) || !preg_match('/-\s*QC\s*-/i', $nama[$peg])) {
                return 0;
            }
            return $peg;
        };

        // Gabung per pegawai + hari (satu pegawai bisa punya lebih dari satu akun).
        $map_pack = [];
        $map_spesial = [];
        foreach ($pack as $r) {
            $peg = $peg_dari_akun($r['akun']);
            if (!$peg) {
                continue;
            }
            $k = $peg . '|' . $r['tgl'];
            $map_pack[$k] = ($map_pack[$k] ?? 0) + (int) $r['total_pack'];
            if ((int) $r['resi_1sku'] > 0) {
                $map_spesial[$k] = true;
            }
        }
        $map_salah = [];
        foreach ($salah as $r) {
            $peg = $peg_dari_akun($r['akun']);
            if (!$peg) {
                continue;
            }
            $k = $peg . '|' . $r['tgl'];
            $map_salah[$k][] = $r['no_resi'];
        }

        $hasil = [];
        foreach ($map_pack + array_fill_keys(array_keys($map_salah), 0) as $k => $total) {
            list($peg, $tgl) = explode('|', $k, 2);
            $resi_salah = $map_salah[$k] ?? [];
            $jml_salah  = count($resi_salah);
            if ($total === 0 && $jml_salah === 0 && !isset($map_spesial[$k])) {
                continue;
            }
            if (!isset($hasil[$peg])) {
                $nm = $nama[(int) $peg];
                $hasil[$peg] = [
                    'kode'  => (int) $peg,
                    'absen' => self::no_absen($nm),
                    'nama'  => self::nama_saja($nm),
                    'bonus' => 0,
                    'hari'  => 0,
                    'det'   => [],
                ];
            }

            // Petugas spesial: hari itu tidak dihitung (packing, kesalahan, bonus).
            if (isset($map_spesial[$k])) {
                $hasil[$peg]['det'][] = ['tgl' => $tgl, 'spesial' => true];
                continue;
            }

            $poin  = $jml_salah * self::POIN_PER_KESALAHAN;
            $net   = $total - $poin;
            $bonus = self::bonus_dari_net($net);

            $hasil[$peg]['bonus'] += $bonus;
            if ($bonus > 0) {
                $hasil[$peg]['hari']++;
            }
            $hasil[$peg]['det'][] = [
                'tgl'     => $tgl,
                'spesial' => false,
                'pick'    => $total,
                'salah'   => $jml_salah,
                'resi'    => $resi_salah,
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

        $hasil = array_values($hasil);
        usort($hasil, function ($a, $b) {
            return $b['bonus'] <=> $a['bonus'] ?: strcmp($a['nama'], $b['nama']);
        });

        return $hasil;
    }
}
