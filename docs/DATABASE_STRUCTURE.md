# Struktur Database & Model IRESIS

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
- created_by (FK → tbluser)
- created_at
```

#### 2. **tbldetailprintresi** - Detail Item per Resi
```sql
- id_detail (PK)
- id_resi (FK → tblprintresi)
- sku
- jumlah
- no_rak
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

#### 6. **tblretur** - Data Retur
```sql
- id_retur (PK)
- noresi_retur
- noresi_asal
- tanggal_terima
- tanggal_buka
- status (TERIMA, BUKA, COMPLAIN)
```

---

### **KPI & Performance Tables**

#### 7. **tblmasterstatusperforma** - Master Status Performa
```sql
- id_statusperforma (PK)
- kode_status (UNIQUE) - NORMAL, FAST, GTL, NDD, dll
- role - PICKER atau PACKER
- status_name
- deskripsi
- target_harian
- isactive
- createdby, created
- updatedby, updated
```

**Status Performa Picker:**
- `NORMAL_PICKER` - Picking normal
- `FAST_PICKER` - Picking cepat

**Status Performa Packer:**
- `GTL` - Good To Live
- `NDD` - Next Day Delivery
- `1_SKU` - Single SKU Order
- `MOONKLAZ` - Moonklaz Order
- `PAYUNG` - Payung Order
- `QTY_BANYAK` - Large Quantity Order
- `NINJA` - Ninja Express Order
- `NORMAL` - Normal Order

#### 8. **tblstatusperforma** - Log Status Performa Harian
```sql
- id_log (PK)
- id_user (FK → tbluser)
- id_statusperforma (FK → tblmasterstatusperforma)
- tanggal (DATE)
- jam_login (TIME)
- isactive
- createdby, created
- updatedby, updated
- UNIQUE KEY (id_user, tanggal)
```

#### 9. **tblkpi** - Data KPI Harian
```sql
- id_kpi (PK)
- id_user (FK → tbluser)
- id_statusperforma (FK → tblmasterstatusperforma)
- tanggal (DATE)
- total_scan
- target_harian
- achievement
- createdby, created
- updatedby, updated
```

#### 10. **tbltargetkpi** - Target KPI
```sql
- id_target (PK)
- id_user (FK → tbluser)
- id_statusperforma (FK → tblmasterstatusperforma)
- tanggal (DATE)
- target_harian
- isactive
- createdby, created
- updatedby, updated
```

---

### **User & Access Tables**

#### 11. **tbluser** - Master User
```sql
- id_user (PK)
- username (UNIQUE)
- password (MD5)
- name
- hakakses (FK → tblrole)
- akses
- nama_komputer
- last_login
- bypass (boolean)
- isactive
```

#### 12. **tblmenu** - Master Menu
```sql
- id_menu (PK)
- nama_menu
- url
- icon
- parent_id (FK → tblmenu)
- urutan
- isactive
```

#### 13. **tblakses** - Access Control
```sql
- id_akses (PK)
- id_role (FK → tblrole)
- id_menu (FK → tblmenu)
- isactive
```

#### 14. **tblrole** - Master Role
```sql
- id_role (PK)
- nama_role
- isactive
```

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
- id_pegawai (PK)
- kode_pegawai
- nama_pegawai
- status_aktif (AKTIF/NONAKTIF)
```

#### 18. **tblnamaambilbarang** - Master Picker
```sql
- id_pegawai (FK → tblpegawai)
- status_aktif
```

#### 19. **tblsku** - Master SKU
```sql
- id_sku (PK)
- sku
- nama_sku
- link_foto
- isactive
```

#### 20. **tbllokasi** - Master Lokasi/Rak
```sql
- id_lokasi (PK)
- no_rak
- isactive
```

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

## 🔗 Relationship Diagram

```
tbluser
  ├─→ tblprintresi (created_by)
  ├─→ tblresiambilbarang (admin_pegawai)
  ├─→ tblpacking (packer_pegawai)
  ├─→ tblresikeluar (id_pegawai)
  ├─→ tblscan_ndd (id_pegawai)
  ├─→ tblstatusperforma (id_user)
  ├─→ tblkpi (id_user)
  ├─→ tbltargetkpi (id_user)
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
  ├─→ tbltargetkpi (id_statusperforma)
  ├─→ tblresiambilbarang (status_performa_id)
  └─→ tblpacking (status_performa_id)

tblpegawai
  ├─→ tblnamaambilbarang (id_pegawai)
  ├─→ tblresiambilbarang (yangambil_pegawai)
  └─→ tbllostscanpicker_pending (kode_picker)

tblmenu
  ├─→ tblmenu (parent_id) - Self reference
  └─→ tblakses (id_menu)

tblrole
  └─→ tblakses (id_role)
  └─→ tbluser (hakakses)
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
Methods:
- get_picker($status) - Get list picker
- save($picking, $user, $mode) - Save picking data
- get_total_scan_user($user_id) - Get total scan per user
- get_picker_detail($noresi) - Get picker detail
- get_picker_by_date($date, $user_id) - Get picker by date
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
Methods:
- get_status_performa($id) - Get status performa
- get_status_performa_by_kategori($kategori) - Get by role
- get_status_id_by_name($status_name) - Get ID by name
- log_status_performa($user_id, $status_id) - Log status
- get_user_status_performa($user_id) - Get user status
- get_kpi_data($filters) - Get KPI data
- save_target_kpi($target, $user_id) - Save target
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

```sql
-- Performance indexes
CREATE INDEX idx_receipt_noresi ON tblprintresi(noresi);
CREATE INDEX idx_receipt_status ON tblprintresi(status_pesanan);
CREATE INDEX idx_receipt_created ON tblprintresi(created_at);

CREATE INDEX idx_picking_resi ON tblresiambilbarang(id_resi);
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

CREATE INDEX idx_kpi_user_date ON tblkpi(id_user, tanggal);
CREATE INDEX idx_kpi_status ON tblkpi(id_statusperforma);

CREATE INDEX idx_status_user_date ON tblstatusperforma(id_user, tanggal);
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

