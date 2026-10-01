# Rekaman Video Packing (Scan Resi Packer Webcam)

Rilis awal 19 September 2026 (resolusi 1080p + VP9); kualitas rekaman
dinaikkan lagi 1 Oktober 2026 (lihat §7). Dokumen ini rujukan utama fitur
rekaman video packing — `HANDOFF.md` §2 dan `docs/ANALISIS_PROGRAM.md` hanya
menunjuk ke sini.

## 1. Tujuan fitur

**Masalah yang diselesaikan:** saat pelanggan komplain "barang yang diterima
salah/kurang/rusak", tim CS selama ini hanya punya kata packer vs. kata
pelanggan — tidak ada bukti objektif tentang apa yang sebenarnya dimasukkan
ke paket saat packing.

**Solusi:** setiap packing di menu **Scan Resi Packer (Webcam)** direkam
videonya dari saat resi pertama kali di-scan sampai packing selesai (scan
kedua). Rekaman disimpan di server dan bisa ditonton tim CS kapan saja lewat
nomor resi, **tanpa perlu melibatkan packer atau mengandalkan ingatannya**.

Kegunaan untuk CS saat menangani komplain:

- **Verifikasi isi paket** — tonton ulang proses packing untuk resi yang
  dikomplain, cocokkan dengan klaim pelanggan (barang kurang, salah SKU,
  salah warna/ukuran, dus sudah rusak sebelum dikirim, dll).
- **Bukti ke pelanggan** — hasil konversi MP4 (lihat §5.3) bisa diunduh dan
  dikirim lewat WhatsApp sebagai bukti kalau komplain pelanggan tidak sesuai
  rekaman (barang sebenarnya sudah benar/lengkap saat dikemas).
- **Bukti ke internal** — kalau rekaman memang menunjukkan kesalahan packer
  (salah ambil, salah hitung qty), jadi dasar objektif untuk pembinaan/KPI,
  bukan tuduhan sepihak.
- **Mempersempit titik masalah** — karena rekaman dimulai tepat di scan
  pertama (bukan dari awal shift), CS bisa langsung melompat ke proses
  packing resi yang dikomplain tanpa menonton rekaman panjang yang tidak
  relevan.

Fitur ini **tidak** merekam proses picking (pengambilan barang dari rak) —
hanya proses packing. Kalau masalahnya diduga terjadi di picking, rujuk ke
`docs/LOST_SCAN.md` dan proses KPI picker, bukan video ini.

## 2. Akses — masih terbatas webmaster (uji coba)

**Penting:** per migrasi di `MY_Controller::run_video_packing_migration()`,
menu **Video Packing** (CS) dan **Scan Resi Packer (Webcam)** (packer)
**sengaja hanya diberi hak akses ke roleid 1 (webmaster)** selama fitur masih
uji coba. Tim CS/packer biasa **belum** bisa membuka menu ini sampai
roleaccess-nya ditambahkan secara eksplisit dan `BOOTSTRAP_VERSI` dinaikkan.

Kalau user melaporkan menu ini tidak muncul di sidebar, itu bukan bug —
cek dulu apakah fiturnya memang sudah waktunya dibuka untuk role mereka.

## 3. Alur kerja packer (sisi perekaman)

```
packer buka menu Scan Resi Packer (Webcam)
            │
   kamera otomatis dibuka (panel kecil di pojok kanan bawah)
            │
   scan resi pertama kali ──► kelayakan resi dicek dulu (sudah di-packing?
            │                 dibatalkan? belum di-picker? resi tidak dikenal?)
            │                 jika tidak layak → DITOLAK, kamera TIDAK mulai rekam
            ▼
      REKAM MULAI (status MEREKAM) ── bilah kuning "MEREKAM mm:ss"
            │
   packer mem-packing barang secara fisik (kamera terus merekam)
            │
            ├──► scan resi sama lagi, < jeda minimum  → DITOLAK "Packing dulu!"
            │     (rekaman TETAP jalan, bukti utuh scan 1 s/d scan sah)
            │
            ├──► scan resi LAIN                        → DITOLAK "Resi X belum
            │                                              selesai" (rekaman jalan)
            │
            ├──► tombol "Batal Scan"                   → rekaman DIBUANG, resi
            │                                              kembali seperti belum
            │                                              pernah di-scan
            │
            └──► scan resi sama lagi, ≥ jeda minimum   → tersimpan ke tblpacking,
                  rekaman DIHENTIKAN & diunggah (status SELESAI)
```

Detail logika double-scan: `Packer::handle_double_scan_state_webcam()`
([application/controllers/Packer.php](../application/controllers/Packer.php)).

### 3.1 Kapan rekaman dianggap gagal/tidak lengkap

