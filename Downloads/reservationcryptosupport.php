<?php /* --- Début du bloc Formulaire de Réservation Crypto --- */ ?>

<?php
// --- Logique de traitement du formulaire (à placer AVANT le HTML du formulaire) ---
// Assurez-vous que cette logique ne crée pas de conflit avec d'autres traitements de formulaires sur votre page.

$crypto_offer_form_submitted = false;
$crypto_offer_form_error = false;
$crypto_offer_error_message = ''; // Pour un message d'erreur plus spécifique si besoin

// Vérifie si la requête est POST ET provient potentiellement de CE formulaire
// (On pourrait ajouter un champ caché 'action' pour être plus sûr)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crypto_offer_submit'])) {

    // Récupérer les données (et les nettoyer !)
    $crypto_offer_name = htmlspecialchars(trim($_POST['crypto_offer_name'] ?? ''));
    $crypto_offer_email = filter_var(trim($_POST['crypto_offer_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $crypto_offer_phone = htmlspecialchars(trim($_POST['crypto_offer_phone'] ?? ''));
    $crypto_offer_message = htmlspecialchars(trim($_POST['crypto_offer_message'] ?? ''));

    // Validation (peut être améliorée)
    if (empty($crypto_offer_name)) {
        $crypto_offer_form_error = true;
        $crypto_offer_error_message = 'Le nom est obligatoire.';
    } elseif (!filter_var($crypto_offer_email, FILTER_VALIDATE_EMAIL)) {
        $crypto_offer_form_error = true;
        $crypto_offer_error_message = 'L\'adresse email n\'est pas valide.';
    } else {
        // --- C'EST ICI QU'IL FAUT AJOUTER VOTRE LOGIQUE ---
        // Exemple : Envoyer un email
        /*
        $to = "votre@email.com";
        $subject = "Nouvelle demande de réservation Accompagnement Crypto";
        $body = "Nom: " . $crypto_offer_name . "\n";
        $body .= "Email: " . $crypto_offer_email . "\n";
        $body .= "Téléphone: " . $crypto_offer_phone . "\n";
        $body .= "Message:\n" . $crypto_offer_message;
        $headers = 'From: webmaster@votresite.com' . "\r\n" . // Mettez une adresse d'expéditeur valide
                   'Reply-To: ' . $crypto_offer_email . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        if (mail($to, $subject, $body, $headers)) {
            $crypto_offer_form_submitted = true;
        } else {
            $crypto_offer_form_error = true;
            $crypto_offer_error_message = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
        }
        */
        // Simplement pour l'exemple, on met submitted à true si pas d'erreur de validation
         $crypto_offer_form_submitted = true;
        // --- FIN DE LA ZONE À MODIFIER ---
    }
}
?>

<div class="crypto-offer-wrapper">
    <section class="crypto-offer-reservation-form-section crypto-offer-section-padding">
        <div class="crypto-offer-container"> <?php // Adaptez ou retirez ce container ?>
            <h2 class="crypto-offer-section-title">Réservez Votre Session d'Accompagnement</h2>
            <p class="crypto-offer-text-center crypto-offer-subtitle-form">Remplissez ce formulaire pour que nous puissions vous recontacter et planifier votre première session. Nous vous répondrons dans les plus brefs délais.</p>

            <?php if ($crypto_offer_form_submitted): ?>
                <div class="crypto-offer-form-success">
                    <p><i class="fas fa-check-circle"></i> Merci ! Votre demande a bien été envoyée. Nous vous contacterons prochainement.</p>
                </div>
            <?php elseif ($crypto_offer_form_error): ?>
                 <div class="crypto-offer-form-error">
                    <p><i class="fas fa-exclamation-triangle"></i> Oups ! Une erreur s'est produite. <?php echo htmlspecialchars($crypto_offer_error_message); ?> Veuillez vérifier les informations et réessayez.</p>
                </div>
            <?php endif; ?>

            <?php if (!$crypto_offer_form_submitted): // N'affiche le formulaire que s'il n'a pas été soumis avec succès ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="crypto-offer-contact-form">
                 <?php // Champ caché pour identifier la soumission (optionnel mais recommandé si plusieurs formulaires) ?>
                <input type="hidden" name="crypto_offer_submit" value="1">

                <div class="crypto-offer-form-group">
                    <label for="crypto_offer_name">Nom Complet <span class="crypto-offer-required">*</span></label>
                    <input type="text" id="crypto_offer_name" name="crypto_offer_name" required value="<?php echo isset($_POST['crypto_offer_name']) && $crypto_offer_form_error ? htmlspecialchars($_POST['crypto_offer_name']) : ''; ?>">
                </div>
                <div class="crypto-offer-form-group">
                    <label for="crypto_offer_email">Adresse Email <span class="crypto-offer-required">*</span></label>
                    <input type="email" id="crypto_offer_email" name="crypto_offer_email" required value="<?php echo isset($_POST['crypto_offer_email']) && $crypto_offer_form_error ? htmlspecialchars($_POST['crypto_offer_email']) : ''; ?>">
                </div>
                <div class="crypto-offer-form-group">
                    <label for="crypto_offer_phone">Numéro de Téléphone (Optionnel)</label>
                    <input type="tel" id="crypto_offer_phone" name="crypto_offer_phone" value="<?php echo isset($_POST['crypto_offer_phone']) && $crypto_offer_form_error ? htmlspecialchars($_POST['crypto_offer_phone']) : ''; ?>">
                </div>
                <div class="crypto-offer-form-group">
                    <label for="crypto_offer_message">Votre message ou questions (Optionnel)</label>
                    <textarea id="crypto_offer_message" name="crypto_offer_message" rows="5"><?php echo isset($_POST['crypto_offer_message']) && $crypto_offer_form_error ? htmlspecialchars($_POST['crypto_offer_message']) : ''; ?></textarea>
                </div>
                <div class="crypto-offer-form-group crypto-offer-form-submit">
                     <button type="submit" class="crypto-offer-cta-button crypto-offer-primary-button">Envoyer ma demande</button>
                </div>
                 <p class="crypto-offer-required-notice"><span class="crypto-offer-required">*</span> Champs obligatoires</p>
            </form>
            <?php endif; ?>

        </div>
    </section>
</div> <?php /* --- Fin du bloc Formulaire de Réservation Crypto --- */ ?>