<?php
require_once __DIR__.'/connectDb.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    echo json_encode(['ok'=>false,'message'=>'Non autorisé']);
    exit;
}

$agent_id = (int)$_SESSION['user_id'];
$house_id = (int)($_GET['house_id'] ?? 0);

if($house_id <= 0){
    echo json_encode(['ok'=>false,'message'=>'Maison invalide']);
    exit;
}

$stmt = $pdo->prepare("
SELECT
  p.id,
  p.name,
  p.sell_price,
  p.sell_currency,
  ast.qty AS stock
FROM agent_stock ast
JOIN products p ON p.id = ast.product_id
WHERE ast.agent_id = ?
  AND ast.house_id = ?
  AND ast.qty > 0
  AND p.is_active = 1
ORDER BY p.name
");
$stmt->execute([$agent_id, $house_id]);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ok' => true,
    'products' => $products
]);