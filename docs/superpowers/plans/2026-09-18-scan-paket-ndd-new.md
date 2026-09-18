# Scan Paket NDD New — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menu baru TIM HO → "Scan Paket NDD New" yang, saat scan ditolak karena resi belum di-packing, langsung menampilkan pilihan packer dan mencatatnya sebagai Lost Scan Packer tanpa pindah menu.

**Architecture:** Controller/model/view/route baru berdampingan dengan `Scan_logistic` (menu lama tidak disentuh). Logika simpan scan dan simpan lost scan tetap memanggil model lama apa adanya (`Scan_logistic_fcd::save_scan()`, `Lost_scan_packer_fcd::save()`); model baru hanya berisi query baca (daftar packer, cek lost scan). Menu + hak akses ditanam lewat migrasi bootstrap ter-gate versi di `MY_Controller`.

**Tech Stack:** CodeIgniter 3, PHP 8.2, MySQL/MariaDB, jQuery + bootstrap-select + noty (sudah dimuat `main.php`).

Spec: `docs/superpowers/specs/2026-09-18-scan-paket-ndd-new-design.md`

## Global Constraints

- Bahasa kerja Indonesia: komentar kode, pesan UI, pesan commit.
- Semua respons AJAX lewat `MY_Controller::make_ajax_response($code, $message, $data)` — fungsi ini menghabiskan output buffer, selalu HTTP 200, status di body, lalu `exit`.
- DILARANG `DELETE`/`DROP`/`TRUNCATE`. Data dummy hanya `INSERT`.
- Tidak mengubah `Scan_logistic.php`, `scan_logistic/scan_view.php`, `Lost_scan_packer.php`, `Scan_logistic_fcd.php`, `Lost_scan_packer_fcd.php`.
- Tidak ada perubahan skema tabel. `tbllostscanpacker.nama_packer` diisi `tblpegawai.nama_pegawai`.
- Setiap migrasi/menu baru di `MY_Controller.php` WAJIB menaikkan `BOOTSTRAP_VERSI` (format `YYYY-MM-DD.n`).
- Tidak ada test suite: verifikasi = `C:/xampp/php/php.exe -l <file>` + uji manual di browser (`http://localhost/new-iresis/`).
- Script dummy ditaruh di `dev_tools/` (gitignored), tidak di-commit.

---

### Task 1: Model baca `Scan_paket_ndd_new_fcd`

**Files:**
- Create: `application/models/Scan_paket_ndd_new_fcd.php`

**Interfaces:**
- Produces: `daftar_packer(): array` — array of `['id_user' => int, 'kode_pegawai' => int, 'nama_pegawai' => string]`, urut nama.
- Produces: `cari_lost_scan(string $noresi): array|null` — baris terakhir `tbllostscanpacker` untuk resi itu: `['id_lostscanpacker','noresi','lost_type','nama_packer','created_at','nama_pelapor']`, atau `null`.

- [ ] **Step 1: Tulis model**

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Query baca untuk menu TIM HO -> "Scan Paket NDD New".
 *
 * Sengaja tidak ada query tulis di sini: simpan scan tetap lewat
 * Scan_logistic_fcd::save_scan() dan simpan lost scan lewat
 * Lost_scan_packer_fcd::save(), supaya aturan bisnisnya satu sumber dengan
 * menu lama.
 */
class Scan_paket_ndd_new_fcd extends CI_Model
{
    /** id_hakakses "client packer" di tblhakakses. */
    const ROLE_PACKER = 4;

    /**
     * Daftar packer untuk dropdown lost scan.
     *
     * Menu Lost Scan lama menampilkan seluruh tblpegawai (139 baris, termasuk
     * QC/INB/AFF dan beberapa nama kosong). Di sini hanya akun packer aktif
     * yang sudah terhubung ke tblpegawai lewat tbluser.id_pegawai -- per
     * 18 Sep 2026 seluruh 15 akun packer aktif memenuhi syarat itu. Yang
     * dipakai sebagai nilai tetap nama_pegawai supaya Laporan Lost Scan lama
     * membaca datanya tanpa perubahan.
     */
    public function daftar_packer()
    {
        return $this->db->query(
            "SELECT u.id_user, p.kode_pegawai, p.nama_pegawai
             FROM tbluser u
             JOIN tblpegawai p ON p.kode_pegawai = u.id_pegawai
             WHERE u.hakakses = ? AND u.isactive = 1 AND TRIM(p.nama_pegawai) <> ''
             ORDER BY p.nama_pegawai ASC",
            [self::ROLE_PACKER]
        )->result_array();
    }

    /**
     * Catatan lost scan terakhir untuk satu resi (tipe apa pun), beserta nama
     * pelapornya. Dipakai untuk menampilkan "sudah dicatat oleh X" tanpa
     * perlu mencoba simpan dulu.
     */
    public function cari_lost_scan($noresi)
    {
        $row = $this->db->query(
            "SELECT t.id_lostscanpacker, t.noresi, t.lost_type, t.nama_packer, t.created_at,
                    u.name AS nama_pelapor
             FROM tbllostscanpacker t
             LEFT JOIN tbluser u ON u.id_user = t.created_by
             WHERE t.noresi = ?
             ORDER BY t.created_at DESC, t.id_lostscanpacker DESC
             LIMIT 1",
            [$noresi]
        )->row_array();

        return $row ? $row : null;
    }
}
```

- [ ] **Step 2: Syntax check**

Run: `C:/xampp/php/php.exe -l application/models/Scan_paket_ndd_new_fcd.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Uji query langsung di MySQL**

Run:
```bash
"C:/xampp/mysql/bin/mysql.exe" -u root -e "SELECT u.id_user, p.kode_pegawai, p.nama_pegawai FROM tbluser u JOIN tblpegawai p ON p.kode_pegawai = u.id_pegawai WHERE u.hakakses = 4 AND u.isactive = 1 AND TRIM(p.nama_pegawai) <> '' ORDER BY p.nama_pegawai ASC;" iresis-prod
```
Expected: 15 baris, tanpa nama kosong.

- [ ] **Step 4: Commit**

