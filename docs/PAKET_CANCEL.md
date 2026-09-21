# Alur Paket Cancel (Deteksi → Cek Retur → Restock ke Display)

Dirancang 21 September 2026. Dokumen ini satu-satunya rujukan alur paket
cancel; `ANALISIS_PROGRAM.md` dan `WORKFLOW_DIAGRAM.md` hanya menunjuk ke sini.
Laporan Resi Cancel (`report/resi-cancel-report`) adalah jendela utamanya.

Status rilis per tahap ada di §9. **Tahap 1** (tabel + pencatatan scan yang
ditolak) sudah live; tahap 2–3 masih rancangan dan bisa berubah.

## 1. Masalah yang diselesaikan

Status `CANCELED` masuk ke iresis **bukan real-time**, melainkan saat tim
resi meng-upload data Jubelio (mis. resi diprint 20 Sep 16.16, cancel baru
diketahui 21 Sep 08.11). Sementara itu paketnya sudah bergerak: picker sudah
mengambil barang dari display, mungkin sudah dipacking, mungkin sudah di
meja HO.

Begitu status cancel masuk, scan berikutnya di meja mana pun ditolak dengan
"Pesanan sudah DIBATALKAN" — tetapi sebelum rilis ini penolakan itu **tidak
dicatat sama sekali**. Akibatnya:

1. Tidak ada jejak paket cancel itu sampai di meja mana. Laporan hanya tahu
   scan yang *tercatat*; "packer lupa scan lalu paket sampai HO" dan "cancel
   sebelum packer, paket ditarik" tampak persis sama.
2. Tidak ada alur pengembalian barang ke display. Tidak ada yang tahu apakah
   barang benar-benar kembali, berapa yang kembali, dan siapa yang
   bertanggung jawab kalau tidak kembali.
3. Kalau isi paket cancel ternyata **kurang atau salah** (resi ZU-1 qty 3,
   yang diambil hanya 2), tidak ada pencatatan — saat restock ke display,
   yang dikembalikan 2 tapi tidak ada yang menjelaskan kenapa bukan 3.

Alur lost scan biasa (`docs/LOST_SCAN.md`) pun buntu untuk resi cancel:
pengecekan cancel berjalan *sebelum* `NOT_PICKED`/`NOT_PACKED`, dan
`Lost_scan_picker_fcd::tambah_picker()` sengaja menolak membuat picking untuk
pesanan cancel.

## 2. Prinsip

Paket cancel adalah **retur internal**: barang sudah keluar dari display tapi
tidak pernah keluar gudang. Karena itu alurnya meniru alur retur yang sudah
jalan (Terima → Buka → Batch ke Display) dan **menumpang** mekanisme yang
sudah ada:

- selisih isi (kurang / salah / tidak ambil) dicatat sebagai baris
  `tblmasalahpicker` — sehingga slip CS, Daftar Masalah Picker, dan KPI
  picker membacanya tanpa perubahan;
- pengiriman ke display memakai `tblretur_display_batch` yang sudah dipakai
  tim restock.

Sama seperti LOST_SCAN.md: pencatatan dilakukan **di meja tempat paket
ditemukan**, dan urutan dipaksa oleh penjaga — item tidak bisa masuk batch
sebelum DICEK, batch tidak SELESAI sebelum DITERIMA.

## 3. Istilah

| Istilah | Arti |
|---|---|
| **Cancel diketahui** | Saat iresis pertama kali tahu resi cancel: `tblcancelorder.tanggal_cancel` bila ada, kalau tidak `tblprintresi.modified_at` (upload resi hanya menulis kolom itu saat `status_pesanan` berubah). |
| **Tahap saat cancel** | Sampai tahap mana resi **tercatat** diproses ketika cancel diketahui: `Belum diproses` / `Setelah Picker` / `Setelah Packer` / `Setelah HO (sudah keluar)`. Dihitung dari scan yang terjadi *sebelum* cancel diketahui. |
| **Scan ditolak** | Scan di meja picker/inbound/packer/HO (atau proses lost scan) yang ditolak karena resi cancel. Sejak tahap 1 setiap penolakan ditulis ke `tblcancel_paket_tolak`. |
| **Paket cancel** | Baris `tblcancel_paket`: satu per resi cancel yang barangnya **sudah keluar display**. Dibuat otomatis dari scan ditolak pertama, lalu berjalan DITEMUKAN → DICEK → DIKIRIM → SELESAI. |
| **Paket cancel tanpa jejak** | Resi cancel yang punya scan picker (barang sudah diambil) tapi tidak pernah ditolak di meja mana pun dan belum punya baris `tblcancel_paket`. Barang terakhir di tangan picker. |
| **Barang sudah diambil** | Penentu apakah scan ditolak melahirkan paket cancel: ya bila tahap penolakan INBOUND/PACKER/HO/LOST_SCAN, atau PICKER dengan baris `tblresiambilbarang` sudah ada. Penolakan di picker tanpa picking = hanya label resi yang sampai, barang masih di display. |

