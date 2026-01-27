<?php
// pagesweb_cn/agent_delete.php
require_once __DIR__ . '/connectDb.php';

if (ob_get_length()) ob_end_clean();

// IMPORTANT : éviter le 406
header('Content-Type: text/plain; charset=utf-8');


$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false, 'message'=>"ID invalide"]);
    exit;
}

// check vendeur
$stmt = $pdo->prepare("SELECT house_id FROM agents WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();

if(!$a){
    echo json_encode(['ok'=>false, 'message'=>"Vendeur introuvable"]);
    exit;
}

// suppression
$del = $pdo->prepare("DELETE FROM agents WHERE id = ?");
$del->execute([$id]);

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
exit;
