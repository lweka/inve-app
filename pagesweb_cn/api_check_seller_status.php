<?php
/**
 * API - verifier le statut vendeur en temps reel.
 */
require_once __DIR__ . '/connectDb.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function respond(array $payload): void {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    respond([
        'status' => 'inactive',
        'is_active' => false,
        'message' => 'Session vendeur invalide.',
        'redirect_to' => 'connect-parse.php?role=seller'
    ]);
}

$agentId = (int)($_SESSION['user_id'] ?? 0);
if ($agentId <= 0) {
    respond([
        'status' => 'inactive',
        'is_active' => false,
        'message' => 'Compte vendeur invalide.',
        'redirect_to' => 'connect-parse.php?role=seller'
    ]);
}

$stmt = $pdo->prepare('SELECT id, status, fullname FROM agents WHERE id = ? LIMIT 1');
$stmt->execute([$agentId]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    respond([
        'status' => 'inactive',
        'is_active' => false,
        'message' => 'Compte vendeur introuvable.',
        'redirect_to' => 'account_disabled.php'
    ]);
}

$isActive = (($agent['status'] ?? '') === 'active');
if (!$isActive) {
    $_SESSION['agent_name'] = (string)($agent['fullname'] ?? ($_SESSION['agent_name'] ?? 'Vendeur'));
    respond([
        'status' => (string)($agent['status'] ?? 'inactive'),
        'name' => (string)($agent['fullname'] ?? ''),
        'is_active' => false,
        'message' => 'Votre compte vendeur est desactive par l\'administrateur.',
        'redirect_to' => 'account_disabled.php'
    ]);
}

respond([
    'status' => 'active',
    'name' => (string)($agent['fullname'] ?? ''),
    'is_active' => true
]);