```bash
git add application/models/Scan_paket_ndd_new_fcd.php
git commit -m "feat(scan-ndd-new): model baca daftar packer dan cek lost scan"
```

---

### Task 2: Controller `Scan_paket_ndd_new` + route

**Files:**
- Create: `application/controllers/Scan_paket_ndd_new.php`
- Modify: `application/config/routes.php` (setelah blok `masalah-picker-new`, sekitar baris 330)

**Interfaces:**
- Consumes: `Scan_paket_ndd_new_fcd::daftar_packer()`, `::cari_lost_scan($noresi)` (Task 1); `Scan_logistic_fcd::save_scan($noresi, $user, $type)` dan `::get_total_scan_today($id_user, $type)` (sudah ada); `Lost_scan_packer_fcd::save($data, $user)` (sudah ada; kembalikan `-1` bila dobel, `>0` bila tersimpan).
- Produces (dipakai view Task 3):
  - `POST scan-paket-ndd-new/save {noresi, is_ndd: 'true'|'false'}` → JSON `{code, message, data}`; sukses `code 201`, `data.type`, `data.ho_inserted`, `data.ndd_inserted`; gagal `data.EXCEPTION_CODE` (`NOT_PACKED`, dst.); selalu `data.srv_ms`, `data.boot_ms`.
  - `POST scan-paket-ndd-new/cek-lost-scan {noresi}` → `code 200`, `data.sudah_dicatat: bool`, `data.catatan: {nama_packer, lost_type, nama_pelapor, created_at}|null`.
  - `POST scan-paket-ndd-new/simpan-lost-scan {noresi, nama_petugas}` → `code 201` `data.{noresi, nama_packer}`; `code 400` dobel dengan `data.sudah_dicatat = true` + `data.catatan`; `code 400` input kosong.

- [ ] **Step 1: Tulis controller**

```php
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
 */
class Scan_paket_ndd_new extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scan_logistic_fcd');
        $this->load->model('lost_scan_packer_fcd');
        $this->load->model('scan_paket_ndd_new_fcd');
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
            'sudah_dicatat' => (bool) $catatan,
            'catatan'       => $this->ringkas_catatan($catatan),
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
            $this->make_ajax_response(201, 'Lost scan packer berhasil dicatat', [
                'noresi'      => $noresi,
                'nama_packer' => $nama_petugas,
            ]);
        }

        $this->make_ajax_response(200, NOTHING_TO_SAVE);
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
```

- [ ] **Step 2: Tambah route**

Di `application/config/routes.php`, tepat setelah baris `$route['masalah-picker-new/data-cetak-ulang'] = ...;`, tambahkan:

```php

// TIM HO -> Scan Paket NDD New (berdampingan dengan scan_logistic yang lama)
$route['scan-paket-ndd-new'] = 'scan_paket_ndd_new/index';
$route['scan-paket-ndd-new/save'] = 'scan_paket_ndd_new/save';
$route['scan-paket-ndd-new/cek-lost-scan'] = 'scan_paket_ndd_new/cek_lost_scan';
$route['scan-paket-ndd-new/simpan-lost-scan'] = 'scan_paket_ndd_new/simpan_lost_scan';
```

- [ ] **Step 3: Syntax check**

Run: `C:/xampp/php/php.exe -l application/controllers/Scan_paket_ndd_new.php && C:/xampp/php/php.exe -l application/config/routes.php`
Expected: dua kali `No syntax errors detected`

- [ ] **Step 4: Uji endpoint tanpa view (curl, butuh cookie login)**

Login dulu di browser, lalu ambil cookie `ci_session`. Run:
```bash
curl -s -b "ci_session=<cookie>" -X POST -d "noresi=TIDAKADA" http://localhost/new-iresis/scan-paket-ndd-new/cek-lost-scan
```
Expected: `{"code":200,"message":"Belum pernah dicatat","data":{"sudah_dicatat":false,"catatan":null}}` (bentuk persis bergantung `make_ajax_response`). Kalau tidak ada cookie, lewati — diuji di Task 5.

- [ ] **Step 5: Commit**

```bash
git add application/controllers/Scan_paket_ndd_new.php application/config/routes.php
git commit -m "feat(scan-ndd-new): controller scan + endpoint cek/simpan lost scan dan route"
```

---

### Task 3: View `scan_paket_ndd_new/index.php`

**Files:**
- Create: `application/views/scan_paket_ndd_new/index.php`

**Interfaces:**
- Consumes: variabel view `$total_scan`, `$total_scan_ndd`, `$nama_komputer`, `$list_packer` (Task 2); endpoint `scan-paket-ndd-new/save`, `/cek-lost-scan`, `/simpan-lost-scan` (Task 2); fungsi global `suaraScan`, `suaraKurir`, `noty` dari `main.php`.

Turunan `application/views/scan_logistic/scan_view.php`. Perbedaan: URL endpoint, label NEW, panel lost scan (di luar `<form>` agar Enter di dropdown tidak men-submit form scan), pengecualian fokus-otomatis untuk area panel, dan pemanggilan `tampilkanPanelLostScan()` dari `showError()` saat `NOT_PACKED`.

- [ ] **Step 1: Tulis view**

