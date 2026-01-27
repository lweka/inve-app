<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Vérifier si un ID d'événement est passé
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    header('Location: index.html');
    exit;
}

$event_id = (int)$_GET['event_id'];

// Récupérer les informations de l'événement
$database = new Database();
$db = $database->getConnection();

$query = "SELECT e.*, c.nom as client_nom, c.email as client_email 
          FROM evenements e 
          JOIN clients c ON e.client_id = c.id 
          WHERE e.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: index.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de demande</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Demande confirmée</h1>
        </div>
    </header>

    <section class="confirmation">
        <div class="container">
            <h2>Merci pour votre demande, <?php echo escape($event['client_nom']); ?>!</h2>
            <p>Votre demande d'invitation pour votre <?php echo escape($event['type_evenement']); ?> a été enregistrée.</p>
            
            <div class="payment-info">
                <h3>Instructions de paiement</h3>
                <p>Veuillez effectuer le paiement pour finaliser votre commande.</p>
                
                <div class="payment-details">
                    <p><strong>Montant à payer:</strong> <span class="highlight"><?php echo escape($event['prix']); ?> $</span></p>
                    <p><strong>Numéro de mobile money:</strong> <span class="highlight">+243814926220</span></p>
                    <p><strong>Email pour confirmation:</strong> <span class="highlight">jlweka@cartelplus.tech</span></p>
                </div>
                
                <p><strong>Délai de traitement:</strong> Votre commande sera traitée dans les 24 heures après confirmation de paiement.</p>
            </div>
            
            <p>Un email de confirmation a été envoyé à <?php echo escape($event['client_email']); ?> avec ces informations.</p>
            <p><a href="index.html">Retour à l'accueil</a></p>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Invitations Élégantes. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>