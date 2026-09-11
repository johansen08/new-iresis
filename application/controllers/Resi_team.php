<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Resi_team extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('resi_team_fcd');
        // Ensure menu tree is available for all methods
        $this->data['html_menu_tree'] = $this->session->userdata('html_menu_tree');
    }

    public function selisih_paket()
    {
        $data['title'] = 'SELISIH HARI INI';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/selisih_paket', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_selisih_data()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d 00:00:00');
        $end_date = $this->input->post('end_date') ?: date('Y-m-d 23:59:59');

        $target_date = date('Y-m-d', strtotime($start_date));
        $top_stats = $this->resi_team_fcd->get_top_stats($target_date);
        
        // Fetch and split data for Total and Wajib categories
        $groups = $this->resi_team_fcd->get_optimized_selisih_bundle($start_date, $end_date);
        
        $process_data = function($pools) {
            $pool1 = $pools['pool1'] ?? [];
            $pool2 = $pools['pool2'] ?? [];
            $pool4 = $pools['pool4'] ?? [];

            // Calculate Pool 3 (1+2 Combined)
            $pool3 = [];
            $keys = ['total_resi', 'sku_special', 'resi_qty_banyak', 'resi_1_sku_sd_9', 'resi_2_9_sku_sd_9'];
            foreach ($keys as $k) {
                $pool3[$k] = (int)($pool1[$k] ?? 0) + (int)($pool2[$k] ?? 0);
            }

            // Merge Special SKU for Pool 3
            $merged_special = [];
            foreach (array_merge($pool1['special_sku_list'] ?? [], $pool2['special_sku_list'] ?? []) as $item) {
                $id = $item['id_sku'];
                if (!isset($merged_special[$id])) {
                    $merged_special[$id] = $item;
                    $merged_special[$id]['resi_count'] = (int)$item['resi_count'];
                } else {
                    $merged_special[$id]['resi_count'] += (int)$item['resi_count'];
                }
            }
            $pool3['special_sku_list'] = array_values($merged_special);
            $pool3['total_special_skus'] = count($pool3['special_sku_list']);
            
            $pool1['total_special_skus'] = count($pool1['special_sku_list'] ?? []);
            $pool2['total_special_skus'] = count($pool2['special_sku_list'] ?? []);
            $pool4['total_special_skus'] = count($pool4['special_sku_list'] ?? []);

            // Calculate Pool 5 (3+4 Combined -> 1+2+4)
            $pool5 = [];
            foreach ($keys as $k) {
                $pool5[$k] = (int)($pool3[$k] ?? 0) + (int)($pool4[$k] ?? 0);
            }

            // Merge Special SKU for Pool 5
            $merged_special_5 = [];
            foreach (array_merge($pool3['special_sku_list'] ?? [], $pool4['special_sku_list'] ?? []) as $item) {
                $id = $item['id_sku'];
                if (!isset($merged_special_5[$id])) {
                    $merged_special_5[$id] = $item;
                    $merged_special_5[$id]['resi_count'] = (int)$item['resi_count'];
                } else {
                    $merged_special_5[$id]['resi_count'] += (int)$item['resi_count'];
                }
            }
            $pool5['special_sku_list'] = array_values($merged_special_5);
            $pool5['total_special_skus'] = count($pool5['special_sku_list']);

            return [
                'pool1' => $pool1,
                'pool2' => $pool2,
                'pool3' => $pool3,
                'pool4' => $pool4,
                'pool5' => $pool5
            ];
        };

        $response = [
            'top_stats' => $top_stats,
            'sisa_data' => $process_data($groups['SISA']),
            'wajib_data' => $process_data($groups['WAJIB']),
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->make_ajax_response(200, 'Success', $response);
    }

    public function batas_kirim_paket()
    {
        $data['title'] = 'BATAS KIRIM PAKET';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/batas_kirim_paket', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_batas_kirim_data()
    {
        $target_date = date('Y-m-d');
        $top_stats = $this->resi_team_fcd->get_top_stats($target_date);
        $days = $this->resi_team_fcd->get_batas_kirim_summary();
        
        $response = [
            'top_stats' => $top_stats,
            'days' => $days,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->make_ajax_response(200, 'Success', $response);
    }

    public function paket_on_progress()
    {
        $data['title'] = 'PAKET ON PROGRESS';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/paket_on_progress', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_on_progress_data()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d 00:00:00');
        $end_date = $this->input->post('end_date') ?: date('Y-m-d 23:59:59');

        // Parse human dates to database format if needed
        if (strpos($start_date, ':') === false) $start_date .= ' 00:00:00';
        if (strpos($end_date, ':') === false) $end_date .= ' 23:59:59';

        $summary_today = $this->resi_team_fcd->get_on_progress_today_summary();
        $days = $this->resi_team_fcd->get_on_progress_deadline_breakdown($start_date, $end_date);
        
        $response = [
            'summary_today' => $summary_today,
            'days' => $days,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->make_ajax_response(200, 'Success', $response);
    }

    public function get_indicator_details()
    {
        $pool_type = $this->input->post('pool_type');
        $indicator_key = $this->input->post('indicator_key');
        $start_date = $this->input->post('start_date') ?: date('Y-m-d 00:00:00');
        $end_date = $this->input->post('end_date') ?: date('Y-m-d 23:59:59');

        $details = $this->resi_team_fcd->get_indicator_details($pool_type, $indicator_key, $start_date, $end_date);
        $this->make_ajax_response(200, 'Success', $details);
    }

    public function export_excel()
    {
        // Implementation would go here, matching rev
    }
    public function scan_preorder()
    {
        $data['title'] = 'SCAN RESI PREORDER';
        
        $this->load->model('picking_fcd');
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/scan_preorder', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function save_scan_preorder()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $id_picker = $this->input->post('id_pegawaipicker');
        $is_preorder = $this->input->post('is_preorder') ?? 1;

        if (empty($noresi)) {
            $this->make_ajax_response(400, 'Nomor resi tidak boleh kosong');
        }

        $result = $this->resi_team_fcd->save_scan_preorder($noresi, $id_picker, $is_preorder, $this->data['user']);

        if (isset($result['error']) && $result['error'] === TRUE) {
            $this->make_ajax_response($result['code'], $result['message']);
        }

        if ($result['affected_rows'] > 0) {
            $this->make_ajax_response(201, 'Sukses menandai resi sebagai Preorder');
        }

        $this->make_ajax_response(400, 'Gagal memproses data');
    }

    public function cek_paket_rts()
    {
        $data['title'] = 'CEK PAKET RTS';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/cek_paket_rts', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_resi_db_data()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        if (empty($start_date) || empty($end_date)) {
            $this->make_ajax_response(400, 'Rentang tanggal tidak boleh kosong');
        }

        $result = $this->resi_team_fcd->get_resi_db_data($start_date, $end_date);
        $this->make_ajax_response(200, 'Success', $result);
    }

    // ─────────────────────────────────────────────
    //  DAFTAR SKU
    // ─────────────────────────────────────────────

    public function daftar_sku()
    {
        $data['title'] = 'DAFTAR SKU';
        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/daftar_sku', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    public function get_daftar_sku_dt()
    {
        $draw   = intval($this->input->post('draw'));
        $order  = $this->input->post('order');
        $search = $this->input->post('search');

        $valid_columns = [
            0 => null,         // checkbox column
            1 => 'id_sku',
            2 => 'nama_sku',
            3 => 'is_special',
            4 => 'jenis_packing',
            5 => 'no_rak',
            6 => 'total_stok',
        ];

        $col = 0;
        $dir = 'ASC';
        if (!empty($order)) {
            $col = intval($order[0]['column']);
            $dir = $order[0]['dir'];
        }

        $params = [
            'start'          => intval($this->input->post('start')),
            'length'         => intval($this->input->post('length')),
            'search'         => $search['value'] ?? '',
            'order'          => $valid_columns[$col] ?? 'id_sku',
            'dir'            => in_array($dir, ['asc','desc']) ? $dir : 'ASC',
            'filter_special' => $this->input->post('filter_special'),
            'filter_packing' => $this->input->post('filter_packing') ?? '',
        ];

        $rows  = $this->resi_team_fcd->get_daftar_sku_data($params);
        $total = $this->resi_team_fcd->count_all_daftar_sku($params);

        // Predefined packing options
        $packing_options = ['Kardus', 'Bubble Wrap', 'Plastik', 'Amplop', 'Karung', 'Kayu', 'Lainnya'];

        $result = [];
        foreach ($rows->result() as $row) {
            $special_badge = $row->is_special
                ? '<span class="label label-danger"><i class="fa fa-star"></i> SPECIAL</span>'
                : '<span class="label label-default">NON SPECIAL</span>';

            $special_select = '<select class="form-control input-sm select-special" data-id="' . htmlspecialchars($row->id_sku) . '">
                <option value="0"' . ($row->is_special == 0 ? ' selected' : '') . '>Non Special</option>
                <option value="1"' . ($row->is_special == 1 ? ' selected' : '') . '>⭐ Special</option>
            </select>';

            $packing_opts = '<option value="">-- Belum diset --</option>';
            foreach ($packing_options as $opt) {
                $sel = ($row->jenis_packing === $opt) ? ' selected' : '';
                $packing_opts .= '<option value="' . $opt . '"' . $sel . '>' . $opt . '</option>';
            }
            $packing_select = '<select class="form-control input-sm select-packing" data-id="' . htmlspecialchars($row->id_sku) . '">' . $packing_opts . '</select>';

            $packing_badge = $row->jenis_packing
                ? '<span class="label label-info">' . htmlspecialchars($row->jenis_packing) . '</span>'
                : '<span class="label label-warning">Belum diset</span>';

            $no_rak_val = htmlspecialchars($row->no_rak ?? '');
            $no_rak_input = '<input type="text" class="form-control input-sm input-norak" data-id="' . htmlspecialchars($row->id_sku) . '" value="' . $no_rak_val . '" placeholder="-- kosong --" style="min-width:90px;">';

            $result[] = [
                '<input type="checkbox" class="sku-check" value="' . htmlspecialchars($row->id_sku) . '">',
                '<span class="text-mono">' . htmlspecialchars($row->id_sku) . '</span>',
                htmlspecialchars($row->nama_sku),
                $special_select,
                $packing_select,
                $no_rak_input,
                number_format($row->total_stok),
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $result,
        ]);
        exit();
    }

    public function update_sku_inline()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_sku      = $this->input->post('id_sku');
        $field       = $this->input->post('field');
        $value       = $this->input->post('value');

        $allowed = ['is_special', 'jenis_packing', 'no_rak'];
        if (!in_array($field, $allowed)) {
            $this->make_ajax_response(400, 'Field tidak diizinkan');
        }

        $update = [$field => $value];
        $result = $this->resi_team_fcd->update_sku_inline($id_sku, $update);

        if ($result) {
            $this->make_ajax_response(200, 'Data berhasil disimpan');
        } else {
            $this->make_ajax_response(500, 'Gagal menyimpan data');
        }
    }

    public function bulk_update_skus()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(405, 'Method not allowed');
        }

        $id_skus         = $this->input->post('id_skus');
        $is_special_raw  = $this->input->post('is_special');
        $jenis_packing   = $this->input->post('jenis_packing');

        if (empty($id_skus) || !is_array($id_skus)) {
            $this->make_ajax_response(400, 'Pilih minimal satu SKU');
        }

        $update = [];
        if ($is_special_raw !== null && $is_special_raw !== '') {
            $update['is_special'] = (int)$is_special_raw;
        }
        if ($jenis_packing !== null && $jenis_packing !== '__no_change__') {
            $update['jenis_packing'] = $jenis_packing;
        }

        if (empty($update)) {
            $this->make_ajax_response(400, 'Tidak ada data yang diubah');
        }

        $result = $this->resi_team_fcd->bulk_update_skus($id_skus, $update);
        $count  = count($id_skus);

        if ($result) {
            $this->make_ajax_response(200, "Berhasil mengupdate $count SKU");
        } else {
            $this->make_ajax_response(500, 'Gagal mengupdate SKU');
        }
    }

    // ─────────────────────────────────────────────
    //  CANCEL ORDER
    // ─────────────────────────────────────────────

    /** Rentang tanggal + filter yang dipakai bersama daftar / statistik / export. */
    private function _cancel_order_params()
    {
        $start = $this->input->post('start_date') ?: date('Y-m-d', strtotime('-7 days'));
        $end   = $this->input->post('end_date') ?: date('Y-m-d');

        if (strpos($start, ':') === false) $start .= ' 00:00:00';
        if (strpos($end, ':') === false)   $end   .= ' 23:59:59';

        $filter_tanggal = $this->input->post('filter_tanggal') === 'pesan' ? 'pesan' : 'cancel';
        $sumber         = strtoupper((string) $this->input->post('filter_sumber'));
        $status         = strtoupper((string) $this->input->post('filter_status'));

        return [
            'start_date'         => $start,
            'end_date'           => $end,
            'filter_tanggal'     => $filter_tanggal,
            'filter_marketplace' => $this->input->post('filter_marketplace'),
            'filter_status'      => in_array($status, ['CANCELED', 'REQUEST_CANCEL', 'CANCEL MANUAL'], true) ? $status : '',
            'filter_sumber'      => in_array($sumber, ['JUBELIO', 'SCAN'], true) ? $sumber : '',
        ];
    }

    /** '2026-07-28 14:05:11' -> "28/07/2026<br>14:05:11" */
    private function _cancel_order_tanggal($nilai)
    {
        if (empty($nilai) || $nilai === '0000-00-00 00:00:00') {
            return '<span class="text-muted">-</span>';
        }
        $ts = strtotime($nilai);
        if (!$ts) {
            return '<span class="text-muted">-</span>';
        }
        return '<span class="co-tgl">' . date('d/m/Y', $ts) . '</span>'
            . '<br><span class="co-jam">' . date('H:i:s', $ts) . '</span>';
    }

    public function cancel_order()
    {
        $this->load->model('cancel_order_fcd');

        $data['title']            = 'CANCEL ORDER';
        $data['list_marketplace'] = $this->cancel_order_fcd->get_list_marketplace();

        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/cancel_order', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    /** Data DataTable server-side untuk daftar cancel order. */
    public function get_cancel_order_data()
    {
        $this->load->model('cancel_order_fcd');

        $draw   = intval($this->input->post('draw'));
        $order  = $this->input->post('order');
        $search = $this->input->post('search');

        $kolom = [
            0 => 'noresi',
            1 => 'no_pesanan',
            2 => 'nama_marketplace',
            3 => 'status_marketplace',
            4 => 'tanggal_pesan',
            5 => 'tanggal_cancel',
            6 => 'sumber',
            7 => null, // aksi
        ];

        $col = 5;
        $dir = 'desc';
        if (!empty($order)) {
            $col = intval($order[0]['column']);
            $dir = $order[0]['dir'];
        }

        $params = $this->_cancel_order_params();
        $params['start']  = intval($this->input->post('start'));
        $params['length'] = intval($this->input->post('length'));
        $params['search'] = $search['value'] ?? '';
        $params['order']  = $kolom[$col] ?? 'tanggal_cancel';
        $params['dir']    = $dir;

        $rows  = $this->cancel_order_fcd->get_cancel_data($params);
        $total = $this->cancel_order_fcd->count_cancel_data($params);
        $stats = $this->cancel_order_fcd->get_stats($params);

        $badge_status = [
            'CANCELED'       => 'co-badge co-badge-danger',
            'REQUEST_CANCEL' => 'co-badge co-badge-warning',
            'CANCEL MANUAL'  => 'co-badge co-badge-info',
        ];

        $result = [];
        foreach ($rows as $row) {
            $status = $row->status_marketplace ?: '-';
            $kelas  = $badge_status[$status] ?? 'co-badge co-badge-default';

            $toko = $row->toko
                ? '<br><small class="text-muted">' . htmlspecialchars($row->toko) . '</small>'
                : '';

            if ($row->sumber === 'SCAN') {
                $sumber = '<span class="co-badge co-badge-scan"><i class="fa fa-barcode"></i> SCAN MANUAL</span>';
                if ($row->terkonfirmasi_jubelio) {
                    $sumber .= '<br><small class="text-success"><i class="fa fa-check"></i> cocok Jubelio</small>';
                }
                $aksi = '<button class="btn btn-xs btn-danger btn-hapus-cancel" data-id="' . (int) $row->id_cancel
                    . '" data-resi="' . htmlspecialchars($row->noresi) . '" title="Hapus data manual">'
                    . '<i class="fa fa-trash"></i></button>';
            } else {
                $sumber = '<span class="co-badge co-badge-jubelio"><i class="fa fa-cloud-download"></i> JUBELIO</span>';
                $aksi   = '<span class="text-muted" title="Data Jubelio ikut sinkron, tidak bisa dihapus manual">&mdash;</span>';
            }

            if (!$row->ada_di_iresis) {
                $sumber .= '<br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> tidak ada di iresis</small>';
            }

            $result[] = [
                '<span class="co-resi">' . htmlspecialchars($row->noresi) . '</span>',
                htmlspecialchars($row->no_pesanan ?: '-'),
                '<strong>' . htmlspecialchars($row->nama_marketplace ?: '-') . '</strong>' . $toko,
                '<span class="' . $kelas . '">' . htmlspecialchars($status) . '</span>',
                $this->_cancel_order_tanggal($row->tanggal_pesan),
                $this->_cancel_order_tanggal($row->tanggal_cancel),
                $sumber,
                $aksi,
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $result,
            'stats'           => $stats,
            'per_marketplace' => $this->cancel_order_fcd->get_per_marketplace($params),
        ]);
        exit();
    }

    /** Tarik resi cancel dari data Jubelio (tblprintresi) ke daftar cancel order. */
    public function sync_cancel_order()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, INVALID_REQUEST_METHOD);
        }

        $this->load->model('cancel_order_fcd');

        $params = $this->_cancel_order_params();

        // Sinkron selalu berdasar tanggal print resi, berapa pun kolom tanggal
        // yang sedang dipakai user untuk memfilter tampilan.
        $selisih_hari = (strtotime($params['end_date']) - strtotime($params['start_date'])) / 86400;
        if ($selisih_hari > 366) {
            $this->make_ajax_response(400, 'Rentang sinkron maksimal 1 tahun. Persempit rentang tanggalnya.');
        }

        set_time_limit(300);

        $hasil = $this->cancel_order_fcd->sync_from_jubelio(
            $params['start_date'],
            $params['end_date'],
            $this->data['user']['id_user'] ?? null
        );

        $this->make_ajax_response(
            200,
            "Sinkron Jubelio selesai: {$hasil['diproses']} resi cancel diperiksa, "
                . "{$hasil['baru']} baru masuk daftar, {$hasil['diperbarui']} diperbarui.",
            $hasil
        );
    }

    public function scan_cancel_order()
    {
        $this->load->model('cancel_order_fcd');

        $data['title']         = 'SCAN CEK CANCEL ORDER';
        $data['scan_terakhir'] = $this->cancel_order_fcd->get_scan_terakhir(15);

        if ($this->input->is_ajax_request()) {
            $this->show($data);
        } else {
            $this->data['content'] = $this->load->view('resi_team/scan_cancel_order', $data, TRUE);
            $this->load->view('main', $this->data);
        }
    }

    /** Riwayat cancel terakhir, dipakai untuk menyegarkan panel di halaman scan. */
    public function get_scan_terakhir_cancel()
    {
        $this->load->model('cancel_order_fcd');
        $this->make_ajax_response(200, 'Success', [
            'rows' => $this->cancel_order_fcd->get_scan_terakhir(15),
        ]);
    }

    /** Cek satu resi: sudah cancel atau belum (dipakai halaman scan). */
    public function cek_cancel_order()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi tidak boleh kosong');
        }

        $this->load->model('cancel_order_fcd');
        $info = $this->cancel_order_fcd->cek_resi($noresi);

        $pesan = [
            'SUDAH_CANCEL'        => 'Resi ini SUDAH CANCEL di marketplace.',
            'CANCEL_MANUAL'       => 'Resi ini ditandai CANCEL manual oleh tim resi (belum terbaca cancel di Jubelio).',
            'BELUM_CANCEL'        => 'Resi ini BELUM cancel — status pesanan masih ' . ($info['status_pesanan'] ?: 'tidak diketahui') . '.',
            'TIDAK_ADA_DI_IRESIS' => 'Resi tidak ditemukan di iresis. Cek ulang nomornya, atau resi belum pernah diupload.',
        ];

        // Resi yang sudah cancel menurut Jubelio langsung dicatat ke daftar,
        // supaya hasil scan tim resi ikut kelihatan di menu Daftar Cancel Order.
        if ($info['kondisi'] === 'SUDAH_CANCEL') {
            $this->cancel_order_fcd->simpan_scan(
                $noresi,
                'JUBELIO',
                null,
                null,
                $this->data['user']['id_user'] ?? null
            );
            $info['sudah_tercatat'] = true;
        }

        $this->make_ajax_response(200, $pesan[$info['kondisi']], $info);
    }

    /** Catat manual resi yang sudah cancel tapi belum terbaca dari Jubelio. */
    public function simpan_cancel_order()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi tidak boleh kosong');
        }

        $tanggal_cancel = trim((string) $this->input->post('tanggal_cancel'));
        if ($tanggal_cancel !== '') {
            $ts = strtotime($tanggal_cancel);
            if (!$ts) {
                $this->make_ajax_response(400, 'Format tanggal cancel tidak valid');
            }
            $tanggal_cancel = date('Y-m-d H:i:s', $ts);
        } else {
            $tanggal_cancel = null;
        }

        $this->load->model('cancel_order_fcd');

        $hasil = $this->cancel_order_fcd->simpan_scan(
            $noresi,
            'SCAN',
            $tanggal_cancel,
            trim((string) $this->input->post('catatan')),
            $this->data['user']['id_user'] ?? null
        );

        if ($hasil['status'] === 'UPDATE') {
            $this->make_ajax_response(200, 'Resi sudah pernah tercatat — datanya diperbarui.', $hasil);
        }

        $this->make_ajax_response(201, 'Resi berhasil ditandai CANCEL (input manual).', $hasil);
    }

    public function hapus_cancel_order()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(405, INVALID_REQUEST_METHOD);
        }

        $this->load->model('cancel_order_fcd');
        $hasil = $this->cancel_order_fcd->hapus_manual($this->input->post('id_cancel'));

        if (!$hasil['sukses']) {
            $this->make_ajax_response(400, $hasil['pesan']);
        }

        $this->make_ajax_response(200, $hasil['pesan']);
    }

    public function export_excel_cancel_order()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        $this->load->model('cancel_order_fcd');

        $params           = $this->_cancel_order_params();
        $params['search'] = trim((string) $this->input->post('search'));
        $params['order']  = 'tanggal_cancel';
        $params['dir']    = 'desc';

        $data['list_data']      = $this->cancel_order_fcd->get_export_data($params);
        $data['reportrange']    = date('d/m/Y H:i', strtotime($params['start_date']))
            . ' - ' . date('d/m/Y H:i', strtotime($params['end_date']));
        $data['filter_tanggal'] = $params['filter_tanggal'] === 'pesan' ? 'Tanggal Pesanan' : 'Tanggal Cancel';

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Cancel_Order_" . date('Y-m-d_His') . ".xls");

        $this->load->view('template_report/cancel_order_excel', $data);
    }
}
