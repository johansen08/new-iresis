<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'config/secrets_load.php');

$config['pusher_app_id']     = iresis_secret('pusher_app_id');
$config['pusher_app_key']    = iresis_secret('pusher_app_key');
$config['pusher_app_secret'] = iresis_secret('pusher_app_secret');
$config['pusher_cluster']    = iresis_secret('pusher_cluster', 'ap1');

// Opsional: server kompatibel-Pusher di LAN (mis. Soketi). Kosong = Pusher cloud.
$config['pusher_host']       = iresis_secret('pusher_host', '');
$config['pusher_port']       = iresis_secret('pusher_port', 6001);
$config['pusher_scheme']     = iresis_secret('pusher_scheme', 'http');

// FALSE di folder dev: Pusher_lib tidak mengirim apa pun, supaya uji coba tidak
// memunculkan notifikasi palsu di browser pengguna produksi (app Pusher-nya sama).
$config['pusher_aktif']      = (bool) iresis_secret('pusher_aktif', TRUE);
