# Update Kategori SKU (Tim Purchasing)

Menu **Update Kategori SKU** (uri `purchasing/update-kategori-sku`, anak TIM PURCHASING, role webmaster 1 dan purchasing 7) mengisi `tblsku.kategori_sku` dari Excel.

## Excel

- Template: tombol **Unduh Template** (`purchasing/update-kategori-sku/template`). Header baris 1: `SKU` (A) dan `Kategori` (B); kolom berformat Teks agar SKU angka panjang tidak jadi notasi ilmiah. Sheet kedua berisi petunjuk.
- Berkas `.xlsx` / `.xls`, maksimal 10 MB dan 20.000 baris. Hanya sheet pertama dibaca.
- Kategori kosong = `P<30`. Spasi di awal/akhir dibuang.

## Aturan (Kategori_sku_fcd::proses)

Semua-atau-tidak-sama-sekali: satu baris bermasalah membatalkan seluruh upload dan pop-up **Gagal upload** menampilkan alasan per baris (maks 40 ditampilkan, sisanya dihitung). Yang divalidasi:

- header harus `SKU` / `Kategori`; berkas harus terbaca (tidak rusak/berpassword);
- SKU kosong padahal kategori terisi; SKU > 100 karakter; kategori > 50 karakter atau berisi karakter kontrol;
- sel berisi rumus, TRUE/FALSE, atau angka >= 1e15 (sudah dibulatkan Excel);
- SKU duplikat, atau kembar yang hanya beda huruf besar/kecil;
- **SKU harus sama persis** dengan `tblsku.id_sku` (huruf besar/kecil dan spasi dihitung). Bila beda huruf, pesan menyebut tulisan di database.

Bila lolos: `UPDATE tblsku SET kategori_sku` per SKU (pencocokan `BINARY`) dalam satu transaksi; pop-up **Upload berhasil** menampilkan jumlah SKU berhasil, yang berubah, dan yang sudah sama.

## Riwayat

`tblkategori_sku_riwayat` (batch, id_sku, kategori_lama, kategori_baru, id_user, created_at) mencatat setiap SKU yang nilainya berubah, per kode batch, sebagai dasar pengembalian manual. Tabel dibuat oleh `MY_Controller::run_kategori_sku_migration()`.

## Menu Kategori SKU (daftar baca-saja)

Menu **TIM PURCHASING -> Kategori SKU** (uri `purchasing/kategori-sku`, webmaster dan purchasing; `Daftar_kategori_sku` + `Daftar_kategori_sku_fcd`, view `kategori_sku/daftar.php`) menampilkan seluruh `tblsku`: SKU, gambar (ikon mata membuka gambar penuh), No Rak (`tblsku.no_rak`) dan Kategori. Pencarian memotong SKU atau no rak (tidak peka huruf besar-kecil); filter kategori multi-pilih dengan jumlah SKU per kategori, termasuk pilihan "(Belum ada kategori)" untuk SKU yang `kategori_sku`-nya NULL/kosong. Paging di server (25/50/100 baris) urut SKU. Responsif: di HP tabel menjadi kartu. Mengubah kategori tetap lewat menu Update Kategori SKU.


### Export Excel

Tombol **Export Excel** di menu Kategori SKU mengunduh semua SKU yang cocok dengan pencarian + filter kategori yang aktif (bukan hanya halaman yang tampil): kolom No, SKU, No Rak, Kategori SKU; header tebal berlatar ungu, rata tengah, garis tegak dan datar. Endpoint `purchasing/kategori-sku/export` (GET `q`, `kat[]`).
