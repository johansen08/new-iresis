' backup_arsip_senyap.vbs - backup harian database iresis_arsip tanpa jendela konsol.
' Dipakai Task Scheduler "IRESIS - Backup arsip 02.30" (sejak 2026-09-21), setelah
' job "IRESIS - Arsip harian" 01.00 selesai menyalin.
'
' Memakai ulang C:\backup-db\backup_db.ps1 (alur & verifikasi sama dengan backup prod):
'   -Database iresis_arsip  -> file otomatis\iresis_arsip_<tgl>_<jam>.sql.gz
'   -Simpan 7               -> rotasi 7 hari (arsip hanya berubah sekali sehari)
'   -Paksa                  -> abaikan batas jam operasional 07-22 milik backup prod
' Log di C:\backup-db\log\backup_db.log; kegagalan memunculkan
' C:\backup-db\PERHATIAN_BACKUP_GAGAL.txt (hilang sendiri saat backup berikutnya sukses).
' Catatan: hanya ada drive C di PC ini (21 Sep 2026) -- kalau nanti ada drive
' eksternal/NAS, ubah $dirBackup di backup_db.ps1 supaya salinan tidak sedisk dengan data.
Dim sh: Set sh = CreateObject("WScript.Shell")
WScript.Quit sh.Run("powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File ""C:\backup-db\backup_db.ps1"" -Database iresis_arsip -Simpan 7 -Paksa", 0, True)
