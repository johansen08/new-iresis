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

## Final di Jubelio = Sudah Verifikasi (21 Nov 2026)

Verifikasi accounting pada dasarnya adalah accounting menyelesaikan / menolak retur di Jubelio, dan status itu tidak bisa diubah lagi.
Karena itu, di **tab Semua**, resi yang **semua barisnya SELESAI / DITOLAK** (tanpa BARANG RETUR) dihitung **Sudah Verifikasi (tahap 4)**
walau iresis tidak punya scan atau catatan verifikasi (data lama, mis. resi `SPXID067129428281`: sebelumnya "Belum Terima").

- Aturan di `Daftar_retur_fcd::get_list()` (baris `final_jubelio` / `dari_jubelio`). Hanya baris tambahan tab Semua yang kena; tab lain berisi
  BARANG RETUR murni sehingga tidak berubah. Resi campuran dengan BARANG RETUR tidak dianggap final (mengikuti scan).
- **Tidak ada data palsu.** Langkah Terima / Buka / Serah Terima yang tidak tercatat tampil "Tidak tercatat (data lama)" di kartu Progress.
  Langkah Verifikasi yang hanya disimpulkan dari Jubelio tampil "Tanggal tidak tersedia" + keterangan "Disimpulkan dari status Jubelio:
  SELESAI / DITOLAK". Bila verifikasi memang tercatat di iresis, kartunya seperti biasa (tanggal + petugas).
- Excel: kolom **Keterangan Data** berisi "Verifikasi disimpulkan dari status Jubelio (...)" untuk resi tersebut.
- Tanggal untuk filter rentang: tanggal verifikasi iresis bila ada, lalu tanggal buka, lalu tanggal pesan.
- Perkiraan dampak (salinan prod 21 Nov): sekitar 19 ribu resi yang tadinya Sudah Buka / Sudah Serah Terima dan sekitar 16,7 ribu yang
  tadinya Belum Terima kini Sudah Verifikasi di tab Semua. Tab Sudah Verifikasi (hanya BARANG RETUR murni) tetap 1.017 di salinan uji.

- **Ukuran halaman bawaan 10 baris** (sebelumnya 25) supaya seluruh tabel muat di layar tanpa gulir; pilihan 10 / 25 / 50 / 100 tetap ada.

- **Tata letak dirapatkan** (tinggi baris 41 -> 37 px, padding tab/filter/judul kolom/bawah dikurangi) supaya halaman 10 baris + pagination muat di layar laptop (viewport ~650 px) tanpa gulir; di layar 1920x950 memang sudah muat.
- **Penyebab scroll di tema**: `page_content_onresize()` (assets/js/actions.js) menyamakan tinggi `.page-content` dengan tinggi menu samping; saat dimuat menu belum ber-scrollbar (tinggi alami ~1000 px) dan sesudahnya setinggi jendela tanpa padding atas 50 px bilah menu, jadi **semua** halaman pendek bisa digulir 50-400 px. Halaman ini memanggil `rapikanTinggi()` tiap tabel tergambar (tinggi alami isi, dinaikkan hanya sampai memenuhi jendela). Hasil ukur 1366x650 dan 1920x950: tanpa gulir di semua tab. JS tema sengaja tidak diubah (berlaku untuk semua menu).

## Tab Sudah Verifikasi lengkap (21 Nov 2026)

Sebelumnya tab Sudah Verifikasi hanya berisi resi BARANG RETUR murni yang terverifikasi di iresis (di produksi sekitar 200-an): begitu Jubelio
mengubah statusnya jadi SELESAI / DITOLAK, resi hilang dari tab ini, padahal label tab Semua sudah menyebutnya Sudah Verifikasi.

- **Isi tab Sudah Verifikasi kini sama dengan label di tab Semua**: resi final di Jubelio (SELESAI / DITOLAK) + resi yang terverifikasi di iresis.
  Baris tambahan dimuat seperti di tab Semua (cache `daftar_retur_semua2`, ~1 detik).
