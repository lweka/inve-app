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

$params = [];
if ($filter_house) {
    $sql .= " AND pm.house_id = ? ";
    $params[] = $filter_house;
}
if ($filter_date_from) {
    $sql .= " AND DATE(pm.created_at) >= ? ";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $sql .= " AND DATE(pm.created_at) <= ? ";
    $params[] = $filter_date_to;
}

$sql .= " GROUP BY p.id ORDER BY profit_maison DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products_marge = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   REQUÊTE : MARGES PAR VENDEUR
   =============================== */
$sql_vendeur = "
SELECT
    a.id,
    a.fullname,
    h.name as house_name,
    COUNT(DISTINCT pm.id) as nb_ventes,
    SUM(pm.qty) as qty_total,
    SUM(pm.qty * pm.unit_sell_price - pm.discount) as montant_total,
    SUM(pm.qty * (
        SELECT (p.sell_price - p.buy_price) 
        FROM products p 
        WHERE p.id = pm.product_id
    )) as profit_vendeur
FROM agents a
LEFT JOIN houses h ON a.house_id = h.id
LEFT JOIN product_movements pm ON a.id = pm.agent_id AND pm.type = 'sale'
WHERE 1=1
";

$params_vendeur = [];
if ($filter_house) {
    $sql_vendeur .= " AND a.house_id = ? ";
    $params_vendeur[] = $filter_house;
}
if ($filter_agent) {
    $sql_vendeur .= " AND a.id = ? ";
    $params_vendeur[] = $filter_agent;
}
if ($filter_date_from) {
    $sql_vendeur .= " AND DATE(pm.created_at) >= ? ";
    $params_vendeur[] = $filter_date_from;
}
if ($filter_date_to) {
    $sql_vendeur .= " AND DATE(pm.created_at) <= ? ";
    $params_vendeur[] = $filter_date_to;
}

$sql_vendeur .= " GROUP BY a.id ORDER BY profit_vendeur DESC ";

$stmt_vendeur = $pdo->prepare($sql_vendeur);
$stmt_vendeur->execute($params_vendeur);
$vendeurs_marge = $stmt_vendeur->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   LISTES POUR FILTRES
   =============================== */
$stmt_houses = $pdo->query("SELECT id, name FROM houses ORDER BY name");
$houses = $stmt_houses->fetchAll(PDO::FETCH_ASSOC);

$stmt_agents = $pdo->query("SELECT id, fullname FROM agents ORDER BY fullname");
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Marges | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
            --success: #28a745;
        }

        body {
            background: linear-gradient(180deg, #0B0E14, #05070B);
            color: var(--white);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: rgba(10, 111, 183, 0.1);
            border-bottom: 2px solid var(--blue);
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .filter-card {
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
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">📊 Dashboard Marges - Maison vs Vendeur</h2>
            <a href="dashboard.php" class="btn btn-outline-light">← Retour Admin</a>
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
