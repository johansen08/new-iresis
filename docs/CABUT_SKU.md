# Cabut SKU (Tim Monitoring)

Menu **Cabut SKU** (uri `cabut-sku`, anak TIM MONITORING, hanya webmaster / role 1) menampilkan SKU yang layak dicabut dari rak:

- `tblsku.kategori_sku` ∈ `EOL`, `WP EOL`, `CUT`, **dan**
- stok total Jubelio `1..10` (stok 0 tidak tampil), **dan**
- No Rak Jubelio **bukan** `CABUT` dan tidak berawalan `KR-` (keduanya dianggap sudah dicabut; rak kosong tetap tampil).
Kolom: SKU, Kategori SKU, No Rak (Jubelio), Stok Total, Qty Real; urut stok terbanyak. Tampilan mengikuti CS -> Manajemen Komplain (New): kartu ringkasan, tab kategori, pencarian, tabel dengan pil kategori.

**Tab**: (1) *Perlu Isi* = qty real belum diisi; (2) *Perlu Penyesuaian* = qty real sudah diisi dan beda dengan stok total Jubelio (ada kolom Selisih = qty real - stok). SKU yang qty real-nya sama dengan stok tidak masuk tab mana pun. Filter kategori (EOL, WP EOL, CUT) berupa dropdown **multi-pilih** dengan kotak centang; tanpa centang = semua kategori, badge menunjukkan jumlah yang dipilih, tombol Reset mengosongkan pilihan. Perbandingan memakai stok dari Perbarui terakhir.

**Foto qty real**: setiap input/ubah qty real WAJIB menyertakan foto. Browser memperkecil foto (maks 1600 px, JPEG) sebelum kirim; server memeriksa isi berkas (bukan ekstensi): JPG/PNG/WebP, maks 5 MB, minimal 200x200 px. Disimpan di assets/uploads/cabut_sku/ (nama acak), nama berkas di tblcabut_sku_stok.qty_real_foto; ikon kamera di baris membuka foto.

**Responsif**: di bawah 768 px tabel berubah menjadi kartu per SKU, tombol dan input berukuran sentuh (44 px), tab bisa digeser.

**Qty Real**: ikon pensil di kolom Qty Real membuka pop-up isian hitung fisik. Guard: hanya bilangan bulat >= 0 (digit saja, maks 9 digit). Desimal (`9,2`, `9.2`), huruf (`sembilan`), minus, spasi, dan kosong ditolak, di browser dan di server (`Cabut_sku::simpan_qty_real`, route `cabut-sku/simpan-qty-real`). Disimpan di `tblcabut_sku_stok.qty_real` (+ `qty_real_oleh`, `qty_real_at`); tombol Perbarui tidak menimpanya.

## Pembaruan 18 Nov 2026 (gambar, stok per gudang, tab No. Rak, export)

- **Gambar SKU**: tiap baris menampilkan gambar kecil (`tblsku.link_foto`, cadangan salinan lokal lewat `assets/js/foto_sku.js`) dan ikon mata untuk membuka gambar penuh di pop-up. Tanpa foto = kotak kosong.
- **Stok Jubelio** menjadi judul kolom besar dengan tiga kolom anak: **Display** (lokasi Display Barang), **Gudang** (Gudang Utama), **Total** (`total_stocks.on_hand`, yang dipakai syarat <= 10 dan pembanding qty real). Display/Gudang disimpan di `stok_display` / `stok_gudang` saat Perbarui; baris lama tampil `-` sampai Perbarui diklik sekali. Transit ikut Total tetapi tidak punya kolom sendiri, jadi Display + Gudang bisa lebih kecil dari Total.
- **Tiga tab**: *Perlu Isi Qty Real* (qty real kosong), *Perlu Penyesuaian* (qty real beda dengan stok total), *Perlu Isi No. Rak* (qty real sudah diisi **dan sama** dengan stok total). Baris pindah tab otomatis bila qty real atau stok berubah.
- **Pindah ke** (tab No. Rak): ikon pensil membuka pop-up no. rak tujuan. Validasi di browser dan server (`Cabut_sku::simpan_pindah_ke`, route `cabut-sku/simpan-pindah-ke`): wajib, **tanpa spasi**, hanya huruf, angka, `-` dan `/`, maks 50 karakter; server juga menolak bila SKU belum di tab No. Rak. Disimpan di `tblcabut_sku_stok.pindah_ke` (+ `pindah_ke_oleh`, `pindah_ke_at`). Ini hanya catatan tujuan: **tidak mengubah rak di Jubelio maupun `tblsku.no_rak`**. SKU yang sudah terisi tetap tampil di tab (urutan paling bawah, nilainya ditampilkan); angka badge/KPI = yang belum diisi.
- **Export Excel**: tombol di toolbar mengunduh tab aktif dengan filter layar (kategori + pencarian) lewat `Cabut_sku::export` (`cabut-sku/export?tab=&q=&kat[]=`), xlsx: header dua baris (grup "Stok Jubelio" digabung di atas Display/Gudang/Total), semua sel rata tengah, garis tepi horizontal dan vertikal, baris header beku + autofilter. Kolom: No, SKU, Kategori SKU, No Rak, Display, Gudang, Total, Qty Real, (Selisih di tab penyesuaian / Pindah ke di tab No. Rak), Diisi oleh, Waktu diisi.

