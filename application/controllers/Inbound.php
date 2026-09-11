<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inbound extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Surat_jalan_fcd');
        $this->load->model('Notification');
        $this->load->library('pusher_lib');
    }

    // Surat Jalan (KURANGAN PICKER)

    public function laporan_surat_jalan_tp() 
    {
        $data['title']                  = "SJ (Kurangan Picker)";
        $reportrange                    = $this->input->post('reportrange');
        $data['list_surat_jalan_tp']    = $this->Surat_jalan_fcd->get_riwayat_tp_inbound($reportrange)->result_array();
        $data['reportrange']            = $reportrange;

        $this->show($data);
    }

    public function update_status_surat_jalan_tp()
    {
        $ids    = $this->input->post('ids');
        $action = $this->input->post('action');

        if (empty($ids)) {
            echo "<script>alert('Harap pilih data yang ingin diproses!'); window.history.back();</script>";
            return;
        }

        $status_baru = '';

        if ($action == 'print') {
            $status_baru = 'PRINT';
        } elseif ($action == 'proses') {
            $status_baru = 'PROSES';
        } elseif ($action == 'done') {
            $status_baru = 'DONE';
        }

        if ($status_baru != '') {
            $this->db->where_in('id', $ids);
            $this->db->update('surat_jalan_tp', [
                'status_inbound'    => $status_baru,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        // JIKA STATUSNYA DONE, UPDATE STATUS RESTOCK DAN KIRIM NOTIFIKASI
        if ($status_baru == 'DONE') {
            $this->db->where_in('id', $ids);
            $this->db->update('surat_jalan_tp', [
                'status_restock'    => 'TERIMA',
            ]);

            $jumlah_dokumen = count($ids);
            $pesan_notif = 'Status ' . $jumlah_dokumen . ' dokumen Surat Jalan (Kurangan Picker) DONE oleh Tim Inbound. Barang sudah di area lift display.';

            $this->Notification->send($pesan_notif, 'TIM RESTOCK', 'Surat Jalan Kurangan Picker');
        }

        $this->show_page('laporan-surat-jalan-tp');
    }

    public function riwayat_surat_jalan()
    {
        $input_range = $this->input->post('reportrange');
        $start_date = date('Y-m-d 00:00:00');
        $end_date   = date('Y-m-d 23:59:59');

        if (empty($input_range)) {
            $reportrange = $start_date . ' - ' . $end_date;
        } else {
            $reportrange = $input_range;
            $dates = explode(' - ', $reportrange);
            
            if (count($dates) == 2) {
                $start_date = $dates[0];
                $end_date   = $dates[1];
            }
        }

        $data['title']              = 'Riwayat Surat Jalan';
        $data['list_surat_jalan']   = $this->Surat_jalan_fcd->get_riwayat($start_date, $end_date)->result_array();
        $data['reportrange']        = $reportrange;
        $data['notif_count']        = $this->Notification->get_unread_count('TIM INBOUND');
        $data['notif_list']         = $this->Notification->get_notifications('TIM INBOUND', 5);

        $this->show($data);
    }

    public function mark_notif_read()
    {
        $id = $this->input->post('id');
        if($id) {
            $this->Notification->mark_as_read($id);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    // --- METHOD BARU UNTUK TANDAI SEMUA DIBACA ---
    public function mark_all_notif_read()
    {
        $category = $this->input->post('category'); // Mengambil kategori dari AJAX
        
        if($category) {
            $this->Notification->mark_all_as_read($category);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kategori tidak valid']);
        }
    }

    public function update_status_surat_jalan()
    {
        $ids    = $this->input->post('ids');
        $action = $this->input->post('action');

        if (empty($ids)) {
            echo "<script>alert('Harap pilih data yang ingin diproses!'); window.history.back();</script>";
            return;
        }

        $status_baru = '';

        if ($action == 'print') {
            $status_baru = 'PRINT';
        } elseif ($action == 'proses') {
            $status_baru = 'PROSES';
        } elseif ($action == 'done') {
            $status_baru = 'DONE';
        }

        if ($status_baru != '') {
            $this->db->where_in('id', $ids);
            $this->db->update('surat_jalan', [
                'status_inbound'    => $status_baru,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            
            // JIKA STATUSNYA ADALAH DONE
            if ($status_baru == 'DONE') {
                $this->db->where_in('id', $ids);
                $this->db->update('surat_jalan', [
                    'status_restock'    => 'TERIMA',
                ]);

                $jumlah_dokumen = count($ids);
                $pesan_notif = 'Status ' . $jumlah_dokumen . ' dokumen Surat Jalan telah di-update menjadi DONE.';

                $this->Notification->send($pesan_notif, 'TIM RESTOCK', 'Inbound Selesai!');
            }
        }

        $this->show_page('riwayat-surat-jalan');
    }

    // ============================================================
    // ============================================================
}
