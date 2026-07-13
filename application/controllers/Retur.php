<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Retur extends MY_Controller
{
	private $complain_statuses = array(
		'TO_DO' => 'To Do',
		'WAITING_CUSTOMER' => 'Waiting Customer',
		'REFUND_DANA' => 'Refund Dana',
		'PERGANTIAN_BARANG' => 'Pergantian Barang',
		'EXPIRED' => 'Expired'
	);

	function __construct()
	{
		parent::__construct();

		$this->load->model('retur_fcd');
		$this->load->model('marketplace_fcd');
		$this->load->model('courrier_fcd');

		// Automatically add is_update column to tblresiretur if not present
		if (!$this->db->field_exists('is_update', 'tblresiretur')) {
			$this->load->dbforge();
			$this->dbforge->add_column('tblresiretur', [
				'is_update' => [
					'type'       => 'TINYINT',
					'constraint' => 1,
					'default'    => 0
				]
			]);
		}

		// Automatically add is_komplain column to tblresiretur if not present
		if (!$this->db->field_exists('is_komplain', 'tblresiretur')) {
			$this->load->dbforge();
			$this->dbforge->add_column('tblresiretur', [
				'is_komplain' => [
					'type'       => 'TINYINT',
					'constraint' => 1,
					'default'    => 0
				]
			]);
		}

		// Automatically add is_shipped_retur column to tblresiretur if not present
		if (!$this->db->field_exists('is_shipped_retur', 'tblresiretur')) {
			$this->load->dbforge();
			$this->dbforge->add_column('tblresiretur', [
				'is_shipped_retur' => [
					'type'       => 'TINYINT',
					'constraint' => 1,
					'default'    => 0
				]
			]);
		}

		// Ensure TIM ACCOUNTING and TIM FINANCE have icons in the database
		$this->db->where('name', 'TIM ACCOUNTING')->update('menu', ['icon' => 'fa fa-book']);
		$this->db->where('name', 'TIM FINANCE')->update('menu', ['icon' => 'fa fa-money']);

		// Delete Proses Komplain (ID 58) from TIM RETUR menu
		$this->db->where('id', 58)->delete('menu');
		$this->db->where('menuid', 58)->delete('roleaccess');

		// Automatically create menu and role access for Cek SKU if not present
		$menu = $this->db->get_where('menu', ['uri' => 'retur/cek-sku'])->row();
		if (!$menu) {
			$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
			$parent_id = $scan_menu ? $scan_menu->parentid : 30; // fallback to 30
			
			$menu_data = [
				'name'      => 'Cek SKU Retur',
				'parentid'  => $parent_id,
				'uri'       => 'retur/cek-sku',
				'icon'      => 'fa fa-search',
				'sortorder' => 15,
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_id = $this->db->insert_id();
		} else {
			$menu_id = $menu->id;
			$parent_id = $menu->parentid;
			if ($parent_id == 0) {
				$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
				$parent_id = $scan_menu ? $scan_menu->parentid : 30;
				$this->db->where('id', $menu_id)->update('menu', ['parentid' => $parent_id]);
			}
		}

		// Ensure role access is granted for all roles that have access to scan_retur (ID 31)
		$roles = $this->db->get_where('roleaccess', ['menuid' => 31])->result();
		foreach ($roles as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Automatically create menu and role access for Update Retur if not present
		$menu_update = $this->db->get_where('menu', ['uri' => 'retur/update-retur'])->row();
		if (!$menu_update) {
			$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
			$parent_id = $scan_menu ? $scan_menu->parentid : 30;
			
			$menu_data = [
				'name'      => 'Update Retur',
				'parentid'  => $parent_id,
				'uri'       => 'retur/update-retur',
				'icon'      => 'fa fa-refresh',
				'sortorder' => 16,
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_update_id = $this->db->insert_id();
		} else {
			$menu_update_id = $menu_update->id;
			$parent_id = $menu_update->parentid;
			if ($parent_id == 0) {
				$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
				$parent_id = $scan_menu ? $scan_menu->parentid : 30;
				$this->db->where('id', $menu_update_id)->update('menu', ['parentid' => $parent_id]);
			}
		}

		foreach ($roles as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_update_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_update_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Automatically create menu and role access for Scan Retur Komplain if not present
		$menu_komplain = $this->db->get_where('menu', ['uri' => 'retur/scan-retur-komplain'])->row();
		if (!$menu_komplain) {
			$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
			$parent_id = $scan_menu ? $scan_menu->parentid : 30;
			
			$menu_data = [
				'name'      => 'Scan Retur Komplain',
				'parentid'  => $parent_id,
				'uri'       => 'retur/scan-retur-komplain',
				'icon'      => 'fa fa-barcode',
				'sortorder' => 17,
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_komplain_id = $this->db->insert_id();
		} else {
			$menu_komplain_id = $menu_komplain->id;
			$parent_id = $menu_komplain->parentid;
			if ($parent_id == 0) {
				$scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
				$parent_id = $scan_menu ? $scan_menu->parentid : 30;
				$this->db->where('id', $menu_komplain_id)->update('menu', ['parentid' => $parent_id]);
			}
		}

		foreach ($roles as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_komplain_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_komplain_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Automatically create menu and role access for Verifikasi Retur Komplain if not present
		$menu_verif_komplain = $this->db->get_where('menu', ['uri' => 'retur/verifikasi-retur-komplain'])->row();
		if (!$menu_verif_komplain) {
			$menu_data = [
				'name'      => 'Verifikasi Retur Komplain',
				'parentid'  => 92,
				'uri'       => 'retur/verifikasi-retur-komplain',
				'icon'      => 'fa fa-book',
				'sortorder' => 80, // placed below Validasi Jubelio (70)
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_verif_komplain_id = $this->db->insert_id();
		} else {
			$menu_verif_komplain_id = $menu_verif_komplain->id;
			$this->db->where('id', $menu_verif_komplain_id)->update('menu', ['sortorder' => 80]);
		}

		// Copy role access from Validasi Jubelio (ID 137)
		$roles_verif = $this->db->get_where('roleaccess', ['menuid' => 137])->result();
		foreach ($roles_verif as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_verif_komplain_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_verif_komplain_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Automatically create menu and role access for Laporan Retur Shipped if not present
		$menu_shipped = $this->db->get_where('menu', ['uri' => 'retur/laporan-retur-shipped'])->row();
		if (!$menu_shipped) {
			$menu_data = [
				'name'      => 'Laporan Retur Shipped',
				'parentid'  => 92, // TIM ACCOUNTING
				'uri'       => 'retur/laporan-retur-shipped',
				'icon'      => 'fa fa-file-text-o',
				'sortorder' => 90, // below Verifikasi Retur Komplain
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_shipped_id = $this->db->insert_id();
		} else {
			$menu_shipped_id = $menu_shipped->id;
			$this->db->where('id', $menu_shipped_id)->update('menu', ['sortorder' => 90]);
		}

		// Copy role access from Verifikasi Retur (ID 137)
		$roles_shipped = $this->db->get_where('roleaccess', ['menuid' => 137])->result();
		foreach ($roles_shipped as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_shipped_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_shipped_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Rename Validasi Jubelio to Verifikasi Retur
		$this->db->where('uri', 'retur/validasi-jubelio')->update('menu', ['name' => 'Verifikasi Retur']);

		// Automatically create menu and role access for Upload Retur Jubelio if not present
		$menu_upload_jubelio = $this->db->get_where('menu', ['uri' => 'retur/upload-retur-jubelio'])->row();
		if (!$menu_upload_jubelio) {
			$menu_data = [
				'name'      => 'Upload Retur Jubelio',
				'parentid'  => 92,
				'uri'       => 'retur/upload-retur-jubelio',
				'icon'      => 'fa fa-upload',
				'sortorder' => 65, // placed above Verifikasi Retur (70)
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_upload_jubelio_id = $this->db->insert_id();
		} else {
			$menu_upload_jubelio_id = $menu_upload_jubelio->id;
			$this->db->where('id', $menu_upload_jubelio_id)->update('menu', ['sortorder' => 65]);
		}

		// Copy role access from Validasi Jubelio (ID 137)
		foreach ($roles_verif as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_upload_jubelio_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_upload_jubelio_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Automatically create menu and role access for Laporan Retur Komplain if not present
		$menu_lap_komplain = $this->db->get_where('menu', ['uri' => 'retur/laporan-retur-komplain'])->row();
		if (!$menu_lap_komplain) {
			$menu_data = [
				'name'      => 'Laporan Retur Komplain',
				'parentid'  => 30, // TIM RETUR
				'uri'       => 'retur/laporan-retur-komplain',
				'icon'      => 'fa fa-list-alt',
				'sortorder' => 65, // right under Laporan Retur (60)
				'isactive'  => 1,
				'createdby' => 1,
				'created'   => date('Y-m-d H:i:s')
			];
			$this->db->insert('menu', $menu_data);
			$menu_lap_komplain_id = $this->db->insert_id();
		} else {
			$menu_lap_komplain_id = $menu_lap_komplain->id;
			$this->db->where('id', $menu_lap_komplain_id)->update('menu', ['sortorder' => 65]);
		}

		// Copy role access from Laporan Retur (ID 60)
		$roles_lap = $this->db->get_where('roleaccess', ['menuid' => 60])->result();
		foreach ($roles_lap as $r) {
			$access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_lap_komplain_id])->row();
			if (!$access_exist) {
				$this->db->insert('roleaccess', [
					'roleid'    => $r->roleid,
					'menuid'    => $menu_lap_komplain_id,
					'created'   => date('Y-m-d H:i:s'),
					'createdby' => 1
				]);
			}
		}

		// Force reload sidebar cache
		$this->session->unset_userdata('html_menu_tree');
	}

	public function scan_retur()
	{
		// Get today's scan count for Terima Retur
		$data['total_scan_terima'] = $this->retur_fcd->get_total_scan_terima_today($this->data['user']['id_user']);
		
		// Get today's scan count for Buka Retur  
		$data['total_scan_buka'] = $this->retur_fcd->get_total_scan_buka_today($this->data['user']['id_user']);

        if ($this->input->method() == 'post' && !empty($this->input->post('noresi_buka'))) {
            $noresi = trim($this->input->post('noresi_buka'));
            
            // Check if resi exists using simple DB query (avoid loading receipt_fcd which has class issues)
            $exists = $this->db->where('noresi', $noresi)->count_all_results('tblprintresi');
            if ($exists > 0) {
                $data['noresi_buka'] = $noresi;
            } else {
                $data['error_message_buka'] = "Noresi tidak ditemukan.";
            }
            $data['active_tab'] = 'buka-retur';
        } else {
            $data['active_tab'] = 'terima-retur';
        }

		$this->show($data);
	}

	public function update_retur()
	{
		$data['is_update'] = true;
		$data['title'] = 'Update Retur';
		
		// Get today's scan count for Terima Retur
		$data['total_scan_terima'] = $this->retur_fcd->get_total_scan_terima_today($this->data['user']['id_user']);
		
		// Get today's scan count for Buka Retur  
		$data['total_scan_buka'] = $this->retur_fcd->get_total_scan_buka_today($this->data['user']['id_user']);

        if ($this->input->method() == 'post' && !empty($this->input->post('noresi_buka'))) {
            $noresi = trim($this->input->post('noresi_buka'));
            
            $exists = $this->db->where('noresi', $noresi)->count_all_results('tblprintresi');
            if ($exists > 0) {
                $data['noresi_buka'] = $noresi;
            } else {
                $data['error_message_buka'] = "Noresi tidak ditemukan.";
            }
            $data['active_tab'] = 'buka-retur';
        } else {
            $data['active_tab'] = 'terima-retur';
        }

		$this->show($data, 'retur/scan_retur');
	}

	public function scan_retur_komplain()
	{
		$data['is_komplain'] = true;
		$data['title'] = 'Scan Retur Komplain';

		// Get today's scan count for Terima Retur
		$data['total_scan_terima'] = $this->retur_fcd->get_total_scan_terima_today($this->data['user']['id_user']);
		
		// Get today's scan count for Buka Retur  
		$data['total_scan_buka'] = $this->retur_fcd->get_total_scan_buka_today($this->data['user']['id_user']);

        if ($this->input->method() == 'post' && !empty($this->input->post('noresi_buka'))) {
            $noresi = trim($this->input->post('noresi_buka'));
            
            $exists = $this->db->where('noresi', $noresi)->count_all_results('tblprintresi');
            if ($exists > 0) {
                $data['noresi_buka'] = $noresi;
            } else {
                $data['error_message_buka'] = "Noresi tidak ditemukan.";
            }
            $data['active_tab'] = 'buka-retur';
        } else {
            $data['active_tab'] = 'terima-retur';
        }

		$this->show($data, 'retur/scan_retur');
	}

	// request by ajax - Terima Retur
	public function save_retur()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$retur['hasil_scan'] = $this->input->post('hasil_scan');
		$retur['is_update'] = $this->input->post('is_update');
		$retur['is_complain'] = $this->input->post('is_complain') ? 1 : 0;

		$save = $this->retur_fcd->save_terima_retur($retur, $this->data['user']['id_user']);

		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message']);
		}

		if ($save['affected_rows'] > 0) {
			$message = $save['success_message'];
			if (isset($save['warning'])) {
				$message .= '. Peringatan: ' . $save['warning'];
			}
			$this->make_ajax_response(201, $message);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

	// request by ajax - Buka Retur
	public function save_buka_retur()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$retur['status_detail'] = $this->input->post('status_detail_buka');
		$retur['hasil_scan'] = $this->input->post('hasil_scan_buka');
		$retur['is_update'] = $this->input->post('is_update');
		$retur['is_complain'] = $this->input->post('is_complain') ? 1 : 0;

		$save = $this->retur_fcd->save_buka_retur($retur, $this->data['user']['id_user']);

		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message']);
		}

		if ($save['affected_rows'] > 0) {
			$message = $save['success_message'];
			if (isset($save['warning'])) {
				$message .= '. Peringatan: ' . $save['warning'];
			}
			$this->make_ajax_response(201, $message);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

    public function get_buka_retur_sku_data($noresi)
    {
        $noresi = urldecode($noresi);
        $is_update = $this->input->get('is_update') ? true : false;
        $is_complain = $this->input->get('is_complain') ? 1 : 0;

        $this->load->model('retur_fcd');

        if ($is_update) {
            // Reset existing return record for update mode
            $this->db->delete('tblbukaretur', ['resi_buka' => $noresi]);
            $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row();
            if ($receipt) {
                $this->db->where('id_resi', $receipt->id_printresi)->where('is_komplain', $is_complain)->update('tblresiretur', [
                    'status_retur' => 'Terima Retur',
                    'status_detail' => null
                ]);
            }
        }

        $data = array(
            'start' => 0,
            'length' => 0, // 0 = no limit in our model
            'search' => '',
            'order'  => null,
            'valid_columns' => []
        );

        try {
            $data_resi = $this->retur_fcd->get_receipt_for_buka_retur($data, $noresi);

            $result = array();
            foreach ($data_resi as $i => $row) {
                $sku       = $row->sku ?? '-';
                $jumlah    = $row->jumlah ?? 0;
                $nama      = $row->nama_sku ?? '-';
                $link_foto = $row->link_foto ?? '';
                $id        = $row->id_printresi ?? '';
                $nr        = $row->noresi ?? $noresi;

                $result[] = array(
                    'nomor'        => ($i + 1) . '.',
                    'id_printresi' => $id,
                    'noresi'       => $nr,
                    'sku'          => $sku,
                    'no_rak'       => $row->no_rak ?? '-',
                    'jumlah'       => $jumlah,
                    'nama_barang'  => htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
                    'link_foto'    => $link_foto,
                    'html_foto'    => $link_foto
                        ? '<img src="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '" style="max-width:80px;max-height:80px;cursor:pointer;" class="img-thumbnail foto-preview" data-foto="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '">'
                        : '<span class="text-muted">-</span>',
                );
            }

            // Clear output buffer to prevent HTML/whitespace corruption
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'items' => $result, 'total' => count($result)]);
        } catch (Exception $e) {
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'items' => [], 'total' => 0]);
        }
        exit();
    }

    public function save_buka_retur_sku()
    {
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        $noresi = $this->input->post('noresi');
        $id_printresi = $this->input->post('id_printresi');
        $sku = $this->input->post('sku');
        $qty = $this->input->post('qty');
        $status_detail = $this->input->post('status_detail');
        $sku_pergantian = $this->input->post('sku_pergantian');
        $alasan_ditolak = $this->input->post('alasan_ditolak');

        // This should insert into tblbukaretur, and maybe update tblresiretur
        if (empty($noresi) || empty($sku) || empty($status_detail)) {
            $this->make_ajax_response(400, 'Data tidak lengkap');
        }

        // Simpan ke tblbukaretur (now with sku and qty columns)
        $bukaretur = [
            'status_buka' => 'Buka Retur',
            'status_detail_buka' => $status_detail,
            'resi_buka' => $noresi,
            'hasil_scan_buka' => $noresi,
            'sku' => $sku,
            'qty' => $qty,
            'sku_pergantian' => ($sku_pergantian !== null && $sku_pergantian !== '') ? $sku_pergantian : null,
            'alasan_ditolak' => ($alasan_ditolak !== null && $alasan_ditolak !== '') ? $alasan_ditolak : null,
            'tanggal_buka_retur' => date('Y-m-d H:i:s'),
            'id_pegawai' => $this->data['user']['id_user'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tblbukaretur', $bukaretur);

        // Update tblresiretur status_retur = Buka Retur
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row();
        if ($receipt) {
            $is_complain = $this->input->post('is_complain') ? 1 : 0;
            $retur_exist = $this->db->get_where('tblresiretur', ['id_resi' => $receipt->id_printresi, 'is_komplain' => $is_complain])->row();
            if ($retur_exist) {
                // We update it so that it reflects "Buka Retur" overall
                $this->retur_fcd->guarded_pk_update('tblresiretur', 'id_resiretur', $retur_exist->id_resiretur, [
                    'status_retur' => 'Buka Retur',
                    'status_detail' => $status_detail,
                    // Keep original tanggal_resiretur as receive date
                    'id_pegawai' => $this->data['user']['id_user'],
                    'is_update' => $this->input->post('is_update') ? 1 : 0,
                    'is_komplain' => $is_complain
                ]);
            }
        }
        
        $this->make_ajax_response(201, "SKU $sku berhasil diproses dengan status $status_detail");
    }

    /**
     * Proses Buka Retur untuk multiple SKU sekaligus dengan status yang sama.
     */
    public function save_buka_retur_sku_bulk()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $status_detail = $this->input->post('status_detail');
        $items_json = $this->input->post('items');
        $sku_pergantian = $this->input->post('sku_pergantian');
        $alasan_ditolak = $this->input->post('alasan_ditolak');

        if (empty($noresi) || empty($status_detail) || empty($items_json)) {
            $this->make_ajax_response(400, 'Data tidak lengkap');
        }

        $items = json_decode($items_json, true);
        if (empty($items) || !is_array($items)) {
            $this->make_ajax_response(400, 'Data item tidak valid');
        }

        $this->db->trans_start();

        $processed_skus = [];
        $now = date('Y-m-d H:i:s');
        $user_id = $this->data['user']['id_user'];

        foreach ($items as $item) {
            $sku = $item['sku'] ?? null;
            $qty = $item['qty'] ?? 0;

            if ($qty <= 0) continue;

            $bukaretur = [
                'status_buka' => 'Buka Retur',
                'status_detail_buka' => $status_detail,
                'resi_buka' => $noresi,
                'hasil_scan_buka' => $noresi,
                'sku' => ($sku === '') ? null : $sku,
                'qty' => $qty,
                'sku_pergantian' => ($sku_pergantian !== null && $sku_pergantian !== '') ? $sku_pergantian : null,
                'alasan_ditolak' => ($alasan_ditolak !== null && $alasan_ditolak !== '') ? $alasan_ditolak : null,
                'tanggal_buka_retur' => $now,
                'id_pegawai' => $user_id,
                'created_at' => $now
            ];
            $this->db->insert('tblbukaretur', $bukaretur);
            $processed_skus[] = ($sku === '' || $sku === null) ? 'TANPA SKU' : $sku;
        }

        // Update tblresiretur status_retur = Buka Retur (hanya sekali)
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row();
        if ($receipt) {
            $is_complain = $this->input->post('is_complain') ? 1 : 0;
            $retur_exist = $this->db->get_where('tblresiretur', ['id_resi' => $receipt->id_printresi, 'is_komplain' => $is_complain])->row();
            if ($retur_exist) {
                $this->retur_fcd->guarded_pk_update('tblresiretur', 'id_resiretur', $retur_exist->id_resiretur, [
                    'status_retur' => 'Buka Retur',
                    'status_detail' => $status_detail,
                    // Keep original tanggal_resiretur as receive date
                    'id_pegawai' => $user_id,
                    'is_update' => $this->input->post('is_update') ? 1 : 0,
                    'is_komplain' => $is_complain
                ]);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->make_ajax_response(500, 'Gagal memproses data retur massal');
        }

        $sku_list = implode(', ', $processed_skus);
        $this->make_ajax_response(201, "SKU ($sku_list) berhasil diproses dengan status $status_detail");
    }

    public function search_retur()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    // ==================== LAPORAN RETUR ====================
    public function laporan_retur()
    {
        // Get list of couriers for filter
        $data['list_kurir'] = $this->courrier_fcd->get_courrier()->result();
        
        $this->show($data);
    }

    public function laporan_retur_komplain()
    {
        // Get list of couriers for filter
        $data['list_kurir'] = $this->courrier_fcd->get_courrier()->result();
        $data['is_komplain'] = true;
        
        $this->show($data, 'retur/laporan_retur');
    }

    public function get_data_retur()
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
            1 => 't.noresi',
            2 => 't.tanggal_resiretur',
            3 => 't.tanggal_resiretur',
            4 => 't2.nama_marketplace',
            5 => 't3.nama_kurir',
            6 => 't4.username',
            7 => null,
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->retur_fcd->get_data($data);

        $total = $this->retur_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                date('Y-m-d', strtotime($row->tanggal_resiretur)),
                date('H:i:s', strtotime($row->tanggal_resiretur)),
                $row->nama_marketplace,
                $row->nama_toko,
                $row->nama_kurir,
                $row->username,
                '<a href="retur_search/delete/' . $row->id_resiretur . '" class="btn btn-danger confirm" onClick="notyConfirm(event);"><i class="fa fa-trash-o"></i> </a>',
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

    public function delete_retur($id_resiretur)
    {
        $save = $this->retur_fcd->destroy($id_resiretur, $this->data['user']['id_user']);

        if ($save['affected_rows'] > 0) {
            $this->set_message('Success', SUCCESS_REMOVE_DATA, 'information');
        } else {
            $this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
        }

        $this->show_index();
    }

    // ==================== LAPORAN TERIMA RETUR (+ Retur Tadro) ====================
    public function get_data_terima_retur_laporan()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $id_kurir = $this->input->post('id_kurir');
        $status = $this->input->post('status') ?: 'Terima Retur';
        if (!in_array($status, ['Retur Tadro', 'Terima Retur'])) $status = 'Terima Retur';

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
            0 => 'dp.no_pesanan',
            1 => 'tr.noresi',
            2 => 'mp.nama_marketplace',
            3 => 'pr.toko',
            4 => 'kr.nama_kurir',
            5 => 'tr.tanggal_resiretur',
            6 => 'tr.tanggal_resiretur',
            7 => 'dp.sku',
            8 => 'dp.no_rak',
            9 => 'dp.jumlah',
            10 => 'tr.status_detail'
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_retur = $this->retur_fcd->get_laporan_terima_retur($data, $start_date, $end_date, $id_kurir, $status);
        $total = $this->retur_fcd->get_total_laporan_terima_retur($data, $start_date, $end_date, $id_kurir, $status);

        $result_data = array();
        foreach ($list_retur->result() as $row) {
            $noresi = htmlspecialchars($row->noresi, ENT_QUOTES);
            if ($status === 'Retur Tadro') {
                $aksi = '<button class="btn btn-xs btn-warning btn-progress" data-noresi="' . $noresi . '" data-action="terima"><i class="fa fa-check"></i> Tandai Terima</button>';
            } else {
                if ($row->status_retur === 'Buka Retur') {
                    $aksi = '<span class="label label-success"><i class="fa fa-check-circle"></i> Sudah Buka</span>';
                } else {
                    $aksi = '<button class="btn btn-xs btn-success btn-progress" data-noresi="' . $noresi . '" data-action="buka"><i class="fa fa-inbox"></i> Tandai Buka</button>';
                }
            }
            $result_data[] = array(
                $row->no_pesanan ?: '-',
                $row->noresi,
                $row->nama_marketplace ?: '-',
                $row->nama_toko ?: '-',
                $row->nama_kurir ?: '-',
                date('Y-m-d', strtotime($row->tanggal_resiretur)),
                date('H:i:s', strtotime($row->tanggal_resiretur)),
                $row->sku ?: '-',
                $row->no_rak ?: '-',
                $row->jumlah ?: '0',
                $row->status_detail ?: '-',
                $aksi
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

    // ==================== PROGRESS STATUS RETUR (inline action) ====================
    public function progress_status_retur()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
            return;
        }
        $noresi = strtoupper(trim($this->input->post('noresi')));
        $action = trim($this->input->post('action'));

        if (empty($noresi) || !in_array($action, ['terima', 'buka'])) {
            $this->make_ajax_response(400, 'Parameter tidak valid');
            return;
        }

        $result = $this->retur_fcd->progress_retur_status($noresi, $action, $this->data['user']['id_user']);

        if (isset($result['error'])) {
            $this->make_ajax_response(400, $result['error']);
            return;
        }

        $this->make_ajax_response(201, $result['ok']);
    }

    // ==================== LAPORAN BUKA RETUR ====================
    public function get_data_buka_retur_laporan()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $id_kurir = $this->input->post('id_kurir');

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
            0 => 'dp.no_pesanan',
            1 => 'br.resi_buka',
            2 => 'mp.nama_marketplace',
            3 => 'kr.nama_kurir',
            4 => 'br.tanggal_buka_retur',
            5 => 'br.tanggal_buka_retur',
            6 => 'br.sku',
            7 => 'br.qty',
            8 => 'br.status_detail_buka'
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_retur = $this->retur_fcd->get_laporan_buka_retur($data, $start_date, $end_date, $id_kurir);
        $total = $this->retur_fcd->get_total_laporan_buka_retur($data, $start_date, $end_date, $id_kurir);

        $i = $data['start'] + 1;
        $result_data = array();
        foreach ($list_retur->result() as $row) {
            $harga = isset($row->harga) && $row->harga !== null ? (float) $row->harga : null;
            $qty   = (int) ($row->jumlah ?: 0);
            $total_harga = $harga !== null ? $harga * $qty : null;
            $result_data[] = array(
                $row->no_pesanan ?: '-',
                $row->noresi,
                $row->nama_marketplace ?: '-',
                $row->nama_toko ?: '-',
                $row->nama_kurir ?: '-',
                date('Y-m-d', strtotime($row->tanggal_resiretur)),
                date('H:i:s', strtotime($row->tanggal_resiretur)),
                $row->sku ?: '-',
                $row->jumlah ?: '0',
                $harga !== null ? number_format($harga, 0, ',', '.') : '-',
                $total_harga !== null ? number_format($total_harga, 0, ',', '.') : '-',
                $row->status_detail ?: '-'
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

    // ==================== EXPORT EXCEL TERIMA RETUR ====================
    public function export_excel_terima_retur()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $id_kurir = $this->input->get('id_kurir');

        $data['list_retur'] = $this->retur_fcd->get_laporan_terima_retur_all($start_date, $end_date, $id_kurir);
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Terima_Retur_" . date('YmdHis') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('retur/export_terima_retur', $data);
    }

    // ==================== EXPORT EXCEL BUKA RETUR ====================
    public function export_excel_buka_retur()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $id_kurir = $this->input->get('id_kurir');

        $data['list_retur'] = $this->retur_fcd->get_laporan_buka_retur_all($start_date, $end_date, $id_kurir);
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Buka_Retur_" . date('YmdHis') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('retur/export_buka_retur', $data);
    }

    // ==================== LAPORAN RETUR LENGKAP (CONSOLIDATED) ====================
    public function get_data_laporan_retur_lengkap()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $id_kurir = $this->input->post('id_kurir');
        $status_retur = $this->input->post('status_retur');
        $date_type = $this->input->post('date_type') ?: 'terima';
        $is_komplain = $this->input->post('is_komplain') ? 1 : 0;

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
            0 => 'dp.no_pesanan',
            1 => 'tr.noresi',
            2 => 'dp.sku',
            3 => 'dp.jumlah',
            4 => 'dp.no_rak',
            5 => 'mp.nama_marketplace',
            6 => 'pr.toko',
            7 => 'br.harga',
            8 => 'br.harga',
            9 => 'br.status_detail_buka',
            10 => 'tr.status_retur',
            11 => 'tr.tanggal_resiretur',
            12 => 'tr.tanggal_resiretur',
            13 => 'br.tanggal_buka_retur',
            14 => 'br.tanggal_buka_retur',
            15 => 'rv.verified_at',
            16 => 'rv.verified_at'
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_retur = $this->retur_fcd->get_laporan_retur_lengkap($data, $start_date, $end_date, $id_kurir, $status_retur, $date_type, $is_komplain);
        $total = $this->retur_fcd->get_total_laporan_retur_lengkap($data, $start_date, $end_date, $id_kurir, $status_retur, $date_type, $is_komplain);

        $result_data = array();
        foreach ($list_retur->result() as $row) {
            $noresi = htmlspecialchars($row->noresi, ENT_QUOTES);
            
            // Aksi logic
            $aksi = '';
            if ($row->status_retur === 'Retur Online') {
                $aksi = '<button class="btn btn-xs btn-warning btn-progress" data-noresi="' . $noresi . '" data-action="terima"><i class="fa fa-check"></i> Tandai Terima</button>';
            } elseif ($row->status_retur === 'Terima Retur') {
                $aksi = '<button class="btn btn-xs btn-success btn-progress" data-noresi="' . $noresi . '" data-action="buka"><i class="fa fa-inbox"></i> Tandai Buka</button>';
            } else {
                $aksi = '<span class="label label-success"><i class="fa fa-check-circle"></i> Selesai</span>';
            }

            $harga = isset($row->harga) && $row->harga !== null ? (float) $row->harga : null;
            $qty   = (int) ($row->jumlah ?: 0);
            $total_harga = $harga !== null ? $harga * $qty : null;

            $result_data[] = array(
                $row->no_pesanan ?: '-',
                $row->noresi,
                $row->sku ?: '-',
                $row->jumlah ?: '0',
                $row->no_rak ?: '-',
                $row->nama_marketplace ?: '-',
                $row->nama_toko ?: '-',
                $harga !== null ? number_format($harga, 0, ',', '.') : '-',
                $total_harga !== null ? number_format($total_harga, 0, ',', '.') : '-',
                $row->status_detail_buka ?: '-',
                $row->status_retur ?: '-',
                !empty($row->tanggal_terima) ? date('Y-m-d', strtotime($row->tanggal_terima)) : '-',
                !empty($row->tanggal_terima) ? date('H:i:s', strtotime($row->tanggal_terima)) : '-',
                !empty($row->tanggal_buka) ? date('Y-m-d', strtotime($row->tanggal_buka)) : '-',
                !empty($row->tanggal_buka) ? date('H:i:s', strtotime($row->tanggal_buka)) : '-',
                !empty($row->tanggal_acc) ? date('Y-m-d', strtotime($row->tanggal_acc)) : '-',
                !empty($row->tanggal_acc) ? date('H:i:s', strtotime($row->tanggal_acc)) : '-',
                $aksi
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

    public function export_excel_laporan_retur_lengkap()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $id_kurir = $this->input->get('id_kurir');
        $status_retur = $this->input->get('status_retur');
        $date_type = $this->input->get('date_type') ?: 'terima';
        $is_komplain = $this->input->get('is_komplain') ? 1 : 0;

        $data['list_retur'] = $this->retur_fcd->get_laporan_retur_lengkap_all($start_date, $end_date, $id_kurir, $status_retur, $date_type, $is_komplain);
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['date_type'] = $date_type;
        $data['is_komplain'] = $is_komplain;

        $filename = $is_komplain ? "Laporan_Retur_Komplain_" : "Laporan_Retur_Lengkap_";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=" . $filename . date('YmdHis') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('retur/export_laporan_retur', $data);
    }

    // ==================== LAPORAN RETUR SHIPPED ====================
    public function laporan_retur_shipped()
    {
        $this->show($this->data, 'retur/laporan_retur_shipped');
    }

    public function get_data_laporan_retur_shipped()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            0 => null, // No
            1 => 'br.tanggal_buka_retur', // Tanggal
            2 => 'br.resi_buka', // No. Resi
            3 => 'COALESCE(dp.no_pesanan, br.no_pesanan)', // No. Pesanan
            4 => 'COALESCE(pr.toko, br.toko)', // Nama Toko
            5 => 'br.sku', // SKU
            6 => 'br.qty', // Qty
            7 => 'pr.status_pesanan', // Status Paket
            8 => 'rj.status_jubelio', // Status Jubelio
            9 => 'rj.id_jubelio' // Status Berubah
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_retur = $this->retur_fcd->get_laporan_retur_shipped($data, $start_date, $end_date);
        $total = $this->retur_fcd->get_total_laporan_retur_shipped($data, $start_date, $end_date);

        $result_data = array();
        $no = $data['start'] + 1;
        foreach ($list_retur->result() as $row) {
            $status_berubah_label = $row->status_berubah === 'Berubah' 
                ? '<span class="label label-success">Berubah</span>' 
                : '<span class="label label-danger">Belum</span>';

            $result_data[] = array(
                $no++ . '.',
                !empty($row->tanggal_buka) ? date('Y-m-d H:i:s', strtotime($row->tanggal_buka)) : '-',
                htmlspecialchars($row->noresi, ENT_QUOTES),
                htmlspecialchars($row->no_pesanan ?? '-', ENT_QUOTES),
                htmlspecialchars($row->nama_toko ?? '-', ENT_QUOTES),
                htmlspecialchars($row->sku, ENT_QUOTES),
                $row->jumlah,
                htmlspecialchars($row->status_pesanan, ENT_QUOTES),
                !empty($row->status_jubelio) ? htmlspecialchars($row->status_jubelio, ENT_QUOTES) : '-',
                $status_berubah_label
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

    public function export_excel_laporan_retur_shipped()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');

        $data['list_data'] = $this->retur_fcd->get_laporan_retur_shipped_all($start_date, $end_date)->result_array();
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Retur_Shipped_" . date('YmdHis') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->load->view('retur/export_laporan_retur_shipped', $data);
    }
    public function cek_sku()
    {
        $this->data['title'] = 'Cek SKU Retur';
        $this->show($this->data);
    }

    public function get_sku_suggestions()
    {
        header('Content-Type: application/json');
        $term = trim($this->input->get('term'));
        if (strlen($term) < 2) {
            echo json_encode([]);
            exit();
        }

        $this->db->select('id_sku as id, id_sku as label, nama_sku as value');
        $this->db->like('id_sku', $term);
        $this->db->or_like('nama_sku', $term);
        $this->db->limit(10);
        $query = $this->db->get('tblsku');

        echo json_encode($query->result_array());
        exit();
    }

    public function get_retur_details_by_sku_api()
    {
        header('Content-Type: application/json');

        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Request method must be POST']);
            exit();
        }

        $sku = trim($this->input->post('sku'));
        if (empty($sku)) {
            echo json_encode(['success' => false, 'message' => 'SKU tidak boleh kosong']);
            exit();
        }

        $results = $this->retur_fcd->get_retur_details_by_sku($sku);

        $formatted = [];
        foreach ($results as $row) {
            $harga = isset($row['harga']) && $row['harga'] !== null ? (float) $row['harga'] : null;
            $qty   = (int) ($row['jumlah'] ?: 0);
            $total_harga = $harga !== null ? $harga * $qty : null;

            $formatted[] = [
                'no_pesanan' => $row['no_pesanan'] ?: '-',
                'noresi' => $row['noresi'],
                'nama_marketplace' => $row['nama_marketplace'] ?: '-',
                'nama_toko' => $row['nama_toko'] ?: '-',
                'nama_kurir' => $row['nama_kurir'] ?: '-',
                'status_retur' => $row['status_retur'] ?: '-',
                'tanggal_terima' => !empty($row['tanggal_terima']) ? date('Y-m-d H:i:s', strtotime($row['tanggal_terima'])) : '-',
                'tanggal_buka' => !empty($row['tanggal_buka']) ? date('Y-m-d H:i:s', strtotime($row['tanggal_buka'])) : '-',
                'status_detail_buka' => $row['status_detail_buka'] ?: '-',
                'tanggal_acc' => !empty($row['tanggal_acc']) ? date('Y-m-d H:i:s', strtotime($row['tanggal_acc'])) : '-',
                'sku' => $row['sku'] ?: '-',
                'jumlah' => $qty,
                'harga' => $harga !== null ? number_format($harga, 0, ',', '.') : '-',
                'total' => $total_harga !== null ? number_format($total_harga, 0, ',', '.') : '-',
                'no_rak' => $row['no_rak'] ?: '-'
            ];
        }

        echo json_encode(['success' => true, 'data' => $formatted]);
        exit();
    }

	// ==================== PROSES COMPLAIN CUSTOMER ====================
	public function complain()
	{
		$data['message'] = $this->session->flashdata('message');
		$data['status_options'] = $this->complain_statuses;
		$data['list_marketplace'] = $this->marketplace_fcd->get_marketplace()->result_array();
		$data['reportrange'] = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
		if ($this->input->method() === 'post' && $this->input->post('reportrange')) {
			$data['reportrange'] = $this->input->post('reportrange');
		}

		$this->show($data);
	}

	public function save_refund_complain()
	{
		// Start output buffering and clear any existing output
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Set JSON header
		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		try {
			$update_existing = $this->input->post('update_existing');
			$complain_id = $this->input->post('complain_id');

			$complain = array(
				'noresi' => strtoupper(trim($this->input->post('noresi'))),
				'customer_name' => $this->input->post('customer_name'),
				'marketplace' => $this->input->post('marketplace'),
				'no_pesanan' => $this->input->post('no_pesanan'),
				'sku' => $this->input->post('sku'),
				'qty' => $this->input->post('qty'),
				'complain_type' => 'refund',
				'status' => 'TO_DO',
				'refund_amount' => $this->input->post('refund_amount'),
				'refund_bank' => $this->input->post('refund_bank'),
				'refund_account' => $this->input->post('refund_account'),
				'notes' => $this->input->post('notes'),
				// Clear replacement fields
				'replacement_sku' => null,
				'replacement_qty' => null
			);

			if ($update_existing && $complain_id) {
				// Update existing record
				$save = $this->retur_fcd->update_complain($complain_id, $complain, $this->data['user']['id_user']);
				if (isset($save['error'])) {
					ob_end_clean();
					$this->make_ajax_response($save['code'], $save['message']);
				}
				ob_end_clean();
				$this->make_ajax_response(200, 'Complain berhasil diubah ke Refund Dana');
			} else {
				// Insert new record
				$save = $this->retur_fcd->save_complain($complain, $this->data['user']['id_user']);
				if (isset($save['error'])) {
					ob_end_clean();
					$this->make_ajax_response($save['code'], $save['message']);
				}
				ob_end_clean();
				$this->make_ajax_response(201, 'Complain refund berhasil disimpan');
			}
		} catch (Exception $e) {
			ob_end_clean();
			$this->make_ajax_response(500, 'Terjadi kesalahan: ' . $e->getMessage());
		}
	}

	public function check_complain_exists()
	{
		// Start output buffering and clear any existing output
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();

		// Set JSON header
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			ob_end_clean();
			echo json_encode(['exists' => false, 'message' => 'Invalid request method']);
			exit();
		}

		$noresi = strtoupper(trim($this->input->post('noresi')));
		$type = $this->input->post('type'); // 'refund' or 'replacement'

		if (empty($noresi) || empty($type)) {
			ob_end_clean();
			echo json_encode(['exists' => false, 'message' => 'Data tidak lengkap']);
			exit();
		}

		// Check if complain exists for this noresi with the specified type
		$this->db->select('id, status');
		$this->db->from('tblreturcomplain');
		$this->db->where('noresi', $noresi);
		$this->db->where('complain_type', $type);
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$row = $query->row();
			$status_label = isset($this->complain_statuses[$row->status]) ? $this->complain_statuses[$row->status] : $row->status;

			ob_end_clean();
			echo json_encode([
				'exists' => true,
				'id' => $row->id,
				'status' => $row->status,
				'status_label' => $status_label
			]);
		} else {
			ob_end_clean();
			echo json_encode(['exists' => false]);
		}
		exit();
	}

	public function save_replacement_complain()
	{
		// Start output buffering and clear any existing output
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Set JSON header
		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		try {
			$update_existing = $this->input->post('update_existing');
			$complain_id = $this->input->post('complain_id');

			$complain = array(
				'noresi' => strtoupper(trim($this->input->post('noresi'))),
				'customer_name' => $this->input->post('customer_name'),
				'marketplace' => $this->input->post('marketplace'),
				'no_pesanan' => $this->input->post('no_pesanan'),
				'sku' => $this->input->post('sku'),
				'qty' => $this->input->post('qty'),
				'complain_type' => 'replacement',
				'status' => 'TO_DO',
				'replacement_sku' => $this->input->post('replacement_sku'),
				'replacement_qty' => $this->input->post('replacement_qty'),
				'notes' => $this->input->post('notes'),
				// Clear refund fields
				'refund_amount' => null,
				'refund_bank' => null,
				'refund_account' => null
			);

			if ($update_existing && $complain_id) {
				// Update existing record
				$save = $this->retur_fcd->update_complain($complain_id, $complain, $this->data['user']['id_user']);
				if (isset($save['error'])) {
					ob_end_clean();
					$this->make_ajax_response($save['code'], $save['message']);
				}
				ob_end_clean();
				$this->make_ajax_response(200, 'Complain berhasil diubah ke Pergantian Barang');
			} else {
				// Insert new record
				$save = $this->retur_fcd->save_complain($complain, $this->data['user']['id_user']);
				if (isset($save['error'])) {
					ob_end_clean();
					$this->make_ajax_response($save['code'], $save['message']);
				}
				ob_end_clean();
				$this->make_ajax_response(201, 'Complain pergantian barang berhasil disimpan');
			}
		} catch (Exception $e) {
			ob_end_clean();
			$this->make_ajax_response(500, 'Terjadi kesalahan: ' . $e->getMessage());
		}
	}

	public function get_complain_data()
	{
		$draw = intval($this->input->post('draw'));
		$order = $this->input->post('order');

		$params['start'] = intval($this->input->post('start'));
		$params['length'] = intval($this->input->post('length'));
		$params['search'] = $this->input->post('search')['value'] ?? '';
		$params['status_filter'] = $this->input->post('status_filter');
		$params['reportrange'] = $this->input->post('reportrange');

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
			5 => 'rc.complain_type',
			6 => 'rc.status'
		);
		$params['order'] = isset($params['valid_columns'][$col]) ? $params['valid_columns'][$col] : 'rc.created_at';

		$list = $this->retur_fcd->get_complain_list($params);
		$total = $this->retur_fcd->get_total_complain_list($params);

		$rows = array();
		$no = $params['start'] + 1;
		foreach ($list->result() as $row) {
			$status_select = '<select class="form-control complain-status-select" data-id="' . $row->id . '" data-old-status="' . htmlspecialchars($row->status, ENT_QUOTES, 'UTF-8') . '">';
			foreach ($this->complain_statuses as $key => $label) {
				$selected = $row->status === $key ? 'selected' : '';
				$status_select .= '<option value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
			}
			$status_select .= '</select>';

			$rows[] = array(
				$no++ . '.',
				$row->noresi,
				$row->customer_name ?: '-',
				$row->marketplace ?: '-',
				date('d/m/Y H:i', strtotime($row->created_at)),
				ucfirst($row->complain_type),
				$status_select,
				$row->notes ?: '-'
			);
		}

		$output = array(
			'draw' => $draw,
			'recordsTotal' => $total,
			'recordsFiltered' => $total,
			'data' => $rows
		);

		echo json_encode($output);
		exit();
	}

	public function update_complain_status()
	{
		// Start output buffering and clear any existing output
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Set JSON header
		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$id = intval($this->input->post('id'));
		$status = $this->input->post('status');

		if (empty($id) || empty($status) || !isset($this->complain_statuses[$status])) {
			ob_end_clean();
			$this->make_ajax_response(400, 'Data status tidak valid');
		}

		$update = $this->retur_fcd->update_complain_status($id, $status, $this->data['user']['id_user']);
		if (isset($update['error'])) {
			ob_end_clean();
			$this->make_ajax_response($update['code'], $update['message']);
		}

		ob_end_clean();
		$this->make_ajax_response(200, 'Status complain berhasil diperbarui');
	}

	public function get_receipt_info()
	{
		// Start output buffering and clear any existing output
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Set JSON header
		header('Content-Type: application/json');
		
		if ($this->input->method() !== 'post') {
			ob_end_clean();
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$noresi = strtoupper(trim($this->input->post('noresi')));

		if (empty($noresi)) {
			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Nomor resi tidak boleh kosong']);
			exit();
		}

		// Get receipt info from tblprintresi
		$this->db->select('
			pr.noresi,
			m.nama_marketplace
		');
		$this->db->from('tblprintresi pr');
		$this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
		$this->db->where('pr.noresi', $noresi);
		$this->db->limit(1);
		
		$query = $this->db->get();
		$receipt = $query->row_array();

		if (empty($receipt)) {
			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Nomor resi tidak ditemukan']);
			exit();
		}

		// Get no_pesanan from tbldetailprintresi (get unique values, join with comma if multiple)
		$this->db->select('GROUP_CONCAT(DISTINCT dr.no_pesanan ORDER BY dr.no_pesanan SEPARATOR ", ") as no_pesanan');
		$this->db->from('tbldetailprintresi dr');
		$this->db->join('tblprintresi pr2', 'pr2.id_printresi = dr.id_resi', 'inner');
		$this->db->where('pr2.noresi', $noresi);
		$this->db->where('dr.no_pesanan IS NOT NULL');
		$this->db->where('dr.no_pesanan !=', '');
		$pesanan_query = $this->db->get();
		$pesanan = $pesanan_query->row_array();

		// Get SKU details from tbldetailprintresi
		$this->db->select('dr.sku, dr.jumlah');
		$this->db->from('tbldetailprintresi dr');
		$this->db->join('tblprintresi pr3', 'pr3.id_printresi = dr.id_resi', 'inner');
		$this->db->where('pr3.noresi', $noresi);
		$this->db->where('dr.sku IS NOT NULL');
		$this->db->where('dr.sku !=', '');
		$sku_details_query = $this->db->get();
		$sku_details = $sku_details_query->result_array();

		// Build SKU list for dropdown
		$sku_list = [];
		$total_qty = 0;
		$first_sku = '';

		// Log raw SKU details for debugging
		log_message('debug', 'Raw SKU details count: ' . count($sku_details));
		log_message('debug', 'Raw SKU details: ' . print_r($sku_details, true));

		foreach ($sku_details as $detail) {
			$sku_code = trim($detail['sku']); // Trim whitespace

			if (empty($sku_code)) {
				continue; // Skip empty SKUs
			}

			if (!isset($sku_list[$sku_code])) {
				$sku_list[$sku_code] = [
					'item_code' => $sku_code,
					'item_name' => $sku_code,
					'qty' => 0
				];
				if (empty($first_sku)) {
					$first_sku = $sku_code;
				}
			}
			$sku_list[$sku_code]['qty'] += intval($detail['jumlah']);
			$total_qty += intval($detail['jumlah']);
		}

		// Convert to indexed array
		$sku_options = array_values($sku_list);

		// Log final SKU options for debugging
		log_message('debug', 'Final SKU options count: ' . count($sku_options));
		log_message('debug', 'Final SKU options: ' . print_r($sku_options, true));


		// Try to get customer_name, no_pesanan, sku, and qty from existing retur complain if available
		// Note: customer_name tidak tersedia di tblprintresi, jadi hanya ambil dari data complain sebelumnya
		$this->db->select('customer_name, no_pesanan, sku, qty');
		$this->db->from('tblreturcomplain');
		$this->db->where('noresi', $noresi);
		$this->db->order_by('created_at', 'DESC');
		$this->db->limit(1);
		$complain_query = $this->db->get();
		$complain = $complain_query->row_array();

		// Get selected SKU - prioritize from complain, fallback to first SKU from receipt
		$selected_sku = $first_sku;
		if (!empty($complain['sku'])) {
			// If complain SKU contains comma (old data), take first one only
			if (strpos($complain['sku'], ',') !== false) {
				$selected_sku = trim(explode(',', $complain['sku'])[0]);
			} else {
				$selected_sku = $complain['sku'];
			}
		}

		// Get quantity for the selected SKU
		$selected_qty = 0;
		if (!empty($selected_sku) && isset($sku_list[$selected_sku])) {
			$selected_qty = $sku_list[$selected_sku]['qty'];
		}

		$result = [
			'marketplace' => $receipt['nama_marketplace'] ?? '',
			// Customer name hanya dari data complain sebelumnya (jika ada), jika tidak ada biarkan kosong untuk diinput manual
			'customer_name' => !empty($complain['customer_name']) ? $complain['customer_name'] : '',
			'no_pesanan' => !empty($complain['no_pesanan']) ? $complain['no_pesanan'] : (!empty($pesanan['no_pesanan']) ? $pesanan['no_pesanan'] : ''),
			// SKU - always return single SKU (first one)
			'sku' => $selected_sku,
			// Add SKU list for dropdown (qty is in each SKU item)
			'sku_list' => $sku_options
		];

		ob_end_clean();
		header('Content-Type: application/json');
		echo json_encode(['success' => true, 'data' => $result]);
		exit();
	}

	// ==================== DASHBOARD TIM RETUR ====================

	/**
	 * Halaman dashboard Tim Retur.
	 */
	public function dashboard()
	{
		$this->show();
	}

	/**
	 * Data dashboard (JSON) untuk rentang tanggal tertentu.
	 */
	public function get_dashboard_data()
	{
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');

		$start = $this->input->post('start_date');
		$end   = $this->input->post('end_date');
		if (empty($start) || empty($end)) {
			$start = date('Y-m-01 00:00:00');
			$end   = date('Y-m-t 23:59:59');
		}

		$summary        = $this->retur_fcd->dashboard_summary($start, $end);
		$trend          = $this->retur_fcd->dashboard_trend($start, $end);
		$by_kurir       = $this->retur_fcd->dashboard_by_kurir($start, $end);
		$by_mp          = $this->retur_fcd->dashboard_by_marketplace($start, $end);
		$status         = $this->retur_fcd->dashboard_status_buka($start, $end);
		$top_sku        = $this->retur_fcd->dashboard_top_sku($start, $end);
		$oldest_pending = $this->retur_fcd->dashboard_oldest_pending(10);
		$hourly         = $this->retur_fcd->dashboard_hourly($start, $end);
		$complain_type  = $this->retur_fcd->dashboard_complain_by_type($start, $end);

		// Susun tren harian (gabung terima & buka per tanggal)
		$map = array();
		foreach ($trend['terima'] as $t) {
			$map[$t->tgl]['terima'] = (int) $t->n;
		}
		foreach ($trend['buka'] as $b) {
			$map[$b->tgl]['buka'] = (int) $b->n;
		}
		ksort($map);
		$trend_out = array();
		foreach ($map as $tgl => $v) {
			$trend_out[] = array(
				'tgl'    => date('d/m', strtotime($tgl)),
				'terima' => $v['terima'] ?? 0,
				'buka'   => $v['buka'] ?? 0,
			);
		}

		$fmt = function ($rows) {
			$out = array();
			foreach ($rows as $r) {
				$out[] = array('label' => $r->label, 'n' => (int) $r->n);
			}
			return $out;
		};

		// Format oldest pending
		$pending_out = array();
		foreach ($oldest_pending as $p) {
			$pending_out[] = array(
				'noresi'        => $p->noresi,
				'tgl'           => $p->tanggal_resiretur,
				'kurir'         => $p->nama_kurir ?: '-',
				'jam_tunggu'    => (int) $p->jam_tunggu,
			);
		}

		// Format hourly
		$hourly_map = array_fill(0, 24, 0);
		foreach ($hourly as $h) {
			$hourly_map[(int)$h->jam] = (int)$h->n;
		}

		echo json_encode(array(
			'summary'        => $summary,
			'trend'          => $trend_out,
			'by_kurir'       => $fmt($by_kurir),
			'by_mp'          => $fmt($by_mp),
			'status_buka'    => $fmt($status),
			'top_sku'        => $fmt($top_sku),
			'oldest_pending' => $pending_out,
			'hourly'         => $hourly_map,
			'complain_type'  => $fmt($complain_type),
		));
		exit();
	}

	// ==================== IMPORT / SUNTIK RETUR DARI EXCEL ====================

	/**
	 * Halaman menu "Upload Retur" (suntik data retur dari Excel).
	 */
	public function import_retur()
	{
		$this->show();
	}

	/**
	 * Proses upload Excel retur -> suntik ke tblresiretur / tblbukaretur.
	 */
	public function upload_retur()
	{
		while (ob_get_level()) ob_end_clean();
		ob_start();
		header('Content-Type: application/json');

		log_message('error', 'Upload Retur: request masuk, method=' . $this->input->method()
			. ', files=' . (isset($_FILES['returFile']) ? $_FILES['returFile']['name'] . ' (err ' . $_FILES['returFile']['error'] . ')' : 'TIDAK ADA'));

		try {
			if ($this->input->method() !== 'post') {
				throw new Exception('Metode request tidak valid.');
			}
			if (!isset($_FILES['returFile']) || $_FILES['returFile']['error'] != 0) {
				throw new Exception('File tidak ditemukan atau gagal diupload.');
			}
			$ext = strtolower(pathinfo($_FILES['returFile']['name'], PATHINFO_EXTENSION));
			if (!in_array($ext, ['xlsx', 'xls'])) {
				throw new Exception('File harus berformat .xlsx atau .xls');
			}

			ini_set('memory_limit', '3072M');
			set_time_limit(0);

			$file        = $_FILES['returFile']['tmp_name'];
			$reader      = IOFactory::createReader(IOFactory::identify($file));
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($file);
			$sheet       = $spreadsheet->getActiveSheet();
			$raw         = $sheet->toArray(null, true, true, true);

			if (count($raw) < 2) {
				throw new Exception('File tidak berisi data.');
			}

			$header_row = array_shift($raw);
			$wanted = [
				'NOMOR RESI'     => 'noresi',
				'NO RESI'        => 'noresi',
				'SKU'            => 'sku',
				'QTY'            => 'qty',
				'NO PESANAN'     => 'no_pesanan',
				'TOKO'           => 'toko',
				'SHOP'           => 'toko',
				'STORE'          => 'toko',
				'KITA'           => 'kurir',
				'KURIR'          => 'kurir',
				'TAGIHAN'        => 'harga',
				'TGL PESANAN'    => 'tgl_pesanan',
				'TANGGAL PESANAN'=> 'tgl_pesanan',
				'TANGGAL TERIMA' => 'tgl_terima',
				'TANGGAL BUKA'   => 'tgl_buka',
			];
			$colmap = [];
			foreach ($header_row as $letter => $title) {
				$key = strtoupper(trim((string) $title));
				if ($key !== '' && isset($wanted[$key])) {
					$colmap[$wanted[$key]] = $letter;
				}
			}
			if (!isset($colmap['noresi'])) {
				throw new Exception('Kolom "NOMOR RESI" tidak ditemukan di file.');
			}

			$rows = [];
			foreach ($raw as $r) {
				$row = [];
				foreach ($colmap as $field => $letter) {
					$val = $r[$letter] ?? null;
					if ($field === 'tgl_terima' || $field === 'tgl_buka' || $field === 'tgl_pesanan') {
						$val = $this->_parse_excel_date($val);
					}
					$row[$field] = is_string($val) ? trim($val) : $val;
				}
				if (empty($row['noresi'])) continue;
				$rows[] = $row;
			}

			if (empty($rows)) {
				throw new Exception('Tidak ada baris data valid (kolom NOMOR RESI kosong semua).');
			}

			$result = $this->retur_fcd->import_retur_excel($rows, $this->data['user']['id_user']);
			log_message('error', 'Upload Retur: hasil ' . json_encode($result));

			$msg = "Import selesai: {$result['total']} baris — "
				. "Retur Online {$result['tadro']}, Terima {$result['terima']}, Buka {$result['buka']}. "
				. "Resi baru {$result['resi_baru']}, diperbarui {$result['resi_update']}, "
				. "resi tdk ditemukan {$result['resi_not_found']}, kurir tdk dikenal {$result['kurir_not_found']}.";

			if (ob_get_length()) ob_clean();
			$this->make_ajax_response(201, $msg, $result);
		} catch (Throwable $e) {
			if (ob_get_length()) ob_clean();
			log_message('error', 'Upload Retur Error: ' . $e->getMessage());
			$this->make_ajax_response(500, 'Error: ' . $e->getMessage());
		}
	}

	// ==================== VALIDASI / REKONSILIASI JUBELIO ====================

	/**
	 * Halaman menu "Validasi Jubelio" (berdiri sendiri, di luar Laporan Retur).
	 */
	public function validasi_jubelio()
	{
		$data['list_kurir'] = $this->courrier_fcd->get_courrier()->result();
		$data['title'] = 'Verifikasi Retur';

		$this->show($data);
	}

	public function verifikasi_retur_komplain()
	{
		$data['list_kurir'] = $this->courrier_fcd->get_courrier()->result();
		$data['is_komplain'] = true;
		$data['title'] = 'Verifikasi Retur Komplain';

		$this->show($data, 'retur/validasi_jubelio');
	}

	public function upload_retur_jubelio()
	{
		$data['title'] = 'Upload Retur Jubelio';
		$this->show($data, 'retur/upload_retur_jubelio');
	}

	/**
	 * Upload file Excel "daftar retur penjualan" dari Jubelio.
	 * Mengikuti pola upload Excel di Finance::upload_hpp_action().
	 */
	public function upload_jubelio()
	{
		while (ob_get_level()) ob_end_clean();
		ob_start();
		header('Content-Type: application/json');

		log_message('error', 'Upload Jubelio: request masuk, method=' . $this->input->method()
			. ', files=' . (isset($_FILES['jubelioFile']) ? $_FILES['jubelioFile']['name'] . ' (err ' . $_FILES['jubelioFile']['error'] . ')' : 'TIDAK ADA'));

		try {
			if ($this->input->method() !== 'post') {
				throw new Exception('Metode request tidak valid.');
			}

			if (!isset($_FILES['jubelioFile']) || $_FILES['jubelioFile']['error'] != 0) {
				throw new Exception('File tidak ditemukan atau gagal diupload.');
			}

			$ext = strtolower(pathinfo($_FILES['jubelioFile']['name'], PATHINFO_EXTENSION));
			if (!in_array($ext, ['xlsx', 'xls'])) {
				throw new Exception('File harus berformat .xlsx atau .xls');
			}

			ini_set('memory_limit', '3072M');
			set_time_limit(0);

			$file = $_FILES['jubelioFile']['tmp_name'];
			$rows = $this->retur_fcd->parse_jubelio_spreadsheet($file);

			if (empty($rows)) {
				throw new Exception('Tidak ada baris data valid di file (semua baris kosong).');
			}

			$batch_id = date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 6);
			$result   = $this->retur_fcd->insert_jubelio_batch($rows, $batch_id, $this->data['user']['id_user']);

			log_message('error', "Upload Jubelio: batch $batch_id, inserted={$result['inserted']}, updated={$result['updated']}, matched={$result['matched']}");

			if (ob_get_length()) ob_clean();
			$this->make_ajax_response(
				201,
				"Upload Jubelio selesai: {$result['inserted']} baris baru, {$result['updated']} baris diperbarui statusnya, {$result['matched']} resi cocok dengan data scan iresis.",
				['batch_id' => $batch_id, 'inserted' => $result['inserted'], 'updated' => $result['updated'], 'matched' => $result['matched']]
			);
		} catch (Throwable $e) {
			if (ob_get_length()) ob_clean();
			log_message('error', 'Upload Jubelio Error: ' . $e->getMessage());
			$this->make_ajax_response(500, 'Error: ' . $e->getMessage());
		}
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
				return ExcelDate::excelToDateTimeObject((float) $val)->format('Y-m-d H:i:s');
			} catch (Exception $e) {
				return null;
			}
		}

		$val = trim((string) $val);
		if ($val === '' || strtoupper($val) === 'N/A' || $val === '-' || stripos($val, 'belum') !== false) {
			return null;
		}

		// Coba format umum (utamakan format Indonesia dd/mm/yyyy yang gagal di strtotime)
		$formats = [
			'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
			'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y',
			'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
			'd/m/y', 'd-m-y', 'm/d/Y',
		];
		foreach ($formats as $f) {
			// '|' me-reset bagian waktu ke 00:00:00 bila format hanya tanggal
			$d = DateTime::createFromFormat($f . '|', $val);
			if ($d !== false) {
				$err = DateTime::getLastErrors();
				if (empty($err['warning_count']) && empty($err['error_count'])) {
					return $d->format('Y-m-d H:i:s');
				}
			}
		}

		$ts = strtotime($val);
		return $ts ? date('Y-m-d H:i:s', $ts) : null;
	}

	/**
	 * Gabungkan data retur iresis dan Jubelio per no_resi, tentukan kondisi:
	 * Cocok / Hanya di iresis / Hanya di Jubelio.
	 */
	private function _reconcile($start_date, $end_date, $id_kurir = null, $is_update = 0, $is_komplain = 0, $use_old = 0)
	{
		$iresis  = $this->retur_fcd->get_iresis_recon($start_date, $end_date, $id_kurir, $is_update, $is_komplain, $use_old);

		// Tampilkan SEMUA baris hasil scan retur buka apa adanya (satu baris per
		// SKU / id_bukaretur). Sebelumnya baris KE_DISPLAY disaring saat bercampur
		// dengan baris lain, atau di-merge jadi satu; sekarang semua ditampilkan
		// sesuai permintaan laporan Verifikasi Retur. Ringkasan (summary) dihitung
		// per-resi (dedup) agar 1 resi dengan beberapa SKU tidak terhitung dobel.

		$resi_list = [];
		foreach ($iresis as $r) {
			$resi = strtoupper(trim($r->noresi));
			if ($resi !== '') {
				$resi_list[] = $resi;
			}
		}
		$jubelio = $this->retur_fcd->get_jubelio_recon($start_date, $end_date, $resi_list);

		$map = [];

		foreach ($iresis as $r) {
			$key = strtoupper(trim($r->noresi)) . '_' . ($r->id_bukaretur ?: 0);
			$map[$key] = [
				'no_resi'            => $r->noresi,
				'no_pesanan'         => $r->no_pesanan ?: '-',
				'marketplace'        => $r->nama_marketplace ?: '-',
				// Nama Toko sisi iresis: marketplace + toko (mis. "Tokopedia TT CARAMEL").
				// Untuk baris "Cocok" nilai ini akan ditimpa oleh data Jubelio di bawah.
				'nama_toko'          => (trim(($r->nama_marketplace ?? '') . ' ' . ($r->nama_toko ?? '')) ?: '-'),
				'kurir'              => $r->nama_kurir ?: '-',
				'kategori_iresis'    => $r->status_retur ?: '-',
				'status_detail_buka' => $r->status_detail_buka ?: '-',
				'status_jubelio'     => '-',
				'status_pesanan'     => $r->status_pesanan ?: '-',
				'qty_iresis'         => (int) $r->total_qty,
				'qty_jubelio'        => 0,
				'tanggal'            => $r->tanggal_resiretur ? date('Y-m-d H:i', strtotime($r->tanggal_resiretur)) : '-',
				'in_iresis'          => true,
				'in_jubelio'         => false,
				'alasan_ditolak'     => $r->alasan_ditolak ?: '-',
				// Detail SKU: selalu tampilkan SKU beserta qty hasil scan buka
				// (bukan hanya untuk status yang ditolak).
				'detail_ditolak'     => !empty($r->sku_list) ? ($r->sku_list . ' (' . (int) $r->total_qty . ' pcs)') : '-',
				'sku_pergantian'     => in_array(strtoupper(trim($r->status_detail_buka)), [
					'PENUKARAN_BERES', 'PENDINGAN BERES (PB)', 'REQUEST_DARI_PEMBELI', 'REQUEST DARI PEMBELI'
				]) && !empty($r->sku_pergantian) ? ($r->sku_list . ' -> ' . $r->sku_pergantian) : '-',
			];
		}

		foreach ($jubelio as $j) {
			$resi_key = strtoupper(trim($j->noresi));
			if ($resi_key === '') continue;
			
			$matched = false;
			foreach ($map as $k => &$row) {
				if (strpos($k, $resi_key . '_') === 0) {
					$row['in_jubelio']     = true;
					// status_jubelio = kolom A (status_retur dari file Jubelio)
					$row['status_jubelio'] = $j->status_retur ?: '-';
					// status_pesanan = kolom Status (cancel/shipped/completed dari file Jubelio)
					$row['status_pesanan'] = $j->status_jubelio ?: '-';
					$row['qty_jubelio']    = (int) $j->total_qty;
					if ($row['no_pesanan'] === '-' && !empty($j->no_pesanan)) {
						$row['no_pesanan'] = $j->no_pesanan;
					}
					// Nama Toko diambil dari Jubelio: marketplace + nama toko
					// (mis. "SHOPEE ZIOSCARF", "Shop | Tokopedia TT YARRA STORE")
					$j_toko = trim(($j->nama_marketplace ?? '') . ' ' . ($j->nama_toko ?? ''));
					if ($j_toko !== '' && $j_toko !== '-') {
						$row['nama_toko'] = $j_toko;
					}
					$matched = true;
				}
			}
			unset($row);

			// Resi yang HANYA ada di Jubelio (tanpa pasangan di iresis) SENGAJA tidak
			// ditambahkan sebagai baris. Laporan Verifikasi Retur / Verifikasi Retur
			// Komplain hanya menampilkan retur yang benar-benar ada di iresis (hasil scan
			// terima/buka + data suntikan). Data Jubelio dipakai murni sebagai pembanding
			// untuk menandai kondisi "Cocok". Resi Jubelio-only tetap tersimpan di
			// tblreturjubelio (lihat menu Upload Retur Jubelio / list tersendiri).
		}

		foreach ($map as &$row) {
			if ($row['in_iresis'] && $row['in_jubelio']) {
				$row['kondisi'] = 'Cocok';
			} elseif ($row['in_iresis']) {
				$row['kondisi'] = 'Hanya di iresis';
			} else {
				$row['kondisi'] = 'Hanya di Jubelio';
			}
		}
		unset($row);

		// Gabungkan status verifikasi (Step 3)
		$verif = array();
		foreach ($this->retur_fcd->get_verifikasi_list() as $v) {
			$verif[strtoupper(trim($v->no_resi))] = $v;
		}
		foreach ($map as $k => &$row) {
			$resi_key = strtoupper(trim($row['no_resi']));
			if (isset($verif[$resi_key])) {
				$row['verified']     = true;
				$row['verified_at']  = $verif[$resi_key]->verified_at;
				$row['verified_by']  = $verif[$resi_key]->username;
			} else {
				$row['verified']     = false;
				$row['verified_at']  = null;
				$row['verified_by']  = null;
			}
		}
		unset($row);

		return array_values($map);
	}

	/**
	 * Data rekonsiliasi untuk DataTable (client-side) + ringkasan jumlah.
	 */
	public function get_rekonsiliasi_data()
	{
		while (ob_get_level()) ob_end_clean();
		header('Content-Type: application/json');

		$start_date = $this->input->post('start_date');
		$end_date   = $this->input->post('end_date');
		$id_kurir   = $this->input->post('id_kurir');
		$kondisi    = $this->input->post('kondisi'); // '', COCOK, IRESIS, JUBELIO
		$is_komplain = $this->input->post('is_komplain') ? 1 : 0;
		$use_old     = $this->input->post('use_old') ? 1 : 0; // toggle "Data Lama" (snapshot 2026-07-07)

		$merged = $this->_reconcile($start_date, $end_date, $id_kurir, 0, $is_komplain, $use_old);

		$data = [];
		// Ringkasan dihitung per-RESI (dedup). Jika 1 resi punya beberapa SKU
		// (beberapa baris), tetap dihitung 1 kali per kategori — jangan dobel.
		$seen = ['cocok' => [], 'iresis' => [], 'jubelio' => [], 'verified' => [], 'ditolak' => []];
		$no = 1;

		foreach ($merged as $row) {
			$resi_key = strtoupper(trim($row['no_resi']));
			if ($row['kondisi'] === 'Cocok') {
				$seen['cocok'][$resi_key] = true;
			} elseif ($row['kondisi'] === 'Hanya di iresis') {
				$seen['iresis'][$resi_key] = true;
			} else {
				$seen['jubelio'][$resi_key] = true;
			}
			if (!empty($row['verified'])) {
				$seen['verified'][$resi_key] = true;
			}
			$status_detail = strtoupper(trim($row['status_detail_buka'] ?? ''));
			if ($status_detail !== '' && $status_detail !== '-' && $status_detail !== 'KE_DISPLAY') {
				$seen['ditolak'][$resi_key] = true;
			}

			if ($kondisi === 'COCOK'    && $row['kondisi'] !== 'Cocok') continue;
			if ($kondisi === 'IRESIS'   && $row['kondisi'] !== 'Hanya di iresis') continue;
			if ($kondisi === 'JUBELIO'  && $row['kondisi'] !== 'Hanya di Jubelio') continue;
			if ($kondisi === 'VERIFIED' && empty($row['verified'])) continue;
			if ($kondisi === 'BELUM'    && (!empty($row['verified']) || $row['kondisi'] === 'Hanya di Jubelio')) continue;

			switch ($row['kondisi']) {
				case 'Cocok':
					$badge = '<span class="label label-success">Cocok</span>';
					break;
				case 'Hanya di iresis':
					$badge = '<span class="label label-warning">Hanya di iresis</span>';
					break;
				default:
					$badge = '<span class="label label-danger">Hanya di Jubelio</span>';
			}

			// Kolom verifikasi (Step 3) — hanya untuk resi yang sudah diterima (ada di iresis)
			if ($row['kondisi'] === 'Hanya di Jubelio') {
				$verif_cell = '<span class="text-muted" title="Belum diterima Retur">-</span>';
			} else {
				$checked = !empty($row['verified']) ? 'checked' : '';
				$info = '';
				if (!empty($row['verified'])) {
					$info = '<br><small class="text-success">' . htmlspecialchars($row['verified_by'] ?: '', ENT_QUOTES, 'UTF-8')
						. ($row['verified_at'] ? ' · ' . date('d/m H:i', strtotime($row['verified_at'])) : '') . '</small>';
				}
				$verif_cell = '<label style="font-weight:normal;margin:0;cursor:pointer;">'
					. '<input type="checkbox" class="verif-chk" data-resi="' . htmlspecialchars($row['no_resi'], ENT_QUOTES, 'UTF-8') . '" ' . $checked . '> Verif</label>' . $info;
			}

			$data[] = [
				$no++ . '.',
				htmlspecialchars($row['no_resi'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['no_pesanan'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['marketplace'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['nama_toko'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['kurir'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['kategori_iresis'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['status_jubelio'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['status_pesanan'] ?? '-', ENT_QUOTES, 'UTF-8'),
				$row['qty_iresis'],
				$row['qty_jubelio'],
				$row['tanggal'],
				$badge,
				htmlspecialchars($row['status_detail_buka'] ?? '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['alasan_ditolak'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['detail_ditolak'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['sku_pergantian'], ENT_QUOTES, 'UTF-8'),
				$verif_cell,
			];
		}

		// Get updated reconciliation data (is_update = 1).
		// Tab "Update Retur" hanya untuk retur yang benar-benar discan ulang (is_update=1).
		// Baris "Hanya di Jubelio" (tanpa sisi iresis) BUKAN update retur — get_jubelio_recon
		// menarik semua Jubelio pada rentang tanggal, jadi tanpa filter ini laporan penuh
		// resi Jubelio yang belum discan. Baris tsb tetap tampil normal di tab utama.
		$merged_update = array_values(array_filter(
			$this->_reconcile($start_date, $end_date, $id_kurir, 1, $is_komplain),
			function ($r) { return !empty($r['in_iresis']); }
		));
		$update_resi = [];
		$data_update = [];
		$no_update = 1;

		foreach ($merged_update as $row) {
			$resi_key = strtoupper(trim($row['no_resi']));
			$update_resi[$resi_key] = true;
			if (!empty($row['verified'])) {
				$seen['verified'][$resi_key] = true;
			}

			if ($kondisi === 'COCOK'    && $row['kondisi'] !== 'Cocok') continue;
			if ($kondisi === 'IRESIS'   && $row['kondisi'] !== 'Hanya di iresis') continue;
			if ($kondisi === 'JUBELIO'  && $row['kondisi'] !== 'Hanya di Jubelio') continue;
			if ($kondisi === 'VERIFIED' && empty($row['verified'])) continue;
			if ($kondisi === 'BELUM'    && (!empty($row['verified']) || $row['kondisi'] === 'Hanya di Jubelio')) continue;

			switch ($row['kondisi']) {
				case 'Cocok':
					$badge = '<span class="label label-success">Cocok</span>';
					break;
				case 'Hanya di iresis':
					$badge = '<span class="label label-warning">Hanya di iresis</span>';
					break;
				default:
					$badge = '<span class="label label-danger">Hanya di Jubelio</span>';
			}

			if ($row['kondisi'] === 'Hanya di Jubelio') {
				$verif_cell = '<span class="text-muted" title="Belum diterima Retur">-</span>';
			} else {
				$checked = !empty($row['verified']) ? 'checked' : '';
				$info = '';
				if (!empty($row['verified'])) {
					$info = '<br><small class="text-success">' . htmlspecialchars($row['verified_by'] ?: '', ENT_QUOTES, 'UTF-8')
						. ($row['verified_at'] ? ' · ' . date('d/m H:i', strtotime($row['verified_at'])) : '') . '</small>';
				}
				$verif_cell = '<label style="font-weight:normal;margin:0;cursor:pointer;">'
					. '<input type="checkbox" class="verif-chk" data-resi="' . htmlspecialchars($row['no_resi'], ENT_QUOTES, 'UTF-8') . '" ' . $checked . '> Verif</label>' . $info;
			}

			$data_update[] = [
				$no_update++ . '.',
				htmlspecialchars($row['no_resi'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['no_pesanan'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['marketplace'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['nama_toko'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['kurir'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['kategori_iresis'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['status_jubelio'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['status_pesanan'] ?? '-', ENT_QUOTES, 'UTF-8'),
				$row['qty_iresis'],
				$row['qty_jubelio'],
				$row['tanggal'],
				$badge,
				htmlspecialchars($row['status_detail_buka'] ?? '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['alasan_ditolak'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['detail_ditolak'], ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($row['sku_pergantian'], ENT_QUOTES, 'UTF-8'),
				$verif_cell,
			];
		}

		// Rangkum jumlah per-resi (dedup) menjadi angka final untuk kartu ringkasan.
		$summary = [
			'cocok'        => count($seen['cocok']),
			'iresis'       => count($seen['iresis']),
			'jubelio'      => count($seen['jubelio']),
			'verified'     => count($seen['verified']),
			'ditolak'      => count($seen['ditolak']),
			'update_retur' => count($update_resi),
		];

		// Setujui Jubelio = TOTAL - Hanya di iresis - Hanya di Jubelio - Update Retur - Ditolak
		// (TOTAL = Cocok + Hanya di iresis + Hanya di Jubelio + Update Retur, sama seperti kartu TOTAL)
		$summary_total = $summary['cocok'] + $summary['iresis'] + $summary['jubelio'] + $summary['update_retur'];
		$summary['selisih'] = $summary_total - $summary['iresis'] - $summary['jubelio'] - $summary['update_retur'] - $summary['ditolak'];

		echo json_encode(['data' => $data, 'data_update' => $data_update, 'summary' => $summary]);
		exit();
	}

	/**
	 * Set / batalkan verifikasi resi (Step 3 — Accounting).
	 */
	public function verifikasi_jubelio()
	{
		while (ob_get_level()) ob_end_clean();
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$no_resi  = $this->input->post('no_resi');
		$verified = $this->input->post('verified') ? 1 : 0;
		$catatan  = $this->input->post('catatan');

		if (empty($no_resi)) {
			$this->make_ajax_response(400, 'Nomor resi kosong');
		}

		$this->retur_fcd->set_verifikasi($no_resi, $verified, $this->data['user']['id_user'], $catatan);
		$this->make_ajax_response(200, $verified ? 'Resi diverifikasi' : 'Verifikasi dibatalkan');
	}

	/**
	 * Bulk verifikasi resi (Step 3 — Accounting).
	 */
	public function bulk_verifikasi_jubelio()
	{
		while (ob_get_level()) ob_end_clean();
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$resi_list = $this->input->post('resi_list');

		if (empty($resi_list) || !is_array($resi_list)) {
			$this->make_ajax_response(400, 'Daftar resi kosong');
		}

		$this->db->trans_start();
		$user_id = $this->data['user']['id_user'];
		$count = 0;

		foreach ($resi_list as $no_resi) {
			$no_resi = strtoupper(trim($no_resi));
			if ($no_resi === '') continue;

			$this->retur_fcd->set_verifikasi($no_resi, 1, $user_id);
			$count++;
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$this->make_ajax_response(500, 'Gagal memproses verifikasi masal');
		}

		$this->make_ajax_response(200, $count . ' resi berhasil diverifikasi');
	}

	/**
	 * List data Jubelio yang sudah masuk (server-side DataTable) — "List Retur Jubelio".
	 */
	public function get_jubelio_list_data()
	{
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');

		$draw  = intval($this->input->post('draw'));
		$order = $this->input->post('order');

		$params['start']  = intval($this->input->post('start'));
		$params['length'] = intval($this->input->post('length'));
		$params['search'] = $this->input->post('search')['value'] ?? '';

		$start_date = $this->input->post('start_date');
		$end_date   = $this->input->post('end_date');

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
			0  => null,
			1  => 'j.no_resi',
			2  => 'j.no_pesanan',
			3  => 'j.sku',
			4  => 'j.nama_barang',
			5  => 'j.qty',
			6  => 'j.marketplace',
			7  => 'j.nama_toko',
			8  => 'j.kurir',
			9  => 'j.status_retur',
			10 => 'j.status_jubelio',
			11 => 'j.tanggal_retur',
			12 => 'j.found_in_iresis',
		);
		$params['order'] = isset($params['valid_columns'][$col]) ? $params['valid_columns'][$col] : 'j.id_jubelio';

		$list  = $this->retur_fcd->get_jubelio_list($params, $start_date, $end_date);
		$total = $this->retur_fcd->get_total_jubelio_list($params, $start_date, $end_date);

		$rows = array();
		$no = $params['start'] + 1;
		foreach ($list->result() as $r) {
			$cocok = $r->found_in_iresis
				? '<span class="label label-success">Ya</span>'
				: '<span class="label label-default">Belum</span>';
			$rows[] = array(
				$no++ . '.',
				htmlspecialchars($r->no_resi ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->no_pesanan ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->sku ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->nama_barang ?: '-', ENT_QUOTES, 'UTF-8'),
				$r->qty !== null ? (int) $r->qty : '-',
				htmlspecialchars($r->marketplace ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->nama_toko ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->kurir ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->status_retur ?: '-', ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($r->status_jubelio ?: '-', ENT_QUOTES, 'UTF-8'),
				$r->tanggal_retur ? date('Y-m-d H:i', strtotime($r->tanggal_retur)) : '-',
				$cocok,
			);
		}

		echo json_encode(array(
			'draw'            => $draw,
			'recordsTotal'    => $total,
			'recordsFiltered' => $total,
			'data'            => $rows,
		));
		exit();
	}

	/**
	 * Export hasil rekonsiliasi ke Excel (.xls HTML, mengikuti pola export lain).
	 */
	public function export_rekonsiliasi()
	{
		$start_date = $this->input->get('start_date');
		$end_date   = $this->input->get('end_date');
		$id_kurir   = $this->input->get('id_kurir');
		$kondisi    = $this->input->get('kondisi');
		$verified   = $this->input->get('verified'); // 1 = hanya yang sudah diverifikasi
		$is_update  = $this->input->get('is_update') ? 1 : 0;
		$is_komplain = $this->input->get('is_komplain') ? 1 : 0;

		$merged = $this->_reconcile($start_date, $end_date, $id_kurir, $is_update, $is_komplain);

		$filter = ['COCOK' => 'Cocok', 'IRESIS' => 'Hanya di iresis', 'JUBELIO' => 'Hanya di Jubelio'];
		if (!empty($kondisi) && isset($filter[$kondisi])) {
			$target = $filter[$kondisi];
			$merged = array_values(array_filter($merged, function ($r) use ($target) {
				return $r['kondisi'] === $target;
			}));
		}

		// Step 4: export hanya yang sudah diverifikasi
		$only_verified = !empty($verified);
		if ($only_verified) {
			$merged = array_values(array_filter($merged, function ($r) {
				return !empty($r['verified']);
			}));
		}

		$data['list']          = $merged;
		$data['start_date']    = $start_date;
		$data['end_date']      = $end_date;
		$data['only_verified'] = $only_verified;

		$fname = $only_verified ? 'Retur_Terverifikasi_Jubelio_' : 'Rekonsiliasi_Retur_Jubelio_';

		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=" . $fname . date('YmdHis') . ".xls");
		header("Pragma: no-cache");
		header("Expires: 0");

		$this->load->view('retur/export_rekonsiliasi', $data);
	}

    public function kirim_display()
    {
        $this->data['title'] = 'Kirim ke Display';
        $this->show($this->data);
    }

    public function get_unbatched_returns()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $reportrange = $this->input->post('reportrange');
        $start_date = null;
        $end_date = null;

        if (!empty($reportrange)) {
            $dates = explode(' s/d ', $reportrange);
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date = $dates[1];
            }
        }

        $col = 0;
        $dir = 'desc';
        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        $valid_columns = array(
            0 => null, // Checkbox
            1 => 'br.resi_buka',
            2 => 'br.sku',
            3 => 's.nama_sku',
            4 => 'br.qty',
            5 => 'dp.no_rak',
            6 => 'mp.nama_marketplace',
            7 => 'br.toko',
            8 => 'br.tanggal_buka_retur'
        );

        $params = array(
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'start'      => $data['start'],
            'length'     => $data['length'],
            'search'     => $data['search'],
            'order'      => $valid_columns[$col] ?? 'br.tanggal_buka_retur',
            'dir'        => $dir
        );

        $list = $this->retur_fcd->get_unbatched_returns_data($params);
        $total = $this->retur_fcd->get_unbatched_returns_count($params);

        $result_data = array();
        foreach ($list->result_array() as $row) {
            $id = $row['id_bukaretur'];
            $chk = '<input type="checkbox" class="chk-item" value="' . $id . '" />';

            $result_data[] = array(
                $chk,
                $row['noresi'],
                $row['sku'],
                $row['nama_barang'],
                $row['qty'] ?? 1,
                $row['no_rak'] ?: '-',
                $row['nama_marketplace'] ?: '-',
                $row['nama_toko'] ?: '-',
                !empty($row['tanggal_buka']) ? date('d/m/Y H:i:s', strtotime($row['tanggal_buka'])) : '-'
            );
        }

        $output = array(
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "data"            => $result_data
        );
        echo json_encode($output);
        exit();
    }

    public function create_display_batch()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, 'Request method must be POST');
        }

        $ids = $this->input->post('ids');
        if (empty($ids) || !is_array($ids)) {
            $this->make_ajax_response(400, 'Silakan pilih barang retur terlebih dahulu!');
        }

        $user_id = $this->data['user']['id_user'];
        $res = $this->retur_fcd->create_display_batch_tx($ids, $user_id);

        if (!$res['success']) {
            $this->make_ajax_response(500, $res['message']);
        }

        $this->make_ajax_response(201, 'Batch display berhasil dibuat dengan kode: ' . $res['kode_batch'], [
            'kode_batch' => $res['kode_batch'],
            'id_batch'   => $res['id_batch']
        ]);
    }
}
