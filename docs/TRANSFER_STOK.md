# Transfer Stok (TIM PURCHASING)

Menu `purchasing/transfer-stok`: Purchasing mencatat pinset **AKS27-RDM** yang dikirim ke lokasi packer.
Bagian dari kontrol stok pinset; teman menu [Daftar SKU Bonus](SKU_BONUS.md).
Sisi packer: [Penerimaan Transfer](PENERIMAAN_TRANSFER.md). Menu lain (Scan Resi Packer, Kartu Stok, Penyesuaian/Opname) belum dibuat.

## Aturan

- Transfer **tidak mengubah saldo packing**. Saldo baru bertambah saat packer menerima dan memasukkan qty real
  (menu Penerimaan Transfer; mengisi `qty_terima`).
- Status hanya dua: **Dalam perjalanan** selama `qty_terima = 0`, lalu **Selesai**. Satu transfer diterima
  sekali; kurang/lebih terlihat di kolom Selisih (kosong selama belum diterima).
- Tanggal transfer wajib, tidak boleh lebih dari hari ini. **Jam** (jam server) terisi hanya bila tanggal
  = hari ini; tanggal lain jamnya `NULL`.
- Qty kirim bilangan bulat 1 .. 1.000.000. Keterangan opsional (default `transfer stok`, maks 255).
- Tidak ada hapus dan tidak ada tutup manual. Transfer yang barangnya tidak akan datang dikoreksi lewat Penyesuaian.
- Kolom **Tgl terima** dan **Jam terima** diambil dari `tbltransfer_stok_terima` (kosong selama belum diterima) dan ikut dicari.
- Tombol "Buat transfer" membuka popup konfirmasi dulu; baru simpan setelah "Ya, simpan transfer".

## Tabel `tbltransfer_stok`

`id`, `tanggal` DATE, `jam` TIME NULL, `qty_kirim`, `qty_terima` (default 0), `keterangan`,
`created/createdby/updated/updatedby`. Dibuat oleh `MY_Controller::run_transfer_stok_migration()`
(`BOOTSTRAP_VERSI` 2026-10-30.7), sekaligus menu dan hak akses.

## Akses

Webmaster (role 1) dan tim purchasing (role 7); role lain mendapat 403 JSON (`Transfer_stok::ROLE_BOLEH`).

## Kode

| Bagian | File |
| --- | --- |
| Controller | `application/controllers/Transfer_stok.php` |
| Model | `application/models/Transfer_stok_fcd.php` |
| View (tabel, filter, popup) | `application/views/transfer_stok/index.php` |
| Route | `purchasing/transfer-stok`, `/data`, `/simpan` di `config/routes.php` |

`GET /data` (q, dari, sampai, per 10/20/50, halaman). Pencarian server-side atas no TR, tanggal
(`Y-m-d` dan `d/m/Y`), jam, qty, keterangan, status, dan selisih. Filter tanggal: popover preset
(Hari ini, Kemarin, 7/30 hari terakhir, Bulan ini, Bulan lalu) + kalender dua bulan; pilihan di kalender
baru berlaku setelah "Terapkan", preset langsung berlaku.
