<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cek barang gedung baru (GD-BR), dua menu anak TIM MONITORING:
 *   - Scan Resi GD-BR  (gdbr/scan):  tahap RESI, GEDUNG BARU, PACKER
 *   - Rekap Resi GD-BR (gdbr/rekap): rekap per resi + detail per SKU
 * Alur dan aturan: docs/GDBR.md. Petugas selalu akun yang login.
 */
class Gdbr extends MY_Controller
{
    const URI_SCAN  = 'gdbr/scan';
    const URI_REKAP = 'gdbr/rekap';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('gdbr_fcd');
    }

    /** Role user harus punya akses ke menu (sidebar saja tidak cukup untuk endpoint POST). */
    private function boleh($uri)
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if ($role === null || $role === '') {
            return false;
        }
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', array('uri' => $uri))->row();
        if (!$menu) {
            return false;
        }
        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))->count_all_results('roleaccess') > 0;
    }

    private function wajib($uri)
    {
        if (!$this->boleh($uri)) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.', array('ok' => false));
        }
    }

    private function petugas()
    {
        return $this->gdbr_fcd->petugas_dari_user($this->data['user']);
    }

    private function komputer()
    {
        return isset($this->data['user']['nama_komputer']) ? $this->data['user']['nama_komputer'] : null;
    }

    private function tanggal($nilai, $default)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $nilai) ? $nilai : $default;
    }

    // ───────── Halaman ─────────

    public function scan()
    {
        $this->show(array('petugas' => $this->petugas()));
    }

    public function rekap()
    {
        $this->show();
    }

    // ───────── Scan Resi GD-BR ─────────

    /** Scan satu resi pada satu tahap. Tahap RESI langsung menyimpan. */
    public function cek()
    {
        $this->wajib(self::URI_SCAN);
        if ($this->session->userdata('mode_arsip')) {
            $this->make_ajax_response(200, 'ok', array('status' => 'arsip'));
        }
        $tahap = (string) $this->input->post('tahap');
        $this->make_ajax_response(200, 'ok', $this->gdbr_fcd->scan($tahap, $this->input->post('resi'), $this->petugas(), $this->komputer()));
    }

    public function simpan()
    {
        $this->wajib(self::URI_SCAN);
        if ($this->session->userdata('mode_arsip')) {
            $this->make_ajax_response(200, 'Mode Arsip aktif: data tidak bisa disimpan.', array('ok' => false));
        }
        $items = json_decode((string) $this->input->post('items'), true);
        $extra = json_decode((string) $this->input->post('extra'), true);
        $hasil = $this->gdbr_fcd->simpan(
            (string) $this->input->post('tahap'),
            $this->input->post('resi'),
            is_array($items) ? $items : array(),
            is_array($extra) ? $extra : array(),
            (int) $this->input->post('lain') === 1,
            $this->input->post('catatan'),
            $this->input->post('waktu_scan'),
            $this->petugas(),
            $this->komputer()
        );
        $this->make_ajax_response($hasil['ok'] ? 200 : 400, $hasil['message'], $hasil);
    }

    // Rekomendasi SKU saat mengetik (barang di luar resi): SKU + no. rak + foto
    public function sku_saran()
    {
        $this->wajib(self::URI_SCAN);
        $this->load->model('cancel_terima_fcd');
        $q = trim((string) $this->input->get('q'));
        $this->make_ajax_response(200, 'ok', array('results' => $q === '' ? array() : $this->cancel_terima_fcd->saran_sku($q)));
    }

    // Tebak satu SKU dari teks
    public function sku_detail()
    {
        $this->wajib(self::URI_SCAN);
        $this->load->model('cancel_terima_fcd');
        $this->make_ajax_response(200, 'ok', $this->cancel_terima_fcd->cari_sku($this->input->post('sku')));
    }

    // ───────── Rekap Resi GD-BR ─────────

    /** Rentang tanggal dari POST (default awal bulan s/d hari ini), maksimal 93 hari; null bila terlalu panjang. */
    private function rentang()
    {
        $start = $this->tanggal($this->input->post('start'), date('Y-m-01'));
        $end   = $this->tanggal($this->input->post('end'), date('Y-m-d'));
        if ($end < $start) {
            list($start, $end) = array($end, $start);
        }
        return (strtotime($end) - strtotime($start) > 92 * 86400) ? null : array($start, $end);
    }

    private function filter_post()
    {
        $filter = array();
        foreach (array('progres', 'hasil', 'picker', 'marketplace', 'toko') as $k) {
            $v = $this->input->post('f_' . $k);
            $filter[$k] = is_array($v) ? array_values(array_filter(array_map('strval', $v), 'strlen')) : array();
        }
        return $filter;
    }

    public function rekap_data()
    {
        $this->wajib(self::URI_REKAP);
        $r = $this->rentang();
        if (!$r) {
            $this->make_ajax_response(400, 'Rentang tanggal maksimal 93 hari', array('ok' => false));
        }
        $data = $this->gdbr_fcd->rekap($r[0], $r[1], $this->filter_post(), $this->input->post('q'), $this->input->post('halaman'), $this->input->post('per'));
        $data['ok'] = true;
        $this->make_ajax_response(200, 'ok', $data);
    }

    /** Unduh CSV sesuai periode & filter yang aktif (dibuka lewat form POST ke iframe tersembunyi). */
    public function rekap_export()
    {
        if (!$this->boleh(self::URI_REKAP)) {
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }
        $r = $this->rentang();
        if (!$r) {
            show_error('Rentang tanggal maksimal 93 hari', 400);
        }
        $d = $this->gdbr_fcd->rekap($r[0], $r[1], $this->filter_post(), $this->input->post('q'), 0, 0);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rekap-resi-gdbr-' . $r[0] . '_' . $r[1] . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, array('No. Resi', 'No. Pesanan', 'Toko', 'Marketplace', 'Picker', 'Waktu diambil', 'Ditandai tim resi', 'Waktu ditandai',
            'Cek Gedung Baru', 'Waktu cek Gedung Baru', 'Hasil Gedung Baru', 'Cek Packer', 'Waktu cek Packer', 'Hasil Packer', 'Progres'));
        $hasil = function ($c) {
            if (!$c) return '';
            if ($c['sesuai']) return 'SESUAI';
            $p = array();
            if ($c['kurang']) $p[] = 'KURANG ' . $c['kurang'];
            if ($c['lebih'])  $p[] = 'LEBIH ' . $c['lebih'];
            if ($c['salah'])  $p[] = 'SALAH ' . $c['salah'];
            if ($c['lain'])   $p[] = 'LAINNYA';
            return implode(', ', $p);
        };
        foreach ($d['rows'] as $b) {
            fputcsv($o, array(
                $b['noresi'], $b['pesanan'], $b['toko'], $b['mp'],
                $b['picker'] ? $b['picker']['label'] : '', $b['picker'] ? $b['picker']['waktu'] : '',
                $b['tandai']['label'], $b['tandai']['waktu'],
                $b['gb'] ? $b['gb']['label'] : '', $b['gb'] ? $b['gb']['waktu'] : '', $hasil($b['gb']),
                $b['pk'] ? $b['pk']['label'] : '', $b['pk'] ? $b['pk']['waktu'] : '', $hasil($b['pk']),
                $b['progres'],
            ));
        }
        fclose($o);
        exit();
    }

    public function detail()
    {
        $this->wajib(self::URI_REKAP);
        $d = $this->gdbr_fcd->detail($this->input->post('noresi'));
        $this->make_ajax_response($d ? 200 : 404, $d ? 'ok' : 'Resi tidak ditemukan di data GD-BR.', array('ok' => (bool) $d, 'detail' => $d));
    }
}
