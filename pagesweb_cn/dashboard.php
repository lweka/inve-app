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
    :root {
        --pp-blue: #0070e0;
        --pp-blue-dark: #003087;
        --pp-cyan: #00a8ff;
        --pp-bg: #f5f7fb;
        --pp-text: #0b1f3a;
        --pp-muted: #6b7a90;
        --pp-card: #ffffff;
        --pp-border: #e5e9f2;
        --pp-shadow: 0 12px 30px rgba(0, 48, 135, 0.08);
    }

    body {
        background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                    radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                    var(--pp-bg);
        color: var(--pp-text);
        min-height: 100vh;
        font-family: "Segoe UI", system-ui, sans-serif;
    }

    .dashboard-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px 16px 60px;
    }

    .dashboard-hero {
        background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
        color: #fff;
        border-radius: 20px;
        padding: 28px 28px;
        box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        position: relative;
        overflow: hidden;
        animation: fadeSlide 0.7s ease both;
    }

    .dashboard-hero::after {
        content: "";
        position: absolute;
        inset: -60% -20% auto auto;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 60%);
        animation: pulseGlow 3.2s ease-in-out infinite;
    }

    .dashboard-hero h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .dashboard-hero p {
        margin: 0;
        color: rgba(255,255,255,0.85);
        font-size: 14px;
    }

    .hero-chip {
        background: rgba(255,255,255,0.18);
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin: 26px 0 24px;
    }

    .kpi-card {
        background: var(--pp-card);
        border: 1px solid var(--pp-border);
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: var(--pp-shadow);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        animation: fadeUp 0.6s ease both;
    }

    .kpi-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 36px rgba(0, 48, 135, 0.14);
    }

    .kpi-title {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--pp-muted);
        margin-bottom: 8px;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--pp-blue-dark);
    }

    .kpi-trend {
        font-size: 12px;
        color: #1f8f6a;
        margin-top: 6px;
    }

    .panel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
    }

    .panel {
        background: var(--pp-card);
        border: 1px solid var(--pp-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--pp-shadow);
        animation: fadeUp 0.7s ease both;
    }

    .panel h4 {
        margin: 0 0 10px;
        font-size: 16px;
        color: var(--pp-blue-dark);
        font-weight: 700;
    }

    .panel p {
        margin: 0 0 14px;
        font-size: 13px;
        color: var(--pp-muted);
    }

    .btn-pp {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .btn-pp-primary {
        background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
        color: #fff;
        box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25);
    }

    .btn-pp-secondary {
        background: #fff;
        color: var(--pp-blue-dark);
        border-color: var(--pp-border);
    }

    .btn-pp-accent {
        background: linear-gradient(135deg, var(--pp-cyan), var(--pp-blue));
        color: #fff;
        box-shadow: 0 10px 24px rgba(0, 168, 255, 0.25);
    }

    .btn-pp:hover {
        transform: translateY(-1px);
        opacity: 0.95;
    }

    .insight-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 10px;
    }

    .insight-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f7f9ff;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 13px;
        color: var(--pp-text);
    }

    .insight-badge {
        background: rgba(0,112,224,0.1);
        color: var(--pp-blue-dark);
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 12px;
    }

    @keyframes fadeSlide {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulseGlow {
        0%, 100% { transform: scale(1); opacity: 0.6; }
        50% { transform: scale(1.15); opacity: 1; }
    }

    @media (max-width: 768px) {
        .dashboard-hero {
            padding: 22px;
        }
        .dashboard-hero h1 {
            font-size: 22px;
        }
    }
</style>

<div class="dashboard-wrap">

    <div class="dashboard-hero">
        <div>
            <h1>Tableau de bord — Administration</h1>
            <p>Suivi en temps réel de vos activités, ventes et performance globale.</p>
        </div>
        <div class="hero-chip">Statut : Actif</div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card" style="animation-delay: 0.05s;">
            <div class="kpi-title">Maisons actives</div>
            <div class="kpi-value"><?= $housesCount ?></div>
            <div class="kpi-trend">+0 aujourd’hui</div>
        </div>
        <div class="kpi-card" style="animation-delay: 0.1s;">
            <div class="kpi-title">Vendeurs</div>
            <div class="kpi-value"><?= $agentsCount ?></div>
            <div class="kpi-trend">Equipe en place</div>
        </div>
        <div class="kpi-card" style="animation-delay: 0.15s;">
            <div class="kpi-title">Produits</div>
            <div class="kpi-value"><?= $productsCount ?></div>
            <div class="kpi-trend">Stock sous contrôle</div>
        </div>
        <div class="kpi-card" style="animation-delay: 0.2s;">
            <div class="kpi-title">Profit du jour</div>
            <div class="kpi-value"><?= number_format($todayProfit, 0) ?> CDF</div>
            <div class="kpi-trend">Mise à jour automatique</div>
        </div>
        <div class="kpi-card" style="animation-delay: 0.25s;">
            <div class="kpi-title">Profit global</div>
            <div class="kpi-value"><?= number_format($global, 0) ?> CDF</div>
            <div class="kpi-trend">Cumul des ventes</div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <h4>Actions rapides</h4>
            <p>Accédez rapidement aux modules essentiels pour administrer votre business.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= HOUSES_MANAGE; ?>" class="btn-pp btn-pp-primary">Gérer les maisons</a>
                <a href="<?= MARGE_PAR_MAISON; ?>" class="btn-pp btn-pp-accent">Marge par maison</a>
                <a href="<?= REPORTS_INVENTORY; ?>" class="btn-pp btn-pp-secondary">Rapports / Inventaire</a>
            </div>
        </div>

        <div class="panel">
            <h4>Insights clés</h4>
            <p>Indicateurs rapides pour suivre l’activité et prioriser les actions.</p>
            <ul class="insight-list">
                <li class="insight-item">
                    Ventes aujourd’hui
                    <span class="insight-badge"><?= number_format($todayProfit, 0) ?> CDF</span>
                </li>
                <li class="insight-item">
                    Produits actifs
                    <span class="insight-badge"><?= $productsCount ?></span>
                </li>
                <li class="insight-item">
                    Vendeurs disponibles
                    <span class="insight-badge"><?= $agentsCount ?></span>
                </li>
            </ul>
        </div>

        <div class="panel">
            <h4>Performance globale</h4>
            <p>Vision consolidée des marges et de la rentabilité.</p>
            <div style="height: 10px; background:#e9eef7; border-radius:999px; overflow:hidden;">
                <div style="width: 70%; height: 100%; background: linear-gradient(90deg, var(--pp-cyan), var(--pp-blue)); border-radius:999px; animation: pulseGlow 3s infinite;"></div>
            </div>
            <div class="d-flex justify-content-between mt-2" style="font-size:12px; color: var(--pp-muted);">
                <span>Objectif mensuel</span>
                <span>70%</span>
            </div>
        </div>
    </div>

</div>

</body>
</html>