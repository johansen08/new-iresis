<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PICKER -> "Bonus Picker": laporan hasil hitung bonus per picker.
 * Aturan hitung ada di Bonus_picker_fcd (baca-saja, tidak menulis data).
 */
class Bonus_picker extends MY_Controller
{
    /** Sejalan dengan MY_Controller::run_bonus_picker_migration(). */
    const ROLE_BOLEH = [1, 2, 6];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('bonus_picker_fcd');
    }

    /** Penolakan tetap JSON valid (pola sama dengan Lost_scan_picker). */
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
                'view'    => $this->load->view('bonus_picker/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Bonus Picker.');
    }

    public function index()
    {
        $data['awal']  = date('Y-m-01');
        $data['akhir'] = date('Y-m-d');
        $data['aturan'] = [
            'poin'    => Bonus_picker_fcd::POIN_PER_KESALAHAN,
            'target'  => Bonus_picker_fcd::TARGET_NET,
            'tier2'   => Bonus_picker_fcd::TIER2_NET,
            'bonus1'  => Bonus_picker_fcd::BONUS_TIER1,
            'bonus2'  => Bonus_picker_fcd::BONUS_TIER2,
        ];
        $this->show($data, 'bonus_picker/index');
    }

    /** Pesan error kalau rentang (yyyy-mm-dd) tidak valid, NULL kalau lolos. */
    private function cek_rentang($awal, $akhir)
    {
        $valid = function ($t) {
            $d = DateTime::createFromFormat('Y-m-d', $t);
            return $d && $d->format('Y-m-d') === $t;
        };
        if (!$valid($awal) || !$valid($akhir) || $awal > $akhir) {
            return 'Rentang tanggal tidak valid.';
        }
        if ((strtotime($akhir) - strtotime($awal)) / 86400 + 1 > Bonus_picker_fcd::MAKS_HARI) {
            return 'Rentang tanggal maksimal ' . Bonus_picker_fcd::MAKS_HARI . ' hari.';
        }
        return NULL;
    }

    /** Seluruh hasil hitung (ringkasan + rincian harian) untuk rentang tanggal. */
    public function get_data()
    {
        $awal  = (string) $this->input->post('awal');
        $akhir = (string) $this->input->post('akhir');

        $salah = $this->cek_rentang($awal, $akhir);
        if ($salah !== NULL) {
            $this->make_ajax_response(400, $salah);
        }

        $this->make_ajax_response(200, 'OK', [
            'pickers' => $this->bonus_picker_fcd->hitung($awal, $akhir),
        ]);
    }

    /** Unduh Excel: No. Absen, Nama, Total Bonus, Total Capai Target. */
    public function export_excel()
    {
        $awal  = (string) $this->input->get('awal');
        $akhir = (string) $this->input->get('akhir');

        $salah = $this->cek_rentang($awal, $akhir);
        if ($salah !== NULL) {
            $this->make_ajax_response(400, $salah);
        }

        $pickers = $this->bonus_picker_fcd->hitung($awal, $akhir);

        $xls   = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Bonus Picker');
        $sheet->fromArray(['No. Absen', 'Nama', 'Total Bonus', 'Total Capai Target'], NULL, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(TRUE);

        $baris = 2;
        foreach ($pickers as $p) {
            // No. absen disimpan sebagai teks 4 digit supaya "0339" tidak jadi 339.
            $sheet->setCellValueExplicit('A' . $baris, $p['absen'] === NULL ? '-' : sprintf('%04d', $p['absen']),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $baris, $p['nama']);
            $sheet->setCellValue('C' . $baris, (int) $p['bonus']);
            $sheet->setCellValue('D' . $baris, (int) $p['hari']);
            $baris++;
        }
        $sheet->getStyle('C2:C' . max($baris - 1, 2))->getNumberFormat()->setFormatCode('#,##0');
        foreach (['A', 'B', 'C', 'D'] as $kol) {
            $sheet->getColumnDimension($kol)->setAutoSize(TRUE);
        }

        $this->kirim_xlsx($xls, 'bonus_picker_' . date('d-m-Y', strtotime($awal)) . '_' . date('d-m-Y', strtotime($akhir)) . '.xlsx');
    }

    /** Unduh Excel rincian harian satu picker (isi sama dengan jendela Detail). */
    public function export_excel_detail()
    {
        $kode  = (int) $this->input->get('kode');
        $awal  = (string) $this->input->get('awal');
        $akhir = (string) $this->input->get('akhir');

        $salah = $this->cek_rentang($awal, $akhir);
        if ($salah !== NULL) {
            $this->make_ajax_response(400, $salah);
        }

        $picker = NULL;
        foreach ($this->bonus_picker_fcd->hitung($awal, $akhir) as $p) {
            if ($p['kode'] === $kode) {
                $picker = $p;
                break;
            }
        }
        if ($picker === NULL) {
            $this->make_ajax_response(404, 'Picker tidak ditemukan pada rentang ini.');
        }

        $xls   = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Detail Bonus');
        $sheet->fromArray(['Tanggal', 'Total Pick', 'Jumlah Kesalahan', 'Poin Kesalahan', 'Net', 'Bonus', 'Keterangan'], NULL, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(TRUE);

        $baris = 2;
        foreach ($picker['det'] as $d) {
            $sheet->setCellValue('A' . $baris, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($d['tgl'])));
            if ($d['spesial']) {
                $sheet->setCellValue('G' . $baris, 'Petugas Resi Spesial (tidak dihitung)');
            } else {
                $sheet->setCellValue('B' . $baris, (int) $d['pick']);
                $sheet->setCellValue('C' . $baris, (int) $d['salah']);
                $sheet->setCellValue('D' . $baris, (int) $d['poin']);
                $sheet->setCellValue('E' . $baris, (int) $d['net']);
                $sheet->setCellValue('F' . $baris, (int) $d['bonus']);
            }
            $baris++;
        }
        $akhir_data = max($baris - 1, 2);
        $sheet->getStyle('A2:A' . $akhir_data)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('B2:B' . $akhir_data)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E2:F' . $akhir_data)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A2:A' . $akhir_data)->getAlignment()->setHorizontal('left');

        // Baris total (hari petugas spesial tidak ikut karena selnya kosong).
        $sheet->setCellValue('A' . $baris, 'TOTAL');
        foreach (['B', 'C', 'D', 'F'] as $kol) {
            $sheet->setCellValue($kol . $baris, '=SUM(' . $kol . '2:' . $kol . $akhir_data . ')');
        }
        $sheet->setCellValue('G' . $baris, 'Capai target: ' . (int) $picker['hari'] . ' hari');
        $sheet->getStyle('A' . $baris . ':G' . $baris)->getFont()->setBold(TRUE);
        $sheet->getStyle('B' . $baris)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $baris)->getNumberFormat()->setFormatCode('#,##0');

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $kol) {
            $sheet->getColumnDimension($kol)->setAutoSize(TRUE);
        }

        $nama = preg_replace('/[^A-Za-z0-9]+/', '_', $picker['nama']);
        $this->kirim_xlsx($xls, 'bonus_picker_' . $nama . '_' . date('d-m-Y', strtotime($awal)) . '_' . date('d-m-Y', strtotime($akhir)) . '.xlsx');
    }

    private function kirim_xlsx($xls, $nama_file)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls))->save('php://output');
        exit();
    }
}
