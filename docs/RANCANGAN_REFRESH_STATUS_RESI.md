# Rancangan: refresh status resi berkala (status final vs non-final)

> **Status: DIIMPLEMENTASI 4 Nov 2026 (belum dijadwalkan).** Rancangan dari temuan
> pengecekan data resi; kode di bagian 9. Putaran pertama di produksi belum dijalankan.

## 1. Masalah

Sinkron Jubelio (`scripts/auto_upload_resi_api.py`, `DAYS_BACK = 3`) hanya menarik
pesanan **H-3 s/d hari ini**. Resi yang lebih tua tidak pernah dicek ulang, jadi
status `status_pesanan` membeku di nilai terakhir yang sempat terbaca.

Sebaran status per umur resi (`tanggal_printresi`), dicek 4 Nov 2026:

| Status | ≤3 hari | 4–30 hari | >30 hari |
|---|---|---|---|
| SHIPPED | 29.459 | 226.107 | 304.417 |
| COMPLETED | 68 | 2.824 | 15.551 |
| PROCESSING | 7.457 | 1 | 366 |
| CANCELED | 101 | 1.071 | 1.174 |
| RETURNED | 25 | 386 | 1.831 |
| REQUEST_CANCEL | 6 | 0 | 13 |

Dampak:
- 304 ribu resi `SHIPPED` >30 hari vs hanya 15 ribu `COMPLETED`: sebagian besar
  hampir pasti sudah selesai di marketplace tapi tidak pernah di-update.
- **Pencairan Dana** (`docs/PENCAIRAN_DANA.md`) mengandalkan `COMPLETED`, jadi
  angka "sudah/belum cair" ikut bias.
- 367 resi `PROCESSING` berumur >10 hari (sebagian bertanggal `0000-00-00`).

## 2. Fakta kode yang menentukan rancangan

- `Receipt_fcd::insert_receipt()` **melewati (skip) resi yang sudah `COMPLETED`**
  — jadi `COMPLETED → RETURNED` tidak pernah tertulis lewat jalur ini. Retur
  ditangani proses terpisah (`auto_upload_retur_jubelio.py`, `tanggal_retur`).
  Artinya `COMPLETED` **sudah diperlakukan final** oleh kode sekarang.
- `cron/check_resi_status` + `Receipt_fcd::get_status_map()` sudah membalas
  `noresi → status` di IRESIS; skrip membandingkannya dengan `internal_status`
  Jubelio dan melewati resi yang tidak berubah. Penghematan panggilan sudah ada.
- Rate limit `core-api`: ±10% panggilan detail kena 429; jeda 0,5 dtk + retry
  3× (5/10/15 dtk).

## 3. Status final vs non-final

| Status | Final? | Dicek ulang? |
|---|---|---|
| `CANCELED` | Ya | Tidak |
| `COMPLETED` | Ya (sudah di kode) | Tidak |
| `RETURNED` | Ya | Tidak (proses retur terpisah) |
| `SHIPPED` | Tidak | Ya, berjenjang (§4) |
| `PROCESSING` | Tidak | Ya, berjenjang (§4) |
| `REQUEST_CANCEL` | Tidak | Ya, berjenjang (§4) |
| NULL / kosong | Tidak | Ya, berjenjang (§4) |

Perlu dikonfirmasi pemilik proses: apakah `CANCELED` yang berasal dari
`REQUEST_CANCEL` bisa dibatalkan balik (jarang; kalau ya, beri masa tunggu 7 hari
sebelum dianggap final).

## 4. Jadwal cek ulang (hanya untuk status non-final)

| Umur resi | Frekuensi | Catatan |
|---|---|---|
| 0–3 hari | tiap run sinkron H-3 (sekarang) | tidak berubah |
| 4–14 hari | harian | |
| 15–60 hari | mingguan | |
| >60 hari, masih non-final | **sekali**, lalu ditandai `status_final_dicek` | tidak dicek lagi |

Penanda yang diusulkan: kolom baru di `tblprintresi`, mis. `status_dicek_at`
(datetime NULL) — pola sama seperti `rts_checked_at`. Pemilihan kandidat:

```sql
-- BACA SAJA — contoh pemilihan kandidat harian
SELECT noresi FROM tblprintresi
WHERE (status_pesanan IS NULL OR status_pesanan NOT IN ('CANCELED','COMPLETED','RETURNED'))
  AND tanggal_printresi BETWEEN CURDATE() - INTERVAL 14 DAY AND CURDATE() - INTERVAL 4 DAY
  AND (status_dicek_at IS NULL OR status_dicek_at < CURDATE())
LIMIT 3000;
```

Resi bertanggal `0000-00-00` dipilih lewat `id_printresi`, bukan tanggal.

## 5. Cara kerja yang diusulkan

