<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu TIM HO -> "Scan Paket NDD New".
 *
 * Versi baru dari Scan_logistic (Scan Paket NDD) yang dibuat sebagai
 * controller, model, view, dan route terpisah supaya menu lama tetap utuh
 * dan bisa dipakai kalau versi ini bermasalah. Logika simpan scan TIDAK
 * disalin: tetap memanggil Scan_logistic_fcd::save_scan() apa adanya.
 *
 * Bedanya dari menu lama: saat scan ditolak NOT_PACKED / NOT_PICKED, panel
 * lost scan muncul di bawah kartu status dengan input no absen ber-saran
 * (packer; plus picker kalau belum di-picker). Satu klik "Simpan Lost Scan"
 * mengisi baris picking/packing yang kosong atas nama orang itu (tanggal =
 * saat klik) lalu langsung men-scan resi ke HO/NDD -- tanpa antar fisik dan
 * tanpa scan ulang. Lihat docs/LOST_SCAN.md §12.
 */
class Scan_paket_ndd_new extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scan_logistic_fcd');
        $this->load->model('scan_paket_ndd_new_fcd');
        $this->load->model('lost_scan_picker_fcd');
        $this->load->model('lost_scan_selesai_fcd');
    }

    public function index()
    {
        $id_user = $this->data['user']['id_user'];

        $data['title']          = 'Scan Paket NDD New';
        $data['total_scan']     = $this->scan_logistic_fcd->get_total_scan_today($id_user, 'HO');
        $data['total_scan_ndd'] = $this->scan_logistic_fcd->get_total_scan_today($id_user, 'NDD');
        $data['nama_komputer']  = $this->data['user']['nama_komputer'];
        $data['roster_packer']  = $this->scan_paket_ndd_new_fcd->roster_packer();
        $data['roster_picker']  = $this->scan_paket_ndd_new_fcd->roster_picker();

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
     * Cek apakah resi sudah pernah dicatat lost scan (tipe apa pun) dan
     * apakah data packingnya sudah terisi. Catatan lost scan tanpa baris
     * packing (dari alur lama yang hanya mencatat) tetap boleh dilengkapi
     * lewat Simpan. Hanya informatif: kalau request ini gagal, sisi klien
     * tetap menampilkan panel simpan.
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
            'sudah_packing'  => $this->scan_paket_ndd_new_fcd->sudah_packing($noresi),
            'catatan'        => $this->ringkas_catatan($catatan),
            'antrean_picker' => $this->ringkas_pending($this->lost_scan_picker_fcd->cari_pending($noresi)),
        ]);
    }

    /**
     * Tombol "Simpan Lost Scan". Dua langkah dalam satu klik:
     *  1. Lost_scan_selesai_fcd::proses() mengisi baris picking/packing yang
     *     masih kosong atas nama pemilik no absen (tanggal = saat klik).
     *  2. Scan_logistic_fcd::save_scan() apa adanya -- sama dengan save() di
     *     atas -- memasukkan resi ke tblresikeluar/tblscan_ndd.
     * Kalau langkah 2 gagal, data langkah 1 tetap tersimpan (data_terisi) dan
     * petugas cukup men-scan ulang resinya.
     */
    public function simpan_lost_scan()
    {
        $t_masuk = microtime(true);

        if ($this->input->method() != 'post') {
            $this->make_ajax_response(400, INVALID_REQUEST_METHOD);
        }

        $noresi = trim((string) $this->input->post('noresi'));
        $type   = ($this->input->post('is_ndd') === 'true') ? 'NDD' : 'HO';

        if ($noresi === '') {
            $this->make_ajax_response(400, 'Nomor resi kosong');
        }

        $proses = $this->lost_scan_selesai_fcd->proses(
            $noresi,
            trim((string) $this->input->post('no_absen_picker')),
            trim((string) $this->input->post('no_absen_packer')),
            $this->data['user']
        );

        if (isset($proses['error'])) {
            $this->make_ajax_response($proses['code'], $proses['message'], $this->tempel_waktu([
                'EXCEPTION_CODE' => $proses['kode'],
            ], $t_masuk));
        }

        $save = $this->scan_logistic_fcd->save_scan($noresi, $this->data['user'], $type);

        if (isset($save['error'])) {
            $code = isset($save['code']) ? $save['code'] : 400;
            $data = isset($save['data']) ? $save['data'] : [];
            $data['data_terisi'] = TRUE;
            $this->make_ajax_response(
                $code,
                'Data lost scan tersimpan, tapi scan HO gagal: ' . $save['message'] . ' Scan ulang resinya.',
                $this->tempel_waktu($data, $t_masuk)
            );
        }

        $isi = [];
        if ($proses['nama_picker']) {
            $isi[] = 'picker ' . $proses['nama_picker'];
        }
        if ($proses['nama_packer']) {
            $isi[] = 'packer ' . $proses['nama_packer'];
        }
        $pesan = ($isi ? 'Diisi ' . implode(', ', $isi) . ' (' . $proses['tanggal'] . ') dan ' : '') .
            'masuk ' . ($type === 'NDD' ? 'HO + NDD' : 'HO REGULER');

        $this->make_ajax_response(201, $pesan, $this->tempel_waktu([
            'type'         => $save['type'],
            'ho_inserted'  => !empty($save['ho_inserted']),
            'ndd_inserted' => !empty($save['ndd_inserted']),
            'nama_picker'  => $proses['nama_picker'],
            'nama_packer'  => $proses['nama_packer'],
            'tanggal'      => $proses['tanggal'],
        ], $t_masuk));
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
