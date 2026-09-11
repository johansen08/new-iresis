# Jalankan sebagai Administrator

# --- 1. IRESIS - Laporan Sore → new-iresis rekap_target_wa ---
$xmlPath = "C:\xampp\htdocs\new-iresis\tmp_task.xml"

schtasks /Query /TN "IRESIS - Laporan Sore" /XML ONE | Out-File $xmlPath -Encoding unicode
$content = Get-Content $xmlPath -Raw
$content = $content -replace [regex]::Escape("C:\xampp\htdocs\iresisv5\cron_laporan_sore.bat"), "C:\xampp\htdocs\new-iresis\cron_laporan_sore.bat"
Set-Content $xmlPath $content -Encoding unicode
schtasks /Delete /TN "IRESIS - Laporan Sore" /F | Out-Null
schtasks /Create /TN "IRESIS - Laporan Sore" /XML $xmlPath /F
Remove-Item $xmlPath -ErrorAction SilentlyContinue
Write-Host "OK: IRESIS - Laporan Sore -> new-iresis (rekap_target_wa, jam 17:00)" -ForegroundColor Green

# --- 2. IRESIS - Paket Keluar → new-iresis cron_paket_keluar.bat ---
schtasks /Query /TN "IRESIS - Paket Keluar" /XML ONE | Out-File $xmlPath -Encoding unicode
$content = Get-Content $xmlPath -Raw
$content = $content -replace [regex]::Escape("C:\xampp\htdocs\iresisv5\cron_paket_keluar.bat"), "C:\xampp\htdocs\new-iresis\cron_paket_keluar.bat"
Set-Content $xmlPath $content -Encoding unicode
schtasks /Delete /TN "IRESIS - Paket Keluar" /F | Out-Null
schtasks /Create /TN "IRESIS - Paket Keluar" /XML $xmlPath /F
Remove-Item $xmlPath -ErrorAction SilentlyContinue
Write-Host "OK: IRESIS - Paket Keluar -> new-iresis (paket_keluar, jam 17:00)" -ForegroundColor Green

# Verifikasi
Write-Host ""
foreach ($t in @("IRESIS - Laporan Sore", "IRESIS - Paket Keluar")) {
    $task = Get-ScheduledTask -TaskName $t
    Write-Host "[$t]"
    Write-Host "  Execute : $($task.Actions[0].Execute)"
    Write-Host "  Start   : $($task.Triggers[0].StartBoundary)"
}
