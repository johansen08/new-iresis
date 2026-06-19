<?php
// Simulation of the closure functions in Receipt_fcd.php

$excelDateToPhpDate = function ($excelDate) {
    if (is_numeric($excelDate)) {
        $unixDate = ($excelDate - 25569) * 86400;
        return gmdate("d/m/Y", $unixDate);
    }
    return $excelDate;
};

$excelTimeToPhpTime = function ($excelTime) {
    if (is_numeric($excelTime)) {
        $totalSeconds = (int) round($excelTime * 86400);
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
    return $excelTime;
};

$combineDateTime = function ($date, $time) {
    if (!$date || !$time) return null;
    echo "Combining Date: '$date' with Time: '$time'\n";
    $dt = DateTime::createFromFormat('d/m/Y H:i:s', "$date $time");
    if (!$dt) {
         echo "FAILED to parse format 'd/m/Y H:i:s'\n";
         $errors = DateTime::getLastErrors();
         // print_r($errors);
    }
    return $dt ? $dt->format('Y-m-d H:i:s') : null;
};

echo "--- Test Case 1: Formatted String Y-m-d (Simulating formatData=true) ---\n";
// Suppose Excel file has "2025-12-31" displayed
$dateRaw = "2025-12-31";
$timeRaw = "12:00:00";
$d = $excelDateToPhpDate($dateRaw);
$t = $excelTimeToPhpTime($timeRaw);
$res = $combineDateTime($d, $t);
echo "Result: " . ($res ? $res : "NULL") . "\n\n";

echo "--- Test Case 2: Numeric (Simulating formatData=false) ---\n";
// 46022 is roughly 2025-12-31
$dateRaw = 46022; 
$timeRaw = 0.5; // 12:00 PM
$d = $excelDateToPhpDate($dateRaw);
$t = $excelTimeToPhpTime($timeRaw);
$res = $combineDateTime($d, $t);
echo "Result: " . ($res ? $res : "NULL") . "\n\n";

echo "--- Test Case 3: Formatted String d/m/Y (Luck scenario) ---\n";
$dateRaw = "31/12/2025";
$timeRaw = "12:00:00";
$d = $excelDateToPhpDate($dateRaw);
$t = $excelTimeToPhpTime($timeRaw);
$res = $combineDateTime($d, $t);
echo "Result: " . ($res ? $res : "NULL") . "\n\n";
