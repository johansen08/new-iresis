<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Transfer Stok".
 *
 * Catatan pinset AKS27-RDM yang dikirim Purchasing ke lokasi packer.
 * Aturan dan model: Transfer_stok_fcd.
 */
class Transfer_stok extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_transfer_stok_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('transfer_stok_fcd');
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
                'view'    => $this->load->view('transfer_stok/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Transfer Stok.');
    }

    private function id_user()
    {
        return isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
    }

    public function index()
    {
        $this->show(['hari_ini' => date('Y-m-d')], 'transfer_stok/index');
    }

    /** GET q, dari, sampai, per, halaman -> satu halaman daftar transfer. */
    public function data()
    {
        $hasil = $this->transfer_stok_fcd->daftar([
            'q'       => $this->input->get('q'),
            'dari'    => $this->input->get('dari'),
            'sampai'  => $this->input->get('sampai'),
            'per'     => $this->input->get('per'),
            'halaman' => $this->input->get('halaman'),
        ]);
        $this->make_ajax_response(200, 'OK', $hasil);
    }

    /** POST tanggal, qty, keterangan. */
    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $hasil = $this->transfer_stok_fcd->simpan(
            $this->input->post('tanggal'),
            $this->input->post('qty'),
            $this->input->post('keterangan'),
            $this->id_user()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['pesan'], ['no' => $hasil['no']]);
    }
}
