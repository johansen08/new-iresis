<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Error_recap_fcd extends CI_Model
{
    public function get_typemasalah_packer()
    {
        return $this->db->get('tbltypemasalahpacker')->result_array();
    }

    public function get_picker_recap($start_date, $end_date, $period)
    {
        // Define MySQL date format based on period
        if ($period == 'weekly') {
            $period_sql = "CONCAT(YEAR(rab.tanggal_resiambilbarang), '-W', LPAD(WEEK(rab.tanggal_resiambilbarang, 1), 2, '0'))";
            $err_period_sql = "CONCAT(YEAR(r.tanggal_resiambilbarang), '-W', LPAD(WEEK(r.tanggal_resiambilbarang, 1), 2, '0'))";
        } elseif ($period == 'monthly') {
            $period_sql = "DATE_FORMAT(rab.tanggal_resiambilbarang, '%Y-%m')";
            $err_period_sql = "DATE_FORMAT(r.tanggal_resiambilbarang, '%Y-%m')";
        } else { // daily
            $period_sql = "DATE_FORMAT(rab.tanggal_resiambilbarang, '%Y-%m-%d')";
            $err_period_sql = "DATE_FORMAT(r.tanggal_resiambilbarang, '%Y-%m-%d')";
        }

        $sql = "
            SELECT 
                work.peg_id,
                peg.nama_pegawai as nama,
                work.periode,
                work.total_resi,
                work.total_sku,
                work.total_qty,
                COALESCE(err.total_kesalahan, 0) as total_kesalahan,
                COALESCE(err.total_qty_salah, 0) as total_qty_salah,
                COALESCE(err.salah_1, 0) as salah_1,
                COALESCE(err.salah_2, 0) as salah_2,
                COALESCE(err.salah_3, 0) as salah_3,
                COALESCE(err.salah_4, 0) as salah_4,
                COALESCE(err.salah_5, 0) as salah_5
            FROM (
                SELECT 
                    rab.yangambil_pegawai as peg_id,
                    $period_sql as periode,
                    COUNT(DISTINCT rab.id_resi) as total_resi,
                    COUNT(DISTINCT dpr.sku) as total_sku,
                    COALESCE(SUM(dpr.jumlah), 0) as total_qty
                FROM tblresiambilbarang rab
                LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = rab.id_resi
                WHERE rab.tanggal_resiambilbarang BETWEEN ? AND ?
                GROUP BY rab.yangambil_pegawai, periode
            ) work
            JOIN tblpegawai peg ON peg.kode_pegawai = work.peg_id
            LEFT JOIN (
                SELECT 
                    r.yangambil_pegawai as peg_id,
                    $err_period_sql as err_periode,
                    COUNT(mp.id_masalahpicker) as total_kesalahan,
                    SUM(mp.qty_bermasalah) as total_qty_salah,
                    SUM(CASE WHEN mp.id_typemasalah = 1 THEN mp.qty_bermasalah ELSE 0 END) as salah_1,
                    SUM(CASE WHEN mp.id_typemasalah = 2 THEN mp.qty_bermasalah ELSE 0 END) as salah_2,
                    SUM(CASE WHEN mp.id_typemasalah = 3 THEN mp.qty_bermasalah ELSE 0 END) as salah_3,
                    SUM(CASE WHEN mp.id_typemasalah = 4 THEN mp.qty_bermasalah ELSE 0 END) as salah_4,
                    SUM(CASE WHEN mp.id_typemasalah = 5 THEN mp.qty_bermasalah ELSE 0 END) as salah_5
                FROM tblmasalahpicker mp
                JOIN tblresiambilbarang r ON r.id_resi = mp.id_printresi
                WHERE r.tanggal_resiambilbarang BETWEEN ? AND ?
                GROUP BY r.yangambil_pegawai, err_periode
            ) err ON err.peg_id = work.peg_id AND err.err_periode = work.periode
            ORDER BY total_kesalahan DESC, total_resi DESC
        ";

        return $this->db->query($sql, [$start_date, $end_date, $start_date, $end_date])->result_array();
    }

    public function get_packer_recap($start_date, $end_date, $period)
    {
        // Define MySQL date format based on period
        if ($period == 'weekly') {
            $period_sql = "CONCAT(YEAR(pk.tanggal_packing), '-W', LPAD(WEEK(pk.tanggal_packing, 1), 2, '0'))";
            $err_period_sql = "CONCAT(YEAR(p.tanggal_packing), '-W', LPAD(WEEK(p.tanggal_packing, 1), 2, '0'))";
        } elseif ($period == 'monthly') {
            $period_sql = "DATE_FORMAT(pk.tanggal_packing, '%Y-%m')";
            $err_period_sql = "DATE_FORMAT(p.tanggal_packing, '%Y-%m')";
        } else { // daily
            $period_sql = "DATE_FORMAT(pk.tanggal_packing, '%Y-%m-%d')";
            $err_period_sql = "DATE_FORMAT(p.tanggal_packing, '%Y-%m-%d')";
        }

        $sql = "
            SELECT 
                work.user_id,
                peg.name as nama,
                work.periode,
                work.total_resi,
                work.total_sku,
                work.total_qty,
                COALESCE(err.total_kesalahan, 0) as total_kesalahan,
                COALESCE(err.total_qty_salah, 0) as total_qty_salah,
                COALESCE(err.salah_1, 0) as salah_1,
                COALESCE(err.salah_2, 0) as salah_2,
                COALESCE(err.salah_3, 0) as salah_3,
                COALESCE(err.salah_4, 0) as salah_4,
                COALESCE(err.salah_5, 0) as salah_5
            FROM (
                SELECT 
                    pk.packer_pegawai as user_id,
                    $period_sql as periode,
                    COUNT(DISTINCT pk.id_resi) as total_resi,
                    COUNT(DISTINCT dpr.sku) as total_sku,
                    COALESCE(SUM(dpr.jumlah), 0) as total_qty
                FROM tblpacking pk
                LEFT JOIN tbldetailprintresi dpr ON dpr.id_resi = pk.id_resi
                WHERE pk.tanggal_packing BETWEEN ? AND ?
                GROUP BY pk.packer_pegawai, periode
            ) work
            JOIN tbluser peg ON peg.id_user = work.user_id
            LEFT JOIN (
                SELECT 
                    p.packer_pegawai as user_id,
                    $err_period_sql as err_periode,
                    COUNT(mp.id_masalahpacker) as total_kesalahan,
                    SUM(mp.qty_bermasalah) as total_qty_salah,
                    SUM(CASE WHEN mp.id_typemasalahpacker = 1 THEN mp.qty_bermasalah ELSE 0 END) as salah_1,
                    SUM(CASE WHEN mp.id_typemasalahpacker = 2 THEN mp.qty_bermasalah ELSE 0 END) as salah_2,
                    SUM(CASE WHEN mp.id_typemasalahpacker = 3 THEN mp.qty_bermasalah ELSE 0 END) as salah_3,
                    SUM(CASE WHEN mp.id_typemasalahpacker = 4 THEN mp.qty_bermasalah ELSE 0 END) as salah_4,
                    SUM(CASE WHEN mp.id_typemasalahpacker = 5 THEN mp.qty_bermasalah ELSE 0 END) as salah_5
                FROM tblmasalahpacker mp
                JOIN tblpacking p ON p.id_resi = mp.id_printresi
                WHERE p.tanggal_packing BETWEEN ? AND ?
                GROUP BY p.packer_pegawai, err_periode
            ) err ON err.user_id = work.user_id AND err.err_periode = work.periode
            ORDER BY total_kesalahan DESC, total_resi DESC
        ";

        return $this->db->query($sql, [$start_date, $end_date, $start_date, $end_date])->result_array();
    }

    public function get_receipt_detail_by_noresi($noresi)
    {
        $this->db->select('pr.id_printresi, pr.noresi, dr.sku, dr.jumlah, pk.packer_pegawai, u.name as nama_packer');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left');
        $this->db->join('tbluser u', 'u.id_user = pk.packer_pegawai', 'left');
        $this->db->where('pr.noresi', $noresi);
        return $this->db->get()->result_array();
    }

    public function save_masalah_packer($data, $user)
    {
        $this->db->trans_start();

        // 1. Ambil detail resi dan id_printresi
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $data['noresi']])->row();
        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Resi tidak ditemukan'];
        }

        // Cek apakah resi ini sudah di-pack
        $packing = $this->db->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])->row();
        if (empty($packing)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Resi belum di-packing oleh siapapun!'];
        }

        $insert_data = [
            'id_printresi' => $receipt->id_printresi,
            'noresi' => $data['noresi'],
            'sku' => $data['sku'] ? $data['sku'] : null,
            'qty' => $data['qty'] ? (int)$data['qty'] : 1,
            'id_typemasalahpacker' => $data['id_typemasalahpacker'],
            'qty_bermasalah' => $data['qty_bermasalah'] ? (int)$data['qty_bermasalah'] : 1,
            'sku_salah' => $data['sku_salah'] ? $data['sku_salah'] : null,
            'keterangan' => $data['keterangan'] ? $data['keterangan'] : null,
            'status' => 1,
            'created_by' => $user['id_user'],
            'created' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('tblmasalahpacker', $insert_data);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['error' => TRUE, 'code' => 500, 'message' => 'Gagal menyimpan data ke database.'];
        }

        return ['error' => FALSE, 'message' => 'Berhasil menyimpan kesalahan packer.'];
    }

    public function get_recent_packer_errors($limit = 10)
    {
        $this->db->select('mp.*, tmp.type_masalah, u.name as nama_packer, creator.name as nama_pelapor');
        $this->db->from('tblmasalahpacker mp');
        $this->db->join('tbltypemasalahpacker tmp', 'tmp.id_typemasalahpacker = mp.id_typemasalahpacker', 'left');
        $this->db->join('tblpacking pk', 'pk.id_resi = mp.id_printresi', 'left');
        $this->db->join('tbluser u', 'u.id_user = pk.packer_pegawai', 'left');
        $this->db->join('tbluser creator', 'creator.id_user = mp.created_by', 'left');
        $this->db->order_by('mp.created', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }
}