| Status di `tblvideopacking` | Artinya | Tetap bisa ditonton? |
|---|---|---|
| `MEREKAM` | Packing masih berlangsung saat ini | Ya, parsial |
| `SELESAI` | Ditutup normal oleh scan kedua yang sah | Ya, utuh |
| `TERPUTUS` | Tab packer tertutup / jaringan putus di tengah rekaman | Ya, sampai titik putus |
| `DIBATALKAN` | Packer menekan Batal Scan, atau potongan rusak sebelum sesi mulai | **Tidak** — berkas dihapus, baris jadi nisan |

- **Rekaman terputus** (tab mati, jaringan putus): kalau packer scan ulang
  resi yang sama setelah itu, rekaman **bagian baru** dibuat (bukan
  menyambung bagian lama — WebM dari dua sesi `MediaRecorder` tidak bisa
  disatukan). CS akan melihat lebih dari satu video untuk satu resi,
  berurutan menurut nomor bagian — tonton semuanya secara berurutan.
- **Potongan gagal terkirim** (jaringan lemah, antrean unggah menumpuk):
  video tersimpan sebagian saja, ditandai ke packer lewat popup "Video
  Terpotong". CS tetap bisa menonton bagian yang berhasil naik.
- **Batas durasi 90 menit**: pengaman untuk resi yang ditinggalkan (packer
  pindah kerjaan, lupa scan kedua) — rekaman dihentikan paksa dan resi
  otomatis ditutup sebagai packing selesai. Ini **bukan** batas kerja
  normal; packing ratusan/ribuan barang yang wajar memakan waktu lama tetap
  aman di bawah batas ini.

## 4. Spesifikasi teknis rekaman

| Setelan | Nilai | Keterangan |
|---|---|---|
| Resolusi | 1920×1080 (1080p), dipaksa lewat constraint `min`+`ideal` | Jatuh ke mode terbaik webcam (ditandai oranye di panel) kalau webcam tidak sanggup 1080p |
| Frame rate | 15 fps | Cukup untuk konten packing yang relatif statis |
| Codec | VP9 (fallback VP8 di browser lama) | WebM, tanpa perlu server tahu codec mana yang dipakai |
| Bitrate | VP9 8,0 Mbps / VP8 10,0 Mbps | Kualitas diprioritaskan di atas ukuran berkas (keputusan 1 Okt 2026) |
| Audio | **Tidak ada** | Sejak awal tidak pernah ada track audio — bukti packing cukup gambarnya |
| Potongan unggah | Tiap 2 detik | Supaya muat batas upload PHP dan bagian yang sudah naik tetap aman kalau tab ditutup mendadak |

Sumber: [assets/js/packer_video.js](../assets/js/packer_video.js). Riwayat
perubahan setelan bitrate/resolusi ada di
[docs/PANDUAN_PULL_PRODUKSI.md](PANDUAN_PULL_PRODUKSI.md) bagian **E** (rilis
1080p+VP9 pertama) dan **N** (kenaikan bitrate untuk kualitas maksimal).

## 5. Sisi CS — mencari dan menonton rekaman

### 5.1 Menu

**TIM CS → Video Packing** (`cs/video-packing`). Scan atau ketik nomor resi,
tekan Enter — daftar video untuk resi itu langsung tampil
([application/views/cs/video_packing.php](../application/views/cs/video_packing.php),
endpoint `Cs::get_video_packing()`).

- Resi dari arsip lama otomatis ditarik balik ke prod dulu lewat
  `pastikan_resi_live()` sebelum dicari — CS tidak perlu tahu resinya lama
  atau baru.
- Kalau satu resi punya lebih dari satu rekaman (karena rekaman sempat
  terputus — lihat §3.1), semuanya ditampilkan berurutan menurut nomor
  bagian, bukan terbaru dulu.
- Baris yang berkasnya hilang dari disk (mis. dihapus manual saat
  bersih-bersih) tidak ditampilkan, supaya player tidak gagal diam-diam.

### 5.2 Memutar video (WebM)

Video diputar langsung di halaman lewat `Cs::putar_video_packing()`, yang
mengalirkan isi berkas dari folder penyimpanan (di luar document root — lihat
§6) dengan dukungan `Range` request supaya bisa melompat ke tengah video
tanpa menunggu seluruh berkas terunduh. Login diperiksa `MY_Controller`,
jadi video hanya bisa diputar pengguna yang sudah masuk — tidak bisa diakses
langsung lewat URL tanpa login.

### 5.3 Menyiapkan MP4 untuk dikirim ke pelanggan

WebM tidak bisa diputar di iPhone dan tidak diterima WhatsApp sebagai video.
Tombol **Siapkan MP4** di halaman CS memicu konversi H.264 (~15 detik per
menit video):

1. CS tekan **Siapkan MP4** → permintaan masuk antrian (`mp4_status =
   ANTRI`), worker langsung dipicu di latar belakang supaya tidak menunggu
   putaran cron berikutnya (`Cs::minta_video_mp4()`).
