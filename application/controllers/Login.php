<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        if ($this->session->userdata('user')) {
            redirect('welcome');
        }
    }

	public function index()
	{
		$data = array(
            'message' => $this->session->flashdata('message')
        );
        $this->load->view('login', $data);
    }
    
    public function auth()
    {
        $this->load->model('login_fcd');

        $username = $this->input->post('username');
        $password = md5($this->input->post('password'));

        $user = $this->login_fcd->auth($username, $password)->row_array();

        if (!empty($user)) {
            $this->load->model('access_fcd');
            $this->load->helper('menu_helper');
            
            $this->login_fcd->update_last_login($user['id']);

            $list_menu = $this->access_fcd->get_access_menu($user['roleid'])->result_array();

            $list_menu_tree = menu_to_tree($list_menu, $list_menu[0]);
            $html_menu_tree = tree_to_html_menu($list_menu_tree['child']);

            $sess_data['user'] = $user;
            $sess_data['list_menu'] = $list_menu;
            $sess_data['list_menu_tree'] = $list_menu_tree;
            $sess_data['html_menu_tree'] = $html_menu_tree;
            
            $this->session->set_userdata($sess_data);

            redirect('/');
        } else {
            $this->session->set_flashdata('message', LOGIN_FAILED);
            redirect('login');
        }
    }
}
