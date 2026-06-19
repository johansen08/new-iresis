<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SELECT * FROM tblsku WHERE 
    id_sku LIKE '%special%' OR 
    nama_sku LIKE '%special%' OR 
    nama_bundle LIKE '%special%' OR 
    bundle LIKE '%special%' OR 
    variasi LIKE '%special%' OR 
    lokasi LIKE '%special%' OR 
    no_rak LIKE '%special%'
    LIMIT 10");

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No SKU with 'special' found in any text column.\n";
    
    // Check all columns again, maybe I missed one?
    $res = $conn->query("SHOW COLUMNS FROM tblsku");
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
