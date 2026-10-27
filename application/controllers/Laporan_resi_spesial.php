<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu Laporan -> "Laporan Resi Spesial": per tanggal scan picker, jumlah resi
 * 1 SKU 1 QTY, jumlah yang discan sebagai 1_SKU_PICKER, dan persentasenya.
 * Target optimasi sejak 23 Okt 2026: minimal 50%. Baca-saja.
 */
class Laporan_resi_spesial extends MY_Controller
{
    const URI = 'laporan-resi-spesial';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('laporan_resi_spesial_fcd');
    }

    /** Halaman menu. Default filter: tanggal 1 bulan ini sampai hari ini. */
    public function index()
    {
        $this->show(array(
            'start' => date('Y-m-01'),
            'end'   => date('Y-m-d'),
        ));
    }

    /** Rentang tanggal 'Y-m-d' dari POST/GET; default 1 bulan ini sampai hari ini. */
    private function _rentang()
    {
        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && strtotime($t) !== false; };
        $start = $this->input->post_get('start_date');
        $end   = $this->input->post_get('end_date');
        $start = $ok($start) ? $start : date('Y-m-01');
        $end   = $ok($end) ? $end : date('Y-m-d');
        if ($start > $end) { $tmp = $start; $start = $end; $end = $tmp; }
        return array($start, $end);
    }

    /** Ekspor Excel seluruh tanggal pada rentang yang sedang tampil. Parameter lewat GET (unduhan). */
    public function export()
    {
        if (!$this->_boleh()) {
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        list($start, $end) = $this->_rentang();
        $rows = $this->laporan_resi_spesial_fcd->daftar($start, $end, 0, 0);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Laporan Resi Spesial');
        $sh->fromArray(array('Tanggal Scan Picker', '1 SKU 1 QTY', '1 SKU PICKER', 'Persen'), null, 'A1');
        $sh->getStyle('A1:D1')->getFont()->setBold(true);

        $baris = 2;
        foreach ($rows as $r) {
            $sh->setCellValue('A' . $baris, $r['tanggal']);
            $sh->setCellValue('B' . $baris, $r['satu_sku_satu_qty']);
            $sh->setCellValue('C' . $baris, $r['satu_sku_picker']);
            $sh->setCellValue('D' . $baris, $r['persen'] / 100);
            $baris++;
        }
        if ($baris > 2) {
            $sh->getStyle('D2:D' . ($baris - 1))->getNumberFormat()->setFormatCode('0.0%');
        }
        foreach (array('A' => 22, 'B' => 14, 'C' => 14, 'D' => 10) as $kol => $lebar) {
            $sh->getColumnDimension($kol)->setWidth($lebar);
        }

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan_Resi_Spesial_' . $start . '_sd_' . $end . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }

    /** Satu halaman baris (JSON). POST: start_date, end_date, size (10/20/50), page. */
    public function get_data()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }

        list($start, $end) = $this->_rentang();

        $size = (int) $this->input->post('size');
        if (!in_array($size, array(10, 20, 50), true)) $size = 10;

        $total   = $this->laporan_resi_spesial_fcd->hitung_tanggal($start, $end);
        $maks    = max(1, (int) ceil($total / $size));
        $halaman = min($maks, max(1, (int) $this->input->post('page')));

        $this->make_ajax_response(200, 'OK', array(
            'rows'  => $this->laporan_resi_spesial_fcd->daftar($start, $end, ($halaman - 1) * $size, $size),
            'total' => $total,
            'page'  => $halaman,
            'size'  => $size,
        ));
    }

    /** Hak akses lewat roleaccess menu ini (URL bisa dibuka langsung). */
    private function _boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;

        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', array('uri' => self::URI))->row();
        if (!$menu) return false;

        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))
            ->count_all_results('roleaccess') > 0;
    }
}
