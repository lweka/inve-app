<?php
/**
 * ===================================
 * PAGE UPGRADE TRIAL → PRO
 * ===================================
 * Pour les utilisateurs en trial qui veulent passer Pro
 * Récupère automatiquement leurs infos, affiche instructions de paiement
 */

require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // Charge $client_code

// Récupérer les informations du client
$stmt = $pdo->prepare("
    SELECT first_name, last_name, email, company_name, subscription_type, expires_at
    FROM active_clients
    WHERE client_code = ? AND status = 'active'
");
$stmt->execute([$client_code]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header('Location: ../pagesweb_cn/connect-parse.php?role=admin&message=client_not_found');
    exit;
}

// Vérifier que c'est bien un compte trial
if ($client['subscription_type'] !== 'trial') {
    header('Location: dashboard.php?message=already_pro');
    exit;
}

// Vérifier s'il y a déjà une demande d'upgrade en cours
$pending_upgrade = false;
$stmt = $pdo->prepare("
    SELECT code FROM subscription_codes 
    WHERE first_name = ? AND last_name = ? AND email = ? 
    AND status = 'pending' AND notes LIKE '%Upgrade from trial%'
");
$stmt->execute([$client['first_name'], $client['last_name'], $client['email']]);
if ($stmt->fetch()) {
    $pending_upgrade = true;
}

// Générer le code d'abonnement si le formulaire est soumis (demande upgrade)
$upgrade_requested = false;
$subscription_code = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_upgrade'])) {
    // Vérifier à nouveau s'il y a déjà une demande en cours
    $stmt = $pdo->prepare("
        SELECT code FROM subscription_codes 
        WHERE first_name = ? AND last_name = ? AND email = ? 
        AND status = 'pending' AND notes LIKE '%Upgrade from trial%'
    ");
    $stmt->execute([$client['first_name'], $client['last_name'], $client['email']]);
    
    if ($stmt->fetch()) {
        $error_message = '⏳ Vous avez déjà une demande d\'upgrade en cours. Veuillez patienter que l\'administrateur la valide.';
    } else {
        // Créer une demande d'upgrade dans subscription_codes
        $subscription_code = 'SUB-UPGRADE-' . strtoupper(uniqid());
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_codes (
                    code, first_name, last_name, email, company_name,
                    payment_amount, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, 10, 'pending', 'Upgrade from trial')
            ");
            $stmt->execute([
                $subscription_code,
                $client['first_name'],
                $client['last_name'],
                $client['email'],
                $client['company_name']
            ]);
            
            $upgrade_requested = true;
        } catch (PDOException $e) {
            $error_message = '❌ Erreur lors de la demande: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passer Pro | CartelPlus Congo</title>
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
            --pp-orange: #ff6b35;
            --pp-success: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0%, 100% {
                background-position: -1000px 0;
            }
            50% {
                background-position: 1000px 0;
            }
        }

        body {
            background: linear-gradient(135deg, var(--pp-bg) 0%, #f0f2f7 100%);
            color: var(--pp-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-upgrade {
            width: 100%;
            max-width: 600px;
            background: var(--pp-white);
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(11, 31, 58, 0.08), 0 0 1px rgba(11, 31, 58, 0.1);
            animation: fadeSlide 0.8s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeSlide 0.8s ease-out 0.1s both;
        }

        .header::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--pp-orange) 0%, #ff8c42 100%);
            border-radius: 2px;
            margin: 20px auto 0;
        }

        .logo {
            font-size: 64px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            color: var(--pp-text);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--pp-orange) 0%, #ff8c42 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 14px;
            color: #7d8fa3;
        }

        .client-info {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.08) 0%, rgba(0, 168, 255, 0.05) 100%);
            border-left: 4px solid var(--pp-blue);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 13px;
            line-height: 1.8;
        }

        .pricing-box {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 140, 66, 0.08) 100%);
            border: 2px solid rgba(255, 107, 53, 0.2);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .pricing-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s infinite;
        }

        .pricing-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--pp-orange);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .pricing-amount {
            font-size: 48px;
            font-weight: 700;
            color: var(--pp-orange);
            margin: 10px 0;
        }

        .pricing-period {
            font-size: 14px;
            color: #7d8fa3;
        }

        .steps {
            margin: 30px 0;
        }

        .step {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
            padding: 16px;
            background: var(--pp-bg);
            border-radius: 12px;
        }

        .step-number {
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(0, 112, 224, 0.2);
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 700;
            color: var(--pp-text);
            margin-bottom: 4px;
        }

        .step-desc {
            font-size: 13px;
            color: #7d8fa3;
        }

        .contact-box {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 140, 66, 0.05) 100%);
            border-left: 4px solid var(--pp-orange);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.8;
        }

        .btn-upgrade {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--pp-orange) 0%, #ff8c42 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.25);
            margin-top: 20px;
        }

        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255, 107, 53, 0.35);
        }

        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: var(--pp-bg);
            color: var(--pp-text);
            border: 2px solid #e0e4e9;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: white;
            border-color: var(--pp-blue);
            color: var(--pp-blue);
            text-decoration: none;
        }

        .success-box {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.08) 0%, rgba(40, 167, 69, 0.05) 100%);
            border: 2px solid rgba(40, 167, 69, 0.2);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            margin: 20px 0;
        }

        .code-display {
            background: var(--pp-bg);
            padding: 16px;
            border-radius: 12px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 20px;
            font-weight: 700;
            color: var(--pp-blue);
            letter-spacing: 2px;
            margin: 20px 0;
            word-break: break-all;
        }

        @media (max-width: 600px) {
            .container-upgrade {
                padding: 30px 20px;
            }

            .title {
                font-size: 26px;
            }

            .logo {
                font-size: 48px;
            }

            .pricing-amount {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>

<div class="container-upgrade">

    <div class="header">
        <div class="logo">👑</div>
        <div class="title">Passer Pro</div>
        <div class="subtitle">Upgrade votre compte trial vers Pro</div>
    </div>

    <?php if (!$upgrade_requested): ?>

        <div style="background: linear-gradient(135deg, #e8f4fd 0%, #d4e8f9 100%); border-left: 4px solid var(--pp-blue); border-radius: 12px; padding: 18px; margin-bottom: 24px;">
            <strong style="color: var(--pp-blue);">ℹ️ Comment ça marche ?</strong><br>
            <ol style="margin: 10px 0 0; padding-left: 20px; font-size: 14px; color: #555;">
                <li>Vous cliquez sur "Demander l'Upgrade Pro" ci-dessous</li>
                <li>Effectuez le paiement de 10 $ (Mobile Money, virement, espèces)</li>
                <li>Contactez l'administrateur avec votre preuve de paiement</li>
                <li>L'admin clique sur <strong>"✅ Valider"</strong> dans le panneau "Codes d'Abonnement"</li>
                <li>Vous recevez un email de confirmation et votre compte devient Pro (30 jours)</li>
            </ol>
        </div>

        <div class="client-info">
            <strong>📋 Vos informations (récupérées automatiquement):</strong><br>
            Nom: <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?><br>
            Email: <?= htmlspecialchars($client['email']) ?><br>
            <?php if ($client['company_name']): ?>
            Entreprise: <?= htmlspecialchars($client['company_name']) ?><br>
            <?php endif; ?>
            Statut actuel: 📅 Essai gratuit<br>
            Expire le: <?= date('d/m/Y', strtotime($client['expires_at'])) ?>
        </div>

        <div class="pricing-box">
            <div class="pricing-label">💎 Tarif Pro</div>
            <div class="pricing-amount">10 $</div>
            <div class="pricing-period">pour 30 jours complets</div>
        </div>

        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Effectuer le paiement</div>
                    <div class="step-desc">
                        Montant: <strong>10 $ USD</strong><br>
                        Envoyez via Mobile Money, virement bancaire ou espèces
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Envoyer la preuve de paiement</div>
                    <div class="step-desc">
                        Contactez-nous avec la capture d'écran de votre paiement et votre code
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <div class="step-title">Validation sous 24h</div>
                    <div class="step-desc">
                        Notre équipe valide votre paiement et active votre compte Pro. Vous recevrez un email de confirmation.
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-box">
            <strong style="color: var(--pp-orange);">📞 Informations de Contact</strong><br>
            📧 Email: admin@cartelplus.cd<br>
            📱 WhatsApp: +243 123 456 789<br>
            ⏰ Disponible 7j/7 de 8h à 20h
        </div>

        <form method="POST">
            <?php if ($pending_upgrade): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #856404;">
                    <strong>⏳ Demande en cours</strong><br>
                    Vous avez déjà une demande d'upgrade Pro en attente de validation. Veuillez patienter que notre équipe la traite (généralement 24h).
                </div>
                <button type="button" class="btn-upgrade" disabled style="opacity: 0.6; cursor: not-allowed;">
                    <i class="fas fa-hourglass-half"></i> Demande en attente...
                </button>
            <?php else: ?>
                <button type="submit" name="request_upgrade" class="btn-upgrade">
                    <i class="fas fa-crown"></i> Demander l'Upgrade Pro
                </button>
            <?php endif; ?>
        </form>

        <a href="dashboard.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au Dashboard
        </a>

    <?php else: ?>

        <div class="success-box">
            <div style="font-size: 48px; margin-bottom: 15px;">✅</div>
            <h3 style="color: var(--pp-success); margin-bottom: 10px;">Demande d'upgrade envoyée !</h3>
            <p style="color: #7d8fa3; font-size: 14px; margin-bottom: 20px;">
                Votre demande a été enregistrée avec succès
            </p>

            <div class="code-display"><?= htmlspecialchars($subscription_code) ?></div>

            <p style="font-size: 13px; color: var(--pp-text); line-height: 1.6;">
                <strong>Prochaines étapes :</strong><br>
                1. Effectuez le paiement de 10 $ USD<br>
                2. Contactez-nous avec ce code et votre preuve de paiement<br>
                3. Attendez la validation (sous 24h)<br>
                4. Recevez l'email de confirmation de votre compte Pro
            </p>
        </div>

        <a href="dashboard.php" class="btn-upgrade">
            <i class="fas fa-home"></i> Retour au Dashboard
        </a>

    <?php endif; ?>

</div>

</body>
</html>
