<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Resi_team extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('resi_team_fcd');
    }

    public function selisih_paket()
    {
        $data['title'] = 'Selisih Paket';
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_selisih_data()
    {
        $start_date = $this->input->post('start_date') ?: date('Y-m-d 00:00:00');
        $end_date = $this->input->post('end_date') ?: date('Y-m-d 23:59:59');

        $top_stats = $this->resi_team_fcd->get_top_stats();
        $pool1 = $this->resi_team_fcd->get_indicators_by_pool('resi_to_picker', $start_date, $end_date);
        $pool2 = $this->resi_team_fcd->get_indicators_by_pool('picker_to_packer', $start_date, $end_date);
        $pool3 = $this->resi_team_fcd->get_indicators_by_pool('tempo_hari_ini', $start_date, $end_date);

        // Add lists of special SKUs
        $pool1['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('resi_to_picker', $start_date, $end_date);
        $pool2['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('picker_to_packer', $start_date, $end_date);
        $pool3['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('tempo_hari_ini', $start_date, $end_date);

        $response = [
            'top_stats' => $top_stats,
            'pool1' => $pool1,
            'pool2' => $pool2,
            'pool3' => $pool3,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->make_ajax_response(200, 'Success', $response);
    }

    public function export_excel()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-d 00:00:00');
        $end_date = $this->input->get('end_date') ?: date('Y-m-d 23:59:59');

        $pool1 = $this->resi_team_fcd->get_indicators_by_pool('resi_to_picker', $start_date, $end_date);
        $pool2 = $this->resi_team_fcd->get_indicators_by_pool('picker_to_packer', $start_date, $end_date);
        $pool3 = $this->resi_team_fcd->get_indicators_by_pool('tempo_hari_ini', $start_date, $end_date);

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Selisih_Paket_" . date('Ymd_His') . ".xls");

        echo "<table border='1'>";
        echo "<tr><th colspan='4'>LAPORAN SELISIH PAKET</th></tr>";
        echo "<tr><th colspan='4'>Periode: $start_date - $end_date</th></tr>";
        echo "<tr><th>KATEGORI</th><th>INDIKATOR</th><th>JUMLAH</th><th>PERSEN (%)</th></tr>";

        $categories = [
            ['label' => 'RESI KE PICKER', 'data' => $pool1],
            ['label' => 'PICKER KE PACKER', 'data' => $pool2],
            ['label' => 'JATOH TEMPO HARI INI', 'data' => $pool3]
        ];

        foreach ($categories as $cat) {
            $total = $cat['data']['total_resi'] ?: 1;
            $indicators = [
                ['label' => 'SKU SPECIAL', 'val' => $cat['data']['sku_special']],
                ['label' => 'RESI SATUAN', 'val' => $cat['data']['resi_satuan']],
                ['label' => 'RESI < 10 SKU', 'val' => $cat['data']['resi_kurang_10_sku']],
                ['label' => 'RESI > 10 SKU', 'val' => $cat['data']['resi_lebih_10_sku']],
                ['label' => 'RESI QTY BANYAK', 'val' => $cat['data']['resi_qty_banyak']]
            ];

            foreach ($indicators as $idx => $ind) {
                echo "<tr>";
                if ($idx === 0) echo "<td rowspan='5'>" . $cat['label'] . "</td>";
                echo "<td>" . $ind['label'] . "</td>";
                echo "<td>" . ($ind['val'] ?: 0) . "</td>";
                echo "<td>" . number_format((($ind['val'] ?: 0) / $total) * 100, 1) . "%</td>";
                echo "</tr>";
            }
            echo "<tr><th colspan='2'>GRAND TOTAL " . $cat['label'] . "</th><th colspan='2'>" . $cat['data']['total_resi'] . "</th></tr>";
        }
        echo "</table>";
    }

    public function batas_kirim_paket()
    {
        $data['title'] = 'Batas Kirim Paket';
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_batas_kirim_data()
    {
        $response = [];
        $response['days'] = [];

        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $label = ($i == 0) ? 'HARI INI' : (($i == 1) ? 'BESOK' : (($i == 2) ? 'LUSA' : date('d M', strtotime($date))));
            
            $pool_data = $this->resi_team_fcd->get_indicators_by_pool('deadline', $date, $date);
            $pool_data['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('deadline', $date, $date);
            $pool_data['date_label'] = $label;
            $pool_data['date'] = $date;

            $response['days'][] = $pool_data;
        }

        $response['top_stats'] = $this->resi_team_fcd->get_top_stats();
        $response['processed_at'] = date('Y-m-d H:i:s');
        $this->make_ajax_response(200, 'Success', $response);
    }

    public function export_batas_kirim_excel()
    {
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Batas_Kirim_Paket_" . date('Ymd_His') . ".xls");

        echo "<table border='1'>";
        echo "<tr><th colspan='4'>LAPORAN BATAS KIRIM PAKET (5 HARI KEDEPAN)</th></tr>";
        echo "<tr><th colspan='4'>Exported at: " . date('Y-m-d H:i:s') . "</th></tr>";
        echo "<tr><th>TANGGAL DEADLINE</th><th>INDIKATOR</th><th>JUMLAH</th><th>PERSEN (%)</th></tr>";

        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $label = ($i == 0) ? 'HARI INI' : (($i == 1) ? 'BESOK' : (($i == 2) ? 'LUSA' : date('d M', strtotime($date))));
            
            $data = $this->resi_team_fcd->get_indicators_by_pool('deadline', $date, $date);
            $total = $data['total_resi'] ?: 1;

            $indicators = [
                ['label' => 'SKU SPECIAL', 'val' => $data['sku_special']],
                ['label' => 'RESI QTY BANYAK', 'val' => $data['resi_qty_banyak']],
                ['label' => 'RESI > 10 SKU', 'val' => $data['resi_lebih_10_sku']],
                ['label' => 'RESI 1 SKU', 'val' => $data['resi_satuan']],
                ['label' => 'RESI 2-10 SKU', 'val' => $data['resi_kurang_10_sku']]
            ];

            foreach ($indicators as $idx => $ind) {
                echo "<tr>";
                if ($idx === 0) echo "<td rowspan='5'>" . $label . " (" . $date . ")</td>";
                echo "<td>" . $ind['label'] . "</td>";
                echo "<td>" . ($ind['val'] ?: 0) . "</td>";
                echo "<td>" . number_format((($ind['val'] ?: 0) / $total) * 100, 1) . "%</td>";
                echo "</tr>";
            }
            echo "<tr><th colspan='2'>GRAND TOTAL " . $label . "</th><th colspan='2'>" . $data['total_resi'] . "</th></tr>";
        }
        echo "</table>";
    }

    public function paket_on_progress()
    {
        $data['title'] = 'Paket On Progress';
        $data['message'] = $this->session->flashdata('message');

        $this->show($data);
    }

    public function get_on_progress_data()
    {
        $response = [];
        $response['days'] = [];

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+4 days'));
        
        // Fetch indicators for all 5 days in ONE query
        $bulk_data = $this->resi_team_fcd->get_on_progress_bulk_data($start_date, $end_date);
        $indexed_bulk = [];
        foreach ($bulk_data as $row) {
            $indexed_bulk[$row['deadline_date']] = $row;
        }

        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $label = ($i == 0) ? 'HARI INI' : (($i == 1) ? 'BESOK' : (($i == 2) ? 'LUSA' : date('d M', strtotime($date))));
            
            $day_bulk = isset($indexed_bulk[$date]) ? $indexed_bulk[$date] : null;

            if ($day_bulk) {
                $p1 = [
                    'total_resi' => $day_bulk['picked_total'],
                    'sku_special' => $day_bulk['picked_special'],
                    'resi_qty_banyak' => $day_bulk['picked_qty_banyak'],
                    'resi_lebih_10_sku' => $day_bulk['picked_lebih_10_sku'],
                    'resi_satuan' => $day_bulk['picked_satuan'],
                    'resi_kurang_10_sku' => $day_bulk['picked_kurang_10_sku']
                ];
                $p2 = [
                    'total_resi' => $day_bulk['packed_total'],
                    'sku_special' => $day_bulk['packed_special'],
                    'resi_qty_banyak' => $day_bulk['packed_qty_banyak'],
                    'resi_lebih_10_sku' => $day_bulk['packed_lebih_10_sku'],
                    'resi_satuan' => $day_bulk['packed_satuan'],
                    'resi_kurang_10_sku' => $day_bulk['packed_kurang_10_sku']
                ];
                $p3 = [
                    'total_resi' => $day_bulk['ho_total'],
                    'sku_special' => $day_bulk['ho_special'],
                    'resi_qty_banyak' => $day_bulk['ho_qty_banyak'],
                    'resi_lebih_10_sku' => $day_bulk['ho_lebih_10_sku'],
                    'resi_satuan' => $day_bulk['ho_satuan'],
                    'resi_kurang_10_sku' => $day_bulk['ho_kurang_10_sku']
                ];
            } else {
                $empty = ['total_resi' => 0, 'sku_special' => 0, 'resi_qty_banyak' => 0, 'resi_lebih_10_sku' => 0, 'resi_satuan' => 0, 'resi_kurang_10_sku' => 0];
                $p1 = $p2 = $p3 = $empty;
            }

            // Fetch special SKU lists (still separate for now, but these are faster due to index optimization)
            $p1['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('deadline_picked', $date, $date);
            $p2['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('deadline_packed', $date, $date);
            $p3['special_sku_list'] = $this->resi_team_fcd->get_special_sku_list('deadline_ho', $date, $date);

            $response['days'][] = [
                'date' => $date,
                'date_label' => $label,
                'p1' => $p1,
                'p2' => $p2,
                'p3' => $p3
            ];
        }

        $response['top_stats'] = $this->resi_team_fcd->get_top_stats();
        $response['processed_at'] = date('Y-m-d H:i:s');
        $this->make_ajax_response(200, 'Success', $response);
    }

    public function export_on_progress_excel()
    {
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Paket_On_Progress_" . date('Ymd_His') . ".xls");

        echo "<table border='1'>";
        echo "<tr><th colspan='5'>LAPORAN PAKET ON PROGRESS (5 HARI KEDEPAN)</th></tr>";
        echo "<tr><th colspan='5'>Exported at: " . date('Y-m-d H:i:s') . "</th></tr>";
        echo "<tr><th>DEADLINE</th><th>KATEGORI</th><th>INDIKATOR</th><th>JUMLAH</th><th>PERSEN (%)</th></tr>";

        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $label = ($i == 0) ? 'HARI INI' : (($i == 1) ? 'BESOK' : (($i == 2) ? 'LUSA' : date('d M', strtotime($date))));
            
            $pools = [
                ['label' => 'RESI KE PICKER', 'type' => 'deadline_picked'],
                ['label' => 'PICKER KE PACKER', 'type' => 'deadline_packed'],
                ['label' => 'PACKER KE HO', 'type' => 'deadline_ho']
            ];

            foreach ($pools as $pool_idx => $pool) {
                $data = $this->resi_team_fcd->get_indicators_by_pool($pool['type'], $date, $date);
                $total = $data['total_resi'] ?: 1;

                $indicators = [
                    ['label' => 'SKU SPECIAL', 'val' => $data['sku_special']],
                    ['label' => 'RESI QTY BANYAK', 'val' => $data['resi_qty_banyak']],
                    ['label' => 'RESI > 10 SKU', 'val' => $data['resi_lebih_10_sku']],
                    ['label' => 'RESI 1 SKU', 'val' => $data['resi_satuan']],
                    ['label' => 'RESI 2-10 SKU', 'val' => $data['resi_kurang_10_sku']]
                ];

                foreach ($indicators as $idx => $ind) {
                    echo "<tr>";
                    if ($pool_idx === 0 && $idx === 0) echo "<td rowspan='18'>" . $label . " (" . $date . ")</td>";
                    if ($idx === 0) echo "<td rowspan='6'>" . $pool['label'] . "</td>";
                    echo "<td>" . $ind['label'] . "</td>";
                    echo "<td>" . ($ind['val'] ?: 0) . "</td>";
                    echo "<td>" . number_format((($ind['val'] ?: 0) / $total) * 100, 1) . "%</td>";
                    echo "</tr>";
                }
                echo "<tr><th colspan='1'>GRAND TOTAL " . $pool['label'] . "</th><th>" . $data['total_resi'] . "</th><th>100%</th></tr>";
            }
        }
        echo "</table>";
    }
}
