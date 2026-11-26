<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Kategori SKU": daftar baca-saja SKU + gambar + no rak + kategori.
 * (Mengisi kategori lewat Excel ada di controller Kategori_sku.) Lihat docs/KATEGORI_SKU.md.
 */
class Daftar_kategori_sku extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_daftar_kategori_sku_migration(). */
    const ROLE_BOLEH = [1, 7];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('daftar_kategori_sku_fcd');
        $this->load->model('kategori_sku_fcd');
    }

    /** Penolakan tetap JSON valid (show_404 mengirim HTML dan merusak SPA). */
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
                'view'    => $this->load->view('kategori_sku/daftar', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Kategori SKU.');
    }

    public function index()
    {
        $this->show([
            'kategori' => $this->daftar_kategori_sku_fcd->daftar_kategori(),
            'belum_ada' => Daftar_kategori_sku_fcd::BELUM_ADA,
            'upload_terakhir' => $this->kategori_sku_fcd->upload_terakhir(),
        ], 'kategori_sku/daftar');
    }

    /** POST: q, kat[] (kategori terpilih; kosong = semua), halaman, ukuran (25/50/100). */
    public function get_data()
    {
        $kat = array_values(array_filter((array) $this->input->post('kat'), 'is_string'));
        $this->make_ajax_response(200, 'OK', $this->daftar_kategori_sku_fcd->halaman(
            (string) $this->input->post('q'),
            $kat,
            (int) $this->input->post('halaman'),
            (int) $this->input->post('ukuran')
        ));
    }

    /** GET: q, kat[] (sama dengan layar). Unduh semua SKU yang cocok sebagai xlsx rapi: header tebal, rata tengah, garis tegak dan datar. */
    public function export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $kat   = array_values(array_filter((array) $this->input->get('kat'), 'is_string'));
        $baris = $this->daftar_kategori_sku_fcd->semua((string) $this->input->get('q'), $kat);

        $kolom = ['No', 'SKU', 'No Rak', 'Kategori SKU'];
        $akhir = 'D';
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Kategori SKU');
        foreach ($kolom as $i => $nama) {
            $ws->setCellValueByColumnAndRow($i + 1, 1, $nama);
        }
        $r = 2;
        foreach ($baris as $i => $b) {
            $ws->setCellValueByColumnAndRow(1, $r, $i + 1);
            // Teks eksplisit supaya SKU angka panjang tidak berubah jadi notasi ilmiah / rumus.
            foreach ([2 => (string) $b['id_sku'], 3 => (string) $b['no_rak'], 4 => trim((string) $b['kategori_sku'])] as $c => $v) {
                $ws->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $r++;
        }
        $terakhir = max(2, $r - 1);

        $rentang = 'A1:' . $akhir . $terakhir;
        $ws->getStyle($rentang)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        $ws->getStyle($rentang)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $head = $ws->getStyle('A1:' . $akhir . '1');
        $head->getFont()->setBold(TRUE)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $ws->getRowDimension(1)->setRowHeight(24);
        foreach ([6, 26, 18, 18] as $i => $w) {
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $ws->freezePane('A2');
        if ($r > 2) {
            $ws->setAutoFilter('A1:' . $akhir . $terakhir);
        }

        $nama_berkas = 'kategori-sku-' . date('Ymd-His') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit();
    }
}
