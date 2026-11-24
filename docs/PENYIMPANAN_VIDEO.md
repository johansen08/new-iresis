# Penyimpanan Video (Tim Monitoring)

Menu `monitoring/penyimpanan-video` memantau HDD backup tempat rekaman video packing disimpan (`video_packing_dir` di `secrets.php`, saat ini `D:/video-packing/`).

- **Rekap per tanggal**: dari `tblvideopacking` (status selain `DIBATALKAN`), dikelompokkan `DATE(mulai_at)`: total video, `ukuran_byte` (WebM), `mp4_ukuran_byte`, total, rata-rata per video, kumulatif. Tidak memindai berkas di disk.
- **Kartu atas**: total penyimpanan terpakai (jumlah ukuran video), space tersisa + kapasitas drive (`disk_free_space`/`disk_total_space`), dan estimasi penuh.
- **Estimasi penuh**: sisa ruang ÷ rata-rata byte/hari dari 14 hari terakhir (hari ini tidak ikut, belum selesai; hari tanpa rekaman ikut dihitung). Alert merah ≤ 30 hari, kuning ≤ 90 hari, hijau selebihnya. Ambang ada di konstanta `Penyimpanan_video_fcd`.
- Drive tidak terbaca (HDD belum terpasang) → alert merah.
- Hanya webmaster (role 1): menu disembunyikan dari sidebar role lain (`MY_Controller::ambil_menu_tree`) dan `Monitoring::penyimpanan_video[_data]` menolak role selain 1. Tampilan mengikuti Retur → Daftar Retur Penjualan.
- File: `Monitoring::penyimpanan_video[_data]`, `models/Penyimpanan_video_fcd.php`, `views/monitoring/penyimpanan_video.php`.
