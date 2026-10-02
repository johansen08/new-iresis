# Alur Lost Scan (Packer & Picker)

Rilis 18 September 2026 (`cb1a1f3..9a904ee`); tampilan menu Laporan Lost
Scan Picker dirapikan 19 September 2026 (`ab4f78e`, lihat §5.5). Dokumen ini
satu-satunya rujukan alur lost scan; `ANALISIS_PROGRAM.md` dan
`WORKFLOW_DIAGRAM.md` hanya menunjuk ke sini.

## 1. Masalah yang diselesaikan

Paket yang sampai di meja HO (atau meja packer) tapi **tidak pernah di-scan**
oleh picker/packer sebelumnya. Sistem menolaknya (`NOT_PICKED` /
`NOT_PACKED`), dan sebelum rilis ini penanganannya manual: petugas pindah ke
menu Lost Scan, mengetik ulang resi, lapor lisan ke CS, lalu CS memakai
SCAN COMBINED yang "menyulap" picking **dan** packing sekaligus (tanpa video,
tanpa scan packer sungguhan).

Sekarang: pencatatan dilakukan **di meja tempat paket ditemukan**, resi
belum-picker masuk **antrean tim picker**, dan urutan pemulihan dipaksa oleh
penjaga yang sudah ada — tim picker → packer scan ulang → HO scan ulang.

## 2. Istilah

| Istilah | Arti |
|---|---|
| **Lost scan packer** | Paket sudah dipacking secara fisik tapi packer tidak scan → `tblpacking` kosong → HO ditolak `NOT_PACKED`. |
| **Lost scan picker** | Paket sudah diambil dari rak tapi picker tidak scan → `tblresiambilbarang` kosong → packer & HO ditolak `NOT_PICKED`. Kalau ketahuan di HO, berarti packer **juga** lost scan (ia menembus penjaga `NOT_PICKED` di menunya). |
| **Antrean tim picker** | `tbllostscanpicker_pending` — daftar resi belum-picker yang menunggu tim picker menentukan siapa picker-nya. |
| **Tambahkan Picker** | Aksi tim picker yang membuat baris picking atas nama picker terpilih, sehingga packer bisa scan ulang. |

## 3. Siapa melakukan apa

| Meja | Menu | Saat ditolak | Yang dilakukan | Yang **tidak** dilakukan |
|---|---|---|---|---|
| HO | TIM HO → **Scan Paket NDD New** (`scan-paket-ndd-new`) | `NOT_PACKED` | Isi **No Absen Packer** di panel bawah kartu status → langsung tulis baris `tblpacking` nyata (tanggal_packing = saat klik) + catat lost scan PACKER | Meloloskan resi ke HO (masih perlu discan lagi) |
| HO | sama | `NOT_PICKED` | Isi No Absen Packer → tulis `tblpacking` + catat PACKER **dan** otomatis lapor ke antrean tim picker (sumber HO) | Memilih/mengisi picker (itu domain tim picker) |
| Packer | TIM PACKER → **Scan Resi Packer (Webcam)** | `NOT_PICKED` | Tombol **Lapor Lost Scan Picker** di popup → masuk antrean (sumber PACKER); tahan paket | Memilih picker; merekam video |
| Tim picker | TIM PICKER → **Laporan Lost Scan Picker** (`lost-scan-picker`) | — | Tab **Menunggu Picker** → **Tambahkan Picker** → pilih picker dari Master Picker (kerjakan yang paling lama menunggu dulu) | Membuat packing |

Menu lama (Scan Paket NDD, Lost Scan Packer/Picker/HO, Laporan Lost Scan)
tetap ada dan tidak berubah — cadangan kalau menu baru bermasalah.

## 4. Alur

