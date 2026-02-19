<?php
// pagesweb_cn/agent_delete.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code
require_once __DIR__ . '/data_integrity.php';

if (ob_get_length()) ob_end_clean();

// IMPORTANT : éviter le 406
header('Content-Type: text/plain; charset=utf-8');


$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false, 'message'=>"ID invalide"]);
    exit;
}

// check vendeur (sécurisé par client_code)
$stmt = $pdo->prepare("SELECT a.house_id FROM agents a JOIN houses h ON h.id = a.house_id WHERE a.id = ? AND h.client_code = ?");
$stmt->execute([$id, $client_code]);
$a = $stmt->fetch();

if(!$a){
    echo json_encode(['ok'=>false, 'message'=>"Vendeur introuvable"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // conserver l'historique en detachant l'agent des mouvements
    if (di_table_exists($pdo, 'product_movements')) {
        $pdo->prepare("UPDATE product_movements SET agent_id = NULL WHERE agent_id = ?")->execute([$id]);
    }

    if (di_table_exists($pdo, 'agent_stock')) {
        $pdo->prepare("DELETE FROM agent_stock WHERE agent_id = ?")->execute([$id]);
    }
    if (di_table_exists($pdo, 'seller_stock')) {
        $pdo->prepare("DELETE FROM seller_stock WHERE seller_id = ?")->execute([$id]);
    }

    // legacy sales liées au vendeur
    if (di_table_exists($pdo, 'sales')) {
        $stmtSales = $pdo->prepare("SELECT id FROM sales WHERE agent_id = ?");
        $stmtSales->execute([$id]);
        $saleIds = $stmtSales->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($saleIds) && di_table_exists($pdo, 'sale_items')) {
            $in = implode(',', array_fill(0, count($saleIds), '?'));
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id IN ($in)")->execute($saleIds);
        }
        if (!empty($saleIds)) {
            $in = implode(',', array_fill(0, count($saleIds), '?'));
            $pdo->prepare("DELETE FROM sales WHERE id IN ($in)")->execute($saleIds);
        }
    }

    if (di_table_exists($pdo, 'ticket_print_logs')) {
        $pdo->prepare("DELETE FROM ticket_print_logs WHERE agent_id = ?")->execute([$id]);
    }

    // suppression agent (scope client)
    $del = $pdo->prepare("DELETE a FROM agents a JOIN houses h ON h.id = a.house_id WHERE a.id = ? AND h.client_code = ?");
    $del->execute([$id, $client_code]);
    if ($del->rowCount() !== 1) {
        throw new RuntimeException('Suppression vendeur non effectuee');
    }

    di_cleanup_orphans($pdo);

    $pdo->commit();
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok'=>false, 'message'=>'Erreur suppression: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
