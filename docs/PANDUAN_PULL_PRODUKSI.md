# Panduan Pull ke PC Produksi

Panduan menarik perubahan dari GitHub (`origin/master`) ke PC produksi IRESIS.
Bagian A berlaku untuk **setiap** pull; Bagian B dan seterusnya adalah langkah
tambahan untuk rilis tertentu yang butuh lebih dari sekadar `git pull`.

Semua perintah dijalankan di **PowerShell** pada PC produksi, dari folder
`C:\xampp\htdocs\new-iresis`.

```powershell
cd C:\xampp\htdocs\new-iresis
```

---

## A. Prosedur baku (setiap pull)

### A.1 Pilih waktu yang aman

Pull mengganti berkas PHP saat Apache masih melayani request. Lakukan saat
tidak ada packer yang sedang merekam video (menu Scan Resi Packer Webcam)
dan tidak ada upload Excel besar yang sedang berjalan — misalnya sebelum
shift pagi atau sesudah jam operasional.

### A.2 Backup database dulu

Beberapa rilis menjalankan migrasi tabel otomatis pada request pertama dari
pengguna yang sedang login (lihat `BOOTSTRAP_VERSI` di
`application/core/MY_Controller.php`). Migrasi tidak bisa di-undo dengan
`git`, jadi backup dulu.

> **Database live bernama `iresis_prod` (garis bawah).** Di MariaDB PC
> produksi masih ada `iresis-prod` (tanda hubung): salinan lama per
> 2 September 2026 yang tidak dipakai aplikasi. Jangan backup atau cek
> kolom di sana. Kalau ragu, lihat kunci `db_database` di
> `application/config/secrets.php`.

```powershell
New-Item -ItemType Directory -Force C:\backup-db | Out-Null
$stamp = Get-Date -Format "yyyyMMdd-HHmm"
cmd /c "`"C:\xampp\mysql\bin\mysqldump.exe`" -u root --routines --triggers --single-transaction iresis_prod > C:\backup-db\iresis_prod-$stamp.sql"
Get-Item C:\backup-db\iresis_prod-$stamp.sql | Select-Object Name, Length
Get-Content C:\backup-db\iresis_prod-$stamp.sql -Tail 1
```

Ukurannya harus jauh di atas 0 (per September 2026 sekitar 2,3 GB) dan baris
terakhirnya `-- Dump completed on ...`. Dump penuh selesai sekitar 1,5 menit
dan tidak mengunci tabel (semua tabel InnoDB, `--single-transaction`), jadi
tetap aman dijalankan saat packer sedang bekerja.

### A.3 Pastikan working tree bersih

```powershell
git status
```

Yang boleh muncul hanya berkas yang memang tidak di-commit (gitignored tidak
tampil di sini). Kalau ada baris `M` (modified) pada berkas yang dilacak git,
berarti ada perubahan lokal di PC produksi yang belum masuk repo — **jangan
di-discard** sebelum tahu apa isinya:

```powershell
git diff
```

- Kalau itu tambalan yang memang perlu dipertahankan: simpan sementara dengan
  `git stash push -m "tambalan lokal produksi"`, pull, lalu `git stash pop`.
- Kalau itu perubahan yang seharusnya masuk repo: hentikan, commit dulu lewat
  branch sesuai `docs/DEVELOPMENT_STANDARDS.md` §5.

### A.4 Pastikan remote terpasang

```powershell
git remote -v
```

Harus menampilkan `origin  https://github.com/johansen08/new-iresis.git`.
Kalau kosong (PC produksi belum pernah di-set remote-nya):

```powershell
git remote add origin https://github.com/johansen08/new-iresis.git
```

### A.5 Tarik perubahan

```powershell
git fetch origin
git log --oneline HEAD..origin/master
```

Baris yang tampil adalah commit yang akan masuk. **Baca dulu** — cari kata
`migrasi`, `BOOTSTRAP_VERSI`, `secrets`, `cron`, `.bat`: itu tanda rilis
butuh langkah tambahan (lihat Bagian B). Lalu:

```powershell
git pull --ff-only origin master
```

