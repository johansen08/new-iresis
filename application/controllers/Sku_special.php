<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sku_special extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sku_special_fcd');
    }

    public function index()
    {
        $this->load->model('picking_fcd');
        $this->load->model('kpi_fcd');

        $data['title'] = 'Master Special SKU';
        $data['message'] = $this->session->flashdata('message');
        
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        $data['list_status_performa'] = $this->kpi_fcd->get_status_performa()->result_array();
        $data['current_assignment'] = $this->sku_special_fcd->get_special_assignment();

        $this->show($data);
    }

    public function get_skus_data()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');
        
        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $valid_columns = [
            0 => 'id_sku',
            1 => 'nama_sku',
            2 => 'is_special'
        ];

        $col = 0;
        $dir = 'desc';
        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        $data['order'] = $valid_columns[$col] ?? 'id_sku';
        $data['dir'] = $dir;

        $query = $this->sku_special_fcd->get_skus_data($data);
        $total = $this->sku_special_fcd->count_all_skus($data);

        $result = [];
        foreach ($query->result() as $row) {
            $status_html = '
                <select class="form-control select-is-special" data-id="'.$row->id_sku.'">
                    <option value="0" '.($row->is_special == 0 ? 'selected' : '').'>NON SPECIAL</option>
                    <option value="1" '.($row->is_special == 1 ? 'selected' : '').'>SPECIAL</option>
                </select>
            ';

            $result[] = [
                $row->id_sku,
                $row->nama_sku,
                $status_html
            ];
        }

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $result
        ]);
        exit();
    }

    public function update_status()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_sku = $this->input->post('id_sku');
        $is_special = $this->input->post('is_special');

        if ($this->sku_special_fcd->update_is_special($id_sku, $is_special)) {
            $this->make_ajax_response(200, 'Status updated successfully');
        } else {
            $this->make_ajax_response(500, 'Failed to update status');
        }
    }

    public function reset_all()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        if ($this->sku_special_fcd->reset_all_special()) {
            $this->make_ajax_response(200, 'Berhasil merubah semua SKU special menjadi non-special');
        } else {
            $this->make_ajax_response(500, 'Gagal merubah status SKU');
        }
    }

    public function undo_reset_all()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        if ($this->sku_special_fcd->undo_reset_all_special()) {
            $this->make_ajax_response(200, 'Berhasil mengembalikan (undo) status SKU special sebelumnya');
        } else {
            $this->make_ajax_response(500, 'Gagal mengembalikan status SKU');
        }
    }

    public function save_daily_assignment()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $data = [
            'tanggal' => date('Y-m-d'),
            'id_pegawaipicker' => $this->input->post('id_pegawaipicker'),
            'status_performa_id' => $this->input->post('status_performa_id'),
        ];

        if ($this->sku_special_fcd->save_special_assignment($data)) {
            $this->make_ajax_response(200, 'Penugasan picker special berhasil disimpan untuk hari ini');
        } else {
            $this->make_ajax_response(500, 'Gagal menyimpan penugasan picker');
        }
    }

    public function analyze()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $updated_count = $this->sku_special_fcd->analyze_and_set_special();

        if ($updated_count > 0) {
            $this->make_ajax_response(200, 'Berhasil menganalisa. ' . $updated_count . ' SKU diubah menjadi special.');
        } else {
            $this->make_ajax_response(200, 'Analisa selesai, tidak ada SKU baru yang memenuhi kriteria untuk menjadi special.');
        }
    }
}
