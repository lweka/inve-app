<?php
/**
 * ===================================
 * ENREGISTREMENT COMPTE ADMINISTRATEUR
 * ===================================
 * L'utilisateur crée son username + password
 * après validation de son code d'essai/abonnement
 */

require_once __DIR__ . '/connectDb.php';

// reCAPTCHA Enterprise v3
$recaptcha_site_key = '6Ldbz1wsAAAAAD0W6Jx_zDpA-dikUxUj-3oBKSaC';
$google_cloud_project = 'inve-app-486119';

// Chemin vers le fichier de credentials Google Cloud (à configurer)
$credentials_file = __DIR__ . '/../pagesweb_cn/credentials/recaptcha-service-account.json';

$error_message = '';
$success_message = '';
$client_code = $_GET['code'] ?? '';
$redirect_url = '';
$registration_complete = false;

// Vérifier que le client_code existe et est actif
if (!$client_code) {
    header('Location: portal.php?message=missing_code');
    exit;
}

$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, subscription_type, expires_at FROM active_clients WHERE client_code = ? AND status = 'active' AND expires_at > NOW()");
$stmt->execute([$client_code]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header('Location: portal.php?message=invalid_code');
    exit;
}

// Vérifier si le compte existe déjà
$stmt = $pdo->prepare("SELECT id FROM admin_accounts WHERE client_code = ?");
$stmt->execute([$client_code]);
if ($stmt->fetch()) {
    header('Location: connect-parse.php?role=admin&message=account_exists');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $recaptcha_token = $_POST['g-recaptcha-token'] ?? '';

    // Vérifier le token reCAPTCHA
    if (!$recaptcha_token) {
        $error_message = '❌ Vérification de sécurité échouée. Veuillez réessayer.';
    } else {
        // Vérifier le token avec l'API reCAPTCHA Enterprise
        $recaptcha_valid = false;
        $recaptcha_score = 0;

        try {
            // Option 1 : Vérification avec clé API REST simple
            // Pour la vérification complète, il faudrait utiliser Google Cloud SDK
            // Pour maintenant, on fait une validation basique du token
            if (!empty($recaptcha_token) && strlen($recaptcha_token) > 50) {
                // Token reçu et semble valide (vérification minimale)
                $recaptcha_valid = true;
            }
        } catch (Exception $e) {
            $error_message = '❌ Erreur lors de la vérification de sécurité.';
        }

        if (!$recaptcha_valid) {
            $error_message = '❌ Vérification reCAPTCHA échouée. Veuillez réessayer.';
        }
    }

    // Validation
    if (!$error_message && (!$username || !$password || !$password_confirm || !$full_name)) {
        $error_message = '❌ Veuillez remplir tous les champs obligatoires';
    } elseif (!$error_message && strlen($username) < 4) {
        $error_message = '❌ Nom d\'utilisateur minimum 4 caractères';
    } elseif (!$error_message && strlen($password) < 6) {
        $error_message = '❌ Mot de passe minimum 6 caractères';
    } elseif (!$error_message && $password !== $password_confirm) {
        $error_message = '❌ Les mots de passe ne correspondent pas';
    } elseif (!$error_message) {
        try {
            // Vérifier si username existe déjà
            $stmt = $pdo->prepare("SELECT id FROM admin_accounts WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = '❌ Ce nom d\'utilisateur est déjà pris';
            } else {
                // Créer le compte
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO admin_accounts (
                        client_code, username, password_hash, full_name
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $client_code, $username, $password_hash, $full_name
                ]);
                
                // Afficher la page de succès et rediriger
                $registration_complete = true;
                $success_message = '✅ Compte créé avec succès !';
                $redirect_url = 'connect-parse.php?role=admin';
            }
        } catch (PDOException $e) {
            $error_message = '❌ Erreur lors de la création du compte';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer votre compte | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?= htmlspecialchars($recaptcha_site_key) ?>" async defer></script>
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

        .container-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 500px;
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

        .code-box {
            background: linear-gradient(135deg, var(--blue) 0%, #0855a0 100%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            color: white;
            text-align: center;
        }

        .code-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .code-value {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }

        .btn-submit {
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

        .btn-submit:hover {
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

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .success-page {
            text-align: center;
            background: #e8f5e9;
            padding: 40px;
            border-radius: 12px;
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .success-page h2 {
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .success-page p {
            color: #555;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .btn-login {
            display: inline-block;
            padding: 12px 30px;
            background: var(--blue);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #0855a0;
            transform: translateY(-2px);
        }

        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .subscription-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #333;
        }

        .subscription-info strong {
            color: var(--blue);
        }

        .recaptcha-wrap {
            margin-top: 12px;
        }

        .recaptcha-hint {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .recaptcha-loading {
            display: none;
            padding: 8px;
            background: #e3f2fd;
            border-radius: 6px;
            color: #1976d2;
            font-size: 12px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }

        .recaptcha-loading.show {
            display: block;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
    </style>

<body>
    <div class="container-form">
        <?php if (!$registration_complete): ?>

            <div class="header">
                <h1>🔐 Créer votre Compte</h1>
                <p>Configurez votre accès à CartelPlus Congo</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="code-box">
                <div class="code-label">Votre Code Client</div>
                <div class="code-value"><?= htmlspecialchars($client_code) ?></div>
            </div>

            <div class="subscription-info">
                <strong>ℹ️ Informations:</strong><br>
                Email: <?= htmlspecialchars($client['email']) ?><br>
                Type: <?= $client['subscription_type'] === 'trial' ? '📅 Essai 7 jours' : '💳 Abonnement 1 mois' ?><br>
                Expire: <?= date('d/m/Y H:i', strtotime($client['expires_at'])) ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nom Complet *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Votre nom complet" required>
                    <div class="help-text">Votre nom d'administrateur</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur *</label>
                    <input type="text" name="username" class="form-control" placeholder="Minimum 4 caractères" minlength="4" required>
                    <div class="help-text">Utilisé pour la connexion</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 caractères" minlength="6" required>
                    <div class="help-text">Sécurisé et chiffré</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer mot de passe *</label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="Répétez le mot de passe" minlength="6" required>
                </div>

                <!-- reCAPTCHA Enterprise v3 -->
                <input type="hidden" name="g-recaptcha-token" id="recaptchaToken">
                <div class="recaptcha-loading" id="recaptchaLoading">
                    ⚙️ Vérification de sécurité en cours...
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">🔒 Créer mon Compte</button>
            </form>

        <?php else: ?>

            <div class="success-page">
                <div class="success-icon">✅</div>
                <h2><?= $success_message ?></h2>
                <p>Votre compte administrateur a été créé avec succès.</p>
                <p>Redirection vers la connexion...</p>
                <a href="<?= $redirect_url ?? 'connect-parse.php?role=admin' ?>" class="btn-login">Se Connecter →</a>
            </div>

            <script>
                // Redirection automatique après 2 secondes
                setTimeout(function() {
                    window.location.href = '<?= $redirect_url ?? 'connect-parse.php?role=admin' ?>';
                }, 2000);
            </script>

        <?php endif; ?>
    </div>

    <script>
        const siteKey = '<?= htmlspecialchars($recaptcha_site_key) ?>';
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        const recaptchaToken = document.getElementById('recaptchaToken');
        const recaptchaLoading = document.getElementById('recaptchaLoading');

        if (form && submitBtn) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Afficher l'indicateur de chargement
                recaptchaLoading.classList.add('show');
                submitBtn.disabled = true;

                try {
                    // Générer le token reCAPTCHA Enterprise v3
                    await grecaptcha.enterprise.ready(async () => {
                        const token = await grecaptcha.enterprise.execute(siteKey, {
                            action: 'REGISTER'
                        });

                        // Stocker le token dans le champ caché
                        recaptchaToken.value = token;

                        // Soumettre le formulaire
                        form.submit();
                    });
                } catch (error) {
                    console.error('reCAPTCHA error:', error);
                    recaptchaLoading.classList.remove('show');
                    submitBtn.disabled = false;
                    alert('❌ Erreur lors de la vérification de sécurité. Veuillez réessayer.');
                }
            });
        }
    </script>
</body>

</html>
