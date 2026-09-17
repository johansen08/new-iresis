# scripts/arsip — script sekali-pakai yang sudah selesai tugasnya

Disimpan untuk rujukan; **tidak dipakai operasional** dan tidak dijamin masih
jalan apa adanya (yang Python mengimpor `secrets_local` dari `scripts/`, jadi
salin dulu ke `scripts/` kalau mau dijalankan lagi).

| File | Dulu untuk | Status |
|---|---|---|
| `sniff_jubelio.py` | Merekam request Jubelio saat reverse-engineer API retur (Jul 2026) | Hasilnya sudah dipakai `auto_upload_retur_jubelio.py`; output lama ada di `C:\xampp\arsip-new-iresis\sniff_out-jubelio-2026-07\` (berisi token, jangan dibagikan) |
| `jubelio_api_probe.py` | Uji akses Data API Jubelio v2 untuk retur penjualan | Selesai |
| `fix_resi_detail_20260704.py` | Perbaikan sekali-jalan qty/no_rak yang rusak oleh run `auto_upload_resi_api` 4 Jul 2026 (endpoint `cron/fix_resi_detail` masih ada) | Sudah dijalankan |
| `import_komplain.py` | Impor Excel "LAPORAN KOMPLAIN JUNI 2026" ke `tblcs_complain` (path hardcode ke Downloads user lain) | Sudah dijalankan |
| `beverra-siresi-windows.bat`, `beverra-siresi-linux.sh` | Launcher Chrome ke URL proyek generasi sebelumnya (`siresi-v1.0.0`, `192.168.1.117:8080/siresi-new`) | Kedaluwarsa |
| `buat_jadwal_pc.ps1`, `setup_pc_schedule.ps1`, `task_wake_0500.xml` | Mendaftarkan task Windows "IRESIS - Auto Shutdown 22:00" / "Auto Wake 05:00" (Jun 2026) | Task sudah terdaftar; `auto_shutdown.bat` sengaja tetap di root karena dirujuk task-nya |

Script yang **masih aktif** tetap di `scripts/` (`auto_upload_*.py`) dan di root
(`cron_finalisasi_video.bat`, `cron_tutup_video_menggantung.bat`,
`setup_task_finalisasi_video.ps1`, `auto_shutdown.bat`) — jangan dipindah,
Task Scheduler menunjuk path absolutnya.
