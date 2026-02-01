<?php
/**
 * ===================================
 * RÉINITIALISATION VIA TOKEN
 * ===================================
 * Permet de réinitialiser le mot de passe avec un token valide
 */

require_once __DIR__ . '/connectDb.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$admin_found = false;

// Vérifier le token
if (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT id, full_name FROM admin_accounts 
        WHERE reset_token = ? 
          AND reset_token_expires > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $error = '❌ Token invalide ou expiré. Veuillez recommencer.';
    } else {
        $admin_found = true;
    }
}

// Traiter la réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin_found) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$new_password || !$confirm_password) {
        $error = '❌ Veuillez remplir tous les champs';
    } elseif (strlen($new_password) < 6) {
        $error = '❌ Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($new_password !== $confirm_password) {
        $error = '❌ Les mots de passe ne correspondent pas';
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("
                UPDATE admin_accounts 
                SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL
                WHERE reset_token = ?
            ");
            $stmt->execute([$password_hash, $token]);
            
            $success = '✅ Mot de passe réinitialisé avec succès ! Redirection...';
            $admin_found = false;
        } catch (Exception $e) {
            $error = '❌ Erreur lors de la mise à jour: ' . $e->getMessage();
        }
    }
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
            --dark: #0B0E14;
            --white: #ffffff;
            --success: #4caf50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .container-reset {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 20px;
            padding: 45px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: inline-block;
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .subtitle {
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

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid;
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

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--blue);
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .success-icon {
            font-size: 64px;
            animation: bounce 0.6s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="container-reset">
        
        <?php if (!empty($token) && !$admin_found): ?>
            <!-- ERREUR: Token invalide -->
            <div class="header">
                <div class="icon">❌</div>
                <div class="title">Lien expiré</div>
                <div class="subtitle">Le lien de réinitialisation n'est plus valide</div>
            </div>

            <div class="alert alert-error">
                <?= $error ?>
            </div>

            <p style="color: rgba(255,255,255,0.6); font-size: 14px; text-align: center; margin: 20px 0;">
                Le lien a peut-être expiré (valide 1 heure) ou a déjà été utilisé.
            </p>

            <div class="back-link">
                <a href="admin_forgot_password.php">🔐 Demander un nouveau lien</a>
            </div>

        <?php elseif ($success): ?>
            <!-- SUCCÈS -->
            <div class="header">
                <div class="icon success-icon">✅</div>
                <div class="title" style="color: var(--success);">Mot de passe réinitialisé</div>
            </div>

            <div class="alert alert-success">
                <?= $success ?>
            </div>

            <p style="color: rgba(255,255,255,0.6); font-size: 14px; text-align: center;">
                Redirection vers la connexion dans 3 secondes...
            </p>

            <script>
                setTimeout(() => {
                    window.location.href = 'admin_login_form.php';
                }, 3000);
            </script>

        <?php elseif (!empty($token) && $admin_found): ?>
            <!-- FORMULAIRE: Réinitialiser -->
            <div class="header">
                <div class="icon">🔐</div>
                <div class="title">Nouveau mot de passe</div>
                <div class="subtitle">Créez un mot de passe sécurisé</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
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

                <button type="submit" class="btn-submit">✅ Réinitialiser</button>
            </form>

        <?php else: ?>
            <!-- ERREUR: Pas de token -->
            <div class="header">
                <div class="icon">⚠️</div>
                <div class="title">Lien manquant</div>
                <div class="subtitle">Aucun token de réinitialisation fourni</div>
            </div>

            <p style="color: rgba(255,255,255,0.6); font-size: 14px; text-align: center; margin: 20px 0;">
                Veuillez suivre le lien fourni dans votre email ou demander un nouveau lien.
            </p>

            <div class="back-link">
                <a href="admin_forgot_password.php">🔐 Demander un lien de réinitialisation</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
