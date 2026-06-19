<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Resi_team_fcd extends CI_Model
{
    public function get_selisih_summary($start_date, $end_date)
    {
        // Totals for different stages
        $sql = "
            SELECT 
                (SELECT COUNT(id_printresi) FROM tblprintresi WHERE tanggal_printresi >= ? AND tanggal_printresi <= ? AND (status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL)) as total_resi,
                (SELECT COUNT(rab.id_resiambilbarang) FROM tblresiambilbarang rab JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)) as total_picker,
                (SELECT COUNT(p.id_packing) FROM tblpacking p JOIN tblprintresi pr ON pr.id_printresi = p.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)) as total_packer,
                (SELECT COUNT(rk.id_resikeluar) FROM tblresikeluar rk JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)) as total_ho
        ";
        
        $params = array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
        return $this->db->query($sql, $params)->row_array();
    }

    public function get_top_stats()
    {
        $today = date('Y-m-d');
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
        
        // Total Today + Deadline Today + Pending 7 Days (Printed Today OR (Deadline < Today AND >= 7 days ago AND Not HO))
        $sql_pending = "
            SELECT COUNT(DISTINCT pr.id_printresi) as total
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (
                (pr.tanggal_printresi >= '$today 00:00:00' AND pr.tanggal_printresi <= '$today 23:59:59')
                OR (pr.tanggal_printresi >= ? AND pr.tanggal_printresi < ? AND ho.id_resikeluar IS NULL)
            )
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
        ";
        $total_resi = $this->db->query($sql_pending, [$seven_days_ago, $today . ' 00:00:00'])->row()->total;

        return [
            'total_resi' => $total_resi
        ];
    }

    public function get_special_sku_list($pool_type, $start_date, $end_date)
    {
        $today = date('Y-m-d');
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
        
        $where_clause = "";
        $joins = "";
        
        if ($pool_type == 'resi_to_picker') {
            $joins = "LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_clause = "((pr.tanggal_printresi >= '$start_date' AND pr.tanggal_printresi <= '$end_date') OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00')) AND rab.id_resiambilbarang IS NULL";
        } elseif ($pool_type == 'picker_to_packer') {
            $joins = "JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "((pr.tanggal_printresi >= '$start_date' AND pr.tanggal_printresi <= '$end_date') OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00')) AND p.id_packing IS NULL";
        } elseif ($pool_type == 'tempo_hari_ini') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$today 00:00:00' AND pr.tanggal_bataskirim <= '$today 23:59:59' AND ho.id_resikeluar IS NULL";
        } elseif ($pool_type == 'deadline') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59' AND ho.id_resikeluar IS NULL";
        } elseif ($pool_type == 'deadline_picked') {
            $joins = "JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        } elseif ($pool_type == 'deadline_packed') {
            $joins = "JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        } elseif ($pool_type == 'deadline_ho') {
            $joins = "JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        }

        $sql = "
            SELECT s.id_sku, s.nama_sku, COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM tblprintresi pr
            $joins
            JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE $where_clause 
            AND s.is_special = 1
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            GROUP BY s.id_sku
            ORDER BY resi_count DESC
        ";

        return $this->db->query($sql)->result_array();
    }

    public function get_indicators_by_pool($pool_type, $start_date, $end_date)
    {
        $today = date('Y-m-d');
        $seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
        
        $where_clause = "";
        $joins = "";
        
        if ($pool_type == 'resi_to_picker') {
            $joins = "LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_clause = "((pr.tanggal_printresi >= '$start_date' AND pr.tanggal_printresi <= '$end_date') OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00')) AND rab.id_resiambilbarang IS NULL";
        } elseif ($pool_type == 'picker_to_packer') {
            $joins = "JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "((pr.tanggal_printresi >= '$start_date' AND pr.tanggal_printresi <= '$end_date') OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00')) AND p.id_packing IS NULL";
        } elseif ($pool_type == 'tempo_hari_ini') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$today 00:00:00' AND pr.tanggal_bataskirim <= '$today 23:59:59' AND ho.id_resikeluar IS NULL";
        } elseif ($pool_type == 'deadline') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59' AND ho.id_resikeluar IS NULL";
        } elseif ($pool_type == 'deadline_picked') {
            $joins = "JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        } elseif ($pool_type == 'deadline_packed') {
            $joins = "JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        } elseif ($pool_type == 'deadline_ho') {
            $joins = "JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi";
            $where_clause = "pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$start_date 23:59:59'";
        }

        $subquery = "
            SELECT 
                pr.id_printresi,
                COUNT(DISTINCT dpr.sku) as distinct_skus,
                SUM(dpr.jumlah) as total_qty,
                MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
            FROM tblprintresi pr
            $joins
            JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            LEFT JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE $where_clause
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            GROUP BY pr.id_printresi
        ";

        $sql = "
            SELECT
                COUNT(*) as total_resi,
                SUM(CASE WHEN has_special = 1 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as resi_lebih_10_sku,
                SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as resi_satuan,
                SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as resi_kurang_10_sku
            FROM ($subquery) as indicators
        ";

        return $this->db->query($sql)->row_array();
    }

    public function get_on_progress_bulk_data($start_date, $end_date)
    {
        $subquery = "
            SELECT 
                DATE(pr.tanggal_bataskirim) as deadline_date,
                pr.id_printresi,
                COUNT(DISTINCT dpr.sku) as distinct_skus,
                SUM(dpr.jumlah) as total_qty,
                MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special,
                MAX(CASE WHEN rab.id_resiambilbarang IS NOT NULL THEN 1 ELSE 0 END) as is_picked,
                MAX(CASE WHEN p.id_packing IS NOT NULL THEN 1 ELSE 0 END) as is_packed,
                MAX(CASE WHEN ho.id_resikeluar IS NOT NULL THEN 1 ELSE 0 END) as is_ho
            FROM tblprintresi pr
            JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            LEFT JOIN tblsku s ON s.id_sku = dpr.sku
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE pr.tanggal_bataskirim >= '$start_date 00:00:00' AND pr.tanggal_bataskirim <= '$end_date 23:59:59'
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            GROUP BY pr.id_printresi
        ";

        $sql = "
            SELECT
                deadline_date,
                SUM(is_picked) as picked_total,
                SUM(CASE WHEN is_picked = 1 AND has_special = 1 THEN 1 ELSE 0 END) as picked_special,
                SUM(CASE WHEN is_picked = 1 AND has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as picked_qty_banyak,
                SUM(CASE WHEN is_picked = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as picked_lebih_10_sku,
                SUM(CASE WHEN is_picked = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as picked_satuan,
                SUM(CASE WHEN is_picked = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as picked_kurang_10_sku,
                
                SUM(is_packed) as packed_total,
                SUM(CASE WHEN is_packed = 1 AND has_special = 1 THEN 1 ELSE 0 END) as packed_special,
                SUM(CASE WHEN is_packed = 1 AND has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as packed_qty_banyak,
                SUM(CASE WHEN is_packed = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as packed_lebih_10_sku,
                SUM(CASE WHEN is_packed = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as packed_satuan,
                SUM(CASE WHEN is_packed = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as packed_kurang_10_sku,
                
                SUM(is_ho) as ho_total,
                SUM(CASE WHEN is_ho = 1 AND has_special = 1 THEN 1 ELSE 0 END) as ho_special,
                SUM(CASE WHEN is_ho = 1 AND has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as ho_qty_banyak,
                SUM(CASE WHEN is_ho = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as ho_lebih_10_sku,
                SUM(CASE WHEN is_ho = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as ho_satuan,
                SUM(CASE WHEN is_ho = 1 AND has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as ho_kurang_10_sku
            FROM ($subquery) as resi_level
            GROUP BY deadline_date
        ";

        return $this->db->query($sql)->result_array();
    }
}