1. Endpoint baru `cron/kandidat_refresh_status` (token cron): balas ≤N noresi
   sesuai jadwal §4 (N dibatasi agar muat rate limit).
2. Skrip baru `scripts/refresh_status_resi.py`: ambil kandidat → panggil Jubelio
   per order (jeda 0,5 dtk) → POST ke jalur upsert yang sudah ada
   (`cron/auto_upload_resi_api`) — **tidak** menulis status lewat jalur baru, supaya
   aturan skip `COMPLETED` dan validasi tetap satu pintu.
3. Setelah tiap resi dicek (berubah atau tidak), isi `status_dicek_at = NOW()`.
4. Task Scheduler: harian, di luar jam sibuk (mis. 02.00, setelah arsip 01.00).
5. Log ke `logs/` seperti job cron lain; ringkasan jumlah dicek / berubah / 429.

## 6. Backlog awal (304 ribu SHIPPED lama)

Jangan sekali jalan. Proses bertahap, mis. 3.000 resi/malam, terbaru dulu
(resi muda lebih mungkin sudah berubah). Perkiraan >100 malam; boleh dipercepat
kalau rate limit longgar. Mulai dengan uji **100 resi** dan bandingkan hasilnya
dengan Jubelio sebelum menjadwalkan.

## 7. Risiko & yang perlu diputuskan

- **Ini UPDATE massal di prod** → ikuti aturan: backup `tblprintresi` dulu
  (`CREATE TABLE ... SELECT` / `mysqldump`) dan minta konfirmasi tiga kali sebelum
  menjalankan putaran pertama.
- Tabel `tblprintresi` juga diarsip (`Arsip_fcd`); perubahan status resi lama
  harus ikut tersalin — cek watermark arsip memakai kolom apa sebelum jalan.
- `Pemenuhan_kirim_fcd` dan Pencairan Dana membaca status; angka historis akan
  bergeser setelah backlog di-refresh (beri tahu tim Monitoring/Finance dulu).
- Perlu keputusan: ambang final (60 hari? 90?), jumlah per malam, jam jalan.

## 8. Uji (rencana)

- Syntax check `php -l` untuk endpoint baru; uji 100 resi dulu, bandingkan manual.
- Pastikan resi `COMPLETED` tidak ikut berubah dan tidak terkena panggilan Jubelio.
- Cek kolom `status_dicek_at` terisi dan resi tidak diambil ulang di hari yang sama.

## 9. Implementasi (4 Nov 2026)

| Bagian | Berkas |
|---|---|
| Kolom penanda `tblprintresi.status_dicek_at` (DATETIME NULL, di prod **dan** arsip) | `MY_Controller::run_status_dicek_migration()`, `BOOTSTRAP_VERSI` 2026-11-04.1 |
| Pemilihan kandidat (baca saja) + penandaan | `Receipt_fcd::get_kandidat_refresh_status()`, `tandai_status_dicek()` |
| Endpoint (token cron) | `Cron::kandidat_refresh_status` (GET `?mode=&limit=`), `Cron::tandai_status_dicek` (POST) |
| Skrip | `scripts/refresh_status_resi.py` (memakai ulang fungsi `auto_upload_resi_api.py`) |

Perbedaan dari rancangan awal:
- Kandidat dipilih **terbaru dulu** lalu rentang tanggalnya dipakai menarik daftar Jubelio
  (daftar order Jubelio hanya bisa dicari per tanggal, bukan per resi).
- Resi bertanggal `0000-00-00` memakai `tanggal_pesan` untuk menghitung umur.
  Resi `MANUAL*` tidak diikutkan.
- Resi yang **tidak ketemu di Jubelio** hanya ditandai dicek di mode **backlog** (rentang >60 hari
  bersifat statis) dan bila daftar Jubelio lengkap (`LIST_LENGKAP`). Di harian/mingguan tidak
  ditandai, karena daftar order Jubelio bergeser saat order baru masuk: uji sungguhan 4 Nov 2026
  menemukan 13 ribu duplikat dan 20–55% kandidat harian "tidak ketemu" yang sebenarnya ada.
- Jatah panggilan detail per mode: harian 8000, mingguan 3000, backlog 3000
  (`--maks-detail` menimpa). Yang melebihi jatah tidak ditandai, jadi dicoba besok.
- Perubahan status ditulis lewat `cron/auto_upload_resi_api` → `insert_receipt()`
  (aturan skip `COMPLETED` tetap satu pintu). Jalur update hanya menyentuh header
  resi (status, tanggal, kurir, marketplace, toko), **tidak** menyentuh detail/qty.

### Menjalankan

