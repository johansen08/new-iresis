# Progress Retur (kolom Progress di Daftar Retur Penjualan)

Menu **Retur → Daftar Retur Penjualan** punya kolom **Progress** (menggantikan kolom Status) yang melacak satu resi
retur lewat lima tahap. Tiap tahap sejak Sudah Terima tersimpan lengkap di database: **petugas** + **tanggal & jam**.

| Tahap | Nama di layar | Terjadi saat | Tabel & kolom sumber | Petugas |
|---|---|---|---|---|
| 0 | Belum Terima | belum ada scan terima | — | — |
| 1 | Sudah Terima | scan **Terima Retur** (HO / Scan Retur) | `tblresiretur.tanggal_resiretur` | `tblresiretur.id_pegawai` |
| 2 | Sudah Buka | scan **Buka Retur** | `tblbukaretur.tanggal_buka_retur` (cadangan `created_at`) | `tblbukaretur.id_pegawai` |
| 3 | Sudah Serah Terima | **Cetak Serah Terima** dari tab Sudah dibuka | `tblretur_serah_terima.tanggal_serah` (**baru**) | `tblretur_serah_terima.id_pegawai` + `petugas` |
| 4 | Sudah Verifikasi | accounting verifikasi | `tblreturverifikasi.verified_at`; bila tidak ada: ACC Accounting > Returan Buka (`tblbukaretur.updated_at` saat `status_acc = 1`) | `verified_by` / `acc_by` |

- Kolom `id_pegawai` / `verified_by` / `acc_by` menyimpan **`tbluser.id_user`**. Di layar & Excel ditampilkan
  **NO ABSEN - NAMA - ROLE** (diturunkan dari `tbluser.name` yang berformat `NAMA - ROLE - NOABSEN`).
- Tanggal dan jam disimpan dalam satu kolom `datetime`; layar dan Excel memisahkannya menjadi Tanggal dan Waktu.
- Tahap dihitung dari posisi paket (`Daftar_retur_fcd::hitung_posisi`): Sudah Serah Terima hanya muncul untuk paket
  Sudah dibuka yang pernah dicetak serah terimanya. Verifikasi selalu menang (tahap 4).
- **Tab** (title case): Semua · Belum Diterima · Sudah Terima · Sudah Buka · Sudah Serah Terima · Sudah Verifikasi.
  Setelah **Cetak Serah Terima** dari tab Sudah Buka, resi yang dicentang pindah ke tab Sudah Serah Terima.
- **Filter tanggal & kolom tanggal mengikuti tab**: Belum Diterima = tanggal pesan; Sudah Terima = tanggal terima (scan pertama);
  Sudah Buka = tanggal buka; Sudah Serah Terima = tanggal serah terima; Sudah Verifikasi = tanggal verifikasi. Tab-tab itu
  memiliki kolom 4 (Tgl Terima / Buka / Serah Terima / Verifikasi, tanggal + jam). Tab Semua menyaring tiap baris dengan tanggal
  tahapnya sendiri. Tanggal terima & verifikasi dibaca per permintaan hanya untuk resi di tahap itu (tidak ada di query dasar yang di-cache).

## Tabel baru `tblretur_serah_terima`

Dibuat otomatis oleh `MY_Controller::run_serah_terima_retur_migration()` (BOOTSTRAP_VERSI `2026-11-21.1`). Satu baris per
resi per kali cetak (riwayat tidak ditimpa; layar memakai yang terakhir): `no_resi`, `tanggal_serah`, `id_pegawai`,
`petugas` (teks saat itu), `batch` (`SRH-yymmdd-His-iduser`). Dicatat oleh `Daftar_retur::serah_cetak()` hanya untuk resi
yang dicentang di tab Sudah Buka (termasuk yang tidak punya barang KE_DISPLAY sehingga tidak muncul di kertas). Masuk `keluarga_resi` di `config/arsip.php`,
jadi ikut arsip/purna bersama resinya.

## Catatan data lama