```
                 paket sampai di meja HO / packer, scan ditolak
                                   │
              ┌────────────────────┴─────────────────────┐
              ▼                                          ▼
       NOT_PACKED (HO)                            NOT_PICKED (HO / packer)
   packer lupa scan saja                     picker lupa scan (+ packer, bila di HO)
              │                                          │
   HO: panel pilih packer                    HO: panel pilih packer → PACKER dicatat
   → tbllostscanpacker PACKER                    + lapor() sumber HO
              │                              Packer: tombol Lapor → lapor() sumber PACKER
              │                                          │
              │                                          ▼
              │                          tbllostscanpicker_pending  status PENDING
              │                                          │
              │                            TIM PICKER → Laporan Lost Scan Picker
              │                              Tambahkan Picker (pilih picker)
              │                                          │
              │                       insert tblresiambilbarang atas nama picker
              │                       (nama_komputer 'LOST SCAN PICKER', tanpa KPI)
              │                       + tbllostscanpacker PICKER  → status SELESAI
              │                       + notifikasi "silakan scan ulang di packer"
              ▼                                          ▼
   paket kembali ke packer ─────────────────►  packer scan ulang → tblpacking (video, KPI asli)
                                                         │
                                                         ▼
                                              HO scan ulang → tblresikeluar / tblscan_ndd
```

Urutan itu **tidak** bergantung pada status antrean. Penjaga yang sudah ada
yang memaksanya: `Packer_fcd::periksa_kelayakan_packing()` menolak resi
tanpa picking, `Scan_logistic_fcd::save_scan()` menolak resi tanpa picking
atau tanpa packing. Satu-satunya cara membuat baris picking di alur ini
adalah tombol Tambahkan Picker.

## 5. Kondisi & keputusan sistem

### 5.1 Scan Paket NDD New (HO) — panel lost scan

| Kondisi setelah scan | Panel | Isi |
|---|---|---|
| `NOT_PACKED`, belum pernah dicatat | mode **simpan** | input **No Absen Packer** (`tblpegawai.kode_pegawai`) + Simpan — resolve ke akun packer aktif, tulis `tblpacking` nyata |
| `NOT_PICKED`, belum pernah dicatat | mode **simpan**, teks merah | sama; saat simpan mengirim `belum_picker=1` → `lapor()`; plus blok "Selesaikan Sekarang" (§12) kalau mau langsung lolos tanpa antar fisik sama sekali |
| Resi sudah punya catatan lost scan (tipe apa pun) | mode **info** | "Sudah dicatat lost scan X → NAMA, oleh PELAPOR pada …" |
| Resi sudah di antrean tim picker | kotak merah (kedua mode) | "Sudah dilaporkan ke tim picker oleh … (HO/PACKER) pada … — menunggu" |
| `NOT_PICKED` + sudah tercatat PACKER + **belum** di antrean | mode info + tombol **Laporkan ke Tim Picker** | `lapor()` sumber HO tanpa mencatat packer lagi |
| Error lain (`NOT_FOUND`, batal, dobel, Shopee di mode NDD) | tidak muncul | — |

Perilaku panel: terikat ke satu resi; scan resi lain tetap diproses dan
panel tetap ada sampai disimpan/ditutup (Esc). `NOT_PACKED`/`NOT_PICKED`
untuk resi lain mengganti isi panel. Input no absen packer fokus otomatis;
Enter di situ langsung memicu Simpan.

### 5.2 Scan Resi Packer Webcam — popup "Jangan Dipacking"

| Kondisi | Popup |
|---|---|
| `NOT_PICKED`, belum di antrean | pesan + tombol merah **Lapor Lost Scan Picker** |
| `NOT_PICKED`, sudah di antrean | pesan "SUDAH dilaporkan oleh … (sumber) pada … tahan paketnya", tanpa tombol |
| Setelah tombol ditekan | pesan berubah, tombol hilang, siklus scan resi itu ditutup di server, rekaman (bila ada) dibuang |

Penjaga kelayakan berjalan **sebelum** siklus scan dibuka, jadi untuk resi
belum-picker kamera tidak pernah mulai merekam dan tidak ada video yang
tersimpan.

### 5.3 `Lost_scan_picker_fcd::lapor($noresi, $sumber, $user)`

