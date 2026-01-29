<?php
/**
 * ===================================
 * PORTAIL D'ENTRÉE - SYSTÈME ABONNEMENT
 * ===================================
 * Point d'entrée principal du système
 * Affiche options essai, achat, ou redirige clients actifs
 */

require_once __DIR__ . '/pagesweb_cn/connectDb.php';
require_once __DIR__ . '/pagesweb_cn/check_client_auth.php';

// Vérifier si client actif
$current_client = check_client_access();

if ($current_client) {
    // Client actif - rediriger vers POS/Dashboard
    header('Location: pagesweb_cn/seller_dashboard.php');
    exit;
}

// Vérifier les messages
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CartelPlus Congo | Gestion Commerciale</title>
    <!-- Suppression de la mauvaise balise <link rel="stylesheet" href="images/LogoCartelplusCongo.png"> -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
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
            flex-direction: column;
        }

        /* NAVBAR */
        .navbar-custom {
            background: rgba(10, 111, 183, 0.1);
            border-bottom: 1px solid rgba(10, 111, 183, 0.3);
            padding: 15px 0;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-logo {
            height: 45px;
            max-width: 200px;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .navbar-logo:hover {
            transform: scale(1.05);
        }

        .navbar-text {
            color: var(--white);
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
        }

        .navbar-text strong {
            color: var(--orange);
        }

        @media (max-width: 768px) {
            .navbar-logo {
                height: 35px;
            }

            .navbar-text {
                font-size: 16px;
            }

            .navbar-brand {
                gap: 8px;
            }
        }

        /* MAIN CONTENT */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .hero-container {
            width: 100%;
            max-width: 1200px;
        }

        .hero-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .hero-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--orange) 0%, var(--white) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }

        .hero-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* CARDS GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .card-option {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .card-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card-option:hover {
            border-color: var(--orange);
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(242, 92, 42, 0.2);
        }

        .card-option:hover::before {
            opacity: 1;
        }

        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }

        .card-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .card-features {
            text-align: left;
            background: rgba(10, 111, 183, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .card-features li {
            list-style: none;
            margin-bottom: 6px;
            padding-left: 20px;
            position: relative;
        }

        .card-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--orange);
            font-weight: bold;
        }

        .card-button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--orange) 0%, #E84A1F 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .card-button:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(242, 92, 42, 0.3);
        }

        .card-button:active {
            transform: scale(0.95);
        }

        .card-secondary {
            background: rgba(10, 111, 183, 0.15);
            border-color: rgba(10, 111, 183, 0.5);
        }

        .card-secondary .card-button {
            background: var(--blue);
        }

        /* ALERT */
        .alert-custom {
            background: rgba(220, 53, 69, 0.15);
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            color: #ff6b6b;
            font-size: 13px;
        }

        /* FOOTER */
        footer {
            background: rgba(10, 111, 183, 0.1);
            border-top: 1px solid rgba(10, 111, 183, 0.3);
            padding: 30px 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }

        .footer-links {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--orange);
            text-decoration: none;
            margin: 0 15px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card-option {
                padding: 30px 20px;
            }

            .card-title {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar-custom">
    <div class="container-fluid">
        <div class="navbar-brand">
            <img src="images/LogoCartelplusCongo.png" alt="CartelPlus Congo" class="navbar-logo">
            <span class="navbar-text"><strong>CartelPlus</strong> Congo</span>
        </div>
    </div>
</nav>

<!-- MAIN -->
<main>
    <div class="hero-container">

        <!-- HERO HEADER -->
        <div class="hero-header">
            <div class="hero-icon">💼</div>
            <h1 class="hero-title">Système de Gestion Commerciale</h1>
            <p class="hero-subtitle">Gérez vos ventes, stocks et profits</p>
            <p class="hero-desc">
                CartelPlus Congo vous offre une solution complète de gestion POS, 
                rapports financiers et analyses de marges en temps réel.
            </p>
        </div>

        <?php if ($message === 'access_expired'): ?>
        <div class="alert-custom">
            ⏰ Votre accès a expiré. Veuillez vous inscrire à un nouvel essai ou acheter un abonnement.
        </div>
        <?php endif; ?>

        <!-- CARDS GRID -->
        <div class="cards-grid">

            <!-- CARTE 1: ESSAI GRATUIT -->
            <div class="card-option">
                <div class="card-icon">🎁</div>
                <div class="card-title">Essai Gratuit</div>
                <p class="card-desc">Testez le système pendant 7 jours</p>
                <div class="card-features">
                    <li>Accès complet au système</li>
                    <li>Zéro frais, zéro engagement</li>
                    <li>Support technique inclus</li>
                    <li>Durée: 7 jours</li>
                </div>
                <a href="pagesweb_cn/trial_form.php" class="card-button">
                    🚀 Commencer Essai
                </a>
            </div>

            <!-- CARTE 2: ACHAT ABONNEMENT -->
            <div class="card-option">
                <div class="card-icon">💳</div>
                <div class="card-title">Abonnement</div>
                <p class="card-desc">Accès illimité pendant 1 mois</p>
                <div class="card-features">
                    <li>Accès illimité 30 jours</li>
                    <li>Tarif: 10 $ USD</li>
                    <li>Renouvellement flexible</li>
                    <li>Assistance prioritaire</li>
                </div>
                <a href="pagesweb_cn/subscription_buy" class="card-button">
                    💰 Acheter Abonnement
                </a>
            </div>

            <!-- CARTE 3: JE M'INSCRIS -->
            <div class="card-option card-secondary">
                <div class="card-icon">✅</div>
                <div class="card-title">J'ai un Code</div>
                <p class="card-desc">Vous avez reçu un code d'accès?</p>
                <div class="card-features">
                    <li>Code d'essai (TRIAL-...)</li>
                    <li>Code d'abonnement (SUB-...)</li>
                    <li>Activation instantanée</li>
                </div>
                <a href="pagesweb_cn/trial_verify.php" class="card-button">
                    🔑 Valider Mon Code
                </a>
            </div>

        </div>

        <!-- INFO SUPPLÉMENTAIRE -->
        <div style="text-align: center; margin-top: 60px; padding: 40px; background: rgba(10, 111, 183, 0.1); border-radius: 15px; border: 1px solid rgba(10, 111, 183, 0.3);">
            <h3 style="margin-bottom: 15px; font-size: 18px;">❓ Questions ?</h3>
            <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 10px;">Contactez-nous pour plus d'informations</p>
            <p style="font-size: 12px; color: rgba(255, 255, 255, 0.6);">
                📧 Email: <strong>admin@cartelplus.cd</strong><br>
                📱 WhatsApp: <strong>+243 123 456 789</strong><br>
                📞 Téléphone: <strong>+243 123 456 789</strong>
            </p>
        </div>

    </div>
</main>

<!-- FOOTER -->
<footer>
    <div class="footer-links">
        <a href="#">Conditions d'Utilisation</a>
        <a href="#">Politique de Confidentialité</a>
        <a href="#">Support</a>
        <a href="pagesweb_cn/admin_login_form">🔐 Admin</a>
    </div>
    <p>&copy; 2026 CartelPlus Congo. Tous droits réservés.</p>
</footer>

<script src="js/bootstrap.min.js"></script>

</body>
</html>

