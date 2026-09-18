# Salah Ambil Special — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menu baru TIM PACKER → "Salah Ambil Special": packer mengisi dua SKU sekali (yang seharusnya + yang keliru terambil), lalu men-scan puluhan resi spesial (1 SKU / 1 qty) berturut-turut; tiap resi yang lolos validasi langsung tercatat di `tblmasalahpicker` sebagai SALAH AMBIL, sama persis dengan hasil modal Masalah Picker di Scan Resi Packer.

**Architecture:** Controller + model + view + route baru berdampingan dengan `Packer` (tidak disentuh). Model hanya berisi query baca dan satu `INSERT` ke `tblmasalahpicker`; validasi 7 lapis ada di controller; cek "sudah dilaporkan" + `INSERT` dibungkus satu transaksi dengan `SELECT ... FOR UPDATE` pada baris resi. Menu + hak akses ditanam lewat migrasi bootstrap ter-gate versi di `MY_Controller`.

**Tech Stack:** CodeIgniter 3, PHP 8.2, MySQL/MariaDB, jQuery (sudah dimuat `main.php`), `suaraScan()` global dari `main.php` untuk bunyi.

Spec: `docs/superpowers/specs/2026-09-18-salah-ambil-special-design.md`

## Global Constraints

- Bahasa kerja Indonesia: komentar kode, pesan UI, pesan commit (`tipe(modul): deskripsi`).
- Semua respons AJAX lewat `MY_Controller::make_ajax_response($code, $message, $data)` — menghabiskan output buffer, selalu HTTP 200, status di body JSON, lalu `exit`.
- DILARANG `DELETE`/`DROP`/`TRUNCATE`. Tidak ada fitur batal di v1.
- Tidak mengubah `Packer.php`, `Packer_fcd.php`, `views/packer/scan_packer*.php`, menu CS/KPI.
- Tidak ada perubahan skema tabel. Tujuan tulis satu-satunya: `tblmasalahpicker` (kolom `id_printresi, noresi, sku, qty, id_typemasalah, qty_bermasalah, sku_salah, status, created_by, created`).
- Setiap migrasi/menu baru di `MY_Controller.php` WAJIB menaikkan `BOOTSTRAP_VERSI` → `2026-09-18.4`.
- Role boleh: `[1, 4]` (webmaster, client packer), dikunci di dua tempat yang harus sejalan: `Salah_ambil_special::ROLE_BOLEH` dan `run_salah_ambil_special_migration()`.
- Form di view wajib class `nojs` + `preventDefault` sendiri; JS diikat ke `#sas-root`, bukan `document`.
- Tidak ada test suite: verifikasi = `C:/xampp/php/php.exe -l <file>` + uji manual di browser (`http://localhost/new-iresis/`). Script diagnostik ditaruh di `dev_tools/` (gitignored), tidak di-commit.
- Branch kerja: `feature/salah-ambil-special` (sudah ada, berisi spec). Commit per task.

---

### Task 1: Model `Salah_ambil_special_fcd`

**Files:**
- Create: `application/models/Salah_ambil_special_fcd.php`

**Interfaces:**
- Produces: `cari_sku(string $kode): array|null` — `['id_sku','nama_sku','no_rak']` dari `tblsku`, atau `null`.
- Produces: `resi_terbaru(string $noresi): array|null` — `['id_printresi','noresi']` dengan `id_printresi` terbaru, atau `null`.
- Produces: `detail_resi(int $id_printresi): array` — daftar `['sku','jumlah']` dari `tbldetailprintresi`.
- Produces: `sudah_packing(int $id_printresi): bool`.
- Produces: `picker_resi(int $id_printresi): array|null` — `['kode_picker','nama_picker']`, `null` kalau belum ada di `tblresiambilbarang`.
- Produces: `laporan_ada(int $id_printresi, string $sku): array|null` — `['id_masalahpicker','status']` baris terakhir, atau `null`.
- Produces: `kunci_resi(int $id_printresi): void` — `SELECT ... FOR UPDATE`, dipanggil di dalam transaksi.
- Produces: `simpan_salah_ambil(array $data): int` — `$data = ['id_printresi','noresi','sku','sku_salah','id_user']`, mengembalikan `insert_id`.
- Produces: konstanta `Salah_ambil_special_fcd::TIPE_SALAH_AMBIL = 4`.

