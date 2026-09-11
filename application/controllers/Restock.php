<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Restock extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('restock_fcd');
    }

    /**
     * Display laporan masalah picker page
     */
    public function laporan_masalah_picker()
    {
        $data['message'] = $this->session->flashdata('message');

        // Default date range: today
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        // Handle AJAX request
        if ($this->input->is_ajax_request() || $this->input->get('ajax')) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'view' => $this->load->view('restock/laporan_masalah_picker', $data, TRUE),
                'message' => empty($data['message']) ? null : $data['message'],
            ));
            exit;
        } else {
            $this->show($data);
        }
    }

    /**
     * Get masalah picker data for DataTables (AJAX endpoint)
     */
    public function get_laporan_masalah_picker_data()
    {
        // Get date range from POST
        $reportrange = $this->input->post('reportrange');

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Get DataTables parameters
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $params = array();
        $params['start'] = intval($this->input->post('start'));
        $params['length'] = intval($this->input->post('length'));
        $params['search'] = $this->input->post('search')['value'] ?? '';
        $params['start_date'] = $start_date;
        $params['end_date'] = $end_date;

        // Order configuration
        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $params['dir'] = $dir;
        $params['valid_columns'] = [
            0 => 'peg_picker.nama_pegawai',
            1 => 'peg.nama_pegawai',
            2 => 'mp.noresi',
            3 => 'mp.sku',
            4 => 'mp.sku_salah',
            5 => 'mp.qty',
            6 => 'mp.qty_bermasalah',
            7 => 'tm.type_masalah',
            8 => 'mp.created',
        ];
        $params['order'] = isset($params['valid_columns'][$col]) ? $params['valid_columns'][$col] : 'mp.created';

        // Get data
        $query = $this->restock_fcd->get_masalah_picker_data($params);
        $items = $query->result_array();

        // Get total count
        $total = $this->restock_fcd->get_masalah_picker_data_count($params);

        // Build table data
        $data_table = [];

        foreach ($items as $item) {
            $action_buttons = '<button class="btn btn-warning btn-xs btn-kembalikan" data-id="' . $item['id_masalahpicker'] . '" title="Kembalikan ke Daftar Masalah Picker" style="margin-right: 5px;"><i class="fa fa-undo"></i></button>' .
                              '<button class="btn btn-danger btn-xs btn-hapus" data-id="' . $item['id_masalahpicker'] . '" title="Hapus Laporan"><i class="fa fa-trash"></i></button>';
            $data_table[] = [
                $item['nama_picker'] ?? '-',
                $item['nama_packer'] ?? '-',
                $item['noresi'] ?? '-',
                $item['sku'] ?? '-',
                $item['sku_salah'] ?? '-',
                $item['qty'] ?? 0,
                $item['qty_bermasalah'] ?? 0,
                $item['type_masalah'] ?? '-',
                !empty($item['created']) ? date('d/m/Y H:i', strtotime($item['created'])) : '-',
                $action_buttons
            ];
        }

        $output = [
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data_table
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
        exit();
    }

    /**
     * Hapus laporan masalah picker (AJAX endpoint)
     */
    public function hapus_masalah_picker()
    {
        if ($this->input->method() == 'post' && $this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            if ($id) {
                $this->db->where('id_masalahpicker', $id);
                $this->db->delete('tblmasalahpicker');
                
                if ($this->db->affected_rows() > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dihapus']);
                    return;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus laporan']);
    }

    /**
     * Kembalikan laporan masalah picker ke daftar masalah (AJAX endpoint)
     */
    public function kembalikan_masalah_picker()
    {
        if ($this->input->method() == 'post' && $this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            if ($id) {
                $this->db->where('id_masalahpicker', $id);
                $this->db->update('tblmasalahpicker', [
                    'status' => 0, 
                    'updated_by' => $this->data['user']['id_user'] ?? null, 
                    'updated' => date('Y-m-d H:i:s')
                ]);
                
                if ($this->db->affected_rows() > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dikembalikan ke daftar masalah picker']);
                    return;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengembalikan laporan']);
    }

    /**
     * Get detail masalah picker (AJAX endpoint)
     */
    public function get_detail_masalah_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
            exit();
        }

        $id = $this->input->post('id');

        if (empty($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
            exit();
        }

        $detail = $this->restock_fcd->get_masalah_picker_detail($id);

        if (empty($detail)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            exit();
        }

        // Build HTML for modal content
        $html = '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><th style="width: 150px;">No. Resi</th><td>' . htmlspecialchars($detail->noresi ?? '-') . '</td></tr>';
        $html .= '<tr><th>SKU</th><td>' . htmlspecialchars($detail->sku ?? '-') . '</td></tr>';
        $html .= '<tr><th>Nama Barang</th><td>' . htmlspecialchars($detail->nama_barang ?? '-') . '</td></tr>';
        $html .= '<tr><th>Qty</th><td>' . htmlspecialchars($detail->qty ?? 0) . '</td></tr>';
        $html .= '<tr><th>Qty Bermasalah</th><td>' . htmlspecialchars($detail->qty_bermasalah ?? 0) . '</td></tr>';
        $html .= '<tr><th>No. Rak</th><td>' . htmlspecialchars($detail->no_rak ?? '-') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-6">';
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><th style="width: 150px;">Tipe Masalah</th><td>' . htmlspecialchars($detail->type_masalah ?? '-') . '</td></tr>';
        $html .= '<tr><th>Nama Picker</th><td>' . htmlspecialchars($detail->nama_picker ?? '-') . '</td></tr>';
        $html .= '<tr><th>Nama Packer / Pelapor</th><td>' . htmlspecialchars($detail->nama_packer ?? '-') . '</td></tr>';
        
        if (!empty($detail->sku_salah)) {
            $html .= '<tr><th>SKU Salah</th><td>' . htmlspecialchars($detail->sku_salah) . '</td></tr>';
        }
        
        $html .= '<tr><th>Tanggal Dibuat</th><td>' . (!empty($detail->created) ? date('d/m/Y H:i:s', strtotime($detail->created)) : '-') . '</td></tr>';
        
        if (!empty($detail->updated)) {
            $html .= '<tr><th>Tanggal Update</th><td>' . date('d/m/Y H:i:s', strtotime($detail->updated)) . '</td></tr>';
        }
        
        $html .= '</table>';
        
        // Show product image if available
        if (!empty($detail->link_foto)) {
            $html .= '<div class="text-center" style="margin-top: 15px;">';
            $html .= '<img src="' . htmlspecialchars($detail->link_foto) . '" class="img-thumbnail" style="max-width: 200px;">';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
        exit();
    }

    /**
     * Export laporan masalah picker to Excel
     */
    public function export_laporan_masalah_picker()
    {
        // Get date range from GET
        $reportrange = $this->input->get('reportrange');

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Prepare parameters for export (no pagination)
        $params = array();
        $params['start'] = 0;
        $params['length'] = 0; // 0 means no limit
        $params['search'] = '';
        $params['start_date'] = $start_date;
        $params['end_date'] = $end_date;
        $params['order'] = 'peg_picker.nama_pegawai';
        $params['dir'] = 'ASC';

        // Get all data
        $query = $this->restock_fcd->get_masalah_picker_data($params);
        $data['list_data'] = $query->result_array();
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // Set headers for Excel download
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Masalah_Picker_" . date('Y-m-d_His') . ".xls");

        // Load view for Excel export
        $this->load->view('template_report/laporan_masalah_picker', $data);
    }

    public function laporan_reject_display()
    {
        $data['title'] = 'Laporan Reject Display';
        $data['reportrange'] = $this->input->post('reportrange');

        $this->show($data);
    }

    public function get_laporan_reject_display_data()
    {
        $reportrange = $this->input->post('reportrange');
        $start_date = null;
        $end_date = null;

        if (!empty($reportrange)) {
            $dates = explode(' - ', $reportrange);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date = $dates[1];
            }
        }

        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'];
        $order = $this->input->post('order');

        $col = 0;
        $dir = 'desc';
        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        $valid_columns = array(
            0 => 'nama_picker',
            1 => 'nama_packer',
            2 => 'mp.noresi',
            3 => 'mp.sku',
            4 => 'mp.sku_salah',
            5 => 'mp.qty',
            6 => 'mp.qty_bermasalah',
            7 => 'tm.type_masalah',
            8 => 'mp.created',
            9 => null
        );

        $params = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start' => $start,
            'length' => $length,
            'search' => $search,
            'order' => $valid_columns[$col] ?? 'mp.created',
            'dir' => $dir
        );

        $list = $this->restock_fcd->get_reject_display_data($params);
        $total = $this->restock_fcd->get_reject_display_data_count($params);

        $data = array();
        foreach ($list->result_array() as $item) {
            $action = '<button class="btn btn-info btn-xs btn-detail" data-id="' . $item['id_masalahpicker'] . '" title="Detail"><i class="fa fa-eye"></i></button>';
            $action .= ' <button class="btn btn-warning btn-xs btn-kembalikan" data-id="' . $item['id_masalahpicker'] . '" title="Kembalikan ke CS"><i class="fa fa-refresh"></i></button>';
            $action .= ' <button class="btn btn-danger btn-xs btn-hapus" data-id="' . $item['id_masalahpicker'] . '" title="Hapus"><i class="fa fa-trash-o"></i></button>';

            $data[] = array(
                $item['nama_picker'] ?? '-',
                $item['nama_packer'] ?? '-',
                $item['noresi'] ?? '-',
                $item['sku'] ?? '-',
                $item['sku_salah'] ?? '-',
                $item['qty'] ?? 0,
                $item['qty_bermasalah'] ?? 0,
                $item['type_masalah'] ?? '-',
                !empty($item['created']) ? date('d/m/Y H:i:s', strtotime($item['created'])) : '-',
                $action
            );
        }

        echo json_encode(array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        ));
    }

    public function export_laporan_reject_display()
    {
        $reportrange = $this->input->get('reportrange');
        $start_date = null;
        $end_date = null;

        if (!empty($reportrange)) {
            $dates = explode(' - ', $reportrange);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date = $dates[1];
            }
        }

        $params = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start' => 0,
            'length' => -1,
            'search' => '',
            'order' => 'mp.created',
            'dir' => 'desc'
        );

        $list = $this->restock_fcd->get_reject_display_data($params)->result_array();

        $data['list_data'] = $list;
        $data['reportrange'] = $reportrange;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Reject_Display_" . date('YmdHis') . ".xls");

        $this->load->view('template_report/laporan_masalah_picker', $data);
    }

    public function laporan_retur_display()
    {
        $this->data['title'] = 'Laporan Retur Display';
        $this->show($this->data);
    }

    public function get_display_batches()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $reportrange = $this->input->post('reportrange');
        $start_date = null;
        $end_date = null;

        if (!empty($reportrange)) {
            $dates = explode(' s/d ', $reportrange);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date = $dates[1];
            }
        }

        $col = 0;
        $dir = 'desc';
        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        $valid_columns = array(
            0 => 'b.kode_batch',
            1 => 'b.created_at',
            2 => 'uc.name',
            3 => 'b.total_qty',
            4 => 'b.status',
            5 => 'ur.name',
            6 => 'b.received_at'
        );

        $params = array(
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'start'      => $data['start'],
            'length'     => $data['length'],
            'search'     => $data['search'],
            'order'      => $valid_columns[$col] ?? 'b.created_at',
            'dir'        => $dir
        );

        $list = $this->restock_fcd->get_display_batches_data($params);
        $total = $this->restock_fcd->get_display_batches_count($params);

        $result_data = array();
        foreach ($list->result_array() as $row) {
            $id = $row['id_batch'];
            
            // Action buttons
            $action = '<button class="btn btn-info btn-xs btn-detail" data-id="' . $id . '" data-code="' . $row['kode_batch'] . '" title="Lihat Detail"><i class="fa fa-eye"></i> Detail</button>';
            $action .= ' <button class="btn btn-default btn-xs btn-print" data-id="' . $id . '" title="Cetak Slip"><i class="fa fa-print"></i> Cetak</button>';
            
            if ($row['status'] === 'DIKIRIM') {
                $action .= ' <button class="btn btn-success btn-xs btn-terima" data-id="' . $id . '" data-code="' . $row['kode_batch'] . '" title="Konfirmasi Terima"><i class="fa fa-check"></i> Terima</button>';
                $status = '<span class="label label-warning">DIKIRIM</span>';
            } else {
                $status = '<span class="label label-success">DITERIMA</span>';
            }

            $result_data[] = array(
                $row['kode_batch'],
                !empty($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-',
                $row['creator_name'],
                $row['total_qty'],
                $status,
                $row['receiver_name'],
                !empty($row['received_at']) ? date('d/m/Y H:i:s', strtotime($row['received_at'])) : '-',
                $action
            );
        }

        $output = array(
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "data"            => $result_data
        );
        echo json_encode($output);
        exit();
    }

    public function get_display_batch_details()
    {
        $batch_id = intval($this->input->post('batch_id'));
        if (empty($batch_id)) {
            $batch_id = intval($this->input->get('batch_id'));
        }

        if (empty($batch_id)) {
            echo json_encode([]);
            exit();
        }

        $details = $this->restock_fcd->get_batch_details($batch_id);
        echo json_encode($details);
        exit();
    }

    public function confirm_display_batch()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Request method must be POST');
        }

        $batch_id = intval($this->input->post('batch_id'));
        if (empty($batch_id)) {
            $this->make_ajax_response(400, 'Batch ID tidak boleh kosong');
        }

        // Check current batch status first
        $batch = $this->db->get_where('tblretur_display_batch', ['id_batch' => $batch_id])->row_array();
        if (!$batch) {
            $this->make_ajax_response(404, 'Batch tidak ditemukan');
        }

        if ($batch['status'] === 'DITERIMA') {
            $this->make_ajax_response(400, 'Batch ini sudah dikonfirmasi terima sebelumnya.');
        }

        $user_id = $this->data['user']['id_user'];
        $res = $this->restock_fcd->confirm_batch_received($batch_id, $user_id);

        if (!$res) {
            $this->make_ajax_response(500, 'Gagal mengkonfirmasi penerimaan batch di database');
        }

        $this->make_ajax_response(200, 'Batch display ' . $batch['kode_batch'] . ' berhasil dikonfirmasi dan telah masuk stok display!');
    }

    public function print_retur_display_batch()
    {
        $batch_id = intval($this->input->get('batch_id'));
        if (empty($batch_id)) {
            show_error('Batch ID is required.', 400);
        }

        $batch = $this->db
            ->select("
                b.*,
                COALESCE(peg_c.nama_pegawai, uc.name) as creator_name,
                COALESCE(peg_r.nama_pegawai, ur.name, '-') as receiver_name
            ")
            ->from('tblretur_display_batch b')
            ->join('tbluser uc', 'uc.id_user = b.created_by', 'left')
            ->join('tblpegawai peg_c', 'peg_c.kode_pegawai = uc.id_pegawai', 'left')
            ->join('tbluser ur', 'ur.id_user = b.received_by', 'left')
            ->join('tblpegawai peg_r', 'peg_r.kode_pegawai = ur.id_pegawai', 'left')
            ->where('b.id_batch', $batch_id)
            ->get()
            ->row_array();

        if (!$batch) {
            show_404();
        }

        $data['batch'] = $batch;
        $data['items'] = $this->restock_fcd->get_batch_details($batch_id);

        $this->load->view('restock/print_retur_display', $data);
    }
}

