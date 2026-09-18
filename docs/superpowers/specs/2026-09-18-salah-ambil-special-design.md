# Desain: Menu Packer "Salah Ambil Special"

Tanggal: 2026-09-18
Status: disetujui user, siap dibuat rencana implementasi

## 1. Latar belakang

Resi "spesial" adalah resi yang isinya tepat **1 SKU dengan qty 1**. Resi seperti ini dikumpulkan dan dikerjakan sekaligus. Ketika picker salah mengambil satu batch (misal 30 resi yang seharusnya BSBI-4 tapi terambil BSBI-5), packer hari ini harus:
scan resi → tombol Masalah Picker → pilih SALAH AMBIL → ketik SKU salah → submit, **diulang 30 kali** di halaman Scan Resi Packer.

Menu ini memangkas itu menjadi: isi dua SKU sekali, lalu scan 30 resi berturut-turut. Tiap scan langsung tercatat sebagai masalah picker tipe SALAH AMBIL (`id_typemasalah = 4`) di `tblmasalahpicker`, sehingga muncul di Daftar Masalah Picker (lama maupun New) tanpa perubahan apa pun di sana.

## 2. Keputusan desain (hasil diskusi)

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Field di kepala form | **Dua SKU**: "SKU seharusnya" dan "SKU terambil" | `sku` di `tblmasalahpicker` = SKU resi (yang benar), `sku_salah` = yang keliru terambil dan **dicetak di slip** (`SALAH AMBIL (terambil BSBI-5)`). SKU seharusnya dipakai sebagai penjaga agar resi nyasar ditolak. |
| Resi bukan 1 SKU / 1 qty | Tolak | Menu ini khusus resi spesial; resi campuran lewat Scan Resi Packer biasa. |
| Resi sudah di-packing | Tolak | Salah ambil ketahuan sebelum packing; kalau sudah packing dicurigai salah scan. |
| Resi belum di-scan ambil picker | Tolak | CS tidak bisa mengaitkan ke picker mana pun (picker dideteksi dari `tblresiambilbarang`). |
| Resi sudah pernah dilaporkan | Tolak, status apa pun | Tidak membuat baris ganda; berbeda dari `Packer_fcd::save_masalah_picker()` yang meng-update baris lama dan mereset status ke pending. |
| Batalkan baris hasil scan | **Tidak ada di v1** | `DELETE` dilarang; soft-delete lewat status butuh mengubah query KPI picker, Error Recap, dan Laporan yang membaca tabel ini tanpa filter status. Penjaga dua SKU + validasi sudah cukup menyaring. |
| Struktur kode | Controller + model + view baru (`Salah_ambil_special`) | `Packer.php` sudah 1.600+ baris dan halaman scan-nya sensitif timing scanner; `save_masalah_picker()` semantiknya tidak cocok. Pola sama dengan `Masalah_picker_new`. |

## 3. Menu & hak akses

- Nama menu: **Salah Ambil Special**, uri `salah-ambil-special`, icon `fa fa-exchange`.
- Parent: parent dari menu `packer/scan_packer` (grup TIM PACKER); cadangan `24`. `sortorder` = 12 (setelah Scan Resi Packer (Webcam) yang 11).
- Role boleh: **1 (webmaster), 4 (client packer)** — sama dengan Scan Resi Packer (Webcam).
- Ditanam oleh `MY_Controller::run_salah_ambil_special_migration()` (pola `run_menu_scan_packer_webcam()`: `order_by('id','ASC')->limit(1)` saat mencari menu, insert `roleaccess` kalau belum ada). `BOOTSTRAP_VERSI` naik ke `2026-09-18.4`. Tidak ada tabel/kolom baru.
- Controller menjaga role di constructor (`const ROLE_BOLEH = [1, 4]`) dengan balasan tetap JSON valid, meniru `Masalah_picker_new::tolak_role_tanpa_akses()`: untuk `index` kirim view dengan `akses_ditolak = TRUE`, untuk endpoint data lewat `make_ajax_response(403, ...)`.

## 4. Alur layar

