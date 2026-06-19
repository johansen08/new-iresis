<?php
$source_db = 'iresis-prod';
$target_db = 'iresis-v10';
$table = 'tblresiretur';

$conn = new mysqli('127.0.0.1', 'root', '');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Syncing table '$table' from '$source_db' to '$target_db'...\n";

// 1. Check if table exists in target
$check = $conn->query("SHOW TABLES FROM `$target_db` LIKE '$table'");
if ($check->num_rows == 0) {
    // If table doesn't exist, create it like the source
    echo "Table '$table' does not exist in target. Creating...\n";
    $create_sql = $conn->query("SHOW CREATE TABLE `$source_db`.`$table`")->fetch_row()[1];
    // Adjust Create SQL database context if necessary, but usually it's just CREATE TABLE `tbl`...
    // We need to run this inside target DB
    $conn->select_db($target_db);
    if (!$conn->query($create_sql)) {
         die("Error creating table: " . $conn->error);
    }
    echo "Table created.\n";
} else {
    // 2. Truncate target table
    echo "Truncating target table...\n";
    if (!$conn->query("TRUNCATE TABLE `$target_db`.`$table`")) {
        die("Error truncating table: " . $conn->error);
    }
}

// 3. Copy data
echo "Copying data...\n";
$sql = "INSERT INTO `$target_db`.`$table` SELECT * FROM `$source_db`.`$table`";
if ($conn->query($sql)) {
    echo "Successfully synced '$table'. Rows affected: " . $conn->affected_rows . "\n";
} else {
    die("Error copying data: " . $conn->error);
}

$conn->close();
?>
