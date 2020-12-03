<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

	public function index()
	{
		$this->load->helper('menu_helper');

        $this->data['user'] = $this->session->userdata('user');
        $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
		$this->data['content'] = $this->load->view('welcome', null, TRUE);
		
		$this->load->view('main', $this->data);
	}

	public function page_notfound()
	{
		$this->load->view('page_notfound', null);
	}

    public function restricted()
    {
		$this->load->view('restricted', null);
    }

	public function logout()
	{
		$this->session->unset_userdata('user');

		redirect('login');
	}
}
