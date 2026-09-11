<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasing_reject extends CI_Model {

    private $_table = "purchasing_reject";

    public function __construct()
    {
        parent::__construct();
    }

    public function get_reject($range = null) 
    {
        $this->db->select('pr.*, t.created_at as waktu_masuk');
        $this->db->from($this->_table . ' pr');
        $this->db->join('tblpengembalian_qc t', 't.id_pengembalian = pr.id_pengembalian', 'left');

        $this->db->where('pr.no_penyesuaian IS NULL', null, false); 

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

    public function get_laporan_reject($range = null)
    {
        $this->db->select('pr.*, t.created_at as waktu_masuk');
        $this->db->from($this->_table . ' pr');
        $this->db->join('tblpengembalian_qc t', 't.id_pengembalian = pr.id_pengembalian', 'left');
        
        $this->db->where('pr.no_penyesuaian IS NOT NULL', null, false);
        $this->db->where('pr.no_penyesuaian !=', '');

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
}
