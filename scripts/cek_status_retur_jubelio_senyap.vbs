' cek_status_retur_jubelio_senyap.vbs - pembungkus agar scripts/cek_status_retur_jubelio.py jalan tanpa
' jendela konsol. Dipakai Task Scheduler "IRESIS - Cek status retur Jubelio" (06.30, 11.30, 20.30).
' Mengecek status Jubelio pesanan di tab Belum Terima & Sudah Verifikasi (Daftar Retur Penjualan); yang ternyata
' COMPLETED diubah jadi 'SELESAI (PESANAN COMPLETED)' (tblreturjubelio) dan status resi COMPLETED (tblprintresi).
' Hasil tiap run: logs\riwayat_cek_status_retur.csv (BERHASIL/GAGAL + alasan + saran) dan
' logs\cek_status_retur_jubelio.log (rinci). Exit code 1 = gagal (terlihat di Task Scheduler).
' Butuh scripts\jubelio_credentials.py dan scripts\secrets_local.py (keduanya di-gitignore).
' Panduan: docs\CEK_STATUS_RETUR_JUBELIO.md
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""set PYTHONIOENCODING=utf-8&& C:\Users\user\AppData\Local\Programs\Python\Python313\python.exe scripts\cek_status_retur_jubelio.py >> C:\xampp\htdocs\new-iresis\logs\cek_status_retur_jubelio_stdout.log 2>&1""", 0, True)
