<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Finance extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('receipt_fcd');
        $this->load->model('Denda');
        $this->load->model('Notification');
    }

    public function pergantian_barang()
    {
        $data['title'] = 'Pergantian Barang';
        $data['data']  = $this->receipt_fcd->get_laporan_pergantian_barang()->result_array();
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);

        $this->show($data);
    }

    public function bulk_acc()
    {
        $ids = $this->input->post('no_resi');

        if (!empty($ids)) {
            $data_update = [
                'status_acc' => 1,
                'acc_by'     => $this->data['user']['id_user'],
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->where_in('no_resi', $ids);
            $this->db->update('pergantian_barang', $data_update);

            // Notify Accounting
            $count = count($ids);
            $this->Notification->send("Finance telah menyetujui (ACC) $count pergantian barang. Silakan lakukan penyesuaian stok.", "TIM ACCOUNTING", "Pergantian Barang - ACC");
        }

        $this->show_page('pergantian-barang');
    }

    // ======================================================
    // DENDA
    // ======================================================

    public function denda()
    {
        $data['title'] = "Denda";
        $data['data'] = $this->Denda->get_all()->result_array();
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);

        $this->show($data);
    }

    public function bulk_status()
    {
        $ids = $this->input->post('ids');

        if (!empty($ids)) {
            $data_update = [
                'status'     => 'DONE',
                'acc_by'     => $this->data['user']['id_user'],
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->where_in('id', $ids);
            $this->db->update('denda', $data_update);

            $count = count($ids);
            $this->Notification->send("Finance telah menyelesaikan (DONE) $count denda kpi.", "TIM ACCOUNTING", "Denda Selesai");
        }

        $this->show_page('denda');
    }

    // ======================================================
    // CONTROL PENJUALAN - UPLOAD HPP
    // ======================================================

    public function upload_hpp()
    {
        $this->_refresh_menu_if_needed('finance/upload_hpp');
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);
        $this->show($data);
    }

    public function upload_hpp_action()
    {
        ob_start();

        try {
            if ($this->input->method() !== 'post') {
                throw new Exception('Invalid request method.');
            }

            if (!isset($_FILES['hppFile']) || $_FILES['hppFile']['error'] != 0) {
                throw new Exception('File tidak ditemukan atau gagal diupload.');
            }

            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', 0);
            set_time_limit(0);

            $user_id   = $this->data['user']['id_user'] ?? null;
            $upload_id = $this->input->post('upload_id') ?? '';
            $file      = $_FILES['hppFile']['tmp_name'];

            $reader      = IOFactory::createReader(IOFactory::identify($file));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $sheet       = $spreadsheet->getActiveSheet();
            $dataRaw     = $sheet->toArray(null, true, true, true);

            $this->load->model('Hpp_fcd');
            $result = $this->Hpp_fcd->insert_hpp_upload($dataRaw, $user_id, $upload_id);

            $row_count = count($dataRaw) - 1;
            $this->Notification->send("Upload HPP massal selesai ($row_count baris diproses).", "GENERAL", "Upload HPP Selesai");

            if (ob_get_length()) ob_clean();
            $this->make_ajax_response(200, $result);

        } catch (Throwable $e) {
            if (ob_get_length()) ob_clean();
            log_message('error', "HPP Upload Error: " . $e->getMessage());
            $this->make_ajax_response(500, "Error: " . $e->getMessage());
        }

        ob_end_flush();
    }

    public function get_hpp_progress($upload_id)
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $file = sys_get_temp_dir() . '/hpp_progress_' . preg_replace('/[^a-z0-9]/i', '', $upload_id);

        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            echo json_encode(['processed' => 0, 'total' => 0, 'remaining' => 0, 'percentage' => 0]);
        }
        exit();
    }

    // ======================================================
    // CONTROL PENJUALAN - LIST DATA HPP
    // ======================================================

    public function list_hpp()
    {
        $this->_refresh_menu_if_needed('finance/list_hpp');
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);
        $this->show($data);
    }

    public function get_hpp_stats()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $this->load->model('Hpp_fcd');
        $stats = $this->Hpp_fcd->get_stats();
        echo json_encode($stats);
        exit();
    }

    public function get_hpp_data()
    {
        $this->load->model('Hpp_fcd');
        $draw  = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start']  = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $data['search'] = $this->input->post('search')['value'];

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }

        $data['dir'] = $dir;
        $data['valid_columns'] = [
            0 => 'id_sku',
            1 => 'nama_sku',
            2 => 'bundle',
            3 => 'variasi',
            4 => 'hpp',
            5 => 'total_stok',
        ];
        $data['order'] = !isset($data['valid_columns'][$col]) ? null : $data['valid_columns'][$col];

        $list  = $this->Hpp_fcd->get_data($data);
        $total = $this->Hpp_fcd->get_total_data($data);

        $i = $data['start'] + 1;
        $result_data = [];
        foreach ($list->result() as $row) {
            $result_data[] = [
                $i++ . '.',
                $row->id_sku,
                $row->nama_sku,
                $row->bundle,
                $row->variasi,
                'Rp ' . number_format($row->hpp, 0, ',', '.'),
                number_format($row->total_stok, 0, ',', '.'),
                $row->updated,
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $result_data,
        ]);
        exit();
    }

    // ======================================================
    // CONTROL PENJUALAN - LAPORAN
    // ======================================================

    public function laporan_control_penjualan()
    {
        $this->_refresh_menu_if_needed('finance/laporan_control_penjualan');
        $data['notif_count'] = $this->Notification->get_unread_count('TIM FINANCE');
        $data['notif_list']  = $this->Notification->get_notifications('TIM FINANCE', 5);
        $this->show($data);
    }

    public function get_laporan_data()
    {
        $this->load->model('Hpp_fcd');
        $draw  = intval($this->input->post('draw'));
        $order = $this->input->post('order');

        $data['start']          = intval($this->input->post('start'));
        $data['length']         = intval($this->input->post('length'));
        $data['tgl_awal']       = $this->input->post('tgl_awal')       ?? '';
        $data['tgl_akhir']      = $this->input->post('tgl_akhir')      ?? '';
        $data['search_sku']     = $this->input->post('search_sku')     ?? '';
        $data['filter_toko']    = $this->input->post('filter_toko')    ?? '';
        $data['filter_status']  = $this->input->post('filter_status')  ?? '';
        $data['filter_analisa'] = $this->input->post('filter_analisa') ?? '';

        $col = 0;
        $dir = '';
        if (!empty($order)) {
            foreach ($order as $o) {
                $col = $o['column'];
                $dir = $o['dir'];
            }
        }
        $data['dir']   = $dir;
        $data['order'] = null;

        $list  = $this->Hpp_fcd->get_laporan($data);
        $total = $this->Hpp_fcd->get_total_laporan($data);

        $i = $data['start'] + 1;
        $result_data = [];
        foreach ($list->result() as $row) {
            $hpp      = (float)($row->hpp    ?? 0);
            $harga    = (float)($row->harga  ?? 0);
            $profit   = (float)($row->profit ?? ($harga - $hpp));
            $margin   = $hpp > 0 ? round((($harga - $hpp) / $hpp) * 100, 2) : null;

            // Analisa Status Logic
            $status_analisa = '<span class="label label-success" style="font-size:10px;">AMAN</span>';
            if ($hpp <= 0) {
                $status_analisa = '<span class="label label-default" style="font-size:10px;">HPP BELUM SET</span>';
            } elseif ($harga < $hpp) {
                $status_analisa = '<span class="label label-danger" style="font-size:10px; background:#e74c3c !important;">BERMASALAH (RUGI)</span>';
            } elseif ($margin !== null && $margin <= 5) {
                $status_analisa = '<span class="label label-warning" style="font-size:10px; background:#f39c12 !important;">BERMASALAH (TIPIS)</span>';
            }

            // Warna margin: hijau jika positif, merah jika negatif
            $margin_html = $margin !== null
                ? '<span style="color:' . ($margin >= 0 ? '#27ae60' : '#e74c3c') . '; font-weight:700;">'
                  . number_format($margin, 1) . '%</span>'
                : '<span style="color:#999;">-</span>';

            $profit_html = '<span style="color:' . ($profit >= 0 ? '#27ae60' : '#e74c3c') . '; font-weight:600;">'
                         . 'Rp ' . number_format($profit, 0, ',', '.') . '</span>';

            $result_data[] = [
                $i++ . '.',
                htmlspecialchars($row->noresi        ?? '-'),
                htmlspecialchars($row->toko          ?? '-'),
                htmlspecialchars($row->resi_id_sku   ?? '-'),
                htmlspecialchars($row->nama_sku      ?? '-'),
                htmlspecialchars($row->variasi       ?? '-'),
                'Rp ' . number_format($harga,  0, ',', '.'),
                'Rp ' . number_format($hpp,    0, ',', '.'),
                $profit_html,
                $margin_html,
                $status_analisa,
                date('d/m/Y H:i', strtotime($row->tanggal_printresi ?? 'now')),
                htmlspecialchars($row->status_pesanan ?? '-'),
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $result_data,
        ]);
        exit();
    }

    public function get_laporan_totals()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $this->load->model('Hpp_fcd');

        $data = [
            'tgl_awal'      => $this->input->post('tgl_awal')      ?? '',
            'tgl_akhir'     => $this->input->post('tgl_akhir')     ?? '',
            'filter_toko'   => $this->input->post('filter_toko')   ?? '',
            'filter_status' => $this->input->post('filter_status') ?? '',
        ];

        $row = $this->Hpp_fcd->get_laporan_totals($data);
        echo json_encode([
            'total_resi'   => $row ? number_format($row->total_resi, 0, ',', '.') : '0',
            'total_harga'  => $row ? 'Rp ' . number_format($row->total_harga,  0, ',', '.') : 'Rp 0',
            'total_hpp'    => $row ? 'Rp ' . number_format($row->total_hpp,    0, ',', '.') : 'Rp 0',
            'total_profit' => $row ? 'Rp ' . number_format($row->total_profit, 0, ',', '.') : 'Rp 0',
            'total_bermasalah' => $row ? number_format($row->total_bermasalah, 0, ',', '.') : '0',
        ]);
        exit();
    }

    public function get_toko_list()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $this->load->model('Hpp_fcd');
        echo json_encode($this->Hpp_fcd->get_toko_list());
        exit();
    }

    // ======================================================
    // PRIVATE HELPERS
    // ======================================================

    public function upload_sales_action()
    {
        ob_start();
        try {
            if ($this->input->method() !== 'post') throw new Exception('Invalid request method.');
            if (!isset($_FILES['salesFile']) || $_FILES['salesFile']['error'] != 0) throw new Exception('File tidak ditemukan.');

            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', 0);
            set_time_limit(0);

            $user_id   = $this->data['user']['id_user'] ?? null;
            $upload_id = $this->input->post('upload_id') ?? '';
            $file      = $_FILES['salesFile']['tmp_name'];

            $reader      = IOFactory::createReader(IOFactory::identify($file));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $sheet       = $spreadsheet->getActiveSheet();
            $dataRaw     = $sheet->toArray(null, true, true, true);

            $this->load->model('Hpp_fcd');
            $result = $this->Hpp_fcd->insert_sales_upload($dataRaw, $user_id, $upload_id);

            if (ob_get_length()) ob_clean();
            $this->make_ajax_response(200, $result);

        } catch (Throwable $e) {
            if (ob_get_length()) ob_clean();
            $this->make_ajax_response(500, "Error: " . $e->getMessage());
        }
        ob_end_flush();
    }

    /**
     * Refresh the session menu tree if the given URI is not yet present.
     * This lets users see newly added menus without having to logout/login.
     * Pattern borrowed from Sku::stock_terupdate().
     */
    private function _refresh_menu_if_needed($uri)
    {
        if (strpos($this->session->userdata('html_menu_tree'), $uri) === false) {
            $this->load->model('access_fcd');
            $this->load->helper('menu_helper');

            $list_menu = $this->access_fcd->get_access_menu($this->data['user']['hakakses'])->result_array();
            if (!empty($list_menu)) {
                $list_menu_tree = menu_to_tree($list_menu, $list_menu[0]);
                $html_menu_tree = tree_to_html_menu($list_menu_tree['child']);

                $this->session->set_userdata('list_menu',      $list_menu);
                $this->session->set_userdata('list_menu_tree', $list_menu_tree);
                $this->session->set_userdata('html_menu_tree', $html_menu_tree);

                // Immediately apply to current request so sidebar renders correctly
                $this->data['html_menu_tree'] = $html_menu_tree;
            }
        }
    }
}
