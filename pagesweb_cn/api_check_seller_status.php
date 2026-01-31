<?php
/**
 * ============================================================
 *  API - Vérifier le statut du vendeur en temps réel
 * ============================================================
 */
session_start();
require_once __DIR__ . '/connectDb.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier que c'est un vendeur
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    echo json_encode(['status' => 'inactive', 'message' => 'Non authentifié']);
    exit;
}

$agent_id = (int)$_SESSION['user_id'];

// Vérifier le statut en base de données
$stmt = $pdo->prepare("SELECT status, fullname FROM agents WHERE id = ? LIMIT 1");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$agent){
    echo json_encode(['status' => 'inactive', 'message' => 'Compte non trouvé']);
    exit;
}

echo json_encode([
    'status' => $agent['status'],
    'name' => $agent['fullname'],
    'is_active' => ($agent['status'] === 'active')
]);
exit;
