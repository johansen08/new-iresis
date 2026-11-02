# Cek Barang Gedung Baru (GD-BR)

Dibuat 2 November 2026. Dua menu anak **TIM MONITORING**:

| Menu | URI | Fungsi |
|---|---|---|
| Scan Resi GD-BR | `gdbr/scan` | Tandai resi gedung baru (tahap RESI), cek barang di gedung baru, cek ulang packer |
| Rekap Resi GD-BR | `gdbr/rekap` | Ringkasan, filter, tabel resi, detail per SKU, export CSV |

Kode: `controllers/Gdbr.php`, `models/Gdbr_fcd.php`, `views/gdbr/{scan,rekap,_umum}.php`,
`assets/css/gdbr.css`, migrasi `run_gdbr_migration()` di `core/MY_Controller.php`,
route `gdbr/*` di `config/routes.php`.

## 1. Alur

Satu resi melewati tiga scan, berurutan:

1. **RESI** (tim resi) menandai resi sebagai resi gedung baru. Tersimpan langsung saat discan
   (tanpa form). Resi yang sudah masuk **tidak bisa discan lagi**. Status awal di Rekap:
   *Belum dicek GD-BR*. Syarat: resi sudah ada di scan picker (`tblresiambilbarang`) dan bukan
   CANCELED / batal.
2. **GEDUNG BARU** (pegawai mana pun, bukan hanya HO) mencocokkan barang dengan resi.
   Status Rekap: *Menunggu packer*.
3. **PACKER** mencocokkan ulang sebelum dipacking dan melihat hasil cek gedung baru per SKU.
   Status Rekap: *Selesai dicek*.

Satu resi hanya bisa dicek satu kali per tahap. Tahap GEDUNG BARU menolak resi yang belum
ditandai tim resi; tahap PACKER menolak resi yang belum dicek gedung baru.

Hasil cek per tahap: **SESUAI**, **KURANG n**, **LEBIH n**, **SALAH n** (SKU di luar resi),
**LAINNYA** (kotak centang "masalah lain": rusak, cacat, kemasan). Catatan wajib bila ada yang
tidak sesuai. Qty resi selalu dibaca server dari `tbldetailprintresi`, bukan dari kiriman klien.

## 2. Petugas dan waktu

- Petugas selalu akun yang login. `tbluser.name` berbentuk `NAMA - ROLE - ABSEN`
  (mis. `GUNTUR - HO - 0236`), ditampilkan `0236 - GUNTUR - HO`. Disimpan sebagai teks
  (`petugas_absen/nama/role`) supaya rekap tetap benar walau nama akun berubah.
- Data picker (no absen, nama, role, tanggal diambil) dibaca dari `tblresiambilbarang` dan
  `tblpegawai.nama_pegawai` (`NAMA - ROLE - ABSEN`). Role picker bisa berbeda dari PICKER
  (perbantuan).
- **Waktu cek = waktu resi discan**: server mengirim `waktu_scan` saat scan, klien
  mengirimnya kembali saat simpan. Server menolak nilai yang di masa depan atau lebih tua dari
  6 jam (diganti waktu sekarang). Jam simpan tercatat terpisah di `created_at`.

## 3. Tabel (hanya ditambah, dibuat migrasi `2026-11-02.1`)

- `tblgdbr_resi`: satu baris per resi yang ditandai (`UNIQUE id_resi`).
- `tblgdbr_cek`: satu baris per tahap GEDUNG_BARU / PACKER (`UNIQUE id_gdbr, tahap`) beserta
  hitungan kurang/lebih/salah, `lain`, `sesuai`, catatan, petugas, `waktu_scan`.
- `tblgdbr_cek_item`: qty resi vs qty ada per SKU, termasuk SKU di luar resi (`di_luar_resi=1`).

Tidak ada `DELETE`/`UPDATE` dari kode ini. Tabel ini **belum** terdaftar di `config/arsip.php`
(belum diarsipkan dan tidak ikut purna).

## 4. Akses

Akses per role lewat `roleaccess`. Migrasi memberi role **1** (webmaster), **4** (QC/packer,
mis. Dewi) dan **5** (HO, mis. Guntur). Karena per role, semua akun aktif di role 4 dan 5 ikut
bisa membuka kedua menu. Persempit lewat halaman Access bila perlu. Endpoint POST memeriksa
`roleaccess` sendiri (sidebar saja tidak cukup).

## 5. Scan beruntun

Kolom scan dibersihkan dan difokuskan ulang setelah tiap scan. Scan yang datang saat request
sebelumnya belum selesai masuk antrean (tidak ada yang hilang). Rentetan ketikan cepat (pola
scanner, ≥ 8 karakter, jeda < 50 ms) atau tempel diproses otomatis tanpa Enter; ketikan manual
tetap perlu Enter. Riwayat 10 scan terakhir (termasuk yang ditolak) disimpan di
`sessionStorage`.

## 6. Uji

Diuji 2 Nov 2026 di browser (php -S pada worktree terpisah, DB `iresis_prod_uji`, tanpa menyentuh
produksi): login, ketiga tahap, resi dobel, resi tidak ada, tanpa scan picker, CANCELED, urutan
tahap, validasi server (catatan kosong, SKU tidak ada, SKU luar = SKU resi, qty negatif), akses
role tanpa hak (403), filter/cari/halaman/export di Rekap, drawer, foto, salin, dan lompatan
"Cek resi ini" dari Rekap ke Scan.