1. **Buka menu** → panel dengan dua input teks: `SKU seharusnya`, `SKU terambil`, tombol **Kunci & Mulai Scan**. Field nomor resi masih disabled.
2. **Kunci** → POST `salah-ambil-special/cek-sku` `{sku_benar, sku_salah}`. Server: keduanya wajib terisi, ada di `tblsku` (`id_sku`), dan tidak sama. Balasan berisi `nama_sku` + `no_rak` masing-masing; JS menampilkannya di bawah field sebagai konfirmasi visual, mengunci kedua field (readonly), mengaktifkan field resi, dan memindahkan fokus ke sana. Kalau gagal → pesan di bawah field, `audio-fail`.
3. **Scan resi** (Enter di field resi) → POST `salah-ambil-special/scan-resi` `{noresi, sku_benar, sku_salah}`. Balasan `code 201` (tercatat) atau `4xx` (ditolak + alasan). JS menambah baris di **atas** tabel sesi: `#`, no. resi, hasil (label hijau "Tercatat" / merah "Ditolak: <alasan>"), jam. Counter `Tercatat: n | Ditolak: m` di heading. Bunyi `audio-alert` untuk tercatat, `audio-fail` untuk ditolak. Field resi dikosongkan dan difokuskan lagi.
4. **Antrean request**: scan diproses satu per satu di JS (queue; request berikutnya baru dikirim setelah yang sebelumnya selesai) supaya scanner cepat tidak memicu dua insert bersamaan dan urutan baris di tabel sesi sesuai urutan scan. Field resi tetap bisa diketik selama antrean berjalan.
5. **Ganti SKU** → `confirm()`, buka kunci field SKU, kosongkan tabel sesi & counter. Tabel sesi hanya di memori browser; riwayat resminya ada di Daftar Masalah Picker.
6. Form scan dan form SKU memakai class **`nojs`** dan `preventDefault` sendiri, seperti Scan Resi Packer, supaya handler global `plugins.js` tidak mengganti isi `.page-content-wrap` saat request berjalan (yang menghapus input dari DOM dan menelan scan).
7. Kalau `akses_ditolak`, view hanya menampilkan alert penolakan.

## 5. Validasi `scan-resi`

Server memeriksa berurutan dan berhenti di kegagalan pertama. Semua balasan lewat `make_ajax_response()` (HTTP 200, status di body).

| # | Kondisi | Code | Pesan |
|---|---|---|---|
| 0 | Method bukan POST | 400 | `INVALID_REQUEST_METHOD` |
| 1 | `noresi` kosong, atau `sku_benar`/`sku_salah` kosong / sama / tidak ada di `tblsku` | 400 | "Kunci SKU dulu" / pesan cek SKU |
| 2 | `noresi` tidak ada di `tblprintresi` | 404 | "Resi tidak ditemukan" |
| 3 | Jumlah baris `tbldetailprintresi` untuk `id_printresi` ≠ 1, atau `jumlah` ≠ 1 | 422 | "Bukan resi spesial (x SKU, qty y)" |
| 4 | `dr.sku` ≠ `sku_benar` | 422 | "SKU resi <dr.sku>, bukan <sku_benar>" |
| 5 | Ada baris `tblpacking` dengan `id_resi = id_printresi` | 409 | "Resi sudah di-packing" |
| 6 | Tidak ada baris `tblresiambilbarang` dengan `id_resi = id_printresi` | 422 | "Resi belum di-scan ambil picker" |
| 7 | Ada baris `tblmasalahpicker` dengan `id_printresi` + `sku = sku_benar` (status apa pun) | 409 | "Sudah dilaporkan (pending)" / "Sudah dilaporkan (sudah diproses CS)" |

Catatan:
- Kalau `noresi` punya lebih dari satu `id_printresi` (cetak ulang), dipakai `id_printresi` terbaru — konsisten dengan `Packer::detail_resi()` yang mengambil `rows[0]` setelah `ORDER BY pr.created_at DESC`.
- `noresi` di-`trim()`; SKU di-`trim()` + `strtoupper()` sebelum dicocokkan (kode SKU di `tblsku` huruf besar).

