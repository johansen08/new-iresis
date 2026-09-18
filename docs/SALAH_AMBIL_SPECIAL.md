# Alur Salah Ambil Special (Packer)

Rilis 18 September 2026 sore (`d17297e..8a42420`, live search `b5b2707..391edc0`).
Dokumen ini satu-satunya rujukan alur menu Salah Ambil Special;
`ANALISIS_PROGRAM.md` dan `WORKFLOW_DIAGRAM.md` hanya menunjuk ke sini.
Keputusan desain saat perancangan ada di
`superpowers/specs/2026-09-18-salah-ambil-special-design.md`.

## 1. Masalah yang diselesaikan

Resi **spesial** = resi yang isinya tepat **1 SKU dengan qty 1**. Resi seperti
ini dikumpulkan dan dikerjakan sekaligus dalam satu batch. Kalau picker salah
mengambil satu batch (misal 30 resi yang seharusnya `BSBI-4` tapi semuanya
terambil `BSBI-5`), sebelum rilis ini packer harus mengulang 30 kali di Scan
Resi Packer: scan resi → tombol Masalah Picker → pilih SALAH AMBIL → ketik SKU
yang terambil → submit.

Sekarang: packer mengisi **dua SKU sekali**, lalu men-scan semua resi
berturut-turut. Tiap resi yang lolos validasi langsung menjadi baris
`tblmasalahpicker` tipe SALAH AMBIL — **bentuknya sama persis** dengan hasil
modal Masalah Picker, sehingga Daftar Masalah Picker (lama & New), slip
kurangan, KPI picker, dan Error Recap membacanya tanpa perubahan apa pun.

## 2. Istilah

| Istilah | Arti |
|---|---|
| **Resi spesial** | Resi dengan tepat 1 baris `tbldetailprintresi` dan `jumlah = 1`. |
| **SKU seharusnya** | SKU yang tertulis di resi (yang benar). Disimpan ke `tblmasalahpicker.sku`. Di menu ini juga berfungsi sebagai **penjaga**: resi yang SKU-nya berbeda ditolak. |
| **SKU terambil** | SKU yang keliru diambil picker. Disimpan ke `tblmasalahpicker.sku_salah`; tercetak di slip CS sebagai `SALAH AMBIL (terambil …)` supaya picker tahu barang mana yang harus ditukar. |
| **Kunci** | Keadaan setelah dua SKU divalidasi ke `tblsku`: field SKU readonly, field resi aktif. Semua scan memakai pasangan SKU itu sampai **Ganti SKU**. |
| **Tabel sesi** | Daftar hasil scan di layar (tercatat/ditolak + alasan). Hanya di memori browser; catatan resminya `tblmasalahpicker`. |

## 3. Siapa melakukan apa

| Meja | Menu | Yang dilakukan | Yang **tidak** dilakukan |
|---|---|---|---|
| Packer (role 4) / webmaster (1) | TIM PACKER → **Salah Ambil Special** (`salah-ambil-special`) | Isi SKU seharusnya + SKU terambil (pilih dari saran), Kunci, scan semua resi batch itu, baca baris hijau/merah | Memilih picker (dideteksi CS dari `tblresiambilbarang`); membatalkan baris yang sudah tercatat; memproses resi campuran |
| CS | TIM CS → **Daftar Masalah Picker New** (atau yang lama) | Memproses & mencetak slip seperti laporan salah ambil biasa | Tidak ada langkah khusus untuk menu ini |
| Picker | — | Menerima slip, menukar barang | — |

Menu Scan Resi Packer + modal Masalah Picker **tetap** jalur untuk resi
campuran atau kasus satuan.

## 4. Alur

