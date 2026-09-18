# Desain Tahap A: Laporan Lost Scan Picker + Tambahkan Picker

Tanggal: 2026-09-18
Status: disetujui (brainstorming bersama user)
Branch: `feature/lost-scan-picker` (dari `master`)

## Latar belakang

Resi yang **belum di-picker** ditolak di dua tempat: meja packer
(`Packer_fcd`, `NOT_PICKED`, pesan "Jangan dipacking dulu, laporkan ke CS")
dan meja HO (`Scan_logistic_fcd`, `NOT_PICKED`). Penyelesaiannya hari ini
manual: packer lapor lisan ke CS, CS memakai SCAN COMBINED (menu TIM PICKER,
`receipt/scan-combined`) yang membuat baris picking **dan** packing sekaligus
atas nama orang lain -- jadi packing-nya pun ikut "disulap", tanpa video dan
tanpa scan packer yang sebenarnya.

## Alur keseluruhan (3 tahap)

| Tahap | Isi | Branch |
|---|---|---|
| **A (dokumen ini)** | Tabel antrean + menu **TIM PICKER → Laporan Lost Scan Picker** + tombol **Tambahkan Picker** + model `lapor()` yang dipakai tahap B/C | `feature/lost-scan-picker` |
| B | Tombol **Lapor Lost Scan Picker** di Scan Resi Packer (Webcam) saat `NOT_PICKED` | branch sendiri |
| C | `NOT_PICKED` di Scan Paket NDD New → panel packer + `lapor()` otomatis | digabung ke `feature/scan-paket-ndd-new` |

Urutan yang **dipaksa oleh penjaga yang sudah ada**, tidak oleh status baru:

```
resi belum picker
   ├─ Packer scan → ditolak NOT_PICKED (Packer_fcd)
   ├─ HO scan     → ditolak NOT_PICKED (Scan_logistic_fcd)
   ▼
Tim picker: Tambahkan Picker → baris tblresiambilbarang dibuat
   ▼
Packer scan ulang → lolos → tblpacking (video + KPI packer asli)
   ▼
HO scan ulang → lolos → tblresikeluar / tblscan_ndd
```

Satu-satunya cara membuat baris picking di alur ini adalah tombol Tambahkan
Picker. Packer dan HO tidak punya tombol yang menulis picking.

## Keputusan desain (disetujui)

- Packer/HO **tidak memilih picker**; hanya melaporkan resi. Tim picker yang
  menentukan siapa picker-nya.
- Catatan `tbllostscanpacker` tipe **PICKER** dibuat **saat tim picker
  memproses** (nama picker baru diketahui saat itu), bukan saat lapor. Tidak
  pernah ada baris lost scan tanpa nama.
- **KPI picker tidak dicatat** untuk picking susulan. Baris picking dibuat
  demi integritas data, bukan prestasi; hukumannya sudah tercatat di
  `tbllostscanpacker`. `nama_komputer = 'LOST SCAN PICKER'` sebagai penanda
  agar laporan lain bisa memisahkannya.
- Role menu: **1 (webmaster), 2 (admin), 6 (tim retur)** -- pola SCAN
  COMBINED / Master Picker / Resi Pending, yaitu menu yang menulis atas nama
  orang lain. Daftar tetap di migrasi dan di `ROLE_BOLEH` controller.
- Tidak menyentuh `Lost_scan_packer_fcd::save()` (menolak duplikat per
  `noresi` tanpa melihat `lost_type`; alur ini butuh unik per
  `(noresi, lost_type)`), tidak menyentuh `Resi_team_fcd::save_combined_scan()`.
  Logika picker-nya ditulis ulang secara terbatas di model baru.

## Tabel baru: `tbllostscanpicker_pending`

