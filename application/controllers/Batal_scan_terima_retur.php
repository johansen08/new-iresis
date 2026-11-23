<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * HO > Batal Scan Terima Retur (batal-scan-terima-retur): hapus resi yang salah scan di menu
 * Terima Retur (mis. paket reguler/NDD). Logika di Terima_retur_ho_fcd::hapus().
 * Aturan: docs/TERIMA_RETUR_HO.md.
 */
class Batal_scan_terima_retur extends MY_Controller
{
    const URI = 'batal-scan-terima-retur';

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

    /**
     * POST resi (+ konfirmasi=1) -> hapus resi salah scan dari data retur. Tanpa konfirmasi hanya memeriksa.
     * Selalu HTTP 200; status ada di data.kode.
     */
    public function hapus()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        if (!$this->boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.', ['kode' => 'ditolak']);
        }
        if ($this->session->userdata('mode_arsip')) {
            $this->make_ajax_response(200, 'Mode Arsip aktif: data tidak bisa diubah.', ['kode' => 'gagal']);
        }

        $hasil = $this->terima_retur_ho_fcd->hapus(
            $this->input->post('resi'),
            $this->data['user']['id_user'],
            $this->input->post('konfirmasi') === '1'
        );

        $this->make_ajax_response(200, $hasil['message'], $hasil);
    }
}
