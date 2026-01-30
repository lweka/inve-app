<?php
// houses_create.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

function clean($v){ return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

$name    = clean($_POST['name'] ?? '');
$code    = clean($_POST['code'] ?? '');
$type    = clean($_POST['type'] ?? '');
$address = clean($_POST['address'] ?? '');

$errors = [];

if($name === '')    $errors[] = "Le nom de la maison est obligatoire.";
if($code === '')    $errors[] = "Le code maison est obligatoire.";
if($address === '') $errors[] = "L'adresse de la maison est obligatoire.";

if(strlen($name) < 3)    $errors[] = "Le nom doit contenir au moins 3 caractères.";
if(strlen($code) < 2)    $errors[] = "Le code doit contenir au moins 2 caractères.";
if(strlen($address) < 5) $errors[] = "L'adresse doit contenir au moins 5 caractères.";

if(strlen($name) > 150)    $errors[] = "Le nom est trop long.";
if(strlen($code) > 50)     $errors[] = "Le code est trop long.";
if(strlen($type) > 100)    $errors[] = "Le type est trop long.";
if(strlen($address) > 255) $errors[] = "L'adresse est trop longue.";

if(!preg_match('/^[A-Za-z0-9_\-]+$/', $code)){
    $errors[] = "Le code maison ne doit contenir que des lettres, chiffres, tirets (-) ou underscores (_).";
}

/* Unicité (par client) */
$stmt = $pdo->prepare("SELECT id FROM houses WHERE code = ? AND client_code = ?");
$stmt->execute([$code, $client_code]);
if($stmt->fetch()) $errors[] = "Ce code maison existe déjà.";

if(!empty($errors)){
    $err = urlencode(json_encode($errors));
    $target = (defined('HOUSES_MANAGE') ? HOUSES_MANAGE : HOUSES_MANAGE);
    header("Location: ".$target."?err=".$err);
    exit;
}

/* Insert */
$stmt = $pdo->prepare("INSERT INTO houses (client_code, name, code, type, address, created_at) VALUES (?,?,?,?,?, NOW())");
$stmt->execute([$client_code, $name, $code, $type, $address]);

$target = (defined('HOUSES_MANAGE') ? HOUSES_MANAGE : HOUSES_MANAGE);
header("Location: ".$target."?msg=created");
exit;
