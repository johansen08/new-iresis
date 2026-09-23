<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Ekspor Excel untuk Laporan Pengiriman Paket (.xlsx sungguhan).
 *
 * Menerima hasil Receipt_fcd::rekap_wajib_keluar() dan mengalirkannya langsung
 * ke browser. Dipakai Report::export_to_excel_shipping_report().
 *
 * Sebelumnya laporan ini dikirim sebagai HTML ber-ekstensi .xls: Excel memang
 * membukanya, tapi selalu memunculkan peringatan "format dan ekstensi tidak
 * cocok", lebar kolom ditentukan sendiri oleh Excel (autofit), dan gaya sel
 * gampang hilang. PhpSpreadsheet menulis xlsx asli, jadi tidak ada peringatan
 * dan lebar/gaya persis seperti yang ditulis di sini.
 *
 * Catatan angka: pemisah ribuan dan desimal selalu mengikuti setelan Windows
 * pembacanya (format [$-421] pun tidak mengubahnya — sudah diuji). Sel jumlah
 * dibiarkan numerik supaya ikut setelan itu dan tetap bisa dijumlah; sel
 * "3,019 (55.5%)" di matriks terpaksa teks, jadi pemisahnya ditulis mengikuti
 * setelan PC kantor (en-US). Kalau PC beralih ke setelan Indonesia, ubah
 * SEPARATOR_RIBUAN/SEPARATOR_DESIMAL di bawah.
 */
class Ekspor_pengiriman
{
    const SEPARATOR_RIBUAN  = ',';
    const SEPARATOR_DESIMAL = '.';

    const FORMAT_JUMLAH = '#,##0';
    const FORMAT_PERSEN = '0.0';

    // Palet: biru = wajib keluar, toska = TikTok, abu = non wajib,
    // navy = total/persen/peringkat. Semua pasangan warna-teks di bawah
    // kontrasnya >= 4,5:1 (ambang WCAG AA untuk teks biasa).
    const NAVY       = '1F3A5F';
    const BIRU_TUA   = '2F5597';
    const TOSKA      = '0F7B7B';
    const ABU_TUA    = '6B6B6B';
    const BIRU_MUDA  = 'D9E2EC';
    const EMAS       = 'FFC000';
    const PERAK      = 'C0C0C0';

    private $sp;
    private $ws;
    private $baris = 1;      // baris tulis berikutnya
    private $lebar = array(); // [indeks kolom] => lebar terbesar yang diminta

    /**
     * Bangun berkas lalu alirkan ke browser dan hentikan eksekusi.
     *
     * @param array  $rekap         hasil Receipt_fcd::rekap_wajib_keluar()
     * @param string $reportrange   "Y-m-d H:i:s - Y-m-d H:i:s"
     * @param array  $nama_kategori kode kategori => nama pendek untuk matriks
     */
    public function kirim($rekap, $reportrange, $nama_kategori)
    {
        $this->sp = new Spreadsheet();
        $this->ws = $this->sp->getActiveSheet();
        $this->ws->setTitle('Pengiriman');
        $this->sp->getProperties()->setTitle('Laporan Pengiriman Paket');

        $gt = (int) $rekap['grand_total'];
        $this->kepala($reportrange, $gt);

        if ($gt === 0) {
            $this->ws->setCellValue('A' . $this->baris, 'Tidak ada paket keluar pada periode ini.');
            $this->ws->getStyle('A' . $this->baris)->getFont()->setItalic(true);
        } else {
            $this->tabel('BY MARKETPLACE', 'Marketplace', $rekap['by_mp']);
            $this->tabel('BY KATEGORI',    'Kategori',    $rekap['by_kategori']);
            $this->tabel('BY KURIR',       'Kurir',       $rekap['by_kurir']);
            $this->matriks($rekap['matriks'], $rekap['kolom_matriks'], $rekap['total_matriks'],
                           $nama_kategori, $gt);
        }

        $this->terapkan_lebar();
        $this->alirkan('Laporan_Pengiriman_Paket_' . date('Y-m-d') . '.xlsx');
    }

