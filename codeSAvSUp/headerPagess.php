<!doctype html>
<html lang="en">
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

        <style>
            .hero-section {
            background-image: url('<?= IMG_DIR ?>/Iaimages.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: top;
            position: relative;
            padding-top: 150px;
            padding-bottom: 150px;
            }
        </style>

    </head>
    
    <body id="top">

        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?=URL_ACCUEIL ?>">
                    <img src="<?= IMG_DIR ?>logo12.png" class="img-fluid logo-image">

                    <div class="d-flex flex-column">
                        <strong class="logo-text">CartelPlus Congo</strong>
                    </div>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav align-items-center ms-lg-5">
                        <!-- <li class="nav-item">
                            <a class="nav-link active" href="index.html">Accueil</a>
                        </li> -->
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="about.html">Solution informatique</a>
                        </li> -->

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarLightDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">Solution informatique</a>

                            <ul class="dropdown-menu dropdown-menu-light" aria-labelledby="navbarLightDropdownMenuLink">
                                <li><a class="dropdown-item" href="<?=URL_CONCEPTION_DEV_INFORMATIQUE ?>">Conception et développement informatique</a></li>
                                <li><a class="dropdown-item" href="<?=URL_SERVICE_HEBERGEMENT_WEB ?>">Service d'hébergement web</a></li>
                                <li><a class="dropdown-item" href="<?=URL_MAINTENANCE_INSTALLATION_INFORMATIQUE ?>">Maintenance et installation</a></li>
                                <li><a class="dropdown-item" href="<?=URL_DECRIVEZ_VOS_BESOIN ?>">Decrivez votre besoin en développement ici</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarLightDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">Cryptomonnaies</a>

                            <ul class="dropdown-menu dropdown-menu-light" aria-labelledby="navbarLightDropdownMenuLink">
                                <li><a class="dropdown-item" href="<?=URL_ACCOMPAGNEMENT_BINANCE ?>">Binance Accompagnement</a></li>
                                <li><a class="dropdown-item" href="<?=URL_MINAGE_BTC ?>">Gagner du BTC avec le minage</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarLightDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">Marketing Digital</a>
                            <ul class="dropdown-menu dropdown-menu-light" aria-labelledby="navbarLightDropdownMenuLink">
                                <li><a class="dropdown-item" href="<?=URL_VOTRE_BUSINESS ?>">Développez votre business</a></li>
                                <li><a class="dropdown-item" href="<?=URL_BOOST_VISIBILITE ?>">Boostez votre visibilité en ligne</a></li>
                                <li><a class="dropdown-item" href="<?=URL_FORMATION_MAKETING ?>">Formation marketing digital</a></li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?=URL_BLOG ?>">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link custom-btn btn" href="#">Connexion</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>








