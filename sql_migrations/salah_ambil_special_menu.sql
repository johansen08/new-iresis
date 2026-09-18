-- Menu TIM PACKER -> "Salah Ambil Special" + hak akses (rilis 18 Sep 2026 sore).
--
-- BIASANYA TIDAK PERLU DIJALANKAN. Isi file ini sama persis dengan yang
-- dijalankan otomatis oleh MY_Controller::run_salah_ambil_special_migration()
-- pada request pertama setelah pull (BOOTSTRAP_VERSI 2026-09-18.4). Pakai file
-- ini hanya kalau penanda application/cache/bootstrap_migrasi.txt tidak naik
-- ke 2026-09-18.4 dan menu tidak muncul (lihat PANDUAN_PULL_PRODUKSI.md D.2).
--
-- Aman diulang: semua INSERT dijaga NOT EXISTS. Tidak ada tabel/kolom baru;
-- menu ini menulis ke tblmasalahpicker yang sudah ada.

-- Induk = induk menu Scan Resi Packer (grup TIM PACKER); 24 hanya cadangan.
SET @parent_id = COALESCE(
    (SELECT parentid FROM menu WHERE uri = 'packer/scan_packer' ORDER BY id ASC LIMIT 1),
    24
);

INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, createdby, created)
SELECT @parent_id, 'Salah Ambil Special', 'salah-ambil-special', 'fa fa-exchange', 12, 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE uri = 'salah-ambil-special');

SET @menu_id = (SELECT id FROM menu WHERE uri = 'salah-ambil-special' ORDER BY id ASC LIMIT 1);

-- Role 1 = webmaster, 4 = client packer (sama dengan Scan Resi Packer (Webcam)).
-- Harus sejalan dengan Salah_ambil_special::ROLE_BOLEH.
INSERT INTO roleaccess (roleid, menuid, created, createdby)
SELECT r.roleid, @menu_id, NOW(), 1
FROM (SELECT 1 AS roleid UNION ALL SELECT 4) r
WHERE NOT EXISTS (SELECT 1 FROM roleaccess a WHERE a.roleid = r.roleid AND a.menuid = @menu_id);

-- Verifikasi: satu baris, roles = 1,4
SELECT m.id, m.name, m.uri, m.parentid, m.isactive,
       GROUP_CONCAT(a.roleid ORDER BY a.roleid) AS roles
FROM menu m LEFT JOIN roleaccess a ON a.menuid = m.id
WHERE m.uri = 'salah-ambil-special'
GROUP BY m.id;
