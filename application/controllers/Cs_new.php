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

    // Komplain dibuat sebelum tanggal ini dan belum punya status = "Selesai (lama)" (tanpa UPDATE massal)
    const STATUS_MULAI = '2026-11-01 00:00:00';
    // Retur DANA_BARANG yang belum dibuka setelah sekian hari sejak status ditetapkan diberi tanda merah
    const BATAS_HARI_RETUR = 14;

    // Status yang boleh dipilih user -> label tampilan
    private $status_label = [
        'DANA'                => 'Pengembalian Dana',
        'DANA_BARANG'         => 'Pengembalian Dana dan Barang',
        'TIDAK_ADA_PENGAJUAN' => 'Tidak Ada Pengajuan',
        'RETUR_BATAL'         => 'Retur Batal',
    ];

    /** Hanya webmaster & admin yang boleh menetapkan/mengubah status. */
    private function _boleh_ubah_status()
    {
        return in_array((int) $this->data['user']['hakakses'], [1, 2], true);
    }

    /**
     * Subquery SQL: "status_retur|status_detail" terbaru di tblresiretur untuk resi komplain.
     * Scan Terima/Buka Retur memetakan resi paket pengembalian (no_resi_retur) ke resi asli, jadi
     * baris tblresiretur selalu memakai no_resi asli. Diubah ke latin1 supaya index noresi tetap terpakai.
     */
    private function _sql_retur_terbaru()
    {
        return "(SELECT CONCAT(r.status_retur, '|', COALESCE(r.status_detail, ''), '|', COALESCE(r.tanggal_resiretur, '')) FROM tblresiretur r "
            . "WHERE r.noresi = CONVERT(c.no_resi USING latin1) ORDER BY r.id_resiretur DESC LIMIT 1)";
    }

    /** Panjang minimum no. pesanan komplain agar boleh dicocokkan sebagian (di bawah itu terlalu rawan salah cocok). */
    const MIN_PESANAN_SEBAGIAN = 6;

    /**
     * Cari catatan Terima Retur (tblresiretur) dan Buka Retur (tblbukaretur) untuk sekumpulan komplain.
     *
     * Urutan pencocokan per komplain:
     *  1. resi asli (no_resi) = tblresiretur.noresi;
     *  2. bila tidak ada: no. pesanan komplain dicocokkan dengan no. pesanan resi yang masuk retur komplain
     *     (tbldetailprintresi.no_pesanan), boleh SEBAGIAN (mis. "123" untuk "SP-123" atau "TT-123-XX"),
     *     minimal MIN_PESANAN_SEBAGIAN karakter dan hanya diterima bila tepat satu resi yang cocok.
     * Buka Retur = ada baris tblbukaretur dengan resi_buka = resi retur itu (atau status_retur sudah Buka Retur).
     *
     * @return array [id_complain => null | ['terima','buka','hasil','tgl_terima','tgl_buka','resi','cocok'=>'resi'|'pesanan']]
     */
    private function _peta_retur(array $komplain)
    {
        $hasil = [];
        if (!$komplain) {
            return $hasil;
        }

        // 1. resi asli
        $resi = [];
        foreach ($komplain as $k) {
            if ($k['no_resi'] !== null && $k['no_resi'] !== '') {
                $resi[] = $k['no_resi'];
            }
        }
        $baris_resi = [];
        if ($resi) {
            $q = $this->db->select('id_resiretur, noresi, status_retur, status_detail, tanggal_resiretur')
                ->where_in('noresi', array_values(array_unique($resi)))
                ->order_by('id_resiretur', 'ASC')->get('tblresiretur')->result_array();
            foreach ($q as $r) {
                $baris_resi[strtoupper($r['noresi'])] = $r; // yang terakhir = terbaru
            }
        }

        // 2. no. pesanan (sebagian) untuk yang belum ketemu lewat resi
        $butuh_pesanan = false;
        foreach ($komplain as $k) {
            if (!isset($baris_resi[strtoupper((string) $k['no_resi'])]) && mb_strlen(trim((string) $k['no_pesanan'])) >= self::MIN_PESANAN_SEBAGIAN) {
                $butuh_pesanan = true;
                break;
            }
        }
        $baris_pesanan = [];
        if ($butuh_pesanan) {
            $baris_pesanan = $this->db->query(
                "SELECT r.id_resiretur, r.noresi, r.status_retur, r.status_detail, r.tanggal_resiretur, d.no_pesanan "
                . "FROM tblresiretur r JOIN tbldetailprintresi d ON d.id_resi = r.id_resi "
                . "WHERE r.is_komplain = 1 AND d.no_pesanan <> ''"
            )->result_array();
        }

        $cocok = []; // id_complain => [baris retur, cara]
        foreach ($komplain as $k) {
            $id = (int) $k['id_complain'];
            $kunci = strtoupper((string) $k['no_resi']);
            if (isset($baris_resi[$kunci])) {
                $cocok[$id] = [$baris_resi[$kunci], 'resi'];
                continue;
            }
            $p = strtoupper(trim((string) $k['no_pesanan']));
            if (mb_strlen($p) < self::MIN_PESANAN_SEBAGIAN || !$baris_pesanan) {
                continue;
            }
            $kandidat = [];
            foreach ($baris_pesanan as $b) {
                if (strpos(strtoupper($b['no_pesanan']), $p) !== false) {
                    $nr = strtoupper($b['noresi']);
                    if (!isset($kandidat[$nr]) || (int) $b['id_resiretur'] > (int) $kandidat[$nr]['id_resiretur']) {
                        $kandidat[$nr] = $b;
                    }
                }
            }
            if (count($kandidat) === 1) {
                $cocok[$id] = [reset($kandidat), 'pesanan'];
            }
        }

        // 3. Buka Retur dari tblbukaretur
        $buka = [];
        $resi_retur = [];
        foreach ($cocok as $c) {
            $resi_retur[$c[0]['noresi']] = true;
        }
        if ($resi_retur) {
            $q = $this->db->query(
                "SELECT resi_buka, MAX(tanggal_buka_retur) AS tgl, "
                . "SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(status_detail_buka, '') ORDER BY id_bukaretur DESC SEPARATOR '|'), '|', 1) AS hasil "
                . "FROM tblbukaretur WHERE resi_buka IN (" . implode(',', array_map([$this->db, 'escape'], array_keys($resi_retur))) . ") "
                . "GROUP BY resi_buka"
            )->result_array();
            foreach ($q as $r) {
                $buka[strtoupper($r['resi_buka'])] = $r;
            }
        }

        foreach ($komplain as $k) {
            $id = (int) $k['id_complain'];
            if (!isset($cocok[$id])) {
                $hasil[$id] = null;
                continue;
            }
            list($b, $cara) = $cocok[$id];
            $bk = $buka[strtoupper($b['noresi'])] ?? null;
            $sudah_buka = $bk !== null || $b['status_retur'] === 'Buka Retur';
            $detail = $bk !== null && $bk['hasil'] !== '' ? $bk['hasil'] : (string) $b['status_detail'];
            $hasil[$id] = [
                'terima'     => true,
                'buka'       => $sudah_buka,
                'hasil'      => $detail !== '' ? ucfirst(strtolower(str_replace('_', ' ', $detail))) : '',
                'tgl_terima' => $b['tanggal_resiretur'] ? date('d/m/Y H:i', strtotime($b['tanggal_resiretur'])) : '',
                'tgl_buka'   => $bk !== null && $bk['tgl'] ? date('d/m/Y H:i', strtotime($bk['tgl'])) : '',
                'resi'       => $b['noresi'],
                'cocok'      => $cara,
            ];
        }
        return $hasil;
    }

    /**
     * Penanda Terima Retur / Buka Retur untuk sel Status tabel (hasil _peta_retur).
     * Kosong bila tidak ada catatan retur dan komplain bukan Dana dan Barang.
     */
    private function _penanda_retur($retur, $status_komplain)
    {
        if ($retur === null && $status_komplain !== 'DANA_BARANG') {
            return '';
        }
        $terima = $retur !== null && $retur['terima'];
        $buka   = $retur !== null && $retur['buka'];
        $via    = $retur !== null && $retur['cocok'] === 'pesanan' ? ' (dicocokkan lewat no. pesanan, resi ' . $retur['resi'] . ')' : '';

        $html = '<div class="kp-st-meta ' . ($terima ? 'kp-rt-ok' : 'kp-rt-belum') . '" title="'
            . htmlspecialchars(($terima ? 'Paket retur diterima ' . $retur['tgl_terima'] . $via : 'Paket retur belum diterima'), ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fa ' . ($terima ? 'fa-check-circle' : 'fa-circle-o') . '"></i><span>'
            . ($terima ? 'Terima' . ($retur['tgl_terima'] !== '' ? ' ' . substr($retur['tgl_terima'], 0, 5) : '') : 'Belum terima') . '</span></div>';
        $html .= '<div class="kp-st-meta ' . ($buka ? 'kp-rt-ok' : 'kp-rt-belum') . '" title="'
            . htmlspecialchars(($buka ? 'Sudah Buka Retur ' . $retur['tgl_buka'] . ($retur['hasil'] !== '' ? ': ' . $retur['hasil'] : '') : 'Belum Buka Retur'), ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fa ' . ($buka ? 'fa-check-circle' : 'fa-circle-o') . '"></i><span>'
            . ($buka ? 'Buka' . ($retur['hasil'] !== '' ? ': ' . htmlspecialchars($retur['hasil']) : '') : 'Belum buka') . '</span></div>';
        return $html;
    }

    /** Kondisi SQL per tab (null = semua). Aman terhadap NULL. */
    private function _kondisi_tab($tab)
    {
        $mulai = $this->db->escape(self::STATUS_MULAI);
        $retur = $this->_sql_retur_terbaru();
        switch ($tab) {
            case 'perlu':
                return "(c.status_komplain IS NULL AND c.created_at >= $mulai)";
            case 'retur':
                return "(c.status_komplain = 'DANA_BARANG' AND COALESCE($retur, '') NOT LIKE 'Buka Retur|%')";
            case 'selesai':
                return "((c.status_komplain IS NULL AND c.created_at < $mulai)"
                    . " OR c.status_komplain IN ('DANA','TIDAK_ADA_PENGAJUAN','RETUR_BATAL','SELESAI_LAMA')"
                    . " OR (c.status_komplain = 'DANA_BARANG' AND COALESCE($retur, '') LIKE 'Buka Retur|%'))";
        }
        return null;
    }

    /**
     * Status tampilan satu baris: kode, label, nada warna, keterangan tahap.
     * $retur = "status_retur|status_detail" (atau null bila belum ada catatan retur).
     */
    private function _info_status($status, $created_at, $tgl_status, $retur)
    {
        if ($status === null || $status === '') {
            if ($created_at && $created_at < self::STATUS_MULAI) {
                return ['kode' => 'SELESAI_LAMA', 'label' => 'Selesai (lama)', 'nada' => 'slate', 'tahap' => '', 'telat' => false, 'tab' => 'selesai'];
            }
            return ['kode' => 'PERLU', 'label' => 'Perlu Status', 'nada' => 'amber', 'tahap' => '', 'telat' => false, 'tab' => 'perlu'];
        }
        if ($status === 'DANA') {
            return ['kode' => $status, 'label' => 'Pengembalian Dana', 'nada' => 'sky', 'tahap' => '', 'telat' => false, 'tab' => 'selesai'];
        }
        if ($status === 'TIDAK_ADA_PENGAJUAN') {
            return ['kode' => $status, 'label' => 'Tidak Ada Pengajuan', 'nada' => 'slate', 'tahap' => '', 'telat' => false, 'tab' => 'selesai'];
        }
        if ($status === 'RETUR_BATAL') {
            return ['kode' => $status, 'label' => 'Retur Batal', 'nada' => 'slate', 'tahap' => '', 'telat' => false, 'tab' => 'selesai'];
        }
        if ($status === 'SELESAI_LAMA') {
            return ['kode' => $status, 'label' => 'Selesai (lama)', 'nada' => 'slate', 'tahap' => '', 'telat' => false, 'tab' => 'selesai'];
        }
        // DANA_BARANG: tahap dibaca dari alur retur (Terima Retur -> Buka Retur)
        list($st, $det) = array_pad(explode('|', (string) $retur, 3), 3, '');
        if ($st === 'Buka Retur') {
            $hasil = $det !== '' ? ucfirst(strtolower(str_replace('_', ' ', $det))) : 'tanpa keterangan';
            return ['kode' => $status, 'label' => 'Dana dan Barang', 'nada' => 'green', 'tahap' => 'Retur selesai: ' . $hasil, 'telat' => false, 'tab' => 'selesai'];
        }
        $tahap = $st === 'Terima Retur' ? 'Diterima, belum dibuka' : 'Menunggu barang tiba';
        $telat = false;
        if ($tgl_status && (time() - strtotime($tgl_status)) > self::BATAS_HARI_RETUR * 86400) {
            $telat = true;
        }
        return ['kode' => $status, 'label' => 'Dana dan Barang', 'nada' => 'indigo', 'tahap' => $tahap, 'telat' => $telat, 'tab' => 'retur'];
    }

    public function complain_management()
    {
        $data['message'] = $this->session->flashdata('message');

        // Get marketplaces
        $data['marketplaces'] = $this->db->get('tblmarketplace')->result_array();

        // Get active employees for QC and Packer
        $data['employees'] = $this->db->get_where('tblpegawai', ['status_aktif' => 'AKTIF'])->result_array();
        foreach ($data['employees'] as &$emp) {
            // value tetap nama asli (cocok dengan data lama); yang tampil "no.absen - NAMA"
            $emp['label'] = $this->_label_orang($emp['nama_pegawai']);
        }
        unset($emp);

        $data['stores'] = $this->_daftar_toko();

        $data['kategori_list'] = $this->complain_kategori;
        $data['sumber_list']   = $this->complain_sumber;
        $data['status_list']   = $this->complain_status;
        $data['jenis_list']    = $this->complain_jenis;
        $data['hasil_list']    = $this->complain_hasil;

        $this->show($data, 'cs/complain_management_new');
    }

    /**
     * Daftar toko unik untuk dropdown form. DISTINCT di tblprintresi (±500 ribu baris, tanpa
     * index toko) memakan ±0,4 detik di setiap buka menu, jadi hasilnya disimpan di file cache
     * selama 6 jam (per database, supaya mode arsip tidak tercampur).
     */
    private function _daftar_toko()
    {
        $berkas = APPPATH . 'cache/daftar_toko_komplain_' . preg_replace('/[^A-Za-z0-9_]/', '', (string) $this->db->database) . '.json';
        if (is_file($berkas) && (time() - filemtime($berkas)) < 21600) {
            $cache = json_decode((string) @file_get_contents($berkas), true);
            if (is_array($cache) && $cache) {
                return $cache;
            }
        }
        $daftar = $this->db->select('DISTINCT(toko) as nama_toko', false)
                           ->where('toko IS NOT NULL')
                           ->where('toko !=', '')
                           ->order_by('toko', 'asc')
                           ->get('tblprintresi')
                           ->result_array();
        if ($daftar) {
            @file_put_contents($berkas, json_encode($daftar), LOCK_EX);
        }
        return $daftar;
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
            0 => 'c.created_at',
            1 => 'c.no_resi',
            2 => 'c.toko',
            3 => 'c.kategori_komplain',
            4 => 'c.hasil_investigasi',
            5 => 'c.status_komplain',
            6 => 'c.nominal_total',
            7 => 'c.nama_qc'
        ];

        if (!empty($order)) {
            $col_index = $order[0]['column'];
            $order_dir = strtoupper($order[0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
            $order_column = $valid_columns[$col_index] ?? null;
        }

        // Hitung total: versi ringan (tanpa join & tanpa kolom) — count_all_results() mereset builder
        $filter = $this->_filter_dari_request();
        $this->_apply_complain_filters($reportrange, $search_value, $filter, true);
        $total = $this->db->count_all_results();

        // Ambil satu halaman data
        $this->_apply_complain_filters($reportrange, $search_value, $filter);

        if ($order_column) {
            $this->db->order_by($order_column, $order_dir);
            $this->db->order_by('c.id_complain', 'DESC');
        } else {
            $this->db->order_by('tgl_efektif', 'DESC');
            $this->db->order_by('c.id_complain', 'DESC');
        }

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        $items = $this->db->get()->result_array();

        $user_role = $this->data['user']['hakakses'];
        $is_admin = in_array($user_role, [1, 2]);
        $boleh_status = $this->_boleh_ubah_status();

        // Nada pil per kategori / hasil (kelas kp-pill-*, didefinisikan di view)
        $nada_kategori = [
            'Kurang Kirim' => 'amber', 'Reject' => 'red', 'Paket Kosong' => 'slate', 'Salah Kirim' => 'indigo',
            'Tidak Sesuai Deskripsi' => 'sky', 'Barang Rusak' => 'red', 'Paket Hilang' => 'red', 'Telat Kirim' => 'amber',
            'Salah Alamat' => 'indigo', 'Barang Tidak Original' => 'red', 'Lainnya' => 'slate'
        ];
        $nada_hasil = ['QC Salah' => 'red', 'QC Benar' => 'green', 'Tidak Terlihat CCTV' => 'amber', 'CCTV Tidak Bisa Diakses' => 'slate'];

        $data_table = [];
        $peta_retur = $this->_peta_retur($items);
        foreach ($items as $item) {
            $id_complain = (int) $item['id_complain'];

            // Kolom INPUT: "NO.ABSEN - NAMA" (kapital) + tanggal input. Komplain lama belum punya
            // kolom penginput terpisah, jadi dipecah dari nama akun pembuatnya.
            $absen = $item['no_absen_input'] ?: '';
            $nama = $item['nama_penginput'] ?: '';
            if ($absen === '' || $nama === '') {
                $p = $this->_pecah_nama_user($item['nama_user_input'] ?? '');
                $absen = $absen !== '' ? $absen : $p['absen'];
                $nama = $nama !== '' ? $nama : $p['nama'];
            }
            $label_input = ($absen !== '' && $absen !== '-' ? $absen . ' - ' : '') . mb_strtoupper($nama);
            $tgl_input = $item['created_at'] ? date('d/m/Y', strtotime($item['created_at'])) : '-';
            $id_cell = '<span class="kp-resi" title="Komplain K-' . str_pad($id_complain, 5, '0', STR_PAD_LEFT) . '">' . htmlspecialchars($label_input, ENT_QUOTES, 'UTF-8') . '</span>'
                . '<span class="kp-sub" title="' . ($item['created_at'] ? date('d/m/Y H:i:s', strtotime($item['created_at'])) : '') . '">' . $tgl_input
                . (!empty($item['id_resiretur']) ? ' <i class="fa fa-truck kp-muted" title="Ditarik dari retur fisik"></i>' : '') . '</span>';

            $resi_cell = '<span class="kp-resi kp-copyrow">' . htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8')
                . $this->_tombol_salin($item['no_resi']) . '</span>';
            if (!empty($item['no_pesanan'])) {
                $pesanan = preg_replace('/^SP-/i', '', $item['no_pesanan']);
                $resi_cell .= '<span class="kp-sub kp-copyrow">' . htmlspecialchars($pesanan, ENT_QUOTES, 'UTF-8')
                    . $this->_tombol_salin($pesanan) . '</span>';
            }

            $toko_cell = '<span class="kp-resi">' . htmlspecialchars($item['toko'] ?: '-', ENT_QUOTES, 'UTF-8') . '</span>'
                . '<span class="kp-sub">' . htmlspecialchars($item['nama_marketplace'] ?: '-', ENT_QUOTES, 'UTF-8') . '</span>';

            $kategori_cell = '<span class="kp-pill kp-pill-' . ($nada_kategori[$item['kategori_komplain']] ?? 'slate') . '">'
                . htmlspecialchars($item['kategori_komplain']) . '</span>';
            if (!empty($item['jenis_komplain']) && $item['jenis_komplain'] !== 'CCTV') {
                $kategori_cell .= '<span class="kp-sub">' . htmlspecialchars($item['jenis_komplain']) . '</span>';
            }

            if (empty($item['hasil_investigasi'])) {
                $hasil_cell = '<span class="kp-dot-text kp-dot-slate"><i></i>Belum dicek</span>';
            } else {
                $hasil_cell = '<span class="kp-dot-text kp-dot-' . ($nada_hasil[$item['hasil_investigasi']] ?? 'slate') . '"><i></i>'
                    . htmlspecialchars($item['hasil_investigasi']) . '</span>';
                if ($item['hasil_investigasi'] === 'QC Salah' && !empty($item['id_masalahpacker'])) {
                    $hasil_cell .= ' <i class="fa fa-star kp-star" title="Poin kesalahan masuk rekapan KPI packer"></i>';
                }
            }

            $info = $this->_info_status($item['status_komplain'], $item['created_at'], $item['tgl_status'], $item['retur_terbaru']);
            // Sel Status: pil + tombol ubah, lalu baris keterangan (tahap retur, resi pengembalian, lewat batas)
            $terkunci = $info['kode'] === 'DANA_BARANG' && $info['tab'] === 'selesai';
            $tombol_status = '';
            if ($boleh_status && !$terkunci) {
                $attr = ' data-id="' . $id_complain . '" data-status="' . $info['kode'] . '" data-resiretur="'
                    . htmlspecialchars((string) $item['no_resi_retur'], ENT_QUOTES, 'UTF-8') . '"';
                $tombol_status = $info['kode'] === 'PERLU'
                    ? '<button type="button" class="kp-st-tetapkan btn-set-status"' . $attr . '>Tetapkan</button>'
                    : '<button type="button" class="kp-icon-btn kp-st-edit btn-set-status" title="Ubah status"' . $attr . '><i class="fa fa-pencil"></i></button>';
            }
            $status_cell = '<div class="kp-st"><div class="kp-st-top"><span class="kp-pill kp-pill-' . $info['nada'] . '">'
                . htmlspecialchars($info['label']) . '</span>' . $tombol_status . '</div>';
            $penanda_retur = $this->_penanda_retur($peta_retur[$id_complain] ?? null, $item['status_komplain']);
            if ($penanda_retur !== '') {
                $status_cell .= $penanda_retur;
            } elseif ($info['tahap'] !== '') {
                $status_cell .= '<div class="kp-st-meta"><i class="fa fa-info-circle"></i><span>' . htmlspecialchars($info['tahap']) . '</span></div>';
            }
            // Resi pengembalian: tampil bila sudah ada (apa pun statusnya), atau bila status Dana dan Barang (kosong = peringatan)
            $ada_resi_retur = $item['no_resi_retur'] !== null && $item['no_resi_retur'] !== '';
            if ($item['status_komplain'] === 'DANA_BARANG' || $ada_resi_retur) {
                $status_cell .= '<div class="kp-st-meta kp-copyrow" title="Resi paket pengembalian barang"><i class="fa fa-truck"></i>'
                    . ($ada_resi_retur
                        ? '<span class="kp-st-resi">' . htmlspecialchars($item['no_resi_retur']) . '</span>' . $this->_tombol_salin($item['no_resi_retur'], 'kp-copy-tegas')
                        : '<span class="kp-telat">Resi retur belum diisi</span>')
                    . '</div>';
            }
            if ($info['telat']) {
                $status_cell .= '<div class="kp-st-meta kp-telat" title="Barang retur belum dibuka lebih dari ' . self::BATAS_HARI_RETUR
                    . ' hari sejak status ditetapkan"><i class="fa fa-clock-o"></i><span>Lewat ' . self::BATAS_HARI_RETUR . ' hari</span></div>';
            }
            $status_cell .= '</div>';

            $penginput = $item['nama_penginput'] ?: '';
            $input_cell = $penginput !== ''
                ? '<span class="kp-resi">' . htmlspecialchars($penginput) . '</span><span class="kp-sub">' . date('d/m/Y H:i', strtotime($item['created_at'])) . '</span>'
                : '<span class="kp-sub" style="margin:0">' . date('d/m/Y H:i', strtotime($item['created_at'])) . '</span>';

            $aksi = '<div class="kp-actions">'
                . '<button type="button" class="kp-icon-btn btn-detail-complain" title="Lihat detail" data-id="' . $id_complain . '"><i class="fa fa-eye"></i></button>';
            if ($is_admin) {
                $aksi .= '<button type="button" class="kp-icon-btn btn-edit-complain" title="Edit" data-id="' . $id_complain . '"><i class="fa fa-pencil"></i></button>'
                    . '<button type="button" class="kp-icon-btn kp-icon-danger btn-delete-complain" title="Hapus (soft-delete)" data-id="' . $id_complain . '" data-noresi="' . htmlspecialchars($item['no_resi'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-trash"></i></button>';
            }
            $aksi .= '</div>';

            $data_table[] = [
                $id_cell,
                $resi_cell,
                $toko_cell,
                $kategori_cell,
                $hasil_cell,
                $status_cell,
                'Rp ' . number_format($item['nominal_total'], 0, ',', '.'),
                '<div class="kp-duo"><span title="Packer">' . $this->_sel_orang($item['nama_qc'] ?: $item['nama_packer']) . '</span>'
                    . '<span title="Picker">' . $this->_sel_orang($item['nama_picker']) . '</span></div>',
                $aksi
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

    /** Ambil nilai filter sebagai array string (dropdown multi-pilih mengirim f_xxx[]). */
    private function _filter_array($nama)
    {
        $v = $this->input->post($nama);
        if ($v === null || $v === '' || $v === []) {
            return [];
        }
        return array_values(array_filter(array_map('strval', (array) $v), 'strlen'));
    }

    /**
     * @param bool $agregat true = tanpa select kolom (dipakai kartu ringkasan, select diatur pemanggil)
     */
    private function _apply_complain_filters($reportrange, $search_value = '', $filters = [], $agregat = false, $kolom_penuh = false)
    {
        $search_value = (string) $search_value;
        // tgl_efektif: tanggal komplain kalau diisi, kalau tidak jatuh ke created_at
        // (data lama hasil migrasi belum punya tgl_komplain)
        // Hanya kolom yang dipakai tabel (bukan c.*: kolom teks panjang tidak ikut terbawa)
        if (!$agregat && $kolom_penuh) {
            // Ekspor Excel butuh hampir semua kolom
            $this->db->select('c.*, m.nama_marketplace, u.name AS nama_user_input, COALESCE(c.tgl_komplain, DATE(c.created_at)) AS tgl_efektif', false);
        } elseif (!$agregat) {
            $this->db->select('c.id_complain, c.no_resi, c.no_pesanan, c.toko, c.kategori_komplain, c.jenis_komplain, c.hasil_investigasi, '
                . 'c.id_masalahpacker, c.id_resiretur, c.nominal_total, c.nama_qc, c.nama_packer, c.nama_picker, '
                . 'c.no_absen_input, c.nama_penginput, c.created_at, c.status_komplain, c.tgl_status, c.no_resi_retur, '
                . $this->_sql_retur_terbaru() . ' AS retur_terbaru, '
                . 'm.nama_marketplace, u.name AS nama_user_input, COALESCE(c.tgl_komplain, DATE(c.created_at)) AS tgl_efektif', false);
        }
        $this->db->from('tblcs_complain c');
        // Join hanya bila dibutuhkan: data halaman (tampil nama) atau pencarian (cari nama marketplace)
        if (!$agregat || $search_value !== '') {
            $this->db->join('tblmarketplace m', 'c.id_marketplace = m.id_marketplace', 'left');
        }
        if (!$agregat) {
            $this->db->join('tbluser u', 'u.id_user = c.created_by', 'left');
        }
        $this->db->where('c.is_deleted', 0);

        if (!empty($reportrange)) {
            $dates = explode(' - ', $reportrange);
            if (count($dates) == 2) {
                $start_date = date('Y-m-d', strtotime(trim($dates[0])));
                $end_date = date('Y-m-d', strtotime(trim($dates[1])));
                $akhir_eks = date('Y-m-d', strtotime($end_date . ' +1 day'));
                // Bentuk yang bisa memakai index (idx_cs_complain_tgl / created_at), setara dengan
                // COALESCE(tgl_komplain, DATE(created_at)) BETWEEN start AND end.
                $this->db->where(
                    '((c.tgl_komplain BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date) . ')'
                        . ' OR (c.tgl_komplain IS NULL AND c.created_at >= ' . $this->db->escape($start_date)
                        . ' AND c.created_at < ' . $this->db->escape($akhir_eks) . '))',
                    null,
                    false
                );
            }
        }

        // Tab (Perlu Status / Retur / Selesai); 'semua' = tanpa kondisi
        $kondisi_tab = $this->_kondisi_tab($filters['tab'] ?? 'semua');
        if ($kondisi_tab !== null) {
            $this->db->where($kondisi_tab, null, false);
        }

        // Filter multi-pilih: hanya nilai yang dikenal yang dipakai
        $kategori = array_values(array_intersect($filters['kategori'] ?? [], $this->complain_kategori));
        if ($kategori) {
            $this->db->where_in('c.kategori_komplain', $kategori);
        }
        $sumber = array_values(array_intersect($filters['sumber'] ?? [], $this->complain_sumber));
        if ($sumber) {
            $this->db->where_in('c.sumber_komplain', $sumber);
        }
        $jenis = array_values(array_intersect($filters['jenis'] ?? [], $this->complain_jenis));
        if ($jenis) {
            $this->db->where_in('c.jenis_komplain', $jenis);
        }
        $mp = array_values(array_filter(array_map('intval', $filters['marketplace'] ?? [])));
        if ($mp) {
            $this->db->where_in('c.id_marketplace', $mp);
        }
        $hasil_in = $filters['hasil'] ?? [];
        $hasil = array_values(array_intersect($hasil_in, $this->complain_hasil));
        $hasil_kosong = in_array('__KOSONG__', $hasil_in, true);
        if ($hasil || $hasil_kosong) {
            $this->db->group_start();
            if ($hasil) {
                $this->db->where_in('c.hasil_investigasi', $hasil);
            }
            if ($hasil_kosong) {
                $this->db->or_where('c.hasil_investigasi IS NULL', null, false);
            }
            $this->db->group_end();
        }

        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('c.no_resi', $search_value);
            $this->db->or_like('c.no_pesanan', $search_value);
            $this->db->or_like('c.toko', $search_value);
            $this->db->or_like('m.nama_marketplace', $search_value);
            $this->db->or_like('c.kategori_komplain', $search_value);
            $this->db->or_like('c.detail_lainnya', $search_value);
            $this->db->or_like('c.nama_qc', $search_value);
            $this->db->or_like('c.nama_packer', $search_value);
            $this->db->or_like('c.nama_picker', $search_value);
            $this->db->or_like('c.nama_penginput', $search_value);
            $this->db->or_like('c.no_absen_input', $search_value);
            $this->db->group_end();
        }
    }

    /**
     * Ekspor daftar komplain ke Excel (.xlsx) mengikuti rentang tanggal, pencarian, dan filter
     * yang sedang aktif di layar. Dikirim lewat form POST biasa (bukan AJAX) supaya browser
     * mengunduh berkasnya.
     */
    public function export_complain_management()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->_apply_complain_filters(
            $this->input->post('reportrange') ?: '',
            $this->input->post('search_text') ?: '',
            $this->_filter_dari_request(),
            false,
            true
        );
        $this->db->order_by('tgl_efektif', 'DESC');
        $this->db->order_by('c.id_complain', 'DESC');
        $this->db->limit(20000);
        $items = $this->db->get()->result_array();

        // Barang per komplain, digabung jadi satu sel "SKU x qty"
        $barang = [];
        $ids = array_column($items, 'id_complain');
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = $this->db->select('id_complain, sku, qty, price')
                             ->where_in('id_complain', $chunk)
                             ->where('is_deleted', 0)
                             ->order_by('id_complain_detail', 'ASC')
                             ->get('tblcs_complain_detail')->result_array();
            foreach ($rows as $r) {
                $barang[$r['id_complain']][] = $r['sku'] . ' x' . (int) $r['qty'];
            }
        }

        $sheet_obj = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $sheet_obj->getActiveSheet();
        $sheet->setTitle('Komplain CS');

        $judul = ['ID', 'Tanggal Komplain', 'No. Resi', 'No. Pesanan', 'Marketplace', 'Toko', 'Jenis', 'Kategori', 'Keterangan',
                  'Sumber', 'Hasil Cek CCTV', 'Catatan Investigasi', 'Barang', 'Nominal', 'Picker', 'Packer',
                  'Tanggal Input', 'No. Absen Input', 'Nama Penginput'];
        $sheet->fromArray($judul, null, 'A1');

        $baris = 2;
        foreach ($items as $it) {
            $sheet->fromArray([
                'K-' . str_pad((int) $it['id_complain'], 5, '0', STR_PAD_LEFT),
                $it['tgl_efektif'] ? date('d/m/Y', strtotime($it['tgl_efektif'])) : '',
                $it['no_resi'],
                preg_replace('/^SP-/i', '', (string) $it['no_pesanan']),
                trim((string) $it['nama_marketplace']),
                $it['toko'],
                $it['jenis_komplain'] ?: 'CCTV',
                $it['kategori_komplain'],
                $it['detail_lainnya'],
                $it['sumber_komplain'],
                $it['hasil_investigasi'] ?: 'Belum dicek',
                $it['catatan_penanganan'],
                implode('; ', $barang[$it['id_complain']] ?? []),
                (float) $it['nominal_total'],
                $this->_label_orang($it['nama_picker']),
                $this->_label_orang($it['nama_qc'] ?: $it['nama_packer']),
                $it['created_at'] ? date('d/m/Y H:i:s', strtotime($it['created_at'])) : '',
                $it['no_absen_input'],
                $it['nama_penginput'],
            ], null, 'A' . $baris);
            // Resi/pesanan berupa teks panjang: jangan sampai dibaca Excel sebagai angka
            $sheet->setCellValueExplicit('C' . $baris, (string) $it['no_resi'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $baris, preg_replace('/^SP-/i', '', (string) $it['no_pesanan']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $baris++;
        }

        $akhir = max($baris - 1, 1);
        $sheet->getStyle('A1:S1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);
        $sheet->getStyle('N2:N' . $akhir)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:S' . $akhir);
        foreach (range('A', 'S') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Komplain_CS_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet_obj))->save('php://output');
        exit;
    }

    /** Filter dari request (dipakai tabel dan kartu ringkasan agar selalu sama). */
    private function _filter_dari_request()
    {
        return [
            'kategori'    => $this->_filter_array('f_kategori'),
            'sumber'      => $this->_filter_array('f_sumber'),
            'jenis'       => $this->_filter_array('f_jenis'),
            'marketplace' => $this->_filter_array('f_marketplace'),
            'hasil'       => $this->_filter_array('f_hasil'),
            'tab'         => in_array($this->input->post('tab'), ['perlu', 'retur', 'selesai'], true) ? $this->input->post('tab') : 'semua',
        ];
    }

    /** Kartu ringkasan di atas tabel, mengikuti filter yang sedang aktif. */
    public function get_complain_ringkasan()
    {
        // Tab tidak ikut WHERE: jumlah semua tab dihitung sekaligus, kartu mengikuti tab aktif
        $filter = $this->_filter_dari_request();
        $tab = $filter['tab'];
        unset($filter['tab']);
        $this->_apply_complain_filters(
            $this->input->post('reportrange') ?: '',
            $this->input->post('search_text') ?: '',
            $filter,
            true
        );
        $k = $this->_kondisi_tab($tab) ?: '1=1';
        $this->db->select(
            "COUNT(*) AS jml_semua,
             COALESCE(SUM(" . $this->_kondisi_tab('perlu') . "), 0) AS jml_perlu,
             COALESCE(SUM(" . $this->_kondisi_tab('retur') . "), 0) AS jml_retur,
             COALESCE(SUM(" . $this->_kondisi_tab('selesai') . "), 0) AS jml_selesai,
             COALESCE(SUM($k), 0) AS total,
             COALESCE(SUM($k AND c.hasil_investigasi = 'QC Salah'), 0) AS qc_salah,
             COALESCE(SUM($k AND c.hasil_investigasi = 'QC Benar'), 0) AS qc_benar,
             COALESCE(SUM($k AND c.hasil_investigasi IS NULL), 0) AS belum_dicek,
             COALESCE(SUM(CASE WHEN $k THEN c.nominal_total END), 0) AS nominal",
            false
        );
        $r = $this->db->get()->row_array();

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'total'       => (int) $r['total'],
            'qc_salah'    => (int) $r['qc_salah'],
            'qc_benar'    => (int) $r['qc_benar'],
            'belum_dicek' => (int) $r['belum_dicek'],
            'nominal'     => (float) $r['nominal'],
            'tab'         => [
                'semua'   => (int) $r['jml_semua'],
                'perlu'   => (int) $r['jml_perlu'],
                'retur'   => (int) $r['jml_retur'],
                'selesai' => (int) $r['jml_selesai'],
            ],
        ]));
    }

    /** "NADYA - QC - 0107" / "0353 - JOHANSEN - IT" -> "0107 - NADYA" / "0353 - JOHANSEN" (kapital). */
    private function _label_orang($teks)
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return '';
        }
        $p = $this->_pecah_nama_user($teks);
        $nama = mb_strtoupper($p['nama']);
        return $p['absen'] !== '-' ? $p['absen'] . ' - ' . $nama : $nama;
    }

    /** Avatar inisial + "no.absen - NAMA" untuk sel tabel. */
    private function _sel_orang($teks)
    {
        $label = $this->_label_orang($teks);
        if ($label === '') {
            return '<span class="kp-muted">&ndash;</span>';
        }
        $nama = mb_strtoupper($this->_pecah_nama_user($teks)['nama']);
        return '<span class="kp-person"><span class="kp-avatar">' . htmlspecialchars(mb_substr($nama, 0, 1)) . '</span>'
            . htmlspecialchars($label) . '</span>';
    }

    /** Tombol salin kecil di samping nomor (diproses handler .kp-copy di view). */
    private function _tombol_salin($nilai, $kelas = '')
    {
        return '<button type="button" class="kp-copy' . ($kelas !== '' ? ' ' . $kelas : '') . '" data-copy="' . htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8') . '" title="Salin"><i class="fa fa-files-o"></i></button>';
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

        $r = $this->db->query("SELECT CONCAT(status_retur, '|', COALESCE(status_detail, ''), '|', COALESCE(tanggal_resiretur, '')) AS s FROM tblresiretur "
            . "WHERE noresi = CONVERT(? USING latin1) ORDER BY id_resiretur DESC LIMIT 1", [$complain['no_resi']])->row_array();
        $retur_terbaru = $r['s'] ?? null;
        $retur_info = $this->_peta_retur([$complain])[(int) $id_complain] ?? null;
        $info_status = $this->_info_status($complain['status_komplain'], $complain['created_at'], $complain['tgl_status'], $retur_terbaru);
        $riwayat_status = $this->db->select('l.status_dari, l.status_ke, l.catatan, l.waktu, u.name AS nama_user')
                                   ->from('tblcs_complain_status_log l')
                                   ->join('tbluser u', 'u.id_user = l.oleh', 'left')
                                   ->where('l.id_complain', $id_complain)
                                   ->order_by('l.id_log', 'DESC')
                                   ->get()->result_array();

        echo json_encode([
            'success' => true,
            'complain' => $complain,
            'info_status' => $info_status,
            'retur_info' => $retur_info,
            'riwayat_status' => $riwayat_status,
            'boleh_ubah_status' => $this->_boleh_ubah_status(),
            'items' => $items,
            'lampiran' => $lampiran,
            'komplain_lain' => $komplain_lain,
            'jejak_resi' => $this->_jejak_resi($complain['no_resi'])
        ]);
        exit;
    }

    /**
     * Jejak resi untuk bagian Investigasi: waktu picker mengambil barang dan waktu packer
     * memacking, beserta status performa masing-masing (1 SKU PICKER / NORMAL PICKER,
     * 1 SKU / NORMAL / PAYUNG / QTY BANYAK ...). Bersumber dari tblresiambilbarang dan
     * tblpacking (bukan dari isian komplain), nama status dari tblmasterstatusperforma.
     */
    private function _jejak_resi($no_resi)
    {
        $kosong = ['ada' => false];
        if (empty($no_resi)) {
            return $kosong;
        }

        $resi = $this->db->select('id_printresi')->get_where('tblprintresi', ['noresi' => $no_resi])->row_array();
        if (!$resi) {
            return $kosong;
        }

        $ambil = $this->db->select('rab.tanggal_resiambilbarang AS waktu, m.kode_status')
                          ->from('tblresiambilbarang rab')
                          ->join('tblmasterstatusperforma m', 'm.id_statusperforma = rab.status_performa_id', 'left')
                          ->where('rab.id_resi', $resi['id_printresi'])
                          ->get()->row_array();

        $packing = $this->db->select('p.tanggal_packing AS waktu, m.kode_status')
                            ->from('tblpacking p')
                            ->join('tblmasterstatusperforma m', 'm.id_statusperforma = p.status_performa_id', 'left')
                            ->where('p.id_resi', $resi['id_printresi'])
                            ->order_by('p.tanggal_packing', 'DESC')
                            ->limit(1)
                            ->get()->row_array();

        return [
            'ada'            => true,
            'waktu_picker'   => $ambil['waktu'] ?? null,
            'status_picker'  => $this->_label_status_performa($ambil['kode_status'] ?? null, '_PACKER'),
            'waktu_packer'   => $packing['waktu'] ?? null,
            'status_packer'  => $this->_label_status_performa($packing['kode_status'] ?? null, '_PACKER'),
        ];
    }

    /** 1_SKU_PACKER -> "1 SKU", NORMAL_PICKER -> "NORMAL PICKER", QTY_BANYAK -> "QTY BANYAK". */
    private function _label_status_performa($kode, $buang_akhiran)
    {
        if (empty($kode)) {
            return '';
        }
        if (substr($kode, -strlen($buang_akhiran)) === $buang_akhiran) {
            $kode = substr($kode, 0, -strlen($buang_akhiran));
        }
        return str_replace('_', ' ', $kode);
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
        // pada resi/pesanan yang sama adalah input dobel -> ditolak, tanpa opsi paksa.
        if (!$is_edit) {
            $this->db->from('tblcs_complain')->group_start()->where('no_resi', $no_resi);
            if ($no_pesanan) {
                $polos = preg_replace('/^SP-/i', '', $no_pesanan);
                $this->db->or_where_in('no_pesanan', [$no_pesanan, $polos, 'SP-' . $polos]);
            }
            $dobel = $this->db->group_end()
                              ->where('kategori_komplain', $kategori_komplain)
                              ->where('is_deleted', 0)
                              ->limit(1)->get()->row();
            if ($dobel) {
                echo json_encode([
                    'code' => 409,
                    'message' => 'Resi/pesanan ini sudah punya komplain berkategori "' . $kategori_komplain . '" (' . ($dobel->tgl_komplain ?: substr($dobel->created_at, 0, 10)) . '). Data yang sama tidak bisa disimpan dua kali.'
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

    /**
     * Tetapkan / ubah status komplain. Hanya webmaster & admin.
     * POST: id_complain, status (DANA|DANA_BARANG|TIDAK_ADA_PENGAJUAN|RETUR_BATAL), catatan.
     * Retur yang sudah dibuka (Buka Retur) mengunci status; RETUR_BATAL hanya dari DANA_BARANG.
     */
    public function set_status_komplain()
    {
        if (!$this->_boleh_ubah_status()) {
            $this->make_ajax_response(403, 'Hanya Webmaster/Admin yang dapat mengubah status komplain.');
        }
        $id = (int) $this->input->post('id_complain');
        $baru = (string) $this->input->post('status');
        $catatan = trim((string) $this->input->post('catatan'));
        $resi_retur = strtoupper(preg_replace('/\s+/', '', (string) $this->input->post('no_resi_retur')));

        if ($id <= 0 || !isset($this->status_label[$baru])) {
            $this->make_ajax_response(400, 'Data status tidak valid.');
        }
        $c = $this->db->select('id_complain, no_resi, no_resi_retur, status_komplain, created_at, tgl_status')
                      ->get_where('tblcs_complain', ['id_complain' => $id, 'is_deleted' => 0])->row_array();
        if (!$c) {
            $this->make_ajax_response(404, 'Komplain tidak ditemukan.');
        }

        $lama = $c['status_komplain'];
        // Status sama hanya boleh bila untuk melengkapi/mengoreksi resi pengembalian barang
        $koreksi_resi = $lama === 'DANA_BARANG' && $baru === 'DANA_BARANG' && $resi_retur !== '' && $resi_retur !== (string) $c['no_resi_retur'];
        if ($lama === $baru && !$koreksi_resi) {
            $this->make_ajax_response(400, 'Status sudah ' . $this->status_label[$baru] . '.');
        }
        if ($baru === 'DANA_BARANG') {
            if ($resi_retur === '') {
                $this->make_ajax_response(400, 'No. resi pengembalian barang wajib diisi untuk Pengembalian Dana dan Barang.');
            }
            if (strlen($resi_retur) > 100) {
                $this->make_ajax_response(400, 'No. resi pengembalian barang terlalu panjang.');
            }
        }
        if ($lama === 'DANA_BARANG') {
            $resi_cari = $c['no_resi'];
            $r = $this->db->query("SELECT status_retur FROM tblresiretur WHERE noresi = CONVERT(? USING latin1) ORDER BY id_resiretur DESC LIMIT 1", [$resi_cari])->row_array();
            if (($r['status_retur'] ?? '') === 'Buka Retur') {
                $this->make_ajax_response(400, 'Retur sudah dibuka (selesai), status tidak bisa diubah lagi.');
            }
        }
        if ($baru === 'RETUR_BATAL') {
            if ($lama !== 'DANA_BARANG') {
                $this->make_ajax_response(400, 'Retur Batal hanya untuk komplain berstatus Pengembalian Dana dan Barang.');
            }
            if ($catatan === '') {
                $this->make_ajax_response(400, 'Alasan wajib diisi untuk membatalkan retur.');
            }
        }

        $sekarang = date('Y-m-d H:i:s');
        $uid = (int) $this->data['user']['id_user'];
        $this->db->trans_start();
        $ubah = [
            'status_komplain' => $baru,
            'tgl_status'      => $lama === $baru ? $c['tgl_status'] : $sekarang,
            'status_by'       => $uid,
            'updated_by'      => $uid,
        ];
        if ($baru === 'DANA_BARANG') {
            $ubah['no_resi_retur'] = $resi_retur;
        }
        $this->db->where('id_complain', $id)->update('tblcs_complain', $ubah);
        $this->db->insert('tblcs_complain_status_log', [
            'id_complain' => $id,
            'status_dari' => $lama,
            'status_ke'   => $baru,
            'catatan'     => mb_substr(trim(($baru === 'DANA_BARANG' ? 'Resi retur: ' . $resi_retur . ($catatan !== '' ? '. ' : '') : '') . $catatan), 0, 255) ?: null,
            'oleh'        => $uid,
            'waktu'       => $sekarang,
        ]);
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->make_ajax_response(500, 'Gagal menyimpan status.');
        }
        $this->make_ajax_response(200, $koreksi_resi ? 'Resi pengembalian barang diperbarui.' : 'Status diubah menjadi ' . $this->status_label[$baru] . '.');
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
            // Nomor pesanan boleh sebagian (minimal 5 karakter): cocokkan bagian mana pun
            if (!$via_pesanan && strlen($noresi) >= 5) {
                $kata = str_replace(['%', '_'], ['\%', '\_'], $noresi);
                $cocok = $this->db->query(
                    "SELECT d.no_pesanan, MAX(pr.noresi) AS noresi FROM tbldetailprintresi d
                     JOIN tblprintresi pr ON pr.id_printresi = d.id_resi
                     WHERE d.no_pesanan LIKE ? GROUP BY d.no_pesanan ORDER BY MAX(pr.id_printresi) DESC LIMIT 6",
                    ['%' . $kata . '%']
                )->result_array();

                if (count($cocok) === 1) {
                    $via_pesanan = ['noresi' => $cocok[0]['noresi']];
                } elseif (count($cocok) > 1) {
                    $daftar = array_map(function ($c) { return preg_replace('/^SP-/i', '', $c['no_pesanan']); }, array_slice($cocok, 0, 5));
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ada ' . (count($cocok) > 5 ? 'lebih dari 5' : count($cocok)) . ' pesanan yang cocok: ' . implode(', ', $daftar) . '. Ketik nomor lebih lengkap.'
                    ]);
                    exit;
                }
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
            // Dicocokkan lewat no. resi maupun no. pesanan (dengan/tanpa awalan SP-)
            $pesanan_cari = array_values(array_filter([
                $res['no_pesanan'],
                preg_replace('/^SP-/i', '', (string) $res['no_pesanan']),
                'SP-' . preg_replace('/^SP-/i', '', (string) $res['no_pesanan']),
            ]));
            $this->db->select('id_complain, kategori_komplain, tgl_komplain, created_at')
                     ->from('tblcs_complain')
                     ->group_start()
                         ->where('no_resi', $res['noresi']);
            if (!empty($res['no_pesanan'])) {
                $this->db->or_where_in('no_pesanan', $pesanan_cari);
            }
            $existing = $this->db->group_end()
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
