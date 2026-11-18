<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Kategori SKU": daftar baca-saja SKU + gambar + no rak + kategori.
 * (Mengisi kategori lewat Excel ada di controller Kategori_sku.) Lihat docs/KATEGORI_SKU.md.
 */
class Daftar_kategori_sku extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_daftar_kategori_sku_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('daftar_kategori_sku_fcd');
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
                'view'    => $this->load->view('kategori_sku/daftar', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Kategori SKU.');
    }

    public function index()
    {
        $this->show([
            'kategori' => $this->daftar_kategori_sku_fcd->daftar_kategori(),
            'belum_ada' => Daftar_kategori_sku_fcd::BELUM_ADA,
        ], 'kategori_sku/daftar');
    }

    /** POST: q, kat[] (kategori terpilih; kosong = semua), halaman, ukuran (25/50/100). */
    public function get_data()
    {
        $kat = array_values(array_filter((array) $this->input->post('kat'), 'is_string'));
        $this->make_ajax_response(200, 'OK', $this->daftar_kategori_sku_fcd->halaman(
            (string) $this->input->post('q'),
            $kat,
            (int) $this->input->post('halaman'),
            (int) $this->input->post('ukuran')
        ));
    }
}
