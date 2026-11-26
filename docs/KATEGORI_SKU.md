# Kategori SKU (Tim Purchasing)

Dua menu di TIM PURCHASING (role webmaster 1 dan purchasing 7) mengelola kolom `tblsku.kategori_sku`:

1. **Update Kategori SKU** (`purchasing/update-kategori-sku`) mengisi kategori massal dari Excel.
2. **Kategori SKU** (`purchasing/kategori-sku`) menampilkan daftar SKU baca-saja dengan pencarian, filter, dan export.

Kategori ini dipakai menu Monitoring → Cabut SKU ([CABUT_SKU.md](CABUT_SKU.md)): hanya `EOL`, `WP EOL`, dan `CUT` yang masuk daftar cabut.

Menu dan hak akses dibuat otomatis oleh `MY_Controller::run_kategori_sku_migration()` dan `run_daftar_kategori_sku_migration()` (ter-gate `BOOTSTRAP_VERSI`).

---

## A. Update Kategori SKU (upload Excel)

File: `Kategori_sku` (controller), `Kategori_sku_fcd` (model, validasi + simpan).

### Excel

- Template: tombol **Unduh Template** (`purchasing/update-kategori-sku/template`). Header baris 1: `SKU` (kolom A) dan `Kategori` (kolom B); kolom berformat Teks agar SKU angka panjang tidak jadi notasi ilmiah. Sheet kedua berisi petunjuk.
- Berkas `.xlsx` / `.xls`, maksimal 10 MB dan 20.000 baris. Hanya sheet pertama dibaca.
- Kategori kosong = `P<30`. Spasi di awal/akhir dibuang.

### Aturan (`Kategori_sku_fcd::proses`)

Semua-atau-tidak-sama-sekali: satu baris bermasalah membatalkan seluruh upload, dan pop-up **Gagal upload** menampilkan alasan per baris (maks 40 ditampilkan, sisanya dihitung). Yang divalidasi:

- header harus `SKU` / `Kategori`; berkas harus terbaca (tidak rusak / berpassword);
- SKU kosong padahal kategori terisi; SKU > 100 karakter; kategori > 50 karakter atau berisi karakter kontrol;
- sel berisi rumus, TRUE/FALSE, atau angka ≥ 1e15 (sudah dibulatkan Excel);
- SKU duplikat, atau kembar yang hanya beda huruf besar/kecil;
- **SKU harus sama persis** dengan `tblsku.id_sku` (huruf besar/kecil dan spasi dihitung). Bila beda huruf, pesan menyebut tulisan di database.

Bila lolos: `UPDATE tblsku SET kategori_sku` per SKU (pencocokan `BINARY`) dalam satu transaksi. Pop-up **Upload berhasil** menampilkan jumlah SKU berhasil, yang berubah, dan yang sudah sama.

### Riwayat

`tblkategori_sku_riwayat` (batch, id_sku, kategori_lama, kategori_baru, id_user, created_at) mencatat setiap SKU yang nilainya berubah, per kode batch, sebagai dasar pengembalian manual. Dibuat oleh `run_kategori_sku_migration()`.

### Info "Upload kategori terakhir"

Tampil di bawah judul kedua menu (Update Kategori SKU dan Kategori SKU), bergaya sama dengan "Update otomatis terakhir" di Daftar Retur Penjualan: waktu, pengunggah, jumlah SKU diupload, dan jumlah yang berubah (nama berkas muncul saat kursor di atas waktu). Di menu upload, info langsung diperbarui setelah upload berhasil. Upload yang ditolak tidak dihitung.

Sumbernya `tblkategori_sku_upload` (satu baris per upload berhasil, termasuk yang tidak mengubah apa pun). Bila tabel itu masih kosong, dipakai batch terakhir di `tblkategori_sku_riwayat`; untuk data lama ini jumlah SKU diupload tidak diketahui sehingga hanya jumlah yang berubah yang tampil. View bersama: `views/kategori_sku/_upload_terakhir.php`.

---

## B. Menu Kategori SKU (daftar baca-saja)

