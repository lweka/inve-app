<?php
// pagesweb_cn/inventory.php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){
    header("Location: ./connect-parse.php?role=admin");
    exit;
}

// filters
$house_id = intval($_GET['house_id'] ?? 0);
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$q = trim($_GET['q'] ?? '');

// build product totals using product_movements or house_stock
$sql = "SELECT p.id, p.house_id, p.name, p.price, IFNULL(hs.qty,0) AS stock_qty,
        COALESCE( (SELECT SUM(pm.qty_change) FROM product_movements pm WHERE pm.product_id=p.id AND pm.type='in' " . ($from? " AND pm.created_at>=? " : "") . ($to? " AND pm.created_at<=? " : "") . "), 0) AS total_in,
        COALESCE( (SELECT SUM(pm.qty_change) FROM product_movements pm WHERE pm.product_id=p.id AND pm.type='out' " . ($from? " AND pm.created_at>=? " : "") . ($to? " AND pm.created_at<=? " : "") . "), 0) AS total_out
        FROM products p
        LEFT JOIN house_stock hs ON hs.product_id = p.id" ;
$conds = [];
$params = [];

if($house_id > 0){
    $conds[] = "p.house_id = ?";
    $params[] = $house_id;
}
if($q !== ''){
    $conds[] = "p.name LIKE ?";
    $params[] = '%'.$q.'%';
}
if($conds) $sql .= " WHERE " . implode(" AND ", $conds);
$sql .= " ORDER BY p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

// houses for filter
$houses = $pdo->query("SELECT id,name FROM houses ORDER BY name")->fetchAll();

if(isset($_GET['export']) && $_GET['export']==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventory_export.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['House','Product','Price','Stock','Total In','Total Out']);
    foreach($list as $r){
        // determine house name
        $h = '';
        foreach($houses as $hh) if($hh['id'] == $r['house_id']) { $h = $hh['name']; break; }
        fputcsv($out, [$h, $r['name'], $r['price'], $r['stock_qty'], $r['total_in'], $r['total_out']]);
    }
    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Inventaire</title>
<link href="../css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container" style="max-width:1100px">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4>Inventaire global</h4><div class="small text-muted">Filtrer et exporter</div></div>
    <div><a class="btn btn-light" href="./houses.php">← Maisons</a></div>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-auto">
      <select name="house_id" class="form-select">
        <option value="0">Toutes les maisons</option>
        <?php foreach($houses as $h): ?>
          <option value="<?= $h['id'] ?>" <?= $h['id']==$house_id ? 'selected' : '' ?>><?= htmlspecialchars($h['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto"><input type="text" name="q" class="form-control" placeholder="Recherche produit..." value="<?= htmlspecialchars($q) ?>"></div>
    <div class="col-auto"><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
    <div class="col-auto"><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
    <div class="col-auto"><button class="btn btn-primary">Filtrer</button></div>
    <div class="col-auto"><a class="btn btn-secondary" href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>">Exporter CSV</a></div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-sm">
      <thead><tr><th>Maison</th><th>Produit</th><th>Prix</th><th>Stock</th><th>Total entré</th><th>Total sorti</th><th></th></tr></thead>
      <tbody>
        <?php if(!$list): ?>
          <tr><td colspan="7">Aucun produit.</td></tr>
        <?php else: foreach($list as $r): 
            $houseName = '';
            foreach($houses as $hh) if($hh['id']==$r['house_id']){ $houseName = $hh['name']; break; }
        ?>
          <tr>
            <td><?= htmlspecialchars($houseName) ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= number_format($r['price'],2) ?></td>
            <td><?= intval($r['stock_qty']) ?></td>
            <td><?= intval($r['total_in']) ?></td>
            <td><?= intval($r['total_out']) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="./product_history.php?product_id=<?= $r['id'] ?>&house_id=<?= $r['house_id'] ?>">Historique</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
