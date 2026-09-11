@echo off
cd /d C:\xampp\htdocs\new-iresis
C:\xampp\php\php.exe index.php cron auto_sisa_resi >> logs\cron_auto_sisa_resi.log 2>&1