- [ ] **Step 1: Tulis model**

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PACKER -> "Salah Ambil Special".
 *
 * Sengaja tidak memakai Packer_fcd::save_masalah_picker(): fungsi itu
 * meng-update baris tblmasalahpicker yang sudah ada dan mereset statusnya ke
 * pending, sedangkan menu ini harus MENOLAK resi yang sudah pernah dilaporkan
 * (status apa pun). Tabel tujuannya tetap tblmasalahpicker supaya laporan
 * langsung muncul di Daftar Masalah Picker (lama & New), KPI picker, dan
 * Error Recap tanpa perubahan di sana.
 *
 * Semua method di sini hanya query; urutan validasinya ada di controller.
 */
class Salah_ambil_special_fcd extends CI_Model
{
    /** Sama dengan Masalah_picker_new_fcd::TIPE_SALAH_AMBIL (tbltypemasalah). */
    const TIPE_SALAH_AMBIL = 4;

    /** Satu baris master SKU (id_sku, nama_sku, no_rak), atau NULL kalau kode tidak ada. */
    public function cari_sku($kode)
    {
        $row = $this->db->select('id_sku, nama_sku, no_rak')
            ->get_where('tblsku', ['id_sku' => $kode])
            ->row_array();
        return $row ?: NULL;
    }

    /**
     * id_printresi terbaru untuk satu noresi. Resi yang dicetak ulang punya
     * beberapa baris tblprintresi; yang dipakai yang terakhir dibuat, sama
     * dengan Packer::detail_resi() (ORDER BY created_at DESC, rows[0]).
     */
    public function resi_terbaru($noresi)
    {
        $row = $this->db->select('id_printresi, noresi')
            ->where('noresi', $noresi)
            ->order_by('created_at', 'DESC')
            ->order_by('id_printresi', 'DESC')
            ->limit(1)
            ->get('tblprintresi')
            ->row_array();
        return $row ?: NULL;
    }

    /** Semua baris SKU di resi: [['sku' => ..., 'jumlah' => ...], ...]. */
    public function detail_resi($id_printresi)
    {
        return $this->db->select('sku, jumlah')
            ->where('id_resi', $id_printresi)
            ->order_by('id_detail_resi', 'ASC')
            ->get('tbldetailprintresi')
            ->result_array();
    }

    public function sudah_packing($id_printresi)
    {
        return $this->db->where('id_resi', $id_printresi)->count_all_results('tblpacking') > 0;
    }

