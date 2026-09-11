-- Menu entry for Ekspedisi Urgent (sub-menu of Laporan Operasional)
SET @parentId = (SELECT id FROM menu WHERE name = 'Laporan Operasional' LIMIT 1);
SET @sortOrder = (SELECT COALESCE(MAX(sortorder), 0) + 1 FROM menu WHERE parentid = @parentId);

INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive)
VALUES (@parentId, 'Ekspedisi Urgent', 'laporan/ekspedisi-urgent', 'fa fa-exclamation-triangle', @sortOrder, 1);

SET @menuId = LAST_INSERT_ID();
INSERT INTO roleaccess (roleid, menuid) VALUES (1, @menuId);
