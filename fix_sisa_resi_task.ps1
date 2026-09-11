# Jalankan sebagai Administrator

# Set repetition via XML export -> edit -> import
$taskName = "IRESIS2 - Sisa Resi"
$xmlPath  = "C:\xampp\htdocs\new-iresis\tmp_task.xml"

# Export XML
schtasks /Query /TN $taskName /XML ONE | Out-File $xmlPath -Encoding unicode

# Baca, inject repetition ke trigger
$xml = [xml](Get-Content $xmlPath -Encoding unicode)
$ns  = "http://schemas.microsoft.com/windows/2004/02/mit/task"

$trigger = $xml.Task.Triggers.CalendarTrigger
if ($trigger -eq $null) {
    Write-Host "ERROR: Trigger tidak ditemukan" -ForegroundColor Red
    exit
}

# Set atau update Repetition
$rep = $xml.CreateElement("Repetition", $ns)

$interval = $xml.CreateElement("Interval", $ns)
$interval.InnerText = "PT1H"
$rep.AppendChild($interval) | Out-Null

$duration = $xml.CreateElement("Duration", $ns)
$duration.InnerText = "PT8H"
$rep.AppendChild($duration) | Out-Null

$stop = $xml.CreateElement("StopAtDurationEnd", $ns)
$stop.InnerText = "false"
$rep.AppendChild($stop) | Out-Null

# Hapus repetition lama jika ada, lalu tambah yang baru
$oldRep = $trigger.Repetition
if ($oldRep) { $trigger.RemoveChild($oldRep) | Out-Null }
$trigger.AppendChild($rep) | Out-Null

$xml.Save($xmlPath)

# Re-register task dari XML
schtasks /Delete /TN $taskName /F | Out-Null
schtasks /Create /TN $taskName /XML $xmlPath /F
Remove-Item $xmlPath -ErrorAction SilentlyContinue

Write-Host "OK: $taskName diupdate -> tiap 1 jam mulai 15:05 s/d 23:05" -ForegroundColor Green

# Verifikasi
$t = Get-ScheduledTask -TaskName $taskName
$info = $t | Get-ScheduledTaskInfo
Write-Host "NextRun: $($info.NextRunTime)"
Write-Host "Interval: $($t.Triggers[0].Repetition.Interval)"
