<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$tables = ['tblsp', 'tblmasterstatusperforma', 'tblsku', 'tblprintresi', 'tbldetailprintresi', 'tblresiambilbarang', 'tblpacking'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $query = ($table === 'tblsku') ? "SHOW FULL COLUMNS FROM $table" : "DESCRIBE $table";
    $res = $conn->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            if ($table === 'tblsku') {
                print_r($row);
            } elseif ($table === 'tblmasterstatusperforma') {
                print_r($row);
            } else {
                echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
        if ($table === 'tblmasterstatusperforma') {
            echo "--- DATA ---\n";
            $data_res = $conn->query("SELECT * FROM $table");
            while($data_row = $data_res->fetch_assoc()) {
                print_r($data_row);
            }
        }
    } else {
        echo "  Table not found or error.\n";
    }
    echo "\n";
}
