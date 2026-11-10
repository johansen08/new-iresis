# Rancangan: tab "Buka Retur Otomatis" (Retur → Scan Retur Komplain)

Status: **sudah diimplementasikan** (10 Nov 2026) sebagai tab ketiga **Buka Retur Otomatis** di Retur → Scan Retur
Komplain (tidak muncul di Update Retur Komplain maupun menu retur biasa). Prototype tetap ada di
`docs/prototype_buka_retur_otomatis.html`.

Berkas: `views/retur/_buka_otomatis.php` (tab, dimuat dari `views/retur/scan_retur.php`), `Retur::buka_otomatis_rencana` (GET,
hanya baca) dan `Retur::save_buka_otomatis` (POST, satu transaksi), `Retur_fcd::rencana_buka_otomatis` dan
`Retur_fcd::kode_saran_kategori`, route `retur/buka-otomatis-rencana/(:any)` dan `retur/save-buka-otomatis`.
Tidak ada migrasi baru.

Hasil uji di browser (aplikasi nyata pada salinan DB, 10 Nov 2026): scan Enter + kolom dikosongkan, kursor terkunci,
foto SKU nyata + lightbox, rencana otomatis untuk 9 skenario (kategori, SKU di luar komplain, qty sebagian, request
pergantian, no. pesanan sebagian, tanpa komplain, dua komplain, belum Terima Retur, resi tidak dikenal), popup
konfirmasi, simpan keseluruhan dan per baris, scan ulang hanya menampilkan sisa, penolakan server (qty melebihi,
alasan kosong, status tidak sah, belum Terima Retur), rollback atomik, dan **paritas dengan Buka Retur manual**
(pemisahan qty REFUND 1 + BUKAN_RETUR 1: isi `tblbukaretur` dan `tblresiretur` identik). Tabel Manajemen Komplain CS New
membaca hasilnya dengan benar.

Tujuan: petugas scan satu resi, sistem **mencocokkan resi asli dengan data komplain**, mengisi **status detail dan
pemisahan qty** secara otomatis, menampilkan hasilnya dulu, petugas boleh **mengubah / menghapus / menambah baris**,
lalu **menyimpan keseluruhan sekaligus**.

---

## 1. Hasil telaah alur sekarang

### Layar (`views/retur/scan_retur.php`, dipakai Scan Retur, Update Retur, Scan Retur Komplain, Update Retur Komplain)

Dua tab: **Terima Retur** dan **Buka Retur**. Menu Scan Retur Komplain = layar yang sama dengan `is_komplain = true`.

### Terima Retur
- `Retur::save_retur` → `Retur_fcd::save_terima_retur`: satu baris `tblresiretur` per (resi, `is_komplain`),
  `status_retur = 'Terima Retur'`. Resi dipetakan lewat `resolusi_noresi_retur()` (resi pengembalian komplain →
  resi asli, lalu resi balik kurir Jubelio).

### Buka Retur
1. Scan resi → `Retur::get_buka_retur_sku_data($noresi)`:
   - `resolusi_noresi_retur`, `pastikan_resi_live`, lalu **gerbang** `_cek_boleh_buka_retur` (wajib sudah Terima Retur
     pada jenis yang sama, tolak salah menu).
   - Mode update: menghapus baris `tblbukaretur` resi itu dan mengembalikan `tblresiretur` ke Terima Retur.
   - Mengembalikan daftar SKU resi: `original_jumlah`, `processed_jumlah`, `jumlah` (= sisa), foto, rak
     (`Retur_fcd::get_receipt_for_buka_retur`).
   - Sejak perubahan terakhir ikut mengembalikan `saran` (status detail dari komplain).
2. Petugas memproses **per SKU** (modal, `Retur::save_buka_retur_sku`) atau **massal** (centang beberapa SKU,
   satu status untuk semuanya, `Retur::save_buka_retur_sku_bulk`, dalam satu transaksi).
