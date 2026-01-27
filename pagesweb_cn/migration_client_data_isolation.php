<?php
/**
 * ===================================
 * MIGRATION - ISOLATION DES DONNÉES
 * ===================================
 * Ajoute client_code aux tables pour cloisonner les données
 * Chaque client ne voit que SES données
 */

require_once __DIR__ . '/connectDb.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // 1. Ajouter client_code à houses
    $sql = "ALTER TABLE houses ADD COLUMN client_code VARCHAR(36) DEFAULT NULL AFTER id";
    try {
        $pdo->exec($sql);
        echo '<p style="color: green;">✅ Colonne client_code ajoutée à houses</p>';
    } catch (Exception $e) {
        echo '<p style="color: orange;">⚠️ Colonne client_code exists déjà dans houses</p>';
    }

    // 2. Ajouter client_code à agents
    $sql = "ALTER TABLE agents ADD COLUMN client_code VARCHAR(36) DEFAULT NULL AFTER id";
    try {
        $pdo->exec($sql);
        echo '<p style="color: green;">✅ Colonne client_code ajoutée à agents</p>';
    } catch (Exception $e) {
        echo '<p style="color: orange;">⚠️ Colonne client_code existe déjà dans agents</p>';
    }

    // 3. Ajouter client_code à products
    $sql = "ALTER TABLE products ADD COLUMN client_code VARCHAR(36) DEFAULT NULL AFTER id";
    try {
        $pdo->exec($sql);
        echo '<p style="color: green;">✅ Colonne client_code ajoutée à products</p>';
    } catch (Exception $e) {
        echo '<p style="color: orange;">⚠️ Colonne client_code existe déjà dans products</p>';
    }

    // 4. Ajouter client_code à product_movements
    $sql = "ALTER TABLE product_movements ADD COLUMN client_code VARCHAR(36) DEFAULT NULL AFTER id";
    try {
        $pdo->exec($sql);
        echo '<p style="color: green;">✅ Colonne client_code ajoutée à product_movements</p>';
    } catch (Exception $e) {
        echo '<p style="color: orange;">⚠️ Colonne client_code existe déjà dans product_movements</p>';
    }

    // Réactiver les contraintes FK
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo '<h2 style="color: green; text-align: center; margin-top: 30px;">✅ Migration Complète !</h2>';
    echo '<p style="text-align: center; margin-top: 20px;">Les données sont maintenant cloisonnées par client.</p>';

} catch (PDOException $e) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (Exception $e2) {}
    
    echo '<h2 style="color: red;">❌ Erreur migration :</h2>';
    echo '<pre style="background: #f0f0f0; padding: 15px; border-radius: 5px; color: #d32f2f;">' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>
