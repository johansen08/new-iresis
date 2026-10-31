<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Persetujuan Stok AKS27".
 *
 * Purchasing menyetujui atau menolak koreksi "lupa dimasukkan" dan opname yang diajukan packer.
 * Setuju menulis baris PENYESUAIAN di buku stok. Aturan dan model: Stok_aks27_kembali_fcd.
 */
class Persetujuan_aks27 extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_stok_aks27_kembali_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('stok_aks27_kembali_fcd');
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
                'view'    => $this->load->view('persetujuan_aks27/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Persetujuan Stok AKS27.');
    }

    public function index()
    {
        $this->show([], 'persetujuan_aks27/index');
    }

    public function data()
    {
        $this->make_ajax_response(200, 'OK', $this->stok_aks27_kembali_fcd->antrean());
    }

    /** POST id, keputusan (setuju/tolak), catatan (wajib bila tolak). */
    public function putuskan()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $id_user = isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
        $h = $this->stok_aks27_kembali_fcd->putuskan(
            $this->input->post('id'),
            $this->input->post('keputusan') === 'setuju',
            $this->input->post('catatan'),
            $id_user
        );
        $this->make_ajax_response($h['ok'] ? 200 : 400, $h['pesan']);
    }
}
