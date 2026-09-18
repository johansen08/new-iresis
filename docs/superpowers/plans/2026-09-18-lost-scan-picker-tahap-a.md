# Lost Scan Picker Tahap A — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menu TIM PICKER → "Laporan Lost Scan Picker": antrean resi belum-picker yang dilaporkan packer/HO, dengan tombol **Tambahkan Picker** yang membuat baris picking atas nama picker pilihan tim picker dan mencatat lost scan PICKER.

**Architecture:** Tabel antrean baru `tbllostscanpicker_pending`; model `Lost_scan_picker_fcd` menyediakan `lapor()`/`cari_pending()` (dipakai tahap B/C) dan `tambah_picker()` (satu transaksi `FOR UPDATE`); controller `Lost_scan_picker` dengan `ROLE_BOLEH`; view DataTables server-side dua tab (Pending/Selesai) + modal; menu & tabel via migrasi bootstrap ter-gate versi.

**Tech Stack:** CodeIgniter 3, PHP 8.2, MariaDB, jQuery + DataTables + daterangepicker/moment + bootstrap-select + noty (sudah di `main.php`).

Spec: `docs/superpowers/specs/2026-09-18-lost-scan-picker-tahap-a-design.md`

## Global Constraints

- Bahasa kerja Indonesia (komentar, UI, commit `tipe(modul): deskripsi`).
- Respons AJAX lewat `make_ajax_response()`; DataTables lewat `kirim_json()` yang menghabiskan semua output buffer.
- DILARANG `DELETE`/`DROP`/`TRUNCATE`. Data dummy hanya `INSERT`.
- Tidak menyentuh `Lost_scan_packer_fcd.php`, `Resi_team_fcd.php`, `Picking_fcd.php` (hanya dipanggil).
- KPI picker **tidak** dicatat. `nama_komputer = 'LOST SCAN PICKER'` pada baris picking susulan.
- Role menu & `ROLE_BOLEH`: `[1, 2, 6]`.
- `BOOTSTRAP_VERSI` naik ke `2026-09-18.2` (branch NDD memakai `.1`; saat merge ambil yang tertinggi dan pastikan kedua migrasi terdaftar).
- Verifikasi: `C:/xampp/php/php.exe -l <file>` + uji manual di `http://localhost/new-iresis/`.
- Script dummy di `dev_tools/` (gitignored).

---

### Task 1: Model `Lost_scan_picker_fcd`

**Files:**
- Create: `application/models/Lost_scan_picker_fcd.php`

**Interfaces (Produces):**
- `lapor(string $noresi, string $sumber, array $user): array` → `['status' => 'DIBUAT'|'SUDAH_PENDING'|'SUDAH_PICKED'|'TIDAK_DITEMUKAN', 'pending' => array|null, 'message' => string]`
- `cari_pending(string $noresi): ?array` → baris PENDING + `nama_pelapor`
- `jumlah_pending(): int`
- `daftar(array $params): array` → `['rows' => array, 'total' => int]`; `$params` = `tab` ('pending'|'selesai'), `start`, `length`, `search`, `start_date`, `end_date`, `order`, `dir`
- `item_resi(array $id_printresi_list): array` → `[id_printresi => [['sku','jumlah','no_rak','nama_sku'], ...]]`
- `tambah_picker(int $id_pending, ?int $kode_picker, array $user): array` → sukses `['status' => 'SELESAI'|'SELESAI_LUAR', 'noresi', 'nama_picker']`; gagal `['error' => TRUE, 'code' => int, 'message' => string, 'kode' => string]`

- [ ] **Step 1: Tulis model**

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Antrean "Lost Scan Picker": resi yang ditolak karena belum di-picker,
 * dilaporkan packer (tahap B) atau HO (tahap C), lalu diselesaikan tim
 * picker lewat tombol Tambahkan Picker (menu TIM PICKER -> Laporan Lost Scan
 * Picker).
 *
 * Keputusan yang dikunci di sini:
 * - Pelapor tidak memilih picker. Nama picker baru ada saat tim picker
 *   memproses, dan baru saat itu pula baris tbllostscanpacker tipe PICKER
 *   dibuat -- tidak pernah ada catatan lost scan tanpa nama.
 * - Baris tblresiambilbarang dibuat demi integritas data supaya packer bisa
 *   scan ulang; KPI picker TIDAK dicatat (hukumannya sudah tercatat sebagai
 *   lost scan). nama_komputer = 'LOST SCAN PICKER' jadi penanda.
 * - Lost_scan_packer_fcd::save() tidak dipakai karena menolak duplikat per
 *   noresi tanpa melihat lost_type; HO mungkin sudah mencatat PACKER untuk
 *   resi yang sama.
 */
class Lost_scan_picker_fcd extends CI_Model
{
    const TABEL = 'tbllostscanpicker_pending';
    const PENANDA_KOMPUTER = 'LOST SCAN PICKER';

    // ------------------------------------------------------------------
    // Dipakai tahap B (packer) dan C (HO)
    // ------------------------------------------------------------------

    /**
     * Buat laporan untuk satu resi. Tidak pernah membuat dua PENDING untuk
     * resi yang sama.
     */
    public function lapor($noresi, $sumber, $user)
    {
        $noresi = trim((string) $noresi);
        $sumber = ($sumber === 'HO') ? 'HO' : 'PACKER';

        if ($noresi === '') {
            return ['status' => 'TIDAK_DITEMUKAN', 'pending' => null, 'message' => 'Nomor resi kosong'];
        }

        $resi = $this->db->query(
            "SELECT p.id_printresi,
                    EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = p.id_printresi) AS is_picked
             FROM tblprintresi p
             WHERE p.noresi = ?
             LIMIT 1",
            [$noresi]
        )->row();

