# Diagram Alur Kerja Sistem IRESIS

## 🔄 Workflow Utama: Receipt → Picking → Packing → Handover

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW UTAMA PRODUKSI                      │
└─────────────────────────────────────────────────────────────────┘

1. RECEIPT (Input Resi)
   │
   ├─→ Scan Receipt
   │   ├─→ Input: No Resi, Marketplace, Kurir, No Picklist
   │   ├─→ Validasi: Cek duplikasi resi
   │   └─→ Save: tblprintresi
   │
   ├─→ Detail Receipt
   │   └─→ View: Detail resi + items
   │
   └─→ List Receipt
       └─→ View: Daftar semua resi (filter, search, pagination)

2. PICKING (Ambil Item dari Gudang)
   │
   ├─→ Scan Picker
   │   ├─→ Input: No Resi, Picker ID, Status Performa
   │   ├─→ Validasi: Resi harus sudah di-receipt
   │   ├─→ Update: Status resi → "Picked"
   │   └─→ Save: tblpicking + Log KPI
   │
   ├─→ Pending Picker
   │   └─→ Resi yang pending (belum selesai picking)
   │
   ├─→ Kurangan Picker
   │   └─→ Report item yang kurang saat picking
   │
   └─→ Master Picker
       └─→ CRUD data picker

3. PACKING (Packing Item)
   │
   ├─→ Scan Packer
   │   ├─→ Input: No Resi
   │   ├─→ Validasi: Resi harus sudah di-pick
   │   ├─→ View: Detail items, Picker info
   │   └─→ Save: tblpacker + Log KPI
   │
   ├─→ Masalah Picker
   │   └─→ Report masalah dari picker
   │
   ├─→ Salah Ambil Special (batch resi 1 SKU/1 qty salah ambil)
   │   ├─→ Input: SKU seharusnya + SKU terambil (sekali), lalu scan resi berantai
   │   ├─→ Validasi: resi ada, 1 SKU/1 qty, SKU cocok, belum packing, sudah picker, belum dilaporkan
   │   └─→ Save: tblmasalahpicker (SALAH AMBIL) → lihat SALAH_AMBIL_SPECIAL.md
   │
   └─→ Search Packer
       └─→ Cari data packing

4. HANDOVER (Serah Terima ke Kurir)
   │
   ├─→ Scan Handover
   │   ├─→ Input: No Resi
   │   ├─→ Validasi: Resi harus sudah di-pack
   │   └─→ Save: tblhandover
   │
   └─→ Print Handover
       └─→ Cetak dokumen handover

5. SHIPPED (Status: Terkirim)
   │
   └─→ Update: Status resi → "Shipped"
```

---

## 🔄 Workflow Retur

```
┌─────────────────────────────────────────────────────────────────┐
│                        WORKFLOW RETUR                            │
└─────────────────────────────────────────────────────────────────┘

1. SCAN RETUR (Input Retur Masuk)
   │
   ├─→ Input: No Resi Retur
   ├─→ Validasi: Cek resi asal
   └─→ Save: tblretur (status: "Terima")

2. TERIMA RETUR
   │
   ├─→ Update: Status retur → "Diterima"
   └─→ Log: Tanggal terima

3. BUKA RETUR
   │
   ├─→ Update: Status retur → "Dibuka"
   └─→ Log: Tanggal buka

4. COMPLAIN (Jika Ada Masalah)
   │
   ├─→ Input: Type complain (Refund/Replacement)
   ├─→ Save: Data complain
   └─→ Update: Status complain

5. LAPORAN RETUR
   │
   ├─→ Terima Retur Report
   └─→ Buka Retur Report
```

---

## 🔄 Workflow KPI & Performance Tracking

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW KPI TRACKING                        │
└─────────────────────────────────────────────────────────────────┘

1. LOGIN
   │
   ├─→ User Login
   ├─→ Pilih: Nama PK (Komputer)
   └─→ Pilih: Status Performa
       │
       └─→ Save: tblstatusperforma (log status harian)

2. PICKING (Dengan Status Performa)
   │
   ├─→ Scan Picker
   ├─→ Pilih: Status Performa (NORMAL/FAST)
   └─→ Save: 
       ├─→ tblpicking (data picking)
       └─→ Tracking KPI (jika diperlukan)

3. PACKING (Dengan Status Performa)
   │
   ├─→ Scan Packer
   ├─→ Auto-detect: Status Performa dari resi
   └─→ Save:
       ├─→ tblpacker (data packing)
       └─→ Tracking KPI

4. KPI DASHBOARD
   │
   ├─→ Dashboard KPI (Overall)
   ├─→ Dashboard Picker
   ├─→ Dashboard Packer
   └─→ Real-time: Total scan, Target, Achievement

5. KPI REPORTS
   │
   ├─→ Filter: Tanggal, User, Status
   ├─→ View: Data KPI harian
   └─→ Export: Excel

6. TARGET KPI
   │
   ├─→ Set: Target harian per status
   ├─→ Copy: Target dari periode sebelumnya
   └─→ Update: Target per user/status
```

