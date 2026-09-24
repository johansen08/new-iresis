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
  branch sesuai `docs/DEVELOPMENT_STANDARDS.md` §6.

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
butuh langkah tambahan (lihat Bagian B).

Setiap merge yang mengubah kode membawa baris `Uji regresi: …` di pesan
merge commit-nya (`docs/UJI_REGRESI.md` §11.3):

```powershell
git log --merges HEAD..origin/master
```

Kalau ada merge yang mengubah kode tanpa baris itu, atau barisnya menyebut
gagal, tunda pull dan tanyakan ke pengembangnya. Aturan ini berlaku untuk
merge sesudah checklist itu dibuat (23 Sep 2026); merge sebelumnya memang
tidak punya baris tersebut. Lalu:

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

### C.0 WAJIB SEKALI: riwayat git ditulis ulang — pakai reset, bukan pull

Pada 18 Sep 2026 seluruh riwayat `origin/master` ditulis ulang (baris
`Co-Authored-By` dibuang dari pesan commit; isi kode, author, dan tanggal
tidak berubah). Akibatnya `git pull` di PC produksi akan **gagal atau
membuat merge ganda**. Untuk rilis ini, ganti langkah A.5 dengan:

```powershell
git fetch origin
git status --short          # harus kosong (A.3); kalau ada perubahan lokal, stash dulu
git reset --hard origin/master
git log --oneline -3        # commit teratas harus 'Merge branch ...' rilis 18 Sep 2026
```

Ini aman karena PC produksi tidak pernah membuat commit sendiri (hanya
menarik). Setelah ini, rilis berikutnya kembali memakai `git pull` biasa.

### C.1 Perubahan database — semuanya otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke **`2026-09-18.3`**. Pada request pertama setelah
pull (lihat A.7) aplikasi menjalankan:

| Objek | Perubahan | Sumber |
|---|---|---|
| `tbllostscanpicker_pending` | **Tabel baru** (`CREATE TABLE IF NOT EXISTS`): antrean laporan resi belum-picker — `noresi`, `sumber` (PACKER/HO), `dilaporkan_oleh`, `waktu_lapor`, `status` (PENDING/SELESAI/SELESAI_LUAR), `kode_picker`, `diproses_oleh`, `waktu_proses`, `id_resiambilbarang`, `id_lostscanpacker` | `MY_Controller::run_lost_scan_picker_migration()` |
| `menu` | 2 baris baru: **Laporan Lost Scan Picker** (`uri` `lost-scan-picker`, induk TIM PICKER, urutan setelah SCAN COMBINED) dan **Scan Paket NDD New** (`uri` `scan-paket-ndd-new`, induk TIM HO, setelah Laporan Paket NDD) | kedua migrasi |
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

### C.6 Hasil pemeriksaan skema produksi (18 Sep 2026, sebelum pull)

Skema `iresis_prod` di 192.168.3.55 (MariaDB 10.4.32) diperiksa read-only
terhadap asumsi kode rilis ini: semua tabel/kolom/enum/role/menu induk yang
dipakai ada dan sesuai; `tbllostscanpicker_pending` belum ada; 15 akun packer
aktif semuanya terhubung ke `tblpegawai`; Master Picker aktif 65; tidak ada
`noresi` di `tbllostscanpacker` yang punya lebih dari satu `lost_type`.
Perbedaan DB lokal vs produksi hanya index tambahan di lokal (hasil script
optimasi) — tidak berpengaruh pada kode.

---

## D. Langkah tambahan rilis 18 September 2026 sore (`d17297e..8a42420`)

Isi rilis: menu **TIM PACKER → Salah Ambil Special** — untuk batch resi
spesial (tepat 1 SKU / qty 1) yang salah diambil picker. Packer mengisi
"SKU seharusnya" dan "SKU terambil" sekali (field ber-live-search ke master
SKU), lalu men-scan semua resi berantai; tiap resi yang lolos validasi
langsung tercatat di `tblmasalahpicker` sebagai SALAH AMBIL, sama persis
dengan hasil modal Masalah Picker di Scan Resi Packer. Daftar Masalah Picker
(lama & New), KPI picker, dan Error Recap membacanya tanpa perubahan.