| Hasil | Kapan |
|---|---|
| `DIBUAT` | resi ada, belum picker, belum ada PENDING → baris PENDING baru |
| `SUDAH_PENDING` | sudah ada PENDING → dikembalikan baris itu (pelapor & waktu) |
| `SUDAH_PICKED` | resi sudah punya `tblresiambilbarang` → tidak perlu lapor |
| `TIDAK_DITEMUKAN` | noresi kosong / tidak ada di `tblprintresi` |

Satu resi hanya punya **satu** baris PENDING, dari sumber mana pun.

### 5.4 `Lost_scan_picker_fcd::tambah_picker($id_pending, $kode_picker, $user)`

Satu transaksi, `db_debug` dimatikan sementara, `SELECT … FOR UPDATE`:

| Langkah | Gagal → kode |
|---|---|
| Baris pending ada dan masih PENDING | `TIDAK_DITEMUKAN` / `SUDAH_DIPROSES` (menyebut siapa yang memproses) |
| Resi ada, tidak `CANCELED`, `batal <> 1` | `ORDER_CANCELED` (pending dibiarkan PENDING, keputusan manual) |
| Picking **sudah ada** (dibuat di luar alur, mis. SCAN COMBINED) | → tutup sebagai `SELESAI_LUAR`, **tidak** menulis picking/lost scan, selesai |
| Picker dipilih dan `status_aktif = 'AKTIF'` | `PICKER_KOSONG` / `PICKER_TIDAK_VALID` |
| Insert `tblresiambilbarang` | `SAVE_FAILED` |
| Insert `tbllostscanpacker` PICKER (kalau belum ada `(noresi, PICKER)`) | — |
| Update pending → `SELESAI` | `SAVE_FAILED` |
| Setelah commit: `Notification::send()` kategori `GENERAL` | dicatat ke log, tidak membatalkan |

Tombol **Tandai Selesai** muncul di tab Menunggu Picker untuk baris yang resinya
sudah punya picking; setelah konfirmasi (modal), memanggil endpoint yang sama
tanpa `kode_picker`.

### 5.5 Laporan Lost Scan Picker — tampilan (rev. 19 Sep 2026, `ab4f78e`)

Halaman dirancang untuk **dibiarkan terbuka** di meja tim picker: antrean
menyegarkan diri, yang lama menonjol, satu klik untuk memproses.

| Bagian | Isi |
|---|---|
| Strip alur | 4 langkah (Packer/HO lapor → **Tim picker tentukan picker** → Packer scan ulang → HO scan ulang); langkah 2 disorot sebagai posisi menu ini |
| Kartu ringkasan | *Menunggu picker*, *Dilaporkan packer*, *Dilaporkan HO*, *Paling lama menunggu* (+ "N resi > 30 mnt"). Warna kartu terakhir: hijau < 30 mnt, kuning ≥ 30 mnt, merah ≥ 2 jam. Diperbarui setiap tabel dimuat, dari `ringkasan_pending()` |
| Tab **Menunggu Picker** [badge] | Semua baris PENDING tanpa filter tanggal. Kolom: No · Waktu Lapor (+ badge usia `12 mnt` / `1 jam 5 mnt` / `2 hari`) · No Resi · Dilaporkan oleh (badge HO/PACKER + nama) · Item (**rak** di depan, lalu SKU × qty, nama) · Aksi |
| Warna baris | ≥ 30 menit latar kuning (`lsp-lama`), ≥ 2 jam merah (`lsp-sangat-lama`). Ambangnya dihitung di server (`data-menit` pada badge usia) dan dibaca `createdRow` |
| Segarkan | Otomatis tiap 30 detik (checkbox, default nyala) + tombol ↻ di heading panel + jam "diperbarui HH:mm:ss". Otomatis **dijeda** saat ada modal terbuka, tab Menunggu Picker tidak aktif, atau tab browser disembunyikan; timer berhenti sendiri begitu `#lsp-root` lepas dari DOM (pindah menu SPA) |
| Tab **Sudah Diproses** | SELESAI / SELESAI_LUAR dalam rentang `waktu_proses` (daterangepicker, default hari ini). Kolom tambahan: Picker · Diproses oleh (+ waktu) · Status |
| Modal Tambahkan Picker | No resi besar, konteks laporan (pelapor + sumber, waktu lapor, lama menunggu, jumlah item — dari atribut `data-*` tombol, tanpa request tambahan), daftar item, dropdown picker ber-live-search (Master Picker aktif, ~65 nama) yang terbuka otomatis; Enter setelah memilih = simpan |
| Modal Tandai Selesai | Konfirmasi Bootstrap (bukan `confirm()` browser) yang menjelaskan laporan ditutup tanpa memilih picker; fokus langsung ke tombol Ya |
| Antrean kosong | Ikon centang hijau + "Antrean kosong -- tidak ada resi yang menunggu picker" |

