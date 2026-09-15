<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report extends MY_Controller
{

	function __construct()
	{
		parent::__construct();

		$this->load->model('receipt_fcd');
		$this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
	}
	public function receipt_in_process_report()
	{
		$data['message'] = $this->session->flashdata('message');

		$this->show($data);
	}

	public function get_receipt_in_process_data_tab0()
	{
		// Endpoint ini hanya membaca session. Lepas kunci berkas session sekarang
		// supaya request lain dari user yang sama (tab lain, ketikan pencarian,
		// menu lain) tidak antre menunggu query laporan ini selesai.
		session_write_close();

		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');

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
			1 => 't3.nama_marketplace',
			2 => 't.created_at',
			3 => 't.created_at',
			4 => 't.noresi',
			5 => 't.status_pesanan',
			6 => 't4.nama_kurir',
			7 => 't.nomorpicklist',
			8 => 't.tanggal_bataskirim',
		);

		$data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

		$list_resi = $this->receipt_fcd->get_data_receipt_process_tab0($data, $start_date, $end_date);

		$total = $this->receipt_fcd->get_total_data_receipt_process_tab0($data, $start_date, $end_date);

		$i = $data['start'] + 1;
		$data = array();
		foreach ($list_resi->result() as $row) {
			// Tanggal dan jam scan resi diambil dari tblprintresi.tanggal_printresi
			$data[] = array(
				$i++ . '.',
				$row->nama_marketplace,
				date('Y-m-d', strtotime($row->tanggal_printresi)),
				date('H:i:s', strtotime($row->tanggal_printresi)),
				$row->noresi,
				$row->status_pesanan,
				$row->nama_kurir,
				$row->nomorpicklist,
				!empty($row->tanggal_bataskirim) ? date('Y-m-d H:i:s', strtotime($row->tanggal_bataskirim)) : '-',
			);
		}

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "grandTotal" => $total, // Grand total = total records untuk resi proses
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

	public function get_receipt_in_process_data_tab1()
	{
		session_write_close(); // lihat get_receipt_in_process_data_tab0()

		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');

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
			1 => 't3.nama_marketplace',
			2 => 't2.created_at',
			3 => 't2.created_at',
			4 => 't2.noresi',
			5 => 't2.status_pesanan',
			6 => 't4.nama_kurir',
			7 => 't2.nomorpicklist',
			8 => 't.tanggal_resiambilbarang',
			9 => 't.tanggal_resiambilbarang',
			10 => 't5.nama_pegawai',
			11 => 't2.tanggal_bataskirim',
		);

		$data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

		$list_resi = $this->receipt_fcd->get_data_receipt_process_tab1($data, $start_date, $end_date);

		$total = $this->receipt_fcd->get_total_data_receipt_process_tab1($data, $start_date, $end_date);

		$i = $data['start'] + 1;
		$data = array();
		foreach ($list_resi->result() as $row) {
			$data[] = array(
				$i++ . '.',
				$row->nama_marketplace,
				empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
				empty($row->tanggal_printresi) ? null : date('H:i:s', strtotime($row->tanggal_printresi)),
				$row->noresi,
				$row->status_pesanan,
				$row->nama_kurir,
				$row->nomorpicklist,
				empty($row->tanggal_resiambilbarang) ? null : date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
				empty($row->tanggal_resiambilbarang) ? null : date('H:i:s', strtotime($row->tanggal_resiambilbarang)),
				$row->picker,
				!empty($row->tanggal_bataskirim) ? date('Y-m-d H:i:s', strtotime($row->tanggal_bataskirim)) : '-', // Added Batas Kirim
			);
		}

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "grandTotal" => $total, // Grand total = total records untuk resi proses
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

	public function get_receipt_in_process_data_tab2()
	{
		session_write_close(); // lihat get_receipt_in_process_data_tab0()

		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');

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
			1 => 't4.nama_marketplace',
			2 => 't2.created_at',
			3 => 't2.created_at',
			4 => 't2.noresi',
			5 => 't2.status_pesanan',
			6 => 't5.nama_kurir',
			7 => 't2.nomorpicklist',
			8 => 't3.tanggal_resiambilbarang',
			9 => 't3.tanggal_resiambilbarang',
			10 => 't6.nama_pegawai',
			11 => 't7.name',
			12 => 't2.tanggal_bataskirim',
		);

		$data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

		$list_resi = $this->receipt_fcd->get_data_receipt_process_tab2($data, $start_date, $end_date);

		$total = $this->receipt_fcd->get_total_data_receipt_process_tab2($data, $start_date, $end_date);

		$i = $data['start'] + 1;
		$data = array();
		foreach ($list_resi->result() as $row) {
			$data[] = array(
				$i++ . '.',
				$row->nama_marketplace,
				empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
				empty($row->tanggal_printresi) ? null : date('H:i:s', strtotime($row->tanggal_printresi)),
				$row->noresi,
				$row->status_pesanan,
				$row->nama_kurir,
				$row->nomorpicklist,
				empty($row->tanggal_resiambilbarang) ? null : date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
				empty($row->tanggal_resiambilbarang) ? null : date('H:i:s', strtotime($row->tanggal_resiambilbarang)),
				$row->picker,
				$row->packer,
				!empty($row->tanggal_bataskirim) ? date('Y-m-d H:i:s', strtotime($row->tanggal_bataskirim)) : '-', // Added Batas Kirim
			);
		}

		$output = array(
			"draw" => $draw,
			"recordsTotal" => $total,
			"recordsFiltered" => $total,
			"grandTotal" => $total, // Grand total = total records untuk resi proses
			"data" => $data
		);
		echo json_encode($output);
		exit();
    }

	public function export_to_excel_receipt_in_process_tab0()
	{
		ini_set('memory_limit', '-1');
		$reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
		if ($this->input->method() == 'post') {
			$reportrange = $this->input->post('reportrange');
		}

		$start_date = explode(" - ", $reportrange)[0];
		$end_date = explode(" - ", $reportrange)[1];

		$data['reportrange'] = $reportrange;
		$data['list_data'] = $this->receipt_fcd->get_data_receipt_process_tab0([], $start_date, $end_date)->result_array();

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Resi_Belum_Pick.xls");

		$this->load->view('template_report/receipt_in_process_report_tab0', $data);
	}

	public function export_to_excel_receipt_in_process_tab1()
	{
		ini_set('memory_limit', '-1');
		$reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
		if ($this->input->method() == 'post') {
			$reportrange = $this->input->post('reportrange');
		}

		$start_date = explode(" - ", $reportrange)[0];
		$end_date = explode(" - ", $reportrange)[1];

		$data['reportrange'] = $reportrange;
		$data['list_data'] = $this->receipt_fcd->get_data_receipt_process_tab1([], $start_date, $end_date)->result_array();

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Resi_Belum_Pick.xls");

		$this->load->view('template_report/receipt_in_process_report_tab1', $data);
	}

	public function export_to_excel_receipt_in_process_tab2()
	{
		ini_set('memory_limit', '-1');
		$reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
		if ($this->input->method() == 'post') {
			$reportrange = $this->input->post('reportrange');
		}

		$start_date = explode(" - ", $reportrange)[0];
		$end_date = explode(" - ", $reportrange)[1];

		$data['reportrange'] = $reportrange;
		$data['list_data'] = $this->receipt_fcd->get_data_receipt_process_tab2([], $start_date, $end_date)->result_array();

		header("Content-type: application/vnd-ms-excel");
		header("Content-Disposition: attachment; filename=Laporan_Resi_Belum_Pick.xls");

		$this->load->view('template_report/receipt_in_process_report_tab2', $data);
	}

	public function delete($id_printresi)
	{
		$save = $this->receipt_fcd->destroy($id_printresi, $this->data['user']['id_user']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_REMOVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

		$this->show_index();
	}

    public function daily_receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['header'] = $this->receipt_fcd->get_header_daily_report($start_date, $end_date)->row_array();
        

        $this->show($data);
    }

    public function get_daily_receipt_report_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            1 => 'f.nama_marketplace',
            2 => 'a.toko',
            3 => 'e.nama_kurir',
            4 => 'a.noresi',
            5 => 'a.status_pesanan',
            6 => 'a.nomorpicklist',
            7 => 'a.created_at',
            8 => 'a.created_at',
            9 => 't1.nama_pegawai',
            10 => 'b.tanggal_resiambilbarang',
            11 => 'b.tanggal_resiambilbarang',
            12 => 't2.nama_pegawai',
            13 => null, // sp2.status_name - tidak bisa di-search karena subquery
            14 => 'c.tanggal_packing',
            15 => 'c.tanggal_packing',
            16 => 't3.name',
            17 => null, // sp3.status_name - tidak bisa di-search karena subquery
            18 => 'd.tanggal_resikeluar',
            19 => 'd.tanggal_resikeluar',
            20 => 't4.nama_pegawai',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_daily_report($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_daily_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->nama_marketplace,
                $row->nama_toko,
                $row->nama_kurir,
                $row->noresi,
                $row->status_pesanan,
                $row->nomorpicklist,
                empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_printresi) ? null : date('H:i:s', strtotime($row->tanggal_printresi)),
                $row->admin_scan,
                empty($row->tanggal_resiambilbarang) ? null: date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
                empty($row->tanggal_resiambilbarang) ? null: date('H:i:s', strtotime($row->tanggal_resiambilbarang)),
                $row->admin_picker,
                !empty($row->picker_status) ? $row->picker_status : '-',
                empty($row->tanggal_packing) ? null : date('Y-m-d', strtotime($row->tanggal_packing)),
                empty($row->tanggal_packing) ? null : date('H:i:s', strtotime($row->tanggal_packing)),
                $row->admin_packer,
                !empty($row->packer_status) ? $row->packer_status : '-',
                empty($row->tanggal_resikeluar) ? null : date('Y-m-d', strtotime($row->tanggal_resikeluar)),
                empty($row->tanggal_resikeluar) ? null : date('H:i:s', strtotime($row->tanggal_resikeluar)),
                $row->admin_ho,
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

    public function export_to_excel_daily_receipt_report()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['header'] = $this->receipt_fcd->get_header_daily_report($start_date, $end_date)->row_array();
        $data['list_data'] = $this->receipt_fcd->get_data_daily_report([], $start_date, $end_date)->result_array();
        

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Harian.xls");

        $this->load->view('template_report/daily_receipt_report', $data);
    }

    public function per_day_receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        if ($this->input->method() == 'post') {
            $data['reportrange'] = $this->input->post('reportrange');
        }

        $this->show($data);
    }

    public function get_per_day_receipt_report_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            2 => 't5.nama_marketplace',
            3 => 't6.nama_kurir',
            4 => 't.nomorpicklist',
            5 => 't.created_at',
            6 => 't.created_at',
            7 => 't2.tanggal_resiambilbarang',
            8 => 't2.tanggal_resiambilbarang',
            9 => 't3.tanggal_packing',
            10 => 't3.tanggal_packing',
            11 => 't4.tanggal_resikeluar',
            12 => 't4.tanggal_resikeluar',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_per_day_report($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_per_day_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->nama_marketplace,
                $row->nama_kurir,
                $row->nomorpicklist,
                empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_printresi) ? null : date('H:i', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_resiambilbarang) ? null : date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
                empty($row->tanggal_resiambilbarang) ? null : date('H:i', strtotime($row->tanggal_resiambilbarang)),
                empty($row->tanggal_packing) ? null : date('Y-m-d', strtotime($row->tanggal_packing)),
                empty($row->tanggal_packing) ? null : date('H:i', strtotime($row->tanggal_packing)),
                empty($row->tanggal_resikeluar) ? null : date('Y-m-d', strtotime($row->tanggal_resikeluar)),
                empty($row->tanggal_resikeluar) ? null : date('H:i', strtotime($row->tanggal_resikeluar)),
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

    public function export_to_excel_per_day_receipt_report()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->receipt_fcd->get_data_per_day_report([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Per_Hari.xls");

        $this->load->view('template_report/per_day_receipt_report', $data);
    }

    public function receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_receipt_report_data_tab0()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            1 => 't.created_at',
            2 => 't.created_at',
            3 => 't3.nama_marketplace',
            4 => 't.nomorpicklist',
            5 => 't4.nama_kurir',
            6 => 't.noresi',
            7 => 't6.tanggal_resikeluar',
            8 => 't6.tanggal_resikeluar',
            9 => 't7.nama_pegawai',
            10 => 't8.nama_pegawai',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_receipt_tab0($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_receipt_tab0($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_printresi) ? null : date('H:i:s', strtotime($row->tanggal_printresi)),
                $row->nama_marketplace,
                $row->nomorpicklist,
                $row->nama_kurir,
                $row->noresi,
                empty($row->tanggal_resikeluar) ? null : date('Y-m-d', strtotime($row->tanggal_resikeluar)),
                empty($row->tanggal_resikeluar) ? null : date('H:i:s', strtotime($row->tanggal_resikeluar)),
                $row->picker,
                $row->packer,
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

    public function get_receipt_report_data_tab1()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            1 => 'date(t.created_at)',
            2 => 'nomorpicklist',
            3 => 'total',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_receipt_tab1($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_receipt_tab1($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                date('Y-m-d', strtotime($row->tanggal_printresi)),
                $row->nomorpicklist,
                $row->total,
            );
        }

        $grandTotal = $this->receipt_fcd->get_grand_total_data_receipt_tab1($data, $start_date, $end_date);

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data,
            "grandTotal" => $grandTotal,
        );
        echo json_encode($output);
        exit();
    }

    public function export_to_excel_receipt_report_tab0()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->receipt_fcd->get_data_receipt_tab0([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Total_Resi.xls");

        $this->load->view('template_report/receipt_report_tab0', $data);
    }

    public function export_to_excel_receipt_report_tab1()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->receipt_fcd->get_data_receipt_tab1([], $start_date, $end_date)->result_array();
        $data['grand_total'] = $this->receipt_fcd->get_grand_total_data_receipt_tab1($data, $start_date, $end_date);

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Total_Picklist.xls");

        $this->load->view('template_report/receipt_report_tab1', $data);
    }

    public function shipped_receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        $this->show($data);
    }

    public function get_shipped_receipt_report_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            1 => 't2.tanggal_resikeluar',
            2 => 't2.tanggal_resikeluar',
            3 => 't.noresi',
            3 => 't3.nama_kurir',
            4 => 't2.tanggal_cetak',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_shipped_report($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_shipped_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                empty($row->tanggal_resikeluar) ? null : date('Y-m-d', strtotime($row->tanggal_resikeluar)),
                empty($row->tanggal_resikeluar) ? null : date('H:i', strtotime($row->tanggal_resikeluar)),
                $row->noresi,
                $row->nama_kurir,
                $row->tanggal_cetak,
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

    public function export_to_excel_shipped_receipt_report()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->receipt_fcd->get_data_shipped_report([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Dikirim.xls");

        $this->load->view('template_report/shipped_receipt_report', $data);
    }

    public function shipping_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');

            $start_date = explode(" - ", $reportrange)[0];
            $end_date = explode(" - ", $reportrange)[1];

            $data['list_data']     = $this->receipt_fcd->get_data_shipping_report($start_date, $end_date)->result_array();
            $data['grand_total']   = $this->receipt_fcd->get_grand_total_data_shipping_report($start_date, $end_date);
            $data['detail_data']   = $this->receipt_fcd->get_shipping_report_detail($start_date, $end_date);
            $data['cat_totals']    = $this->receipt_fcd->get_shipping_report_category_totals($start_date, $end_date);
        }

        $data['reportrange'] = $reportrange;

        $this->show($data);
    }

    public function export_to_excel_shipping_report()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date   = explode(" - ", $reportrange)[1];

        $data['reportrange']  = $reportrange;
        $data['list_data']    = $this->receipt_fcd->get_data_shipping_report($start_date, $end_date)->result_array();
        $data['grand_total']  = $this->receipt_fcd->get_grand_total_data_shipping_report($start_date, $end_date);
        $data['detail_data']  = $this->receipt_fcd->get_shipping_report_detail($start_date, $end_date);
        $data['cat_totals']   = $this->receipt_fcd->get_shipping_report_category_totals($start_date, $end_date);

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Total_Pengiriman_Paket_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/shipping_report', $data);
    }

    public function retur_receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        if ($this->input->method() == 'post') {
            $data['reportrange'] = $this->input->post('reportrange');
        }

        $this->show($data);
    }

    public function get_retur_receipt_report_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

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
            2 => 't3.nama_marketplace',
            3 => 't4.nama_kurir',
            4 => 't2.nomorpicklist',
            5 => 't2.tanggal_printresi',
            6 => 't2.tanggal_printresi',
            7 => 't.tanggal_resiretur',
            8 => 't.tanggal_resiretur',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data_retur_receipt_report($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_retur_receipt_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->nama_marketplace,
                $row->nama_toko,
                $row->nama_kurir,
                $row->nomorpicklist,
                empty($row->tanggal_printresi) ? null : date('Y-m-d', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_printresi) ? null : date('H:i', strtotime($row->tanggal_printresi)),
                empty($row->tanggal_resiretur) ? null : date('Y-m-d', strtotime($row->tanggal_resiretur)),
                empty($row->tanggal_resiretur) ? null : date('H:i', strtotime($row->tanggal_resiretur))
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

    public function export_to_excel_retur_receipt_report()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->receipt_fcd->get_data_retur_receipt_report([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Total_Resi_Retur.xls");

        $this->load->view('template_report/retur_receipt_report', $data);
    }

    // ==================== NEW RETUR REPORT METHODS ====================
    
    /**
     * Get data for Terima Retur tab (with SKU details)
     */
    public function get_terima_retur_report_data()
    {
        $this->load->model('retur_fcd');
        
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
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
            0 => 'dp.no_pesanan',
            1 => 'pr.noresi',
            2 => 'mp.nama_marketplace',
            3 => 'pr.toko',
            4 => 'kr.nama_kurir',
            5 => 'tr.tanggal_resiretur',
            6 => 'tr.tanggal_resiretur',
            7 => 'dp.sku',
            8 => 'dp.jumlah',
            9 => 'tr.status_retur',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->retur_fcd->get_terima_retur_with_details($data, $start_date, $end_date);
        $total = $this->retur_fcd->get_total_terima_retur_with_details($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $result = array();
        foreach ($list_resi->result() as $row) {
            $result[] = array(
                'no_pesanan' => $row->no_pesanan ?? '-',
                'noresi' => $row->noresi,
                'marketplace' => $row->nama_marketplace ?? '-',
                'nama_toko' => $row->nama_toko ?? '-', // Added nama_toko
                'kurir' => $row->nama_kurir ?? '-',
                'tanggal_terima' => empty($row->tanggal_resiretur) ? '-' : date('Y-m-d', strtotime($row->tanggal_resiretur)),
                'jam_terima' => empty($row->tanggal_resiretur) ? '-' : date('H:i:s', strtotime($row->tanggal_resiretur)),
                'sku' => $row->sku ?? '-',
                'quantity' => $row->jumlah ?? 0,
                'status_detail' => $row->status_retur ?? '-',
                'action' => '
                    <input type="checkbox" class="row-select row-select-terima" data-id="' . $row->id_resiretur . '" style="margin-right: 5px;">
                    <button class="btn btn-danger btn-xs btn-delete-retur" data-url="report/delete-terima-retur/' . $row->id_resiretur . '" data-type="terima"><i class="fa fa-trash"></i></button>
                '
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $result
        );
        echo json_encode($output);
        exit();
    }

    /**
     * Get data for Buka Retur tab (with SKU details)
     */
    public function get_buka_retur_report_data()
    {
        $this->load->model('retur_fcd');
        
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
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
            0 => 'dp.no_pesanan',
            1 => 'br.resi_buka',
            2 => 'mp.nama_marketplace',
            3 => 'pr.toko',
            4 => 'kr.nama_kurir',
            5 => 'br.tanggal_buka_retur',
            6 => 'br.tanggal_buka_retur',
            7 => 'dp.sku',
            8 => 'dp.jumlah',
            9 => 'br.status_detail_buka',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->retur_fcd->get_buka_retur_with_details($data, $start_date, $end_date);
        $total = $this->retur_fcd->get_total_buka_retur_with_details($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $result = array();
        foreach ($list_resi->result() as $row) {
            $result[] = array(
                'no_pesanan' => $row->no_pesanan ?? '-',
                'noresi' => $row->resi_buka ?? '-',
                'marketplace' => $row->nama_marketplace ?? '-',
                'nama_toko' => $row->nama_toko ?? '-',
                'kurir' => $row->nama_kurir ?? '-',
                'tanggal_buka' => empty($row->tanggal_buka_retur) ? '-' : date('Y-m-d', strtotime($row->tanggal_buka_retur)),
                'jam_buka' => empty($row->tanggal_buka_retur) ? '-' : date('H:i:s', strtotime($row->tanggal_buka_retur)),
                'sku' => $row->sku ?? '-',
                'quantity' => $row->jumlah ?? 0,
                'status_detail' => $row->status_detail_buka ?? '-',
                'action' => '
                    <input type="checkbox" class="row-select row-select-buka" data-id="' . $row->id_bukaretur . '" style="margin-right: 5px;">
                    <button class="btn btn-danger btn-xs btn-delete-retur" data-url="report/delete-buka-retur/' . $row->id_bukaretur . '" data-type="buka"><i class="fa fa-trash"></i></button>
                '
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $result
        );
        echo json_encode($output);
        exit();
    }

    /**
     * Export Excel for Terima Retur
     */
    public function export_to_excel_terima_retur_report()
    {
        $this->load->model('retur_fcd');
        ini_set('memory_limit', '-1');
        
        $reportrange = $this->input->get('reportrange');
        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->retur_fcd->get_terima_retur_with_details([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Terima_Retur_" . date('YmdHis') . ".xls");

        $this->load->view('template_report/terima_retur_report', $data);
    }

    /**
     * Export Excel for Buka Retur
     */
    public function export_to_excel_buka_retur_report()
    {
        $this->load->model('retur_fcd');
        ini_set('memory_limit', '-1');
        
        $reportrange = $this->input->get('reportrange');
        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        $data['list_data'] = $this->retur_fcd->get_buka_retur_with_details([], $start_date, $end_date)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Buka_Retur_" . date('YmdHis') . ".xls");

        $this->load->view('template_report/buka_retur_report', $data);
    }

    /**
     * Delete Terima Retur record
     */
    public function delete_terima_retur($id_resiretur)
    {
        $this->load->model('retur_fcd');
        
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, 'Invalid request method');
        }

        $result = $this->retur_fcd->destroy($id_resiretur);

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(200, 'Data berhasil dihapus');
        } else {
            $this->make_ajax_response(400, 'Gagal menghapus data');
        }
    }

    /**
     * Delete Buka Retur record
     */
    public function delete_buka_retur($id_bukaretur)
    {
        $this->load->model('retur_fcd');
        
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, 'Invalid request method');
        }

        $result = $this->retur_fcd->destroy_buka_retur($id_bukaretur);

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(200, 'Data berhasil dihapus');
        } else {
            $this->make_ajax_response(400, 'Gagal menghapus data');
        }
    }

    /**
     * Batch Delete Retur records
     */
    public function batch_delete_retur()
    {
        $this->load->model('retur_fcd');

        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, 'Invalid request method');
        }

        $ids = $this->input->post('ids');
        $type = $this->input->post('type'); // 'terima' or 'buka'

        if (empty($ids) || !is_array($ids)) {
            $this->make_ajax_response(400, 'Tidak ada data yang dipilih');
        }

        if ($type == 'terima') {
            $result = $this->retur_fcd->batch_destroy($ids);
        } else if ($type == 'buka') {
            $result = $this->retur_fcd->batch_destroy_buka_retur($ids);
        } else {
            $this->make_ajax_response(400, 'Tipe penghapusan tidak valid');
        }

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(200, $result['affected_rows'] . ' data berhasil dihapus');
        } else {
            $this->make_ajax_response(400, 'Gagal menghapus data atau data sudah tidak ada');
        }
    }

    public function production_team_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_production_team_report_data_tab0()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $data['valid_columns'] = array(
            0 => ['searchable' => true, 'col' => 't2.nama_pegawai',],
            1 => ['searchable' => true, 'col' => 'date(b.tanggal_resiambilbarang)',],
            2 => ['searchable' => false, 'col' => 'count(1)',],
        );

        $data['order'] = $order;

        $list_resi = $this->receipt_fcd->get_data_production_team_tab0($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_production_team_tab0($data, $start_date, $end_date);

        $data = array();
        $grand_total = 0;
        
        // Data DIPISAH per picker per hari PER STATUS
        foreach ($list_resi->result() as $row) {
            $grand_total += $row->total; // Hitung grand total dari jumlah per row
            
            $data[] = array(
                'id_picker'       => $row->id_picker,
                'pegawai'         => $row->pegawai,
                'role'            => strtolower(trim($row->role)),
                'tanggal'         => date('d-m-Y', strtotime($row->tanggal_resiambilbarang)),
                'tanggal_raw'     => date('Y-m-d', strtotime($row->tanggal_resiambilbarang)),
                'jam_mulai'       => empty($row->waktu_scan_picker) ? '-' : date('H:i:s', strtotime($row->waktu_scan_picker)),
                'jam_selesai'     => empty($row->waktu_scan_selesai) ? '-' : date('H:i:s', strtotime($row->waktu_scan_selesai)),
                'total'           => $row->total,
                'status_performa' => $row->status_performa ?: 'Tanpa Status'
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "grandTotal" => $grand_total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

    public function get_production_team_report_data_tab1()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $data['valid_columns'] = array(
            0 => ['searchable' => true, 'col' => 't3.name',],
            1 => ['searchable' => true, 'col' => 'date(c.tanggal_packing)',],
            2 => ['searchable' => false, 'col' => 'count(1)',],
        );

        $data['order'] = $order;

        $list_resi = $this->receipt_fcd->get_data_production_team_tab1($data, $start_date, $end_date);

        $total = $this->receipt_fcd->get_total_data_production_team_tab1($data, $start_date, $end_date);

        $data = array();
        $grand_total = 0;
        
        // Data sudah ter-group by status, tinggal tampilkan langsung
        foreach ($list_resi->result() as $row) {
            $grand_total += $row->total; // Hitung grand total dari jumlah per row
            
            $data[] = array(
                'id_packer'       => $row->packer_pegawai,
                'pegawai'         => $row->pegawai,
                'role'            => strtolower(trim($row->role)),
                'tanggal'         => date('d-m-Y', strtotime($row->tanggal_packing)),
                'tanggal_raw'     => date('Y-m-d', strtotime($row->tanggal_packing)),
                'jam_mulai'       => empty($row->waktu_scan_packer) ? '-' : date('H:i:s', strtotime($row->waktu_scan_packer)),
                'jam_selesai'     => empty($row->waktu_scan_selesai) ? '-' : date('H:i:s', strtotime($row->waktu_scan_selesai)),
                'total'           => $row->total,
                'status_performa' => $row->status_performa ?: 'Tanpa Status'
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "grandTotal" => $grand_total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

    public function get_production_team_report_data_tab2()
    {
        $start_date = $this->input->post('start_date');
        $end_date   = $this->input->post('end_date');

        $draw  = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start']  = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $data['valid_columns'] = array(
            0 => ['searchable' => true, 'col' => 't4.nama_pegawai',],
            1 => ['searchable' => true, 'col' => 'date(d.tanggal_resikeluar)',],
            2 => ['searchable' => false, 'col' => 'count(1)',],
        );

        $data['order'] = $order;

        $list_resi = $this->receipt_fcd->get_data_production_team_tab2($data, $start_date, $end_date);
        $total     = $this->receipt_fcd->get_total_data_production_team_tab2($data, $start_date, $end_date);

        $data_array = array();
        $grand_total = 0;
        
        foreach ($list_resi->result() as $row) {
            $grand_total += $row->total;
            
            $data_array[] = array(
                'pegawai'         => $row->pegawai,
                'role'            => strtolower(trim($row->role)),
                'tanggal'         => $row->waktu_scan_ho ?: $row->tanggal_resikeluar,
                'total'           => $row->total,
                'status_performa' => $row->status_performa ?: 'Tanpa Status'
            );
        }

        $output = array(
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "grandTotal"      => $grand_total,
            "data"            => $data_array
        );
        echo json_encode($output);
        exit();
    }

    public function export_to_excel_production_team_report_tab0()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        
        $data['list_inti'] = [];
        $data['list_perbantuan'] = [];
        $data['total_populasi_inti'] = $this->db->query("SELECT count(1) as total FROM tbluser JOIN tblhakakses on tblhakakses.id_hakakses = tbluser.hakakses WHERE tblhakakses.akses LIKE '%picker%' AND tbluser.isactive = 1")->row()->total;
        $data['total_masuk_inti'] = 0;
        $data['total_paket_inti'] = 0;
        $data['total_masuk_perbantuan'] = 0;
        $data['total_paket_perbantuan'] = 0;

        $grand_total = 0;
        $pickers = $this->receipt_fcd->get_data_production_team_tab0([], $start_date, $end_date)->result_array();
        
        $details = $this->receipt_fcd->get_production_team_report_details_batch($start_date, $end_date);
        $details_map = [];
        foreach ($details as $d) {
            $details_map[$d['id_picker']][$d['tanggal']] = $d;
        }

        $min_mulai_inti = null;
        $max_selesai_inti = null;
        $min_mulai_perbantuan = null;
        $max_selesai_perbantuan = null;

        foreach ($pickers as $picker) {
            $id_picker = $picker['id_picker'];
            $pegawai = $picker['pegawai'];
            $tanggal = $picker['tanggal_resiambilbarang'];
            $waktu_scan = $picker['waktu_scan_picker'];
            $waktu_scan_selesai = $picker['waktu_scan_selesai'] ?? '';
            $total = $picker['total'];
            $status_performa = $picker['status_performa'] ?? '';
            $role = strtolower(trim($picker['role'] ?? ''));
            $isInti = (strpos($role, 'picker') !== false);
            
            $det = isset($details_map[$id_picker][$tanggal]) ? $details_map[$id_picker][$tanggal] : [
                'sku_special' => 0,
                'resi_1_sku_sd_9' => 0,
                'resi_2_9_sku_sd_9' => 0,
                'resi_qty_banyak' => 0
            ];

            if ($isInti) {
                if ($waktu_scan) {
                    if ($min_mulai_inti === null || $waktu_scan < $min_mulai_inti) {
                        $min_mulai_inti = $waktu_scan;
                    }
                }
                if ($waktu_scan_selesai) {
                    if ($max_selesai_inti === null || $waktu_scan_selesai > $max_selesai_inti) {
                        $max_selesai_inti = $waktu_scan_selesai;
                    }
                }
                if (!isset($data['list_inti'][$pegawai])) {
                    $data['list_inti'][$pegawai] = [];
                    $data['total_masuk_inti']++;
                }
                $data['list_inti'][$pegawai][] = [
                    'tanggal' => $tanggal,
                    'jam_mulai' => $waktu_scan,
                    'jam_selesai' => $waktu_scan_selesai,
                    'total' => $total,
                    'sku_special' => $det['sku_special'],
                    'resi_1_sku_sd_9' => $det['resi_1_sku_sd_9'],
                    'resi_2_9_sku_sd_9' => $det['resi_2_9_sku_sd_9'],
                    'resi_qty_banyak' => $det['resi_qty_banyak'],
                    'status_performa' => $status_performa
                ];
                $data['total_paket_inti'] += $total;
            } else {
                if ($waktu_scan) {
                    if ($min_mulai_perbantuan === null || $waktu_scan < $min_mulai_perbantuan) {
                        $min_mulai_perbantuan = $waktu_scan;
                    }
                }
                if ($waktu_scan_selesai) {
                    if ($max_selesai_perbantuan === null || $waktu_scan_selesai > $max_selesai_perbantuan) {
                        $max_selesai_perbantuan = $waktu_scan_selesai;
                    }
                }
                if (!isset($data['list_perbantuan'][$pegawai])) {
                    $data['list_perbantuan'][$pegawai] = [];
                    $data['total_masuk_perbantuan']++;
                }
                $data['list_perbantuan'][$pegawai][] = [
                    'tanggal' => $tanggal,
                    'jam_mulai' => $waktu_scan,
                    'jam_selesai' => $waktu_scan_selesai,
                    'total' => $total,
                    'sku_special' => $det['sku_special'],
                    'resi_1_sku_sd_9' => $det['resi_1_sku_sd_9'],
                    'resi_2_9_sku_sd_9' => $det['resi_2_9_sku_sd_9'],
                    'resi_qty_banyak' => $det['resi_qty_banyak'],
                    'status_performa' => $status_performa
                ];
                $data['total_paket_perbantuan'] += $total;
            }
            $grand_total += $total;
        }
        
        $data['jam_mulai_inti'] = $min_mulai_inti ? date('H:i:s', strtotime($min_mulai_inti)) : '-';
        $data['jam_selesai_inti'] = $max_selesai_inti ? date('H:i:s', strtotime($max_selesai_inti)) : '-';
        $data['jam_mulai_perbantuan'] = $min_mulai_perbantuan ? date('H:i:s', strtotime($min_mulai_perbantuan)) : '-';
        $data['jam_selesai_perbantuan'] = $max_selesai_perbantuan ? date('H:i:s', strtotime($max_selesai_perbantuan)) : '-';
        $data['grand_total'] = $grand_total;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Produksi_Picker.xls");

        $this->load->view('template_report/production_team_report_tab0', $data);
    }

    public function export_to_excel_production_team_report_tab1()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        
        $data['list_inti'] = [];
        $data['list_perbantuan'] = [];
        $data['total_populasi_inti'] = $this->db->query("SELECT count(1) as total FROM tbluser JOIN tblhakakses on tblhakakses.id_hakakses = tbluser.hakakses WHERE tblhakakses.akses LIKE '%packer%' AND tbluser.isactive = 1")->row()->total;
        $data['total_masuk_inti'] = 0;
        $data['total_paket_inti'] = 0;
        $data['total_masuk_perbantuan'] = 0;
        $data['total_paket_perbantuan'] = 0;

        $grand_total = 0;
        $packers = $this->receipt_fcd->get_data_production_team_tab1([], $start_date, $end_date)->result_array();
        
        $details = $this->receipt_fcd->get_production_team_report_details_batch_tab1($start_date, $end_date);
        $details_map = [];
        foreach ($details as $d) {
            $details_map[$d['id_packer']][$d['tanggal']] = $d;
        }

        $min_mulai_inti = null;
        $max_selesai_inti = null;
        $min_mulai_perbantuan = null;
        $max_selesai_perbantuan = null;

        foreach ($packers as $packer) {
            $id_packer = $packer['packer_pegawai'];
            $pegawai = $packer['pegawai'];
            $tanggal = $packer['tanggal_packing'];
            $waktu_scan = $packer['waktu_scan_packer'];
            $waktu_scan_selesai = $packer['waktu_scan_selesai'] ?? '';
            $status = $packer['status_performa'] ?: '';
            $total = $packer['total'];
            $role = strtolower(trim($packer['role'] ?? ''));
            $isInti = (strpos($role, 'packer') !== false);
            
            $det = isset($details_map[$id_packer][$tanggal]) ? $details_map[$id_packer][$tanggal] : [
                'sku_special' => 0,
                'resi_1_sku_sd_9' => 0,
                'resi_2_9_sku_sd_9' => 0,
                'resi_qty_banyak' => 0
            ];

            if ($isInti) {
                if ($waktu_scan) {
                    if ($min_mulai_inti === null || $waktu_scan < $min_mulai_inti) {
                        $min_mulai_inti = $waktu_scan;
                    }
                }
                if ($waktu_scan_selesai) {
                    if ($max_selesai_inti === null || $waktu_scan_selesai > $max_selesai_inti) {
                        $max_selesai_inti = $waktu_scan_selesai;
                    }
                }
                if (!isset($data['list_inti'][$pegawai])) {
                    $data['list_inti'][$pegawai] = [];
                    $data['total_masuk_inti']++;
                }
                $data['list_inti'][$pegawai][] = [
                    'tanggal' => $tanggal,
                    'jam_mulai' => $waktu_scan,
                    'jam_selesai' => $waktu_scan_selesai,
                    'total' => $total,
                    'sku_special' => $det['sku_special'],
                    'resi_1_sku_sd_9' => $det['resi_1_sku_sd_9'],
                    'resi_2_9_sku_sd_9' => $det['resi_2_9_sku_sd_9'],
                    'resi_qty_banyak' => $det['resi_qty_banyak'],
                    'status_performa' => $status
                ];
                $data['total_paket_inti'] += $total;
            } else {
                if ($waktu_scan) {
                    if ($min_mulai_perbantuan === null || $waktu_scan < $min_mulai_perbantuan) {
                        $min_mulai_perbantuan = $waktu_scan;
                    }
                }
                if ($waktu_scan_selesai) {
                    if ($max_selesai_perbantuan === null || $waktu_scan_selesai > $max_selesai_perbantuan) {
                        $max_selesai_perbantuan = $waktu_scan_selesai;
                    }
                }
                if (!isset($data['list_perbantuan'][$pegawai])) {
                    $data['list_perbantuan'][$pegawai] = [];
                    $data['total_masuk_perbantuan']++;
                }
                $data['list_perbantuan'][$pegawai][] = [
                    'tanggal' => $tanggal,
                    'jam_mulai' => $waktu_scan,
                    'jam_selesai' => $waktu_scan_selesai,
                    'total' => $total,
                    'sku_special' => $det['sku_special'],
                    'resi_1_sku_sd_9' => $det['resi_1_sku_sd_9'],
                    'resi_2_9_sku_sd_9' => $det['resi_2_9_sku_sd_9'],
                    'resi_qty_banyak' => $det['resi_qty_banyak'],
                    'status_performa' => $status
                ];
                $data['total_paket_perbantuan'] += $total;
            }
            $grand_total += $total;
        }
        
        $data['jam_mulai_inti'] = $min_mulai_inti ? date('H:i:s', strtotime($min_mulai_inti)) : '-';
        $data['jam_selesai_inti'] = $max_selesai_inti ? date('H:i:s', strtotime($max_selesai_inti)) : '-';
        $data['jam_mulai_perbantuan'] = $min_mulai_perbantuan ? date('H:i:s', strtotime($min_mulai_perbantuan)) : '-';
        $data['jam_selesai_perbantuan'] = $max_selesai_perbantuan ? date('H:i:s', strtotime($max_selesai_perbantuan)) : '-';
        $data['grand_total'] = $grand_total;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Produksi_Packer.xls");

        $this->load->view('template_report/production_team_report_tab1', $data);
    }

    public function export_to_excel_production_team_report_tab2()
    {
        ini_set('memory_limit', '-1');
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $start_date = explode(" - ", $reportrange)[0];
        $end_date = explode(" - ", $reportrange)[1];

        $data['reportrange'] = $reportrange;
        
        $data['list_inti'] = [];
        $data['list_perbantuan'] = [];
        $data['total_populasi_inti'] = $this->db->query("SELECT count(1) as total FROM tbluser JOIN tblhakakses on tblhakakses.id_hakakses = tbluser.hakakses WHERE tblhakakses.akses IN ('ho', 'admin')")->row()->total;
        $data['total_masuk_inti'] = 0;
        $data['total_paket_inti'] = 0;
        $data['total_masuk_perbantuan'] = 0;
        $data['total_paket_perbantuan'] = 0;

        $grand_total = 0;
        $hos = $this->receipt_fcd->get_data_production_team_tab2([], $start_date, $end_date)->result_array();
        
        foreach ($hos as $ho) {
            $pegawai = $ho['pegawai'];
            $tanggal = $ho['tanggal_resikeluar'];
            $waktu_scan = $ho['waktu_scan_ho'];
            $total = $ho['total'];
            $status = $ho['status_performa'] ?? 'Tanpa Status';
            
            $roleName = strtolower(trim($ho['role'] ?? ''));
            $isInti = (strpos($roleName, 'ho') !== false || strpos($roleName, 'admin') !== false);
            
            if ($isInti) {
                if (!isset($data['list_inti'][$pegawai])) {
                    $data['list_inti'][$pegawai] = [];
                    $data['total_masuk_inti']++;
                }
                $data['list_inti'][$pegawai][] = [
                    'tanggal' => $waktu_scan ?: $tanggal,
                    'total' => $total,
                    'status_performa' => $status
                ];
                $data['total_paket_inti'] += $total;
            } else {
                if (!isset($data['list_perbantuan'][$pegawai])) {
                    $data['list_perbantuan'][$pegawai] = [];
                    $data['total_masuk_perbantuan']++;
                }
                $data['list_perbantuan'][$pegawai][] = [
                    'tanggal' => $waktu_scan ?: $tanggal,
                    'total' => $total,
                    'status_performa' => $status
                ];
                $data['total_paket_perbantuan'] += $total;
            }
            $grand_total += $total;
        }
        
        $data['grand_total'] = $grand_total;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Produksi_HO.xls");

        $this->load->view('template_report/production_team_report_tab2', $data);
    }
    
    private function get_picker_status($user_id, $tanggal_scan, $picker_status_map) {
        if (empty($user_id) || empty($tanggal_scan)) {
            return '';
        }
        
        $tanggal_only = date('Y-m-d', strtotime($tanggal_scan));
        $map_key = $user_id . '_' . $tanggal_only;
        $scan_time = strtotime($tanggal_scan);
        
        if (isset($picker_status_map[$map_key]) && is_array($picker_status_map[$map_key])) {
            // Cari status yang created <= waktu scan resi (paling terakhir)
            $last_status = null;
            $last_created = null;
            foreach ($picker_status_map[$map_key] as $status_data) {
                $created_time = strtotime($status_data['created']);
                if ($created_time <= $scan_time) {
                    if ($last_created === null || $created_time > $last_created) {
                        $last_status = $status_data['status'];
                        $last_created = $created_time;
                    }
                }
            }
            // Jika tidak ada status yang created <= waktu scan, return kosong
            // (bukan 'Normal', karena user belum punya status performa di waktu itu)
            return $last_status !== null ? $last_status : '';
        }
        
        return '';
    }
    

    private function get_packer_status($user_id, $tanggal_packing, $packer_status_map) {
        if (empty($user_id) || empty($tanggal_packing)) {
            return '';
        }
        
        $tanggal_only = date('Y-m-d', strtotime($tanggal_packing));
        $map_key = $user_id . '_' . $tanggal_only;
        $packing_time = strtotime($tanggal_packing);
        
        if (isset($packer_status_map[$map_key]) && is_array($packer_status_map[$map_key])) {
            // Cari status yang created <= waktu packing (paling terakhir)
            $last_status = null;
            $last_created = null;
            foreach ($packer_status_map[$map_key] as $status_data) {
                $created_time = strtotime($status_data['created']);
                if ($created_time <= $packing_time) {
                    if ($last_created === null || $created_time > $last_created) {
                        $last_status = $status_data['status'];
                        $last_created = $created_time;
                    }
                }
            }
            // Jika tidak ada status yang created <= waktu packing, return kosong
            return $last_status !== null ? $last_status : '';
        }
        
        return '';
    }

    public function kurangan_picker_processed()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        $this->show($data);
    }

    public function get_kurangan_picker_processed_data()
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
            3 => 'm.nama_marketplace',
            4 => 'pr.tanggal_printresi',
            5 => 'pr.tanggal_bataskirim',
            6 => 'dr.qty_kurang',
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']);
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Build query
        $this->db->from('tbldetailprintresi dr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');

        $this->db->where('dr.status_kurangan', 'Sudah Diproses');
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

        // Get total count
        $total = $this->db->count_all_results('', false);

        $this->db->select('
            dr.sku,
            dr.qty_kurang,
            dr.tanggal_scan_kurangan,
            pr.noresi,
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
                $item['nama_marketplace'] ?? '-',
                !empty($item['tanggal_printresi']) ? date('d/m/Y', strtotime($item['tanggal_printresi'])) : '-',
                !empty($item['tanggal_bataskirim']) ? date('d/m/Y', strtotime($item['tanggal_bataskirim'])) : '-',
                $item['qty_kurang'] ?? 0,
                'Sudah Diproses'
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

    public function export_excel_kurangan_picker_processed()
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

        $this->db->where('dr.status_kurangan', 'Sudah Diproses');
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) >= '$start_date'", null, false);
        $this->db->where("COALESCE(dr.tanggal_scan_kurangan, pr.tanggal_printresi) <= '$end_date'", null, false);

        $this->db->group_by('dr.sku');
        $this->db->order_by('jumlah_resi', 'DESC');

        $data['list_data'] = $this->db->get()->result_array();
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Kurangan_Picker_Processed_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/laporan_kurangan_picker_processed', $data);
    }

    public function preorder_receipt_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;

        $this->show($data, 'report/preorder_receipt_report');
    }

    public function get_preorder_receipt_report_data()
    {
        $reportrange = $this->input->post('reportrange');
        $start_date = null;
        $end_date = null;
        if (!empty($reportrange)) {
            $dates = explode(" - ", $reportrange);
            if (count($dates) == 2) {
                $start_date = trim($dates[0]);
                $end_date = trim($dates[1]);
            }
        }

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $search = $this->input->post('search');
        $data['search'] = $search ? $search['value'] : '';

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
            2 => 'dr.sku',
            3 => 'nama_barang',
            4 => 'dr.jumlah',
            5 => 'pr.tanggal_bataskirim',
        );

        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : null;

        $this->load->model('receipt_fcd');
        
        $list_data = $this->receipt_fcd->get_data_preorder_report($data, $start_date, $end_date);
        $total = $this->receipt_fcd->get_total_data_preorder_report($data, $start_date, $end_date);
        $grandTotalQty = $this->receipt_fcd->get_grand_total_qty_preorder_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $result_data = array();
        foreach ($list_data->result() as $row) {
            $result_data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->sku,
                $row->nama_barang,
                $row->jumlah,
                date('d/m/Y H:i:s', strtotime($row->tanggal_bataskirim))
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $result_data,
            "grandTotal" => number_format((float)$grandTotalQty, 0, ',', '.'),
            "totalResi" => number_format((float)$total, 0, ',', '.')
        );
        echo json_encode($output);
        exit();
    }

    public function export_to_excel_preorder_receipt_report()
    {
        ini_set('memory_limit', '-1');
        
        $reportrange = $this->input->method() == 'post'
            ? $this->input->post('reportrange')
            : date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $this->load->model('receipt_fcd');

        $data['list_data'] = $this->receipt_fcd->get_data_preorder_report(null, $start_date, $end_date)->result_array();
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Preorder_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/preorder_receipt_report_excel', $data);
    }

    public function sku_special_report()
    {
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;
        
        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        
        $this->load->model('receipt_fcd');
        // Panggil rekap untuk panel atas
        $data['summary'] = $this->receipt_fcd->get_sku_special_report_summary($start_date, $end_date);
        
        // Panggil rekap kategori untuk cards
        $data['sku_cat_totals'] = $this->receipt_fcd->get_sku_special_report_category_totals($start_date, $end_date);

        $this->show($data, 'report/sku_special_report');
    }

    public function get_sku_special_report_data()
    {
        // View mengirim start_date dan end_date secara terpisah ke DataTable Ajax
        $start_date = $this->input->post('start_date');
        $end_date   = $this->input->post('end_date');

        // Fallback: coba parse dari reportrange jika ada
        if (empty($start_date) || empty($end_date)) {
            $reportrange = $this->input->post('reportrange');
            if (!empty($reportrange)) {
                $dates = explode(" - ", $reportrange);
                if (count($dates) == 2) {
                    $start_date = trim($dates[0]);
                    $end_date   = trim($dates[1]);
                }
            }
        }

        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $search = $this->input->post('search');
        $data['search'] = $search ? $search['value'] : '';

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['valid_columns'] = array(
            0 => null,
            1 => 'pr.created_at',
            2 => 'pr.noresi',
            3 => 'dr.sku',
            4 => 'dr.jumlah',
            5 => 'm.nama_marketplace',
            6 => 'k.nama_kurir',
            7 => 'pr.status_pesanan',
        );

        $data['order'] = isset($data['valid_columns'][$col]) ? $data['valid_columns'][$col] : null;
        $data['dir'] = $dir;

        $this->load->model('receipt_fcd');
        
        $list_data = $this->receipt_fcd->get_data_sku_special_report($data, $start_date, $end_date);
        $total = $this->receipt_fcd->get_total_data_sku_special_report($data, $start_date, $end_date);

        $i = $data['start'] + 1;
        $result_data = array();
        foreach ($list_data->result() as $row) {
            $result_data[] = array(
                $i++ . '.',
                date('d/m/Y H:i:s', strtotime($row->created_at)),
                $row->noresi,
                $row->sku,
                $row->jumlah,
                $row->nama_marketplace,
                $row->nama_kurir,
                isset($row->kategori_resi) ? $row->kategori_resi : '-',
                $row->status_pesanan,
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

    public function export_to_excel_sku_special_report()
    {
        ini_set('memory_limit', '-1');
        
        $reportrange = $this->input->method() == 'post'
            ? $this->input->post('reportrange')
            : date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date   = trim($dates[1]);

        $this->load->model('receipt_fcd');

        $data['summary']    = $this->receipt_fcd->get_sku_special_report_summary($start_date, $end_date);
        $data['list_data']  = $this->receipt_fcd->get_data_sku_special_report(null, $start_date, $end_date)->result_array();
        $data['cat_totals'] = $this->receipt_fcd->get_shipping_report_category_totals($start_date, $end_date);
        $data['sku_cat_totals'] = $this->receipt_fcd->get_sku_special_report_category_totals($start_date, $end_date);
        $data['reportrange'] = $reportrange;
        $data['start_date'] = $start_date;
        $data['end_date']   = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_SKU_Special_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/sku_special_report_excel', $data);
    }

    public function resi_cancel_report()
    {
        $data['message'] = $this->session->flashdata('message');
        $data['title'] = 'LAPORAN RESI CANCEL';

        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
            $this->data['content'] = $this->load->view('report/resi_cancel_report', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_resi_cancel_report_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'];

        $this->load->model('receipt_fcd');
        $result = $this->receipt_fcd->get_resi_cancel_report_data($start_date, $end_date, $start, $length, $search);

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $result['total'],
            "recordsFiltered" => $result['filtered'],
            "data" => $result['data']
        );
        echo json_encode($output);
        exit();
    }

    public function export_excel_resi_cancel()
    {
        ini_set('memory_limit', '-1');
        
        $reportrange = $this->input->post('reportrange');
        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        }

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $this->load->model('receipt_fcd');
        $res = $this->receipt_fcd->get_resi_cancel_report_data($start_date, $end_date, 0, 1000000, '');

        $data['list_data'] = $res['data'];
        $data['reportrange'] = $reportrange;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Cancel_" . date('Y-m-d') . ".xls");

        $this->load->view('template_report/resi_cancel_report_excel', $data);
    }

    public function proses_barang_qc()
    {
        $data['title'] = 'Laporan Proses Barang QC';
        $this->show($data);
    }

    public function get_proses_barang_qc_data()
    {
        $reportrange = $this->input->post('reportrange');
        $status = $this->input->post('status');
        
        $filters = array();
        if ($reportrange) {
            $dates = explode(' - ', $reportrange);
            $filters['start_date'] = trim($dates[0]);
            $filters['end_date'] = trim($dates[1]);
        }
        $filters['status'] = $status;

        $this->load->model('qc_return_fcd');
        $list = $this->qc_return_fcd->get_proses_barang_qc($filters);
        
        $data = array();
        $no = 1;
        foreach ($list->result() as $row) {
            // Determine tracking status
            $tracking = '-';
            $no_penyesuaian = '-';
            $foto_bukti = '-';

            if ($row->status_sortir == 0) {
                $tracking = '<span class="label label-warning">Mencari Keputusan (Purchasing)</span>';
            } else {
                if ($row->reject_no_penyesuaian || $row->reject_foto) {
                    $tracking = '<span class="label label-danger">REJECT</span>';
                    $no_penyesuaian = $row->reject_no_penyesuaian ?: '-';
                    if($row->reject_foto) $foto_bukti = '<a href="'.$row->reject_foto.'" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-image"></i> Lihat Foto</a>';
                } elseif ($row->repair_status) {
                    $tracking = '<span class="label label-info">REPAIR ('.$row->repair_status.')</span>';
                    $no_penyesuaian = $row->repair_no_penyesuaian ?: '-';
                    if($row->repair_foto) $foto_bukti = '<a href="'.$row->repair_foto.'" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-image"></i> Lihat Foto</a>';
                } elseif ($row->status == 'GIVEAWAY' || $row->status == 'DIKIRIM KE GUDANG PURCHASING') {
                    $tracking = '<span class="label label-success">GIVEAWAY</span>';
                    $no_penyesuaian = $row->no_penyesuaian ?: '-';
                    if($row->foto_barang) $foto_bukti = '<a href="'.$row->foto_barang.'" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-image"></i> Lihat Foto</a>';
                } elseif ($row->status == 'BARANG_TIDAK_ADA') {
                    $tracking = '<span class="label label-default">TIDAK ADA</span>';
                } elseif ($row->status == 'REJECTED') {
                    $tracking = '<span class="label label-danger">TOLAK PENGEMBALIAN</span>';
                }
            }

            // Entry Time (Tgl Masuk)
            $waktu_masuk = !empty($row->created_at) ? date('Y-m-d H:i:s', strtotime($row->created_at)) : '-';
            
            // Processed Time (Tgl Di Proses)
            $waktu_proses = '-';
            if ($row->reject_at) {
                $waktu_proses = date('Y-m-d H:i:s', strtotime($row->reject_at));
            } elseif ($row->repair_at) {
                $waktu_proses = date('Y-m-d H:i:s', strtotime($row->repair_at));
            } elseif ($row->acc_at) {
                $waktu_proses = date('Y-m-d H:i:s', strtotime($row->acc_at));
            }

            // Keterangan
            $keterangan = '-';
            if (!empty($row->reject_keterangan)) {
                $keterangan = $row->reject_keterangan;
            } elseif (!empty($row->repair_keterangan)) {
                $keterangan = $row->repair_keterangan;
            } elseif (!empty($row->keterangan_reject)) {
                $keterangan = $row->keterangan_reject;
            }

            $data[] = array(
                $no++,
                $row->tanggal,
                $waktu_masuk,
                $waktu_proses,
                $row->sku,
                $row->qty,
                $row->no_rak,
                $row->kondisi,
                $row->status,
                $tracking,
                $keterangan,
                $no_penyesuaian,
                $foto_bukti,
                $row->acc_name ?? '-'
            );
        }

        echo json_encode(array("data" => $data));
    }

    public function get_picker_performance_detail_summary()
    {
        $id_picker = $this->input->post('id_picker');
        $tanggal = $this->input->post('tanggal');

        if (empty($id_picker) || empty($tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $summary = $this->receipt_fcd->get_picker_performance_detail_summary($id_picker, $tanggal);
        echo json_encode(['status' => 'success', 'data' => $summary]);
        exit();
    }

    public function get_picker_sku_summary()
    {
        $id_picker = $this->input->post('id_picker');
        $tanggal = $this->input->post('tanggal');
        $category = $this->input->post('category');

        if (empty($id_picker) || empty($tanggal) || empty($category)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $summary = $this->receipt_fcd->get_picker_sku_summary_by_category($id_picker, $tanggal, $category);
        echo json_encode(['status' => 'success', 'data' => $summary]);
        exit();
    }

    public function get_picker_resi_list_by_sku()
    {
        $id_picker = $this->input->post('id_picker');
        $tanggal = $this->input->post('tanggal');
        $category = $this->input->post('category');
        $id_sku = $this->input->post('id_sku');

        if (empty($id_picker) || empty($tanggal) || empty($category) || empty($id_sku)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $list = $this->receipt_fcd->get_picker_resi_list_by_sku_and_category($id_picker, $tanggal, $category, $id_sku);
        
        $data = array();
        foreach ($list as $item) {
            $data[] = array(
                'noresi' => $item['noresi'],
                'tanggal_bataskirim' => !empty($item['tanggal_bataskirim']) ? date('d-m-Y H:i:s', strtotime($item['tanggal_bataskirim'])) : '-',
                'status_pesanan' => $item['status_pesanan'] ?: '-',
                'waktu_scan' => !empty($item['waktu_scan']) ? date('H:i:s', strtotime($item['waktu_scan'])) : '-',
                'distinct_skus' => $item['distinct_skus'],
                'total_qty' => $item['total_qty'],
                'detail_barang' => $item['detail_barang'] ?: '-'
            );
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
        exit();
    }

    public function export_picker_sku_summary()
    {
        $id_picker = $this->input->get('id_picker');
        $tanggal = $this->input->get('tanggal');
        $category = $this->input->get('category');

        if (empty($id_picker) || empty($tanggal) || empty($category)) {
            show_error('Parameter tidak lengkap', 400);
        }

        $picker_name = $this->db->select('name')->where('id_user', $id_picker)->get('tbluser')->row();
        $nama_picker = $picker_name ? $picker_name->name : 'Picker';

        $list = $this->receipt_fcd->get_picker_sku_summary_by_category($id_picker, $tanggal, $category);

        $data['list'] = $list;
        $data['nama_picker'] = $nama_picker;
        $data['tanggal'] = date('d-m-Y', strtotime($tanggal));
        
        $category_labels = [
            'sku_special' => 'RESI SPECIAL',
            'resi_1_sku_sd_9' => '1 SKU & QTY S D 9',
            'resi_2_9_sku_sd_9' => '2-9 SKU & QTY S D 9',
            'resi_qty_banyak' => 'QTY BANYAK (>9)'
        ];
        $data['category_label'] = isset($category_labels[$category]) ? $category_labels[$category] : 'Detail';

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Rangkuman_SKU_" . str_replace(' ', '_', $data['category_label']) . "_" . str_replace(' ', '_', $nama_picker) . "_" . $tanggal . ".xls");

        $this->load->view('template_report/picker_sku_summary_excel', $data);
    }

    public function export_picker_resi_list_by_sku()
    {
        $id_picker = $this->input->get('id_picker');
        $tanggal = $this->input->get('tanggal');
        $category = $this->input->get('category');
        $id_sku = $this->input->get('id_sku');

        if (empty($id_picker) || empty($tanggal) || empty($category) || empty($id_sku)) {
            show_error('Parameter tidak lengkap', 400);
        }

        $picker_name = $this->db->select('name')->where('id_user', $id_picker)->get('tbluser')->row();
        $nama_picker = $picker_name ? $picker_name->name : 'Picker';

        $list = $this->receipt_fcd->get_picker_resi_list_by_sku_and_category($id_picker, $tanggal, $category, $id_sku);
        
        $data['list'] = $list;
        $data['nama_picker'] = $nama_picker;
        $data['nama_sku'] = $id_sku; // Gunakan SKU langsung
        $data['tanggal'] = date('d-m-Y', strtotime($tanggal));
        
        $category_labels = [
            'sku_special' => 'RESI SPECIAL',
            'resi_1_sku_sd_9' => '1 SKU & QTY S D 9',
            'resi_2_9_sku_sd_9' => '2-9 SKU & QTY S D 9',
            'resi_qty_banyak' => 'QTY BANYAK (>9)'
        ];
        $data['category_label'] = isset($category_labels[$category]) ? $category_labels[$category] : 'Detail';

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_" . str_replace(' ', '_', $nama_sku) . "_" . str_replace(' ', '_', $nama_picker) . "_" . $tanggal . ".xls");

        $this->load->view('template_report/picker_detail_resi_excel', $data);
    }

    public function get_packer_performance_detail_summary()
    {
        $id_packer = $this->input->post('id_packer');
        $tanggal = $this->input->post('tanggal');

        if (empty($id_packer) || empty($tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $summary = $this->receipt_fcd->get_packer_performance_detail_summary($id_packer, $tanggal);
        echo json_encode(['status' => 'success', 'data' => $summary]);
        exit();
    }

    public function get_packer_sku_summary()
    {
        $id_packer = $this->input->post('id_packer');
        $tanggal = $this->input->post('tanggal');
        $category = $this->input->post('category');

        if (empty($id_packer) || empty($tanggal) || empty($category)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $summary = $this->receipt_fcd->get_packer_sku_summary_by_category($id_packer, $tanggal, $category);
        echo json_encode(['status' => 'success', 'data' => $summary]);
        exit();
    }

    public function get_packer_resi_list_by_sku()
    {
        $id_packer = $this->input->post('id_packer');
        $tanggal = $this->input->post('tanggal');
        $category = $this->input->post('category');
        $id_sku = $this->input->post('id_sku');

        if (empty($id_packer) || empty($tanggal) || empty($category) || empty($id_sku)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            exit();
        }

        $list = $this->receipt_fcd->get_packer_resi_list_by_sku_and_category($id_packer, $tanggal, $category, $id_sku);
        
        $data = array();
        foreach ($list as $item) {
            $data[] = array(
                'noresi' => $item['noresi'],
                'tanggal_bataskirim' => !empty($item['tanggal_bataskirim']) ? date('d-m-Y H:i:s', strtotime($item['tanggal_bataskirim'])) : '-',
                'status_pesanan' => $item['status_pesanan'] ?: '-',
                'waktu_scan' => !empty($item['waktu_scan']) ? date('H:i:s', strtotime($item['waktu_scan'])) : '-',
                'distinct_skus' => $item['distinct_skus'],
                'total_qty' => $item['total_qty'],
                'detail_barang' => $item['detail_barang'] ?: '-'
            );
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
        exit();
    }

    public function export_packer_sku_summary()
    {
        $id_packer = $this->input->get('id_packer');
        $tanggal = $this->input->get('tanggal');
        $category = $this->input->get('category');

        if (empty($id_packer) || empty($tanggal) || empty($category)) {
            show_error('Parameter tidak lengkap', 400);
        }

        $packer_name = $this->db->select('name')->where('id_user', $id_packer)->get('tbluser')->row();
        $nama_packer = $packer_name ? $packer_name->name : 'Packer';

        $list = $this->receipt_fcd->get_packer_sku_summary_by_category($id_packer, $tanggal, $category);

        $data['list'] = $list;
        $data['nama_picker'] = $nama_packer; // We can reuse the same Excel view since it uses $nama_picker
        $data['tanggal'] = date('d-m-Y', strtotime($tanggal));
        
        $category_labels = [
            'sku_special' => 'RESI SPECIAL',
            'resi_1_sku_sd_9' => '1 SKU & QTY S D 9',
            'resi_2_9_sku_sd_9' => '2-9 SKU & QTY S D 9',
            'resi_qty_banyak' => 'QTY BANYAK (>9)'
        ];
        $data['category_label'] = isset($category_labels[$category]) ? $category_labels[$category] : 'Detail';

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Rangkuman_SKU_Packer_" . str_replace(' ', '_', $data['category_label']) . "_" . str_replace(' ', '_', $nama_packer) . "_" . $tanggal . ".xls");

        $this->load->view('template_report/picker_sku_summary_excel', $data);
    }

    public function export_packer_resi_list_by_sku()
    {
        $id_packer = $this->input->get('id_packer');
        $tanggal = $this->input->get('tanggal');
        $category = $this->input->get('category');
        $id_sku = $this->input->get('id_sku');

        if (empty($id_packer) || empty($tanggal) || empty($category) || empty($id_sku)) {
            show_error('Parameter tidak lengkap', 400);
        }

        $packer_name = $this->db->select('name')->where('id_user', $id_packer)->get('tbluser')->row();
        $nama_packer = $packer_name ? $packer_name->name : 'Packer';

        $list = $this->receipt_fcd->get_packer_resi_list_by_sku_and_category($id_packer, $tanggal, $category, $id_sku);
        
        $data['list'] = $list;
        $data['nama_picker'] = $nama_packer; // Reuse the same Excel view
        $data['nama_sku'] = $id_sku;
        $data['tanggal'] = date('d-m-Y', strtotime($tanggal));
        
        $category_labels = [
            'sku_special' => 'RESI SPECIAL',
            'resi_1_sku_sd_9' => '1 SKU & QTY S D 9',
            'resi_2_9_sku_sd_9' => '2-9 SKU & QTY S D 9',
            'resi_qty_banyak' => 'QTY BANYAK (>9)'
        ];
        $data['category_label'] = isset($category_labels[$category]) ? $category_labels[$category] : 'Detail';

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Resi_Packer_" . str_replace(' ', '_', $id_sku) . "_" . str_replace(' ', '_', $nama_packer) . "_" . $tanggal . ".xls");

        $this->load->view('template_report/picker_detail_resi_excel', $data);
    }
}
