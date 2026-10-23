# Top Level

**Top level** adalah sisa stok dari restok yang tidak muat di rak display. Contoh: SKU A dibawa
1.000 pcs dari gudang, display hanya muat 100, sisa 900 disimpan di beberapa rak lain dan dicatat.
Biasanya untuk best seller supaya tidak bolak-balik ke gudang. Gunanya: tim Restok tahu display mana
yang kosong dan dari rak mana barangnya diambil.

Kapasitas display **tidak** dicatat (ukuran barang terlalu beragam). Jumlah yang diambil Restok
ditulis tangan di slip, lalu diinput CS.

## Menu

| Menu | Induk | URI | Controller | Dipakai |
|---|---|---|---|---|
| Cek Top Level | TIM PICKER | `picker/cek-top-level` | `Cek_top_level` | Picker (satu akun bersama) |
| Request Top Level | TIM CS | `cs/request-top-level` | `Top_level::request` | CS |
| Upload Top Level | TIM CS | `cs/upload-top-level` | `Top_level::upload` | CS |
| Stok Top Level | TIM CS | `cs/stok-top-level` | `Top_level::stok` | CS |
| Input Top Level | TIM CS | `cs/input-top-level` | `Top_level::input_form` | CS |

Menu dan tabel dibuat `MY_Controller::run_top_level_migration()` (`BOOTSTRAP_VERSI` 2026-10-23.1).
Hak akses menu baru disalin dari menu saudara (`picker/scan_picker` untuk Picker,
`masalah-picker-new` untuk CS), Admin selalu ikut; ubah lewat menu Access.
Tim Restok **tidak memakai sistem**. Log perubahan disimpan di `tbltoplevel_log` dan belum
punya tampilan web.

## Alur

1. **Diajukan.** Picker scan resi, klik Cek pada SKU, lalu Request Restok Display. Waktu request
   tercatat sampai detik. Resi fisik diserahkan ke CS. Satu SKU hanya boleh punya satu request aktif;
   resi lain dengan SKU sama memakai tombol Gabungkan resi.
2. **Di Restok.** CS klik Cetak slip (pratinjau kertas termal 100 x 150 mm), slip diserahkan ke
   Restok dengan tanda tangan. Cetak pertama mengubah status dan membekukan daftar rak di slip;
   waktu cetak tercatat sampai detik. Restok mengisi display, menulis qty yang diambil per rak, tanda tangan.
3. **Selesai.** Slip kembali ke CS. CS klik Input qty: qty diambil dan sisa fisik per rak. Stok rak
   berkurang dan mengikuti sisa fisik (selisih dicatat di log jenis SELISIH), status langsung Selesai.
   Resi diserahkan fisik ke picker, tidak dicatat sistem.

Cabang: **Dibatalkan** (hanya sebelum slip dicetak).

## Upload Top Level

Excel dari tim Restok, **header di baris ke-3**, data mulai baris ke-4. Kolom A-I:
`SKU, NO RAK, QTY, AC NO LIST, NAMA LIST, TGL LIST, AC NO INPUT, NAMA INPUT, TGL INPUT`.
Dua langkah: pratinjau (`dry=1`, tidak menyimpan) lalu impor (`dry=0`). Setiap baris menambah qty pada
SKU dan rak (rak baru dibuat). Baris error (SKU tidak ada di `tblsku`, rak kosong, qty bukan bilangan
bulat > 0) tidak ikut diimpor. Baris yang sama persis (hash SKU, rak, qty, pencatat, tanggal) dengan
baris lain di file atau yang pernah diimpor dilewati, jadi file yang terunggah dua kali tidak menggandakan stok.
Maksimal 5.000 baris per file.

## Input Top Level dan nomor transfer

**Input Top Level** memasukkan stok top level secara manual: nama tim Restok, lalu baris SKU, no rak, qty
(qty ditambahkan ke rak, rak baru dibuat). Nama tim punya kotak saran yang mengikuti ketikan: yang terakhir
dipakai di tbltoplevel_transfer lebih dulu, lalu pegawai aktif (`tblpegawai.status_aktif = 'AKTIF'`) yang cocok.
Teks bebas tetap diterima. Kolom SKU juga bersaran, hanya kode SKU (tanpa nama produk).
**Tanggal input** bawaannya hari ini, boleh diubah ke tanggal lampau tetapi tidak ke masa depan; disimpan di
`tbltoplevel_transfer.tgl_input` (kolom ditambahkan migrasi 2026-10-23.3, transfer lama dibiarkan NULL).
Tombol **Simpan** membuka dialog konfirmasi (tim, tanggal, total, daftar baris) sebelum benar-benar menyimpan.
Nomor selalu melanjutkan nomor terakhir tahun berjalan, terlepas dari tanggal input.

Setiap perpindahan stok top level mendapat **nomor transfer `TTL-TAHUN-NNNNN`**, masuk maupun keluar:

| Sumber | Arah | Kapan |
|---|---|---|
| `INPUT` | MASUK | Simpan di Input Top Level |
| `UPLOAD` | MASUK | Impor di Upload Top Level (ref = `UP-0001`) |
| `REQUEST` | KELUAR | Input qty dari slip selesai (ref = `RT-0001`); hanya rak yang qty diambilnya > 0 |
| `KOREKSI` | MASUK dan/atau KELUAR | Edit di Stok Top Level: kenaikan dan penurunan masing-masing mendapat nomor sendiri (ref = alasan) |

Urutan per tahun diambil dari `tbltoplevel_nomor` di dalam transaksi yang sama dengan perubahan stok: baris
penghitung terkunci sampai commit, jadi tidak ada nomor ganda dan tidak ada lubang bila transaksi gagal.
`uq_nomor` dan `uq_tahun_urut` menjaga di level database. Nomor tampil di hasil simpan Input dan Upload,
panel detail request, riwayat upload, dan pesan koreksi; baris per rak ada di `tbltoplevel_transfer_baris`
(`qty_sebelum`, `qty_sesudah`).

## Tabel

| Tabel | Isi |
|---|---|
| `tbltoplevel` | stok per SKU per rak (unik `sku, no_rak`); qty 0 tidak dihapus |
| `tbltoplevel_request` | request: status, `request_at`, `cetak_at`, `cetak_ke`, `selesai_at`, `slip` dan `hasil` (JSON) |
| `tbltoplevel_request_resi` | resi yang menunggu request |
| `tbltoplevel_import`, `tbltoplevel_import_baris` | riwayat upload dan barisnya (`hash_baris` unik) |
| `tbltoplevel_transfer`, `tbltoplevel_transfer_baris`, `tbltoplevel_nomor` | nomor transfer TTL, barisnya per SKU dan rak, dan penghitung urutan per tahun |
| `tbltoplevel_log` | jejak audit: REQUEST, SLIP, POSTING, SELISIH, UPLOAD, KOREKSI, CEK_KOSONG |

Tidak ada `DELETE` di kode ini (aturan proyek): stok habis = qty 0, request batal = status BATAL.

## Berkas

`application/controllers/Cek_top_level.php`, `Top_level.php`; `application/models/Top_level_fcd.php`;
`application/views/cek_top_level/`, `application/views/top_level/` (`_dasar.php` memuat helper JS `window.TL`);
`assets/css/top_level.css`; route di `application/config/routes.php` (akhir berkas).
Prototype UI yang disetujui ada di artifact "Cek Top Level" (alur dan tampilan).
