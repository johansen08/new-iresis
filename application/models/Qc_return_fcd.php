<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_return_fcd extends CI_Model
{
    function get_data($filters = array())
    {
        $this->db->select('t.*, u1.name as submitter_name, u2.name as acc_name');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('t.tanggal >=', $filters['start_date']);
            $this->db->where('t.tanggal <=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        
        if (!empty($filters['kondisi'])) {
            $this->db->where('t.kondisi', $filters['kondisi']);
        }

        $this->db->order_by('t.id_pengembalian', 'DESC');
        return $this->db->get();
    }

    function save($data)
    {
        return $this->db->insert('tblpengembalian_qc', $data);
    }

    function approve($id, $user_id, $status = 'APPROVED')
    {
        $data = array(
            'status' => $status,
            'acc_by' => $user_id,
            'acc_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('id_pengembalian', $id);
        return $this->db->update('tblpengembalian_qc', $data);
    }

    function approve_all($user_id)
    {
        $data = array(
            'status' => 'APPROVED',
            'acc_by' => $user_id,
            'acc_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('status', 'PENDING');
        return $this->db->update('tblpengembalian_qc', $data);
    }

    function get_by_id($id)
    {
        $this->db->where('id_pengembalian', $id);
        return $this->db->get('tblpengembalian_qc')->row();
    }

    function get_sku_suggestions($search)
    {
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->from('tblsku');
        $this->db->like('id_sku', $search);
        $this->db->order_by('id_sku');
        $this->db->limit(10);
        return $this->db->get()->result_array();
    }

    /**
     * Cari satu SKU dari teks yang diketik user (boleh tidak lengkap, huruf besar/kecil bebas).
     * Urutan pencocokan:
     *   1. persis sama     : "BM-AKS28-2"
     *   2. mengandung teks : "aks28-2" -> hanya BM-AKS28-2 yang cocok
     *   3. akhiran sama    : "aks28-1" -> mengandung 6 SKU (-1, -10, -11, ...), tapi yang berakhiran "-1" cuma BM-AKS28-1
     *
     * Hasil:
     *   ['status' => 'ok',        'sku' => baris tblsku]
     *   ['status' => 'ambigu',    'kandidat' => [baris, ...]]  -> lebih dari satu, user harus pilih
     *   ['status' => 'tidak_ada']
     */
    function cari_sku($teks)
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return array('status' => 'tidak_ada');
        }

        // 1. Persis sama (kolasi id_sku sudah case-insensitive)
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->where('id_sku', $teks);
        $row = $this->db->get('tblsku')->row_array();
        if ($row) {
            return array('status' => 'ok', 'sku' => $row);
        }

        // 2. Mengandung teks
        $kandidat = $this->get_sku_suggestions($teks);
        if (count($kandidat) === 0) {
            return array('status' => 'tidak_ada');
        }
        if (count($kandidat) === 1) {
            return array('status' => 'ok', 'sku' => $kandidat[0]);
        }

        // 3. Banyak kandidat: ambil yang akhirannya persis sama, asal cuma satu
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->like('id_sku', $teks, 'before');
        $this->db->limit(2);
        $akhiran = $this->db->get('tblsku')->result_array();
        if (count($akhiran) === 1) {
            return array('status' => 'ok', 'sku' => $akhiran[0]);
        }

        return array('status' => 'ambigu', 'kandidat' => $kandidat);
    }

    function get_data_export($start_date, $end_date, $status = null, $kondisi = null)
    {
        $this->db->select('t.*, u1.username as submitter, u2.username as approver');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');

        $this->db->where('t.tanggal >=', $start_date);
        $this->db->where('t.tanggal <=', $end_date);

        if (!empty($status)) {
            $this->db->where('t.status', $status);
        }

        if (!empty($kondisi)) {
            $this->db->where('t.kondisi', $kondisi);
        }

        $this->db->order_by('t.tanggal', 'DESC');
        $this->db->order_by('t.id_pengembalian', 'DESC');

        return $this->db->get();
    }

    function delete_data($id)
    {
        $this->db->where('id_pengembalian', $id);
        return $this->db->delete('tblpengembalian_qc');
    }

    function get_proses_barang_qc($filters = array())
    {
        $this->db->select('t.*, u1.name as submitter_name, u2.name as acc_name, 
                           pr.no_penyesuaian as reject_no_penyesuaian, pr.foto_barang as reject_foto, pr.created_at as reject_at, pr.keterangan as reject_keterangan,
                           pr_repair.no_penyesuaian as repair_no_penyesuaian, pr_repair.status as repair_status, pr_repair.foto_barang as repair_foto, pr_repair.created_at as repair_at, pr_repair.keterangan as repair_keterangan');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');
        $this->db->join('purchasing_reject pr', 't.id_pengembalian = pr.id_pengembalian', 'left');
        $this->db->join('purchasing_repair pr_repair', 't.id_pengembalian = pr_repair.id_pengembalian', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('t.tanggal >=', $filters['start_date']);
            $this->db->where('t.tanggal <=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        
        $this->db->order_by('t.tanggal', 'DESC');
        $this->db->order_by('t.id_pengembalian', 'DESC');
        return $this->db->get();
    }
}