```php
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default shadow-lg" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="panel-heading" id="panel_header" style="background: linear-gradient(135deg, #1a2a6c, #2a4858); color: white; padding: 25px;">
                <h3 class="panel-title" style="font-weight: 700; font-size: 1.6rem; letter-spacing: 1px;">
                    <i class="fa fa-truck"></i> <span id="header_title">SCAN HO + NDD</span>
                    <span class="label label-warning" style="font-size: 0.7rem; vertical-align: middle; margin-left: 8px;">NEW</span>
                </h3>
                <div class="pull-right">
                    <span class="label" id="header_badge" style="font-size: 0.9rem; padding: 5px 10px; border-radius: 4px; background: #e67e22;">HO + NDD</span>
                </div>
            </div>
            <div class="panel-body" style="padding: 40px; background: #f4f7f6;">
                <form id="form_scan_logistic" autocomplete="off" class="form-horizontal nojs">
                    <input type="hidden" name="is_ndd" id="is_ndd" value="true">

                    <!-- MODE TOGGLE -->
                    <div class="row" style="margin-bottom: 25px;">
                        <div class="col-md-12 text-center">
                            <div class="btn-group" id="mode_toggle" style="box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
                                <button type="button" class="btn btn-lg mode-btn" id="btn_mode_ndd" style="padding: 12px 35px; font-weight: 700; font-size: 1rem; letter-spacing: 1px; background: #1a2a6c; color: #fff; border: none;">
                                    <i class="fa fa-bolt"></i> HO + NDD
                                </button>
                                <button type="button" class="btn btn-lg mode-btn" id="btn_mode_reguler" style="padding: 12px 35px; font-weight: 700; font-size: 1rem; letter-spacing: 1px; background: #e0e0e0; color: #666; border: none;">
                                    <i class="fa fa-cube"></i> REGULER
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #1a2a6c; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Scan NDD</label>
                                <div style="font-size: 2.2rem; font-weight: 800; color: #1a2a6c;" id="total_scan_ndd_display"><?= $total_scan_ndd ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #27ae60; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Scan HO</label>
                                <div style="font-size: 2.2rem; font-weight: 800; color: #27ae60;" id="total_scan_ho_display"><?= $total_scan ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card" style="background: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 6px solid #2a4858; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Host ID</label>
                                <div style="font-size: 1.2rem; font-weight: 700; color: #333; margin-top: 5px;"><i class="fa fa-desktop"></i> <?= $nama_komputer ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <div class="col-md-12">
                            <div class="input-group input-group-lg" id="input_wrapper" style="box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
                                <span class="input-group-addon" id="input_icon" style="background: #fff; border-right: none; border-radius: 12px 0 0 12px; color: #1a2a6c;">
                                    <i class="fa fa-barcode fa-2x"></i>
                                </span>
                                <input type="text" name="noresi" id="noresi" class="form-control"
                                       placeholder="MASUKKAN NOMOR RESI..."
                                       style="height: 75px; font-size: 1.8rem; border-left: none; border-radius: 0 12px 12px 0; border: 2px solid #ddd; font-weight: 600; text-align: center;">
                            </div>
                            <p class="text-center text-muted" style="margin-top: 15px; font-weight: 500;" id="scan_description">
                                <i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> dan <strong>NDD Report</strong>
                            </p>
                        </div>
                    </div>

                    <div id="status_container" class="mt-4 text-center" style="min-height: 140px; margin-top: 40px;">
                        <div id="latest_resi_card" class="well" style="background: #fff; border: 2px solid #eee; border-radius: 15px; transition: all 0.3s ease; padding: 20px;">
                            <div id="resi_status_icon" style="font-size: 3rem; margin-bottom: 10px; color: #bbb;">
                                <i class="fa fa-dot-circle-o"></i>
                            </div>
                            <h3 id="display_noresi" style="font-weight: 800; color: #444; letter-spacing: 2px; margin: 5px 0;">-</h3>
                            <p id="display_message" style="font-size: 1.1rem; font-weight: 600; color: #888;">STANDBY</p>
                            <span id="queue_badge" class="label label-warning" style="display: none; font-size: 0.85rem; padding: 5px 10px;">
                                <i class="fa fa-spinner fa-spin"></i> <span id="queue_count">0</span> resi dalam antrean
                            </span>
                        </div>
                    </div>
                </form>

                <!-- PANEL LOST SCAN PACKER: di luar <form> supaya Enter di dropdown
                     tidak men-submit form scan. Muncul hanya saat NOT_PACKED. -->
                <div id="panel_lost_scan" style="display: none; margin-top: 20px;">
                    <div class="well" style="background: #fff8e1; border: 2px solid #f39c12; border-radius: 15px; padding: 20px; margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #b9770e; letter-spacing: 1px;">
                                    <i class="fa fa-user-times"></i> Lost Scan Packer
                                </div>
                                <div id="ls_noresi" style="font-size: 1.4rem; font-weight: 800; letter-spacing: 2px; color: #444;">-</div>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" id="ls_tutup"><i class="fa fa-times"></i> Tutup (Esc)</button>
                        </div>

                        <div id="ls_mode_simpan">
                            <p style="color: #666; margin-bottom: 10px;">
                                Resi belum di-packing. Pilih packer yang lupa scan, lalu tekan <b>Enter</b> / klik Simpan.
                                Resi ini <b>tidak</b> masuk HO -- scan ulang setelah packer selesai.
                            </p>
                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <select id="ls_packer" class="form-control" data-live-search="true" data-size="8" title="Pilih Packer">
                                        <option value="">Pilih Packer</option>
                                        <?php foreach ($list_packer as $p) : ?>
                                            <option value="<?= htmlspecialchars($p['nama_pegawai']) ?>"><?= htmlspecialchars($p['nama_pegawai'] . ' - ' . $p['kode_pegawai']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-warning" id="ls_simpan" style="font-weight: 700; white-space: nowrap;">
                                    <i class="fa fa-save"></i> Simpan Lost Scan
                                </button>
                            </div>
                            <?php if (empty($list_packer)) : ?>
                                <p class="text-danger" style="margin: 10px 0 0;">
                                    <i class="fa fa-exclamation-triangle"></i> Tidak ada akun packer aktif yang terhubung ke data pegawai.
                                    Catat lewat <a href="lost_scan_packer/input" class="link">menu Lost Scan Packer</a>.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div id="ls_mode_info" style="display: none;">
                            <p style="font-weight: 600; color: #155724; margin: 0; font-size: 1.05rem;">
                                <i class="fa fa-check-circle"></i> <span id="ls_info_teks"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div id="stat_waktu_wrapper" style="margin-top: 20px; text-align: center; font-size: 0.85rem; color: #666;">
                    <i class="fa fa-clock-o"></i> <span id="stat_waktu">belum ada scan</span>
                </div>

                <div id="history_wrapper" style="margin-top: 25px; display: none;">
                    <label style="color: #666; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
                        <i class="fa fa-history"></i> Riwayat Scan Terakhir
                    </label>
                    <div style="background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;">
                        <table class="table table-condensed" style="margin: 0; font-size: 0.9rem;">
                            <tbody id="scan_history"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="panel-footer" style="background: #2a4858; color: white; border: none; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 500;">
                    <i class="fa fa-shield"></i> SECURE LOGISTIC SCAN v2.0 &middot; NEW
                </div>
                <div>
                    <a href="lost_scan_packer/report" class="btn btn-warning btn-sm link" style="border-radius: 6px; font-weight: 600; margin-right: 6px;">
                        <i class="fa fa-user-times"></i> LAPORAN LOST SCAN
                    </a>
                    <a href="scan_logistic/report" class="btn btn-info btn-sm link" style="border-radius: 6px; font-weight: 600;">
                        <i class="fa fa-list-alt"></i> BUKA LAPORAN NDD
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #noresi:focus { border-color: #1a2a6c; outline: none; box-shadow: 0 0 15px rgba(26, 42, 108, 0.3); }
    .status-success { border: 3px solid #28a745 !important; background-color: #f0fff4 !important; animation: pop-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .status-error { border: 3px solid #dc3545 !important; background-color: #fff5f5 !important; animation: shake-hard 0.4s; }

    @keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    @keyframes shake-hard { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-15px); } 40% { transform: translateX(15px); } 60% { transform: translateX(-15px); } 80% { transform: translateX(15px); } }

    .info-card { transition: transform 0.2s; }
    .info-card:hover { transform: translateY(-5px); }

    .mode-btn { transition: all 0.3s ease; }
    .mode-btn:focus { outline: none; box-shadow: none; }

    /* Reguler mode theme */
    .mode-reguler #panel_header { background: linear-gradient(135deg, #27ae60, #1e8449) !important; }
    .mode-reguler #input_icon { color: #27ae60 !important; }
    .mode-reguler #noresi:focus { border-color: #27ae60 !important; box-shadow: 0 0 15px rgba(39, 174, 96, 0.3) !important; }

    #panel_lost_scan { animation: pop-in 0.3s ease; }
    #panel_lost_scan .bootstrap-select > .btn { height: 42px; font-weight: 600; }
</style>

<script>
$(document).ready(function() {
    $("#noresi").focus();

    // Klik di mana pun mengembalikan fokus ke input resi -- KECUALI di area
    // panel lost scan dan dropdown-nya, supaya petugas bisa memilih packer.
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group, .btn, #panel_lost_scan, .bootstrap-select, .dropdown-menu').length) {
            $("#noresi").focus();
        }
    });

    // ===== MODE TOGGLE =====
    var currentMode = 'ndd'; // default

    $("#btn_mode_ndd").on('click', function() {
        currentMode = 'ndd';
        $("#is_ndd").val('true');

        $(this).css({ background: '#1a2a6c', color: '#fff' });
        $("#btn_mode_reguler").css({ background: '#e0e0e0', color: '#666' });

        $("#header_title").text("SCAN HO + NDD");
        $("#header_badge").text("HO + NDD").css('background', '#e67e22');
        $(".panel-default").removeClass('mode-reguler');

        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> dan <strong>NDD Report</strong>');

        $("#noresi").focus();
    });

    $("#btn_mode_reguler").on('click', function() {
        currentMode = 'reguler';
        $("#is_ndd").val('false');

        $(this).css({ background: '#27ae60', color: '#fff' });
        $("#btn_mode_ndd").css({ background: '#e0e0e0', color: '#666' });

        $("#header_title").text("SCAN HO REGULER");
        $("#header_badge").text("REGULER").css('background', '#27ae60');
        $(".panel-default").addClass('mode-reguler');

        $("#scan_description").html('<i class="fa fa-info-circle"></i> Scan otomatis masuk ke <strong>Handover (HO)</strong> saja');

        $("#noresi").focus();
    });

    // ===== ANTREAN SCAN =====
    // Input TIDAK pernah di-disable (scanner gun mengetik cepat lalu Enter).
    // Nilai langsung diambil + dikosongkan, resi masuk antrean, dikirim satu
    // per satu di latar belakang. Sama dengan menu lama.
    var busy = false;
    var queue = [];
    var recent = {};              // noresi -> waktu scan sukses terakhir
    var RECENT_MS = 5 * 60 * 1000; // tolak scan ulang dalam 5 menit tanpa ke server

    $("#form_scan_logistic").on("submit", function(e) {
        e.preventDefault();

        var noresi = $("#noresi").val().trim();
        $("#noresi").val("").focus();
        if (noresi === "") return;

        queue.push({ noresi: noresi, mode: currentMode });
        renderQueue();
        processQueue();
    });

    function renderQueue() {
        var pending = queue.length + (busy ? 1 : 0);
        $("#queue_count").text(pending);
        $("#queue_badge").toggle(pending > 1);
    }

    // ===== PENGUKUR WAKTU SCAN ===== (sama dengan menu lama)
    var statWaktu = { n: 0, total: 0, maks: 0, lambat: 0, nSrv: 0, totalSrv: 0, totalBoot: 0 };
    var AMBANG_LAMBAT = 1000; // ms

    function catatWaktu(t0, data) {
        var t1 = (window.performance && performance.now) ? performance.now() : Date.now();
        var ms = Math.round(t1 - t0);

        var w = { ms: ms, srv: null, boot: null };
        if (data && typeof data.srv_ms === 'number') {
            w.srv = data.srv_ms;
            w.boot = (typeof data.boot_ms === 'number') ? data.boot_ms : null;

            statWaktu.nSrv++;
            statWaktu.totalSrv += w.srv;
            statWaktu.totalBoot += (w.boot || 0);
        }

        statWaktu.n++;
        statWaktu.total += ms;
        if (ms > statWaktu.maks) statWaktu.maks = ms;
        if (ms > AMBANG_LAMBAT) statWaktu.lambat++;

        var rata = Math.round(statWaktu.total / statWaktu.n);
        var teks =
            'terakhir <b>' + ms + ' ms</b> &nbsp;|&nbsp; rata-rata <b>' + rata +
            ' ms</b> &nbsp;|&nbsp; terlama <b>' + statWaktu.maks +
            ' ms</b> &nbsp;|&nbsp; di atas 1 detik: <b>' + statWaktu.lambat + '</b> dari ' + statWaktu.n;

        if (statWaktu.nSrv > 0) {
            var rataSrv = Math.round(statWaktu.totalSrv / statWaktu.nSrv);
            var rataBoot = Math.round(statWaktu.totalBoot / statWaktu.nSrv);
            teks += '<br><span style="font-size: 11px;">server <b>' + rataSrv +
                ' ms</b> (bootstrap <b>' + rataBoot + ' ms</b>) &nbsp;|&nbsp; jaringan+antrean <b>' +
                Math.max(0, rata - rataSrv) + ' ms</b></span>';
        }

        $("#stat_waktu").html(teks).css('color', ms > AMBANG_LAMBAT ? '#dc3545' : '#666');

        return w;
    }

    function processQueue() {
        if (busy || queue.length === 0) return;

        var item = queue.shift();
        var noresi = item.noresi;
        var now = Date.now();

        if (recent[noresi] && (now - recent[noresi]) < RECENT_MS) {
            showError(noresi, "SUDAH DI-SCAN BARUSAN (DOBEL)", "ALREADY_SCANNED");
            renderQueue();
            setTimeout(processQueue, 0);
            return;
        }

        busy = true;
        renderQueue();

        var t0 = (window.performance && performance.now) ? performance.now() : Date.now();

        $.ajax({
            url: "scan-paket-ndd-new/save",
            type: "POST",
            data: { noresi: noresi, is_ndd: (item.mode === 'ndd') ? 'true' : 'false' },
            dataType: "json",
            success: function(response) {
                var data = response.data || {};
                var w = catatWaktu(t0, data);
                if (response.code === 201) {
                    recent[noresi] = Date.now();
                    pruneRecent();
                    showSuccess(noresi, item.mode, data, w);
                } else {
                    showError(noresi, response.message, data.EXCEPTION_CODE || '', w);
                }
            },
            error: function() {
                showError(noresi, "KESALAHAN SISTEM!", '', catatWaktu(t0));
            },
            complete: function() {
                busy = false;
                renderQueue();
                // Kalau panel lost scan sedang menunggu pilihan packer, fokus
                // jangan direbut kembali ke input resi.
                if (!panelLostScanAktif()) $("#noresi").focus();
                processQueue();
            }
        });
    }

    function pruneRecent() {
        var cutoff = Date.now() - RECENT_MS;
        for (var k in recent) {
            if (recent[k] < cutoff) delete recent[k];
        }
    }

    // ===== TAMPILAN HASIL =====
    function showSuccess(noresi, mode, data, w) {
        $("#latest_resi_card").removeClass("status-error").addClass("status-success");
        $("#resi_status_icon").html('<i class="fa fa-check-circle text-success" style="animation: pop-in 0.5s;"></i>');
        $("#display_noresi").text(noresi).css("color", "#155724");

        var pesan = (mode === 'ndd') ? "SCAN HO + NDD BERHASIL!" : "SCAN HO REGULER BERHASIL!";
        $("#display_message").text(pesan).css("color", "#28a745");

        if (data.ndd_inserted) bumpCounter("#total_scan_ndd_display");
        if (data.ho_inserted) bumpCounter("#total_scan_ho_display");

        pushHistory(noresi, pesan, true, w);
        playCourierAudio(noresi);
    }

    function showError(noresi, message, exceptionCode, w) {
        $("#latest_resi_card").removeClass("status-success").addClass("status-error");
        $("#resi_status_icon").html('<i class="fa fa-exclamation-triangle text-danger"></i>');
        $("#display_noresi").text(noresi).css("color", "#721c24");
        $("#display_message").text(String(message).toUpperCase()).css("color", "#dc3545");

        pushHistory(noresi, message, false, w);
        playErrorAudio(exceptionCode);

        // Inti menu New: belum di-packing -> langsung tawarkan catat lost scan.
        if (exceptionCode === 'NOT_PACKED') {
            tampilkanPanelLostScan(noresi);
        }
    }

    function bumpCounter(selector) {
        var el = $(selector);
        el.text((parseInt(el.text(), 10) || 0) + 1);
    }

    function pushHistory(noresi, message, ok, w) {
        var jam = new Date().toTimeString().substring(0, 8);
        var warna = ok ? '#28a745' : '#dc3545';
        var ikon = ok ? 'fa-check-circle' : 'fa-times-circle';

        var ms = (w && typeof w.ms === 'number') ? w.ms : null;

        var lambat = (ms !== null && ms > AMBANG_LAMBAT);
        var rincian = '';
        if (w && typeof w.srv === 'number') {
            rincian = '<br><span style="font-weight: 400; font-size: 10px; color: #999;">srv ' +
                w.srv + (typeof w.boot === 'number' ? ' · boot ' + w.boot : '') + '</span>';
        }
        var kolomMs = (ms !== null)
            ? '<td style="width: 95px; text-align: right; font-weight: 700; color: ' +
              (lambat ? '#dc3545' : '#999') + ';">' + ms + ' ms' + rincian + '</td>'
            : '<td></td>';

        $("#history_wrapper").show();
        $("#scan_history").prepend(
            '<tr>' +
            '<td style="width: 70px; color: #999;">' + jam + '</td>' +
            '<td style="font-weight: 700; letter-spacing: 1px;">' + $('<div>').text(noresi).html() + '</td>' +
            '<td style="color: ' + warna + '; font-weight: 600;">' +
            '<i class="fa ' + ikon + '"></i> ' + $('<div>').text(message).html() +
            '</td>' +
            kolomMs +
            '</tr>'
        );
        $("#scan_history tr:gt(7)").remove();
    }

    // ===== PANEL LOST SCAN PACKER =====
    // Panel terikat ke satu resi (lsResi). Antrean scan tetap berjalan; scan
    // resi lain hanya mengganti kartu status di atas, panel tetap ada sampai
    // disimpan atau ditutup. NOT_PACKED untuk resi lain mengganti isi panel.
    var lsResi = null;
    var lsAdaSelectpicker = (typeof $.fn.selectpicker === 'function');
    if (lsAdaSelectpicker) {
        $("#ls_packer").selectpicker({ liveSearch: true, size: 8 });
    }

    function panelLostScanAktif() {
        return $("#panel_lost_scan").is(":visible") && $("#ls_mode_simpan").is(":visible");
    }

    function tampilkanPanelLostScan(noresi) {
        if (lsResi === noresi && $("#panel_lost_scan").is(":visible")) return;

        lsResi = noresi;
        $("#ls_noresi").text(noresi);
        setModeSimpan();
        $("#panel_lost_scan").show();
        fokusPacker();

        // Cek informatif: kalau sudah pernah dicatat, ganti ke mode info.
        // Kalau request-nya gagal, panel simpan tetap tampil.
        $.ajax({
            url: "scan-paket-ndd-new/cek-lost-scan",
            type: "POST",
            data: { noresi: noresi },
            dataType: "json",
            success: function(r) {
                if (lsResi !== noresi) return; // panel sudah pindah ke resi lain
                if (r.code === 200 && r.data && r.data.sudah_dicatat) {
                    setModeInfo(r.data.catatan);
                }
            }
        });
    }

    function setModeSimpan() {
        $("#ls_mode_info").hide();
        $("#ls_mode_simpan").show();
        $("#ls_simpan").prop("disabled", false);
        resetPilihanPacker();
    }

    function setModeInfo(catatan) {
        var c = catatan || {};
        var waktu = c.created_at ? String(c.created_at).substring(0, 16) : '-';
        var teks = 'Sudah dicatat lost scan ' + (c.lost_type || '') + ' → ' + (c.nama_packer || '-') +
            ', oleh ' + (c.nama_pelapor || '-') + ' pada ' + waktu + '. Tidak perlu dicatat lagi.';
        $("#ls_info_teks").text(teks);
        $("#ls_mode_simpan").hide();
        $("#ls_mode_info").show();
        $("#ls_tutup").focus();
    }

    function resetPilihanPacker() {
        $("#ls_packer").val('');
        if (lsAdaSelectpicker) $("#ls_packer").selectpicker('refresh');
    }

    function fokusPacker() {
        if (lsAdaSelectpicker) {
            // Membuka dropdown langsung menaruh kursor di kotak pencarian:
            // petugas tinggal mengetik nama.
            setTimeout(function() { $("#ls_packer").selectpicker('toggle'); }, 50);
        } else {
            $("#ls_packer").focus();
        }
    }

    function tutupPanelLostScan() {
        lsResi = null;
        $("#panel_lost_scan").hide();
        $("#noresi").focus();
    }

    function simpanLostScan() {
        if (!lsResi) return;

        var packer = $("#ls_packer").val();
        if (!packer) {
            noty({ text: 'Pilih packer dulu', layout: 'topRight', type: 'warning', timeout: 2500 });
            fokusPacker();
            return;
        }

        var resi = lsResi;
        $("#ls_simpan").prop("disabled", true);

        $.ajax({
            url: "scan-paket-ndd-new/simpan-lost-scan",
            type: "POST",
            data: { noresi: resi, nama_petugas: packer },
            dataType: "json",
            success: function(r) {
                if (r.code === 201) {
                    noty({ text: 'Lost scan packer dicatat: ' + resi + ' → ' + packer, layout: 'topRight', type: 'success', timeout: 3000 });
                    pushHistory(resi, 'LOST SCAN DICATAT → ' + packer, true, null);
                    playTag('audio-alert');
                    tutupPanelLostScan();
                } else if (r.data && r.data.sudah_dicatat) {
                    noty({ text: r.message, layout: 'topRight', type: 'warning', timeout: 3000 });
                    setModeInfo(r.data.catatan);
                } else {
                    noty({ text: r.message || 'Gagal menyimpan lost scan', layout: 'topRight', type: 'error', timeout: 3000 });
                    $("#ls_simpan").prop("disabled", false);
                }
            },
            error: function() {
                noty({ text: 'Kesalahan sistem saat menyimpan lost scan', layout: 'topRight', type: 'error', timeout: 3000 });
                $("#ls_simpan").prop("disabled", false);
            }
        });
    }

    $("#ls_simpan").on('click', simpanLostScan);
    $("#ls_tutup").on('click', tutupPanelLostScan);

    // Setelah packer dipilih (Enter di kotak cari / klik), fokus pindah ke
    // tombol Simpan -- Enter berikutnya langsung menyimpan.
    $("#ls_packer").on('changed.bs.select change', function() {
        if ($(this).val()) $("#ls_simpan").focus();
    });

    // Esc = tutup panel (kalau dropdown sedang terbuka, Esc pertama hanya
    // menutup dropdown -- itu perilaku bawaan bootstrap-select).
    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape' || !$("#panel_lost_scan").is(":visible")) return;
        if ($("#panel_lost_scan .bootstrap-select").hasClass('open')) return;
        tutupPanelLostScan();
    });

    // ===== SUARA ===== (sama dengan menu lama)
    var BATAS_SUARA = {
        'audio-jnt':     400,
        'audio-jne':     400,
        'audio-double':  900,
        'audio-fail':    900,
        'audio-cancel':  800,
        'audio-paket-double': 1900,
        'audio-cancel-order': 2100,
        'audio-tidak-ditemukan': 1500,
        'audio-alert':   500
    };
    var BATAS_DEFAULT = 500;

    function playTag(id) {
        var batas = BATAS_SUARA[id] || BATAS_DEFAULT;

        if (typeof suaraScan === 'function') {
            suaraScan(id, { batas: batas });
            return;
        }

        var el = document.getElementById(id);
        if (el) {
            try { el.currentTime = 0; } catch (err) {}
            el.play();
        }
    }

    function playCourierAudio(noresi) {
        if (typeof suaraKurir === "function") {
            suaraKurir(noresi);
            return;
        }
        playTag("audio-alert");
    }

    function playErrorAudio(exceptionCode) {
        if (exceptionCode === 'ALREADY_HANDOVER' || exceptionCode === 'ALREADY_SCANNED') {
            playTag('audio-paket-double');
        } else if (exceptionCode === 'NOT_PICKED' || exceptionCode === 'NOT_PACKED') {
            playTag('audio-fail');
        } else if (exceptionCode === 'ORDER_CANCELED' || exceptionCode === 'ORDER_COMPLETED') {
            playTag('audio-cancel-order');
        } else if (exceptionCode === 'NOT_FOUND') {
            playTag('audio-tidak-ditemukan');
        } else {
            playTag('audio-alert');
        }
    }
});
</script>
```

