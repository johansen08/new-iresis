<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Receipt extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('receipt_fcd');
        $this->load->model('marketplace_fcd');
        $this->load->model('courrier_fcd');
        $this->load->model('receipt_reprint_fcd');
        $this->load->model('param_fcd');
        $this->load->model('picking_fcd');
        $this->load->model('user_fcd');
        $this->load->model('resi_team_fcd');
    }

    public function scan_receipt()
    {
        $data['list_marketplace'] = $this->marketplace_fcd->get_marketplace()->result_array();
        $data['list_courrier'] = $this->courrier_fcd->get_courrier()->result_array();
        $data['total_scan'] = $this->receipt_fcd->get_total_scan_user($this->data['user']['id_user'])->row()->total_scan;
        $this->show($data);
    }

    public function save_receipt()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $receipt['id_marketplace'] = $this->input->post('id_marketplace');
        $receipt['id_kurir'] = $this->input->post('id_kurir');
        $receipt['nomorpicklist'] = trim($this->input->post('nomorpicklist'));
        $receipt['noresi'] = trim($this->input->post('noresi'));

        $save = $this->receipt_fcd->save($receipt, $this->data['user']['id_user']);

        // Receipt_fcd::save() mengembalikan ['error' => TRUE, 'message' => ...]
        // untuk resi yang sudah completed, dan array itu tidak punya kunci
        // affected_rows. Tanpa cabang ini, PHP 8 melempar undefined array key
        // lalu responsnya jatuh ke NOTHING_TO_SAVE -- alasan gagal yang
        // sebenarnya hilang, dan halaman scan tidak bisa memilih suaranya.
        if (isset($save['error']) && $save['error'] === TRUE) {
            $this->make_ajax_response(
                isset($save['code']) ? $save['code'] : 400,
                $save['message']
            );
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA, ['id_printresi' => $save['id_printresi']]);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

    public function print_label($id_resi)
    {
        $data['receipt'] = $this->receipt_fcd->get_detail_by_id($id_resi)->row_array();
        if (empty($data['receipt'])) {
            show_404();
        }
        $this->load->view('receipt/print_label', $data);
    }

    public function detail_receipt()
    {
        $data = [];

        if ($this->input->method() == 'post') {
            $keyword = trim($this->input->post('noresi'));

            $data['noresi'] = $keyword;

            // Dukung input berupa nomor resi maupun hasil scan nomor pesanan.
            $noresi = $this->receipt_fcd->resolve_noresi($keyword);

            if (!empty($noresi)) {
                $data['receipt'] = $this->receipt_fcd->get_detail($noresi)->row_array();
                $data['receipt_items'] = $this->receipt_fcd->get_detail_items($noresi)->result_array();
            }
        }

        $this->show($data);
    }

    public function list_receipt()
    {
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_list_receipt_data()
    {
        $draw = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;

        $data['valid_columns'] = array(
            0 => null,
            1 => 't.noresi',
            2 => 't.created_at',
            3 => 't3.nama_kurir',
            4 => 't2.nama_marketplace',
            5 => 't.toko',
            6 => 't.nomorpicklist',
            7 => 't.status_pesanan',
            8 => null // Action column
        );

        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list_resi = $this->receipt_fcd->get_data($data);

        $total = $this->receipt_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $data = array();
        foreach ($list_resi->result() as $row) {
            $data[] = array(
                $i++ . '.',
                $row->noresi,
                date('Y-m-d H:i:s', strtotime($row->tanggal_printresi)),
                $row->nama_kurir,
                $row->nama_marketplace,
                $row->toko,
                $row->nomorpicklist,
                $row->status_pesanan,
                '<a href="receipt/delete-list-receipt-data/' . $row->id_printresi . '" class="btn btn-danger confirm" onClick="notyConfirm(event);"><i class="fa fa-trash-o"></i> </a>',
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );
        echo json_encode($output);
        exit();
    }

    public function delete_list_receipt_data($id_printresi)
    {
        $save = $this->receipt_fcd->destroy($id_printresi, $this->data['user']['id_user']);

        if ($save['affected_rows'] > 0) {
            $this->session->set_flashdata('noty_message', [
                'text' => 'Data berhasil dihapus.',
                'type' => 'success' // Noty supports: alert, success, error, warning, info
            ]);
            //$this->set_message('Success', SUCCESS_REMOVE_DATA, 'information');
        } else {
            $this->session->set_flashdata('noty_message', [
                'text' => 'Tidak ada data yang dihapus.',
                'type' => 'warning'
            ]);
            //$this->set_message('Warning', NOTHING_TO_SAVE, 'warning');
        }

        redirect('receipt/list_receipt');
    }

    public function print_pergantian_barang()
    {
        $noresi = $this->input->get('noresi');
        $sku = $this->input->get('sku');
        $qty = $this->input->get('qty');

        // Cari informasi SKU dan Nomor Pesanan jika memungkinkan
        $this->db->select('dr.no_pesanan');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->where('pr.noresi', $noresi);
        $resi_data = $this->db->get()->row_array();

        // Cari lokasi rak terbaru dari SKU tersebut jika ada
        $this->db->select('no_rak');
        $this->db->from('tblsku');
        $this->db->where('id_sku', $sku);
        $this->db->limit(1);
        $rak_data = $this->db->get()->row_array();

        $data['no_pesanan'] = $resi_data['no_pesanan'] ?? '-';
        $data['noresi'] = $noresi;
        $data['sku'] = $sku;
        $data['qty'] = $qty;
        $data['no_rak'] = $rak_data['no_rak'] ?? 'BELUM DITENTUKAN';

        $this->load->view('receipt/print_pergantian_barang', $data);
    }

    public function delete_receipt()
    {
        $data['list_marketplace'] = $this->marketplace_fcd->get_marketplace()->result_array();
        $data['list_courrier'] = $this->courrier_fcd->get_courrier()->result_array();
        $data['total_scan'] = $this->receipt_fcd->get_total_scan_user($this->data['user']['id_user'])->row()->total_scan;

        $this->show($data);
    }

    public function delete_receipt_action()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');

        $save = $this->receipt_fcd->destroy_by_noresi($noresi, $this->data['user']['id_user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

    public function reprint_receipt()
    {
        $data['list_reason'] = $this->param_fcd->get_param_by_group('REPRINT_RECEIPT_REASON')->result_array();

        $this->show($data);
    }

    public function save_reprint_receipt()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $receipt['alasan'] = $this->input->post('alasan');
        $receipt['keterangan'] = $this->input->post('keterangan');
        $receipt['noresi'] = $this->input->post('noresi');

        if (!empty($_FILES['images']['name'][0])) {
            if (count($_FILES['images']['name']) > 5) {
                $this->set_message('Warning', '5 image max to upload', 'warning');
                $this->show_index();
            }

            $images = $this->upload_reprint_receipt_file($_FILES['images']);

            if (empty($images)) {
                $this->set_message('Error', $this->upload->display_errors(), 'danger');
                $this->show_index();
            }

            $receipt['image'] = implode(',', $images);
        }

        $save = $this->receipt_reprint_fcd->save($receipt, $this->data['user']);

        if (isset($save['error'])) {
            $this->make_ajax_response($save['code'], $save['message']);
        }

        if ($save['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    public function upload_reprint_receipt_file($files)
    {
        $config = array(
            'upload_path' => 'uploads/transaction/',
            'allowed_types' => 'jpg|gif|png',
            'overwrite' => 1,
        );

        $this->load->library('upload', $config);

        $images = array();

        $i = 1;
        $time = time();
        foreach ($files['name'] as $key => $image) {
            $_FILES['images[]']['name'] = $files['name'][$key];
            $_FILES['images[]']['type'] = $files['type'][$key];
            $_FILES['images[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['images[]']['error'] = $files['error'][$key];
            $_FILES['images[]']['size'] = $files['size'][$key];

            $fileExt = pathinfo($_FILES['images[]']['name'], PATHINFO_EXTENSION);
            $fileName = '_' . $time . '_' . $i++ . '.' . $fileExt;

            $images[] = $fileName;

            $config['file_name'] = $fileName;

            $this->upload->initialize($config);

            if ($this->upload->do_upload('images[]')) {
                $this->upload->data();
            } else {
                return null;
            }
        }

        return $images;
    }

    public function upload_receipt()
    {
        $this->show();
    }

    /**
     * Menerima file laporan penjualan Jubelio (.xlsx/.xls) lalu mengimpornya.
     *
     * Alurnya:
     *  1. validasi file + token progres dari browser
     *  2. LEPAS KUNCI SESSION (session_write_close). Driver session 'files'
     *     mengunci berkas session selama request berjalan, jadi tanpa ini
     *     request lain dari user yang sama (termasuk polling progres) ikut
     *     menggantung sampai impor selesai -- itulah sebabnya indikator
     *     progres lama tidak pernah bergerak.
     *  3. baca file lewat Xlsx_cepat (fallback PhpSpreadsheet), impor lewat
     *     Receipt_fcd::insert_receipt() sambil menulis progres ke berkas
     *     application/cache/upload_resi/<token>.json
     *  4. balas JSON. Hasil akhir juga ditulis ke berkas progres, jadi kalau
     *     koneksi browser putus di tengah jalan, UI tetap bisa menampilkan
     *     hasilnya lewat upload_receipt_progress().
     */
    public function upload_receipt_action()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        // post_max_size terlampaui -> PHP mengosongkan $_FILES dan $_POST tanpa pesan
        $panjang_body = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if (empty($_FILES) && $panjang_body > 0 && $panjang_body > $this->ke_byte(ini_get('post_max_size'))) {
            $this->make_ajax_response(400, 'File terlalu besar. Batas maksimal server: ' . ini_get('post_max_size'));
        }

        if (!isset($_FILES['receiptFile'])) {
            $this->make_ajax_response(400, 'Tidak ada file yang dipilih');
        }

        $err = (int)$_FILES['receiptFile']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $msg = "Gagal mengunggah file. Kode error: $err";
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) $msg = 'File terlalu besar. Batas maksimal server: ' . ini_get('upload_max_filesize');
            if ($err === UPLOAD_ERR_PARTIAL) $msg = 'File hanya terunggah sebagian, coba unggah ulang.';
            if ($err === UPLOAD_ERR_NO_FILE) $msg = 'Tidak ada file yang diunggah.';
            $this->make_ajax_response(400, $msg);
        }

        $nama_file = $_FILES['receiptFile']['name'];
        $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        if (!in_array($ekstensi, ['xlsx', 'xls'], true)) {
            $this->make_ajax_response(400, "Format file .$ekstensi tidak didukung. Unggah file .xlsx atau .xls dari Jubelio.");
        }

        $token = $this->input->post('token');
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9]{8,40}$/', $token)) {
            $token = bin2hex(random_bytes(8));
        }

        $user_id = $this->data['user']['id_user'] ?? null;
        $file    = $_FILES['receiptFile']['tmp_name'];
        $ukuran  = (int)$_FILES['receiptFile']['size'];

        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        // Jangan hentikan impor di tengah transaksi hanya karena browser
        // menutup koneksi; hasilnya tetap tercatat di berkas progres.
        ignore_user_abort(true);

        // Lepas kunci session (lihat docblock). Setelah ini jangan menulis session.
        session_write_close();

        $this->bersihkan_progres_lama();
        $this->tulis_progres($token, 'proses', 'baca', 'File diterima (' . $this->format_ukuran($ukuran) . '), membaca isi Excel...', 5);

        // Kalau PHP mati fatal (mis. kehabisan memori) di tengah jalan, tandai
        // gagal supaya UI tidak menunggu selamanya.
        register_shutdown_function(function () use ($token) {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $progres = $this->baca_progres($token);
                if (($progres['status'] ?? '') === 'proses') {
                    $this->tulis_progres($token, 'gagal', 'error', 'Proses berhenti karena error server: ' . $e['message'], null);
                }
            }
        });

        try {
            $mulai = microtime(true);

            $this->load->library('xlsx_cepat');
            $jalur = null;
            $dataRaw = $this->xlsx_cepat->baca_dengan_fallback($file, 'W', $jalur);
            $rowCount = count($dataRaw);
            $lama_baca = round(microtime(true) - $mulai, 1);

            log_message('info', "Upload resi [$token] user $user_id: $nama_file, $rowCount baris dibaca via $jalur dalam {$lama_baca}s");

            $this->tulis_progres($token, 'proses', 'olah', number_format($rowCount, 0, ',', '.') . " baris terbaca ({$lama_baca} dtk), mulai mengimpor...", 15);

            $lapor = function (string $tahap, string $pesan, ?int $persen) use ($token) {
                $this->tulis_progres($token, 'proses', $tahap, $pesan, $persen);
            };
            $result = $this->receipt_fcd->insert_receipt($dataRaw, $user_id, $lapor);
            unset($dataRaw);

            $durasi = round(microtime(true) - $mulai, 1);

            // insert_receipt mengembalikan string "Error ..." bila transaksi gagal
            if (strpos($result, 'Error') === 0) {
                throw new Exception($result);
            }

            $ringkasan = "$result | Waktu: {$durasi} dtk";
            log_message('info', "Upload resi [$token] selesai: $ringkasan");
            $this->tulis_progres($token, 'selesai', 'selesai', $ringkasan, 100, $ringkasan);

            $this->make_ajax_response(201, $ringkasan, ['token' => $token, 'durasi' => $durasi, 'baris' => $rowCount]);

        } catch (\Throwable $e) {
            $error_message = 'Upload gagal: ' . $e->getMessage();
            log_message('error', "Upload resi [$token] gagal: " . $e->getMessage());
            $this->tulis_progres($token, 'gagal', 'error', $error_message, null);

            $this->make_ajax_response(500, $error_message, ['token' => $token]);
        }
    }

    /**
     * Dipanggil berkala oleh halaman Upload Resi (GET ?token=...) untuk
     * menampilkan tahap impor yang sedang berjalan, dan mengambil hasil akhir
     * bila koneksi upload-nya sempat putus.
     */
    public function upload_receipt_progress()
    {
        $token = $this->input->get('token');
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9]{8,40}$/', $token)) {
            $this->make_ajax_response(400, 'Token progres tidak valid');
        }

        $progres = $this->baca_progres($token);
        if ($progres === null) {
            $this->make_ajax_response(404, 'Belum ada catatan progres untuk token ini', ['status' => 'tidak_ada']);
        }

        $this->make_ajax_response(200, $progres['pesan'] ?? '', $progres);
    }

    // ---- Helper berkas progres upload -------------------------------------

    private function folder_progres(): string
    {
        $dir = APPPATH . 'cache/upload_resi';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function path_progres(string $token): string
    {
        return $this->folder_progres() . DIRECTORY_SEPARATOR . $token . '.json';
    }

    private function tulis_progres(string $token, string $status, string $tahap, string $pesan, ?int $persen, ?string $hasil = null): void
    {
        $lama = $this->baca_progres($token) ?: [];
        $data = [
            'status'     => $status,   // proses | selesai | gagal
            'tahap'      => $tahap,
            'pesan'      => $pesan,
            'persen'     => $persen ?? ($lama['persen'] ?? null),
            'hasil'      => $hasil ?? ($lama['hasil'] ?? null),
            'mulai'      => $lama['mulai'] ?? time(),
            'diperbarui' => time(),
        ];
        // Tulis ke berkas sementara lalu rename supaya pembaca tidak pernah
        // mendapat JSON setengah jadi.
        $path = $this->path_progres($token);
        $tmp  = $path . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($data)) !== false) {
            @rename($tmp, $path);
        }
    }

    private function baca_progres(string $token): ?array
    {
        $path = $this->path_progres($token);
        if (!is_file($path)) return null;
        $isi = @file_get_contents($path);
        $data = $isi ? json_decode($isi, true) : null;
        return is_array($data) ? $data : null;
    }

    /** Buang berkas progres yang lebih tua dari 1 hari. */
    private function bersihkan_progres_lama(): void
    {
        $batas = time() - 86400;
        foreach (glob($this->folder_progres() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
            if (@filemtime($f) < $batas) @unlink($f);
        }
    }

    /** "500M" / "2G" -> byte */
    private function ke_byte(string $nilai): int
    {
        $nilai = trim($nilai);
        $satuan = strtolower(substr($nilai, -1));
        $angka = (int)$nilai;
        switch ($satuan) {
            case 'g': return $angka * 1073741824;
            case 'm': return $angka * 1048576;
            case 'k': return $angka * 1024;
            default:  return $angka;
        }
    }

    private function format_ukuran(int $byte): string
    {
        if ($byte >= 1048576) return number_format($byte / 1048576, 1, ',', '.') . ' MB';
        if ($byte >= 1024) return number_format($byte / 1024, 0, ',', '.') . ' KB';
        return $byte . ' B';
    }

    public function scan_combined()
    {
        $data['title'] = 'SCAN COMBINED';
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        // Use User_fcd to get active users (for Packers)
        $data['list_packer'] = $this->user_fcd->get_user()->result_array();
        
        $this->show($data);
    }

    public function save_combined()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $id_picker = $this->input->post('id_picker');
        $id_packer = $this->input->post('id_packer');

        if (empty($noresi) || empty($id_picker) || empty($id_packer)) {
            $this->make_ajax_response(400, 'Nomor Resi, Picker dan Packer harus diisi.');
        }

        $result = $this->resi_team_fcd->save_combined_scan($noresi, $id_picker, $id_packer, $this->data['user']);

        if (isset($result['error']) && $result['error'] === TRUE) {
            $this->make_ajax_response($result['code'], $result['message']);
        }

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(201, SUCCESS_SAVE_DATA);
        }

        $this->make_ajax_response(400, NOTHING_TO_SAVE);
    }

}

