# Pemenuhan Kirim Harian (Tim Monitoring)

Dibuat 24 September 2026. Menu **TIM MONITORING → Pemenuhan Kirim Harian**
(`monitoring/pemenuhan-kirim-harian`) menjawab tiga pertanyaan monitor harian:

1. Berapa resi masuk dan berapa yang **wajib keluar hari ini**.
2. Sampai tahap mana resi wajib itu diproses: picker, packer, HO.
3. Setelah jam 15.00: apakah **packer perlu OT atau perbantuan** tenaga.

Rancangan UI disepakati lewat prototipe bertahap dengan user (7 versi). Versi
dasbor lengkap ditolak karena terlalu banyak informasi. Prinsip yang dipegang:
satu layar, angka dulu, dan **semua teks penjelas masuk ke ikon ⓘ** (muncul
saat hover, fokus, atau ketuk), bukan paragraf di layar.

| Berkas | Isi |
|---|---|
| `models/Pemenuhan_kirim_fcd.php` | Semua hitungan (satu subquery per resi `sql_per_resi()` + cache): `snapshot()` untuk angka, `daftar_belum()` untuk popup |
| `controllers/Monitoring.php` | `pemenuhan_kirim_harian()` (halaman), `pemenuhan_kirim_harian_data()` (JSON, dipanggil tiap menit), `pemenuhan_kirim_harian_detail()` (daftar popup), `pemenuhan_kirim_harian_excel()` (unduh xlsx) |
| `views/monitoring/pemenuhan_kirim_harian.php` | Tampilan + hitungan beban per packer di browser |
| `core/MY_Controller.php` | `run_pemenuhan_kirim_harian_migration()`: menu, hak akses, setelan |

## 1. Isi layar

- **Resi masuk hari ini**: resi yang di-upload ke IRESIS pada tanggal itu,
  dipecah jadi wajib keluar hari ini dan boleh keluar besok.
- **Wajib keluar hari ini** dengan rinciannya: sisa kemarin, batas kirim MP
  hari ini (standar MP), dan TikTok s/d 15.00 (tambahan operasional) (§2).
- **Picker / Packer / HO (keluar)**: jumlah sudah, persen, belum, dan tombol
  **Lihat detail** (§1.1). Kotak Packer diberi bingkai karena packing adalah
  tahap paling lama.
- **Detail Belum Selesai** (bisa dilipat): belum picker/packer/HO per
  **1 Qty** dan **>1 Qty**.
- **Detail OT/Perbantuan** (bisa dilipat): input jumlah packer (bawaan 8) dan
  batas per packer (bawaan 120). Keduanya bisa diubah dan hasilnya langsung
  dihitung ulang (§6).
- Pilih tanggal lain untuk melihat **rekap akhir hari** tanggal itu. Hari ini
  diperbarui otomatis tiap 1 menit. Posisi lipatan diingat per browser.

### 1.1 Lihat detail dan unduh Excel

Diminta user 24 Sep 2026 (prototipe disetujui lebih dulu). Di kotak
Picker/Packer/HO ada tombol **Lihat detail** di samping "Belum" yang membuka
popup daftar resi:

- Tab tahap: Belum picker / Belum packer / Belum HO, dengan jumlahnya.
- Kolom: no resi (label Sisa kemarin / TikTok s/d 15.00), no pesanan,
  marketplace, kurir, SKU × qty, masuk IRESIS + jam pesan, batas kirim (merah
  bila lewat), posisi (belum dipick / dipick jam + nama picker / packing jam).
  **Tidak ditampilkan**: toko (`tblprintresi.toko` kosong di semua resi), no rak
  (user: tidak perlu), dan jam tutup kurir `tblkurir.jam_batas_kirim` (sempat
  tampil di rilis H/I; kolom itu tidak diisi/dipakai menu mana pun sehingga
  kebenarannya tidak terverifikasi).
- Cari (no resi, no pesanan, SKU, nama picker; kata yang cocok disorot),
  filter marketplace, kurir, jenis, kelompok wajib, posisi; urut; 50 baris per
  halaman; di layar sempit baris menjadi kartu.
