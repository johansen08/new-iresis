<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packer_fcd extends CI_Model
{

    function save($packer, $user)
    {
        /**
         * 1. check does noresi exist in tblprintresi and get status
         * 2. throw if does not exist
         * 3. check if status_pesanan is COMPLETED or CANCELED
         * 4. throw if status is COMPLETED or CANCELED
         * 5. check does id_resi exist in tblresiambilbarang
         * 6. throw if does not exist
         * 7. check does id_resi exist in tblpacking
         * 8. throw if exists
         * 9. save into tblpacking
         *
         * Ketiga pemeriksaan di atas dulu tiga SELECT terpisah. Sekarang satu
         * query ber-LEFT JOIN: urutan penolakannya tetap sama persis, tapi
         * ongkosnya sepertiga. Itu penting karena DB ada di mesin lain (lihat
         * secrets.php), jadi tiap query membayar perjalanan bolak-balik LAN.
         *
         * LEFT JOIN ke tblpacking bisa menggandakan baris kalau satu resi
         * punya lebih dari satu baris packing. Tidak jadi soal: yang dipakai
         * cuma "ada atau tidak", dan baris hasil LEFT JOIN hanya bernilai NULL
         * kalau memang tidak ada pasangannya sama sekali.
         */
        $receipt = $this->db
            ->select('pr.id_printresi, pr.status_pesanan, pr.batal, rab.id_resiambilbarang, pk.id_packing')
            ->from('tblprintresi pr')
            ->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left')
            ->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left')
            ->where('pr.noresi', $packer['noresi'])
            ->limit(1)
            ->get()
            ->row();

        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak ditemukan', 'data' => ['EXCEPTION_CODE' => 'NOT_FOUND']];
        }

        // Check if status_pesanan is COMPLETED or CANCELED
        if ($receipt->status_pesanan == 'COMPLETED') {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah SELESAI', 'data' => ['EXCEPTION_CODE' => 'ORDER_COMPLETED']];
        }
        if ($receipt->status_pesanan == 'CANCELED' || $receipt->batal == '1' || $receipt->batal == 1) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN', 'data' => ['EXCEPTION_CODE' => 'ORDER_CANCELED']];
        }

        $packer['noresi_original'] = $packer['noresi'];
        unset($packer['noresi']);

        // Check if this receipt has been picked
        if (empty($receipt->id_resiambilbarang)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor Resi belum di-picker. Silakan Cek data', 'data' => ['EXCEPTION_CODE' => 'NOT_PICKED']];
        }

        // Check if this receipt has already been packed
        if (!empty($receipt->id_packing)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi sudah di-packing (Double Scan).', 'data' => ['EXCEPTION_CODE' => 'ALREADY_PACKED']];
        }

        // Insert packing record
        // Priority: use nama_komputer from $user array (should be synced with database since login fix)
        // This ensures packer computer number is accurately recorded
        $insert_data = [
            'id_resi' => $receipt->id_printresi,
            'tanggal_packing' => date('Y-m-d H:i:s'),
            'packer_pegawai' => $user['id_user'],
            'keterangan' => $user['nama_komputer'], // Now synced with database from login
            'status_performa_id' => $packer['status_performa_id'] ?? null
        ];

        // Dua tulisan inti ini harus jadi satu: baris packing tanpa reset
        // "pending" di tblresiambilbarang membuat resi tersangkut di menu
        // pending picker. db_debug dimatikan sementara sesuai standar proyek --
        // pesan error CI berupa halaman HTML dan akan merusak JSON respons AJAX.
        $db_debug_semula = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $this->db->trans_start();

        $this->db->insert('tblpacking', $insert_data);
        $baris_tersimpan = $this->db->affected_rows();

        $this->db->where('id_resiambilbarang', $receipt->id_resiambilbarang);
        $this->db->update('tblresiambilbarang', ['pending' => '']);

        $this->db->trans_complete();

        $transaksi_sukses = $this->db->trans_status();
        $this->db->db_debug = $db_debug_semula;

        if ($transaksi_sukses === FALSE) {
            return ['error' => TRUE, 'code' => 500, 'message' => FAILED_SAVE_DATA, 'data' => ['EXCEPTION_CODE' => 'SAVE_FAILED']];
        }

        // Pencatatan KPI dan monitoring SENGAJA di luar transaksi di atas.
        // Keduanya data pendamping, bukan data packing itu sendiri; kalau
        // salah satunya gagal, resi yang sudah benar-benar dipacking tidak
        // boleh ikut dibatalkan -- itu akan menghentikan seluruh meja packing.
        $this->log_kpi_transaksi($user['id_user'], 'PACKER');

        // Log performance monitoring
        $this->load->model('packer_monitoring_fcd');
        $perf = $this->packer_monitoring_fcd->log_performance($user['id_user'], $receipt->id_printresi, $packer['noresi_original']);

        // Diambil tepat setelah INSERT. Dulu dibaca di sini, setelah serangkaian
        // query lain berjalan, jadi yang terbaca sebenarnya affected_rows milik
        // query terakhir -- kebetulan bernilai 1 sehingga tidak pernah ketahuan.
        $packer['affected_rows'] = $baris_tersimpan;
        $packer['performance'] = $perf;
        $packer['id_resi'] = $receipt->id_printresi;

        return $packer;
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
            pr.noresi,
            u.name AS nama_pegawai,
            p.tanggal_packing,
            COALESCE(p.keterangan, u.nama_komputer) AS keterangan
        ');

        $this->db->from('tblpacking p');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = p.id_resi');
        $this->db->join('tbluser u', 'u.id_user = p.packer_pegawai', 'left');

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
            
            // Only join when searching
            $this->db->join('tblprintresi pr', 'pr.id_printresi = p.id_resi');
            $this->db->join('tbluser u', 'u.id_user = p.packer_pegawai', 'left');
            
            return $this->db->count_all_results('tblpacking p');
        } else {
            // No search, return total count directlyfrom table (very fast)
            return $this->db->count_all('tblpacking');
        }
    }

    function get_total_scan_user($id_pegawai)
    {
        $this->db->select('count(1) as total_scan');

        $criterias = [
            'tanggal_packing >= ' => date('Y-m-d'),
            'packer_pegawai' => $id_pegawai,
        ];

        $this->db->where($criterias);

        return $this->db->get_where('tblpacking');
    }

    function get_picker_detail_for_packer($noresi) {
        $this->db->select('
            t.nama_komputer,
            t3.nama_pegawai
        ');
        $this->db->from('tblresiambilbarang t');
        $this->db->join('tblprintresi t2', 't.id_resi = t2.id_printresi');
        $this->db->join('tblpegawai t3', 't3.kode_pegawai = t.yangambil_pegawai');
        $this->db->where('t2.noresi', $noresi);

        $query = $this->db->get();
        return $query->row();
    }

    /**
     * Log transaksi packing ke tblkpi untuk KPI tracking
     */
    private function log_kpi_transaksi($user_id, $tipe_transaksi) {
        $tanggal = date('Y-m-d');
        
        // Ambil id_statusperforma dari log status performa user hari ini
        $status_log = $this->db
            ->select('id_statusperforma')
            ->get_where('tblstatusperforma', [
                'id_user' => $user_id,
                'tanggal' => $tanggal,
                'isactive' => 1
            ])
            ->row();
        
        log_message('info', "LOG PACKER DEBUG: user_id={$user_id}, tanggal={$tanggal}, status_log=" . ($status_log ? $status_log->id_statusperforma : 'NULL'));
        
        if (!$status_log) {
            // Jika tidak ada log status performa, skip
            log_message('warning', "User {$user_id} melakukan {$tipe_transaksi} tanpa status performa. tanggal={$tanggal}");
            return false;
        }
        
        // Cek apakah sudah ada log transaksi untuk user, status, tanggal, dan tipe ini
        $existing_log = $this->db
            ->get_where('tblkpi', [
                'id_user' => $user_id,
                'id_statusperforma' => $status_log->id_statusperforma,
                'tanggal' => $tanggal,
                'tipe_transaksi' => $tipe_transaksi
            ])
            ->row();
        
        if ($existing_log) {
            // Update: increment jumlah_resi, waktu created TETAP (scan pertama kali)
            $this->db->where('id_log', $existing_log->id_log);
            $this->db->set('jumlah_resi', 'jumlah_resi + 1', FALSE);
            $this->db->set('updated', date('Y-m-d H:i:s'));
            $this->db->set('updatedby', $user_id);
            $this->db->update('tblkpi');
        } else {
            // Insert: log transaksi baru (scan pertama kali untuk kombinasi ini)
            $this->db->insert('tblkpi', [
                'id_user' => $user_id,
                'id_statusperforma' => $status_log->id_statusperforma,
                'tanggal' => $tanggal,
                'tipe_transaksi' => $tipe_transaksi,
                'jumlah_resi' => 1,
                'createdby' => $user_id,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
        
        return true;
    }

    /**
     * Save masalah picker data to tblmasalahpicker
     */
    function save_masalah_picker($masalah_picker, $user)
    {
        try {
            $this->db->trans_start();

            // Validate that receipt exists
            $receipt = $this->db
                ->select('id_printresi, noresi')
                ->get_where('tblprintresi', ['id_printresi' => $masalah_picker['id_printresi'], 'noresi' => $masalah_picker['noresi']])
                ->row();

            if (empty($receipt)) {
                return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak ditemukan'];
            }

            // Check if masalah picker already exists for this SKU and receipt
            $existing = $this->db
                ->get_where('tblmasalahpicker', [
                    'id_printresi' => $masalah_picker['id_printresi'],
                    'sku' => $masalah_picker['sku']
                ])
                ->row();

            $insert_data = [
                'id_printresi' => $masalah_picker['id_printresi'],
                'noresi' => $masalah_picker['noresi'],
                'sku' => $masalah_picker['sku'],
                'qty' => $masalah_picker['qty'],
                'id_typemasalah' => $masalah_picker['id_typemasalah'],
                'qty_bermasalah' => $masalah_picker['qty_bermasalah'],
                'sku_salah' => $masalah_picker['sku_salah'],
                'status' => 0, // Always Pending, must be reviewed in Daftar Masalah Picker
                'created_by' => $user['id_user'],
                'created' => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                // Update existing record
                $insert_data['updated_by'] = $user['id_user'];
                $insert_data['updated'] = date('Y-m-d H:i:s');
                $this->db->where('id_masalahpicker', $existing->id_masalahpicker);
                $this->db->update('tblmasalahpicker', $insert_data);
                // Get affected_rows before trans_complete
                $affected_rows = $this->db->affected_rows();
                // Jika affected_rows = 0, berarti data tidak berubah, tapi tetap dianggap sukses
                // karena update berhasil (hanya tidak ada perubahan data)
                $masalah_picker['affected_rows'] = $affected_rows > 0 ? $affected_rows : 1;
            } else {
                // Insert new record
                $this->db->insert('tblmasalahpicker', $insert_data);
                // Get affected_rows before trans_complete
                $masalah_picker['affected_rows'] = $this->db->affected_rows();
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                return ['error' => TRUE, 'code' => 500, 'message' => 'Database transaction failed'];
            }

            return $masalah_picker;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Error in save_masalah_picker: ' . $e->getMessage());
            return ['error' => TRUE, 'code' => 500, 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }

    function get_total_scan_packer_nonsubmit_user($id_pegawai)
    {
        $this->db->select('count(1) as total_scan');

        $criterias = [
            'tanggal_packing >= ' => date('Y-m-d'),
            'packer_pegawai' => $id_pegawai,
        ];

        $this->db->where($criterias);

        return $this->db->get_where('tblpacking');
    }

    function save_packer_nonsubmit($packer, $user)
    {
        /**
         * 1. check does noresi exist in tblprintresi and get status
         * 2. throw if does not exist
         * 3. check if status_pesanan is COMPLETED or CANCELED
         * 4. throw if status is COMPLETED or CANCELED
         * 5. check does id_resi exist in tblresiambilbarang
         * 6. throw if does not exist
         * 7. check does id_resi exist in tblpacking
         * 8. throw if exists
         * 9. save into tblpacking
         */
        $receipt = $this->db
            ->select('id_printresi, status_pesanan, batal')
            ->get_where('tblprintresi', ['noresi' => $packer['noresi']])
            ->row();
        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak ditemukan', 'data' => ['EXCEPTION_CODE' => 'NOT_FOUND']];
        }

        // Check if status_pesanan is COMPLETED or CANCELED
        if ($receipt->status_pesanan == 'COMPLETED') {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah SELESAI', 'data' => ['EXCEPTION_CODE' => 'ORDER_COMPLETED']];
        }
        if ($receipt->status_pesanan == 'CANCELED' || $receipt->batal == '1' || $receipt->batal == 1) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Pesanan sudah DIBATALKAN', 'data' => ['EXCEPTION_CODE' => 'ORDER_CANCELED']];
        }

        $packer['noresi_original'] = $packer['noresi'];
        unset($packer['noresi']);

        // Check if this receipt has been picked
        $picking_exist = $this->db->get_where('tblresiambilbarang', ['id_resi' => $receipt->id_printresi])->row();
        if (!$picking_exist) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor Resi belum di-picker.', 'data' => ['EXCEPTION_CODE' => 'NOT_PICKED']];
        }

        // Check if this receipt has already been packed
        $packer_exist = $this->db->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])->row();
        if ($packer_exist) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi sudah di-packing (Double Scan).', 'data' => ['EXCEPTION_CODE' => 'ALREADY_PACKED']];
        }

        // Insert packing record
        // Priority: use nama_komputer from $user array (should be synced with database since login fix)
        // This ensures packer computer number is accurately recorded
        $insert_data = [
            'id_resi' => $receipt->id_printresi,
            'tanggal_packing' => date('Y-m-d H:i:s'),
            'packer_pegawai' => $user['id_user'],
            'keterangan' => $user['nama_komputer'], // Now synced with database from login
            'status_performa_id' => $packer['status_performa_id'] ?? null
        ];

        $this->db->insert('tblpacking', $insert_data);

        // Update tblresiambilbarang to reset pending to ''
        $this->db->where('id_resiambilbarang', $picking_exist->id_resiambilbarang);
        $this->db->update('tblresiambilbarang', ['pending' => '']);

        // Log transaksi ke tblkpi untuk KPI tracking
        $this->log_kpi_transaksi($user['id_user'], 'PACKER');

        // Log performance monitoring
        $this->load->model('packer_monitoring_fcd');
        $perf = $this->packer_monitoring_fcd->log_performance($user['id_user'], $receipt->id_printresi, $packer['noresi_original']);

        $packer['affected_rows'] = $this->db->affected_rows();
        $packer['performance'] = $perf;
        $packer['id_resi'] = $receipt->id_printresi;

        return $packer;
    }
}
