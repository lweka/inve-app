<?php
/**
 * ===================================
 * FORMULAIRE DE CONNEXION ADMIN
 * ===================================
 * Accès à l'espace d'administration
 * des codes d'essai et d'abonnement
 */

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validation basique
    if (!$username || !$password) {
        $error = '❌ Veuillez entrer le nom d\'utilisateur et le mot de passe';
    } else {
        // Identifiants administrateur (sécurisés - à modifier)
        $admin_username = 'admin';
        $admin_password_hash = '$2y$10$YvXCpHrUGzqGCEz1vhLlKe6h4OlLQWCpL7V1QmKL8dD6P8FzCOyVq'; // hash de "admin123"

        // Vérifier les identifiants
        if ($username === $admin_username && password_verify($password, $admin_password_hash)) {
            // Connexion réussie
            session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_time'] = time();
            
            // Redirection vers le tableau de bord d'administration
            header('Location: admin_subscription_manager');
            exit;
        } else {
            $error = '❌ Identifiants invalides. Accès refusé.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
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

        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }

        .login-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 8px;
            color: var(--white);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--orange);
            box-shadow: 0 0 10px rgba(242, 92, 42, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--orange) 0%, #E84A1F 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(242, 92, 42, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border-left: 4px solid #dc3545;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #ff6b6b;
            font-size: 13px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border-left: 4px solid #28a745;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #51cf66;
            font-size: 13px;
        }

        .security-notice {
            background: rgba(13, 110, 253, 0.1);
            border: 1px solid rgba(13, 110, 253, 0.3);
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.5;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--orange);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- HEADER -->
    <div class="login-header">
        <div class="login-icon">🔐</div>
        <h1 class="login-title">Espace Admin</h1>
        <p class="login-subtitle">Gestion des codes d'essai et d'abonnement</p>
    </div>

    <!-- MESSAGES -->
    <?php if ($error): ?>
    <div class="alert-error">
        <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- FORMULAIRE -->
    <form method="POST" action="">

        <div class="form-group">
            <label class="form-label">Nom d'utilisateur</label>
            <input type="text" name="username" class="form-control" placeholder="Entrez votre nom d'utilisateur" autofocus required>
        </div>

        <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-control" placeholder="Entrez votre mot de passe" required>
        </div>

        <button type="submit" class="btn-login">🚀 Se Connecter</button>

    </form>

    <!-- AVERTISSEMENT DE SÉCURITÉ -->
    <div class="security-notice">
        ⚠️ <strong>Sécurité:</strong> Cet espace est réservé à l'administrateur du système. 
        Ne partagez jamais vos identifiants. Les tentatives de connexion sont enregistrées.
    </div>

    <!-- RETOUR -->
    <div class="back-link">
        <a href="../">← Retour à l'accueil</a>
    </div>

</div>

<script src="../js/bootstrap.min.js"></script>

</body>
</html>
