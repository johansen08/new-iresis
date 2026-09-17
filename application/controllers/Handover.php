<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Handover extends MY_Controller
{

	function __construct()
	{
		parent::__construct();

		$this->load->model('handover_fcd');
	}

	public function scan_handover()
	{
		$data['nama_komputer'] = $this->data['user']['nama_komputer'];
		$data['total_scan'] = $this->handover_fcd->get_total_scan_user($this->data['user']['id_pegawai'])->row()->total_scan;

		$this->show($data);
	}

	// request by ajax
	public function save_handover()
	{
		if ($this->input->method() == 'get') {
			$this->make_ajax_response(400, INVALID_REQUEST_METHOD);
		}

		$handover['noresi'] = $this->input->post('noresi');

		$save = $this->handover_fcd->save($handover, $this->data['user']);

		if (isset($save['error'])) {
			$this->make_ajax_response($save['code'], $save['message'], $save['data']);
		}

		if ($save['affected_rows'] > 0) {
			$this->make_ajax_response(201, SUCCESS_SAVE_DATA);
		}

		$this->make_ajax_response(200, NOTHING_TO_SAVE);
	}

    public function search_handover()
    {
        $this->show();
    }

    public function get_data_handover()
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
            3 => 't.tanggal_resikeluar',
            4 => 't.tanggal_resikeluar',
            5 => 't.sudah_cetak',
            5 => 't.tanggal_cetak',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->handover_fcd->get_data($data);

        $total = $this->handover_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                $row->pegawai,
                date('Y-m-d', strtotime($row->tanggal_resikeluar)),
                date('H:i:s', strtotime($row->tanggal_resikeluar)),
                $row->sudah_cetak,
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
}
