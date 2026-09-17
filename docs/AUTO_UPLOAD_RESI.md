# Auto Upload Resi — Dokumentasi Teknis

## Gambaran Umum

Sistem auto upload resi mengambil data penjualan dari Jubelio secara otomatis, lalu mengimpornya ke database iresis. Proses ini berjalan via Windows Task Scheduler setiap hari.

Ada 2 versi:
- **v1 (browser, lama)** — `scripts/auto_upload_resi.py`, download xlsx via Chrome/DrissionPage.
- **v2 (API, baru)** — `scripts/auto_upload_resi_api.py`, langsung panggil Jubelio core-api tanpa browser. Jauh lebih hemat RAM (tanpa Chrome) dan lebih cepat. Lihat bagian "Versi API" di bawah.

---

## Alur Proses (v1 — browser)

```
Task Scheduler
    └─► run_upload_resi.bat
            └─► scripts/auto_upload_resi.py
                    ├─► Login Jubelio (Chrome profile)
                    ├─► Download Laporan Penjualan (Faktur)   → .xlsx
                    ├─► Upload ke cron/auto_upload_resi        → DB
                    ├─► Download Laporan Penjualan (Pesanan)  → .xlsx
                    └─► Upload ke cron/auto_upload_resi        → DB
```

---

## Komponen

### 1. Script Python — `scripts/auto_upload_resi.py`

| Parameter | Nilai |
|-----------|-------|
| Login Jubelio | `beverra2019@gmail.com` |
| URL Jubelio | `https://v2.jubelio.com/sales/reports/` |
| Chrome Profile | `C:\MP\chrome_resi_profile` |
| Download dir | `%USERPROFILE%\Downloads` |
| Upload URL | `http://localhost:8080/new-iresis/index.php/cron/auto_upload_resi` |
| Timeout download | 300 detik (5 menit) |
| Timeout upload | 600 detik (10 menit) |

**Yang dilakukan:**
1. Buka Chrome dengan profil khusus (sudah login Jubelio)
2. Jika belum login, otomatis login dengan kredensial di atas
3. Navigasi ke halaman Laporan Penjualan
4. Klik card "Laporan daftar transaksi penjualan"
5. Set tanggal range: **H-3 s/d hari ini**
6. Download xlsx untuk tipe **Faktur**, lalu **Pesanan**
7. Upload masing-masing file ke endpoint PHP via multipart POST
8. Hapus file xlsx setelah upload selesai

### 2. Endpoint PHP — `application/controllers/Cron.php` → `auto_upload_resi()`

- Menerima file `receiptFile` (multipart upload)
- Baca xlsx menggunakan PhpSpreadsheet
- Panggil `receipt_fcd->insert_receipt($dataRaw, null)`
- Return JSON `{"success": true/false, "message": "..."}`
- Memory limit: 3072M, no execution timeout

### 3. Model — `application/models/Receipt_fcd.php`

Fungsi `insert_receipt()` memproses data xlsx baris per baris:
- Skip header dan baris kosong
- Upsert data ke tabel `tblprintresi` berdasarkan nomor resi
- Mapping kolom xlsx → kolom database

### 4. BAT Runner — `C:\MP\run_upload_resi.bat`

```bat
@echo off
cd /d C:\xampp\htdocs\new-iresis\scripts
python auto_upload_resi.py >> C:\MP\log_upload_resi.txt 2>&1
```

---

## Jadwal (Windows Task Scheduler)

Cek jadwal saat ini:
```
schtasks /query /tn "IRESIS*Resi*" /fo LIST
```

Upload resi berjalan sekali sehari, biasanya pagi hari setelah data Jubelio tersedia.

---

## Log

| File | Isi |
|------|-----|
| `C:\MP\log_upload_resi.txt` | Output Python script (tiap run) |
| `application/logs/log-YYYY-MM-DD.php` | Log CI (termasuk hasil upload dari PHP) |

Contoh log sukses:
```
[08:05:01] === AUTO UPLOAD RESI ===
[08:05:04] Login OK: https://v2.jubelio.com/dashboard
[08:05:12] Download Laporan Penjualan (Faktur)...
[08:05:47]   Download OK: laporan_penjualan_faktur_...xlsx
[08:06:15] Upload OK: Berhasil insert 320 resi, skip 45 duplikat
[08:06:17] Download Laporan Penjualan (Pesanan)...
[08:06:58]   Download OK: laporan_penjualan_pesanan_...xlsx
[08:07:30] Upload OK: Berhasil insert 12 resi, skip 308 duplikat
[08:07:30] === SELESAI ===
```

---

## Troubleshooting

### Download timeout (5 menit terlewat)
- Cek koneksi internet
- Buka manual `https://v2.jubelio.com/sales/reports/` untuk verifikasi halaman bisa diakses
- Cek apakah ada update UI Jubelio yang mengubah struktur halaman

