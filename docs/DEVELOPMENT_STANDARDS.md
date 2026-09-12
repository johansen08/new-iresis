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

## 5. Alur Git (Branching)

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
