<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: connect-parse.php?role=admin");
    exit;
}

// récupération des maisons du client connecté
$stmt = $pdo->prepare("SELECT * FROM houses WHERE client_code = ? ORDER BY id DESC");
$stmt->execute([$client_code]);
$houses = $stmt->fetchAll();

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
   PARAMÈTRES
========================= */
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, (int)($_GET['limit'] ?? 10));
$offset = ($page - 1) * $limit;

$product_id = (int)($_GET['product_id'] ?? 0);
$agent_id   = (int)($_GET['agent_id'] ?? 0);
$type       = $_GET['movement_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

/* =========================
   FILTRES
========================= */
$where = " WHERE pm.client_code = ? ";
$params = [$client_code];

if($product_id > 0){
    $where .= " AND pm.product_id = ? ";
    $params[] = $product_id;
}

if($agent_id > 0){
    $where .= " AND pm.agent_id = ? ";
    $params[] = $agent_id;
}

if(in_array($type, ['in','sale','out'])){
    $where .= " AND pm.type = ? ";
    $params[] = $type;
}

if($start_date !== ''){
    $where .= " AND pm.created_at >= ? ";
    $params[] = $start_date.' 00:00:00';
}

if($end_date !== ''){
    $where .= " AND pm.created_at <= ? ";
    $params[] = $end_date.' 23:59:59';
}

/* =========================
   TOTAL
========================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM product_movements pm
    $where
");
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

/* =========================
   HISTORIQUE
========================= */
$sql = "
SELECT 
    pm.created_at,
    pm.type,
    pm.qty,
    pm.unit_buy_price_cdf,
    pm.unit_sell_price_cdf,
    (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf) AS margin_unit,
    (pm.qty * (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf)) AS margin_total,
    pm.note,
    p.name AS product_name,
    h.name AS house_name,
    a.fullname AS agent_name
FROM product_movements pm
JOIN products p ON p.id = pm.product_id
JOIN houses h ON h.id = pm.house_id
LEFT JOIN agents a ON a.id = pm.agent_id
$where
ORDER BY pm.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* =========================
   DONNÉES FILTRES
========================= */
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE client_code = ? ORDER BY name");
$stmt->execute([$client_code]);
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, fullname FROM agents WHERE client_code = ? ORDER BY fullname");
$stmt->execute([$client_code]);
$agents = $stmt->fetchAll();



// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;
?>



<div class="container" style="max-width:1400px">
<h4>Historique global des produits</h4>
<?php foreach($houses as $h): ?>
  <a href="<?=PRODUCTS_MANAGE?>?house_id=<?= $h['id']?>" class="btn btn-light ">← Retour</a>
<?php endforeach; ?>

<form method="GET" class="row g-2 mb-3">
<div class="col-md-2">
<select name="product_id" class="form-select">
<option value="">Tous produits</option>
<?php foreach($products as $p): ?>
<option value="<?= $p['id'] ?>" <?= $product_id==$p['id']?'selected':'' ?>>
<?= htmlspecialchars($p['name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select name="agent_id" class="form-select">
<option value="">Tous vendeurs</option>
<?php foreach($agents as $a): ?>
<option value="<?= $a['id'] ?>" <?= $agent_id==$a['id']?'selected':'' ?>>
<?= htmlspecialchars($a['fullname']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select name="movement_type" class="form-select">
<option value="">Tous types</option>
<option value="in">Entrée</option>
<option value="sale">Vente</option>
<option value="out">Sortie</option>
</select>
</div>

<div class="col-md-2"><input type="date" name="start_date" class="form-control"></div>
<div class="col-md-2"><input type="date" name="end_date" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Filtrer</button></div>
</form>

<table class="table table-sm table-striped">
<thead>
<tr>
<th>Date</th>
<th>Produit</th>
<th>Maison</th>
<th>Type</th>
<th>Vendeur</th>
<th>Qté</th>
<th>Marge unitaire</th>
<th>Marge totale</th>
<th>Note</th>
</tr>
</thead>
<tbody>

<?php if(!$rows): ?>
<tr><td colspan="9">Aucune donnée</td></tr>
<?php endif; ?>

<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['created_at'] ?></td>
<td><?= htmlspecialchars($r['product_name']) ?></td>
<td><?= htmlspecialchars($r['house_name']) ?></td>
<td><?= strtoupper($r['type']) ?></td>
<td><?= htmlspecialchars($r['agent_name'] ?? '-') ?></td>
<td><?= $r['qty'] ?></td>
<td><?= number_format((float)$r['margin_unit'],0) ?> CDF</td>
<td><strong><?= number_format((float)$r['margin_total'],0) ?> CDF</strong></td>
<td><?= htmlspecialchars($r['note']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<nav>
<ul class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>

</div>

<?php 
if(isset($footerPath) && is_file($footerPath)) require_once $footerPath;
?>
