<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        // Allow CLI and token-authenticated HTTP (for chatbot callback)
        if (!is_cli()) {
            $token = $this->input->get('token');
            $this->load->config('whatsapp', TRUE);
            $expected = $this->config->item('wa_api_token', 'whatsapp');

            if ($token !== $expected) {
                show_error('Access denied.', 403);
                exit;
            }
        }

        $this->load->database();
        $this->load->model('laporan_fcd');
        $this->load->model('rts_fcd');
        $this->load->model('resi_team_fcd');
        $this->load->library('wa_gateway');
    }

    public function sisa_resi()
    {
        $msg = $this->laporan_fcd->format_wa_sisa_resi();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_sisa_resi', $result);
    }

    public function paket_keluar()
    {
        $msg = $this->laporan_fcd->format_wa_paket_keluar();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_paket_keluar', $result);
    }

    public function rts_check()
    {
        $hour = (int) date('H');
        $trip = ($hour < 18) ? 1 : 2;

        $data = $this->rts_fcd->check_rts($trip)->result();
        $jumlah = count($data);

        $resi_list = array_map(function ($r) {
            return $r->noresi;
        }, $data);

        $this->rts_fcd->log_rts_cycle($trip, $jumlah, $resi_list);

        if ($jumlah > 0) {
            $ids = array_map(function ($r) {
                return $r->id_printresi;
            }, $data);
            $this->rts_fcd->mark_checked($ids);
        }

        $msg = $this->rts_fcd->format_wa_rts($trip);

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_rts_check', $result);
    }

    public function control_pengiriman()
    {
        $msg = $this->laporan_fcd->format_wa_control_pengiriman();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_control_pengiriman', $result);
    }

    public function auto_sisa_resi()
    {
        $lock_file = FCPATH . 'logs/sent_sisa_resi_' . date('Y-m-d') . '.lock';
        if (file_exists($lock_file)) {
            echo date('Y-m-d H:i:s') . " [auto_sisa_resi] SKIP: sudah dikirim hari ini\n";
            return;
        }

        $hour = (int)date('H');

        if ($hour < 18) {
            echo date('Y-m-d H:i:s') . " [auto_sisa_resi] SKIP: belum jam 18\n";
            return;
        }

        // Deadline jam 20:00 — kirim paksa meski HO masih jalan
        if ($hour < 20) {
            $last_ho = $this->laporan_fcd->get_last_ho_scan();
            $diff_minutes = (time() - strtotime($last_ho)) / 60;

            if ($diff_minutes < 30) {
                echo date('Y-m-d H:i:s') . " [auto_sisa_resi] WAIT: last HO {$last_ho} ({$diff_minutes} menit lalu)\n";
                return;
            }
        } else {
            echo date('Y-m-d H:i:s') . " [auto_sisa_resi] FORCE: melewati deadline jam 20:00\n";
        }

        $msg = $this->laporan_fcd->format_wa_sisa_resi();
        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('auto_sisa_resi', $result);

        if (isset($result['success']) && $result['success']) {
            file_put_contents($lock_file, date('Y-m-d H:i:s'));
        }
    }

    public function auto_paket_keluar()
    {
        $lock_file = FCPATH . 'logs/sent_paket_keluar_' . date('Y-m-d') . '.lock';
        if (file_exists($lock_file)) {
            echo date('Y-m-d H:i:s') . " [auto_paket_keluar] SKIP: sudah dikirim hari ini\n";
            return;
        }

        $hour = (int)date('H');

        if ($hour < 17) {
            echo date('Y-m-d H:i:s') . " [auto_paket_keluar] SKIP: belum jam 17\n";
            return;
        }

        // Deadline jam 19:00 — kirim paksa meski masih ada aktivitas HO
        if ($hour < 19) {
            $last_ho = $this->laporan_fcd->get_last_ho_scan();
            $diff_minutes = (time() - strtotime($last_ho)) / 60;

            if ($diff_minutes < 60) {
                echo date('Y-m-d H:i:s') . " [auto_paket_keluar] WAIT: last HO {$last_ho} ({$diff_minutes} menit lalu)\n";
                return;
            }
        } else {
            echo date('Y-m-d H:i:s') . " [auto_paket_keluar] FORCE: melewati deadline jam 19:00\n";
        }

        $msg = $this->laporan_fcd->format_wa_paket_keluar();
        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('auto_paket_keluar', $result);

        if (isset($result['success']) && $result['success']) {
            file_put_contents($lock_file, date('Y-m-d H:i:s'));
        }
    }

    public function selisih_paket()
    {
        $render_url = 'http://localhost:8080/new-iresis/index.php/cron/render_selisih?token=' . $this->config->item('wa_api_token', 'whatsapp');
        $caption    = '📋 Monitoring Selisih Paket — ' . date('d F Y');

        $result = $this->wa_gateway->send_screenshot_to_group($render_url, $caption);
        $this->_log('cron_selisih_paket', $result);
    }

    public function render_selisih()
    {
        $date  = date('Y-m-d');
        $start = $date . ' 00:00:00';
        $end   = $date . ' 23:59:59';

        $data['top_stats'] = $this->resi_team_fcd->get_top_stats($date);
        $groups = $this->resi_team_fcd->get_optimized_selisih_bundle($start, $end);

        $process = function($pools) {
            $pool1 = $pools['pool1'] ?? [];
            $pool2 = $pools['pool2'] ?? [];
            $pool4 = $pools['pool4'] ?? [];
            $keys  = ['total_resi', 'sku_special', 'resi_qty_banyak', 'resi_1_sku_sd_9', 'resi_2_9_sku_sd_9'];
            $pool3 = []; $pool5 = [];
            foreach ($keys as $k) {
                $pool3[$k] = (int)($pool1[$k] ?? 0) + (int)($pool2[$k] ?? 0);
                $pool5[$k] = (int)($pool1[$k] ?? 0) + (int)($pool2[$k] ?? 0) + (int)($pool4[$k] ?? 0);
            }
            return ['pool1' => $pool1, 'pool2' => $pool2, 'pool3' => $pool3, 'pool4' => $pool4, 'pool5' => $pool5];
        };

        $data['wajib'] = $process($groups['WAJIB']);
        $data['sisa']  = $process($groups['SISA']);

        $this->load->view('laporan/render_selisih_paket', $data);
    }

    public function render_control()
    {
        $data['rows'] = $this->laporan_fcd->get_control_pengiriman()->result();
        $this->load->view('laporan/render_control_pengiriman', $data);
    }

    public function ekspedisi_urgent()
    {
        $msg = $this->laporan_fcd->format_wa_ekspedisi_urgent();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_ekspedisi_urgent', $result);
    }

    public function rekap_target_wa()
    {
        $msg = $this->laporan_fcd->format_wa_rekap_target();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_rekap_target', $result);
    }

    public function laporan_pagi()
    {
        $token   = $this->config->item('wa_api_token', 'whatsapp');
        $render_url = 'http://localhost:8080/new-iresis/index.php/cron/render_laporan_pagi?token=' . $token;
        $caption = '🌅 Laporan Produksi Pagi — ' . date('d F Y');

        if ($this->_is_text_output()) {
            echo $this->laporan_fcd->format_wa_laporan_pagi();
            return;
        }

        $result = $this->wa_gateway->send_screenshot_to_group($render_url, $caption);
        $this->_log('laporan_pagi', $result);
    }

    public function render_laporan_pagi()
    {
        $tanggal = date('Y-m-d');
        $data['backlog'] = $this->laporan_fcd->get_backlog_per_marketplace($tanggal)->result();
        $data['wajib']   = $this->laporan_fcd->get_resi_wajib_marketplace($tanggal)->result();
        $this->load->view('laporan/render_laporan_pagi', $data);
    }

    public function laporan_siang()
    {
        $token      = $this->config->item('wa_api_token', 'whatsapp');
        $render_url = 'http://localhost:8080/new-iresis/index.php/cron/render_laporan_siang?token=' . $token;
        $caption    = '☀️ Laporan Produksi Siang — ' . date('d F Y');

        if ($this->_is_text_output()) {
            echo $this->laporan_fcd->format_wa_laporan_siang();
            return;
        }

        $result = $this->wa_gateway->send_screenshot_to_group($render_url, $caption);
        $this->_log('laporan_siang', $result);
    }

    public function render_laporan_siang()
    {
        $tanggal = date('Y-m-d');
        $data['wajib']    = $this->laporan_fcd->get_resi_wajib_marketplace($tanggal)->result();
        $data['selesai']  = $this->laporan_fcd->get_selesai_per_marketplace($tanggal)->result();
        $data['total_ho'] = $this->laporan_fcd->get_total_ho_hari_ini($tanggal);
        $this->load->view('laporan/render_laporan_siang', $data);
    }

    public function laporan_sore_rts()
    {
        $msg = $this->laporan_fcd->format_wa_laporan_sore_rts();
        if ($this->_is_text_output()) { echo $msg; return; }
        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('laporan_sore_rts', $result);
    }

    public function laporan_sore_produksi()
    {
        $token      = $this->config->item('wa_api_token', 'whatsapp');
        $render_url = 'http://localhost:8080/new-iresis/index.php/cron/render_laporan_sore?token=' . $token;
        $caption    = '🌆 Laporan Produksi Sore — ' . date('d F Y');

        if ($this->_is_text_output()) {
            echo $this->laporan_fcd->format_wa_laporan_sore_produksi();
            return;
        }

        $result = $this->wa_gateway->send_screenshot_to_group($render_url, $caption);
        $this->_log('laporan_sore_produksi', $result);
    }

    public function render_laporan_sore()
    {
        $tanggal = date('Y-m-d');
        $data['pickers']    = $this->laporan_fcd->get_totalan_picker($tanggal)->result();
        $data['packers']    = $this->laporan_fcd->get_totalan_packer($tanggal)->result();
        $data['total_ho']   = $this->laporan_fcd->get_total_ho_hari_ini($tanggal);
        $data['per_mp']     = $this->laporan_fcd->get_selesai_per_marketplace($tanggal)->result();
        $data['sisa_wajib'] = $this->laporan_fcd->get_sisa_packing_wajib($tanggal)->result();
        $this->load->view('laporan/render_laporan_sore', $data);
    }

    public function laporan_eod()
    {
        $token      = $this->config->item('wa_api_token', 'whatsapp');
        $render_url = 'http://localhost:8080/new-iresis/index.php/cron/render_laporan_eod?token=' . $token;
        $caption    = '🌙 Rekap Akhir Hari — HO Selesai ' . date('d F Y');

        if ($this->_is_text_output()) {
            echo $this->laporan_fcd->format_wa_laporan_sore_produksi();
            return;
        }

        $result = $this->wa_gateway->send_screenshot_to_group($render_url, $caption);
        $this->_log('laporan_eod', $result);
    }

    public function render_laporan_eod()
    {
        $tanggal = date('Y-m-d');
        $data['pickers']     = $this->laporan_fcd->get_totalan_picker($tanggal)->result();
        $data['packers']     = $this->laporan_fcd->get_totalan_packer($tanggal)->result();
        $data['total_ho']    = $this->laporan_fcd->get_total_ho_hari_ini($tanggal);
        $data['per_mp']      = $this->laporan_fcd->get_selesai_per_marketplace($tanggal)->result();
        $data['sisa_wajib']  = $this->laporan_fcd->get_sisa_packing_wajib($tanggal)->result();
        $data['paket_keluar']= $this->laporan_fcd->get_paket_keluar_hari_ini($tanggal)->result();
        $this->load->view('laporan/render_laporan_eod', $data);
    }

    public function auto_upload_sku()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        header('Content-Type: application/json');

        try {
            // Terima file xlsx dari script Python (sama seperti auto_upload_resi)
            if (empty($_FILES['skuFile']['tmp_name'])) {
                throw new \Exception('File skuFile tidak ditemukan');
            }

            $file = $_FILES['skuFile'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                throw new \Exception('Format file harus .xls atau .xlsx');
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($file['tmp_name'])
            );
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
            $sheet       = $spreadsheet->getActiveSheet();
            $dataRaw     = $sheet->toArray(null, true, true, true);

            $this->load->model('sku_fcd');
            $result = $this->sku_fcd->insert_sku_upload($dataRaw, 0, 'cron_' . date('YmdHis'));

            $this->_log('auto_upload_sku', ['success' => true, 'message' => $result]);
            echo json_encode(['success' => true, 'message' => $result]);
        } catch (\Throwable $e) {
            $this->_log('auto_upload_sku', ['success' => false, 'message' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function auto_upload_resi()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['receiptFile']) || $_FILES['receiptFile']['error'] != 0) {
                throw new \Exception('File tidak ditemukan atau error upload');
            }

            $file = $_FILES['receiptFile']['tmp_name'];
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($file)
            );
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $dataRaw = $sheet->toArray(null, true, true, true);

            $this->load->model('receipt_fcd');
            $result = $this->receipt_fcd->insert_receipt($dataRaw, null);

            $this->_log('auto_upload_resi', ['success' => true, 'message' => $result]);
            echo json_encode(['success' => true, 'message' => $result]);
        } catch (\Throwable $e) {
            $this->_log('auto_upload_resi', ['success' => false, 'message' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Versi API (pure-JSON) dari auto_upload_resi — dipakai oleh
     * scripts/auto_upload_resi_api.py yang narik data langsung dari
     * Jubelio core-api (tanpa browser/xlsx). Body: JSON array baris,
     * key A..V sama persis seperti hasil parsing xlsx lama, supaya bisa
     * reuse insert_receipt() apa adanya.
     */
    public function auto_upload_resi_api()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        header('Content-Type: application/json');

        try {
            $raw  = file_get_contents('php://input');
            $rows = json_decode($raw, true);

            if (!is_array($rows) || empty($rows)) {
                throw new \Exception('Data JSON kosong atau tidak valid');
            }

            // admin_pegawai di tblprintresi NOT NULL tanpa default -> user_id
            // WAJIB nilai valid (bukan null), beda dari auto_upload_resi() versi
            // xlsx yang pass null dan diam-diam bergantung ke lenient sql_mode.
            $this->load->model('receipt_fcd');
            $result = $this->receipt_fcd->insert_receipt($rows, '0');

            $this->_log('auto_upload_resi_api', ['success' => true, 'message' => $result]);
            echo json_encode(['success' => true, 'message' => $result]);
        } catch (\Throwable $e) {
            $this->_log('auto_upload_resi_api', ['success' => false, 'message' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Balikin status_pesanan SAAT INI (di iresis) untuk daftar noresi yang
     * dikirim, supaya scripts/auto_upload_resi_api.py bisa SKIP panggil detail
     * Jubelio untuk resi yang statusnya TIDAK BERUBAH sejak upload terakhir
     * (bukan cuma yang COMPLETED — mayoritas resi di window H-3 sudah SHIPPED,
     * skip status COMPLETED saja tidak banyak membantu).
     * Body: {"noresi": ["AWB1","AWB2",...]}
     * Balas: {"success":true, "status_map": {"AWB1":"SHIPPED", ...}}
     */
    public function check_resi_status()
    {
        header('Content-Type: application/json');

        try {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw, true);
            $noresi_list = $body['noresi'] ?? [];

            if (!is_array($noresi_list) || empty($noresi_list)) {
                echo json_encode(['success' => true, 'status_map' => []]);
                exit;
            }

            $this->load->model('receipt_fcd');
            $status_map = $this->receipt_fcd->get_status_map($noresi_list);

            echo json_encode(['success' => true, 'status_map' => $status_map]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * PERBAIKAN SEKALI-JALAN: memperbaiki qty (jumlah) & no_rak pada
     * tbldetailprintresi yang rusak akibat bug run auto_upload_resi_api
     * 2026-07-04. Dipanggil oleh scripts/fix_resi_detail_20260704.py.
     * NON-DESTRUKTIF: tidak menyentuh header/picklist/status.
     * Body: {"corrections":[{"noresi":..,"items":[{"sku":..,"qty":int},..]},..]}
     */
    public function fix_resi_detail()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        header('Content-Type: application/json');

        try {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw, true);
            $corrections = $body['corrections'] ?? [];

            if (!is_array($corrections) || empty($corrections)) {
                throw new \Exception('Data corrections kosong atau tidak valid');
            }

            $this->load->model('receipt_fcd');
            $result = $this->receipt_fcd->fix_detail_from_jubelio($corrections);

            echo json_encode(array_merge(['success' => true], $result));
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Upload otomatis file "daftar retur penjualan" dari Jubelio (dipanggil oleh
     * scripts/auto_upload_retur_jubelio.py). Menggunakan logika parsing & insert
     * yang sama dengan menu manual Retur::upload_jubelio (retur_fcd).
     *
     * Field file  : jubelioFile
     * Auth        : ?token=<wa_api_token> (dicek di constructor Cron)
     */
    public function auto_upload_retur_jubelio()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['jubelioFile']) || $_FILES['jubelioFile']['error'] != 0) {
                throw new \Exception('File tidak ditemukan atau error upload');
            }

            $file = $_FILES['jubelioFile']['tmp_name'];

            $this->load->model('retur_fcd');
            $rows = $this->retur_fcd->parse_jubelio_spreadsheet($file);

            if (empty($rows)) {
                throw new \Exception('Tidak ada baris data valid di file (semua baris kosong).');
            }

            $batch_id = date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 6);
            $result   = $this->retur_fcd->insert_jubelio_batch($rows, $batch_id, null);

            $msg = "Upload retur Jubelio selesai: {$result['inserted']} baris baru, "
                 . "{$result['updated']} diperbarui, {$result['matched']} cocok dengan scan iresis "
                 . "(batch {$batch_id}).";

            $this->_log('auto_upload_retur_jubelio', ['success' => true, 'message' => $msg]);
            echo json_encode(array_merge(
                ['success' => true, 'message' => $msg, 'batch_id' => $batch_id],
                $result
            ));
        } catch (\Throwable $e) {
            $this->_log('auto_upload_retur_jubelio', ['success' => false, 'message' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Tutup baris video packing yang menggantung di status MEREKAM.
     *
     * Baris ditandai MEREKAM sejak potongan pertama masuk dan baru jadi SELESAI
     * kalau potongan penutup sempat naik. Kalau tab packer mati mendadak --
     * Chrome crash, PC padam, jaringan putus di tengah packing -- potongan
     * penutup itu tidak pernah dikirim, dan barisnya menggantung selamanya.
     *
     * Berkasnya sendiri tetap utuh sampai potongan terakhir yang berhasil naik
     * dan tetap bisa diputar, jadi yang dibereskan di sini murni labelnya:
     * MEREKAM -> TERPUTUS, supaya CS bisa membedakan rekaman yang benar-benar
     * masih berjalan dari rekaman yang mati di tengah jalan.
     *
     * Jalankan berkala, mis. tiap 15 menit:
     *   php index.php cron tutup_video_menggantung
     *   php index.php cron tutup_video_menggantung 45   (ambang khusus)
     */
    public function tutup_video_menggantung($menit = null)
    {
        // Ambang HARUS lebih longgar dari batas durasi rekaman di browser
        // (MAKS_DURASI_DTK di assets/js/packer_video.js = 90 menit). Kalau lebih
        // pendek, rekaman yang masih berjalan ikut ditandai terputus: packing
        // resi berisi ratusan barang memang wajar memakan satu jam, dan selama
        // itu barisnya memang berstatus MEREKAM.
        //
        // Argumen diambil dari parameter CLI kalau ada; $_GET tidak terisi saat
        // dijalankan lewat command line, jadi query string hanya berlaku untuk
        // pemanggilan HTTP ber-token.
        $ambang_menit = (int) ($menit !== null ? $menit : $this->input->get('menit'));
        if ($ambang_menit <= 0) {
            $ambang_menit = 120;
        }

        $batas = date('Y-m-d H:i:s', time() - ($ambang_menit * 60));

        $this->db->where('status', 'MEREKAM');
        $this->db->where('mulai_at <', $batas);
        $this->db->update('tblvideopacking', ['status' => 'TERPUTUS']);

        $jumlah = $this->db->affected_rows();

        $this->_log('cron_tutup_video_menggantung', [
            'success'      => TRUE,
            'ditandai'     => $jumlah,
            'ambang_menit' => $ambang_menit,
        ]);
    }

    /**
     * Finalisasi rekaman video packing dengan ffmpeg. Dua pekerjaan:
     *
     *  1. Remux WebM yang sudah selesai/terputus supaya punya durasi dan cues
     *     (keluaran MediaRecorder tidak punya keduanya -> player CS tidak tahu
     *     panjang video dan seek-nya meleset). Tanpa encode ulang, hitungan detik.
     *  2. Konversi ke MP4 H.264 untuk baris yang diminta CS lewat tombol
     *     "Siapkan MP4" (WebM tidak bisa diputar di iPhone/WhatsApp). Ini berat
     *     (~15 detik per menit video), jadi hanya satu transcode yang boleh
     *     berjalan pada satu waktu di server.
     *
     * Aman dijadwalkan tiap menit walau proses sebelumnya belum selesai: klaim
     * baris dilakukan atomik di model, jadi dua cron yang hidup bersamaan tidak
     * mengerjakan berkas yang sama. Kalau ffmpeg tidak terpasang, cron hanya
     * mencatat itu dan keluar -- rekaman tetap bisa diputar seperti biasa.
     *
     *   php index.php cron finalisasi_video
     *   php index.php cron finalisasi_video 5   (maks. 5 remux per jalan)
     */
    public function finalisasi_video($maks_remux = null)
    {
        $this->load->model('video_packing_fcd');
        $this->load->library('video_ffmpeg');

        set_time_limit(0);

        if (!$this->video_ffmpeg->tersedia()) {
            $this->_log('cron_finalisasi_video', [
                'success' => FALSE,
                'pesan'   => 'ffmpeg tidak bisa dijalankan: ' . $this->video_ffmpeg->path_ffmpeg()
                    . ' -- pasang ffmpeg atau isi ffmpeg_path di secrets.php',
            ]);
            return;
        }

        $maks_remux = (int) ($maks_remux !== null ? $maks_remux : $this->input->get('maks'));
        if ($maks_remux <= 0) {
            $maks_remux = 20;
        }

        $ringkas = ['remux_ok' => 0, 'remux_gagal' => 0, 'mp4_ok' => 0, 'mp4_gagal' => 0, 'mp4_dilewati' => FALSE];

        // ---- 1. remux ---------------------------------------------------
        for ($i = 0; $i < $maks_remux; $i++) {
            $row = $this->video_packing_fcd->klaim_finalisasi();
            if (!$row) {
                break;
            }

            $path  = $this->video_packing_fcd->path_berkas($row);
            $hasil = $this->video_ffmpeg->remux_webm($path);

            if ($hasil['sukses']) {
                $update = [
                    'finalisasi'       => 'SELESAI',
                    'finalisasi_at'    => date('Y-m-d H:i:s'),
                    'finalisasi_pesan' => NULL,
                    'ukuran_byte'      => (int) @filesize($path),
                ];
                // Durasi dari ffprobe lebih akurat daripada hitungan browser,
                // apalagi untuk rekaman yang terputus di tengah.
                if ($hasil['durasi'] > 0) {
                    $update['durasi_detik'] = (int) round($hasil['durasi']);
                }
                $this->video_packing_fcd->update_by_id($row->id_videopacking, $update);
                $ringkas['remux_ok']++;
            } else {
                // Berkas hilang tidak akan sembuh dengan diulang; selain itu
                // (mis. sedang diputar CS) dicoba lagi sampai lima kali.
                $percobaan = (int) $row->finalisasi_percobaan;
                $menyerah  = !is_file($path) || $percobaan >= 5;

                $this->video_packing_fcd->update_by_id($row->id_videopacking, [
                    'finalisasi'       => $menyerah ? 'GAGAL' : 'BELUM',
                    'finalisasi_pesan' => substr($hasil['pesan'], 0, 255),
                ]);
                $ringkas['remux_gagal']++;
            }
        }

        // ---- 2. MP4 atas permintaan -------------------------------------
        if ($this->video_packing_fcd->jumlah_mp4_proses() > 0) {
            $ringkas['mp4_dilewati'] = TRUE;
        } else {
            $row = $this->video_packing_fcd->klaim_mp4();

            if ($row) {
                $sumber = $this->video_packing_fcd->path_berkas($row);
                $nama   = $this->video_packing_fcd->nama_mp4_untuk($row);
                $tujuan = $this->video_packing_fcd->root_upload()
                    . Video_packing_fcd::SUBFOLDER_MP4 . '/' . $nama;

                $hasil = $this->video_ffmpeg->ke_mp4($sumber, $tujuan);

                if ($hasil['sukses']) {
                    $this->video_packing_fcd->update_by_id($row->id_videopacking, [
                        'mp4_status'      => 'SIAP',
                        'mp4_nama_file'   => $nama,
                        'mp4_ukuran_byte' => (int) $hasil['ukuran'],
                        'mp4_selesai_at'  => date('Y-m-d H:i:s'),
                        'mp4_pesan'       => NULL,
                    ]);
                    $ringkas['mp4_ok']++;
                } else {
                    $menyerah = !is_file($sumber) || (int) $row->mp4_percobaan >= 3;

                    $this->video_packing_fcd->update_by_id($row->id_videopacking, [
                        'mp4_status' => $menyerah ? 'GAGAL' : 'ANTRI',
                        'mp4_pesan'  => substr($hasil['pesan'], 0, 255),
                    ]);
                    $ringkas['mp4_gagal']++;
                }
            }
        }

        $ringkas['success'] = ($ringkas['remux_gagal'] + $ringkas['mp4_gagal']) === 0;
        $this->_log('cron_finalisasi_video', $ringkas);
    }

    private function _is_text_output()
    {
        return $this->input->get('output') === 'text';
    }

    private function _log($task, $result)
    {
        $status = (isset($result['success']) && $result['success']) ? 'OK' : 'FAIL';
        $detail = is_array($result) ? json_encode($result) : $result;
        $log_line = date('Y-m-d H:i:s') . " [{$task}] {$status}: {$detail}";

        echo $log_line . "\n";
        log_message('error', $log_line);
    }
}