- **Filter Status retur** dan kolom **Status Retur** tampil di tab Sudah Verifikasi (selain tab Semua). Angka tab Sudah Verifikasi mengikuti filter
  itu dan hanya dihitung saat tab Semua / Sudah Verifikasi aktif (di tab lain angka terakhir dibiarkan). Tab lain tidak berubah.
- **Lencana kuning "Jubelio belum final"** di kolom Status Retur untuk resi yang sudah diverifikasi di iresis tetapi di Jubelio masih BARANG RETUR
  (accounting perlu menuntaskannya di Jubelio). Hanya penanda tampilan; di Excel ditulis "(Jubelio belum final)". Fungsi `Daftar_retur_fcd::belum_final_jubelio()`.
- **Tanggal verifikasi** untuk resi yang hanya disimpulkan dari status Jubelio dikosongkan ('-'), tidak lagi diisi tanggal buka. Penyaringan rentang
  tanggalnya memakai tanggal pesan (kalau tidak, resi itu hilang dari semua rentang).

## UI/UX 21 Nov 2026 (15 baris, navigasi halaman, lebar kolom Toko)

- **Ukuran halaman bawaan 15 baris** (pilihan 10 / 15 / 25 / 50 / 100). Halaman 15 baris + navigasi muat tanpa gulir bila tinggi jendela >= ~840 px
  (diukur: 1920x950 dan 1680x900 tanpa gulir; 1440x830 hanya tab Sudah Buka kelebihan 11 px karena bar info pilihan). Di laptop 1366x650 halaman
  15 baris memang perlu digulir; pilih 10 baris di "Baris per halaman" bila perlu.
- **Navigasi di bawah tabel** dirapikan: info "Menampilkan 1-15 dari N data" di kiri; "Baris per halaman" + tombol halaman di kanan dalam satu baris,
  dipisah garis tipis dari tabel. Tombol halaman persegi bersudut halus, halaman aktif hijau, tombol mati redup, ada tombol awal / akhir
  (`pagingType: full_numbers`).
- **Lebar kolom Toko**: di layar >= 1680 / >= 1900 semua kolom diberi lebar eksplisit yang jumlahnya mendekati lebar tabel, sehingga Toko tidak lagi
  menampung sisa lebar (269 px -> ~200 px di 1920; nama toko terpanjang ~160 px tetap tidak terpotong). Layar < 1680 tidak diubah (di sana
  nama toko memakai awalan marketplace sehingga memang butuh ~210 px).
- Lencana "Jubelio belum final" di kolom Status Retur kini **ikon segitiga kuning** (tooltip menjelaskan); versi teks terlalu panjang dan membuat
  tabel melebar ke samping di tab Sudah Verifikasi.

## Tab Belum Terima dibagi menurut umur (21 Nov 2026)

- **Belum Terima**: belum ada scan terima dan **umur <= 30 hari** (umur = hari sejak tanggal retur, lihat urutan sumber tanggal di atas).
- **Belum Terima (Hangus)** (tab baru, `POSISI_BELUM_HANGUS`): belum ada scan terima dan **umur > 30 hari** (`Daftar_retur_fcd::UMUR_HANGUS`).
  Paket yang tak kunjung diterima lewat batas ini dianggap hangus. Lencana Progress "Hangus" (merah), umur ditandai merah dengan tooltip,
  Excel menulis "Belum Terima (Hangus)". Filter tanggal tab ini memakai tanggal pesan, sama dengan Belum Terima.
- Angka kedua tab dijumlahkan sama dengan angka Belum Terima sebelumnya (data uji: 68 + 6.489 = 6.557). Tab lain tidak berubah.
- Tab Semua tetap menampilkan keduanya; baris hangus di tab Semua juga berlencana "Hangus".
- Tujuh tab tetap satu baris di lebar 1366 (jarak antar tab dan lencana angka dirapatkan).
- Potensi lost scan (verifikasi tanpa scan terima / buka: 28 resi di produksi) belum ditangani; menunggu keputusan.

## Detail resi saat baris diklik (23 Nov 2026)

