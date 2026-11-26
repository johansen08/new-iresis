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
            'bulan_jual' => array_column($this->daftar_kategori_sku_fcd->bulan_penjualan(), 'label'),
        ], 'kategori_sku/daftar');
    }

    /** POST: q, kat[] (kategori terpilih; kosong = semua), halaman, ukuran (25/50/100), urut (indeks bulan), arah (asc/desc). */
    public function get_data()
    {
        $kat = array_values(array_filter((array) $this->input->post('kat'), 'is_string'));
        $q       = (string) $this->input->post('q');
        $halaman = (int) $this->input->post('halaman');
        $ukuran  = (int) $this->input->post('ukuran');
        $urut    = (string) $this->input->post('urut');
        $arah    = (string) $this->input->post('arah');
        // Lepas kunci session supaya permintaan lain dari pengguna yang sama tidak antre.
        session_write_close();

        $penjualan = $this->daftar_kategori_sku_fcd->penjualan();
        $data      = $this->daftar_kategori_sku_fcd->halaman($q, $kat, $halaman, $ukuran, $penjualan, $urut, $arah);
        foreach ($data['baris'] as &$b) {
            $b['jual']  = $this->daftar_kategori_sku_fcd->penjualan_sku($penjualan, $b['id_sku']);
            $b['belum'] = $this->daftar_kategori_sku_fcd->belum_final_sku($penjualan, $b['id_sku']);
        }
        unset($b);
        $data['jual_info'] = $this->daftar_kategori_sku_fcd->info_penjualan($penjualan);

        $this->make_ajax_response(200, 'OK', $data);
    }

    /** POST: hitung ulang penjualan sekarang (tombol "Hitung ulang" dan pembaruan otomatis tiap 30 menit). */
    public function hitung_ulang()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        session_write_close();
        // Hitungan tetap diselesaikan walau pengguna menutup halaman.
        ignore_user_abort(TRUE);

        $status = $this->daftar_kategori_sku_fcd->hitung_ulang();
        if ($status === 'gagal') {
            $this->make_ajax_response(500, 'Penjualan gagal dihitung. Angka sebelumnya tetap dipakai.');
        }
        $this->make_ajax_response(200, 'OK', [
            'status' => $status,
            'info'   => $this->daftar_kategori_sku_fcd->info_penjualan($this->daftar_kategori_sku_fcd->penjualan()),
        ]);
    }

    /** GET: progres hitungan, dipantau layar selama hitungan berjalan. */
    public function status_hitung()
    {
        session_write_close();
        $this->make_ajax_response(200, 'OK', $this->daftar_kategori_sku_fcd->status_hitung());
    }

    /** GET: q, kat[], urut, arah (sama dengan layar). Unduh semua SKU yang cocok sebagai xlsx rapi: header tebal, rata tengah, garis tegak dan datar. */
    public function export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $kat   = array_values(array_filter((array) $this->input->get('kat'), 'is_string'));
        $q     = (string) $this->input->get('q');
        session_write_close();
        $urut  = (string) $this->input->get('urut');
        $arah  = (string) $this->input->get('arah');
        $bulan = array_column($this->daftar_kategori_sku_fcd->bulan_penjualan(), 'label');
        // File unduhan harus memuat angka segar: hitung dulu bila cache basi. Bila sedang dihitung orang lain
        // atau gagal, angka tersimpan yang dipakai.
        $penjualan = $this->daftar_kategori_sku_fcd->penjualan();
        if ($this->daftar_kategori_sku_fcd->info_penjualan($penjualan)['basi']) {
            $this->daftar_kategori_sku_fcd->hitung_ulang();
            $penjualan = $this->daftar_kategori_sku_fcd->penjualan();
        }
        $baris = $this->daftar_kategori_sku_fcd->semua($q, $kat, $penjualan, $urut, $arah);

        $kolom = array_merge(['No', 'SKU', 'No Rak', 'Kategori SKU'], $bulan);
        $akhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($kolom));
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
            // Pcs sebagai angka supaya bisa dijumlah / diurutkan di Excel; kosong bila data tidak tersedia.
            $belum = $this->daftar_kategori_sku_fcd->belum_final_sku($penjualan, $b['id_sku']);
            foreach ($this->daftar_kategori_sku_fcd->penjualan_sku($penjualan, $b['id_sku']) as $j => $pcs) {
                if ($pcs === null) continue;
                $ws->setCellValueByColumnAndRow(5 + $j, $r, $pcs);
                if ($belum[$j] > 0) {
                    $ws->getStyleByColumnAndRow(5 + $j, $r)->getFont()->setBold(TRUE)->getColor()->setRGB('EA580C');
                }
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
        foreach (array_merge([6, 26, 18, 18], array_fill(0, count($bulan), 14)) as $i => $w) {
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        if ($r > 2) {
            $ws->getStyle('E2:' . $akhir . $terakhir)->getNumberFormat()->setFormatCode('#,##0');
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
