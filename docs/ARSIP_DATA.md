# Arsip Data — `iresis_prod` ringan, data lama tetap mudah diakses

Dibuat 21 Sep 2026. Status tahapan ada di §6.

## 1. Latar belakang & keputusan

Per 21 Sep 2026 `iresis_prod` berukuran 3,28 GB (indeks 1,96 GB + data 1,33 GB), tumbuh
±300 MB/bulan. Lima tabel keluarga resi (`tblprintresi`, `tbldetailprintresi`,
`tblresiambilbarang`, `tblpacking`, `tblresikeluar`) memakan 3 GB di antaranya. Data resi
baru dimulai 20 Okt 2025.

Kebutuhan harian (picking, packing, handover, monitoring) hanya menyentuh ±1 bulan
terakhir. Kebutuhan data lama jarang: retur, komplain CS, laporan, KPI. Sebaran jeda
cetak resi → retur: 89 % ≤ 30 hari, **99,2 % ≤ 60 hari**, 99,5 % ≤ 90 hari.

Keputusan (21 Sep 2026):

| Hal | Keputusan |
|---|---|
| Retensi keluarga resi di prod | **60 hari** dari aktivitas terakhir resi |
| Isi `iresis_arsip` | **Semua data sejak awal** (superset), bukan hanya sisa |
| Akses data lama | Mode Arsip (read-only) untuk role yang diberi akses menu-nya — awal: webmaster, admin, **tim retur, tim finance**; dibuka ke role lain bila butuh; alur retur/CS menarik resi lama ke prod otomatis |
| Jadwal | Tiap malam **01.00** (PC menyala 24 jam; backup 30 menit hanya 07–22, task lain 23.00) |

## 2. Prinsip

```
MariaDB (satu instance, satu koneksi aplikasi)
├── iresis_prod    ← keluarga resi 60 hari terakhir + tabel master; ±0,7 GB saat stabil
└── iresis_arsip   ← SEMUA data s/d putaran terakhir; tabel & kolom persis sama dengan prod
```

- Karena satu server, job memakai `REPLACE INTO iresis_arsip.t SELECT * FROM iresis_prod.t`
  — tidak ada koneksi kedua, tidak ada dump/impor, tidak ada data lewat PHP.
- Arsip **superset**: laporan/KPI di Mode Arsip mencakup rentang tanggal apa pun dalam satu
  query. Aturan untuk tim: *"Live = 60 hari terakhir, cepat. Arsip = lengkap sampai kemarin
  malam."*
- Skema arsip dibuat `CREATE TABLE … LIKE prod` (PK/indeks ikut, FK tidak — sengaja, agar
  urutan salin bebas). View dibuat ulang tiap putaran dengan nama DB diganti.
- Nama DB prod **tidak** ditulis di config: diambil dari koneksi aktif (`secrets.php`),
  supaya job tidak bisa salah arah. Nama arsip di `application/config/arsip.php`.

## 3. Komponen

| Berkas | Peran |
|---|---|
| `application/config/arsip.php` | Setelan: nama DB arsip, retensi, batas tabel "penuh", ukuran batch, jeda, pola tabel yang dikecualikan |
| `application/models/Arsip_fcd.php` | Seluruh logika: cek skema, sinkron penuh/bertahap, laporan perkiraan purna, watermark, kunci |
| `application/controllers/Cron.php::arsip_harian()` | Pemicu CLI/HTTP, cetak log, ringkasan ke `application/logs/` via `_log()` |
| `scripts/arsip_harian_senyap.vbs` | Pembungkus tanpa jendela untuk Task Scheduler; stdout → `logs/arsip_harian.log` |
| `iresis_arsip._arsip_status` | Watermark per tabel (`pk_terakhir`), mode, baris disalin, `COUNT(*)` arsip, durasi |
| `iresis_arsip._arsip_log` | Jejak tiap putaran/tabel (OK/FAIL + pesan) |
| `application/cache/arsip_harian.lock` | Kunci anti-putaran-ganda (basi setelah 6 jam) |
| `application/helpers/arsip_helper.php` | `pastikan_resi_live($noresi)` (autoload) → `Arsip_fcd::tarik_balik()`: tarik keluarga resi dari arsip ke prod bila hanya ada di arsip; peta tabel di config `keluarga_resi` |
| `Cron.php::cek_resi_live($noresi)` | Diagnostik CLI: status resi (live / ditarik / tidak_ada) — perilaku sama dengan helper |
| `scripts/backup_arsip_senyap.vbs` | Task "IRESIS - Backup arsip 02.30" → `C:\backup-db\backup_db.ps1 -Database iresis_arsip -Simpan 7 -Paksa` |