- **Serah terima sebelum 20 Nov 2026**: alurnya belum ada dan cetak tidak pernah disimpan. Keputusan: **dikosongkan dan diberi
  keterangan, bukan dibuatkan data** (tidak ada tanggal/petugas karangan, tidak ada backfill/UPDATE). Aturannya
  (`Daftar_retur_fcd::SERAH_MULAI`, `data_lama`): resi yang tanggal bukanya < 20 Nov 2026, tanpa catatan serah terima, dan
  posisinya Sudah Buka atau Sudah Verifikasi = *data lama*.
  - Tab Sudah Buka: lencana abu-abu **Lama** di samping tanggal buka (tooltip menjelaskan). Resi lama tetap bisa dicentang dan
    dicetak (jadi tercatat serah terima mulai saat itu) — tidak otomatis pindah tab.
  - Kartu Progress: baris Sudah Serah Terima bertulisan "Tidak tercatat (data lama)" + penjelasan, bukan "Belum ada catatan".
  - Excel: kolom **Keterangan Data** berisi "Data lama: serah terima tidak tercatat (dibuka sebelum 20/11/2026)".
  - Resi yang dibuka setelah 20 Nov 2026 lalu langsung diverifikasi tanpa cetak **bukan** data lama (kartu: "Belum ada catatan").
- Sekitar 125 baris `tblbukaretur` lama berstatus ACC tanpa `acc_by` (petugas "tidak tercatat" di kartu). Jalur ACC sekarang
  selalu mengisi `acc_by` dan `updated_at`.

## Tampilan

- Lencana tahap + 4 ruas penanda (Terima, Buka, Serah Terima, Verifikasi); huruf **K** kecil = ada scan retur komplain.
- Arahkan kursor ke Progress → kartu rincian: tiap tahap tanggal · jam + petugas, atau "Belum ada catatan".
- Excel (Export) menambah kolom Tanggal / Waktu / Petugas untuk Terima, Buka, Serah Terima, Verifikasi.
- Rincian dibaca hanya untuk baris di halaman yang tampil (dan seluruh hasil saat Export), bukan di query dasar yang di-cache.

## Pembaruan 21 Nov 2026 (cetak ulang, Dipesan/Diretur, kolom disembunyikan)

- **Kartu Progress memuat enam langkah**: Dipesan (tanggal pesan) -> Diretur (tanggal retur; bertanda "Perkiraan" bila tanggal retur
  diganti tanggal kirim/order) -> Sudah Terima -> Sudah Buka -> Sudah Serah Terima -> Sudah Verifikasi. Progres sebenarnya dimulai
  dari Diretur (ruas pertama selalu terisi); Dipesan hanya informasi kapan paket dipesan. Kolom **Tgl Pesan** dan **Tgl Retur**
  disembunyikan dari tabel (tetap ada di kartu dan di Excel).
- **Tab Belum Terima** (sebelumnya "Belum Diterima"): cakupan daftar tetap = retur Jubelio berstatus BARANG RETUR yang belum ada
  scan terima (hampir seluruh pesanan RETURNED yang belum diterima; pesanan RETURNED berstatus retur SELESAI / DITOLAK di Jubelio =
  komplain yang sudah diproses, sengaja tidak masuk).
- **Cetak ulang**: tab Sudah Serah Terima punya tombol **Cetak Ulang Serah Terima** (centang resi, konfirmasi). Tiap cetak = satu baris
  `tblretur_serah_terima` dengan `cetak_ke` (1 = serah terima pertama) dan `is_ulang` (1 untuk cetak ke-2 dst). Kertas ditandai
  "CETAK ULANG". Tanggal serah terima (filter, kolom, sortir) = **cetak pertama**; lencana "Ulang N x" di kolom tanggal, kartu
  menampilkan cetak ulang terakhir + petugasnya, Excel punya kolom "Jumlah Cetak Serah Terima".
- Kolom `cetak_ke` dan `is_ulang` ditambah migrasi bootstrap `2026-11-21.2` (baris lama otomatis cetak_ke 1, is_ulang 0).
