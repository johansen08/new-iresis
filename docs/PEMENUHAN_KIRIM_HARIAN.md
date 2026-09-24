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
| `models/Pemenuhan_kirim_fcd.php` | Semua hitungan (satu query utama + cache) |
| `controllers/Monitoring.php` | `pemenuhan_kirim_harian()` (halaman) dan `pemenuhan_kirim_harian_data()` (JSON, dipanggil tiap menit) |
| `views/monitoring/pemenuhan_kirim_harian.php` | Tampilan + hitungan beban per packer di browser |
| `core/MY_Controller.php` | `run_pemenuhan_kirim_harian_migration()`: menu, hak akses, setelan |

## 1. Isi layar

- **Resi masuk hari ini**: resi yang di-upload ke IRESIS pada tanggal itu,
  dipecah jadi wajib keluar hari ini dan boleh keluar besok.
- **Wajib keluar hari ini** dengan rinciannya: sisa kemarin, pesanan s/d 12.00,
  dan TikTok 12.00–15.00. Satu baris tambahan muncul hanya bila jaring pengaman
  batas kirim MP menangkap sesuatu (§2).
- **Picker / Packer / HO (keluar)**: jumlah sudah, persen, dan belum. Kotak
  Packer diberi bingkai karena packing adalah tahap paling lama.
- **Detail Belum Selesai** (bisa dilipat): belum picker/packer/HO per
  **1 Qty** dan **>1 Qty**.
- **Detail OT/Perbantuan** (bisa dilipat): input jumlah packer (bawaan 8) dan
  batas per packer (bawaan 120). Keduanya bisa diubah dan hasilnya langsung
  dihitung ulang (§6).
- Pilih tanggal lain untuk melihat **rekap akhir hari** tanggal itu. Hari ini
  diperbarui otomatis tiap 1 menit. Posisi lipatan diingat per browser.

## 2. Aturan wajib keluar hari D

Setiap resi masuk ke tepat satu grup:

| Grup | Syarat | Wajib hari D? |
|---|---|---|
| KA | di-print sebelum D (≤ 7 hari), belum keluar sebelum D | ya (sisa kemarin) |
| TA | di-print D, **masuk s/d D 12.00** | ya (aturan semua MP) |
| TB | TikTok, masuk s/d D 15.00, **batas kirim MP D atau D+1** (atau kosong) | ya (target operasional TikTok) |
| TM | batas kirim MP ≤ D, tapi masuk sesudah jam | ya (jaring pengaman) |
| TX | TikTok, masuk s/d D 15.00, batas kirim MP ≥ D+2 | tidak, boleh besok |
| TC | lainnya | tidak, boleh besok |

- **Jam masuk** = `tanggal_pesan` dari Jubelio (kosong → `tanggal_printresi`).
  Pesanan tepat 12.00 atau 15.00 ikut wajib. Aturan jam sama dengan laporan
  pengiriman "wajib keluar" (`Receipt_fcd::BATAS_WAJIB_KELUAR`/`_TT`).
- **TikTok vs Tokopedia**: awalan `no_pesanan` `TT-` (keduanya ber-`id_marketplace`
  3); `id_marketplace` 5 adalah data TikTok lama.
- **Batas kirim MP** = `tanggal_bataskirim` (selalu 23.59.59, dibandingkan per
  tanggal). Lazada tidak membawa batas kirim dari upload, jadi hanya mengikuti
  aturan 12.00.
- Resi dihitung sejak **ter-upload**. Pesanan yang baru ter-upload dua hari
  kemudian tetap masuk grup wajib begitu ada (contoh nyata: pesanan 22 Sep malam
  ter-upload 24 Sep 09.26, batas kirim MP 24 Sep).

## 3. Cancel, keluar, dan tahap

- **Cancel dikeluarkan dari semua angka** (wajib, picker, packer, HO), termasuk
  yang sudah sempat discan: `status_pesanan` `CANCELED` atau `REQUEST_CANCEL`
  (daftar yang sama dengan `Cancel_order_fcd::STATUS_CANCEL`), atau `batal = '1'`.
  Status cancel datang lewat upload Jubelio, jadi angka bisa turun sedikit
  sesudah upload. Yang ditampilkan di ikon ⓘ: cancel yang di-print hari itu +
  resi lama yang cancel-nya tercatat hari itu (`modified_at`).
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

## 7. Hasil verifikasi data (produksi, 15–24 Sep 2026)

- **Aturan jam cocok dengan batas kirim MP**: tidak ada satu pun pesanan
  Shopee, Tokopedia, atau TikTok yang masuk sesudah batas jam tetapi batas kirim
  MP-nya hari itu juga. Grup TM (jaring pengaman) = 0 di semua hari.
- TikTok 12.00–15.00: 28% memang berbatas kirim MP hari itu. Yang berbatas
  **lusa** hanya muncul Jumat 18 Sep (454 dari 669) dan Sabtu 19 Sep (384 dari
  591); tampaknya TikTok memperpanjang batas di akhir pekan (lihat §9).
- Pesanan sesudah batas pada 22 Sep (wajib 23 Sep): Shopee 3.216 keluar, TikTok
  1.465 keluar, Lazada dan Tokopedia semua keluar. Resi yang "belum dipick"
  ternyata baru ter-upload 24 Sep (upload terlambat, bukan kesalahan gudang).
- Tidak ada scan ganda di picker, packer, maupun HO pada 23 Sep.
- Angka menu di `iresis_dev` (salinan 23 Sep 13.30) cocok dengan putar ulang
  data produksi pada jam yang sama, dengan selisih 2–6 resi karena status resi
  di salinan itu belum diperbarui.

## 8. Kinerja

Hitungan utama ±1 detik di atas data seukuran produksi. Hal yang membuatnya
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
   `hitung_grup()`).
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
