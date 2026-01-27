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

        .container-verify {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 20px;
            padding: 50px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group {
            margin-bottom: 25px;
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
            padding: 14px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 8px;
            color: var(--white);
            font-size: 14px;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--orange);
            background: rgba(255, 255, 255, 0.12);
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
            text-transform: uppercase;
            transition: all 0.3s;
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

        .info-box {
            background: rgba(10, 111, 183, 0.1);
            border-left: 4px solid var(--blue);
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
        }

        .success-details {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }

        .client-code {
            background: rgba(10, 111, 183, 0.2);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: var(--orange);
            letter-spacing: 1px;
            margin: 15px 0;
        }

        .btn-redirect {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: var(--blue);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            margin-top: 15px;
            cursor: pointer;
        }

        .btn-redirect:hover {
            opacity: 0.9;
        }

        .steps {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .step {
            display: flex;
            margin-bottom: 12px;
        }

        .step-number {
            background: var(--orange);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
            font-size: 12px;
        }

        .step-text {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
        }

        .footer-link a {
            color: var(--orange);
            text-decoration: none;
            font-size: 12px;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="container-verify">

    <div class="header">
        <div class="icon">🔑</div>
        <div class="title">Activer Essai</div>
        <div class="subtitle">Entrez votre code pour commencer</div>
    </div>

    <?php if (!$success_message): ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?= $error_message ?>
        </div>
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
                ✅ Valider et Activer
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
            <h5 style="color: #90EE90; margin-bottom: 10px;">✅ Essai Activé !</h5>
            
            <p style="font-size: 12px; color: rgba(255, 255, 255, 0.8); margin-bottom: 10px;">
                Votre code client unique:
            </p>
            
            <div class="client-code"><?= htmlspecialchars($client_code) ?></div>

            <p style="font-size: 11px; color: rgba(255, 255, 255, 0.6); margin-bottom: 15px;">
                Valid jusqu'au: <strong><?= date('d/m/Y', strtotime('+7 days')) ?></strong>
            </p>

            <a href="../" class="btn-redirect">
                🚀 Accéder à l'Application
            </a>

            <button onclick="copyCode('<?= htmlspecialchars($client_code) ?>')" class="btn-redirect" style="background: rgba(10, 111, 183, 0.6); margin-top: 10px;">
                📋 Copier le Code Client
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
        <a href="trial_form">← Retour Inscription</a>
        <a href="../" style="margin-left: 15px;">Accueil →</a>
    </div>

</div>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('✅ Code copié !');
    });
}
</script>

</body>
</html>
