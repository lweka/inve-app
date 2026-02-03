<?php
/**
 * ===================================
 * DASHBOARD MARGES - MAISON VS VENDEUR
 * ===================================
 * Pour l'administrateur
 * Affiche : benefices maison, marges vendeurs, stock disponible
 */

require_once __DIR__ . '/connectDb.php';

// Verifier si admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: admin_login.php");
    exit;
}

// Recuperer le client_code de l'admin connecte
$admin_id = $_SESSION['user_id'] ?? null;
$admin_client_code = $_SESSION['client_code'] ?? null;

// Si pas de client_code en session, chercher dans active_clients
if (!$admin_client_code) {
    $stmt = $pdo->prepare("SELECT client_code FROM active_clients WHERE id = ? LIMIT 1");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    $admin_client_code = $admin['client_code'] ?? null;
}

/* ===============================
   RECUPERATION DES PARAMETRES FILTRE
   =============================== */
$filter_house = isset($_GET['house']) ? (int)$_GET['house'] : null;
$filter_agent = isset($_GET['agent']) ? (int)$_GET['agent'] : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

/* ===============================
   REQUETE : MARGES PAR PRODUIT
   =============================== */
$sql = "
SELECT
    p.id,
    p.name,
    p.buy_price,
    p.sell_price,
    SUM(CASE WHEN (pm.type = 'out' OR pm.type = 'sale') THEN pm.qty ELSE 0 END) as qty_sold,
    SUM(CASE WHEN (pm.type = 'out' OR pm.type = 'sale') THEN (pm.qty * (p.sell_price - p.buy_price)) ELSE 0 END) as profit_maison,
    ROUND((SELECT SUM(qty) FROM agent_stock WHERE product_id = p.id), 2) as stock_available,
    COUNT(DISTINCT pm.agent_id) as nb_vendeurs
FROM products p
LEFT JOIN houses h ON h.id = p.house_id
LEFT JOIN product_movements pm ON p.id = pm.product_id AND (pm.type = 'out' OR pm.type = 'sale')
WHERE 1=1
";

$params = [];
if ($admin_client_code) {
    $sql .= " AND h.client_code = ? ";
    $params[] = $admin_client_code;
}
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
   REQUETE : MARGES PAR VENDEUR
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
LEFT JOIN product_movements pm ON a.id = pm.agent_id AND (pm.type = 'out' OR pm.type = 'sale')
WHERE 1=1
";

$params_vendeur = [];
if ($admin_client_code) {
    $sql_vendeur .= " AND h.client_code = ? ";
    $params_vendeur[] = $admin_client_code;
}
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
if ($admin_client_code) {
    $stmt_houses = $pdo->prepare("SELECT id, name FROM houses WHERE client_code = ? ORDER BY name");
    $stmt_houses->execute([$admin_client_code]);
    $houses = $stmt_houses->fetchAll(PDO::FETCH_ASSOC);

    $stmt_agents = $pdo->prepare("SELECT a.id, a.fullname FROM agents a LEFT JOIN houses h ON a.house_id = h.id WHERE h.client_code = ? ORDER BY a.fullname");
    $stmt_agents->execute([$admin_client_code]);
    $agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt_houses = $pdo->query("SELECT id, name FROM houses ORDER BY name");
    $houses = $stmt_houses->fetchAll(PDO::FETCH_ASSOC);

    $stmt_agents = $pdo->query("SELECT id, fullname FROM agents ORDER BY fullname");
    $agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
}
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
            --pp-orange: #d47000;
        }

        body {
            background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                        radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                        var(--pp-bg);
            color: var(--pp-text);
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        .page-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 16px 60px;
        }

        .page-hero {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
            animation: fadeSlide 0.7s ease both;
        }

        .page-hero h2 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        .filter-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--pp-shadow);
            margin-bottom: 26px;
            animation: fadeUp 0.6s ease both;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--pp-blue-dark);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--pp-border);
        }

        .table-container {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: var(--pp-shadow);
            animation: fadeUp 0.7s ease both;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border: none;
            padding: 14px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 14px;
            border-color: var(--pp-border);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(0,112,224,0.02);
        }

        .table-hover tbody tr:hover {
            background: rgba(0,112,224,0.06);
        }

        .badge-marge {
            background: var(--pp-success);
            color: white;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-pp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
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

        .btn-pp:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
<div class="page-wrap">
    <div class="page-hero">
        <h2>Dashboard Marges - Maison vs Vendeur</h2>
        <a href="dashboard.php" class="btn-pp btn-pp-secondary"><- Retour Admin</a>
    </div>
    <!-- FILTRES -->
    <div class="filter-card">
        <h5 style="margin-bottom: 15px;">Filtres</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Maison</label>
                <select name="house" class="form-select form-select-sm">
                    <option value="">Toutes les maisons</option>
                    <?php foreach($houses as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $filter_house == $h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['name']) ?>
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
                <button type="submit" class="btn-pp btn-pp-primary w-100">Filtrer</button>
                <a href="house_marge.php" class="btn-pp btn-pp-secondary" style="text-decoration: none; text-align: center;">Reinitialiser</a>
            </div>
        </form>
    </div>

    <!-- SECTION: MARGES PAR PRODUIT -->
    <div class="section-title">Benefices Maison par Produit</div>
    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-center">PU Achat</th>
                    <th class="text-center">PU Vente</th>
                    <th class="text-center">Marge/Unit</th>
                    <th class="text-center">Quantite Vendue</th>
                    <th class="text-end">Profit Maison</th>
                    <th class="text-center">Stock Dispo</th>
                    <th class="text-center">Nb Vendeurs</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($products_marge)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Aucune donnee</td>
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
                    <td class="text-end fw-bold" style="color: var(--pp-success);">
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
    <div class="section-title">Marges par Vendeur</div>
    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Vendeur</th>
                    <th class="text-center">Maison</th>
                    <th class="text-center">Nb Ventes</th>
                    <th class="text-center">Quantite Vendue</th>
                    <th class="text-end">Montant Total (HT)</th>
                    <th class="text-end">Profit Vendeur</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($vendeurs_marge)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Aucune donnee</td>
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
                    <td class="text-end fw-bold" style="color: var(--pp-orange);">
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
