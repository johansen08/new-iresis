<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'config/secrets_load.php');

$config['pusher_app_id']     = iresis_secret('pusher_app_id');
$config['pusher_app_key']    = iresis_secret('pusher_app_key');
$config['pusher_app_secret'] = iresis_secret('pusher_app_secret');
$config['pusher_cluster']    = iresis_secret('pusher_cluster', 'ap1');

// FALSE di folder dev: Pusher_lib tidak mengirim apa pun, supaya uji coba tidak
// memunculkan notifikasi palsu di browser pengguna produksi (app Pusher-nya sama).
$config['pusher_aktif']      = (bool) iresis_secret('pusher_aktif', TRUE);
