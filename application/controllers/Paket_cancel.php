<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Terima Paket Cancel & Laporan Paket Cancel (tim retur). Alur: docs/PAKET_CANCEL.md §12.
 */
class Paket_cancel extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('cancel_terima_fcd');
    }

    private function id_user()
    {
        return (int) $this->data['user']['id_user'];
    }

    private function tanggal($nilai, $default)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $nilai) ? $nilai : $default;
    }

    // ───────── Terima Paket Cancel ─────────

    public function terima()
    {
        $this->show();
    }

    // Scan resi -> isi seharusnya
    public function cek_resi()
    {
        $this->make_ajax_response(200, 'ok', $this->cancel_terima_fcd->cek_resi($this->input->post('noresi')));
    }

    // Saran SKU saat mengetik (barang di luar resi)
    public function sku_saran()
    {
        $q = trim((string) $this->input->get('q'));
        $this->make_ajax_response(200, 'ok', ['results' => $q === '' ? [] : $this->cancel_terima_fcd->saran_sku($q)]);
    }

    // Tebak satu SKU dari teks
    public function sku_detail()
    {
        $this->make_ajax_response(200, 'ok', $this->cancel_terima_fcd->cari_sku($this->input->post('sku')));
    }

    public function simpan()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->simpan(
            $this->input->post('noresi'),
            $this->input->post('dari'),
            is_array($items) ? $items : [],
            $this->input->post('catatan'),
            $this->id_user(),
            isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : null
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // ───────── Laporan Paket Cancel ─────────

    public function laporan()
    {
        $this->show();
    }

    // Semua paket diterima pada rentang tanggal (maks 93 hari); filter asal & cari dilakukan di klien.
    public function laporan_data()
    {
        $hari_ini = date('Y-m-d');
        $start = $this->tanggal($this->input->post('start'), $hari_ini);
        $end = $this->tanggal($this->input->post('end'), $start);
        if ($end < $start) {
            list($start, $end) = array($end, $start);
        }
        if (strtotime($end) - strtotime($start) > 92 * 86400) {
            $this->make_ajax_response(400, 'Rentang tanggal maksimal 93 hari', ['ok' => false]);
        }
        $this->make_ajax_response(200, 'ok', ['rows' => $this->cancel_terima_fcd->laporan_baris($start, $end), 'ok' => true]);
    }

    public function edit_simpan()
    {
        $items = json_decode((string) $this->input->post('items'), true);
        $hasil = $this->cancel_terima_fcd->edit(
            (int) $this->input->post('id'),
            $this->input->post('dari'),
            is_array($items) ? $items : [],
            $this->input->post('catatan'),
            $this->id_user()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    public function hapus()
    {
        $hasil = $this->cancel_terima_fcd->hapus((int) $this->input->post('id'), $this->input->post('alasan'), $this->id_user());
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Isi kertas serah terima satu tanggal (pratinjau di halaman laporan)
    public function serah_data()
    {
        $tanggal = $this->tanggal($this->input->post('tanggal'), date('Y-m-d'));
        $this->make_ajax_response(200, 'ok', ['tanggal' => $tanggal, 'ok' => true] + $this->cancel_terima_fcd->serah_harian($tanggal));
    }

    // Halaman cetak A4 (tab baru, bukan JSON)
    public function serah_cetak()
    {
        $tanggal = $this->tanggal($this->input->get('tanggal'), date('Y-m-d'));
        $data = $this->cancel_terima_fcd->serah_harian($tanggal);
        $data['tanggal'] = $tanggal;
        $this->load->view('paket_cancel/serah_cetak', $data);
    }
}