| Commit | Perubahan |
|---|---|
| `8a42420` (merge) | Controller `Salah_ambil_special.php`, model `Salah_ambil_special_fcd.php`, view `salah_ambil_special/index.php`, 4 route, migrasi menu di `MY_Controller.php`, dokumen spec/plan di `docs/superpowers/` |

Riwayat git **tidak** ditulis ulang pada rilis ini — kembali pakai `git pull`
biasa (A.5). Prosedur C.0 hanya berlaku sekali untuk rilis sebelumnya; kalau
PC produksi belum pernah menjalankan C.0, lakukan C.0 dulu, rilis ini ikut
tertarik di dalamnya.

### D.1 Perubahan database — hanya menu + hak akses, otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke **`2026-09-18.4`**. Pada request pertama setelah
pull (A.7) aplikasi menjalankan `MY_Controller::run_salah_ambil_special_migration()`:

| Objek | Perubahan |
|---|---|
| `menu` | 1 baris baru: **Salah Ambil Special** (`uri` `salah-ambil-special`, icon `fa fa-exchange`, `sortorder` 12, induk = induk menu `packer/scan_packer` yaitu grup TIM PACKER) |
| `roleaccess` | menu itu → role **1** (webmaster) dan **4** (client packer) — sama dengan Scan Resi Packer (Webcam) |

**Tidak ada tabel baru, tidak ada kolom baru, tidak ada tabel lama yang
diubah.** Tidak ada SQL manual. Migrasi idempoten: menu/akses yang sudah ada
tidak dibuat dua kali. Cadangan kalau migrasi otomatis tidak terpicu (D.2
kosong padahal A.7 sudah diulang): `sql_migrations/salah_ambil_special_menu.sql`
berisi INSERT yang sama, aman diulang:

```powershell
Get-Content sql_migrations\salah_ambil_special_menu.sql -Raw | & C:\xampp\mysql\bin\mysql.exe -u root -t iresis_prod
```

Data yang **ditulis saat fitur dipakai**: satu baris `tblmasalahpicker` per
resi yang lolos (`sku` = SKU seharusnya, `sku_salah` = SKU terambil, `qty` 1,
`qty_bermasalah` 1, `id_typemasalah` 4, `status` 0, `created_by` = user
packer). Resi yang ditolak tidak menulis apa pun.

Kolom yang dibaca kode dan harus ada di produksi (semuanya sudah dipakai
menu lain, jadi tidak ada asumsi baru): `tblsku.id_sku/nama_sku/no_rak`,
`tblprintresi.noresi/created_at`, `tbldetailprintresi.id_resi/sku/jumlah/id_detail_resi`,
`tblpacking.id_resi`, `tblresiambilbarang.id_resi/yangambil_pegawai`,
`tblpegawai.kode_pegawai/nama_pegawai`, `tbluser.id_pegawai/name`.

### D.2 Verifikasi migrasi

