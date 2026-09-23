# Backlog iResis

Terakhir diperbarui: 23 Sep 2026 · Sumber: [`PRD.md`](PRD.md)

Backlog ini menurunkan PRD menjadi pekerjaan yang bisa diambil satu per satu. Sumber tiap task adalah risiko dan pertanyaan terbuka di PRD §10, metrik yang belum terukur di §2, dan kebutuhan **(rancangan)** di §5. Angka diambil dari `iresis_prod`, `iresis_arsip`, dan `iresis_dev` per 23 Sep 2026 (hanya `SELECT`). Task bertanda *temuan* tidak ada di PRD; masalahnya ditemukan saat backlog ini disusun.

**Cara membaca**

- **Prioritas.** P0 = risiko data rusak atau hilang, kerjakan dulu. P1 = keamanan dan metrik PRD. P2 = bisa menunggu.
- **Ukuran.** S ≤ ½ hari, M 1–3 hari, L > 3 hari. Task L wajib punya spec + plan dulu di `docs/superpowers/`.
- **Status.** *Siap* = bisa dikerjakan sekarang. *Terhambat Qx* = menunggu keputusan di bawah. *Setelah B-xx* = menunggu task lain.
- **Rujukan.** Cantumkan ID di pesan commit, misalnya `fix(receipt): isi tanggal_printresi kosong (B-03)`. Setelah selesai, ubah status di tabel ringkasan menjadi `Selesai (<hash commit>)`.

## Ringkasan

| ID | Task | Prio | Ukuran | Status | Sumber |
| --- | --- | --- | --- | --- | --- |
| B-01 | Perbaiki argumen task "Optimasi tabel bulanan" | P0 | S | Siap, sebelum 1 Okt 22.30 | *temuan* |
| B-02 | Salin backup ke luar drive C: dan uji pulihkan | P0 | S | Terhambat Q7 | §10 satu disk |
| B-03 | Isi `tanggal_printresi` 0000-00-00 dari `created_at` | P0 | S | Siap (konfirmasi 3×) | §10 data tanggal |
| B-04 | Password ke `password_hash()`, migrasi saat login | P1 | M | Siap | §10 MD5 |
| B-05 | Cek hak akses endpoint jadi pola umum — tahap 1 | P1 | M | Siap | §10 hak akses |
| B-06 | Cek hak akses endpoint — sisa endpoint tulis | P2 | L | Setelah B-05 | §10 hak akses |
| B-07 | Aktifkan CSRF bertahap | P2 | L | Siap (spec dulu) | §10 CSRF |
| B-08 | Laporan ketepatan handover vs jam batas kirim | P1 | M | Siap; target menunggu Q1 | §2, F-32, F-80 |
| B-09 | Laporan umur retur "Terima Retur" | P1 | M | Siap; ambang menunggu Q3 | §2, F-54 |
| B-10 | Isi target harian picker | P1 | S | Terhambat Q2 | §2, F-82 |
| B-11 | Tetapkan jalur unggah resi dan pastikan terjadwal | P1 | S | Terhambat Q4 | §10 Jubelio, F-01, F-02 |
| B-12 | Akun tim accounting dan finance | P1 | S | Terhambat Q5 | §3, F-71 |
| B-13 | Paket cancel tahap 2: Cek Paket Cancel | P2 | L | Terhambat Q6 | F-44 |
| B-14 | Paket cancel tahap 3: batch ke display | P2 | L | Setelah B-13 | F-44 |
| B-15 | Daftar uji regresi manual per meja | P2 | S | Selesai (`7949cd2`) | §10 tanpa test |
| B-16 | Smoke test CLI untuk endpoint scan | P2 | M | Siap | §10 tanpa test, §6 |
| B-17 | Jajaki API retur Jubelio | P2 | M | Siap (riset) | §10 Jubelio |
| B-18 | Perbarui dokumen yang usang | P2 | S | Siap | *temuan* |
| B-19 | Tutup folder `graft/` dari akses HTTP | P1 | S | Selesai (`75f24ad`) | *temuan* |

## Keputusan yang dibutuhkan

Q1–Q6 berasal dari PRD §10. Q7 baru, karena B-02 butuh perangkat.

