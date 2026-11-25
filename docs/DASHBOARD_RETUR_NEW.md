# Dashboard Retur New

Menu **Retur → Dashboard Retur New** (`retur/dashboard-retur-new`, controller `Dashboard_retur_new`, model `Dashboard_retur_fcd`, view `views/dashboard_retur_new/index.php`). Hanya membaca data.

## Sumber angka

Tidak punya queri sendiri. `Dashboard_retur_fcd::ringkas()` memanggil `Daftar_retur_fcd::get_list()` dengan parameter tab **Semua** (semua status Jubelio, jenis B+K, tanpa batas baris), jadi definisi tahap, retur final Jubelio, lost scan, dan batas Hangus **sama persis** dengan Daftar Retur Penjualan. Total dashboard = isi tab Semua pada periode yang sama.

Rentang tanggal disaring dengan **tanggal tahap** (bukan tanggal pesan), sama seperti Daftar Retur. Preset: 7/30/90 hari, Tahun ini (default), Semua.

## Tahap

Belum Terima (tab Belum Terima + Hangus) → Sudah Terima → Sudah Buka → Sudah Serah Terima → Sudah Verifikasi → Selesai (final di Jubelio). Satu retur masuk tepat satu tahap, jadi persentase enam tahap berjumlah 100%.

## Peringkat

- **Marketplace, Kurir, Toko**: total, persentase, badge rank (#1 emas, #2 perak, #3 perunggu), dan bar komposisi tahap.
- Klik tahap atau baris untuk menyaring silang; daftar yang sedang diklik tetap menampilkan semua pilihannya.
- **Kurir** disatukan lewat aturan "mengandung" (`Dashboard_retur_fcd::$ATURAN_KURIR`): SPX, J&T, GoTo, JNE, Ninja, LEX ID; sisanya "Lainnya". Menambah kurir/varian = tambah kata kunci di aturan itu.
- **Toko**: 10 teratas + baris **Lainnya** yang bisa dibuka (rank 11 dst.), kotak cari (rank tetap global).

## Teknis

Server mengirim sel `[mp, kurir, nama_kurir_mentah, toko, tahap, jumlah]` berkamus; penyaringan silang dilakukan di browser. Hasil di-cache 120 detik di `application/cache/dashboard_retur_new_*.json` (tombol **Segarkan** melewatinya). Kelas CSS berawalan `drb-` supaya tidak bentrok dengan tema. Hak akses menu disalin dari Daftar Retur Penjualan (migrasi `run_dashboard_retur_new_menu_migration`, `BOOTSTRAP_VERSI` 2026-11-25.1).
