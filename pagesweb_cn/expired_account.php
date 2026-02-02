<?php
session_start();
require_once 'connectDb.php';

// Si pas de client_code en session, rediriger vers login
if (!isset($_SESSION['client_code'])) {
    header('Location: admin_login.php');
    exit;
}

$client_code = $_SESSION['client_code'];

// Récupérer les infos du client
try {
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, subscription_type, expires_at, status
        FROM active_clients
        WHERE client_code = ?
    ");
    $stmt->execute([$client_code]);
    $client = $stmt->fetch();

    if (!$client) {
        header('Location: admin_login.php');
        exit;
    }

    $expires_at = strtotime($client['expires_at']);
    $now = time();
    $days_expired = floor(($now - $expires_at) / 86400);

} catch(PDOException $e) {
    error_log("Error in expired_account.php: " . $e->getMessage());
    die("Erreur système");
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Expiré — Cartel+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --pp-blue: #0070e0;
            --pp-blue-dark: #003087;
            --pp-orange: #ff6b35;
            --pp-cyan: #00a8ff;
            --pp-bg: #f5f7fb;
            --pp-text: #2c3e50;
            --pp-danger: #dc3545;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fb 0%, #e8ecf3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--pp-text);
        }

        .expired-container {
            max-width: 540px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 48, 135, 0.15);
            padding: 40px;
            text-align: center;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .expired-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--pp-blue-dark);
            margin-bottom: 12px;
        }

        .expired-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .info-box {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 4px solid var(--pp-orange);
            border-radius: 12px;
            padding: 18px;
            margin: 24px 0;
            text-align: left;
        }

        .info-box strong {
            display: block;
            color: var(--pp-blue-dark);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-box p {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
        }

        .expired-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            font-size: 14px;
        }

        .expired-details p {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .expired-details strong {
            color: var(--pp-blue-dark);
        }

        .badge-expired {
            background: var(--pp-danger);
            color: white;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .contact-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--pp-orange) 0%, #e76f00 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255, 107, 53, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--pp-blue);
            border: 2px solid var(--pp-blue);
        }

        .btn-secondary:hover {
            background: var(--pp-blue);
            color: white;
            transform: translateY(-2px);
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        }

        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(37, 211, 102, 0.4);
        }

        .contact-info {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
            color: #777;
        }

        .logout-link {
            display: inline-block;
            margin-top: 16px;
            color: var(--pp-blue);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.3s;
        }

        .logout-link:hover {
            color: var(--pp-blue-dark);
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .expired-container {
                padding: 28px 20px;
            }
            h1 {
                font-size: 24px;
            }
            .expired-icon {
                font-size: 56px;
            }
        }
    </style>
</head>
<body>

<div class="expired-container">
    <div class="expired-icon">⏰</div>
    
    <h1>Abonnement Expiré</h1>
    
    <p class="expired-message">
        Votre abonnement a expiré <?= $days_expired > 0 ? "il y a <strong>$days_expired jour" . ($days_expired > 1 ? 's' : '') . "</strong>" : "aujourd'hui" ?>.
        Pour continuer à utiliser tous les services, veuillez renouveler votre compte.
    </p>

    <div class="expired-details">
        <p>
            <span><strong>Nom :</strong> <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?></span>
        </p>
        <p>
            <span><strong>Email :</strong> <?= htmlspecialchars($client['email']) ?></span>
        </p>
        <p>
            <span><strong>Type :</strong> <?= $client['subscription_type'] === 'trial' ? 'Essai 7 jours' : 'Abonnement Mensuel' ?></span>
        </p>
        <p>
            <span><strong>Expiré le :</strong></span>
            <span class="badge-expired"><?= date('d/m/Y', $expires_at) ?></span>
        </p>
    </div>

    <div class="info-box">
        <strong>💡 Comment renouveler ?</strong>
        <p>
            Contactez notre équipe par email ou WhatsApp pour procéder au renouvellement.
            Un administrateur validera votre demande et votre compte sera réactivé immédiatement.
        </p>
    </div>

    <div class="contact-actions">
        <a href="mailto:support@cartelplus.cd?subject=Demande%20de%20renouvellement%20-%20<?= urlencode($client_code) ?>&body=Bonjour,%0A%0AJe%20souhaite%20renouveler%20mon%20abonnement.%0A%0ACode%20client%20:%20<?= urlencode($client_code) ?>%0ANom%20:%20<?= urlencode($client['first_name'] . ' ' . $client['last_name']) ?>%0AEmail%20:%20<?= urlencode($client['email']) ?>" class="btn btn-primary">
            <i class="fas fa-envelope"></i> Envoyer un Email
        </a>
        
        <a href="https://wa.me/243998877665?text=Bonjour,%0AJe%20souhaite%20renouveler%20mon%20abonnement.%0A%0ACode%20:%20<?= urlencode($client_code) ?>%0ANom%20:%20<?= urlencode($client['first_name'] . ' ' . $client['last_name']) ?>" target="_blank" class="btn btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Contacter sur WhatsApp
        </a>

        <a href="subscription_buy.php" class="btn btn-secondary">
            <i class="fas fa-credit-card"></i> Renouveler en ligne
        </a>
    </div>

    <div class="contact-info">
        <p><strong>Besoin d'aide ?</strong></p>
        <p>📧 support@cartelplus.cd</p>
        <p>📱 +243 998 877 665</p>
        <p>🕒 Lun-Ven : 8h00 - 17h00 | Sam : 9h00 - 13h00</p>
    </div>

    <a href="admin_logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> Se déconnecter
    </a>
</div>

</body>
</html>