- [ ] **Step 2: Syntax check**

Run: `C:/xampp/php/php.exe -l application/views/scan_paket_ndd_new/index.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Uji langsung lewat URL (sebelum menu ada)**

Login sebagai webmaster, buka `http://localhost/new-iresis/` lalu di console browser: `$.get('scan-paket-ndd-new', function(d){ $('.page-content-wrap').html(d.view); })`.
Expected: halaman scan tampil dengan label NEW, tanpa teks JSON/HTML nyasar.

- [ ] **Step 4: Commit**

```bash
git add application/views/scan_paket_ndd_new/index.php
git commit -m "feat(scan-ndd-new): halaman scan dengan panel lost scan packer saat NOT_PACKED"
```

---

### Task 4: Migrasi menu + hak akses, naikkan `BOOTSTRAP_VERSI`

**Files:**
- Modify: `application/core/MY_Controller.php` — konstanta `BOOTSTRAP_VERSI` (baris 42), daftar pemanggilan di `jalankan_bootstrap_sekali()` (setelah `$this->run_masalah_picker_new_migration();`, baris ~95), method baru di akhir class (setelah `run_masalah_picker_new_migration()`).

**Interfaces:**
- Produces: baris `menu` dengan `uri = 'scan-paket-ndd-new'`, `parentid = 27` (TIM HO), `roleaccess` untuk `roleid` 1, 2, 5.

