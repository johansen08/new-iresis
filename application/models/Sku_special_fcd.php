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

    function reset_all_special()
    {
        // First backup the current state
        $this->db->query("UPDATE tblsku SET was_special = is_special");
        
        // Then set all special to non-special
        $this->db->where('is_special', 1);
        return $this->db->update('tblsku', ['is_special' => 0]);
    }

    function undo_reset_all_special()
    {
        // Restore is_special from was_special
        $this->db->query("UPDATE tblsku SET is_special = was_special WHERE was_special = 1");
        
        // Clear was_special buffer
        return $this->db->query("UPDATE tblsku SET was_special = 0");
    }

    function get_special_assignment($tanggal = null)
    {
        if (empty($tanggal)) $tanggal = date('Y-m-d');
        
        $this->db->select('sa.*, p.nama_pegawai, sp.status_name');
        $this->db->from('tblspecial_picker_today sa');
        $this->db->join('tblpegawai p', 'p.kode_pegawai = sa.id_pegawaipicker', 'left');
        $this->db->join('tblmasterstatusperforma sp', 'sp.id_statusperforma = sa.status_performa_id', 'left');
        $this->db->where('sa.tanggal', $tanggal);
        return $this->db->get()->row();
    }

    function save_special_assignment($data)
    {
        $existing = $this->db->get_where('tblspecial_picker_today', ['tanggal' => $data['tanggal']])->row();
        
        if ($existing) {
            $this->db->where('id', $existing->id);
            return $this->db->update('tblspecial_picker_today', $data);
        } else {
            return $this->db->insert('tblspecial_picker_today', $data);
        }
    }

    function is_special_receipt($noresi)
    {
        $noresi = trim($noresi);
        $this->db->select('s.id_sku');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dpr', 'dpr.id_resi = pr.id_printresi');
        $this->db->join('tblsku s', 's.id_sku = dpr.sku');
        $this->db->where('pr.noresi', $noresi);
        $this->db->where('s.is_special', 1);
        $this->db->limit(1);
        
        $query = $this->db->get();
        return $query->num_rows() > 0;
    }

    function analyze_and_set_special()
    {
        // Temukan resi dengan 1 tipe sku dan jumlah 1
        // yang hari ini, dan belum di packing/ho (optional, tapi biasanya berdasarkan data print resi hari ini)
        
        $sql = "
            SELECT dr.sku
            FROM tbldetailprintresi dr
            JOIN tblprintresi pr ON pr.id_printresi = dr.id_resi
            JOIN (
                SELECT id_resi
                FROM tbldetailprintresi
                GROUP BY id_resi
                HAVING COUNT(id_resi) = 1 AND SUM(jumlah) = 1
            ) sr ON dr.id_resi = sr.id_resi
            WHERE DATE(pr.created_at) = CURDATE()
            GROUP BY dr.sku
            HAVING COUNT(dr.id_resi) >= 3
        ";

        $query = $this->db->query($sql);
        $skus = $query->result_array();

        $updated_count = 0;
        if (!empty($skus)) {
            $sku_ids = array_column($skus, 'sku');
            
            $this->db->where_in('id_sku', $sku_ids);
            $this->db->where('is_special', 0); // Only update those that are not special yet
            $this->db->update('tblsku', ['is_special' => 1]);
            
            $updated_count = $this->db->affected_rows();
        }

        return $updated_count;
    }
}
