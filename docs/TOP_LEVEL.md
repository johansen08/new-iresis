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
| Daftar Transfer Top Level | TIM CS | `cs/daftar-transfer-top-level` | `Top_level::transfer_form` | CS |

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

**Input Top Level** memasukkan stok top level secara manual: **Petugas List** (yang mendata stok) dan
**Petugas Input** (yang menginput, sejajar dengan kolom NAMA LIST dan NAMA INPUT di Excel upload), lalu baris SKU,
no rak, qty (rak baru dibuat otomatis; SKU + rak yang sudah punya stok ditolak sebagai duplikat). Keduanya wajib
dan masing-masing punya kotak saran yang mengikuti ketikan: yang terakhir dipakai (di kolom petugas list maupun
petugas input) lebih dulu, lalu pegawai aktif (`tblpegawai.status_aktif = 'AKTIF'`) yang cocok. Disimpan di
`tbltoplevel_transfer.nama_tim` (petugas list) dan `nama_input` (kolom baru, migrasi 2026-10-23.4). Pada Upload
Excel kedua kolom diisi dari NAMA LIST dan NAMA INPUT (beberapa nama digabung koma, tidak ikut disarankan).
Teks bebas tetap diterima. Kolom SKU juga bersaran, hanya kode SKU (tanpa nama produk).
Tiap petugas punya tanggalnya: **Tanggal list** (pasangan Petugas List, kolom TGL LIST di Excel) dan
**Tanggal input** (pasangan Petugas Input, kolom TGL INPUT). Bawaannya hari ini, boleh ke tanggal lampau tetapi
tidak ke masa depan, dan tanggal list tidak boleh setelah tanggal input. Disimpan di `tbltoplevel_transfer.tgl_list`
(migrasi 2026-10-23.5) dan `tgl_input` (migrasi 2026-10-23.3); transfer lama dibiarkan NULL. Pada Upload Excel
keduanya diisi dari tanggal terawal di kolom TGL LIST dan TGL INPUT.
Tombol **Simpan** membuka dialog konfirmasi (petugas dan tanggalnya, total, daftar baris) sebelum benar-benar menyimpan.
Nomor selalu melanjutkan nomor terakhir tahun berjalan, terlepas dari tanggal input.

**Guard duplikat.** Input Top Level bukan penambahan stok. SKU + no rak (rak tak peka huruf besar-kecil) yang
sudah tersimpan di `tbltoplevel` dengan qty bukan nol dianggap duplikat: barisnya langsung merah saat mengetik
(`cs/input-top-level/cek`), begitu juga baris yang sama dengan baris lain di form yang sama, dan Simpan
ditolak (server memeriksa lagi sebelum transaksi dan di dalam transaksi dengan `FOR UPDATE`, jadi satu baris
duplikat menggagalkan seluruh transfer). Pasangan yang stoknya 0 boleh diinput lagi. Koreksi stok yang sudah ada
dilakukan lewat Edit di Stok Top Level. Guard ini hanya untuk Input; Upload Excel tetap menambah qty.

Setiap perpindahan stok top level mendapat **nomor transfer `TTL-TAHUN-NNNNN`**, masuk maupun keluar:

| Sumber | Arah | Kapan |
|---|---|---|
| `INPUT` | MASUK | Simpan di Input Top Level |
| `UPLOAD` | MASUK | Impor di Upload Top Level (ref = `UP-0001`) |
| `REQUEST` | KELUAR | Input qty dari slip selesai (ref = `RT-0001`); hanya rak yang qty diambilnya > 0 |
| `KOREKSI` | MASUK dan/atau KELUAR | Edit di Stok Top Level: kenaikan dan penurunan masing-masing mendapat nomor sendiri (ref = alasan) |

Urutan per tahun = nomor terakhir yang **masih ada** di `tbltoplevel_transfer` + 1, jadi transfer terakhir yang
dihapus nomornya dipakai lagi: hapus TTL-2026-00002 saja, input berikutnya kembali bernomor 00002; hapus semuanya,
mulai lagi dari 00001. Lubang di tengah tidak diisi (hapus 00001 saat ada 00002 dan 00003: berikutnya 00004).
Dihitung di dalam transaksi yang sama dengan perubahan stok: baris penghitung di `tbltoplevel_nomor` terkunci
sampai commit dan MAX dibaca dengan `FOR UPDATE`, jadi tidak ada nomor ganda dan tidak ada lubang bila transaksi
gagal. Nilai di `tbltoplevel_nomor` hanya catatan nomor terakhir yang diberikan, bukan sumber urutan.
Menghapus transfer harus lewat SQL manual (tidak ada tombol hapus); balikkan juga qty stoknya (lihat
`tbltoplevel_transfer_baris`) dan simpan log sebagai jejak audit.
`uq_nomor` dan `uq_tahun_urut` menjaga di level database. Nomor tampil di hasil simpan Input dan Upload,
panel detail request, riwayat upload, dan pesan koreksi; baris per rak ada di `tbltoplevel_transfer_baris`
(`qty_sebelum`, `qty_sesudah`).

## Daftar Transfer Top Level

Tabel semua nomor transfer `TTL-TAHUN-NNNNN`, terbaru di atas: nomor, arah (Masuk/Keluar), sumber dan ref
(Input, Upload Excel `UP-…`, Request `RT-…`, Koreksi), petugas list dan petugas input beserta tanggalnya, jumlah
baris dan qty, serta waktu dibuat sampai detik. Filter arah (Semua, Masuk, Keluar), rentang tanggal input
(`tgl_input`), dan pencarian (nomor, nama petugas, ref, atau SKU di baris transfer; karakter `%` dan `_` dicari
apa adanya). Paging 25/50/100 baris dan ringkasan total qty masuk dan keluar sesuai filter. Klik baris atau
Detail untuk rincian per SKU dan rak (qty, stok sebelum, stok sesudah). Hanya membaca, tidak mengubah data.

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
