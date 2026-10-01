# Struktur Database & Model IRESIS

> **Diaudit ulang 1 Okt 2026.** Sebagian tabel di dokumen ini (Core Tables,
> Lost Scan Tables) sudah pernah diverifikasi ketat sebelumnya dan masih
> akurat. Bagian lain (§User & Access, sebagian §KPI, §Master Data, dan
> beberapa method di §Model Structure) ternyata menyebut nama tabel/kolom/
> method yang **tidak pernah ada** — sudah diperbaiki di audit ini
> berdasarkan `SHOW CREATE TABLE` langsung ke `iresis_prod` dan `grep` ke
> kode model. Kalau ragu pada bagian yang belum ditandai "diverifikasi",
> cek ulang ke DB/kode — dokumen ini historis dan bisa drift lagi.

## 📊 Database Schema Overview

### **Core Tables**

#### 1. **tblprintresi** - Master Receipt/Resi
```sql
- id_printresi (PK)
- noresi (UNIQUE)
- id_marketplace (FK → tblmarketplace)
- id_kurir (FK → tblkurir)
- nomorpicklist
- batal (varchar: '' = tidak batal, '1' = batal — penjaga membandingkan string)
- tanggal_printresi
- status_pesanan (nilai nyata di produksi: PROCESSING, SHIPPED, COMPLETED, CANCELED, RETURNED, REQUEST_CANCEL, NULL — status dari marketplace, BUKAN tahapan gudang; tahapan gudang dibaca dari ada/tidaknya baris di tblresiambilbarang/tblpacking/tblresikeluar)
- created_by (varchar — **bukan** FK int ke tbluser; diisi teks dari proses upload, bisa nama/username apa saja)
- created_at
```

#### 2. **tbldetailprintresi** - Detail Item per Resi
```sql
- id_detail_resi (PK)                     -- bukan id_detail
- id_resi (FK → tblprintresi)
- no_pesanan
- sku
- jumlah
- no_rak
- status_kurangan (DEFAULT 'Tidak'), qty_kurang, tanggal_scan_kurangan,
  jenis_penyelesaian_kurangan, note_kurangan, sku_pengganti,
  tanggal_selesai_kurangan, user_selesai_kurangan   -- alur Kurangan Picker
```

#### 3. **tblresiambilbarang** (tblpicking) - Data Picking
```sql
- id_resiambilbarang (PK)
- id_resi (FK → tblprintresi)
- tanggal_resiambilbarang
- admin_pegawai (FK → tbluser)
- yangambil_pegawai (FK → tblpegawai)
- nama_komputer
- pending
- status_performa_id (FK → tblmasterstatusperforma)
```

#### 4. **tblpacking** - Data Packing
```sql
- id_packing (PK)
- tanggal_packing (INDEX)
- id_resi (FK → tblprintresi, INDEX)
- packer_pegawai (FK → tbluser.id_user)          -- akun packer, BUKAN tblpegawai
- status_performa_id (FK → tblmasterstatusperforma, NULL)
- keterangan                                     -- nama komputer packer ("(Combined)" untuk SCAN COMBINED)
- video_path (NULL)                              -- rekaman webcam packing
```
Ditulis `Packer_fcd::save()` / `save_packer_nonsubmit()` dan
`Resi_team_fcd::save_combined_scan()`. Nama lama `tblpacker` di dokumen ini
tidak pernah ada; yang ada dengan awalan itu hanya `tblpacker_sessions` dan
`tblpacker_performance_logs` (monitoring packer).

