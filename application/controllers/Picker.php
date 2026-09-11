<?php

class Picker extends MY_Controller
{
    private static $status_cache = array(); // Simple static cache for status IDs
    
    // Clear cache method for debugging
    public function clear_status_cache() {
        self::$status_cache = array();
        echo "Status cache cleared";
    }
    
    function __construct()
    {
        parent::__construct();

        $this->load->model('picking_fcd');
        $this->load->model('employee_fcd');
        $this->load->model('Notification');
    }

    public function scan_picker()
    {
        $this->load->model('kpi_fcd');
        
        // Optimize: Load data in parallel
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        // Get total scan with fallback
        $total_scan_result = $this->picking_fcd->get_total_scan_user($this->data['user']['id_user'])->row();
        $data['total_scan'] = $total_scan_result ? $total_scan_result->total_scan : 0;
        
        // Ambil data status performa untuk PICKER
        $list_status_performa_raw = $this->kpi_fcd->get_status_performa_by_kategori('PICKER')->result_array();
        $data['list_status_performa'] = $list_status_performa_raw;

        $this->show($data);
    }

    public function save_scan_picker()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $picking['noresi'] = $this->input->post('noresi');
        $picking['yangambil_pegawai'] = $this->input->post('id_pegawaipicker');
        $picking['pending'] = '';
        
        // Debug: Tampilkan semua data POST
        $post_data = $this->input->post();
        log_message('debug', 'All POST data: ' . json_encode($post_data));
        
        // Ambil status performa yang dipilih (optimized with static cache)
        $status_performa_code = $this->input->post('status_performa');
        log_message('debug', 'Status performa code dari POST: ' . $status_performa_code);
        
        $status_id = null;
        if (!empty($status_performa_code)) {
            // Check static cache first
            if (!isset(self::$status_cache[$status_performa_code])) {
                $this->load->model('kpi_fcd');
                $status_id = $this->kpi_fcd->get_status_id_by_name($status_performa_code);
                
                // Fallback for 1_sku variations
                if (!$status_id && (strtoupper($status_performa_code) == '1_SKU' || strtoupper($status_performa_code) == '1_SKU_PICKER')) {
                    $status_id = $this->kpi_fcd->get_status_id_by_name('1_SKU_PICKER');
                }
                
                self::$status_cache[$status_performa_code] = $status_id;
            }
            
            $status_id = self::$status_cache[$status_performa_code];
            if ($status_id) {
                $picking['status_performa_id'] = $status_id;
                
                // CRITICAL FIX: Update active status in tblstatusperforma so reports show correct mode
                $this->load->model('kpi_fcd');
                $this->kpi_fcd->log_status_performa($this->data['user']['id_user'], $status_id);
                
                // Sync to session
                $this->session->set_userdata('user_status_performa', [
                    'id_statusperforma' => $status_id,
                    'kode_status' => $status_performa_code
                ]);
            }
        }

        // Fallback to session if still no status_id
        if (empty($picking['status_performa_id'])) {
            $user_status = $this->session->userdata('user_status_performa');
            if ($user_status) {
                if (is_object($user_status) && isset($user_status->id_statusperforma)) {
                    $picking['status_performa_id'] = $user_status->id_statusperforma;
                } elseif (is_array($user_status) && isset($user_status['id_statusperforma'])) {
                    $picking['status_performa_id'] = $user_status['id_statusperforma'];
                }
            }

            if (empty($picking['status_performa_id'])) {
                $this->load->model('kpi_fcd');
                $db_status = $this->kpi_fcd->get_user_status_performa($this->data['user']['id_user']);
                if ($db_status) {
                    $picking['status_performa_id'] = $db_status->id_statusperforma;
                } else {
                    $normal_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL_PICKER');
                    if ($normal_status_id) {
                        $picking['status_performa_id'] = $normal_status_id;
                    }
                }
            }
        }

        // Debug: Tampilkan data yang akan disimpan
        log_message('debug', 'Data picking yang akan disimpan: ' . json_encode($picking));
        log_message('debug', 'User data: ' . json_encode($this->data['user']));

