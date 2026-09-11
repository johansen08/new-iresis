<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lost_scan_packer_fcd extends CI_Model
{
    function save($data, $user)
    {
        // Check for double scan
        $this->db->where('noresi', $data['noresi']);
        $existing = $this->db->get('tbllostscanpacker')->row();
        if ($existing) {
            return -1; // Special code for duplicate
        }

        // Fetch status and courier from tblprintresi
        $this->db->select('t.status_pesanan, t2.nama_kurir');
        $this->db->from('tblprintresi t');
        $this->db->join('tblkurir t2', 't2.id_kurir = t.id_kurir', 'left');
        $this->db->where('t.noresi', $data['noresi']);
        $receipt = $this->db->get()->row();

        $insert_data = [
            'noresi' => $data['noresi'],
            'lost_type' => $data['lost_type'],
            'nama_packer' => $data['nama_packer'],
            'status_resi' => $receipt ? $receipt->status_pesanan : 'NOT FOUND',
            'kurir' => $receipt ? $receipt->nama_kurir : 'NOT FOUND',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $user['id_user']
        ];

        $this->db->insert('tbllostscanpacker', $insert_data);
        return $this->db->affected_rows();
    }

    function get_data($data, $start_date, $end_date, $lost_type = null)
    {
        $this->db->select('t.*, p.nama_pegawai as nama_picker, u.name as nama_pelapor');
        $this->db->from('tbllostscanpacker t');
        $this->db->join('tblprintresi pr', 'pr.noresi = t.noresi', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblpegawai p', 'p.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->join('tbluser u', 'u.id_user = t.created_by', 'left');

        $this->db->where('t.created_at >=', $start_date . ' 00:00:00');
        $this->db->where('t.created_at <=', $end_date . ' 23:59:59');

        if ($lost_type) {
            $this->db->where('t.lost_type', $lost_type);
        }

        if (!empty($data['search'])) {
            $x = 0;
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }
            $this->db->group_end();
        }

        if (isset($data['order']) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('t.created_at', 'DESC');
        }

        if (isset($data['length']) && $data['length'] != -1) {
            $this->db->limit($data['length'], $data['start']);
        }

        $this->db->group_by('t.id_lostscanpacker');

        return $this->db->get();
    }

    function get_total_data($data, $start_date, $end_date, $lost_type = null)
    {
        $this->db->from('tbllostscanpacker t');
        
        $this->db->where('t.created_at >=', $start_date . ' 00:00:00');
        $this->db->where('t.created_at <=', $end_date . ' 23:59:59');

        if ($lost_type) {
            $this->db->where('t.lost_type', $lost_type);
        }
        if (!empty($data['search'])) {
            $this->db->join('tblprintresi pr', 'pr.noresi = t.noresi', 'left');
            $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left');
            $this->db->join('tblpegawai p', 'p.kode_pegawai = rab.yangambil_pegawai', 'left');
            $this->db->join('tbluser u', 'u.id_user = t.created_by', 'left');

            $x = 0;
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }
            $this->db->group_end();
        }

        $this->db->where('t.created_at >=', $start_date . ' 00:00:00');
        $this->db->where('t.created_at <=', $end_date . ' 23:59:59');

        return $this->db->count_all_results();
    }
    function get_summary_stats($start_date, $end_date)
    {
        // Totals per Type
        $this->db->select('lost_type, count(*) as total');
        $this->db->from('tbllostscanpacker');
        $this->db->where('created_at >=', $start_date . ' 00:00:00');
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->group_by('lost_type');
        $totals_raw = $this->db->get()->result_array();
        
        $totals = ['PACKER' => 0, 'PICKER' => 0, 'HO' => 0, 'ALL' => 0];
        foreach ($totals_raw as $t) {
            $totals[$t['lost_type']] = $t['total'];
            $totals['ALL'] += $t['total'];
        }

        // Top 3 Picker
        $this->db->select('nama_packer as nama, count(*) as total');
        $this->db->from('tbllostscanpacker');
        $this->db->where('lost_type', 'PICKER');
        $this->db->where('created_at >=', $start_date . ' 00:00:00');
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->group_by('nama_packer');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(3);
        $top_picker = $this->db->get()->result_array();

        // Top 3 Packer
        $this->db->select('nama_packer as nama, count(*) as total');
        $this->db->from('tbllostscanpacker');
        $this->db->where('lost_type', 'PACKER');
        $this->db->where('created_at >=', $start_date . ' 00:00:00');
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->group_by('nama_packer');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(3);
        $top_packer = $this->db->get()->result_array();

        // Top 3 HO
        $this->db->select('nama_packer as nama, count(*) as total');
        $this->db->from('tbllostscanpacker');
        $this->db->where('lost_type', 'HO');
        $this->db->where('created_at >=', $start_date . ' 00:00:00');
        $this->db->where('created_at <=', $end_date . ' 23:59:59');
        $this->db->group_by('nama_packer');
        $this->db->order_by('total', 'DESC');
        $this->db->limit(3);
        $top_ho = $this->db->get()->result_array();

        return [
            'totals' => $totals,
            'top_picker' => $top_picker,
            'top_packer' => $top_packer,
            'top_ho' => $top_ho
        ];
    }

    function delete($id)
    {
        $this->db->where('id_lostscanpacker', $id);
        $this->db->delete('tbllostscanpacker');
        return $this->db->affected_rows();
    }
}
