<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Receipt extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('receipt_fcd');
        $this->load->model('marketplace_fcd');
        $this->load->model('courrier_fcd');
        $this->load->model('receipt_reprint_fcd');
        $this->load->model('param_fcd');
        $this->load->model('picking_fcd');
        $this->load->model('user_fcd');
        $this->load->model('resi_team_fcd');
    }

    public function scan_receipt()
    {
        $data['list_marketplace'] = $this->marketplace_fcd->get_marketplace()->result_array();
        $data['list_courrier'] = $this->courrier_fcd->get_courrier()->result_array();
        $data['total_scan'] = $this->receipt_fcd->get_total_scan_user($this->data['user']['id_user'])->row()->total_scan;
        $this->show($data);
    }

    public function save_receipt()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $receipt['id_marketplace'] = $this->input->post('id_marketplace');
        $receipt['id_kurir'] = $this->input->post('id_kurir');
        $receipt['nomorpicklist'] = trim($this->input->post('nomorpicklist'));
        $receipt['noresi'] = trim($this->input->post('noresi'));

        $save = $this->receipt_fcd->save($receipt, $this->data['user']['id_user']);

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA, ['id_printresi' => $save['id_printresi']]);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

    public function print_label($id_resi)
    {
        $data['receipt'] = $this->receipt_fcd->get_detail_by_id($id_resi)->row_array();
        if (empty($data['receipt'])) {
            show_404();
        }
        $this->load->view('receipt/print_label', $data);
    }

    public function detail_receipt()
    {
        $data = [];

        if ($this->input->method() == 'post') {
            $keyword = trim($this->input->post('noresi'));

            $data['noresi'] = $keyword;

            // Dukung input berupa nomor resi maupun hasil scan nomor pesanan.
            $noresi = $this->receipt_fcd->resolve_noresi($keyword);

            if (!empty($noresi)) {
                $data['receipt'] = $this->receipt_fcd->get_detail($noresi)->row_array();
                $data['receipt_items'] = $this->receipt_fcd->get_detail_items($noresi)->result_array();
            }
        }

        $this->show($data);
    }

    public function list_receipt()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_list_receipt_data()
    {
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
            1 => 't.noresi',
            2 => 't.created_at',
            3 => 't3.nama_kurir',
            4 => 't2.nama_marketplace',
            5 => 't.toko',
            6 => 't.nomorpicklist',
            7 => 't.status_pesanan',
            8 => null // Action column
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data($data);

        $total = $this->receipt_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                date('Y-m-d H:i:s', strtotime($row->tanggal_printresi)),
                $row->nama_kurir,
                $row->nama_marketplace,
                $row->toko,
                $row->nomorpicklist,
                $row->status_pesanan,
                '<a href="receipt/delete-list-receipt-data/' . $row->id_printresi . '" class="btn btn-danger confirm" onClick="notyConfirm(event);"><i class="fa fa-trash-o"></i> </a>',
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

    public function delete_list_receipt_data($id_printresi)
    {
        $save = $this->receipt_fcd->destroy($id_printresi, $this->data['user']['id_user']);

        if ($save['affected_rows'] > 0) {
            $this->session->set_flashdata('noty_message', [
                'text' => 'Data berhasil dihapus.',
                'type' => 'success' // Noty supports: alert, success, error, warning, info
            ]);
            //$this->set_message('Success', SUCCESS_REMOVE_DATA, 'information');
        } else {
            $this->session->set_flashdata('noty_message', [
                'text' => 'Tidak ada data yang dihapus.',
                'type' => 'warning'
            ]);
            //$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
        }

        redirect('receipt/list_receipt');
    }

    public function print_pergantian_barang()
    {
        $noresi = $this->input->get('noresi');
        $sku = $this->input->get('sku');
        $qty = $this->input->get('qty');

        // Cari informasi SKU dan Nomor Pesanan jika memungkinkan
        $this->db->select('dr.no_pesanan');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->where('pr.noresi', $noresi);
        $resi_data = $this->db->get()->row_array();

        // Cari lokasi rak terbaru dari SKU tersebut jika ada
        $this->db->select('no_rak');
        $this->db->from('tblsku');
        $this->db->where('id_sku', $sku);
        $this->db->limit(1);
        $rak_data = $this->db->get()->row_array();

        $data['no_pesanan'] = $resi_data['no_pesanan'] ?? '-';
        $data['noresi'] = $noresi;
        $data['sku'] = $sku;
        $data['qty'] = $qty;
        $data['no_rak'] = $rak_data['no_rak'] ?? 'BELUM DITENTUKAN';

        $this->load->view('receipt/print_pergantian_barang', $data);
    }

    public function delete_receipt()
    {
        $data['list_marketplace'] = $this->marketplace_fcd->get_marketplace()->result_array();
        $data['list_courrier'] = $this->courrier_fcd->get_courrier()->result_array();
        $data['total_scan'] = $this->receipt_fcd->get_total_scan_user($this->data['user']['id_user'])->row()->total_scan;

        $this->show($data);
    }

    public function delete_receipt_action()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');

        $save = $this->receipt_fcd->destroy_by_noresi($noresi, $this->data['user']['id_user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

    public function reprint_receipt()
    {
        $data['list_reason'] = $this->param_fcd->get_param_by_group('REPRINT_RECEIPT_REASON')->result_array();

        $this->show($data);
    }

    public function save_reprint_receipt()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $receipt['alasan'] = $this->input->post('alasan');
        $receipt['keterangan'] = $this->input->post('keterangan');
        $receipt['noresi'] = $this->input->post('noresi');

        if (!empty($_FILES['images']['name'][0])) {
            if (count($_FILES['images']['name']) > 5) {
                $this->set_message('Warning', '5 image max to upload', 'warning');
                $this->show_index();
            }

            $images = $this->upload_reprint_receipt_file($_FILES['images']);

            if (empty($images)) {
                $this->set_message('Error', $this->upload->display_errors(), 'danger');
                $this->show_index();
            }

            $receipt['image'] = implode(',', $images);
        }

        $save = $this->receipt_reprint_fcd->save($receipt, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function upload_reprint_receipt_file($files)
    {
        $config = array(
            'upload_path' => 'uploads/transaction/',
            'allowed_types' => 'jpg|gif|png',
            'overwrite' => 1,
        );

        $this->load->library('upload', $config);

        $images = array();

        $i = 1;
        $time = time();
        foreach ($files['name'] as $key => $image) {
            $_FILES['images[]']['name'] = $files['name'][$key];
            $_FILES['images[]']['type'] = $files['type'][$key];
            $_FILES['images[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['images[]']['error'] = $files['error'][$key];
            $_FILES['images[]']['size'] = $files['size'][$key];

            $fileExt = pathinfo($_FILES['images[]']['name'], PATHINFO_EXTENSION);
            $fileName = '_' . $time . '_' . $i++ . '.' . $fileExt;

            $images[] = $fileName;

            $config['file_name'] = $fileName;

            $this->upload->initialize($config);

            if ($this->upload->do_upload('images[]')) {
                $this->upload->data();
            } else {
                return null;
            }
        }

        return $images;
    }

    public function upload_receipt()
    {
        $this->show();
    }

    public function upload_receipt_action()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        if (!isset($_FILES['receiptFile'])) {
            $this->make_ajax_response(400, "Tidak ada file yang dipilih");
        }

        if ($_FILES['receiptFile']['error'] != 0) {
            $err = $_FILES['receiptFile']['error'];
            $msg = "Gagal mengunggah file. Kode error: $err";
            if ($err == 1 || $err == 2) $msg = "File terlalu besar. Batas maksimal server: " . ini_get('upload_max_filesize');
            if ($err == 3) $msg = "File hanya terunggah sebagian.";
            if ($err == 4) $msg = "Tidak ada file yang diunggah.";
            $this->make_ajax_response(500, $msg);
        }

        // Set proper limits for large file processing
        ini_set('memory_limit', '3072M'); // Increase memory limit
        ini_set('max_execution_time', 0); // Remove execution time limit
        // Buffer output to catch any unwanted echoes or warnings
        ob_start();

        // Log the start of processing
        log_message('info', 'Starting Excel upload processing for user: ' . ($this->data['user']['id_user'] ?? 'unknown'));

        try {
            // Set proper limits for large file processing (from v9)
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', 0);
            set_time_limit(0);

            $user_id = $this->data['user']['id_user'] ?? null;
            $file = $_FILES['receiptFile']['tmp_name'];

            // Identify and load the Excel file (supports both XLS and XLSX)
            $reader = IOFactory::createReader(IOFactory::identify($file));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();

            // Get all rows with Excel-style column keys (A, B, C, etc.)
            $dataRaw = $sheet->toArray(null, true, true, true);

            // Log row count
            $rowCount = count($dataRaw);
            log_message('info', "Processing Excel file with {$rowCount} rows");

            // Show progress for large files
            if ($rowCount > 1000) {
                // Set a session flag to indicate processing
                $this->session->set_userdata('upload_processing', true);
                $this->session->set_userdata('upload_start_time', time());
                $this->session->set_userdata('upload_row_count', $rowCount);
            }

            // Proses insert
            $result = $this->receipt_fcd->insert_receipt($dataRaw, $user_id);

            // Clear processing flag
            $this->session->unset_userdata('upload_processing');
            $this->session->unset_userdata('upload_start_time');
            $this->session->unset_userdata('upload_row_count');

            // Log completion
            log_message('info', "Excel upload completed: {$result}");

            // Set session flashdata for notification on next page load
            $this->session->set_flashdata('noty_message', [
                'text' => $result,
                'type' => 'success'
            ]);

            // Cleaning buffer before sending response
            if (ob_get_length()) ob_clean(); 

            $this->make_ajax_response(201, $result);

        } catch (Exception $e) {
            // Clear processing flag on error
            $this->session->unset_userdata('upload_processing');
            $this->session->unset_userdata('upload_start_time');
            $this->session->unset_userdata('upload_row_count');

            $error_message = "Error processing Excel file: " . $e->getMessage();
            log_message('error', $error_message);

            $this->session->set_flashdata('noty_message', [
                'text' => $error_message,
                'type' => 'error'
            ]);

            // Cleaning buffer before sending error response
            if (ob_get_length()) ob_clean();

            $this->make_ajax_response(500, $error_message);
        } finally {
            // Flush and stop buffering
            if (ob_get_length()) ob_end_flush();
        }
    }

    // Add a method to check upload progress
    public function check_upload_progress()
    {
        $processing = $this->session->userdata('upload_processing');
        $start_time = $this->session->userdata('upload_start_time');
        $row_count = $this->session->userdata('upload_row_count');

        if ($processing && $start_time) {
            $elapsed = time() - $start_time;
            $response = [
                'processing' => true,
                'elapsed_time' => $elapsed,
                'row_count' => $row_count,
                'estimated_time' => round($row_count / 100) // Rough estimate: 100 rows per second
            ];
        } else {
            $response = ['processing' => false];
        }

        echo json_encode($response);
    }

    public function scan_combined()
    {
        $data['title'] = 'SCAN COMBINED';
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        // Use User_fcd to get active users (for Packers)
        $data['list_packer'] = $this->user_fcd->get_user()->result_array();
        
        $this->show($data);
    }

    public function save_combined()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $id_picker = $this->input->post('id_picker');
        $id_packer = $this->input->post('id_packer');

        if (empty($noresi) || empty($id_picker) || empty($id_packer)) {
            $this->make_ajax_response(400, 'Nomor Resi, Picker dan Packer harus diisi.');
        }

        $result = $this->resi_team_fcd->save_combined_scan($noresi, $id_picker, $id_packer, $this->data['user']);

        if (isset($result['error']) && $result['error'] === TRUE) {
            $this->make_ajax_response($result['code'], $result['message']);
        }

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

}