Antrean tindak lanjut. Satu baris per laporan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id_pending` | int PK AI | |
| `id_printresi` | bigint | dari `tblprintresi` |
| `noresi` | varchar(100) | |
| `sumber` | enum('PACKER','HO') | meja yang melapor |
| `dilaporkan_oleh` | int | `tbluser.id_user` |
| `waktu_lapor` | datetime | |
| `status` | enum('PENDING','SELESAI','SELESAI_LUAR') | `SELESAI_LUAR` = picking ternyata sudah dibuat di luar alur (mis. SCAN COMBINED) |
| `kode_picker` | int NULL | `tblpegawai.kode_pegawai`, diisi saat proses |
| `diproses_oleh` | int NULL | `tbluser.id_user` |
| `waktu_proses` | datetime NULL | |
| `id_resiambilbarang` | int NULL | baris picking yang dibuat |
| `id_lostscanpacker` | int NULL | baris `tbllostscanpacker` PICKER yang dibuat |

Index: `idx_noresi_status (noresi, status)`, `idx_status_waktu (status, waktu_lapor)`.
Dibuat lewat `CREATE TABLE IF NOT EXISTS` di migrasi bootstrap. SKU/qty/no rak
**tidak** disimpan -- dibaca dari `tbldetailprintresi` saat tampil.

## Komponen

| File | Peran |
|---|---|
| `application/models/Lost_scan_picker_fcd.php` | `lapor()`, `cari_pending()`, `get_data()`/`get_total_data()` (DataTables), `detail_item()`, `tambah_picker()` |
| `application/controllers/Lost_scan_picker.php` | `index()`, `get_data()`, `tambah_picker()`; `ROLE_BOLEH = [1, 2, 6]` (URL bisa dibuka langsung, jadi dijaga di controller juga) |
| `application/views/lost_scan_picker/index.php` | Halaman laporan: filter tanggal, tab Pending / Selesai, DataTables server-side, modal Tambahkan Picker |
| `application/config/routes.php` | `lost-scan-picker`, `lost-scan-picker/get-data`, `lost-scan-picker/tambah-picker` |
| `application/core/MY_Controller.php` | `run_lost_scan_picker_migration()`: tabel + menu "Laporan Lost Scan Picker" di TIM PICKER (`parentid` 19, setelah SCAN COMBINED, icon `fa fa-user-times`) + `roleaccess` 1, 2, 6; `BOOTSTRAP_VERSI` naik |

Dropdown picker di modal memakai `Picking_fcd::get_picker('AKTIF')` (Master
Picker, `tblnamaambilbarang` join `tblpegawai`) -- sumber yang sama dengan
Scan Resi Picker dan SCAN COMBINED. Nilai yang dikirim: `kode_pegawai`.

## Antarmuka model (dipakai tahap B dan C)

```php
// Buat laporan. Tidak pernah membuat dua PENDING untuk resi yang sama.
// Kembalikan ['status' => ..., 'pending' => row|null, 'message' => string]
//   'DIBUAT'          baris PENDING baru dibuat
//   'SUDAH_PENDING'   sudah ada PENDING (pending = baris itu, ada nama pelapor & waktu)
//   'SUDAH_PICKED'    resi sudah punya baris tblresiambilbarang -> tidak perlu lapor
//   'TIDAK_DITEMUKAN' noresi tidak ada di tblprintresi
Lost_scan_picker_fcd::lapor(string $noresi, string $sumber /* 'PACKER'|'HO' */, array $user): array

