-- =====================================================
-- MENU BARU: Purchasing, Accounting, Inbound, Finance
-- =====================================================

-- LANGKAH 1: Tambah Role Baru
INSERT IGNORE INTO tblhakakses (akses) VALUES 
    ('tim purchasing'),
    ('tim accounting'),
    ('tim inbound'),
    ('tim finance');

-- LANGKAH 2: Ambil ID Role
SET @role_webmaster    = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'webmaster' LIMIT 1);
SET @role_admin        = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'admin' LIMIT 1);
SET @role_purchasing   = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'tim purchasing' LIMIT 1);
SET @role_accounting   = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'tim accounting' LIMIT 1);
SET @role_inbound      = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'tim inbound' LIMIT 1);
SET @role_finance      = (SELECT id_hakakses FROM tblhakakses WHERE akses = 'tim finance' LIMIT 1);

-- =====================================================
-- LANGKAH 3: TIM PURCHASING  (parent menu)
-- =====================================================
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (1, 'TIM PURCHASING', NULL, 'fa fa-shopping-cart', 120, 'Menu Tim Purchasing', 1, 1, NOW());
SET @parent_purchasing = LAST_INSERT_ID();

-- Sub-menu Purchasing
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created) VALUES
    (@parent_purchasing, 'Pengembalian QC',  'purchasing/pengembalian-qc',  'fa fa-reply',         10, '', 1, 1, NOW()),
    (@parent_purchasing, 'Repair',           'purchasing/repair',           'fa fa-wrench',        20, '', 1, 1, NOW()),
    (@parent_purchasing, 'Reject',           'purchasing/reject',           'fa fa-ban',           30, '', 1, 1, NOW()),
    (@parent_purchasing, 'Laporan Repair',   'purchasing/laporan-repair',   'fa fa-file-text-o',   40, '', 1, 1, NOW()),
    (@parent_purchasing, 'Laporan Reject',   'purchasing/laporan-reject',   'fa fa-file-text-o',   50, '', 1, 1, NOW());

-- Role Access Purchasing
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_webmaster, 1, NOW() FROM menu WHERE parentid = @parent_purchasing;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_admin, 1, NOW() FROM menu WHERE parentid = @parent_purchasing;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_purchasing, 1, NOW() FROM menu WHERE parentid = @parent_purchasing;

-- Parent Menu Purchasing access
INSERT INTO roleaccess (menuid, roleid, createdby, created) VALUES
    (@parent_purchasing, @role_webmaster, 1, NOW()),
    (@parent_purchasing, @role_admin, 1, NOW()),
    (@parent_purchasing, @role_purchasing, 1, NOW());


-- =====================================================
-- LANGKAH 4: TIM ACCOUNTING  (parent menu)
-- =====================================================
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (1, 'TIM ACCOUNTING', NULL, 'fa fa-calculator', 130, 'Menu Tim Accounting', 1, 1, NOW());
SET @parent_accounting = LAST_INSERT_ID();

-- Sub-menu Accounting
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created) VALUES
    (@parent_accounting, 'Unggah Surat Jalan',     'accounting/form-unggah-surat-jalan',   'fa fa-upload',        10, '', 1, 1, NOW()),
    (@parent_accounting, 'Riwayat Surat Jalan',    'accounting/riwayat-surat-jalan',       'fa fa-history',       20, '', 1, 1, NOW()),
    (@parent_accounting, 'SJ Kurangan Picker',     'accounting/riwayat-surat-jalan-tp',    'fa fa-file-text-o',   30, '', 1, 1, NOW()),
    (@parent_accounting, 'Laporan Kurangan Picker','accounting/laporan-kurangan-picker',   'fa fa-clipboard',     40, '', 1, 1, NOW()),
    (@parent_accounting, 'Pergantian Barang',      'accounting/pergantian-barang',         'fa fa-exchange',      50, '', 1, 1, NOW());

-- Role Access Accounting
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_webmaster, 1, NOW() FROM menu WHERE parentid = @parent_accounting;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_admin, 1, NOW() FROM menu WHERE parentid = @parent_accounting;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_accounting, 1, NOW() FROM menu WHERE parentid = @parent_accounting;

-- Parent Menu Accounting access
INSERT INTO roleaccess (menuid, roleid, createdby, created) VALUES
    (@parent_accounting, @role_webmaster, 1, NOW()),
    (@parent_accounting, @role_admin, 1, NOW()),
    (@parent_accounting, @role_accounting, 1, NOW());


-- =====================================================
-- LANGKAH 5: TIM INBOUND  (parent menu)
-- =====================================================
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (1, 'TIM INBOUND', NULL, 'fa fa-inbox', 140, 'Menu Tim Inbound', 1, 1, NOW());
SET @parent_inbound = LAST_INSERT_ID();

-- Sub-menu Inbound
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created) VALUES
    (@parent_inbound, 'SJ Kurangan Picker', 'inbound/laporan-surat-jalan-tp', 'fa fa-file-text-o', 10, '', 1, 1, NOW()),
    (@parent_inbound, 'Riwayat Surat Jalan','inbound/riwayat-surat-jalan',    'fa fa-history',     20, '', 1, 1, NOW());

-- Role Access Inbound
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_webmaster, 1, NOW() FROM menu WHERE parentid = @parent_inbound;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_admin, 1, NOW() FROM menu WHERE parentid = @parent_inbound;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_inbound, 1, NOW() FROM menu WHERE parentid = @parent_inbound;

-- Parent Menu Inbound access
INSERT INTO roleaccess (menuid, roleid, createdby, created) VALUES
    (@parent_inbound, @role_webmaster, 1, NOW()),
    (@parent_inbound, @role_admin, 1, NOW()),
    (@parent_inbound, @role_inbound, 1, NOW());


-- =====================================================
-- LANGKAH 6: TIM FINANCE  (parent menu)
-- =====================================================
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (1, 'TIM FINANCE', NULL, 'fa fa-money', 150, 'Menu Tim Finance', 1, 1, NOW());
SET @parent_finance = LAST_INSERT_ID();

-- Sub-menu Finance
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created) VALUES
    (@parent_finance, 'Pergantian Barang', 'finance/pergantian-barang', 'fa fa-exchange', 10, '', 1, 1, NOW()),
    (@parent_finance, 'Denda',             'finance/denda',             'fa fa-money',    20, '', 1, 1, NOW());

-- Role Access Finance
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_webmaster, 1, NOW() FROM menu WHERE parentid = @parent_finance;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_admin, 1, NOW() FROM menu WHERE parentid = @parent_finance;
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT id, @role_finance, 1, NOW() FROM menu WHERE parentid = @parent_finance;

-- Parent Menu Finance access
INSERT INTO roleaccess (menuid, roleid, createdby, created) VALUES
    (@parent_finance, @role_webmaster, 1, NOW()),
    (@parent_finance, @role_admin, 1, NOW()),
    (@parent_finance, @role_finance, 1, NOW());


-- =====================================================
-- VERIFIKASI: Show semua menu yang baru dibuat
-- =====================================================
SELECT m.id, m.parentid, p.name as parent_name, m.name, m.uri, m.sortorder
FROM menu m
LEFT JOIN menu p ON p.id = m.parentid
WHERE m.parentid IN (@parent_purchasing, @parent_accounting, @parent_inbound, @parent_finance)
   OR m.id IN (@parent_purchasing, @parent_accounting, @parent_inbound, @parent_finance)
ORDER BY m.parentid, m.sortorder;
