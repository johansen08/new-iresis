<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sku_fcd extends CI_Model
{

  function get_sku($sku_id = null)
  {
    $criterias['isactive'] = TRUE;

    if (!empty($sku_id)) {
      $criterias['id'] = $sku_id;
    }

    $this->db->where($criterias);

    return $this->db->get('tblsku');
  }

  function get_sku_location_stock($item_id)
  {
    return $this->db->get_where('skulocationstock', array('skulocationstock.item_id' => $item_id, 'skulocationstock.isactive' => TRUE));
  }

  function save($sku, $user_id, $sync_stock = false)
  {
    $timestamp = date('Y-m-d H:i:s');
    /**
     * 1. initiate list_skulocationstock
     * 2. unset location_stocks from sku
     * 3. begin transactional
     * 4. replace sku
     * 5. replace skulocationstock
     * 6. end transactional
     * 7. handling transactional
     * 8. return the result
     */

    // initiate list_skulocationstock
    $list_skulocationstock = $sku['location_stocks'];

    // unset location_stocks from sku
    unset($sku['location_stocks']);

    // begin transactional
    $this->db->trans_start();

    // replace sku
    $sku['updatedby'] = $user_id;
    $sku['updated'] = $timestamp;
    $this->db->update('sku', $sku, array('item_id' => $sku['item_id']));
    if ($this->db->affected_rows() == 0) {
      $sku['isactive'] = TRUE;
      $sku['createdby'] = $user_id;
      $sku['created'] = $timestamp;

      $this->db->insert('sku', $sku);
    }

    if ($sync_stock) {
      // replace skulocationstock
      foreach ($list_skulocationstock as $skulocationstock) :
        unset($skulocationstock['location_code']);

        $skulocationstock['updatedby'] = $user_id;
        $skulocationstock['updated'] = $timestamp;
        $this->db->update('skulocationstock', $skulocationstock, array('item_id' => $skulocationstock['item_id'], 'location_id' => $skulocationstock['location_id']));
        if ($this->db->affected_rows() == 0) {
          $skulocationstock['isactive'] = TRUE;
          $skulocationstock['createdby'] = $user_id;
          $skulocationstock['created'] = $timestamp;

          $this->db->insert('skulocationstock', $skulocationstock);
        }
      endforeach;
    }

    // end transactional
    $this->db->trans_complete();

    // handling transactional
    if ($this->db->trans_status() === FALSE) {
      return false;
    }

    // return the result
    return true;
  }

  function truncate_sku()
  {
    $this->db->truncate('tblsku');
  }

  function truncate_sku_location_stock()
  {
    $this->db->truncate('skulocationstock');
  }

  function get_data($data, $list_location)
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

    $this->db->select('sku.id, sku.item_id, sku.item_code, sku.item_name, sku.average_cost');

    foreach ($list_location as $location) {
      $this->db->select('sum(case when skulocationstock.location_id = ' . $location['paramvalue2'] . ' THEN on_hand END) `' . $location['paramvalue2'] . '_on_hand`');
      $this->db->select('sum(case when skulocationstock.location_id = ' . $location['paramvalue2'] . ' THEN on_order END) `' . $location['paramvalue2'] . '_on_order`');
      $this->db->select('sum(case when skulocationstock.location_id = ' . $location['paramvalue2'] . ' THEN reserved END) `' . $location['paramvalue2'] . '_reserved`');
      $this->db->select('sum(case when skulocationstock.location_id = ' . $location['paramvalue2'] . ' THEN available END) `' . $location['paramvalue2'] . '_available`');
    }

    $this->db->join('skulocationstock', 'skulocationstock.item_id = sku.item_id', 'left');

    $this->db->group_by('sku.id, sku.item_id, sku.item_code, sku.item_name, sku.average_cost');

    $this->db->limit($data['length'], $data['start']);

    return $this->db->get('tblsku');
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

    $query = $this->db->select("count(1) as num")->get("tblsku");
    $result = $query->row();

    return isset($result) ? $result->num : 0;
  }

  function insert_sku_upload($dataRaw, $user_id, $upload_id = null)
  {
      $countInsert = 0;
      $countUpdate = 0;

      // We still use $dataRaw for sorting because it's already in memory from IOFactory load
      // Filter valid rows (skip header row 1)
      $rows = [];
      foreach ($dataRaw as $key => $row) {
          if ($key <= 1) continue; // Skip header
          $id_sku = trim($row['A'] ?? '');
          if (empty($id_sku)) continue;
          $rows[] = $row;
      }

      // Sort rows by Total (Column H) descending to prioritize stock items
      usort($rows, function($a, $b) {
          $totalA = isset($a['H']) ? (int)$a['H'] : 0;
          $totalB = isset($b['H']) ? (int)$b['H'] : 0;
          return $totalB - $totalA; 
      });

      $totalRows = count($rows);
      $processed = 0;
      
      $progressFile = null;
      if ($upload_id) {
          $progressFile = sys_get_temp_dir() . '/sku_progress_' . preg_replace('/[^a-z0-9]/i', '', $upload_id);
      }

      // Pre-fetch all existing SKU IDs
      $existing_skus = [];
      $query = $this->db->select('id_sku')->get('tblsku');
      foreach ($query->result() as $row_db) {
          $existing_skus[$row_db->id_sku] = true;
      }

      $this->db->trans_start(); // START TRANSACTION

      $inserts = [];
      $updates = [];

      foreach ($rows as $row) {
          $processed++;

          // Update progress in file every 10 rows
          if ($progressFile && ($processed % 10 == 0 || $processed == $totalRows)) {
              $progressData = [
                  'status' => 'Processing',
                  'processed' => $processed,
                  'total' => $totalRows,
                  'remaining' => $totalRows - $processed,
                  'percentage' => round(($processed / $totalRows) * 100)
              ];
              @file_put_contents($progressFile, json_encode($progressData));
          }

          $id_sku = trim($row['A'] ?? '');
          $nama_sku = $row['B'] ?? '';
          $bundle = $row['C'] ?? '';
          $variasi = $row['D'] ?? '';
          
          $display = $row['E'] ?? '';
          $gudang = $row['F'] ?? '';
          $transit = $row['G'] ?? '';
          $lokasi_parts = [];
          if ($display) $lokasi_parts[] = "Display: $display";
          if ($gudang) $lokasi_parts[] = "Gudang: $gudang";
          if ($transit) $lokasi_parts[] = "Transit: $transit";
          $lokasi = implode(', ', $lokasi_parts);

          $total_stok = $row['H'] ?? 0;
          $link_foto = $row['I'] ?? '';
          $no_rak = $row['J'] ?? '';
          $berat = $row['K'] ?? 0;

          $data = [
              'nama_sku' => $nama_sku,
              'nama_bundle' => $bundle,
              'bundle' => $bundle,
              'variasi' => $variasi,
              'lokasi' => $lokasi,
              'total_stok' => $total_stok,
              'link_foto' => $link_foto,
              'no_rak' => $no_rak,
              'berat' => $berat,
              'id_sku' => $id_sku
          ];

          if (isset($existing_skus[$id_sku])) {
              $updates[] = $data;
          } else {
              $inserts[] = $data;
              $existing_skus[$id_sku] = true;
          }

          // Batch process to avoid huge memory usage for very large files
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
          $progressData = [
              'status' => 'Finalizing',
              'processed' => $totalRows,
              'total' => $totalRows,
              'remaining' => 0,
              'percentage' => 100
          ];
          @file_put_contents($progressFile, json_encode($progressData));
      }

      $this->db->trans_complete(); // END TRANSACTION

      if ($this->db->trans_status() === FALSE) {
          log_message('error', 'SKU Upload Transaction Failed.');
          throw new Exception("Transaction failed. Data might not be saved correctly.");
      }

      if ($progressFile && @file_exists($progressFile)) {
          @unlink($progressFile);
      }

      log_message('debug', "SKU Upload finished. Inserted: $countInsert, Updated: $countUpdate");
      return "Upload Berhasil. Insert: $countInsert, Update: $countUpdate. Total: $totalRows data.";
  }

}
