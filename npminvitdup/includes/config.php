<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'invitation_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('SITE_URL', 'http://localhost/invitation-system');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('QRCODE_DIR', __DIR__ . '/../qrcodes/');

// Configuration email
define('ADMIN_EMAIL', 'jlweka@cartelplus.tech');
define('EMAIL_SUBJECT', 'Nouvelle demande d\'invitation');

// Assurez-vous que les répertoires existent
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if (!file_exists(QRCODE_DIR)) {
    mkdir(QRCODE_DIR, 0777, true);
}

// Désactiver l'affichage des erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>