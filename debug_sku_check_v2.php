<?php
// Simple script to check for whitespace and closing tags

echo "Checking Model...\n";
$fcd_path = 'c:\xampp\htdocs\iresis-v9\application\models\Sku_fcd.php';
$content = file_get_contents($fcd_path);

if (substr($content, 0, 5) !== '<?php') {
    echo "WARNING: Model does not start with <?php\n";
}
if (trim(substr($content, -2)) == '?>') {
    echo "WARNING: Model has closing tag ?> at the end. This is bad for CodeIgniter.\n";
} else {
    echo "Model clean.\n";
}

echo "Checking Controller...\n";
$ctl_path = 'c:\xampp\htdocs\iresis-v9\application\controllers\Sku.php';
$content = file_get_contents($ctl_path);

if (substr($content, 0, 5) !== '<?php') {
    echo "WARNING: Controller does not start with <?php\n";
}
// check for any output before JSON
if (strpos($content, 'echo') !== false || strpos($content, 'print_r') !== false || strpos($content, 'var_dump') !== false) {
    echo "WARNING: Possible debug output found in Controller (echo/print_r/var_dump).\n";
}