- **Salin no resi**: seluruh hasil filter ke clipboard (bila browser menolak,
  mis. lewat http di IP LAN, teksnya ditampilkan terpilih untuk Ctrl+C).
- **Unduh Excel**: browser mengirim id resi hasil filter (urutan layar); server
  mengambil isi barisnya dari `daftar_belum()` yang sama, jadi hanya resi yang
  memang ada di daftar yang bisa ikut. Baris 1 judul + tanggal, baris 2 filter
  yang dipakai, header di baris 4; no resi/pesanan disimpan sebagai teks. Gagal
  (sesi habis, daftar kosong) dibalas JSON dan ditampilkan sebagai pesan.

`daftar_belum()` memakai subquery yang sama dengan angka kotak, jadi jumlah
baris popup = angka "Belum" (smoke test memeriksa ini). Data dimuat saat popup
dibuka dan disimpan di cache berkas seperti angka (55 dtk / 10 menit);
cari/filter/halaman dikerjakan di browser. Ukuran: ±1 MB JSON untuk ±4.000
resi (pagi hari, sebelum picking), ±1 dtk query. Excel 2.672 resi ±4 dtk, 50 MB.

## 2. Aturan wajib keluar hari D (standar operasional)

Menu ini mengukur **standar operasional**, bukan standar MP saja:
standar operasional = standar MP + tambahan TikTok s/d 15.00. Setiap resi
masuk ke tepat satu grup:

| Grup | Syarat | Wajib hari D? |
|---|---|---|
| KA | di-print sebelum D (≤ 7 hari), belum keluar sebelum D | ya (sisa kemarin) |
| TA | di-print D, **batas kirim MP ≤ D**; resi tanpa batas kirim (Lazada, reseller): **pesanan s/d D 12.00** | ya (standar MP) |
| TB | TikTok, pesanan s/d D 15.00, **batas kirim MP D+1** (atau kosong) | ya (tambahan operasional) |
| TX | TikTok, pesanan s/d D 15.00, batas kirim MP ≥ D+2 | tidak, boleh besok |
| TC | lainnya | tidak, boleh besok |

- **Standar MP = batas kirim MP**, bukan jam pesan (diputuskan user 24 Sep
  2026). Jam 12.00 hanya perkiraannya: dari resi Shopee 23 Sep yang dipesan
  s/d 12.00, 3.439 berbatas kirim hari itu dan hanya 9 berbatas kirim besok
  (mis. SPXID069641016539 dipesan 22 Sep 19.34 dan SPXID065758032289 dipesan
  23 Sep 00.24, keduanya baru ter-upload 16.34 dengan batas kirim 24 Sep).
  Menurut MP resi seperti itu boleh keluar besok, jadi tidak ikut wajib.
  Sebelum 24 Sep grup TA memakai "pesanan s/d 12.00" untuk semua MP, dan resi
  berbatas kirim hari ini yang dipesan sesudah jam masuk grup TM (jaring
  pengaman); TM kini sudah tercakup TA.
- **Jam pesan** = `tanggal_pesan` dari Jubelio (kosong → `tanggal_printresi`).
  Pesanan tepat 12.00 atau 15.00 ikut wajib.
- **TikTok vs Tokopedia**: awalan `no_pesanan` `TT-` (keduanya ber-`id_marketplace`
  3); `id_marketplace` 5 adalah data TikTok lama.
- **Batas kirim MP** = `tanggal_bataskirim` (selalu 23.59.59, dibandingkan per
  tanggal; nilai `0000-00-00` dianggap kosong). Lazada dan reseller tidak
  membawa batas kirim dari upload (±1.100 resi per 2 minggu), jadi mengikuti
  aturan 12.00.
- Resi dihitung sejak **ter-upload**. Pesanan yang baru ter-upload dua hari
  kemudian tetap masuk grup wajib begitu ada (contoh nyata: pesanan 22 Sep malam
  ter-upload 24 Sep 09.26, batas kirim MP 24 Sep).

