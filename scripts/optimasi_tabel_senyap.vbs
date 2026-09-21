' optimasi_tabel_senyap.vbs - OPTIMIZE TABLE tabel-tabel besar iresis_prod tanpa jendela.
' Dipakai Task Scheduler "IRESIS - Optimasi tabel" (22.30 tanggal 1 tiap bulan;
' pertama kali 21 Sep 2026 setelah purna pertama). Mengembalikan ruang disk yang
' dilepas purna (DELETE tidak mengecilkan berkas .ibd). Cron::optimasi_tabel
' menolak jalan di jam kerja 07-22. Log: logs\optimasi_tabel.log dan application\logs\.
Dim sh: Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\xampp\htdocs\new-iresis"
WScript.Quit sh.Run("cmd.exe /c ""C:\xampp\php\php.exe index.php cron optimasi_tabel >> C:\xampp\htdocs\new-iresis\logs\optimasi_tabel.log 2>&1""", 0, True)
