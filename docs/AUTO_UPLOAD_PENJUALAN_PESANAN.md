# Auto Upload Daftar Penjualan (Pesanan) 3 Bulan — update status & tanggal/waktu retur

Task Scheduler **"IRESIS - Upload penjualan pesanan 3 bulan"** jalan **tiap hari 22.00**: unduh laporan
*Daftar Penjualan (Pesanan)* Jubelio untuk **90 hari terakhir**, lalu unggah ke IRESIS lewat `cron/auto_upload_resi`
(logika sama dengan menu **Upload Resi**: `Receipt_fcd::insert_receipt()`).

Efeknya pada `tblprintresi`:

* `status_pesanan` resi yang statusnya berubah diperbarui (SHIPPED → COMPLETED / RETURNED / CANCELED, dst.) beserta
  tanggal pesan/batas kirim/pengiriman/selesai, kurir, marketplace, toko, `status_wms`.
* `tanggal_retur` diisi dari kolom **TGL_RETUR + WAKTU_RETUR** (tanggal & jam retur). Bila Excel membawa nilai baru yang
  beda dengan DB, nilainya ditimpa; baris tanpa tanggal retur **tidak** mengosongkan yang sudah ada.
* Resi yang sudah `COMPLETED` di IRESIS tidak diubah (aturan lama `insert_receipt`), kecuali `status_label_mp` (di bawah);
  resi baru di rentang itu di-insert.
* `status_wms` = kolom berjudul **Status WMS** (kolom AA). Sampai 26 Nov 2026 yang tersimpan adalah kolom W (nama barang);
  data lama itu tidak diisi ulang dan baru terganti bila `status_pesanan` resinya berubah.
* `status_label_mp` = kolom **Status** Excel (label asli marketplace; dicari lewat judul kolom, di ekspor sekarang kolom X).
  Diisi untuk resi baru dan resi yang `status_pesanan`-nya berubah. Resi lama yang statusnya tetap hanya disentuh bila
  labelnya **Paket Hilang**, **Ready To Ship** pada pesanan PROCESSING (dipakai Cek Paket RTS), atau label tersimpannya berubah, jadi `NULL` pada resi lama bukan berarti tanpa label.
  File / sumber tanpa kolom itu (mis. core-api) tidak mengubah `status_label_mp`.
* **Hitungan penjualan:** `status_pesanan = 'COMPLETED' AND COALESCE(status_label_mp,'') <> 'Paket Hilang'`, per bulan
  `tanggal_pesan`, jumlahkan `tbldetailprintresi.jumlah`. Resi di luar jendela unggah 90 hari tidak terisi otomatis.

## Alur (jalur laporan hasil sniff 21 Nov 2026, tanpa browser)

| # | Request | Hasil |
|---|---------|-------|
| 1 | `POST open.jubelio.com/core-api/login` | token JWT |
| 2 | `GET open.jubelio.com/core-api/reports/sales-list/date-range/?date_from=…&date_to=…&reference=order&hpp=true&tz=Asia/Bangkok` | `{"status":"ok","url":"https://report-prod.jubelio.com/?&token=T"}` |
| 3 | `GET https://report-prod.jubelio.com/xlsx/?&token=T` | xlsx kolom A..W (NO_PESANAN … STATUS_PESANAN, TGL_RETUR, WAKTU_RETUR) |
| 4 | `POST <IRESIS>/cron/auto_upload_resi?token=…` (field `receiptFile`) | JSON `{success, message}` |

Uji 24 Nov 2026: 7 hari ≈ 7,8–10 MB, unduh 27–49 detik; header persis sama dengan yang dibaca `insert_receipt`.

## Optimasi

* **Dipecah per 7 hari, terbaru dulu.** 90 hari ≈ 1 juta baris: tidak muat satu xlsx (batas Excel 1.048.576 baris) dan akan
  membuat PHP/Jubelio timeout. Terbaru dulu supaya kalau run terpotong, yang paling berguna sudah masuk. Ubah dengan `--potong-hari`.
* **Paralel.** Hingga `--unduh-paralel` (bawaan **3**) potongan diunduh bersamaan *sementara* potongan sekarang diunggah/diolah PHP.
  Upload ke PHP sengaja **berurutan** (semuanya menulis `tblprintresi` dalam satu transaksi besar; paralel berisiko lock/deadlock dan
  untungnya kecil karena PHP hanya ±10 dtk per potongan). Unduhan yang putus di tengah diulang otomatis (3×).
* **Satu login** untuk seluruh run (login ulang otomatis bila > 20 menit).
* Sisi PHP sudah cepat: pembaca xlsx streaming (`Xlsx_cepat`), insert 500/query, update 200/query, resi tanpa perubahan dilewati.
* **Tahan gagal:** satu potongan gagal tidak menghentikan yang lain; berhenti lebih awal hanya bila 3 potongan berturut-turut gagal
  (mis. Apache mati). Run berikutnya aman diulang (upsert idempotent).
* Lock `scratch/penjualan_pesanan/berjalan.lock` mencegah dua run tumpang tindih (task `IgnoreNew` + lock; lock > 6 jam dianggap usang).

## Mencatat hasil — cara menelusuri

Tiap run **selalu** menulis di `logs/`:

1. **`riwayat_penjualan_pesanan.csv`** — satu baris per run (buka di Excel): `status` (BERHASIL/GAGAL), `tahap_gagal`, `alasan`
   (semua potongan yang gagal + penyebabnya), `saran_perbaikan`, `ringkasan` (mis. `13/13 potongan berhasil`), `durasi_detik`.
   Filter `status = GAGAL` untuk melihat semua kegagalan.
