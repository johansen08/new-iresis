<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pusher_lib {

    /** Setelah satu kegagalan kirim, lewati pengiriman selama sekian detik (pemutus sirkuit). */
    const JEDA_GAGAL_DETIK = 120;

    private $pusher;
    private $aktif = TRUE;
    private $berkas_gagal;

    public function __construct()
    {
        // Panggil instance super-object CodeIgniter
        $CI =& get_instance();

        // Load file konfigurasi pusher.php yang kita buat di Langkah 1
        $CI->load->config('pusher', TRUE);

        $this->berkas_gagal = APPPATH . 'cache/pusher_gagal.txt';

        // Dimatikan di folder dev lewat `pusher_aktif` di secrets.php (lihat config/pusher.php)
        $this->aktif = $CI->config->item('pusher_aktif', 'pusher') !== FALSE;
        if ( ! $this->aktif) {
            return;
        }

        // Timeout pendek: aplikasi dijalankan penuh di LAN, internet bisa tidak ada.
        // Tanpa ini tiap aksi yang memicu notifikasi ikut menggantung selama timeout.
        $options = array(
            'cluster' => $CI->config->item('pusher_cluster', 'pusher'),
            'useTLS'  => true,
            'timeout' => 2
        );

        // Server kompatibel-Pusher lokal (mis. Soketi): isi pusher_host di secrets.php.
        $host = $CI->config->item('pusher_host', 'pusher');
        if ($host) {
            $options['host']   = $host;
            $options['port']   = (int) ($CI->config->item('pusher_port', 'pusher') ?: 6001);
            $options['scheme'] = $CI->config->item('pusher_scheme', 'pusher') ?: 'http';
            $options['useTLS'] = ($options['scheme'] === 'https');
        }

        // Inisialisasi Pusher
        $this->pusher = new Pusher\Pusher(
            $CI->config->item('pusher_app_key', 'pusher'),
            $CI->config->item('pusher_app_secret', 'pusher'),
            $CI->config->item('pusher_app_id', 'pusher'),
            $options
        );
    }

    /** TRUE bila kirim terakhir gagal belum lama ini (jaringan putus) -> jangan coba lagi dulu. */
    private function sedang_gagal()
    {
        $t = @filemtime($this->berkas_gagal);
        return $t !== FALSE && (time() - $t) < self::JEDA_GAGAL_DETIK;
    }

    // Buat fungsi wrapper agar mudah dipanggil di controller
    public function trigger($channels, $event, $data)
    {
        if ( ! $this->aktif) {
            log_message('debug', 'Pusher nonaktif (pusher_aktif = FALSE), event '.$event.' tidak dikirim.');
            return new stdClass();
        }

        if ($this->sedang_gagal()) {
            log_message('debug', 'Pusher dilewati (gagal kirim < '.self::JEDA_GAGAL_DETIK.' dtk lalu), event '.$event.'.');
            return FALSE;
        }

        try {
            $hasil = $this->pusher->trigger($channels, $event, $data);
        } catch (Exception $e) {
            $hasil = FALSE;
            log_message('error', 'Pusher trigger gagal: ' . $e->getMessage());
        }

        if ($hasil === FALSE) {
            @file_put_contents($this->berkas_gagal, date('c'));
        } elseif (is_file($this->berkas_gagal)) {
            @unlink($this->berkas_gagal);
        }

        return $hasil;
    }
}
