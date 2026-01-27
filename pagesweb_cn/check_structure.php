<?php
require_once __DIR__ . '/connectDb.php';

$tables = ['agents', 'products', 'product_movements', 'agent_stock', 'houses'];

foreach($tables as $table) {
    echo "\n=== TABLE: $table ===\n";
    try {
        $result = $pdo->query("DESCRIBE $table");
        if($result) {
            while($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo "  - {$row['Field']} ({$row['Type']})\n";
            }
        } else {
            echo "  ❌ Table not found\n";
        }
    } catch(Exception $e) {
        echo "  ❌ Table not found\n";
    }
}
?>
