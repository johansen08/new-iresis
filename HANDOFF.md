# Handoff — Sistem IRESIS (new-iresis)

Dokumen ini adalah ringkasan orientasi untuk developer baru yang mengambil alih project **IRESIS** — sistem manajemen gudang/fulfillment (receipt → picking → packing → handover → retur → QC/repair → finance) berbasis **CodeIgniter 3 (PHP)**. Untuk detail teknis mendalam, lihat folder [`docs/`](docs/) (`ANALISIS_PROGRAM.md`, `DATABASE_STRUCTURE.md`, `WORKFLOW_DIAGRAM.md`, `DEVELOPMENT_STANDARDS.md`, `AUTO_UPLOAD_RESI.md`, `AUTO_UPLOAD_RETUR_JUBELIO.md`) — dokumen-dokumen itu sudah cukup lengkap; file ini mengaitkannya jadi satu peta.

## 1. Stack & Struktur

- **Framework**: CodeIgniter 3.x (`system/`), PHP 7.4.
- **DB**: MySQL/MariaDB, koneksi di `application/config/database.php` (mendukung multi-DB via array `$db`).
- **Frontend**: jQuery + DataTables + Bootstrap 3, NVD3 (chart), Summernote, Noty.
- **Library penting**: Guzzle (HTTP client), PhpSpreadsheet (export Excel), DomPDF (PDF).
- **Auth**: session `siresi_session` (24 jam), password **MD5 tanpa salt** (⚠️ lihat § Risiko Keamanan), role-based access via `tblroleaccess`/`tblrole`, dicek di `application/core/MY_Controller.php`.
- **51 controller**, **44 model** (suffix `_fcd` = model utama per domain).

## 2. Modul Bisnis Utama

Alur inti: **Receipt (resi masuk) → Picking → Packing → Handover → Shipped**, plus jalur paralel **Retur → Buka Retur → QC/Repair/Reject → Restock/Display → Finance/Accounting**.

| Area | Controller kunci | Model kunci | Tabel utama |
|---|---|---|---|
| Receipt/Resi | `Receipt.php` | `Receipt_fcd.php` | `tblprintresi`, `tbldetailprintresi` |
| Picking | `Picker.php`, `Resi_team.php`, `Lost_scan_picker.php` (Laporan Lost Scan Picker: antrean resi belum-picker, tombol Tambahkan Picker) | `Picking_fcd.php`, `Lost_scan_picker_fcd.php` | `tblresiambilbarang`, `tbllostscanpicker_pending` |
| Packing | `Packer.php`, `Packer_monitoring.php` | `Packer_fcd.php`, `Packer_monitoring_fcd.php` | `tblpacking`, `tblpacker_monitoring` |
| Handover / HO | `Handover.php`, `Scan_logistic.php` (Scan Paket NDD), `Scan_paket_ndd_new.php` (versi baru: panel lost scan packer + lapor ke tim picker), `Lost_scan_packer.php` | `Handover_fcd.php`, `Scan_logistic_fcd.php`, `Scan_paket_ndd_new_fcd.php`, `Lost_scan_packer_fcd.php` | `tblresikeluar`, `tblscan_ndd`, `tbllostscanpacker` |
| Retur | `Retur.php` | `Retur_fcd.php`, `Buka_retur.php` | `tblresiretur`, `tblbukaretur` |
| QC/Purchasing | `Purchasing_qc.php` (QC/Reject/Repair) | `Pengembalian_qc.php`, `Purchasing_reject.php`, `Purchasing_repair.php` | `tblpengembalian_qc`, `tblpurchasing_reject`, `tblpurchasing_repair` |
| Accounting | `Accounting.php` | `Buka_retur.php`, `Surat_jalan_fcd.php` | `tblbukaretur`, `surat_jalan_tp` |
| Inbound | `Inbound.php`, `Inbound_picker.php` | `Surat_jalan_fcd.php` | `surat_jalan_tp` |
| Finance | `Finance.php` | `Denda.php`, `Receipt_fcd.php` | `pergantian_barang`, `denda` |
| Restock (reverse logistics) | `Restock.php` | `Restock_fcd.php` | `tblretur_display_batch(_detail)` |
| CS | `Cs.php`, `Masalah_picker_new.php` (Daftar Masalah Picker New, terpisah dari yang lama) | berbagai `_fcd`, `Masalah_picker_new_fcd.php` | `tblkurangan_picker`, `tblmasalahpicker`, `tblmasalahpicker_proses(_item)`, `tblretur_komplain` |
| Monitoring | `Monitoring.php`, `Packer_monitoring.php` | `Packer_monitoring_fcd.php` | — |
| KPI | `Kpi_reports.php` | `Kpi_fcd.php`, `Target_kpi_fcd.php` | `tblkpi`, `tbltargetkpi`, `tblstatusperforma` |
| SKU/Master data | `Sku.php`, `Sku_special.php`, `User.php`, `Menu.php`, `Access.php` | `Sku_fcd.php`, dll | `tblsku`, `tbllokasi`, `tbluser`, `tblmenu`, `tblroleaccess` |
| Cron/scheduler | `Cron.php` | — | token-protected endpoints |

