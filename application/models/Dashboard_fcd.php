<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_fcd extends CI_Model
{
    /**
     * Get performance statistics for today
     * OPTIMIZED: Uses UNION ALL to execute all counts in one query with better performance
     *
     * RECOMMENDED INDEXES:
     * - CREATE INDEX idx_printresi_tanggal ON tblprintresi(tanggal_printresi);
     * - CREATE INDEX idx_resiambilbarang_tanggal ON tblresiambilbarang(tanggal_resiambilbarang);
     * - CREATE INDEX idx_packing_tanggal ON tblpacking(tanggal_packing);
     * - CREATE INDEX idx_resikeluar_tanggal ON tblresikeluar(tanggal_resikeluar);
     */
    public function get_performance_today()
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        // Use UNION ALL for better performance - executes all counts in parallel
        $sql = "
            SELECT 
                SUM(CASE WHEN type = 'scan' THEN cnt ELSE 0 END) as total_scan,
                SUM(CASE WHEN type = 'picker' THEN cnt ELSE 0 END) as total_picker,
                SUM(CASE WHEN type = 'packer' THEN cnt ELSE 0 END) as total_packer,
                SUM(CASE WHEN type = 'ho' THEN cnt ELSE 0 END) as total_ho
            FROM (
                SELECT 'scan' as type, COUNT(*) as cnt 
                FROM tblprintresi 
                WHERE tanggal_printresi BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 'picker' as type, COUNT(*) as cnt 
                FROM tblresiambilbarang 
                WHERE tanggal_resiambilbarang BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 'packer' as type, COUNT(*) as cnt 
                FROM tblpacking 
                WHERE tanggal_packing BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 'ho' as type, COUNT(*) as cnt 
                FROM tblresikeluar 
                WHERE tanggal_resikeluar BETWEEN ? AND ?
            ) as counts
        ";

        $result = $this->db->query($sql, [
            $today_start, $today_end,
            $today_start, $today_end,
            $today_start, $today_end,
            $today_start, $today_end
        ])->row_array();

        return $result;
    }

    /**
     * Get queue status (backlog)
     * OPTIMIZED: Uses LEFT JOIN instead of NOT EXISTS for better performance with indexes
     *
     * RECOMMENDED INDEXES:
     * - CREATE INDEX idx_printresi_status ON tblprintresi(status_pesanan);
     * - CREATE INDEX idx_resiambilbarang_id_resi ON tblresiambilbarang(id_resi);
     * - CREATE INDEX idx_packing_id_resi ON tblpacking(id_resi);
     * - CREATE INDEX idx_resikeluar_id_resi ON tblresikeluar(id_resi);
     */
    public function get_queue_status()
    {
        // Using UNION ALL with optimized LEFT JOINs
        $sql = "
            SELECT 
                SUM(CASE WHEN type = 'picker' THEN cnt ELSE 0 END) as sisa_picker,
                SUM(CASE WHEN type = 'packer' THEN cnt ELSE 0 END) as sisa_packer,
                SUM(CASE WHEN type = 'ho' THEN cnt ELSE 0 END) as sisa_ho
            FROM (
                SELECT 'picker' as type, COUNT(*) as cnt
                FROM tblprintresi pr
                LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                WHERE (pr.status_pesanan NOT IN ('CANCELED', 'REQUEST_CANCEL') OR pr.status_pesanan IS NULL)
                AND rab.id_resi IS NULL
                
                UNION ALL
                
                SELECT 'packer' as type, COUNT(*) as cnt
                FROM tblresiambilbarang rab
                INNER JOIN tblprintresi pr ON rab.id_resi = pr.id_printresi
                LEFT JOIN tblpacking p ON p.id_resi = rab.id_resi
                WHERE (pr.status_pesanan NOT IN ('CANCELED', 'REQUEST_CANCEL') OR pr.status_pesanan IS NULL)
                AND p.id_resi IS NULL
                
                UNION ALL
                
                SELECT 'ho' as type, COUNT(*) as cnt
                FROM tblpacking p
                INNER JOIN tblprintresi pr ON p.id_resi = pr.id_printresi
                LEFT JOIN tblresikeluar ho ON ho.id_resi = p.id_resi
                WHERE (pr.status_pesanan NOT IN ('CANCELED', 'REQUEST_CANCEL') OR pr.status_pesanan IS NULL)
                AND ho.id_resi IS NULL
            ) as counts
        ";

        return $this->db->query($sql)->row_array();
    }

    /**
     * Get shipping summary for next 5 days
     * OPTIMIZED: Uses LEFT JOIN and proper date handling
     *
     * RECOMMENDED INDEXES:
     * - CREATE INDEX idx_printresi_bataskirim ON tblprintresi(tanggal_bataskirim);
     */
    public function get_shipping_summary()
    {
        $today_start = date('Y-m-d 00:00:00');
        $five_days_later = date('Y-m-d 23:59:59', strtotime('+5 days'));

        $sql = "
            SELECT DATE(pr.tanggal_bataskirim) as deadline, COUNT(*) as total
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE pr.tanggal_bataskirim BETWEEN ? AND ?
            AND ho.id_resi IS NULL
            GROUP BY DATE(pr.tanggal_bataskirim)
            ORDER BY pr.tanggal_bataskirim ASC
        ";

        return $this->db->query($sql, [$today_start, $five_days_later])->result_array();
    }

    /**
     * Get count of shipments that must go out today
     * OPTIMIZED: Uses LEFT JOIN for better performance
     */
    public function get_must_ship_today()
    {
        $today_end = date('Y-m-d 23:59:59');

        $sql = "
            SELECT COUNT(*) as total
            FROM tblprintresi pr
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE pr.tanggal_bataskirim <= ?
            AND ho.id_resi IS NULL
        ";

        return $this->db->query($sql, [$today_end])->row()->total;
    }

    /**
     * Get courrier statistics for today
     * OPTIMIZED: Uses parameter binding and optimized JOIN
     *
     * RECOMMENDED INDEXES:
     * - CREATE INDEX idx_printresi_kurir_tanggal ON tblprintresi(id_kurir, tanggal_printresi);
     */
    public function get_courrier_stats_today()
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $sql = "
            SELECT k.nama_kurir, COUNT(p.id_printresi) as total
            FROM tblkurir k
            INNER JOIN tblprintresi p ON p.id_kurir = k.id_kurir
            WHERE p.tanggal_printresi BETWEEN ? AND ?
            GROUP BY k.id_kurir, k.nama_kurir
            ORDER BY total DESC
        ";

        return $this->db->query($sql, [$today_start, $today_end])->result_array();
    }
    /**
     * Get simplified dashboard data (Today + Overdue)
     * OPTIMIZED: Single pass query using LEFT JOIN to avoid slow subqueries
     */
    public function get_simple_dashboard_data()
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        $today_15_00 = date('Y-m-d 15:00:00');
        $overdue_limit = date('Y-m-d 00:00:00', strtotime('-30 days'));

        // Define the 'Wajib Hari Ini' condition:
        // 1. All marketplaces with deadline today
        // 2. Tiktok (id 5) created today before 15:00
        $wajib_today_cond = "((pr.tanggal_bataskirim BETWEEN ? AND ?) OR (pr.id_marketplace = 5 AND pr.tanggal_pesan BETWEEN ? AND ?))";

        // Single optimized query for all dashboard stats
        $sql = "
            SELECT 
                -- Section 1: MONITORING BATAS KIRIM HARI INI
                COUNT(CASE WHEN $wajib_today_cond THEN 1 END) as total_resi,
                COUNT(CASE WHEN $wajib_today_cond AND rab.id_resi IS NOT NULL THEN 1 END) as sudah_picker,
                COUNT(CASE WHEN $wajib_today_cond AND pack.id_resi IS NOT NULL THEN 1 END) as sudah_packer,
                COUNT(CASE WHEN $wajib_today_cond AND ho.id_resi IS NOT NULL THEN 1 END) as sudah_ho,
                COUNT(CASE WHEN $wajib_today_cond AND ho.id_resi IS NULL THEN 1 END) as belum_selesai,
                COUNT(CASE WHEN pr.tanggal_bataskirim >= ? AND pr.tanggal_bataskirim < ? AND ho.id_resi IS NULL THEN 1 END) as total_overdue,

                -- Section 2: SISA PENGERJAAN (BATAS KIRIM HARI INI)
                COUNT(CASE WHEN $wajib_today_cond AND rab.id_resi IS NULL THEN 1 END) as pending_picker_today,
                COUNT(CASE WHEN $wajib_today_cond AND rab.id_resi IS NOT NULL AND pack.id_resi IS NULL THEN 1 END) as pending_packer_today,
                COUNT(CASE WHEN $wajib_today_cond AND pack.id_resi IS NOT NULL AND ho.id_resi IS NULL THEN 1 END) as pending_ho_today
                
            FROM tblprintresi pr
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking pack ON pack.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (
                $wajib_today_cond 
                OR (pr.tanggal_bataskirim >= ? AND pr.tanggal_bataskirim < ?)
            )
            AND (pr.status_pesanan NOT IN ('CANCELED', 'REQUEST_CANCEL') OR pr.status_pesanan IS NULL)
        ";
        
        $params = [];
        // total_resi (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // sudah_picker (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // sudah_packer (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // sudah_ho (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // belum_selesai (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // total_overdue (2 params)
        $params[] = $overdue_limit; $params[] = $today_start;
        // pending_picker_today (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // pending_packer_today (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // pending_ho_today (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        
        // WHERE clause
        // $wajib_today_cond (4 params)
        $params[] = $today_start; $params[] = $today_end; $params[] = $today_start; $params[] = $today_15_00;
        // overdue (2 params)
        $params[] = $overdue_limit; $params[] = $today_start;

        return $this->db->query($sql, $params)->row_array();
    }
    /**
     * Get detailed list of receipts for dashboard category
     */
    public function get_dashboard_details_data($type)
    {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        $today_15_00 = date('Y-m-d 15:00:00');
        $overdue_limit = date('Y-m-d 00:00:00', strtotime('-30 days'));

        $wajib_today_cond = "((pr.tanggal_bataskirim BETWEEN ? AND ?) OR (pr.id_marketplace = 5 AND pr.tanggal_pesan BETWEEN ? AND ?))";

        $sql_base = "
            SELECT 
                pr.id_printresi,
                pr.noresi,
                pr.status_pesanan,
                pr.tanggal_bataskirim,
                k.nama_kurir,
                CASE 
                    WHEN ho.id_resi IS NOT NULL THEN 'HO'
                    WHEN pack.id_resi IS NOT NULL THEN 'PACKED'
                    WHEN rab.id_resi IS NOT NULL THEN 'PICKED'
                    ELSE 'STAGING'
                END as current_status,
                CASE 
                    WHEN ho.id_resi IS NOT NULL THEN ho.tanggal_resikeluar
                    WHEN pack.id_resi IS NOT NULL THEN pack.tanggal_packing
                    WHEN rab.id_resi IS NOT NULL THEN rab.tanggal_resiambilbarang
                    ELSE pr.tanggal_printresi
                END as last_action_date
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblpacking pack ON pack.id_resi = pr.id_printresi
            LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
            WHERE (pr.status_pesanan NOT IN ('CANCELED', 'REQUEST_CANCEL') OR pr.status_pesanan IS NULL)
        ";

        $where = "";
        $params = [];

        switch ($type) {
            case 'belum_selesai':
                $where = " AND $wajib_today_cond AND ho.id_resi IS NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'total_overdue':
                $where = " AND pr.tanggal_bataskirim >= ? AND pr.tanggal_bataskirim < ? AND ho.id_resi IS NULL";
                $params = [$overdue_limit, $today_start];
                break;
            case 'total_resi':
                $where = " AND $wajib_today_cond ";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'sudah_picker':
                $where = " AND $wajib_today_cond AND rab.id_resi IS NOT NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'sudah_packer':
                $where = " AND $wajib_today_cond AND pack.id_resi IS NOT NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'sudah_ho':
                $where = " AND $wajib_today_cond AND ho.id_resi IS NOT NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'pending_picker':
                $where = " AND $wajib_today_cond AND rab.id_resi IS NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'pending_packer':
                $where = " AND $wajib_today_cond AND rab.id_resi IS NOT NULL AND pack.id_resi IS NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
            case 'pending_ho':
                $where = " AND $wajib_today_cond AND pack.id_resi IS NOT NULL AND ho.id_resi IS NULL";
                $params = [$today_start, $today_end, $today_start, $today_15_00];
                break;
        }

        $sql = $sql_base . $where . " ORDER BY pr.tanggal_bataskirim ASC LIMIT 1000";
        return $this->db->query($sql, $params)->result_array();
    }
}
