<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| ARSIP DATA — setelan job malam `cron/arsip_harian`
| -------------------------------------------------------------------
| Skema: iresis_prod = jendela data hidup (retensi_hari terakhir);
|        iresis_arsip = SEMUA data sejak awal sampai putaran terakhir,
|        tabel & kolom persis sama dengan prod (superset, bukan sisa).
| Penjelasan lengkap di docs/ARSIP_DATA.md.
|
| Nama DB prod TIDAK diatur di sini — diambil dari koneksi aktif
| (secrets.php `db_database`) supaya tidak bisa salah arah.
*/

// Nama database arsip. Harus di server MariaDB yang sama dengan prod
// karena job memakai REPLACE INTO arsip.tabel SELECT * FROM prod.tabel.
$config['db_arsip'] = 'iresis_arsip';

// Retensi keluarga resi di prod (hari, dihitung dari aktivitas terakhir resi).
// Dipakai tahap "purna" (belum aktif — lihat docs/ARSIP_DATA.md §Tahapan).
$config['retensi_hari'] = 60;

// Retensi khusus per tabel yang bukan bagian keluarga resi (hari).
$config['retensi_khusus'] = array(
	'tblkpi'                     => 400,
	'tblpacker_performance_logs' => 400,
	'notifications'              => 30,
);

// Tabel prod yang TIDAK disalin ke arsip: pola nama (substring, tanpa regex)
// untuk tabel sisa/backup yang tidak sengaja tertinggal di prod.
$config['kecuali_pola'] = array('_corrupt_', '_bak_', '_backup', '_dup_', 'tmp_');

// Tabel prod yang tidak disalin, disebut namanya satu per satu.
$config['kecuali_tabel'] = array();

// Tabel dengan jumlah baris <= ini (atau PK bukan integer) disalin PENUH tiap
// malam (REPLACE seluruh isi) — menutup update apa pun pada tabel master.
// Di atas itu disalin BERTAHAP: pk > watermark + baris yang berubah N hari terakhir.
$config['batas_penuh'] = 20000;

// Ukuran satu batch REPLACE INTO ... SELECT pada tabel bertahap (jumlah baris).
$config['batch'] = 50000;

// Jeda antar batch (milidetik) supaya I/O tidak menyedot disk saat muat awal.
$config['jeda_batch_ms'] = 100;

// Baris yang kolom-ubahnya >= NOW() - N hari disalin ulang tiap malam
// (menangkap UPDATE pada baris lama tanpa menunggu tahap purna).
$config['jendela_ubah_hari'] = 2;

// Nama kolom yang dianggap penanda "baris diubah" (dicocokkan berurutan,
// dipakai yang pertama ada dan bertipe datetime/timestamp).
$config['kolom_ubah'] = array('modified_at', 'updated_at', 'updated', 'tanggal_update', 'last_update');

// Batas waktu satu putaran (detik). Lewat batas → berhenti rapi di antara batch,
// putaran berikutnya melanjutkan dari watermark. Muat awal 3+ GB butuh beberapa putaran.
$config['maks_detik'] = 7200;

// Kunci supaya dua putaran tidak jalan bersamaan; dianggap basi setelah N detik.
$config['kunci_basi_detik'] = 6 * 3600;

/*
| -------------------------------------------------------------------
| MODE ARSIP — user melihat iresis_arsip lewat aplikasi yang sama
| -------------------------------------------------------------------
| Diaktifkan per-session lewat menu "Mode Arsip" (uri `arsip`); siapa yang
| boleh = role yang punya akses menu itu di halaman Access (roleaccess).
| MY_Controller::terapkan_mode_arsip() memindahkan koneksi ke DB arsip
| (db_select) di akhir constructor, SETELAH bootstrap migrasi dan cache
| menu — jadi DDL bootstrap selalu jatuh ke prod.
*/

// FALSE (bawaan): sesi Mode Arsip dibuat READ ONLY di MariaDB
// (SET SESSION TRANSACTION READ ONLY) — INSERT/UPDATE/DELETE ditolak server,
// halaman laporan/KPI tetap jalan karena hanya membaca (diverifikasi 21 Sep 2026:
// tulisan di menu-menu itu semuanya aksi sengaja, bukan saat membuka halaman).
// TRUE: tulisan diizinkan masuk ke arsip. Hati-hati: baris BARU yang dibuat di
// arsip memakai id yang juga akan dipakai prod, dan malam berikutnya REPLACE
// dari prod menimpanya. Nyalakan hanya kalau memang ada kebutuhan yang jelas.
$config['mode_arsip_tulis'] = FALSE;
