<?php
require_once __DIR__ . '/connectDb.php';

try {
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'product_movements' 
        AND COLUMN_NAME = 'receipt_id'
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Ajouter la colonne receipt_id
        $pdo->exec("ALTER TABLE product_movements ADD COLUMN receipt_id VARCHAR(36) DEFAULT NULL AFTER is_kit");
        $pdo->exec("ALTER TABLE product_movements ADD INDEX idx_receipt_id (receipt_id)");
        echo "✅ Colonne 'receipt_id' ajoutée avec succès à la table 'product_movements'";
    } else {
        echo "✅ La colonne 'receipt_id' existe déjà";
    }
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
