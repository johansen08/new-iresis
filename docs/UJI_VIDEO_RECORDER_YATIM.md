# Checklist uji: perbaikan recorder yatim (rekaman macet)

Dibuat 24 November 2026. Menguji perbaikan di `assets/js/packer_video.js` (penjaga start ganda) dan
`Packer::upload_video_packing` (tolak potongan sesi > 105 menit). Latar belakang dan buktinya ada di
`docs/PENYIMPANAN_VIDEO.md`. Tidak ada test otomatis; ini checklist manual di PC packer berkamera.

**Persiapan**
- Satu PC packer berkamera (mis. F19-04). Muat ulang halaman (F5 / Ctrl+F5) supaya JS baru terpakai.
- Buka menu **Scan Resi Packer (Webcam)**, pastikan panel kamera tampil dan status "SIAP".
- Pakai resi uji yang sah (belum pernah di-packing). Catat nomornya; semua pengecekan DB di bawah **read-only (SELECT)**.

Query bantu (jalankan di HeidiSQL/phpMyAdmin atau `mysql.exe -u root iresis_prod -e "..."`):

```sql
-- sesi rekaman satu resi
SELECT id_videopacking, bagian, status, durasi_detik, ukuran_byte, mulai_at, selesai_at
FROM tblvideopacking WHERE noresi = 'NOMOR_RESI_UJI' ORDER BY mulai_at;

-- dua sesi dimulai dalam 2 detik untuk resi yang sama (ciri recorder yatim) hari ini
SELECT a.noresi, a.id_videopacking, b.id_videopacking AS id_kembar, a.nama_komputer
FROM tblvideopacking a JOIN tblvideopacking b
  ON a.noresi = b.noresi AND a.id_videopacking < b.id_videopacking
 AND ABS(TIMESTAMPDIFF(SECOND, a.mulai_at, b.mulai_at)) <= 2
WHERE a.mulai_at >= CURDATE();
```

## A. Alur normal (harus tetap sama seperti sebelumnya)

| # | Langkah | Hasil yang diharapkan | OK |
|---|---|---|---|
| A-1 | Scan resi uji **A1** sekali (scan pertama). | Status panel **MEREKAM**, timer durasi berjalan. | ☐ |
| A-2 | Tunggu ±20 detik, scan **A1** lagi (scan kedua) / Submit. | Status "Menyimpan video..." lalu selesai. | ☐ |
| A-3 | Jalankan query "sesi rekaman satu resi" untuk A1. | **Satu** baris, bagian 1, status `SELESAI`, `ukuran_byte` > 0. | ☐ |
| A-4 | Buka video A1 di CS (Video Packing). | Bisa diputar, durasi sesuai. | ☐ |

## B. Scan ganda cepat (inti perbaikan)

| # | Langkah | Hasil yang diharapkan | OK |
|---|---|---|---|
| B-1 | Scan resi uji **B1**, lalu **segera** scan/Enter lagi (< 1 detik) atau tekan Enter dua kali pada kolom scan. | Panel hanya menampilkan satu rekaman (**MEREKAM**), tidak muncul error. | ☐ |
| B-2 | Tunggu ±20 detik, tutup dengan scan kedua. | Selesai normal. | ☐ |
| B-3 | Query "sesi rekaman satu resi" untuk B1. | **Satu** baris `SELESAI`. Sebelum perbaikan muncul dua sesi kembar (bagian 2 + 3). | ☐ |
| B-4 | Ulangi B-1 s/d B-3 sebanyak 5 kali dengan resi uji berbeda. | Tidak pernah ada sesi kembar. | ☐ |
| B-5 | Query "dua sesi dimulai dalam 2 detik" setelah B-4. | **0 baris** untuk resi uji. | ☐ |

## C. Pengecualian

