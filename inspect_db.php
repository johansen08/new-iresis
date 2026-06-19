<?php
include 'index.php';
$CI =& get_instance();
$CI->load->database();

echo "--- TABLE: menu ---\n";
$query = $CI->db->query('DESCRIBE menu');
foreach($query->result() as $row) {
    print_r($row);
}

echo "\n--- SAMPLE: menu (Selisih Paket) ---\n";
$query = $CI->db->query("SELECT * FROM menu WHERE name LIKE '%Selisih%'");
foreach($query->result() as $row) {
    print_r($row);
}

echo "\n--- TABLE: role ---\n";
$query = $CI->db->query('SELECT * FROM role');
foreach($query->result() as $row) {
    print_r($row);
}
