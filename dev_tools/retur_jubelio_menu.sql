-- =====================================================================
-- Tambah menu "Validasi Jubelio" di bawah parent TIM RETUR (id 30).
-- Hak akses (roleaccess) disalin dari menu "Laporan Retur" (id 60)
-- supaya role yang sama bisa mengaksesnya.
-- =====================================================================

INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (30, 'Validasi Jubelio', 'retur/validasi-jubelio', 'fa fa-check-square-o', 25,
        'Validasi & rekonsiliasi retur antara scan iresis dan file Excel Jubelio', 1, 1, NOW());

SET @new_menuid = LAST_INSERT_ID();

INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT @new_menuid, roleid, createdby, NOW()
FROM roleaccess
WHERE menuid = 60;
