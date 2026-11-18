<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PURCHASING -> "Update Kategori SKU": upload Excel (SKU, Kategori) untuk mengisi tblsku.kategori_sku.
 * Aturan validasi dan penulisan ada di Kategori_sku_fcd. Alur: docs/KATEGORI_SKU.md.
 */
class Kategori_sku extends MY_Controller
{
    /** Webmaster (1) dan tim purchasing (7). Sejalan dengan MY_Controller::run_kategori_sku_migration(). */
    const ROLE_BOLEH = [1, 7];
    const MAKS_UKURAN_BYTE = 10485760; // 10 MB

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
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
                'view'    => $this->load->view('kategori_sku/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Update Kategori SKU.');
    }

    public function index()
    {
        $this->show(['default_kategori' => Kategori_sku_fcd::DEFAULT_KATEGORI], 'kategori_sku/index');
    }

    /** Unduh template Excel: header SKU + Kategori, kolom teks, dan sheet petunjuk. Tanpa baris contoh. */
    public function template()
    {
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Kategori SKU');
        $ws->setCellValue('A1', 'SKU');
        $ws->setCellValue('B1', 'Kategori');
        $ws->getStyle('A1:B1')->getFont()->setBold(TRUE);
        $ws->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        // Kolom teks: cegah Excel mengubah SKU angka panjang menjadi notasi ilmiah.
        $ws->getStyle('A2:B' . (Kategori_sku_fcd::MAKS_BARIS + 1))->getNumberFormat()->setFormatCode('@');
        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('B')->setWidth(22);

        $p = $ss->createSheet();
        $p->setTitle('Petunjuk');
        $teks = [
            'Petunjuk pengisian',
            '1. Isi mulai baris 2 pada sheet "Kategori SKU". Baris 1 (header) jangan diubah.',
            '2. Kolom A = SKU, harus sama persis dengan SKU di sistem (huruf besar/kecil dan spasi dihitung).',
            '3. Kolom B = Kategori, maksimal ' . Kategori_sku_fcd::MAKS_PANJANG_KATEGORI . ' karakter. Bila dikosongkan, kategori menjadi ' . Kategori_sku_fcd::DEFAULT_KATEGORI . '.',
            '4. Satu SKU hanya boleh muncul sekali.',
            '5. Bila ada satu saja baris yang salah, upload dibatalkan seluruhnya dan alasan per baris ditampilkan.',
            '6. Maksimal ' . Kategori_sku_fcd::MAKS_BARIS . ' baris per berkas, ukuran berkas maksimal 10 MB (.xlsx / .xls).',
        ];
        foreach ($teks as $i => $t) {
            $p->setCellValue('A' . ($i + 1), $t);
        }
        $p->getStyle('A1')->getFont()->setBold(TRUE);
        $p->getColumnDimension('A')->setWidth(110);
        $ss->setActiveSheetIndex(0);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_update_kategori_sku.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit();
    }

    /** POST multipart: berkas. Selalu membalas JSON; gagal => code 400 + data.galat (alasan per baris). */
    public function upload()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $f = isset($_FILES['berkas']) ? $_FILES['berkas'] : null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $this->make_ajax_response(400, 'Pilih berkas Excel terlebih dahulu.', ['galat' => ['Belum ada berkas yang dipilih.'], 'total_galat' => 1]);
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $alasan = ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
                ? 'Ukuran berkas melebihi batas server. Maksimal 10 MB.'
                : 'Berkas gagal terunggah (kode ' . (int) $f['error'] . '). Coba lagi.';
            $this->make_ajax_response(400, $alasan, ['galat' => [$alasan], 'total_galat' => 1]);
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], TRUE)) {
            $this->tolak('Format berkas harus .xlsx atau .xls (berkas Anda: .' . ($ext === '' ? '?' : $ext) . ').');
        }
        if ($f['size'] > self::MAKS_UKURAN_BYTE) {
            $this->tolak('Ukuran berkas ' . round($f['size'] / 1048576, 1) . ' MB melebihi batas 10 MB.');
        }

        $parse = $this->baca_excel($f['tmp_name'], $ext);
        if (isset($parse['galat'])) {
            $this->tolak($parse['galat']);
        }

        $id_user = isset($this->data['user']['id_user']) ? (int) $this->data['user']['id_user'] : 0;
        $h = $this->kategori_sku_fcd->proses($parse['baris'], $f['name'], $id_user);
        if (!$h['ok']) {
            $this->make_ajax_response(400, 'Upload dibatalkan: ada ' . $h['total_galat'] . ' masalah. Tidak ada data yang diubah.', [
                'galat' => $h['galat'], 'total_galat' => $h['total_galat'],
            ]);
        }
        $this->make_ajax_response(200, 'Update kategori SKU berhasil.', [
            'total' => $h['total'], 'berubah' => $h['berubah'], 'sama' => $h['sama'], 'batch' => $h['batch'],
        ]);
    }

    private function tolak($alasan)
    {
        $this->make_ajax_response(400, $alasan, ['galat' => [$alasan], 'total_galat' => 1]);
    }

    /**
     * Baca sheet pertama. Mengembalikan ['baris' => [{no, sku, kategori}]] atau ['galat' => 'pesan'].
     * Baris yang kedua selnya kosong dibuang.
     */
    private function baca_excel($path, $ext)
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext === 'xls' ? 'Xls' : 'Xlsx');
            $reader->setReadDataOnly(TRUE);
            $ws = $reader->load($path)->getSheet(0);
        } catch (\Throwable $e) {
            return ['galat' => 'Berkas tidak bisa dibaca sebagai Excel (rusak, diproteksi password, atau bukan ' . $ext . ' asli).'];
        }

        $tinggi = $ws->getHighestDataRow();
        $a1 = mb_strtolower(trim((string) $ws->getCell('A1')->getValue()));
        $b1 = mb_strtolower(trim((string) $ws->getCell('B1')->getValue()));
        if ($a1 !== 'sku' || $b1 !== 'kategori') {
            return ['galat' => 'Header salah. Baris 1 harus berisi "SKU" di kolom A dan "Kategori" di kolom B (ditemukan: "' . mb_substr($a1, 0, 20) . '" dan "' . mb_substr($b1, 0, 20) . '"). Gunakan template.'];
        }
        if ($tinggi > Kategori_sku_fcd::MAKS_BARIS + 1) {
            return ['galat' => 'Terlalu banyak baris (' . ($tinggi - 1) . '). Maksimal ' . Kategori_sku_fcd::MAKS_BARIS . ' baris per upload.'];
        }

        $baris = [];
        for ($r = 2; $r <= $tinggi; $r++) {
            $sku = $this->sel_teks($ws->getCell('A' . $r)->getValue(), $r, 'A', $galat_sel);
            if ($galat_sel !== null) {
                return ['galat' => $galat_sel];
            }
            $kat = $this->sel_teks($ws->getCell('B' . $r)->getValue(), $r, 'B', $galat_sel);
            if ($galat_sel !== null) {
                return ['galat' => $galat_sel];
            }
            if ($sku === '' && $kat === '') {
                continue;
            }
            $baris[] = ['no' => $r, 'sku' => $sku, 'kategori' => $kat];
        }
        return ['baris' => $baris];
    }

    /**
     * Nilai sel -> teks bersih. Angka utuh diubah ke teks tanpa notasi ilmiah; angka sangat panjang (>= 1e15)
     * ditolak karena Excel sudah membulatkannya (format kolom sebagai Teks). Rumus ditolak.
     */
    private function sel_teks($v, $baris, $kolom, &$galat)
    {
        $galat = null;
        if ($v === null) {
            return '';
        }
        if (is_bool($v)) {
            $galat = "Baris $baris kolom $kolom: nilai TRUE/FALSE tidak valid.";
            return '';
        }
        if (is_int($v) || is_float($v)) {
            if (abs($v) >= 1e15) {
                $galat = "Baris $baris kolom $kolom: angka terlalu panjang dan sudah dibulatkan Excel. Format kolom sebagai Teks lalu ketik ulang.";
                return '';
            }
            return ($v == floor($v)) ? sprintf('%.0f', $v) : rtrim(rtrim(sprintf('%.10F', $v), '0'), '.');
        }
        $v = trim((string) $v);
        if ($v !== '' && $v[0] === '=') {
            $galat = "Baris $baris kolom $kolom: berisi rumus. Ganti dengan nilai teks.";
            return '';
        }
        return $v;
    }
}