3. Penyimpanan satu baris (`Retur_fcd::simpan_buka_sku`):
   - Guard qty: total yang diproses tidak boleh melebihi qty pesanan (`sisa_qty_buka`).
   - Unique key `uq_bukaretur_resi_sku_kmp_st` = (`resi_buka`, `sku`, `is_komplain`, `status_detail_buka`).
     Status yang **sama** untuk SKU yang sama digabung (qty ditambah), status **beda** jadi baris baru.
   - `alasan_ditolak` dan `sku_pergantian` hanya diisi bila baris lama belum punya.
4. Setelah tiap simpan: `tblresiretur.status_retur = 'Buka Retur'` dan `tblresiretur.status_detail = status terakhir`.

### Pemakai data hasil Buka Retur
- `tblbukaretur`: Laporan Retur, Rekap/Rekonsiliasi, Accounting (returan buka), batch **Ke Display**
  (`tblretur_display_batch*`, hanya baris belum masuk batch), Setujui Jubelio (baris `KE_DISPLAY`).
- `tblresiretur.status_detail`: label tahap di Manajemen Komplain CS New ("Retur selesai: …") dan tab Retur/Selesai.

### Arti status detail (dari penjelasan tim)
| Status | Arti |
|---|---|
| KE_DISPLAY | barang layak kembali ke display |
| REJECT | barang ditolak (alasan wajib) |
| REFUND | pengembalian dana |
| BUKAN_BARANG_KITA | yang datang bukan barang kita |
| BARANG_TIDAK_ADA | paket kembali tanpa barang |
| BUKAN_RETUR | barang yang pembeli **terima** (mis. pesan 3 SKU, 2 SKU diretur, 1 diterima) — termasuk sisa qty di luar qty komplain |
| REQUEST_DARI_PEMBELI | pergantian variasi/warna; SKU komplain ≠ SKU di resi; SKU pergantian wajib |
| PENUKARAN_BERES (PB) | pergantian selesai; SKU pergantian wajib |
| ALASAN_LAINNYA | alasan bebas (wajib ditulis) |

## 2. Celah yang ditemukan

1. **Tidak atomik antar SKU.** Mode per SKU = satu request per SKU; kalau petugas berhenti di tengah, resi setengah jadi.
   Mode massal atomik tetapi hanya satu status untuk semua SKU yang dicentang.
2. **Validasi hanya di browser.** Alasan wajib (REJECT / ALASAN_LAINNYA) dan SKU pergantian wajib (PB / REQUEST)
   tidak dicek server.
3. **`tblresiretur.status_detail` = status terakhir.** Resi dengan beberapa status (mis. REJECT 1 + BUKAN_RETUR 2)
   akan berlabel sesuai klik terakhir, sehingga label di Manajemen Komplain bisa menyesatkan (lihat pertanyaan 1).
4. **Pemecahan qty manual.** Petugas harus memecah sendiri qty per status (sisa qty tidak disarankan).
5. **Tidak ada keterkaitan dengan komplain** di layar Buka Retur (kecuali saran status detail yang baru).
6. **Mode update menghapus** baris `tblbukaretur` resi itu (kode lama) — tidak termasuk cakupan tab baru.

## 3. Rancangan

### 3.1 Alur layar (tab ke-3, hanya di menu komplain: `is_komplain = 1`)

1. **Scan resi** (asli atau resi pengembalian). Gerbang yang sama dengan Buka Retur biasa.
2. **Hasil matching** ditampilkan dulu: resi asli ↔ resi pengembalian ↔ komplain (K-xxxxx, kategori, hasil CCTV, SKU
   komplain), cara cocok (resi / no. pesanan / no. pesanan sebagian), tanggal Terima Retur, qty yang sudah diproses.
