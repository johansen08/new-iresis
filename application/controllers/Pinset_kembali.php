<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PACKER -> "Konfirmasi Stok AKS27-RDM".
 *
 * Packer mengonfirmasi fisik pinset bonus AKS27-RDM dari resi cancel/retur dan mengajukan opname.
 * Aturan dan model: Stok_aks27_kembali_fcd. Saldo sistem sengaja tidak dikirim ke layar ini (opname buta).
 */
class Pinset_kembali extends MY_Controller
{
    /** Webmaster (1) dan client packer (4). Sejalan dengan MY_Controller::run_stok_aks27_kembali_migration(). */
    const ROLE_BOLEH = [1, 4];

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
                'view'    => $this->load->view('pinset_kembali/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Konfirmasi Stok AKS27-RDM.');
    }

    private function id_user()
    {
        return isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
    }

    private function wajib_post()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
    }

    public function index()
    {
        $this->show([], 'pinset_kembali/index');
    }

    /** Resi menunggu konfirmasi + riwayat konfirmasi dan pengajuan. */
    public function data()
    {
        $this->make_ajax_response(200, 'OK', array_merge(
            ['menunggu' => $this->stok_aks27_kembali_fcd->menunggu()],
            $this->stok_aks27_kembali_fcd->riwayat_packer()
        ));
    }

    /** POST noresi -> boleh dikonfirmasi atau tidak (dan jenisnya). */
    public function cek()
    {
        $this->wajib_post();
        $h = $this->stok_aks27_kembali_fcd->cek($this->input->post('noresi'));
        $this->make_ajax_response($h['ok'] ? 200 : 400, $h['pesan'], $h['data']);
    }

    /** POST noresi, pilihan (ADA/TIDAK_ADA/RUSAK), alasan. */
    public function simpan()
    {
        $this->wajib_post();
        $h = $this->stok_aks27_kembali_fcd->konfirmasi(
            $this->input->post('noresi'),
            $this->input->post('pilihan'),
            $this->input->post('alasan'),
            $this->id_user()
        );
        $this->make_ajax_response($h['ok'] ? 200 : 400, $h['pesan'], $h['data']);
    }

    /** POST fisik, alasan, catatan -> pengajuan opname (menunggu persetujuan Purchasing). */
    public function opname()
    {
        $this->wajib_post();
        $h = $this->stok_aks27_kembali_fcd->ajukan_opname(
            $this->input->post('fisik'),
            $this->input->post('alasan'),
            $this->input->post('catatan'),
            $this->id_user()
        );
        $this->make_ajax_response($h['ok'] ? 200 : 400, $h['pesan'], $h['data']);
    }
}
