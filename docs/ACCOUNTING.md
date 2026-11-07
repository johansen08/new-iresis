# Menu Tim Accounting yang Dipakai — Per 7 November 2026

Dokumen ini mencatat kondisi **per 7 Nov 2026**: dari semua menu di grup
**TIM ACCOUNTING** (`menu.id = 92`), Tim Accounting hanya memakai **tiga menu**,
dan ketiganya hidup di controller `Retur` (bukan `Accounting`):

| # | Menu | URI | `menu.id` | Urutan | Halaman (view) |
|---|------|-----|-----------|--------|----------------|
| 1 | **Upload Retur Jubelio** | `retur/upload-retur-jubelio` | 158 | 65 | `views/retur/upload_retur_jubelio.php` |
| 2 | **Verifikasi Retur** | `retur/validasi-jubelio` | 137 | 70 | `views/retur/validasi_jubelio.php` |
| 3 | **Verifikasi Retur Komplain** | `retur/verifikasi-retur-komplain` | 157 | 80 | `views/retur/validasi_jubelio.php` (sama, `is_komplain = true`) |

Hak akses ketiganya sama (`roleaccess`): `1` webmaster, `2` admin, `6` tim retur,
`8` tim accounting, `12` tim restok.

> **Sejak 7 Nov 2026 menu lain di grup ini dinonaktifkan** (`menu.isactive = 0` lewat
> `MY_Controller::run_nonaktifkan_menu_accounting_lain()`, `BOOTSTRAP_VERSI`
> `2026-11-07.5`; `roleaccess` tidak diubah, backup di tabel `menu_bak_20261107`).
> Mengaktifkan lagi: set `isactive = 1` pada barisnya **dan** hapus/ubah pemanggilan
> migrasi itu, kalau tidak ia menonaktifkannya lagi saat versi bootstrap naik.
>
> **Menu lain di grup TIM ACCOUNTING tidak dipakai** (masih ada di DB dan kodenya
> masih ada, jangan dianggap rusak/hilang): Surat Jalan (93), Surat Jalan Database
> (94/163), Nomor Rak (160), SJ Kurangan Picker (95), Laporan Kurangan Picker (96),
> Pergantian Barang (97), Laporan Retur Shipped (161), Rekap Proses Retur (166),
> Pengajuan Klaim Retur (167). Controller `Accounting.php` — yang memuat
> Surat Jalan, Kurangan Picker, Pergantian Barang, dan Nomor Rak — praktis tidak
> dipakai Tim Accounting saat ini.

---

## 1. Gambaran alur

```
Jubelio (laporan "Daftar Retur Penjualan")
   │   unduh otomatis harian (scripts/auto_upload_retur_jubelio.py)
   │   atau unggah manual lewat menu ①
   ▼
tblreturjubelio  ◄── data "kata Jubelio"
                           │
                           │  dicocokkan per no_resi
                           ▼
        ┌──────────────── rekonsiliasi ────────────────┐
        │ tblresiretur  (hasil scan Terima Retur, tim HO/Retur)   │
        │ tblbukaretur  (hasil scan Buka Retur)                   │
        └─────────────────────────────────────────────┘
                           │
                           ▼
        ② Verifikasi Retur (is_komplain = 0)
        ③ Verifikasi Retur Komplain (is_komplain = 1)
                           │  centang "Verif"
                           ▼
                  tblreturverifikasi  → Export Terverifikasi (.xls)
```

Ide dasarnya: Jubelio adalah pembanding. Accounting memastikan setiap retur yang
**benar-benar diterima & dibuka di gudang** (data iresis) sesuai dengan retur
yang tercatat di Jubelio, lalu menandainya **sudah diverifikasi**.

---

## 2. Menu ① Upload Retur Jubelio

**Controller:** `Retur::upload_retur_jubelio()` (halaman) dan `Retur::upload_jubelio()`
(proses unggah, route `retur/upload-jubelio`). Daftar: `Retur::get_jubelio_list_data()`
(route `retur/get-jubelio-list-data`).