Hak akses: user aplikasi (`iresis_app`@`localhost` dan `@127.0.0.1`) diberi
`GRANT ALL PRIVILEGES ON iresis_arsip.*` pada 21 Sep 2026. `REPLACE` membutuhkan INSERT +
DELETE **di tabel arsip** — bukan di prod. Sinkron dan tarik-balik tidak pernah menjalankan DELETE/UPDATE di prod; satu-satunya yang menghapus di prod adalah purna (§4c), dengan gerbang keselamatan dan verifikasi per batch.

## 4. Cara kerja satu putaran (`cron arsip_harian`)

1. **Kunci** — tolak kalau putaran lain masih jalan.
2. **Sesi** — `sql_mode = ''` (tanggal `0000-00-00` tersalin apa adanya), isolasi
   `READ COMMITTED` (SELECT sumber tidak memasang shared lock pada baris prod),
   `db_debug` mati + semua kueri lewat pembungkus yang menangkap `mysqli_sql_exception`.
3. **Siapkan DB** — `CREATE DATABASE IF NOT EXISTS`, tabel `_arsip_status`, `_arsip_log`.
4. **Cek skema** — untuk tiap tabel prod (kecuali pola `_corrupt_`, `_bak_`, `_backup`, `_dup_`, `tmp_`):
   - belum ada di arsip → `CREATE TABLE LIKE`;
   - nama/tipe/urutan kolom beda → **dilewati** dan dicatat FAIL (tidak ditebak; samakan
     skemanya lalu jalankan lagi). Ini terjadi tiap kali `BOOTSTRAP_VERSI` membawa `ALTER TABLE`
     — jalankan ALTER yang sama di arsip, atau `CREATE TABLE LIKE` ulang kalau tabel masih kosong;
   - view → `CREATE OR REPLACE VIEW` dengan `iresis_prod.` → `iresis_arsip.`, klausa `DEFINER`
     dibuang (definer root butuh SUPER).
5. **Sinkron**, tabel kecil → besar (master dulu supaya Mode Arsip selalu punya `tbluser`/`menu`/`tblsku`):
   - **penuh** (≤ `batas_penuh` = 20.000 baris, atau PK bukan integer, mis. `tblsku`):
     `REPLACE` seluruh isi — menutup UPDATE apa pun pada tabel master;
   - **bertahap** (tabel besar, PK integer): per batch `batch` = 50.000 baris
     `WHERE pk > watermark AND pk <= maks_batch`, watermark disimpan **tiap batch**; lalu baris
     yang kolom-ubahnya (`modified_at`/`updated_at`/`updated`/…) ≥ `NOW() - 2 hari` disalin ulang;
   - tanpa PK tunggal → dilewati (`tblnamapacking`, kosong);
   - lewat `maks_detik` (default 7.200) → berhenti rapi; putaran berikutnya melanjutkan dari watermark.
6. **Laporan** — hitung resi yang aktivitas terakhirnya (`GREATEST(tanggal_printresi|created_at,
   modified_at, tanggal_selesai, tanggal_retur)`) sudah lewat 60 hari. **Hanya angka pemantau**;
   penghapusan dilakukan tahap purna (§4c) bila `purna_aktif = TRUE`.

Kecepatan terukur 21 Sep 2026: ±13–15 rb baris/detik (`tblkpi` 545 rb baris 36 dtk).
Muat awal 3,3 GB ≈ 30–40 menit; putaran harian normal (±10 rb resi/hari) hitungan menit.

### Perintah manual

```bash
# Putaran lengkap (yang dijalankan task 01.00)
C:/xampp/php/php.exe index.php cron arsip_harian
```

```bash
# Hanya samakan struktur (setelah BOOTSTRAP_VERSI naik / ALTER TABLE di prod)
C:/xampp/php/php.exe index.php cron arsip_harian cek_skema
```

```bash
# Sinkron dengan batas waktu (detik) — dipakai saat muat awal bertahap
C:/xampp/php/php.exe index.php cron arsip_harian sinkron 600
```

