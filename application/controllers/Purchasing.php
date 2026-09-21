<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Purchasing extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pengembalian_qc');
        $this->load->model('Purchasing_reject');
        $this->load->model('Purchasing_repair');
        $this->load->model('Notification');
        $this->load->library('pusher_lib');
    }

    // === PENGEMBALIAN QC ===

    public function pengembalian_qc()
    {
        $data['title']                  = 'Pengembalian QC';
        $reportrange                    = $this->input->post('reportrange');
        $data['list_pengembalian_qc']   = $this->Pengembalian_qc->get_pengembalian_qc($reportrange)->result_array();
        $data['notif_count']            = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']             = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']            = $reportrange;
        $data['message']                = $this->session->flashdata('message');

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

    public function process_bulk_qc()
    {
        $ids             = $this->input->post('ids');
        $action          = $this->input->post('action_type');
        $no_penyesuaian  = $this->input->post('no_penyesuaian_bulk');

        if (empty($ids) || !is_array($ids)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Tidak ada data yang dipilih!</div>');
            redirect('purchasing/pengembalian_qc');
            return;
        }

        $allowed_actions = ['reject', 'repair', 'giveaway', 'tolak', 'tidak_ada'];
        if (!in_array($action, $allowed_actions)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Tipe aksi tidak valid!</div>');
            redirect('purchasing/pengembalian_qc');
            return;
        }

        $timestamp     = date('Y-m-d H:i:s');
        $keterangan    = $this->input->post('keterangan');
        $success_count = 0;

        $this->db->trans_start();

        if (in_array($action, ['giveaway', 'tolak', 'tidak_ada'])) {
            $status_map = [
                'giveaway'  => 'GIVEAWAY',
                'tolak'     => 'REJECTED',
                'tidak_ada' => 'BARANG_TIDAK_ADA'
            ];
            $status_baru    = $status_map[$action];
            $qty_proses_arr = $this->input->post('qty_proses');     // tolak
            $qty_tdk_arr    = $this->input->post('qty_tidak_ada');  // tidak_ada

            foreach ($ids as $id_pengembalian) {

                // --- TOLAK: partial split ---
                if ($action === 'tolak' && isset($qty_proses_arr[$id_pengembalian])) {
                    $qty_proses  = (int)$qty_proses_arr[$id_pengembalian];
                    $source_data = $this->db->get_where('tblpengembalian_qc', [
                        'id_pengembalian' => $id_pengembalian,
                        'status_sortir'   => 0,
                    ])->row();

                    if ($source_data && $qty_proses > 0 && $qty_proses < $source_data->qty) {
                        $sisa_qty = $source_data->qty - $qty_proses;
                        $this->db->where('id_pengembalian', $id_pengembalian);
                        $this->db->update('tblpengembalian_qc', ['qty' => $sisa_qty]);

                        $new_row = (array)$source_data;
                        unset($new_row['id_pengembalian'], $new_row['created_at'], $new_row['updated_at']);
                        $new_row['qty']               = $qty_proses;
                        $new_row['status']            = $status_baru;
                        $new_row['status_sortir']     = 1;
                        $new_row['keterangan_reject'] = $keterangan;
                        $new_row['acc_by']            = $this->data['user']['id_user'];
                        $new_row['acc_at']            = $timestamp;
                        $this->db->insert('tblpengembalian_qc', $new_row);
                        $success_count++;
                        continue;
                    }
                }

                // --- TIDAK ADA: partial split ---
                if ($action === 'tidak_ada' && isset($qty_tdk_arr[$id_pengembalian])) {
                    $qty_tdk     = (int)$qty_tdk_arr[$id_pengembalian];
                    $source_data = $this->db->get_where('tblpengembalian_qc', [
                        'id_pengembalian' => $id_pengembalian,
                        'status_sortir'   => 0,
                    ])->row();

                    if ($source_data && $qty_tdk > 0 && $qty_tdk < $source_data->qty) {
                        $sisa_qty = $source_data->qty - $qty_tdk;
                        $this->db->where('id_pengembalian', $id_pengembalian);
                        $this->db->update('tblpengembalian_qc', ['qty' => $sisa_qty]);

                        $new_row = (array)$source_data;
                        unset($new_row['id_pengembalian'], $new_row['created_at'], $new_row['updated_at']);
                        $new_row['qty']               = $qty_tdk;
                        $new_row['status']            = 'BARANG_TIDAK_ADA';
                        $new_row['status_sortir']     = 1;
                        $new_row['keterangan_reject'] = $keterangan;
                        $new_row['acc_by']            = $this->data['user']['id_user'];
                        $new_row['acc_at']            = $timestamp;
                        $this->db->insert('tblpengembalian_qc', $new_row);
                        $success_count++;
                        continue;
                    }
                }

                // --- Normal process (Full qty) ---
                $update_data = [
                    'status'            => $status_baru,
                    'status_sortir'     => 1,
                    'keterangan_reject' => $keterangan,
                    'acc_by'            => $this->data['user']['id_user'],
                    'acc_at'            => $timestamp
                ];
                // Simpan no_penyesuaian untuk giveaway jika kolom tersedia
                if ($action === 'giveaway' && !empty($no_penyesuaian)) {
                    $update_data['no_penyesuaian'] = $no_penyesuaian;
                }

                $this->db->where('id_pengembalian', $id_pengembalian);
                $this->db->where('status_sortir', 0);
                $this->db->update('tblpengembalian_qc', $update_data);
                if ($this->db->affected_rows() > 0) $success_count++;
            }

        } else {
            // reject / repair
            $target_table = ($action == 'reject') ? 'purchasing_reject' : 'purchasing_repair';
            foreach ($ids as $id_pengembalian) {
                $source_data = $this->db->get_where('tblpengembalian_qc', [
                    'id_pengembalian' => $id_pengembalian,
                    'status_sortir'   => 0,
                ])->row();

                if ($source_data) {
                    $insert_data = [
                        'id_pengembalian' => $source_data->id_pengembalian,
                        'tanggal'         => $source_data->tanggal,
                        'sku'             => $source_data->sku,
                        'no_rak'          => $source_data->no_rak,
                        'qty'             => $source_data->qty,
                        'keterangan'      => $keterangan,
                        'created_at'      => $timestamp,
                    ];
                    // Simpan no_penyesuaian langsung saat reject
                    if ($action === 'reject' && !empty($no_penyesuaian)) {
                        $insert_data['no_penyesuaian'] = $no_penyesuaian;
                    }

                    $new_status = ($action == 'reject') ? 'REJECTED' : 'REPAIR';
                    $this->db->insert($target_table, $insert_data);
                    $this->db->where('id_pengembalian', $id_pengembalian);
                    $this->db->update('tblpengembalian_qc', [
                        'status'            => $new_status,
                        'status_sortir'     => 1,
                        'keterangan_reject' => $keterangan,
                        'acc_by'            => $this->data['user']['id_user'],
                        'acc_at'            => $timestamp,
                    ]);
                    $success_count++;
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Gagal memproses data. Silakan coba lagi.</div>');
        } else {
            if ($success_count > 0) {
                $display_action = ($action == 'tolak') ? 'DITOLAK' : strtoupper($action);
                $pesan_sukses   = $success_count . ' data berhasil diproses sebagai ' . $display_action;
                $this->session->set_flashdata('message', '<div class="alert alert-success">' . $pesan_sukses . '</div>');
                if ($action == 'repair') {
                    $this->Notification->send($pesan_sukses, 'TIM RESTOCK', 'Update QC: REPAIR');
                } elseif ($action == 'reject') {
                    $this->Notification->send($pesan_sukses, 'TIM ACCOUNTING', 'Update QC: REJECT');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-warning">Data terpilih mungkin sudah diproses sebelumnya.</div>');
            }
        }

        redirect('purchasing/pengembalian_qc');
    }

    // Endpoint: ambil gambar SKU
    public function get_sku_img()
    {
        $sku   = $this->input->get('sku');
        $row   = $this->db->get_where('tblsku', ['id_sku' => $sku])->row();
        $field = null;
        if ($row) {
            // Cek berbagai kemungkinan nama kolom foto
            foreach (['link_foto', 'foto', 'gambar', 'image', 'foto_produk', 'img', 'foto_sku'] as $f) {
                if (!empty($row->$f)) { $field = $row->$f; break; }
            }
        }
        
        if ($field) {
            // Jika berupa URL lengkap: ke URL asli; ?sumber=lokal (dipanggil onerror
            // <img> di view) -> salinan lokal bila sudah disinkron, kalau tidak no-image.
            if (filter_var($field, FILTER_VALIDATE_URL)) {
                if ($this->input->get('sumber') === 'lokal') {
                    $lokal = foto_sku_lokal_url($field, $row->foto_lokal ?? '');
                    if ($lokal !== '') {
                        redirect($lokal);
                    }
                    $field = null; // jatuh ke gambar default di bawah
                } else {
                    redirect($field);
                }
            }
            // Jika base64
            if (strpos($field, 'data:image') === 0) {
                $img_data = explode(',', $field);
                $decoded  = base64_decode($img_data[1]);
                header('Content-Type: image/jpeg');
                echo $decoded;
                exit;
            }
            // Jika path file (cek local)
            $path = FCPATH . $field;
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                header('Content-Type: ' . $mime);
                readfile($path);
                exit;
            }
            // Jika path tapi perlu base_url (untuk eksternal link yang tidak pakai http)
            if (strpos($field, 'http') !== 0 && (strpos($field, 'www.') === 0 || strpos($field, '/') === 0)) {
                 // Coba redirect dengan asumsi ini path relative atau domain tanpa protokol
                 redirect($field);
            }
        }
        
        // Jika gagal mendapatkan gambar, tampilkan gambar default
        $default_path = FCPATH . 'assets/img/no-image.png';
        if (file_exists($default_path)) {
            header('Content-Type: image/png');
            readfile($default_path);
        } else {
            header('HTTP/1.0 404 Not Found');
        }
        exit;
    }

    // === GIVEAWAY ===

    public function giveaway()
    {
        $data['title']          = "Giveaway";
        $reportrange            = $this->input->post('reportrange');
        $data['list_giveaway']  = $this->Pengembalian_qc->get_giveaway($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function kirim_gudang_purchasing()
    {
        $ids            = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian');
        $foto_link      = $this->input->post('foto_link');
        $foto_base64    = $this->input->post('foto_base64');
        
        $foto_final = '';
        if(!empty($foto_link)) {
            $foto_final = $foto_link;
        } else if(!empty($foto_base64)) {
            $foto_final = $this->_upload_base64_img($foto_base64, 'giveaway');
        }

        if ($ids) {
            $this->db->where_in('id_pengembalian', $ids);
            $this->db->update('tblpengembalian_qc', [
                'status'         => 'DIKIRIM KE GUDANG PURCHASING',
                'no_penyesuaian' => $no_penyesuaian,
                'foto_barang'    => $foto_final,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $this->session->set_flashdata('message', '<div class="alert alert-success">Barang berhasil dikirim ke Gudang Purchasing dengan No. Penyesuaian: '.$no_penyesuaian.'</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Pilih barang terlebih dahulu!</div>');
        }

        redirect('purchasing/giveaway');
    }

    public function laporan_giveaway()
    {
        $data['title']          = "Laporan Giveaway";
        $reportrange            = $this->input->post('reportrange');
        $data['list_giveaway']  = $this->Pengembalian_qc->get_laporan_giveaway($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    // ============================================================
    // ============================================================

    // ==== REPAIR ===

    public function repair()
    {
        $data['title']          = "Repair";
        $reportrange            = $this->input->post('reportrange');
        $data['list_repair']    = $this->Purchasing_repair->get_repair($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function bulk_no_penyesuaian_repair()
    {
        $ids            = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian');

        if ($ids && $no_penyesuaian != '') {
            $this->db->trans_start();
            $this->db->where_in('id_pengembalian', $ids);
            $this->db->update('purchasing_repair', [
                'no_penyesuaian' => $no_penyesuaian,
                'updated_at'     => date('Y-m-d H:i:s')
            ]);
            $this->db->trans_complete();
            $this->session->set_flashdata('message', '<div class="alert alert-success">No Penyesuaian Repair berhasil diperbarui.</div>');
        }

        redirect('purchasing/repair');
    }

    public function kirim_gudang_repair()
    {
        $ids = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian');
        $foto_link = $this->input->post('foto_link');
        $foto_base64 = $this->input->post('foto_base64');
        
        $foto_final = '';
        if(!empty($foto_link)) {
            $foto_final = $foto_link;
        } else if(!empty($foto_base64)) {
            $foto_final = $this->_upload_base64_img($foto_base64, 'repair');
        }

        if ($ids) {
            foreach ($ids as $id) {
                $this->db->where('id_pengembalian', $id);
                $this->db->update('purchasing_repair', [
                    'status'          => 'DI GUDANG',
                    'no_penyesuaian'  => $no_penyesuaian,
                    'foto_barang'     => $foto_final,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
            $this->session->set_flashdata('message', '<div class="alert alert-success">Barang berhasil dikirim ke gudang dengan nomor penyesuaian: ' . $no_penyesuaian . '</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Pilih barang terlebih dahulu!</div>');
        }

        redirect('purchasing/repair');
    }

    public function kirim_gudang_reject()
    {
        $ids = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian_modal_val');
        $foto_link = $this->input->post('foto_link');
        $foto_base64 = $this->input->post('foto_base64');
        
        $foto_final = '';
        if(!empty($foto_link)) {
            $foto_final = $foto_link;
        } else if(!empty($foto_base64)) {
            $foto_final = $this->_upload_base64_img($foto_base64, 'reject');
        }

        if ($ids) {
            foreach ($ids as $id) {
                $this->db->where('id_pengembalian', $id);
                $this->db->update('purchasing_reject', [
                    'status'          => 'DI GUDANG',
                    'no_penyesuaian'  => $no_penyesuaian,
                    'foto_barang'     => $foto_final,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
            $this->session->set_flashdata('message', '<div class="alert alert-success">Barang berhasil dikirim ke gudang dengan nomor penyesuaian: ' . $no_penyesuaian . '</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Pilih barang terlebih dahulu!</div>');
        }

        redirect('purchasing/reject');
    }

    private function _upload_base64_img($base64_string, $prefix = 'qc')
    {
        $dir = 'uploads/qc_proof/';
        if (!is_dir(FCPATH . $dir)) {
            mkdir(FCPATH . $dir, 0777, TRUE);
        }

        $image_parts = explode(";base64,", $base64_string);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $file_name = $prefix . '_' . time() . '_' . uniqid() . '.' . $image_type;
        $file_path = $dir . $file_name;

        file_put_contents(FCPATH . $file_path, $image_base64);
        return base_url($file_path);
    }


    public function laporan_repair()
    {
        $data['title']          = "Laporan Repair";
        $reportrange            = $this->input->post('reportrange');
        $data['list_repair']    = $this->Purchasing_repair->get_laporan_repair($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function update_status_repair()
    {
        $ids    = $this->input->post('ids');
        $action = $this->input->post('action');

        if (empty($ids)) {
            echo "<script>alert('Harap pilih data yang ingin diproses!'); window.history.back();</script>";
            return;
        }

        $status_baru = '';

        if ($action == 'done') {
            $status_baru = 'DONE PERBAIKI';
        } elseif ($action == 'antar') {
            $status_baru = 'ANTAR KE DISP';
        }

        if ($status_baru != '') {
            $this->db->where_in('id_pengembalian', $ids);

            // Simpan hasil eksekusi update ke variabel $update
            $update = $this->db->update('purchasing_repair', [
                'status'     => $status_baru,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($update && $status_baru == 'ANTAR KE DISP') {
                $jumlah_barang = count($ids);
                $pesan_notif = "Sebanyak " . $jumlah_barang . " barang perbaikan telah diantar ke DISP oleh Tim Purchasing.";
                
                // Menggunakan model terpadu yang baru
                $this->Notification->send($pesan_notif, 'TIM RESTOCK', 'Barang Diantar ke DISP');
            }
        }

        $this->show_page('laporan-repair'); 
    }

    // ============================================================
    // ============================================================

    // === REJECT ===

    public function reject()
    {
        $reportrange            = $this->input->post('reportrange');
        $data['title']          = 'Reject';
        $data['list_reject']    = $this->Purchasing_reject->get_reject($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function bulk_no_penyesuaian() 
    {
        $ids            = $this->input->post('ids');
        $no_penyesuaian = $this->input->post('no_penyesuaian');

        if ($no_penyesuaian != '') {
            $this->db->trans_start();
            $this->db->where_in('id_pengembalian', $ids);
            $this->db->update('purchasing_reject', [
                'no_penyesuaian' => $no_penyesuaian,
                'updated_at'     => date('Y-m-d H:i:s')
            ]);
            $this->db->trans_complete();
        }

        $this->show_page('reject');
    }

    public function laporan_reject() 
    {
        $data['title']          = "Laporan Reject";
        $reportrange            = $this->input->post('reportrange');
        $data['list_reject']    = $this->Purchasing_reject->get_laporan_reject($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function update_no_penyesuaian()
    {
        $id_pengembalian        = $this->input->post('id_pengembalian');
        $no_penyesuaian_baru    = $this->input->post('no_penyesuaian');

        if (empty($id_pengembalian) || empty($no_penyesuaian_baru)) {
            $this->session->set_flashdata('error', 'Data tidak boleh kosong.');
            $this->show_page('laporan-reject');
        }

        $data_update = [
            'no_penyesuaian'    => $no_penyesuaian_baru,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id_pengembalian', $id_pengembalian);
        $update = $this->db->update('purchasing_reject', $data_update);

        if ($update) {
            $this->session->set_flashdata('success', 'No Penyesuaian berhasil diperbaiki.');
            $this->show_page('laporan-reject');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate data.');
            $this->show_page('laporan-reject');
        }
    }

    public function laporan_penolakan()
    {
        $data['title']          = "Laporan Penolakan";
        $reportrange            = $this->input->post('reportrange');
        $data['list_penolakan'] = $this->Pengembalian_qc->get_laporan_penolakan($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function laporan_tidak_ada()
    {
        $data['title']          = "Laporan Barang Tidak Ada";
        $reportrange            = $this->input->post('reportrange');
        $data['list_tidak_ada'] = $this->Pengembalian_qc->get_laporan_tidak_ada($reportrange)->result_array();
        $data['notif_count']    = $this->Notification->get_unread_count('TIM PURCHASING');
        $data['notif_list']     = $this->Notification->get_notifications('TIM PURCHASING', 5);
        $data['reportrange']    = $reportrange;

        $this->show($data);
    }

    public function refresh_menu()
    {
        $this->load->model('access_fcd');
        $this->load->helper('menu_helper');
        $user = $this->session->userdata('user');

        if ($user) {
            $list_menu = $this->access_fcd->get_access_menu($user['hakakses'])->result_array();
            if (!empty($list_menu)) {
                $list_menu_tree = menu_to_tree($list_menu, $list_menu[0]);
                $html_menu_tree = tree_to_html_menu($list_menu_tree['child']);
                $this->session->set_userdata('html_menu_tree', $html_menu_tree);
                echo "Menu berhasil diperbarui! Silakan refresh halaman (tekan F5).";
            } else {
                echo "Gagal mengambil data menu.";
            }
        } else {
            echo "Sesi login tidak ditemukan. Silakan login kembali.";
        }
    }

    // ============================================================
    // ============================================================
}