---

## 🔄 Workflow User & Access Management

```
┌─────────────────────────────────────────────────────────────────┐
│                 WORKFLOW USER MANAGEMENT                        │
└─────────────────────────────────────────────────────────────────┘

1. USER MANAGEMENT
   │
   ├─→ Add User
   │   ├─→ Input: Username, Password, Role, Akses
   │   └─→ Save: tbluser
   │
   ├─→ Edit User
   │   └─→ Update: Data user
   │
   ├─→ Delete User
   │   └─→ Soft delete (isactive = 0)
   │
   └─→ Generate Password
       └─→ Generate random password

2. MENU MANAGEMENT
   │
   ├─→ Add Menu
   │   ├─→ Input: Nama, URL, Parent, Icon
   │   └─→ Save: tblmenu
   │
   ├─→ Edit Menu
   │   └─→ Update: Data menu
   │
   └─→ Delete Menu
       └─→ Soft delete

3. ACCESS CONTROL
   │
   ├─→ Edit Access
   │   ├─→ Pilih: Role
   │   ├─→ Pilih: Menu yang diizinkan
   │   └─→ Save: tblakses
   │
   └─→ Validation
       └─→ Cek akses saat akses menu
```

---

## 🔄 Workflow Reporting

```
┌─────────────────────────────────────────────────────────────────┐
│                      WORKFLOW REPORTING                          │
└─────────────────────────────────────────────────────────────────┘

1. RECEIPT REPORTS
   │
   ├─→ Receipt In Process
   │   ├─→ Tab 0: Receipt baru
   │   ├─→ Tab 1: Receipt di-pick
   │   └─→ Tab 2: Receipt di-pack
   │
   ├─→ Daily Receipt Report
   │   └─→ Laporan harian resi
   │
   ├─→ Per Day Receipt Report
   │   └─→ Laporan per hari
   │
   ├─→ Receipt Report
   │   ├─→ Tab 0: Receipt summary
   │   └─→ Tab 1: Receipt detail
   │
   └─→ Shipped Receipt Report
       └─→ Laporan resi terkirim

2. PRODUCTION TEAM REPORT
   │
   ├─→ Tab 0: Summary tim
   └─→ Tab 1: Detail per user

3. SHIPPING REPORT
   │
   └─→ Laporan pengiriman per kurir

4. RETUR REPORT
   │
   ├─→ Terima Retur Report
   └─→ Buka Retur Report

5. EXPORT
   │
   └─→ Export ke Excel (PhpSpreadsheet)
```

---

## 🔄 Workflow CS (Customer Service)

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW CUSTOMER SERVICE                     │
└─────────────────────────────────────────────────────────────────┘

1. LAPORAN KURANGAN PICKER
   │
   ├─→ View: Daftar kurangan picker
   ├─→ Filter: Tanggal, Picker
   └─→ Export: Excel

2. RETUR COMPLAIN
   │
   ├─→ View: Daftar complain
   ├─→ Action: Refund/Replacement
   └─→ Update: Status complain

3. MASALAH PICKER
   │
   ├─→ View: Daftar masalah
   ├─→ Detail: Detail masalah
   └─→ Submit: Submit masalah