Setelah logout/login (A.7):

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "SELECT m.id, m.name, m.uri, m.parentid, m.isactive, GROUP_CONCAT(r.roleid ORDER BY r.roleid) AS roles FROM menu m LEFT JOIN roleaccess r ON r.menuid = m.id WHERE m.uri = 'salah-ambil-special' GROUP BY m.id;"
```

Harus tampil: penanda `2026-09-18.4`; **satu** baris menu dengan `roles`
`1,4` dan `parentid` sama dengan menu `packer/scan_packer`. Kalau kosong,
versi bootstrap belum terpicu — ulangi A.7. Kalau ada dua baris dengan `uri`
sama (bootstrap sempat jalan dua kali serentak), nonaktifkan yang `id`-nya
lebih besar: `UPDATE menu SET isactive = 0 WHERE id = <id besar>`.

### D.3 Verifikasi di sisi pengguna

Login sebagai **packer** (role 4) setelah logout/login:

1. TIM PACKER → **Salah Ambil Special** tampil setelah Scan Resi Packer
   (Webcam). Klik → halaman tampil tanpa reload, kursor di field "SKU
   seharusnya".
2. Ketik 2–3 huruf awal kode SKU → dropdown saran `KODE — rak X` muncul;
   pilih → nama barang + rak tampil di bawah field, fokus pindah ke field
   kedua. Isi SKU terambil (beda dari yang pertama) → **Kunci & Mulai Scan**
   → field terkunci, field resi aktif.
3. Scan resi nyata yang **bukan** 1 SKU/1 qty → baris merah "Bukan resi
   spesial (...)", bunyi gagal, tidak ada data tersimpan. Ini cukup sebagai
   uji asap; jangan scan resi yang benar-benar salah ambil hanya untuk uji,
   karena akan masuk antrean CS.
4. Role selain 1/4 (mis. tim retur) tidak melihat menu; URL langsung
   dibalas panel "Role akun Anda tidak punya akses".

Yang perlu disampaikan ke packer: menu ini **hanya** untuk batch resi 1 SKU /
1 qty yang salah ambil; resi campuran tetap lewat modal Masalah Picker di
Scan Resi Packer. Baris merah = ditolak beserta alasannya, tidak tersimpan.
Tidak ada tombol batal — kalau salah scan resi yang lolos, laporkan ke CS
lewat Daftar Masalah Picker.

### D.4 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9. Baris menu + `roleaccess` **tetap ada** setelah rollback kode
(menu akan 404 karena controller-nya tidak ada). Untuk menyembunyikannya
tanpa menghapus:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "UPDATE menu SET isactive = 0 WHERE uri = 'salah-ambil-special';"
```

Saat kode dipasang lagi, kembalikan `isactive = 1` manual — migrasi hanya
membuat menu yang belum ada. Baris `tblmasalahpicker` yang sudah ditulis
fitur ini adalah laporan salah ambil sungguhan; biarkan CS memprosesnya
seperti biasa.

---

## E. Langkah tambahan rilis 19 September 2026 — rekaman webcam 1080p + VP9

Isi rilis: **Scan Resi Packer (Webcam)** merekam **1920×1080 @ 15 fps**
(sebelumnya 1280×720) dengan codec **VP9** (fallback VP8 di browser lama).
Tanpa suara — memang sejak awal tidak pernah ada track audio. Hanya
`assets/js/packer_video.js` yang berubah perilakunya; sisi server (upload
potongan, remux, antrian MP4, halaman Video Packing CS) tidak berubah karena
VP9 tetap WebM. **Tidak ada migrasi, tidak ada SQL.** Kembali pakai
`git pull` biasa (A.5).

| Commit | Perubahan |
|---|---|
| branch `feature/packer-webcam-1080p-vp9` | resolusi 1080p, bitrate per codec (VP9 2,0 Mbps / VP8 2,7 Mbps), VP9 didahulukan, panel kamera menampilkan codec |

Kenapa tidak "dikompres saat simpan lalu diekstrak di CS": diukur pada
rekaman nyata 1080p 60 detik (11,6 MB) — gzip hanya hemat 2,8 %, dan encode
ulang H.264 "tanpa mengurangi kualitas" (CRF 20–23) justru **membesar**
menjadi 17–31 MB, karena rekaman webcam sudah lossy dan encoder ikut
menyimpan noise. Ukuran ditentukan bitrate saat merekam; VP9 memberi
kualitas setara VP8 pada bitrate ~25–30 % lebih rendah, itu penghematan
satu-satunya yang nyata.

### E.1 Kapasitas disk `C:\video-packing\` — WAJIB dicek sebelum pull

Ini kebalikan dari rilis 15 Sep (B.6) yang **menurunkan** ke 720p karena
disk. Perkiraan baru:

| Setelan | Per PC per jam | ~30 PC × 6 jam/hari |
|---|---|---|
| 720p VP8 1,2 Mbps (sebelum rilis ini) | ≈ 540 MB | ≈ 100 GB/hari |
| **1080p VP9 2,0 Mbps (rilis ini)** | **≈ 900 MB** | **≈ 160 GB/hari** |
| 1080p VP8 2,7 Mbps (PC yang jatuh ke VP8) | ≈ 1,2 GB | ≈ 215 GB/hari |

