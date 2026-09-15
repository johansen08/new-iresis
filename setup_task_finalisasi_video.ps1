# Jalankan script ini sebagai Administrator (klik kanan PowerShell -> Run as administrator):
#   cd C:\xampp\htdocs\new-iresis
#   powershell -ExecutionPolicy Bypass -File .\setup_task_finalisasi_video.ps1
#
# Mendaftarkan task "IRESIS - Finalisasi Video" yang menjalankan
# cron_finalisasi_video.bat tiap 1 menit (remux WebM + antrian MP4).
#
# Task jalan sebagai SYSTEM (LogonType ServiceAccount): tidak ada jendela cmd
# yang berkedip tiap menit di layar PC produksi, dan task tetap jalan walau
# belum ada user yang login (misalnya setelah restart Windows Update).
# Mendaftarkan task atas nama SYSTEM memang butuh hak Administrator.
#
# MultipleInstances IgnoreNew: kalau transcode MP4 sebelumnya masih jalan,
# pemicu berikutnya dilewati -- klaim baris di cron sudah atomik, jadi ini
# hanya menghemat proses PHP, bukan syarat keamanan data.
# ExecutionTimeLimit 2 jam: transcode video satu jam bisa ~20 menit, plus
# antrian remux; batas ini cuma pengaman kalau ffmpeg menggantung.

$admin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
    [Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $admin) {
    Write-Host "[GAGAL] Buka PowerShell dengan 'Run as administrator', lalu jalankan ulang script ini." -ForegroundColor Red
    exit 1
}

$action   = New-ScheduledTaskAction `
    -Execute "C:\xampp\htdocs\new-iresis\cron_finalisasi_video.bat" `
    -WorkingDirectory "C:\xampp\htdocs\new-iresis"
$trigger  = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At "00:00"
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask `
    -TaskName "IRESIS - Finalisasi Video" `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Force

Write-Host "[OK] Task 'IRESIS - Finalisasi Video' dibuat (tiap 1 menit, akun SYSTEM)" -ForegroundColor Green
Write-Host "Cek: Get-ScheduledTaskInfo -TaskName 'IRESIS - Finalisasi Video'" -ForegroundColor Cyan
Write-Host "Log: C:\xampp\htdocs\new-iresis\logs\cron_finalisasi_video.log" -ForegroundColor Cyan
