<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shopee_penalty extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('shopee_penalty_fcd');
        $this->load->model('receipt_fcd');
        $this->load->model('Notification');
    }

    public function index()
    {
        $data['stats'] = $this->shopee_penalty_fcd->get_dashboard_stats();
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);
        $this->show($data);
    }

    public function input_admin()
    {
        $data['marketplaces'] = $this->shopee_penalty_fcd->get_shopee_marketplaces();
        $data['shops'] = $this->shopee_penalty_fcd->get_shopee_shops();
        $this->show($data);
    }

    public function save_penalty()
    {
        $noresi = $this->input->post('noresi');
        $nilai_pesanan = $this->input->post('nilai_pesanan');
        $id_marketplace = $this->input->post('id_marketplace');
        $id_shopee_shop = $this->input->post('id_shopee_shop');
        $alasan_batal = $this->input->post('alasan_batal');

        // Handle file upload
        $config['upload_path']   = './assets/uploads/shopee_penalty/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf';
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_bukti')) {
            $file_bukti = '';
        } else {
            $upload_data = $this->upload->data();
            $file_bukti = $upload_data['file_name'];
        }

        // Get additional info from receipt
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
        $no_pesanan = '';
        if ($receipt) {
            // Try to find no_pesanan in detail
            $detail = $this->db->get_where('tbldetailprintresi', ['id_resi' => $receipt['id_printresi']])->row_array();
            if ($detail) {
                $no_pesanan = $detail['no_pesanan'];
            }
            if (!$id_marketplace) $id_marketplace = $receipt['id_marketplace'];
            if (!$id_shopee_shop && !empty($receipt['toko'])) {
                $shop = $this->db->get_where('tblshopee_shop', ['nama_toko' => $receipt['toko']])->row_array();
                if ($shop) $id_shopee_shop = $shop['id_shopee_shop'];
            }
        }

        $save_data = [
            'noresi' => $noresi,
            'no_pesanan' => $no_pesanan,
            'nilai_pesanan' => $nilai_pesanan,
            'id_marketplace' => $id_marketplace,
            'id_shopee_shop' => $id_shopee_shop,
            'alasan_batal' => $alasan_batal,
            'file_bukti' => $file_bukti
        ];

        if ($this->shopee_penalty_fcd->save_penalty($save_data)) {
            $this->session->set_flashdata('message', 'Data pinalti berhasil disimpan.');
            
            // Notify Admin & Accounting
            $this->Notification->send("Pinalti Shopee baru telah diinput (Resi: $noresi). Alasan: $alasan_batal", "TIM ACCOUNTING", "Pinalti Shopee Baru");
        } else {
            $this->session->set_flashdata('message', 'Gagal menyimpan data pinalti.');
        }

        redirect('shopee_penalty/input_admin');
    }

    public function report()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-d');
        $end_date = $this->input->get('end_date') ?: date('Y-m-d');
        $id_shopee_shop = $this->input->get('id_shopee_shop');

        $shop_name = '';
        if ($id_shopee_shop) {
            $shop = $this->db->get_where('tblshopee_shop', ['id_shopee_shop' => $id_shopee_shop])->row_array();
            if ($shop) $shop_name = $shop['nama_toko'];
        }

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'id_shopee_shop' => $id_shopee_shop,
            'nama_toko' => $shop_name
        ];

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['id_shopee_shop'] = $id_shopee_shop;
        $data['shops'] = $this->shopee_penalty_fcd->get_shopee_shops();
        
        $data['admin_cancellations'] = $this->shopee_penalty_fcd->get_admin_cancellation_report($filter);
        $data['auto_cancellations'] = $this->shopee_penalty_fcd->get_auto_cancel_report($filter);
        $data['return_penalties'] = $this->shopee_penalty_fcd->get_return_penalty_report($filter);
        
        $this->show($data);
    }
}