#### 4a. **tblmasalahpicker** - Laporan Masalah Picker (dari meja packer)
```sql
- id_masalahpicker (PK)
- id_printresi (FK → tblprintresi)
- noresi
- sku                    -- SKU di resi (yang seharusnya)
- qty (DEFAULT 1)        -- qty SKU itu di resi
- id_typemasalah (FK → tbltypemasalah: 1 TIDAK AMBIL, 2 LEBIH AMBIL, 3 KURANG AMBIL, 4 SALAH AMBIL, 5 REJECT DISPLAY)
- qty_bermasalah (DEFAULT 1)
- sku_salah (NULL)       -- hanya tipe 4: SKU yang keliru terambil, dicetak di slip "(terambil …)"
- status (DEFAULT 0)     -- 0 pending, 1 sudah diproses CS
- created_by (FK → tbluser)  -- packer pelapor
- created
- updated_by (NULL), updated (NULL)
```
Ditulis oleh: modal **Masalah Picker** di Scan Resi Packer / Webcam
(`Packer_fcd::save_masalah_picker()` — meng-UPDATE baris lama untuk
`id_printresi`+`sku` yang sama dan mereset `status` ke 0) dan menu
**Salah Ambil Special** (`Salah_ambil_special_fcd::simpan_salah_ambil()` —
hanya INSERT tipe 4, menolak kalau sudah ada baris untuk `id_printresi`+`sku`
status apa pun; lihat `SALAH_AMBIL_SPECIAL.md`). Dibaca oleh Daftar Masalah
Picker (lama `Cs.php` & New `Masalah_picker_new.php`, `status = 0`), Restock
(`status = 1`), KPI picker (`Kpi_reports.php`), Error Recap, Laporan —
tiga yang terakhir **tanpa filter status**. Picker tidak disimpan di sini:
dideteksi dari `tblresiambilbarang.yangambil_pegawai` saat ditampilkan.
Tidak ada UNIQUE pada `(id_printresi, sku)`.

#### 5. **tblresikeluar** - Data Handover / Scan Resi Keluar (HO)
```sql
- id_resikeluar (PK)
- tanggal_resikeluar (INDEX)
- id_resi (FK → tblprintresi, INDEX)   -- belum UNIQUE, lihat dev_tools/optimasi_scan_ho.sql
- id_pegawai (FK → tbluser.id_user)    -- petugas HO yang scan
- sudah_cetak
- tanggal_cetak
```
Ditulis `Handover_fcd` (menu Scan Resi Keluar) dan `Scan_logistic_fcd::save_scan()`
(Scan Paket NDD / NDD New). Nama lama `tblhandover` di dokumen ini tidak
pernah ada.

#### 5a. **tblscan_ndd** - Scan Paket NDD
```sql
- id_scan_ndd (PK)
- id_resi (FK → tblprintresi, UNIQUE)
- tanggal_scan (INDEX)
- id_pegawai (FK → tbluser.id_user)
- keterangan
```
Diisi bersamaan dengan `tblresikeluar` saat mode HO+NDD di Scan Paket NDD;
kurir Shopee tidak pernah masuk ke sini.

#### 6. **tblresiretur** / **tblbukaretur** - Data Retur (bukan `tblretur`, yang tidak pernah ada)
```sql
-- tblresiretur: resi retur yang diterima (scan terima)
- id_resiretur (PK)
- id_resi (FK → tblprintresi)
- id_kurir (FK → tblkurir)
- id_marketplace (FK → tblmarketplace)
- noresi
- tanggal_resiretur
- status_detail, status_retur
- is_update, is_komplain, is_shipped_retur

-- tblbukaretur: hasil buka retur per SKU (1 resi retur bisa banyak baris SKU)
- id_bukaretur (PK)
- resi_buka                 -- noresi retur
- sku, qty, sku_pergantian
- status_buka, status_detail_buka
- alasan_ditolak, harga, no_pesanan, toko, sumber_input (scan|import)
- is_komplain (0=retur biasa, 1=komplain — mengikuti tblresiretur)
- tanggal_buka_retur, id_pegawai, created_at, updated_at
- status_acc, acc_by
```
Tabel retur lain yang juga ada: `tblreturklaim`, `tblreturcomplain`,
`tblreturjubelio`, `tblreturverifikasi`, `tblretur_display_batch(_detail)`,
`tblcancel_paket`/`tblcancel_paket_tolak` (lihat §23–24 di bawah). Tidak ada
satu pun tabel bernama `tblretur`.

---

### **KPI & Performance Tables**

#### 7. **tblmasterstatusperforma** - Master Status Performa
```sql
- id_statusperforma (PK)
- kode_status (UNIQUE)
- role - PICKER atau PACKER
- status_name
- deskripsi
- target_harian (DEFAULT 50)
- isactive
- createdby, created
- updatedby, updated
```

**Status Performa Picker** (isi aktual tabel, 1 Okt 2026 — tidak ada `FAST_PICKER`):
- `NORMAL_PICKER`
- `1_SKU_PICKER`

**Status Performa Packer:**
- `NORMAL_PACKER` (bukan `NORMAL`)
- `1_SKU_PACKER` (bukan `1_SKU`)
- `GTL` - Good To Live
- `NDD` - Next Day Delivery
- `MOONKLAZ`
- `PAYUNG`
- `QTY_BANYAK`
- `NINJA`

