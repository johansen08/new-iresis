<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Sku extends MY_Controller
{

  function __construct()
  {
    parent::__construct();

    $this->load->model('sku_fcd');
    $this->load->model('Notification');
  }

  public function upload_sku()
  {
      $this->show();
  }

  public function upload_sku_action()
  {
      // Buffer output to catch any unwanted echoes or warnings
      ob_start();

      try {
          if ($this->input->method() !== 'post') {
              throw new Exception(INVALID_REQUEST_METHOD);
          }

          if (!isset($_FILES['skuFile']) || $_FILES['skuFile']['error'] != 0) {
              throw new Exception(FAILED_SAVE_DATA);
          }

          // Set proper limits for large file processing
          ini_set('memory_limit', '3072M');
          ini_set('max_execution_time', 0);
          set_time_limit(0);

          $user_id = $this->data['user']['id_user'] ?? null;
          $upload_id = $this->input->post('upload_id') ?? '';
          $file = $_FILES['skuFile']['tmp_name'];

          $reader = IOFactory::createReader(IOFactory::identify($file));
          $reader->setReadDataOnly(true);
          $spreadsheet = $reader->load($file);
          $sheet = $spreadsheet->getActiveSheet();

          // Get all rows
          $dataRaw = $sheet->toArray(null, true, true, true);
          
          // Process insert/update
          $result = $this->sku_fcd->insert_sku_upload($dataRaw, $user_id, $upload_id);

          // Notify Admin
          $row_count = count($dataRaw) - 1; // excluding header
          $this->Notification->send("Upload SKU massal telah selesai ($row_count baris diproses).", "GENERAL", "Upload SKU Selesai");

          // Cleaning buffer before sending response
          if (ob_get_length()) ob_clean(); 
          
          $this->make_ajax_response(200, $result);

      } catch (Throwable $e) {
          // Cleaning buffer before sending error response
          if (ob_get_length()) ob_clean();

          $error_message = "Error: " . $e->getMessage();
          log_message('error', $error_message);
          
          $this->make_ajax_response(500, $error_message);
      }
      
      // Flush just in case, though make_ajax_response exits
      ob_end_flush();
  }

  public function get_progress_file($upload_id)
  {
      // Clean output just in case
      if (ob_get_length()) ob_clean();
      
      header('Content-Type: application/json');
      $file = sys_get_temp_dir() . '/sku_progress_' . preg_replace('/[^a-z0-9]/i', '', $upload_id);
      
      if (file_exists($file)) {
          $content = file_get_contents($file);
          echo $content;
      } else {
          echo json_encode(['processed' => 0, 'total' => 0, 'remaining' => 0, 'percentage' => 0]);
      }
      exit();
  }

  public function stock_terupdate()
  {
      // Restricted to webmaster only
      if ($this->data['user']['hakakses'] != 1) {
          redirect('welcome');
      }

      // Check if current menu is in the session menu tree
      // If not, refresh it (so user doesn't have to logout/login)
      if (strpos($this->session->userdata('html_menu_tree'), 'sku/stock_terupdate') === false) {
          $this->load->model('access_fcd');
          $this->load->helper('menu_helper');

          $list_menu = $this->access_fcd->get_access_menu($this->data['user']['hakakses'])->result_array();
          if (!empty($list_menu)) {
              $list_menu_tree = menu_to_tree($list_menu, $list_menu[0]);
              $html_menu_tree = tree_to_html_menu($list_menu_tree['child']);

              $this->session->set_userdata('list_menu', $list_menu);
              $this->session->set_userdata('list_menu_tree', $list_menu_tree);
              $this->session->set_userdata('html_menu_tree', $html_menu_tree);
              
              // Update local variable for main view rendering in this request
              $this->data['html_menu_tree'] = $html_menu_tree;
          }
      }

      $data['message'] = $this->session->flashdata('message');
      $this->show($data);
  }

  public function get_stock_data()
  {
      // Restricted to webmaster only
      if ($this->data['user']['hakakses'] != 1) {
          $this->make_ajax_response(403, 'Akses Terbatas', null);
      }

      $draw = intval($this->input->post('draw'));
      $order = $this->input->post('order');

      $data['start'] = intval($this->input->post('start'));
      $data['length'] = intval($this->input->post('length'));
      $data['search'] = $this->input->post('search')['value'];

      $col = 0;
      $dir = '';
      if (!empty($order)) {
          foreach ($order as $o) {
              $col = $o['column'];
              $dir = $o['dir'];
          }
      }

      $data['dir'] = $dir;

      $data['valid_columns'] = array(
          0 => 'id_sku',
          1 => 'nama_sku',
          2 => 'no_rak',
          3 => 'total_stok',
          4 => 'updated',
      );

      $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

      $this->load->model('sku_fcd');
      $list_sku = $this->sku_fcd->get_stock_terupdate_data($data);
      $total = $this->sku_fcd->get_total_stock_terupdate_data($data);

      $result_data = array();
      foreach ($list_sku->result() as $row) {
          $result_data[] = array(
              $row->id_sku,
              $row->nama_sku,
              $row->no_rak,
              $row->total_stok,
              $row->updated,
          );
      }

      $output = array(
          "draw" => $draw,
          "recordsTotal" => $total,
          "recordsFiltered" => $total,
          "data" => $result_data
      );

      echo json_encode($output);
      exit();
  }
}
