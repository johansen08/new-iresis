# Handoff — Retur: Rebuild Kanonik, Injeksi Data & Laporan Verifikasi

Dibuat 2026-07-09. Ringkasan pekerjaan modul retur (rebuild tabel, injeksi data historis,
penyesuaian Laporan Verifikasi Retur / Verifikasi Retur Komplain).

---

## 1. Rebuild skema kanonik + cutover (SELESAI 2026-07-08)
- `tblresiretur` & `tblbukaretur` dibangun ulang dengan skema kanonik (PK/AUTO_INCREMENT eksplisit,
  kolom lengkap termasuk `status_acc`/`acc_by`, collation `noresi`/`resi_buka` = `latin1_swedish_ci`).
- Strategi **rename-swap**: data lama → `tblresiretur_arsip` (22.303) / `tblbukaretur_arsip` (26.716);
  tabel `*_new` → nama kanonik. Rollback ada di `dev_tools/cutover_retur.sql`.
- Insiden saat cutover: 2 query batch `INSERT…SELECT` macet ~19,6 jam memegang MDL → di-KILL, cutover sukses.
- Detail lengkap: memory `retur-canonical-rebuild`, plan `~/.claude/plans/http-192-168-3-182-8080-phpmyadmin-tbl-s-iridescent-kite.md`.

## 2. Tooling injeksi (di `dev_tools/`, default DRY-RUN, `--commit` untuk simpan)
- **`inject_retur_putaway.php`** — Excel Putaway format FLAT (header baris 1: Tanggal, SKU, Qty,
  No Pesanan, No Resi, Sumber). Skip TRFI/no-resi; skip sel No Resi ganda (newline) + lapor.
- **`inject_retur_by_resi.php`** — input .txt (daftar resi) ATAU .xlsx (kolom: No Resi, Tanggal, SKU,
  Qty, Status, Alasan Ditolak, SKU Pergantian). **Model HYBRID**: baris SKU-kosong = status default utk
  seluruh pesanan; baris SKU-terisi = item eksplisit (mis. REJECT). SKU pesanan yg tak ditulis → otomatis
  KE_DISPLAY. SKU/qty item non-eksplisit diturunkan dari `tbldetailprintresi` (asumsi order diretur PENUH).
  `norm_status()` memetakan label→nilai (mis. "PENDINGAN BERES (PB)"→PENUKARAN_BERES). `parse_dt()` handle
  format d/M/y ("01/Apr/26"). Idempoten: resi yg sudah ada (is_komplain sama) dilewati.
- **Flag umum**: `--commit`, `--user=28`, `--acc=1`, `--komplain` (is_komplain=1 → Laporan Verifikasi
  Retur Komplain; tanpa flag = retur biasa).
- Template Excel di `Downloads/`: `Template_Suntik_Resi.xlsx` (resi+status, dropdown) & `Template_Suntik_Retur.xlsx`.
- **Data sudah masuk (live)**: 239 resi (8 Juli, resi-list) + 2.447 resi (19-30 Juni, putaway flat) +
  17.906 resi (Apr-Jun 2026, template hybrid). Per 2026-07-09: tblresiretur ~22.130, tblbukaretur ~26.272.

Status_detail_buka valid: KE_DISPLAY, REJECT, REFUND, PENUKARAN_BERES, REQUEST_DARI_PEMBELI, KURANG,
KURANG_DARI_PEMBELI, BUKAN_BARANG_KITA, PAKET_HILANG, DIPAKAI_ADMIN, BARANG_TIDAK_ADA, BUKAN_RETUR (khusus komplain).

## 3. Laporan Verifikasi Retur / Komplain (`application/controllers/Retur.php`, view `retur/validasi_jubelio.php`)

### SUDAH DI-COMMIT
- `e9d1682` — Tabel hanya menampilkan data iresis; baris "Hanya di Jubelio" TIDAK dimunculkan
  (Jubelio hanya pembanding untuk menandai "Cocok"). Berlaku Verifikasi Retur & Komplain.