### Upload gagal — "File tidak ditemukan atau error upload"
- Pastikan Apache (XAMPP) berjalan di port 8080
- Cek `php_fileinfo` extension aktif di `php.ini`
- Pastikan folder `tmp` PHP bisa ditulis

### Upload gagal — "Expecting value: line 1 column 1"
- PHP mengembalikan halaman HTML error, bukan JSON
- Cek log Apache: `C:\xampp\apache\logs\error.log`
- Kemungkinan PHP error / memory exhausted saat proses xlsx besar

### Session Jubelio expired di Chrome profile
- Script akan otomatis login ulang jika URL mengarah ke `/auth/login`
- Jika tetap gagal, hapus profil Chrome: `C:\MP\chrome_resi_profile` dan jalankan ulang (login manual sekali)

### Data tidak masuk / duplikat semua
- Jubelio mungkin sudah mengubah format kolom xlsx
- Bandingkan header xlsx terbaru dengan mapping di `Receipt_fcd.php`
- Download xlsx manual dari Jubelio dan cek isi kolomnya

---

## Versi API (v2) — `scripts/auto_upload_resi_api.py`

Menggantikan browser/Chrome dengan panggilan HTTP langsung ke `open.jubelio.com/core-api` (API yang sama dipakai website Jubelio sendiri saat browsing manual). Tidak butuh Chrome sama sekali → hemat RAM signifikan, dan jauh lebih cepat per-panggilan (~0.15-0.2 detik).

### Kenapa bukan Data API resmi (`api2.jubelio.com`)?

Sudah dicoba — endpoint tersebut throttle sangat ketat (429 terus meski sudah retry+backoff berkali-kali) untuk akses login biasa. `core-api` (dipakai web app) jauh lebih longgar, meski tetap ada rate-limit lunak (lihat di bawah).

### Alur

```
Task Scheduler
    └─► run_upload_resi_api.bat
            └─► scripts/auto_upload_resi_api.py
                    ├─► Login core-api (open.jubelio.com/core-api/login)
                    ├─► GET sales/v2/orders/  (list, paginated, H-3 s/d hari ini)
                    ├─► POST cron/check_resi_status  → skip yang sudah COMPLETED
                    ├─► GET sales/orders/{id}  per order tersisa (isi SKU/qty)
                    │       (jeda 0.5s + retry backoff kalau kena 429)
                    └─► POST cron/auto_upload_resi_api  (JSON, bukan xlsx)  → DB
```

### Mapping Field (Jubelio API → iresis)

Sudah divalidasi silang terhadap data asli di `tblprintresi` (bukan tebakan):

| Kolom iresis | Sumber API | Keterangan |
|---|---|---|
| `noresi` | `tracking_no` (list) | Cocok persis dengan format resi asli (mis. `SPXID...`, `GTL...`) |
| `no_pesanan` | `salesorder_no` (list) | Cocok persis (mis. `SP-260703PCCWYXWH`) |
| `status_pesanan` | `internal_status` (list) | Vocab sama persis: SHIPPED/COMPLETED/PROCESSING/CANCELED/RETURNED/REQUEST_CANCEL |
| `marketplace` | `channel_name` (list) | mis. "SHOPEE", "Shop \| Tokopedia" — cocok dengan normalisasi di `insert_receipt()` |
| `kurir` (raw, sebelum parsing) | `shipper` (list) | mis. "J&T Express Standard" → cocok pola `courier_aliases` |
| `tanggal_pesan` | `transaction_date` (list) | UTC → dikonversi ke WIB (+7 jam) |
| `tanggal_bataskirim` | `due_date` (detail per order) | UTC → WIB. **Dikonfirmasi cocok 100%** dengan nilai asli di DB |
| `tanggal_selesai` | `completed_date` (detail) | UTC → WIB. Null selama belum COMPLETED |
| `tanggal_pengiriman` | `awb_created_date` (detail/item) | UTC → WIB. **Belum bisa divalidasi penuh** — nilai lama di DB untuk kolom ini kelihatan punya bug tersendiri (tanggal bulan/hari tertukar), jadi tidak ada pembanding yang bisa dipercaya. Tidak dipakai laporan manapun, risiko rendah. |
| `sku` | `items[].item_code` (detail) | Cocok persis (mis. `BM-AKS28-2`) |
| `jumlah` | `items[].qty` (detail) | |
| `no_rak` | *(dikosongkan)* | Lokasi rak asli ada di `tblsku.no_rak`, bukan dari Jubelio — sistem sudah pakai `COALESCE` ke `tblsku` saat tampil ke packer |
| `nomorpicklist` | *(dikosongkan)* | Nomor batch internal iresis, bukan data Jubelio |
| `tanggal_retur` | *(tidak tersedia)* | Endpoint order ini tidak punya info retur; ditangani proses retur terpisah (`auto_upload_retur_jubelio.py`) |
| `status_wms` | `wms_status` (list/detail) | **Kolom lama yang SEBELUMNYA selalu kosong** (baik pipeline xlsx maupun API awal tidak pernah mengisinya) — dipakai `cek_paket_rts.php` untuk deteksi "Ready to Ship". Empiris: nilainya biasanya sama dengan `internal_status` untuk order yang sudah final; nilai "Ready to Ship" kemungkinan cuma muncul lewat endpoint khusus `wms/sales/v2/orders/ready-to-ship/` atau jendela waktu sempit sebelum AWB dibuat. Hanya terisi untuk resi BARU / status berubah — resi lama TIDAK di-backfill otomatis. |

