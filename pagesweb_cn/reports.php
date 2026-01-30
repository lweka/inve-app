<?php
/**
 * ===================================
 * RAPPORT JOURNALIER COMPLET
 * ===================================
 * Pour l'administrateur
 * Affiche : ventes du jour, filtrage dates, export PDF
 */

require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

/* ===============================
   RÉCUPÉRATION DES PARAMÈTRES FILTRE
   =============================== */
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$filter_house = isset($_GET['house']) ? (int)$_GET['house'] : null;
$filter_agent = isset($_GET['agent']) ? (int)$_GET['agent'] : null;

/* ===============================
   REQUÊTE : TOUTES LES VENTES
   =============================== */
$sql = "
SELECT
    pm.id,
    pm.created_at,
    pm.qty,
    pm.unit_sell_price,
    pm.discount,
    pm.payment_method,
    pm.customer_name,
    pm.is_kit,
    pm.receipt_id,
    p.name AS product_name,
    a.fullname AS agent_fullname,
    h.name as house_name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
LEFT JOIN agents a ON a.id = pm.agent_id
LEFT JOIN houses h ON h.id = pm.house_id
WHERE pm.type = 'sale'
    AND pm.client_code = ?
    AND DATE(pm.created_at) >= ?
    AND DATE(pm.created_at) <= ?
";

$params = [$client_code, $filter_date_from, $filter_date_to];

if ($filter_house) {
    $sql .= " AND pm.house_id = ? ";
    $params[] = $filter_house;
}
if ($filter_agent) {
    $sql .= " AND pm.agent_id = ? ";
    $params[] = $filter_agent;
}

