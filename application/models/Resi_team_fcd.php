<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Resi_team_fcd extends CI_Model
{
    public function get_selisih_summary($start_date, $end_date)
    {
        // Totals for different stages
        $sql = "
            SELECT 
                (SELECT COUNT(id_printresi) FROM tblprintresi WHERE tanggal_printresi >= ? AND tanggal_printresi <= ? AND (status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL) AND (batal IS NULL OR batal = '')) as total_resi,
                (SELECT COUNT(rab.id_resiambilbarang) FROM tblresiambilbarang rab JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL) AND (pr.batal IS NULL OR pr.batal = '')) as total_picker,
                (SELECT COUNT(p.id_packing) FROM tblpacking p JOIN tblprintresi pr ON pr.id_printresi = p.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL) AND (pr.batal IS NULL OR pr.batal = '')) as total_packer,
                (SELECT COUNT(rk.id_resikeluar) FROM tblresikeluar rk JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi WHERE pr.tanggal_printresi >= ? AND pr.tanggal_printresi <= ? AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL) AND (pr.batal IS NULL OR pr.batal = '')) as total_ho
        ";
        
        $params = array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
        return $this->db->query($sql, $params)->row_array();
    }

    public function get_top_stats($target_date = NULL)
    {
        $date = $target_date ?: date('Y-m-d');
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';
        
        $lookback_date = date('Y-m-d 00:00:00', strtotime($date . ' -30 days'));
        
        // 1. Total Resi Aktif (Semua yang belum HO dalam 30 hari terakhir)
        $sql_total = "
            SELECT COUNT(DISTINCT pr.id_printresi) as total 
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (
                (pr.tanggal_printresi >= '$start' AND pr.tanggal_printresi <= '$end')
                OR (pr.tanggal_printresi >= '$lookback_date' AND pr.tanggal_printresi < '$start')
            )
            AND ho.id_resi IS NULL
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
        ";
        $total_active = $this->db->query($sql_total)->row()->total;

        // 2. Yang Wajib pada tanggal tersebut (Deadline <= Tanggal tersebut + Belum HO)
        $date_15_00 = $date . ' 15:00:00';
        $sql_wajib = "
            SELECT COUNT(DISTINCT pr.id_printresi) as total
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (
                (pr.tanggal_bataskirim >= '$lookback_date' AND pr.tanggal_bataskirim <= '$end')
                OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start' AND pr.tanggal_pesan <= '$date_15_00')
            )
            AND ho.id_resi IS NULL
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
        ";
        $total_wajib = $this->db->query($sql_wajib)->row()->total;

        return [
            'total_resi' => $total_active,
            'wajib_resi' => $total_wajib
        ];
    }

    public function get_indicators_by_pool($pool_type, $start_date, $end_date)
    {
        $lookback_30 = date('Y-m-d 00:00:00', strtotime($start_date . ' -30 days'));
        
        $where_clause = "1=1"; 
        $joins = "";
        
        $wajib_cond = "(
            (pr.tanggal_bataskirim >= '$lookback_30' AND pr.tanggal_bataskirim <= '$end_date')
            OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start_date' AND pr.tanggal_pesan <= '$end_date')
        )";

        $indicator_filter = "";

        if ($pool_type == 'resi_to_picker') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_clause = "$wajib_cond AND rab.id_resiambilbarang IS NULL AND ho.id_resi IS NULL";
            $indicator_filter = ""; 
        } elseif ($pool_type == 'picker_to_packer') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "$wajib_cond AND p.id_packing IS NULL AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'packer_to_ho') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_clause = "$wajib_cond AND ho.id_resi IS NULL";
        }

        $subquery = "
            SELECT 
                pr.id_printresi,
                COUNT(DISTINCT dpr.sku) as distinct_skus,
                COALESCE(SUM(dpr.jumlah), 0) as total_qty,
                MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
            FROM tblprintresi pr
            $joins
            LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            LEFT JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE $where_clause
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
            GROUP BY pr.id_printresi
            $indicator_filter
        ";

        $sql = "
            SELECT
                COUNT(*) as total_resi,
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_1_sku_sd_9,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_2_9_sku_sd_9
            FROM ($subquery) as indicators
        ";

        return $this->db->query($sql)->row_array();
    }

    public function get_special_sku_list($pool_type, $start_date, $end_date)
    {
        $lookback_30 = date('Y-m-d 00:00:00', strtotime($start_date . ' -30 days'));
        
        $where_action = "";
        $joins = "";
        
        $wajib_cond = "(
            (pr.tanggal_bataskirim >= '$lookback_30' AND pr.tanggal_bataskirim <= '$end_date')
            OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start_date' AND pr.tanggal_pesan <= '$end_date')
        )";

        if ($pool_type == 'resi_to_picker') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND rab.id_resiambilbarang IS NULL AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'picker_to_packer') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND p.id_packing IS NULL AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'packer_to_ho') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND ho.id_resi IS NULL";
        }

        $sql = "
            SELECT 
                s.id_sku,
                s.nama_sku,
                COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM tblprintresi pr
            $joins
            JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE $where_action
            AND s.is_special = 1
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
            GROUP BY s.id_sku
            ORDER BY resi_count DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    /**
     * Optimized method to get all indicators and special lists for Selisih Hari Ini report
     * Combines multiple pool calculations into more efficient queries
     */
    public function get_optimized_selisih_bundle($start_date, $end_date)
    {
        $lookback_30 = date('Y-m-d 00:00:00', strtotime($start_date . ' -30 days'));
        
        $date_only = date('Y-m-d', strtotime($start_date));
        $date_15_00 = $date_only . ' 15:00:00';

        // 1. Fetch ALL metadata for relevant resi in one go (combining TOTAL and WAJIB conditions)
        $sql_meta = "
            SELECT 
                pr.id_printresi,
                pr.id_marketplace,
                pr.tanggal_pesan,
                pr.tanggal_bataskirim,
                pr.tanggal_printresi,
                (CASE WHEN rab.id_resiambilbarang IS NOT NULL THEN 1 ELSE 0 END) as has_picker,
                (CASE WHEN p.id_packing IS NOT NULL THEN 1 ELSE 0 END) as has_packer,
                (CASE WHEN ho.id_resi IS NOT NULL THEN 1 ELSE 0 END) as has_ho,
                m.distinct_skus,
                m.total_qty,
                m.has_special
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            LEFT JOIN (
                SELECT 
                    dt.id_resi,
                    COUNT(DISTINCT dt.sku) as distinct_skus,
                    SUM(dt.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tbldetailprintresi dt
                JOIN tblsku s ON s.id_sku = dt.sku
                WHERE dt.id_resi IN (
                    SELECT id_printresi FROM tblprintresi 
                    WHERE ((tanggal_bataskirim >= '$lookback_30' AND tanggal_bataskirim <= '$end_date')
                       OR (id_marketplace = 5 AND tanggal_pesan >= '$start_date' AND tanggal_pesan <= '$date_15_00')
                       OR (tanggal_printresi >= '$lookback_30' AND tanggal_printresi <= '$end_date'))
                )
                GROUP BY dt.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE (
                (pr.tanggal_bataskirim >= '$lookback_30' AND pr.tanggal_bataskirim <= '$end_date')
                OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start_date' AND pr.tanggal_pesan <= '$date_15_00')
                OR (pr.tanggal_printresi >= '$lookback_30' AND pr.tanggal_printresi <= '$end_date')
            )
            AND ho.id_resi IS NULL
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
        ";
        
        $all_resi = $this->db->query($sql_meta)->result_array();
        
        // 2. Aggregate in PHP (much faster than nested SQL counts)
        $template = [
            'total_resi' => 0, 'sku_special' => 0, 'resi_qty_banyak' => 0, 'resi_1_sku_sd_9' => 0, 'resi_2_9_sku_sd_9' => 0, 'special_sku_list' => []
        ];
        
        $groups = [
            'SISA' => [
                'pool1' => $template, 'pool2' => $template, 'pool4' => $template
            ],
            'WAJIB' => [
                'pool1' => $template, 'pool2' => $template, 'pool4' => $template
            ]
        ];

        foreach ($all_resi as $r) {
            $is_wajib = false;
            // Wajib Condition
            if (($r['tanggal_bataskirim'] >= $lookback_30 && $r['tanggal_bataskirim'] <= $end_date)
                || ($r['id_marketplace'] == 5 && $r['tanggal_pesan'] >= $start_date && $r['tanggal_pesan'] <= $date_15_00)) {
                $is_wajib = true;
            }

            // Pool assignment (only for non-HO resis as per WHERE ho.id_resi IS NULL)
            $pool_id = null;
            if ($r['has_picker'] == 0) $pool_id = 'pool1';
            elseif ($r['has_packer'] == 0) $pool_id = 'pool2';
            else $pool_id = 'pool4';

            if ($pool_id) {
                // Indicators calculation
                $sp = $r['has_special'] == 1;
                $ds = $r['distinct_skus'] ?? 0;
                $tq = $r['total_qty'] ?? 0;

                $indicator = null;
                if ($sp && $ds == 1 && $tq <= 10) {
                    $indicator = 'sku_special';
                } elseif ((!$sp || $ds > 1 || $tq > 10) && $tq > 9) {
                    $indicator = 'resi_qty_banyak';
                } elseif ((!$sp || $ds > 1 || $tq > 10) && $tq <= 9 && ($ds <= 1)) {
                    $indicator = 'resi_1_sku_sd_9';
                } elseif ((!$sp || $ds > 1 || $tq > 10) && $tq <= 9 && ($ds >= 2 && $ds <= 9)) {
                    $indicator = 'resi_2_9_sku_sd_9';
                }

                $category = $is_wajib ? 'WAJIB' : 'SISA';

                $groups[$category][$pool_id]['total_resi']++;
                if ($indicator) $groups[$category][$pool_id][$indicator]++;
            }
        }

        // 3. Fetch Special SKU lists in one pass for all Pools and Categories
        $sql_special = "
            SELECT 
                s.id_sku,
                s.nama_sku,
                pr.id_marketplace,
                pr.tanggal_pesan,
                pr.tanggal_bataskirim,
                (CASE 
                    WHEN rab.id_resiambilbarang IS NULL AND ho.id_resi IS NULL THEN 'pool1'
                    WHEN rab.id_resiambilbarang IS NOT NULL AND p.id_packing IS NULL AND ho.id_resi IS NULL THEN 'pool2'
                    WHEN p.id_packing IS NOT NULL AND ho.id_resi IS NULL THEN 'pool4'
                    ELSE NULL
                END) as pool_id,
                COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
            JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE (
                (pr.tanggal_bataskirim >= '$lookback_30' AND pr.tanggal_bataskirim <= '$end_date')
                OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start_date' AND pr.tanggal_pesan <= '$date_15_00')
                OR (pr.tanggal_printresi >= '$lookback_30' AND pr.tanggal_printresi <= '$end_date')
            )
            AND s.is_special = 1
            AND ho.id_resi IS NULL
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
            GROUP BY pool_id, s.id_sku
            ORDER BY resi_count DESC
        ";
        
        $special_results = $this->db->query($sql_special)->result_array();
        foreach ($special_results as $row) {
            $pid = $row['pool_id'];
            if (!$pid) continue;

            $is_wajib = false;
            // Wajib Condition for special SKU row
            if (($row['tanggal_bataskirim'] >= $lookback_30 && $row['tanggal_bataskirim'] <= $end_date)
                || ($row['id_marketplace'] == 5 && $row['tanggal_pesan'] >= $start_date && $row['tanggal_pesan'] <= $date_15_00)) {
                $is_wajib = true;
            }

            $category = $is_wajib ? 'WAJIB' : 'SISA';
            $groups[$category][$pid]['special_sku_list'][] = $row;
        }

        return $groups;
    }

    public function get_indicator_details($pool_type, $indicator_key, $start_date, $end_date)
    {
        $lookback_30 = date('Y-m-d 00:00:00', strtotime($start_date . ' -30 days'));
        
        $joins = "";
        $where_action = "";
        $wajib_cond = "(
            (pr.tanggal_bataskirim >= '$lookback_30' AND pr.tanggal_bataskirim <= '$end_date')
            OR (pr.id_marketplace = 5 AND pr.tanggal_pesan >= '$start_date' AND pr.tanggal_pesan <= '$end_date')
        )";

        if ($pool_type == 'resi_to_picker') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND rab.id_resiambilbarang IS NULL AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'picker_to_packer') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND p.id_packing IS NULL AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'packer_to_ho') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND ho.id_resi IS NULL";
        } elseif ($pool_type == 'picker_total_combined') {
            $joins = "LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                      LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi";
            $where_action = "$wajib_cond AND p.id_packing IS NULL AND ho.id_resi IS NULL";
        }

        $indicator_filter = "";
        if ($indicator_key == 'sku_special') {
            $indicator_filter = "AND has_special = 1 AND distinct_skus = 1 AND total_qty <= 10";
        } elseif ($indicator_key == 'resi_qty_banyak') {
            $indicator_filter = "AND (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9";
        } elseif ($indicator_key == 'resi_1_sku_sd_9') {
            $indicator_filter = "AND (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL)";
        } elseif ($indicator_key == 'resi_2_9_sku_sd_9') {
            $indicator_filter = "AND (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9)";
        }

        $sql = "
            SELECT * FROM (
                SELECT 
                    pr.id_printresi,
                    pr.noresi,
                    pr.tanggal_bataskirim,
                    pr.status_pesanan,
                    COUNT(DISTINCT dpr.sku) as distinct_skus,
                    SUM(dpr.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tblprintresi pr
                $joins
                LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
                LEFT JOIN tblsku s ON s.id_sku = dpr.sku
                WHERE $where_action
                AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
                AND (pr.batal IS NULL OR pr.batal = '')
                GROUP BY pr.id_printresi
            ) as pool_details
            WHERE 1=1 $indicator_filter
            ORDER BY tanggal_bataskirim ASC
        ";
        return $this->db->query($sql)->result_array();
    }
    public function save_combined_scan($noresi, $id_picker, $id_packer, $user_admin)
    {
        $this->db->trans_start();

        try {
            // 1. Get receipt details (search by noresi OR nomorpicklist)
            $this->db->select('id_printresi, status_pesanan, noresi as actual_noresi');
            $this->db->from('tblprintresi');
            $this->db->group_start();
            $this->db->where('noresi', $noresi);
            $this->db->or_where('nomorpicklist', $noresi);
            $this->db->group_end();
            $this->db->limit(1);
            
            $receipt = $this->db->get()->row();

            if (empty($receipt)) {
                $this->db->trans_rollback();
                return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi/picklist tidak ditemukan'];
            }

            // Validation: Check if status_pesanan is CANCELED (Allow COMPLETED for override)
            if (strtoupper($receipt->status_pesanan) == 'CANCELED') {
                $this->db->trans_rollback();
                return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN'];
            }

            $id_resi = $receipt->id_printresi;

            $current_status_res = $this->db
                ->select('id_statusperforma')
                ->get_where('tblstatusperforma', ['id_user' => $user_admin['id_user'], 'tanggal' => date('Y-m-d'), 'isactive' => 1])
                ->row();
            $current_status_id = $current_status_res ? $current_status_res->id_statusperforma : null;

            // Resolve picker status performa
            $normal_picker_status_id = null;
            $normal_picker_status_res = $this->db
                ->select('id_statusperforma')
                ->get_where('tblmasterstatusperforma', ['isactive' => 1, 'kode_status' => 'NORMAL_PICKER'])
                ->row();
            if ($normal_picker_status_res) {
                $normal_picker_status_id = $normal_picker_status_res->id_statusperforma;
            }

            $picker_status_id = null;
            $picker_user = $this->db
                ->select('id_user')
                ->get_where('tbluser', ['id_pegawai' => $id_picker, 'isactive' => 1])
                ->row();
            if ($picker_user) {
                $picker_status_res = $this->db
                    ->select('id_statusperforma')
                    ->get_where('tblstatusperforma', ['id_user' => $picker_user->id_user, 'tanggal' => date('Y-m-d'), 'isactive' => 1])
                    ->row();
                $picker_status_id = $picker_status_res ? $picker_status_res->id_statusperforma : null;
            }
            if (!$picker_status_id) {
                $picker_status_id = $normal_picker_status_id ?: $current_status_id;
            }

            // Resolve packer status performa
            $normal_packer_status_id = null;
            $normal_packer_status_res = $this->db
                ->select('id_statusperforma')
                ->get_where('tblmasterstatusperforma', ['isactive' => 1, 'kode_status' => 'NORMAL_PACKER'])
                ->row();
            if ($normal_packer_status_res) {
                $normal_packer_status_id = $normal_packer_status_res->id_statusperforma;
            }

            $packer_status_res = $this->db
                ->select('id_statusperforma')
                ->get_where('tblstatusperforma', ['id_user' => $id_packer, 'tanggal' => date('Y-m-d'), 'isactive' => 1])
                ->row();
            $packer_status_id = $packer_status_res ? $packer_status_res->id_statusperforma : null;
            if (!$packer_status_id) {
                $packer_status_id = $normal_packer_status_id ?: $current_status_id;
            }

            // 2. Picker Scan (tblresiambilbarang)
            $picking_exist = $this->db->get_where('tblresiambilbarang', ['id_resi' => $id_resi])->row();
            
            $picking_data = [
                'id_resi' => $id_resi,
                'tanggal_resiambilbarang' => date('Y-m-d H:i:s'),
                'admin_pegawai' => $user_admin['id_user'],
                'yangambil_pegawai' => $id_picker,
                'nama_komputer' => $user_admin['nama_komputer'] ?? 'COMBINED_SCAN',
                'pending' => '',
                'is_preorder' => 0,
                'status_performa_id' => $picker_status_id
            ];

            if ($picking_exist) {
                $this->db->where('id_resiambilbarang', $picking_exist->id_resiambilbarang);
                $this->db->update('tblresiambilbarang', $picking_data);
            } else {
                $this->db->insert('tblresiambilbarang', $picking_data);
            }

            // 3. Packer Scan (tblpacking)
            $packing_exist = $this->db->get_where('tblpacking', ['id_resi' => $id_resi])->row();
            
            $packing_data = [
                'id_resi' => $id_resi,
                'tanggal_packing' => date('Y-m-d H:i:s'),
                'packer_pegawai' => $id_packer,
                'keterangan' => ($user_admin['nama_komputer'] ?? 'COMBINED_SCAN') . " (Combined)",
                'status_performa_id' => $packer_status_id
            ];

            if ($packing_exist) {
                $this->db->where('id_packing', $packing_exist->id_packing);
                $this->db->update('tblpacking', $packing_data);
            } else {
                $this->db->insert('tblpacking', $packing_data);
            }

            // 4. Log KPI (Corrected logic: Use current session/admin for KPI if individual mapping fails)
            // Picker KPI
            $this->log_kpi_combined($id_picker, 'PICKER', TRUE); // Pass TRUE for Picker (Pegawai ID)
            // Packer KPI
            $this->log_kpi_combined($id_packer, 'PACKER', FALSE);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                return ['error' => TRUE, 'code' => 500, 'message' => 'Database transaction failed'];
            }

            return ['affected_rows' => 1, 'code' => 201, 'message' => 'Sukses menambahkan data', 'noresi' => $receipt->actual_noresi];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['error' => TRUE, 'code' => 500, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Helper to log KPI for combined scan
     */
    private function log_kpi_combined($target_id, $tipe_transaksi, $is_picker = FALSE)
    {
        $tanggal = date('Y-m-d');
        
        // If it's a picker, we need to find their user_id first (if they have one)
        $user_id = $target_id;
        if ($is_picker) {
            $user_record = $this->db->get_where('tbluser', ['id_pegawai' => $target_id, 'isactive' => 1])->row();
            if (!$user_record) return false; // Cannot log KPI for non-user pickers
            $user_id = $user_record->id_user;
        }

        // Cari status performa aktif untuk user ini hari ini
        $status_log = $this->db
            ->select('id_statusperforma')
            ->get_where('tblstatusperforma', [
                'id_user' => $user_id,
                'tanggal' => $tanggal,
                'isactive' => 1
            ])
            ->row();
        
        if (!$status_log) return false;

        // Cek existing log di tblkpi
        $existing_log = $this->db
            ->get_where('tblkpi', [
                'id_user' => $user_id,
                'id_statusperforma' => $status_log->id_statusperforma,
                'tanggal' => $tanggal,
                'tipe_transaksi' => $tipe_transaksi
            ])
            ->row();
        
        if ($existing_log) {
            $this->db->where('id_log', $existing_log->id_log);
            $this->db->set('jumlah_resi', 'jumlah_resi + 1', FALSE);
            $this->db->set('updated', date('Y-m-d H:i:s'));
            $this->db->update('tblkpi');
        } else {
            $this->db->insert('tblkpi', [
                'id_user' => $user_id,
                'id_statusperforma' => $status_log->id_statusperforma,
                'tanggal' => $tanggal,
                'tipe_transaksi' => $tipe_transaksi,
                'jumlah_resi' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
        return true;
    }

    public function save_scan_preorder($noresi, $id_picker, $is_preorder, $user_admin)
    {
        // 1. Get resi details
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row();

        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 404, 'message' => 'Nomor resi tidak ditemukan'];
        }

        $id_resi = $receipt->id_printresi;

        // 2. Check if there's already a picking record
        $existing = $this->db->get_where('tblresiambilbarang', ['id_resi' => $id_resi])->row();

        $data = [
            'id_resi' => $id_resi,
            'is_preorder' => $is_preorder,
            'tanggal_resiambilbarang' => date('Y-m-d H:i:s'),
            'admin_pegawai' => $user_admin['id_user']
        ];

        // Only update picker if provided
        if (!empty($id_picker)) {
            $data['yangambil_pegawai'] = $id_picker;
        }

        if ($existing) {
            $this->db->where('id_resiambilbarang', $existing->id_resiambilbarang);
            $this->db->update('tblresiambilbarang', $data);
        } else {
            $this->db->insert('tblresiambilbarang', $data);
        }

        return ['affected_rows' => $this->db->affected_rows(), 'id_resi' => $id_resi];
    }

    public function get_batas_kirim_summary()
    {
        $days = [];
        $today = date('Y-m-d');
        $day_labels = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];

        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("$today +$i days"));
            $label = ($i == 0) ? 'HARI INI' : (($i == 1) ? 'BESOK' : $day_labels[date('w', strtotime($date))]);
            
            $start = $date . ' 00:00:00';
            $end = $date . ' 23:59:59';
            
            $subquery = "
                SELECT 
                    pr.id_printresi,
                    COUNT(DISTINCT dpr.sku) as distinct_skus,
                    SUM(dpr.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tblprintresi pr
                LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
                LEFT JOIN tblsku s ON s.id_sku = dpr.sku
                WHERE pr.tanggal_bataskirim >= '$start' AND pr.tanggal_bataskirim <= '$end'
                AND ho.id_resi IS NULL
                AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
                AND (pr.batal IS NULL OR pr.batal = '')
                GROUP BY pr.id_printresi
            ";

            $sql = "
                SELECT
                    COUNT(*) as total_resi,
                    SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND distinct_skus >= 10 THEN 1 ELSE 0 END) as resi_lebih_10_sku,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_satuan,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_kurang_10_sku
                FROM ($subquery) as indicators
            ";
            
            $res = $this->db->query($sql)->row_array();
            $res['date'] = $date;
            $res['date_label'] = $label;
            
            $sql_special = "
                SELECT s.id_sku, COUNT(DISTINCT pr.id_printresi) as resi_count
                FROM tblprintresi pr
                LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
                JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
                JOIN tblsku s ON s.id_sku = dpr.sku
                WHERE pr.tanggal_bataskirim >= '$start' AND pr.tanggal_bataskirim <= '$end'
                AND s.is_special = 1
                AND ho.id_resi IS NULL
                AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
                AND (pr.batal IS NULL OR pr.batal = '')
                GROUP BY s.id_sku
                ORDER BY resi_count DESC
            ";
            $res['special_sku_list'] = $this->db->query($sql_special)->result_array();
            
            $days[] = $res;
        }
        
        return $days;
    }

    public function get_on_progress_today_summary()
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $get_summary = function($table, $date_col) use ($today_start, $today_end) {
            $subquery = "
                SELECT 
                    p.id_resi,
                    COUNT(DISTINCT dpr.sku) as distinct_skus,
                    SUM(dpr.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM $table p
                JOIN tblprintresi pr ON pr.id_printresi = p.id_resi
                LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = p.id_resi
                LEFT JOIN tblsku s ON s.id_sku = dpr.sku
                WHERE p.$date_col >= '$today_start' AND p.$date_col <= '$today_end'
                AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
                AND (pr.batal IS NULL OR pr.batal = '')
                GROUP BY p.id_resi
            ";
            
            $sql = "
                SELECT
                    COUNT(*) as total_resi,
                    SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND distinct_skus >= 10 THEN 1 ELSE 0 END) as resi_lebih_10_sku,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_satuan,
                    SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_kurang_10_sku
                FROM ($subquery) as indicators
            ";
            return $this->db->query($sql)->row_array();
        };

        return [
            'p1' => $get_summary('tblresiambilbarang', 'tanggal_resiambilbarang'),
            'p2' => $get_summary('tblpacking', 'tanggal_packing'),
            'p3' => $get_summary('tblresikeluar', 'tanggal_resikeluar')
        ];
    }

    public function get_on_progress_deadline_breakdown($start_date, $end_date)
    {
        $sql_dates = "
            SELECT DISTINCT DATE(pr.tanggal_bataskirim) as deadline
            FROM tblprintresi pr
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (rab.tanggal_resiambilbarang BETWEEN ? AND ?
               OR p.tanggal_packing BETWEEN ? AND ?
               OR ho.tanggal_resikeluar BETWEEN ? AND ?)
            AND pr.tanggal_bataskirim IS NOT NULL
            ORDER BY deadline ASC
        ";
        $deadlines = $this->db->query($sql_dates, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date])->result_array();
        
        $days = [];
        $day_labels = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        $today = date('Y-m-d');

        foreach ($deadlines as $d) {
            $date = $d['deadline'];
            $label = ($date == $today) ? 'HARI INI' : $day_labels[date('w', strtotime($date))];
            
            $day_data = [
                'date' => $date,
                'date_label' => $label,
                'p1' => $this->get_stage_stats_by_deadline('tblresiambilbarang', 'tanggal_resiambilbarang', $date, $start_date, $end_date),
                'p2' => $this->get_stage_stats_by_deadline('tblpacking', 'tanggal_packing', $date, $start_date, $end_date),
                'p3' => $this->get_stage_stats_by_deadline('tblresikeluar', 'tanggal_resikeluar', $date, $start_date, $end_date)
            ];
            $days[] = $day_data;
        }
        return $days;
    }

    private function get_stage_stats_by_deadline($table, $work_date_col, $deadline_date, $period_start, $period_end)
    {
        $subquery = "
            SELECT 
                p.id_resi,
                COUNT(DISTINCT dpr.sku) as distinct_skus,
                SUM(dpr.jumlah) as total_qty,
                MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
            FROM $table p
            JOIN tblprintresi pr ON pr.id_printresi = p.id_resi
            LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = p.id_resi
            LEFT JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE DATE(pr.tanggal_bataskirim) = '$deadline_date'
            AND p.$work_date_col BETWEEN '$period_start' AND '$period_end'
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
            GROUP BY p.id_resi
        ";

        $sql = "
            SELECT
                COUNT(*) as total_resi,
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND distinct_skus >= 10 THEN 1 ELSE 0 END) as resi_lebih_10_sku,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_satuan,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_kurang_10_sku
            FROM ($subquery) as indicators
        ";
        
        $res = $this->db->query($sql)->row_array();
        
        $sql_special = "
            SELECT s.id_sku, COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM $table p
            JOIN tblprintresi pr ON pr.id_printresi = p.id_resi
            JOIN tbldetailprintresi dpr ON dpr.id_resi = p.id_resi
            JOIN tblsku s ON s.id_sku = dpr.sku
            WHERE DATE(pr.tanggal_bataskirim) = '$deadline_date'
            AND p.$work_date_col BETWEEN '$period_start' AND '$period_end'
            AND s.is_special = 1
            AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
            AND (pr.batal IS NULL OR pr.batal = '')
            GROUP BY s.id_sku
            ORDER BY resi_count DESC
        ";
        $res['special_sku_list'] = $this->db->query($sql_special)->result_array();
        
        return $res;
    }

    public function get_resi_db_data($start_date, $end_date)
    {
        $this->db->select('
            pr.noresi,
            pr.tanggal_printresi as tanggal,
            rk.tanggal_resikeluar as tanggal_ho,
            k.nama_kurir as kurir,
            pr.status_wms,
            pr.status_pesanan,
            pr.batal
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tblresikeluar rk', 'rk.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblkurir k', 'k.id_kurir = pr.id_kurir', 'left');
        $this->db->where('pr.tanggal_printresi >=', $start_date . ' 00:00:00');
        $this->db->where('pr.tanggal_printresi <=', $end_date . ' 23:59:59');
        
        $query = $this->db->get();
        return [
            'total' => $query->num_rows(),
            'data' => $query->result_array()
        ];
    }

    // ─────────────────────────────────────────────
    //  DAFTAR SKU
    // ─────────────────────────────────────────────

    public function get_daftar_sku_data($data)
    {
        $this->db->select('id_sku, nama_sku, is_special, jenis_packing, no_rak, total_stok');
        $this->db->from('tblsku');

        $this->_apply_daftar_sku_filters($data);

        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir']);
        } else {
            $this->db->order_by('id_sku', 'ASC');
        }

        $this->db->limit($data['length'], $data['start']);
        return $this->db->get();
    }

    public function count_all_daftar_sku($data)
    {
        $this->db->from('tblsku');
        $this->_apply_daftar_sku_filters($data);
        return $this->db->count_all_results();
    }

    private function _apply_daftar_sku_filters($data)
    {
        // Search by keyword
        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('id_sku', $data['search']);
            $this->db->or_like('nama_sku', $data['search']);
            $this->db->or_like('jenis_packing', $data['search']);
            $this->db->group_end();
        }

        // Filter by status special
        if (isset($data['filter_special']) && $data['filter_special'] !== '') {
            $this->db->where('is_special', (int)$data['filter_special']);
        }

        // Filter by jenis packing
        if (!empty($data['filter_packing'])) {
            if ($data['filter_packing'] === 'kosong') {
                $this->db->group_start();
                $this->db->where('jenis_packing IS NULL');
                $this->db->or_where('jenis_packing', '');
                $this->db->group_end();
            } else {
                $this->db->where('jenis_packing', $data['filter_packing']);
            }
        }
    }

    public function update_sku_inline($id_sku, $update_data)
    {
        $allowed_fields = ['is_special', 'jenis_packing', 'no_rak'];
        $clean = [];
        foreach ($allowed_fields as $f) {
            if (array_key_exists($f, $update_data)) {
                $clean[$f] = $update_data[$f];
            }
        }
        if (empty($clean)) return false;
        $this->db->where('id_sku', $id_sku);
        return $this->db->update('tblsku', $clean);
    }

    public function bulk_update_skus($id_skus, $update_data)
    {
        if (empty($id_skus)) return false;
        $allowed_fields = ['is_special', 'jenis_packing', 'no_rak'];
        $clean = [];
        foreach ($allowed_fields as $f) {
            if (array_key_exists($f, $update_data)) {
                $clean[$f] = $update_data[$f];
            }
        }
        if (empty($clean)) return false;
        $this->db->where_in('id_sku', $id_skus);
        return $this->db->update('tblsku', $clean);
    }
}
