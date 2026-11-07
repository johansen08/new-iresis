# Terima Retur (TIM HO)

Menu **TIM HO → Terima Retur** (`terima-retur-ho`) untuk menerima paket retur secara
beruntun dengan alat scan. Satu layar, dua jenis retur, dan hanya dua bagian: pilih jenis,
lalu scan resi.

Menu lama **Scan Retur** dan **Scan Retur Komplain** (TIM RETUR) tidak diubah dan tetap jalan.

## Jenis retur

| Jenis | Arti | `tblresiretur.is_komplain` |
|---|---|---|
| Retur Ekspedisi | gagal kirim, paket dikembalikan kurir | `0` |
| Retur Komplain | pengembalian barang oleh pembeli | `1` |

Tidak ada jenis default: petugas wajib memilih (klik kartu, atau `Alt+1` / `Alt+2`). Scan
sebelum memilih jenis ditolak di layar dan tidak dikirim ke server. Mengganti jenis
mengosongkan banner hasil.

## Layar

- Kursor dikunci di kolom scan saat menu dibuka, dan kembali ke sana setelah klik di mana pun.
  Handler dilepas otomatis saat pindah menu (aplikasi SPA).
- Tidak ada tombol Terima. Alat scan mengirim Enter; kolom langsung dikosongkan dan siap
  ditimpa resi berikutnya.
- Request dikirim lewat **antrian berurutan** (satu per satu, timeout 15 detik), jadi 20 paket
  berturut-turut tidak saling tumpang tindih. Banner dan bunyi tiap resi muncul saat jawabannya tiba.
- Bagian bawah: **5 scan terbaru** (hanya di memori halaman, hilang saat halaman ditutup).

## Aturan terima (`Terima_retur_ho_fcd::terima`)

Urutan pengecekan, dan kode hasil yang dikembalikan:

1. Jenis tidak valid → `jenis_belum_dipilih`. Resi kosong → `kosong`.
2. `resolusi_noresi_retur()` memetakan resi balik kurir (mis. `JZ…`) ke resi pengiriman Iresis
   lewat `tblreturjubelio` → `tbldetailprintresi` (hanya jika memetakan ke tepat satu resi),
   sekaligus menarik resi lama dari arsip. Resi balik ditampilkan di banner.
3. Resi tidak ada di `tblprintresi` → `tidak_ditemukan`.
4. `status_pesanan` mengandung `CANCEL` atau `batal = 1` → `cancel`.
5. Sudah ada baris pada jenis yang sama → `double`.
6. Sudah ada baris pada jenis sebelah → `salah_jenis` (pesan menyebut jenis yang benar).
7. Selain itu INSERT → `ok`.

Langkah 5–7 dijaga `GET_LOCK('terima_retur_<id_printresi>')`. `tblresiretur` tidak punya unique
key `(id_resi, is_komplain)`, jadi tanpa kunci dua scan resi yang sama yang tiba bersamaan bisa
sama-sama lolos cek dan menghasilkan dua baris.

Hal yang sengaja **tidak** ada dibanding menu lama: mode Update (reset baris + hapus
`tblbukaretur`) dan penghitung harian yang tidak memisahkan jenis.

## Data yang disimpan

Satu baris `tblresiretur` per resi diterima:

| Kolom | Isi |
|---|---|
| `id_resi` | `tblprintresi.id_printresi` |
| `noresi` | resi pengiriman Iresis (setelah pemetaan resi balik) |
| `tanggal_resiretur` | waktu scan |
| `status_retur` | `Terima Retur` |
| `status_detail` | `NULL` (diisi saat Buka Retur) |
| `sudah_cetak` | string kosong |
| `id_kurir`, `id_marketplace` | disalin dari `tblprintresi` |
| `id_pegawai` | `id_user` yang login |
| `is_komplain` | `0` Ekspedisi / `1` Komplain |

`is_update` dan `is_shipped_retur` tidak diisi (default `0`). Tidak ada tabel atau kolom baru,
sehingga Buka Retur, laporan retur, dan rekonsiliasi yang sudah ada membaca baris ini apa adanya.

## Suara

Semua memakai berkas bawaan proyek (`assets/audio/`, elemen `<audio>` di `views/main.php`) lewat
`suaraScan()`, yang memutar dari awal dan memotong sesuai durasi:

| Hasil | Suara |
|---|---|
| `ok` | `audio-alexis` |
| `double` | `audio-sudah-scan` |
| `cancel` | `audio-cancel-order` |
| `tidak_ditemukan` | `audio-tidak-ditemukan` |
| `salah_jenis`, `jenis_belum_dipilih`, `gagal`, `ditolak` | `audio-wrong` |

Suara selalu aktif, tidak ada tombol mute.

## Menu dan hak akses

`run_terima_retur_ho_migration()` (`core/MY_Controller.php`, versi bootstrap `2026-11-07.1`)
membuat menu anak TIM HO (induk mengikuti **Lost Scan HO**, urutan 5). Hak akses disalin dari
**Lost Scan HO** ditambah role `1` dan `5` (HO); baris `roleaccess` yang ada tidak diubah.
Endpoint POST memeriksa ulang akses ke menu (sidebar saja tidak cukup). Persempit lewat halaman
Access bila perlu.

Mode Arsip: scan ditolak dengan pesan "Mode Arsip aktif" (basis data arsip baca-saja).

## Belum ditangani

Resi balik pembeli yang belum ada di `tblreturjubelio` (upload Excel retur Jubelio, tidak
selalu rutin harian) tetap berakhir **tidak ditemukan**. Jalur cadangan (cari lewat
`tblcs_complain.no_resi` / `no_pesanan`, atau input manual) butuh keputusan desain data tersendiri.

## Berkas

`controllers/Terima_retur_ho.php`, `models/Terima_retur_ho_fcd.php`,
`views/terima_retur_ho/index.php`, `assets/css/terima_retur_ho.css`, route di
`config/routes.php`, migrasi menu di `core/MY_Controller.php`.
