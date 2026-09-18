<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM PACKER -> "Salah Ambil Special".
 *
 * Untuk batch resi "spesial" (tepat 1 SKU dengan qty 1) yang salah diambil
 * picker, misalnya 30 resi BSBI-4 yang semuanya terambil BSBI-5. Lewat Scan
 * Resi Packer tiap resi harus discan lalu diisi modal Masalah Picker satu per
 * satu; di sini dua SKU diisi sekali, lalu semua resi tinggal discan
 * berturut-turut dan masing-masing langsung tercatat di tblmasalahpicker
 * sebagai SALAH AMBIL (tipe 4) -- bentuk barisnya sama dengan hasil modal itu,
 * jadi Daftar Masalah Picker, KPI, dan Error Recap tidak perlu diubah.
 *
 * Sengaja controller + model terpisah dari Packer.php: file itu sudah 1.600+
 * baris dan halaman scan-nya sensitif terhadap timing scanner. Desain lengkap:
 * docs/superpowers/specs/2026-09-18-salah-ambil-special-design.md
 */
class Salah_ambil_special extends MY_Controller
{
    /**
     * Sama dengan Scan Resi Packer (Webcam): webmaster (1), client packer (4).
     * Harus sejalan dengan MY_Controller::run_salah_ambil_special_migration().
     * Dijaga di controller juga karena URL-nya bisa dibuka langsung.
     */
    const ROLE_BOLEH = [1, 4];

    function __construct()
    {
        parent::__construct();
        $this->tolak_role_tanpa_akses();
        $this->load->model('salah_ambil_special_fcd');
    }

    /**
     * Penolakan tetap JSON valid: halaman lewat view dengan penanda
     * akses_ditolak (show_404 mengirim HTML dan merusak SPA), endpoint data
     * lewat make_ajax_response.
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
                'view'    => $this->load->view('salah_ambil_special/index', ['akses_ditolak' => TRUE], TRUE),
                'message' => null,
            ]);
            exit();
        }

        $this->make_ajax_response(403, 'Role Anda tidak punya akses ke menu Salah Ambil Special.');
    }

    public function index()
    {
        $this->show([], 'salah_ambil_special/index');
    }

    /** Tombol "Kunci & Mulai Scan": validasi pasangan SKU dan kembalikan nama barang + rak untuk konfirmasi visual. */
    public function cek_sku()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $pasangan = $this->validasi_pasangan_sku($this->input->post('sku_benar'), $this->input->post('sku_salah'));
        if (isset($pasangan['error'])) {
            $this->make_ajax_response(400, $pasangan['error']);
        }