```bash
# 1. Setelah git pull di produksi: buka aplikasi sekali (login) agar migrasi kolom jalan.
# 2. BACKUP tblprintresi dulu (CREATE TABLE ... SELECT / mysqldump) — job ini meng-UPDATE massal.
# 3. Uji tanpa menulis apa pun (tetap membaca Jubelio):
python scripts/refresh_status_resi.py --mode harian --maks-detail 20 --kering
# 4. Uji kecil sungguhan lalu bandingkan beberapa resi dengan Jubelio:
python scripts/refresh_status_resi.py --mode harian --maks-detail 100
# 5. Jadwal malam (semua mode), mis. Task Scheduler 02.00:
python scripts/refresh_status_resi.py
```

Catatan: kandidat yang dikembalikan hanya yang `status_dicek_at` kosong / sudah lewat
jatuh tempo, jadi `GET cron/kandidat_refresh_status` aman dipanggil berkali-kali
(tidak menulis). Cron endpoint ini error "Unknown column" kalau migrasi kolom belum jalan.

### Hasil uji (4 Nov 2026, DB salinan `iresis_prod_uji`, Jubelio dipalsukan)

- Endpoint kandidat: 3 mode + mode salah + tanpa token (403) sesuai harapan;
  resi yang sudah ditandai hilang dari kandidat.
- `--kering`: tidak ada yang tertulis. Mode normal: 4 resi berubah PROCESSING→SHIPPED
  lewat jalur upsert, 55 resi ditandai (4 diperbarui + 51 tidak ketemu), sisanya ditunda.
- **Belum teruji**: panggilan nyata ke Jubelio (kredensial hanya ada di PC produksi).
  Karena itu langkah 3–4 di atas wajib dijalankan dulu sebelum dijadwalkan.

### Penjadwalan (PC produksi)

`scripts/refresh_status_resi_senyap.vbs` menjalankan semua mode tanpa jendela konsol dan
menulis ke `logs/refresh_status_resi.log`. Alamat IRESIS bisa ditimpa per PC lewat
`IRESIS_BASE_URL` di `scripts/secrets_local.py` (opsional; bawaan `localhost:8080`).
Daftarkan sekali (jam 03.00, setelah arsip 01.00 dan backup arsip 02.30):

```bash
schtasks /create /tn "IRESIS - Refresh status resi" /sc daily /st 03:00 /rl LIMITED /f /tr "wscript.exe C:\xampp\htdocs\new-iresis\scripts\refresh_status_resi_senyap.vbs"
```

Pantau `logs/refresh_status_resi.log`: tiap mode mencetak ringkasan
`kandidat / tanpa_perubahan / diperbarui / tidak_ketemu / ditunda`. Backlog selesai
saat kandidat mode backlog tinggal 0. Hasil uji tulis 4 Nov 2026: mingguan 100 diperbarui
(99 COMPLETED, 1 RETURNED), backlog 100 diperbarui, tanpa error.

### Pembaruan 5 Nov 2026 (setelah run malam pertama)

- Sebaran nyata: SHIPPED umur 15-60 hari ada ±415 ribu dan 4-14 hari ±83 ribu (prod hanya menyimpan
  ±150 hari; sisanya dipindah purna). Antrean mingguan bukan "ratusan ribu menunggu" tapi
  "±9 ribu masuk tiap hari", jadi jatah 3.000/malam tidak mengejar.
- Panggilan detail kini **paralel** (`--pekerja`, bawaan 4). Ukur 5 Nov: 1 pekerja 1,4 order/dtk,
  3 pekerja 3,9/dtk, 5 pekerja 6,8/dtk, tanpa 429. Jatah per malam naik: harian 12.000,
  mingguan 15.000, backlog 6.000.
- Upload + penandaan dilakukan **per 1.000 order**, jadi kalau job berhenti di tengah, hasil
  yang sudah diproses tidak hilang.
- Error jaringan Jubelio dicoba ulang (sebelumnya satu putus koneksi membatalkan satu mode).
- Daftar order Jubelio tidak memuat `completed_date` (hanya ada di detail), jadi panggilan
  detail tetap perlu untuk resi yang berubah menjadi COMPLETED; update "hanya dari daftar"
  tidak dipakai agar `tanggal_selesai` tetap akurat.

### Pembaruan 6 Nov 2026 (jatah dinaikkan)

- Run 6 Nov: 03.00-05.07, 0 error, 0 gagal detail, tanpa 429. Antrean umur 15-60 hari masih ±397 ribu
  (masuk ±9 ribu/hari, jatah 15.000 hanya menyusut ±6 ribu/malam → ±2 bulan).
- Jatah mingguan dinaikkan ke 25.000 (batas kandidat 45.000) dan pekerja bawaan ke 5. Perkiraan run
  selesai ±06.15 (jam kerja mulai ±07.00); antrean habis ±3 minggu. Pantau log pagi: kalau muncul 429
  atau run melewati 07.00, turunkan lagi.