| # | Langkah | Hasil yang diharapkan | OK |
|---|---|---|---|
| C-1 | Scan **C1**, lalu sebelum selesai scan resi lain **C2** (ganti resi). | C1 tertutup rapi (`SELESAI`/`TERPUTUS` dengan ukuran kecil), C2 mulai MEREKAM. Tidak ada rekaman ketiga. | ☐ |
| C-2 | Saat C2 merekam, klik **Batal Scan**. | Rekaman berhenti, status baris `DIBATALKAN` bila fitur batal berlaku. | ☐ |
| C-3 | Saat merekam, pindah menu lain (tanpa reload). | Rekaman berhenti, status panel tidak tertinggal MEREKAM. | ☐ |
| C-5 | Tepat setelah membuka menu (kamera belum siap), scan resi **C3** dua kali cepat (scan 1 dan 2 berselang < 2 detik). | Tidak ada rekaman yang menyala sendiri setelah itu (status panel bukan MEREKAM). | ☐ |
| C-4 | Saat merekam, tutup tab. | Baris tertutup (potongan terakhir naik) atau menunggu cron menandai `TERPUTUS`. | ☐ |

## D. Batas 90 menit (butuh waktu)

| # | Langkah | Hasil yang diharapkan | OK |
|---|---|---|---|
| D-1 | Scan **D1**, **jangan** scan kedua. Biarkan 90 menit (boleh sambil menunggu uji lain di PC lain). | Pada menit ke-90 rekaman berhenti sendiri dan muncul pemberitahuan batas habis. | ☐ |
| D-2 | Cek DB untuk D1. | Status bukan `MEREKAM` (sesi tertutup), `durasi_detik` ≈ 5400. | ☐ |

## E. Pengaman server (opsional, hanya di DB uji)

Menguji penolakan potongan sesi > 105 menit memerlukan mengubah `mulai_at` baris uji ke masa lalu,
yaitu `UPDATE`. **Dilarang di produksi tanpa prosedur** (konfirmasi tiga kali + backup, lihat `CLAUDE.md`).
Jalankan hanya di salinan DB uji; atau cukup tunggu 105 menit pada recorder yang sengaja dibiarkan.

| # | Langkah | Hasil yang diharapkan | OK |
|---|---|---|---|
| E-1 | Sesi uji berumur > 105 menit menerima potongan baru. | Respons JSON `Rekaman melewati batas durasi, potongan diabaikan.`; ukuran berkas tidak bertambah; status jadi `TERPUTUS`. | ☐ |

## F. Pantau 1-2 hari setelah dipakai semua packer

| # | Pengecekan | Hasil yang diharapkan | OK |
|---|---|---|---|
| F-1 | Menu **Penyimpanan Video** (webmaster), kolom **Macet** hari ini dan kemarin. | `-` (kosong); banner macet tidak muncul. | ☐ |
| F-2 | Query "dua sesi dimulai dalam 2 detik" dengan `a.mulai_at >= CURDATE()`. | 0 baris (kecuali PC yang belum di-F5). | ☐ |
| F-3 | Ukuran harian di menu Penyimpanan Video. | Kembali ke kisaran normal (±50-90 GB/hari), tanpa lonjakan 100+ GB. | ☐ |
| F-4 | `logs/cron_tutup_video_menggantung.log`. | `"ditandai":0` pada hampir semua putaran. Angka > 0 berulang = ada PC yang belum memakai JS baru. | ☐ |

Kalau B-3 atau F-2 masih menunjukkan sesi kembar: catat `nama_komputer`, `id_user`, dan jam; periksa apakah
PC itu sudah memuat ulang halaman (cek `Network` → `packer_video.js?v=` berisi versi terbaru).

## Hasil uji simulasi di browser (24 Nov 2026)

Dijalankan dengan kamera tiruan (canvas) dan unggahan tiruan, memuat `packer_video.js` lama (sebelum `beb99dd`) dan baru:

| Skenario | JS lama | JS baru |
|---|---|---|
| `sinkron(resi sama)` dipanggil 3x beruntun (< 0,2 detik) | **2 sesi** berjalan bersamaan | **1 sesi** |
| Scan kedua (`auto_save_success`) | satu sesi ditutup, **satu sesi yatim** terus mengunggah (tidak pernah ada potongan penutup, `aktif()` = false) | satu sesi ditutup dengan potongan penutup, tidak ada unggahan lagi |
| Ganti resi A1 → B1 beruntun | - | hanya sesi B1 |
| Scan kedua sebelum kamera siap | - | tidak ada rekaman yang menyala |
