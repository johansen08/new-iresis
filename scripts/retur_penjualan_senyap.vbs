' retur_penjualan_senyap.vbs - pembungkus agar scripts/auto_upload_retur_penjualan.py jalan tanpa
' jendela konsol. Dipakai Task Scheduler "IRESIS - Upload retur penjualan" (06.00, 12.00, 20.00).
' Mengunduh Daftar Retur Penjualan Jubelio (1 Jan s/d hari ini) lalu mengunggahnya ke IRESIS.
' Hasil tiap run: logs\riwayat_retur_penjualan.csv (BERHASIL/GAGAL + alasan + saran) dan
' logs\auto_upload_retur_penjualan.log (rinci). Exit code 1 = gagal (terlihat di Task Scheduler).
' Butuh scripts\jubelio_credentials.py dan scripts\secrets_local.py (keduanya di-gitignore).
' Panduan: docs\AUTO_UPLOAD_RETUR_PENJUALAN.md
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""set PYTHONIOENCODING=utf-8&& C:\Users\user\AppData\Local\Programs\Python\Python313\python.exe scripts\auto_upload_retur_penjualan.py >> C:\xampp\htdocs\new-iresis\logs\auto_upload_retur_penjualan_stdout.log 2>&1""", 0, True)
