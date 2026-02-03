<?php
require_once 'pagesweb_cn/connectDb.php';

echo "<h2>Structure de product_movements:</h2>";
$stmt = $pdo->query("DESCRIBE product_movements");
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " (" . $row['Type'] . ")<br>";
}

echo "<h2>Derniers mouvements:</h2>";
$stmt = $pdo->query("SELECT id, type, unit_sell_price, unit_buy_price, unit_sell_price_cdf, unit_buy_price_cdf, sell_currency, qty, created_at FROM product_movements ORDER BY created_at DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

echo "<h2>Ventes d'aujourd'hui (toutes les colonnes):</h2>";
$stmt = $pdo->query("SELECT * FROM product_movements WHERE DATE(created_at) = CURDATE() AND (type = 'out' OR type = 'sale') ORDER BY created_at DESC");
$rows = $stmt->fetchAll();
echo "Nombre de ventes: " . count($rows) . "<br>";
foreach ($rows as $row) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}
?>
