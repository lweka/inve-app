<?php
session_start();
  require 'connectDb.php';   // Votre fichier de connexion

$error_message = '';
$form_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Nettoyage et validation des données
    $form_data['motivation']              = trim(htmlspecialchars($_POST['motivation'] ?? ''));
    $form_data['nom_enfant']              = trim(htmlspecialchars($_POST['nom_enfant'] ?? ''));
    $form_data['sexe_enfant']             = trim(htmlspecialchars($_POST['sexe_enfant'] ?? ''));
    $form_data['age_enfant']              = filter_input(INPUT_POST, 'age_enfant', FILTER_VALIDATE_INT);
    $form_data['classe_actuelle']         = trim(htmlspecialchars($_POST['classe_actuelle'] ?? ''));
    $form_data['ecole_frequentee']        = trim(htmlspecialchars($_POST['ecole_frequentee'] ?? ''));
    $form_data['nom_parent']              = trim(htmlspecialchars($_POST['nom_parent'] ?? ''));
    $form_data['telephone_parent']        = trim(htmlspecialchars($_POST['telephone_parent'] ?? ''));
    $form_data['email_parent']            = filter_input(INPUT_POST, 'email_parent', FILTER_VALIDATE_EMAIL) ?: null;
    $form_data['engagement_participation'] = isset($_POST['engagement_participation']);
    $form_data['engagement_paiement']     = isset($_POST['engagement_paiement']);

    // Vérification des champs obligatoires
    if (empty($form_data['motivation']) || empty($form_data['nom_enfant']) || empty($form_data['sexe_enfant']) || empty($form_data['age_enfant']) || empty($form_data['classe_actuelle']) || empty($form_data['ecole_frequentee']) || empty($form_data['nom_parent']) || empty($form_data['telephone_parent']) || !$form_data['engagement_participation'] || !$form_data['engagement_paiement']) {
        $error_message = "Veuillez remplir tous les champs obligatoires (*) et cocher les cases d'engagement.";
    } else {
        try {
            // 2. Insertion dans la base de données
            $sql = "INSERT INTO inscriptions_vacances (motivation, nom_enfant, sexe_enfant, age_enfant, classe_actuelle, ecole_frequentee, nom_parent, telephone_parent, email_parent, engagement_participation, engagement_paiement) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                $form_data['motivation'],
                $form_data['nom_enfant'],
                $form_data['sexe_enfant'],
                $form_data['age_enfant'],
                $form_data['classe_actuelle'],
                $form_data['ecole_frequentee'],
                $form_data['nom_parent'],
                $form_data['telephone_parent'],
                $form_data['email_parent'],
                $form_data['engagement_participation'],
                $form_data['engagement_paiement']
            ]);

            // 3. Envoi de l'email de notification
            $to = 'jlweka@cartelplus.tech';
            $subject = 'NOUVELLE INSCRIPTION - FORMATION VACANCES';
            
            $message = "
            <html><body>
                <h2 style='color: #0d6efd;'>Nouvelle Inscription : Formation Vacances</h2>
                <p>Un parent a inscrit son enfant à la formation 'Initiation à l’ordinateur, Word et Excel'.</p>
                <h3 style='border-bottom: 1px solid #ccc; padding-bottom: 5px;'>Détails de l'enfant</h3>
                <table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                    <tr style='background-color: #f2f2f2;'><td style='width: 30%;'><strong>Nom de l'enfant :</strong></td><td>" . $form_data['nom_enfant'] . "</td></tr>
                    <tr><td><strong>Sexe :</strong></td><td>" . $form_data['sexe_enfant'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Âge :</strong></td><td>" . $form_data['age_enfant'] . " ans</td></tr>
                    <tr><td><strong>Classe actuelle :</strong></td><td>" . $form_data['classe_actuelle'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>École fréquentée :</strong></td><td>" . $form_data['ecole_frequentee'] . "</td></tr>
                </table>
                <h3 style='border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 20px;'>Détails du Parent / Tuteur</h3>
                <table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                    <tr style='background-color: #f2f2f2;'><td style='width: 30%;'><strong>Nom du parent/tuteur :</strong></td><td>" . $form_data['nom_parent'] . "</td></tr>
                    <tr><td><strong>Téléphone :</strong></td><td>" . $form_data['telephone_parent'] . "</td></tr>
                    <tr style='background-color: #f2f2f2;'><td><strong>Email :</strong></td><td>" . ($form_data['email_parent'] ?: 'Non fourni') . "</td></tr>
                </table>
                <h3 style='border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 20px;'>Motivation</h3>
                <p>" . nl2br($form_data['motivation']) . "</p>
            </body></html>";

            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: <jlweka@cartelplus.tech>\r\n";
            mail($to, $subject, $message, $headers);

            // 4. Redirection vers la page de succès
            header('Location: vacancesok.php');
            exit();

        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'enregistrement. Veuillez réessayer.";
            // error_log("Erreur SQL : " . $e->getMessage()); // Pour le debug
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Formation pour Enfants en Vacances</title>
     <!-- Balise de description pour les moteurs de recherche -->
    <meta name="description" content="Inscrivez votre enfant à notre programme de vacances : initiation à l'ordinateur, Word et Excel. Une formation ludique et essentielle pour 10$. Début le 28 Juillet 2025.">

    <!-- Mots-clés -->
    <meta name="keywords" content="formation vacances, enfants, initiation informatique, word, excel, cours ordinateur, Kinshasa, RDC, inscription, été 2025">
    
    <!-- Balise Canonical pour indiquer l'URL préférée -->
    <link rel="canonical" href="https://votre-domaine.com/formationvacances">

    <!-- Balises Open Graph pour le partage sur les réseaux sociaux -->
    <meta property="og:title" content="Formation Vacances pour Enfants | Initiation Informatique">
    <meta property="og:description" content="Inscrivez votre enfant à notre programme d'initiation à l'ordinateur, Word et Excel. Session d'un mois pour 10$.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="Logo.png" type="image/png">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 800px; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .card-header { background-color: #198754; color: white; padding: 1.5rem; }
        .btn-success { background-color: #198754; border: none; }
        .btn-success:hover { background-color: #157347; }
        .form-label { font-weight: 600; }
        .section-title { color: #198754; border-bottom: 2px solid #198754; padding-bottom: 10px; margin-top: 2rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="card">
        <div class="card-header text-center">
            <h1 class="h3 mb-0">Fiche d'Inscription</h1>
            <p class="mb-0">Formation pour Enfants en Vacances</p>
        </div>
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <p class="lead fw-bold">Thème : Initiation à l’ordinateur, Word et Excel</p>
                <p><strong>Début :</strong> 28 Juillet 2025 | <strong>Durée :</strong> 1 mois | <strong>Coût :</strong> 10 $</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $error_message; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <h2 class="h4 section-title">Informations de l’Enfant</h2>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="nom_enfant" class="form-label">Nom complet de l'enfant <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_enfant" name="nom_enfant" required value="<?= htmlspecialchars($form_data['nom_enfant'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="age_enfant" class="form-label">Âge <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="age_enfant" name="age_enfant" required min="1" value="<?= htmlspecialchars($form_data['age_enfant'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Sexe <span class="text-danger">*</span></label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sexe_enfant" id="sexe_garcon" value="Garçon" required>
                        <label class="form-check-label" for="sexe_garcon">Garçon</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="sexe_enfant" id="sexe_fille" value="Fille" required>
                        <label class="form-check-label" for="sexe_fille">Fille</label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="classe_actuelle" class="form-label">Classe actuelle <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="classe_actuelle" name="classe_actuelle" required value="<?= htmlspecialchars($form_data['classe_actuelle'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ecole_frequentee" class="form-label">École fréquentée <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ecole_frequentee" name="ecole_frequentee" required value="<?= htmlspecialchars($form_data['ecole_frequentee'] ?? '') ?>">
                    </div>
                </div>

                <h2 class="h4 section-title">Informations du Parent / Tuteur</h2>
                
                <div class="mb-3">
                    <label for="nom_parent" class="form-label">Nom du Parent/Tuteur (en guise de signature) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom_parent" name="nom_parent" required value="<?= htmlspecialchars($form_data['nom_parent'] ?? '') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telephone_parent" class="form-label">Téléphone du Parent/Tuteur <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="telephone_parent" name="telephone_parent" required placeholder="+243..." value="<?= htmlspecialchars($form_data['telephone_parent'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email_parent" class="form-label">Email du Parent/Tuteur (optionnel)</label>
                        <input type="email" class="form-control" id="email_parent" name="email_parent" value="<?= htmlspecialchars($form_data['email_parent'] ?? '') ?>">
                    </div>
                </div>

                <h2 class="h4 section-title">Motivation</h2>
                <div class="mb-3">
                    <label for="motivation" class="form-label">Pourquoi souhaitez-vous que votre enfant suive cette formation ? <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="motivation" name="motivation" rows="3" required><?= htmlspecialchars($form_data['motivation'] ?? '') ?></textarea>
                </div>
                
                <h2 class="h4 section-title">Engagement du Parent / Tuteur</h2>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="engagement_participation" name="engagement_participation" required>
                    <label class="form-check-label" for="engagement_participation">
                        Je promets que mon enfant participera sérieusement à tous les cours. <span class="text-danger">*</span>
                    </label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="engagement_paiement" name="engagement_paiement" required>
                    <label class="form-check-label" for="engagement_paiement">
                        J'accepte de payer les 10$ avant le début de la session. <span class="text-danger">*</span>
                    </label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Inscrire mon enfant</button>
                </div>
            </form>
            
            <div class="mt-5 text-center p-3 bg-light border rounded">
                <h3 class="h5">Informations Supplémentaires</h3>
                <p>Pour toute question, n'hésitez pas à nous contacter via ce numéro :</p>
                <p class="h4 fw-bold"><i class="bi bi-whatsapp"></i> +243814926220</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>