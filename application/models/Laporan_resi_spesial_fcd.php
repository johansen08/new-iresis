<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporan Resi Spesial (TIM RESI): per tanggal scan picker, berapa resi "1 SKU 1 QTY"
 * (tblprintresi.tipe_resi = 'satuan') dan berapa di antaranya yang discan sebagai
 * status performa 1_SKU_PICKER (resi spesial, lewat menu Upload Resi Spesial).
 * Baca-saja; tidak ada tabel baru.
 */
class Laporan_resi_spesial_fcd extends CI_Model
{
    const STATUS_SPESIAL = '1_SKU_PICKER';

    /** Jumlah tanggal yang punya scan picker 1 SKU 1 QTY dalam rentang ('Y-m-d'). */
    public function hitung_tanggal($start, $end)
    {
        $row = $this->db->query(
            "SELECT COUNT(DISTINCT DATE(r.tanggal_resiambilbarang)) AS n
               FROM tblresiambilbarang r
               JOIN tblprintresi p ON p.id_printresi = r.id_resi AND p.tipe_resi = 'satuan'
              WHERE r.tanggal_resiambilbarang >= ? AND r.tanggal_resiambilbarang < DATE_ADD(?, INTERVAL 1 DAY)",
            array($start, $end)
        )->row();
        return $row ? (int) $row->n : 0;
    }

    /** Satu halaman baris per tanggal, tanggal terbaru dulu. $limit 0 = semua baris (untuk ekspor). */
    public function daftar($start, $end, $offset, $limit)
    {
        $batas = (int) $limit > 0 ? 'LIMIT ?, ?' : '';
        $param = array(self::STATUS_SPESIAL, $start, $end);
        if ($batas !== '') {
            $param[] = (int) $offset;
            $param[] = (int) $limit;
        }

        $rows = $this->db->query(
            "SELECT DATE(r.tanggal_resiambilbarang) AS tanggal,
                    COUNT(*) AS satu_sku_satu_qty,
                    SUM(s.status_name = ?) AS satu_sku_picker
               FROM tblresiambilbarang r
               JOIN tblprintresi p ON p.id_printresi = r.id_resi AND p.tipe_resi = 'satuan'
               LEFT JOIN tblmasterstatusperforma s ON s.id_statusperforma = r.status_performa_id
              WHERE r.tanggal_resiambilbarang >= ? AND r.tanggal_resiambilbarang < DATE_ADD(?, INTERVAL 1 DAY)
              GROUP BY DATE(r.tanggal_resiambilbarang)
              ORDER BY tanggal DESC
              $batas",
            $param
        )->result_array();

        foreach ($rows as &$r) {
            $r['satu_sku_satu_qty'] = (int) $r['satu_sku_satu_qty'];
            $r['satu_sku_picker']   = (int) $r['satu_sku_picker'];
            $r['persen']            = $r['satu_sku_satu_qty'] > 0
                ? round(100 * $r['satu_sku_picker'] / $r['satu_sku_satu_qty'], 1) : 0;
        }
        unset($r);
        return $rows;
    }
}
