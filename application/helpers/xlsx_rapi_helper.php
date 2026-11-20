<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Isi satu sheet PhpSpreadsheet dan rapikan: header tebal berlatar hijau dengan teks putih, semua sel
 * rata tengah, garis tegak & datar, header dibekukan, filter otomatis.
 *
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws
 * @param string[] $head      judul kolom
 * @param array[]  $baris     baris data (urut sesuai $head)
 * @param int[]    $lebar     lebar tiap kolom
 * @param int[]    $kol_teks  nomor kolom (mulai 1) yang ditulis sebagai teks eksplisit (SKU / resi angka panjang)
 * @param int[]    $kol_angka nomor kolom (mulai 1) berformat angka #,##0
 */
function xlsx_tulis_sheet_rapi($ws, array $head, array $baris, array $lebar, array $kol_teks = array(), array $kol_angka = array())
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
