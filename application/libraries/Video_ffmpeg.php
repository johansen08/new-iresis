<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pembungkus ffmpeg/ffprobe untuk rekaman video packing.
 *
 * Dua pekerjaan yang dilayani:
 *
 *  1. remux_webm(): WebM keluaran MediaRecorder tidak punya durasi dan cues,
 *     jadi tag <video> di halaman CS tidak tahu panjang videonya dan lompat ke
 *     tengah rekaman satu jam bisa gagal. Menulis ulang kontainernya dengan
 *     `-c copy` (tanpa encode ulang, hitungan detik) sudah cukup memperbaikinya.
 *  2. ke_mp4(): WebM tidak bisa diputar di iPhone dan tidak diterima WhatsApp,
 *     sedangkan CS kadang harus mengirim bukti packing ke pelanggan. Konversi
 *     ke H.264 memakan ~15 detik per menit video, jadi hanya dikerjakan atas
 *     permintaan (lihat Cs::minta_video_mp4 dan Cron::finalisasi_video).
 *
 * Lokasi binary dibaca dari kunci `ffmpeg_path` di secrets.php; kalau tidak
 * ada, dipakai `ffmpeg` dari PATH. ffprobe diasumsikan ada di folder yang sama.
 * Kalau ffmpeg tidak terpasang, semua method mengembalikan gagal dengan pesan
 * jelas -- rekaman tetap tersimpan dan bisa diputar, hanya tidak difinalisasi.
 */
class Video_ffmpeg
{
    /** Batas waktu satu proses ffmpeg; transcode video satu jam bisa 20 menit. */
    const BATAS_DETIK_TRANSCODE = 3600;
    const BATAS_DETIK_REMUX     = 600;

    private $ffmpeg;
    private $ffprobe;
    private $tersedia = null;

    public function __construct()
    {
        if (!function_exists('iresis_secret')) {
            require_once APPPATH . 'config/secrets_load.php';
        }

        $path = trim((string) iresis_secret('ffmpeg_path', 'ffmpeg'));
        $this->ffmpeg  = $path === '' ? 'ffmpeg' : $path;
        $this->ffprobe = $this->tebak_ffprobe($this->ffmpeg);
    }

    /** Path ffprobe di folder yang sama dengan ffmpeg (ffmpeg.exe -> ffprobe.exe). */
    private function tebak_ffprobe($ffmpeg)
    {
        return preg_replace('/ffmpeg(\.exe)?$/i', 'ffprobe$1', $ffmpeg);
    }

    /** Apakah ffmpeg bisa dijalankan dari sini. Hasilnya di-cache per request. */
    public function tersedia()
    {
        if ($this->tersedia === null) {
            $keluaran = '';
            $this->tersedia = $this->jalankan([$this->ffmpeg, '-version'], $keluaran, 30) === 0;
        }

        return $this->tersedia;
    }

    public function path_ffmpeg()
    {
        return $this->ffmpeg;
    }

    /**
     * Durasi berkas menurut ffprobe, dalam detik (float). 0 kalau tidak
     * terbaca -- termasuk WebM MediaRecorder yang belum di-remux.
     */
    public function durasi_detik($path)
    {
        $keluaran = '';
        $kode = $this->jalankan([
            $this->ffprobe, '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'csv=p=0',
            $path,
        ], $keluaran, 60);

        if ($kode !== 0) {
            return 0.0;
        }

        $nilai = trim($keluaran);

        return is_numeric($nilai) ? (float) $nilai : 0.0;
    }

    /**
     * Tulis ulang kontainer WebM di tempat supaya punya durasi dan cues.
     *
     * Hasil ditulis ke berkas sementara dulu, diperiksa, baru menggantikan yang
     * asli -- kalau ffmpeg gagal di tengah, rekaman aslinya tidak tersentuh.
     * Penggantian bisa gagal kalau berkasnya sedang dialirkan ke browser CS
     * (Windows mengunci berkas yang sedang dibaca); itu dikembalikan sebagai
     * gagal biasa supaya pemanggil mencoba lagi nanti.
     *
     * @return array ['sukses' => bool, 'durasi' => float, 'pesan' => string]
     */
    public function remux_webm($path)
    {
        if (!is_file($path)) {
            return $this->gagal('Berkas tidak ditemukan: ' . $path);
        }

        $sementara = $path . '.remux.tmp';
        $keluaran  = '';
        $kode = $this->jalankan([
            $this->ffmpeg, '-v', 'error', '-y',
            '-i', $path,
            '-c', 'copy',
            '-f', 'webm',
            $sementara,
        ], $keluaran, self::BATAS_DETIK_REMUX);

        if ($kode !== 0 || !is_file($sementara) || filesize($sementara) === 0) {
            @unlink($sementara);
            return $this->gagal('ffmpeg remux gagal (kode ' . $kode . '): ' . trim($keluaran));
        }

        $durasi = $this->durasi_detik($sementara);
        if ($durasi <= 0) {
            @unlink($sementara);
            return $this->gagal('Hasil remux tidak punya durasi; berkas asli dibiarkan.');
        }

        if (!@rename($sementara, $path)) {
            @unlink($sementara);
            return $this->gagal('Tidak bisa mengganti berkas asli (mungkin sedang diputar).');
        }

        return ['sukses' => TRUE, 'durasi' => $durasi, 'pesan' => ''];
    }

