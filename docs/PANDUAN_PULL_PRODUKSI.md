# Panduan Pull ke PC Produksi

Panduan menarik perubahan dari GitHub (`origin/master`) ke PC produksi IRESIS.
Bagian A berlaku untuk **setiap** pull; Bagian B adalah langkah tambahan untuk
rilis tertentu yang butuh lebih dari sekadar `git pull`.

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
shift pagi atau setelah `cron_laporan_sore` selesai.

### A.2 Backup database dulu

Beberapa rilis menjalankan migrasi tabel otomatis saat login pertama
(lihat `BOOTSTRAP_VERSI` di `application/core/MY_Controller.php`).
Migrasi tidak bisa di-undo dengan `git`, jadi backup dulu:

```powershell
$stamp = Get-Date -Format "yyyyMMdd-HHmm"
cmd /c "`"C:\xampp\mysql\bin\mysqldump.exe`" -u root --routines --triggers --single-transaction iresis-prod > C:\backup-db\iresis-prod-$stamp.sql"
```

Pastikan folder `C:\backup-db\` ada, dan cek ukuran file hasilnya bukan 0 KB.

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

Migrasi dan pembaruan menu berjalan **sekali** saat request pertama setelah
login oleh pengguna mana pun. Jadi setelah pull: **logout, lalu login lagi**
dengan akun webmaster, buka satu menu apa saja. Tandanya berhasil: berkas
`application/cache/bootstrap_migrasi.txt` berisi versi baru:

```powershell
Get-Content application\cache\bootstrap_migrasi.txt
```

Pengguna lain yang masih login akan melihat menu baru setelah mereka
logout/login (cache menu di session dikunci per versi bootstrap).

### A.8 Verifikasi cepat

1. Buka `http://localhost:8080/new-iresis/` — halaman login tampil tanpa
   teks `PHP Error`/`Warning` di atasnya.
2. Login, buka 2–3 menu yang disentuh rilis (lihat `git log`). Halaman yang
   tampil sebagai **teks JSON mentah** berarti ada output nyasar di PHP —
   lihat `CLAUDE.md` bagian "SPA semu".
3. Cek log error PHP hari ini:

```powershell
Get-Content application\logs\log-$(Get-Date -Format yyyy-MM-dd).php -Tail 40
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
| `c6bb95c`, `a0e52e6` | Rekaman webcam packer 1920×1080 @ 15 fps, 2,5 Mbps (~1,1 GB/jam per PC) |
| `010878c` | Panel kamera menampilkan resolusi aktual webcam (oranye kalau di bawah 1080p) |
| `c585bf1` | Finalisasi WebM dengan ffmpeg (durasi/seek) + tombol "Siapkan MP4" di menu Video Packing |

Tanpa langkah di bawah, aplikasi tetap jalan: rekaman tetap tersimpan dan
bisa diputar, hanya durasi/seek tidak diperbaiki dan tombol MP4 akan
menampilkan "mengantre" tanpa pernah selesai.

### B.1 Pasang ffmpeg

Pilih salah satu:

```powershell
winget install Gyan.FFmpeg
```

atau unduh build "release full" dari https://www.gyan.dev/ffmpeg/builds/,
ekstrak ke `C:\ffmpeg\` sehingga ada `C:\ffmpeg\bin\ffmpeg.exe` dan
`C:\ffmpeg\bin\ffprobe.exe`.

Cek:

```powershell
& C:\ffmpeg\bin\ffmpeg.exe -version
```

(Kalau lewat winget, cari lokasinya dengan `(Get-Command ffmpeg).Source`.)

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
& C:\xampp\mysql\bin\mysql.exe -u root iresis-prod -e "SHOW COLUMNS FROM tblvideopacking LIKE 'mp4_status';"
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
difinalisasi (maks. 20 rekaman per jalan).

### B.5 Daftarkan task terjadwal (tiap 1 menit)

Jalankan PowerShell **sebagai Administrator**:

```powershell
.\setup_task_finalisasi_video.ps1
```

Skrip itu membuat task `IRESIS - Finalisasi Video` yang memanggil
`cron_finalisasi_video.bat` tiap menit. Cek di Task Scheduler bahwa task
ada dan "Last Run Result" = `0x0` setelah satu-dua menit. Log-nya di
`logs\cron_finalisasi_video.log`.

### B.6 Cek kapasitas disk `C:\video-packing\`

Ukuran rekaman naik ~6× dibanding setelan lama (≈ 1,1 GB per jam per PC
packer). Dengan ~30 PC × 6 jam/hari ≈ **200 GB/hari**. Pastikan drive-nya
cukup dan sepakati kebijakan retensi (berapa hari rekaman disimpan) —
belum ada penghapusan otomatis di aplikasi.

### B.7 Verifikasi di sisi pengguna

- **Packer**: buka Scan Resi Packer (Webcam) → di panel kamera muncul baris
  `Resolusi: 1920×1080 @ 15 fps`. Kalau oranye ("di bawah 1920×1080"),
  webcam PC itu tidak sanggup 1080p — catat PC-nya.
- **CS**: Video Packing → cari resi yang sudah selesai → durasi tampil dan
  slider bisa dilompat; tombol **Siapkan MP4** → dalam 1–3 menit berubah
  jadi **Unduh MP4**.