File: `Daftar_kategori_sku` (controller), `Daftar_kategori_sku_fcd` (model), `views/kategori_sku/daftar.php`. Akses: role 1 dan 7 (role lain mendapat pesan tanpa merusak SPA).

Menampilkan seluruh `tblsku` (> 11 ribu baris) dengan kolom:

| Kolom | Isi |
|---|---|
| SKU | id_sku + gambar kecil (ikon mata membuka gambar penuh; cadangan salinan lokal lewat `assets/js/foto_sku.js`) |
| No Rak | `tblsku.no_rak` |
| Kategori | pil berwarna (EOL merah, WP EOL kuning, CUT biru) atau "–" bila kosong |

### Pencarian dan filter

- **Pencarian**: potongan teks pada SKU atau No Rak, tidak peka huruf besar-kecil, karakter `%` dan `_` diperlakukan literal. Debounce 300 ms.
- **Filter kategori multi-pilih**: dropdown kotak centang berisi tiap kategori yang ada beserta jumlah SKU-nya (terbanyak dulu), termasuk pilihan **(Belum ada kategori)** (nilai khusus `__BELUM__`) untuk SKU yang `kategori_sku`-nya NULL atau kosong. Tanpa centang = semua. Badge menunjukkan jumlah pilihan; tombol Reset mengosongkan; Esc / klik di luar menutup.
- Pencarian dan filter digabung (AND).

### Paging di server

Karena datanya besar, endpoint `purchasing/kategori-sku/get-data` (POST `q`, `kat[]`, `halaman`, `ukuran`) hanya mengirim satu halaman: 25 / 50 / 100 baris (default 50), urut SKU. Respons: `{total, halaman, total_halaman, ukuran, baris}`. Permintaan lama yang datang terlambat dibuang di klien. Halaman di luar jangkauan dirapikan ke halaman terakhir.

### Export Excel

Tombol **Export Excel** (`purchasing/kategori-sku/export`, GET `q`, `kat[]`) mengunduh **semua** SKU yang cocok dengan pencarian + filter aktif (bukan hanya halaman yang tampil), urut SKU. Format xlsx:

- kolom: No, SKU, No Rak, Kategori SKU (kategori kosong ditulis kosong);
- header tebal putih berlatar ungu, semua sel rata tengah, garis tepi horizontal dan vertikal, baris header beku, autofilter;
- sel teks eksplisit supaya SKU angka panjang aman;
- nama berkas `kategori-sku-YYYYMMDD-HHMMSS.xlsx`. Uji 19 Nov 2026: filter EOL menghasilkan 6.668 baris.

Query pencarian/filter dipakai bersama oleh layar dan export (`bangun_where()`), jadi isi file selalu persis yang terlihat.

### Responsif

Di bawah 768 px tabel berubah menjadi kartu per SKU, filter dan tombol berukuran sentuh (44 px).

---

## C. Data

- `tblsku.kategori_sku` VARCHAR(50): hanya diubah lewat upload Excel (bagian A). SKU di luar Excel bernilai NULL. Kategori kosong di Excel = `P<30`.
- Collation `utf8mb4_general_ci` tidak peka huruf besar-kecil, karena itu pencocokan persis memakai `BINARY`.
- Tidak ada penghapusan data di fitur ini.

## D. Cara uji

- Syntax: `C:/xampp/php/php.exe -l` untuk file yang diubah.
- Upload: pakai salinan DB, bukan produksi; coba berkas dengan SKU salah huruf, duplikat, dan rumus untuk melihat pesan penolakan.
- Daftar: cari SKU/rak, centang beberapa kategori + "(Belum ada kategori)", ganti ukuran halaman, Export lalu cocokkan jumlah baris dengan angka "N SKU" di layar, layar 375 px.

## E. Riwayat perubahan

- 17 Nov 2026: Update Kategori SKU (upload Excel + riwayat).
- 18 Nov 2026: menu Kategori SKU (daftar baca-saja, gambar, pencarian, filter multi-pilih).
- 19 Nov 2026: Export Excel menu Kategori SKU.
- 26 Nov 2026: info "Upload kategori terakhir" di kedua menu + tabel `tblkategori_sku_upload`.
