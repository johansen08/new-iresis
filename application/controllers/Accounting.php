<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accounting extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Buka_retur');
        $this->load->model('Surat_jalan_fcd');
        $this->load->model('Receipt_fcd');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
        $this->load->library('pusher_lib');
    }

    // RETURAN BUKA

    public function returan_buka()
    {
        $data['list_returan_buka'] = $this->Buka_retur->get_returan_buka()->result_array();
        $data['title'] = 'Returan Buka';
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function proses_acc_bulk()
    {
        $ids = $this->input->post('ids');

        // Guard against the id=0 mass-update class (see Retur_fcd::guarded_pk_update):
        // only ACC explicitly-selected, positive ids so a stray 0 can't sweep the table.
        $ids = array_values(array_filter(array_map('intval', (array) $ids), function ($id) {
            return $id > 0;
        }));

        if (!empty($ids)) {
            $data_update = [
                'status_acc' => 1,
                'acc_by' => $this->data['user']['id_user'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where_in('id_bukaretur', $ids);
            $this->db->update('tblbukaretur', $data_update);
        }

        redirect('accounting/returan-buka');
    }

    // ============================================================
    // ============================================================

    // SURAT JALAN

    public function form_surat_jalan()
    {
        $data['title']    = 'Surat Jalan';
        $data['message']  = $this->session->flashdata('message');
        // Dari halaman Daftar Surat Jalan: buka dokumen tertentu otomatis (?doc=ID)
        $data['open_doc'] = $this->input->get('doc') ? intval($this->input->get('doc')) : null;
        $this->show($data);
    }

    public function save_surat_jalan()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();

		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		try {
			$surat_jalan = array(
				'nama_file' => $this->input->post('nama_file'),
				'link_dokumen' => $this->input->post('link_dokumen'),
				'jenis' => $this->input->post('jenis'),
			);

			$save = $this->Surat_jalan_fcd->save_surat_jalan($surat_jalan, $this->data['user']['id_user']);
			if (isset($save['error'])) {
				ob_end_clean();
				$this->make_ajax_response($save['code'], $save['message']);
			}

            $pesan_notif = 'Surat Jalan Baru (' . $surat_jalan['jenis'] . '): ' . $surat_jalan['nama_file'] . ' telah diupload oleh Tim Accounting.';
            
            $db_notif_data = array(
                    'message'    => $pesan_notif,
                    'category'   => 'TIM INBOUND',
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s')
                );
            $this->db->insert('notifications', $db_notif_data);

            $notif_data['title']   = 'Dokumen Surat Jalan Baru!';
            $notif_data['message'] = $pesan_notif;
            // ID TIM INBOUND: 8
            $target_channels = array('notif-role-8', 'notif-role-1');
            $this->pusher_lib->trigger($target_channels, 'notif-event', $notif_data);

            ob_end_clean();
			$this->make_ajax_response(201, 'Surat jalan berhasil disimpan');
		} catch (Exception $e) {
			ob_end_clean();
			$this->make_ajax_response(500, 'Terjadi kesalahan: ' . $e->getMessage());
		}
	}

    public function riwayat_surat_jalan()
    {
        $input_range = $this->input->post('reportrange');
        $start_date = date('Y-m-d 00:00:00');
        $end_date   = date('Y-m-d 23:59:59');

        if (empty($input_range)) {
            $reportrange = $start_date . ' - ' . $end_date;
        } else {
            $reportrange = $input_range;
            $dates = explode(' - ', $reportrange);
            
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date   = $dates[1];
            }
        }

        $data['list_riwayat_surat_jalan'] = $this->Surat_jalan_fcd->get_riwayat($start_date, $end_date)->result_array();
        $data['reportrange'] = $reportrange;
        $data['title'] = 'Surat Jalan Database';

        $this->show($data);
    }

    public function delete_surat_jalan($id)
    {
        while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();

		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        try {
            $delete = $this->Surat_jalan_fcd->delete($id);
            ob_end_clean();
			$this->make_ajax_response(201, 'Surat jalan berhasil dihapus');
        } catch (Exception $e) {
			ob_end_clean();
			$this->make_ajax_response(500, 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function update_surat_jalan()
    {
        $id = $this->input->post('id');
        $nama_file = $this->input->post('nama_file');
        $link_dokumen = $this->input->post('link_dokumen');
        $jenis = $this->input->post('jenis');

        if (empty($id) || empty($nama_file) || empty($link_dokumen) || empty($jenis)) {
            $this->session->set_flashdata('error', 'Data tidak boleh kosong.');
            $this->show_page('riwayat-surat-jalan');
        }

        $data_update = [
            'nama_file' => $nama_file,
            'link_dokumen' => $link_dokumen,
            'jenis' => $jenis,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        $update = $this->db->update('surat_jalan', $data_update);

        if ($update) {
            $this->session->set_flashdata('success', 'Surat jalan berhasil diperbaiki.');
            $this->show_page('riwayat-surat-jalan');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate data.');
            $this->show_page('riwayat-surat-jalan');
        }
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
        $reportrange = $this->input->post('reportrange') ?: date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

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

        $this->_apply_kurangan_picker_filters($start_date, $end_date, $search_value);

        $total = $this->db->count_all_results('', false);

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

        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
        } else {
            $this->db->order_by('COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi)', 'DESC');
        }

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

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
                (function($date) {
                    if (empty($date)) return '-';
                    $is_today = (date('Y-m-d', strtotime($date)) == date('Y-m-d'));
                    $formatted = date('d/m/Y', strtotime($date));
                    return $is_today ? '<span style="color: red; font-weight: bold;">' . $formatted . '</span>' : $formatted;
                })($item['tanggal_bataskirim']),
                $item['qty_kurang'] ?? 0,
                '<input type="checkbox" class="row-select" data-id-detail="' . $item['id_detail_resi'] . '" data-noresi="' . htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8') . '" />',
                '<div style="min-width: 130px;">
                    <button type="button" class="btn btn-success btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Stock Ready" data-action="Stock Ready" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-check"></i></button>
                    <button type="button" class="btn btn-warning btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Minta SJ" data-action="Minta SJ" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-file-text-o"></i></button>
                    <button type="button" class="btn btn-info btn-xs btn-action-kurangan" style="margin-bottom:2px; height: 35px; width:45px;" title="Pergantian Barang" data-action="Pergantian Barang" data-id="'.$item['id_detail_resi'].'" data-noresi="'.htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8').'" data-qty="'.$item['qty_kurang'].'" data-sku="'.htmlspecialchars($item['sku'], ENT_QUOTES, 'UTF-8').'"><i class="fa fa-exchange"></i></button>
                </div>'
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

    private function _apply_kurangan_picker_filters($start_date, $end_date, $search_value = '')
    {
        $this->db->from('tbldetailprintresi dr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');

        $this->db->where('dr.status_kurangan', 'TERIMA');
        $this->db->where('dr.qty_kurang >', 0);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) >= '$start_date'", null, false);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) <= '$end_date'", null, false);

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
            'status_kurangan' => 'PROSES', // Keep the legacy status PROSES so it behaves identically
            'jenis_penyelesaian_kurangan' => $action_type,
            'note_kurangan' => $notes,
            'sku_pengganti' => $new_sku,
            'tanggal_selesai_kurangan' => date('Y-m-d H:i:s'),
            'user_selesai_kurangan' => $this->data['user']['name'] ?? 'System'
        ];

        $this->db->where('id_detail_resi', $id_detail_resi);
        $this->db->update('tbldetailprintresi', $data_update);

        if ($this->db->affected_rows() > 0) {
            // Jika action-nya Minta SJ, masukkan ke surat_jalan_tp
            if ($action_type == 'Minta SJ') {
                $date_file = date('Ymd_His');
                $nama_file = 'Surat_Jalan_TP_' . $date_file;
                $data_surat = [
                    'nama_file'         => $nama_file,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'id_pegawai'        => $this->data['user']['id_user'],
                    'sku'               => $sku,
                    'id_detail_resi'    => $id_detail_resi,
                ];
                $this->db->insert('surat_jalan_tp', $data_surat);
            }

            header('Content-Type: application/json');
            echo json_encode(array('code' => 201, 'message' => 'Status kurangan berhasil diupdate menjadi: ' . $action_type));
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode(array('code' => 400, 'message' => 'Gagal mengupdate atau data sudah sama.'));
        exit();
    }

    public function submit_kurangan_picker()
    {
        if ($this->input->method() == 'get') {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => INVALID_REQUEST_METHOD));
            exit();
        }

        $selected_items = $this->input->post('selected_items');
        $noresi = $this->input->post('noresi');

        if (empty($selected_items) && empty($noresi)) {
            header('Content-Type: application/json');
            echo json_encode(array('code' => 400, 'message' => 'Tidak ada data yang dipilih'));
            exit();
        }

        $ids_to_process = [];
        $skus_collected = [];

        if (!empty($noresi)) {
            $this->db->select('dr.id_detail_resi, dr.sku');
            $this->db->from('tbldetailprintresi dr');
            $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
            $this->db->where('pr.noresi', $noresi);
            
            $query = $this->db->get();
            $results = $query->result_array();

            foreach ($results as $row) {
                $ids_to_process[] = $row['id_detail_resi'];
                if (!empty($row['sku'])) {
                    $skus_collected[] = $row['sku'];
                }
            }
        } 
        elseif (!empty($selected_items)) {
            $ids_to_process = $selected_items;

            $this->db->select('sku');
            $this->db->from('tbldetailprintresi');
            $this->db->where_in('id_detail_resi', $ids_to_process);
            $query = $this->db->get();

            foreach ($query->result() as $row) {
                if (!empty($row->sku)) {
                    $skus_collected[] = $row->sku;
                }
            }
        }

        $updated_count = 0;

        if (!empty($ids_to_process)) {
            $this->db->where_in('id_detail_resi', $ids_to_process);
            $this->db->update('tbldetailprintresi', [
                'status_kurangan' => 'PROSES'
            ]);

            $updated_count = $this->db->affected_rows();

            if ($updated_count > 0) {
                $sku_unique = array_unique($skus_collected);
                $sku_string = implode(', ', $sku_unique);
                $id_detail_resi_string = implode(',', $ids_to_process); 
                $date_file = date('Ymd_His');
                $nama_file = 'Surat_Jalan_TP_' . $date_file;

                $data_surat = [
                    'nama_file'         => $nama_file,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'id_pegawai'        => $this->data['user']['id_user'],
                    'sku'               => $sku_string,
                    'id_detail_resi'    => $id_detail_resi_string,
                ];

                $this->db->insert('surat_jalan_tp', $data_surat);
            }
        }

        header('Content-Type: application/json');
        if ($updated_count > 0) {
            echo json_encode(array('code' => 201, 'message' => 'Data berhasil disubmit (' . $updated_count . ' item). SKU: ' . $sku_string));
        } else {
            echo json_encode(array('code' => 200, 'message' => 'Tidak ada data yang diupdate'));
        }
        exit();
    }

    public function export_excel_laporan_kurangan_picker()
    {
        ini_set('memory_limit', '-1');

        $reportrange = $this->input->method() == 'post'
            ? $this->input->post('reportrange')
            : date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

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

    public function riwayat_surat_jalan_tp()
    {
        $input_range = $this->input->post('reportrange');
        $start_date = date('Y-m-d 00:00:00');
        $end_date   = date('Y-m-d 23:59:59');

        if (empty($input_range)) {
            $reportrange = $start_date . ' - ' . $end_date;
        } else {
            $reportrange = $input_range;
            $dates = explode(' - ', $reportrange);
            
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date   = $dates[1];
            }
        }

        $data['list_riwayat_surat_jalan_tp'] = $this->Surat_jalan_fcd->get_riwayat_tp($start_date, $end_date)->result_array();
        $data['reportrange'] = $reportrange;
        $data['title'] = 'Riwayat Surat Jalan (TP)';

        $this->show($data); 
    }

    public function update_surat_jalan_tp()
    {
        $id = $this->input->post('id');
        $nama_file = $this->input->post('nama_file');
        $link_dokumen = $this->input->post('link_dokumen');
        $jenis = $this->input->post('jenis');

        if (empty($id) || empty($nama_file) || empty($link_dokumen) || empty($jenis)) {
            $this->session->set_flashdata('error', 'Data tidak boleh kosong.');
            $this->show_page('riwayat-surat-jalan-tp'); 
            return; // DITAMBAHKAN: Agar eksekusi kode berhenti di sini jika data kosong
        }

        $data_update = [
            'nama_file' => $nama_file,
            'link_dokumen' => $link_dokumen,
            'jenis' => $jenis,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        $update = $this->db->update('surat_jalan_tp', $data_update);

        if ($update) {
            // Jika update berhasil dan link_dokumen tidak kosong
            if (!empty($link_dokumen)) {
                $data_surat = $this->db->select('id_detail_resi')
                                       ->get_where('surat_jalan_tp', ['id' => $id])
                                       ->row_array();

                $ids_string = $data_surat['id_detail_resi'] ?? '';

                if (!empty($ids_string)) {
                    $ids_array = explode(',', $ids_string);
                    $this->db->where_in('id_detail_resi', $ids_array);
                    $update_kurangan = $this->db->update('tbldetailprintresi', [
                        'status_kurangan' => 'DONE'
                    ]);

                    if ($update_kurangan) {
                        $pesan_notif = "Surat Jalan Kurangan Picker baru (" . $nama_file . ") telah diunggah";

                        // 1. Simpan Notifikasi ke Database
                        $db_notif_data = array(
                            'message'    => $pesan_notif,
                            'category'   => 'TIM INBOUND',
                            'is_read'    => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('notifications', $db_notif_data);

                        // 2. Trigger Pusher menggunakan Custom Library
                        $notif_data = array(
                            'title'   => 'Surat Jalan Kurangan Picker (DONE)',
                            'message' => $pesan_notif
                        );

                        $target_channels = array('notif-role-8', 'notif-role-1');
                        $this->pusher_lib->trigger($target_channels, 'notif-event', $notif_data);
                    }
                }
            }

            $this->session->set_flashdata('success', 'Surat jalan (TP) berhasil diperbaiki & Status item menjadi DONE.');
            $this->show_page('riwayat-surat-jalan-tp');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate data.');
            $this->show_page('riwayat-surat-jalan-tp');
        }
    }

    public function delete_surat_jalan_tp($id)
    {
        while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();

		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        try {
            $delete = $this->Surat_jalan_fcd->delete_tp($id);
            ob_end_clean();
			$this->make_ajax_response(201, 'Surat jalan berhasil dihapus');
        } catch (Exception $e) {
			ob_end_clean();
			$this->make_ajax_response(500, 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ============================================================
    // ============================================================

    // PERGANTIAN BARANG

    public function pergantian_barang()
    {
        $data['title']   = 'Pergantian Barang';
        $data['data']    = $this->Receipt_fcd->get_penyesuaian()->result_array();

        $this->show($data);
    }

    public function bulk_no_penyesuaian() 
    {
        $ids            = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian');

        if ($no_penyesuaian != '') {
            $this->db->trans_start();
            $this->db->where_in('id', $ids);
            $this->db->update('pergantian_barang', [
                'no_penyesuaian' => $no_penyesuaian,
                'status_penyesuaian' => 1,
                'penyesuaian_by' => $this->data['user']['id_user'],
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $this->db->trans_complete();

            // Notify Admin/Finance
            $count = count($ids);
            $this->Notification->send("Tim Accounting telah menyelesaikan penyesuaian stok untuk $count barang (No. Penyesuaian: $no_penyesuaian).", "TIM FINANCE", "Stok Berhasil Disesuaikan");
        }

        $this->show_page('pergantian-barang');
    }

    // ============================================================
    // ============================================================

    // NEW SURAT JALAN ITEMS SPREADSHEET CRUD
    
    public function get_surat_jalan_items()
    {
        $input_range = $this->input->post('reportrange');
        $start_date = date('Y-m-d 00:00:00');
        $end_date   = date('Y-m-d 23:59:59');

        if (!empty($input_range)) {
            $dates = explode(' - ', $input_range);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date   = $dates[1];
            }
        }

        $items = $this->db->where('tgl >=', date('Y-m-d', strtotime($start_date)))
                          ->where('tgl <=', date('Y-m-d', strtotime($end_date)))
                          ->order_by('tgl', 'DESC')
                          ->order_by('id', 'DESC')
                          ->get('tblsurat_jalan_items')
                          ->result_array();

        // Calculate summaries
        $total_rows = count($items);
        $unique_skus = [];
        $total_qty_restock = 0;
        foreach ($items as $item) {
            if (!empty($item['sku'])) {
                $unique_skus[$item['sku']] = true;
            }
            $total_qty_restock += intval($item['qty_restock_real']);
        }
        $total_skus = count($unique_skus);

        echo json_encode([
            'status' => 'success',
            'data' => $items,
            'summary' => [
                'total_rows' => $total_rows,
                'total_skus' => $total_skus,
                'total_qty_restock' => $total_qty_restock
            ]
        ]);
        exit;
    }

    public function save_surat_jalan_item()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id = $this->input->post('id');

        // Real boleh KOSONG (NULL) => "REAL BLM"; jangan paksa jadi 0.
        $realPost = $this->input->post('qty_restock_real');
        $realVal  = ($realPost === '' || $realPost === null) ? null : intval($realPost);

        $data_save = [
            'id_doc'            => $this->input->post('id_doc') ?: null,
            'tgl'               => $this->input->post('tgl') ?: null,
            'jenis_sj'          => $this->input->post('jenis_sj') ?: null,
            'no_trf_jubelio'    => $this->input->post('no_trf_jubelio') ?: null,
            'sku'               => $this->input->post('sku') ?: null,
            'qty_restock_rqst'  => intval($this->input->post('qty_restock_rqst')),
            'qty_restock_real'  => $realVal,
            'qty_restock_over'  => intval($this->input->post('qty_restock_over')),
            'qty_jubelio_disp'  => intval($this->input->post('qty_jubelio_disp')),
            'qty_jubelio_gd'    => intval($this->input->post('qty_jubelio_gd')),
            'sj_jubelio_sku'    => $this->input->post('sj_jubelio_sku') ?: null,
            'sj_jubelio_qty'    => intval($this->input->post('sj_jubelio_qty')),
        ];

        // Nilai turunan dihitung server (bukan dari klien) agar konsisten dgn worksheet.
        $rc = $this->_recon_values([
            'qty_jubelio_gd'  => $data_save['qty_jubelio_gd'],
            'qty_restock_real'=> $realVal,
            'sj_jubelio_qty'  => $data_save['sj_jubelio_qty'],
            'sku'             => $data_save['sku'],
            'sj_jubelio_sku'  => $data_save['sj_jubelio_sku'],
        ]);
        $data_save['selisih']        = $rc['selisih'];
        $data_save['real_vs_jb_qty'] = $rc['real_vs_jb_qty'];
        $data_save['real_vs_jb_sku'] = $rc['real_vs_jb_sku'];

        // Action: pertahankan catatan manual; selain itu isi otomatis.
        $postAct = $this->input->post('action_in_jubelio');
        $postAct = ($postAct === '') ? null : $postAct;
        if ($postAct === null || in_array($postAct, $this->_sj_auto_action_texts(), true)) {
            $data_save['action_in_jubelio'] = ($rc['action_auto'] === '') ? null : $rc['action_auto'];
        } else {
            $data_save['action_in_jubelio'] = $postAct;
        }

        if (empty($id)) {
            $data_save['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('tblsurat_jalan_items', $data_save);
            $id = $this->db->insert_id();
        } else {
            $this->db->where('id', $id)->update('tblsurat_jalan_items', $data_save);
        }

        $saved_item = $this->db->get_where('tblsurat_jalan_items', ['id' => $id])->row_array();
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil disimpan',
            'data' => $saved_item
        ]);
        exit;
    }

    public function delete_surat_jalan_item()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id = $this->input->post('id');
        if (empty($id)) {
            $this->make_ajax_response(400, 'ID tidak boleh kosong');
        }

        $this->db->where('id', $id)->delete('tblsurat_jalan_items');
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil dihapus'
        ]);
        exit;
    }

    public function get_sku_autocomplete()
    {
        $term = $this->input->get('term') ?: '';
        $this->db->select('id_sku, nama_sku');
        $this->db->from('tblsku');
        if ($term !== '') {
            $this->db->group_start();
            $this->db->like('id_sku', $term);
            $this->db->or_like('nama_sku', $term);
            $this->db->group_end();
        }
        $this->db->limit(15);
        $skus = $this->db->get()->result_array();

        $response = [];
        foreach ($skus as $sku) {
            $response[] = [
                'label' => $sku['id_sku'] . ' - ' . $sku['nama_sku'],
                'value' => $sku['id_sku']
            ];
        }

        echo json_encode($response);
        exit;
    }

    // ============================================================
    // SURAT JALAN — DOKUMEN (upload persediaan -> filter display minus)
    // ============================================================

    /**
     * Buat dokumen Surat Jalan baru (status DRAFT). Balikan id_doc + no_sj.
     */
    public function create_surat_jalan_doc()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $tgl = $this->input->post('tgl') ?: date('Y-m-d');

        $doc = [
            'no_sj'          => $this->Surat_jalan_fcd->generate_no_sj($tgl),
            'tgl'            => $tgl,
            'jenis_sj'       => $this->input->post('jenis_sj') ?: null,
            'no_trf_jubelio' => $this->input->post('no_trf_jubelio') ?: null,
            'status'         => 'DRAFT',
            'id_pegawai'     => $this->data['user']['id_user'],
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $id_doc = $this->Surat_jalan_fcd->create_doc($doc);
        $doc['id'] = $id_doc;

        echo json_encode([
            'status'  => 'success',
            'message' => 'Dokumen Surat Jalan dibuat',
            'data'    => $doc
        ]);
        exit;
    }

    /**
     * Daftar dokumen Surat Jalan (untuk dropdown "Buka Dokumen").
     */
    public function list_surat_jalan_docs()
    {
        $input_range = $this->input->post('reportrange');
        $start_date = null;
        $end_date   = null;
        if (!empty($input_range)) {
            $dates = explode(' - ', $input_range);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date   = $dates[1];
            }
        }

        $docs = $this->Surat_jalan_fcd->list_docs($start_date, $end_date);
        echo json_encode(['status' => 'success', 'data' => $docs]);
        exit;
    }

    /**
     * Ambil item milik satu dokumen Surat Jalan.
     */
    public function get_surat_jalan_doc_items($id_doc)
    {
        $doc   = $this->Surat_jalan_fcd->get_doc($id_doc);
        $items = $this->Surat_jalan_fcd->get_doc_items($id_doc);

        $unique_skus = [];
        $total_qty_restock = 0;
        foreach ($items as $item) {
            if (!empty($item['sku'])) {
                $unique_skus[$item['sku']] = true;
            }
            $total_qty_restock += intval($item['qty_restock_real']);
        }

        echo json_encode([
            'status'  => 'success',
            'doc'     => $doc,
            'data'    => $items,
            'summary' => [
                'total_rows'        => count($items),
                'total_skus'        => count($unique_skus),
                'total_qty_restock' => $total_qty_restock,
            ]
        ]);
        exit;
    }

    /**
     * Upload excel persediaan gudang bundle -> tarik baris Display Barang < 0
     * ke dalam item dokumen. Re-upload menimpa item dokumen (idempotent).
     */
    public function upload_bundle_persediaan()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        try {
            $id_doc = intval($this->input->post('id_doc'));
            if ($id_doc <= 0) {
                throw new \Exception('Dokumen Surat Jalan belum dipilih.');
            }

            $doc = $this->Surat_jalan_fcd->get_doc($id_doc);
            if (!$doc) {
                throw new \Exception('Dokumen tidak ditemukan.');
            }

            if (!isset($_FILES['bundleFile']) || $_FILES['bundleFile']['error'] != 0) {
                throw new \Exception('File excel tidak ditemukan atau gagal diunggah.');
            }

            $file = $_FILES['bundleFile']['tmp_name'];
            $ext  = strtolower(pathinfo($_FILES['bundleFile']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                throw new \Exception('Format file harus .xls atau .xlsx');
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($file)
            );
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $sheet   = $spreadsheet->getActiveSheet();
            $dataRaw = $sheet->toArray(null, true, false, false); // 0-indexed rows/cols

            if (empty($dataRaw)) {
                throw new \Exception('File excel kosong.');
            }

            // Deteksi kolom lewat substring header (baris pertama).
            $header = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $dataRaw[0]);

            $findCol = function ($needle) use ($header) {
                foreach ($header as $idx => $h) {
                    if ($h !== '' && strpos($h, $needle) !== false) {
                        return $idx;
                    }
                }
                return -1;
            };

            $colSku     = $findCol('sku');
            $colDisplay = $findCol('display');
            $colGudang  = $findCol('gudang');
            $colNoRak   = $findCol('no rak'); // kolom J -> rak display

            if ($colSku < 0 || $colDisplay < 0) {
                throw new \Exception('Kolom "SKU" atau "Display Barang" tidak ditemukan pada file.');
            }

            // Pre-fetch rak display saat ini untuk sinkronisasi dari kolom J
            // (di-trim agar beda spasi saja tidak dianggap perubahan)
            $no_rak_map = [];
            if ($colNoRak >= 0) {
                foreach ($this->db->select('id_sku, no_rak')->get('tblsku')->result_array() as $s) {
                    $no_rak_map[$s['id_sku']] = trim((string) ($s['no_rak'] ?? ''));
                }
            }
            $rak_display_updated = 0;

            $now  = date('Y-m-d H:i:s');
            $rows = [];
            for ($r = 1; $r < count($dataRaw); $r++) {
                $row = $dataRaw[$r];
                $sku = isset($row[$colSku]) ? trim((string) $row[$colSku]) : '';
                if ($sku === '') {
                    continue;
                }

                // --- Sinkronisasi Rak Display dari kolom J (semua baris, bukan hanya minus) ---
                if ($colNoRak >= 0 && array_key_exists($sku, $no_rak_map)) {
                    $rakJ = isset($row[$colNoRak]) ? trim((string) $row[$colNoRak]) : '';
                    if ($rakJ !== '' && $rakJ !== $no_rak_map[$sku]) {
                        $lama = $no_rak_map[$sku];
                        $this->db->where('id_sku', $sku)->update('tblsku', ['no_rak' => $rakJ]);
                        $this->log_rak_history($sku, 'DISPLAY', $lama, $rakJ, 'PERSEDIAAN');
                        $no_rak_map[$sku] = $rakJ; // hindari log ganda bila SKU muncul lagi
                        $rak_display_updated++;
                    }
                }

                $displayVal = isset($row[$colDisplay]) ? $row[$colDisplay] : null;
                if (!is_numeric($displayVal)) {
                    continue;
                }
                $display = intval($displayVal);
                if ($display >= 0) {
                    continue; // hanya display minus
                }

                $gudang = 0;
                if ($colGudang >= 0 && isset($row[$colGudang]) && is_numeric($row[$colGudang])) {
                    $gudang = intval($row[$colGudang]);
                }

                $isNbp = ($gudang === 0); // Gudang habis => Nol Bisa Pesan
                $rows[] = [
                    'id_doc'            => $id_doc,
                    'tgl'              => $doc['tgl'],
                    'jenis_sj'         => $doc['jenis_sj'],
                    'no_trf_jubelio'   => $doc['no_trf_jubelio'],
                    'sku'              => $sku,
                    'qty_restock_rqst' => 0,
                    'qty_restock_real' => $isNbp ? 0 : null, // NULL => "REAL BLM" sampai Real diisi
                    'qty_restock_over' => 0,
                    'qty_jubelio_disp' => $display,
                    'qty_jubelio_gd'   => $gudang,
                    'sj_jubelio_qty'   => 0,
                    'real_vs_jb_qty'   => 0,
                    'selisih'          => 0,
                    'action_in_jubelio'=> $isNbp ? $this->_sj_act_nbp : null,
                    'created_at'       => $now,
                ];
            }

            $inserted = $this->Surat_jalan_fcd->replace_items_for_doc($id_doc, $rows);

            $msg = "Berhasil menarik {$inserted} baris display minus ke Surat Jalan.";
            if ($rak_display_updated > 0) {
                $msg .= " Rak display {$rak_display_updated} SKU ikut diperbarui dari kolom No Rak.";
            }

            echo json_encode([
                'status'  => 'success',
                'message' => $msg,
                'count'   => $inserted,
                'rak_display_updated' => $rak_display_updated,
            ]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Cetak Surat Jalan format "TIM PRINT RESI" (sesuai PDF) untuk tim Inbound.
     * View standalone (tanpa layout sidebar) agar siap print.
     */
    public function surat_jalan_print($id_doc)
    {
        $doc = $this->Surat_jalan_fcd->get_doc($id_doc);
        if (!$doc) {
            show_404();
            return;
        }

        $data['doc']   = $doc;
        $data['items'] = $this->Surat_jalan_fcd->get_doc_items_with_sku($id_doc);
        $data['pembuat'] = $this->data['user']['name'] ?? 'Tim Accounting';
        $this->load->view('accounting/surat_jalan_print', $data);
    }

    /**
     * Kirim Surat Jalan ke tim Inbound: set status TERKIRIM + notifikasi.
     */
    public function kirim_surat_jalan_inbound()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_doc = intval($this->input->post('id_doc'));
        $doc    = $this->Surat_jalan_fcd->get_doc($id_doc);
        if (!$doc) {
            echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.']);
            exit;
        }

        $item_count = count($this->Surat_jalan_fcd->get_doc_items($id_doc));
        if ($item_count === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Surat Jalan belum ada item. Upload excel persediaan dahulu.']);
            exit;
        }

        $this->Surat_jalan_fcd->update_doc($id_doc, [
            'status' => 'TERKIRIM'
        ]);

        // Notifikasi ke tim Inbound (best-effort, pola sama seperti modul lain)
        try {
            $pesan = 'Surat Jalan ' . $doc['no_sj'] . ' (' . $item_count . ' item) telah dikirim oleh Tim Accounting untuk disiapkan.';
            $this->Notification->send($pesan, 'TIM INBOUND', 'Surat Jalan Baru');
        } catch (\Throwable $e) {
            // abaikan bila layanan notifikasi tidak tersedia
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Surat Jalan ' . $doc['no_sj'] . ' berhasil dikirim ke Tim Inbound.',
        ]);
        exit;
    }

    /**
     * Simpan No. Trf Jubelio di level dokumen (diisi di akhir proses).
     * Ikut memperbarui kolom no_trf_jubelio pada semua item dokumen.
     */
    public function update_surat_jalan_notrf()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_doc = intval($this->input->post('id_doc'));
        $no_trf = $this->input->post('no_trf_jubelio');
        $no_trf = ($no_trf === '' ? null : $no_trf);

        if ($id_doc <= 0 || !$this->Surat_jalan_fcd->get_doc($id_doc)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.']);
            exit;
        }

        $this->Surat_jalan_fcd->update_doc($id_doc, ['no_trf_jubelio' => $no_trf]);
        $this->db->where('id_doc', $id_doc)->update('tblsurat_jalan_items', ['no_trf_jubelio' => $no_trf]);

        echo json_encode(['status' => 'success', 'message' => 'No. Trf Jubelio tersimpan.']);
        exit;
    }

    /**
     * Halaman Daftar Surat Jalan (dulu menu "Surat Jalan Database").
     * Menampilkan semua dokumen Surat Jalan secara live dari tblsurat_jalan_doc.
     */
    public function daftar_surat_jalan()
    {
        $data['title'] = 'Surat Jalan Database';
        $data['stats'] = [
            'total'    => $this->db->count_all_results('tblsurat_jalan_doc'),
            'draft'    => $this->db->where('status', 'DRAFT')->count_all_results('tblsurat_jalan_doc'),
            'terkirim' => $this->db->where('status', 'TERKIRIM')->count_all_results('tblsurat_jalan_doc'),
            'selesai'  => $this->db->where('status', 'SELESAI')->count_all_results('tblsurat_jalan_doc'),
        ];

        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('accounting/daftar_surat_jalan', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    /**
     * DataTables server-side untuk halaman Daftar Surat Jalan.
     */
    public function get_surat_jalan_docs_dt()
    {
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length')) ?: 25;
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?: '';
        $range  = $this->input->post('reportrange') ?: '';

        $start_date = null; $end_date = null;
        if ($range) {
            $d = explode(' - ', $range);
            if (count($d) == 2) { $start_date = $d[0]; $end_date = $d[1]; }
        }

        $build = function () use ($search, $status, $start_date, $end_date) {
            $this->db->from('tblsurat_jalan_doc d');
            $this->db->join('tbluser u', 'u.id_user = d.id_pegawai', 'left');
            if ($start_date && $end_date) {
                $this->db->where('d.tgl >=', date('Y-m-d', strtotime($start_date)));
                $this->db->where('d.tgl <=', date('Y-m-d', strtotime($end_date)));
            }
            if ($status !== '') {
                $this->db->where('d.status', $status);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('d.no_sj', $search);
                $this->db->or_like('d.jenis_sj', $search);
                $this->db->or_like('d.no_trf_jubelio', $search);
                $this->db->group_end();
            }
        };

        $build();
        $total = $this->db->count_all_results('', FALSE);

        $this->db->select("d.*, u.name as nama_pegawai,
            (SELECT COUNT(*) FROM tblsurat_jalan_items i WHERE i.id_doc = d.id) AS total_item,
            (SELECT COALESCE(SUM(i.qty_restock_rqst),0) FROM tblsurat_jalan_items i WHERE i.id_doc = d.id) AS total_request");
        $this->db->order_by('d.tgl', 'DESC');
        $this->db->order_by('d.id', 'DESC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $bukaUrl  = base_url('accounting/form-unggah-surat-jalan');
        $printUrl = base_url('accounting/surat-jalan-print');

        $data = [];
        $i = $start + 1;
        foreach ($rows as $row) {
            $st = $row['status'] ?: 'DRAFT';
            $badge = '<span class="doc-status-badge status-' . $st . '">' . $st . '</span>';
            $aksi =
                '<a class="link btn btn-xs btn-primary" href="' . $bukaUrl . '?doc=' . $row['id'] . '" title="Buka & edit"><i class="fa fa-folder-open"></i> Buka</a> ' .
                '<a class="btn btn-xs btn-info" href="' . $printUrl . '/' . $row['id'] . '" target="_blank" title="Cetak"><i class="fa fa-print"></i></a>';

            $data[] = [
                $i++,
                '<span class="text-mono">' . htmlspecialchars($row['no_sj']) . '</span>',
                htmlspecialchars($row['tgl']),
                htmlspecialchars($row['jenis_sj'] ?? '-'),
                htmlspecialchars($row['no_trf_jubelio'] ?? '-'),
                $badge,
                '<span class="text-center">' . intval($row['total_item']) . '</span>',
                number_format(intval($row['total_request'])),
                htmlspecialchars($row['nama_pegawai'] ?? '-'),
                $aksi,
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
        exit;
    }

    // ============================================================
    // SURAT JALAN — WORKSHEET (edit semua item lintas dokumen)
    // ============================================================

    private $_sj_item_fields = ['sku','qty_jubelio_disp','qty_jubelio_gd','qty_restock_rqst','qty_restock_real','sj_jubelio_sku','sj_jubelio_qty','real_vs_jb_sku','real_vs_jb_qty','action_in_jubelio'];
    private $_sj_doc_fields  = ['tgl','jenis_sj','no_trf_jubelio','status'];
    private $_sj_num_fields  = ['qty_jubelio_disp','qty_jubelio_gd','qty_restock_rqst','qty_restock_real','sj_jubelio_qty','real_vs_jb_qty'];

    private function _sj_field_label($f)
    {
        $m = [
            'sku'=>'SKU','qty_jubelio_disp'=>'Disp','qty_jubelio_gd'=>'Gd',
            'qty_restock_rqst'=>'Request','qty_restock_real'=>'Real',
            'sj_jubelio_sku'=>'SJ SKU','sj_jubelio_qty'=>'SJ Qty',
            'real_vs_jb_sku'=>'RvJB SKU','real_vs_jb_qty'=>'RvJB Qty',
            'action_in_jubelio'=>'Action','tgl'=>'Tanggal','jenis_sj'=>'Jenis SJ',
            'no_trf_jubelio'=>'No. Trf','status'=>'Status',
        ];
        return $m[$f] ?? $f;
    }

    /** Normalisasi nomor transfer: ambil angka, buang nol depan. "TRFO-000044732" => "44732". */
    private function _norm_trf($s)
    {
        $d = preg_replace('/\D/', '', (string)$s);
        $d = ltrim($d, '0');
        return $d === '' ? '' : $d;
    }

    /**
     * Baca 1 file export Transfer Jubelio (.xls/.xlsx).
     * Balikan: ['trf'=>'44732'|null, 'items'=>[sku=>qty], 'raw'=>[[sku,qty]], 'header_found'=>bool].
     */
    private function _parse_jubelio_tr($path)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
            \PhpOffice\PhpSpreadsheet\IOFactory::identify($path)
        );
        $reader->setReadDataOnly(true);
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        $trf = null; $headerRow = -1; $skuCol = -1; $qtyCol = -1;
        foreach ($rows as $ri => $r) {
            $sc = -1; $qc = -1;
            foreach ($r as $ci => $cell) {
                if ($cell === null) continue;
                $s = trim((string)$cell);
                if ($s === '') continue;
                if ($trf === null && preg_match('/TRFO[-\s]*0*([0-9]+)/i', $s, $m)) {
                    $trf = ltrim($m[1], '0'); if ($trf === '') $trf = '0';
                }
                $u = strtoupper($s);
                if ($u === 'SKU') $sc = $ci;
                elseif ($u === 'QTY') $qc = $ci;
            }
            if ($headerRow < 0 && $sc >= 0 && $qc >= 0) { $headerRow = $ri; $skuCol = $sc; $qtyCol = $qc; }
        }

        $items = []; $raw = [];
        if ($headerRow >= 0) {
            $n = count($rows);
            for ($i = $headerRow + 1; $i < $n; $i++) {
                $r = $rows[$i];
                $sku = isset($r[$skuCol]) ? trim((string)$r[$skuCol]) : '';
                if ($sku === '') continue;
                $qv = isset($r[$qtyCol]) ? $r[$qtyCol] : null;
                if (!is_numeric($qv)) continue; // lewati baris footer/teks
                $qty = intval($qv);
                $items[$sku] = ($items[$sku] ?? 0) + $qty;
                $raw[] = ['sku' => $sku, 'qty' => $qty];
            }
        }
        return ['trf' => $trf, 'items' => $items, 'raw' => $raw, 'header_found' => ($headerRow >= 0)];
    }

    /**
     * Upload 1+ file Transfer Jubelio => cocokkan ke baris SJ (by No.Trf + SKU),
     * isi otomatis kolom SJ-JUBELIO SKU & Qty, lalu selisih/flag/action ikut terhitung.
     */
    public function upload_transfer_jubelio()
    {
        if ($this->input->method() !== 'post') { $this->make_ajax_response(405, 'Method not allowed'); }
        if (empty($_FILES['trfFiles']) || empty($_FILES['trfFiles']['name'][0])) {
            echo json_encode(['status'=>'error','message'=>'Tidak ada file diunggah.']); exit;
        }

        // Semua item ber-No.Trf (tabel item kecil) untuk pencocokan ternormalisasi.
        $allItems = $this->db->where('no_trf_jubelio IS NOT NULL', null, false)
            ->get('tblsurat_jalan_items')->result_array();
        if (!is_array($allItems)) $allItems = [];

        $files = $_FILES['trfFiles'];
        $results = [];
        $tot = ['files'=>0,'matched'=>0,'unmatched_sj'=>0,'extra_jb'=>0];

        $count = count($files['name']);
        for ($k = 0; $k < $count; $k++) {
            $orig = $files['name'][$k];
            if ($files['error'][$k] !== UPLOAD_ERR_OK) { $results[] = ['file'=>$orig,'error'=>'Gagal upload']; continue; }
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls','xlsx'], true)) { $results[] = ['file'=>$orig,'error'=>'Format harus .xls/.xlsx']; continue; }

            try { $p = $this->_parse_jubelio_tr($files['tmp_name'][$k]); }
            catch (\Exception $e) { $results[] = ['file'=>$orig,'error'=>'Gagal membaca: '.$e->getMessage()]; continue; }

            if (!$p['trf'])          { $results[] = ['file'=>$orig,'error'=>'No. Transfer (TRFO-...) tidak ditemukan']; continue; }
            if (!$p['header_found']) { $results[] = ['file'=>$orig,'error'=>'Kolom SKU/QTY tidak ditemukan']; continue; }

            $trf = $p['trf'];
            $map = $p['items'];

            $sjItems = array_values(array_filter($allItems, function($it) use ($trf) {
                return $this->_norm_trf($it['no_trf_jubelio']) === $trf;
            }));

            $matched = 0; $unmatchedSj = []; $usedSku = [];
            foreach ($sjItems as $it) {
                $sku = (string)$it['sku'];
                if ($sku !== '' && array_key_exists($sku, $map)) {
                    $qty = intval($map[$sku]);
                    $usedSku[$sku] = true;
                    $it2 = $it; $it2['sj_jubelio_sku'] = $sku; $it2['sj_jubelio_qty'] = $qty;
                    $rc = $this->_recon_values($it2);
                    $upd = [
                        'sj_jubelio_sku' => $sku,
                        'sj_jubelio_qty' => $qty,
                        'selisih'        => $rc['selisih'],
                        'real_vs_jb_qty' => $rc['real_vs_jb_qty'],
                        'real_vs_jb_sku' => $rc['real_vs_jb_sku'],
                    ];
                    $cur = (string)($it['action_in_jubelio'] ?? '');
                    if ($cur === '' || in_array($cur, $this->_sj_auto_action_texts(), true)) {
                        $upd['action_in_jubelio'] = ($rc['action_auto'] === '') ? null : $rc['action_auto'];
                    }
                    $this->db->where('id', $it['id'])->update('tblsurat_jalan_items', $upd);
                    $matched++;
                } else {
                    // SKU di SJ tak ada di transfer Jubelio => tandai (kemungkinan salah/absen transfer)
                    $this->db->where('id', $it['id'])->update('tblsurat_jalan_items', [
                        'sj_jubelio_sku' => null, 'sj_jubelio_qty' => 0, 'real_vs_jb_sku' => 1,
                    ]);
                    if ($sku !== '') $unmatchedSj[] = $sku;
                }
            }

            $extraJb = [];
            foreach ($map as $s => $q) { if (empty($usedSku[$s])) $extraJb[] = $s; }

            $results[] = [
                'file' => $orig, 'trf' => $trf,
                'sj_rows' => count($sjItems),
                'matched' => $matched,
                'unmatched_sj' => $unmatchedSj,
                'extra_jb' => $extraJb,
            ];
            $tot['files']++; $tot['matched'] += $matched;
            $tot['unmatched_sj'] += count($unmatchedSj); $tot['extra_jb'] += count($extraJb);
        }

        echo json_encode(['status'=>'success','total'=>$tot,'results'=>$results]);
        exit;
    }

    private function log_sj_item_history($id_item, $id_doc, $field, $old, $new, $sumber = 'EDIT')
    {
        $this->db->insert('tblsj_item_history', [
            'id_item'    => $id_item ?: null,
            'id_doc'     => $id_doc ?: null,
            'field'      => $field,
            'nilai_lama' => ($old === '' || $old === null) ? null : $old,
            'nilai_baru' => ($new === '' || $new === null) ? null : $new,
            'sumber'     => $sumber,
            'id_pegawai' => $this->data['user']['id_user'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Data worksheet: semua item + info dokumen dalam rentang/filter.
     */
    public function get_sj_worksheet()
    {
        $range  = $this->input->post('reportrange') ?: '';
        $status = $this->input->post('status') ?: '';
        $search = $this->input->post('search') ?: '';

        $start_date = null; $end_date = null;
        if ($range) {
            $d = explode(' - ', $range);
            if (count($d) == 2) { $start_date = $d[0]; $end_date = $d[1]; }
        }

        $this->db->select('i.id AS id_item, i.id_doc, i.sku, i.qty_jubelio_disp, i.qty_jubelio_gd,
            i.qty_restock_rqst, i.qty_restock_real, i.sj_jubelio_sku, i.sj_jubelio_qty,
            i.real_vs_jb_sku, i.real_vs_jb_qty, i.selisih, i.action_in_jubelio,
            d.no_sj, d.tgl, d.jenis_sj, d.no_trf_jubelio, d.status');
        $this->db->from('tblsurat_jalan_items i');
        $this->db->join('tblsurat_jalan_doc d', 'd.id = i.id_doc', 'inner');
        if ($start_date && $end_date) {
            $this->db->where('d.tgl >=', date('Y-m-d', strtotime($start_date)));
            $this->db->where('d.tgl <=', date('Y-m-d', strtotime($end_date)));
        }
        if ($status !== '') { $this->db->where('d.status', $status); }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('d.no_sj', $search);
            $this->db->or_like('d.jenis_sj', $search);
            $this->db->or_like('i.sku', $search);
            $this->db->group_end();
        }
        $this->db->order_by('d.tgl', 'DESC');
        $this->db->order_by('d.id', 'DESC');
        $this->db->order_by('i.id', 'ASC');
        $rows = $this->db->get()->result_array();

        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    // Teks Action baku (dipakai auto-isi & sebagai penanda "boleh ditimpa").
    private $_sj_act_lebih  = 'LEBIH ISSUE - TRF DARI DISP KE GUDANG';
    private $_sj_act_kurang = 'KURANG ISSUE - TRF DARI GUDANG KE DISP';
    private $_sj_act_nbp    = 'NOL BISA PESAN => PENYESUAIAN STOCK';

    /** Daftar teks Action yang boleh ditimpa otomatis (bukan catatan manual user). */
    private function _sj_auto_action_texts()
    {
        return [$this->_sj_act_lebih, $this->_sj_act_kurang, $this->_sj_act_nbp, 'KLOP'];
    }

    /**
     * Hitung nilai rekonsiliasi turunan satu item (mengikuti Excel sheet HARI INI):
     *  - Gudang (qty_jubelio_gd) = 0  => baris NBP: selisih 0, flag 0, Action NBP.
     *  - Real belum diisi (NULL)      => "REAL BLM": selisih 0, flag 0, Action kosong.
     *  - Real terisi                  => selisih = Qty Jubelio (SJ) - Real;
     *       flag qty = 1 bila beda; flag sku = 1 bila SKU transfer beda dgn SKU baris;
     *       Action LEBIH/KURANG, atau kosong bila klop.
     * Balikan: selisih, real_vs_jb_qty, real_vs_jb_sku, action_auto, sel_display, nbp, real_blank.
     */
    private function _recon_values(array $it)
    {
        $gd        = intval($it['qty_jubelio_gd']);
        $realRaw   = array_key_exists('qty_restock_real', $it) ? $it['qty_restock_real'] : null;
        $realBlank = ($realRaw === null || $realRaw === '');
        $jbq       = intval($it['sj_jubelio_qty']);
        $real      = intval($realRaw);
        $sku       = (string)($it['sku'] ?? '');
        $sjSku     = (string)($it['sj_jubelio_sku'] ?? '');

        if ($gd === 0) {
            return ['selisih'=>0,'real_vs_jb_qty'=>0,'real_vs_jb_sku'=>0,
                    'action_auto'=>$this->_sj_act_nbp,'sel_display'=>'0','nbp'=>true,'real_blank'=>$realBlank];
        }
        if ($realBlank) {
            return ['selisih'=>0,'real_vs_jb_qty'=>0,'real_vs_jb_sku'=>0,
                    'action_auto'=>'','sel_display'=>'REAL BLM','nbp'=>false,'real_blank'=>true];
        }
        $sel = $jbq - $real;
        return [
            'selisih'        => $sel,
            'real_vs_jb_qty' => ($sel !== 0) ? 1 : 0,
            'real_vs_jb_sku' => ($sjSku !== '' && $sjSku !== $sku) ? 1 : 0,
            'action_auto'    => $sel > 0 ? $this->_sj_act_lebih : ($sel < 0 ? $this->_sj_act_kurang : ''),
            'sel_display'    => (string)$sel,
            'nbp'            => false,
            'real_blank'     => false,
        ];
    }

    /**
     * Simpan satu sel worksheet (autosave) + catat riwayat.
     */
    public function save_sj_item_cell()
    {
        if ($this->input->method() !== 'post') { $this->make_ajax_response(405, 'Method not allowed'); }

        $id_item = intval($this->input->post('id_item'));
        $field   = $this->input->post('field');
        $value   = $this->input->post('value');

        $item = $this->db->get_where('tblsurat_jalan_items', ['id' => $id_item])->row_array();
        if (!$item) { echo json_encode(['status'=>'error','message'=>'Item tidak ditemukan']); exit; }
        $id_doc = $item['id_doc'];

        $resp = ['status'=>'success'];

        if (in_array($field, $this->_sj_item_fields, true)) {
            $old = $item[$field];
            if (in_array($field, $this->_sj_num_fields, true)) {
                // Real boleh KOSONG (NULL) => menandai "REAL BLM"; field angka lain kosong => 0.
                $new = ($value === '' && $field === 'qty_restock_real') ? null : intval($value);
            } else {
                $new = ($value === '') ? null : $value;
            }
            if ((string)$old !== (string)$new) {
                $this->db->where('id', $id_item)->update('tblsurat_jalan_items', [$field => $new]);
                $this->log_sj_item_history($id_item, $id_doc, $field, $old, $new, 'EDIT');
            }
            // Auto-rekonsiliasi (ikut Excel HARI INI: NBP / REAL BLM / LEBIH / KURANG / klop).
            if (in_array($field, ['qty_restock_real', 'sj_jubelio_qty', 'qty_jubelio_gd', 'sku', 'sj_jubelio_sku'], true)) {
                $item[$field] = $new; // pakai nilai terbaru
                $rc  = $this->_recon_values($item);
                $upd = ['selisih' => $rc['selisih'], 'real_vs_jb_qty' => $rc['real_vs_jb_qty']];

                // Flag SKU hanya di-recompute saat SKU/SJ SKU berubah (jangan reset hasil upload).
                if ($field === 'sku' || $field === 'sj_jubelio_sku') {
                    $upd['real_vs_jb_sku']  = $rc['real_vs_jb_sku'];
                    $resp['real_vs_jb_sku'] = $rc['real_vs_jb_sku'];
                }

                // Action: timpa hanya bila kosong / masih teks auto (bukan catatan manual).
                $curAct = (string)($item['action_in_jubelio'] ?? '');
                if ($curAct === '' || in_array($curAct, $this->_sj_auto_action_texts(), true)) {
                    $upd['action_in_jubelio']  = ($rc['action_auto'] === '') ? null : $rc['action_auto'];
                    $resp['action_in_jubelio'] = $rc['action_auto'];
                }

                $this->db->where('id', $id_item)->update('tblsurat_jalan_items', $upd);
                $resp['selisih']        = $rc['selisih'];
                $resp['sel_display']    = $rc['sel_display'];
                $resp['real_vs_jb_qty'] = $rc['real_vs_jb_qty'];
            }
            $resp['value'] = $new;
        } elseif (in_array($field, $this->_sj_doc_fields, true)) {
            $doc = $this->db->get_where('tblsurat_jalan_doc', ['id' => $id_doc])->row_array();
            $old = $doc[$field] ?? null;
            $new = ($value === '') ? null : $value;
            if ((string)$old !== (string)$new) {
                $this->db->where('id', $id_doc)->update('tblsurat_jalan_doc', [$field => $new]);
                // denormalisasi ke item (kecuali status) agar cetak konsisten
                if (in_array($field, ['tgl','jenis_sj','no_trf_jubelio'], true)) {
                    $this->db->where('id_doc', $id_doc)->update('tblsurat_jalan_items', [$field => $new]);
                }
                $this->log_sj_item_history($id_item, $id_doc, $field, $old, $new, 'EDIT');
            }
            $resp['value'] = $new;
            $resp['doc_level'] = true;
        } else {
            echo json_encode(['status'=>'error','message'=>'Kolom tidak boleh diedit']); exit;
        }

        echo json_encode($resp);
        exit;
    }

    /**
     * Kembalikan sebuah field ke nilai SEBELUM perubahan (revert).
     */
    public function revert_sj_item_field()
    {
        if ($this->input->method() !== 'post') { $this->make_ajax_response(405, 'Method not allowed'); }

        $hid = intval($this->input->post('history_id'));
        $h = $this->db->get_where('tblsj_item_history', ['id' => $hid])->row_array();
        if (!$h) { echo json_encode(['status'=>'error','message'=>'Riwayat tidak ditemukan']); exit; }

        $field   = $h['field'];
        $restore = $h['nilai_lama']; // nilai sebelum edit itu
        $id_doc  = $h['id_doc'];
        $id_item = $h['id_item'];
        $resp = ['status'=>'success'];

        if (in_array($field, $this->_sj_item_fields, true)) {
            $item = $this->db->get_where('tblsurat_jalan_items', ['id' => $id_item])->row_array();
            if (!$item) { echo json_encode(['status'=>'error','message'=>'Item tidak ditemukan']); exit; }
            $current = $item[$field];
            $new = in_array($field, $this->_sj_num_fields, true) ? intval($restore) : $restore;
            $this->db->where('id', $id_item)->update('tblsurat_jalan_items', [$field => $new]);
            if (in_array($field, ['qty_restock_real', 'sj_jubelio_qty', 'qty_jubelio_gd', 'sku', 'sj_jubelio_sku'], true)) {
                $item[$field] = $new;
                $rc  = $this->_recon_values($item);
                $upd = ['selisih' => $rc['selisih'], 'real_vs_jb_qty' => $rc['real_vs_jb_qty']];
                if ($field === 'sku' || $field === 'sj_jubelio_sku') {
                    $upd['real_vs_jb_sku'] = $rc['real_vs_jb_sku'];
                }
                $this->db->where('id', $id_item)->update('tblsurat_jalan_items', $upd);
            }
            $this->log_sj_item_history($id_item, $id_doc, $field, $current, $new, 'REVERT');
        } elseif (in_array($field, $this->_sj_doc_fields, true)) {
            $doc = $this->db->get_where('tblsurat_jalan_doc', ['id' => $id_doc])->row_array();
            $current = $doc[$field] ?? null;
            $new = ($restore === '' ? null : $restore);
            $this->db->where('id', $id_doc)->update('tblsurat_jalan_doc', [$field => $new]);
            if (in_array($field, ['tgl','jenis_sj','no_trf_jubelio'], true)) {
                $this->db->where('id_doc', $id_doc)->update('tblsurat_jalan_items', [$field => $new]);
            }
            $this->log_sj_item_history($id_item, $id_doc, $field, $current, $new, 'REVERT');
        } else {
            echo json_encode(['status'=>'error','message'=>'Kolom tidak dikenal']); exit;
        }

        $resp['message'] = 'Berhasil dikembalikan ke nilai sebelumnya.';
        echo json_encode($resp);
        exit;
    }

    /**
     * DataTables server-side untuk modal Riwayat Pengeditan Worksheet.
     */
    public function get_sj_item_history()
    {
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length')) ?: 25;
        $search = $this->input->post('search')['value'] ?? '';

        $build = function () use ($search) {
            $this->db->from('tblsj_item_history h');
            $this->db->join('tblsurat_jalan_doc d', 'd.id = h.id_doc', 'left');
            $this->db->join('tblsurat_jalan_items i', 'i.id = h.id_item', 'left');
            $this->db->join('tbluser u', 'u.id_user = h.id_pegawai', 'left');
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('d.no_sj', $search);
                $this->db->or_like('i.sku', $search);
                $this->db->or_like('h.field', $search);
                $this->db->group_end();
            }
        };

        $build();
        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('h.*, d.no_sj, i.sku, u.name AS nama_pegawai');
        $this->db->order_by('h.id', 'DESC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $fmt = function ($v) {
            if ($v === null || $v === '') return '<em style="color:#9ca3af;">(kosong)</em>';
            $s = (mb_strlen($v) > 60) ? (mb_substr($v, 0, 60) . '…') : $v;
            return '<span title="' . htmlspecialchars($v) . '">' . htmlspecialchars($s) . '</span>';
        };

        $data = [];
        foreach ($rows as $r) {
            $srcBadge = $r['sumber'] === 'REVERT'
                ? '<span class="label label-warning">REVERT</span>'
                : '<span class="label label-default">EDIT</span>';
            $aksi = '<button type="button" class="btn btn-xs btn-warning btn-revert" data-id="' . $r['id'] . '"><i class="fa fa-undo"></i> Kembalikan</button>';
            $data[] = [
                date('d/m/Y H:i', strtotime($r['created_at'])),
                '<span class="text-mono">' . htmlspecialchars($r['no_sj'] ?? '-') . '</span>',
                htmlspecialchars($r['sku'] ?? '-'),
                htmlspecialchars($this->_sj_field_label($r['field'])),
                $fmt($r['nilai_lama']) . ' &rarr; <strong>' . $fmt($r['nilai_baru']) . '</strong>',
                $srcBadge,
                htmlspecialchars($r['nama_pegawai'] ?? '-'),
                $aksi,
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
        exit;
    }

    public function nomor_rak()
    {
        $data['title'] = 'Nomor Rak (Data SKU)';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('accounting/nomor_rak', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    private function parse_lokasi_stock($lokasi)
    {
        $stocks = [
            'gudang'  => 0,
            'transit' => 0,
            'display' => 0
        ];
        if (empty($lokasi)) {
            return $stocks;
        }
        $parts = explode(',', $lokasi);
        foreach ($parts as $part) {
            $subparts = explode(':', $part);
            if (count($subparts) == 2) {
                $key = strtolower(trim($subparts[0]));
                $val = intval(trim($subparts[1]));
                if ($key == 'gudang') {
                    $stocks['gudang'] = $val;
                } elseif ($key == 'transit') {
                    $stocks['transit'] = $val;
                } elseif ($key == 'display') {
                    $stocks['display'] = $val;
                }
            }
        }
        return $stocks;
    }

    public function get_nomor_rak_dt()
    {
        if ($this->input->get('stats') == '1') {
            $total = $this->db->count_all_results('tblsku');
            $terisi = $this->db->where('no_rak IS NOT NULL')
                               ->where('no_rak !=', '')
                               ->count_all_results('tblsku');
            $kosong = $total - $terisi;
            echo json_encode([
                'total' => $total,
                'terisi' => $terisi,
                'kosong' => $kosong
            ]);
            exit();
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $order  = $this->input->post('order');

        $valid_columns = [
            0 => 'id_sku', // dummy for No
            1 => 'id_sku',
            2 => 'no_rak',
            3 => 'no_rak_gudang',
            4 => "CASE WHEN lokasi LIKE '%Display: %' THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(lokasi, 'Display: ', -1), ',', 1) AS UNSIGNED) ELSE 0 END",
            5 => "CASE WHEN lokasi LIKE '%Transit: %' THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(lokasi, 'Transit: ', -1), ',', 1) AS UNSIGNED) ELSE 0 END",
            6 => "CASE WHEN lokasi LIKE '%Gudang: %' THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(lokasi, 'Gudang: ', -1), ',', 1) AS UNSIGNED) ELSE 0 END"
        ];

        $col = 0;
        $dir = 'ASC';
        if (!empty($order)) {
            $col = intval($order[0]['column']);
            $dir = $order[0]['dir'];
        }
        $order_col = $valid_columns[$col] ?? 'id_sku';

        // Query data
        $this->db->from('tblsku');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('id_sku', $search);
            $this->db->or_like('nama_sku', $search);
            $this->db->or_like('no_rak', $search);
            $this->db->or_like('no_rak_gudang', $search);
            $this->db->group_end();
        }
        $total = $this->db->count_all_results('', FALSE);

        if ($order_col != '') {
            if ($col >= 4) {
                $this->db->order_by($order_col . ' ' . (in_array($dir, ['asc','desc']) ? $dir : 'ASC'), '', FALSE);
            } else {
                $this->db->order_by($order_col, in_array($dir, ['asc','desc']) ? $dir : 'ASC');
            }
        }
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $result = [];
        $i = $start + 1;
        foreach ($rows as $row) {
            $stocks = $this->parse_lokasi_stock($row['lokasi']);
            
            $no_rak_display_val = htmlspecialchars($row['no_rak'] ?? '');
            $no_rak_display_input = '<input type="text" class="form-control input-sm input-norak input-norak-display" data-id="' . htmlspecialchars($row['id_sku']) . '" data-field="no_rak" value="' . $no_rak_display_val . '" placeholder="-- kosong --" style="min-width:90px;">';

            $no_rak_gudang_val = htmlspecialchars($row['no_rak_gudang'] ?? '');
            $no_rak_gudang_input = '<textarea rows="1" class="form-control input-sm input-norak input-norak-gudang" data-id="' . htmlspecialchars($row['id_sku']) . '" data-field="no_rak_gudang" placeholder="-- kosong --">' . $no_rak_gudang_val . '</textarea>';

            $result[] = [
                $i++,
                '<span class="text-mono">' . htmlspecialchars($row['id_sku']) . '</span>',
                $no_rak_display_input,
                $no_rak_gudang_input,
                number_format($stocks['display']), // Display
                number_format($stocks['transit']), // Transit
                number_format($stocks['gudang']),  // Gudang
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $result,
        ]);
        exit();
    }

    public function update_sku_inline()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_sku      = $this->input->post('id_sku');
        $field       = $this->input->post('field');
        $value       = $this->input->post('value');

        $allowed = ['no_rak', 'no_rak_gudang'];
        if (!in_array($field, $allowed)) {
            $this->make_ajax_response(400, 'Field tidak diizinkan');
        }

        // Ambil nilai lama untuk history sebelum di-update
        $old = $this->db->select($field)->get_where('tblsku', ['id_sku' => $id_sku])->row_array();
        $old_val = $old ? ($old[$field] ?? '') : '';

        $this->db->where('id_sku', $id_sku);
        $result = $this->db->update('tblsku', [$field => $value]);

        if ($result) {
            if ((string)$old_val !== (string)$value) {
                $jenis = ($field === 'no_rak_gudang') ? 'GUDANG' : 'DISPLAY';
                $this->log_rak_history($id_sku, $jenis, $old_val, $value, 'INLINE');
            }
            $this->make_ajax_response(200, 'Data berhasil disimpan');
        } else {
            $this->make_ajax_response(500, 'Gagal menyimpan data');
        }
    }

    /**
     * Catat satu perubahan nomor rak ke tblrak_history.
     */
    private function log_rak_history($id_sku, $jenis, $rak_lama, $rak_baru, $sumber)
    {
        $this->db->insert('tblrak_history', [
            'id_sku'     => $id_sku,
            'jenis'      => $jenis,
            'rak_lama'   => ($rak_lama === '' ? null : $rak_lama),
            'rak_baru'   => ($rak_baru === '' ? null : $rak_baru),
            'sumber'     => $sumber,
            'id_pegawai' => $this->data['user']['id_user'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Upload template Rak Gudang (SKU | No Rak Gudang).
     * Nilai rak baru SELALU ditambahkan di belakang nilai lama (dipisah " / ").
     */
    public function upload_rak_gudang()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        try {
            if (!isset($_FILES['rakFile']) || $_FILES['rakFile']['error'] != 0) {
                throw new \Exception('File excel tidak ditemukan atau gagal diunggah.');
            }

            $file = $_FILES['rakFile']['tmp_name'];
            $ext  = strtolower(pathinfo($_FILES['rakFile']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                throw new \Exception('Format file harus .xls atau .xlsx');
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($file)
            );
            $reader->setReadDataOnly(true);
            $sheet   = $reader->load($file)->getActiveSheet();
            $dataRaw = $sheet->toArray(null, true, false, false);

            if (empty($dataRaw)) {
                throw new \Exception('File excel kosong.');
            }

            $header = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $dataRaw[0]);
            $findCol = function ($needle) use ($header) {
                foreach ($header as $idx => $h) {
                    if ($h !== '' && strpos($h, $needle) !== false) return $idx;
                }
                return -1;
            };
            $colSku = $findCol('sku');
            $colRak = $findCol('rak');
            if ($colSku < 0 || $colRak < 0) {
                throw new \Exception('Kolom "SKU" atau "No Rak Gudang" tidak ditemukan pada file.');
            }

            $diproses = 0;
            $dilewati = 0;
            for ($r = 1; $r < count($dataRaw); $r++) {
                $row = $dataRaw[$r];
                $sku = isset($row[$colSku]) ? trim((string) $row[$colSku]) : '';
                $new = isset($row[$colRak]) ? trim((string) $row[$colRak]) : '';
                if ($sku === '' || $new === '') {
                    continue;
                }

                $skuRow = $this->db->select('no_rak_gudang')
                                   ->get_where('tblsku', ['id_sku' => $sku])
                                   ->row_array();
                if (!$skuRow) {
                    $dilewati++;
                    continue; // SKU tidak ada di master
                }

                $lama  = trim((string) ($skuRow['no_rak_gudang'] ?? ''));
                $baru  = ($lama === '') ? $new : ($lama . ' / ' . $new); // selalu tambah (menggandakan)

                $this->db->where('id_sku', $sku)->update('tblsku', ['no_rak_gudang' => $baru]);
                $this->log_rak_history($sku, 'GUDANG', $lama, $baru, 'UPLOAD');
                $diproses++;
            }

            echo json_encode([
                'status'   => 'success',
                'message'  => "Rak Gudang: {$diproses} SKU diperbarui" . ($dilewati ? ", {$dilewati} SKU dilewati (tidak ada di master)." : "."),
                'diproses' => $diproses,
                'dilewati' => $dilewati,
            ]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Unduh template .xlsx untuk upload Rak Gudang.
     */
    public function download_template_rak_gudang()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rak Gudang');
        $sheet->setCellValue('A1', 'SKU');
        $sheet->setCellValue('B1', 'No Rak Gudang');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        // baris contoh
        $sheet->setCellValue('A2', 'C222-AK122-1');
        $sheet->setCellValue('B2', 'G8-1-D3-3');
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(20);

        $filename = 'Template_Rak_Gudang.xlsx';
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * DataTables server-side untuk modal Riwayat perubahan rak.
     */
    public function get_rak_history()
    {
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length')) ?: 25;
        $search = $this->input->post('search')['value'] ?? '';
        $jenis  = $this->input->post('jenis') ?: '';

        $build = function () use ($search, $jenis) {
            $this->db->from('tblrak_history h');
            $this->db->join('tbluser u', 'u.id_user = h.id_pegawai', 'left');
            if ($jenis !== '') {
                $this->db->where('h.jenis', $jenis);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('h.id_sku', $search);
                $this->db->or_like('h.rak_lama', $search);
                $this->db->or_like('h.rak_baru', $search);
                $this->db->group_end();
            }
        };

        // Build query sekali; count_all_results(FALSE) menjaga from/join/where
        // agar tidak perlu (dan tidak boleh) di-build ulang.
        $build();
        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('h.*, u.name as nama_pegawai');
        $this->db->order_by('h.id', 'DESC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        // Ringkas nilai rak yang panjang + tooltip nilai penuh
        $fmt = function ($v) {
            if ($v === null || $v === '') {
                return '<em style="color:#9ca3af;">(kosong)</em>';
            }
            $short = (mb_strlen($v) > 140) ? (mb_substr($v, 0, 140) . '…') : $v;
            return '<span title="' . htmlspecialchars($v) . '">' . htmlspecialchars($short) . '</span>';
        };

        $data = [];
        foreach ($rows as $row) {
            $badge = $row['jenis'] === 'GUDANG'
                ? '<span class="label label-primary">GUDANG</span>'
                : '<span class="label label-success">DISPLAY</span>';

            $isDelete = ($row['rak_baru'] === null || $row['rak_baru'] === '');
            $baruCell = $isDelete
                ? '<span class="label label-danger">DIHAPUS</span>'
                : '<strong>' . $fmt($row['rak_baru']) . '</strong>';
            $perubahan = '<span style="color:#9ca3af;">' . $fmt($row['rak_lama']) . '</span> &rarr; ' . $baruCell;

            $data[] = [
                date('d/m/Y H:i', strtotime($row['created_at'])),
                '<span class="text-mono">' . htmlspecialchars($row['id_sku']) . '</span>',
                $badge,
                $perubahan,
                htmlspecialchars($row['sumber']),
                htmlspecialchars($row['nama_pegawai'] ?? '-'),
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
        exit;
    }
}