| Q | Keputusan | Membuka |
| --- | --- | --- |
| Q1 | Target % paket di-handover sebelum jam batas kirim, **dan definisi "tepat waktu"**. Contoh pilihan: di-handover hari yang sama dengan unggah dan sebelum `jam_batas_kirim`, atau sebelum `tblprintresi.tanggal_bataskirim` dari marketplace | B-08 |
| Q2 | Target harian picker per status performa (1_SKU_PICKER, NORMAL_PICKER) | B-10 |
| Q3 | Berapa hari retur boleh berstatus "Terima Retur" sebelum dianggap terlambat | B-09 |
| Q4 | Jalur unggah resi yang aktif: v1 (browser) atau v2 (API), dan di PC mana script dijalankan | B-11 |
| Q5 | Siapa pemegang akun tim accounting dan tim finance | B-12 |
| Q6 | Jadwal tahap 2–3 paket cancel | B-13, B-14 |
| Q7 | Tujuan salinan backup: HDD eksternal, NAS, atau PC lain di LAN | B-02 |

## P0 — lindungi data

### B-01 · Perbaiki argumen task "Optimasi tabel bulanan"

`S` · Siap · *temuan*

- **Fakta.** Argumen task `IRESIS - Optimasi tabel bulanan` rusak: `//B //Nologo " C:\xampp\htdocs\new-iresis\scripts\optimasi_tabel_senyap.vbs\`. Ada spasi di dalam kutip, garis miring balik di akhir, dan tidak ada kutip penutup. Task ini belum pernah jalan (`0x41303`), dan jadwal pertamanya 1 Okt 22.30. Karena `//B`, wscript akan gagal tanpa pesan apa pun.
- **Langkah.** Samakan argumennya dengan task `IRESIS - Optimasi tabel (sekali 21 Sep 2026)` yang sukses: `//B //Nologo "C:\xampp\htdocs\new-iresis\scripts\optimasi_tabel_senyap.vbs"`. Jangan dijalankan manual pada jam kerja, karena optimasi membangun ulang tabel.
- **Selesai bila** `LastTaskResult` bernilai `0x0` setelah jadwal 1 Okt 22.30.

### B-02 · Salin backup ke luar drive C: dan uji pulihkan

`S` setelah perangkat ada · Terhambat Q7 · PRD §10 "Satu server, satu disk"

- **Fakta.** Task `IRESIS - Backup DB 30 menit` menulis ke `C:\backup-db\`. Backup arsip 02.30 juga tersimpan di C:. PC server hanya punya drive C: (389 GB kosong), jadi disk rusak berarti DB dan semua backup hilang bersamaan.
- **Langkah.** Siapkan tujuan di luar disk ini. Pasang salinan terjadwal (misalnya `robocopy`) setelah tiap backup, dengan masa simpan yang jelas. Lalu pulihkan satu file backup ke DB kosong untuk membuktikan backup-nya bisa dipakai.
- **Selesai bila** salinan terbaru di tujuan berumur ≤ 1 jam, dan uji pulihkan tercatat di `docs/` beserta tanggal dan langkahnya.

### B-03 · Isi `tanggal_printresi` 0000-00-00 dari `created_at`

`S` · Siap, wajib konfirmasi 3× + backup · PRD §10 "Data tanggal rusak"

- **Fakta.** Baris rusak ada 17 di prod dan 61.033 di arsip. Semuanya dibuat 9–12 Mar 2026, dan `created_at` terisi di semua baris. Untuk resi September, tanggal `tanggal_printresi` sama dengan tanggal `created_at` di 236.474 dari 236.474 baris (dev), jadi `created_at` adalah nilai pengganti yang tepat. Tidak ada baris rusak baru setelah 12 Mar, jadi akar masalahnya sudah hilang.
- **Langkah.** Ikuti pola skrip koreksi tanggal hari/bulan 22 Sep:
  - backup tabel dulu (`CREATE TABLE … SELECT`);
  - koreksi prod dulu, baru arsip;
  - jalankan di luar jam kerja dan di luar jendela 01.00–03.00;
  - `UPDATE` hanya baris yang `tanggal_printresi = '0000-00-00'`.

  Tampilkan tabel, query, dan dampaknya ke user sebelum konfirmasi.
- **Selesai bila** hitungan `tanggal_printresi = '0000-00-00'` bernilai 0 di prod dan arsip.

## Keamanan (P1–P2)

### B-19 · Tutup folder `graft/` dari akses HTTP

`S` · Siap · *temuan*

- **Fakta.** `.htaccess` root memblokir folder yang disebut satu per satu (`scripts`, `docs`, `dev_tools`, `logs`, `sql_migrations`, `vendor`) dan file root berekstensi tertentu. Folder lain yang benar-benar ada tetap dilayani Apache apa adanya. Akibatnya, `http://localhost/iresis-dev/graft/index.md` membalas 200. Isinya indeks kode Graft (717 file, termasuk ringkasan `scripts/` dan `dev_tools/`) dan bisa dibuka dari LAN. Tidak ditemukan nilai kredensial di dalamnya. Folder ini hanya ada di dev (di-gitignore, tidak ada di produksi).
- **Langkah.** Tambahkan `graft` ke aturan di `.htaccess` baris 10. Aturan ini aman ikut ter-pull ke produksi.
- **Selesai bila** URL di atas membalas 403 dan Graft tetap berjalan untuk Claude Code, karena Graft membaca dari disk, bukan lewat HTTP.

