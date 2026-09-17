# Auto Upload Retur Jubelio — Dokumentasi Teknis

## Gambaran Umum

Mengotomatiskan proses harian di menu **TIM ACCOUNTING → Upload Retur Jubelio**:
mengunduh laporan **"Daftar Retur Penjualan"** dari Jubelio (rentang **1 Januari
s/d hari ini**), lalu mengunggahnya ke SIRESI. Menggantikan langkah manual
(download Excel → upload lewat tombol di web).

---

## Kenapa pakai browser, bukan API murni?

Saat investigasi (via `scripts/arsip/sniff_jubelio.py`) ditemukan:

| Bagian | Bisa via API? | Keterangan |
|--------|---------------|------------|
| Login | ✅ | `POST open.jubelio.com/core-api/login` → token JWT |
| Ambil URL laporan | ✅ | `GET open.jubelio.com/core-api/reports/customer-retur/date-range/` → URL viewer (rate-limited 429) |
| **Generate/Download Excel** | ❌ | File dibuat oleh **Telerik Report Server** (`report-prod.jubelio.com`). Resolve laporan lewat HTTP murni selalu `404 "cannot be resolved"` — template ada di server internal Jubelio (`\\172.16.7.7\report-server-repo\...`) dan hanya ter-register lewat alur klik **Cetak** di aplikasi. |

Kesimpulan: download **hanya andal lewat otomasi browser** (klik Cetak), sama
seperti `scripts/auto_upload_resi.py`.

---

## Alur Proses

```
Task Scheduler
    └─► run_upload_retur_jubelio.bat
            └─► scripts/auto_upload_retur_jubelio.py
                    ├─► Login Jubelio (Chrome profile)
                    ├─► Buka Penjualan > Laporan > kartu "Daftar Retur Penjualan"
                    ├─► Set tanggal: 1 Januari s/d hari ini
                    ├─► Klik Cetak → tunggu file .xlsx ter-download
                    └─► Upload ke cron/auto_upload_retur_jubelio (produksi) → DB
```

---

## Komponen

### 1. Script Python — `scripts/auto_upload_retur_jubelio.py`

| Parameter | Nilai |
|-----------|-------|
| Login Jubelio | `beverra2019@gmail.com` |
| URL Jubelio | `https://v2.jubelio.com/sales/reports/` |
| Chrome Profile | `C:\MP\chrome_resi_profile` (sama dgn auto_upload_resi) |
| Download dir | `%USERPROFILE%\Downloads` |
| Upload URL | `<BASE_URL>cron/auto_upload_retur_jubelio` (BASE_URL = produksi, via `--base-url`) |
| Rentang tanggal | 1 Januari tahun berjalan s/d hari ini |

**PENTING sebelum jalan pertama kali:**
1. Isi domain produksi. Dua cara:
   - Edit konstanta `IRESIS_BASE_URL` di script, **atau**
   - Jalankan dengan `--base-url`, contoh:
     ```
     python scripts/auto_upload_retur_jubelio.py --base-url https://siresi-anda.com/index.php/
     ```
2. Verifikasi teks kartu laporan retur. Script mencoba kandidat di
   `RETUR_CARD_TEXTS`. Jika kartu tidak ketemu, jalankan sekali dengan
   pengawasan lalu sesuaikan daftar teks tersebut dengan yang tampil di UI.

### 2. Endpoint PHP — `application/controllers/Cron.php` → `auto_upload_retur_jubelio()`

- Menerima file `jubelioFile` (multipart upload)
- Parsing xlsx via `retur_fcd->parse_jubelio_spreadsheet()` (logika **sama** dgn
  upload manual `Retur::upload_jubelio`)
- Insert via `retur_fcd->insert_jubelio_batch($rows, $batch_id, null)`
- Return JSON `{"success": true/false, "message": "...", "inserted":.., "updated":.., "matched":..}`
- Diamankan token `?token=<cron_token>` (dicek di constructor `Cron`)

### 3. Model — `application/models/Retur_fcd.php`

- `parse_jubelio_spreadsheet($tmp)` — baca header Jubelio (`tracking_number`,
  `salesorder_no`, `SKU`, dst) → array baris. Dipakai bareng oleh upload manual
  & otomasi.
- `insert_jubelio_batch()` — upsert ke `tblreturjubelio`, tandai kecocokan
  dengan `tblprintresi` / `tblresiretur`.

### 4. BAT Runner — `C:\MP\run_upload_retur_jubelio.bat` (buat manual)

```bat
@echo off
cd /d C:\xampp\htdocs\new-iresis\scripts
python auto_upload_retur_jubelio.py --base-url https://siresi-anda.com/index.php/ >> C:\MP\log_upload_retur_jubelio.txt 2>&1
```

---

## Jadwal (Windows Task Scheduler)

Buat task harian (sekali sehari, setelah data retur Jubelio tersedia). Contoh
membuat lewat CLI (jalankan sebagai Administrator):
```
schtasks /create /tn "IRESIS Upload Retur Jubelio" /tr "C:\MP\run_upload_retur_jubelio.bat" /sc daily /st 08:30
```

---

## Token Autentikasi

```
<lihat cron_token di application/config/secrets.php>
```
Dibaca constructor `Cron` lewat `iresis_secret('cron_token')`
(token yang sama dengan `auto_upload_resi`).

---

## Troubleshooting

### Kartu "Daftar Retur Penjualan" tidak ditemukan
- UI Jubelio berubah / teks kartu beda. Sesuaikan `RETUR_CARD_TEXTS` di script.

### Download timeout (5 menit)
- Rentang 1 Jan–hari ini bisa besar → generate lama. Naikkan `timeout` di
  `wait_for_new_file` bila perlu, atau cek halaman Jubelio manual.

### Upload gagal — respons bukan JSON / HTTP 403
- 403 = token salah / endpoint diakses tanpa `?token=`.
- Pastikan `--base-url` benar dan diakhiri `/index.php/`.

### Data duplikat / kolom tidak terbaca
- Jubelio mengubah header xlsx. Bandingkan dengan mapping di
  `Retur_fcd::parse_jubelio_spreadsheet()`.

---

## File Terkait

| File | Fungsi |
|------|--------|
| `scripts/auto_upload_retur_jubelio.py` | Script Python utama |
| `scripts/arsip/sniff_jubelio.py` | Perekam jaringan (dipakai saat reverse-engineer API; sudah diarsipkan) |
| `application/controllers/Cron.php` | Endpoint `auto_upload_retur_jubelio()` |
| `application/controllers/Retur.php` | Upload manual `upload_jubelio()` |
| `application/models/Retur_fcd.php` | `parse_jubelio_spreadsheet()` + `insert_jubelio_batch()` |
| `application/config/secrets.php` | Token cron (`cron_token`) |