3. **Rencana** berupa tabel per SKU. Tiap baris: qty, status detail, keterangan (alasan / SKU pergantian) dan label asal
   (`otomatis · kategori`, `otomatis · qty`, `otomatis · SKU di luar komplain`, `otomatis · request pembeli`, `manual`,
   `ditambah user`, `diedit`). Aksi baris: **ubah**, **Pecah** (pindahkan 1 pcs ke baris baru), **Hapus**, serta
   **+ Tambah baris** per SKU. Tombol **Terapkan ulang otomatis** mengembalikan saran awal.
4. Indikator alokasi per SKU: `x dari y pcs dialokasikan` (hijau pas, kuning kurang, merah melebihi).
5. **Simpan keseluruhan** aktif hanya bila tidak ada baris tidak lengkap dan tidak ada SKU yang melebihi sisa.
   Alokasi kurang dari sisa **boleh** (sisanya tetap menunggu, resi bisa dibuka lagi).

### 3.2 Aturan otomatis (urutan prioritas; diuji di prototype)

1. **Cari komplain** aktif (`is_deleted = 0`): `no_resi` sama, atau `no_pesanan` komplain (boleh sebagian, ≥ 6 karakter)
   terkandung di no. pesanan resi. Harus **tepat satu**; 0 → semua baris manual; ≥ 2 → semua manual + peringatan.
2. **SKU di resi tapi tidak ada di komplain** → `BUKAN_RETUR` (qty penuh sisa).
3. **Tepat 1 SKU resi dan 1 SKU komplain yang tidak berpasangan** → `REQUEST_DARI_PEMBELI`, SKU pergantian = SKU komplain.
   Selain itu ambigu → manual.
4. **Qty komplain < qty resi** → qty komplain memakai saran kategori, sisanya `BUKAN_RETUR`. Qty yang sudah diproses
   (`tblbukaretur`) dikurangkan dulu: bagian komplain terisi lebih dulu, lalu bagian BUKAN_RETUR.
5. **Saran kategori**: Salah Kirim QC → KE_DISPLAY; Salah Kirim Ekspedisi → BUKAN_BARANG_KITA; Salah Kirim (lama) →
   QC Benar BUKAN_BARANG_KITA / QC Salah KE_DISPLAY / lainnya manual; Kurang Kirim → REFUND; Reject → REJECT;
   Paket Kosong → BARANG_TIDAK_ADA; Barang Tidak Original, Tidak Sesuai Deskripsi, Telat Kirim, Salah Alamat →
   KE_DISPLAY; Lainnya → ALASAN_LAINNYA; Paket Hilang → manual.
6. Qty komplain > qty resi → peringatan (dipakai sebesar qty resi).

Aturan 1–5 sudah ada di `Retur_fcd::saran_status_detail` (per SKU, satu baris). Tab baru perlu versi yang mengembalikan
**daftar baris per SKU** (`rencana_buka_otomatis($noresi)`); `saran_status_detail` dipanggil dari situ supaya modal
Buka Retur lama dan tab baru memakai satu sumber aturan.

### 3.3 Server

| Endpoint (route baru) | Fungsi |
|---|---|
| `GET retur/buka-otomatis-rencana/{resi}` | Gerbang `_cek_boleh_buka_retur`, lalu mengembalikan header matching + SKU (qty resi, sisa) + baris rencana. **Tidak menulis apa pun.** |
| `POST retur/save-buka-otomatis` | Menerima JSON `{noresi, baris:[{sku, qty, status, alasan, sku_pergantian}]}` dan menyimpan seluruhnya dalam **satu transaksi**. |

Aturan simpan (server memeriksa ulang, bukan percaya browser):
- Gerbang Terima Retur, mode arsip ditolak, hak akses menu sama dengan Buka Retur komplain.
- `GET_LOCK('buka_retur_<id_printresi>')` agar dua petugas / double-Enter tidak tabrakan.
- Per SKU: Σ qty baris ≤ `sisa_qty_buka` (dihitung ulang di dalam lock); status ∈ daftar sah; `alasan_ditolak` wajib untuk
  REJECT dan ALASAN_LAINNYA; `sku_pergantian` wajib untuk PENUKARAN_BERES dan REQUEST_DARI_PEMBELI.
