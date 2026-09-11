<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ngrok_control extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Ensure only admins can access
        $user = $this->session->userdata('user');
        if (!$user || !in_array($user['hakakses'], [1, 2, 5, 6])) {
            redirect('restricted');
        }

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

        $this->data['content'] = $this->load->view('ngrok_control', null, TRUE);

        $this->load->view('main', $this->data);
    }

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

    public function get_status()
    {
        $url = 'http://localhost:4040/api/tunnels';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            echo $response;
        } else {
            echo json_encode(['status' => 'offline']);
        }
    }

    public function save_token()
    {
        $token = $this->input->post('token');
        if (empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Token cannot be empty']);
            return;
        }

        $cmd = "cd " . FCPATH . " && ngrok.exe config add-authtoken " . escapeshellarg($token);
        exec($cmd, $output, $return_var);

        if ($return_var === 0) {
            echo json_encode(['status' => 'success', 'message' => 'Token saved successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save token. Make sure ngrok.exe is present.']);
        }
    }
}
