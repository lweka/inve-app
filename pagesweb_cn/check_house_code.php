<?php
// public/api/check_house_code.php
// IMPORTANT : inclure uniquement la connexion PDO qui n'émet rien.
// Adapte le chemin vers ton connectDb si nécessaire.

//code check_house_code | API

require_once __DIR__ . '/connectDb.php';

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$code = trim($_GET['code'] ?? '');
if($code === ''){
    echo json_encode(['ok'=>false,'exists'=>false,'message'=>'Code manquant']);
    exit;
}
if(!preg_match('/^[A-Za-z0-9_\-]+$/', $code)){
    echo json_encode(['ok'=>false,'exists'=>false,'message'=>'Format invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM houses WHERE code = ?");
    $stmt->execute([$code]);
    $exists = (bool)$stmt->fetch();
    echo json_encode(['ok'=>true,'exists'=>$exists]);
    exit;
} catch (Exception $e) {
    // ne pas renvoyer la stack en prod
    echo json_encode(['ok'=>false,'exists'=>false,'message'=>'Erreur serveur']);
    exit;
}
