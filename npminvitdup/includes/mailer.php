<?php
require_once 'config.php';
require_once 'functions.php';

/**
 * Envoyer un email
 */
function sendEmail($to, $subject, $body, $from = null) {
    if ($from === null) {
        $from = 'noreply@' . parse_url(SITE_URL, PHP_URL_HOST);
    }
    
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Envoyer une notification pour une nouvelle demande
 */
function sendNewRequestNotification($requestData) {
    $subject = "Nouvelle demande d'invitation - " . $requestData['nom'];
    
    $body = "<h2>Nouvelle demande d'invitation</h2>";
    $body .= "<p><strong>Nom:</strong> " . escape($requestData['nom']) . "</p>";
    $body .= "<p><strong>Email:</strong> " . escape($requestData['email']) . "</p>";
    $body .= "<p><strong>Téléphone:</strong> " . escape($requestData['telephone']) . "</p>";
    $body .= "<p><strong>Type d'événement:</strong> " . escape($requestData['type_evenement']) . "</p>";
    $body .= "<p><strong>Date:</strong> " . escape($requestData['date_evenement']) . "</p>";
    $body .= "<p><strong>Lieu:</strong> " . escape($requestData['lieu']) . "</p>";
    $body .= "<p><strong>Nombre de participants:</strong> " . escape($requestData['nombre_participants']) . "</p>";
    $body .= "<p><strong>Prix:</strong> " . escape($requestData['prix']) . " $</p>";
    
    if (!empty($requestData['design_preferences'])) {
        $body .= "<p><strong>Préférences design:</strong><br>" . nl2br(escape($requestData['design_preferences'])) . "</p>";
    }
    
    return sendEmail(ADMIN_EMAIL, $subject, $body);
}

/**
 * Envoyer une invitation à un participant
 */
function sendGuestInvitation($guestData, $qrcodeFilename) {
    $subject = "Invitation - " . $guestData['evenement_nom'];
    
    $body = "<h2>Vous êtes invité!</h2>";
    $body .= "<p>Cher(e) " . escape($guestData['prenom']) . " " . escape($guestData['nom']) . ",</p>";
    $body .= "<p>Vous êtes invité à " . escape($guestData['evenement_nom']) . " qui aura lieu le " . escape($guestData['date_evenement']) . " à " . escape($guestData['lieu']) . ".</p>";
    $body .= "<p>Votre QR code d'invitation est joint à cet email. Présentez-le à l'entrée de l'événement.</p>";
    
    $qrcodePath = SITE_URL . '/qrcodes/' . $qrcodeFilename;
    $body .= "<p><img src='" . $qrcodePath . "' alt='QR Code Invitation'></p>";
    
    $body .= "<p>Merci et à bientôt!</p>";
    
    // Headers pour joindre le QR code
    $boundary = uniqid('np');
    
    $headers = "From: noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "Reply-To: noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=$boundary\r\n";
    
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $body . "\r\n\r\n";
    
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: image/png; name=\"qrcode.png\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"qrcode.png\"\r\n\r\n";
    $message .= chunk_split(base64_encode(file_get_contents(QRCODE_DIR . $qrcodeFilename))) . "\r\n";
    
    $message .= "--$boundary--";
    
    return mail($guestData['email'], $subject, $message, $headers);
}
?>