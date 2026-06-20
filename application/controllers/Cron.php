<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        // Allow CLI and token-authenticated HTTP (for chatbot callback)
        if (!is_cli()) {
            $token = $this->input->get('token');
            $this->load->config('whatsapp', TRUE);
            $expected = $this->config->item('wa_api_token', 'whatsapp');

            if ($token !== $expected) {
                show_error('Access denied.', 403);
                exit;
            }
        }

        $this->load->database();
        $this->load->model('laporan_fcd');
        $this->load->model('rts_fcd');
        $this->load->library('wa_gateway');
    }

    public function sisa_resi()
    {
        $msg = $this->laporan_fcd->format_wa_sisa_resi();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_sisa_resi', $result);
    }

    public function paket_keluar()
    {
        $msg = $this->laporan_fcd->format_wa_paket_keluar();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_paket_keluar', $result);
    }

    public function rts_check()
    {
        $hour = (int) date('H');
        $trip = ($hour < 18) ? 1 : 2;

        $data = $this->rts_fcd->check_rts($trip)->result();
        $jumlah = count($data);

        $resi_list = array_map(function ($r) {
            return $r->noresi;
        }, $data);

        $this->rts_fcd->log_rts_cycle($trip, $jumlah, $resi_list);

        if ($jumlah > 0) {
            $ids = array_map(function ($r) {
                return $r->id_printresi;
            }, $data);
            $this->rts_fcd->mark_checked($ids);
        }

        $msg = $this->rts_fcd->format_wa_rts($trip);

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_rts_check', $result);
    }

    public function rekap_target_wa()
    {
        $msg = $this->laporan_fcd->format_wa_rekap_target();

        if ($this->_is_text_output()) {
            echo $msg;
            return;
        }

        $result = $this->wa_gateway->send_to_group($msg);
        $this->_log('cron_rekap_target', $result);
    }

    private function _is_text_output()
    {
        return $this->input->get('output') === 'text';
    }

    private function _log($task, $result)
    {
        $status = (isset($result['success']) && $result['success']) ? 'OK' : 'FAIL';
        $detail = is_array($result) ? json_encode($result) : $result;
        $log_line = date('Y-m-d H:i:s') . " [{$task}] {$status}: {$detail}";

        echo $log_line . "\n";
        log_message('info', $log_line);
    }
}
