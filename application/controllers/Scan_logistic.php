<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scan_logistic extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scan_logistic_fcd');
    }

    public function index()
    {
        $data['title'] = 'Scan HO + NDD';
        $data['total_scan'] = $this->scan_logistic_fcd->get_total_scan_today($this->data['user']['id_user'], 'HO');
        $data['total_scan_ndd'] = $this->scan_logistic_fcd->get_total_scan_today($this->data['user']['id_user'], 'NDD');
        $data['nama_komputer'] = $this->data['user']['nama_komputer'];
        
        $this->show($data, 'scan_logistic/scan_view');
    }

    public function save()
    {
        // Penanda waktu sisi server. Sampai sekarang satu-satunya angka yang kita
        // punya adalah bolak-balik AJAX dari browser, yang mencampur jaringan,
        // antrean Apache, dan PHP jadi satu angka -- lihat catatan di scan_view.php.
        // $t_masuk diambil di baris pertama method ini, jadi selisihnya terhadap
        // REQUEST_TIME_FLOAT adalah ongkos bootstrap MY_Controller (migrasi DDL +
        // rebuild pohon menu) yang jalan sebelum kode scan tersentuh sama sekali.
        $t_masuk = microtime(true);

        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, "Metode request tidak valid");
        }

        $noresi = $this->input->post('noresi');
        $is_ndd = $this->input->post('is_ndd');
        $type = ($is_ndd === 'true') ? 'NDD' : 'HO';

        $save = $this->scan_logistic_fcd->save_scan($noresi, $this->data['user'], $type);

        if (isset($save['error'])) {
            // Kode asli dari model diteruskan apa adanya (404/400/409/500) dan
            // EXCEPTION_CODE dikirim rata di response.data -- sebelumnya
            // ter-bungkus dua lapis sehingga sisi klien harus menebak-nebak.
            $code = isset($save['code']) ? $save['code'] : 400;
            $data = isset($save['data']) ? $save['data'] : [];
            $this->make_ajax_response($code, $save['message'], $this->tempel_waktu($data, $t_masuk));
        }

        if (isset($save['affected_rows']) && $save['affected_rows'] > 0) {
            $msg = isset($save['message']) ? $save['message'] : "Data berhasil disimpan";
            $this->make_ajax_response(201, $msg, $this->tempel_waktu([
                'type'         => $save['type'],
                'ho_inserted'  => !empty($save['ho_inserted']),
                'ndd_inserted' => !empty($save['ndd_inserted']),
            ], $t_masuk));
        }

        $this->make_ajax_response(200, "Tidak ada data yang disimpan", $this->tempel_waktu([], $t_masuk));
    }

    /**
     * Menyisipkan rincian waktu server ke payload response scan.
     *
     *   srv_ms  = seluruh waktu PHP, dari request masuk sampai balasan disusun
     *   boot_ms = bagian dari srv_ms yang habis SEBELUM Scan_logistic::save()
     *             mulai bekerja (konstruktor MY_Controller)
     *
     * Selisih srv_ms terhadap angka yang diukur browser adalah jaringan +
     * antrean Apache. Sengaja ditaruh di controller ini saja, bukan di
     * make_ajax_response, supaya menu lain tidak ikut berubah.
     */
    private function tempel_waktu(array $data, $t_masuk)
    {
        $t_awal = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : $t_masuk;
        $now    = microtime(true);

        $data['srv_ms']  = (int) round(($now - $t_awal) * 1000);
        $data['boot_ms'] = (int) round(($t_masuk - $t_awal) * 1000);

        return $data;
    }

    public function report()
    {
        $data['title'] = 'Laporan Paket NDD';
        $reportrange = $this->input->post('reportrange');
        if (empty($reportrange)) {
            $reportrange = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d H:i:s');
        }
        $data['reportrange'] = $reportrange;
        
        $this->show($data, 'scan_logistic/report_view');
    }

    public function get_data()
    {
        $type = 'NDD';
        $reportrange = $this->input->post('reportrange');
        
        // Validation for reportrange
        if (empty($reportrange) || strpos($reportrange, ' - ') === false) {
            $start_date = date('Y-m-d 00:00:00');
            $end_date = date('Y-m-d H:i:s');
        } else {
            $dates = explode(' - ', $reportrange);
            $start_date = $dates[0];
            $end_date = $dates[1];
        }
        
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['draw'] = intval($this->input->post('draw'));
        $data['start'] = intval($this->input->post('start'));
        $data['length'] = intval($this->input->post('length'));
        $search = $this->input->post('search');
        $data['search'] = isset($search['value']) ? $search['value'] : '';

        $order = $this->input->post('order');
        $col = 0; $dir = 'desc';
        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }
        
        $valid_columns = [
            0 => null,
            1 => 't2.noresi',
            2 => 't4.nama_marketplace',
            3 => 't5.nama_kurir',
            4 => 't.tanggal_scan',
            5 => 't3.nama_pegawai'
        ];
        
        $data['order'] = $valid_columns[$col] ?? 't.tanggal_scan';
        $data['dir'] = $dir;

        $list = $this->scan_logistic_fcd->get_report_data($data, $type);
        $total = $this->scan_logistic_fcd->get_total_report_data($data, $type);

        $result = [];
        $i = $data['start'] + 1;
        foreach ($list->result() as $row) {
            $result[] = [
                $i++ . '.',
                $row->noresi,
                $row->nama_marketplace,
                $row->nama_kurir,
                $row->tanggal_scan,
                $row->nama_pegawai
            ];
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        echo json_encode([
            "draw" => $data['draw'],
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $result
        ]);
    }

    public function export_excel()
    {
        ini_set('memory_limit', '-1');
        $type = 'NDD';
        $reportrange = $this->input->get('reportrange');

        if (empty($reportrange) || strpos($reportrange, ' - ') === false) {
            $start_date = date('Y-m-d 00:00:00');
            $end_date = date('Y-m-d H:i:s');
        } else {
            $dates = explode(' - ', $reportrange);
            $start_date = $dates[0];
            $end_date = $dates[1];
        }
        
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['type'] = $type;
        $data['list_data'] = $this->scan_logistic_fcd->get_report_data($data, $type)->result_array();

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Paket_NDD_" . date('Ymd_His') . ".xls");

        $this->load->view('scan_logistic/export_excel', $data);
    }
}
