<?php
// pagesweb_cn/agent_delete.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

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

// suppression
$del = $pdo->prepare("DELETE a FROM agents a JOIN houses h ON h.id = a.house_id WHERE a.id = ? AND h.client_code = ?");
$del->execute([$id, $client_code]);

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
exit;
