<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        // Bebas token saat CLI (Task Scheduler); lewat HTTP wajib ?token=
        // yang cocok dengan `cron_token` di secrets.php (dipakai scripts/auto_upload_*.py).
        if (!is_cli()) {
            require_once(APPPATH . 'config/secrets_load.php');
            $token    = $this->input->get('token');
            $expected = iresis_secret('cron_token');

            if (empty($expected) || $token !== $expected) {
                show_error('Access denied.', 403);
                exit;
            }
        }

        $this->load->database();
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
     * Auth        : ?token=<cron_token> (dicek di constructor Cron)
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
     *     (~13 detik per menit video di CPU server), jadi hanya satu transcode
     *     yang boleh berjalan pada satu waktu -- dijaga kunci bernama MariaDB
     *     (Video_packing_fcd::kunci_transcode_mp4), bukan hitungan baris PROSES.
     *     Pemegang kunci menghabiskan seluruh antrian MP4 dalam satu jalan
     *     (dulu satu berkas per menit, jadi tiga permintaan = tiga menit).
     *
     * Dipanggil dari dua arah: task terjadwal tiap menit (mode 'semua') dan
     * worker latar belakang yang dilepas Cs::minta_video_mp4 begitu CS menekan
     * tombol (mode 'mp4', supaya permintaan tidak menunggu putaran cron dan
     * antrian remux). Aman hidup bersamaan: klaim baris atomik di model dan
     * transcode dipagari kunci. Kalau ffmpeg tidak terpasang, cron hanya
     * mencatat itu dan keluar -- rekaman tetap bisa diputar seperti biasa.
     *
     *   php index.php cron finalisasi_video               (remux + MP4)
     *   php index.php cron finalisasi_video mp4           (hanya antrian MP4)
     *   php index.php cron finalisasi_video remux 5       (maks. 5 remux, tanpa MP4)
     */
    public function finalisasi_video($mode = 'semua', $maks_remux = null)
    {
        $this->load->model('video_packing_fcd');
        $this->load->library('video_ffmpeg');

        set_time_limit(0);

        $mode = in_array($mode, ['semua', 'remux', 'mp4'], TRUE) ? $mode : 'semua';

        if (!$this->video_ffmpeg->tersedia()) {
            $this->_log('cron_finalisasi_video', [
                'success' => FALSE,
                'mode'    => $mode,
                'pesan'   => 'ffmpeg tidak bisa dijalankan: ' . $this->video_ffmpeg->path_ffmpeg()
                    . ' -- pasang ffmpeg atau isi ffmpeg_path di secrets.php',
            ]);
            return;
        }

        $maks_remux = (int) ($maks_remux !== null ? $maks_remux : $this->input->get('maks'));
        if ($maks_remux <= 0) {
            $maks_remux = 20;
        }
        if ($mode === 'mp4') {
            $maks_remux = 0;
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
        if ($mode !== 'remux') {
            // Tunggu sebentar kalau kunci sedang dipegang: pemegangnya mungkin
            // sedang menutup putarannya (antrian kosong) dan sebentar lagi lepas,
            // sehingga permintaan yang baru masuk tidak harus menunggu cron
            // berikutnya. Kalau ia sedang mentranscode video panjang, menyerah
            // saja -- ia akan mengambil antrian ini sendiri sesudahnya.
            if (!$this->video_packing_fcd->kunci_transcode_mp4(self::TUNGGU_KUNCI_MP4_DETIK)) {
                $ringkas['mp4_dilewati'] = TRUE;
            } else {
                $ringkas['mp4_dipulihkan'] = $this->video_packing_fcd->pulihkan_mp4_terlantar();
                $mulai_antrian = time();

                // Habiskan antrian, tapi berhenti mengambil baris baru setelah
                // anggaran waktu lewat: task terjadwal dibatasi 2 jam, dan satu
                // transcode bisa sampai BATAS_DETIK_TRANSCODE (1 jam) sendiri.
                while ((time() - $mulai_antrian) < self::ANGGARAN_ANTRIAN_MP4_DETIK) {
                    $row = $this->video_packing_fcd->klaim_mp4();
                    if (!$row) {
                        break;
                    }

                    $this->transcode_satu_mp4($row, $ringkas);
                }

                $this->video_packing_fcd->lepas_kunci_transcode_mp4();
            }
        }

        $ringkas['success'] = ($ringkas['remux_gagal'] + $ringkas['mp4_gagal']) === 0;

        // Cron ini jalan tiap menit; kalau tidak ada video yang disentuh, jangan
        // tulis ke file log -- sebelumnya 1.440 baris "OK: {semua 0}" per hari
        // memenuhi application/logs (~13 MB/bulan). Output ke layar tetap ada.
        $ada_kerja = ($ringkas['remux_ok'] + $ringkas['remux_gagal']
            + $ringkas['mp4_ok'] + $ringkas['mp4_gagal']) > 0;
        $ringkas['mode'] = $mode;
        $this->_log('cron_finalisasi_video', $ringkas, $ada_kerja);
    }

    /** Berapa lama menunggu kunci transcode MP4 yang sedang dipegang proses lain. */
    const TUNGGU_KUNCI_MP4_DETIK = 5;

    /**
     * Setelah lewat ini, pemegang kunci tidak mengambil antrian MP4 baru lagi
     * dan menyerahkannya ke putaran cron berikutnya. 50 menit + transcode
     * terakhir maks. 60 menit masih di bawah batas 2 jam task terjadwal.
     */
    const ANGGARAN_ANTRIAN_MP4_DETIK = 50 * 60;

    /**
     * Transcode satu baris yang sudah diklaim (mp4_status = PROSES) dan catat
     * hasilnya. Sumber yang hilang tidak akan sembuh dengan diulang; kegagalan
     * lain dikembalikan ke antrian sampai tiga percobaan.
     */
    private function transcode_satu_mp4($row, array &$ringkas)
    {
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
            return;
        }

        $menyerah = !is_file($sumber) || (int) $row->mp4_percobaan >= 3;

        $this->video_packing_fcd->update_by_id($row->id_videopacking, [
            'mp4_status' => $menyerah ? 'GAGAL' : 'ANTRI',
            'mp4_pesan'  => substr($hasil['pesan'], 0, 255),
        ]);
        $ringkas['mp4_gagal']++;
    }

    /**
     * Cetak ringkasan tugas cron ke layar dan (opsional) ke file log.
     * Level 'error' dipakai karena log_threshold = 1 hanya meloloskan level itu.
     * $tulis_file = FALSE untuk tugas yang berjalan sangat sering tanpa hasil.
     */
    private function _log($task, $result, $tulis_file = TRUE)
    {
        $status = (isset($result['success']) && $result['success']) ? 'OK' : 'FAIL';
        $detail = is_array($result) ? json_encode($result) : $result;
        $log_line = date('Y-m-d H:i:s') . " [{$task}] {$status}: {$detail}";

        echo $log_line . "\n";
        if ($tulis_file || $status === 'FAIL') {
            log_message('error', $log_line);
        }
    }
}
