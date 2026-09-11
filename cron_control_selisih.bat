@echo off
cd /d C:\xampp\htdocs\new-iresis
C:\xampp\php\php.exe index.php cron control_pengiriman >> logs\cron_control_pengiriman.log 2>&1
C:\xampp\php\php.exe index.php cron selisih_paket >> logs\cron_selisih_paket.log 2>&1
