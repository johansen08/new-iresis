# Cabut SKU (Tim Monitoring)

Menu **Cabut SKU** (uri `cabut-sku`, anak TIM MONITORING, hanya webmaster / role 1) menampilkan SKU yang layak dicabut dari rak:

- `tblsku.kategori_sku` ∈ `EOL`, `WP EOL`, `CUT`, **dan**
- stok total Jubelio `1..10` (stok 0 tidak tampil), **dan**
- No Rak Jubelio **bukan** `CABUT` dan tidak berawalan `KR-` (keduanya dianggap sudah dicabut; rak kosong tetap tampil).
Kolom: SKU, Kategori SKU, No Rak (Jubelio), Stok Total, Qty Real; urut stok terbanyak. Tampilan mengikuti CS -> Manajemen Komplain (New): kartu ringkasan, tab kategori, pencarian, tabel dengan pil kategori.

**Qty Real**: ikon pensil di kolom Qty Real membuka pop-up isian hitung fisik. Guard: hanya bilangan bulat >= 0 (digit saja, maks 9 digit). Desimal (`9,2`, `9.2`), huruf (`sembilan`), minus, spasi, dan kosong ditolak, di browser dan di server (`Cabut_sku::simpan_qty_real`, route `cabut-sku/simpan-qty-real`). Disimpan di `tblcabut_sku_stok.qty_real` (+ `qty_real_oleh`, `qty_real_at`); tombol Perbarui tidak menimpanya.

## Alur

1. `Cabut_sku::get_data` hanya membaca tabel `tblcabut_sku_stok` (salinan stok) digabung `tblsku`. Tidak menyentuh Jubelio.
2. Tombol **Perbarui** memanggil `cabut-sku/perbarui` berulang per halaman (200 item, ±59 halaman, ±1 menit) lewat `Jubelio_stok::daftar_stok()`, lalu upsert ke `tblcabut_sku_stok`. Stok baru hanya berubah kalau tombol ini diklik.
3. Tiap halaman Perbarui juga mengambil `rack_no` dari `GET /variations/v2/` (endpoint `/inventory/v2/` tidak memuat rak) ke kolom `rak_jubelio`.
4. Stok total = `total_stocks.on_hand` Jubelio (jumlah semua lokasi: Display, Gudang Utama, Transit).

## Data

- `tblsku.kategori_sku` VARCHAR(50): diisi dari Excel KATEGORI SKU (17 Nov 2026). SKU di luar Excel bernilai NULL, jadi tidak tampil. Kategori kosong di Excel = `P<30`.
- `tblcabut_sku_stok` (`id_sku` PK, `item_id`, `stok_total`, `rak_jubelio`, `qty_real`, `qty_real_oleh`, `qty_real_at`, `diperbarui`), dibuat oleh `MY_Controller::run_cabut_sku_migration()`.
- Jubelio bisa punya dua item yang hanya beda huruf besar/kecil (mis. `MICKEY` dan `mickey`); PK tidak membedakannya, jadi yang diproses terakhir menang.
- SKU kategori tersebut yang tidak ada di Jubelio tidak punya baris stok dan tidak tampil; jumlahnya ditampilkan di baris status.