        if (!$resi) {
            return ['status' => 'TIDAK_DITEMUKAN', 'pending' => null, 'message' => 'Nomor resi tidak ditemukan'];
        }
        if ($resi->is_picked) {
            return ['status' => 'SUDAH_PICKED', 'pending' => null, 'message' => 'Resi sudah di-picker, tidak perlu dilaporkan'];
        }

        $pending = $this->cari_pending($noresi);
        if ($pending) {
            return ['status' => 'SUDAH_PENDING', 'pending' => $pending, 'message' => 'Resi sudah dilaporkan, menunggu tim picker'];
        }

        $this->db->insert(self::TABEL, [
            'id_printresi'    => (int) $resi->id_printresi,
            'noresi'          => $noresi,
            'sumber'          => $sumber,
            'dilaporkan_oleh' => (int) $user['id_user'],
            'waktu_lapor'     => date('Y-m-d H:i:s'),
            'status'          => 'PENDING',
        ]);

        return ['status' => 'DIBUAT', 'pending' => $this->cari_pending($noresi), 'message' => 'Laporan lost scan picker dibuat'];
    }

    /** Baris PENDING untuk resi itu beserta nama pelapor, atau null. */
    public function cari_pending($noresi)
    {
        $row = $this->db->query(
            "SELECT t.*, u.name AS nama_pelapor
             FROM " . self::TABEL . " t
             LEFT JOIN tbluser u ON u.id_user = t.dilaporkan_oleh
             WHERE t.noresi = ? AND t.status = 'PENDING'
             ORDER BY t.id_pending DESC
             LIMIT 1",
            [$noresi]
        )->row_array();

        return $row ? $row : null;
    }

    // ------------------------------------------------------------------
    // Halaman laporan
    // ------------------------------------------------------------------

    public function jumlah_pending()
    {
        return (int) $this->db->where('status', 'PENDING')->count_all_results(self::TABEL);
    }

    /**
     * Data DataTables. Tab pending: SEMUA baris PENDING (tanpa filter
     * tanggal -- antrean tidak boleh tersembunyi). Tab selesai: SELESAI dan
     * SELESAI_LUAR yang waktu_proses-nya di rentang.
     */
    public function daftar($params)
    {
        $tab = ($params['tab'] === 'selesai') ? 'selesai' : 'pending';

        $this->terapkan_filter($params, $tab);
        $total = $this->db->count_all_results(self::TABEL . ' t');

        $this->terapkan_filter($params, $tab);
        $this->db->select("t.*, u.name AS nama_pelapor, up.name AS nama_pemroses, pg.nama_pegawai AS nama_picker,
            EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = t.id_printresi) AS sudah_picked", FALSE);

        $urut = !empty($params['order']) ? $params['order'] : ($tab === 'pending' ? 't.waktu_lapor' : 't.waktu_proses');
        $arah = (isset($params['dir']) && strtolower($params['dir']) === 'asc') ? 'ASC' : 'DESC';
        $this->db->order_by($urut, $arah, FALSE);

        if (isset($params['length']) && (int) $params['length'] > 0) {
            $this->db->limit((int) $params['length'], (int) $params['start']);
        }

        $rows = $this->db->get(self::TABEL . ' t')->result_array();

        return ['rows' => $rows, 'total' => $total];
    }

    private function terapkan_filter($params, $tab)
    {
        $this->db->join('tbluser u', 'u.id_user = t.dilaporkan_oleh', 'left');
        $this->db->join('tbluser up', 'up.id_user = t.diproses_oleh', 'left');
        $this->db->join('tblpegawai pg', 'pg.kode_pegawai = t.kode_picker', 'left');

        if ($tab === 'pending') {
            $this->db->where('t.status', 'PENDING');
        } else {
            $this->db->where_in('t.status', ['SELESAI', 'SELESAI_LUAR']);
            $this->db->where('t.waktu_proses >=', $params['start_date']);
            $this->db->where('t.waktu_proses <=', $params['end_date']);
        }

        if (!empty($params['search'])) {
            $this->db->group_start();
            $this->db->like('t.noresi', $params['search']);
            $this->db->or_like('u.name', $params['search']);
            $this->db->or_like('pg.nama_pegawai', $params['search']);
            $this->db->group_end();
        }
    }

    /**
     * Item (SKU x qty @ rak) untuk sekumpulan resi -- satu query untuk satu
     * halaman tabel, bukan satu query per baris.
     */
    public function item_resi(array $id_list)
    {
        $hasil = [];
        $id_list = array_values(array_unique(array_map('intval', $id_list)));
        if (empty($id_list)) {
            return $hasil;
        }

        $rows = $this->db->query(
            "SELECT dr.id_resi, dr.sku, dr.jumlah, dr.no_rak,
                    COALESCE(NULLIF(NULLIF(TRIM(s.nama_sku), ''), 'False'), '') AS nama_sku
             FROM tbldetailprintresi dr
             LEFT JOIN tblsku s ON s.id_sku = dr.sku
             WHERE dr.id_resi IN (" . implode(',', $id_list) . ")
             ORDER BY dr.id_resi, dr.no_rak, dr.sku"
        )->result_array();

        foreach ($rows as $r) {
            $hasil[(int) $r['id_resi']][] = $r;
        }
        return $hasil;
    }

    // ------------------------------------------------------------------
    // Proses "Tambahkan Picker"
    // ------------------------------------------------------------------

    /**
     * Satu transaksi:
     *  1. kunci baris pending (FOR UPDATE), harus PENDING
     *  2. resi ada & tidak batal
     *  3. kalau picking sudah ada (dibuat di luar alur, mis. SCAN COMBINED)
     *     -> tutup sebagai SELESAI_LUAR, tidak menulis picking/lost scan
     *  4. picker valid & aktif
     *  5. insert tblresiambilbarang atas nama picker (tanpa KPI)
     *  6. insert tbllostscanpacker tipe PICKER (unik per noresi+PICKER)
     *  7. tutup pending -> SELESAI
     */
    public function tambah_picker($id_pending, $kode_picker, $user)
    {
        $id_pending  = (int) $id_pending;
        $kode_picker = (int) $kode_picker;

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_begin();

        $pending = $this->db->query("SELECT * FROM " . self::TABEL . " WHERE id_pending = ? FOR UPDATE", [$id_pending])->row();
        if (!$pending) {
            return $this->batal($prev_debug, 404, 'Laporan tidak ditemukan', 'TIDAK_DITEMUKAN');
        }
        if ($pending->status !== 'PENDING') {
            $pemroses = $this->db->select('name')->get_where('tbluser', ['id_user' => (int) $pending->diproses_oleh])->row();
            $oleh = $pemroses ? $pemroses->name : '-';
            return $this->batal($prev_debug, 409, 'Laporan sudah diproses oleh ' . $oleh, 'SUDAH_DIPROSES');
        }

        $resi = $this->db->query(
            "SELECT p.id_printresi, p.noresi, p.status_pesanan, p.batal, k.nama_kurir
             FROM tblprintresi p
             LEFT JOIN tblkurir k ON k.id_kurir = p.id_kurir
             WHERE p.id_printresi = ?
             LIMIT 1",
            [(int) $pending->id_printresi]
        )->row();
        if (!$resi) {
            return $this->batal($prev_debug, 404, 'Resi tidak ditemukan di tblprintresi', 'TIDAK_DITEMUKAN');
        }
        if (strtoupper((string) $resi->status_pesanan) === 'CANCELED' || (string) $resi->batal === '1') {
            return $this->batal($prev_debug, 400, 'Pesanan sudah DIBATALKAN -- tidak dibuatkan picking', 'ORDER_CANCELED');
        }

        $now = date('Y-m-d H:i:s');

        // 3. Sudah di-picker di luar alur -> tutup saja.
        $picking_ada = $this->db->query(
            "SELECT a.id_resiambilbarang, a.yangambil_pegawai, pg.nama_pegawai
             FROM tblresiambilbarang a
             LEFT JOIN tblpegawai pg ON pg.kode_pegawai = a.yangambil_pegawai
             WHERE a.id_resi = ?
             LIMIT 1",
            [(int) $resi->id_printresi]
        )->row();
        if ($picking_ada) {
            $this->db->where('id_pending', $id_pending)->update(self::TABEL, [
                'status'             => 'SELESAI_LUAR',
                'kode_picker'        => (int) $picking_ada->yangambil_pegawai,
                'diproses_oleh'      => (int) $user['id_user'],
                'waktu_proses'       => $now,
                'id_resiambilbarang' => (int) $picking_ada->id_resiambilbarang,
            ]);
            if ($this->db->trans_status() === FALSE) {
                return $this->batal($prev_debug, 500, 'Gagal menutup laporan', 'SAVE_FAILED');
            }
            $this->db->trans_commit();
            $this->db->db_debug = $prev_debug;
            return [
                'status'      => 'SELESAI_LUAR',
                'noresi'      => $resi->noresi,
                'nama_picker' => $picking_ada->nama_pegawai ?: ('PEGAWAI #' . (int) $picking_ada->yangambil_pegawai),
            ];
        }

        // 4. Picker
        if ($kode_picker <= 0) {
            return $this->batal($prev_debug, 400, 'Picker belum dipilih', 'PICKER_KOSONG');
        }
        $picker = $this->db->get_where('tblpegawai', ['kode_pegawai' => $kode_picker, 'status_aktif' => 'AKTIF'])->row();
        if (!$picker) {
            return $this->batal($prev_debug, 400, 'Picker tidak ditemukan atau tidak aktif', 'PICKER_TIDAK_VALID');
        }

        // 5. Picking atas nama picker -- pola kolom sama dengan
        //    Resi_team_fcd::save_combined_scan(), minus KPI.
        $this->db->insert('tblresiambilbarang', [
            'id_resi'                 => (int) $resi->id_printresi,
            'tanggal_resiambilbarang' => $now,
            'admin_pegawai'           => (int) $user['id_user'],
            'yangambil_pegawai'       => $kode_picker,
            'nama_komputer'           => self::PENANDA_KOMPUTER,
            'pending'                 => '',
            'is_preorder'             => 0,
            'status_performa_id'      => $this->status_performa_picker($kode_picker),
        ]);
        $id_rab = (int) $this->db->insert_id();
        if ($id_rab <= 0) {
            return $this->batal($prev_debug, 500, 'Gagal membuat baris picking', 'SAVE_FAILED');
        }

        // 6. Catatan lost scan PICKER (kolom sama dengan Lost_scan_packer_fcd::save()).
        $lost = $this->db->select('id_lostscanpacker')
            ->get_where('tbllostscanpacker', ['noresi' => $resi->noresi, 'lost_type' => 'PICKER'])
            ->row();
        if ($lost) {
            $id_lost = (int) $lost->id_lostscanpacker;
        } else {
            $this->db->insert('tbllostscanpacker', [
                'noresi'      => $resi->noresi,
                'lost_type'   => 'PICKER',
                'nama_packer' => $picker->nama_pegawai,
                'status_resi' => $resi->status_pesanan,
                'kurir'       => $resi->nama_kurir ?: 'NOT FOUND',
                'created_at'  => $now,
                'created_by'  => (int) $user['id_user'],
            ]);
            $id_lost = (int) $this->db->insert_id();
        }

        // 7. Tutup pending
        $this->db->where('id_pending', $id_pending)->update(self::TABEL, [
            'status'             => 'SELESAI',
            'kode_picker'        => $kode_picker,
            'diproses_oleh'      => (int) $user['id_user'],
            'waktu_proses'       => $now,
            'id_resiambilbarang' => $id_rab,
            'id_lostscanpacker'  => $id_lost,
        ]);

        if ($this->db->trans_status() === FALSE) {
            return $this->batal($prev_debug, 500, 'Gagal menyimpan, silakan ulangi', 'SAVE_FAILED');
        }

        $this->db->trans_commit();
        $this->db->db_debug = $prev_debug;

        return [
            'status'      => 'SELESAI',
            'noresi'      => $resi->noresi,
            'nama_picker' => $picker->nama_pegawai,
        ];
    }

    /** Rollback + pulihkan db_debug + bentuk error seragam. */
    private function batal($prev_debug, $code, $message, $kode)
    {
        $this->db->trans_rollback();
        $this->db->db_debug = $prev_debug;
        return ['error' => TRUE, 'code' => $code, 'message' => $message, 'kode' => $kode];
    }

    /**
     * Status performa picker hari ini (lewat akun tbluser yang terhubung ke
     * pegawai), fallback NORMAL_PICKER, fallback NULL. Sama dengan urutan di
     * save_combined_scan().
     */
    private function status_performa_picker($kode_picker)
    {
        $akun = $this->db->select('id_user')->get_where('tbluser', ['id_pegawai' => $kode_picker, 'isactive' => 1])->row();
        if ($akun) {
            $st = $this->db->select('id_statusperforma')
                ->get_where('tblstatusperforma', ['id_user' => $akun->id_user, 'tanggal' => date('Y-m-d'), 'isactive' => 1])
                ->row();
            if ($st) {
                return (int) $st->id_statusperforma;
            }
        }

        $normal = $this->db->select('id_statusperforma')
            ->get_where('tblmasterstatusperforma', ['kode_status' => 'NORMAL_PICKER', 'isactive' => 1])
            ->row();

        return $normal ? (int) $normal->id_statusperforma : null;
    }
}
```

- [ ] **Step 2: Syntax check**

Run: `C:/xampp/php/php.exe -l application/models/Lost_scan_picker_fcd.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add application/models/Lost_scan_picker_fcd.php
git commit -m "feat(lost-scan-picker): model antrean lost scan picker + proses tambah picker"
```

---

### Task 2: Migrasi tabel + menu, naikkan `BOOTSTRAP_VERSI`

**Files:**
- Modify: `application/core/MY_Controller.php` — `BOOTSTRAP_VERSI` (baris 42), daftar pemanggilan di `jalankan_bootstrap_sekali()` setelah `run_masalah_picker_new_migration()`, method baru sebelum `}` penutup class.

- [ ] **Step 1: Versi** → `const BOOTSTRAP_VERSI = '2026-09-18.2';`

- [ ] **Step 2: Daftarkan** setelah `$this->run_masalah_picker_new_migration();`:
```php
        $this->run_lost_scan_picker_migration();