    /**
     * Picker yang scan ambil resi ini: kode pegawai + nama (tblpegawai, cadangan
     * tbluser.name). NULL kalau resi belum pernah di-scan ambil -- CS tidak
     * bisa mengaitkan laporan ke siapa pun, jadi controller menolaknya.
     */
    public function picker_resi($id_printresi)
    {
        $row = $this->db->select('rab.yangambil_pegawai AS kode_picker, COALESCE(peg.nama_pegawai, u.name) AS nama_picker', FALSE)
            ->from('tblresiambilbarang rab')
            ->join('tblpegawai peg', 'peg.kode_pegawai = rab.yangambil_pegawai', 'left')
            ->join('tbluser u', 'u.id_pegawai = rab.yangambil_pegawai', 'left')
            ->where('rab.id_resi', $id_printresi)
            ->order_by('rab.id_resiambilbarang', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
        return $row ?: NULL;
    }

    /** Laporan masalah picker yang sudah ada untuk resi + SKU ini, status apa pun. */
    public function laporan_ada($id_printresi, $sku)
    {
        $row = $this->db->select('id_masalahpicker, status')
            ->where('id_printresi', $id_printresi)
            ->where('sku', $sku)
            ->order_by('id_masalahpicker', 'DESC')
            ->limit(1)
            ->get('tblmasalahpicker')
            ->row_array();
        return $row ?: NULL;
    }

    /**
     * Kunci baris resi selama transaksi berjalan. Dua scan resi yang sama yang
     * datang nyaris bersamaan jadi antre di sini: yang kedua baru lanjut
     * setelah yang pertama commit, lalu tertangkap laporan_ada().
     * Wajib dipanggil di antara trans_begin() dan trans_commit().
     */
    public function kunci_resi($id_printresi)
    {
        $this->db->query('SELECT id_printresi FROM tblprintresi WHERE id_printresi = ? FOR UPDATE', [(int) $id_printresi]);
    }

    /**
     * Satu baris SALAH AMBIL, bentuknya sama dengan yang dibuat modal Masalah
     * Picker di Scan Resi Packer (qty 1, qty_bermasalah 1, status 0 = pending).
     */
    public function simpan_salah_ambil(array $data)
    {
        $this->db->insert('tblmasalahpicker', [
            'id_printresi'   => (int) $data['id_printresi'],
            'noresi'         => $data['noresi'],
            'sku'            => $data['sku'],
            'qty'            => 1,
            'id_typemasalah' => self::TIPE_SALAH_AMBIL,
            'qty_bermasalah' => 1,
            'sku_salah'      => $data['sku_salah'],
            'status'         => 0,
            'created_by'     => (int) $data['id_user'],
            'created'        => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }
}
```

- [ ] **Step 2: Syntax check**

Run: `C:/xampp/php/php.exe -l application/models/Salah_ambil_special_fcd.php`
Expected: `No syntax errors detected in application/models/Salah_ambil_special_fcd.php`

- [ ] **Step 3: Commit**

```bash
git add application/models/Salah_ambil_special_fcd.php
git commit -m "feat(packer): model Salah_ambil_special_fcd untuk validasi resi spesial dan simpan salah ambil"
```

---

### Task 2: Controller `Salah_ambil_special` + route

**Files:**
- Create: `application/controllers/Salah_ambil_special.php`
- Modify: `application/config/routes.php` (setelah blok `masalah-picker-new`, sekitar baris 336)

**Interfaces:**
- Consumes: semua method `Salah_ambil_special_fcd` dari Task 1.
- Produces endpoint `POST salah-ambil-special/cek-sku` `{sku_benar, sku_salah}` → `{code: 200, data: {sku_benar: {id_sku,nama_sku,no_rak}, sku_salah: {...}}}` atau `{code: 400, message}`.
- Produces endpoint `POST salah-ambil-special/scan-resi` `{noresi, sku_benar, sku_salah}` → `{code: 201, data: {noresi, sku, sku_salah, nama_picker}}` atau `{code: 400|404|409|422|500, message}`.
- Produces `GET salah-ambil-special` → JSON `{view, message}` (via `show()`), view `salah_ambil_special/index` (Task 3) menerima `$akses_ditolak` opsional.

- [ ] **Step 1: Tulis controller**

```php
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
```

- [ ] **Step 2: Tambah route**

Di `application/config/routes.php`, tepat setelah baris `$route['masalah-picker-new/data-cetak-ulang'] = ...;`, tambahkan:

```php

// Menu TIM PACKER -> Salah Ambil Special (controller Salah_ambil_special)
$route['salah-ambil-special'] = 'salah_ambil_special/index';
$route['salah-ambil-special/cek-sku'] = 'salah_ambil_special/cek_sku';
$route['salah-ambil-special/scan-resi'] = 'salah_ambil_special/scan_resi';
```

- [ ] **Step 3: Syntax check**

Run:
```bash
C:/xampp/php/php.exe -l application/controllers/Salah_ambil_special.php
```
```bash
C:/xampp/php/php.exe -l application/config/routes.php
```
Expected: `No syntax errors detected` untuk keduanya.

- [ ] **Step 4: Uji endpoint tanpa view (curl, sebelum view ada)**

Login dulu di browser sebagai role packer, ambil nilai cookie session (`ci_session` di DevTools → Application → Cookies), lalu:

```bash
curl -s -b "ci_session=ISI_COOKIE" -X POST -d "sku_benar=&sku_salah=" http://localhost/new-iresis/salah-ambil-special/cek-sku
```
Expected: `{"code":400,"message":"SKU seharusnya dan SKU terambil wajib diisi"}`

```bash
curl -s -b "ci_session=ISI_COOKIE" -X POST -d "noresi=TIDAKADA&sku_benar=BSBI-4&sku_salah=BSBI-5" http://localhost/new-iresis/salah-ambil-special/scan-resi
```
Expected (dengan dua SKU yang memang ada di `tblsku`): `{"code":404,"message":"Resi tidak ditemukan"}`. Kalau salah satu SKU tidak ada: `{"code":400,"message":"Kunci SKU dulu: SKU seharusnya \"BSBI-4\" tidak ada di master SKU"}`.

Balasan harus JSON murni — tidak ada teks/HTML sebelum `{`.

- [ ] **Step 5: Commit**

```bash
git add application/controllers/Salah_ambil_special.php application/config/routes.php
git commit -m "feat(packer): controller Salah_ambil_special dengan validasi 7 lapis + route"
```

---

### Task 3: View `salah_ambil_special/index.php`

**Files:**
- Create: `application/views/salah_ambil_special/index.php`

**Interfaces:**
- Consumes: endpoint `salah-ambil-special/cek-sku` dan `salah-ambil-special/scan-resi` dari Task 2 (bentuk balasan persis seperti di Interfaces Task 2).
- Consumes: `suaraScan(id)` global dari `application/views/main.php` dan elemen `<audio id="audio-alert">`, `<audio id="audio-fail">` yang sudah ada di layout.
- Consumes: variabel view opsional `$akses_ditolak` (bool).

- [ ] **Step 1: Tulis view**

```php
<?php
// View menu TIM PACKER -> Salah Ambil Special. Dimuat lewat AJAX (SPA) oleh
// plugins.js: semua handler diikat ke #sas-root supaya ikut hilang saat user
// pindah menu, dan kedua form diberi class "nojs" + preventDefault sendiri --
// tanpa itu handler global plugins.js mengganti seluruh .page-content-wrap
// saat request berjalan, input resi hilang dari DOM, dan scan yang diketik
// scanner selama itu lenyap (lihat komentar di packer/scan_packer.php).
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Salah Ambil Special</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Hak aksesnya sama dengan menu <em>Scan Resi Packer (Webcam)</em>; minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif; ?>
<div id="sas-root">
  <div class="row">
    <div class="col-md-12">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Salah Ambil Special</strong>
            <small class="text-muted" style="margin-left:8px;">resi 1 SKU / 1 qty yang salah diambil picker, dilaporkan sekaligus</small>
          </h3>
        </div>
        <div class="panel-body">
          <form class="form-horizontal nojs" id="sas-form-sku" autocomplete="off">
            <div class="form-group" style="margin-bottom:0;">
              <label class="col-md-2 col-xs-12 control-label">SKU seharusnya</label>
              <div class="col-md-3 col-xs-12">
                <input type="text" id="sas-sku-benar" class="form-control" placeholder="mis. BSBI-4" />
                <p class="help-block" id="sas-info-benar" style="margin-bottom:0;"></p>
              </div>
              <label class="col-md-2 col-xs-12 control-label">SKU terambil (salah)</label>
              <div class="col-md-3 col-xs-12">
                <input type="text" id="sas-sku-salah" class="form-control" placeholder="mis. BSBI-5" />
                <p class="help-block" id="sas-info-salah" style="margin-bottom:0;"></p>
              </div>
              <div class="col-md-2 col-xs-12">
                <button type="submit" class="btn btn-primary btn-block" id="sas-btn-kunci"><i class="fa fa-lock"></i> Kunci &amp; Mulai Scan</button>
                <button type="button" class="btn btn-warning btn-block hidden" id="sas-btn-ganti"><i class="fa fa-unlock"></i> Ganti SKU</button>
              </div>
            </div>
            <div class="alert alert-danger hidden" id="sas-pesan-sku" style="margin:10px 0 0 0;"></div>
          </form>
        </div>
      </div>

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Scan Resi</strong>
            <span class="pull-right">
              <span class="label label-success" id="sas-cnt-ok">Tercatat: 0</span>
              <span class="label label-danger" id="sas-cnt-tolak">Ditolak: 0</span>
            </span>
          </h3>
        </div>
        <div class="panel-body">
          <form class="form-horizontal nojs" id="sas-form-scan" autocomplete="off">
            <div class="form-group">
              <div class="col-md-12">
                <input type="text" id="sas-noresi" class="form-control input-lg"
                  placeholder="Kunci SKU dulu, lalu scan nomor resi di sini" disabled />
              </div>
            </div>
          </form>
          <p class="text-muted" style="margin-bottom:6px;">
            Daftar di bawah hanya untuk sesi ini di layar; catatan resminya ada di menu <em>Daftar Masalah Picker</em>.
          </p>
          <table class="table table-bordered table-striped" id="sas-table" style="margin-bottom:0;">
            <thead>
              <tr>
                <th style="width:50px;">#</th>
                <th style="width:220px;">Nomor resi</th>
                <th>Hasil</th>
                <th style="width:200px;">Picker</th>
                <th style="width:90px;">Jam</th>
              </tr>
            </thead>
            <tbody>
              <tr id="sas-kosong"><td colspan="5" class="text-muted text-center">Belum ada scan</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#sas-root');
  var URL = {
    cekSku: 'salah-ambil-special/cek-sku',
    scan:   'salah-ambil-special/scan-resi'
  };
  var BARIS_KOSONG = '<tr id="sas-kosong"><td colspan="5" class="text-muted text-center">Belum ada scan</td></tr>';

  var terkunci = false, skuBenar = '', skuSalah = '';
  // Scan diantre dan dikirim satu per satu: scanner bisa menembak lebih cepat
  // dari balasan server, dan urutan baris di tabel harus sama dengan urutan scan.
  var antrian = [], sedangKirim = false;
  var nomor = 0, jumlahOk = 0, jumlahTolak = 0;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  // Semua suara lewat suaraScan (main.php): memotong durasi dan mereset posisi
  // supaya scan kedua yang datang sebelum suara pertama habis tetap berbunyi.
  function bunyi(id) {
    if (typeof suaraScan === 'function') { suaraScan(id); return; }
    var el = document.getElementById(id);
    if (el && typeof el.play === 'function') { el.currentTime = 0; el.play(); }
  }
  function jam() {
    var d = new Date();
    return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + ':' + ('0' + d.getSeconds()).slice(-2);
  }
  function perbaruiCounter() {
    $('#sas-cnt-ok').text('Tercatat: ' + jumlahOk);
    $('#sas-cnt-tolak').text('Ditolak: ' + jumlahTolak);
  }
  function infoSku(row) {
    return (row.nama_sku || '-') + ' \u2014 rak ' + (row.no_rak || '-');
  }
  function tolakSku(pesan) {
    $('#sas-pesan-sku').removeClass('hidden').text(pesan);
    bunyi('audio-fail');
  }

  // ---------- kunci SKU ----------
  $root.on('submit', '#sas-form-sku', function (e) {
    e.preventDefault();
    if (terkunci) { return; }

    var benar = $('#sas-sku-benar').val().trim();
    var salah = $('#sas-sku-salah').val().trim();
    $('#sas-pesan-sku').addClass('hidden').text('');
    $('#sas-btn-kunci').prop('disabled', true);

    $.post(URL.cekSku, { sku_benar: benar, sku_salah: salah }, null, 'json')
      .done(function (res) {
        if (Number(res.code) !== 200 || !res.data) { tolakSku(res.message || 'SKU tidak valid'); return; }
        skuBenar = res.data.sku_benar.id_sku;
        skuSalah = res.data.sku_salah.id_sku;
        $('#sas-sku-benar').val(skuBenar).prop('readonly', true);
        $('#sas-sku-salah').val(skuSalah).prop('readonly', true);
        $('#sas-info-benar').text(infoSku(res.data.sku_benar));
        $('#sas-info-salah').text(infoSku(res.data.sku_salah));
        terkunci = true;
        $('#sas-btn-kunci').addClass('hidden');
        $('#sas-btn-ganti').removeClass('hidden');
        $('#sas-noresi').prop('disabled', false)
          .attr('placeholder', 'Scan nomor resi yang terambil ' + skuSalah + ' (seharusnya ' + skuBenar + ')')
          .focus();
      })
      .fail(function (xhr) {
        // Bukan JSON = ada output nyasar (warning PHP / halaman error CI).
        tolakSku('Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200));
      })
      .always(function () {
        $('#sas-btn-kunci').prop('disabled', false);
      });
  });

  // ---------- ganti SKU ----------
  $root.on('click', '#sas-btn-ganti', function () {
    if (!confirm('Buka kunci SKU dan kosongkan daftar scan di layar?\nResi yang sudah tercatat tetap tersimpan.')) { return; }
    terkunci = false; skuBenar = ''; skuSalah = '';
    antrian = [];
    nomor = 0; jumlahOk = 0; jumlahTolak = 0;
    perbaruiCounter();
    $('#sas-sku-benar, #sas-sku-salah').prop('readonly', false);
    $('#sas-info-benar, #sas-info-salah').text('');
    $('#sas-pesan-sku').addClass('hidden').text('');
    $('#sas-btn-ganti').addClass('hidden');
    $('#sas-btn-kunci').removeClass('hidden');
    $('#sas-noresi').val('').prop('disabled', true).attr('placeholder', 'Kunci SKU dulu, lalu scan nomor resi di sini');
    $('#sas-table tbody').html(BARIS_KOSONG);
    $('#sas-sku-benar').focus();
  });

  // ---------- scan ----------
  // Nilai input langsung diambil lalu dikosongkan, jadi scanner boleh
  // mengetik resi berikutnya kapan saja.
  $root.on('submit', '#sas-form-scan', function (e) {
    e.preventDefault();
    var nilai = $('#sas-noresi').val().trim();
    $('#sas-noresi').val('').focus();
    if (nilai === '' || !terkunci) { return; }
    antrian.push(nilai);
    prosesAntrian();
  });

  function prosesAntrian() {
    if (sedangKirim || antrian.length === 0) { return; }
    sedangKirim = true;
    var noresi = antrian.shift();

    $.post(URL.scan, { noresi: noresi, sku_benar: skuBenar, sku_salah: skuSalah }, null, 'json')
      .done(function (res) {
        if (Number(res.code) === 201 && res.data) {
          tambahBaris(noresi, true, res.data.sku + ' \u2192 terambil ' + res.data.sku_salah, res.data.nama_picker);
        } else {
          tambahBaris(noresi, false, res.message || 'Ditolak', '');
        }
      })
      .fail(function (xhr) {
        tambahBaris(noresi, false, 'Server tidak membalas JSON: ' + (xhr.responseText || xhr.statusText || '').slice(0, 200), '');
      })
      .always(function () {
        sedangKirim = false;
        prosesAntrian();
      });
  }

  function tambahBaris(noresi, ok, keterangan, picker) {
    $('#sas-kosong').remove();
    nomor++;
    if (ok) { jumlahOk++; } else { jumlahTolak++; }
    perbaruiCounter();
    bunyi(ok ? 'audio-alert' : 'audio-fail');

    var label = ok
      ? '<span class="label label-success">Tercatat</span> '
      : '<span class="label label-danger">Ditolak</span> ';
    $('#sas-table tbody').prepend(
      '<tr class="' + (ok ? 'success' : 'danger') + '">' +
        '<td>' + nomor + '</td>' +
        '<td><strong>' + esc(noresi) + '</strong></td>' +
        '<td>' + label + esc(keterangan) + '</td>' +
        '<td>' + esc(picker || '-') + '</td>' +
        '<td>' + jam() + '</td>' +
      '</tr>'
    );
  }

  $('#sas-sku-benar').focus();
})();
</script>

<style>
  #sas-table td { vertical-align: middle; }
  #sas-noresi { font-size: 20px; }
</style>
```

- [ ] **Step 2: Syntax check**

Run: `C:/xampp/php/php.exe -l application/views/salah_ambil_special/index.php`
Expected: `No syntax errors detected in application/views/salah_ambil_special/index.php`

- [ ] **Step 3: Uji halaman lewat URL langsung (menu belum ada sampai Task 4)**

Login sebagai role packer, buka `http://localhost/new-iresis/salah-ambil-special` di tab baru — balasannya JSON `{"view":"...","message":null}` (ini normal: halaman SPA dimuat lewat AJAX). Pastikan tidak ada teks/warning PHP sebelum `{`.

Lalu di console DevTools halaman utama aplikasi (setelah login), muat view lewat mekanisme SPA:

```js
$.get('salah-ambil-special', function (r) { $('.page-content-wrap').html(r.view); }, 'json');
```

Expected:
1. Dua panel tampil; kursor otomatis di field "SKU seharusnya"; field resi disabled.
2. Kunci dengan dua kode kosong → alert merah "SKU seharusnya dan SKU terambil wajib diisi", bunyi gagal.
3. Kunci dengan kode yang sama → "tidak boleh sama".
4. Kunci dengan kode yang tidak ada → `SKU seharusnya "XXX" tidak ada di master SKU`.
5. Kunci dengan dua kode valid → nama barang + rak tampil di bawah tiap field, field readonly, tombol berubah "Ganti SKU", field resi aktif dan fokus.
6. Scan resi tidak ada → baris merah "Ditolak Resi tidak ditemukan", counter Ditolak 1, bunyi gagal, fokus tetap di field resi.
7. Ganti SKU → confirm → semua kembali ke kondisi awal.

- [ ] **Step 4: Commit**

```bash
git add application/views/salah_ambil_special/index.php
git commit -m "feat(packer): view Salah Ambil Special — kunci dua SKU lalu scan resi berantai"
```

---

### Task 4: Migrasi menu + hak akses, naikkan `BOOTSTRAP_VERSI`

**Files:**
- Modify: `application/core/MY_Controller.php` — konstanta `BOOTSTRAP_VERSI` (baris 42), daftar pemanggilan di `jalankan_bootstrap_sekali()` (setelah baris 97 `$this->run_scan_paket_ndd_new_migration();`), dan method baru di akhir class (sebelum `}` penutup terakhir, setelah `run_scan_paket_ndd_new_migration()`).

**Interfaces:**
- Produces: baris `menu` dengan `uri = 'salah-ambil-special'` di bawah parent menu `packer/scan_packer`, plus `roleaccess` untuk role 1 dan 4. Harus sejalan dengan `Salah_ambil_special::ROLE_BOLEH` (Task 2).

- [ ] **Step 1: Naikkan versi bootstrap**

Ubah baris 42:

```php
    const BOOTSTRAP_VERSI = '2026-09-18.4';
```

- [ ] **Step 2: Daftarkan pemanggilan migrasi**

Di `jalankan_bootstrap_sekali()`, tepat setelah `$this->run_scan_paket_ndd_new_migration();`, tambahkan:

```php
        $this->run_salah_ambil_special_migration();
```

- [ ] **Step 3: Tulis method migrasi**

Tambahkan sebelum `}` penutup class (paling akhir file):

```php

    /**
     * Menu TIM PACKER -> "Salah Ambil Special" (controller Salah_ambil_special).
     *
     * Untuk batch resi spesial (1 SKU / 1 qty) yang salah diambil picker:
     * dua SKU diisi sekali, semua resi discan berantai, tiap resi langsung
     * jadi baris SALAH AMBIL di tblmasalahpicker. Tidak ada tabel baru.
     *
     * Hak akses disamakan dengan Scan Resi Packer (Webcam): webmaster (1) dan
     * client packer (4). Daftar yang sama dikunci di
     * Salah_ambil_special::ROLE_BOLEH; keduanya harus sejalan.
     */
    protected function run_salah_ambil_special_migration()
    {
        $uri = 'salah-ambil-special';

        // Urutan dikunci ke id terkecil -- lihat catatan di run_menu_scan_packer_webcam.
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri])->row();
        if (!$menu) {
            // Induknya diambil dari menu Scan Resi Packer supaya ikut pindah kalau
            // grup TIM PACKER pernah ditata ulang; 24 hanya cadangan.
            $menu_asal = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => 'packer/scan_packer'])->row();
            $parent_id = $menu_asal ? $menu_asal->parentid : 24;

            $this->db->insert('menu', [
                'name'      => 'Salah Ambil Special',
                'parentid'  => $parent_id,
                'uri'       => $uri,
                'icon'      => 'fa fa-exchange',
                'sortorder' => 12,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        $role_boleh = [1, 4];
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
Expected: `No syntax errors detected in application/core/MY_Controller.php`

- [ ] **Step 5: Uji menu di browser**

1. Logout, login lagi sebagai role packer (session menyimpan cache pohon menu berkunci `BOOTSTRAP_VERSI|role`; login ulang memastikan cache lama tidak dipakai). Request pertama menjalankan migrasi dan menulis `application/cache/bootstrap_migrasi.txt` berisi `2026-09-18.4`.
2. Grup TIM PACKER kini punya item **Salah Ambil Special** setelah Scan Resi Packer (Webcam); klik → halaman Task 3 tampil tanpa reload.
3. Login sebagai role selain 1/4 (mis. tim retur 6): menu tidak muncul; buka `salah-ambil-special` via `$.get(...)` seperti Task 3 Step 3 → panel merah "Role akun Anda tidak punya akses".
4. Cek file penanda:
```bash
cat application/cache/bootstrap_migrasi.txt
```
Expected: `2026-09-18.4`

- [ ] **Step 6: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat(packer): migrasi menu Salah Ambil Special + hak akses role 1,4; BOOTSTRAP_VERSI 2026-09-18.4"
```

---

### Task 5: Uji alur ujung ke ujung + dokumentasi

**Files:**
- Modify: `docs/ANALISIS_PROGRAM.md` (bagian `### 3. **Proses Packing**`, setelah baris `- **Search Packer**: Pencarian data packing`)
- Buat sementara (tidak di-commit): `dev_tools/cek_salah_ambil_special.php`

**Interfaces:**
- Consumes: semua yang sudah dibuat di Task 1–4.

- [ ] **Step 1: Siapkan data uji tanpa mengubah data produksi**

Butuh resi nyata yang memenuhi: tepat 1 baris `tbldetailprintresi` dengan `jumlah = 1`, sudah ada di `tblresiambilbarang`, belum ada di `tblpacking`, belum ada di `tblmasalahpicker`. Cari lewat script baca-saja di `dev_tools/cek_salah_ambil_special.php` (dijalankan via CLI CodeIgniter tidak tersedia; pakai koneksi langsung dengan kredensial dari `secrets.php`):

```php
<?php
// dev_tools/cek_salah_ambil_special.php -- baca saja, cari resi kandidat uji.
$s = require __DIR__ . '/../application/config/secrets.php';
$db = new mysqli($s['db_hostname'], $s['db_username'], $s['db_password'], $s['db_database']);
$sql = "SELECT pr.noresi, dr.sku, dr.jumlah
        FROM tblprintresi pr
        JOIN tbldetailprintresi dr ON dr.id_resi = pr.id_printresi
        JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
        LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
        LEFT JOIN tblmasalahpicker mp ON mp.id_printresi = pr.id_printresi
        WHERE p.id_resi IS NULL AND mp.id_printresi IS NULL
        GROUP BY pr.id_printresi
        HAVING COUNT(dr.id_detail_resi) = 1 AND SUM(dr.jumlah) = 1
        ORDER BY pr.created_at DESC LIMIT 5";
foreach ($db->query($sql) as $r) { echo implode("\t", $r), "\n"; }
```

Run: `C:/xampp/php/php.exe dev_tools/cek_salah_ambil_special.php` → catat satu `noresi` dan `sku`-nya (= SKU seharusnya). Pilih SKU terambil = kode lain yang ada di `tblsku`.

Kalau tidak ada kandidat, gunakan resi hasil uji picking di mesin lokal (scan ambil lewat menu Picking), jangan mengubah data lewat SQL.

- [ ] **Step 2: Uji tiap kondisi tolak**

Di menu Salah Ambil Special (role packer), kunci SKU dengan pasangan dari Step 1, lalu scan:

| Scan | Expected di tabel sesi |
|---|---|
| Nomor acak | Ditolak: `Resi tidak ditemukan` |
| Resi dengan >1 SKU atau qty >1 | Ditolak: `Bukan resi spesial (n SKU, qty m)` |
| Resi 1 SKU/1 qty tapi SKU lain | Ditolak: `SKU resi XXX, bukan <SKU seharusnya>` |
| Resi yang sudah di-packing (ada di tblpacking) dengan SKU yang sama | Ditolak: `Resi sudah di-packing` |
| Resi belum di-picker (tidak ada di tblresiambilbarang) | Ditolak: `Resi belum di-scan ambil picker` |

Setiap baris merah harus disertai bunyi `fail`, counter Ditolak bertambah, dan **tidak ada** baris baru di `tblmasalahpicker` (cek lewat menu Daftar Masalah Picker New rentang hari ini).

- [ ] **Step 3: Uji jalur sukses dan scan ganda**

1. Scan resi kandidat dari Step 1 → baris hijau `Tercatat <SKU> → terambil <SKU salah>`, kolom Picker terisi nama, bunyi `alert`, counter Tercatat 1.
2. Scan resi yang sama sekali lagi → baris merah `Sudah dilaporkan (pending)`, counter Ditolak +1.
3. Buka menu TIM CS → Daftar Masalah Picker New, rentang hari ini: ada tepat **satu** baris untuk resi itu, tipe SALAH AMBIL, kolom SKU Salah = SKU terambil, Picker = nama picker, Packer = nama user yang login.
4. Di menu yang sama klik Preview proses (tidak perlu benar-benar memproses): label item berbunyi `SALAH AMBIL (terambil <SKU salah>)`.
5. Klik Ganti SKU → confirm → daftar kosong; scan resi tadi lagi setelah kunci ulang → tetap `Sudah dilaporkan (pending)` (bukti tidak ada duplikat).

- [ ] **Step 4: Dokumentasi**

Di `docs/ANALISIS_PROGRAM.md`, setelah baris `- **Search Packer**: Pencarian data packing`, tambahkan:

```markdown
- **Salah Ambil Special** (`salah-ambil-special`, controller `Salah_ambil_special.php` + model `Salah_ambil_special_fcd.php`, sejak 18 Sep 2026): untuk batch resi spesial (tepat 1 SKU / 1 qty) yang salah diambil picker. Packer mengisi "SKU seharusnya" dan "SKU terambil" sekali, lalu men-scan semua resi berantai; tiap resi yang lolos validasi (resi ada, 1 SKU/1 qty, SKU cocok, belum packing, sudah di-picker, belum pernah dilaporkan) langsung jadi baris SALAH AMBIL di `tblmasalahpicker` — bentuknya sama dengan hasil modal Masalah Picker, jadi Daftar Masalah Picker/KPI tidak berubah. Role 1 & 4. Tidak ada fitur batal; spec di `docs/superpowers/specs/2026-09-18-salah-ambil-special-design.md`.
```

- [ ] **Step 5: Pastikan script uji tidak ikut commit, lalu commit dokumentasi**

```bash
git status --short
```
Expected: `dev_tools/` tidak muncul (gitignored); hanya `docs/ANALISIS_PROGRAM.md` yang termodifikasi (abaikan `index.php` dan `system/*` yang sudah termodifikasi sejak sebelum pekerjaan ini — jangan di-add).

```bash
git add docs/ANALISIS_PROGRAM.md
git commit -m "docs(packer): catat menu Salah Ambil Special di ANALISIS_PROGRAM"
```

---

### Task 6: Merge ke master

**Files:** tidak ada perubahan kode.

- [ ] **Step 1: Cek riwayat branch**

```bash
git log --oneline master..feature/salah-ambil-special
```
Expected: 6 commit (spec, model, controller+route, view, migrasi, docs).

- [ ] **Step 2: Merge tanpa fast-forward dan hapus branch**

```bash
git checkout master
```
```bash
git merge --no-ff feature/salah-ambil-special -m "Merge branch 'feature/salah-ambil-special'"
```
```bash
git branch -d feature/salah-ambil-special
```

- [ ] **Step 3: Push (hanya kalau user minta)**

```bash
git push origin master
```

Setelah merge, ingatkan user: di PC produksi ikuti `docs/PANDUAN_PULL_PRODUKSI.md`; migrasi menu jalan otomatis di request pertama setelah pull karena `BOOTSTRAP_VERSI` naik, dan user packer perlu login ulang agar menu baru muncul.
