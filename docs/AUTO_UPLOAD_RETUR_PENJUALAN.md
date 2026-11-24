# Auto Upload Daftar Retur Penjualan (tanpa browser)

Task Scheduler **"IRESIS - Upload retur penjualan"** jalan **06.00, 12.00, 20.00**: unduh laporan
*Daftar Retur Penjualan* Jubelio (1 Jan tahun berjalan s/d hari ini) lalu unggah ke IRESIS
(`cron/auto_upload_retur_jubelio` → `tblreturjubelio`, logika sama dengan upload manual).
Menggantikan jalur Chrome lama `scripts/auto_upload_retur_jubelio.py`.

## Alur (hasil sniff 24 Nov 2026)

| # | Request | Hasil |
|---|---------|-------|
| 1 | `POST open.jubelio.com/core-api/login` | token JWT |
| 2 | `GET open.jubelio.com/core-api/reports/customer-retur/date-range/?date_from=…&date_to=…&tz=Asia/Bangkok` | `{"status":"ok","url":"https://report-prod.jubelio.com/?&token=T"}` |
| 3 | `GET https://report-prod.jubelio.com/xlsx/?&token=T` (path `/xlsx/`, token T sama) | xlsx ±12,8 MB, ±25 detik |
| 4 | `POST <IRESIS>/cron/auto_upload_retur_jubelio?token=…` (field `jubelioFile`) | JSON; ±4 menit untuk 100 rb baris |

Catatan: dokumen lama menyebut unduhan HTTP murni mustahil (404). Sniff terbaru membuktikan sebaliknya:
cukup ganti path URL viewer menjadi `/xlsx/`. Endpoint Cron mencetak satu baris log sebelum JSON-nya;
klien mengambil JSON dari baris terakhir.

## File

| File | Fungsi |
|------|--------|
| `scripts/auto_upload_retur_penjualan.py` | Program utama (`--tanpa-upload` = hanya unduh, uji jalur Jubelio) |
| `scripts/retur_penjualan_senyap.vbs` | Pembungkus tanpa jendela konsol (dipanggil Task Scheduler) |
| `scripts/daftar_task_retur_penjualan.ps1` | Mendaftarkan task 3× sehari (aman diulang) |

Butuh `scripts/jubelio_credentials.py` (baca `secrets.php`) dan `scripts/secrets_local.py` (`IRESIS_CRON_TOKEN`, `IRESIS_BASE_URL`) — sama dengan `refresh_status_resi.py`.

## Mencatat hasil — cara menelusuri

Tiap run **selalu** menulis dua berkas di `logs/`:

1. **`riwayat_retur_penjualan.csv`** — satu baris per run (buka di Excel): `status` (BERHASIL/GAGAL),
   `tahap_gagal`, `alasan`, `saran_perbaikan`, `ringkasan` (jumlah baris baru/diperbarui/cocok), `durasi_detik`.
   Filter kolom `status = GAGAL` untuk melihat semua kegagalan.
2. **`auto_upload_retur_penjualan.log`** — log rinci; setiap baris diberi `run_id` yang sama dengan CSV.
   Error tak terduga menyertakan traceback lengkap. Token/JWT otomatis disensor.

Exit code 1 saat gagal (kolom *Last Run Result* di Task Scheduler = 1). Berkas xlsx yang gagal diunggah
disimpan di `scratch/retur_penjualan/` untuk diperiksa (berkas >3 hari dibersihkan otomatis).

### Tahap gagal dan tindakannya

| `tahap_gagal` | Penyebab umum | Tindakan |
|---|---|---|
| `PERSIAPAN` | kredensial/secrets_local kosong; run sebelumnya masih jalan (lock) | isi secrets; tunggu / hapus `scratch/retur_penjualan/berjalan.lock` bila usang |
| `LOGIN` | password Jubelio diganti, akun dikunci, internet putus | login manual di v2.jubelio.com, perbarui `jubelio_password` di `secrets.php` |
| `URL_LAPORAN` | 429 rate limit, akun tanpa akses laporan, endpoint berubah | tunggu jadwal berikut; bila berulang sniff ulang (`C:\proses-pesanan-otomatis\sniff`) |
| `UNDUH` | token ditolak, koneksi putus (3× coba), report server error | cek internet; bila 403/404 sniff ulang tombol Cetak → Excel |
| `VALIDASI_FILE` | file kosong/bukan xlsx | buka berkas di `scratch/retur_penjualan/` |
| `UPLOAD` | Apache mati, token cron salah (403), respons bukan JSON, header xlsx berubah | pesan IRESIS ada di kolom `alasan`; cek `Retur_fcd::parse_jubelio_spreadsheet()` |
| `TAK_TERDUGA` | bug di script | baca traceback di `.log` |

`logs/auto_upload_retur_penjualan_stdout.log` menangkap output mentah yang lolos dari logger (mis. Python gagal start).

## Pasang / hapus

```powershell
powershell -ExecutionPolicy Bypass -File scripts\daftar_task_retur_penjualan.ps1
```

Task lama jalur Chrome (`auto_upload_retur_jubelio.py`), bila ada, nonaktifkan agar tidak dobel.
