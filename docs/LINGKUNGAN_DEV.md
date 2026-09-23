# Lingkungan Dev (`iresis-dev`)

Dibuat 23 September 2026. Sebelumnya semua pengembangan dilakukan langsung di
folder produksi `C:\xampp\htdocs\new-iresis`, sehingga setiap simpan file langsung
dipakai 16 klien LAN. Contohnya 21 Sep 2026: urutan edit yang terbalik di
`MY_Controller` membuat 22 request scan gagal dalam 16 detik. Sekarang
pengembangan dan uji coba dilakukan di folder dev. Folder produksi hanya
menerima `git pull`.

## 1. Peta produksi vs dev

| | Produksi | Dev |
|---|---|---|
| Folder | `C:\xampp\htdocs\new-iresis` | `C:\xampp\htdocs\iresis-dev` |
| URL | `http://192.168.3.55/new-iresis/` | `http://localhost/iresis-dev/` |
| Database | `iresis_prod` | `iresis_dev` (salinan `iresis_prod`) |
| User MariaDB | `iresis_app` | `iresis_dev`: ALL di `iresis_dev`, **SELECT saja** di `iresis_arsip`, tanpa akses ke `iresis_prod` |
| Cookie session | `siresi_session` | `iresis_dev_session` |
| Pusher | aktif | mati (`pusher_aktif` => FALSE) |
| Folder video packing | `C:/video-packing/` | `C:/video-packing-dev/` |
| `cron_token` | milik produksi | token acak tersendiri |
| Task Scheduler | semua task `IRESIS - *` | **tidak ada**; jangan didaftarkan |

Semua perbedaan di atas diatur lewat `application/config/secrets.php` milik folder
dev (gitignored). Kodenya sama persis dengan produksi.

## 2. Alur kerja

1. Kerjakan di `C:\xampp\htdocs\iresis-dev`. Buat branch dari `master`
   (`feature/*` atau `fix/*`, lihat `docs/DEVELOPMENT_STANDARDS.md` §6).
2. Uji di `http://localhost/iresis-dev/` dengan checklist
   `docs/UJI_REGRESI.md`, lalu tulis hasilnya di pesan merge commit. Kalau
   menambah migrasi atau menu, naikkan `BOOTSTRAP_VERSI`. Migrasinya akan
   jalan ke `iresis_dev` dulu.
3. Merge `--no-ff` ke `master` di folder dev, lalu `git push origin master`.
4. Di folder produksi, jalankan `git pull` sesuai `docs/PANDUAN_PULL_PRODUKSI.md`,
   sebaiknya di luar jam kerja.

Folder produksi tidak lagi diedit langsung. Kalau ada perbaikan darurat yang
terpaksa dikerjakan di sana, commit dan push dari sana, lalu jalankan
`git pull` di folder dev supaya keduanya tidak bercabang.

## 3. Pengaman yang sudah terpasang

- **User DB terbatas.** Kode dev secara teknis tidak bisa menulis ke
  `iresis_prod` karena koneksinya pasti ditolak (*Access denied*). Cron
  `arsip_harian`/`arsip_purna` yang tidak sengaja dijalankan di dev juga gagal
  sebelum sempat menulis, karena user dev hanya punya SELECT di `iresis_arsip`.
  Sebaliknya Mode Arsip dan `pastikan_resi_live()` di dev tetap bisa membaca arsip asli.
- **Cookie session terpisah.** Nama cookie diambil dari `sess_cookie_name` di
  `secrets.php` (bawaan `siresi_session`). Tanpa ini, karena `cookie_path` `/` dan
  `sess_save_path` bawaan PHP, login, logout, atau Mode Arsip di dev ikut mengubah
  session produksi di browser yang sama.
- **Pusher dimatikan.** `Pusher_lib::trigger()` tidak mengirim apa pun kalau
  `pusher_aktif` FALSE. Tanpa ini, uji coba di dev memunculkan notifikasi palsu
  di browser pengguna produksi karena app Pusher-nya sama.

## 4. Yang masih dipakai bersama

