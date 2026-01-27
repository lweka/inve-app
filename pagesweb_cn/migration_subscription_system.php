<?php
/**
 * ===================================
 * MIGRATION - SYSTÈME D'ABONNEMENT
 * ===================================
 * À exécuter UNE SEULE FOIS
 * Crée les tables pour codes d'essai et d'abonnement
 */

require_once __DIR__ . '/connectDb.php';

try {
    // Désactiver les contraintes FK temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // TABLE 1: CODES D'ESSAI (7 jours gratuit)
    $sql = "
    CREATE TABLE IF NOT EXISTS trial_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(36) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        company_name VARCHAR(100),
        status ENUM('unused', 'activated', 'expired') DEFAULT 'unused',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        activated_at TIMESTAMP NULL,
        expired_at TIMESTAMP NULL,
        INDEX idx_code (code),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);

    // TABLE 2: CODES D'ABONNEMENT (1 mois payant)
    $sql = "
    CREATE TABLE IF NOT EXISTS subscription_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(36) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        company_name VARCHAR(100),
        payment_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'validated', 'active', 'suspended', 'expired') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validated_at TIMESTAMP NULL,
        activated_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        notes TEXT,
        INDEX idx_code (code),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);

    // TABLE 3: CLIENTS ACTIFS (utilisateurs avec abonnement/essai)
    $sql = "
    CREATE TABLE IF NOT EXISTS active_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_code VARCHAR(36) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        company_name VARCHAR(100),
        subscription_type ENUM('trial', 'monthly') NOT NULL,
        trial_code_id INT,
        subscription_code_id INT,
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        last_login TIMESTAMP NULL,
        FOREIGN KEY (trial_code_id) REFERENCES trial_codes(id),
        FOREIGN KEY (subscription_code_id) REFERENCES subscription_codes(id),
        INDEX idx_client_code (client_code),
        INDEX idx_email (email),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);

    // Réactiver les contraintes FK
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo '<h2 style="color: green; text-align: center;">✅ Toutes les Tables Créées avec Succès !</h2>';
    echo '<p style="text-align: center; margin-top: 20px;">
        <a href="admin_subscription_manager.php" style="padding: 10px 20px; background: #0A6FB7; color: white; text-decoration: none; border-radius: 5px;">
            Aller au Dashboard Admin →
        </a>
    </p>';

} catch (PDOException $e) {
    // Réactiver les contraintes FK en cas d'erreur
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (Exception $e2) {}
    
    echo '<h2 style="color: red;">❌ Erreur migration :</h2>';
    echo '<pre style="background: #f0f0f0; padding: 15px; border-radius: 5px; color: #d32f2f;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<p style="color: orange; margin-top: 20px;">💡 Conseil: Si les tables existent déjà, vous pouvez ignorer cette erreur.</p>';
}
?>
