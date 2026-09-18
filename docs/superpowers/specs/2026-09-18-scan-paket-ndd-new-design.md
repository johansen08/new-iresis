# Desain: Scan Paket NDD New — pencatatan Lost Scan Packer langsung dari halaman scan

Tanggal: 2026-09-18
Status: disetujui (brainstorming bersama user)

## Latar belakang

Di menu **TIM HO → Scan Paket NDD** (`scan_logistic`), saat petugas HO men-scan
resi yang belum di-packing, server menolak dengan `NOT_PACKED`. Situasi ini
hampir selalu berarti packer lupa scan (lost scan). Petugas lalu harus pindah
ke menu **Lost Scan Packer/Picker**, mengetik ulang resi, memilih packer, dan
kembali lagi ke halaman scan. Bolak-balik ini memutus antrean scan dan rawan
salah ketik.

## Tujuan

Halaman scan baru yang, saat `NOT_PACKED`, langsung menampilkan field pilih
packer di bawah pemberitahuan dan menyimpan catatan lost scan tanpa pindah
menu. Halaman ini berdiri sendiri dari menu lama supaya kalau bermasalah,
petugas tetap bisa memakai **Scan Paket NDD** yang lama.

## Keputusan desain

- **Hanya mencatat** ke `tbllostscanpacker` (pilihan A). Tidak membuat baris
  `tblpacking`, tidak meloloskan resi ke HO. Resi di-scan ulang setelah packer
  menyelesaikan packing — alur sama seperti sekarang. Alternatif B (loloskan
  HO tanpa packing) ditolak karena merusak konsistensi laporan; alternatif C
  (buatkan baris packing atas nama packer) ditunda karena menyangkut KPI dan
  efek samping `Packer_fcd::save_packer()`.
- Panel lost scan muncul di **kedua mode** (REGULER dan HO+NDD).
- Tidak ada perubahan skema tabel. Kolom `nama_packer` tetap diisi
  `tblpegawai.nama_pegawai` agar Laporan Lost Scan lama tetap kompatibel.
- Tidak ada satu pun file lama yang diubah (`Scan_logistic.php`,
  `scan_logistic/scan_view.php`, `Lost_scan_packer.php`, kedua modelnya).
  Model lama dipakai apa adanya.

## Komponen

| File | Peran |
|---|---|
| `application/controllers/Scan_paket_ndd_new.php` | `index()` halaman scan; `save()` delegasi ke `Scan_logistic_fcd::save_scan()` (termasuk rincian waktu `srv_ms`/`boot_ms`); `cek_lost_scan()`; `simpan_lost_scan()` delegasi ke `Lost_scan_packer_fcd::save()` dengan `lost_type = PACKER` |
| `application/models/Scan_paket_ndd_new_fcd.php` | Query baca saja: `daftar_packer()` — `tbluser` role 4 (client packer) aktif join `tblpegawai` lewat `id_pegawai`, urut nama; `cari_lost_scan($noresi)` — baris `tbllostscanpacker` terakhir untuk resi itu + nama pelapor dari `tbluser` |
| `application/views/scan_paket_ndd_new/index.php` | Turunan `scan_view.php` + panel lost scan; header/footer diberi label NEW |
| `application/config/routes.php` | `scan-paket-ndd-new`, `scan-paket-ndd-new/save`, `scan-paket-ndd-new/cek-lost-scan`, `scan-paket-ndd-new/simpan-lost-scan` |
| `application/core/MY_Controller.php` | `run_scan_paket_ndd_new_migration()`: menu "Scan Paket NDD New" di TIM HO (`parentid` 27, `sortorder` menu lama + 1, icon sama), `roleaccess` untuk role 1, 2, 5 sebagai daftar tetap; `BOOTSTRAP_VERSI` dinaikkan |

## Alur

1. Petugas scan resi → `POST scan-paket-ndd-new/save` → respons sama persis
   dengan menu lama (kode 201 sukses, 4xx dengan `EXCEPTION_CODE`).
