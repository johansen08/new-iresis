<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pusher_lib {

    private $pusher;

    public function __construct()
    {
        // Panggil instance super-object CodeIgniter
        $CI =& get_instance();
        
        // Load file konfigurasi pusher.php yang kita buat di Langkah 1
        $CI->load->config('pusher', TRUE);

        // Ambil data dari config
        $options = array(
            'cluster' => $CI->config->item('pusher_cluster', 'pusher'),
            'useTLS'  => true
        );

        // Inisialisasi Pusher
        $this->pusher = new Pusher\Pusher(
            $CI->config->item('pusher_app_key', 'pusher'),
            $CI->config->item('pusher_app_secret', 'pusher'),
            $CI->config->item('pusher_app_id', 'pusher'),
            $options
        );
    }

    // Buat fungsi wrapper agar mudah dipanggil di controller
    public function trigger($channels, $event, $data)
    {
        return $this->pusher->trigger($channels, $event, $data);
    }
}
