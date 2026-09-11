# Jalankan script ini sebagai Administrator (klik kanan -> Run as administrator)
# Script ini memperbaiki 2 hal:
# 1. Update path WA Gateway ke new-iresis (yang benar)
# 2. Tambah task watchdog setiap 15 menit

# --- Fix 1: Update IRESIS - WA Gateway ---
$actionGW = New-ScheduledTaskAction `
    -Execute "C:\xampp\htdocs\new-iresis\start_wa_gateway.bat" `
    -WorkingDirectory "C:\xampp\htdocs\new-iresis\scratch\wa-gateway"

Set-ScheduledTask -TaskName "IRESIS - WA Gateway" -Action $actionGW
Write-Host "[OK] Task 'IRESIS - WA Gateway' diupdate ke path new-iresis" -ForegroundColor Green

# --- Fix 2: Buat IRESIS - WA Watchdog (tiap 15 menit) ---
$actionWD  = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\new-iresis\watchdog_wa_gateway.bat"
$triggerWD = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 15) -Once -At "00:00"
$settingsWD = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 2) -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName "IRESIS - WA Watchdog" `
    -Action $actionWD `
    -Trigger $triggerWD `
    -Settings $settingsWD `
    -RunLevel Highest `
    -Force

Write-Host "[OK] Task 'IRESIS - WA Watchdog' dibuat (tiap 15 menit)" -ForegroundColor Green
Write-Host ""
Write-Host "Selesai! WA Gateway sekarang:" -ForegroundColor Cyan
Write-Host "  - Auto-start saat login (task lama)" -ForegroundColor Cyan
Write-Host "  - Auto-restart jika mati (watchdog tiap 15 menit)" -ForegroundColor Cyan
