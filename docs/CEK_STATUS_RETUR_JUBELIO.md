# Cek Status Jubelio — tab Belum Terima & Sudah Verifikasi

Task Scheduler **"IRESIS - Cek status retur Jubelio"** jalan **06.30, 11.30, 20.30**. Tugasnya: pesanan yang di Daftar Retur
Penjualan masih tercatat retur (tab **Belum Terima** + **Belum Terima (Hangus)** dan **Sudah Verifikasi**) dicek ke Jubelio.

## Masalah

Saat Excel Daftar Retur Penjualan diunggah, pesanan berstatus `RETURNED`. Belakangan pesanan bisa berubah `COMPLETED` di Jubelio,
tetapi IRESIS tidak pernah memperbaruinya, sehingga resi tersangkut di dua tab itu selamanya.

## Aturan

| Status pesanan di Jubelio (`internal_status`) | Tindakan |
|---|---|
| `RETURNED` (atau lainnya) | **Tidak ada perubahan** |
| `COMPLETED` | `tblreturjubelio.status_retur`: `BARANG RETUR` → **`SELESAI (PESANAN COMPLETED)`** (status_jubelio → COMPLETED); `tblprintresi.status_pesanan`: `RETURNED` → `COMPLETED` |

* **`SELESAI (PESANAN COMPLETED)` adalah status final.** Tidak ada kode yang boleh mengubahnya lagi: pengecekan ini hanya menyentuh baris
  `BARANG RETUR`, dan upload Excel (`Retur_fcd::insert_jubelio_batch`) mempertahankan `status_retur` + `status_jubelio` baris itu serta
  tidak lagi menimpa `tblprintresi` jadi RETURNED untuk resinya.
* **`SELESAI (DATA LAMA)`** = status final kedua, ditetapkan manual (24 Nov 2026) untuk 19 pesanan Shopee > 3 bulan (25 Jun-21 Agu 2026) yang sudah tidak ada di Jubelio/Excel sehingga tak bisa dicek lagi (27 baris; jejak di `tblretur_cek_status_log`, `run_id = DATA_LAMA-20261124`; cadangan `logs/backup/bak_returjubelio_datalama_20261124.sql`). Aturan perlindungannya sama dengan `SELESAI (PESANAN COMPLETED)`: tidak ditimpa upload Excel, tidak disentuh pengecekan.
* **`DITOLAK (PENYESUAIAN)`** = status final ketiga, ditetapkan manual (24 Nov 2026) untuk 78 pesanan (87 baris; 77 TikTok + 1 Shopee) yang retur-nya ditolak lewat penyesuaian. Jejak: `tblretur_cek_status_log`, `run_id = DITOLAK_PENYESUAIAN-20261124`; cadangan `logs/backup/bak_returjubelio_ditolak_penyesuaian_20261124.sql`. Tiga status final buatan iresis ini (`SELESAI (PESANAN COMPLETED)`, `SELESAI (DATA LAMA)`, `DITOLAK (PENYESUAIAN)`) tidak ditimpa upload Excel dan tidak disentuh pengecekan.
* **`SELESAI (PENGEMBALIAN DANA)`** = status final keempat, ditetapkan manual (24 Nov 2026) untuk 25 pesanan (49 baris; 24 Shopee + 1 Tokopedia) di tab Belum Terima yang nomor resi/pesanannya ada di Excel "DATA PENGEMBALIAN DANA BANDING CUSTOMER" (file Oktober 2026): dana sudah dikembalikan, jadi paket tidak ditunggu lagi. Jejak: `tblretur_cek_status_log`, `run_id = PENGEMBALIAN_DANA-20261124`; cadangan `logs/backup/bak_returjubelio_pengembalian_dana_20261124.sql`. `tblprintresi` tidak diubah. Aturan perlindungan sama dengan tiga status final lain: tidak ditimpa upload Excel dan tidak disentuh pengecekan.
* Resi yang semua barisnya berubah final keluar dari tab Belum Terima / Sudah Verifikasi dan muncul di tab **Selesai** (dan tab Semua; filter
  *Status Retur* punya pilihan baru `SELESAI (PESANAN COMPLETED)`).
* `tblprintresi` hanya diubah bila resi masih ada di prod dan tidak tersisa baris `BARANG RETUR` pada resi itu. Resi yang sudah dipurna ke arsip
  hanya berubah di `tblreturjubelio`.

