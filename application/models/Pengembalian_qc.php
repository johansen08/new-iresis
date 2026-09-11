<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pengembalian_qc extends CI_Model {
    
    private $_table = "tblpengembalian_qc";

    public function __construct()
    {
        parent::__construct();
    }

    public function get_pengembalian_qc($range = null)
    {
        // Hanya tampilkan data dengan kondisi REJECT yang belum disortir (status_sortir=0)
        // dan belum diproses (status masih PENDING atau APPROVED)
        $this->db->select('*');
        $this->db->from($this->_table);
        $this->db->where('kondisi', 'REJECT');
        $this->db->where('status_sortir', 0);
        $this->db->where_not_in('status', ['REJECTED', 'REPAIR', 'GIVEAWAY', 'BARANG_TIDAK_ADA']);

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('tanggal >=', $start);
                $this->db->where('tanggal <=', $end);
            }
        }
        
        $this->db->order_by('tanggal', 'DESC');

        return $this->db->get();
    }
    public function get_giveaway($range = null)
    {
        $this->db->select('*');
        $this->db->from($this->_table);
        $this->db->where('status', 'GIVEAWAY');

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('acc_at >=', $start);
                $this->db->where('acc_at <=', $end);
            }
        }
        
        $this->db->order_by('acc_at', 'DESC');

        return $this->db->get();
    }

    public function get_laporan_giveaway($range = null)
    {
        $this->db->select('*');
        $this->db->from($this->_table);
        $this->db->where('status', 'DIKIRIM KE GUDANG PURCHASING');

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('updated_at >=', $start);
                $this->db->where('updated_at <=', $end);
            }
        }
        
        $this->db->order_by('updated_at', 'DESC');

        return $this->db->get();
    }

    public function get_laporan_penolakan($range = null)
    {
        $this->db->select('t.*, u.name as nama_acc');
        $this->db->from($this->_table . ' t');
        $this->db->join('tbluser u', 't.acc_by = u.id_user', 'left');
        $this->db->where('t.status', 'REJECTED');
        $this->db->where('t.status_sortir', 1);

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('t.acc_at >=', $start);
                $this->db->where('t.acc_at <=', $end);
            }
        }
        
        $this->db->order_by('t.acc_at', 'DESC');

        return $this->db->get();
    }

    public function get_laporan_tidak_ada($range = null)
    {
        $this->db->select('t.*, u.name as nama_acc');
        $this->db->from($this->_table . ' t');
        $this->db->join('tbluser u', 't.acc_by = u.id_user', 'left');
        $this->db->where('t.status', 'BARANG_TIDAK_ADA');

        if (!empty($range) && strpos($range, ' - ') !== false) {
            $dates = explode(' - ', $range);
            if (count($dates) == 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);
                $this->db->where('t.acc_at >=', $start);
                $this->db->where('t.acc_at <=', $end);
            }
        }
        
        $this->db->order_by('t.acc_at', 'DESC');

        return $this->db->get();
    }
}
