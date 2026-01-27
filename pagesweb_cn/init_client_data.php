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

        // Créer une maison par défaut
        $stmt = $pdo->prepare("
            INSERT INTO houses (client_code, name, code, type, address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $client_code,
            'Maison Principale',
            strtoupper(substr($client_code, 0, 3)) . '-' . time(),
            'principal',
            'Kinshasa, RDC'
        ]);
        $house_id = $pdo->lastInsertId();

        // Créer un vendeur par défaut
        $stmt = $pdo->prepare("
            INSERT INTO agents (client_code, house_id, fullname, phone, address, seller_code, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $client_code,
            $house_id,
            'Vendeur Principal',
            '+243 000 000 000',
            'Kinshasa, RDC',
            'V-' . strtoupper(substr($client_code, -6)),
            'active'
        ]);
        $agent_id = $pdo->lastInsertId();

        // Créer 2-3 produits par défaut
        $products = [
            ['name' => 'Produit Standard', 'buy_price_cdf' => 10000, 'sell_price_cdf' => 15000],
            ['name' => 'Produit Premium', 'buy_price_cdf' => 25000, 'sell_price_cdf' => 40000],
            ['name' => 'Produit Économique', 'buy_price_cdf' => 5000, 'sell_price_cdf' => 8000]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO products (
                client_code, house_id, name, buy_price_cdf, sell_price_cdf, 
                sell_currency, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 'CDF', 1, NOW())
        ");

        foreach ($products as $product) {
            $stmt->execute([
                $client_code,
                $house_id,
                $product['name'],
                $product['buy_price_cdf'],
                $product['sell_price_cdf']
            ]);
        }

        return true;

    } catch (Exception $e) {
        error_log("Erreur initialisation client $client_code: " . $e->getMessage());
        return false;
    }
}

?>
