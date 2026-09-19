<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PICKER -> "Laporan Lost Scan Picker".
 *
 * Antrean resi yang ditolak karena belum di-picker (dilaporkan packer/HO).
 * Tim picker menentukan siapa picker-nya lewat tombol Tambahkan Picker;
 * model membuat baris picking atas nama picker itu dan mencatat lost scan
 * PICKER. Setelah itu packer bisa scan ulang, lalu HO -- urutan ini dijaga
 * oleh penjaga NOT_PICKED / NOT_PACKED yang sudah ada di masing-masing menu.
 */
class Lost_scan_picker extends MY_Controller
{
    /**
     * Role yang boleh membuka menu ini: webmaster (1), admin (2), tim retur
     * (6) -- sama dengan SCAN COMBINED / Master Picker / Resi Pending. Harus
     * sejalan dengan MY_Controller::run_lost_scan_picker_migration().
     */
    const ROLE_BOLEH = [1, 2, 6];

    public function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('lost_scan_picker_fcd');
        $this->load->model('picking_fcd');
        $this->load->model('Notification');
    }

    /**
     * Penolakannya tetap JSON valid: halaman lewat view dengan penanda
     * akses_ditolak (show_404 mengirim HTML dan merusak SPA), endpoint data
     * lewat make_ajax_response. Pola sama dengan Masalah_picker_new.
     */
    private function tolak_role_tanpa_akses()
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
                'view'    => $this->load->view('lost_scan_picker/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Laporan Lost Scan Picker.');
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
        $data['reportrange'] = $this->rentang_default();
        $data['list_picker'] = $this->picking_fcd->get_picker('AKTIF')->result_array();
        $data['ringkasan']   = $this->lost_scan_picker_fcd->ringkasan_pending();

        $this->show($data, 'lost_scan_picker/index');
    }

    /** Endpoint DataTables server-side untuk kedua tab. */
    public function get_data()
    {
        $tab = ($this->input->post('tab') === 'selesai') ? 'selesai' : 'pending';
        list($start_date, $end_date) = $this->pecah_rentang($this->input->post('reportrange'));

        $draw   = intval($this->input->post('draw'));
        $order  = $this->input->post('order');
        $search = $this->input->post('search');

        $params = [
            'tab'        => $tab,
            'start'      => intval($this->input->post('start')),
            'length'     => intval($this->input->post('length')),
            'search'     => trim((string) ($search['value'] ?? '')),
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'order'      => null,
            'dir'        => 'desc',
        ];

        // Urutan kolom harus sama dengan <thead> di view:
        // pending: No | Waktu Lapor | No Resi | Dilaporkan oleh | Item | Aksi
        // selesai: No | Waktu Lapor | No Resi | Dilaporkan oleh | Item | Picker | Diproses | Status
        $valid_columns = ($tab === 'pending')
            ? [1 => 't.waktu_lapor', 2 => 't.noresi', 3 => 'u.name']
            : [1 => 't.waktu_lapor', 2 => 't.noresi', 3 => 'u.name', 5 => 'pg.nama_pegawai', 6 => 't.waktu_proses', 7 => 't.status'];
        if (!empty($order[0]) && isset($valid_columns[(int) $order[0]['column']])) {
            $params['order'] = $valid_columns[(int) $order[0]['column']];
            $params['dir']   = strtolower($order[0]['dir']) === 'asc' ? 'asc' : 'desc';
        }

        $hasil = $this->lost_scan_picker_fcd->daftar($params);
        $item  = $this->lost_scan_picker_fcd->item_resi(array_column($hasil['rows'], 'id_printresi'));

        $nomor = $params['start'] + 1;
        $data_table = [];
        foreach ($hasil['rows'] as $r) {
            $id = (int) $r['id_pending'];
            $noresi = $this->e($r['noresi']);
            $daftar_item = $item[(int) $r['id_printresi']] ?? [];

            $baris = [
                $nomor++ . '.',
                $this->sel_waktu_lapor($r['waktu_lapor'], $tab === 'pending'),
                '<span class="lsp-resi">' . $noresi . '</span>',
                $this->badge_sumber($r['sumber']) . ' ' . $this->e($r['nama_pelapor'] ?: '-'),
                $this->sel_item($daftar_item),
            ];

            if ($tab === 'pending') {
                // Atribut data dipakai modal untuk menampilkan konteks laporan
                // tanpa request tambahan.
                $attr = ' data-id="' . $id . '" data-noresi="' . $noresi . '"'
                    . ' data-sumber="' . $this->e($r['sumber']) . '"'
                    . ' data-pelapor="' . $this->e($r['nama_pelapor'] ?: '-') . '"'
                    . ' data-waktu="' . $this->e(!empty($r['waktu_lapor']) ? date('d/m/Y H:i', strtotime($r['waktu_lapor'])) : '-') . '"'
                    . ' data-usia="' . $this->e($this->usia_teks($r['waktu_lapor'])) . '"'
                    . ' data-jumlah-item="' . count($daftar_item) . '"';

                if (!empty($r['sudah_picked'])) {
                    $baris[] = '<button type="button" class="btn btn-sm btn-default btn-block btn-tandai-selesai"' . $attr . '>'
                        . '<i class="fa fa-check"></i> Tandai Selesai</button>'
                        . '<small class="text-muted lsp-catatan-aksi" title="Baris picking sudah dibuat di luar alur (mis. SCAN COMBINED)">'
                        . '<i class="fa fa-info-circle"></i> sudah di-picker di luar alur</small>';
                } else {
                    $baris[] = '<button type="button" class="btn btn-sm btn-warning btn-block btn-tambah-picker"' . $attr . '>'
                        . '<i class="fa fa-user-plus"></i> Tambahkan Picker</button>';
                }
            } else {
                $baris[] = '<strong>' . $this->e($r['nama_picker'] ?: ('PEGAWAI #' . (int) $r['kode_picker'])) . '</strong>';
                $baris[] = $this->e($r['nama_pemroses'] ?: '-') . '<br><small class="text-muted">'
                    . (!empty($r['waktu_proses']) ? date('d/m/Y H:i', strtotime($r['waktu_proses'])) : '-') . '</small>';
                $baris[] = ($r['status'] === 'SELESAI_LUAR')
                    ? '<span class="label label-default" title="Picking sudah ada sebelum diproses (mis. lewat SCAN COMBINED); laporan ditutup tanpa memilih picker">SELESAI (di luar alur)</span>'
                    : '<span class="label label-success">SELESAI</span>';
            }

            $data_table[] = $baris;
        }

        $this->kirim_json([
            'draw'            => $draw,
            'recordsTotal'    => $hasil['total'],
            'recordsFiltered' => $hasil['total'],
            'data'            => $data_table,
            'ringkasan'       => $this->lost_scan_picker_fcd->ringkasan_pending(),
        ]);
    }

    /** Tombol Tambahkan Picker / Tandai Selesai. */
    public function tambah_picker()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $id_pending  = (int) $this->input->post('id_pending');
        $kode_picker = (int) $this->input->post('kode_picker');
        if ($id_pending <= 0) {
            $this->make_ajax_response(400, 'ID laporan tidak valid');
        }

        $hasil = $this->lost_scan_picker_fcd->tambah_picker($id_pending, $kode_picker, $this->data['user']);

        if (isset($hasil['error'])) {
            $this->make_ajax_response($hasil['code'], $hasil['message'], ['kode' => $hasil['kode']]);
        }

        // Notifikasi ke packer (role packer membaca kategori GENERAL, admin
        // membaca semua). Gagal kirim tidak membatalkan proses yang sudah commit.
        if ($hasil['status'] === 'SELESAI') {
            try {
                $this->Notification->send(
                    'Resi ' . $hasil['noresi'] . ' sudah ditambahkan picker (' . $hasil['nama_picker'] . '). Silakan scan ulang di packer.',
                    'GENERAL',
                    'Lost Scan Picker Selesai'
                );
            } catch (Throwable $e) {
                log_message('error', 'Notifikasi lost scan picker gagal: ' . $e->getMessage());
            }
        }

        $pesan = ($hasil['status'] === 'SELESAI_LUAR')
            ? 'Resi ' . $hasil['noresi'] . ' sudah di-picker di luar alur oleh ' . $hasil['nama_picker'] . ' -- laporan ditutup.'
            : 'Picker ' . $hasil['nama_picker'] . ' ditambahkan untuk resi ' . $hasil['noresi'] . '. Packer bisa scan ulang.';

        $this->make_ajax_response(201, $pesan, $hasil);
    }

    // ------------------------------------------------------------------

    private function badge_sumber($sumber)
    {
        return ($sumber === 'HO')
            ? '<span class="label label-primary">HO</span>'
            : '<span class="label label-info">PACKER</span>';
    }

    /** Selisih waktu lapor ke sekarang dalam menit (null bila kosong). */
    private function usia_menit($waktu)
    {
        if (empty($waktu) || strtotime($waktu) === FALSE) {
            return null;
        }
        return max(0, (int) floor((time() - strtotime($waktu)) / 60));
    }

    /** "baru saja" / "12 mnt" / "1 jam 5 mnt" / "2 hari" -- untuk kolom usia & modal. */
    private function usia_teks($waktu)
    {
        $menit = $this->usia_menit($waktu);
        if ($menit === null) {
            return '-';
        }
        if ($menit < 1) {
            return 'baru saja';
        }
        if ($menit < 60) {
            return $menit . ' mnt';
        }
        if ($menit < 1440) {
            $sisa = $menit % 60;
            return floor($menit / 60) . ' jam' . ($sisa ? ' ' . $sisa . ' mnt' : '');
        }
        return floor($menit / 1440) . ' hari';
    }

    /**
     * Sel "Waktu Lapor". Di tab pending ditambah badge usia menunggu;
     * data-menit dibaca JS (createdRow) untuk mewarnai baris yang sudah lama.
     * Ambang: 30 menit = kuning, 120 menit = merah.
     */
    private function sel_waktu_lapor($waktu, $dengan_usia)
    {
        if (empty($waktu)) {
            return '-';
        }
        $ts = strtotime($waktu);
        $html = '<div class="lsp-waktu">' . date('d/m H:i', $ts) . '</div>';
        if ($dengan_usia) {
            $menit = $this->usia_menit($waktu);
            $kelas = $menit >= 120 ? 'label-danger' : ($menit >= 30 ? 'label-warning' : 'label-default');
            $html .= '<span class="label ' . $kelas . ' lsp-usia" data-menit="' . $menit . '" title="Menunggu sejak ' . date('d/m/Y H:i', $ts) . '">'
                . '<i class="fa fa-clock-o"></i> ' . $this->e($this->usia_teks($waktu)) . '</span>';
        }
        return $html;
    }

    /**
     * Daftar item resi; dibungkus .lsp-items supaya modal bisa menyalinnya.
     * Rak ditaruh paling depan karena tim picker mencari berdasarkan rak.
     */
    private function sel_item(array $items)
    {
        if (empty($items)) {
            return '<div class="lsp-items"><em class="text-muted">tidak ada detail item</em></div>';
        }
        $html = '<div class="lsp-items">';
        foreach ($items as $it) {
            $html .= '<div class="lsp-item">'
                . '<span class="lsp-rak" title="Rak">' . $this->e($it['no_rak'] ?: '-') . '</span> '
                . '<strong>' . $this->e($it['sku']) . '</strong>'
                . ' <span class="lsp-qty">&times;' . (int) $it['jumlah'] . '</span>'
                . ($it['nama_sku'] !== '' ? ' <small class="text-muted">' . $this->e($it['nama_sku']) . '</small>' : '')
                . '</div>';
        }
        return $html . '</div>';
    }

    private function e($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }

    /** DataTables butuh JSON polos; buang semua buffer supaya tidak ada byte nyasar. */
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
