<?php
/**
 * ===================================
 * FORMULAIRE D'ACHAT ABONNEMENT (1 mois)
 * ===================================
 * Les entreprises achètent un abonnement 1 mois
 * Reçoivent un code à faire valider par l'admin
 */

require_once __DIR__ . '/connectDb.php';

$error_message = '';
$success_message = '';
$subscription_code = '';
$payment_amount = 10; // Montant par défaut: 10 USD

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $payment_amount = (float)($_POST['payment_amount'] ?? 0);

    // Validation
    if (!$first_name || !$last_name || !$email || !$payment_amount) {
        $error_message = '❌ Veuillez remplir tous les champs obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '❌ Adresse email invalide';
    } elseif ($payment_amount < 1) {
        $error_message = '❌ Montant minimum: 1 USD';
    } else {
        // Générer code d'abonnement unique
        $subscription_code = 'SUB-' . strtoupper(uniqid());
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_codes (
                    code, first_name, last_name, email, phone, company_name, 
                    payment_amount, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $subscription_code, $first_name, $last_name, $email, $phone, 
                $company_name, $payment_amount
            ]);
            
            $success_message = '✅ Code d\'abonnement généré !';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_message = '❌ Cette adresse email est déjà enregistrée';
            } else {
                $error_message = '❌ Erreur lors de l\'enregistrement';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement 1 Mois | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
        }

        body {
            background: linear-gradient(135deg, #0B0E14 0%, #1a1f2e 100%);
            color: var(--white);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-subscription {
            width: 100%;
            max-width: 550px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        .pricing-box {
            background: rgba(242, 92, 42, 0.15);
            border: 2px solid var(--orange);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .pricing-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .pricing-amount {
            font-size: 32px;
            font-weight: 700;
            color: var(--orange);
            margin-bottom: 8px;
        }

        .pricing-period {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .benefits {
            background: rgba(10, 111, 183, 0.1);
            border-left: 4px solid var(--blue);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .benefits-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 8px;
        }

        .benefit-item {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .benefit-item:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--blue);
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 8px;
            color: var(--white);
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--orange);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 10px rgba(242, 92, 42, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--orange) 0%, #E84A1F 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(242, 92, 42, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border-left: 4px solid #dc3545;
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-left: 4px solid #28a745;
            color: #90EE90;
        }

        .next-steps {
            background: rgba(10, 111, 183, 0.15);
            border: 1px dashed var(--blue);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .code-box {
            background: rgba(10, 111, 183, 0.2);
            border: 1px dashed var(--blue);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .code-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .code-display {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            color: var(--orange);
            letter-spacing: 2px;
            word-break: break-all;
            margin-bottom: 15px;
        }

        .step-title {
            font-weight: 700;
            color: var(--orange);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .step-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .btn-waiting {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            cursor: default;
            margin-top: 10px;
            font-size: 12px;
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
        }

        .footer-links a {
            color: var(--orange);
            text-decoration: none;
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container-subscription {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .title {
                font-size: 22px;
            }

            .pricing-amount {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="container-subscription">

    <div class="header">
        <div class="logo">💳</div>
        <div class="title">Abonnement Mensuel</div>
        <div class="subtitle">Accès complet - 30 jours</div>
    </div>

    <?php if (!$success_message): ?>

        <div class="pricing-box">
            <div class="pricing-label">Tarif Standard</div>
            <div class="pricing-amount">10 $ USD</div>
            <div class="pricing-period">pour 1 mois complet</div>
        </div>

        <div class="benefits">
            <div class="benefits-title">Inclus dans votre abonnement</div>
            <div class="benefit-item">Gestion complète des ventes POS</div>
            <div class="benefit-item">Dashboard administrateur illimité</div>
            <div class="benefit-item">Rapports journaliers & marges avancées</div>
            <div class="benefit-item">Génération tickets PDF illimités</div>
            <div class="benefit-item">Support technique par email</div>
            <div class="benefit-item">Sauvegarde données quotidienne</div>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Entreprise *</label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Montant (USD) *</label>
                <input type="number" name="payment_amount" class="form-control" 
                    value="10" min="1" step="0.01" required>
            </div>

            <button type="submit" class="btn-submit">💰 Générer Code Abonnement</button>
        </form>

    <?php else: ?>

        <div class="alert alert-success"><?= $success_message ?></div>

        <div class="code-box">
            <div class="code-label">Votre Code d'Abonnement</div>
            <div class="code-display"><?= htmlspecialchars($subscription_code) ?></div>
            <button onclick="copyCode()" class="btn-submit">
                📋 Copier le Code
            </button>
        </div>

        <div class="next-steps">
            <div class="step-title">📋 Prochaines Étapes</div>
            
            <div class="step-desc" style="margin-bottom: 15px;">
                <strong>1️⃣ Effectuer le paiement</strong><br>
                Montant: <span style="color: var(--orange); font-weight: bold;">10 $ USD</span>
            </div>

            <div class="step-desc" style="margin-bottom: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <strong>2️⃣ Envoyer le code</strong><br>
                Contactez-nous avec ce code et la preuve de paiement
            </div>

            <div class="step-desc" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <strong>3️⃣ Validation admin</strong><br>
                Nous validons votre paiement et votre compte est activé
            </div>

            <div style="background: rgba(242, 92, 42, 0.15); padding: 12px; border-radius: 5px; margin-top: 15px; font-size: 11px; color: rgba(255, 255, 255, 0.8);">
                📧 Contact: admin@cartelplus.cd<br>
                📱 WhatsApp: +243 123 456 789
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 11px; color: rgba(255, 255, 255, 0.6);">
            <p>Votre abonnement expire automatiquement après 30 jours si non renouvelé</p>
        </div>

    <?php endif; ?>

    <div class="footer-links">
        <a href="trial_form">Essayer 7 jours</a>
        <a href="../">← Accueil</a>
    </div>

</div>

<script>
function copyCode() {
    const code = '<?= htmlspecialchars($subscription_code) ?>';
    navigator.clipboard.writeText(code).then(() => {
        alert('✅ Code copié !');
    });
}
</script>

</body>
</html>