- Baris dengan SKU + status sama digabung sebelum simpan (unique key), memakai `simpan_buka_sku` yang sudah ada.
- Semua baris gagal bersama bila satu gagal (`trans_start` / `trans_complete`), balasan memuat pesan per baris.
- Setelah sukses: `tblresiretur` diperbarui **sekali**. `status_detail` = status baris yang disimpan **terakhir**
  (urutan baris pada layar), sama persis dengan Buka Retur manual (keputusan 1).

### 3.3a Kontrak simpan: sama persis dengan Buka Retur manual

Tab baru **hanya mengganti cara mengisi dan menampilkan**; penulisan data memakai fungsi yang sama dengan
`Retur::save_buka_retur_sku` (diperiksa terhadap kode, bukan asumsi):

| Langkah | Buka Retur manual | Tab otomatis |
|---|---|---|
| Normalisasi resi | `resolusi_noresi_retur` | sama |
| Gerbang | `_cek_boleh_buka_retur($noresi, 1)` (wajib Terima Retur komplain) | sama |
| Batas qty | `sisa_qty_buka`; qty > sisa ditolak 409 | sama, dihitung ulang di dalam kunci |
| Normalisasi qty | `max(1, (int) qty)` | sama |
| Tulis `tblbukaretur` | `simpan_buka_sku`: `status_buka='Buka Retur'`, `status_detail_buka`, `resi_buka`, `hasil_scan_buka` (= resi), `sku`, `qty`, `is_komplain`, `sku_pergantian`, `alasan_ditolak`, `tanggal_buka_retur`, `id_pegawai`, `created_at`; status sama untuk SKU sama digabung (qty ditambah) | memanggil `simpan_buka_sku` yang sama, satu panggilan per baris |
| `sku_pergantian` / `alasan_ditolak` | hanya terisi untuk PB, REQUEST (pergantian) dan REJECT, ALASAN_LAINNYA (alasan); disembunyikan dan dikosongkan untuk status lain | sama (server mengabaikan nilai untuk status lain) |
| Update `tblresiretur` | `guarded_pk_update`: `status_retur='Buka Retur'`, `status_detail`, `id_pegawai`, `is_update`, `is_komplain` | sama, sekali setelah semua baris |
| Daftar status detail | 11 nilai di `scan_retur.php` (BUKAN_RETUR hanya menu komplain) | sama persis |
| Transaksi | per SKU tanpa transaksi (massal memakai transaksi) | satu transaksi `trans_start/complete` |

Tidak ada tabel, kolom, atau status baru. Laporan, rekonsiliasi, batch Ke Display, dan Accounting membaca baris yang sama.

**`tblresiretur.status_detail` pada resi multi-status:** memakai status terakhir yang disimpan, sama dengan manual
(keputusan 1, diubah dari "prioritas komplain" atas permintaan agar paritas penuh). Satu-satunya selisih dengan manual
tinggal transaksi tunggal untuk semua baris, yang tidak mengubah isi data.
Catatan: nilai ini bergantung pada urutan simpan. Label "Retur selesai: …" di Manajemen Komplain mengikutinya, sehingga
resi dengan REJECT lalu BUKAN_RETUR bisa berlabel BUKAN_RETUR. Bila ini mengganggu, aturan bisa diubah nanti tanpa
menyentuh `tblbukaretur`.

**Uji paritas (pada salinan DB, bukan produksi):** untuk resi yang sama, simpan hasil yang sama lewat Buka Retur manual
dan lewat tab otomatis, lalu bandingkan isi `tblbukaretur` dan `tblresiretur` (selain `id_*` dan waktu). Harus identik;
bila tidak, anggap bug. Sertakan kasus: qty dipecah ke beberapa status, status sama diinput dua kali (digabung),
REJECT dengan alasan, REQUEST dengan SKU pergantian, status ganti setelah alasan diketik (alasan harus tidak tersimpan).

