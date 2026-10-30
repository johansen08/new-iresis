# Daftar SKU Bonus (pinset AKS27-RDM)

Menu **TIM PURCHASING → Daftar SKU Bonus** (`purchasing/sku-bonus`), rilis 30 Okt 2026.
Ini tahap pertama kontrol stok pinset AKS27-RDM: daftar SKU pemicu. Kartu stok,
transfer Purchasing → packer, dan potong stok per resi belum dibuat.

## Aturan

- Resi yang berisi **salah satu SKU aktif** di daftar mendapat **1 pinset**, berapa
  pun jumlah SKU terdaftar atau qty-nya (dipukul rata).
- SKU **tidak dihapus**, hanya dinonaktifkan (`aktif` 0/1). Resi baru dengan SKU
  nonaktif tidak mendapat pinset.
- Tidak boleh duplikat: pengecekan di model **dan** `UNIQUE (sku)` di tabel.
  Spasi dan huruf kecil diabaikan; duplikat nonaktif juga ditolak dengan petunjuk
  "Aktifkan saja dari tabel".
- SKU baru hanya boleh dari master `tblsku` (rekomendasi muncul saat mengetik).
  `AKS27-RDM` sendiri ditolak karena itu barang bonusnya.

## Teknis

| Hal | Isi |
|---|---|
| Tabel | `tblsku_bonus` (`sku` UNIQUE, `aktif`, `created/createdby`, `updated/updatedby`) |
| Isi awal | 22 SKU dari `AKS27-RDM.xlsx`, hanya saat tabel pertama dibuat |
| Akses | webmaster (1) dan tim purchasing (7); dijaga di menu dan di controller |
| Kode | `Sku_bonus` (controller), `Sku_bonus_fcd` (model), `views/sku_bonus/index.php` |
| Migrasi | `MY_Controller::run_sku_bonus_migration()`, `BOOTSTRAP_VERSI` 2026-10-30.6 |
| Endpoint | `data` (cari + halaman, 10 per halaman), `saran`, `tambah`, `ubah-status` |
