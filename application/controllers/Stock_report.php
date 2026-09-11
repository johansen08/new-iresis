<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stock_report extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('sku_fcd');
        $this->load->model('param_fcd');
    }

    public function index()
    {
        // Get threshold from param
        $threshold_param = $this->db->get_where('param', ['paramgroup' => 'STOCK_SETTING', 'paramvalue1' => 'LOW_STOCK_THRESHOLD'])->row_array();
        $threshold = $threshold_param ? (int)$threshold_param['paramvalue2'] : 10;

        $data['threshold'] = $threshold;
        
        // Count Summary
        $data['empty_stock'] = $this->db->where('total_stok', 0)->count_all_results('tblsku');
        $data['low_stock'] = $this->db->where('total_stok >', 0)->where('total_stok <=', $threshold)->count_all_results('tblsku');
        $data['available_stock'] = $this->db->where('total_stok >', $threshold)->count_all_results('tblsku');
        
        $this->show($data);
    }

    public function save_setting()
    {
        $threshold = $this->input->post('threshold');
        
        $check = $this->db->get_where('param', ['paramgroup' => 'STOCK_SETTING', 'paramvalue1' => 'LOW_STOCK_THRESHOLD'])->row_array();
        
        if ($check) {
            $this->db->update('param', ['paramvalue2' => $threshold], ['id' => $check['id']]);
        } else {
            $this->db->insert('param', [
                'paramgroup' => 'STOCK_SETTING',
                'paramvalue1' => 'LOW_STOCK_THRESHOLD',
                'paramvalue2' => $threshold,
                'description' => 'Batas stok menipis',
                'isactive' => 1
            ]);
        }
        
        $this->make_ajax_response(200, 'Setting berhasil disimpan');
    }

    public function get_details()
    {
        $type = $this->input->get('type');
        $threshold_param = $this->db->get_where('param', ['paramgroup' => 'STOCK_SETTING', 'paramvalue1' => 'LOW_STOCK_THRESHOLD'])->row_array();
        $threshold = $threshold_param ? (int)$threshold_param['paramvalue2'] : 10;

        $this->db->select('id_sku, nama_sku, total_stok, no_rak, lokasi');
        if ($type == 'empty') {
            $this->db->where('total_stok', 0);
        } elseif ($type == 'low') {
            $this->db->where('total_stok >', 0)->where('total_stok <=', $threshold);
        } else {
            $this->db->where('total_stok >', $threshold);
        }
        
        $list = $this->db->get('tblsku')->result_array();
        
        $this->make_ajax_response(200, null, $list);
    }
}
