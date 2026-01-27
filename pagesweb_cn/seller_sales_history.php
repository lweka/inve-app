<?php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    header("Location: connect-parse.php?role=seller");
    exit;
}

$house_id = (int)$_SESSION['house_id'];
$agent_id = (int)$_SESSION['user_id'];

/* ===============================
   HISTORIQUE DES VENTES VENDEUR
   =============================== */
$stmt = $pdo->prepare("
SELECT
  pm.id,
  pm.created_at,
  pm.qty,
  pm.unit_sell_price,
  pm.discount,
  pm.payment_method,
  pm.customer_name,
  pm.is_kit,
  pm.kit_id,
  p.name AS product_name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
WHERE pm.house_id = ?
  AND pm.agent_id = ?
  AND pm.type = 'sale'
ORDER BY
  COALESCE(pm.kit_id, pm.id) DESC,
  pm.is_kit DESC
");
$stmt->execute([$house_id, $agent_id]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
$stmt = $pdo->prepare("
SELECT
    pm.created_at,
    p.name,
    pm.qty,
    pm.unit_sell_price,
    p.sell_currency,
    pm.discount,
    pm.payment_method,
    pm.customer_name,
    (pm.qty * pm.unit_sell_price - pm.discount) AS total
FROM product_movements pm
JOIN products p ON p.id = pm.product_id
WHERE pm.house_id = ?
  AND pm.agent_id = ?
  AND pm.type = 'sale'
ORDER BY pm.created_at DESC
");
$stmt->execute([$house_id, $agent_id]);
$sales = $stmt->fetchAll();*/
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique des ventes | Cartelplus Congo</title>

<link href="../css/bootstrap.min.css" rel="stylesheet">

<style>
/* ===============================
   THEME CARTELPLUS CONGO
   =============================== */
:root{
    --blue:#0A6FB7;
    --orange:#F25C2A;
    --dark:#0B0E14;
    --white:#ffffff;
}

/* PAGE */
body{
    min-height:100vh;
    background:
        radial-gradient(circle at top, rgba(242,92,42,0.15), transparent 60%),
        linear-gradient(180deg, #0B0E14, #05070B);
    color:var(--white);
    font-family: "Segoe UI", system-ui, sans-serif;
}

/* HEADER */
.page-title{
    color:var(--white);
    font-weight:700;
}

/* BOUTON */
.btn-cartel{
    border:1px solid var(--orange);
    color:var(--orange);
}
.btn-cartel:hover{
    background:var(--orange);
    color:#000;
}

/* CARD */
.card{
    border-radius:18px;
    border:none;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(12px);
    box-shadow:0 25px 60px rgba(0,0,0,0.6);
}

/* TABLE */
.table{
    color:var(--white);
    margin-bottom:0;
}
.table thead th{
    background:var(--blue);
    color:#fff;
    border:none;
    padding:14px;
}
.table tbody td{
    border-color:rgba(245, 235, 235, 0.08);
}
.table-striped tbody tr:nth-of-type(odd){
    background:rgba(255,255,255,0.03);
}
.table-hover tbody tr:hover{
    background:rgba(242,92,42,0.12);
}

/* EMPTY STATE */
.text-muted{
    color:rgba(255,255,255,0.6)!important;
}

/* CONTAINER */
.container{
    max-width:980px;
}
</style>
</head>

<body class="p-4">

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title mb-0">🧾 Historique des ventes</h4>
        <a href="seller_dashboard.php" class="btn btn-cartel">← Retour POS</a>
    </div>

    <div class="card">
        <div class="card-body p-0">

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th class="text-center">Qté</th>
                        <th class="text-end">PU</th>
                        <th class="text-end">Remise</th>
                        <th>Paiement</th>
                        <th>Client</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!$sales): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Aucune vente enregistrée
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    $currentKit = null;

                    foreach($sales as $s):

                    /* ================= KIT (PARENT) ================= */
                    if($s['is_kit'] && !$s['kit_id']):
                        $currentKit = $s['id'];
                    ?>
                    <tr class="table-warning">
                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                        <td colspan="2"><strong>🧺 KIT PRODUITS</strong></td>
                        <td class="text-end fw-bold">
                            <?= number_format($s['unit_sell_price'],2) ?>
                        </td>
                        <td class="text-end"><?= number_format((float)$s['discount'],2) ?></td>
                        <td><?= htmlspecialchars($s['payment_method']) ?></td>
                        <td><?= htmlspecialchars($s['customer_name']) ?></td>

                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end text-success fw-bold">
                            <?= number_format($s['unit_sell_price'],2) ?>
                        </td>
                        
                    </tr>

                    <?php
                    continue;
                    endif;

                    /* ================= COMPOSANTS DU KIT ================= */
                    if($s['kit_id'] && $s['kit_id'] == $currentKit):
                    ?>
                    <tr class="small text-muted">
                        <td></td>
                        <td style="color: rgba(253, 250, 250, 0.99);">↳ <?= htmlspecialchars($s['product_name']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-center">× <?= (int)$s['qty'] ?></td>
                        
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end">
                            <?= number_format($s['unit_sell_price'],2) ?>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end">
                            <?= number_format($s['unit_sell_price'] * $s['qty'],2) ?>
                        </td>
                    </tr>

                    <?php continue; endif; ?>

                    <?php
                    /* ================= PRODUIT SIMPLE ================= */
                    $currentKit = null;
                    ?>
                    <tr>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['created_at']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['product_name']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-center"><?= (int)$s['qty'] ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end">
                            <?= number_format($s['unit_sell_price'],2) ?>
                        </td>
                         <td style="color: rgba(253, 250, 250, 0.99);" class="text-end"><?= number_format((float)$s['discount'],2) ?></td>
                        
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['payment_method']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['customer_name']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end fw-bold">
                            <?= number_format(($s['unit_sell_price'] * $s['qty']) - (float)$s['discount'],2) ?>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>


                <!-- <tbody>

                <?php if(!$sales): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            Aucune vente enregistrée
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach($sales as $s): ?>
                        
                    <tr>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['created_at']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['name']) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-center"><?= (int)$s['qty'] ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end">
                            <?= number_format((float)$s['unit_sell_price'],2) ?>
                            <?= htmlspecialchars($s['sell_currency']) ?>
                        </td>
                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end"><?= number_format((float)$s['discount'],2) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars(ucfirst($s['payment_method'])) ?></td>
                        <td style="color: rgba(253, 250, 250, 0.99);"><?= htmlspecialchars($s['customer_name'] ?: '—') ?></td>


                        <td style="color: rgba(253, 250, 250, 0.99);" class="text-end fw-bold text-warning">
                            <?= number_format((float)$s['total'],2) ?>
                            <?= htmlspecialchars($s['sell_currency']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody> -->
            </table>

        </div>
    </div>

</div>

</body>
</html>