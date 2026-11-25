<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard Retur New: ringkasan progres retur (6 tahap) dan peringkat per Marketplace / Kurir / Toko.
 *
 * Tidak punya queri sendiri: memakai Daftar_retur_fcd::get_list() sehingga definisi tahap, tab Semua, retur final Jubelio,
 * lost scan, dan batas Hangus SAMA PERSIS dengan menu Daftar Retur Penjualan. Hasilnya dikelompokkan jadi sel
 * (marketplace, kurir, nama kurir mentah, toko, tahap) + jumlah, lalu disaring silang di browser.
 * Hanya membaca data.
 */
class Dashboard_retur_fcd extends CI_Model
{
    /** Urutan tahap di dashboard (indeks dipakai di sel dan di JS). Tab Belum Terima + Hangus digabung jadi "Belum Terima". */
    const TAHAP = array('belum', 'terima', 'buka', 'serah', 'verif', 'selesai');

    /** Tab Daftar_retur_fcd -> indeks TAHAP (diisi di constructor: konstanta Daftar_retur_fcd baru ada setelah modelnya dimuat). */
    private $peta_tab = array();

    /**
     * Aturan penyatuan nama kurir: nama mentah dijadikan huruf kecil tanpa spasi, lalu dicari apakah MENGANDUNG kata kunci
     * (awal, tengah, atau akhir). Aturan atas dicek lebih dulu. Tidak cocok = 'Lainnya'.
     */
    private static $ATURAN_KURIR = array(
        'SPX'    => array('spx', 'shopeexpress', 'shopeexpres', 'shopeeexpress'),
        'J&T'    => array('j&t', 'jnt'),
        'GoTo'   => array('goto', 'gosend', 'gojek'),
        'JNE'    => array('jne'),
        'Ninja'  => array('ninja'),
        'LEX ID' => array('lexid', 'lazadaexpress', 'lex'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('daftar_retur_fcd');
        $this->peta_tab = array(
            Daftar_retur_fcd::POSISI_BELUM_DITERIMA => 0,
            Daftar_retur_fcd::POSISI_BELUM_HANGUS   => 0,
            Daftar_retur_fcd::POSISI_BELUM_DIBUKA   => 1,
            Daftar_retur_fcd::POSISI_SUDAH_DIBUKA   => 2,
            Daftar_retur_fcd::POSISI_SUDAH_SERAH    => 3,
            Daftar_retur_fcd::POSISI_SUDAH_VERIF    => 4,
            Daftar_retur_fcd::TAB_SELESAI           => 5,
        );
    }

    /** Nama kurir mentah -> nama kurir baku (lihat $ATURAN_KURIR). */
    public function normalisasi_kurir($mentah)
    {
        $s = strtolower(preg_replace('/\s+/', '', (string) $mentah));
        if ($s === '') return 'Lainnya';
        foreach (self::$ATURAN_KURIR as $baku => $kata) {
            foreach ($kata as $k) {
                if (strpos($s, $k) !== false) return $baku;
            }
        }
        return 'Lainnya';
    }

    /**
     * @param string $start 'Y-m-d H:i:s'  @param string $end 'Y-m-d H:i:s'  (disaring dengan tanggal tahap, seperti Daftar Retur)
     * @param bool $segarkan lewati cache Daftar_retur_fcd
     * @return array kamus (mp, kurir, raw, toko), sel [[mp,kurir,raw,toko,tahap,jumlah]...], total, waktu
     */
    public function ringkas($start, $end, $segarkan = false)
    {
        list($rows, , , , $waktu) = $this->daftar_retur_fcd->get_list(array(
            'start' => $start, 'end' => $end, 'status' => '', 'search' => '',
            'segarkan' => $segarkan,
            'jenis' => array(Daftar_retur_fcd::JENIS_BIASA, Daftar_retur_fcd::JENIS_KOMPLAIN),
            'status_retur' => Daftar_retur_fcd::STATUS_RETUR,   // semua status Jubelio = isi tab Semua
            'order' => 'umur', 'dir' => 'desc', 'offset' => 0, 'limit' => PHP_INT_MAX,
        ));

        $kamus = array('mp' => array(), 'kurir' => array(), 'raw' => array(), 'toko' => array());
        $indeks = array('mp' => array(), 'kurir' => array(), 'raw' => array(), 'toko' => array());
        $id = function ($jenis, $nilai) use (&$kamus, &$indeks) {
            if (!isset($indeks[$jenis][$nilai])) {
                $indeks[$jenis][$nilai] = count($kamus[$jenis]);
                $kamus[$jenis][] = $nilai;
            }
            return $indeks[$jenis][$nilai];
        };

        $sel = array();
        $total = 0;
        foreach ($rows as $r) {
            if (!isset($r->tab) || !isset($this->peta_tab[$r->tab])) continue;
            $tahap = $this->peta_tab[$r->tab];
            $mp = trim((string) $r->marketplace);
            $toko = trim((string) $r->nama_toko);
            $raw = trim((string) $r->kurir);
            $a = $id('mp', $mp !== '' ? $mp : '-');
            $b = $id('kurir', $this->normalisasi_kurir($raw));
            $c = $id('raw', $raw !== '' ? $raw : '-');
            $d = $id('toko', $toko !== '' ? $toko : '-');
            $k = $a . '|' . $b . '|' . $c . '|' . $d . '|' . $tahap;
            if (!isset($sel[$k])) $sel[$k] = array($a, $b, $c, $d, $tahap, 0);
            $sel[$k][5]++;
            $total++;
        }

        return array(
            'kamus' => $kamus,
            'sel'   => array_values($sel),
            'total' => $total,
            'waktu' => date('H:i:s', $waktu),
        );
    }
}