Kalau laporan ternyata sudah diproses orang lain (`SUDAH_DIPROSES`), notifikasi
merah tampil, tabel disegarkan, dan modal ditutup. Teks DataTables (cari,
paginasi, info) berbahasa Indonesia; semua CSS diawali `#lsp-root` supaya
tidak bocor ke halaman lain di SPA.

## 6. Keputusan desain (jangan diubah tanpa membaca ini)

1. **Pelapor tidak memilih picker.** Packer/HO hanya melaporkan resi. Satu
   sumber kebenaran untuk nama picker: tim picker.
2. **Baris `tbllostscanpacker` PICKER dibuat saat diproses**, bukan saat
   lapor — tidak pernah ada catatan lost scan tanpa nama.
3. **Picking susulan tidak dihitung KPI picker.** Baris dibuat demi
   integritas data (agar packer bisa scan ulang); hukumannya sudah tercatat
   sebagai lost scan. Penanda `nama_komputer = 'LOST SCAN PICKER'`.
4. **HO tidak pernah meloloskan resi** yang belum packing/belum picker, dan
   tidak membuat baris packing atas nama packer (opsi ini ditolak karena
   mengotori KPI/waktu packing dan butuh pemetaan `tblpegawai`→`tbluser`).
   **Per 1 Okt 2026 ada pengecualian yang disengaja**: menu "Selesaikan
   Sekarang (Tanpa Scan Ulang)" (§12) sekarang membolehkan HO meloloskan
   resi lewat no absen, atas permintaan bisnis eksplisit (bukan bug) --
   risikonya ditahan dengan validasi roster aktif dan **tanpa pencatatan
   KPI sama sekali** untuk baris yang dibuat lewat jalur itu.
5. **Unik per `(noresi, lost_type)`** di jalur baru. `Lost_scan_packer_fcd::save()`
   lama menolak duplikat per `noresi` saja, jadi tidak dipakai untuk PICKER.
6. `tbllostscanpacker.nama_packer` tetap diisi `tblpegawai.nama_pegawai`
   apa pun tipenya — Laporan Lost Scan lama membacanya tanpa perubahan.
7. Menu baru **berdampingan** dengan menu lama; tidak ada file menu lama yang
   diubah kecuali `Packer.php`/`scan_packer_webcam.php` (tombol lapor).

## 7. Data yang ditulis

| Tabel | Oleh | Isi |
|---|---|---|
| `tbllostscanpacker` | Scan Paket NDD New | `lost_type = PACKER`, `nama_packer`, `created_by` = petugas HO |
| `tbllostscanpacker` | `tambah_picker()` | `lost_type = PICKER`, `nama_packer` = nama picker, `created_by` = tim picker |
| `tbllostscanpicker_pending` | `lapor()` | `sumber` HO/PACKER, `dilaporkan_oleh`, `waktu_lapor`, `status = PENDING` |
| `tbllostscanpicker_pending` | `tambah_picker()` | `status` SELESAI/SELESAI_LUAR, `kode_picker`, `diproses_oleh`, `waktu_proses`, `id_resiambilbarang`, `id_lostscanpacker` |
| `tblresiambilbarang` | `tambah_picker()` | `yangambil_pegawai` = `kode_pegawai` picker, `admin_pegawai` = tim picker, `nama_komputer = 'LOST SCAN PICKER'`, `pending = ''`, `status_performa_id` = status picker hari itu → `NORMAL_PICKER` |
| `notifications` | `tambah_picker()` | kategori `GENERAL`: "Resi X sudah ditambahkan picker (NAMA). Silakan scan ulang di packer." |

