@echo off
echo [%date% %time%] Menjalankan cron paket_keluar...
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron paket_keluar >> C:\xampp\htdocs\new-iresis\logs\cron_paket_keluar.log 2>&1
echo [%date% %time%] Selesai.
