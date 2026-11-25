<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Dashboard Retur New" (TIM RETUR): progres retur 6 tahap + peringkat per Marketplace, Kurir, dan Toko.
 * Hanya membaca data; angka diambil dari Daftar_retur_fcd::get_list() lewat Dashboard_retur_fcd, jadi selalu
 * sama dengan menu Daftar Retur Penjualan (tab Semua).
 */
class Dashboard_retur_new extends MY_Controller
{
    const URI = 'retur/dashboard-retur-new';

    /** Umur cache hasil ringkasan (detik). Tombol Segarkan melewatinya. */
    const CACHE_DETIK = 120;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_retur_fcd');
    }

    /** Halaman menu. */
    public function index()
    {
        $this->show(array());
    }

    /** POST: start_date, end_date (Y-m-d), segarkan (1 = lewati cache). Balasan JSON ringkasan sel. */
    public function get_data()
    {
        ini_set('memory_limit', '3072M');
        set_time_limit(0);
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        if (!$this->_boleh()) {
            echo json_encode(array('ok' => false, 'error' => 'Anda tidak punya akses ke menu ini.'));
            return;
        }

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
        $start = $this->input->post('start_date');
        $end = $this->input->post('end_date');
        $start = $ok($start) ? $start : date('Y-01-01');
        $end = $ok($end) ? $end : date('Y-m-d');
        $segarkan = $this->input->post('segarkan') === '1';

        $berkas = APPPATH . 'cache/dashboard_retur_new_' . md5($start . '|' . $end) . '.json';
        if (!$segarkan && is_file($berkas) && (time() - filemtime($berkas)) < self::CACHE_DETIK) {
            $isi = @file_get_contents($berkas);
            if ($isi !== false && $isi !== '') {
                echo $isi;
                return;
            }
        }

        try {
            $hasil = $this->dashboard_retur_fcd->ringkas($start . ' 00:00:00', $end . ' 23:59:59', $segarkan);
        } catch (Throwable $e) {
            log_message('error', 'Dashboard_retur_new::get_data: ' . $e->getMessage());
            echo json_encode(array('ok' => false, 'error' => 'Gagal menghitung dashboard. Coba segarkan.'));
            return;
        }
        $hasil['ok'] = true;
        $hasil['start'] = $start;
        $hasil['end'] = $end;
        $json = json_encode($hasil);
        @file_put_contents($berkas, $json);
        echo $json;
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
