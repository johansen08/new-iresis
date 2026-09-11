<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends CI_Model {

    // Mengambil jumlah notifikasi yang belum dibaca (is_read = 0)
    public function get_unread_count($category = 'TIM PURCHASING')
    {
        if ($category !== 'ADMIN') {
            if ($category === 'TIM RETUR') {
                $this->db->where_in('category', ['TIM RETUR', 'TIM RESTOCK']);
            } else {
                $this->db->where('category', $category);
            }
        }
        $this->db->group_start(); 
            $this->db->where('is_read', 0);
            $this->db->or_where('is_read IS NULL');
        $this->db->group_end();
        return $this->db->count_all_results('notifications');
    }

    // Mengambil list notifikasi untuk ditampilkan di dropdown (misal 5 terbaru)
    public function get_notifications($category = 'TIM PURCHASING', $limit = 5)
    {
        if ($category !== 'ADMIN') {
            if ($category === 'TIM RETUR') {
                $this->db->where_in('category', ['TIM RETUR', 'TIM RESTOCK']);
            } else {
                $this->db->where('category', $category);
            }
        }
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get('notifications')->result_array();
    }

    // Mengubah status notifikasi menjadi sudah dibaca saat diklik
    public function mark_as_read($id)
    {
        $this->db->where('id', $id);
        $this->db->update('notifications', ['is_read' => 1]);
    }

    /**
     * Menandai semua notifikasi dalam kategori tertentu sebagai terbaca
     */
    public function mark_all_as_read($category)
    {
        if ($category !== 'ADMIN') {
            if ($category === 'TIM RETUR') {
                $this->db->where_in('category', ['TIM RETUR', 'TIM RESTOCK']);
            } else {
                $this->db->where('category', $category);
            }
        }
        
        $this->db->group_start();
            $this->db->where('is_read', 0);
            $this->db->or_where('is_read IS NULL');
        $this->db->group_end();
        
        $this->db->update('notifications', ['is_read' => 1]);
        return $this->db->affected_rows();
    }

    /**
     * Kirim notifikasi ke Database dan Real-time Pusher
     * 
     * @param string $message Pesan notifikasi
     * @param string $category Kategori (TIM PURCHASING, TIM ACCOUNTING, dll)
     * @param string $title Judul untuk Pusher alert
     * @return bool status pengiriman pusher
     */
    public function send($message, $category = 'GENERAL', $title = 'Notifikasi Baru')
    {
        // 1. Simpan ke Database
        $data = [
            'message'    => $message,
            'category'   => $category,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('notifications', $data);

        // 2. Trigger Pusher
        try {
            // Load library explicitly in case it's not loaded in the controller
            $CI =& get_instance();
            if (!isset($CI->pusher_lib)) {
                $CI->load->library('pusher_lib');
            }
            
            $notif_data = [
                'title'   => $title,
                'message' => $message,
                'category' => $category
            ];

            // Mapping Category ke Role Channel
            // Hanya tim terkait yang menerima notifikasi (Webmaster/Admin dikecualikan)
            $channels = [];

            switch (strtoupper($category)) {
                case 'TIM PURCHASING':
                    $channels[] = 'notif-role-7';
                    break;
                case 'TIM ACCOUNTING':
                    $channels[] = 'notif-role-8';
                    break;
                case 'TIM INBOUND':
                    $channels[] = 'notif-role-9';
                    break;
                case 'TIM FINANCE':
                    $channels[] = 'notif-role-10';
                    break;
                case 'TIM CS':
                    $channels[] = 'notif-role-5';
                    break;
                case 'TIM RETUR':
                case 'TIM RESTOCK':
                    // Role 6 digunakan oleh Tim Retur/Restock di sistem ini
                    $channels[] = 'notif-role-6';
                    break;
            }

            // Hapus duplikasi jika ada
            $channels = array_unique($channels);

            if (empty($channels)) {
                return false;
            }

            return $CI->pusher_lib->trigger($channels, 'notif-event', $notif_data);
        } catch (Exception $e) {
            log_message('error', 'Notification/send Error: ' . $e->getMessage());
            return false;
        }
    }
}
