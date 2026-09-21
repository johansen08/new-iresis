# Standard Pengembangan & Troubleshooting (iresis-dev)

Dokumen ini mendokumentasikan standar teknis yang harus diikuti untuk memastikan aplikasi stabil, terutama saat menangani operasi berat (seperti upload file besar) dan komunikasi AJAX.

## 1. Komunikasi AJAX & Respon JSON

Masalah format respon yang tidak valid ("Unexpected token <") sering disebabkan oleh *leakage* output (seperti spasi, PHP Warnings, atau error HTML).

### **Standar:**
Setiap fungsi controller yang merespon AJAX **WAJIB** menggunakan pembersihan output buffer secara total sebelum mengirim JSON.

**Implementasi (di `MY_Controller`):**
```php
public function make_ajax_response($status_code, $message, $data = []) {
    // Nuclear clear: hapus semua level buffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    set_status_header($status_code);
    echo json_encode(['message' => $message, 'data' => $data]);
    exit();
}
```

---

## 2. Penanganan Operasi Berat (Large Data/Files)

Operasi seperti upload SKU atau export laporan massal membutuhkan sumber daya lebih besar.

### **Standar:**
1. **Memory & Time Limit:** Set secara eksplisit di awal fungsi controller.
   ```php
   ini_set('memory_limit', '3072M'); // Gunakan standar 3GB untuk iresis
   set_time_limit(0);               // unlimited
   ```
2. **AJAX Timeout:** Pastikan di sisi JavaScript, `timeout` diatur cukup panjang.
   ```javascript
   $.ajax({
       // ...
       timeout: 600000, // 10 menit
   });
   ```

---

## 3. Keamanan & Stabilitas Database

Kesalahan database sering menghentikan eksekusi dengan tampilan halaman HTML error CodeIgniter, yang merusak respon AJAX.

### **Standar:**
1. **Disable db_debug:** Matikan `db_debug` selama operasi krusial agar error bisa ditangkap manual.
   ```php
   $db_debug = $this->db->db_debug;
   $this->db->db_debug = FALSE;
   // ... proses db ...
   $this->db->db_debug = $db_debug; // kembalikan status awal
   ```
2. **Transaksi:** Selalu gunakan `trans_start()` dan `trans_complete()` untuk menjamin integritas data.

---

## 4. Standar UI/UX untuk Upload

1. **Instruksi Kolom:** Tampilkan urutan kolom Excel yang eksplisit (A, B, C...) di layar upload agar user tidak salah format.
2. **Progress Tracking:** Gunakan file progress atau session untuk memberikan feedback visual kepada user saat memproses ribuan baris.
3. **Raw Debugging:** Jika AJAX gagal parsing JSON, tampilkan cuplikan respon mentah (*raw response*) di konsol atau layar untuk memudahkan diagnosa.

---

## 5. Aset Pihak Ketiga Harus Lokal (Tanpa CDN)

Aplikasi dipakai 16 PC di LAN dan harus tetap berfungsi penuh saat internet putus. Sejak 21 Sep 2026 tidak ada lagi aset yang dimuat dari CDN (jsdelivr, cdnjs, Google Fonts, js.pusher.com).

### **Standar:**

1. **Dilarang `<script src="https://...">`, `<link href="https://...">`, atau `@import url(https://...)`** di view/CSS. Unduh library ke `assets/js/plugins/<nama>/` dan `assets/css/<nama>/`, referensikan dengan path relatif `assets/...`.
2. **Font web** (Inter, Outfit, Open Sans) sudah ada di `assets/fonts/*.woff2` dan dideklarasikan di `assets/css/fonts-lokal.css` (dimuat `main.php` dan di-`@import` semua `theme-*.css`). Butuh font baru: unduh woff2 subset latin/latin-ext, tambahkan `@font-face` di file itu.
3. **Font Awesome 4.7.0** lokal di `assets/css/fontawesome/` + `assets/css/fonts/`, sudah termuat lewat `theme-*.css` — jangan tambahkan link FA lagi di view.
4. Library yang sudah tersedia lokal: moment 2.18.1, daterangepicker 3.1.0, Chart.js 4.4.0, html2canvas 1.4.1, SheetJS xlsx 0.18.5, pusher-js 8.0.2, blueimp-gallery 2.41.0.
5. Panggilan keluar dari PHP (mis. Pusher) **wajib timeout pendek** (`Pusher_lib`: 5 detik) dan dibungkus try/catch agar request user tidak menggantung saat internet mati.
6. **Foto produk** (`tblsku.link_foto`, URL object storage Jubelio). Urutan: browser mencoba **URL asli dulu**, salinan lokal hanya cadangan saat URL gagal atau tidak selesai 4 detik (`assets/js/foto_sku.js`, dimuat `main.php`). Salinan disimpan di `C:oto-produk\<md5(url)>.<ext>` (kunci `foto_produk_dir` di secrets.php), diisi `cron/sinkron_foto_sku` (task scheduler *IRESIS - Sinkron foto SKU*, harian 23.00 via `scripts/sinkron_foto_sku_senyap.vbs`; log `C:oto-produk\sinkron.log`) yang juga mencatat nama berkas ke kolom **`tblsku.foto_lokal`**. Apache melayani folder itu langsung lewat `Alias /foto-produk/` (`C:
mpp\apache\confxtra\httpd-iresis-foto-produk.conf`, cache browser 30 hari) — tanpa PHP, jadi ringan. Di kode: query ikutkan `s.foto_lokal` di samping `s.link_foto`; HTML server pakai **`foto_sku_img($link_foto, $foto_lokal, $attr)`**, JSON kirim `foto_lokal => foto_sku_lokal_url($link_foto, $foto_lokal)` lalu view menaruhnya di atribut `data-foto-lokal` pada `<img>` (juga saat mengganti `src` gambar modal). Jangan tulis `<img src=link_foto>` polos.
7. Cek cepat sebelum commit: `grep -rn "cdnjs\|jsdelivr\|googleapis\|unpkg" application/views assets/css assets/js/*.js` harus kosong.

---

## 6. Alur Git (Branching)

Branch utama proyek ini adalah **`master`**. Branch `development` sudah dihapus (dimerge penuh ke `master` pada 2026-09-12) — jangan dibuat lagi.

### **Standar:**
1. **Satu branch per pekerjaan**, selalu lahir dari `master`:
   ```bash
   git switch master
   git switch -c feature/nama-fitur   # untuk fitur baru
   git switch -c fix/nama-masalah     # untuk perbaikan bug
   ```
2. **Format pesan commit:** `tipe(modul): deskripsi singkat bahasa Indonesia`. Tipe yang dipakai di repo ini: `feat`, `fix`, `docs`, `chore`.
   ```bash
   git commit -m "feat(retur): kartu ringkasan verifikasi per-baris"
   ```
3. **Merge balik ke `master` dengan `--no-ff`** setelah diuji langsung di browser, supaya jejak branch tetap terbaca di riwayat:
   ```bash
   git switch master
   git merge --no-ff feature/nama-fitur
   git branch -d feature/nama-fitur
   ```
4. **Hapus branch dengan `-d`, JANGAN `-D`.** Huruf kecil akan menolak jika masih ada commit yang belum termerge — itu pengaman agar tidak ada pekerjaan yang hilang.
5. **Belum ada remote.** Seluruh riwayat hanya tersimpan di PC ini dan belum punya backup off-site.

---

*Dokumen ini diperbarui terakhir: 2026-09-12*