2. Jika `EXCEPTION_CODE = NOT_PACKED`: JS memanggil
   `POST scan-paket-ndd-new/cek-lost-scan {noresi}`.
   - Belum pernah dicatat → panel mode **simpan**: resi (readonly, tampil
     besar), dropdown packer (bootstrap-select live-search, terfokus
     otomatis), tombol **Simpan Lost Scan**.
   - Sudah dicatat → panel mode **info**: "Sudah dicatat lost scan → NAMA
     PACKER, oleh PELAPOR, HH:MM" + tombol Tutup.
   - Cek gagal (jaringan) → panel mode simpan; cek hanya informatif.
3. Simpan → `POST scan-paket-ndd-new/simpan-lost-scan {noresi, nama_petugas}`
   → `Lost_scan_packer_fcd::save()`; `created_by` = petugas HO.
   - 201: noty hijau, baris riwayat "LOST SCAN DICATAT → NAMA PACKER", panel
     tutup, fokus ke `#noresi`.
   - 400 (model kembalikan -1, dobel): panel berubah ke mode info.
4. Resi tidak masuk `tblresikeluar`/`tblscan_ndd`; counter tidak berubah.

## Perilaku panel

- Panel terikat ke satu `noresi`. Antrean scan tetap berjalan; scan resi lain
  hanya mengganti kartu status, panel tetap ada sampai disimpan/ditutup.
  `NOT_PACKED` untuk resi yang sama tidak membuat panel ulang; `NOT_PACKED`
  untuk resi lain mengganti isi panel ke resi terbaru.
- Enter di dropdown = simpan; Esc = tutup & fokus ke `#noresi`.
- Aturan halaman lama "klik di mana pun → fokus ke input resi" dikecualikan
  untuk area panel supaya dropdown bisa dipakai.
- Dropdown kosong (tidak ada akun packer aktif) → peringatan + tautan ke menu
  Lost Scan lama.

## Penanganan error

- Semua respons AJAX lewat `make_ajax_response()`.
- Validasi packer kosong di klien dan server (400).
- Pengecekan hak akses menu mengikuti mekanisme `roleaccess` yang ada; tidak
  ada penjaga role tambahan di controller (sama seperti menu lama).

## Pengujian (manual)

1. `php -l` untuk semua file PHP baru/diubah.
2. Buka menu baru: migrasi jalan sekali (`bootstrap_migrasi.txt` berubah),
   menu tampil untuk role webmaster/admin/ho.
3. Resi belum packing di mode REGULER dan HO+NDD → panel simpan → baris muncul
   di `tbllostscanpacker` dan di Laporan Lost Scan lama.
4. Resi yang sama lagi → panel mode info.
5. Scan resi normal saat panel terbuka → scan diproses, panel tetap.
6. Menu lama `scan_logistic` diuji sekali; tidak berubah.
7. Data dummy: resi berawalan `DUMMY-NDDNEW-` di `tblprintresi` +
   `tblresiambilbarang` (sudah picker, belum packing) untuk uji coba, dibuat
   lewat script di `dev_tools/` (tidak di-commit).

## Tambahan tahap C (18 Sep 2026, disetujui)

Panel packer juga muncul saat scan ditolak **`NOT_PICKED`**: packer tetap
wajib dipilih (packer menembus penjaga belum-picker di menunya, jadi ikut
lost scan) dan dicatat sebagai `tbllostscanpacker` PACKER. Saat disimpan,
controller mengirim `belum_picker=1` dan server sekaligus memanggil
`Lost_scan_picker_fcd::lapor($noresi, 'HO', $user)` -- resi masuk antrean
**TIM PICKER → Laporan Lost Scan Picker** (spec
`2026-09-18-lost-scan-picker-tahap-a-design.md`). Petugas HO tidak memilih
picker; tim picker yang menentukan. Urutan selanjutnya dijaga penjaga yang
ada: tim picker Tambahkan Picker → packer scan ulang → HO scan ulang.

`cek-lost-scan` ikut mengembalikan `antrean_picker` (pelapor, sumber,
waktu) agar panel menampilkan "sudah dilaporkan, menunggu tim picker" dan
tidak melapor ganda (`lapor()` juga menolak PENDING ganda).