```
              TIM PACKER → Salah Ambil Special
                          │
        ┌─────────────────▼──────────────────┐
        │  Field "SKU seharusnya"            │  ketik ≥1 huruf → GET cari-sku?term=
        │  Field "SKU terambil (salah)"      │  → dropdown saran KODE — rak X
        │  [Kunci & Mulai Scan]              │  (cocok berdasarkan kode saja, maks 15)
        └─────────────────┬──────────────────┘
                          │ POST cek-sku {sku_benar, sku_salah}
                          ▼
            keduanya terisi, beda, ada di tblsku?
                 │ tidak                │ ya
                 ▼                      ▼
        alert merah + bunyi gagal   field SKU readonly, nama+rak tampil,
        (tetap belum terkunci)      field resi aktif & fokus  ── TERKUNCI
                                        │
                        ┌───────────────┘  scan resi (Enter) → masuk antrean JS,
                        │                  dikirim SATU PER SATU
                        ▼
              POST scan-resi {noresi, sku_benar, sku_salah}
                        │
                        ▼
        ┌── validasi berurutan, berhenti di kegagalan pertama ───────────┐
        │ #1 pasangan SKU dicek ulang ke tblsku (jangan percaya browser) │
        │ #2 noresi ada di tblprintresi (cetak ulang → id terbaru)       │
        │ #3 tepat 1 baris detail & total qty 1                          │
        │ #4 sku detail = SKU seharusnya                                 │
        │ #5 tidak ada di tblpacking                                     │
        │ #6 ada di tblresiambilbarang (picker terdeteksi)               │
        │ #7 belum ada tblmasalahpicker untuk (id_printresi, sku)        │
        └────────────────┬──────────────────────────┬────────────────────┘
                         │ gagal                    │ lolos (#7 + INSERT dalam
                         ▼                          ▼  satu transaksi, resi FOR UPDATE)
             baris MERAH "Ditolak: <alasan>"   INSERT tblmasalahpicker
             bunyi gagal, tidak ada tulisan    (tipe 4, qty 1, status 0)
                         │                          │
                         └──────────┬───────────────┘
                                    ▼
                  field resi dikosongkan & fokus lagi → scan berikutnya
                                    │
                                    ▼  (kapan saja)
                  [Ganti SKU] → confirm → buka kunci, tabel sesi kosong
                                    │
                                    ▼
        TIM CS → Daftar Masalah Picker New: baris tampil sebagai SALAH AMBIL,
        SKU Salah terisi, picker dari tblresiambilbarang, packer = pelapor
        → Proses & Cetak → slip "SALAH AMBIL (terambil <sku_salah>)" per picker
```

## 5. Kondisi & keputusan sistem

### 5.1 `Salah_ambil_special::cari_sku()` — live search (GET `?term=`)

- `term` kosong → `[]`. Selain itu `Salah_ambil_special_fcd::cari_sku_mirip()`:
  `id_sku LIKE '%term%'`, yang `LIKE 'term%'` di atas, urut `id_sku`, maks 15.
- Hanya mencocokkan **kode**, bukan `nama_sku` — dropdown pun hanya
  menampilkan `KODE — rak X`; mencocokkan nama membuat kode yang tak mirip
  ketikan ikut muncul dan membingungkan.
- Balasan array polos `{label, value, nama_sku, no_rak}` (bukan bentuk
  `make_ajax_response`) supaya JS tinggal memetakan.
- Dropdown dibuat sendiri di view (`pasangSaran()`): `jquery-ui.min.js` proyek
  ini build ringkas **tanpa** widget autocomplete/menu — memanggil
  `.autocomplete()` melempar error dan mematikan seluruh script halaman.
  Keyboard: ↑/↓ sorot, Enter pilih (atau pindah field kalau tidak ada saran),
  Esc tutup; klik memilih. Balasan yang datang terlambat dari ketikan lama
  diabaikan (`requestTerakhir`).

### 5.2 `Salah_ambil_special::cek_sku()` — tombol Kunci (POST)

`validasi_pasangan_sku()` mengembalikan `['error' => …]` atau
`['sku_benar' => baris tblsku, 'sku_salah' => baris tblsku]`:

| Kondisi | Pesan (code 400) |
|---|---|
| salah satu kosong | SKU seharusnya dan SKU terambil wajib diisi |
| sama (tanpa peduli huruf) | SKU seharusnya dan SKU terambil tidak boleh sama |
| tidak ada di `tblsku` | SKU seharusnya "X" tidak ada di master SKU / SKU terambil "X" … |