Detail lengkap per-controller ada di `docs/ANALISIS_PROGRAM.md`; skema tabel di `docs/DATABASE_STRUCTURE.md`; diagram alur di `docs/WORKFLOW_DIAGRAM.md`.

## 3. Integrasi Eksternal

**Jubelio** (marketplace/ERP eksternal): tidak ada API publik yang stabil untuk download laporan — laporan Excel di-generate lewat **Telerik Report Server** yang hanya bisa dipicu dari flow "Cetak" di aplikasi, sehingga download murni via HTTP API gagal (404). Solusinya browser automation. Lihat memory [jubelio-retur-download-telerik-blocker](../.claude-memory) dan `docs/AUTO_UPLOAD_RESI.md` / `docs/AUTO_UPLOAD_RETUR_JUBELIO.md`.

Script Python terkait (folder `scripts/`):
- `arsip/sniff_jubelio.py` — reverse-engineer network call Jubelio (selesai, diarsipkan).
- `auto_upload_resi.py` — download laporan resi dari Jubelio (browser automation) → upload ke `cron/auto_upload_resi?token=...`.
- `auto_upload_retur_jubelio.py` — sama untuk retur → `cron/auto_upload_retur_jubelio?token=...`.
- `auto_upload_sku.py` — upload SKU; `arsip/jubelio_api_probe.py` — probing API (selesai, diarsipkan).

⚠️ Kredensial Jubelio & token cron **hardcoded di script Python** — harus dipindah ke env var sebelum deploy produksi lain.

**Catatan riset lanjutan (2026-07-02, di memory)**: data retur sebenarnya tersedia via Data API `api2.jubelio.com` (`POST /login`, `GET /sales/v2/sales-returns/`) tanpa perlu browser — tapi rate-limited keras untuk token login-web; perlu API Key resmi dari setting Jubelio agar bisa dipakai 100% server-side PHP. Ini jalur yang belum selesai divalidasi (belum pernah dapat response 200), jadi masih pakai jalur browser automation sebagai solusi berjalan.

## 4. Risiko Keamanan (perlu perhatian sebelum deploy lanjut)

- Password user pakai **MD5 tanpa salt** — perlu migrasi ke bcrypt/password_hash.
- CSRF protection & XSS filtering CI tampaknya **dimatikan** — cek `application/config/config.php`.
- Kredensial Jubelio + token cron **hardcoded** di script Python (`scripts/*.py`) — pindahkan ke `.env`/config terpisah, jangan commit ke repo publik.

## 5. Cron Jobs (token-protected, via `Cron.php`)

Semua endpoint butuh `?token=<cron_token di secrets.php>` atau dijalankan via CLI:
- `cron/finalisasi_video`, `cron/tutup_video_menggantung` — pemeliharaan video packing (task scheduler).
- `cron/auto_upload_resi`, `cron/auto_upload_retur_jubelio` — endpoint upload yang dipanggil script Python di atas.

## 6. Menjalankan Project

1. Import skema database (lihat `docs/DATABASE_STRUCTURE.md`) ke MySQL/MariaDB, set koneksi di `application/config/database.php`.
2. `composer install` (Guzzle, PhpSpreadsheet, DomPDF).
3. Jalankan Apache/Nginx (mod_rewrite aktif) atau `php -S` mengarah ke root project.
4. Login pakai akun dari tabel `tbluser`.
5. Untuk fitur WA, jalankan gateway Node.js di `localhost:3000` (repo terpisah, tidak ada di project ini — pastikan tanyakan lokasinya ke tim sebelumnya).
6. Untuk auto-sync Jubelio, siapkan Python env (DrissionPage, requests) dan jadwalkan `scripts/auto_upload_resi.py` / `scripts/auto_upload_retur_jubelio.py` via Task Scheduler/cron.

## 7. Dokumen Pendukung

- [`docs/ANALISIS_PROGRAM.md`](docs/ANALISIS_PROGRAM.md) — analisis per-controller/model (paling detail).
- [`docs/DATABASE_STRUCTURE.md`](docs/DATABASE_STRUCTURE.md) — skema tabel.
- [`docs/WORKFLOW_DIAGRAM.md`](docs/WORKFLOW_DIAGRAM.md) — diagram alur bisnis.
- [`docs/LOST_SCAN.md`](docs/LOST_SCAN.md) — alur lost scan packer/picker lintas meja HO → packer → tim picker (kondisi, skenario, keputusan desain, endpoint).
- [`docs/DEVELOPMENT_STANDARDS.md`](docs/DEVELOPMENT_STANDARDS.md) — coding standard.
- [`docs/AUTO_UPLOAD_RESI.md`](docs/AUTO_UPLOAD_RESI.md), [`docs/AUTO_UPLOAD_RETUR_JUBELIO.md`](docs/AUTO_UPLOAD_RETUR_JUBELIO.md) — setup automasi Jubelio.
- [`docs/PANDUAN_PULL_PRODUKSI.md`](docs/PANDUAN_PULL_PRODUKSI.md) — prosedur `git pull` di PC produksi (backup, migrasi, verifikasi, rollback) + langkah tambahan per rilis.
- `README.md` di root saat ini **kosong** — pertimbangkan mengisinya dengan ringkasan dari file ini.
