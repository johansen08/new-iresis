<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Denda extends CI_Model {

    protected $table = 'denda';

    public function __construct() 
    {
        parent::__construct();
    }

    public function get_all() 
    {
        $this->db->select('*');
        $this->db->from($this->table);

        return $this->db->get();
    }

    public function delete_denda($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('denda');
    }

    public function insert_denda($data)
    {
        return $this->db->insert('denda', $data);
    }
}
