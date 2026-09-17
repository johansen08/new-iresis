# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Aturan wajib (dari `.agents/AGENTS.md`)

- **DILARANG KERAS** menjalankan SQL `DELETE`, `DROP`, atau `TRUNCATE` — tanpa pengecualian. Jika user minta hapus data, tolak dan tawarkan soft-delete / flag / arsip.
- SQL yang `UPDATE`/mengubah record: minta konfirmasi user **tiga kali**, tampilkan dulu tabel target, query, dan dampak yang diharapkan.
- Sebelum `UPDATE` massal: **wajib backup** tabel terkait (`CREATE TABLE ... SELECT` atau `mysqldump`).

Bahasa kerja proyek ini **Indonesia** — komentar kode, pesan commit, dokumen, dan teks UI semuanya berbahasa Indonesia. Ikuti itu.

## Perintah

Tidak ada test suite, linter, atau build step di repo ini (tidak ada PHPUnit, tidak ada `require-dev`, tidak ada npm build). Verifikasi dilakukan dengan syntax check + uji manual di browser.

```bash
# Syntax check — satu-satunya "lint" yang tersedia. Jalankan untuk SETIAP file PHP yang diubah.
C:/xampp/php/php.exe -l application/controllers/Retur.php
```

```bash
# Dependency (Guzzle, PhpSpreadsheet, Pusher)
composer install
```

