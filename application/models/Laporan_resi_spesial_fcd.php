<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporan Resi Spesial (TIM RESI): per tanggal scan picker, berapa resi "1 SKU 1 QTY"
 * (tblprintresi.tipe_resi = 'satuan') dan berapa di antaranya yang discan sebagai
 * status performa 1_SKU_PICKER (resi spesial, lewat menu Upload Resi Spesial).
 * Kolom "Resi Spesial" = batas atas resi yang MEMUNGKINKAN dijadikan spesial: resi
 * 1 SKU 1 QTY dikelompokkan per SKU pada tanggal scan yang sama; SKU yang hanya punya
 * 1-2 resi tidak ada peluang jadi spesial (batch minimal MIN_BATCH resi), jadi tidak
 * dihitung. SKU yang sudah terlanjur discan 1_SKU_PICKER tetap dihitung (tidak mungkin
 * angka spesial melebihi potensi).
 * Baca-saja; tidak ada tabel baru.
 *
 * Kecepatan: tblresiambilbarang ~600 ribu baris dan tiap hitungan harus join ke
 * tblprintresi (~1 detik per bulan). Angka hari yang sudah lewat hampir tidak berubah,
 * jadi disimpan per hari di application/cache/laporan_resi_spesial/ (satu berkas kecil
 * per database + tanggal) dan kueri hanya menghitung hari yang belum ada di cache.
 * Hari ini selalu dihitung langsung. Menghapus folder itu aman: angkanya dihitung ulang.
 */
class Laporan_resi_spesial_fcd extends CI_Model
{
    const STATUS_SPESIAL = '1_SKU_PICKER';

    /** Jumlah resi 1 SKU yang sama (per tanggal) minimal agar bisa dijadikan batch spesial. */
    const MIN_BATCH = 3;

    /** Umur cache (detik): kemarin masih bisa terkoreksi, hari yang lebih lama praktis tetap. */
    const TTL_KEMARIN = 3600;
    const TTL_LAMA    = 86400;

    private function _dir()
    {
        $dir = APPPATH . 'cache/laporan_resi_spesial/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function _berkas($tanggal)
    {
        return $this->_dir() . preg_replace('/[^A-Za-z0-9_]/', '', $this->db->database) . '_' . $tanggal . '.json';
    }

    /** Baris cache satu hari, atau null bila tidak ada / sudah basi. */
    private function _baca($tanggal)
    {
        $f = $this->_berkas($tanggal);
        if (!is_file($f)) return null;
        $ttl = ($tanggal >= date('Y-m-d', strtotime('-1 day'))) ? self::TTL_KEMARIN : self::TTL_LAMA;
        if (time() - (int) @filemtime($f) > $ttl) return null;
        $j = json_decode((string) @file_get_contents($f), true);
        return (is_array($j) && count($j) >= 3) ? $j : null;   // berkas format lama (2 angka) dihitung ulang
    }

    private function _tulis($tanggal, $satu, $spesial, $potensi)
    {
        @file_put_contents($this->_berkas($tanggal), json_encode(array($satu, $spesial, $potensi)), LOCK_EX);
    }

    /** Hitung langsung dari database: array tanggal => [satu_sku_satu_qty, satu_sku_picker, potensi_spesial]. */
    private function _hitung($start, $end)
    {
        // Satu kueri: kelompok (tanggal, SKU) dihitung dulu, lalu dijumlah per tanggal.
        // Resi satuan hanya punya satu baris detail ber-jumlah > 0, jadi join detail tidak menggandakan baris.
        $rows = $this->db->query(
            "SELECT x.tanggal,
                    SUM(x.n) AS satu,
                    SUM(x.sp) AS spesial,
                    SUM(IF(x.sku IS NOT NULL AND (x.n >= ? OR x.sp > 0), x.n, 0)) AS potensi
               FROM (SELECT DATE(r.tanggal_resiambilbarang) AS tanggal, d.sku,
                            COUNT(*) AS n,
                            SUM(s.status_name = ?) AS sp
                       FROM tblresiambilbarang r
                       JOIN tblprintresi p ON p.id_printresi = r.id_resi AND p.tipe_resi = 'satuan'
                       LEFT JOIN tbldetailprintresi d ON d.id_resi = p.id_printresi AND d.jumlah > 0
                       LEFT JOIN tblmasterstatusperforma s ON s.id_statusperforma = r.status_performa_id
                      WHERE r.tanggal_resiambilbarang >= ? AND r.tanggal_resiambilbarang < DATE_ADD(?, INTERVAL 1 DAY)
                      GROUP BY DATE(r.tanggal_resiambilbarang), d.sku) x
              GROUP BY x.tanggal",
            array(self::MIN_BATCH, self::STATUS_SPESIAL, $start, $end)
        )->result_array();

        $hasil = array();
        foreach ($rows as $r) {
            $hasil[$r['tanggal']] = array((int) $r['satu'], (int) $r['spesial'], (int) $r['potensi']);
        }
        return $hasil;
    }

    /**
     * Seluruh tanggal yang punya scan picker 1 SKU 1 QTY pada rentang ('Y-m-d'),
     * tanggal terbaru dulu.
     */
    public function rekap($start, $end)
    {
        $hari_ini = date('Y-m-d');
        $angka = array();   // tanggal => [satu, spesial]
        $kurang = array();  // hari lampau yang belum ada di cache

        for ($t = strtotime($end); $t >= strtotime($start); $t -= 86400) {
            $tgl = date('Y-m-d', $t);
            if ($tgl >= $hari_ini) continue;   // hari ini (dan masa depan) dihitung langsung
            $c = $this->_baca($tgl);
            if ($c === null) {
                $kurang[] = $tgl;
            } else {
                $angka[$tgl] = $c;
            }
        }

        // Satu kueri untuk seluruh bentang hari yang belum ada di cache.
        if ($kurang) {
            $baru = $this->_hitung(min($kurang), max($kurang));
            foreach ($kurang as $tgl) {
                $v = isset($baru[$tgl]) ? $baru[$tgl] : array(0, 0, 0);
                $this->_tulis($tgl, $v[0], $v[1], $v[2]);
                $angka[$tgl] = $v;
            }
        }

        if ($end >= $hari_ini) {
            $angka += $this->_hitung(max($start, $hari_ini), $end);
        }

        krsort($angka);
        $rows = array();
        foreach ($angka as $tgl => $v) {
            if ($v[0] <= 0) continue;
            $rows[] = array(
                'tanggal'           => $tgl,
                'satu_sku_satu_qty' => $v[0],
                'satu_sku_picker'   => $v[1],
                'resi_spesial'      => $v[2],
                'persen'            => $v[2] > 0 ? round(100 * $v[1] / $v[2], 1) : 0,   // dari Resi Spesial (potensi), bukan 1 SKU 1 QTY
            );
        }
        return $rows;
    }
}
