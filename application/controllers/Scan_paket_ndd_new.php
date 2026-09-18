<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM HO -> "Scan Paket NDD New".
 *
 * Versi baru dari Scan_logistic (Scan Paket NDD) yang dibuat sebagai
 * controller, model, view, dan route terpisah supaya menu lama tetap utuh
 * dan bisa dipakai kalau versi ini bermasalah. Logika simpan scan TIDAK
 * disalin: tetap memanggil Scan_logistic_fcd::save_scan() apa adanya, begitu
 * pula pencatatan lost scan lewat Lost_scan_packer_fcd::save().
 *
 * Bedanya dari menu lama: saat scan ditolak karena resi belum di-packing
 * (NOT_PACKED), halaman langsung menampilkan pilihan packer di bawah kartu
 * status dan menyimpannya sebagai Lost Scan Packer -- petugas HO tidak perlu
 * pindah ke menu Lost Scan Packer/Picker lalu mengetik ulang resi. Resi itu
 * sendiri tetap TIDAK masuk HO/NDD; di-scan ulang setelah packer
 * menyelesaikan packing (alur sama dengan sekarang).
 *
 * Saat ditolak karena belum di-picker (NOT_PICKED), packer DAN picker
 * sama-sama tidak scan (packer menembus penjaga NOT_PICKED di menunya).
 * Panel yang sama muncul: packer tetap wajib dipilih dan dicatat sebagai
 * lost scan PACKER, lalu resi otomatis dilaporkan ke antrean tim picker
 * (Lost_scan_picker_fcd::lapor) -- tim picker yang menentukan picker-nya,
 * baru packer bisa scan ulang, lalu HO.
 */
