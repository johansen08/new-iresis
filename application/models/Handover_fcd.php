<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Handover_fcd extends CI_Model
{
    function save($handover, $user)
    {
        /**
         * FLOW UTAMA:
         * 1. Cek apakah nomor resi ada di tblprintresi
         * 2. Jika tidak ada → error 404
         * 3. Jika status pesanan = COMPLETED/CANCELED → error 400 (baru)
         * 4. Cek apakah sudah dikirim → error 400
         * 5. Cek apakah sudah di-picker → error 401
         * 6. Cek apakah sudah di-packing → error 402
         * 7. Jika semua validasi lolos → insert ke tblresikeluar
         */

        // 🔹 [1] Ambil data resi berdasarkan nomor resi
        $receipt = $this->db
            ->select('id_printresi, status_pesanan')
            ->get_where('tblprintresi', ['noresi' => $handover['noresi']])
            ->row_array();

        // 🔸 [2] Jika nomor resi tidak ditemukan
        if (empty($receipt)) {
            return [
                'error' => TRUE,
                'code' => 404,
                'message' => 'Nomor resi tidak ditemukan',
                'data' => ['EXCEPTION_CODE' => 'NOT_FOUND']
            ];
        }

        // 🔹 [3] Validasi status pesanan (tambahan dari versi baru)
        // Jika status pesanan sudah "COMPLETED" atau "CANCELED" → tidak boleh lanjut
        if (in_array($receipt['status_pesanan'], ['COMPLETED', 'CANCELED'])) {
            return [
                'error' => TRUE,
                'code' => 400,
                'message' => 'Nomor resi tidak dapat diproses karena status pesanan sudah ' . $receipt['status_pesanan'],
                'data' => ['EXCEPTION_CODE' => 'INVALID_STATUS']
            ];
        }

        // 🔹 [4] Cek apakah resi sudah dikirim sebelumnya (tblresikeluar)
        $handover_exist = $this->db
            ->get_where('tblresikeluar', ['id_resi' => $receipt['id_printresi']])
            ->num_rows();

        // 🔸 Jika sudah ada → tampilkan pesan sama seperti versi lama
        if ($handover_exist > 0) {
            return [
                'error' => TRUE,
                'code' => 400,
                'message' => 'Nomor resi sudah dikirim. Silakan cek data',
                'data' => ['EXCEPTION_CODE' => 'ALREADY_HANDOVER']
            ];
        }

        // 🔹 [5] Cek apakah resi sudah diambil oleh picker (tblresiambilbarang)
        $picking_exist = $this->db
            ->get_where('tblresiambilbarang', ['id_resi' => $receipt['id_printresi']])
            ->num_rows();

        // 🔸 Jika belum → notifikasi error 401
        if ($picking_exist < 1) {
            return [
                'error' => TRUE,
                'code' => 401,
                'message' => 'Nomor Resi belum di-picker. Silakan Cek data',
                'data' => ['EXCEPTION_CODE' => 'NOT_PICKED']
            ];
        }

        // 🔹 [6] Cek apakah resi sudah di-packing (tblpacking)
        $packer_exist = $this->db
            ->get_where('tblpacking', ['id_resi' => $receipt['id_printresi']])
            ->num_rows();

        // 🔸 Jika belum → notifikasi error 402
        if ($packer_exist < 1) {
            return [
                'error' => TRUE,
                'code' => 402,
                'message' => 'Nomor Resi belum di-packing. Silakan Cek data',
                'data' => ['EXCEPTION_CODE' => 'NOT_PACKED']
            ];
        }

        // 🔹 [7] Semua validasi lolos → lanjut insert ke tblresikeluar
        unset($handover['noresi']); // hilangkan noresi agar tidak ikut disimpan

        // Siapkan data yang akan disimpan
        $insert_data = [
            'id_resi' => $receipt['id_printresi'],
            'tanggal_resikeluar' => date('Y-m-d H:i:s'),
            'sudah_cetak' => '-',
            'tanggal_cetak' => '',
            // Gunakan id_pegawai jika ada, kalau tidak pakai id_user (kompatibilitas versi baru)
            'id_pegawai' => isset($user['id_pegawai']) ? $user['id_pegawai'] : (isset($user['id_user']) ? $user['id_user'] : null)
        ];

        // 🔸 [8] Lakukan insert ke database
        $this->db->insert('tblresikeluar', $insert_data);

        // Tambahkan informasi hasil insert untuk keperluan feedback frontend
        $handover['id_resikeluar'] = $this->db->insert_id();
        $handover['affected_rows'] = $this->db->affected_rows();

        // 🔹 [9] Kembalikan hasil untuk dikonsumsi frontend (JSON response)
        return $handover;
    }

    /**
     * Ambil data untuk tampilan tabel handover (DataTables)
     */
    function get_data($data)
    {
        // Urutan data (sorting)
        if ($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        // Pencarian global (search)
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

        // Pilih kolom yang akan ditampilkan
        $this->db->select('
            t.id_resikeluar id,
            t2.noresi,
            t3.nama_pegawai pegawai,
            t.tanggal_resikeluar,
            t.sudah_cetak,
            t.tanggal_cetak
        ');

        // Join antar tabel untuk ambil data lengkap
        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
        $this->db->join('tblpegawai t3', 't3.kode_pegawai = t.id_pegawai', 'left');

        // Limit dan offset untuk pagination
        $this->db->limit($data['length'], $data['start']);

        return $this->db->get('tblresikeluar t');
    }

    /**
     * Hitung total data (untuk DataTables pagination)
     */
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

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
        $this->db->join('tblpegawai t3', 't3.kode_pegawai = t.id_pegawai', 'left');

        $query = $this->db->select("count(1) as num")->get("tblresikeluar t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    /**
     * Ambil data resi untuk keperluan cetak / laporan
     */
    function get_data_print($id_kurir, $start_date, $end_date)
    {
        $this->db->select('t2.noresi');

        $this->db->where([
            't2.id_kurir' => $id_kurir,
            't.tanggal_resikeluar >=' => $start_date,
            't.tanggal_resikeluar <' => $end_date,
        ]);

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
        $this->db->order_by('t2.noresi');

        return $this->db->get('tblresikeluar t');
    }

    /**
     * Hitung total scan yang dilakukan oleh pegawai tertentu pada hari ini
     */
    function get_total_scan_user($id_pegawai)
    {
        $this->db->select('count(1) as total_scan');

        $criterias = [
            'tanggal_resikeluar >= ' => date('Y-m-d'),
            'id_pegawai' => $id_pegawai,
        ];

        $this->db->where($criterias);

        return $this->db->get_where('tblresikeluar');
    }
}
