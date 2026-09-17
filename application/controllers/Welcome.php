<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();

		$this->load->model('receipt_fcd');
		$this->load->model('dashboard_fcd');
		$this->load->model('Notification');
	}

	public function index()
	{
		$this->load->helper('menu_helper');

		$this->data['user'] = $this->session->userdata('user');
		$this->data['nama_pk'] = $this->session->userdata('nama_pk');
		$this->data['status_performa'] = $this->session->userdata('status_performa');
		$this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
		
		// GET NOTIFICATION DATA
		$category = $this->get_notif_category($this->data['user']['hakakses']);
		$this->data['notif_count'] = $this->Notification->get_unread_count($category);
		$this->data['notif_list'] = $this->Notification->get_notifications($category, 5);
		$this->data['notif_category'] = $category;

		$this->data['content'] = $this->load->view('welcome', null, TRUE);

		$this->load->view('main', $this->data);
	}

	public function get_dashboard_data()
	{
		$data['simple'] = $this->dashboard_fcd->get_simple_dashboard_data();
		echo json_encode($data);
		exit();
	}

	public function get_dashboard_details()
	{
		$type = $this->input->get('type');
		$data = $this->dashboard_fcd->get_dashboard_details_data($type);
		echo json_encode($data);
		exit();
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

	// === NOTIFICATION HELPERS ===

	private function get_notif_category($role_id)
	{
		switch ($role_id) {
			case 1:
			case 2: return 'ADMIN';
			case 5: return 'TIM CS';
			case 6: return 'TIM RETUR';
			case 7: return 'TIM PURCHASING';
			case 8: return 'TIM ACCOUNTING';
			case 9: return 'TIM INBOUND';
			case 10: return 'TIM FINANCE';
			default: return 'GENERAL';
		}
	}

	public function get_notif_ajax()
	{
		$user = $this->session->userdata('user');
		if (!$user) {
			echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
			return;
		}

		$category = $this->get_notif_category($user['hakakses']);
		$data['count'] = $this->Notification->get_unread_count($category);
		$data['list'] = $this->Notification->get_notifications($category, 5);
		
		echo json_encode(['status' => 'success', 'data' => $data]);
	}

	public function mark_notif_read()
	{
		$id = $this->input->post('id');
		if ($id) {
			$this->Notification->mark_as_read($id);
			echo json_encode(['status' => 'success']);
		} else {
			echo json_encode(['status' => 'error']);
		}
	}

	public function mark_all_read()
	{
		$user = $this->session->userdata('user');
		if ($user) {
			$category = $this->get_notif_category($user['hakakses']);
			$this->Notification->mark_all_as_read($category);
			echo json_encode(['status' => 'success']);
		} else {
			echo json_encode(['status' => 'error']);
		}
	}
}
