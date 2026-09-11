# Jalankan sebagai Administrator

$taskName = "IRESIS - RTS Check"
$xmlPath  = "C:\xampp\htdocs\new-iresis\tmp_rts.xml"

# Export XML lalu ganti trigger menjadi 3 waktu tetap (tanpa repetisi)
schtasks /Query /TN $taskName /XML ONE | Out-File $xmlPath -Encoding unicode

$xml = [xml](Get-Content $xmlPath -Encoding unicode)
$ns  = "http://schemas.microsoft.com/windows/2004/02/mit/task"

# Hapus semua trigger lama
$triggersNode = $xml.Task.Triggers
while ($triggersNode.HasChildNodes) {
    $triggersNode.RemoveChild($triggersNode.FirstChild) | Out-Null
}

# Tambah 3 trigger: 15:00, 16:00, 17:00
foreach ($jam in @("15:00", "16:00", "17:00")) {
    $cal = $xml.CreateElement("CalendarTrigger", $ns)

    $sb = $xml.CreateElement("StartBoundary", $ns)
    $sb.InnerText = "2026-06-23T${jam}:00"
    $cal.AppendChild($sb) | Out-Null

    $en = $xml.CreateElement("Enabled", $ns)
    $en.InnerText = "true"
    $cal.AppendChild($en) | Out-Null

    $sched = $xml.CreateElement("ScheduleByDay", $ns)
    $di = $xml.CreateElement("DaysInterval", $ns)
    $di.InnerText = "1"
    $sched.AppendChild($di) | Out-Null
    $cal.AppendChild($sched) | Out-Null

    $triggersNode.AppendChild($cal) | Out-Null
}

$xml.Save($xmlPath)

schtasks /Delete /TN $taskName /F | Out-Null
schtasks /Create /TN $taskName /XML $xmlPath /F
Remove-Item $xmlPath -ErrorAction SilentlyContinue

Write-Host "OK: $taskName diupdate -> hanya jam 15:00, 16:00, 17:00" -ForegroundColor Green

$t = Get-ScheduledTask -TaskName $taskName
$t.Triggers | ForEach-Object { Write-Host "  Trigger: $($_.StartBoundary) | Interval: $($_.Repetition.Interval)" }
