<?php
$source_db = 'iresis-prod';
$target_db = 'iresis-v10';
$mysql_path = 'C:\xampp\mysql\bin\mysql.exe';
$mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe';

$conn = new mysqli('127.0.0.1', 'root', '');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "Creating database if not exists: $target_db\n";
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$target_db`")) {
    die("Error creating database: " . $conn->error);
}

$dump_file = 'db_clone_temp.sql';
echo "Dumping source database: $source_db\n";
exec("$mysqldump_path -u root $source_db > $dump_file", $output, $return_var);
if ($return_var !== 0) {
    die("Error dumping database. Code: $return_var");
}

echo "Restoring to target database: $target_db\n";
exec("$mysql_path -u root $target_db < $dump_file", $output, $return_var);
if ($return_var !== 0) {
    unlink($dump_file);
    die("Error restoring database. Code: $return_var");
}

unlink($dump_file);
echo "Database cloning completed successfully.\n";
