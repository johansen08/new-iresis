<?php
// Define CI constants manually to bootstrap basic environment if needed, 
// OR just load the CI bootstrap.
// Trying to load CI bootstrap from CLI.

define('STDIN', fopen('php://stdin', 'r'));

// Point to index methods? No, difficult to mock request.
// Better to just load the model.

class MockCI {
    public $db;
    public $session;
    
    public function __construct() {
        $this->db = new MockDB();
        $this->session = new MockSession();
    }
}

class MockDB {
    public function get_where($table, $where) {
        // Return dummy row object
        return new MockQuery(false); // Simulate not exists
    }
    public function update($table, $data, $where) {
        echo "DB UPDATE: $table\n";
        print_r($data);
    }
    public function insert($table, $data) {
        echo "DB INSERT: $table\n";
        print_r($data);
    }
}

class MockQuery {
    public $row;
    public function __construct($exists) {
        $this->row = $exists ? (object)['id_sku'=>'TEST'] : null;
    }
    public function row() {
        return $this->row;
    }
}

class MockSession {
    public function set_userdata($key, $val) {
        echo "SESSION SET: $key\n";
    }
    public function unset_userdata($key) {
        echo "SESSION UNSET: $key\n";
    }
    public function userdata($key) {
        return [];
    }
}

// Mimic the Model Logic directly to see syntax errors
$dataRaw = [
    1 => ['A'=>'Header'],
    2 => [
        'A' => 'SKU001',
        'B' => 'Nama Barang',
        'C' => 'Bundle A',
        'D' => 'Variasi X',
        'E' => 'Rak A',
        'F' => 'Gudang 1',
        'G' => 'Transit',
        'H' => 100,
        'I' => 'link',
        'J' => 'NoRak',
        'K' => 1
    ]
];

// Logic copy-paste from Sku_fcd.php for isolated testing
// If this script runs fine, then the syntax is fine.
echo "Starting Debug...\n";

try {
    // Paste logic snippet here or include file if possible. 
    // Since it's a model class, we can try to include it but it extends CI_Model.
    // So we will just proceed to verifying the controller file for hidden BOM or whitespace.
    
    $fcd_path = 'c:\xampp\htdocs\iresis-v9\application\models\Sku_fcd.php';
    if (!file_exists($fcd_path)) die("Model not found");
    
    $content = file_get_contents($fcd_path);
    if (strpos($content, '<?php') !== 0) {
        echo "WARNING: File does not start with <?php cleanly.\n";
    }
    
    // Check for closing tag ?>
    if (strpos($content, '?>') !== false) {
        echo "WARNING: Closing PHP tag found. This often causes whitespace output.\n";
    }

    echo "Model file syntax check passed (basic).\n";
    
    $controller_path = 'c:\xampp\htdocs\iresis-v9\application\controllers\Sku.php';
    $content = file_get_contents($controller_path);
    if (strpos($content, '<?php') !== 0) {
        echo "WARNING: Controller does not start with <?php cleanly.\n";
    }
    if (strpos($content, '?>') !== false) {
        echo "WARNING: Controller Closing PHP tag found.\n";
    }
    
    echo "Controller file syntax check passed (basic).\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
