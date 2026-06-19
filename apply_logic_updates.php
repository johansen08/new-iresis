<?php
$file = 'application/models/Resi_team_fcd.php';
$content = file_get_contents($file);
$today = date('Y-m-d');

// Replace tempo_hari_ini branch
$content = str_replace(
    "DATE(pr.tanggal_bataskirim) = '$today' AND ho.id_resikeluar IS NULL",
    "DATE(pr.tanggal_bataskirim) <= '$today' AND ho.id_resikeluar IS NULL",
    $content
);

// Replace deadline branch with logic to handle $start_date == $today
// We'll use a more complex replacement here to be sure.
$content = str_replace(
    "\$where_clause = \"DATE(pr.tanggal_bataskirim) = '\$start_date' AND ho.id_resikeluar IS NULL\";",
    "if (\$start_date == \$today) {
                \$where_clause = \"DATE(pr.tanggal_bataskirim) <= '\$start_date' AND ho.id_resikeluar IS NULL\";
            } else {
                \$where_clause = \"DATE(pr.tanggal_bataskirim) = '\$start_date' AND ho.id_resikeluar IS NULL\";
            }",
    $content
);

file_put_contents($file, $content);
echo "Successfully updated $file\n";
