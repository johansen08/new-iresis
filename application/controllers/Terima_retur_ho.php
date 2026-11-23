<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * HO > Terima Retur (terima-retur-ho): dua jenis, Retur Ekspedisi dan Retur Komplain.
 * Aturan dan data yang disimpan: docs/TERIMA_RETUR_HO.md.
 */
class Terima_retur_ho extends MY_Controller
{
    const URI = 'terima-retur-ho';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('terima_retur_ho_fcd');
    }

    /** Role user harus punya akses ke menu (sidebar saja tidak cukup untuk endpoint POST). */
    private function boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if ($role === null || $role === '') {
            return false;
        }
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => self::URI])->row();
        if (!$menu) {
            return false;
        }
        return $this->db->where(['menuid' => $menu->id, 'roleid' => $role])->count_all_results('roleaccess') > 0;
    }

    public function index()
    {
        $this->show([]);
    }

    /** POST resi + jenis -> satu resi diterima. Selalu HTTP 200; status ada di data.kode. */
    public function scan()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        if (!$this->boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.', ['kode' => 'ditolak']);
        }
        if ($this->session->userdata('mode_arsip')) {
            $this->make_ajax_response(200, 'Mode Arsip aktif: data tidak bisa disimpan.', ['kode' => 'gagal']);
        }

        $hasil = $this->terima_retur_ho_fcd->terima(
            $this->input->post('resi'),
            (string) $this->input->post('jenis'),
            $this->data['user']['id_user']
        );

        $this->make_ajax_response(200, $hasil['message'], $hasil);
    }
}