## 4. Kapan paket cancel wajib lewat alur ini

| Tahap saat cancel | Barang ada di mana | Alur ini? |
|---|---|---|
| Belum diproses | masih di display; picker akan ditolak saat scan | tidak — hanya label resi yang dibuang |
| Setelah Picker / Setelah Packer, atau ditolak di inbound/packer/HO | **sudah keluar display, masih di gudang** | **ya, wajib** |
| Setelah HO (sudah keluar) | di kurir, akan kembali sebagai retur | tidak — masuk Terima Retur biasa |

## 5. Alur lengkap

```
 Meja picker / inbound / packer / HO: scan ditolak "DIBATALKAN"
   → tblcancel_paket_tolak: siapa, meja mana, jam                     [tahap 1]
   → bila barang sudah diambil: tblcancel_paket DITEMUKAN             [tahap 1]
   → petugas taruh paket ke keranjang CANCEL, serahkan ke tim retur
                    │
                    ▼
 TIM RETUR — menu "Cek Paket Cancel": scan resi                        [tahap 2]
   → layar tampilkan isi seharusnya (tbldetailprintresi: SKU, qty)
   → petugas isi per baris: qty ditemukan, SKU yang sebenarnya ada, kondisi
   → sistem hitung selisih otomatis:
        ZU-1 qty 3, ditemukan 2      → KURANG AMBIL 1   ┐ baris tblmasalahpicker
        ZU-1 qty 1, ditemukan BSBI-5 → SALAH AMBIL      ┤ atas nama picker resi
        ZU-1 qty 1, ditemukan 0      → TIDAK AMBIL      ┘ itu (tblresiambilbarang)
   → status DICEK; daftar "harus kembali ke display" = yang DITEMUKAN
     (ZU-1 ×2, BSBI-5 ×1), BUKAN yang tertulis di resi
   → barang RUSAK tidak ikut batch display: masuk jalur REJECT Buka Retur
                    │
                    ▼
 Batch ke display (tblretur_display_batch, sumber CANCEL)              [tahap 3]
   → status DIKIRIM, isi = qty ditemukan per SKU; paket cancel → DIKIRIM
                    │
                    ▼
 TIM RESTOCK — terima batch: scan SKU, isi qty diterima                [tahap 3]
   → cocok  → DITERIMA, paket cancel SELESAI
   → kurang → selisih tercatat di detail batch, batch tetap ditutup
```

## 6. Siapa bertanggung jawab kalau macet

Tiap tahap punya pemilik dan batas waktu; laporan menampilkan yang lewat
batas beserta nama. Angka batas waktu belum diputuskan (lihat §10).

| Kondisi | Bukti di sistem | Penanggung jawab |
|---|---|---|
| Ada scan picker, **tidak pernah** ditolak di meja mana pun, belum DITEMUKAN | `tblresiambilbarang` ada, `tblcancel_paket` kosong | **Picker** — barang terakhir di tangannya. Tab "tanpa jejak" untuk tim retur berburu |
| DITEMUKAN tapi belum DICEK > N jam | `ditemukan_oleh`, `ditemukan_at` | Petugas meja penemu (belum menyerahkan) / tim retur (belum mengecek) |
| Isi kurang / salah saat dicek | `tblmasalahpicker` (slip CS & KPI yang sudah ada) | **Picker** |
| DICEK tapi belum masuk batch > N jam | `dicek_at`, `id_batch` kosong | Tim retur |
| Batch DIKIRIM belum DITERIMA | `tblretur_display_batch.status` | Tim restock |
| Qty diterima < qty dikirim | detail batch: `qty` vs qty diterima | Serah-terima retur → restock (dua nama tercatat di batch) |

