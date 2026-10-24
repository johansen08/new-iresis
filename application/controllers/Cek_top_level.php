<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Cek Top Level" (TIM CS, URL picker/cek-top-level; semula di TIM PICKER). Satu akun dipakai semua picker.
 *
 * Picker scan resi, klik Cek pada SKU yang barangnya tidak ada di display, lalu (bila stok top level
 * ada) mengajukan Request Restok Display dan menyerahkan resi fisik ke CS. Sisi CS ada di controller
 * Top_level; alur dan aturan lengkap di docs/TOP_LEVEL.md.
 */
class Cek_top_level extends MY_Controller
{
    const URI = 'picker/cek-top-level';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('top_level_fcd');
    }

    public function index()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh()));
    }

    /** Hasil pencarian resi (JSON). Dipanggil otomatis begitu isian berhenti diketik/ditempel/discan. */
    public function cari()
    {
        $this->_wajib();
        $resi = $this->top_level_fcd->cari_resi($this->input->post('q'));
        $this->make_ajax_response(200, $resi === null ? 'Tidak ditemukan' : 'OK', array('ditemukan' => $resi !== null, 'resi' => $resi));
    }

    public function cek()
    {
        $this->_wajib();
        $sku = trim((string) $this->input->post('sku'));
        $noresi = trim((string) $this->input->post('noresi'));
        if ($sku === '') {
            $this->make_ajax_response(400, 'SKU kosong.');
        }
        $this->make_ajax_response(200, 'OK', $this->top_level_fcd->cek_sku($sku, $noresi, $this->_oleh()));
    }

    public function ajukan()
    {
        $this->_wajib();
        $sku = trim((string) $this->input->post('sku'));
        $noresi = trim((string) $this->input->post('noresi'));
        if ($sku === '' || $noresi === '') {
            $this->make_ajax_response(400, 'SKU dan resi wajib diisi.');
        }
        $h = $this->top_level_fcd->buat_request($sku, $noresi, $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Request ' . $h['nomor'] . ' dibuat.' : $h['pesan'], $h['ok'] ? array('kode' => $h['kode'], 'nomor' => $h['nomor']) : array('aktif' => isset($h['aktif']) ? $h['aktif'] : 0));
    }

    public function gabung()
    {
        $this->_wajib();
        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Resi kosong.');
        }
        $h = $this->top_level_fcd->gabung((int) $this->input->post('id'), $noresi, $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Resi digabung ke ' . $h['nomor'] . '.' : $h['pesan']);
    }

    public function batal()
    {
        $this->_wajib();
        $h = $this->top_level_fcd->batal((int) $this->input->post('id'), $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Request dibatalkan.' : $h['pesan']);
    }

    public function terbaru()
    {
        $this->_wajib();
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->terbaru()));
    }

    // ------------------------------------------------------------ utilitas

    /** Role user harus punya akses ke menu ini (sidebar saja tidak cukup untuk endpoint POST). */
    private function _boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;
        $menu = $this->db->get_where('menu', array('uri' => self::URI))->row();
        if (!$menu) return false;
        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))->count_all_results('roleaccess') > 0;
    }

    private function _wajib()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }
    }

    private function _oleh()
    {
        $u = isset($this->data['user']) ? $this->data['user'] : array();
        return !empty($u['name']) ? $u['name'] : (isset($u['username']) ? $u['username'] : '');
    }
}
