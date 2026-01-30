<?php 
    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
    require_once __DIR__ . '/connectDb.php'; // connexion PDO

    $role = $_GET['role'] ?? 'seller';
    $err = $_GET['err'] ?? '';

    $errorMessages = [
        '1' => "Identifiant et mot de passe incorrect.",
        '2' => "Compte inactif. Veuillez contacter le support.",
        '3' => "Accès client inactif. Veuillez vérifier votre abonnement.",
        '4' => "Abonnement expiré. Veuillez renouveler votre accès.",
        '99' => "Erreur technique. Veuillez réessayer plus tard."
    ];
    $errorMessage = $errorMessages[$err] ?? '';
?>

<?php require_once $headerPath; ?>

<style>
    body {
        min-height: 100vh;
        background: linear-gradient(135deg, #050B14, #0B1F3A);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, sans-serif;
    }

    .login-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.35);
        padding: 30px;
        border-top: 6px solid #0B6FB8;
    }

    .login-title {
        text-align: center;
        font-weight: 700;
        color: #0B6FB8;
        margin-bottom: 20px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 20px;
    }

    .login-logo img {
        max-width: 120px;
    }

    label {
        font-weight: 600;
        color: #0B1F3A;
    }

    .btn-primary {
        background-color: #0B6FB8;
        border: none;
        font-weight: 600;
    }

    .btn-primary:hover {
        background-color: #094f86;
    }

    .btn-success {
        background-color: #F45B2A;
        border: none;
        font-weight: 600;
    }

    .btn-success:hover {
        background-color: #d94e23;
    }

    .back-link a {
        color: #0B6FB8;
        font-weight: 600;
        text-decoration: none;
    }

    .back-link a:hover {
        text-decoration: underline;
    }
</style>

<div class="container py-5">
    <div class="card login-card mx-auto" style="max-width:540px;">
        <div class="login-logo">
            <img src="<?=IMG_DIR ?>LogoCartelplusCongo.png" alt="Cartelplus Congo">
        </div>

        <div class="card-body">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreur :</strong> <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if($role === 'admin'): ?>
                <h5 class="login-title">Connexion Administrateur</h5>
                <form id="adminLogin" method="POST" action="/pagesweb_cn/auth.php">
                    <input type="hidden" name="role" value="admin">
                    <div class="mb-3">
                        <label>Nom d'utilisateur</label>
                        <input name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Code admin</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Se connecter</button>
                </form>
            <?php else: ?>
                <h5 class="login-title">Connexion Vendeur</h5>
                <form id="sellerLogin" method="POST" action="<?= AUTHENTIFICATION ?>">
                    <input type="hidden" name="role" value="seller">
                    <div class="mb-3">
                        <label>Numéro vendeur</label>
                        <input name="vendor_number" class="form-control" required>
                    </div>
                    <button class="btn btn-success w-100">Accéder à l’espace vente</button>
                </form>
            <?php endif; ?>

            <div class="mt-4 text-center back-link">
                <a href="<?=URL_ACCUEIL ?>">← Retour à l’accueil gr</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>