<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM CS -> "Daftar Masalah Picker New".
 *
 * Versi baru dari Cs::masalah_picker() yang sengaja dibuat sebagai controller
 * terpisah (controller, model, view, route, dan tabel riwayatnya sendiri)
 * supaya menu lama tetap utuh dan bisa dipakai kalau versi ini perlu
 * diperbaiki. Tabel sumber datanya tetap tblmasalahpicker yang diisi Packer.
 *
 * Perbedaan perilaku dari menu lama:
 * 1. "Proses Kurangan & Cetak" langsung memproses SEMUA masalah pending di
 *    rentang tanggal (atau yang dicentang saja) dan mencetak slipnya -- tidak
 *    lagi dibatasi 15 baris dan tanpa memilih penerima/pengambil manual.
 * 2. LEBIH AMBIL tetap ditandai selesai tapi tidak dicetak: tidak ada barang
 *    yang harus diambil picker.
 * 3. Picker terdeteksi otomatis dari tblresiambilbarang (siapa yang scan ambil
 *    resi itu); satu slip per picker.
 * 4. Nama packer/pelapor ikut tercetak di tiap baris slip.
 * 5. Setiap proses tercatat dan slipnya bisa dicetak ulang.
 */
class Masalah_picker_new extends MY_Controller
{
    /**
     * Role yang boleh membuka menu ini: hanya Tim CS. Tidak ada role "CS"
     * tersendiri di tblhakakses -- akun CS tersebar di webmaster (1), admin
     * (2), dan tim retur (6). Client packer (4) sengaja TIDAK termasuk:
     * packer melaporkan masalah lewat Scan Resi Packer, yang memprosesnya CS.
     *
     * Daftar ini harus sejalan dengan hak akses menu yang ditanam
     * MY_Controller::run_masalah_picker_new_migration(). Penjagaan ditaruh di
     * controller juga karena URL-nya bisa dibuka langsung.
     */
    const ROLE_BOLEH = [1, 2, 6];

    function __construct()
    {
        parent::__construct();
        $this->tolak_bukan_cs();
        $this->load->model('masalah_picker_new_fcd');
        $this->load->model('Notification');
    }

    /**
     * Penolakannya tetap JSON valid: untuk halaman lewat show() dengan
     * penanda akses_ditolak (show_404 mengirim HTML dan merusak SPA), untuk
     * endpoint data lewat make_ajax_response.
     */
    private function tolak_bukan_cs()
    {
        $role = isset($this->data['user']['hakakses']) ? (int) $this->data['user']['hakakses'] : 0;
        if (in_array($role, self::ROLE_BOLEH, TRUE)) {
            return;
        }

        if ($this->router->method === 'index') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'view'    => $this->load->view('masalah_picker_new/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Menu Daftar Masalah Picker New hanya untuk Tim CS.');
    }

    private function rentang_default()
    {
        return date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
    }

    /** Pecah "YYYY-mm-dd HH:ii:ss - YYYY-mm-dd HH:ii:ss" jadi [awal, akhir]. */
    private function pecah_rentang($reportrange)
    {
        if (empty($reportrange) || strpos($reportrange, ' - ') === FALSE) {
            $reportrange = $this->rentang_default();
        }
        $bagian = explode(' - ', $reportrange, 2);
        $awal  = trim($bagian[0]);
        $akhir = trim($bagian[1]);
        if (strtotime($awal) === FALSE || strtotime($akhir) === FALSE) {
            $bagian = explode(' - ', $this->rentang_default(), 2);
            $awal  = $bagian[0];
            $akhir = $bagian[1];
        }
        return [$awal, $akhir];
    }

    public function index()
    {
        $data['message'] = $this->session->flashdata('message');
        $data['reportrange'] = $this->input->method() == 'post'
            ? ($this->input->post('reportrange') ?: $this->rentang_default())
            : $this->rentang_default();
        $data['list_tipe'] = $this->db->order_by('id_typemasalah', 'ASC')->get('tbltypemasalah')->result_array();

        $this->show($data, 'masalah_picker_new/index');
    }

