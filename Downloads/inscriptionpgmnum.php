<?php
// Démarrer la session pour stocker les messages d'erreur ou de succès si nécessaire
session_start();

// Inclure le fichier de connexion à la base de données
    require 'connectDb.php';   // Votre fichier de connexion

$error_message = '';
$form_data = [];

// Traitement du formulaire lorsqu'il est soumis (méthode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Nettoyage et validation des données (TRÈS IMPORTANT pour la sécurité)
    // htmlspecialchars() pour se protéger contre les attaques XSS
    $form_data['nom_eglise_formation']    = trim(htmlspecialchars($_POST['nom_eglise_formation'] ?? ''));
    $form_data['nom_complet']             = trim(htmlspecialchars($_POST['nom_complet'] ?? ''));
    $form_data['sexe']                    = trim(htmlspecialchars($_POST['sexe'] ?? ''));
    $form_data['age']                     = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $form_data['telephone']               = trim(htmlspecialchars($_POST['telephone'] ?? ''));
    $form_data['adresse']                 = trim(htmlspecialchars($_POST['adresse'] ?? ''));
    $form_data['email']                   = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null; // Email optionnel
    $form_data['situation_actuelle']      = trim(htmlspecialchars($_POST['situation_actuelle'] ?? ''));
    $form_data['situation_autre_details'] = ($form_data['situation_actuelle'] === 'Autre') ? trim(htmlspecialchars($_POST['situation_autre_details'] ?? '')) : null;
    $form_data['nom_eglise_appartenance'] = trim(htmlspecialchars($_POST['nom_eglise_appartenance'] ?? ''));
    $form_data['pasteur_responsable']     = trim(htmlspecialchars($_POST['pasteur_responsable'] ?? ''));
    $form_data['motivation']              = trim(htmlspecialchars($_POST['motivation'] ?? ''));
    $form_data['engagement_serieux']      = isset($_POST['engagement_serieux']);
    $form_data['engagement_paiement']     = isset($_POST['engagement_paiement']);

    // Vérification des champs obligatoires
    if (empty($form_data['nom_complet']) || empty($form_data['sexe']) || empty($form_data['age']) || empty($form_data['telephone']) || empty($form_data['adresse']) || empty($form_data['situation_actuelle']) || empty($form_data['nom_eglise_appartenance']) || empty($form_data['pasteur_responsable']) || empty($form_data['motivation']) || !$form_data['engagement_serieux'] || !$form_data['engagement_paiement']) {
        $error_message = "Veuillez remplir tous les champs obligatoires et cocher les cases d'engagement.";
    } else {
        try {
            // 2. Insertion dans la base de données avec une requête préparée (protection contre les injections SQL)
            $sql = "INSERT INTO inscriptions_pgm_num (nom_complet, sexe, age, telephone, adresse, email, situation_actuelle, situation_autre_details, nom_eglise_appartenance, pasteur_responsable, motivation, engagement_serieux, engagement_paiement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // Exécution de la requête
            $stmt->execute([
                $form_data['nom_complet'],
                $form_data['sexe'],
                $form_data['age'],
                $form_data['telephone'],
                $form_data['adresse'],
                $form_data['email'],
                $form_data['situation_actuelle'],
                $form_data['situation_autre_details'],
                $form_data['nom_eglise_appartenance'],
                $form_data['pasteur_responsable'],
                $form_data['motivation'],
                $form_data['engagement_serieux'],
                $form_data['engagement_paiement']
            ]);

            // 3. Envoi de l'email de notification
            $to = 'jlweka@cartelplus.tech';
            $subject = 'NOUVELLE INSCRIPTION AU PROGRAMME';
            
            $message = "
            <html>
            <head><title>$subject</title></head>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #0056b3;'>Nouvelle inscription reçue</h2>
                <p>Une nouvelle personne s'est inscrite au programme de formation en compétences numériques.</p>
                <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                    <tr style='background-color: #f2f2f2;'><td style='width: 30%;'><strong>Nom complet :</strong></td><td>" . $form_data['nom_complet'] . "</td></tr>
                    <tr><td><strong>Sexe :</strong></td><td>" . $form_data['sexe'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Âge :</strong></td><td>" . $form_data['age'] . " ans</td></tr>
                    <tr><td><strong>Téléphone :</strong></td><td>" . $form_data['telephone'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Email :</strong></td><td>" . ($form_data['email'] ?? 'Non fourni') . "</td></tr>
                    <tr><td><strong>Adresse :</strong></td><td>" . $form_data['adresse'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Situation :</strong></td><td>" . $form_data['situation_actuelle'] . ($form_data['situation_autre_details'] ? ' (' . $form_data['situation_autre_details'] . ')' : '') . "</td></tr>
                    <tr><td><strong>Église d'appartenance :</strong></td><td>" . $form_data['nom_eglise_appartenance'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Pasteur responsable :</strong></td><td>" . $form_data['pasteur_responsable'] . "</td></tr>
                    <tr><td><strong>Motivation :</strong></td><td>" . nl2br($form_data['motivation']) . "</td></tr>
                </table>
                <p style='margin-top: 20px;'>Cet email a été envoyé automatiquement depuis le site web.</p>
            </body>
            </html>";

            // Headers pour l'envoi d'email HTML
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: <jlweka@cartelplus.tech>' . "\r\n"; // Remplacez par une adresse de votre domaine

            mail($to, $subject, $message, $headers);

            // 4. Redirection vers la page de succès
            header('Location: pgmok.php');
            exit();

        } catch (PDOException $e) {
            // En cas d'erreur de la base de données, on affiche un message
            $error_message = "Erreur lors de l'enregistrement : " . $e->getMessage();
            // Pour le debug, ne pas afficher en production : error_log("Erreur SQL : " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription à la Formation en Compétences Numériques</title>
    <meta property="og:description" content="Une initiative pour former, équiper et propulser les enfants de Dieu dans le monde numérique.">
    <!-- Intégration de Bootstrap 5 pour un design moderne -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="Logo.png" type="image/png">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            padding: 1.5rem;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0a58ca;
        }
        .form-label {
            font-weight: 600;
        }
        .section-title {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-top: 2rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="card">
        <div class="card-header text-center">
            <h1 class="h3 mb-0">Fiche d'Inscription</h1>
            <p class="mb-0">Formation en Compétences Numériques</p>
        </div>
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <p class="lead">Projet : Renforcement des capacités numériques pour les membres des églises</p>
                <p class="fw-bold">Coût : 10 $ pour toute la session (1 mois)</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="inscriptionForm">
             <!-- Intégration de Bootstrap 5 pour un design moderne 
                <div class="mb-4">
                    <label for="nom_eglise_formation" class="form-label">Indiquez le nom de votre Eglise (si la formation s'y déroule)</label>
                    <input type="text" class="form-control" id="nom_eglise_formation" name="nom_eglise_formation" value="<?= htmlspecialchars($form_data['nom_eglise_formation'] ?? '') ?>">
                </div>
            -->
                <h2 class="h4 section-title">Informations Personnelles</h2>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="nom_complet" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_complet" name="nom_complet" required value="<?= htmlspecialchars($form_data['nom_complet'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="age" class="form-label">Âge <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="age" name="age" required min="1" value="<?= htmlspecialchars($form_data['age'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Sexe <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sexe" id="sexe_homme" value="Homme" required <?= (isset($form_data['sexe']) && $form_data['sexe'] == 'Homme') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sexe_homme">Homme</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sexe" id="sexe_femme" value="Femme" required <?= (isset($form_data['sexe']) && $form_data['sexe'] == 'Femme') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sexe_femme">Femme</label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" required placeholder="+243..." value="<?= htmlspecialchars($form_data['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email (si disponible)</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse physique <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="adresse" name="adresse" required value="<?= htmlspecialchars($form_data['adresse'] ?? '') ?>">
                </div>

                <h2 class="h4 section-title">Situation Actuelle</h2>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="situation_actuelle" id="etudiant" value="Élève / Étudiant(e)" required>
                        <label class="form-check-label" for="etudiant">Élève / Étudiant(e)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="situation_actuelle" id="recherche" value="En recherche d’emploi" required>
                        <label class="form-check-label" for="recherche">En recherche d’emploi</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="situation_actuelle" id="activite" value="En activité professionnelle" required>
                        <label class="form-check-label" for="activite">En activité professionnelle</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="situation_actuelle" id="serviteur" value="Serviteur/Servante dans l’église" required>
                        <label class="form-check-label" for="serviteur">Serviteur/Servante dans l’église</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="situation_actuelle" id="autre" value="Autre" required>
                        <label class="form-check-label" for="autre">Autre</label>
                    </div>
                    <div class="mt-2" id="autre_details_div" style="display: none;">
                        <input type="text" class="form-control" id="situation_autre_details" name="situation_autre_details" placeholder="Précisez ici">
                    </div>
                </div>
                
                <h2 class="h4 section-title">Église d'Appartenance</h2>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom_eglise_appartenance" class="form-label">Nom de l'église <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_eglise_appartenance" name="nom_eglise_appartenance" required value="<?= htmlspecialchars($form_data['nom_eglise_appartenance'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="pasteur_responsable" class="form-label">Pasteur responsable <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pasteur_responsable" name="pasteur_responsable" required value="<?= htmlspecialchars($form_data['pasteur_responsable'] ?? '') ?>">
                    </div>
                </div>

                <h2 class="h4 section-title">Motivation</h2>
                
                <div class="mb-3">
                    <label for="motivation" class="form-label">Pourquoi souhaitez-vous suivre cette formation ? (En une ou deux phrases) <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="motivation" name="motivation" rows="3" required><?= htmlspecialchars($form_data['motivation'] ?? '') ?></textarea>
                </div>
                
                <h2 class="h4 section-title">Engagement</h2>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="engagement_serieux" name="engagement_serieux" required>
                    <label class="form-check-label" for="engagement_serieux">
                        Je m’engage à suivre sérieusement la formation pendant toute la durée du programme. <span class="text-danger">*</span>
                    </label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" value="1" id="engagement_paiement" name="engagement_paiement" required>
                    <label class="form-check-label" for="engagement_paiement">
                        Je confirme ma participation en payant la somme de 10 $ avant le début des cours. <span class="text-danger">*</span>
                    </label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Soumettre mon inscription</button>
                </div>

            </form>
            
            <div class="mt-5 text-center p-3" style="background-color: #e9ecef; border-radius: 8px;">
                <h3 class="h5">Intégrez le groupe WhatsApp</h3>
                <p>Une fois votre inscription soumise, écrivez à ce numéro pour être ajouté au groupe de la formation :</p>
                <p class="h4 fw-bold"><i class="bi bi-whatsapp"></i> +243814926220</p>
            </div>

        </div>
    </div>
</div>

<!-- Script JavaScript pour gérer l'affichage du champ "Autre" -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="situation_actuelle"]');
        const autreDiv = document.getElementById('autre_details_div');
        const autreInput = document.getElementById('situation_autre_details');

        function toggleAutreDetails() {
            if (document.getElementById('autre').checked) {
                autreDiv.style.display = 'block';
                autreInput.required = true;
            } else {
                autreDiv.style.display = 'none';
                autreInput.required = false;
                autreInput.value = '';
            }
        }

        radios.forEach(radio => radio.addEventListener('change', toggleAutreDetails));

        // Au cas où le formulaire est re-rempli après une erreur
        toggleAutreDetails(); 
    });
</script>

</body>
</html>