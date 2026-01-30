<?php
/**
 * ===================================
 * DASHBOARD MARGES - MAISON VS VENDEUR
 * ===================================
 * Pour l'administrateur
 * Affiche : bénéfices maison, marges vendeurs, stock disponible
 */

require_once __DIR__ . '/connectDb.php';

// Vérifier si admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: admin_login.php");
    exit;
}

/* ===============================
   RÉCUPÉRATION DES PARAMÈTRES FILTRE
   =============================== */
$filter_house = isset($_GET['house']) ? (int)$_GET['house'] : null;
$filter_agent = isset($_GET['agent']) ? (int)$_GET['agent'] : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

/* ===============================
   REQUÊTE : MARGES PAR PRODUIT
   =============================== */
$sql = "
SELECT
    p.id,
    p.name,
    p.buy_price,
    p.sell_price,
    SUM(CASE WHEN pm.type = 'sale' THEN pm.qty ELSE 0 END) as qty_sold,
    SUM(CASE WHEN pm.type = 'sale' THEN (pm.qty * (p.sell_price - p.buy_price)) ELSE 0 END) as profit_maison,
    ROUND((SELECT SUM(qty) FROM agent_stock WHERE product_id = p.id), 2) as stock_available,
    COUNT(DISTINCT pm.agent_id) as nb_vendeurs