Per 15 Sep 2026 sisa drive C: produksi ±375 GB dan `C:\video-packing\`
sedrive dengan MariaDB. Dengan 160 GB/hari, drive penuh dalam **2–3 hari
kerja** kalau belum ada penghapusan/pemindahan rekaman lama. Sebelum pull:

```powershell
Get-PSDrive C | Select-Object @{n='Terpakai_GB';e={[math]::Round($_.Used/1GB)}}, @{n='Sisa_GB';e={[math]::Round($_.Free/1GB)}}
(Get-ChildItem C:\video-packing -File | Measure-Object Length -Sum).Sum / 1GB
```

Kalau sisa ruang tidak cukup untuk kebijakan retensi yang disepakati,
pilihannya: pindahkan `video_packing_dir` di `secrets.php` ke drive lain,
atau tunda rilis ini. Menurunkan bitrate (`BITRATE_VP9` / `BITRATE_VP8` di
`packer_video.js`) juga bisa, tapi kualitas 1080p-nya ikut turun.

### E.2 Verifikasi di sisi pengguna

- **Packer**: buka Scan Resi Packer (Webcam) → panel kamera menampilkan
  `Resolusi: 1920×1080 @ 15 fps · VP9`. Kalau tertulis `· VP8`, browser PC
  itu tidak punya encoder VP9 (Chrome sangat lama) — rekamannya tetap jalan,
  hanya berkasnya ~35 % lebih besar. Kalau oranye ("di bawah 1920×1080"),
  webcam PC itu hanya sanggup 720p — catat PC-nya; ini bukan kesalahan.
- Perhatikan CPU PC packer saat merekam (Task Manager → chrome): encode
  VP9 1080p lebih berat dari VP8 720p. Kalau preview kamera patah-patah atau
  PC melambat saat packing, laporkan PC-nya — bitrate/resolusi bisa
  diturunkan per rilis, belum ada setelan per PC.
- **CS**: Video Packing → cari resi yang direkam setelah rilis → video
  diputar seperti biasa (Chrome/Edge/Firefox memutar VP9 tanpa apa pun).
  **Siapkan MP4** tetap bekerja (ffmpeg membaca VP9); hasil MP4-nya bisa
  lebih besar dari WebM-nya, itu normal (lihat catatan di atas).
- Browser packer harus memuat `packer_video.js` versi baru: `main.php`
  menambahkan `?v=<filemtime>` otomatis, jadi cukup muat ulang halaman
  (F5). Panel yang masih menampilkan `1280×720` tanpa nama codec berarti
  masih memakai JS lama.

### E.3 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9 — tidak ada data yang perlu dipulihkan. Rekaman yang sudah
terlanjur dibuat 1080p/VP9 tetap bisa diputar dan dikonversi MP4 oleh
versi lama (server memang tidak pernah membedakan codec).

---

## F. Rilis 21 September 2026 — aplikasi tetap jalan saat internet putus

Dikerjakan **langsung di PC produksi** (semua sudah aktif di sana sejak 21 Sep
pagi), jadi bagian ini untuk PC lain (dev/cadangan) yang menarik commit-nya.

| Commit | Perubahan |
|---|---|
| `fix/aset-lokal-offline` | Semua library/font/ikon dari `assets/` (tanpa CDN); timeout Pusher 5 detik |
| `feature/foto-sku-lokal` + `feature/foto-produk-url-dulu-lokal` | Foto produk: URL asli dulu, cadangan lokal `C:\foto-produk\`; kolom `tblsku.foto_lokal`; cron `sinkron_foto_sku` |

### F.1 Database — otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke `2026-09-21.1`: `run_foto_sku_lokal_migration()`
menambah kolom `tblsku.foto_lokal VARCHAR(64) NULL` (hanya kolom, tidak
menyentuh baris). Kolom diisi oleh cron, bukan migrasi. Cek:

```sql
SHOW COLUMNS FROM tblsku LIKE 'foto_lokal';
SELECT COUNT(*) total, SUM(foto_lokal IS NOT NULL) terisi FROM tblsku WHERE link_foto LIKE 'http%';
```

### F.2 Di luar repo — WAJIB di PC yang melayani klien

1. **Folder** `C:\foto-produk\` (atau ubah `foto_produk_dir` di `secrets.php`).
2. **Apache**: salin `C:\xampp\apache\conf\extra\httpd-iresis-foto-produk.conf`
   (isinya ada di `docs/DEVELOPMENT_STANDARDS.md` §5 dan di PC produksi) lalu
   tambahkan `Include conf/extra/httpd-iresis-foto-produk.conf` di akhir
   `httpd.conf`; `httpd -t` harus `Syntax OK`; restart Apache. Tanpa ini
   `/foto-produk/...` 404 — aplikasi tetap jalan (URL asli dipakai dulu), hanya
   cadangan offline-nya tidak ada.
3. **Task Scheduler** *IRESIS - Sinkron foto SKU*: harian 23.00, aksi
   `wscript.exe //B //Nologo "C:\xampp\htdocs\new-iresis\scripts\sinkron_foto_sku_senyap.vbs"`,
   batas 1 jam, jangan dobel. Isi awal (8.550 URL, ±200 MB, ±10 menit) bisa
   dijalankan manual: `C:\xampp\php\php.exe index.php cron sinkron_foto_sku 3600 10`.

