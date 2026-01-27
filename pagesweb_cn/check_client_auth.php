<?php
/**
 * ===================================
 * MIDDLEWARE - VÉRIFICATION CLIENT ACTIF
 * ===================================
 * À inclure dans chaque page nécessitant une authentification client
 * Vérifie que le client a un accès actif valide
 */

require_once __DIR__ . '/connectDb.php';

function check_client_access() {
    global $pdo;
    
    // Chercher le code client en session ou cookie
    $client_code = isset($_SESSION['client_code']) ? $_SESSION['client_code'] : 
                   (isset($_COOKIE['client_code']) ? $_COOKIE['client_code'] : null);
    
    if (!$client_code) {
        return null; // Pas d'accès
    }
    
    // Vérifier dans la BD
    $stmt = $pdo->prepare("
        SELECT * FROM active_clients 
        WHERE client_code = ? 
        AND status = 'active'
        AND expires_at > NOW()
    ");
    $stmt->execute([$client_code]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        // Mettre à jour last_login
        $stmt_update = $pdo->prepare("UPDATE active_clients SET last_login = NOW() WHERE id = ?");
        $stmt_update->execute([$client['id']]);
        
        return $client;
    }
    
    return null; // Accès expiré ou invalide
}

function require_client_access() {
    $client = check_client_access();
    
    if (!$client) {
        // Rediriger vers essai ou achat
        header('Location: /inve-app/pagesweb_cn/trial_form?message=access_expired');
        exit;
    }
    
    return $client;
}

// Optionnel: Fonction pour vérifier si admin
function is_main_admin() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == 1;
}
?>
