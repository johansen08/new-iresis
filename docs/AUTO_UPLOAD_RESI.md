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
- Baca xlsx lewat `application/libraries/Xlsx_cepat.php` (lihat bagian
  "Pembaca xlsx" di bawah), fallback otomatis ke PhpSpreadsheet untuk `.xls`
- Panggil `receipt_fcd->insert_receipt($dataRaw, null)`
- Return JSON `{"success": true/false, "message": "..."}`
- Memory limit: 3072M, no execution timeout

### 3. Model — `application/models/Receipt_fcd.php`

Fungsi `insert_receipt($rows, $user_id, $lapor = null)` memproses data xlsx baris per baris:
- Skip header dan baris kosong
- Upsert data ke tabel `tblprintresi` berdasarkan nomor resi: resi baru
  di-`insert_batch` 500/query, resi lama yang statusnya berubah
  di-`update_batch` 200/query (dulu satu `UPDATE` per resi)
- Mapping kolom xlsx → kolom database
- `db_debug` dimatikan selama impor supaya error query tidak mencetak halaman
  HTML CI; pesan error DB dilampirkan ke hasil "Error ..."
- Parameter ketiga `$lapor` (opsional) adalah callback
  `fn(string $tahap, string $pesan, ?int $persen)` yang dipanggil di tiap
  tahap — dipakai menu Upload Resi untuk menulis berkas progres

### Pembaca xlsx — `application/libraries/Xlsx_cepat.php`

PhpSpreadsheet membuat objek `Cell` untuk tiap sel, sehingga file 20 ribu
baris butuh ~20 detik dan ~270 MB hanya untuk dibaca. `Xlsx_cepat` membaca
`xl/worksheets/sheetN.xml` + `sharedStrings.xml` secara streaming (ZipArchive +
XMLReader) dalam ~2,5 detik / ~50 MB, dan hasilnya **identik** dengan
`Worksheet::toArray(null, true, true, true)` untuk kolom A..W (diuji 20.001
baris × 23 kolom, 0 perbedaan). Selalu lembar pertama, sama seperti
PhpSpreadsheet dalam mode `readDataOnly`.

```php
$this->load->library('xlsx_cepat');
$rows = $this->xlsx_cepat->baca_dengan_fallback($path, 'W', $jalur); // $jalur = 'cepat' | 'phpspreadsheet'
```

### Menu Upload Resi (manual) — `Receipt::upload_receipt_action()`

Menu **Tim Resi → Upload Resi** memakai model yang sama. Alur khususnya:

1. Browser mengirim `token` acak bersama file. Bar progres 0–10 % = transfer
   file (`xhr.upload.progress`), sisanya tahap server.
2. Server memanggil `session_write_close()` sebelum mulai membaca file.
   Driver session `files` mengunci berkas session selama request, jadi tanpa
   ini **semua** request lain dari user yang sama (termasuk polling progres)
   menggantung sampai impor selesai.
3. Tahap impor ditulis ke `application/cache/upload_resi/<token>.json`
   (`status`: `proses` | `selesai` | `gagal`, `tahap`, `pesan`, `persen`,
   `hasil`). Browser mem-poll `receipt/upload-receipt-progress?token=…` tiap
   1,5 detik. Berkas > 1 hari dibuang saat upload berikutnya.
4. `ignore_user_abort(true)` + `register_shutdown_function`: kalau koneksi
   browser putus, impor tetap selesai dan hasilnya tercatat di berkas progres;
   kalau PHP mati fatal (kehabisan memori), berkas ditandai `gagal` supaya UI
   tidak menunggu selamanya.
5. Respons akhir lewat `make_ajax_response()` (HTTP 200, `code` 201 sukses /
   400–500 gagal). Jika AJAX-nya gagal (timeout/koneksi), UI memakai berkas
   progres sebagai sumber kebenaran sebelum menyatakan gagal.

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
| `tanggal_pengiriman` | `awb_created_date` (detail/item) | UTC → WIB. Nilai lama di DB (unggahan xlsx Jan–Sep 2026) hari/bulannya tertukar dan hari > 12 hilang jadi NULL — akar masalahnya parser teks tanggal di `Receipt_fcd::insert_receipt()` (lihat §Bug tanggal hari/bulan di bawah), sudah diperbaiki 22 Sep 2026. Jalur API ini sejak awal benar karena mengirim `Y-m-d`. |
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

---

## Bug tanggal hari/bulan tertukar (ditemukan 22 Sep 2026)

**Gejala.** Di `tblprintresi`, `tanggal_pengiriman` 36 % berada di masa depan
(175.040 dari 485.388 terisi), `tanggal_selesai` 53 %, `tanggal_retur` 48 %;
nilainya menumpuk di hari 1–12 tiap bulan (resi dicetak 12 Sep tersimpan
`2026-12-09`). Sekaligus ~348.000 resi `SHIPPED` punya `tanggal_pengiriman`
NULL. `tanggal_pesan` dan `tanggal_bataskirim` selalu benar.

**Akar masalah.** Sejak unggahan 1 Jan 2026 kolom J/L/U (tanggal pengiriman/
selesai/retur) di laporan Jubelio berupa **teks** `hari/bulan/tahun`, bukan
angka serial seperti kolom D/H. `Receipt_fcd::insert_receipt()` mencoba
`createFromFormat('d/m/Y H:i:s')`, gagal (bentuk jamnya tidak persis), lalu
jatuh ke `new DateTime("12/09/2026 …")` — PHP membaca garis miring ala
Amerika (bulan/hari): 12/09 → 9 Desember, dan **hari > 12 melempar exception
→ NULL**. Data Okt–Des 2025 benar (format ekspor saat itu masih terbaca).

**Perbaikan (22 Sep 2026, `fix(receipt)`).** Parser diganti: teks bertanda
`/ - .` dibaca hari-dulu lewat regex (bulan-dulu hanya bila terbukti dari isi
file: ada angka kedua > 12), jam menerima `H:i:s`, `H:i`, `H.i.s`, AM/PM dan
pecahan detik, angka serial tetap seperti semula, jalur API (`Y-m-d`) tidak
berubah. Tiap unggahan menulis satu baris log (`application/logs/`) berisi
urutan yang terdeteksi, jumlah teks tanggal yang gagal, dan contoh sel mentah
`D E J K L M U V` — **periksa log setelah unggahan pertama** untuk memastikan
format Jubelio memang yang diduga.

**Data lama.** Nilai NULL tidak bisa dipulihkan dari DB (hilang saat impor;
perlu impor ulang dari Jubelio, dan jalur update hanya menulis bila
`status_pesanan` berubah). Nilai yang tertukar dikoreksi dengan skrip
`dev_tools/sql/20260922_koreksi_tanggal_hari_bulan_tblprintresi.sql`
(+ `_rollback.sql`): backup `CREATE TABLE … SELECT` per DB, tukar hari↔bulan
hanya bila hasilnya ≤ NOW() dan ≥ `tanggal_pesan`/`tanggal_printresi`, prod
dulu baru arsip (sinkron malam tidak membawa UPDATE tanpa `modified_at`).