`--ff-only` sengaja dipakai: kalau gagal, berarti ada commit lokal di PC
produksi yang tidak ada di GitHub. Jangan dipaksa dengan merge/rebase di
PC produksi — hubungi yang memegang repo.

### A.6 Syntax check berkas yang berubah

```powershell
git diff --name-only HEAD@{1} HEAD -- '*.php' | ForEach-Object { & C:\xampp\php\php.exe -l $_ }
```

Semua baris harus `No syntax errors detected`. Kalau ada yang error, kembali
ke versi sebelumnya (lihat A.9) dan laporkan.

### A.7 Picu migrasi + buang cache menu

Migrasi dan pembaruan menu berjalan **sekali**, pada request pertama dari
pengguna mana pun yang sedang login. Di jam kerja itu terjadi dalam hitungan
detik setelah pull karena packer terus mengirim scan (rilis 15 September 2026
termigrasi pada detik yang sama dengan pull). Di luar jam kerja, picu sendiri:
**logout, lalu login lagi** dengan akun webmaster, buka satu menu apa saja.
Tandanya berhasil: berkas `application/cache/bootstrap_migrasi.txt` berisi
versi baru:

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
```

Pengguna lain yang masih login akan melihat menu baru setelah mereka
logout/login (cache menu di session dikunci per versi bootstrap).

### A.8 Verifikasi cepat

1. Buka `http://localhost/new-iresis/` — halaman login tampil tanpa
   teks `PHP Error`/`Warning` di atasnya. Apache di PC produksi mendengarkan
   port **80** (dan 443 untuk https), bukan 8080.
2. Login, buka 2–3 menu yang disentuh rilis (lihat `git log`). Halaman yang
   tampil sebagai **teks JSON mentah** berarti ada output nyasar di PHP —
   lihat `CLAUDE.md` bagian "SPA semu".
3. Cek log error PHP hari ini. Dua jenis baris sudah biasa dan boleh
   diabaikan: `Undefined array key "WARNING"` dari `system/core/Log.php`
   (bawaan CI3 di PHP 8) dan `[cron_finalisasi_video] OK` yang ditulis cron
   tiap menit (Bagian B). Saring keduanya:

```powershell
Get-Content application\logs\log-$(Get-Date -Format yyyy-MM-dd).php -Tail 400 |
    Select-String -NotMatch 'Undefined array key "WARNING"|\[cron_finalisasi_video\] OK'
```

### A.9 Kembali ke versi sebelumnya (kalau ada masalah)

```powershell
git log --oneline -5
git reset --hard <hash-commit-sebelum-pull>
```

Catatan: kolom/tabel yang sudah ditambahkan migrasi **tidak** ikut kembali —
itu tidak masalah, kode lama mengabaikannya. Kalau perlu benar-benar
mengembalikan data, restore dari backup A.2. Setelah masalah beres, lakukan
ulang `git pull --ff-only origin master`.

---

## B. Langkah tambahan rilis 15 September 2026 (`56cc405..f55cde4`)

Isi rilis:

| Commit | Perubahan |
|---|---|
| `c6bb95c`, `a0e52e6` | Rekaman webcam packer sempat 1920×1080 @ 2,5 Mbps; **diturunkan lagi** ke 1280×720 @ 15 fps, 1,2 Mbps (~540 MB/jam per PC) oleh branch `fix/video-packing-720p` |
| `010878c` | Panel kamera menampilkan resolusi aktual webcam (oranye kalau di bawah 720p) |
| `c585bf1` | Finalisasi WebM dengan ffmpeg (durasi/seek) + tombol "Siapkan MP4" di menu Video Packing |

Tanpa langkah di bawah, aplikasi tetap jalan: rekaman tetap tersimpan dan
bisa diputar, hanya durasi/seek tidak diperbaiki dan tombol MP4 akan
menampilkan "mengantre" tanpa pernah selesai.

### B.1 Pasang ffmpeg

