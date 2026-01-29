<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
http_response_code(404);

require_once __DIR__ . '../../configUrlcn.php'; // __DIR__ = dossier racine
require_once __DIR__ . '../../defConstLiens.php'; // __DIR__ = dossier racine
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="">
    <meta name="author" content="">

    <title>Cartelplus Congo</title>

    <!-- CSS FILES -->
    <link rel="icon" href="<?= IMG_DIR ; ?>logo12.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100;300;400;600;700&display=swap" rel="stylesheet">
    <link href="<?= CSS_DIR ?>bootstrap.min.css" rel="stylesheet">
    <link href="<?= CSS_DIR ?>bootstrap-icons.css" rel="stylesheet">
    <link href="<?= CSS_DIR ?>owl.carousel.min.css" rel="stylesheet">
    <link href="<?= CSS_DIR ?>owl.theme.default.min.css" rel="stylesheet">
    <link href="<?= CSS_DIR ?>tooplate-gotto-job.css" rel="stylesheet">
</head>
<body>
  <h1>Oups 😢</h1>
  <p>La page que vous cherchez n’existe pas.</p>
  <p><a href="<?= BASE_URL ?>">Retour à l’accueil</a></p>
</body>
</html>