## Alur

1. `Cabut_sku::get_data` hanya membaca tabel `tblcabut_sku_stok` (salinan stok) digabung `tblsku`. Tidak menyentuh Jubelio.
2. Tombol **Perbarui** memanggil `cabut-sku/perbarui` berulang per halaman (200 item, ±59 halaman, ±1 menit) lewat `Jubelio_stok::daftar_stok()`, lalu upsert ke `tblcabut_sku_stok`. Stok baru hanya berubah kalau tombol ini diklik.
3. Tiap halaman Perbarui juga mengambil `rack_no` dari `GET /variations/v2/` (endpoint `/inventory/v2/` tidak memuat rak) ke kolom `rak_jubelio`.
4. Stok total = `total_stocks.on_hand` Jubelio (jumlah semua lokasi: Display, Gudang Utama, Transit).

## Data

- `tblsku.kategori_sku` VARCHAR(50): diisi dari Excel KATEGORI SKU (17 Nov 2026). SKU di luar Excel bernilai NULL, jadi tidak tampil. Kategori kosong di Excel = `P<30`.
- `tblcabut_sku_stok` (`id_sku` PK, `item_id`, `stok_total`, `stok_display`, `stok_gudang`, `rak_jubelio`, `pindah_ke`, `pindah_ke_oleh`, `pindah_ke_at`, `qty_real`, `qty_real_oleh`, `qty_real_at`, `qty_real_foto`, `diperbarui`), dibuat oleh `MY_Controller::run_cabut_sku_migration()`.
- Jubelio bisa punya dua item yang hanya beda huruf besar/kecil (mis. `MICKEY` dan `mickey`); PK tidak membedakannya, jadi yang diproses terakhir menang.
- SKU kategori tersebut yang tidak ada di Jubelio tidak punya baris stok dan tidak tampil; jumlahnya ditampilkan di baris status.

## Pembaruan 19 Nov 2026

- SKU yang no. rak Jubelio-nya sudah sama dengan "Pindah ke" (tanpa peka huruf besar-kecil, spasi tepi diabaikan) dianggap selesai dicabut dan dipindah: hilang dari semua tab dan hitungan. Filter ada di `Cabut_sku_fcd::daftar()`; di layar baris langsung dibuang setelah simpan bila sudah sama.

## Pembaruan 19 Nov 2026 (kolom stok)

Kolom **Stok Jubelio** kini empat: Display Barang (F19&F19A), Gudang Utama (F16), Transit, Total Stok (kolom baru `tblcabut_sku_stok.stok_transit`, migrasi ter-gate `BOOTSTRAP_VERSI` 2026-11-19.1). Sumber sama dengan CS -> Cek Top Level (`/inventory/v2/`): Display dan Gudang = On Hand per lokasi, Transit = On Available lokasi 0 (seperti kartu Transit di Cek Top Level), Total = `total_stocks.on_hand`. Transit baru terisi setelah klik Perbarui. Uji A31-AB3: 10 / 0 / 0 / 10. Warna: Display biru, Gudang oranye, Transit ungu, Total hijau (layar dan Excel).
