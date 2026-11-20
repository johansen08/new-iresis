<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Stok Retur Buka" (TIM RETUR): stok barang retur sudah dibuka (KE_DISPLAY) yang belum
 * diverifikasi accounting. Hanya membaca. Definisi stok ada di Stok_retur_buka_fcd.
 */
class Stok_retur_buka extends MY_Controller
{
    const URI = 'retur/stok-retur-buka';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('stok_retur_buka_fcd');
    }

    /** Halaman menu. */
    public function index()
    {
        $this->show();
    }

    /** Data stok per SKU (JSON via make_ajax_response). */
    public function stok_data()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
            return;
        }
        $hasil = $this->stok_retur_buka_fcd->stok();
        $hasil['ok'] = true;
        $this->make_ajax_response(200, 'ok', $hasil);
    }

    /** Role user harus punya akses ke menu ini (sidebar saja tidak cukup untuk endpoint POST). */
    private function _boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;

        $menu = $this->db->get_where('menu', array('uri' => self::URI))->row();
        if (!$menu) return false;

        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))
            ->count_all_results('roleaccess') > 0;
    }
}
