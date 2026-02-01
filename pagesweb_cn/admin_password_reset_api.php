<?php
/**
 * ===================================
 * RÉINITIALISATION MOT DE PASSE DIRECT
 * ===================================
 * Endpoint pour les administrateurs uniquement
 * Permet de réinitialiser le mot de passe directement
 */

header('Content-Type: application/json');
require_once __DIR__ . '/connectDb.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

// 1. RESET VIA CODE CLIENT
if ($action === 'request_reset') {
    $client_code = trim($_POST['client_code'] ?? '');
    
    if (!$client_code) {
        echo json_encode([
            'success' => false,
            'message' => 'Code client manquant'
        ]);
        exit;
    }

    try {
        // Vérifier que le code existe
        $stmt = $pdo->prepare("
            SELECT id, full_name FROM admin_accounts 
            WHERE client_code = ? LIMIT 1
        ");
        $stmt->execute([$client_code]);
        $admin = $stmt->fetch();

        if (!$admin) {
            echo json_encode([
                'success' => false,
                'message' => 'Code client non trouvé'
            ]);
            exit;
        }

        // Générer un token temporaire (valable 1 heure)
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600);

        // Sauvegarder le token en base
        $stmt = $pdo->prepare("
            UPDATE admin_accounts 
            SET reset_token = ?, reset_token_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$reset_token, $expires_at, $admin['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Token généré avec succès',
            'reset_token' => $reset_token,
            'admin_name' => $admin['full_name'],
            'reset_url' => 'admin_reset_with_token.php?token=' . urlencode($reset_token)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// 2. RESET VIA TOKEN
if ($action === 'reset_with_token') {
    $reset_token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$reset_token || !$new_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Données manquantes'
        ]);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Le mot de passe doit contenir au moins 6 caractères'
        ]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Les mots de passe ne correspondent pas'
        ]);
        exit;
    }

    try {
        // Vérifier le token
        $stmt = $pdo->prepare("
            SELECT id FROM admin_accounts 
            WHERE reset_token = ? 
              AND reset_token_expires > NOW()
            LIMIT 1
        ");
        $stmt->execute([$reset_token]);
        $admin = $stmt->fetch();

        if (!$admin) {
            echo json_encode([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ]);
            exit;
        }

        // Mettre à jour le mot de passe
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            UPDATE admin_accounts 
            SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $admin['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Action invalide
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Action non reconnue'
]);
?>