### 3.3b Scan ulang resi yang sudah ditetapkan

Rencana hanya memuat SKU yang masih punya sisa qty. SKU yang sudah selesai diproses tidak ditampilkan lagi (cukup
catatan jumlahnya); resi yang seluruh qty-nya sudah diproses hanya menampilkan pemberitahuan dan arahan ke Update Retur.

### 3.4 Data

Tidak perlu kolom atau tabel baru untuk fungsi inti. Opsional (fase 2) untuk mengukur akurasi saran:
`tblbukaretur_saran_log` (`resi_buka`, `sku`, `qty`, `status_saran`, `status_final`, `diedit`, `id_pegawai`, `waktu`).
Dibuat lewat migrasi di `MY_Controller` dengan menaikkan `BOOTSTRAP_VERSI`.

### 3.5 Kasus tepi

| Kasus | Perilaku |
|---|---|
| Resi belum Terima Retur / salah menu | Ditolak (gerbang lama), suara salah |
| Resi tidak dikenal | Ditolak "tidak ditemukan" |
| Komplain tidak ada | Rencana kosong (manual) |
| Dua komplain aktif | Manual + peringatan; tidak ditebak |
| Sudah diproses sebagian | Rencana hanya untuk sisa; bagian komplain didahulukan |
| Sudah diproses penuh | Hanya tampilan; arahkan ke Update Retur |
| SKU komplain tidak ada di resi dan tidak berpasangan jelas | Manual + peringatan |
| Qty komplain > qty resi | Peringatan |
| Dua baris SKU + status sama | Digabung (qty dijumlah) saat simpan |
| Kategori Paket Hilang / SKU komplain kosong | Tanpa saran |
| Resi diarsipkan | Ditarik balik (`pastikan_resi_live`) seperti sekarang |

## 4. Prototype

`docs/prototype_buka_retur_otomatis.html`: halaman mandiri (tanpa CDN), logika pencocokan memakai aturan di atas.
Data uji (12 resi): Salah Kirim lama + CCTV QC Benar (termasuk scan lewat **resi pengembalian**), 2 SKU dengan 1 SKU
dikomplain, qty resi 2 komplain 1, 3 SKU dengan 1 dikomplain + QC Salah, request pergantian variasi, tanpa komplain,
dua komplain, belum Terima Retur, sudah diproses sebagian, Salah Kirim Ekspedisi, Lainnya, komplain dengan
no. pesanan sebagian. Yang bisa dicoba: ubah status, ubah qty, Pecah, Hapus, Tambah baris, validasi, Simpan (simulasi).

## 5. Fase implementasi

1. `rencana_buka_otomatis()` di `Retur_fcd` + endpoint GET (hanya baca) + tab baru yang menampilkan rencana (tanpa simpan).
2. Endpoint simpan transaksional + validasi server + kunci per resi.
3. Pencatatan saran vs final (`tblbukaretur_saran_log`) dan penyesuaian label di Manajemen Komplain bila disepakati.
4. Uji: `docs/UJI_REGRESI.md` (meja Retur) + skenario 12 resi di atas pada data salinan, bukan produksi.

## 6. Pertanyaan terbuka

1. ~~`tblresiretur.status_detail` bila resi punya beberapa status.~~ **Diputuskan: status terakhir**, sama persis dengan
   Buka Retur manual (sempat dipilih "prioritas komplain", lalu diganti demi paritas penuh).
2. **Resi yang seluruh SKU-nya BUKAN_RETUR**: tetap dianggap Buka Retur selesai? (Saat ini ya.)
3. **Komplain ambigu (dua komplain aktif)**: cukup manual, atau pilih komplain terbaru lewat dropdown di layar?
4. Perlu pencatatan saran vs final (fase 3) sejak awal atau menyusul?
