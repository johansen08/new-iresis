# Pencairan Dana (TIM FINANCE)

Dibuat 22 Okt 2026. Menu `finance/pencairan-dana`, anak dari **TIM FINANCE**. Hanya membaca
data pesanan Completed lalu menandai mana yang dananya sudah cair; satu-satunya tulisan adalah
unggahan tanggal pencairan.

## Berkas

| Berkas | Peran |
|---|---|
| `application/controllers/Pencairan_dana.php` | Halaman, `get_data` (JSON), `export` (xlsx), `upload` (Excel) |
| `application/models/Pencairan_dana_fcd.php` | Query ringkasan/daftar, pencocokan nomor pesanan, isi tanggal pencairan |
| `application/views/pencairan_dana/index.php` | UI lengkap (ringkasan %, tab, pemilih tanggal, multi pilih marketplace, tabel, pagination, dialog upload) |
| `MY_Controller::run_tanggal_pencairan_mp_migration()` | Kolom `tblprintresi.tanggal_pencairan` di prod **dan** arsip |
| `MY_Controller::run_pencairan_dana_menu_migration()` | Menu + hak akses (role webmaster + role yang boleh membuka Denda) |

Route: `finance/pencairan-dana`, `finance/get-pencairan-dana`, `finance/export-pencairan-dana` (GET),
`finance/upload-pencairan-dana`.

## Definisi

- **Satu baris = satu pesanan.** Satu resi hanya memuat satu nomor pesanan (dicek 22 Okt 2026),
  nomornya dari `tbldetailprintresi.no_pesanan`.
- **Daftar** = `tblprintresi.status_pesanan = 'COMPLETED'` dengan `tanggal_selesai` terisi
  (= `completed_date` Jubelio) pada rentang yang dipilih.
- **Marketplace** dari awalan nomor pesanan: SP Shopee, TT TikTok, TP Tokopedia, LZ Lazada.
  Awalan lain (SO reseller) diabaikan.
- **ID Pesanan di layar** = bagian di antara tanda `-` pertama dan kedua
  (`TP-586720295584630101-109465` → `586720295584630101`; `SP-260202KAQK3F9A` → `260202KAQK3F9A`).
  Itu juga yang disalin tombol salin, dan yang dicocokkan saat upload.
- **Kolom tabel/ekspor** (23 Okt 2026): ID Pesanan, Tgl Shipped (`tanggal_pengiriman`, = `awb_created_date`),
  Tgl Delivered, Tgl Completed (`tanggal_selesai`), Marketplace, Toko (`tblprintresi.toko`), Tanggal Cair, Status.
  **Tgl Delivered belum punya kolom di database** sehingga selalu tampil "-" (tempatnya sudah disiapkan di
  `_baris()` dan ekspor); isi bila nanti ada kolom `tanggal_delivered` (ingat: kolom baru di `tblprintresi` juga ke arsip).
- **Status** (patokan sama untuk semua marketplace, dari tanggal completed):
  hijau = `tanggal_pencairan` terisi; kuning = belum cair, 0–7 hari; merah = belum cair, ≥ 8 hari
  (`Pencairan_dana_fcd::BATAS_KUNING`).
- Urutan: belum cair dulu (paling lama di atas), lalu sudah cair (terbaru dulu).
- Persentase dihitung dari semua pesanan Completed pada rentang tanggal + filter marketplace.

## Upload tanggal pencairan

Tombol **Upload laporan MP**. Excel/CSV: kolom A = nomor pesanan marketplace (format **Teks**),
kolom B = tanggal pencairan (`05-10-2026 08:10`, `05/10/2026`, `2026-10-05`, atau serial Excel).
Baris pertama boleh judul. Maksimal 20.000 baris.

Dua langkah: **Periksa file** (dry run, tidak menulis) lalu **Simpan**. Penyimpanan memakai
`UPDATE … WHERE tanggal_pencairan IS NULL` dalam satu transaksi: **tidak menimpa** tanggal yang
sudah ada dan tidak menghapus apa pun. Angka 18 digit yang tersimpan sebagai angka di Excel sudah
kehilangan presisi, jadi baris seperti itu ditolak dengan pesan.

Pencocokan: Shopee `SP-<nomor>`; lainnya `TT|TP|LZ-<nomor>-<sufiks>`.

## Hal yang perlu diketahui

- **Status Iresis tertinggal dari Jubelio.** Per 22 Okt 2026 pesanan TikTok (TT) terakhir
  berstatus COMPLETED tanggal 27 Mar 2026; yang sesudahnya masih SHIPPED. Selama sinkron status
  dari Jubelio belum dijadwalkan, TikTok praktis tidak muncul di menu ini.
- **Completed tanpa `tanggal_selesai`** (±12 %: Shopee, Lazada, Tokopedia) tidak bisa difilter
  rentang tanggal, jadi tidak ikut daftar.
- **Resi lama yang sudah dipindah ke arsip (> 60 hari)** tidak ada di prod, jadi upload
  melaporkannya "tidak ditemukan".
