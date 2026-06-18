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
    function get_laporan_terima_retur($data, $start_date, $end_date, $id_kurir = null)
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

        // Select with joins
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

        // Pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count Laporan Terima Retur
     */
    function get_total_laporan_terima_retur($data, $start_date, $end_date, $id_kurir = null)
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
            mp.nama_marketplace,
            kr.nama_kurir,
            dp.no_pesanan,
            pr.toko as nama_toko,
            br.sku as sku,
            br.qty as jumlah
        ');

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Filter by kurir
        if (!empty($id_kurir)) {
            $this->db->where('pr.id_kurir', $id_kurir);
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
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Filter by kurir
        if (!empty($id_kurir)) {
            $this->db->where('pr.id_kurir', $id_kurir);
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
            mp.nama_marketplace,
            kr.nama_kurir,
            dp.no_pesanan,
            pr.toko as nama_toko,
            br.sku as sku,
            br.qty as jumlah
        ');

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = pr.id_kurir', 'left');

        // Filter by date range
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        // Filter by kurir
        if (!empty($id_kurir)) {
            $this->db->where('pr.id_kurir', $id_kurir);
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

        return array('inserted' => $inserted, 'matched' => $matched);
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
