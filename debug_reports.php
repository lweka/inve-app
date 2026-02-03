<?php
require_once 'pagesweb_cn/connectDb.php';
require_once 'pagesweb_cn/require_admin_auth.php';

echo "<h2>Debug Reports</h2>";
echo "<p><strong>Client Code:</strong> " . htmlspecialchars($client_code) . "</p>";

// Vérifier les ventes
$stmt = $pdo->prepare("
SELECT COUNT(*) as total
FROM product_movements
WHERE (type = 'out' OR type = 'sale') AND client_code = ? AND DATE(created_at) = CURDATE()
");
$stmt->execute([$client_code]);
$result = $stmt->fetch();
echo "<p><strong>Ventes d'aujourd'hui:</strong> " . $result['total'] . "</p>";

// Dernières ventes
$stmt = $pdo->prepare("
SELECT id, type, qty, unit_sell_price, created_at, product_id
FROM product_movements
WHERE (type = 'out' OR type = 'sale') AND client_code = ?
ORDER BY created_at DESC
LIMIT 5
");
$stmt->execute([$client_code]);
$sales = $stmt->fetchAll();
echo "<p><strong>Dernières ventes:</strong></p>";
echo "<pre>";
print_r($sales);
echo "</pre>";

// Vérifier la requête de rapport
$filter_date_from = date('Y-m-d');
$filter_date_to = date('Y-m-d');

$sql = "
SELECT
    pm.id,
    pm.created_at,
    pm.qty,
    pm.unit_sell_price,
    pm.discount,
    pm.payment_method,
    pm.customer_name,
    pm.is_kit,
    pm.receipt_id,
    p.name AS product_name,
    a.fullname AS agent_fullname,
    h.name as house_name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
LEFT JOIN agents a ON a.id = pm.agent_id
LEFT JOIN houses h ON h.id = pm.house_id
WHERE (pm.type = 'out' OR pm.type = 'sale')
    AND pm.client_code = ?
    AND DATE(pm.created_at) >= ?
    AND DATE(pm.created_at) <= ?
ORDER BY pm.receipt_id DESC, pm.is_kit DESC, pm.created_at DESC
";

$params = [$client_code, $filter_date_from, $filter_date_to];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales_rapport = $stmt->fetchAll();

echo "<p><strong>Sales pour rapport:</strong> " . count($sales_rapport) . "</p>";
echo "<pre>";
print_r($sales_rapport);
echo "</pre>";
?>
