<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packer_monitoring extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('packer_monitoring_fcd');
    }

    /**
     * Dashboard Monitoring Speed
     */
    public function index()
    {
        $data['title'] = 'Monitoring Speed Packer';
        $this->show($data);
    }

    /**
     * Get real-time monitoring data for DataTables
     */
    public function get_monitoring_data()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        $data = $this->packer_monitoring_fcd->get_monitoring_data();
        
        // Calculate Summary Stats
        $total_slow_scans = 0;
        $slow_persons = 0;
        $total_active = count($data);

        foreach ($data as $row) {
            if ($row->total_slow > 0) {
                $total_slow_scans += $row->total_slow;
                $slow_persons++;
            }
        }

        echo json_encode([
            'data' => $data ? $data : [],
            'stats' => [
                'total_slow_scans' => $total_slow_scans,
                'slow_persons' => $slow_persons,
                'total_active' => $total_active
            ]
        ]);
        exit;
    }

    /**
     * Get detailed logs for a specific packer
     */
    public function get_detailed_logs($user_id)
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        $logs = $this->packer_monitoring_fcd->get_detailed_logs($user_id);
        echo json_encode(['data' => $logs ? $logs : []]);
        exit;
    }

    /**
     * Get slow status for currently logged-in packer (untuk polling di halaman scan)
     */
    public function get_slow_status()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $user_id = $this->data['user']['id_user'];
        $status  = $this->packer_monitoring_fcd->get_slow_status($user_id);

        echo json_encode([
            'is_slow'      => $status['last_is_slow'],
            'slow_count'   => $status['slow_count'],
        ]);
        exit;
    }

    /**
     * Set session action (masuk, pulang, istirahat_mulai, istirahat_selesai)
     */
    public function update_session()
    {
        $action = $this->input->post('action');
        $user_id = $this->data['user']['id_user'];

        $result = $this->packer_monitoring_fcd->update_session($user_id, $action);
        
        if ($result) {
            $this->make_ajax_response(200, 'Berhasil update status');
        } else {
            $this->make_ajax_response(500, 'Gagal update status');
        }
    }

    /**
     * Add comment to a problematic log
     */
    public function add_comment()
    {
        $log_id = $this->input->post('log_id');
        $comment = $this->input->post('comment');

        if ($this->packer_monitoring_fcd->add_comment($log_id, $comment)) {
            $this->make_ajax_response(200, 'Komentar berhasil ditambahkan');
        } else {
            $this->make_ajax_response(500, 'Gagal menambahkan komentar');
        }
    }

    /**
     * Delete/Flag a problematic log
     */
    public function delete_log()
    {
        $log_id = $this->input->post('log_id');

        if ($this->packer_monitoring_fcd->delete_log($log_id)) {
            $this->make_ajax_response(200, 'Log berhasil dihapus dari dashboard');
        } else {
            $this->make_ajax_response(500, 'Gagal menghapus log');
        }
    }

    /**
     * View Detailed Report (includes deleted logs)
     */
    public function report()
    {
        $this->load->model('employee_fcd');
        $data['title'] = 'Laporan Detail Speed Packer';
        $data['list_packer'] = $this->db->get_where('tbluser', ['isactive' => 1])->result_array();
        $this->show($data, 'packer_monitoring/report');
    }

    public function get_report_data()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $filters = [
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'user_id' => $this->input->post('user_id'),
            'is_slow' => $this->input->post('is_slow')
        ];

        $data = $this->packer_monitoring_fcd->get_report_data($filters);
        echo json_encode(['data' => $data ? $data : []]);
        exit;
    }
}
