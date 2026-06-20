@echo off
echo [%date% %time%] Menjalankan cron rts_check...
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron rts_check >> C:\xampp\htdocs\new-iresis\logs\cron_rts_check.log 2>&1
echo [%date% %time%] Selesai.