    /**
     * Transcode ke MP4 H.264 (yuv420p, faststart) supaya bisa diputar di
     * iPhone/WhatsApp. Audio memang tidak ada di rekaman packing.
     *
     * Preset veryfast + CRF 23: ukurannya kurang lebih sama dengan sumber
     * VP8-nya, kualitas visual setara, dan waktu prosesnya ~15 detik per menit
     * video di CPU biasa. Preset lebih lambat cuma menghemat ukuran sedikit.
     *
     * @return array ['sukses' => bool, 'ukuran' => int, 'pesan' => string]
     */
    public function ke_mp4($sumber, $tujuan)
    {
        if (!is_file($sumber)) {
            return $this->gagal('Berkas sumber tidak ditemukan: ' . $sumber);
        }

        $folder = dirname($tujuan);
        if (!is_dir($folder) && !@mkdir($folder, 0777, TRUE)) {
            return $this->gagal('Folder MP4 tidak bisa dibuat: ' . $folder);
        }

        $sementara = $tujuan . '.tmp';
        $keluaran  = '';
        $kode = $this->jalankan([
            $this->ffmpeg, '-v', 'error', '-y',
            '-i', $sumber,
            '-an',
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '23',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            '-f', 'mp4',
            $sementara,
        ], $keluaran, self::BATAS_DETIK_TRANSCODE);

        if ($kode !== 0 || !is_file($sementara) || filesize($sementara) === 0) {
            @unlink($sementara);
            return $this->gagal('ffmpeg transcode gagal (kode ' . $kode . '): ' . trim($keluaran));
        }

        if (!@rename($sementara, $tujuan)) {
            @unlink($sementara);
            return $this->gagal('Tidak bisa menaruh hasil MP4 di ' . $tujuan);
        }

        return ['sukses' => TRUE, 'ukuran' => (int) filesize($tujuan), 'pesan' => ''];
    }

    private function gagal($pesan)
    {
        return ['sukses' => FALSE, 'durasi' => 0.0, 'ukuran' => 0, 'pesan' => $pesan];
    }

    /**
     * Jalankan satu proses dan tunggu sampai selesai.
     *
     * Perintah dioper sebagai array (PHP >= 7.4) supaya tidak lewat shell:
     * path berkas berisi spasi atau karakter aneh tidak perlu di-escape, dan
     * nama berkas tidak pernah bisa disisipi perintah lain.
     *
     * @param  string $keluaran Diisi gabungan stdout+stderr (ffmpeg menulis
     *                          pesan error ke stderr).
     * @return int    Kode keluar proses; -1 kalau tidak bisa dijalankan,
     *                -2 kalau melewati batas waktu.
     */
    private function jalankan(array $perintah, &$keluaran, $batas_detik)
    {
        $keluaran = '';
        $pipa     = [];
        $spek     = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proses = @proc_open($perintah, $spek, $pipa);
        if (!is_resource($proses)) {
            $keluaran = 'Tidak bisa menjalankan ' . $perintah[0];
            return -1;
        }

        fclose($pipa[0]);
        stream_set_blocking($pipa[1], FALSE);
        stream_set_blocking($pipa[2], FALSE);

        $mulai = time();
        $kode  = -1;

        while (TRUE) {
            $keluaran .= (string) stream_get_contents($pipa[1]);
            $keluaran .= (string) stream_get_contents($pipa[2]);

            $status = proc_get_status($proses);
            if (!$status['running']) {
                $kode = (int) $status['exitcode'];
                break;
            }

            if ((time() - $mulai) > $batas_detik) {
                proc_terminate($proses);
                $keluaran .= "\nMelewati batas waktu " . $batas_detik . ' detik.';
                $kode = -2;
                break;
            }

            usleep(200000);
        }

        // Sisa keluaran yang keluar tepat sebelum proses berhenti.
        $keluaran .= (string) stream_get_contents($pipa[1]);
        $keluaran .= (string) stream_get_contents($pipa[2]);

        fclose($pipa[1]);
        fclose($pipa[2]);
        proc_close($proses);

        return $kode;
    }
}