### 2.1 Riwayat: "upload telat" (dicabut)

Rilis H (24 Sep 13.14) sempat memisahkan resi wajib yang di-upload sesudah
16.00 sebagai "Upload telat". User tidak memerlukannya: tujuan menu hanya
"wajib keluar sudah selesai atau belum, kalau belum mana saja". Sesudah aturan
TA memakai batas kirim MP (§2), resi Shopee yang baru ter-upload sore dengan
batas kirim besok otomatis menjadi boleh besok, dan resi TikTok s/d 15.00 yang
ter-upload sore memang tetap wajib. Tampilan, kolom, dan filter upload telat
dihapus. Baris setelan `pkh_jam_upload_terlambat` di `tb_config_operasional`
dibiarkan (tidak dibaca kode lagi; aturan proyek melarang DELETE).

## 3. Cancel, keluar, dan tahap

- **Cancel dikeluarkan dari semua angka** (wajib, picker, packer, HO), termasuk
  yang sudah sempat discan: `status_pesanan` `CANCELED` atau `REQUEST_CANCEL`
  (daftar yang sama dengan `Cancel_order_fcd::STATUS_CANCEL`), `batal = '1'`,
  **atau noresi-nya ada di Daftar Cancel Order** (`tblcancelorder`, diisi menu
  Tim Resi → Scan Cek Cancel atau sinkron Jubelio). Status cancel dari upload
  Jubelio hanya diperbarui untuk pesanan H-3 (`scripts/auto_upload_resi.py`),
  jadi resi lama yang cancel di marketplace bisa tetap PROCESSING di IRESIS
  (contoh SPXID067708609229, dipick 17 Sep, cancel di Shopee). Untuk resi
  seperti itu tim resi cukup scan di **Scan Cek Cancel**; resi langsung keluar
  dari angka (sejak 24 Sep 2026, keputusan user). Meja scan picker/packer/HO
  belum menolak resi yang hanya tercatat di Daftar Cancel Order. Yang
  ditampilkan di ikon ⓘ: cancel yang di-print hari itu + resi lama yang
  cancel-nya tercatat hari itu (`modified_at` atau `tblcancelorder.created_at`).
- **Sudah keluar** = ada scan HO (`tblresikeluar`), atau status MP sudah
  `SHIPPED` / `COMPLETED` / `RETURNED` tanpa scan HO sama sekali. Status itu baru
  berubah **sesudah** HO: dari 7.981 resi yang di-HO 23 Sep, 7.981 berubah jadi
  SHIPPED 0–24 jam sesudah scan HO. Jadi resi seperti itu sudah keluar, hanya
  scan HO-nya terlewat (jumlahnya ditulis di ikon ⓘ kotak HO).
- **Tahap bertingkat**: yang sudah keluar pasti terhitung sudah packer dan
  sudah picker; yang sudah packer pasti terhitung sudah picker. Tanpa ini, resi
  yang scan packer-nya terlewat tampil sebagai "belum packer" dan beban packer
  terlihat lebih berat. Scan ganda tidak terhitung dua kali (MIN per resi).
- Untuk tanggal lalu, scan dihitung s/d 23.59.59 tanggal itu, tetapi status
  cancel/keluar memakai status **sekarang** (status tidak punya riwayat).

## 4. Sisa ≤ 7 hari dan resi lama

Sisa dihitung untuk resi yang di-print ≤ 7 hari sebelum D. Per 23 Sep ada
**234 resi** lebih tua (PROCESSING atau status kosong, terlama Okt 2025) yang
tidak pernah keluar dan tidak pernah cancel. Kalau ikut dihitung, "belum
keluar" tidak akan pernah nol. Jumlahnya ditampilkan terpisah di ikon ⓘ kartu
Wajib keluar sebagai "perlu dicek".

## 5. 1 Qty dan >1 Qty