- [ ] **Step 1: Naikkan versi**

Ubah `const BOOTSTRAP_VERSI = '2026-09-17.7';` menjadi `const BOOTSTRAP_VERSI = '2026-09-18.1';`

- [ ] **Step 2: Daftarkan pemanggilan**

Setelah `$this->run_masalah_picker_new_migration();` tambahkan:
```php
        $this->run_scan_paket_ndd_new_migration();
```

- [ ] **Step 3: Tambah method migrasi** (sebelum `}` penutup class)

```php

    /**
     * Menu TIM HO -> "Scan Paket NDD New".
     *
     * Versi baru berdiri sendiri (controller Scan_paket_ndd_new, model, view,
     * route) di samping menu lama "Scan Paket NDD" (scan_logistic) yang tetap
     * aktif sebagai cadangan. Tidak ada tabel baru: lost scan tetap ditulis
     * ke tbllostscanpacker lewat model lama.
     *
     * Hak akses: disamakan dengan pemegang menu lama per 18 Sep 2026 --
     * webmaster (1), admin (2), ho (5) -- sebagai daftar tetap, bukan disalin
     * saat migrasi jalan (alasan yang sama dengan run_masalah_picker_new_migration).
     */
    protected function run_scan_paket_ndd_new_migration()
    {
        $uri_baru = 'scan-paket-ndd-new';
        $uri_lama = 'scan_logistic';

        $menu_lama = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri_lama])->row();

        // Urutan dikunci ke id terkecil -- lihat catatan di run_menu_scan_packer_webcam.
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri_baru])->row();
        if (!$menu) {
            $this->db->insert('menu', [
                'name'      => 'Scan Paket NDD New',
                'parentid'  => $menu_lama ? $menu_lama->parentid : 27,
                'uri'       => $uri_baru,
                'icon'      => $menu_lama ? $menu_lama->icon : 'fa fa-bolt',
                'sortorder' => $menu_lama ? ((int) $menu_lama->sortorder + 1) : 11,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        $role_boleh = [1, 2, 5];
        foreach ($role_boleh as $roleid) {
            $akses_ada = $this->db->get_where('roleaccess', ['roleid' => $roleid, 'menuid' => $menu_id])->row();
            if (!$akses_ada) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }
```

