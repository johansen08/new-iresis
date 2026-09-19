<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pembaca .xlsx streaming (ZipArchive + XMLReader) untuk file unggahan besar.
 *
 * PhpSpreadsheet membangun objek Cell untuk setiap sel, jadi laporan penjualan
 * Jubelio 20 ribu baris butuh ~20 detik dan ~270 MB hanya untuk dibaca.
 * Pembaca ini cuma membaca XML lembar kerja secara berurutan dan langsung
 * menghasilkan array baris -> ~1 detik untuk file yang sama.
 *
 * Bentuk hasilnya SAMA dengan `Worksheet::toArray(null, true, true, true)`
 * yang dipakai sebelumnya, supaya `Receipt_fcd::insert_receipt()` dan pemakai
 * lain tidak perlu berubah:
 *   - key baris = nomor baris Excel (1, 2, 3, ...), key kolom = huruf (A, B, ...)
 *   - setiap baris memuat SEMUA kolom A..kolom_maks (sel kosong = null)
 *   - angka dikembalikan sebagai string ("46290", "0.31"), sama seperti hasil
 *     format "General" PhpSpreadsheet
 *   - tanggal tetap berupa angka serial Excel (tidak diformat) karena
 *     setReadDataOnly(true) juga tidak memuat format sel
 *   - yang dibaca selalu lembar PERTAMA, sama seperti PhpSpreadsheet dalam
 *     mode readDataOnly (activeTab diabaikan)
 *
 * Yang TIDAK didukung (lempar Exception, pemanggil harus fallback ke
 * PhpSpreadsheet): file .xls lama, arsip rusak, atau lembar tanpa data.
 */
class Xlsx_cepat
{
    /** Daftar shared string, diindeks sesuai urutan di sharedStrings.xml */
    private $shared = [];

    /**
     * Membaca lembar aktif sebuah file .xlsx.
     *
     * @param string $path       Lokasi file .xlsx
     * @param string $kolom_maks Kolom terakhir yang dibaca (mis. 'W'); kolom di
     *                           kanannya diabaikan supaya array tetap ramping
     * @return array             Baris ala toArray() dengan referensi sel
     * @throws Exception         Bila file bukan xlsx yang bisa dibaca
     */
    public function baca(string $path, string $kolom_maks = 'W'): array
    {
        if (!class_exists('ZipArchive') || !class_exists('XMLReader')) {
            throw new Exception('Ekstensi zip/xmlreader PHP tidak aktif');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('File bukan arsip xlsx yang valid');
        }

        try {
            $sheet_path = $this->cari_lembar_aktif($zip);
            $this->muat_shared_strings($zip);

            $xml = $zip->getFromName($sheet_path);
            if ($xml === false) {
                throw new Exception("Lembar kerja $sheet_path tidak ditemukan di arsip");
            }

            return $this->urai_lembar($xml, strtoupper($kolom_maks));
        } finally {
            $zip->close();
            $this->shared = [];
        }
    }

