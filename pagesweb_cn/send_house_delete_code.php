<?php
// pagesweb_cn/send_house_delete_code.php
session_start();
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// sécurisation
$house_id = intval($_POST['house_id'] ?? 0);

// IMPORTANT : SANITISATION PROPRE
$email_raw = trim($_POST['email'] ?? '');
$email = filter_var($email_raw, FILTER_SANITIZE_EMAIL);

/* validation maison */
if($house_id <= 0){
    echo json_encode(['ok'=>false,'message'=>'Maison invalide']);
    exit;
}

/* validation email */
if($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
    echo json_encode(['ok'=>false,'message'=>'Email invalide']);
    exit;
}

/* Vérification existence maison (client connecté) */
$stmt = $pdo->prepare("SELECT id, name, code, address FROM houses WHERE id = ? AND client_code = ?");
$stmt->execute([$house_id, $client_code]);
$house = $stmt->fetch();

if(!$house){
    echo json_encode(['ok'=>false,'message'=>'Maison introuvable']);
    exit;
}

/* Code suppression 6 chiffres */
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = time() + 300; // 5 minutes

/* Table création si absente */
$pdo->exec("CREATE TABLE IF NOT EXISTS house_delete_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* Insertion requête */
$stmt = $pdo->prepare("INSERT INTO house_delete_requests (house_id,email,code,expires_at) VALUES (?,?,?,?)");
$stmt->execute([$house_id, $email, $code, $expires]);
$request_id = $pdo->lastInsertId();

/* Envoi email */
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.titan.email';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'cartelplus-congo@cartelplus.tech';
    $mail->Password   = 'Jo@Kin243'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Code de suppression — Maison : " . htmlspecialchars($house['name']);
    $mail->Body = "
        <p>Vous avez demandé la suppression de la maison <strong>" . htmlspecialchars($house['name']) . "</strong>.</p>
        <p>Code de suppression : <h2>$code</h2></p>
        <p>Ce code est valide pendant <strong>5 minutes</strong>.</p>
    ";

    $mail->send();

    echo json_encode(['ok'=>true, 'request_id'=>$request_id]);
    exit;

} catch (Exception $e) {
    echo json_encode(['ok'=>false,'message'=>'Erreur envoi mail: '.$e->getMessage()]);
    exit;
}
