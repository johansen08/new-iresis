# Cabut SKU (Tim Monitoring)

Menu **Cabut SKU** (uri `cabut-sku`, anak TIM MONITORING, hanya webmaster / role 1) membantu tim memilih SKU lama yang layak **dicabut dari rak display**, menghitung fisiknya, lalu mencatat ke rak mana barang dipindah. Dokumen ini menjelaskan syarat tampil, tab, kolom stok, alur Perbarui dari Jubelio, data, dan cara uji. Menu pasangannya (pengisi kategori) ada di [KATEGORI_SKU.md](KATEGORI_SKU.md).

File: `application/controllers/Cabut_sku.php`, `application/models/Cabut_sku_fcd.php`, `application/libraries/Jubelio_stok.php`, `application/views/cabut_sku/index.php`. Migrasi tabel/menu: `MY_Controller::run_cabut_sku_migration()` (ter-gate `BOOTSTRAP_VERSI`).

## 1. SKU mana yang tampil

Semua syarat harus terpenuhi (`Cabut_sku_fcd::daftar()`):

1. `tblsku.kategori_sku` ∈ `EOL`, `WP EOL`, `CUT`.
2. Stok total Jubelio (On Hand) antara **1 dan 10** (stok 0 tidak tampil).
3. No Rak Jubelio **bukan** `CABUT` dan tidak berawalan `KR-` (keduanya dianggap sudah dicabut). Rak kosong tetap tampil.
4. **Bukan** SKU yang sudah selesai pindah: kalau No Rak Jubelio **sama dengan** "Pindah ke" (tanpa peka huruf besar-kecil, spasi tepi diabaikan), SKU dianggap selesai dicabut dan dipindah, lalu hilang dari semua tab dan semua hitungan.

Urutan default: stok terbanyak dulu. Data hanya berubah bila tombol **Perbarui** diklik (lihat §5); membuka menu tidak menghubungi Jubelio.

## 2. Tiga tab

Setiap SKU berada di tepat satu tab, ditentukan otomatis oleh `Cabut_sku_fcd::tab_baris()` (dan padanannya `tabBaris()` di JS):

| Tab | Syarat | Isi tambahan |
|---|---|---|
| **Perlu Isi Qty Real** | qty real belum diisi | ikon pensil untuk mengisi |
| **Perlu Penyesuaian** | qty real sudah diisi dan **beda** dengan stok total | kolom Selisih (qty real − stok) |
| **Perlu Isi No. Rak** | qty real sudah diisi dan **sama** dengan stok total | kolom **Pindah ke** + ikon pensil |

Baris pindah tab sendiri bila qty real atau stok (setelah Perbarui) berubah. Empat kartu ringkasan di atas: SKU Perlu Dicabut (semua), Perlu Isi Qty Real, Perlu Penyesuaian (+ total selisih), Perlu Isi No. Rak (yang belum diisi).

Alat bantu: pencarian (SKU / No Rak / rak tujuan), filter kategori **multi-pilih** (tanpa centang = semua; badge jumlah; tombol Reset), pilihan 100/… baris per halaman. Di bawah 768 px tabel berubah menjadi kartu per SKU dengan tombol dan input berukuran sentuh (44 px).

## 3. Kolom stok Jubelio

Judul besar **Stok Jubelio** dengan header **dua baris** rata tengah (Stok Jubelio → nama gudang). Jenis angka (On Hand / On Order / On Reserve / On Available) **tidak dijadikan header**: tiap angka dibuat sempit (maks 2 digit) dan diberi warna; nama jenisnya muncul sebagai tooltip saat angka di-hover (desktop) atau diklik/diketuk (HP, hilang sendiri setelah ± 2 detik).

| Gudang | Lokasi Jubelio | Angka yang tampil |
|---|---|---|
| Display Barang (F19&F19A) | `location_id` −1 | On Hand, On Order, On Reserve, On Available |
| Gudang Utama (F16) | `location_id` 1 | On Hand, On Order, On Reserve, On Available |
| Transit | `location_id` 0 | hanya On Available (sama dengan kartu Transit di CS → Cek Top Level) |
| Total Stok | `total_stocks` | On Hand, On Order, On Reserve, On Available |

