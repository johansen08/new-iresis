@echo off
echo [%date% %time%] Menjalankan cron tutup_video_menggantung...
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron tutup_video_menggantung >> C:\xampp\htdocs\new-iresis\logs\cron_tutup_video_menggantung.log 2>&1
echo [%date% %time%] Selesai.
