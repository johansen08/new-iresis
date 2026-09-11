@echo off
cd /d C:\xampp\htdocs\new-iresis
C:\xampp\php\php.exe index.php cron auto_paket_keluar >> logs\cron_auto_paket_keluar.log 2>&1
