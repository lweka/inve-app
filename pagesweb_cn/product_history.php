<?php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: connect-parse.php?role=admin");
    exit;
}

$product_id = (int)($_GET['product_id'] ?? 0);
$house_id   = (int)($_GET['house_id'] ?? 0);

if($product_id <= 0 || $house_id <= 0){
    die('Paramètres invalides');
}

/* =========================
   PRODUIT
========================= */
$stmt = $pdo->prepare("
    SELECT id, name, sell_currency
    FROM products
    WHERE id = ? AND house_id = ?
");
$stmt->execute([$product_id, $house_id]);
$product = $stmt->fetch();

if(!$product){
    die('Produit introuvable');
}

/* =========================
   MOUVEMENTS
========================= */
$stmt = $pdo->prepare("
    SELECT
        pm.created_at,
        pm.type,
        pm.qty,
        pm.unit_buy_price,
        pm.unit_sell_price,
        (pm.unit_sell_price - pm.unit_buy_price) * pm.qty AS marge
    FROM product_movements pm
    WHERE pm.product_id = ? AND pm.house_id = ?
    ORDER BY pm.created_at DESC
");
$stmt->execute([$product_id, $house_id]);
$rows = $stmt->fetchAll();
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique produit</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">

<div class="container">
<h4>Historique — <?= htmlspecialchars($product['name']) ?></h4>

<table class="table table-sm table-striped mt-3">
<thead>
<tr>
  <th>Date</th>
  <th>Type</th>
  <th>Qté</th>
  <th>Achat</th>
  <th>Vente</th>
  <th>Marge</th>
</tr>
</thead>
<tbody>

<?php if(!$rows): ?>
<tr><td colspan="6">Aucun mouvement</td></tr>
<?php endif; ?>

<?php foreach($rows as $r): ?>
<tr>
  <td><?= $r['created_at'] ?></td>
  <td><?= strtoupper($r['type']) ?></td>
  <td><?= (int)$r['qty'] ?></td>
  <td><?= number_format((float)$r['unit_buy_price'],2).' '.$product['sell_currency'] ?></td>
  <td><?= number_format((float)$r['unit_sell_price'],2).' '.$product['sell_currency'] ?></td>
  <td>
    <?= number_format((float)($r['marge'] ?? 0),2).' '.$product['sell_currency'] ?>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<a href="products.php?house_id=<?= $house_id ?>" class="btn btn-light">← Retour</a>
</div>

</body>
</html>