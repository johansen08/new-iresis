<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Surat_jalan_fcd extends CI_Model {

    protected $table = 'surat_jalan'; 

    public function __construct() 
    {
        parent::__construct();
    }

    public function get_riwayat($start_date = null, $end_date = null)
    {
        $this->db->select($this->table . '.*, tbluser.name as nama_pegawai'); 
        $this->db->from($this->table);
        $this->db->join('tbluser', 'tbluser.id_pegawai = ' . $this->table . '.id_pegawai', 'left');

        if ($start_date && $end_date) {
            $this->db->where($this->table . '.created_at >=', $start_date);
            $this->db->where($this->table . '.created_at <=', $end_date);
        }

        $this->db->order_by($this->table . '.created_at', 'DESC');

        return $this->db->get();
    }

    public function save_surat_jalan($surat_jalan, $user_id)
    {
        if (empty($surat_jalan['nama_file']) || empty($surat_jalan['jenis']) || empty($surat_jalan['link_dokumen'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Semua kolom wajib diisi!'];
        }

        $surat_jalan['id_pegawai'] = $user_id;
        $surat_jalan['created_at'] = date('Y-m-d H:i:s');
        $insert = $this->db->insert('surat_jalan', $surat_jalan);

        return $surat_jalan;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('surat_jalan');
    }

    public function get_by_id($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row_array();
    }

    public function update($surat_jalan, $user_id)
    {

        $this->db->where(array('id' => $surat_jalan['id']));
        $this->db->update('surat_jalan', $surat_jalan);
        $surat_jalan['affected_rows'] = $this->db->affected_rows();

        return $surat_jalan;
    }

    public function get_riwayat_tp($start_date = null, $end_date = null)
    {
        $this->db->select('surat_jalan_tp' . '.*, tbluser.name as nama_pegawai'); 
        $this->db->from('surat_jalan_tp');
        $this->db->join('tbluser', 'tbluser.id_pegawai = ' . 'surat_jalan_tp' . '.id_pegawai', 'left');

        if ($start_date && $end_date) {
            $this->db->where('surat_jalan_tp' . '.created_at >=', $start_date);
            $this->db->where('surat_jalan_tp' . '.created_at <=', $end_date);
        }

        $this->db->order_by('surat_jalan_tp' . '.created_at', 'DESC');

        return $this->db->get();
    }

    public function get_riwayat_tp_inbound($start_date = null, $end_date = null)
    {
        $this->db->select('surat_jalan_tp' . '.*, tbluser.name as nama_pegawai'); 
        $this->db->from('surat_jalan_tp');
        $this->db->join('tbluser', 'tbluser.id_pegawai = ' . 'surat_jalan_tp' . '.id_pegawai', 'left');

        $this->db->where('link_dokumen IS NOT NULL', null, false);
        $this->db->where('link_dokumen !=', '');

        if ($start_date && $end_date) {
            $this->db->where('surat_jalan_tp' . '.created_at >=', $start_date);
            $this->db->where('surat_jalan_tp' . '.created_at <=', $end_date);
        }

        $this->db->order_by('surat_jalan_tp' . '.created_at', 'DESC');

        return $this->db->get();
    }

    public function get_riwayat_tp_restock($start_date = null, $end_date = null)
    {
        $this->db->select('surat_jalan_tp' . '.*, tbluser.name as nama_pegawai'); 
        $this->db->from('surat_jalan_tp');
        $this->db->join('tbluser', 'tbluser.id_pegawai = ' . 'surat_jalan_tp' . '.id_pegawai', 'left');
        $this->db->where('status_inbound', 'DONE');

        if ($start_date && $end_date) {
            $this->db->where('surat_jalan_tp' . '.created_at >=', $start_date);
            $this->db->where('surat_jalan_tp' . '.created_at <=', $end_date);
        }

        $this->db->order_by('surat_jalan_tp' . '.created_at', 'DESC');

        return $this->db->get();
    }

    public function delete_tp($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('surat_jalan_tp');
    }

    // ================= SURAT JALAN DOCUMENT (header + items) =================

    /**
     * Buat nomor SJ unik: SJ-YYYYMMDD-NNN (urut per tanggal).
     */
    public function generate_no_sj($tgl)
    {
        $date_part = date('Ymd', strtotime($tgl));
        $prefix    = 'SJ-' . $date_part . '-';
        $this->db->select('no_sj');
        $this->db->like('no_sj', $prefix, 'after');
        $this->db->order_by('no_sj', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get('tblsurat_jalan_doc')->row_array();

        $next = 1;
        if ($last && !empty($last['no_sj'])) {
            $parts = explode('-', $last['no_sj']);
            $next  = intval(end($parts)) + 1;
        }
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function create_doc($data)
    {
        $this->db->insert('tblsurat_jalan_doc', $data);
        return $this->db->insert_id();
    }

    public function get_doc($id)
    {
        $this->db->where('id', $id);
        return $this->db->get('tblsurat_jalan_doc')->row_array();
    }

    public function update_doc($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tblsurat_jalan_doc', $data);
    }

    public function list_docs($start_date = null, $end_date = null)
    {
        $this->db->select('d.*, tbluser.name as nama_pegawai,
            (SELECT COUNT(*) FROM tblsurat_jalan_items i WHERE i.id_doc = d.id) as total_item');
        $this->db->from('tblsurat_jalan_doc d');
        $this->db->join('tbluser', 'tbluser.id_user = d.id_pegawai', 'left');

        if ($start_date && $end_date) {
            $this->db->where('d.tgl >=', date('Y-m-d', strtotime($start_date)));
            $this->db->where('d.tgl <=', date('Y-m-d', strtotime($end_date)));
        }

        $this->db->order_by('d.tgl', 'DESC');
        $this->db->order_by('d.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_doc_items($id_doc)
    {
        $this->db->where('id_doc', $id_doc);
        $this->db->order_by('id', 'ASC');
        return $this->db->get('tblsurat_jalan_items')->result_array();
    }

    /**
     * Item + data rak dari master SKU untuk cetak Surat Jalan (format PDF).
     * NO. RAK GUDANG = tblsku.no_rak_gudang, NO. PO (display) = tblsku.no_rak.
     */
    public function get_doc_items_with_sku($id_doc)
    {
        $this->db->select('i.*, s.nama_sku, s.no_rak, s.no_rak_gudang');
        $this->db->from('tblsurat_jalan_items i');
        $this->db->join('tblsku s', 's.id_sku = i.sku', 'left');
        $this->db->where('i.id_doc', $id_doc);
        $this->db->order_by('i.id', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Ganti seluruh item milik satu dokumen (re-upload idempotent).
     */
    public function replace_items_for_doc($id_doc, $rows)
    {
        $this->db->where('id_doc', $id_doc);
        $this->db->delete('tblsurat_jalan_items');

        if (!empty($rows)) {
            $this->db->insert_batch('tblsurat_jalan_items', $rows);
        }
        return count($rows);
    }
}
