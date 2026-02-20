<?php
/**
 * ===================================
 * SYSTÈME D'ENVOI D'EMAILS
 * ===================================
 * Envoie des emails avec lien d'activation du compte
 * Utilise PHPMailer avec configuration Hostinger
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Envoie un email avec le lien d'activation du compte
 * 
 * @param string $to_email Adresse email du destinataire
 * @param string $to_name Nom complet du destinataire
 * @param string $client_code Code client généré
 * @param string $type Type de compte: 'trial' ou 'subscription'
 * @return bool True si envoyé avec succès, False sinon
 */
function sendActivationEmail($to_email, $to_name, $client_code, $type = 'trial') {
    try {
        $mail = new PHPMailer(true);

        // Configuration SMTP Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.titan.email';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cartelplus-congo@cartelplus.site';
        $mail->Password   = 'Jo@Kin243';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Expéditeur
        $mail->setFrom('cartelplus-congo@cartelplus.site', 'Cartelplus Congo');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo('support@cartelplus.cd', 'Support Cartelplus Congo');

        // Lien d'activation
        $activation_link = 'https://inve-app.cartelplus.site/pagesweb_cn/admin_register.php?code=' . urlencode($client_code);

        // Contenu de l'email
        $mail->isHTML(true);
        
        if ($type === 'trial') {
            $mail->Subject = '🎉 Activez votre essai gratuit Cartelplus Congo - 7 jours';
            $mail->Body    = getTrialEmailTemplate($to_name, $activation_link, $client_code);
        } else {
            $mail->Subject = '✅ Activez votre compte Cartelplus Congo - Abonnement';
            $mail->Body    = getSubscriptionEmailTemplate($to_name, $activation_link, $client_code);
        }
        
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        error_log("Email sent successfully to: $to_email - Type: $type - Code: $client_code");
        return true;
        
    } catch (Exception $e) {
        error_log("Email send failed for: $to_email - Type: $type - Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envoie un email de confirmation d'upgrade Trial → Pro
 */
function sendUpgradeProEmail($to_email, $to_name) {
    try {
        $mail = new PHPMailer(true);

        // Configuration SMTP Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.titan.email';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cartelplus-congo@cartelplus.site';
        $mail->Password   = 'Jo@Kin243';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Expéditeur
        $mail->setFrom('cartelplus-congo@cartelplus.site', 'Cartelplus Congo');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo('support@cartelplus.cd', 'Support Cartelplus Congo');

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = '👑 Félicitations ! Vous êtes passé en Pro - Cartelplus Congo';
        $mail->Body    = getUpgradeProEmailTemplate($to_name);
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        error_log("Upgrade email sent successfully to: $to_email");
        return true;
        
    } catch (Exception $e) {
        error_log("Upgrade email send failed for: $to_email - Error: {$mail->ErrorInfo}");
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
        .btn { display: inline-block; background: linear-gradient(135deg, #0070e0, #003087); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; text-align: center; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
        .steps { margin: 20px 0; }
        .step { display: flex; align-items: flex-start; margin: 15px 0; }
        .step-number { background: #0070e0; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Bienvenue chez Cartelplus Congo !</h1>
            <p>Votre essai gratuit de 7 jours est prêt</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>$name</strong>,</p>
            <p>Félicitations ! Votre demande d'essai gratuit a été approuvée. Vous êtes à quelques clics de découvrir toute la puissance de notre plateforme de gestion POS.</p>
            
            <div class="code-box">
                <div style="font-size: 12px; color: #6b7a90; margin-bottom: 8px;">VOTRE CODE CLIENT</div>
                <div class="code">$code</div>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div><strong>Créez votre compte</strong> - Cliquez sur le bouton ci-dessous</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div><strong>Définissez vos identifiants</strong> - Choisissez votre nom d'utilisateur et mot de passe</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div><strong>Commencez à explorer</strong> - Accédez à toutes les fonctionnalités pendant 7 jours</div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="$link" style="display: inline-block; background: linear-gradient(135deg, #0070e0, #003087); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold;">🔑 Créer mon compte maintenant</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                <strong>⏰ Important :</strong> Votre essai de 7 jours commence dès la création de votre compte. Profitez-en pour tester toutes nos fonctionnalités sans limitation !
            </p>
        </div>
        <div class="footer">
            <p>© 2026 Cartelplus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 998 877 665</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Template HTML pour email d'abonnement payant
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
        .header { background: linear-gradient(135deg, #ff6b35, #e76f00); padding: 40px 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .code-box { background: #fff3e0; border: 2px solid #ff6b35; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-family: monospace; font-size: 20px; font-weight: bold; color: #e76f00; letter-spacing: 2px; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
        .steps { margin: 20px 0; }
        .step { display: flex; align-items: flex-start; margin: 15px 0; }
        .step-number { background: #ff6b35; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Paiement reçu !</h1>
            <p>Votre abonnement Cartelplus Congo est validé</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>$name</strong>,</p>
            <p>Merci pour votre confiance ! Votre paiement de 10 $ a été traité avec succès. Votre compte est maintenant prêt à être activé.</p>
            
            <div class="code-box">
                <div style="font-size: 12px; color: #6b7a90; margin-bottom: 8px;">VOTRE CODE CLIENT</div>
                <div class="code">$code</div>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div><strong>Activez votre compte</strong> - Cliquez sur le bouton ci-dessous</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div><strong>Définissez vos identifiants</strong> - Créez votre nom d'utilisateur et mot de passe</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div><strong>Connectez-vous</strong> - Accédez à votre tableau de bord professionnel</div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="$link" style="display: inline-block; background: linear-gradient(135deg, #ff6b35, #e76f00); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold;">👑 Activer mon compte Pro</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                <strong>📅 Détails :</strong> Votre abonnement est valide pour 30 jours à partir d'aujourd'hui. Vous pouvez le renouveler à tout moment avant l'expiration.
            </p>
        </div>
        <div class="footer">
            <p>© 2026 Cartelplus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 998 877 665</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Template HTML pour email de confirmation upgrade Trial → Pro
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
        .header { background: linear-gradient(135deg, #FFD700, #FFA500); padding: 40px 30px; text-align: center; color: #1a1a1a; }
        .header h1 { margin: 0; font-size: 32px; }
        .header p { margin: 10px 0 0; opacity: 0.8; }
        .content { padding: 40px 30px; }
        .footer { background: #f5f7fb; padding: 20px; text-align: center; font-size: 12px; color: #6b7a90; }
        .benefit { display: flex; align-items: center; margin: 15px 0; padding: 12px; background: #fffaf0; border-radius: 8px; }
        .benefit-icon { font-size: 24px; margin-right: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Bienvenue Pro !</h1>
            <p>Vous êtes maintenant abonné à Cartelplus Congo Pro</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>$name</strong>,</p>
            <p>Excellent ! 🎉 Votre upgrade de l'essai gratuit vers l'abonnement Pro a été validée avec succès. Vous pouvez dès maintenant accéder à toutes les fonctionnalités premium.</p>
            
            <h3 style="color: #003087; margin-top: 25px;">Vos avantages Pro :</h3>
            
            <div class="benefit">
                <span class="benefit-icon">✅</span>
                <div><strong>Accès illimité</strong> - Utilisez tous les outils sans restriction</div>
            </div>
            <div class="benefit">
                <span class="benefit-icon">📊</span>
                <div><strong>Rapports avancés</strong> - Tableaux de bord personnalisés et analytics</div>
            </div>
            <div class="benefit">
                <span class="benefit-icon">👥</span>
                <div><strong>Support prioritaire</strong> - Réponses en moins de 2 heures</div>
            </div>
            <div class="benefit">
                <span class="benefit-icon">🔄</span>
                <div><strong>Mises à jour gratuites</strong> - Accès à toutes les nouvelles fonctionnalités</div>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e9f2; font-size: 13px; color: #6b7a90;">
                <strong>📅 Durée :</strong> Votre abonnement est valide pour 30 jours. Vous recevrez un email avant l'expiration pour le renouveler.
            </p>
        </div>
        <div class="footer">
            <p>© 2026 Cartelplus Congo - Votre partenaire gestion POS</p>
            <p>📧 support@cartelplus.cd | 📱 +243 998 877 665</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Envoi email commercial pour la prospection.
 *
 * @param string $to_email
 * @param string $to_name
 * @param string $subject
 * @param string $htmlBody
 * @param string $altBody
 * @param string|null $errorInfo Message d'erreur SMTP retourne en cas d'echec
 * @return bool
 */
function sendProspectionEmail($to_email, $to_name, $subject, $htmlBody, $altBody = '', &$errorInfo = null) {
    try {
        $mail = new PHPMailer(true);

        // Configuration SMTP Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.titan.email';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cartelplus-congo@cartelplus.site';
        $mail->Password   = 'Jo@Kin243';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Expediteur
        $mail->setFrom('cartelplus-congo@cartelplus.site', 'Cartelplus Congo');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo('support@cartelplus.cd', 'Support Cartelplus Congo');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

        $mail->send();
        error_log("Prospection email sent successfully to: $to_email");
        $errorInfo = null;
        return true;
    } catch (Exception $e) {
        $smtpError = (string)($mail->ErrorInfo ?? '');
        $errorInfo = trim($smtpError !== '' ? $smtpError : $e->getMessage());
        if ($errorInfo === '') {
            $errorInfo = 'Erreur SMTP inconnue';
        }
        error_log("Prospection email send failed for: $to_email - Error: {$errorInfo}");
        return false;
    }
}
?>