Skema kolom: `DATABASE_STRUCTURE.md` §21–22.

## 8. Skenario ringkas

| # | Situasi | Jalur |
|---|---|---|
| A | HO scan, "belum di-packing" | panel → pilih packer → PACKER dicatat → paket ke packer → packer scan biasa → HO scan ulang |
| B | HO scan, "belum di-picker" | panel → pilih packer → PACKER dicatat + antrean (HO) → paket ditahan → tim picker Tambahkan Picker → packer scan ulang → HO scan ulang |
| C | Packer webcam scan, "belum di-picker" | popup → Lapor → antrean (PACKER) → paket ditahan (tanpa rekaman) → tim picker → packer scan ulang → HO |
| D | Resi sudah di antrean, discan lagi di HO/packer | HO: kotak merah "menunggu tim picker"; packer: popup tanpa tombol. Tidak ada laporan ganda |
| E | Admin memakai SCAN COMBINED untuk resi yang ada di antrean | tab Menunggu Picker menandai "sudah di-picker di luar alur" → Tandai Selesai (konfirmasi modal) → `SELESAI_LUAR`, tanpa baris PICKER baru |
| F | Dua orang memproses baris yang sama | `FOR UPDATE`: yang kedua dapat "sudah diproses oleh X", tabel disegarkan |
| G | Pesanan batal saat diproses | ditolak `ORDER_CANCELED`, baris tetap PENDING untuk keputusan manual |

## 9. Menu, hak akses, endpoint

| Menu | URI | Induk | Role | Migrasi |
|---|---|---|---|---|
| Scan Paket NDD New | `scan-paket-ndd-new` | TIM HO (27) | 1 webmaster, 2 admin, 5 ho | `run_scan_paket_ndd_new_migration()` |
| Laporan Lost Scan Picker | `lost-scan-picker` | TIM PICKER (19) | 1, 2, 6 tim retur | `run_lost_scan_picker_migration()` (+ tabel) |

Role dikunci ganda: di migrasi (`roleaccess`) dan di `ROLE_BOLEH` controller
(`Lost_scan_picker`), karena URL bisa dibuka langsung. Tombol di webcam
mengikuti `Packer::ROLE_BOLEH_WEBCAM` (1, 4).

| Endpoint (POST) | Fungsi |
|---|---|
| `scan-paket-ndd-new/save` | simpan scan (delegasi `Scan_logistic_fcd::save_scan()`, respons identik menu lama) |
| `scan-paket-ndd-new/cek-lost-scan` | catatan lost scan + status antrean untuk satu resi |
| `scan-paket-ndd-new/simpan-lost-scan` | `Lost_scan_selesai_fcd::simpan_packer()` — tulis `tblpacking` nyata + catat PACKER; `belum_picker=1` → juga `lapor()` sumber HO |
| `scan-paket-ndd-new/lapor-picker` | `lapor()` sumber HO tanpa mencatat packer |
| `scan-paket-ndd-new/selesaikan-lost-scan` | `Lost_scan_selesai_fcd::proses()` (§12) — pastikan picking+packing ada, lalu `Scan_logistic_fcd::save_scan()` |
| `packer/lapor-lost-scan-picker` | `lapor()` sumber PACKER + tutup siklus scan resi itu |
| `lost-scan-picker/get-data` | DataTables tab `pending` / `selesai` (+ `ringkasan`: `total`, `dari_packer`, `dari_ho`, `tertua`, `tertua_menit`, `lebih_30_menit` — dari `ringkasan_pending()`) |
| `lost-scan-picker/tambah-picker` | `tambah_picker()`; tanpa `kode_picker` = Tandai Selesai |

