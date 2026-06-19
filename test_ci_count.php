<?php
// Mocking CI environment for a quick test if possible, 
// or just using the mysqli to see if the query CI would generate is valid.

// But wait, it's better to just use the actual CI if I can.
// Since I can't easily run a CI method from CLI without issues, 
// I'll check how count_all_results is implemented in system/database/DB_query_builder.php if it exists.

// Alternatively, I'll just check if my test query with aliases worked (it did).

// Wait, I see something else!
// In Receipt_fcd.php line 636:
// return $this->db->count_all_results("tblprintresi a");

// If $data['search'] is NOT empty, joins are added.
// If aliases are used in joins (a.id_printresi), but the table is "tblprintresi a", 
// CI might generate "SELECT COUNT(*) FROM tblprintresi a WHERE ...". This IS valid SQL.

// HOWEVER, what if start_date/end_date are causing issues?
// I'll test the count query with the joins in a script.
?>