2. **`riwayat_penjualan_pesanan_potongan.csv`** — satu baris per **potongan tanggal** per run: rentang tanggal, status, tahap gagal,
   alasan, saran, ukuran file, detik unduh/upload, dan pesan hasil IRESIS (jumlah resi baru/diperbarui/dilewati). Cocokkan lewat `run_id`.
3. **`auto_upload_penjualan_pesanan.log`** — log rinci, tiap baris diberi `run_id`; error tak terduga menyertakan traceback. Token/JWT disensor.
4. `auto_upload_penjualan_pesanan_stdout.log` — output mentah yang lolos dari logger (mis. Python gagal start).

Exit code 1 bila ada potongan gagal (*Last Run Result* = 1 di Task Scheduler). Berkas xlsx potongan yang gagal dipertahankan di
`scratch/penjualan_pesanan/` (berkas > 3 hari dibersihkan otomatis).

| `tahap_gagal` | Penyebab umum | Tindakan |
|---|---|---|
| `PERSIAPAN` | kredensial/`secrets_local.py` kosong; run sebelumnya masih jalan (lock); potongan tidak dicoba karena 3 gagal beruntun | isi secrets; tunggu / hapus lock usang; lihat alasan potongan yang gagal |
| `LOGIN` | password Jubelio diganti, akun dikunci, internet putus | login manual di v2.jubelio.com, perbarui `jubelio_password` di `secrets.php` |
| `URL_LAPORAN` | 429 rate limit, akun tanpa akses laporan penjualan, endpoint `sales-list` berubah | tunggu jadwal berikut; bila berulang sniff ulang |
| `UNDUH` | token laporan ditolak, koneksi putus (3× coba), report server error | cek internet; bila 403/404 sniff ulang tombol Cetak → Excel |
| `VALIDASI_FILE` | file kosong/bukan xlsx | buka berkas di `scratch/penjualan_pesanan/` |
| `UPLOAD` | Apache mati, token cron salah (403), PHP kehabisan memori/timeout, header xlsx berubah | pesan IRESIS ada di `alasan`; cek `application/logs/`; perkecil `--potong-hari` |
| `TAK_TERDUGA` | bug di script | baca traceback di `.log` |

## File

| File | Fungsi |
|------|--------|
| `scripts/auto_upload_penjualan_pesanan.py` | Program utama (memakai ulang fungsi Jubelio dari `auto_upload_retur_penjualan.py`) |
| `scripts/penjualan_pesanan_senyap.vbs` | Pembungkus tanpa jendela konsol (dipanggil Task Scheduler) |
| `scripts/daftar_task_penjualan_pesanan.ps1` | Mendaftarkan task harian 22.00 (aman diulang) |

Butuh `scripts/jubelio_credentials.py` dan `scripts/secrets_local.py` (`IRESIS_CRON_TOKEN`, `IRESIS_BASE_URL`) — sama dengan skrip lain.

## Pasang / uji

```powershell
powershell -ExecutionPolicy Bypass -File scripts\daftar_task_penjualan_pesanan.ps1
```

Uji tanpa menulis ke DB: `python scripts\auto_upload_penjualan_pesanan.py --tanpa-upload --hari 14`.
Uji tulis kecil: `python scripts\auto_upload_penjualan_pesanan.py --hari 7`.

Jam 22.00 dipilih setelah cek status retur 20.30 selesai dan sebelum arsip 01.00 / refresh status 03.00, di luar jam kerja
(upsert besar membebani MySQL). Ubah jam di `daftar_task_penjualan_pesanan.ps1`.

## Cadangan & pembatalan

Sebelum run pertama (24 Nov 2026) `tblprintresi` dicadangkan penuh: `logs/backup/bak_printresi_sebelum_penjualan_pesanan_20261124.sql`
(mysqldump, folder di-gitignore). Pemulihan butuh persetujuan sesuai aturan UPDATE proyek.

## Interaksi dengan job lain

* **Refresh status resi (03.00)** dan **Upload resi API** memakai jalur upsert yang sama — saling melengkapi, tidak bentrok
  (status sama dilewati).
* **Cek status retur Jubelio** — pesanan yang sudah `SELESAI (PESANAN COMPLETED)` di `tblreturjubelio` tidak ditimpa; `COMPLETED` di
  `tblprintresi` tidak diubah balik oleh upload ini.

## Uji tulis pertama (24 Nov 2026, 7 hari)

* Percobaan 1 menemukan bug: `Cron::auto_upload_resi` mengirim `user_id` null → `admin_pegawai` NOT NULL gagal, transaksi dibatalkan
  (tidak ada data tertulis), tetapi respons `success:true` dengan teks "Error…". Diperbaiki: `user_id` = `'0'` dan respons berawalan
  "Error" kini `success:false`; skrip juga menolak pesan berawalan "Error" sebagai sukses.
* Percobaan 2: unduh 25 dtk, **upload+olah PHP hanya 11 dtk** untuk ±85 rb baris → `Terinput 1 | Dilewati 9485 (baris tanpa SKU/resi) | Duplikat 5201
  (COMPLETED, dilewati) | Diupdate 4349 | Tidak berubah 65794`. Perkiraan 90 hari: ±13 potongan, kurang dari 15 menit.

## Uji paralel (24 Nov 2026, 1-24 Nov, 4 potongan, 3 unduhan bersamaan)

Total **2 mnt 47 dtk** (unduh 27-62 dtk per potongan, 1 unduhan putus di tengah lalu berhasil di percobaan ke-2; upload PHP 5-12 dtk
per potongan). Hasil: 4/4 berhasil; Diupdate 203 + 2.232 + 972 + 94 = **3.501 resi**, Terinput 4. Perkiraan 90 hari ±13 potongan: ±8 menit.
