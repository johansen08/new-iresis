@echo off
REM Watchdog: cek port 3000, restart WA gateway jika mati

netstat -ano | findstr ":3000 " | findstr "LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo %DATE% %TIME% [watchdog] WA Gateway OK - port 3000 aktif >> C:\xampp\htdocs\new-iresis\logs\wa_gateway_watchdog.log
    exit /b 0
)

echo %DATE% %TIME% [watchdog] WA Gateway MATI - restart... >> C:\xampp\htdocs\new-iresis\logs\wa_gateway_watchdog.log
cd /d C:\xampp\htdocs\new-iresis\scratch\wa-gateway
start "" /B node server.js >> C:\xampp\htdocs\new-iresis\logs\wa_gateway.log 2>&1
echo %DATE% %TIME% [watchdog] Restart dilakukan >> C:\xampp\htdocs\new-iresis\logs\wa_gateway_watchdog.log
