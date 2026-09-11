<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Operational_dashboard extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('receipt_fcd');
        // $this->load->model('target_kpi_fcd'); // Load if needed for target
        // $this->load->model('report_fcd'); // Potentially needed for daily metrics
    }

    public function index()
    {
        $this->load->view('operational_dashboard');
    }

    public function get_summary_data()
    {
        // 1. Total Resi Hari Ini
        $today = date('Y-m-d');
        // Reuse existing method or simple count
        $this->db->where('DATE(tanggal_printresi)', $today);
        $this->db->where('batal', 0);
        $data['total_resi_today'] = $this->db->count_all_results('tblprintresi');

        // 2. Target Resi (Global or Sum of active users?)
        // User request: "Target Resi". Assume Global Daily Target.
        // Assuming we need to fetch from tbltargetkpiharian sum for today.
        $this->load->model('target_kpi_fcd');
        $target_summary = $this->target_kpi_fcd->get_target_summary($today, 'PACKER'); // Example role
        // Ideally should be sum of all roles or specific role. For "Operasional", likely SCAN/PICKER/PACKER/HO targets combined or just Packer?
        // Let's grab all roles for today.
        $query_target = $this->db->query("SELECT SUM(target_resi) as total FROM tbltargetkpiharian WHERE tanggal = '$today'");
        $data['target_resi'] = $query_target->row()->total ?? 0;


        // 3. Picker / Packer / HO (Selisih dari target)
        // Need Actual vs Target for each.
        
        // Actual Picker
        $this->db->where('DATE(tanggal_resiambilbarang)', $today);
        $data['actual_picker'] = $this->db->count_all_results('tblresiambilbarang');
        
        // Actual Packer
        $this->db->where('DATE(tanggal_packing)', $today);
        $data['actual_packer'] = $this->db->count_all_results('tblpacking');
        
        // Actual HO
        $this->db->where('DATE(tanggal_resikeluar)', $today);
        $data['actual_ho'] = $this->db->count_all_results('tblresikeluar');

        // Targets for each role
        $role_targets = $this->db->query("
            SELECT role, SUM(target_resi) as total 
            FROM tbltargetkpiharian 
            WHERE tanggal = '$today' 
            GROUP BY role
        ")->result_array();
        
        $targets = ['PICKER' => 0, 'PACKER' => 0, 'HO' => 0]; // Default
        foreach($role_targets as $rt) {
            $targets[$rt['role']] = $rt['total'];
        }

        $data['diff_picker'] = $data['actual_picker'] - $targets['PICKER'];
        $data['diff_packer'] = $data['actual_packer'] - $targets['PACKER'];
        // HO might not have explicit target in DB depending on setup, if missing assume 0 or handle logic.
        // If 'HO' role doesn't exist in target table, we might skip or use 0.
        // Let's assume there is a target or compare against Packer count (since HO follows Packer).
        // But user asked "Selisih dari target". So assume target exists.
        $data['diff_ho'] = $data['actual_ho'] - ($targets['HO'] ?? 0); 
        
        // 4. Pending Kemarin
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $data['pending_yesterday'] = $this->receipt_fcd->get_pending_yesterday($yesterday);

        echo json_encode($data);
    }

    public function get_deadline_data()
    {
        $start_date = date('Y-m-d'); // Hari ini
        $end_date = date('Y-m-d', strtotime('+5 days')); // 5 hari ke depan

        $summary = $this->receipt_fcd->get_deadline_summary($start_date, $end_date);
        
        // Format for frontend: Group by Date -> Courier
        $formatted = [];
        
        // Initialize structure for 6 days
        $current = $start_date;
        while($current <= $end_date) {
            $formatted[$current] = [];
            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }

        foreach ($summary as $row) {
             // Clean courier name to match "SHOPEE, TOKOPEDIA, LAZADA, J&T, NINJA, SICEPAT"
             // if DB has "Shopee Express", map to "SHOPEE" etc if needed.
             // For now use raw name.
             $formatted[$row['deadline_date']][] = [
                 'courier' => $row['nama_kurir'],
                 'count' => $row['total_resi']
             ];
        }

        echo json_encode($formatted);
    }
}
