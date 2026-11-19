<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM MONITORING -> "Cabut SKU" (hanya webmaster). Lihat docs/CABUT_SKU.md.
 * Stok live diambil dari Jubelio per halaman; browser memanggil `perbarui` berulang sampai halaman terakhir.
 */
class Cabut_sku extends MY_Controller
{
    /** Sejalan dengan MY_Controller::run_cabut_sku_migration(). */
    const ROLE_BOLEH = [1];
    const MAKS_FOTO_BYTE = 5242880; // 5 MB

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('cabut_sku_fcd');
    }

    /** Penolakan tetap JSON valid (pola sama dengan Bonus_picker). */
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
                'view'    => $this->load->view('cabut_sku/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Hanya webmaster yang punya akses ke menu Cabut SKU.');
    }

    public function index()
    {
        $this->show([
            'kategori' => Cabut_sku_fcd::KATEGORI,
            'stok_min' => Cabut_sku_fcd::STOK_MIN,
            'batas'    => Cabut_sku_fcd::BATAS_STOK,
            'foto_url' => base_url('assets/uploads/cabut_sku/'),
        ], 'cabut_sku/index');
    }

    /** Daftar SKU yang memenuhi syarat dari data stok terakhir yang tersimpan (tanpa menyentuh Jubelio). */
    public function get_data()
    {
        $this->make_ajax_response(200, 'OK', [
            'baris'     => $this->cabut_sku_fcd->daftar(),
            'ringkasan' => $this->cabut_sku_fcd->ringkasan(),
        ]);
    }

    /**
     * Ambil SELURUH stok + no rak dari Jubelio sekali jalan (request paralel, page size besar; lihat
     * Jubelio_stok::ambil_semua) lalu simpan dalam satu transaksi.
     */
    public function perbarui()
    {
        set_time_limit(180);
        ini_set('memory_limit', '512M');
        $mulai = microtime(true);

        try {
            $this->load->library('jubelio_stok');
            $hasil = $this->jubelio_stok->ambil_semua();
        } catch (RuntimeException $e) {
            $this->make_ajax_response(502, $e->getMessage());
        }
        if (!$hasil['stok']) {
            $this->make_ajax_response(502, 'Jubelio tidak mengirim data stok. Coba lagi.');
        }

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_start();
        $n = $this->cabut_sku_fcd->simpan_halaman($hasil['stok']);
        $this->cabut_sku_fcd->simpan_rak_halaman($hasil['rak']);
        $this->db->trans_complete();
        $this->db->db_debug = $db_debug;
        if ($this->db->trans_status() === FALSE) {
            $this->make_ajax_response(500, 'Gagal menyimpan stok ke database.');
        }

        $this->make_ajax_response(200, 'OK', [
            'total_item' => $hasil['total'],
            'tersimpan'  => $n,
            'detik'      => round(microtime(true) - $mulai, 1),
        ]);
    }

    public function simpan_pindah_ke()
    {
        $sku = trim((string) $this->input->post('sku'));
        $rak = (string) $this->input->post('rak');

        if ($sku === '') {
            $this->make_ajax_response(400, 'SKU tidak boleh kosong.');
        }
        if (trim($rak) === '') {
            $this->make_ajax_response(400, 'No rak wajib diisi.');
        }
        if (preg_match('/\s/u', $rak)) {
            $this->make_ajax_response(400, 'No rak tidak boleh mengandung spasi.');
        }
        if (!preg_match('/^[A-Za-z0-9\/-]+$/D', $rak)) {
            $this->make_ajax_response(400, 'No rak hanya boleh huruf, angka, tanda - dan /.');
        }
        if (strlen($rak) > 50) {
            $this->make_ajax_response(400, 'No rak maksimal 50 karakter.');
        }

        $oleh  = isset($this->data['user']['name']) ? (string) $this->data['user']['name'] : '';
        $galat = $this->cabut_sku_fcd->simpan_pindah_ke($sku, $rak, $oleh);
        if ($galat !== '') {
            $this->make_ajax_response(400, $galat);
        }
        $this->make_ajax_response(200, 'No rak tujuan disimpan.', [
            'sku' => $sku, 'pindah_ke' => $rak, 'oleh' => $oleh, 'waktu' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ekspor Excel (xlsx) satu tab dengan filter layar. GET: tab (isi|adj|rak), kat[] (kategori), q (kata kunci).
     * Header tiga baris (Stok Jubelio -> gudang -> On Hand / On Order / On Reserve / On Available), rata tengah, garis tepi
     * semua sel; angka diberi warna sama dengan layar (hijau / merah / ungu / biru).
     */
    public function export()
    {
        $tab = (string) $this->input->get('tab');
        $judul = ['isi' => 'Perlu Isi Qty Real', 'adj' => 'Perlu Penyesuaian', 'rak' => 'Perlu Isi No Rak'];
        if (!isset($judul[$tab])) {
            $this->make_ajax_response(400, 'Tab tidak dikenal.');
        }
        $kat   = array_values(array_filter((array) $this->input->get('kat'), 'is_string'));
        $baris = $this->cabut_sku_fcd->daftar_tab($tab, $kat, (string) $this->input->get('q'));

        // Kolom stok: [grup, sub-judul, kunci data, jenis warna]. Transit hanya On Available.
        $stok = [];
        foreach ([['Display Barang (F19&F19A)', ['stok_display', 'disp_order', 'disp_reserved', 'disp_available']],
                  ['Gudang Utama (F16)', ['stok_gudang', 'gud_order', 'gud_reserved', 'gud_available']]] as $g) {
            foreach (['On Hand', 'On Order', 'On Reserve', 'On Available'] as $i => $nm) {
                $stok[] = [$g[0], $nm, $g[1][$i], $i];
            }
        }
        $stok[] = ['Transit', 'On Available', 'stok_transit', 3];
        foreach (['On Hand', 'On Order', 'On Reserve', 'On Available'] as $i => $nm) {
            $stok[] = ['Total Stok', $nm, ['stok_total', 'tot_order', 'tot_reserved', 'tot_available'][$i], $i];
        }
        $n_stok = count($stok); // 13

        $depan = ['No', 'SKU', 'Kategori SKU', 'No Rak'];
        $belakang = ['Qty Real'];
        if ($tab === 'adj') { $belakang[] = 'Selisih'; }
        if ($tab === 'rak') { $belakang[] = 'Pindah ke'; }
        $belakang[] = 'Diisi oleh';
        $belakang[] = 'Waktu diisi';
        $n_depan = count($depan);
        $n = $n_depan + $n_stok + count($belakang);
        $col = function ($i) { return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i); };
        $akhir = $col($n);
        $c_stok1 = $col($n_depan + 1);
        $c_stok2 = $col($n_depan + $n_stok);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle($judul[$tab]);

        // Kolom di luar stok: judul di baris 1, digabung vertikal sampai baris 3.
        foreach (array_merge($depan, [], []) as $i => $nama) {
            $h = $col($i + 1);
            $ws->setCellValue($h . '1', $nama);
            $ws->mergeCells($h . '1:' . $h . '3');
        }
        foreach ($belakang as $i => $nama) {
            $h = $col($n_depan + $n_stok + $i + 1);
            $ws->setCellValue($h . '1', $nama);
            $ws->mergeCells($h . '1:' . $h . '3');
        }
        $ws->setCellValue($c_stok1 . '1', 'Stok Jubelio');
        $ws->mergeCells($c_stok1 . '1:' . $c_stok2 . '1');
        // Baris 2: nama gudang (digabung per grup); baris 3: jenis stok.
        $awal = 0;
        while ($awal < $n_stok) {
            $akhir_g = $awal;
            while ($akhir_g + 1 < $n_stok && $stok[$akhir_g + 1][0] === $stok[$awal][0]) { $akhir_g++; }
            $a1 = $col($n_depan + $awal + 1);
            $a2 = $col($n_depan + $akhir_g + 1);
            $ws->setCellValue($a1 . '2', $stok[$awal][0]);
            if ($akhir_g > $awal) { $ws->mergeCells($a1 . '2:' . $a2 . '2'); }
            $awal = $akhir_g + 1;
        }
        foreach ($stok as $i => $k) {
            $ws->setCellValue($col($n_depan + $i + 1) . '3', $k[1]);
        }

        $r = 4;
        foreach ($baris as $i => $b) {
            $nilai = [$i + 1, (string) $b['id_sku'], (string) $b['kategori_sku'], (string) $b['rak_jubelio']];
            foreach ($stok as $k) {
                $nilai[] = $b[$k[2]] === null ? '' : (int) $b[$k[2]];
            }
            $nilai[] = $b['qty_real'] === null ? '' : (int) $b['qty_real'];
            if ($tab === 'adj') { $nilai[] = (int) $b['qty_real'] - (int) $b['stok_total']; }
            if ($tab === 'rak') { $nilai[] = (string) $b['pindah_ke']; }
            $nilai[] = (string) $b['qty_real_oleh'];
            $nilai[] = $b['qty_real_at'] ? date('d/m/Y H:i', strtotime($b['qty_real_at'])) : '';
            foreach ($nilai as $j => $v) {
                // Teks eksplisit supaya SKU angka panjang tidak berubah jadi notasi ilmiah / rumus.
                if (is_string($v)) {
                    $ws->setCellValueExplicit($col($j + 1) . $r, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $ws->setCellValue($col($j + 1) . $r, $v);
                }
            }
            $r++;
        }
        $terakhir = max(4, $r - 1);

        $rentang = 'A1:' . $akhir . $terakhir;
        $ws->getStyle($rentang)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        $ws->getStyle($rentang)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $head = $ws->getStyle('A1:' . $akhir . '3');
        $head->getFont()->setBold(TRUE)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        foreach ([1, 2, 3] as $br) {
            $ws->getRowDimension($br)->setRowHeight(22);
        }
        // Baris 2 (nama gudang) abu muda; baris 3 dan angka berwarna per jenis: hijau / merah / ungu / biru.
        $ws->getStyle($c_stok1 . '1:' . $c_stok2 . '1')->getFill()->getStartColor()->setRGB('4338CA');
        $ws->getStyle($c_stok1 . '2:' . $c_stok2 . '2')->getFill()->getStartColor()->setRGB('E2E8F0');
        $ws->getStyle($c_stok1 . '2:' . $c_stok2 . '2')->getFont()->getColor()->setRGB('334155');
        $warna = [['DCFCE7', '15803D'], ['FEE2E2', 'B91C1C'], ['F3E8FF', '7E22CE'], ['DBEAFE', '1D4ED8']];
        foreach ($stok as $i => $k) {
            $h = $col($n_depan + $i + 1);
            $ws->getStyle($h . '3')->getFill()->getStartColor()->setRGB($warna[$k[3]][0]);
            $ws->getStyle($h . '3')->getFont()->getColor()->setRGB($warna[$k[3]][1]);
            $ws->getStyle($h . '4:' . $h . $terakhir)->getFont()->setBold(TRUE)->getColor()->setRGB($warna[$k[3]][1]);
        }
        foreach (array_merge($depan, array_fill(0, $n_stok, ''), $belakang) as $i => $nama) {
            if ($i >= $n_depan && $i < $n_depan + $n_stok) {
                $w = 13;
            } else {
                $w = ['No' => 6, 'SKU' => 24, 'Kategori SKU' => 14, 'No Rak' => 16, 'Qty Real' => 11, 'Selisih' => 11, 'Diisi oleh' => 22, 'Waktu diisi' => 18][$nama] ?? 16;
            }
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $ws->freezePane('A4');
        if ($r > 4) {
            $ws->setAutoFilter('A3:' . $akhir . $terakhir);
        }

        $nama_berkas = 'cabut-sku-' . $tab . '-' . date('Ymd-His') . '.xlsx';
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
     * Simpan qty real satu SKU. POST multipart: sku, qty_real, foto. Guard qty: hanya bilangan bulat >= 0 (digit saja,
     * maks 9 digit); desimal ("9,2" / "9.2"), huruf ("sembilan"), tanda minus, spasi di tengah, dan kosong ditolak.
     * Guard foto: wajib, JPG/PNG/WebP (dicek isi berkas, bukan ekstensi), maks 5 MB, minimal 200x200 piksel.
     */
    public function simpan_qty_real()
    {
        $sku = trim((string) $this->input->post('sku'));
        $raw = trim((string) $this->input->post('qty_real'));

        if ($sku === '') {
            $this->make_ajax_response(400, 'SKU tidak boleh kosong.');
        }
        if ($raw === '') {
            $this->make_ajax_response(400, 'Qty real wajib diisi.');
        }
        if (!preg_match('/^[0-9]{1,9}$/D', $raw)) {
            $this->make_ajax_response(400, 'Qty real harus bilangan bulat (contoh: 9). Desimal seperti 9,2 atau huruf seperti sembilan ditolak.');
        }

        if (!$this->cabut_sku_fcd->ada($sku)) {
            $this->make_ajax_response(404, 'SKU ' . $sku . ' tidak ditemukan di data Cabut SKU. Klik Perbarui lalu coba lagi.');
        }

        // Foto bukti wajib. Diperiksa sebelum disimpan ke disk supaya tidak ada berkas yatim.
        $f = isset($_FILES['foto']) ? $_FILES['foto'] : null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $this->make_ajax_response(400, 'Foto wajib diunggah sebagai bukti qty real.');
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $this->make_ajax_response(400, ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
                ? 'Ukuran foto melebihi batas server (maks 5 MB).'
                : 'Foto gagal terunggah (kode ' . (int) $f['error'] . '). Coba lagi.');
        }
        if ($f['size'] > self::MAKS_FOTO_BYTE) {
            $this->make_ajax_response(400, 'Ukuran foto ' . round($f['size'] / 1048576, 1) . ' MB melebihi batas 5 MB.');
        }
        $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : '';
        $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($ekstensi[$mime])) {
            $this->make_ajax_response(400, 'Format foto harus JPG, PNG, atau WebP.');
        }
        $ukuran = @getimagesize($f['tmp_name']);
        if (!$ukuran || $ukuran[0] < 200 || $ukuran[1] < 200) {
            $this->make_ajax_response(400, 'Berkas bukan foto yang valid atau terlalu kecil (minimal 200 x 200 piksel).');
        }

        $dir = FCPATH . 'assets/uploads/cabut_sku/';
        if (!is_dir($dir) && !@mkdir($dir, 0775, TRUE)) {
            $this->make_ajax_response(500, 'Folder foto tidak bisa dibuat di server.');
        }
        $nama = 'cabut_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi[$mime];
        if (!@move_uploaded_file($f['tmp_name'], $dir . $nama)) {
            $this->make_ajax_response(500, 'Foto gagal disimpan di server.');
        }

        $oleh = isset($this->data['user']['name']) ? (string) $this->data['user']['name'] : '';
        if (!$this->cabut_sku_fcd->simpan_qty_real($sku, (int) $raw, $oleh, $nama)) {
            @unlink($dir . $nama);
            $this->make_ajax_response(500, 'Gagal menyimpan qty real ke database. Coba lagi.');
        }

        $this->make_ajax_response(200, 'Qty real disimpan.', [
            'sku'      => $sku,
            'qty_real' => (int) $raw,
            'oleh'     => $oleh,
            'waktu'    => date('Y-m-d H:i:s'),
            'foto'     => $nama,
        ]);
    }
}
