# Penerimaan Transfer (TIM PACKER)

Menu `packer/penerimaan-transfer`: packer menghitung ulang pinset **AKS27-RDM** yang diantar inbound dan
memasukkan qty real. Pasangan menu [Transfer Stok](TRANSFER_STOK.md) milik Purchasing.

## Aturan

- Daftar menunggu = transfer dengan `qty_terima = 0`, terlama dulu.
- Satu transfer diterima **sekali**. Berapa pun qty real, transfer langsung **Selesai**; kurang/lebihnya
  jadi selisih (`qty_terima - qty_kirim`) yang dilihat Purchasing. Tambahan stok sesudahnya lewat Penyesuaian (belum dibuat).
- Tanggal diterima wajib, default hari ini, tidak boleh lebih dari hari ini dan tidak boleh sebelum tanggal transfer.
  **Jam** (jam server) terisi hanya bila tanggal diterima = hari ini.
- Qty real bilangan bulat 1 .. 1.000.000.
- Klik **Terima** membuka popup konfirmasi (transfer, tanggal, jam, qty, stok packing sebelum -> sesudah, hasil);
  tersimpan setelah "Ya, terima stok". Pratinjau dampak stok tampil saat qty diketik.
- Riwayat penerimaan: pencarian langsung (no TR, tanggal, jam, qty, penerima), filter tanggal (preset + kalender
  dua bulan, sama dengan Transfer Stok), pagination 10/20/50.
- Tidak ada hapus/ubah penerimaan.

## Tabel

`tbltransfer_stok_terima`: `id`, `transfer_id` (**UNIQUE**: penjaga satu penerimaan per transfer, juga bila dua
packer menekan Terima bersamaan), `tanggal`, `jam` NULL, `qty`, `created`, `createdby` (penerima).
Penerimaan juga mengisi `tbltransfer_stok.qty_terima` dalam satu transaksi (baris transfer dikunci `FOR UPDATE`).
Dibuat oleh `MY_Controller::run_penerimaan_transfer_migration()` (`BOOTSTRAP_VERSI` 2026-10-30.10) bersama menu dan hak akses.

**Saldo stok packing** yang tampil di popup/pratinjau sementara = `SUM(qty)` tabel ini. Saat menu Kartu Stok
dibuat, sumber saldo pindah ke buku stok itu.

## Akses

Webmaster (role 1) dan client packer (role 4); role lain mendapat 403 JSON (`Penerimaan_transfer::ROLE_BOLEH`).
Tidak ada role "supervisor packer" di `tblhakakses`; kalau perlu, tambahkan role-nya ke `ROLE_BOLEH` dan ke
`roleaccess`.

## Kode

| Bagian | File |
| --- | --- |
| Controller | `application/controllers/Penerimaan_transfer.php` |
| Model | `application/models/Penerimaan_transfer_fcd.php` |
| View | `application/views/penerimaan_transfer/index.php` |
| Route | `packer/penerimaan-transfer`, `/menunggu`, `/riwayat`, `/terima` di `config/routes.php` |
