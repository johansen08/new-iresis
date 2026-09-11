# Jalankan sebagai Administrator
# Otomatis: shutdown jam 22:00, nyala jam 05:00

# --- TASK 1: Shutdown jam 22:00 setiap hari ---
# /h = hibernate, /f = paksa tutup aplikasi
# Ganti /h dengan /s jika ingin shutdown penuh (tapi wake timer tidak akan bekerja)
$actionShutdown = New-ScheduledTaskAction -Execute "shutdown.exe" -Argument "/h /f"
$triggerShutdown = New-ScheduledTaskTrigger -Daily -At "22:00"
$settingsShutdown = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 2) `
    -MultipleInstances IgnoreNew `
    -WakeToRun $false

Register-ScheduledTask `
    -TaskName "IRESIS - Auto Shutdown 22:00" `
    -Action $actionShutdown `
    -Trigger $triggerShutdown `
    -Settings $settingsShutdown `
    -RunLevel Highest `
    -Force

Write-Host "[OK] Task shutdown jam 22:00 dibuat" -ForegroundColor Green

# --- TASK 2: Wake (nyalakan) jam 05:00 setiap hari ---
# Task ini menggunakan Wake Timer bawaan Windows
$actionWake = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c echo PC dinyalakan otomatis jam 05:00"
$triggerWake = New-ScheduledTaskTrigger -Daily -At "05:00"
$settingsWake = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 1) `
    -MultipleInstances IgnoreNew `
    -WakeToRun $true   # <-- ini yang menyalakan PC dari sleep/hibernate

Register-ScheduledTask `
    -TaskName "IRESIS - Auto Wake 05:00" `
    -Action $actionWake `
    -Trigger $triggerWake `
    -Settings $settingsWake `
    -RunLevel Highest `
    -Force

Write-Host "[OK] Task wake jam 05:00 dibuat (WakeToRun aktif)" -ForegroundColor Green

# --- Aktifkan Wake Timers di Power Plan ---
# Tanpa ini, WakeToRun tidak akan berfungsi
powercfg /setacvalueindex SCHEME_CURRENT SUB_SLEEP RTCWAKE 1
powercfg /setdcvalueindex SCHEME_CURRENT SUB_SLEEP RTCWAKE 1
powercfg /setactive SCHEME_CURRENT

Write-Host "[OK] Wake Timers diaktifkan di power plan" -ForegroundColor Green
Write-Host ""
Write-Host "Selesai! Jadwal PC:" -ForegroundColor Cyan
Write-Host "  - Hibernate otomatis: 22:00 (PC masuk hibernate)" -ForegroundColor Cyan
Write-Host "  - Nyala otomatis    : 05:00 (dari kondisi Sleep/Hibernate)" -ForegroundColor Cyan
Write-Host ""
Write-Host "PENTING: Wake jam 05:00 hanya bekerja jika PC dalam kondisi" -ForegroundColor Yellow
Write-Host "Sleep atau Hibernate (bukan dimatikan total / power off)." -ForegroundColor Yellow
Write-Host "Pastikan saat shutdown jam 22:00, PC masuk ke Hibernate bukan Power Off." -ForegroundColor Yellow
