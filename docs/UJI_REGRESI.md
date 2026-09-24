# Uji Regresi Manual per Meja

Dibuat 23 September 2026 (backlog B-15). Checklist ini dijalankan di
`iresis-dev` sebelum perubahan di-merge ke `master` lalu di-pull ke produksi.
Repo ini tidak punya test otomatis, jadi hanya checklist ini yang bisa
menunjukkan bahwa alur scan tetap sama setelah kode diubah, terutama saat
refactor.

Satu putaran penuh memakai lima resi uji yang berjalan dari satu meja ke meja
berikutnya, ditambah satu file Excel Jubelio. Tiap meja diuji dengan resi
normal, resi batal, resi yang tahap sebelumnya belum di-scan, dan satu alur
pengecualian. Pesan di kolom "Hasil yang diharapkan" dikutip dari kode per
23 Sep 2026.

## 1. Kapan dijalankan

| Perubahan | Putaran |
|---|---|
| Hanya `docs/`, file `*.md`, `scripts/*.py` | tidak perlu |
| `application/core/`, `application/config/`, `application/helpers/`, `assets/js/plugins.js`, `views/main.php` | **penuh** (§5–§10) |
| File yang ada di tabel §3 | langkah yang disebut di tabel itu |
| File lain, atau ragu | **penuh** |

Kalau perubahan sengaja mengganti pesan atau alur, perbarui tabel di dokumen
ini dalam commit yang sama.

## 2. Persiapan (sekali per putaran)

1. Checkout branch yang diuji di `C:\xampp\htdocs\iresis-dev`, lalu syntax
   check semua file PHP yang berubah:
   ```bash
   git diff --name-only master...HEAD -- '*.php' | xargs -r -n1 C:/xampp/php/php.exe -l
   ```
   Setelah itu jalankan smoke test (§2.3). Kalau smoke test gagal, perbaiki
   dulu sebelum mulai checklist manual.
2. Buka `http://localhost/iresis-dev/` **di PC server**, bukan lewat IP LAN.
   Browser hanya mengizinkan kamera di `localhost` atau HTTPS, dan §7 butuh
   kamera. Login dengan akun **webmaster** (role 1). Role ini memegang semua
   menu di bawah, termasuk Scan Resi Packer (Webcam) yang hanya dibuka untuk
   role 1 dan 4. Pastikan **Mode Arsip mati**. Selama mode itu aktif, koneksi
   DB bersifat read-only dan semua scan akan gagal.
3. Kalau branch menaikkan `BOOTSTRAP_VERSI`, request pertama akan menjalankan
   migrasi ke `iresis_dev`. Buka satu halaman apa saja dulu dan pastikan tidak
   ada error, baru mulai.
4. Catat **jam mulai** (dipakai di §11). Buka DevTools (F12), tab Console, dan
   biarkan terbuka selama uji.
5. Siapkan resi uji (§2.1) dan file uji (§2.2).

### 2.1 Resi uji

Jalankan query berikut sebagai user `iresis_dev`, bukan root. User ini tidak
punya akses ke `iresis_prod`. Baris `SET SESSION optimizer_switch` di awal
jangan dilewati. Tanpa baris itu MariaDB memindai penuh tabel detail dan tabel
picker, sehingga query butuh sekitar 16 detik, bukan 0,02 detik.

```bash
"C:/xampp/mysql/bin/mysql.exe" -u iresis_dev -p iresis_dev
```

