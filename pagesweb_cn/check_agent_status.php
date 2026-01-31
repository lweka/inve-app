<?php
/*
require_once __DIR__ . '/connectDb.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['active'=>false]);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM agents WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$status = $stmt->fetchColumn();

if($status !== 'active'){
    session_destroy();
    echo json_encode(['active'=>false]);
    exit;
}

echo json_encode(['active'=>true]);*/




require_once __DIR__ . '/connectDb.php';

header('Content-Type: application/json; charset=utf-8');

// Si session détruite ou logout demandé
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    echo json_encode(['active' => false]);
    exit;
}

$agent_id = $_SESSION['user_id'];

// Vérifier force_logout
if(isset($_SESSION['force_logout']) && $_SESSION['force_logout'] === true){
    unset($_SESSION['force_logout']);
    echo json_encode(['active' => false]);
    exit;
}

// Vérifier statut en BDD
$stmt = $pdo->prepare("SELECT status FROM agents WHERE id = ?");
$stmt->execute([$agent_id]);
$status = $stmt->fetchColumn();

if($status !== 'active'){
    // Rediriger vers la page de compte désactivé
    header('Location: account_disabled.php');
    exit;
}

echo json_encode(['active' => true]);
exit;