<?php
/**
 * ===================================
 * PAGE D'ATTENTE - VALIDATION ABONNEMENT
 * ===================================
 * L'utilisateur qui a payé attend que l'admin valide son code
 * Une fois validé, son compte est activé
 */

require_once __DIR__ . '/connectDb.php';

$subscription_code = isset($_GET['code']) ? trim($_GET['code']) : '';
$error_message = '';
$subscription_info = null;
$client_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_input = trim($_POST['subscription_code'] ?? '');
    
    if (!$code_input) {
        $error_message = '❌ Veuillez entrer votre code d\'abonnement';
    } else {
        // Vérifier le code
        $stmt = $pdo->prepare("SELECT * FROM subscription_codes WHERE code = ?");
        $stmt->execute([$code_input]);
        $subscription_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subscription_info) {
            $error_message = '❌ Code d\'abonnement invalide';
        }
    }
} elseif ($subscription_code) {
    // Vérifier via GET
    $stmt = $pdo->prepare("SELECT * FROM subscription_codes WHERE code = ?");
    $stmt->execute([$subscription_code]);
    $subscription_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si trouvé, vérifier si un client existe déjà
if ($subscription_info) {
    // Vérifier si activé
    if ($subscription_info['status'] === 'validated' || $subscription_info['status'] === 'active') {
        // Récupérer le client associé
        $stmt_client = $pdo->prepare("
            SELECT * FROM active_clients 
            WHERE subscription_code_id = ?
        ");
        $stmt_client->execute([$subscription_info['id']]);
        $client_info = $stmt_client->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>État Abonnement | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
            --success: #28a745;
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

        .container-status {
            width: 100%;
            max-width: 500px;
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

        .title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .status-icon {
            font-size: 64px;
            margin: 20px 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            margin: 10px 0;
        }

        .badge-pending {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            color: #FFD700;
        }

        .badge-validated {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
            color: #90EE90;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 8px;
            color: var(--white);
            font-size: 14px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--orange);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--blue);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn-submit:hover {
            opacity: 0.9;
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

        .status-box {
            background: rgba(10, 111, 183, 0.15);
            border: 1px dashed var(--blue);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: rgba(255, 255, 255, 0.6);
        }

        .info-value {
            font-weight: 700;
            color: var(--white);
        }

        .waiting-message {
            background: rgba(255, 193, 7, 0.15);
            border-left: 4px solid rgba(255, 193, 7, 0.5);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .waiting-icon {
            font-size: 48px;
            margin-bottom: 10px;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .waiting-title {
            font-size: 16px;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 8px;
        }

        .waiting-desc {
            font-size: 12px;
            color: rgba(255, 193, 7, 0.8);
            line-height: 1.6;
        }

        .success-box {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.5);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }

        .success-title {
            font-size: 18px;
            font-weight: 700;
            color: #90EE90;
            margin-bottom: 10px;
        }

        .success-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .btn-redirect {
            width: 100%;
            padding: 12px;
            background: var(--success);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            cursor: pointer;
        }

        .btn-redirect:hover {
            opacity: 0.9;
        }

        .contact-info {
            background: rgba(10, 111, 183, 0.1);
            border-left: 4px solid var(--blue);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        .contact-item {
            margin-bottom: 8px;
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
    </style>
</head>

<body>

<div class="container-status">

    <div class="header">
        <div class="title">État de votre Abonnement</div>
    </div>

    <?php if (!$subscription_info): ?>

        <!-- FORMULAIRE ENTRER CODE -->
        <div class="status-icon">🔐</div>

        <div style="text-align: center; margin-bottom: 30px;">
            <p style="font-size: 13px; color: rgba(255, 255, 255, 0.8);">
                Entrez votre code d'abonnement pour vérifier le statut
            </p>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Code d'Abonnement</label>
                <input type="text" name="subscription_code" class="form-control" 
                    placeholder="SUB-..." required autofocus>
            </div>
            <button type="submit" class="btn-submit">Vérifier Statut</button>
        </form>

    <?php elseif ($subscription_info['status'] === 'pending'): ?>

        <!-- STATUT: EN ATTENTE DE VALIDATION -->
        <div class="waiting-message">
            <div class="waiting-icon">⏳</div>
            <div class="waiting-title">En Attente de Validation</div>
            <div class="waiting-desc">
                Veuillez patienter pendant que nous validons votre paiement et votre code d'abonnement.
            </div>
        </div>

        <div class="status-box">
            <div class="info-row">
                <span class="info-label">Montant payé:</span>
                <span class="info-value"><?= number_format($subscription_info['payment_amount'], 0) ?> FC</span>
            </div>
            <div class="info-row">
                <span class="info-label">Code:</span>
                <span class="info-value" style="font-family: 'Courier New'; font-size: 12px;">
                    <?= htmlspecialchars($subscription_info['code']) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Enregistré le:</span>
                <span class="info-value"><?= date('d/m/Y à H:i', strtotime($subscription_info['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="status-badge badge-pending">En Attente</span>
            </div>
        </div>

        <div class="contact-info">
            <div style="font-weight: 700; margin-bottom: 10px; color: var(--blue);">📧 Contactez-Nous</div>
            <div class="contact-item">Email: admin@cartelplus.cd</div>
            <div class="contact-item">WhatsApp: +243 123 456 789</div>
            <div class="contact-item">Téléphone: +243 123 456 789</div>
            <div style="margin-top: 10px; font-size: 11px; border-top: 1px solid rgba(10, 111, 183, 0.3); padding-top: 10px;">
                Mentionnez votre code: <?= htmlspecialchars($subscription_info['code']) ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; font-size: 11px; color: rgba(255, 255, 255, 0.7);">
            Vous avez un code validé ? <a href="subscription_pending.php" style="color: var(--orange);">Cliquez ici →</a>
        </div>

    <?php elseif ($subscription_info['status'] === 'validated' || $subscription_info['status'] === 'active'): ?>

        <!-- STATUT: VALIDÉ - COMPTE ACTIF -->
        <?php if ($client_info): ?>
        <div class="success-box">
            <div class="success-icon">✅</div>
            <div class="success-title">Abonnement Activé !</div>
            <div class="success-desc">
                Votre compte est maintenant actif et prêt à être utilisé.
            </div>

            <div style="background: rgba(10, 111, 183, 0.2); padding: 15px; border-radius: 8px; margin: 15px 0; text-align: left;">
                <div class="info-row" style="border: none; flex-direction: column; align-items: flex-start;">
                    <span class="info-label">Code Client:</span>
                    <span class="info-value" style="font-family: 'Courier New'; font-size: 12px; margin-top: 5px;">
                        <?= htmlspecialchars($client_info['client_code']) ?>
                    </span>
                </div>
                <div class="info-row" style="border: none; margin-top: 10px; flex-direction: column; align-items: flex-start;">
                    <span class="info-label">Valide jusqu'au:</span>
                    <span class="info-value" style="margin-top: 5px;">
                        <?= date('d/m/Y à H:i', strtotime($client_info['expires_at'])) ?>
                    </span>
                </div>
            </div>

            <a href="http://localhost/inve-app/" class="btn-redirect">
                🚀 Accéder à l'Application
            </a>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="footer-link">
        <a href="subscription_buy.php">← Retour</a>
        <a href="http://localhost/inve-app/" style="margin-left: 15px;">Accueil →</a>
    </div>

</div>

</body>
</html>
