<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Daftar SKU Bonus".
 *
 * Daftar SKU pemicu bonus pinset AKS27-RDM: resi yang berisi salah satu SKU
 * aktif mendapat 1 pinset. Aturan dan model: Sku_bonus_fcd.
 */
class Sku_bonus extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_sku_bonus_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('sku_bonus_fcd');
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
                'view'    => $this->load->view('sku_bonus/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Daftar SKU Bonus.');
    }

    private function id_user()
    {
        return isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
    }

    public function index()
    {
        $this->show([], 'sku_bonus/index');
    }

    /** GET q, halaman -> satu halaman daftar. */
    public function data()
    {
        $hasil = $this->sku_bonus_fcd->daftar($this->input->get('q'), $this->input->get('halaman'));
        $this->make_ajax_response(200, 'OK', $hasil);
    }

    /** GET q -> saran SKU standar (array polos untuk dropdown). */
    public function saran()
    {
        $hasil = [];
        foreach ($this->sku_bonus_fcd->saran($this->input->get('q'), 6) as $r) {
            $hasil[] = ['sku' => $r['id_sku'], 'nama' => $r['nama_sku']];
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($hasil);
        exit();
    }

    public function tambah()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $hasil = $this->sku_bonus_fcd->tambah($this->input->post('sku'), $this->id_user());
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['pesan'], ['sku' => $hasil['sku']]);
    }

    public function ubah_status()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $row = $this->sku_bonus_fcd->ubah_status($this->input->post('id'), $this->id_user());
        if (!$row) {
            $this->make_ajax_response(404, 'SKU tidak ditemukan.');
        }
        $pesan = $row['sku'] . ($row['aktif']
            ? ' diaktifkan lagi.'
            : ' dinonaktifkan. Resi baru dengan SKU ini tidak dapat pinset.');
        $this->make_ajax_response(200, $pesan, ['aktif' => (int) $row['aktif']]);
    }
}
