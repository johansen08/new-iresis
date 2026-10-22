<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu Cari Detail Resi (URL receipt/detail-receipt).
 *
 * Halaman hanya berisi kolom pencarian. Hasilnya diambil lewat AJAX (cari()) begitu
 * isian berhenti diketik/ditempel/discan, lalu potongan HTML-nya ditaruh di bawah kolom
 * pencarian -- tanpa tombol Cari. Tampilan mengikuti prototype di docs/ (hirarki: identitas
 * dan batas kirim, alur scan, barang dan petugas, lalu kartu sekunder yang tertutup).
 */
class Detail_resi extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('detail_resi_fcd');
    }

    /** Halaman: kolom pencarian saja. */
    public function index()
    {
        $this->show([]);
    }

    /** Hasil pencarian satu resi sebagai potongan HTML. */
    public function cari()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $keyword = trim((string) $this->input->post('q'));
        if ($keyword === '') {
            $this->make_ajax_response(200, 'Kosong', ['html' => '', 'ditemukan' => false]);
        }

        $detail = $this->detail_resi_fcd->cari($keyword);

        $html = $this->load->view('detail_resi/hasil', [
            'keyword' => $keyword,
            'd'       => $detail,
            'tarik'   => TRUE,
        ], TRUE);

        $this->make_ajax_response(200, $detail === null ? 'Tidak ditemukan' : 'Ditemukan', [
            'html'      => $html,
            'ditemukan' => $detail !== null,
        ]);
    }
}