- [ ] **Step 4: Syntax check**

Run: `C:/xampp/php/php.exe -l application/core/MY_Controller.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Picu migrasi & verifikasi**

Buka `http://localhost/new-iresis/` (login), lalu:
```bash
cat application/cache/bootstrap_migrasi.txt
"C:/xampp/mysql/bin/mysql.exe" -u root -e "SELECT m.id,m.parentid,m.name,m.uri,m.sortorder,GROUP_CONCAT(r.roleid) roles FROM menu m LEFT JOIN roleaccess r ON r.menuid=m.id WHERE m.uri='scan-paket-ndd-new' GROUP BY m.id;" iresis-prod
```
Expected: penanda berisi `2026-09-18.1`; satu baris menu, `parentid 27`, `sortorder 11`, `roles 1,2,5`. Login ulang (cache menu di session) → menu "Scan Paket NDD New" tampil di TIM HO tepat di bawah "Scan Paket NDD".

- [ ] **Step 6: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat(scan-ndd-new): migrasi menu TIM HO + hak akses, naikkan BOOTSTRAP_VERSI"
```

---

### Task 5: Data dummy & uji manual menyeluruh

**Files:**
- Create: `dev_tools/dummy_scan_ndd_new.sql` (gitignored, tidak di-commit)

**Interfaces:**
- Consumes: seluruh Task 1–4.

- [ ] **Step 1: Tulis script data dummy**

Resi berawalan `DUMMYNDD_` (mengikuti pola `TESTCAM_*` yang sudah ada di DB lokal). Kurir 6 (JNT) supaya lolos aturan "Shopee bukan NDD"; marketplace 3 (Tokopedia). `id_user` 1 dipakai sebagai admin/packer dummy.

```sql
-- Data dummy uji menu Scan Paket NDD New. Hanya INSERT; semua resi berawalan DUMMYNDD_.
-- Skenario:
--   DUMMYNDD_BELUMPACK_1..3 : sudah picker, BELUM packing -> NOT_PACKED -> panel lost scan
--   DUMMYNDD_NORMAL         : sudah picker + packing      -> scan HO/NDD sukses
--   DUMMYNDD_BELUMPICK      : belum picker                -> NOT_PICKED (panel TIDAK muncul)
--   DUMMYNDD_SHOPEE         : kurir Shopee, sudah packing -> mode NDD ditolak NOT_NDD, mode REGULER sukses
INSERT INTO tblprintresi (tanggal_printresi, id_marketplace, noresi, nomorpicklist, batal, keterangan, id_kurir, admin_pegawai, status_pesanan, tipe_resi, created_at, created_by)
SELECT NOW(), 3, r.noresi, 'PL-DUMMYNDD', '0', 'dummy testing scan ndd new', r.id_kurir, 1, 'PROCESSING', 'satuan', NOW(), 'dummy'
FROM (
    SELECT 'DUMMYNDD_BELUMPACK_1' AS noresi, 6 AS id_kurir UNION ALL
    SELECT 'DUMMYNDD_BELUMPACK_2', 6 UNION ALL
    SELECT 'DUMMYNDD_BELUMPACK_3', 9 UNION ALL
    SELECT 'DUMMYNDD_NORMAL', 6 UNION ALL
    SELECT 'DUMMYNDD_BELUMPICK', 6 UNION ALL
    SELECT 'DUMMYNDD_SHOPEE', 7
) r
WHERE NOT EXISTS (SELECT 1 FROM tblprintresi p WHERE p.noresi = r.noresi);