### B-04 · Password ke `password_hash()`, migrasi saat login

`M` · Siap · PRD §10 "Password MD5 tanpa salt"

- **Fakta.** `md5()` dipakai di [`Login.php:59`](../application/controllers/Login.php) (login), [`User.php:50`](../application/controllers/User.php) (tambah user), dan `User.php:153` (ubah password). Kolom `tbluser.password` sudah `varchar(255)`, jadi tidak perlu `ALTER`.
- **Langkah.**
  - Saat login, coba `password_verify()` dulu.
  - Kalau gagal tetapi MD5-nya cocok, loloskan login lalu simpan `password_hash()`.
  - Tambah user dan ubah password langsung memakai `password_hash()`.
- **Selesai bila** akun lama bisa login dan setelahnya tersimpan dengan hash baru, akun baru langsung memakai hash baru, dan password salah tetap ditolak.

### B-05 · Cek hak akses endpoint jadi pola umum — tahap 1

`M` · Siap · PRD §10 "Hak akses dicek manual per controller"

- **Fakta.** [`MY_Controller::validate()`](../application/core/MY_Controller.php) (baris 276) berhenti setelah menghitung `$class` dan `$method`, tanpa memeriksa apa pun. [`Retur_klaim.php`](../application/controllers/Retur_klaim.php) (baris 29–50) sudah punya pola yang benar: `_has_menu($uri)` mengecek `roleaccess`, lalu `_require($uri)` membalas lewat `make_ajax_response(403, …)`.
- **Langkah.** Angkat kedua helper itu ke `MY_Controller`, lalu pakai di `Retur_klaim` supaya polanya tidak ganda. Pasang di endpoint tulis:
  - modul admin: User, Menu, Access, Hapus Resi;
  - modul uang: Denda, Pergantian Barang, Upload HPP.
- **Selesai bila** role yang tidak memegang menu itu mendapat pesan "tidak punya akses" saat POST langsung ke endpoint, sementara role yang berhak tetap bisa memakainya.

### B-06 · Cek hak akses endpoint — sisa endpoint tulis

`L` · Setelah B-05 · PRD §10

Terapkan helper dari B-05 ke endpoint tulis lain, per modul (retur, CS, receipt, report, dan seterusnya). Mulailah dengan spec yang memetakan endpoint → menu, karena satu endpoint bisa dipakai lebih dari satu menu.

### B-07 · Aktifkan CSRF bertahap

`L` · Siap, spec dulu · PRD §10 "CSRF dan XSS filter mati"

- **Fakta.** `csrf_protection` bernilai `FALSE` dan `csrf_exclude_uris` kosong di `config/config.php` (baris 463 dan 468). CSRF di CI3 berlaku global untuk semua POST, jadi "bertahap per modul" artinya mulai dengan daftar pengecualian yang lebar, lalu dipersempit.
- **Yang harus ada di spec.**
  - Token dikirim lewat `$.ajaxSetup` di `assets/js/plugins.js`.
  - `csrf_regenerate` bernilai `FALSE`, supaya layar scan yang mengirim beruntun tidak gagal di scan kedua.
  - `cron/*` dikecualikan.
  - Perlakuan untuk upload multipart dan POST DataTables server-side.
  - Karena gagal CSRF di CI membalas halaman HTML, balasannya harus lewat jalur yang tidak merusak JSON.

