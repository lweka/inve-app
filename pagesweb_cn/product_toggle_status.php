<?php
// pagesweb_cn/product_toggle_status.php (AJAX)
require_once __DIR__ . '/connectDb.php';
if (ob_get_length()) ob_end_clean();
header('Content-Type: text/plain; charset=utf-8');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){ echo json_encode(['ok'=>false,'message'=>'ID invalide']); exit; }

$stmt = $pdo->prepare("SELECT is_active FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if(!$p){ echo json_encode(['ok'=>false,'message'=>'Produit introuvable']); exit; }

$new = $p['is_active'] ? 0 : 1;
$pdo->prepare("UPDATE products SET is_active=? WHERE id=?")->execute([$new, $id]);
echo json_encode(['ok'=>true,'new'=>$new]);
exit;