```

---

## 🔄 Workflow Lost Scan (Packer & Picker)

Paket yang sampai di meja HO/packer tanpa pernah di-scan picker/packer.
Rincian kondisi, skenario, dan keputusan desain: [`LOST_SCAN.md`](LOST_SCAN.md).

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW LOST SCAN                            │
└─────────────────────────────────────────────────────────────────┘

HO scan (Scan Paket NDD New)                Packer scan (Webcam)
   │                                             │
   ├─→ ditolak NOT_PACKED                        └─→ ditolak NOT_PICKED
   │   └─→ panel: pilih packer                       └─→ popup: Lapor Lost Scan Picker
   │       └─→ tbllostscanpacker PACKER                  └─→ antrean (sumber PACKER)
   │           └─→ paket ke packer → scan biasa              (kamera tidak merekam)
   │
   └─→ ditolak NOT_PICKED
       └─→ panel: pilih packer
           ├─→ tbllostscanpacker PACKER
           └─→ antrean (sumber HO)
                     │
                     ▼
        TIM PICKER → Laporan Lost Scan Picker (tab Menunggu Picker)
           └─→ Tambahkan Picker
               ├─→ tblresiambilbarang atas nama picker ('LOST SCAN PICKER', tanpa KPI)
               ├─→ tbllostscanpacker PICKER
               ├─→ antrean → SELESAI  (SELESAI_LUAR bila picking sudah ada dari SCAN COMBINED)
               └─→ notifikasi ke packer
                     │
                     ▼
        Packer scan ulang → tblpacking (video + KPI asli)
                     │
                     ▼
        HO scan ulang → tblresikeluar / tblscan_ndd

Penjaga yang memaksa urutan (sudah ada sebelumnya):
  Packer_fcd::periksa_kelayakan_packing  → tolak tanpa picking
  Scan_logistic_fcd::save_scan           → tolak tanpa picking / tanpa packing
```

---

## 🔄 Workflow Paket Cancel (Deteksi → Cek Retur → Restock)

Resi yang status cancel-nya baru masuk (upload Jubelio) setelah barangnya
keluar dari display. Rincian, tahapan rilis, dan keputusan desain:
[`PAKET_CANCEL.md`](PAKET_CANCEL.md). Tahap 1 (jejak penolakan) live 21 Sep 2026;
tahap 2–3 masih rancangan.

```
Scan ditolak "DIBATALKAN" di picker / inbound / packer / HO / lost scan
   ├─→ tblcancel_paket_tolak  (setiap penolakan: siapa, meja, jam)       [tahap 1]
   └─→ bila barang sudah keluar display: tblcancel_paket DITEMUKAN       [tahap 1]
                     │
                     ▼
        TIM RETUR → Cek Paket Cancel (scan resi, isi qty/SKU ditemukan)   [tahap 2]
           ├─→ tblcancel_paket_item
           ├─→ selisih → tblmasalahpicker (KURANG / SALAH / TIDAK AMBIL)
           └─→ tblcancel_paket DICEK
                     │
                     ▼
        Batch ke display (tblretur_display_batch, sumber CANCEL) → DIKIRIM  [tahap 3]
                     │
                     ▼
        TIM RESTOCK terima batch → DITERIMA → tblcancel_paket SELESAI       [tahap 3]

Laporan Resi Cancel: kolom "Tahap saat Cancel" + jejak penolakan
(penanda "packer lupa scan" / "picker lupa scan").
```

---

## 🔄 Workflow Salah Ambil Special (Packer)

Batch resi spesial (tepat 1 SKU / qty 1) yang salah diambil picker,
dilaporkan sekaligus tanpa mengulang modal Masalah Picker per resi.
Rincian kondisi, skenario, dan keputusan desain: [`SALAH_AMBIL_SPECIAL.md`](SALAH_AMBIL_SPECIAL.md).

```
┌─────────────────────────────────────────────────────────────────┐
│                 WORKFLOW SALAH AMBIL SPECIAL                     │
└─────────────────────────────────────────────────────────────────┘

TIM PACKER → Salah Ambil Special
   │
   ├─→ Isi SKU seharusnya + SKU terambil (live search ke tblsku)
   │   └─→ Kunci & Mulai Scan → cek-sku: terisi, beda, ada di tblsku
   │
   ├─→ Scan resi (berantai, diantre satu per satu)
   │   └─→ scan-resi: 7 validasi berurutan
   │       ├─→ gagal  → baris MERAH + alasan, bunyi gagal, tidak ada tulisan
   │       └─→ lolos  → INSERT tblmasalahpicker (tipe 4 SALAH AMBIL, status 0)
   │                    baris HIJAU, bunyi sukses
   │
   └─→ Ganti SKU → buka kunci, tabel sesi kosong (data tetap di DB)
                     │
                     ▼
TIM CS → Daftar Masalah Picker New (tanpa perubahan)
   └─→ Proses & Cetak → slip per picker "SALAH AMBIL (terambil <sku_salah>)"
                     │
                     ▼
Picker menukar barang → packer scan biasa

Validasi scan-resi (berhenti di kegagalan pertama):
  #1 pasangan SKU dicek ulang  #2 resi ada  #3 tepat 1 SKU / qty 1
  #4 SKU resi = SKU seharusnya #5 belum packing  #6 sudah di-picker
  #7 belum pernah dilaporkan (status apa pun) — #7 + INSERT satu transaksi, resi FOR UPDATE
```

