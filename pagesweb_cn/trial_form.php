<?php
/**
 * ===================================
 * FORMULAIRE D'ESSAI GRATUIT (7 jours)
 * ===================================
 * Les utilisateurs remplissent ce formulaire
 * et reçoivent un code pour tester 7 jours
 */

require_once __DIR__ . '/connectDb.php';

$error_message = '';
$success_message = '';
$trial_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');

    // Validation
    if (!$first_name || !$last_name || !$email) {
        $error_message = '❌ Veuillez remplir tous les champs obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '❌ Adresse email invalide';
    } else {
        // Générer code d'essai unique
        $trial_code = 'TRIAL-' . strtoupper(uniqid());
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO trial_codes (code, first_name, last_name, email, phone, company_name, status)
                VALUES (?, ?, ?, ?, ?, ?, 'unused')
            ");
            $stmt->execute([$trial_code, $first_name, $last_name, $email, $phone, $company_name]);
            
            $success_message = "✅ Code d'essai généré avec succès !";
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
    <title>Essai Gratuit 7 Jours | CartelPlus Congo</title>
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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
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

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(0, 112, 224, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(0, 112, 224, 0);
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

        .container-trial {
            width: 100%;
            max-width: 550px;
            background: var(--pp-white);
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(11, 31, 58, 0.08), 0 0 1px rgba(11, 31, 58, 0.1);
            animation: fadeSlide 0.8s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeSlide 0.8s ease-out 0.1s both;
        }

        .header::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--pp-blue) 0%, var(--pp-cyan) 100%);
            border-radius: 2px;
            margin: 20px auto 0;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            color: var(--pp-text);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 14px;
            color: #7d8fa3;
        }

        .benefits {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.08) 0%, rgba(0, 168, 255, 0.05) 100%);
            border-left: 4px solid var(--pp-blue);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            animation: slideInRight 0.6s ease-out 0.2s both;
        }

        .benefits-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--pp-blue);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .benefit-item {
            font-size: 13px;
            color: var(--pp-text);
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
            transition: all 0.3s;
        }

        .benefit-item:hover {
            transform: translateX(4px);
        }

        .benefit-item:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--pp-blue);
            font-weight: bold;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
            animation: fadeSlide 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.5s; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--pp-text);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .required {
            color: var(--pp-orange);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            background: var(--pp-bg);
            border: 2px solid #e0e4e9;
            border-radius: 8px;
            color: var(--pp-text);
            font-size: 14px;
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
            color: #a8b4c1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 112, 224, 0.2);
            margin-top: 10px;
            animation: fadeSlide 0.6s ease-out 0.6s both;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 112, 224, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid;
            animation: fadeSlide 0.4s ease-out;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.08);
            border-left-color: #dc3545;
            color: #c71c1c;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.08);
            border-left-color: var(--pp-success);
            color: #155724;
        }

        .code-box {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.08) 0%, rgba(0, 168, 255, 0.08) 100%);
            border: 2px solid rgba(0, 112, 224, 0.15);
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            margin-top: 20px;
            animation: fadeSlide 0.6s ease-out 0.2s both;
        }

        .code-label {
            font-size: 11px;
            color: #7d8fa3;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .code-display {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 26px;
            font-weight: 700;
            color: var(--pp-blue);
            letter-spacing: 3px;
            word-break: break-all;
            margin-bottom: 15px;
            padding: 12px;
            background: var(--pp-white);
            border-radius: 8px;
            user-select: all;
        }

        .next-step {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 107, 53, 0.05) 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border-left: 4px solid var(--pp-orange);
            animation: slideInRight 0.6s ease-out 0.3s both;
        }

        .next-step-title {
            font-weight: 700;
            color: var(--pp-orange);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .next-step-desc {
            font-size: 13px;
            color: var(--pp-text);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .btn-validate {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 112, 224, 0.2);
        }

        .btn-validate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 112, 224, 0.35);
            text-decoration: none;
            color: white;
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            padding-top: 20px;
            border-top: 1px solid #e0e4e9;
        }

        .footer-links a {
            color: var(--pp-blue);
            text-decoration: none;
            font-weight: 600;
            margin: 0 12px;
            transition: all 0.3s;
        }

        .footer-links a:hover {
            color: var(--pp-cyan);
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container-trial {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .title {
                font-size: 26px;
            }

            .logo {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>

<div class="container-trial">

    <div class="header">
        <div class="logo">🚀</div>
        <div class="title">Essai Gratuit</div>
        <div class="subtitle">7 jours complets - Accès illimité</div>
    </div>

    <?php if (!$success_message): ?>

        <div class="benefits">
            <div class="benefits-title">✨ Inclus dans votre essai</div>
            <div class="benefit-item">Gestion complète des ventes POS</div>
            <div class="benefit-item">Dashboard administrateur</div>
            <div class="benefit-item">Rapports journaliers & marges</div>
            <div class="benefit-item">Génération tickets PDF</div>
            <div class="benefit-item">Gestion des utilisateurs</div>
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
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="votre@email.com" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+243 xxx xxx xxx">
                </div>
                <div class="form-group">
                    <label class="form-label">Entreprise</label>
                    <input type="text" name="company_name" class="form-control" placeholder="Nom de votre entreprise">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-arrow-right"></i> Commencer l'Essai
            </button>
        </form>

    <?php else: ?>

        <div class="alert alert-success"><?= $success_message ?></div>

        <div class="code-box">
            <div class="code-label">🎫 Votre Code d'Essai</div>
            <div class="code-display"><?= htmlspecialchars($trial_code) ?></div>
            <button onclick="copyCode()" class="btn-validate">
                <i class="fas fa-copy"></i> Copier le Code
            </button>
        </div>

        <div class="next-step">
            <div class="next-step-title">📋 Prochaine Étape</div>
            <div class="next-step-desc">
                Cliquez sur le bouton ci-dessous pour activer votre essai. Notre équipe créera votre compte d'accès immédiatement et vous pourrez commencer à utiliser le système.
            </div>
            <a href="trial_verify.php?code=<?= urlencode($trial_code) ?>" class="btn-validate">
                <i class="fas fa-check-circle"></i> Valider et Activer →
            </a>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #7d8fa3;">
            <p>⏰ Votre essai expire automatiquement après 7 jours</p>
        </div>

    <?php endif; ?>

    <div class="footer-links">
        <a href="trial_form"><i class="fas fa-redo"></i> Nouvelle Inscription</a>
        <a href="subscription_buy"><i class="fas fa-credit-card"></i> Abonnement</a>
        <a href="../"><i class="fas fa-home"></i> Accueil</a>
    </div>

</div>

<script>
function copyCode() {
    const code = '<?= htmlspecialchars($trial_code) ?>';
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
