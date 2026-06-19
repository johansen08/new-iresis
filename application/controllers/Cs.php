<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cs extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        // Load necessary models if any, e.g., cs_fcd, retur_fcd
        // $this->load->model('retur_fcd'); 
    }

    public function retur_complain()
    {
        $data['message'] = $this->session->flashdata('message');
        // $data['title'] = 'Laporan Retur Complain'; 

        // Placeholder view. If view doesn't exist, this might error.
        // Usually views are in views/cs/retur_complain or similar.
        // For now, finding a view or just showing data to prevent 404.
        
        $this->show($data);
    }
}
