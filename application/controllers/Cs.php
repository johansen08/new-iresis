<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cs extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('receipt_fcd');
        $this->load->model('retur_fcd');
        $this->load->model('packer_fcd');
        $this->load->model('Notification');
        $this->load->model('video_packing_fcd');
    }

    public function laporan_kurangan_picker()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        $this->show($data);
    }

    public function get_laporan_kurangan_picker_data()
    {
        // Get and validate input parameters
        $reportrange = $this->input->post('reportrange') ?: date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // DataTable parameters
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        // Sorting parameters
        $order = $this->input->post('order');
        $order_column = null;
        $order_dir = 'DESC';

        $valid_columns = [
            1 => 'dr.sku',
            2 => 'pr.noresi',
            3 => 'dr.no_pesanan',
            4 => 'm.nama_marketplace',
            5 => 'pr.tanggal_printresi',
            6 => 'pr.tanggal_bataskirim',
            7 => 'dr.qty_kurang',
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']);
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Build WHERE conditions (reusable for both count and data query)
        $this->_apply_kurangan_picker_filters($start_date, $end_date, $search_value);

        // Get total count
        $total = $this->db->count_all_results('', false); // Keep query for reuse

        // Build data query with same filters
        $this->db->select('
            dr.id_detail_resi,
            dr.sku,
            dr.qty_kurang,
            dr.tanggal_scan_kurangan,
            dr.no_pesanan,
            pr.noresi,
            pr.id_printresi,
            pr.tanggal_printresi,
            pr.tanggal_bataskirim,
            m.nama_marketplace,
            COALESCE(s.nama_sku, dr.sku) as nama_barang
        ');

        // Apply ordering
        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
        } else {
            $this->db->order_by('COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi)', 'DESC');
        }

        // Apply pagination
        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

        // Build table data
        $data_table = [];
        $row_number = $start + 1;

        foreach ($items as $item) {
            $data_table[] = [
                $row_number++ . '.',
                $item['sku'] ?? '-',
                $item['noresi'] ?? '-',
                $item['no_pesanan'] ?? '-',
                $item['nama_marketplace'] ?? '-',
                !empty($item['tanggal_printresi']) ? date('d/m/Y', strtotime($item['tanggal_printresi'])) : '-',
                !empty($item['tanggal_bataskirim']) ? date('d/m/Y', strtotime($item['tanggal_bataskirim'])) : '-',
                $item['qty_kurang'] ?? 0,
                '<input type="checkbox" class="row-select" data-id-detail="' . $item['id_detail_resi'] . '" data-noresi="' . htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8') . '" />',
                '<div style="min-width: 130px;">
                    <button type="button" class="btn btn-success btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Stock Ready" data-action="Stock Ready" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-check"></i></button>
                    <button type="button" class="btn btn-warning btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Minta SJ" data-action="Minta SJ" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-file-text-o"></i></button>
                    <button type="button" class="btn btn-info btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Pergantian Barang" data-action="Pergantian Barang" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-exchange"></i></button>
                </div>'
            ];
        }

        // Return JSON response
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data_table
            ]));
    }

    /**
     * Apply filters for kurangan picker queries
     * Uses COALESCE to simplify date filtering logic
     */
    private function _apply_kurangan_picker_filters($start_date, $end_date, $search_value = '')
    {
        $this->db->from('tbldetailprintresi dr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');

        // Main filter: only get records with status_kurangan = 'Ya'
        $this->db->where('dr.status_kurangan', 'Ya');

        // Only show records with qty_kurang > 0
        $this->db->where('dr.qty_kurang >', 0);

        // Date filter: use tanggal_scan_kurangan if exists, otherwise use tanggal_printresi
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) >= '$start_date'", null, false);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) <= '$end_date'", null, false);

        // Search filter
        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('dr.sku', $search_value);
            $this->db->or_like('pr.noresi', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->or_like('s.nama_sku', $search_value);
            $this->db->group_end();
        }
    }

    public function action_kurangan_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => INVALID_REQUEST_METHOD));
            exit();
        }

        $id_detail_resi = $this->input->post('id_detail_resi');
        $action_type = $this->input->post('action_type'); // Stock Ready, Minta SJ, Pergantian Barang
        $notes = $this->input->post('notes');
        $new_sku = $this->input->post('new_sku');
        $noresi = $this->input->post('noresi');
        $sku = $this->input->post('sku');

        if (empty($id_detail_resi) || empty($action_type)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Data tidak lengkap.'));
            exit();
        }

        $data_update = [
            'status_kurangan' => 'Sudah Diproses', // Keep the legacy status 'Sudah Diproses' so it drops from the active queue
            'jenis_penyelesaian_kurangan' => $action_type,
            'note_kurangan' => $notes,
            'sku_pengganti' => $new_sku,
            'tanggal_selesai_kurangan' => date('Y-m-d H:i:s'),
            'user_selesai_kurangan' => $this->data['user']['name'] ?? 'System CS'
        ];

        $this->db->where('id_detail_resi', $id_detail_resi);
        $this->db->update('tbldetailprintresi', $data_update);

        if ($this->db->affected_rows() > 0) {
            // Jika action-nya Minta SJ, masukkan ke surat_jalan_tp khusus dari CS 
            // (Sebaiknya Inbound/Accounting yg proses, tapi flow ini bisa dipakai jika user ingin SJ dikirim ke list request)
            if ($action_type == 'Minta SJ') {
                $date_file = date('Ymd_His');
                $nama_file = 'Surat_Jalan_TP_' . $date_file;
                $data_surat = [
                    'nama_file'         => $nama_file,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'id_pegawai'        => $this->data['user']['id_user'] ?? 0,
                    'sku'               => $sku,
                    'id_detail_resi'    => $id_detail_resi,
                ];
                $this->db->insert('surat_jalan_tp', $data_surat);
            }

            header('Content-Type: application/json');
            echo json_encode(array('code' => 201, 'message' => 'Status kurangan diset: ' . $action_type));
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode(array('code' => 400, 'message' => 'Gagal mengupdate.'));
        exit();
    }

    public function submit_kurangan_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => INVALID_REQUEST_METHOD));
            exit();
        }

        $selected_items = $this->input->post('selected_items'); // Array of id_detail_resi
        $noresi = $this->input->post('noresi'); // Optional: single noresi for single submit

        if (empty($selected_items) && empty($noresi)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Tidak ada data yang dipilih'));
            exit();
        }

        // Jika submit per resi (single)
        if (!empty($noresi)) {
            // Update status untuk semua item kurangan di resi tersebut
            $this->db->select('dr.id_detail_resi');
            $this->db->from('tbldetailprintresi dr');
            $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
            $this->db->where('pr.noresi', $noresi);
            $this->db->where('dr.status_kurangan', 'Ya');
            $query = $this->db->get();
            $items = $query->result_array();

            $saved_count = 0;
            foreach ($items as $item) {
                // Update status atau tambahkan flag bahwa sudah di-submit CS
                // Misalnya update status_kurangan menjadi 'Sudah Diproses' atau tambahkan kolom baru
                // Untuk sementara, kita bisa update qty_kurang atau tambahkan timestamp
                $this->db->where('id_detail_resi', $item['id_detail_resi']);
                $this->db->update('tbldetailprintresi', [
                    'status_kurangan' => 'Sudah Diproses' // atau bisa tetap 'Ya' dan tambah kolom baru
                ]);
                if ($this->db->affected_rows() > 0) {
                    $saved_count++;
                }
            }
        } else {
            // Submit multiple items
            $saved_count = 0;
            foreach ($selected_items as $id_detail_resi) {
                $this->db->where('id_detail_resi', $id_detail_resi);
                $this->db->where('status_kurangan', 'Ya');
                $this->db->update('tbldetailprintresi', [
                    'status_kurangan' => 'Sudah Diproses'
                ]);
                if ($this->db->affected_rows() > 0) {
                    $saved_count++;
                }
            }
        }

        header('Content-Type: application/json');
        if ($saved_count > 0) {
            echo json_encode(array('code' => 201, 'message' => 'Data berhasil disubmit (' . $saved_count . ' item)'));
        } else {
            echo json_encode(array('code' => 200, 'message' => 'Tidak ada data yang diupdate'));
        }
        exit();
    }

    public function export_excel_laporan_kurangan_picker()
    {
        ini_set('memory_limit', '-1');

        // Get date range
        $reportrange = $this->input->method() == 'post'
            ? $this->input->post('reportrange')
            : date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Query untuk mendapatkan data kurangan picker (grouped by SKU)
        $this->db->select('
            dr.sku,
            COUNT(DISTINCT pr.noresi) as jumlah_resi,
            SUM(dr.qty_kurang) as total_qty_kurang,
            GROUP_CONCAT(DISTINCT m.nama_marketplace ORDER BY m.nama_marketplace SEPARATOR ", ") as marketplace,
            MIN(COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi)) as tgl_cetak,
            MIN(pr.tanggal_bataskirim) as b_akhir_kirim
        ');
        $this->db->from('tbldetailprintresi dr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');

        // Apply filters
        $this->db->where('dr.status_kurangan', 'Ya');
        $this->db->where('dr.qty_kurang >', 0);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) >= '$start_date'", null, false);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) <= '$end_date'", null, false);

        $this->db->group_by('dr.sku');
        $this->db->order_by('jumlah_resi', 'DESC');

        $data['list_data'] = $this->db->get()->result_array();
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Kurangan_Picker_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/laporan_kurangan_picker', $data);
    }

    public function retur_complain()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        $this->show($data);
    }

    public function get_retur_complain_data()
    {
        $reportrange = $this->input->post('reportrange');

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $params['start'] = intval($this->input->post('start'));
        $params['length'] = intval($this->input->post('length'));
        $params['search'] = $this->input->post('search')['value'] ?? '';
        $params['status_filter'] = $this->input->post('status_filter');
        $params['reportrange'] = $reportrange;

        $col = 0;
        $dir = 'desc';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $params['dir'] = $dir;
        $params['valid_columns'] = array(
            0 => null,
            1 => 'rc.noresi',
            2 => 'rc.customer_name',
            3 => 'rc.marketplace',
            4 => 'rc.created_at',
            5 => 'rc.updated_at',
            6 => 'rc.complain_type',
            7 => 'rc.status'
        );
        $params['order'] = isset($params['valid_columns'][$col]) ? $params['valid_columns'][$col] : 'rc.created_at';

        // Get data complain (semua status termasuk TO_DO)
        $list = $this->retur_fcd->get_complain_report_list($params);
        $total = $this->retur_fcd->get_total_complain_report_list($params);

        $rows = array();
        $no = $params['start'] + 1;
        foreach ($list->result() as $row) {
            $complain_type_label = ucfirst($row->complain_type);
            if ($row->complain_type === 'refund') {
                $complain_type_label = 'Refund Dana';
            } elseif ($row->complain_type === 'replacement') {
                $complain_type_label = 'Pergantian Barang';
            }

            $status_labels = array(
                'TO_DO' => 'To Do',
                'WAITING_CUSTOMER' => 'Waiting Customer',
                'REFUND_DANA' => 'Refund Dana',
                'PERGANTIAN_BARANG' => 'Pergantian Barang',
                'EXPIRED' => 'Expired'
            );
            $status_label = isset($status_labels[$row->status]) ? $status_labels[$row->status] : $row->status;

            // Use marketplace from table if available, otherwise use marketplace_name from join
            $marketplace_display = !empty($row->marketplace) ? $row->marketplace : ($row->marketplace_name ?: '-');

            $rows[] = array(
                $no++ . '.',
                $row->noresi,
                $row->customer_name ?: '-',
                $marketplace_display,
                date('d/m/Y H:i', strtotime($row->created_at)),
                date('d/m/Y H:i', strtotime($row->updated_at)),
                $complain_type_label,
                $status_label,
                $row->notes ?: '-',
                !empty($row->refund_amount) ? number_format($row->refund_amount, 0, ',', '.') : '-',
                !empty($row->replacement_sku) ? $row->replacement_sku . ' (Qty: ' . $row->replacement_qty . ')' : '-'
            );
        }

        $output = array(
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $rows
        );

        header('Content-Type: application/json');
        echo json_encode($output);
        exit();
    }

    public function export_excel_retur_complain()
    {
        ini_set('memory_limit', '-1');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Get all complain data yang sudah diubah statusnya
        $params = array(
            'start' => 0,
            'length' => 0, // Get all - no limit
            'search' => '',
            'status_filter' => 'ALL',
            'reportrange' => $reportrange,
            'dir' => 'desc',
            'order' => 'rc.updated_at'
        );

        $query = $this->retur_fcd->get_complain_report_list($params);
        $data['list_data'] = $query->result_array();
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Retur_Complain_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/retur_complain', $data);
    }

    public function masalah_picker()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        if ($this->input->is_ajax_request() || $this->input->get('ajax')) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'view' => $this->load->view('cs/masalah_picker', $data, TRUE),
                'message' => empty($data['message']) ? null : $data['message'],
            ));
            exit;
        } else {
            $this->show($data);
        }
    }

    public function get_masalah_picker_data()
    {
        $reportrange = $this->input->post('reportrange');

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

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
            1 => 'mp.noresi',
            2 => 'mp.sku',
            3 => 'mp.sku_salah',
            4 => null,
            5 => 'mp.qty',
            6 => 'mp.qty_bermasalah',
            7 => 'tm.type_masalah',
            8 => 'peg_picker.nama_pegawai',
            9 => 'peg.nama_pegawai',
            10 => 'mp.created',
            11 => null,
            12 => null
        ];
        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : 'mp.created';

        // Build base query for counting total
        $this->db->select('mp.id_masalahpicker');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->where('mp.created >=', $start_date);
        $this->db->where('mp.created <=', $end_date);
        $this->db->where('mp.status', 0);

        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $data['search']);
            $this->db->or_like('mp.noresi', $data['search']);
            $this->db->or_like('tm.type_masalah', $data['search']);
            $this->db->or_like('mp.sku_salah', $data['search']);
            $this->db->group_end();
        }

        // Get total count
        $total = $this->db->count_all_results();

        // Now build query for data
        $this->db->select('
            mp.id_masalahpicker,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.created,
            tm.type_masalah,
            COALESCE(s.nama_sku, mp.sku) as nama_barang,
            COALESCE(peg.nama_pegawai, u.name) as nama_packer,
            COALESCE(peg_picker.nama_pegawai, \'Belum Dipick\') as nama_picker
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->where('mp.created >=', $start_date);
        $this->db->where('mp.created <=', $end_date);
        $this->db->where('mp.status', 0);

        if (!empty($data['search'])) {
            $this->db->group_start();
            $this->db->like('mp.sku', $data['search']);
            $this->db->or_like('mp.noresi', $data['search']);
            $this->db->or_like('tm.type_masalah', $data['search']);
            $this->db->or_like('mp.sku_salah', $data['search']);
            $this->db->or_like('peg.nama_pegawai', $data['search']);
            $this->db->or_like('peg_picker.nama_pegawai', $data['search']);
            $this->db->group_end();
        }

        if ($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir']);
        } else {
            $this->db->order_by('mp.created', 'DESC');
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
            $checkbox = '<input type="checkbox" class="row-select" data-id-detail="' . $item['id_masalahpicker'] . '">';
            $data_table[] = [
                $table_number++ . '.',
                $item['noresi'] ?? '-',
                $item['sku'] ?? '-',
                $item['sku_salah'] ?? '-',
                $item['nama_barang'] ?? '-',
                $item['qty'] ?? 0,
                $item['qty_bermasalah'] ?? 0,
                $item['type_masalah'] ?? '-',
                $item['nama_picker'] ?? '-',
                $item['nama_packer'] ?? '-',
                !empty($item['created']) ? date('d/m/Y H:i', strtotime($item['created'])) : '-',
                $checkbox,
                '<button type="button" class="btn btn-sm btn-info btn-detail-masalah" data-id="' . $item['id_masalahpicker'] . '">Detail</button>'
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

    public function get_detail_masalah_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
            exit();
        }

        $id = $this->input->post('id');

        if (empty($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
            exit();
        }

        $this->db->select('
            mp.id_masalahpicker,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.created,
            mp.updated,
            tm.type_masalah,
            COALESCE(s.nama_sku, mp.sku) as nama_barang,
            COALESCE(peg.nama_pegawai, u.name) as nama_packer,
            COALESCE(peg_picker.nama_pegawai, \'Belum Dipick\') as nama_picker
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->where('mp.id_masalahpicker', $id);
        $query = $this->db->get();
        $result = $query->row_array();

        if ($result) {
            // Format tanggal
            if (!empty($result['created'])) {
                $result['created'] = date('d/m/Y H:i:s', strtotime($result['created']));
            }
            if (!empty($result['updated'])) {
                $result['updated'] = date('d/m/Y H:i:s', strtotime($result['updated']));
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit();
    }

    public function get_kurangan_preview_data()
    {
        $reportrange = $this->input->post('reportrange');
        $selected_ids = $this->input->post('selected_ids'); // Array of IDs if checked

        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $this->db->select('
            mp.id_masalahpicker,
            mp.sku,
            mp.qty_bermasalah,
            dr.no_rak
        ');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tblprintresi pr', 'pr.noresi = mp.noresi', 'left');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi AND dr.sku = mp.sku', 'left');

        if (!empty($selected_ids)) {
            $this->db->where_in('mp.id_masalahpicker', $selected_ids);
        } else {
            $this->db->where('mp.created >=', $start_date);
            $this->db->where('mp.created <=', $end_date);
            $this->db->where('mp.status', 0);
            $this->db->limit(15); // Increased limit for non-selected
        }

        $this->db->order_by('mp.created', 'DESC');

        $query = $this->db->get();
        $data = $query->result_array();

        header('Content-Type: application/json');
        echo json_encode(['data' => $data]);
        exit();
    }

    public function get_user_list_kurangan()
    {
        // Get all active users for selection
        $users = $this->db->select('id_user, name')
            ->from('tbluser')
            ->get()->result_array();

        header('Content-Type: application/json');
        echo json_encode($users);
        exit();
    }

    public function save_proses_kurangan()
    {
        // In actual implementation, we would save to tbl_proses_kurangan
        // For now, we return success as per UI request
        $penerima_id = $this->input->post('penerima_id');
        $pengambil_id = $this->input->post('pengambil_id');
        $items = $this->input->post('items');

        if (empty($items)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Tidak ada data untuk diproses']);
            exit();
        }

        $this->db->trans_start();

        foreach ($items as $item) {
            $id = isset($item['id_masalahpicker']) ? $item['id_masalahpicker'] : null;
            if ($id) {
                // Get detail first for automation check
                $problem = $this->db->get_where('tblmasalahpicker', ['id_masalahpicker' => $id])->row();

                $this->db->where('id_masalahpicker', $id);
                $this->db->update('tblmasalahpicker', [
                    'status' => 1,
                    'updated_by' => $this->data['user']['id_user'],
                    'updated' => date('Y-m-d H:i:s')
                ]);

                // AUTOMATION: Only for Reject Display (5), auto-insert to tblpengembalian_qc
                if ($problem && $problem->id_typemasalah == 5) {
                    // Fetch no_rak from detail history
                    $detail = $this->db
                        ->select('no_rak')
                        ->get_where('tbldetailprintresi', [
                            'id_resi' => $problem->id_printresi,
                            'sku' => $problem->sku
                        ])
                        ->row();

                    $kondisi = 'REJECT';
                    $keterangan = 'Reject Display (Auto from CS Process)';

                    // Prevent duplicate entries for the same SKU and record
                    $qc_exists = $this->db->get_where('tblpengembalian_qc', [
                        'sku' => $problem->sku,
                        'no_rak' => $detail->no_rak ?? '-',
                        'tanggal' => date('Y-m-d'),
                        'kondisi' => $kondisi
                    ])->row();

                    if (!$qc_exists) {
                        $this->db->insert('tblpengembalian_qc', [
                            'tanggal' => date('Y-m-d'),
                            'sku' => $problem->sku,
                            'qty' => $problem->qty_bermasalah,
                            'no_rak' => $detail->no_rak ?? '-',
                            'kondisi' => $kondisi,
                            'keterangan_reject' => $keterangan,
                            'submit_by' => $this->data['user']['id_user'],
                            'status' => 'PENDING'
                        ]);

                        // KIRIM NOTIFIKASI KE TIM RETUR (QC Support)
                        $pesan_qc = "Ada barang reject baru (Auto dari CS) untuk SKU: " . $problem->sku;
                        $this->Notification->send($pesan_qc, 'TIM RETUR', 'Update QC: Reject Display');
                    }
                }
            }
        }

        $this->db->trans_complete();

        header('Content-Type: application/json');
        echo json_encode(['success' => $this->db->trans_status()]);
        exit();
    }

    // Daftar acuan (harus sinkron dengan enum di tblcs_complain)
    private $complain_kategori = [
        'Kurang Kirim',
        'Reject',
        'Paket Kosong',
        'Salah Kirim',
        'Tidak Sesuai Deskripsi',
        'Barang Rusak',
        'Paket Hilang',
        'Telat Kirim',
        'Salah Alamat',
        'Barang Tidak Original',
        'Lainnya'
    ];

    private $complain_sumber = [
        'Chat Marketplace',
        'WhatsApp',
        'Telepon',
        'Review/Rating',
        'Email',
        'Retur Fisik',
        'Lainnya'
    ];

    private $complain_status = ['Baru', 'Proses', 'Selesai', 'Ditolak'];

    // Hasil pengecekan CCTV atas komplain. Terpisah dari kategori supaya jenis
    // komplain aslinya tidak hilang. 'QC Salah' memicu poin ke KPI packer.
    private $complain_hasil = [
        'QC Salah',
        'QC Benar',
        'Tidak Terlihat CCTV',
        'CCTV Tidak Bisa Diakses'
    ];

    const TYPE_MASALAH_QC_SALAH = 'QC Salah (Komplain CS)';

    public function complain_management()
    {
        $data['message'] = $this->session->flashdata('message');

        // Get marketplaces
        $data['marketplaces'] = $this->db->get('tblmarketplace')->result_array();

        // Get active employees for QC and Packer
        $data['employees'] = $this->db->get_where('tblpegawai', ['status_aktif' => 'AKTIF'])->result_array();

        // Get list of unique stores
        $data['stores'] = $this->db->select('DISTINCT(toko) as nama_toko')
                                   ->where('toko IS NOT NULL')
                                   ->where('toko !=', '')
                                   ->order_by('toko', 'asc')
                                   ->get('tblprintresi')
                                   ->result_array();

        $data['kategori_list'] = $this->complain_kategori;
        $data['sumber_list']   = $this->complain_sumber;
        $data['status_list']   = $this->complain_status;
        $data['hasil_list']    = $this->complain_hasil;

        // Jumlah komplain retur fisik yang belum ditarik ke modul ini
        $data['jumlah_kandidat'] = $this->_kandidat_retur_builder()->count_all_results();

        $this->show($data, 'cs/complain_management');
    }

    public function get_complain_management_data()
    {
        $reportrange = $this->input->post('reportrange') ?: '';
        
        // DataTable parameters
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        // Sorting
        $order = $this->input->post('order');
        $order_column = null;
        $order_dir = 'DESC';

        $valid_columns = [
            1 => 'tgl_efektif',
            2 => 'c.no_resi',
            3 => 'm.nama_marketplace',
            4 => 'c.toko',
            5 => 'c.kategori_komplain',
            6 => 'c.sumber_komplain',
            7 => 'c.status_penanganan',
            8 => 'c.hasil_investigasi',
            9 => 'c.nominal_total',
            10 => 'c.nama_qc',
            11 => 'c.nama_packer',
            12 => 'c.tgl_banding_pengajuan'
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']);
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Apply filters
        $this->_apply_complain_filters($reportrange, $search_value, [
            'kategori' => $this->input->post('f_kategori'),
            'sumber'   => $this->input->post('f_sumber'),
            'status'   => $this->input->post('f_status'),
            'hasil'    => $this->input->post('f_hasil')
        ]);

        // Get total filtered count
        $total = $this->db->count_all_results('', false);

        // Apply sorting and limit
        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
        } else {
            $this->db->order_by('tgl_efektif', 'DESC');
            $this->db->order_by('c.id_complain', 'DESC');
        }

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

        // Hitung lampiran per komplain dalam satu query
        $lampiran_count = [];
        if (!empty($items)) {
            $ids = array_column($items, 'id_complain');
            $rows = $this->db->select('id_complain, COUNT(*) as jml')
                             ->from('tblcs_complain_lampiran')
                             ->where_in('id_complain', $ids)
                             ->where('is_deleted', 0)
                             ->group_by('id_complain')
                             ->get()
                             ->result_array();
            foreach ($rows as $r) {
                $lampiran_count[$r['id_complain']] = $r['jml'];
            }
        }

        $data_table = [];
        $row_number = $start + 1;

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        foreach ($items as $item) {
            // Badges for complain categories
            $badge_class = 'label-info';
            switch ($item['kategori_komplain']) {
                case 'Kurang Kirim': $badge_class = 'label-warning'; break;
                case 'Reject': $badge_class = 'label-danger'; break;
                case 'Paket Kosong': $badge_class = 'label-default'; break;
                case 'Salah Kirim': $badge_class = 'label-primary'; break;
                case 'Tidak Sesuai Deskripsi': $badge_class = 'label-info'; break;
                case 'Barang Rusak': $badge_class = 'label-danger'; break;
                case 'Paket Hilang': $badge_class = 'label-danger'; break;
                case 'Telat Kirim': $badge_class = 'label-warning'; break;
                case 'Salah Alamat': $badge_class = 'label-primary'; break;
                case 'Barang Tidak Original': $badge_class = 'label-danger'; break;
                case 'Lainnya': $badge_class = 'label-default'; break;
            }
            $complain_badge = '<span class="label ' . $badge_class . '">' . htmlspecialchars($item['kategori_komplain']) . '</span>';

            // Sumber komplain (NULL = data lama yang belum dilengkapi)
            if (empty($item['sumber_komplain'])) {
                $sumber_badge = '<span class="text-muted">-</span>';
            } else {
                $sumber_badge = '<span class="label label-default">' . htmlspecialchars($item['sumber_komplain']) . '</span>';
            }

            // Status penanganan
            if (empty($item['status_penanganan'])) {
                $status_badge = '<span class="text-muted">-</span>';
            } else {
                $status_class = 'label-default';
                switch ($item['status_penanganan']) {
                    case 'Baru': $status_class = 'label-danger'; break;
                    case 'Proses': $status_class = 'label-warning'; break;
                    case 'Selesai': $status_class = 'label-success'; break;
                    case 'Ditolak': $status_class = 'label-default'; break;
                }
                $status_badge = '<span class="label ' . $status_class . '">' . htmlspecialchars($item['status_penanganan']) . '</span>';
            }

            // Hasil investigasi CCTV
            if (empty($item['hasil_investigasi'])) {
                $hasil_badge = '<span class="text-muted">-</span>';
            } else {
                $hasil_class = 'label-default';
                switch ($item['hasil_investigasi']) {
                    case 'QC Salah': $hasil_class = 'label-danger'; break;
                    case 'QC Benar': $hasil_class = 'label-success'; break;
                    case 'Tidak Terlihat CCTV': $hasil_class = 'label-warning'; break;
                    case 'CCTV Tidak Bisa Diakses': $hasil_class = 'label-default'; break;
                }
                $hasil_badge = '<span class="label ' . $hasil_class . '">' . htmlspecialchars($item['hasil_investigasi']) . '</span>';
                if ($item['hasil_investigasi'] === 'QC Salah' && !empty($item['id_masalahpacker'])) {
                    $hasil_badge .= ' <i class="fa fa-star text-danger" title="Poin kesalahan masuk rekapan KPI packer"></i>';
                }
            }

            // Penanda asal & lampiran di kolom resi
            $resi_cell = htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8');
            if (!empty($item['no_pesanan'])) {
                $resi_cell .= '<br><small class="text-muted">' . htmlspecialchars($item['no_pesanan'], ENT_QUOTES, 'UTF-8') . '</small>';
            }
            if (!empty($item['id_resiretur'])) {
                $resi_cell .= ' <i class="fa fa-truck text-muted" title="Ditarik dari retur fisik"></i>';
            }
            $jml_lampiran = $lampiran_count[$item['id_complain']] ?? 0;
            if ($jml_lampiran > 0) {
                $resi_cell .= ' <span class="text-muted" title="' . $jml_lampiran . ' lampiran"><i class="fa fa-paperclip"></i> ' . $jml_lampiran . '</span>';
            }

            // Banding status
            $status_banding = '<span class="label label-default">Belum Diajukan</span>';
            if (!empty($item['tgl_banding_pengajuan'])) {
                if (!empty($item['tgl_claim_dana'])) {
                    $status_banding = '<span class="label label-success">Claimed: Rp ' . number_format($item['nominal_claim_dana'], 0, ',', '.') . '</span>';
                } else if (!empty($item['tgl_banding_tinjauan'])) {
                    $status_banding = '<span class="label label-info">Ditinjau</span>';
                } else {
                    $status_banding = '<span class="label label-warning">Diajukan</span>';
                }
            }

            // Edit and Delete buttons (Delete only for admin/supervisor)
            $id_complain = (int) $item['id_complain'];
            $action_buttons = '<button type="button" class="btn btn-info btn-xs btn-edit-complain" title="Detail / Edit" data-id="' . $id_complain . '"><i class="fa fa-pencil"></i></button> ';
            if ($is_admin) {
                $action_buttons .= '<button type="button" class="btn btn-danger btn-xs btn-delete-complain" title="Hapus (Soft-delete)" data-id="' . $id_complain . '" data-noresi="' . htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-trash"></i></button>';
            }

            $data_table[] = [
                $row_number++ . '.',
                $item['tgl_efektif'] ? date('d/m/Y', strtotime($item['tgl_efektif'])) : '-',
                $resi_cell,
                $item['nama_marketplace'],
                $item['toko'] ?: '-',
                $complain_badge,
                $sumber_badge,
                $status_badge,
                $hasil_badge,
                'Rp ' . number_format($item['nominal_total'], 0, ',', '.'),
                $item['nama_qc'] ?: '-',
                $item['nama_packer'] ?: '-',
                $status_banding,
                $action_buttons
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data_table
            ]));
    }

    private function _apply_complain_filters($reportrange, $search_value = '', $filters = [])
    {
        // tgl_efektif: tanggal komplain kalau diisi, kalau tidak jatuh ke created_at
        // (data lama hasil migrasi belum punya tgl_komplain)
        $this->db->select('c.*, m.nama_marketplace, COALESCE(c.tgl_komplain, DATE(c.created_at)) AS tgl_efektif', false);
        $this->db->from('tblcs_complain c');
        $this->db->join('tblmarketplace m', 'c.id_marketplace = m.id_marketplace', 'left');
        $this->db->where('c.is_deleted', 0);

        if (!empty($reportrange)) {
            $dates = explode(' - ', $reportrange);
            if (count($dates) == 2) {
                $start_date = date('Y-m-d', strtotime(trim($dates[0])));
                $end_date = date('Y-m-d', strtotime(trim($dates[1])));
                // Ditulis mentah supaya COALESCE(...) tidak diperlakukan sebagai nama kolom
                $this->db->where(
                    'COALESCE(c.tgl_komplain, DATE(c.created_at)) BETWEEN '
                        . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date),
                    null,
                    false
                );
            }
        }

        if (!empty($filters['kategori']) && in_array($filters['kategori'], $this->complain_kategori)) {
            $this->db->where('c.kategori_komplain', $filters['kategori']);
        }
        if (!empty($filters['sumber']) && in_array($filters['sumber'], $this->complain_sumber)) {
            $this->db->where('c.sumber_komplain', $filters['sumber']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === '__KOSONG__') {
                $this->db->where('c.status_penanganan IS NULL', null, false);
            } elseif (in_array($filters['status'], $this->complain_status)) {
                $this->db->where('c.status_penanganan', $filters['status']);
            }
        }
        if (!empty($filters['hasil'])) {
            if ($filters['hasil'] === '__KOSONG__') {
                $this->db->where('c.hasil_investigasi IS NULL', null, false);
            } elseif (in_array($filters['hasil'], $this->complain_hasil)) {
                $this->db->where('c.hasil_investigasi', $filters['hasil']);
            }
        }

        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('c.no_resi', $search_value);
            $this->db->or_like('c.toko', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->or_like('c.kategori_komplain', $search_value);
            $this->db->or_like('c.sumber_komplain', $search_value);
            $this->db->or_like('c.nama_qc', $search_value);
            $this->db->or_like('c.nama_packer', $search_value);
            $this->db->group_end();
        }
    }

    public function get_complain_management_detail($id_complain)
    {
        header('Content-Type: application/json');

        $id_complain = (int) $id_complain;
        $complain = $this->db->select('c.*, u.name AS nama_penginput')
                             ->from('tblcs_complain c')
                             ->join('tbluser u', 'u.id_user = c.created_by', 'left')
                             ->where(['c.id_complain' => $id_complain, 'c.is_deleted' => 0])
                             ->get()
                             ->row_array();
        if (!$complain) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            exit;
        }

        $items = $this->db->select('d.*, s.nama_sku')
                          ->from('tblcs_complain_detail d')
                          ->join('tblsku s', 'd.sku = s.id_sku', 'left')
                          ->where(['d.id_complain' => $id_complain, 'd.is_deleted' => 0])
                          ->get()
                          ->result_array();

        $lampiran = $this->db->select('id_lampiran, nama_file, nama_asli, ukuran, uploaded_at')
                             ->from('tblcs_complain_lampiran')
                             ->where(['id_complain' => $id_complain, 'is_deleted' => 0])
                             ->order_by('id_lampiran', 'asc')
                             ->get()
                             ->result_array();

        foreach ($lampiran as &$l) {
            $l['url'] = base_url('assets/uploads/cs_complain/' . $l['nama_file']);
        }
        unset($l);

        // Komplain lain di resi yang sama (multi komplain per resi)
        $komplain_lain = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                  ->from('tblcs_complain')
                                  ->where('no_resi', $complain['no_resi'])
                                  ->where('id_complain !=', $id_complain)
                                  ->where('is_deleted', 0)
                                  ->get()
                                  ->result_array();

        echo json_encode([
            'success' => true,
            'complain' => $complain,
            'items' => $items,
            'lampiran' => $lampiran,
            'komplain_lain' => $komplain_lain
        ]);
        exit;
    }

    public function save_complain_management()
    {
        header('Content-Type: application/json');
        
        $is_edit = $this->input->post('is_edit') == '1';
        $id_complain = (int) $this->input->post('id_complain');
        $no_resi = trim($this->input->post('no_resi'));
        $id_marketplace = $this->input->post('id_marketplace');
        $toko = trim($this->input->post('toko'));
        $kategori_komplain = $this->input->post('kategori_komplain');
        $detail_lainnya = $this->input->post('detail_lainnya');
        $nama_qc = $this->input->post('nama_qc');
        $nama_packer = $this->input->post('nama_packer');
        $nama_picker = $this->input->post('nama_picker');

        // Hasil auto-fill dari nomor resi
        $no_pesanan = trim($this->input->post('no_pesanan')) ?: null;
        $tgl_pesanan = $this->input->post('tgl_pesanan') ?: null;
        $nilai_pesanan = $this->input->post('nilai_pesanan');
        $nilai_pesanan = ($nilai_pesanan === null || $nilai_pesanan === '') ? null : floatval($nilai_pesanan);

        // Field cakupan baru
        $tgl_komplain = $this->input->post('tgl_komplain') ?: null;
        $sumber_komplain = $this->input->post('sumber_komplain') ?: null;
        $status_penanganan = $this->input->post('status_penanganan') ?: null;
        $catatan_penanganan = $this->input->post('catatan_penanganan') ?: null;
        $hasil_investigasi = $this->input->post('hasil_investigasi') ?: null;

        // Banding inputs
        $tgl_banding_pengajuan = $this->input->post('tgl_banding_pengajuan') ?: null;
        $tgl_banding_tinjauan = $this->input->post('tgl_banding_tinjauan') ?: null;
        $tgl_claim_dana = $this->input->post('tgl_claim_dana') ?: null;
        $keterangan_banding = $this->input->post('keterangan_banding') ?: null;
        $nominal_claim_dana = floatval($this->input->post('nominal_claim_dana') ?: 0);

        // Validations
        if (empty($no_resi) || empty($id_marketplace) || empty($toko) || empty($kategori_komplain)) {
            echo json_encode(['code' => 400, 'message' => 'Harap isi semua kolom wajib!']);
            exit;
        }

        if (!in_array($kategori_komplain, $this->complain_kategori)) {
            echo json_encode(['code' => 400, 'message' => 'Kategori komplain tidak dikenali.']);
            exit;
        }
        if ($sumber_komplain !== null && !in_array($sumber_komplain, $this->complain_sumber)) {
            echo json_encode(['code' => 400, 'message' => 'Sumber komplain tidak dikenali.']);
            exit;
        }
        if ($status_penanganan !== null && !in_array($status_penanganan, $this->complain_status)) {
            echo json_encode(['code' => 400, 'message' => 'Status penanganan tidak dikenali.']);
            exit;
        }
        if ($hasil_investigasi !== null && !in_array($hasil_investigasi, $this->complain_hasil)) {
            echo json_encode(['code' => 400, 'message' => 'Hasil investigasi tidak dikenali.']);
            exit;
        }

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        // Security check for Editability (Only webmaster and admin can edit existing data)
        if ($is_edit && !$is_admin) {
            echo json_encode(['code' => 403, 'message' => 'Anda tidak memiliki hak untuk mengedit data ini. Edit hanya boleh dilakukan oleh Admin/Supervisor.']);
            exit;
        }

        if ($is_edit) {
            if ($id_complain <= 0) {
                echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
                exit;
            }
            $exist = $this->db->get_where('tblcs_complain', ['id_complain' => $id_complain, 'is_deleted' => 0])->row();
            if (!$exist) {
                echo json_encode(['code' => 404, 'message' => 'Data komplain tidak ditemukan.']);
                exit;
            }
        }

        // Satu resi boleh punya lebih dari satu komplain, tapi kategori yang sama
        // pada resi yang sama hampir pasti input dobel -> tolak kecuali dipaksa.
        if (!$is_edit && $this->input->post('force_duplikat') != '1') {
            $dobel = $this->db->get_where('tblcs_complain', [
                'no_resi' => $no_resi,
                'kategori_komplain' => $kategori_komplain,
                'is_deleted' => 0
            ])->row();
            if ($dobel) {
                echo json_encode([
                    'code' => 409,
                    'message' => 'Resi ini sudah punya komplain berkategori "' . $kategori_komplain . '". Lanjut simpan sebagai komplain terpisah?'
                ]);
                exit;
            }
        }

        $skus = $this->input->post('sku');
        $qtys = $this->input->post('qty');
        $prices = $this->input->post('price');

        if (empty($skus) || !is_array($skus)) {
            echo json_encode(['code' => 400, 'message' => 'Minimal harus menginputkan 1 item SKU!']);
            exit;
        }

        $this->db->trans_start();

        // Calculate total nominal
        $nominal_total = 0;
        $items_raw = [];
        for ($i = 0; $i < count($skus); $i++) {
            if (empty(trim($skus[$i]))) continue;
            $qty = intval($qtys[$i] ?? 1);
            $price = floatval($prices[$i] ?? 0);
            $nominal_total += ($qty * $price);

            $items_raw[] = [
                'no_resi' => $no_resi,
                'sku' => trim($skus[$i]),
                'qty' => $qty,
                'price' => $price,
                'is_deleted' => 0
            ];
        }

        $complain_data = [
            'tgl_komplain' => $tgl_komplain,
            'id_marketplace' => $id_marketplace,
            'toko' => $toko,
            'kategori_komplain' => $kategori_komplain,
            'detail_lainnya' => $kategori_komplain === 'Lainnya' ? $detail_lainnya : null,
            'sumber_komplain' => $sumber_komplain,
            'status_penanganan' => $status_penanganan,
            'catatan_penanganan' => $catatan_penanganan,
            'hasil_investigasi' => $hasil_investigasi,
            'no_pesanan' => $no_pesanan,
            'tgl_pesanan' => $tgl_pesanan,
            'nama_qc' => $nama_qc ?: null,
            'nama_packer' => $nama_packer ?: null,
            'nama_picker' => $nama_picker ?: null,
            'nominal_total' => $nominal_total,
            'nilai_pesanan' => $nilai_pesanan,
            'tgl_banding_pengajuan' => $tgl_banding_pengajuan,
            'tgl_banding_tinjauan' => $tgl_banding_tinjauan,
            'tgl_claim_dana' => $tgl_claim_dana,
            'keterangan_banding' => $keterangan_banding,
            'nominal_claim_dana' => $nominal_claim_dana,
            'updated_by' => $this->data['user']['id_user']
        ];

        if ($is_edit) {
            // Update master
            $this->db->where('id_complain', $id_complain)->update('tblcs_complain', $complain_data);

            // Soft-delete old items (set is_deleted = 1, NO SQL DELETE statement is run)
            $this->db->where('id_complain', $id_complain)->update('tblcs_complain_detail', ['is_deleted' => 1]);
        } else {
            // Insert master
            $complain_data['no_resi'] = $no_resi;
            $complain_data['created_at'] = date('Y-m-d H:i:s');
            $complain_data['created_by'] = $this->data['user']['id_user'];
            $this->db->insert('tblcs_complain', $complain_data);
            $id_complain = (int) $this->db->insert_id();
        }

        // Insert new items
        if (!empty($items_raw) && $id_complain > 0) {
            $items_to_save = [];
            foreach ($items_raw as $it) {
                $it['id_complain'] = $id_complain;
                $items_to_save[] = $it;
            }
            $this->db->insert_batch('tblcs_complain_detail', $items_to_save);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['code' => 500, 'message' => 'Gagal menyimpan data komplain.']);
            exit;
        }

        // Lampiran diproses setelah transaksi supaya id_complain sudah pasti ada
        $upload_result = $this->_simpan_lampiran_complain($id_complain);

        // Poin KPI packer mengikuti hasil investigasi
        $kpi = $this->_sinkron_poin_kpi_packer($id_complain, $hasil_investigasi, $no_resi);

        $message = 'Data komplain berhasil disimpan!';
        if ($upload_result['tersimpan'] > 0) {
            $message .= ' ' . $upload_result['tersimpan'] . ' lampiran terunggah.';
        }
        if (!empty($upload_result['gagal'])) {
            $message .= ' Lampiran gagal: ' . implode('; ', $upload_result['gagal']);
        }
        if (!empty($kpi['message'])) {
            $message .= ' ' . $kpi['message'];
        }

        echo json_encode(['code' => 201, 'message' => $message, 'id_complain' => $id_complain]);
        exit;
    }

    public function delete_complain_management($id_complain)
    {
        header('Content-Type: application/json');

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        if (!$is_admin) {
            echo json_encode(['code' => 403, 'message' => 'Hanya Admin/Supervisor yang dapat menghapus data komplain!']);
            exit;
        }

        $id_complain = (int) $id_complain;
        if ($id_complain <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
            exit;
        }

        // Komplain yang dihapus tidak boleh menyisakan poin di KPI packer
        $this->_sinkron_poin_kpi_packer($id_complain, null, null);

        $this->db->trans_start();

        // Soft delete master
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain', [
            'is_deleted' => 1,
            'updated_by' => $this->data['user']['id_user']
        ]);

        // Soft delete details
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain_detail', [
            'is_deleted' => 1
        ]);

        // Soft delete lampiran (file fisik dibiarkan agar bisa dipulihkan)
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain_lampiran', [
            'is_deleted' => 1
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['code' => 500, 'message' => 'Gagal menghapus data komplain.']);
        } else {
            echo json_encode(['code' => 200, 'message' => 'Data komplain berhasil dihapus!']);
        }
        exit;
    }

    public function search_resi_for_complain()
    {
        header('Content-Type: application/json');
        $noresi = trim($this->input->post('noresi'));

        if (empty($noresi)) {
            echo json_encode(['success' => false, 'message' => 'Nomor resi kosong']);
            exit;
        }

        // Data induk resi: marketplace, toko, tanggal pesanan, picker, packer.
        // Detail sengaja tidak di-join di sini supaya baris tidak berlipat.
        $query = "SELECT pr.noresi, pr.id_printresi, pr.id_marketplace, m.nama_marketplace, pr.toko,
                         pr.tanggal_pesan, pr.tanggal_printresi,
                         COALESCE(pr.harga, 0) as nilai_pesanan,
                         p.packer_pegawai as packer_id, peg_pack.nama_pegawai as nama_packer,
                         rab.yangambil_pegawai as picker_id, peg_pick.nama_pegawai as nama_picker
                  FROM tblprintresi pr
                  LEFT JOIN tblmarketplace m ON pr.id_marketplace = m.id_marketplace
                  LEFT JOIN tblpacking p ON pr.id_printresi = p.id_resi
                  LEFT JOIN tblpegawai peg_pack ON p.packer_pegawai = peg_pack.kode_pegawai
                  LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                  LEFT JOIN tblpegawai peg_pick ON peg_pick.kode_pegawai = rab.yangambil_pegawai
                  WHERE pr.noresi = ? OR pr.nomorpicklist = ?
                  LIMIT 1";

        $res = $this->db->query($query, [$noresi, $noresi])->row_array();

        if ($res) {
            // Get items
            $items_query = "SELECT d.sku, d.jumlah as qty, d.no_pesanan, s.nama_sku, COALESCE(s.hpp, 0) as hpp
                            FROM tbldetailprintresi d
                            LEFT JOIN tblsku s ON d.sku = s.id_sku
                            WHERE d.id_resi = ?";
            $items = $this->db->query($items_query, [$res['id_printresi']])->result_array();

            // Nomor pesanan diambil dari baris detail pertama yang terisi
            $res['no_pesanan'] = '';
            foreach ($items as $it) {
                if (!empty($it['no_pesanan'])) {
                    $res['no_pesanan'] = $it['no_pesanan'];
                    break;
                }
            }

            // Lengkapi harga dari data Jubelio: tblsku.hpp dan tblprintresi.harga
            // kosong di seluruh database, jadi ini satu-satunya sumber nominal.
            $harga = $this->_harga_dari_jubelio($res['noresi']);
            foreach ($items as &$it) {
                if (floatval($it['hpp']) <= 0 && isset($harga['per_sku'][$it['sku']])) {
                    $it['hpp'] = $harga['per_sku'][$it['sku']];
                }
            }
            unset($it);

            if (empty($res['no_pesanan']) && !empty($harga['no_pesanan'])) {
                $res['no_pesanan'] = $harga['no_pesanan'];
            }

            // toko wajib diisi, sedangkan tblprintresi.toko sering kosong
            if (empty($res['toko']) && !empty($harga['nama_toko'])) {
                $res['toko'] = $harga['nama_toko'];
            }

            // Nilai pesanan: pakai harga resi kalau ada, kalau tidak jumlahkan barang
            if (empty($res['nilai_pesanan']) || $res['nilai_pesanan'] <= 0) {
                $total = 0;
                foreach ($items as $it) {
                    $total += intval($it['qty']) * floatval($it['hpp']);
                }
                $res['nilai_pesanan'] = $total;
                $res['nilai_pesanan_perkiraan'] = true;
            } else {
                $res['nilai_pesanan_perkiraan'] = false;
            }

            $res['sumber_harga'] = $harga['ada'] ? 'jubelio' : 'tidak ada';

            // Komplain yang sudah tercatat untuk resi ini (boleh lebih dari satu)
            $existing = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                 ->from('tblcs_complain')
                                 ->where('no_resi', $res['noresi'])
                                 ->where('is_deleted', 0)
                                 ->order_by('id_complain', 'desc')
                                 ->get()
                                 ->result_array();

            echo json_encode([
                'success' => true,
                'data' => $res,
                'items' => $items,
                'existing' => $existing
            ]);
        } else {
            // Cadangan: resi tidak ada di print resi, coba data retur Jubelio
            $jbl = $this->db->query(
                "SELECT no_resi, no_pesanan, sku, nama_barang, qty, amount, marketplace, nama_toko, tanggal_retur
                 FROM tblreturjubelio WHERE no_resi = ?",
                [$noresi]
            )->result_array();

            if (empty($jbl)) {
                echo json_encode(['success' => false, 'message' => 'Data resi/pesanan tidak ditemukan di database. Silakan isi form secara manual.']);
                exit;
            }

            $baris = $jbl[0];
            $mp = $this->db->get_where('tblmarketplace', ['nama_marketplace' => $baris['marketplace']])->row_array();

            $items = [];
            $total = 0;
            foreach ($jbl as $j) {
                $qty = intval($j['qty']) ?: 1;
                $harga_satuan = round(floatval($j['amount']) / $qty, 2);
                $total += $qty * $harga_satuan;
                $items[] = [
                    'sku' => $j['sku'],
                    'qty' => $qty,
                    'no_pesanan' => $j['no_pesanan'],
                    'nama_sku' => $j['nama_barang'],
                    'hpp' => $harga_satuan
                ];
            }

            $existing = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                 ->from('tblcs_complain')
                                 ->where('no_resi', $noresi)
                                 ->where('is_deleted', 0)
                                 ->order_by('id_complain', 'desc')
                                 ->get()
                                 ->result_array();

            echo json_encode([
                'success' => true,
                'data' => [
                    'noresi' => $baris['no_resi'],
                    'no_pesanan' => $baris['no_pesanan'],
                    'id_marketplace' => $mp ? $mp['id_marketplace'] : null,
                    'nama_marketplace' => $baris['marketplace'],
                    'toko' => $baris['nama_toko'],
                    'tanggal_pesan' => null,
                    'nama_packer' => null,
                    'nama_picker' => null,
                    'nilai_pesanan' => $total,
                    'nilai_pesanan_perkiraan' => true,
                    'sumber_harga' => 'jubelio'
                ],
                'items' => $items,
                'existing' => $existing,
                'catatan' => 'Resi tidak ada di data print resi. Data diambil dari retur Jubelio, picker/packer tidak tersedia.'
            ]);
        }
        exit;
    }

    /**
     * Harga per SKU untuk satu resi, diambil dari data retur Jubelio.
     *
     * tblsku.hpp dan tblprintresi.harga kosong di seluruh database, jadi
     * tblreturjubelio.amount adalah satu-satunya sumber nominal yang hidup.
     * amount = total per baris, sehingga harga satuan = amount / qty.
     *
     * Dicari per resi lewat index no_resi (~8 ms); jangan pernah men-join
     * tabel ini ke tblprintresi secara penuh - charset-nya beda (utf8 vs
     * latin1) sehingga CONVERT mematikan index dan query jadi sangat lambat.
     */
    private function _harga_dari_jubelio($no_resi)
    {
        $hasil = ['ada' => false, 'per_sku' => [], 'no_pesanan' => null, 'nama_toko' => null];

        if (empty($no_resi)) {
            return $hasil;
        }

        $rows = $this->db->query(
            "SELECT sku, qty, amount, no_pesanan, nama_toko FROM tblreturjubelio WHERE no_resi = ?",
            [$no_resi]
        )->result_array();

        foreach ($rows as $r) {
            $qty = intval($r['qty']);
            if ($qty < 1) $qty = 1;
            if (floatval($r['amount']) > 0) {
                $hasil['per_sku'][$r['sku']] = round(floatval($r['amount']) / $qty, 2);
            }
            if ($hasil['no_pesanan'] === null && !empty($r['no_pesanan'])) {
                $hasil['no_pesanan'] = $r['no_pesanan'];
            }
            if ($hasil['nama_toko'] === null && !empty($r['nama_toko'])) {
                $hasil['nama_toko'] = $r['nama_toko'];
            }
        }

        $hasil['ada'] = !empty($hasil['per_sku']);
        return $hasil;
    }

    public function get_sku_suggestions_with_hpp()
    {
        header('Content-Type: application/json');
        $term = trim($this->input->get('term'));
        if (strlen($term) < 1) {
            echo json_encode([]);
            exit();
        }

        $this->db->select('id_sku as id, id_sku as label, nama_sku as value, COALESCE(hpp, 0) as hpp');
        $this->db->like('id_sku', $term);
        $this->db->or_like('nama_sku', $term);
        $this->db->limit(10);
        $query = $this->db->get('tblsku');

        echo json_encode($query->result_array());
        exit();
    }

    // -----------------------------------------------------------------------
    // Poin KPI packer dari hasil investigasi komplain
    // -----------------------------------------------------------------------

    private function _id_type_masalah_qc_salah()
    {
        $row = $this->db->get_where('tbltypemasalahpacker', ['type_masalah' => self::TYPE_MASALAH_QC_SALAH])->row();
        if ($row) {
            return (int) $row->id_typemasalahpacker;
        }

        $this->db->insert('tbltypemasalahpacker', ['type_masalah' => self::TYPE_MASALAH_QC_SALAH]);
        return (int) $this->db->insert_id();
    }

    /**
     * Selaraskan poin kesalahan packer dengan hasil investigasi komplain.
     * 'QC Salah' -> satu baris tblmasalahpacker dibuat (dihitung 50 poin di KPI).
     * Hasil lain / dikosongkan -> poin yang pernah dibuat dicabut lagi.
     *
     * Sengaja idempoten: menyimpan ulang komplain yang sama tidak menggandakan poin.
     */
    private function _sinkron_poin_kpi_packer($id_complain, $hasil_investigasi, $no_resi)
    {
        $complain = $this->db->select('id_masalahpacker')
                             ->get_where('tblcs_complain', ['id_complain' => $id_complain])
                             ->row();
        $id_masalah_lama = $complain ? (int) $complain->id_masalahpacker : 0;

        // Bukan (lagi) QC Salah -> cabut poin kalau sebelumnya ada
        if ($hasil_investigasi !== 'QC Salah') {
            if ($id_masalah_lama > 0) {
                $this->db->where('id_masalahpacker', $id_masalah_lama)->delete('tblmasalahpacker');
                $this->db->where('id_complain', $id_complain)
                         ->update('tblcs_complain', ['id_masalahpacker' => null]);
                return ['message' => 'Poin kesalahan packer dicabut dari rekapan KPI.'];
            }
            return ['message' => ''];
        }

        // Sudah punya poin dan barisnya masih ada -> tidak perlu apa-apa
        if ($id_masalah_lama > 0) {
            $masih_ada = $this->db->get_where('tblmasalahpacker', ['id_masalahpacker' => $id_masalah_lama])->row();
            if ($masih_ada) {
                return ['message' => ''];
            }
        }

        // Poin hanya bisa dilekatkan kalau resinya dikenal dan sudah pernah dipacking,
        // karena rekapan KPI menghitung lewat tblpacking.packer_pegawai.
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $no_resi])->row();
        if (!$receipt) {
            return ['message' => 'Poin KPI tidak dibuat: resi tidak ada di data print resi.'];
        }

        $packing = $this->db->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])->row();
        if (!$packing) {
            return ['message' => 'Poin KPI tidak dibuat: resi ini belum pernah dipacking.'];
        }

        // Ambil barang pertama dari detail komplain sebagai rujukan SKU
        $item = $this->db->select('sku, qty')
                         ->get_where('tblcs_complain_detail', ['id_complain' => $id_complain, 'is_deleted' => 0])
                         ->row();

        $this->db->insert('tblmasalahpacker', [
            'id_printresi'         => $receipt->id_printresi,
            'noresi'               => $no_resi,
            'sku'                  => $item ? $item->sku : null,
            'qty'                  => $item ? (int) $item->qty : 1,
            'id_typemasalahpacker' => $this->_id_type_masalah_qc_salah(),
            'qty_bermasalah'       => $item ? (int) $item->qty : 1,
            'keterangan'           => 'Otomatis dari Manajemen Komplain CS #' . $id_complain . ' (hasil investigasi: QC Salah)',
            'status'               => 1,
            'created_by'           => $this->data['user']['id_user'],
            'created'              => date('Y-m-d H:i:s')
        ]);

        $id_masalah_baru = (int) $this->db->insert_id();
        $this->db->where('id_complain', $id_complain)
                 ->update('tblcs_complain', ['id_masalahpacker' => $id_masalah_baru]);

        return ['message' => 'Poin kesalahan packer ditambahkan ke rekapan KPI.'];
    }

    // -----------------------------------------------------------------------
    // Feeder: komplain dari retur fisik (tblresiretur.is_komplain = 1)
    // Hanya membaca tabel retur, tidak pernah menulis ke sana.
    // -----------------------------------------------------------------------

    /**
     * Query builder kandidat retur fisik yang belum ditarik ke modul komplain.
     * noresi di tblresiretur/tblprintresi bercharset latin1 sedangkan
     * tblcs_complain.no_resi utf8mb4, jadi perbandingan wajib pakai CONVERT.
     */
    private function _kandidat_retur_builder()
    {
        $this->db->from('tblresiretur rr');
        $this->db->where('rr.is_komplain', 1);
        $this->db->where('NOT EXISTS (SELECT 1 FROM tblcs_complain cc WHERE cc.id_resiretur = rr.id_resiretur AND cc.is_deleted = 0)', null, false);

        return $this->db;
    }

    public function get_kandidat_retur_data()
    {
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        $this->_kandidat_retur_builder();
        $this->db->select('rr.id_resiretur, rr.noresi, rr.tanggal_resiretur, rr.status_retur, m.nama_marketplace, pr.toko');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = rr.id_marketplace', 'left');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = rr.id_resi', 'left');
        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('rr.noresi', $search_value);
            $this->db->or_like('pr.toko', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by('rr.tanggal_resiretur', 'DESC');
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        $items = $this->db->get()->result_array();

        $rows = [];
        $row_number = $start + 1;
        foreach ($items as $item) {
            $rows[] = [
                $row_number++ . '.',
                $item['tanggal_resiretur'] ? date('d/m/Y H:i', strtotime($item['tanggal_resiretur'])) : '-',
                htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8'),
                $item['nama_marketplace'] ?: '-',
                $item['toko'] ?: '-',
                htmlspecialchars($item['status_retur'] ?: '-', ENT_QUOTES, 'UTF-8'),
                '<button type="button" class="btn btn-primary btn-xs btn-tarik-kandidat" data-id="' . (int) $item['id_resiretur'] . '" data-noresi="' . htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-download"></i> Tarik</button>'
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $rows
            ]));
    }

    public function tarik_kandidat_retur()
    {
        header('Content-Type: application/json');

        $ids = $this->input->post('id_resiretur');
        if (!is_array($ids)) {
            $ids = $ids !== null ? [$ids] : [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            echo json_encode(['code' => 400, 'message' => 'Tidak ada kandidat yang dipilih.']);
            exit;
        }

        $rows = $this->db->select('rr.id_resiretur, rr.noresi, rr.tanggal_resiretur, rr.id_marketplace, rr.id_resi,
                                   pr.toko, pr.id_marketplace AS mp_printresi, pr.tanggal_pesan,
                                   peg_pack.nama_pegawai AS nama_packer, peg_pick.nama_pegawai AS nama_picker', false)
                         ->from('tblresiretur rr')
                         ->join('tblprintresi pr', 'pr.id_printresi = rr.id_resi', 'left')
                         ->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left')
                         ->join('tblpegawai peg_pack', 'peg_pack.kode_pegawai = pk.packer_pegawai', 'left')
                         ->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left')
                         ->join('tblpegawai peg_pick', 'peg_pick.kode_pegawai = rab.yangambil_pegawai', 'left')
                         ->where_in('rr.id_resiretur', $ids)
                         ->where('rr.is_komplain', 1)
                         ->group_by('rr.id_resiretur')
                         ->get()
                         ->result_array();

        if (empty($rows)) {
            echo json_encode(['code' => 404, 'message' => 'Kandidat tidak ditemukan atau sudah tidak bertanda komplain.']);
            exit;
        }

        $user_id = $this->data['user']['id_user'];
        $now = date('Y-m-d H:i:s');
        $ditarik = 0;
        $dilewati = 0;

        foreach ($rows as $row) {
            // Lewati kalau sudah pernah ditarik
            $sudah = $this->db->get_where('tblcs_complain', [
                'id_resiretur' => $row['id_resiretur'],
                'is_deleted' => 0
            ])->row();
            if ($sudah) {
                $dilewati++;
                continue;
            }

            $id_marketplace = $row['id_marketplace'] ?: $row['mp_printresi'];
            if (empty($id_marketplace)) {
                $dilewati++;
                continue;
            }

            $this->db->trans_start();

            $this->db->insert('tblcs_complain', [
                'no_resi' => $row['noresi'],
                'tgl_komplain' => $row['tanggal_resiretur'] ? date('Y-m-d', strtotime($row['tanggal_resiretur'])) : date('Y-m-d'),
                'id_marketplace' => $id_marketplace,
                'toko' => $row['toko'] ?: '-',
                'kategori_komplain' => 'Lainnya',
                'detail_lainnya' => 'Ditarik otomatis dari retur fisik (scan retur komplain). Mohon lengkapi kategori & nominal.',
                'sumber_komplain' => 'Retur Fisik',
                'status_penanganan' => 'Baru',
                'id_resiretur' => $row['id_resiretur'],
                'tgl_pesanan' => $row['tanggal_pesan'] ?: null,
                'nama_packer' => $row['nama_packer'] ?: null,
                'nama_picker' => $row['nama_picker'] ?: null,
                'nominal_total' => 0,
                'created_at' => $now,
                'created_by' => $user_id
            ]);

            $id_complain = (int) $this->db->insert_id();

            // Prefill item dari detail print resi
            if ($id_complain > 0 && !empty($row['id_resi'])) {
                $items = $this->db->query(
                    "SELECT d.sku, d.jumlah AS qty, d.no_pesanan, COALESCE(s.hpp, 0) AS hpp
                     FROM tbldetailprintresi d
                     LEFT JOIN tblsku s ON d.sku = s.id_sku
                     WHERE d.id_resi = ?",
                    [$row['id_resi']]
                )->result_array();

                $batch = [];
                $nominal = 0;
                $no_pesanan = null;
                foreach ($items as $it) {
                    if ($no_pesanan === null && !empty($it['no_pesanan'])) {
                        $no_pesanan = $it['no_pesanan'];
                    }
                    $qty = intval($it['qty'] ?: 1);
                    $price = floatval($it['hpp']);
                    $nominal += $qty * $price;
                    $batch[] = [
                        'id_complain' => $id_complain,
                        'no_resi' => $row['noresi'],
                        'sku' => $it['sku'],
                        'qty' => $qty,
                        'price' => $price,
                        'is_deleted' => 0
                    ];
                }

                if (!empty($batch)) {
                    $this->db->insert_batch('tblcs_complain_detail', $batch);
                    $this->db->where('id_complain', $id_complain)
                             ->update('tblcs_complain', [
                                 'nominal_total' => $nominal,
                                 'nilai_pesanan' => $nominal,
                                 'no_pesanan' => $no_pesanan
                             ]);
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $dilewati++;
            } else {
                $ditarik++;
            }
        }

        $pesan = $ditarik . ' komplain retur fisik berhasil ditarik.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' dilewati (sudah ditarik / data tidak lengkap).';
        }

        echo json_encode([
            'code' => $ditarik > 0 ? 201 : 400,
            'message' => $pesan,
            'ditarik' => $ditarik,
            'dilewati' => $dilewati
        ]);
        exit;
    }

    // -----------------------------------------------------------------------
    // Lampiran bukti komplain
    // -----------------------------------------------------------------------

    private function _lampiran_upload_path()
    {
        return './assets/uploads/cs_complain/';
    }

    /**
     * Simpan berkas dari input file "lampiran[]" untuk satu komplain.
     * Mengembalikan ['tersimpan' => int, 'gagal' => string[]].
     */
    private function _simpan_lampiran_complain($id_complain)
    {
        $hasil = ['tersimpan' => 0, 'gagal' => []];

        if ($id_complain <= 0 || empty($_FILES['lampiran']['name'][0])) {
            return $hasil;
        }

        $path = $this->_lampiran_upload_path();
        if (!is_dir($path)) {
            mkdir($path, 0777, TRUE);
        }

        $config = [
            'upload_path'   => $path,
            'allowed_types' => 'jpg|jpeg|png|gif|pdf',
            'max_size'      => 5120, // KB
            'encrypt_name'  => TRUE
        ];
        $this->load->library('upload', $config);

        $files = $_FILES['lampiran'];
        $batch = [];
        $now = date('Y-m-d H:i:s');

        foreach ($files['name'] as $key => $nama) {
            if (empty($nama)) continue;

            $_FILES['lampiran_single'] = [
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key],
            ];

            $this->upload->initialize($config);

            if ($this->upload->do_upload('lampiran_single')) {
                $up = $this->upload->data();
                $batch[] = [
                    'id_complain' => $id_complain,
                    'nama_file'   => $up['file_name'],
                    'nama_asli'   => $nama,
                    'mime_type'   => $up['file_type'],
                    'ukuran'      => (int) $files['size'][$key],
                    'uploaded_by' => $this->data['user']['id_user'],
                    'uploaded_at' => $now,
                    'is_deleted'  => 0
                ];
                $hasil['tersimpan']++;
            } else {
                $hasil['gagal'][] = $nama . ' (' . strip_tags($this->upload->display_errors('', '')) . ')';
            }
        }

        if (!empty($batch)) {
            $this->db->insert_batch('tblcs_complain_lampiran', $batch);
        }

        return $hasil;
    }

    public function upload_lampiran_complain()
    {
        header('Content-Type: application/json');

        $id_complain = (int) $this->input->post('id_complain');
        if ($id_complain <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
            exit;
        }

        $ada = $this->db->get_where('tblcs_complain', ['id_complain' => $id_complain, 'is_deleted' => 0])->row();
        if (!$ada) {
            echo json_encode(['code' => 404, 'message' => 'Data komplain tidak ditemukan.']);
            exit;
        }

        $hasil = $this->_simpan_lampiran_complain($id_complain);

        if ($hasil['tersimpan'] === 0) {
            echo json_encode([
                'code' => 400,
                'message' => 'Tidak ada lampiran tersimpan.' . (!empty($hasil['gagal']) ? ' ' . implode('; ', $hasil['gagal']) : '')
            ]);
            exit;
        }

        $pesan = $hasil['tersimpan'] . ' lampiran berhasil diunggah.';
        if (!empty($hasil['gagal'])) {
            $pesan .= ' Gagal: ' . implode('; ', $hasil['gagal']);
        }

        echo json_encode(['code' => 201, 'message' => $pesan]);
        exit;
    }

    public function delete_lampiran_complain($id_lampiran)
    {
        header('Content-Type: application/json');

        $user_role = $this->data['user']['hakakses'];
        if (!in_array($user_role, [1, 2])) {
            echo json_encode(['code' => 403, 'message' => 'Hanya Admin/Supervisor yang dapat menghapus lampiran!']);
            exit;
        }

        $id_lampiran = (int) $id_lampiran;
        if ($id_lampiran <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID lampiran tidak valid.']);
            exit;
        }

        $this->db->where('id_lampiran', $id_lampiran)->update('tblcs_complain_lampiran', ['is_deleted' => 1]);

        echo json_encode(['code' => 200, 'message' => 'Lampiran dihapus.']);
        exit;
    }

    // -----------------------------------------------------------------------
    // Video packing
    // -----------------------------------------------------------------------

    /** Halaman cari video packing berdasarkan nomor resi. */
    public function video_packing()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    /**
     * Daftar rekaman untuk satu resi (JSON).
     *
     * Satu resi bisa punya lebih dari satu rekaman kalau sempat di-scan ulang,
     * jadi yang dikembalikan selalu berupa daftar -- terbaru di urutan pertama.
     */
    public function get_video_packing()
    {
        header('Content-Type: application/json');

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $noresi = trim((string) $this->input->get('noresi'));
        }

        if ($noresi === '') {
            echo json_encode(['code' => 400, 'message' => 'Nomor resi tidak boleh kosong.', 'data' => []]);
            exit;
        }

        $rows = $this->video_packing_fcd->get_by_resi($noresi);

        $list = [];
        foreach ($rows as $row) {
            $path = $this->video_packing_fcd->path_berkas($row);

            // Baris tanpa berkas (mis. berkas dihapus manual saat bersih-bersih)
            // sengaja tidak ditampilkan supaya player tidak gagal diam-diam.
            if (!is_file($path)) {
                continue;
            }

            $list[] = [
                'id'            => (int) $row->id_videopacking,
                'noresi'        => $row->noresi,
                'bagian'        => isset($row->bagian) ? (int) $row->bagian : 1,
                'nama_file'     => $row->nama_file,
                'url'           => $this->video_packing_fcd->url_video($row),
                'nama_packer'   => $row->nama_packer ? $row->nama_packer : '-',
                'nama_komputer' => $row->nama_komputer ? $row->nama_komputer : '-',
                'mulai_at'      => $row->mulai_at,
                'selesai_at'    => $row->selesai_at,
                'durasi_detik'  => (int) $row->durasi_detik,
                'ukuran_byte'   => (int) $row->ukuran_byte,
                'status'        => $row->status,
            ];
        }

        if (empty($list)) {
            echo json_encode([
                'code'    => 404,
                'message' => 'Belum ada video packing untuk resi ' . $noresi . '.',
                'data'    => []
            ]);
            exit;
        }

        echo json_encode([
            'code'    => 200,
            'message' => count($list) . ' video ditemukan.',
            'data'    => $list
        ]);
        exit;
    }

    /**
     * Alirkan isi satu berkas rekaman ke browser.
     *
     * Folder rekaman ada di luar document root (lihat Video_packing_fcd), jadi
     * Apache tidak bisa menyajikannya langsung; endpoint ini yang membacanya
     * dan mengirimnya sebagai video/webm. Login sudah diperiksa MY_Controller,
     * jadi video hanya bisa dibuka pengguna yang masuk.
     *
     * Permintaan Range dilayani supaya player bisa melompat ke tengah video
     * tanpa mengunduh seluruh berkas -- tanpa itu, seek di tag <video> harus
     * menunggu berkas utuh, dan rekaman satu jam praktis tidak bisa ditonton.
     *
     * ?unduh=1 memaksa dialog simpan-berkas alih-alih diputar di browser.
     */
    public function putar_video_packing($id = 0)
    {
        $row  = $this->video_packing_fcd->get_by_id($id);
        $path = $row ? $this->video_packing_fcd->path_berkas($row) : '';

        if (!$row || $row->status === 'DIBATALKAN' || !is_file($path)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Video tidak ditemukan.';
            exit;
        }

        $ukuran = filesize($path);
        $mulai  = 0;
        $akhir  = $ukuran - 1;
        $mime   = !empty($row->mime_type) ? $row->mime_type : 'video/webm';

        // Output buffer CI dan apa pun yang tercetak sebelum ini dibuang: satu
        // byte nyasar di depan data biner membuat WebM-nya tidak bisa diputar.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Batas waktu dan memori: berkasnya dibaca per 8 KB, bukan sekaligus,
        // jadi memori tidak masalah, tapi mengalirkan rekaman satu jam bisa
        // lebih lama dari max_execution_time bawaan.
        set_time_limit(0);

        $range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : '';

        if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            if ($m[1] !== '') {
                $mulai = (int) $m[1];
                if ($m[2] !== '') {
                    $akhir = min((int) $m[2], $ukuran - 1);
                }
            } elseif ($m[2] !== '') {
                // "bytes=-500": 500 byte terakhir.
                $mulai = max(0, $ukuran - (int) $m[2]);
            }

            if ($mulai > $akhir || $mulai >= $ukuran) {
                header('HTTP/1.1 416 Range Not Satisfiable');
                header('Content-Range: bytes */' . $ukuran);
                exit;
            }

            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $mulai . '-' . $akhir . '/' . $ukuran);
        } else {
            header('HTTP/1.1 200 OK');
        }

        $nama_unduh = preg_replace('/[^A-Za-z0-9._-]/', '_', $row->nama_file);
        $disposisi  = $this->input->get('unduh') ? 'attachment' : 'inline';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . ($akhir - $mulai + 1));
        header('Accept-Ranges: bytes');
        header('Content-Disposition: ' . $disposisi . '; filename="' . $nama_unduh . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        $fp = fopen($path, 'rb');
        if ($fp === FALSE) {
            exit;
        }

        fseek($fp, $mulai);
        $sisa = $akhir - $mulai + 1;

        while ($sisa > 0 && !feof($fp) && !connection_aborted()) {
            $baca = fread($fp, min(8192, $sisa));
            if ($baca === FALSE) {
                break;
            }
            echo $baca;
            flush();
            $sisa -= strlen($baca);
        }

        fclose($fp);
        exit;
    }
}
