<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_return extends MY_Controller
{
    // Hak klik serah terima: Barang Diambil = tim packer (4); Cetak Serah Terima + Selesai = tim restok (6).
    // Admin (1, 2) boleh semuanya. Dicek di server; tombol di layar hanya cermin dari ini.
    const ROLE_DIAMBIL = array(1, 2, 4);
    const ROLE_RESTOK = array(1, 2, 6);

    private function punya_peran($daftar)
    {
        $peran = isset($this->data['user']['hakakses']) ? (int) $this->data['user']['hakakses'] : 0;
        return in_array($peran, $daftar, TRUE);
    }

    function __construct()
    {
        parent::__construct();
        $this->load->model('qc_return_fcd');
        $this->load->model('Notification');
    }

    public function index()
    {
        $data['message'] = $this->session->flashdata('message');
        $data['bisa_diambil'] = $this->punya_peran(self::ROLE_DIAMBIL);
        $data['bisa_restok'] = $this->punya_peran(self::ROLE_RESTOK);
        $this->show($data);
    }

    public function get_data()
    {
        $filters = array(
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'status' => $this->input->post('status'),
            'kondisi' => $this->input->post('kondisi')
        );

        $list = $this->qc_return_fcd->get_data($filters);
        $data = array();
        $no = 1;
        foreach ($list->result() as $row) {
            $action = '';
            // Change 'role' to 'hakakses' as per session data in Login controller
            $user_role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
            if ($row->status == 'PENDING' || $user_role == '1') { // Admin or Pending can be deleted
                $action = '<button class="btn btn-danger btn-xs" onclick="deleteData(' . $row->id_pengembalian . ')"><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                $no++,
                $row->tanggal,
                $row->sku,
                $row->qty . ($row->qty_kurang > 0 ? " <br><small class='text-danger'>Kurang: " . $row->qty_kurang . "</small>" : ""),
                $row->no_rak,
                $row->kondisi . ($row->keterangan_reject ? ' ('. $row->keterangan_reject .')' : ''),
                $row->submitter_name,
                $row->status,
                $row->acc_name ?: '-',
                $action
            );
        }

        // Ensure no output buffer content interferes with JSON
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array("data" => $data));
        exit();
    }

    public function add()
    {
        $this->show();
    }

    public function save()
    {
        $data = array(
            'tanggal' => date('Y-m-d'),
            'sku' => $this->input->post('sku'),
            'qty' => $this->input->post('qty'),
            'qty_kurang' => $this->input->post('ketersediaan') == 'Ada yang kurang' ? $this->input->post('qty_kurang') : 0,
            'no_rak' => $this->input->post('no_rak'),
            'kondisi' => $this->input->post('kondisi'),
            'keterangan_reject' => $this->input->post('keterangan_reject'),
            'submit_by' => $this->data['user']['id_user'],
            'status' => 'PENDING'
        );

        if ($this->qc_return_fcd->save($data)) {
            $this->set_message('Success', 'Data pengembalian berhasil diajukan', 'information');
        } else {
            $this->set_message('Error', 'Gagal menyimpan data', 'error');
        }

        redirect('qc_return');
    }

    public function approval()
    {
        // Only allow Tim Support (Role matches name or ID) or Admin
        // This is a simple check, usually handled by MY_Controller or Access filter
        $data['message'] = $this->session->flashdata('message');
        $this->show($data);
    }

    public function get_data_approval()
    {
        $filters = array('status' => 'PENDING');
        $list = $this->qc_return_fcd->get_data($filters);
        $data = array();
        $no = 1;
        foreach ($list->result() as $row) {
            $action = '<button class="btn btn-success btn-xs" onclick="approve(' . $row->id_pengembalian . ')" title="Terima"><i class="fa fa-check"></i> Acc</button> ';
            $action .= '<button class="btn btn-danger btn-xs" onclick="reject(' . $row->id_pengembalian . ')" title="Tolak"><i class="fa fa-times"></i> Tolak</button> ';
            $action .= '<button class="btn btn-warning btn-xs" onclick="giveaway(' . $row->id_pengembalian . ')" title="Giveaway"><i class="fa fa-gift"></i> Giveaway</button> ';
            $action .= '<button class="btn btn-info btn-xs" onclick="tidakAda(' . $row->id_pengembalian . ')" title="Barang Tidak Ada"><i class="fa fa-search-minus"></i> Tidak Ada</button>';
            
            $data[] = array(
                $no++,
                $row->tanggal,
                $row->sku,
                $row->qty . ($row->qty_kurang > 0 ? " <br><small class='text-danger'>Kurang: " . $row->qty_kurang . "</small>" : ""),
                $row->no_rak,
                $row->kondisi . ($row->keterangan_reject ? ' ('. $row->keterangan_reject .')' : ''),
                $row->submitter_name,
                $action
            );
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(array("data" => $data));
        exit();
    }

    public function do_approve()
    {
        $id = $this->input->post('id');
        $status = $this->input->post('status'); // APPROVED or REJECTED
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->approve($id, $this->data['user']['id_user'], $status)) {
            // KIRIM NOTIFIKASI KE TIM PURCHASING
            $pesan = "Update status pengembalian QC: " . $status . " untuk ID #" . $id;
            $this->Notification->send($pesan, 'TIM PURCHASING', 'Update QC Return');

            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }
    public function do_approve_all()
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->approve_all($this->data['user']['id_user'])) {
            // KIRIM NOTIFIKASI KE TIM PURCHASING
            $this->Notification->send("Seluruh data pengembalian QC PENDING telah disetujui.", 'TIM PURCHASING', 'Bulk Approval QC');

            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }

    // Saran SKU saat mengetik (dropdown). Balasan: {code, message, data: {results: [{id_sku, nama_sku, no_rak}, ...]}}
    public function get_sku_suggestions()
    {
        $search = trim((string) $this->input->get('q'));
        $suggestions = $search === '' ? array() : $this->qc_return_fcd->get_sku_suggestions($search);
        $this->make_ajax_response(200, 'ok', array('results' => $suggestions));
    }

    // Tebak satu SKU dari teks (Enter / pindah field). Balasan data: lihat Qc_return_fcd::cari_sku()
    public function get_sku_detail()
    {
        $hasil = $this->qc_return_fcd->cari_sku($this->input->post('sku'));
        $this->make_ajax_response(200, 'ok', $hasil);
    }

    public function export_to_excel()
    {
        ini_set('memory_limit', '-1');
        
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');
        $kondisi = $this->input->get('kondisi');

        if (empty($start_date)) $start_date = date('Y-m-d');
        if (empty($end_date)) $end_date = date('Y-m-d');

        $data['list_data'] = $this->qc_return_fcd->get_data_export($start_date, $end_date, $status, $kondisi)->result_array();
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Pengembalian_QC_" . $start_date . "_to_" . $end_date . ".xls");

        $this->load->view('template_report/qc_return_report', $data);
    }

    // Halaman cetak A4 serah terima QC -> Restok (tanpa REJECT), dibuka di tab baru
    public function serah_terima()
    {
        if (!$this->punya_peran(self::ROLE_RESTOK)) {
            exit('Cetak serah terima hanya untuk tim Restok.');
        }
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $re = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($re, (string) $start_date)) $start_date = date('Y-m-d');
        if (!preg_match($re, (string) $end_date)) $end_date = $start_date;

        // "NAMA - ROLE - NOABSEN" -> "NOABSEN - NAMA - ROLE"
        $penyerah = array();
        foreach ($this->qc_return_fcd->get_penyerah_serah_terima($start_date, $end_date) as $nama) {
            $p = array_map('trim', explode(' - ', (string) $nama));
            $penyerah[] = count($p) >= 3 ? end($p) . ' - ' . $p[0] . ' - ' . $p[1] : (string) $nama;
        }

        $rows = $this->qc_return_fcd->get_serah_terima($start_date, $end_date);
        $waktu_cetak = date('Y-m-d H:i:s');

        // Riwayat: setiap halaman cetak dibuka dicatat, lengkap dengan snapshot isinya
        $this->qc_return_fcd->simpan_riwayat_cetak(array(
            'tanggal_awal' => $start_date,
            'tanggal_akhir' => $end_date,
            'jumlah_sku' => count($rows),
            'total_qty' => (int) array_sum(array_column($rows, 'qty')),
            'penyerah' => implode(', ', $penyerah),
            'detail' => json_encode($rows),
            'dicetak_oleh' => $this->data['user']['id_user'],
            'dicetak_at' => $waktu_cetak
        ));

        $data['rows'] = $rows;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['penyerah'] = $penyerah;
        $data['waktu_cetak'] = $waktu_cetak;

        $this->load->view('qc_return/serah_terima', $data);
    }

    // Tabel kesalahan picker di halaman Pengembalian Barang (rentang tanggal sama dengan tabel utama)
    public function kesalahan_picker()
    {
        $mulai = $this->input->post('start_date');
        $akhir = $this->input->post('end_date');
        $tgl = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($tgl, (string) $mulai) || !preg_match($tgl, (string) $akhir)) {
            $mulai = $akhir = date('Y-m-d');
        }
        $this->make_ajax_response(200, 'ok', $this->qc_return_fcd->get_kesalahan_picker($mulai, $akhir)->result_array());
    }

    // Tombol "Barang Diambil" di tabel Kesalahan Picker: baris terpilih jadi BARANG DIAMBIL RESTOK
    public function kesalahan_picker_diambil()
    {
        if (!$this->punya_peran(self::ROLE_DIAMBIL)) {
            $this->make_ajax_response(403, 'Barang Diambil hanya bisa dilakukan tim Packer');
        }
        $ids = $this->input->post('ids');
        if (!is_array($ids) || !$ids) {
            $this->make_ajax_response(400, 'Pilih minimal satu baris');
        }
        $jumlah = $this->qc_return_fcd->tandai_kesalahan_diambil($ids, $this->data['user']['id_user']);
        $this->make_ajax_response(200, $jumlah . ' baris ditandai Barang Diambil Restok', array('jumlah' => $jumlah));
    }

    // Daftar riwayat cetak serah terima (untuk modal di halaman laporan)
    public function riwayat_cetak()
    {
        $this->make_ajax_response(200, 'ok', $this->qc_return_fcd->get_riwayat_cetak());
    }

    // Daftar pengerjaan harian (tanggal data, status ambil/selesai, riwayat cetak)
    public function pengerjaan_harian()
    {
        $this->make_ajax_response(200, 'ok', $this->qc_return_fcd->get_pengerjaan_harian(30));
    }

    // QC klik saat keranjang tanggal tsb diambil tim Restok
    public function barang_diambil()
    {
        if (!$this->punya_peran(self::ROLE_DIAMBIL)) {
            $this->make_ajax_response(403, 'Barang Diambil hanya bisa dilakukan tim Packer');
        }
        $tanggal = $this->input->post('tanggal');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $tanggal)) {
            $this->make_ajax_response(400, 'Tanggal tidak valid');
        }
        if (!$this->qc_return_fcd->tandai_diambil($tanggal, $this->data['user']['id_user'])) {
            $this->make_ajax_response(409, 'Barang tanggal ini sudah pernah ditandai diambil');
        }
        $this->make_ajax_response(200, 'Barang ditandai diambil');
    }

    // Restok selesai mengerjakan keranjang tanggal tsb (hanya setelah diambil)
    public function pengerjaan_selesai()
    {
        if (!$this->punya_peran(self::ROLE_RESTOK)) {
            $this->make_ajax_response(403, 'Selesai hanya bisa dilakukan tim Restok');
        }
        $tanggal = $this->input->post('tanggal');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $tanggal)) {
            $this->make_ajax_response(400, 'Tanggal tidak valid');
        }
        if (!$this->qc_return_fcd->tandai_selesai($tanggal, $this->data['user']['id_user'])) {
            $this->make_ajax_response(409, 'Belum diambil atau sudah pernah diselesaikan');
        }
        $this->make_ajax_response(200, 'Pengerjaan ditandai selesai');
    }

    public function delete()
    {
        $id = $this->input->post('id');
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        if ($this->qc_return_fcd->delete_data($id)) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        exit();
    }
}