2. Halaman CS memantau statusnya otomatis tiap beberapa detik
   (`get_video_packing`) sampai `mp4_status = SIAP`.
3. Tombol **Unduh MP4** muncul begitu siap (`Cs::unduh_video_mp4()`) — selalu
   diunduh sebagai attachment, karena tujuannya memang dikirim ke pelanggan.

MP4 hanya bisa disiapkan untuk rekaman yang sudah selesai (`SELESAI` /
`TERPUTUS`) — rekaman yang masih `MEREKAM` ditolak dengan pesan jelas.
Kalau proses konversi menunggu lebih dari 60 menit (cron mati / ffmpeg tidak
terpasang), ditandai "terlalu lama" ke CS.

## 6. Penyimpanan & siklus hidup berkas

- **Lokasi**: `C:/video-packing/` (bisa diubah lewat `video_packing_dir` di
  `secrets.php`, dipakai supaya folder dev dan produksi di PC yang sama tidak
  saling menimpa) — **sengaja di luar document root** supaya berkas besarnya
  tidak ikut ter-backup bersama kode dan tidak bisa diunduh langsung lewat
  URL tanpa login.
- **Nama berkas**: nomor resi apa adanya (karakter di luar huruf/angka/titik/
  strip/garis-bawah diganti `_`), bukan kode sesi acak — supaya CS/IT bisa
  menelusuri folder manual kalau perlu. Bagian 2+ (akibat rekaman terputus)
  mendapat akhiran `-2`, `-3`, dst.
- **Finalisasi otomatis** (`Cron::finalisasi_video`, task scheduler tiap
  menit): WebM mentah dari `MediaRecorder` tidak punya durasi/cues, jadi
  tombol seek di player bisa gagal untuk rekaman panjang. Cron me-remux
  kontainernya (`-c copy`, tanpa encode ulang) begitu status rekaman
  `SELESAI`/`TERPUTUS`.
- **Retensi**: dokumen ini tidak menetapkan kebijakan hapus otomatis —
  lihat `docs/PANDUAN_PULL_PRODUKSI.md` §N.1 soal kapasitas disk dan
  kebutuhan menyepakati kebijakan retensi manual (`C:\video-packing\` satu
  drive dengan MariaDB di produksi).
- **Pembatalan** (`Packer::batalkan_video_packing`): menghapus berkas dari
  disk dan menandai baris `DIBATALKAN` — bukan soft-delete biasa, berkasnya
  benar-benar hilang karena memang tidak merekam packing yang jadi (lihat
  §3, tombol Batal Scan).

## 7. Riwayat perubahan setelan kualitas

| Tanggal | Resolusi | Codec/Bitrate | Catatan |
|---|---|---|---|
| 15 Sep 2026 | 1280×720 | VP8 1,2 Mbps | Setelan awal, diturunkan dari 1080p karena kapasitas disk |
| 19 Sep 2026 | 1920×1080 (`ideal` saja) | VP9 2,0 Mbps / VP8 2,7 Mbps | Rilis 1080p+VP9 pertama |
| 1 Okt 2026 | 1920×1080 (`min`+`ideal`, dipaksa) | VP9 8,0 Mbps / VP8 10,0 Mbps | Kualitas diutamakan di atas ukuran berkas atas permintaan user |

Detail lengkap tiap rilis (termasuk estimasi kapasitas disk) ada di
`docs/PANDUAN_PULL_PRODUKSI.md` bagian **B.6**, **E**, dan **N**.

## 8. Rujukan kode

| Area | File |
|---|---|
| Perekam sisi browser (kamera, `MediaRecorder`, unggah bertahap) | [assets/js/packer_video.js](../assets/js/packer_video.js) |
| View menu packer | [application/views/packer/scan_packer_webcam.php](../application/views/packer/scan_packer_webcam.php) |
| Controller packer (double-scan, upload, batal, batas durasi) | [application/controllers/Packer.php](../application/controllers/Packer.php) |
| Model metadata rekaman | [application/models/Video_packing_fcd.php](../application/models/Video_packing_fcd.php) |
| Pembungkus ffmpeg (remux + konversi MP4) | [application/libraries/Video_ffmpeg.php](../application/libraries/Video_ffmpeg.php) |
| Controller CS (cari, putar, minta MP4, unduh) | [application/controllers/Cs.php](../application/controllers/Cs.php) |
| View menu CS | [application/views/cs/video_packing.php](../application/views/cs/video_packing.php) |
| Cron finalisasi (remux + antrian MP4) | [application/controllers/Cron.php](../application/controllers/Cron.php) |
| Migrasi tabel `tblvideopacking` + menu | `MY_Controller::run_video_packing_migration()` di [application/core/MY_Controller.php](../application/core/MY_Controller.php) |
