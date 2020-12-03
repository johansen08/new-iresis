<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_blast extends MY_Controller {

	function __construct()
    {
        parent::__construct();

        $this->load->model('email_blast_fcd');
    }

	public function index()
	{
		$data['message'] = $this->session->flashdata('message');
		$data['list_email_blast'] = $this->email_blast_fcd->get_email_blast()->result_array();

		$this->show($data);
    }

    public function edit($email_blast_id = null)
    {
        $data['action'] = 'Add new email blast';

		if (!empty($email_blast_id)) {
			$data['action'] = 'Edit email blast';
			$data['email_blast'] = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

			if (!empty($data['email_blast'])) {
				$data['email_blast']['body_mail'] = file_get_contents(APPPATH.'views/email_blast_template/'.$data['email_blast']['file_template']);
			}
		}

		$this->show($data);
	}

    public function save()
    {
		if ($this->input->method() == 'get') {
            redirect('404_override');
		}

		$email_blast['id'] = $this->input->post('id');
		$email_blast['code'] = $this->input->post('code');
		$email_blast['subject'] = $this->input->post('subject');
		// $email_blast['file_template'] = strtolower(str_replace(' ', '_', $email_blast['subject']).'.php');
		$email_blast['file_template'] = substr(md5(microtime()), rand(0, 26), 6).'.php';
				
		try {
			$file_location = APPPATH.'views/email_blast_template/'.$email_blast['file_template'];

			file_put_contents($file_location, $this->input->post('body_mail'));
		} catch (Exception $e) {
			$this->set_message('Warning', 'Unable to open file!', 'warning');
			$this->show_index();
		}

		$save = $this->email_blast_fcd->save($email_blast, $this->data['user']['id']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

        $this->show_index();
    }

    public function delete($email_blast_id)
    {
        $email_blast['id'] =  $email_blast_id;
		$email_blast['is_active'] = FALSE;

		$save = $this->email_blast_fcd->save($email_blast, $this->data['user']['id']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

        $this->show_index();
	}
	
	public function preview($email_blast_id)
	{
		$email_blast = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

		if (empty($email_blast)) {
			$this->set_message('Warning', DATA_NOT_FOUND, 'warning');
			$this->show_index();
		}

		$data['body_mail'] = file_get_contents(APPPATH.'views/email_blast_template/'.$email_blast['file_template']);

		$this->show($data);
	}

	public function recipients($email_blast_id)
	{
		$email_blast = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

		if (empty($email_blast)) {
			$this->set_message('Warning', DATA_NOT_FOUND, 'warning');
			$this->show_index();
		}

		$data['email_blast'] = $email_blast;
		$data['message'] = $this->session->flashdata('message');

		$this->show($data);
	}

	function recipients_init()
	{
		if ($this->input->method() == 'get') {
            redirect('404_override');
		}

		$email_blast['id'] = $this->input->post('email_blast_id');
		$email_blast['upload_type'] = $this->input->post('upload_type');

		if (empty($email_blast['upload_type'])) {
			$this->set_message('Warning', 'Upload type not found', 'warning');
			redirect($this->router->class.'/recipients/'.$email_blast['id']);
		}

		if ($email_blast['upload_type'] == SEND_EMAIL_PARTIAL) {
			$config['upload_path'] = 'uploads/tmp_doc/';
			$config['allowed_types'] = 'xlsx|xls|csv';
			$config['file_name'] = $email_blast['id'].'_'.time();

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('file_partial')) {
				$file = $this->upload->data();

				if (($handle = fopen('uploads/tmp_doc/'.$file['file_name'], 'r')) !== FALSE) {
					while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
						$email_blast['list_recipients'][] = array(
							'email_blast_id' => $email_blast['id'],
							'uid' => $data[0],
							'username' => $data[1],
							'firstname' => $data[2],
							'lastname' => $data[3],
							'email' => $data[4],
						);
					}
					fclose($handle);
				}

				fclose($csv);
			} else {
				$this->set_message('Warning', 'Error : '.$this->upload->display_errors(), 'warning');
				redirect($this->router->class.'/recipients/'.$email_blast['id']);
			}
		}

		$save = $this->email_blast_fcd->save_recipients($email_blast);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

		redirect($this->router->class.'/recipients/'.$email_blast['id']);
	}

	public function recipients_reset($email_blast_id)
	{
		$email_blast = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

		if (empty($email_blast)) {
			$this->set_message('Warning', DATA_NOT_FOUND, 'warning');
			$this->show_index();
		}
		
		$save = $this->email_blast_fcd->save_recipients($email_blast);

		redirect($this->router->class.'/recipients/'.$email_blast['id']);
	}

	public function get_data_recipients()
	{
		$draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');
        
        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
		$data['search'] = $this->input->post('search')['value'];
		$data['email_blast_id'] = $this->input->post('email_blast_id');

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
            0 => 'uid',
            1 => 'username',
            2 => 'firstname',
            3 => 'lastname',
			4 => 'email',
			5 => 'status',
			6 => 'sent_date',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $total = $this->email_blast_fcd->total_data_recipients($data);
        
        $list_recipients = $this->email_blast_fcd->get_data_recipients($data);

        $data = array();
        foreach($list_recipients->result() as $row) {
			$status = '';
			if (empty($row->status)) {
				$status = 'Unsent';
			} else if (intval($row->status) > 0) {
				$status = 'Success';
			} else {
				$status = 'Failed';
			}

            $data[]= array(
                $row->uid,
                $row->username,
                $row->firstname,
                $row->lastname,
				$row->email,
				$status,
				$row->sent_date,
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

	public function report($email_blast_id)
	{
		$email_blast = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

		if (empty($email_blast)) {
			$this->set_message('Warning', DATA_NOT_FOUND, 'warning');
			$this->show_index();
		}

		$data['email_blast'] = $email_blast;
		$data['email_blast_count'] = $this->email_blast_fcd->count_summary($email_blast);

		$this->show($data);
	}

	public function send($email_blast_id)
	{
		$email_blast = $this->email_blast_fcd->get_email_blast($email_blast_id)->row_array();

		if (empty($email_blast)) {
			$this->set_message('Warning', DATA_NOT_FOUND, 'warning');
			$this->show_index();
		}

		if (!empty($email_blast['status'])) {
			$this->set_message('Warning', 'Email is in progress, please wait', 'warning');
			$this->show_index();			
		}

		$list_recipients = $this->email_blast_fcd->get_recipients($email_blast)->result_array();

		if (empty($list_recipients)) {
			$this->set_message('Warning', RECIPIENTS_NOT_FOUND, 'warning');
			$this->show_index();
		}

		// update email blast status to be 'in progress' and publish date
		unset($email_blast['created_by_name']);
		$email_blast['status'] = EMAIL_BLAST_STATUS['IN_PROGRESS'];
		$email_blast['publish_date'] = date('Y-m-d H:i:s');
		$this->email_blast_fcd->save($email_blast, $this->data['user']['id']);

		// create file template email
		$temp_file = APPPATH.'views/email_blast_template/tmp_'.$email_blast['file_template'];

		$content = file_get_contents(APPPATH.'views/email_blast_template/'.$email_blast['file_template']);
		$content = str_replace('{{', '<?= $', $content);
		$content = str_replace('}}', '; ?>', $content);
		
		file_put_contents($temp_file, $content);

		// load bgprocess lib
		$this->load->library('bgprocess');

		$data = array();
		foreach ($list_recipients as $recipients):
			if ($recipients['status']) 
				continue;
			
			$data[] = $recipients;
			
			if (count($data) == 25) {
				$param['list_recipients'] = $data;
				$param['email_blast'] = $email_blast;

				$this->bgprocess->do_async(base_url().'bgprocess/send_email_blast', $param);

				// reset var data
				unset($data);
				$data = array();
			}
		endforeach;

		// do background process for remaining var data
		if (!empty($data)) {
			$param['list_recipients'] = $data;
			$param['email_blast'] = $email_blast;

			$this->bgprocess->do_async(base_url().'bgprocess/send_email_blast', $param);
		}

		$this->show_index();
	}
}