### F.3 Verifikasi di sisi pengguna

- Matikan internet sebentar (cabut WAN router) → buka Scan Resi Packer: ikon,
  filter tanggal, dan foto produk tetap tampil (foto muncul ≤4 detik setelah
  URL asli gagal). Log `C:\foto-produk\sinkron.log` berisi ringkasan per putaran.
- Console browser tidak boleh ada request ke `cdnjs`, `jsdelivr`, `googleapis`.

### F.4 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9. Kolom `foto_lokal` boleh dibiarkan (tidak dipakai versi lama).
Backup `tblsku` sebelum pengisian pertama: tabel `tblsku_bak_20260921`.

---

## G. Rilis 24 September 2026 — menu Pemenuhan Kirim Harian

Isi rilis: menu **TIM MONITORING → Pemenuhan Kirim Harian**: resi masuk, resi
wajib keluar hari ini, progres picker/packer/HO, rincian 1 Qty / >1 Qty, dan
hitungan OT/perbantuan packer. Hanya **membaca** data resi; tidak ada tombol
yang menulis. Aturan dan hasil verifikasinya: `docs/PEMENUHAN_KIRIM_HARIAN.md`.

| Branch | Perubahan |
|---|---|
| `feature/pemenuhan-kirim-harian` | Model `Pemenuhan_kirim_fcd.php`, 2 method + 2 route di `Monitoring`, view `monitoring/pemenuhan_kirim_harian.php`, migrasi di `MY_Controller.php`, smoke test ditambah 4 pemeriksaan |

Riwayat git tidak ditulis ulang, jadi pakai `git pull` biasa (A.5).

### G.1 Database — hanya menu, hak akses, dan setelan; otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke **`2026-09-24.1`**. Pada request pertama setelah pull
(A.7) aplikasi menjalankan `MY_Controller::run_pemenuhan_kirim_harian_migration()`:

| Objek | Perubahan |
|---|---|
| `menu` | 1 baris baru: **Pemenuhan Kirim Harian** (`uri` `monitoring/pemenuhan-kirim-harian`, icon `fa fa-truck`, `sortorder` 10 = paling atas di grup, induk = induk menu Laporan Pesanan Masuk yaitu TIM MONITORING id 83) |
| `roleaccess` | menu itu → role yang sama dengan Laporan Pesanan Masuk (per 24 Sep: **1, 2, 6**) |
| `tb_config_operasional` | 3 baris baru bila belum ada: `pkh_jam_selesai_packer` = `18:00`, `pkh_default_packer` = `8`, `pkh_batas_per_packer` = `120` |

Tidak ada tabel atau kolom baru dan tidak ada SQL manual. Migrasi idempoten.
Menu memakai berkas cache `application/cache/pkh_*.json` dan `pkh_*.lock`
(folder yang sama dengan penanda bootstrap, jadi tidak perlu disiapkan).

### G.2 Verifikasi migrasi

