@echo off
echo [%date% %time%] Menjalankan cron rekap_target_wa...
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron rekap_target_wa >> C:\xampp\htdocs\new-iresis\logs\cron_laporan_sore.log 2>&1
echo [%date% %time%] Selesai.
