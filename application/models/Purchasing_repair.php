<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasing_repair extends CI_Model {

    private $_table = "purchasing_repair";

    public function __construct()
    {
        parent::__construct();
    }

    public function get_repair($range = null) 
    {
        $this->db->select('pr.*, t.created_at as waktu_masuk');
        $this->db->from($this->_table . ' pr');
        $this->db->join('tblpengembalian_qc t', 't.id_pengembalian = pr.id_pengembalian', 'left');
        $this->db->where('pr.status IS NULL', null, false);

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('pr.created_at >=', $start);
                $this->db->where('pr.created_at <=', $end);
            }
        }
    
        $this->db->order_by('pr.created_at', 'DESC');

        return $this->db->get();
    }

    public function get_laporan_repair($range = null)
    {
        $this->db->select('pr.*, tbluser.name as nama_pegawai, t.created_at as waktu_masuk');
        $this->db->from($this->_table . ' pr');
        $this->db->join('tbluser', 'tbluser.id_pegawai = pr.acc_by', 'left');
        $this->db->join('tblpengembalian_qc t', 't.id_pengembalian = pr.id_pengembalian', 'left');

        $this->db->where('pr.status IS NOT NULL', null, false);
        $this->db->where('pr.status !=', '');

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('pr.created_at >=', $start);
                $this->db->where('pr.created_at <=', $end);
            }
        }

        $this->db->order_by('pr.updated_at', 'DESC');
        
        return $this->db->get();
    }

    public function get_barang_repair() 
    {
        $this->db->select('*');
        $this->db->from($this->_table);
        $this->db->where('status', 'ANTAR KE DISP');
        $this->db->where('status_acc', 0);
        
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get();
    }
}
