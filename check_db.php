<?php
$conn = mysqli_connect('localhost', 'root', '', 'iresis-prod');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$tables = ['tblresiambilbarang', 'tblpacking', 'tblresikeluar'];

foreach ($tables as $table) {
    echo "--- Indexes on $table ---\n";
    $res = mysqli_query($conn, "SHOW INDEX FROM $table");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "Table: {$row['Table']}, Non_unique: {$row['Non_unique']}, Key_name: {$row['Key_name']}, Column_name: {$row['Column_name']}\n";
        }
    } else {
        echo "Error showing indexes for $table: " . mysqli_error($conn) . "\n";
    }
    echo "\n";
}

mysqli_close($conn);
?>
