<?php
// pagesweb_cn/admin_check_password.php
//code check_house_code | API

require_once __DIR__ . '/connectDb.php';

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');




$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$house_id = intval($_POST['house_id'] ?? 0);

/* Vérification session admin */
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    echo json_encode(['ok'=>false,'message'=>'Non autorisé']);
    exit;
}

/* Mot de passe obligatoire */
if($password === ''){
    echo json_encode(['ok'=>false,'message'=>'Mot de passe requis']);
    exit;
}

/* Si username fourni → chercher par username
   Sinon → utiliser admin actuellement connecté */
if($username !== ''){
    $q = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $q->execute([$username]);
} else {
    // utiliser l'admin connecté
    $q = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $q->execute([$_SESSION['admin_id']]);
}

$admin = $q->fetch();

if(!$admin){
    echo json_encode(['ok'=>false,'message'=>'Administrateur introuvable']);
    exit;
}

/* Vérification password */
if(!password_verify($password, $admin['password_hash'])){
    echo json_encode(['ok'=>false,'message'=>'Mot de passe incorrect']);
    exit;
}

/* Récupération du code maison */
$stmt = $pdo->prepare("SELECT code FROM houses WHERE id=?");
$stmt->execute([$house_id]);
$house = $stmt->fetch();

if(!$house){
    echo json_encode(['ok'=>false,'message'=>'Maison introuvable']);
    exit;
}

/* OK — retour JSON */
echo json_encode([
    'ok'   => true,
    'code' => $house['code']
]);
exit;
