<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_return extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('qc_return_fcd');
        $this->load->model('Notification');
    }

    public function index()
    {
        $data['message'] = $this->session->flashdata('message');
        $this->show($data);
    }

    public function get_data()
    {
        $filters = array(
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'status' => $this->input->post('status'),
            'kondisi' => $this->input->post('kondisi')
        );

        $list = $this->qc_return_fcd->get_data($filters);
        $data = array();
        $no = 1;
        foreach ($list->result() as $row) {
            $action = '';
            // Change 'role' to 'hakakses' as per session data in Login controller
            $user_role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
            if ($row->status == 'PENDING' || $user_role == '1') { // Admin or Pending can be deleted
                $action = '<button class="btn btn-danger btn-xs" onclick="deleteData(' . $row->id_pengembalian . ')"><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                $no++,
                $row->tanggal,
                $row->sku,
                $row->qty . ($row->qty_kurang > 0 ? " <br><small class='text-danger'>Kurang: " . $row->qty_kurang . "</small>" : ""),
                $row->no_rak,
                $row->kondisi . ($row->keterangan_reject ? ' ('. $row->keterangan_reject .')' : ''),
                $row->submitter_name,
                $row->status,
                $row->acc_name ?: '-',
                $action
            );
        }

        // Ensure no output buffer content interferes with JSON
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array("data" => $data));
        exit();
    }

    public function add()
    {
        $this->show();
    }

    public function save()
    {
        $data = array(
            'tanggal' => date('Y-m-d'),
            'sku' => $this->input->post('sku'),
            'qty' => $this->input->post('qty'),
            'qty_kurang' => $this->input->post('ketersediaan') == 'Ada yang kurang' ? $this->input->post('qty_kurang') : 0,
            'no_rak' => $this->input->post('no_rak'),
            'kondisi' => $this->input->post('kondisi'),
            'keterangan_reject' => $this->input->post('keterangan_reject'),
            'submit_by' => $this->data['user']['id_user'],
            'status' => 'PENDING'
        );

        if ($this->qc_return_fcd->save($data)) {
            $this->set_message('Success', 'Data pengembalian berhasil diajukan', 'information');
        } else {
            $this->set_message('Error', 'Gagal menyimpan data', 'error');
        }

        redirect('qc_return');
    }

    public function approval()
    {
        // Only allow Tim Support (Role matches name or ID) or Admin
        // This is a simple check, usually handled by MY_Controller or Access filter
        $data['message'] = $this->session->flashdata('message');
        $this->show($data);
    }

    public function get_data_approval()
    {
        $filters = array('status' => 'PENDING');
        $list = $this->qc_return_fcd->get_data($filters);
        $data = array();
        $no = 1;
        foreach ($list->result() as $row) {
            $action = '<button class="btn btn-success btn-xs" onclick="approve(' . $row->id_pengembalian . ')" title="Terima"><i class="fa fa-check"></i> Acc</button> ';
            $action .= '<button class="btn btn-danger btn-xs" onclick="reject(' . $row->id_pengembalian . ')" title="Tolak"><i class="fa fa-times"></i> Tolak</button> ';
            $action .= '<button class="btn btn-warning btn-xs" onclick="giveaway(' . $row->id_pengembalian . ')" title="Giveaway"><i class="fa fa-gift"></i> Giveaway</button> ';
            $action .= '<button class="btn btn-info btn-xs" onclick="tidakAda(' . $row->id_pengembalian . ')" title="Barang Tidak Ada"><i class="fa fa-search-minus"></i> Tidak Ada</button>';
            
            $data[] = array(
                $no++,
                $row->tanggal,
                $row->sku,
                $row->qty . ($row->qty_kurang > 0 ? " <br><small class='text-danger'>Kurang: " . $row->qty_kurang . "</small>" : ""),
                $row->no_rak,
                $row->kondisi . ($row->keterangan_reject ? ' ('. $row->keterangan_reject .')' : ''),
                $row->submitter_name,
                $action
            );
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array("data" => $data));
        exit();
    }

    public function do_approve()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status'); // APPROVED or REJECTED
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->approve($id, $this->data['user']['id_user'], $status)) {
            // KIRIM NOTIFIKASI KE TIM PURCHASING
            $pesan = "Update status pengembalian QC: " . $status . " untuk ID #" . $id;
            $this->Notification->send($pesan, 'TIM PURCHASING', 'Update QC Return');

            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }
    public function do_approve_all()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->approve_all($this->data['user']['id_user'])) {
            // KIRIM NOTIFIKASI KE TIM PURCHASING
            $this->Notification->send("Seluruh data pengembalian QC PENDING telah disetujui.", 'TIM PURCHASING', 'Bulk Approval QC');

            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }

    public function get_sku_suggestions()
    {
        $search = $this->input->get('q');
        $suggestions = $this->qc_return_fcd->get_sku_suggestions($search);
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array('results' => $suggestions));
        exit();
    }

    public function get_sku_detail()
    {
        $sku = $this->input->post('sku');
        $detail = $this->qc_return_fcd->get_sku_detail($sku);
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($detail);
        exit();
    }

    public function export_to_excel()
    {
        ini_set('memory_limit', '-1');
        
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');
        $kondisi = $this->input->get('kondisi');

        if (empty($start_date)) $start_date = date('Y-m-d');
        if (empty($end_date)) $end_date = date('Y-m-d');

        $data['list_data'] = $this->qc_return_fcd->get_data_export($start_date, $end_date, $status, $kondisi)->result_array();
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Pengembalian_QC_" . $start_date . "_to_" . $end_date . ".xls");

        $this->load->view('template_report/qc_return_report', $data);
    }

    public function delete()
    {
        $id = $this->input->post('id');
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->delete_data($id)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }
}