---

## 📊 Status Flow Diagram

```
RECEIPT STATUS FLOW:
┌─────────┐
│  NEW    │ → Receipt baru diinput
└────┬────┘
     │
     ▼
┌─────────┐
│ PICKED  │ → Sudah di-pick oleh picker
└────┬────┘
     │
     ▼
┌─────────┐
│ PACKED  │ → Sudah di-pack oleh packer
└────┬────┘
     │
     ▼
┌─────────┐
│ HANDOVER│ → Sudah di-handover ke kurir
└────┬────┘
     │
     ▼
┌─────────┐
│ SHIPPED │ → Sudah terkirim
└─────────┘

RETUR STATUS FLOW:
┌─────────┐
│ TERIMA  │ → Retur diterima
└────┬────┘
     │
     ▼
┌─────────┐
│  BUKA   │ → Retur dibuka untuk proses
└────┬────┘
     │
     ▼
┌─────────┐
│ COMPLAIN│ → Ada complain (optional)
└─────────┘
```

---

## 🔐 Authentication Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION FLOW                           │
└─────────────────────────────────────────────────────────────────┘

1. USER ACCESS PAGE
   │
   ├─→ Check: Session user exists?
   │   ├─→ YES → Continue
   │   └─→ NO → Redirect to Login
   │
   └─→ MY_Controller::__construct()
       └─→ Validate session

2. LOGIN PROCESS
   │
   ├─→ Input: Username, Password
   ├─→ Input: Nama PK (Komputer)
   ├─→ Input: Status Performa
   │
   ├─→ Validate: Username & Password (MD5)
   │   ├─→ Valid → Continue
   │   └─→ Invalid → Error message
   │
   ├─→ Get: Menu access berdasarkan role
   │
   ├─→ Build: Menu tree
   │
   ├─→ Save: Status performa ke database
   │
   └─→ Create: Session
       ├─→ User data
       ├─→ Menu tree
       ├─→ Nama PK
       └─→ Status Performa

3. MENU ACCESS VALIDATION
   │
   ├─→ Check: User role
   ├─→ Check: Menu access (tblakses)
   └─→ Allow/Deny access
```

---

## 📱 API Endpoints (Routes)

### Receipt
- `POST /receipt/save-receipt` - Simpan resi baru
- `POST /receipt/detail-receipt` - Detail resi
- `GET /receipt/get-list-receipt-data` - List resi (DataTables)

### Picker
- `POST /picker/save-scan-picker` - Simpan scan picker
- `GET /picker/get-search-picker-data` - Data search picker

### Packer
- `POST /packer/save-packer` - Simpan packing
- `GET /packer/get-scan-packer-data/:noresi` - Data scan packer

### Handover
- `POST /handover/save-handover` - Simpan handover

### Salah Ambil Special (lihat `SALAH_AMBIL_SPECIAL.md` §9)
- `GET /salah-ambil-special/cari-sku?term=` - Saran SKU (live search)
- `POST /salah-ambil-special/cek-sku` - Validasi pasangan SKU (kunci)
- `POST /salah-ambil-special/scan-resi` - Satu scan resi → tblmasalahpicker

### Lost Scan (lihat `LOST_SCAN.md` §9)
- `POST /scan-paket-ndd-new/save` · `/cek-lost-scan` · `/simpan-lost-scan` · `/lapor-picker`
- `POST /packer/lapor-lost-scan-picker`
- `POST /lost-scan-picker/get-data` · `/tambah-picker`

### Retur
- `POST /retur/save-retur` - Simpan retur
- `POST /retur/save-buka-retur` - Buka retur

### Report
- `GET /report/get-receipt-in-process-data-tab0` - Data receipt in process
- `GET /report/export-to-excel-*` - Export Excel

### KPI
- `GET /kpi/dashboard` - Dashboard KPI
- `GET /kpi_reports/get-kpi-data` - Data KPI
- `POST /target_kpi/save-targets` - Simpan target KPI

---

**Diagram ini membantu memahami alur kerja sistem IRESIS secara visual.**

