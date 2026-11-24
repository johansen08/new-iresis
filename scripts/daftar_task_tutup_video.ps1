# Mendaftarkan task "IRESIS - Tutup video menggantung" di Windows Task Scheduler: tiap 15 menit.
# Jalankan sekali:  powershell -ExecutionPolicy Bypass -File scripts\daftar_task_tutup_video.ps1
# Aman dijalankan ulang (task ditimpa). Setelan meniru task IRESIS lain (user interaktif, IgnoreNew).
$nama = 'IRESIS - Tutup video menggantung'
$aksi = New-ScheduledTaskAction -Execute 'wscript.exe' -Argument 'C:\xampp\htdocs\new-iresis\scripts\tutup_video_menggantung_senyap.vbs'
$pemicu = New-ScheduledTaskTrigger -Once -At '00:00' -RepetitionInterval (New-TimeSpan -Minutes 15)
$setelan = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 10)
Register-ScheduledTask -TaskName $nama -Action $aksi -Trigger $pemicu -Settings $setelan `
    -Description 'Tandai rekaman video packing berstatus MEREKAM > 120 menit sebagai TERPUTUS (Cron::tutup_video_menggantung). Log: logs\cron_tutup_video_menggantung.log' `
    -Force | Out-Null
Get-ScheduledTask -TaskName $nama | Get-ScheduledTaskInfo | Format-List TaskName, NextRunTime
