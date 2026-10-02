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
| HO | TIM HO → **Scan Paket NDD New** (`scan-paket-ndd-new`) | `NOT_PACKED` | Pilih packer dari saran no absen → **Simpan**: tulis `tblpacking` (tanggal = saat klik) + catat PACKER **dan** resi langsung masuk HO/NDD (§12) | Antar paket ke packer / scan ulang |
| HO | sama | `NOT_PICKED` | Pilih picker **dan** packer dari saran → **Simpan**: tulis picking + packing + catat PICKER/PACKER, tutup antrean tim picker bila ada, resi langsung masuk HO/NDD (§12) | Lapor ke antrean tim picker |
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

**Sejak 2 Okt 2026 cabang HO di diagram ini tidak dipakai lagi**: HO mengisi
picker/packer sendiri dan resi langsung lolos (§12). Diagram tetap berlaku
untuk laporan dari meja packer (webcam) yang masuk antrean tim picker.

Urutan itu **tidak** bergantung pada status antrean. Penjaga yang sudah ada
yang memaksanya: `Packer_fcd::periksa_kelayakan_packing()` menolak resi
tanpa picking, `Scan_logistic_fcd::save_scan()` menolak resi tanpa picking
atau tanpa packing. Satu-satunya cara membuat baris picking di alur ini
adalah tombol Tambahkan Picker.

## 5. Kondisi & keputusan sistem

### 5.1 Scan Paket NDD New (HO) — panel lost scan

*(Diganti 2 Okt 2026 -- lihat §12. Tidak ada lagi mode info, tombol
Laporkan ke Tim Picker, maupun tombol Selesaikan Sekarang terpisah.)*

| Kondisi setelah scan | Kolom yang tampil | Catatan |
|---|---|---|
| `NOT_PACKED` | No Absen Packer | Simpan → packing diisi + masuk HO/NDD |
| `NOT_PICKED`, packing belum ada | No Absen Picker + No Absen Packer | Simpan → picking + packing diisi + masuk HO/NDD |
| `NOT_PICKED`, packing sudah ada | No Absen Picker | kolom packer disembunyikan setelah `cek-lost-scan` |
| Sudah pernah dicatat lost scan | kotak biru (informasi) | catatan lama dipakai ulang, Simpan tetap mengisi yang kosong |
| Resi sudah di antrean tim picker | kotak merah (informasi) | Simpan menutup laporan itu sebagai `SELESAI_LUAR` |
| Error lain (`NOT_FOUND`, batal, dobel, Shopee di mode NDD) | panel tidak muncul | — |

Perilaku panel: terikat ke satu resi; scan resi lain tetap diproses dan
panel tetap ada sampai disimpan/ditutup (Esc). `NOT_PACKED`/`NOT_PICKED`
untuk resi lain mengganti isi panel. Kolom pertama fokus otomatis; ketik
no absen atau nama → dropdown saran (no absen · nama · jabatan) → panah
atas/bawah + Enter memilih dan pindah ke kolom berikutnya → Enter di kolom
terakhir = Simpan.

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
   sumber kebenaran untuk nama picker: tim picker. *Per 2 Okt 2026 tidak
   berlaku untuk HO -- HO memilih picker sendiri dari saran Master Picker
   (§12). Masih berlaku untuk packer (webcam).*
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
| `scan-paket-ndd-new/simpan-lost-scan` | `Lost_scan_selesai_fcd::proses()` (§12) — isi picking/packing yang kosong, lalu `Scan_logistic_fcd::save_scan()` |
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

## 12. HO mengisi picker/packer lewat no absen, resi langsung lolos (per 1-2 Okt 2026)

Panel lost scan **Scan Paket NDD New** (HO) punya **satu** tombol, **Simpan
Lost Scan**, yang dalam satu klik:

1. mengisi baris yang masih kosong atas nama orang yang dipilih, tanggal =
   saat klik -- `tblresiambilbarang` (kalau `NOT_PICKED`) dan `tblpacking`;
2. langsung men-scan resi ke HO/NDD lewat `Scan_logistic_fcd::save_scan()`
   apa adanya.

HO tidak perlu mengantar paket ke picker/packer, tidak perlu scan ulang, dan
tidak lagi melapor ke antrean tim picker. Detail resi
(`Receipt_fcd::get_detail`) langsung menampilkan **Nama Packer**
(`tbluser.name` lewat `tblpacking.packer_pegawai`), **Tanggal Packing**,
**Nama Picker** (`tblpegawai` lewat `yangambil_pegawai`), dan **Tanggal
Picker**. (Sempat ada dua tombol -- Simpan yang hanya mengisi packing dan
"Selesaikan Sekarang" terpisah; digabung 2 Okt 2026 atas permintaan user
supaya HO tidak kerja dua kali.)

**Input dengan saran.** Kolom No Absen Packer (selalu) dan No Absen Picker
(hanya saat `NOT_PICKED`) menampilkan dropdown saran saat diketik: no absen ·
nama · jabatan, maks. 8. Ketik angka → cocok ke no absen (yang persis
dulu); ketik huruf → cocok ke nama/jabatan. Sumber saran = himpunan yang
sama dengan yang divalidasi server:
- packer: akun `tbluser` `hakakses = 4`, `isactive = 1`
  (`Scan_paket_ndd_new_fcd::roster_packer()` ↔ `Lost_scan_selesai_fcd::cari_packer()`);