```sql
SET SESSION optimizer_switch = 'semijoin=off,materialization=off';
SET @batas := (SELECT MAX(tanggal_printresi) FROM tblprintresi) - INTERVAL 14 DAY;

-- Baris 1 = N, baris 2 = N2, baris 3 = L1. Baris 4-5 cadangan untuk menu
-- tambahan di §3 (Resi Pending, Inbound Picker & Packer).
-- Resi normal, belum di-picker, bukan kurir Shopee (id 7 ditolak mode NDD),
-- punya detail SKU, belum pernah masuk lost scan maupun retur.
SELECT pr.noresi, pr.id_kurir, pr.status_pesanan
FROM tblprintresi pr
WHERE pr.tanggal_printresi >= @batas
  AND pr.id_kurir <> 7
  AND COALESCE(pr.status_pesanan, '') NOT LIKE '%CANCEL%'
  AND COALESCE(pr.status_pesanan, '') <> 'COMPLETED'
  AND COALESCE(pr.batal, '') <> '1'
  AND EXISTS     (SELECT 1 FROM tbldetailprintresi d WHERE d.id_resi = pr.id_printresi)
  AND NOT EXISTS (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi)
  AND NOT EXISTS (SELECT 1 FROM tbllostscanpicker_pending p WHERE p.noresi = pr.noresi)
  AND NOT EXISTS (SELECT 1 FROM tbllostscanpacker l WHERE l.noresi = pr.noresi)
  AND NOT EXISTS (SELECT 1 FROM tblresiretur r WHERE r.id_resi = pr.id_printresi)
ORDER BY pr.id_printresi DESC
LIMIT 5;

-- C: CANCELED, belum di-picker, belum pernah ditolak di meja mana pun.
SELECT pr.noresi, pr.id_kurir, pr.status_pesanan
FROM tblprintresi pr
WHERE pr.tanggal_printresi >= @batas
  AND pr.status_pesanan = 'CANCELED'
  AND pr.id_kurir <> 7
  AND NOT EXISTS (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi)
  AND NOT EXISTS (SELECT 1 FROM tblcancel_paket_tolak t WHERE t.noresi = pr.noresi)
  AND NOT EXISTS (SELECT 1 FROM tblresiretur r WHERE r.id_resi = pr.id_printresi)
ORDER BY pr.id_printresi DESC
LIMIT 1;

-- Baris 1 = L2: sudah di-picker, belum packing, belum pernah dicatat lost scan.
-- Baris 2 = S: cadangan untuk SCAN SPECIAL (hanya dipakai bila §3 memintanya).
SELECT pr.noresi, pr.status_pesanan
FROM tblprintresi pr
WHERE pr.tanggal_printresi >= @batas
  AND COALESCE(pr.status_pesanan, '') NOT LIKE '%CANCEL%'
  AND COALESCE(pr.status_pesanan, '') <> 'COMPLETED'
  AND COALESCE(pr.batal, '') <> '1'
  AND EXISTS     (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi)
  AND NOT EXISTS (SELECT 1 FROM tblpacking k WHERE k.id_resi = pr.id_printresi)
  AND NOT EXISTS (SELECT 1 FROM tbllostscanpacker l WHERE l.noresi = pr.noresi)
  AND NOT EXISTS (SELECT 1 FROM tbllostscanpicker_pending p WHERE p.noresi = pr.noresi)
ORDER BY pr.id_printresi DESC
LIMIT 2;
```

Setiap putaran menghabiskan resinya sendiri, jadi putaran berikutnya otomatis
mendapat resi lain. Kalau salah satu query kosong, data dev sudah terlalu
basi: segarkan database dev (`docs/LINGKUNGAN_DEV.md` §5).

| Kode | Kondisi awal | Dipakai untuk | Noresi |
|---|---|---|---|
| **N** | normal, belum di-picker | alur lengkap mode HO + NDD, lalu retur | |
| **N2** | normal, belum di-picker | alur lengkap mode REGULER lewat menu packer biasa | |
| **L1** | normal, belum di-picker; **jangan di-picker** di §6 | lost scan picker yang dilaporkan packer (§7, §10) | |
| **C** | CANCELED, belum di-picker | penolakan di setiap meja | |
| **L2** | sudah di-picker, belum packing | lost scan packer di meja HO | |
| **X** | tidak ada di database | resi tidak ditemukan | `UJI-TIDAK-ADA` |

### 2.2 File uji

- **Excel Jubelio**: ekspor laporan penjualan `.xlsx` yang biasa diunggah tim
  resi. File hari ini lebih baik, karena isinya belum ada di dev.
- **Salinan Excel yang diubah** untuk U-3 (lihat langkahnya).
- **Satu file bukan Excel**, misalnya `.csv` atau `.pdf` apa saja.

