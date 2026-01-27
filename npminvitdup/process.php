<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

// Initialiser la base de données
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Échapper et valider les données
    $nom = escape($_POST['nom']);
    $email = escape($_POST['email']);
    $telephone = escape($_POST['telephone']);
    $type_evenement = escape($_POST['type_evenement']);
    $date_evenement = escape($_POST['date_evenement']);
    $lieu = escape($_POST['lieu']);
    $nombre_participants = (int)$_POST['nombre_participants'];
    $design_preferences = escape($_POST['design_preferences']);
    
    // Validation
    $errors = [];
    
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($email) || !isValidEmail($email)) $errors[] = "L'email est invalide.";
    if (empty($telephone) || !isValidPhone($telephone)) $errors[] = "Le téléphone est invalide.";
    if (empty($type_evenement)) $errors[] = "Le type d'événement est obligatoire.";
    if (empty($date_evenement)) $errors[] = "La date est obligatoire.";
    if (empty($lieu)) $errors[] = "Le lieu est obligatoire.";
    if ($nombre_participants < 1) $errors[] = "Le nombre de participants doit être au moins 1.";
    
    // Gestion du fichier uploadé
    $design_file = null;
    if (!empty($_FILES['design_file']['name'])) {
        $uploadResult = uploadFile($_FILES['design_file']);
        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['message'];
        } else {
            $design_file = $uploadResult['filename'];
        }
    }
    
    // Calcul du prix
    $prix = calculatePrice($nombre_participants);
    
    // Si pas d'erreurs, enregistrer en base de données
    if (empty($errors)) {
        try {
            // Commencer une transaction
            $db->beginTransaction();
            
            // Insérer le client
            $query = "INSERT INTO clients (nom, email, telephone) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nom, $email, $telephone]);
            $client_id = $db->lastInsertId();
            
            // Insérer l'événement
            $query = "INSERT INTO evenements (client_id, type_evenement, date_evenement, lieu, nombre_participants, design_preferences, design_file, prix) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$client_id, $type_evenement, $date_evenement, $lieu, $nombre_participants, $design_preferences, $design_file, $prix]);
            $evenement_id = $db->lastInsertId();
            
            // Valider la transaction
            $db->commit();
            
            // Préparer les données pour l'email
            $requestData = [
                'nom' => $nom,
                'email' => $email,
                'telephone' => $telephone,
                'type_evenement' => $type_evenement,
                'date_evenement' => $date_evenement,
                'lieu' => $lieu,
                'nombre_participants' => $nombre_participants,
                'prix' => $prix,
                'design_preferences' => $design_preferences
            ];
            
            // Envoyer l'email de notification
            sendNewRequestNotification($requestData);
            
            // Rediriger vers la page de confirmation
            header('Location: confirmation.php?event_id=' . $evenement_id);
            exit;
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            error_log("Erreur base de données: " . $e->getMessage());
            $errors[] = "Une erreur s'est produite lors de l'enregistrement. Veuillez réessayer.";
        }
    }
    
    // Si on arrive ici, c'est qu'il y a eu des erreurs
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: index.html#form-section');
    exit;
} else {
    // Rediriger si accès direct
    header('Location: index.html');
    exit;
}
?>