class Scan_paket_ndd_new extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scan_logistic_fcd');
        $this->load->model('lost_scan_packer_fcd');
        $this->load->model('scan_paket_ndd_new_fcd');
        $this->load->model('lost_scan_picker_fcd');
    }

    public function index()
    {
        $id_user = $this->data['user']['id_user'];

        $data['title']          = 'Scan Paket NDD New';
        $data['total_scan']     = $this->scan_logistic_fcd->get_total_scan_today($id_user, 'HO');
        $data['total_scan_ndd'] = $this->scan_logistic_fcd->get_total_scan_today($id_user, 'NDD');
        $data['nama_komputer']  = $this->data['user']['nama_komputer'];
        $data['list_packer']    = $this->scan_paket_ndd_new_fcd->daftar_packer();

        $this->show($data, 'scan_paket_ndd_new/index');
    }

    /**
     * Simpan satu scan. Isinya sama dengan Scan_logistic::save() -- termasuk
     * rincian waktu srv_ms/boot_ms yang dibaca pengukur di sisi klien -- agar
     * perilaku scan di menu ini identik dengan menu lama.
     */
    public function save()
    {
        $t_masuk = microtime(true);

        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = $this->input->post('noresi');
        $is_ndd = $this->input->post('is_ndd');
        $type   = ($is_ndd === 'true') ? 'NDD' : 'HO';

        $save = $this->scan_logistic_fcd->save_scan($noresi, $this->data['user'], $type);

        if (isset($save['error'])) {
            $code = isset($save['code']) ? $save['code'] : 400;
            $data = isset($save['data']) ? $save['data'] : [];
            $this->make_ajax_response($code, $save['message'], $this->tempel_waktu($data, $t_masuk));
        }

        if (isset($save['affected_rows']) && $save['affected_rows'] > 0) {
            $msg = isset($save['message']) ? $save['message'] : 'Data berhasil disimpan';
            $this->make_ajax_response(201, $msg, $this->tempel_waktu([
                'type'         => $save['type'],
                'ho_inserted'  => !empty($save['ho_inserted']),
                'ndd_inserted' => !empty($save['ndd_inserted']),
            ], $t_masuk));
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE, $this->tempel_waktu([], $t_masuk));
    }

    /**
     * Cek apakah resi sudah pernah dicatat lost scan (tipe apa pun). Hanya
     * informatif: kalau request ini gagal, sisi klien tetap menampilkan
     * panel simpan.
     */
    public function cek_lost_scan()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }

        $catatan = $this->scan_paket_ndd_new_fcd->cari_lost_scan($noresi);

        $this->make_ajax_response(200, $catatan ? 'Sudah pernah dicatat' : 'Belum pernah dicatat', [
            'sudah_dicatat'  => (bool) $catatan,
            'catatan'        => $this->ringkas_catatan($catatan),
            'antrean_picker' => $this->ringkas_pending($this->lost_scan_picker_fcd->cari_pending($noresi)),
        ]);
    }

    /**
     * Catat lost scan packer untuk resi yang barusan ditolak NOT_PACKED.
     * Nama packer disimpan persis seperti menu Lost Scan lama
     * (tblpegawai.nama_pegawai) supaya Laporan Lost Scan tetap kompatibel.
     */
    public function simpan_lost_scan()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi       = trim((string) $this->input->post('noresi'));
        $nama_petugas = trim((string) $this->input->post('nama_petugas'));
        $belum_picker = ($this->input->post('belum_picker') === '1');

        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }
        if ($nama_petugas === '') {
            $this->make_ajax_response(400, 'Packer belum dipilih');
        }

        $save = $this->lost_scan_packer_fcd->save([
            'noresi'      => $noresi,
            'lost_type'   => 'PACKER',
            'nama_packer' => $nama_petugas,
        ], $this->data['user']);

        if ($save === -1) {
            $catatan = $this->scan_paket_ndd_new_fcd->cari_lost_scan($noresi);
            $this->make_ajax_response(400, 'Nomor resi sudah pernah dicatat lost scan', [
                'sudah_dicatat' => TRUE,
                'catatan'       => $this->ringkas_catatan($catatan),
            ]);
        }

        if ($save > 0) {
            $data = [
                'noresi'         => $noresi,
                'nama_packer'    => $nama_petugas,
                'antrean_picker' => null,
            ];
            $pesan = 'Lost scan packer berhasil dicatat';

            // Belum di-picker: sekaligus masukkan ke antrean tim picker.
            // Kegagalan di sini tidak membatalkan catatan packer yang sudah
            // tersimpan -- statusnya dikirim apa adanya supaya petugas tahu.
            if ($belum_picker) {
                $lapor = $this->lost_scan_picker_fcd->lapor($noresi, 'HO', $this->data['user']);
                $data['lapor_picker']   = $lapor['status'];
                $data['antrean_picker'] = $this->ringkas_pending($lapor['pending']);
                $pesan .= ($lapor['status'] === 'DIBUAT')
                    ? ' dan dilaporkan ke tim picker'
                    : ' (' . $lapor['message'] . ')';
            }

            $this->make_ajax_response(201, $pesan, $data);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
    }

    /**
     * Laporkan resi belum-picker ke antrean tim picker tanpa mencatat packer
     * lagi -- dipakai saat packer sudah tercatat (mis. lewat menu lama) tapi
     * resi belum ada di antrean. Sumber laporan = HO, karena memang di sini
     * ditemukannya.
     */
    public function lapor_picker()
    {
        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }

        $lapor = $this->lost_scan_picker_fcd->lapor($noresi, 'HO', $this->data['user']);
        $data  = [
            'lapor_picker'   => $lapor['status'],
            'antrean_picker' => $this->ringkas_pending($lapor['pending']),
        ];

        if ($lapor['status'] === 'DIBUAT') {
            $this->make_ajax_response(201, 'Resi ' . $noresi . ' dilaporkan ke tim picker', $data);
        }

        $this->make_ajax_response(400, $lapor['message'], $data);
    }

    /** Kolom antrean picker yang ditampilkan panel; null bila tidak ada. */
    private function ringkas_pending($pending)
    {
        if (empty($pending)) {
            return null;
        }

        return [
            'sumber'       => $pending['sumber'],
            'nama_pelapor' => $pending['nama_pelapor'],
            'waktu_lapor'  => $pending['waktu_lapor'],
        ];
    }

    /** Ambil hanya kolom yang ditampilkan panel; null bila tidak ada catatan. */
    private function ringkas_catatan($catatan)
    {
        if (empty($catatan)) {
            return null;
        }

        return [
            'nama_packer'  => $catatan['nama_packer'],
            'lost_type'    => $catatan['lost_type'],
            'nama_pelapor' => $catatan['nama_pelapor'],
            'created_at'   => $catatan['created_at'],
        ];
    }

    /**
     * Sisipkan rincian waktu server ke payload response scan (salinan dari
     * Scan_logistic::tempel_waktu -- pengukur waktu di view membacanya).
     *   srv_ms  = seluruh waktu PHP, dari request masuk sampai balasan disusun
     *   boot_ms = bagian yang habis SEBELUM method save() mulai (konstruktor)
     */
    private function tempel_waktu(array $data, $t_masuk)
    {
        $t_awal = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : $t_masuk;
        $now    = microtime(true);

        $data['srv_ms']  = (int) round(($now - $t_awal) * 1000);
        $data['boot_ms'] = (int) round(($t_masuk - $t_awal) * 1000);

        return $data;
    }
}