Aplikasi berjalan di Apache XAMPP pada port **80** (dan 443 untuk LAN HTTPS): `http://localhost/new-iresis/`. Login pakai akun dari tabel `tbluser`.

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root -e "SHOW DATABASES;"
```

```bash
# Endpoint cron — lewat HTTP butuh ?token= (= nilai `cron_token` di secrets.php); lewat CLI bebas token
C:/xampp/php/php.exe index.php cron finalisasi_video mp4
```

Script diagnostik sekali pakai **tidak di-commit** — `.gitignore` sudah membuang `dev_tools/`, `scratch/`, dan pola root seperti `check_*.php`, `describe_*.php`, `dump_db*.php`, `debug_*.php`. Taruh script investigasi di sana, bukan di `application/`.

## Arsitektur

CodeIgniter 3 + MySQL, PHP 7.4 secara nominal (`composer.json` pin platform `7.4.33`) tapi sudah dijalankan di PHP 8.2 — lihat commit `e48446d` untuk tambalan kompatibilitasnya. `ENVIRONMENT` di `index.php` default **`development`**, jadi `display_errors` menyala di mesin ini.

### Aplikasi ini SPA semu lewat AJAX — ini sumber bug paling sering

Navigasi menu tidak reload halaman. `assets/js/plugins.js` mem-fetch URL controller, lalu menaruh `data.view` ke `.page-content-wrap`. Sisi server: `MY_Controller::show()` merender view jadi string dan membalas **JSON** `{"view": "<html>", "message": ...}`.

Konsekuensinya: **satu byte output nyasar merusak seluruh halaman** — spasi sebelum tag PHP, PHP Warning, atau halaman error HTML CodeIgniter akan tercampur ke JSON, jQuery gagal parse, dan body mentah tampil sebagai teks di layar. Dua pertahanan yang sudah ada, jangan dibongkar:

- `application/core/MY_Controller.php` → `make_ajax_response($code, $message, $data)`: menghabiskan **semua** level output buffer (`while (ob_get_level() > 0) ob_end_clean()`), selalu kirim HTTP 200 dan sampaikan status di body JSON — karena `set_status_header(4xx)` di CI menghasilkan halaman HTML yang merusak parsing. Pakai helper ini untuk semua respon AJAX.
- `application/core/MY_Output.php`: memastikan `CI_Output::$final_output` berupa string, bukan NULL, supaya `str_replace()` di `_display()` tidak memicu *Deprecated* PHP 8.1+ yang ikut tercetak setelah JSON. Ini menutup controller yang tidak turun dari `MY_Controller` (`Cron`, `Login`).

Endpoint DataTables server-side membalas `{"data": [[...]]}` berisi **array string HTML yang sudah dirender di PHP** (termasuk tombol aksi dengan `onclick`), bukan data mentah — lihat `Qc_return::get_data()` sebagai contoh polanya.

### Bootstrap migrasi ter-gate versi

`MY_Controller::jalankan_bootstrap_sekali()` menjalankan deretan `run_*_migrations()` (DDL + auto-create menu + hak akses) **sekali per versi**, dijaga konstanta `BOOTSTRAP_VERSI` dan penanda file `application/cache/bootstrap_migrasi.txt`. Dulu blok ini jalan di setiap request dan memakan ~98 query + DDL (228 ms) bahkan di endpoint scan.

**Kalau Anda menambah migrasi atau menu baru di file itu, WAJIB naikkan `BOOTSTRAP_VERSI`** (format `YYYY-MM-DD.n`) — itu satu-satunya pemicu agar migrasi jalan ulang di server, sekaligus membuang cache pohon menu semua user.

### Menu & hak akses

Menu disimpan di tabel `menu`/`tblmenu` dengan `uri` + `parentid`, hak akses di `tblroleaccess` per `hakakses` (role id di session). `ambil_menu_tree()` membangun HTML menu via `application/helpers/menu_helper.php` dan **men-cache-nya di session** dengan kunci `BOOTSTRAP_VERSI|role` — jadi perubahan menu/role tidak terlihat user sampai versi dinaikkan atau session diperbarui. Pengecekan role per-tombol sering dilakukan manual di controller (`$this->data['user']['hakakses']`), bukan terpusat: `MY_Controller::validate()` saat ini praktis no-op.

### Routing

`application/config/routes.php` memuat **392 route eksplisit** yang memetakan URL ber-tanda-hubung ke method ber-underscore (`receipt/save-receipt` → `receipt/save_receipt`), sering didaftarkan dalam dua ejaan sekaligus. Endpoint baru yang dipanggil dari JS perlu entri di sini. `index_page` kosong + rewrite di `.htaccess`, jadi URL tanpa `index.php`.

### Controller & model

39 controller, 41 model (13 controller mati bawaan template lama — email blast, webhook, verification, dsb. — dihapus 17 Sep 2026; kalau butuh, ada di git history sebelum branch `chore/bersih-bersih-kode-mati`). Model utama per domain bersuffix **`_fcd`** (`Retur_fcd`, `Receipt_fcd`, …), di-load di constructor controller. `Status_performa` dan `Picker_performance` berfungsi tapi tidak ada di menu — hanya via URL langsung. Controller terbesar: `Retur.php` (3088 baris), `Report.php`, `Cs.php`, `Accounting.php` — perubahan di sana perlu hati-hati karena banyak method berbagi tabel retur yang sama.

Alur bisnis inti: **Receipt → Picking → Packing → Handover → Shipped**, dengan jalur paralel **Retur → Buka Retur → QC/Repair/Reject → Restock/Display → Finance/Accounting**. Peta lengkap area → controller → model → tabel ada di `HANDOFF.md` §2; detail per-controller di `docs/ANALISIS_PROGRAM.md`, skema tabel di `docs/DATABASE_STRUCTURE.md`, diagram alur di `docs/WORKFLOW_DIAGRAM.md`.

### Kredensial

Tidak ada kredensial di file config. `application/config/secrets.php` (gitignored) mengembalikan array, dibaca lewat `iresis_secret('nama')` dari `secrets_load.php`; `database.php`, `pusher.php`, dan constructor `Cron` memanggil fungsi itu. Mesin baru: salin `secrets.php.example` → `secrets.php`. Aplikasi `exit()` dengan pesan jelas kalau file atau key-nya belum ada.

### Integrasi eksternal

- **Pusher** (notifikasi realtime): `models/Notification.php::send()` → `libraries/Pusher_lib.php`.
- **Jubelio**: tidak ada API stabil untuk unduh laporan (Telerik Report Server hanya bisa dipicu dari flow "Cetak" di UI-nya), jadi jalurnya browser automation Python di `scripts/*.py` yang lalu POST ke endpoint `cron/auto_upload_*`. Lihat `docs/AUTO_UPLOAD_RESI.md` dan `docs/AUTO_UPLOAD_RETUR_JUBELIO.md`.
- **`Cron.php`**: auth via `?token=` yang dicocokkan ke `cron_token` di secrets, atau bebas token saat `is_cli()`. Isinya sekarang hanya `auto_upload_*` (dipanggil `scripts/*.py`), `check_resi_status`, `fix_resi_detail`, `tutup_video_menggantung`, dan `finalisasi_video` (task scheduler, tiap menit).
- **WhatsApp gateway & ngrok sudah dihapus** (17 Sep 2026, commit di branch `chore/hapus-wa-gateway-ngrok`). Kalau perlu lagi, sumbernya ada di git history sebelum commit itu.

## Standar coding (ringkas dari `docs/DEVELOPMENT_STANDARDS.md`)

- Respon AJAX: selalu bersihkan output buffer total sebelum kirim JSON (pakai `make_ajax_response()`).
- Operasi berat (upload SKU, export massal): `ini_set('memory_limit', '3072M')` dan `set_time_limit(0)` di awal method; sisi JS pakai `timeout: 600000`.
- Operasi DB krusial: matikan `$this->db->db_debug` sementara agar error tidak mencetak halaman HTML CI yang merusak JSON, lalu pulihkan nilainya. Selalu pakai `trans_start()`/`trans_complete()`.
- Upload: tampilkan urutan kolom Excel (A, B, C…) eksplisit di layar, sediakan progress tracking, dan tampilkan raw response saat parsing JSON gagal.

## Alur git

Branch utama **`master`** (branch `development` sudah dihapus 2026-09-12). Satu branch per pekerjaan lahir dari `master` (`feature/*` atau `fix/*`), pesan commit `tipe(modul): deskripsi` dalam bahasa Indonesia (`feat`, `fix`, `docs`, `chore`), merge balik dengan `--no-ff`, hapus branch dengan `-d` (jangan `-D`). Detailnya di `docs/DEVELOPMENT_STANDARDS.md` §5.

Remote `origin` = `https://github.com/johansen08/new-iresis.git` (sejak 2026-09-12). Setelah merge ke `master`, `git push origin master`. Prosedur menarik perubahan ke PC produksi ada di `docs/PANDUAN_PULL_PRODUKSI.md`.

## Risiko yang sudah diketahui (jangan dianggap temuan baru)

Password user MD5 tanpa salt; `csrf_protection` dan `global_xss_filtering` keduanya `FALSE` di `config.php`; `enable_hooks` `FALSE`. `README.md` kosong. Konteks lengkapnya di `HANDOFF.md` §4.

`.htaccess` root adalah **whitelist**: hanya `index.php`, `assets/`, dan `favicon.ico` yang boleh diakses langsung dari browser; `.git`, `scripts/`, `docs/`, `dev_tools/`, `logs/`, `sql_migrations/`, `vendor/`, dan file root ber-ekstensi md/bat/ps1/sql/py/json/lock/xml/log/txt dijawab 403 (sejak 17 Sep 2026 — sebelumnya kredensial di `scripts/` dan seluruh `.git` bisa diunduh dari LAN). File yang memang harus bisa diunduh user taruh di bawah `assets/`.
