<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_fcd extends CI_Model
{

    /**
     * Guarded single-row UPDATE keyed on a primary-key column.
     *
     * Second line of defense against the 2026-07-06 data incident: a backup made
     * with CREATE TABLE ... AS SELECT dropped PRIMARY KEY/AUTO_INCREMENT, so every
     * freshly inserted row received id_resiretur = 0. A single ordinary
     * "UPDATE tblresiretur ... WHERE id_resiretur = 0" then rewrote *thousands* of
     * rows at once (mass-converting standard scans to komplain / moving pegawai).
     *
     * This helper refuses to run when the target id is not a positive integer, and
     * hard-caps the statement to a single row (LIMIT 1), so even a duplicated key
     * can never fan out into a mass update again.
     *
     * @param  string $table
     * @param  string $pk_column
     * @param  mixed  $id
     * @param  array  $data
     * @return bool    TRUE if the update ran, FALSE if it was blocked.
     */
    public function guarded_pk_update($table, $pk_column, $id, $data)
    {
        $safe_id = (int) $id;
        if ($safe_id <= 0) {
            log_message('error', sprintf(
                'guarded_pk_update BLOCKED mass-update: %s WHERE %s = %s (data: %s)',
                $table,
                $pk_column,
                var_export($id, true),
                json_encode($data)
            ));
            return FALSE;
        }

        // 4th arg = LIMIT 1: never touch more than the one intended row.
        $this->db->where($pk_column, $safe_id)->update($table, $data, NULL, 1);
        return TRUE;
    }

    /**
     * Sisa qty sebuah SKU yang masih boleh diproses di Buka Retur.
     * = qty pesanan (tbldetailprintresi) - qty yang sudah diproses (tblbukaretur).
     * Mengembalikan NULL bila qty pesanan tidak diketahui (tanpa batas).
     */
    public function sisa_qty_buka($noresi, $sku, $is_komplain = 0)
    {
        $receipt = $this->db->select('id_printresi')
            ->get_where('tblprintresi', ['noresi' => $noresi])->row();
        if (empty($receipt)) return null;

        $pesan = $this->db->select_sum('jumlah', 'total')
            ->get_where('tbldetailprintresi', ['id_resi' => $receipt->id_printresi, 'sku' => $sku])->row();
        if (empty($pesan) || $pesan->total === null) return null;

        $sudah = $this->db->select_sum('qty', 'total')
            ->get_where('tblbukaretur', [
                'resi_buka'   => $noresi,
                'sku'         => $sku,
                'is_komplain' => $is_komplain,
            ])->row();

        return (int) $pesan->total - (int) ($sudah->total ?? 0);
    }

    /**
     * Simpan 1 baris Buka Retur. Bila SKU + status yang sama sudah ada untuk resi
     * ini, qty-nya DITAMBAHKAN ke baris tersebut (bukan bikin baris baru), sehingga
     * 1 SKU bisa dipecah beberapa status tanpa menabrak UNIQUE index.
     * Return FALSE bila bentrok UNIQUE (race) — pemanggil membalas 409.
     */
    public function simpan_buka_sku(array $data, $user_id)
    {
        $now = date('Y-m-d H:i:s');
        $exist = $this->db->get_where('tblbukaretur', [
            'resi_buka'          => $data['resi_buka'],
            'sku'                => $data['sku'],
            'is_komplain'        => $data['is_komplain'] ?? 0,
            'status_detail_buka' => $data['status_detail_buka'],
        ])->row();

        if (!empty($exist)) {
            $upd = [
                'qty'                => (int) $exist->qty + (int) $data['qty'],
                'tanggal_buka_retur' => $data['tanggal_buka_retur'] ?? $now,
                'id_pegawai'         => $user_id,
                'updated_at'         => $now,
            ];
            // Isi keterangan tambahan hanya bila baris lama belum punya.
            foreach (['sku_pergantian', 'alasan_ditolak'] as $kolom) {
                if (!empty($data[$kolom]) && empty($exist->$kolom)) $upd[$kolom] = $data[$kolom];
            }
            return $this->guarded_pk_update('tblbukaretur', 'id_bukaretur', $exist->id_bukaretur, $upd);
        }

        // Race-safe: bentrok UNIQUE tidak boleh memunculkan halaman error PHP.
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $ok  = $this->db->insert('tblbukaretur', $data);
        $err = $this->db->error();
        $this->db->db_debug = $prev_debug;

        return $ok && !(isset($err['code']) && (int) $err['code'] === 1062);
    }

    /**
     * Save Terima Retur - Creates new record(s) with status "Terima Retur"
     * Ensures only 1 row per receipt in tblresiretur
     */
    function save_terima_retur($retur, $user)
    {
        $is_update = !empty($retur['is_update']);
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
                ->select('id_printresi, id_kurir, id_marketplace, noresi, status_pesanan, batal')
                ->get_where('tblprintresi', ['noresi' => $noresi])
                ->row_array();

            if (empty($receipt)) {
                $error_messages[] = "Resi $noresi tidak ditemukan";
                continue;
            }

            // Check if paket is canceled
            $status_pesanan = strtoupper(trim($receipt['status_pesanan'] ?? ''));
            if (
                stripos($status_pesanan, 'CANCEL') !== false ||
                (string)($receipt['batal'] ?? '') === '1'
            ) {
                $error_messages[] = "Resi $noresi tidak dapat diterima karena status paket adalah CANCEL";
                continue;
            }

            $is_complain = isset($retur['is_complain']) ? $retur['is_complain'] : 0;
            // Check if retur already exists
            $retur_exist = $this->db
                ->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', ['id_resi' => $receipt['id_printresi'], 'is_komplain' => $is_complain])
                ->row_array();

            if (!empty($retur_exist)) {
                if ($is_update) {
                    // Reset existing return record for update mode.
                    // Batasi ke is_komplain yang sama: tanpa ini scan ulang di menu
                    // Update Retur ikut menghapus baris Buka Retur milik menu komplain.
                    $this->db->delete('tblbukaretur', ['resi_buka' => $noresi, 'is_komplain' => $is_complain]);

                    $ok = $this->guarded_pk_update('tblresiretur', 'id_resiretur', $retur_exist['id_resiretur'], [
                        'status_retur' => 'Terima Retur',
                        'status_detail' => null,
                        'tanggal_resiretur' => date('Y-m-d H:i:s'),
                        'id_pegawai' => $user,
                        'is_update' => 1,
                        'is_komplain' => $is_complain
                    ]);
                    if ($ok) {
                        $success_count++;
                    } else {
                        $error_messages[] = "Resi $noresi gagal diupdate (id retur tidak valid)";
                    }
                    continue;
                } else {
                    $error_messages[] = "Resi $noresi sudah diinput di Terima Retur";
                    continue;
                }
            }

            // Guard salah menu: resi tidak boleh punya catatan retur biasa DAN
            // komplain sekaligus (dulu bikin 1 resi muncul di kedua Laporan
            // Verifikasi). Kalau sudah tercatat di menu sebelah, tolak dengan
            // pesan jelas agar operator memakai menu yang benar.
            $lawan = $this->db
                ->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', [
                    'id_resi'     => $receipt['id_printresi'],
                    'is_komplain' => $is_complain ? 0 : 1,
                ])->row_array();
            if (!empty($lawan)) {
                $asal = $is_complain ? 'Retur Biasa' : 'Retur Komplain';
                $menu = $is_complain ? 'Scan Retur / Update Retur' : 'Scan Retur Komplain / Update Retur Komplain';
                $error_messages[] = "Resi $noresi sudah tercatat sebagai $asal (status: "
                    . ($lawan['status_retur'] ?: '-') . "). Gunakan menu $menu, atau hubungi admin bila jenisnya perlu diubah";
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
                'noresi' => $receipt['noresi'],
                'is_komplain' => $is_complain
            ];

            $this->db->insert('tblresiretur', $insert_data);
            $success_count++;
        }

        // Build response
        if ($success_count === 0) {
            $is_double = false;
            foreach ($error_messages as $em) {
                if (stripos($em, 'sudah diinput') !== false) {
                    $is_double = true;
                    break;
                }
            }
            $code = $is_double ? 409 : 400; // 409 Conflict for double
            return ['error' => TRUE, 'code' => $code, 'message' => 'Tidak ada resi yang berhasil diproses. ' . implode(', ', $error_messages)];
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
        $is_update = !empty($retur['is_update']);
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

            $is_complain = isset($retur['is_complain']) ? $retur['is_complain'] : 0;
            // Check if retur exists with status "Terima Retur"
            $retur_exist = $this->db
                ->select('id_resiretur, status_retur')
                ->get_where('tblresiretur', ['id_resi' => $receipt['id_printresi'], 'is_komplain' => $is_complain])
                ->row_array();

            if (empty($retur_exist)) {
                // Beri petunjuk bila ternyata resi tercatat di jenis sebelah (salah menu).
                $lawan = $this->db->get_where('tblresiretur', [
                    'id_resi'     => $receipt['id_printresi'],
                    'is_komplain' => $is_complain ? 0 : 1,
                ])->row_array();
                if (!empty($lawan)) {
                    $asal = $is_complain ? 'Retur Biasa' : 'Retur Komplain';
                    $error_messages[] = "Resi $noresi tercatat sebagai $asal, bukan di menu ini. Gunakan menu yang sesuai";
                } else {
                    $error_messages[] = "Resi $noresi belum diinput di Terima Retur";
                }
                continue;
            }

            if ($retur_exist['status_retur'] !== 'Terima Retur' && !$is_update) {
                $error_messages[] = "Resi $noresi sudah diproses dengan status " . $retur_exist['status_retur'];
                continue;
            }

            // Update existing record to Buka Retur (preserve original tanggal_resiretur as receive date)
            $update_data = [
                'status_retur' => 'Buka Retur',
                'status_detail' => $retur['status_detail'],
                'id_pegawai' => $user,
                'is_komplain' => $is_complain
            ];

            if ($this->guarded_pk_update('tblresiretur', 'id_resiretur', $retur_exist['id_resiretur'], $update_data)) {
                $success_count++;
            } else {
                $error_messages[] = "Resi $noresi gagal diupdate (id retur tidak valid)";
            }
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

        // Filter by status (include Buka Retur when status is Terima Retur)
        if ($status === 'Terima Retur') {
            $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);
        } else {
            $this->db->where('tr.status_retur', $status);
        }

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
        if ($status === 'Terima Retur') {
            $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);
        } else {
            $this->db->where('tr.status_retur', $status);
        }
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

        // Filter by Terima/Buka Retur (include Buka Retur so historical receive date is shown)
        $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);

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
            COALESCE(dr.no_rak, s.no_rak) as no_rak,
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
        // $this->db->having('jumlah >', 0);

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

        // Tagihan total
        $row = $this->db->select('COALESCE(SUM(harga*qty),0) as total', false)
            ->where('tanggal_buka_retur >=', $start)->where('tanggal_buka_retur <=', $end)
            ->get('tblbukaretur')->row();
        $r['tagihan'] = $row ? (float) $row->total : 0;

        // ---- TAMBAHAN UNTUK MANAGER ----

        // Completion rate = buka / total_terima (terima + buka)
        $total_masuk = $r['belum_dibuka'] + $r['buka'];
        $r['completion_rate'] = $total_masuk > 0 ? round($r['buka'] / $total_masuk * 100, 1) : 0;

        // Rata-rata waktu proses (Terima -> Buka) dalam jam
        $avg_row = $this->db->select(
            'AVG(TIMESTAMPDIFF(HOUR, tr.tanggal_resiretur, br.tanggal_buka_retur)) as avg_jam', false
        )->from('tblresiretur tr')
        ->join('tblbukaretur br', 'br.resi_buka = tr.noresi', 'inner')
        ->where('br.tanggal_buka_retur >=', $start)->where('br.tanggal_buka_retur <=', $end)
        ->where('tr.tanggal_resiretur IS NOT NULL', null, false)
        ->get()->row();
        $r['avg_proses_jam'] = $avg_row ? round((float)$avg_row->avg_jam, 1) : 0;

        // Overdue: retur diterima (belum dibuka) lebih dari 48 jam sejak tanggal terima
        $this->db->where('status_retur', 'Terima Retur')
            ->where('tanggal_resiretur < ', date('Y-m-d H:i:s', strtotime('-48 hours')));
        $r['overdue'] = $this->db->count_all_results('tblresiretur');

        // Retur Tadro semua waktu yang masih pending (belum diterima, any date)
        $this->db->where('status_retur', 'Retur Tadro');
        $r['tadro_all'] = $this->db->count_all_results('tblresiretur');

        // Nilai tagihan rata-rata per retur (buka)
        $r['tagihan_rata'] = $r['buka'] > 0 ? round($r['tagihan'] / $r['buka'], 0) : 0;

        return $r;
    }

    /**
     * Ambil daftar resi yang sudah diterima tapi belum dibuka paling lama (oldest pending).
     */
    function dashboard_oldest_pending($limit = 10)
    {
        return $this->db->select(
            'tr.noresi, tr.tanggal_resiretur, kr.nama_kurir,
             TIMESTAMPDIFF(HOUR, tr.tanggal_resiretur, NOW()) as jam_tunggu', false
        )->from('tblresiretur tr')
        ->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left')
        ->where('tr.status_retur', 'Terima Retur')
        ->where('tr.tanggal_resiretur IS NOT NULL', null, false)
        ->order_by('tr.tanggal_resiretur', 'ASC')
        ->limit($limit)
        ->get()->result();
    }

    /**
     * Retur yang masuk per jam dalam sehari (untuk heatmap jam kerja).
     */
    function dashboard_hourly($start, $end)
    {
        return $this->db->select('HOUR(tanggal_resiretur) as jam, COUNT(*) as n', false)
            ->where('tanggal_resiretur >=', $start)->where('tanggal_resiretur <=', $end)
            ->group_by('HOUR(tanggal_resiretur)', false)
            ->order_by('jam', 'ASC')
            ->get('tblresiretur')->result();
    }

    /**
     * Tren mingguan / bulanan agregat.
     */
    function dashboard_complain_by_type($start, $end)
    {
        return $this->db->select("COALESCE(NULLIF(jenis_komplain,''),'-') as label, COUNT(*) as n", false)
            ->where('created_at >=', $start)->where('created_at <=', $end)
            ->group_by('jenis_komplain')->order_by('n', 'DESC')->limit(8)
            ->get('tblreturcomplain')->result();
    }


    function dashboard_trend($start, $end)
    {
        $terima = $this->db->select('DATE(tanggal_resiretur) as tgl, COUNT(*) as n', false)
            ->where_in('status_retur', ['Terima Retur', 'Buka Retur'])
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

        $row = $this->db->select('id_resiretur, status_retur, is_komplain')
            ->get_where('tblresiretur', ['noresi' => $noresi])->row_array();
        if (empty($row)) return ['error' => 'Resi tidak ditemukan di data retur'];
        $is_komplain = (int) ($row['is_komplain'] ?? 0);

        $now = date('Y-m-d H:i:s');

        if ($action === 'terima') {
            if (!$this->guarded_pk_update('tblresiretur', 'id_resiretur', $row['id_resiretur'], [
                'status_retur'      => 'Terima Retur',
                'tanggal_resiretur' => $now,
                'id_pegawai'        => $user_id,
            ])) {
                return ['error' => 'Data retur tidak valid (id_resiretur = 0). Update dibatalkan.'];
            }
            return ['ok' => "Resi $noresi ditandai Terima Retur"];
        }

        if ($action === 'buka') {
            if (!$this->guarded_pk_update('tblresiretur', 'id_resiretur', $row['id_resiretur'], [
                'status_retur'      => 'Buka Retur',
                // Keep original tanggal_resiretur as receive date
                'id_pegawai'        => $user_id,
            ])) {
                return ['error' => 'Data retur tidak valid (id_resiretur = 0). Update dibatalkan.'];
            }

            // Buat detail buka bila belum ada (dicek per jenis: biasa vs komplain)
            $exists = $this->db->where('resi_buka', $noresi)
                ->where('is_komplain', $is_komplain)->count_all_results('tblbukaretur');
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
                            'is_komplain' => $is_komplain,
                            'tanggal_buka_retur' => $now, 'id_pegawai' => $user_id, 'created_at' => $now,
                        ]);
                    }
                } else {
                    $this->db->insert('tblbukaretur', [
                        'status_buka' => 'Buka Retur', 'status_detail_buka' => 'DEFAULT',
                        'resi_buka' => $noresi, 'sku' => null, 'qty' => null,
                        'sumber_input' => 'laporan', 'hasil_scan_buka' => $noresi,
                        'is_komplain' => $is_komplain,
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
    private function _normalize_kurir($raw, $kurir_map, $tgl_pesan = null)
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

        if (strpos($name, 'JNT') !== false || strpos($name, 'J&T') !== false) {
            $dateStr = $tgl_pesan ? substr($tgl_pesan, 0, 10) : '';
            if ($dateStr !== '') {
                if ($dateStr < '2024-05-27') {
                    return $hit('JNT');
                } elseif ($dateStr >= '2024-05-27' && $dateStr < '2025-10-13') {
                    return $hit('JNT-FIERRA');
                } else {
                    return $hit('JNT-KAV-DPR');
                }
            } else {
                return $hit('JNT-KAV-DPR'); // default to latest
            }
        }
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

            list($id_kurir, $kurir_found) = $this->_normalize_kurir($r['kurir'] ?? '', $kurir_map, $tgl_pesan);
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
                if ($this->guarded_pk_update('tblresiretur', 'id_resiretur', $existing['id_resiretur'], $upd)) {
                    $summary['resi_update']++;
                }
            }

            // Detail buka retur (per resi + sku)
            if ($has_buka) {
                // Import selalu retur biasa (is_komplain=0) — sertakan di pencarian
                // agar tidak menimpa baris Buka Retur milik retur komplain.
                $exists_buka = $this->db
                    ->get_where('tblbukaretur', ['resi_buka' => $noresi, 'sku' => $sku, 'is_komplain' => 0])->row_array();
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
                    'is_komplain'        => 0,
                    'hasil_scan_buka'    => $noresi,
                    'tanggal_buka_retur' => $tgl_buka,
                    'id_pegawai'         => $user_id,
                ];
                if (empty($exists_buka)) {
                    $buka_data['created_at'] = $now;
                    $this->db->insert('tblbukaretur', $buka_data);
                } else {
                    $buka_data['updated_at'] = $now;
                    $this->guarded_pk_update('tblbukaretur', 'id_bukaretur', $exists_buka['id_bukaretur'], $buka_data);
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
        $updated  = 0;
        $matched  = 0;

        // Consolidate rows in memory by unique key (no_resi, no_pesanan, sku) to sum Qty and Amount
        $consolidated = [];
        foreach ($rows as $r) {
            $no_resi    = strtoupper(trim($r['no_resi'] ?? ''));
            $no_pesanan = trim($r['no_pesanan'] ?? '');
            $sku        = trim($r['sku'] ?? '');

            if ($no_resi === '' && $no_pesanan === '') {
                continue;
            }

            $key = $no_resi . '|' . $no_pesanan . '|' . $sku;
            if (!isset($consolidated[$key])) {
                $consolidated[$key] = $r;
                $consolidated[$key]['qty'] = is_numeric($r['qty'] ?? null) ? (int) $r['qty'] : 0;
                $consolidated[$key]['amount'] = is_numeric($r['amount'] ?? null) ? (float) $r['amount'] : 0.0;
            } else {
                $consolidated[$key]['qty'] += is_numeric($r['qty'] ?? null) ? (int) $r['qty'] : 0;
                $consolidated[$key]['amount'] += is_numeric($r['amount'] ?? null) ? (float) $r['amount'] : 0.0;
            }
        }
        $rows = array_values($consolidated);

        $this->db->trans_start();

        foreach ($rows as $r) {
            $no_resi    = strtoupper(trim($r['no_resi'] ?? ''));
            $no_pesanan = trim($r['no_pesanan'] ?? '');

            // Lewati baris hantu / kosong
            if ($no_resi === '' && $no_pesanan === '') {
                continue;
            }

            // Cek apakah resi sudah discan retur di iresis
            $match_resi      = 0;
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

            $sku         = $r['sku'] ?? null;
            $nama_barang = $r['nama_barang'] ?? null;
            $qty         = is_numeric($r['qty'] ?? null) ? (int) $r['qty'] : null;
            $amount      = is_numeric($r['amount'] ?? null) ? $r['amount'] : null;
            $marketplace = $r['marketplace'] ?? null;
            $nama_toko   = $r['nama_toko'] ?? null;
            $kurir       = $r['kurir'] ?? null;
            $status_j    = $r['status_jubelio'] ?? null;
            $status_r    = $r['status_retur'] ?? null;
            $tgl         = $r['tanggal_retur'] ?? null;

            // --- UPSERT: INSERT baru, atau UPDATE status saja jika sudah ada ---
            $sql = "INSERT INTO tblreturjubelio
                        (batch_id, no_resi, no_pesanan, sku, nama_barang, qty, amount,
                         marketplace, nama_toko, kurir, status_jubelio, status_retur,
                         tanggal_retur, match_resi, match_pesanan, found_in_iresis,
                         uploaded_by, uploaded_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        batch_id        = VALUES(batch_id),
                        nama_barang     = VALUES(nama_barang),
                        qty             = VALUES(qty),
                        amount          = VALUES(amount),
                        marketplace     = VALUES(marketplace),
                        nama_toko       = VALUES(nama_toko),
                        kurir           = VALUES(kurir),
                        status_jubelio  = VALUES(status_jubelio),
                        status_retur    = VALUES(status_retur),
                        tanggal_retur   = VALUES(tanggal_retur),
                        match_resi      = VALUES(match_resi),
                        match_pesanan   = VALUES(match_pesanan),
                        found_in_iresis = VALUES(found_in_iresis),
                        uploaded_by     = VALUES(uploaded_by),
                        uploaded_at     = VALUES(uploaded_at)";

            $this->db->query($sql, [
                $batch_id, $no_resi, $no_pesanan, $sku, $nama_barang, $qty, $amount,
                $marketplace, $nama_toko, $kurir, $status_j, $status_r,
                $tgl, $match_resi, $match_pesanan, $found_in_iresis,
                $user_id, $now,
            ]);

            $affected = $this->db->affected_rows();
            // MySQL ON DUPLICATE KEY: 1 = inserted, 2 = updated, 0 = no change
            if ($affected === 1) {
                $inserted++;
            } elseif ($affected >= 2) {
                $updated++;
            }
        }

        $this->db->trans_complete();

        return array('inserted' => $inserted, 'updated' => $updated, 'matched' => $matched);
    }

    /**
     * Baca file Excel "daftar retur penjualan" dari Jubelio dan kembalikan array baris
     * siap dipakai insert_jubelio_batch(). Dipakai oleh Retur::upload_jubelio (upload
     * manual) dan Cron::auto_upload_retur_jubelio (otomasi harian) agar logika parsing
     * tetap satu sumber.
     *
     * @param string $tmp_name path file Excel
     * @return array daftar baris ['no_resi','no_pesanan','sku',...]
     * @throws Exception bila format/kolom tidak sesuai
     */
    public function parse_jubelio_spreadsheet($tmp_name)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
            \PhpOffice\PhpSpreadsheet\IOFactory::identify($tmp_name)
        );
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tmp_name);
        $sheet = $spreadsheet->getActiveSheet();
        $raw   = $sheet->toArray(null, true, true, true);

        if (count($raw) < 2) {
            throw new \Exception('File tidak berisi data.');
        }

        // Petakan nama header -> huruf kolom dari baris pertama
        $header_row = array_shift($raw);
        $wanted = [
            'status_retur'    => 'status_retur',
            'tracking_number' => 'no_resi',
            'salesorder_no'   => 'no_pesanan',
            'QTY'             => 'qty',
            'SKU'             => 'sku',
            'Nama Barang'     => 'nama_barang',
            'amount'          => 'amount',
            'Tanggal'         => 'tanggal_retur',
            'Sumber'          => 'marketplace',
            'store'           => 'nama_toko',
            'Status'          => 'status_jubelio',
            'Kurir'           => 'kurir',
        ];
        $colmap = [];
        foreach ($header_row as $letter => $title) {
            $title = trim((string) $title);
            if ($title !== '' && isset($wanted[$title])) {
                $colmap[$wanted[$title]] = $letter;
            }
        }

        if (!isset($colmap['no_resi']) || !isset($colmap['no_pesanan'])) {
            throw new \Exception('Kolom wajib (tracking_number / salesorder_no) tidak ditemukan. Pastikan ini file "daftar retur penjualan" dari Jubelio.');
        }

        $rows = [];
        foreach ($raw as $r) {
            $row = [];
            foreach ($colmap as $field => $letter) {
                $val = $r[$letter] ?? null;
                if ($field === 'tanggal_retur') {
                    $val = $this->_parse_excel_date($val);
                }
                $row[$field] = is_string($val) ? trim($val) : $val;
            }
            // Lewati baris kosong (file Jubelio sering punya ribuan baris hantu)
            if (empty($row['no_resi']) && empty($row['no_pesanan'])) {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Konversi nilai tanggal dari sel Excel (serial number / string) ke 'Y-m-d H:i:s'.
     */
    private function _parse_excel_date($val)
    {
        if ($val === null || $val === '') {
            return null;
        }
        // Sel Excel berupa serial number (tanggal asli Excel)
        if (is_numeric($val)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

        $val = trim((string) $val);
        if ($val === '' || strtoupper($val) === 'N/A' || $val === '-' || stripos($val, 'belum') !== false) {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
            'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y',
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/y', 'd-m-y', 'm/d/Y',
        ];
        foreach ($formats as $f) {
            $d = \DateTime::createFromFormat($f . '|', $val);
            if ($d !== false) {
                $err = \DateTime::getLastErrors();
                if (empty($err['warning_count']) && empty($err['error_count'])) {
                    return $d->format('Y-m-d H:i:s');
                }
            }
        }

        $ts = strtotime($val);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
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
            j.status_retur,
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
    function get_iresis_recon($start_date, $end_date, $id_kurir = null, $is_update = 0, $is_komplain = 0, $use_old = 0)
    {
        // Toggle "Data Lama": baca snapshot sebelum restore 2026-07-07 (opsional, default live)
        $tr_tbl = $use_old ? 'tblresiretur_corrupt_20260707' : 'tblresiretur';
        $br_tbl = $use_old ? 'tblbukaretur_corrupt_20260707' : 'tblbukaretur';

        $this->db->select('
            tr.noresi,
            tr.status_retur,
            tr.status_detail,
            MIN(tr.tanggal_resiretur) as tanggal_resiretur,
            mp.nama_marketplace,
            pr.toko as nama_toko,
            MAX(pr.status_pesanan) as status_pesanan,
            kr.nama_kurir,
            GROUP_CONCAT(DISTINCT dp.no_pesanan ORDER BY dp.no_pesanan SEPARATOR ", ") as no_pesanan,
            COALESCE(br.sku, GROUP_CONCAT(DISTINCT dp.sku ORDER BY dp.sku SEPARATOR ", ")) as sku_list,
            COALESCE(br.qty, SUM(dp.jumlah), 0) as total_qty,
            br.status_detail_buka,
            br.alasan_ditolak,
            br.sku_pergantian,
            br.id_bukaretur
        ', FALSE);
        $this->db->from("$tr_tbl tr");
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        // Sertakan is_komplain di kondisi join: baris Buka Retur komplain hanya boleh
        // menempel ke Terima Retur komplain, dan sebaliknya. Tanpa ini 1 baris buka
        // ikut muncul di Laporan Verifikasi Retur DAN Verifikasi Retur Komplain.
        // Tabel snapshot "Data Lama" belum punya kolom ini, jadi syarat dilewati.
        $join_kmp = $use_old ? '' : ' AND br.is_komplain = tr.is_komplain';
        $this->db->join("$br_tbl br", "br.resi_buka = tr.noresi{$join_kmp} AND (br.sku = dp.sku OR (dp.sku IS NULL AND br.sku IS NOT NULL))", 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('tr.tanggal_resiretur >=', $start_date);
            $this->db->where('tr.tanggal_resiretur <=', $end_date);
        }
        if (!empty($id_kurir)) {
            $this->db->where('tr.id_kurir', $id_kurir);
        }

        $this->db->where('tr.is_update', $is_update);
        $this->db->where('tr.is_komplain', $is_komplain);

        $this->db->group_by('tr.noresi');
        $this->db->group_by('br.id_bukaretur');

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
    function get_jubelio_recon($start_date, $end_date, $resi_list = [])
    {
        $select = '
            j.no_resi as noresi,
            GROUP_CONCAT(DISTINCT j.no_pesanan ORDER BY j.no_pesanan SEPARATOR ", ") as no_pesanan,
            GROUP_CONCAT(DISTINCT j.sku ORDER BY j.sku SEPARATOR ", ") as sku_list,
            COALESCE(SUM(j.qty), 0) as total_qty,
            COALESCE(SUM(j.amount), 0) as total_amount,
            MAX(j.marketplace) as nama_marketplace,
            MAX(j.nama_toko) as nama_toko,
            MAX(j.kurir) as nama_kurir,
            MAX(j.status_retur) as status_retur,
            MAX(j.status_jubelio) as status_jubelio,
            MIN(COALESCE(j.tanggal_retur, j.uploaded_at)) as tanggal_retur
        ';

        $rows = [];

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->select($select);
            $this->db->from('tblreturjubelio j');
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) >=', $start_date);
            $this->db->where('COALESCE(j.tanggal_retur, j.uploaded_at) <=', $end_date);
            $this->db->where("j.no_resi !=", '');
            $this->db->group_by('j.no_resi');
            foreach ($this->db->get()->result() as $r) {
                $rows[strtoupper(trim($r->noresi))] = $r;
            }
        }

        // Tambahan: resi yang cocok by no_resi tapi tanggal Jubelio-nya di luar
        // rentang (mis. upload terpisah). IN() dipecah per-batch 500 karena kalau
        // resi_list sampai ribuan item dalam satu IN(), query builder CodeIgniter
        // gagal compile (preg_match regex-too-large) dan seluruh laporan error
        // (lihat kasus 14 Agustus 2026: 1901 resi dalam sehari).
        if (!empty($resi_list)) {
            foreach (array_chunk(array_unique($resi_list), 500) as $chunk) {
                $this->db->select($select);
                $this->db->from('tblreturjubelio j');
                $this->db->where_in('j.no_resi', $chunk);
                $this->db->where("j.no_resi !=", '');
                $this->db->group_by('j.no_resi');
                foreach ($this->db->get()->result() as $r) {
                    $key = strtoupper(trim($r->noresi));
                    if (!isset($rows[$key])) {
                        $rows[$key] = $r;
                    }
                }
            }
        }

        return array_values($rows);
    }

    /**
     * Get consolidated Laporan Retur Lengkap
     */
    function get_laporan_retur_lengkap($data, $start_date, $end_date, $id_kurir = null, $status_retur = '', $date_type = 'terima', $is_komplain = 0)
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

        $this->db->select("
            tr.id_resiretur,
            tr.noresi,
            tr.tanggal_resiretur as tanggal_terima,
            CASE WHEN tr.status_retur = 'Retur Tadro' THEN 'Retur Online' ELSE tr.status_retur END as status_retur,
            COALESCE(mp.nama_marketplace, mp2.nama_marketplace) as nama_marketplace,
            COALESCE(kr.nama_kurir, kr2.nama_kurir) as nama_kurir,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            COALESCE(pr.toko, br.toko) as nama_toko,
            COALESCE(dp.sku, br.sku) as sku,
            COALESCE(br.qty, dp.jumlah, 0) as jumlah,
            dp.no_rak,
            br.tanggal_buka_retur as tanggal_buka,
            br.status_detail_buka as status_detail_buka,
            br.harga,
            rv.verified_at as tanggal_acc
        ", FALSE);

        $this->db->from('tblresiretur tr');
        $this->db->where('tr.is_komplain', $is_komplain);
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblbukaretur br', 'br.resi_buka = tr.noresi AND (br.sku = dp.sku OR (dp.sku IS NULL AND br.sku IS NOT NULL))', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblreturverifikasi rv', 'rv.no_resi = tr.noresi AND rv.verified = 1', 'left');

        if (!empty($status_retur)) {
            $status_query = $status_retur;
            if ($status_query === 'Retur Online') {
                $status_query = 'Retur Tadro';
            }
            if ($status_query === 'Terima Retur') {
                $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);
            } else {
                $this->db->where('tr.status_retur', $status_query);
            }
        }

        if (!empty($start_date) && !empty($end_date)) {
            if ($date_type === 'buka') {
                $this->db->where('br.tanggal_buka_retur >=', $start_date);
                $this->db->where('br.tanggal_buka_retur <=', $end_date);
            } elseif ($date_type === 'acc') {
                $this->db->where('rv.verified_at >=', $start_date);
                $this->db->where('rv.verified_at <=', $end_date);
            } else {
                $this->db->where('tr.tanggal_resiretur >=', $start_date);
                $this->db->where('tr.tanggal_resiretur <=', $end_date);
            }
        }

        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('tr.id_kurir', $id_kurir);
            $this->db->or_where('pr.id_kurir', $id_kurir);
            $this->db->group_end();
        }

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count for Laporan Retur Lengkap
     */
    function get_total_laporan_retur_lengkap($data, $start_date, $end_date, $id_kurir = null, $status_retur = '', $date_type = 'terima', $is_komplain = 0)
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
        $this->db->where('tr.is_komplain', $is_komplain);
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblbukaretur br', 'br.resi_buka = tr.noresi AND (br.sku = dp.sku OR (dp.sku IS NULL AND br.sku IS NOT NULL))', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblreturverifikasi rv', 'rv.no_resi = tr.noresi AND rv.verified = 1', 'left');

        if (!empty($status_retur)) {
            $status_query = $status_retur;
            if ($status_query === 'Retur Online') {
                $status_query = 'Retur Tadro';
            }
            if ($status_query === 'Terima Retur') {
                $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);
            } else {
                $this->db->where('tr.status_retur', $status_query);
            }
        }

        if (!empty($start_date) && !empty($end_date)) {
            if ($date_type === 'buka') {
                $this->db->where('br.tanggal_buka_retur >=', $start_date);
                $this->db->where('br.tanggal_buka_retur <=', $end_date);
            } elseif ($date_type === 'acc') {
                $this->db->where('rv.verified_at >=', $start_date);
                $this->db->where('rv.verified_at <=', $end_date);
            } else {
                $this->db->where('tr.tanggal_resiretur >=', $start_date);
                $this->db->where('tr.tanggal_resiretur <=', $end_date);
            }
        }

        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('tr.id_kurir', $id_kurir);
            $this->db->or_where('pr.id_kurir', $id_kurir);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    /**
     * Get all data for Laporan Retur Lengkap Excel export
     */
    function get_laporan_retur_lengkap_all($start_date, $end_date, $id_kurir = null, $status_retur = '', $date_type = 'terima', $is_komplain = 0)
    {
        $this->db->select("
            tr.id_resiretur,
            tr.noresi,
            tr.tanggal_resiretur as tanggal_terima,
            CASE WHEN tr.status_retur = 'Retur Tadro' THEN 'Retur Online' ELSE tr.status_retur END as status_retur,
            COALESCE(mp.nama_marketplace, mp2.nama_marketplace) as nama_marketplace,
            COALESCE(kr.nama_kurir, kr2.nama_kurir) as nama_kurir,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            COALESCE(pr.toko, br.toko) as nama_toko,
            COALESCE(dp.sku, br.sku) as sku,
            COALESCE(br.qty, dp.jumlah, 0) as jumlah,
            dp.no_rak,
            br.tanggal_buka_retur as tanggal_buka,
            br.status_detail_buka as status_detail_buka,
            br.harga,
            rv.verified_at as tanggal_acc
        ", FALSE);

        $this->db->from('tblresiretur tr');
        $this->db->where('tr.is_komplain', $is_komplain);
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblbukaretur br', 'br.resi_buka = tr.noresi AND (br.sku = dp.sku OR (dp.sku IS NULL AND br.sku IS NOT NULL))', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblreturverifikasi rv', 'rv.no_resi = tr.noresi AND rv.verified = 1', 'left');

        if (!empty($status_retur)) {
            $status_query = $status_retur;
            if ($status_query === 'Retur Online') {
                $status_query = 'Retur Tadro';
            }
            if ($status_query === 'Terima Retur') {
                $this->db->where_in('tr.status_retur', ['Terima Retur', 'Buka Retur']);
            } else {
                $this->db->where('tr.status_retur', $status_query);
            }
        }

        if (!empty($start_date) && !empty($end_date)) {
            if ($date_type === 'buka') {
                $this->db->where('br.tanggal_buka_retur >=', $start_date);
                $this->db->where('br.tanggal_buka_retur <=', $end_date);
            } elseif ($date_type === 'acc') {
                $this->db->where('rv.verified_at >=', $start_date);
                $this->db->where('rv.verified_at <=', $end_date);
            } else {
                $this->db->where('tr.tanggal_resiretur >=', $start_date);
                $this->db->where('tr.tanggal_resiretur <=', $end_date);
            }
        }

        if (!empty($id_kurir)) {
            $this->db->group_start();
            $this->db->where('tr.id_kurir', $id_kurir);
            $this->db->or_where('pr.id_kurir', $id_kurir);
            $this->db->group_end();
        }

        $this->db->order_by('tr.tanggal_resiretur', 'DESC');

        return $this->db->get();
    }

    /**
     * Get return details for a specific SKU
     */
    function get_retur_details_by_sku($sku)
    {
        $sku = trim($sku);
        if ($sku === '') return [];

        $this->db->select("
            tr.noresi,
            CASE WHEN tr.status_retur = 'Retur Tadro' THEN 'Retur Online' ELSE tr.status_retur END as status_retur,
            tr.tanggal_resiretur as tanggal_terima,
            COALESCE(mp.nama_marketplace, mp2.nama_marketplace) as nama_marketplace,
            COALESCE(kr.nama_kurir, kr2.nama_kurir) as nama_kurir,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            COALESCE(pr.toko, br.toko) as nama_toko,
            COALESCE(dp.sku, br.sku) as sku,
            COALESCE(br.qty, dp.jumlah, 0) as jumlah,
            dp.no_rak,
            br.tanggal_buka_retur as tanggal_buka,
            br.status_detail_buka as status_detail_buka,
            br.harga,
            rv.verified_at as tanggal_acc
        ", FALSE);

        $this->db->from('tblresiretur tr');
        $this->db->join('tblprintresi pr', 'pr.noresi = tr.noresi', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblbukaretur br', 'br.resi_buka = tr.noresi AND (br.sku = dp.sku OR (dp.sku IS NULL AND br.sku IS NOT NULL))', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = tr.id_marketplace', 'left');
        $this->db->join('tblmarketplace mp2', 'mp2.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblkurir kr', 'kr.id_kurir = tr.id_kurir', 'left');
        $this->db->join('tblkurir kr2', 'kr2.id_kurir = pr.id_kurir', 'left');
        $this->db->join('tblreturverifikasi rv', 'rv.no_resi = tr.noresi AND rv.verified = 1', 'left');

        $this->db->group_start();
        $this->db->where('dp.sku', $sku);
        $this->db->or_where('br.sku', $sku);
        $this->db->group_end();

        $this->db->order_by('tr.tanggal_resiretur', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_unbatched_returns_data($params)
    {
        $this->db->select("
            br.id_bukaretur,
            br.resi_buka as noresi,
            br.sku,
            br.qty,
            br.toko as nama_toko,
            br.tanggal_buka_retur as tanggal_buka,
            br.status_detail_buka,
            COALESCE(dp.no_rak, '-') as no_rak,
            COALESCE(mp.nama_marketplace, '-') as nama_marketplace,
            COALESCE(s.nama_sku, 'Tidak Ada Detail SKU') as nama_barang,
            (SELECT is_komplain FROM tblresiretur tr WHERE tr.noresi = br.resi_buka ORDER BY id_resiretur DESC LIMIT 1) as is_komplain
        ");
        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka', 'left');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblmarketplace mp', 'mp.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblsku s', 's.id_sku = br.sku', 'left');
        
        // Exclude items that are already inside a batch
        $this->db->where("br.id_bukaretur NOT IN (SELECT id_bukaretur FROM tblretur_display_batch_detail)", NULL, FALSE);

        // Date range filter
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('br.tanggal_buka_retur >=', $params['start_date']);
            $this->db->where('br.tanggal_buka_retur <=', $params['end_date']);
        }

        // Status filter
        if (!empty($params['status_filter'])) {
            $this->db->where('br.status_detail_buka', $params['status_filter']);
        }

        // Jenis filter
        if (isset($params['jenis_filter']) && $params['jenis_filter'] !== '') {
            $is_komplain_val = intval($params['jenis_filter']);
            if ($is_komplain_val === 0) {
                $this->db->where("(COALESCE((SELECT is_komplain FROM tblresiretur tr WHERE tr.noresi = br.resi_buka ORDER BY id_resiretur DESC LIMIT 1), 0) = 0)", NULL, FALSE);
            } else {
                $this->db->where("((SELECT is_komplain FROM tblresiretur tr WHERE tr.noresi = br.resi_buka ORDER BY id_resiretur DESC LIMIT 1) = 1)", NULL, FALSE);
            }
        }

        // Search filter
        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('br.resi_buka', $params['search']);
            $this->db->or_like('br.sku', $params['search']);
            $this->db->or_like('br.toko', $params['search']);
            $this->db->or_like('s.nama_sku', $params['search']);
            $this->db->group_end();
        }

        // Sort order
        if (!empty($params['order'])) {
            $this->db->order_by($params['order'], $params['dir']);
        } else {
            $this->db->order_by('br.tanggal_buka_retur', 'DESC');
        }

        // Limit / paging
        if (isset($params['start']) && isset($params['length']) && $params['length'] != -1) {
            $this->db->limit($params['length'], $params['start']);
        }

        return $this->db->get();
    }

    public function get_unbatched_returns_count($params)
    {
        $this->db->from('tblbukaretur br');
        $this->db->join('tblsku s', 's.id_sku = br.sku', 'left');
        
        $this->db->where("br.id_bukaretur NOT IN (SELECT id_bukaretur FROM tblretur_display_batch_detail)", NULL, FALSE);

        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $this->db->where('br.tanggal_buka_retur >=', $params['start_date']);
            $this->db->where('br.tanggal_buka_retur <=', $params['end_date']);
        }

        if (!empty($params['status_filter'])) {
            $this->db->where('br.status_detail_buka', $params['status_filter']);
        }

        if (isset($params['jenis_filter']) && $params['jenis_filter'] !== '') {
            $is_komplain_val = intval($params['jenis_filter']);
            if ($is_komplain_val === 0) {
                $this->db->where("(COALESCE((SELECT is_komplain FROM tblresiretur tr WHERE tr.noresi = br.resi_buka ORDER BY id_resiretur DESC LIMIT 1), 0) = 0)", NULL, FALSE);
            } else {
                $this->db->where("((SELECT is_komplain FROM tblresiretur tr WHERE tr.noresi = br.resi_buka ORDER BY id_resiretur DESC LIMIT 1) = 1)", NULL, FALSE);
            }
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('br.resi_buka', $params['search']);
            $this->db->or_like('br.sku', $params['search']);
            $this->db->or_like('br.toko', $params['search']);
            $this->db->or_like('s.nama_sku', $params['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    public function create_display_batch_tx($item_ids, $user_id)
    {
        $this->db->trans_start();

        // 1. Generate Kode Batch
        $last_batch = $this->db
            ->select('kode_batch')
            ->order_by('id_batch', 'DESC')
            ->limit(1)
            ->get('tblretur_display_batch')
            ->row_array();
            
        if (!empty($last_batch)) {
            $num = intval(str_replace('RETURDISPLAY-', '', $last_batch['kode_batch']));
            $next_num = $num + 1;
        } else {
            $next_num = 1;
        }
        
        $kode_batch = 'RETURDISPLAY-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

        // 2. Insert batch header
        $batch_data = [
            'kode_batch' => $kode_batch,
            'status'     => 'DIKIRIM',
            'total_qty'  => 0,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tblretur_display_batch', $batch_data);
        $batch_id = $this->db->insert_id();

        // 3. Insert details and count total Qty
        $total_qty = 0;
        foreach ($item_ids as $id_bukaretur) {
            // Get item info from tblbukaretur
            $item = $this->db->get_where('tblbukaretur', ['id_bukaretur' => $id_bukaretur])->row_array();
            if ($item) {
                $qty = (int) ($item['qty'] ?: 1);
                $total_qty += $qty;

                $detail_data = [
                    'batch_id'     => $batch_id,
                    'id_bukaretur' => $id_bukaretur,
                    'qty'          => $qty,
                    'keterangan'   => 'Kondisi: ' . ($item['status_detail_buka'] ?: 'Sesuai')
                ];
                $this->db->insert('tblretur_display_batch_detail', $detail_data);
            }
        }

        // 4. Update total qty in batch header
        $this->db->where('id_batch', $batch_id)->update('tblretur_display_batch', ['total_qty' => $total_qty]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Gagal membuat batch di database.'];
        }

        return ['success' => true, 'kode_batch' => $kode_batch, 'id_batch' => $batch_id];
    }

    /**
     * Get list of opened returns with status SHIPPED
     */
    function get_laporan_retur_shipped($data, $start_date, $end_date)
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

        $this->db->select("
            br.resi_buka as noresi,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            br.sku,
            br.qty as jumlah,
            COALESCE(pr.toko, br.toko) as nama_toko,
            br.tanggal_buka_retur as tanggal_buka,
            pr.status_pesanan,
            rj.status_jubelio,
            rj.status_retur,
            CASE WHEN rj.id_jubelio IS NOT NULL THEN 'Berubah' ELSE 'Belum' END as status_berubah
        ", FALSE);

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblreturjubelio rj', 'rj.no_resi = br.resi_buka AND rj.sku = br.sku', 'left');
        $this->db->where('pr.status_pesanan', 'SHIPPED');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        if (isset($data['start']) && isset($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    /**
     * Get total count of opened returns with status SHIPPED
     */
    function get_total_laporan_retur_shipped($data, $start_date, $end_date)
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
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblreturjubelio rj', 'rj.no_resi = br.resi_buka AND rj.sku = br.sku', 'left');
        $this->db->where('pr.status_pesanan', 'SHIPPED');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get all opened returns with status SHIPPED (for Excel export)
     */
    function get_laporan_retur_shipped_all($start_date, $end_date)
    {
        $this->db->select("
            br.resi_buka as noresi,
            COALESCE(dp.no_pesanan, br.no_pesanan) as no_pesanan,
            br.sku,
            br.qty as jumlah,
            COALESCE(pr.toko, br.toko) as nama_toko,
            br.tanggal_buka_retur as tanggal_buka,
            pr.status_pesanan,
            rj.status_jubelio,
            rj.status_retur,
            CASE WHEN rj.id_jubelio IS NOT NULL THEN 'Berubah' ELSE 'Belum' END as status_berubah
        ", FALSE);

        $this->db->from('tblbukaretur br');
        $this->db->join('tblprintresi pr', 'pr.noresi = br.resi_buka');
        $this->db->join('tbldetailprintresi dp', 'dp.id_resi = pr.id_printresi AND dp.sku = br.sku', 'left');
        $this->db->join('tblreturjubelio rj', 'rj.no_resi = br.resi_buka AND rj.sku = br.sku', 'left');
        $this->db->where('pr.status_pesanan', 'SHIPPED');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('br.tanggal_buka_retur >=', $start_date);
            $this->db->where('br.tanggal_buka_retur <=', $end_date);
        }

        $this->db->order_by('br.tanggal_buka_retur', 'DESC');

        return $this->db->get();
    }

    // ==================== REKAP PROSES RETUR (TIM ACCOUNTING) ====================
    //
    // Berbeda dengan semua laporan retur lain yang berangkat dari resi yang SUDAH
    // discan (tblresiretur), rekap ini berangkat dari tblreturjubelio — daftar
    // retur yang DIKLAIM marketplace. Hanya dengan arah itu resi yang barangnya
    // tidak pernah kembali bisa terlihat (resi hilang tidak punya baris di iresis).

    /** Status buka yang berarti "paket datang tapi isinya bermasalah". */
    private $rekap_status_masalah = array(
        'BARANG_TIDAK_ADA', 'KURANG', 'KURANG_DARI_PEMBELI', 'BUKAN_BARANG_KITA', 'PAKET_HILANG'
    );

    /**
     * Ambang hari klasifikasi retur yang belum kembali. Disimpan di tabel `param`
     * grup RETUR_KLAIM_SETTING (pola sama dengan STOCK_SETTING) supaya bisa diubah
     * tanpa mengubah kode.
     */
    public function rekap_sla()
    {
        $sla = array('wajar' => 7, 'terlambat' => 14, 'window' => 30);
        $map = array(
            'SLA_WAJAR_HARI'     => 'wajar',
            'SLA_TERLAMBAT_HARI' => 'terlambat',
            'WINDOW_KLAIM_HARI'  => 'window',
        );

        $rows = $this->db->where('paramgroup', 'RETUR_KLAIM_SETTING')
            ->where('isactive', 1)
            ->get('param')->result();

        foreach ($rows as $r) {
            if (isset($map[$r->paramvalue1]) && is_numeric($r->paramvalue2)) {
                $sla[$map[$r->paramvalue1]] = (int) $r->paramvalue2;
            }
        }

        // Jaga urutan tetap masuk akal walau param diisi ngawur.
        if ($sla['terlambat'] < $sla['wajar'])  $sla['terlambat'] = $sla['wajar'];
        if ($sla['window']    < $sla['terlambat']) $sla['window'] = $sla['terlambat'];

        return $sla;
    }

    /**
     * Query dasar rekap: satu baris per no_resi Jubelio, sudah diberi label bucket.
     * Dipakai ulang oleh summary / matriks / detail / export.
     *
     * PENTING — soal charset: `tblreturjubelio.no_resi` ber-charset utf8, sedangkan
     * `tblresiretur.noresi` dan `tblbukaretur.resi_buka` latin1. Membandingkan
     * langsung membuat MySQL mengonversi sisi latin1 sehingga index-nya TIDAK dipakai
     * (satu query ringkasan sebulan sempat >110 detik). Karena itu no_resi
     * di-CONVERT ke latin1 lebih dulu (kolom `resi_l`) supaya lookup tetap lewat index
     * `tblresiretur.noresi` / `tblbukaretur.resi_buka` (turun ke ~1,3 detik).
     */
    private function _rekap_base($start, $end, $sla, $kurir = null, $bucket = null, $search = null)
    {
        $t1 = (int) $sla['wajar'];
        $t2 = (int) $sla['terlambat'];
        $t3 = (int) $sla['window'];

        $masalah = "'" . implode("','", $this->rekap_status_masalah) . "'";

        $where = '';
        if (!empty($kurir)) {
            $where .= ' AND kurir = ' . $this->db->escape($kurir);
        }
        if (!empty($search)) {
            $like = $this->db->escape('%' . $search . '%');
            $where .= " AND (no_resi LIKE {$like} OR no_pesanan LIKE {$like}"
                . " OR nama_toko LIKE {$like} OR kurir LIKE {$like})";
        }

        $where_bucket = !empty($bucket) ? ' WHERE f.bucket = ' . $this->db->escape($bucket) : '';

        return "
            SELECT f.* FROM (
                SELECT g.*,
                       CASE
                         WHEN g.n_buka > 0 AND (g.n_masalah > 0 OR g.qty_buka < g.qty_jubelio) THEN 'BERMASALAH'
                         WHEN g.n_buka > 0 THEN 'SELESAI'
                         WHEN g.n_iresis > 0 THEN 'BELUM_DIBUKA'
                         WHEN g.umur_hari <= {$t1} THEN 'DALAM_PERJALANAN'
                         WHEN g.umur_hari <= {$t2} THEN 'TERLAMBAT'
                         WHEN g.umur_hari <= {$t3} THEN 'LAYAK_KLAIM'
                         ELSE 'HANGUS'
                       END AS bucket
                FROM (
                    SELECT j.no_resi, j.no_pesanan, j.tanggal_retur, j.kurir, j.marketplace, j.nama_toko,
                           j.status_retur, j.qty_jubelio, j.nilai,
                           DATEDIFF(NOW(), j.tanggal_retur) AS umur_hari,
                           (SELECT COUNT(*) FROM tblresiretur r WHERE r.noresi = j.resi_l) AS n_iresis,
                           (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = j.resi_l) AS n_buka,
                           (SELECT COALESCE(SUM(b.qty),0) FROM tblbukaretur b WHERE b.resi_buka = j.resi_l) AS qty_buka,
                           (SELECT COUNT(*) FROM tblbukaretur b WHERE b.resi_buka = j.resi_l
                              AND b.status_detail_buka IN ({$masalah})) AS n_masalah,
                           (SELECT GROUP_CONCAT(DISTINCT b.status_detail_buka ORDER BY b.status_detail_buka SEPARATOR ', ')
                              FROM tblbukaretur b WHERE b.resi_buka = j.resi_l) AS status_list
                    FROM (
                        SELECT no_resi,
                               CONVERT(no_resi USING latin1) AS resi_l,
                               MIN(tanggal_retur) AS tanggal_retur,
                               MAX(no_pesanan)    AS no_pesanan,
                               MAX(kurir)         AS kurir,
                               MAX(marketplace)   AS marketplace,
                               MAX(nama_toko)     AS nama_toko,
                               MAX(status_retur)  AS status_retur,
                               SUM(qty)           AS qty_jubelio,
                               SUM(amount)        AS nilai
                        FROM tblreturjubelio
                        WHERE tanggal_retur >= " . $this->db->escape($start) . "
                          AND tanggal_retur <= " . $this->db->escape($end) . "
                          AND no_resi IS NOT NULL AND no_resi <> ''
                          {$where}
                        GROUP BY no_resi
                    ) j
                ) g
            ) f {$where_bucket}
        ";
    }

    /** Ringkasan corong: jumlah resi + qty + nilai per bucket. */
    public function rekap_summary($start, $end, $sla, $kurir = null)
    {
        $sql = "SELECT t.bucket, COUNT(*) AS n_resi, SUM(t.qty_jubelio) AS qty, SUM(t.nilai) AS nilai
                FROM (" . $this->_rekap_base($start, $end, $sla, $kurir) . ") t
                GROUP BY t.bucket";

        return $this->db->query($sql)->result();
    }

    /** Matriks kurir x bucket — bahan negosiasi klaim. */
    public function rekap_by_kurir($start, $end, $sla, $kurir = null)
    {
        $sql = "SELECT COALESCE(NULLIF(t.kurir,''),'(tanpa kurir)') AS label, t.bucket,
                       COUNT(*) AS n, SUM(t.nilai) AS nilai
                FROM (" . $this->_rekap_base($start, $end, $sla, $kurir) . ") t
                GROUP BY label, t.bucket";

        return $this->db->query($sql)->result();
    }

    /** Sebaran per marketplace / toko. */
    public function rekap_by_marketplace($start, $end, $sla, $kurir = null)
    {
        $sql = "SELECT COALESCE(NULLIF(t.marketplace,''),'(tanpa marketplace)') AS label, t.bucket,
                       COUNT(*) AS n, SUM(t.nilai) AS nilai
                FROM (" . $this->_rekap_base($start, $end, $sla, $kurir) . ") t
                GROUP BY label, t.bucket";

        return $this->db->query($sql)->result();
    }

    /** Daftar kurir yang muncul pada rentang tanggal (untuk dropdown filter). */
    public function rekap_kurir_list($start, $end)
    {
        $sql = "SELECT DISTINCT kurir FROM tblreturjubelio
                WHERE tanggal_retur >= " . $this->db->escape($start) . "
                  AND tanggal_retur <= " . $this->db->escape($end) . "
                  AND kurir IS NOT NULL AND kurir <> ''
                ORDER BY kurir";

        return $this->db->query($sql)->result();
    }

    /**
     * Resi yang discan di iresis tapi TIDAK ada di Jubelio ("Hanya di iresis").
     * Bukan kandidat klaim — ini indikasi masalah data / retur di luar sistem.
     */
    public function rekap_hanya_iresis($start, $end)
    {
        // CONVERT ke utf8 di sisi iresis supaya index tblreturjubelio.no_resi tetap
        // terpakai (lihat catatan charset di _rekap_base).
        $sql = "SELECT COUNT(*) AS n FROM (
                    SELECT r.noresi FROM tblresiretur r
                    WHERE r.tanggal_resiretur >= " . $this->db->escape($start) . "
                      AND r.tanggal_resiretur <= " . $this->db->escape($end) . "
                      AND r.is_komplain = 0 AND r.noresi IS NOT NULL AND r.noresi <> ''
                    GROUP BY r.noresi
                ) x
                WHERE NOT EXISTS (
                    SELECT 1 FROM tblreturjubelio j
                    WHERE j.no_resi = CONVERT(x.noresi USING utf8)
                )";

        $row = $this->db->query($sql)->row();
        return $row ? (int) $row->n : 0;
    }

    /** Kesegaran data Jubelio — dashboard ini hanya sevalid upload terakhir. */
    public function rekap_freshness()
    {
        return $this->db->query(
            "SELECT MAX(uploaded_at) AS last_upload, MAX(tanggal_retur) AS last_retur
             FROM tblreturjubelio"
        )->row();
    }

    /** Detail resi (server-side DataTables). */
    public function rekap_detail($start, $end, $sla, $params)
    {
        $base = $this->_rekap_base(
            $start, $end, $sla,
            isset($params['kurir'])  ? $params['kurir']  : null,
            isset($params['bucket']) ? $params['bucket'] : null,
            isset($params['search']) ? $params['search'] : null
        );

        $order  = !empty($params['order']) ? $params['order'] : 'umur_hari';
        $dir    = (isset($params['dir']) && strtolower($params['dir']) === 'asc') ? 'ASC' : 'DESC';
        $limit  = isset($params['length']) ? (int) $params['length'] : 50;
        $offset = isset($params['start'])  ? (int) $params['start']  : 0;
        if ($limit <= 0) $limit = 50;

        $sql = "SELECT * FROM ({$base}) d ORDER BY d.{$order} {$dir}, d.no_resi ASC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql)->result();
    }

    /** Jumlah baris detail (untuk paging). */
    public function rekap_detail_total($start, $end, $sla, $params)
    {
        $base = $this->_rekap_base(
            $start, $end, $sla,
            isset($params['kurir'])  ? $params['kurir']  : null,
            isset($params['bucket']) ? $params['bucket'] : null,
            isset($params['search']) ? $params['search'] : null
        );

        $row = $this->db->query("SELECT COUNT(*) AS n FROM ({$base}) d")->row();
        return $row ? (int) $row->n : 0;
    }

    /**
     * Kandidat klaim: resi bermasalah / hilang yang BELUM punya baris di tblreturklaim.
     * Dipakai menu Pengajuan Klaim Retur.
     */
    private function _kandidat_sql($start, $end, $sla, $params)
    {
        $base = $this->_rekap_base(
            $start, $end, $sla,
            isset($params['kurir'])  ? $params['kurir']  : null,
            isset($params['bucket']) ? $params['bucket'] : null,
            isset($params['search']) ? $params['search'] : null
        );

        // Tanpa filter bucket eksplisit, tampilkan semua kategori yang layak diklaim.
        $filter_bucket = empty($params['bucket'])
            ? " AND d.bucket IN ('BERMASALAH','LAYAK_KLAIM','HANGUS')"
            : '';

        // CONVERT supaya index tblreturklaim.no_resi (latin1) tetap terpakai.
        return "SELECT d.* FROM ({$base}) d
                WHERE NOT EXISTS (
                    SELECT 1 FROM tblreturklaim k WHERE k.no_resi = CONVERT(d.no_resi USING latin1)
                ){$filter_bucket}";
    }

    public function rekap_kandidat_klaim($start, $end, $sla, $params)
    {
        $order  = !empty($params['order']) ? $params['order'] : 'nilai';
        $dir    = (isset($params['dir']) && strtolower($params['dir']) === 'asc') ? 'ASC' : 'DESC';
        $limit  = isset($params['length']) ? (int) $params['length'] : 50;
        $offset = isset($params['start'])  ? (int) $params['start']  : 0;
        if ($limit <= 0) $limit = 50;

        $sql = "SELECT * FROM (" . $this->_kandidat_sql($start, $end, $sla, $params) . ") k
                ORDER BY k.{$order} {$dir}, k.no_resi ASC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql)->result();
    }

    public function rekap_kandidat_total($start, $end, $sla, $params)
    {
        $sql = "SELECT COUNT(*) AS n, COALESCE(SUM(k.nilai),0) AS nilai
                FROM (" . $this->_kandidat_sql($start, $end, $sla, $params) . ") k";

        return $this->db->query($sql)->row();
    }

    /** Ambil satu baris kandidat berdasarkan no_resi (dipakai saat menyimpan pengajuan). */
    public function rekap_kandidat_by_resi($start, $end, $sla, array $resi_list)
    {
        if (empty($resi_list)) return array();

        $escaped = array();
        foreach ($resi_list as $r) {
            $escaped[] = $this->db->escape(trim($r));
        }

        $base = $this->_rekap_base($start, $end, $sla);
        $sql  = "SELECT d.* FROM ({$base}) d WHERE d.no_resi IN (" . implode(',', $escaped) . ")";

        return $this->db->query($sql)->result();
    }

    /** Semua baris detail untuk export Excel (tanpa paging). */
    public function rekap_detail_all($start, $end, $sla, $params)
    {
        $base = $this->_rekap_base(
            $start, $end, $sla,
            isset($params['kurir'])  ? $params['kurir']  : null,
            isset($params['bucket']) ? $params['bucket'] : null,
            isset($params['search']) ? $params['search'] : null
        );

        return $this->db->query("SELECT * FROM ({$base}) d ORDER BY d.umur_hari DESC, d.no_resi ASC")->result_array();
    }
}
