' sinkron_foto_sku_senyap.vbs - pembungkus agar `php index.php cron sinkron_foto_sku`
' jalan tanpa jendela konsol sama sekali. Dipakai Task Scheduler
' "IRESIS - Sinkron foto SKU" (harian 23.00, sejak 2026-09-21). wscript.exe adalah
' aplikasi GUI sehingga tidak membuka konsol; angka 0 = jendela php disembunyikan,
' True = tunggu sampai selesai supaya Task Scheduler tahu kapan putaran berakhir.
' Log putaran ditulis PHP ke application/logs/ (log_message) dan ke berkas di bawah.
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""C:\xampp\php\php.exe index.php cron sinkron_foto_sku >> C:\foto-produk\sinkron.log 2>&1""", 0, True)