## P1 — metrik PRD §2

PRD §2 menyebut dua laporan ini sebagai pekerjaan pertama setelah PRD disepakati. P0 tetap didahulukan karena semuanya berukuran S dan tidak menunda kedua laporan ini.

### B-08 · Laporan ketepatan handover vs jam batas kirim

`M` · Siap; garis target menunggu Q1 · PRD §2, F-32, F-80

- **Data.**
  - Waktu handover: `tblresikeluar.tanggal_resikeluar` (dan `tblscan_ndd` untuk paket NDD).
  - Batas: `tblkurir.jam_batas_kirim` (`varchar(5)`, format `HH:MM`), digabung lewat `tblprintresi.id_kurir`.
  - Pembanding lain yang tersedia: `tblprintresi.tanggal_bataskirim`.
- **Hasil.** Persentase resi tepat waktu per kurir per hari, daftar resi yang terlambat, dan ekspor Excel (F-84). Rumus "tepat waktu" mengikuti jawaban Q1. Menu baru berarti `BOOTSTRAP_VERSI` wajib dinaikkan.
- **Selesai bila** angka satu hari cocok dengan hitungan manual dari tabel untuk dua kurir.

### B-09 · Laporan umur retur "Terima Retur"

`M` · Siap; penanda terlambat menunggu Q3 · PRD §2, F-54

- **Fakta (dev, 23 Sep).** Ada 487 retur berstatus "Terima Retur". Yang tertua 13 Jul 2026, dan 127 di antaranya sudah lebih dari 7 hari.
- **Data.** `tblresiretur.status_retur` dan `tanggal_resiretur`, dengan tanggal scan sebagai awal umur.
- **Hasil.** Hitungan per kelompok umur (0–2, 3–7, > 7 hari) di Dashboard Retur, plus daftar yang bisa diklik ke detail resi. Ambang terlambat mengikuti jawaban Q3.

### B-10 · Isi target harian picker

`S` · Terhambat Q2 · PRD §2, F-82

Target `1_SKU_PICKER` dan `NORMAL_PICKER` di `tblmasterstatusperforma` masih 0, sehingga pencapaian picker tidak punya pembanding. Isi lewat menu Target KPI, bukan SQL manual. Setelah itu, cek Dashboard KPI Picker menampilkan persentase.

## P1 — keputusan operasional

### B-11 · Tetapkan jalur unggah resi dan pastikan terjadwal

`S` · Terhambat Q4 · PRD §10 "Ketergantungan pada UI Jubelio", F-01, F-02, F-52

- **Fakta.** Di PC server tidak ada task terjadwal yang menjalankan `scripts/*.py`. Folder `C:\MP`, tempat `.bat` di `AUTO_UPLOAD_RETUR_JUBELIO.md`, juga tidak ada. Berarti unggah otomatis resi dan retur berjalan di PC lain, atau tidak terjadwal sama sekali.
- **Langkah.** Pastikan di mana dan kapan script berjalan. Tetapkan jalur resi v1 atau v2. Catat hasilnya di `AUTO_UPLOAD_RESI.md` dan `AUTO_UPLOAD_RETUR_JUBELIO.md`.
- **Selesai bila** kedua dokumen menyebut PC, nama task, dan jamnya, dan unggahan terakhir terlihat di data.

### B-12 · Akun tim accounting dan finance

`S` · Terhambat Q5 · PRD §3, F-71

Kedua peran ini belum punya akun aktif, jadi menunya dijalankan admin atau webmaster. Akibatnya, pemisahan "pengaju klaim bukan verifikator" di F-71 belum berlaku dalam praktik. Buat akunnya lewat menu User setelah Q5 terjawab, lalu uji satu klaim dari pengajuan sampai verifikasi dengan dua akun berbeda.

## P2 — fitur rancangan

### B-13 · Paket cancel tahap 2: Cek Paket Cancel