        $this->make_ajax_response(200, 'SKU valid', $pasangan);
    }

    /**
     * Satu scan resi. Validasi berurutan, berhenti di kegagalan pertama;
     * nomor urutnya mengikuti tabel di spec bagian 5.
     */
    public function scan_resi()
    {
        if ($this->input->method() !== 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }

        // #1 SKU header dicek ulang tiap scan, jangan percaya nilai yang
        // dikirim browser: field readonly tetap bisa diubah lewat devtools.
        $pasangan = $this->validasi_pasangan_sku($this->input->post('sku_benar'), $this->input->post('sku_salah'));
        if (isset($pasangan['error'])) {
            $this->make_ajax_response(400, 'Kunci SKU dulu: ' . $pasangan['error']);
        }
        $sku_benar = $pasangan['sku_benar']['id_sku'];
        $sku_salah = $pasangan['sku_salah']['id_sku'];

        // #2 resi ada
        $resi = $this->salah_ambil_special_fcd->resi_terbaru($noresi);
        if (!$resi) {
            $this->make_ajax_response(404, 'Resi tidak ditemukan');
        }
        $id_printresi = (int) $resi['id_printresi'];

        // #3 tepat 1 baris SKU dengan total qty 1
        $detail = $this->salah_ambil_special_fcd->detail_resi($id_printresi);
        $jumlah_sku = count($detail);
        $total_qty  = 0;
        foreach ($detail as $d) {
            $total_qty += (int) $d['jumlah'];
        }
        if ($jumlah_sku !== 1 || $total_qty !== 1) {
            $this->make_ajax_response(422, 'Bukan resi spesial (' . $jumlah_sku . ' SKU, qty ' . $total_qty . ')');
        }

        // #4 SKU resi = SKU seharusnya. Perbandingan tanpa peduli huruf besar-
        // kecil, sama longgarnya dengan pencocokan tblsku di cari_sku().
        $sku_resi = trim((string) $detail[0]['sku']);
        if (strcasecmp($sku_resi, $sku_benar) !== 0) {
            $this->make_ajax_response(422, 'SKU resi ' . $sku_resi . ', bukan ' . $sku_benar);
        }

        // #5 belum di-packing
        if ($this->salah_ambil_special_fcd->sudah_packing($id_printresi)) {
            $this->make_ajax_response(409, 'Resi sudah di-packing');
        }

        // #6 sudah di-scan ambil picker (supaya CS tahu picker mana)
        $picker = $this->salah_ambil_special_fcd->picker_resi($id_printresi);
        if (!$picker) {
            $this->make_ajax_response(422, 'Resi belum di-scan ambil picker');
        }

        // #7 belum pernah dilaporkan + INSERT, satu transaksi dengan baris resi
        // dikunci supaya scan ganda yang nyaris bersamaan tidak lolos dua-duanya.
        // db_debug dimatikan: error DB akan mencetak halaman HTML CI yang
        // merusak JSON.
        $db_debug_awal = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $this->salah_ambil_special_fcd->kunci_resi($id_printresi);
        $ada = $this->salah_ambil_special_fcd->laporan_ada($id_printresi, $sku_benar);
        if ($ada) {
            $this->db->trans_rollback();
            $this->db->db_debug = $db_debug_awal;
            $this->make_ajax_response(409, (int) $ada['status'] === 0
                ? 'Sudah dilaporkan (pending)'
                : 'Sudah dilaporkan (sudah diproses CS)');
        }

        $this->salah_ambil_special_fcd->simpan_salah_ambil([
            'id_printresi' => $id_printresi,
            'noresi'       => $resi['noresi'],
            'sku'          => $sku_benar,
            'sku_salah'    => $sku_salah,
            'id_user'      => (int) $this->data['user']['id_user'],
        ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->db->db_debug = $db_debug_awal;
            log_message('error', 'Salah_ambil_special::scan_resi gagal: ' . json_encode($this->db->error()));
            $this->make_ajax_response(500, 'Gagal menyimpan, coba lagi');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $db_debug_awal;

        $this->make_ajax_response(201, 'Tercatat', [
            'noresi'      => $resi['noresi'],
            'sku'         => $sku_benar,
            'sku_salah'   => $sku_salah,
            'nama_picker' => !empty($picker['nama_picker'])
                ? $picker['nama_picker']
                : 'PEGAWAI #' . (int) $picker['kode_picker'],
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Validasi pasangan SKU header. Mengembalikan ['error' => pesan] atau
     * ['sku_benar' => baris tblsku, 'sku_salah' => baris tblsku]. id_sku yang
     * dipakai selanjutnya adalah ejaan dari tblsku, bukan ketikan user.
     */
    private function validasi_pasangan_sku($benar, $salah)
    {
        $benar = trim((string) $benar);
        $salah = trim((string) $salah);

        if ($benar === '' || $salah === '') {
            return ['error' => 'SKU seharusnya dan SKU terambil wajib diisi'];
        }
        if (strcasecmp($benar, $salah) === 0) {
            return ['error' => 'SKU seharusnya dan SKU terambil tidak boleh sama'];
        }

        $row_benar = $this->salah_ambil_special_fcd->cari_sku($benar);
        if (!$row_benar) {
            return ['error' => 'SKU seharusnya "' . $benar . '" tidak ada di master SKU'];
        }
        $row_salah = $this->salah_ambil_special_fcd->cari_sku($salah);
        if (!$row_salah) {
            return ['error' => 'SKU terambil "' . $salah . '" tidak ada di master SKU'];
        }

        return ['sku_benar' => $row_benar, 'sku_salah' => $row_salah];
    }
}
