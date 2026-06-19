<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_fcd extends CI_Model
{

    /**
     * Save Terima Retur - Creates new record(s) with status "Terima Retur"
     * Ensures only 1 row per receipt in tblresiretur
     */
    function save_terima_retur($retur, $user)
    {
        // Validate input
        if (empty($retur['hasil_scan'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Hasil Scan harus diisi'];
        }

        // Parse hasil scan - bisa berisi multiple resi (separated by newline)
        $resi_list = array_filter(array_map('trim', explode("\n", $retur['hasil_scan'])));
        if (empty($resi_list)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Hasil scan tidak valid'];
        }

        $success_count = 0;
        $error_messages = [];

        foreach ($resi_list as $noresi) {
            // Get receipt from tblprintresi
            $receipt = $this->db
                ->select('id_printresi, id_kurir, id_marketplace, noresi')
                ->get_where('tblprintresi', ['noresi' => $noresi])
                ->row_array();

            if (empty($receipt)) {
                $error_messages[] = "Resi $noresi tidak ditemukan";
                continue;
            }

            // Check if retur already exists
            $retur_exist = $this->db
                ->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', ['id_resi' => $receipt['id_printresi']])
                ->row_array();

            if (!empty($retur_exist)) {
                $error_messages[] = "Resi $noresi sudah diinput di Terima Retur";
                continue;
            }

            // Insert new Terima Retur record
            $insert_data = [
                'id_resi' => $receipt['id_printresi'],
                'tanggal_resiretur' => date('Y-m-d H:i:s'),
                'status_retur' => 'Terima Retur',
                'status_detail' => null,
                'sudah_cetak' => '',
                'id_kurir' => $receipt['id_kurir'],
                'id_pegawai' => $user,
                'id_marketplace' => $receipt['id_marketplace'],
                'noresi' => $receipt['noresi']
            ];

            $this->db->insert('tblresiretur', $insert_data);
            $success_count++;
        }

        // Build response
        if ($success_count === 0) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Tidak ada resi yang berhasil diproses. ' . implode(', ', $error_messages)];
        }

        $response = [
            'affected_rows' => $success_count,
            'success_message' => "$success_count resi berhasil diproses"
        ];

        if (!empty($error_messages)) {
            $response['warning'] = implode(', ', $error_messages);
        }

        return $response;
    }

    /**
     * Save Buka Retur - Updates existing "Terima Retur" record(s) to "Buka Retur"
     * Ensures only 1 row per receipt in tblresiretur
     */
    function save_buka_retur($retur, $user)
    {
        // Validate input
        if (empty($retur['status_detail'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Status Detail harus diisi'];
        }

        if (empty($retur['hasil_scan'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Hasil Scan harus diisi'];
        }

        // Parse hasil scan - bisa berisi multiple resi (separated by newline)
        $resi_list = array_filter(array_map('trim', explode("\n", $retur['hasil_scan'])));
        if (empty($resi_list)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Hasil scan tidak valid'];
        }

        $success_count = 0;
        $error_messages = [];

        foreach ($resi_list as $noresi) {
            // Get receipt from tblprintresi
            $receipt = $this->db
                ->select('id_printresi, id_kurir, id_marketplace, noresi')
                ->get_where('tblprintresi', ['noresi' => $noresi])
                ->row_array();

            if (empty($receipt)) {
                $error_messages[] = "Resi $noresi tidak ditemukan";
                continue;
            }

            // Check if retur exists with status "Terima Retur"
            $retur_exist = $this->db
                ->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', ['id_resi' => $receipt['id_printresi']])
                ->row_array();

            if (empty($retur_exist)) {
                $error_messages[] = "Resi $noresi belum diinput di Terima Retur";
                continue;
            }

            if ($retur_exist['status_retur'] !== 'Terima Retur') {
                $error_messages[] = "Resi $noresi sudah diproses dengan status " . $retur_exist['status_retur'];
                continue;
            }

            // Update existing record to Buka Retur
            $update_data = [
                'status_retur' => 'Buka Retur',
                'status_detail' => $retur['status_detail'],
                'tanggal_resiretur' => date('Y-m-d H:i:s'),
                'id_pegawai' => $user
            ];

            $this->db->where('id_resiretur', $retur_exist['id_resiretur']);
            $this->db->update('tblresiretur', $update_data);
            $success_count++;
        }

        // Build response
        if ($success_count === 0) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Tidak ada resi yang berhasil diproses. ' . implode(', ', $error_messages)];
        }

        $response = [
            'affected_rows' => $success_count,
            'success_message' => "$success_count resi berhasil diproses"
        ];

        if (!empty($error_messages)) {
            $response['warning'] = implode(', ', $error_messages);
        }

        return $response;
    }

    /**
     * Legacy save method - routes to appropriate function based on status_retur
     * @deprecated Use save_terima_retur() or save_buka_retur() directly
     */
    function save($retur, $user)
    {
        if (empty($retur['status_retur'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Status harus diisi'];
        }

        if ($retur['status_retur'] === 'Terima Retur') {
            return $this->save_terima_retur($retur, $user);
        } elseif ($retur['status_retur'] === 'Buka Retur') {
            return $this->save_buka_retur($retur, $user);
        }

        return ['error' => TRUE, 'code' => 400, 'message' => 'Status retur tidak valid'];
    }

    function get_data($data)
    {
        if ($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
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

        $this->db->select('
            t.id_resiretur,
            t.noresi,
            t.tanggal_resiretur,
            t2.nama_marketplace,
            t3.nama_kurir,
            pr.toko as nama_toko,
            t.status_detail,
            t4.username
        ');

        $this->db->from('tblresiretur t');
        $this->db->join('tblprintresi pr', 'pr.noresi = t.noresi', 'left'); 
        $this->db->join('tblmarketplace t2', 't2.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t3', 't3.id_kurir = t.id_kurir', 'left');
        $this->db->join('tbluser t4', 't4.id_user = t.id_pegawai', 'left');

        $this->db->limit($data['length'], $data['start']);

        return $this->db->get();
    }

    function get_total_data($data)
    {
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

        $this->db->join('tblmarketplace t2', 't2.id_marketplace = t.id_marketplace', 'left');

        $this->db->join('tblkurir t3', 't3.id_kurir = t.id_kurir', 'left');

        $this->db->join('tbluser t4', 't4.id_user = t.id_pegawai', 'left');

        $query = $this->db->select("count(1) as num")->get("tblresiretur t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function destroy($id_resiretur)
    {
        $this->db->delete('tblresiretur', ['id_resiretur' => $id_resiretur]);

        $retur['affected_rows'] = $this->db->affected_rows();

        return $retur;
    }

    function batch_destroy($ids)
    {
        if (empty($ids)) return ['affected_rows' => 0];
        
        $this->db->where_in('id_resiretur', $ids);
        $this->db->delete('tblresiretur');

        $retur['affected_rows'] = $this->db->affected_rows();

        return $retur;
    }

    /**
     * Get total scan Terima Retur for today by user
     */
    function get_total_scan_terima_today($user_id)
    {
        $this->db->select('COUNT(*) as total');
        $this->db->from('tblresiretur');
        $this->db->where('id_pegawai', $user_id);
        $this->db->where('status_retur', 'Terima Retur');
        $this->db->where('DATE(tanggal_resiretur)', date('Y-m-d'));
        
        $result = $this->db->get()->row();
        return $result ? $result->total : 0;
    }

    /**
     * Get total scan Buka Retur for today by user
     */
    function get_total_scan_buka_today($user_id)
    {
        $this->db->select('COUNT(*) as total');
        $this->db->from('tblbukaretur');
        $this->db->where('id_pegawai', $user_id);
        $this->db->where('DATE(tanggal_buka_retur)', date('Y-m-d'));
        
        $result = $this->db->get()->row();
        return $result ? $result->total : 0;
    }

    function destroy_buka_retur($id_bukaretur)
    {
        $this->db->delete('tblbukaretur', ['id_bukaretur' => $id_bukaretur]);

        $retur['affected_rows'] = $this->db->affected_rows();

        return $retur;
    }

    function batch_destroy_buka_retur($ids)
    {
        if (empty($ids)) return ['affected_rows' => 0];

        $this->db->where_in('id_bukaretur', $ids);
        $this->db->delete('tblbukaretur');

        $retur['affected_rows'] = $this->db->affected_rows();

        return $retur;
    }

    /**
     * Get Terima Retur data with SKU details
     */
    function get_terima_retur_with_details($data, $start_date, $end_date)
    {
        // Build order clause
        if (!empty($data) && !empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('tr.tanggal_resiretur', 'DESC');
        }

        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        // Select with joins to get SKU details
        $this->db->select('
            tr.id_resiretur,
            tr.noresi,
            tr.tanggal_resiretur,
            tr.status_retur,
            mp.nama_marketplace,
            kr.nama_kurir,
            pr.toko as nama_toko,
            tr.status_detail,
            dp.no_pesanan,
            dp.sku,
            dp.jumlah
        ');

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }

        // Pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    // ==================== RETUR COMPLAIN ====================
    public function save_complain($complain, $user_id)
    {
        if (empty($complain['noresi'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi harus diisi'];
        }

        if (empty($complain['complain_type'])) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Jenis complain harus diisi'];
        }

        $complain['noresi'] = strtoupper(trim($complain['noresi']));

        $this->db->where('noresi', $complain['noresi']);
        if ($complain['complain_type'] === 'refund') {
            $this->db->where('complain_type', 'refund');
        } elseif ($complain['complain_type'] === 'replacement') {
            $this->db->where('complain_type', 'replacement');
        }

        $existing = $this->db->get('tblreturcomplain')->row_array();

        $complain['updated_by'] = $user_id;
        $complain['updated_at'] = date('Y-m-d H:i:s');

        if (!empty($existing)) {
            $this->db->where('id', $existing['id']);
            $this->db->update('tblreturcomplain', $complain);
            $complain['id'] = $existing['id'];
        } else {
            $complain['created_by'] = $user_id;
            $complain['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('tblreturcomplain', $complain);
            $complain['id'] = $this->db->insert_id();
        }

        return $complain;
    }

    public function get_complain_list($params)
    {
        $this->_build_complain_query($params);

        if (!empty($params['length'])) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    public function get_total_complain_list($params)
    {
        $this->_build_complain_query($params, true);
        return $this->db->count_all_results();
    }

    private function _build_complain_query($params, $count_only = false)
    {
        if (!$count_only) {
            $this->db->select('rc.*, pr.id_marketplace, mp.nama_marketplace as marketplace_name');
        }

        $this->db->from('tblreturcomplain rc');
        $this->db->join('tblprintresi pr', 'pr.noresi = rc.noresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');

        if (!empty($params['status_filter']) && $params['status_filter'] !== 'ALL') {
            $this->db->where('rc.status', $params['status_filter']);
        }

        if (!empty($params['reportrange'])) {
            $dates = explode(' - ', $params['reportrange']);
            if (count($dates) === 2) {
                $start_date = trim($dates[0]);
                $end_date = trim($dates[1]);
                $this->db->where('rc.created_at >=', $start_date);
                $this->db->where('rc.created_at <=', $end_date);
            }
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('rc.noresi', $params['search']);
            $this->db->or_like('rc.customer_name', $params['search']);
            $this->db->or_like('rc.marketplace', $params['search']);
            $this->db->or_like('rc.notes', $params['search']);
            $this->db->group_end();
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $this->db->order_by($params['order'], $params['dir']);
            } else {
                $this->db->order_by('rc.created_at', 'DESC');
            }
        }
    }

    public function get_complain_report_list($params)
    {
        $this->_build_complain_report_query($params);

        if (!empty($params['length'])) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    public function get_total_complain_report_list($params)
    {
        $this->_build_complain_report_query($params, true);
        return $this->db->count_all_results();
    }

    private function _build_complain_report_query($params, $count_only = false)
    {
        if (!$count_only) {
            $this->db->select('rc.*, pr.id_marketplace, mp.nama_marketplace as marketplace_name');
        }

        $this->db->from('tblreturcomplain rc');
        $this->db->join('tblprintresi pr', 'pr.noresi = rc.noresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');

        // Tampilkan semua data complain (termasuk yang status TO_DO)

        if (!empty($params['status_filter']) && $params['status_filter'] !== 'ALL') {
            $this->db->where('rc.status', $params['status_filter']);
        }

        if (!empty($params['reportrange'])) {
            $dates = explode(' - ', $params['reportrange']);
            if (count($dates) === 2) {
                $start_date = trim($dates[0]);
                $end_date = trim($dates[1]);
                // Filter berdasarkan created_at (kapan proses retur complain dilakukan)
                $this->db->where('rc.created_at >=', $start_date);
                $this->db->where('rc.created_at <=', $end_date);
            }
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('rc.noresi', $params['search']);
            $this->db->or_like('rc.customer_name', $params['search']);
            $this->db->or_like('rc.marketplace', $params['search']);
            $this->db->or_like('rc.notes', $params['search']);
            $this->db->group_end();
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $this->db->order_by($params['order'], $params['dir']);
            } else {
                $this->db->order_by('rc.created_at', 'DESC');
            }
        }
    }

    public function update_complain_status($id, $status, $user_id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblreturcomplain', array(
            'status' => $status,
            'updated_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s')
        ));

        if ($this->db->affected_rows() === 0) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Data complain tidak ditemukan atau status sama'];
        }

        return ['success' => TRUE];
    }

    public function update_complain($id, $complain, $user_id)
    {
        // Check if record exists and status is TO_DO
        $this->db->select('status');
        $this->db->from('tblreturcomplain');
        $this->db->where('id', $id);
        $existing = $this->db->get()->row();

        if (!$existing) {
            return ['error' => TRUE, 'code' => 404, 'message' => 'Data complain tidak ditemukan'];
        }

        if ($existing->status !== 'TO_DO') {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Hanya complain dengan status TO_DO yang dapat diubah'];
        }

        // Update the complain
        $complain['updated_by'] = $user_id;
        $complain['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        $this->db->update('tblreturcomplain', $complain);

        if ($this->db->affected_rows() === 0) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Tidak ada perubahan data'];
        }

        return ['success' => TRUE];
    }

    /**
     * Get total count of Terima Retur with details
     */
    function get_total_terima_retur_with_details($data, $start_date, $end_date)
    {
        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get Buka Retur data with SKU details
     */
    function get_buka_retur_with_details($data, $start_date, $end_date)
    {
        // Build order clause
        if (!empty($data) && !empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('br.tanggal_buka_retur', 'DESC');
        }

        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        // Select with joins to get SKU details
        // Note: resi_buka might be noresi, so we join based on that
        $this->db->select('
            br.id_bukaretur,
            br.resi_buka,
            br.tanggal_buka_retur,
            br.status_detail_buka,
            mp.nama_marketplace,
            kr.nama_kurir,
            pr.toko as nama_toko,
            dp.no_pesanan,
            dp.sku,
            dp.jumlah
        ');

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresiretur tr', 'tr.noresi = br.resi_buka', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = tr.id_marketplace', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count of Buka Retur with details
     */
    function get_total_buka_retur_with_details($data, $start_date, $end_date)
    {
        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresiretur tr', 'tr.noresi = br.resi_buka', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = tr.id_marketplace', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get Laporan Terima Retur (with pagination)
     */
    function get_laporan_terima_retur($data, $start_date, $end_date, $id_kurir = null, $status = 'Terima Retur')
    {
        // Build order clause
        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('tr.tanggal_resiretur', 'DESC');
        }

        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        $this->db->select('
            tr.id_resiretur,
            tr.noresi,
            tr.tanggal_resiretur,
            tr.status_retur,
            tr.status_detail,
            mp.nama_marketplace,
            kr.nama_kurir,
            dp.no_pesanan,
            pr.toko as nama_toko,
            dp.sku,
            dp.jumlah,
            dp.no_rak
        ');

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        // Filter by status
        $this->db->where('tr.status_retur', $status);

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }

        // Filter by kurir
        if (!empty($id_kurir)) {
            $this->db->where('tr.id_kurir', $id_kurir);
        }

        // Pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count Laporan Terima Retur
     */
    function get_total_laporan_terima_retur($data, $start_date, $end_date, $id_kurir = null, $status = 'Terima Retur')
    {
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');
        $this->db->where('tr.status_retur', $status);
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }
        if (!empty($id_kurir)) {
            $this->db->where('tr.id_kurir', $id_kurir);
        }
        return $this->db->count_all_results();
    }

    /**
     * Get Laporan Buka Retur (with pagination)
     */
    function get_laporan_buka_retur($data, $start_date, $end_date, $id_kurir = null)
    {
        // Build order clause
        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('br.tanggal_buka_retur', 'DESC');
        }

        // Build search clause
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        // Select with joins (base table is tblbukaretur)
        $this->db->select('
            br.id_bukaretur as id_resiretur,
            br.resi_buka as noresi,
            br.tanggal_buka_retur as tanggal_resiretur,
            br.status_buka as status_retur,
            br.status_detail_buka as status_detail,
            COALESCE(mp.nama_marketplace, mp2.nama_marketplace) as nama_marketplace,
            COALESCE(kr.nama_kurir, kr2.nama_kurir) as nama_kurir,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            COALESCE(pr.toko, br.toko) as nama_toko,
            br.sku as sku,
            br.qty as jumlah,
            br.harga as harga
        ');

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresiretur tr', 'tr.noresi = br.resi_buka', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = tr.id_marketplace', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Filter by kurir (cek dari receipt atau dari data retur hasil import)
        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('pr.id_kurir', $id_kurir);
            $this->db->or_where('tr.id_kurir', $id_kurir);
            $this->db->group_end();
        }

        // Pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count Laporan Buka Retur
     */
    function get_total_laporan_buka_retur($data, $start_date, $end_date, $id_kurir = null)
    {
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                $this->db->or_like($sterm, $data['search']);
            }
            $this->db->group_end();
        }

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresiretur tr', 'tr.noresi = br.resi_buka', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = tr.id_marketplace', 'left');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }
        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('pr.id_kurir', $id_kurir);
            $this->db->or_where('tr.id_kurir', $id_kurir);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }

    /**
     * Get ALL Laporan Terima Retur (for Excel export, no pagination)
     */
    function get_laporan_terima_retur_all($start_date, $end_date, $id_kurir = null)
    {
        $this->db->select('
            tr.id_resiretur,
            tr.noresi,
            tr.tanggal_resiretur,
            tr.status_retur,
            tr.status_detail,
            mp.nama_marketplace,
            kr.nama_kurir,
            dp.no_pesanan,
            pr.toko as nama_toko,
            dp.sku,
            dp.jumlah
        ');

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        // Filter by Terima Retur only - Use WHERE for better performance (can use index)
        $this->db->where('tr.status_retur', 'Terima Retur');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }

        // Filter by kurir
        if (!empty($id_kurir)) {
            $this->db->where('tr.id_kurir', $id_kurir);
        }

        $this->db->order_by('tr.tanggal_resiretur', 'DESC');

        return $this->db->get();
    }

    /**
     * Get ALL Laporan Buka Retur (for Excel export, no pagination)
     */
    function get_laporan_buka_retur_all($start_date, $end_date, $id_kurir = null)
    {
        $this->db->select('
            br.id_bukaretur as id_resiretur,
            br.resi_buka as noresi,
            br.tanggal_buka_retur as tanggal_resiretur,
            br.status_buka as status_retur,
            br.status_detail_buka as status_detail,
            COALESCE(mp.nama_marketplace, mp2.nama_marketplace) as nama_marketplace,
            COALESCE(kr.nama_kurir, kr2.nama_kurir) as nama_kurir,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            COALESCE(pr.toko, br.toko) as nama_toko,
            br.sku as sku,
            br.qty as jumlah,
            br.harga as harga
        ');

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblresiretur tr', 'tr.noresi = br.resi_buka', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = tr.id_marketplace', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Filter by kurir (cek dari receipt atau dari data retur hasil import)
        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('pr.id_kurir', $id_kurir);
            $this->db->or_where('tr.id_kurir', $id_kurir);
            $this->db->group_end();
        }

        $this->db->order_by('br.tanggal_buka_retur', 'DESC');

        return $this->db->get();
    }

    function get_receipt_for_buka_retur($data, $noresi) {
        $this->db->select('
            pr.noresi,
            pr.id_printresi,
            dr.sku,
            dr.jumlah as original_jumlah,
            COALESCE(SUM(br.qty), 0) as processed_jumlah,
            dr.jumlah - COALESCE(SUM(br.qty), 0) as jumlah,
            dr.no_rak,
            (CASE 
                WHEN s.nama_sku IS NOT NULL AND TRIM(s.nama_sku) != \'\' AND TRIM(s.nama_sku) != \'False\' THEN s.nama_sku
                WHEN ps.nama_sku IS NOT NULL AND TRIM(ps.nama_sku) != \'\' AND TRIM(ps.nama_sku) != \'False\' THEN CONCAT(ps.nama_sku, \' \', IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\'))
                ELSE CONCAT(IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\')) 
            END) as nama_sku,
            s.link_foto
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->join('tblbukaretur br', 'br.resi_buka = pr.noresi AND br.sku = dr.sku', 'left');

        $this->db->where('pr.noresi', $noresi);

        $this->db->group_by('dr.sku');
        $this->db->having('jumlah >', 0);

        // Optional: pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        // Order by latest
        $this->db->order_by('pr.created_at', 'DESC');

        $query = $this->db->get();

        return $query->result();
    }

    // ==================== DASHBOARD TIM RETUR ====================

    function dashboard_summary($start, $end)
    {
        $r = [];

        // Retur Tadro = belum diterima (perlu DITERIMA)
        $this->db->where('status_retur', 'Retur Tadro')
            ->where('tanggal_resiretur >=', $start)->where('tanggal_resiretur <=', $end);
        $r['tadro'] = $this->db->count_all_results('tblresiretur');

        // Terima Retur = sudah diterima, belum dibuka (perlu DIBUKA)
        $this->db->where('status_retur', 'Terima Retur')
            ->where('tanggal_resiretur >=', $start)->where('tanggal_resiretur <=', $end);
        $r['belum_dibuka'] = $this->db->count_all_results('tblresiretur');

        // Buka Retur = sudah dibuka (selesai)
        $this->db->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end);
        $r['buka'] = $this->db->count_all_results('tblbukaretur');

        // Total semua retur (gabungan) dalam rentang
        $this->db->where('tanggal_resiretur >=', $start)->where('tanggal_resiretur <=', $end);
        $r['total_retur'] = $this->db->count_all_results('tblresiretur');

        $this->db->where('created_at >=', $start)->where('created_at <=', $end);
        $r['complain'] = $this->db->count_all_results('tblreturcomplain');

        $row = $this->db->select('COALESCE(SUM(harga*qty),0) as total', false)
            ->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end)
            ->get('tblbukaretur')->row();
        $r['tagihan'] = $row ? (float) $row->total : 0;

        return $r;
    }

    function dashboard_trend($start, $end)
    {
        $terima = $this->db->select('DATE(tanggal_resiretur) as tgl, COUNT(*) as n', false)
            ->where('tanggal_resiretur >=', $start)->where('tanggal_resiretur <=', $end)
            ->group_by('DATE(tanggal_resiretur)', false)->order_by('tgl', 'ASC')
            ->get('tblresiretur')->result();

        $buka = $this->db->select('DATE(tanggal_buka_retur) as tgl, COUNT(*) as n', false)
            ->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end)
            ->group_by('DATE(tanggal_buka_retur)', false)->order_by('tgl', 'ASC')
            ->get('tblbukaretur')->result();

        return ['terima' => $terima, 'buka' => $buka];
    }

    function dashboard_by_kurir($start, $end)
    {
        return $this->db->select("COALESCE(kr.nama_kurir, 'Lainnya') as label, COUNT(*) as n", false)
            ->from('tblresiretur tr')
            ->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left')
            ->where('tr.tanggal_resiretur >=', $start)->where('tr.tanggal_resiretur <=', $end)
            ->group_by('tr.id_kurir')->order_by('n', 'DESC')->limit(8)
            ->get()->result();
    }

    function dashboard_by_marketplace($start, $end)
    {
        return $this->db->select("COALESCE(mp.nama_marketplace, 'Lainnya') as label, COUNT(*) as n", false)
            ->from('tblresiretur tr')
            ->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left')
            ->where('tr.tanggal_resiretur >=', $start)->where('tr.tanggal_resiretur <=', $end)
            ->group_by('tr.id_marketplace')->order_by('n', 'DESC')->limit(8)
            ->get()->result();
    }

    function dashboard_status_buka($start, $end)
    {
        return $this->db->select("COALESCE(NULLIF(status_detail_buka,''),'-') as label, COUNT(*) as n", false)
            ->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end)
            ->group_by('status_detail_buka')->order_by('n', 'DESC')
            ->get('tblbukaretur')->result();
    }

    function dashboard_top_sku($start, $end)
    {
        return $this->db->select('sku as label, SUM(qty) as n', false)
            ->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end)
            ->where('sku IS NOT NULL', null, false)->where("sku !=", '')
            ->group_by('sku')->order_by('n', 'DESC')->limit(10)
            ->get('tblbukaretur')->result();
    }

    // ==================== AKSI INLINE DI LAPORAN (progres status) ====================

    /**
     * Naikkan status retur dari laporan: 'terima' (Tadro->Terima) atau 'buka' (Terima->Buka).
     * Untuk 'buka', sekaligus buat baris detail buka dari SKU resi (status DEFAULT) bila belum ada.
     */
    function progress_retur_status($noresi, $action, $user_id)
    {
        $noresi = strtoupper(trim($noresi));
        if ($noresi === '') return ['error' => 'Nomor resi kosong'];

        $row = $this->db->select('id_resiretur, status_retur')
            ->get_where('tblresiretur', ['noresi' => $noresi])->row_array();
        if (empty($row)) return ['error' => 'Resi tidak ditemukan di data retur'];

        $now = date('Y-m-d H:i:s');

        if ($action === 'terima') {
            $this->db->where('id_resiretur', $row['id_resiretur'])->update('tblresiretur', [
                'status_retur'      => 'Terima Retur',
                'tanggal_resiretur' => $now,
                'id_pegawai'        => $user_id,
            ]);
            return ['ok' => "Resi $noresi ditandai Terima Retur"];
        }

        if ($action === 'buka') {
            $this->db->where('id_resiretur', $row['id_resiretur'])->update('tblresiretur', [
                'status_retur'      => 'Buka Retur',
                'tanggal_resiretur' => $now,
                'id_pegawai'        => $user_id,
            ]);

            // Buat detail buka bila belum ada
            $exists = $this->db->where('resi_buka', $noresi)->count_all_results('tblbukaretur');
            if ($exists == 0) {
                $skus = [];
                $receipt = $this->db->select('id_printresi')
                    ->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
                if (!empty($receipt)) {
                    $skus = $this->db->select('sku, jumlah')
                        ->get_where('tbldetailprintresi', ['id_resi' => $receipt['id_printresi']])->result();
                }
                if (!empty($skus)) {
                    foreach ($skus as $s) {
                        $this->db->insert('tblbukaretur', [
                            'status_buka' => 'Buka Retur', 'status_detail_buka' => 'DEFAULT',
                            'resi_buka' => $noresi, 'sku' => $s->sku, 'qty' => $s->jumlah,
                            'sumber_input' => 'laporan', 'hasil_scan_buka' => $noresi,
                            'tanggal_buka_retur' => $now, 'id_pegawai' => $user_id, 'created_at' => $now,
                        ]);
                    }
                } else {
                    $this->db->insert('tblbukaretur', [
                        'status_buka' => 'Buka Retur', 'status_detail_buka' => 'DEFAULT',
                        'resi_buka' => $noresi, 'sku' => null, 'qty' => null,
                        'sumber_input' => 'laporan', 'hasil_scan_buka' => $noresi,
                        'tanggal_buka_retur' => $now, 'id_pegawai' => $user_id, 'created_at' => $now,
                    ]);
                }
            }
            return ['ok' => "Resi $noresi ditandai Buka Retur"];
        }

        return ['error' => 'Aksi tidak valid'];
    }

    // ==================== IMPORT / SUNTIK RETUR DARI EXCEL ====================

    /**
     * Normalisasi nama kurir dari Excel ke id_kurir di tblkurir.
     * jnt-<nama> => JNT, shopee => SHOPEE, sisanya dicocokkan by nama.
     * @return array [id_kurir, found(bool)]
     */
    private function _normalize_kurir($raw, $kurir_map)
    {
        $name = strtoupper(trim((string) $raw));
        if ($name === '' || $name === 'N/A' || $name === '-') return [0, false]; // blank -> N/A

        // Semua "LEX" / "LEX - XXX" = LAZADA (Lazada Express)
        if (strpos($name, 'LEX') !== false) {
            return [$kurir_map['LAZADA'] ?? 0, isset($kurir_map['LAZADA'])];
        }

        $hit = function ($key) use ($kurir_map) {
            return [$kurir_map[$key] ?? 0, isset($kurir_map[$key])];
        };

        if (strpos($name, 'JNT') !== false || strpos($name, 'J&T') !== false) return $hit('JNT');
        if (strpos($name, 'SPX') !== false || strpos($name, 'SHOPEE') !== false) return $hit('SHOPEE');
        if (strpos($name, 'JNE') !== false) return $hit('JNE');
        if (strpos($name, 'NINJA') !== false) return $hit('NINJA');
        if (strpos($name, 'GOTO') !== false || strpos($name, 'GO-TO') !== false || strpos($name, 'GO TO') !== false) return $hit('GOTO');
        if (strpos($name, 'SICEPAT') !== false) return $hit('SICEPAT');
        if (strpos($name, 'ANTERAJA') !== false) return $hit('ANTERAJA');
        if (strpos($name, 'IDEXPRESS') !== false || strpos($name, 'ID EXPRESS') !== false) return $hit('ID EXPRESS');
        if (strpos($name, 'LAZADA') !== false) return $hit('LAZADA');
        if (strpos($name, 'WAHANA') !== false) return $hit('WAHANA');
        if (strpos($name, 'SAPX') !== false) return $hit('SAPX');
        if (strpos($name, 'REX') !== false) return $hit('REX');

        // exact match nama kurir
        if (isset($kurir_map[$name])) return [$kurir_map[$name], true];

        // partial match
        foreach ($kurir_map as $kname => $kid) {
            if ($kname !== '' && (strpos($name, $kname) !== false || strpos($kname, $name) !== false)) {
                return [$kid, true];
            }
        }
        return [0, false];
    }

    /**
     * Parse kolom TOKO dari Excel menjadi [id_marketplace|null, nama_toko].
     * Contoh: "Shop | Tokopedia - TT YARRA STORE" => [Tiktok, "TT YARRA STORE"]
     *         "Shopee - Yarra Store"             => [Shopee, "Yarra Store"]
     * Prefix "Shop |" dianggap TikTok Shop. Toko = teks setelah " - " terakhir.
     */
    private function _parse_toko_marketplace($raw, $mp_map)
    {
        $raw = trim((string) $raw);
        if ($raw === '') return [null, null];

        $low = strtolower($raw);

        // Deteksi marketplace
        $mpname = null;
        if (preg_match('/^shop\s*\|/', $low) || strpos($low, 'tiktok') !== false || strpos($low, 'tt shop') !== false) {
            $mpname = 'tiktok';
        } elseif (strpos($low, 'shopee') !== false) {
            $mpname = 'shopee';
        } elseif (strpos($low, 'tokopedia') !== false || strpos($low, 'tokped') !== false) {
            $mpname = 'tokopedia';
        } elseif (strpos($low, 'lazada') !== false) {
            $mpname = 'lazada';
        } elseif (strpos($low, 'akulaku') !== false) {
            $mpname = 'akulaku';
        } elseif (strpos($low, 'reseller') !== false) {
            $mpname = 'reseller';
        } elseif (strpos($low, 'shop') !== false) {
            $mpname = 'tiktok'; // "Shop" tanpa shopee dianggap TikTok Shop
        }
        $mp_id = ($mpname !== null && isset($mp_map[$mpname])) ? $mp_map[$mpname] : null;

        // Ambil nama toko: teks setelah " - " terakhir
        $toko = $raw;
        if (strpos($raw, ' - ') !== false) {
            $parts = explode(' - ', $raw);
            $toko = trim(end($parts));
        }
        // Bersihkan sisa prefix "X | "
        if (strpos($toko, '|') !== false) {
            $p = explode('|', $toko);
            $toko = trim(end($p));
        }
        if ($toko === '') $toko = null;

        return [$mp_id, $toko];
    }

    /**
     * Suntik data retur dari Excel (sudah dimapping di controller).
     * - Tanggal terima  -> buat/upsert tblresiretur (Terima Retur)
     * - Tanggal buka     -> insert tblbukaretur (Buka Retur, harga) + status jadi Buka Retur
     * - Kurir dinormalkan; resi dicari di tblprintresi (kalau ada id_resi/marketplace terisi)
     *
     * @return array ringkasan hasil import
     */
    function import_retur_excel($rows, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $summary = [
            'total'           => 0,
            'tadro'           => 0,  // baris status Retur Tadro (belum terima & belum buka)
            'terima'          => 0,  // baris status Terima Retur (sudah terima, belum buka)
            'buka'            => 0,  // baris Buka Retur (sudah dibuka)
            'resi_baru'       => 0,
            'resi_update'     => 0,
            'resi_not_found'  => 0,
            'kurir_not_found' => 0,
            'skipped'         => 0,
        ];

        // Cache map kurir (nama uppercase => id)
        $kurir_map = [];
        foreach ($this->db->select('id_kurir, nama_kurir')->get('tblkurir')->result() as $k) {
            $kurir_map[strtoupper(trim($k->nama_kurir))] = $k->id_kurir;
        }

        // Cache map marketplace (nama lowercase => id)
        $mp_map = [];
        foreach ($this->db->select('id_marketplace, nama_marketplace')->get('tblmarketplace')->result() as $m) {
            $mp_map[strtolower(trim($m->nama_marketplace))] = $m->id_marketplace;
        }

        // Bungkus semua insert/update dalam 1 transaksi -> commit sekali (jauh lebih cepat)
        $this->db->trans_start();

        foreach ($rows as $r) {
            $noresi = strtoupper(trim($r['noresi'] ?? ''));
            if ($noresi === '') { $summary['skipped']++; continue; }

            $tgl_terima = !empty($r['tgl_terima'])  ? $r['tgl_terima']  : null;
            $tgl_buka   = !empty($r['tgl_buka'])    ? $r['tgl_buka']    : null;
            $tgl_pesan  = !empty($r['tgl_pesanan']) ? $r['tgl_pesanan'] : null;

            $summary['total']++;

            $sku        = trim((string) ($r['sku'] ?? ''));
            $qty        = is_numeric($r['qty'] ?? null) ? (int) $r['qty'] : null;
            $no_pesanan = trim((string) ($r['no_pesanan'] ?? ''));
            $harga      = is_numeric($r['harga'] ?? null) ? $r['harga'] : null;

            // Parse kolom TOKO -> marketplace + nama toko bersih
            list($mp_id, $toko) = $this->_parse_toko_marketplace($r['toko'] ?? '', $mp_map);

            list($id_kurir, $kurir_found) = $this->_normalize_kurir($r['kurir'] ?? '', $kurir_map);
            if (!$kurir_found) $summary['kurir_not_found']++;

            // Cari resi di tblprintresi
            $receipt = $this->db->select('id_printresi, id_marketplace')
                ->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
            if (empty($receipt)) {
                $summary['resi_not_found']++;
                $id_resi = 0;
                $id_marketplace = $mp_id; // pakai hasil parse Excel karena resi tak ada di sistem
            } else {
                $id_resi = $receipt['id_printresi'];
                $id_marketplace = !empty($receipt['id_marketplace']) ? $receipt['id_marketplace'] : $mp_id;
            }

            // Tentukan status pipeline: Retur Tadro -> Terima Retur -> Buka Retur
            $has_buka   = !empty($tgl_buka);
            $has_terima = !empty($tgl_terima);
            if ($has_buka) {
                $status_retur = 'Buka Retur';
                $tanggal_resiretur = $tgl_terima ?: $tgl_buka;
            } elseif ($has_terima) {
                $status_retur = 'Terima Retur';
                $tanggal_resiretur = $tgl_terima;
            } else {
                // Belum terima & belum buka = RETUR TADRO (acuan tanggal: tgl pesanan / waktu upload)
                $status_retur = 'Retur Tadro';
                $tanggal_resiretur = $tgl_pesan ?: $now;
            }

            // Hitung per status baris
            if ($status_retur === 'Retur Tadro')      $summary['tadro']++;
            elseif ($status_retur === 'Terima Retur') $summary['terima']++;

            // Upsert tblresiretur per noresi (1 baris per resi), tanpa downgrade status
            $rank = ['Retur Tadro' => 1, 'Terima Retur' => 2, 'Buka Retur' => 3];
            $existing = $this->db->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', ['noresi' => $noresi])->row_array();
            if (empty($existing)) {
                $this->db->insert('tblresiretur', [
                    'tanggal_resiretur' => $tanggal_resiretur,
                    'sudah_cetak'       => '',
                    'id_resi'           => $id_resi,
                    'id_kurir'          => $id_kurir,
                    'id_pegawai'        => $user_id,
                    'id_marketplace'    => $id_marketplace,
                    'noresi'            => $noresi,
                    'status_detail'     => null,
                    'status_retur'      => $status_retur,
                ]);
                $summary['resi_baru']++;
            } else {
                $upd = ['id_kurir' => $id_kurir];
                if (!empty($id_resi)) $upd['id_resi'] = $id_resi;
                if (!empty($id_marketplace)) $upd['id_marketplace'] = $id_marketplace;
                // hanya naikkan status (jangan turunkan)
                $cur = $existing['status_retur'] ?: 'Retur Tadro';
                if (($rank[$status_retur] ?? 0) > ($rank[$cur] ?? 0)) {
                    $upd['status_retur'] = $status_retur;
                    $upd['tanggal_resiretur'] = $tanggal_resiretur;
                }
                $this->db->where('id_resiretur', $existing['id_resiretur'])->update('tblresiretur', $upd);
                $summary['resi_update']++;
            }

            // Detail buka retur (per resi + sku)
            if ($has_buka) {
                $exists_buka = $this->db
                    ->get_where('tblbukaretur', ['resi_buka' => $noresi, 'sku' => $sku])->row_array();
                $buka_data = [
                    'status_buka'        => 'Buka Retur',
                    'status_detail_buka' => 'DEFAULT',
                    'resi_buka'          => $noresi,
                    'sku'                => $sku,
                    'qty'                => $qty,
                    'harga'              => $harga,
                    'no_pesanan'         => $no_pesanan ?: null,
                    'toko'               => $toko ?: null,
                    'sumber_input'       => 'import',
                    'hasil_scan_buka'    => $noresi,
                    'tanggal_buka_retur' => $tgl_buka,
                    'id_pegawai'         => $user_id,
                ];
                if (empty($exists_buka)) {
                    $buka_data['created_at'] = $now;
                    $this->db->insert('tblbukaretur', $buka_data);
                } else {
                    $buka_data['updated_at'] = $now;
                    $this->db->where('id_bukaretur', $exists_buka['id_bukaretur'])
                        ->update('tblbukaretur', $buka_data);
                }
                $summary['buka']++;
            }
        }

        $this->db->trans_complete();
        $summary['db_success'] = $this->db->trans_status();

        return $summary;
    }

    // ==================== VALIDASI / REKONSILIASI JUBELIO ====================

    /**
     * Simpan satu batch data hasil parsing Excel Jubelio ke tblreturjubelio.
     * Sekaligus menandai match_resi / match_pesanan / found_in_iresis.
     *
     * @param array  $rows    array of associative rows (sudah dimapping di controller)
     * @param string $batch_id
     * @param int    $user_id
     * @return array ['inserted' => int, 'matched' => int]
     */
    function insert_jubelio_batch($rows, $batch_id, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $inserted = 0;
        $matched = 0;
        $insert_data = array();

        $this->db->trans_start();

        foreach ($rows as $r) {
            $no_resi = strtoupper(trim($r['no_resi'] ?? ''));
            $no_pesanan = trim($r['no_pesanan'] ?? '');

            // Lewati baris hantu / kosong (file Jubelio bisa punya ribuan baris kosong)
            if ($no_resi === '' && $no_pesanan === '') {
                continue;
            }

            // Cek apakah resi sudah discan retur di iresis
            $match_resi = 0;
            $found_in_iresis = 0;
            if ($no_resi !== '') {
                $found_in_iresis = $this->db
                    ->where('noresi', $no_resi)
                    ->count_all_results('tblresiretur') > 0 ? 1 : 0;
                $match_resi = $this->db
                    ->where('noresi', $no_resi)
                    ->count_all_results('tblprintresi') > 0 ? 1 : 0;
            }

            // Cek apakah no_pesanan dikenal di iresis
            $match_pesanan = 0;
            if ($no_pesanan !== '') {
                $match_pesanan = $this->db
                    ->where('no_pesanan', $no_pesanan)
                    ->count_all_results('tbldetailprintresi') > 0 ? 1 : 0;
            }

            if ($found_in_iresis) {
                $matched++;
            }

            $insert_data[] = array(
                'batch_id'        => $batch_id,
                'no_resi'         => $no_resi,
                'no_pesanan'      => $no_pesanan,
                'sku'             => $r['sku'] ?? null,
                'nama_barang'     => $r['nama_barang'] ?? null,
                'qty'             => is_numeric($r['qty'] ?? null) ? (int) $r['qty'] : null,
                'amount'          => is_numeric($r['amount'] ?? null) ? $r['amount'] : null,
                'marketplace'     => $r['marketplace'] ?? null,
                'nama_toko'       => $r['nama_toko'] ?? null,
                'kurir'           => $r['kurir'] ?? null,
                'status_jubelio'  => $r['status_jubelio'] ?? null,
                'tanggal_retur'   => $r['tanggal_retur'] ?? null,
                'match_resi'      => $match_resi,
                'match_pesanan'   => $match_pesanan,
                'found_in_iresis' => $found_in_iresis,
                'uploaded_by'     => $user_id,
                'uploaded_at'     => $now,
            );
            $inserted++;

            // Insert per 500 baris agar hemat memori
            if (count($insert_data) >= 500) {
                $this->db->insert_batch('tblreturjubelio', $insert_data);
                $insert_data = array();
            }
        }

        if (!empty($insert_data)) {
            $this->db->insert_batch('tblreturjubelio', $insert_data);
        }

        $this->db->trans_complete();

        return array('inserted' => $inserted, 'matched' => $matched);
    }

    /**
     * List data mentah Jubelio yang sudah masuk (server-side DataTable).
     */
    function get_jubelio_list($data, $start_date = null, $end_date = null)
    {
        $this->_build_jubelio_list_query($data, $start_date, $end_date);

        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('j.id_jubelio', 'DESC');
        }

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    function get_total_jubelio_list($data, $start_date = null, $end_date = null)
    {
        $this->_build_jubelio_list_query($data, $start_date, $end_date);
        return $this->db->count_all_results();
    }

    private function _build_jubelio_list_query($data, $start_date = null, $end_date = null)
    {
        $this->db->select('
            j.id_jubelio,
            j.no_resi,
            j.no_pesanan,
            j.sku,
            j.nama_barang,
            j.qty,
            j.marketplace,
            j.nama_toko,
            j.kurir,
            j.status_jubelio,
            j.tanggal_retur,
            j.found_in_iresis,
            j.uploaded_at
        ');
        $this->db->from('tblreturjubelio j');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) >=', $start_date);
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) <=', $end_date);
        }

        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('j.no_resi', $data['search']);
            $this->db->or_like('j.no_pesanan', $data['search']);
            $this->db->or_like('j.sku', $data['search']);
            $this->db->or_like('j.nama_barang', $data['search']);
            $this->db->or_like('j.marketplace', $data['search']);
            $this->db->or_like('j.nama_toko', $data['search']);
            $this->db->group_end();
        }
    }

    /**
     * Ambil data retur iresis (per no_resi) untuk rekonsiliasi, dalam rentang
     * tanggal terima retur. Termasuk kategori (Terima Retur / Buka Retur).
     */
    function get_iresis_recon($start_date, $end_date, $id_kurir = null)
    {
        $this->db->select('
            tr.noresi,
            tr.status_retur,
            tr.status_detail,
            MIN(tr.tanggal_resiretur) as tanggal_resiretur,
            mp.nama_marketplace,
            pr.toko as nama_toko,
            kr.nama_kurir,
            GROUP_CONCAT(DISTINCT dp.no_pesanan ORDER BY dp.no_pesanan SEPARATOR ", ") as no_pesanan,
            GROUP_CONCAT(DISTINCT dp.sku ORDER BY dp.sku SEPARATOR ", ") as sku_list,
            COALESCE(SUM(dp.jumlah), 0) as total_qty
        ');
        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }
        if (!empty($id_kurir)) {
            $this->db->where('tr.id_kurir', $id_kurir);
        }

        $this->db->group_by('tr.noresi');

        return $this->db->get()->result();
    }

    /**
     * Set / batalkan verifikasi sebuah resi (Step 3 Accounting).
     */
    function set_verifikasi($no_resi, $verified, $user_id, $catatan = null)
    {
        $no_resi = strtoupper(trim($no_resi));
        if ($no_resi === '') return false;

        $existing = $this->db->get_where('tblreturverifikasi', ['no_resi' => $no_resi])->row_array();
        $data = [
            'verified'    => $verified ? 1 : 0,
            'catatan'     => $catatan,
            'verified_by' => $user_id,
            'verified_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($existing)) {
            $this->db->where('id', $existing['id'])->update('tblreturverifikasi', $data);
        } else {
            $data['no_resi'] = $no_resi;
            $this->db->insert('tblreturverifikasi', $data);
        }
        return true;
    }

    /**
     * Ambil semua resi yang sudah diverifikasi (untuk merge ke rekonsiliasi).
     */
    function get_verifikasi_list()
    {
        return $this->db->select('v.no_resi, v.verified, v.catatan, v.verified_at, u.username', false)
            ->from('tblreturverifikasi v')
            ->join('tbluser u', 'u.id_user = v.verified_by', 'left')
            ->where('v.verified', 1)
            ->get()->result();
    }

    /**
     * Ambil data Jubelio (per no_resi) untuk rekonsiliasi, dalam rentang tanggal.
     * Fallback ke uploaded_at jika tanggal_retur kosong.
     */
    function get_jubelio_recon($start_date, $end_date)
    {
        $this->db->select('
            j.no_resi as noresi,
            GROUP_CONCAT(DISTINCT j.no_pesanan ORDER BY j.no_pesanan SEPARATOR ", ") as no_pesanan,
            GROUP_CONCAT(DISTINCT j.sku ORDER BY j.sku SEPARATOR ", ") as sku_list,
            COALESCE(SUM(j.qty), 0) as total_qty,
            COALESCE(SUM(j.amount), 0) as total_amount,
            MAX(j.marketplace) as nama_marketplace,
            MAX(j.nama_toko) as nama_toko,
            MAX(j.kurir) as nama_kurir,
            MAX(j.status_jubelio) as status_jubelio,
            MIN(COALESCE(j.tanggal_retur, j.uploaded_at)) as tanggal_retur
        ');
        $this->db->from('tblreturjubelio j');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) >=', $start_date);
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) <=', $end_date);
        }

        $this->db->where("j.no_resi !=", '');
        $this->db->group_by('j.no_resi');

        return $this->db->get()->result();
    }
}