```bash
# Pantau: watermark & hasil per tabel
"C:/xampp/mysql/bin/mysql.exe" -u root -e "SELECT tabel, mode, pk_terakhir, baris_terakhir, baris_arsip, terakhir_jalan, durasi_detik, keterangan FROM iresis_arsip._arsip_status ORDER BY terakhir_jalan DESC;"
```

```bash
# Pantau: jejak putaran (FAIL terbaru dulu)
"C:/xampp/mysql/bin/mysql.exe" -u root -e "SELECT waktu, tahap, tabel, status, LEFT(pesan,120) pesan FROM iresis_arsip._arsip_log ORDER BY status='FAIL' DESC, id DESC LIMIT 30;"
```

Lewat HTTP (jarang perlu): `/cron/arsip_harian?token=<cron_token>&tahap=cek_skema`.

## 4b. Mode Arsip (melihat arsip lewat aplikasi yang sama)

| Bagian | Isi |
|---|---|
| Siapa boleh | Role yang punya akses menu **"Mode Arsip"** (uri `arsip`, tingkat atas). Nilai awal dari migrasi `run_mode_arsip_migration` (BOOTSTRAP 2026-09-21.2): webmaster 1, admin 2, tim retur 6, tim finance 10. Buka untuk role lain: centang di halaman **Access** — tanpa ubah kode. |
| Cara masuk/keluar | Menu Mode Arsip → tombol **Masuk Mode Arsip** (`arsip/masuk`, set session `mode_arsip`, muat ulang) / **Kembali ke LIVE** (`arsip/keluar`). Banner merah di atas semua halaman + judul tab `[ARSIP]` selama aktif. |
| Mekanisme | `MY_Controller::terapkan_mode_arsip()` di **akhir** constructor (setelah bootstrap DDL & cache menu → keduanya selalu di prod): `db_select('iresis_arsip')` pada koneksi yang sama — user `iresis_app` punya hak di kedua DB — jadi seluruh model membaca arsip tanpa diubah. |
| Read-only | Bawaan `mode_arsip_tulis = FALSE` → `SET SESSION TRANSACTION READ ONLY`: INSERT/UPDATE/DELETE ditolak MariaDB (ERROR 1792, diuji 21 Sep 2026), `db_debug` dimatikan agar penolakan tidak mencetak HTML error di tengah JSON. Halaman laporan/KPI/monitoring/retur diverifikasi hanya membaca saat dibuka; tulisan di menu-menu itu adalah aksi sengaja (simpan target, komentar) yang memang tidak boleh di arsip. |
| Membuka tulisan | `mode_arsip_tulis = TRUE` di `config/arsip.php`. Risiko: baris baru di arsip memakai id yang juga akan dipakai prod → ditimpa `REPLACE` malam berikutnya. Hanya kalau ada kebutuhan jelas. |
| Ditolak otomatis | Kalau `db_pconnect` aktif (SET SESSION akan menempel di koneksi yang dipakai ulang request prod), atau `db_select` gagal — session dibersihkan, dicatat di `application/logs/`. |
| Kesegaran | Halaman Mode Arsip menampilkan sinkron terakhir dan kegagalan 3 hari terakhir dari `_arsip_status`/`_arsip_log`. |

## 4c. Purna — memindahkan keluarga resi > 60 hari keluar dari prod

Satu-satunya bagian yang menjalankan `DELETE` di prod: `Arsip_fcd::purna()`, dipicu
`php index.php cron arsip_purna [uji|jalankan] [maks_detik] [maks_resi]`, atau otomatis di
putaran 01.00 setelah sinkron bila `purna_aktif = TRUE` di `config/arsip.php`.

**Gerbang keselamatan** (ditolak kalau salah satu gagal): skema prod = arsip untuk semua tabel
keluarga; sinkron terakhir ≤ 36 jam; backup arsip (`C:\backup-db\otomatis\iresis_arsip_*.sql.gz`)
≤ 36 jam.

**Calon** (`calon_purna`): resi yang aktivitas terakhir di `tblprintresi` (`GREATEST` tanggal
cetak/`created_at`/`modified_at`) < cutoff, **dan** tidak ada tanda masih terbuka.
`tanggal_selesai`/`tanggal_retur`/`tanggal_pengiriman` **sengaja tidak dihitung** sebagai
aktivitas: ketiganya dari kolom Excel upload resi dan 36–53 % nilainya di masa depan
(hari/bulan tertukar — temuan 22 Sep 2026; pada putaran pertama itu menahan 159 rb resi tua di
prod). Aktivitas nyata (packing, keluar, retur) sudah dicek lewat tabel anaknya:

