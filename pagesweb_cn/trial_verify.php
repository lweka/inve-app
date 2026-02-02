<?php
/**
 * ===================================
 * VALIDATION & ACTIVATION CODE ESSAI
 * ===================================
 * L'utilisateur valide son code d'essai
 * Et accède immédiatement au système
 */

require_once __DIR__ . '/connectDb.php';

$trial_code = isset($_GET['code']) ? trim($_GET['code']) : '';
$error_message = '';
$success_message = '';
$client_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_input = trim($_POST['trial_code'] ?? '');
    
    if (!$code_input) {
        $error_message = '❌ Veuillez entrer votre code d\'essai';
    } else {
        // Vérifier le code
        $stmt = $pdo->prepare("SELECT * FROM trial_codes WHERE code = ?");
        $stmt->execute([$code_input]);
        $trial = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trial) {
            $error_message = '❌ Code d\'essai invalide';
        } elseif ($trial['status'] === 'expired') {
            $error_message = '❌ Cet essai a expiré';
        } elseif ($trial['status'] === 'activated') {
            $error_message = '⚠️ Vous êtes déjà enregistré. <a href="login.php">Se connecter →</a>';
        } else {
            // Activer l'essai
            $client_code = 'CLI-TRIAL-' . strtoupper(uniqid());
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            try {
                // Créer le client actif
                $stmt_client = $pdo->prepare("
                    INSERT INTO active_clients (
                        client_code, first_name, last_name, email, company_name,
                        subscription_type, trial_code_id, status, created_at, expires_at
                    ) VALUES (?, ?, ?, ?, ?, 'trial', ?, 'active', NOW(), ?)
                ");
                $stmt_client->execute([
                    $client_code,
                    $trial['first_name'],
                    $trial['last_name'],
                    $trial['email'],
                    $trial['company_name'],
                    $trial['id'],
                    $expires_at
                ]);
                
                // Marquer le code d'essai comme activé
                $stmt_update = $pdo->prepare("
                    UPDATE trial_codes 
                    SET status = 'activated', activated_at = NOW()
                    WHERE id = ?
                ");
                $stmt_update->execute([$trial['id']]);
                
                // Rediriger vers le formulaire d'enregistrement
                header('Location: admin_register.php?code=' . urlencode($client_code));
                exit;
            } catch (PDOException $e) {
                $error_message = '❌ Erreur lors de l\'activation';
            }
        }
    }
}