// Baris PENDING untuk resi itu + nama pelapor, atau null.
Lost_scan_picker_fcd::cari_pending(string $noresi): ?array
```

## Proses "Tambahkan Picker" — `tambah_picker($id_pending, $kode_picker, $user)`

Satu transaksi, `db_debug` dimatikan sementara lalu dipulihkan:

1. `SELECT ... FOR UPDATE` baris pending; harus `status = 'PENDING'`, kalau
   tidak → error `SUDAH_DIPROSES`.
2. Cek `tblprintresi`: ada, `status_pesanan <> 'CANCELED'`, `batal <> '1'`;
   kalau batal → error `ORDER_CANCELED` (baris pending tetap PENDING supaya
   terlihat, tim picker memutuskan manual).
3. Cek `kode_picker` ada di `tblpegawai` dengan `status_aktif = 'AKTIF'`.
4. Kalau `tblresiambilbarang` untuk `id_printresi` **sudah ada** → update
   pending jadi `SELESAI_LUAR` (`diproses_oleh`, `waktu_proses`,
   `id_resiambilbarang` diisi, `kode_picker` dari baris picking yang ada),
   commit, kembalikan `status = 'SELESAI_LUAR'` dengan nama picker yang ada.
5. Insert `tblresiambilbarang`: `id_resi`, `tanggal_resiambilbarang = NOW`,
   `admin_pegawai = $user['id_user']`, `yangambil_pegawai = $kode_picker`,
   `nama_komputer = 'LOST SCAN PICKER'`, `pending = ''`, `is_preorder = 0`,
   `status_performa_id` = status performa picker hari ini (`tblstatusperforma`
   via `tbluser.id_pegawai`), fallback `NORMAL_PICKER`
   (`tblmasterstatusperforma`), fallback NULL. Pola sama dengan
   `save_combined_scan()`.
6. Insert `tbllostscanpacker`: `noresi`, `lost_type = 'PICKER'`,
   `nama_packer = nama_pegawai picker`, `status_resi` & `kurir` dari
   `tblprintresi` join `tblkurir` (sama seperti `Lost_scan_packer_fcd::save()`),
   `created_by = $user['id_user']`. Duplikat dicek per
   `(noresi, lost_type = 'PICKER')`; kalau sudah ada, pakai id yang ada.
7. Update pending → `SELESAI`, `kode_picker`, `diproses_oleh`, `waktu_proses`,
   `id_resiambilbarang`, `id_lostscanpacker`.
8. **Tidak** memanggil `log_kpi_combined` / KPI apa pun.
9. Commit. Setelah commit (di luar transaksi): `Notification::send()` kategori
   `LOST_SCAN_PICKER`, pesan "Resi {noresi} sudah ditambahkan picker
   ({nama}). Silakan scan ulang di packer." Kegagalan notifikasi tidak
   membatalkan proses.

## Halaman laporan

- Filter rentang tanggal (`daterangepicker`, default hari ini) berlaku untuk
  tab **Selesai** (berdasarkan `waktu_proses`). Tab **Pending** selalu
  menampilkan **semua** PENDING tanpa filter tanggal -- antrean tidak boleh
  tersembunyi karena tanggal.
- Kolom: No · Waktu lapor · No resi · Sumber (badge PACKER/HO) · Pelapor ·
  Item (`SKU × qty @ rak`, satu baris per SKU, dari `tbldetailprintresi` join
  `tblsku` untuk nama) · Picker (tab Selesai) · Diproses oleh / waktu (tab
  Selesai) · Aksi (tab Pending: tombol **Tambahkan Picker**).
- Pola DataTables server-side seperti `Qc_return::get_data()`: array string
  HTML yang sudah dirender di PHP, tombol dengan `onclick`. Item per resi
  diambil satu query untuk seluruh halaman (`WHERE id_resi IN (...)`), bukan
  per baris.
- Modal Tambahkan Picker: no resi, daftar item, `select` picker
  (bootstrap-select live-search), tombol Simpan. Sukses → noty hijau, tabel
  reload, badge jumlah pending di judul tab berkurang.
- Baris PENDING yang resinya sudah punya picking (dibuat di luar alur)
  ditandai visual "sudah di-picker di luar alur" dan tombolnya berubah jadi
  **Tandai Selesai** (memanggil endpoint yang sama; model mendeteksi dan
  menutup sebagai `SELESAI_LUAR`).

## Penanganan error

- Semua AJAX lewat `make_ajax_response()`; DataTables lewat `echo json_encode`
  dengan `ob_clean()` seperti pola yang ada.
- Role di luar `ROLE_BOLEH` → `index()` mengembalikan view dengan
  `akses_ditolak = TRUE` (pola `Masalah_picker_new`), endpoint AJAX → 403 di
  body JSON.
- Dua orang memproses baris yang sama bersamaan → `FOR UPDATE` + cek status
  membuat yang kedua dapat `SUDAH_DIPROSES` dengan nama pemroses pertama.

## Pengujian (manual)

1. `php -l` semua file PHP yang dibuat/diubah.
2. Migrasi: tabel `tbllostscanpicker_pending` ada, menu tampil di TIM PICKER
   untuk role 1/2/6, tidak untuk role lain; URL langsung oleh role lain →
   halaman "akses ditolak".
3. Data dummy (`dev_tools/dummy_lost_scan_picker.sql`, tidak di-commit):
   resi `DUMMYLSP_1..3` belum picker; masukkan PENDING lewat
   `Lost_scan_picker_fcd::lapor()` (script PHP kecil di `dev_tools/` atau
   INSERT langsung).
4. Tab Pending menampilkan 3 baris dengan item SKU/qty/rak.
5. Tambahkan Picker untuk `DUMMYLSP_1` → `tblresiambilbarang` ada
   (`nama_komputer = 'LOST SCAN PICKER'`), `tbllostscanpacker` PICKER ada,
   pending SELESAI, notifikasi muncul, baris pindah ke tab Selesai.
6. Menu Scan Resi Picker/packer: scan `DUMMYLSP_1` di packer → lolos
   (picking ada). Sebelum diproses, `DUMMYLSP_2` di packer → tetap ditolak.
7. `lapor('DUMMYLSP_2', 'HO', user)` dua kali → kedua kali `SUDAH_PENDING`;
   `lapor('DUMMYLSP_1', ...)` setelah diproses → `SUDAH_PICKED`.
8. Buat picking `DUMMYLSP_3` lewat SCAN COMBINED → tab Pending menandai
   "di luar alur", Tandai Selesai → `SELESAI_LUAR`.
9. Laporan Lost Scan lama (TIM HO) menampilkan baris PICKER hasil proses.
