<?php
// pagesweb_cn/verify_house_delete_code.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code
require_once __DIR__ . '/data_integrity.php';

if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$request_id = (int)($_POST['request_id'] ?? 0);
$code = trim($_POST['code'] ?? '');

if ($request_id <= 0 || $code === '') {
    echo json_encode(['ok' => false, 'message' => 'Parametres requis']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM house_delete_requests WHERE id = ?");
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req) {
    echo json_encode(['ok' => false, 'message' => 'Demande introuvable']);
    exit;
}
if (time() > (int)$req['expires_at']) {
    echo json_encode(['ok' => false, 'message' => 'Code expire']);
    exit;
}
if ((string)$req['code'] !== $code) {
    echo json_encode(['ok' => false, 'message' => 'Code incorrect']);
    exit;
}

$house_id = (int)$req['house_id'];

// Verifier que la maison appartient au client connecte
$stmt = $pdo->prepare("SELECT id FROM houses WHERE id = ? AND client_code = ? LIMIT 1");
$stmt->execute([$house_id, $client_code]);
if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'message' => 'Maison non autorisee']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Legacy sales tables (si presentes)
    if (di_table_exists($pdo, 'sales')) {
        $sales = $pdo->prepare("SELECT id FROM sales WHERE house_id = ?");
        $sales->execute([$house_id]);
        $saleIds = $sales->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($saleIds) && di_table_exists($pdo, 'sale_items')) {
            $in = implode(',', array_fill(0, count($saleIds), '?'));
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id IN ($in)")->execute($saleIds);
        }
        if (!empty($saleIds)) {
            $in = implode(',', array_fill(0, count($saleIds), '?'));
            $pdo->prepare("DELETE FROM sales WHERE id IN ($in)")->execute($saleIds);
        }
    }

    // Tables liees a house_id
    $houseScopedTables = [
        'ticket_print_logs',
        'product_movements',
        'stock_movements',
        'agent_stock',
        'seller_stock',
        'house_stock',
        'exchange_rate',
    ];

    foreach ($houseScopedTables as $table) {
        if (di_table_exists($pdo, $table)) {
            $pdo->prepare("DELETE FROM {$table} WHERE house_id = ?")->execute([$house_id]);
        }
    }

    // Entites principales de la maison
    if (di_table_exists($pdo, 'products')) {
        $pdo->prepare("DELETE FROM products WHERE house_id = ?")->execute([$house_id]);
    }
    if (di_table_exists($pdo, 'agents')) {
        $pdo->prepare("DELETE FROM agents WHERE house_id = ?")->execute([$house_id]);
    }

    $delHouse = $pdo->prepare("DELETE FROM houses WHERE id = ? AND client_code = ?");
    $delHouse->execute([$house_id, $client_code]);
    if ($delHouse->rowCount() !== 1) {
        throw new RuntimeException('Suppression maison non effectuee');
    }

    if (di_table_exists($pdo, 'house_delete_requests')) {
        $pdo->prepare("DELETE FROM house_delete_requests WHERE house_id = ?")->execute([$house_id]);
    }

    // Sweep any legacy leftovers in the same transaction.
    di_cleanup_orphans($pdo);

    $pdo->commit();
    echo json_encode(['ok' => true]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'message' => 'Erreur suppression: ' . $e->getMessage()]);
    exit;
}
