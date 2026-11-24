' penjualan_pesanan_senyap.vbs - pembungkus agar scripts/auto_upload_penjualan_pesanan.py jalan tanpa
' jendela konsol. Dipakai Task Scheduler "IRESIS - Upload penjualan pesanan 3 bulan" (tiap 22.00).
' Mengunduh Daftar Penjualan (Pesanan) Jubelio 90 hari terakhir per potongan 7 hari lalu mengunggahnya ke
' IRESIS (cron/auto_upload_resi) untuk memperbarui status pesanan + tanggal/waktu retur.
' Hasil tiap run: logs\riwayat_penjualan_pesanan.csv (BERHASIL/GAGAL + alasan + saran),
' logs\riwayat_penjualan_pesanan_potongan.csv (per potongan) dan logs\auto_upload_penjualan_pesanan.log (rinci).
' Exit code 1 = gagal (terlihat di Task Scheduler). Butuh scripts\jubelio_credentials.py dan
' scripts\secrets_local.py (keduanya di-gitignore). Panduan: docs\AUTO_UPLOAD_PENJUALAN_PESANAN.md
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""set PYTHONIOENCODING=utf-8&& C:\Users\user\AppData\Local\Programs\Python\Python313\python.exe scripts\auto_upload_penjualan_pesanan.py >> C:\xampp\htdocs\new-iresis\logs\auto_upload_penjualan_pesanan_stdout.log 2>&1""", 0, True)