// Si code valide passé en URL
if ($trial_code && !$error_message && !$success_message) {
    $stmt = $pdo->prepare("SELECT * FROM trial_codes WHERE code = ?");
    $stmt->execute([$trial_code]);
    $trial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trial && $trial['status'] === 'unused') {
        // Activer automatiquement
        $client_code = 'CLI-TRIAL-' . strtoupper(uniqid());
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        try {
            $pdo->beginTransaction();
            
            $stmt_client = $pdo->prepare("
                INSERT INTO active_clients (
                    client_code, first_name, last_name, email, company_name,
                    subscription_type, trial_code_id, status, created_at, expires_at
                ) VALUES (?, ?, ?, ?, ?, 'trial', ?, 'active', NOW(), ?)
            ");
            $stmt_client->execute([
                $client_code,
                $trial['first_name'],
                $trial['last_name'],
                $trial['email'],
                $trial['company_name'],
                $trial['id'],
                $expires_at
            ]);
            
            $stmt_update = $pdo->prepare("
                UPDATE trial_codes 
                SET status = 'activated', activated_at = NOW()
                WHERE id = ?
            ");
            $stmt_update->execute([$trial['id']]);
            
            $pdo->commit();
            
            // Rediriger vers le formulaire d'enregistrement
            header('Location: admin_register.php?code=' . urlencode($client_code));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = '❌ Erreur lors de l\'activation';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valider Code Essai | CartelPlus Congo</title>
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

        .container-verify {
            width: 100%;
            max-width: 480px;
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

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
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

        .form-group {
            margin-bottom: 25px;
            animation: fadeSlide 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--pp-text);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            background: var(--pp-bg);
            border: 2px solid #e0e4e9;
            border-radius: 8px;
            color: var(--pp-text);
            font-size: 14px;
            font-family: 'Monaco', 'Courier New', monospace;
            letter-spacing: 2px;
            transition: all 0.3s;
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
            text-transform: uppercase;
            transition: all 0.3s;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 112, 224, 0.2);
            margin-top: 10px;
            animation: fadeSlide 0.6s ease-out 0.5s both;
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

        .info-box {
            background: linear-gradient(135deg, rgba(0, 112, 224, 0.08) 0%, rgba(0, 168, 255, 0.08) 100%);
            border-left: 4px solid var(--pp-blue);
            padding: 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: var(--pp-text);
            margin-bottom: 20px;
            animation: slideInRight 0.6s ease-out 0.2s both;
        }

        .success-details {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.08) 0%, rgba(40, 167, 69, 0.05) 100%);
            border: 2px solid rgba(40, 167, 69, 0.15);
            padding: 24px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
            animation: fadeSlide 0.6s ease-out 0.2s both;
        }

        .success-details h5 {
            color: var(--pp-success);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .client-code {
            background: var(--pp-bg);
            padding: 14px;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-weight: bold;
            color: var(--pp-blue);
            letter-spacing: 2px;
            margin: 15px 0;
            user-select: all;
        }

        .btn-redirect {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            margin-top: 15px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 112, 224, 0.2);
        }

        .btn-redirect:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 112, 224, 0.35);
            text-decoration: none;
            color: white;
        }

        .btn-redirect:active {
            transform: translateY(0);
        }

        .steps {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e4e9;
            animation: slideInRight 0.6s ease-out 0.3s both;
        }

        .step {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-start;
        }

        .step-number {
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
            font-size: 13px;
            box-shadow: 0 4px 10px rgba(0, 112, 224, 0.2);
        }

        .step-text {
            font-size: 13px;
            color: var(--pp-text);
            padding-top: 4px;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e4e9;
        }

        .footer-link a {
            color: var(--pp-blue);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s;
        }

        .footer-link a:hover {
            color: var(--pp-cyan);
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container-verify {
                padding: 30px 20px;
            }

            .title {
                font-size: 26px;
            }

            .icon {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>

<div class="container-verify">

    <div class="header">
        <div class="icon">🔑</div>
        <div class="title">Valider Essai</div>
        <div class="subtitle">Entrez votre code pour commencer</div>
    </div>

    <?php if (!$success_message): ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>💡 Vous avez reçu un code d'essai ?</strong><br>
            Entrez-le ci-dessous pour activer votre accès 7 jours complets au système.
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Code d'Essai</label>
                <input type="text" name="trial_code" class="form-control" 
                    value="<?= htmlspecialchars($trial_code) ?>"
                    placeholder="TRIAL-..." required autofocus>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-check-circle"></i> Valider et Activer
            </button>
        </form>

        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-text">Entrez votre code reçu par email</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Votre compte est créé automatiquement</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Accédez au système pendant 7 jours</div>
            </div>
        </div>

    <?php else: ?>

        <div class="alert alert-success"><?= $success_message ?></div>

        <div class="success-details">
            <h5>✅ Essai Activé avec Succès !</h5>
            
            <p style="font-size: 13px; color: var(--pp-text); margin: 12px 0;">
                Votre code client unique:
            </p>
            
            <div class="client-code"><?= htmlspecialchars($client_code) ?></div>

            <p style="font-size: 12px; color: #7d8fa3; margin-bottom: 15px;">
                ⏰ Valide jusqu'au: <strong><?= date('d/m/Y', strtotime('+7 days')) ?></strong>
            </p>

            <a href="../" class="btn-redirect">
                <i class="fas fa-arrow-right"></i> Accéder à l'Application
            </a>

            <button onclick="copyCode('<?= htmlspecialchars($client_code) ?>')" class="btn-redirect" style="background: linear-gradient(135deg, rgba(0, 112, 224, 0.8) 0%, rgba(0, 112, 224, 0.6) 100%); margin-top: 10px;">
                <i class="fas fa-copy"></i> Copier le Code Client
            </button>
        </div>

        <div class="steps">
            <div class="step">
                <div class="step-number">✓</div>
                <div class="step-text">Code d'essai validé</div>
            </div>
            <div class="step">
                <div class="step-number">✓</div>
                <div class="step-text">Compte client créé</div>
            </div>
            <div class="step">
                <div class="step-number">→</div>
                <div class="step-text">Continuez vers l'application</div>
            </div>
        </div>

    <?php endif; ?>

    <div class="footer-link">
        <a href="trial_form"><i class="fas fa-undo"></i> Nouvelle Inscription</a>
        <a href="../"><i class="fas fa-home"></i> Accueil</a>
    </div>

</div>

<script>
function copyCode(code) {
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
