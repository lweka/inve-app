<?php
// pagesweb_cn/agent_create.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: ".PARSE_CONNECT."?role=admin");
    exit;
}

function clean($v){ return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

$house_id = intval($_POST['house_id'] ?? 0);
$fullname = clean($_POST['fullname'] ?? '');
$phone     = clean($_POST['phone'] ?? '');
$address   = clean($_POST['address'] ?? '');

$errors = [];

if($house_id <= 0)          $errors[] = "Maison invalide.";
if(strlen($fullname) < 3)   $errors[] = "Le nom complet est trop court.";
if(strlen($phone) < 3)      $errors[] = "Numéro de téléphone invalide.";
if(strlen($address) < 3)    $errors[] = "Adresse invalide.";

if(!empty($errors)){
    $err = urlencode(json_encode($errors));
    header("Location:" .AGENTS_MANAGE."?house_id=$house_id&err=$err");
    exit;
}

/* Génération code vendeur unique */
function generateSellerCode(){
    return "AG" . strtoupper(bin2hex(random_bytes(2))); // Exemple: AG9F3A
}

do {
    $seller_code = generateSellerCode();
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE seller_code = ?");
    $stmt->execute([$seller_code]);
    $exists = $stmt->fetch();
} while($exists);

/* Insert */
$stmt = $pdo->prepare("
    INSERT INTO agents (house_id, fullname, phone, address, seller_code)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$house_id, $fullname, $phone, $address, $seller_code]);

header("Location:".AGENTS_MANAGE."?house_id=$house_id&msg=agent_created");
exit;