## 7. Tahap 1 — pencatatan scan yang ditolak (live)

### 7.1 Titik pencatatan

Semua lewat `Cancel_paket_fcd::catat_tolak($resi, $tahap, $user, $opsi)`.
Tidak ada perilaku penolakan yang berubah; pesan dan `EXCEPTION_CODE` tetap
sama.

| # | Tempat | Tahap | Catatan |
|---|---|---|---|
| 1 | `Picking_fcd::save()` — Scan Picker, Pending Picker, Update Picker | `PICKER` | dipanggil **setelah** `trans_rollback()` model. Paket cancel dibuat hanya bila picking sudah ada (Update Picker) — scan picker biasa yang ditolak berarti barang belum diambil |
| 2 | `Inbound_picker::save_scan()` | `INBOUND` | model dilewati (`$picking['sumber_scan'] = 'INBOUND'`) karena `Picking_fcd::save()` berjalan di dalam transaksi luar milik controller; pencatatan dilakukan controller setelah rollback-nya sendiri. Barang sudah diambil → paket cancel dibuat |
| 3 | `Packer_fcd::periksa_kelayakan_packing()` — Scan Resi Packer (webcam & biasa) | `PACKER` | tanpa transaksi. Dipakai scan pertama webcam (`Packer::…` (4)) dan `save()` |
| 4 | `Packer_fcd::save_packer_nonsubmit()` — Scan Non-Submit | `PACKER` | tanpa transaksi |
| 5 | `Handover_fcd::save()` — Scan HO lama | `HO` | tanpa transaksi |
| 6 | `Scan_logistic_fcd::save_scan()` — Scan Paket NDD / NDD New | `HO` | pengecekan cancel sebelum `trans_begin()` |
| 7 | `Lost_scan_picker_fcd::tambah_picker()` — tim picker Tambahkan Picker | `LOST_SCAN` | setelah `batal()` (rollback). `ditemukan_di` = sumber laporan pending (PACKER/HO) — meja tempat paket ditahan; keterangan menyebut id pending |

Rekap ulang `sync_resi()` Inbound Picker **tidak** mencatat: ia melewati resi
cancel secara massal tanpa ada paket fisik di tangan operator (lihat commit
`5ddca3f`). Resi itu muncul sebagai "tanpa jejak" — memang harus dicari.

### 7.2 `Cancel_paket_fcd::catat_tolak()`

| Langkah | Keterangan |
|---|---|
| Lewati bila Mode Arsip aktif | koneksi DB sedang `READ ONLY` ke `iresis_arsip` |
| `alasan` | `BATAL_MANUAL` bila `tblprintresi.batal` terisi, kalau tidak `status_pesanan` (CANCELED / REQUEST_CANCEL) |
| Insert `tblcancel_paket_tolak` | selalu, satu baris per penolakan |
| Barang sudah diambil? | `$opsi['barang_sudah_diambil']` bila diberikan; kalau tidak: tahap ≠ PICKER → ya; PICKER → cek `tblresiambilbarang` |
| Bila ya: `INSERT … ON DUPLICATE KEY UPDATE` `tblcancel_paket` | baris baru = DITEMUKAN (`ditemukan_di` = `$opsi['ditemukan_di']` ?? tahap); baris lama = `jumlah_tolak + 1`, `tolak_terakhir_*` diperbarui, **status tidak disentuh** |
| Kegagalan apa pun | ditelan (`try/catch`, `db_debug` dimatikan sementara), ditulis ke `log_message('error')`. Scan **tidak pernah** gagal gara-gara pencatatan ini |

### 7.3 Laporan Resi Cancel

Kolom **Tahap saat Cancel** kini ditambah baris kedua dari `tblcancel_paket`
bila ada, mis. `Ditolak di HO 21/09 09:15 oleh GUNTUR (2×)`, dan penanda
otomatis:

| Penanda | Kondisi |
|---|---|
| `packer lupa scan` | ditolak di HO, tidak ada baris `tblpacking` |
| `picker lupa scan` | ditolak di PACKER/HO/INBOUND, tidak ada baris `tblresiambilbarang` |