    /** Endpoint DataTables server-side: masalah yang belum diproses. */
    public function get_data()
    {
        list($start_date, $end_date) = $this->pecah_rentang($this->input->post('reportrange'));

        $draw  = intval($this->input->post('draw'));
        $order = $this->input->post('order');
        $search = $this->input->post('search');

        $params = [
            'start'      => intval($this->input->post('start')),
            'length'     => intval($this->input->post('length')),
            'search'     => trim((string) ($search['value'] ?? '')),
            'tipe'       => trim((string) $this->input->post('tipe')),
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'order'      => 'mp.created',
            'dir'        => 'desc',
        ];

        $valid_columns = [
            1  => 'mp.noresi',
            2  => 'mp.sku',
            3  => 'mp.sku_salah',
            4  => 'nama_barang',
            5  => 'mp.qty',
            6  => 'mp.qty_bermasalah',
            7  => 'tm.type_masalah',
            8  => 'no_rak',
            9  => 'nama_picker',
            10 => 'nama_packer',
            11 => 'mp.created',
        ];
        if (!empty($order[0]) && isset($valid_columns[(int) $order[0]['column']])) {
            $params['order'] = $valid_columns[(int) $order[0]['column']];
            $params['dir']   = strtolower($order[0]['dir']) === 'asc' ? 'asc' : 'desc';
        }

        $hasil = $this->masalah_picker_new_fcd->daftar_pending($params);
        $luar  = $this->masalah_picker_new_fcd->pending_luar_rentang($start_date, $end_date);

        $nomor = $params['start'] + 1;
        $data_table = [];
        foreach ($hasil['rows'] as $r) {
            $id = (int) $r['id_masalahpicker'];
            $data_table[] = [
                $nomor++ . '.',
                $this->e($r['noresi']),
                '<strong>' . $this->e($r['sku']) . '</strong>',
                $this->e($r['sku_salah'] ?: '-'),
                $this->e($r['nama_barang']),
                (int) $r['qty'],
                '<strong>' . (int) $r['qty_bermasalah'] . '</strong>',
                $this->badge_tipe((int) $r['id_typemasalah'], $r['type_masalah']),
                $this->e($r['no_rak'] ?: '-'),
                $this->sel_picker($r),
                $this->e($r['nama_packer'] ?: '-'),
                !empty($r['created']) ? date('d/m/Y H:i', strtotime($r['created'])) : '-',
                '<input type="checkbox" class="row-select" value="' . $id . '">',
                '<button type="button" class="btn btn-xs btn-info btn-detail-masalah" data-id="' . $id . '"><i class="fa fa-search"></i> Detail</button>',
            ];
        }

        $this->kirim_json([
            'draw'            => $draw,
            'recordsTotal'    => $hasil['total'],
            'recordsFiltered' => $hasil['total'],
            'data'            => $data_table,
            'pending_luar_rentang' => $luar,
        ]);
    }

    public function get_detail()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        $id = (int) $this->input->post('id');
        if ($id <= 0) {
            $this->make_ajax_response(400, 'ID tidak ditemukan');
        }

        $row = $this->masalah_picker_new_fcd->detail($id);
        if (!$row) {
            $this->make_ajax_response(404, 'Data tidak ditemukan');
        }

        $row['created_fmt'] = !empty($row['created']) ? date('d/m/Y H:i:s', strtotime($row['created'])) : '-';
        $row['updated_fmt'] = !empty($row['updated']) ? date('d/m/Y H:i:s', strtotime($row['updated'])) : null;
        $row['status_label'] = (int) $row['status'] === 0 ? 'Belum diproses' : 'Sudah diproses';

