<?php
require 'connectDb.php'; // Fichier de connexion PDO

// Vérifier que le formulaire a bien été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Récupérer et nettoyer les données du formulaire
    // On utilise filter_input pour plus de sécurité
    $formation_id = filter_input(INPUT_POST, 'formation_id', FILTER_VALIDATE_INT);
    $nom_complet = filter_input(INPUT_POST, 'nom_complet', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);

    // 2. Valider les données
    if (!$formation_id || !$nom_complet || !$email || !$telephone) {
        // Une des données est manquante ou invalide
        // On pourrait rediriger vers le formulaire avec un message d'erreur
        die("Erreur : Veuillez remplir tous les champs correctement.");
    }

    // 3. Insérer les données dans la table 'inscriptions'
    try {
        $sql = "INSERT INTO inscriptions (formation_id, nom_complet, email, telephone) VALUES (:formation_id, :nom_complet, :email, :telephone)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':formation_id' => $formation_id,
            ':nom_complet' => $nom_complet,
            ':email' => $email,
            ':telephone' => $telephone
        ]);

        // 4. Rediriger vers une page de succès
        header('Location: merci.php');
        exit();

    } catch (\PDOException $e) {
        // Gérer l'erreur (par exemple, l'afficher ou la logger)
        die("Erreur lors de l'enregistrement de votre inscription : " . $e->getMessage());
    }

} else {
    // Si quelqu'un accède à ce script directement, on le redirige
    header('Location: index.php'); // ou vers la page des formations
    exit();
}
?>