- **1 Qty** = 1 SKU, total qty 1: Spesial dan Reguler.
- **>1 Qty** = 1 SKU qty 2–9, 2–9 SKU qty ≤ 9, dan qty > 9.
- **Spesial** = SKU bertanda `tblsku.is_special` hari itu, atau resi yang sudah
  diproses lewat jalur 1 SKU (picking `1_SKU_PICKER` / packing `SYNC_FROM_PICKER`).
  `is_special` adalah tanda **harian** (di-set dan di-reset tiap hari), jadi untuk
  tanggal lalu hanya jalur proses yang terbaca. Spesial otomatis ter-pack saat
  dipick, jadi tidak lewat meja packer.
- Resi tanpa baris detail (mis. RESELLER) muncul sebagai baris "Tanpa rincian
  SKU" hanya bila ada.

Rata-rata waktu packing per paket dari jeda antar-scan tiap packer 16–23 Sep
(scan sinkron spesial tidak dihitung, jeda > 10 menit dibuang): Reguler ±38
detik, 1 SKU 2–9 ±43, 2–9 SKU ±50, Qty > 9 ±218. Angka ini hanya dipakai untuk
mengubah kelebihan paket menjadi menit OT (`Pemenuhan_kirim_fcd::DETIK_PACKING`).

## 6. Hitungan OT / Perbantuan

Model ambang yang diminta user:

- **Beban per packer** = ⌈paket >1 Qty belum dipacking ÷ jumlah packer⌉.
  Centang "Ikut hitung 1 Qty" menambahkan Reguler (bawaannya tidak dicentang;
  user menilai 1 Qty cepat).
- **Cukup** bila beban ≤ 85% batas; **Mepet** bila ≤ batas; **Perlu OT /
  Perbantuan** bila lewat batas, dengan saran: tambah ⌈paket ÷ batas⌉ − packer
  orang, atau OT untuk kelebihan paket (menitnya dari rata-rata waktu packing).
- Jumlah packer yang terdeteksi scan packing 1 jam terakhir dan sisa waktu
  kerja sampai jam selesai (istirahat 12.00–13.00 tidak dihitung) ada di ikon ⓘ,
  sebagai pembanding angka yang diisi user.

Setelan di `tb_config_operasional` (dibuat migrasi, bisa diubah lewat DB tanpa
mengubah kode; nilai yang sudah ada tidak ditimpa migrasi):

| Kunci | Bawaan |
|---|---|
| `pkh_jam_selesai_packer` | `18:00` |
| `pkh_default_packer` | `8` |
| `pkh_batas_per_packer` | `120` |

(`pkh_jam_upload_terlambat` dari `BOOTSTRAP_VERSI` 2026-09-24.2 tidak dipakai lagi, §2.1.)

## 7. Hasil verifikasi data (produksi, 15–24 Sep 2026)

- **Jam pesan vs batas kirim MP** (dasar keputusan §2): pesanan sesudah
  12.00 tidak pernah berbatas kirim hari itu juga (15–24 Sep). Pesanan s/d
  12.00 hampir selalu berbatas kirim hari itu, kecuali segelintir yang baru
  siap/ter-upload sore (23 Sep: 9 Shopee, 1 Tokopedia, 2 TikTok berbatas
  kirim besok; yang TikTok tetap wajib lewat TB).
- TikTok 12.00–15.00: 28% memang berbatas kirim MP hari itu. Yang berbatas
  **lusa** hanya muncul Jumat 18 Sep (454 dari 669) dan Sabtu 19 Sep (384 dari
  591); tampaknya TikTok memperpanjang batas di akhir pekan (lihat §9).
- Pesanan sesudah batas pada 22 Sep (wajib 23 Sep): Shopee 3.216 keluar, TikTok
  1.465 keluar, Lazada dan Tokopedia semua keluar. Resi yang "belum dipick"
  ternyata baru ter-upload 24 Sep (upload terlambat, bukan kesalahan gudang).
