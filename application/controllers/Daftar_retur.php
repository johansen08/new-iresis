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
            1 => 'x.no_resi', 2 => 'x.no_pesanan', 3 => 'x.tanggal_retur', 4 => 'umur',
            5 => 'x.kurir', 6 => 'x.marketplace', 7 => 'x.nama_toko', 8 => 'x.nilai',
            9 => 'x.posisi', 10 => 'x.sumber_tanggal',
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

        list($rows, $total_filter, $total) = $this->daftar_retur_fcd->get_list(array(
            'start' => $start, 'end' => $end, 'search' => $cari,
            'order' => $kol, 'dir' => $dir, 'offset' => $offset, 'limit' => $limit,
        ));

        $data = array();
        $no = $offset;
        foreach ($rows as $r) {
            $no++;
            $data[] = array(
                $no,
                '<strong>' . $this->_h($r->no_resi) . '</strong>',
                $this->_h($r->no_pesanan ?: '-'),
                $r->tanggal_retur ? date('d/m/Y H:i', strtotime($r->tanggal_retur)) : '-',
                $this->_badge_umur($r),
                $this->_h($r->kurir ?: '-'),
                $this->_h($r->marketplace ?: '-'),
                $this->_h($r->nama_toko ?: '-'),
                'Rp ' . number_format((float) $r->nilai, 0, ',', '.'),
                $this->_label_posisi($r->posisi),
                $this->_label_sumber($r->sumber_tanggal),
            );
        }

        echo json_encode(array(
            'draw'            => (int) $this->input->post('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total_filter,
            'data'            => $data,
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

    /** Rentang tanggal retur 'Y-m-d H:i:s'; default 1 Januari tahun ini s/d hari ini. */
    private function _rentang()
    {
        $start = $this->input->post('start_date');
        $end   = $this->input->post('end_date');

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
        $start = $ok($start) ? $start : date('Y-01-01');
        $end   = $ok($end)   ? $end   : date('Y-m-d');

        return array($start . ' 00:00:00', $end . ' 23:59:59');
    }

    private function _h($t)
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Umur dari tanggal retur. Warna hanya untuk paket yang belum dibuka --
     * untuk yang sudah dibuka, barangnya sudah kembali sehingga umur tidak lagi
     * jadi alasan klaim.
     */
    private function _badge_umur($r)
    {
        $umur  = (int) $r->umur_hari;
        $teks  = number_format($umur, 0, ',', '.') . ' hari';
        $gaya  = 'display:inline-block;padding:2px 8px;border-radius:10px;font-weight:600;';

        if ($r->posisi === Daftar_retur_fcd::POSISI_SUDAH_DIBUKA) {
            return '<span style="' . $gaya . 'background:#e5e7eb;color:#6b7280;">' . $teks . '</span>';
        }
        if ($umur >= Daftar_retur_fcd::UMUR_MERAH) {
            return '<span style="' . $gaya . 'background:#dc2626;color:#fff;" title="Wajib klaim ke ekspedisi">' . $teks . '</span>';
        }
        if ($umur >= Daftar_retur_fcd::UMUR_ORANYE) {
            return '<span style="' . $gaya . 'background:#f59e0b;color:#fff;" title="Mendekati batas klaim">' . $teks . '</span>';
        }
        return '<span style="' . $gaya . 'background:#dcfce7;color:#166534;">' . $teks . '</span>';
    }

    private function _label_posisi($posisi)
    {
        switch ($posisi) {
            case Daftar_retur_fcd::POSISI_SUDAH_DIBUKA:
                return '<span class="label label-success">Sudah dibuka</span>';
            case Daftar_retur_fcd::POSISI_BELUM_DIBUKA:
                return '<span class="label label-info">Diterima, belum dibuka</span>';
            default:
                return '<span class="label label-default">Belum diterima</span>';
        }
    }

    private function _label_sumber($sumber)
    {
        switch ($sumber) {
            case 'RETUR': return '<span class="text-success">Tanggal retur</span>';
            case 'KIRIM': return '<span class="text-warning" title="Tanggal retur tidak tersedia; dipakai tanggal kirim">Perkiraan (tgl kirim)</span>';
            default:      return '<span class="text-danger" title="Tanggal retur tidak tersedia; dipakai tanggal order, umur cenderung berlebih">Perkiraan (tgl order)</span>';
        }
    }
}