Dev dan produksi memakai Apache dan MariaDB yang sama. Unggah file besar di
dev ikut memperlambat produksi, jadi jalankan §5 di luar jam sibuk.

### 2.3 Smoke test otomatis

`tests/smoke_scan.php` (B-16) memanggil endpoint scan lewat HTTP sungguhan,
lalu memeriksa bahwa setiap balasan adalah JSON utuh tanpa satu byte pun di
luarnya, dengan `code` dan `EXCEPTION_CODE` yang benar. Yang diperiksa:

- balasan 401 saat belum login dan proses login;
- delapan halaman menu scan, termasuk pengecekan tidak ada halaman error PHP
  di dalam view;
- tolakan untuk resi tidak ditemukan dan resi dobel;
- dalam putaran penuh: satu resi normal dibawa dari picker sampai Terima Retur,
  satu resi CANCELED ditolak di tiap meja, lalu keadaan akhir keduanya dicocokkan
  di database.

```bash
C:/xampp/php/php.exe tests/smoke_scan.php
```

Kode keluar 0 berarti lulus, 1 berarti ada yang gagal (rinciannya tercetak),
dan 2 berarti tidak bisa mulai. Pakai `--baca-saja` untuk putaran yang tidak
menulis data. Syaratnya adalah `uji_username` dan `uji_password` (akun
webmaster di `iresis_dev`) sudah diisi di `secrets.php` folder dev. Script
menolak jalan di folder produksi.

Smoke test **tidak menggantikan** checklist ini. Smoke test tidak melihat
layar, tidak mendengar suara, tidak menyentuh kamera, upload, Buka Retur,
maupun lost scan. Kerjanya menangkap lebih dulu kerusakan paling umum, yaitu
JSON rusak dan kode balasan yang berubah.

## 3. Peta file → langkah

Dipakai untuk putaran sebagian. Satu file bisa muncul di beberapa baris.

| Kalau yang berubah | Jalankan |
|---|---|
| `models/Receipt_fcd.php`, `controllers/Receipt.php`, `libraries/Xlsx_cepat.php`, `views/receipt/*` | §5 |
| `models/Picking_fcd.php`, `controllers/Picker.php`, `controllers/Inbound_picker.php`, `views/picker/*` | §6, §10. Scan resi cadangan baris 4 di menu **Resi Pending** dan baris 5 di **Inbound Picker & Packer** |
| `models/Packer_fcd.php`, `controllers/Packer.php`, `views/packer/*`, `assets/js/packer_video.js` | §7, §10. Scan resi **S** di menu **SCAN SPECIAL**: sukses, lalu dobel ditolak |
| `models/Scan_logistic_fcd.php`, `controllers/Scan_logistic.php`, `controllers/Scan_paket_ndd_new.php`, `models/Scan_paket_ndd_new_fcd.php`, `views/scan_paket_ndd_new/*` | §8, §10. Juga menu lama **Scan Paket NDD**: satu resi sukses dan satu dobel |
| `models/Handover_fcd.php`, `controllers/Handover.php` | H-1 sampai H-4 diulang di menu lama **Scan Resi Keluar** |
| `models/Retur_fcd.php`, `controllers/Retur.php`, `views/retur/*` | §9 |
| `models/Cancel_paket_fcd.php` | P-3, K-3, H-4, R-3, lalu query §11.1 |
| `models/Lost_scan_picker_fcd.php`, `models/Lost_scan_packer_fcd.php`, `controllers/Lost_scan_picker.php` | K-4, K-5, H-5, §10 |

Putaran sebagian tetap memakai persiapan §2 dan penutup §11. Resi yang
langkahnya tidak dijalankan cukup dilewati di tabel keadaan akhir.

## 4. Aturan lulus di setiap langkah

Selain kolom "Hasil yang diharapkan", sebuah langkah **gagal** bila:

- layar menampilkan teks JSON mentah (`{"code":...`), halaman error
  CodeIgniter, atau "A PHP Error was encountered";
- Console browser memunculkan error merah baru (`parsererror`, `Uncaught ...`);
- pesan yang muncul berbeda dari tabel. Beda kalimat bisa berarti cabang kode
  lain yang berjalan, jadi cek dulu sebelum dianggap lulus;
