<?php
// pagesweb_cn/agent_update_status.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false, 'message'=>"ID invalide"]);
    exit;
}

// récupérer vendeur (sécurisé par client_code)
$stmt = $pdo->prepare("SELECT a.id, a.status FROM agents a JOIN houses h ON h.id = a.house_id WHERE a.id = ? AND h.client_code = ?");
$stmt->execute([$id, $client_code]);
$a = $stmt->fetch();

if(!$a){
    echo json_encode(['ok'=>false, 'message'=>"Vendeur introuvable"]);
    exit;
}

$newStatus = ($a['status'] === 'active') ? 'inactive' : 'active';

$upd = $pdo->prepare("UPDATE agents a JOIN houses h ON h.id = a.house_id SET a.status=? WHERE a.id=? AND h.client_code = ?");
$upd->execute([$newStatus, $id, $client_code]);

echo json_encode(['ok'=>true, 'new_status'=>$newStatus]);
exit;