## 6. Penyimpanan

Lolos semua → satu `INSERT` ke `tblmasalahpicker`:

| Kolom | Nilai |
|---|---|
| `id_printresi` | id resi terpilih |
| `noresi` | noresi |
| `sku` | `sku_benar` |
| `qty` | 1 |
| `id_typemasalah` | 4 (SALAH AMBIL) |
| `qty_bermasalah` | 1 |
| `sku_salah` | `sku_salah` |
| `status` | 0 (pending) |
| `created_by` | `id_user` session |
| `created` | `date('Y-m-d H:i:s')` |

- Cek #7 dan INSERT berada dalam satu transaksi (`trans_begin` … `trans_commit`), dengan `SELECT id_printresi FROM tblprintresi WHERE id_printresi = ? FOR UPDATE` di awal agar dua scan resi sama yang nyaris bersamaan tidak lolos dua-duanya (yang kedua menunggu lock, lalu gagal di #7).
- `db_debug` dimatikan sementara selama transaksi dan dipulihkan setelahnya; kalau `trans_status() === FALSE` → rollback, `log_message('error', ...)`, balas 500 "Gagal menyimpan, coba lagi".
- Balasan sukses `201` membawa `{noresi, sku, sku_salah, nama_picker}` (nama picker dari `tblresiambilbarang` → `tblpegawai`/`tbluser`, untuk ditampilkan di baris sesi).

Setelah tersimpan, baris langsung tampil di Daftar Masalah Picker (lama & New): tipe SALAH AMBIL, `sku_salah` tercetak di slip, picker dideteksi dari `tblresiambilbarang`, nama packer = user yang scan. KPI picker dan Error Recap menghitungnya seperti laporan salah ambil biasa.

## 7. File

Baru:
- `application/controllers/Salah_ambil_special.php` — `index()`, `cek_sku()`, `scan_resi()`, penjaga role.
- `application/models/Salah_ambil_special_fcd.php` — `cari_sku($kode)`, `resi_terbaru($noresi)`, `detail_resi($id_printresi)`, `sudah_packing($id)`, `picker_resi($id)` (null kalau belum di-scan ambil), `laporan_ada($id, $sku)`, `simpan_salah_ambil(array $data)`.
- `application/views/salah_ambil_special/index.php` — panel SKU, panel scan, tabel sesi, JS (queue scan, audio, fokus).

Diubah:
- `application/config/routes.php` — `salah-ambil-special`, `salah-ambil-special/cek-sku`, `salah-ambil-special/scan-resi` (plus ejaan underscore).
- `application/core/MY_Controller.php` — `run_salah_ambil_special_migration()` dipanggil dari `jalankan_bootstrap_sekali()`, `BOOTSTRAP_VERSI = '2026-09-18.4'`.
- `docs/ANALISIS_PROGRAM.md` — satu paragraf di bagian Packer.

Tidak disentuh: `Packer.php`, `Packer_fcd.php`, `scan_packer*.php`, semua menu CS/KPI.

## 8. Verifikasi

- `C:/xampp/php/php.exe -l` untuk setiap file PHP yang dibuat/diubah.
- Uji manual di browser (login role packer):
  1. Menu muncul di TIM PACKER; login role lain tidak melihat menu dan URL langsung dibalas penolakan.
  2. Kunci SKU: kode tidak ada → ditolak; kedua sama → ditolak; valid → nama barang & rak tampil, fokus pindah ke field resi.
  3. Tiap kondisi #2–#7 dicoba satu resi → baris merah dengan alasan yang benar, bunyi gagal, tidak ada baris baru di `tblmasalahpicker`.
  4. Resi valid → baris hijau, bunyi sukses; muncul di Daftar Masalah Picker New sebagai SALAH AMBIL dengan `sku_salah` terisi dan picker terdeteksi; slip mencetak `(terambil <sku_salah>)`.
  5. Resi yang sama di-scan dua kali cepat → yang kedua ditolak "Sudah dilaporkan", hanya satu baris di DB.
  6. Ganti SKU → field terbuka, tabel sesi kosong.
