<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packer extends MY_Controller
{

	function __construct()
	{
		parent::__construct();

		$this->load->model('employee_fcd');
		$this->load->model('packer_fcd');
        $this->load->model('receipt_fcd');
        $this->load->model('problemtype_fcd');
	}

    public function scan_packer_nonsubmit()
    {
        $data = [];
        $data['total_scan'] = 0;
        $data['nama_picker'] = '-';
        $data['komputer_packer'] = isset($this->data['nama_pk']) ? $this->data['nama_pk'] : (isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : '-');

        $packer_scan = $this->packer_fcd->get_total_scan_packer_nonsubmit_user($this->data['user']['id_user'])->row();
        if ($packer_scan) {
            $data['total_scan'] = $packer_scan->total_scan;
        }

        // Load session status for packer monitoring
        $this->load->model('packer_monitoring_fcd');
        $session = $this->packer_monitoring_fcd->get_session($this->data['user']['id_user']);
        $data['session_status'] = [
            'masuk' => !empty($session->waktu_masuk),
            'istirahat' => (!empty($session->waktu_istirahat_mulai) && empty($session->waktu_istirahat_selesai)),
            'pulang' => !empty($session->waktu_pulang)
        ];

        $this->show($data, 'packer/scan_packer_nonsubmit');
    }

    public function save_packer_nonsubmit()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        
        $packer_data = ['noresi' => $noresi];
        $status_id = $this->determine_status_performa_id();
        if ($status_id) {
            $packer_data['status_performa_id'] = $status_id;
        }

        $save = $this->packer_fcd->save_packer_nonsubmit($packer_data, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message'], $save['data'] ?? null);
        }

        if ($save['affected_rows'] > 0) {
            // Include is_slow and slow_count if available from performance data
            $res_data = [
                'performance' => $save['performance'] ?? null,
                'is_slow' => $save['performance']['is_slow'] ?? 0,
                'slow_count' => $save['performance']['slow_count'] ?? 0
            ];
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA, $res_data);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function masalah_picker_save()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $masalah['id_printresi'] = $this->input->post('id_printresi');
        $masalah['noresi'] = $this->input->post('noresi');
        $masalah['sku'] = $this->input->post('sku');
        $masalah['qty'] = $this->input->post('qty');
        $masalah['id_typemasalah'] = $this->input->post('type_masalah');
        $masalah['qty_bermasalah'] = $this->input->post('qty_bermasalah');
        $masalah['sku_salah'] = $this->input->post('sku_salah');

        $save = $this->packer_fcd->save_masalah_picker($masalah, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

	public function scan_packer()
	{
        $data = [];

        // Initialize default values to prevent undefined variable errors
        $data['total_scan'] = 0;
        $data['nama_picker'] = '-';
        $data['komputer_picker'] = '-';
        $data['komputer_packer'] = isset($this->data['nama_pk']) ? $this->data['nama_pk'] : (isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : '-');

        // Modal "Submit Masalah Picker" selalu ikut dirender, termasuk saat halaman
        // dibuka lewat GET (belum ada resi yang discan). Tanpa default di bawah,
        // view memicu "Undefined variable $list_type_masalah" dan "$noresi".
        $data['noresi'] = '';
        $data['list_type_masalah'] = $this->problemtype_fcd->get_list();
        
        // Load session status for packer monitoring
        $this->load->model('packer_monitoring_fcd');
        $session = $this->packer_monitoring_fcd->get_session($this->data['user']['id_user']);
        $data['session_status'] = [
            'masuk' => !empty($session->waktu_masuk),
            'istirahat' => (!empty($session->waktu_istirahat_mulai) && empty($session->waktu_istirahat_selesai)),
            'pulang' => !empty($session->waktu_pulang)
        ];

        if ($this->input->method() == 'post') {
            $noresi = trim($this->input->post('noresi'));

            $receipts = $this->receipt_fcd->get_detail_receipt($noresi)->result();
            $packer_scan = $this->packer_fcd->get_total_scan_user($this->data['user']['id_user'])->row();
            $picker_detail = $this->packer_fcd->get_picker_detail_for_packer($noresi);

            // Handle double scan requirement (scan twice to auto save)
            $scan_feedback = $this->handle_double_scan_state($noresi);
            if ($scan_feedback) {
                $data['scan_feedback'] = $scan_feedback;
            }

            // Refresh total scan count if auto save happened
            if (!empty($scan_feedback['auto_saved'])) {
                $packer_scan = $this->packer_fcd->get_total_scan_user($this->data['user']['id_user'])->row();
            }

            // Always update total_scan from current user
            if($packer_scan) {
                $data['total_scan'] = $packer_scan->total_scan;
            }

            if(!empty($receipts)) {
                $data['noresi'] = $noresi;
                $data['list_type_masalah'] = $this->problemtype_fcd->get_list();

                // Get the first receipt for basic info
                $first_receipt = $receipts[0];
                $data['id_printresi'] = $first_receipt->id_printresi;

                // Collect all SKUs and quantities - handle null values
                $data['items'] = [];
                $total_qty = 0;
                foreach ($receipts as $receipt) {
                    $data['items'][] = [
                        'sku' => $receipt->sku ?? '-',
                        'jumlah' => $receipt->jumlah ?? 0,
                        'no_rak' => $receipt->no_rak ?? '-'
                    ];
                    $total_qty += ($receipt->jumlah ?? 0);
                }

                // For backward compatibility, set first item as main
                $data['sku'] = $first_receipt->sku ?? '-';
                $data['qty'] = $first_receipt->jumlah ?? 0;
                $data['no_rak'] = $first_receipt->no_rak ?? '-';
                $data['total_qty'] = $total_qty;
                $data['total_items'] = count($receipts);

                // Update picker details if found
                if($picker_detail) {
                    $data['nama_picker'] = $picker_detail->nama_pegawai;
                    $data['komputer_picker'] = $picker_detail->nama_komputer;
                }
            } else {
                // Handle case where no detail records exist
                $data['noresi'] = $noresi;
                $data['error_message'] = 'No detail records found for this receipt number';
            }
        }

        $this->show($data);
	}

    public function get_scan_packer_data($noresi)
    {
        // Decode the noresi parameter in case it contains special characters
        $noresi = urldecode($noresi);

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

        // Define valid columns for ordering
        $data['valid_columns'] = [
            0 => null,
            1 => null,
            2 => 's.nama_sku',
            3 => 's.jenis_packing',
            4 => 'dr.sku',
            5 => 'dr.jumlah',
            6 => null
        ];

        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : null;

        try {
            // Get receipt data with proper null handling
            $data_resi = $this->receipt_fcd->get_receipt_for_packer($data, $noresi);
            $total_data_resi = $this->receipt_fcd->get_total_receipt_for_packer($noresi);

            $table_number = $data['start'] + 1;
            $data_masalah_picker = array();

            // Check if we have any detail records
            if (!empty($data_resi)) {
                foreach ($data_resi as $row_masalah) {
                    $sku = $row_masalah->sku ?? '-';
                    $jumlah = $row_masalah->jumlah ?? 0;
                    $no_rak = $row_masalah->no_rak ?? '-';
                    $nama_barang = $row_masalah->nama_sku ?? '-';
                    $link_foto = $row_masalah->link_foto ?? '';
                    $yangambil_pegawai = $row_masalah->yangambil_pegawai ?? '';
                    $picker_name = $row_masalah->name ?? $yangambil_pegawai; // Use picker_name if available, fallback to yangambil_pegawai

                    $jenis_packing = $row_masalah->jenis_packing ?? '';
                    if ($jenis_packing !== '') {
                        $packing_display = '<button class="btn btn-xs btn-info" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #00c0ef !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-cube"></i> ' . htmlspecialchars($jenis_packing, ENT_QUOTES, 'UTF-8') . '</button>';
                    } else {
                        $packing_display = '<button class="btn btn-xs btn-warning" disabled style="cursor: default; opacity: 1 !important; font-weight: bold; background-color: #f39c12 !important; color: #fff !important; border: none; pointer-events: none; padding: 4px 8px;"><i class="fa fa-warning"></i> Belum diset</button>';
                    }

                    $data_masalah_picker[] = array(
                        $table_number++ . '.',
                        $link_foto ? '<img src="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '" style="max-width: 100px; max-height: 100px; cursor: pointer;" class="img-thumbnail foto-preview" data-foto="' . htmlspecialchars($link_foto, ENT_QUOTES, 'UTF-8') . '">' : '<span class="text-muted">No Photo</span>',
                        htmlspecialchars($nama_barang, ENT_QUOTES, 'UTF-8'),
                        $packing_display,
                        htmlspecialchars($sku, ENT_QUOTES, 'UTF-8'),
                        $jumlah,
                        '<div class="text-center">
                            <button 
                                class="btn btn-info saveMasalahPicker" 
                                data-id="' . htmlspecialchars($row_masalah->id_printresi, ENT_QUOTES, 'UTF-8') . '"
                                data-noresi="' . htmlspecialchars($row_masalah->noresi, ENT_QUOTES, 'UTF-8') . '"
                                data-sku="' . htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') . '"
                                data-qty="' . htmlspecialchars($jumlah, ENT_QUOTES, 'UTF-8') . '"
                                data-nama-picker="' . htmlspecialchars($picker_name, ENT_QUOTES, 'UTF-8') . '"
                                data-no-rak="' . htmlspecialchars($no_rak, ENT_QUOTES, 'UTF-8') . '"
                            >Masalah Picker</button><br>
                        </div>'
                    );
                }
            } else {
                // When no detail records exist, return empty data
                // The static table will show "No details" message
                $data_masalah_picker = [];
                $total_data_resi = 0;
            }

            $output = array(
                "draw" => $draw,
                "recordsTotal" => $total_data_resi,
                "recordsFiltered" => $total_data_resi,
                "data" => $data_masalah_picker
            );

            echo json_encode($output);
        } catch (Exception $e) {
            // Return error response for DataTables
            $output = array(
                "draw" => $draw,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "Error: " . $e->getMessage()
            );
            echo json_encode($output);
            log_message('error', 'Error in get_scan_packer_data: ' . $e->getMessage());
        }

        exit();
    }

	// request by ajax
	public function save_packer()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

        $noresi = $this->input->post('noresi');
        $status_performa_code = $this->input->post('status_performa');
        $save = $this->process_packer_save($noresi, $status_performa_code);

		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message']);
		}

		if ($save['affected_rows'] > 0) {
			$this->make_ajax_response(201, SUCCESS_SAVE_DATA);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

    public function search_packer()
    {
        $this->show();
    }

    public function get_data_packer()
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
            1 => 'pr.noresi',
            2 => 'u.name',
            3 => 'p.tanggal_packing',
            4 => 'p.tanggal_packing',
            5 => 'p.keterangan',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->packer_fcd->get_data($data);

        $total = $this->packer_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->nama_pegawai,
                date('Y-m-d', strtotime($row->tanggal_packing)),
                date('H:i:s', strtotime($row->tanggal_packing)),
                $row->keterangan,
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

	// Keepalive endpoint untuk prevent session timeout
	public function keepalive()
	{
		// Simple endpoint to keep session alive
		// Just accessing session data will refresh the session timeout
		if ($this->session->userdata('user')) {
			echo json_encode(['status' => 'alive', 'timestamp' => time()]);
		} else {
			set_status_header(401);
			echo json_encode(['status' => 'expired']);
		}
	}

    /**
     * Determine double scan flow (requires identical resi to be submitted twice)
     */
    private function handle_double_scan_state($noresi)
    {
        if (empty($noresi)) {
            return null;
        }

        $current_state = $this->session->userdata('packer_double_scan');
        if (!is_array($current_state)) {
            $current_state = ['resi' => null, 'count' => 0];
        }
        $previous_state = $current_state;

        if ($current_state['resi'] === $noresi) {
            $current_state['count'] = isset($current_state['count']) ? $current_state['count'] + 1 : 1;
        } else {
            $current_state = ['resi' => $noresi, 'count' => 1];
        }

        $feedback = null;

        if ($current_state['count'] >= 2) {
            $save = $this->process_packer_save($noresi);

            if (isset($save['error'])) {
                // exception_code ikut dikirim ke view supaya suara gagalnya bisa
                // dibedakan (sudah packing / pesanan cancel / lainnya). Tanpa ini
                // view cuma punya kalimat pesan, dan semua kegagalan terdengar sama.
                $feedback = [
                    'status' => 'auto_save_failed',
                    'type' => 'error',
                    'message' => $save['message'],
                    'exception_code' => isset($save['data']['EXCEPTION_CODE']) ? $save['data']['EXCEPTION_CODE'] : null,
                    'auto_saved' => false
                ];
            } else {
                $feedback = [
                    'status' => 'auto_save_success',
                    'type' => 'success',
                    'message' => 'Nomor resi ' . $noresi . ' berhasil otomatis disimpan.',
                    'auto_saved' => true
                ];
            }

            $current_state = ['resi' => null, 'count' => 0];
        } else {
            $status = 'need_second_scan';
            $type = 'warning';
            $message = 'Scan ulang nomor resi ' . $noresi . ' satu kali lagi untuk menyimpan.';

            if (!empty($previous_state['resi']) && $previous_state['resi'] !== $noresi && (isset($previous_state['count']) && $previous_state['count'] === 1)) {
                $status = 'scan_restarted';
                $type = 'information';
                $message = 'Nomor resi berubah dari ' . $previous_state['resi'] . ' ke ' . $noresi . '. Scan resi baru ini sekali lagi untuk menyimpan.';
            }

            $feedback = [
                'status' => $status,
                'type' => $type,
                'message' => $message,
                'auto_saved' => false
            ];
        }

        $this->session->set_userdata('packer_double_scan', $current_state);

        return $feedback;
    }

    /**
     * Wrapper to reuse save logic both for ajax endpoint and double scan auto save
     */
    private function process_packer_save($noresi, $status_performa_code = null)
    {
        if (empty($noresi)) {
            return ['error' => true, 'code' => 400, 'message' => 'Nomor resi tidak boleh kosong'];
        }

        $packer = ['noresi' => $noresi];
        $status_id = $this->determine_status_performa_id($status_performa_code);
        if ($status_id) {
            $packer['status_performa_id'] = $status_id;
        }

        return $this->packer_fcd->save($packer, $this->data['user']);
    }

    /**
     * Resolve status performa id by priority
     */
    private function determine_status_performa_id($status_performa_code = null)
    {
        // Prioritas: 1) Status dari parameter, 2) Status session user, 3) Default NORMAL_PACKER
        if (!empty($status_performa_code)) {
            $this->load_kpi_model_if_needed();
            $status_id = $this->kpi_fcd->get_status_id_by_name($status_performa_code);
            if ($status_id) {
                return $status_id;
            }
        }

        $user_status_performa = $this->session->userdata('user_status_performa');
        if ($user_status_performa && isset($user_status_performa->id_statusperforma)) {
            return $user_status_performa->id_statusperforma;
        }

        $this->load_kpi_model_if_needed();
        return $this->kpi_fcd->get_status_id_by_name('NORMAL_PACKER');
    }

    private function load_kpi_model_if_needed()
    {
        if (!isset($this->kpi_fcd)) {
            $this->load->model('kpi_fcd');
        }
    }
}
