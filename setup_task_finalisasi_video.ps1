# Jalankan script ini sebagai Administrator (klik kanan -> Run as administrator)
# Mendaftarkan task "IRESIS - Finalisasi Video" yang menjalankan
# cron_finalisasi_video.bat tiap 1 menit (remux WebM + antrian MP4).
#
# MultipleInstances IgnoreNew: kalau transcode MP4 sebelumnya masih jalan,
# pemicu berikutnya dilewati -- klaim baris di cron sudah atomik, jadi ini
# hanya menghemat proses PHP, bukan syarat keamanan data.
# ExecutionTimeLimit 2 jam: transcode video satu jam bisa ~20 menit, plus
# antrian remux; batas ini cuma pengaman kalau ffmpeg menggantung.

$action   = New-ScheduledTaskAction `
    -Execute "C:\xampp\htdocs\new-iresis\cron_finalisasi_video.bat" `
    -WorkingDirectory "C:\xampp\htdocs\new-iresis"
$trigger  = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At "00:00"
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable

Register-ScheduledTask `
    -TaskName "IRESIS - Finalisasi Video" `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -RunLevel Highest `
    -Force

Write-Host "[OK] Task 'IRESIS - Finalisasi Video' dibuat (tiap 1 menit)" -ForegroundColor Green
Write-Host "Cek log di C:\xampp\htdocs\new-iresis\logs\cron_finalisasi_video.log" -ForegroundColor Cyan
