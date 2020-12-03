<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verification extends MY_Controller {

	function __construct()
    {
        parent::__construct();

        $this->load->model('verification_fcd');
    }

	public function index()
	{
        if ($this->input->method() == 'post') {
            $this->save_search_session(array(
                'verification_status_id' => $this->input->post('verification_status_id'),
            ));
        }

        $data['message'] = $this->session->flashdata('message');

		$this->show($data);
    }

    public function get_data()
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
            0 => 'user.username',
            1 => 'concat(user.firstname, \' \', user.lastname)',
            2 => 'user.email',
            3 => 'lnkshr.name',
            4 => 'param.value',
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $search = $this->get_search_session();

        $total = $this->verification_fcd->total_data($data, $search);
        
        $list_verification = $this->verification_fcd->get_data($data, $search);

        $data = array();
        foreach($list_verification->result() as $row) {
            $btn_phone = '';
            $btn_email = '';

            if (!empty($row->phone_exist)) {
                $btn_phone = '<a href="tel:'.$row->phone.'" class="btn btn-info" title="Make a call"><i class="fa fa-phone"></i> </a> ';
            }

            $data[]= array(
                $row->username,
                $row->name,
                $row->email,
                $row->linkshare,
                $row->verification_status,
                $row->id_photo_exist ? '<i class="fa fa-check"></i>' : '',
                $row->phone_exist ? '<i class="fa fa-check"></i>' : '',
                '<div class="text-center">'.
                    '<a href="verification/edit/'.$row->id.'" class="btn btn-info link" title="Edit and view data"><i class="fa fa-edit"></i> </a> '.
                    $btn_phone.
                    '<a href="verification/preview_email/'.$row->id.'" class="btn btn-info link" title="Email"><i class="fa fa-envelope"></i> </a> '.
                '</div>'
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

    public function edit($user_id)
    {
        if (empty($user_id)) {
            redirect('404_override');
        }

        $this->load->model('master_param_fcd');
        
        $data['message'] = $this->session->flashdata('message');

        $data['user'] = $this->verification_fcd->get_user($user_id)->row_array();
        $data['verification'] = $this->verification_fcd->get_user_verification($data['user']['id'])->row_array();

        if (empty($user_id)) {
            redirect('404_override');
        }
        
        $this->show($data);
    }

    public function save()
    {
        if ($this->input->method() == 'get') {
            redirect('404_override');
		}
		
        $verification['id'] = $this->input->post('id');
        $verification['user_id'] = $this->input->post('user_id');
		$verification['name_verified'] = $this->input->post('name_verified');
		$verification['gender_verified'] = $this->input->post('gender_verified');
		$verification['phone_verified'] = $this->input->post('phone_verified');
		$verification['birthdate_verified'] = $this->input->post('birthdate_verified');
		$verification['noidentitas_verified'] = $this->input->post('noidentitas_verified');
        $verification['file_identitas_verified'] = $this->input->post('file_identitas_verified');

        $verification['verification_status_id'] = VERIFICATION_STATUS['VERIFIED'];
        foreach ($verification as $key => $value):
            if (strpos($key, 'verified') !== false) {
                if ($value == 0) {
                    $verification['verification_status_id'] = VERIFICATION_STATUS['UNVERIFIED'];
                    break;
                }
            }
        endforeach;

		$save = $this->verification_fcd->save($verification, $this->data['user']['id']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

        $this->show_index();
    }

    private function get_data_email($user_id)
    {
        $data['user'] = $this->verification_fcd->get_user($user_id)->row_array();
        
        $verification = $this->verification_fcd->get_user_verification($data['user']['id'])->row_array();

        if (empty($verification)) {
            redirect('404_override');
            exit();
        }

        $data['list_unverified'] = array();
        if (empty($verification['name_verified'])) {
            $data['list_unverified'][] = 'Name';
        }

        if (empty($verification['gender_verified'])) {
            $data['list_unverified'][] = 'Gender';
        }

        if (empty($verification['phone_verified'])) {
            $data['list_unverified'][] = 'Phone';
        }

        if (empty($verification['birthdate_verified'])) {
            $data['list_unverified'][] = 'Date of Birth';
        }

        if (empty($verification['noidentitas_verified'])) {
            $data['list_unverified'][] = 'ID No';
        }

        if (empty($verification['file_identitas_verified'])) {
            $data['list_unverified'][] = 'ID Card Photo';
        }

        return $data;
    }

    public function preview_email($user_id)
    {
        $data = $this->get_data_email($user_id);

        $data['ispreview'] = TRUE;

        $this->show($data);
    }

    public function send_email()
    {
        if ($this->input->method() == 'get') {
            redirect('404_override');
        }
        
        $user_id = $this->input->post('id');

        $data = $this->get_data_email($user_id);
        $data['ispreview'] = FALSE;

        /**
         * send email here
         */
		$content = $this->load->view('verification_preview_email', $data, true);

		$this->load->library("notif");
        $send = $this->notif->send(["email" => $data['user']['email'], "name" => $data['user']['firstname'].' '.$data['user']['lastname']], 'Sobat, lengkapi profilmu di Muniyo yuk!', $content);
        /**
         * send email ends
         */
        if ($send) {
            $this->set_message('Success', 'Succcess send email', 'information');
        } else {
            $this->set_message('Success', 'Failed to send email', 'information');
        }

        redirect('verification/edit/'.$user_id);
    }
}
