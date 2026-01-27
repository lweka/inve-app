<?php
// pagesweb_cn/agent_update_status.php
require_once __DIR__ . '/connectDb.php';

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false, 'message'=>"ID invalide"]);
    exit;
}

// récupérer vendeur
$stmt = $pdo->prepare("SELECT id, status FROM agents WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();

if(!$a){
    echo json_encode(['ok'=>false, 'message'=>"Vendeur introuvable"]);
    exit;
}

$newStatus = ($a['status'] === 'active') ? 'inactive' : 'active';

$upd = $pdo->prepare("UPDATE agents SET status=? WHERE id=?");
$upd->execute([$newStatus, $id]);

echo json_encode(['ok'=>true, 'new_status'=>$newStatus]);
exit;
