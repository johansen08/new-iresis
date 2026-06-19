<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
$res = $conn->query("SELECT * FROM tblsp");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
