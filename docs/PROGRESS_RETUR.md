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

## Pembaruan 21 Nov 2026 (tab Semua memuat semua retur + filter Status retur)

- **Tab Semua** kini menampilkan **seluruh retur Jubelio** (`tblreturjubelio`, satu baris per resi), bukan hanya retur ekspedisi:
  resi BARANG RETUR murni (isi tab lain) **plus** resi yang punya baris SELESAI / DITOLAK (pengembalian complain).
  Tab lain (Belum Terima ... Sudah Verifikasi) dan angkanya **tidak berubah**.
- **Filter Status retur** (multi select: SELESAI, DITOLAK, BARANG RETUR; bawaan ketiganya) hanya tampil di tab Semua. Resi campuran
  (mis. satu barang SELESAI, satu BARANG RETUR) tampil bila **salah satu** statusnya dicentang dan menampilkan dua lencana.
  Tidak ada yang dicentang = tabel kosong. Tombol atur ulang mengembalikan ketiganya.
- Kolom **Status Retur** (kolom 13, paling akhir supaya indeks kolom lain tidak bergeser) dan kolom **Status Retur** di Excel (kolom ke-29)
  hanya mengikuti tab Semua. Umur resi tanpa BARANG RETUR ditampilkan netral (tanpa warna peringatan klaim ekspedisi).
- Angka di tab Semua = isi tab Semua menurut filter status; hanya dihitung saat tab Semua aktif (baris tambahannya berat), di tab
  lain angka terakhir dibiarkan.
- **Performa**: ~72 ribu resi SELESAI/DITOLAK dimuat lewat `Daftar_retur_fcd::baris_tambahan()` (cache terpisah
  `application/cache/daftar_retur_semua1_<db>.json`, umur 10 menit, ~26 MB) dengan queri agregat `_sql_tambahan()` (~9 detik saat
  cache kosong; tanpa subquery per resi yang butuh ~17 detik). Hanya dijalankan bila tab Semua dibuka dengan SELESAI/DITOLAK terpilih.
  Tanggal verifikasi baris tambahan ikut queri itu (`vf_at`, `ac_b`, `ac_k`). Pengurutan memakai `array_multisort` (urutan identik
  dengan `usort` sebelumnya; diuji untuk semua kolom dan arah).
- Pemanggil `get_list()` tanpa `status_retur` (cetak serah terima, pilih semua hasil) berperilaku seperti dulu.

## Optimasi performa 21 Nov 2026 (cache, tugas terjadwal, hasil ukur)

Diukur di salinan prod (`iresis_prod_uji`, ~100 ribu baris `tblreturjubelio`, 72 ribu resi SELESAI/DITOLAK):

| Skenario | Sebelum | Sesudah |
|---|---|---|
| Tab selain Semua (cache hangat) | 0,2 s | 0,2 s |
| Tab Semua, cache hangat | 1,6 s | ~1,0 s |
| Tab Semua, cache kosong (user pertama) | 5,4-9 s | ~5,7 s, **dan tidak pernah dialami user bila tugas terjadwal hidup** |

- **Indeks sudah memadai**, tidak ada yang ditambah. Semua pencarian per resi memakai indeks unik / `idx_*` (`tblprintresi.noresi` unik,
  `tblreturjubelio uq_resi_pesanan_sku`, `tblresiretur.idx_resiretur_noresi`, `tblbukaretur idx_buka_resi_kmp`, `tblreturverifikasi.uq_resi`).
  Queri baris tambahan murni ~2,5 s dari ~5,7 s; sisanya mengambil/menulis 72 ribu baris di PHP. Indeks penutup di `tblprintresi`
  (294 MB prod + 616 MB arsip) hanya menghemat ~0,3 s dan memperlambat tulis tabel terpanas, jadi tidak dibuat.
- **Cache baris tambahan** memakai `serialize()` (baca ~200 ms vs JSON ~450 ms) dengan nilai bawaan NULL; status resi dipecah jadi bit
  (`stm`) supaya penyaringan tanpa `explode` per baris. Cache ditulis lewat berkas sementara + `rename` (pembaca tidak pernah melihat berkas separuh jadi).
- **Tugas terjadwal `cron segarkan_daftar_retur`** (`scripts/segarkan_daftar_retur_senyap.vbs`, tiap 2 menit): membangun ulang daftar dasar
  tiap putaran dan baris tambahan bila umurnya >= 300 detik, lalu menyentuh `application/cache/daftar_retur_warm.txt` (denyut).
  Selama denyut lebih baru dari 10 menit, cache yang sudah lewat umur **tetap disajikan** (maks 30 menit) sehingga tidak ada user yang
  menunggu hitung ulang. **Tanpa denyut** (tugas belum dibuat / mati) perilaku lama berlaku: cache lewat umur = hitung ulang saat itu juga.
  Tombol atur ulang tetap memaksa hitung ulang.
- **Kenapa tidak memuat per halaman/potongan di SQL?** Tabel sudah berpaginasi (DataTables 25 baris; rincian Progress dibaca hanya untuk
  halaman itu). Yang berat adalah hitungan *seluruh* himpunan: jumlah per tab, urut umur, dan penyaringan tanggal tahap, yang bergantung
  pada posisi paket dari 5 tabel. Itu tidak bisa dipotong per halaman tanpa mengubah angka/urutan yang dilihat user. Yang dihemat
  sudah dilakukan: baris SELESAI/DITOLAK **hanya dimuat saat tab Semua dibuka** dengan status itu tercentang.
- Pasang tugasnya sekali di PC produksi:
  `schtasks /create /tn "IRESIS - Segarkan daftar retur" /sc minute /mo 2 /rl LIMITED /f /tr "wscript.exe //B //Nologo C:\xampp\htdocs\new-iresis\scripts\segarkan_daftar_retur_senyap.vbs"`