Warna angka: **On Hand hijau, On Order merah, On Reserve ungu, On Available biru** (urutan di tiap gudang; ada legenda di atas tabel). Sumbernya sama dengan CS → Cek Top Level (`/inventory/v2/`, `location_stocks[].on_hand/on_order/reserved/available`). Contoh A31-AB3: Display 10/0/0/10, Gudang 0/0/0/0, Transit 0, Total 10/0/0/10.

- Syarat tampil (1–10) dan pembanding qty real memakai **Total On Hand**.
- "–" berarti angka itu belum pernah diambil; klik Perbarui. Bila ada SKU begitu, muncul petunjuk oranye di kanan atas.
- Display + Gudang bisa berbeda dari Total karena Total juga memuat lokasi lain (Transit dan lain-lain).

## 4. Fitur per baris

- **Gambar SKU**: gambar kecil dari `tblsku.link_foto` (cadangan salinan lokal lewat `assets/js/foto_sku.js`); ikon mata membuka gambar penuh di pop-up. Tanpa foto = kotak kosong.
- **Salin SKU**: ikon papan klip di samping SKU menyalin teks SKU (berubah jadi centang sebentar). Memakai `navigator.clipboard` bila tersedia, jika tidak `execCommand('copy')`, karena aplikasi di LAN berjalan lewat http (bukan konteks aman).
- **Qty Real** (ikon pensil): pop-up hitungan fisik.
  - Angka: hanya bilangan bulat ≥ 0, digit saja, maks 9 digit. Desimal (`9,2` / `9.2`), huruf, minus, spasi, dan kosong ditolak di browser **dan** server (`Cabut_sku::simpan_qty_real`, route `cabut-sku/simpan-qty-real`).
  - **Foto bukti wajib** setiap input atau ubah. Browser memperkecil (maks 1600 px, JPEG); server memeriksa isi berkas (bukan ekstensi): JPG/PNG/WebP, maks 5 MB, minimal 200×200 px. Disimpan di `assets/uploads/cabut_sku/` dengan nama acak; ikon kamera di baris membuka foto.
  - Disimpan di `qty_real`, `qty_real_oleh`, `qty_real_at`, `qty_real_foto`. Perbarui tidak menimpanya.
- **Pindah ke** (hanya tab No. Rak, ikon pensil): pop-up no. rak tujuan. Wajib, **tanpa spasi**, hanya huruf, angka, `-` dan `/`, maks 50 karakter (browser + server: `Cabut_sku::simpan_pindah_ke`, route `cabut-sku/simpan-pindah-ke`; server juga menolak bila SKU belum berada di tab No. Rak). Disimpan di `pindah_ke`, `pindah_ke_oleh`, `pindah_ke_at`.
  - Ini **hanya catatan tujuan**: tidak mengubah rak di Jubelio maupun `tblsku.no_rak`.
  - SKU yang sudah terisi tetap tampil di tab (urutan paling bawah, nilainya ditampilkan) sampai No Rak Jubelio berubah menjadi sama dengan tujuan; saat itu SKU selesai dan hilang (aturan 4 di §1). Di layar baris langsung dibuang bila rak tujuan yang disimpan sama dengan rak sekarang.

## 5. Tombol Perbarui (ambil data dari Jubelio)

`Cabut_sku::perbarui` (route `cabut-sku/perbarui`, POST tanpa parameter) adalah satu-satunya jalur yang menulis stok. Alurnya satu request dari browser, tanpa loop halaman:

1. `Jubelio_stok::ambil_semua()` meminta halaman 1 `/inventory/v2/` dan `/variations/v2/` dulu untuk tahu jumlah total, lalu semua halaman sisanya **sekaligus paralel** lewat `curl_multi` (jendela 24 koneksi).
2. Penghematan request: `/inventory/v2/` menerima `page_size` besar (diuji sampai ≥ 20.000), jadi dipakai **2.000 per halaman** (± 6 request untuk ± 11.700 item). `/variations/v2/` (satu-satunya pemilik `rack_no`) menolak `page_size` > 200 dengan galat 500, jadi tetap **200** (± 59 request paralel).
3. Ketahanan: 429, 5xx, galat jaringan diulang otomatis (maks 4× per halaman dengan jeda bertahap); 401 memicu login ulang sekali; kegagalan sertifikat TLS dengan bundel CA mini otomatis diulang dengan bundel bawaan. Bila tetap gagal, proses berhenti dengan pesan Indonesia dan tidak ada yang tersimpan.
4. `Cabut_sku_fcd::simpan_halaman()` dan `simpan_rak_halaman()` meng-upsert per 500 baris dalam **satu transaksi**. Kolom `qty_real*` dan `pindah_ke*` tidak disentuh.