        $save = $this->picking_fcd->save($picking, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            // Get item details for summary mode
            $items = [];
            $this->db->select('dr.sku, dr.jumlah, dr.no_rak, s.nama_sku');
            $this->db->from('tblprintresi pr');
            $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi');
            $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
            $this->db->where('pr.noresi', $picking['noresi']);
            $query = $this->db->get();
            $items = $query->result_array();

            header('Content-Type: application/json');
            echo json_encode([
                'code' => 201,
                'message' => SUCCESS_SAVE_DATA,
                'items' => $items
            ]);
            exit;
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function get_summary_by_picker()
    {
        $id_picker = $this->input->post('id_picker');
        if (empty($id_picker)) {
            $this->make_ajax_response(400, 'ID Picker tidak boleh kosong');
        }

        $this->db->select('dr.sku, SUM(dr.jumlah) as total_qty, dr.no_rak, s.nama_sku');
        $this->db->from('tblresiambilbarang rab');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = rab.id_resi');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
        $this->db->where('rab.yangambil_pegawai', $id_picker);
        $this->db->where('DATE(rab.tanggal_resiambilbarang)', date('Y-m-d'));
        $this->db->group_by('dr.sku, dr.no_rak');
        $this->db->order_by('dr.no_rak', 'ASC');
        $this->db->order_by('total_qty', 'DESC');
        
        $query = $this->db->get();
        $items = $query->result_array();

        // Get total resi count for this picker today
        $this->db->select('COUNT(DISTINCT rab.id_resi) as total_resi');
        $this->db->from('tblresiambilbarang rab');
        $this->db->where('rab.yangambil_pegawai', $id_picker);
        $this->db->where('DATE(rab.tanggal_resiambilbarang)', date('Y-m-d'));
        $resi_count = $this->db->get()->row()->total_resi;

        header('Content-Type: application/json');
        echo json_encode([
            'code' => 200,
            'items' => $items,
            'total_resi' => $resi_count
        ]);
        exit;
    }

    public function save_summary_log()
    {
        $picker_id = $this->input->post('picker_id');
        $total_resi = $this->input->post('total_resi');
        $total_qty = $this->input->post('total_qty');
        $sku_count = $this->input->post('sku_count');
        $summary_data = $this->input->post('summary_data');

        if (empty($summary_data)) {
            $this->make_ajax_response(400, 'Data summary kosong');
        }

        // Create table if not exists (Lazy migration)
        $this->db->query("CREATE TABLE IF NOT EXISTS tblpickingsummarylog (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            picker_id VARCHAR(50), 
            admin_id INT, 
            timestamp DATETIME, 
            total_resi INT, 
            total_qty INT, 
            sku_count INT, 
            summary_data LONGTEXT, 
            INDEX (timestamp)
        )");

        // Admin name for display
        $admin_name = $this->data['user']['name'];

        $data = [
            'picker_id' => $picker_id,
            'admin_id' => $this->data['user']['id_user'],
            'timestamp' => date('Y-m-d H:i:s'),
            'total_resi' => $total_resi,
            'total_qty' => $total_qty,
            'sku_count' => $sku_count,
            'summary_data' => $summary_data // JSON string from JS
        ];

        $this->db->insert('tblpickingsummarylog', $data);

        $this->make_ajax_response(201, 'Log berhasil disimpan secara terpusat');
    }

    public function get_summary_logs()
    {
        // Cleanup logs older than 24 hours
        $this->db->where('timestamp <', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $this->db->delete('tblpickingsummarylog');

        $this->db->select('t.*, p.nama_pegawai as picker_name, u.name as admin_name');
        $this->db->from('tblpickingsummarylog t');
        $this->db->join('tblpegawai p', 'p.id_pegawai = t.picker_id', 'left'); // Menggunakan id_pegawai
        $this->db->join('tbluser u', 'u.id_user = t.admin_id', 'left');
        $this->db->where('t.timestamp >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $this->db->order_by('t.timestamp', 'DESC');
        $this->db->limit(50);
        
        $logs = $this->db->get()->result_array();

        header('Content-Type: application/json');
        echo json_encode([
            'code' => 200,
            'logs' => $logs
        ]);
        exit;
    }

    public function scan_picker_summary()
    {
        $this->load->model('kpi_fcd');
        
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        $total_scan_result = $this->picking_fcd->get_total_scan_user($this->data['user']['id_user'])->row();
        $data['total_scan'] = $total_scan_result ? $total_scan_result->total_scan : 0;
        
        $list_status_performa_raw = $this->kpi_fcd->get_status_performa_by_kategori('PICKER')->result_array();
        $data['list_status_performa'] = $list_status_performa_raw;

        $this->show($data, 'picker/scan_picker_summary');
    }

    public function save_scan_picker_summary()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $picking['noresi'] = $noresi;
        $picking['yangambil_pegawai'] = $this->input->post('id_pegawaipicker');
        $picking['pending'] = '';
        
        $status_performa_code = $this->input->post('status_performa');
        
        $status_id = null;
        if (!empty($status_performa_code)) {
            if (!isset(self::$status_cache[$status_performa_code])) {
                $this->load->model('kpi_fcd');
                $status_id = $this->kpi_fcd->get_status_id_by_name($status_performa_code);
                
                // Fallback for 1_sku variations
                if (!$status_id && (strtoupper($status_performa_code) == '1_SKU' || strtoupper($status_performa_code) == '1_SKU_PICKER')) {
                    $status_id = $this->kpi_fcd->get_status_id_by_name('1_SKU_PICKER');
                }
                
                self::$status_cache[$status_performa_code] = $status_id;
            }
            $status_id = self::$status_cache[$status_performa_code];
            if ($status_id) {
                $picking['status_performa_id'] = $status_id;
                
                // CRITICAL FIX: Update active status in tblstatusperforma
                $this->load->model('kpi_fcd');
                $this->kpi_fcd->log_status_performa($this->data['user']['id_user'], $status_id);
                
                // Sync to session
                $this->session->set_userdata('user_status_performa', [
                    'id_statusperforma' => $status_id,
                    'kode_status' => $status_performa_code
                ]);
            }
        }

        // Fallback to session if still no status_id
        if (empty($picking['status_performa_id'])) {
            $user_status = $this->session->userdata('user_status_performa');
            if ($user_status) {
                if (is_object($user_status) && isset($user_status->id_statusperforma)) {
                    $picking['status_performa_id'] = $user_status->id_statusperforma;
                } elseif (is_array($user_status) && isset($user_status['id_statusperforma'])) {
                    $picking['status_performa_id'] = $user_status['id_statusperforma'];
                }
            }
            if (empty($picking['status_performa_id'])) {
                $this->load->model('kpi_fcd');
                $db_status = $this->kpi_fcd->get_user_status_performa($this->data['user']['id_user']);
                if ($db_status) {
                    $picking['status_performa_id'] = $db_status->id_statusperforma;
                } else {
                    $normal_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL_PICKER');
                    if ($normal_status_id) {
                        $picking['status_performa_id'] = $normal_status_id;
                    }
                }
            }
        }

        $save = $this->picking_fcd->save($picking, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        // Get item details for summary
        $items = [];
        if ($save['affected_rows'] > 0) {
            $this->db->select('dr.sku, dr.jumlah, dr.no_rak, s.nama_sku');
            $this->db->from('tblprintresi pr');
            $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi');
            $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
            $this->db->where('pr.noresi', $noresi);
            $query = $this->db->get();
            $items = $query->result_array();

            echo json_encode(['code' => 201, 'message' => SUCCESS_SAVE_DATA, 'items' => $items, 'noresi' => $noresi]);
            exit;
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function scan_picker_preorder()
    {
        $this->load->model('kpi_fcd');
        
        // Optimize: Load data in parallel
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        // Get total scan with fallback
        $total_scan_result = $this->picking_fcd->get_total_scan_preorder_user($this->data['user']['id_user'])->row();
        $data['total_scan'] = $total_scan_result ? $total_scan_result->total_scan : 0;
        
        // Ambil data status performa untuk PICKER
        $list_status_performa_raw = $this->kpi_fcd->get_status_performa_by_kategori('PICKER')->result_array();
        $data['list_status_performa'] = $list_status_performa_raw;

        $this->show($data, 'picker/scan_picker_preorder');
    }

    public function save_scan_picker_preorder()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $picking['noresi'] = $this->input->post('noresi');
        $picking['yangambil_pegawai'] = $this->input->post('id_pegawaipicker');
        $picking['pending'] = '';
        $picking['is_preorder'] = $this->input->post('is_preorder') ?? 0;
        
        // Debug: Tampilkan semua data POST
        $post_data = $this->input->post();
        log_message('debug', 'All POST data: ' . json_encode($post_data));
        
        // Ambil status performa yang dipilih (optimized with static cache)
        $status_performa_code = $this->input->post('status_performa');
        log_message('debug', 'Status performa code dari POST (Preorder): ' . $status_performa_code);
        
        $status_id = null;
        if (!empty($status_performa_code)) {
            // Check static cache first
            if (!isset(self::$status_cache[$status_performa_code])) {
                $this->load->model('kpi_fcd');
                $status_id = $this->kpi_fcd->get_status_id_by_name($status_performa_code);
                
                // Fallback for 1_sku variations
                if (!$status_id && (strtoupper($status_performa_code) == '1_SKU' || strtoupper($status_performa_code) == '1_SKU_PICKER')) {
                    $status_id = $this->kpi_fcd->get_status_id_by_name('1_SKU_PICKER');
                }
                
                self::$status_cache[$status_performa_code] = $status_id;
            }
            
            $status_id = self::$status_cache[$status_performa_code];
            if ($status_id) {
                $picking['status_performa_id'] = $status_id;
                
                // CRITICAL FIX: Update active status in tblstatusperforma
                $this->load->model('kpi_fcd');
                $this->kpi_fcd->log_status_performa($this->data['user']['id_user'], $status_id);
                
                // Sync to session
                $this->session->set_userdata('user_status_performa', [
                    'id_statusperforma' => $status_id,
                    'kode_status' => $status_performa_code
                ]);
            }
        }

        // Fallback to session/database if still no status_id
        if (empty($picking['status_performa_id'])) {
            $user_status = $this->session->userdata('user_status_performa');
            if ($user_status) {
                if (is_object($user_status) && isset($user_status->id_statusperforma)) {
                    $picking['status_performa_id'] = $user_status->id_statusperforma;
                } elseif (is_array($user_status) && isset($user_status['id_statusperforma'])) {
                    $picking['status_performa_id'] = $user_status['id_statusperforma'];
                }
            }
            
            if (empty($picking['status_performa_id'])) {
                $this->load->model('kpi_fcd');
                $db_status = $this->kpi_fcd->get_user_status_performa($this->data['user']['id_user']);
                if ($db_status) {
                    $picking['status_performa_id'] = $db_status->id_statusperforma;
                } else {
                    $normal_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL_PICKER');
                    if ($normal_status_id) {
                        $picking['status_performa_id'] = $normal_status_id;
                    }
                }
            }
        }

        // Debug: Tampilkan data yang akan disimpan
        log_message('debug', 'Data picking yang akan disimpan: ' . json_encode($picking));
        log_message('debug', 'User data: ' . json_encode($this->data['user']));

        $save = $this->picking_fcd->save($picking, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function search_picker()
    {
        $this->show();
    }

    public function process_kpi_queue()
    {
        // Process KPI queue in background
        $this->picking_fcd->process_kpi_queue();
        $this->make_ajax_response(200, 'KPI queue processed');
    }

    public function get_search_picker_data()
    {
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
            0 => null,
            1 => 't2.noresi',
            2 => 't3.nama_pegawai',
            3 => 't.tanggal_resiambilbarang',
            4 => 't.tanggal_resiambilbarang',
            5 => 't4.name',
            6 => null,
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->picking_fcd->get_data($data);

        $total = $this->picking_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->nama_pegawai,
                date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
                date('H:i:s', strtotime($row->tanggal_resiambilbarang)),
                $row->name,
                $row->nama_komputer,
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

    public function master_picker()
    {
        $data['message'] = $this->session->flashdata('message');
        $data['list_employee'] = $this->employee_fcd->get_employee()->result_array();
        $data['list_status'] = ['AKTIF', 'TIDAK AKTIF'];
        $data['list_picker'] = $this->picking_fcd->get_picker()->result_array();

        $this->show($data);
    }

    public function save_master_picker()
    {
        if ($this->input->method() == 'get') {
            redirect('404_override');
        }

        $picker['id_pegawai'] =  $this->input->post('id_pegawai');
        $picker['status_aktif'] =  $this->input->post('status_aktif');

        $save = $this->picking_fcd->save_picker($picker);

        if ($save['affected_rows'] > 0) {
            $this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
        } else {
            $this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
        }

        redirect('picker/master_picker');
    }

    public function delete_master_picker($id_namaambilbarang)
    {
        $save = $this->picking_fcd->destroy_picker($id_namaambilbarang);

        if ($save['affected_rows'] > 0) {
            $this->set_message('Success', SUCCESS_REMOVE_DATA, 'information');
        } else {
            $this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
        }

        redirect('picker/master_picker');
    }

    public function pending_picker()
    {
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();

        $this->show($data);
    }

    public function save_pending_picker()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $picking['noresi'] = $this->input->post('noresi');
        $picking['yangambil_pegawai'] = $this->input->post('id_pegawaipicker');
        $picking['pending'] = 'ya';

        $save = $this->picking_fcd->save($picking, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function update_picker()
    {
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();

        $this->show($data);
    }

    public function save_update_picker()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $picking['noresi'] = $this->input->post('noresi');
        $picking['yangambil_pegawai'] = $this->input->post('id_pegawaipicker');

        $save = $this->picking_fcd->save($picking, $this->data['user'], PICKING_UPDATE_PACKER);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function kurangan_picker()
    {
        $this->load->model('receipt_fcd');
        
        $data = [];
        $data['noresi'] = null;

        if ($this->input->method() == 'post') {
            $noresi = $this->input->post('noresi');
            
            if (!empty($noresi)) {
                // Check if receipt exists
                $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
                
                if (!empty($receipt)) {
                    $data['noresi'] = $noresi;
                    $data['id_printresi'] = $receipt['id_printresi'];
                } else {
                    $data['error_message'] = 'Nomor resi tidak ditemukan';
                }
            }
        }

        $this->show($data);
    }

    public function get_kurangan_picker_data($noresi = null)
    {
        // Get noresi from URL parameter or POST
        if (empty($noresi)) {
            $noresi = $this->input->post('noresi');
        }
        
        if (empty($noresi)) {
            $noresi = $this->uri->segment(3);
        }
        
        $noresi = urldecode($noresi);
        
        if (empty($noresi)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Nomor resi tidak boleh kosong'));
            exit();
        }

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');
        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'] ?? '';

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;
        $data['valid_columns'] = [
            0 => null,
            1 => 's.nama_sku',
            2 => 'dr.sku',
            3 => 'dr.jumlah',
            4 => null
        ];
        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : null;

        // Build base query for counting total
        $this->db->select('dr.id_detail_resi');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'inner');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->where('pr.noresi', $noresi);
        
        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('dr.sku', $data['search']);
            $this->db->or_like('s.nama_sku', $data['search']);
            $this->db->group_end();
        }
        
        // Get total count
        $total = $this->db->count_all_results();
        
        // Now build query for data with all columns
        $this->db->select('
            dr.id_detail_resi,
            dr.sku,
            dr.jumlah as qty,
            (CASE 
                WHEN s.nama_sku IS NOT NULL AND TRIM(s.nama_sku) != \'\' AND TRIM(s.nama_sku) != \'False\' THEN s.nama_sku
                WHEN ps.nama_sku IS NOT NULL AND TRIM(ps.nama_sku) != \'\' AND TRIM(ps.nama_sku) != \'False\' THEN CONCAT(ps.nama_sku, \' \', IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\'))
                ELSE CONCAT(IFNULL(s.nama_bundle, \'\'), \' \', IFNULL(s.variasi, \'\')) 
            END) as nama_barang,
            pr.noresi,
            pr.id_printresi,
            COALESCE(dr.qty_kurang, 0) as qty_kurang
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'inner');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
        $this->db->join('tblsku ps', 'ps.id_sku = s.bundle', 'left');
        $this->db->where('pr.noresi', $noresi);
        
        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('dr.sku', $data['search']);
            $this->db->or_like('s.nama_sku', $data['search']);
            $this->db->group_end();
        }
        
        if ($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir']);
        } else {
            $this->db->order_by('dr.id_detail_resi', 'ASC');
        }
        
        // Apply limit
        if ($data['length'] > 0) {
            $this->db->limit($data['length'], $data['start']);
        }
        
        $query = $this->db->get();
        $items = $query->result_array();

        $table_number = $data['start'] + 1;
        $data_table = [];
        
        foreach ($items as $item) {
            $qty_kurang = $item['qty_kurang'] ?? 0;
            $qty = $item['qty'] ?? 0;

            $data_table[] = [
                $table_number++ . '.',
                $item['sku'] ?? '-',
                $item['nama_barang'] ?? '-',
                $qty,
                '<input type="number" class="form-control qty_kurang" data-id-detail="' . $item['id_detail_resi'] . '" data-qty="' . $qty . '" value="' . $qty_kurang . '" min="0" max="' . $qty . '" step="1" style="width: 100px;" />'
            ];
        }

        $output = [
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data_table
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
        exit();
    }

    public function save_kurangan_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => INVALID_REQUEST_METHOD));
            exit();
        }

        $noresi = $this->input->post('noresi');
        $items = $this->input->post('items'); // Array of items with status_kurangan and qty_kurang

        if (empty($noresi)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Nomor resi tidak boleh kosong'));
            exit();
        }

        if (empty($items) || !is_array($items)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Data items tidak valid'));
            exit();
        }

        $this->load->model('receipt_fcd');
        
        // Get receipt ID
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
        if (empty($receipt)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 404, 'message' => 'Nomor resi tidak ditemukan'));
            exit();
        }

        // Save kurangan data for each item
        $saved_count = 0;
        foreach ($items as $item) {
            $id_detail_resi = $item['id_detail_resi'] ?? null;
            $qty_kurang = intval($item['qty_kurang'] ?? 0);

            if (empty($id_detail_resi)) continue;

            // Check if detail record exists
            $existing = $this->db->get_where('tbldetailprintresi', [
                'id_detail_resi' => $id_detail_resi
            ])->row_array();

            if (!empty($existing)) {
                // Only update status to 'Ya' if qty_kurang > 0, otherwise set to 'Tidak'
                $status_kurangan = $qty_kurang > 0 ? 'Ya' : 'Tidak';

                $update_data = [
                    'status_kurangan' => $status_kurangan,
                    'qty_kurang' => $qty_kurang > 0 ? $qty_kurang : 0
                ];
                
                // Simpan tanggal scan kurangan jika qty_kurang > 0 dan (belum ada tanggal atau status sebelumnya bukan 'Ya')
                if ($qty_kurang > 0 && ($existing['status_kurangan'] !== 'Ya' || empty($existing['tanggal_scan_kurangan']))) {
                    $update_data['tanggal_scan_kurangan'] = date('Y-m-d H:i:s');
                }
                
                $this->db->where('id_detail_resi', $id_detail_resi);
                $this->db->update('tbldetailprintresi', $update_data);
                if ($this->db->affected_rows() > 0) {
                    $saved_count++;
                }
            }
        }

        header('Content-Type: application/json');
        if ($saved_count > 0) {
            // KIRIM NOTIFIKASI KE TIM CS
            $pesan_notif = "Ada kurangan picker baru dari " . $this->data['user']['name'] . " untuk resi: " . $noresi;
            $this->Notification->send($pesan_notif, 'TIM CS', 'Kurangan Picker Baru');

            echo json_encode(array('code' => 201, 'message' => 'Data kurangan berhasil disimpan'));
        } else {
            echo json_encode(array('code' => 200, 'message' => 'Tidak ada data yang diupdate'));
        }
        exit();
    }
}