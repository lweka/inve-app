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
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Stock Bas – Cartelplus Congo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root {
    --pp-blue: #0070e0; --pp-blue-dark: #003087; --pp-bg: #f5f7fb;
    --pp-text: #0b1f3a; --pp-card: #ffffff; --pp-border: #e5e9f2;
    --pp-shadow: 0 12px 30px rgba(0, 48, 135, 0.08);
    --pp-warning: #f59e0b; --pp-danger: #dc2626; --pp-success: #1f8f6a;
}
body {
    background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                var(--pp-bg);
    color: var(--pp-text); min-height: 100vh;
    font-family: "Segoe UI", system-ui, sans-serif;
}
.page-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 16px 60px; }
.page-hero { background: linear-gradient(135deg, var(--pp-warning), #d97706);
    color: #fff; border-radius: 20px; padding: 28px;
    box-shadow: 0 18px 36px rgba(245, 158, 11, 0.2);
    margin-bottom: 26px; animation: fadeSlide 0.7s ease both; }
.page-hero h3 { font-size: 26px; font-weight: 700; margin: 0; }
.table-container { background: var(--pp-card); border: 1px solid var(--pp-border);
    border-radius: 16px; overflow: hidden; box-shadow: var(--pp-shadow);
    animation: fadeUp 0.7s ease both; }
.table thead th { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; border: none; padding: 14px; font-weight: 600; }
.table tbody td { padding: 12px 14px; border-color: var(--pp-border); }
.table-warning { background: rgba(245, 158, 11, 0.1) !important; }
.table-danger { background: rgba(220, 38, 38, 0.1) !important; }
.btn-pp { display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: 999px; border: 1px solid transparent;
    font-weight: 600; font-size: 14px; text-decoration: none;
    transition: transform 0.2s ease; cursor: pointer; }
.btn-pp-primary { background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff; box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25); }
.btn-pp-secondary { background: #fff; color: var(--pp-blue-dark); border-color: var(--pp-border); }
.btn-pp:hover { transform: translateY(-1px); opacity: 0.95; }
.btn-sm { padding: 7px 14px; font-size: 13px; }
.alert { border-radius: 12px; padding: 14px 18px; animation: fadeSlide 0.5s ease both; border: none; }
.alert-success { background: rgba(31, 143, 106, 0.15); color: var(--pp-success);
    border-left: 4px solid var(--pp-success); }
@keyframes fadeSlide { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<div class="page-wrap">
  <div class="page-hero">
    <h3><i class="fa-solid fa-triangle-exclamation"></i> Produits en stock bas</h3>
  </div>
  
  <a href="<?= DASHBOARD_ADMIN ?>" class="btn-pp btn-pp-secondary mb-3">
    <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
  </a>

<?php if(!$rows): ?>
  <div class="alert alert-success">
    Aucun produit en rupture ou stock bas.
  </div>
<?php else: ?>

<div class="table-container">
<table class="table table-sm align-middle mb-0">
<thead>
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
    <a class="btn-pp btn-pp-primary btn-sm"
       href="product_history.php?product_id=<?= $r['product_id'] ?>&house_id=<?= $r['house_id'] ?>">
       <i class="fa-solid fa-clock-rotate-left"></i> Historique
    </a>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

