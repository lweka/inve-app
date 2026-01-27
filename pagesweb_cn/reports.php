<?php
/**
 * ===================================
 * RAPPORT JOURNALIER COMPLET
 * ===================================
 * Pour l'administrateur
 * Affiche : ventes du jour, filtrage dates, export PDF
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
    AND DATE(pm.created_at) >= ?
    AND DATE(pm.created_at) <= ?
";

$params = [$filter_date_from, $filter_date_to];

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
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   STATISTIQUES GLOBALES
   =============================== */
$total_sales = 0;
$total_discount = 0;
$qty_total = 0;
$payment_methods = [];

foreach ($sales as $sale) {
    if (!$sale['is_kit']) {
        $total_sales += ($sale['qty'] * $sale['unit_sell_price']) - (float)$sale['discount'];
        $total_discount += (float)$sale['discount'];
    } else {
        $total_sales += $sale['unit_sell_price'];
    }
    
    $qty_total += $sale['qty'];
    
    $method = $sale['payment_method'] ?? 'inconnu';
    if (!isset($payment_methods[$method])) {
        $payment_methods[$method] = 0;
    }
    if (!$sale['is_kit']) {
        $payment_methods[$method] += ($sale['qty'] * $sale['unit_sell_price']) - (float)$sale['discount'];
    } else {
        $payment_methods[$method] += $sale['unit_sell_price'];
    }
}

/* ===============================
   LISTES POUR FILTRES
   =============================== */
$stmt_houses = $pdo->query("SELECT id, name FROM houses ORDER BY name");
$houses = $stmt_houses->fetchAll(PDO::FETCH_ASSOC);

$stmt_agents = $pdo->query("SELECT id, fullname FROM agents ORDER BY fullname");
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   EXPORT PDF
   =============================== */
if (isset($_GET['export_pdf'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new TCPDF();
    $pdf->SetFont('helvetica', '', 8);
    $pdf->AddPage();
    
    // Récupérer le nom de la maison si filtrée
    $house_name = 'RAPPORT JOURNALIER';
    if ($filter_house) {
        $stmt_house = $pdo->prepare("SELECT name FROM houses WHERE id = ?");
        $stmt_house->execute([$filter_house]);
        $house = $stmt_house->fetch(PDO::FETCH_ASSOC);
        if ($house) {
            $house_name = 'RAPPORT JOURNALIER - ' . strtoupper($house['name']);
        }
    } else {
        $house_name = 'RAPPORT JOURNALIER - TOUTES LES MAISONS';
    }
    
    // En-tête
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $house_name, 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Période: ' . $filter_date_from . ' au ' . $filter_date_to, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Statistiques
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(10, 111, 183);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(40, 6, 'Total Ventes', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Remise Total', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Qté Vendue', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(40, 6, number_format($total_sales, 0) . ' FC', 1, 0, 'R', true);
    $pdf->Cell(40, 6, number_format($total_discount, 0) . ' FC', 1, 0, 'R', true);
    $pdf->Cell(40, 6, $qty_total, 1, 1, 'C', true);
    $pdf->Ln(5);
    
    // Tableau détail
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(10, 111, 183);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(20, 5, 'Date', 1, 0, 'C', true);
    $pdf->Cell(30, 5, 'Produit', 1, 0, 'L', true);
    $pdf->Cell(15, 5, 'Qté', 1, 0, 'C', true);
    $pdf->Cell(20, 5, 'PU', 1, 0, 'R', true);
    $pdf->Cell(15, 5, 'Total', 1, 0, 'R', true);
    $pdf->Cell(25, 5, 'Vendeur', 1, 1, 'L', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $alt = 0;
    foreach ($sales as $s) {
        if ($s['is_kit']) continue;
        
        $row_amount = ($s['qty'] * $s['unit_sell_price']) - (float)$s['discount'];
        
        if ($alt++ % 2 == 0) {
            $pdf->SetFillColor(220, 240, 255); // Bleu clair
        } else {
            $pdf->SetFillColor(245, 250, 255); // Blanc bleuté
        }
        $pdf->SetTextColor(0, 0, 0); // Texte noir
        
        $pdf->Cell(20, 4, substr($s['created_at'], 5, 11), 1, 0, 'C', $alt % 2 == 0);
        $pdf->Cell(30, 4, substr($s['product_name'], 0, 12), 1, 0, 'L', $alt % 2 == 0);
        $pdf->Cell(15, 4, $s['qty'], 1, 0, 'C', $alt % 2 == 0);
        $pdf->Cell(20, 4, number_format($s['unit_sell_price'], 0), 1, 0, 'R', $alt % 2 == 0);
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
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">📈 Rapports Journaliers</h2>
            <a href="dashboard.php" class="btn btn-outline-light">← Retour Admin</a>
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