    /**
     * Membaca file Excel apa pun: coba jalur cepat dulu untuk .xlsx, dan bila
     * gagal (atau file .xls lama) pakai PhpSpreadsheet seperti sebelumnya.
     *
     * @param string        $path       Lokasi file
     * @param string        $kolom_maks Kolom terakhir yang dibaca
     * @param string|null   $jalur      Diisi 'cepat' atau 'phpspreadsheet' (untuk log)
     */
    public function baca_dengan_fallback(string $path, string $kolom_maks = 'W', ?string &$jalur = null): array
    {
        if ($this->tampak_seperti_xlsx($path)) {
            try {
                $jalur = 'cepat';
                return $this->baca($path, $kolom_maks);
            } catch (Exception $e) {
                log_message('error', 'Xlsx_cepat gagal, fallback ke PhpSpreadsheet: ' . $e->getMessage());
            }
        }

        $jalur = 'phpspreadsheet';
        ini_set('memory_limit', '3072M');
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
            \PhpOffice\PhpSpreadsheet\IOFactory::identify($path)
        );
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }

    /** xlsx = arsip zip (magic "PK\x03\x04") yang memuat [Content_Types].xml */
    private function tampak_seperti_xlsx(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) return false;
        $magic = fread($fh, 4);
        fclose($fh);
        return $magic === "PK\x03\x04";
    }

    /**
     * Menentukan file XML lembar PERTAMA: workbook.xml -> rId -> workbook.xml.rels
     * -> Target. Sengaja bukan lembar aktif (activeTab): PhpSpreadsheet dengan
     * setReadDataOnly(true) juga mengabaikan activeTab dan selalu memakai
     * lembar indeks 0, jadi perilakunya sama persis dengan sebelumnya.
     */
    private function cari_lembar_aktif(ZipArchive $zip): string
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        if ($wb === false) {
            throw new Exception('xl/workbook.xml tidak ditemukan');
        }

        preg_match_all('/<sheet\b[^>]*>/', $wb, $sheets);
        if (empty($sheets[0])) {
            throw new Exception('Workbook tidak punya lembar kerja');
        }
        $sheet_tag = $sheets[0][0];
        if (!preg_match('/\br:id="([^"]+)"/', $sheet_tag, $m)) {
            throw new Exception('Relasi lembar kerja tidak ditemukan');
        }
        $rid = $m[1];

        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels === false) {
            throw new Exception('xl/_rels/workbook.xml.rels tidak ditemukan');
        }
        if (!preg_match('/<Relationship\b[^>]*\bId="' . preg_quote($rid, '/') . '"[^>]*\bTarget="([^"]+)"/', $rels, $m)
            && !preg_match('/<Relationship\b[^>]*\bTarget="([^"]+)"[^>]*\bId="' . preg_quote($rid, '/') . '"/', $rels, $m)) {
            throw new Exception("Target relasi $rid tidak ditemukan");
        }

        $target = $m[1];
        if ($target[0] === '/') {
            return ltrim($target, '/');
        }
        return 'xl/' . $target;
    }

    /** Memuat sharedStrings.xml; teks kaya (rich text) digabung apa adanya. */
    private function muat_shared_strings(ZipArchive $zip): void
    {
        $this->shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return; // file tanpa string sama sekali (semua angka / inlineStr)
        }

        $reader = new XMLReader();
        if (!$reader->XML($xml, null, LIBXML_NONET | LIBXML_NOENT)) {
            throw new Exception('sharedStrings.xml tidak bisa dibaca');
        }

        $dalam_si = false;
        $buf = '';
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->localName === 'si') {
                    $dalam_si = true;
                    $buf = '';
                } elseif ($dalam_si && $reader->localName === 't') {
                    $buf .= $reader->readString();
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'si') {
                $this->shared[] = $buf;
                $dalam_si = false;
            }
        }
        $reader->close();
    }

    /** Mengurai sheetN.xml menjadi array baris. */
    private function urai_lembar(string $xml, string $kolom_maks): array
    {
        $reader = new XMLReader();
        if (!$reader->XML($xml, null, LIBXML_NONET | LIBXML_NOENT)) {
            throw new Exception('XML lembar kerja tidak bisa dibaca');
        }

        $idx_maks = $this->indeks_kolom($kolom_maks);
        $kolom = [];
        for ($i = 1; $i <= $idx_maks; $i++) {
            $kolom[$i] = $this->huruf_kolom($i);
        }
        $baris_kosong = array_fill_keys($kolom, null);

        $rows = [];
        $no_baris = 0;
        $baris = null;
        $idx_terakhir = 0;

        // Keadaan sel yang sedang dibaca
        $sel_idx = 0;
        $sel_tipe = '';
        $dalam_sel = false;
        $nilai = null;
        $nilai_ada = false;

        while ($reader->read()) {
            $type = $reader->nodeType;

            if ($type === XMLReader::ELEMENT) {
                $nama = $reader->localName;

                if ($nama === 'row') {
                    $r = $reader->getAttribute('r');
                    $no_baris = $r !== null ? (int)$r : $no_baris + 1;
                    $baris = $baris_kosong;
                    $idx_terakhir = 0;
                    if ($reader->isEmptyElement) {
                        $rows[$no_baris] = $baris;
                        $baris = null;
                    }
                } elseif ($nama === 'c' && $baris !== null) {
                    $ref = $reader->getAttribute('r');
                    if ($ref !== null && preg_match('/^([A-Z]+)/', $ref, $m)) {
                        $sel_idx = $this->indeks_kolom($m[1]);
                    } else {
                        $sel_idx = $idx_terakhir + 1; // penulis yang tidak menyertakan atribut r
                    }
                    $idx_terakhir = $sel_idx;
                    $sel_tipe = (string)$reader->getAttribute('t');
                    $dalam_sel = !$reader->isEmptyElement;
                    $nilai = null;
                    $nilai_ada = false;
                } elseif ($dalam_sel && ($nama === 'v' || ($nama === 't' && $sel_tipe === 'inlineStr'))) {
                    $teks = $reader->readString();
                    $nilai = $nilai_ada && $nama === 't' ? $nilai . $teks : $teks;
                    $nilai_ada = true;
                }
            } elseif ($type === XMLReader::END_ELEMENT) {
                $nama = $reader->localName;

                if ($nama === 'c' && $dalam_sel) {
                    $dalam_sel = false;
                    if ($nilai_ada && $sel_idx >= 1 && $sel_idx <= $idx_maks) {
                        $baris[$kolom[$sel_idx]] = $this->konversi_nilai($sel_tipe, $nilai);
                    }
                } elseif ($nama === 'row' && $baris !== null) {
                    $rows[$no_baris] = $baris;
                    $baris = null;
                }
            }
        }
        $reader->close();

        // Samakan dengan toArray(): baris yang dilompati penulis (tanpa <row>)
        // tetap ada sebagai baris kosong agar nomor baris sinambung.
        if (!empty($rows)) {
            $terakhir = max(array_keys($rows));
            if (count($rows) !== $terakhir) {
                for ($i = 1; $i <= $terakhir; $i++) {
                    if (!isset($rows[$i])) $rows[$i] = $baris_kosong;
                }
                ksort($rows);
            }
        }

        return $rows;
    }

    /**
     * Nilai sel -> bentuk yang sama dengan hasil PhpSpreadsheet (format General):
     * string apa adanya, angka sebagai string sesuai teks di file, boolean 1/0.
     */
    private function konversi_nilai(string $tipe, ?string $nilai)
    {
        switch ($tipe) {
            case 's':
                return $this->shared[(int)$nilai] ?? '';
            case 'inlineStr':
            case 'str':
                return (string)$nilai;
            case 'b':
                return $nilai === '1' ? 'TRUE' : 'FALSE'; // sama dengan format General PhpSpreadsheet
            case 'e':
                return (string)$nilai; // teks error, mis. #N/A
            default:
                // Angka / tanggal serial. Disamakan dengan PhpSpreadsheet: teks
                // angka jadi int bila bulat (46290.0 -> "46290", 1E+15 ->
                // "1000000000000000"), selebihnya float -> string ("0.31").
                if ($nilai === null || $nilai === '' || !is_numeric($nilai)) {
                    return $nilai;
                }
                $angka = $nilai + 0;
                if (is_float($angka) && $angka == floor($angka) && abs($angka) < 9.2e18) {
                    $angka = (int)$angka;
                }
                return (string)$angka;
        }
    }

    private function indeks_kolom(string $huruf): int
    {
        $n = 0;
        $len = strlen($huruf);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($huruf[$i]) - 64);
        }
        return $n;
    }

    private function huruf_kolom(int $idx): string
    {
        $s = '';
        while ($idx > 0) {
            $m = ($idx - 1) % 26;
            $s = chr(65 + $m) . $s;
            $idx = intdiv($idx - 1, 26);
        }
        return $s;
    }
}