## 10. File terkait

| Lapisan | File |
|---|---|
| Controller | `Scan_paket_ndd_new.php`, `Lost_scan_picker.php`, `Packer.php` (`feedback_resi_tidak_layak()`, `lapor_lost_scan_picker()`) |
| Model | `Scan_paket_ndd_new_fcd.php` (baca), `Lost_scan_picker_fcd.php` (antrean + proses), `Lost_scan_selesai_fcd.php` (tulis packing/picking langsung, §12); dipakai apa adanya: `Scan_logistic_fcd` |
| View | `scan_paket_ndd_new/index.php`, `lost_scan_picker/index.php`, `packer/scan_packer_webcam.php` (popup) |
| Migrasi | `MY_Controller.php` — `BOOTSTRAP_VERSI` `2026-09-18.3` |
| Spesifikasi | `docs/superpowers/specs/2026-09-18-scan-paket-ndd-new-design.md`, `docs/superpowers/specs/2026-09-18-lost-scan-picker-tahap-a-design.md` |
| Rilis produksi | `docs/PANDUAN_PULL_PRODUKSI.md` Bagian C |

## 11. Menguji di mesin lokal

Tidak ada test suite. Buat resi dummy (`INSERT` saja) dengan awalan jelas,
**wajib punya baris `tbldetailprintresi`** — Scan Resi Packer Webcam menolak
resi tanpa item sebagai "tidak ditemukan" (penjaga anti barcode non-resi).
Kombinasi minimum: resi belum picker (skenario B/C), resi picker-belum-packing
(A), resi lengkap (kontrol), resi kurir Shopee (ditolak di mode NDD). Script
contoh ada di `dev_tools/` (gitignored).

## 12. No Absen Packer/Picker & Selesaikan Langsung Tanpa Scan Ulang (per 1-2 Okt 2026)

Dua perubahan berdampingan di panel lost scan **Scan Paket NDD New** (HO),
keduanya memakai **no absen** = `tblpegawai.kode_pegawai` (satu-satunya ID
di `tblpegawai`, tidak ada kolom absen terpisah):

1. **Tombol "Simpan Lost Scan" (mode utama, dropdown lama diganti input)**
   -- dipakai saat resi `NOT_PACKED` atau `NOT_PICKED`. Dulu cuma mencatat
   nama packer dari dropdown ke `tbllostscanpacker` (resi tetap tidak lolos
   apa pun yang terjadi). **Sekarang** HO mengisi **No Absen Packer**, dan
   saat diklik langsung menulis baris `tblpacking` **nyata** atas nama
   packer itu, `tanggal_packing` = tanggal & jam saat tombol diklik --
   `Lost_scan_selesai_fcd::simpan_packer()`. Efeknya: scan HO berikutnya
   untuk resi ini otomatis lolos penjaga `is_packed` tanpa packer perlu
   packing ulang secara fisik. Picker tidak disentuh oleh tombol ini -- kalau
   resi juga `NOT_PICKED`, alur lama tetap jalan (`belum_picker=1` →
   `lapor()` ke antrean tim picker, sama seperti sebelumnya).
2. **Tombol "Selesaikan Sekarang (Tanpa Scan Ulang)"** -- blok terpisah di
   bawahnya, dengan input tambahan **No Absen Picker** (muncul hanya kalau
   resi `NOT_PICKED`) dan checkbox konfirmasi fisik. Memastikan baris
   picking **dan** packing ada (insert kalau belum, lewat
   `Lost_scan_selesai_fcd::proses()`), lalu langsung memanggil
   `Scan_logistic_fcd::save_scan()` -- resi **lolos ke HO/NDD saat itu
   juga**, tanpa paket diantar fisik sama sekali dan tanpa melalui antrean
   tim picker.

