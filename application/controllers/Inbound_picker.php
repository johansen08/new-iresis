<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inbound_picker extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('picking_fcd');
        $this->load->model('receipt_fcd');
        $this->load->model('kpi_fcd');
    }

    public function index()
    {
        $data['title'] = 'Inbound Picker & Packer';
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        
        // Fetch Packer List from tbluser
        $this->load->model('user_fcd');
        $data['list_packer'] = $this->user_fcd->get_user()->result_array();
        
        // Ambil total scan hari ini untuk user ini
        $total_scan_result = $this->picking_fcd->get_total_scan_user($this->data['user']['id_user'])->row();
        $data['total_scan'] = $total_scan_result ? $total_scan_result->total_scan : 0;

        $this->show($data, 'inbound_picker/scan');
    }

    public function save_scan()
    {
        if ($this->input->method() == 'get') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = strtoupper(trim($this->input->post('noresi')));
        $id_picker = $this->input->post('id_picker');
        $id_packer = $this->input->post('id_packer');

        if (empty($noresi) || empty($id_picker) || empty($id_packer)) {
            $this->make_ajax_response(400, 'Nomor Resi, Picker, dan Packer harus diisi.');
        }

        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row();
        
        if (empty($receipt)) {
            $this->make_ajax_response(404, 'Nomor resi tidak ditemukan di sistem. Harap scan di menu Receipt dulu.');
        }

        $this->db->trans_start();

        // 1. Save to Picker Table (tblresiambilbarang)
        $picking = [
            'noresi' => $noresi,
            'yangambil_pegawai' => $id_picker,
            'pending' => '',
            // Picking_fcd::save() tidak mencatat penolakan cancel untuk sumber ini;
            // dicatat di bawah setelah rollback transaksi luar (docs/PAKET_CANCEL.md §7.1 #2).
            'sumber_scan' => 'INBOUND',
        ];

        $normal_picker_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL_PICKER');
        $picker_status_id = $normal_picker_status_id;

        // Cari user picker berdasarkan id_pegawai
        $picker_user = $this->db->get_where('tbluser', ['id_pegawai' => $id_picker, 'isactive' => 1])->row();
        if ($picker_user) {
            $picker_status = $this->kpi_fcd->get_user_status_performa($picker_user->id_user);
            if ($picker_status) {
                $picker_status_id = $picker_status->id_statusperforma;
            }
        }

        if ($picker_status_id) {
            $picking['status_performa_id'] = $picker_status_id;
        }

        $save_picker = $this->picking_fcd->save($picking, $this->data['user']);

        if (isset($save_picker['error'])) {
            $this->db->trans_rollback();
            // Di meja inbound paket fisik sudah diambil picker -> paket cancel DITEMUKAN.
            if (($save_picker['data']['EXCEPTION_CODE'] ?? '') === 'ORDER_CANCELED') {
                $this->load->model('cancel_paket_fcd');
                $this->cancel_paket_fcd->catat_tolak($receipt, 'INBOUND', $this->data['user'], [
                    'barang_sudah_diambil' => true,
                ]);
            }
            $this->make_ajax_response($save_picker['code'], $save_picker['message'] . ' (Picker)');
        }

        // 2. Save to Packer Table (tblpacking)
        $this->load->model('packer_fcd');
        
        // Correct Packer Status Name is 'NORMAL'
        $normal_packer_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL');
        
        // Cari status performa aktif untuk packer terpilih
        $packer_status = $this->kpi_fcd->get_user_status_performa($id_packer);
        $packer_status_id = $packer_status ? $packer_status->id_statusperforma : $normal_packer_status_id;
        
        $packer_save_data = [
            'id_resi' => $receipt->id_printresi,
            'tanggal_packing' => date('Y-m-d H:i:s'),
            'packer_pegawai' => $id_packer, // SELECTED PACKER
            'keterangan' => 'INBOUND_PICKER_PACKER_SCAN',
            'status_performa_id' => $packer_status_id
        ];

        // Check if already packed
        $packer_exist = $this->db->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])->row();
        if ($packer_exist) {
            $this->db->trans_rollback();
            $this->make_ajax_response(400, 'Nomor resi sudah di-packing (Double Scan).');
        }

        $this->db->insert('tblpacking', $packer_save_data);
        
        // Log KPI for Packer - Use correct public method
        $this->kpi_fcd->log_transaksi_harian($id_packer, $packer_status_id, 'PACKING', 1);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->make_ajax_response(500, 'Gagal menyimpan data transaksi gabungan.');
        }

        // Get details for response
        $resi_detail = $this->receipt_fcd->get_detail($noresi)->row_array();
        $items = $this->receipt_fcd->get_detail_items($noresi)->result_array();
        
        $this->make_ajax_response(201, SUCCESS_SAVE_DATA, [
            'resi' => $resi_detail,
            'items' => $items,
            'total_scan' => $this->picking_fcd->get_total_scan_user($this->data['user']['id_user'])->row()->total_scan
        ]);
    }

    public function sync_resi()
    {
        // Sinkronisasi bisa menyentuh ribuan resi sekaligus (±4.500/hari/picker),
        // jadi jangan sampai dipotong batas eksekusi default.
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $id_picker = $this->input->get('id_picker');
        $id_packer = $this->input->get('id_packer');

        if (empty($id_picker) || empty($id_packer)) {
            $this->make_ajax_response(400, 'Harap pilih Picker dan Packer terlebih dahulu.');
        }

        // Cari semua resi yang sudah di-scan oleh picker ini HARI INI
        // Tapi BELUM di-scan oleh packer mana pun.
        //
        // Filter tanggal memakai rentang (>= awal hari, < awal hari besok), BUKAN
        // DATE(tanggal_resiambilbarang) = CURDATE(). Membungkus kolom dengan DATE()
        // membuat index idx_resiambilbarang_date_user tidak terpakai, sehingga MySQL
        // memindai seluruh riwayat picker tersebut (±590.000 baris, 11-41 detik).
        // Dengan rentang, yang dibaca hanya ±21.000 baris satu hari (±0,3 detik).
        $awal_hari  = date('Y-m-d 00:00:00');
        $besok_hari = date('Y-m-d 00:00:00', strtotime('+1 day'));

        // Ikut baca status resi: yang sudah CANCELED/REQUEST_CANCEL (dari upload
        // Jubelio) atau dibatalkan manual (tblprintresi.batal) TIDAK boleh ikut
        // dipindah ke packer — scan packer satuan menolaknya, sinkron massal
        // harus konsisten. Dihitung terpisah supaya operator tahu ada yang dilewati.
        $this->db->select('rab.id_resi, pr.status_pesanan, pr.batal');
        $this->db->from('tblresiambilbarang rab');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = rab.id_resi', 'inner');
        $this->db->join('tblpacking p', 'p.id_resi = rab.id_resi', 'left');
        $this->db->where('rab.yangambil_pegawai', $id_picker);
        $this->db->where('rab.tanggal_resiambilbarang >=', $awal_hari);
        $this->db->where('rab.tanggal_resiambilbarang <', $besok_hari);
        $this->db->where('p.id_packing IS NULL');

        $query = $this->db->get();
        $resis_to_sync = [];
        $dilewati_cancel = 0;
        foreach ($query->result_array() as $row) {
            $sudah_cancel = stripos((string) $row['status_pesanan'], 'CANCEL') !== false
                || ($row['batal'] !== null && $row['batal'] !== '' && $row['batal'] !== '0');
            if ($sudah_cancel) {
                $dilewati_cancel++;
                continue;
            }
            $resis_to_sync[] = $row;
        }

        $catatan_cancel = $dilewati_cancel > 0
            ? " ($dilewati_cancel resi dilewati karena sudah CANCEL.)"
            : '';

        if (empty($resis_to_sync)) {
            $this->make_ajax_response(200, 'Tidak ada resi baru dari Picker ini yang perlu disinkronkan.' . $catatan_cancel, [
                'total_sync'      => 0,
                'dilewati_cancel' => $dilewati_cancel,
            ]);
        }

        $normal_packer_status_id = $this->kpi_fcd->get_status_id_by_name('NORMAL');

        // Cari status performa aktif untuk packer terpilih
        $packer_status = $this->kpi_fcd->get_user_status_performa($id_packer);
        $packer_status_id = $packer_status ? $packer_status->id_statusperforma : $normal_packer_status_id;

        $tanggal_packing = date('Y-m-d H:i:s');
        $batch = [];

        foreach ($resis_to_sync as $row) {
            $batch[] = [
                'id_resi' => $row['id_resi'],
                'tanggal_packing' => $tanggal_packing,
                'packer_pegawai' => $id_packer,
                'keterangan' => 'SYNC_FROM_PICKER',
                'status_performa_id' => $packer_status_id
            ];
        }

        $count = count($batch);

        // Matikan db_debug sementara: kalau insert gagal, CI mencetak halaman HTML
        // yang tercampur ke respon JSON dan merusak tampilan (lihat DEVELOPMENT_STANDARDS).
        $db_debug_asli = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $this->db->trans_start();

        // insert_batch per 500 baris: ±9 perjalanan ke server DB menggantikan
        // ±4.500 INSERT satuan. Inilah beban terbesar versi lama — tiap statement
        // memakan ±11 ms bolak-balik, jadi 4.500 INSERT saja sudah ±50 detik.
        foreach (array_chunk($batch, 500) as $chunk) {
            $this->db->insert_batch('tblpacking', $chunk);
        }

        // Log KPI packer cukup SEKALI dengan total resi, bukan N kali @1 resi.
        // Hasil akhir di tblkpi identik (jumlah_resi bertambah sebanyak $count),
        // tapi menghemat ±9.000 query (1 SELECT + 1 UPDATE per resi).
        $this->kpi_fcd->log_transaksi_harian($id_packer, $packer_status_id, 'PACKING', $count);

        $this->db->trans_complete();

        $sukses = $this->db->trans_status();
        $this->db->db_debug = $db_debug_asli;

        if ($sukses === FALSE) {
            $this->make_ajax_response(500, 'Gagal melakukan sinkronisasi massal.');
        }

        $this->make_ajax_response(200, "Berhasil menyinkronkan $count resi ke Packer yang dipilih." . $catatan_cancel, [
            'total_sync'      => $count,
            'dilewati_cancel' => $dilewati_cancel,
        ]);
    }
}
