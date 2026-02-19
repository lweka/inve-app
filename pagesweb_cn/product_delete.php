<?php
// pagesweb_cn/product_delete.php (AJAX)
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code
require_once __DIR__ . '/data_integrity.php';

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){ echo json_encode(['ok'=>false,'message'=>'ID invalide']); exit; }

// Produit securise par maison/client connecte
$stmt = $pdo->prepare("
    SELECT p.id, p.house_id
    FROM products p
    JOIN houses h ON h.id = p.house_id
    WHERE p.id = ? AND h.client_code = ?
    LIMIT 1
");
$stmt->execute([$id, $client_code]);
$p = $stmt->fetch();
if(!$p){ echo json_encode(['ok'=>false,'message'=>'Produit introuvable']); exit; }

try {
    $pdo->beginTransaction();

    if (di_table_exists($pdo, 'sale_items')) {
        $pdo->prepare("DELETE FROM sale_items WHERE product_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'stock_movements')) {
        $pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'agent_stock')) {
        $pdo->prepare("DELETE FROM agent_stock WHERE product_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'seller_stock')) {
        $pdo->prepare("DELETE FROM seller_stock WHERE product_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'house_stock')) {
        $pdo->prepare("DELETE FROM house_stock WHERE product_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'product_movements')) {
        $pdo->prepare("DELETE FROM product_movements WHERE product_id = ?")->execute([$id]);
    }

    $del = $pdo->prepare("DELETE FROM products WHERE id = ? LIMIT 1");
    $del->execute([$id]);
    if ($del->rowCount() !== 1) {
        throw new RuntimeException('Suppression produit non effectuee');
    }

    di_cleanup_orphans($pdo);

    $pdo->commit();
    echo json_encode(['ok'=>true]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok'=>false,'message'=>'Erreur suppression: ' . $e->getMessage()]);
    exit;
}
