<?php
    require_once __DIR__ . '/configUrlcn.php';
    require_once __DIR__ . '/defConstLiens.php';
    require_once $dataDbConnect;
?>

<?php require_once $headerPath; ?>

<style>
    /* === FOND GLOBAL === */
    body {
        min-height: 100vh;
        background: radial-gradient(circle at top, #0B6FBF 0%, #020617 60%);
        font-family: "Segoe UI", system-ui, sans-serif;
    }

    /* === CONTAINER PRINCIPAL === */
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    /* === CARTE PRINCIPALE === */
    .auth-card {
        max-width: 720px;
        width: 100%;
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,.45);
        background: #ffffff;
    }

    /* === COLONNE GAUCHE === */
    .auth-left {
        padding: 40px 30px;
        text-align: center;
    }

    .auth-left h3 {
        font-weight: 700;
        color: #0B6FBF;
        margin-bottom: 10px;
    }

    .auth-left p {
        color: #6c757d;
        margin-bottom: 25px;
    }

    /* === BOUTONS === */
    .btn-cartel {
        background: #0B6FBF;
        color: #fff;
        border-radius: 30px;
        padding: 12px;
        font-weight: 600;
        border: none;
        transition: all .3s ease;
    }

    .btn-cartel:hover {
        background: #F25C2A;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(242,92,42,.4);
    }

    .btn-outline-cartel {
        border: 2px solid #0B6FBF;
        color: #0B6FBF;
        border-radius: 30px;
        padding: 12px;
        font-weight: 600;
        transition: all .3s ease;
    }

    .btn-outline-cartel:hover {
        background: #0B6FBF;
        color: #fff;
    }

    /* === COLONNE DROITE === */
    .auth-right {
        background: linear-gradient(160deg, #0B6FBF, #F25C2A);
        color: #fff;
        padding: 40px 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-right h5 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    .auth-right p {
        font-size: 14px;
        opacity: .9;
    }

    /* === LOGO === */
    .auth-logo {
        max-width: 120px;
        margin-bottom: 20px;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="row g-0">

            <!-- COLONNE GAUCHE -->
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="auth-left w-100">
                    <img src="<?=IMG_DIR ?>LogoCartelplusCongo.png" alt="Cartelplus Congo" class="auth-logo">
                    <h3>Connexion</h3>
                    <p>Choisissez votre mode de connexion</p>

                    <a href="<?= PARSE_CONNECT; ?>?role=admin"
                       class="btn btn-cartel mb-3 w-100">
                        Je me connecte comme Admin
                    </a>

                    <a href="<?= PARSE_CONNECT; ?>?role=seller"
                       class="btn btn-outline-cartel w-100">
                        Je me connecte comme vendeur
                    </a>
                </div>
            </div>

            <!-- COLONNE DROITE -->
            <div class="col-md-6 d-none d-md-flex auth-right">
                <h5>Inventeur de produits</h5>
                <p>
                    Gestion multi-maison, contrôle des stocks,
                    point de vente et rapports détaillés.
                </p>
            </div>

        </div>
    </div>
</div>

</body>
</html>