### Yang dilakukan user
1. Pilih file Excel **"daftar retur penjualan"** dari Jubelio (`.xlsx` / `.xls`).
2. Klik **Upload & Validasi**. Pesan hasil: `N baris baru, M baris diperbarui
   statusnya, K resi cocok dengan data scan iresis`.
3. Tabel **List Retur Jubelio** di bawahnya (server-side DataTable, bisa dicari
   berdasarkan resi, no pesanan, SKU, nama barang, marketplace, toko) menampilkan
   isi `tblreturjubelio`. Kolom **"Cocok di iresis?"** = `Ya` bila resi itu sudah
   ada di `tblresiretur` (sudah di-scan Terima Retur).

### Yang terjadi di server
- `Retur_fcd::parse_jubelio_spreadsheet()` memetakan header Excel → kolom:

  | Header Excel Jubelio | Kolom DB |
  |----------------------|----------|
  | `tracking_number` | `no_resi` (wajib) |
  | `salesorder_no` | `no_pesanan` (wajib) |
  | `status_retur` | `status_retur` |
  | `SKU`, `Nama Barang`, `QTY`, `amount` | `sku`, `nama_barang`, `qty`, `amount` |
  | `Tanggal` | `tanggal_retur` |
  | `Sumber` | `marketplace` |
  | `store` | `nama_toko` |
  | `Status` | `status_jubelio` (cancel/shipped/completed) |
  | `Kurir` | `kurir` |

  Bila `tracking_number` / `salesorder_no` tidak ada, upload ditolak
  ("pastikan ini file daftar retur penjualan"). Baris yang resi **dan** no
  pesanannya kosong dibuang (file Jubelio banyak baris hantu).
- `Retur_fcd::insert_jubelio_batch()`:
  1. Baris dengan kunci sama `(no_resi, no_pesanan, sku)` di dalam satu file
     **dijumlahkan** qty & amount-nya.
  2. Cek `tblresiretur` (→ `found_in_iresis`), `tblprintresi` (→ `match_resi`),
     `tbldetailprintresi` (→ `match_pesanan`).
  3. **Upsert** ke `tblreturjubelio` dengan `INSERT … ON DUPLICATE KEY UPDATE`
     pada unique key `uq_resi_pesanan_sku (no_resi, no_pesanan, sku)`. Jadi
     mengunggah file yang sama berkali-kali **aman**: baris lama diperbarui
     (status, qty, tanggal, flag kecocokan), bukan digandakan.
  4. Semua dalam satu transaksi (`trans_start/complete`); `batch_id` =
     `YmdHis_xxxxxx`.
- Memori 3072 MB & tanpa batas waktu (`set_time_limit(0)`), karena file bisa
  puluhan ribu baris.

### Otomatis harian (tanpa user)
Jalur yang sama dipanggil juga oleh `Cron::auto_upload_retur_jubelio()`, yang
dipicu `scripts/auto_upload_retur_jubelio.py` (browser automation Jubelio,
rentang 1 Januari s/d hari ini). Hasilnya masuk ke tabel yang sama dengan
`uploaded_by = NULL`. Detail teknis, jadwal, dan troubleshooting:
[`AUTO_UPLOAD_RETUR_JUBELIO.md`](AUTO_UPLOAD_RETUR_JUBELIO.md).

Kondisi data prod per 7 Nov 2026: `tblreturjubelio` ±97.600 baris, tanggal retur
4 Nov 2025 – 6 Nov 2026, unggahan terakhir 7 Nov 2026 14:30.

---

## 3. Menu ② Verifikasi Retur dan ③ Verifikasi Retur Komplain

Satu view (`retur/validasi_jubelio.php`) dipakai kedua menu. Bedanya hanya
flag `is_komplain`:

