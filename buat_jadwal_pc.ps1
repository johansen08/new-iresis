# Jalankan sebagai Administrator

# Buat bat file untuk hibernate
$shutdownBat = "C:\xampp\htdocs\new-iresis\auto_shutdown.bat"
Set-Content $shutdownBat "@echo off`nshutdown /h"

# --- Task Shutdown 22:00 ---
$a1 = New-ScheduledTaskAction -Execute $shutdownBat
$t1 = New-ScheduledTaskTrigger -Daily -At "22:00"
$s1 = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 2)
Register-ScheduledTask -TaskName "IRESIS - Auto Shutdown 22:00" -Action $a1 -Trigger $t1 -Settings $s1 -RunLevel Highest -Force
Write-Host "OK: Shutdown 22:00" -ForegroundColor Green

# --- Task Wake 05:00 (WakeToRun pakai switch tanpa nilai) ---
$a2 = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c echo wake"
$t2 = New-ScheduledTaskTrigger -Daily -At "05:00"
$s2 = New-ScheduledTaskSettingsSet -WakeToRun -ExecutionTimeLimit (New-TimeSpan -Minutes 1)
Register-ScheduledTask -TaskName "IRESIS - Auto Wake 05:00" -Action $a2 -Trigger $t2 -Settings $s2 -RunLevel Highest -Force
Write-Host "OK: Wake 05:00" -ForegroundColor Green

# Aktifkan wake timer di power plan
powercfg /setacvalueindex SCHEME_CURRENT SUB_SLEEP RTCWAKE 1
powercfg /setactive SCHEME_CURRENT
Write-Host "OK: Wake timer aktif" -ForegroundColor Green

# Verifikasi
Write-Host ""
Get-ScheduledTask | Where-Object { $_.TaskName -match "IRESIS - Auto" } | Select-Object TaskName, State
Write-Host "`nSELESAI - PC akan hibernate jam 22:00 dan nyala jam 05:00" -ForegroundColor Cyan
