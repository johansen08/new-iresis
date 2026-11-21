<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Daftar Retur Penjualan" (TIM RETUR) — daftar retur ekspedisi yang
 * masih ditunggu, lengkap dengan umur dari tanggal retur. Hanya membaca data.
 * Definisi retur ekspedisi, urutan sumber tanggal, dan batas umur ada di
 * Daftar_retur_fcd.
 */
class Daftar_retur extends MY_Controller
{
    const URI = 'retur/daftar-retur-penjualan';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('daftar_retur_fcd');
    }

    /** Halaman menu. */
    public function index()
    {
        $this->show(array(
            'umur_oranye' => Daftar_retur_fcd::UMUR_ORANYE,
            'umur_merah'  => Daftar_retur_fcd::UMUR_MERAH,
        ));
    }

    /** Endpoint DataTables server-side. */
    public function get_data()
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        if (!$this->_boleh()) {
            echo json_encode(array('draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => array(),
                'error' => 'Anda tidak punya akses ke menu ini.'));
            return;
        }

        list($start, $end) = $this->_rentang();

        // Indeks kolom DataTables -> kolom SQL ('umur' ditangani khusus di model).
        $urut = array(
            // kolom 0 = kotak centang (tidak bisa diurutkan)
            1 => 'tanggal_pesan', 2 => 'tanggal_retur', 3 => 'tanggal_buka', 4 => 'no_pesanan', 5 => 'no_resi',
            6 => 'kurir', 7 => 'marketplace', 8 => 'nama_toko', 9 => 'nilai',
            10 => 'umur', 11 => 'posisi',
        );
        $kol = 'umur';
        $dir = 'desc';
        $order = $this->input->post('order');
        if (!empty($order) && is_array($order)) {
            $o = end($order);
            if (isset($urut[(int) $o['column']])) {
                $kol = $urut[(int) $o['column']];
                $dir = ($o['dir'] === 'asc') ? 'asc' : 'desc';
            }
        }

        $cari = $this->input->post('search');
        $cari = is_array($cari) && isset($cari['value']) ? trim($cari['value']) : '';

        $limit = (int) $this->input->post('length');
        if ($limit < 1 || $limit > 500) $limit = 25;
        $offset = max(0, (int) $this->input->post('start'));

        $status = $this->input->post('status');
        $status = is_string($status) ? $status : '';

        // Jenis retur: 'B,K' (centang multi). Kosong/tak valid = keduanya; kirim '-' = tidak ada yang dicentang.
        $jenis_raw = $this->input->post('jenis');
        $jenis = array(Daftar_retur_fcd::JENIS_BIASA, Daftar_retur_fcd::JENIS_KOMPLAIN);
        if (is_string($jenis_raw) && $jenis_raw !== '') {
            $jenis = array_values(array_intersect(explode(',', $jenis_raw), $jenis));
        }

        // "Pilih semua hasil": kembalikan daftar resi seluruh hasil filter, tanpa paging.
        if ($this->input->post('ids_saja') === '1') {
            list($semua) = $this->daftar_retur_fcd->get_list(array(
                'start' => $start, 'end' => $end, 'status' => $status, 'search' => $cari,
                'segarkan' => false, 'jenis' => $jenis,
                'order' => 'umur', 'dir' => 'desc', 'offset' => 0, 'limit' => PHP_INT_MAX,
            ));
            echo json_encode(array('resi' => array_map(function ($x) { return (string) $x->no_resi; }, $semua)));
            return;
        }

        list($rows, $total_filter, $total, $tab, $waktu) = $this->daftar_retur_fcd->get_list(array(
            'start' => $start, 'end' => $end, 'status' => $status, 'search' => $cari,
            'segarkan' => $this->input->post('segarkan') === '1', 'jenis' => $jenis,
            'order' => $kol, 'dir' => $dir, 'offset' => $offset, 'limit' => $limit,
        ));

        $data = array();
        foreach ($rows as $r) {
            $data[] = array(
                '<input type="checkbox" class="dr-pilih" data-resi="' . $this->_h($r->no_resi) . '">',
                $this->_sel_tanggal($r->tanggal_pesan),
                $this->_sel_tanggal($r->tanggal_retur) . $this->_label_sumber($r->sumber_tanggal),
                $this->_sel_tanggal($r->tanggal_buka),
                $this->_salinan($this->_ringkas_pesanan($r->no_pesanan), false, $r->no_pesanan),
                $this->_salinan($r->no_resi, true),
                $this->_h($r->kurir ?: '-'),
                $this->_h($r->marketplace ?: '-'),
                '<span class="dr-toko" title="' . $this->_h($r->nama_toko) . '">' . $this->_h($r->nama_toko ?: '-') . '</span>',
                number_format((float) $r->nilai, 0, ',', '.'),
                $this->_badge_umur($r),
                $this->_label_posisi($r->posisi) . $this->_pill_komplain($r),
            );
        }

        echo json_encode(array(
            'draw'            => (int) $this->input->post('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total_filter,
            'tab_counts'      => $tab,
            'dihitung_pada'   => date('H:i:s', $waktu),
            'data'            => $data,
        ));
    }

    /**
     * GET: start_date, end_date, status, jenis, q (sama dengan layar). Unduh semua baris yang
     * cocok sebagai xlsx rapi: header tebal rata tengah, garis tegak dan datar. Urut umur terbesar.
     */
    public function export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (!$this->_boleh()) {
            while (ob_get_level() > 0) ob_end_clean();
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
        $s = $this->input->get('start_date');
        $e = $this->input->get('end_date');
        $start = ($ok($s) ? $s : date('Y-01-01')) . ' 00:00:00';
        $end   = ($ok($e) ? $e : date('Y-m-d')) . ' 23:59:59';

        $status = $this->input->get('status');
        $status = is_string($status) ? $status : '';
        $cari = $this->input->get('q');
        $cari = is_string($cari) ? trim($cari) : '';

        $jenis = array(Daftar_retur_fcd::JENIS_BIASA, Daftar_retur_fcd::JENIS_KOMPLAIN);
        $jenis_raw = $this->input->get('jenis');
        if (is_string($jenis_raw) && $jenis_raw !== '') {
            $jenis = array_values(array_intersect(explode(',', $jenis_raw), $jenis));
        }

        list($rows) = $this->daftar_retur_fcd->get_list(array(
            'start' => $start, 'end' => $end, 'status' => $status, 'search' => $cari,
            'segarkan' => false, 'jenis' => $jenis,
            'order' => 'umur', 'dir' => 'desc', 'offset' => 0, 'limit' => PHP_INT_MAX,
        ));

        $label_posisi = array(
            Daftar_retur_fcd::POSISI_SUDAH_DIBUKA   => 'Sudah dibuka',
            Daftar_retur_fcd::POSISI_SUDAH_VERIF    => 'Sudah verif',
            Daftar_retur_fcd::POSISI_BELUM_DIBUKA   => 'Belum dibuka',
            Daftar_retur_fcd::POSISI_BELUM_DITERIMA => 'Belum diterima',
        );
        $label_sumber = array('RETUR' => 'Tanggal retur', 'KIRIM' => 'Perkiraan: tgl kirim', 'ORDER' => 'Perkiraan: tgl order');

        $kolom = array('No', 'Tanggal Pesan', 'Tanggal Retur', 'Sumber Tanggal Retur', 'No. Pesanan', 'No. Resi',
            'Kurir', 'Marketplace', 'Toko', 'Nilai (Rp)', 'Umur (hari)', 'Status Retur', 'Komplain', 'Tanggal Buka Retur');
        $jml = count($kolom);
        $akhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($jml);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Daftar Retur');
        foreach ($kolom as $i => $nama) {
            $ws->setCellValueByColumnAndRow($i + 1, 1, $nama);
        }

        $teks = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
        $r = 2;
        foreach ($rows as $i => $b) {
            $ws->setCellValueByColumnAndRow(1, $r, $i + 1);
            $isi = array(
                2  => $b->tanggal_pesan ? date('d/m/Y H:i', strtotime($b->tanggal_pesan)) : '-',
                3  => $b->tanggal_retur ? date('d/m/Y H:i', strtotime($b->tanggal_retur)) : '-',
                4  => isset($label_sumber[$b->sumber_tanggal]) ? $label_sumber[$b->sumber_tanggal] : '-',
                // Teks eksplisit supaya nomor panjang tidak jadi notasi ilmiah.
                5  => (string) $b->no_pesanan,
                6  => (string) $b->no_resi,
                7  => (string) $b->kurir,
                8  => (string) $b->marketplace,
                9  => (string) $b->nama_toko,
                12 => isset($label_posisi[$b->posisi]) ? $label_posisi[$b->posisi] : (string) $b->posisi,
                13 => ((int) $b->ir_k + (int) $b->bk_k) > 0 ? 'Ya' : '-',
                14 => $b->tanggal_buka ? date('d/m/Y H:i', strtotime($b->tanggal_buka)) : '-',
            );
            foreach ($isi as $c => $v) {
                $ws->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r, $v, $teks);
            }
            $ws->setCellValueByColumnAndRow(10, $r, (float) $b->nilai);
            $ws->setCellValueByColumnAndRow(11, $r, (int) $b->umur_hari);
            $r++;
        }
        $terakhir = max(2, $r - 1);

        $rentang = 'A1:' . $akhir . $terakhir;
        $ws->getStyle($rentang)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        $ws->getStyle($rentang)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $ws->getStyle('J2:J' . $terakhir)->getNumberFormat()->setFormatCode('#,##0');
        $head = $ws->getStyle('A1:' . $akhir . '1');
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
        $ws->getRowDimension(1)->setRowHeight(26);
        foreach (array(6, 17, 17, 20, 24, 22, 14, 14, 24, 14, 12, 16, 11, 19) as $i => $w) {
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $ws->freezePane('A2');
        if ($r > 2) {
            $ws->setAutoFilter('A1:' . $akhir . $terakhir);
        }

        $nama_berkas = 'daftar-retur-penjualan-' . date('Ymd-His') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit();
    }

    /**
     * POST: start_date, end_date, jenis, q (sama dengan layar, tab Sudah dibuka), resi (dicentang, dipisah koma). Halaman cetak A4
     * serah terima Retur -> Restok (tab baru): barang yang dibuka lalu kembali ke display, per SKU.
     * Hanya membaca; tidak mengubah status apa pun.
     */
    public function serah_cetak()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (!$this->_boleh()) {
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
        $s = $this->input->post('start_date');
        $e = $this->input->post('end_date');
        $cari = $this->input->post('q');
        $jenis = array(Daftar_retur_fcd::JENIS_BIASA, Daftar_retur_fcd::JENIS_KOMPLAIN);
        $jenis_raw = $this->input->post('jenis');
        if (is_string($jenis_raw) && $jenis_raw !== '') {
            $jenis = array_values(array_intersect(explode(',', $jenis_raw), $jenis));
        }

        list($rows) = $this->daftar_retur_fcd->get_list(array(
            'start' => ($ok($s) ? $s : date('Y-01-01')) . ' 00:00:00',
            'end' => ($ok($e) ? $e : date('Y-m-d')) . ' 23:59:59',
            'status' => Daftar_retur_fcd::POSISI_SUDAH_DIBUKA, 'search' => is_string($cari) ? trim($cari) : '',
            'segarkan' => false, 'jenis' => $jenis,
            'order' => 'umur', 'dir' => 'desc', 'offset' => 0, 'limit' => PHP_INT_MAX,
        ));

        // Hanya resi yang dicentang DAN masih tampil di filter ini (seperti serah terima Paket Cancel).
        $pilih = $this->input->post('resi');
        $pilih = (is_string($pilih) && $pilih !== '') ? array_flip(explode(',', $pilih)) : array();
        $resi = array();
        foreach ($rows as $r) {
            if (isset($pilih[$r->no_resi])) $resi[] = $r->no_resi;
        }

        // Nama petugas: "NAMA - ROLE - NOABSEN" -> "NOABSEN - NAMA - ROLE" (sama dengan serah terima paket cancel).
        $nama = isset($this->data['user']['name']) ? (string) $this->data['user']['name'] : '';
        $p = array_map('trim', explode(' - ', $nama));
        $petugas = count($p) >= 3 ? end($p) . ' - ' . $p[0] . ' - ' . $p[1] : $nama;

        // Kertas hanya berisi SKU + qty yang berstatus KE_DISPLAY dan belum di-ACC accounting (status_acc = 0),
        // sama dengan isi tab Sudah dibuka. Baris reject / bukan barang kita / dll tidak ikut.
        $this->load->model('stok_retur_buka_fcd');
        $stok = $this->stok_retur_buka_fcd->stok($resi, true);
        $masuk = (int) $stok['ringkas']['resi'];

        $this->load->view('daftar_retur/serah_cetak', array(
            'det' => $this->stok_retur_buka_fcd->baris_serah_terima($stok['rows']),
            'sub' => count($resi) . ' resi dicentang · ' . $masuk . ' resi ikut serah terima'
                . (count($resi) > $masuk ? ' (' . (count($resi) - $masuk) . ' dilewati: tanpa barang KE_DISPLAY / sudah di-ACC accounting)' : '')
                . ' · ' . count($stok['rows']) . ' SKU',
            'petugas' => $petugas,
            'tanggal' => date('Y-m-d'),
        ));
    }

    // ------------------------------------------------------------ utilitas

    /** Role user harus punya akses ke menu ini (sidebar saja tidak cukup untuk endpoint POST). */
    private function _boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;

        $menu = $this->db->get_where('menu', array('uri' => self::URI))->row();
        if (!$menu) return false;

        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))
            ->count_all_results('roleaccess') > 0;
    }

    /** Rentang tanggal 'Y-m-d H:i:s' (tanggal pesan; baris Sudah dibuka = tanggal buka retur); default 1 Januari tahun ini s/d hari ini. */
    private function _rentang()
    {
        $start = $this->input->post('start_date');
        $end   = $this->input->post('end_date');

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
        $start = $ok($start) ? $start : date('Y-01-01');
        $end   = $ok($end)   ? $end   : date('Y-m-d');

        return array($start . ' 00:00:00', $end . ' 23:59:59');
    }

    /** Tanggal dua baris: tanggal tebal, jam redup di bawahnya. */
    private function _sel_tanggal($t)
    {
        if (!$t) return '-';
        $ts = strtotime($t);
        return '<span class="dr-t1">' . date('d/m/y', $ts) . '</span><span class="dr-t2">' . date('H:i', $ts) . '</span>';
    }

    /** Teks + ikon clipboard kecil; satu klik menyalin (ditangani di view lewat data-salin). */
    /**
     * No pesanan untuk tampilan web: buang awalan marketplace (TT-, SP-, TP-, LZ-)
     * dan, khusus TikTok/Tokopedia/Lazada, buang juga akhiran "-angka" (kode toko).
     * Shopee hanya dibuang awalannya. Data di DB dan Excel tetap lengkap.
     */
    private function _ringkas_pesanan($no)
    {
        $no = trim((string) $no);
        if (!preg_match('/^(TT|SP|TP|LZ)-(.+)$/i', $no, $m)) return $no;
        $isi = $m[2];
        if (strtoupper($m[1]) !== 'SP') $isi = preg_replace('/-\d+$/', '', $isi);
        return $isi;
    }

    private function _salinan($teks, $tebal, $lengkap = null)
    {
        $teks = trim((string) $teks);
        if ($teks === '') return '-';

        $isi = $tebal ? '<strong>' . $this->_h($teks) . '</strong>' : $this->_h($teks);
        $judul = ($lengkap !== null && trim((string) $lengkap) !== $teks) ? ' title="' . $this->_h($lengkap) . '"' : '';
        return '<span class="dr-no"' . $judul . '><span class="dr-nt">' . $isi . '</span>'
            . '<i class="fa fa-clipboard dr-salin" data-salin="' . $this->_h($teks)
            . '" title="Salin" role="button" tabindex="0"></i></span>';
    }

    private function _h($t)
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Umur dari tanggal retur. Warna & keterangan hanya untuk paket yang belum
     * dibuka -- yang sudah dibuka barangnya sudah kembali, umur bukan alasan klaim.
     * Gaya ada di view (kelas dr-*).
     */
    private function _badge_umur($r)
    {
        $umur = (int) $r->umur_hari;
        $teks = number_format($umur, 0, ',', '.') . ' hari';

        if ($r->posisi === Daftar_retur_fcd::POSISI_SUDAH_DIBUKA || $r->posisi === Daftar_retur_fcd::POSISI_SUDAH_VERIF) {
            return '<span class="dr-umur dr-umur-selesai">' . $teks . '</span>';
        }
        if ($umur >= Daftar_retur_fcd::UMUR_MERAH) {
            return '<span class="dr-umur dr-umur-merah">' . $teks . '</span>'
                . '<div><span class="dr-pill dr-pill-merah">Wajib klaim</span></div>';
        }
        if ($umur >= Daftar_retur_fcd::UMUR_ORANYE) {
            return '<span class="dr-umur dr-umur-oranye">' . $teks . '</span>'
                . '<div><span class="dr-pill dr-pill-oranye">Segera ditindak</span></div>';
        }
        return '<span class="dr-umur dr-umur-aman">' . $teks . '</span>';
    }

    private function _label_posisi($posisi)
    {
        switch ($posisi) {
            case Daftar_retur_fcd::POSISI_SUDAH_DIBUKA:
                return '<span class="dr-st dr-st-hijau" title="Sudah dibuka, belum diverifikasi accounting">Sudah dibuka</span>';
            case Daftar_retur_fcd::POSISI_SUDAH_VERIF:
                return '<span class="dr-st dr-st-hijau" title="Sudah dibuka dan diverifikasi accounting">Sudah verif</span>';
            case Daftar_retur_fcd::POSISI_BELUM_DIBUKA:
                return '<span class="dr-st dr-st-biru" title="Terima, belum dibuka">Belum dibuka</span>';
            default:
                return '<span class="dr-st dr-st-abu">Belum diterima</span>';
        }
    }

    /** Penanda kecil bila resi pernah di-scan sebagai retur komplain. */
    private function _pill_komplain($r)
    {
        if ((int) $r->ir_k + (int) $r->bk_k === 0) return '';
        return '<div><span class="dr-pill dr-pill-komplain" title="Ada scan retur komplain untuk resi ini">Komplain</span></div>';
    }

    private function _label_sumber($sumber)
    {
        switch ($sumber) {
            case 'RETUR': return '';   // tanggal retur sebenarnya: tidak perlu keterangan tambahan
            case 'KIRIM': return '<div class="dr-sb dr-sb-kira" title="Perkiraan: tanggal retur tidak tersedia, dipakai tanggal kirim">≈ tgl kirim</div>';
            default:      return '<div class="dr-sb dr-sb-kasar" title="Perkiraan: tanggal retur tidak tersedia, dipakai tanggal order (umur cenderung berlebih)">≈ tgl order</div>';
        }
    }
}
