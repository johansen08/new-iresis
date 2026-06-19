<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sku_special_fcd extends CI_Model
{
    function get_skus_data($data = null)
    {
        $this->db->select('id_sku, nama_sku, is_special');
        $this->db->from('tblsku');

        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('id_sku', $data['search']);
            $this->db->or_like('nama_sku', $data['search']);
            $this->db->group_end();
        }

        if (isset($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        if (isset($data['order'])) {
            $this->db->order_by($data['order'], $data['dir']);
        } else {
            $this->db->order_by('id_sku', 'DESC');
        }

        return $this->db->get();
    }

    function count_all_skus($data = null)
    {
        $this->db->from('tblsku');
        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('id_sku', $data['search']);
            $this->db->or_like('nama_sku', $data['search']);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }

    function update_is_special($id_sku, $is_special)
    {
        $this->db->where('id_sku', $id_sku);
        return $this->db->update('tblsku', ['is_special' => $is_special]);
    }
}
