<?php
require_once __DIR__ . '/connectDb.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Methode non autorisee'
    ]);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Session vendeur requise'
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$saleId = (int)($payload['sale_id'] ?? 0);
$printMode = strtolower(trim((string)($payload['mode'] ?? 'manual')));
if ($printMode === '') {
    $printMode = 'manual';
}
$printMode = preg_replace('/[^a-z0-9_-]/', '', $printMode);
if ($printMode === '') {
    $printMode = 'manual';
}

if ($saleId <= 0) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'sale_id invalide'
    ]);
    exit;
}

$agentId = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            pm.id,
            pm.client_code,
            pm.house_id,
            pm.agent_id,
            pm.receipt_id,
            pm.ticket_number
        FROM product_movements pm
        WHERE pm.id = ? AND pm.agent_id = ?
        LIMIT 1
    ");
    $stmt->execute([$saleId, $agentId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'message' => 'Vente introuvable pour ce vendeur'
        ]);
        exit;
    }

    $receiptId = trim((string)($sale['receipt_id'] ?? ''));
    if ($receiptId === '') {
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'message' => 'Aucun recu lie a cette vente'
        ]);
        exit;
    }

    ensureTicketPrintLogTable($pdo);

    $dedupeStmt = $pdo->prepare("
        SELECT id
        FROM ticket_print_logs
        WHERE client_code = ?
          AND agent_id = ?
          AND receipt_id = ?
          AND print_mode = ?
          AND printed_at >= DATE_SUB(NOW(), INTERVAL 8 SECOND)
        LIMIT 1
    ");
    $dedupeStmt->execute([
        (string)$sale['client_code'],
        (int)$sale['agent_id'],
        $receiptId,
        $printMode
    ]);
    $existingId = (int)$dedupeStmt->fetchColumn();
    if ($existingId > 0) {
        echo json_encode([
            'ok' => true,
            'duplicate' => true
        ]);
        exit;
    }

    $ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    $insertStmt = $pdo->prepare("
        INSERT INTO ticket_print_logs
        (
            client_code,
            house_id,
            agent_id,
            sale_id,
            receipt_id,
            ticket_number,
            print_mode,
            ip_address,
            user_agent,
            printed_at
        )
        VALUES (?,?,?,?,?,?,?,?,?,NOW())
    ");
    $insertStmt->execute([
        (string)$sale['client_code'],
        (int)$sale['house_id'],
        (int)$sale['agent_id'],
        $saleId,
        $receiptId,
        (string)($sale['ticket_number'] ?? ''),
        $printMode,
        $ipAddress,
        $userAgent
    ]);

    echo json_encode([
        'ok' => true
    ]);
} catch (Throwable $e) {
    error_log('ticket_print_log error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur technique'
    ]);
}

function ensureTicketPrintLogTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ticket_print_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_code VARCHAR(100) NOT NULL,
            house_id INT NOT NULL,
            agent_id INT NOT NULL,
            sale_id INT NOT NULL,
            receipt_id VARCHAR(120) NOT NULL,
            ticket_number VARCHAR(120) DEFAULT NULL,
            print_mode VARCHAR(30) NOT NULL DEFAULT 'manual',
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            printed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tpl_client_date (client_code, printed_at),
            INDEX idx_tpl_receipt (receipt_id),
            INDEX idx_tpl_agent_date (agent_id, printed_at),
            INDEX idx_tpl_house_date (house_id, printed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
