<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once(APPPATH.'config/secrets_load.php');

$config['wa_api_url']      = iresis_secret('wa_api_url', 'http://localhost:3000');
$config['wa_api_token']    = iresis_secret('wa_api_token');
$config['wa_target_group'] = iresis_secret('wa_target_group'); // Grup TIM OPERASIONAL
