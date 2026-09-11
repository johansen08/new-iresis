<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shopee_penalty_fcd extends CI_Model
{
    public function get_shopee_marketplaces()
    {
        return $this->db->like('nama_marketplace', 'shopee', 'both')->get('tblmarketplace')->result_array();
    }

    public function get_shopee_shops()
    {
        return $this->db->get('tblshopee_shop')->result_array();
    }

    public function save_penalty($data)
    {
        $penalty = [
            'noresi' => $data['noresi'],
            'no_pesanan' => $data['no_pesanan'],
            'nilai_pesanan' => $data['nilai_pesanan'],
            'poin_penalty' => $data['nilai_pesanan'] * 0.1,
            'id_marketplace' => $data['id_marketplace'],
            'id_shopee_shop' => $data['id_shopee_shop'],
            'alasan_batal' => $data['alasan_batal'],
            'file_bukti' => $data['file_bukti'],
            'created_by' => $this->session->userdata('user')['id_user'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('tblshopee_penalty', $penalty);
    }

    public function get_dashboard_stats($date = null)
    {
        if (!$date) $date = date('Y-m-d');

        // Total orders today per specific shopee shop
        $this->db->select('ss.id_shopee_shop, ss.nama_toko, COUNT(pr.id_printresi) as total_orders');
        $this->db->from('tblshopee_shop ss');
        $this->db->join('tblprintresi pr', 'pr.toko = ss.nama_toko AND pr.id_marketplace = 1 AND DATE(pr.tanggal_printresi) = "' . $date . '"', 'left');
        $this->db->group_by('ss.id_shopee_shop');
        $orders = $this->db->get()->result_array();

        // Total penalty points per shop (accumulation)
        $this->db->select('id_shopee_shop, SUM(poin_penalty) as total_points');
        $this->db->from('tblshopee_penalty');
        $this->db->group_by('id_shopee_shop');
        $penalties = $this->db->get()->result_array();

        // Merge stats
        $stats = [];
        foreach ($orders as $o) {
            $stats[$o['id_shopee_shop']] = [
                'nama_toko' => $o['nama_toko'],
                'total_orders' => $o['total_orders'],
                'total_points' => 0
            ];
        }

        foreach ($penalties as $p) {
            if (isset($stats[$p['id_shopee_shop']])) {
                $stats[$p['id_shopee_shop']]['total_points'] = $p['total_points'];
            }
        }

        return $stats;
    }

    public function get_admin_cancellation_report($filters = [])
    {
        $this->db->select('p.*, ss.nama_toko, u.name as cs_name');
        $this->db->from('tblshopee_penalty p');
        $this->db->join('tblshopee_shop ss', 'ss.id_shopee_shop = p.id_shopee_shop', 'left');
        $this->db->join('tbluser u', 'u.id_user = p.created_by', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(p.created_at) >=', $filters['start_date']);
            $this->db->where('DATE(p.created_at) <=', $filters['end_date']);
        }

        if (!empty($filters['id_shopee_shop'])) {
            $this->db->where('p.id_shopee_shop', $filters['id_shopee_shop']);
        }

        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_auto_cancel_report($filters = [])
    {
        $this->db->select('pr.noresi, pr.id_printresi, pr.tanggal_bataskirim, pr.toko as nama_toko');
        $this->db->from('tblprintresi pr');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblpacking p', 'p.id_resi = pr.id_printresi', 'left');
        
        $this->db->where('m.nama_marketplace LIKE', '%shopee%');
        $this->db->where('pr.tanggal_bataskirim <', date('Y-m-d H:i:s'));
        $this->db->where('p.id_resi IS NULL');
        $this->db->where('pr.batal', 0);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(pr.tanggal_printresi) >=', $filters['start_date']);
            $this->db->where('DATE(pr.tanggal_printresi) <=', $filters['end_date']);
        }

        if (!empty($filters['nama_toko'])) {
            $this->db->where('pr.toko', $filters['nama_toko']);
        }

        $this->db->order_by('pr.tanggal_bataskirim', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_return_penalty_report($filters = [])
    {
        $this->db->select('rr.*, m.nama_marketplace, pr.toko as nama_toko');
        $this->db->from('tblresiretur rr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = rr.id_resi', 'left');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = rr.id_marketplace', 'left');
        $this->db->where('rr.status_retur', 'Buka Retur');
        $this->db->where_in('rr.status_detail', ['REJECT', 'KURANG']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(rr.tanggal_resiretur) >=', $filters['start_date']);
            $this->db->where('DATE(rr.tanggal_resiretur) <=', $filters['end_date']);
        }

        if (!empty($filters['nama_toko'])) {
            $this->db->where('pr.toko', $filters['nama_toko']);
        }

        $this->db->order_by('rr.tanggal_resiretur', 'DESC');
        return $this->db->get()->result_array();
    }
}
