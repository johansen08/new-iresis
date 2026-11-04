# Rancangan: refresh status resi berkala (status final vs non-final)

> **Status: RANCANGAN — belum diimplementasi.** Dibuat 4 Nov 2026 dari temuan
> pengecekan data resi. Tidak ada kode atau data yang diubah oleh dokumen ini.

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
