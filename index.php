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
            --blue: #0070ba;
            --blue-dark: #003087;
            --yellow: #ffc439;
            --orange: #f25c2a;
            --gray-bg: #f7fafd;
            --gray-border: #e5e7eb;
            --white: #fff;
            --text: #222;
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
            background: var(--gray-bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* COOKIE CONSENT */
        .cookie-consent {
            position: fixed;
            bottom: 24px;
            right: 24px;
            max-width: 420px;
            width: calc(100% - 48px);
            background: linear-gradient(145deg, #ffffff, #f3f6fb);
            border: 1px solid rgba(0, 112, 186, 0.18);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            padding: 18px 20px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px) scale(0.98);
            pointer-events: none;
            transition: all 0.35s ease;
        }

        .cookie-consent.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .cookie-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .cookie-badge {
            background: rgba(0, 112, 186, 0.12);
            color: var(--blue);
            font-weight: 700;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
        }

        .cookie-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--blue-dark);
        }

        .cookie-text {
            font-size: 13px;
            color: #3b3f46;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .cookie-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cookie-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .cookie-btn.primary {
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            color: #fff;
            box-shadow: 0 10px 20px rgba(0, 112, 186, 0.25);
        }

        .cookie-btn.secondary {
            background: #e9edf5;
            color: #1f2a44;
        }

        .cookie-btn:hover {
            transform: translateY(-1px);
        }

        .cookie-settings {
            margin-top: 12px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e4e9f2;
            display: none;
        }

        .cookie-settings.show {
            display: block;
        }

        .cookie-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: #3b3f46;
            margin-bottom: 6px;
        }

        .cookie-toggle input {
            accent-color: var(--blue);
        }

        @media (max-width: 640px) {
            .cookie-consent {
                left: 16px;
                right: 16px;
                bottom: 16px;
                width: auto;
            }
        }

        /* NAVBAR */
        .navbar-custom {
            background: var(--white);
            border-bottom: 1px solid var(--gray-border);
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .navbar-custom .container-fluid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 20px;
        }
        .navbar-brand {
            font-size: 2rem;
            font-weight: 700;
            color: var(--blue-dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-account {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 22px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            color: var(--white);
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            border: 1px solid transparent;
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(0,112,186,0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }
        .btn-account:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0,112,186,0.28);
            opacity: 0.95;
            color: var(--white);
        }
        .btn-account:focus,
        .btn-account:active {
            color: var(--white);
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
            color: var(--blue-dark);
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
        }
        .navbar-text strong {
            color: var(--orange);
        }

        @media (max-width: 768px) {
            .navbar-custom .container-fluid {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: heroFloat 6s ease-in-out infinite, heroHue 10s linear infinite;
            filter: drop-shadow(0 10px 25px rgba(0, 112, 186, 0.25));
            will-change: transform, filter;
        }
        @keyframes heroFloat {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
            50% { transform: translateY(-10px) rotate(-4deg) scale(1.04); }
        }
        @keyframes heroHue {
            0% { filter: hue-rotate(0deg) drop-shadow(0 10px 25px rgba(0, 112, 186, 0.25)); }
            50% { filter: hue-rotate(35deg) drop-shadow(0 12px 28px rgba(244, 91, 42, 0.35)); }
            100% { filter: hue-rotate(0deg) drop-shadow(0 10px 25px rgba(0, 112, 186, 0.25)); }
        }
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--blue-dark);
        }
        .hero-subtitle {
            font-size: 18px;
            color: var(--blue);
            margin-bottom: 10px;
        }
        .hero-desc {
            font-size: 15px;
            color: #555;
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
            background: var(--white);
            border: 1px solid var(--gray-border);
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: box-shadow 0.3s, border 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .card-option:hover {
            border-color: var(--blue);
            box-shadow: 0 8px 32px rgba(0,112,186,0.10);
        }
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--blue);
        }
        .card-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--blue-dark);
            margin-bottom: 10px;
        }
        .card-desc {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .card-features {
            text-align: left;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #333;
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
            color: var(--yellow);
            font-weight: bold;
        }
        .card-button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(90deg, var(--blue) 0%, var(--blue-dark) 100%);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, color 0.25s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 48, 135, 0.18);
        }
        .card-button::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(255, 255, 255, 0.35), transparent 60%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .card-button:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 14px 30px rgba(0, 48, 135, 0.2);
        }
        .card-button:hover::before {
            opacity: 1;
        }
        .card-button:active {
            transform: scale(0.97);
        }
        .cta-trial {
            background: linear-gradient(90deg, var(--blue) 0%, var(--blue-dark) 100%);
            color: var(--white);
        }
        .cta-trial:hover {
            background: linear-gradient(90deg, #0088e5 0%, #002466 100%);
            color: var(--white);
        }
        .cta-subscribe {
            background: linear-gradient(90deg, var(--orange) 0%, #e74a1f 100%);
            color: var(--white);
            box-shadow: 0 10px 26px rgba(242, 92, 42, 0.25);
        }
        .cta-subscribe:hover {
            background: linear-gradient(90deg, #ff6a3d 0%, #d6451c 100%);
            color: var(--white);
            box-shadow: 0 16px 32px rgba(242, 92, 42, 0.3);
        }
        .card-secondary {
            background: #eaf6fb;
            border-color: var(--blue);
        }
        .card-secondary .card-button {
            background: var(--blue-dark);
            color: var(--white);
        }

        .reveal {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal.delay-1 {
            transition-delay: 0.08s;
        }
        .reveal.delay-2 {
            transition-delay: 0.16s;
        }

        .contact-info {
            text-align: center;
            margin-top: 60px;
            padding: 40px;
            background: rgba(10, 111, 183, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(10, 111, 183, 0.3);
        }
        .contact-info p {
            color: #1f2a44;
        }
        .contact-info .contact-details {
            font-size: 12px;
            color: rgba(31, 42, 68, 0.75);
        }
        .contact-info .contact-details strong {
            color: var(--blue-dark);
        }

        @media (max-width: 768px) {
            .cards-grid {
                gap: 18px;
            }
            .card-option {
                padding: 30px 22px;
            }
            .card-button {
                width: 100%;
                text-align: center;
                padding: 14px 22px;
            }
            .hero-title {
                font-size: 32px;
            }
        }

        /* ALERT */
        .alert-custom {
            background: #fffbe6;
            border-left: 4px solid #ffc439;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            color: #b38f00;
            font-size: 13px;
        }

        /* FOOTER */
        footer {
            background: var(--white);
            border-top: 1px solid var(--gray-border);
            padding: 30px 0;
            text-align: center;
            color: #888;
            font-size: 12px;
        }
        .footer-links {
            margin-bottom: 10px;
        }
        .footer-links a {
            color: var(--blue);
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }
        .footer-links a:hover {
            color: var(--blue-dark);
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
        <div class="navbar-actions">
            <a class="btn-account" href="https://inve-app.cartelplus.site/pagesweb_cn/connect-parse.php?role=seller">Espace vendeur</a>
            <a class="btn-account" href="https://inve-app.cartelplus.site/pagesweb_cn/connect-parse.php?role=admin">Mon compte</a>
        </div>
    </div>
</nav>

<!-- MAIN -->
<main>
    <div class="hero-container">

        <!-- HERO HEADER -->
        <div class="hero-header reveal">
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
            <div class="card-option reveal delay-1">
                <div class="card-icon">🎁</div>
                <div class="card-title">Essai Gratuit</div>
                <p class="card-desc">Testez le système pendant 7 jours</p>
                <div class="card-features">
                    <li>Accès complet au système</li>
                    <li>Zéro frais, zéro engagement</li>
                    <li>Support technique inclus</li>
                    <li>Durée: 7 jours</li>
                </div>
                <a href="pagesweb_cn/trial_form" class="card-button cta-trial">
                    🚀 Commencer Essai
                </a>
            </div>

            <!-- CARTE 2: ACHAT ABONNEMENT -->
            <div class="card-option reveal delay-2">
                <div class="card-icon">💳</div>
                <div class="card-title">Abonnement</div>
                <p class="card-desc">Accès illimité pendant 1 mois</p>
                <div class="card-features">
                    <li>Accès illimité 30 jours</li>
                    <li>Tarif: 10 $ USD</li>
                    <li>Renouvellement flexible</li>
                    <li>Assistance prioritaire</li>
                </div>
                <a href="pagesweb_cn/subscription_buy" class="card-button cta-subscribe">
                    💰 Acheter Abonnement
                </a>
            </div>

        </div>

        <!-- MESSAGE AIDE EMAIL -->
        <div class="reveal" style="text-align: center; margin-top: 40px; padding: 25px; background: rgba(251, 191, 36, 0.1); border-radius: 12px; border: 1px solid rgba(251, 191, 36, 0.3);">
            <p style="color: rgba(16, 14, 14, 0.85); font-size: 15px; margin: 0;">
                📧 <strong>Vous n'avez pas reçu votre email d'activation ?</strong><br>
                <span style="font-size: 13px; color: rgba(16, 14, 14, 0.7);">→ Vérifiez vos spams ou contactez-nous pour obtenir de l'aide</span>
            </p>
        </div>

        <!-- INFO SUPPLÉMENTAIRE -->
        <div class="contact-info reveal">
            <h3 style="margin-bottom: 15px; font-size: 18px;">❓ Questions ?</h3>
            <p style="color: rgba(16, 14, 14, 0.8); margin-bottom: 10px;">Contactez-nous pour plus d'informations</p>
            <p class="contact-details">
                📧 Email: <strong>cartelplus-congo@cartelplus.site</strong><br>
                📱 WhatsApp: <strong>+243 858756470</strong><br>
                📞 Téléphone: <strong>+243 856525518</strong>
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

<!-- COOKIE CONSENT -->
<div id="cookieConsent" class="cookie-consent" role="dialog" aria-live="polite" aria-label="Consentement cookies">
    <div class="cookie-header">
        <span class="cookie-badge">Sécurité & Confidentialité</span>
        <div class="cookie-title">Nous respectons vos données</div>
    </div>
    <div class="cookie-text">
        Nous utilisons des cookies pour sécuriser la session, améliorer l'expérience et analyser l'utilisation.
        Vous pouvez accepter, refuser ou personnaliser vos préférences.
    </div>
    <div class="cookie-actions">
        <button class="cookie-btn primary" id="cookieAccept">Accepter tout</button>
        <button class="cookie-btn secondary" id="cookieDecline">Refuser</button>
        <button class="cookie-btn secondary" id="cookieSettingsToggle">Personnaliser</button>
    </div>
    <div class="cookie-settings" id="cookieSettings">
        <div class="cookie-toggle">
            <span>Cookies essentiels (obligatoires)</span>
            <input type="checkbox" checked disabled>
        </div>
        <div class="cookie-toggle">
            <span>Mesure d'audience</span>
            <input type="checkbox" id="cookieAnalytics" checked>
        </div>
        <div class="cookie-toggle">
            <span>Personnalisation</span>
            <input type="checkbox" id="cookiePersonalization" checked>
        </div>
    </div>
</div>

<script src="js/bootstrap.min.js"></script>

<script>
    const cookieKey = 'cp_cookie_consent_v1';
    const banner = document.getElementById('cookieConsent');
    const acceptBtn = document.getElementById('cookieAccept');
    const declineBtn = document.getElementById('cookieDecline');
    const settingsToggle = document.getElementById('cookieSettingsToggle');
    const settingsPanel = document.getElementById('cookieSettings');

    const existingConsent = localStorage.getItem(cookieKey);

    if (!existingConsent) {
        setTimeout(() => {
            banner.classList.add('show');
        }, 3000);
    }

    function saveConsent(status) {
        const analytics = document.getElementById('cookieAnalytics').checked;
        const personalization = document.getElementById('cookiePersonalization').checked;
        const payload = {
            status,
            analytics,
            personalization,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem(cookieKey, JSON.stringify(payload));
        banner.classList.remove('show');
    }

    acceptBtn.addEventListener('click', () => saveConsent('accepted'));
    declineBtn.addEventListener('click', () => saveConsent('declined'));
    settingsToggle.addEventListener('click', () => {
        settingsPanel.classList.toggle('show');
    });
</script>

<script>
    const revealItems = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    revealItems.forEach((item) => revealObserver.observe(item));

    const ctaButtons = document.querySelectorAll('.card-button');
    ctaButtons.forEach((btn) => {
        btn.addEventListener('mousemove', (event) => {
            const rect = btn.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;
            btn.style.setProperty('--x', `${x}%`);
            btn.style.setProperty('--y', `${y}%`);
        });

        btn.addEventListener('mouseleave', () => {
            btn.style.setProperty('--x', '50%');
            btn.style.setProperty('--y', '50%');
        });
    });

    const heroIcon = document.querySelector('.hero-icon');
    if (heroIcon) {
        const icons = ['💼', '📊', '📈', '💹', '🧾', '🏪'];
        let iconIndex = 0;
        setInterval(() => {
            iconIndex = (iconIndex + 1) % icons.length;
            heroIcon.textContent = icons[iconIndex];
        }, 2200);
    }
</script>

</body>
</html>