FROM products p
LEFT JOIN product_movements pm ON p.id = pm.product_id AND pm.type = 'sale'
WHERE 1=1
";
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
            --pp-success: #1f8f6a;
        }

        body {
            background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                        radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                        var(--pp-bg);
            color: var(--pp-text);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border-radius: 20px;
            padding: 22px 24px;
            margin: 24px 16px 24px;
            box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
            position: relative;
            overflow: hidden;
            animation: fadeSlide .7s ease both;
        }

        .page-header::after {
            content: "";
            position: absolute;
            inset: -60% -20% auto auto;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 60%);
            animation: pulseGlow 3.2s ease-in-out infinite;
        }

        .filter-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            padding: 20px;
            margin: 0 16px 24px;
            box-shadow: var(--pp-shadow);
            animation: fadeUp .6s ease both;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--pp-blue-dark);
            margin: 14px 16px 10px;
        }

        .table-container {
            background: var(--pp-card);
            border-radius: 14px;
            overflow: hidden;
            margin: 0 16px 26px;
            border: 1px solid var(--pp-border);
            box-shadow: var(--pp-shadow);
            animation: fadeUp .7s ease both;
        }

        .table thead th {
            background: #f0f4ff;
            color: var(--pp-blue-dark);
            border: none;
            font-weight: 700;
        }

        .badge-marge {
            background: rgba(0, 112, 224, 0.1);
            color: var(--pp-blue-dark);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-summary {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 14px;
            padding: 16px;
            margin: 0 16px 20px;
            box-shadow: var(--pp-shadow);
            animation: fadeUp .6s ease both;
        }

        .card-summary h6 {
            font-size: 12px;
            color: var(--pp-muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .card-summary strong {
            font-size: 20px;
            color: var(--pp-success);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            transition: transform .2s ease, opacity .2s ease;
        }

        .btn-filter:hover { transform: translateY(-1px); opacity: .95; }

        .btn-reset {
            background: #fff;
            color: var(--pp-blue-dark);
            border: 1px solid var(--pp-border);
            padding: 8px 18px;
            border-radius: 999px;
            cursor: pointer;
            margin-left: 10px;
            transition: transform .2s ease, opacity .2s ease;
        }

        .btn-reset:hover { transform: translateY(-1px); opacity: .9; }

        @keyframes fadeSlide { from{opacity:0;transform:translateY(12px);} to{opacity:1;transform:translateY(0);} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px);} to{opacity:1;transform:translateY(0);} }
        @keyframes pulseGlow { 0%,100%{transform:scale(1);opacity:.6;} 50%{transform:scale(1.12);opacity:1;} }
    </style>
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(10, 111, 183, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(10, 111, 183, 0.1);
            border-left: 4px solid var(--orange);
            padding: 15px;
            border-radius: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--orange);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--blue);
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, 0.02);
        }

        .table-hover tbody tr:hover {
            background: rgba(242, 92, 42, 0.1);
        }

        .badge-marge {
            background: var(--success);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--orange);
        }

        .btn-filter {
            background: var(--blue);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-filter:hover {
            background: var(--orange);
        }

        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="mb-1">Dashboard des marges</h2>
                <div style="font-size:13px; color: rgba(255,255,255,.85);">Analyse par maison et vendeur, avec filtres avancés.</div>
            </div>
            <a href="dashboard.php" class="btn btn-light">Retour tableau de bord</a>
        </div>
    </div>
</div>

<div class="container-fluid p-4">

    <!-- FILTRES -->
    <div class="filter-card">
        <h5 style="margin-bottom: 15px;">🔍 Filtres</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Maison</label>
                <select name="house" class="form-select form-select-sm">
                    <option value="">Toutes les maisons</option>
                    <?php foreach($houses as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $filter_house == $h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['house_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Vendeur</label>
                <select name="agent" class="form-select form-select-sm">
                    <option value="">Tous les vendeurs</option>
                    <?php foreach($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $filter_agent == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['fullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Depuis</label>
                <input type="date" name="date_from" class="form-control form-control-sm" 
                    value="<?= htmlspecialchars($filter_date_from ?? '') ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Jusqu'au</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="<?= htmlspecialchars($filter_date_to ?? '') ?>">
            </div>

            <div class="col-md-2" style="display: flex; align-items: flex-end; gap: 5px;">
                <button type="submit" class="btn-filter w-100">Filtrer</button>
                <a href="house_marge.php" class="btn-reset" style="text-decoration: none; text-align: center;">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- SECTION: MARGES PAR PRODUIT -->
    <div class="section-title">💼 Bénéfices Maison par Produit</div>

    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-center">PU Achat</th>
                    <th class="text-center">PU Vente</th>
                    <th class="text-center">Marge/Unit</th>
                    <th class="text-center">Quantité Vendue</th>
                    <th class="text-end">Profit Maison</th>
                    <th class="text-center">Stock Dispo</th>
                    <th class="text-center">Nb Vendeurs</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($products_marge)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Aucune donnée</td>
                    </tr>
                <?php endif; ?>

                <?php foreach($products_marge as $p): 
                    $marge_unit = $p['sell_price'] - $p['buy_price'];
                    $marge_pct = $p['buy_price'] > 0 ? (($marge_unit / $p['buy_price']) * 100) : 0;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td class="text-center"><?= number_format($p['buy_price'], 0) ?> FC</td>
                    <td class="text-center"><?= number_format($p['sell_price'], 0) ?> FC</td>
                    <td class="text-center">
                        <span class="badge-marge">
                            <?= number_format($marge_unit, 0) ?> FC (<?= number_format($marge_pct, 1) ?>%)
                        </span>
                    </td>
                    <td class="text-center"><?= $p['qty_sold'] ?? 0 ?></td>
                    <td class="text-end fw-bold" style="color: var(--success);">
                        <?= number_format($p['profit_maison'] ?? 0, 0) ?> FC
                    </td>
                    <td class="text-center"><?= number_format($p['stock_available'] ?? 0, 0) ?></td>
                    <td class="text-center"><?= $p['nb_vendeurs'] ?? 0 ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- SECTION: MARGES PAR VENDEUR -->
    <div class="section-title">👥 Marges par Vendeur</div>

    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Vendeur</th>
                    <th class="text-center">Maison</th>
                    <th class="text-center">Nb Ventes</th>
                    <th class="text-center">Quantité Vendue</th>
                    <th class="text-end">Montant Total (HT)</th>
                    <th class="text-end">Profit Vendeur</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($vendeurs_marge)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Aucune donnée</td>
                    </tr>
                <?php endif; ?>

                <?php foreach($vendeurs_marge as $v): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($v['fullname']) ?></strong>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($v['house_name'] ?? 'N/A') ?></td>
                    <td class="text-center"><?= $v['nb_ventes'] ?? 0 ?></td>
                    <td class="text-center"><?= $v['qty_total'] ?? 0 ?></td>
                    <td class="text-end"><?= number_format($v['montant_total'] ?? 0, 0) ?> FC</td>
                    <td class="text-end fw-bold" style="color: var(--orange);">
                        <?= number_format($v['profit_vendeur'] ?? 0, 0) ?> FC
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="../js/bootstrap.min.js"></script>

</body>
</html>
