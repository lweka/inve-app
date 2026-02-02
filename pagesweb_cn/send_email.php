<?php
/**
 * ===================================
 * SYSTÈME D'ENVOI D'EMAILS
 * ===================================
 * Envoie des emails avec lien d'activation du compte
 * Utilise PHPMailer pour l'envoi
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Envoie un email avec le lien d'activation du compte
 * 
 * @param string $to_email Adresse email du destinataire
 * @param string $to_name Nom complet du destinataire
 * @param string $client_code Code client généré (CLI-TRIAL-xxx ou CLI-SUB-xxx)
 * @param string $type Type de compte: 'trial' ou 'subscription'
 * @return bool True si envoyé avec succès, False sinon
 */
function sendActivationEmail($to_email, $to_name, $client_code, $type = 'trial') {
    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP (à adapter selon votre serveur)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Serveur SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'votre-email@gmail.com'; // Votre adresse Gmail
        $mail->Password   = 'votre-mot-de-passe-application'; // Mot de passe application Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Expéditeur
        $mail->setFrom('noreply@cartelplus.cd', 'CartelPlus Congo');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo('support@cartelplus.cd', 'Support CartelPlus');

        // Lien d'activation
        $activation_link = 'https://inve-app.cartelplus.site/pagesweb_cn/admin_register.php?code=' . urlencode($client_code);

        // Contenu de l'email
        $mail->isHTML(true);
        
        if ($type === 'trial') {
            $mail->Subject = '🎉 Activez votre essai gratuit CartelPlus - 7 jours';
            $mail->Body    = getTrialEmailTemplate($to_name, $activation_link, $client_code);
        } else {
            $mail->Subject = '✅ Activez votre abonnement CartelPlus';
            $mail->Body    = getSubscriptionEmailTemplate($to_name, $activation_link, $client_code);
        }
        
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi email à {$to_email}: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Template HTML pour email d'essai gratuit
 */
function getTrialEmailTemplate($name, $link, $code) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0070e0, #003087); padding: 40px 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .code-box { background: #f5f7fb; border: 2px solid #0070e0; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-family: monospace; font-size: 20px; font-weight: bold; color: #003087; letter-spacing: 2px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #0070e0, #003087); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
        .steps { margin: 20px 0; }
        .step { display: flex; align-items: flex-start; margin: 15px 0; }
        .step-number { background: #0070e0; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Bienvenue chez CartelPlus !</h1>
            <p>Votre essai gratuit de 7 jours est prêt</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Félicitations ! Votre demande d'essai gratuit a été validée. Vous êtes à quelques clics de découvrir toute la puissance de notre plateforme de gestion POS.</p>
            
            <div class="code-box">
                <div style="font-size: 12px; color: #6b7a90; margin-bottom: 8px;">VOTRE CODE CLIENT</div>
                <div class="code">{$code}</div>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div>Cliquez sur le bouton ci-dessous pour créer votre compte</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div>Choisissez votre nom d'utilisateur et mot de passe</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div>Connectez-vous et explorez toutes les fonctionnalités pendant 7 jours</div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{$link}" class="btn">🔑 Créer mon compte maintenant</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                ⏰ <strong>Important :</strong> Votre essai de 7 jours commence dès la création de votre compte. Profitez-en pour tester toutes nos fonctionnalités sans limitation !
            </p>
        </div>
        <div class="footer">
            <p>© 2026 CartelPlus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 123 456 789</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Template HTML pour email d'abonnement
 */
function getSubscriptionEmailTemplate($name, $link, $code) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0070e0, #003087); padding: 40px 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .code-box { background: #f5f7fb; border: 2px solid #0070e0; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-family: monospace; font-size: 20px; font-weight: bold; color: #003087; letter-spacing: 2px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #0070e0, #003087); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
        .steps { margin: 20px 0; }
        .step { display: flex; align-items: flex-start; margin: 15px 0; }
        .step-number { background: #0070e0; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; flex-shrink: 0; }
        .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Abonnement validé !</h1>
            <p>Votre paiement a été confirmé</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Excellente nouvelle ! Votre paiement a été validé par notre équipe. Vous pouvez maintenant créer votre compte et accéder à toutes les fonctionnalités de CartelPlus pendant 30 jours.</p>
            
            <div class="code-box">
                <div style="font-size: 12px; color: #6b7a90; margin-bottom: 8px;">VOTRE CODE CLIENT</div>
                <div class="code">{$code}</div>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div>Cliquez sur le bouton ci-dessous pour créer votre compte</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div>Choisissez votre nom d'utilisateur et mot de passe sécurisé</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div>Connectez-vous et gérez votre business avec CartelPlus</div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{$link}" class="btn">🔑 Créer mon compte Pro</a>
            </div>

            <div class="alert">
                <strong>💎 Compte Pro activé</strong><br>
                Vous bénéficiez de toutes les fonctionnalités premium pendant 30 jours. N'oubliez pas de renouveler votre abonnement avant l'expiration pour continuer à profiter du service.
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                📅 <strong>Expiration :</strong> Votre abonnement expire automatiquement dans 30 jours. Vous recevrez un rappel 12 jours avant l'expiration.
            </p>
        </div>
        <div class="footer">
            <p>© 2026 CartelPlus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 123 456 789</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Envoie un email de confirmation d'upgrade Pro
 */
function sendUpgradeProEmail($to_email, $to_name) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'votre-email@gmail.com';
        $mail->Password   = 'votre-mot-de-passe-application';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('noreply@cartelplus.cd', 'CartelPlus Congo');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo('support@cartelplus.cd', 'Support CartelPlus');

        $mail->isHTML(true);
        $mail->Subject = '👑 Félicitations ! Vous êtes passé Pro';
        $mail->Body    = getUpgradeProEmailTemplate($to_name);
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi email upgrade à {$to_email}: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Template pour email upgrade Pro
 */
function getUpgradeProEmailTemplate($name) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ff6b35, #ff8c42); padding: 40px 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 32px; }
        .header p { margin: 10px 0 0; opacity: 0.9; font-size: 18px; }
        .content { padding: 40px 30px; }
        .pro-badge { background: linear-gradient(135deg, #ff6b35, #ff8c42); color: white; display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 20px 0; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Bienvenue en mode Pro !</h1>
            <p>Votre compte a été upgradé avec succès</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Votre paiement a été confirmé et votre compte est maintenant en <span class="pro-badge">💎 MODE PRO</span></p>
            
            <p>Vous bénéficiez maintenant de :</p>
            <ul style="line-height: 2;">
                <li>✅ Accès complet pendant 30 jours</li>
                <li>✅ Toutes les fonctionnalités premium</li>
                <li>✅ Support prioritaire</li>
                <li>✅ Rapports avancés illimités</li>
            </ul>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                Connectez-vous à votre espace pour voir votre nouveau badge Pro et profiter de toutes les fonctionnalités !
            </p>
        </div>
        <div class="footer">
            <p>© 2026 CartelPlus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 123 456 789</p>
        </div>
    </div>
</body>
</html>
HTML;
}
?>
