<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sku_fcd extends CI_Model
{

  function get_sku($sku_id = null)
  {
    if (!empty($sku_id)) {
      $this->db->where('id', $sku_id);
    }

    return $this->db->get('tblsku');
  }

  function get_sku_location_stock($id_sku)
  {
    // In tblsku, stock information is in the table itself
    return $this->db->get_where('tblsku', array('id_sku' => $id_sku));
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

    // replace sku (using tblsku consistently)
    $sku['updatedby'] = $user_id;
    $sku['updated'] = $timestamp;
    $this->db->update('tblsku', $sku, array('id_sku' => $sku['id_sku']));
    if ($this->db->affected_rows() == 0) {
      $sku['isactive'] = TRUE;
      $sku['createdby'] = $user_id;
      $sku['created'] = $timestamp;

      $this->db->insert('tblsku', $sku);
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
    $this->db->truncate('sku');
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

    $this->db->select('id, id_sku, nama_sku, berat, total_stok');
    
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

  /**
   * Petakan kolom file upload SKU berdasarkan NAMA header, bukan posisi kolom.
   *
   * File "persediaan gudang bundle" dari Jubelio sebelumnya dibaca dengan urutan
   * kolom tetap (A..K). Kalau Jubelio menggeser urutan kolomnya, data bisa masuk
   * ke field yang salah tanpa memicu error sama sekali. Fungsi ini mencari baris
   * header (maksimal 5 baris pertama) lalu memetakan tiap field ke kolomnya lewat
   * nama header.
   *
   * Kalau header tidak dikenali, kembali ke perilaku lama (header di baris 1,
   * kolom A..K) supaya file berformat lama tetap bisa diupload.
   *
   * @return array ['cols' => [field => kolom|null], 'header_row' => int, 'legacy' => bool, 'missing' => array]
   */
  private function _sku_upload_column_map($dataRaw)
  {
      // field => [kolom lama, alias header (cocok persis), kata terlarang di header]
      $spec = [
          'id_sku'     => ['A', ['sku', 'kode sku', 'kode barang', 'item code'], []],
          'nama_sku'   => ['B', ['nama', 'nama barang', 'nama produk', 'nama sku', 'item name'], ['bundle', 'rak']],
          'bundle'     => ['C', ['bundle', 'nama bundle', 'bundel'], []],
          'variasi'    => ['D', ['variasi', 'varian', 'variant'], []],
          'display'    => ['E', ['display', 'display barang', 'stok display'], ['rak']],
          'gudang'     => ['F', ['gudang', 'gudang barang', 'stok gudang'], ['rak']],
          'transit'    => ['G', ['transit', 'transit barang', 'stok transit'], ['rak']],
          'total_stok' => ['H', ['total', 'total stok', 'total barang', 'jumlah'], ['rak']],
          'link_foto'  => ['I', ['link foto', 'foto', 'gambar', 'image'], []],
          'no_rak'     => ['J', ['no rak', 'nomor rak', 'rak', 'no rak display', 'rak display'], ['gudang']],
          'berat'      => ['K', ['berat', 'berat gram', 'weight'], []],
      ];

      $norm = function ($v) {
          $v = strtolower(trim((string) $v));
          $v = preg_replace('/[^a-z0-9]+/', ' ', $v);
          return trim(preg_replace('/\s+/', ' ', $v));
      };

      $map_row = function ($rawRow) use ($spec, $norm) {
          $headers = [];
          foreach ($rawRow as $colKey => $cell) {
              $h = $norm($cell);
              if ($h !== '') {
                  $headers[$colKey] = $h;
              }
          }

          $cols  = [];
          $taken = [];

          // Tahap 1: header cocok persis dengan salah satu alias.
          foreach ($spec as $field => $def) {
              foreach ($headers as $colKey => $h) {
                  if (isset($taken[$colKey])) continue;
                  if (in_array($h, $def[1], TRUE)) {
                      $cols[$field]   = $colKey;
                      $taken[$colKey] = TRUE;
                      break;
                  }
              }
          }

          // Tahap 2: header memuat alias (mis. "No Rak Display"). Kata terlarang
          // menjaga supaya "No Rak Gudang" tidak diklaim sebagai rak display.
          foreach ($spec as $field => $def) {
              if (isset($cols[$field])) continue;
              foreach ($headers as $colKey => $h) {
                  if (isset($taken[$colKey])) continue;
                  $blocked = FALSE;
                  foreach ($def[2] as $bad) {
                      if (strpos($h, $bad) !== FALSE) { $blocked = TRUE; break; }
                  }
                  if ($blocked) continue;
                  foreach ($def[1] as $alias) {
                      if (strpos($h, $alias) !== FALSE) {
                          $cols[$field]   = $colKey;
                          $taken[$colKey] = TRUE;
                          break 2;
                      }
                  }
              }
          }

          return $cols;
      };

      // Cari baris header di 5 baris pertama, ambil yang paling banyak cocok.
      $best    = ['row' => NULL, 'cols' => [], 'score' => 0];
      $scanned = 0;
      foreach ($dataRaw as $rowKey => $rawRow) {
          if (++$scanned > 5) break;
          if (!is_array($rawRow)) continue;

          $cols  = $map_row($rawRow);
          $score = count($cols);
          if (isset($cols['id_sku']) && $score >= 3 && $score > $best['score']) {
              $best = ['row' => $rowKey, 'cols' => $cols, 'score' => $score];
          }
      }

      // Header tidak dikenali -> pakai urutan kolom lama.
      if ($best['row'] === NULL) {
          $legacy = [];
          foreach ($spec as $field => $def) {
              $legacy[$field] = $def[0];
          }
          log_message('error', 'SKU Upload: header tidak dikenali, memakai urutan kolom lama (A..K).');
          return ['cols' => $legacy, 'header_row' => 1, 'legacy' => TRUE, 'missing' => []];
      }

      $cols    = [];
      $missing = [];
      foreach ($spec as $field => $def) {
          if (isset($best['cols'][$field])) {
              $cols[$field] = $best['cols'][$field];
          } else {
              // Kolomnya memang tidak ada di file -> jangan tebak posisi. Field ini
              // dilewati supaya data lama di tblsku tidak tertimpa nilai kosong.
              $cols[$field] = NULL;
              $missing[]    = $field;
          }
      }

      log_message('debug', 'SKU Upload: header baris ' . $best['row'] . ', peta kolom ' . json_encode($cols));

      return ['cols' => $cols, 'header_row' => $best['row'], 'legacy' => FALSE, 'missing' => $missing];
  }

  function insert_sku_upload($dataRaw, $user_id, $upload_id = null)
  {
      // Disable db_debug to prevent HTML error output on database failure
      $db_debug = $this->db->db_debug;
      $this->db->db_debug = FALSE;

      $countInsert = 0;
      $countUpdate = 0;

      // Petakan kolom lewat nama header (fallback ke urutan kolom lama).
      $colMap    = $this->_sku_upload_column_map($dataRaw);
      $col       = $colMap['cols'];
      $headerRow = $colMap['header_row'];

      // Filter valid rows (lewati baris header dan baris di atasnya)
      $rows = [];
      foreach ($dataRaw as $key => $row) {
          if ($key <= $headerRow) continue; // Skip header
          $id_sku = trim($row[$col['id_sku']] ?? '');
          if (empty($id_sku)) continue;
          $rows[] = $row;
      }

      // Sort rows by Total descending to prioritize stock items
      $colTotal = $col['total_stok'];
      usort($rows, function($a, $b) use ($colTotal) {
          $totalA = ($colTotal !== null && isset($a[$colTotal])) ? (int)$a[$colTotal] : 0;
          $totalB = ($colTotal !== null && isset($b[$colTotal])) ? (int)$b[$colTotal] : 0;
          return $totalB - $totalA;
      });

      $totalRows = count($rows);
      $processed = 0;
      
      $progressFile = null;
      if ($upload_id) {
          $progressFile = sys_get_temp_dir() . '/sku_progress_' . preg_replace('/[^a-z0-9]/i', '', $upload_id);
      }

      // Pre-fetch all existing SKU IDs (Case-insensitive normalize)
      $existing_skus = [];
      $query = $this->db->select('id_sku')->get('tblsku');
      foreach ($query->result() as $row_db) {
          $existing_skus[strtolower(trim($row_db->id_sku))] = true;
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

          $id_sku = trim($row[$col['id_sku']] ?? '');

          $lokasi_parts = [];
          foreach (['display' => 'Display', 'gudang' => 'Gudang', 'transit' => 'Transit'] as $f => $label) {
              if ($col[$f] === null) continue;
              $val = $row[$col[$f]] ?? '';
              if ($val) $lokasi_parts[] = "$label: $val";
          }

          $data = [
              'id_sku'  => $id_sku,
              'updated' => date('Y-m-d H:i:s'),
          ];

          // Field yang kolomnya tidak ada di file sengaja TIDAK ditulis, supaya
          // data lama di tblsku tidak tertimpa nilai kosong.
          if ($col['nama_sku'] !== null) {
              $data['nama_sku'] = $row[$col['nama_sku']] ?? '';
          }
          if ($col['bundle'] !== null) {
              $bundle = $row[$col['bundle']] ?? '';
              $data['nama_bundle'] = $bundle;
              $data['bundle']      = $bundle;
          }
          if ($col['variasi'] !== null) {
              $data['variasi'] = $row[$col['variasi']] ?? '';
          }
          if ($col['display'] !== null || $col['gudang'] !== null || $col['transit'] !== null) {
              $data['lokasi'] = implode(', ', $lokasi_parts);
          }
          if ($col['total_stok'] !== null) {
              $tot = $row[$col['total_stok']] ?? null;
              $data['total_stok'] = is_numeric($tot) ? (int)$tot : 0;
          }
          if ($col['link_foto'] !== null) {
              $data['link_foto'] = substr($row[$col['link_foto']] ?? '', 0, 255);
          }
          if ($col['berat'] !== null) {
              $brt = $row[$col['berat']] ?? null;
              $data['berat'] = is_numeric($brt) ? (int)$brt : 0;
          }

          $no_rak = $col['no_rak'] !== null ? trim(substr($row[$col['no_rak']] ?? '', 0, 255)) : '';

          $id_sku_lower = strtolower($id_sku);
          if (isset($existing_skus[$id_sku_lower])) {
              // Hanya update no_rak jika ada isinya di xlsx (jangan timpa dengan kosong).
              // update_batch memakai "ELSE kolom END" jadi baris tanpa key ini aman.
              if ($no_rak !== '') {
                  $data['no_rak'] = $no_rak;
              }
              $updates[] = $data;
          } else {
              // Insert wajib seragam kolomnya: set_insert_batch() membatalkan SELURUH
              // batch kalau ada baris yang key-nya beda, jadi no_rak selalu diikutkan.
              $data['no_rak'] = ($no_rak !== '' ? $no_rak : null);
              $inserts[] = $data;
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
          $this->db->db_debug = $db_debug;
          log_message('error', 'SKU Upload Transaction Failed.');
          throw new Exception("Transaction failed. Data might not be saved correctly.");
      }

      if ($progressFile && @file_exists($progressFile)) {
          @unlink($progressFile);
      }

      // Restore db_debug
      $this->db->db_debug = $db_debug;

  log_message('debug', "SKU Upload finished. Inserted: $countInsert, Updated: $countUpdate");
      // Catatan pemetaan kolom supaya operator tahu file-nya dibaca dengan cara apa.
      $catatan = '';
      if ($colMap['legacy']) {
          $catatan = ' [PERINGATAN: header file tidak dikenali, memakai urutan kolom lama A..K]';
      } elseif (!empty($colMap['missing'])) {
          $catatan = ' [kolom tidak ada di file, dilewati: ' . implode(', ', $colMap['missing']) . ']';
      }

      return "Upload Berhasil. Insert: $countInsert, Update: $countUpdate. Total: $totalRows data." . $catatan;
  }

  function get_stock_terupdate_data($data)
  {
      if ($data['order'] != null) {
          $this->db->order_by($data['order'], $data['dir'], FALSE);
      } else {
          $this->db->order_by('updated', 'DESC');
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

      $this->db->select('id_sku, nama_sku, no_rak, total_stok, updated');
      $this->db->limit($data['length'], $data['start']);

      return $this->db->get('tblsku');
  }

  function get_total_stock_terupdate_data($data)
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
}
