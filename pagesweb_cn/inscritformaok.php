<?php 
    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
?>

<?php require_once $headerPath; ?>


<section class="section-padding text-center">
  <div class="container">
    <h2>✅ Votre inscription a été enregistrée avec succès !</h2>
    <img src="<?= IMG_DIR ?>nos-formations/cfrm.png" width="350" class="" alt="">
    <p>Nous vous contacterons bientôt pour finaliser les détails de la formation.</p>
    <a href="<?= URL_ACCUEIL ?>" class="btn btn-primary mt-3">Retour aux formations</a>
  </div>
</section>

<?php require_once $footerPath; ?>
