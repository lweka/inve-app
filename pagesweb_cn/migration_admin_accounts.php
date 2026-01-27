<?php
/**
 * ===================================
 * MIGRATION - COMPTES ADMINISTRATEUR
 * ===================================
 * Crée la table pour les comptes admin des clients
 */

require_once __DIR__ . '/connectDb.php';

try {
    // Désactiver les contraintes FK temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // TABLE: COMPTES ADMIN (USERNAME + PASSWORD)
    $sql = "
    CREATE TABLE IF NOT EXISTS admin_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_code VARCHAR(36) UNIQUE NOT NULL,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(200) NOT NULL,
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        FOREIGN KEY (client_code) REFERENCES active_clients(client_code),
        INDEX idx_username (username),
        INDEX idx_client_code (client_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);

    // Réactiver les contraintes FK
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo '<h2 style="color: green; text-align: center;">✅ Table admin_accounts Créée avec Succès !</h2>';
    echo '<p style="text-align: center; margin-top: 20px;">
        <a href="diagnostic.php" style="padding: 10px 20px; background: #0A6FB7; color: white; text-decoration: none; border-radius: 5px;">
            Aller au Diagnostic →
        </a>
    </p>';

} catch (PDOException $e) {
    // Réactiver les contraintes FK en cas d'erreur
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (Exception $e2) {}
    
    echo '<h2 style="color: red;">❌ Erreur migration :</h2>';
    echo '<pre style="background: #f0f0f0; padding: 15px; border-radius: 5px; color: #d32f2f;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<p style="color: orange; margin-top: 20px;">💡 Conseil: Si la table existe déjà, vous pouvez ignorer cette erreur.</p>';
}
?>