Yang dipakai selanjutnya adalah **ejaan `id_sku` dari `tblsku`**, bukan
ketikan user (kolom `id_sku` diisi ulang ke field saat kunci). Sukses → code
200 + kedua baris (nama, rak) untuk konfirmasi visual.

### 5.3 `Salah_ambil_special::scan_resi()` — satu scan (POST)

Berhenti di kegagalan pertama; semua balasan HTTP 200 dengan `code` di body
(`make_ajax_response`), karena `set_status_header(4xx)` di CI mengirim HTML
yang merusak JSON SPA.

| # | Kondisi | Code | Pesan |
|---|---|---|---|
| 0 | bukan POST / `noresi` kosong | 400 | `INVALID_REQUEST_METHOD` / Nomor resi kosong |
| 1 | pasangan SKU gagal `validasi_pasangan_sku()` | 400 | Kunci SKU dulu: … |
| 2 | `resi_terbaru(noresi)` NULL | 404 | Resi tidak ditemukan |
| 3 | `count(detail) != 1` atau `sum(jumlah) != 1` | 422 | Bukan resi spesial (n SKU, qty m) |
| 4 | `strcasecmp(detail.sku, sku_benar) != 0` | 422 | SKU resi X, bukan Y |
| 5 | `sudah_packing()` | 409 | Resi sudah di-packing |
| 6 | `picker_resi()` NULL | 422 | Resi belum di-scan ambil picker |
| 7 | `laporan_ada()` (status apa pun) | 409 | Sudah dilaporkan (pending) / (sudah diproses CS) |
| — | `trans_status()` FALSE | 500 | Gagal menyimpan, coba lagi |
| ✔ | lolos | 201 | Tercatat + `{noresi, sku, sku_salah, nama_picker}` |

Catatan:

- **#1 diulang tiap scan**: field readonly tetap bisa diubah lewat devtools;
  server tidak memercayai nilai dari browser.
- **#2**: `noresi` yang dicetak ulang punya beberapa `id_printresi`; dipakai
  yang terbaru (`created_at DESC, id_printresi DESC`) — sama dengan
  `Packer::detail_resi()`.
- **#7 + INSERT** dalam satu transaksi (`trans_begin` … `trans_commit`),
  diawali `SELECT … FOR UPDATE` pada baris `tblprintresi`. Dua scan resi yang
  sama yang nyaris bersamaan (scanner menembak dua kali) antre di lock; yang
  kedua lalu tertangkap #7. `db_debug` dimatikan sementara agar error DB tidak
  mencetak halaman HTML CI ke dalam JSON.
- Nama picker di balasan: `tblpegawai.nama_pegawai`, cadangan `tbluser.name`,
  terakhir `PEGAWAI #<kode>`.

### 5.4 Sisi browser (`views/salah_ambil_special/index.php`)

- Kedua form ber-class **`nojs`** + `preventDefault` sendiri: tanpa itu handler
  global `plugins.js` mengganti seluruh `.page-content-wrap` saat request
  berjalan, input resi hilang dari DOM, dan scan yang diketik scanner saat itu
  lenyap (pelajaran dari Scan Resi Packer).
- Semua handler diikat ke `#sas-root`, bukan `document`, agar ikut hilang saat
  pindah menu (tidak menumpuk).
- **Antrean scan**: nilai input langsung diambil lalu dikosongkan (scanner
  boleh mengetik berikutnya kapan saja); request dikirim satu per satu
  (`sedangKirim`), sehingga urutan baris di tabel = urutan scan dan tidak ada
  dua INSERT paralel dari satu browser.
- Bunyi lewat `suaraScan()` global (`main.php`): `audio-alert` tercatat,
  `audio-fail` ditolak/gagal kunci.
- Balasan non-JSON (warning PHP, halaman error CI) tampil sebagai baris merah
  "Server tidak membalas JSON: …" dengan 200 karakter pertama body — bukan
  diam-diam gagal.

## 6. Keputusan desain (jangan diubah tanpa membaca ini)

