<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Stok AKS27-RDM".
 *
 * Buku stok pinset bonus AKS27-RDM (hanya baca). Aturan dan model: Stok_aks27_fcd.
 */
class Stok_aks27 extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_stok_aks27_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('transfer_stok_fcd');
        $this->load->model('stok_aks27_fcd');
    }

    /** Penolakan tetap JSON valid (show_404 mengirim HTML dan merusak SPA). */
    private function tolak_role_tanpa_akses()
    {
        $role = isset($this->data['user']['hakakses']) ? (int) $this->data['user']['hakakses'] : 0;
        if (in_array($role, self::ROLE_BOLEH, TRUE)) {
            return;
        }

        if ($this->router->method === 'index') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'view'    => $this->load->view('stok_aks27/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Stok AKS27-RDM.');
    }

    public function index()
    {
        $this->show(['hari_ini' => date('Y-m-d')], 'stok_aks27/index');
    }

    /** Kartu ringkasan, jumlah per ref, dan titik grafik. */
    public function ringkas()
    {
        $this->make_ajax_response(200, 'OK', $this->stok_aks27_fcd->ringkas());
    }

    /** GET q, ref (dipisah koma), dari, sampai, per, halaman -> satu halaman buku stok. */
    public function data()
    {
        $hasil = $this->stok_aks27_fcd->daftar([
            'q'       => $this->input->get('q'),
            'ref'     => $this->input->get('ref'),
            'dari'    => $this->input->get('dari'),
            'sampai'  => $this->input->get('sampai'),
            'per'     => $this->input->get('per'),
            'halaman' => $this->input->get('halaman'),
        ]);
        $this->make_ajax_response(200, 'OK', $hasil);
    }
}
