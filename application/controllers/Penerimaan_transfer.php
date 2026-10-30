<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PACKER -> "Penerimaan Transfer".
 *
 * Packer menghitung ulang dan menerima transfer pinset AKS27-RDM dari
 * Purchasing. Aturan dan model: Penerimaan_transfer_fcd.
 */
class Penerimaan_transfer extends MY_Controller
{
    /** Webmaster (1) dan client packer (4). Sejalan dengan MY_Controller::run_penerimaan_transfer_migration(). */
    const ROLE_BOLEH = [1, 4];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('transfer_stok_fcd');
        $this->load->model('penerimaan_transfer_fcd');
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
                'view'    => $this->load->view('penerimaan_transfer/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Penerimaan Transfer.');
    }

    private function id_user()
    {
        return isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
    }

    public function index()
    {
        $this->show(['hari_ini' => date('Y-m-d')], 'penerimaan_transfer/index');
    }

    /** Transfer yang menunggu diterima + saldo stok packing. */
    public function menunggu()
    {
        $this->make_ajax_response(200, 'OK', [
            'rows'  => $this->penerimaan_transfer_fcd->menunggu(),
            'saldo' => $this->penerimaan_transfer_fcd->saldo(),
        ]);
    }

    /** GET q, dari, sampai, per, halaman -> satu halaman riwayat penerimaan. */
    public function riwayat()
    {
        $hasil = $this->penerimaan_transfer_fcd->riwayat([
            'q'       => $this->input->get('q'),
            'dari'    => $this->input->get('dari'),
            'sampai'  => $this->input->get('sampai'),
            'per'     => $this->input->get('per'),
            'halaman' => $this->input->get('halaman'),
        ]);
        $this->make_ajax_response(200, 'OK', $hasil);
    }

    /** POST id, tanggal, qty. */
    public function terima()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $hasil = $this->penerimaan_transfer_fcd->terima(
            $this->input->post('id'),
            $this->input->post('tanggal'),
            $this->input->post('qty'),
            $this->id_user()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['pesan'], ['tipe' => $hasil['tipe']]);
    }
}