```

- [ ] **Step 3: Method migrasi**

```php

    /**
     * Tabel antrean + menu TIM PICKER -> "Laporan Lost Scan Picker".
     *
     * Resi yang ditolak karena belum di-picker dilaporkan packer/HO ke antrean
     * ini; tim picker menentukan picker-nya lewat tombol Tambahkan Picker
     * (Lost_scan_picker_fcd::tambah_picker) -- baru setelah itu packer bisa
     * scan ulang, lalu HO. SKU/qty/rak tidak disimpan, dibaca dari
     * tbldetailprintresi saat tampil.
     *
     * Hak akses: webmaster (1), admin (2), tim retur (6) -- pola menu TIM
     * PICKER yang menulis atas nama orang lain (SCAN COMBINED, Master Picker,
     * Resi Pending). Daftar tetap; harus sejalan dengan
     * Lost_scan_picker::ROLE_BOLEH.
     */
    protected function run_lost_scan_picker_migration()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `tbllostscanpicker_pending` (
          `id_pending` int(11) NOT NULL AUTO_INCREMENT,
          `id_printresi` bigint(20) NOT NULL,
          `noresi` varchar(100) NOT NULL,
          `sumber` enum('PACKER','HO') NOT NULL DEFAULT 'PACKER',
          `dilaporkan_oleh` int(11) NOT NULL,
          `waktu_lapor` datetime NOT NULL,
          `status` enum('PENDING','SELESAI','SELESAI_LUAR') NOT NULL DEFAULT 'PENDING',
          `kode_picker` int(11) DEFAULT NULL,
          `diproses_oleh` int(11) DEFAULT NULL,
          `waktu_proses` datetime DEFAULT NULL,
          `id_resiambilbarang` int(11) DEFAULT NULL,
          `id_lostscanpacker` int(11) DEFAULT NULL,
          PRIMARY KEY (`id_pending`),
          KEY `idx_noresi_status` (`noresi`, `status`),
          KEY `idx_status_waktu` (`status`, `waktu_lapor`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $uri = 'lost-scan-picker';

        // Urutan dikunci ke id terkecil -- lihat catatan di run_menu_scan_packer_webcam.
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri])->row();
        if (!$menu) {
            $this->db->insert('menu', [
                'name'      => 'Laporan Lost Scan Picker',
                'parentid'  => 19,
                'uri'       => $uri,
                'icon'      => 'fa fa-user-times',
                'sortorder' => 26, // tepat setelah SCAN COMBINED (25)
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        $role_boleh = [1, 2, 6];
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
}
```

- [ ] **Step 4: Syntax check** — `C:/xampp/php/php.exe -l application/core/MY_Controller.php` → `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add application/core/MY_Controller.php
git commit -m "feat(lost-scan-picker): migrasi tabel antrean + menu TIM PICKER, naikkan BOOTSTRAP_VERSI"
```

---

### Task 3: Controller `Lost_scan_picker` + route

**Files:**
- Create: `application/controllers/Lost_scan_picker.php`
- Modify: `application/config/routes.php` — setelah `$route['lost_scan_packer/export_excel'] = ...;` (baris 132)

**Interfaces:**
- Consumes: Task 1; `Picking_fcd::get_picker('AKTIF')`; `Notification::send($message, $category, $title)`.
- Produces (dipakai view Task 4):
  - `GET lost-scan-picker` → halaman; variabel view `list_picker`, `reportrange`, `jumlah_pending`.
  - `POST lost-scan-picker/get-data {tab, reportrange, draw, start, length, search, order}` → DataTables `{draw, recordsTotal, recordsFiltered, data, jumlah_pending}`.
  - `POST lost-scan-picker/tambah-picker {id_pending, kode_picker}` → `code 201` `data.{status, noresi, nama_picker}`; gagal `code 4xx/5xx` `data.kode`.

- [ ] **Step 1: Tulis controller**

```php
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
        $data['reportrange']    = $this->rentang_default();
        $data['list_picker']    = $this->picking_fcd->get_picker('AKTIF')->result_array();
        $data['jumlah_pending'] = $this->lost_scan_picker_fcd->jumlah_pending();

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

        $valid_columns = ($tab === 'pending')
            ? [1 => 't.waktu_lapor', 2 => 't.noresi', 3 => 't.sumber', 4 => 'u.name']
            : [1 => 't.waktu_lapor', 2 => 't.noresi', 3 => 't.sumber', 4 => 'u.name', 6 => 'pg.nama_pegawai', 7 => 't.waktu_proses', 8 => 't.status'];
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
            $baris = [
                $nomor++ . '.',
                !empty($r['waktu_lapor']) ? date('d/m/Y H:i', strtotime($r['waktu_lapor'])) : '-',
                '<strong style="letter-spacing:1px;">' . $this->e($r['noresi']) . '</strong>',
                $this->badge_sumber($r['sumber']),
                $this->e($r['nama_pelapor'] ?: '-'),
                $this->sel_item($item[(int) $r['id_printresi']] ?? []),
            ];

            if ($tab === 'pending') {
                if (!empty($r['sudah_picked'])) {
                    $baris[] = '<span class="label label-default" title="Baris picking sudah dibuat di luar alur (mis. SCAN COMBINED)">sudah di-picker di luar alur</span><br>'
                        . '<button type="button" class="btn btn-xs btn-default btn-tandai-selesai" style="margin-top:4px;" data-id="' . $id . '" data-noresi="' . $this->e($r['noresi']) . '">'
                        . '<i class="fa fa-check"></i> Tandai Selesai</button>';
                } else {
                    $baris[] = '<button type="button" class="btn btn-xs btn-warning btn-tambah-picker" data-id="' . $id . '" data-noresi="' . $this->e($r['noresi']) . '">'
                        . '<i class="fa fa-user-plus"></i> Tambahkan Picker</button>';
                }
            } else {
                $baris[] = $this->e($r['nama_picker'] ?: ('PEGAWAI #' . (int) $r['kode_picker']));
                $baris[] = $this->e($r['nama_pemroses'] ?: '-') . '<br><small class="text-muted">'
                    . (!empty($r['waktu_proses']) ? date('d/m/Y H:i', strtotime($r['waktu_proses'])) : '-') . '</small>';
                $baris[] = ($r['status'] === 'SELESAI_LUAR')
                    ? '<span class="label label-default">SELESAI (di luar alur)</span>'
                    : '<span class="label label-success">SELESAI</span>';
            }

            $data_table[] = $baris;
        }

        $this->kirim_json([
            'draw'            => $draw,
            'recordsTotal'    => $hasil['total'],
            'recordsFiltered' => $hasil['total'],
            'data'            => $data_table,
            'jumlah_pending'  => $this->lost_scan_picker_fcd->jumlah_pending(),
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

    /** Daftar item resi; dibungkus .lsp-items supaya modal bisa menyalinnya. */
    private function sel_item(array $items)
    {
        if (empty($items)) {
            return '<div class="lsp-items"><em class="text-muted">tidak ada detail item</em></div>';
        }
        $html = '<div class="lsp-items">';
        foreach ($items as $it) {
            $html .= '<div style="white-space:nowrap;"><strong>' . $this->e($it['sku']) . '</strong> &times; ' . (int) $it['jumlah']
                . ' <span class="text-muted">@ ' . $this->e($it['no_rak'] ?: '-') . '</span>'
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
```

- [ ] **Step 2: Route** — setelah `$route['lost_scan_packer/export_excel'] = 'lost_scan_packer/export_excel';`:

```php

// TIM PICKER -> Laporan Lost Scan Picker (antrean resi belum di-picker)
$route['lost-scan-picker'] = 'lost_scan_picker/index';
$route['lost-scan-picker/get-data'] = 'lost_scan_picker/get_data';
$route['lost-scan-picker/tambah-picker'] = 'lost_scan_picker/tambah_picker';
```

- [ ] **Step 3: Syntax check** — kedua file → `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add application/controllers/Lost_scan_picker.php application/config/routes.php
git commit -m "feat(lost-scan-picker): controller laporan + endpoint data dan tambah picker, route"
```

---

### Task 4: View `lost_scan_picker/index.php`

**Files:**
- Create: `application/views/lost_scan_picker/index.php`

**Interfaces:**
- Consumes: `$akses_ditolak`, `$reportrange`, `$list_picker` (`kode_pegawai`, `nama_pegawai`), `$jumlah_pending`; endpoint Task 3.

- [ ] **Step 1: Tulis view**

```php
<?php
// View menu TIM PICKER -> Laporan Lost Scan Picker. Dimuat lewat AJAX (SPA)
// oleh plugins.js, jadi semua handler diikat ke #lsp-root supaya ikut hilang
// saat pengguna pindah menu -- bukan ke document, yang membuat handler menumpuk.
if (!empty($akses_ditolak)) : ?>
<div class="row"><div class="col-md-12">
  <div class="panel panel-default">
    <div class="panel-heading"><h3 class="panel-title"><strong>Laporan Lost Scan Picker</strong></h3></div>
    <div class="panel-body">
      <div class="alert alert-danger" style="margin-bottom:0;">
        <i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu ini. Hak aksesnya sama dengan menu <em>SCAN COMBINED</em>; minta admin membukanya lewat menu Access.
      </div>
    </div>
  </div>
</div></div>
<?php return; endif;

$rentang_default = date('Y-m-d 00:00:00') . ' - ' . date('Y-m-d 23:59:59');
$reportrange = !empty($reportrange) ? $reportrange : $rentang_default;
$list_picker = (isset($list_picker) && is_array($list_picker)) ? $list_picker : [];
$jumlah_pending = isset($jumlah_pending) ? (int) $jumlah_pending : 0;
?>
<div id="lsp-root">
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><strong>Laporan Lost Scan Picker</strong>
            <small class="text-muted" style="margin-left:8px;">resi belum di-picker yang dilaporkan packer/HO -- tentukan picker-nya di sini supaya packer bisa scan ulang</small>
          </h3>
        </div>

        <div class="panel-body">
          <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="active"><a href="#lsp-tab-pending" data-toggle="tab"><i class="fa fa-hourglass-half"></i> Pending <span class="badge" id="lsp-badge-pending" style="background:#f0ad4e;"><?= $jumlah_pending ?></span></a></li>
            <li><a href="#lsp-tab-selesai" data-toggle="tab"><i class="fa fa-check-circle"></i> Selesai</a></li>
          </ul>

          <div class="tab-content">
            <!-- ================= PENDING ================= -->
            <div class="tab-pane fade in active" id="lsp-tab-pending">
              <p class="text-muted" style="margin-bottom:10px;">
                <i class="fa fa-info-circle"></i> Semua laporan yang belum diproses ditampilkan tanpa filter tanggal. Setelah picker ditambahkan, packer bisa scan ulang resi tersebut, lalu HO.
              </p>
              <table id="lsp-table-pending" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:120px;">Waktu Lapor</th>
                    <th>No Resi</th>
                    <th style="width:80px;">Sumber</th>
                    <th>Pelapor</th>
                    <th>Item (SKU &times; qty @ rak)</th>
                    <th style="width:160px;">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <!-- ================= SELESAI ================= -->
            <div class="tab-pane fade" id="lsp-tab-selesai">
              <form class="form-horizontal nojs" id="lsp-form-filter">
                <div class="form-group">
                  <label class="col-md-2 col-xs-12 control-label">Rentang waktu proses</label>
                  <div class="col-md-4 col-xs-12">
                    <input type="text" id="lsp-reportrange" class="form-control" value="<?= htmlspecialchars($reportrange, ENT_QUOTES, 'UTF-8') ?>" />
                  </div>
                  <div class="col-md-2 col-xs-12">
                    <button type="button" class="btn btn-info" id="lsp-btn-cari"><i class="fa fa-search"></i> Cari</button>
                  </div>
                </div>
              </form>
              <table id="lsp-table-selesai" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                  <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:120px;">Waktu Lapor</th>
                    <th>No Resi</th>
                    <th style="width:80px;">Sumber</th>
                    <th>Pelapor</th>
                    <th>Item (SKU &times; qty @ rak)</th>
                    <th>Picker</th>
                    <th>Diproses oleh</th>
                    <th style="width:140px;">Status</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL TAMBAHKAN PICKER ================= -->
  <div id="lsp-modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background:#f5f5f5;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-user-plus"></i> Tambahkan Picker</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="lsp-modal-id" value="">
          <p style="margin-bottom:6px;">No Resi</p>
          <h3 id="lsp-modal-noresi" style="margin:0 0 12px; font-weight:800; letter-spacing:2px;">-</h3>
          <p style="margin-bottom:6px;">Item</p>
          <div id="lsp-modal-item" style="background:#f9f9f9; border:1px solid #eee; border-radius:6px; padding:8px 10px; margin-bottom:15px; max-height:180px; overflow:auto;"></div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="lsp-modal-picker">Picker yang mengambil resi ini</label>
            <select id="lsp-modal-picker" class="form-control" data-live-search="true" data-size="8" title="Pilih Picker">
              <option value="">Pilih Picker</option>
              <?php foreach ($list_picker as $p) : ?>
                <option value="<?= (int) $p['kode_pegawai'] ?>"><?= htmlspecialchars($p['nama_pegawai'] . ' - ' . $p['kode_pegawai'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($list_picker)) : ?>
              <p class="text-danger" style="margin:8px 0 0;"><i class="fa fa-exclamation-triangle"></i> Master Picker kosong. Tambahkan dulu di menu Master Picker.</p>
            <?php endif; ?>
          </div>
          <p class="text-muted" style="margin:12px 0 0; font-size:12px;">
            <i class="fa fa-info-circle"></i> Baris picking dibuat atas nama picker ini (tanpa KPI) dan lost scan PICKER dicatat. Setelah itu packer bisa scan ulang.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-warning" id="lsp-modal-simpan"><i class="fa fa-save"></i> Simpan &amp; Tambahkan Picker</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var $root = $('#lsp-root');
  var URL = {
    data:   'lost-scan-picker/get-data',
    tambah: 'lost-scan-picker/tambah-picker'
  };

  function notif(teks, tipe) {
    if (typeof noty !== 'undefined') {
      noty({ text: teks, layout: 'topRight', type: tipe || 'success', timeout: 5000 });
    } else {
      alert(teks);
    }
  }
  function rentang() {
    return $('#lsp-reportrange').val() || <?= json_encode($rentang_default) ?>;
  }
  function perbaruiBadge(n) {
    $('#lsp-badge-pending').text(n);
  }

  // ---------- filter tanggal (tab selesai) ----------
  var awal = moment(rentang().split(' - ')[0]);
  var akhir = moment(rentang().split(' - ')[1]);
  $('#lsp-reportrange').daterangepicker({
    timePicker: true, timePicker24Hour: true, startDate: awal, endDate: akhir,
    ranges: {
      'Hari ini': [moment().startOf('day'), moment().endOf('day')],
      'Kemarin': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
      '7 hari terakhir': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
      'Bulan ini': [moment().startOf('month'), moment().endOf('month')]
    },
    locale: { format: 'YYYY-MM-DD HH:mm:ss' }
  });
  $('#lsp-reportrange').on('apply.daterangepicker', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('click', '#lsp-btn-cari', function () { tableSelesai.ajax.reload(null, true); });
  $root.on('submit', '#lsp-form-filter', function (e) { e.preventDefault(); tableSelesai.ajax.reload(null, true); });

  // ---------- tabel pending ----------
  var tablePending = $('#lsp-table-pending').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: { processing: 'Memproses data...', emptyTable: 'Tidak ada laporan lost scan picker yang menunggu.' },
    order: [[1, 'asc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'pending'; },
      dataSrc: function (json) { perbaruiBadge(json.jumlah_pending || 0); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 3, 6] },
      { orderable: false, targets: [0, 5, 6] }
    ]
  });

  // ---------- tabel selesai ----------
  var tableSelesai = $('#lsp-table-selesai').DataTable({
    scrollX: true, pageLength: 25, processing: true, serverSide: true,
    language: { processing: 'Memproses data...', emptyTable: 'Tidak ada laporan yang diproses pada rentang ini.' },
    order: [[7, 'desc']],
    lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
    ajax: {
      url: URL.data, type: 'POST',
      data: function (d) { d.tab = 'selesai'; d.reportrange = rentang(); },
      dataSrc: function (json) { perbaruiBadge(json.jumlah_pending || 0); return json.data; }
    },
    columnDefs: [
      { className: 'text-center', targets: [0, 3, 8] },
      { orderable: false, targets: [0, 5] }
    ]
  });

  // DataTables di dalam tab tersembunyi salah menghitung lebar kolom;
  // hitung ulang saat tab ditampilkan.
  $root.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
    tablePending.columns.adjust();
    tableSelesai.columns.adjust();
  });

  // ---------- modal tambahkan picker ----------
  var adaSelectpicker = (typeof $.fn.selectpicker === 'function');
  if (adaSelectpicker) {
    $('#lsp-modal-picker').selectpicker({ liveSearch: true, size: 8 });
  }

  $root.on('click', '.btn-tambah-picker', function () {
    var $btn = $(this);
    $('#lsp-modal-id').val($btn.data('id'));
    $('#lsp-modal-noresi').text($btn.data('noresi'));
    $('#lsp-modal-item').html($btn.closest('tr').find('.lsp-items').html() || '<em class="text-muted">tidak ada detail item</em>');
    $('#lsp-modal-picker').val('');
    if (adaSelectpicker) $('#lsp-modal-picker').selectpicker('refresh');
    $('#lsp-modal-simpan').prop('disabled', false);
    $('#lsp-modal').modal('show');
  });

  $('#lsp-modal').on('shown.bs.modal', function () {
    if (adaSelectpicker) {
      $('#lsp-modal-picker').selectpicker('toggle');
    } else {
      $('#lsp-modal-picker').focus();
    }
  });

  // Setelah picker dipilih, Enter berikutnya = simpan.
  $root.on('changed.bs.select change', '#lsp-modal-picker', function () {
    if ($(this).val()) $('#lsp-modal-simpan').focus();
  });

  function kirimTambahPicker(idPending, kodePicker, $tombol, sesudah) {
    $tombol.prop('disabled', true);
    $.ajax({
      url: URL.tambah, type: 'POST', dataType: 'json',
      data: { id_pending: idPending, kode_picker: kodePicker },
      success: function (r) {
        if (r.code === 201) {
          notif(r.message, 'success');
          tablePending.ajax.reload(null, false);
          tableSelesai.ajax.reload(null, false);
          if (sesudah) sesudah();
        } else {
          notif(r.message || 'Gagal memproses', 'error');
          // Sudah diproses orang lain -> segarkan supaya barisnya hilang.
          if (r.data && r.data.kode === 'SUDAH_DIPROSES') tablePending.ajax.reload(null, false);
          $tombol.prop('disabled', false);
        }
      },
      error: function () {
        notif('Kesalahan sistem saat memproses laporan', 'error');
        $tombol.prop('disabled', false);
      }
    });
  }

  $root.on('click', '#lsp-modal-simpan', function () {
    var id = $('#lsp-modal-id').val();
    var picker = $('#lsp-modal-picker').val();
    if (!picker) {
      notif('Pilih picker dulu', 'warning');
      if (adaSelectpicker) $('#lsp-modal-picker').selectpicker('toggle');
      return;
    }
    kirimTambahPicker(id, picker, $(this), function () { $('#lsp-modal').modal('hide'); });
  });

  // Picking sudah dibuat di luar alur: tutup laporan tanpa memilih picker.
  $root.on('click', '.btn-tandai-selesai', function () {
    var $btn = $(this);
    if (!confirm('Tutup laporan resi ' + $btn.data('noresi') + ' sebagai selesai di luar alur?')) return;
    kirimTambahPicker($btn.data('id'), '', $btn, null);
  });
})();
</script>
```

- [ ] **Step 2: Syntax check** — `C:/xampp/php/php.exe -l application/views/lost_scan_picker/index.php`

- [ ] **Step 3: Commit**

```bash
git add application/views/lost_scan_picker/index.php
git commit -m "feat(lost-scan-picker): halaman laporan dua tab + modal Tambahkan Picker"
```

---

### Task 5: Data dummy & uji manual

**Files:**
- Create: `dev_tools/dummy_lost_scan_picker.sql` (gitignored)

- [ ] **Step 1: Script dummy**

```sql
-- Data dummy uji Laporan Lost Scan Picker. Hanya INSERT; resi berawalan DUMMYLSP_.
--   DUMMYLSP_1..3 : belum picker, ada detail item (2 SKU), masuk antrean PENDING
--   DUMMYLSP_1 sumber PACKER, DUMMYLSP_2 sumber HO, DUMMYLSP_3 sumber PACKER
INSERT INTO tblprintresi (tanggal_printresi, id_marketplace, noresi, nomorpicklist, batal, keterangan, id_kurir, admin_pegawai, status_pesanan, tipe_resi, created_at, created_by)
SELECT NOW(), 3, r.noresi, 'PL-DUMMYLSP', '0', 'dummy testing lost scan picker', 6, 1, 'PROCESSING', 'campuran', NOW(), 'dummy'
FROM (SELECT 'DUMMYLSP_1' AS noresi UNION ALL SELECT 'DUMMYLSP_2' UNION ALL SELECT 'DUMMYLSP_3') r
WHERE NOT EXISTS (SELECT 1 FROM tblprintresi p WHERE p.noresi = r.noresi);

INSERT INTO tbldetailprintresi (id_resi, no_pesanan, sku, no_rak, jumlah)
SELECT p.id_printresi, CONCAT('PSN-', p.noresi), d.sku, d.no_rak, d.jumlah
FROM tblprintresi p
JOIN (SELECT 'SKU-DUMMY-A' AS sku, 'R1-01' AS no_rak, 2 AS jumlah UNION ALL SELECT 'SKU-DUMMY-B', 'R2-05', 1) d
WHERE p.noresi IN ('DUMMYLSP_1','DUMMYLSP_2','DUMMYLSP_3')
  AND NOT EXISTS (SELECT 1 FROM tbldetailprintresi x WHERE x.id_resi = p.id_printresi AND x.sku = d.sku);

INSERT INTO tbllostscanpicker_pending (id_printresi, noresi, sumber, dilaporkan_oleh, waktu_lapor, status)
SELECT p.id_printresi, p.noresi, IF(p.noresi = 'DUMMYLSP_2', 'HO', 'PACKER'), 1, NOW(), 'PENDING'
FROM tblprintresi p
WHERE p.noresi IN ('DUMMYLSP_1','DUMMYLSP_2','DUMMYLSP_3')
  AND NOT EXISTS (SELECT 1 FROM tbllostscanpicker_pending q WHERE q.noresi = p.noresi AND q.status = 'PENDING');

SELECT q.id_pending, q.noresi, q.sumber, q.status,
       EXISTS(SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = q.id_printresi) AS picked
FROM tbllostscanpicker_pending q WHERE q.noresi LIKE 'DUMMYLSP_%' ORDER BY q.noresi;
```

- [ ] **Step 2: Picu migrasi lalu jalankan script** — buka `http://localhost/new-iresis/` (login) supaya tabel dibuat, lalu:

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root iresis-prod < dev_tools/dummy_lost_scan_picker.sql
```
Expected: 3 baris PENDING, `picked = 0`.

- [ ] **Step 3: Uji manual** (login role 1/2/6)

1. Menu TIM PICKER → Laporan Lost Scan Picker tampil setelah SCAN COMBINED; badge Pending = 3.
2. Tab Pending: 3 baris, item `SKU-DUMMY-A × 2 @ R1-01`, `SKU-DUMMY-B × 1 @ R2-05`, sumber PACKER/HO.
3. Tambahkan Picker `DUMMYLSP_1` → modal, dropdown terbuka, pilih picker, Simpan → noty sukses, baris hilang dari Pending, badge 2, muncul di tab Selesai dengan picker & pemroses.
4. DB: `tblresiambilbarang` untuk `DUMMYLSP_1` ada dengan `nama_komputer = 'LOST SCAN PICKER'`; `tbllostscanpacker` baris `PICKER` ada; pending `SELESAI`.
5. Notifikasi lonceng (akun admin) menampilkan "Resi DUMMYLSP_1 sudah ditambahkan picker…".
6. Buat picking `DUMMYLSP_3` lewat SCAN COMBINED (atau INSERT manual ke `tblresiambilbarang`) → tab Pending menandai "sudah di-picker di luar alur", Tandai Selesai → status `SELESAI (di luar alur)`, tidak ada baris `tbllostscanpacker` baru.
7. Role selain 1/2/6: menu tidak tampil; URL langsung → panel "akses ditolak".
8. TIM HO → Laporan Lost Scan, tipe PICKER hari ini → baris `DUMMYLSP_1` tampil.

- [ ] **Step 4: Siapkan merge** — `git status --short` tidak menampilkan `dev_tools/`; `git log --oneline master..HEAD` = spec + plan + 4 commit tugas.