-- Picker: semua kecuali DUMMYNDD_BELUMPICK
INSERT INTO tblresiambilbarang (tanggal_resiambilbarang, id_resi, yangambil_pegawai, pending, admin_pegawai, nama_komputer)
SELECT NOW(), p.id_printresi, 1, '', 1, 'DUMMY'
FROM tblprintresi p
WHERE p.noresi IN ('DUMMYNDD_BELUMPACK_1','DUMMYNDD_BELUMPACK_2','DUMMYNDD_BELUMPACK_3','DUMMYNDD_NORMAL','DUMMYNDD_SHOPEE')
  AND NOT EXISTS (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = p.id_printresi);

-- Packing: hanya DUMMYNDD_NORMAL dan DUMMYNDD_SHOPEE
INSERT INTO tblpacking (tanggal_packing, id_resi, packer_pegawai, keterangan)
SELECT NOW(), p.id_printresi, 1, 'DUMMY'
FROM tblprintresi p
WHERE p.noresi IN ('DUMMYNDD_NORMAL','DUMMYNDD_SHOPEE')
  AND NOT EXISTS (SELECT 1 FROM tblpacking k WHERE k.id_resi = p.id_printresi);

SELECT p.noresi, p.id_kurir,
       EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi=p.id_printresi) AS picked,
       EXISTS(SELECT 1 FROM tblpacking k WHERE k.id_resi=p.id_printresi) AS packed,
       EXISTS(SELECT 1 FROM tblresikeluar h WHERE h.id_resi=p.id_printresi) AS handover,
       EXISTS(SELECT 1 FROM tbllostscanpacker l WHERE l.noresi=p.noresi) AS lost_scan