        $this->make_ajax_response(200, 'OK', $row);
    }

    /**
     * Pratinjau sebelum proses: dikelompokkan per picker, plus daftar LEBIH
     * AMBIL yang akan diproses tanpa cetak. Tidak mengubah data apa pun.
     */
    public function preview_proses()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        list($start_date, $end_date) = $this->pecah_rentang($this->input->post('reportrange'));
        $ids = $this->bersihkan_ids($this->input->post('selected_ids'));

        $rows = $this->masalah_picker_new_fcd->ambil_pending_untuk_proses($start_date, $end_date, $ids);
        if (empty($rows)) {
            $this->make_ajax_response(404, 'Tidak ada masalah picker yang belum diproses pada rentang/pilihan ini.');
        }

        $kelompok = $this->masalah_picker_new_fcd->kelompokkan_per_picker($rows);
        $this->make_ajax_response(200, 'OK', [
            'slips'       => $kelompok['slips'],
            'tanpa_cetak' => $kelompok['tanpa_cetak'],
            'jumlah_item' => count($rows),
            'mode'        => empty($ids) ? 'semua' : 'terpilih',
        ]);
    }

    /**
     * Proses + siapkan data cetak. Semua pending di rentang (atau yang dipilih)
     * ditandai selesai, Reject Display dikirim ke antrean QC, prosesnya dicatat,
     * lalu struktur slip per picker dikembalikan untuk dicetak di browser.
     */
    public function proses_cetak()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }
        list($start_date, $end_date) = $this->pecah_rentang($this->input->post('reportrange'));
        $ids = $this->bersihkan_ids($this->input->post('selected_ids'));
        $id_user   = (int) $this->data['user']['id_user'];
        $nama_user = (string) ($this->data['user']['name'] ?? $this->data['user']['username'] ?? $id_user);

        $rows = $this->masalah_picker_new_fcd->ambil_pending_untuk_proses($start_date, $end_date, $ids);
        if (empty($rows)) {
            $this->make_ajax_response(404, 'Tidak ada masalah picker yang belum diproses pada rentang/pilihan ini.');
        }

        // Matikan db_debug sementara: error DB di tengah transaksi akan mencetak
        // halaman HTML CI dan merusak JSON yang dibaca plugins.js.
        $db_debug_awal = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $diproses = [];
        $reject_ke_qc = 0;
        foreach ($rows as $r) {
            // Hanya baris yang benar-benar kita ubah dari 0 -> 1 yang ikut slip;
            // baris yang disambar request lain sedetik sebelumnya dilewati.
            if (!$this->masalah_picker_new_fcd->tandai_selesai($r['id_masalahpicker'], $id_user)) {
                continue;
            }
            $diproses[] = $r;
        }

        if (empty($diproses)) {
            $this->db->trans_rollback();
            $this->db->db_debug = $db_debug_awal;
            $this->make_ajax_response(409, 'Semua masalah pada pilihan ini baru saja diproses oleh user lain. Muat ulang tabel.');
        }

        $kelompok = $this->masalah_picker_new_fcd->kelompokkan_per_picker($diproses);

        foreach ($kelompok['slips'] as $slip) {
            foreach ($slip['items'] as $item) {
                if ($item['id_typemasalah'] === Masalah_picker_new_fcd::TIPE_REJECT_DISPLAY
                    && $this->masalah_picker_new_fcd->kirim_reject_ke_qc($item, $id_user)) {
                    $reject_ke_qc++;
                }
            }
        }

        $id_proses = $this->masalah_picker_new_fcd->simpan_proses($id_user, $nama_user, $kelompok['slips'], $kelompok['tanpa_cetak']);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->db->db_debug = $db_debug_awal;
            log_message('error', 'Masalah_picker_new::proses_cetak gagal: ' . json_encode($this->db->error()));
            $this->make_ajax_response(500, 'Gagal menyimpan proses. Tidak ada data yang berubah, silakan coba lagi.');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $db_debug_awal;

        if ($reject_ke_qc > 0) {
            $this->Notification->send(
                'Ada ' . $reject_ke_qc . ' barang Reject Display baru dari proses CS (Daftar Masalah Picker New).',
                'TIM RETUR',
                'Update QC: Reject Display'
            );
        }

        $this->make_ajax_response(200, 'Berhasil diproses', [
            'id_proses'     => $id_proses,
            'waktu_proses'  => date('d/m/Y H:i'),
            'nama_user'     => $nama_user,
            'slips'         => $kelompok['slips'],
            'tanpa_cetak'   => $kelompok['tanpa_cetak'],
            'jumlah_item'   => count($diproses),
            'jumlah_dilewati' => count($rows) - count($diproses),
            'reject_ke_qc'  => $reject_ke_qc,
        ]);
    }

    /** Riwayat proses terakhir untuk panel cetak ulang. */
    public function riwayat_cetak()
    {
        $rows = $this->masalah_picker_new_fcd->riwayat_proses(30);
        foreach ($rows as &$r) {
            $r['waktu_fmt'] = date('d/m/Y H:i', strtotime($r['waktu_proses']));
            $r['daftar_picker'] = $r['daftar_picker'] ?: '-';
        }
        unset($r);
        $this->make_ajax_response(200, 'OK', ['rows' => $rows]);
    }

    /** Data slip dari snapshot untuk cetak ulang satu proses (opsional satu picker). */
    public function data_cetak_ulang()
    {
        $id_proses = (int) $this->input->post('id_proses');
        $kode_picker = $this->input->post('kode_picker');
        if ($id_proses <= 0) {
            $this->make_ajax_response(400, 'ID proses tidak valid');
        }

        $header = $this->masalah_picker_new_fcd->proses_header($id_proses);
        if (!$header) {
            $this->make_ajax_response(404, 'Riwayat proses tidak ditemukan');
        }

        $slips = $this->masalah_picker_new_fcd->slip_dari_snapshot($id_proses, $kode_picker === '' ? null : $kode_picker);
        if (empty($slips)) {
            $this->make_ajax_response(404, 'Proses ini tidak punya slip untuk dicetak (semua LEBIH AMBIL).');
        }

        $this->make_ajax_response(200, 'OK', [
            'id_proses'    => $id_proses,
            'waktu_proses' => date('d/m/Y H:i', strtotime($header['waktu_proses'])),
            'nama_user'    => $header['nama_user'],
            'slips'        => $slips,
            'cetak_ulang'  => TRUE,
        ]);
    }

    // ------------------------------------------------------------------

    private function bersihkan_ids($ids)
    {
        if (!is_array($ids)) {
            return [];
        }
        $bersih = [];
        foreach ($ids as $id) {
            if ((int) $id > 0) {
                $bersih[] = (int) $id;
            }
        }
        return array_values(array_unique($bersih));
    }

    private function e($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }

    /** Sel kolom Picker: nama, atau kode pegawainya kalau tidak punya nama, atau tanda belum di-scan ambil. */
    private function sel_picker(array $r)
    {
        if (!empty($r['nama_picker'])) {
            return $this->e($r['nama_picker']);
        }
        if ($r['kode_picker'] !== null) {
            return '<span class="text-warning" title="Kode pegawai ini tidak ada di data pegawai/user">PEGAWAI #' . (int) $r['kode_picker'] . '</span>';
        }
        return '<span class="text-danger" title="Resi ini belum pernah di-scan ambil barang"><i class="fa fa-question-circle"></i> Tidak terdeteksi</span>';
    }

    private function badge_tipe($id_tipe, $label)
    {
        $warna = [
            Masalah_picker_new_fcd::TIPE_TIDAK_AMBIL    => 'label-danger',
            Masalah_picker_new_fcd::TIPE_LEBIH_AMBIL    => 'label-default',
            Masalah_picker_new_fcd::TIPE_KURANG_AMBIL   => 'label-warning',
            Masalah_picker_new_fcd::TIPE_SALAH_AMBIL    => 'label-primary',
            Masalah_picker_new_fcd::TIPE_REJECT_DISPLAY => 'label-info',
        ];
        $kelas = $warna[$id_tipe] ?? 'label-default';
        $judul = $id_tipe === Masalah_picker_new_fcd::TIPE_LEBIH_AMBIL ? ' title="Diproses tanpa cetak: tidak ada barang yang perlu diambil"' : '';
        return '<span class="label ' . $kelas . '"' . $judul . '>' . $this->e($label ?: '-') . '</span>';
    }

    /** Balasan DataTables: bentuknya bukan {code,message}, jadi tidak lewat make_ajax_response. */
    private function kirim_json(array $payload)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }
}
