# Stok AKS27-RDM (TIM PURCHASING)

Menu `purchasing/stok-aks27`: buku stok pinset **bonus** AKS27-RDM di lokasi khusus area packer (hanya baca).
Rangkaian kontrol stok: [Daftar SKU Bonus](SKU_BONUS.md) -> [Transfer Stok](TRANSFER_STOK.md) ->
[Penerimaan Transfer](PENERIMAAN_TRANSFER.md) -> buku stok ini. Pemotongan dari scan resi berjalan otomatis
(lihat "Sinkron scan"). Pengembalian dari cancel/retur dan opname lewat konfirmasi packer + persetujuan
purchasing (lihat "Konfirmasi pinset kembali").

## Aturan

- Ini stok **terpisah** dari stok AKS27-RDM biasa (`tblsku.total_stok`, rak/gudang dari Jubelio). AKS27-RDM yang
  **dibeli** di sebuah resi diambil dari rak biasa dan tidak pernah menyentuh buku ini; yang memotong hanya
  **1 pinset bonus per resi pemicu**, berapa pun qty/jumlah SKU-nya. Di produksi (30 Okt 2026) ada 17 resi
  yang membeli AKS27-RDM (7 juga berisi SKU pemicu, 10 hanya AKS27-RDM): yang 10 tidak memotong apa pun.
  Penjaga: SKU pemicu tidak boleh `AKS27-RDM` (ditolak di Daftar SKU Bonus); kueri pemotong nanti wajib
  memakai `sku != 'AKS27-RDM'`.
- **Tanggal mulai potong** `Stok_aks27_fcd::TANGGAL_MULAI_POTONG` = **2026-10-28** (stok 3080 masuk malam 27 Okt).
  Resi dihitung memotong bila tanggal scan packer (`tblpacking.tanggal_packing`), atau scan HO
  (`tblresikeluar.tanggal_resikeluar`, scan HO / serah terima) bila tanpa catatan packer, >= tanggal itu. Resi yang discan sebelumnya tidak
  dicatat sama sekali, jadi cancel/retur-nya juga tidak menambah stok.
- Ref: INPUT (penerimaan transfer), PENJUALAN (keluar 1), CANCEL (+1, sebelum HO), RETURAN (+1, hanya bila
  pinset bonus ikut kembali dan lolos QC retur), PENYESUAIAN (manual/opname). Baris hanya bertambah.
- Kartu: **Sisa Stok**, **Penjualan** (jumlah baris PENJUALAN), **Dalam perjalanan** (jumlah qty kirim transfer
  yang belum diterima).

## Data

- Baris **INPUT tidak disalin**: diambil langsung dari `tbltransfer_stok_terima` (tanggal, jam, qty selalu sama
  dengan Penerimaan Transfer).
- `tblstok_aks27` menampung ref lain: `ref`, `tanggal`, `jam` NULL, `masuk`, `keluar`, `qty` (qty jual),
  `no_ref` (no resi), `keterangan`, `flag` (besar/opname), `kunci` **UNIQUE** (mis. `JUAL:<noresi>`: satu
  pemotongan per resi), `created/createdby`. Dibuat oleh `MY_Controller::run_stok_aks27_migration()`
  (`BOOTSTRAP_VERSI` 2026-10-30.11) bersama menu dan hak akses.
- Saldo berjalan (`Sisa`) dihitung di SQL (`SUM() OVER`) atas urutan waktu catat, sebelum filter apa pun.
  Tgl QC, Tgl HO, dan Status untuk baris PENJUALAN diambil dari data resi (`tblprintresi`, `tblpacking`,
  `tblresikeluar`), tidak disimpan.
- Kolom nomor: **No. Transfer** (baris INPUT), **No. Resi** (`tblprintresi.noresi`, baris PENJUALAN/CANCEL/RETURAN),
  dan **No. Pesanan** (`tbldetailprintresi.no_pesanan`, diambil dari data resi, tidak disimpan). Buku hanya
  menyimpan satu `no_ref`; pemisahannya dilakukan saat dibaca.
- Pencarian server-side atas no transfer/resi, no pesanan, ref, keterangan, penanda, tanggal (`Y-m-d`, `d/m/Y`),
  jam, masuk, keluar, sisa, qty jual. Status resi belum ikut dicari.

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

## Sinkron scan (PENJUALAN dan CANCEL otomatis)

`Stok_aks27_fcd::sinkron_scan()` membaca data scan yang sudah ada, jadi tidak ada menu scan baru:

- **PENJUALAN** (keluar 1, `kunci` `JUAL:<noresi>`): resi yang berisi >= 1 SKU aktif di `tblsku_bonus` (selain
  AKS27-RDM) dan tanggal `tblpacking.tanggal_packing` (bila tidak ada catatan packer: `tblresikeluar.tanggal_resikeluar`)
  >= `TANGGAL_MULAI_POTONG`. Tepat 1 per resi; `qty` = total qty barang selain AKS27-RDM. Kolom Ket baris PENJUALAN
  dikosongkan (baris lama dari versi awal masih menyimpan catatan, tetapi tidak ditampilkan dan tidak dicari). Resi `CANCELED` / `batal = 1` dilewati.
- **CANCEL/RETURAN tidak lagi otomatis** (sejak 31 Okt 2026). Resi tercatat PENJUALAN tetap mengurangi stok
  walau lalu `CANCELED` atau diretur; pinsetnya baru masuk lewat konfirmasi packer di bawah.
- Idempoten: `INSERT IGNORE` + UNIQUE `kunci`; aman dipanggil berulang atau bersamaan.
- **Kapan jalan:** (1) segera setelah scan packer menyimpan (`Packer_fcd::save` dan `save_packer_nonsubmit` ->
  `catat_stok_aks27()`, satu resi, di luar transaksi packing, galat tidak pernah sampai ke packer); (2) tiap
  menu Stok dibuka / saldo dibaca, paling sering sekali per 30 detik (berkas penanda
  `application/cache/stok_aks27_sinkron.txt`), untuk menangkap scan HO tanpa catatan packer, status CANCELED baru,
  dan resi sejak 28 Okt yang discan sebelum fitur ini ada (pembukaan pertama menarik semuanya).
- Query digerakkan dari SKU pemicu (ribuan resi, bukan jutaan), ~0,15 detik di produksi.

## Konfirmasi pinset kembali (cancel, retur, opname)

Pinset sudah keluar dari rak saat resi discan packer, jadi cancel/retur tidak otomatis mengembalikannya:
stok baru naik setelah **packer** (pemegang stok fisik) mengonfirmasi. Model: `Stok_aks27_kembali_fcd`;
migrasi `MY_Controller::run_stok_aks27_kembali_migration()` (`BOOTSTRAP_VERSI` 2026-10-31.1).

**Menu TIM PACKER "Konfirmasi Stok AKS27-RDM"** (`packer/pinset-kembali`, `Pinset_kembali`, role 1 dan 4 = semua
client packer). Packer scan resi -> sistem memeriksa: resi harus punya baris PENJUALAN, berstatus `CANCELED`
(jenis CANCEL) atau ada di `tblresiretur` (jenis RETUR), dan belum diputuskan. Pilihan:

| Pilihan | Status | Efek stok |
| --- | --- | --- |
| Pinset ADA | KEMBALI | **+1 langsung** (ref `CANCEL` atau `RETURAN`, kunci `CANCEL:`/`RETUR:<noresi>`) |
| TIDAK ADA - pembeli tidak mengirim balik | TIDAK_KEMBALI | tidak berubah (-1 sudah benar) |
| TIDAK ADA - lupa dimasukkan saat packing | TIDAK_PERNAH_MASUK | tidak berubah sampai purchasing menyetujui ajuan +1 (pinset masih di rak) |
| RUSAK | RUSAK | tidak berubah, hanya dicatat |

Satu resi satu keputusan (`tblstok_aks27_kembali.noresi` UNIQUE), tidak bisa diubah; salah diperbaiki lewat opname.
Daftar "Menunggu konfirmasi" diturunkan (tidak disalin): PENJUALAN + CANCELED/retur - sudah diputuskan; umur > 14 hari
ditandai merah dan dihitung di kartu "Menunggu kembali" menu Stok.

**Opname** (di menu packer yang sama): packer memasukkan jumlah fisik, **tanpa melihat saldo sistem** (hitung buta).
Server menghitung `selisih = fisik - saldo` saat pengajuan dan menyimpannya di `tblstok_aks27_ajuan`
(jenis `OPNAME`). Hanya satu opname boleh menunggu pada satu waktu.

**Menu TIM PURCHASING "Persetujuan Stok AKS27"** (`purchasing/persetujuan-aks27`, `Persetujuan_aks27`, role 1 dan 7;
tanpa batas jumlah, satu persetujuan cukup). Setuju menulis satu baris `PENYESUAIAN` (flag `opname`/`koreksi`,
kunci `AJUAN:<id>`) dan mengaitkannya ke ajuan; tolak wajib beralasan dan tidak mengubah stok. Setuju yang akan
membuat saldo negatif ditolak sistem. Keputusan tidak bisa ditarik. Kartu "Menunggu persetujuan" di menu Stok.

Batas yang diketahui: selisih opname dihitung saat diajukan, bukan saat disetujui (penjualan di antaranya tidak
ikut dikoreksi); resi yang sudah CANCELED sebelum sempat tercatat PENJUALAN tidak muncul sebagai menunggu.
