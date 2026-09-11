<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hpp_fcd extends CI_Model
{
    // =========================================================
    // INSERT/UPDATE HPP UPLOAD
    // =========================================================
    function insert_hpp_upload($dataRaw, $user_id, $upload_id = null)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $countInsert = 0;
        $countUpdate = 0;

        // Filter valid rows (skip header row 1)
        $rows = [];
        foreach ($dataRaw as $key => $row) {
            if ($key <= 1) continue; // Skip header
            $id_sku = trim($row['A'] ?? '');
            if (empty($id_sku)) continue;
            $rows[] = $row;
        }

        $totalRows = count($rows);
        $processed = 0;

        $progressFile = null;
        if ($upload_id) {
            $progressFile = sys_get_temp_dir() . '/hpp_progress_' . preg_replace('/[^a-z0-9]/i', '', $upload_id);
        }

        // Pre-fetch all existing SKU IDs
        $existing_skus = [];
        $query = $this->db->select('id_sku')->get('tblsku');
        foreach ($query->result() as $row_db) {
            $existing_skus[strtolower(trim($row_db->id_sku))] = true;
        }

        $this->db->trans_start();

        $inserts = [];
        $updates = [];

        foreach ($rows as $row) {
            $processed++;

            // Update progress every 10 rows
            if ($progressFile && ($processed % 10 == 0 || $processed == $totalRows)) {
                $progressData = [
                    'status'     => 'Processing',
                    'processed'  => $processed,
                    'total'      => $totalRows,
                    'remaining'  => $totalRows - $processed,
                    'percentage' => round(($processed / $totalRows) * 100)
                ];
                @file_put_contents($progressFile, json_encode($progressData));
            }

            // Column mapping (based on HPP upload format):
            // A=SKU, B=Nama, C=Bundle, D=HPP, E=Variasi, F=Display Barang, G=Gudang Utama, H=Transit, I=Total
            $id_sku   = trim($row['A'] ?? '');
            $nama_sku = $row['B'] ?? '';
            $bundle   = $row['C'] ?? '';
            $hpp      = isset($row['D']) && is_numeric($row['D']) ? (float)$row['D'] : 0;
            $variasi  = $row['E'] ?? '';

            $display  = $row['F'] ?? '';
            $gudang   = $row['G'] ?? '';
            $transit  = $row['H'] ?? '';
            $lokasi_parts = [];
            if ($display) $lokasi_parts[] = "Display: $display";
            if ($gudang)  $lokasi_parts[] = "Gudang: $gudang";
            if ($transit) $lokasi_parts[] = "Transit: $transit";
            $lokasi = implode(', ', $lokasi_parts);

            $total_stok = isset($row['I']) && is_numeric($row['I']) ? (int)$row['I'] : 0;

            $id_sku_lower = strtolower($id_sku);

            if (isset($existing_skus[$id_sku_lower])) {
                // Update: only update hpp, variasi, and related fields
                $updates[] = [
                    'id_sku'     => $id_sku,
                    'hpp'        => $hpp,
                    'variasi'    => $variasi,
                    'lokasi'     => $lokasi,
                    'total_stok' => $total_stok,
                    'updated'    => date('Y-m-d H:i:s'),
                ];
            } else {
                // Insert new SKU with HPP
                $inserts[] = [
                    'id_sku'     => $id_sku,
                    'nama_sku'   => $nama_sku,
                    'nama_bundle'=> $bundle,
                    'bundle'     => $bundle,
                    'hpp'        => $hpp,
                    'variasi'    => $variasi,
                    'lokasi'     => $lokasi,
                    'total_stok' => $total_stok,
                    'updated'    => date('Y-m-d H:i:s'),
                ];
                $existing_skus[$id_sku_lower] = true;
            }

            // Batch process
            if (count($inserts) >= 500) {
                $this->db->insert_batch('tblsku', $inserts);
                $countInsert += count($inserts);
                $inserts = [];
            }
            if (count($updates) >= 500) {
                $this->db->update_batch('tblsku', $updates, 'id_sku');
                $countUpdate += count($updates);
                $updates = [];
            }
        }

        // Final batches
        if (!empty($inserts)) {
            $this->db->insert_batch('tblsku', $inserts);
            $countInsert += count($inserts);
        }
        if (!empty($updates)) {
            $this->db->update_batch('tblsku', $updates, 'id_sku');
            $countUpdate += count($updates);
        }

        if ($progressFile) {
            @file_put_contents($progressFile, json_encode([
                'status' => 'Finalizing', 'processed' => $totalRows,
                'total' => $totalRows, 'remaining' => 0, 'percentage' => 100
            ]));
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->db_debug = $db_debug;
            log_message('error', 'HPP Upload Transaction Failed.');
            throw new Exception("Transaction failed. Data might not be saved correctly.");
        }

        if ($progressFile && @file_exists($progressFile)) {
            @unlink($progressFile);
        }

        $this->db->db_debug = $db_debug;
        log_message('debug', "HPP Upload finished. Inserted: $countInsert, Updated: $countUpdate");
        return "Upload HPP Berhasil. Insert: $countInsert, Update: $countUpdate. Total: $totalRows data.";
    }

    // =========================================================
    // INSERT/UPDATE SALES UPLOAD (Resi + SKU + Harga)
    // =========================================================
    function insert_sales_upload($dataRaw, $user_id, $upload_id = null)
    {
        $countUpdate = 0;
        $totalRows = count($dataRaw) - 1;
        $processed = 0;

        $this->db->trans_start();
        $updates = [];

        foreach ($dataRaw as $key => $row) {
            if ($key <= 1) continue; // Skip header
            $processed++;

            $noresi = trim($row['A'] ?? '');
            $id_sku = trim($row['B'] ?? '');
            $harga  = isset($row['C']) && is_numeric($row['C']) ? (float)$row['C'] : 0;

            if (empty($noresi)) continue;

            $updates[] = [
                'noresi'      => $noresi,
                'id_sku'      => $id_sku,
                'harga'       => $harga,
                'modified_at' => date('Y-m-d H:i:s'),
                'modified_by' => $user_id,
            ];

            if (count($updates) >= 500) {
                $this->db->update_batch('tblprintresi', $updates, 'noresi');
                $countUpdate += count($updates);
                $updates = [];
            }
        }

        if (!empty($updates)) {
            $this->db->update_batch('tblprintresi', $updates, 'noresi');
            $countUpdate += count($updates);
        }

        $this->db->trans_complete();
        return "Berhasil update data penjualan. Total: $countUpdate resi diperbarui.";
    }

    // =========================================================
    // LIST DATA HPP
    // =========================================================
    function get_data($data)
    {
        if ($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        } else {
            $this->db->order_by('updated', 'DESC');
        }

        if (!empty($data['search'])) {
            $this->db->group_start();
            $x = 0;
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                if ($x == 0) $this->db->like($sterm, $data['search']);
                else         $this->db->or_like($sterm, $data['search']);
                $x++;
            }
            $this->db->group_end();
        }

        // Only show SKUs that have HPP set
        $this->db->where('hpp >', 0);
        $this->db->select('id_sku, nama_sku, bundle, variasi, hpp, lokasi, total_stok, updated');
        $this->db->limit($data['length'], $data['start']);

        return $this->db->get('tblsku');
    }

    function get_total_data($data)
    {
        if (!empty($data['search'])) {
            $this->db->group_start();
            $x = 0;
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                if ($x == 0) $this->db->like($sterm, $data['search']);
                else         $this->db->or_like($sterm, $data['search']);
                $x++;
            }
            $this->db->group_end();
        }
        $this->db->where('hpp >', 0);
        $query = $this->db->select("count(1) as num")->get("tblsku");
        $result = $query->row();
        return isset($result) ? $result->num : 0;
    }

    function get_stats()
    {
        $query = $this->db->select('COUNT(id_sku) as total_sku, AVG(hpp) as avg_hpp, SUM(total_stok) as total_stok')
                          ->where('hpp >', 0)
                          ->get('tblsku');
        $row = $query->row();
        return [
            'total_sku'  => $row ? (int)$row->total_sku : 0,
            'avg_hpp'    => $row ? 'Rp ' . number_format($row->avg_hpp, 0, ',', '.') : 'Rp 0',
            'total_stok' => $row ? (int)$row->total_stok : 0,
        ];
    }

    // =========================================================
    // LAPORAN CONTROL PENJUALAN
    // Join tblprintresi (harga, id_sku) <-> tblsku (hpp)
    // Per-resi view: setiap baris = 1 resi
    // =========================================================
    function get_laporan($data)
    {
        $this->db->select('
            r.id_printresi,
            r.noresi,
            r.toko,
            r.tanggal_printresi,
            r.status_pesanan,
            r.id_sku        AS resi_id_sku,
            r.harga,
            s.nama_sku,
            s.bundle,
            s.variasi,
            s.hpp,
            (r.harga - s.hpp) AS profit,
            CASE WHEN s.hpp > 0 THEN ROUND(((r.harga - s.hpp) / s.hpp) * 100, 2) ELSE NULL END AS margin_pct
        ');
        $this->db->from('tblprintresi r');
        $this->db->join('tblsku s', 'r.id_sku = s.id_sku', 'left');

        // Filter tanggal
        if (!empty($data['tgl_awal'])) {
            $this->db->where('DATE(r.tanggal_printresi) >=', $data['tgl_awal']);
        }
        if (!empty($data['tgl_akhir'])) {
            $this->db->where('DATE(r.tanggal_printresi) <=', $data['tgl_akhir']);
        }

        // Filter SKU / nama / noresi / toko
        if (!empty($data['search_sku'])) {
            $this->db->group_start();
            $this->db->like('r.id_sku',   $data['search_sku']);
            $this->db->or_like('s.nama_sku', $data['search_sku']);
            $this->db->or_like('r.noresi',   $data['search_sku']);
            $this->db->or_like('r.toko',     $data['search_sku']);
            $this->db->group_end();
        }

        // Filter toko
        if (!empty($data['filter_toko'])) {
            $this->db->where('r.toko', $data['filter_toko']);
        }

        // Filter status pesanan
        if (!empty($data['filter_status'])) {
            $this->db->where('r.status_pesanan', $data['filter_status']);
        }

        // Filter Analisa (Status Bermasalah/Rugi/Tipis)
        if (!empty($data['filter_analisa'])) {
            if ($data['filter_analisa'] === 'BERMASALAH') {
                $this->db->where('(r.harga < s.hpp OR r.harga <= (s.hpp * 1.05))');
            } elseif ($data['filter_analisa'] === 'RUGI') {
                $this->db->where('r.harga < s.hpp');
            } elseif ($data['filter_analisa'] === 'TIPIS') {
                $this->db->where('r.harga >= s.hpp AND r.harga <= (s.hpp * 1.05)');
            } elseif ($data['filter_analisa'] === 'AMAN') {
                $this->db->where('r.harga > (s.hpp * 1.05)');
            }
        }

        // Hanya tampilkan resi yang sudah punya harga
        $this->db->where('r.harga >', 0);

        $this->db->order_by('r.tanggal_printresi', 'DESC');
        $this->db->limit($data['length'], $data['start']);

        return $this->db->get();
    }

    function get_total_laporan($data)
    {
        $this->db->select('COUNT(r.id_printresi) as num');
        $this->db->from('tblprintresi r');
        $this->db->join('tblsku s', 'r.id_sku = s.id_sku', 'left');

        if (!empty($data['tgl_awal'])) {
            $this->db->where('DATE(r.tanggal_printresi) >=', $data['tgl_awal']);
        }
        if (!empty($data['tgl_akhir'])) {
            $this->db->where('DATE(r.tanggal_printresi) <=', $data['tgl_akhir']);
        }
        if (!empty($data['search_sku'])) {
            $this->db->group_start();
            $this->db->like('r.id_sku',   $data['search_sku']);
            $this->db->or_like('s.nama_sku', $data['search_sku']);
            $this->db->or_like('r.noresi',   $data['search_sku']);
            $this->db->or_like('r.toko',     $data['search_sku']);
            $this->db->group_end();
        }
        if (!empty($data['filter_toko'])) {
            $this->db->where('r.toko', $data['filter_toko']);
        }
        if (!empty($data['filter_status'])) {
            $this->db->where('r.status_pesanan', $data['filter_status']);
        }

        if (!empty($data['filter_analisa'])) {
            if ($data['filter_analisa'] === 'BERMASALAH') {
                $this->db->where('(r.harga < s.hpp OR r.harga <= (s.hpp * 1.05))');
            } elseif ($data['filter_analisa'] === 'RUGI') {
                $this->db->where('r.harga < s.hpp');
            } elseif ($data['filter_analisa'] === 'TIPIS') {
                $this->db->where('r.harga >= s.hpp AND r.harga <= (s.hpp * 1.05)');
            } elseif ($data['filter_analisa'] === 'AMAN') {
                $this->db->where('r.harga > (s.hpp * 1.05)');
            }
        }

        $this->db->where('r.harga >', 0);

        $query  = $this->db->get();
        $result = $query->row();
        return isset($result) ? $result->num : 0;
    }

    // Ringkasan totals untuk summary cards laporan
    function get_laporan_totals($data)
    {
        $this->db->select('
            COUNT(r.id_printresi)  AS total_resi,
            SUM(r.harga)           AS total_harga,
            SUM(s.hpp)             AS total_hpp,
            SUM(r.harga - s.hpp)   AS total_profit,
            SUM(CASE WHEN (r.harga < s.hpp OR r.harga <= (s.hpp * 1.05)) THEN 1 ELSE 0 END) AS total_bermasalah
        ');
        $this->db->from('tblprintresi r');
        $this->db->join('tblsku s', 'r.id_sku = s.id_sku', 'left');
        $this->db->where('r.harga >', 0);

        if (!empty($data['tgl_awal'])) {
            $this->db->where('DATE(r.tanggal_printresi) >=', $data['tgl_awal']);
        }
        if (!empty($data['tgl_akhir'])) {
            $this->db->where('DATE(r.tanggal_printresi) <=', $data['tgl_akhir']);
        }
        if (!empty($data['filter_toko'])) {
            $this->db->where('r.toko', $data['filter_toko']);
        }
        if (!empty($data['filter_status'])) {
            $this->db->where('r.status_pesanan', $data['filter_status']);
        }

        $query  = $this->db->get();
        return $query->row();
    }

    // Ambil daftar toko unik untuk filter dropdown
    function get_toko_list()
    {
        $query = $this->db->select('DISTINCT toko')
                          ->where('toko IS NOT NULL')
                          ->where('toko !=', '')
                          ->order_by('toko', 'ASC')
                          ->get('tblprintresi');
        return $query->result_array();
    }
}
