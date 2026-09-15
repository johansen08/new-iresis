@echo off
echo [%date% %time%] Menjalankan cron finalisasi_video...
if not exist C:\xampp\htdocs\new-iresis\logs mkdir C:\xampp\htdocs\new-iresis\logs
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron finalisasi_video >> C:\xampp\htdocs\new-iresis\logs\cron_finalisasi_video.log 2>&1
echo [%date% %time%] Selesai.