| | Verifikasi Retur | Verifikasi Retur Komplain |
|---|---|---|
| Method | `Retur::validasi_jubelio()` | `Retur::verifikasi_retur_komplain()` |
| Judul panel | Laporan Verifikasi Retur | Laporan Verifikasi Retur Komplain |
| Sumber scan iresis | `tblresiretur.is_komplain = 0` | `tblresiretur.is_komplain = 1` |
| Asal scan | Terima Retur ekspedisi | Terima Retur komplain ([`TERIMA_RETUR_HO.md`](TERIMA_RETUR_HO.md)) |

Join ke `tblbukaretur` sengaja memakai `br.is_komplain = tr.is_komplain`, supaya
satu baris Buka Retur tidak muncul di kedua laporan.

### Filter & tombol
- **Rentang Waktu** (wajib; berdasarkan `tblresiretur.tanggal_resiretur`), **Kurir**,
  **Kondisi** (Cocok / Hanya di iresis / Hanya di Jubelio / Sudah / Belum Diverifikasi).
- **Tampilkan** → `POST retur/get-rekonsiliasi-data`.
- **Export Semua**, **Export Terverifikasi**, **Export Update** →
  `retur/export-rekonsiliasi` (file `.xls` HTML; nama
  `Rekonsiliasi_Retur_Jubelio_*` atau `Retur_Terverifikasi_Jubelio_*`).
- **Pilih Semua** + **Verifikasi Terpilih (n)** → verifikasi massal.

### Cara rekonsiliasi (`Retur::_reconcile`)
1. Ambil sisi iresis (`Retur_fcd::get_iresis_recon`): `tblresiretur` di-join
   `tblprintresi`, `tbldetailprintresi`, `tblbukaretur`, marketplace, kurir;
   dikelompokkan per `noresi` + `id_bukaretur` → **satu baris per resi per SKU hasil
   Buka Retur**. Hanya `is_update = 0` untuk tabel utama.
2. Ambil sisi Jubelio (`get_jubelio_recon`) per `no_resi` — termasuk resi yang
   tanggal Jubelio-nya di luar rentang (di-chunk 500 resi per `IN()` agar query
   builder CI tidak gagal pada ribuan resi).
3. Tentukan **Kondisi**:
   - **Cocok** — resi ada di iresis **dan** di Jubelio. Kolom Nama Toko, Status
     Jubelio, Keterangan, dan Qty Jubelio diambil dari Jubelio.
   - **Hanya di iresis** — sudah discan di gudang tapi tidak ada di file Jubelio.
   - **Hanya di Jubelio** — hanya berlaku di hitungan; **baris-nya tidak
     ditampilkan** (laporan hanya memuat retur yang ada di iresis). Resinya tetap
     ada di menu ① untuk dicari.
4. Gabungkan status verifikasi dari `tblreturverifikasi`.

### Dua tabel dalam satu halaman
- **Laporan Verifikasi Retur** — scan normal (`is_update = 0`).
- **Laporan Update Retur** — resi yang discan ulang (`is_update = 1`), khusus baris
  yang ada sisi iresis-nya.

### Kartu ringkasan (klik = filter tabel)
Dihitung **per baris**, bukan per resi.

| Kartu | Arti |
|-------|------|
| TOTAL | Semua baris (tabel utama + Update) |
| Sudah Diverifikasi | Baris dengan centang Verif |
| Cocok | Kondisi Cocok |
| Hanya di iresis | Kondisi Hanya di iresis |
| Hanya di Jubelio | Kondisi Hanya di Jubelio |
| Update Retur | Jumlah baris tabel Update |
| Ditolak | `Status Buka` terisi dan bukan `KE_DISPLAY` |
| Setujui Jubelio | `Status Buka = KE_DISPLAY` dan kondisi bukan "Hanya di iresis" (di tabel Update: hanya yang **belum** diverifikasi) |

### Kolom tabel
No. Resi, No. Pesanan, Marketplace, Nama Toko, Kurir, Kategori iresis
(`status_retur`), Status Jubelio, Keterangan (Status Paket), Qty iresis, Qty Jubelio,
Tanggal, Kondisi, **Status Buka**, **Ditolak Karena Apa**, **Detail SKU** (SKU +
qty hasil scan buka), **SKU Pergantian** (hanya untuk status penukaran/pending
beres/request pembeli), **Verifikasi**.

