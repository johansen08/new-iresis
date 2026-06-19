<?php
$m = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
if ($m->connect_error) die("Connect Error: " . $m->connect_error);

$tables = ['tblprintresi', 'tbldetailprintresi', 'tblresiambilbarang', 'tblpacking', 'tblresikeluar', 'tblsku'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $count = $m->query("SELECT COUNT(*) FROM $table")->fetch_row()[0];
    echo "Count: $count\n";
    
    echo "Indexes:\n";
    $res = $m->query("SHOW INDEX FROM $table");
    while ($idx = $res->fetch_assoc()) {
        echo " - " . $idx['Key_name'] . " (" . $idx['Column_name'] . ")\n";
    }
    
    echo "Columns types:\n";
    $res = $m->query("DESC $table");
    while ($col = $res->fetch_assoc()) {
        if (strpos($col['Field'], 'tanggal') !== false || strpos($col['Field'], 'deadline') !== false) {
            echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
    echo "\n";
}
