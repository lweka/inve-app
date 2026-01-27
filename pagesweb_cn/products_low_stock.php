<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: connect-parse.php?role=admin");
    exit;
}

// récupération des maisons
$houses = $pdo->query("SELECT * FROM houses ORDER BY id DESC")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $rate = floatval($_POST['usd_rate']);
    if($rate>0){
        $stmt = $pdo->prepare("
        INSERT INTO exchange_rate (id, usd_rate)
        VALUES (1,?)
        ON DUPLICATE KEY UPDATE usd_rate=VALUES(usd_rate)
        ");
        $stmt->execute([$rate]);
        header("Location: exchange_rate_manage.php?ok=1");
        exit;
    }
}

/* =========================
   PRODUITS EN ALERTE
========================= */
$stmt = $pdo->prepare("
SELECT 
    p.id AS product_id,
    p.name AS product_name,
    h.id AS house_id,
    h.name AS house_name,
    hs.qty
FROM house_stock hs
JOIN products p ON p.id = hs.product_id
JOIN houses h ON h.id = hs.house_id
WHERE hs.qty < 5
ORDER BY hs.qty ASC
");
$stmt->execute();
$rows = $stmt->fetchAll();


// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;
?>



<div class="container" style="max-width:1000px">
<h4 class="mb-3 text-danger">⚠ Produits en stock bas</h4>
<?php foreach($houses as $h): ?>
  <a href="<?=PRODUCTS_MANAGE?>?house_id=<?= $h['id']?>" class="btn btn-light ">← Retour</a>
<?php endforeach; ?>
<?php if(!$rows): ?>
  <div class="alert alert-success">
    Aucun produit en rupture ou stock bas.
  </div>
<?php else: ?>

<table class="table table-bordered table-sm align-middle">
<thead class="table-light">
<tr>
  <th>Produit</th>
  <th>Maison</th>
  <th>Stock actuel</th>
  <th>Action</th>
</tr>
</thead>
<tbody>

<?php foreach($rows as $r): ?>
<tr class="<?= $r['qty'] <= 0 ? 'table-danger' : 'table-warning' ?>">
  <td><?= htmlspecialchars($r['product_name']) ?></td>
  <td><?= htmlspecialchars($r['house_name']) ?></td>
  <td><strong><?= (int)$r['qty'] ?></strong></td>
  <td>
    <a class="btn btn-sm btn-outline-info"
       href="product_history.php?product_id=<?= $r['product_id'] ?>&house_id=<?= $r['house_id'] ?>">
       Historique
    </a>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>

