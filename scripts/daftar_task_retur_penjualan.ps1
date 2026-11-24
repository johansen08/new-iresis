# Mendaftarkan task "IRESIS - Upload retur penjualan" di Windows Task Scheduler: 06.00, 12.00, 20.00.
# Jalankan sekali:  powershell -ExecutionPolicy Bypass -File scripts\daftar_task_retur_penjualan.ps1
# Aman dijalankan ulang (task ditimpa). Setelan meniru task IRESIS lain (user interaktif, IgnoreNew).
$nama = 'IRESIS - Upload retur penjualan'
$aksi = New-ScheduledTaskAction -Execute 'wscript.exe' -Argument 'C:\xampp\htdocs\new-iresis\scripts\retur_penjualan_senyap.vbs'
$pemicu = @('06:00', '12:00', '20:00') | ForEach-Object { New-ScheduledTaskTrigger -Daily -At $_ }
$setelan = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Hours 2)
Register-ScheduledTask -TaskName $nama -Action $aksi -Trigger $pemicu -Settings $setelan `
    -Description 'Unduh Daftar Retur Penjualan Jubelio (1 Jan s/d hari ini) lalu upload ke IRESIS. Log: logs\riwayat_retur_penjualan.csv' `
    -Force | Out-Null
Get-ScheduledTask -TaskName $nama | Get-ScheduledTaskInfo | Format-List TaskName, NextRunTime