1. **Dua SKU, bukan satu.** SKU seharusnya sebenarnya bisa dibaca dari resi,
   tetapi tetap diminta sebagai penjaga batch: resi nyasar yang ikut terscan
   ditolak (#4), bukan diam-diam tercatat. SKU terambil wajib karena itulah
   yang dicetak di slip; tanpa itu slip hanya bertuliskan "SALAH AMBIL".
2. **Tolak, jangan toleransi.** Semua kondisi #3–#7 menolak. Menu ini khusus
   resi spesial; resi campuran, resi yang sudah packing, resi tanpa picker,
   dan resi yang sudah dilaporkan (status apa pun) diarahkan ke jalur biasa.
3. **Tidak memakai `Packer_fcd::save_masalah_picker()`.** Fungsi itu
   meng-UPDATE baris lama dan mereset `status` ke 0 — bertentangan dengan #7.
   Model baru hanya INSERT.
4. **Tidak ada tombol batal (v1).** `DELETE` dilarang di proyek ini; soft-delete
   lewat status baru butuh mengubah query `Kpi_reports`, `Error_recap_fcd`,
   `Laporan_fcd` yang membaca `tblmasalahpicker` tanpa filter status. Penjaga
   dua SKU + 7 validasi dianggap cukup. Kalau kelak dibuat: status 2
   "dibatalkan packer", hanya oleh pelapor, hanya saat masih pending, dan
   ketiga query itu wajib mengecualikannya.
5. **Controller/model/view terpisah dari `Packer.php`** (1.600+ baris, halaman
   scannya sensitif timing). Tidak ada file Packer yang disentuh.
6. **Bentuk baris identik dengan modal Masalah Picker** (`qty` 1,
   `qty_bermasalah` 1, `id_typemasalah` 4, `status` 0) supaya hilir (CS, KPI,
   Error Recap, Restock) tidak berubah.
7. **Role 1 & 4 dikunci ganda**: `roleaccess` (migrasi) dan
   `Salah_ambil_special::ROLE_BOLEH` (URL bisa dibuka langsung); penolakan
   tetap JSON valid (`akses_ditolak` untuk halaman, 403 body untuk endpoint).
8. **Pencocokan SKU tidak peduli huruf** (`strcasecmp`, query `tblsku` ikut
   collation); ejaan yang disimpan selalu dari `tblsku`.

## 7. Data yang ditulis

| Tabel | Oleh | Isi |
|---|---|---|
| `tblmasalahpicker` | `simpan_salah_ambil()` | `id_printresi`, `noresi`, `sku` = SKU seharusnya, `qty` 1, `id_typemasalah` 4, `qty_bermasalah` 1, `sku_salah` = SKU terambil, `status` 0, `created_by` = user packer, `created` = now |
| `menu`, `roleaccess` | migrasi `run_salah_ambil_special_migration()` (sekali, `BOOTSTRAP_VERSI 2026-09-18.4`) | menu `salah-ambil-special` di grup TIM PACKER, role 1 & 4 |

Tidak ada tabel/kolom baru. Resi yang ditolak tidak menulis apa pun. Yang
dibaca: `tblsku`, `tblprintresi`, `tbldetailprintresi`, `tblpacking`,
`tblresiambilbarang`, `tblpegawai`, `tbluser`. Skema `tblmasalahpicker`:
`DATABASE_STRUCTURE.md` §4a. Cadangan SQL manual untuk menu + akses (kalau
migrasi otomatis tidak terpicu): `sql_migrations/salah_ambil_special_menu.sql`.

## 8. Skenario ringkas

| # | Situasi | Hasil |
|---|---|---|
| A | 30 resi BSBI-4 terambil BSBI-5 | isi BSBI-4 / BSBI-5, Kunci, scan 30 resi → 30 baris hijau → CS proses → slip per picker "SALAH AMBIL (terambil BSBI-5)" |
| B | Resi BSBI-6 ikut terscan di batch itu | merah "SKU resi BSBI-6, bukan BSBI-4", tidak tersimpan |
| C | Scanner menembak dua kali resi yang sama | kedua merah "Sudah dilaporkan (pending)"; satu baris di DB |
| D | Resi campuran (2 SKU) ikut terscan | merah "Bukan resi spesial (2 SKU, qty 2)" → laporkan lewat modal Masalah Picker |
| E | Resi sudah dipacking | merah "Resi sudah di-packing" — dicurigai salah scan |
| F | Resi belum pernah di-scan picker | merah "Resi belum di-scan ambil picker" → jalur lost scan / picker dulu |
| G | Resi sudah pernah dilaporkan & diproses CS | merah "Sudah dilaporkan (sudah diproses CS)" — kalau memang salah ambil lagi, lewat modal Masalah Picker |
| H | Ketik kode SKU yang tidak ada | Kunci ditolak "tidak ada di master SKU"; gunakan saran dropdown |
| I | Packer salah scan resi yang lolos semua validasi | tidak bisa dibatalkan di menu ini; lapor ke CS (keputusan desain 4) |
| J | Ganti batch (SKU lain) | Ganti SKU → confirm → kunci ulang; tabel sesi kosong, data lama tetap di DB |

## 9. Menu, hak akses, endpoint

| Menu | URI | Induk | Role | Migrasi |
|---|---|---|---|---|
| Salah Ambil Special | `salah-ambil-special` | TIM PACKER (induk `packer/scan_packer`, cadangan 24), `sortorder` 12 | 1 webmaster, 4 client packer | `run_salah_ambil_special_migration()` |

| Endpoint | Method | Fungsi |
|---|---|---|
| `salah-ambil-special` | GET | halaman (JSON `{view}` via `show()`) |
| `salah-ambil-special/cari-sku?term=` | GET | saran SKU, array polos |
| `salah-ambil-special/cek-sku` | POST `sku_benar, sku_salah` | validasi pasangan → 200 + nama/rak, atau 400 |
| `salah-ambil-special/scan-resi` | POST `noresi, sku_benar, sku_salah` | 7 validasi + INSERT → 201, atau 400/404/409/422/500 |

## 10. File terkait

| Lapisan | File |
|---|---|
| Controller | `application/controllers/Salah_ambil_special.php` |
| Model | `application/models/Salah_ambil_special_fcd.php` (`cari_sku`, `cari_sku_mirip`, `resi_terbaru`, `detail_resi`, `sudah_packing`, `picker_resi`, `laporan_ada`, `kunci_resi`, `simpan_salah_ambil`) |
| View | `application/views/salah_ambil_special/index.php` |
| Route | `application/config/routes.php` (4 baris `salah-ambil-special*`) |
| Migrasi | `application/core/MY_Controller.php` — `run_salah_ambil_special_migration()`, `BOOTSTRAP_VERSI` `2026-09-18.4` |
| Hilir (tidak diubah) | `Masalah_picker_new.php` / `Cs.php` (daftar & slip), `Kpi_reports.php`, `Error_recap_fcd.php`, `Restock_fcd.php` |
| Spesifikasi & rencana | `docs/superpowers/specs/2026-09-18-salah-ambil-special-design.md`, `docs/superpowers/plans/2026-09-18-salah-ambil-special.md` |
| Rilis produksi | `docs/PANDUAN_PULL_PRODUKSI.md` Bagian D |

## 11. Menguji di mesin lokal

Tidak ada test suite; `php -l` + uji manual. Script data dummy
`dev_tools/dummy_salah_ambil_special.sql` (gitignored, hanya `INSERT`, dijaga
`NOT EXISTS`) membuat 9 resi `DUMMYSAS_*` yang menutup semua skenario:
`OK_1..3` (valid), `2SKU`, `QTY2`, `SKULAIN`, `PACKED`, `BELUMPICK`,
`DILAPORKAN`. SKU-nya diambil dari 3 kode nyata pertama di `tblsku` yang
punya rak dan dicetak di akhir script. Jalankan dari PowerShell:

```powershell
Get-Content dev_tools/dummy_salah_ambil_special.sql -Raw | & "C:\xampp\mysql\bin\mysql.exe" -u root -t "<nama_db_lokal>"
```

Urutan uji yang dipakai 18 Sep 2026: kunci (kosong / sama / tidak ada /
valid) → scan tiap resi ditolak → `OK_1` hijau → `OK_1` lagi ditolak →
`OK_2`,`OK_3` cepat berurutan → cek Daftar Masalah Picker New (3 baris,
SKU Salah terisi, Preview menampilkan "terambil …") → Ganti SKU.
