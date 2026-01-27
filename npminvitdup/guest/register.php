<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Vérifier si un ID d'événement est passé
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id > 0) {
    // Vérifier que l'événement existe et est traité
    $query = "SELECT id, type_evenement, date_evenement, lieu FROM evenements WHERE id = ? AND statut = 'traite'";
    $stmt = $db->prepare($query);
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        die("Événement non trouvé ou non traité.");
    }
}

// Traitement du formulaire d'inscription
$success = false;
$guestData = [];
$qrcodeFilename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = escape($_POST['nom']);
    $prenom = escape($_POST['prenom']);
    $telephone = escape($_POST['telephone']);
    $email = escape($_POST['email']);
    $event_id = (int)$_POST['event_id'];
    
    // Validation
    $errors = [];
    
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($email) || !isValidEmail($email)) $errors[] = "L'email est invalide.";
    if (empty($telephone) || !isValidPhone($telephone)) $errors[] = "Le téléphone est invalide.";
    
    if (empty($errors)) {
        try {
            // Récupérer une invitation disponible pour cet événement
            $query = "SELECT i.id FROM invitations i 
                     LEFT JOIN participants p ON i.id = p.invitation_id 
                     WHERE i.evenement_id = ? AND p.id IS NULL 
                     LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$event_id]);
            $invitation = $stmt->fetch();
            
            if ($invitation) {
                $invitation_id = $invitation['id'];
                
                // Générer le QR code
                $qrData = "EVENT:$event_id;INVITATION:$invitation_id;GUEST:" . urlencode("$prenom $nom");
                $qrcodeFilename = "guest_$invitation_id.png";
                
                if (generateQRCode($qrData, $qrcodeFilename)) {
                    // Insérer le participant
                    $query = "INSERT INTO participants (invitation_id, nom, prenom, telephone, email, qr_code_file) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$invitation_id, $nom, $prenom, $telephone, $email, $qrcodeFilename]);
                    
                    $success = true;
                    $guestData = [
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'evenement_nom' => $event['type_evenement'],
                        'date_evenement' => date('d/m/Y', strtotime($event['date_evenement'])),
                        'lieu' => $event['lieu']
                    ];
                    
                    // Envoyer l'email d'invitation
                    require_once '../includes/mailer.php';
                    sendGuestInvitation($guestData, $qrcodeFilename);
                } else {
                    $errors[] = "Erreur lors de la génération du QR code.";
                }
            } else {
                $errors[] = "Aucune invitation disponible pour cet événement.";
            }
        } catch (Exception $e) {
            error_log("Erreur inscription invité: " . $e->getMessage());
            $errors[] = "Une erreur s'est produite lors de l'inscription.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Invité</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <h1>Inscription Invité</h1>
        </div>
    </header>

    <section class="request-form">
        <div class="container">
            <?php if (isset($_SESSION['form_errors'])): ?>
                <div class="error-message">
                    <?php 
                    foreach ($_SESSION['form_errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    unset($_SESSION['form_errors']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="confirmation">
                    <h2>Inscription réussie!</h2>
                    <p>Merci <?php echo escape($guestData['prenom'] . ' ' . $guestData['nom']); ?>, vous êtes maintenant inscrit à l'événement.</p>
                    <p>Un email de confirmation avec votre QR code a été envoyé à <?php echo escape($guestData['email']); ?>.</p>
                    
                    <div class="qr-code-container">
                        <div id="qrcode"></div>
                        <a href="../qrcodes/<?php echo $qrcodeFilename; ?>" download="mon-qrcode.png" class="download-btn">Télécharger mon QR code</a>
                    </div>
                    
                    <script>
                        // Générer le QR code pour affichage immédiat
                        generateQRCode('qrcode', '<?php echo $qrData; ?>');
                    </script>
                </div>
            <?php else: ?>
                <h2>Formulaire d'inscription</h2>
                <?php if ($event_id > 0): ?>
                    <p>Inscription pour: <strong><?php echo escape($event['type_evenement']); ?></strong> 
                    le <?php echo date('d/m/Y', strtotime($event['date_evenement'])); ?> 
                    à <?php echo escape($event['lieu']); ?></p>
                <?php endif; ?>
                
                <form method="POST" class="guest-form">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required 
                               value="<?php echo isset($_SESSION['form_data']['prenom']) ? escape($_SESSION['form_data']['prenom']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required 
                               value="<?php echo isset($_SESSION['form_data']['nom']) ? escape($_SESSION['form_data']['nom']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_SESSION['form_data']['email']) ? escape($_SESSION['form_data']['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" required 
                               value="<?php echo isset($_SESSION['form_data']['telephone']) ? escape($_SESSION['form_data']['telephone']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">S'inscrire</button>
                </form>
                <?php unset($_SESSION['form_data']); ?>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Invitations Élégantes. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>