- penghitung "total scan hari ini" naik padahal scan ditolak, atau tidak naik
  padahal sukses.

## 5. Admin upload: TIM RESI → Upload Resi

Upload adalah tahap pertama, jadi tidak ada kasus "tahap sebelumnya belum
di-scan". Kasus itu diganti validasi file (U-4).

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| U-1 | **Normal.** Unggah Excel Jubelio. | Progres bergerak (membaca, mengolah, menyimpan, menyelesaikan transaksi), lalu muncul `Total Data Terinput: … \| Dilewati: … \| Duplikat: … \| Diupdate: … \| Data Tidak Berubah: … \| Waktu: … dtk`. Ambil satu noresi dari kolom B file, buka **Detail Resi**: resi tampil dengan SKU dan qty sesuai kolom P dan Q. |
| U-2 | **Pengecualian: unggah ulang.** Unggah file yang sama sekali lagi. | `Total Data Terinput: 0` dan `Diupdate: 0`. Semua resi jatuh ke `Duplikat` (sudah COMPLETED) atau `Data Tidak Berubah`. |
| U-3 | **Batal.** Di salinan file, pilih satu resi yang cuma punya **satu baris** dan kolom T-nya bukan `CANCELED` atau `COMPLETED`. Ubah kolom T baris itu menjadi `CANCELED`, simpan, lalu unggah. | `Diupdate: 1`. Query `SELECT status_pesanan, modified_at FROM tblprintresi WHERE noresi = '<resi>';` menghasilkan `CANCELED` dengan `modified_at` baru. Scan resi itu di Scan Resi Picker: ditolak `Pesanan sudah DIBATALKAN`. |
| U-4 | **Validasi file.** Tekan tombol upload tanpa memilih file. Lalu pilih file `.csv`/`.pdf`; ganti filter jenis file di dialog ke "All files" supaya file itu terlihat. | Tanpa file: `Silahkan Input File Terlebih Dahulu!` dan notifikasi `Pilih file Excel terlebih dahulu.` File salah: teks merah `File .csv tidak didukung, pilih .xlsx atau .xls.` (ekstensi sesuai file), notifikasi `Format file tidak didukung, pilih file .xlsx atau .xls dari Jubelio.`, dan pilihan file dikosongkan. Tidak ada request yang terkirim. |

## 6. Picker: TIM PICKER

Menu **Scan Resi Picker**, kecuali P-5. Pilih **Nama Picker** di dropdown
sebelum scan, lalu biarkan Status Performa seperti apa adanya.

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| P-1 | **Normal.** Scan **N**, lalu **N2**. | Keduanya sukses: tabel item menampilkan SKU dan qty resi itu, penghitung naik. |
| P-2 | **Dobel.** Scan **N** lagi. | `Nomor resi sudah di-picker (Double Scan).` |
| P-3 | **Batal.** Scan **C** satu kali saja. | `Pesanan sudah DIBATALKAN` |
| P-4 | **Tahap sebelumnya belum.** Scan **X** (`UJI-TIDAK-ADA`). | `Nomor resi tidak ditemukan` |
| P-5 | **Pengecualian: koreksi nama picker.** Menu **Update Picker**. Pilih picker lain, scan **N**. Lalu scan **L1**. | N: `Sukses menambahkan data`, dan nama picker N berganti. L1: `Resi belum memiliki nama picker. Scan lewat menu Scan Picker lebih dulu.` |

