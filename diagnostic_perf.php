<?php
include 'index.php';
$CI =& get_instance();
$CI->load->database();

$tables = ['tblprintresi', 'tbldetailprintresi', 'tblresiambilbarang', 'tblpacking', 'tblresikeluar', 'tblsku'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $count = $CI->db->query("SELECT COUNT(*) as total FROM $table")->row()->total;
    echo "Count: $count\n";
    
    echo "Indexes:\n";
    $indexes = $CI->db->query("SHOW INDEX FROM $table")->result();
    foreach ($indexes as $idx) {
        echo " - " . $idx->Key_name . " (" . $idx->Column_name . ")\n";
    }
    echo "\n";
}
