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
    } elseif (!preg_match('/@gmail\.com$/i', $email)) {
        $error_message = '❌ Seules les adresses Gmail (@gmail.com) sont acceptées pour des raisons de sécurité';
    } elseif ($payment_amount < 1) {
        $error_message = '❌ Montant minimum: 1 USD';
    } else {
        // Vérifier que l'email n'existe pas déjà dans subscription_codes ou trial_codes
        $stmt = $pdo->prepare("
            SELECT email FROM subscription_codes WHERE email = ?
            UNION
            SELECT email FROM trial_codes WHERE email = ?
            UNION
            SELECT email FROM active_clients WHERE email = ?
        ");
        $stmt->execute([$email, $email, $email]);
        
        if ($stmt->fetch()) {
            $error_message = '❌ Cette adresse email est déjà utilisée. Utilisez une autre adresse Gmail ou contactez le support.';
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
                $error_message = '❌ Erreur lors de l\'enregistrement: ' . $e->getMessage();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --pp-blue: #0070e0;
            --pp-blue-dark: #003087;
            --pp-cyan: #00a8ff;
            --pp-bg: #f5f7fb;
            --pp-white: #ffffff;
            --pp-text: #0b1f3a;
            --pp-border: #e1e8f0;
            --pp-success: #1f8f6a;
            --pp-orange: #ff6b35;
            --pp-shadow: rgba(0, 48, 135, 0.08);
            --pp-shadow-lg: rgba(0, 48, 135, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--pp-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ===== CONTAINER ===== */
        .container-subscription {
            width: 100%;
            max-width: 650px;
            background: var(--pp-white);
            border-radius: 24px;
            padding: 0;
            box-shadow: 0 20px 60px var(--pp-shadow-lg);
            overflow: hidden;
            animation: fadeSlide 0.8s ease;
            border: 1px solid var(--pp-border);
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            padding: 48px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(0, 168, 255, 0.2) 0%, transparent 50%);
        }

        .logo {
            font-size: 56px;
            margin-bottom: 16px;
            animation: pulse 2s infinite;
            position: relative;
            z-index: 1;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.85);
            position: relative;
            z-index: 1;
        }

        /* ===== BODY CONTENT ===== */
        .content-body {
            padding: 40px;
        }

        /* ===== PRICING BOX ===== */
        .pricing-box {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.05) 0%, rgba(0, 168, 255, 0.05) 100%);
            border: 2px solid var(--pp-blue);
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            animation: fadeSlide 0.9s ease;
        }

        .pricing-box::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            height: 4px;
            background: linear-gradient(90deg, var(--pp-blue), var(--pp-cyan), var(--pp-blue));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .pricing-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .pricing-amount {
            font-size: 48px;
            font-weight: 700;
            color: var(--pp-blue);
            margin-bottom: 8px;
            line-height: 1;
        }

        .pricing-period {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        /* ===== BENEFITS ===== */
        .benefits {
            background: linear-gradient(135deg, rgba(31, 143, 106, 0.05) 0%, rgba(31, 143, 106, 0.02) 100%);
            border-left: 4px solid var(--pp-success);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            animation: slideInRight 1s ease;
        }

        .benefits-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--pp-success);
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }

        .benefit-item {
            font-size: 14px;
            color: var(--pp-text);
            margin-bottom: 10px;
            padding-left: 28px;
            position: relative;
            line-height: 1.6;
        }

        .benefit-item:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--pp-success);
            font-weight: 700;
            font-size: 16px;
        }

        .benefit-item:last-child {
            margin-bottom: 0;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--pp-text);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-label .required {
            color: #dc2626;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background: var(--pp-bg);
            border: 2px solid var(--pp-border);
            border-radius: 10px;
            color: var(--pp-text);
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--pp-blue);
            background: var(--pp-white);
            box-shadow: 0 0 0 4px rgba(0, 112, 224, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ===== BUTTONS ===== */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 24px rgba(0, 112, 224, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 112, 224, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeSlide 0.5s ease;
        }

        .alert-error {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            color: #dc2626;
        }

        .alert-error::before {
            content: '❌';
            font-size: 20px;
        }

        .alert-success {
            background: rgba(31, 143, 106, 0.1);
            border: 1px solid var(--pp-success);
            color: var(--pp-success);
        }

        .alert-success::before {
            content: '✅';
            font-size: 20px;
        }

        /* ===== CODE BOX ===== */
        .code-box {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.08) 0%, rgba(0, 168, 255, 0.08) 100%);
            border: 2px dashed var(--pp-blue);
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            margin-top: 24px;
            animation: fadeSlide 0.8s ease;
        }

        .code-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .code-display {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: 700;
            color: var(--pp-blue);
            letter-spacing: 2px;
            word-break: break-all;
            margin-bottom: 20px;
            padding: 16px;
            background: rgba(0, 112, 224, 0.05);
            border-radius: 10px;
        }

        /* ===== NEXT STEPS ===== */
        .next-steps {
            background: linear-gradient(135deg, rgba(31, 143, 106, 0.05) 0%, rgba(31, 143, 106, 0.02) 100%);
            border: 1px solid rgba(31, 143, 106, 0.2);
            padding: 28px;
            border-radius: 16px;
            margin-top: 24px;
            animation: slideInRight 1s ease;
        }

        .step-title {
            font-weight: 700;
            color: var(--pp-blue);
            margin-bottom: 20px;
            font-size: 16px;
            text-align: center;
        }

        .step-desc {
            font-size: 14px;
            color: var(--pp-text);
            line-height: 1.8;
            margin-bottom: 16px;
            padding: 16px;
            background: white;
            border-radius: 10px;
            border-left: 3px solid var(--pp-blue);
        }

        .step-desc strong {
            color: var(--pp-blue);
            display: block;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .contact-box {
            background: rgba(255, 107, 53, 0.08);
            padding: 16px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 13px;
            color: var(--pp-text);
            text-align: center;
            border: 1px solid rgba(255, 107, 53, 0.2);
        }

        /* ===== FOOTER ===== */
        .footer-links {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--pp-border);
            font-size: 13px;
        }

        .footer-links a {
            color: var(--pp-blue);
            text-decoration: none;
            margin: 0 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .footer-links a:hover {
            color: var(--pp-blue-dark);
            text-decoration: underline;
        }

        .footer-note {
            text-align: center;
            margin-top: 16px;
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.6;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 600px) {
            .container-subscription {
                border-radius: 16px;
            }

            .header {
                padding: 32px 24px;
            }

            .content-body {
                padding: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .title {
                font-size: 26px;
            }

            .pricing-amount {
                font-size: 40px;
            }

            .logo {
                font-size: 48px;
            }
        }
    </style>
</head>

<body>

<div class="container-subscription">

    <div class="header">
        <div class="logo">💳</div>
        <div class="title">Abonnement Mensuel</div>
        <div class="subtitle">Accès complet pendant 30 jours</div>
    </div>

    <div class="content-body">

    <?php if (!$success_message): ?>

        <div class="pricing-box">
            <div class="pricing-label">Tarif Standard</div>
            <div class="pricing-amount">10 $</div>
            <div class="pricing-period">pour 1 mois complet</div>
        </div>

        <div class="benefits">
            <div class="benefits-title">✨ Inclus dans votre abonnement</div>
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
                    <label class="form-label">Prénom <span class="required">*</span></label>
                    <input type="text" name="first_name" class="form-control" placeholder="Votre prénom" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nom <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control" placeholder="Votre nom" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Gmail <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="votreadresse@gmail.com" required pattern=".+@gmail\.com$" title="Seules les adresses Gmail sont acceptées">
                <small style="color: #7d8fa3; font-size: 11px; display: block; margin-top: 4px;">
                    🔒 Seules les adresses Gmail (@gmail.com) sont acceptées pour la sécurité
                </small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+243 xxx xxx xxx">
                </div>
                <div class="form-group">
                    <label class="form-label">Entreprise <span class="required">*</span></label>
                    <input type="text" name="company_name" class="form-control" placeholder="Nom entreprise" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Montant (USD) <span class="required">*</span></label>
                <input type="number" name="payment_amount" class="form-control" 
                    value="10" min="1" step="0.01" required placeholder="10.00">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-credit-card"></i> Générer Code Abonnement
            </button>
        </form>

    <?php else: ?>

        <div class="alert alert-success"><?= $success_message ?></div>

        <div class="code-box">
            <div class="code-label">🎫 Votre Code d'Abonnement</div>
            <div class="code-display"><?= htmlspecialchars($subscription_code) ?></div>
            <button onclick="copyCode()" class="btn-submit">
                <i class="fas fa-copy"></i> Copier le Code
            </button>
        </div>

        <div class="next-steps">
            <div class="step-title">📋 Prochaines Étapes</div>
            
            <div class="step-desc">
                <strong>1️⃣ Effectuer le paiement</strong>
                Montant: <span style="color: var(--pp-blue); font-weight: bold;">10 $ USD</span><br>
                Envoyez le paiement via Mobile Money ou virement bancaire
            </div>

            <div class="step-desc">
                <strong>2️⃣ Envoyer le code avec preuve</strong>
                Contactez-nous avec ce code et la capture d'écran de votre paiement
            </div>

            <div class="step-desc">
                <strong>3️⃣ Validation sous 24h</strong>
                Notre équipe valide votre paiement et active votre compte immédiatement
            </div>

            <div class="contact-box">
                <strong style="color: var(--pp-orange);">📞 Informations de Contact</strong><br>
                📧 Email: admin@cartelplus.cd<br>
                📱 WhatsApp: +243 123 456 789<br>
                ⏰ Disponible 7j/7 de 8h à 20h
            </div>
        </div>

        <div class="footer-note">
            Votre abonnement expire automatiquement après 30 jours.<br>
            Renouvelez avant expiration pour éviter toute interruption de service.
        </div>

    <?php endif; ?>

    <div class="footer-links">
        <a href="../"><i class="fas fa-home"></i> Retour Accueil</a>
    </div>

    </div>

</div>

<script>
function copyCode() {
    const code = '<?= htmlspecialchars($subscription_code) ?>';
    navigator.clipboard.writeText(code).then(() => {
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Code Copié !';
        btn.style.background = 'linear-gradient(135deg, var(--pp-success) 0%, #1a7a52 100%)';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '';
        }, 2000);
    });
}
</script>

</body>
</html>