Taruh ffmpeg di path tetap `C:\ffmpeg\bin\`. Jangan pakai `winget install`:
paket portable itu dipasang di `AppData` milik satu user dengan nama folder
berversi, jadi path di `secrets.php` bisa putus setelah `winget upgrade`.
Pakai `winget download` (hash unduhan tetap diverifikasi winget), lalu
ekstrak. Tidak butuh Administrator:

```powershell
$dl = "$env:TEMP\ffmpeg-dl"
winget download --id Gyan.FFmpeg --exact --source winget --download-directory $dl
$zip = Get-ChildItem $dl -Filter *.zip | Select-Object -First 1
New-Item -ItemType Directory -Force "$dl\x" | Out-Null
tar -xf $zip.FullName -C "$dl\x"
$isi = Get-ChildItem "$dl\x" -Directory | Select-Object -First 1
Move-Item $isi.FullName C:\ffmpeg
```

Tanpa winget: unduh `ffmpeg-<versi>-full_build.zip` dari
https://github.com/GyanD/codexffmpeg/releases (sumber yang sama dengan paket
winget) dan ekstrak dengan cara yang sama. Hasil akhirnya harus
`C:\ffmpeg\bin\ffmpeg.exe` dan `C:\ffmpeg\bin\ffprobe.exe` di folder yang
sama — library mencari ffprobe di sebelah ffmpeg.

Cek:

```powershell
& C:\ffmpeg\bin\ffmpeg.exe -version
Test-Path C:\ffmpeg\bin\ffprobe.exe
```

### B.2 Tambah `ffmpeg_path` di `secrets.php`

Buka `application\config\secrets.php`, tambahkan di dalam array (sebelum
`);` penutup), pakai path dari B.1 dengan garis miring biasa:

```php
	'ffmpeg_path' => 'C:/ffmpeg/bin/ffmpeg.exe',
```

Contoh lengkapnya ada di `application/config/secrets.php.example`.

### B.3 Pastikan migrasi kolom sudah jalan

Setelah logout/login (A.7), cek:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "SHOW COLUMNS FROM tblvideopacking LIKE 'mp4_status';"
```

Harus menampilkan satu baris `mp4_status`. Kalau kosong, versi bootstrap
belum terpicu — ulangi A.7.

### B.4 Coba cron sekali secara manual

```powershell
& C:\xampp\php\php.exe index.php cron finalisasi_video
```

Keluaran yang benar berbentuk
`[cron_finalisasi_video] OK: {"remux_ok":N,...}`. Kalau muncul
`ffmpeg tidak bisa dijalankan`, periksa B.1–B.2. Jalankan beberapa kali
sampai `remux_ok` menjadi 0 — itu artinya semua rekaman lama sudah
difinalisasi (maks. 20 rekaman per jalan). Kalau belum pernah ada rekaman
(tabel `tblvideopacking` kosong), `remux_ok` langsung 0 pada jalan pertama;
itu normal — yang diuji di sini hanya bahwa ffmpeg bisa dijalankan.

### B.5 Daftarkan task terjadwal (tiap 1 menit)

Buka PowerShell **sebagai Administrator** (klik kanan → *Run as
administrator*), lalu:

```powershell
cd C:\xampp\htdocs\new-iresis
powershell -ExecutionPolicy Bypass -File .\setup_task_finalisasi_video.ps1
```

Skrip itu membuat task `IRESIS - Finalisasi Video` yang memanggil
`cron_finalisasi_video.bat` tiap menit sebagai akun **SYSTEM**: tidak ada
jendela cmd yang berkedip di layar PC produksi, dan task tetap jalan walau
belum ada user yang login (misalnya setelah restart Windows Update). Skrip
aman dijalankan ulang — task lama ditimpa.

Cek setelah satu-dua menit — `LastTaskResult` harus `0` dan log bertambah:

```powershell
Get-ScheduledTaskInfo -TaskName "IRESIS - Finalisasi Video" | Select-Object LastRunTime, LastTaskResult, NextRunTime
Get-Content logs\cron_finalisasi_video.log -Tail 3
```

### B.6 Cek kapasitas disk `C:\video-packing\`

