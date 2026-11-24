# Mendaftarkan task "IRESIS - Cek status retur Jubelio" di Windows Task Scheduler: 06.30, 11.30, 20.30.
# Jalankan sekali:  powershell -ExecutionPolicy Bypass -File scripts\daftar_task_cek_status_retur.ps1
# Aman dijalankan ulang (task ditimpa). Setelan meniru task IRESIS lain (user interaktif, IgnoreNew).
$nama = 'IRESIS - Cek status retur Jubelio'
$aksi = New-ScheduledTaskAction -Execute 'wscript.exe' -Argument 'C:\xampp\htdocs\new-iresis\scripts\cek_status_retur_jubelio_senyap.vbs'
$pemicu = @('06:30', '11:30', '20:30') | ForEach-Object { New-ScheduledTaskTrigger -Daily -At $_ }
$setelan = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Hours 2)
Register-ScheduledTask -TaskName $nama -Action $aksi -Trigger $pemicu -Settings $setelan `
    -Description 'Cek status Jubelio pesanan di tab Belum Terima & Sudah Verifikasi; yang COMPLETED diperbarui. Log: logs\riwayat_cek_status_retur.csv' `
    -Force | Out-Null
Get-ScheduledTask -TaskName $nama | Get-ScheduledTaskInfo | Format-List TaskName, NextRunTime