Setelah logout/login (A.7):

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "SELECT m.id, m.name, m.parentid, m.sortorder, GROUP_CONCAT(r.roleid ORDER BY r.roleid) AS roles FROM menu m LEFT JOIN roleaccess r ON r.menuid = m.id WHERE m.uri = 'monitoring/pemenuhan-kirim-harian' GROUP BY m.id; SELECT kunci, nilai FROM tb_config_operasional WHERE kunci LIKE 'pkh_%';"
```

Harus tampil: penanda `2026-09-24.1`; **satu** baris menu dengan `parentid` 83
dan `roles` 1,2,6; tiga baris setelan `pkh_*`. Kalau ada dua baris menu dengan
`uri` sama, nonaktifkan yang `id`-nya lebih besar
(`UPDATE menu SET isactive = 0 WHERE id = <id besar>`).

### G.3 Verifikasi di sisi pengguna

Login sebagai webmaster, admin, atau tim retur (role 1, 2, 6) setelah
logout/login:

1. TIM MONITORING → **Pemenuhan Kirim Harian** tampil paling atas. Halaman
   terbuka tanpa reload dan tanpa teks JSON mentah. Buka pertama ±1 detik.
2. Angka wajib keluar = sisa kemarin + pesanan s/d 12.00 + TikTok 12.00–15.00.
   Kotak Packer "Belum" sama dengan total "Belum packer" di Detail Belum Selesai.
3. Ubah jumlah packer atau batas per packer: beban dan kesimpulan langsung
   berubah. Ganti tanggal ke kemarin: judul berubah jadi "rekap akhir hari".
4. Biarkan terbuka lebih dari 1 menit: "data jam" ikut berganti tanpa reload.
5. Log error PHP (A.8 langkah 3) tidak berisi baris dari
   `Pemenuhan_kirim_fcd`.

### G.4 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9. Baris menu, `roleaccess`, dan setelan **tetap ada** setelah rollback
kode (menu akan 404 karena method-nya tidak ada). Untuk menyembunyikannya tanpa
menghapus:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "UPDATE menu SET isactive = 0 WHERE uri = 'monitoring/pemenuhan-kirim-harian';"
```

Saat kode dipasang lagi, kembalikan `isactive = 1` manual. Migrasi hanya
membuat menu yang belum ada. Berkas `application/cache/pkh_*` boleh dibiarkan.

---

## H. Rilis 24 September 2026 (lanjutan) — Pemenuhan Kirim Harian: upload telat + Lihat detail

Isi rilis:

1. Resi wajib yang di-upload ke IRESIS **sesudah 16.00** (picking sudah tutup)
   dipisah dari "Belum" di kotak Picker/Packer/HO dan diberi baris sendiri di
   Detail Belum Selesai. Angka wajib dan persen tidak berubah. Hitungan
   OT/perbantuan tidak lagi memasukkan resi itu (`PEMENUHAN_KIRIM_HARIAN.md` §2.1).
2. Tombol **Lihat detail** di samping tiap "Belum" (dan **Lihat** di samping
   "Upload telat"): popup daftar resi dengan cari, filter, Salin no resi, dan
   **Unduh Excel** hasil filter (§1.1). Hanya membaca data.

| Branch | Perubahan |
|---|---|
| `feature/pkh-upload-telat` | `Pemenuhan_kirim_fcd.php` (kolom `telat` di query, hasil `telat`, setelan baru, versi cache), view `monitoring/pemenuhan_kirim_harian.php`, 1 setelan di migrasi `MY_Controller.php` |
| `feature/pkh-lihat-detail` | `Pemenuhan_kirim_fcd.php` (`sql_per_resi()` dipakai bersama, `daftar_belum()`, `hitung_sekali()`), 2 method + 2 route di `Monitoring` (`-detail`, `-excel`), popup di view, smoke test +3 pemeriksaan |

Riwayat git tidak ditulis ulang, jadi pakai `git pull` biasa (A.5).

### H.1 Database — 1 setelan, otomatis lewat migrasi

`BOOTSTRAP_VERSI` naik ke **`2026-09-24.2`**. Request pertama setelah pull
(A.7) menambah 1 baris `tb_config_operasional` bila belum ada:
`pkh_jam_upload_terlambat` = `16:00`. Menu dan hak akses tidak berubah. Cache
lama `application/cache/pkh_snap_*` tidak dibaca lagi (kunci cache baru
`pkh_snap2_*`) dan boleh dibiarkan.

