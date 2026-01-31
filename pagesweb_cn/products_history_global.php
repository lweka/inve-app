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

$house_id   = (int)($_GET['house_id'] ?? 0);
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

if($house_id > 0){
    $where .= " AND pm.house_id = ? ";
    $params[] = $house_id;
}

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
    CASE
        WHEN pm.unit_sell_price_cdf IS NOT NULL
         AND pm.unit_buy_price_cdf IS NOT NULL
         AND (pm.unit_sell_price_cdf != 0 OR pm.unit_buy_price_cdf != 0)
        THEN (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf)
        ELSE (COALESCE(NULLIF(pm.unit_sell_price, 0), p.sell_price) - COALESCE(NULLIF(pm.unit_buy_price, 0), p.buy_price))
    END AS margin_unit,
    CASE
        WHEN pm.unit_sell_price_cdf IS NOT NULL
         AND pm.unit_buy_price_cdf IS NOT NULL
         AND (pm.unit_sell_price_cdf != 0 OR pm.unit_buy_price_cdf != 0)
        THEN (pm.qty * (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf))
        ELSE (pm.qty * (COALESCE(NULLIF(pm.unit_sell_price, 0), p.sell_price) - COALESCE(NULLIF(pm.unit_buy_price, 0), p.buy_price)))
    END AS margin_total,
    CASE
        WHEN pm.unit_sell_price_cdf IS NOT NULL
         AND pm.unit_buy_price_cdf IS NOT NULL
         AND (pm.unit_sell_price_cdf != 0 OR pm.unit_buy_price_cdf != 0)
        THEN 'CDF'
        ELSE p.sell_currency
    END AS margin_currency,
    pm.note,
    p.name AS product_name,
    p.sell_currency,
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
if($house_id > 0){
    $stmt = $pdo->prepare("SELECT DISTINCT p.id, p.name FROM products p JOIN house_stock hs ON hs.product_id = p.id WHERE p.client_code = ? AND hs.house_id = ? ORDER BY p.name");
    $stmt->execute([$client_code, $house_id]);
    $products = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE client_code = ? ORDER BY name");
    $stmt->execute([$client_code]);
    $products = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT id, fullname FROM agents WHERE client_code = ? ORDER BY fullname");
$stmt->execute([$client_code]);
$agents = $stmt->fetchAll();



// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Historique Global – Cartelplus Congo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root {
    --pp-blue: #0070e0;
    --pp-blue-dark: #003087;
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
    min-height: 100vh;
    font-family: "Segoe UI", system-ui, sans-serif;
}
.page-wrap { max-width: 1400px; margin: 0 auto; padding: 32px 16px 60px; }
.page-hero {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; border-radius: 20px; padding: 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    margin-bottom: 26px; animation: fadeSlide 0.7s ease both;
}
.page-hero h3 { font-size: 26px; font-weight: 700; margin: 0; }
.filter-card { background: var(--pp-card); border: 1px solid var(--pp-border);
    border-radius: 16px; padding: 20px; box-shadow: var(--pp-shadow);
    margin-bottom: 20px; animation: fadeUp 0.6s ease both; }
.table-container { background: var(--pp-card); border: 1px solid var(--pp-border);
    border-radius: 16px; overflow: hidden; box-shadow: var(--pp-shadow);
    animation: fadeUp 0.7s ease both; }
.table thead th { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; border: none; padding: 14px; font-weight: 600; }
.table tbody td { padding: 12px 14px; border-color: var(--pp-border); }
.table-striped tbody tr:nth-of-type(odd) { background: rgba(0,112,224,0.02); }
.table-hover tbody tr:hover { background: rgba(0,112,224,0.06); }
.btn-pp { display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: 999px; border: 1px solid transparent;
    font-weight: 600; font-size: 14px; text-decoration: none;
    transition: transform 0.2s ease; cursor: pointer; }
.btn-pp-primary { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25); }
.btn-pp-secondary { background: #fff; color: var(--pp-blue-dark); border-color: var(--pp-border); }
.btn-pp:hover { transform: translateY(-1px); opacity: 0.95; }
.form-control, .form-select { border-radius: 8px; border: 1px solid var(--pp-border); padding: 10px 14px; }
.form-control:focus, .form-select:focus { border-color: var(--pp-blue); box-shadow: 0 0 0 3px rgba(0,112,224,0.1); }
.pagination .page-link { border-radius: 8px; margin: 0 3px; border-color: var(--pp-border); color: var(--pp-blue-dark); }
.pagination .page-item.active .page-link { background: var(--pp-blue); border-color: var(--pp-blue); }
@keyframes fadeSlide { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<div class="page-wrap">
  <div class="page-hero">
    <h3><i class="fa-solid fa-clock-rotate-left"></i> Historique global des produits</h3>
  </div>
  
  <?php if($house_id > 0): ?>
    <a href="<?= PRODUCTS_MANAGE ?>?house_id=<?= (int)$house_id ?>" class="btn-pp btn-pp-secondary mb-3">
      <i class="fa-solid fa-arrow-left"></i> Retour aux produits
    </a>
  <?php else: ?>
    <a href="<?= DASHBOARD_ADMIN ?>" class="btn-pp btn-pp-secondary mb-3">
      <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
    </a>
  <?php endif; ?>

<div class="filter-card">
<form method="GET" class="row g-3">
<?php if($house_id > 0): ?>
  <input type="hidden" name="house_id" value="<?= (int)$house_id ?>">
<?php endif; ?>
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
<div class="col-md-2"><button class="btn-pp btn-pp-primary w-100" style="justify-content: center;"><i class="fa-solid fa-filter"></i> Filtrer</button></div>
</form>
</div>

<div class="table-container">
<table class="table table-sm table-striped table-hover mb-0">
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
<?php
    $marginCurrency = $r['margin_currency'] ?? 'CDF';
    $marginDecimals = ($marginCurrency === 'USD') ? 2 : 0;
?>
<td><?= number_format((float)$r['margin_unit'], $marginDecimals) ?> <?= htmlspecialchars($marginCurrency) ?></td>
<td><?= htmlspecialchars($r['margin_total'] ?? 0) ?> <?= htmlspecialchars($marginCurrency) ?></strong></td>
<td><?= htmlspecialchars($r['note'] ?? '-') ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<nav class="mt-4">
<ul class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): 
  $query_params = ['page' => $i];
  if($house_id > 0) $query_params['house_id'] = $house_id;
  if($product_id > 0) $query_params['product_id'] = $product_id;
  if($agent_id > 0) $query_params['agent_id'] = $agent_id;
  if($type) $query_params['movement_type'] = $type;
  if($start_date) $query_params['start_date'] = $start_date;
  if($end_date) $query_params['end_date'] = $end_date;
  $query_string = http_build_query($query_params);
?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?<?= $query_string ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
if(isset($footerPath) && is_file($footerPath)) require_once $footerPath;
?>