## Alur teknis (hasil sniff 24 Nov 2026)

1. `POST open.jubelio.com/core-api/login` → token.
2. `GET <IRESIS>/cron/kandidat_cek_status_retur` → kandidat (memakai `Daftar_retur_fcd::get_list`, jadi isi tab persis sama dengan layar).
3. `GET open.jubelio.com/core-api/sales/v2/orders/?q=<no_pesanan>&page=1&page_size=25&sku_filter=false&sort_by=transaction_date&sort_direction=DESC`
   → `data[].internal_status`; dicocokkan persis dengan `salesorder_no` (cadangan `ref_no`). ±7 pesanan/detik dengan 4 pekerja
   (±8 ribu pesanan ≈ 20 menit).
4. `POST <IRESIS>/cron/terapkan_status_retur` (JSON `{run_id, kering, hasil[]}`; hanya `kering:false` eksplisit yang menulis) →
   `Daftar_retur_fcd::terapkan_cek_status`. Tiap perubahan dicatat (nilai sebelum & sesudah) di tabel **`tblretur_cek_status_log`**.

## Mencatat hasil — cara menelusuri

Tiap run **selalu** menulis di `logs/`:

1. **`riwayat_cek_status_retur.csv`** — satu baris per run: `status` (BERHASIL/GAGAL), `tahap_gagal`, `alasan`, `saran_perbaikan`, `ringkasan`.
2. **`cek_status_retur_jubelio.log`** — log rinci, tiap baris diberi `run_id`; error tak terduga menyertakan traceback. Token disensor.
3. **`rekap_cek_status_retur/rekap_<run_id>.csv`** — pesanan yang berubah di run itu (`kering_<run_id>.csv` untuk mode `--kering`).
4. **`rekap_cek_status_retur/tidak_cocok_<run_id>.csv`** — pesanan yang tidak ditemukan / error di Jubelio, beserta penyebabnya.

Exit code 1 saat gagal (*Last Run Result* = 1 di Task Scheduler). Run dianggap GAGAL (dan **tidak menerapkan apa pun**) bila >20% pesanan error di Jubelio.

| `tahap_gagal` | Penyebab umum | Tindakan |
|---|---|---|
| `PERSIAPAN` | kredensial/secrets_local kosong; run sebelumnya masih jalan (lock) | isi secrets; tunggu / hapus `scratch/cek_status_retur/berjalan.lock` bila usang |
| `LOGIN` | password Jubelio diganti, akun dikunci, internet putus | login manual di v2.jubelio.com, perbarui `jubelio_password` di `secrets.php` |
| `KANDIDAT` | Apache mati, token cron salah (403), PHP error di `Daftar_retur_fcd::kandidat_cek_status` | pesan IRESIS ada di `alasan`; cek `application/logs/` |
| `CEK_JUBELIO` | rate limit 429, token ditolak, Jubelio gangguan, endpoint `sales/v2/orders` berubah | tunggu jadwal berikut; kurangi `--pekerja`; bila berulang sniff ulang |
| `TERAPKAN` | Apache mati, error SQL di `terapkan_cek_status` | cek `application/logs/`; run berikutnya aman diulang (hanya baris `BARANG RETUR` yang diproses) |
| `TAK_TERDUGA` | bug di script | baca traceback di `.log` |

## Membatalkan perubahan

Setiap perubahan ada di `tblretur_cek_status_log` (`run_id`, `no_resi`, `no_pesanan`, status lama/baru). Untuk run pertama (24 Nov 2026) ada juga
cadangan baris terkait di `logs/backup/bak_returjubelio_20261124.sql` dan `bak_printresi_20261124.sql` (mysqldump, folder di-gitignore). Pembatalan = kembalikan `status_retur` ke `BARANG RETUR`, `tblprintresi.status_pesanan` ke
`RETURNED` untuk resi pada log (butuh persetujuan sesuai aturan UPDATE proyek).

## Pasang / uji

```powershell
powershell -ExecutionPolicy Bypass -File scripts\daftar_task_cek_status_retur.ps1
```

Uji tanpa menulis: `python scripts\cek_status_retur_jubelio.py --kering [--maks 50]`.
Butuh `scripts/jubelio_credentials.py` dan `scripts/secrets_local.py` (sama dengan `auto_upload_retur_penjualan.py`).
