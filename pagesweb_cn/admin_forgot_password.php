<?php
/**
 * ===================================
 * RÉINITIALISATION MOT DE PASSE ADMIN
 * ===================================
 * Permet à un admin d'oublier et réinitialiser son mot de passe
 */

require_once __DIR__ . '/connectDb.php';

$error = '';
$success = '';
$step = 1; // Étape 1: Demander le code client, Étape 2: Réinitialiser
$reset_token = null;
$admin_id = null;

// ÉTAPE 1 : Vérifier le code client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['token'])) {
    $client_code = trim($_POST['client_code'] ?? '');
    
    if (!$client_code) {
        $error = '❌ Veuillez entrer votre code client';
    } else {
        // Vérifier que le code client existe et a un admin
        $stmt = $pdo->prepare("
            SELECT aa.id, aa.full_name, aa.client_code 
            FROM admin_accounts aa
            WHERE aa.client_code = ? 
            LIMIT 1
        ");
        $stmt->execute([$client_code]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $error = '❌ Code client non trouvé ou pas associé à un administrateur';
        } else {
            // Générer un token unique
            $reset_token = bin2hex(random_bytes(32));
            $admin_id = $admin['id'];
            
            // Sauvegarder le token en cache (1 heure)
            $_SESSION['password_reset_token'] = $reset_token;
            $_SESSION['password_reset_admin_id'] = $admin_id;
            $_SESSION['password_reset_time'] = time();
            
            $success = "✅ Vérification réussie ! Vous pouvez maintenant réinitialiser votre mot de passe.";
            $step = 2;
        }
    }
}

// ÉTAPE 2 : Réinitialiser le mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier le token en session
    if (
        empty($_SESSION['password_reset_token']) ||
        $_SESSION['password_reset_token'] !== $token ||
        empty($_SESSION['password_reset_admin_id'])
    ) {
        $error = '❌ Token invalide ou expiré. Recommencez la procédure.';
        $step = 1;
    } elseif (time() - ($_SESSION['password_reset_time'] ?? 0) > 3600) {
        // Token expiré après 1 heure
        $error = '❌ Le lien a expiré. Recommencez la procédure.';
        unset($_SESSION['password_reset_token']);
        unset($_SESSION['password_reset_admin_id']);
        unset($_SESSION['password_reset_time']);
        $step = 1;
    } elseif (!$new_password || !$confirm_password) {
        $error = '❌ Veuillez remplir tous les champs';
        $step = 2;
    } elseif (strlen($new_password) < 6) {
        $error = '❌ Le mot de passe doit contenir au moins 6 caractères';
        $step = 2;
    } elseif ($new_password !== $confirm_password) {
        $error = '❌ Les mots de passe ne correspondent pas';
        $step = 2;
    } else {
        try {
            $admin_id = $_SESSION['password_reset_admin_id'];
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare("UPDATE admin_accounts SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $admin_id]);
            
            // Nettoyer la session
            unset($_SESSION['password_reset_token']);
            unset($_SESSION['password_reset_admin_id']);
            unset($_SESSION['password_reset_time']);
            
            $success = '✅ Mot de passe réinitialisé avec succès ! Vous pouvez maintenant vous connecter.';
            $step = 3; // Page de succès
        } catch (Exception $e) {
            $error = '❌ Erreur lors de la mise à jour : ' . $e->getMessage();
            $step = 2;
        }
    }
}

// Si revenir à l'étape 1
if (isset($_GET['restart'])) {
    session_unset();
    $step = 1;
}

// Récupérer le token de la session s'il existe
if ($step === 2 && !empty($_SESSION['password_reset_token'])) {
    $reset_token = $_SESSION['password_reset_token'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser mot de passe | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
            --success: #4caf50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            background: linear-gradient(135deg, #0B0E14 0%, #1a1f2e 50%, #0B0E14 100%);
            color: var(--white);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 20px;
            padding: 45px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .reset-icon {
            font-size: 48px;
            margin-bottom: 15px;
            animation: slideDown 0.6s ease;
        }

        .reset-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .reset-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--white);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: var(--white);
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(10, 111, 183, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid;
            animation: slideUp 0.4s ease;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-color: #f44336;
            color: #ff8a80;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4caf50;
            color: #81c784;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--blue) 0%, #0855a0 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(10, 111, 183, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--blue);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: var(--white);
            text-decoration: underline;
        }

        .success-page {
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: popIn 0.6s ease;
        }

        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 15px;
        }

        .success-text {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .btn-login {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--success) 0%, #388e3c 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(76, 175, 80, 0.3);
        }

        .progress-step {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 15px;
            color: var(--blue);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        
        <!-- ÉTAPE 1 : Verification du code client -->
        <?php if ($step === 1): ?>
            <div class="reset-header">
                <div class="reset-icon">🔑</div>
                <div class="reset-title">Mot de passe oublié ?</div>
                <div class="reset-subtitle">Veuillez entrer votre code client pour continuer</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Code Client *</label>
                    <input type="text" name="client_code" class="form-control" 
                           placeholder="ex: CLI-TRIAL-697F99F7CC646" required autofocus>
                    <small style="color: rgba(255,255,255,0.5); display: block; margin-top: 6px;">
                        Vous avez reçu ce code lors de votre inscription
                    </small>
                </div>

                <button type="submit" class="btn-submit">🔓 Vérifier et continuer</button>
            </form>

            <div class="back-link">
                <a href="admin_login_form.php">← Retour à la connexion</a>
            </div>

        <!-- ÉTAPE 2 : Réinitialiser le mot de passe -->
        <?php elseif ($step === 2): ?>
            <div class="reset-header">
                <div class="reset-icon">🔐</div>
                <div class="reset-title">Nouveau mot de passe</div>
                <div class="reset-subtitle">Créez un mot de passe sécurisé</div>
                <div class="progress-step">Étape 2/2</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($reset_token ?? '') ?>">

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe *</label>
                    <input type="password" name="new_password" class="form-control" 
                           placeholder="Minimum 6 caractères" minlength="6" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe *</label>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="Répétez le mot de passe" minlength="6" required>
                </div>

                <button type="submit" class="btn-submit">✅ Réinitialiser le mot de passe</button>
            </form>

            <div class="back-link">
                <a href="?restart=1">← Recommencer avec un autre code</a>
            </div>

        <!-- ÉTAPE 3 : Succès -->
        <?php else: ?>
            <div class="success-page">
                <div class="success-icon">🎉</div>
                <div class="success-title">Mot de passe réinitialisé !</div>
                <div class="success-text">
                    Votre mot de passe a été changé avec succès.<br>
                    Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
                </div>
                <a href="admin_login_form.php" class="btn-login">Se connecter</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
