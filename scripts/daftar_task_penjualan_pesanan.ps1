# Mendaftarkan task "IRESIS - Upload penjualan pesanan 3 bulan" di Windows Task Scheduler: tiap hari 22.00.
# Jalankan sekali:  powershell -ExecutionPolicy Bypass -File scripts\daftar_task_penjualan_pesanan.ps1
# Aman dijalankan ulang (task ditimpa). Setelan meniru task IRESIS lain (user interaktif, IgnoreNew).
$nama = 'IRESIS - Upload penjualan pesanan 3 bulan'
$aksi = New-ScheduledTaskAction -Execute 'wscript.exe' -Argument 'C:\xampp\htdocs\new-iresis\scripts\penjualan_pesanan_senyap.vbs'
$pemicu = New-ScheduledTaskTrigger -Daily -At '22:00'
$setelan = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Hours 5)
Register-ScheduledTask -TaskName $nama -Action $aksi -Trigger $pemicu -Settings $setelan `
    -Description 'Unduh Daftar Penjualan (Pesanan) Jubelio 90 hari terakhir, update status pesanan + tanggal/waktu retur di IRESIS. Log: logs\riwayat_penjualan_pesanan.csv' `
    -Force | Out-Null
Get-ScheduledTask -TaskName $nama | Get-ScheduledTaskInfo | Format-List TaskName, NextRunTime