Suara P-2, P-3, dan P-4 harus berbeda satu sama lain ("sudah scan", "cancel
order", "tidak ditemukan"). Kalau ketiganya sama, `EXCEPTION_CODE` hilang
dari respons. Halaman ini memilih suara dari kode itu, bukan dari teks pesan.

**L1 tidak boleh di-picker di sini.** Resi itu dipakai untuk lost scan di §7
dan §10.

## 7. Packer: TIM PACKER

Menu **Scan Resi Packer (Webcam)**, kecuali K-6. Syaratnya panel kamera di
pojok kanan bawah bertulisan "Kamera siap", dan status packer bukan
ISTIRAHAT atau sudah check-out. Setiap resi ditutup dengan dua kali scan:
scan pertama membuka siklus dan mulai merekam, scan kedua menyimpan.

Task finalisasi video tidak didaftarkan di dev, jadi file video tidak ikut
dijadikan kriteria lulus.

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| K-1 | **Normal.** Scan **N**, tunggu beberapa detik, scan **N** lagi. | Scan pertama: detail SKU tampil, nama picker = picker hasil P-5, rekaman mulai. Scan kedua: tersimpan, rekaman berhenti tanpa pesan error, penghitung naik. |
| K-2 | **Dobel.** Scan **N** sekali lagi. | `Resi N sudah selesai di-packing oleh <nama akun Anda> pada <tanggal jam> dan sudah punya video. Satu resi hanya boleh dua kali scan.` |
| K-3 | **Batal.** Scan **C** satu kali saja. | `Pesanan sudah DIBATALKAN (resi C). Jangan dipacking dulu, laporkan ke CS.` Kamera tidak mulai merekam. |
| K-4 | **Tahap sebelumnya belum.** Scan **L1**. | Popup `Resi L1 belum di-picker. Jangan dipacking dulu -- tekan Lapor Lost Scan Picker, tahan paketnya, tunggu tim picker menentukan picker, lalu scan ulang.` dengan tombol merah **Lapor Lost Scan Picker**. Kamera tidak mulai merekam. |
| K-5 | **Pengecualian: lapor lost scan picker.** Tekan tombol di popup K-4. Lalu scan **L1** lagi. | Tombol: `Resi L1 dilaporkan ke tim picker. Tahan paketnya sampai picker ditentukan.` dan tombolnya hilang. Scan ulang: `Resi L1 belum di-picker dan SUDAH dilaporkan ke tim picker oleh <nama> (PACKER) pada <waktu>. Tahan paketnya; tunggu tim picker menentukan picker, lalu scan ulang.` tanpa tombol. |
| K-6 | **Menu cadangan.** Menu **Scan Resi Packer** (biasa). Scan **N2** dua kali. | Scan pertama menampilkan detail SKU, scan kedua `Sukses menambahkan data`, penghitung naik. |

**Kalau PC uji tidak punya kamera**, pakai menu Scan Resi Packer biasa. Di
menu itu penolakan baru muncul di scan **kedua**, karena scan pertama hanya
menampilkan detail:

- K-1: sama seperti di atas, tanpa rekaman.
- K-2: scan N dua kali. Scan kedua ditolak `Nomor resi sudah di-packing (Double Scan).`
- K-3: scan C dua kali. Scan kedua ditolak `Pesanan sudah DIBATALKAN`.
- K-4 dan K-5: tandai "tidak diuji", karena tombol lapor hanya ada di menu webcam.
  Laporannya dipindah ke HO. Di H-5, scan **L1** alih-alih L2. Panel akan
  terbuka dengan teks merah, dan Simpan membalas `Lost scan packer berhasil
  dicatat dan dilaporkan ke tim picker`. L-2 juga dikerjakan di menu biasa.

Keadaan akhir L1 di §11.1 menjadi `lost_scan` = `PACKER,PICKER` dan
`antrean_picker` = `HO:SELESAI`. Baris L2 dilewati.

## 8. HO: TIM HO → Scan Paket NDD New

Halaman dibuka dalam mode **HO + NDD**. Tombol **REGULER** mengganti mode.

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| H-1 | **Normal (HO + NDD).** Scan **N**. | `SCAN HO + NDD BERHASIL!`. Penghitung NDD dan HO sama-sama naik. |
| H-2 | **Dobel.** Scan **N** lagi. | `Nomor resi sudah di-scan NDD (Double Scan).` |
| H-3 | **Normal (REGULER).** Tekan REGULER. Scan **N2**, lalu scan **N2** lagi. | Pertama: `SCAN HO REGULER BERHASIL!`, hanya penghitung HO yang naik. Kedua: `Nomor resi sudah di-scan keluar (Double Scan).` |
| H-4 | **Batal.** Masih mode REGULER, scan **C** satu kali. | `Pesanan sudah DIBATALKAN`. Panel lost scan tidak muncul. |
| H-5 | **Tahap sebelumnya belum + pengecualian lost scan packer.** Masih mode REGULER, scan **L2**. Pilih packer di panel yang muncul di bawah kartu status, lalu Simpan. Scan **L2** lagi. | Scan pertama: `Nomor resi belum di-packing.` dan panel pilih packer terbuka. Simpan: `Lost scan packer berhasil dicatat`. Scan ulang: panel mode info `Sudah dicatat lost scan PACKER → <nama packer>, oleh <nama Anda> pada <waktu>. Tidak perlu dicatat lagi.`, tanpa dropdown. L2 tetap tidak masuk HO. |

## 9. Retur: TIM RETUR

Menu **Scan Retur**, kecuali R-6. Tab **Terima Retur** menerima satu resi per
Enter. Tab **Buka Retur** meminta resi dulu, lalu menampilkan daftar SKU yang
diproses satu per satu lewat modal (qty dan Status Detail).

Terima Retur saat ini **tidak** memeriksa apakah resinya sudah HO. Ini
perilaku yang ada sekarang, bukan bagian uji. Jangan sampai berubah tanpa
sengaja saat refactor.

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| R-1 | **Normal (Terima).** Tab Terima Retur, scan **N**. | `1 resi berhasil diproses` |
| R-2 | **Dobel.** Scan **N** lagi. | Kotak merah `RESI DOUBLE / SUDAH DIINPUT!` diikuti `Tidak ada resi yang berhasil diproses. Resi N sudah diinput di Terima Retur`, lalu suara double. Halaman mengenali dobel dari kode 409 di respons. |
| R-3 | **Batal.** Scan **C**. | `Tidak ada resi yang berhasil diproses. Resi C tidak dapat diterima karena status paket adalah CANCEL` |
| R-4 | **Tahap sebelumnya belum.** Tab Buka Retur, scan **N2** (sudah HO, belum Terima Retur). | `Resi N2 belum discan Terima Retur. Scan Terima Retur dulu, baru bisa Buka Retur.` Daftar SKU tidak muncul. |
| R-5 | **Normal (Buka).** Tab Buka Retur, scan **N**. Proses satu SKU dengan qty penuh dan status **KE DISPLAY**. Lalu proses SKU yang sama sekali lagi. | Daftar SKU N tampil. Simpan pertama: `SKU <sku> berhasil diproses dengan status KE_DISPLAY`. Simpan kedua: `SKU <sku> sudah selesai diproses seluruhnya untuk resi ini. Untuk mengubah, gunakan menu Update Retur.` |
| R-6 | **Pengecualian: salah menu.** Menu **Scan Retur Komplain**, tab Terima Retur, scan **N**. | `Tidak ada resi yang berhasil diproses. Resi N sudah tercatat sebagai Retur Biasa (status: Buka Retur). Gunakan menu Scan Retur / Update Retur, atau hubungi admin bila jenisnya perlu diubah` |

## 10. Lintas meja: pemulihan lost scan picker

Lanjutan K-5. Urutannya mengikuti `docs/LOST_SCAN.md` §4: tim picker →
packer scan ulang → HO scan ulang.

| ID | Langkah | Hasil yang diharapkan |
|---|---|---|
| L-1 | TIM PICKER → **Laporan Lost Scan Picker**, tab **Menunggu Picker**. Cari **L1**, tekan **Tambahkan Picker**, pilih picker, simpan. | L1 ada di tab itu dengan sumber PACKER. Simpan: `Picker <nama> ditambahkan untuk resi L1. Packer bisa scan ulang.` L1 pindah dari tab Menunggu Picker. Notifikasi Pusher tidak muncul, karena Pusher memang dimatikan di dev. |
| L-2 | Scan Resi Packer (Webcam): scan **L1** dua kali. | Seperti K-1: tersimpan, nama picker = picker dari L-1. |
| L-3 | Scan Paket NDD New, mode **HO + NDD**: scan **L1**. | `SCAN HO + NDD BERHASIL!` |

## 11. Penutup

### 11.1 Keadaan akhir di database

Ganti isi `IN (...)` dengan kelima noresi uji:

```sql
SELECT pr.noresi,
       pr.status_pesanan AS status,
       (SELECT COUNT(*) FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi) AS picker,
       (SELECT COUNT(*) FROM tblpacking k        WHERE k.id_resi = pr.id_printresi) AS packing,
       (SELECT COUNT(*) FROM tblresikeluar h     WHERE h.id_resi = pr.id_printresi) AS ho,
       (SELECT COUNT(*) FROM tblscan_ndd n       WHERE n.id_resi = pr.id_printresi) AS ndd,
       (SELECT GROUP_CONCAT(r.status_retur, ' / komplain=', r.is_komplain)
          FROM tblresiretur r WHERE r.id_resi = pr.id_printresi) AS retur,
       (SELECT GROUP_CONCAT(t.tahap ORDER BY t.id_tolak)
          FROM tblcancel_paket_tolak t WHERE t.noresi = pr.noresi) AS tolak_cancel,
       (SELECT CONCAT(c.status, ' x', c.jumlah_tolak)
          FROM tblcancel_paket c WHERE c.noresi = pr.noresi) AS paket_cancel,
       (SELECT GROUP_CONCAT(l.lost_type ORDER BY l.id_lostscanpacker)
          FROM tbllostscanpacker l WHERE l.noresi = pr.noresi) AS lost_scan,
       (SELECT GROUP_CONCAT(p.sumber, ':', p.status)
          FROM tbllostscanpicker_pending p WHERE p.noresi = pr.noresi) AS antrean_picker
FROM tblprintresi pr
WHERE pr.noresi IN ('<N>', '<N2>', '<L1>', '<C>', '<L2>');
```

| Resi | picker | packing | ho | ndd | retur | tolak_cancel | paket_cancel | lost_scan | antrean_picker |
|---|---|---|---|---|---|---|---|---|---|
| N | 1 | 1 | 1 | 1 | `Buka Retur / komplain=0` | NULL | NULL | NULL | NULL |
| N2 | 1 | 1 | 1 | 0 | NULL | NULL | NULL | NULL | NULL |
| L1 | 1 | 1 | 1 | 1 | NULL | NULL | NULL | `PICKER` | `PACKER:SELESAI` |
| C | 0 | 0 | 0 | 0 | NULL | `PICKER,PACKER,HO` | `DITEMUKAN x2` | NULL | NULL |
| L2 | 1 | 0 | 0 | 0 | NULL | NULL | NULL | `PACKER` | NULL |

Cara membaca baris C (`docs/PAKET_CANCEL.md` §7): setiap penolakan karena
cancel menulis satu baris `tolak_cancel`. Paket cancel baru lahir di
penolakan packer, karena di meja picker barangnya belum diambil. Penolakan
HO menambah `jumlah_tolak` menjadi 2. Penolakan retur tidak tercatat di sini.
Angka `x2` hanya berlaku bila C discan tepat sekali di packer dan HO.

### 11.2 Log

Tidak boleh ada error baru sejak jam mulai. `log_threshold` bernilai 1, jadi
log CI hanya berisi error:

```bash
grep -n "^ERROR" application/logs/log-$(date +%F).php
```

Kalau file-nya tidak ada, berarti hari itu belum ada error sama sekali.

Log Apache dipakai bersama produksi, jadi saring ke folder dev:

```bash
grep "iresis-dev" C:/xampp/apache/logs/error.log | tail -n 20
```

### 11.3 Catat hasilnya

Tulis satu baris di pesan merge commit ke `master`, misalnya:

```
Uji regresi: penuh, lulus (docs/UJI_REGRESI.md), 23 Sep 2026
Uji regresi: sebagian §7 §10, lulus; K-4 dan K-5 tidak diuji (tanpa kamera)
```

Satu langkah gagal berarti jangan merge dan jangan pull ke produksi. Langkah
yang dilewati disebut, jangan didiamkan. Sebelum pull, petugas produksi bisa
membaca baris ini lewat `git log --merges -5`.