#### 8. **tblstatusperforma** - Log Status Performa Harian
```sql
- id_log (PK)
- id_user (FK → tbluser)
- id_statusperforma (FK → tblmasterstatusperforma)
- tanggal (DATE)
- jam_login (TIME, nullable)
- isactive
- createdby, created
- updatedby, updated
- UNIQUE KEY (id_user, tanggal)
```

#### 9. **tblkpi** - Log Transaksi KPI (bukan "total_scan/target_harian/achievement" — kolomnya beda)
```sql
- id_log (PK)                              -- bukan id_kpi
- id_user (FK → tbluser)
- id_statusperforma (FK → tblmasterstatusperforma, ON DELETE CASCADE)
- tanggal (DATE)
- tipe_transaksi (ENUM: PACKER, PICKER)
- jumlah_resi (DEFAULT 1)
- createdby, created
- updatedby, updated
```
Target & pencapaian harian **tidak disimpan di tabel ini** — dihitung saat
tampil, digabung dengan `tbltargetkpiharian` (lihat #10).

#### 10. **tbltargetkpiharian** - Target KPI Harian (bukan `tbltargetkpi`)
```sql
- id_target (PK)
- id_user
- tanggal (DATE)
- target_resi (DEFAULT 0)
- role (varchar — bukan FK ke tblmasterstatusperforma)
- UNIQUE KEY (id_user, tanggal, role)
```
Tidak ada kolom `isactive`, `createdby/created/updatedby/updated`, atau
`id_statusperforma` di tabel ini.

---

### **User & Access Tables**

> Tabel-tabel ini di versi sebelumnya dokumen diberi nama yang tidak pernah
> ada di skema (`tblmenu`, `tblakses`, `tblrole`) — sudah diganti ke nama
> aslinya (`menu`, `roleaccess`, `tblhakakses`).

#### 11. **tbluser** - Master User
```sql
- id_user (PK)
- username                    -- TIDAK ada UNIQUE constraint di DB; dijaga di level aplikasi saja
- password (MD5)
- nama_komputer
- hakakses (int)              -- id role; TIDAK ada FK beneran, cocokkan manual ke tblhakakses.id_hakakses
- id_pegawai (FK → tblpegawai, nullable)
- name, email
- lastlogin, last_activity
- isactive
- createdby, created, updatedby, updated
- bypass (tinyint, DEFAULT 0)
- status_performer
- foto
```
Tidak ada kolom `akses` terpisah dari `hakakses`.

#### 12. **menu** - Master Menu (bukan `tblmenu`)
```sql
- id (PK)                     -- bukan id_menu
- parentid (FK → menu.id — self reference, bukan parent_id)
- name                        -- bukan nama_menu
- uri                         -- bukan url
- icon
- sortorder                   -- bukan urutan
- description
- isactive
- createdby, created, updatedby, updated
```

#### 13. **roleaccess** - Access Control (bukan `tblakses`; struktur beda total)
```sql
- id (PK)
- menuid (varchar)            -- bukan id_menu int FK
- roleid (varchar)            -- bukan id_role int FK; bisa berisi beberapa role sekaligus
- createdby, created
```

#### 14. **tblhakakses** - Master Role (bukan `tblrole`)
```sql
- id_hakakses (PK)            -- bukan id_role
- akses                       -- nama role; bukan nama_role
```
Tidak ada kolom `isactive` di tabel ini.

---

### **Master Data Tables**

#### 15. **tblmarketplace** - Master Marketplace
```sql
- id_marketplace (PK)
- nama_marketplace
- isactive
```

**Contoh:**
- Shopee
- Lazada
- Tokopedia
- dll

#### 16. **tblkurir** - Master Kurir
```sql
- id_kurir (PK)
- nama_kurir
- isactive
```

**Contoh:**
- JNE
- JNT
- Ninja Express
- SiCepat
- dll

#### 17. **tblpegawai** - Master Karyawan
```sql
- kode_pegawai (PK, auto-increment)    -- bukan id_pegawai; ini SATU-SATUNYA id, bukan kode terpisah
- nama_pegawai
- status_aktif (varchar, nilai aktual: AKTIF/NONAKTIF)
```

#### 18. **tblnamaambilbarang** - Master Picker
```sql
- id_namaambilbarang (PK)
- id_pegawai (FK → tblpegawai.kode_pegawai)
- status_aktif
```

#### 19. **tblsku** - Master SKU
```sql
- id_sku (PK, varchar)        -- kode SKU ITU SENDIRI jadi primary key, bukan auto-increment int; tidak ada kolom "sku" terpisah
- nama_sku
- nama_bundle, bundle, variasi
- hpp (decimal)
- lokasi, no_rak, no_rak_gudang   -- rak tersimpan langsung di sini, bukan tabel master terpisah
- total_stok
- link_foto, foto_lokal        -- foto_lokal diisi cron sinkron_foto_sku (lihat CLAUDE.md)
- berat (gram)
- is_special, was_special, jenis_packing
- updated
```
Tidak ada kolom `isactive`.

Tidak ada tabel master lokasi/rak terpisah (`tbllokasi` di versi sebelumnya
dokumen ini **tidak pernah ada**) — rak tersimpan sebagai kolom `lokasi`/
`no_rak`/`no_rak_gudang` di `tblsku` itu sendiri. Riwayat perubahan rak ada
di **`tblrak_history`** (`id_sku`, `jenis`, `rak_lama`, `rak_baru`, `sumber`,
`id_pegawai`, `created_at`), ditulis lewat menu Accounting
(`accounting/get-rak-history`).

---

### **Lost Scan Tables**

Dipakai alur lost scan antar meja (rilis 18 Sep 2026, lihat
`docs/superpowers/specs/2026-09-18-lost-scan-picker-tahap-a-design.md`).

#### 21. **tbllostscanpacker** - Catatan Lost Scan (Packer/Picker/HO)
```sql
- id_lostscanpacker (PK)
- noresi
- lost_type (ENUM: PICKER, PACKER, HO)
- nama_packer          -- nama pegawai yang lupa scan (tblpegawai.nama_pegawai), apa pun lost_type-nya
- status_resi          -- status_pesanan resi saat dicatat
- kurir                -- nama_kurir saat dicatat
- created_at (INDEX)
- created_by (FK → tbluser)  -- pelapor
```
Ditulis oleh: menu Lost Scan Packer/Picker/HO (lama), Scan Paket NDD New
(baris `PACKER`), dan `Lost_scan_picker_fcd::tambah_picker()` (baris
`PICKER`, dibuat saat tim picker menentukan picker — bukan saat lapor).
Model lama `Lost_scan_packer_fcd::save()` menolak duplikat per `noresi`
saja; jalur baru mengecek per `(noresi, lost_type)`.

#### 22. **tbllostscanpicker_pending** - Antrean Resi Belum Di-picker
```sql
- id_pending (PK)
- id_printresi (FK → tblprintresi)
- noresi
- sumber (ENUM: PACKER, HO)            -- meja yang melapor
- dilaporkan_oleh (FK → tbluser)
- waktu_lapor
- status (ENUM: PENDING, SELESAI, SELESAI_LUAR)
- kode_picker (FK → tblpegawai.kode_pegawai, NULL)   -- diisi saat diproses
- diproses_oleh (FK → tbluser, NULL)
- waktu_proses (NULL)
- id_resiambilbarang (FK → tblresiambilbarang, NULL) -- baris picking yang dibuat
- id_lostscanpacker (FK → tbllostscanpacker, NULL)   -- baris PICKER yang dibuat
- INDEX (noresi, status), INDEX (status, waktu_lapor)
```
Dibuat migrasi `MY_Controller::run_lost_scan_picker_migration()`
(`BOOTSTRAP_VERSI` 2026-09-18.x). Satu resi hanya punya satu baris
`PENDING` (dijaga `Lost_scan_picker_fcd::lapor()`). `SELESAI_LUAR` = baris
picking ternyata sudah dibuat di luar alur (mis. SCAN COMBINED), laporan
ditutup tanpa menulis picking/lost scan. SKU/qty/no rak tidak disimpan —
dibaca dari `tbldetailprintresi` saat tampil.

Alur: Packer (webcam) / HO (Scan Paket NDD New) → `lapor()` → PENDING →
tim picker *Tambahkan Picker* → `tambah_picker()` → insert
`tblresiambilbarang` (`nama_komputer = 'LOST SCAN PICKER'`, tanpa KPI) +
insert `tbllostscanpacker` PICKER → SELESAI → packer scan ulang → HO scan
ulang.

Dipakai alur paket cancel (tahap 1 live 21 Sep 2026, lihat `docs/PAKET_CANCEL.md`).

#### 23. **tblcancel_paket** - Paket Cancel (satu per resi yang barangnya sudah keluar display)
```sql
- id_cancel_paket (PK)
- id_resi (FK → tblprintresi, UNIQUE)
- noresi (INDEX)
- status (ENUM: DITEMUKAN, DICEK, DIKIRIM, SELESAI)   -- tahap 1 hanya menulis DITEMUKAN
- ditemukan_di (ENUM: PICKER, INBOUND, PACKER, HO)    -- meja fisik tempat paket pertama ditolak
- ditemukan_oleh (FK → tbluser, NULL)
- ditemukan_komputer
- ditemukan_at
- jumlah_tolak                                        -- naik tiap penolakan berikutnya
- tolak_terakhir_di (ENUM: PICKER, INBOUND, PACKER, HO, LOST_SCAN, NULL)
- tolak_terakhir_at
- dicek_oleh, dicek_at            (tahap 2)
- id_batch (FK → tblretur_display_batch, NULL)  (tahap 3)
- selesai_at                      (tahap 3)
- catatan
- created_at, updated_at
- INDEX (status, ditemukan_at)
```
Ditulis `Cancel_paket_fcd::catat_tolak()` lewat `INSERT … ON DUPLICATE KEY
UPDATE`: baris baru = DITEMUKAN; baris lama hanya `jumlah_tolak` dan
`tolak_terakhir_*` yang berubah, status tidak disentuh. Dibuat hanya bila
barang sudah keluar display (penolakan di picker tanpa picking tidak
melahirkan baris ini).

#### 24. **tblcancel_paket_tolak** - Jejak Scan Ditolak Karena Cancel
```sql
- id_tolak (PK)
- id_resi (FK → tblprintresi)
- noresi
- tahap (ENUM: PICKER, INBOUND, PACKER, HO, LOST_SCAN)
- alasan                          -- CANCELED / REQUEST_CANCEL / BATAL_MANUAL
- id_user (FK → tbluser, NULL)
- nama_komputer
- waktu
- keterangan                      -- mis. 'Scan Paket REGULER', 'Update Picker', 'Scan Non-Submit'
- INDEX (id_resi, waktu), INDEX (waktu)
```
Satu baris per penolakan, selalu ditulis (juga saat tidak melahirkan
`tblcancel_paket`). Titik penulisannya 7 buah — `PAKET_CANCEL.md` §7.1.
Kedua tabel dibuat `MY_Controller::run_paket_cancel_migration()`
(`BOOTSTRAP_VERSI` 2026-09-21.3).

## 🔗 Relationship Diagram

```
tbluser
  ├─→ tblresiambilbarang (admin_pegawai)
  ├─→ tblpacking (packer_pegawai)
  ├─→ tblresikeluar (id_pegawai)
  ├─→ tblscan_ndd (id_pegawai)
  ├─→ tblstatusperforma (id_user)
  ├─→ tblkpi (id_user)
  ├─→ tbltargetkpiharian (id_user)
  ├─→ tbllostscanpacker (created_by)
  └─→ tbllostscanpicker_pending (dilaporkan_oleh, diproses_oleh)

tblprintresi
  ├─→ tbldetailprintresi (id_resi)
  ├─→ tblresiambilbarang (id_resi)
  ├─→ tblpacking (id_resi)
  ├─→ tblresikeluar (id_resi)
  ├─→ tblscan_ndd (id_resi)
  ├─→ tbllostscanpicker_pending (id_printresi)
  └─→ tblmarketplace (id_marketplace)
  └─→ tblkurir (id_kurir)

tbllostscanpicker_pending
  ├─→ tblresiambilbarang (id_resiambilbarang)  -- picking susulan yang dibuat
  ├─→ tbllostscanpacker (id_lostscanpacker)    -- baris PICKER yang dibuat
  └─→ tblpegawai (kode_picker)

tblmasterstatusperforma
  ├─→ tblstatusperforma (id_statusperforma)
  ├─→ tblkpi (id_statusperforma)
  ├─→ tblresiambilbarang (status_performa_id)
  └─→ tblpacking (status_performa_id)
(tbltargetkpiharian TIDAK punya FK ke tblmasterstatusperforma -- lihat #10)

tblpegawai
  ├─→ tblnamaambilbarang (id_pegawai)
  ├─→ tblresiambilbarang (yangambil_pegawai)
  ├─→ tbluser (id_pegawai)
  └─→ tbllostscanpicker_pending (kode_picker)

menu
  └─→ menu (parentid) - Self reference

tblhakakses
  └─→ tbluser (hakakses, dicocokkan manual -- bukan FK database beneran)

roleaccess
  -- menuid/roleid berupa varchar, dicocokkan manual ke menu.id / tblhakakses.id_hakakses saat ambil_menu_tree()
```

---

## 📁 Model Structure

### **Core Models**

#### **Receipt_fcd.php**
```php
Methods:
- get_data($data) - Get list receipt dengan filter
- get_total_data($data) - Count total receipt
- get_detail_receipt($noresi) - Get detail receipt
- get_detail($noresi) - Get receipt info
- get_detail_items($noresi) - Get items per receipt
- save($receipt, $user_id) - Save new receipt
- get_header_daily_report($start, $end) - Daily report header
- get_receipt_for_packer($data, $noresi) - Receipt data for packer
```

#### **Picking_fcd.php**
```php
Methods (diverifikasi 1 Okt 2026 -- get_picker_detail()/get_picker_by_date()
di versi sebelumnya dokumen ini TIDAK PERNAH ADA):
- get_picker($picker_status_aktif) - Get list picker
- get_next_picker_rr() - Round-robin pemilihan picker berikutnya
- save($picking, $user, $mode) - Save picking data
- save_picker($picker)
- get_data($data) / get_total_data($data) - List + count picking
- destroy_picker($id_namaambilbarang)
- get_total_scan_user($id_user) - Get total scan per user
- get_total_scan_preorder_user($id_user)
- process_kpi_queue() + beberapa method log_kpi_transaksi*() (private) -- antrean KPI async
```

#### **Packer_fcd.php**
```php
Methods:
- periksa_kelayakan_packing($noresi) - Penjaga: ada, belum batal/selesai, sudah picker, belum packing
- save($packer, $user) / save_packer_nonsubmit() - Insert tblpacking + reset pending picker (+ KPI, monitoring di luar transaksi)
- info_packing($noresi) - Siapa & kapan resi di-packing
- get_total_scan_user($user_id) - Total scan per user
```

#### **Kpi_fcd.php**
```php
Methods (diverifikasi 1 Okt 2026 -- get_kpi_data()/save_target_kpi() di
versi sebelumnya dokumen ini TIDAK PERNAH ADA; model ini jauh lebih besar
dari yang didokumentasikan sebelumnya, ~25 method):
- get_status_performa($id) / get_status_performa_by_kategori($kategori)
- save_status_performa($status, $user_id)
- get_status_id_by_name($status_name)
- log_status_performa($user_id, $status_id, $tanggal) / log_status_performa_with_target(...)
- get_user_status_performa($user_id, $tanggal)
- log_transaksi_harian($user_id, $status_id, $tipe_transaksi, $jumlah_resi, $tanggal) / get_transaksi_harian(...)
- get_kpi_dashboard/get_kpi_by_status/get_kpi_summary/get_kpi_summary_cards($start, $end, ...)
- get_top_performers($start, $end, $limit)
- update_kpi_harian($tanggal)
- get_daily_performance_chart/get_daily_trend_data/get_daily_performance($start, $end)
- get_status_performance_comparison/get_status_performa_cards($start, $end, ...)
- get_realtime_performance($tanggal) / get_user_performance_today($user_id, $tanggal)
- get_total_receipts_processed/get_total_shipped_receipts/get_total_pending_receipts/get_total_retur_receipts($start, $end)
- get_avg_processing_time/get_picker_productivity/get_packer_productivity($start, $end)
```

---

## 🔍 Key Queries

### **Get Receipt dengan Join**
```sql
SELECT 
    t.noresi, 
    t.tanggal_printresi, 
    t3.nama_kurir, 
    t2.nama_marketplace, 
    t.nomorpicklist, 
    t.status_pesanan
FROM tblprintresi t
LEFT JOIN tblmarketplace t2 ON t.id_marketplace = t2.id_marketplace
LEFT JOIN tblkurir t3 ON t.id_kurir = t3.id_kurir
ORDER BY t.created_at DESC
```

### **Get Picking dengan Status Performa**
```sql
SELECT 
    rab.*,
    sp.status_name,
    sp.kode_status
FROM tblresiambilbarang rab
LEFT JOIN tblmasterstatusperforma sp ON rab.status_performa_id = sp.id_statusperforma
WHERE rab.id_resi = ?
```

### **Get KPI Data**
```sql
SELECT 
    k.*,
    u.username,
    sp.status_name,
    sp.kode_status
FROM tblkpi k
INNER JOIN tbluser u ON k.id_user = u.id_user
INNER JOIN tblmasterstatusperforma sp ON k.id_statusperforma = sp.id_statusperforma
WHERE k.tanggal BETWEEN ? AND ?
```

---

## 📝 Indexes (Recommended)

> Dicek ulang 1 Okt 2026: `idx_receipt_noresi` sudah ada (sebagai `UNIQUE KEY
> noresi`), dan `tblkpi`/`tblstatusperforma` sudah punya index/UNIQUE yang
> fungsinya sama dengan yang "direkomendasikan" di bawah (`fk_kpi_user`,
> `fk_kpi_status`, `idx_kpi_user_date_type`, `user_date_UNIQUE(id_user,
> tanggal)`) — baris itu dicoret, tidak perlu dibuat lagi. Yang masih
> benar-benar belum ada: `idx_receipt_status`, `idx_receipt_created`,
> `idx_picking_user`, `idx_picking_date` (index tunggal; `tblresiambilbarang`
> cuma punya `id_resi` UNIQUE).

```sql
-- Performance indexes yang BELUM ada (per 1 Okt 2026):
CREATE INDEX idx_receipt_status ON tblprintresi(status_pesanan);
CREATE INDEX idx_receipt_created ON tblprintresi(created_at);

CREATE INDEX idx_picking_user ON tblresiambilbarang(admin_pegawai);
CREATE INDEX idx_picking_date ON tblresiambilbarang(tanggal_resiambilbarang);

-- Diverifikasi ke iresis_prod 18 Sep 2026:
-- tblpacking     : idx_packing_date_user(tanggal_packing, packer_pegawai),
--                  idx_packing_id_date(id_resi, tanggal_packing), idx_packing_pegawai(packer_pegawai),
--                  idx_packing_status(status_performa_id). Tidak ada index tunggal id_resi
--                  (yang ada di DB lokal berasal dari script optimasi, bukan produksi).
-- tblresikeluar  : idx_resikeluar_resi(id_resi) NON-unique, idx_resikeluar_date, idx_resikeluar_pegawai
-- tblscan_ndd    : uq_scan_ndd_resi(id_resi) UNIQUE, idx_tanggal, idx_scan_ndd_pegawai_tgl
-- tblresiambilbarang: id_resi UNIQUE
-- tbllostscanpacker : idx_lostscan_date(created_at)

-- SUDAH ADA, jangan dibuat ulang (dicek 1 Okt 2026):
-- tblprintresi      : UNIQUE noresi
-- tblkpi            : fk_kpi_user(id_user), fk_kpi_status(id_statusperforma),
--                      idx_kpi_user_date_type(id_user, tipe_transaksi, tanggal, created)
-- tblstatusperforma : UNIQUE user_date_UNIQUE(id_user, tanggal)
```

---

## 🔄 Transaction Flow

### **Picking Transaction**
```php
1. Start Transaction
2. Check receipt exists
3. Check receipt status
4. Check duplicate picking
5. Insert picking record
6. Update receipt status
7. Commit/Rollback
```

### **Packing Transaction** (`Packer_fcd::save`)
```php
1. periksa_kelayakan_packing: resi ada, tidak batal/selesai, sudah picker, belum packing
2. db_debug = FALSE, trans_start
3. Insert tblpacking (packer_pegawai = id_user, keterangan = nama komputer)
4. Update tblresiambilbarang.pending = '' (reset pending picker)
5. trans_complete (gagal -> SAVE_FAILED)
6. Di luar transaksi: log KPI packer + log performa (packer_monitoring)
```

---

## 📊 Data Flow

```
INPUT → VALIDATION → PROCESS → DATABASE → RESPONSE
  │         │           │          │          │
  │         │           │          │          └─→ JSON/AJAX
  │         │           │          └─→ Transaction
  │         │           └─→ Business Logic
  │         └─→ Input Sanitization
  └─→ POST/GET Data
```

---

**Dokumen ini menjelaskan struktur database dan model yang digunakan dalam sistem IRESIS.**
**Diaudit & diperbaiki: 2026-10-01**

