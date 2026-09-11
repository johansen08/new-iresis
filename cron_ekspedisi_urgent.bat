@echo off
cd /d C:\xampp\htdocs\new-iresis
C:\xampp\php\php.exe index.php cron ekspedisi_urgent >> logs\cron_ekspedisi_urgent.log 2>&1