### Rate Limit `core-api`

Empiris (lihat riwayat percobaan): dengan jeda 0.5 detik antar panggilan detail, ~10% panggilan kena 429 sesekali (bukan blokir permanen) — cukup di-retry singkat (`DETAIL_MAX_RETRY = 3`, backoff 5/10/15 detik). Endpoint list juga dibungkus retry serupa.

### Optimasi: skip resi yang statusnya tidak berubah

Endpoint baru `cron/check_resi_status` (terima daftar noresi, balas `status_pesanan` SAAT INI di iresis untuk tiap noresi) dipanggil SEBELUM ambil detail per order. Resi di-skip (tidak fetch detail Jubelio) kalau status di iresis **sama** dengan `internal_status` Jubelio sekarang.

Penting: ini BUKAN sekadar cek "sudah COMPLETED" — mayoritas resi di window H-3 berstatus **SHIPPED** (final di sisi Jubelio, tapi bukan `COMPLETED` di iresis). Awalnya dibangun dengan filter "skip hanya kalau COMPLETED" dan hasilnya nyaris tidak membantu (hampir semua resi tetap kena fetch ulang tiap hari, run pertama >1 jam belum selesai). Setelah diganti jadi perbandingan status penuh (map noresi→status), resi yang statusnya tidak berubah (mayoritas kasus harian) langsung di-skip tanpa panggil Jubelio sama sekali.

### Endpoint PHP baru (ringan, tanpa PhpSpreadsheet)

- `cron/auto_upload_resi_api` — terima JSON array baris (key `A`..`V`, sama seperti hasil parsing xlsx), langsung panggil `Receipt_fcd::insert_receipt()` yang sudah ada (logic upsert/skip-completed tidak diubah sama sekali).
- `cron/check_resi_status` — terima `{"noresi": [...]}`, balas noresi mana yang sudah `COMPLETED`.

### Kredensial

Kredensial Jubelio (dipakai semua script `jubelio_*` dan `auto_upload_resi*.py`) disimpan di `scripts/jubelio_credentials.py` — **file ini di-gitignore, jangan pernah commit**.

### Cara pindah dari v1 ke v2

1. Nonaktifkan/hapus scheduled task lama yang menjalankan `run_upload_resi.bat`.
2. Daftarkan scheduled task baru menjalankan `C:\MP\run_upload_resi_api.bat` di jam yang sama.
3. Pantau `C:\MP\log_upload_resi_api.txt` beberapa hari pertama untuk pastikan hasil (`Total Data Terinput`/`Diupdate`) sebanding dengan histori v1.
4. v1 (`auto_upload_resi.py`) tetap disimpan sebagai fallback kalau `core-api` bermasalah/berubah.

---

## Token Autentikasi

Endpoint cron diamankan dengan token:
```
<lihat cron_token di application/config/secrets.php>
```
Dibaca constructor `Cron` lewat `iresis_secret('cron_token')`.

---

## File Terkait

| File | Fungsi |
|------|--------|
| `scripts/auto_upload_resi.py` | Script Python v1 (browser/Chrome) |
| `scripts/auto_upload_resi_api.py` | Script Python v2 (pure API, tanpa browser) |
| `scripts/jubelio_credentials.py` | Kredensial Jubelio (gitignored) |
| `C:\MP\run_upload_resi.bat` | BAT launcher v1 |
| `C:\MP\run_upload_resi_api.bat` | BAT launcher v2 |
| `application/controllers/Cron.php` | Endpoint `auto_upload_resi()`, `auto_upload_resi_api()`, `check_resi_status()` |
| `application/models/Receipt_fcd.php` | Logic insert ke DB (`insert_receipt`, `get_completed_noresi`) |
| `application/config/secrets.php` | Token cron (`cron_token`) |
| `C:\MP\log_upload_resi.txt` / `log_upload_resi_api.txt` | Log output harian |
