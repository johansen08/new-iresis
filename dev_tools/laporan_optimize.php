<?php
$db = new mysqli('127.0.0.1','root','','iresis-prod');
if($db->connect_error) die($db->connect_error);

$sqls = [
    'ALTER TABLE tblresiretur ADD INDEX idx_status_tgl (status_retur, tanggal_resiretur)',
    'ALTER TABLE tblbukaretur ADD INDEX idx_buka_tgl (resi_buka, tanggal_buka_retur)',
];
foreach($sqls as $sql){
    if(!$db->query($sql)) echo 'Skip: '.$db->error.PHP_EOL;
    else echo 'OK: '.$sql.PHP_EOL;
}
$db->close();
echo 'Done'.PHP_EOL;
