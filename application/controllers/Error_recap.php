<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Error_recap extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('error_recap_fcd');
        $this->check_access();
    }

    private function check_access()
    {
        $user = $this->session->userdata('user');
        if (!$user || !isset($user['id_user'])) {
            $this->session->set_flashdata('message', 'Access denied. Please login first.');
            redirect('welcome/restricted');
        }

        // Cek hakakses - hanya admin (hakakses = 1) dan webmaster/manager (hakakses = 2)
        if (!isset($user['hakakses']) || !in_array($user['hakakses'], [1, 2])) {
            $this->session->set_flashdata('message', 'Access denied. Only admin and managers can access Error Recap.');
            redirect('welcome/restricted');
        }
    }

    public function index()
    {
        // Load menu helper
        $this->load->helper('menu_helper');

        $data['message'] = $this->session->flashdata('message');
        $data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        $data['types_packer'] = $this->error_recap_fcd->get_typemasalah_packer();
        $data['recent_errors'] = $this->error_recap_fcd->get_recent_packer_errors(10);

        if ($this->input->is_ajax_request() || $this->input->get('ajax')) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'view' => $this->load->view('error_recap_index', $data, TRUE),
                    'message' => empty($data['message']) ? null : $data['message'],
                )));
        } else {
            $this->data['user'] = $this->session->userdata('user');
            $this->data['nama_pk'] = $this->session->userdata('nama_pk');
            $this->data['status_performa'] = $this->session->userdata('status_performa');
            $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
            $this->data['content'] = $this->load->view('error_recap_index', $data, TRUE);

            $this->load->view('main', $this->data);
        }
    }

    public function get_data_picker()
    {
        $reportrange = $this->input->post('reportrange');
        $period = $this->input->post('period'); // daily, weekly, monthly

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }
        if (empty($period)) {
            $period = 'daily';
        }

        $dates = explode(' - ', $reportrange);
        $start_date = isset($dates[0]) ? $dates[0] : date('Y-m-d 00:00:00');
        $end_date = isset($dates[1]) ? $dates[1] : date('Y-m-d 23:59:59');

        $result = $this->error_recap_fcd->get_picker_recap($start_date, $end_date, $period);

        // Format data for datatable
        $data = [];
        $no = 1;
        foreach ($result as $row) {
            $total_resi = (int)$row['total_resi'];
            $total_kesalahan = (int)$row['total_kesalahan'];
            $error_rate = $total_resi > 0 ? round(($total_kesalahan / $total_resi) * 100, 2) : 0;

            $data[] = [
                'no' => $no++,
                'nama' => $row['nama'],
                'periode' => $row['periode'],
                'total_resi' => $total_resi,
                'total_sku' => (int)$row['total_sku'],
                'total_qty' => (int)$row['total_qty'],
                'total_kesalahan' => $total_kesalahan,
                'total_qty_salah' => (int)$row['total_qty_salah'],
                'error_rate' => $error_rate . '%',
                'breakdown' => sprintf(
                    "<small>Tidak Ambil: %d<br>Lebih Ambil: %d<br>Kurang Ambil: %d<br>Salah Ambil: %d<br>Reject Display: %d</small>",
                    $row['salah_1'], $row['salah_2'], $row['salah_3'], $row['salah_4'], $row['salah_5']
                )
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'draw' => intval($this->input->post('draw')),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }

    public function get_data_packer()
    {
        $reportrange = $this->input->post('reportrange');
        $period = $this->input->post('period'); // daily, weekly, monthly

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }
        if (empty($period)) {
            $period = 'daily';
        }

        $dates = explode(' - ', $reportrange);
        $start_date = isset($dates[0]) ? $dates[0] : date('Y-m-d 00:00:00');
        $end_date = isset($dates[1]) ? $dates[1] : date('Y-m-d 23:59:59');

        $result = $this->error_recap_fcd->get_packer_recap($start_date, $end_date, $period);

        // Format data for datatable
        $data = [];
        $no = 1;
        foreach ($result as $row) {
            $total_resi = (int)$row['total_resi'];
            $total_kesalahan = (int)$row['total_kesalahan'];
            $error_rate = $total_resi > 0 ? round(($total_kesalahan / $total_resi) * 100, 2) : 0;

            $data[] = [
                'no' => $no++,
                'nama' => $row['nama'],
                'periode' => $row['periode'],
                'total_resi' => $total_resi,
                'total_sku' => (int)$row['total_sku'],
                'total_qty' => (int)$row['total_qty'],
                'total_kesalahan' => $total_kesalahan,
                'total_qty_salah' => (int)$row['total_qty_salah'],
                'error_rate' => $error_rate . '%',
                'breakdown' => sprintf(
                    "<small>Packing Tdk Sesuai: %d<br>Salah Barang: %d<br>Salah Resi: %d<br>Abaikan Catatan: %d<br>Melewati QC: %d</small>",
                    $row['salah_1'], $row['salah_2'], $row['salah_3'], $row['salah_4'], $row['salah_5']
                )
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'draw' => intval($this->input->post('draw')),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }

    public function get_receipt_details()
    {
        $noresi = $this->input->post('noresi');
        if (empty($noresi)) {
            echo json_encode(['status' => 'error', 'message' => 'Nomor resi kosong']);
            return;
        }

        $details = $this->error_recap_fcd->get_receipt_detail_by_noresi($noresi);
        if (empty($details)) {
            echo json_encode(['status' => 'error', 'message' => 'Resi tidak ditemukan atau belum di-packing']);
            return;
        }

        echo json_encode(['status' => 'success', 'data' => $details]);
    }

    public function save_packer_error()
    {
        $user = $this->session->userdata('user');
        
        $data = [
            'noresi' => $this->input->post('noresi'),
            'sku' => $this->input->post('sku'),
            'qty' => $this->input->post('qty'),
            'id_typemasalahpacker' => $this->input->post('id_typemasalahpacker'),
            'qty_bermasalah' => $this->input->post('qty_bermasalah'),
            'sku_salah' => $this->input->post('sku_salah'),
            'keterangan' => $this->input->post('keterangan')
        ];

        $res = $this->error_recap_fcd->save_masalah_packer($data, $user);
        echo json_encode($res);
    }

    public function export_excel()
    {
        $reportrange = $this->input->get('reportrange');
        $period = $this->input->get('period');
        $type = $this->input->get('type'); // picker or packer

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }
        if (empty($period)) {
            $period = 'daily';
        }

        $dates = explode(' - ', $reportrange);
        $start_date = isset($dates[0]) ? $dates[0] : date('Y-m-d 00:00:00');
        $end_date = isset($dates[1]) ? $dates[1] : date('Y-m-d 23:59:59');

        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Rekap_Kesalahan_" . ucfirst($type) . "_" . date('Ymd_His') . ".xls");

        if ($type == 'packer') {
            $data['recap_data'] = $this->error_recap_fcd->get_packer_recap($start_date, $end_date, $period);
            $data['type'] = 'Packer';
            $data['headers'] = ['Packing Tidak Sesuai', 'Salah Barang', 'Salah Resi', 'Mengabaikan Catatan', 'Melewati QC'];
        } else {
            $data['recap_data'] = $this->error_recap_fcd->get_picker_recap($start_date, $end_date, $period);
            $data['type'] = 'Picker';
            $data['headers'] = ['Tidak Ambil', 'Lebih Ambil', 'Kurang Ambil', 'Salah Ambil', 'Reject Display'];
        }

        $data['period'] = $period;
        $data['reportrange'] = $reportrange;

        $this->load->view('template_report/error_recap_excel', $data);
    }
}
