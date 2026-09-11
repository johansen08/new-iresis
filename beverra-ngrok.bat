@echo off
title BEVERRA Ngrok Service
set NGROK_PORT=8080

echo ==========================================
echo    BEVERRA NGROK STABILITY CONTROL
echo ==========================================
echo Starting Ngrok on port %NGROK_PORT%...
echo Location: %~dp0ngrok.exe
echo Press CTRL+C to stop.
echo.

if not exist "%~dp0ngrok.exe" (
    echo ERROR: ngrok.exe not found in this directory!
    pause
    exit
)

:loop
echo [%date% %time%] Starting Ngrok tunnel...
"%~dp0ngrok.exe" http %NGROK_PORT%
echo.
echo [%date% %time%] Ngrok has stopped or crashed. Restarting in 5 seconds...
timeout /t 5
goto loop