    // ── Bagian laporan ───────────────────────────────────────

    private function kepala($reportrange, $gt)
    {
        $dates = array_pad(explode(' - ', $reportrange), 2, '');

        $this->ws->setCellValue('A1', 'LAPORAN PENGIRIMAN PAKET');
        $this->ws->mergeCells('A1:D1');
        $this->ws->getStyle('A1:D1')->applyFromArray(array(
            'font' => array('bold' => true, 'size' => 14, 'color' => array('rgb' => 'FFFFFF')),
            'fill' => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => self::NAVY)),
        ));
        $this->ws->getRowDimension(1)->setRowHeight(24);

        $this->ws->setCellValue('A2', 'Periode');
        $this->ws->setCellValue('B2', $this->tanggal($dates[0]) . ' – ' . $this->tanggal($dates[1]));
        $this->ws->mergeCells('B2:D2');

        $this->ws->setCellValue('A3', 'Total');
        $this->ws->setCellValue('B3', $gt);
        $this->ws->getStyle('B3')->getNumberFormat()->setFormatCode(self::FORMAT_JUMLAH);
        $this->ws->getStyle('B3')->getFont()->setBold(true)->getColor()->setRGB(self::NAVY);

        $this->ws->getStyle('A2:A3')->getFont()->setBold(true);
        $this->baris = 5;
    }

    /**
     * Tabel label + Wajib Keluar / Wajib TT / Non Wajib / Total / In % / In Rank.
     */
    private function tabel($judul, $kolom, $rows)
    {
        $this->judul_tabel($judul, 7);

        $r = $this->baris;
        $this->kepala_kolom($r, array(
            array($kolom,         self::BIRU_MUDA, self::NAVY, 24),
            array('Wajib Keluar', self::BIRU_TUA,  'FFFFFF',   13),
            array('Wajib TT',     self::TOSKA,     'FFFFFF',   13),
            array('Non Wajib',    self::ABU_TUA,   'FFFFFF',   13),
            array('Total',        self::NAVY,      'FFFFFF',   13),
            array('In %',         self::NAVY,      'FFFFFF',    8),
            array('In Rank',      self::NAVY,      'FFFFFF',    9),
        ));
        $r++;

        $isi   = array('wajib' => 'DEEBF7', 'wajib_tt' => 'E0F2F1', 'non_wajib' => 'F2F2F2');
        $jumlah = array('wajib' => 0, 'wajib_tt' => 0, 'non_wajib' => 0);
        foreach ($rows as $baris) {
            $this->ws->setCellValue("A$r", $baris['label']);
            $kol = 2;
            foreach ($isi as $ukur => $warna) {
                $jumlah[$ukur] += (int) $baris[$ukur];
                $this->angka($kol++, $r, (int) $baris[$ukur], self::FORMAT_JUMLAH, $warna);
            }
            $this->angka($kol++, $r, (int) $baris['total'], self::FORMAT_JUMLAH, null, true);
            $this->angka($kol++, $r, round($baris['pct'], 1), self::FORMAT_PERSEN);
            $this->peringkat($kol, $r, (int) $baris['rank']);
            $r++;
        }

        $this->baris_total($r, 1, array_merge(
            array(array($jumlah['wajib'], 'C5D9F1'), array($jumlah['wajib_tt'], 'B2DFDB'),
                  array($jumlah['non_wajib'], 'D9D9D9'), array(array_sum($jumlah), null)),
            array(array(100.0, null, self::FORMAT_PERSEN), array(null, null))
        ));
        $this->bingkai(1, $this->baris, 7, $r);
        $this->baris = $r + 2;
    }

    /**
     * Matriks: baris = marketplace, kolom = kategori. Tiap sel berisi
     * "3,019 (55.5%)" — persen terhadap total marketplace baris itu.
     */
    private function matriks($rows, $kolom, $total_kolom, $nama_kategori, $gt)
    {
        $n = count($kolom);
        $this->judul_tabel('BY MARKETPLACE & KATEGORI', $n + 4);

        $r = $this->baris;
        $kepala = array(array('Marketplace', self::BIRU_MUDA, self::NAVY, 24));
        foreach ($kolom as $k) {
            $kepala[] = array($nama_kategori[$k], self::BIRU_MUDA, self::NAVY, 15);
        }
        $kepala[] = array('Total',   self::NAVY, 'FFFFFF', 13);
        $kepala[] = array('In %',    self::NAVY, 'FFFFFF',  8);
        $kepala[] = array('In Rank', self::NAVY, 'FFFFFF',  9);
        $this->kepala_kolom($r, $kepala);
        $r++;

        foreach ($rows as $baris) {
            $this->ws->setCellValue("A$r", $baris['label']);
            $kol = 2;
            foreach ($kolom as $k) {
                $this->teks($kol++, $r, $this->jumlah_persen($baris['sel'][$k], $baris['total']), 'F7F9FC');
            }
            $this->angka($kol++, $r, (int) $baris['total'], self::FORMAT_JUMLAH, null, true);
            $this->angka($kol++, $r, round($baris['pct'], 1), self::FORMAT_PERSEN);
            $this->peringkat($kol, $r, (int) $baris['rank']);
            $r++;
        }

        $sel_total = array();
        foreach ($kolom as $k) {
            $sel_total[] = array($this->jumlah_persen($total_kolom[$k], $gt), null, 'teks');
        }
        $sel_total[] = array(array_sum($total_kolom), null);
        $sel_total[] = array(100.0, null, self::FORMAT_PERSEN);
        $sel_total[] = array(null, null);
        $this->baris_total($r, 1, $sel_total);

        $this->bingkai(1, $this->baris, $n + 4, $r);
        $this->baris = $r + 2;
    }

    // ── Perkakas penulisan sel ───────────────────────────────

    private function judul_tabel($teks, $jml_kolom)
    {
        $r = $this->baris;
        $this->ws->setCellValue("A$r", $teks);
        $this->ws->mergeCells('A' . $r . ':' . $this->huruf($jml_kolom) . $r);
        $this->ws->getStyle('A' . $r . ':' . $this->huruf($jml_kolom) . $r)->applyFromArray(array(
            'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
            'fill' => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => self::NAVY)),
        ));
        $this->baris = $r + 1;
    }

    /** $kolom: array of [teks, warna isi, warna teks, lebar] */
    private function kepala_kolom($r, $kolom)
    {
        $i = 1;
        foreach ($kolom as $k) {
            list($teks, $bg, $fg, $lebar) = $k;
            $sel = $this->huruf($i) . $r;
            $this->ws->setCellValue($sel, $teks);
            $this->ws->getStyle($sel)->applyFromArray(array(
                'font'      => array('bold' => true, 'color' => array('rgb' => $fg)),
                'fill'      => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => $bg)),
                'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER,
                                     'vertical'   => Alignment::VERTICAL_CENTER,
                                     'wrapText'   => true),
            ));
            $this->minta_lebar($i, $lebar);
            $i++;
        }
        $this->ws->getRowDimension($r)->setRowHeight(28);
    }

    private function angka($kol, $r, $nilai, $format, $warna = null, $tebal = false)
    {
        $sel = $this->huruf($kol) . $r;
        $this->ws->setCellValue($sel, $nilai);
        $gaya = $this->ws->getStyle($sel);
        $gaya->getNumberFormat()->setFormatCode($format);
        $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        if ($warna !== null) {
            $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
        }
        if ($tebal) {
            $gaya->getFont()->setBold(true);
        }
    }

    private function teks($kol, $r, $nilai, $warna = null)
    {
        $sel = $this->huruf($kol) . $r;
        $this->ws->setCellValueExplicit($sel, $nilai, DataType::TYPE_STRING);
        $gaya = $this->ws->getStyle($sel);
        $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        if ($warna !== null) {
            $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
        }
    }

    /** Peringkat 1 emas, 2 perak, sisanya polos. */
    private function peringkat($kol, $r, $rank)
    {
        $sel = $this->huruf($kol) . $r;
        $this->ws->setCellValue($sel, $rank);
        $gaya = $this->ws->getStyle($sel);
        $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if ($rank === 1) {
            $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::EMAS);
            $gaya->getFont()->setBold(true)->getColor()->setRGB('4A3500');
        } elseif ($rank === 2) {
            $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PERAK);
            $gaya->getFont()->setBold(true)->getColor()->setRGB('262626');
        } else {
            $gaya->getFont()->getColor()->setRGB('595959');
        }
    }

    /** $sel: array of [nilai, warna, format|'teks'] — nilai null = sel kosong. */
    private function baris_total($r, $kol_awal, $sel)
    {
        $this->ws->setCellValue($this->huruf($kol_awal) . $r, 'Total');
        $kol = $kol_awal + 1;
        foreach ($sel as $s) {
            $nilai  = $s[0];
            $warna  = isset($s[1]) && $s[1] !== null ? $s[1] : 'E7ECF3';
            $format = isset($s[2]) ? $s[2] : self::FORMAT_JUMLAH;
            if ($nilai === null) {
                $this->ws->getStyle($this->huruf($kol) . $r)->getFill()
                     ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
            } elseif ($format === 'teks') {
                $this->teks($kol, $r, $nilai, $warna);
            } else {
                $this->angka($kol, $r, $nilai, $format, $warna);
            }
            $kol++;
        }
        $rentang = $this->huruf($kol_awal) . $r . ':' . $this->huruf($kol - 1) . $r;
        $this->ws->getStyle($rentang)->getFont()->setBold(true);
        $this->ws->getStyle($this->huruf($kol_awal) . $r)->getFill()
             ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7ECF3');
    }

    private function bingkai($kol1, $r1, $kol2, $r2)
    {
        $this->ws->getStyle($this->huruf($kol1) . $r1 . ':' . $this->huruf($kol2) . $r2)
             ->getBorders()->getAllBorders()
             ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
    }

    // ── Bantuan kecil ────────────────────────────────────────

    /** "3,019 (55.5%)" — persen dibulatkan satu desimal, 0 kalau pembagi 0. */
    private function jumlah_persen($nilai, $dari)
    {
        $pct = $dari > 0 ? $nilai / $dari * 100 : 0;
        return number_format($nilai, 0, self::SEPARATOR_DESIMAL, self::SEPARATOR_RIBUAN)
             . ' (' . number_format($pct, 1, self::SEPARATOR_DESIMAL, self::SEPARATOR_RIBUAN) . '%)';
    }

    private function tanggal($s)
    {
        $bulan = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
        $t = strtotime($s);
        return $t ? date('d', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y H:i', $t) : $s;
    }

    private function huruf($i)
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    }

    /** Kolom dipakai beberapa tabel; yang terlebar menang. */
    private function minta_lebar($i, $lebar)
    {
        if (!isset($this->lebar[$i]) || $this->lebar[$i] < $lebar) {
            $this->lebar[$i] = $lebar;
        }
    }

    private function terapkan_lebar()
    {
        foreach ($this->lebar as $i => $lebar) {
            $this->ws->getColumnDimension($this->huruf($i))->setWidth($lebar);
        }
    }

    private function alirkan($nama_berkas)
    {
        // Sisa buffer apa pun akan merusak berkas zip xlsx — bersihkan semua.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($this->sp))->save('php://output');
        $this->sp->disconnectWorksheets();
        exit;
    }
}