- **Instance MariaDB dan Apache.** Query berat di dev ikut memperlambat
  produksi, dan reload Apache berlaku untuk keduanya.
- **Kunci Pusher di JavaScript.** Kunci ini ditulis langsung di `views/main.php`,
  jadi browser yang membuka dev tetap *menerima* notifikasi produksi. Arahnya
  hanya masuk dan tidak berbahaya.
- **Folder foto produk** `C:/foto-produk/`. Dev hanya membacanya. Jangan jalankan
  `cron sinkron_foto_sku` dari folder dev.
- **Akses LAN.** `http://192.168.3.55/iresis-dev/` juga bisa dibuka dari LAN.
  Isi datanya sama dengan produksi, jadi perlakukan sama rahasianya.

## 5. Menyegarkan database dev

Data dev adalah salinan `iresis_prod` per 23 Sep 2026 13.30 dan akan makin basi.
Untuk menyegarkannya, `iresis_dev` harus dikosongkan dulu (`DROP DATABASE`).
**Langkah itu dijalankan user sendiri**, karena aturan proyek melarang Claude
menjalankan `DROP`. Sesudahnya, cara pengisian ulangnya sama dengan pembuatan awal:

1. Ambil dump tabel `iresis_prod` **tanpa** `--databases`, dengan
   `--skip-add-drop-table --skip-triggers --single-transaction --quick`.
   Dengan begitu tidak ada `CREATE DATABASE`, `USE iresis_prod`, atau `DROP TABLE`.
   File backup di `C:\backup-db\otomatis` **tidak boleh** dipakai begitu saja:
   isinya memuat `USE iresis_prod` dan `DROP TABLE`, jadi mengimpornya akan
   menimpa produksi.
2. mysqldump MariaDB 10.4 tetap menyertakan view walau diberi `--ignore-table`.
   Buang blok view dari dump (placeholder dan *Final view structure*, termasuk
   `DROP VIEW`), lalu buat keempat view terpisah **tanpa** klausa
   `DEFINER=iresis_app`. Dengan definer `iresis_app`, view di dev akan berjalan
   dengan hak akses produksi.
3. Cek ulang dump: selain di baris `INSERT`, tidak boleh ada `DROP`, `USE`,
   `DATABASE`, `DEFINER`, maupun rujukan `iresis_prod`/`iresis_arsip`.
4. Impor **sebagai user `iresis_dev`**, bukan root. Kalau ada pernyataan yang
   lolos dan menyasar produksi, pernyataan itu pasti ditolak server.

## 6. Membuat ulang dari nol (PC baru)

1. `git clone https://github.com/johansen08/new-iresis.git C:\xampp\htdocs\iresis-dev`,
   lalu atur `git config user.name/user.email` di repo itu.
2. Salin `vendor/` dari produksi (atau `composer install`). Buat folder
   `application/cache`, `application/logs`, `assets/uploads/cs_complain`,
   `assets/uploads/shopee_penalty`, dan `logs/`.
3. Buat database dan user `iresis_dev` (sebagai root). Perhatikan garis bawah
   yang di-escape, karena `_` di GRANT adalah wildcard:
   ```sql
   CREATE DATABASE iresis_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   CREATE USER 'iresis_dev'@'localhost' IDENTIFIED BY '...';
   CREATE USER 'iresis_dev'@'127.0.0.1' IDENTIFIED BY '...';
   GRANT ALL PRIVILEGES ON `iresis\_dev`.* TO 'iresis_dev'@'localhost', 'iresis_dev'@'127.0.0.1';
   GRANT SELECT ON `iresis\_arsip`.* TO 'iresis_dev'@'localhost', 'iresis_dev'@'127.0.0.1';
   ```
4. Isi database dengan langkah §5.
5. Salin `secrets.php` produksi ke folder dev, lalu ganti `db_username`,
   `db_password`, dan `db_database`. Tambahkan `sess_cookie_name`,
   `pusher_aktif => FALSE`, `video_packing_dir`, dan `cron_token` baru
   (lihat `secrets.php.example`).