Di semua tab Daftar Retur Penjualan, klik baris (di luar kotak centang, tombol salin, dan saat tidak sedang memblok teks) membuka
drawer dari kanan (pola UI sama dengan drawer Manajemen Komplain CS New). Endpoint: `POST retur/detail-daftar-retur-penjualan`
(`Daftar_retur::detail` → `Daftar_retur_fcd::detail_resi`), hanya membaca, tanpa cache, prod + arsip digabung per primary key.
Isi drawer: ringkasan qty per hasil buka, data pesanan, alur Progress (petugas + waktu tiap scan terima / buka / cetak serah
terima / verifikasi), tabel hasil Buka Retur **per SKU** (KE_DISPLAY / BUKAN_RETUR / REJECT / BARANG_TIDAK_ADA / dst, qty,
pergantian, alasan, jenis B/K, petugas, status ACC), barang menurut Jubelio, dan komplain CS terkait.

## Filter satu tombol (23 Nov 2026)

Bar filter Daftar Retur Penjualan sekarang: chip tanggal, **satu tombol Filter** (ikon + angka jumlah kategori aktif), atur ulang, kotak cari, Export.
Tombol membuka panel dua kolom (kategori di kiri: Jenis retur, Status Jubelio, Kurir, Marketplace, Toko; pilihan di kanan, ada pencarian untuk
Kurir/MP/Toko). Pilihan baru berlaku setelah **Terapkan** (data dimuat sekali). Filter yang berlaku tampil sebagai chip di bawah bar, tiap chip bisa
dihapus dengan x. Status Jubelio hanya muncul di tab Semua/Selesai; Jenis retur dinonaktifkan di tab Belum Terima/Hangus.
Menambah filter baru: tambah satu entri di array `KAT` (view `daftar_retur/index.php`); untuk tipe `dinamis` daftarkan juga kuncinya di `FIL`, `ajax.data`, export, dan `Daftar_retur::_saring`.

## Filter rentang Nilai dan Umur (23 Nov 2026)

Panel Filter punya dua kategori rentang: **Nilai (Rp)** (total amount per resi) dan **Umur** (hari, = kolom Umur; ada pilihan cepat ≥ 25 / ≥ 30 hari).
Isi salah satu sisi saja boleh: hanya Minimum = tanpa batas atas, hanya Maksimum = tanpa batas bawah. Minimum > Maksimum ditolak dengan pesan di panel.
Server: parameter `f_nilai_min/max`, `f_umur_min/max` (`Daftar_retur::_saring` → `Daftar_retur_fcd::get_list`), dipasang sebelum hitungan tab sehingga angka semua tab ikut menyempit; ikut ke Export dan "Pilih semua hasil".
Diuji di browser (salinan DB uji, 63.022 resi) pada 8 tab: min saja + max saja selalu menjumlah ke total tab, batas tiap baris benar, kombinasi Nilai+Umur benar.

## Filter Status Jubelio di semua tab (23 Nov 2026)

Kategori **Status Jubelio** (SELESAI / DITOLAK / BARANG RETUR) di panel Filter sekarang muncul dan berlaku di semua tab, tidak lagi hanya Semua/Selesai. Resi disaring menurut status_retur yang dimilikinya (resi campuran lolos bila salah satu statusnya dipilih); dipasang sebelum hitungan tab. Semua status terpilih = tanpa filter. Diuji di 7 tab (salinan DB uji): tiap status hanya memunculkan baris yang membawa status itu, bawaan sama dengan sebelumnya.

## Detail resi: foto SKU, tanpa nama produk (23 Nov 2026)

Drawer detail resi tidak lagi menampilkan nama produk. Setiap SKU (hasil Buka Retur, barang menurut Jubelio, SKU pergantian) memakai foto thumbnail 68 px dengan ikon mata untuk gambar penuh (lightbox; Esc / klik latar menutup, drawer tetap). SKU tanpa foto di tblsku tampil sebagai kotak penanda. Cadangan foto lokal lewat foto_sku.js. Diuji di 6 tab (salinan DB uji): semua SKU berfoto dan berikon mata.