Sel Picker/Packer/HO menampilkan jam scan; scan yang terjadi sesudah cancel
diketahui ditandai `(setelah cancel)`.

## 8. Data yang ditulis

| Tabel | Oleh | Isi |
|---|---|---|
| `tblcancel_paket_tolak` | `catat_tolak()` | `id_resi, noresi, tahap, alasan, id_user, nama_komputer, waktu, keterangan` |
| `tblcancel_paket` | `catat_tolak()` (tahap 1) | `status = DITEMUKAN`, `ditemukan_di/oleh/komputer/at`, `jumlah_tolak`, `tolak_terakhir_di/at` |
| `tblcancel_paket` | Cek Paket Cancel (tahap 2) | `status = DICEK`, `dicek_oleh`, `dicek_at` |
| `tblcancel_paket_item` (tahap 2) | Cek Paket Cancel | per SKU resi: `sku_resi, qty_resi, qty_ditemukan, sku_ditemukan, kondisi (BAIK/RUSAK), id_masalahpicker, qty_diterima_display` |
| `tblmasalahpicker` (tahap 2) | Cek Paket Cancel | tipe KURANG AMBIL / SALAH AMBIL / TIDAK AMBIL atas nama picker resi |
| `tblretur_display_batch` + `_detail` (tahap 3) | batch & terima | kolom baru nullable `id_cancel_item` di detail; `tblcancel_paket.id_batch`, `status` DIKIRIM → SELESAI, `selesai_at` |

Skema kolom tahap 1: `DATABASE_STRUCTURE.md` §23–24. Tabel dibuat
`MY_Controller::run_paket_cancel_migration()` (`BOOTSTRAP_VERSI` 2026-09-21.3).

## 9. Tahapan rilis

| Tahap | Isi | Status |
|---|---|---|
| 1 | Tabel `tblcancel_paket` + `tblcancel_paket_tolak`, pencatatan di 7 titik penolakan, baris kedua di kolom Tahap saat Cancel | **live 21 Sep 2026** |
| 2 | Menu tim retur **Cek Paket Cancel** (scan resi → isi qty/SKU ditemukan per baris → `tblcancel_paket_item` + `tblmasalahpicker`), tab **Paket Cancel** di laporan (DITEMUKAN belum DICEK, tanpa jejak) | rancangan |
| 3 | Batch ke display sumber CANCEL, terima batch di restock, laporan macet per penanggung jawab dengan batas waktu | rancangan |

## 10. Keputusan yang masih terbuka

1. **Batas waktu** tiap tahap (mis. DITEMUKAN → DICEK maks 4 jam, DICEK →
   batch maks 1 hari). Angka ini menentukan kapan nama muncul di laporan
   "macet".
2. **Paket cancel tanpa jejak** — langsung dibebankan ke picker, atau lewat
   tahap "konfirmasi picker" dulu?
3. `REQUEST_CANCEL` saat ini **tidak** ditolak di scan (hanya `CANCELED`),
   karena permintaan batal masih bisa ditolak penjual. Ikut alur ini atau
   tidak, diputuskan bersama tahap 2.

## 11. Keputusan desain (jangan diubah tanpa membaca ini)

1. **Scan yang ditolak tetap ditolak.** Tahap 1 hanya menambah jejak; tidak
   ada meja yang meloloskan resi cancel.
2. **Scan lama tidak dihapus.** Scan yang terjadi saat resi masih sah adalah
   dasar KPI (dan aturan proyek melarang DELETE). "Hanya satu scan" dicapai
   dengan penolakan + jejak, bukan penghapusan.
3. **Paket cancel hanya untuk barang yang sudah keluar display.** Penolakan di
   picker tanpa picking dicatat di log, tapi tidak melahirkan paket cancel.
4. **Satu baris `tblcancel_paket` per resi** (UNIQUE `id_resi`). Penolakan
   berulang menaikkan `jumlah_tolak`, tidak membuat baris baru dan tidak
   mengubah status.
5. **Pencatatan tidak boleh menggagalkan scan.** Semua kesalahan ditelan dan
   dicatat ke log aplikasi.
6. **Selisih isi = masalah picker biasa** (`tblmasalahpicker`), bukan tabel
   baru — supaya slip CS dan KPI picker tidak perlu diubah.
