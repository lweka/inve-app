<?php
/**
 * ===================================
 * INITIALISATION CLIENT
 * ===================================
 * À exécuter quand un client se connecte pour la première fois
 * Crée ses données de base (maison, vendeurs, produits)
 */

require_once __DIR__ . '/connectDb.php';

function initialize_client_data($client_code, $pdo) {
    try {
        // Vérifier si le client a déjà des données
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM houses WHERE client_code = ?");
        $stmt->execute([$client_code]);
        if ((int)$stmt->fetchColumn() > 0) {
            // Client a déjà des données
            return true;
        }

        // Ne pas créer de données par défaut : le client doit tout configurer lui‑même
        return true;

    } catch (Exception $e) {
        error_log("Erreur initialisation client $client_code: " . $e->getMessage());
        return false;
    }
}

?>
