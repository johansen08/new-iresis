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

Tampil di bawah judul kedua menu (Update Kategori SKU dan Kategori SKU), bergaya sama dengan "Update otomatis terakhir" di Daftar Retur Penjualan: waktu dan pengunggah (nama berkas muncul saat kursor di atas waktu); jumlah SKU tidak ditampilkan, hanya disimpan. Di menu upload, info langsung diperbarui setelah upload berhasil. Upload yang ditolak tidak dihitung.

Sumbernya `tblkategori_sku_upload` (satu baris per upload berhasil, termasuk yang tidak mengubah apa pun). Bila tabel itu masih kosong, dipakai batch terakhir di `tblkategori_sku_riwayat`; untuk data lama ini jumlah SKU diupload dan nama berkas tidak diketahui. View bersama: `views/kategori_sku/_upload_terakhir.php`.

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
- 28 Nov 2026: kolom penjualan ditambah menjadi 5 bulan.
- 26 Nov 2026: info "Upload kategori terakhir" di kedua menu + tabel `tblkategori_sku_upload`.

## Kolom penjualan di menu Kategori SKU (26 Nov 2026)

Lima kolom pcs terjual: bulan berjalan dan 4 bulan sebelumnya (mis. NOV/26, OKT/26, SEPT/26, AGU/26, JUL/26),
bergeser sendiri tiap ganti bulan (jumlahnya `JUMLAH_BULAN`; cache dengan susunan bulan lain diabaikan). Ikut di layar dan Export Excel.

* Aturan: `status_pesanan = 'COMPLETED'` dan `status_label_mp` bukan `Paket Hilang`; bulan menurut `tanggal_pesan`;
  jumlah `tbldetailprintresi.jumlah` per SKU. SHIPPED / PROCESSING / RETURNED / CANCELED tidak dihitung, jadi bulan
  berjalan masih bertambah.
* Sumber: prod + resi yang hanya ada di arsip (purna).
* **Angka orange** (layar dan Excel) = SKU itu di bulan itu masih punya pesanan belum final (status selain
  COMPLETED / RETURNED / CANCELED). Ditandai per angka, bukan seluruh kolom. Pesanan + SKU yang sudah punya salinan
  berstatus final (resi lama yang diganti) diabaikan. Pesanan tanpa nomor resi tidak ada di iresis, jadi tidak ikut.
* **Judul kolom** membawa jumlah SKU yang angka penjualannya belum final, mis. `NOV/26 (103)` = 103 SKU berangka
  orange di bulan itu, dihitung dari semua SKU yang cocok dengan pencarian / filter (bukan hanya halaman tampil). Tidak tampil bila 0. Ikut di judul Export Excel; di HP tampil sebagai ringkasan
  di atas daftar.
* **Sort** per kolom bulan: klik judul (terbanyak, tersedikit, kembali urut SKU); di HP lewat pilihan "Urut".
  Berlaku untuk semua SKU yang cocok (bukan hanya halaman tampil) dan ikut di Export Excel.
* Cache `application/cache/penjualan_sku_YYYY-MM.json`, masa berlaku 30 menit. Layar **selalu** tampil dari cache
  (tabel tidak pernah menunggu hitungan). Bila basi / belum ada, layar memanggil `hitung-ulang` di latar; selama
  halaman terbuka pembaruan juga terpicu sendiri tiap 30 menit. Halaman tertutup = tidak ada yang menghitung, dan
  pembuka berikutnya yang memicunya. Export Excel menghitung dulu bila cache basi.
* Tombol **Hitung ulang** -> `POST purchasing/kategori-sku/hitung-ulang`; progres dipantau lewat
  `GET purchasing/kategori-sku/status-hitung` (`penjualan_sku_progres.json`). Satu hitungan saja pada satu waktu
  (`penjualan_sku.lock`); permintaan lain mendapat `sibuk` lalu ikut memantau. Gagal = cache lama tetap dipakai,
  layar mencoba lagi 5 menit kemudian.
* **Paralel:** 24 potongan rentang `id_printresi` per database, dikerjakan 6 koneksi baca-saja (mysqli async).
  Uji 26 Nov 2026 di prod: ±2,2 dtk (satu koneksi ±13 dtk), hasil identik. Bila koneksi tambahan tidak bisa dibuat,
  otomatis satu per satu. Setelan: `POTONGAN_PER_DATABASE`, `KONEKSI_PARALEL`.
* Memaksa hitung ulang cukup lewat tombol. Logika: `Daftar_kategori_sku_fcd::hitung_ulang()`.
