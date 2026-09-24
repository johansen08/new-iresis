<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Monitoring extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Memuat model yang sama seperti di laporan kurangan picker CS
        $this->load->model('receipt_fcd');
    }

    public function kurangan_picker()
    {
        $data['title'] = 'Status Kurangan Picker';
        $data['message'] = $this->session->flashdata('message');

        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post') {
            $reportrange = $this->input->post('reportrange');
        }

        $data['reportrange'] = $reportrange;
        $this->show($data);
    }

    public function get_kurangan_picker_data()
    {
        // Parameter DataTable
        $reportrange = $this->input->post('reportrange') ?: date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        $order = $this->input->post('order');
        $order_column = null;
        $order_dir = 'DESC';

        $valid_columns = [
            1 => 'dr.sku',
            2 => 'pr.noresi',
            3 => 'm.nama_marketplace',
            4 => 'dr.qty_kurang',
            5 => 'dr.tanggal_selesai_kurangan',
            6 => 'dr.jenis_penyelesaian_kurangan',
            7 => 'dr.note_kurangan',
            8 => 'dr.sku_pengganti',
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']);
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Apply filters
        $this->db->from('tbldetailprintresi dr');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = dr.id_resi', 'inner');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        
        // Hanya yang sudah diproses (punya flag jenis penyelesaian)
        $this->db->where('dr.jenis_penyelesaian_kurangan IS NOT NULL', null, false);
        $this->db->where("dr.tanggal_selesai_kurangan >= '$start_date'", null, false);
        $this->db->where("dr.tanggal_selesai_kurangan <= '$end_date'", null, false);

        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('dr.sku', $search_value);
            $this->db->or_like('pr.noresi', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->or_like('dr.jenis_penyelesaian_kurangan', $search_value);
            $this->db->or_like('dr.sku_pengganti', $search_value);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->select('
            dr.id_detail_resi,
            dr.sku,
            dr.qty_kurang,
            dr.jenis_penyelesaian_kurangan,
            dr.note_kurangan,
            dr.sku_pengganti,
            dr.tanggal_selesai_kurangan,
            pr.noresi,
            m.nama_marketplace
        ');

        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
        } else {
            $this->db->order_by('dr.tanggal_selesai_kurangan', 'DESC');
        }

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

        $data_table = [];
        $row_number = $start + 1;

        foreach ($items as $item) {
            
            // Format Badge Jenis Penyelesaian
            $badge = '';
            if ($item['jenis_penyelesaian_kurangan'] == 'Stock Ready') {
                $badge = '<span class="label label-success">Stock Ready</span>';
            } elseif ($item['jenis_penyelesaian_kurangan'] == 'Minta SJ') {
                $badge = '<span class="label label-warning">Minta SJ</span>';
            } elseif ($item['jenis_penyelesaian_kurangan'] == 'Pergantian Barang') {
                $badge = '<span class="label label-info">Pergantian Barang</span>';
            } else {
                $badge = '<span class="label label-default">'.htmlspecialchars($item['jenis_penyelesaian_kurangan']).'</span>';
            }

            $data_table[] = [
                $row_number++ . '.',
                $item['sku'] ?? '-',
                $item['noresi'] ?? '-',
                $item['nama_marketplace'] ?? '-',
                $item['qty_kurang'] ?? 0,
                !empty($item['tanggal_selesai_kurangan']) ? date('d/m/Y H:i:s', strtotime($item['tanggal_selesai_kurangan'])) : '-',
                $badge,
                htmlspecialchars($item['note_kurangan'] ?? '-'),
                htmlspecialchars($item['sku_pengganti'] ?? '-'),
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data_table
            ]));
    }

    /**
     * Pemenuhan Kirim Harian: berapa resi wajib keluar hari ini dan sampai
     * tahap mana (picker, packer, HO), plus hitungan OT/perbantuan packer.
     * Aturan: docs/PEMENUHAN_KIRIM_HARIAN.md.
     */
    public function pemenuhan_kirim_harian()
    {
        // Hitungan ±1 dtk: lepas kunci session supaya request lain user ini tidak ikut menunggu.
        session_write_close();
        $this->load->model('pemenuhan_kirim_fcd');
        $tanggal = $this->tanggal_pemenuhan($this->input->get('tanggal'));

        $data['title']    = 'Pemenuhan Kirim Harian';
        $data['snapshot'] = $this->pemenuhan_kirim_fcd->snapshot($tanggal);
        $data['tanggal']  = $tanggal;
        $this->show($data, 'monitoring/pemenuhan_kirim_harian');
    }

    /** Data JSON untuk pembaruan otomatis tiap menit dan ganti tanggal. */
    public function pemenuhan_kirim_harian_data()
    {
        session_write_close();
        $this->load->model('pemenuhan_kirim_fcd');
        $tanggal = $this->tanggal_pemenuhan($this->input->get('tanggal'));

        $snapshot = $this->pemenuhan_kirim_fcd->snapshot($tanggal);
        if ($snapshot === NULL) {
            $this->make_ajax_response(500, 'Data gagal dihitung. Coba lagi sebentar lagi.');
        }
        $this->make_ajax_response(200, 'OK', $snapshot);
    }

    /** Daftar resi wajib yang belum keluar, untuk popup "Lihat detail". */
    public function pemenuhan_kirim_harian_detail()
    {
        session_write_close();
        $this->load->model('pemenuhan_kirim_fcd');
        $tanggal = $this->tanggal_pemenuhan($this->input->get('tanggal'));

        $daftar = $this->pemenuhan_kirim_fcd->daftar_belum($tanggal);
        if ($daftar === NULL) {
            $this->make_ajax_response(500, 'Daftar resi gagal dimuat. Coba lagi sebentar lagi.');
        }
        $this->make_ajax_response(200, 'OK', $daftar);
    }

    /**
     * Unduh Excel hasil filter popup. Browser mengirim id resi yang sedang
     * tersaring (urutannya sama dengan di layar); isi baris diambil dari
     * daftar_belum() di server, jadi hanya resi yang memang ada di daftar
     * yang bisa ikut terunduh. Gagal → JSON biasa (dibaca oleh JS).
     */
    public function pemenuhan_kirim_harian_excel()
    {
        session_write_close();
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $this->load->model('pemenuhan_kirim_fcd');
        $tanggal = $this->tanggal_pemenuhan($this->input->post('tanggal'));

        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $this->input->post('ids'))))));
        if (!$ids) {
            $this->make_ajax_response(400, 'Tidak ada resi untuk diunduh.');
        }
        $daftar = $this->pemenuhan_kirim_fcd->daftar_belum($tanggal);
        if ($daftar === NULL) {
            $this->make_ajax_response(500, 'Daftar resi gagal dimuat. Coba lagi sebentar lagi.');
        }
        $per_id = array_column($daftar['rows'], NULL, 'id');
        $rows = [];
        foreach ($ids as $id) {
            if (isset($per_id[$id])) {
                $rows[] = $per_id[$id];
            }
        }
        if (!$rows) {
            $this->make_ajax_response(400, 'Resi yang dipilih sudah tidak ada di daftar belum selesai. Muat ulang lalu coba lagi.');
        }

        $grup  = ['KA' => 'Sisa kemarin', 'TA' => 'Batas kirim MP hari ini', 'TB' => 'TikTok s/d 15.00 (operasional)'];
        $jenis = [1 => '1 Qty', 2 => '>1 Qty', 0 => 'Tanpa rincian SKU'];
        $judul = mb_substr(trim((string) $this->input->post('judul')) ?: 'Resi belum selesai', 0, 60);
        $filter = mb_substr(trim((string) $this->input->post('filter')), 0, 300);

        $xls = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $xls->getActiveSheet();
        $ws->setTitle('Resi');
        $ws->setCellValue('A1', $judul . ' · ' . $daftar['label_tanggal'] . ' · ' . ($daftar['hari_ini'] ? 'data jam ' . $daftar['jam_data'] : 'rekap akhir hari'));
        $ws->setCellValue('A2', ($filter !== '' ? 'Filter: ' . $filter . ' · ' : '') . count($rows) . ' resi');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $kolom = ['No Resi', 'No Pesanan', 'Marketplace', 'Kurir', 'SKU x Qty', 'Rak', 'Total Qty', 'Jenis', 'Kelompok',
                  'Masuk IRESIS', 'Jam Pesan', 'Batas Kirim', 'Posisi', 'Jam Pick', 'Picker', 'Jam Packing'];
        $huruf = range('A', 'P');
        $ws->fromArray($kolom, NULL, 'A4');
        $ws->getStyle('A4:P4')->getFont()->setBold(true);

        $baris = 5;
        foreach ($rows as $r) {
            $posisi = $r['pc'] !== '' ? 'Sudah packing, belum HO' : ($r['pk'] !== '' ? 'Sudah dipick, belum packing' : 'Belum dipick');
            $nilai = [
                $r['r'], $r['p'], $r['mp'], $r['k'],
                implode(', ', array_map(function ($s) { return $s[0] . ' x' . $s[1]; }, $r['sku'])),
                implode(', ', array_unique(array_filter(array_column($r['sku'], 2)))),
                array_sum(array_column($r['sku'], 1)),
                $jenis[$r['j']] ?? '', $grup[$r['g']] ?? $r['g'],
                $r['up'], $r['ps'], $r['bk'], $posisi, $r['pk'] === '-' ? '' : $r['pk'], $r['pn'], $r['pc'],
            ];
            foreach ($nilai as $i => $v) {
                // teks eksplisit: no resi/pesanan angka panjang tidak boleh jadi 1,23E+15
                if ($i === 6) {
                    $ws->setCellValue($huruf[$i] . $baris, (int) $v);
                } else {
                    $ws->setCellValueExplicit($huruf[$i] . $baris, (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            $baris++;
        }
        foreach ($huruf as $c) {
            $ws->getColumnDimension($c)->setAutoSize(true);
        }
        $ws->getColumnDimension('A')->setAutoSize(false)->setWidth(24);   // judul di A1 jangan melebarkan kolom resi
        $ws->freezePane('A5');
        $ws->setAutoFilter('A4:P' . ($baris - 1));

        $nama = preg_replace('/[^A-Za-z0-9]+/', '_', $judul) . '_' . $tanggal . ($daftar['hari_ini'] ? '_' . str_replace(':', '', $daftar['jam_data']) : '') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls))->save('php://output');
        exit;
    }

    /** Tanggal Y-m-d yang sah dan tidak di masa depan; selain itu hari ini. */
    private function tanggal_pemenuhan($masukan)
    {
        $masukan = (string) $masukan;
        $t = DateTime::createFromFormat('!Y-m-d', $masukan);
        if (!$t || $t->format('Y-m-d') !== $masukan || $masukan > date('Y-m-d')) {
            return date('Y-m-d');
        }
        return $masukan;
    }

    public function laporan_pesanan_masuk()
    {
        $data['title'] = 'Laporan Pesanan Masuk';
        
        $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
        if ($this->input->method() == 'post' && $this->input->post('reportrange')) {
            $reportrange = $this->input->post('reportrange');
        }
        $data['reportrange'] = $reportrange;

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Get total orders all marketplace - ensure where clauses are respected
        $this->db->where("created_at >= '$start_date'", null, false);
        $this->db->where("created_at <= '$end_date'", null, false);
        $this->db->from('tblprintresi');
        $total_semua = $this->db->count_all_results();
        
        // Get breakdown per marketplace
        $sql = "
            SELECT 
                CASE 
                    WHEN m.nama_marketplace LIKE '%tiktok%' OR m.nama_marketplace LIKE '%tokopedia%' THEN 'Shop Tokopedia'
                    ELSE m.nama_marketplace 
                END as nama_marketplace,
                MIN(m.id_marketplace) as id_marketplace,
                COUNT(pr.id_printresi) as total_pesanan,
                SUM(CASE 
                    WHEN pr.status_pesanan LIKE '%CANCEL%' 
                         OR (pr.batal IS NOT NULL AND pr.batal != '' AND pr.batal != '0') 
                    THEN 1 ELSE 0 
                END) as total_cancel,
                SUM(agg.total_qty) as total_qty,
                SUM(CASE WHEN agg.unique_skus = 1 AND agg.total_qty = 1 THEN 1 ELSE 0 END) as total_special,
                SUM(CASE WHEN agg.unique_skus = 1 AND agg.total_qty BETWEEN 2 AND 9 THEN 1 ELSE 0 END) as total_1sku_sd9,
                SUM(CASE WHEN agg.unique_skus BETWEEN 2 AND 9 AND agg.total_qty <= 9 THEN 1 ELSE 0 END) as total_2_9sku_sd9,
                SUM(CASE WHEN agg.total_qty > 9 THEN 1 ELSE 0 END) as total_qty_banyak
            FROM tblmarketplace m
            LEFT JOIN tblprintresi pr ON pr.id_marketplace = m.id_marketplace 
                AND pr.created_at >= '$start_date' 
                AND pr.created_at <= '$end_date'
            LEFT JOIN (
                SELECT dr.id_resi, COUNT(DISTINCT dr.sku) as unique_skus, SUM(dr.jumlah) as total_qty
                FROM tbldetailprintresi dr
                INNER JOIN tblprintresi p2 ON p2.id_printresi = dr.id_resi
                WHERE p2.created_at >= '$start_date' AND p2.created_at <= '$end_date'
                GROUP BY dr.id_resi
            ) agg ON agg.id_resi = pr.id_printresi
            LEFT JOIN (
                SELECT DISTINCT drs.id_resi 
                FROM tbldetailprintresi drs
                JOIN tblsku s ON s.id_sku = drs.sku
                INNER JOIN tblprintresi p3 ON p3.id_printresi = drs.id_resi
                WHERE p3.created_at >= '$start_date' AND p3.created_at <= '$end_date'
                AND s.is_special = 1
            ) has_special ON has_special.id_resi = pr.id_printresi
            GROUP BY 
                CASE 
                    WHEN m.nama_marketplace LIKE '%tiktok%' OR m.nama_marketplace LIKE '%tokopedia%' THEN 'Shop Tokopedia'
                    ELSE m.nama_marketplace 
                END
            ORDER BY total_pesanan DESC
        ";
        $data['marketplaces'] = $this->db->query($sql)->result_array();
        $data['total_semua'] = $total_semua;

        if ($this->input->post('action') == 'export') {
            $this->export_laporan_pesanan($data['marketplaces'], $start_date, $end_date);
            return;
        }

        $this->show($data, 'monitoring/laporan_pesanan_masuk');
    }

    private function export_laporan_pesanan($marketplaces, $start, $end)
    {
        // Clear ANY output buffer to ensure clean Excel file
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Pesanan_Masuk_" . date('Ymd_His') . ".xls");

        echo "<table border='1'>";
        echo "<tr><th colspan='8'>Laporan Pesanan Masuk ($start s/d $end)</th></tr>";
        echo "<tr>";
        echo "<th>Marketplace</th>";
        echo "<th>Total Pesanan</th>";
        echo "<th>Pesanan Batal (Cancel)</th>";
        echo "<th>Resi Special</th>";
        echo "<th>1 SKU & QTY S/D 9</th>";
        echo "<th>2-9 SKU & QTY S/D 9</th>";
        echo "<th>Qty Banyak (>9)</th>";
        echo "</tr>";

        foreach ($marketplaces as $m) {
            $total = $m['total_pesanan'] ?: 0;
            $p_cancel = $total > 0 ? round(($m['total_cancel'] / $total) * 100, 1) : 0;
            $p_special = $total > 0 ? round(($m['total_special'] / $total) * 100, 1) : 0;
            $p_sku1 = $total > 0 ? round(($m['total_1sku_sd9'] / $total) * 100, 1) : 0;
            $p_sku29 = $total > 0 ? round(($m['total_2_9sku_sd9'] / $total) * 100, 1) : 0;
            $p_qtybanyak = $total > 0 ? round(($m['total_qty_banyak'] / $total) * 100, 1) : 0;

            echo "<tr>";
            echo "<td>{$m['nama_marketplace']}</td>";
            echo "<td align='center'>" . number_format($total) . "</td>";
            echo "<td align='center'>" . number_format($m['total_cancel']) . " ($p_cancel%)</td>";
            echo "<td align='center'>" . number_format($m['total_special']) . " ($p_special%)</td>";
            echo "<td align='center'>" . number_format($m['total_1sku_sd9']) . " ($p_sku1%)</td>";
            echo "<td align='center'>" . number_format($m['total_2_9sku_sd9']) . " ($p_sku29%)</td>";
            echo "<td align='center'>" . number_format($m['total_qty_banyak']) . " ($p_qtybanyak%)</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    public function get_marketplace_resi_detail()
    {
        $marketplace = $this->input->post('marketplace');
        $indicator = $this->input->post('indicator');
        $reportrange = $this->input->post('reportrange');

        $dates = explode(' - ', $reportrange);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);

        // Mapping Marketplace Name back to logic
        $mp_cond = "m.nama_marketplace = " . $this->db->escape($marketplace);
        if ($marketplace == 'Shop Tokopedia') {
            $mp_cond = "(m.nama_marketplace LIKE '%tiktok%' OR m.nama_marketplace LIKE '%tokopedia%')";
        }

        $this->db->select('pr.noresi, pr.tanggal_bataskirim, pr.status_pesanan, pr.batal, agg.unique_skus as distinct_skus, agg.total_qty');
        $this->db->from('tblprintresi pr');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
        $this->db->join('(
            SELECT id_resi, COUNT(DISTINCT sku) as unique_skus, SUM(jumlah) as total_qty
            FROM tbldetailprintresi
            GROUP BY id_resi
        ) agg', 'agg.id_resi = pr.id_printresi', 'left');

        $this->db->where("pr.created_at >= '$start_date'", null, false);
        $this->db->where("pr.created_at <= '$end_date'", null, false);
        $this->db->where($mp_cond, null, false);

        if ($indicator == 'special') {
            $this->db->where('agg.unique_skus', 1);
            $this->db->where('agg.total_qty', 1);
        } elseif ($indicator == '1sku') {
            $this->db->where('agg.unique_skus', 1);
            $this->db->where('agg.total_qty >=', 2);
            $this->db->where('agg.total_qty <=', 9);
        } elseif ($indicator == '29sku') {
            $this->db->where('agg.unique_skus >=', 2);
            $this->db->where('agg.unique_skus <=', 9);
            $this->db->where('agg.total_qty <=', 9);
        } elseif ($indicator == 'qty_banyak') {
            $this->db->where('agg.total_qty >', 9);
        } elseif ($indicator == 'cancel') {
            $this->db->group_start();
            $this->db->like('pr.status_pesanan', 'CANCEL');
            $this->db->or_where('pr.batal IS NOT NULL AND pr.batal != "" AND pr.batal != "0"', null, false);
            $this->db->group_end();
        }

        $query = $this->db->get();
        $data = $query->result_array();

        // Standardize status_pesanan for display
        foreach ($data as &$r) {
            if ($r['batal'] && $r['batal'] != '0') {
                $r['status_pesanan'] = 'CANCELED (Manual)';
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => $data
            ]));
    }
}
