<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Wa_gateway
{
    private $api_url;
    private $api_token;
    private $target_group;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->config('whatsapp', TRUE);

        $this->api_url      = $CI->config->item('wa_api_url', 'whatsapp');
        $this->api_token    = $CI->config->item('wa_api_token', 'whatsapp');
        $this->target_group = $CI->config->item('wa_target_group', 'whatsapp');
    }

    public function send($to, $message)
    {
        return $this->_curl_post('/send', [
            'to'      => $to,
            'message' => $message,
        ]);
    }

    public function send_to_group($message)
    {
        if (empty($this->target_group)) {
            log_message('error', 'WA Gateway: wa_target_group belum diatur di config.');
            return ['error' => true, 'message' => 'Group ID belum dikonfigurasi.'];
        }
        return $this->send($this->target_group, $message);
    }

    public function get_status()
    {
        return $this->_curl_get('/status');
    }

    private function _curl_post($endpoint, $data)
    {
        $ch = curl_init($this->api_url . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_token,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', 'WA Gateway cURL error: ' . $err);
            return ['error' => true, 'message' => $err];
        }

        return json_decode($response, true);
    }

    private function _curl_get($endpoint)
    {
        $ch = curl_init($this->api_url . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', 'WA Gateway cURL error: ' . $err);
            return ['error' => true, 'message' => $err];
        }

        return json_decode($response, true);
    }
}