- `55daaa5` — Nama Toko baris **Cocok** = `marketplace + nama_toko` dari `tblreturjubelio`
  (mis. "SHOPEE ZIOSCARF", "Shop | Tokopedia TT YARRA STORE").
- `f1ee811` — Nama Toko baris **"Hanya di iresis"** = `marketplace + toko` dari `tblprintresi`/`tblmarketplace`
  (fallback saat resi belum ada di Jubelio; ketika nanti masuk Jubelio & di-refresh, otomatis ikut Jubelio
  karena laporan dihitung live tiap load).

### BELUM DI-COMMIT (ada di working tree, sudah lolos lint)
- **Kartu ringkasan dihitung per BARIS (apa adanya), bukan dedup per resi.** Di `get_rekonsiliasi_data`
  variabel `$rc` (row counters) menggantikan `$seen`/`$update_resi`. TOTAL = semua baris.
- **Setujui Jubelio (`selisih`) = baris status `KE_DISPLAY` yang BUKAN "Hanya di iresis"**
  (definisi user: "status ke display − hanya di iresis tapi statusnya ke display"). Rumus lama
  (TOTAL − iresis − jubelio − update − ditolak) DIHAPUS. Filter klik "Setujui Jubelio" di
  `validasi_jubelio.php` juga diselaraskan (statusDetail==='KE_DISPLAY' && bukan label-warning).
- Catatan: data 07-21 semua "Terima Retur" (belum dibuka) → Setujui Jubelio jadi 0 (belum ada KE_DISPLAY);
  ini benar apa adanya.

### Setujui Jubelio — tab Update Retur (BELUM DI-COMMIT, sudah diimplement + lolos lint)
> "jika di update retur ada yang ke display dan sudah diverifikasi maka tidak dihitung masuk ke Setujui [Jubelio]"

Diimplement: loop `foreach ($merged_update ...)` di `get_rekonsiliasi_data` sekarang menambah `$rc['selisih']++`
untuk baris **KE_DISPLAY** yang **bukan "Hanya di iresis"** DAN **belum diverifikasi** (`empty($row['verified'])`).
Baris tab Update yang sudah diverifikasi tidak dihitung. Filter JS `SELISIH` di `validasi_jubelio.php` diselaraskan:
di `applyFilter`, baris `ALL_ROWS_UPDATE` yang `row[17]` mengandung `checked` dikecualikan saat filter `SELISIH`,
sehingga angka kartu = jumlah baris yang tampil. Tab utama (is_update=0) tetap menghitung semua KE_DISPLAY non-iresis
(termasuk yang verified) — amandemen ini khusus tab Update Retur sesuai permintaan user.

## 4. Data-quality yang masih terbuka
- **5 baris resi-ganda lama** (newline di resi_buka), #5 sudah diperbaiki; #1-#4 belum (tumpang-tindih/qty ganjil).
- **17 sel resi-ganda** dari file Juni (dilewati saat injeksi) — ~38 resi, belum ditangani.
- **35 resi JNAP/NLIDAP** dari batch Apr-Jun — tak ada di tblprintresi, dilewati.
- **76 baris REJECT tanpa alasan** (kolom Alasan Ditolak kosong di file user). Bisa di-update kolom alasannya saja.
- **Retur komplain** — user berencana suntik data komplain (pakai `--komplain`), belum dikirim.

## 5. Cara commit perubahan ringkasan (yang belum di-commit)
```
git add application/controllers/Retur.php application/views/retur/validasi_jubelio.php
git commit  # (Bash tool = POSIX sh; pakai heredoc `-F - <<'EOF'`, JANGAN here-string PowerShell @'...'@)
```
Branch: `master` (branch utama sejak 2026-09-12 — `development` sudah dimerge ke `master` lalu dihapus).
Belum ada remote, jadi belum ada push. Lihat `DEVELOPMENT_STANDARDS.md` bagian "Alur Git" untuk alur branch.
