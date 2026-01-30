<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

$product_id = (int)($_GET['product_id'] ?? 0);
$house_id   = (int)($_GET['house_id'] ?? 0);

if($product_id <= 0 || $house_id <= 0){
    die('Paramètres invalides');
}

/* =========================
   PRODUIT
========================= */
$stmt = $pdo->prepare("
  SELECT p.id, p.name, p.sell_currency
  FROM products p
  JOIN houses h ON h.id = p.house_id
  WHERE p.id = ? AND p.house_id = ? AND h.client_code = ?
");
$stmt->execute([$product_id, $house_id, $client_code]);
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
  WHERE pm.product_id = ? AND pm.house_id = ? AND pm.client_code = ?
  ORDER BY pm.created_at DESC
");
$stmt->execute([$product_id, $house_id, $client_code]);
$rows = $stmt->fetchAll();
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique produit – Cartelplus Congo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root {
    --pp-blue: #0070e0; --pp-blue-dark: #003087; --pp-bg: #f5f7fb;
    --pp-text: #0b1f3a; --pp-card: #ffffff; --pp-border: #e5e9f2;
    --pp-shadow: 0 12px 30px rgba(0, 48, 135, 0.08);
}
body {
    background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                var(--pp-bg);
    color: var(--pp-text); min-height: 100vh;
    font-family: "Segoe UI", system-ui, sans-serif;
}
.page-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 16px 60px; }
.page-hero { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; border-radius: 20px; padding: 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    margin-bottom: 26px; animation: fadeSlide 0.7s ease both; }
.page-hero h3 { font-size: 26px; font-weight: 700; margin: 0; }
.table-container { background: var(--pp-card); border: 1px solid var(--pp-border);
    border-radius: 16px; overflow: hidden; box-shadow: var(--pp-shadow);
    animation: fadeUp 0.7s ease both; }
.table thead th { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; border: none; padding: 14px; font-weight: 600; }
.table tbody td { padding: 12px 14px; border-color: var(--pp-border); }
.table-striped tbody tr:nth-of-type(odd) { background: rgba(0,112,224,0.02); }
.btn-pp { display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: 999px; border: 1px solid transparent;
    font-weight: 600; font-size: 14px; text-decoration: none;
    transition: transform 0.2s ease; cursor: pointer; }
.btn-pp-secondary { background: #fff; color: var(--pp-blue-dark); border-color: var(--pp-border); }
.btn-pp:hover { transform: translateY(-1px); opacity: 0.95; }
@keyframes fadeSlide { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<div class="page-wrap">
  <div class="page-hero">
    <h3><i class="fa-solid fa-box"></i> Historique — <?= htmlspecialchars($product['name']) ?></h3>
  </div>

<div class="table-container">
<table class="table table-sm table-striped mb-0">
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
</div>

<a href="products.php?house_id=<?= $house_id ?>" class="btn-pp btn-pp-secondary mt-3">
  <i class="fa-solid fa-arrow-left"></i> Retour
</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>