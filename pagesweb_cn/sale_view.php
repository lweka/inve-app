<?php
// pagesweb_cn/sale_view.php
require_once __DIR__ . '/connectDb.php';
$sale_id = intval($_GET['sale_id'] ?? 0);
if($sale_id <= 0) { echo "Invalid"; exit; }

$stmt = $pdo->prepare("SELECT s.*, h.name AS house_name, a.fullname AS agent_name FROM sales s JOIN houses h ON h.id=s.house_id LEFT JOIN agents a ON a.id=s.agent_id WHERE s.id = ?");
$stmt->execute([$sale_id]);
$s = $stmt->fetch();
if(!$s){ echo "Introuvable"; exit; }

$stmt = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Vente #<?= $s['id'] ?></title><link href="../css/bootstrap.min.css" rel="stylesheet"></head><body class="p-3">
<div class="container" style="max-width:800px">
  <h4>Vente #<?= $s['id'] ?></h4>
  <div class="small text-muted">Maison: <?= htmlspecialchars($s['house_name']) ?> — Vendeur: <?= htmlspecialchars($s['agent_name']) ?> — <?= $s['created_at'] ?></div>
  <table class="table table-sm mt-3"><thead><tr><th>Produit</th><th>Prix</th><th>Qté</th><th>Sous-total</th></tr></thead><tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= number_format($it['price'],2) ?></td>
        <td><?= intval($it['qty']) ?></td>
        <td><?= number_format($it['subtotal'],2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
  <div><strong>Total:</strong> <?= number_format($s['total_amount'],2) ?> USD</div>
  <div><strong>Remise:</strong> <?= number_format($s['discount'],2) ?> USD</div>
  <div class="mt-3"><a class="btn btn-light" href="seller_sales_history.php">← Retour</a></div>
</div>
</body></html>
