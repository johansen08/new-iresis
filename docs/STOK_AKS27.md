# Stok AKS27-RDM (TIM PURCHASING)

Menu `purchasing/stok-aks27`: buku stok pinset **bonus** AKS27-RDM di lokasi khusus area packer (hanya baca).
Rangkaian kontrol stok: [Daftar SKU Bonus](SKU_BONUS.md) -> [Transfer Stok](TRANSFER_STOK.md) ->
[Penerimaan Transfer](PENERIMAAN_TRANSFER.md) -> buku stok ini. Menu Scan Resi Packer (pemotong) dan
Penyesuaian/Opname belum dibuat.

## Aturan

- Ini stok **terpisah** dari stok AKS27-RDM biasa (`tblsku.total_stok`, rak/gudang dari Jubelio). AKS27-RDM yang
  **dibeli** di sebuah resi diambil dari rak biasa dan tidak pernah menyentuh buku ini; yang memotong hanya
  **1 pinset bonus per resi pemicu**, berapa pun qty/jumlah SKU-nya. Di produksi (30 Okt 2026) ada 17 resi
  yang membeli AKS27-RDM (7 juga berisi SKU pemicu, 10 hanya AKS27-RDM): yang 10 tidak memotong apa pun.
  Penjaga: SKU pemicu tidak boleh `AKS27-RDM` (ditolak di Daftar SKU Bonus); kueri pemotong nanti wajib
  memakai `sku != 'AKS27-RDM'`.
- **Tanggal mulai potong** `Stok_aks27_fcd::TANGGAL_MULAI_POTONG` = **2026-10-28** (stok 3080 masuk malam 27 Okt).
  Resi dihitung memotong bila tanggal scan packer (`tblpacking.tanggal_packing`), atau scan HO
  (`tblscan_ho.tanggal_scan`) bila tanpa catatan packer, >= tanggal itu. Resi yang discan sebelumnya tidak
  dicatat sama sekali, jadi cancel/retur-nya juga tidak menambah stok. Saat menu scan dibuat, resi sejak
  28 Okt yang sudah discan perlu ditarik sekali (aman diulang).
- Ref: INPUT (penerimaan transfer), PENJUALAN (keluar 1), CANCEL (+1, sebelum HO), RETURAN (+1, hanya bila
  pinset bonus ikut kembali dan lolos QC retur), PENYESUAIAN (manual/opname). Baris hanya bertambah.
- Kartu: **Sisa Stok**, **Penjualan** (jumlah baris PENJUALAN), **Dalam perjalanan** (jumlah qty kirim transfer
  yang belum diterima). Grafik saldo memakai 200 transaksi terakhir.

## Data

- Baris **INPUT tidak disalin**: diambil langsung dari `tbltransfer_stok_terima` (tanggal, jam, qty selalu sama
  dengan Penerimaan Transfer).
- `tblstok_aks27` menampung ref lain: `ref`, `tanggal`, `jam` NULL, `masuk`, `keluar`, `qty` (qty jual),
  `no_ref` (no resi), `keterangan`, `flag` (besar/opname), `kunci` **UNIQUE** (mis. `JUAL:<noresi>`: satu
  pemotongan per resi), `created/createdby`. Dibuat oleh `MY_Controller::run_stok_aks27_migration()`
  (`BOOTSTRAP_VERSI` 2026-10-30.11) bersama menu dan hak akses.
- Saldo berjalan (`Sisa`) dihitung di SQL (`SUM() OVER`) atas urutan waktu catat, sebelum filter apa pun.
  Tgl QC, Tgl HO, dan Status untuk baris PENJUALAN diambil dari data resi (`tblprintresi`, `tblpacking`,
  `tblscan_ho`), tidak disimpan.
- Pencarian server-side atas no, ref, keterangan, penanda, tanggal (`Y-m-d`, `d/m/Y`), jam, masuk, keluar,
  sisa, qty jual. Status resi belum ikut dicari.

## Akses

Webmaster (role 1) dan tim purchasing (role 7); role lain mendapat 403 JSON (`Stok_aks27::ROLE_BOLEH`).

## Kode

| Bagian | File |
| --- | --- |
| Controller | `application/controllers/Stok_aks27.php` |
| Model | `application/models/Stok_aks27_fcd.php` |
| View | `application/views/stok_aks27/index.php` |
| Route | `purchasing/stok-aks27`, `/ringkas`, `/data` di `config/routes.php` |

`Penerimaan_transfer_fcd::saldo()` memakai `Stok_aks27_fcd::saldo()` (saldo di popup/pratinjau penerimaan).
