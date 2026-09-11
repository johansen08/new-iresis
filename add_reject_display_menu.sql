-- 1. Insert the new menu item
INSERT INTO menu (parentid, name, uri, icon, sortorder, description, isactive, createdby, created)
VALUES (77, 'Laporan Reject Display', 'restock/laporan-reject-display', 'fa fa-ban', 2, 'Laporan khusus untuk barang reject display dari picker', 1, 1, NOW());

-- 2. Get the new menu ID
SET @new_menuid = LAST_INSERT_ID();

-- 3. Copy permissions from "Laporan Masalah Picker" (ID 78) to the new menu
INSERT INTO roleaccess (menuid, roleid, createdby, created)
SELECT @new_menuid, roleid, createdby, NOW()
FROM roleaccess
WHERE menuid = 78;

-- 4. Update sibling sort orders to make space if needed (optional but good practice)
-- Currently Laporan Masalah Picker is 1, let's make the new one 2 and move others.
UPDATE menu SET sortorder = sortorder + 1 WHERE parentid = 77 AND sortorder >= 2 AND id != @new_menuid;
