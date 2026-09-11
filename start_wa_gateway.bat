@echo off
REM Start IRESIS WhatsApp Gateway (new-iresis)
cd /d C:\xampp\htdocs\new-iresis\scratch\wa-gateway
node server.js >> C:\xampp\htdocs\new-iresis\logs\wa_gateway.log 2>&1
