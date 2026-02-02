<?php
/**
 * ===================================
 * MIDDLEWARE - AUTHENTIFICATION ADMIN
 * ===================================
 * À inclure en haut de chaque page admin
 * Vérifie que l'admin est connecté et récupère son client_code
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté comme admin
if (empty($_SESSION['admin_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /pagesweb_cn/connect-parse.php?role=admin&message=not_logged_in');
    exit;
}

// Récupérer les informations du client
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'] ?? '';
$admin_full_name = $_SESSION['admin_full_name'] ?? '';
$client_code = $_SESSION['client_code'] ?? null;

// Si pas de client_code, chercher dans la BD
if (!$client_code) {
    require_once __DIR__ . '/connectDb.php';
    $stmt = $pdo->prepare("SELECT client_code FROM admin_accounts WHERE id = ?");
    $stmt->execute([$admin_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $client_code = $result['client_code'] ?? null;
    $_SESSION['client_code'] = $client_code;
}

// Vérifier que le client_code est valide et actif
if (!$client_code) {
    header('Location: /pagesweb_cn/connect-parse.php?role=admin&message=invalid_client');
    exit;
}

require_once __DIR__ . '/connectDb.php';
$stmt = $pdo->prepare("SELECT id, expires_at FROM active_clients WHERE client_code = ? AND status = 'active'");
$stmt->execute([$client_code]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    session_destroy();
    header('Location: /pagesweb_cn/connect-parse.php?role=admin&message=client_inactive');
    exit;
}

if (strtotime($client['expires_at']) < time()) {
    // Ne pas détruire la session, rediriger vers page d'expiration
    header('Location: /pagesweb_cn/expired_account.php');
    exit;
}

// Maintenant on peut utiliser $client_code dans les requêtes
// Exemple : SELECT * FROM houses WHERE client_code = ?
?>