$sql .= " ORDER BY pm.receipt_id DESC, pm.is_kit DESC, pm.created_at DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 0 16px 24px;
        }

        .stat-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 14px;
            padding: 15px;
            box-shadow: var(--pp-shadow);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 32px rgba(0,48,135,.12); }

        .stat-label {
            font-size: 12px;
            color: var(--pp-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--pp-blue-dark);
        }

        .table-container {
            background: var(--pp-card);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--pp-border);
            box-shadow: var(--pp-shadow);
            margin: 0 16px 24px;
        }

        .table { margin-bottom: 0; }

        .table thead th {
            background: #f0f4ff;
            color: var(--pp-blue-dark);
            border: none;
            padding: 15px;
            font-weight: 700;
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: #eef1f6;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: #fafbff;
        }

        .btn-export {
            background: linear-gradient(135deg, var(--pp-cyan), var(--pp-blue));
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            transition: transform .2s ease, opacity .2s ease;
        }

        .btn-export:hover { transform: translateY(-1px); opacity: .95; }

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

        .receipt-group {
            border-left: 3px solid var(--pp-blue);
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .receipt-label {
            font-size: 11px;
            color: var(--pp-muted);
            margin-bottom: 5px;
        }

        @keyframes fadeSlide { from{opacity:0;transform:translateY(12px);} to{opacity:1;transform:translateY(0);} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px);} to{opacity:1;transform:translateY(0);} }
        @keyframes pulseGlow { 0%,100%{transform:scale(1);opacity:.6;} 50%{transform:scale(1.12);opacity:1;} }
    </style>
        $pdf->Cell(15, 4, number_format($row_amount, 0), 1, 0, 'R', $alt % 2 == 0);
        $pdf->Cell(25, 4, substr($s['agent_fullname'] ?? '', 0, 10), 1, 1, 'L', $alt % 2 == 0);
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="rapport-' . date('Y-m-d') . '.pdf"');
    echo $pdf->Output('', 'S');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Journaliers | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            font-size: 22px;
            font-weight: bold;
            color: var(--orange);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            overflow: hidden;
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

        .btn-export {
            background: var(--orange);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }

        .btn-export:hover {
            opacity: 0.9;
        }

        .payment-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .payment-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .payment-item-label {
            font-size: 12px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }

        .payment-item-value {
            font-size: 16px;
            font-weight: bold;
            color: var(--orange);
        }

        .receipt-group {
            border-left: 3px solid var(--blue);
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .receipt-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="mb-1">Rapports journaliers</h2>
                <div style="font-size:13px; color: rgba(255,255,255,.85);">Suivi complet des ventes et export PDF.</div>
            </div>
            <a href="dashboard.php" class="btn btn-light">Retour tableau de bord</a>
        </div>
    </div>
</div>

<div class="container-fluid p-4">

    <!-- FILTRES -->
    <div class="filter-card">
        <h5 style="margin-bottom: 15px;">🔍 Filtres & Export</h5>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Depuis</label>
                <input type="date" name="date_from" class="form-control form-control-sm" 
                    value="<?= htmlspecialchars($filter_date_from) ?>" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Jusqu'au</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="<?= htmlspecialchars($filter_date_to) ?>" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Maison</label>
                <select name="house" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <?php foreach($houses as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $filter_house == $h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Vendeur</label>
                <select name="agent" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <?php foreach($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $filter_agent == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['fullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-filter">Filtrer</button>
                <a href="reports.php" class="btn-filter" style="text-decoration: none; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">Réinitialiser</a>
                <button type="submit" name="export_pdf" value="1" class="btn-export">📄 PDF</button>
            </div>
        </form>
    </div>

    <!-- STATISTIQUES -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Ventes</div>
            <div class="stat-value"><?= number_format($total_sales, 0) ?> FC</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Remises Accordées</div>
            <div class="stat-value">-<?= number_format($total_discount, 0) ?> FC</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Quantité Vendue</div>
            <div class="stat-value"><?= number_format($qty_total, 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Nombre Transactions</div>
            <div class="stat-value"><?= count(array_unique(array_column($sales, 'receipt_id'))) ?></div>
        </div>
    </div>

    <!-- RÉPARTITION PAR MODE PAIEMENT -->
    <?php if (!empty($payment_methods)): ?>
    <div style="margin-bottom: 30px;">
        <h5 style="margin-bottom: 15px;">💳 Répartition par Mode de Paiement</h5>
        <div class="payment-breakdown">
            <?php foreach ($payment_methods as $method => $amount): ?>
            <div class="payment-item">
                <div class="payment-item-label"><?= ucfirst(htmlspecialchars($method)) ?></div>
                <div class="payment-item-value"><?= number_format($amount, 0) ?> FC</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABLEAU DÉTAIL VENTES -->
    <div>
        <h5 style="margin-bottom: 15px;">📋 Détail des Ventes</h5>
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Produit</th>
                        <th class="text-center">Qté</th>
                        <th class="text-end">PU</th>
                        <th class="text-end">Remise</th>
                        <th class="text-end">Total</th>
                        <th>Vendeur</th>
                        <th>Client</th>
                        <th>Paiement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sales)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Aucune vente pour cette période</td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    $current_receipt = null;
                    foreach($sales as $s): 
                        if ($s['is_kit']) continue;
                        
                        $row_amount = ($s['qty'] * $s['unit_sell_price']) - (float)$s['discount'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                        <td><?= htmlspecialchars($s['product_name']) ?></td>
                        <td class="text-center"><?= $s['qty'] ?></td>
                        <td class="text-end"><?= number_format($s['unit_sell_price'], 0) ?> FC</td>
                        <td class="text-end"><?= $s['discount'] > 0 ? '-' . number_format($s['discount'], 0) . ' FC' : '-' ?></td>
                        <td class="text-end fw-bold"><?= number_format($row_amount, 0) ?> FC</td>
                        <td><?= htmlspecialchars($s['agent_fullname'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? 'N/A') ?></td>
                        <td><?= ucfirst(htmlspecialchars($s['payment_method'] ?? '-')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="../js/bootstrap.min.js"></script>

</body>
</html>
