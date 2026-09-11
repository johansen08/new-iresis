<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packer_monitoring_fcd extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sku_special_fcd');
    }

    /**
     * Get or create session for today
     */
    public function get_session($user_id, $tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');
        
        $session = $this->db->get_where('tblpacker_sessions', [
            'id_user' => $user_id,
            'tanggal' => $tanggal
        ])->row();

        if (!$session) {
            $this->db->insert('tblpacker_sessions', [
                'id_user' => $user_id,
                'tanggal' => $tanggal,
                'waktu_masuk' => date('Y-m-d H:i:s')
            ]);
            return $this->get_session($user_id, $tanggal);
        }

        return $session;
    }

    /**
     * Update session status (masuk, pulang, istirahat)
     */
    public function update_session($user_id, $action)
    {
        $tanggal = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $session = $this->get_session($user_id, $tanggal);

        $update = [];
        switch ($action) {
            case 'masuk':
                if (!$session->waktu_masuk) $update['waktu_masuk'] = $now;
                break;
            case 'pulang':
                $update['waktu_pulang'] = $now;
                break;
            case 'istirahat_mulai':
                $update['waktu_istirahat_mulai'] = $now;
                $update['waktu_istirahat_selesai'] = null;
                break;
            case 'istirahat_selesai':
                if ($session->waktu_istirahat_mulai) {
                    $start = strtotime($session->waktu_istirahat_mulai);
                    $end = strtotime($now);
                    $diff = $end - $start;
                    $update['waktu_istirahat_selesai'] = $now;
                    $update['total_istirahat'] = $session->total_istirahat + $diff;
                }
                break;
        }

        if (!empty($update)) {
            $this->db->where('id', $session->id);
            $this->db->update('tblpacker_sessions', $update);
        }

        return true;
    }

    /**
     * Log performance for a scan
     */
    public function log_performance($user_id, $id_resi, $noresi)
    {
        $now = date('Y-m-d H:i:s');
        $session = $this->get_session($user_id);

        // 1. Determine SOP Status & Target
        $is_special = $this->sku_special_fcd->is_special_receipt($noresi);
        
        // Get total qty
        $this->db->select_sum('jumlah');
        $qty_row = $this->db->get_where('tbldetailprintresi', ['id_resi' => $id_resi])->row();
        $total_qty = $qty_row->jumlah ?? 0;

        $status = 'NORMAL';
        $target = 60; // default 60s (1 minute per resi)

        if ($is_special) {
            $status = 'SPECIAL';
            $target = 30; // 30 seconds for special (2 resi per minute)
        } elseif ($total_qty > 10) {
            $status = 'BANYAK';
            $target = 30 * 60; // 30 minutes for large orders
        } elseif ($total_qty > 3) {
            $status = 'MEDIUM';
            $target = 120; // 2 minutes
        }

        // 2. Calculate Actual Duration
        $last_scan = $this->db
            ->where('id_user', $user_id)
            ->where('DATE(tanggal_packing)', date('Y-m-d'))
            ->order_by('tanggal_packing', 'DESC')
            ->limit(1)
            ->get('tblpacker_performance_logs')
            ->row();

        $start_time = null;
        if ($last_scan) {
            $start_time = strtotime($last_scan->tanggal_packing);
        } else {
            $start_time = strtotime($session->waktu_masuk);
        }

        // Adjust for break if it happened after the last scan
        if ($session->waktu_istirahat_selesai && strtotime($session->waktu_istirahat_selesai) > $start_time) {
            $start_time = strtotime($session->waktu_istirahat_selesai);
        }

        $duration = time() - $start_time;
        if ($duration < 0) $duration = 0;

        $is_slow = ($duration > $target) ? 1 : 0;

        // 3. Save Log
        $this->db->insert('tblpacker_performance_logs', [
            'id_user'         => $user_id,
            'id_resi'         => $id_resi,
            'tanggal_packing' => $now,
            'durasi_aktual'   => $duration,
            'durasi_target'   => $target,
            'status_performa' => $status,
            'is_slow'         => $is_slow
        ]);

        // Count how many slow scans today (not deleted)
        $slow_count = $this->db
            ->where('id_user', $user_id)
            ->where('DATE(tanggal_packing)', date('Y-m-d'))
            ->where('is_slow', 1)
            ->where('is_deleted', 0)
            ->count_all_results('tblpacker_performance_logs');

        return [
            'is_slow'    => $is_slow,
            'slow_count' => $slow_count,
            'duration'   => $duration,
            'target'     => $target,
        ];
    }

    /**
     * Get current slow status for a user today
     */
    public function get_slow_status($user_id)
    {
        $slow_count = $this->db
            ->where('id_user', $user_id)
            ->where('DATE(tanggal_packing)', date('Y-m-d'))
            ->where('is_slow', 1)
            ->where('is_deleted', 0)
            ->count_all_results('tblpacker_performance_logs');

        $last_log = $this->db
            ->where('id_user', $user_id)
            ->where('DATE(tanggal_packing)', date('Y-m-d'))
            ->order_by('tanggal_packing', 'DESC')
            ->limit(1)
            ->get('tblpacker_performance_logs')
            ->row();

        $last_is_slow = $last_log ? (int)$last_log->is_slow : 0;

        return [
            'slow_count'  => $slow_count,
            'last_is_slow' => $last_is_slow,
        ];
    }

    /**
     * Get active monitoring data
     */
    public function get_monitoring_data($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        // Escaping the date manually for the subqueries
        $escaped_tanggal = $this->db->escape($tanggal);

        $this->db->select("
            u.id_user,
            u.name as nama_packer,
            s.waktu_masuk,
            s.waktu_pulang,
            s.waktu_istirahat_mulai,
            s.waktu_istirahat_selesai,
            s.total_istirahat,
            (SELECT COUNT(*) FROM tblpacker_performance_logs WHERE id_user = u.id_user AND DATE(tanggal_packing) = $escaped_tanggal) as total_scan,
            (SELECT COUNT(*) FROM tblpacker_performance_logs WHERE id_user = u.id_user AND DATE(tanggal_packing) = $escaped_tanggal AND is_slow = 1 AND is_deleted = 0) as total_slow
        ", FALSE);
        $this->db->from('tbluser u');
        $this->db->join('tblpacker_sessions s', "s.id_user = u.id_user AND s.tanggal = $escaped_tanggal", 'inner');
        $this->db->where('u.isactive', 1);
        
        return $this->db->get()->result();
    }

    public function get_detailed_logs($user_id, $tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $this->db->select("l.*, pr.noresi, (SELECT GROUP_CONCAT(CONCAT(sku, ' (', jumlah, ')') SEPARATOR ', ') FROM tbldetailprintresi WHERE id_resi = l.id_resi) as sku_qty");
        $this->db->from('tblpacker_performance_logs l');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = l.id_resi');
        $this->db->where('l.id_user', $user_id);
        $this->db->where('DATE(l.tanggal_packing)', $tanggal);
        $this->db->order_by('l.tanggal_packing', 'DESC');

        return $this->db->get()->result();
    }

    public function add_comment($log_id, $comment)
    {
        $this->db->where('id', $log_id);
        return $this->db->update('tblpacker_performance_logs', ['komentar' => $comment]);
    }

    public function delete_log($log_id)
    {
        $this->db->where('id', $log_id);
        return $this->db->update('tblpacker_performance_logs', ['is_deleted' => 1]);
    }

    public function get_report_data($filters = [])
    {
        $this->db->select("l.*, pr.noresi, u.name as nama_packer, (SELECT GROUP_CONCAT(CONCAT(sku, ' (', jumlah, ')') SEPARATOR ', ') FROM tbldetailprintresi WHERE id_resi = l.id_resi) as sku_qty");
        $this->db->from('tblpacker_performance_logs l');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = l.id_resi');
        $this->db->join('tbluser u', 'u.id_user = l.id_user');
        
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(l.tanggal_packing) >=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(l.tanggal_packing) <=', $filters['end_date']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('l.id_user', $filters['user_id']);
        }
        if (isset($filters['is_slow'])) {
            $this->db->where('l.is_slow', $filters['is_slow']);
        }

        $this->db->order_by('l.tanggal_packing', 'DESC');
        return $this->db->get()->result();
    }
}
