<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'config/secrets_load.php');

$config['pusher_app_id']     = iresis_secret('pusher_app_id');
$config['pusher_app_key']    = iresis_secret('pusher_app_key');
$config['pusher_app_secret'] = iresis_secret('pusher_app_secret');
$config['pusher_cluster']    = iresis_secret('pusher_cluster', 'ap1');
