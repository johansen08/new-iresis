' segarkan_daftar_retur_senyap.vbs - pembungkus agar `php index.php cron segarkan_daftar_retur`
' jalan tanpa jendela konsol. Dipakai Task Scheduler "IRESIS - Segarkan daftar retur" (tiap 2 menit).
' Membangun ulang cache menu Daftar Retur Penjualan di application/cache supaya user tidak menunggu
' hitung ulang; hanya membaca DB dan menulis berkas cache (tidak menulis ke tabel apa pun).
' wscript.exe adalah aplikasi GUI sehingga tidak membuka konsol; angka 0 = jendela php disembunyikan,
' True = tunggu sampai selesai supaya putaran berikutnya tidak menumpuk.
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""C:\xampp\php\php.exe index.php cron segarkan_daftar_retur""", 0, True)
