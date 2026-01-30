<?php 
    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
    require_once __DIR__ . '/connectDb.php';
    require_once __DIR__ . '/require_admin_auth.php'; // Vérifie l'authentification et charge $client_code

    // Maintenant on a $client_code qui identifie le client connecté

    // Compter les maisons du client
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM houses WHERE client_code = ?");
    $stmt->execute([$client_code]);
    $housesCount = (int)$stmt->fetchColumn();

    // Compter les vendeurs du client
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE client_code = ?");
    $stmt->execute([$client_code]);
    $agentsCount = (int)$stmt->fetchColumn();

    // Compter les produits du client
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE client_code = ?");
    $stmt->execute([$client_code]);
    $productsCount = (int)$stmt->fetchColumn();

    // Récupérer les marges par produit pour ce client
    $stmt = $pdo->prepare("
        SELECT p.name,
        SUM((pm.unit_sell_price_cdf - pm.unit_buy_price_cdf) * pm.qty) AS marge_cdf
        FROM product_movements pm
        JOIN products p ON p.id = pm.product_id
        WHERE pm.type = 'sale' AND pm.client_code = ?
        GROUP BY pm.product_id
        ORDER BY marge_cdf DESC
    ");
    $stmt->execute([$client_code]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Profit d'aujourd'hui pour ce client
    $stmt = $pdo->prepare("
        SELECT SUM((unit_sell_price_cdf - unit_buy_price_cdf) * qty)
        FROM product_movements
        WHERE type = 'sale' AND DATE(created_at) = CURDATE() AND client_code = ?
    ");
    $stmt->execute([$client_code]);
    $todayProfit = (float)($stmt->fetchColumn() ?? 0);

    // Profit global pour ce client
    $stmt = $pdo->prepare("
        SELECT SUM((unit_sell_price_cdf - unit_buy_price_cdf) * qty)
        FROM product_movements
        WHERE type = 'sale' AND client_code = ?
    ");
    $stmt->execute([$client_code]);
    $global = (float)($stmt->fetchColumn() ?? 0);
?>

<?php require_once $headerPath; ?>

<style>
    body {
        background: linear-gradient(135deg, #061C2F, #000000);
        color: #ffffff;
        min-height: 100vh;
    }

    h3 {
        color: #F45B2A;
        font-weight: 700;
        margin-bottom: 30px;
    }

    .dashboard-card {
        background: #ffffff;
        color: #0B6FB8;
        border-radius: 14px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        transition: transform .3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-6px);
    }

    .dashboard-card strong {
        font-size: 2.2rem;
        color: #F45B2A;
    }

    .btn-dashboard {
        border-radius: 30px;
        padding: 12px 26px;
        font-weight: 600;
        margin-right: 10px;
    }

    .btn-primary-custom {
        background-color: #0B6FB8;
        color: #fff;
        border: none;
    }

    .btn-primary-custom:hover {
        background-color: #095c99;
    }

    .btn-orange {
        background-color: #F45B2A;
        color: #fff;
        border: none;
    }

    .btn-orange:hover {
        background-color: #d94f23;
    }

    .btn-dark-custom {
        background-color: #111;
        color: #fff;
        border: 1px solid #333;
    }

    .btn-dark-custom:hover {
        background-color: #222;
    }
</style>

<div class="container py-5">

    <h3>Tableau de bord – Administration</h3>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                Maisons<br>
                <strong><?= $housesCount ?></strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card">
                Vendeurs<br>
                <strong><?= $agentsCount ?></strong>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card">
                Produits<br>
                <strong><?= $productsCount ?></strong>
            </div>
        </div>
    </div>

    <hr class="my-5" style="border-color:#333;">

    <div class="d-flex flex-wrap gap-3">
        <a href="<?= HOUSES_MANAGE; ?>" class="btn btn-dashboard btn-primary-custom">
            Gérer les maisons
        </a>

        <a href="<?= MARGE_PAR_MAISON; ?>" class="btn btn-dashboard btn-orange">
            Marge par maison
        </a>

        <a href="<?= REPORTS_INVENTORY; ?>" class="btn btn-dashboard btn-dark-custom">
            Rapports / Inventaire (PDF)
        </a>
    </div>

</div>

</body>
</html>