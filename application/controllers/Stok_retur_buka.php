<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Stok Retur Buka" (TIM RETUR): stok barang retur sudah dibuka (KE_DISPLAY) yang belum
 * diverifikasi accounting. Hanya membaca. Definisi stok ada di Stok_retur_buka_fcd.
 */
class Stok_retur_buka extends MY_Controller
{
    const URI = 'retur/stok-retur-buka';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('stok_retur_buka_fcd');
    }

    /** Halaman menu. */
    public function index()
    {
        $this->show();
    }

    /** Data stok per SKU (JSON via make_ajax_response). */
    public function stok_data()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
            return;
        }
        $hasil = $this->stok_retur_buka_fcd->stok();
        $hasil['ok'] = true;
        $this->make_ajax_response(200, 'ok', $hasil);
    }

    /**
     * GET: q, jenis ('B,K' = Ekspedisi, Komplain; kosong = keduanya) -- sama dengan filter di layar.
     * Unduh xlsx rapi: header tebal rata tengah, garis tegak dan datar. Sheet 1 stok per SKU,
     * sheet 2 rincian per resi.
     */
    public function export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (!$this->_boleh()) {
            while (ob_get_level() > 0) ob_end_clean();
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }

        $q = $this->input->get('q');
        $q = is_string($q) ? mb_strtolower(trim($q)) : '';
        $jenis_raw = $this->input->get('jenis');
        $jenis = is_string($jenis_raw) && $jenis_raw !== ''
            ? array_values(array_intersect(explode(',', $jenis_raw), array('B', 'K')))
            : array();

        // Aturan sama dengan penyaringan di layar: satu SKU lolos bila ada resinya yang cocok jenis,
        // dan teks pencarian cocok di SKU / rak / resi / no. pesanan.
        $hasil = $this->stok_retur_buka_fcd->stok();
        $rows = array();
        foreach ($hasil['rows'] as $r) {
            if ($jenis) {
                $ada = false;
                foreach ($r['resi'] as $p) {
                    if (in_array($p['komplain'] ? 'K' : 'B', $jenis, true)) { $ada = true; break; }
                }
                if (!$ada) continue;
            }
            if ($q !== '') {
                $gabung = $r['sku'] . ' ' . $r['rak'];
                foreach ($r['resi'] as $p) $gabung .= ' ' . $p['noresi'] . ' ' . $p['no_pesanan'];
                if (mb_strpos(mb_strtolower($gabung), $q) === false) continue;
            }
            $rows[] = $r;
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ---- Sheet 1: stok per SKU
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Stok per SKU');
        $head1 = array('No', 'SKU', 'Nama SKU', 'No. Rak', 'Stok', 'Jumlah Resi', 'Jenis');
        $baris1 = array();
        foreach ($rows as $i => $r) {
            $k = $b = false;
            foreach ($r['resi'] as $p) { if ($p['komplain']) $k = true; else $b = true; }
            $baris1[] = array($i + 1, $r['sku'], $r['nama'], $r['rak'], (int) $r['stok'], (int) $r['jumlah_resi'],
                ($b && $k) ? 'Ekspedisi + Komplain' : ($k ? 'Komplain' : 'Ekspedisi'));
        }
        $this->_tulis_sheet($ws, $head1, $baris1, array(6, 26, 40, 14, 10, 14, 22), array(2, 3, 4), array(5, 6));

        // ---- Sheet 2: rincian per resi
        $ws2 = $ss->createSheet();
        $ws2->setTitle('Rincian Resi');
        $head2 = array('No', 'SKU', 'No. Rak', 'No. Resi', 'No. Pesanan', 'Toko', 'Jenis', 'Tanggal Buka', 'Qty');
        $baris2 = array();
        $no = 1;
        foreach ($rows as $r) {
            foreach ($r['resi'] as $p) {
                $baris2[] = array($no++, $r['sku'], $r['rak'], $p['noresi'], $p['no_pesanan'], $p['toko'],
                    $p['komplain'] ? 'Komplain' : 'Ekspedisi',
                    $p['tgl'] ? date('d/m/Y', strtotime($p['tgl'])) . ' ' . $p['jam'] : '-', (int) $p['qty']);
            }
        }
        $this->_tulis_sheet($ws2, $head2, $baris2, array(6, 26, 14, 24, 24, 24, 14, 18, 8), array(2, 3, 4, 5, 6), array(9));

        $ss->setActiveSheetIndex(0);
        $nama_berkas = 'stok-retur-buka-' . date('Ymd-His') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit();
    }

    /**
     * Isi satu sheet dan rapikan: header tebal berlatar hijau, semua sel rata tengah, garis tegak & datar,
     * header dibekukan, filter otomatis. $kol_teks = nomor kolom (mulai 1) yang ditulis sebagai teks
     * eksplisit (SKU / resi angka panjang), $kol_angka = kolom angka.
     */
    private function _tulis_sheet($ws, array $head, array $baris, array $lebar, array $kol_teks, array $kol_angka)
    {
        $jml = count($head);
        $akhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($jml);
        foreach ($head as $i => $nama) {
            $ws->setCellValueByColumnAndRow($i + 1, 1, $nama);
        }
        $r = 2;
        foreach ($baris as $row) {
            foreach ($row as $i => $v) {
                $c = $i + 1;
                if (in_array($c, $kol_teks, true)) {
                    $ws->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r, (string) $v,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $ws->setCellValueByColumnAndRow($c, $r, $v);
                }
            }
            $r++;
        }
        $terakhir = max(2, $r - 1);

        $rentang = 'A1:' . $akhir . $terakhir;
        $ws->getStyle($rentang)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        $ws->getStyle($rentang)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        foreach ($kol_angka as $c) {
            $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $ws->getStyle($huruf . '2:' . $huruf . $terakhir)->getNumberFormat()->setFormatCode('#,##0');
        }
        $head_style = $ws->getStyle('A1:' . $akhir . '1');
        $head_style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head_style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
        $ws->getRowDimension(1)->setRowHeight(26);
        foreach ($lebar as $i => $w) {
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $ws->freezePane('A2');
        if ($r > 2) {
            $ws->setAutoFilter('A1:' . $akhir . $terakhir);
        }
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
