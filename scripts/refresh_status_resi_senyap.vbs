' refresh_status_resi_senyap.vbs - pembungkus agar scripts/refresh_status_resi.py jalan
' tanpa jendela konsol. Dipakai Task Scheduler "IRESIS - Refresh status resi" (tiap 03.00).
' Semua mode (harian, mingguan, backlog) dijalankan berurutan; 0 = jendela disembunyikan,
' True = tunggu sampai selesai. Log: logs\refresh_status_resi.log (selalu ditambah, tidak ditimpa).
' Butuh scripts\jubelio_credentials.py dan scripts\secrets_local.py (keduanya di-gitignore;
' kalau aplikasi bukan di port 80, isi IRESIS_BASE_URL di secrets_local.py).
' Rancangan: docs\RANCANGAN_REFRESH_STATUS_RESI.md
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""set PYTHONIOENCODING=utf-8&& C:\Users\user\AppData\Local\Programs\Python\Python313\python.exe scripts\refresh_status_resi.py >> C:\xampp\htdocs\new-iresis\logs\refresh_status_resi.log 2>&1""", 0, True)