### Verifikasi
- **Per resi:** centang kotak "Verif" di baris → `POST retur/verifikasi-jubelio`
  (`set_verifikasi($no_resi, 1|0, user)`). Mencentang ulang untuk membatalkan.
- **Massal:** centang beberapa baris (atau Pilih Semua) → **Verifikasi Terpilih** →
  `POST retur/bulk-verifikasi-jubelio` (satu transaksi).
- Disimpan di `tblreturverifikasi` (`no_resi` UNIQUE, `verified`, `verified_by`,
  `verified_at`, `catatan`). Baris yang sudah diverifikasi menampilkan nama user dan
  waktunya; baris "Hanya di Jubelio" tidak bisa diverifikasi (belum diterima).
- **Verifikasi disimpan per `no_resi`**, tidak dibedakan per menu. Ini aman karena
  `is_komplain` memisahkan sumber scan, jadi satu resi hanya muncul di salah satu
  dari menu ② atau ③.

Kondisi data prod per 7 Nov 2026: `tblreturverifikasi` 30.039 resi, 30.032
terverifikasi. `tblresiretur`: normal 29.252 (+78 update), komplain 2.169 (+62 update).

---

## 4. Urutan kerja harian yang disarankan

1. Pastikan unggahan Jubelio hari ini sudah masuk (otomatis; bila perlu unggah manual di
   menu ①, cek tabel "List Retur Jubelio").
2. Buka menu ② atau ③, pilih rentang waktu + **Tampilkan**.
3. Periksa baris **Hanya di iresis** (belum ada di Jubelio), **Ditolak**, dan
   selisih Qty iresis vs Jubelio.
4. Centang baris yang sudah sesuai → **Verifikasi Terpilih**.
5. **Export Terverifikasi** bila perlu dilaporkan.

---

## 5. File terkait

| File | Fungsi |
|------|--------|
| `application/controllers/Retur.php` | `upload_retur_jubelio`, `upload_jubelio`, `validasi_jubelio`, `verifikasi_retur_komplain`, `get_rekonsiliasi_data`, `_reconcile`, `verifikasi_jubelio`, `bulk_verifikasi_jubelio`, `get_jubelio_list_data`, `export_rekonsiliasi` |
| `application/models/Retur_fcd.php` | `parse_jubelio_spreadsheet`, `insert_jubelio_batch`, `get_jubelio_list`, `get_iresis_recon`, `get_jubelio_recon`, `set_verifikasi`, `get_verifikasi_list` |
| `application/controllers/Cron.php` | `auto_upload_retur_jubelio` |
| `application/views/retur/upload_retur_jubelio.php` | Halaman menu ① |
| `application/views/retur/validasi_jubelio.php` | Halaman menu ② dan ③ |
| `application/views/retur/export_rekonsiliasi.php` | Template export `.xls` |
| `application/config/routes.php` | Route `retur/*` baris 167–168 dan 202–208 |
| `scripts/auto_upload_retur_jubelio.py` | Unduh laporan Jubelio otomatis |

Tabel: `tblreturjubelio` (data Jubelio), `tblreturverifikasi` (status verifikasi),
`tblresiretur` + `tblbukaretur` (scan gudang), `tblprintresi` + `tbldetailprintresi`
(data pesanan).

## 6. Catatan

- Menu dan hak aksesnya dibuat otomatis oleh konstruktor `Retur.php` (parent 92,
  sortorder 65/70/80); nama "Verifikasi Retur" dulunya "Validasi Jubelio" (URI tetap
  `retur/validasi-jubelio`).
- Tampilan lama "Data Lama (07-07)" di halaman ini sudah dihapus
  (commit `eef9954`).
- Daftar menu yang disebut "tidak dipakai" di atas adalah informasi operasional
  dari user per 7 Nov 2026, bukan hasil penelusuran log pemakaian.