Keduanya adalah pengecualian yang **disengaja** terhadap keputusan desain §6
poin 4 (menu lama di luar Scan Paket NDD New tidak disentuh), diminta secara
eksplisit lewat permintaan bisnis, bukan bug. Risiko integritas data ditahan
dengan:

- **Validasi roster aktif**, bukan percaya mentah nomor yang diketik:
  - No absen picker divalidasi ke `tblpegawai` (`status_aktif = 'AKTIF'`) --
    sama dengan validasi `Lost_scan_picker_fcd::tambah_picker()`.
  - No absen packer divalidasi ke akun packer aktif (`tbluser.hakakses = 4`,
    `isactive = 1`, terhubung ke `tblpegawai`) lewat
    `Lost_scan_selesai_fcd::resolve_packer()`. Ini sekaligus menyelesaikan
    masalah pemetaan `tblpegawai`→`tbluser` yang jadi alasan §6 poin 4
    menolak opsi ini dulu: kalau nomor tidak match akun packer aktif,
    ditolak (`PACKER_TIDAK_VALID`), tidak ada baris packing dengan FK
    kosong/salah.
- **Tidak ada pencatatan KPI** untuk baris picking/packing yang dibuat lewat
  jalur ini -- sama seperti precedent `tambah_picker()` (§6 poin 3): tidak
  adil mengkreditkan KPI untuk kerja yang tidak benar-benar dilakukan ulang.
- Baris yang dibuat ditandai `keterangan`/`nama_komputer` = `LOST SCAN HO
  LANGSUNG`, berbeda dari baris normal maupun dari `LOST SCAN PICKER`
  (`tambah_picker()`), supaya laporan bisa membedakan "benar discan ulang"
  vs "diselesaikan administratif oleh HO".
- Kalau resi ini sudah di antrean tim picker (`tbllostscanpicker_pending`
  status `PENDING`) dan picker-nya dibuat lewat jalur "Selesaikan Sekarang",
  baris itu otomatis ditutup `SELESAI_LUAR` -- tidak menggantung.
- `tbllostscanpacker` tetap dicatat (dup-dicek per `(noresi, lost_type)`)
  supaya Laporan Lost Scan lama tetap konsisten.

Setelah baris picking/packing sintetis dibuat (kalau belum ada), endpoint
memanggil `Scan_logistic_fcd::save_scan()` **apa adanya** (bukan logika
duplikat) untuk benar-benar memasukkan `tblresikeluar`/`tblscan_ndd` --
sama seperti scan HO/NDD normal, dikreditkan ke petugas HO yang memproses.

| Endpoint (POST) | Fungsi |
|---|---|
| `scan-paket-ndd-new/simpan-lost-scan` | `Lost_scan_selesai_fcd::simpan_packer()` -- tulis `tblpacking` nyata + catat PACKER; `belum_picker=1` → juga `lapor()` sumber HO |
| `scan-paket-ndd-new/selesaikan-lost-scan` | `Lost_scan_selesai_fcd::proses()` (insert picking/packing sintetis bila perlu, tutup antrean picker bila ada) lalu `Scan_logistic_fcd::save_scan()` |

File terkait: `application/models/Lost_scan_selesai_fcd.php` (baru,
`simpan_packer()` + `proses()` + `resolve_packer()` bersama),
`application/controllers/Scan_paket_ndd_new.php::simpan_lost_scan()` +
`::selesaikan_lost_scan()`, `application/views/scan_paket_ndd_new/index.php`
(`#ls_packer_no_absen` dipakai kedua tombol, `#ls_selesai_block` khusus
tombol kedua). Tidak ada migrasi/perubahan skema -- "no absen" memakai
kolom `tblpegawai.kode_pegawai` yang sudah ada. `Lost_scan_packer_fcd::save()`
(model lama) tidak lagi dipanggil dari menu ini -- tetap dipakai menu
Lost Scan Packer/Picker/HO yang lama, tidak diubah.
