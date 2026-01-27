<?php
// On inclut le fichier de connexion à la base de données
require 'connectDb.php'; // Assurez-vous que ce chemin est correct

// On vérifie que le formulaire a bien été soumis en méthode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // On vérifie que le champ email n'est pas vide
    if (!empty($_POST['EMAIL'])) {
        
        // On nettoie l'adresse e-mail pour éviter les failles
        $email = filter_var($_POST['EMAIL'], FILTER_SANITIZE_EMAIL);

        // On valide le format de l'e-mail
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
            try {
                // 1. VÉRIFIER SI L'E-MAIL EXISTE DÉJÀ
                // On prépare une requête pour chercher l'e-mail dans la table
                $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                
                // Si fetch() retourne une ligne, l'e-mail existe
                if ($stmt->fetch()) {
                    // L'e-mail existe déjà, on redirige avec un statut "exists"
                    header("Location: subscription_status.php?status=exists");
                    exit();
                } else {
                    // 2. INSÉRER LE NOUVEL E-MAIL
                    // L'e-mail n'existe pas, on le prépare pour l'insertion
                    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
                    $stmt->execute([$email]);
                    
                    // On redirige vers la page de succès
                    header("Location: subscription_status.php?status=success");
                    exit();
                }

            } catch (PDOException $e) {
                // En cas d'erreur avec la base de données, on redirige avec un statut "error"
                // Pour le débogage, vous pourriez logger l'erreur : error_log($e->getMessage());
                header("Location: subscription_status.php?status=error");
                exit();
            }

        } else {
            // L'e-mail n'est pas valide
            header("Location: subscription_status.php?status=invalid_email");
            exit();
        }
    } else {
        // Le champ email est vide
        header("Location: subscription_status.php?status=empty");
        exit();
    }
} else {
    // Si quelqu'un accède au script directement sans soumettre de formulaire
    header("Location: index.php"); // Redirigez vers votre page d'accueil
    exit();
}
?>