| Ditunda kalau | Alasan |
|---|---|
| packing / ambil barang / keluar / retur / verifikasi / cancel / komplain bertanggal ≥ cutoff | masih ada aktivitas |
| `tblresiretur.status_retur = 'Terima Retur'` | diterima tapi belum dibuka |
| `tblbukaretur.status_acc = 0` | belum di-ACC finance (3.517 resi per 21 Sep 2026) |
| komplain CS `status_penanganan` ≠ 'Selesai' (NULL = belum) | komplain masih berjalan |
| belum ada scan keluar **dan** status bukan CANCELED/COMPLETED/SHIPPED/RETURNED | resi nyangkut (201 resi) |

`status_pesanan = PROCESSING` yang sudah punya scan keluar (54.044 resi) dianggap **status
basi** dan boleh diarsip.

**Per batch** (2.000 resi, `purna_batch_resi`): `REPLACE` 19 tabel keluarga ke arsip →
verifikasi **setiap PK prod ada di arsip** (gagal = berhenti, tidak ada yang dihapus) → `DELETE`
di prod dalam satu transaksi, anak dulu, `tblprintresi` terakhir. Lalu `tblkpi` (400 hari,
kolom `tanggal`) dan `notifications` (30 hari) dengan cara yang sama berdasarkan tanggalnya.
Jejak per batch di `_arsip_log` tahap `purna`.

Sesi purna memakai `optimizer_switch='materialization=off'`: tanpa itu MariaDB 10.4
mematerialisasi tiap `NOT EXISTS` (memindai indeks `tblpacking` 2,7 jt baris per batch,
±10 dtk); dengan itu ±90 ms. Terukur 21 Sep 2026: mode uji 20.000 resi / 19,8 dtk.

**Prosedur eksekusi pertama** (dijalankan user — Claude dilarang menjalankan DELETE):

```bash
# 1. Uji — semua langkah kecuali DELETE (REPLACE ke arsip + verifikasi), laporkan jumlah
C:/xampp/php/php.exe index.php cron arsip_purna uji 300
```

```bash
# 2. Satu batch sungguhan (2.000 resi tertua), lalu cek aplikasi & jumlah baris
C:/xampp/php/php.exe index.php cron arsip_purna jalankan 120 2000
```

```bash
# 3. Sisanya — berhenti sendiri saat calon habis atau 2 jam
C:/xampp/php/php.exe index.php cron arsip_purna jalankan 7200
```

Setelah bersih, nyalakan `purna_aktif = TRUE` supaya putaran malam mengarsipkan yang baru
lewat 60 hari tiap hari (±10 rb resi/hari, hitungan detik). Ukuran berkas `.ibd` **tidak**
mengecil sampai `OPTIMIZE TABLE` (tahap 8); jumlah baris dan kerja indeks langsung turun.

## 5. Batasan yang disengaja

- **Baris yang dihapus di prod tetap ada di arsip.** Arsip tidak pernah menghapus. Kalau
  prod menghapus resi (mis. pindah ke `tblprintresihapus`), arsip masih menyimpan aslinya —
  untuk jejak historis ini justru diinginkan; untuk laporan bisa berbeda tipis dari prod.
- **UPDATE pada baris lama tanpa kolom-ubah** (mis. `tbldetailprintresi.status_kurangan`)
  baru tersalin saat tahap purna me-`REPLACE` ulang seluruh keluarga resi di hari ke-60.
  Sampai tahap itu aktif, arsip untuk tabel tersebut bisa tertinggal beberapa kolom status.
- `table_rows` dari `information_schema` hanya perkiraan → pilihan mode penuh/bertahap
  bisa bergeser di sekitar 20.000 baris; keduanya benar, hanya beda cara.
- 24 tabel sisa lama yang dulu ditumpuk di `iresis_arsip` (`*_backup_*`, `*_arsip`, `tmp_*`,
  `tblprintresi_clean`, `tblpickingsummarylog_20260915`; 673 MB) sudah dipindah ke
  `iresis_sampah` pada 21 Sep 2026 (`RENAME TABLE`, dijalankan user). `iresis_arsip` kini hanya
  berisi kembaran tabel prod + `_arsip_status`/`_arsip_log`.