Ukuran dan waktu (diukur 19 Nov 2026, 11.689 item): stok ± 3 dtk, rak ± 17–25 dtk karena batas 200 per halaman; total ± 20–25 dtk (sebelumnya 118 request berurutan, > 1,5 menit). Jubelio kadang menjawab 502; klik Perbarui lagi bila muncul pesan galat.

Tidak ada cron: stok baru hanya mengikuti klik Perbarui, jadi angka di layar adalah foto saat klik terakhir (waktunya tampil di kanan atas).

## 6. Export Excel

Tombol **Export Excel** mengunduh **tab aktif** dengan filter layar (kategori + pencarian) lewat `Cabut_sku::export` (`cabut-sku/export?tab=isi|adj|rak&q=&kat[]=`). Format xlsx:

- header **tiga baris**: Stok Jubelio (digabung) → nama gudang (digabung per grup) → On Hand / On Order / On Reserve / On Available; kolom lain digabung vertikal;
- seluruh sel rata tengah dengan garis tepi horizontal dan vertikal; header berlatar ungu, nama gudang abu muda, sub-header dan angka berwarna sama dengan layar (hijau/merah/ungu/biru);
- baris header beku + autofilter; SKU ditulis sebagai teks supaya SKU angka panjang tidak jadi notasi ilmiah;
- kolom: No, SKU, Kategori SKU, No Rak, 13 kolom stok (Display×4, Gudang×4, Transit, Total×4), Qty Real, (Selisih di tab penyesuaian / Pindah ke di tab No. Rak), Diisi oleh, Waktu diisi.

## 7. Data

- `tblsku.kategori_sku` VARCHAR(50): diisi dari Excel lewat menu Update Kategori SKU ([KATEGORI_SKU.md](KATEGORI_SKU.md)). SKU di luar Excel bernilai NULL sehingga tidak tampil. Kategori kosong di Excel = `P<30`.
- `tblcabut_sku_stok` (`id_sku` PK):
  - `item_id`, `rak_jubelio`, `diperbarui`;
  - On Hand: `stok_total`, `stok_display`, `stok_gudang`; Transit: `stok_transit` (On Available);
  - angka lain: `disp_*`, `gud_*`, `tot_*` dengan akhiran `_order`, `_reserved`, `_available`;
  - catatan user: `qty_real`, `qty_real_oleh`, `qty_real_at`, `qty_real_foto`, `pindah_ke`, `pindah_ke_oleh`, `pindah_ke_at`.
  Dibuat/ditambah oleh `run_cabut_sku_migration()`; menambah kolom baru **wajib menaikkan `BOOTSTRAP_VERSI`**.
- Jubelio bisa punya dua item yang hanya beda huruf besar/kecil (mis. `MICKEY` dan `mickey`); PK tidak membedakannya sehingga yang diproses terakhir menang. Pencocokan UPDATE memakai `BINARY`.
- SKU berkategori yang tidak ada di Jubelio tidak punya baris stok dan tidak tampil; jumlahnya ditampilkan di baris status.

## 8. Cara uji

- Syntax: `C:/xampp/php/php.exe -l` untuk file yang diubah (lihat CLAUDE.md).
- Uji manual: Perbarui sampai selesai; cek satu SKU (mis. A31-AB3) dengan Cek Top Level; isi qty real + foto; qty sama stok → muncul di tab No. Rak; isi Pindah ke; Export tiap tab; layar 375 px.
- Pengujian uji-tulis jangan di produksi: pakai salinan DB (lihat `docs/LINGKUNGAN_DEV.md`). Sebelum menulis ulang `tblcabut_sku_stok` di produksi, backup dulu dengan `CREATE TABLE ... AS SELECT`.

## 9. Riwayat perubahan

- 18 Nov 2026: gambar SKU + ikon mata, stok per gudang, tab No. Rak + Pindah ke, Export Excel.
- 19 Nov 2026 (sore): kolom stok diringkas: header dua baris, angka sempit berwarna, jenis stok lewat tooltip hover/klik.
- 19 Nov 2026: SKU rak == pindah ke hilang dari daftar; kolom Transit; empat angka stok per gudang berwarna; ikon salin SKU; Perbarui paralel dengan page size besar; petunjuk bila rincian stok kosong.
