<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tiga menu CS untuk stok top level (TIM CS):
 *   cs/request-top-level  Request Top Level  - cetak slip 100x150, input qty dari slip sampai selesai
 *   cs/upload-top-level   Upload Top Level   - unggah Excel stok dari tim Restok (header baris ke-3)
 *   cs/stok-top-level     Stok Top Level     - total per SKU, rincian per rak, koreksi
 *
 * Tim Restok tidak memakai sistem: mereka bekerja dari slip kertas. Log perubahan disimpan di
 * tbltoplevel_log dan belum punya tampilan. Alur dan aturan lengkap di docs/TOP_LEVEL.md.
 */
class Top_level extends MY_Controller
{
    const URI_REQUEST = 'cs/request-top-level';
    const URI_UPLOAD  = 'cs/upload-top-level';
    const URI_STOK    = 'cs/stok-top-level';
    const URI_INPUT   = 'cs/input-top-level';
    const URI_TRANSFER = 'cs/daftar-transfer-top-level';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('top_level_fcd');
    }

    // ------------------------------------------------------------ halaman

    public function request()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh(self::URI_REQUEST), 'akun' => self::akun_tampil($this->_oleh()), 'hari_ini' => date('Y-m-d')));
    }

    public function upload()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh(self::URI_UPLOAD), 'maks_baris' => Top_level_fcd::MAKS_BARIS));
    }

    public function input_form()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh(self::URI_INPUT), 'akun_login' => $this->_oleh()), 'top_level/input');
    }

    // ------------------------------------------------------------ Input Top Level

    /** Rekomendasi nama petugas list dan petugas input (terakhir dipakai, lalu pegawai aktif) mengikuti ketikan. */
    /** Pemeriksaan duplikat langsung saat mengetik: baris yang SKU + rak-nya sudah tersimpan dengan stok. */
    public function input_cek()
    {
        $this->_wajib(self::URI_INPUT);
        $rows = $this->input->post('rows');
        $dobel = $this->top_level_fcd->cek_duplikat(is_array($rows) ? array_values($rows) : array());
        $keluar = array();
        foreach ($dobel as $k => $qty) {
            list($sku, $rak) = explode('|', $k, 2);
            $keluar[] = array('sku' => $sku, 'rak' => $rak, 'qty' => $qty);
        }
        $this->make_ajax_response(200, 'OK', array('rows' => $keluar));
    }

    public function input_tim()
    {
        $this->_wajib(self::URI_INPUT);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->rekomendasi_tim($this->input->post('q'))));
    }

    public function input_sku()
    {
        $this->_wajib(self::URI_INPUT);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->cari_sku($this->input->post('q'))));
    }

    /** Saran no rak: keluar = rak yang menyimpan stok SKU, masuk = semua rak sesuai ketikan. */
    public function input_rak()
    {
        $this->_wajib(self::URI_INPUT);
        $arah = (string) $this->input->post('arah') === 'KELUAR' ? 'KELUAR' : 'MASUK';
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->cari_rak($this->input->post('sku'), $this->input->post('q'), $arah)));
    }

    /** Menyimpan input stok masuk dan mengembalikan nomor transfer TTL. */
    public function input_simpan()
    {
        $this->_wajib(self::URI_INPUT);
        $arah = (string) $this->input->post('arah');
        if ($arah !== 'MASUK' && $arah !== 'KELUAR') {
            $this->make_ajax_response(409, 'Pilih arah: Masuk atau Keluar.', array());
            return;
        }
        $rows = $this->input->post('rows');
        $h = $this->top_level_fcd->input_masuk((string) $this->input->post('petugas_list'), is_array($rows) ? array_values($rows) : array(), $this->_oleh(), (string) $this->input->post('tanggal'), (string) $this->input->post('petugas_input'), (string) $this->input->post('tanggal_list'), $arah);
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Tersimpan, transfer ' . $h['nomor'] . '.' : $h['pesan'], $h['ok'] ? array('nomor' => $h['nomor'], 'baris' => $h['baris'], 'qty' => $h['qty'], 'tanggal' => $h['tanggal'], 'tanggal_list' => $h['tanggal_list']) : array());
    }

    // ------------------------------------------------------------ Daftar Transfer Top Level

    public function transfer_form()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh(self::URI_TRANSFER), 'hari_ini' => date('Y-m-d')), 'top_level/transfer');
    }

    public function transfer_data()
    {
        $this->_wajib(self::URI_TRANSFER);
        $sumber = $this->input->post('sumber');
        $h = $this->top_level_fcd->daftar_transfer(
            (string) $this->input->post('arah'), is_array($sumber) ? $sumber : array(), $this->input->post('q'), (string) $this->input->post('dari'),
            (string) $this->input->post('sampai'), $this->input->post('halaman'), $this->input->post('ukuran')
        );
        $this->make_ajax_response(200, 'OK', $h);
    }

    public function transfer_detail()
    {
        $this->_wajib(self::URI_TRANSFER);
        $d = $this->top_level_fcd->detail_transfer((int) $this->input->post('id'));
        $d === null ? $this->make_ajax_response(404, 'Transfer tidak ditemukan.') : $this->make_ajax_response(200, 'OK', array('d' => $d));
    }

    /** Simpan edit satu transfer: petugas list/input dan baris (SKU, rak, qty); stok ikut bergeser sesuai selisih. */
    public function transfer_edit()
    {
        $this->_wajib(self::URI_TRANSFER);
        $baris = $this->input->post('baris');
        $h = $this->top_level_fcd->edit_transfer(
            (int) $this->input->post('id'), (string) $this->input->post('nama_tim'), (string) $this->input->post('nama_input'),
            is_array($baris) ? array_values($baris) : array(), (string) $this->input->post('alasan'), $this->_oleh()
        );
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['pesan']);
    }

    /** Saran SKU dari master (tblsku) untuk isian edit baris. */
    public function transfer_sku()
    {
        $this->_wajib(self::URI_TRANSFER);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->cari_sku($this->input->post('q'))));
    }

    /** Saran nama petugas untuk isian edit petugas list/input. */
    public function transfer_tim()
    {
        $this->_wajib(self::URI_TRANSFER);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->rekomendasi_tim($this->input->post('q'))));
    }

    public function stok()
    {
        $this->show(array('akses_ditolak' => !$this->_boleh(self::URI_STOK)));
    }

    // ------------------------------------------------------------ Request Top Level

    public function request_data()
    {
        $this->_wajib(self::URI_REQUEST);
        $this->make_ajax_response(200, 'OK', array(
            'sekarang' => date('Y-m-d H:i:s'),
            'hitung' => $this->top_level_fcd->hitung_request(),
            'rows'   => $this->top_level_fcd->daftar_request($this->input->post('filter'), $this->input->post('q')),
        ));
    }

    public function request_detail()
    {
        $this->_wajib(self::URI_REQUEST);
        $d = $this->top_level_fcd->detail_request((int) $this->input->post('id'));
        $d === null ? $this->make_ajax_response(404, 'Request tidak ditemukan.') : $this->make_ajax_response(200, 'OK', array('d' => $d));
    }

    /** Mencetak slip: cetak pertama menandai status Di Restok. Mengembalikan detail terbaru untuk dicetak. */
    public function request_cetak()
    {
        $this->_wajib(self::URI_REQUEST);
        $id = (int) $this->input->post('id');
        $h = $this->top_level_fcd->cetak($id, $this->_oleh());
        if (!$h['ok']) {
            $this->make_ajax_response(409, $h['pesan']);
        }
        $this->make_ajax_response(200, 'Slip dicetak.', array('d' => $this->top_level_fcd->detail_request($id)));
    }

    /** Input qty dari slip yang kembali dari Restok; status langsung SELESAI. */
    public function request_selesai()
    {
        $this->_wajib(self::URI_REQUEST);
        $rows = $this->input->post('rows');
        $h = $this->top_level_fcd->selesai((int) $this->input->post('id'), is_array($rows) ? array_values($rows) : array(), $this->_oleh(), (string) $this->input->post('petugas_list'), (bool) $this->input->post('qty_nol'));
        $pesan = $h['ok'] ? ($h['total'] > 0 ? 'Selesai. Stok diperbarui, transfer ' . $h['nomor'] . '.' : 'Selesai dengan qty 0, stok tidak berubah, transfer ' . $h['nomor'] . '.') : $h['pesan'];
        $this->make_ajax_response($h['ok'] ? 200 : 409, $pesan, $h['ok'] ? array('total' => $h['total'], 'nomor' => $h['nomor']) : array());
    }

    /** Saran nama Petugas List untuk input qty (terakhir dipakai, lalu pegawai aktif) mengikuti ketikan. */
    public function request_tim()
    {
        $this->_wajib(self::URI_REQUEST);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->rekomendasi_tim($this->input->post('q'))));
    }

    public function request_batal()
    {
        $this->_wajib(self::URI_REQUEST);
        $h = $this->top_level_fcd->batal((int) $this->input->post('id'), $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Request dibatalkan.' : $h['pesan']);
    }

    // ------------------------------------------------------------ Stok Top Level

    public function stok_data()
    {
        $this->_wajib(self::URI_STOK);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->stok_list($this->input->post('q'))));
    }

    /** Unduh Excel stok top level: sheet DATA BASE (SKU, NO RAK, STOCK per rak) dan REKAP (satu baris per SKU), format mengikuti contoh tim CS. */
    public function stok_export()
    {
        $this->_wajib(self::URI_STOK);
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $xls = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $teks = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
        $data = $this->top_level_fcd->stok_export();

        // DATA BASE urut NO RAK (natural: A2 sebelum A10), lalu SKU; REKAP urut SKU.
        usort($data, function ($a, $b) {
            $c = strnatcasecmp((string) $a['no_rak'], (string) $b['no_rak']);
            return $c !== 0 ? $c : strnatcasecmp((string) $a['sku'], (string) $b['sku']);
        });
        $per_sku = array();
        foreach ($data as $r) {
            $per_sku[$r['sku']][] = array('rak' => (string) $r['no_rak'], 'qty' => (int) $r['qty']);
        }
        uksort($per_sku, function ($a, $b) {
            return strnatcasecmp((string) $a, (string) $b);
        });

        // Baris 1 kedua sheet: SUBTOTAL per kolom (ikut filter), kuning, comma style bilangan bulat.
        // $fungsi per kolom: 109 = jumlah (kolom angka), unik = hitung nilai unik (kolom teks).
        $subtotal = function ($sheet, array $fungsi, $akhir, $kolom_akhir) {
            foreach ($fungsi as $kol => $kode) {
                $rng = $kol . '4:' . $kol . max($akhir, 4);
                // 'unik' = jumlah nilai unik di kolom (tidak ikut filter); data kosong = 0.
                $sheet->setCellValue($kol . '1', $kode === 'unik'
                    ? ($akhir >= 4 ? '=SUMPRODUCT(1/COUNTIF(' . $rng . ',' . $rng . '))' : 0)
                    : '=SUBTOTAL(' . $kode . ',' . $rng . ')');
            }
            // Garis tipis horizontal dan vertikal di seluruh tabel (baris 1 sampai data terakhir).
            $sheet->getStyle('A1:' . $kolom_akhir . max($akhir, 3))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            // Kuning, tebal, dan comma style hanya di kolom yang berisi rumus.
            foreach (array_keys($fungsi) as $kol) {
                $st = $sheet->getStyle($kol . '1');
                $st->getFont()->setBold(true);
                $st->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                $st->getNumberFormat()->setFormatCode('_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)');
            }
        };

        // Kolom Cek: kotak kosong untuk dicentang tangan setelah dicetak.
        $kotak = function ($sheet, $kol, $akhir) {
            $sheet->setCellValue($kol . '2', 'Cek');
            for ($i = 4; $i <= $akhir; $i++) {
                $sheet->setCellValueExplicit($kol . $i, "\u{2610}", \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            if ($akhir >= 4) {
                $st = $sheet->getStyle($kol . '4:' . $kol . $akhir);
                $st->getFont()->setSize(14);
                $st->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            $sheet->getColumnDimension($kol)->setWidth(8);
            // Cetak: judul kolom diulang tiap halaman, lebar muat satu halaman.
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
            $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageSetup()->setFitToPage(true);
        };

        $warna_banyak_rak = 'FFE699'; // SKU yang tersebar di lebih dari 1 rak
        // Sheet DATA BASE: judul di baris 2, data mulai baris 4 (baris 1 ringkasan, baris 3 hanya tombol filter), satu baris per SKU per rak.
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('DATA BASE');
        $sheet->fromArray(array('SKU', 'NO RAK', 'STOCK', 'Rak > 1', 'Cek'), null, 'A2');
        $sheet->getStyle('A2:E2')->getFont()->setBold(true);
        $sheet->getStyle('A2:E2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A2:E2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $baris = 4;
        foreach ($data as $r) {
            $sheet->setCellValueExplicit('A' . $baris, (string) $r['sku'], $teks);
            $sheet->setCellValueExplicit('B' . $baris, (string) $r['no_rak'], $teks);
            $sheet->setCellValue('C' . $baris, (int) $r['qty']);
            $sheet->setCellValue('D' . $baris, count($per_sku[$r['sku']]) > 1 ? 1 : 0);
            if (count($per_sku[$r['sku']]) > 1) {
                $sheet->getStyle('A' . $baris)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($warna_banyak_rak);
            }
            $baris++;
        }
        if ($baris > 4) {
            $sheet->getStyle('C4:C' . ($baris - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D4:D' . ($baris - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
        foreach (array('A' => 22, 'B' => 20, 'C' => 10, 'D' => 10) as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $subtotal($sheet, array('A' => 'unik', 'B' => 'unik', 'C' => 109, 'D' => 109), $baris - 1, 'E');
        $kotak($sheet, 'E', $baris - 1);
        $sheet->setAutoFilter('A3:E' . max($baris - 1, 3)); // filter di baris 3, seperti contoh
        $sheet->freezePane('C4');

        // Sheet REKAP: satu baris per SKU, rak ditulis "no rak ( stok ) / no rak ( stok )".
        $rekap = $xls->createSheet();
        $rekap->setTitle('REKAP');
        $rekap->fromArray(array('SKU', 'STOCK', 'NO RAK', 'Rak > 1', 'JLH LOKASI', 'Cek'), null, 'A2');
        $rekap->getStyle('A2:F2')->getFont()->setBold(true);
        $rekap->getStyle('A2:F2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $rekap->getStyle('A2:F2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $baris = 4;
        foreach ($per_sku as $sku => $raks) {
            $total = 0;
            $teks_rak = array();
            foreach ($raks as $x) {
                $total += $x['qty'];
                $teks_rak[] = $x['rak'] . ' ( ' . number_format($x['qty']) . ' )';
            }
            $rekap->setCellValueExplicit('A' . $baris, (string) $sku, $teks);
            $rekap->setCellValue('B' . $baris, $total);
            // Satu rak: cukup no rak (stok = stok total di kolom STOCK); lebih dari satu: no rak beserta stoknya.
            $rekap->setCellValueExplicit('C' . $baris, count($raks) > 1 ? implode(' / ', $teks_rak) : $raks[0]['rak'], $teks);
            $rekap->setCellValue('D' . $baris, count($raks) > 1 ? 1 : 0);
            $rekap->setCellValue('E' . $baris, count($raks));
            if (count($raks) > 1) {
                $rekap->getStyle('A' . $baris)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($warna_banyak_rak);
            }
            $baris++;
        }
        if ($baris > 4) {
            $rekap->getStyle('B4:B' . ($baris - 1))->getNumberFormat()->setFormatCode('_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)');
            $rekap->getStyle('D4:D' . ($baris - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $rekap->getStyle('E4:E' . ($baris - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
        foreach (array('A' => 22, 'B' => 20, 'C' => 60, 'D' => 10, 'E' => 14) as $kol => $lebar) {
            $rekap->getColumnDimension($kol)->setWidth($lebar);
        }
        $subtotal($rekap, array('A' => 'unik', 'B' => 109, 'D' => 109), $baris - 1, 'F');
        $kotak($rekap, 'F', $baris - 1);
        $rekap->setAutoFilter('A3:F' . max($baris - 1, 3));
        $rekap->freezePane('C4');
        $xls->setActiveSheetIndex(0);
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Stok_Top_Level_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);
        $writer->save('php://output');
        exit;
    }

    public function stok_detail()
    {
        $this->_wajib(self::URI_STOK);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->rak_sku(trim((string) $this->input->post('sku')))));
    }

    public function stok_koreksi()
    {
        $this->_wajib(self::URI_STOK);
        $rows = $this->input->post('rows');
        $h = $this->top_level_fcd->koreksi(trim((string) $this->input->post('sku')), is_array($rows) ? array_values($rows) : array(), (string) $this->input->post('alasan'), $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Stok top level diperbarui, transfer ' . implode(', ', $h['nomor']) . '.' : $h['pesan']);
    }

    /** Saran SKU dari master (tblsku) untuk isian ganti SKU. */
    public function stok_sku()
    {
        $this->_wajib(self::URI_STOK);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->cari_sku($this->input->post('q'))));
    }

    public function stok_riwayat()
    {
        $this->_wajib(self::URI_STOK);
        $this->make_ajax_response(200, 'OK', array('rows' => $this->top_level_fcd->stok_riwayat(trim((string) $this->input->post('sku')), strtoupper(trim((string) $this->input->post('rak'))))));
    }

    /** Simpan perubahan satu SKU: qty per rak, ganti no rak, ganti SKU (harus ada di master). */
    public function stok_simpan()
    {
        $this->_wajib(self::URI_STOK);
        $rows = $this->input->post('rows');
        $h = $this->top_level_fcd->ubah_stok(trim((string) $this->input->post('sku')), trim((string) $this->input->post('sku_baru')), is_array($rows) ? array_values($rows) : array(), (string) $this->input->post('alasan'), $this->_oleh());
        $this->make_ajax_response($h['ok'] ? 200 : 409, $h['ok'] ? 'Stok top level diperbarui, transfer ' . implode(', ', $h['nomor']) . '.' : $h['pesan']);
    }

    // ------------------------------------------------------------ Upload Top Level

    /**
     * Unggah Excel. dry=1: hanya memeriksa dan mengembalikan semua baris dengan statusnya (pratinjau);
     * dry=0: menyimpan baris yang siap. Header di baris ke-3, data mulai baris ke-4.
     * Kolom A-I: SKU, NO RAK, QTY, AC NO LIST, NAMA LIST, TGL LIST, AC NO INPUT, NAMA INPUT, TGL INPUT.
     */
    public function upload_proses()
    {
        $this->_wajib(self::URI_UPLOAD);
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        try {
            if (!isset($_FILES['berkas']) || $_FILES['berkas']['error'] != 0) {
                throw new \Exception('File Excel tidak ditemukan atau gagal diunggah.');
            }
            $ext = strtolower(pathinfo($_FILES['berkas']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, array('xls', 'xlsx', 'csv'), true)) {
                throw new \Exception('Format file harus .xls, .xlsx, atau .csv.');
            }
            $dry = $this->input->post('dry') !== '0';

            $path = $_FILES['berkas']['tmp_name'];
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(\PhpOffice\PhpSpreadsheet\IOFactory::identify($path));
            $reader->setReadDataOnly(true);
            $mentah = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

            if (count($mentah) < 3) {
                throw new \Exception('Header harus ada di baris ke-3. File ini hanya berisi ' . count($mentah) . ' baris.');
            }
            $salah = array();
            $judul = array('SKU', 'NO RAK', 'QTY', 'AC NO LIST', 'NAMA LIST', 'TGL LIST', 'AC NO INPUT', 'NAMA INPUT', 'TGL INPUT');
            foreach ($judul as $i => $h) {
                $ada = isset($mentah[2][$i]) ? strtoupper(trim(preg_replace('/\s+/', ' ', (string) $mentah[2][$i]))) : '';
                if ($ada !== $h) {
                    $salah[] = 'Kolom ' . chr(65 + $i) . ' baris 3 harus "' . $h . '", ditemukan "' . ($ada === '' ? 'kosong' : $ada) . '".';
                }
            }
            if ($salah) {
                $this->make_ajax_response(422, 'Header tidak sesuai.', array('header_salah' => $salah));
            }
            if (count($mentah) - 3 > Top_level_fcd::MAKS_BARIS) {
                throw new \Exception('Maksimal ' . Top_level_fcd::MAKS_BARIS . ' baris data per unggahan.');
            }

            $baris = array();
            $tgl_salah = array();
            for ($i = 3; $i < count($mentah); $i++) {
                $r = $mentah[$i];
                $kosong = true;
                for ($c = 0; $c < 9; $c++) {
                    if (isset($r[$c]) && trim((string) $r[$c]) !== '') { $kosong = false; break; }
                }
                if ($kosong) continue;

                $qty_mentah = isset($r[2]) ? $r[2] : '';
                $qty = (is_numeric($qty_mentah) && (float) $qty_mentah == (int) $qty_mentah) ? (int) $qty_mentah : trim((string) $qty_mentah);
                $tl = Top_level_fcd::baca_tanggal(isset($r[5]) ? $r[5] : '');
                $ti = Top_level_fcd::baca_tanggal(isset($r[8]) ? $r[8] : '');
                if ((isset($r[5]) && trim((string) $r[5]) !== '' && $tl === '') || (isset($r[8]) && trim((string) $r[8]) !== '' && $ti === '')) {
                    $tgl_salah[$i + 1] = true;
                }
                $baris[] = array(
                    'baris'      => $i + 1,
                    'sku'        => $this->_teks(isset($r[0]) ? $r[0] : ''),
                    'rak'        => Top_level_fcd::rapikan_rak($this->_teks(isset($r[1]) ? $r[1] : '')),
                    'qty'        => $qty,
                    'ac_list'    => $this->_teks(isset($r[3]) ? $r[3] : ''),
                    'nama_list'  => $this->_teks(isset($r[4]) ? $r[4] : ''),
                    'tgl_list'   => $tl,
                    'ac_input'   => $this->_teks(isset($r[6]) ? $r[6] : ''),
                    'nama_input' => $this->_teks(isset($r[7]) ? $r[7] : ''),
                    'tgl_input'  => $ti,
                );
            }
            if (!$baris) {
                throw new \Exception('Tidak ada baris data mulai baris ke-4.');
            }

            $baris = $this->top_level_fcd->periksa_baris($baris);
            foreach ($baris as &$b) {
                if ($b['st'] === 'ok' && isset($tgl_salah[$b['baris']])) {
                    $b['warn'] = 'Tanggal tidak terbaca';
                }
            }
            unset($b);

            if ($dry) {
                $hitung = array('ok' => 0, 'err' => 0, 'dup' => 0, 'qty' => 0);
                $keluar = array();
                foreach ($baris as $b) {
                    $hitung[$b['st']]++;
                    if ($b['st'] === 'ok') $hitung['qty'] += $b['qty'];
                    $keluar[] = array(
                        'baris' => $b['baris'], 'sku' => $b['sku'], 'rak' => $b['rak'], 'qty' => $b['qty'],
                        'nama_list' => $b['nama_list'], 'tgl_list' => $b['tgl_list'], 'ac_list' => $b['ac_list'],
                        'nama_input' => $b['nama_input'], 'tgl_input' => $b['tgl_input'], 'ac_input' => $b['ac_input'],
                        'st' => $b['st'], 'err' => $b['err'], 'warn' => $b['warn'], 'baru' => $b['baru'],
                    );
                }
                $this->make_ajax_response(200, 'OK', array('dry' => true, 'file' => $_FILES['berkas']['name'], 'hitung' => $hitung, 'rows' => $keluar, 'riwayat' => $this->top_level_fcd->riwayat_import()));
            }

            $h = $this->top_level_fcd->impor($baris, $_FILES['berkas']['name'], $this->_oleh());
            if (!$h['ok']) {
                throw new \Exception($h['pesan']);
            }
            $this->make_ajax_response(200, 'OK', array('dry' => false, 'hasil' => $h, 'riwayat' => $this->top_level_fcd->riwayat_import()));
        } catch (\Exception $e) {
            $this->make_ajax_response(500, $e->getMessage());
        }
    }

    public function upload_riwayat()
    {
        $this->_wajib(self::URI_UPLOAD);
        $this->make_ajax_response(200, 'OK', array('riwayat' => $this->top_level_fcd->riwayat_import()));
    }

    // ------------------------------------------------------------ utilitas

    /** Sel Excel jadi teks; angka bulat tidak diberi ".0". */
    private function _teks($v)
    {
        if (is_float($v) && $v == (int) $v) return (string) (int) $v;
        return trim((string) $v);
    }

    /** Role user harus punya akses ke menu itu (sidebar saja tidak cukup untuk endpoint POST). */
    private function _boleh($uri)
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;
        $menu = $this->db->get_where('menu', array('uri' => $uri))->row();
        if (!$menu) return false;
        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))->count_all_results('roleaccess') > 0;
    }

    private function _wajib($uri)
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        if (!$this->_boleh($uri)) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }
    }

    /**
     * Nama akun IRESIS ("Ika - LEADER OP - 0001" = nama - role - no ac) ditampilkan sebagai "no ac - nama - role"
     * ("0001 - Ika - LEADER OP"). Yang tersimpan di database tetap nama akun apa adanya; ini hanya tampilan.
     * Nama yang tidak berpola itu dikembalikan apa adanya.
     */
    public static function akun_tampil($nama)
    {
        $nama = trim((string) $nama);
        if (preg_match('/^(.+?)\s+-\s+(.+)\s+-\s+(\d+)$/u', $nama, $m)) {
            return $m[3] . ' - ' . $m[1] . ' - ' . $m[2];
        }
        return $nama;
    }

    private function _oleh()
    {
        $u = isset($this->data['user']) ? $this->data['user'] : array();
        return !empty($u['name']) ? $u['name'] : (isset($u['username']) ? $u['username'] : '');
    }
}
