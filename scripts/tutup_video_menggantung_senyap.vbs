' tutup_video_menggantung_senyap.vbs - pembungkus agar `php index.php cron tutup_video_menggantung`
' jalan tanpa jendela konsol. Dipakai Task Scheduler "IRESIS - Tutup video menggantung" (tiap 15 menit).
' Hanya mengubah label rekaman MEREKAM yang lebih dari 120 menit menjadi TERPUTUS; berkas video tidak disentuh.
' Log: logs\cron_tutup_video_menggantung.log
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""C:\xampp\php\php.exe index.php cron tutup_video_menggantung >> logs\cron_tutup_video_menggantung.log 2>&1""", 0, True)
