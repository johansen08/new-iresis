# sql_migrations — arsip migrasi manual yang SUDAH diterapkan

Semua file di folder ini sudah dijalankan di database produksi (`iresis_prod`)
pada tanggal yang tercantum di nama/isi filenya (Mar–Jul 2026). Jangan
dijalankan ulang; disimpan hanya sebagai catatan skema dan asal-usul menu.

Mekanisme migrasi yang **masih hidup** bukan folder ini, melainkan
`MY_Controller::jalankan_bootstrap_sekali()` — deretan `run_*_migrations()`
yang dijaga konstanta `BOOTSTRAP_VERSI` (lihat CLAUDE.md §Bootstrap).
Perubahan skema/menu baru ditambahkan di sana, bukan sebagai file SQL di sini.

Catatan DB operasional (hapus indeks, pindah tabel arsip, dsb.) ada di
`dev_tools/sql/` (tidak di-commit).
