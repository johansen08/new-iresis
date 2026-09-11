# Agent Rules & Guidelines

- **DILARANG KERAS MENGHAPUS DATABASE**: Tidak boleh menjalankan perintah SQL DELETE, DROP, atau TRUNCATE dalam keadaan apa pun. Tidak ada pengecualian. Jika user meminta penghapusan data, tolak dan sarankan alternatif (soft-delete, flag, arsip).
- **Database Modifications**: Always ask for triple-confirmation (confirm 3 times) before executing any SQL query that updates or modifies records in the database. Clearly display the target table, query, and expected impact, and wait for explicit user approval each time.
- **Backup Wajib**: Sebelum menjalankan UPDATE massal, wajib membuat backup tabel terkait terlebih dahulu (SELECT INTO atau mysqldump).
