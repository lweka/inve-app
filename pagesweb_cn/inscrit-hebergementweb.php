<?php 
    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
?>

<?php require_once $headerPath; ?>


<section class="section-padding text-center">
  <div class="container">
    <h2>Votre demande a bien été envoyée.</h2>
    <img src="<?= IMG_DIR ?>nos-formations/succ.png" width="250" class="" alt="">
    <p>Nous vous contacterons bientôt pour finaliser les détails....</p>
    <a href="<?= URL_ACCUEIL ?>" class="btn btn-primary mt-3">Page d'accueil</a>
  </div>
</section>

<?php require_once $footerPath; ?>
