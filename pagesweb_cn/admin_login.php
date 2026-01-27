<?php
/**
 * ===================================
 * CONNEXION ADMINISTRATEUR
 * ===================================
 * Page de connexion avec username + password
 */

require_once __DIR__ . '/connectDb.php';

session_start();

$error_message = '';

// Si déjà connecté, rediriger vers le dashboard
if (!empty($_SESSION['admin_username'])) {
    header('Location: seller_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error_message = '❌ Veuillez remplir tous les champs';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, client_code, password_hash, full_name, status 
                FROM admin_accounts 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account || !password_verify($password, $account['password_hash'])) {
                $error_message = '❌ Nom d\'utilisateur ou mot de passe incorrect';
            } elseif ($account['status'] !== 'active') {
                $error_message = '❌ Ce compte a été suspendu';
            } else {
                // Vérifier que le client_code est toujours actif
                $stmt = $pdo->prepare("
                    SELECT id, expires_at FROM active_clients 
                    WHERE client_code = ? AND status = 'active'
                ");
                $stmt->execute([$account['client_code']]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$client) {
                    $error_message = '❌ Votre compte client n\'est pas actif';
                } elseif (strtotime($client['expires_at']) < time()) {
                    $error_message = '❌ Votre abonnement a expiré';
                } else {
                    // Connexion réussie
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_id'] = $account['id'];
                    $_SESSION['client_code'] = $account['client_code'];
                    $_SESSION['admin_full_name'] = $account['full_name'];

                    // Mettre à jour last_login
                    $stmt = $pdo->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$account['id']]);

                    // Mettre à jour last_login dans active_clients aussi
                    $stmt = $pdo->prepare("UPDATE active_clients SET last_login = NOW() WHERE client_code = ?");
                    $stmt->execute([$account['client_code']]);

                    header('Location: seller_dashboard.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error_message = '❌ Erreur système';
        }
    }
}

// Récupérer message de query string
$msg = $_GET['message'] ?? '';
$msg_display = '';
if ($msg === 'account_exists') {
    $msg_display = 'ℹ️ Un compte existe déjà pour ce code. Connectez-vous avec vos identifiants.';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | CartelPlus Congo</title>
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
        }

        .container-login {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            color: var(--dark);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--blue);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(10, 111, 183, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--blue) 0%, #0855a0 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(10, 111, 183, 0.3);
        }

        .alert {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #1565c0;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }

        .footer-link a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        .logo {
            font-size: 32px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container-login">
        <div class="logo">🔐</div>

        <div class="header">
            <h1>Connexion</h1>
            <p>CartelPlus Congo - Tableau de Bord</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($msg_display): ?>
            <div class="alert alert-info"><?= $msg_display ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="Entrez votre nom d'utilisateur" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="Entrez votre mot de passe" required>
            </div>

            <button type="submit" class="btn-login">🚀 Se Connecter</button>
        </form>

        <div class="footer-link">
            Pas encore de compte? 
            <a href="portal.php">Créez un compte</a>
        </div>
    </div>
</body>

</html>