- Backup arsip (task 02.30, rotasi 7) **sedisk dengan datanya** karena PC ini hanya punya
  drive C:. Kalau ada drive eksternal/NAS, ubah `$dirBackup` di `C:\backup-db\backup_db.ps1`
  (berlaku untuk backup prod juga) supaya satu kerusakan disk tidak menghabisi keduanya.
- `pastikan_resi_live()` menyalin keluarga resi dengan `INSERT IGNORE`; baris yang sudah ada
  di prod tidak disentuh. Resi yang ditarik balik hidup di **dua** DB sampai purna
  mengarsipkannya lagi (60 hari sejak aktivitas terakhir) — normal, bukan duplikasi.

## 6. Tahapan

| # | Tahap | Status |
|---|---|---|
| 1 | Job sinkron malam (cek skema + salin + laporan), task 01.00, muat awal | **Selesai 21 Sep 2026** — pantau beberapa malam |
| 2 | Perbaiki 61.033 `tblprintresi.tanggal_printresi = 0000-00-00` (Mar 2026) dari `created_at` | Belum — UPDATE prod, prosedur konfirmasi 3× + backup |
| 3 | Pindahkan 24 tabel sisa dari `iresis_arsip` ke `iresis_sampah` | Belum — `RENAME TABLE`, dijalankan user |
| 4 | Mode Arsip: `db_select` ke arsip di akhir `MY_Controller`, sesi `READ ONLY`, menu "Mode Arsip" + roleaccess (1, 2, 6, 10; role lain via Access), banner merah | **Selesai 21 Sep 2026** — uji di browser oleh user |
| 5 | `pastikan_resi_live($noresi)` (helper autoload → `Arsip_fcd::tarik_balik`, peta tabel di config `keluarga_resi`): dipasang di 10 titik masuk — scan retur, buka retur (3), komplain CS (2), kurangan picker, video packing, detail resi, cancel order. Diagnostik: `php index.php cron cek_resi_live <noresi>` | **Selesai 21 Sep 2026** — jalur `ditarik` diuji di sandbox bersama tahap 7 |
| 6 | Backup arsip harian: task "IRESIS - Backup arsip 02.30" → `backup_db.ps1 -Database iresis_arsip -Simpan 7 -Paksa` (via `scripts/backup_arsip_senyap.vbs`), file `C:backup-dbotomatisiresis_arsip_*.sql.gz`, rotasi 7 hari. Uji 21 Sep: 1,7 GB → 260 MB gz, 129 dtk, CRC OK. Masih satu disk (hanya ada C:) — pindahkan `$dirBackup` bila ada drive lain | **Selesai 21 Sep 2026** |
| 7 | Purna (§4c): `cron arsip_purna [uji|jalankan]`, gerbang keselamatan (skema, sinkron ≤ 36 jam, backup arsip ≤ 36 jam), per batch REPLACE → verifikasi PK → DELETE; `tblkpi` 400 hari, `notifications` 30 hari | **Selesai 21 Sep 2026**: eksekusi pertama oleh user 12.41–14.02, 1.902.185 resi dipindah, 0 gagal; prod 2,75 jt → 852 rb resi; `purna_aktif = TRUE` sejak 14.15 |
| 8 | `Cron::optimasi_tabel` (config `optimasi_tabel`, 8 tabel; tolak jam kerja 07–22 kecuali `paksa`; catat ukuran `.ibd` sebelum/sesudah) — task "IRESIS - Optimasi tabel (sekali 21 Sep 2026)" 22.30 + "IRESIS - Optimasi tabel bulanan" tgl 1 22.30, via `scripts/optimasi_tabel_senyap.vbs`, log `logs/optimasi_tabel.log` | **Terjadwal 21 Sep 2026** — cek hasil besok pagi |
| 9 | Peringatan di laporan bila rentang tanggal menyentuh sebelum cutoff | Belum |

Tuning terpisah yang disarankan (di luar jam kerja, butuh restart MariaDB):
`tmp_table_size`/`max_heap_table_size` 16 → 128 MB (50 % tabel sementara jatuh ke disk),
`innodb_buffer_pool_size` 1 → 2 GB (RAM 8 GB).