- picker: Master Picker aktif -- `tblnamaambilbarang.status_aktif` dan
  `tblpegawai.status_aktif` `AKTIF`, daftar yang sama dengan dropdown
  Tambahkan Picker tim picker (`roster_picker()` ↔ `cari_picker()`).
Roster diurai sekali di server saat halaman dibuka dan disisipkan sebagai
JSON; dropdown dibuat sendiri (jQuery UI lokal tidak punya autocomplete).

**No absen** = angka di ujung nama menurut konvensi
penamaan "NAMA - JABATAN - NOABSEN" yang dipakai `tbluser.name` dan
`tblpegawai.nama_pegawai` (mis. "DEWI - QC - **0288**" → 288). **Bukan**
`kode_pegawai`/`id_user` -- DEWI ber-id 57. Tidak ada kolom absen terpisah;
angkanya dibaca dengan `CAST(TRIM(SUBSTRING_INDEX(nama, '-', -1)) AS
UNSIGNED)`, jadi "0288" dan "288" sama. Nama tanpa konvensi ini (9 dari 135
pegawai aktif per 2 Okt 2026, mis. trainer/webmaster) tidak bisa dipilih
lewat no absen. Beberapa no absen dipakai ganda di `tblpegawai` (0331,
0353, 0386 -- bukan packer/picker); kalau cocok ke lebih dari satu orang,
simpan ditolak dengan menyebut keduanya.

Resi yang sudah punya catatan `tbllostscanpacker` (mis. dari alur lama yang
hanya mencatat) tetap bisa disimpan: kotak biru menampilkan catatan lama,
catatannya dipakai ulang (tidak digandakan), dan baris yang kosong tetap
diisi. Kalau packing ternyata sudah ada, kolom packer disembunyikan.

Kalau langkah 2 gagal (mis. kalah balapan dengan scan lain), data langkah 1
tetap tersimpan; respons membawa `data_terisi = true`, panel ditutup, dan
petugas cukup men-scan ulang resinya.

Ini adalah pengecualian yang **disengaja** terhadap keputusan desain §6
poin 4 (menu lama di luar Scan Paket NDD New tidak disentuh), diminta secara
eksplisit lewat permintaan bisnis, bukan bug. Risiko integritas data ditahan
dengan:

- **Validasi roster aktif**, bukan percaya mentah nomor yang diketik:
  - No absen picker dicocokkan ke `tblpegawai.nama_pegawai` di Master
    Picker aktif (`cari_picker()`); yang ditulis ke `yangambil_pegawai`
    adalah `kode_pegawai` hasilnya.
  - No absen packer dicocokkan ke `tbluser.name` akun packer aktif
    (`hakakses = 4`, `isactive = 1`) lewat `cari_packer()`; yang ditulis ke
    `tblpacking.packer_pegawai` adalah `id_user` hasilnya. Ini sekaligus
    menyelesaikan masalah pemetaan `tblpegawai`→`tbluser` yang jadi alasan
    §6 poin 4 menolak opsi ini dulu: kalau nomor tidak cocok ke tepat satu
    akun packer aktif, ditolak (`PACKER_TIDAK_VALID`), tidak ada baris
    packing dengan FK kosong/salah.
- **Tidak ada pencatatan KPI** untuk baris picking/packing yang dibuat lewat
  jalur ini -- sama seperti precedent `tambah_picker()` (§6 poin 3): tidak
  adil mengkreditkan KPI untuk kerja yang tidak benar-benar dilakukan ulang.
- Baris yang dibuat ditandai `keterangan`/`nama_komputer` = `LOST SCAN HO
  LANGSUNG`, berbeda dari baris normal maupun dari `LOST SCAN PICKER`
  (`tambah_picker()`), supaya laporan bisa membedakan "benar discan ulang"
  vs "diselesaikan administratif oleh HO".
- Kalau resi ini sudah di antrean tim picker (`tbllostscanpicker_pending`
  status `PENDING`, dilaporkan packer webcam), baris itu otomatis ditutup
  `SELESAI_LUAR` saat HO menyimpan -- tidak menggantung.
- `tbllostscanpacker` tetap dicatat (dup-dicek per `(noresi, lost_type)`)
  supaya Laporan Lost Scan lama tetap konsisten.
- Scan HO/NDD tetap lewat `Scan_logistic_fcd::save_scan()` apa adanya
  (bukan logika duplikat), dikreditkan ke petugas HO yang memproses.

| Endpoint (POST) | Field | Fungsi |
|---|---|---|
| `scan-paket-ndd-new/simpan-lost-scan` | `noresi`, `no_absen_picker`, `no_absen_packer`, `is_ndd` | `Lost_scan_selesai_fcd::proses()` lalu `Scan_logistic_fcd::save_scan()` |

Endpoint `lapor-picker` dan `selesaikan-lost-scan` dihapus 2 Okt 2026.

File terkait: `application/models/Lost_scan_selesai_fcd.php` (`proses()`,
`kunci_resi()`, `cari_packer()`, `cari_picker()`),
`Scan_paket_ndd_new_fcd` (`roster_packer()`, `roster_picker()`,
`sudah_packing()`), `Scan_paket_ndd_new::simpan_lost_scan()`,
`application/views/scan_paket_ndd_new/index.php` (`pasangSaran()`). Tidak
ada migrasi/perubahan skema -- no absen dibaca dari nama yang sudah ada.
`Lost_scan_packer_fcd::save()` (model lama) tidak lagi dipanggil dari menu
ini -- tetap dipakai menu Lost Scan Packer/Picker/HO yang lama, tidak diubah.
