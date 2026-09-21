<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Restock_fcd extends CI_Model
{
    /**
     * Get masalah picker data with pagination and search
     */
    public function get_masalah_picker_data($params)
    {
        $this->db->select('
            mp.id_masalahpicker,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.created,
            tm.type_masalah,
            (CASE 
                WHEN s.nama_sku IS NOT NULL AND TRIM(s.nama_sku) != \'\' AND TRIM(s.nama_sku) != \'False\' THEN s.nama_sku
                WHEN ps.nama_sku IS NOT NULL AND TRIM(ps.nama_sku) != \'\' AND TRIM(ps.nama_sku) != \'False\' THEN CONCAT(ps.nama_sku, \' \', IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\'))
                ELSE CONCAT(IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\')) 
            END) as nama_barang,
            COALESCE(peg.nama_pegawai, u.name) as nama_packer,
            COALESCE(peg_picker.nama_pegawai, \'Belum Dipick\') as nama_picker
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('mp.created >=', $params['start_date']);
            $this->db->where('mp.created <=', $params['end_date']);
        }

        // Only show processed items in reports
        $this->db->where('mp.status', 1);
        
        // Exclude Reject Display (Type 5) from this report
        $this->db->where('mp.id_typemasalah !=', 5);

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $params['search']);
            $this->db->or_like('mp.noresi', $params['search']);
            $this->db->or_like('tm.type_masalah', $params['search']);
            $this->db->or_like('mp.sku_salah', $params['search']);
            $this->db->or_like('peg.nama_pegawai', $params['search']);
            $this->db->or_like('peg_picker.nama_pegawai', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->group_end();
        }

        // Order
        if (!empty($params['order']) && $params['order'] != null) {
            $this->db->order_by($params['order'], $params['dir']);
        } else {
            $this->db->order_by('mp.created', 'DESC');
        }

        // Limit
        if ($params['length'] > 0) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count of masalah picker data
     */
    public function get_masalah_picker_data_count($params)
    {
        $this->db->select('mp.id_masalahpicker');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('mp.created >=', $params['start_date']);
            $this->db->where('mp.created <=', $params['end_date']);
        }

        // Only show processed items in reports
        $this->db->where('mp.status', 1);

        // Exclude Reject Display (Type 5) from this report
        $this->db->where('mp.id_typemasalah !=', 5);

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $params['search']);
            $this->db->or_like('mp.noresi', $params['search']);
            $this->db->or_like('tm.type_masalah', $params['search']);
            $this->db->or_like('mp.sku_salah', $params['search']);
            $this->db->or_like('peg.nama_pegawai', $params['search']);
            $this->db->or_like('peg_picker.nama_pegawai', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    /**
     * Get detail of a specific masalah picker record
     */
    public function get_masalah_picker_detail($id)
    {
        $this->db->select('
            mp.id_masalahpicker,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.created,
            mp.updated,
            tm.type_masalah,
            (CASE 
                WHEN s.nama_sku IS NOT NULL AND TRIM(s.nama_sku) != \'\' AND TRIM(s.nama_sku) != \'False\' THEN s.nama_sku
                WHEN ps.nama_sku IS NOT NULL AND TRIM(ps.nama_sku) != \'\' AND TRIM(ps.nama_sku) != \'False\' THEN CONCAT(ps.nama_sku, \' \', IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\'))
                ELSE CONCAT(IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\')) 
            END) as nama_barang,
            COALESCE(s.link_foto, "") as link_foto,
            COALESCE(s.foto_lokal, "") as foto_lokal,
            COALESCE(peg.nama_pegawai, u.name) as nama_packer,
            COALESCE(peg_picker.nama_pegawai, \'Belum Dipick\') as nama_picker,
            dr.no_rak
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = mp.id_printresi AND dr.sku = mp.sku', 'left');
        $this->db->where('mp.id_masalahpicker', $id);

        return $this->db->get()->row();
    }

    /**
     * Get Reject Display data with pagination and search
     */
    public function get_reject_display_data($params)
    {
        $this->db->select('
            mp.id_masalahpicker,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.created,
            tm.type_masalah,
            (CASE 
                WHEN s.nama_sku IS NOT NULL AND TRIM(s.nama_sku) != \'\' AND TRIM(s.nama_sku) != \'False\' THEN s.nama_sku
                WHEN ps.nama_sku IS NOT NULL AND TRIM(ps.nama_sku) != \'\' AND TRIM(ps.nama_sku) != \'False\' THEN CONCAT(ps.nama_sku, \' \', IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\'))
                ELSE CONCAT(IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\')) 
            END) as nama_barang,
            COALESCE(peg.nama_pegawai, u.name) as nama_packer,
            COALESCE(peg_picker.nama_pegawai, \'Belum Dipick\') as nama_picker
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('mp.created >=', $params['start_date']);
            $this->db->where('mp.created <=', $params['end_date']);
        }

        // Only show Reject Display (Type 5)
        $this->db->where('mp.id_typemasalah', 5);
        $this->db->where('mp.status', 1);

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $params['search']);
            $this->db->or_like('mp.noresi', $params['search']);
            $this->db->or_like('tm.type_masalah', $params['search']);
            $this->db->or_like('mp.sku_salah', $params['search']);
            $this->db->or_like('peg.nama_pegawai', $params['search']);
            $this->db->or_like('peg_picker.nama_pegawai', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->group_end();
        }

        // Order
        if (!empty($params['order']) && $params['order'] != null) {
            $this->db->order_by($params['order'], $params['dir']);
        } else {
            $this->db->order_by('mp.created', 'DESC');
        }

        // Limit
        if ($params['length'] > 0) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count of Reject Display data
     */
    public function get_reject_display_data_count($params)
    {
        $this->db->select('mp.id_masalahpicker');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('mp.created >=', $params['start_date']);
            $this->db->where('mp.created <=', $params['end_date']);
        }

        // Only show Reject Display (Type 5)
        $this->db->where('mp.id_typemasalah', 5);
        $this->db->where('mp.status', 1);

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $params['search']);
            $this->db->or_like('mp.noresi', $params['search']);
            $this->db->or_like('tm.type_masalah', $params['search']);
            $this->db->or_like('mp.sku_salah', $params['search']);
            $this->db->or_like('peg.nama_pegawai', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    public function get_display_batches_data($params)
    {
        $this->db->select("
            b.id_batch,
            b.kode_batch,
            b.status,
            b.total_qty,
            b.created_at,
            b.received_at,
            COALESCE(peg_c.nama_pegawai, uc.name) as creator_name,
            COALESCE(peg_r.nama_pegawai, ur.name, '-') as receiver_name
        ");
        $this->db->from('tblretur_display_batch b');
        $this->db->join('tbluser uc', 'uc.id_user = b.created_by', 'left');
        $this->db->join('tblpegawai peg_c', 'peg_c.kode_pegawai = uc.id_pegawai', 'left');
        $this->db->join('tbluser ur', 'ur.id_user = b.received_by', 'left');
        $this->db->join('tblpegawai peg_r', 'peg_r.kode_pegawai = ur.id_pegawai', 'left');

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('b.created_at >=', $params['start_date']);
            $this->db->where('b.created_at <=', $params['end_date']);
        }

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('b.kode_batch', $params['search']);
            $this->db->or_like('uc.name', $params['search']);
            $this->db->or_like('peg_c.nama_pegawai', $params['search']);
            $this->db->group_end();
        }

        // Sort order
        if (!empty($params['order'])) {
            $this->db->order_by($params['order'], $params['dir']);
        } else {
            $this->db->order_by('b.created_at', 'DESC');
        }

        // Limit / paging
        if (isset($params['start']) && isset($params['length']) && $params['length'] != -1) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    public function get_display_batches_count($params)
    {
        $this->db->from('tblretur_display_batch b');
        $this->db->join('tbluser uc', 'uc.id_user = b.created_by', 'left');
        $this->db->join('tblpegawai peg_c', 'peg_c.kode_pegawai = uc.id_pegawai', 'left');

        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('b.created_at >=', $params['start_date']);
            $this->db->where('b.created_at <=', $params['end_date']);
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('b.kode_batch', $params['search']);
            $this->db->or_like('uc.name', $params['search']);
            $this->db->or_like('peg_c.nama_pegawai', $params['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    public function get_batch_details($batch_id)
    {
        $this->db->select("
            MIN(bd.id_detail) as id_detail,
            SUM(bd.qty) as qty,
            GROUP_CONCAT(DISTINCT bd.keterangan SEPARATOR '; ') as keterangan,
            GROUP_CONCAT(DISTINCT br.resi_buka ORDER BY br.resi_buka ASC SEPARATOR ', ') as noresi,
            br.sku,
            MAX(COALESCE(s.nama_sku, 'Tidak Ada Detail SKU')) as nama_barang,
            GROUP_CONCAT(DISTINCT COALESCE(dp.no_rak, '-') ORDER BY dp.no_rak ASC SEPARATOR ', ') as no_rak,
            GROUP_CONCAT(DISTINCT COALESCE(mp.nama_marketplace, '-') ORDER BY mp.nama_marketplace ASC SEPARATOR ', ') as nama_marketplace,
            GROUP_CONCAT(DISTINCT br.toko ORDER BY br.toko ASC SEPARATOR ', ') as nama_toko
        ");
        $this->db->from('tblretur_display_batch_detail bd');
        $this->db->join('tblbukaretur br', 'br.id_bukaretur = bd.id_bukaretur', 'left');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblsku s', 's.id_sku = br.sku', 'left');
        $this->db->where('bd.batch_id', $batch_id);
        $this->db->group_by('br.sku');
        $this->db->order_by('no_rak', 'ASC');

        return $this->db->get()->result_array();
    }

    public function confirm_batch_received($batch_id, $user_id)
    {
        $update_data = [
            'status'      => 'DITERIMA',
            'received_by' => $user_id,
            'received_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id_batch', $batch_id);
        return $this->db->update('tblretur_display_batch', $update_data);
    }
}