- **Skema arsip.** `Arsip_fcd::cek_skema` membandingkan kolom prod dan arsip; kolom yang hanya ada
  di prod membuat sinkron `tblprintresi` dilewati dan purna ditolak. Karena itu migrasi kolom
  `tanggal_pencairan` juga menambahkannya ke `iresis_arsip.tblprintresi`. Kolom berikutnya di
  `tblprintresi` harus diperlakukan sama.
- **Pencarian ID pesanan** (kotak di bar filter): cocok sebagian di mana saja pada nomor pesanan marketplace (bukan awalan TT-/TP-/sufiks), diketik langsung memperbarui tabel, tab, dan ringkasan (jeda 250 ms; Enter langsung mencari, Esc menghapus). Bagian yang cocok disorot. Hanya huruf/angka yang dipakai (`Pencairan_dana_fcd::syarat_cari`).
- **Selisih cair vs Completed**: di bawah badge "Sudah cair" tertulis berapa hari tanggal cair sebelum/sesudah tanggal Completed; di ekspor kolom "Cair vs Completed (hari)" (negatif = sebelum).
- **Indeks** `idx_printresi_status_selesai (status_pesanan, tanggal_selesai)` di `tblprintresi` (migrasi `run_indeks_pencairan_dana_migration`, dibuat online) menurunkan query dari ±1 dtk menjadi ±0,1 dtk. Indeks tidak dibandingkan `Arsip_fcd::cek_skema`, jadi tidak perlu ada di arsip.
- **Optimasi query/web (22 Okt 2026):** daftar dibaca langsung dari `tblprintresi` lewat indeks `idx_printresi_status_selesai` dengan nomor pesanan dari subquery skalar (tanpa GROUP BY/tabel sementara); tab Belum/Sudah diurutkan murni menurut `tanggal_selesai` agar bisa berhenti begitu halaman terisi. Ringkasan per marketplace hanya bergantung pada rentang tanggal + kotak cari, jadi view menyimpannya (`PD.ring`) dan hanya meminta ulang bila kuncinya berubah, lewat 60 detik, atau setelah upload (`ring=1`); pindah tab/halaman/ukuran/marketplace/centang hanya mengambil baris (`ring=0`) dan angka kartu/tab/halaman dihitung di browser. Permintaan yang masih berjalan dibatalkan saat ada ketikan/klik baru. Hasilnya diverifikasi identik dengan query lama pada 228 kombinasi (rentang x pencarian x marketplace x tab x centang).

## Nominal & tab selisih (26 Okt 2026)

Tab: **Semua**, **Belum Cair**, **Sudah Cair, Ada Selisih**, **Sudah Cair, Full** (tab "Sudah Cair" lama diganti dua tab ini).

Tiga kolom baru di `tblprintresi` (prod **dan** arsip, migrasi `run_nominal_pencairan_migration`, `BOOTSTRAP_VERSI` 2026-10-26.1):

| Kolom | Sumber |
|---|---|
| `nilai_pesanan` | Jubelio `escrow_list.revenue_amount` (cadangan `grand_total`) = "Harga final" di laporan TikTok |
| `dana_diterima_mp` | Jubelio `escrow_list.settlement_amount` = "Dana diterima" menurut Jubelio |
| `dana_diterima_file` | kolom "Dana diterima" di laporan MP yang diunggah finance |

- **Ada selisih** = sudah cair, kedua dana ada, `|dana_diterima_mp − dana_diterima_file| > Rp0,5`. **Full** = kedua dana ada dan sama. Sudah cair tetapi salah satu dana belum ada **tidak masuk kedua tab** (hanya di Semua, label "Nominal belum dicek"), jadi jumlah tab Selisih + Full bisa lebih kecil dari Sudah Cair.
- Selisih = Jubelio − laporan. Kolom ekspor: Dana Jubelio, Dana Laporan MP, Selisih, Cek Nominal.
- **Sinkron Jubelio**: `scripts/sinkron_nominal_mp.py [--hari N]` (default 30 hari tanggal transaksi). Memakai login/list/detail dari `auto_upload_resi_api.py`; menanyakan `cron/nominal_mp_butuh` pesanan COMPLETED yang dana MP-nya kosong, mengambil detailnya, lalu POST ke `cron/auto_upload_nominal_mp`. Hanya mengisi yang kosong; pesanan tanpa `escrow_list.settlement_amount` dilewati dan dicoba lagi. Dijalankan **di PC yang punya `jubelio_credentials.py`** (PC ini tidak punya). Backfill pesanan lama: jalankan dengan `--hari` besar (detail dipanggil satu per satu, jeda 0,5 dtk).
- **Upload laporan MP**: baris judul dikenali otomatis (`ID Pesanan`, `Tanggal pencairan`, `Dana diterima`; angka seperti `Rp7.577` terbaca). Tanpa judul tetap format lama A = nomor, B = tanggal, C = dana (opsional). Tanggal dan dana diisi terpisah dan tidak menimpa; pesanan yang tanggal cairnya sudah ada tetap bisa diisi dana-nya.
- Sampel Jubelio yang dipakai merancang ini: satu pesanan TikTok COD; `escrow_list` untuk Shopee/Tokopedia/Lazada belum diperiksa.
