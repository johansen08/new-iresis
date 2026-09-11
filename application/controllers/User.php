<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends MY_Controller
{

	function __construct()
	{
		parent::__construct();

		$this->load->model('user_fcd');
		$this->load->model('employee_fcd');
		$this->load->model('role_fcd');
	}

	public function index()
	{
		$data['message'] = $this->session->flashdata('message');
		$data['list_user'] = $this->user_fcd->get_user()->result_array();
		$data['list_role'] = $this->role_fcd->get_role()->result_array();

		$this->show($data);
	}

	public function edit_user($user_id = null)
	{
		$this->load->model('role_fcd');

		$data['action'] = 'Add new user';
		$data['list_role'] = $this->role_fcd->get_role()->result_array();
		$data['list_status'] = array("AKTIF", "NON_AKTIF");

		if (!empty($user_id)) {
			$data['action'] = 'Edit user';
			$data['user'] = $this->user_fcd->get_user($user_id)->row_array();
			$data['employee'] = $this->employee_fcd->get_employee($data['user']['id_pegawai'])->row_array();
		}

		$this->show($data);
	}

	public function save_user()
	{
		if ($this->input->method() == 'get') {
			redirect('404_override');
		}

		$user['id_user'] =  $this->input->post('id_user');
		$user['username'] =  $this->input->post('username');
		$user['password'] =  md5($this->input->post('password'));
		$user['name'] =  $this->input->post('name');
		$user['email'] =  $this->input->post('email');
		$user['hakakses'] = $this->input->post('hakakses');
		$user['foto'] = $this->input->post('foto');
		$user['bypass'] = $this->input->post('bypass') ? 1 : 0;

        // HANDLE FILE UPLOAD IF EXISTS
        if (!empty($_FILES['foto_file']['name'])) {
            $config['upload_path']   = './assets/img/users/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size']      = 2048; // 2MB
            $config['file_name']     = 'user_' . ($user['id_user'] ? $user['id_user'] : 'new') . '_' . time();

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('foto_file')) {
                $upload_data = $this->upload->data();
                $user['foto'] = 'assets/img/users/' . $upload_data['file_name'];
            }
        }

        // Auto-convert Google Drive links to direct links (supports resourcekey)
        if (strpos($user['foto'], 'drive.google.com') !== false) {
            $id = '';
            $rk = '';
            
            // Extract ID
            if (preg_match('/\/file\/d\/([^\/\?]+)/', $user['foto'], $matches)) {
                $id = $matches[1];
            } elseif (preg_match('/id=([^\&]+)/', $user['foto'], $matches)) {
                $id = $matches[1];
            }
            
            // Extract ResourceKey
            if (preg_match('/resourcekey=([^\&]+)/', $user['foto'], $matches)) {
                $rk = $matches[1];
            }
            
            if ($id) {
                // thumbnail link is more reliable for embedding
                $user['foto'] = "https://drive.google.com/thumbnail?id=" . $id . ($rk ? "&resourcekey=" . $rk : "") . "&sz=w1000";
            }
        }

        // Validasi username duplikat
        if (empty($user['id_user'])) {
            // Untuk user baru
            $existing = $this->db->get_where('tbluser', ['username' => $user['username'], 'isactive' => 1])->row();
            if ($existing) {
                $this->set_message('Error', 'Username already exists', 'error');
                $this->show_index();
                return;
            }
        } else {
            // Untuk edit user (exclude current user)
            $existing = $this->db->get_where('tbluser', [
                'username' => $user['username'], 
                'id_user !=' => $user['id_user'],
                'isactive' => 1
            ])->row();
            if ($existing) {
                $this->set_message('Error', 'Username already exists', 'error');
                $this->show_index();
                return;
            }
        }

		if (empty($user['id_user'])) {
			$user['employee'] = [
				'nama_pegawai' => $this->input->post('nama_pegawai'),
				'status_aktif' => $this->input->post('status_aktif'),
			];
		}

		$save = $this->user_fcd->save($user, $this->data['user']['id_user']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
            
            // IF editing OWN profile, update session immediately
            if ($user['id_user'] == $this->data['user']['id_user']) {
                $updated_user = $this->user_fcd->get_user($user['id_user'])->row_array();
                $this->session->set_userdata('user', $updated_user);
            }
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

		$this->show_index();
	}

	public function generate_password_user($user_id)
	{
		$data['action'] = 'Generate user password';
		$data['user'] = $this->user_fcd->get_user($user_id)->row_array();

		$this->show($data);
	}

	public function update_password_user()
	{
		$user['id_user'] =  $this->input->post('id_user');
		$user['password'] = md5($this->input->post('password'));

		$save = $this->user_fcd->update_password($user, $this->data['user']['id_user']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

		$this->show_index();
	}

	public function delete_user($user_id)
	{
		$user['id_user'] =  $user_id;
		$user['isactive'] = FALSE;

		$save = $this->user_fcd->save($user, $this->data['user']['id_user']);

		if ($save['affected_rows'] > 0) {
			$this->set_message('Success', SUCCESS_SAVE_DATA, 'information');
		} else {
			$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
		}

		$this->show_index();
	}

	public function update_role_user()
	{
		if ($this->input->method() != 'post') {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['status' => 'error', 'message' => 'Invalid request']));
		}

		$id_user  = (int) $this->input->post('id_user');
		$hakakses = (int) $this->input->post('hakakses');

		if (!$id_user || !$hakakses) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['status' => 'error', 'message' => 'Invalid data']));
		}

		$this->db->where('id_user', $id_user);
		$this->db->update('tbluser', [
			'hakakses'  => $hakakses,
			'updatedby' => $this->data['user']['id_user'],
			'updated'   => date('Y-m-d H:i:s'),
		]);

		$affected = $this->db->affected_rows();

		// Get new role name
		$role = $this->role_fcd->get_role($hakakses)->row_array();
		$role_name = $role ? $role['akses'] : '-';

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'status'    => $affected > 0 ? 'success' : 'warning',
				'message'   => $affected > 0 ? 'Role berhasil diperbarui' : 'Tidak ada perubahan',
				'role_name' => $role_name,
			]));
	}
}
