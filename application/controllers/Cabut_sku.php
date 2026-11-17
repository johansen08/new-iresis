<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM MONITORING -> "Cabut SKU" (hanya webmaster). Lihat docs/CABUT_SKU.md.
 * Stok live diambil dari Jubelio per halaman; browser memanggil `perbarui` berulang sampai halaman terakhir.
 */
class Cabut_sku extends MY_Controller
{
    /** Sejalan dengan MY_Controller::run_cabut_sku_migration(). */
    const ROLE_BOLEH = [1];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('cabut_sku_fcd');
    }

    /** Penolakan tetap JSON valid (pola sama dengan Bonus_picker). */
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
                'view'    => $this->load->view('cabut_sku/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Hanya webmaster yang punya akses ke menu Cabut SKU.');
    }

    public function index()
    {
        $this->show([
            'kategori' => Cabut_sku_fcd::KATEGORI,
            'batas'    => Cabut_sku_fcd::BATAS_STOK,
        ], 'cabut_sku/index');
    }

    /** Daftar SKU yang memenuhi syarat dari data stok terakhir yang tersimpan (tanpa menyentuh Jubelio). */
    public function get_data()
    {
        $this->make_ajax_response(200, 'OK', [
            'baris'     => $this->cabut_sku_fcd->daftar(),
            'ringkasan' => $this->cabut_sku_fcd->ringkasan(),
        ]);
    }

    /** Ambil satu halaman stok dari Jubelio lalu simpan. POST: halaman (mulai 1). */
    public function perbarui()
    {
        $halaman = max(1, (int) $this->input->post('halaman'));
        set_time_limit(60);

        try {
            $this->load->library('jubelio_stok');
            $hasil = $this->jubelio_stok->daftar_stok($halaman, Cabut_sku_fcd::UKURAN_HALAMAN);
            $rak   = $this->jubelio_stok->daftar_rak($halaman, Cabut_sku_fcd::UKURAN_HALAMAN);
        } catch (RuntimeException $e) {
            $this->make_ajax_response(502, $e->getMessage());
        }

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_start();
        $n = $this->cabut_sku_fcd->simpan_halaman($hasil['items']);
        $this->cabut_sku_fcd->simpan_rak_halaman($rak['items']);
        $this->db->trans_complete();
        $this->db->db_debug = $db_debug;
        if ($this->db->trans_status() === FALSE) {
            $this->make_ajax_response(500, 'Gagal menyimpan stok halaman ' . $halaman . ' ke database.');
        }

        $this->make_ajax_response(200, 'OK', [
            'halaman'       => $halaman,
            'total_halaman' => (int) max(1, ceil($hasil['total'] / Cabut_sku_fcd::UKURAN_HALAMAN)),
            'total_item'    => $hasil['total'],
            'tersimpan'     => $n,
        ]);
    }
}