FROM tblprintresi p WHERE p.noresi LIKE 'DUMMYNDD_%' ORDER BY p.noresi;
```

- [ ] **Step 2: Jalankan script**

Run: `"C:/xampp/mysql/bin/mysql.exe" -u root iresis-prod < dev_tools/dummy_scan_ndd_new.sql`
Expected: tabel ringkasan 6 baris; `BELUMPACK_*` picked=1 packed=0; `NORMAL`/`SHOPEE` packed=1; `BELUMPICK` picked=0; semua handover=0, lost_scan=0.

- [ ] **Step 3: Uji manual di browser** (login role webmaster/ho)

1. Menu TIM HO → Scan Paket NDD New tampil; halaman terbuka bersih.
2. Mode HO+NDD, scan `DUMMYNDD_BELUMPACK_1` → kartu merah "NOMOR RESI BELUM DI-PACKING", suara fail, panel lost scan muncul, dropdown terbuka dengan kotak cari.
3. Ketik nama packer → Enter → fokus ke Simpan → Enter → noty hijau, riwayat "LOST SCAN DICATAT → …", panel tertutup, fokus di input resi.
4. Cek: `SELECT * FROM tbllostscanpacker WHERE noresi LIKE 'DUMMYNDD_%'` → 1 baris `lost_type PACKER`, `created_by` = id user login. Buka TIM HO → Laporan Lost Scan → baris tampil.
5. Scan `DUMMYNDD_BELUMPACK_1` lagi → panel mode info "Sudah dicatat … oleh … pada …".
6. Mode REGULER, scan `DUMMYNDD_BELUMPACK_2` → panel muncul (kedua mode). Tekan Esc → panel tutup, fokus ke input resi.
7. Scan `DUMMYNDD_BELUMPACK_3` → panel muncul; tanpa menutupnya scan `DUMMYNDD_NORMAL` → kartu hijau sukses, counter naik, panel `BELUMPACK_3` tetap ada.
8. Scan `DUMMYNDD_BELUMPICK` → "belum di-picker", panel TIDAK muncul.
9. Mode HO+NDD scan `DUMMYNDD_SHOPEE` → ditolak NOT_NDD, panel tidak muncul.
10. Menu lama TIM HO → Scan Paket NDD masih berjalan seperti biasa.

- [ ] **Step 4: Rapikan riwayat commit dan siapkan merge**

```bash
git status --short   # pastikan dev_tools/ tidak ikut
git log --oneline master..HEAD
```
Expected: 5 commit (spec + 4 task), tidak ada file dev_tools terlacak. Merge ke `master` dengan `--no-ff` dilakukan setelah user mengonfirmasi hasil uji.
