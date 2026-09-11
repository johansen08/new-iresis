<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Buka_retur extends CI_Model {

    protected $table = 'tblbukaretur';

    public function __construct() 
    {
        parent::__construct();
    }

    public function get_returan_buka() 
    {
        $this->db->select('*');
        $this->db->from($this->table); 
        $this->db->where('status_acc', 0);
        $this->db->order_by('created_at', 'DESC');

        return $this->db->get();
    }
}