- Tidak ada scan ganda di picker, packer, maupun HO pada 23 Sep.
- Rekap 23 Sep (produksi, 24 Sep): aturan lama (pesanan s/d 12.00) wajib
  7.956, belum picker/packer/HO 5/6/6. Aturan §2: wajib **7.946**, belum
  **3/4/4** = JY1721848707, JY1724158697, GTL7250416617 (TikTok dipesan
  14.28–14.40, ter-upload 16.34/16.46, sesudah picking tutup 15.43) +
  SPXID067708609229 (cancel di Shopee, status IRESIS masih PROCESSING). Begitu
  resi terakhir itu discan di Scan Cek Cancel: **3/3/3**, sama dengan hitungan
  user. Diuji juga dengan transaksi di-rollback di `iresis_dev`.
- Angka menu di `iresis_dev` (salinan 23 Sep 13.30) cocok dengan putar ulang
  data produksi pada jam yang sama, dengan selisih 2–6 resi karena status resi
  di salinan itu belum diperbarui.

## 8. Kinerja

Hitungan utama ±1,7 detik di atas data seukuran produksi (termasuk hitungan
resi lama dan cek Daftar Cancel Order lewat indeks unik `noresi`, +0,15 dtk). Hal yang membuatnya
cepat (sebelumnya 10 detik), jangan dibongkar tanpa mengukur ulang:

- `NOT EXISTS` ke `tblresikeluar` dengan syarat tanggal membuat MariaDB 10.4
  memindai seluruh tabel (±660 rb baris, materialization). Diganti subquery
  skalar `MIN(...)` per resi yang lewat indeks `id_resi`.
- Resi sisa disaring lebih dulu lewat status: yang statusnya sudah
  keluar/cancel sebelum hari D tidak dicek scan HO-nya.
- Hasil disimpan di `application/cache/pkh_*.json`: 55 detik untuk hari ini,
  10 menit untuk tanggal lalu, 30 menit untuk hitungan resi lama (±0,4 detik).
  Kunci berkas (`pkh_*.lock`) memastikan hanya satu request yang menghitung;
  layar lain menunggu lalu memakai hasilnya. Jadi berapa pun layar yang membuka
  menu ini, beban DB tetap ±1 detik per menit.
- Driver cache bawaan CI3 sengaja tidak dipakai: di PHP 8.2 ia mencetak
  "Creation of dynamic property CI_Cache::$file is deprecated" di tengah JSON.
- Controller memanggil `session_write_close()` lebih dulu supaya request lain
  milik user yang sama tidak tertahan kunci session selama hitungan berjalan.
- Timer 1 menit berhenti sendiri begitu menu lain dibuka (halaman dimuat lewat
  AJAX, jadi timer lama dihentikan saat halaman dibuka ulang).

## 9. Keputusan terbuka

1. **TikTok 12.00–15.00 berbatas kirim lusa**: aturan sekarang (permintaan user
   24 Sep) menganggapnya boleh besok. Dampaknya hanya di akhir pekan (§7).
   Kalau bonus traffic TikTok tetap berlaku untuk pengiriman hari yang sama,
   syarat batas kirim di grup TB bisa dicabut (satu baris `CASE` di
   `sql_per_resi()`).
2. **REQUEST_CANCEL** dikeluarkan dari angka, tetapi meja Picker, Packer, HO, dan
   NDD hanya menolak `CANCELED`. Pada 23 Sep ada 3 resi REQUEST_CANCEL: 1 sudah
   di-HO, 2 sudah dipacking. Kalau memang harus ditahan, meja scan perlu diberi
   peringatan untuk status ini (perubahan terpisah).
3. Batas **7 hari** untuk sisa, dan apakah **1 Qty** ikut dihitung secara bawaan.

## 10. Catatan lain

- `Dashboard_fcd::get_simple_dashboard_data()` (dashboard lama) memakai
  `id_marketplace = 5` untuk syarat TikTok 15.00, padahal TikTok kini tercatat
  sebagai id 3 dengan awalan `TT-`. Syarat itu praktis tidak pernah cocok.
  Menu ini tidak memakai dashboard lama.
- Hak akses awal disalin dari menu saudaranya, Laporan Pesanan Masuk (role 1, 2,
  6). Role lain dibuka lewat halaman Access.
