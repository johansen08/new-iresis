' arsip_harian_senyap.vbs - pembungkus agar `php index.php cron arsip_harian`
' jalan tanpa jendela konsol sama sekali. Dipakai Task Scheduler
' "IRESIS - Arsip harian" (tiap 01.00, sejak 2026-09-21). wscript.exe adalah
' aplikasi GUI sehingga tidak membuka konsol; angka 0 = jendela php disembunyikan,
' True = tunggu sampai selesai supaya Task Scheduler tahu kapan putaran berakhir.
' Log putaran ditulis PHP ke application/logs/ (log_message) dan ke berkas di bawah;
' jejak per tabel ada di iresis_arsip._arsip_log dan _arsip_status.
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""C:\xampp\php\php.exe index.php cron arsip_harian >> C:\xampp\htdocs\new-iresis\logs\arsip_harian.log 2>&1""", 0, True)
