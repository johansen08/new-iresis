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

            // Pembaca streaming (~1-3 dtk per 20rb baris), fallback otomatis ke
            // PhpSpreadsheet untuk .xls -- hasilnya identik dengan toArray() lama.
            $file = $_FILES['receiptFile']['tmp_name'];
            $this->load->library('xlsx_cepat');
            $dataRaw = $this->xlsx_cepat->baca_dengan_fallback($file, 'W');

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
     * 2026-07-04. Dipanggil oleh scripts/arsip/fix_resi_detail_20260704.py.
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
    /**
     * Sinkron foto produk ke folder lokal (foto_produk_dir, bawaan C:/foto-produk/)
     * dan catat nama berkasnya di tblsku.foto_lokal -- lihat helper foto_sku.
     *
     * tblsku.link_foto menunjuk ke object storage Jubelio, jadi foto di layar
     * packer/retur/QC hilang saat internet putus. Browser tetap mencoba URL asli
     * dulu; salinan lokal hanya cadangan. Method ini, per URL unik:
     *   - berkas sudah ada  -> pastikan foto_lokal semua SKU ber-URL itu = nama berkas
     *   - belum ada         -> unduh (curl_multi), tulis .part lalu rename, set foto_lokal
     *   - 404/410           -> tandai <nama>.tidakada, kosongkan foto_lokal
     *   - gagal lain        -> dicoba lagi di putaran berikutnya
     * Idempoten dan aman dijalankan berulang; nama berkas = md5(url).ext, jadi
     * SKU yang fotonya diganti otomatis mendapat berkas baru. Satu putaran
     * dibatasi $maks_detik; kunci berkas mencegah dua putaran bersamaan.
     *
     *   php index.php cron sinkron_foto_sku            (maks. 25 menit, 6 paralel)
     *   php index.php cron sinkron_foto_sku 3600 8     (maks. 1 jam, 8 paralel)
     */
    public function sinkron_foto_sku($maks_detik = null, $paralel = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $maks_detik = (int) ($maks_detik !== null ? $maks_detik : $this->input->get('maks_detik'));
        $paralel    = (int) ($paralel !== null ? $paralel : $this->input->get('paralel'));
        if ($maks_detik <= 0) {
            $maks_detik = 1500;
        }
        $paralel = max(1, min(16, $paralel ?: 6));

        $dir = foto_sku_dir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, TRUE)) {
            $this->_log('cron_sinkron_foto_sku', ['success' => FALSE, 'pesan' => "Folder $dir tidak bisa dibuat"]);
            return;
        }

        // Kunci: satu putaran pada satu waktu (task harian vs. jalan manual).
        $kunci = fopen(APPPATH . 'cache/sinkron_foto_sku.lock', 'c');
        if (!$kunci || !flock($kunci, LOCK_EX | LOCK_NB)) {
            $this->_log('cron_sinkron_foto_sku', ['success' => TRUE, 'pesan' => 'Putaran lain masih berjalan, lewati'], FALSE);
            return;
        }

        $mulai   = microtime(true);
        $ringkas = ['total_url' => 0, 'sudah_ada' => 0, 'tidak_ada' => 0, 'diunduh' => 0, 'gagal' => 0,
                    'db_diperbarui' => 0, 'sisa' => 0, 'byte' => 0, 'detik' => 0, 'habis_waktu' => FALSE,
                    'folder' => $dir, 'contoh_gagal' => []];

        // fl = gabungan nilai foto_lokal semua SKU yang memakai URL ini; kalau
        // semuanya sudah = nama berkas, GROUP_CONCAT DISTINCT menghasilkan tepat nama itu.
        $rows = $this->db->query(
            "SELECT link_foto, GROUP_CONCAT(DISTINCT COALESCE(foto_lokal, '')) AS fl
             FROM tblsku WHERE link_foto LIKE 'http%' GROUP BY link_foto"
        )->result();
        $antrian = [];
        foreach ($rows as $r) {
            $url  = trim($r->link_foto);
            $nama = foto_sku_nama_berkas($url);
            if ($nama === '') {
                continue;
            }
            $ringkas['total_url']++;
            if (is_file($dir . $nama)) {
                $ringkas['sudah_ada']++;
                if ($r->fl !== $nama) {
                    $ringkas['db_diperbarui'] += $this->catat_foto_lokal($r->link_foto, $nama);
                }
            } elseif (is_file($dir . $nama . '.tidakada')) {
                $ringkas['tidak_ada']++;
                if ($r->fl !== '') {
                    $ringkas['db_diperbarui'] += $this->catat_foto_lokal($r->link_foto, NULL);
                }
            } else {
                $antrian[$nama] = $r->link_foto;
            }
        }
        unset($rows);

        foreach (array_chunk($antrian, $paralel, TRUE) as $batch) {
            if (microtime(true) - $mulai > $maks_detik) {
                $ringkas['habis_waktu'] = TRUE;
                break;
            }

            $mh = curl_multi_init();
            $ch = [];
            foreach ($batch as $nama => $url) {
                $c = curl_init(trim($url));
                curl_setopt_array($c, [
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_FOLLOWLOCATION => TRUE,
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT        => 45,
                    CURLOPT_USERAGENT      => 'IRESIS-sinkron-foto-sku/1.0',
                ]);
                curl_multi_add_handle($mh, $c);
                $ch[$nama] = $c;
            }
            do {
                $status = curl_multi_exec($mh, $aktif);
                if ($aktif) {
                    curl_multi_select($mh, 1.0);
                }
            } while ($aktif && $status === CURLM_OK);

            foreach ($ch as $nama => $c) {
                $body = curl_multi_getcontent($c);
                $kode = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
                $tipe = (string) curl_getinfo($c, CURLINFO_CONTENT_TYPE);
                $err  = curl_error($c);
                curl_multi_remove_handle($mh, $c);
                curl_close($c);

                if ($kode === 200 && $body !== '' && $body !== FALSE
                    && (stripos($tipe, 'image/') === 0 || $this->terlihat_gambar($body))) {
                    // Tulis ke .part dulu, lalu rename, supaya pembaca tidak dapat berkas setengah.
                    if (file_put_contents($dir . $nama . '.part', $body) !== FALSE
                        && rename($dir . $nama . '.part', $dir . $nama)) {
                        $ringkas['diunduh']++;
                        $ringkas['byte'] += strlen($body);
                        $ringkas['db_diperbarui'] += $this->catat_foto_lokal($batch[$nama], $nama);
                        continue;
                    }
                    $err = 'gagal menulis berkas';
                } elseif ($kode === 404 || $kode === 410) {
                    @touch($dir . $nama . '.tidakada');
                    $ringkas['tidak_ada']++;
                    $ringkas['db_diperbarui'] += $this->catat_foto_lokal($batch[$nama], NULL);
                    continue;
                }

                $ringkas['gagal']++;
                if (count($ringkas['contoh_gagal']) < 5) {
                    $ringkas['contoh_gagal'][] = "HTTP $kode " . ($err ?: $tipe) . ' <- ' . $batch[$nama];
                }
            }
            curl_multi_close($mh);
        }

        $ringkas['sisa']    = max(0, $ringkas['total_url'] - $ringkas['sudah_ada'] - $ringkas['tidak_ada'] - $ringkas['diunduh']);
        $ringkas['detik']   = round(microtime(true) - $mulai, 1);
        $ringkas['success'] = TRUE;

        flock($kunci, LOCK_UN);
        fclose($kunci);

        $this->_log('cron_sinkron_foto_sku', $ringkas,
            $ringkas['diunduh'] > 0 || $ringkas['gagal'] > 0 || $ringkas['db_diperbarui'] > 0);
    }

    /**
     * Salin data prod ke database arsip (superset) — Task Scheduler
     * "IRESIS - Arsip harian" tiap 01.00 via scripts/arsip_harian_senyap.vbs.
     *   php index.php cron arsip_harian                → cek skema + sinkron + laporan
     *   php index.php cron arsip_harian cek_skema      → hanya samakan struktur
     *   php index.php cron arsip_harian sinkron 600    → sinkron maks 600 detik (muat awal bertahap)
     *   php index.php cron arsip_harian laporan        → hanya hitung resi lewat retensi
     * Tidak ada DELETE di prod pada tahap ini. Setelan: application/config/arsip.php;
     * penjelasan alur & tahapan: docs/ARSIP_DATA.md.
     */
    public function arsip_harian($tahap = 'semua', $maks_detik = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $tahap = $tahap ?: $this->input->get('tahap');
        $tahap = in_array($tahap, array('semua', 'cek_skema', 'sinkron', 'laporan')) ? $tahap : 'semua';
        $maks_detik = (int) ($maks_detik !== null ? $maks_detik : $this->input->get('maks_detik'));

        $this->load->model('Arsip_fcd');
        $hasil = $this->Arsip_fcd->jalankan($tahap, $maks_detik ?: null);

        // Tiap baris sudah membawa jam kejadiannya (dari model); tanggal ditambah di sini.
        foreach ($hasil['log'] as $baris) {
            echo date('Y-m-d') . " $baris\n";
        }

        $ringkas = array(
            'success' => $hasil['success'], 'tahap' => $tahap, 'durasi_detik' => $hasil['durasi_detik'],
            'tabel_selesai' => isset($hasil['sinkron']) ? count($hasil['sinkron']['selesai']) : 0,
            'tabel_gagal'   => isset($hasil['sinkron']) ? array_keys($hasil['sinkron']['gagal']) : array(),
            'skema_beda'    => isset($hasil['skema']) ? array_keys($hasil['skema']['beda']) : array(),
            'terpotong'     => !empty($hasil['sinkron']['terpotong']),
            'resi_lewat_retensi' => isset($hasil['purna_perkiraan']['resi_lewat_retensi']) ? $hasil['purna_perkiraan']['resi_lewat_retensi'] : null,
        );
        if (isset($hasil['pesan'])) {
            $ringkas['pesan'] = $hasil['pesan'];
        }
        $this->_log('cron_arsip_harian', $ringkas);
    }

    /**
     * Purna: pindahkan keluarga resi > retensi (config arsip `retensi_hari`)
     * dari prod ke arsip — satu-satunya tugas yang menjalankan DELETE di prod.
     *   php index.php cron arsip_purna                    → UJI: semua langkah kecuali DELETE, laporkan jumlah
     *   php index.php cron arsip_purna uji 300            → uji maks 300 detik
     *   php index.php cron arsip_purna jalankan 600 2000  → HAPUS sungguhan, maks 600 dtk / 2.000 resi
     *   php index.php cron arsip_purna jalankan           → HAPUS sungguhan sampai habis / batas waktu config
     * Ditolak gerbang keselamatan bila backup arsip atau sinkron terakhir > 36 jam,
     * atau skema prod-arsip beda. Rincian: docs/ARSIP_DATA.md §4c.
     */
    public function arsip_purna($mode = 'uji', $maks_detik = null, $maks_resi = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $mode       = $mode ?: $this->input->get('mode');
        $uji        = ($mode !== 'jalankan');
        $maks_detik = (int) ($maks_detik !== null ? $maks_detik : $this->input->get('maks_detik'));
        $maks_resi  = (int) ($maks_resi !== null ? $maks_resi : $this->input->get('maks_resi'));

        $this->load->model('Arsip_fcd');
        $hasil = $this->Arsip_fcd->purna($maks_detik ?: null, $maks_resi, $uji);

        foreach ($hasil['log'] as $baris) {
            echo date('Y-m-d') . " $baris\n";
        }
        $ringkas = array(
            'success' => $hasil['success'], 'mode' => $uji ? 'uji' : 'jalankan', 'durasi_detik' => $hasil['durasi_detik'],
            'resi' => $hasil['resi'], 'batch' => $hasil['batch'], 'baris' => array_filter($hasil['baris']),
            'khusus' => $hasil['khusus'],
        );
        if (!empty($hasil['pesan'])) {
            $ringkas['pesan'] = $hasil['pesan'];
        }
        $this->_log('cron_arsip_purna', $ringkas);
    }

    /**
     * Diagnostik: status satu resi terhadap arsip, dan tarik balik ke prod bila
     * hanya ada di arsip (perilaku sama persis dengan helper pastikan_resi_live()
     * yang dipanggil alur retur/CS).
     *   php index.php cron cek_resi_live JX1234567890
     */
    public function cek_resi_live($noresi = '')
    {
        $noresi = trim((string) ($noresi !== '' ? $noresi : $this->input->get('noresi')));
        if ($noresi === '') {
            echo "Pakai: php index.php cron cek_resi_live <noresi>\n";
            return;
        }
        $this->load->helper('arsip');
        $t0     = microtime(TRUE);
        $status = pastikan_resi_live($noresi);
        $ket    = array(
            'live'      => 'ada di prod (tidak perlu ditarik)',
            'ditarik'   => 'hanya ada di arsip -> keluarga resi DISALIN ke prod',
            'tidak_ada' => 'tidak ada di prod maupun arsip',
            'lewati'    => 'dilewati (noresi kosong / Mode Arsip / arsip tidak diatur)',
            'gagal'     => 'GAGAL -- lihat iresis_arsip._arsip_log tahap tarik_balik',
        );
        printf("%s: %s (%s) %.1f ms\n", $noresi, $status, isset($ket[$status]) ? $ket[$status] : '?', (microtime(TRUE) - $t0) * 1000);
    }

    /**
     * Set tblsku.foto_lokal untuk semua SKU yang memakai URL ini (NULL = tidak ada
     * salinan). Hanya baris yang nilainya berbeda yang disentuh; balik jumlahnya.
     */
    private function catat_foto_lokal($link_foto, $nama)
    {
        $this->db->where('link_foto', $link_foto);
        if ($nama === NULL) {
            $this->db->where('foto_lokal IS NOT NULL', NULL, FALSE);
        } else {
            $this->db->group_start()->where('foto_lokal IS NULL', NULL, FALSE)
                     ->or_where('foto_lokal !=', $nama)->group_end();
        }
        $this->db->update('tblsku', ['foto_lokal' => $nama]);
        return (int) $this->db->affected_rows();
    }

    /** Deteksi gambar dari magic bytes bila server tidak mengirim Content-Type image/*. */
    private function terlihat_gambar($body)
    {
        $awal = substr($body, 0, 12);
        return strpos($awal, "\xFF\xD8\xFF") === 0                                  // JPEG
            || strpos($awal, "\x89PNG") === 0                                      // PNG
            || strpos($awal, 'GIF8') === 0                                         // GIF
            || (strpos($awal, 'RIFF') === 0 && substr($awal, 8, 4) === 'WEBP');    // WebP
    }

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
