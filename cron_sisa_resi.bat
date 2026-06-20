@echo off
echo [%date% %time%] Menjalankan cron sisa_resi...
C:\xampp\php\php.exe C:\xampp\htdocs\new-iresis\index.php cron sisa_resi >> C:\xampp\htdocs\new-iresis\logs\cron_sisa_resi.log 2>&1
echo [%date% %time%] Selesai.
