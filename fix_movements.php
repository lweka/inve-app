<?php
require_once __DIR__ . '/pagesweb_cn/connectDb.php';

$result = $pdo->exec('UPDATE product_movements SET type = "out" WHERE type IS NULL OR type = "sale"');
echo "✅ Normalisation effectuée : $result mouvements corrigés\n";
?>