Dengan setelan 720p @ 1,2 Mbps, rekaman ≈ 540 MB per jam per PC packer.
Dengan ~30 PC × 6 jam/hari ≈ **100 GB/hari** (separuh setelan 1080p
sebelumnya). Rekaman yang sudah terlanjur dibuat dengan 1080p tetap
berukuran lama. Pastikan drive-nya
cukup dan sepakati kebijakan retensi (berapa hari rekaman disimpan) —
belum ada penghapusan otomatis di aplikasi.

Perhatikan: di PC produksi `C:\video-packing\` berada di drive **C:** yang
sama dengan data MariaDB (`iresis_prod`) dan XAMPP. Kalau drive itu penuh,
yang berhenti bukan cuma rekaman — database dan aplikasi ikut macet. Per
15 September 2026 sisa ruang C: ±375 GB, artinya kurang dari empat hari kerja
pada perkiraan penuh di atas. Pantau:

```powershell
Get-PSDrive C | Select-Object @{n='Terpakai_GB';e={[math]::Round($_.Used/1GB)}}, @{n='Sisa_GB';e={[math]::Round($_.Free/1GB)}}
```

### B.7 Verifikasi di sisi pengguna

- **Packer**: buka Scan Resi Packer (Webcam) → di panel kamera muncul baris
  `Resolusi: 1280×720 @ 15 fps`. Kalau oranye ("di bawah 1280×720"),
  webcam PC itu tidak sanggup 720p — catat PC-nya.
- **CS**: Video Packing → cari resi yang sudah selesai → durasi tampil dan
  slider bisa dilompat; tombol **Siapkan MP4** → untuk video packing biasa
  (di bawah 1 menit) berubah jadi **Unduh MP4** dalam ±5–20 detik; video
  panjang butuh ±13 detik per menit rekaman. Kalau selalu baru selesai pada
  menit berikutnya, worker latar belakang tidak bisa dilepas dari Apache —
  cek `application/logs/` untuk baris `minta_video_mp4: php.exe tidak
  ditemukan` dan isi `php_cli_path` di `secrets.php`.

---

## C. Langkah tambahan rilis 18 September 2026 (`cb1a1f3..9a904ee`)

Isi rilis: alur **lost scan** yang terhubung antar meja.

| Commit | Perubahan |
|---|---|
| `e79c4cc` (merge) | **TIM PICKER → Laporan Lost Scan Picker** — antrean resi belum-picker; tombol *Tambahkan Picker* membuat baris picking atas nama picker pilihan tim picker |
| `9a904ee` (merge) | **TIM HO → Scan Paket NDD New** — halaman scan baru berdampingan dengan Scan Paket NDD lama; saat resi belum packing/belum picker, panel pilih packer muncul di bawah kartu status (catat Lost Scan Packer tanpa pindah menu); belum picker → otomatis dilaporkan ke tim picker |
| `59045b1` | **Scan Resi Packer (Webcam)** — popup "belum di-picker" mendapat tombol *Lapor Lost Scan Picker* |

Urutan penyelesaian resi belum-picker: tim picker *Tambahkan Picker* →
packer scan ulang → HO scan ulang. Urutan itu dijaga penjaga `NOT_PICKED`
/ `NOT_PACKED` yang sudah ada di tiap menu, bukan status baru.

### C.1 Perubahan database — semuanya otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke **`2026-09-18.3`**. Pada request pertama setelah
pull (lihat A.7) aplikasi menjalankan:

| Objek | Perubahan | Sumber |
|---|---|---|
| `tbllostscanpicker_pending` | **Tabel baru** (`CREATE TABLE IF NOT EXISTS`): antrean laporan resi belum-picker — `noresi`, `sumber` (PACKER/HO), `dilaporkan_oleh`, `waktu_lapor`, `status` (PENDING/SELESAI/SELESAI_LUAR), `kode_picker`, `diproses_oleh`, `waktu_proses`, `id_resiambilbarang`, `id_lostscanpacker` | `MY_Controller::run_lost_scan_picker_migration()` |
| `menu` | 2 baris baru: **Laporan Lost Scan Picker** (`uri` `lost-scan-picker`, induk TIM PICKER, urutan setelah SCAN COMBINED) dan **Scan Paket NDD New** (`uri` `scan-paket-ndd-new`, induk TIM HO, tepat setelah Scan Paket NDD) | kedua migrasi |
| `roleaccess` | Laporan Lost Scan Picker → role 1, 2, 6 (webmaster, admin, tim retur); Scan Paket NDD New → role 1, 2, 5 (webmaster, admin, ho) | kedua migrasi |

**Tidak ada tabel lama yang diubah strukturnya** (`tbllostscanpacker`,
`tblresiambilbarang`, `tblpacking`, `tblprintresi` tetap). Tidak ada SQL
yang perlu dijalankan manual. Migrasi aman diulang (idempoten): tabel/menu/
akses yang sudah ada tidak dibuat dua kali.

Data yang **ditulis saat fitur dipakai** (bukan saat migrasi):

- `tbllostscanpacker` — baris `PACKER` dari Scan Paket NDD New, baris
  `PICKER` saat tim picker menekan *Tambahkan Picker* (nama picker baru
  diketahui saat itu). Laporan Lost Scan lama membacanya tanpa perubahan.
- `tblresiambilbarang` — baris picking susulan atas nama picker, ditandai
  `nama_komputer = 'LOST SCAN PICKER'`, `pending = ''`, status performa
  picker hari itu (fallback `NORMAL_PICKER`). **KPI picker tidak dicatat.**
- `notifications` — kategori `GENERAL`, "Resi X sudah ditambahkan picker",
  agar packer tahu resi boleh discan ulang.

### C.2 Pastikan folder cache ada (sekali saja)

Penanda migrasi ditulis ke `application/cache/bootstrap_migrasi.txt`. Folder
`application/cache/` **tidak ikut git** (`.gitignore`). Kalau tidak ada,
migrasi tetap jalan tapi **berulang di setiap request** (lambat, ±100 query
+ DDL per scan). Cek dan buat:

```powershell
if (-not (Test-Path application\cache)) { New-Item -ItemType Directory application\cache | Out-Null }
```

### C.3 Verifikasi migrasi

Setelah logout/login (A.7):

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "SHOW TABLES LIKE 'tbllostscanpicker_pending'; SELECT m.id, m.name, m.uri, GROUP_CONCAT(r.roleid ORDER BY r.roleid) AS roles FROM menu m LEFT JOIN roleaccess r ON r.menuid = m.id WHERE m.uri IN ('lost-scan-picker','scan-paket-ndd-new') GROUP BY m.id;"
```

