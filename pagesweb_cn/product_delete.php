<?php
// pagesweb_cn/product_delete.php (AJAX)
require_once __DIR__ . '/connectDb.php';
if (ob_get_length()) ob_end_clean();
header('Content-Type: text/plain; charset=utf-8');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){ echo json_encode(['ok'=>false,'message'=>'ID invalide']); exit; }

// fetch house_id for redirection if needed
$stmt = $pdo->prepare("SELECT house_id FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if(!$p){ echo json_encode(['ok'=>false,'message'=>'Produit introuvable']); exit; }
$house_id = $p['house_id'];

// delete movements
$pdo->prepare("DELETE FROM product_movements WHERE product_id = ?")->execute([$id]);
// delete stock row
$pdo->prepare("DELETE FROM house_stock WHERE product_id = ?")->execute([$id]);
// delete product
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

echo json_encode(['ok'=>true]);
exit;
