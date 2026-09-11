-- Revisions for Scan Picker Summary and Packer Speed Monitoring

-- Add Monitoring Menu
INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive) 
VALUES (24, 'Monitoring Speed', 'packer_monitoring', 'fa fa-clock-o', 30, 1);

-- Get ID of the new menu
SET @menu_id = LAST_INSERT_ID();

-- Grant access to Admin and Purchasing (as requested)
INSERT INTO roleaccess (menuid, roleid) VALUES (@menu_id, '1'), (@menu_id, '2'), (@menu_id, '7');

-- Add Report Menu
INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive) 
VALUES (24, 'Laporan Speed', 'packer_monitoring/report', 'fa fa-file-text-o', 40, 1);

-- Get ID of the new report menu
SET @report_menu_id = LAST_INSERT_ID();

-- Grant access to Admin and Purchasing
INSERT INTO roleaccess (menuid, roleid) VALUES (@report_menu_id, '1'), (@report_menu_id, '2'), (@report_menu_id, '7');

-- Ensure the tables for packer monitoring have comments column and is_deleted
-- (Assuming they were created but might be missing these if it was a partial manual creation earlier)
-- Based on DESCRIBE, they exist, but let's be sure about the is_deleted and komentar.

-- If they don't have it, add them (using IF NOT EXISTS logic via a trick or just assuming since DESCRIBE showed them)
-- DESCRIBE showed: komentar text, is_deleted tinyint(1).