Harus tampil: penanda `2026-09-18.3`; satu tabel; dua baris menu dengan
`roles` `1,2,6` (lost-scan-picker) dan `1,2,5` (scan-paket-ndd-new). Kalau
kosong, versi bootstrap belum terpicu — ulangi A.7.

### C.4 Verifikasi di sisi pengguna

- **HO** (role ho): menu TIM HO → **Scan Paket NDD New** tampil di bawah
  Scan Paket NDD. Scan resi yang belum packing → kartu merah + panel
  "Lost Scan Packer" di bawahnya dengan dropdown packer. Menu lama tetap
  bisa dipakai sebagai cadangan.
- **Packer** (Scan Resi Packer Webcam): scan resi belum-picker → popup
  "Jangan Dipacking" dengan tombol merah **Lapor Lost Scan Picker**; kamera
  tidak mulai merekam.
- **Tim picker** (webmaster/admin/tim retur): TIM PICKER → **Laporan Lost
  Scan Picker**, tab Pending menampilkan laporan dari packer/HO lengkap
  dengan SKU/qty/rak; *Tambahkan Picker* → baris pindah ke tab Selesai.
- Pengguna yang masih login perlu logout/login agar menu baru tampil.

### C.5 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9. Tabel `tbllostscanpicker_pending` dan dua baris menu **tetap
ada** setelah rollback kode (menu akan menampilkan 404 karena controller-nya
tidak ada). Kalau ingin menyembunyikannya sementara tanpa menghapus data:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "UPDATE menu SET isactive = 0 WHERE uri IN ('lost-scan-picker','scan-paket-ndd-new');"
```

Saat kode dipasang lagi, `isactive` perlu dikembalikan ke 1 secara manual —
migrasi hanya membuat menu yang belum ada, tidak mengaktifkan ulang.
