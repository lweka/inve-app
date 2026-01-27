<?php
require_once __DIR__ . '/connectDb.php';

// Test: Créer la table stella_data
try {
    $sql = "CREATE TABLE IF NOT EXISTS stella_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    
    echo "✅ <strong>TEST RÉUSSI !</strong><br>";
    echo "📝 Fichier: stella.php créé dans pagesweb_cn<br>";
    echo "🗄️ Table: stella_data créée dans la BDD<br>";
    echo "<br><strong>Détails :</strong><br>";
    echo "- Base de données: inventeur_produits-App<br>";
    echo "- Connexion: OK<br>";
    echo "- Table stella_data: Prête à l'emploi<br>";
    echo "<br><hr>";
    echo "✨ Nous pouvons commencer le développement du projet !";
    
} catch (PDOException $e) {
    echo "❌ <strong>ERREUR :</strong><br>";
    echo "Message: " . $e->getMessage();
}
?>
