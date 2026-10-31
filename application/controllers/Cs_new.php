<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Manajemen Komplain CS New — duplikat penuh dari Cs::complain_management*.
 * Dipakai untuk eksperimen/perubahan; menu lama (cs/complain-management) tetap
 * utuh sebagai cadangan. Tabel data (tblcs_complain, dst.) dipakai bersama.
 * Rute: cs-new/complain-management/* (lihat routes.php).
 */
class Cs_new extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('receipt_fcd');
        $this->load->model('retur_fcd');
        $this->load->model('packer_fcd');
        $this->load->model('Notification');
        $this->load->model('video_packing_fcd');
    }


    // Daftar acuan (harus sinkron dengan enum di tblcs_complain)
    private $complain_kategori = [
        'Kurang Kirim',
        'Reject',
        'Paket Kosong',
        'Salah Kirim',
        'Tidak Sesuai Deskripsi',
        'Barang Rusak',
        'Paket Hilang',
        'Telat Kirim',
        'Salah Alamat',
        'Barang Tidak Original',
        'Lainnya'
    ];

    private $complain_sumber = [
        'Chat Marketplace',
        'WhatsApp',
        'Telepon',
        'Review/Rating',
        'Email',
        'Retur Fisik',
        'Lainnya'
    ];

    // Jenis komplain (kolom "Jenis Komplain" di Excel manual); bawaan CCTV
    private $complain_jenis = ['CCTV', 'PENGEMBALIAN', 'BANDING'];

    private $complain_status = ['Baru', 'Proses', 'Selesai', 'Ditolak'];

    // Hasil pengecekan CCTV atas komplain. Terpisah dari kategori supaya jenis
    // komplain aslinya tidak hilang. 'QC Salah' memicu poin ke KPI packer.
    private $complain_hasil = [
        'QC Salah',
        'QC Benar',
        'Tidak Terlihat CCTV',
        'CCTV Tidak Bisa Diakses'
    ];

    const TYPE_MASALAH_QC_SALAH = 'QC Salah (Komplain CS)';

    public function complain_management()
    {
        $data['message'] = $this->session->flashdata('message');

        // Get marketplaces
        $data['marketplaces'] = $this->db->get('tblmarketplace')->result_array();

        // Get active employees for QC and Packer
        $data['employees'] = $this->db->get_where('tblpegawai', ['status_aktif' => 'AKTIF'])->result_array();

        // Get list of unique stores
        $data['stores'] = $this->db->select('DISTINCT(toko) as nama_toko')
                                   ->where('toko IS NOT NULL')
                                   ->where('toko !=', '')
                                   ->order_by('toko', 'asc')
                                   ->get('tblprintresi')
                                   ->result_array();

        $data['kategori_list'] = $this->complain_kategori;
        $data['sumber_list']   = $this->complain_sumber;
        $data['status_list']   = $this->complain_status;
        $data['jenis_list']    = $this->complain_jenis;
        $data['hasil_list']    = $this->complain_hasil;

        // Jumlah komplain retur fisik yang belum ditarik ke modul ini
        $data['jumlah_kandidat'] = $this->_kandidat_retur_builder()->count_all_results();

        $this->show($data, 'cs/complain_management_new');
    }

    public function get_complain_management_data()
    {
        $reportrange = $this->input->post('reportrange') ?: '';
        
        // DataTable parameters
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        // Sorting
        $order = $this->input->post('order');
        $order_column = null;
        $order_dir = 'DESC';

        $valid_columns = [
            1 => 'tgl_efektif',
            2 => 'c.no_resi',
            3 => 'm.nama_marketplace',
            4 => 'c.toko',
            5 => 'c.kategori_komplain',
            6 => 'c.sumber_komplain',
            7 => 'c.status_penanganan',
            8 => 'c.hasil_investigasi',
            9 => 'c.nominal_total',
            10 => 'c.nama_qc',
            11 => 'c.nama_packer',
            12 => 'c.tgl_banding_pengajuan'
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']);
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Apply filters
        $this->_apply_complain_filters($reportrange, $search_value, [
            'kategori' => $this->input->post('f_kategori'),
            'sumber'   => $this->input->post('f_sumber'),
            'status'   => $this->input->post('f_status'),
            'hasil'    => $this->input->post('f_hasil')
        ]);

        // Get total filtered count
        $total = $this->db->count_all_results('', false);

        // Apply sorting and limit
        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
        } else {
            $this->db->order_by('tgl_efektif', 'DESC');
            $this->db->order_by('c.id_complain', 'DESC');
        }

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

        // Hitung lampiran per komplain dalam satu query
        $lampiran_count = [];
        if (!empty($items)) {
            $ids = array_column($items, 'id_complain');
            $rows = $this->db->select('id_complain, COUNT(*) as jml')
                             ->from('tblcs_complain_lampiran')
                             ->where_in('id_complain', $ids)
                             ->where('is_deleted', 0)
                             ->group_by('id_complain')
                             ->get()
                             ->result_array();
            foreach ($rows as $r) {
                $lampiran_count[$r['id_complain']] = $r['jml'];
            }
        }

        $data_table = [];
        $row_number = $start + 1;

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        foreach ($items as $item) {
            // Badges for complain categories
            $badge_class = 'label-info';
            switch ($item['kategori_komplain']) {
                case 'Kurang Kirim': $badge_class = 'label-warning'; break;
                case 'Reject': $badge_class = 'label-danger'; break;
                case 'Paket Kosong': $badge_class = 'label-default'; break;
                case 'Salah Kirim': $badge_class = 'label-primary'; break;
                case 'Tidak Sesuai Deskripsi': $badge_class = 'label-info'; break;
                case 'Barang Rusak': $badge_class = 'label-danger'; break;
                case 'Paket Hilang': $badge_class = 'label-danger'; break;
                case 'Telat Kirim': $badge_class = 'label-warning'; break;
                case 'Salah Alamat': $badge_class = 'label-primary'; break;
                case 'Barang Tidak Original': $badge_class = 'label-danger'; break;
                case 'Lainnya': $badge_class = 'label-default'; break;
            }
            $complain_badge = '<span class="label ' . $badge_class . '">' . htmlspecialchars($item['kategori_komplain']) . '</span>';

            // Sumber komplain (NULL = data lama yang belum dilengkapi)
            if (empty($item['sumber_komplain'])) {
                $sumber_badge = '<span class="text-muted">-</span>';
            } else {
                $sumber_badge = '<span class="label label-default">' . htmlspecialchars($item['sumber_komplain']) . '</span>';
            }

            // Status penanganan
            if (empty($item['status_penanganan'])) {
                $status_badge = '<span class="text-muted">-</span>';
            } else {
                $status_class = 'label-default';
                switch ($item['status_penanganan']) {
                    case 'Baru': $status_class = 'label-danger'; break;
                    case 'Proses': $status_class = 'label-warning'; break;
                    case 'Selesai': $status_class = 'label-success'; break;
                    case 'Ditolak': $status_class = 'label-default'; break;
                }
                $status_badge = '<span class="label ' . $status_class . '">' . htmlspecialchars($item['status_penanganan']) . '</span>';
            }

            // Hasil investigasi CCTV
            if (empty($item['hasil_investigasi'])) {
                $hasil_badge = '<span class="text-muted">-</span>';
            } else {
                $hasil_class = 'label-default';
                switch ($item['hasil_investigasi']) {
                    case 'QC Salah': $hasil_class = 'label-danger'; break;
                    case 'QC Benar': $hasil_class = 'label-success'; break;
                    case 'Tidak Terlihat CCTV': $hasil_class = 'label-warning'; break;
                    case 'CCTV Tidak Bisa Diakses': $hasil_class = 'label-default'; break;
                }
                $hasil_badge = '<span class="label ' . $hasil_class . '">' . htmlspecialchars($item['hasil_investigasi']) . '</span>';
                if ($item['hasil_investigasi'] === 'QC Salah' && !empty($item['id_masalahpacker'])) {
                    $hasil_badge .= ' <i class="fa fa-star text-danger" title="Poin kesalahan masuk rekapan KPI packer"></i>';
                }
            }

            // Penanda asal & lampiran di kolom resi
            $resi_cell = htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8');
            if (!empty($item['no_pesanan'])) {
                $resi_cell .= '<br><small class="text-muted">' . htmlspecialchars($item['no_pesanan'], ENT_QUOTES, 'UTF-8') . '</small>';
            }
            if (!empty($item['id_resiretur'])) {
                $resi_cell .= ' <i class="fa fa-truck text-muted" title="Ditarik dari retur fisik"></i>';
            }
            $jml_lampiran = $lampiran_count[$item['id_complain']] ?? 0;
            if ($jml_lampiran > 0) {
                $resi_cell .= ' <span class="text-muted" title="' . $jml_lampiran . ' lampiran"><i class="fa fa-paperclip"></i> ' . $jml_lampiran . '</span>';
            }

            // Banding status
            $status_banding = '<span class="label label-default">Belum Diajukan</span>';
            if (!empty($item['tgl_banding_pengajuan'])) {
                if (!empty($item['tgl_claim_dana'])) {
                    $status_banding = '<span class="label label-success">Claimed: Rp ' . number_format($item['nominal_claim_dana'], 0, ',', '.') . '</span>';
                } else if (!empty($item['tgl_banding_tinjauan'])) {
                    $status_banding = '<span class="label label-info">Ditinjau</span>';
                } else {
                    $status_banding = '<span class="label label-warning">Diajukan</span>';
                }
            }

            // Edit and Delete buttons (Delete only for admin/supervisor)
            $id_complain = (int) $item['id_complain'];
            $action_buttons = '<button type="button" class="btn btn-info btn-xs btn-edit-complain" title="Detail / Edit" data-id="' . $id_complain . '"><i class="fa fa-pencil"></i></button> ';
            if ($is_admin) {
                $action_buttons .= '<button type="button" class="btn btn-danger btn-xs btn-delete-complain" title="Hapus (Soft-delete)" data-id="' . $id_complain . '" data-noresi="' . htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-trash"></i></button>';
            }

            $data_table[] = [
                $row_number++ . '.',
                $item['tgl_efektif'] ? date('d/m/Y', strtotime($item['tgl_efektif'])) : '-',
                $resi_cell,
                $item['nama_marketplace'],
                $item['toko'] ?: '-',
                $complain_badge,
                $sumber_badge,
                $status_badge,
                $hasil_badge,
                'Rp ' . number_format($item['nominal_total'], 0, ',', '.'),
                $item['nama_qc'] ?: '-',
                $item['nama_packer'] ?: '-',
                $status_banding,
                $action_buttons
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

    private function _apply_complain_filters($reportrange, $search_value = '', $filters = [])
    {
        // tgl_efektif: tanggal komplain kalau diisi, kalau tidak jatuh ke created_at
        // (data lama hasil migrasi belum punya tgl_komplain)
        $this->db->select('c.*, m.nama_marketplace, COALESCE(c.tgl_komplain, DATE(c.created_at)) AS tgl_efektif', false);
        $this->db->from('tblcs_complain c');
        $this->db->join('tblmarketplace m', 'c.id_marketplace = m.id_marketplace', 'left');
        $this->db->where('c.is_deleted', 0);

        if (!empty($reportrange)) {
            $dates = explode(' - ', $reportrange);
            if (count($dates) == 2) {
                $start_date = date('Y-m-d', strtotime(trim($dates[0])));
                $end_date = date('Y-m-d', strtotime(trim($dates[1])));
                // Ditulis mentah supaya COALESCE(...) tidak diperlakukan sebagai nama kolom
                $this->db->where(
                    'COALESCE(c.tgl_komplain, DATE(c.created_at)) BETWEEN '
                        . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date),
                    null,
                    false
                );
            }
        }

        if (!empty($filters['kategori']) && in_array($filters['kategori'], $this->complain_kategori)) {
            $this->db->where('c.kategori_komplain', $filters['kategori']);
        }
        if (!empty($filters['sumber']) && in_array($filters['sumber'], $this->complain_sumber)) {
            $this->db->where('c.sumber_komplain', $filters['sumber']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === '__KOSONG__') {
                $this->db->where('c.status_penanganan IS NULL', null, false);
            } elseif (in_array($filters['status'], $this->complain_status)) {
                $this->db->where('c.status_penanganan', $filters['status']);
            }
        }
        if (!empty($filters['hasil'])) {
            if ($filters['hasil'] === '__KOSONG__') {
                $this->db->where('c.hasil_investigasi IS NULL', null, false);
            } elseif (in_array($filters['hasil'], $this->complain_hasil)) {
                $this->db->where('c.hasil_investigasi', $filters['hasil']);
            }
        }

        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('c.no_resi', $search_value);
            $this->db->or_like('c.toko', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->or_like('c.kategori_komplain', $search_value);
            $this->db->or_like('c.sumber_komplain', $search_value);
            $this->db->or_like('c.nama_qc', $search_value);
            $this->db->or_like('c.nama_packer', $search_value);
            $this->db->group_end();
        }
    }

    public function get_complain_management_detail($id_complain)
    {
        header('Content-Type: application/json');

        $id_complain = (int) $id_complain;
        $complain = $this->db->select('c.*, u.name AS nama_user_input')
                             ->from('tblcs_complain c')
                             ->join('tbluser u', 'u.id_user = c.created_by', 'left')
                             ->where(['c.id_complain' => $id_complain, 'c.is_deleted' => 0])
                             ->get()
                             ->row_array();
        if (!$complain) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            exit;
        }

        $items = $this->db->select('d.*, s.nama_sku')
                          ->from('tblcs_complain_detail d')
                          ->join('tblsku s', 'd.sku = s.id_sku', 'left')
                          ->where(['d.id_complain' => $id_complain, 'd.is_deleted' => 0])
                          ->get()
                          ->result_array();

        $lampiran = $this->db->select('id_lampiran, nama_file, nama_asli, ukuran, uploaded_at')
                             ->from('tblcs_complain_lampiran')
                             ->where(['id_complain' => $id_complain, 'is_deleted' => 0])
                             ->order_by('id_lampiran', 'asc')
                             ->get()
                             ->result_array();

        foreach ($lampiran as &$l) {
            $l['url'] = base_url('assets/uploads/cs_complain/' . $l['nama_file']);
        }
        unset($l);

        if (empty($complain['nama_penginput']) || empty($complain['no_absen_input'])) {
            $penginput = $this->_pecah_nama_user($complain['nama_user_input'] ?? '');
            $complain['nama_penginput'] = $complain['nama_penginput'] ?: $penginput['nama'];
            $complain['no_absen_input'] = $complain['no_absen_input'] ?: $penginput['absen'];
        }

        // Komplain lain di resi yang sama (multi komplain per resi)
        $komplain_lain = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                  ->from('tblcs_complain')
                                  ->where('no_resi', $complain['no_resi'])
                                  ->where('id_complain !=', $id_complain)
                                  ->where('is_deleted', 0)
                                  ->get()
                                  ->result_array();

        echo json_encode([
            'success' => true,
            'complain' => $complain,
            'items' => $items,
            'lampiran' => $lampiran,
            'komplain_lain' => $komplain_lain
        ]);
        exit;
    }

    /**
     * Pecah nama user jadi nama + no absen. Format di tbluser tidak seragam:
     * "IKA - LEADER - 0001", "0353 - JOHANSEN - IT", "KUSDIANTORO - MANAGEMENT -888".
     * No absen = potongan yang semuanya angka; nama = potongan pertama yang bukan angka.
     */
    private function _pecah_nama_user($nama_lengkap)
    {
        $absen = '-';
        $nama = '-';
        foreach (explode('-', (string) $nama_lengkap) as $potong) {
            $potong = trim($potong);
            if ($potong === '') {
                continue;
            }
            if (ctype_digit($potong)) {
                if ($absen === '-') {
                    $absen = $potong;
                }
            } elseif ($nama === '-') {
                $nama = $potong;
            }
        }
        return ['nama' => $nama, 'absen' => $absen];
    }

    public function save_complain_management()
    {
        header('Content-Type: application/json');
        
        $is_edit = $this->input->post('is_edit') == '1';
        $id_complain = (int) $this->input->post('id_complain');
        $no_resi = trim($this->input->post('no_resi'));
        $id_marketplace = $this->input->post('id_marketplace');
        $toko = trim($this->input->post('toko'));
        $kategori_komplain = $this->input->post('kategori_komplain');
        $detail_lainnya = $this->input->post('detail_lainnya');
        $nama_qc = $this->input->post('nama_qc');
        $nama_packer = $this->input->post('nama_packer');
        $nama_picker = $this->input->post('nama_picker');

        // Hasil auto-fill dari nomor resi
        $no_pesanan = trim($this->input->post('no_pesanan')) ?: null;
        $tgl_pesanan = $this->input->post('tgl_pesanan') ?: null;
        $nilai_pesanan = $this->input->post('nilai_pesanan');
        $nilai_pesanan = ($nilai_pesanan === null || $nilai_pesanan === '') ? null : floatval($nilai_pesanan);

        // Field cakupan baru
        // Tanggal komplain tidak ada di form: komplain baru = hari ini, edit tidak mengubahnya
        $tgl_komplain = $this->input->post('tgl_komplain') ?: ($is_edit ? null : date('Y-m-d'));
        $sumber_komplain = $this->input->post('sumber_komplain') ?: null;
        $jenis_komplain = $this->input->post('jenis_komplain') ?: 'CCTV';
        $status_penanganan = $this->input->post('status_penanganan') ?: null;
        $catatan_penanganan = $this->input->post('catatan_penanganan') ?: null;
        $hasil_investigasi = $this->input->post('hasil_investigasi') ?: null;

        // Banding inputs
        $tgl_banding_pengajuan = $this->input->post('tgl_banding_pengajuan') ?: null;
        $tgl_banding_tinjauan = $this->input->post('tgl_banding_tinjauan') ?: null;
        $tgl_claim_dana = $this->input->post('tgl_claim_dana') ?: null;
        $keterangan_banding = $this->input->post('keterangan_banding') ?: null;
        $nominal_claim_dana = floatval($this->input->post('nominal_claim_dana') ?: 0);

        // Validations
        if (empty($no_resi) || empty($id_marketplace) || empty($toko) || empty($kategori_komplain)) {
            echo json_encode(['code' => 400, 'message' => 'Harap isi semua kolom wajib!']);
            exit;
        }

        if (!in_array($kategori_komplain, $this->complain_kategori)) {
            echo json_encode(['code' => 400, 'message' => 'Kategori komplain tidak dikenali.']);
            exit;
        }
        $detail_lainnya = trim((string) $detail_lainnya);
        if ($kategori_komplain === 'Lainnya' && $detail_lainnya === '') {
            echo json_encode(['code' => 400, 'message' => 'Keterangan wajib diisi untuk kategori Lainnya.']);
            exit;
        }
        if (!in_array($jenis_komplain, $this->complain_jenis)) {
            echo json_encode(['code' => 400, 'message' => 'Jenis komplain tidak dikenali.']);
            exit;
        }
        if ($sumber_komplain !== null && !in_array($sumber_komplain, $this->complain_sumber)) {
            echo json_encode(['code' => 400, 'message' => 'Sumber komplain tidak dikenali.']);
            exit;
        }
        if ($status_penanganan !== null && !in_array($status_penanganan, $this->complain_status)) {
            echo json_encode(['code' => 400, 'message' => 'Status penanganan tidak dikenali.']);
            exit;
        }
        if ($hasil_investigasi !== null && !in_array($hasil_investigasi, $this->complain_hasil)) {
            echo json_encode(['code' => 400, 'message' => 'Hasil investigasi tidak dikenali.']);
            exit;
        }

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        // Security check for Editability (Only webmaster and admin can edit existing data)
        if ($is_edit && !$is_admin) {
            echo json_encode(['code' => 403, 'message' => 'Anda tidak memiliki hak untuk mengedit data ini. Edit hanya boleh dilakukan oleh Admin/Supervisor.']);
            exit;
        }

        if ($is_edit) {
            if ($id_complain <= 0) {
                echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
                exit;
            }
            $exist = $this->db->get_where('tblcs_complain', ['id_complain' => $id_complain, 'is_deleted' => 0])->row();
            if (!$exist) {
                echo json_encode(['code' => 404, 'message' => 'Data komplain tidak ditemukan.']);
                exit;
            }
        }

        // Satu resi boleh punya lebih dari satu komplain, tapi kategori yang sama
        // pada resi yang sama hampir pasti input dobel -> tolak kecuali dipaksa.
        if (!$is_edit && $this->input->post('force_duplikat') != '1') {
            $dobel = $this->db->get_where('tblcs_complain', [
                'no_resi' => $no_resi,
                'kategori_komplain' => $kategori_komplain,
                'is_deleted' => 0
            ])->row();
            if ($dobel) {
                echo json_encode([
                    'code' => 409,
                    'message' => 'Resi ini sudah punya komplain berkategori "' . $kategori_komplain . '". Lanjut simpan sebagai komplain terpisah?'
                ]);
                exit;
            }
        }

        $skus = $this->input->post('sku');
        $qtys = $this->input->post('qty');
        $prices = $this->input->post('price');

        if (empty($skus) || !is_array($skus)) {
            echo json_encode(['code' => 400, 'message' => 'Minimal harus menginputkan 1 item SKU!']);
            exit;
        }

        $this->db->trans_start();

        // Calculate total nominal
        $nominal_total = 0;
        $items_raw = [];
        for ($i = 0; $i < count($skus); $i++) {
            if (empty(trim($skus[$i]))) continue;
            $qty = intval($qtys[$i] ?? 1);
            $price = floatval($prices[$i] ?? 0);
            $nominal_total += ($qty * $price);

            $items_raw[] = [
                'no_resi' => $no_resi,
                'sku' => trim($skus[$i]),
                'qty' => $qty,
                'price' => $price,
                'is_deleted' => 0
            ];
        }

        $complain_data = [
            'id_marketplace' => $id_marketplace,
            'toko' => $toko,
            'kategori_komplain' => $kategori_komplain,
            'detail_lainnya' => $detail_lainnya !== '' ? $detail_lainnya : null,
            'sumber_komplain' => $sumber_komplain,
            'jenis_komplain' => $jenis_komplain,
            'catatan_penanganan' => $catatan_penanganan,
            'hasil_investigasi' => $hasil_investigasi,
            'no_pesanan' => $no_pesanan,
            'tgl_pesanan' => $tgl_pesanan,
            'nama_qc' => $nama_qc ?: null,
            'nama_packer' => $nama_packer ?: null,
            'nama_picker' => $nama_picker ?: null,
            'nominal_total' => $nominal_total,
            'nilai_pesanan' => $nilai_pesanan,
            'updated_by' => $this->data['user']['id_user']
        ];

        if ($tgl_komplain !== null) {
            $complain_data['tgl_komplain'] = $tgl_komplain;
        }

        // Form New tidak punya status penanganan & proses banding: tulis hanya bila dikirim
        if ($status_penanganan !== null) {
            $complain_data['status_penanganan'] = $status_penanganan;
        } elseif (!$is_edit) {
            $complain_data['status_penanganan'] = 'Baru';
        }
        foreach (['tgl_banding_pengajuan' => $tgl_banding_pengajuan, 'tgl_banding_tinjauan' => $tgl_banding_tinjauan,
                  'tgl_claim_dana' => $tgl_claim_dana, 'keterangan_banding' => $keterangan_banding] as $kolom => $nilai) {
            if ($this->input->post($kolom) !== null) {
                $complain_data[$kolom] = $nilai;
            }
        }
        if ($this->input->post('nominal_claim_dana') !== null) {
            $complain_data['nominal_claim_dana'] = $nominal_claim_dana;
        }

        if ($is_edit) {
            // Update master
            $this->db->where('id_complain', $id_complain)->update('tblcs_complain', $complain_data);

            // Soft-delete old items (set is_deleted = 1, NO SQL DELETE statement is run)
            $this->db->where('id_complain', $id_complain)->update('tblcs_complain_detail', ['is_deleted' => 1]);
        } else {
            // Insert master
            $complain_data['no_resi'] = $no_resi;
            $complain_data['created_at'] = date('Y-m-d H:i:s');
            $complain_data['created_by'] = $this->data['user']['id_user'];
            // Data penginput dipisah per kolom supaya bisa difilter/dilaporkan sendiri
            $penginput = $this->_pecah_nama_user($this->data['user']['name'] ?? '');
            $complain_data['no_absen_input'] = $penginput['absen'];
            $complain_data['nama_penginput'] = $penginput['nama'];
            $this->db->insert('tblcs_complain', $complain_data);
            $id_complain = (int) $this->db->insert_id();
        }

        // Insert new items
        if (!empty($items_raw) && $id_complain > 0) {
            $items_to_save = [];
            foreach ($items_raw as $it) {
                $it['id_complain'] = $id_complain;
                $items_to_save[] = $it;
            }
            $this->db->insert_batch('tblcs_complain_detail', $items_to_save);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['code' => 500, 'message' => 'Gagal menyimpan data komplain.']);
            exit;
        }

        // Lampiran diproses setelah transaksi supaya id_complain sudah pasti ada
        $upload_result = $this->_simpan_lampiran_complain($id_complain);

        // Poin KPI packer mengikuti hasil investigasi
        $kpi = $this->_sinkron_poin_kpi_packer($id_complain, $hasil_investigasi, $no_resi);

        $message = 'Data komplain berhasil disimpan!';
        if ($upload_result['tersimpan'] > 0) {
            $message .= ' ' . $upload_result['tersimpan'] . ' lampiran terunggah.';
        }
        if (!empty($upload_result['gagal'])) {
            $message .= ' Lampiran gagal: ' . implode('; ', $upload_result['gagal']);
        }
        if (!empty($kpi['message'])) {
            $message .= ' ' . $kpi['message'];
        }

        echo json_encode(['code' => 201, 'message' => $message, 'id_complain' => $id_complain]);
        exit;
    }

    public function delete_complain_management($id_complain)
    {
        header('Content-Type: application/json');

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);

        if (!$is_admin) {
            echo json_encode(['code' => 403, 'message' => 'Hanya Admin/Supervisor yang dapat menghapus data komplain!']);
            exit;
        }

        $id_complain = (int) $id_complain;
        if ($id_complain <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
            exit;
        }

        // Komplain yang dihapus tidak boleh menyisakan poin di KPI packer
        $this->_sinkron_poin_kpi_packer($id_complain, null, null);

        $this->db->trans_start();

        // Soft delete master
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain', [
            'is_deleted' => 1,
            'updated_by' => $this->data['user']['id_user']
        ]);

        // Soft delete details
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain_detail', [
            'is_deleted' => 1
        ]);

        // Soft delete lampiran (file fisik dibiarkan agar bisa dipulihkan)
        $this->db->where('id_complain', $id_complain)->update('tblcs_complain_lampiran', [
            'is_deleted' => 1
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['code' => 500, 'message' => 'Gagal menghapus data komplain.']);
        } else {
            echo json_encode(['code' => 200, 'message' => 'Data komplain berhasil dihapus!']);
        }
        exit;
    }

    public function search_resi_for_complain()
    {
        header('Content-Type: application/json');
        $noresi = trim($this->input->post('noresi'));

        if (empty($noresi)) {
            echo json_encode(['success' => false, 'message' => 'Nomor resi kosong']);
            exit;
        }

        // Resi lama yang sudah diarsipkan ditarik dulu ke prod (docs/ARSIP_DATA.md)
        pastikan_resi_live($noresi);

        // Data induk resi: marketplace, toko, tanggal pesanan, picker, packer.
        // Detail sengaja tidak di-join di sini supaya baris tidak berlipat.
        $query = "SELECT pr.noresi, pr.id_printresi, pr.id_marketplace, m.nama_marketplace, pr.toko,
                         pr.tanggal_pesan, pr.tanggal_printresi,
                         COALESCE(pr.harga, 0) as nilai_pesanan,
                         p.packer_pegawai as packer_id, peg_pack.nama_pegawai as nama_packer,
                         rab.yangambil_pegawai as picker_id, peg_pick.nama_pegawai as nama_picker
                  FROM tblprintresi pr
                  LEFT JOIN tblmarketplace m ON pr.id_marketplace = m.id_marketplace
                  LEFT JOIN tblpacking p ON pr.id_printresi = p.id_resi
                  LEFT JOIN tblpegawai peg_pack ON p.packer_pegawai = peg_pack.kode_pegawai
                  LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
                  LEFT JOIN tblpegawai peg_pick ON peg_pick.kode_pegawai = rab.yangambil_pegawai
                  WHERE pr.noresi = ? OR pr.nomorpicklist = ?
                  LIMIT 1";

        $res = $this->db->query($query, [$noresi, $noresi])->row_array();

        // Bukan resi/picklist: coba sebagai nomor pesanan (detail resi, lalu retur Jubelio)
        if (!$res) {
            $via_pesanan = $this->db->query(
                "SELECT pr.noresi FROM tbldetailprintresi d
                 JOIN tblprintresi pr ON pr.id_printresi = d.id_resi
                 WHERE d.no_pesanan IN (?, ?) ORDER BY pr.id_printresi DESC LIMIT 1", [$noresi, 'SP-' . $noresi]
            )->row_array();
            if (!$via_pesanan) {
                $via_pesanan = $this->db->query(
                    "SELECT no_resi AS noresi FROM tblreturjubelio WHERE no_pesanan IN (?, ?) AND no_resi <> '' LIMIT 1", [$noresi, 'SP-' . $noresi]
                )->row_array();
            }
            if ($via_pesanan && !empty($via_pesanan['noresi'])) {
                $noresi = $via_pesanan['noresi'];
                pastikan_resi_live($noresi);
                $res = $this->db->query($query, [$noresi, $noresi])->row_array();
            }
        }

        if ($res) {
            // Get items
            $items_query = "SELECT d.sku, d.jumlah as qty, d.no_pesanan, s.nama_sku, COALESCE(s.hpp, 0) as hpp
                            FROM tbldetailprintresi d
                            LEFT JOIN tblsku s ON d.sku = s.id_sku
                            WHERE d.id_resi = ?";
            $items = $this->db->query($items_query, [$res['id_printresi']])->result_array();

            // Nomor pesanan diambil dari baris detail pertama yang terisi
            $res['no_pesanan'] = '';
            foreach ($items as $it) {
                if (!empty($it['no_pesanan'])) {
                    $res['no_pesanan'] = $it['no_pesanan'];
                    break;
                }
            }

            // Lengkapi harga dari data Jubelio: tblsku.hpp dan tblprintresi.harga
            // kosong di seluruh database, jadi ini satu-satunya sumber nominal.
            $harga = $this->_harga_dari_jubelio($res['noresi']);
            foreach ($items as &$it) {
                if (floatval($it['hpp']) <= 0 && isset($harga['per_sku'][$it['sku']])) {
                    $it['hpp'] = $harga['per_sku'][$it['sku']];
                }
            }
            unset($it);

            if (empty($res['no_pesanan']) && !empty($harga['no_pesanan'])) {
                $res['no_pesanan'] = $harga['no_pesanan'];
            }

            // toko wajib diisi, sedangkan tblprintresi.toko sering kosong
            if (empty($res['toko']) && !empty($harga['nama_toko'])) {
                $res['toko'] = $harga['nama_toko'];
            }

            // tblprintresi.toko baru terisi sejak upload resi memuat nama toko; resi lama
            // (Agu-Sep 2026) masih kosong. Cari di tabel lain yang pernah mencatat toko resi ini.
            if (empty($res['toko'])) {
                $res['toko'] = $this->_toko_dari_tabel_lain($res['noresi'], $res['no_pesanan'], $res['id_marketplace']);
            }

            // Nilai pesanan: pakai harga resi kalau ada, kalau tidak jumlahkan barang
            if (empty($res['nilai_pesanan']) || $res['nilai_pesanan'] <= 0) {
                $total = 0;
                foreach ($items as $it) {
                    $total += intval($it['qty']) * floatval($it['hpp']);
                }
                $res['nilai_pesanan'] = $total;
                $res['nilai_pesanan_perkiraan'] = true;
            } else {
                $res['nilai_pesanan_perkiraan'] = false;
            }

            $res['sumber_harga'] = $harga['ada'] ? 'jubelio' : 'tidak ada';

            // Komplain yang sudah tercatat untuk resi ini (boleh lebih dari satu)
            $existing = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                 ->from('tblcs_complain')
                                 ->where('no_resi', $res['noresi'])
                                 ->where('is_deleted', 0)
                                 ->order_by('id_complain', 'desc')
                                 ->get()
                                 ->result_array();

            echo json_encode([
                'success' => true,
                'data' => $res,
                'items' => $items,
                'existing' => $existing
            ]);
        } else {
            // Cadangan: resi tidak ada di print resi, coba data retur Jubelio
            $jbl = $this->db->query(
                "SELECT no_resi, no_pesanan, sku, nama_barang, qty, amount, marketplace, nama_toko, tanggal_retur
                 FROM tblreturjubelio WHERE no_resi = ?",
                [$noresi]
            )->result_array();

            if (empty($jbl)) {
                echo json_encode(['success' => false, 'message' => 'Data resi/pesanan tidak ditemukan di database. Silakan isi form secara manual.']);
                exit;
            }

            $baris = $jbl[0];
            $mp = $this->db->get_where('tblmarketplace', ['nama_marketplace' => $baris['marketplace']])->row_array();

            $items = [];
            $total = 0;
            foreach ($jbl as $j) {
                $qty = intval($j['qty']) ?: 1;
                $harga_satuan = round(floatval($j['amount']) / $qty, 2);
                $total += $qty * $harga_satuan;
                $items[] = [
                    'sku' => $j['sku'],
                    'qty' => $qty,
                    'no_pesanan' => $j['no_pesanan'],
                    'nama_sku' => $j['nama_barang'],
                    'hpp' => $harga_satuan
                ];
            }

            $existing = $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                                 ->from('tblcs_complain')
                                 ->where('no_resi', $noresi)
                                 ->where('is_deleted', 0)
                                 ->order_by('id_complain', 'desc')
                                 ->get()
                                 ->result_array();

            echo json_encode([
                'success' => true,
                'data' => [
                    'noresi' => $baris['no_resi'],
                    'no_pesanan' => $baris['no_pesanan'],
                    'id_marketplace' => $mp ? $mp['id_marketplace'] : null,
                    'nama_marketplace' => $baris['marketplace'],
                    'toko' => $baris['nama_toko'],
                    'tanggal_pesan' => null,
                    'nama_packer' => null,
                    'nama_picker' => null,
                    'nilai_pesanan' => $total,
                    'nilai_pesanan_perkiraan' => true,
                    'sumber_harga' => 'jubelio'
                ],
                'items' => $items,
                'existing' => $existing,
                'catatan' => 'Resi tidak ada di data print resi. Data diambil dari retur Jubelio, picker/packer tidak tersedia.'
            ]);
        }
        exit;
    }

    /**
     * Cadangan nama toko untuk resi yang tblprintresi.toko-nya kosong. Urutan:
     * komplain sebelumnya, buka retur, cancel order, klaim retur, lalu resi lain
     * dengan nomor pesanan yang sama. Semua lookup lewat index resi/pesanan.
     */
    private function _toko_dari_tabel_lain($noresi, $no_pesanan, $id_marketplace)
    {
        $kandidat = [
            ['tblcs_complain', 'toko', 'no_resi'],
            ['tblbukaretur', 'toko', 'resi_buka'],
            ['tblcancelorder', 'toko', 'noresi'],
            ['tblreturklaim', 'nama_toko', 'no_resi'],
        ];
        foreach ($kandidat as $k) {
            $row = $this->db->query(
                "SELECT {$k[1]} AS t FROM {$k[0]} WHERE {$k[2]} = ? AND {$k[1]} IS NOT NULL AND {$k[1]} <> '' LIMIT 1",
                [$noresi]
            )->row_array();
            if ($row) {
                return $row['t'];
            }
        }

        if (!empty($no_pesanan)) {
            $row = $this->db->query(
                "SELECT p.toko AS t FROM tbldetailprintresi d
                 JOIN tblprintresi p ON p.id_printresi = d.id_resi
                 WHERE d.no_pesanan = ? AND p.toko IS NOT NULL AND p.toko <> '' LIMIT 1",
                [$no_pesanan]
            )->row_array();
            if ($row) {
                return $row['t'];
            }
        }
        return null;
    }

    /**
     * Harga per SKU untuk satu resi, diambil dari data retur Jubelio.
     *
     * tblsku.hpp dan tblprintresi.harga kosong di seluruh database, jadi
     * tblreturjubelio.amount adalah satu-satunya sumber nominal yang hidup.
     * amount = total per baris, sehingga harga satuan = amount / qty.
     *
     * Dicari per resi lewat index no_resi (~8 ms); jangan pernah men-join
     * tabel ini ke tblprintresi secara penuh - charset-nya beda (utf8 vs
     * latin1) sehingga CONVERT mematikan index dan query jadi sangat lambat.
     */
    private function _harga_dari_jubelio($no_resi)
    {
        $hasil = ['ada' => false, 'per_sku' => [], 'no_pesanan' => null, 'nama_toko' => null];

        if (empty($no_resi)) {
            return $hasil;
        }

        $rows = $this->db->query(
            "SELECT sku, qty, amount, no_pesanan, nama_toko FROM tblreturjubelio WHERE no_resi = ?",
            [$no_resi]
        )->result_array();

        foreach ($rows as $r) {
            $qty = intval($r['qty']);
            if ($qty < 1) $qty = 1;
            if (floatval($r['amount']) > 0) {
                $hasil['per_sku'][$r['sku']] = round(floatval($r['amount']) / $qty, 2);
            }
            if ($hasil['no_pesanan'] === null && !empty($r['no_pesanan'])) {
                $hasil['no_pesanan'] = $r['no_pesanan'];
            }
            if ($hasil['nama_toko'] === null && !empty($r['nama_toko'])) {
                $hasil['nama_toko'] = $r['nama_toko'];
            }
        }

        $hasil['ada'] = !empty($hasil['per_sku']);
        return $hasil;
    }

    public function get_sku_suggestions_with_hpp()
    {
        header('Content-Type: application/json');
        $term = trim($this->input->get('term'));
        if (strlen($term) < 1) {
            echo json_encode([]);
            exit();
        }

        $this->db->select('id_sku as id, id_sku as label, nama_sku as value, COALESCE(hpp, 0) as hpp');
        $this->db->like('id_sku', $term);
        $this->db->or_like('nama_sku', $term);
        $this->db->limit(10);
        $query = $this->db->get('tblsku');

        echo json_encode($query->result_array());
        exit();
    }

    // -----------------------------------------------------------------------
    // Poin KPI packer dari hasil investigasi komplain
    // -----------------------------------------------------------------------

    private function _id_type_masalah_qc_salah()
    {
        $row = $this->db->get_where('tbltypemasalahpacker', ['type_masalah' => self::TYPE_MASALAH_QC_SALAH])->row();
        if ($row) {
            return (int) $row->id_typemasalahpacker;
        }

        $this->db->insert('tbltypemasalahpacker', ['type_masalah' => self::TYPE_MASALAH_QC_SALAH]);
        return (int) $this->db->insert_id();
    }

    /**
     * Selaraskan poin kesalahan packer dengan hasil investigasi komplain.
     * 'QC Salah' -> satu baris tblmasalahpacker dibuat (dihitung 50 poin di KPI).
     * Hasil lain / dikosongkan -> poin yang pernah dibuat dicabut lagi.
     *
     * Sengaja idempoten: menyimpan ulang komplain yang sama tidak menggandakan poin.
     */
    private function _sinkron_poin_kpi_packer($id_complain, $hasil_investigasi, $no_resi)
    {
        $complain = $this->db->select('id_masalahpacker')
                             ->get_where('tblcs_complain', ['id_complain' => $id_complain])
                             ->row();
        $id_masalah_lama = $complain ? (int) $complain->id_masalahpacker : 0;

        // Bukan (lagi) QC Salah -> cabut poin kalau sebelumnya ada
        if ($hasil_investigasi !== 'QC Salah') {
            if ($id_masalah_lama > 0) {
                $this->db->where('id_masalahpacker', $id_masalah_lama)->delete('tblmasalahpacker');
                $this->db->where('id_complain', $id_complain)
                         ->update('tblcs_complain', ['id_masalahpacker' => null]);
                return ['message' => 'Poin kesalahan packer dicabut dari rekapan KPI.'];
            }
            return ['message' => ''];
        }

        // Sudah punya poin dan barisnya masih ada -> tidak perlu apa-apa
        if ($id_masalah_lama > 0) {
            $masih_ada = $this->db->get_where('tblmasalahpacker', ['id_masalahpacker' => $id_masalah_lama])->row();
            if ($masih_ada) {
                return ['message' => ''];
            }
        }

        // Poin hanya bisa dilekatkan kalau resinya dikenal dan sudah pernah dipacking,
        // karena rekapan KPI menghitung lewat tblpacking.packer_pegawai.
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $no_resi])->row();
        if (!$receipt) {
            return ['message' => 'Poin KPI tidak dibuat: resi tidak ada di data print resi.'];
        }

        $packing = $this->db->get_where('tblpacking', ['id_resi' => $receipt->id_printresi])->row();
        if (!$packing) {
            return ['message' => 'Poin KPI tidak dibuat: resi ini belum pernah dipacking.'];
        }

        // Ambil barang pertama dari detail komplain sebagai rujukan SKU
        $item = $this->db->select('sku, qty')
                         ->get_where('tblcs_complain_detail', ['id_complain' => $id_complain, 'is_deleted' => 0])
                         ->row();

        $this->db->insert('tblmasalahpacker', [
            'id_printresi'         => $receipt->id_printresi,
            'noresi'               => $no_resi,
            'sku'                  => $item ? $item->sku : null,
            'qty'                  => $item ? (int) $item->qty : 1,
            'id_typemasalahpacker' => $this->_id_type_masalah_qc_salah(),
            'qty_bermasalah'       => $item ? (int) $item->qty : 1,
            'keterangan'           => 'Otomatis dari Manajemen Komplain CS #' . $id_complain . ' (hasil investigasi: QC Salah)',
            'status'               => 1,
            'created_by'           => $this->data['user']['id_user'],
            'created'              => date('Y-m-d H:i:s')
        ]);

        $id_masalah_baru = (int) $this->db->insert_id();
        $this->db->where('id_complain', $id_complain)
                 ->update('tblcs_complain', ['id_masalahpacker' => $id_masalah_baru]);

        return ['message' => 'Poin kesalahan packer ditambahkan ke rekapan KPI.'];
    }

    // -----------------------------------------------------------------------
    // Feeder: komplain dari retur fisik (tblresiretur.is_komplain = 1)
    // Hanya membaca tabel retur, tidak pernah menulis ke sana.
    // -----------------------------------------------------------------------

    /**
     * Query builder kandidat retur fisik yang belum ditarik ke modul komplain.
     * noresi di tblresiretur/tblprintresi bercharset latin1 sedangkan
     * tblcs_complain.no_resi utf8mb4, jadi perbandingan wajib pakai CONVERT.
     */
    private function _kandidat_retur_builder()
    {
        $this->db->from('tblresiretur rr');
        $this->db->where('rr.is_komplain', 1);
        $this->db->where('NOT EXISTS (SELECT 1 FROM tblcs_complain cc WHERE cc.id_resiretur = rr.id_resiretur AND cc.is_deleted = 0)', null, false);

        return $this->db;
    }

    public function get_kandidat_retur_data()
    {
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search_value = $this->input->post('search')['value'] ?? '';

        $this->_kandidat_retur_builder();
        $this->db->select('rr.id_resiretur, rr.noresi, rr.tanggal_resiretur, rr.status_retur, m.nama_marketplace, pr.toko');
        $this->db->join('tblmarketplace m', 'm.id_marketplace = rr.id_marketplace', 'left');
        $this->db->join('tblprintresi pr', 'pr.id_printresi = rr.id_resi', 'left');
        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('rr.noresi', $search_value);
            $this->db->or_like('pr.toko', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by('rr.tanggal_resiretur', 'DESC');
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        $items = $this->db->get()->result_array();

        $rows = [];
        $row_number = $start + 1;
        foreach ($items as $item) {
            $rows[] = [
                $row_number++ . '.',
                $item['tanggal_resiretur'] ? date('d/m/Y H:i', strtotime($item['tanggal_resiretur'])) : '-',
                htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8'),
                $item['nama_marketplace'] ?: '-',
                $item['toko'] ?: '-',
                htmlspecialchars($item['status_retur'] ?: '-', ENT_QUOTES, 'UTF-8'),
                '<button type="button" class="btn btn-primary btn-xs btn-tarik-kandidat" data-id="' . (int) $item['id_resiretur'] . '" data-noresi="' . htmlspecialchars($item['noresi'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-download"></i> Tarik</button>'
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $rows
            ]));
    }

    public function tarik_kandidat_retur()
    {
        header('Content-Type: application/json');

        $ids = $this->input->post('id_resiretur');
        if (!is_array($ids)) {
            $ids = $ids !== null ? [$ids] : [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            echo json_encode(['code' => 400, 'message' => 'Tidak ada kandidat yang dipilih.']);
            exit;
        }

        $rows = $this->db->select('rr.id_resiretur, rr.noresi, rr.tanggal_resiretur, rr.id_marketplace, rr.id_resi,
                                   pr.toko, pr.id_marketplace AS mp_printresi, pr.tanggal_pesan,
                                   peg_pack.nama_pegawai AS nama_packer, peg_pick.nama_pegawai AS nama_picker', false)
                         ->from('tblresiretur rr')
                         ->join('tblprintresi pr', 'pr.id_printresi = rr.id_resi', 'left')
                         ->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left')
                         ->join('tblpegawai peg_pack', 'peg_pack.kode_pegawai = pk.packer_pegawai', 'left')
                         ->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left')
                         ->join('tblpegawai peg_pick', 'peg_pick.kode_pegawai = rab.yangambil_pegawai', 'left')
                         ->where_in('rr.id_resiretur', $ids)
                         ->where('rr.is_komplain', 1)
                         ->group_by('rr.id_resiretur')
                         ->get()
                         ->result_array();

        if (empty($rows)) {
            echo json_encode(['code' => 404, 'message' => 'Kandidat tidak ditemukan atau sudah tidak bertanda komplain.']);
            exit;
        }

        $user_id = $this->data['user']['id_user'];
        $now = date('Y-m-d H:i:s');
        $ditarik = 0;
        $dilewati = 0;

        foreach ($rows as $row) {
            // Lewati kalau sudah pernah ditarik
            $sudah = $this->db->get_where('tblcs_complain', [
                'id_resiretur' => $row['id_resiretur'],
                'is_deleted' => 0
            ])->row();
            if ($sudah) {
                $dilewati++;
                continue;
            }

            $id_marketplace = $row['id_marketplace'] ?: $row['mp_printresi'];
            if (empty($id_marketplace)) {
                $dilewati++;
                continue;
            }

            $this->db->trans_start();

            $this->db->insert('tblcs_complain', [
                'no_resi' => $row['noresi'],
                'tgl_komplain' => $row['tanggal_resiretur'] ? date('Y-m-d', strtotime($row['tanggal_resiretur'])) : date('Y-m-d'),
                'id_marketplace' => $id_marketplace,
                'toko' => $row['toko'] ?: '-',
                'kategori_komplain' => 'Lainnya',
                'detail_lainnya' => 'Ditarik otomatis dari retur fisik (scan retur komplain). Mohon lengkapi kategori & nominal.',
                'sumber_komplain' => 'Retur Fisik',
                'status_penanganan' => 'Baru',
                'id_resiretur' => $row['id_resiretur'],
                'tgl_pesanan' => $row['tanggal_pesan'] ?: null,
                'nama_packer' => $row['nama_packer'] ?: null,
                'nama_picker' => $row['nama_picker'] ?: null,
                'nominal_total' => 0,
                'created_at' => $now,
                'created_by' => $user_id
            ]);

            $id_complain = (int) $this->db->insert_id();

            // Prefill item dari detail print resi
            if ($id_complain > 0 && !empty($row['id_resi'])) {
                $items = $this->db->query(
                    "SELECT d.sku, d.jumlah AS qty, d.no_pesanan, COALESCE(s.hpp, 0) AS hpp
                     FROM tbldetailprintresi d
                     LEFT JOIN tblsku s ON d.sku = s.id_sku
                     WHERE d.id_resi = ?",
                    [$row['id_resi']]
                )->result_array();

                $batch = [];
                $nominal = 0;
                $no_pesanan = null;
                foreach ($items as $it) {
                    if ($no_pesanan === null && !empty($it['no_pesanan'])) {
                        $no_pesanan = $it['no_pesanan'];
                    }
                    $qty = intval($it['qty'] ?: 1);
                    $price = floatval($it['hpp']);
                    $nominal += $qty * $price;
                    $batch[] = [
                        'id_complain' => $id_complain,
                        'no_resi' => $row['noresi'],
                        'sku' => $it['sku'],
                        'qty' => $qty,
                        'price' => $price,
                        'is_deleted' => 0
                    ];
                }

                if (!empty($batch)) {
                    $this->db->insert_batch('tblcs_complain_detail', $batch);
                    $this->db->where('id_complain', $id_complain)
                             ->update('tblcs_complain', [
                                 'nominal_total' => $nominal,
                                 'nilai_pesanan' => $nominal,
                                 'no_pesanan' => $no_pesanan
                             ]);
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $dilewati++;
            } else {
                $ditarik++;
            }
        }

        $pesan = $ditarik . ' komplain retur fisik berhasil ditarik.';
        if ($dilewati > 0) {
            $pesan .= ' ' . $dilewati . ' dilewati (sudah ditarik / data tidak lengkap).';
        }

        echo json_encode([
            'code' => $ditarik > 0 ? 201 : 400,
            'message' => $pesan,
            'ditarik' => $ditarik,
            'dilewati' => $dilewati
        ]);
        exit;
    }

    // -----------------------------------------------------------------------
    // Lampiran bukti komplain
    // -----------------------------------------------------------------------

    private function _lampiran_upload_path()
    {
        return './assets/uploads/cs_complain/';
    }

    /**
     * Simpan berkas dari input file "lampiran[]" untuk satu komplain.
     * Mengembalikan ['tersimpan' => int, 'gagal' => string[]].
     */
    private function _simpan_lampiran_complain($id_complain)
    {
        $hasil = ['tersimpan' => 0, 'gagal' => []];

        if ($id_complain <= 0 || empty($_FILES['lampiran']['name'][0])) {
            return $hasil;
        }

        $path = $this->_lampiran_upload_path();
        if (!is_dir($path)) {
            mkdir($path, 0777, TRUE);
        }

        $config = [
            'upload_path'   => $path,
            'allowed_types' => 'jpg|jpeg|png|gif|pdf',
            'max_size'      => 5120, // KB
            'encrypt_name'  => TRUE
        ];
        $this->load->library('upload', $config);

        $files = $_FILES['lampiran'];
        $batch = [];
        $now = date('Y-m-d H:i:s');

        foreach ($files['name'] as $key => $nama) {
            if (empty($nama)) continue;

            $_FILES['lampiran_single'] = [
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key],
            ];

            $this->upload->initialize($config);

            if ($this->upload->do_upload('lampiran_single')) {
                $up = $this->upload->data();
                $batch[] = [
                    'id_complain' => $id_complain,
                    'nama_file'   => $up['file_name'],
                    'nama_asli'   => $nama,
                    'mime_type'   => $up['file_type'],
                    'ukuran'      => (int) $files['size'][$key],
                    'uploaded_by' => $this->data['user']['id_user'],
                    'uploaded_at' => $now,
                    'is_deleted'  => 0
                ];
                $hasil['tersimpan']++;
            } else {
                $hasil['gagal'][] = $nama . ' (' . strip_tags($this->upload->display_errors('', '')) . ')';
            }
        }

        if (!empty($batch)) {
            $this->db->insert_batch('tblcs_complain_lampiran', $batch);
        }

        return $hasil;
    }

    public function upload_lampiran_complain()
    {
        header('Content-Type: application/json');

        $id_complain = (int) $this->input->post('id_complain');
        if ($id_complain <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID komplain tidak valid.']);
            exit;
        }

        $ada = $this->db->get_where('tblcs_complain', ['id_complain' => $id_complain, 'is_deleted' => 0])->row();
        if (!$ada) {
            echo json_encode(['code' => 404, 'message' => 'Data komplain tidak ditemukan.']);
            exit;
        }

        $hasil = $this->_simpan_lampiran_complain($id_complain);

        if ($hasil['tersimpan'] === 0) {
            echo json_encode([
                'code' => 400,
                'message' => 'Tidak ada lampiran tersimpan.' . (!empty($hasil['gagal']) ? ' ' . implode('; ', $hasil['gagal']) : '')
            ]);
            exit;
        }

        $pesan = $hasil['tersimpan'] . ' lampiran berhasil diunggah.';
        if (!empty($hasil['gagal'])) {
            $pesan .= ' Gagal: ' . implode('; ', $hasil['gagal']);
        }

        echo json_encode(['code' => 201, 'message' => $pesan]);
        exit;
    }

    public function delete_lampiran_complain($id_lampiran)
    {
        header('Content-Type: application/json');

        $user_role = $this->data['user']['hakakses'];
        if (!in_array($user_role, [1, 2])) {
            echo json_encode(['code' => 403, 'message' => 'Hanya Admin/Supervisor yang dapat menghapus lampiran!']);
            exit;
        }

        $id_lampiran = (int) $id_lampiran;
        if ($id_lampiran <= 0) {
            echo json_encode(['code' => 400, 'message' => 'ID lampiran tidak valid.']);
            exit;
        }

        $this->db->where('id_lampiran', $id_lampiran)->update('tblcs_complain_lampiran', ['is_deleted' => 1]);

        echo json_encode(['code' => 200, 'message' => 'Lampiran dihapus.']);
        exit;
    }
}
