<?php
require_once 'database.php';

/**
 * Échapper les données pour prévenir les failles XSS
 */
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valider une adresse email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valider un numéro de téléphone
 */
function isValidPhone($phone) {
    return preg_match('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/', $phone);
}

/**
 * Calculer le prix en fonction du nombre de participants
 */
function calculatePrice($participants) {
    if ($participants <= 20) {
        return 35;
    } elseif ($participants <= 50) {
        return 35 + ($participants - 20) * 1.5;
    } elseif ($participants <= 100) {
        return 35 + 30 * 1.5 + ($participants - 50) * 1.0;
    } else {
        return 35 + 30 * 1.5 + 50 * 1.0 + ($participants - 100) * 0.5;
    }
}

/**
 * Générer un hash unique pour une invitation
 */
function generateUniqueHash() {
    return bin2hex(random_bytes(16));
}

/**
 * Vérifier si l'utilisateur est connecté en tant qu'admin
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Rediriger vers la page de connexion si non authentifié
 */
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Télécharger un fichier avec validation
 */
function uploadFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors du téléchargement du fichier.'];
    }
    
    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedTypes)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé.'];
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB).'];
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.'];
    }
}

/**
 * Générer un QR code (nécessite l'extension GD)
 */
function generateQRCode($data, $filename, $size = 200) {
    // URL de l'API QR code (solution alternative si GD n'est pas disponible)
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    
    $qrcode = file_get_contents($url);
    if ($qrcode !== false) {
        file_put_contents(QRCODE_DIR . $filename, $qrcode);
        return true;
    }
    
    return false;
}
?>