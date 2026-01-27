<?php
/**
 * ===================================
 * CONFIGURATION - SYSTÈME ABONNEMENT
 * ===================================
 * Fichier centralisé pour configurer le système
 * À adapter selon vos besoins
 */

// =============================
// CONFIGURATION ABONNEMENTS
// =============================

// Durée essai (jours)
define('TRIAL_DURATION_DAYS', 7);

// Durée abonnement (jours)
define('SUBSCRIPTION_DURATION_DAYS', 30);

// Tarification abonnement (en FC)
define('SUBSCRIPTION_PRICE', 50000);

// Devise
define('CURRENCY', 'FC');

// =============================
// CONFIGURATION ADMIN
// =============================

// ID admin principal (pour restrictions)
define('MAIN_ADMIN_ID', 1);

// Email admin
define('ADMIN_EMAIL', 'admin@cartelplus.cd');

// Téléphone admin
define('ADMIN_PHONE', '+243 123 456 789');

// WhatsApp admin
define('ADMIN_WHATSAPP', '+243 123 456 789');

// =============================
// CONFIGURATION CODES
// =============================

// Préfixe codes d'essai
define('TRIAL_CODE_PREFIX', 'TRIAL');

// Préfixe codes d'abonnement
define('SUBSCRIPTION_CODE_PREFIX', 'SUB');

// Préfixe codes clients
define('CLIENT_CODE_PREFIX', 'CLI');

// =============================
// CONFIGURATION SÉCURITÉ
// =============================

// Activer logs d'accès
define('LOG_ACCESS', true);

// Répertoire logs
define('LOG_DIR', __DIR__ . '/logs/');

// =============================
// FONCTIONS UTILITAIRES
// =============================

/**
 * Générer code d'essai
 */
function generate_trial_code() {
    return TRIAL_CODE_PREFIX . '-' . strtoupper(uniqid());
}

/**
 * Générer code d'abonnement
 */
function generate_subscription_code() {
    return SUBSCRIPTION_CODE_PREFIX . '-' . strtoupper(uniqid());
}

/**
 * Générer code client
 */
function generate_client_code() {
    return CLIENT_CODE_PREFIX . '-' . strtoupper(uniqid());
}

/**
 * Obtenir date expiration essai
 */
function get_trial_expiry_date() {
    return date('Y-m-d H:i:s', strtotime('+' . TRIAL_DURATION_DAYS . ' days'));
}

/**
 * Obtenir date expiration abonnement
 */
function get_subscription_expiry_date() {
    return date('Y-m-d H:i:s', strtotime('+' . SUBSCRIPTION_DURATION_DAYS . ' days'));
}

/**
 * Vérifier si essai expiré
 */
function is_trial_expired($created_at) {
    $expiry = strtotime($created_at) + (TRIAL_DURATION_DAYS * 86400);
    return time() > $expiry;
}

/**
 * Vérifier si abonnement expiré
 */
function is_subscription_expired($created_at) {
    $expiry = strtotime($created_at) + (SUBSCRIPTION_DURATION_DAYS * 86400);
    return time() > $expiry;
}

/**
 * Logger accès client
 */
function log_client_access($client_code, $action) {
    if (!LOG_ACCESS) return;
    
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $log_file = LOG_DIR . date('Y-m-d') . '_access.log';
    $message = date('Y-m-d H:i:s') . " | " . $client_code . " | " . $action . "\n";
    file_put_contents($log_file, $message, FILE_APPEND);
}

/**
 * Formater montant monnaie
 */
function format_currency($amount) {
    return number_format($amount, 0) . ' ' . CURRENCY;
}

/**
 * Envoyer email (à intégrer selon votre système)
 * @param string $to Email destinataire
 * @param string $subject Sujet
 * @param string $body Corps
 */
function send_email($to, $subject, $body) {
    // À implémenter selon votre système
    // Exemple: utiliser PHPMailer, SwiftMailer, etc.
    
    // Pour maintenant, juste un placeholder
    // error_log("Email sent to: $to | Subject: $subject");
    
    return true;
}

/**
 * Envoyer code d'essai par email
 */
function send_trial_code_email($email, $first_name, $trial_code) {
    $subject = "Votre code d'essai CartelPlus Congo";
    $body = "
    Bonjour $first_name,
    
    Bienvenue chez CartelPlus Congo!
    
    Votre code d'essai: $trial_code
    Durée: " . TRIAL_DURATION_DAYS . " jours
    
    Cliquez ici pour activer: " . ADMIN_WHATSAPP . "
    
    Cordialement,
    Équipe CartelPlus
    ";
    
    return send_email($email, $subject, $body);
}

/**
 * Envoyer code d'abonnement par email
 */
function send_subscription_code_email($email, $first_name, $subscription_code, $amount) {
    $subject = "Votre code d'abonnement CartelPlus Congo";
    $body = "
    Bonjour $first_name,
    
    Merci de votre intérêt pour CartelPlus Congo!
    
    Votre code d'abonnement: $subscription_code
    Montant: " . format_currency($amount) . "
    Durée: " . SUBSCRIPTION_DURATION_DAYS . " jours
    
    Veuillez nous contacter avec ce code et preuve de paiement:
    Email: " . ADMIN_EMAIL . "
    WhatsApp: " . ADMIN_WHATSAPP . "
    
    Cordialement,
    Équipe CartelPlus
    ";
    
    return send_email($email, $subject, $body);
}

/**
 * Envoyer notification admin - nouveau code essai
 */
function notify_admin_trial($email, $first_name, $last_name, $trial_code) {
    $subject = "Nouveau code d'essai générés";
    $body = "
    Nouveau client d'essai:
    
    Nom: $first_name $last_name
    Email: $email
    Code: $trial_code
    Date: " . date('Y-m-d H:i:s') . "
    ";
    
    return send_email(ADMIN_EMAIL, $subject, $body);
}

/**
 * Envoyer notification admin - nouveau code abonnement
 */
function notify_admin_subscription($email, $first_name, $last_name, $subscription_code, $amount) {
    $subject = "Nouvel abonnement en attente de validation";
    $body = "
    Nouvel abonnement:
    
    Nom: $first_name $last_name
    Email: $email
    Code: $subscription_code
    Montant: " . format_currency($amount) . "
    Date: " . date('Y-m-d H:i:s') . "
    
    URL validation: http://localhost/inve-app/pagesweb_cn/admin_subscription_manager.php
    ";
    
    return send_email(ADMIN_EMAIL, $subject, $body);
}

// =============================
// CONSTANTES STATUT
// =============================

define('STATUS_TRIAL_UNUSED', 'unused');
define('STATUS_TRIAL_ACTIVATED', 'activated');
define('STATUS_TRIAL_EXPIRED', 'expired');

define('STATUS_SUB_PENDING', 'pending');
define('STATUS_SUB_VALIDATED', 'validated');
define('STATUS_SUB_ACTIVE', 'active');
define('STATUS_SUB_SUSPENDED', 'suspended');
define('STATUS_SUB_EXPIRED', 'expired');

define('STATUS_CLIENT_ACTIVE', 'active');
define('STATUS_CLIENT_SUSPENDED', 'suspended');

define('TYPE_TRIAL', 'trial');
define('TYPE_MONTHLY', 'monthly');

?>
