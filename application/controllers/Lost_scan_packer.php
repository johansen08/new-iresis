<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lost_scan_packer extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('lost_scan_packer_fcd');
    }

    public function index()
    {
        redirect('lost_scan_packer/input');
    }

    public function input($active_tab = 'packer')
    {
        $this->load->model('employee_fcd');
        $data['user_fullname'] = $this->data['user']['name'];
        $employees = $this->employee_fcd->get_employee()->result_array();
        $data['list_packer'] = $employees; 
        $data['list_picker'] = $employees; 
        $data['list_ho'] = $employees; 
        $data['active_tab'] = $active_tab;
        $this->show($data);
    }

    public function report()
    {
        $today = date('Y-m-d');
        $data['is_webmaster'] = ($this->data['user']['hakakses'] == 1);
        $data['stats'] = $this->lost_scan_packer_fcd->get_summary_stats($today, $today);
        $this->show($data);
    }

    public function get_summary_stats_ajax()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $stats = $this->lost_scan_packer_fcd->get_summary_stats($start_date, $end_date);
        echo json_encode(['data' => $stats]);
    }

    public function save()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $lost_scan = [
            'noresi' => $this->input->post('noresi'),
            'lost_type' => $this->input->post('lost_type'),
            'nama_packer' => $this->input->post('nama_petugas')
        ];

        $save = $this->lost_scan_packer_fcd->save($lost_scan, $this->data['user']);

        if ($save === -1) {
            $this->make_ajax_response(400, 'Nomor resi sudah diinput sebelumnya (Double Scan)');
        }

        if ($save > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA, ['status' => 201]);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE, ['status' => 200]);
    }

    public function get_lost_scan_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $lost_type = $this->input->post('lost_type');

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;
        $data['valid_columns'] = array(
            0 => null,
            1 => 't.created_at',
            2 => 't.created_at',
            3 => 't.noresi',
            4 => 't.lost_type',
            5 => 't.status_resi',
            6 => 't.kurir',
            7 => 't.nama_packer',
            8 => 'u.name',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list = $this->lost_scan_packer_fcd->get_data($data, $start_date, $end_date, $lost_type);
        $total = $this->lost_scan_packer_fcd->get_total_data($data, $start_date, $end_date, $lost_type);

        $i = $data['start'] + 1;
        $table_data = array();
        foreach ($list->result() as $row) {
            $created_at = !empty($row->created_at) ? strtotime($row->created_at) : false;
            $row_data = array(
                $i++ . '.',
                $created_at ? date('Y-m-d', $created_at) : '-',
                $created_at ? date('H:i:s', $created_at) : '-',
                $row->noresi,
                $row->lost_type,
                $row->status_resi,
                $row->kurir,
                $row->nama_packer,
                $row->nama_pelapor,
            );

            if ($this->data['user']['hakakses'] == 1) {
                $row_data[] = $row->id_lostscanpacker;
            }

            $table_data[] = $row_data;
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $table_data
        );
        echo json_encode($output);
        exit();
    }

    public function delete()
    {
        if ($this->data['user']['hakakses'] != 1) {
            $this->make_ajax_response(403, 'Anda tidak memiliki hak akses untuk menghapus data');
        }

        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $id = $this->input->post('id');
        if (empty($id)) {
            $this->make_ajax_response(400, 'ID Data tidak valid');
        }

        $delete = $this->lost_scan_packer_fcd->delete($id);

        if ($delete > 0) {
            $this->make_ajax_response(200, 'Data berhasil dihapus', ['status' => 200]);
        } else {
            $this->make_ajax_response(400, 'Gagal menghapus data / Data tidak ditemukan');
        }
    }

    public function export_excel()
    {
        ini_set('memory_limit', '-1');
        
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $lost_type = $this->input->get('lost_type') ?: 'PACKER';

        $data['reportrange'] = $start_date . ' - ' . $end_date;
        $data['type'] = strtoupper($lost_type);
        $data['list_data'] = $this->lost_scan_packer_fcd->get_data(['order' => null, 'search' => null], $start_date, $end_date, $lost_type)->result_array();

        $filename = "Laporan_Lost_Scan_" . ucfirst(strtolower($lost_type)) . ".xls";

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=$filename");

        $this->load->view('template_report/lost_scan_packer_excel', $data);
    }
}