### H.2 Verifikasi

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
& C:\xampp\mysql\bin\mysql.exe -u root iresis_prod -e "SELECT kunci, nilai FROM tb_config_operasional WHERE kunci LIKE 'pkh_%';"
```

Harus tampil penanda `2026-09-24.2` dan **empat** baris `pkh_*`. Lalu buka
menu, pilih tanggal **23 Sep 2026**: kotak Picker "Belum: 0" dengan baris
oranye "Upload telat: 5", Packer dan HO "Belum: 1" dengan "Upload telat: 5";
persen tetap 99,9%. (Kalau status SPXID067708609229 sudah diperbarui jadi
CANCELED, Packer dan HO menjadi "Belum: 0".)

Kembali ke hari ini, klik **Lihat detail** di kotak Packer: popup terbuka,
jumlah di tab "Belum packer" sama dengan angka "Belum" di kotak. Ketik
sebagian no resi di kotak cari, lalu **Unduh Excel**: berkas
`Belum_packer_<tanggal>_<jam>.xlsx` terunduh dan isinya sama dengan daftar di
layar.

### H.3 Kalau perlu kembali ke versi sebelumnya

Ikuti A.9. Baris setelan `pkh_jam_upload_terlambat` boleh dibiarkan; versi lama
tidak membacanya.

---

## I. Rilis 24 September 2026 (sore) — Pemenuhan Kirim Harian: aturan wajib ikut batas kirim MP

Isi rilis (koreksi dari user sesudah rilis H):

1. Wajib keluar = sisa kemarin + resi yang **batas kirim MP ≤ hari ini**
   (standar MP; Lazada/reseller tanpa batas kirim tetap pesanan s/d 12.00) +
   pesanan **TikTok s/d 15.00** (tambahan operasional). Sebelumnya bagian MP
   memakai "pesanan s/d 12.00", sehingga resi Shopee yang dipesan pagi tetapi
   batas kirimnya besok ikut dihitung wajib.
2. Tampilan **Upload telat** dari rilis H dihapus; kotak kembali "Belum" +
   tombol Lihat detail.
3. Resi yang dicatat di **Daftar Cancel Order** (Tim Resi → Scan Cek Cancel)
   keluar dari semua angka, tanpa menunggu status CANCELED dari upload Jubelio.

| Branch | Perubahan |
|---|---|
| `fix/pkh-aturan-batas-kirim` | `Pemenuhan_kirim_fcd.php` (grup TA, grup TM dihapus, cek `tblcancelorder`, kolom `telat` dihapus, `VERSI_CACHE` 3), view, kolom Excel "Upload Telat" dihapus, setelan `pkh_jam_upload_terlambat` dikeluarkan dari migrasi, smoke test |

Tidak ada migrasi baru dan `BOOTSTRAP_VERSI` tidak naik (tetap `2026-09-24.2`).
Baris setelan `pkh_jam_upload_terlambat` yang sudah terbentuk dibiarkan.

### I.1 Verifikasi

Buka menu, pilih **23 Sep 2026**: wajib keluar **7.946**, kotak Picker
"Belum: 3", Packer dan HO "Belum: 4" (3 resi TikTok + SPXID067708609229).
Sesudah tim resi scan SPXID067708609229 di Scan Cek Cancel dan cache 10 menit
lewat, Packer dan HO menjadi "Belum: 3". Tidak ada baris oranye "Upload telat".

### I.2 Kalau perlu kembali ke versi sebelumnya

`git reset --hard 6901460` (A.9). Tidak ada perubahan database yang perlu
dikembalikan.

---

## J. Rilis 24 September 2026 (sore, lanjutan) — popup Lihat detail tanpa rak dan jam tutup kurir

Popup dan Excel Pemenuhan Kirim Harian tidak lagi menampilkan no rak (permintaan
user) dan "tutup HH.MM" di bawah kurir (`tblkurir.jam_batas_kirim` tidak
terverifikasi). Kolom Excel "Rak" dihapus. Branch `fix/pkh-detail-tanpa-rak`,
hanya model/controller/view; tanpa migrasi, `VERSI_CACHE` 4. Verifikasi: buka
popup, kolom Kurir hanya nama kurir, kolom SKU hanya "SKU ×qty".