`L` · Terhambat Q6 · F-44

Tim retur men-scan paket cancel yang sudah ditemukan (`tblcancel_paket` berstatus DITEMUKAN) dan mengecek isinya. Selisih dengan pesanan menjadi masalah picker. Rancangannya ada di [`PAKET_CANCEL.md`](PAKET_CANCEL.md) (tahap 2). Tulis spec dan plan dulu di `docs/superpowers/`.

### B-14 · Paket cancel tahap 3: batch ke display

`L` · Setelah B-13 · F-44

Barang dari paket cancel yang lolos cek dikirim ke display lewat `tblretur_display_batch` dengan sumber CANCEL. Tim restock menerimanya dengan scan SKU dan qty. Rancangannya ada di `PAKET_CANCEL.md` (tahap 3).

## P2 — hutang teknis

### B-15 · Daftar uji regresi manual per meja

`S` · Selesai (`7949cd2`, hasilnya [`UJI_REGRESI.md`](UJI_REGRESI.md)) · PRD §10 "Tanpa test otomatis"

Buat satu checklist di `docs/` yang dijalankan di `iresis-dev` sebelum pull ke produksi. Untuk tiap meja (picker, packer, HO, retur, admin upload), isinya: scan resi normal, resi batal, resi yang tahap sebelumnya belum di-scan, dan satu alur pengecualian (lost scan atau masalah picker), lengkap dengan hasil yang diharapkan.

### B-16 · Smoke test CLI untuk endpoint scan

`M` · Siap · PRD §10, §6 "Keandalan respons"

Buat script PHP CLI yang memanggil endpoint scan utama di `iresis-dev` dengan resi uji, lalu memeriksa bahwa balasannya JSON valid dengan status yang diharapkan. Script ini menangkap kasus "satu byte keluaran nyasar" sebelum sampai ke lantai gudang. Simpan di `tests/`, tambahkan `tests` ke aturan blokir `.htaccess` baris 10 (lihat B-19: folder yang tidak disebut di sana bisa dibuka lewat HTTP), dan tolak eksekusi selain lewat CLI.

### B-17 · Jajaki API retur Jubelio

`M` · Siap (riset) · PRD §10 "Ketergantungan pada UI Jubelio"

Unggah resi sudah punya jalur API v2 (`core-api`), tetapi retur masih bergantung pada tombol "Cetak" di Telerik Report Server. Cek apakah `core-api` punya endpoint retur yang cukup untuk `cron/auto_upload_retur_jubelio`. Hasilnya berupa catatan di `AUTO_UPLOAD_RETUR_JUBELIO.md`: bisa, sebagian, atau tidak, beserta alasannya.

### B-18 · Perbarui dokumen yang usang

`S` · Siap · *temuan*

- `HANDOFF.md` §4 masih menyebut kredensial Jubelio dan token cron *hardcoded* di `scripts/*.py`. Kenyataannya, keempat script `auto_upload_*` sudah membaca `secrets_local.py`, yang di-gitignore.
- `CLAUDE.md` menyebut 39 controller, 41 model, dan 392 route. Kode sekarang punya 44 controller, 47 model, dan 413 route.
- `CLAUDE.md` menyebut `.htaccess` sebagai *whitelist*. Isinya sebenarnya daftar blokir: folder yang tidak disebut di sana tetap bisa dibuka lewat HTTP (lihat B-19). Komentar di `.htaccess` sudah diluruskan di `75f24ad`.
- Setelah B-11 selesai, cocokkan juga bagian jadwal di kedua dokumen auto upload.

## Urutan yang disarankan

1. **Minggu ini, tanpa menunggu.** B-01 (sebelum 1 Okt 22.30) dan B-03. B-15 dan B-19 sudah selesai. Sambil itu, jawab Q1–Q7.
2. **Berikutnya.** B-08 dan B-09 (pekerjaan pertama menurut PRD §2), lalu B-04 dan B-05.
3. **Begitu keputusan masuk.** B-02, B-10, B-11, dan B-12. Hampir semuanya kerja operasional, bukan kode.
4. **Setelah itu.** B-13 → B-14, lalu B-06, B-16, B-07, dan B-17. B-18 bisa diselipkan kapan saja.
