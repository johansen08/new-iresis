<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_reports extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->load->model('kpi_fcd');

        // Cek akses - hanya admin dan webmaster
        $this->check_kpi_access();
    }

    private function check_kpi_access()
    {
        $user = $this->session->userdata('user');

        if (!$user || !isset($user['id_user'])) {
            $this->session->set_flashdata('message', 'Access denied. Please login first.');
            redirect('welcome/restricted');
        }

        // Cek hakakses - hanya admin (hakakses = 1) dan webmaster/manager (hakakses = 2)
        if (!isset($user['hakakses']) || !in_array($user['hakakses'], [1, 2])) {
            $this->session->set_flashdata('message', 'Access denied. Only admin and webmaster can access KPI Reports.');
            redirect('welcome/restricted');
        }
    }

    public function index()
    {
        // Load menu helper
        $this->load->helper('menu_helper');

        $data['message'] = $this->session->flashdata('message');

        // Set default date range untuk KPI Reports
        $data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $data['reportrange'] = $this->input->post('reportrange');
        }

        // Set all template data (same pattern as Welcome controller)
        $this->data['user'] = $this->session->userdata('user');
        $this->data['nama_pk'] = $this->session->userdata('nama_pk');
        $this->data['status_performa'] = $this->session->userdata('status_performa');
        $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
        $this->data['content'] = $this->load->view('kpi_reports_index', $data, TRUE);

        // Load main template
        $this->load->view('main', $this->data);
    }

    public function dashboard_picker()
    {
        // Load menu helper
        $this->load->helper('menu_helper');

        $data['message'] = $this->session->flashdata('message');
        $user = $this->session->userdata('user');

        // Set default date range untuk Dashboard KPI Picker (today)
        $data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post' && $this->input->post('reportrange')) {
            $data['reportrange'] = $this->input->post('reportrange');
        }

        // Parse date range with validation
        $dates = explode(' - ', $data['reportrange']);
        if (count($dates) >= 2) {
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
        } else {
            // Fallback to default if format is invalid
            $start_date = date('Y-m-d 00:00:00');
            $end_date = date('Y-m-d H:i:s');
            $data['reportrange'] = $start_date . ' - ' . $end_date;
        }

        // Get picker dashboard statistics (dengan target)
        $data['dashboard_stats'] = $this->get_picker_dashboard_stats($start_date, $end_date);
        $data['tanggal_filter'] = date('Y-m-d', strtotime($start_date));

        // Check if this is AJAX request (from menu click OR form submission)
        if ($this->input->is_ajax_request() || $this->input->get('ajax')) {
            // Return JSON for AJAX (like other menu pages)
            header('Content-Type: application/json');
            echo json_encode(array(
                'view' => $this->load->view('kpi_dashboard_picker', $data, TRUE),
                'message' => empty($data['message']) ? null : $data['message'],
            ));
            exit;
        } else {
            // Return full HTML for direct access
            $this->data['user'] = $this->session->userdata('user');
            $this->data['nama_pk'] = $this->session->userdata('nama_pk');
            $this->data['status_performa'] = $this->session->userdata('status_performa');
            $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
            $this->data['content'] = $this->load->view('kpi_dashboard_picker', $data, TRUE);

            // Load main template
            $this->load->view('main', $this->data);
        }
    }

    public function export_excel_picker()
    {
        ini_set('memory_limit', '-1');

        // Get date range
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post' || $this->input->method() == 'get') {
            $reportrange = $this->input->post('reportrange') ?: $this->input->get('reportrange');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = $dates[0];
        $end_date = $dates[1];

        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // Calculate number of days in period
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $data['num_days'] = $interval->days + 1; // +1 to include both start and end date

        $dashboard_stats = $this->get_picker_dashboard_stats($start_date, $end_date);

        // Extract data for view
        $data['top_pickers_inti'] = $dashboard_stats['top_pickers_inti'] ?? [];
        $data['top_pickers_others'] = $dashboard_stats['top_pickers_others'] ?? [];

        // Set Excel headers
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Picker_" . date('Y-m-d_His') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('template_report/kpi_picker_excel', $data);
    }

    public function dashboard_packer()
    {
        // Load menu helper
        $this->load->helper('menu_helper');

        $data['message'] = $this->session->flashdata('message');
        $user = $this->session->userdata('user');

        // Set default date range untuk Dashboard KPI Packer (today)
        $data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post' && $this->input->post('reportrange')) {
            $data['reportrange'] = $this->input->post('reportrange');
        }

        // Parse date range with validation
        $dates = explode(' - ', $data['reportrange']);
        if (count($dates) >= 2) {
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
        } else {
            // Fallback to default if format is invalid
            $start_date = date('Y-m-d 00:00:00');
            $end_date = date('Y-m-d H:i:s');
            $data['reportrange'] = $start_date . ' - ' . $end_date;
        }

        // Get packer dashboard statistics (dengan target)
        $data['dashboard_stats'] = $this->get_packer_dashboard_stats($start_date, $end_date);
        $data['tanggal_filter'] = date('Y-m-d', strtotime($start_date));

        // Check if this is AJAX request (from menu click OR form submission)
        if ($this->input->is_ajax_request() || $this->input->get('ajax')) {
            // Return JSON for AJAX (like other menu pages)
            header('Content-Type: application/json');
            echo json_encode(array(
                'view' => $this->load->view('kpi_dashboard_packer', $data, TRUE),
                'message' => empty($data['message']) ? null : $data['message'],
            ));
            exit;
        } else {
            // Return full HTML for direct access
            $this->data['user'] = $this->session->userdata('user');
            $this->data['nama_pk'] = $this->session->userdata('nama_pk');
            $this->data['status_performa'] = $this->session->userdata('status_performa');
            $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
            $this->data['content'] = $this->load->view('kpi_dashboard_packer', $data, TRUE);

            // Load main template
            $this->load->view('main', $this->data);
        }
    }

    public function export_excel_packer()
    {
        ini_set('memory_limit', '-1');

        // Get date range
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post' || $this->input->method() == 'get') {
            $reportrange = $this->input->post('reportrange') ?: $this->input->get('reportrange');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = $dates[0];
        $end_date = $dates[1];

        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // Calculate number of days in period
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $data['num_days'] = $interval->days + 1; // +1 to include both start and end date

        $dashboard_stats = $this->get_packer_dashboard_stats($start_date, $end_date);

        // Pass data directly to view (for v2 template compatibility)
        $data['dashboard_stats'] = $dashboard_stats;
        $data['top_packers_inti'] = $dashboard_stats['top_packers_inti'] ?? [];
        $data['top_packers_others'] = $dashboard_stats['top_packers_others'] ?? [];

        // Set Excel headers
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=KPI_Packer_" . date('Y-m-d_His') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('template_report/kpi_packer_excel', $data);
    }

    /**
     * Jumlah hari kerja "seharusnya" dalam rentang = semua hari kecuali Minggu.
     * Dipakai sebagai penyebut skor Hari Kerja pada perhitungan performa. Minimal 1.
     */
    private function count_working_days($start_date, $end_date)
    {
        $start = new DateTime(date('Y-m-d', strtotime($start_date)));
        $end = new DateTime(date('Y-m-d', strtotime($end_date)));
        $end->modify('+1 day'); // sertakan tanggal akhir
        $days = 0;
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $d) {
            if ($d->format('w') != 0) { // 0 = Minggu
                $days++;
            }
        }
        return max(1, $days);
    }

    private function get_picker_dashboard_stats($start_date, $end_date)
    {
        $stats = array();
        $stats['expected_hari_kerja'] = $this->count_working_days($start_date, $end_date);

        try {
            // 1. Get Summary Totals
            $summary_query = $this->db->query("
                SELECT 
                    COUNT(DISTINCT rab.id_resi) as total_picking,
                    COUNT(DISTINCT rab.yangambil_pegawai) as total_active_pickers,
                    COUNT(DISTINCT dr.sku) as total_sku_unique,
                    SUM(CAST(dr.jumlah AS UNSIGNED)) as total_sku_qty
                FROM tblresiambilbarang rab
                LEFT JOIN tbldetailprintresi dr ON dr.id_resi = rab.id_resi
                WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
            ", array($start_date, $end_date));

            $summary = $summary_query->row();
            $stats['total_picking'] = $summary->total_picking ?? 0;
            $stats['total_qty'] = $summary->total_sku_qty ?? 0;
            $stats['total_sku_unique'] = $summary->total_sku_unique ?? 0;
            $stats['total_active_pickers'] = $summary->total_active_pickers ?? 0;

            // Average SKU per Picker (unique SKU / total receipts)
            $stats['avg_sku_per_picker'] = $stats['total_picking'] > 0
                ? round($stats['total_sku_unique'] / $stats['total_picking'], 2)
                : 0;

            // 2. Get Top Pickers with targets and detailed breakdown
            try {
                // Try with window function first (MySQL 8.0+)
                $pickers_query = $this->db->query("
                    SELECT 
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        CASE WHEN u.hakakses = 3 THEN 1 ELSE 0 END as is_inti,
                        COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)) as total_hari_masuk,
                        COUNT(DISTINCT rab.id_resi) as total_resi,
                        COUNT(DISTINCT CASE WHEN rab.is_preorder = 0 THEN rab.id_resi END) as resi_normal,
                        COUNT(DISTINCT dr.sku) as total_sku_unique,
                        SUM(CAST(dr.jumlah AS UNSIGNED)) as total_qty,
                        ROUND(SUM(CAST(dr.jumlah AS UNSIGNED)) / COUNT(DISTINCT rab.id_resi), 2) as avg_sku_per_resi,
                        MIN(TIME(rab.tanggal_resiambilbarang)) as jam_in,
                        MAX(TIME(rab.tanggal_resiambilbarang)) as jam_out,
                        COALESCE((
                            SELECT SUM(
                                LEAST(
                                    GREATEST(
                                        TIMESTAMPDIFF(SECOND, r2.min_tgl, r2.max_tgl) / 3600,
                                        0.5
                                    ),
                                    10.0
                                )
                            )
                            FROM (
                                SELECT yangambil_pegawai,
                                       MIN(tanggal_resiambilbarang) as min_tgl,
                                       MAX(tanggal_resiambilbarang) as max_tgl
                                FROM tblresiambilbarang
                                WHERE tanggal_resiambilbarang BETWEEN ? AND ?
                                GROUP BY yangambil_pegawai, DATE(tanggal_resiambilbarang)
                            ) r2
                            WHERE r2.yangambil_pegawai = rab.yangambil_pegawai
                        ), 0) as total_jam_kerja,
                        COALESCE(AVG(tk.target_resi), 1150) as target_resi,
                        COALESCE((
                            SELECT COUNT(mp.id_masalahpicker)
                            FROM tblmasalahpicker mp
                            JOIN tblresiambilbarang r ON r.id_resi = mp.id_printresi
                            WHERE r.yangambil_pegawai = rab.yangambil_pegawai
                            AND r.tanggal_resiambilbarang BETWEEN ? AND ?
                            -- Reject Display (tipe 5) dilaporkan lewat menu terpisah, bukan kesalahan picker
                            AND mp.id_typemasalah != 5
                        ), 0) as total_kesalahan,
                        
                        -- SKU breakdown by category (using COUNT DISTINCT to prevent double counting due to join)
                        COUNT(DISTINCT CASE WHEN dr.sku_count = 1 THEN rab.id_resi END) as paket_1_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 2 AND 5 THEN rab.id_resi END) as paket_5_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 6 AND 10 THEN rab.id_resi END) as paket_10_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 11 AND 20 THEN rab.id_resi END) as paket_20_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 21 AND 30 THEN rab.id_resi END) as paket_30_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 31 AND 40 THEN rab.id_resi END) as paket_40_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count BETWEEN 41 AND 50 THEN rab.id_resi END) as paket_50_sku,
                        COUNT(DISTINCT CASE WHEN dr.sku_count > 50 THEN rab.id_resi END) as paket_50plus_sku
                        
                    FROM tblresiambilbarang rab
                    LEFT JOIN (
                        SELECT id_resi, sku, jumlah, 
                               COUNT(*) OVER (PARTITION BY id_resi) as sku_count
                        FROM tbldetailprintresi
                    ) dr ON dr.id_resi = rab.id_resi
                    LEFT JOIN tblpegawai peg ON peg.kode_pegawai = rab.yangambil_pegawai
                    LEFT JOIN tbluser u ON u.id_pegawai = peg.kode_pegawai
                    LEFT JOIN tblnamaambilbarang nab ON nab.id_pegawai = rab.yangambil_pegawai AND nab.status_aktif = 'AKTIF'
                    LEFT JOIN tbltargetkpiharian tk ON tk.id_user = u.id_user 
                        AND DATE(tk.tanggal) = DATE(rab.tanggal_resiambilbarang)
                        AND LOWER(tk.role) = 'picker'
                    WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                    AND rab.yangambil_pegawai IS NOT NULL
                    GROUP BY 
                        rab.yangambil_pegawai, 
                        u.id_user, 
                        u.username, 
                        peg.nama_pegawai,
                        u.hakakses
                    ORDER BY total_resi DESC
                ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            } catch (Exception $e) {
                // Fallback query without window function for older MySQL
                log_message('info', 'Window function failed for picker, using fallback query: ' . $e->getMessage());

                $pickers_query = $this->db->query("
                    SELECT 
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        CASE WHEN u.hakakses = 3 THEN 1 ELSE 0 END as is_inti,
                        COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)) as total_hari_masuk,
                        COUNT(DISTINCT rab.id_resi) as total_resi,
                        COUNT(DISTINCT CASE WHEN rab.is_preorder = 0 THEN rab.id_resi END) as resi_normal,
                        COUNT(DISTINCT dr.sku) as total_sku_unique,
                        SUM(CAST(dr.jumlah AS UNSIGNED)) as total_qty,
                        ROUND(SUM(CAST(dr.jumlah AS UNSIGNED)) / COUNT(DISTINCT rab.id_resi), 2) as avg_sku_per_resi,
                        MIN(TIME(rab.tanggal_resiambilbarang)) as jam_in,
                        MAX(TIME(rab.tanggal_resiambilbarang)) as jam_out,
                        COALESCE((
                            SELECT SUM(
                                LEAST(
                                    GREATEST(
                                        TIMESTAMPDIFF(SECOND, r2.min_tgl, r2.max_tgl) / 3600,
                                        0.5
                                    ),
                                    10.0
                                )
                            )
                            FROM (
                                SELECT yangambil_pegawai,
                                       MIN(tanggal_resiambilbarang) as min_tgl,
                                       MAX(tanggal_resiambilbarang) as max_tgl
                                FROM tblresiambilbarang
                                WHERE tanggal_resiambilbarang BETWEEN ? AND ?
                                GROUP BY yangambil_pegawai, DATE(tanggal_resiambilbarang)
                            ) r2
                            WHERE r2.yangambil_pegawai = rab.yangambil_pegawai
                        ), 0) as total_jam_kerja,
                        COALESCE(AVG(tk.target_resi), 1150) as target_resi,
                        COALESCE((
                            SELECT COUNT(mp.id_masalahpicker)
                            FROM tblmasalahpicker mp
                            JOIN tblresiambilbarang r ON r.id_resi = mp.id_printresi
                            WHERE r.yangambil_pegawai = rab.yangambil_pegawai
                            AND r.tanggal_resiambilbarang BETWEEN ? AND ?
                            -- Reject Display (tipe 5) dilaporkan lewat menu terpisah, bukan kesalahan picker
                            AND mp.id_typemasalah != 5
                        ), 0) as total_kesalahan,
                        0 as paket_1_sku,
                        0 as paket_5_sku,
                        0 as paket_10_sku,
                        0 as paket_20_sku,
                        0 as paket_30_sku,
                        0 as paket_40_sku,
                        0 as paket_50_sku,
                        0 as paket_50plus_sku
                        
                    FROM tblresiambilbarang rab
                    LEFT JOIN tbldetailprintresi dr ON dr.id_resi = rab.id_resi
                    LEFT JOIN tblpegawai peg ON peg.kode_pegawai = rab.yangambil_pegawai
                    LEFT JOIN tbluser u ON u.id_pegawai = peg.kode_pegawai
                    LEFT JOIN tblnamaambilbarang nab ON nab.id_pegawai = rab.yangambil_pegawai AND nab.status_aktif = 'AKTIF'
                    LEFT JOIN tbltargetkpiharian tk ON tk.id_user = u.id_user 
                        AND DATE(tk.tanggal) = DATE(rab.tanggal_resiambilbarang)
                        AND LOWER(tk.role) = 'picker'
                    WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                    AND rab.yangambil_pegawai IS NOT NULL
                    GROUP BY 
                        rab.yangambil_pegawai, 
                        u.id_user, 
                        u.username, 
                        peg.nama_pegawai,
                        u.hakakses
                    ORDER BY total_resi DESC
                ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            }

            $all_pickers = $pickers_query->result_array();

            // Calculate derived fields for each picker
            foreach ($all_pickers as &$picker) {
                // Target dikunci 1.150 resi per HARI KERJA.
                // Hari kerja = jumlah hari dalam rentang yang ADA scan resi untuk picker ini
                // (total_hari_masuk = COUNT(DISTINCT DATE(tanggal_resiambilbarang))).
                // Hari tanpa scan sama sekali dianggap off/izin, jadi tidak menambah target.
                $target_per_hari = 1150;
                $hari_kerja = (int)($picker['total_hari_masuk'] ?? 0);

                // Calculate jam kerja stats (dari jam scan pertama s/d terakhir per hari)
                $picker['total_jam_kerja'] = round((float)($picker['total_jam_kerja'] ?? 0), 2);
                $max_jam_kerja = $hari_kerja * 10.0;
                $picker['pct_jam_kerja'] = $max_jam_kerja > 0
                    ? round(($picker['total_jam_kerja'] / $max_jam_kerja) * 100, 2)
                    : 0;

                // Target = 1.150 × jumlah hari kerja (hari off/izin tidak dihitung)
                $picker['target_resi'] = $target_per_hari * $hari_kerja;

                if ($picker['target_resi'] < 0) {
                    $picker['target_resi'] = 0;
                }

                // Keep total_jam for backward compatibility/other fields
                $picker['total_jam'] = $picker['total_jam_kerja'];

                // Calculate per-hour rates
                $picker['paket_per_jam'] = $picker['total_jam'] > 0
                    ? round($picker['total_resi'] / $picker['total_jam'], 2)
                    : 0;
                $picker['sku_per_jam'] = $picker['total_jam'] > 0
                    ? round($picker['total_sku_unique'] / $picker['total_jam'], 2)
                    : 0;

                // Calculate target achievement
                $picker['selisih'] = $picker['total_resi'] - $picker['target_resi'];
                $picker['pct_capai'] = $picker['target_resi'] > 0
                    ? round(($picker['total_resi'] / $picker['target_resi']) * 100, 2)
                    : 0;

                // Calculate error metrics
                $picker['total_kesalahan'] = (int)($picker['total_kesalahan'] ?? 0);
                $picker['total_eror'] = $picker['total_kesalahan'] * 50;
                $picker['total_final_sku'] = ($picker['total_qty'] ?? 0) - $picker['total_eror'];

                // Recalculate avg_sku_per_resi using: (total_qty - total_eror) / total_resi
                $picker['avg_sku_per_resi'] = $picker['total_resi'] > 0
                    ? ($picker['total_final_sku'] / $picker['total_resi'])
                    : 0;
            }

            $stats['top_pickers_inti'] = array_values(array_filter($all_pickers, function ($p) {
                return !empty($p['is_inti']);
            }));
            $stats['top_pickers_others'] = array_values(array_filter($all_pickers, function ($p) {
                return empty($p['is_inti']);
            }));

            // 3. Hourly performance (for chart)
            $hourly_query = $this->db->query("
                SELECT 
                    HOUR(tanggal_resiambilbarang) as hour,
                    COUNT(*) as total
                FROM tblresiambilbarang
                WHERE tanggal_resiambilbarang BETWEEN ? AND ?
                GROUP BY HOUR(tanggal_resiambilbarang)
                ORDER BY hour
            ", array($start_date, $end_date));
            $stats['hourly_performance'] = $hourly_query->result_array();

        } catch (Exception $e) {
            log_message('error', 'Error getting picker dashboard stats: ' . $e->getMessage());
        }

        return $stats;
    }

    private function get_packer_dashboard_stats($start_date, $end_date)
    {
        $stats = array();
        $stats['expected_hari_kerja'] = $this->count_working_days($start_date, $end_date);

        try {
            // 1. Get Summary Totals
            $summary_query = $this->db->query("
                SELECT 
                    COUNT(DISTINCT p.id_resi) as total_packing,
                    COUNT(DISTINCT p.packer_pegawai) as total_active_packers,
                    COUNT(DISTINCT dr.sku) as total_sku_unique,
                    SUM(CAST(dr.jumlah AS UNSIGNED)) as total_sku_qty
                FROM tblpacking p
                LEFT JOIN tbldetailprintresi dr ON dr.id_resi = p.id_resi
                WHERE p.tanggal_packing BETWEEN ? AND ?
            ", array($start_date, $end_date));

            $summary = $summary_query->row();
            $stats['total_packing'] = $summary->total_packing ?? 0;
            $stats['total_qty'] = $summary->total_sku_qty ?? 0;
            $stats['total_sku_unique'] = $summary->total_sku_unique ?? 0;
            $stats['total_sku'] = $summary->total_sku_unique ?? 0; // Alias for Excel template
            $stats['total_active_packers'] = $summary->total_active_packers ?? 0;

            // Calculate Average (total unique SKU / total packing)
            $stats['avg_sku_per_packer'] = $stats['total_packing'] > 0
                ? round($stats['total_sku_unique'] / $stats['total_packing'], 2)
                : 0;

            // 2. Get Top Packers with targets and detailed breakdown
            try {
                // Try with window function first (MySQL 8.0+)
                $packers_query = $this->db->query("
                    SELECT 
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        u.name as user_name,
                        COUNT(DISTINCT p.id_resi) as total_resi,
                        COUNT(DISTINCT dr.sku) as total_sku_unique,
                        COUNT(DISTINCT dr.sku) as total_sku,
                        SUM(CAST(dr.jumlah AS UNSIGNED)) as total_qty,
                        ROUND(COUNT(DISTINCT dr.sku) / COUNT(DISTINCT p.id_resi), 2) as avg_sku_per_resi,
                        MIN(TIME(p.tanggal_packing)) as jam_in,
                        MAX(TIME(p.tanggal_packing)) as jam_out,
                        ROUND(
                            TIMESTAMPDIFF(MINUTE, 
                                MIN(p.tanggal_packing), 
                                MAX(p.tanggal_packing)
                            ) / 60, 
                            2
                        ) as total_jam,
                        COUNT(DISTINCT DATE(p.tanggal_packing)) as total_hari_masuk,
                        COALESCE((
                            SELECT SUM(
                                LEAST(
                                    GREATEST(
                                        TIMESTAMPDIFF(SECOND, r2.min_tgl, r2.max_tgl) / 3600,
                                        0.5
                                    ),
                                    10.0
                                )
                            )
                            FROM (
                                SELECT packer_pegawai,
                                       MIN(tanggal_packing) as min_tgl,
                                       MAX(tanggal_packing) as max_tgl
                                FROM tblpacking
                                WHERE tanggal_packing BETWEEN ? AND ?
                                GROUP BY packer_pegawai, DATE(tanggal_packing)
                            ) r2
                            WHERE r2.packer_pegawai = p.packer_pegawai
                        ), 0) as total_jam_kerja,
                        COALESCE((
                            SELECT COUNT(mp.id_masalahpacker)
                            FROM tblmasalahpacker mp
                            JOIN tblpacking pk2 ON pk2.id_resi = mp.id_printresi
                            WHERE pk2.packer_pegawai = p.packer_pegawai
                            AND pk2.tanggal_packing BETWEEN ? AND ?
                        ), 0) as total_kesalahan,
                        COALESCE(AVG(tk.target_resi), 0) as target_resi,
                        CASE WHEN u.hakakses = 4 THEN 1 ELSE 0 END as is_inti,
                        
                        -- SKU breakdown by category
                        SUM(CASE WHEN dr.sku_count = 1 THEN 1 ELSE 0 END) as paket_1_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 2 AND 5 THEN 1 ELSE 0 END) as paket_5_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 6 AND 10 THEN 1 ELSE 0 END) as paket_10_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 11 AND 20 THEN 1 ELSE 0 END) as paket_20_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 21 AND 30 THEN 1 ELSE 0 END) as paket_30_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 31 AND 40 THEN 1 ELSE 0 END) as paket_40_sku,
                        SUM(CASE WHEN dr.sku_count BETWEEN 41 AND 50 THEN 1 ELSE 0 END) as paket_50_sku,
                        SUM(CASE WHEN dr.sku_count > 50 THEN 1 ELSE 0 END) as paket_50plus_sku
                        
                    FROM tblpacking p
                    LEFT JOIN (
                        SELECT id_resi, sku, jumlah, 
                               COUNT(*) OVER (PARTITION BY id_resi) as sku_count
                        FROM tbldetailprintresi
                    ) dr ON dr.id_resi = p.id_resi
                    LEFT JOIN tblpegawai peg ON peg.kode_pegawai = p.packer_pegawai
                    LEFT JOIN tbluser u ON (u.id_pegawai = peg.kode_pegawai OR u.id_user = p.packer_pegawai)
                    LEFT JOIN tbltargetkpiharian tk ON tk.id_user = u.id_user 
                        AND DATE(tk.tanggal) = DATE(p.tanggal_packing)
                        AND LOWER(tk.role) = 'packer'
                    WHERE p.tanggal_packing BETWEEN ? AND ?
                    AND p.packer_pegawai IS NOT NULL
                    GROUP BY 
                        p.packer_pegawai,
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        u.name,
                        peg.kode_pegawai,
                        u.hakakses
                    ORDER BY total_resi DESC
                ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            } catch (Exception $e) {
                // Fallback query without window function for older MySQL
                log_message('info', 'Window function failed for packer, using fallback query: ' . $e->getMessage());

                $packers_query = $this->db->query("
                    SELECT 
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        u.name as user_name,
                        COUNT(DISTINCT p.id_resi) as total_resi,
                        COUNT(DISTINCT dr.sku) as total_sku_unique,
                        COUNT(DISTINCT dr.sku) as total_sku,
                        SUM(CAST(dr.jumlah AS UNSIGNED)) as total_qty,
                        ROUND(COUNT(DISTINCT dr.sku) / COUNT(DISTINCT p.id_resi), 2) as avg_sku_per_resi,
                        MIN(TIME(p.tanggal_packing)) as jam_in,
                        MAX(TIME(p.tanggal_packing)) as jam_out,
                        ROUND(
                            TIMESTAMPDIFF(MINUTE, 
                                MIN(p.tanggal_packing), 
                                MAX(p.tanggal_packing)
                            ) / 60, 
                            2
                        ) as total_jam,
                        COUNT(DISTINCT DATE(p.tanggal_packing)) as total_hari_masuk,
                        COALESCE((
                            SELECT SUM(
                                LEAST(
                                    GREATEST(
                                        TIMESTAMPDIFF(SECOND, r2.min_tgl, r2.max_tgl) / 3600,
                                        0.5
                                    ),
                                    10.0
                                )
                            )
                            FROM (
                                SELECT packer_pegawai,
                                       MIN(tanggal_packing) as min_tgl,
                                       MAX(tanggal_packing) as max_tgl
                                FROM tblpacking
                                WHERE tanggal_packing BETWEEN ? AND ?
                                GROUP BY packer_pegawai, DATE(tanggal_packing)
                            ) r2
                            WHERE r2.packer_pegawai = p.packer_pegawai
                        ), 0) as total_jam_kerja,
                        COALESCE((
                            SELECT COUNT(mp.id_masalahpacker)
                            FROM tblmasalahpacker mp
                            JOIN tblpacking pk2 ON pk2.id_resi = mp.id_printresi
                            WHERE pk2.packer_pegawai = p.packer_pegawai
                            AND pk2.tanggal_packing BETWEEN ? AND ?
                        ), 0) as total_kesalahan,
                        COALESCE(AVG(tk.target_resi), 0) as target_resi,
                        CASE WHEN u.hakakses = 4 THEN 1 ELSE 0 END as is_inti,
                        0 as paket_1_sku,
                        0 as paket_5_sku,
                        0 as paket_10_sku,
                        0 as paket_20_sku,
                        0 as paket_30_sku,
                        0 as paket_40_sku,
                        0 as paket_50_sku,
                        0 as paket_50plus_sku
                        
                    FROM tblpacking p
                    LEFT JOIN tbldetailprintresi dr ON dr.id_resi = p.id_resi
                    LEFT JOIN tblpegawai peg ON peg.kode_pegawai = p.packer_pegawai
                    LEFT JOIN tbluser u ON (u.id_pegawai = peg.kode_pegawai OR u.id_user = p.packer_pegawai)
                    LEFT JOIN tbltargetkpiharian tk ON tk.id_user = u.id_user 
                        AND DATE(tk.tanggal) = DATE(p.tanggal_packing)
                        AND LOWER(tk.role) = 'packer'
                    WHERE p.tanggal_packing BETWEEN ? AND ?
                    AND p.packer_pegawai IS NOT NULL
                    GROUP BY 
                        p.packer_pegawai,
                        u.id_user,
                        u.username,
                        peg.nama_pegawai,
                        u.name,
                        peg.kode_pegawai,
                        u.hakakses
                    ORDER BY total_resi DESC
                ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            }

            $all_packers = $packers_query->result_array();

            // Calculate derived fields for each packer
            foreach ($all_packers as &$packer) {
                // Target dikunci 1.150 resi per HARI KERJA.
                // Hari kerja = jumlah hari dalam rentang yang ADA scan packing untuk packer ini.
                // Hari tanpa scan packing dianggap off/izin, jadi tidak menambah target.
                $target_per_hari = 1150;
                $hari_kerja = (int)($packer['total_hari_masuk'] ?? 0);

                // Jam kerja (dari jam packing pertama s/d terakhir per hari, dibatasi 0,5-10 jam)
                $packer['total_jam_kerja'] = round((float)($packer['total_jam_kerja'] ?? 0), 2);
                $max_jam_kerja = $hari_kerja * 10.0;
                $packer['pct_jam_kerja'] = $max_jam_kerja > 0
                    ? round(($packer['total_jam_kerja'] / $max_jam_kerja) * 100, 2)
                    : 0;

                // Target = 1.150 × jumlah hari kerja (hari off/izin tidak dihitung)
                $packer['target_resi'] = $target_per_hari * $hari_kerja;
                if ($packer['target_resi'] < 0) {
                    $packer['target_resi'] = 0;
                }

                // Backward compat + per-hour rates
                $packer['total_jam'] = $packer['total_jam_kerja'];
                $packer['paket_per_jam'] = $packer['total_jam'] > 0
                    ? round($packer['total_resi'] / $packer['total_jam'], 2)
                    : 0;
                $packer['sku_per_jam'] = $packer['total_jam'] > 0
                    ? round($packer['total_sku_unique'] / $packer['total_jam'], 2)
                    : 0;

                // Calculate target achievement
                $packer['selisih'] = $packer['total_resi'] - $packer['target_resi'];
                $packer['pct_capai'] = $packer['target_resi'] > 0
                    ? round(($packer['total_resi'] / $packer['target_resi']) * 100, 2)
                    : 0;

                // Error metrics (dari tblmasalahpacker)
                $packer['total_kesalahan'] = (int)($packer['total_kesalahan'] ?? 0);
                $packer['total_eror'] = $packer['total_kesalahan'] * 50;
                $packer['total_final_sku'] = ($packer['total_qty'] ?? 0) - $packer['total_eror'];
            }

            $stats['top_packers_inti'] = array_values(array_filter($all_packers, function ($p) {
                return $p['is_inti'] == 1;
            }));
            $stats['top_packers_others'] = array_values(array_filter($all_packers, function ($p) {
                return $p['is_inti'] == 0;
            }));

            // 3. Hourly performance
            $hourly_query = $this->db->query("
                SELECT 
                    HOUR(tanggal_packing) as hour,
                    COUNT(*) as total
                FROM tblpacking
                WHERE tanggal_packing BETWEEN ? AND ?
                GROUP BY HOUR(tanggal_packing)
                ORDER BY hour
            ", array($start_date, $end_date));
            $stats['hourly_performance'] = $hourly_query->result_array();

        } catch (Exception $e) {
            log_message('error', 'Error getting packer dashboard stats: ' . $e->getMessage());
        }

        return $stats;
    }

    public function export()
    {
        $data['message'] = $this->session->flashdata('message');

        // Set default date range untuk export
        $data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $data['reportrange'] = $this->input->post('reportrange');
            // Jika ada data POST, langsung export
            $this->export_to_excel();
        } else {
            // Jika tidak ada data POST, tampilkan halaman export
            $this->load->helper('menu_helper');

            $this->data['user'] = $this->session->userdata('user');
            $this->data['nama_pk'] = $this->session->userdata('nama_pk');
            $this->data['status_performa'] = $this->session->userdata('status_performa');
            $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
            $this->data['content'] = $this->load->view('kpi_reports_export', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_kpi_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        try {
            // Ambil data KPI langsung dari tabel tblresi
            $data = array();

            // KPI Summary Cards - dari tabel tblprintresi
            $summary_query = $this->db->query("
                SELECT 
                    COUNT(DISTINCT pr.id_printresi) as total_resi,
                    COUNT(DISTINCT pr.created_by) as total_users_scan,
                    COUNT(DISTINCT rab.yangambil_pegawai) as total_users_picker,
                    COUNT(DISTINCT p.packer_pegawai) as total_users_packer,
                    COUNT(DISTINCT rk.id_pegawai) as total_users_ho
                FROM tblprintresi pr
                LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
                LEFT JOIN tblresikeluar rk ON rk.id_resi = pr.id_printresi
                WHERE pr.tanggal_printresi BETWEEN ? AND ?
            ", array($start_date, $end_date));

            $summary = $summary_query->row_array();
            $total_users = ($summary['total_users_scan'] ?? 0) +
                ($summary['total_users_picker'] ?? 0) +
                ($summary['total_users_packer'] ?? 0) +
                ($summary['total_users_ho'] ?? 0);

            $data['kpi_summary'] = array(
                'total_status' => 4, // Scan, Picker, Packer, HO
                'total_users' => $total_users,
                'total_transactions' => $summary['total_resi'] ?? 0,
                'rata_rata_capai' => 85 // Placeholder
            );

            // Status Performa Cards - berdasarkan proses (Scan, Picker, Packer, HO)
            $status_query = $this->db->query("
                SELECT 
                    'SCAN' as kode_status,
                    'Resi Scan' as nama_status,
                    COUNT(DISTINCT pr.id_printresi) as total_transaksi,
                    COUNT(DISTINCT pr.created_by) as total_user,
                    'GOOD' as status_performa,
                    85 as rata_rata_capai
                FROM tblprintresi pr
                WHERE pr.tanggal_printresi BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    'PICKER' as kode_status,
                    'Picking' as nama_status,
                    COUNT(DISTINCT rab.id_resi) as total_transaksi,
                    COUNT(DISTINCT rab.yangambil_pegawai) as total_user,
                    'GOOD' as status_performa,
                    80 as rata_rata_capai
                FROM tblresiambilbarang rab
                WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    'PACKER' as kode_status,
                    'Packing' as nama_status,
                    COUNT(DISTINCT p.id_resi) as total_transaksi,
                    COUNT(DISTINCT p.packer_pegawai) as total_user,
                    'GOOD' as status_performa,
                    75 as rata_rata_capai
                FROM tblpacking p
                WHERE p.tanggal_packing BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT 
                    'HO' as kode_status,
                    'Hand Over' as nama_status,
                    COUNT(DISTINCT rk.id_resi) as total_transaksi,
                    COUNT(DISTINCT rk.id_pegawai) as total_user,
                    'GOOD' as status_performa,
                    90 as rata_rata_capai
                FROM tblresikeluar rk
                WHERE rk.tanggal_resikeluar BETWEEN ? AND ?
            ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            $data['status_performa'] = $status_query->result_array();

            // Top Performers - top 10 user berdasarkan transaksi (Picker + Packer)
            $top_query = $this->db->query("
                SELECT 
                    u.username as nama_user,
                    peg.nama_pegawai,
                    'PICKER' as nama_status,
                    COUNT(rab.id_resi) as total_transaksi,
                    COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)) as hari_aktif,
                    ROUND(COUNT(rab.id_resi) / NULLIF(COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)), 0), 2) as rata_rata_harian
                FROM tblresiambilbarang rab
                LEFT JOIN tblpegawai peg ON peg.kode_pegawai = rab.yangambil_pegawai
                LEFT JOIN tbluser u ON u.id_pegawai = peg.kode_pegawai
                WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                AND rab.yangambil_pegawai IS NOT NULL
                GROUP BY u.username, peg.nama_pegawai
                
                UNION ALL
                
                SELECT 
                    u.username as nama_user,
                    peg.nama_pegawai,
                    'PACKER' as nama_status,
                    COUNT(p.id_resi) as total_transaksi,
                    COUNT(DISTINCT DATE(p.tanggal_packing)) as hari_aktif,
                    ROUND(COUNT(p.id_resi) / NULLIF(COUNT(DISTINCT DATE(p.tanggal_packing)), 0), 2) as rata_rata_harian
                FROM tblpacking p
                LEFT JOIN tblpegawai peg ON peg.kode_pegawai = p.packer_pegawai
                LEFT JOIN tbluser u ON u.id_pegawai = peg.kode_pegawai
                WHERE p.tanggal_packing BETWEEN ? AND ?
                AND p.packer_pegawai IS NOT NULL
                GROUP BY u.username, peg.nama_pegawai
                
                ORDER BY total_transaksi DESC
                LIMIT 10
            ", array($start_date, $end_date, $start_date, $end_date));
            $data['top_performers'] = $top_query->result_array();

            // Daily Performance Chart - transaksi per hari per proses
            $daily_query = $this->db->query("
                SELECT 
                    DATE(pr.tanggal_printresi) as tanggal,
                    'SCAN' as kode_status,
                    COUNT(pr.id_printresi) as total_transaksi
                FROM tblprintresi pr
                WHERE pr.tanggal_printresi BETWEEN ? AND ?
                GROUP BY DATE(pr.tanggal_printresi)
                
                UNION ALL
                
                SELECT 
                    DATE(rab.tanggal_resiambilbarang) as tanggal,
                    'PICKER' as kode_status,
                    COUNT(rab.id_resi) as total_transaksi
                FROM tblresiambilbarang rab
                WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                GROUP BY DATE(rab.tanggal_resiambilbarang)
                
                UNION ALL
                
                SELECT 
                    DATE(p.tanggal_packing) as tanggal,
                    'PACKER' as kode_status,
                    COUNT(p.id_resi) as total_transaksi
                FROM tblpacking p
                WHERE p.tanggal_packing BETWEEN ? AND ?
                GROUP BY DATE(p.tanggal_packing)
                
                UNION ALL
                
                SELECT 
                    DATE(rk.tanggal_resikeluar) as tanggal,
                    'HO' as kode_status,
                    COUNT(rk.id_resi) as total_transaksi
                FROM tblresikeluar rk
                WHERE rk.tanggal_resikeluar BETWEEN ? AND ?
                GROUP BY DATE(rk.tanggal_resikeluar)
                
                ORDER BY tanggal ASC, kode_status ASC
            ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            $data['daily_chart'] = $daily_query->result_array();

            // Status Performance Distribution
            $dist_query = $this->db->query("
                SELECT 'Resi Scan' as nama_status, COUNT(*) as total_transaksi FROM tblprintresi WHERE tanggal_printresi BETWEEN ? AND ?
                UNION ALL
                SELECT 'Picking' as nama_status, COUNT(*) as total_transaksi FROM tblresiambilbarang WHERE tanggal_resiambilbarang BETWEEN ? AND ?
                UNION ALL
                SELECT 'Packing' as nama_status, COUNT(*) as total_transaksi FROM tblpacking WHERE tanggal_packing BETWEEN ? AND ?
                UNION ALL
                SELECT 'Hand Over' as nama_status, COUNT(*) as total_transaksi FROM tblresikeluar WHERE tanggal_resikeluar BETWEEN ? AND ?
            ", array($start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
            $data['status_distribution'] = $dist_query->result_array();

            // Metrics untuk KPI table
            $total_resi = $summary['total_resi'] ?? 0;
            $data['total_receipts'] = $total_resi;
            $data['shipped_receipts'] = round($total_resi * 0.85);
            $data['pending_receipts'] = round($total_resi * 0.10);
            $data['retur_receipts'] = round($total_resi * 0.05);
            $data['completion_rate'] = $total_resi > 0 ? round(($data['shipped_receipts'] / $total_resi) * 100, 2) : 0;
            $data['retur_rate'] = $total_resi > 0 ? round(($data['retur_receipts'] / $total_resi) * 100, 2) : 0;
            $data['avg_processing_time'] = 18;
            $data['picker_productivity'] = 65;
            $data['packer_productivity'] = 70;

            echo json_encode(array('success' => true, 'data' => $data));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
        exit();
    }

    private function update_kpi_range($start_date, $end_date)
    {
        // Update KPI untuk setiap hari dalam range
        $current_date = $start_date;
        while (strtotime($current_date) <= strtotime($end_date)) {
            $this->kpi_fcd->update_kpi_harian($current_date);
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
    }

    public function export_to_excel()
    {
        ini_set('memory_limit', '-1');

        // Handle different export ranges
        $range = $this->input->get('range');
        if ($range) {
            switch ($range) {
                case 'yesterday':
                    $reportrange = date('Y-m-d 00:00:00', strtotime('-1 day')) . ' - ' . date('Y-m-d 23:59:59', strtotime('-1 day'));
                    break;
                case 'this_week':
                    $reportrange = date('Y-m-d 00:00:00', strtotime('monday this week')) . ' - ' . date('Y-m-d H:i:s');
                    break;
                case 'this_month':
                    $reportrange = date('Y-m-01 00:00:00') . ' - ' . date('Y-m-d H:i:s');
                    break;
                default:
                    $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
            }
        } else {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
            if ($this->input->method() == 'post') {
                $reportrange = $this->input->post('reportrange');
            }
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['kpi_data'] = $this->get_kpi_data_for_export($start_date, $end_date);

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=KPI_Reports_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/kpi_reports', $data);
    }

    private function get_kpi_data_for_export($start_date, $end_date)
    {
        // Ambil data KPI untuk export menggunakan kpi_fcd (bukan receipt_fcd yang kosong)
        $data = array();

        $total_receipts = $this->kpi_fcd->get_total_receipts_processed($start_date, $end_date);
        $shipped_receipts = $this->kpi_fcd->get_total_shipped_receipts($start_date, $end_date);
        $pending_receipts = $this->kpi_fcd->get_total_pending_receipts($start_date, $end_date);
        $retur_receipts = $this->kpi_fcd->get_total_retur_receipts($start_date, $end_date);

        $completion_rate = $total_receipts > 0 ? ($shipped_receipts / $total_receipts) * 100 : 0;
        $retur_rate = $total_receipts > 0 ? ($retur_receipts / $total_receipts) * 100 : 0;

        $data['total_receipts'] = $total_receipts;
        $data['shipped_receipts'] = $shipped_receipts;
        $data['pending_receipts'] = $pending_receipts;
        $data['retur_receipts'] = $retur_receipts;
        $data['completion_rate'] = round($completion_rate, 2);
        $data['retur_rate'] = round($retur_rate, 2);
        $data['avg_processing_time'] = $this->kpi_fcd->get_avg_processing_time($start_date, $end_date);
        $data['picker_productivity'] = $this->kpi_fcd->get_picker_productivity($start_date, $end_date);
        $data['packer_productivity'] = $this->kpi_fcd->get_packer_productivity($start_date, $end_date);
        $data['daily_performance'] = $this->kpi_fcd->get_daily_performance($start_date, $end_date);

        return $data;
    }
}